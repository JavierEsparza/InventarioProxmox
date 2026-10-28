<?php

namespace App\Http\Controllers;

use App\Models\PbsServer;
use App\Models\PbsSnapshot;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use App\Models\node;
use App\Models\ClusterCredentials;
use App\Services\PbsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ProxmoxBackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $servers = PbsServer::with(['datastores', 'tasks' => fn($q) => $q->latest('starttime')->limit(8)])->orderBy('role')->orderBy('name')->get();
        $totalServers = $servers->count();
        $onlineServers = $servers->where('status', 'online')->count();
        $datastores = $servers->flatMap->datastores;
        $totalBytes = $datastores->sum(fn($d) => (int)($d->total ?? 0));
        $usedBytes = $datastores->sum(fn($d) => (int)($d->used ?? 0));
        $usagePercent = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 2) : 0;
        $recentTasks = \App\Models\PbsTask::with('server')
            ->where(function ($query) {
                $query->where('status', 'like', '%error%')->orWhere('status', 'like', '%fail%');
            })
            ->latest('starttime')
            ->limit(10)
            ->get();
        $errorTasks = $recentTasks;
        $namespaceMappings = Cache::remember('pbs_namespace_cluster_mappings', now()->addHour(), fn() => $this->buildNamespaceClusterMappings());
        $backupRows = $this->buildBackupRows($namespaceMappings);
        $backupsTotal = $backupRows->count();
        $namespacesTotal = PbsSnapshot::query()->select('namespace')->distinct()->count('namespace');
        $backupsReplicated = $backupRows->where('replication_status', 'ok')->count();
        $backupsMissingSlave = $backupRows->where('replication_status', 'missing_slave')->count();
        $retentionAlerts = $backupRows->filter(fn($row) => ($row->master_count ?? 0) > 4 || ($row->slave_count ?? 0) > 4)->values();
        $backupScheduleAudit = Cache::remember('pbs_vm_without_backup_schedule_audit', now()->addMinutes(15), fn() => $this->buildVmBackupScheduleAudit());
        $vmsWithoutBackup = collect($backupScheduleAudit['rows'] ?? []);
        $backupScheduleSummary = collect($backupScheduleAudit['summary'] ?? []);
        $namespaceRows = $backupRows->groupBy(fn($row) => $row->namespace ?: 'root')->map(function ($rows, $namespace) {
            return (object) [
                'namespace' => $namespace,
                'rows' => $rows,
                'total' => $rows->count(),
                'ok' => $rows->where('replication_status', 'ok')->count(),
                'missing_slave' => $rows->where('replication_status', 'missing_slave')->count(),
                'stale_slave' => $rows->where('replication_status', 'stale_slave')->count(),
                'retention_alerts' => $rows->filter(fn($row) => ($row->master_count ?? 0) > 4 || ($row->slave_count ?? 0) > 4)->count(),
            ];
        })->sortKeys();
        $recommendedActions = $this->buildRecommendedActions($vmsWithoutBackup, $retentionAlerts, $backupRows, $datastores, $recentTasks);
        return view('proxmox-backup.index', compact('servers', 'totalServers', 'onlineServers', 'datastores', 'totalBytes', 'usedBytes', 'usagePercent', 'recentTasks', 'errorTasks', 'backupRows', 'namespaceRows', 'backupsTotal', 'namespacesTotal', 'backupsReplicated', 'backupsMissingSlave', 'retentionAlerts', 'vmsWithoutBackup', 'backupScheduleSummary', 'backupScheduleAudit', 'recommendedActions', 'namespaceMappings'));
    }

    private function buildBackupRows(array $namespaceMappings = [])
    {
        $snapshots = PbsSnapshot::with('server')->get()->groupBy(function ($s) {
            $namespace = $s->namespace ?: '';
            $id = $s->vmid ?: $s->backup_id;
            return $namespace . '|' . $s->backup_type . '|' . $id;
        });
        return $snapshots->map(function ($items, $key) use ($namespaceMappings) {
            $masterItems = $items->filter(fn($s) => optional($s->server)->role === 'master');
            $slaveItems = $items->filter(fn($s) => optional($s->server)->role === 'slave');
            $master = $masterItems->sortByDesc('backup_time')->first();
            $slave = $slaveItems->sortByDesc('backup_time')->first();
            $latest = $items->sortByDesc('backup_time')->first();
            $pbsVmName = $this->pbsSnapshotVmName($latest);
            $mappedCluster = $namespaceMappings[$latest->namespace ?: ''] ?? null;
            $qemu = $this->findQemuForBackupSnapshot($latest);
            $status = 'no_master';
            if ($master && $slave) {
                $status = $slave->backup_time >= $master->backup_time->copy()->subHours(24) ? 'ok' : 'stale_slave';
            } elseif ($master && !$slave) {
                $status = 'missing_slave';
            } elseif (!$master && $slave) {
                $status = 'slave_only';
            }
            return (object)[
                'key' => $key,
                'namespace' => $latest->namespace ?: 'Sin namespace',
                'vmid' => $latest->vmid,
                'backup_id' => $latest->backup_id,
                'backup_type' => $latest->backup_type,
                'pbs_vm_name' => $pbsVmName,
                'vm_name' => $pbsVmName ?: ($qemu->name ?? null),
                'cluster_name' => $qemu->cluster_name ?? null,
                'mapped_cluster' => $mappedCluster,
                'ip_address' => $qemu->ip_address ?? null,
                'os_name' => $qemu->os_name ?? null,
                'qemu_exact_match' => (bool)$qemu,
                'master' => $master,
                'slave' => $slave,
                'master_count' => $masterItems->count(),
                'slave_count' => $slaveItems->count(),
                'master_snapshots_count' => $masterItems->count(),
                'slave_snapshots_count' => $slaveItems->count(),
                'snapshots_count' => $items->count(),
                'retention_alert' => $masterItems->count() > 4 || $slaveItems->count() > 4,
                'retention_diff' => abs($masterItems->count() - $slaveItems->count()),
                'replication_status' => $status,
            ];
        })->sortByDesc(fn($r) => optional($r->master ?: $r->slave)->backup_time)->values();
    }



    private function buildRecommendedActions($vmsWithoutBackup, $retentionAlerts, $backupRows, $datastores, $recentTasks): array
    {
        $actions = [];
        if ($vmsWithoutBackup->count() > 0) {
            $actions[] = ['level'=>'danger','title'=>'VMs sin horario de backup','count'=>$vmsWithoutBackup->count(),'message'=>'Revisar jobs de backup Proxmox e incluir estas VMs en un job habilitado con schedule. Esta plataforma solo informa; no crea ni modifica jobs.'];
        }
        $stale = $backupRows->where('replication_status', 'stale_slave')->count();
        if ($stale > 0) {
            $actions[] = ['level'=>'warning','title'=>'Slave atrasado','count'=>$stale,'message'=>'Revisar el sync job PBS Master → Slave, último estado de tareas y conectividad entre PBS.'];
        }
        if ($retentionAlerts->count() > 0) {
            $actions[] = ['level'=>'warning','title'=>'Retención > 4','count'=>$retentionAlerts->count(),'message'=>'Revisar prune jobs y política de retención en Master/Slave para alinear la cantidad esperada de snapshots.'];
        }
        $criticalDatastores = $datastores->filter(fn($d) => (float)($d->usage_percent ?? 0) >= 90);
        if ($criticalDatastores->count() > 0) {
            $actions[] = ['level'=>'danger','title'=>'PBS datastore > 90%','count'=>$criticalDatastores->count(),'message'=>'Revisar prune/GC, tareas fallidas y capacidad del datastore; evaluar expansión si sigue sobre 90%.'];
        }
        if ($recentTasks->count() > 0) {
            $actions[] = ['level'=>'danger','title'=>'Tareas PBS con error','count'=>$recentTasks->count(),'message'=>'Revisar el detalle del task en PBS. Priorizar errores de syncjob, verify, prune, GC y conexión.'];
        }
        $qgaDown = \App\Models\qemu::where('status','running')->where(function($q){
            $q->whereNull('qga_enabled_config')->orWhere('qga_enabled_config',0)->orWhereNull('ip_address')->orWhereNull('os_name');
        })->count();
        if ($qgaDown > 0) {
            $actions[] = ['level'=>'info','title'=>'QGA no disponible o sin datos','count'=>$qgaDown,'message'=>'Instalar/iniciar qemu-guest-agent en la VM o revisar que Proxmox tenga Agent habilitado. La plataforma solo muestra la recomendación.'];
        }
        return $actions;
    }

    private function buildNamespaceClusterMappings(): array
    {
        $client = new Client(['verify' => false, 'timeout' => 10, 'connect_timeout' => 3]);
        $map = [];
        $clusters = node::whereNotNull('cluster_name')->whereNotNull('ip')->get()->groupBy('cluster_name');
        foreach ($clusters as $clusterName => $nodes) {
            try {
                [$baseUrl, $headers] = $this->pveAuthForNode($client, $nodes->first());
                if (!$baseUrl) continue;
                $storages = collect($this->pveApi($client, 'GET', $baseUrl.'/storage', $headers))
                    ->filter(fn($s) => ($s['type'] ?? '') === 'pbs' && (string)($s['disable'] ?? '0') !== '1' && !empty($s['namespace']))
                    ->values();
                foreach ($storages as $storage) {
                    $namespace = (string)$storage['namespace'];
                    if (!isset($map[$namespace])) $map[$namespace] = [];
                    if (!in_array((string)$clusterName, $map[$namespace], true)) $map[$namespace][] = (string)$clusterName;
                }
            } catch (\Throwable $e) {}
        }
        return $map;
    }

    private function pbsSnapshotVmName($snapshot): ?string
    {
        if (!$snapshot) return null;
        $raw = is_array($snapshot->raw ?? null) ? $snapshot->raw : [];
        foreach (['comment', 'notes', 'name', 'hostname'] as $key) {
            $value = trim((string)($raw[$key] ?? ''));
            if ($value !== '') return $value;
        }
        return null;
    }

    private function findQemuForBackupSnapshot($snapshot)
    {
        if (!$snapshot || !$snapshot->vmid) return null;
        $pbsName = $this->pbsSnapshotVmName($snapshot);
        if (!$pbsName) return null;
        // Regla solicitada: solo cargar IP/SO si hay coincidencia exacta ID + nombre VM.
        return \App\Models\qemu::where('vmid', $snapshot->vmid)
            ->where('name', $pbsName)
            ->latest('updated_at')
            ->first();
    }

    public function refreshBackupScheduleAudit()
    {
        Cache::forget('pbs_vm_without_backup_schedule_audit');
        Cache::put('pbs_vm_without_backup_schedule_audit', $this->buildVmBackupScheduleAudit(), now()->addMinutes(15));
        return redirect()->route('pbs.index')->with('success', 'Auditoría de horarios de backup Proxmox actualizada.');
    }

    private function buildVmBackupScheduleAudit(): array
    {
        $client = new Client(['verify' => false, 'timeout' => 12, 'connect_timeout' => 4]);
        $summary = [];
        $rows = [];
        $manualDnsSummary = [];
        $manualDnsRows = [];
        $manualDnsClusters = ['DNSCCP', 'DNSLUMEN', 'DNSUFINET01'];
        $clusters = node::whereNotNull('cluster_name')->whereNotNull('ip')->get()->groupBy('cluster_name');
        foreach ($clusters as $clusterName => $nodes) {
            $first = $nodes->first();
            try {
                [$baseUrl, $headers] = $this->pveAuthForNode($client, $first);
                if (!$baseUrl) throw new \Exception('Sin credenciales PVE para el nodo '.$first->node);
                $resources = $this->pveApi($client, 'GET', $baseUrl.'/cluster/resources', $headers);
                // Auditoría operativa: solo VMs encendidas. Las stopped no se listan como alertas.
                $vms = collect($resources)->filter(fn($r) => ($r['type'] ?? '') === 'qemu' && ($r['status'] ?? '') === 'running')->values();
                $jobs = collect($this->pveApi($client, 'GET', $baseUrl.'/cluster/backup', $headers))
                    ->filter(fn($j) => !isset($j['enabled']) || (string)$j['enabled'] !== '0')
                    ->values();
                $poolMembers = $this->pvePoolMembers($client, $baseUrl, $headers);
                $covered = 0;
                $isManualDnsCluster = in_array(strtoupper(trim((string)$clusterName)), $manualDnsClusters, true);
                foreach ($vms as $vm) {
                    $isCovered = $jobs->contains(fn($job) => $this->pveBackupJobCoversVm($job, $vm, $poolMembers));
                    if ($isCovered) { $covered++; continue; }
                    $row = [
                        'cluster' => (string)$clusterName,
                        'vmid' => (string)($vm['vmid'] ?? ''),
                        'name' => (string)($vm['name'] ?? ''),
                        'node' => (string)($vm['node'] ?? ''),
                        'status' => (string)($vm['status'] ?? ''),
                    ];
                    if ($isManualDnsCluster) $manualDnsRows[] = $row;
                    else $rows[] = $row;
                }
                $clusterSummary = ['cluster'=>(string)$clusterName, 'total'=>$vms->count(), 'covered'=>$covered, 'uncovered'=>$vms->count()-$covered, 'jobs'=>$jobs->count(), 'scope'=>'running', 'error'=>null];
                if ($isManualDnsCluster) $manualDnsSummary[] = $clusterSummary;
                else $summary[] = $clusterSummary;
            } catch (\Throwable $e) {
                $errorSummary = ['cluster'=>(string)$clusterName, 'total'=>0, 'covered'=>0, 'uncovered'=>0, 'jobs'=>0, 'scope'=>'running', 'error'=>$e->getMessage()];
                if (in_array(strtoupper(trim((string)$clusterName)), $manualDnsClusters, true)) $manualDnsSummary[] = $errorSummary;
                else $summary[] = $errorSummary;
            }
        }
        usort($rows, fn($a, $b) => [$a['cluster'], (int)$a['vmid']] <=> [$b['cluster'], (int)$b['vmid']]);
        return ['generated_at'=>now()->format('Y-m-d H:i:s'), 'summary'=>$summary, 'rows'=>$rows, 'manual_dns_summary'=>$manualDnsSummary, 'manual_dns_rows'=>$manualDnsRows];
    }

    private function pveAuthForNode(Client $client, $node): array
    {
        $credentials = ClusterCredentials::where('ip', $node->ip)->first();
        if (!$credentials) return [null, []];
        $baseUrl = 'https://'.$node->ip.':8006/api2/json';
        if (strpos($credentials->username, '!') !== false) {
            return [$baseUrl, ['Authorization' => 'PVEAPIToken='.$credentials->username.'='.Crypt::decrypt($credentials->password)]];
        }
        $username = $credentials->username;
        if (strpos($username, '@') === false) $username .= '@pam';
        $ticket = $this->pveApi($client, 'POST', $baseUrl.'/access/ticket', [], ['username'=>$username, 'password'=>Crypt::decrypt($credentials->password)]);
        return [$baseUrl, ['Cookie'=>'PVEAuthCookie='.$ticket['ticket'], 'CSRFPreventionToken'=>$ticket['CSRFPreventionToken'] ?? '']];
    }

    private function pveApi(Client $client, string $method, string $url, array $headers = [], array $form = [])
    {
        $options = ['headers'=>$headers, 'verify'=>false, 'timeout'=>12, 'connect_timeout'=>4];
        if ($form) $options['form_params'] = $form;
        $response = $client->request($method, $url, $options);
        $json = json_decode((string)$response->getBody(), true);
        return $json['data'] ?? $json;
    }

    private function pvePoolMembers(Client $client, string $baseUrl, array $headers): array
    {
        $poolMembers = [];
        try {
            foreach ($this->pveApi($client, 'GET', $baseUrl.'/pools', $headers) as $pool) {
                $poolId = $pool['poolid'] ?? null;
                if (!$poolId) continue;
                try {
                    $detail = $this->pveApi($client, 'GET', $baseUrl.'/pools/'.rawurlencode($poolId), $headers);
                    $members = [];
                    foreach (($detail['members'] ?? []) as $member) {
                        if (($member['type'] ?? '') === 'qemu' && isset($member['vmid'])) $members[] = (string)$member['vmid'];
                    }
                    $poolMembers[$poolId] = $members;
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {}
        return $poolMembers;
    }

    private function pveBackupJobCoversVm(array $job, array $vm, array $poolMembers): bool
    {
        if (isset($job['enabled']) && (string)$job['enabled'] === '0') return false;
        if (empty($job['schedule'])) return false;
        $vmid = (string)($vm['vmid'] ?? '');
        $excludes = $this->splitPveList($job['exclude'] ?? null);
        if (in_array($vmid, $excludes, true)) return false;
        if (!empty($job['node']) && (string)$job['node'] !== (string)($vm['node'] ?? '')) return false;
        $includes = $this->splitPveList($job['vmid'] ?? null);
        if ($includes) return in_array($vmid, $includes, true);
        if (!empty($job['pool'])) return isset($poolMembers[(string)$job['pool']]) && in_array($vmid, $poolMembers[(string)$job['pool']], true);
        return !empty($job['all']) && (string)$job['all'] !== '0';
    }

    private function splitPveList($value): array
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') return [];
        if (is_array($value)) return array_values(array_filter(array_map('strval', $value), fn($x) => $x !== ''));
        return array_values(array_filter(preg_split('/[,;\s]+/', (string)$value), fn($x) => $x !== ''));
    }


    public function exportVmWithoutBackupSchedule()
    {
        $audit = Cache::get('pbs_vm_without_backup_schedule_audit');
        if (!$audit) {
            $audit = $this->buildVmBackupScheduleAudit();
            Cache::put('pbs_vm_without_backup_schedule_audit', $audit, now()->addMinutes(15));
        }
        $rows = collect($audit['rows'] ?? []);
        $filename = 'vms_sin_horario_backup_' . now()->format('Ymd_His') . '.csv';
        $textCell = function ($value) {
            $value = str_replace('"', '""', (string)($value ?? ''));
            return '="' . $value . '"';
        };
        $callback = function () use ($rows, $textCell) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Cluster', 'VMID', 'Nombre VM', 'Nodo', 'Estado'], ';');
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['cluster'] ?? '',
                    $textCell($row['vmid'] ?? ''),
                    $row['name'] ?? '',
                    $row['node'] ?? '',
                    $row['status'] ?? '',
                ], ';');
            }
            fclose($out);
        };
        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request, PbsApiService $service)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:master,slave',
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'api_user' => 'required|string|max:255',
            'api_token_id' => 'required|string|max:255',
            'api_token_secret' => 'required|string',
            'verify_tls' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);
        $data['host'] = preg_replace('#^https?://#', '', trim($data['host']));
        $data['port'] = $data['port'] ?: 8007;
        $data['verify_tls'] = $request->boolean('verify_tls');
        $data['api_token_secret'] = Crypt::encryptString($data['api_token_secret']);
        $data['status'] = 'pending';

        $server = PbsServer::updateOrCreate(
            ['host' => $data['host'], 'port' => $data['port']],
            $data
        );
        try {
            $service->sync($server);
            return redirect()->route('pbs.index')->with('success', "PBS {$server->name} agregado y sincronizado correctamente.");
        } catch (\Throwable $e) {
            $server->update(['status' => 'error', 'last_error' => $e->getMessage()]);
            Log::error('PBS store/sync error: '.$e->getMessage(), ['server' => $server->id]);
            return redirect()->route('pbs.index')->with('error', "PBS guardado, pero falló la sincronización: {$e->getMessage()}");
        }
    }

    public function sync(PbsServer $server, PbsApiService $service)
    {
        try {
            $service->sync($server);
            return redirect()->route('pbs.index')->with('success', "PBS {$server->name} sincronizado correctamente.");
        } catch (\Throwable $e) {
            $server->update(['status' => 'error', 'last_error' => $e->getMessage()]);
            Log::error('PBS sync error: '.$e->getMessage(), ['server' => $server->id]);
            return redirect()->route('pbs.index')->with('error', "Error sincronizando {$server->name}: {$e->getMessage()}");
        }
    }

    public function destroy(PbsServer $server)
    {
        $name = $server->name;
        $server->delete();
        return redirect()->route('pbs.index')->with('success', "Conexión PBS {$name} eliminada de la plataforma.");
    }

}

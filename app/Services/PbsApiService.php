<?php

namespace App\Services;

use App\Models\PbsDatastore;
use App\Models\PbsServer;
use App\Models\PbsSnapshot;
use App\Models\PbsTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class PbsApiService
{
    private function baseUrl(PbsServer $server): string
    {
        $host = preg_replace('#^https?://#', '', trim($server->host));
        return 'https://' . $host . ':' . ($server->port ?: 8007) . '/api2/json';
    }

    private function client(PbsServer $server)
    {
        $secret = $server->api_token_secret ? Crypt::decryptString($server->api_token_secret) : '';
        $token = trim((string)$server->api_user) . '!' . trim((string)$server->api_token_id) . ':' . $secret;
        return Http::withHeaders(['Authorization' => 'PBSAPIToken=' . $token])
            ->withOptions(['verify' => (bool)$server->verify_tls, 'timeout' => 45, 'connect_timeout' => 8]);
    }

    public function api(PbsServer $server, string $path): array
    {
        $response = $this->client($server)->get($this->baseUrl($server) . $path);
        if (!$response->successful()) {
            throw new \RuntimeException('PBS API HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
        }
        return $response->json('data') ?? [];
    }

    public function sync(PbsServer $server): PbsServer
    {
        $version = $this->api($server, '/version');
        $server->version = $version['version'] ?? ($version['release'] ?? null);

        // El inventario PBS debe reflejar el estado actual post-prune/GC.
        // Por eso se reemplazan snapshots/tareas del servidor en cada sync,
        // evitando retenciones viejas acumuladas en la BD local.
        PbsSnapshot::where('pbs_server_id', $server->id)->delete();
        PbsTask::where('pbs_server_id', $server->id)->delete();

        $datastores = $this->api($server, '/admin/datastore');
        foreach ($datastores as $ds) {
            $name = $ds['store'] ?? $ds['name'] ?? null;
            if (!$name) continue;
            $status = [];
            try { $status = $this->api($server, '/admin/datastore/' . rawurlencode($name) . '/status'); } catch (\Throwable $e) {}
            $total = $status['total'] ?? $ds['total'] ?? null;
            $used = $status['used'] ?? $ds['used'] ?? null;
            $available = $status['avail'] ?? $status['available'] ?? (($total !== null && $used !== null) ? max($total - $used, 0) : null);
            $percent = ($total && $used !== null) ? round(($used / max($total, 1)) * 100, 2) : null;

            PbsDatastore::updateOrCreate(
                ['pbs_server_id' => $server->id, 'name' => $name],
                ['path' => $ds['path'] ?? null, 'total' => $total, 'used' => $used, 'available' => $available, 'usage_percent' => $percent]
            );
            $this->syncSnapshotsForDatastore($server, $name);
        }

        try {
            $tasks = $this->api($server, '/nodes/localhost/tasks?limit=200');
            foreach ($tasks as $task) {
                $upid = $task['upid'] ?? null;
                if (!$upid) continue;
                PbsTask::updateOrCreate(
                    ['pbs_server_id' => $server->id, 'upid' => $upid],
                    [
                        'type' => $task['worker_type'] ?? $task['type'] ?? null,
                        'status' => $task['status'] ?? null,
                        'starttime' => isset($task['starttime']) ? Carbon::createFromTimestamp($task['starttime']) : null,
                        'endtime' => isset($task['endtime']) ? Carbon::createFromTimestamp($task['endtime']) : null,
                        'user' => $task['user'] ?? null,
                        'worker_id' => $task['worker_id'] ?? null,
                        'summary' => isset($task['status']) ? (string)$task['status'] : null,
                    ]
                );
            }
        } catch (\Throwable $e) {}

        $server->status = 'online';
        $server->last_error = null;
        $server->last_sync_at = now();
        $server->save();
        return $server;
    }

    private function syncSnapshotsForDatastore(PbsServer $server, string $store): void
    {
        $namespaces = [''];
        try {
            $nsRows = $this->api($server, '/admin/datastore/' . rawurlencode($store) . '/namespace');
            foreach ($nsRows as $row) {
                $ns = (string)($row['ns'] ?? '');
                if (!in_array($ns, $namespaces, true)) $namespaces[] = $ns;
            }
        } catch (\Throwable $e) {
            // Namespace endpoint may not exist in older PBS; root namespace is still queried below.
        }

        foreach ($namespaces as $namespace) {
            $query = $namespace !== '' ? '?ns=' . rawurlencode($namespace) : '';
            try {
                $snapshots = $this->api($server, '/admin/datastore/' . rawurlencode($store) . '/snapshots' . $query);
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($snapshots as $snap) {
                $backupType = $snap['backup-type'] ?? $snap['backup_type'] ?? null;
                $backupId = $snap['backup-id'] ?? $snap['backup_id'] ?? null;
                $backupTimeRaw = $snap['backup-time'] ?? $snap['backup_time'] ?? null;
                if (!$backupType || !$backupId || $backupTimeRaw === null) continue;
                $backupTime = is_numeric($backupTimeRaw) ? Carbon::createFromTimestamp((int)$backupTimeRaw) : Carbon::parse($backupTimeRaw);
                $vmid = null;
                if (preg_match('/^(?:qemu|vm|ct|lxc)[\/-]?(\d+)$/i', (string)$backupId, $m) || preg_match('/^(\d+)$/', (string)$backupId, $m)) {
                    $vmid = (int)$m[1];
                }
                PbsSnapshot::updateOrCreate(
                    [
                        'pbs_server_id' => $server->id,
                        'datastore' => $store,
                        'namespace' => $namespace,
                        'backup_type' => $backupType,
                        'backup_id' => (string)$backupId,
                        'backup_time' => $backupTime,
                    ],
                    [
                        'vmid' => $vmid,
                        'size' => $snap['size'] ?? $snap['backup-size'] ?? null,
                        'protected' => (bool)($snap['protected'] ?? false),
                        'verified' => is_array($snap['verification'] ?? null) ? json_encode($snap['verification']) : ($snap['verification'] ?? null),
                        'raw' => $snap,
                    ]
                );
            }
        }
    }
}
<?php

namespace App\Console\Commands;

use App\Models\qemu;
use App\Models\ClusterCredentials;
use App\Services\Proxmox\AuthenticationService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RefreshQgaIpOsSource extends Command
{
    protected $signature = 'qemu:refresh-qga-ip-os {--limit=0}';
    protected $description = 'Refresca IP/SO desde QGA real y marca fuente qga solo si el agente responde.';

    public function handle(): int
    {
        $client = new Client(['verify' => false, 'timeout' => 4, 'connect_timeout' => 3]);
        $authService = app(AuthenticationService::class);
        $limit = (int)$this->option('limit');
        $query = qemu::where('qga_enabled_config', 1)->where('status', 'running')->orderBy('cluster_name')->orderBy('vmid');
        if ($limit > 0) $query->limit($limit);
        $vms = $query->get();
        $authByNode = [];
        $stats = ['checked'=>0,'qga_ok'=>0,'qga_failed'=>0,'updated'=>0,'no_ip_os'=>0];
        foreach ($vms as $vm) {
            $stats['checked']++;
            $nodeName = str_replace('node/', '', (string)$vm->node_id);
            $nodeIp = DB::table('nodes')->where('id_proxmox', $vm->node_id)->value('ip');
            if (!$nodeIp) { $stats['qga_failed']++; continue; }
            if (!array_key_exists($nodeIp, $authByNode)) {
                $cred = ClusterCredentials::where('ip', $nodeIp)->first();
                if (!$cred) { $authByNode[$nodeIp] = null; }
                elseif (strpos($cred->username, '!') !== false) {
                    $authByNode[$nodeIp] = ['type'=>'token','header'=>'PVEAPIToken='.$cred->username.'='.Crypt::decrypt($cred->password)];
                } else {
                    $authByNode[$nodeIp] = $authService->getAuthToken($nodeIp, $cred->username, $cred->password);
                }
            }
            $auth = $authByNode[$nodeIp];
            if (!$auth) { $stats['qga_failed']++; continue; }
            $headers = ['Content-Type'=>'application/json'];
            if (($auth['type'] ?? null) === 'token') $headers['Authorization'] = $auth['header'];
            else {
                $headers['Cookie'] = 'PVEAuthCookie=' . ($auth['ticket'] ?? '');
                if (!empty($auth['CSRFPreventionToken'])) $headers['CSRFPreventionToken'] = $auth['CSRFPreventionToken'];
            }
            $base = "https://{$nodeIp}:8006/api2/json/nodes/{$nodeName}/qemu/{$vm->vmid}/agent";
            $ip = null; $os = null;
            try {
                $res = $client->get($base.'/network-get-interfaces', ['headers'=>$headers]);
                $json = json_decode((string)$res->getBody(), true);
                $ip = $this->parseIp($json['data']['result'] ?? []);
            } catch (\Throwable $e) {}
            try {
                $res = $client->get($base.'/get-osinfo', ['headers'=>$headers]);
                $json = json_decode((string)$res->getBody(), true);
                $info = $json['data']['result'] ?? $json['data'] ?? [];
                $os = trim((string)($info['pretty-name'] ?? $info['name'] ?? '')) ?: null;
            } catch (\Throwable $e) {}
            if ($ip || $os) {
                if ($ip) $vm->ip_address = $ip;
                if ($os) $vm->os_name = $os;
                $vm->ip_os_source = 'qga';
                $vm->save();
                $stats['qga_ok']++; $stats['updated']++;
            } else {
                $stats['qga_failed']++;
                if (empty($vm->ip_address) && empty($vm->os_name)) $stats['no_ip_os']++;
            }
        }
        foreach ($stats as $k=>$v) $this->line("{$k}={$v}");
        $this->line('remaining_pdf_qga_running='.qemu::where('ip_os_source','pdf')->where('qga_enabled_config',1)->where('status','running')->count());
        $this->line('qga_source='.qemu::where('ip_os_source','qga')->count());
        return self::SUCCESS;
    }

    private function parseIp(array $interfaces): ?string
    {
        foreach ($interfaces as $iface) {
            $name = strtolower((string)($iface['name'] ?? ''));
            if (in_array($name, ['lo'], true)) continue;
            foreach (($iface['ip-addresses'] ?? []) as $addr) {
                $ip = $addr['ip-address'] ?? null;
                $type = $addr['ip-address-type'] ?? '';
                if ($ip && $type === 'ipv4' && !str_starts_with($ip, '127.') && !str_starts_with($ip, '169.254.')) return $ip;
            }
        }
        return null;
    }
}

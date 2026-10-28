<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use phpseclib3\Net\SSH2;

class ClusterAuditController extends Controller
{
    public function start($cluster)
    {
        $cluster = urldecode($cluster);
        if (DB::table('cluster_audit_runs')->where('cluster_name', $cluster)->where('status', 'running')->exists()) {
            return response()->json(['error' => 'Ya hay una auditoría en ejecución'], 409);
        }

        $eligible = DB::table('qemus')->where('cluster_name', $cluster)
            ->whereNotNull('ip_address')->where('ip_address', '!=', '')
            ->whereNotNull('os_name')->get()->filter(function ($q) {
                $os = strtolower((string) $q->os_name);
                return str_contains($os, 'debian') || str_contains($os, 'ubuntu') || str_contains($os, 'centos') || str_contains($os, 'rhel') || str_contains($os, 'rocky') || str_contains($os, 'alma') || str_contains($os, 'opensuse') || str_contains($os, 'suse') || str_contains($os, 'fedora') || str_contains($os, 'linux');
            })->values();

        $now = now();
        $run = DB::table('cluster_audit_runs')->insertGetId([
            'cluster_name' => $cluster, 'status' => 'running', 'total' => $eligible->count(),
            'started_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach ($eligible as $q) {
            DB::table('cluster_audit_results')->insert([
                'run_id' => $run, 'qemu_id' => $q->id, 'ssh_port' => (int) ($q->ssh_port ?? 22),
                'cluster_name' => $cluster, 'server_name' => $q->name, 'host' => $q->ip_address,
                'os' => $q->os_name, 'result' => 'pending', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        return response()->json(['run_id' => $run, 'total' => $eligible->count()]);
    }

    public function startVm($qemu)
    {
        $vm = DB::table('qemus')->where('id', $qemu)->first();
        if (!$vm) return response()->json(['error' => 'VM no encontrada'], 404);
        if (!$vm->ip_address || !$vm->os_name) return response()->json(['error' => 'La VM no tiene IP u OS válido para auditar'], 422);
        $os = strtolower((string) $vm->os_name);
        $isLinux = str_contains($os, 'debian') || str_contains($os, 'ubuntu') || str_contains($os, 'centos') || str_contains($os, 'rhel') || str_contains($os, 'rocky') || str_contains($os, 'alma') || str_contains($os, 'opensuse') || str_contains($os, 'suse') || str_contains($os, 'fedora') || str_contains($os, 'linux');
        if (!$isLinux) return response()->json(['error' => 'La VM no está clasificada como Linux'], 422);
        $cluster = (string) ($vm->cluster_name ?? '');
        if (DB::table('cluster_audit_runs')->where('cluster_name', $cluster)->where('status', 'running')->exists()) {
            return response()->json(['error' => 'Ya hay una auditoría en ejecución para este cluster'], 409);
        }
        $now = now();
        $run = DB::table('cluster_audit_runs')->insertGetId([
            'cluster_name' => $cluster, 'status' => 'running', 'total' => 1,
            'started_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('cluster_audit_results')->insert([
            'run_id' => $run, 'qemu_id' => $vm->id, 'ssh_port' => (int) ($vm->ssh_port ?? 22),
            'cluster_name' => $cluster, 'server_name' => $vm->name, 'host' => $vm->ip_address,
            'os' => $vm->os_name, 'result' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);
        return response()->json(['run_id' => $run, 'total' => 1]);
    }

    public function next($run)
    {
        $r = DB::table('cluster_audit_runs')->where('id', $run)->first();
        if (!$r) return response()->json(['error' => 'Auditoría no encontrada'], 404);
        if ($r->status !== 'running') return $this->summary($run);

        $row = DB::table('cluster_audit_results')->where('run_id', $run)
            ->where('result', 'pending')->orderBy('id')->first();
        if (!$row) {
            DB::table('cluster_audit_runs')->where('id', $run)->update(['status' => 'finished', 'finished_at' => now(), 'updated_at' => now()]);
            return $this->summary($run);
        }

        $out = [
            'result' => 'ssh_unavailable', 'infransible' => 'unknown', 'sudo_status' => 'unknown',
            'vip_status' => 'none', 'keepalived_status' => 'unknown', 'haproxy_status' => 'unknown',
            'zabbix_status_infransible' => null, 'zabbix_version_infransible' => null,
            'wazuh_status_infransible' => null, 'wazuh_version_infransible' => null,
            'services' => [], 'services_checked_at' => null,
            'agent_audited' => false, 'secondary_ips' => [], 'configured_vips' => [], 'detail' => '',
        ];

        try {
            $port = (int) ($row->ssh_port ?? 22);
            if ($port < 1 || $port > 65535) $port = 22;
            $ssh = new SSH2($row->host, $port);
            $ssh->setTimeout(8);
            $user = (string) env('INFRANSIBLE_USER', env('NODE_SSH_USER', 'infransible'));
            $password = (string) env('INFRANSIBLE_PASSWORD', '');
            if ($password === '') throw new \Exception('Credencial Infransible no configurada');
            if (!$ssh->login($user, $password)) throw new \Exception('Autenticación SSH rechazada');

            $out['infransible'] = 'ok';
            $out['agent_audited'] = true;
            $out = array_merge($out, $this->agentInfo($ssh));
            $out['services'] = $this->serviceInventory($ssh);
            if ($this->isViciDialCluster((string) ($r->cluster_name ?? ''))) {
                $out['services'] = array_merge($out['services'], $this->viciDialServiceInventory($ssh));
            }
            $out['services_checked_at'] = now();
            $ssh->setTimeout(5);
            $ip = $ssh->exec("timeout 5s ip -o -4 addr show 2>/dev/null");
            $ka = $ssh->exec("timeout 5s sh -c 'test -r /etc/keepalived/keepalived.conf && cat /etc/keepalived/keepalived.conf || true'");
            $out['configured_vips'] = $this->parseKeepalivedIps($ka);
            $out['result'] = 'ok';
            $out['secondary_ips'] = $this->parseIps($ip, $row->host, $out['configured_vips']);
            $sudo = trim($ssh->exec("timeout 5s sh -c 'sudo -n -v >/dev/null 2>&1; echo $?'"));
            $out['sudo_status'] = $sudo === '0' ? 'ok' : 'no';
            $out['keepalived_status'] = $this->service($ssh, 'keepalived')['status'];
            $out['haproxy_status'] = $this->service($ssh, 'haproxy')['status'];
            $out['vip_status'] = count(array_filter($out['secondary_ips'], fn ($x) => ($x['network_type'] ?? '') === 'vip')) ? 'active' : (count($out['configured_vips']) ? 'configured' : 'none');
        } catch (\Throwable $e) {
            $out['detail'] = substr($e->getMessage(), 0, 500);
            if (str_contains(strtolower($out['detail']), 'autenticación') || str_contains(strtolower($out['detail']), 'credencial')) $out['result'] = 'credential_error';
        }

        $this->persist($row, $out);
        DB::table('cluster_audit_results')->where('id', $row->id)->update([
            'result' => $out['result'], 'infransible' => $out['infransible'], 'sudo_status' => $out['sudo_status'],
            'vip_status' => $out['vip_status'], 'keepalived_status' => $out['keepalived_status'], 'haproxy_status' => $out['haproxy_status'],
            'services' => json_encode($out['services']), 'services_checked_at' => $out['services_checked_at'],
            'secondary_ips' => json_encode($out['secondary_ips']), 'detail' => $out['detail'], 'audited_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('cluster_audit_runs')->where('id', $run)->increment('processed');
        DB::table('cluster_audit_runs')->where('id', $run)->update(['updated_at' => now()]);
        $fresh = DB::table('cluster_audit_runs')->where('id', $run)->first();
        if ($fresh && $fresh->processed >= $fresh->total) DB::table('cluster_audit_runs')->where('id', $run)->update(['status' => 'finished', 'finished_at' => now(), 'updated_at' => now()]);
        return $this->summary($run);
    }

    private function persist($row, $out)
    {
        $note = $out['detail'];
        if ($out['sudo_status'] === 'no') $note = 'SSH OK / SUDO NO' . ($note ? '; ' . $note : '');
        $updates = [
            'infransible_status' => $out['infransible'] === 'ok' ? 'ok' : ($out['result'] === 'credential_error' ? 'error' : 'unknown'),
            'infransible_checked_at' => now(), 'infransible_note' => substr($note, 0, 255),
        ];
        if (($out['agent_audited'] ?? false) === true) {
            $updates += [
                'zabbix_status_infransible' => $out['zabbix_status_infransible'],
                'zabbix_version_infransible' => $out['zabbix_version_infransible'],
                'wazuh_status_infransible' => $out['wazuh_status_infransible'],
                'wazuh_version_infransible' => $out['wazuh_version_infransible'],
                'infransible_services' => json_encode($out['services']),
                'infransible_services_checked_at' => $out['services_checked_at'],
            ];
        }
        DB::table('qemus')->where('id', $row->qemu_id)->update($updates);
        if ($out['result'] !== 'ok') return;
        DB::table('qemu_network_addresses')->where('qemu_id', $row->qemu_id)->where('source', 'audit')->delete();
        foreach ($out['secondary_ips'] as $ip) {
            $addr = explode('/', $ip['cidr'])[0]; $type = $ip['network_type'] ?? 'secondary';
            DB::table('qemu_network_addresses')->updateOrInsert(
                ['qemu_id' => $row->qemu_id, 'interface' => $ip['interface'], 'ip_address' => $addr],
                ['cidr' => $ip['cidr'], 'network_type' => $type, 'description' => strtoupper($type), 'source' => 'audit', 'is_virtual' => $type === 'vip', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function agentInfo($ssh)
    {
        $z2 = $this->package($ssh, 'zabbix-agent2');
        $z1 = $this->package($ssh, 'zabbix-agent');
        $zPackage = $z2['installed'] ? 'zabbix-agent2' : ($z1['installed'] ? 'zabbix-agent' : null);
        $z = $zPackage ? $this->service($ssh, $zPackage) : ['status' => 'not_installed', 'detail' => 'paquete no instalado'];
        $wPackage = $this->package($ssh, 'wazuh-agent');
        $w = $wPackage['installed'] ? $this->service($ssh, 'wazuh-agent') : ['status' => 'not_installed', 'detail' => 'paquete no instalado'];
        return [
            'zabbix_status_infransible' => $z['status'],
            'zabbix_version_infransible' => $zPackage ? ($zPackage === 'zabbix-agent2' ? ($z2['version'] ?: $this->binaryVersion($ssh, 'zabbix_agent2')) : ($z1['version'] ?: $this->binaryVersion($ssh, 'zabbix_agentd'))) : null,
            'wazuh_status_infransible' => $w['status'],
            'wazuh_version_infransible' => $wPackage['installed'] ? ($wPackage['version'] ?: $this->binaryVersion($ssh, '/var/ossec/bin/wazuh-control')) : null,
        ];
    }

    private function serviceInventory($ssh)
    {
        $definitions = [
            'apache' => [['apache2', 'httpd'], ['apache2', 'httpd'], ['apache2', 'httpd']],
            'nginx' => [['nginx'], ['nginx'], ['nginx']],
            'php-fpm' => [
                ['php-fpm', 'php5.6-fpm', 'php7.0-fpm', 'php7.1-fpm', 'php7.2-fpm', 'php7.3-fpm', 'php7.4-fpm', 'php8.0-fpm', 'php8.1-fpm', 'php8.2-fpm', 'php8.3-fpm', 'php8.4-fpm'],
                ['php-fpm', 'php5.6-fpm', 'php7.0-fpm', 'php7.1-fpm', 'php7.2-fpm', 'php7.3-fpm', 'php7.4-fpm', 'php8.0-fpm', 'php8.1-fpm', 'php8.2-fpm', 'php8.3-fpm', 'php8.4-fpm'],
                ['php-fpm', 'php-fpm5.6', 'php-fpm7.0', 'php-fpm7.1', 'php-fpm7.2', 'php-fpm7.3', 'php-fpm7.4', 'php-fpm8.0', 'php-fpm8.1', 'php-fpm8.2', 'php-fpm8.3', 'php-fpm8.4'],
            ],
            'mysql' => [['mysql-server'], ['mysql'], ['mysqld']],
            'mariadb' => [['mariadb-server'], ['mariadb'], ['mariadbd']],
            'postgresql' => [['postgresql'], ['postgresql'], ['postgres', 'postgresql']],
            'redis' => [['redis-server'], ['redis-server', 'redis'], ['redis-server', 'redis-cli']],
            'haproxy' => [['haproxy'], ['haproxy'], ['haproxy']],
            'isc-dhcp-server' => [['isc-dhcp-server'], ['isc-dhcp-server'], ['dhcpd']],
            'kea-dhcp4-server' => [['kea-dhcp4-server'], ['kea-dhcp4-server'], ['kea-dhcp4']],
            'kea-dhcp6-server' => [['kea-dhcp6-server'], ['kea-dhcp6-server'], ['kea-dhcp6']],
        ];
        $result = [];

        // PHP CLI es independiente de PHP-FPM: informar el runtime instalado
        // aunque no exista un servicio FPM activo.
        $phpPackage = $this->package($ssh, 'php');
        $phpVersion = $phpPackage['version'] ?: $this->binaryVersion($ssh, 'php');
        $phpInstalled = $phpPackage['installed'] || $phpVersion !== null;
        $result['php'] = [
            'installed' => $phpInstalled,
            'status' => $phpInstalled ? 'installed_inactive' : 'not_installed',
            'version' => $phpVersion,
            'package' => $phpPackage['installed'] ? 'php' : null,
            'unit' => null,
            'detail' => $phpInstalled ? 'PHP runtime detectado' : 'PHP no instalado',
        ];

        foreach ($definitions as $name => [$packages, $units, $binaries]) {
            $foundPackage = null; $packageInfo = ['installed' => false, 'version' => null];
            foreach ($packages as $candidate) { $info = $this->package($ssh, $candidate); if ($info['installed']) { $foundPackage = $candidate; $packageInfo = $info; break; } }
            $foundUnit = null; $svc = ['status' => 'not_installed', 'detail' => 'no detectado'];
            foreach ($units as $candidate) { $check = $this->service($ssh, $candidate); if (($check['exists'] ?? false) || $check['status'] !== 'not_installed' || $foundUnit === null) { $foundUnit = $candidate; $svc = $check; } if ($check['status'] === 'active') break; }
            $installed = $packageInfo['installed'] || ($svc['exists'] ?? false);
            $version = $packageInfo['version'];
            if (!$version && $installed) foreach ($binaries as $binary) { $v = $this->binaryVersion($ssh, $binary); if ($v) { $version = $v; break; } }
            $result[$name] = [
                'installed' => $installed,
                'status' => $installed ? $svc['status'] : 'not_installed',
                'version' => $version,
                'package' => $foundPackage,
                'unit' => $installed ? $foundUnit : null,
                'detail' => $svc['detail'],
                'active_since' => $installed ? ($svc['active_since'] ?? null) : null,
                'uptime_seconds' => $installed ? ($svc['uptime_seconds'] ?? null) : null,
                'unit_state' => $installed ? ($svc['unit_state'] ?? null) : null,
            ];
        }
        return $result;
    }

    private function isViciDialCluster($cluster)
    {
        return (bool) preg_match('/vici(?:dial|box)?/i', $cluster);
    }

    private function viciDialServiceInventory($ssh)
    {
        $services = [];
        $asterisk = $this->service($ssh, 'asterisk');
        $asteriskProcess = trim($this->exec($ssh, ['sh', '-c', "pgrep -x asterisk >/dev/null 2>&1 && echo active || true"]));
        $asteriskInstalled = ($asterisk['exists'] ?? false) || $asterisk['status'] !== 'not_installed' || $asteriskProcess === 'active';
        if ($asteriskInstalled) {
            $services['asterisk'] = [
                'installed' => true,
                'status' => $asteriskProcess === 'active' || $asterisk['status'] === 'active' ? 'active' : 'inactive',
                'version' => null,
                'package' => 'asterisk',
                'unit' => ($asterisk['exists'] ?? false) ? 'asterisk' : null,
                'detail' => $asteriskProcess === 'active' ? 'process active' : ($asterisk['detail'] ?? 'inactive'),
            ];
        }

        $processDefinitions = [
            'vicidial-manager' => ['/usr/share/astguiclient/AST_manager_listen.pl', 'AST_manager_listen.pl'],
            'vicidial-autodial' => ['/usr/share/astguiclient/AST_VDauto_dial.pl', 'AST_VDauto_dial.pl'],
            'vicidial-remote' => ['/usr/share/astguiclient/AST_VDremote.pl', 'AST_VDremote.pl'],
            'vicidial-adapt' => ['/usr/share/astguiclient/AST_VDadapt.pl', 'AST_VDadapt.pl'],
            'vicidial-hopper' => ['/usr/share/astguiclient/AST_VDhopper.pl', 'AST_VDhopper.pl'],
            'vicidial-cron' => ['/usr/share/astguiclient/AST_CRON.php', 'AST_CRON.php'],
        ];
        foreach ($processDefinitions as $key => [$path, $pattern]) {
            $exists = trim($this->exec($ssh, ['sh', '-c', "test -e " . escapeshellarg($path) . " && echo installed || true"])) === 'installed';
            $active = trim($this->exec($ssh, ['sh', '-c', "pgrep -af " . escapeshellarg($pattern) . " >/dev/null 2>&1 && echo active || true"])) === 'active';
            if ($exists || $active) {
                $services[$key] = [
                    'installed' => true,
                    'status' => $active ? 'active' : 'inactive',
                    'version' => null,
                    'package' => null,
                    'unit' => null,
                    'detail' => $active ? 'process active' : 'script installed, process inactive',
                ];
            }
        }
        return $services;
    }

    private function package($ssh, $name)
    {
        $package = escapeshellarg($name);
        $dpkgRaw = trim($this->exec($ssh, ['sh', '-c', "dpkg-query -W -f='\${Status}\t\${Version}' {$package} 2>/dev/null || true"]));
        if (preg_match('/^install ok installed\t([^\s]+)/m', $dpkgRaw, $m)) {
            preg_match('/(\d+\.\d+(?:\.\d+)*)/', $m[1], $version);
            return ['installed' => true, 'version' => $version[1] ?? $m[1]];
        }

        $rpmRaw = trim($this->exec($ssh, ['sh', '-c', "rpm -q --qf='%{VERSION}' {$package} 2>/dev/null || true"]));
        if ($rpmRaw !== '' && !str_contains(strtolower($rpmRaw), 'not installed')) {
            preg_match('/(\d+\.\d+(?:\.\d+)*)/', $rpmRaw, $m);
            return ['installed' => true, 'version' => $m[1] ?? $rpmRaw];
        }

        return ['installed' => false, 'version' => null];
    }

    private function binaryVersion($ssh, $binary)
    {
        $versionArg = $binary === '/var/ossec/bin/wazuh-control' ? 'info' : ($binary === 'php' ? '-v' : '-V');
        $raw = $this->exec($ssh, [$binary, $versionArg]);
        return preg_match('/\b(\d+\.\d+(?:\.\d+)*)\b/', $raw, $m) ? $m[1] : null;
    }

    private function exec($ssh, $parts)
    {
        $ssh->setTimeout(5); return (string) $ssh->exec(implode(' ', array_map(fn ($p) => escapeshellarg($p), $parts)) . ' 2>&1');
    }

    private function service($ssh, $name)
    {
        $safe = escapeshellarg($name);
        $existsRaw = trim($ssh->exec("timeout 5s systemctl list-unit-files --type=service --no-legend $safe 2>/dev/null"));
        $raw = trim($ssh->exec("timeout 5s systemctl show -p ActiveState -p SubState -p ActiveEnterTimestamp -p UnitFileState --value $safe 2>/dev/null"));
        $values = preg_split('/\r?\n/', $raw);
        $activeState = strtolower(trim($values[0] ?? ''));
        $subState = strtolower(trim($values[1] ?? ''));
        $activeSince = trim($values[2] ?? '');
        $unitState = trim($values[3] ?? '');
        $status = match ($activeState) {
            'active' => 'active',
            'failed' => 'failed',
            'activating', 'deactivating' => 'restarting',
            'inactive' => ($existsRaw === '' ? 'not_installed' : 'inactive'),
            default => (($existsRaw === '' || $activeState === '' || $activeState === 'unknown') ? 'not_installed' : 'unknown'),
        };
        if ($status === 'active' && in_array($subState, ['start', 'stop', 'auto-restart', 'failed'], true)) $status = 'restarting';
        $uptimeSeconds = null;
        if ($status === 'active' && $activeSince !== '') {
            $started = strtotime($activeSince);
            if ($started !== false) $uptimeSeconds = max(0, time() - $started);
        }
        return [
            'status' => $status,
            'detail' => substr(trim($activeState . ($subState !== '' ? '/' . $subState : '')), 0, 120),
            'exists' => $existsRaw !== '',
            'active_since' => $activeSince !== '' ? $activeSince : null,
            'uptime_seconds' => $uptimeSeconds,
            'unit_state' => $unitState !== '' ? $unitState : null,
        ];
    }

    private function parseKeepalivedIps($raw) { $ips = []; if (preg_match_all('/virtual_ipaddress(?:_excluded)?\s*\{(.*?)\}/is', $raw, $blocks)) foreach ($blocks[1] as $block) foreach (preg_split('/\r?\n/', $block) as $line) if (preg_match('/\b(\d{1,3}(?:\.\d{1,3}){3})(?:\/\d{1,2})?\b/', $line, $m)) $ips[] = $m[1]; return array_values(array_unique($ips)); }
    private function parseIps($raw, $host, $vips = []) { $ret = []; $found = []; foreach (preg_split('/\r?\n/', $raw) as $line) { if (!preg_match('/^\d+:\s+([^ ]+).*? inet (\d+\.\d+\.\d+\.\d+\/\d+)/', $line, $m)) continue; $if = $m[1]; $ip = explode('/', $m[2])[0]; if ($if === 'lo' || preg_match('/^(docker|br-|veth|virbr|cni|flannel|tun|tap)/i', $if) || $ip === $host || str_starts_with($ip, '127.') || preg_match('/^172\.(1[7-9]|2[0-8])\./', $ip)) continue; $found[$ip] = true; $ret[] = ['interface' => $if, 'cidr' => $m[2], 'network_type' => in_array($ip, $vips, true) ? 'vip' : (str_starts_with($ip, '10.0.202.') ? 'veeam' : 'secondary')]; } foreach (array_unique($vips) as $vip) if (!isset($found[$vip])) $ret[] = ['interface' => 'keepalived', 'cidr' => $vip . '/32', 'network_type' => 'vip']; return array_values(array_reduce($ret, function ($carry, $item) { $carry[$item['interface'] . '|' . explode('/', $item['cidr'])[0]] = $item; return $carry; }, [])); }
    public function stop($run)
    {
        $audit = DB::table('cluster_audit_runs')->where('id', $run)->first();
        if (!$audit) return response()->json(['error' => 'Auditoría no encontrada'], 404);
        if ($audit->status !== 'running') return $this->summary($run);
        DB::table('cluster_audit_runs')->where('id', $run)->update([
            'status' => 'cancelled', 'cancel_requested' => true, 'finished_at' => now(), 'updated_at' => now(),
        ]);
        return $this->summary($run);
    }

    public function latest($cluster) { $run = DB::table('cluster_audit_runs')->where('cluster_name', urldecode($cluster))->latest()->first(); return $run ? $this->summary($run->id) : response()->json(['run' => null]); }
    private function summary($run) { $r = DB::table('cluster_audit_runs')->where('id', $run)->first(); $rows = DB::table('cluster_audit_results')->where('run_id', $run)->orderBy('id')->get(); return response()->json(['run' => $r, 'counts' => $rows->groupBy('result')->map->count(), 'rows' => $rows]); }
}

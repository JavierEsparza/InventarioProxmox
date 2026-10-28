<?php

namespace App\Services\Proxmox;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    protected $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function executeQgaCommand($authData, $node, $vmid, $commandArray, $baseUrl)
    {
        try {
            $execUrl = $baseUrl . "/nodes/{$node}/qemu/{$vmid}/agent/exec";
            $payload = ['command' => $commandArray];

            $client = $this->authService->getClient();

            $response = $client->request('POST', $execUrl, [
                'headers' => isset($authData['header']) ? ['Authorization' => $authData['header']] : ['Cookie' => "PVEAuthCookie={$authData['ticket']}", 'CSRFPreventionToken' => $authData['CSRFPreventionToken']],
                'json' => $payload,
                'verify' => false,
                'timeout' => 3 // Bajamos timeout a 3s
            ]);

            $json = json_decode($response->getBody(), true);
            $pid = $json['data']['pid'] ?? null;

            if (!$pid) return null;

            $statusUrl = $baseUrl . "/nodes/{$node}/qemu/{$vmid}/agent/exec-status?pid={$pid}";

            // Polling muy rápido (Max 2 segundos)
            for ($i = 0; $i < 10; $i++) {
                usleep(200000);
                try {
                    $statusResp = $this->authService->makeRequest('GET', $statusUrl, $authData);
                } catch (\Exception $e) {
                    return null;
                }

                if (isset($statusResp['exited']) && $statusResp['exited'] == 1) {
                    if (isset($statusResp['out-data'])) {
                        return trim($statusResp['out-data']);
                    }
                    return null;
                }
            }
            return null;

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            // --- DETECCIÓN DOBLE DE ERRORES ---
            if (strpos($msg, 'guest-exec has been disabled') !== false) {
                 throw new \Exception("QGA_DISABLED");
            }
            if (strpos($msg, 'QEMU guest agent is not running') !== false) {
                 throw new \Exception("QGA_NOT_RUNNING");
            }

            // Loguear solo si es un error inesperado real
            // Log::error("QGA EXCEPTION [VM {$vmid}]: " . $e->getMessage());
            return null;
        }
    }

    private function checkLinuxServiceRunning($authData, $node, $vmid, $serviceName, $baseUrl)
    {
        $commands = [
            ['systemctl', 'is-active', $serviceName],
            ['/usr/bin/systemctl', 'is-active', $serviceName],
            ['/bin/systemctl', 'is-active', $serviceName],
        ];

        foreach ($commands as $cmd) {
            // Al llamar a executeQgaCommand, si lanza "QGA_DISABLED",
            // NO lo atrapamos aquí, dejamos que suba a getMonitoringInfo para romper el ciclo.
            $status = $this->executeQgaCommand($authData, $node, $vmid, $cmd, $baseUrl);

            if ($status === 'active') {
                return true;
            }
        }

        return false;
    }

    public function getMonitoringInfo($authData, $node, $vmid, $baseUrl)
    {
        $info = [
            'zabbix_status' => 'stopped',
            'zabbix_version' => null,
            'wazuh_status' => 'stopped',
            'wazuh_version' => null,
        ];

        try {
            // --- ZABBIX STATUS ---
            // 1. Revisamos si corre el Agente 1
            $zabbixRunning = $this->checkLinuxServiceRunning($authData, $node, $vmid, 'zabbix-agent', $baseUrl);

            // 2. Si no, revisamos si corre el Agente 2
            if (!$zabbixRunning) {
                $zabbixRunning = $this->checkLinuxServiceRunning($authData, $node, $vmid, 'zabbix-agent2', $baseUrl);
            }

            // --- ZABBIX VERSION (AQUÍ ESTABA EL ERROR) ---
            if ($zabbixRunning) {
                $info['zabbix_status'] = 'running';

                // Probamos ambos comandos para sacar la versión
                $zbxCommands = [
                    ['zabbix_agent2', '-V'],          // 1. Probar Agente 2
                    ['/usr/sbin/zabbix_agent2', '-V'], // 2. Ruta absoluta Agente 2
                    ['zabbix_agentd', '-V'],          // 3. Probar Agente 1
                    ['/usr/sbin/zabbix_agentd', '-V']  // 4. Ruta absoluta Agente 1
                ];

                foreach ($zbxCommands as $cmd) {
                    $resZVer = $this->executeQgaCommand($authData, $node, $vmid, $cmd, $baseUrl);
                    // Buscamos patrón numérico ej: 7.0.21
                    if ($resZVer && preg_match('/(\d+\.\d+\.\d+)/', $resZVer, $matches)) {
                        $info['zabbix_version'] = $matches[1];
                        break; // Ya la encontramos, dejamos de buscar
                    }
                }
            }

            // --- WAZUH STATUS & VERSION ---
            if ($this->checkLinuxServiceRunning($authData, $node, $vmid, 'wazuh-agent', $baseUrl)) {
                $info['wazuh_status'] = 'running';

                $wazuhCommands = [
                    ['/var/ossec/bin/wazuh-control', 'info'],
                    ['wazuh-control', 'info']
                ];

                foreach ($wazuhCommands as $cmd) {
                    $resWVer = $this->executeQgaCommand($authData, $node, $vmid, $cmd, $baseUrl);
                    if ($resWVer && preg_match('/v(\d+\.\d+\.\d+)/', $resWVer, $matches)) {
                        $info['wazuh_version'] = $matches[1];
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            // Si el agente está deshabilitado o no corre, retornamos lo que tengamos
            return $info;
        }

        return $info;
    }

    public function parseIpFromInterfaces($interfaces)
    {
        if (empty($interfaces) || !is_array($interfaces)) {
            return null;
        }
        foreach ($interfaces as $interface) {
            // Saltamos interfaces sin nombre o loopback
            if (empty($interface['name']) || $interface['name'] === 'lo') {
                continue;
            }
            if (!empty($interface['ip-addresses'])) {
                foreach ($interface['ip-addresses'] as $ip) {
                    // Buscamos la primera IP v4 que no sea 127.0.0.1
                    if (isset($ip['ip-address-type']) && $ip['ip-address-type'] == 'ipv4' && $ip['ip-address'] != '127.0.0.1') {
                        return $ip['ip-address'];
                    }
                }
            }
        }
        return null;
    }
}

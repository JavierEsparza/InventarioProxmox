<?php

namespace App\Services\Proxmox;

use App\Models\qemu;
use App\Models\node;
use Illuminate\Support\Facades\Log;

class QemuService
{
    protected $authService;
    protected $monitoringService;

    public function __construct(AuthenticationService $authService, MonitoringService $monitoringService)
    {
        $this->authService = $authService;
        $this->monitoringService = $monitoringService;
    }

    public function fetchAndSaveQemuData($authData, $qemuItem, $runQga, $baseUrl, $clusterName, &$updatedQemuIds, &$updatedQemuKeys, $nodeCache)
    {
        if (empty($qemuItem['vmid'])) return;

        $vmid = $qemuItem['vmid'];
        $nodeId = $qemuItem['node'];
        $proxmoxId = 'node/' . $nodeId . '/qemu/' . $vmid;
        $nodeIdProxmox = 'node/' . $nodeId;

        $updatedQemuIds[] = $proxmoxId;
        $logicalCluster = $clusterName ?: (string) ($nodeCache->get($nodeIdProxmox)->cluster_name ?? '');
        $logicalKey = $logicalCluster . '|' . (string) $vmid;
        $updatedQemuKeys[] = $logicalKey;

        // Obtener Configuración
        $configUrl = $baseUrl . "/nodes/{$nodeId}/qemu/{$vmid}/config";
        try {
            $configData = $this->authService->makeRequest('GET', $configUrl, $authData);
        } catch (\Exception $e) {
            return;
        }

        if (!$configData) return;

        // Recuperar datos previos
        // VMID es único dentro del cluster aunque cambie de nodo.
        $existingVm = qemu::where('cluster_name', $logicalCluster)
            ->where('vmid', $vmid)->first();
        if (!$existingVm) {
            // Compatibilidad con inventario antiguo sin cluster_name.
            $existingVm = qemu::where('id_proxmox', $proxmoxId)->first();
        }
        $ip_address = $existingVm ? $existingVm->ip_address : null;
        $os_name = $existingVm ? $existingVm->os_name : null;
        $ip_os_source = $existingVm ? ($existingVm->ip_os_source ?: 'unknown') : 'unknown';
        $manualIp = $existingVm ? $existingVm->ip_address_manual : null;
        $manualOs = $existingVm ? $existingVm->os_name_manual : null;
        $qgaProvidedIp = false;
        $qgaProvidedOs = false;
        if ($existingVm && $ip_os_source !== 'qga') {
            if ($manualIp && empty($ip_address)) $ip_address = $manualIp;
            if ($manualOs && empty($os_name)) $os_name = $manualOs;
        }
        $zabbix_status = $existingVm ? $existingVm->zabbix_status : null;
        $zabbix_version = $existingVm ? $existingVm->zabbix_version : null;
        $wazuh_status = $existingVm ? $existingVm->wazuh_status : null;
        $wazuh_version = $existingVm ? $existingVm->wazuh_version : null;

        $is_running = isset($qemuItem['status']) && $qemuItem['status'] == 'running';
        // Proxmox puede devolver "1" o "1,opcion=valor" (por ejemplo fstrim_cloned_disks).
        // Ambos significan que QEMU Guest Agent está habilitado.
        $agentConfig = trim((string)($configData['agent'] ?? ''));
        $has_agent_conf = preg_match('/^(1|true|yes|on)(,|$)/i', $agentConfig) === 1;

        // --- LÓGICA OPTIMIZADA QGA ---
        if ($runQga && $is_running && $has_agent_conf) {

            $agentIsHealthy = true; // Asumimos que está bien hasta que falle

            // A. IP (Primer test del agente)
            try {
                $interfacesUrl = $baseUrl . "/nodes/{$nodeId}/qemu/{$vmid}/agent/network-get-interfaces";
                // Usamos un try-catch específico para Guzzle aquí
                $interfacesResult = $this->authService->makeRequest('GET', $interfacesUrl, $authData);
                $interfacesData = $interfacesResult['result'] ?? [];
                $newIp = $this->monitoringService->parseIpFromInterfaces($interfacesData);
                if ($newIp) { $ip_address = $newIp; $qgaProvidedIp = true; }

            } catch (\Exception $e) {
                // Si falla obteniendo interfaces, verificamos si es porque el agente está muerto
                if (strpos($e->getMessage(), 'not running') !== false) {
                    $agentIsHealthy = false;
                    Log::warning("SYNC SKIP [VM {$vmid}]: Agente no corre. Saltando OS y Monitor.");
                }
            }

            // SOLO si el agente pasó la prueba de la IP, intentamos lo demás
            if ($agentIsHealthy) {

                // B. OS
                $osUrl = $baseUrl . "/nodes/{$nodeId}/qemu/{$vmid}/agent/get-osinfo";
                try {
                    $osResult = $this->authService->makeRequest('GET', $osUrl, $authData);
                    $osInfo = $osResult['result'] ?? $osResult;
                    if (isset($osInfo['pretty-name'])) { $os_name = $osInfo['pretty-name']; $qgaProvidedOs = true; }
                } catch (\Exception $e) {
                     // Si falla aquí, no es crítico, pero seguimos
                }

                // C. Monitorización (Zabbix/Wazuh)
                // Solo si no es Windows o si queremos forzar chequeo
                if (empty($os_name) || stripos($os_name, 'Windows') === false) {
                     try {
                        $monitoringInfo = $this->monitoringService->getMonitoringInfo($authData, $nodeId, $vmid, $baseUrl);
                        $zabbix_status = $monitoringInfo['zabbix_status'];
                        $zabbix_version = $monitoringInfo['zabbix_version'];
                        $wazuh_status = $monitoringInfo['wazuh_status'];
                        $wazuh_version = $monitoringInfo['wazuh_version'];
                    } catch (\Exception $e) {
                        // El manejo de QGA_DISABLED/NOT_RUNNING ya está en getMonitoringInfo
                    }
                }
            }
        }

        // Validar Nodo Padre y Guardar (Igual que antes)
        $node = $nodeCache->get($nodeIdProxmox);
        if (!$node) {
            $node = node::where('id_proxmox', $nodeIdProxmox)->first();
            if (!$node) return;
        }

        $disk = $this->calculateTotalDiskSize($configData);
        $cores = $configData['cores'] ?? 1;
        $sockets = $configData['sockets'] ?? 1;
        $maxCpu = $cores * $sockets;

        if (empty($clusterName)) {
             $clusterName = $node->cluster_name;
        }
        // Mantener la clave física actual después de una migración de nodo.
        $logicalCluster = $clusterName;

        if ($qgaProvidedIp || $qgaProvidedOs) {
            $ip_os_source = 'qga';
        } elseif (in_array($ip_os_source, ['manual','pdf'], true)) {
            if (!$ip_address && $manualIp) $ip_address = $manualIp;
            if (!$os_name && $manualOs) $os_name = $manualOs;
        }

        if ($existingVm && $existingVm->id_proxmox !== $proxmoxId) {
            $existingVm->id_proxmox = $proxmoxId;
            $existingVm->save();
        }

        qemu::updateOrCreate(['id_proxmox' => $proxmoxId], [
            'node_id' => $nodeIdProxmox,
            'id_node' => $node->id,
            'vmid' => $vmid,
            'name' => $qemuItem['name'] ?? ('VM ' . $vmid),
            'type' => $qemuItem['type'] ?? 'qemu',
            'status' => $qemuItem['status'] ?? 'stopped',
            'ip_address' => $ip_address,
            'os_name' => $os_name,
            'ip_os_source' => $ip_os_source,
            'qga_enabled_config' => $has_agent_conf,
            'zabbix_status' => $zabbix_status,
            'zabbix_version' => $zabbix_version,
            'wazuh_status' => $wazuh_status,
            'wazuh_version' => $wazuh_version,
            'cpu' => $qemuItem['cpu'] ?? 0,
            'maxcpu' => $maxCpu,
            'maxmem' => $qemuItem['maxmem'] ?? 0,
            'mem' => $qemuItem['mem'] ?? 0,
            'uptime' => $qemuItem['uptime'] ?? 0,
            'disk' => $qemuItem['disk'] ?? 0,
            'maxdisk' => $qemuItem['maxdisk'] ?? 0,
            'storageName' => $disk['storageNames'],
            'size' => $disk['totalSize'],
            'cluster_name' => $clusterName,
        ]);
    }

    public function calculateTotalDiskSize($configData)
    {
        $totalSize = 0;
        $storageNames = [];

        foreach ($configData as $key => $value) {
            if (preg_match('/^(scsi|ide|sata|virtio)\d+$/', $key) && !empty($value)) {
                if (strpos($value, 'size=') === false) {
                    continue;
                }
                $parts = explode(':', $value, 2);
                $storageName = $parts[0];
                if (!in_array($storageName, $storageNames)) {
                    $storageNames[] = $storageName;
                }
                $diskInfo = $this->extractDiskInfo($value);
                if ($diskInfo && isset($diskInfo['size'])) {
                    $totalSize += $this->convertToBytes($diskInfo['size']);
                }
            }
        }
        return ['totalSize' => $totalSize, 'storageNames' => implode(', ', $storageNames)];
    }

    protected function extractDiskInfo($diskString)
    {
        if (preg_match('/size=([^,]+)/', $diskString, $matches)) return ['size' => $matches[1]];
        return null;
    }

    protected function convertToBytes($size)
    {
        $units = ['T' => pow(1024, 4), 'G' => pow(1024, 3), 'M' => pow(1024, 2)];
        foreach ($units as $unit => $factor) {
            if (strpos($size, $unit) !== false) {
                return (int)((float)str_replace($unit, '', $size) * $factor);
            }
        }
        return (int)$size;
    }

    public function extractStorageInfo($scsiString)
    {
        $storageName = explode(':', $scsiString)[0];
        preg_match('/size=(\d+(?:G|T))/', $scsiString, $matches);
        return ['storageName' => $storageName, 'size' => isset($matches[1]) ? $matches[1] : null];
    }
}

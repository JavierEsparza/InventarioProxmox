<?php

namespace App\Services\Proxmox;

use App\Models\cluster;
use App\Models\node;
use Illuminate\Support\Facades\Log;

class ClusterService
{
    protected $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function fetchAndSaveClusterStatus($authData, $baseUrl, &$nodeInfo, &$clusterName)
    {
        $clusterName = null;
        $nodeInfo = [];
        $url = $baseUrl . '/cluster/status';
        $data = $this->authService->makeRequest('GET', $url, $authData);
        if (!$data) return;

        usort($data, function($a, $b) {
            return (isset($a['type'], $b['type'])) ? strcmp($a['type'], $b['type']) : 0;
        });

        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] == 'node' && isset($item['id'])) {
                $nodeInfo[$item['id']] = $item;
            }
        }

        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] == 'cluster' && isset($item['name'])) {
                $nameNode = implode(',', array_column($nodeInfo, 'name'));
                $this->saveDataToCluster($item, $nameNode);
                $clusterName = $item['name'];
            }
        }

        // Proxmox standalone / versiones antiguas pueden no devolver item type=cluster.
        // Usamos el primer nodo como nombre lógico para que la sync exclusiva no deje cluster_name NULL.
        if (!$clusterName && !empty($nodeInfo)) {
            $firstNode = reset($nodeInfo);
            $clusterName = $firstNode['name'];
            $nameNode = implode(',', array_column($nodeInfo, 'name'));
            $this->saveDataToCluster([
                'type' => 'cluster',
                'name' => $clusterName,
                'nodes' => count($nodeInfo),
            ], $nameNode);
            Log::channel('proxmox_sync')->info("SYNC STATUS: Proxmox sin objeto cluster; se usará '{$clusterName}' como nombre lógico.");
        }

        // =======================================================================
        // === NUEVO: AUTO-DESCUBRIMIENTO DE NODOS ===
        // =======================================================================
        if ($clusterName) {
            foreach ($data as $item) {
                // Si encontramos un item tipo nodo con IP
                if (isset($item['type']) && $item['type'] == 'node' && isset($item['ip'])) {

                    // 1. Verificar si el nodo ya existe en nuestra BD
                    $nodeExists = node::where('id_proxmox', $item['id'])->exists();

                    if (!$nodeExists) {
                        Log::channel('proxmox_sync')->info("SYNC DISCOVERY: Nuevo nodo detectado '{$item['name']}' ({$item['ip']}). Creando...");

                        // 2. Crear el nodo en la tabla 'nodes'
                        node::create([
                            'id_proxmox' => $item['id'], // ej: node/pve-desarrollo06
                            'node' => $item['name'],
                            'ip' => $item['ip'],
                            'cluster_name' => $clusterName,
                            'type' => 'node',
                            'status' => isset($item['online']) && $item['online'] ? 'online' : 'offline',
                            // Valores por defecto
                            'disk' => 0, 'maxdisk' => 0, 'mem' => 0, 'maxmem' => 0, 'cpu' => 0, 'maxcpu' => 0, 'uptime' => 0
                        ]);

                        // 3. Replicar Credenciales (CRÍTICO)
                        // Si el nodo es nuevo, no tiene credenciales en la DB y el próximo sync fallará.
                        // Copiamos las credenciales de algún "hermano" de su mismo cluster.
                        $siblingNode = node::where('cluster_name', $clusterName)
                                           ->where('ip', '!=', $item['ip'])
                                           ->whereNotNull('ip')
                                           ->first();

                        if ($siblingNode) {
                            $siblingCred = \App\Models\ClusterCredentials::where('ip', $siblingNode->ip)->first();
                            if ($siblingCred) {
                                \App\Models\ClusterCredentials::updateOrCreate(['ip' => $item['ip']], [
                                    'username' => $siblingCred->username,
                                    'password' => $siblingCred->password
                                ]);
                                Log::channel('proxmox_sync')->info("SYNC DISCOVERY: Credenciales replicadas para {$item['ip']} desde {$siblingNode->ip}");
                            }
                        }
                    }
                }
            }
        }
    }

    protected function saveDataToCluster($item, $nodeInfo)
    {
        cluster::updateOrCreate(['id_proxmox' => $item['name']], [
            'type' => $item['type'],
            'name' => $item['name'],
            'node_count' => $item['nodes'],
            'nodes' => $nodeInfo
        ]);
    }
}

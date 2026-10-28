<?php

namespace App\Services\Proxmox;

use App\Models\node;
use App\Models\NodeInterface;
use Illuminate\Support\Facades\Log;

class NodeService
{
    protected $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function fetchAndSaveNodeData($nodeItem, $IP, $clusterName, &$nodeCache)
    {
        if ($nodeItem) {
            $nodeIdProxmox = $nodeItem['id'];
            $updatedNode = Node::updateOrCreate(['id_proxmox' => $nodeIdProxmox], [
                'cluster_name' => $clusterName,
                'type' => $nodeItem['type'],
                'ip' => $nodeItem['ip'] ?? (isset($IP['ip']) ? $IP['ip'] : null),
                'online' => isset($IP['online']) ? $IP['online'] : 0,
                'status' => $nodeItem['status'],
                'disk' => $nodeItem['disk'],
                'maxdisk' => $nodeItem['maxdisk'],
                'node' => $nodeItem['node'],
                'mem' => $nodeItem['mem'],
                'maxmem' => $nodeItem['maxmem'],
                'cpu' => $nodeItem['cpu'],
                'maxcpu' => $nodeItem['maxcpu'],
                'uptime' => $nodeItem['uptime'],
            ]);
            $nodeCache[$nodeIdProxmox] = $updatedNode;
        }
    }

    public function fetchAndSaveNodeNetwork($authData, $dbNode, $baseUrl)
    {
        $nodeName = $dbNode->node;
        $url = $baseUrl . '/nodes/' . $nodeName . '/network';

        try {
            $networks = $this->authService->makeRequest('GET', $url, $authData);

            if ($networks) {
                // ===================================================================
                // ESTRATEGIA: updateOrCreate en lugar de delete() + create()
                // Esto permite que SSH y API convivan sin destruir datos mutuamente.
                //
                // - API actualiza: type, gateway, active (info básica de config)
                // - SSH actualiza: mac (permaddr real), cidr, comments, vinculaciones
                // - Ambos actualizan: address, ports (el último que corra gana)
                // ===================================================================

                foreach ($networks as $net) {
                    $mac = null;
                    $ip = $net['cidr'] ?? null;
                    $ports = null;

                    // 1. DETECCIÓN DE MAC (Intento extendido)
                    if (isset($net['address']) && preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $net['address'])) {
                        $mac = $net['address'];
                    }
                    elseif (isset($net['mac'])) {
                        $mac = $net['mac'];
                    }

                    // 2. CAPTURA DE IP
                    if (isset($net['address']) && !$mac) {
                        $ip = $net['address'];
                    }

                    // 3. CAPTURA DE PUERTOS / MIEMBROS
                    if (isset($net['bridge_ports'])) {
                        $ports = $net['bridge_ports'];
                    } elseif (isset($net['slaves'])) {
                        $ports = $net['slaves'];
                    }

                    // 4. USAR updateOrCreate INTELIGENTE PARA PRESERVAR DATOS DE SSH
                    // Primero buscamos si ya existe la interfaz
                    $existing = NodeInterface::where('node_id', $dbNode->id)
                                                          ->where('iface', $net['iface'])
                                                          ->first();

                    // Preparar datos base que siempre actualiza la API
                    $dataToUpdate = [
                        'type'     => $net['type'],
                        'address'  => $ip,
                        'gateway'  => $net['gateway'] ?? null,
                        'active'   => $net['active'] ?? 0,
                    ];

                    // Solo actualizar MAC y ports si NO hay datos previos (SSH los tiene)
                    if (!$existing || empty($existing->mac)) {
                        $dataToUpdate['mac'] = $mac;
                    }
                    if (!$existing || empty($existing->ports)) {
                        $dataToUpdate['ports'] = $ports;
                    }

                    // Preservar campos exclusivos de SSH (nunca los toca la API)
                    if ($existing) {
                        if ($existing->comments) $dataToUpdate['comments'] = $existing->comments;
                        if ($existing->cidr) $dataToUpdate['cidr'] = $existing->cidr;
                        if ($existing->bridge_parent) $dataToUpdate['bridge_parent'] = $existing->bridge_parent;
                        if ($existing->bond_parent) $dataToUpdate['bond_parent'] = $existing->bond_parent;
                    }

                    // Crear o actualizar
                    NodeInterface::updateOrCreate(
                        [
                            'node_id' => $dbNode->id,
                            'iface'   => $net['iface']
                        ],
                        $dataToUpdate
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error("Error obteniendo red para nodo {$nodeName}: " . $e->getMessage());
        }
    }
}

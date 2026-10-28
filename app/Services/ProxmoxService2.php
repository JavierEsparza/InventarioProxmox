<?php

namespace App\Services;

use App\Services\Proxmox\AuthenticationService;
use App\Services\Proxmox\ClusterService;
use App\Services\Proxmox\NodeService;
use App\Services\Proxmox\QemuService;
use App\Services\Proxmox\StorageService;
use App\Services\Proxmox\InventoryService;
use App\Services\Proxmox\MonitoringService;
use GuzzleHttp\Client;
use App\Models\node;
use App\Models\cluster;
use App\Models\ClusterCredentials;
use App\Models\VirtualMachineHistory;
use App\Models\MonthlyTotal;
use App\Models\QemuDeleted;
use App\Models\NodeDeleted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProxmoxService2
{
    protected $authService;
    protected $clusterService;
    protected $nodeService;
    protected $qemuService;
    protected $storageService;
    protected $inventoryService;
    protected $monitoringService;

    protected $client;
    protected $baseUrl;
    protected $nodeInfo = [];
    protected $clusterName;
    protected $updatedQemuIds = [];
    protected $updatedQemuKeys = [];
    protected $processedNodes = [];
    protected $targetCluster = null;
    protected $syncRunId = null;
    protected $nodeCache = [];

    public function __construct(
        AuthenticationService $authService,
        ClusterService $clusterService,
        NodeService $nodeService,
        QemuService $qemuService,
        StorageService $storageService,
        InventoryService $inventoryService,
        MonitoringService $monitoringService
    ) {
        $this->authService = $authService;
        $this->clusterService = $clusterService;
        $this->nodeService = $nodeService;
        $this->qemuService = $qemuService;
        $this->storageService = $storageService;
        $this->inventoryService = $inventoryService;
        $this->monitoringService = $monitoringService;
        $this->client = new Client();
    }

    public function getAuthToken($ip, $username, $password)
    {
        return $this->authService->getAuthToken($ip, $username, $password);
    }

    protected function makeRequest($method, $url, $authData)
    {
        return $this->authService->makeRequest($method, $url, $authData);
    }

    public function addClusterWithToken($ip, $tokenId, $tokenSecret)
    {
        $this->baseUrl = 'https://' . $ip . ':8006/api2/json';

        // Preparamos la cabecera de autenticación para Token
        // Formato: PVEAPIToken=USER@REALM!TOKENID=UUID
        $authHeader = "PVEAPIToken={$tokenId}={$tokenSecret}";

        $authData = [
            'type' => 'token',
            'header' => $authHeader
        ];

        // 1. VERIFICAR CONEXIÓN: Intentamos obtener datos básicos para probar el token
        try {
            // Usamos 'version' como endpoint ligero para probar autenticación
            $this->makeRequest('GET', $this->baseUrl . '/version', $authData);
        } catch (\Exception $e) {
            throw new \Exception("No se pudo validar el Token: " . $e->getMessage());
        }

        // 2. Si pasa, guardamos las credenciales (reusando campos existentes para no migrar DB)
        // Guardamos el secret encriptado, igual que hacíamos con el password
        ClusterCredentials::updateOrCreate(['ip' => $ip], [
            'username' => $tokenId,                  // Guardamos "root@pam!token" en username
            'password' => Crypt::encrypt($tokenSecret), // Guardamos el UUID encriptado en password
        ]);

        // 3. Sincronizamos inmediatamente para obtener el resto de nodos
        $this->addClusterCredentialsFromToken($ip, $tokenId, $tokenSecret);
    }

    protected function addClusterCredentialsFromToken($ip, $tokenId, $tokenSecret)
    {
        $authData = [
            'type' => 'token',
            'header' => "PVEAPIToken={$tokenId}={$tokenSecret}"
        ];

        // Obtenemos estado del cluster para ver TODOS los nodos
        $url = 'https://' . $ip . ':8006/api2/json/cluster/status';
        try {
            $data = $this->makeRequest('GET', $url, $authData);
        } catch (\Exception $e) {
            Log::channel('proxmox_sync')->error("Error obteniendo status inicial del cluster {$ip}: " . $e->getMessage());
            return;
        }

        if (!$data) return;

        $clusterName = null;
        $nodeItems = [];

        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] == 'cluster' && isset($item['name'])) {
                $clusterName = $item['name'];
            }

            if (isset($item['type']) && $item['type'] == 'node' && isset($item['name'])) {
                $nodeItems[] = $item;
            }
        }

        // Proxmox standalone / PVE 6 puede no devolver item type=cluster en /cluster/status.
        // En ese caso usamos el nombre del nodo como nombre lógico del cluster en la plataforma.
        if (!$clusterName && !empty($nodeItems)) {
            $clusterName = $nodeItems[0]['name'];
            Log::channel('proxmox_sync')->info("SYNC TOKEN: Proxmox sin objeto cluster detectado en {$ip}; se usará '{$clusterName}' como nombre lógico.");
        }

        if (!$clusterName) {
            throw new \Exception("No se pudo detectar nombre de cluster ni nodos desde /cluster/status para {$ip}.");
        }

        $nodeNames = implode(',', array_map(function ($node) {
            return $node['name'];
        }, $nodeItems));

        cluster::updateOrCreate(['id_proxmox' => $clusterName], [
            'type' => 'cluster',
            'name' => $clusterName,
            'node_count' => count($nodeItems),
            'nodes' => $nodeNames,
        ]);

        foreach ($nodeItems as $item) {
            // En standalone, Proxmox puede reportar una IP interna distinta a la IP de gestión usada para conectar.
            // Para el nodo local mantenemos la IP ingresada en el formulario, que ya validó /version y /cluster/status.
            $nodeIp = (!empty($item['local'])) ? $ip : ($item['ip'] ?? $ip);

            // 1. Guardar Credenciales (para todos los nodos)
            ClusterCredentials::updateOrCreate(['ip' => $nodeIp], [
                'username' => $tokenId,
                'password' => Crypt::encrypt($tokenSecret),
            ]);

            // 2. Crear/actualizar el nodo del cluster detectado.
            node::updateOrCreate(
                ['id_proxmox' => $item['id'] ?? ('node/' . $item['name'])],
                [
                    'cluster_name' => $clusterName,
                    'node' => $item['name'],
                    'ip' => $nodeIp,
                    'status' => 'unknown',
                    'disk' => 0, 'maxdisk' => 0,
                    'mem' => 0, 'maxmem' => 0,
                    'cpu' => 0, 'maxcpu' => 0,
                    'uptime' => 0,
                    'type' => 'node'
                ]
            );
        }

        // 3. Ahora que los nodos existen en la DB, sincronizamos SOLO el cluster recién detectado.
        $this->processClusterNodes(true, $clusterName);
    }

    public function processClusterNodes($runQga = true, $targetCluster = null)
    {
        $this->processedNodes = [];
        $this->updatedQemuIds = [];
        $this->updatedQemuKeys = [];
        $this->targetCluster = $targetCluster ? trim($targetCluster) : null;
        $this->syncRunId = DB::getSchemaBuilder()->hasTable('proxmox_sync_runs') ? DB::table('proxmox_sync_runs')->insertGetId([
            'cluster_name' => $this->targetCluster ?: 'GLOBAL', 'started_at' => Carbon::now(),
            'status' => 'running', 'expected_nodes' => 0, 'processed_nodes' => 0, 'failed_nodes' => 0,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]) : null;
        $this->nodeCache = node::all()->keyBy('id_proxmox');

        // LOG DE DEPURACIÓN: Para saber qué está intentando hacer
        if ($targetCluster) {
            Log::channel('proxmox_sync')->info("SYNC START: Iniciando sincronización EXCLUSIVA del cluster: '{$targetCluster}'");
        } else {
            Log::channel('proxmox_sync')->info("SYNC START: Iniciando sincronización GLOBAL (Todos los clusters).");
        }

        $query = node::query();

        if ($targetCluster) {
            // OPCIÓN A: Solo el cluster específico
            $query->where('cluster_name', 'LIKE', trim($targetCluster));
        } else {
            // OPCIÓN B: Todos los clusters válidos (Lógica original)
            $query->whereIn('cluster_name', \App\Models\cluster::all()->pluck('name')->toArray())
                  ->orWhereNull('cluster_name');
        }

        $nodesToProcess = $query->get();
        $failedNodes = 0; $processedNodesCount = 0;
        if ($this->syncRunId) DB::table('proxmox_sync_runs')->where('id', $this->syncRunId)->update(['expected_nodes' => $nodesToProcess->count(), 'updated_at' => Carbon::now()]);

        // Log para verificar cuántos nodos encontró
        Log::channel('proxmox_sync')->info("SYNC INFO: Se encontraron " . $nodesToProcess->count() . " nodos para procesar.");

        foreach ($nodesToProcess as $node) {
            $this->baseUrl = 'https://' . $node->ip . ':8006/api2/json';
            try {
                $credentials = ClusterCredentials::where('ip', $node->ip)->first();
                if (!$credentials) { $failedNodes++; continue; }

                // === DETECCIÓN AUTOMÁTICA DE TOKEN ===
                if (strpos($credentials->username, '!') !== false) {
                    $authData = [
                        'type' => 'token',
                        'header' => "PVEAPIToken=" . $credentials->username . "=" . Crypt::decrypt($credentials->password)
                    ];
                    $this->getProxmoxData($authData, $node, $runQga);
                    $processedNodesCount++;
                }
                else {
                    $authData = $this->getAuthToken($node->ip, $credentials->username, $credentials->password);
                    if ($authData) {
                        $this->getProxmoxData($authData, $node, $runQga);
                        $processedNodesCount++;
                    } else { $failedNodes++; }
                }

            } catch (\Exception $e) {
               Log::channel('proxmox_sync')->error("Error procesando nodo {$node->ip}: " . $e->getMessage());
               $failedNodes++;
               continue;
            }
        }

        if ($this->syncRunId) DB::table('proxmox_sync_runs')->where('id', $this->syncRunId)->update([
            'finished_at' => Carbon::now(), 'processed_nodes' => $processedNodesCount, 'failed_nodes' => $failedNodes,
            'status' => $failedNodes === 0 ? 'completed' : 'partial', 'updated_at' => Carbon::now()]);

        // Se registra una fotografía sólo después de terminar el recorrido del cluster.
        $snapshotClusters = $nodesToProcess->pluck('cluster_name')->filter()->unique();
        foreach ($snapshotClusters as $snapshotCluster) {
            $this->recordVmSnapshot($snapshotCluster);
        }
    }


    /**
     * Compara el inventario actual con el snapshot anterior del cluster.
     * VMID es identidad lógica dentro del cluster; el nodo sólo es atributo mutable.
     */
    public function recordVmSnapshot($clusterName)
    {
        if (!DB::getSchemaBuilder()->hasTable('proxmox_vm_snapshots')) return;
        $now = Carbon::now();
        $previous = DB::table('proxmox_vm_snapshots')->where('cluster_name', $clusterName)
            ->orderByDesc('snapshot_at')->get()->keyBy(fn ($row) => (string) $row->vmid);
        $current = DB::table('qemus')->where('cluster_name', $clusterName)->get(['vmid','name','node_id','id_proxmox']);
        $currentKeys = [];
        foreach ($current as $vm) {
            $key=(string)$vm->vmid; $currentKeys[$key]=true;
            DB::table('proxmox_vm_snapshots')->insert([
                'cluster_name'=>$clusterName,'snapshot_at'=>$now,'vmid'=>$vm->vmid,
                'name'=>$vm->name,'node_id'=>$vm->node_id,'id_proxmox'=>$vm->id_proxmox,
                'created_at'=>$now,'updated_at'=>$now
            ]);
            if (!$previous->has($key) && $previous->isNotEmpty()) {
                DB::table('proxmox_vm_events')->insert(['cluster_name'=>$clusterName,'vmid'=>$vm->vmid,'event_type'=>'created','event_at'=>$now,'old_node_id'=>null,'new_node_id'=>$vm->node_id,'old_id_proxmox'=>null,'new_id_proxmox'=>$vm->id_proxmox,'created_at'=>$now,'updated_at'=>$now]);
            } elseif ($previous->has($key) && (string)$previous[$key]->node_id !== (string)$vm->node_id) {
                DB::table('proxmox_vm_events')->insert(['cluster_name'=>$clusterName,'vmid'=>$vm->vmid,'event_type'=>'migrated','event_at'=>$now,'old_node_id'=>$previous[$key]->node_id,'new_node_id'=>$vm->node_id,'old_id_proxmox'=>$previous[$key]->id_proxmox,'new_id_proxmox'=>$vm->id_proxmox,'created_at'=>$now,'updated_at'=>$now]);
            }
        }
        foreach ($previous as $key=>$vm) if (!isset($currentKeys[$key])) {
            DB::table('proxmox_vm_events')->insert(['cluster_name'=>$clusterName,'vmid'=>$vm->vmid,'event_type'=>'deleted','event_at'=>$now,'old_node_id'=>$vm->node_id,'new_node_id'=>null,'old_id_proxmox'=>$vm->id_proxmox,'new_id_proxmox'=>null,'created_at'=>$now,'updated_at'=>$now]);
        }
    }

    public function getProxmoxData($authData, $node, $runQga = true)
    {
        $this->clusterName = null;
        $this->fetchAndSaveClusterStatus($authData);
        $this->fetchAndSaveNodeResources($authData, $node, $runQga);
    }

    protected function fetchAndSaveClusterStatus($authData)
    {
        $this->clusterService->fetchAndSaveClusterStatus($authData, $this->baseUrl, $this->nodeInfo, $this->clusterName);
    }

    protected function fetchAndSaveNodeResources($authData, $dbNode, $runQga = true)
    {
        $nodeName = $dbNode->node;

        // 1. Obtener el ESTADO del Nodo
        $statusUrl = $this->baseUrl . '/nodes/' . $nodeName . '/status';
        $statusData = $this->makeRequest('GET', $statusUrl, $authData);

        if ($statusData) {
            $nodeItem = [
                'id' => $dbNode->id_proxmox,
                'node' => $nodeName,
                'status' => 'online',
                'disk' => isset($statusData['rootfs']['used']) ? $statusData['rootfs']['used'] : 0,
                'maxdisk' => isset($statusData['rootfs']['total']) ? $statusData['rootfs']['total'] : 0,
                'mem' => isset($statusData['memory']['used']) ? $statusData['memory']['used'] : 0,
                'maxmem' => isset($statusData['memory']['total']) ? $statusData['memory']['total'] : 0,
                'cpu' => isset($statusData['cpu']) ? $statusData['cpu'] : 0,
                'maxcpu' => isset($statusData['cpuinfo']['cpus']) ? $statusData['cpuinfo']['cpus'] : 0,
                'uptime' => isset($statusData['uptime']) ? $statusData['uptime'] : 0,
                'type' => 'node',
                'ip' => $dbNode->ip,
            ];
            $ipInfo = isset($this->nodeInfo[$dbNode->id_proxmox]) ? $this->nodeInfo[$dbNode->id_proxmox] : null;
            $this->fetchAndSaveNodeData($nodeItem, $ipInfo);
        }

        $this->fetchAndSaveNodeNetwork($authData, $dbNode);

        // 2. Obtener ALMACENAMIENTOS para este Nodo
        $storageUrl = $this->baseUrl . '/nodes/' . $nodeName . '/storage';
        $storages = $this->makeRequest('GET', $storageUrl, $authData);
        if ($storages) {
            foreach ($storages as $storageItem) {
                $storageItem['node'] = $nodeName;
                $this->fetchAndSaveStorageData($storageItem);
            }
        }

        // 3. Obtener VMs (Qemu) para este Nodo
        $qemuUrl = $this->baseUrl . '/nodes/' . $nodeName . '/qemu';

        try {
            $qemus = $this->makeRequest('GET', $qemuUrl, $authData);
        } catch (\Exception $e) {
            Log::channel('proxmox_sync')->error("SYNC NODE {$nodeName}: Falló al obtener lista de VMs. " . $e->getMessage());
            $qemus = [];
        }

        if ($qemus) {
            // LOG DE INICIO: Cuántas VMs dice Proxmox que tiene este nodo
            Log::channel('proxmox_sync')->info("SYNC NODE {$nodeName}: Procesando " . count($qemus) . " VMs encontradas en API.");

            foreach ($qemus as $qemuItem) {
                // --- INICIO BLINDAJE ---
                try {
                    $qemuItem['node'] = $nodeName;
                    $this->fetchAndSaveQemuData($authData, $qemuItem, $runQga);
                } catch (\Exception $e) {
                    // Si falla UNA VM, solo logueamos esa y seguimos con la siguiente
                    $vmid = $qemuItem['vmid'] ?? 'UNKNOWN';
                    Log::channel('proxmox_sync')->error("SYNC ERROR VM {$vmid} en nodo {$nodeName}: Saltando por error -> " . $e->getMessage());
                }
                // --- FIN BLINDAJE ---
            }
        }
        $this->processedNodes[] = 'node/' . $dbNode->node;
    }

    protected function fetchAndSaveNodeNetwork($authData, $dbNode)
    {
        $this->nodeService->fetchAndSaveNodeNetwork($authData, $dbNode, $this->baseUrl);
    }

    protected function fetchAndSaveQemuData($authData, $qemuItem, $runQga = true)
    {
        $this->qemuService->fetchAndSaveQemuData(
            $authData,
            $qemuItem,
            $runQga,
            $this->baseUrl,
            $this->clusterName,
            $this->updatedQemuIds,
            $this->updatedQemuKeys,
            $this->nodeCache
        );
    }

    protected function fetchAndSaveNodeData($nodeItem, $IP)
    {
        $this->nodeService->fetchAndSaveNodeData($nodeItem, $IP, $this->clusterName, $this->nodeCache);
    }

    protected function fetchAndSaveStorageData($storageItem)
    {
        $this->storageService->fetchAndSaveStorageData($storageItem, $this->clusterName, $this->nodeCache);
    }

    public function addCluster($ip, $username, $password)
    {
        $this->baseUrl = 'https://' . $ip . ':8006/api2/json';
        $passwordEncrypt = Crypt::encrypt($password);
        try {
            $authData = $this->getAuthToken($ip, $username, $passwordEncrypt);
            if ($authData) {
                $this->addClusterCredentials($ip, $username, $password);
                $this->VMHistory();
                $this->MonthlyTotals();
            } else {
                throw new \Exception("Fallo en la autenticación. Revisa IP, usuario y contraseña/token.");
            }
        } catch (\Exception $e) {
            Log::channel('proxmox_sync')->error("Error general al añadir cluster {$ip}: " . $e->getMessage());
            throw $e;
        }
    }

    public function addClusterCredentials($ip, $username, $password)
    {
        $this->baseUrl = 'https://' . $ip . ':8006/api2/json';
        $url = $this->baseUrl . '/cluster/status';
        $passwordEncrypt = Crypt::encrypt($password);
        $authData = $this->getAuthToken($ip, $username, $passwordEncrypt);
        $data = $this->makeRequest('GET', $url, $authData);
        if(!$data) return;

        $IpNodes = [];
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] == 'node' && isset($item['ip'])) {
                $IpNodes[] = $item['ip'];
            }
        }
        foreach ($IpNodes as $ipNode) {
            ClusterCredentials::updateOrCreate(['ip' => $ipNode], [
                'username' => $username,
                'password' => $passwordEncrypt,
            ]);
        }
    }

    public function VMHistory()
    {
        try {
            $clusters = cluster::all();
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            foreach ($clusters as $cluster) {
                $qemus = \App\Models\qemu::where('cluster_name', $cluster->name)->get();
                $nodeIds = \App\Models\node::where('cluster_name', $cluster->name)->pluck('id_proxmox');

                // Desde la incorporación de snapshots, los movimientos salen de eventos
                // comparados contra la fotografía anterior del mismo cluster.
                if (DB::getSchemaBuilder()->hasTable('proxmox_vm_events')) {
                    $vmsCreated = DB::table('proxmox_vm_events')->where('cluster_name', $cluster->name)
                        ->where('event_type', 'created')->whereBetween('event_at', [$startOfMonth, $endOfMonth])->count();
                    $vmsDeleted = DB::table('proxmox_vm_events')->where('cluster_name', $cluster->name)
                        ->where('event_type', 'deleted')->whereBetween('event_at', [$startOfMonth, $endOfMonth])->count();
                } else {
                    $vmsCreated = 0; $vmsDeleted = 0;
                }

                $nodesCreated = \App\Models\node::where('cluster_name', $cluster->name)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();

                $nodesDeleted = \App\Models\NodeDeleted::where('cluster_name', $cluster->name)
                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                    ->count();

                VirtualMachineHistory::updateOrCreate(
                    ['date' => $startOfMonth, 'cluster_name' => $cluster->name],
                    [
                        'cluster_qemus' => $qemus->count(),
                        'cluster_cpu' => $qemus->sum('maxcpu'),
                        'cluster_memory' => $qemus->sum('maxmem'),
                        'cluster_disk' => $qemus->sum('size'),
                        'vms_created' => $vmsCreated,
                        'vms_deleted' => $vmsDeleted,
                        'nodes_created' => $nodesCreated,
                        'nodes_deleted' => $nodesDeleted,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Error en VMHistory: " . $e->getMessage());
        }
    }

    public function MonthlyTotals()
    {
        try {
            $startOfMonth = Carbon::now()->startOfMonth();
            $totalsForMonth = VirtualMachineHistory::where('date', $startOfMonth)->get();
            MonthlyTotal::updateOrCreate(['date' => $startOfMonth], [
                'cluster_qemus' => $totalsForMonth->sum('cluster_qemus'),
                'cluster_cpu' => $totalsForMonth->sum('cluster_cpu'),
                'cluster_memory' => $totalsForMonth->sum('cluster_memory'),
                'cluster_disk' => $totalsForMonth->sum('cluster_disk'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en MonthlyTotals: " . $e->getMessage());
        }
    }
    public function markMissingQemuAsDeleted()
    {
        // 1. Si no procesamos ningún nodo, NO borramos nada (seguridad)
        if (empty($this->processedNodes)) {
            Log::channel('proxmox_sync')->warning("SYNC CLEANUP ABORTED: No se procesó ningún nodo correctamente.");
            return;
        }

        // 2. Buscamos VMs que estén en la DB, pertenezcan a nodos que SÍ revisamos,
        // pero que NO aparecieron en el escaneo actual.
        // La identidad lógica es cluster + VMID, no node + VMID.
        // Así una migración de nodo no se convierte en una baja.
        $qemuQuery = \App\Models\qemu::whereIn('node_id', $this->processedNodes);
        if ($this->targetCluster) {
            $qemuQuery->where('cluster_name', $this->targetCluster);
        }
        $qemusToDelete = $qemuQuery->get()->filter(function ($qemu) {
            $key = ($qemu->cluster_name ?: $this->targetCluster ?: '') . '|' . (string) $qemu->vmid;
            return !in_array($key, $this->updatedQemuKeys, true);
        });

        if ($qemusToDelete->isEmpty()) return;

        Log::channel('proxmox_sync')->info("SYNC CLEANUP: " . $qemusToDelete->count() . " VMs no encontradas; se evaluarán como bajas reales (no migraciones).");

        foreach($qemusToDelete as $qemu) {
            // Guardamos respaldo en QemuDeleted
            $data = $qemu->toArray();
            QemuDeleted::updateOrCreate(
                ['id_proxmox' => $qemu->id_proxmox],
                $data
            );
        }

        // Borramos solo las identificadas
        $idsToDelete = $qemusToDelete->pluck('id_proxmox');
        \App\Models\qemu::whereIn('id_proxmox', $idsToDelete)->delete();
    }

    public function markMissingNodeAsDeleted()
    {
        return $this->inventoryService->markMissingNodeAsDeleted($this->processedNodes);
    }



    /**
 * Ejecuta un comando en la VM vía QGA, espera y retorna la salida (stdout).
 */
     // En app/Services/ProxmoxService2.php

     // En app/Services/ProxmoxService2.php

protected function executeQgaCommand($authData, $node, $vmid, $commandArray)
{
    try {
        $execUrl = $this->baseUrl . "/nodes/{$node}/qemu/{$vmid}/agent/exec";
        $payload = ['command' => $commandArray];

        $response = $this->client->request('POST', $execUrl, [
            'headers' => isset($authData['header']) ? ['Authorization' => $authData['header']] : ['Cookie' => "PVEAuthCookie={$authData['ticket']}", 'CSRFPreventionToken' => $authData['CSRFPreventionToken']],
            'json' => $payload,
            'verify' => false,
            'timeout' => 3 // Bajamos timeout a 3s
        ]);

        $json = json_decode($response->getBody(), true);
        $pid = $json['data']['pid'] ?? null;

        if (!$pid) return null;

        $statusUrl = $this->baseUrl . "/nodes/{$node}/qemu/{$vmid}/agent/exec-status?pid={$pid}";

        // Polling muy rápido (Max 2 segundos)
        for ($i = 0; $i < 10; $i++) {
            usleep(200000);
            try {
                $statusResp = $this->makeRequest('GET', $statusUrl, $authData);
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

    /**
     * Helper inteligente para buscar un servicio en Linux probando múltiples rutas.
     */
    // En app/Services/ProxmoxService2.php

private function checkLinuxServiceRunning($authData, $node, $vmid, $serviceName)
{
    $commands = [
        ['systemctl', 'is-active', $serviceName],
        ['/usr/bin/systemctl', 'is-active', $serviceName],
        ['/bin/systemctl', 'is-active', $serviceName],
    ];

    foreach ($commands as $cmd) {
        // Al llamar a executeQgaCommand, si lanza "QGA_DISABLED",
        // NO lo atrapamos aquí, dejamos que suba a getMonitoringInfo para romper el ciclo.
        $status = $this->executeQgaCommand($authData, $node, $vmid, $cmd);

        if ($status === 'active') {
            return true;
        }
    }

    return false;
}
    // En app/Services/ProxmoxService2.php

    protected function getMonitoringInfo($authData, $node, $vmid)
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
            $zabbixRunning = $this->checkLinuxServiceRunning($authData, $node, $vmid, 'zabbix-agent');

            // 2. Si no, revisamos si corre el Agente 2
            if (!$zabbixRunning) {
                $zabbixRunning = $this->checkLinuxServiceRunning($authData, $node, $vmid, 'zabbix-agent2');
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
                    $resZVer = $this->executeQgaCommand($authData, $node, $vmid, $cmd);
                    // Buscamos patrón numérico ej: 7.0.21
                    if ($resZVer && preg_match('/(\d+\.\d+\.\d+)/', $resZVer, $matches)) {
                        $info['zabbix_version'] = $matches[1];
                        break; // Ya la encontramos, dejamos de buscar
                    }
                }
            }

            // --- WAZUH STATUS & VERSION ---
            if ($this->checkLinuxServiceRunning($authData, $node, $vmid, 'wazuh-agent')) {
                $info['wazuh_status'] = 'running';

                $wazuhCommands = [
                    ['/var/ossec/bin/wazuh-control', 'info'],
                    ['wazuh-control', 'info']
                ];

                foreach ($wazuhCommands as $cmd) {
                    $resWVer = $this->executeQgaCommand($authData, $node, $vmid, $cmd);
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


    public function resetUpdatedQemuIds()
    {
        $this->updatedQemuIds = [];
        $this->updatedQemuKeys = [];
    }

    public function extractStorageInfo($scsiString)
    {
        $storageName = explode(':', $scsiString)[0];
        preg_match('/size=(\d+(?:G|T))/', $scsiString, $matches);
        return ['storageName' => $storageName, 'size' => isset($matches[1]) ? $matches[1] : null];
    }
}

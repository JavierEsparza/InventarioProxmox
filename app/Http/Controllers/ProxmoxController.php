<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\qemu;
use App\Models\Storage;
use App\Models\node;
use App\Models\cluster;
use App\Models\MonthlyTotal;
use App\Models\QemuDeleted;
use App\Models\VirtualMachineHistory;
use App\Services\ProxmoxService2;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Node_storage;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Net\SSH2;
use Illuminate\Validation\Rule;

class ProxmoxController extends Controller
{
    protected $proxmoxService;

    public function __construct(ProxmoxService2 $proxmoxService)
    {
        $this->middleware('auth', ['except' => ['getDataRedirect2']]);
        $this->proxmoxService = $proxmoxService;
    }

    // --- MÉTODO PRIVADO: Maneja la lógica de Reportes y Eliminación (Lenta) ---
private function slowSyncAndReport()
{
    // 1. Ejecutar el proceso de fetch principal (incluyendo QGA/IP/OS)
    $this->proxmoxService->processClusterNodes(true); // TRUE = Habilitar llamadas QGA lentas

    // 2. Correr la lógica de post-procesamiento
    $this->proxmoxService->markMissingQemuAsDeleted();
    $this->proxmoxService->VMHistory();
    $this->proxmoxService->resetUpdatedQemuIds();
    $this->proxmoxService->MonthlyTotals();
}

// =========================================================================
// === BOTÓN 1: SINCRONIZACIÓN RÁPIDA (Estado y Uso) - FRECUENTE
// =========================================================================

public function fastSyncRedirect()
{
    set_time_limit(120); // 2 minutos para el timeout
    try {
        // Run basic resource fetch (EXCLUYE QGA y no actualiza historial)
        $this->proxmoxService->processClusterNodes(false); // FALSE = Omitir llamadas QGA lentas

        return redirect()->back()->with('success', 'Sincronización rápida completada (Estado, Uso y Discos).');
    } catch (\Exception $e) {
        Log::error('Error en sincronización rápida (Estado/Uso): ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error en sincronización rápida: ' . $e->getMessage());
    }
}

// =========================================================================
// === BOTÓN 2: SINCRONIZACIÓN LENTA (IP, OS, Historial, Eliminadas) - INFRECUENTE
// =========================================================================

public function qgaSyncRedirect()
{
    set_time_limit(1200); // 20 minutos para el timeout (QGA es lento)
    try {
        $this->slowSyncAndReport(); // Llama a la lógica completa y guarda reportes

        return redirect()->back()->with('success', 'Sincronización lenta completada (IP, OS, Historial).');
    } catch (\Exception $e) {
        Log::error('Error en sincronización lenta: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error en sincronización lenta: ' . $e->getMessage());
    }
}


public function index()
{
    // --- 1. Definir Unidades ---
    $GIB_DIVISOR = 1024 * 1024 * 1024;
    $TB_DIVISOR = 1000 * 1000 * 1000 * 1000;

    // --- 2. Totales de la Infraestructura (Nodos) ---
    $totalMaxCPU = \App\Models\node::sum('maxcpu');
    $avgCpuLoad = \App\Models\node::avg('cpu');
    $totalMaxRAMBytes = \App\Models\node::sum('maxmem');
    $totalUsedRAMBytes = \App\Models\node::sum('mem');

    // --- 3. Totales de VMs ---
    $totalQemus = \App\Models\qemu::count();
    $totalStoppedQemus = \App\Models\qemu::where('status', '!=', 'running')->count();

    // --- 3.B. Obtener VMs para las tablas del acordeón ---
    $stoppedQemusList = \App\Models\qemu::where('status', '!=', 'running')
                            ->orderBy('cluster_name')
                            ->orderBy('name')
                            ->get();

    $topRamQemus = \App\Models\qemu::where('status', 'running')
                        ->orderBy('mem', 'desc')
                        ->take(10)
                        ->get();

    // ¡NUEVO! Lista completa de VMs
    $allQemusList = \App\Models\qemu::orderBy('cluster_name')
                        ->orderBy('name')
                        ->get();

    // --- 4. Totales de Storage (LÓGICA MEJORADA CON NODOS) ---
    $storagesToExclude = ['local', 'local-lvm'];

    // Obtenemos TODOS los storages y los agrupamos por su nombre
    $allStoragesGrouped = \App\Models\Storage::all()->groupBy('storage');
    $totalStorageUsedBytes = 0;
    $totalStorageMaxBytes = 0;
    $globalStoragesList = []; // Esta será la nueva lista de objetos

    foreach ($allStoragesGrouped as $storageName => $storagesInGroup) {

        // 1. Revisar si este storage está excluido
        $isExcluded = false;
        if (in_array(strtolower($storageName), array_map('strtolower', $storagesToExclude), true)) {
            $isExcluded = true;
        }

        if ($isExcluded) {
            continue; // Saltar este grupo
        }

        // 2. Tomar el primer storage del grupo como referencia para datos comunes
        $referenceStorage = $storagesInGroup->first();

        // 3. Preparar la lista de nodos (solo los nombres de nodo, únicos)
        //$nodeNames = $storagesInGroup->pluck('node')->unique()->implode(', ');
        $nodeNameList = $storagesInGroup->pluck('node_id')->unique()
                                     ->map(fn($id) => str_replace('node/', '', $id))
                                     ->values();
        $nodeNames = $nodeNameList->implode('<br>');
        $firstNodeNames = $nodeNameList->take(6)->implode(', ');

        // 4. Sumar al total del dashboard (solo una vez por storage compartido)
        $totalStorageUsedBytes += $referenceStorage->disk;
        $totalStorageMaxBytes += $referenceStorage->maxdisk;

        // 5. Calcular espacio disponible (proteger contra negativos y maxdisk=0)
        $availableBytes = max(0, $referenceStorage->maxdisk - $referenceStorage->disk);
        $usedPercent = ($referenceStorage->maxdisk > 0) ? ($referenceStorage->disk / $referenceStorage->maxdisk) * 100 : 0;

        // 6. Añadir a la lista para la tabla
        $globalStoragesList[] = (object) [
            'storage' => $storageName,
            'plugintype' => $referenceStorage->plugintype,
            'disk' => $referenceStorage->disk,
            'maxdisk' => $referenceStorage->maxdisk,
            'available' => $availableBytes,
            'usedPercent' => $usedPercent,
            'nodes' => $nodeNames, // Lista completa para tooltip/compatibilidad
            'node_count' => $nodeNameList->count(),
            'first_nodes' => $firstNodeNames,
        ];
    }

    // Ordenar la lista de objetos por nombre
    $globalStoragesList = collect($globalStoragesList)->sortBy('storage');

    // --- Storages Críticos (>=90%) para el panel de alertas ---
    $criticalStorages = $globalStoragesList->filter(function ($s) {
        return round($s->usedPercent) >= 90;
    })->sortByDesc('usedPercent');


    // --- 5. Calcular Porcentajes ---
    $cpuUsagePercentage = ($avgCpuLoad ?? 0) * 100;
    $memoryUsagePercentage = ($totalMaxRAMBytes > 0) ? ($totalUsedRAMBytes / $totalMaxRAMBytes) * 100 : 0;
    $storageUsagePercentage = ($totalStorageMaxBytes > 0) ? ($totalStorageUsedBytes / $totalStorageMaxBytes) * 100 : 0;

    // --- 6. Obtener todos los nodos para la tabla ---
    $allNodes = \App\Models\node::all()->sortBy('cluster_name');

    // --- 7. Retornar la Vista con TODAS las variables ---
    return view('proxmox.show', [
        'totalClusters' => \App\Models\cluster::count(),
        'totalNodes' => \App\Models\node::count(),
        'totalQemus' => $totalQemus,
        'totalStoppedQemus' => $totalStoppedQemus,

        'totalMaxCPU' => $totalMaxCPU,
        'cpuUsagePercentage' => round($cpuUsagePercentage, 2),

        'totalUsedRAM' => round($totalUsedRAMBytes / $GIB_DIVISOR, 2),
        'totalMaxRAM' => round($totalMaxRAMBytes / $GIB_DIVISOR, 2),
        'memoryUsagePercentage' => round($memoryUsagePercentage, 2),

        'totalUsedStorage' => round($totalStorageUsedBytes / $TB_DIVISOR, 2),
        'totalMaxStorage' => round($totalStorageMaxBytes / $TB_DIVISOR, 2),
        'storageUsagePercentage' => round($storageUsagePercentage, 2),

        'nodes' => $allNodes,
        'clusters' => \App\Models\cluster::all(),

        // --- ¡NUEVAS VARIABLES PARA LAS TABLAS! ---
        'stoppedQemusList' => $stoppedQemusList,
        'topRamQemus' => $topRamQemus,
        'allQemusList' => $allQemusList, // <-- ¡NUEVA!
        'globalStoragesList' => $globalStoragesList,
        'criticalStorages' => $criticalStorages,
    ]);
}
    private function getSizeInBytes($sizeStr)
    {
        preg_match('/(\d+)(G|T)/', $sizeStr, $matches);
        $size = $matches[1] ?? 0;
        $unit = $matches[2] ?? 'G';
        if ($unit === 'T') return $size * 1024 * 1024 * 1024 * 1024;
        return $size * 1024 * 1024 * 1024;
    }

    public function cluster()
    {
        $clusters = cluster::all()->sortBy('name')->values();
        $nodes = \App\Models\node::all()->groupBy('cluster_name');
        $qemus = \App\Models\qemu::all()->groupBy('cluster_name');

        $clusters = $clusters->map(function ($cluster) use ($nodes, $qemus) {
            $clusterNodes = $nodes->get($cluster->name, collect());
            $clusterVms = $qemus->get($cluster->name, collect());
            $nodeNames = collect(explode(',', (string) $cluster->nodes))
                ->map(fn($n) => trim($n))
                ->filter()
                ->values();

            $cluster->node_names = $nodeNames;
            $cluster->node_names_preview = $nodeNames->take(5)->implode(', ');
            $cluster->node_records = $clusterNodes->count();
            $cluster->online_nodes = $clusterNodes->filter(fn($n) => strtolower((string) $n->status) === 'online')->count();
            $cluster->offline_nodes = max(0, $clusterNodes->count() - $cluster->online_nodes);
            $cluster->vm_count = $clusterVms->count();
            $cluster->running_vms = $clusterVms->filter(fn($q) => strtolower((string) $q->status) === 'running')->count();
            $cluster->stopped_vms = max(0, $cluster->vm_count - $cluster->running_vms);
            $cluster->total_cpu = $clusterNodes->sum('maxcpu');
            $cluster->total_ram = $clusterNodes->sum('maxmem');
            $cluster->used_ram = $clusterNodes->sum('mem');
            $cluster->ram_percent = $cluster->total_ram > 0 ? ($cluster->used_ram / $cluster->total_ram) * 100 : 0;
            $cluster->avg_cpu_percent = $clusterNodes->count() > 0 ? ($clusterNodes->avg('cpu') * 100) : 0;
            $cluster->last_node_update = $clusterNodes->max('updated_at');
            return $cluster;
        });

        return view('proxmox.cluster.index', ['clusters' => $clusters]);
    }

    public function node()
    {
        $nodes = node::all();
        $nodesIdProxmox = $nodes->pluck('id_proxmox')->toArray();
        $qemus = Qemu::whereIn('node_id', $nodesIdProxmox)->get();
        $storages = Storage::whereIn('node_id', $nodesIdProxmox)->get();

        $sizeSumByNodeId = []; // -> $storageLocal (Usado)
        $storageLocalMax = []; // -> $storageLocalMax (Total)

        // --- BUCLE CORREGIDO 1: Calcular el Almacenamiento Local TOTAL ---
        // Iteramos los storages y SUMAMOS *SOLAMENTE* los que son 'local' o 'local-lvm'
        foreach ($storages as $storage) {
            $nodeId = $storage->node_id;
            $storageName = strtolower($storage->storage); // Normalizamos a minúsculas

            if (!isset($storageLocalMax[$nodeId])) {
                $storageLocalMax[$nodeId] = 0;
            }

            // Solo sumamos el tamaño si el storage es local
            if ($storageName == 'local' || $storageName == 'local-lvm') {
                $storageLocalMax[$nodeId] += $storage->maxdisk;
            }
        }

        // --- BUCLE CORREGIDO 2: Calcular el Almacenamiento USADO por VMs ---
        // Iteramos las VMs y usamos el valor en bytes (qemu->size)
        foreach ($qemus as $qemu) {
            $nodeId = $qemu->node_id;

            // $size = $this->getSizeInBytes($qemu->size); // <-- ¡LÍNEA INCORRECTA ANTIGUA!
            $size = $qemu->size; // <-- ¡LÍNEA CORRECTA! Usamos el valor en bytes

            if (!isset($sizeSumByNodeId[$nodeId])) {
                $sizeSumByNodeId[$nodeId] = 0;
            }
            $sizeSumByNodeId[$nodeId] += $size;
        }

        // El resto de la función sigue igual
        return view('proxmox.node', [
            'nodes' => $nodes,
            'qemus' => $qemus,
            'storages' => $storages,
            'storageLocal' => $sizeSumByNodeId,    // <-- Ahora tiene los datos de USO correctos
            'storageLocalMax' => $storageLocalMax // <-- Ahora tiene los datos TOTALES correctos
        ]);
    }

    public function qemu(Request $request)
{
    // 1. Capturar búsqueda si existe
    $search = $request->input('search');

    // 2. Query Base
    $query = qemu::with('networkAddresses');

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('vmid', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('cluster_name', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%")
              ->orWhere('node_id', 'like', "%{$search}%")
	      ->orWhere('storageName', '=', $search);
	});
    }

    // 3. Obtener resultados ordenados por Cluster y luego por Nombre
    $qemus = $query->orderBy('cluster_name', 'asc')
                   ->orderBy('name', 'asc')
                   ->get();

    // 4. AGRUPAR POR CLUSTER (La clave para el acordeón)
    // Esto crea una colección donde la llave es el nombre del cluster y el valor es la lista de VMs
    $groupedQemus = $qemus->groupBy('cluster_name');

    // 5. La sincronización es por clúster: la fecha se toma de los nodos, no de una VM individual.
    $clusterLastUpdates = \App\Models\node::whereIn('cluster_name', $groupedQemus->keys()->all())
        ->get()->groupBy('cluster_name')
        ->map(fn($clusterNodes) => $clusterNodes->max('updated_at'));

    // 6. Retornar vista con la variable agrupada
    return view('proxmox.qemu', [
        'groupedQemus' => $groupedQemus,
        'clusterLastUpdates' => $clusterLastUpdates,
        'search' => $search
    ]);
}


    /**
     * Muestra la vista de Storage, AGRUPADA POR CLUSTER y POR NOMBRE, filtrada y con búsqueda.
     */
    public function storage(Request $request)
    {
        // 1. Obtener el término de búsqueda
        $search = $request->input('search');

        // 2. Iniciar la consulta base (Excluir 'local' y 'local-lvm')
        $query = \App\Models\Storage::query()
            ->where('storage', '!=', 'local')
            ->where('storage', '!=', 'local-lvm')
            ->where('status', 'online')
            ->whereNotNull('cluster');

        // 3. Aplicar la búsqueda (si existe)
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('storage', 'like', "%{$search}%")
                  ->orWhere('cluster', 'like', "%{$search}%")
                  ->orWhere('plugintype', 'like', "%{$search}%");
            });
        }

        // 4. Obtener los resultados ordenados
        $filteredStorages = $query->orderBy('cluster')->orderBy('storage')->get();

        // 5. Agrupar por Cluster (Este es el grupo del acordeón)
        $storagesGroupedByCluster = $filteredStorages->groupBy('cluster');

        // 6. --- ¡LA NUEVA LÓGICA! ---
        //    Iteramos sobre cada cluster y agrupamos sus storages por nombre

        $finalGroupedData = collect(); // Esta será la nueva colección que pasaremos a la vista

        foreach ($storagesGroupedByCluster as $clusterName => $storagesInCluster) {

            // Ahora, agrupamos los storages *dentro* de este cluster por su nombre
            $storagesGroupedByName = $storagesInCluster->groupBy('storage');

            $clusterStorageList = []; // Lista para los storages *únicos* de este cluster

            foreach ($storagesGroupedByName as $storageName => $storages) {

                // $storages es ahora una colección de storages idénticos (uno por nodo)
                // e.g., [ (Backup-Virt @ node1), (Backup-Virt @ node2) ]

                // Tomamos el primero como referencia para los datos
                $referenceStorage = $storages->first();

                // ...pero creamos la lista de nodos a partir de *todos* ellos
                $nodeNames = $storages->pluck('node_id')->unique()
                                       ->map(fn($id) => str_replace('node/', '', $id))
                                       ->implode('<br>'); // Usamos <br> para saltos de línea

                // Construimos el objeto de storage (ahora sí, único)
                $clusterStorageList[] = (object) [
                    'storage' => $storageName,
                    'plugintype' => $referenceStorage->plugintype,
                    'disk' => $referenceStorage->disk,
                    'maxdisk' => $referenceStorage->maxdisk,
                    'used' => $referenceStorage->used,
                    'content' => $referenceStorage->content,
                    'updated_at' => $referenceStorage->updated_at,
                    'nodes' => $nodeNames // Esta es la lista de nodos que lo ven
                ];
            }

            // Añadimos la lista *única* de este cluster a la colección final
            $finalGroupedData->put($clusterName, collect($clusterStorageList)->sortBy('storage'));
        }

        // 7. Pasar los datos doblemente agrupados a la vista
        return view('proxmox.storage', [
            'storagesByCluster' => $finalGroupedData, // ¡Datos correctos!
            'searchQuery'       => $search
        ]);
    }

    public function destroyCluster($name)
    {
        try {
            $cluster = cluster::find($name);
            if (!$cluster) throw new \Exception('Cluster no encontrado');
            $nodes = node::where('cluster_name', $name)->get();
            $nodesIdProxmox = $nodes->pluck('id_proxmox')->toArray();
            Qemu::whereIn('node_id', $nodesIdProxmox)->delete();
            $storages = Storage::where('cluster', $name)->get();
            foreach ($nodes as $node) {
                node_storage::where('node_id', $node->id)->delete();
                $node->delete();
            }
            foreach ($storages as $storage) {
                Node_storage::where('storage_id', $storage->id)->delete();
                $storage->delete();
            }
            $cluster->delete();
            return redirect()->route('proxmox.index');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while deleting the cluster.');
        }
    }

    public function destroyNode($name)
    {
        $node = node::where('node', $name)->first();
        if(!$node) return redirect()->back()->with('error', 'Nodo no encontrado.');
        Qemu::where('node_id', $node->id_proxmox)->delete();
        $storages = Storage::where('node_id', $node->id_proxmox)->get();
        node_storage::where('node_id', $node->id)->delete();
        foreach ($storages as $storage) {
            Node_storage::where('storage_id', $storage->id)->delete();
            $storage->delete();
        }
        $node->delete();
        return redirect()->route('proxmox.index');
    }

    public function createCluster()
    {
        return view('proxmox.cluster.create');
    }

    // En app/Http/Controllers/ProxmoxController.php

    public function storeCluster(Request $request)
    {
        // 1. Validación básica CORREGIDA
        $request->validate([
            'ip' => 'required',
            // Usamos regex para obligar a que contenga un '!'
            'token_id' => ['required', 'string', 'regex:/!/'],
            'token_secret' => 'required|string',
        ], [
            // Mensaje personalizado para cuando falle el regex
            'token_id.regex' => 'El Token ID debe tener el formato usuario@realm!nombretoken (debe incluir el "!")',
        ]);

        try {
            $ip = $request->input('ip');
            $tokenId = $request->input('token_id');
            $tokenSecret = $request->input('token_secret');

            Log::channel('proxmox_sync')->info('Intentando agregar nuevo cluster con API Token.', compact('ip', 'tokenId'));

            $this->proxmoxService->addClusterWithToken($ip, $tokenId, $tokenSecret);

            return redirect()->route('proxmox.index')->with('success', 'Cluster conectado vía Token y sincronizado correctamente.');
        } catch (\Exception $e) {
            Log::channel('proxmox_sync')->error('Error agregando el cluster con Token: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error al conectar: ' . $e->getMessage());
        }
    }
    public function showbyIdCluster($name)
{
    try {
        // --- 1. CONFIGURACIÓN Y OBTENCIÓN DE DATOS ---
        $GB = 1024 * 1024 * 1024;
        $TB = 1024 * 1024 * 1024 * 1024;
        $clusterName = $name;

        $cluster = Cluster::find($clusterName);
        if (!$cluster) throw new \Exception('Cluster no encontrado');

        $nodes = Node::where(DB::raw('TRIM(LOWER(cluster_name))'), strtolower(trim($clusterName)))
                     ->orderBy('node', 'asc')
                     ->get();

        if ($nodes->isEmpty()) {
            return redirect()->back()->with('error', 'El cluster no tiene nodos asociados.');
        }

        $nodesIdProxmox = $nodes->pluck('id_proxmox')->toArray();
        $qemus = Qemu::whereIn('node_id', $nodesIdProxmox)->get();

        $totalQemus = $qemus->count();
        $totalStoppedQemus = $qemus->where('status', '!=', 'running')->count();

        // --- Obtención de Storage Corregida ---
        $nodeDbIds = $nodes->pluck('id')->toArray();
        $storageIds = Node_storage::whereIn('node_id', $nodeDbIds)->pluck('storage_id')->unique();
        $allClusterStorages = Storage::whereIn('id', $storageIds)->get();

        // --- 2. CÁLCULO DE RECURSOS GLOBALES (Para los medidores principales) ---
        $totalMaxCPU = $nodes->sum('maxcpu');
        $averageCpuLoad = $nodes->avg('cpu');
        $cpuUsagePercentage = $averageCpuLoad * 100;

        $totalMaxRAMBytes = $nodes->sum('maxmem');
        $totalUsedRAMBytes = $nodes->sum('mem');
        $totalAssignedRAMBytes = $qemus->sum('maxmem');
        $memoryUsagePercentage = ($totalMaxRAMBytes > 0) ? ($totalUsedRAMBytes / $totalMaxRAMBytes) * 100 : 0;

        // --- LÓGICA DE ALMACENAMIENTO AGREGADO (PARA EL GRÁFICO TOTAL) ---
        $totalStorageUsedBytes = 0;
        $totalStorageMaxBytes = 0;
        $uniqueSharedForTotal = [];
        $storagesToExcludeGraph = ['local', 'local-lvm', 'Backup', 'Backup-Vicidial', 'Backup-Virt', 'Proxmox-Backup', 'Proxmox-BackupTI', 'PBS-ProxmoxTI'];

        foreach ($allClusterStorages as $storage) {
            if ($storage->status !== 'online') {
                continue;
            }

            $isExcluded = false;
            foreach ($storagesToExcludeGraph as $exclude) {
                if (stripos(strtolower($storage->storage), strtolower($exclude)) !== false) {
                    $isExcluded = true;
                    break;
                }
            }

            if (!$isExcluded) {
                // Sumamos compartidos (solo una vez)
                if ($storage->shared && !in_array($storage->storage, $uniqueSharedForTotal)) {
                     $uniqueSharedForTotal[] = $storage->storage;
                     $totalStorageUsedBytes += $storage->disk;
                     $totalStorageMaxBytes += $storage->maxdisk;
                }
            }
        }
        $storageUsagePercentage = ($totalStorageMaxBytes > 0) ? ($totalStorageUsedBytes / $totalStorageMaxBytes) * 100 : 0;

        // --- 3. CÁLCULOS PARA VISUALIZACIÓN (GB/TB) ---
        $totalMaxRAMGB = round($totalMaxRAMBytes / $GB, 2);
        $totalAssignedRAMGB = round($totalAssignedRAMBytes / $GB, 2);
        $totalUsedRAMGB = round($totalUsedRAMBytes / $GB, 2);
        $TB_DIVISOR = 1000 * 1000 * 1000 * 1000;
        $totalStorageUsedTB = round($totalStorageUsedBytes / $TB_DIVISOR, 2);
        $totalStorageMaxTB = round($totalStorageMaxBytes / $TB_DIVISOR, 2);

        // ==========================================================
        // === ¡LÓGICA DE CLASIFICACIÓN CORREGIDA! ===
        // ==========================================================
        $localStoragesByNode = [];
        $backupStorages = [];
        $dataStorages = [];
        $uniqueSharedNames = [];

        foreach ($allClusterStorages as $storage) {
            if ($storage->status !== 'online') {
                continue;
            }

            $storageNameLower = strtolower($storage->storage);

            // 1. Clasificar Almacenamiento Local (LÓGICA MEJORADA)
            if ($storageNameLower === 'local' || $storageNameLower === 'local-lvm') {
                //$localStoragesByNode[$storage->node][] = $storage;
	        $localStoragesByNode[$storage->node_id][] = $storage;
	    }
            // 2. Clasificar Backups
            elseif (stripos($storageNameLower, 'backup') !== false || stripos($storageNameLower, 'pbs') !== false || stripos($storageNameLower, 'virt') !== false) {
                if (!in_array($storage->storage, $uniqueSharedNames)) {
                    $uniqueSharedNames[] = $storage->storage;
                    $backupStorages[] = $storage;
                }
            }
            // 3. Clasificar Datos (Todo lo demás, incl. "Nodo2")
            else {
                if (!in_array($storage->storage, $uniqueSharedNames)) {
                    $uniqueSharedNames[] = $storage->storage;
                    $dataStorages[] = $storage;
                }
            }
        }
        ksort($localStoragesByNode);

        // --- 5. RETORNO DE LA VISTA CON LOS DATOS CORRECTOS ---
        return view('proxmox.cluster.show', [
            'cluster' => $cluster,
            'nodes' => $nodes,
            'qemus' => $qemus,
            'totalQemu' => $totalQemus,
            'totalStoppedQemus' => $totalStoppedQemus,

            'totalMaxCPU' => $totalMaxCPU,
            'totalUsedCPU' => $averageCpuLoad * $totalMaxCPU,
            'cpuUsagePercentage' => round($cpuUsagePercentage, 2),
            'totalMaxRAM' => $totalMaxRAMGB,
            'totalUsedRAM' => $totalUsedRAMGB,
            'totalAssignedRAM' => $totalAssignedRAMGB,
            'memoryUsagePercentage' => round($memoryUsagePercentage, 2),
            'totalMaxStorage' => $totalStorageMaxTB,
            'totalUsedStorage' => $totalStorageUsedTB,
            'storageUsagePercentage' => round($storageUsagePercentage, 2),

            'dataStorages' => $dataStorages,
            'backupStorages' => $backupStorages,
            'localStoragesByNode' => $localStoragesByNode,
        ]);

    } catch (\Exception $e) {
        Log::error("Error en showbyIdCluster para {$name}: " . (string) $e);
        return redirect()->back()->with('error', 'Ocurrió un error al obtener datos del cluster.');
    }
}

    public function exportClusterCSV()
    {
        $clusters = cluster::all();
        $csvExporter = new \Laracsv\Export();
        $csvExporter->build($clusters, ['name', 'node_count', 'nodes'])->download();
    }

    public function exportNodeCSV()
    {
        $nodes = Node::all();

        $fields = [
            'id', 'cluster_name', 'id_proxmox', 'name', 'ip',
            'type', 'online', 'status', 'disk', 'maxdisk',
            'node', 'mem', 'maxmem', 'cpu', 'maxcpu', 'uptime',
            'serial_number', 'brand', 'model', 'firmware_version',
            'pve_version', 'kernel_version'
        ];

        $csvExporter = new \Laracsv\Export();
        $csvExporter->build($nodes, $fields)->download('nodos_export.csv');
    }

    public function exportQemuCSV()
    {
        $qemus = qemu::with('networkAddresses')->orderBy('cluster_name')->orderBy('name')->get();
        $headers = [
            'VMID','Nombre','Cluster','Nodo','Estado','IP principal','IP manual','SO','SO manual','Origen IP/SO',
            'vCPU','RAM GB','Disco GB','Storage','QGA configurado','Infransible','Última auditoría servicios',
            'Antigüedad auditoría horas','Nota Infransible','Zabbix estado','Zabbix versión','Wazuh estado','Wazuh versión',
            'Servicios detectados','IPs secundarias','IPs backup/Veeam','VIPs','Actualizado inventario'
        ];
        $rows = [];
        foreach ($qemus as $vm) {
            $addresses = $vm->networkAddresses ?? collect();
            $joinIps = fn ($types) => $addresses->whereIn('network_type', $types)->pluck('ip_address')->filter()->unique()->implode(', ');
            $rawServices = $vm->infransible_services ?? '';
            $services = is_array($rawServices) ? $rawServices : (json_decode((string) $rawServices, true) ?: []);
            $serviceLabels = [
                'php' => 'PHP',
                'php-fpm' => 'PHP-FPM',
                'php_fpm' => 'PHP-FPM',
                'apache2' => 'Apache',
                'httpd' => 'Apache',
                'nginx' => 'Nginx',
                'mysql' => 'MySQL',
                'mariadb' => 'MariaDB',
                'postgresql' => 'PostgreSQL',
                'redis' => 'Redis',
                'haproxy' => 'HAProxy',
            ];
            $serviceStatusLabels = [
                'active' => 'ACTIVO',
                'installed_inactive' => 'INSTALADO',
                'inactive' => 'INACTIVO',
                'failed' => 'FALLIDO',
                'restarting' => 'REINICIANDO',
            ];
            $serviceText = collect($services)->filter(fn ($s) => !empty($s['installed']))->map(function ($s, $name) use ($serviceLabels, $serviceStatusLabels) {
                $label = $serviceLabels[$name] ?? ucwords(str_replace(['_', '-'], ' ', (string) $name));
                $version = !empty($s['version']) ? ' ' . $s['version'] : '';
                $status = $serviceStatusLabels[$s['status'] ?? ''] ?? null;
                return $label . $version . ($status ? ' [' . $status . ']' : '');
            })->implode(' | ');
            $auditAt = $vm->infransible_checked_at ? \Carbon\Carbon::parse($vm->infransible_checked_at) : null;
            $ageHours = $auditAt ? max(0, (int) $auditAt->diffInHours(now(), false)) : null;
            $rows[] = [
                $vm->vmid, $vm->name, $vm->cluster_name, str_replace('node/', '', (string) $vm->node_id), $vm->status,
                $vm->ip_address, $vm->ip_address_manual, $vm->os_name, $vm->os_name_manual, $vm->ip_os_source,
                $vm->maxcpu, round(((float) $vm->maxmem) / 1073741824, 2), round(((float) $vm->size) / 1073741824, 2), $vm->storageName,
                $vm->qga_enabled_config ? 'Sí' : 'No', $vm->infransible_status, $auditAt?->format('Y-m-d H:i:s'), $ageHours,
                $vm->infransible_note, $vm->zabbix_status_infransible ?: $vm->zabbix_status, $vm->zabbix_version_infransible ?: $vm->zabbix_version,
                $vm->wazuh_status_infransible ?: $vm->wazuh_status, $vm->wazuh_version_infransible ?: $vm->wazuh_version,
                $serviceText, $joinIps(['secondary']), $joinIps(['backup','veeam']), $joinIps(['vip']), $vm->updated_at,
            ];
        }
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');
            foreach ($rows as $row) fputcsv($handle, $row, ';');
            fclose($handle);
        }, 'qemus_export_completo_' . date('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

   # public function searchQemu(Request $request)
   # {
   #     $search = $request->get('search');
   #     $qemus = Qemu::where('vmid', 'like', "%{$search}%")
   #         ->orWhere('name', 'like', "%{$search}%")
	#    ->orWhere('cluster_name', 'like', "%{$search}%")
        #    ->orWhere('ip_address', 'LIKE', "%{$search}%")
        #    ->orWhere('node_id', 'like', "%{$search}%")
        #    ->paginate(1000);
      #  return view('proxmox.qemu', ['qemus' => $qemus]);
   #  }

    public function searchNode(Request $request)
    {
        $search = $request->get('search');

        // ¡AQUÍ ESTÁ LA CORRECCIÓN!
        // Cambiamos la consulta para que busque en las columnas correctas
        $nodes = Node::where('node', 'like', "%{$search}%")      // Buscar por nombre (ej: pve-regula01)
                     ->orWhere('ip', 'like', "%{$search}%")        // Buscar por IP (ej: 192.168.100.4)
                     ->orWhere('cluster_name', 'like', "%{$search}%") // Bonus: Buscar también por cluster
                     ->get(); // Cambiado de paginate(100) a get() para ser consistente con el método node()

        // El resto de la función es idéntica y calcula los discos
        $nodesIdProxmox = $nodes->pluck('id_proxmox')->toArray();
        $qemus = Qemu::whereIn('node_id', $nodesIdProxmox)->get();
        $storages = Storage::whereIn('node_id', $nodesIdProxmox)->get();

        $sizeSumByNodeId = [];
        $storageLocalMax = [];

        foreach ($storages as $storage) {
            $nodeId = $storage->node_id;
            $storageName = strtolower($storage->storage);
            if (!isset($storageLocalMax[$nodeId])) {
                $storageLocalMax[$nodeId] = 0;
            }
            if ($storageName == 'local' || $storageName == 'local-lvm') {
                $storageLocalMax[$nodeId] += $storage->maxdisk;
            }
        }

        foreach ($qemus as $qemu) {
            $nodeId = $qemu->node_id;
            $size = $qemu->size;
            if (!isset($sizeSumByNodeId[$nodeId])) {
                $sizeSumByNodeId[$nodeId] = 0;
            }
            $sizeSumByNodeId[$nodeId] += $size;
        }

        return view('proxmox.node', [
            'nodes' => $nodes,
            'qemus' => $qemus,
            'storages' => $storages,
            'storageLocal' => $sizeSumByNodeId,
            'storageLocalMax' => $storageLocalMax
        ]);
    }

    public function searchStorage(Request $request)
    {
        $search = $request->get('search');
        $storages = Storage::where('storage', 'like', '%' . $search . '%')->paginate(100);
        $uniqueNames = [];
        $filteredStorages = [];
        $totalUsedDisk = 0;
        $totalMaxDisk = 0;
        foreach ($storages as $storage) {
            if (!in_array($storage->storage, $uniqueNames)) {
                $uniqueNames[] = $storage->storage;
                if ($storage->storage != 'local' && $storage->storage != 'local-lvm' && $storage->storage != 'Backup' && $storage->storage != 'Backup-Vicidial' || $storage->cluster == null) {
                    $filteredStorages[] = $storage;
                    if($storage->storage != 'Backup-Virt'){
                        $totalUsedDisk += $storage->disk;
                        $totalMaxDisk += $storage->maxdisk;
                    }
                }
            }
        }
        return view('proxmox.storage', ['storages' => $storages, 'filteredStorages' => $filteredStorages, 'totalUsedDisk' => $totalUsedDisk, 'totalMaxDisk' => $totalMaxDisk]);
    }

    public function showByIdNode($node)
    {
        try {
            $nodeIdProxmox = 'node/' . $node;
            $nodes = node::where('id_proxmox', $nodeIdProxmox)->get();
            $qemus = Qemu::where('node_id', $nodeIdProxmox)->get();
            $storages = Storage::where('node_id', $nodeIdProxmox)->get();

            $sizeSumByNodeId = [];
            $storageLocalMax = [];

            foreach ($storages as $storage) {
                $nodeId = $storage->node_id;
                $maxdisk = $storage->maxdisk;
                $storageName = $storage->storage;
                if (!isset($storageLocalMax[$nodeId])) $storageLocalMax[$nodeId] = 0;
                if ($storageName != 'Backup-Virt') $storageLocalMax[$nodeId] += $maxdisk;
            }

            foreach ($qemus as $qemu) {
                $nodeId = $qemu->node_id;
                $size = $this->getSizeInBytes($qemu->size);
                if (!isset($sizeSumByNodeId[$nodeId])) $sizeSumByNodeId[$nodeId] = 0;
                $sizeSumByNodeId[$nodeId] += $size;
            }

            $uniqueNames = [];
            $filteredStorages = [];
            foreach ($storages as $storage) {
                if (!in_array($storage->storage, $uniqueNames)) {
                    $uniqueNames[] = $storage->storage;
                    if ($storage->storage != 'local' && $storage->storage != 'local-lvm' && $storage->storage != 'Backup' && $storage->storage != 'Backup-Vicidial' || $storage->cluster == null) {
                        $filteredStorages[] = $storage;
                    }
                }
            }

            return view('proxmox.node.show', [
                'nodes' => $nodes, 'qemus' => $qemus, 'storages' => $filteredStorages,
                'storageLocal' => $sizeSumByNodeId, 'storageLocalMax' => $storageLocalMax
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->view('error', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Exporta solo las VMs apagadas a un CSV.
     */
    public function exportQemuStopped()
    {
        try {
            $stoppedQemus = \App\Models\qemu::where('status', '!=', 'running')
                                ->orderBy('cluster_name')
                                ->orderBy('name')
                                ->get();

            // Campos para el CSV de apagadas
            $fields = [
                'vmid',
                'name',
                'cluster_name',
                'node_id',
                'status',
                'maxmem',
                'size'
            ];

            $csvExporter = new \Laracsv\Export();
            return $csvExporter->build($stoppedQemus, $fields)->download('qemus_apagadas.csv');

        } catch (\Exception $e) {
            Log::error("Error al exportar VMs apagadas: " . $e->getMessage());
            return redirect()->back()->with('error', 'No se pudo generar el archivo de exportación.');
        }
    }

    /**
 * Sincroniza el inventario de hardware (DMI) vía SSH.
 * Esta es una función lenta y debe ser ejecutada manualmente.
 */
// En App\Http\Controllers\ProxmoxController.php

public function syncHardwareInventory(Request $request, $nodeId = null)
{
    set_time_limit(900);

    $runLogger = new \App\Services\SshSyncRunLogger();
    $runLogger->add('info', 'Iniciando sincronización de hardware e inventario de red vía SSH');
    Log::info("SSH SYNC {$runLogger->id()}: Iniciando sincronización de hardware e inventario de red vía SSH");

    if (!env('NODE_SSH_USER') || !env('NODE_SSH_PASS')) {
        $runLogger->add('error', 'Credenciales SSH faltantes en .env');
        Log::error("SSH SYNC {$runLogger->id()}: Credenciales SSH faltantes en .env");
        return redirect()->back()->with('error', 'Credenciales SSH faltantes.');
    }

    try {
        $ssh_user = env('NODE_SSH_USER');
        $ssh_pass = \Illuminate\Support\Facades\Crypt::decrypt(env('NODE_SSH_PASS'));
    } catch (\Exception $e) {
        $runLogger->add('error', 'Error desencriptando password SSH', ['error' => $e->getMessage()]);
        Log::error("SSH SYNC {$runLogger->id()}: Error desencriptando password SSH: " . $e->getMessage());
        return redirect()->back()->with('error', 'Error password SSH.');
    }

    $singleNode = null;
    if ($nodeId !== null) {
        $singleNode = \App\Models\node::where('id', $nodeId)->whereNotNull('ip')->first();
        if (!$singleNode) {
            $message = 'Nodo no encontrado o sin IP de gestión.';
            $runLogger->add('error', $message, ['node_id' => $nodeId]);
            if ($request->ajax() || $request->wantsJson()) return response()->json(['success'=>false,'run_id'=>$runLogger->id(),'message'=>$message,'events'=>$runLogger->summary()['events']], 404);
            return redirect()->back()->with('error', $message);
        }
        if ((bool) $singleNode->skip_ssh_sync) {
            $message = "El nodo {$singleNode->node} está marcado como carga manual/offline; no se ejecuta SSH para preservar datos.";
            $runLogger->add('warning', $message, ['node' => $singleNode->node, 'ip' => $singleNode->ip]);
            if ($request->ajax() || $request->wantsJson()) return response()->json(['success'=>false,'run_id'=>$runLogger->id(),'nodesUpdated'=>0,'nodesSkipped'=>1,'nodesFailed'=>0,'totalNodes'=>1,'message'=>$message,'events'=>$runLogger->summary()['events'],'log_path'=>'storage/app/ssh-sync-runs/sync-'.$runLogger->id().'.json']);
            return redirect()->back()->with('error', $message);
        }
        $nodes = collect([$singleNode]);
        $excludedNodes = collect();
    } else {
        $nodes = \App\Models\node::whereNotNull('ip')
            ->where(function ($query) {
                $query->whereNull('skip_ssh_sync')->orWhere('skip_ssh_sync', false);
            })
            ->get();
        $excludedNodes = \App\Models\node::whereNotNull('ip')->where('skip_ssh_sync', true)->get(['id', 'node', 'ip']);
    }
    $runLogger->add('info', $singleNode ? 'Nodo seleccionado para procesar' : 'Nodos encontrados para procesar', [
        'total' => $nodes->count(),
        'node' => $singleNode->node ?? null,
        'excluded_manual_offline' => $excludedNodes->count(),
        'excluded_nodes' => $excludedNodes->pluck('node')->values()->all(),
    ]);

    $nodesUpdated = 0;
    $nodesSkipped = 0;
    $nodesFailed = 0;

    $badValue = function ($value): bool {
        $value = trim((string) $value);
        if ($value === '') return true;
        foreach (['command not found','sudo:','permission denied','no such file','operation not permitted','authentication failed','not specified','to be filled by o.e.m'] as $pattern) {
            if (stripos($value, $pattern) !== false) return true;
        }
        return false;
    };

    $cleanValue = function ($value) use ($badValue) {
        $value = trim((string) $value);
        return $badValue($value) ? null : $value;
    };

    foreach ($nodes as $node) {
        try {
            $runLogger->add('info', "Procesando nodo {$node->node}", ['ip' => $node->ip]);
            Log::info("SSH SYNC {$runLogger->id()}: Procesando nodo {$node->node} ({$node->ip})");

            $ssh = new \phpseclib3\Net\SSH2($node->ip);
            $ssh->setTimeout(18);
            if (!$ssh->login($ssh_user, $ssh_pass)) {
                $nodesSkipped++;
                $runLogger->add('warning', 'Sin acceso SSH: se preserva data existente/manual', ['node' => $node->node, 'ip' => $node->ip]);
                Log::warning("SSH SYNC {$runLogger->id()}: No se pudo autenticar en nodo {$node->node} ({$node->ip}); se preserva data existente/manual");
                continue;
            }

            $run = function (string $cmd) use ($ssh): string {
                return trim((string) $ssh->exec('bash -lc ' . escapeshellarg($cmd)));
            };
            $runPriv = function (string $cmd) use ($run): string {
                return trim($run("if command -v sudo >/dev/null 2>&1; then sudo -n {$cmd} 2>/dev/null && exit 0; fi; {$cmd} 2>/dev/null"));
            };

            $serial = $cleanValue($runPriv('dmidecode -s system-serial-number'));
            $modelRaw = $cleanValue($runPriv('dmidecode -s system-product-name'));
            $brand = $cleanValue($runPriv('dmidecode -s system-manufacturer'));
            $firmwareRaw = $cleanValue($runPriv('dmidecode -s bios-version'));
            $pveRaw = $cleanValue($run("pveversion -v 2>/dev/null | awk '/pve-manager/ {print $2; exit}'"));
            $kernel = $cleanValue($run('uname -r'));
            $model = $modelRaw;
            if ($modelRaw && preg_match('/^(.*?) -\[(.*?)]-$/', $modelRaw, $m)) $model = trim($m[1]) . ' (' . $m[2] . ')';
            $firmware = $firmwareRaw;
            if ($firmwareRaw && preg_match('/^-\[(.*?)-(.*?)\]-$/', $firmwareRaw, $m)) $firmware = "UEFI: {$m[2]} (Build: {$m[1]})";

            $nodeUpdates = [];
            foreach (['serial_number'=>$serial,'brand'=>$brand,'model'=>$model,'firmware_version'=>$firmware,'pve_version'=>$pveRaw,'kernel_version'=>$kernel] as $field => $value) {
                if (!$badValue($value)) $nodeUpdates[$field] = $value;
            }
            if ($nodeUpdates) {
                $node->update($nodeUpdates);
                $runLogger->add('info', 'Hardware actualizado', ['node' => $node->node, 'fields' => array_keys($nodeUpdates)]);
            } else {
                $runLogger->add('warning', 'Hardware sin datos válidos; se preserva data anterior/manual', ['node' => $node->node]);
            }

            $netConfigContent = $run('cat /etc/network/interfaces 2>/dev/null; cat /etc/network/interfaces.d/* 2>/dev/null');
            $commentsMap = [];
            $currentContextIface = null;
            foreach (explode("\n", $netConfigContent) as $line) {
                $line = trim($line);
                if ($line === '') continue;
                if (preg_match('/^(?:iface|auto)\s+([a-zA-Z0-9\.\-_]+)/', $line, $matches)) $currentContextIface = $matches[1];
                if ($currentContextIface && str_contains($line, '#')) {
                    $commentText = trim(explode('#', $line, 2)[1] ?? '');
                    if ($commentText !== '' && !str_contains(strtolower($commentText), 'autogenerated')) $commentsMap[$currentContextIface] = $commentText;
                }
            }

            $netJson = $run('ip -j -d addr show 2>/dev/null');
            $interfaces = json_decode($netJson, true);
            if (!is_array($interfaces)) {
                $nodesSkipped++;
                $runLogger->add('warning', 'No se pudo leer ip -j; se preservan interfaces existentes/manuales', ['node' => $node->node]);
                continue;
            }

            $seenIfaces = [];
            $runLogger->add('info', 'Procesando interfaces', ['node' => $node->node, 'count' => count($interfaces)]);
            foreach ($interfaces as $iface) {
                $ifName = $iface['ifname'] ?? null;
                if (!$ifName || $ifName === 'lo') continue;
                $seenIfaces[] = $ifName;
                $type = 'eth';
                if (str_starts_with($ifName, 'vmbr')) $type = 'bridge';
                elseif (str_starts_with($ifName, 'bond')) $type = 'bond';
                elseif (str_contains($ifName, '.')) $type = 'vlan';

                $mac = $iface['address'] ?? null;
                if (!empty($iface['permaddr'])) $mac = $iface['permaddr'];
                $existing = \App\Models\NodeInterface::where('node_id', $node->id)->where('iface', $ifName)->first();
                $mediaType = null; $linkSpeed = null; $duplex = null; $driver = null; $busInfo = null; $ifaceFirmware = null; $linkDetected = null;
                $isTapLike = preg_match('/^(tap|veth|fwpr|fwln|fwbr)/', $ifName);
                $isVirtualLike = $isTapLike || $type === 'bridge' || $type === 'bond' || $type === 'vlan';
                if ($type === 'bridge') { $mediaType = 'Virtual/Lógico'; $driver = 'bridge'; }
                elseif ($type === 'bond') { $mediaType = 'Virtual/Lógico'; $driver = 'bonding'; }
                elseif ($type === 'vlan') { $mediaType = 'Virtual/Lógico'; $driver = '802.1Q VLAN Support'; }
                elseif ($isTapLike) { $mediaType = 'Virtual'; }

                $safeIf = escapeshellarg($ifName);
                $sysSpeed = $cleanValue($run("cat /sys/class/net/{$safeIf}/speed 2>/dev/null"));
                if ($sysSpeed !== null && is_numeric($sysSpeed) && (int)$sysSpeed > 0) $linkSpeed = $sysSpeed . 'Mb/s';
                $sysDuplex = $cleanValue($run("cat /sys/class/net/{$safeIf}/duplex 2>/dev/null"));
                if ($sysDuplex && in_array(strtolower($sysDuplex), ['full','half'], true)) $duplex = ucfirst(strtolower($sysDuplex));
                if (!$isTapLike) {
                    $ethtoolRaw = $runPriv("ethtool {$safeIf}");
                    if ($ethtoolRaw !== '' && !$badValue($ethtoolRaw)) {
                        if (preg_match('/Speed:\s*([^\n]+)/', $ethtoolRaw, $m) && stripos($m[1], 'Unknown') === false) $linkSpeed = trim($m[1]);
                        if (preg_match('/Duplex:\s*([^\n]+)/', $ethtoolRaw, $m) && stripos($m[1], 'Unknown') === false) $duplex = trim($m[1]);
                        if (preg_match('/Link detected:\s*([^\n]+)/', $ethtoolRaw, $m)) $linkDetected = trim($m[1]);
                        if (!$isVirtualLike) {
                            if (preg_match('/Port:\s*Twisted Pair/i', $ethtoolRaw) || preg_match('/Supported ports:\s*\[\s*TP\s*\]/i', $ethtoolRaw)) $mediaType = 'UTP/RJ45';
                            elseif (preg_match('/Port:\s*FIBRE/i', $ethtoolRaw) || preg_match('/Supported ports:\s*\[\s*FIBRE\s*\]/i', $ethtoolRaw)) $mediaType = 'Fibra/SFP';
                        }
                    }
                    if (!$isVirtualLike) {
                        $moduleRaw = $runPriv("ethtool -m {$safeIf} | head -40");
                        if ($moduleRaw !== '' && !$badValue($moduleRaw) && stripos($moduleRaw, 'operation not supported') === false) $mediaType = 'Fibra/SFP';
                    }
                    $driverRaw = $runPriv("ethtool -i {$safeIf}");
                    if ($driverRaw !== '' && !$badValue($driverRaw)) {
                        if (preg_match('/^driver:\s*(.+)$/m', $driverRaw, $m)) $driver = trim($m[1]);
                        if (preg_match('/^firmware-version:\s*(.+)$/m', $driverRaw, $m)) { $fw=trim($m[1]); if ($fw !== '' && strtoupper($fw) !== 'N/A') $ifaceFirmware=$fw; }
                        if (preg_match('/^bus-info:\s*(.+)$/m', $driverRaw, $m)) { $bus=trim($m[1]); if ($bus !== '' && strtoupper($bus) !== 'N/A' && strtolower($bus) !== 'tap') $busInfo=$bus; }
                    }
                }
                if ($mediaType === null && !$isVirtualLike && $existing && $existing->media_type) $mediaType = $existing->media_type;
                if ($linkSpeed === null && $existing && $existing->link_speed) $linkSpeed = $existing->link_speed;
                if ($duplex === null && $existing && $existing->duplex) $duplex = $existing->duplex;
                if ($driver === null && $existing && $existing->driver) $driver = $existing->driver;
                if ($busInfo === null && $existing && $existing->bus_info) $busInfo = $existing->bus_info;
                if ($ifaceFirmware === null && $existing && $existing->firmware) $ifaceFirmware = $existing->firmware;
                if ($linkDetected === null && $existing && $existing->link_detected) $linkDetected = $existing->link_detected;
                if ($mediaType === null) $mediaType = $isVirtualLike ? 'Virtual/Lógico' : 'Desconocido';

                $ip = null; $cidr = null;
                if (isset($iface['addr_info']) && is_array($iface['addr_info'])) foreach ($iface['addr_info'] as $addr) if (($addr['family'] ?? null) === 'inet') { $ip=$addr['local'] ?? null; $cidr=$addr['prefixlen'] ?? null; break; }
                $ports = null;
                if ($type === 'bridge') {
                    $brPortsRaw = $run("ls /sys/class/net/{$safeIf}/brif/ 2>/dev/null");
                    if ($brPortsRaw !== '' && !$badValue($brPortsRaw)) {
                        $cleanPorts=[]; foreach (preg_split('/\s+/', trim($brPortsRaw)) as $p) if ($p !== '' && !preg_match('/^(tap|veth|fwpr|fwln)/', $p)) $cleanPorts[]=$p;
                        if ($cleanPorts) $ports=implode(' ', $cleanPorts);
                    }
                } elseif ($type === 'bond') {
                    $bondSlaves = $run("cat /sys/class/net/{$safeIf}/bonding/slaves 2>/dev/null");
                    if ($bondSlaves !== '' && !$badValue($bondSlaves)) $ports = trim($bondSlaves);
                }
                $isActive = is_array($iface['flags'] ?? null) ? in_array('UP', $iface['flags']) : str_contains((string)($iface['flags'] ?? ''), 'UP');
                $finalComment = $commentsMap[$ifName] ?? ($existing->comments ?? null);
                \App\Models\NodeInterface::updateOrCreate(['node_id'=>$node->id,'iface'=>$ifName], ['type'=>$type,'media_type'=>$mediaType,'link_speed'=>$linkSpeed,'duplex'=>$duplex,'driver'=>$driver,'bus_info'=>$busInfo,'firmware'=>$ifaceFirmware,'link_detected'=>$linkDetected,'address'=>$ip,'cidr'=>$cidr,'mac'=>$mac,'active'=>$isActive,'ports'=>$ports,'comments'=>$finalComment,'gateway'=>$existing->gateway ?? null]);
            }

            $bridges = \App\Models\NodeInterface::where('node_id', $node->id)->where('type', 'bridge')->get();
            foreach ($bridges as $br) {
                if (empty($br->ports)) continue;
                foreach (preg_split('/\s+/', $br->ports) as $portName) {
                    if ($portName === '') continue;
                    $cleanName = preg_replace('/\.\d+$/', '', $portName);
                    $child = \App\Models\NodeInterface::where('node_id', $node->id)->where('iface', $cleanName)->first();
                    if ($child) {
                        $currentParents = $child->bridge_parent;
                        if (!str_contains((string)$currentParents, $br->iface)) $child->update(['bridge_parent' => empty($currentParents) ? $br->iface : $currentParents . ',' . $br->iface]);
                    }
                }
            }
            $bonds = \App\Models\NodeInterface::where('node_id', $node->id)->where('type', 'bond')->get();
            foreach ($bonds as $bond) {
                if (empty($bond->ports)) continue;
                foreach (preg_split('/\s+/', $bond->ports) as $slaveName) {
                    if ($slaveName === '') continue;
                    $child = \App\Models\NodeInterface::where('node_id', $node->id)->where('iface', $slaveName)->first();
                    if ($child) $child->update(['bond_parent'=>$bond->iface, 'bridge_parent'=>$bond->bridge_parent ?: $child->bridge_parent]);
                }
            }
            $nodesUpdated++;
            $runLogger->add('success', 'Nodo procesado exitosamente', ['node' => $node->node, 'interfaces' => count($seenIfaces)]);
            Log::info("SSH SYNC {$runLogger->id()}: Nodo {$node->node} procesado exitosamente");
        } catch (\Exception $e) {
            $nodesFailed++;
            $runLogger->add('error', 'Error procesando nodo', ['node' => $node->node, 'ip' => $node->ip, 'error' => $e->getMessage()]);
            Log::error("SSH SYNC {$runLogger->id()}: Error en nodo {$node->node}: " . $e->getMessage());
        }
    }

    $message = ($singleNode ? "Sincronización de {$singleNode->node} completada" : "Sincronización completada") . ": {$nodesUpdated} actualizados, {$nodesSkipped} preservados/sin SSH, {$nodesFailed} fallidos";
    $runLogger->add('info', $message, ['updated'=>$nodesUpdated,'skipped'=>$nodesSkipped,'failed'=>$nodesFailed,'total'=>$nodes->count()]);
    Log::info("SSH SYNC {$runLogger->id()}: {$message}");
    if (request()->ajax() || request()->wantsJson()) return response()->json(['success'=>$nodesFailed===0,'run_id'=>$runLogger->id(),'nodesUpdated'=>$nodesUpdated,'nodesSkipped'=>$nodesSkipped,'nodesFailed'=>$nodesFailed,'totalNodes'=>$nodes->count(),'message'=>$message,'events'=>$runLogger->summary()['events'],'log_path'=>'storage/app/ssh-sync-runs/sync-'.$runLogger->id().'.json']);
    return redirect()->back()->with('success', $message);
}


public function updateManualInventory(Request $request, $id)
{
    $node = \App\Models\node::findOrFail($id);
    $data = $request->validate([
        'brand' => 'nullable|string|max:255',
        'model' => 'nullable|string|max:255',
        'serial_number' => 'nullable|string|max:255',
        'firmware_version' => 'nullable|string|max:255',
        'pve_version' => 'nullable|string|max:255',
        'kernel_version' => 'nullable|string|max:255',
        'skip_ssh_sync' => 'nullable|boolean',
        'inventory_txt' => 'nullable|file|mimes:txt,log,text|max:2048',
    ]);

    $updates = [];
    foreach (['brand','model','serial_number','firmware_version','pve_version','kernel_version'] as $field) {
        if ($request->filled($field)) $updates[$field] = trim((string) $request->input($field));
    }
    $updates['skip_ssh_sync'] = $request->boolean('skip_ssh_sync');

    $txtImported = false;
    $interfacesImported = 0;
    if ($request->hasFile('inventory_txt')) {
        $content = file_get_contents($request->file('inventory_txt')->getRealPath());
        $parsed = $this->parseOfflineInventoryTxt($content);
        foreach (['brand','model','serial_number','firmware_version','pve_version','kernel_version'] as $field) {
            if (!empty($parsed['node'][$field])) $updates[$field] = $parsed['node'][$field];
        }
        if (!empty($parsed['interfaces'])) {
            foreach ($parsed['interfaces'] as $iface => $row) {
                \App\Models\NodeInterface::updateOrCreate(['node_id' => $node->id, 'iface' => $iface], $row);
                $interfacesImported++;
            }
        }
        $txtImported = true;
        $updates['skip_ssh_sync'] = true;
    }

    $node->update($updates);
    $msg = $txtImported
        ? "Carga TXT aplicada a {$node->node}: {$interfacesImported} interfaces importadas."
        : "Datos manuales actualizados para {$node->node}.";
    return redirect()->back()->with('success', $msg);
}

private function parseOfflineInventoryTxt(string $content): array
{
    $node = [];
    $firstLine = function (string $pattern) use ($content): ?string {
        if (!preg_match($pattern, $content, $m)) return null;
        $value = trim((string)($m[1] ?? ''));
        $value = preg_replace('/\s+/', ' ', $value);
        return $value !== '' ? mb_substr($value, 0, 250) : null;
    };
    $firstSectionLine = function (string $section, string $label) use ($content): ?string {
        if (!preg_match('/^###\s+'.preg_quote($section, '/').'\s*$([\s\S]*?)(?=^###\s+|\z)/mi', $content, $sec)) return null;
        if (!preg_match('/^\s*'.preg_quote($label, '/').'\s*:\s*(.+)$/mi', $sec[1], $m)) return null;
        $value = trim($m[1]);
        $value = preg_replace('/\s+/', ' ', $value);
        return $value !== '' ? mb_substr($value, 0, 250) : null;
    };

    $node['brand'] = $firstSectionLine('HARDWARE', 'manufacturer')
        ?? $firstSectionLine('HOST', 'Hardware Vendor')
        ?? $firstLine('/^\s*Manufacturer:\s*([^\r\n]+)/mi');
    $node['model'] = $firstSectionLine('HARDWARE', 'product_name')
        ?? $firstSectionLine('HOST', 'Hardware Model')
        ?? $firstLine('/^\s*Product Name:\s*([^\r\n]+)/mi');
    $node['serial_number'] = $firstSectionLine('HARDWARE', 'serial_number')
        ?? $firstSectionLine('HOST', 'Hardware Serial')
        ?? $firstLine('/^\s*Serial Number:\s*([^\r\n]+)/mi');
    $node['firmware_version'] = $firstSectionLine('HARDWARE', 'bios_version')
        ?? $firstSectionLine('HOST', 'Firmware Version')
        ?? $firstLine('/^\s*BIOS Version:\s*([^\r\n]+)/mi');
    if (preg_match('/^pve-manager:\s*([^\s]+)/mi', $content, $m) || preg_match('/pve-manager\/([^\s]+)/i', $content, $m)) $node['pve_version'] = mb_substr(trim($m[1]), 0, 250);
    if (preg_match('/^###\s+KERNEL\s*$\s*(?:Linux\s+\S+\s+)?([^\s\r\n]+)/mi', $content, $m) || preg_match('/Kernel:\s*Linux\s+([^\s\r\n]+)/i', $content, $m)) $node['kernel_version'] = mb_substr(trim($m[1]), 0, 250);

    $interfaces = [];
    $pveNetwork = [];
    if (preg_match('/^###\s+PVE NETWORK JSON\s*$(.*?)(?=^###\s+|\z)/ms', $content, $m)) {
        $json = trim($m[1]);
        $decoded = json_decode($json, true);
        if (is_array($decoded)) foreach ($decoded as $item) if (!empty($item['iface'])) $pveNetwork[$item['iface']] = $item;
    }
    if (preg_match_all('/^=====\s*([^=\r\n]+?)\s*=====\s*$(.*?)(?=^=====|\z)/ms', $content, $blocks, PREG_SET_ORDER)) {
        foreach ($blocks as $block) {
            $iface = trim($block[1]);
            if ($iface === '' || $iface === 'lo' || $iface === 'bonding_masters') continue;
            $body = $block[2];
            $type = str_starts_with($iface, 'vmbr') ? 'bridge' : (str_starts_with($iface, 'bond') && !str_contains($iface, '.') ? 'bond' : (str_contains($iface, '.') ? 'vlan' : 'eth'));
            $isTap = preg_match('/^(tap|veth|fwpr|fwln|fwbr)/', $iface);
            $isVirtual = $isTap || $type !== 'eth';
            $mediaType = $isTap ? 'Virtual' : ($isVirtual ? 'Virtual/Lógico' : null);
            if (!$isVirtual) {
                if (preg_match('/Port:\s*Twisted Pair/i', $body) || preg_match('/Supported ports:\s*\[\s*TP\s*\]/i', $body)) $mediaType = 'UTP/RJ45';
                if (preg_match('/Port:\s*FIBRE/i', $body) || preg_match('/Supported ports:\s*\[\s*FIBRE\s*\]/i', $body) || preg_match('/sfp\/module:\s*[\r\n]+\s*(Identifier|Vendor name|Transceiver)/i', $body)) $mediaType = 'Fibra/SFP';
            }
            $row = ['type' => $type, 'media_type' => $mediaType ?: 'Desconocido'];
            if (preg_match('/^mac:\s*([0-9a-f]{2}(?::[0-9a-f]{2}){5})/im', $body, $m)) $row['mac'] = strtolower($m[1]);
            elseif (preg_match('/([0-9a-f]{2}(?::[0-9a-f]{2}){5})/i', $body, $m)) $row['mac'] = strtolower($m[1]);
            if (preg_match('/Speed:\s*([^\r\n]+)/i', $body, $m) && stripos($m[1], 'Unknown') === false) $row['link_speed'] = mb_substr(trim($m[1]), 0, 250);
            elseif (preg_match('/^speed:\s*([0-9]+)\s*$/im', $body, $m) && (int)$m[1] > 0) $row['link_speed'] = trim($m[1]).'Mb/s';
            if (preg_match('/Duplex:\s*([^\r\n]+)/i', $body, $m) && stripos($m[1], 'Unknown') === false) $row['duplex'] = mb_substr(trim($m[1]), 0, 250);
            elseif (preg_match('/^duplex:\s*(full|half)\s*$/im', $body, $m)) $row['duplex'] = ucfirst(strtolower($m[1]));
            if (preg_match('/Link detected:\s*([^\r\n]+)/i', $body, $m)) $row['link_detected'] = mb_substr(trim($m[1]), 0, 250);
            if (preg_match('/^driver:[ \t]*(.+)$/im', $body, $m)) {
                $driver = trim($m[1]);
                if ($driver !== '' && !str_starts_with($driver, '/sys/')) $row['driver'] = mb_substr($driver, 0, 250);
            }
            if (preg_match('/^firmware-version:[ \t]*(.+)$/im', $body, $m)) { $fw=trim($m[1]); if ($fw !== '' && strtoupper($fw) !== 'N/A') $row['firmware'] = mb_substr($fw,0,250); }
            if (preg_match('/^bus-info:[ \t]*(.+)$/im', $body, $m)) { $bus=trim($m[1]); if ($bus !== '' && strtoupper($bus) !== 'N/A' && strtolower($bus) !== 'tap') $row['bus_info'] = mb_substr($bus,0,250); }
            $row['active'] = preg_match('/^operstate:\s*up/im', $body) || preg_match('/Link detected:\s*yes/i', $body) ? 1 : 0;
            if (isset($pveNetwork[$iface])) {
                $item = $pveNetwork[$iface];
                if (!empty($item['address'])) $row['address'] = $item['address'];
                if (!empty($item['netmask'])) $row['cidr'] = $item['netmask'];
                elseif (!empty($item['cidr']) && preg_match('/\/(\d+)/', $item['cidr'], $cm)) $row['cidr'] = $cm[1];
                if (!empty($item['gateway'])) $row['gateway'] = $item['gateway'];
                if (!empty($item['comments'])) $row['comments'] = trim($item['comments']);
                if (!empty($item['bridge_ports'])) $row['ports'] = trim($item['bridge_ports']);
                if (!empty($item['slaves'])) $row['ports'] = trim($item['slaves']);
            }
            $interfaces[$iface] = $row;
        }
    }

    foreach ($interfaces as $ifaceName => $row) {
        if (($row['type'] ?? null) === 'bond' && !empty($row['ports'])) {
            foreach (preg_split('/\s+/', trim($row['ports'])) as $port) {
                if ($port !== '' && isset($interfaces[$port])) $interfaces[$port]['bond_parent'] = $ifaceName;
            }
        }
    }
    foreach ($interfaces as $ifaceName => $row) {
        if (($row['type'] ?? null) === 'bridge' && !empty($row['ports'])) {
            foreach (preg_split('/\s+/', trim($row['ports'])) as $port) {
                if ($port === '') continue;
                if (isset($interfaces[$port])) {
                    $current = $interfaces[$port]['bridge_parent'] ?? '';
                    $interfaces[$port]['bridge_parent'] = $current === '' ? $ifaceName : $current . ',' . $ifaceName;
                }
                $basePort = preg_replace('/\.\d+$/', '', $port);
                if ($basePort !== $port && isset($interfaces[$basePort])) {
                    $current = $interfaces[$basePort]['bridge_parent'] ?? '';
                    $interfaces[$basePort]['bridge_parent'] = $current === '' ? $ifaceName : $current . ',' . $ifaceName;
                }
            }
        }
    }

    return ['node' => array_filter($node, fn($v) => $v !== null && $v !== ''), 'interfaces' => $interfaces];
}

    public function globalSearch(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $type = $request->input('type', 'all');
        $status = trim((string) $request->input('status', ''));
        $like = '%' . $term . '%';
        $vmResults = collect(); $nodeResults = collect(); $clusterResults = collect(); $storageResults = collect();
        if ($term !== '' && in_array($type, ['all','vm'], true)) {
            $query = qemu::query()->where(function ($q) use ($like) {
                foreach (['vmid','name','cluster_name','ip_address','ip_address_manual','os_name','os_name_manual','node_id','storageName','zabbix_status','zabbix_version','wazuh_status','wazuh_version','zabbix_status_infransible','zabbix_version_infransible','wazuh_status_infransible','wazuh_version_infransible','infransible_services','infransible_status'] as $column) $q->orWhere($column,'like',$like);
            });
            if ($status !== '') $query->where('status',$status);
            $vmResults=$query->orderBy('cluster_name')->orderBy('name')->limit(60)->get();
        }
        if ($term !== '' && in_array($type, ['all','node'], true)) $nodeResults=node::query()->where(function($q)use($like){foreach(['cluster_name','node','ip','brand','model','serial_number','firmware_version','pve_version','kernel_version'] as $column)$q->orWhere($column,'like',$like);})->orderBy('cluster_name')->orderBy('node')->limit(60)->get();
        if ($term !== '' && in_array($type, ['all','cluster'], true)) $clusterResults=cluster::query()->where('name','like',$like)->orderBy('name')->limit(60)->get();
        if ($term !== '' && in_array($type, ['all','storage'], true)) $storageResults=Storage::query()->where(function($q)use($like){$q->where('storage','like',$like)->orWhere('cluster','like',$like)->orWhere('plugintype','like',$like)->orWhere('node_id','like',$like);})->orderBy('cluster')->orderBy('storage')->limit(60)->get();
        return view('proxmox.global-search', compact('term','type','status','vmResults','nodeResults','clusterResults','storageResults') + ['totalResults'=>$vmResults->count()+$nodeResults->count()+$clusterResults->count()+$storageResults->count()]);
    }


    public function exportGlobalSearch(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $type = $request->input('type', 'all');
        $status = trim((string) $request->input('status', ''));
        $like = '%' . $term . '%';
        $rows = collect();

        if ($term !== '' && in_array($type, ['all', 'vm'], true)) {
            $query = qemu::query()->where(function ($q) use ($like) {
                foreach (['vmid','name','cluster_name','ip_address','ip_address_manual','os_name','os_name_manual','node_id','storageName','zabbix_status','zabbix_version','wazuh_status','wazuh_version','zabbix_status_infransible','zabbix_version_infransible','wazuh_status_infransible','wazuh_version_infransible','infransible_services','infransible_status'] as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
            if ($status !== '') {
                $query->where('status', $status);
            }
            foreach ($query->orderBy('cluster_name')->orderBy('name')->limit(60)->get() as $vm) {
                $rows->push(['VM', $vm->name ?: 'VM sin nombre', $vm->vmid ?: 'S/D', $vm->cluster_name ?: 'Sin clúster', $vm->node_id ?: 'S/D', $vm->status ?: 'S/D', $vm->os_name ?: $vm->os_name_manual ?: 'OS S/D', $vm->ip_address ?: $vm->ip_address_manual ?: 'S/D', $vm->storageName ?: 'S/D']);
            }
        }

        if ($term !== '' && in_array($type, ['all', 'node'], true)) {
            $nodes = node::query()->where(function ($q) use ($like) {
                foreach (['cluster_name','node','ip','brand','model','serial_number','firmware_version','pve_version','kernel_version'] as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            })->orderBy('cluster_name')->orderBy('node')->limit(60)->get();
            foreach ($nodes as $node) {
                $rows->push(['Nodo', $node->node ?: 'S/D', 'S/D', $node->cluster_name ?: 'Sin clúster', $node->ip ?: 'S/D', $node->brand ?: 'S/D', $node->model ?: 'S/D', $node->serial_number ?: 'S/D', $node->firmware_version ?: 'S/D']);
            }
        }

        if ($term !== '' && in_array($type, ['all', 'cluster'], true)) {
            foreach (cluster::query()->where('name', 'like', $like)->orderBy('name')->limit(60)->get() as $cluster) {
                $rows->push(['Clúster', $cluster->name ?: 'S/D', 'S/D', $cluster->name ?: 'S/D', 'S/D', 'S/D', 'S/D', 'S/D', 'S/D']);
            }
        }

        if ($term !== '' && in_array($type, ['all', 'storage'], true)) {
            $storages = Storage::query()->where(function ($q) use ($like) {
                $q->where('storage', 'like', $like)->orWhere('cluster', 'like', $like)->orWhere('plugintype', 'like', $like)->orWhere('node_id', 'like', $like);
            })->orderBy('cluster')->orderBy('storage')->limit(60)->get();
            foreach ($storages as $storage) {
                $rows->push(['Storage', $storage->storage ?: 'S/D', 'S/D', $storage->cluster ?: 'Sin clúster', $storage->node_id ?: 'S/D', $storage->status ?: 'S/D', $storage->plugintype ?: 'S/D', 'S/D', 'S/D']);
            }
        }

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Tipo', 'Nombre', 'VMID', 'Clúster', 'Nodo/IP', 'Estado/Marca', 'Sistema/Modelo', 'IP/Serial', 'Storage/Firmware'], ';');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        }, 'busqueda_inventario_' . date('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function showInventory(Request $request)
    {
    // 1. Obtener el término de búsqueda desde la URL (ej: ?_q=Lenovo)
    $search = $request->input('_q');

    // 2. Empezar la consulta a la base de datos
    $query = \App\Models\node::query();

    // 3. Si hay un término de búsqueda, aplicar los filtros
    if ($search) {
        // Buscamos en todas las columnas que te interesan
        $query->where(function($q) use ($search) {
            $q->where('cluster_name', 'like', "%{$search}%")
              ->orWhere('node', 'like', "%{$search}%")
              ->orWhere('ip', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%")
              ->orWhere('serial_number', 'like', "%{$search}%")
              ->orWhere('firmware_version', 'like', "%{$search}%")
	      ->orWhere('pve_version', 'like', "%{$search}%")
              ->orWhere('kernel_version', 'like', "%{$search}%")
              ->orWhere('support_license_date', 'like', "%{$search}%");
	});
    }

    // 4. Ordenar y obtener los resultados (ya filtrados, si había búsqueda)
    $nodes = $query->with('interfaces')->orderBy('cluster_name')->orderBy('node')->get();

    // 5. Devolver la vista, pasando los nodos y también el término de búsqueda
    //    (para que la barra de búsqueda "recuerde" lo que buscaste)
    return view('proxmox.inventory', [
        'nodes' => $nodes,
        'searchQuery' => $search
    ]);
    }

    public function updateLicenseDate(Request $request, $id)
    {
        // 1. Validar que la fecha sea una fecha válida (o nula, si se borra)
        $request->validate([
            'support_license_date' => 'nullable|date',
        ]);

        try {
            // 2. Encontrar el nodo
            $node = \App\Models\node::findOrFail($id);

            // 3. Actualizar la fecha
            $node->support_license_date = $request->input('support_license_date');
            $node->save();

            // 4. Redirigir de vuelta con éxito
            return redirect()->route('proxmox.inventory')->with('success', 'Fecha de licencia actualizada para ' . $node->node);

        } catch (\Exception $e) {
            Log::error("Error actualizando licencia de nodo {$id}: " . $e->getMessage());
            return redirect()->route('proxmox.inventory')->with('error', 'No se pudo actualizar la fecha.');
        }
    }

    public function exportInventoryCSV()
    {
        try {
            $nodes = \App\Models\node::orderBy('cluster_name')->orderBy('node')->get();
            $headers = ['Cluster','Nodo','IP Gestión','Marca','Modelo','Serial Number','Firmware','PVE','Kernel','Soporte vence','Actualizado'];
            $rows = [];
            foreach ($nodes as $node) {
                $rows[] = [
                    $node->cluster_name ?? '',
                    $node->node ?? '',
                    $node->ip ?? '',
                    $node->brand ?? '',
                    $node->model ?? '',
                    $node->serial_number ?? '',
                    $node->firmware_version ?? '',
                    $node->pve_version ?? '',
                    $node->kernel_version ?? '',
                    $node->support_license_date ? \Carbon\Carbon::parse($node->support_license_date)->format('Y-m-d') : '',
                    $node->updated_at ? \Carbon\Carbon::parse($node->updated_at)->format('Y-m-d H:i:s') : '',
                ];
            }

            $tmp = tempnam(sys_get_temp_dir(), 'inventario_nodos_') . '.xlsx';
            $zip = new \ZipArchive();
            if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('No se pudo crear archivo XLSX temporal.');
            }

            $xmlEscape = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $colName = function ($index) {
                $name = '';
                while ($index >= 0) {
                    $name = chr($index % 26 + 65) . $name;
                    $index = intdiv($index, 26) - 1;
                }
                return $name;
            };
            $cell = function ($row, $col, $value, $style = 1) use ($colName, $xmlEscape) {
                $ref = $colName($col) . $row;
                return '<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t>'.$xmlEscape($value).'</t></is></c>';
            };

            $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<cols><col min="1" max="1" width="18" customWidth="1"/><col min="2" max="2" width="22" customWidth="1"/><col min="3" max="3" width="18" customWidth="1"/><col min="4" max="5" width="24" customWidth="1"/><col min="6" max="9" width="20" customWidth="1"/><col min="10" max="11" width="20" customWidth="1"/></cols>'
                . '<sheetData>';
            $sheet .= '<row r="1">';
            foreach ($headers as $i => $h) $sheet .= $cell(1, $i, $h, 2);
            $sheet .= '</row>';
            $r = 2;
            foreach ($rows as $row) {
                $sheet .= '<row r="'.$r.'">';
                foreach ($row as $i => $value) $sheet .= $cell($r, $i, $value, 1);
                $sheet .= '</row>';
                $r++;
            }
            $sheet .= '</sheetData><autoFilter ref="A1:K'.max(1, count($rows)+1).'"/><pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';

            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Inventario Nodos" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
            $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9E2F3"/></left><right style="thin"><color rgb="FFD9E2F3"/></right><top style="thin"><color rgb="FFD9E2F3"/></top><bottom style="thin"><color rgb="FFD9E2F3"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="49" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/><xf numFmtId="49" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
            $zip->close();

            return response()->download($tmp, 'inventario_nodos.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Error al exportar inventario de nodos XLSX: " . $e->getMessage());
            return redirect()->back()->with('error', 'No se pudo generar el archivo de exportación.');
        }
    }

    public function updateQemuIp(Request $request, $qemuId)
{
    // 1. Validar la IP y la OS manuales
    $request->validate([
        'ip_address' => 'nullable|string|max:64',
        'os_name' => 'nullable|string|max:255',
        'ssh_port' => 'nullable|integer|min:1|max:65535',
    ]);

    try {
        $qemu = qemu::findOrFail($qemuId);

        // 2. Actualizar set manual. Si la VM no tiene QGA activo, también se muestra como efectivo.
        $ip = trim((string) $request->input('ip_address'));
        $ip = preg_replace('#/\d+$#', '', $ip);
        $os = trim((string) $request->input('os_name'));
        $sshPort = (int) $request->input('ssh_port', 22);
        $qemu->ssh_port = $sshPort > 0 ? $sshPort : 22;
        $qemu->ip_address_manual = $ip ?: null;
        $qemu->os_name_manual = $os ?: null;
        $qemu->ip_address = $ip ?: null;
        $qemu->os_name = $os ?: null;
        $qemu->ip_os_source = 'manual';
        $qemu->manual_inventory_note = 'Carga manual desde interfaz';
        $qemu->manual_inventory_updated_at = now();
        $qemu->save();

        return redirect()->back()->with('success', 'IP, SO y puerto SSH de VM ' . $qemu->vmid . ' (' . $qemu->name . ') actualizados.');

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Error al actualizar IP/SO de VM {$qemuId}: " . $e->getMessage());
        return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage());
    }
}

    public function syncClusterName($name)
{
    // Aumentamos el tiempo de ejecución a 5 minutos por si el cluster es muy grande
    set_time_limit(300);

    try {
	Log::channel('proxmox_sync')->info("ROUTE HIT: Recibida petición para cluster: " . $name);
	// Al usar route() de Laravel, el parámetro llega ya decodificado,
        // pero por seguridad aplicamos urldecode por si acaso llega sucio.
        $decodedName = urldecode($name);

        Log::channel('proxmox_sync')->info("SYNC MANUAL: Iniciando actualización exclusiva para cluster: '{$decodedName}'");

        // 1. Llamamos al servicio pasando el nombre del cluster.
        // El 'true' activa QGA (Agent) para obtener IPs reales.
        $this->proxmoxService->processClusterNodes(true, $decodedName);

        // 2. Limpieza de VMs huérfanas
        // IMPORTANTE: Tu servicio ya maneja la lógica para borrar SOLO las del cluster procesado
        $this->proxmoxService->markMissingQemuAsDeleted();
        $this->proxmoxService->resetUpdatedQemuIds();

        // 3. (Opcional) Recalcular totales mensuales si es crítico tener el dato al segundo
        // $this->proxmoxService->MonthlyTotals();

        return redirect()->back()->with('success', "Cluster '{$decodedName}' sincronizado correctamente.");

    } catch (\Exception $e) {
        Log::channel('proxmox_sync')->error("Error sincronizando cluster {$name}: " . $e->getMessage());
        return redirect()->back()->with('error', "Error al sincronizar cluster: " . $e->getMessage());
    }
}

    public function destroyQemu()
    {
        try {
            QemuDeleted::truncate();
            return redirect()->route('proxmox.index');
        } catch (\Exception $e) {
            return response()->view('error', ['message' => $e->getMessage()], 500);
        }
    }
}

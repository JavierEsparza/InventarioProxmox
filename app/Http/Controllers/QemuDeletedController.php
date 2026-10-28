<?php

namespace App\Http\Controllers;

use App\Models\QemuDeleted;
use App\Models\node;
use Illuminate\Http\Request;
use Carbon\Carbon;

class QemuDeletedController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Histórico de VMs eliminadas.
     * Usa updated_at como fecha real de eliminación, porque created_at conserva la fecha
     * original del registro/VM copiada desde qemus.
     */
    public function index()
    {
        Carbon::setLocale('es');

        $nodeClusterMap = node::select('id_proxmox', 'cluster_name', 'node')
            ->get()
            ->keyBy('id_proxmox');

        $qemus = QemuDeleted::orderBy('updated_at', 'desc')->get()->map(function ($vm) use ($nodeClusterMap) {
            $nodeKey = $vm->node_id;
            $nodeInfo = $nodeClusterMap->get($nodeKey);
            $vm->node_name = str_replace('node/', '', (string) $nodeKey);
            $vm->vmid_clean = preg_match('/(\d+)$/', (string) $vm->id_proxmox, $matches) ? $matches[1] : null;
            $vm->cluster_name = $nodeInfo->cluster_name ?? 'Sin cluster / eliminado';
            $vm->deleted_month = Carbon::parse($vm->updated_at)->format('Y-m');
            return $vm;
        });

        $groupedDeleted = $qemus->groupBy('deleted_month');

        $monthlyStats = $groupedDeleted->map(function ($items, $month) {
            $topNode = $items->groupBy('node_name')->map->count()->sortDesc()->keys()->first();
            return (object) [
                'month' => $month,
                'label' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
                'count' => $items->count(),
                'clusters' => $items->pluck('cluster_name')->unique()->count(),
                'nodes' => $items->pluck('node_name')->unique()->count(),
                'cpu' => $items->sum('maxcpu'),
                'memory' => $items->sum('maxmem'),
                'disk' => $items->sum('maxdisk'),
                'top_node' => $topNode ?: 'S/D',
            ];
        })->values();

        $topDeletedNodes = $qemus->groupBy('node_name')->map(function ($items, $nodeName) {
            return (object) [
                'node' => $nodeName,
                'cluster' => $items->pluck('cluster_name')->filter()->first() ?: 'Sin cluster',
                'count' => $items->count(),
                'cpu' => $items->sum('maxcpu'),
                'memory' => $items->sum('maxmem'),
                'disk' => $items->sum('maxdisk'),
                'last_deleted_at' => $items->max('updated_at'),
            ];
        })->sortByDesc('count')->take(12)->values();

        $topDeletedClusters = $qemus->groupBy('cluster_name')->map(function ($items, $clusterName) {
            return (object) [
                'cluster' => $clusterName,
                'count' => $items->count(),
                'nodes' => $items->pluck('node_name')->unique()->count(),
                'cpu' => $items->sum('maxcpu'),
                'memory' => $items->sum('maxmem'),
                'disk' => $items->sum('maxdisk'),
            ];
        })->sortByDesc('count')->take(8)->values();

        $storageStats = $qemus->groupBy(function ($vm) {
            return $vm->storageName ?: 'Sin storage';
        })->map(function ($items, $storageName) {
            return (object) [
                'storage' => $storageName,
                'count' => $items->count(),
                'disk' => $items->sum('maxdisk'),
            ];
        })->sortByDesc('count')->take(8)->values();

        $statusStats = $qemus->groupBy(function ($vm) {
            return $vm->status ?: 'unknown';
        })->map->count()->sortDesc();

        $recentDeleted = $qemus->take(10);

        return view('proxmox.QemuDeleted.index', compact(
            'groupedDeleted',
            'monthlyStats',
            'topDeletedNodes',
            'topDeletedClusters',
            'storageStats',
            'statusStats',
            'recentDeleted',
            'qemus'
        ));
    }

    public function create() {}
    public function store(Request $request) {}
    public function show(QemuDeleted $qemuDeleted) {}
    public function edit(QemuDeleted $qemuDeleted) {}
    public function update(Request $request, QemuDeleted $qemuDeleted) {}
    public function destroy(QemuDeleted $qemuDeleted) {}
}

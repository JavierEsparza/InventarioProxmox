<?php

namespace App\Services\Proxmox;

use App\Models\VirtualMachineHistory;
use App\Models\MonthlyTotal;
use App\Models\cluster;
use App\Models\QemuDeleted;
use App\Models\NodeDeleted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function VMHistory()
    {
        try {
            $clusters = cluster::all();
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            foreach ($clusters as $cluster) {
                $qemus = \App\Models\qemu::where('cluster_name', $cluster->name)->get();
                $nodeIds = \App\Models\node::where('cluster_name', $cluster->name)->pluck('id_proxmox');

                $vmsCreated = \App\Models\qemu::where('cluster_name', $cluster->name)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();

                $vmsDeleted = QemuDeleted::whereIn('node_id', $nodeIds)
                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                    ->count();

                $nodesCreated = \App\Models\node::where('cluster_name', $cluster->name)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();

                $nodesDeleted = NodeDeleted::where('cluster_name', $cluster->name)
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

    public function markMissingQemuAsDeleted($processedNodes, $updatedQemuIds)
    {
        // 1. Si no procesamos ningún nodo, NO borramos nada (seguridad)
        if (empty($processedNodes)) {
            Log::warning("SYNC CLEANUP ABORTED: No se procesó ningún nodo correctamente.");
            return;
        }

        // 2. Buscamos VMs que estén en la DB, pertenezcan a nodos que SÍ revisamos,
        // pero que NO aparecieron en el escaneo actual.
        $qemusToDelete = \App\Models\qemu::whereIn('node_id', $processedNodes) // Solo en nodos revisados
                                         ->whereNotIn('id_proxmox', $updatedQemuIds) // Que no están en la lista viva
                                         ->get();

        if ($qemusToDelete->isEmpty()) return;

        Log::info("SYNC CLEANUP: Eliminando " . $qemusToDelete->count() . " VMs obsoletas.");

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

    public function markMissingNodeAsDeleted($processedNodes)
    {
        // 1. Si no procesamos ningún nodo, NO borramos nada (seguridad)
        if (empty($processedNodes)) {
            Log::warning("NODE CLEANUP ABORTED: No se procesó ningún nodo correctamente.");
            return;
        }

        // 2. Obtenemos todos los clusters activos
        $activeClusters = \App\Models\cluster::pluck('name')->toArray();

        // 3. Buscamos nodos que:
        //    - Pertenecen a clusters activos
        //    - NO aparecieron en el escaneo actual (no están en $processedNodes)
        $nodesToDelete = \App\Models\node::whereIn('cluster_name', $activeClusters)
                                         ->whereNotIn('id_proxmox', $processedNodes)
                                         ->get();

        if ($nodesToDelete->isEmpty()) return;

        Log::info("NODE CLEANUP: Eliminando " . $nodesToDelete->count() . " nodos obsoletos.");

        foreach($nodesToDelete as $node) {
            // Guardamos respaldo en NodeDeleted
            $data = $node->toArray();
            unset($data['id']); // Removemos el id autoincremental

            NodeDeleted::updateOrCreate(
                ['id_proxmox' => $node->id_proxmox],
                $data
            );
        }

        // Borramos solo los identificados
        $idsToDelete = $nodesToDelete->pluck('id_proxmox');
        \App\Models\node::whereIn('id_proxmox', $idsToDelete)->delete();
    }
}

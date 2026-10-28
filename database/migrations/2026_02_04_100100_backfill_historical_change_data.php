<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Este script recalcula los datos históricos desde noviembre 2025
     */
    public function up(): void
    {
        // Obtenemos todos los registros históricos existentes
        $histories = DB::table('virtual_machine_histories')->get();

        foreach ($histories as $history) {
            $date = Carbon::parse($history->date);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            $clusterName = $history->cluster_name;

            // Calcular VMs creadas ese mes
            $vmsCreated = DB::table('qemus')
                ->where('cluster_name', $clusterName)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Calcular VMs eliminadas ese mes
            // Primero obtenemos los IDs de nodos de este cluster
            $nodeIds = DB::table('nodes')
                ->where('cluster_name', $clusterName)
                ->pluck('id_proxmox');

            $vmsDeleted = DB::table('qemu_deleteds')
                ->whereIn('node_id', $nodeIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Calcular nodos creados ese mes
            $nodesCreated = DB::table('nodes')
                ->where('cluster_name', $clusterName)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Nodos eliminados = 0 (no hay registro histórico previo)
            $nodesDeleted = 0;

            // Actualizar el registro
            DB::table('virtual_machine_histories')
                ->where('id', $history->id)
                ->update([
                    'vms_created' => $vmsCreated,
                    'vms_deleted' => $vmsDeleted,
                    'nodes_created' => $nodesCreated,
                    'nodes_deleted' => $nodesDeleted,
                    'updated_at' => now()
                ]);

            echo "✓ Actualizado: {$clusterName} - {$date->format('Y-m')} (VMs: +{$vmsCreated}/-{$vmsDeleted}, Nodos: +{$nodesCreated}/-{$nodesDeleted})\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Resetear las columnas a 0
        DB::table('virtual_machine_histories')
            ->update([
                'vms_created' => 0,
                'vms_deleted' => 0,
                'nodes_created' => 0,
                'nodes_deleted' => 0
            ]);
    }
};

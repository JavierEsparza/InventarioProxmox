<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProxmoxService2;
use App\Models\cluster;
use Illuminate\Support\Facades\Log;

class ProxmoxSyncDaily extends Command
{
    /**
     * El nombre y la firma del comando.
     */
    protected $signature = 'proxmox:sync-daily';

    /**
     * La descripción.
     */
    protected $description = 'Sincroniza todos los clusters de Proxmox secuencialmente para evitar sobrecarga.';

    /**
     * Ejecución del comando.
     */
    public function handle(ProxmoxService2 $proxmoxService)
    {
        $this->info("Iniciando sincronización diaria programada...");
        Log::info("CRON: Iniciando sincronización diaria.");

        // Obtenemos todos los nombres de clusters
        $clusters = cluster::pluck('name');

        if ($clusters->isEmpty()) {
            $this->error("No hay clusters registrados.");
            return;
        }

        $bar = $this->output->createProgressBar(count($clusters));
        $bar->start();

        foreach ($clusters as $clusterName) {
            $this->info("\nProcesando cluster: {$clusterName}");

            try {
                // 1. Sincronizar Cluster Específico (Con QGA activado = true)
                // Usamos el método que ya arreglamos para filtrar por nombre
                $proxmoxService->processClusterNodes(true, $clusterName);

                // 2. Limpieza (Solo afecta al cluster actual)
                $proxmoxService->markMissingQemuAsDeleted();
                $proxmoxService->resetUpdatedQemuIds();

                // 3. Pausa de seguridad (5 segundos) para "enfriar" la conexión y la DB
                sleep(5);

            } catch (\Exception $e) {
                $this->error("Error en cluster {$clusterName}: " . $e->getMessage());
                Log::error("CRON ERROR en {$clusterName}: " . $e->getMessage());
            }

            $bar->advance();
        }

        // Al final, regeneramos los históricos globales si lo necesitas
        try {
            $this->info("\nGenerando históricos y totales...");
            $proxmoxService->VMHistory();
            $proxmoxService->MonthlyTotals();
        } catch (\Exception $e) {
            Log::error("CRON ERROR Generando históricos: " . $e->getMessage());
        }

        $bar->finish();
        $this->info("\n\nSincronización diaria completada exitosamente.");
        Log::info("CRON: Sincronización diaria finalizada.");
    }
}

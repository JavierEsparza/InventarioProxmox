<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\qemu;
use Illuminate\Support\Facades\DB;

class ImportVmIpsCommand extends Command
{
    /**
     * El nombre y la firma del comando Artisan.
     * Uso: php artisan proxmox:import-ips [ruta/a/archivo.csv]
     *
     * @var string
     */
    protected $signature = 'proxmox:import-ips {file}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Importa IPs de VMs desde un archivo CSV y las cruza con la tabla qemus usando VMID y Cluster.';

    /**
     * Ejecuta el comando Artisan.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado en: {$filePath}");
            return Command::FAILURE;
        }

        $file = fopen($filePath, 'r');
        // Asumimos que la primera fila es el encabezado
        $headers = fgetcsv($file, 1000, ',');

        // --- Búsqueda de Encabezados ---
        // Identificamos la columna exacta por su nombre (cluster_name, vmid, ip_address)
        $clusterCol = array_search('cluster_name', $headers);
        $vmidCol = array_search('vmid', $headers);
        $ipCol = array_search('ip_address', $headers);

        if ($clusterCol === false || $vmidCol === false || $ipCol === false) {
            $this->error("El CSV debe contener las columnas: cluster_name, vmid, ip_address.");
            return Command::FAILURE;
        }

        $updatedCount = 0;
        $failedCount = 0;

        // Utilizamos la barra de progreso de Artisan
        // No podemos usar filesize($filePath) para la barra de progreso sin leer el archivo dos veces,
        // así que la omitimos o usamos un conteo de líneas aproximado si fuera necesario.
        // Aquí solo la usaremos de forma simple para que se vea el proceso.
        $this->info("Iniciando importación...");

        // Iniciar transacción para seguridad (esto asegura que si algo falla, no se guarden cambios parciales)
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($file, 1000, ',')) !== FALSE) {
                // Saltar filas vacías
                if (empty(array_filter($row))) continue;

                // Mapeo y limpieza de datos
                $vmid = trim($row[$vmidCol]);
                $clusterName = trim($row[$clusterCol]);
                $ipAddress = trim($row[$ipCol]);

                // 1. CRUCE: Encontrar y actualizar el registro
                $updated = qemu::where('vmid', $vmid)
                               ->where('cluster_name', $clusterName)
                               ->update(['ip_address' => $ipAddress]);

                if ($updated) {
                    $updatedCount++;
                } else {
                    $this->warn("Advertencia: No se encontró VM con ID {$vmid} en el cluster {$clusterName}.");
                    $failedCount++;
                }
            }

            DB::commit(); // Confirmar todos los cambios
            $this->info("¡Importación completa!");
            $this->info("Registros actualizados: {$updatedCount}");
            $this->warn("Registros fallidos/no encontrados: {$failedCount}");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollback(); // Deshacer cambios en caso de error
            $this->error("Error fatal durante la importación: " . $e->getMessage());
            return Command::FAILURE;
        } finally {
            // Asegurarse de cerrar el archivo
            if (isset($file)) {
                fclose($file);
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTotal;
use App\Models\VirtualMachineHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VirtualMachineHistoryController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        // Quitamos el 'can:view cluster' de aquí, ya que el historial es global
        // Deberías añadirlo en las rutas (web.php) si es necesario
    }

    /**
     * Muestra el historial mensual de las máquinas virtuales.
     * Versión LIMPcompreia y CORREGIDA.
     */
    public function indexMonthly()
    {
        // 1. Obtenemos el historial detallado (para el acordeón)
        $VMHistory = VirtualMachineHistory::all();

        // 2. Obtenemos los totales mensuales (para el gráfico)
        $histories = VirtualMachineHistory::selectRaw('date, SUM(cluster_qemus) as cluster_qemus')
            ->groupBy('date')->get();

        // 3. Obtenemos el último total registrado (para las tarjetas de "Último Registro")
        $lastMonthlyTotal = MonthlyTotal::orderBy('date', 'desc')->first();

        // 4. Obtenemos el penúltimo registro (para calcular el crecimiento)
        $secondLastRecord = MonthlyTotal::orderBy('date', 'desc')->skip(1)->first();

        // 5. Calculamos el crecimiento
        $growth = [
            'qemus' => 0,
            'cpus' => 0,
            'memorys' => 0,
            'disks' => 0,
        ];

        if ($lastMonthlyTotal && $secondLastRecord) {
            $growth['qemus'] = $lastMonthlyTotal->cluster_qemus - $secondLastRecord->cluster_qemus;
            $growth['cpus'] = $lastMonthlyTotal->cluster_cpu - $secondLastRecord->cluster_cpu;
            $growth['memorys'] = $lastMonthlyTotal->cluster_memory - $secondLastRecord->cluster_memory;

            // --- ¡AQUÍ ESTÁ LA LÓGICA CLAVE! ---
            // Esta resta es la que corrige tu error visual.
            // Si $secondLastRecord->cluster_disk es 0 (porque es un registro antiguo
            // antes de que arreglaras la lógica de 'size'), el crecimiento será
            // igual al total. El error está en tus *datos*, pero el código es correcto.
            $growth['disks'] = $lastMonthlyTotal->cluster_disk - $secondLastRecord->cluster_disk;
        }

        // 6. Pasamos todas las variables necesarias a la vista en un solo 'compact()'
        return view('proxmox.virtualMachineHistory', compact(
            'VMHistory',          // Para el acordeón
            'histories',          // Para el gráfico
            'lastMonthlyTotal',   // Para las tarjetas de "Último Registro"
            'growth'              // Para las tarjetas de "Crecimiento"
        ));
    }

    /**
     * Muestra el historial anual de las máquinas virtuales.
     * Versión LIMPcompreia y CORREGIDA.
     */
    public function indexAnual()
    {
        // 1. Obtenemos el historial detallado (para el acordeón)
        $VMHistory = VirtualMachineHistory::all();

        // 2. Obtenemos los totales mensuales (para el gráfico)
        $histories = VirtualMachineHistory::selectRaw('date, SUM(cluster_qemus) as cluster_qemus, SUM(cluster_cpu) as cluster_cpu, SUM(cluster_memory) as cluster_memory, SUM(cluster_disk) as cluster_disk')
            ->groupBy('date')
            ->get();

        // 3. Obtenemos el último total registrado (para las tarjetas de "Último Registro")
        //    ¡ESTA VARIABLE FALTABA ANTES!
        $lastMonthlyTotal = MonthlyTotal::orderBy('date', 'desc')->first();

        // 4. Calculamos el crecimiento vs el año anterior
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        // Total del año actual (es el último registro que tenemos)
        $currentYearTotal = $lastMonthlyTotal;

        // Total del año anterior (es el último registro del año pasado)
        $previousYearTotal = MonthlyTotal::whereYear('date', $previousYear)
                                ->orderBy('date', 'desc')
                                ->first();

        $growth = [
            'qemus' => 0,
            'cpus' => 0,
            'memorys' => 0,
            'disks' => 0,
        ];

        // 5. Calculamos la diferencia
        if ($currentYearTotal && $previousYearTotal) {
            $growth['qemus'] = $currentYearTotal->cluster_qemus - $previousYearTotal->cluster_qemus;
            $growth['cpus'] = $currentYearTotal->cluster_cpu - $previousYearTotal->cluster_cpu;
            $growth['memorys'] = $currentYearTotal->cluster_memory - $previousYearTotal->cluster_memory;

            // --- ¡AQUÍ ESTÁ LA LÓGICA CLAVE ANUAL! ---
            $growth['disks'] = $currentYearTotal->cluster_disk - $previousYearTotal->cluster_disk;
        }

        // 6. Pasamos todas las variables necesarias a la vista
        return view('proxmox.virtualMachineHistoryAnual', compact(
            'VMHistory',          // Para el acordeón
            'histories',          // Para el gráfico
            'lastMonthlyTotal',   // Para las tarjetas de "Último Registro" (¡AHORA SÍ ESTÁ!)
            'growth'              // Para las tarjetas de "Crecimiento"
        ));
    }

    /**
     * Obtiene el detalle de VMs/Nodos creados o eliminados en un período específico
     */
    public function getChangeDetails(Request $request)
    {
        $clusterName = $request->cluster_name;
        $date = $request->date;
        $type = $request->type; // 'vms_created', 'vms_deleted', 'nodes_created', 'nodes_deleted'

        $startOfMonth = Carbon::parse($date)->startOfMonth();
        $endOfMonth = Carbon::parse($date)->endOfMonth();

        $data = [];

        switch ($type) {
            case 'vms_created':
                $data = \App\Models\qemu::where('cluster_name', $clusterName)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->select('vmid', 'name', 'node_id', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();
                break;

            case 'vms_deleted':
                $nodeIds = \App\Models\node::where('cluster_name', $clusterName)->pluck('id_proxmox');
                $data = \App\Models\QemuDeleted::whereIn('node_id', $nodeIds)
                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                    ->select('id_proxmox', 'name', 'node_id', 'updated_at as created_at')
                    ->orderBy('updated_at', 'desc')
                    ->get();
                break;

            case 'nodes_created':
                $data = \App\Models\node::where('cluster_name', $clusterName)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->select('id_proxmox', 'node', 'ip', 'updated_at as created_at')
                    ->orderBy('updated_at', 'desc')
                    ->get();
                break;

            case 'nodes_deleted':
                $data = \App\Models\NodeDeleted::where('cluster_name', $clusterName)
                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                    ->select('id_proxmox', 'node', 'ip', 'updated_at as created_at')
                    ->orderBy('updated_at', 'desc')
                    ->get();
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count()
        ]);
    }
}

<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelloWorld;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TablaController;
use App\Http\Controllers\ProxmoxController;
use App\Http\Controllers\QemuDeletedController;
use App\Http\Controllers\VirtualMachineHistoryController;
use App\Http\Controllers\ProxmoxBackupController;
use App\Models\QemuDeleted;
use App\Http\Controllers\FirewallNatRuleController;

// use auth
use Illuminate\Support\Facades\Auth;




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/




Route::get('/proxmox-backup', [ProxmoxBackupController::class, 'index'])->name('pbs.index')->middleware('can:view pbs');
Route::post('/proxmox-backup', [ProxmoxBackupController::class, 'store'])->name('pbs.store')->middleware('can:manage cluster');
Route::get('/proxmox-backup/backup-schedule/export', [ProxmoxBackupController::class, 'exportVmWithoutBackupSchedule'])->name('pbs.backup-schedule.export')->middleware('can:view pbs');
Route::get('/proxmox-backup/backup-schedule/refresh', [ProxmoxBackupController::class, 'refreshBackupScheduleAudit'])->name('pbs.backup-schedule.refresh')->middleware('can:run sync');
Route::get('/proxmox-backup/{server}/sync', [ProxmoxBackupController::class, 'sync'])->name('pbs.sync')->middleware('can:run sync');
Route::delete('/proxmox-backup/{server}', [ProxmoxBackupController::class, 'destroy'])->name('pbs.destroy')->middleware('can:manage cluster');

Route::get('/proxmox', [ProxmoxController::class, 'index'])->name('proxmox.index')->middleware('can:view dashboard');

#Route::get('/', [ProxmoxController::class, 'home'])->name('proxmox.home');
Route::get('/', function () {
    $user = auth()->user();
    if ($user->can('view dashboard')) return redirect()->route('proxmox.index');
    foreach ([
        'view vms' => 'proxmox.qemu',
        'view nodes' => 'proxmox.node',
        'view storage' => 'proxmox.storage',
        'view cluster' => 'proxmox.cluster',
        'view inventory' => 'proxmox.inventory',
        'view history' => 'proxmox.history',
        'view pbs' => 'pbs.index',
    ] as $permission => $route) {
        if ($user->can($permission)) return redirect()->route($route);
    }
    abort(403);
})->middleware('auth');
Route::get('/home', function () {
    return redirect('/');
});
Route::get('/proxmox/fetch', [ProxmoxController::class, 'getDataRedirect'])->middleware('can:run sync');
Route::get('/proxmox/actualizar', [ProxmoxController::class, 'getDataRedirect2'])->name('proxmox.actualizar')->middleware(['auth','can:run sync']);
Route::get('/proxmox/node', [ProxmoxController::class, 'node'])->name('proxmox.node')->middleware('can:view nodes');

Route::get('/proxmox/qemu', [ProxmoxController::class, 'qemu'])->name('proxmox.qemu')->middleware('can:view vms');
Route::get('/proxmox/storage', [ProxmoxController::class, 'storage'])->name('proxmox.storage')->middleware('can:view storage');
Route::get('/proxmox/cluster/create', [ProxmoxController::class, 'createCluster'])->name('proxmox.cluster.create')->middleware('can:manage cluster');
Route::post('/proxmox/cluster/', [ProxmoxController::class, 'storeCluster'])->name('proxmox.cluster.store')->middleware('can:manage cluster');
Route::get('/proxmox/cluster', [ProxmoxController::class, 'cluster'])->name('proxmox.cluster')->middleware('can:view cluster');

Route::get('/proxmox/cluster/{name}', [ProxmoxController::class, 'showByIdCluster'])->name('proxmox.cluster.show')->middleware('can:view cluster');
Route::get('/proxmox/cluster/{name}/export', [ProxmoxController::class, 'exportQemuByClusterCSV'])->name('proxmox.qemuByCluster.export')->middleware('can:view vms');
Route::delete('/proxmox/cluster/{name}', [ProxmoxController::class, 'destroyCluster'])->name('proxmox.cluster.destroy')->middleware('can:manage cluster');

Route::delete('/proxmox/node/{node}', [ProxmoxController::class, 'destroyNode'])->name('proxmox.cluster.node.destroy')->middleware('can:manage cluster');
Route::get('/proxmox/node/search', [ProxmoxController::class, 'searchNode'])->name('proxmox.searchNode')->middleware('can:view nodes');
Route::get('/proxmox/node/{node}', [ProxmoxController::class, 'showByIdNode'])->name('proxmox.cluster.node.show')->middleware('can:view nodes');

Route::get('/proxmox/exportQemu', [ProxmoxController::class, 'exportQemuCSV'])->name('proxmox.exportQemu')->middleware('can:view vms');
Route::get('/proxmox/exportStorage', [ProxmoxController::class, 'exportStorageCSV'])->name('proxmox.exportStorage')->middleware('can:view storage');
Route::get('/proxmox/exportNode', [ProxmoxController::class, 'exportNodeCSV'])->name('proxmox.exportNode')->middleware('can:view nodes');
Route::get('/proxmox/exportCluster', [ProxmoxController::class, 'exportClusterCSV'])->name('proxmox.exportCluster')->middleware('can:view cluster');

Route::get('/proxmox/qemu/search', [ProxmoxController::class, 'searchQemu'])->name('proxmox.searchQemu')->middleware('can:view vms');
Route::get('/proxmox/storage/search', [ProxmoxController::class, 'searchStorage'])->name('proxmox.searchStorage')->middleware('can:view storage');

Route::delete('/proxmox/qemu', [ProxmoxController::class, 'destroyQemu'])->name('proxmox.qemu.destroy')->middleware('can:manage cluster');
Route::get('/proxmox/QemuDeleted', [QemuDeletedController::class, 'index'])->name('proxmox.qemuDeleted')->middleware('can:view history');

Route::get('/proxmox/history', [VirtualMachineHistoryController::class, 'indexMonthly'])->name('proxmox.history')->middleware('can:view history');
Route::get('/proxmox/historyAnual', [VirtualMachineHistoryController::class, 'indexAnual'])->name('proxmox.historyAnual')->middleware('can:view history');

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

Route::get('proxmox/sync/fast', [ProxmoxController::class, 'fastSyncRedirect'])
     ->name('proxmox.sync.fast')
     ->middleware('can:run sync');

Route::get('proxmox/sync/qga', [ProxmoxController::class, 'qgaSyncRedirect'])
     ->name('proxmox.sync.qga')
     ->middleware('can:run sync');

//Ruta para obtener alertas en tiempo real (AJAX)
Route::get('/proxmox/alerts', [ProxmoxController::class, 'getAlerts'])->name('proxmox.alerts');

Route::get('/proxmox/export/qemu-stopped', [App\Http\Controllers\ProxmoxController::class, 'exportQemuStopped'])
     ->name('proxmox.exportQemuStopped')
     ->middleware(['auth','can:view vms']);
Route::post('proxmox/inventory/node/{id}/update-license', [ProxmoxController::class, 'updateLicenseDate'])
	->name('proxmox.inventory.updateLicense')
	->middleware('can:manage cluster');
Route::get('proxmox/inventory/export', [ProxmoxController::class, 'exportInventoryCSV'])
     ->name('proxmox.exportInventory')
     ->middleware('can:view inventory');
Route::get('/proxmox/inventory/sync', [ProxmoxController::class, 'syncHardwareInventory'])->name('proxmox.inventory.sync')->middleware('can:run sync');
Route::get('/proxmox/inventory/sync/{nodeId}', [ProxmoxController::class, 'syncHardwareInventory'])->name('proxmox.inventory.syncNode')->middleware('can:run sync');
Route::post('/proxmox/inventory/node/{id}/manual', [ProxmoxController::class, 'updateManualInventory'])->name('proxmox.inventory.manual')->middleware('can:manage cluster');
Route::get('/proxmox/search/export', [ProxmoxController::class, 'exportGlobalSearch'])->name('proxmox.globalSearch.export')->middleware('can:view inventory');
Route::get('/proxmox/search', [ProxmoxController::class, 'globalSearch'])->name('proxmox.globalSearch')->middleware('can:view inventory');
Route::get('/proxmox/inventory', [ProxmoxController::class, 'showInventory'])->name('proxmox.inventory')->middleware('can:view inventory');
// Ruta para actualizar manualmente la IP de una VM
Route::post('proxmox/qemus/{qemuId}/update-ip', [ProxmoxController::class, 'updateQemuIp'])->name('proxmox.qemu.update_ip')->middleware('can:manage cluster');

Route::get('/proxmox/sync-cluster/{name}', [App\Http\Controllers\ProxmoxController::class, 'syncClusterName'])->name('proxmox.sync.cluster')->middleware('can:run sync');

//PRUEBA
// Ruta temporal de DEBUG
// Ruta de DEBUG MEJORADA (Soporta Tokens y Passwords)
Route::get('/debug-network/{nodeName}', function ($nodeName) {

    // 1. Buscar nodo y credenciales
    $node = \App\Models\node::where('node', $nodeName)->firstOrFail();
    $credentials = \App\Models\ClusterCredentials::where('ip', $node->ip)->firstOrFail();
    $service = new \App\Services\ProxmoxService2();

    // 2. Autenticación Inteligente (Copiada del Service)
    $headers = [];
    if (strpos($credentials->username, '!') !== false) {
        // ES TOKEN
        $token = \Illuminate\Support\Facades\Crypt::decrypt($credentials->password);
        $headers['Authorization'] = "PVEAPIToken=" . $credentials->username . "=" . $token;
    } else {
        // ES PASSWORD
        $authData = $service->getAuthToken($node->ip, $credentials->username, $credentials->password);
        if (!$authData) return "Error: No se pudo obtener ticket de autenticación.";
        $headers['Cookie'] = "PVEAuthCookie=" . ($authData['ticket'] ?? $authData);
    }

    // 3. Petición Directa a la API
    $url = 'https://' . $node->ip . ':8006/api2/json/nodes/' . $nodeName . '/network';

    try {
        $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 5]);
        $response = $client->request('GET', $url, ['headers' => $headers]);
        $json = json_decode($response->getBody(), true);

        // 4. IMPRIMIR DATOS CRUDOS
        return response()->json($json['data'], 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return "Error conectando a la API: " . $e->getMessage();
    }
});
// FIN PRUEBA
Route::get('/proxmox/firewall/nat', [FirewallNatRuleController::class, 'index'])->name('proxmox.firewall.nat')->middleware('can:view dashboard');
// Ruta para obtener detalles de cambios en VMs/Nodos
Route::get('/proxmox/history/change-details', [VirtualMachineHistoryController::class, 'getChangeDetails'])
    ->name('proxmox.history.changeDetails')
    ->middleware('can:view history');

Auth::routes(['register' => false, 'reset' => false, 'verify' => false, 'confirm' => false]);


// Auditoría controlada por clúster: un servidor por petición, sin paralelismo.
Route::middleware('auth')->prefix('proxmox/qemu/audit')->group(function () {
    Route::post('/vm/{qemu}/start', [\App\Http\Controllers\ClusterAuditController::class, 'startVm'])->name('proxmox.audit.vm.start')->middleware('can:run sync');
    Route::post('/{cluster}/start', [\App\Http\Controllers\ClusterAuditController::class, 'start'])->name('proxmox.audit.start')->middleware('can:run sync');
    Route::get('/{run}/next', [\App\Http\Controllers\ClusterAuditController::class, 'next'])->name('proxmox.audit.next')->middleware('can:run sync');
    Route::post('/{run}/stop', [\App\Http\Controllers\ClusterAuditController::class, 'stop'])->name('proxmox.audit.stop')->middleware('can:run sync');
    Route::get('/{cluster}/latest', [\App\Http\Controllers\ClusterAuditController::class, 'latest'])->name('proxmox.audit.latest')->middleware('can:view vms');
});

<?php

namespace App\Console\Commands;

use App\Models\PbsServer;
use App\Models\PbsSnapshot;
use App\Models\PbsTask;
use App\Services\PbsApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PbsSyncAll extends Command
{
    protected $signature = 'pbs:sync-all {--server= : ID o nombre exacto del PBS a sincronizar}';

    protected $description = 'Sincroniza todos los Proxmox Backup Server registrados y refresca snapshots/tareas post sync/prune/GC.';

    public function handle(PbsApiService $service): int
    {
        $query = PbsServer::query()->orderBy('role')->orderBy('name');
        if ($filter = $this->option('server')) {
            $query->where(function ($q) use ($filter) {
                $q->where('id', $filter)->orWhere('name', $filter);
            });
        }

        $servers = $query->get();
        if ($servers->isEmpty()) {
            $this->warn('No hay servidores PBS para sincronizar.');
            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;
        $this->info('Iniciando PBS sync-all: ' . now()->format('Y-m-d H:i:s'));
        Log::info('PBS CRON: iniciando sync-all', ['servers' => $servers->pluck('name')->all()]);

        foreach ($servers as $server) {
            try {
                $this->line("Sincronizando {$server->name} ({$server->role}) {$server->host}:{$server->port}...");
                $service->sync($server);
                $server->refresh();
                $snapshots = PbsSnapshot::where('pbs_server_id', $server->id)->count();
                $tasks = PbsTask::where('pbs_server_id', $server->id)->count();
                $datastores = $server->datastores()->count();
                $this->info("OK {$server->name}: version={$server->version}, datastores={$datastores}, snapshots={$snapshots}, tasks={$tasks}");
                Log::info('PBS CRON: sync OK', ['server' => $server->name, 'datastores' => $datastores, 'snapshots' => $snapshots, 'tasks' => $tasks]);
                $ok++;
            } catch (\Throwable $e) {
                $failed++;
                $server->update(['status' => 'error', 'last_error' => $e->getMessage()]);
                $this->error("ERROR {$server->name}: {$e->getMessage()}");
                Log::error('PBS CRON: sync error', ['server' => $server->name, 'error' => $e->getMessage()]);
            }
        }

        $this->info("PBS sync-all finalizado: ok={$ok}, failed={$failed}");
        Log::info('PBS CRON: sync-all finalizado', ['ok' => $ok, 'failed' => $failed]);
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

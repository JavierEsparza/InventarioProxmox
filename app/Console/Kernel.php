<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\RefreshQgaIpOsSource::class,

        \App\Console\Commands\ImportVmIpOsFromComparisonCsv::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecutar diariamente a las 02:00 AM
        // runInBackground() permite que si tienes otras tareas, no se bloqueen.
        $schedule->command('proxmox:sync-daily')
                 ->dailyAt('02:00')
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/proxmox-cron.log')); // Log separado para control


        // Refresco periódico PBS: mantiene datos correctos después de sync/prune/garbage collector.
        $schedule->command('pbs:sync-all')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping(30)
                 ->appendOutputTo(storage_path('logs/pbs-sync.log'));
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InfransibleStatusSeeder extends Seeder
{
    public function run(): void
    {
        $qemu = DB::table('qemus')->where('name', 'ZabbixApp01')->first();
        if (!$qemu) {
            $this->command?->warn('VM no encontrada: ZabbixApp01');
            return;
        }
        $updated = DB::table('qemus')->where('id', $qemu->id)->update([
            'ip_address' => '192.168.11.47',
            'ip_address_manual' => '192.168.11.47',
            'ip_os_source' => 'manual',
            'infransible_status' => 'ok',
            'infransible_checked_at' => now(),
            'infransible_note' => 'SSH autenticado en 192.168.11.47; sudo no autorizado',
        ]);
        $this->command?->info("ZabbixApp01 corregido y estado Infransible aplicado. Registros actualizados: {$updated}");
    }
}

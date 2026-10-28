<?php

namespace Database\Seeders;

use App\Models\QemuNetworkAddress;
use App\Models\qemu;
use Illuminate\Database\Seeder;

class QemuNetworkAddressPrototypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Backup-A10', 'interface' => 'ens19', 'ip_address' => '10.0.202.102', 'cidr' => '10.0.202.102/23', 'network_type' => 'backup', 'description' => 'Red de backup Veeam', 'is_virtual' => false],
            ['name' => 'VoIP-SBC', 'interface' => 'ens19', 'ip_address' => '131.221.164.9', 'cidr' => '131.221.164.9/24', 'network_type' => 'public', 'description' => 'Red pública/externa', 'is_virtual' => false],
            ['name' => 'ZabbixApp01', 'interface' => 'ens19', 'ip_address' => '10.0.202.97', 'cidr' => '10.0.202.97/23', 'network_type' => 'backup', 'description' => 'Red de backup Veeam', 'is_virtual' => false],
            ['name' => 'ZabbixApp02', 'interface' => 'ens19', 'ip_address' => '10.0.202.104', 'cidr' => '10.0.202.104/23', 'network_type' => 'backup', 'description' => 'Red de backup Veeam', 'is_virtual' => false],
            ['name' => 'ZabbixApp03', 'interface' => 'ens19', 'ip_address' => '10.0.202.105', 'cidr' => '10.0.202.105/23', 'network_type' => 'backup', 'description' => 'Red de backup Veeam', 'is_virtual' => false],
            ['name' => 'ZabbixApp01', 'interface' => 'ens18', 'ip_address' => '192.168.11.46', 'cidr' => '192.168.11.46/24', 'network_type' => 'vip', 'description' => 'VIP compartida ZabbixApp_Cluster; Corosync/Pacemaker', 'is_virtual' => true],
        ];

        foreach ($rows as $row) {
            $vm = qemu::where('name', $row['name'])->first();
            if (!$vm) {
                $this->command?->warn("VM no encontrada: {$row['name']}");
                continue;
            }
            QemuNetworkAddress::updateOrCreate(
                ['qemu_id' => $vm->id, 'interface' => $row['interface'], 'ip_address' => $row['ip_address']],
                [
                    'cidr' => $row['cidr'], 'network_type' => $row['network_type'],
                    'description' => $row['description'], 'source' => 'ssh_audit',
                    'is_virtual' => $row['is_virtual'],
                ]
            );
            $this->command?->info("IP registrada: {$row['name']} {$row['ip_address']}");
        }
    }
}

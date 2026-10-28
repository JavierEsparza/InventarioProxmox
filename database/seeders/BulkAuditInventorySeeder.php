<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BulkAuditInventorySeeder extends Seeder
{
    public function run(): void
    {
        $backup = 'qemu_network_addresses_backup_20260828';
        if (!DB::getSchemaBuilder()->hasTable($backup)) {
            DB::statement("CREATE TABLE {$backup} LIKE qemu_network_addresses");
            DB::statement("INSERT INTO {$backup} SELECT * FROM qemu_network_addresses");
            $this->command?->info("Backup de IPs creado: {$backup}");
        }
        $ips = [
            ['name'=>'Backup-A10','interface'=>'ens19','ip_address'=>'10.0.202.102','cidr'=>'10.0.202.102/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'HA1-Zabbix-Front','interface'=>'ens18','ip_address'=>'192.168.11.42','cidr'=>'192.168.11.42/32','network_type'=>'vip','description'=>'VIP/servicio compartido','is_virtual'=>false],
            ['name'=>'HAProxy-wazuh02','interface'=>'ens18','ip_address'=>'10.10.52.176','cidr'=>'10.10.52.176/24','network_type'=>'secondary','description'=>'IP secundaria operativa','is_virtual'=>false],
            ['name'=>'MaxScale2-ZabbixDB','interface'=>'ens18','ip_address'=>'192.168.11.50','cidr'=>'192.168.11.50/32','network_type'=>'vip','description'=>'VIP/servicio compartido','is_virtual'=>false],
            ['name'=>'SBC-ING-MUNDO','interface'=>'ens19','ip_address'=>'131.221.164.40','cidr'=>'131.221.164.40/24','network_type'=>'public','description'=>'Red pública/externa','is_virtual'=>false],
            ['name'=>'Srv-Calidad1-Wh','interface'=>'ens19','ip_address'=>'10.0.202.101','cidr'=>'10.0.202.101/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'Srv-Calidad2-Proc','interface'=>'ens19','ip_address'=>'10.0.202.95','cidr'=>'10.0.202.95/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'VoIP-SBC','interface'=>'ens19','ip_address'=>'131.221.164.9','cidr'=>'131.221.164.9/24','network_type'=>'public','description'=>'Red pública/externa','is_virtual'=>false],
            ['name'=>'ZabbixApp01','interface'=>'ens19','ip_address'=>'10.0.202.97','cidr'=>'10.0.202.97/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'ZabbixApp02','interface'=>'ens19','ip_address'=>'10.0.202.104','cidr'=>'10.0.202.104/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'ZabbixApp03','interface'=>'ens19','ip_address'=>'10.0.202.105','cidr'=>'10.0.202.105/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'ZabbixDb01','interface'=>'ens19','ip_address'=>'10.0.202.98','cidr'=>'10.0.202.98/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'ZabbixDb03','interface'=>'ens19','ip_address'=>'10.0.202.107','cidr'=>'10.0.202.107/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'ZabbixFront01','interface'=>'ens19','ip_address'=>'10.0.202.99','cidr'=>'10.0.202.99/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
            ['name'=>'ZabbixFront02','interface'=>'ens19','ip_address'=>'10.0.202.108','cidr'=>'10.0.202.108/23','network_type'=>'backup','description'=>'Red de backup Veeam','is_virtual'=>false],
        ];
        $ipCount = 0;
        foreach ($ips as $row) {
            $vm = DB::table('qemus')->where('name', $row['name'])->first();
            if (!$vm) { $this->command?->warn("VM no encontrada: {$row['name']}"); continue; }
            DB::table('qemu_network_addresses')->updateOrInsert(
                ['qemu_id'=>$vm->id, 'interface'=>$row['interface'], 'ip_address'=>$row['ip_address']],
                ['cidr'=>$row['cidr'], 'network_type'=>$row['network_type'], 'description'=>$row['description'], 'source'=>'ssh_audit', 'is_virtual'=>$row['is_virtual'], 'updated_at'=>now(), 'created_at'=>now()]
            );
            $ipCount++;
        }
        $names = [
            'Api-Colas-Clientes',
            'Api-Colas-Clientes2',
            'Api-Colas3',
            'Api-Colas4',
            'Api-Colas5',
            'Api-Colas6',
            'Arcgis01-DB',
            'Arcgis01-DB-CLON',
            'Arcgis02-Server',
            'ArcgisDB-Desarrollo0910',
            'BD-Cluster-Clientes',
            'BD-Cluster-Core',
            'BMP-BGP',
            'Backup-A10',
            'Backup-Core-Infra',
            'DHCP-Ajax',
            'DNS-Interno02',
            'DNS-Interno1',
            'Darktrace-Monitor-Mundo',
            'ELK-Infra',
            'Fog-Soporteti-Service',
            'FreeRadius-Empresas-Holdco',
            'FreeRadius-Networking-HA1',
            'GTS-IA',
            'Galera-Manager-Zabbix',
            'Galera-Testigo-Zabbix',
            'Gestcon',
            'Glpi-Service',
            'GpsTrack2-Innovacion',
            'Grafana-Noc',
            'Grafana-Service',
            'HA1-Zabbix-Front',
            'HA2-Zabbix-Front',
            'HAProxy-wazuh02',
            'Ipam-Service',
            'LibreNMS-TI',
            'MaxScale1-ZabbixDB',
            'MaxScale2-ZabbixDB',
            'N8N-Javi',
            'Networking-Script',
            'New-Netbox-Service',
            'New-SBC-Clientes',
            'New-SBC-Empresa',
            'Odoo17-Service',
            'Portabilidad-SSC5',
            'Pre-Odoo17-Service',
            'Prueba-Bash',
            'Prueba-Debian13',
            'SBC-ING-MUNDO',
            'SRV-AgenteIA-PEX',
            'SRV-FIBERHOME',
            'Sondas-PIN-NW',
            'Sondas-Subtel-PIN',
            'Srv-Agente-Legales',
            'Srv-Automatizacion',
            'Srv-Calidad1-Wh',
            'Srv-Calidad2-Proc',
            'Srv-Ing-Service',
            'Srv-Smart-Business',
            'Srv-TACACS',
            'URL-Proxy-CC',
            'VTiger',
            'VTiger-Virtualizacion',
            'VoIP-SBC',
            'VoiceAgent-Mundo',
            'Zabbix-Back01',
            'Zabbix-Back02',
            'Zabbix-DBM',
            'Zabbix-DBS',
            'Zabbix-FitControl',
            'Zabbix-Front01',
            'Zabbix-Front02',
            'Zabbix-HA',
            'Zabbix-Noc-Prueba',
            'ZabbixApp01',
            'ZabbixApp02',
            'ZabbixApp03',
            'ZabbixDb01',
            'ZabbixDb02',
            'ZabbixDb03',
            'ZabbixFront01',
            'ZabbixFront02',
            'Zbx-Proxy-Emp01',
            'Zbx-Proxy-Emp02',
            'Zbx-Proxy-Emp03',
            'ns3-mundo',
            'ns4-mundo'
        ];
        $statusCount = DB::table('qemus')->whereIn('name', $names)->update([
            'infransible_status'=>'ok', 'infransible_checked_at'=>now(),
            'infransible_note'=>'SSH autenticado según auditoría; sudo no validado',
        ]);
        $this->command?->info("IPs cargadas/actualizadas: {$ipCount}; VMs con INF OK: {$statusCount}");
    }
}

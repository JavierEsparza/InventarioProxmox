<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class qemu extends Model
{
    /* protected $primaryKey = 'id_proxmox';
    public $incrementing = true; */
    protected $fillable = [
        'id_proxmox', 'vmid', 'name', 'type', 'status', 'ip_address',
        'ip_address_manual', 'os_name', 'os_name_manual', 'ip_os_source',
        'manual_inventory_note', 'manual_inventory_updated_at',
        'qga_enabled_config', 'zabbix_status', 'zabbix_version',
        'wazuh_status', 'wazuh_version', 'zabbix_status_infransible', 'zabbix_version_infransible',
        'wazuh_status_infransible', 'wazuh_version_infransible', 'infransible_services', 'infransible_services_checked_at', 'infransible_status',
        'infransible_checked_at', 'infransible_note', 'ssh_port', 'disk', 'maxdisk', 'node_id',
        'uptime', 'mem', 'maxmem', 'cpu', 'maxcpu', 'netin', 'netout',
        'storageName', 'size', 'cluster_name', 'id_node'
    ];

    protected $casts = [
        'infransible_services' => 'array',
        'infransible_services_checked_at' => 'datetime',
    ];

    public function firewallNatRules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FirewallNatRule::class);
    }

    public function node()
    {
        return $this->belongsTo(Node::class, 'id_node', 'id');
    }

    public function disks()
    {
        return $this->hasMany(Storage::class);
    }

    public function networkAddresses()
    {
        return $this->hasMany(QemuNetworkAddress::class, 'qemu_id');
    }
}

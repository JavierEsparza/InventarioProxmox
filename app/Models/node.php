<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    /* protected $primaryKey = 'id_proxmox';
    public $incrementing = false; */
    protected $fillable = [
        'cluster_name',
        'id_proxmox',
        'name',
        'ip',
        'type',
        'online',
        'status',
        'disk',
        'maxdisk',
        'node',
        'mem',
        'maxmem',
        'cpu',
        'maxcpu',
	'uptime',
	'serial_number',
        'brand',
        'model',
	'firmware_version',
	'pve_version',
        'kernel_version',
        'support_license_date',
        'skip_ssh_sync'
    ];

    public function storages()
    {
        return $this->belongsToMany(Storage::class, 'node_storage', 'node_id', 'storage_id');
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class, 'cluster_name', 'name');
    }

    public function qemus()
    {
        return $this->hasMany(Qemu::class);
    }

    public function interfaces()
    {
        return $this->hasMany(\App\Models\NodeInterface::class, 'node_id');
    }

}

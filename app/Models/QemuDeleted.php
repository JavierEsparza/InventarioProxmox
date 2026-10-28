<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QemuDeleted extends Model
{
    protected $primaryKey = 'id_proxmox';
    public $incrementing = false;
    protected $fillable = [
        'id_proxmox',
        'name',
        'type',
        'status',
        'disk',
        'maxdisk',
        'node_id',
        'uptime',
        'mem',
        'maxmem',
        'cpu',
        'maxcpu',
        'netin',
        'netout',
        'storageName',
        'size',
    ];

}

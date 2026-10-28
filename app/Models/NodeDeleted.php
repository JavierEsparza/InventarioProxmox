<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeDeleted extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_proxmox';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_proxmox',
        'node',
        'ip',
        'cluster_name',
        'status',
        'disk',
        'maxdisk',
        'mem',
        'maxmem',
        'cpu',
        'maxcpu',
        'uptime',
    ];
}

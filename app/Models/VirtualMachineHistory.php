<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualMachineHistory extends Model
{
    protected $fillable = [
        'date',
        'cluster_name',
        'cluster_qemus',
        'cluster_cpu',
        'cluster_memory',
        'cluster_disk',
        'vms_created',
        'vms_deleted',
        'vms_migrated',
        'vms_unverified',
        'original_vms_created',
        'original_vms_deleted',
        'calculation_method',
        'nodes_created',
        'nodes_deleted',

    ];
}

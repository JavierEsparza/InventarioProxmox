<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbsSnapshot extends Model
{
    protected $fillable = [
        'pbs_server_id',
        'datastore',
        'namespace',
        'backup_type',
        'backup_id',
        'vmid',
        'backup_time',
        'size',
        'protected',
        'verified',
        'raw',
    ];

    protected $casts = [
        'backup_time' => 'datetime',
        'protected' => 'boolean',
        'raw' => 'array',
    ];

    public function server()
    {
        return $this->belongsTo(PbsServer::class, 'pbs_server_id');
    }
}

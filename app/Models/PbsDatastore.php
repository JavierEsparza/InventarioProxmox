<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbsDatastore extends Model
{
    protected $table = 'pbs_datastores';

    protected $fillable = [
        'pbs_server_id', 'name', 'path', 'total', 'used', 'available', 'usage_percent',
        'gc_status', 'prune_status', 'verify_status', 'last_gc', 'last_prune', 'last_verify'
    ];

    protected $casts = [
        'last_gc' => 'datetime',
        'last_prune' => 'datetime',
        'last_verify' => 'datetime',
        'usage_percent' => 'float',
    ];

    public function server()
    {
        return $this->belongsTo(PbsServer::class, 'pbs_server_id');
    }
}

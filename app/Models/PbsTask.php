<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbsTask extends Model
{
    protected $table = 'pbs_tasks';

    protected $fillable = [
        'pbs_server_id', 'upid', 'type', 'status', 'starttime', 'endtime', 'user', 'worker_id', 'summary'
    ];

    protected $casts = [
        'starttime' => 'datetime',
        'endtime' => 'datetime',
    ];

    public function server()
    {
        return $this->belongsTo(PbsServer::class, 'pbs_server_id');
    }
}

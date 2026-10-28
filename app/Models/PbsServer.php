<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbsServer extends Model
{
    protected $table = 'pbs_servers';

    protected $fillable = [
        'name', 'role', 'host', 'port', 'api_user', 'api_token_id', 'api_token_secret',
        'verify_tls', 'status', 'version', 'last_error', 'last_sync_at', 'notes'
    ];

    protected $casts = [
        'verify_tls' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function datastores()
    {
        return $this->hasMany(PbsDatastore::class, 'pbs_server_id');
    }

    public function tasks()
    {
        return $this->hasMany(PbsTask::class, 'pbs_server_id');
    }
}

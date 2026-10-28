<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cluster extends Model
{
    use HasFactory;

    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $fillable = [
        'id_proxmox',
        'name',
        'node_count',
        'type',
        'nodes'
    ];

    public function nodes()
    {
        return $this->hasMany(Node::class, 'cluster_name', 'name');
    }/*  */
}

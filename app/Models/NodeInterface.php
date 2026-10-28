<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeInterface extends Model
{
    use HasFactory;

    protected $table = 'node_interfaces';

    protected $fillable = [
        'node_id',
        'iface',
        'type',
        'media_type',
        'link_speed',
        'duplex',
        'driver',
        'bus_info',
        'firmware',
        'link_detected',
        'address',
        'cidr',
        'gateway',
	'mac',
	'ports',
        'bond_parent',
	'bridge_parent',
        'comments',
        'active'
    ];

    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirewallNatRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'qemu_id', 'firewall_name', 'policy_order', 'policy_name', 'action',
        'policy_type', 'from_value', 'to_value', 'public_ip', 'private_ip',
        'protocol', 'public_port', 'private_port', 'geolocation', 'pbr',
        'sdwan', 'application_control', 'enabled', 'notes', 'last_verified_at',
    ];

    protected $casts = [
        'policy_order' => 'integer',
        'public_port' => 'integer',
        'private_port' => 'integer',
        'enabled' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function qemu(): BelongsTo
    {
        return $this->belongsTo(qemu::class);
    }
}

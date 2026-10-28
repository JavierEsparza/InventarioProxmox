<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QemuNetworkAddress extends Model
{
    protected $table = 'qemu_network_addresses';

    protected $fillable = [
        'qemu_id', 'interface', 'ip_address', 'cidr', 'network_type',
        'description', 'source', 'is_virtual',
    ];

    protected $casts = ['is_virtual' => 'boolean'];

    public function qemu()
    {
        return $this->belongsTo(qemu::class, 'qemu_id');
    }
}

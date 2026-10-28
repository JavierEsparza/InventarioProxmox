<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class node_storage extends Model
{
    use HasFactory;

    protected $table = 'node_storage';
    protected $fillable = ['node_id', 'storage_id'];

    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id', 'id');
    }

    public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id', 'id');
    }


}

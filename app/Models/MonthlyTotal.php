<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyTotal extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'cluster_qemus',
        'cluster_cpu',
        'cluster_memory',
        'cluster_disk',

    ];
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('node_deleteds', function (Blueprint $table) {
            $table->string('id_proxmox')->primary();
            $table->string('node')->nullable();
            $table->string('ip')->nullable();
            $table->string('cluster_name')->nullable();
            $table->string('status')->nullable();
            $table->bigInteger('disk')->nullable();
            $table->bigInteger('maxdisk')->nullable();
            $table->bigInteger('mem')->nullable();
            $table->bigInteger('maxmem')->nullable();
            $table->float('cpu')->nullable();
            $table->integer('maxcpu')->nullable();
            $table->bigInteger('uptime')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_deleteds');
    }
};

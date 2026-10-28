<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id('id');
            $table->string('cluster_name')->nullable();
            $table->string('id_proxmox')->unique();
            $table->string('ip')->nullable();
            $table->string('type')->nullable();
            $table->boolean('online')->nullable();
            $table->string('status')->nullable();
            $table->bigInteger('disk')->nullable();
            $table->bigInteger('maxdisk')->nullable();
            $table->string('node')->nullable();
            $table->bigInteger('mem')->nullable();
            $table->bigInteger('maxmem')->nullable();
            $table->float('cpu')->nullable();
            $table->integer('maxcpu')->nullable();
            $table->bigInteger('uptime')->nullable();
            $table->timestamps();

            $table->foreign('cluster_name')->references('name')->on('clusters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('nodes');
    }

};

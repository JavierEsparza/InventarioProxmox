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
        //
        /* Schema::create('tabla', function (Blueprint $table) {
            $table->id();
            $table->string('id_proxmox')->unique();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('maxdisk')->nullable();
            $table->string('disk')->nullable();
            $table->string('node')->nullable();
            $table->string('uptime')->nullable();
            $table->string('cgroup_mode')->nullable();
            $table->string('mem')->nullable();
            $table->string('maxmem')->nullable();
            $table->string('maxcpu')->nullable();
            $table->string('cpu')->nullable();
            $table->string('level')->nullable();
            $table->timestamps();
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        /* Schema::drop('tabla'); */
    }
};

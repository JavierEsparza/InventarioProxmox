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
        //debo guardar el nombre del cluster, las ips del nodo, el id del nodo, para eso se hace referencia  nodo para los ultimos 2
        Schema::create('clusters', function (Blueprint $table) {
            $table->id('id')->index();
            $table->string('id_proxmox')->nullable();
            $table->string('name')->unique();
            $table->integer('node_count')->nullable();
            $table->string('type')->nullable();
            $table->string('nodes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clusters');
    }
};

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
    Schema::create('node_interfaces', function (Blueprint $table) {
        $table->id();
        $table->foreignId('node_id')->constrained('nodes')->onDelete('cascade'); // Relación con tu tabla nodes
        $table->string('iface');      // Nombre: vmbr0, eno1
        $table->string('type');       // eth, bridge, bond, vlan
        $table->string('address')->nullable(); // IP v4
        $table->string('cidr')->nullable();    // Máscara /24
        $table->string('gateway')->nullable();
        $table->string('mac')->nullable();
        $table->text('comments')->nullable();  // Descripción/Comentario en Proxmox
        $table->boolean('active')->default(false);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_interfaces');
    }
};

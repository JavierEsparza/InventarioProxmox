<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qemu_network_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qemu_id')->constrained('qemus')->cascadeOnDelete();
            $table->string('interface', 64)->nullable();
            $table->string('ip_address', 64);
            $table->string('cidr', 32)->nullable();
            $table->string('network_type', 32)->default('secondary');
            $table->string('description', 255)->nullable();
            $table->string('source', 32)->default('audit');
            $table->boolean('is_virtual')->default(false);
            $table->timestamps();
            $table->unique(['qemu_id', 'interface', 'ip_address'], 'qemu_net_addr_unique');
            $table->index(['network_type', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qemu_network_addresses');
    }
};

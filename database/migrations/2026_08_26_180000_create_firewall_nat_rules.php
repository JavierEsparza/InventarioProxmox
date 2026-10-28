<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_nat_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qemu_id')->nullable()->constrained('qemus')->nullOnDelete();
            $table->string('firewall_name')->nullable();
            $table->unsignedInteger('policy_order')->nullable();
            $table->string('policy_name');
            $table->string('action', 32)->default('allow');
            $table->string('policy_type', 64)->nullable();
            $table->string('from_value', 255)->nullable();
            $table->string('to_value', 255)->nullable();
            $table->string('public_ip', 45)->nullable();
            $table->string('private_ip', 45)->nullable();
            $table->string('protocol', 16)->nullable();
            $table->unsignedSmallInteger('public_port')->nullable();
            $table->unsignedSmallInteger('private_port')->nullable();
            $table->string('geolocation', 128)->nullable();
            $table->string('pbr', 128)->nullable();
            $table->string('sdwan', 128)->nullable();
            $table->string('application_control', 128)->nullable();
            $table->boolean('enabled')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['public_ip', 'public_port', 'protocol']);
            $table->index(['private_ip', 'private_port']);
            $table->index(['policy_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firewall_nat_rules');
    }
};

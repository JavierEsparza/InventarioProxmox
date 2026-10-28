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
        Schema::table('virtual_machine_histories', function (Blueprint $table) {
            $table->integer('vms_created')->default(0)->after('cluster_disk')->comment('VMs creadas en este mes');
            $table->integer('vms_deleted')->default(0)->after('vms_created')->comment('VMs eliminadas en este mes');
            $table->integer('nodes_created')->default(0)->after('vms_deleted')->comment('Nodos creados en este mes');
            $table->integer('nodes_deleted')->default(0)->after('nodes_created')->comment('Nodos eliminados en este mes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_machine_histories', function (Blueprint $table) {
            $table->dropColumn(['vms_created', 'vms_deleted', 'nodes_created', 'nodes_deleted']);
        });
    }
};

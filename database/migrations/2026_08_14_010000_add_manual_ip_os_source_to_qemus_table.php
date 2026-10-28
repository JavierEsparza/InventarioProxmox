<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            if (!Schema::hasColumn('qemus', 'ip_address_manual')) $table->string('ip_address_manual', 64)->nullable()->after('ip_address');
            if (!Schema::hasColumn('qemus', 'os_name_manual')) $table->string('os_name_manual', 255)->nullable()->after('os_name');
            if (!Schema::hasColumn('qemus', 'ip_os_source')) $table->string('ip_os_source', 32)->nullable()->default('unknown')->after('os_name_manual');
            if (!Schema::hasColumn('qemus', 'manual_inventory_note')) $table->string('manual_inventory_note', 255)->nullable()->after('ip_os_source');
            if (!Schema::hasColumn('qemus', 'manual_inventory_updated_at')) $table->timestamp('manual_inventory_updated_at')->nullable()->after('manual_inventory_note');
        });
    }

    public function down(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            foreach (['manual_inventory_updated_at','manual_inventory_note','ip_os_source','os_name_manual','ip_address_manual'] as $column) {
                if (Schema::hasColumn('qemus', $column)) $table->dropColumn($column);
            }
        });
    }
};

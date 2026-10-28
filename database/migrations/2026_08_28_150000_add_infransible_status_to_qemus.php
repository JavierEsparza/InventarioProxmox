<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            if (!Schema::hasColumn('qemus', 'infransible_status')) $table->string('infransible_status', 32)->nullable()->default('unknown')->after('wazuh_status');
            if (!Schema::hasColumn('qemus', 'infransible_checked_at')) $table->timestamp('infransible_checked_at')->nullable()->after('infransible_status');
            if (!Schema::hasColumn('qemus', 'infransible_note')) $table->string('infransible_note', 255)->nullable()->after('infransible_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            foreach (['infransible_note', 'infransible_checked_at', 'infransible_status'] as $column) {
                if (Schema::hasColumn('qemus', $column)) $table->dropColumn($column);
            }
        });
    }
};

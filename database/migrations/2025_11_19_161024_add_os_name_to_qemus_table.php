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
        Schema::table('qemus', function (Blueprint $table) {
            $table->string('os_name', 255)->nullable()->after('ip_address');
	    $table->boolean('qga_enabled_config')->default(0)->after('os_name');
	});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            $table->dropColumn('os_name');
        });
    }
};

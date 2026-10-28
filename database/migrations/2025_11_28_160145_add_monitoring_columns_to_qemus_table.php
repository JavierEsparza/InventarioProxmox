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
            $table->string('zabbix_status')->nullable(); // active, inactive, unknown
            $table->string('zabbix_version')->nullable();
            $table->string('wazuh_status')->nullable();
            $table->string('wazuh_version')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            //
        });
    }
};

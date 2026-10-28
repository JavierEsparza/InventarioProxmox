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
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->after('uptime');
            $table->string('brand')->nullable()->after('serial_number');
            $table->string('model')->nullable()->after('serial_number');
            $table->string('firmware_version')->nullable()->after('model');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            //
        });
    }
};

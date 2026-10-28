<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_interfaces', function (Blueprint $table) {
            if (!Schema::hasColumn('node_interfaces', 'media_type')) $table->string('media_type')->nullable()->after('type');
            if (!Schema::hasColumn('node_interfaces', 'link_speed')) $table->string('link_speed')->nullable()->after('media_type');
            if (!Schema::hasColumn('node_interfaces', 'duplex')) $table->string('duplex')->nullable()->after('link_speed');
            if (!Schema::hasColumn('node_interfaces', 'driver')) $table->string('driver')->nullable()->after('duplex');
            if (!Schema::hasColumn('node_interfaces', 'bus_info')) $table->string('bus_info')->nullable()->after('driver');
            if (!Schema::hasColumn('node_interfaces', 'firmware')) $table->string('firmware')->nullable()->after('bus_info');
            if (!Schema::hasColumn('node_interfaces', 'link_detected')) $table->string('link_detected')->nullable()->after('firmware');
        });
    }

    public function down(): void
    {
        Schema::table('node_interfaces', function (Blueprint $table) {
            $drop = [];
            foreach (['media_type','link_speed','duplex','driver','bus_info','firmware','link_detected'] as $col) {
                if (Schema::hasColumn('node_interfaces', $col)) $drop[] = $col;
            }
            if ($drop) $table->dropColumn($drop);
        });
    }
};

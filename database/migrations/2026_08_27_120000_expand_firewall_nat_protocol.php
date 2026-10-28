<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firewall_nat_rules', function (Blueprint $table) {
            $table->string('protocol', 128)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('firewall_nat_rules', function (Blueprint $table) {
            $table->string('protocol', 16)->nullable()->change();
        });
    }
};

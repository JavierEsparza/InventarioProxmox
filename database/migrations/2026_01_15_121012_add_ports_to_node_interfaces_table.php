<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('node_interfaces', function (Blueprint $table) {
        $table->string('ports')->nullable()->after('mac'); // Para guardar "eno1 eno2" o "bond0"
    });
}

public function down()
{
    Schema::table('node_interfaces', function (Blueprint $table) {
        $table->dropColumn('ports');
    });
}

};

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
        $table->string('bond_parent')->nullable()->after('ports');   // Ej: bond0
        $table->string('bridge_parent')->nullable()->after('bond_parent'); // Ej: vmbr50
    });
}

public function down()
{
    Schema::table('node_interfaces', function (Blueprint $table) {
        $table->dropColumn(['bond_parent', 'bridge_parent']);
    });
}

};

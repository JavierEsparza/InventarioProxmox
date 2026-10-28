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
        Schema::table('cluster_credentials', function (Blueprint $table) {
            // Cambiamos a 'text' para asegurar que quepa cualquier hash encriptado
            $table->text('password')->change();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cluster_credentials', function (Blueprint $table) {
            //
        });
    }
};

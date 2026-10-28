<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            // 1. Eliminar la antigua restricción única (¡Usa el nombre de tu clave!)
            // El nombre por defecto es 'storages_storage_unique'
            $table->dropUnique('storages_storage_unique');

            // 2. Añadir la nueva restricción única compuesta
            $table->unique(['storage', 'node_id']);
        });
    }

    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            // Lógica para revertir, si es necesario
            $table->dropUnique(['storage', 'node_id']);
            $table->unique('storage', 'storages_storage_unique');
        });
    }
};

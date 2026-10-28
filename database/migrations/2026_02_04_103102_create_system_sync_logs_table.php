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
        Schema::create('system_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('sync_type', ['fast', 'full', 'cron', 'ssh'])->comment('Tipo de sincronización');
            $table->string('triggered_by')->nullable()->comment('Usuario o sistema que disparó la sync');
            $table->integer('clusters_synced')->default(0)->comment('Cantidad de clusters sincronizados');
            $table->text('notes')->nullable()->comment('Notas adicionales o errores');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_sync_logs');
    }
};

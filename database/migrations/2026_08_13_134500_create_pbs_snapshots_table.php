<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pbs_snapshots')) {
            Schema::create('pbs_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pbs_server_id');
                $table->string('datastore', 120);
                $table->string('namespace', 120)->nullable();
                $table->string('backup_type', 60)->nullable();
                $table->string('backup_id', 160)->nullable();
                $table->unsignedBigInteger('vmid')->nullable();
                $table->timestamp('backup_time')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->boolean('protected')->default(false);
                $table->string('verified')->nullable();
                $table->json('raw')->nullable();
                $table->timestamps();
            });
        }
        Schema::table('pbs_snapshots', function (Blueprint $table) {
            $table->index(['pbs_server_id', 'datastore'], 'pbs_snap_srv_store_idx');
            $table->index(['vmid', 'backup_time'], 'pbs_snap_vmid_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbs_snapshots');
    }
};

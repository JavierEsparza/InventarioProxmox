<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pbs_servers')) {
            Schema::create('pbs_servers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('role', ['master', 'slave'])->default('master');
                $table->string('host');
                $table->unsignedInteger('port')->default(8007);
                $table->string('api_user')->nullable();
                $table->string('api_token_id')->nullable();
                $table->text('api_token_secret')->nullable();
                $table->boolean('verify_tls')->default(false);
                $table->string('status')->default('pending');
                $table->string('version')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['host', 'port']);
            });
        }

        if (!Schema::hasTable('pbs_datastores')) {
            Schema::create('pbs_datastores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pbs_server_id')->constrained('pbs_servers')->cascadeOnDelete();
                $table->string('name');
                $table->string('path')->nullable();
                $table->unsignedBigInteger('total')->nullable();
                $table->unsignedBigInteger('used')->nullable();
                $table->unsignedBigInteger('available')->nullable();
                $table->decimal('usage_percent', 6, 2)->nullable();
                $table->string('gc_status')->nullable();
                $table->string('prune_status')->nullable();
                $table->string('verify_status')->nullable();
                $table->timestamp('last_gc')->nullable();
                $table->timestamp('last_prune')->nullable();
                $table->timestamp('last_verify')->nullable();
                $table->timestamps();
                $table->unique(['pbs_server_id', 'name']);
            });
        }

        if (!Schema::hasTable('pbs_tasks')) {
            Schema::create('pbs_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pbs_server_id')->constrained('pbs_servers')->cascadeOnDelete();
                $table->string('upid');
                $table->string('type')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('starttime')->nullable();
                $table->timestamp('endtime')->nullable();
                $table->string('user')->nullable();
                $table->string('worker_id')->nullable();
                $table->text('summary')->nullable();
                $table->timestamps();
                $table->unique(['pbs_server_id', 'upid']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pbs_tasks');
        Schema::dropIfExists('pbs_datastores');
        Schema::dropIfExists('pbs_servers');
    }
};

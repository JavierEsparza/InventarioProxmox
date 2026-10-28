<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('cluster_audit_runs', function(Blueprint $t){$t->id();$t->string('cluster_name');$t->string('status')->default('running');$t->unsignedInteger('total')->default(0);$t->unsignedInteger('processed')->default(0);$t->timestamp('started_at')->nullable();$t->timestamp('finished_at')->nullable();$t->timestamps();$t->index(['cluster_name','created_at']);});
  Schema::create('cluster_audit_results', function(Blueprint $t){$t->id();$t->foreignId('run_id')->constrained('cluster_audit_runs')->cascadeOnDelete();$t->unsignedBigInteger('qemu_id')->nullable();$t->string('cluster_name');$t->string('server_name');$t->string('host')->nullable();$t->string('os')->nullable();$t->string('result',40);$t->string('infransible',30)->nullable();$t->string('sudo_status',30)->nullable();$t->string('vip_status',30)->nullable();$t->string('keepalived_status',30)->nullable();$t->string('haproxy_status',30)->nullable();$t->json('secondary_ips')->nullable();$t->text('detail')->nullable();$t->timestamp('audited_at')->nullable();$t->timestamps();$t->index(['run_id','result']);$t->index(['cluster_name','server_name']);});
 }
 public function down(): void { Schema::dropIfExists('cluster_audit_results'); Schema::dropIfExists('cluster_audit_runs'); }
};

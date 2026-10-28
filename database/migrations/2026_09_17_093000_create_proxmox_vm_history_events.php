<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('proxmox_vm_snapshots', function(Blueprint $t){$t->id();$t->string('cluster_name');$t->dateTime('snapshot_at');$t->unsignedBigInteger('vmid');$t->string('name')->nullable();$t->string('node_id')->nullable();$t->string('id_proxmox')->nullable();$t->timestamps();$t->index(['cluster_name','snapshot_at']);$t->unique(['cluster_name','snapshot_at','vmid']);});
  Schema::create('proxmox_vm_events', function(Blueprint $t){$t->id();$t->string('cluster_name');$t->unsignedBigInteger('vmid');$t->string('event_type',20);$t->dateTime('event_at');$t->string('old_node_id')->nullable();$t->string('new_node_id')->nullable();$t->string('old_id_proxmox')->nullable();$t->string('new_id_proxmox')->nullable();$t->timestamps();$t->index(['cluster_name','event_at','event_type']);$t->index(['cluster_name','vmid']);});
 }
 public function down(): void {Schema::dropIfExists('proxmox_vm_events');Schema::dropIfExists('proxmox_vm_snapshots');}
};

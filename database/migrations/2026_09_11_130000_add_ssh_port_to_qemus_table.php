<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('qemus', 'ssh_port')) Schema::table('qemus', fn (Blueprint $table) => $table->unsignedSmallInteger('ssh_port')->default(22)->after('infransible_note'));
        if (Schema::hasTable('cluster_audit_results') && !Schema::hasColumn('cluster_audit_results', 'ssh_port')) Schema::table('cluster_audit_results', fn (Blueprint $table) => $table->unsignedSmallInteger('ssh_port')->default(22)->after('host'));
    }
    public function down(): void {
        if (Schema::hasColumn('cluster_audit_results', 'ssh_port')) Schema::table('cluster_audit_results', fn (Blueprint $table) => $table->dropColumn('ssh_port'));
        if (Schema::hasColumn('qemus', 'ssh_port')) Schema::table('qemus', fn (Blueprint $table) => $table->dropColumn('ssh_port'));
    }
};

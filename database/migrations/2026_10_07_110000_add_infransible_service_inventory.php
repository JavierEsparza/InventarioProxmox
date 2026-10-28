<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('qemus')) {
            Schema::table('qemus', function (Blueprint $table) {
                if (!Schema::hasColumn('qemus', 'infransible_services')) $table->json('infransible_services')->nullable()->after('wazuh_version_infransible');
                if (!Schema::hasColumn('qemus', 'infransible_services_checked_at')) $table->timestamp('infransible_services_checked_at')->nullable()->after('infransible_services');
            });
        }
        if (Schema::hasTable('cluster_audit_results')) {
            Schema::table('cluster_audit_results', function (Blueprint $table) {
                if (!Schema::hasColumn('cluster_audit_results', 'services')) $table->json('services')->nullable()->after('haproxy_status');
                if (!Schema::hasColumn('cluster_audit_results', 'services_checked_at')) $table->timestamp('services_checked_at')->nullable()->after('services');
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('qemus')) Schema::table('qemus', function (Blueprint $table) { foreach (['infransible_services_checked_at', 'infransible_services'] as $c) if (Schema::hasColumn('qemus', $c)) $table->dropColumn($c); });
        if (Schema::hasTable('cluster_audit_results')) Schema::table('cluster_audit_results', function (Blueprint $table) { foreach (['services_checked_at', 'services'] as $c) if (Schema::hasColumn('cluster_audit_results', $c)) $table->dropColumn($c); });
    }
};

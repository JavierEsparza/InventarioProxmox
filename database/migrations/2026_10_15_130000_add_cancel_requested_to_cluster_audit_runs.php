<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cluster_audit_runs') && !Schema::hasColumn('cluster_audit_runs', 'cancel_requested')) {
            Schema::table('cluster_audit_runs', function (Blueprint $table) {
                $table->boolean('cancel_requested')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cluster_audit_runs') && Schema::hasColumn('cluster_audit_runs', 'cancel_requested')) {
            Schema::table('cluster_audit_runs', fn (Blueprint $table) => $table->dropColumn('cancel_requested'));
        }
    }
};

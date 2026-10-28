<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            if (!Schema::hasColumn('nodes', 'skip_ssh_sync')) {
                $table->boolean('skip_ssh_sync')->default(false)->after('support_license_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            if (Schema::hasColumn('nodes', 'skip_ssh_sync')) {
                $table->dropColumn('skip_ssh_sync');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            if (!Schema::hasColumn('qemus', 'zabbix_status_infransible')) {
                $table->string('zabbix_status_infransible', 32)->nullable()->after('infransible_note');
            }
            if (!Schema::hasColumn('qemus', 'zabbix_version_infransible')) {
                $table->string('zabbix_version_infransible', 64)->nullable()->after('zabbix_status_infransible');
            }
            if (!Schema::hasColumn('qemus', 'wazuh_status_infransible')) {
                $table->string('wazuh_status_infransible', 32)->nullable()->after('zabbix_version_infransible');
            }
            if (!Schema::hasColumn('qemus', 'wazuh_version_infransible')) {
                $table->string('wazuh_version_infransible', 64)->nullable()->after('wazuh_status_infransible');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qemus', function (Blueprint $table) {
            foreach ([
                'zabbix_status_infransible',
                'zabbix_version_infransible',
                'wazuh_status_infransible',
                'wazuh_version_infransible',
            ] as $column) {
                if (Schema::hasColumn('qemus', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

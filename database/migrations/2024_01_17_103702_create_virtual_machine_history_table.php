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
        Schema::create('virtual_machine_histories', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('cluster_name');
            $table->bigInteger('cluster_qemus');
            $table->bigInteger('cluster_cpu');
            $table->bigInteger('cluster_memory');
            $table->bigInteger('cluster_disk');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_machine_histories');
    }
};

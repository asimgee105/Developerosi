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
        Schema::create('dora_metrics_daily', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->date('date');
            $table->integer('deployment_frequency')->default(0); // Count of successful deployments
            $table->integer('lead_time_seconds')->default(0); // Lead time average in seconds
            $table->integer('mttr_seconds')->default(0); // Mean Time to Recover average in seconds
            $table->decimal('change_failure_rate', 5, 2)->default(0.00); // CFR percentage e.g., 15.50
            $table->timestamps();

            $table->foreign('workspace_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dora_metrics_daily');
    }
};

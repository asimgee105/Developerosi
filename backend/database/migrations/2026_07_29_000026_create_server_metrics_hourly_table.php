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
        Schema::create('server_metrics_hourly', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('server_id');
            $table->decimal('cpu_utilization_percentage', 5, 2)->default(0.00);
            $table->decimal('ram_utilization_percentage', 5, 2)->default(0.00);
            $table->decimal('disk_utilization_percentage', 5, 2)->default(0.00);
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->foreign('server_id')
                ->references('id')
                ->on('servers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_metrics_hourly');
    }
};

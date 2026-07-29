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
        Schema::create('training_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('model_id');
            $table->timestamp('started_at')->useCurrent();
            $table->integer('duration_seconds')->default(0);
            $table->integer('tokens_processed')->default(0);
            $table->timestamps();

            $table->foreign('model_id')
                ->references('id')
                ->on('sovereign_models')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_jobs');
    }
};

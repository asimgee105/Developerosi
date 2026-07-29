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
        Schema::create('qa_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pr_id');
            $table->string('status')->default('queued'); // queued, running, success, failed, flaked
            $table->json('generated_script')->nullable();
            $table->string('video_artifact_url')->nullable();
            $table->integer('flake_retries')->default(0);
            $table->timestamps();

            $table->foreign('pr_id')
                ->references('id')
                ->on('git_pull_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_runs');
    }
};

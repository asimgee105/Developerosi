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
        Schema::create('git_pull_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('repository_id');
            $table->integer('number');
            $table->string('title');
            $table->string('source_branch');
            $table->string('target_branch');
            $table->string('author_username');
            $table->string('status')->default('open'); // open, merged, closed
            $table->string('merge_commit_sha')->nullable();
            $table->timestamps();

            $table->foreign('repository_id')
                ->references('id')
                ->on('git_repositories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_pull_requests');
    }
};

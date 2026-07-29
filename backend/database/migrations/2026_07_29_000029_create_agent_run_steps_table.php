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
        Schema::create('agent_run_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_run_id');
            $table->string('step_name');
            $table->string('step_type')->default('planning'); // planning, coding, test, lint
            $table->string('status')->default('pending'); // pending, running, success, failed
            $table->text('output')->nullable();
            $table->integer('duration_ms')->default(0);
            $table->timestamps();

            $table->foreign('agent_run_id')
                ->references('id')
                ->on('agent_runs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_run_steps');
    }
};

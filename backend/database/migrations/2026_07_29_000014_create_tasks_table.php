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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('sprint_id')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reporter_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('task'); // task, bug, story
            $table->string('status')->default('todo'); // todo, inprogress, inreview, done
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->integer('story_points')->default(0);
            $table->integer('position')->default(0); // position inside Kanban column
            $table->uuid('parent_id')->nullable(); // subtasks linkage
            $table->timestamps();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('sprint_id')
                ->references('id')
                ->on('sprints')
                ->onDelete('set null');

            $table->foreign('parent_id')
                ->references('id')
                ->on('tasks')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

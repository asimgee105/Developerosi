<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Get all tasks in a sprint, grouped by status.
     */
    public function indexBySprint(Request $request, string $sprintId): JsonResponse
    {
        $tasks = Task::where('sprint_id', $sprintId)
            ->orderBy('position', 'asc')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    /**
     * Create a new task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'string'],
            'sprint_id' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:task,bug,story'],
            'priority' => ['required', 'string', 'in:low,medium,high,critical'],
            'story_points' => ['integer', 'min:0'],
        ]);

        // Determine position (append to bottom of column)
        $position = Task::where('project_id', $validated['project_id'])
            ->where('status', 'todo')
            ->count();

        $task = Task::create([
            'project_id' => $validated['project_id'],
            'sprint_id' => $validated['sprint_id'] ?? null,
            'assignee_id' => null,
            'reporter_id' => $request->user()?->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'status' => 'todo',
            'priority' => $validated['priority'],
            'story_points' => $validated['story_points'] ?? 0,
            'position' => $position,
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => $task,
        ], 201);
    }

    /**
     * Update task status (moving between todo, inprogress, inreview, done columns).
     */
    public function updateStatus(Request $request, string $taskId): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:todo,inprogress,inreview,done'],
        ]);

        $task = Task::findOrFail($taskId);
        $oldStatus = $task->status;
        $newStatus = $request->input('status');

        if ($oldStatus !== $newStatus) {
            DB::transaction(function () use ($task, $oldStatus, $newStatus) {
                // Reorder old status column items
                Task::where('project_id', $task->project_id)
                    ->where('status', $oldStatus)
                    ->where('position', '>', $task->position)
                    ->decrement('position');

                // Determine new position in target column
                $newPosition = Task::where('project_id', $task->project_id)
                    ->where('status', $newStatus)
                    ->count();

                $task->update([
                    'status' => $newStatus,
                    'position' => $newPosition,
                ]);
            });
        }

        return response()->json([
            'message' => 'Task status updated successfully.',
            'task' => $task,
        ]);
    }

    /**
     * Reorder task position inside its current column.
     */
    public function updatePosition(Request $request, string $taskId): JsonResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $task = Task::findOrFail($taskId);
        $oldPosition = $task->position;
        $newPosition = $request->input('position');

        if ($oldPosition !== $newPosition) {
            DB::transaction(function () use ($task, $oldPosition, $newPosition) {
                if ($newPosition > $oldPosition) {
                    // Decrement items between old and new position
                    Task::where('project_id', $task->project_id)
                        ->where('status', $task->status)
                        ->whereBetween('position', [$oldPosition + 1, $newPosition])
                        ->decrement('position');
                } else {
                    // Increment items between new and old position
                    Task::where('project_id', $task->project_id)
                        ->where('status', $task->status)
                        ->whereBetween('position', [$newPosition, $oldPosition - 1])
                        ->increment('position');
                }

                $task->update(['position' => $newPosition]);
            });
        }

        return response()->json([
            'message' => 'Task position updated successfully.',
            'task' => $task,
        ]);
    }
}

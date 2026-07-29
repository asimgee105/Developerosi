<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Get all projects for a workspace.
     */
    public function index(Request $request, string $workspaceId): JsonResponse
    {
        $projects = Project::where('workspace_id', $workspaceId)
            ->with(['sprints'])
            ->get();

        return response()->json(['projects' => $projects]);
    }

    /**
     * Create a new project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project = Project::create([
            'workspace_id' => $validated['workspace_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $project,
        ], 201);
    }

    /**
     * Create a new sprint inside a project.
     */
    public function storeSprint(Request $request, string $projectId): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'goal' => ['nullable', 'string'],
        ]);

        $sprint = Sprint::create([
            'project_id' => $projectId,
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'goal' => $validated['goal'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Sprint created successfully.',
            'sprint' => $sprint,
        ], 201);
    }
}

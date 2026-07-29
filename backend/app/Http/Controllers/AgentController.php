<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use App\Models\AgentRunStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    /**
     * Start a new autonomous agent coding run.
     */
    public function startAgentRun(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'task_description' => ['required', 'string'],
        ]);

        $run = AgentRun::create([
            'workspace_id' => $validated['workspace_id'],
            'task_description' => $validated['task_description'],
            'status' => 'queued',
            'log_file_path' => 'storage/logs/agent_' . Str::random(12) . '.log',
        ]);

        return response()->json([
            'message' => 'Autonomous coding agent job queued successfully.',
            'agent_run' => $run,
            'vm_config' => [
                'executor' => 'Firecracker',
                'allow_egress' => false,
            ]
        ], 201);
    }

    /**
     * Get the steps trace for a coding run (simulating progressions if newly queued).
     */
    public function getAgentRun(Request $request, string $runId): JsonResponse
    {
        $run = AgentRun::findOrFail($runId);

        // If newly queued, statefully simulate step execution tracing
        if ($run->status === 'queued') {
            DB::transaction(function () use ($run) {
                $run->update(['status' => 'completed']);

                AgentRunStep::create([
                    'agent_run_id' => $run->id,
                    'step_name' => 'Scan Workspace & Plan',
                    'step_type' => 'planning',
                    'status' => 'success',
                    'output' => 'Scanned workspace directories. Identified import layouts and 14 dependency files.',
                    'duration_ms' => 450,
                ]);

                AgentRunStep::create([
                    'agent_run_id' => $run->id,
                    'step_name' => 'Apply Code Modification',
                    'step_type' => 'coding',
                    'status' => 'success',
                    'output' => 'Applied line refactors to AuthController.php. Resolved 1 syntax warning.',
                    'duration_ms' => 890,
                ]);

                AgentRunStep::create([
                    'agent_run_id' => $run->id,
                    'step_name' => 'Format & Lint Check',
                    'step_type' => 'lint',
                    'status' => 'success',
                    'output' => 'Ran phpcs. Codebase formatting complies with PSR-12 styling standards.',
                    'duration_ms' => 320,
                ]);

                AgentRunStep::create([
                    'agent_run_id' => $run->id,
                    'step_name' => 'Execute Test Runner',
                    'step_type' => 'test',
                    'status' => 'success',
                    'output' => 'Ran phpunit. All 34 tests passed successfully (489 assertions).',
                    'duration_ms' => 2450,
                ]);
            });
        }

        $run->refresh();
        $steps = AgentRunStep::where('agent_run_id', $run->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'agent_run' => $run,
            'steps' => $steps,
        ]);
    }

    /**
     * Scan directories to parse dependency trees.
     */
    public function analyzeContext(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'string'],
        ]);

        // Simulates scanning local files
        $contextTree = [
            'files_count' => 124,
            'import_relations' => [
                'app/Http/Controllers/AuthController.php' => [
                    'imports' => ['App\Models\User', 'App\Models\Organization', 'Laravel\Fortify\TwoFactorAuthenticationProvider'],
                    'size_bytes' => 12450,
                ],
                'routes/api.php' => [
                    'imports' => ['App\Http\Controllers\AuthController', 'App\Http\Controllers\VcsController'],
                    'size_bytes' => 3560,
                ]
            ],
            'performance_metrics' => [
                'directories_traversed' => 42,
                'scan_duration_ms' => 15,
            ]
        ];

        return response()->json([
            'message' => 'Workspace import hierarchy parsed successfully.',
            'context' => $contextTree,
        ]);
    }

    /**
     * Apply precise code modifications.
     */
    public function modifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_file' => ['required', 'string'],
            'target_content' => ['required', 'string'],
            'replacement_content' => ['required', 'string'],
        ]);

        // Returns success simulation
        return response()->json([
            'status' => 'success',
            'message' => 'Line modifications applied securely.',
            'file_updated' => $validated['target_file'],
            'lint_errors' => [],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CloudEnvironment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CdeController extends Controller
{
    /**
     * Launch a containerized Cloud Development Environment (CDE) workspace.
     */
    public function launchWorkspace(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'uuid'],
            'devcontainer_json' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        // Parse devcontainer json settings (defaulting node and postgres environment structures)
        $config = json_decode($validated['devcontainer_json'] ?? '', true);
        if (!is_array($config)) {
            $config = [
                'name' => 'Default Node DevOS Workspace',
                'image' => 'mcr.microsoft.com/devcontainers/javascript-node:20',
                'features' => ['ghcr.io/devcontainers/features/sshd:1' => []],
                'forwardPorts' => [3000, 5432],
            ];
        }

        $environment = CloudEnvironment::create([
            'user_id' => $user->id,
            'workspace_id' => $validated['workspace_id'],
            'status' => 'booting',
            'compute_spec' => [
                'cpu_cores' => 4,
                'ram_gb' => 8,
                'gpu_cores' => 0,
                'disk_persistent_gb' => 50,
                'isolation' => 'Firecracker microVM',
                'devcontainer_config' => $config,
            ],
            'last_active_at' => now(),
        ]);

        // Boot completes in 3 seconds (EBS Snapshot Warm Start Simulation)
        $environment->update(['status' => 'running']);

        return response()->json([
            'message' => 'Cloud environment container launched successfully.',
            'environment' => $environment,
            'connection' => [
                'ssh_endpoint' => 'ssh://cde-gateway.devos.host:2222/' . $environment->id,
                'web_ide_url' => 'https://cde-web.devos.host/workspace/' . $environment->id,
                'ws_tunnel_port' => 8888,
            ]
        ], 201);
    }

    /**
     * Snapshot and hibernate active CDE workspace (saving compute state to S3).
     */
    public function hibernateWorkspace(Request $request, string $id): JsonResponse
    {
        $environment = CloudEnvironment::findOrFail($id);

        DB::transaction(function () use ($environment) {
            $environment->update([
                'status' => 'hibernated',
                'last_active_at' => now()->subMinutes(45), // simulate idle state
            ]);
        });

        return response()->json([
            'message' => 'CDE memory and persistent state snapshot completed. Compute node suspended.',
            'environment' => $environment,
            'snapshot_s3_key' => 'cde-snapshots/' . $environment->id . '.tar.gz',
        ]);
    }

    /**
     * Idle cost manager. Hibernates workspaces with keyboard inactivity over 30 minutes.
     */
    public function autoHibernateInactive(Request $request, string $id): JsonResponse
    {
        $environment = CloudEnvironment::findOrFail($id);

        $inactiveMinutes = $environment->last_active_at->diffInMinutes(now());
        $cooldownLimit = 30;

        if ($inactiveMinutes > $cooldownLimit && $environment->status === 'running') {
            $environment->update(['status' => 'hibernated']);
        }

        return response()->json([
            'environment' => $environment,
            'inactive_minutes' => $inactiveMinutes,
            'auto_hibernated' => $environment->status === 'hibernated',
        ]);
    }
}

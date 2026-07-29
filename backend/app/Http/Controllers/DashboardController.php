<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\DashboardLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Get the active dashboard and layout for the workspace.
     * Auto-creates a default layout if none exists.
     */
    public function getActiveDashboard(Request $request, string $workspaceId): JsonResponse
    {
        $user = $request->user();

        // 1. Fetch dashboard or create default
        $dashboard = Dashboard::where('workspace_id', $workspaceId)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->orderBy('is_default', 'desc')
            ->first();

        if (!$dashboard) {
            $dashboard = DB::transaction(function () use ($workspaceId, $user) {
                $newDashboard = Dashboard::create([
                    'workspace_id' => $workspaceId,
                    'user_id' => $user->id,
                    'name' => 'Mission Control',
                    'is_default' => true,
                ]);

                // Define default widget configurations
                $defaultLayout = [
                    [
                        'id' => 'ai_assistant',
                        'w' => 6, 'h' => 4, 'x' => 0, 'y' => 0,
                        'title' => 'DevOS AI Copilot',
                        'properties' => []
                    ],
                    [
                        'id' => 'sprint_kanban',
                        'w' => 6, 'h' => 4, 'x' => 6, 'y' => 0,
                        'title' => 'Assigned Tasks',
                        'properties' => []
                    ],
                    [
                        'id' => 'git_activity',
                        'w' => 6, 'h' => 3, 'x' => 0, 'y' => 4,
                        'title' => 'VCS Active Branches & PRs',
                        'properties' => []
                    ],
                    [
                        'id' => 'financial_metrics',
                        'w' => 6, 'h' => 3, 'x' => 6, 'y' => 4,
                        'title' => 'Financial Metrics & Invoices',
                        'properties' => []
                    ]
                ];

                DashboardLayout::create([
                    'dashboard_id' => $newDashboard->id,
                    'layout_data' => $defaultLayout,
                ]);

                return $newDashboard;
            });
        }

        return response()->json([
            'dashboard' => $dashboard,
            'layout' => $dashboard->layout->layout_data,
        ]);
    }

    /**
     * Update the layout structure for a dashboard.
     */
    public function updateLayout(Request $request, string $dashboardId): JsonResponse
    {
        $request->validate([
            'layout_data' => ['required', 'array'],
        ]);

        $dashboard = Dashboard::findOrFail($dashboardId);

        // Ensure the logged in user owns this dashboard workspace
        $user = $request->user();
        $isMember = DB::table('organization_members')
            ->where('organization_id', $dashboard->workspace_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $layout = DashboardLayout::updateOrCreate(
            ['dashboard_id' => $dashboardId],
            ['layout_data' => $request->input('layout_data')]
        );

        return response()->json([
            'message' => 'Dashboard layout updated successfully.',
            'layout' => $layout->layout_data,
        ]);
    }
}

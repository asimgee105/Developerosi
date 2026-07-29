<?php

namespace Tests\Feature;

use App\Models\Dashboard;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test active dashboard auto-provisions a default layout on first request.
     */
    public function test_active_dashboard_auto_provisions_default_layout(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/workspaces/{$organization->id}/dashboards/active");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'dashboard' => ['id', 'workspace_id', 'user_id', 'name', 'is_default'],
            'layout' => [
                '*' => ['id', 'w', 'h', 'x', 'y', 'title']
            ]
        ]);

        $this->assertDatabaseHas('dashboards', [
            'workspace_id' => $organization->id,
            'user_id' => $user->id,
            'is_default' => 1,
        ]);
    }

    /**
     * Test user can successfully update their dashboard layout configuration.
     */
    public function test_user_can_update_dashboard_layout(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $dashboard = Dashboard::create([
            'workspace_id' => $organization->id,
            'user_id' => $user->id,
            'name' => 'Custom Board',
        ]);

        $newLayout = [
            [
                'id' => 'ai_assistant',
                'w' => 12, 'h' => 4, 'x' => 0, 'y' => 0,
                'title' => 'Stretched AI Panel'
            ]
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/v1/dashboards/{$dashboard->id}/layout", [
                'layout_data' => $newLayout
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Dashboard layout updated successfully.',
            'layout' => $newLayout
        ]);

        $this->assertDatabaseHas('dashboard_layouts', [
            'dashboard_id' => $dashboard->id,
            'layout_data' => json_encode($newLayout)
        ]);
    }
}

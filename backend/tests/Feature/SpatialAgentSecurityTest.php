<?php

namespace Tests\Feature;

use App\Models\AutonomousAgent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpatialAgentSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test WebXR spatial collaborative war room creations.
     */
    public function test_spatial_room_creation(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Wayne Spatial Hub',
            'slug' => 'wayne-spatial',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/spatial/rooms/create', [
                'workspace_id' => $organization->id,
                'room_name' => 'Visual Kubernetes Debug Room',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'room_name' => 'Visual Kubernetes Debug Room',
        ]);
        $response->assertJsonStructure([
            'room' => ['id', 'workspace_id', 'room_name', 'layout_state'],
            'webxr_connection' => ['websocket_url', 'webrtc_signaling_server']
        ]);
    }

    /**
     * Test LangGraph business agent objectives, budget limit locks, and hardcoded halt kill switches.
     */
    public function test_agent_swarm_objectives_budgets_and_kill_switches(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Wayne Spatial Hub',
            'slug' => 'wayne-spatial',
        ]);

        // 1. Success launch (budget is 500, cost is 150)
        $launchResponse = $this->actingAs($user)
            ->postJson('/api/v1/agents/objectives/launch', [
                'workspace_id' => $organization->id,
                'role' => 'Sales',
                'objective' => 'Autonomously pitch Wayne cloud migrations to target tech startups.',
                'estimated_cost_usd' => 150.00,
            ]);

        $launchResponse->assertStatus(200);
        $launchResponse->assertJsonFragment([
            'status' => 'Active',
        ]);
        $this->assertDatabaseHas('agent_action_logs', [
            'action_taken' => 'AI B2B Sales scraped LinkedIn leads, drafted personalized MSA pitches, and emailed clients.',
        ]);

        $agentId = $launchResponse->json('agent.id');

        // 2. Budget Limit violation block (cost is 900, limit is 500)
        $budgetLimitResponse = $this->actingAs($user)
            ->postJson('/api/v1/agents/objectives/launch', [
                'workspace_id' => $organization->id,
                'role' => 'Sales',
                'objective' => 'Deploy high scale ad campaign across global search sites.',
                'estimated_cost_usd' => 900.00,
            ]);

        $budgetLimitResponse->assertStatus(402);
        $this->assertDatabaseHas('agent_action_logs', [
            'agent_id' => $agentId,
            'action_taken' => 'Objective aborted: Budget Limit Exceeded. Required $900, Limit $500',
        ]);

        // 3. Un-bypassable Halt kill switch activation
        $haltResponse = $this->actingAs($user)
            ->postJson("/api/v1/agents/{$agentId}/halt");

        $haltResponse->assertStatus(200);
        $haltResponse->assertJsonFragment([
            'status' => 'Halted',
        ]);

        // 4. Verification that subsequent launches are strictly blocked (403)
        $blockedResponse = $this->actingAs($user)
            ->postJson('/api/v1/agents/objectives/launch', [
                'workspace_id' => $organization->id,
                'role' => 'Sales',
                'objective' => 'Attempt new tasks',
                'estimated_cost_usd' => 20.00,
            ]);

        $blockedResponse->assertStatus(403);
    }

    /**
     * Test PQC hybrid keys exchange endpoints.
     */
    public function test_post_quantum_key_exchange(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/security/pqc/public-key');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'algorithm_suite' => 'CNSA 2.0 Hybrid KEM',
        ]);
        $response->assertJsonStructure([
            'classical_kem' => ['type', 'public_key'],
            'post_quantum_kem' => ['type', 'public_key', 'nist_standard'],
            'server_session_nonce',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AgentRun;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test starting an agent run queues the job.
     */
    public function test_agent_run_start_queues_job(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Labs',
            'slug' => 'acme-labs',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/agent/run', [
                'workspace_id' => $organization->id,
                'task_description' => 'Fix the undefinedgetCurrentOtp call in AuthTest.php',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'status' => 'queued',
            'task_description' => 'Fix the undefinedgetCurrentOtp call in AuthTest.php',
        ]);

        $this->assertDatabaseHas('agent_runs', [
            'workspace_id' => $organization->id,
            'status' => 'queued',
        ]);
    }

    /**
     * Test querying agent run simulates steps trace and shifts status.
     */
    public function test_agent_run_simulates_steps_trace(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Labs',
            'slug' => 'acme-labs',
        ]);

        $run = AgentRun::create([
            'workspace_id' => $organization->id,
            'task_description' => 'Implement concurrency checks',
            'status' => 'queued',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/agent/runs/{$run->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'completed',
        ]);

        $response->assertJsonStructure([
            'steps' => [
                '*' => ['id', 'agent_run_id', 'step_name', 'step_type', 'status', 'output', 'duration_ms']
            ]
        ]);

        // Assert database records
        $this->assertDatabaseHas('agent_run_steps', [
            'agent_run_id' => $run->id,
            'step_type' => 'planning',
        ]);

        $this->assertDatabaseHas('agent_run_steps', [
            'agent_run_id' => $run->id,
            'step_type' => 'test',
        ]);
    }

    /**
     * Test scanning directories to parse dependency trees.
     */
    public function test_analyze_context_directory_scanner(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Labs',
            'slug' => 'acme-labs',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/agent/context/analyze', [
                'workspace_id' => $organization->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'context' => ['files_count', 'import_relations', 'performance_metrics']
        ]);
    }

    /**
     * Test applying line refactors.
     */
    public function test_agent_code_modifier_actions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/agent/action/modify-code', [
                'target_file' => 'app/Http/Controllers/AuthController.php',
                'target_content' => 'old code',
                'replacement_content' => 'new code',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
            'file_updated' => 'app/Http/Controllers/AuthController.php',
        ]);
    }
}

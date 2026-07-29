<?php

namespace Tests\Feature;

use App\Models\AgentSession;
use App\Models\LlmRequestLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiGatewayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unified AI Chat Completion gateway and telemetry cost log tracking.
     */
    public function test_ai_chat_completions_returns_correct_response_and_logs_telemetry(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/chat/completions', [
                'model' => 'gemini-2.0-flash',
                'messages' => [
                    ['role' => 'user', 'content' => 'Tell me about database indexes.']
                ]
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'object',
            'created',
            'model',
            'choices' => [
                '*' => [
                    'index',
                    'message' => ['role', 'content'],
                    'finish_reason'
                ]
            ],
            'usage' => ['prompt_tokens', 'completion_tokens', 'total_tokens']
        ]);

        // Verify database log entry was recorded
        $this->assertDatabaseHas('llm_request_logs', [
            'workspace_id' => $organization->id,
            'user_id' => $user->id,
            'model_used' => 'gemini-2.0-flash',
        ]);

        $log = LlmRequestLog::first();
        $this->assertGreaterThan(0, $log->prompt_tokens);
        $this->assertGreaterThan(0, $log->completion_tokens);
        $this->assertGreaterThan(0, $log->cost_usd);
    }

    /**
     * Test trigger agent workflow logs steps and sessions tracking.
     */
    public function test_agent_task_trigger_records_logs_and_returns_session(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/agents/task', [
                'prompt' => 'Scaffold payment controller and tests.'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'workspace_id',
            'user_id',
            'status',
            'prompt',
            'steps_log' => [
                '*' => ['timestamp', 'agent', 'message']
            ]
        ]);

        $this->assertEquals('completed', $response->json('status'));

        // Assert database persistence
        $this->assertDatabaseHas('agent_sessions', [
            'workspace_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Test fetching status returns log data.
     */
    public function test_get_agent_task_status_returns_steps(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $session = AgentSession::create([
            'workspace_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'prompt' => 'Setup telemetry',
            'steps_log' => [['timestamp' => '12:00:00', 'agent' => 'Planner', 'message' => 'Init']]
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/ai/agents/task/{$session->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'completed',
            'prompt' => 'Setup telemetry'
        ]);
    }

    /**
     * Test silent context synchronization.
     */
    public function test_sync_context_succeeds(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ai/context/sync', [
                'file_path' => 'app/Models/User.php',
                'cursor_line' => 12,
                'code_delta' => '// modification delta'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'timestamp']);
    }
}

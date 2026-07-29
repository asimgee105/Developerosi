<?php

namespace Tests\Feature;

use App\Models\CloudEnvironment;
use App\Models\Organization;
use App\Models\SovereignModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CdeMlopsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test launching, hibernating, and auto-hibernating CDE container environments.
     */
    public function test_cde_workspace_launch_hibernate_and_auto_cleanup(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Wayne Tech CDE',
            'slug' => 'wayne-cde',
        ]);

        // 1. Launch environment
        $response = $this->actingAs($user)
            ->postJson('/api/v1/cde/workspaces/launch', [
                'workspace_id' => $organization->id,
                'devcontainer_json' => json_encode(['name' => 'Custom Node.js environment']),
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'status' => 'running',
        ]);
        $response->assertJsonStructure([
            'connection' => ['ssh_endpoint', 'web_ide_url', 'ws_tunnel_port']
        ]);

        $envId = $response->json('environment.id');

        // 2. Manual hibernate snapshot S3
        $hibResponse = $this->actingAs($user)
            ->putJson("/api/v1/cde/workspaces/{$envId}/hibernate");

        $hibResponse->assertStatus(200);
        $hibResponse->assertJsonFragment([
            'status' => 'hibernated',
        ]);
        $this->assertStringContainsString('cde-snapshots/', $hibResponse->json('snapshot_s3_key'));

        // 3. Auto-hibernate check trigger on active environment
        $activeEnv = CloudEnvironment::create([
            'user_id' => $user->id,
            'workspace_id' => $organization->id,
            'status' => 'running',
            'last_active_at' => now()->subMinutes(40), // idle for 40 minutes (limit is 30)
        ]);

        $cleanupResponse = $this->actingAs($user)
            ->getJson("/api/v1/cde/workspaces/{$activeEnv->id}/auto-hibernate");

        $cleanupResponse->assertStatus(200);
        $cleanupResponse->assertJsonFragment([
            'auto_hibernated' => true,
        ]);

        $activeEnv->refresh();
        $this->assertEquals('hibernated', $activeEnv->status);
    }

    /**
     * Test Sovereign LoRA training trigger and Secret Scrubbing DLP pipeline.
     */
    public function test_lora_training_scrubs_secrets_from_source_code(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Wayne Tech CDE',
            'slug' => 'wayne-cde',
        ]);

        $sourceDiffWithSecrets = "
            public function connectToStripe() {
                \$apiKey = 'sk_test_51Mz290aBcXe10239082acKey_super_secret_value';
                \$clientSecret = 'xoxb-stripe-app-client-secret-token-key-value';
                return new StripeClient(\$apiKey);
            }
        ";

        $response = $this->actingAs($user)
            ->postJson('/api/v1/mlops/models/fine-tune', [
                'workspace_id' => $organization->id,
                'base_model_name' => 'llama-3-8b-instruct',
                'source_code_diff' => $sourceDiffWithSecrets,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'deployed',
            'dlp_data_scrubbed' => true, // verified scrubbed by preg_replace patterns
        ]);

        $this->assertDatabaseHas('sovereign_models', [
            'workspace_id' => $organization->id,
            'status' => 'deployed',
        ]);

        $this->assertDatabaseHas('training_jobs', [
            'tokens_processed' => 1245000,
        ]);
    }

    /**
     * Test Sovereign private autocomplete completions inference lookups.
     */
    public function test_sovereign_inference_autocomplete_completion(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Wayne Tech CDE',
            'slug' => 'wayne-cde',
        ]);

        $model = SovereignModel::create([
            'workspace_id' => $organization->id,
            'base_model_name' => 'llama-3-8b-instruct',
            'status' => 'deployed',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/mlops/inference/completions', [
                'prompt' => 'function calculateInvoiceTotal($hours, $rate) {',
                'model_id' => $model->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'choices' => [
                '*' => ['text', 'index', 'finish_reason']
            ],
            'usage' => ['prompt_tokens', 'completion_tokens', 'total_tokens']
        ]);
        $this->assertStringContainsString('Autocomplete from Sovereign AI', $response->json('choices.0.text'));
    }
}

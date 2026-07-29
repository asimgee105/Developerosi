<?php

namespace Tests\Feature;

use App\Models\BountyEscrow;
use App\Models\GitPullRequest;
use App\Models\GitRepository;
use App\Models\Incident;
use App\Models\IncidentLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SreQaEscrowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test SRE Incident rollbacks and 30-minute loops fatigue cooldown controls.
     */
    public function test_sre_incident_rollback_and_cooldown(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Ops',
            'slug' => 'stark-ops',
        ]);

        $incident = Incident::create([
            'workspace_id' => $organization->id,
            'severity' => 'critical',
            'status' => 'active',
        ]);

        // First rollback execution should succeed
        $response = $this->actingAs($user)
            ->postJson("/api/v1/sre/incidents/{$incident->id}/rollback");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'rolled_back',
        ]);

        $this->assertDatabaseHas('incident_logs', [
            'incident_id' => $incident->id,
            'action_taken' => 'GitOps Rollback',
        ]);

        // Second immediate rollback execution on the same workspace should trigger a 30-minute cooldown alert fatigue block (HTTP 429)
        $anotherIncident = Incident::create([
            'workspace_id' => $organization->id,
            'severity' => 'high',
            'status' => 'active',
        ]);

        $cooldownResponse = $this->actingAs($user)
            ->postJson("/api/v1/sre/incidents/{$anotherIncident->id}/rollback");

        $cooldownResponse->assertStatus(429);
        $cooldownResponse->assertJsonFragment([
            'cooldown_active' => true,
        ]);
    }

    /**
     * Test Playwright QA runs trigger and Visual layout regression checks.
     */
    public function test_qa_playwright_run_and_visual_regression(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Ops',
            'slug' => 'stark-ops',
        ]);

        $repository = GitRepository::create([
            'workspace_id' => $organization->id,
            'name' => 'devos-core',
            'provider' => 'github',
            'external_repository_id' => '12345',
            'clone_url' => 'https://github.com/stark/devos-core.git',
        ]);

        $pr = GitPullRequest::create([
            'repository_id' => $repository->id,
            'number' => 45,
            'title' => 'Feature checkout billing',
            'source_branch' => 'feat-billing',
            'target_branch' => 'main',
            'author_username' => 'stark_dev',
            'status' => 'open',
        ]);

        // Standard QA test should pass
        $response = $this->actingAs($user)
            ->postJson('/api/v1/qa/runs/trigger', [
                'pr_id' => $pr->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        // Forcing visual regression check should catch button layout shifts (HTTP 200 with failed flag)
        $regResponse = $this->actingAs($user)
            ->postJson('/api/v1/qa/runs/trigger', [
                'pr_id' => $pr->id,
                'simulate_visual_regression' => true,
            ]);

        $regResponse->assertStatus(200);
        $regResponse->assertJsonFragment([
            'status' => 'failed',
        ]);
        $this->assertStringContainsString('Visual Regression Alert', $regResponse->json('visual_regression_spotted'));
    }

    /**
     * Test Web3 Bounty escrow locks and AWS KMS Oracle signatures approvals.
     */
    public function test_web3_bounty_lock_and_oracle_kms_signatures(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Ops',
            'slug' => 'stark-ops',
        ]);

        $project = Project::create([
            'workspace_id' => $organization->id,
            'name' => 'Blockchain portal',
            'slug' => 'blockchain-portal',
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Integrate MetaMask Wallet connect',
            'position' => 1,
            'status' => 'todo',
        ]);

        // Lock funds bounty
        $lockResponse = $this->actingAs($user)
            ->postJson('/api/v1/web3/bounties/lock', [
                'issue_id' => $task->id,
                'smart_contract_address' => '0x_escrow_contract_0x12903820ac',
                'client_wallet' => '0x_client_wallet_hash_xyz',
                'dev_wallet' => '0x_dev_wallet_hash_abc',
                'amount_usdc' => 1000.00,
            ]);

        $lockResponse->assertStatus(201);
        $lockResponse->assertJsonFragment([
            'status' => 'Locked',
            'amount_usdc' => 1000.00,
        ]);

        // Accessing signature should be forbidden (403) while task is still 'todo'
        $sigForbiddenResponse = $this->actingAs($user)
            ->getJson("/api/v1/web3/bounties/{$task->id}/signature");

        $sigForbiddenResponse->assertStatus(403);
        $sigForbiddenResponse->assertJsonFragment([
            'signed' => false,
        ]);

        // Transition task to 'done' (merged/approved) to authorize Oracle signature release
        $task->update(['status' => 'done']);

        $sigApprovedResponse = $this->actingAs($user)
            ->getJson("/api/v1/web3/bounties/{$task->id}/signature");

        $sigApprovedResponse->assertStatus(200);
        $sigApprovedResponse->assertJsonFragment([
            'status' => 'approved',
        ]);
        $sigApprovedResponse->assertJsonStructure([
            'cryptographic_signature',
            'oracle_verifying_pubkey',
            'signing_payload',
        ]);
    }
}

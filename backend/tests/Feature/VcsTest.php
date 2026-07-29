<?php

namespace Tests\Feature;

use App\Models\DoraMetricDaily;
use App\Models\GitPullRequest;
use App\Models\GitRepository;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VcsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test VCS webhook processes pull requests correctly.
     */
    public function test_vcs_webhook_ingests_pull_requests_and_saves_them(): void
    {
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $response = $this->postJson('/api/v1/vcs/webhooks/github', [
            'workspace_id' => $organization->id,
            'repository' => [
                'name' => 'devos-core',
                'clone_url' => 'https://github.com/developerosi/devos-core.git',
            ],
            'pull_request' => [
                'number' => 12,
                'title' => 'feat: support stateful firecracker ssh connections',
                'state' => 'open',
                'merged' => false,
                'head' => ['ref' => 'feat/firecracker-ssh'],
                'base' => ['ref' => 'main'],
                'user' => ['login' => 'coder123'],
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'pr_id']);

        $this->assertDatabaseHas('git_repositories', [
            'workspace_id' => $organization->id,
            'name' => 'devos-core',
        ]);

        $this->assertDatabaseHas('git_pull_requests', [
            'number' => 12,
            'title' => 'feat: support stateful firecracker ssh connections',
            'status' => 'open',
        ]);
    }

    /**
     * Test DORA metrics retrieval auto-seeds and aggregates values correctly.
     */
    public function test_dora_metrics_retrieval_returns_metrics_summary(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $user->update(['active_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/workspaces/{$organization->id}/dora");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => [
                'deployment_frequency',
                'lead_time_minutes',
                'mttr_minutes',
                'change_failure_rate'
            ],
            'history' => [
                '*' => ['id', 'workspace_id', 'date', 'deployment_frequency', 'lead_time_seconds', 'mttr_seconds', 'change_failure_rate']
            ]
        ]);

        $this->assertDatabaseHas('dora_metrics_daily', [
            'workspace_id' => $organization->id,
        ]);
    }

    /**
     * Test repository health returns complexity and static audits logs.
     */
    public function test_repository_health_returns_static_audits(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        
        $repository = GitRepository::create([
            'workspace_id' => $organization->id,
            'name' => 'devos-core',
            'clone_url' => 'https://github.com/developerosi/devos-core.git',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/repositories/{$repository->id}/health");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'repository' => ['id', 'name'],
            'metrics' => [
                'cyclomatic_complexity',
                'test_coverage_percentage',
                'vulnerabilities_count',
                'duplication_percentage',
                'last_audit_at'
            ],
            'rating'
        ]);
    }
}

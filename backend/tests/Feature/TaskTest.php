<?php

namespace Tests\Feature;

use App\Models\GitRepository;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test task creation is saved at the bottom of the todo column.
     */
    public function test_task_creation_appends_to_bottom_of_column(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        $project = Project::create([
            'workspace_id' => $organization->id,
            'name' => 'Mobile App',
        ]);

        // Create initial tasks
        Task::create([
            'project_id' => $project->id,
            'title' => 'Task A',
            'type' => 'task',
            'status' => 'todo',
            'position' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tasks', [
                'project_id' => $project->id,
                'title' => 'Task B',
                'type' => 'bug',
                'priority' => 'high',
                'story_points' => 3,
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'title' => 'Task B',
            'status' => 'todo',
            'position' => 1, // Appended below Task A
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Task B',
            'position' => 1,
        ]);
    }

    /**
     * Test dragging task to another column updates its status and shifts older column items.
     */
    public function test_task_status_update_reorders_columns(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        $project = Project::create([
            'workspace_id' => $organization->id,
            'name' => 'Mobile App',
        ]);

        $task1 = Task::create([
            'project_id' => $project->id,
            'title' => 'Task 1',
            'type' => 'task',
            'status' => 'todo',
            'position' => 0,
        ]);

        $task2 = Task::create([
            'project_id' => $project->id,
            'title' => 'Task 2',
            'type' => 'task',
            'status' => 'todo',
            'position' => 1,
        ]);

        // Move task 1 to inprogress
        $response = $this->actingAs($user)
            ->putJson("/api/v1/tasks/{$task1->id}/status", [
                'status' => 'inprogress'
            ]);

        $response->assertStatus(200);

        // Verify task 2 was shifted up to position 0 in todo column
        $task2->refresh();
        $this->assertEquals(0, $task2->position);

        // Verify task 1 is now inprogress at position 0
        $task1->refresh();
        $this->assertEquals('inprogress', $task1->status);
        $this->assertEquals(0, $task1->position);
    }

    /**
     * Test drag reordering position inside the same column.
     */
    public function test_task_position_reordering_inside_same_column(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);
        $project = Project::create([
            'workspace_id' => $organization->id,
            'name' => 'Mobile App',
        ]);

        $task0 = Task::create([
            'project_id' => $project->id,
            'title' => 'Task 0',
            'type' => 'task',
            'status' => 'todo',
            'position' => 0,
        ]);

        $task1 = Task::create([
            'project_id' => $project->id,
            'title' => 'Task 1',
            'type' => 'task',
            'status' => 'todo',
            'position' => 1,
        ]);

        $task2 = Task::create([
            'project_id' => $project->id,
            'title' => 'Task 2',
            'type' => 'task',
            'status' => 'todo',
            'position' => 2,
        ]);

        // Move Task 0 to position 2
        $response = $this->actingAs($user)
            ->putJson("/api/v1/tasks/{$task0->id}/position", [
                'position' => 2
            ]);

        $response->assertStatus(200);

        // Verify Task 1 shifted up to position 0
        $task1->refresh();
        $this->assertEquals(0, $task1->position);

        // Verify Task 2 shifted up to position 1
        $task2->refresh();
        $this->assertEquals(1, $task2->position);

        // Verify Task 0 is now at position 2
        $task0->refresh();
        $this->assertEquals(2, $task0->position);
    }

    /**
     * Test Git webhook with task identifier (e.g. DEV-102) transitions status automatically.
     */
    public function test_git_webhook_transitions_task_status_and_creates_git_link(): void
    {
        $organization = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $project = Project::create([
            'workspace_id' => $organization->id,
            'name' => 'Mobile App',
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => '[DEV-102] Implement concurrent device limits warning',
            'type' => 'task',
            'status' => 'todo',
            'position' => 0,
        ]);

        // Mock GitHub Webhook payload for PR open
        $response = $this->postJson('/api/v1/vcs/webhooks/github', [
            'workspace_id' => $organization->id,
            'repository' => [
                'name' => 'devos-core',
                'clone_url' => 'https://github.com/developerosi/devos-core.git',
            ],
            'pull_request' => [
                'number' => 42,
                'title' => 'feat: support DEV-102 concurrent login audits',
                'state' => 'open',
                'merged' => false,
                'head' => ['ref' => 'feature/DEV-102-auth'],
                'base' => ['ref' => 'main'],
                'user' => ['login' => 'coder123'],
            ]
        ]);

        $response->assertStatus(200);

        // Verify task transitioned to inreview
        $task->refresh();
        $this->assertEquals('inreview', $task->status);

        // Verify Git link was recorded
        $this->assertDatabaseHas('task_git_links', [
            'task_id' => $task->id,
            'pull_request_number' => 42,
            'branch_name' => 'feature/DEV-102-auth',
        ]);

        // Mock GitHub Webhook payload for PR merge (deploy)
        $responseMerge = $this->postJson('/api/v1/vcs/webhooks/github', [
            'workspace_id' => $organization->id,
            'repository' => [
                'name' => 'devos-core',
                'clone_url' => 'https://github.com/developerosi/devos-core.git',
            ],
            'pull_request' => [
                'number' => 42,
                'title' => 'feat: support DEV-102 concurrent login audits',
                'state' => 'closed',
                'merged' => true,
                'merge_commit_sha' => 'sha_merge_42_123',
                'head' => ['ref' => 'feature/DEV-102-auth'],
                'base' => ['ref' => 'main'],
                'user' => ['login' => 'coder123'],
            ]
        ]);

        $responseMerge->assertStatus(200);

        // Verify task transitioned to done
        $task->refresh();
        $this->assertEquals('done', $task->status);
    }
}

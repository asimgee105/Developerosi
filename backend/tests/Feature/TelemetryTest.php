<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test servers index fetches and seeds web-node-01 and db-node-primary.
     */
    public function test_telemetry_servers_index_seeds_cluster(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Data Center',
            'slug' => 'stark-dc',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/workspaces/{$organization->id}/servers");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'servers' => [
                '*' => ['id', 'workspace_id', 'name', 'ip_address', 'status', 'OS', 'CPU_cores', 'RAM_mb']
            ]
        ]);

        $this->assertDatabaseHas('servers', [
            'workspace_id' => $organization->id,
            'name' => 'web-node-01',
        ]);
    }

    /**
     * Test server registration.
     */
    public function test_server_node_registration(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Data Center',
            'slug' => 'stark-dc',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/servers', [
                'workspace_id' => $organization->id,
                'name' => 'gpu-node-02',
                'ip_address' => '192.168.1.105',
                'OS' => 'Ubuntu 24.04 LTS',
                'CPU_cores' => 16,
                'RAM_mb' => 65536,
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'gpu-node-02',
            'status' => 'online',
        ]);

        $this->assertDatabaseHas('servers', [
            'workspace_id' => $organization->id,
            'name' => 'gpu-node-02',
        ]);
    }

    /**
     * Test resource metrics hourly logs.
     */
    public function test_server_metrics_utilization_history(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Data Center',
            'slug' => 'stark-dc',
        ]);

        $server = Server::create([
            'workspace_id' => $organization->id,
            'name' => 'web-node-01',
            'ip_address' => '192.168.1.100',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/servers/{$server->id}/metrics");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'metrics' => [
                '*' => ['id', 'server_id', 'cpu_utilization_percentage', 'ram_utilization_percentage', 'disk_utilization_percentage', 'measured_at']
            ]
        ]);

        $this->assertDatabaseHas('server_metrics_hourly', [
            'server_id' => $server->id,
        ]);
    }

    /**
     * Test eBPF packet logs diagnostics retrieval.
     */
    public function test_ebpf_packet_logs(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Data Center',
            'slug' => 'stark-dc',
        ]);

        $server = Server::create([
            'workspace_id' => $organization->id,
            'name' => 'web-node-01',
            'ip_address' => '192.168.1.100',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/servers/{$server->id}/ebpf-network-logs");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'logs' => [
                '*' => ['id', 'server_id', 'source_ip', 'destination_ip', 'port', 'duration_ms', 'bytes_sent', 'bytes_received', 'protocol']
            ]
        ]);

        $this->assertDatabaseHas('ebpf_network_logs', [
            'server_id' => $server->id,
            'protocol' => 'HTTPS',
        ]);
    }

    /**
     * Test SSH connection login audits retrieval.
     */
    public function test_ssh_login_audits(): void
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Stark Data Center',
            'slug' => 'stark-dc',
        ]);

        $server = Server::create([
            'workspace_id' => $organization->id,
            'name' => 'web-node-01',
            'ip_address' => '192.168.1.100',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/servers/{$server->id}/ssh-audits");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'audits' => [
                '*' => ['id', 'server_id', 'username', 'ip_address', 'status', 'authenticated_at']
            ]
        ]);

        $this->assertDatabaseHas('ssh_access_audits', [
            'server_id' => $server->id,
            'username' => 'admin',
        ]);
    }
}

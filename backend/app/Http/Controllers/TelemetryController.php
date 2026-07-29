<?php

namespace App\Http\Controllers;

use App\Models\EbpfNetworkLog;
use App\Models\Server;
use App\Models\ServerMetricHourly;
use App\Models\SshAccessAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelemetryController extends Controller
{
    /**
     * Get all servers for a workspace (seeding default cluster if none exist).
     */
    public function getServers(Request $request, string $workspaceId): JsonResponse
    {
        $hasServers = Server::where('workspace_id', $workspaceId)->exists();

        if (!$hasServers) {
            $this->seedServers($workspaceId);
        }

        $servers = Server::where('workspace_id', $workspaceId)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json(['servers' => $servers]);
    }

    /**
     * Register a new server node.
     */
    public function createServer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'OS' => ['nullable', 'string', 'max:255'],
            'CPU_cores' => ['required', 'integer', 'min:1'],
            'RAM_mb' => ['required', 'integer', 'min:512'],
        ]);

        $server = Server::create([
            'workspace_id' => $validated['workspace_id'],
            'name' => $validated['name'],
            'ip_address' => $validated['ip_address'],
            'status' => 'online',
            'OS' => $validated['OS'] ?? 'Ubuntu 24.04 LTS',
            'CPU_cores' => $validated['CPU_cores'],
            'RAM_mb' => $validated['RAM_mb'],
        ]);

        return response()->json([
            'message' => 'Server registered successfully.',
            'server' => $server,
        ], 201);
    }

    /**
     * Get hourly system utilization metrics (auto-populating simulated chart data if empty).
     */
    public function getServerMetrics(Request $request, string $serverId): JsonResponse
    {
        $hasMetrics = ServerMetricHourly::where('server_id', $serverId)->exists();

        if (!$hasMetrics) {
            $this->seedHourlyMetrics($serverId);
        }

        $metrics = ServerMetricHourly::where('server_id', $serverId)
            ->orderBy('measured_at', 'asc')
            ->take(12)
            ->get();

        return response()->json(['metrics' => $metrics]);
    }

    /**
     * Get eBPF kernel network logs (auto-generating network packet flows if empty).
     */
    public function getEbpfNetworkLogs(Request $request, string $serverId): JsonResponse
    {
        $hasLogs = EbpfNetworkLog::where('server_id', $serverId)->exists();

        if (!$hasLogs) {
            $this->seedEbpfNetworkLogs($serverId);
        }

        $logs = EbpfNetworkLog::where('server_id', $serverId)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json(['logs' => $logs]);
    }

    /**
     * Get SSH login connection audit trail history.
     */
    public function getSshAudits(Request $request, string $serverId): JsonResponse
    {
        $hasAudits = SshAccessAudit::where('server_id', $serverId)->exists();

        if (!$hasAudits) {
            $this->seedSshAudits($serverId);
        }

        $audits = SshAccessAudit::where('server_id', $serverId)
            ->orderBy('authenticated_at', 'desc')
            ->take(20)
            ->get();

        return response()->json(['audits' => $audits]);
    }

    /**
     * Seed default servers.
     */
    private function seedServers(string $workspaceId): void
    {
        DB::transaction(function () use ($workspaceId) {
            Server::create([
                'workspace_id' => $workspaceId,
                'name' => 'web-node-01',
                'ip_address' => '192.168.1.100',
                'status' => 'online',
                'OS' => 'Ubuntu 24.04 LTS',
                'CPU_cores' => 4,
                'RAM_mb' => 8192,
            ]);

            Server::create([
                'workspace_id' => $workspaceId,
                'name' => 'db-node-primary',
                'ip_address' => '192.168.1.101',
                'status' => 'online',
                'OS' => 'Debian 12 Bookworm',
                'CPU_cores' => 8,
                'RAM_mb' => 16384,
            ]);
        });
    }

    /**
     * Seed simulated 12 hours load logs.
     */
    private function seedHourlyMetrics(string $serverId): void
    {
        DB::transaction(function () use ($serverId) {
            for ($i = 11; $i >= 0; $i--) {
                ServerMetricHourly::create([
                    'server_id' => $serverId,
                    'cpu_utilization_percentage' => rand(15, 85) + (rand(0, 99) / 100),
                    'ram_utilization_percentage' => 45.5 + ($i * 1.2) + (rand(-5, 5) / 10),
                    'disk_utilization_percentage' => 60.12,
                    'measured_at' => now()->subHours($i),
                ]);
            }
        });
    }

    /**
     * Seed eBPF kernel network logs.
     */
    private function seedEbpfNetworkLogs(string $serverId): void
    {
        DB::transaction(function () use ($serverId) {
            EbpfNetworkLog::create([
                'server_id' => $serverId,
                'source_ip' => '192.168.1.100',
                'destination_ip' => '172.217.16.142', // google.com DNS lookup
                'port' => 443,
                'duration_ms' => 12.45,
                'bytes_sent' => 840,
                'bytes_received' => 2450,
                'protocol' => 'HTTPS',
            ]);

            EbpfNetworkLog::create([
                'server_id' => $serverId,
                'source_ip' => '203.0.113.45', // external API request
                'destination_ip' => '192.168.1.100',
                'port' => 8000,
                'duration_ms' => 42.10,
                'bytes_sent' => 1250,
                'bytes_received' => 31250,
                'protocol' => 'HTTP',
            ]);
        });
    }

    /**
     * Seed SSH connection audits.
     */
    private function seedSshAudits(string $serverId): void
    {
        DB::transaction(function () use ($serverId) {
            SshAccessAudit::create([
                'server_id' => $serverId,
                'username' => 'admin',
                'ip_address' => '192.168.1.5',
                'status' => 'success',
                'authenticated_at' => now()->subMinutes(12),
            ]);

            SshAccessAudit::create([
                'server_id' => $serverId,
                'username' => 'root',
                'ip_address' => '203.0.113.99', // external bruteforce attempt
                'status' => 'failed',
                'authenticated_at' => now()->subHours(2),
            ]);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'ip_address',
        'status',
        'OS',
        'CPU_cores',
        'RAM_mb',
    ];

    /**
     * Get the workspace that owns the server node.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'workspace_id');
    }

    /**
     * Get the eBPF network logs for this server.
     */
    public function networkLogs(): HasMany
    {
        return $this->hasMany(EbpfNetworkLog::class);
    }

    /**
     * Get the hourly utilization metrics for this server.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetricHourly::class);
    }

    /**
     * Get the SSH security login audits for this server.
     */
    public function sshAudits(): HasMany
    {
        return $this->hasMany(SshAccessAudit::class);
    }
}

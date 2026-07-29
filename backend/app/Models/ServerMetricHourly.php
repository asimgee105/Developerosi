<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetricHourly extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'server_metrics_hourly';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'cpu_utilization_percentage',
        'ram_utilization_percentage',
        'disk_utilization_percentage',
        'measured_at',
    ];

    /**
     * Get the server linked to this metrics log.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}

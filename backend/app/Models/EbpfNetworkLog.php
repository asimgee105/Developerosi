<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EbpfNetworkLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ebpf_network_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'source_ip',
        'destination_ip',
        'port',
        'duration_ms',
        'bytes_sent',
        'bytes_received',
        'protocol',
    ];

    /**
     * Get the server linked to this log.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}

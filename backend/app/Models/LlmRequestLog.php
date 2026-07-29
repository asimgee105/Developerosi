<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmRequestLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'user_id',
        'model_used',
        'prompt_tokens',
        'completion_tokens',
        'cost_usd',
        'latency_ms',
    ];

    /**
     * Get the workspace (organization) that owns the log.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'workspace_id');
    }

    /**
     * Get the user that executed the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

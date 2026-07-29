<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dashboard extends Model
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
        'name',
        'is_default',
    ];

    /**
     * Get the workspace (organization) that owns the dashboard.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'workspace_id');
    }

    /**
     * Get the user that owns the dashboard (optional, if personal dashboard).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the layout configuration for this dashboard.
     */
    public function layout(): HasOne
    {
        return $this->hasOne(DashboardLayout::class);
    }
}

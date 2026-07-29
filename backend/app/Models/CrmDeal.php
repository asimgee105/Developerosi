<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmDeal extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crm_deals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'contact_id',
        'title',
        'amount',
        'stage',
        'probability_percentage',
        'expected_close_date',
    ];

    /**
     * Get the workspace that owns the deal.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'workspace_id');
    }

    /**
     * Get the contact linked to this deal.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    /**
     * Get the interactions linked to this deal.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(CrmInteraction::class, 'deal_id');
    }
}

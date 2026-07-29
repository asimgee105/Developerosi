<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmContact extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crm_contacts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'status',
        'source',
    ];

    /**
     * Get the workspace that owns the contact.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'workspace_id');
    }

    /**
     * Get the deals linked to this contact.
     */
    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'contact_id');
    }

    /**
     * Get the interactions linked to this contact.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(CrmInteraction::class, 'contact_id');
    }
}

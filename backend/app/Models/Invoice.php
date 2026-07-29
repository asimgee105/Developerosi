<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'stripe_invoice_id',
        'client_name',
        'client_email',
        'amount',
        'status',
        'due_at',
        'paid_at',
    ];

    /**
     * Get the workspace (organization) that owns the invoice.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'workspace_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BountyEscrow extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bounty_escrows';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'issue_id',
        'smart_contract_address',
        'client_wallet',
        'dev_wallet',
        'amount_usdc',
        'status',
        'oracle_tx_hash',
    ];

    /**
     * Get the issue (task ticket) funded by this escrow.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'issue_id');
    }
}

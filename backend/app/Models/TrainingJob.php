<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingJob extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'training_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model_id',
        'started_at',
        'duration_seconds',
        'tokens_processed',
    ];

    /**
     * Get the sovereign model target linked to this fine-tuning job.
     */
    public function sovereignModel(): BelongsTo
    {
        return $this->belongsTo(SovereignModel::class, 'model_id');
    }
}

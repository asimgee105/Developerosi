<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SovereignModel extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sovereign_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'base_model_name',
        'status',
        'checkpoint_s3_url',
        'training_loss',
    ];

    /**
     * Get the workspace hosting this custom Sovereign model.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'workspace_id');
    }

    /**
     * Get all fine-tuning training jobs executed on this model adapter.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(TrainingJob::class, 'model_id');
    }
}

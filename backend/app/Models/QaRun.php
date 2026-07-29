<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaRun extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'qa_runs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pr_id',
        'status',
        'generated_script',
        'video_artifact_url',
        'flake_retries',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'generated_script' => 'array',
    ];

    /**
     * Get the pull request verified by this QA run.
     */
    public function pullRequest(): BelongsTo
    {
        return $this->belongsTo(GitPullRequest::class, 'pr_id');
    }
}

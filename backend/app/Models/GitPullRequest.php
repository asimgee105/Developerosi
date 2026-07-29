<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitPullRequest extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'repository_id',
        'number',
        'title',
        'source_branch',
        'target_branch',
        'author_username',
        'status',
        'merge_commit_sha',
    ];

    /**
     * Get the repository that owns the pull request.
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class, 'repository_id');
    }
}

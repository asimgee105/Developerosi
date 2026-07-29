<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SshAccessAudit extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ssh_access_audits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'username',
        'ip_address',
        'status',
        'authenticated_at',
    ];

    /**
     * Get the server linked to this SSH connection log.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}

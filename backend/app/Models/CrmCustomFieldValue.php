<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCustomFieldValue extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crm_custom_field_values';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'custom_field_id',
        'entity_id',
        'field_value',
    ];

    /**
     * Get the custom field definition linked to this value.
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CrmCustomField::class, 'custom_field_id');
    }
}

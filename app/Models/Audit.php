<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single audit trail entry recording who changed what, when and from where.
 * `old_values` / `new_values` hold only the attributes that were affected.
 */
class Audit extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'user_name',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }
}

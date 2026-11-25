<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Metadata of a module scaffolded by the panel CRUD generator. Mirrors the
 * `crud_definitions` table so generated modules can be listed and audited.
 */
class CrudDefinition extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'module',
        'model',
        'resource_key',
        'table_name',
        'database',
        'label',
        'icon',
        'group',
        'description',
        'fields',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fields' => 'array',
        ];
    }
}

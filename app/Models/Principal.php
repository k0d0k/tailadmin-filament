<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A principal is the top level of the account hierarchy. Every tenant (and
 * consequently every user) belongs to exactly one principal.
 */
class Principal extends Model
{
    use Auditable, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'code',
        'email',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

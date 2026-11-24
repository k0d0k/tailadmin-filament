<?php

namespace App\Models;

use App\Jobs\ProvisionTenant;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tenant sits between a principal and its users. Users are optionally
 * attached to a tenant, which always belongs to a principal. Creating a tenant
 * provisions its own database plus a tenant administrator account.
 */
class Tenant extends Model
{
    use Auditable, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'principal_id',
        'name',
        'code',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'provisioned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $tenant): void {
            if (config('tenancy.provision_on_create')) {
                ProvisionTenant::dispatch($tenant);
            }
        });
    }

    /**
     * Whether the tenant database has been created and migrated.
     */
    public function isProvisioned(): bool
    {
        return $this->provisioned_at !== null;
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Principal::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

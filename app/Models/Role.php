<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use Auditable, HasSlug;

    public const SCOPE_CENTRAL = 'central';
    public const SCOPE_TENANT = 'tenant';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'label',
        'scope',
        'description',
        'slug',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => 'string',
        ];
    }

    /**
     * Menus this role has access to, with the granted abilities on the pivot.
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'role_menu')
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete', 'can_export'])
            ->withTimestamps();
    }

    /**
     * Human readable label, falling back to a headline of the role name.
     */
    public function displayName(): string
    {
        $label = $this->getAttribute('label');

        return filled($label) ? (string) $label : \Illuminate\Support\Str::headline((string) $this->name);
    }

    /**
     * Localised scope label (Central / Tenant).
     */
    public function scopeLabel(): string
    {
        return __('ui.roles.scopes.'.($this->getAttribute('scope') ?: self::SCOPE_CENTRAL));
    }

    /**
     * Scopes offered by the Role & Access screen.
     *
     * @return array<string, string>
     */
    public static function scopeOptions(): array
    {
        return [
            self::SCOPE_CENTRAL => __('ui.roles.scopes.central'),
            self::SCOPE_TENANT => __('ui.roles.scopes.tenant'),
        ];
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Support\Panel\ResourceRegistry;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

class Menu extends Model
{
    use Auditable, HasSlug;

    public const TYPE_GROUP = 'group';
    public const TYPE_LINK = 'link';
    public const TYPE_HEADER = 'header';

    /**
     * Attribute the slug is derived from.
     */
    protected string $slugFrom = 'label';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'parent_id',
        'title',
        'key',
        'type',
        'label',
        'icon',
        'url',
        'route_name',
        'resource',
        'module_key',
        'permission',
        'sort',
        'is_active',
        'is_visible',
        'target_blank',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'target_blank' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Roles granted access to this menu, with the per-action abilities.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu')
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete', 'can_export'])
            ->withTimestamps();
    }

    /**
     * Localised title with a sensible fallback chain (locale → id → en → key).
     */
    public function label(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $title = (array) ($this->getAttribute('title') ?? []);

        foreach ([$locale, 'id', 'en', $this->key, $this->getAttribute('label')] as $candidate) {
            if (filled($candidate) && filled($title[$candidate] ?? null)) {
                return (string) $title[$candidate];
            }
        }

        return (string) ($title['id'] ?? $title['en'] ?? $this->getAttribute('label') ?? $this->key ?? '');
    }

    /**
     * Whether this node groups other menus.
     */
    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    /**
     * Whether this node is a section header without a destination.
     */
    public function isHeader(): bool
    {
        return $this->type === self::TYPE_HEADER;
    }

    /**
     * Resolve the target URL for this menu, either from the linked Filament
     * resource/page or from the manual URL.
     */
    public function resolveUrl(): ?string
    {
        if (filled($this->resource) && class_exists($this->resource)) {
            $class = $this->resource;

            if (is_subclass_of($class, Resource::class)) {
                return $class::getUrl('index');
            }

            if (method_exists($class, 'getUrl')) {
                return $class::getUrl();
            }
        }

        if (filled($this->route_name) && Route::has($this->route_name)) {
            return route($this->route_name);
        }

        return $this->url();
    }

    /**
     * Resolve the target URL for this menu inside the standalone TailAdmin
     * panel, using the manual URL first and the panel resource registry as a
     * fallback. Filament-only menus (resource classes) return null here.
     */
    public function panelUrl(): ?string
    {
        if ($url = $this->url()) {
            return $url;
        }

        if (filled($this->route_name) && Route::has($this->route_name)) {
            return route($this->route_name);
        }

        if (filled($this->resource) && ResourceRegistry::has($this->resource)) {
            return route('panel.index', $this->resource);
        }

        return null;
    }

    /**
     * Normalise the manual URL column into an absolute URL.
     */
    protected function url(): ?string
    {
        if (blank($this->getAttribute('url'))) {
            return null;
        }

        $url = (string) $this->getAttribute('url');

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Support\Panel\ResourceRegistry;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use Auditable, HasSlug;

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
        'label',
        'icon',
        'url',
        'resource',
        'permission',
        'sort',
        'is_active',
        'target_blank',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

        if (blank($this->url)) {
            return null;
        }

        if (str_starts_with($this->url, 'http://') || str_starts_with($this->url, 'https://')) {
            return $this->url;
        }

        return url($this->url);
    }

    /**
     * Resolve the target URL for this menu inside the standalone TailAdmin
     * panel, using the manual URL first and the panel resource registry as a
     * fallback. Filament-only menus (resource classes) return null here.
     */
    public function panelUrl(): ?string
    {
        if (filled($this->url)) {
            if (str_starts_with($this->url, 'http://') || str_starts_with($this->url, 'https://')) {
                return $this->url;
            }

            return url($this->url);
        }

        if (filled($this->resource) && ResourceRegistry::has($this->resource)) {
            return route('panel.index', $this->resource);
        }

        return null;
    }
}

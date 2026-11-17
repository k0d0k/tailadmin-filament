<?php

namespace App\Models;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
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
}

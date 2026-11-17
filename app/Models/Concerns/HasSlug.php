<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Keeps a unique `slug` column in sync with a human readable source attribute
 * so records can be addressed by slug instead of their numeric id. A slug that
 * is already present (auto generated before or entered by the user) is kept as
 * is, which keeps record URLs stable when the source attribute changes.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            $model->slug = $model->slugifyForStorage();
        });
    }

    protected function slugifyForStorage(): string
    {
        $source = trim((string) ($this->slug ?? ''));

        if ($source === '') {
            $source = (string) $this->{$this->slugSource()};
        }

        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($slug)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query()->where('slug', $slug);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    protected function slugSource(): string
    {
        return $this->slugFrom ?? 'name';
    }
}

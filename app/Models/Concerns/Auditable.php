<?php

namespace App\Models\Concerns;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Records create / update / delete events of a model into the `audits` table.
 * Hidden attributes (e.g. passwords) and timestamps are never stored and only
 * the attributes that actually changed are kept on update.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->recordAudit('created'));
        static::updated(fn (Model $model) => $model->recordAudit('updated'));
        static::deleted(fn (Model $model) => $model->recordAudit('deleted'));
    }

    protected function recordAudit(string $event): void
    {
        if (! Schema::hasTable('audits')) {
            return;
        }

        $old = [];
        $new = [];

        if ($event === 'created') {
            $new = $this->auditableAttributes((array) $this->getAttributes());
        } elseif ($event === 'deleted') {
            $old = $this->auditableAttributes((array) $this->getAttributes());
        } else {
            $original = $this->getOriginal();

            foreach (array_keys($this->getChanges()) as $key) {
                if ($this->auditableExcluded($key)) {
                    continue;
                }

                $old[$key] = $original[$key] ?? null;
                $new[$key] = $this->getAttribute($key);
            }

            if ($old === [] && $new === []) {
                return;
            }
        }

        Audit::query()->create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'old_values' => $old !== [] ? $old : null,
            'new_values' => $new !== [] ? $new : null,
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 500, ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function auditableAttributes(array $attributes): array
    {
        return collect($attributes)
            ->reject(fn ($value, string $key): bool => $this->auditableExcluded($key))
            ->all();
    }

    protected function auditableExcluded(string $key): bool
    {
        return in_array($key, array_merge((array) $this->getHidden(), ['created_at', 'updated_at']), true);
    }
}

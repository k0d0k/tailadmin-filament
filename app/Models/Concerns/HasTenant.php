<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Scopes a model to the active tenant and stamps new rows with its id. Used by
 * modules generated against the tenant database.
 */
trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::creating(function (Model $model): void {
            $tenant = app()->bound('active_tenant') ? app('active_tenant') : null;

            if ($tenant !== null && $model->getAttribute('tenant_id') === null) {
                $model->setAttribute('tenant_id', $tenant->getKey());
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = app()->bound('active_tenant') ? app('active_tenant') : null;

            if ($tenant !== null) {
                $builder->where($builder->getModel()->qualifyColumn('tenant_id'), $tenant->getKey());
            }
        });
    }

    public function tenantId(): ?int
    {
        $value = $this->getAttribute('tenant_id');

        return $value === null ? null : (int) $value;
    }
}

<?php

namespace App\Support;

use App\Models\Permission;
use Illuminate\Support\Str;

/**
 * Central place for the RBAC naming conventions used across the panel.
 */
class Acl
{
    /**
     * The role that is allowed to do anything.
     */
    public const SUPER_ADMIN_ROLE = 'super_admin';

    /**
     * The actions a resource exposes, mapped to the Filament policy methods
     * that are gated on them.
     */
    public const ACTIONS = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
    ];

    /**
     * Build the permission name for an action on a resource key, e.g. `view_any_user`.
     */
    public static function name(string $action, string $resource): string
    {
        return Str::of($action)->append('_', $resource)->toString();
    }

    /**
     * All permission names belonging to a resource key.
     *
     * @return array<int, string>
     */
    public static function permissionsFor(string $resource): array
    {
        return array_map(
            fn (string $action): string => self::name($action, $resource),
            self::ACTIONS,
        );
    }

    /**
     * Derive the resource key (used in permission names) from a model class.
     */
    public static function resourceKey(string $modelClass): string
    {
        return Str::snake(class_basename($modelClass));
    }

    /**
     * Permissions grouped by resource, keyed by permission id so the result can
     * feed a `CheckboxList::relationship()` component directly.
     *
     * @return array<string, array<int, string>>
     */
    public static function groupedPermissionOptions(): array
    {
        $groups = [];

        foreach (Permission::query()->orderBy('name')->get() as $permission) {
            $name = $permission->name;
            $hasAction = str_contains($name, '_');

            $resource = $hasAction ? Str::afterLast($name, '_') : 'other';
            $action = $hasAction ? Str::beforeLast($name, '_') : $name;

            $groupLabel = $resource === 'other' ? 'Lainnya' : Str::headline($resource);
            $groups[$groupLabel] ??= [];
            $groups[$groupLabel][$permission->id] = Str::headline($action);
        }

        ksort($groups);

        return $groups;
    }
}

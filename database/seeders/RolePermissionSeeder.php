<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Acl;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the RBAC baseline: one permission per action for every built-in
 * module and the application roles.
 *
 * - `super_admin` bypasses every check and is granted all permissions.
 * - `principal` administers the tenants and users belonging to its principal.
 * - `tenant` administers the users belonging to its tenant.
 * - `user` is a plain end user without access to the admin menus.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Resource keys handled by the built-in modules.
     *
     * @var array<int, string>
     */
    public const RESOURCES = ['user', 'role', 'permission', 'menu', 'principal', 'tenant'];

    public function run(): void
    {
        // The permission cache must be flushed before creating new permissions.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::RESOURCES as $resource) {
            foreach (Acl::permissionsFor($resource) as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }

        // Standalone permission guarding the CRUD Generator page.
        Permission::findOrCreate('generate_crud', 'web');

        $superAdmin = Role::findOrCreate(Acl::SUPER_ADMIN_ROLE, 'web');
        $superAdmin->syncPermissions(Permission::all());

        $principal = Role::findOrCreate('principal', 'web');
        $principal->syncPermissions($this->permissionNames([
            'principal' => ['view_any', 'view', 'update'],
            'tenant' => Acl::ACTIONS,
            'user' => Acl::ACTIONS,
            'role' => ['view_any', 'view'],
            'menu' => ['view_any', 'view'],
        ]));

        $tenant = Role::findOrCreate('tenant', 'web');
        $tenant->syncPermissions($this->permissionNames([
            'tenant' => ['view_any', 'view'],
            'user' => ['view_any', 'view', 'create', 'update'],
        ]));

        // Plain end user: no admin menu access at all.
        Role::findOrCreate('user', 'web');

        // Drop the role from the previous baseline that is no longer used.
        Role::query()->where('name', 'operator')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Expand a `resource => actions` matrix into permission names.
     *
     * @param  array<string, array<int, string>>  $matrix
     * @return array<int, string>
     */
    protected function permissionNames(array $matrix): array
    {
        $names = [];

        foreach ($matrix as $resource => $actions) {
            foreach ($actions as $action) {
                $names[] = Acl::name($action, $resource);
            }
        }

        return $names;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Acl;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the RBAC baseline: one permission per action for every built-in
 * module and the application roles.
 *
 * - `super_admin` bypasses every check and is granted all permissions.
 * - `operator` co-administers the central database.
 * - `principal` administers the tenants and users belonging to its principal.
 * - tenant roles (`tenant_admin`, `manajemen`, ...) operate inside a tenant.
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

        // Standalone permission guarding the menu builder page.
        Permission::findOrCreate('manage_menu', 'web');

        // Standalone permission guarding the report builder page.
        Permission::findOrCreate('manage_report', 'web');

        // Permissions guarding the read-only audit trail listing.
        foreach (['view_any_audit', 'view_audit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate(Acl::SUPER_ADMIN_ROLE, 'web');
        $superAdmin->syncPermissions(Permission::all());

        // Operator: co-administrator of the central database.
        $operator = Role::findOrCreate(Roles::OPERATOR, 'web');
        $operator->syncPermissions(Permission::all());

        $principal = Role::findOrCreate('principal', 'web');
        $principal->syncPermissions($this->permissionNames([
            'principal' => ['view_any', 'view', 'update'],
            'tenant' => Acl::ACTIONS,
            'user' => Acl::ACTIONS,
            'role' => ['view_any', 'view'],
            'menu' => ['view_any', 'view'],
            'audit' => ['view_any', 'view'],
        ]));

        // Tenant administrator manages the users belonging to its tenant.
        $tenantAdmin = Role::findOrCreate(Roles::TENANT_ADMIN, 'web');
        $tenantAdmin->syncPermissions($this->permissionNames([
            'user' => Acl::ACTIONS,
            'role' => ['view_any', 'view'],
            'audit' => ['view_any', 'view'],
        ]));

        // Remaining tenant roles are provisioned for every tenant.
        foreach ([
            Roles::MANAJEMEN,
            Roles::KEPALA_SEKOLAH,
            Roles::GURU,
            Roles::PETUGAS_PIKET,
            Roles::ASRAMA,
            Roles::KEAMANAN,
            Roles::ORANG_TUA,
            Roles::SISWA,
        ] as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Plain end user: no admin menu access at all.
        Role::findOrCreate('user', 'web');

        // Drop roles from the previous baseline that are no longer used.
        Role::query()->whereIn('name', ['tenant'])->delete();

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

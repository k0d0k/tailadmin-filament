<?php

namespace Database\Seeders;

use App\Filament\Pages\CrudGenerator;
use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Principals\PrincipalResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Menu;
use App\Support\Acl;
use Illuminate\Database\Seeder;

/**
 * Seeds the sidebar: the built-in modules are grouped so the RBAC behaviour
 * (a menu disappears when its permission is missing) can be seen out of the
 * box. Menus are matched by label, so re-running the seeder is safe.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $management = Menu::updateOrCreate(
            ['label' => 'Manajemen', 'parent_id' => null],
            ['icon' => 'user-group', 'url' => null, 'resource' => null, 'permission' => null, 'sort' => 1, 'is_active' => true],
        );

        $system = Menu::updateOrCreate(
            ['label' => 'Sistem', 'parent_id' => null],
            ['icon' => 'cog-6-tooth', 'url' => null, 'resource' => null, 'permission' => null, 'sort' => 2, 'is_active' => true],
        );

        $this->menu($management, 'Principal', PrincipalResource::class, '/panel/principals', 'building-office-2', Acl::name('view_any', 'principal'), 1);
        $this->menu($management, 'Tenant', TenantResource::class, '/panel/tenants', 'building-storefront', Acl::name('view_any', 'tenant'), 2);
        $this->menu($management, 'Users', UserResource::class, '/panel/users', 'users', Acl::name('view_any', 'user'), 3);
        $this->menu($management, 'Roles', RoleResource::class, '/panel/roles', 'shield-check', Acl::name('view_any', 'role'), 4);
        $this->menu($management, 'Permissions', PermissionResource::class, '/panel/permissions', 'key', Acl::name('view_any', 'permission'), 5);

        $this->menu($system, 'Menu', MenuResource::class, '/panel/menus', 'bars-3', Acl::name('view_any', 'menu'), 1);
        $this->menu($system, 'CRUD Generator', CrudGenerator::class, '/panel/crud-generator', 'sparkles', 'generate_crud', 2);
    }

    protected function menu(
        Menu $parent,
        string $label,
        string $target,
        string $url,
        string $icon,
        string $permission,
        int $sort,
    ): void {
        Menu::updateOrCreate(
            ['label' => $label, 'parent_id' => $parent->id],
            [
                'icon' => $icon,
                'url' => $url,
                'resource' => $target,
                'permission' => $permission,
                'sort' => $sort,
                'is_active' => true,
            ],
        );
    }
}

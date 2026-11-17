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
        $management = Menu::firstOrCreate(
            ['label' => 'Manajemen', 'parent_id' => null],
            ['icon' => 'heroicon-o-user-group', 'sort' => 1, 'is_active' => true],
        );

        $system = Menu::firstOrCreate(
            ['label' => 'Sistem', 'parent_id' => null],
            ['icon' => 'heroicon-o-cog-6-tooth', 'sort' => 2, 'is_active' => true],
        );

        $this->menu($management, 'Principal', PrincipalResource::class, 'heroicon-o-building-office-2', Acl::name('view_any', 'principal'), 1);
        $this->menu($management, 'Tenant', TenantResource::class, 'heroicon-o-building-storefront', Acl::name('view_any', 'tenant'), 2);
        $this->menu($management, 'Users', UserResource::class, 'heroicon-o-users', Acl::name('view_any', 'user'), 3);
        $this->menu($management, 'Roles', RoleResource::class, 'heroicon-o-shield-check', Acl::name('view_any', 'role'), 4);
        $this->menu($management, 'Permissions', PermissionResource::class, 'heroicon-o-key', Acl::name('view_any', 'permission'), 5);

        $this->menu($system, 'Menu', MenuResource::class, 'heroicon-o-bars-3', Acl::name('view_any', 'menu'), 1);
        $this->menu($system, 'CRUD Generator', CrudGenerator::class, 'heroicon-o-sparkles', 'generate_crud', 2);
    }

    protected function menu(
        Menu $parent,
        string $label,
        string $target,
        string $icon,
        string $permission,
        int $sort,
    ): void {
        Menu::updateOrCreate(
            ['label' => $label, 'parent_id' => $parent->id],
            [
                'icon' => $icon,
                'resource' => $target,
                'permission' => $permission,
                'sort' => $sort,
                'is_active' => true,
            ],
        );
    }
}

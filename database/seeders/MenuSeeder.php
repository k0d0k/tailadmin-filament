<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Support\Acl;
use Illuminate\Database\Seeder;

/**
 * Seeds the database driven TailAdmin sidebar. Groups are parents (type
 * `group`) and the built-in modules are links resolved through the panel
 * resource registry. Re-running is safe because menus are matched by their
 * unique `key`, and every node carries id/en/ar titles.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $management = $this->group('management', [
            'id' => 'Manajemen',
            'en' => 'Management',
            'ar' => 'الإدارة',
        ], 'user-group', 1);

        $system = $this->group('system', [
            'id' => 'Sistem',
            'en' => 'System',
            'ar' => 'النظام',
        ], 'cog-6-tooth', 2);

        $this->link($management, 'principals', [
            'id' => 'Principal',
            'en' => 'Principal',
            'ar' => 'الجهة',
        ], 'building-office-2', 'principals', Acl::name('view_any', 'principal'), 1);

        $this->link($management, 'tenants', [
            'id' => 'Tenant',
            'en' => 'Tenant',
            'ar' => 'المستأجر',
        ], 'building-storefront', 'tenants', Acl::name('view_any', 'tenant'), 2);

        $this->link($management, 'users', [
            'id' => 'Pengguna',
            'en' => 'Users',
            'ar' => 'المستخدمون',
        ], 'users', 'users', Acl::name('view_any', 'user'), 3);

        $this->link($management, 'roles', [
            'id' => 'Role',
            'en' => 'Roles',
            'ar' => 'الأدوار',
        ], 'shield-check', 'roles', Acl::name('view_any', 'role'), 4);

        $this->link($management, 'permissions', [
            'id' => 'Permission',
            'en' => 'Permissions',
            'ar' => 'الصلاحيات',
        ], 'key', 'permissions', Acl::name('view_any', 'permission'), 5);

        $this->link($system, 'menus', [
            'id' => 'Menu',
            'en' => 'Menus',
            'ar' => 'القوائم',
        ], 'bars-3', 'menus', Acl::name('view_any', 'menu'), 1);

        Menu::updateOrCreate(
            ['key' => 'menu_builder'],
            [
                'title' => [
                    'id' => 'Pembuat Menu',
                    'en' => 'Menu Builder',
                    'ar' => 'منشئ القوائم',
                ],
                'label' => 'Pembuat Menu',
                'type' => Menu::TYPE_LINK,
                'icon' => 'bars-3',
                'route_name' => 'panel.menu-builder',
                'resource' => null,
                'permission' => Acl::name('view_any', 'menu'),
                'parent_id' => $system->id,
                'sort' => 3,
                'is_active' => true,
                'is_visible' => true,
            ],
        );

        Menu::updateOrCreate(
            ['key' => 'crud_generator'],
            [
                'title' => [
                    'id' => 'Generator CRUD',
                    'en' => 'CRUD Generator',
                    'ar' => 'منشئ CRUD',
                ],
                'label' => 'Generator CRUD',
                'type' => Menu::TYPE_LINK,
                'icon' => 'sparkles',
                'route_name' => 'panel.generator',
                'resource' => null,
                'permission' => 'generate_crud',
                'parent_id' => $system->id,
                'sort' => 2,
                'is_active' => true,
                'is_visible' => true,
            ],
        );
    }

    /**
     * @param  array<string, string>  $title
     */
    protected function group(string $key, array $title, string $icon, int $sort): Menu
    {
        return Menu::updateOrCreate(
            ['key' => $key],
            [
                'title' => $title,
                'label' => $title['id'] ?? $title['en'] ?? $key,
                'type' => Menu::TYPE_GROUP,
                'icon' => $icon,
                'parent_id' => null,
                'sort' => $sort,
                'is_active' => true,
                'is_visible' => true,
            ],
        );
    }

    /**
     * @param  array<string, string>  $title
     */
    protected function link(
        Menu $parent,
        string $key,
        array $title,
        string $icon,
        string $resource,
        string $permission,
        int $sort,
    ): void {
        Menu::updateOrCreate(
            ['key' => $key],
            [
                'title' => $title,
                'label' => $title['id'] ?? $title['en'] ?? $key,
                'type' => Menu::TYPE_LINK,
                'icon' => $icon,
                'route_name' => null,
                'resource' => $resource,
                'permission' => $permission,
                'parent_id' => $parent->id,
                'sort' => $sort,
                'is_active' => true,
                'is_visible' => true,
            ],
        );
    }
}

<?php

namespace App\Support\Panel;

use App\Models\Menu;
use App\Models\User;
use App\Support\Acl;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Builds the TailAdmin sidebar sections from the database driven `menus`
 * table. Nodes are only rendered when their permission is granted to the
 * signed in user and a panel URL can be resolved.
 */
class PanelMenu
{
    /**
     * Menu sections keyed by group label. A group of `''` means a standalone
     * entry that is rendered without a heading.
     *
     * @return array<string, array<int, array{title: string, icon: string, url: string, active: bool}>>
     */
    public static function sections(?User $user): array
    {
        if (! Schema::hasTable('menus')) {
            return [];
        }

        try {
            $roots = Menu::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->where('is_visible', true)
                ->orderBy('sort')
                ->orderBy('label')
                ->with(['children' => fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_visible', true)
                    ->orderBy('sort')
                    ->orderBy('label')])
                ->get();
        } catch (Throwable) {
            return [];
        }

        $sections = [];

        foreach ($roots as $root) {
            $items = [];

            foreach ($root->children as $child) {
                $item = self::item($child, $user);

                if ($item !== null) {
                    $items[] = $item;
                }
            }

            if ($items !== []) {
                $sections[$root->label()] = $items;

                continue;
            }

            // A root without visible children can still be a direct link.
            $item = self::item($root, $user);

            if ($item !== null) {
                $sections[''][] = $item;
            }
        }

        return $sections;
    }

    /**
     * Map a menu into a sidebar item, or null when it is hidden / unresolvable.
     *
     * @return array{title: string, icon: string, url: string, active: bool}|null
     */
    protected static function item(Menu $menu, ?User $user): ?array
    {
        if (! self::visible($menu, $user)) {
            return null;
        }

        $url = $menu->panelUrl();

        if ($url === null) {
            return null;
        }

        return [
            'title' => $menu->label(),
            'icon' => $menu->icon ?: 'bars-3',
            'url' => $url,
            'active' => self::isActive($url),
        ];
    }

    /**
     * A menu is visible when it has no permission, when the user is a super
     * admin, or when the user holds the required permission.
     */
    protected static function visible(Menu $menu, ?User $user): bool
    {
        if (blank($menu->permission)) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->hasRole(Acl::SUPER_ADMIN_ROLE)) {
            return true;
        }

        return $user->checkPermissionTo($menu->permission);
    }

    /**
     * Whether the current request points at the given menu url.
     */
    protected static function isActive(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        return rtrim(request()->url(), '/') === rtrim($url, '/');
    }
}

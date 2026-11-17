<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns the `menus` table into Filament sidebar navigation items. Each menu is
 * only rendered when the current user holds its permission (menus without a
 * permission are visible to everybody).
 */
class MenuRegistry
{
    /**
     * @return array<int, NavigationItem>
     */
    public static function navigationItems(): array
    {
        $items = [];

        foreach (self::rootMenus() as $root) {
            $children = $root->children()
                ->where('is_active', true)
                ->orderBy('sort')
                ->orderBy('label')
                ->get();

            if ($children->isNotEmpty()) {
                foreach ($children as $child) {
                    $items[] = self::toNavigationItem($child)->group($root->label);
                }

                continue;
            }

            $items[] = self::toNavigationItem($root);
        }

        return $items;
    }

    /**
     * Ordered group labels, so the sidebar keeps the order defined in the DB.
     *
     * @return array<int, string>
     */
    public static function navigationGroups(): array
    {
        return self::rootMenus()
            ->filter(fn (Menu $root): bool => $root->children()->where('is_active', true)->exists())
            ->pluck('label')
            ->all();
    }

    /**
     * @return Collection<int, Menu>
     */
    protected static function rootMenus(): Collection
    {
        try {
            if (! Schema::hasTable('menus')) {
                return new Collection;
            }

            return Menu::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort')
                ->orderBy('label')
                ->get();
        } catch (Throwable) {
            return new Collection;
        }
    }

    protected static function toNavigationItem(Menu $menu): NavigationItem
    {
        $item = NavigationItem::make($menu->label)
            ->sort((int) $menu->sort)
            ->url(
                fn (): ?string => $menu->resolveUrl(),
                fn (): bool => (bool) $menu->target_blank,
            )
            ->visible(fn (): bool => self::isVisible($menu));

        if (filled($menu->icon)) {
            $item->icon(self::filamentIcon($menu->icon));
        }

        return $item;
    }

    /**
     * Filament resolves icons through blade-icons and therefore needs the set
     * prefix (`heroicon-o-users`), while the TailAdmin menu form stores the
     * plain name (`users`). Prefixing here keeps both panels working off the
     * same row, including rows that already carry the prefix.
     */
    protected static function filamentIcon(string $icon): string
    {
        if (Str::startsWith($icon, ['heroicon-', 'fas-', 'far-', 'fab-'])) {
            return $icon;
        }

        return 'heroicon-o-'.$icon;
    }

    protected static function isVisible(Menu $menu): bool
    {
        $user = Filament::auth()->user() ?? auth()->user();

        if (! $user instanceof User) {
            return blank($menu->permission);
        }

        if (blank($menu->permission)) {
            return true;
        }

        if ($user->hasRole(Acl::SUPER_ADMIN_ROLE)) {
            return true;
        }

        return $user->checkPermissionTo($menu->permission);
    }
}

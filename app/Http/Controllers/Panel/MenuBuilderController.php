<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use App\Support\Acl;
use App\Support\Panel\IconLibrary;
use App\Support\Panel\ResourceRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Nested drag & drop builder for the database driven TailAdmin sidebar. Every
 * node carries a multi language title, a type, an optional route/module and the
 * per-language visibility. The whole tree is sent back on save and persisted in
 * a single transaction.
 */
class MenuBuilderController extends Controller
{
    /**
     * Locales offered for the menu titles.
     *
     * @var array<int, string>
     */
    public const LOCALES = ['id', 'en', 'ar'];

    public function index(Request $request): View
    {
        $this->authorizeBuilder($request);

        $menus = Menu::query()
            ->orderBy('parent_id')
            ->orderBy('sort')
            ->orderBy('label')
            ->get();

        return view('panel.menu-builder', [
            'tree' => $this->buildTree($menus),
            'iconMap' => IconLibrary::map(),
            'icons' => array_combine(IconLibrary::keys(), IconLibrary::keys()),
            'permissions' => Permission::query()->orderBy('name')->pluck('name', 'name')->all(),
            'panelResources' => collect(ResourceRegistry::all())
                ->map(fn (array $definition, string $key): array => [
                    'key' => $key,
                    'title' => $definition['title'] ?? $key,
                ])
                ->values()
                ->all(),
            'types' => [
                Menu::TYPE_GROUP => __('ui.menu_builder.types.group'),
                Menu::TYPE_LINK => __('ui.menu_builder.types.link'),
                Menu::TYPE_HEADER => __('ui.menu_builder.types.header'),
            ],
            'modules' => $this->moduleOptions(),
            'locales' => self::LOCALES,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $this->authorizeBuilder($request);

        $validated = $request->validate([
            'tree' => ['present', 'array'],
        ]);

        $this->validateNodes($validated['tree']);

        DB::transaction(function () use ($validated): void {
            $ids = [];
            $keys = [];
            $this->persistNodes($validated['tree'], null, $ids, $keys);

            if ($ids === []) {
                Menu::query()->delete();
            } else {
                Menu::query()->whereNotIn('id', $ids)->delete();
            }
        });

        return response()->json([
            'status' => 'ok',
            'message' => __('ui.menu_builder.saved'),
        ]);
    }

    /**
     * Build the nested tree the builder view renders.
     *
     * @param  \Illuminate\Support\Collection<int, Menu>  $menus
     * @return array<int, array<string, mixed>>
     */
    protected function buildTree($menus, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($menus as $menu) {
            if ($menu->parent_id !== $parentId) {
                continue;
            }

            $branch[] = [
                'id' => $menu->id,
                'title' => (array) ($menu->title ?? []),
                'key' => $menu->key,
                'type' => $menu->type ?: Menu::TYPE_LINK,
                'label' => $menu->label,
                'icon' => $menu->icon,
                'url' => $menu->url,
                'route_name' => $menu->route_name,
                'resource' => $menu->resource,
                'module_key' => $menu->module_key,
                'permission' => $menu->permission,
                'sort' => (int) $menu->sort,
                'is_active' => (bool) $menu->is_active,
                'is_visible' => (bool) $menu->is_visible,
                'target_blank' => (bool) $menu->target_blank,
                'children' => $this->buildTree($menus, $menu->id),
            ];
        }

        return $branch;
    }

    /**
     * Recursively validate the submitted nodes so a malformed payload returns
     * a 422 JSON response instead of a partial save.
     *
     * @param  array<int, mixed>  $nodes
     */
    protected function validateNodes(array $nodes): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                throw ValidationException::withMessages(['tree' => __('ui.menu_builder.invalid')]);
            }

            Validator::make($node, [
                'title' => ['array'],
                'title.id' => ['nullable', 'string', 'max:255'],
                'title.en' => ['nullable', 'string', 'max:255'],
                'title.ar' => ['nullable', 'string', 'max:255'],
                'type' => ['nullable', 'in:group,link,header'],
                'icon' => ['nullable', 'string', 'max:255'],
                'url' => ['nullable', 'string', 'max:2048'],
                'route_name' => ['nullable', 'string', 'max:255'],
                'resource' => ['nullable', 'string', 'max:255'],
                'module_key' => ['nullable', 'string', 'max:255'],
                'permission' => ['nullable', 'string', 'max:255'],
            ])->validate();

            $title = (array) ($node['title'] ?? []);

            if (blank($title['id'] ?? null) && blank($title['en'] ?? null) && blank($title['ar'] ?? null)) {
                throw ValidationException::withMessages(['tree' => __('ui.menu_builder.title_required')]);
            }

            if (isset($node['children']) && is_array($node['children'])) {
                $this->validateNodes($node['children']);
            }
        }
    }

    /**
     * Persist the submitted nodes in order, updating existing menus and
     * creating the new ones. Collected ids are used to prune removed rows.
     *
     * @param  array<int, mixed>  $nodes
     * @param  array<int, int>  $ids
     * @param  array<int, string>  $keys
     */
    protected function persistNodes(array $nodes, ?int $parentId, array &$ids, array &$keys): void
    {
        foreach (array_values($nodes) as $index => $node) {
            $id = is_numeric($node['id'] ?? null) ? (int) $node['id'] : null;

            $title = collect(self::LOCALES)
                ->mapWithKeys(fn (string $locale): array => [
                    $locale => $this->value(data_get($node, "title.{$locale}")),
                ])
                ->filter()
                ->all();

            $label = $title['id'] ?? $title['en'] ?? $title['ar'] ?? $this->value($node['key'] ?? null) ?? 'menu';
            $key = $this->uniqueKey(
                $this->value($node['key'] ?? null) ?? (string) ($node['route_name'] ?? $node['url'] ?? $label),
                $keys,
                $id,
            );

            $attributes = [
                'title' => $title !== [] ? $title : null,
                'key' => $key,
                'type' => in_array($node['type'] ?? null, [Menu::TYPE_GROUP, Menu::TYPE_LINK, Menu::TYPE_HEADER], true)
                    ? $node['type']
                    : Menu::TYPE_LINK,
                'label' => $label,
                'icon' => $this->value($node['icon'] ?? null),
                'url' => $this->value($node['url'] ?? null),
                'route_name' => $this->value($node['route_name'] ?? null),
                'resource' => $this->value($node['resource'] ?? null),
                'module_key' => $this->value($node['module_key'] ?? null),
                'permission' => $this->value($node['permission'] ?? null),
                'is_active' => (bool) ($node['is_active'] ?? true),
                'is_visible' => (bool) ($node['is_visible'] ?? true),
                'target_blank' => (bool) ($node['target_blank'] ?? false),
                'parent_id' => $parentId,
                'sort' => $index,
            ];

            $menu = $id !== null ? Menu::query()->find($id) : null;

            if ($menu !== null) {
                $menu->update($attributes);
            } else {
                $menu = Menu::query()->create($attributes);
            }

            $ids[] = $menu->id;
            $keys[] = $key;

            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            $this->persistNodes($children, $menu->id, $ids, $keys);
        }
    }

    /**
     * Build a stable, unique `key` for a menu node. The value is slugified and
     * disambiguated with a numeric suffix when it collides with a key already
     * used in this payload or stored in the database.
     *
     * @param  array<int, string>  $used
     */
    protected function uniqueKey(string $value, array &$used, ?int $id = null): string
    {
        $base = Str::slug($value, '_') ?: 'menu';
        $key = $base;
        $suffix = 1;

        while (in_array($key, $used, true) || $this->keyExists($key, $id)) {
            $key = $base.'_'.(++$suffix);
        }

        return $key;
    }

    protected function keyExists(string $key, ?int $id): bool
    {
        return Menu::query()
            ->where('key', $key)
            ->when($id !== null, fn ($query) => $query->whereKeyNot($id))
            ->exists();
    }

    /**
     * Normalise a nullable string value coming from the JSON payload.
     */
    protected function value(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return filled($value) ? (string) $value : null;
    }

    /**
     * Options for the module selector, built from the module directories.
     *
     * @return array<string, string>
     */
    protected function moduleOptions(): array
    {
        $options = ['core' => 'Core'];

        $root = (string) config('modules.path', base_path('Modules'));

        if (File::isDirectory($root)) {
            foreach (File::directories($root) as $directory) {
                $name = basename($directory);
                $options[Str::snake($name)] = $name;
            }
        }

        return $options;
    }

    /**
     * Only super admins or users holding `manage_menu` may use the builder.
     */
    protected function authorizeBuilder(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && ($user->hasRole(Acl::SUPER_ADMIN_ROLE) || $user->checkPermissionTo('manage_menu')),
            403,
        );
    }
}

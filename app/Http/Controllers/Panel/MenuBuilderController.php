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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Nested drag & drop builder for the database driven TailAdmin sidebar. The
 * whole tree is sent back on save and persisted in a single transaction.
 */
class MenuBuilderController extends Controller
{
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
            $this->persistNodes($validated['tree'], null, $ids);

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
                'label' => $menu->label,
                'icon' => $menu->icon,
                'url' => $menu->url,
                'resource' => $menu->resource,
                'permission' => $menu->permission,
                'sort' => (int) $menu->sort,
                'is_active' => (bool) $menu->is_active,
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
                'label' => ['required', 'string', 'max:255'],
                'icon' => ['nullable', 'string', 'max:255'],
                'url' => ['nullable', 'string', 'max:2048'],
                'resource' => ['nullable', 'string', 'max:255'],
                'permission' => ['nullable', 'string', 'max:255'],
            ])->validate();

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
     */
    protected function persistNodes(array $nodes, ?int $parentId, array &$ids): void
    {
        foreach (array_values($nodes) as $index => $node) {
            $id = is_numeric($node['id'] ?? null) ? (int) $node['id'] : null;

            $attributes = [
                'label' => (string) ($node['label'] ?? ''),
                'icon' => $this->value($node['icon'] ?? null),
                'url' => $this->value($node['url'] ?? null),
                'resource' => $this->value($node['resource'] ?? null),
                'permission' => $this->value($node['permission'] ?? null),
                'is_active' => (bool) ($node['is_active'] ?? true),
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

            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            $this->persistNodes($children, $menu->id, $ids);
        }
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

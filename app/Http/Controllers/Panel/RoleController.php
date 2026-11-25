<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Support\Acl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dedicated Role & Access screen for the standalone TailAdmin panel. It lists
 * the roles with their scope and usage, allows creating / editing / deleting
 * roles and offers a per-role matrix to grant the menu abilities (view,
 * create, update, delete, export) stored in the `role_menu` pivot.
 */
class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAbility($request, 'view_any_role');

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->withCount(['menus as access_count'])
            ->orderBy('name')
            ->get();

        return view('panel.roles.index', [
            'roles' => $roles,
            'scopeOptions' => Role::scopeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAbility($request, 'create_role');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:roles,name'],
            'label' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', 'in:central,tenant'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
            'label' => $data['label'] ?? null,
            'scope' => $data['scope'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('panel.roles.index')
            ->with('status', __('ui.roles.created'));
    }

    public function edit(Request $request, Role $role): View
    {
        $this->authorizeAbility($request, 'view_any_role');

        return view('panel.roles.form', [
            'role' => $role,
            'scopeOptions' => Role::scopeOptions(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeAbility($request, 'update_role');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:roles,name,'.$role->getKey()],
            'label' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', 'in:central,tenant'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role->update([
            'name' => $data['name'],
            'label' => $data['label'] ?? null,
            'scope' => $data['scope'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('panel.roles.index')
            ->with('status', __('ui.roles.updated'));
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeAbility($request, 'delete_role');

        abort_if($role->name === Acl::SUPER_ADMIN_ROLE, 403, __('ui.roles.access_denied'));

        $role->menus()->detach();
        $role->delete();

        return redirect()
            ->route('panel.roles.index')
            ->with('status', __('ui.roles.deleted'));
    }

    /**
     * Menu access matrix for a single role.
     */
    public function access(Request $request, Role $role): View
    {
        $this->authorizeAbility($request, 'view_any_role');

        $menus = Menu::query()
            ->orderBy('parent_id')
            ->orderBy('sort')
            ->orderBy('label')
            ->get();

        $granted = RoleMenu::query()
            ->where('role_id', $role->getKey())
            ->get()
            ->keyBy('menu_id');

        return view('panel.roles.access', [
            'role' => $role,
            'menus' => $this->flatten($menus),
            'granted' => $granted,
            'abilities' => RoleMenu::ABILITIES,
        ]);
    }

    public function saveAccess(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeAbility($request, 'update_role');

        $input = (array) $request->input('access', []);

        DB::transaction(function () use ($role, $input): void {
            foreach ($input as $menuId => $abilities) {
                $menuId = (int) $menuId;
                $abilities = (array) $abilities;

                $values = [];

                foreach (RoleMenu::ABILITIES as $ability => $column) {
                    $values[$column] = (bool) ($abilities[$ability] ?? false);
                }

                $granted = array_filter($values);

                if ($granted === []) {
                    RoleMenu::query()->where('role_id', $role->getKey())->where('menu_id', $menuId)->delete();

                    continue;
                }

                RoleMenu::query()->updateOrCreate(
                    ['role_id' => $role->getKey(), 'menu_id' => $menuId],
                    $values,
                );
            }
        });

        return redirect()
            ->route('panel.roles.access', $role)
            ->with('status', __('ui.roles.access_saved'));
    }

    /**
     * Flatten the menu tree into an ordered list carrying the depth so the
     * access matrix can render a readable hierarchy.
     *
     * @param  \Illuminate\Support\Collection<int, Menu>  $menus
     * @return array<int, array<string, mixed>>
     */
    protected function flatten($menus, ?int $parentId = null, int $depth = 0): array
    {
        $flat = [];

        foreach ($menus as $menu) {
            if ($menu->parent_id !== $parentId) {
                continue;
            }

            $flat[] = [
                'id' => $menu->getKey(),
                'title' => $menu->label(),
                'type' => $menu->type ?: Menu::TYPE_LINK,
                'icon' => $menu->icon ?: 'bars-3',
                'depth' => $depth,
            ];

            foreach ($this->flatten($menus, $menu->getKey(), $depth + 1) as $child) {
                $flat[] = $child;
            }
        }

        return $flat;
    }

    /**
     * Restrict the screen to super admins or a user holding the given
     * permission name.
     */
    protected function authorizeAbility(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && ($user->hasRole(Acl::SUPER_ADMIN_ROLE) || $user->checkPermissionTo($permission)),
            403,
        );
    }
}

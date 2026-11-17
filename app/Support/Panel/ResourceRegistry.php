<?php

namespace App\Support\Panel;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Principal;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Panel\PanelResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;

/**
 * Single source of truth for the standalone TailAdmin panel. Every entry
 * describes one CRUD module: the model, the DataTables columns, the form
 * fields, the column filters and how the base query is built. The generic
 * controller and the blade views are entirely driven by this registry.
 */
class ResourceRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $resources = [
            'principals' => self::principals(),
            'tenants' => self::tenants(),
            'users' => self::users(),
            'roles' => self::roles(),
            'permissions' => self::permissions(),
            'menus' => self::menus(),
        ];

        foreach (self::generated() as $key => $definition) {
            $resources[$key] ??= $definition;
        }

        return $resources;
    }

    /**
     * Modules created by the panel CRUD generator, discovered from
     * App\Panel\Resources. The disk is scanned once per request.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function generated(): array
    {
        static $resources = null;

        if ($resources !== null) {
            return $resources;
        }

        $resources = [];

        foreach (File::glob(app_path('Panel/Resources/*.php')) ?: [] as $path) {
            $class = 'App\\Panel\\Resources\\'.pathinfo($path, PATHINFO_FILENAME);

            if (! class_exists($class) || ! is_subclass_of($class, PanelResource::class)) {
                continue;
            }

            $definition = $class::definition();

            if (is_array($definition) && isset($definition['model'])) {
                $resources[$class::key()] = $definition;
            }
        }

        return $resources;
    }

    /**
     * @return array<string, mixed>
     */
    public static function find(string $key): array
    {
        $resources = self::all();

        abort_unless(isset($resources[$key]), 404);

        return $resources[$key];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /**
     * Resources grouped by navigation group for the sidebar.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::all() as $key => $resource) {
            $groups[$resource['group']][] = ['key' => $key] + $resource;
        }

        return $groups;
    }

    /**
     * Permission resource key (e.g. `view_any_user`) for a definition.
     */
    public static function permissionResource(array $resource): string
    {
        return $resource['resource'];
    }

    /* ---------------------------------------------------------------------
     | Definitions
     | ------------------------------------------------------------------- */

    /**
     * @return array<string, mixed>
     */
    protected static function principals(): array
    {
        return [
            'model' => Principal::class,
            'resource' => 'principal',
            'title' => 'Principal',
            'singular' => 'Principal',
            'group' => 'Organisasi',
            'icon' => 'building-office-2',
            'description' => 'Entitas induk tertinggi dalam struktur organisasi.',
            'query' => fn (): Builder => Principal::query()->withCount(['tenants', 'users']),
            'columns' => [
                ['data' => 'name', 'title' => 'Nama', 'orderable' => true, 'value' => fn ($r) => Html::text($r->name)],
                ['data' => 'code', 'title' => 'Kode', 'orderable' => true, 'value' => fn ($r) => '<span class="font-mono text-xs">'.Html::text($r->code).'</span>'],
                ['data' => 'email', 'title' => 'Email', 'orderable' => true, 'value' => fn ($r) => Html::text($r->email)],
                ['data' => 'tenants_count', 'title' => 'Tenant', 'orderable' => false, 'className' => 'text-center', 'value' => fn ($r) => Html::count($r->tenants_count)],
                ['data' => 'is_active', 'title' => 'Status', 'orderable' => false, 'value' => fn ($r) => Html::status($r->is_active)],
            ],
            'search' => ['name', 'code', 'email'],
            'filters' => [
                self::selectFilter('is_active', 'Status', ['1' => 'Aktif', '0' => 'Nonaktif']),
            ],
            'fields' => [
                self::text('name', 'Nama', ['required', 'string', 'max:255'], 'half'),
                self::text('code', 'Kode', ['nullable', 'string', 'max:50'], 'half'),
                self::email('email', 'Email', ['nullable', 'email', 'max:255'], 'half'),
                self::toggle('is_active', 'Aktif', 'half'),
            ],
            'default_sort' => ['name', 'asc'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function tenants(): array
    {
        return [
            'model' => Tenant::class,
            'resource' => 'tenant',
            'title' => 'Tenant',
            'singular' => 'Tenant',
            'group' => 'Organisasi',
            'icon' => 'building-storefront',
            'description' => 'Unit usaha di bawah sebuah principal.',
            'query' => fn (): Builder => Tenant::query()->with('principal')->withCount('users'),
            'columns' => [
                ['data' => 'name', 'title' => 'Nama', 'orderable' => true, 'value' => fn ($r) => Html::text($r->name)],
                ['data' => 'code', 'title' => 'Kode', 'orderable' => true, 'value' => fn ($r) => '<span class="font-mono text-xs">'.Html::text($r->code).'</span>'],
                ['data' => 'principal.name', 'title' => 'Principal', 'orderable' => false, 'value' => fn ($r) => Html::text($r->principal?->name)],
                ['data' => 'users_count', 'title' => 'User', 'orderable' => false, 'className' => 'text-center', 'value' => fn ($r) => Html::count($r->users_count)],
                ['data' => 'is_active', 'title' => 'Status', 'orderable' => false, 'value' => fn ($r) => Html::status($r->is_active)],
            ],
            'search' => ['name', 'code'],
            'filters' => [
                self::selectFilter('principal_id', 'Principal', fn () => Principal::query()->orderBy('name')->pluck('name', 'id')->all()),
                self::selectFilter('is_active', 'Status', ['1' => 'Aktif', '0' => 'Nonaktif']),
            ],
            'fields' => [
                self::select('principal_id', 'Principal', fn () => Principal::query()->orderBy('name')->pluck('name', 'id')->all(), ['required', 'exists:principals,id'], 'half'),
                self::text('name', 'Nama', ['required', 'string', 'max:255'], 'half'),
                self::text('code', 'Kode', ['nullable', 'string', 'max:50'], 'half'),
                self::toggle('is_active', 'Aktif', 'half'),
            ],
            'default_sort' => ['name', 'asc'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function users(): array
    {
        return [
            'model' => User::class,
            'resource' => 'user',
            'title' => 'User',
            'singular' => 'User',
            'group' => 'Akses',
            'icon' => 'users',
            'description' => 'Akun yang dapat mengakses panel.',
            'query' => fn (): Builder => User::query()->with(['roles', 'principal', 'tenant']),
            'columns' => [
                ['data' => 'name', 'title' => 'Nama', 'orderable' => true, 'value' => fn ($r) => '<div class="font-medium text-gray-800 dark:text-gray-100">'.e($r->name).'</div>'],
                ['data' => 'email', 'title' => 'Email', 'orderable' => true, 'value' => fn ($r) => Html::text($r->email)],
                ['data' => 'roles', 'title' => 'Role', 'orderable' => false, 'value' => fn ($r) => Html::badges($r->roles->pluck('name'), 'brand')],
                ['data' => 'principal.name', 'title' => 'Principal', 'orderable' => false, 'value' => fn ($r) => Html::text($r->principal?->name)],
                ['data' => 'tenant.name', 'title' => 'Tenant', 'orderable' => false, 'value' => fn ($r) => Html::text($r->tenant?->name)],
                ['data' => 'created_at', 'title' => 'Dibuat', 'orderable' => true, 'value' => fn ($r) => '<span class="text-gray-500 dark:text-gray-400">'.e($r->created_at?->format('d M Y')).'</span>'],
            ],
            'search' => ['name', 'email'],
            'filters' => [
                self::selectFilter('role', 'Role', fn () => Role::query()->orderBy('name')->pluck('name', 'name')->all()),
                self::selectFilter('principal_id', 'Principal', fn () => Principal::query()->orderBy('name')->pluck('name', 'id')->all()),
                self::selectFilter('tenant_id', 'Tenant', fn () => Tenant::query()->orderBy('name')->pluck('name', 'id')->all()),
            ],
            'filter_apply' => [
                'role' => fn (Builder $query, $value) => $query->whereHas('roles', fn ($q) => $q->where('name', $value)),
            ],
            'fields' => [
                self::text('name', 'Nama', ['required', 'string', 'max:255'], 'half'),
                self::email('email', 'Email', ['required', 'email', 'max:255', 'unique:users,email'], 'half', ['required', 'email', 'max:255', 'unique:users,email,{id}']),
                self::password('password', 'Password', 'full'),
                self::select('principal_id', 'Principal', fn () => Principal::query()->orderBy('name')->pluck('name', 'id')->all(), ['nullable', 'exists:principals,id'], 'half'),
                self::select('tenant_id', 'Tenant', fn () => Tenant::query()->orderBy('name')->pluck('name', 'id')->all(), ['nullable', 'exists:tenants,id'], 'half'),
                self::multiselect('roles', 'Role', fn () => Role::query()->orderBy('name')->pluck('name', 'name')->all(), 'full', 'roles'),
            ],
            'default_sort' => ['name', 'asc'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function roles(): array
    {
        return [
            'model' => Role::class,
            'resource' => 'role',
            'title' => 'Role',
            'singular' => 'Role',
            'group' => 'Akses',
            'icon' => 'shield-check',
            'description' => 'Kumpulan hak akses yang dapat diberikan ke user.',
            'query' => fn (): Builder => Role::query()->withCount(['permissions', 'users']),
            'columns' => [
                ['data' => 'name', 'title' => 'Nama', 'orderable' => true, 'value' => fn ($r) => '<span class="font-medium text-gray-800 dark:text-gray-100">'.e($r->name).'</span>'],
                ['data' => 'guard_name', 'title' => 'Guard', 'orderable' => false, 'value' => fn ($r) => Html::badge($r->guard_name, 'muted')],
                ['data' => 'permissions_count', 'title' => 'Permission', 'orderable' => false, 'className' => 'text-center', 'value' => fn ($r) => Html::count($r->permissions_count)],
                ['data' => 'users_count', 'title' => 'User', 'orderable' => false, 'className' => 'text-center', 'value' => fn ($r) => Html::count($r->users_count)],
            ],
            'filters' => [],
            'fields' => [
                self::text('name', 'Nama', ['required', 'string', 'max:255', 'unique:roles,name'], 'half', ['required', 'string', 'max:255', 'unique:roles,name,{id}']),
                self::text('guard_name', 'Guard', ['required', 'string', 'max:255'], 'half'),
                self::multiselect('permissions', 'Hak Akses', fn () => self::permissionOptions(), 'full', 'permissions', 'Semua hak akses yang dimiliki role ini.'),
            ],
            'default_sort' => ['name', 'asc'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function permissions(): array
    {
        return [
            'model' => Permission::class,
            'resource' => 'permission',
            'title' => 'Permission',
            'singular' => 'Permission',
            'group' => 'Akses',
            'icon' => 'key',
            'description' => 'Hak akses granular yang dapat dikaitkan ke role.',
            'query' => fn (): Builder => Permission::query()->withCount(['roles']),
            'columns' => [
                ['data' => 'name', 'title' => 'Nama', 'orderable' => true, 'value' => fn ($r) => '<span class="font-mono text-xs text-gray-700 dark:text-gray-200">'.e($r->name).'</span>'],
                ['data' => 'guard_name', 'title' => 'Guard', 'orderable' => false, 'value' => fn ($r) => Html::badge($r->guard_name, 'muted')],
                ['data' => 'roles_count', 'title' => 'Role', 'orderable' => false, 'className' => 'text-center', 'value' => fn ($r) => Html::count($r->roles_count)],
            ],
            'filters' => [],
            'fields' => [
                self::text('name', 'Nama', ['required', 'string', 'max:255', 'unique:permissions,name'], 'half', ['required', 'string', 'max:255', 'unique:permissions,name,{id}']),
                self::text('guard_name', 'Guard', ['required', 'string', 'max:255'], 'half'),
            ],
            'default_sort' => ['name', 'asc'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function menus(): array
    {
        return [
            'model' => Menu::class,
            'resource' => 'menu',
            'title' => 'Menu',
            'singular' => 'Menu',
            'group' => 'Akses',
            'icon' => 'bars-3',
            'description' => 'Navigasi yang tampil pada panel.',
            'query' => fn (): Builder => Menu::query()->with('parent'),
            'columns' => [
                ['data' => 'label', 'title' => 'Label', 'orderable' => true, 'value' => fn ($r) => '<span class="font-medium text-gray-800 dark:text-gray-100">'.e($r->label).'</span>'],
                ['data' => 'parent.label', 'title' => 'Induk', 'orderable' => false, 'value' => fn ($r) => Html::text($r->parent?->label)],
                ['data' => 'permission', 'title' => 'Permission', 'orderable' => false, 'value' => fn ($r) => $r->permission ? Html::badge($r->permission, 'muted') : '<span class="text-gray-400">—</span>'],
                ['data' => 'sort', 'title' => 'Urut', 'orderable' => true, 'className' => 'text-center', 'value' => fn ($r) => '<span class="text-gray-500 dark:text-gray-400">'.(int) $r->sort.'</span>'],
                ['data' => 'is_active', 'title' => 'Status', 'orderable' => false, 'value' => fn ($r) => Html::status($r->is_active)],
            ],
            'search' => ['label', 'url', 'permission'],
            'filters' => [
                self::selectFilter('is_active', 'Status', ['1' => 'Aktif', '0' => 'Nonaktif']),
            ],
            'fields' => [
                self::text('label', 'Label', ['required', 'string', 'max:255'], 'half'),
                self::select('parent_id', 'Menu Induk', fn ($resource) => self::menuParentOptions($resource), ['nullable', 'exists:menus,id'], 'half'),
                self::icon('icon', 'Ikon', ['nullable', 'string'], 'half'),
                self::number('sort', 'Urutan', ['nullable', 'integer', 'min:0'], 'half'),
                self::text('url', 'URL Manual', ['nullable', 'string', 'max:255'], 'half'),
                self::text('resource', 'Filament Resource', ['nullable', 'string', 'max:255'], 'half', [], 'Kelas resource Filament (opsional).'),
                self::select('permission', 'Permission', fn () => self::permissionOptions(), ['nullable', 'string'], 'half'),
                self::toggle('is_active', 'Aktif', 'half'),
                self::toggle('target_blank', 'Buka di tab baru', 'half'),
            ],
            'default_sort' => ['sort', 'asc'],
        ];
    }

    /* ---------------------------------------------------------------------
     | Column / field factories
     | ------------------------------------------------------------------- */

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected static function selectFilter(string $name, string $label, array|callable $options): array
    {
        return ['name' => $name, 'label' => $label, 'type' => 'select', 'options' => $options];
    }

    /**
     * @param  array<int, string>  $rules
     * @param  array<int, string>  $updateRules
     * @return array<string, mixed>
     */
    protected static function text(string $name, string $label, array $rules = [], string $col = 'half', array $updateRules = [], ?string $help = null): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => 'text',
            'rules' => $rules,
            'update_rules' => $updateRules,
            'col' => $col,
            'help' => $help,
        ];
    }

    /**
     * @param  array<int, string>  $rules
     * @param  array<int, string>  $updateRules
     * @return array<string, mixed>
     */
    protected static function email(string $name, string $label, array $rules = [], string $col = 'half', array $updateRules = []): array
    {
        return compact('name', 'label', 'rules', 'col') + ['type' => 'email', 'update_rules' => $updateRules];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function password(string $name, string $label, string $col = 'half'): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => 'password',
            'col' => $col,
            'rules' => ['required', 'string', 'min:8'],
            'update_rules' => ['nullable', 'string', 'min:8'],
            'help' => 'Minimal 8 karakter. Biarkan Kosong saat mengubah untuk mempertahankan password lama.',
        ];
    }

    /**
     * @param  array<int, string>  $rules
     * @return array<string, mixed>
     */
    protected static function number(string $name, string $label, array $rules = [], string $col = 'half'): array
    {
        return compact('name', 'label', 'rules', 'col') + ['type' => 'number'];
    }

    /**
     * @param  array<int, string>  $rules
     * @return array<string, mixed>
     */
    protected static function select(string $name, string $label, callable $options, array $rules = [], string $col = 'half', ?string $help = null): array
    {
        return compact('name', 'label', 'options', 'rules', 'col', 'help') + ['type' => 'select'];
    }

    /**
     * @param  array<int, string>  $rules
     * @return array<string, mixed>
     */
    protected static function multiselect(string $name, string $label, callable $options, string $col = 'full', ?string $relation = null, ?string $help = null): array
    {
        return compact('name', 'label', 'options', 'col', 'relation', 'help') + ['type' => 'multiselect', 'rules' => ['array']];
    }

    /**
     * Icon picker with a live preview of the selected icon.
     *
     * @param  array<int, string>  $rules
     * @return array<string, mixed>
     */
    protected static function icon(string $name, string $label, array $rules = [], string $col = 'half', ?string $help = null): array
    {
        return compact('name', 'label', 'rules', 'col', 'help') + [
            'type' => 'icon',
            'options' => fn (): array => array_combine(IconLibrary::keys(), IconLibrary::keys()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function toggle(string $name, string $label, string $col = 'half', ?string $help = null): array
    {
        return compact('name', 'label', 'col', 'help') + ['type' => 'toggle', 'rules' => ['boolean']];
    }

    /* ---------------------------------------------------------------------
     | Option builders
     | ------------------------------------------------------------------- */

    /**
     * @return array<string, string>
     */
    protected static function permissionOptions(): array
    {
        return Permission::query()->orderBy('name')->pluck('name', 'name')->all();
    }

    /**
     * Parent menu options, optionally excluding the menu being edited (and its
     * descendants) so a menu can never become its own ancestor.
     *
     * @param  array<string, mixed>  $resource
     * @return array<int, string>
     */
    protected static function menuParentOptions(array $resource): array
    {
        $excludeId = $resource['record'] ?? null;

        return Menu::query()
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->orderBy('label')
            ->pluck('label', 'id')
            ->all();
    }
}

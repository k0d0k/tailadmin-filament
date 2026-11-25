<?php

namespace App\Support\Panel;

use App\Models\CrudDefinition;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Acl;
use App\Support\CrudGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Generates a self contained module under `Modules/<Model>` (the modular
 * monolith layout used by the reference application) and wires it into the
 * standalone TailAdmin panel.
 *
 * Output per module:
 *   Modules/<Model>/Models/<Model>.php
 *   Modules/<Model>/Providers/<Model>ServiceProvider.php  (loads central migrations)
 *   Modules/<Model>/Panel/<Model>Resource.php             (PanelResource definition)
 *   Modules/<Model>/Database/Migrations/...                (when database = central)
 *   Modules/<Model>/Database/Migrations/Tenant/...         (when database = tenant)
 *
 * The module is discovered automatically by the ResourceRegistry, gets its
 * RBAC permissions and a sidebar menu entry. File rendering and migration
 * helpers are shared with the Filament generator.
 */
class ModuleGenerator extends CrudGenerator
{
    /**
     * Databases a module can be generated against.
     *
     * @var array<string, string>
     */
    public const DATABASES = [
        'central' => 'Central',
        'tenant' => 'Tenant',
    ];

    /**
     * Field types offered by the panel generator, keyed by type id.
     *
     * @var array<string, string>
     */
    public const FIELD_TYPES = [
        'string' => 'Text (singkat)',
        'text' => 'Text (panjang)',
        'email' => 'Email',
        'integer' => 'Angka bulat',
        'decimal' => 'Angka desimal',
        'boolean' => 'Ya / Tidak',
        'date' => 'Tanggal',
        'datetime' => 'Tanggal & Waktu',
        'time' => 'Waktu',
        'select' => 'Dropdown',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function generate(array $data): array
    {
        $model = Str::studly((string) $data['model']);
        $resourceKey = Str::snake($model);
        $plural = Str::plural($resourceKey);
        $table = Str::snake(Str::pluralStudly($model));
        $label = filled($data['label'] ?? null) ? (string) $data['label'] : Str::headline(Str::pluralStudly($model));
        $group = filled($data['navigation_group'] ?? null) ? (string) $data['navigation_group'] : null;
        $description = (string) ($data['description'] ?? '');
        $icon = IconLibrary::normalize($data['icon'] ?? null) ?? 'table-cells';
        $connection = ($data['database'] ?? 'central') === 'tenant' ? 'tenant' : 'central';
        $isTenant = $connection === 'tenant';

        $fields = $this->normalizeFields($data['fields'] ?? []);

        if ($fields === []) {
            throw new RuntimeException('Definisikan minimal satu field untuk modul ini.');
        }

        if ($this->tableFields($fields) === []) {
            throw new RuntimeException('Pilih minimal satu field untuk tampil di tabel.');
        }

        if ($this->formFields($fields) === []) {
            throw new RuntimeException('Pilih minimal satu field untuk tampil di form.');
        }

        $modulePath = $this->moduleRoot()."/{$model}";

        if (File::isDirectory($modulePath)) {
            throw new RuntimeException("Modul [{$model}] sudah ada di Modules/. Gunakan nama lain.");
        }

        if (File::exists(app_path("Models/{$model}.php"))) {
            throw new RuntimeException("Model [App\\Models\\{$model}] sudah ada. Gunakan nama lain.");
        }

        $created = [];
        $sort = $this->tableFields($fields)[0]['name'];

        // 1. Migration — central lives in the module, tenant in its Tenant folder.
        $migrationName = date('Y_m_d_His')."_create_{$table}_table";
        $migrationPath = $isTenant
            ? "{$modulePath}/Database/Migrations/Tenant/{$migrationName}.php"
            : "{$modulePath}/Database/Migrations/{$migrationName}.php";

        $this->put($migrationPath, $this->render('migration', [
            'TABLE' => $table,
            'TENANT_COLUMN' => $isTenant
                ? "            \$table->unsignedBigInteger('tenant_id')->nullable()->index();\n"
                : '',
            'COLUMNS' => $this->migrationColumns($fields),
        ]));
        $created[] = $migrationPath;

        // 2. Model
        $fillable = $this->fillable($fields);

        if ($isTenant) {
            $fillable = "        'tenant_id',".($fillable !== '' ? "\n".$fillable : '');
        }

        $modelPath = "{$modulePath}/Models/{$model}.php";
        $this->put($modelPath, $this->render('model', [
            'MODEL' => $model,
            'CONNECTION' => $connection,
            'TABLE' => $table,
            'FILLABLE' => $fillable,
            'CASTS' => $this->casts($fields),
            'SLUG_SOURCE' => $this->slugSource($fields),
            'TENANT_IMPORT' => $isTenant ? "\nuse App\\Models\\Concerns\\HasTenant;" : '',
            'TENANT_TRAIT' => $isTenant ? "\n    use HasTenant;" : '',
        ]));
        $created[] = $modelPath;

        // 3. ServiceProvider (self contained module, modular native style)
        $providerPath = "{$modulePath}/Providers/{$model}ServiceProvider.php";
        $this->put($providerPath, $this->render('provider', [
            'MODEL' => $model,
            'NOTE' => $isTenant
                ? 'Migrasi modul ini berada di Database/Migrations/Tenant dan dijalankan saat provisi tenant.'
                : 'Migrasi pusat modul ini dimuat otomatis oleh provider.',
            'BOOT' => $isTenant
                ? "        //\n"
                : "        \$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');\n",
        ]));
        $created[] = $providerPath;

        // 4. Panel resource definition (discovered by the ResourceRegistry)
        $panelPath = "{$modulePath}/Panel/{$model}Resource.php";
        $this->put($panelPath, $this->render('panel', [
            'MODEL' => $model,
            'RESOURCE_KEY' => $resourceKey,
            'DATABASE' => $connection,
            'TITLE' => addslashes($label),
            'SINGULAR' => addslashes(Str::headline($model)),
            'GROUP' => addslashes($group ?? 'Modul'),
            'ICON' => addslashes($icon),
            'DESCRIPTION' => addslashes($description),
            'COLUMNS' => $this->panelColumns($fields),
            'SEARCH' => $this->searchList($fields),
            'FILTERS' => $this->panelFilters($fields),
            'FIELDS' => $this->panelFields($fields),
            'SORT_COLUMN' => $sort,
            'SORT_DIRECTION' => 'asc',
        ]));
        $created[] = $panelPath;

        // 5. Permissions + super admin
        $permissions = Acl::permissionsFor($resourceKey);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::where('name', Acl::SUPER_ADMIN_ROLE)
            ->first()
            ?->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 6. Sidebar menu entry
        $menu = $this->createMenu($label, $plural, $icon, $resourceKey, $group);

        // 7. Definition record
        CrudDefinition::updateOrCreate(
            ['resource_key' => $resourceKey],
            [
                'module' => $model,
                'model' => $model,
                'table_name' => $table,
                'database' => $connection,
                'label' => $label,
                'icon' => $icon,
                'group' => $group,
                'description' => $description !== '' ? $description : null,
                'fields' => $fields,
                'created_by' => auth()->id(),
            ],
        );

        // 8. Run the migration — only for central modules. Tenant migrations
        //    are applied when a tenant database is provisioned.
        if (! $isTenant) {
            Artisan::call('migrate', [
                '--path' => 'Modules/'.$model.'/Database/Migrations',
                '--force' => true,
            ]);
        }

        return [
            'module' => $modulePath,
            'model' => "Modules\\{$model}\\Models\\{$model}",
            'database' => $connection,
            'key' => $plural,
            'title' => $label,
            'menu' => $menu,
            'permissions' => $permissions,
            'files' => $created,
            'url' => route('panel.index', $plural),
        ];
    }

    /**
     * Absolute path of the modules root directory.
     */
    protected function moduleRoot(): string
    {
        return rtrim((string) config('modules.path', base_path('Modules')), '/\\');
    }

    /**
     * Create the sidebar menu entry (and its optional group) for a module.
     */
    protected function createMenu(string $label, string $plural, string $icon, string $resourceKey, ?string $group): Menu
    {
        $parent = null;

        if ($group !== null) {
            $parent = Menu::firstOrCreate(
                ['key' => Str::slug($group, '_')],
                [
                    'type' => Menu::TYPE_GROUP,
                    'title' => ['id' => $group, 'en' => $group, 'ar' => $group],
                    'label' => $group,
                    'icon' => 'bars-3',
                    'is_active' => true,
                    'is_visible' => true,
                    'sort' => (int) Menu::whereNull('parent_id')->max('sort') + 1,
                ],
            );
        }

        return Menu::updateOrCreate(
            ['key' => $plural],
            [
                'parent_id' => $parent?->id,
                'type' => Menu::TYPE_LINK,
                'title' => ['id' => $label, 'en' => $label, 'ar' => $label],
                'label' => $label,
                'icon' => $icon,
                'resource' => $plural,
                'permission' => Acl::name('view_any', $resourceKey),
                'is_active' => true,
                'is_visible' => true,
                'sort' => (int) Menu::where('parent_id', $parent?->id)->max('sort') + 1,
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeFields(array $fields): array
    {
        $reserved = ['id', 'slug', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'];

        return collect($fields)
            ->map(function (array $field): array {
                $name = Str::snake(trim((string) ($field['name'] ?? '')));
                $type = (string) ($field['type'] ?? 'string');

                return [
                    'name' => $name,
                    'label' => filled($field['label'] ?? null) ? (string) $field['label'] : Str::headline($name),
                    'type' => array_key_exists($type, self::FIELD_TYPES) ? $type : 'string',
                    'nullable' => (bool) ($field['nullable'] ?? false),
                    'default' => filled($field['default'] ?? null) ? (string) $field['default'] : null,
                    'options' => $this->parseOptions($field['options'] ?? null),
                    'in_form' => (bool) ($field['in_form'] ?? true),
                    'in_table' => (bool) ($field['in_table'] ?? true),
                ];
            })
            ->reject(function (array $field) use ($reserved): bool {
                if ($field['name'] === '') {
                    return true;
                }

                if (! preg_match('/^[a-z][a-z0-9_]*$/', $field['name'])) {
                    throw new RuntimeException("Nama field [{$field['name']}] tidak valid. Gunakan huruf kecil dan underscore.");
                }

                return in_array($field['name'], $reserved, true);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function tableFields(array $fields): array
    {
        return array_values(array_filter($fields, fn (array $field): bool => $field['in_table']));
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function formFields(array $fields): array
    {
        return array_values(array_filter($fields, fn (array $field): bool => $field['in_form']));
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function panelColumns(array $fields): string
    {
        $slug = "                ['data' => 'slug', 'title' => 'Slug', 'orderable' => true, 'value' => fn (\$r) => '<span class=\"font-mono text-xs text-gray-500 dark:text-gray-400\">'.e(\$r->slug).'</span>'],";

        $columns = collect($this->tableFields($fields))
            ->map(function (array $field): string {
                $orderable = in_array($field['type'], ['text', 'boolean'], true) ? 'false' : 'true';

                return "                ['data' => '{$field['name']}', 'title' => ".$this->quote($field['label'])
                    .", 'orderable' => {$orderable}, 'value' => fn (\$r) => ".$this->columnValue($field).'],';
            })
            ->implode("\n");

        return $slug."\n".$columns;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function columnValue(array $field): string
    {
        $name = $field['name'];

        return match ($field['type']) {
            'boolean' => "Html::boolean((bool) \$r->{$name})",
            'select' => "Html::badge(\$r->{$name})",
            'text' => "Html::truncate(\$r->{$name}, 60)",
            'date' => "Html::date(\$r->{$name})",
            'datetime' => "Html::date(\$r->{$name}, 'd/m/Y H:i')",
            'time' => "Html::date(\$r->{$name}, 'H:i')",
            default => "Html::text((string) \$r->{$name})",
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function searchList(array $fields): string
    {
        $terms = collect($fields)
            ->filter(fn (array $field): bool => in_array($field['type'], ['string', 'text', 'email'], true))
            ->map(fn (array $field): string => $this->quote($field['name']))
            ->prepend($this->quote('slug'));

        return $terms->implode(', ');
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function panelFilters(array $fields): string
    {
        return collect($fields)
            ->map(function (array $field): ?string {
                $options = match (true) {
                    $field['type'] === 'boolean' => "['1' => 'Ya', '0' => 'Tidak']",
                    $field['type'] === 'select' && $field['options'] !== [] => $this->phpArray($field['options']),
                    default => null,
                };

                if ($options === null) {
                    return null;
                }

                return "                ['name' => '{$field['name']}', 'label' => ".$this->quote($field['label'])
                    .", 'type' => 'select', 'options' => {$options}],";
            })
            ->filter()
            ->implode("\n");
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function panelFields(array $fields): string
    {
        $slug = "                ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'update_rules' => [], 'col' => 'half', 'help' => 'Alamat URL record. Kosongkan untuk otomatis dari nama.'],";

        $fieldsHtml = collect($this->formFields($fields))
            ->map(function (array $field): string {
                $type = $this->panelFieldType($field['type']);
                $options = $type === 'select' && $field['options'] !== []
                    ? ", 'options' => ".$this->phpArray($field['options'])
                    : '';
                $rules = collect($this->fieldRules($field))
                    ->map(fn (string $rule): string => $this->quote($rule))
                    ->implode(', ');

                return "                ['name' => '{$field['name']}', 'label' => ".$this->quote($field['label'])
                    .", 'type' => '{$type}'{$options}, 'rules' => [{$rules}], 'update_rules' => [], 'col' => '"
                    .($type === 'textarea' ? 'full' : 'half')."', 'help' => null],";
            })
            ->implode("\n");

        return $slug."\n".$fieldsHtml;
    }

    /**
     * Field type used by the panel form renderer.
     */
    protected function panelFieldType(string $type): string
    {
        return match ($type) {
            'string' => 'text',
            'text' => 'textarea',
            'integer', 'decimal' => 'number',
            'boolean' => 'toggle',
            'datetime' => 'datetime-local',
            default => $type,
        };
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    protected function fieldRules(array $field): array
    {
        $rules = [];

        if ($field['type'] !== 'boolean') {
            $rules[] = $field['nullable'] ? 'nullable' : 'required';
        }

        $rules[] = match ($field['type']) {
            'email' => 'email',
            'integer' => 'integer',
            'decimal' => 'numeric',
            'boolean' => 'boolean',
            'date', 'datetime' => 'date',
            'time' => 'date_format:H:i',
            'select' => 'string',
            default => 'string',
        };

        if (in_array($field['type'], ['string', 'email', 'select'], true)) {
            $rules[] = 'max:255';
        }

        if ($field['type'] === 'select' && $field['options'] !== []) {
            $rules[] = 'in:'.implode(',', array_keys($field['options']));
        }

        return $rules;
    }

    /**
     * Turn a comma/newline separated option list into a value => label map.
     *
     * @return array<string, string>
     */
    protected function parseOptions(?string $options): array
    {
        return collect(preg_split('/[\n,]+/', (string) $options) ?: [])
            ->map(fn (string $option): string => trim($option))
            ->filter()
            ->mapWithKeys(fn (string $option): array => [Str::slug($option, '_') => Str::headline($option)])
            ->all();
    }

    /**
     * @param  array<string, string>  $map
     */
    protected function phpArray(array $map): string
    {
        $pairs = collect($map)
            ->map(fn (string $label, string $value): string => $this->quote($value).' => '.$this->quote($label))
            ->implode(', ');

        return '['.$pairs.']';
    }

    protected function quote(string $value): string
    {
        return "'".addslashes($value)."'";
    }

    /**
     * Render a module stub from `stubs/module`.
     *
     * @param  array<string, string>  $replacements
     */
    protected function render(string $stub, array $replacements): string
    {
        $contents = File::get(base_path("stubs/module/{$stub}.stub"));

        foreach ($replacements as $key => $value) {
            $contents = str_replace("%{$key}%", $value, $contents);
        }

        return $contents;
    }
}

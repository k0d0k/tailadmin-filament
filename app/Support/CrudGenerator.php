<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Generates a Model, migration, Filament Resource (form + table + pages) and an
 * optional Policy, then registers the RBAC permissions and a sidebar menu entry
 * for the new module.
 */
class CrudGenerator
{
    /**
     * Field types that can be generated, keyed by the type id.
     *
     * @var array<string, string>
     */
    public const FIELD_TYPES = [
        'string' => 'Text (singkat)',
        'text' => 'Text (panjang)',
        'email' => 'Email',
        'integer' => 'Angka bulat',
        'decimal' => 'Angka desimal',
        'boolean' => 'Boolean (Ya/Tidak)',
        'date' => 'Tanggal',
        'datetime' => 'Tanggal & Waktu',
        'time' => 'Waktu',
        'select' => 'Dropdown (pilihan)',
        'json' => 'JSON',
    ];

    /**
     * Icons offered in the generator form.
     *
     * @return array<string, string>
     */
    public static function iconOptions(): array
    {
        return Icons::options();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function generate(array $data): array
    {
        $model = Str::studly((string) $data['model']);
        $plural = Str::pluralStudly($model);
        $table = Str::snake($plural);
        $resourceKey = Str::snake($model);
        $label = filled($data['label'] ?? null) ? (string) $data['label'] : Str::headline($plural);
        $icon = (string) ($data['icon'] ?? 'heroicon-o-rectangle-stack');
        $group = filled($data['navigation_group'] ?? null) ? (string) $data['navigation_group'] : null;

        $fields = $this->normalizeFields($data['fields'] ?? []);

        if ($fields === []) {
            throw new RuntimeException('Definisikan minimal satu field untuk modul ini.');
        }

        if (File::exists(app_path("Models/{$model}.php"))) {
            throw new RuntimeException("Model [App\\Models\\{$model}] sudah ada. Gunakan nama lain.");
        }

        $resourceClass = "App\\Filament\\Resources\\{$plural}\\{$model}Resource";

        $created = [];

        // 1. Migration
        $migrationName = date('Y_m_d_His')."_create_{$table}_table";
        $migrationPath = database_path("migrations/{$migrationName}.php");
        $this->put($migrationPath, $this->render('migration', [
            'TABLE' => $table,
            'COLUMNS' => $this->migrationColumns($fields),
        ]));
        $created[] = $migrationPath;

        // 2. Model
        $modelPath = app_path("Models/{$model}.php");
        $this->put($modelPath, $this->render('model', [
            'MODEL' => $model,
            'SLUG_SOURCE' => $this->slugSource($fields),
            'FILLABLE' => $this->fillable($fields),
            'CASTS' => $this->casts($fields),
        ]));
        $created[] = $modelPath;

        // 3. Filament Resource
        $directory = app_path("Filament/Resources/{$plural}");
        $this->put("{$directory}/{$model}Resource.php", $this->render('resource', [
            'MODEL' => $model,
            'PLURAL' => $plural,
            'LABEL' => addslashes($label),
            'ICON' => $icon,
        ]));
        $this->put("{$directory}/Schemas/{$model}Form.php", $this->render('form', [
            'MODEL' => $model,
            'PLURAL' => $plural,
            'FORM_IMPORTS' => $this->formImports($fields),
            'FORM_COMPONENTS' => $this->formComponents($fields),
        ]));
        $this->put("{$directory}/Tables/{$plural}Table.php", $this->render('table', [
            'MODEL' => $model,
            'PLURAL' => $plural,
            'TABLE_IMPORTS' => $this->tableImports($fields),
            'TABLE_COLUMNS' => $this->tableColumns($fields),
        ]));
        foreach (['page_list', 'page_create', 'page_edit'] as $page) {
            $this->put(
                "{$directory}/Pages/".match ($page) {
                    'page_list' => "List{$plural}.php",
                    'page_create' => "Create{$model}.php",
                    default => "Edit{$model}.php",
                },
                $this->render($page, ['MODEL' => $model, 'PLURAL' => $plural]),
            );
        }
        $created[] = $directory;

        // 4. Policy
        $this->put(app_path("Policies/{$model}Policy.php"), $this->render('policy', [
            'MODEL' => $model,
            'RESOURCE_KEY' => $resourceKey,
        ]));
        $created[] = app_path("Policies/{$model}Policy.php");

        // 5. Permissions + super admin
        $permissions = Acl::permissionsFor($resourceKey);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::where('name', Acl::SUPER_ADMIN_ROLE)
            ->first()
            ?->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 6. Menu entry
        $parent = null;
        if ($group !== null) {
            $parent = Menu::firstOrCreate(
                ['label' => $group, 'parent_id' => null],
                ['sort' => (int) (Menu::max('sort') + 1), 'is_active' => true],
            );
        }

        $menu = Menu::create([
            'parent_id' => $parent?->id,
            'label' => $label,
            'icon' => $icon,
            'resource' => $resourceClass,
            'permission' => Acl::name('view_any', $resourceKey),
            'sort' => (int) (Menu::where('parent_id', $parent?->id)->max('sort') + 1),
            'is_active' => true,
        ]);

        // 7. Run the migration
        Artisan::call('migrate', ['--force' => true]);
        $migrationOutput = trim(Artisan::output());

        return [
            'model' => "App\\Models\\{$model}",
            'resource' => $resourceClass,
            'menu' => $menu,
            'permissions' => $permissions,
            'files' => $created,
            'migration' => $migrationName,
            'migration_output' => $migrationOutput,
            'resource_url' => $this->resolveResourceUrl($resourceClass, $plural),
        ];
    }

    /**
     * Resolve the index URL of a freshly generated resource. Filament only
     * registers routes for resources that exist when the panel boots, so the
     * new resource has no route yet within the current request and we fall back
     * to the conventional `{panel}/{slug}` path.
     */
    protected function resolveResourceUrl(string $resourceClass, string $plural): string
    {
        try {
            return $resourceClass::getUrl('index');
        } catch (Throwable) {
            return url('admin/'.Str::kebab($plural));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeFields(array $fields): array
    {
        $reserved = ['id', 'slug', 'created_at', 'updated_at', 'deleted_at'];

        return collect($fields)
            ->map(function (array $field): array {
                $name = Str::snake(trim((string) ($field['name'] ?? '')));
                $type = (string) ($field['type'] ?? 'string');

                return [
                    'name' => $name,
                    'type' => array_key_exists($type, self::FIELD_TYPES) ? $type : 'string',
                    'nullable' => (bool) ($field['nullable'] ?? false),
                    'default' => filled($field['default'] ?? null) ? (string) $field['default'] : null,
                    'options' => filled($field['options'] ?? null) ? (string) $field['options'] : null,
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
     */
    protected function migrationColumns(array $fields): string
    {
        return collect($fields)->map(function (array $field): string {
            $line = match ($field['type']) {
                'text' => "            \$table->text('{$field['name']}')",
                'integer' => "            \$table->integer('{$field['name']}')",
                'decimal' => "            \$table->decimal('{$field['name']}', 12, 2)",
                'boolean' => "            \$table->boolean('{$field['name']}')",
                'date' => "            \$table->date('{$field['name']}')",
                'datetime' => "            \$table->dateTime('{$field['name']}')",
                'time' => "            \$table->time('{$field['name']}')",
                'json' => "            \$table->json('{$field['name']}')",
                default => "            \$table->string('{$field['name']}')",
            };

            if ($field['nullable'] || $field['type'] === 'json') {
                $line .= '->nullable()';
            }

            if ($field['default'] !== null) {
                $line .= '->default('.$this->defaultValue($field).')';
            }

            return $line.';';
        })->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function defaultValue(array $field): string
    {
        $value = $field['default'];

        return match ($field['type']) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'integer', 'decimal' => is_numeric($value) ? $value : '0',
            default => "'".addslashes($value)."'",
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function fillable(array $fields): string
    {
        return collect($fields)
            ->map(fn (array $field): string => "        '{$field['name']}',")
            ->implode("\n");
    }

    /**
     * Pick the field the slug is generated from: the first common human
     * readable name, otherwise the first textual field.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function slugSource(array $fields): string
    {
        foreach (['name', 'title', 'label', 'subject', 'code', 'email'] as $candidate) {
            foreach ($fields as $field) {
                if ($field['name'] === $candidate) {
                    return $candidate;
                }
            }
        }

        foreach ($fields as $field) {
            if (in_array($field['type'], ['string', 'email', 'text', 'select'], true)) {
                return $field['name'];
            }
        }

        return $fields[0]['name'] ?? 'name';
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function casts(array $fields): string
    {
        $casts = collect($fields)
            ->filter(fn (array $field): bool => in_array($field['type'], ['boolean', 'integer', 'decimal', 'date', 'datetime', 'time', 'json'], true))
            ->map(fn (array $field): string => match ($field['type']) {
                'boolean' => "            '{$field['name']}' => 'boolean',",
                'integer' => "            '{$field['name']}' => 'integer',",
                'decimal' => "            '{$field['name']}' => 'decimal:2',",
                'date' => "            '{$field['name']}' => 'date',",
                'json' => "            '{$field['name']}' => 'array',",
                default => "            '{$field['name']}' => 'datetime',",
            })
            ->implode("\n");

        if ($casts === '') {
            return '';
        }

        return "\n    /**\n     * @return array<string, string>\n     */\n    protected function casts(): array\n    {\n        return [\n{$casts}\n        ];\n    }\n";
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function formImports(array $fields): string
    {
        $components = collect($fields)
            ->filter(fn (array $field): bool => $field['in_form'])
            ->map(fn (array $field): string => $this->formComponentClass($field['type']))
            ->unique()
            ->map(fn (string $class): string => "use {$class};")
            ->implode("\n");

        return $components;
    }

    protected function formComponentClass(string $type): string
    {
        return match ($type) {
            'text' => 'Filament\Forms\Components\Textarea',
            'boolean' => 'Filament\Forms\Components\Toggle',
            'date' => 'Filament\Forms\Components\DatePicker',
            'datetime' => 'Filament\Forms\Components\DateTimePicker',
            'time' => 'Filament\Forms\Components\TimePicker',
            'select' => 'Filament\Forms\Components\Select',
            'json' => 'Filament\Forms\Components\KeyValue',
            default => 'Filament\Forms\Components\TextInput',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function formComponents(array $fields): string
    {
        return collect($fields)
            ->filter(fn (array $field): bool => $field['in_form'])
            ->map(function (array $field): string {
                $label = Str::headline($field['name']);
                $required = $field['nullable'] ? '' : '->required()';
                $base = '                '.Str::afterLast($this->formComponentClass($field['type']), '\\')."::make('{$field['name']}')->label('{$label}')";

                return match ($field['type']) {
                    'email' => "{$base}->email()->maxLength(255){$required},",
                    'string' => "{$base}->maxLength(255){$required},",
                    'text' => "{$base}->rows(4)->columnSpanFull(){$required},",
                    'integer' => "{$base}->numeric()->integer(){$required},",
                    'decimal' => "{$base}->numeric(){$required},",
                    'select' => "{$base}->options({$this->selectOptions($field)}){$required},",
                    'json' => "{$base}->columnSpanFull(),",
                    'boolean' => "{$base},",
                    default => "{$base}{$required},",
                };
            })
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function selectOptions(array $field): string
    {
        $options = collect(preg_split('/[\n,]+/', (string) $field['options']))
            ->map(fn (string $option): string => trim($option))
            ->filter()
            ->map(fn (string $option): string => "'".addslashes($option)."' => '".Str::headline($option)."'")
            ->implode(', ');

        return '['.$options.']';
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function tableImports(array $fields): string
    {
        $imports = collect($fields)
            ->filter(fn (array $field): bool => $field['in_table'])
            ->map(fn (array $field): string => $field['type'] === 'boolean' ? 'Filament\Tables\Columns\IconColumn' : 'Filament\Tables\Columns\TextColumn')
            ->unique()
            ->push('Filament\Tables\Columns\TextColumn')
            ->unique()
            ->map(fn (string $class): string => "use {$class};")
            ->implode("\n");

        return $imports;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function tableColumns(array $fields): string
    {
        $columns = collect($fields)
            ->filter(fn (array $field): bool => $field['in_table'])
            ->map(function (array $field): string {
                $label = Str::headline($field['name']);
                $base = '                '.($field['type'] === 'boolean' ? 'IconColumn' : 'TextColumn')."::make('{$field['name']}')->label('{$label}')";

                return match ($field['type']) {
                    'boolean' => "{$base}->boolean(),",
                    'integer', 'decimal' => "{$base}->numeric()->sortable(),",
                    'date' => "{$base}->date()->sortable(),",
                    'datetime' => "{$base}->dateTime()->sortable(),",
                    'time' => "{$base}->time(),",
                    'text', 'json' => "{$base}->limit(50)->toggleable(),",
                    'select' => "{$base}->badge()->searchable(),",
                    default => "{$base}->searchable()->sortable(),",
                };
            });

        $columns->push("                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),");

        return $columns->implode("\n");
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function render(string $stub, array $replacements): string
    {
        $contents = File::get(base_path("stubs/crud/{$stub}.stub"));

        foreach ($replacements as $key => $value) {
            $contents = str_replace("%{$key}%", $value, $contents);
        }

        return $contents;
    }

    protected function put(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }
}

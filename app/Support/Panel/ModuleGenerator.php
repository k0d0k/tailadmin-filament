<?php

namespace App\Support\Panel;

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
 * Generates a native TailAdmin panel module: migration, model, policy, RBAC
 * permissions and a PanelResource definition that the ResourceRegistry
 * discovers automatically. File rendering and migration helpers are shared
 * with the Filament generator.
 */
class ModuleGenerator extends CrudGenerator
{
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

        if (File::exists(app_path("Models/{$model}.php"))) {
            throw new RuntimeException("Model [App\\Models\\{$model}] sudah ada. Gunakan nama lain.");
        }

        if (File::exists(app_path("Panel/Resources/{$model}Resource.php"))) {
            throw new RuntimeException("Modul [{$model}] sudah ada di panel.");
        }

        $created = [];
        $sort = $this->tableFields($fields)[0]['name'];

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
            'FILLABLE' => $this->fillable($fields),
            'CASTS' => $this->casts($fields),
        ]));
        $created[] = $modelPath;

        // 3. Policy (auto discovered by Laravel from App\Models\X -> App\Policies\XPolicy)
        $policyPath = app_path("Policies/{$model}Policy.php");
        $this->put($policyPath, $this->render('policy', [
            'MODEL' => $model,
            'RESOURCE_KEY' => $resourceKey,
        ]));
        $created[] = $policyPath;

        // 4. Panel definition
        $panelPath = app_path("Panel/Resources/{$model}Resource.php");
        $this->put($panelPath, $this->render('panel', [
            'MODEL' => $model,
            'RESOURCE_KEY' => $resourceKey,
            'TITLE' => addslashes($label),
            'SINGULAR' => addslashes(Str::headline($model)),
            'GROUP' => addslashes(filled($data['navigation_group'] ?? null) ? (string) $data['navigation_group'] : 'Modul'),
            'ICON' => addslashes(IconLibrary::normalize($data['icon'] ?? null) ?? 'table-cells'),
            'DESCRIPTION' => addslashes((string) ($data['description'] ?? '')),
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

        // 6. Run the migration
        Artisan::call('migrate', ['--force' => true]);

        return [
            'model' => "App\\Models\\{$model}",
            'key' => $plural,
            'title' => $label,
            'permissions' => $permissions,
            'files' => $created,
            'url' => route('panel.index', $plural),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeFields(array $fields): array
    {
        $reserved = ['id', 'created_at', 'updated_at', 'deleted_at'];

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
        return collect($this->tableFields($fields))
            ->map(function (array $field): string {
                $orderable = in_array($field['type'], ['text', 'boolean'], true) ? 'false' : 'true';

                return "                ['data' => '{$field['name']}', 'title' => ".$this->quote($field['label'])
                    .", 'orderable' => {$orderable}, 'value' => fn (\$r) => ".$this->columnValue($field).'],';
            })
            ->implode("\n");
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
        return collect($fields)
            ->filter(fn (array $field): bool => in_array($field['type'], ['string', 'text', 'email'], true))
            ->map(fn (array $field): string => $this->quote($field['name']))
            ->implode(', ');
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
        return collect($this->formFields($fields))
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
}

<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\Panel\IconLibrary;
use App\Support\Panel\ResourceRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Yajra\DataTables\Facades\DataTables;

/**
 * Generic CRUD controller that drives every module from the ResourceRegistry.
 * A single instance serves the six resources; the resource key comes from the
 * route and is validated against the registry on every request.
 */
class ResourceController extends Controller
{
    /* ---------------------------------------------------------------------
     | Listing
     | ------------------------------------------------------------------- */

    public function index(Request $request, string $resource): View|JsonResponse
    {
        $definition = ResourceRegistry::find($resource);

        Gate::authorize('viewAny', $definition['model']);

        if ($request->ajax() || $request->has('draw')) {
            return $this->dataTable($resource, $definition, $request);
        }

        return view('panel.index', $this->viewData($resource, $definition) + [
            'columns' => $this->tableColumns($definition),
            'filters' => $this->resolveFilters($definition),
        ]);
    }

    protected function dataTable(string $resource, array $definition, Request $request): JsonResponse
    {
        $query = $this->query($definition, $request);

        $table = DataTables::eloquent($query);

        $nativeColumns = $this->nativeColumns($definition['model']);
        $raw = [];

        foreach ($definition['columns'] as $column) {
            $name = $column['data'];

            if (! isset($column['value'])) {
                continue;
            }

            $value = $column['value'];

            if (! str_contains($name, '.') && in_array($name, $nativeColumns, true)) {
                $table->editColumn($name, fn (Model $row) => $value($row));
            } else {
                $table->addColumn($name, fn (Model $row) => $value($row));
            }

            $raw[] = $name;
        }

        $table->addColumn('actions', fn (Model $row) => view('panel.partials.row-actions', [
            'resource' => $resource,
            'permission' => $definition['resource'],
            'id' => $row->getKey(),
        ])->render());

        $raw[] = 'actions';

        if (filled($search = $this->searchTerm($request)) && filled($definition['search'] ?? [])) {
            $table->filter(function ($builder) use ($definition, $search) {
                $builder->where(function ($inner) use ($definition, $search) {
                    foreach ($definition['search'] as $column) {
                        $inner->orWhere($column, 'like', "%{$search}%");
                    }
                });
            });
        }

        return $table->rawColumns($raw)->toJson();
    }

    /* ---------------------------------------------------------------------
     | Create / store
     | ------------------------------------------------------------------- */

    public function create(string $resource): View
    {
        $definition = ResourceRegistry::find($resource);

        Gate::authorize('create', $definition['model']);

        return view('panel.form', $this->viewData($resource, $definition) + [
            'record' => null,
            'fields' => $this->resolveFields($definition, null),
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $definition = ResourceRegistry::find($resource);

        Gate::authorize('create', $definition['model']);

        $data = $request->validate($this->rules($definition));

        $model = $definition['model']::create($this->payload($definition, $request, $data));
        $this->syncRelations($definition, $model, $data);

        return redirect()
            ->route('panel.index', $resource)
            ->with('status', $definition['singular'].' berhasil dibuat.');
    }

    /* ---------------------------------------------------------------------
     | Edit / update
     | ------------------------------------------------------------------- */

    public function edit(string $resource, int $id): View
    {
        $definition = ResourceRegistry::find($resource);
        $record = $this->findRecord($definition, $id);

        Gate::authorize('update', $record);

        return view('panel.form', $this->viewData($resource, $definition) + [
            'record' => $record,
            'fields' => $this->resolveFields($definition + ['record' => $record], $record),
        ]);
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = ResourceRegistry::find($resource);
        $record = $this->findRecord($definition, $id);

        Gate::authorize('update', $record);

        $data = $request->validate($this->rules($definition, $record));

        $record->update($this->payload($definition, $request, $data));
        $this->syncRelations($definition, $record, $data);

        return redirect()
            ->route('panel.index', $resource)
            ->with('status', $definition['singular'].' berhasil diperbarui.');
    }

    /* ---------------------------------------------------------------------
     | Delete
     | ------------------------------------------------------------------- */

    public function destroy(string $resource, int $id): JsonResponse|RedirectResponse
    {
        $definition = ResourceRegistry::find($resource);
        $record = $this->findRecord($definition, $id);

        Gate::authorize('delete', $record);

        $record->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['message' => $definition['singular'].' berhasil dihapus.']);
        }

        return redirect()
            ->route('panel.index', $resource)
            ->with('status', $definition['singular'].' berhasil dihapus.');
    }

    /* ---------------------------------------------------------------------
     | Export
     | ------------------------------------------------------------------- */

    public function export(Request $request, string $resource, string $format)
    {
        $definition = ResourceRegistry::find($resource);

        Gate::authorize('viewAny', $definition['model']);

        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $rows = $this->query($definition, $request)->get();
        $headers = array_map(fn ($column) => $column['title'], $definition['columns']);

        $records = [$headers];

        foreach ($rows as $row) {
            $records[] = array_map(fn ($column) => $this->exportValue($column, $row), $definition['columns']);
        }

        $filename = Str::slug($definition['title']).'-'.now()->format('Ymd-His').'.'.$format;

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($records) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                foreach ($records as $record) {
                    fputcsv($out, $record);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->streamDownload(function () use ($records) {
            $writer = new XlsxWriter();
            $writer->openToFile('php://output');
            foreach ($records as $record) {
                $writer->addRow(Row::fromValues($record));
            }
            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /* ---------------------------------------------------------------------
     | Helpers
     | ------------------------------------------------------------------- */

    /**
     * @return array<string, mixed>
     */
    protected function viewData(string $resource, array $definition): array
    {
        return [
            'resourceKey' => $resource,
            'resource' => $definition['resource'],
            'definition' => $definition,
            'title' => $definition['title'],
            'singular' => $definition['singular'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function tableColumns(array $definition): array
    {
        $columns = array_map(fn ($column) => [
            'data' => $column['data'],
            'title' => $column['title'],
            'orderable' => $column['orderable'] ?? false,
            'className' => $column['className'] ?? '',
        ], $definition['columns']);

        $columns[] = ['data' => 'actions', 'title' => '', 'orderable' => false, 'className' => 'text-right'];

        return $columns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolveFilters(array $definition): array
    {
        return array_map(function ($filter) {
            $filter['options'] = is_callable($filter['options']) ? $filter['options']() : $filter['options'];

            return $filter;
        }, $definition['filters'] ?? []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolveFields(array $definition, ?Model $record): array
    {
        return array_map(function ($field) use ($definition, $record) {
            if (isset($field['options']) && is_callable($field['options'])) {
                $field['options'] = ($field['options'])($definition);
            }

            $value = $record ? data_get($record, $field['name']) : old($field['name']);

            if ($field['type'] === 'multiselect') {
                $value = collect($value ?? [])->map(fn ($item) => is_object($item) ? $item->getAttribute('name') : $item)->all();
            }

            if ($field['type'] === 'icon') {
                $value = IconLibrary::normalize($value);
            }

            $field['value'] = $value;
            $field['required'] = in_array('required', $field['rules'] ?? [], true);

            return $field;
        }, $definition['fields']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(array $definition, ?Model $record = null): array
    {
        $rules = [];

        foreach ($definition['fields'] as $field) {
            $fieldRules = $field['rules'] ?? [];

            if ($record && ! empty($field['update_rules'])) {
                $fieldRules = $field['update_rules'];
            }

            $rules[$field['name']] = array_map(
                fn ($rule) => is_string($rule) ? str_replace('{id}', (string) $record?->getKey(), $rule) : $rule,
                $fieldRules,
            );
        }

        return $rules;
    }

    /**
     * Build the validated payload, applying toggle defaults and dropping the
     * password field when it is left empty on update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function payload(array $definition, Request $request, array $data): array
    {
        foreach ($definition['fields'] as $field) {
            $name = $field['name'];

            if ($field['type'] === 'toggle') {
                $data[$name] = $request->boolean($name);
            }

            if ($field['type'] === 'password' && blank($data[$name] ?? null)) {
                unset($data[$name]);
            }

            if ($field['type'] === 'multiselect') {
                unset($data[$name]);
            }
        }

        return $data;
    }

    protected function syncRelations(array $definition, Model $model, array $data): void
    {
        foreach ($definition['fields'] as $field) {
            if ($field['type'] !== 'multiselect' || empty($field['relation'])) {
                continue;
            }

            $values = $data[$field['name']] ?? [];
            $relation = $field['relation'];

            // Spatie's helpers translate role/permission names into the related
            // records; a plain sync() would treat the names as primary keys.
            if ($relation === 'roles' && method_exists($model, 'syncRoles')) {
                $model->syncRoles($values);

                continue;
            }

            if ($relation === 'permissions' && method_exists($model, 'syncPermissions')) {
                $model->syncPermissions($values);

                continue;
            }

            if (method_exists($model, $relation)) {
                $model->{$relation}()->sync($values);
            }
        }
    }

    protected function query(array $definition, Request $request): Builder
    {
        $query = ($definition['query'])();

        foreach ((array) $request->input('filters', []) as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (isset($definition['filter_apply'][$name])) {
                ($definition['filter_apply'][$name])($query, $value);

                continue;
            }

            $query->where($name, $value);
        }

        if (filled($search = $this->searchTerm($request)) && filled($definition['search'] ?? [])) {
            $query->where(function (Builder $inner) use ($definition, $search) {
                foreach ($definition['search'] as $column) {
                    $inner->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }

    protected function searchTerm(Request $request): ?string
    {
        $search = $request->input('search');

        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        return filled($search) ? (string) $search : null;
    }

    protected function findRecord(array $definition, int $id): Model
    {
        return $definition['model']::query()->findOrFail($id);
    }

    /**
     * @return array<int, string>
     */
    protected function nativeColumns(string $modelClass): array
    {
        static $cache = [];

        if (! isset($cache[$modelClass])) {
            $model = new $modelClass();

            $cache[$modelClass] = $model->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($model->getTable());
        }

        return $cache[$modelClass];
    }

    protected function exportValue(array $column, Model $row): string
    {
        if (isset($column['value'])) {
            $value = ($column['value'])($row);
        } else {
            $value = data_get($row, $column['data']);
        }

        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace('/\s+/', ' ', $value));
    }
}

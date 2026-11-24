<?php

namespace App\Support\Panel;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Catalogues the fields that can be dragged into a report, derived from the
 * same ResourceRegistry definitions that drive the CRUD modules. Columns that
 * reference a related model (either `relation.column` or a relation method)
 * are grouped as relations so the builder can present them separately.
 */
class ReportRegistry
{
    /**
     * All reportable sources keyed by resource key. The payload is JSON safe
     * (no closures) so it can be embedded in the builder view.
     *
     * @return array<string, array{key: string, title: string, fields: array<int, array<string, mixed>>}>
     */
    public static function sources(): array
    {
        $sources = [];

        foreach (ResourceRegistry::all() as $key => $definition) {
            $sources[$key] = [
                'key' => $key,
                'title' => $definition['title'] ?? $key,
                'fields' => self::catalog($definition),
            ];
        }

        return $sources;
    }

    /**
     * Field metadata (no value callback) for a definition.
     *
     * @return array<int, array{key: string, title: string, group: string, relation: ?string}>
     */
    public static function catalog(array $definition): array
    {
        $relations = self::relations($definition['model']);
        $fields = [];

        foreach ($definition['columns'] as $column) {
            $data = $column['data'];
            $relation = null;

            if (str_contains($data, '.')) {
                $relation = Str::before($data, '.');
            } elseif (in_array($data, $relations, true)) {
                $relation = $data;
            }

            $fields[] = [
                'key' => $data,
                'title' => $column['title'] ?? $data,
                'group' => $relation ? 'relation' : 'primary',
                'relation' => $relation,
            ];
        }

        return $fields;
    }

    /**
     * The raw registry column (including its value callback) for a data key.
     *
     * @return array<string, mixed>|null
     */
    public static function column(array $definition, string $key): ?array
    {
        foreach ($definition['columns'] as $column) {
            if (($column['data'] ?? null) === $key && isset($column['value'])) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Public relation method names on the model, detected through their return
     * type so inherited relations (e.g. Spatie roles) are picked up too.
     *
     * @return array<int, string>
     */
    protected static function relations(string $modelClass): array
    {
        static $cache = [];

        if (isset($cache[$modelClass])) {
            return $cache[$modelClass];
        }

        $names = [];

        foreach ((new ReflectionClass($modelClass))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $type = $method->getReturnType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $name = $type->getName();

            if (class_exists($name) && is_subclass_of($name, Relation::class)) {
                $names[] = $method->getName();
            }
        }

        return $cache[$modelClass] = $names;
    }
}

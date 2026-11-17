<?php

namespace App\Panel;

use Illuminate\Support\Str;

/**
 * Base class for panel modules produced by the CRUD generator. Every class
 * placed in App\Panel\Resources is picked up automatically by the
 * ResourceRegistry, so a generated module appears in the TailAdmin panel
 * without editing any registry code.
 */
abstract class PanelResource
{
    /**
     * Describe the module: model, columns, form fields and filters. The shape
     * is identical to the definitions written in {@see \App\Support\Panel\ResourceRegistry}.
     *
     * @return array<string, mixed>
     */
    abstract public static function definition(): array;

    /**
     * Registry key used in panel URLs, e.g. `products`.
     */
    public static function key(): string
    {
        return Str::plural(static::definition()['resource']);
    }
}

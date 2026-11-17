<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds a unique `slug` column to every application table so records can be
 * addressed by slug in the panel URLs (e.g. /panel/branches/acme/edit).
 * Laravel's own bookkeeping and the permission pivot tables are skipped, as
 * are tables that already ship a slug column.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $skip = [
        'migrations',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'personal_access_tokens',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
    ];

    /**
     * @var list<string>
     */
    private array $skipPrefixes = ['role_has_', 'model_has_', 'permission_has_', 'sqlite_'];

    /**
     * Candidate source attributes, in order of preference.
     *
     * @var list<string>
     */
    private array $sources = ['name', 'label', 'title', 'subject', 'code', 'email'];

    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (Schema::hasColumn($table, 'slug')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('slug')->nullable();
            });

            $this->backfill($table);

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unique('slug');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasColumn($table, 'slug')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropUnique(['slug']);
                $blueprint->dropColumn('slug');
            });
        }
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return collect(Schema::getTableListing(null, false))
            ->reject(fn (string $table): bool => in_array($table, $this->skip, true))
            ->reject(fn (string $table): bool => Str::startsWith($table, $this->skipPrefixes))
            ->values()
            ->all();
    }

    private function backfill(string $table): void
    {
        $source = $this->sourceColumn($table);
        $used = [];

        foreach (DB::table($table)->orderBy('id')->get(['id', $source]) as $row) {
            $base = Str::slug((string) ($row->{$source} ?? '')) ?: 'item';
            $slug = $base;
            $suffix = 1;

            while (isset($used[$slug])) {
                $slug = $base.'-'.(++$suffix);
            }

            $used[$slug] = true;

            DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    private function sourceColumn(string $table): string
    {
        foreach ($this->sources as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return 'id';
    }
};

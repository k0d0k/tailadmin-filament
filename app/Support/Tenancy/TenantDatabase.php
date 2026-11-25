<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

/**
 * Resolves and prepares the per-tenant database.
 *
 * The physical database is created by {@see create()} and then exposed to the
 * application as the `tenant` database connection by {@see configure()}. On
 * SQLite every tenant gets its own file; on MySQL/MariaDB a dedicated schema
 * is created on the same server.
 */
class TenantDatabase
{
    /**
     * Name of the runtime connection used to talk to a tenant database.
     */
    public const CONNECTION = 'tenant';

    public function driver(): string
    {
        $driver = config('tenancy.driver');

        if (filled($driver)) {
            return (string) $driver;
        }

        return (string) ($this->templateConfig()['driver'] ?? config('tenancy.connection', 'sqlite'));
    }

    /**
     * Base connection whose driver & credentials are reused for tenants.
     *
     * @return array<string, mixed>
     */
    protected function templateConfig(): array
    {
        $connection = (string) config('tenancy.template_connection', 'central');

        return (array) config("database.connections.{$connection}", []);
    }

    /**
     * Logical database name, also persisted on the tenant record.
     */
    public function name(Tenant $tenant): string
    {
        return $tenant->getAttribute('database')
            ?: config('tenancy.prefix').Str::slug((string) ($tenant->slug ?: 'tenant-'.$tenant->getKey()), '_');
    }

    /**
     * Absolute path of the SQLite file backing the tenant.
     */
    public function path(Tenant $tenant): string
    {
        return rtrim((string) config('tenancy.path'), '/').'/'.($tenant->slug ?: 'tenant-'.$tenant->getKey()).'.sqlite';
    }

    /**
     * Marshal a tenant database into existence and return its physical
     * identifier (a file path on SQLite, a schema name otherwise).
     */
    public function create(Tenant $tenant): string
    {
        $driver = $this->driver();

        if ($driver === 'sqlite') {
            $path = $this->path($tenant);
            $directory = dirname($path);

            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException("Tidak dapat membuat direktori tenant: {$directory}");
            }

            if (! file_exists($path)) {
                touch($path);
            }

            return $path;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $name = $this->name($tenant);
            $this->createServerDatabase($name);

            return $name;
        }

        throw new RuntimeException("Driver tenant [{$driver}] belum didukung.");
    }

    public function exists(Tenant $tenant): bool
    {
        if ($this->driver() === 'sqlite') {
            return file_exists($this->path($tenant));
        }

        $config = $this->templateConfig();
        $pdo = $this->serverConnection($config);
        $statement = $pdo->prepare('SHOW DATABASES LIKE ?');
        $statement->execute([$this->name($tenant)]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Point the `tenant` connection at the given tenant database.
     */
    public function configure(Tenant $tenant): void
    {
        $driver = $this->driver();
        $base = $this->templateConfig();

        $base['database'] = $driver === 'sqlite'
            ? $this->path($tenant)
            : $this->name($tenant);

        Config::set('database.connections.'.self::CONNECTION, $base);

        DB::purge(self::CONNECTION);
    }

    /**
     * Drop the runtime connection so the next tenant starts from a clean slate.
     */
    public function forget(): void
    {
        DB::purge(self::CONNECTION);
        Config::set('database.connections.'.self::CONNECTION, null);
    }

    /**
     * Point the tenant connection at the central database. Used when no tenant
     * is active so tenant bound models stay queryable in a single database
     * development setup.
     */
    public function fallbackToCentral(): void
    {
        $connection = (string) config('tenancy.template_connection', 'central');

        Config::set('database.connections.'.self::CONNECTION, (array) config("database.connections.{$connection}", []));

        DB::purge(self::CONNECTION);
    }

    protected function createServerDatabase(string $name): void
    {
        $config = $this->templateConfig();
        $pdo = $this->serverConnection($config);

        $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $name));
    }

    /**
     * Open a server level PDO connection (no database selected).
     *
     * @param  array<string, mixed>  $config
     */
    protected function serverConnection(array $config): PDO
    {
        $driver = $this->driver();
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;

        $pdo = new PDO("mysql:host={$host};port={$port}", $config['username'] ?? '', $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        return $pdo;
    }
}

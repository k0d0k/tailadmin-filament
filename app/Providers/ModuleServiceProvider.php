<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Discovers every generated module under `Modules/<Name>` and wires it into the
 * application: its service provider is registered, its route file loaded and
 * its migrations published to the migrator.
 *
 * This mirrors the modular monolith layout used by the reference application so
 * that CRUD generated modules are self contained and portable.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ($this->providers() as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
        foreach ($this->basePaths() as $path) {
            // Modules that ship their own ServiceProvider are self contained:
            // the provider loads its routes and migrations, so we must not
            // load them twice here.
            if (! $this->hasProvider($path)) {
                $routes = "{$path}/Routes/web.php";

                if (File::exists($routes)) {
                    $this->loadRoutesFrom($routes);
                }

                $migrations = "{$path}/Database/Migrations";

                if (File::isDirectory($migrations)) {
                    $this->loadMigrationsFrom($migrations);
                }
            }

            $views = "{$path}/Resources/views";

            if (File::isDirectory($views)) {
                $this->loadViewsFrom($views, 'module-'.strtolower(basename($path)));
            }
        }
    }

    /**
     * Whether the module ships its own service provider.
     */
    protected function hasProvider(string $path): bool
    {
        return (bool) (File::glob("{$path}/Providers/*ServiceProvider.php") ?: []);
    }

    /**
     * Absolute paths of every module directory.
     *
     * @return array<int, string>
     */
    protected function basePaths(): array
    {
        $root = (string) config('modules.path', base_path('Modules'));

        if (! File::isDirectory($root)) {
            return [];
        }

        return collect(File::directories($root))
            ->filter(fn (string $directory): bool => File::isDirectory("{$directory}/Providers")
                || File::isDirectory("{$directory}/Routes"))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Fully qualified service provider classes found inside the modules.
     *
     * @return array<int, class-string>
     */
    protected function providers(): array
    {
        $root = (string) config('modules.path', base_path('Modules'));

        if (! File::isDirectory($root)) {
            return [];
        }

        $providers = [];

        foreach (File::glob("{$root}/*/Providers/*ServiceProvider.php") ?: [] as $file) {
            $relative = str_replace([$root.'/', '.php'], '', $file);
            $class = 'Modules\\'.str_replace('/', '\\', $relative);

            if (class_exists($class)) {
                $providers[] = $class;
            }
        }

        return $providers;
    }
}

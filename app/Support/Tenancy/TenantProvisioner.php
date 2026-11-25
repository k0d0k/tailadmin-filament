<?php

namespace App\Support\Tenancy;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Provisions a tenant: creates its database, migrates the tenant schema and
 * creates the tenant administrator account (with a tenant role) in the central
 * database.
 */
class TenantProvisioner
{
    public function __construct(protected TenantDatabase $database) {}

    /**
     * @return array{database: string, admin: User}
     */
    public function provision(Tenant $tenant, ?string $adminEmail = null, ?string $adminPassword = null): array
    {
        try {
            $database = $this->database->create($tenant);

            $this->database->configure($tenant);
            $this->migrate();

            $admin = $this->createAdministrator($tenant, $adminEmail, $adminPassword);

            $tenant->forceFill([
                'database' => $this->database->name($tenant),
                'provisioned_at' => now(),
                'provision_error' => null,
            ])->save();

            return ['database' => $database, 'admin' => $admin];
        } finally {
            $this->database->forget();
        }
    }

    /**
     * Run the tenant schema migrations on the tenant connection. Includes the
     * core tenant migrations plus every `Modules/<Model>/Database/Migrations/Tenant`
     * directory so generated tenant modules are provisioned too.
     */
    protected function migrate(): void
    {
        $paths = array_filter([(string) config('tenancy.migrations')]);

        $root = (string) config('modules.path', base_path('Modules'));

        foreach (File::glob("{$root}/*/Database/Migrations/Tenant") ?: [] as $directory) {
            $paths[] = str_replace(base_path().'/', '', $directory);
        }

        if ($paths === []) {
            throw new RuntimeException('Path migrasi tenant belum dikonfigurasi.');
        }

        Artisan::call('migrate', [
            '--database' => TenantDatabase::CONNECTION,
            '--path' => array_values($paths),
            '--force' => true,
        ]);
    }

    /**
     * Create the tenant administrator inside the central database, linked to
     * the tenant and granted the configured tenant role.
     */
    protected function createAdministrator(Tenant $tenant, ?string $email, ?string $password): User
    {
        $email ??= str_replace('{tenant}', (string) ($tenant->slug ?: 'tenant-'.$tenant->getKey()), (string) config('tenancy.admin.email'));

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('tenancy.admin.name', 'Tenant Admin'),
                'password' => Hash::make((string) ($password ?? config('tenancy.admin.password'))),
                'email_verified_at' => now(),
                'principal_id' => $tenant->principal_id,
                'tenant_id' => $tenant->getKey(),
            ],
        );

        $role = (string) config('tenancy.admin.role', 'tenant_admin');

        if (Role::query()->where('name', $role)->exists()) {
            $user->syncRoles([$role]);
        }

        return $user;
    }
}

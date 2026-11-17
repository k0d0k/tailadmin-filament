<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Acl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the default login accounts, one per role. Passwords are only set
 * when the user is created, so re-running the seeder never resets an existing
 * password. The principal/tenant accounts are linked to the sample hierarchy
 * seeded by {@see OrganizationSeeder}.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $principal = Principal::query()->where('code', 'PRN-001')->first();
        $tenant = $principal?->tenants()->orderBy('id')->first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles([Acl::SUPER_ADMIN_ROLE]);

        $this->account(
            'principal@example.com',
            'Principal Admin',
            ['principal'],
            $principal?->id,
            null,
        );

        $this->account(
            'tenant@example.com',
            'Tenant Admin',
            ['tenant'],
            $principal?->id,
            $tenant?->id,
        );

        $this->account(
            'user@example.com',
            'User Biasa',
            ['user'],
            $principal?->id,
            $tenant?->id,
        );
    }

    /**
     * @param  array<int, string>  $roles
     */
    protected function account(
        string $email,
        string $name,
        array $roles,
        ?int $principalId,
        ?int $tenantId,
    ): void {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'principal_id' => $principalId,
                'tenant_id' => $tenantId,
            ],
        );

        $user->syncRoles($roles);
    }
}

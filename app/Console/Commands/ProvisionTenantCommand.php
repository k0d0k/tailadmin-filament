<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenancy\TenantProvisioner;
use Illuminate\Console\Command;
use Throwable;

/**
 * Provisions tenant databases on demand. Useful to (re)run provisioning for
 * tenants created while no queue worker was running, or to retry a failed
 * provisioning.
 */
class ProvisionTenantCommand extends Command
{
    protected $signature = 'tenants:provision {tenant? : Tenant slug, or all pending tenants when omitted}';

    protected $description = 'Create the database and administrator account for a tenant';

    public function handle(TenantProvisioner $provisioner): int
    {
        $query = Tenant::query();

        if ($slug = $this->argument('tenant')) {
            $query->where('slug', $slug);
        } else {
            $query->whereNull('provisioned_at');
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('Tidak ada tenant yang perlu diprovisi.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            try {
                $result = $provisioner->provision($tenant);
                $this->info("✔ {$tenant->name}: database [{$result['database']}] siap, admin {$result['admin']->email}.");
            } catch (Throwable $exception) {
                $tenant->forceFill(['provision_error' => $exception->getMessage()])->save();
                $this->error("✘ {$tenant->name}: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}

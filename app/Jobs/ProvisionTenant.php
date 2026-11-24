<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Support\Tenancy\TenantProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Creates a tenant database (and its administrator) in the background. The job
 * is dispatched automatically when a tenant is created, so the request that
 * created the tenant is never blocked by schema provisioning.
 */
class ProvisionTenant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Tenant $tenant) {}

    public function handle(TenantProvisioner $provisioner): void
    {
        $provisioner->provision($this->tenant);
    }

    public function failed(Throwable $exception): void
    {
        $this->tenant->forceFill([
            'provision_error' => Str::limit($exception->getMessage(), 1000, ''),
        ])->save();
    }
}

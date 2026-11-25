<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Resolves the active tenant for the current request and points the `tenant`
 * database connection at its database. The resolved model is bound into the
 * container as `active_tenant` so the rest of the application can read it.
 *
 * Resolution order:
 *   1. the tenant the signed in user belongs to;
 *   2. the tenant stored in the session (chosen from the tenant switcher).
 */
class ResolveTenant
{
    public function __construct(protected TenantDatabase $database) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolve($request);

        if ($tenant !== null) {
            $this->database->configure($tenant);
            app()->instance('active_tenant', $tenant);
        } else {
            app()->instance('active_tenant', null);
            $this->database->fallbackToCentral();
        }

        return $next($request);
    }

    protected function resolve(Request $request): ?Tenant
    {
        try {
            $user = $request->user();

            if ($user !== null && $user->getAttribute('tenant_id')) {
                return Tenant::query()->find($user->getAttribute('tenant_id'));
            }

            $sessionId = $request->session()->get('active_tenant_id');

            if ($sessionId) {
                return Tenant::query()->find($sessionId);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}

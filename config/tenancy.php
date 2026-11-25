<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant database connection
    |--------------------------------------------------------------------------
    |
    | Template connection whose driver and credentials are reused to spawn a
    | fresh database per tenant. `template_connection` defaults to the central
    | connection, `driver` is derived from it when left empty.
    |
    */

    'template_connection' => env('TENANCY_TEMPLATE_CONNECTION', 'central'),

    'driver' => env('TENANCY_DRIVER'),

    'connection' => env('TENANCY_DB_CONNECTION', env('CENTRAL_DB_CONNECTION', env('DB_CONNECTION', 'sqlite'))),

    /*
    |--------------------------------------------------------------------------
    | Tenant database location & naming
    |--------------------------------------------------------------------------
    */

    // Directory holding the per-tenant SQLite files.
    'path' => env('TENANCY_PATH', database_path('tenants')),

    // Prefix prepended to the tenant database name.
    'prefix' => env('TENANCY_PREFIX', 'tenant_'),

    // Migrations that build the tenant schema (relative to the base path).
    'migrations' => 'database/migrations/tenant',

    // Provision a tenant database automatically right after a tenant is created.
    'provision_on_create' => env('TENANCY_PROVISION_ON_CREATE', true),

    /*
    |--------------------------------------------------------------------------
    | Tenant admin account
    |--------------------------------------------------------------------------
    |
    | When a tenant database is provisioned, an administrator account is created
    | in the central database and linked to the new tenant with the role below.
    | The `{tenant}` placeholder is replaced with the tenant slug.
    |
    */

    'admin' => [
        'role' => 'tenant_admin',
        'name' => 'Tenant Admin',
        'email' => 'admin@{tenant}.local',
        'password' => env('TENANCY_ADMIN_PASSWORD', 'password'),
    ],

];

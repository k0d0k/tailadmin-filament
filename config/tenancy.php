<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant database connection
    |--------------------------------------------------------------------------
    |
    | Driver used to spawn a fresh database for every tenant. It defaults to
    | the driver of the central connection: on SQLite each tenant gets its own
    | file, on MySQL/MariaDB a dedicated database is created.
    |
    */

    'connection' => env('TENANCY_DB_CONNECTION', env('DB_CONNECTION', 'sqlite')),

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

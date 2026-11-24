<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks the per-tenant database created when a tenant is provisioned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('database')->nullable()->after('code');
            $table->timestamp('provisioned_at')->nullable()->after('database');
            $table->text('provision_error')->nullable()->after('provisioned_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['database', 'provisioned_at', 'provision_error']);
        });
    }
};

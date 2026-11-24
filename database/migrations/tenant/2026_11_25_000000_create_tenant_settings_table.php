<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Initial schema of a tenant database. Only tenant scoped, business level
 * tables live here; the central tables (users, roles, permissions, tenants)
 * stay in the central database.
 *
 * Modules scaffolded by the CRUD generator can add their own migrations to
 * this directory to shape the tenant database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};

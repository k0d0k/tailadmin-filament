<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the presentation metadata used by the Role & Access screen: a human
 * readable label, the scope (central / tenant) and a description.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('label')->nullable()->after('name');
            $table->string('scope')->default('central')->after('label');
            $table->string('description')->nullable()->after('scope');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['label', 'scope', 'description']);
        });
    }
};

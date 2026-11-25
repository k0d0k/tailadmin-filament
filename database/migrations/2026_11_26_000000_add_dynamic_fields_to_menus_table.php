<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the menus table into a fully dynamic navigation definition: a
 * multi language title (id/en/ar), a stable key, a node type, an optional
 * route name, the originating module and per-language visibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table): void {
            $table->json('title')->nullable()->after('parent_id');
            $table->string('key')->nullable()->after('title');
            $table->string('type')->default('link')->after('key');
            $table->string('route_name')->nullable()->after('url');
            $table->string('module_key')->nullable()->after('resource');
            $table->boolean('is_visible')->default(true)->after('is_active');

            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table): void {
            $table->dropUnique(['key']);
            $table->dropColumn(['title', 'key', 'type', 'route_name', 'module_key', 'is_visible']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crud_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('module');
            $table->string('model');
            $table->string('resource_key');
            $table->string('table_name');
            $table->string('database')->default('central');
            $table->string('label')->nullable();
            $table->string('icon')->nullable();
            $table->string('group')->nullable();
            $table->string('description')->nullable();
            $table->json('fields')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique('resource_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crud_definitions');
    }
};

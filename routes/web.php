<?php

use App\Http\Controllers\Panel\Auth\LoginController;
use App\Http\Controllers\Panel\AuditController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\GeneratorController;
use App\Http\Controllers\Panel\LocaleController;
use App\Http\Controllers\Panel\MenuBuilderController;
use App\Http\Controllers\Panel\PasswordController;
use App\Http\Controllers\Panel\ProfileController;
use App\Http\Controllers\Panel\ReportBuilderController;
use App\Http\Controllers\Panel\ResourceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('panel', DashboardController::class)->name('panel.dashboard');

    Route::get('panel/crud-generator', [GeneratorController::class, 'show'])->name('panel.generator');
    Route::post('panel/crud-generator', [GeneratorController::class, 'generate'])->name('panel.generator.store');

    Route::get('panel/profile', [ProfileController::class, 'edit'])->name('panel.profile');
    Route::put('panel/profile', [ProfileController::class, 'update'])->name('panel.profile.update');

    Route::get('panel/password', [PasswordController::class, 'edit'])->name('panel.password.edit');
    Route::put('panel/password', [PasswordController::class, 'update'])->name('panel.password.update');

    Route::get('panel/menu-builder', [MenuBuilderController::class, 'index'])->name('panel.menu-builder');
    Route::post('panel/menu-builder', [MenuBuilderController::class, 'sync'])->name('panel.menu-builder.sync');

    Route::get('panel/report-builder', [ReportBuilderController::class, 'index'])->name('panel.report-builder');
    Route::post('panel/report-builder/run', [ReportBuilderController::class, 'run'])->name('panel.report-builder.run');
    Route::get('panel/report-builder/export/{format}', [ReportBuilderController::class, 'export'])
        ->where('format', 'csv|xlsx')->name('panel.report-builder.export');

    Route::get('panel/audit', [AuditController::class, 'index'])->name('panel.audit');
    Route::get('panel/audit/export/{format}', [AuditController::class, 'export'])
        ->where('format', 'csv|xlsx')->name('panel.audit.export');

    Route::get('panel/{resource}/export/{format}', [ResourceController::class, 'export'])->name('panel.export');
    Route::post('panel/{resource}/import', [ResourceController::class, 'import'])->name('panel.import');
    Route::get('panel/{resource}/import/template', [ResourceController::class, 'importTemplate'])->name('panel.import.template');
    Route::get('panel/{resource}/create', [ResourceController::class, 'create'])->name('panel.create');
    Route::post('panel/{resource}', [ResourceController::class, 'store'])->name('panel.store');
    Route::get('panel/{resource}/{slug}/edit', [ResourceController::class, 'edit'])
        ->where('slug', '[A-Za-z0-9_-]+')->name('panel.edit');
    Route::put('panel/{resource}/{slug}', [ResourceController::class, 'update'])
        ->where('slug', '[A-Za-z0-9_-]+')->name('panel.update');
    Route::delete('panel/{resource}/{slug}', [ResourceController::class, 'destroy'])
        ->where('slug', '[A-Za-z0-9_-]+')->name('panel.destroy');
    Route::get('panel/{resource}', [ResourceController::class, 'index'])->name('panel.index');
});

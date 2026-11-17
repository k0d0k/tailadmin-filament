<?php

use App\Http\Controllers\Panel\Auth\LoginController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\GeneratorController;
use App\Http\Controllers\Panel\PasswordController;
use App\Http\Controllers\Panel\ResourceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('panel', DashboardController::class)->name('panel.dashboard');

    Route::get('panel/crud-generator', [GeneratorController::class, 'show'])->name('panel.generator');
    Route::post('panel/crud-generator', [GeneratorController::class, 'generate'])->name('panel.generator.store');

    Route::get('panel/password', [PasswordController::class, 'edit'])->name('panel.password.edit');
    Route::put('panel/password', [PasswordController::class, 'update'])->name('panel.password.update');

    Route::get('panel/{resource}/export/{format}', [ResourceController::class, 'export'])->name('panel.export');
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

<?php

use App\Http\Controllers\Panel\Auth\LoginController;
use App\Http\Controllers\Panel\DashboardController;
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

    Route::get('panel/{resource}/export/{format}', [ResourceController::class, 'export'])->name('panel.export');
    Route::get('panel/{resource}/create', [ResourceController::class, 'create'])->name('panel.create');
    Route::post('panel/{resource}', [ResourceController::class, 'store'])->name('panel.store');
    Route::get('panel/{resource}/{id}/edit', [ResourceController::class, 'edit'])->name('panel.edit');
    Route::put('panel/{resource}/{id}', [ResourceController::class, 'update'])->name('panel.update');
    Route::delete('panel/{resource}/{id}', [ResourceController::class, 'destroy'])->name('panel.destroy');
    Route::get('panel/{resource}', [ResourceController::class, 'index'])->name('panel.index');
});

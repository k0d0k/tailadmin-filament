<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Stateless JSON API guarded by Laravel Sanctum personal access tokens.
| The mobile / SPA clients authenticate through `POST /api/login` and then
| send the returned bearer token on every subsequent request.
|
*/

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', fn (Request $request) => $request->user());
    Route::post('logout', [AuthController::class, 'logout']);
});

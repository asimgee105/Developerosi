<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Custom Authenticated Session routes with 2-device check & session termination
Route::post('/api/auth/login', [AuthController::class, 'login']);
Route::post('/api/auth/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/auth/sessions', [AuthController::class, 'getActiveSessions']);
    Route::delete('/api/auth/sessions/{id}', [AuthController::class, 'terminateSession']);

    // Custom Two-Factor activation endpoints
    Route::post('/api/auth/two-factor/setup', [AuthController::class, 'setupTwoFactor']);
    Route::post('/api/auth/two-factor/verify', [AuthController::class, 'verifyTwoFactor']);
    Route::post('/api/auth/two-factor/disable', [AuthController::class, 'disableTwoFactor']);
});


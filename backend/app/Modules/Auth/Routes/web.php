<?php

use App\Core\Http\ApiResponse;
use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::get('/csrf-cookie', fn (Request $request) => ApiResponse::success([
        'token' => $request->session()->token(),
    ]));
    Route::get('/me', [AuthController::class, 'current']);
    Route::get('/permissions', [AuthController::class, 'permissions']);
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');
    Route::post('/logout', [AuthController::class, 'logout']);
});

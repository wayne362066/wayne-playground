<?php

use App\Http\Controllers\AuthController;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes([
    'prefix' => 'api',
    'middleware' => ['web', 'throttle:120,1'],
]);

require __DIR__.'/channels.php';

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'message' => 'Playground API is running.',
    ]);
});

Route::prefix('api/auth')->group(function (): void {
    Route::get('/csrf-cookie', fn (Request $request) => ApiResponse::success([
        'token' => $request->session()->token(),
    ]));
    Route::get('/me', [AuthController::class, 'current']);
    Route::get('/permissions', [AuthController::class, 'permissions']);
    Route::patch('/profile', [AuthController::class, 'updateProfile'])
        ->middleware('auth');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');
    Route::post('/logout', [AuthController::class, 'logout']);
});

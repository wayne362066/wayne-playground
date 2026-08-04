<?php

use App\Http\Controllers\AccessManagementController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PowerLotteryController;
use App\Http\Controllers\PowerLotteryDuelController;
use App\Http\Controllers\WishController;
use App\Http\Controllers\WishManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::prefix('admin/access')
        ->middleware('permission:access.manage')
        ->group(function (): void {
            Route::get('/', [AccessManagementController::class, 'index']);
            Route::post('/roles', [AccessManagementController::class, 'storeRole']);
            Route::patch('/roles/{role}', [AccessManagementController::class, 'updateRole']);
            Route::delete('/roles/{role}', [AccessManagementController::class, 'destroyRole']);
            Route::patch('/users/{user}/roles', [AccessManagementController::class, 'updateUserRoles']);
        });

    Route::get('/modules', ModuleController::class);

    Route::post('/lottery/power/generate', [PowerLotteryController::class, 'generate'])
        ->middleware('permission:lottery.generate');
    Route::post('/lottery/power/simulate', [PowerLotteryController::class, 'simulate'])
        ->middleware('permission:lottery.simulate');

    Route::prefix('/lottery/duels')
        ->middleware(['permission:lottery.duel', 'throttle:120,1'])
        ->group(function (): void {
            Route::get('/', [PowerLotteryDuelController::class, 'index']);
            Route::get('/current', [PowerLotteryDuelController::class, 'current']);
            Route::post('/', [PowerLotteryDuelController::class, 'store'])
                ->middleware('throttle:lottery-duel-create');
            Route::get('/{room}', [PowerLotteryDuelController::class, 'show']);
            Route::post('/{room}/join', [PowerLotteryDuelController::class, 'join'])
                ->middleware('throttle:20,1');
            Route::post('/{room}/computer', [PowerLotteryDuelController::class, 'addComputer']);
            Route::post('/{room}/ready', [PowerLotteryDuelController::class, 'ready']);
            Route::post('/{room}/heartbeat', [PowerLotteryDuelController::class, 'heartbeat']);
            Route::post('/{room}/leave', [PowerLotteryDuelController::class, 'leave']);
            Route::post('/{room}/rematch', [PowerLotteryDuelController::class, 'rematch']);
        });

    Route::get('/wish-management', [WishManagementController::class, 'index']);
    Route::post('/wish-management/{id}/restore', [WishManagementController::class, 'restore']);

    Route::get('/wishes', [WishController::class, 'index']);
    Route::post('/wishes', [WishController::class, 'store']);
    Route::get('/wishes/{wish}', [WishController::class, 'show']);
    Route::patch('/wishes/{wish}', [WishController::class, 'update']);
    Route::delete('/wishes/{wish}', [WishController::class, 'destroy']);
});

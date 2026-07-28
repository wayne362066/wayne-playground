<?php

use App\Modules\Lottery\Controllers\PowerLotteryController;
use App\Modules\Lottery\Controllers\PowerLotteryDuelController;
use Illuminate\Support\Facades\Route;

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

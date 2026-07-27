<?php

use App\Modules\Lottery\Controllers\PowerLotteryController;
use Illuminate\Support\Facades\Route;

Route::post('/lottery/power/generate', [PowerLotteryController::class, 'generate'])
    ->middleware('permission:lottery.generate');
Route::post('/lottery/power/simulate', [PowerLotteryController::class, 'simulate'])
    ->middleware('permission:lottery.simulate');

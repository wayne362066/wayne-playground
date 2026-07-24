<?php

use App\Modules\Lottery\Controllers\PowerLotteryController;
use Illuminate\Support\Facades\Route;

Route::post('/lottery/power/generate', [PowerLotteryController::class, 'generate']);
Route::post('/lottery/power/simulate', [PowerLotteryController::class, 'simulate']);

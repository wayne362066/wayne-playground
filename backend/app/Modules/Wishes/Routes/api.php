<?php

use App\Modules\Wishes\Controllers\WishController;
use App\Modules\Wishes\Controllers\WishManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/wish-management', [WishManagementController::class, 'index']);
Route::post('/wish-management/{publicId}/restore', [WishManagementController::class, 'restore']);

Route::get('/wishes', [WishController::class, 'index']);
Route::post('/wishes', [WishController::class, 'store']);
Route::get('/wishes/{wish}', [WishController::class, 'show']);
Route::patch('/wishes/{wish}', [WishController::class, 'update']);
Route::delete('/wishes/{wish}', [WishController::class, 'destroy']);

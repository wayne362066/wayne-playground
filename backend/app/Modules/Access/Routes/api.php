<?php

use App\Modules\Access\Controllers\AccessManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/access')
    ->middleware('permission:access.manage')
    ->group(function (): void {
        Route::get('/', [AccessManagementController::class, 'index']);
        Route::post('/roles', [AccessManagementController::class, 'storeRole']);
        Route::patch('/roles/{role}', [AccessManagementController::class, 'updateRole']);
        Route::delete('/roles/{role}', [AccessManagementController::class, 'destroyRole']);
        Route::patch('/users/{user}/roles', [AccessManagementController::class, 'updateUserRoles']);
    });

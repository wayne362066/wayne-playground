<?php

use App\Modules\Home\Controllers\ModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/modules', ModuleController::class);

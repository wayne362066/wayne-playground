<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

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

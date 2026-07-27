<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $apiRouteFiles = glob(app_path('Modules/*/Routes/api.php')) ?: [];

        foreach ($apiRouteFiles as $routeFile) {
            Route::middleware(['web', 'api'])
                ->prefix('api')
                ->group($routeFile);
        }

        $webRouteFiles = glob(app_path('Modules/*/Routes/web.php')) ?: [];

        foreach ($webRouteFiles as $routeFile) {
            Route::middleware('web')
                ->prefix('api')
                ->group($routeFile);
        }
    }
}

<?php

namespace App\Providers;

use App\Core\Access\AuthorizationService;
use App\Modules\Wishes\Models\Wish;
use App\Modules\Wishes\Policies\WishPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(AuthorizationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Wish::class, WishPolicy::class);
    }
}

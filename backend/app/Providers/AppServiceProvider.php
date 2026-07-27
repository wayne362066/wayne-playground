<?php

namespace App\Providers;

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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Wish::class, WishPolicy::class);
    }
}

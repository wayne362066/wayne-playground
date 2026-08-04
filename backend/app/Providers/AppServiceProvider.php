<?php

namespace App\Providers;

use App\Models\Wish;
use App\Policies\WishPolicy;
use App\Services\AuthorizationService;
use App\Services\DuelParticipantService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(AuthorizationService::class);
        $this->app->scoped(DuelParticipantService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for(
            'lottery-duel-create',
            fn (Request $request): Limit => Limit::perMinute(20)->by(
                app(DuelParticipantService::class)->id($request),
            ),
        );

        Auth::viaRequest(
            'duel-participant',
            fn (Request $request) => app(DuelParticipantService::class)->identity($request),
        );

        Gate::policy(Wish::class, WishPolicy::class);
    }
}

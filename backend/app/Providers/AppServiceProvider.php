<?php

namespace App\Providers;

use App\Core\Access\AuthorizationService;
use App\Modules\Lottery\Services\DuelParticipantService;
use App\Modules\Wishes\Models\Wish;
use App\Modules\Wishes\Policies\WishPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $this->app->scoped(DuelParticipantService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::viaRequest(
            'duel-participant',
            fn (Request $request) => app(DuelParticipantService::class)->identity($request),
        );

        Gate::policy(Wish::class, WishPolicy::class);
    }
}

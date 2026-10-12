<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if ($this->app->environment('production') || env('APP_FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // Separate buckets: job polling (1 req/s while a run is in flight) must not eat the run budget.
        RateLimiter::for('sandbox-execute', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('sandbox-poll', fn (Request $r) => Limit::perMinute(240)->by($r->user()?->id ?: $r->ip()));
    }
}

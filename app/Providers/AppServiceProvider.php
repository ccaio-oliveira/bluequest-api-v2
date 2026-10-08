<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        Model::preventSilentlyDiscardingAttributes(!$this->app->environment('production'));
        Str::createUuidsUsing(fn () => Uuid::uuid7());

        RateLimiter::for('password-forgot', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}

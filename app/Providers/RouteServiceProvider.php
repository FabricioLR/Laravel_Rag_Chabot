<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json(['message' => 'Too many login attempts. Please try again later.'], 429);
            });
        });

        RateLimiter::for('api.general', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
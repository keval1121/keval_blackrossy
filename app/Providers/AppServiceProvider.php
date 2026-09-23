<?php

namespace App\Providers;

use App\View\Composers\StorefrontComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();
        View::composer('*', function ($view) {
            $name = $view->getName();
            if (
                str_starts_with($name, 'storefront.')
                || str_starts_with($name, 'layouts.')
                || str_starts_with($name, 'errors.')
                || str_starts_with($name, 'components.')
            ) {
                app(StorefrontComposer::class)->compose($view);
            }
        });

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));
        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('track', fn (Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(40)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}

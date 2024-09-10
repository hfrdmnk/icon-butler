<?php

namespace App\Providers;

use App\Contracts\IconRepositoryInterface;
use App\Repositories\PhosphorIconRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('icon.repository', function ($app) {
            return new PhosphorIconRepository;
        });

        $this->app->bind(IconRepositoryInterface::class, PhosphorIconRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('suggestions', function ($request) {
            return Limit::perMinute(2)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}

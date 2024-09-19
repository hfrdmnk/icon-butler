<?php

namespace App\Providers;

use App\Contracts\IconRepositoryInterface;
use App\Repositories\LucideIconRepository;
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
        $this->app->bind('icon.repository.phosphor', PhosphorIconRepository::class);
        $this->app->bind('icon.repository.lucide', LucideIconRepository::class);

        $this->app->bind(IconRepositoryInterface::class, PhosphorIconRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('suggestions', function ($request) {
            return Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}

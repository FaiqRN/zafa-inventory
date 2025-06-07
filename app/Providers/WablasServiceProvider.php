<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WablasService;

class WablasServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WablasService::class, function ($app) {
            return new WablasService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
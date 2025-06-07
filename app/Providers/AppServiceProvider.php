<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Pemesanan;
use App\Observers\PemesananObserver;
use App\Services\WablasService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Excel::macro('storeMultiple', function (...$args) {
            // Helper macro for Excel
        });

        $this->app->singleton(WablasService::class, function ($app) {
            return new WablasService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Pemesanan::observe(PemesananObserver::class);
    }
}

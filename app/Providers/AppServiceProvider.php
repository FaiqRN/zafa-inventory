<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Barang;
use App\Models\Pemesanan;
use App\Models\Toko;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Observers\BarangCacheObserver;
use App\Observers\PemesananObserver;
use App\Observers\TokoCacheObserver;
use App\Observers\PengirimanCacheObserver;
use App\Observers\ReturCacheObserver;
use App\Services\WablasService;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(WablasService::class, function () {
            return new WablasService();
        });
    }


    public function boot(): void
    {
        Barang::observe(BarangCacheObserver::class);
        Pemesanan::observe(PemesananObserver::class);
        Toko::observe(TokoCacheObserver::class);
        Pengiriman::observe(PengirimanCacheObserver::class);
        Retur::observe(ReturCacheObserver::class);
    }
}

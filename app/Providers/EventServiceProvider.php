<?php

namespace App\Providers;

use App\Models\Barang;
use App\Models\BarangStok;
use App\Models\Pemesanan;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Observers\BarangCacheObserver;
use App\Observers\StockTransactionObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register Barang cache observer
        Barang::observe(BarangCacheObserver::class);
        
        // Register Stock transaction observers
        $stockObserver = new StockTransactionObserver();
        
        Pemesanan::created(fn($model) => $stockObserver->createdPemesanan($model));
        Pemesanan::updated(fn($model) => $stockObserver->updatedPemesanan($model));
        Pemesanan::deleted(fn($model) => $stockObserver->deletedPemesanan($model));
        
        Pengiriman::created(fn($model) => $stockObserver->createdPengiriman($model));
        Pengiriman::updated(fn($model) => $stockObserver->updatedPengiriman($model));
        Pengiriman::deleted(fn($model) => $stockObserver->deletedPengiriman($model));
        
        Retur::created(fn($model) => $stockObserver->createdRetur($model));
        Retur::updated(fn($model) => $stockObserver->updatedRetur($model));
        Retur::deleted(fn($model) => $stockObserver->deletedRetur($model));
        
        BarangStok::created(fn($model) => $stockObserver->createdBarangStok($model));
        BarangStok::updated(fn($model) => $stockObserver->updatedBarangStok($model));
        BarangStok::deleted(fn($model) => $stockObserver->deletedBarangStok($model));
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

<?php

namespace App\Observers;

use App\Models\Barang;
use App\Services\BarangCacheService;

class BarangCacheObserver
{
    /**
     * Handle the Barang "created" event.
     */
    public function created(Barang $barang): void
    {
        BarangCacheService::clearAllCache();
    }

    /**
     * Handle the Barang "updated" event.
     */
    public function updated(Barang $barang): void
    {
        BarangCacheService::clearBarangCache($barang->barang_id);
    }

    /**
     * Handle the Barang "deleted" event.
     */
    public function deleted(Barang $barang): void
    {
        BarangCacheService::clearBarangCache($barang->barang_id);
    }
}

<?php

namespace App\Observers;

use App\Models\Pemesanan;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\BarangStok;
use App\Services\BarangCacheService;

class StockTransactionObserver
{
    /**
     * Clear stock cache when any stock-related transaction changes
     */
    private function clearStockCache($model): void
    {
        // Clear specific barang cache if barang_id exists
        if (isset($model->barang_id)) {
            BarangCacheService::clearBarangCache($model->barang_id);
        } else {
            // Fallback: clear all cache
            BarangCacheService::clearStockRelatedCache();
        }
    }

    // Pemesanan events
    public function createdPemesanan(Pemesanan $pemesanan): void
    {
        $this->clearStockCache($pemesanan);
    }

    public function updatedPemesanan(Pemesanan $pemesanan): void
    {
        $this->clearStockCache($pemesanan);
    }

    public function deletedPemesanan(Pemesanan $pemesanan): void
    {
        $this->clearStockCache($pemesanan);
    }

    // Pengiriman events
    public function createdPengiriman(Pengiriman $pengiriman): void
    {
        $this->clearStockCache($pengiriman);
    }

    public function updatedPengiriman(Pengiriman $pengiriman): void
    {
        $this->clearStockCache($pengiriman);
    }

    public function deletedPengiriman(Pengiriman $pengiriman): void
    {
        $this->clearStockCache($pengiriman);
    }

    // Retur events
    public function createdRetur(Retur $retur): void
    {
        $this->clearStockCache($retur);
    }

    public function updatedRetur(Retur $retur): void
    {
        $this->clearStockCache($retur);
    }

    public function deletedRetur(Retur $retur): void
    {
        $this->clearStockCache($retur);
    }

    // BarangStok events
    public function createdBarangStok(BarangStok $barangStok): void
    {
        $this->clearStockCache($barangStok);
    }

    public function updatedBarangStok(BarangStok $barangStok): void
    {
        $this->clearStockCache($barangStok);
    }

    public function deletedBarangStok(BarangStok $barangStok): void
    {
        $this->clearStockCache($barangStok);
    }
}

<?php

namespace App\Helpers\MasterData\barang;

use App\Models\Barang;
use App\Models\Pemesanan;
use App\Models\Pengiriman;
use App\Models\Retur;
use Illuminate\Support\Str;

class BarangHelper
{

    public static function calculateAvailableStock($barangId)
    {
        $barang = Barang::find($barangId);
        
        if (!$barang) {
            return 0;
        }

        $stokTersedia = BarangStokHelper::getStokTersedia($barangId);

        $pemesananOut = Pemesanan::where(Pemesanan::FIELD_BARANG_ID, $barangId)
            ->whereIn(Pemesanan::FIELD_STATUS_PEMESANAN, ['diproses', 'dikirim', 'selesai'])
            ->sum(Pemesanan::FIELD_JUMLAH_PESANAN);

        $pengirimanOut = Pengiriman::where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);

        $returIn = Retur::where(Retur::FIELD_BARANG_ID, $barangId)
            ->sum(Retur::FIELD_JUMLAH_RETUR);

        $availableStock = $stokTersedia - $pemesananOut - $pengirimanOut + $returIn;

        return max(0, $availableStock); 
    }

    public static function getStockDetails($barangId)
    {
        $barang = Barang::find($barangId);
        
        if (!$barang) {
            return [
                'base_stock' => 0,
                'fifo_stock' => 0,
                'pemesanan_out' => 0,
                'pengiriman_out' => 0,
                'retur_in' => 0,
                'available_stock' => 0,
                'stock_status' => 'out_of_stock',
                'batch_info' => []
            ];
        }

        $baseStock = $barang->stok ?? 0;
        $fifoStock = BarangStokHelper::getStokTersedia($barangId);
        $batchSummary = BarangStokHelper::getStokSummary($barangId);

        $pemesananOut = Pemesanan::where(Pemesanan::FIELD_BARANG_ID, $barangId)
            ->whereIn(Pemesanan::FIELD_STATUS_PEMESANAN, ['diproses', 'dikirim', 'selesai'])
            ->sum(Pemesanan::FIELD_JUMLAH_PESANAN);

        $pengirimanOut = Pengiriman::where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);

        $returIn = Retur::where(Retur::FIELD_BARANG_ID, $barangId)
            ->sum(Retur::FIELD_JUMLAH_RETUR);

        $availableStock = max(0, $fifoStock - $pemesananOut - $pengirimanOut + $returIn);

        return [
            'base_stock' => $baseStock,
            'fifo_stock' => $fifoStock,
            'pemesanan_out' => $pemesananOut,
            'pengiriman_out' => $pengirimanOut,
            'retur_in' => $returIn,
            'available_stock' => $availableStock,
            'stock_status' => self::getStockStatus($availableStock, $baseStock),
            'batch_count' => $batchSummary['total_batch'],
            'oldest_batch_date' => $batchSummary['batch_tertua'],
            'newest_batch_date' => $batchSummary['batch_terbaru']
        ];
    }

    public static function getStockSummary()
    {
        $barangList = \App\Services\BarangCacheService::getAllBarang();

        return $barangList->map(function ($barang) {
            return [
                'barang_id' => $barang->barang_id,
                'barang_kode' => $barang->barang_kode,
                'nama_barang' => $barang->nama_barang,
                'satuan' => $barang->satuan,
                'base_stock' => $barang->stok,
                'available_stock' => $barang->available_stock,
                'stock_status' => $barang->stock_status
            ];
        });
    }

    public static function getActiveBarang()
    {
        return \App\Services\BarangCacheService::getAllBarang();
    }

    public static function getBarangWithLowStock($threshold = 10)
    {
        $barangList = \App\Services\BarangCacheService::getAllBarang();

        return $barangList->filter(function ($barang) use ($threshold) {
            return $barang->available_stock > 0 && $barang->available_stock <= $threshold;
        });
    }

    public static function getBarangById($barangId)
    {
        $barang = Barang::where(Barang::FIELD_BARANG_ID, $barangId)
            ->first();

        if ($barang) {
            $stockDetails = self::getStockDetails($barangId);
            $barang->available_stock = $stockDetails['available_stock'];
            $barang->stock_details = $stockDetails;
            $barang->stock_status = $stockDetails['stock_status'];
        }

        return $barang;
    }

    public static function validateStockAvailability($barangId, $quantity)
    {
        $availableStock = self::calculateAvailableStock($barangId);

        return [
            'is_available' => $availableStock >= $quantity,
            'available_stock' => $availableStock,
            'requested_quantity' => $quantity,
            'shortage' => max(0, $quantity - $availableStock)
        ];
    }

    public static function generateUniqueBarangId()
    {
        $prefix = 'BRG';
        $padLength = 7; 

        $lastBarang = Barang::where(Barang::FIELD_BARANG_ID, 'like', $prefix . '%')
            ->whereRaw("SUBSTRING(barang_id, 4) REGEXP '^[0-9]+$'")
            ->orderByRaw('CAST(SUBSTRING(barang_id, 4) AS UNSIGNED) DESC')
            ->first();

        if (!$lastBarang) {
            return $prefix . str_pad('1', $padLength, '0', STR_PAD_LEFT);
        }

        $lastId = $lastBarang->{Barang::FIELD_BARANG_ID};
        $lastNumber = (int) substr($lastId, strlen($prefix));
        $nextNumber = $lastNumber + 1;

        return $prefix . str_pad($nextNumber, $padLength, '0', STR_PAD_LEFT);
    }

    public static function validateBarangKode($kode, $excludeId = null)
    {
        $query = Barang::where(Barang::FIELD_BARANG_KODE, $kode);

        if ($excludeId) {
            $query->where(Barang::FIELD_BARANG_ID, '!=', $excludeId);
        }

        return !$query->exists();
    }

    private static function getStockStatus($availableStock, $baseStock)
    {
        if ($availableStock <= 0) {
            return 'out_of_stock';
        }

        $lowStockThreshold = max(10, $baseStock * 0.2);
        
        if ($availableStock <= $lowStockThreshold) {
            return 'low_stock';
        }

        return 'sufficient';
    }

    public static function getNoCacheHeaders()
    {
        return [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];
    }

    public static function getStockBatchSummary($barangId)
    {
        $batches = BarangStokHelper::getDetailBatch($barangId);
        $summary = BarangStokHelper::getStokSummary($barangId);
        
        return [
            'batches' => $batches,
            'summary' => $summary
        ];
    }
}

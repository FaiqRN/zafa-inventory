<?php

namespace App\Helpers\MasterData\barang;

use App\Models\Barang;
use App\Models\Pemesanan;
use App\Models\Pengiriman;
use App\Models\Retur;
use Illuminate\Support\Str;

class BarangHelper
{
    /**
     * Calculate available stock for a specific barang
     * Formula: Base Stock - (Pemesanan Processed + Pengiriman) + Retur
     *
     * @param string $barangId
     * @return int
     */
    public static function calculateAvailableStock($barangId)
    {
        $barang = Barang::find($barangId);
        
        if (!$barang) {
            return 0;
        }

        // Base stock from barang table
        $baseStock = $barang->stok ?? 0;

        // Stock out from Pemesanan (only processed orders)
        $pemesananOut = Pemesanan::where(Pemesanan::FIELD_BARANG_ID, $barangId)
            ->whereIn(Pemesanan::FIELD_STATUS_PEMESANAN, ['diproses', 'dikirim', 'selesai'])
            ->sum(Pemesanan::FIELD_JUMLAH_PESANAN);

        // Stock out from Pengiriman
        $pengirimanOut = Pengiriman::where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);

        // Stock in from Retur
        $returIn = Retur::where(Retur::FIELD_BARANG_ID, $barangId)
            ->sum(Retur::FIELD_JUMLAH_RETUR);

        // Calculate available stock
        $availableStock = $baseStock - $pemesananOut - $pengirimanOut + $returIn;

        return max(0, $availableStock); // Ensure non-negative
    }

    /**
     * Get detailed stock breakdown for a specific barang
     *
     * @param string $barangId
     * @return array
     */
    public static function getStockDetails($barangId)
    {
        $barang = Barang::find($barangId);
        
        if (!$barang) {
            return [
                'base_stock' => 0,
                'pemesanan_out' => 0,
                'pengiriman_out' => 0,
                'retur_in' => 0,
                'available_stock' => 0,
                'stock_status' => 'out_of_stock'
            ];
        }

        $baseStock = $barang->stok ?? 0;

        $pemesananOut = Pemesanan::where(Pemesanan::FIELD_BARANG_ID, $barangId)
            ->whereIn(Pemesanan::FIELD_STATUS_PEMESANAN, ['diproses', 'dikirim', 'selesai'])
            ->sum(Pemesanan::FIELD_JUMLAH_PESANAN);

        $pengirimanOut = Pengiriman::where(Pengiriman::FIELD_BARANG_ID, $barangId)
            ->sum(Pengiriman::FIELD_JUMLAH_KIRIM);

        $returIn = Retur::where(Retur::FIELD_BARANG_ID, $barangId)
            ->sum(Retur::FIELD_JUMLAH_RETUR);

        $availableStock = max(0, $baseStock - $pemesananOut - $pengirimanOut + $returIn);

        return [
            'base_stock' => $baseStock,
            'pemesanan_out' => $pemesananOut,
            'pengiriman_out' => $pengirimanOut,
            'retur_in' => $returIn,
            'available_stock' => $availableStock,
            'stock_status' => self::getStockStatus($availableStock, $baseStock)
        ];
    }

    /**
     * Get stock summary for all active barang
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getStockSummary()
    {
        $barangList = Barang::all();

        return $barangList->map(function ($barang) {
            $stockDetails = self::getStockDetails($barang->barang_id);
            
            return [
                'barang_id' => $barang->barang_id,
                'barang_kode' => $barang->barang_kode,
                'nama_barang' => $barang->nama_barang,
                'satuan' => $barang->satuan,
                'base_stock' => $stockDetails['base_stock'],
                'available_stock' => $stockDetails['available_stock'],
                'stock_status' => $stockDetails['stock_status']
            ];
        });
    }

    /**
     * Get all active barang with stock information
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveBarang()
    {
        $barangList = Barang::orderBy(Barang::FIELD_BARANG_KODE, 'asc')
            ->get();

        return $barangList->map(function ($barang) {
            $stockDetails = self::getStockDetails($barang->barang_id);
            $barang->available_stock = $stockDetails['available_stock'];
            $barang->stock_status = $stockDetails['stock_status'];
            return $barang;
        });
    }

    /**
     * Get barang with low stock (below threshold)
     *
     * @param int $threshold
     * @return \Illuminate\Support\Collection
     */
    public static function getBarangWithLowStock($threshold = 10)
    {
        $barangList = Barang::all();

        return $barangList->filter(function ($barang) use ($threshold) {
            $availableStock = self::calculateAvailableStock($barang->barang_id);
            return $availableStock > 0 && $availableStock <= $threshold;
        })->map(function ($barang) {
            $stockDetails = self::getStockDetails($barang->barang_id);
            $barang->available_stock = $stockDetails['available_stock'];
            $barang->stock_status = $stockDetails['stock_status'];
            return $barang;
        });
    }

    /**
     * Get single barang by ID with stock details
     *
     * @param string $barangId
     * @return \App\Models\Barang|null
     */
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

    /**
     * Validate if stock is available for a given quantity
     *
     * @param string $barangId
     * @param int $quantity
     * @return array
     */
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

    /**
     * Generate unique barang ID
     *
     * @return string
     */
    public static function generateUniqueBarangId()
    {
        $barangId = 'BRG' . strtoupper(Str::random(7));
        
        // Check if ID already exists and regenerate if needed
        while (Barang::find($barangId)) {
            $barangId = 'BRG' . strtoupper(Str::random(7));
        }

        return $barangId;
    }

    /**
     * Validate barang kode uniqueness
     *
     * @param string $kode
     * @param string|null $excludeId
     * @return bool
     */
    public static function validateBarangKode($kode, $excludeId = null)
    {
        $query = Barang::where(Barang::FIELD_BARANG_KODE, $kode);

        if ($excludeId) {
            $query->where(Barang::FIELD_BARANG_ID, '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Get stock status based on available stock
     *
     * @param int $availableStock
     * @param int $baseStock
     * @return string
     */
    private static function getStockStatus($availableStock, $baseStock)
    {
        if ($availableStock <= 0) {
            return 'out_of_stock';
        }

        // Low stock if available is less than 20% of base stock or less than 10 units
        $lowStockThreshold = max(10, $baseStock * 0.2);
        
        if ($availableStock <= $lowStockThreshold) {
            return 'low_stock';
        }

        return 'sufficient';
    }

    /**
     * Get cache headers for no-cache responses
     *
     * @return array
     */
    public static function getNoCacheHeaders()
    {
        return [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];
    }
}

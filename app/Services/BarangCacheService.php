<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Pemesanan;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Helpers\MasterData\barang\BarangStokHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BarangCacheService
{
    // Cache keys
    const CACHE_KEY_ALL_BARANG = 'barang:all';
    const CACHE_KEY_BARANG_DETAIL = 'barang:detail:';
    const CACHE_KEY_STOCK_SUMMARY = 'barang:stock_summary';
    
    // Cache TTL (dalam detik)
    const CACHE_TTL_LIST = 3600;      // 1 jam untuk list
    const CACHE_TTL_DETAIL = 1800;    // 30 menit untuk detail
    const CACHE_TTL_STOCK = 900;      // 15 menit untuk stock (lebih sering berubah)

    /**
     * Get all barang dengan caching
     * Optimized: Single query dengan eager loading + batch calculation
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAllBarang()
    {
        return Cache::remember(self::CACHE_KEY_ALL_BARANG, self::CACHE_TTL_LIST, function () {
            return self::fetchAllBarangOptimized();
        });
    }

    /**
     * Fetch all barang dengan optimized query
     * Menghindari N+1 problem dengan batch calculation
     *
     * @return \Illuminate\Support\Collection
     */
    private static function fetchAllBarangOptimized()
    {
        // 1. Get all barang dalam satu query
        $barangList = Barang::select([
            'barang_id', 'barang_kode', 'nama_barang', 
            'harga_awal_barang', 'stok', 'satuan', 'keterangan'
        ])->orderBy('barang_kode', 'asc')->get();

        if ($barangList->isEmpty()) {
            return collect([]);
        }

        $barangIds = $barangList->pluck('barang_id')->toArray();

        // 2. Batch query untuk FIFO stock (sisa_stok)
        $fifoStocks = DB::table('barang_stok')
            ->select('barang_id', DB::raw('SUM(sisa_stok) as total_tersedia'))
            ->whereIn('barang_id', $barangIds)
            ->groupBy('barang_id')
            ->pluck('total_tersedia', 'barang_id')
            ->toArray();

        // 3. Batch query untuk Pemesanan (processed orders)
        $pemesananOut = Pemesanan::select('barang_id', DB::raw('SUM(jumlah_pesanan) as total'))
            ->whereIn('barang_id', $barangIds)
            ->whereIn('status_pemesanan', ['diproses', 'dikirim', 'selesai'])
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id')
            ->toArray();

        // 4. Batch query untuk Pengiriman
        $pengirimanOut = Pengiriman::select('barang_id', DB::raw('SUM(jumlah_kirim) as total'))
            ->whereIn('barang_id', $barangIds)
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id')
            ->toArray();

        // 5. Batch query untuk Retur
        $returIn = Retur::select('barang_id', DB::raw('SUM(jumlah_retur) as total'))
            ->whereIn('barang_id', $barangIds)
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id')
            ->toArray();

        // 6. Calculate stock untuk setiap barang
        return $barangList->map(function ($barang) use ($fifoStocks, $pemesananOut, $pengirimanOut, $returIn) {
            $barangId = $barang->barang_id;
            
            $fifoStock = $fifoStocks[$barangId] ?? 0;
            $pesananOut = $pemesananOut[$barangId] ?? 0;
            $kirimOut = $pengirimanOut[$barangId] ?? 0;
            $retIn = $returIn[$barangId] ?? 0;

            $availableStock = max(0, $fifoStock - $pesananOut - $kirimOut + $retIn);
            
            $barang->available_stock = $availableStock;
            $barang->stock_status = self::getStockStatus($availableStock, $barang->stok ?? 0);
            
            return $barang;
        });
    }

    /**
     * Get single barang dengan caching
     *
     * @param string $barangId
     * @return \App\Models\Barang|null
     */
    public static function getBarangById($barangId)
    {
        $cacheKey = self::CACHE_KEY_BARANG_DETAIL . $barangId;
        
        return Cache::remember($cacheKey, self::CACHE_TTL_DETAIL, function () use ($barangId) {
            $barang = Barang::find($barangId);
            
            if ($barang) {
                $stockDetails = self::calculateStockDetails($barangId);
                $barang->available_stock = $stockDetails['available_stock'];
                $barang->stock_details = $stockDetails;
                $barang->stock_status = $stockDetails['stock_status'];
            }
            
            return $barang;
        });
    }

    /**
     * Calculate stock details untuk single barang
     *
     * @param string $barangId
     * @return array
     */
    private static function calculateStockDetails($barangId)
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
                'stock_status' => 'out_of_stock'
            ];
        }

        $baseStock = $barang->stok ?? 0;
        $fifoStock = BarangStokHelper::getStokTersedia($barangId);

        $pemesananOut = Pemesanan::where('barang_id', $barangId)
            ->whereIn('status_pemesanan', ['diproses', 'dikirim', 'selesai'])
            ->sum('jumlah_pesanan');

        $pengirimanOut = Pengiriman::where('barang_id', $barangId)
            ->sum('jumlah_kirim');

        $returIn = Retur::where('barang_id', $barangId)
            ->sum('jumlah_retur');

        $availableStock = max(0, $fifoStock - $pemesananOut - $pengirimanOut + $returIn);

        return [
            'base_stock' => $baseStock,
            'fifo_stock' => $fifoStock,
            'pemesanan_out' => $pemesananOut,
            'pengiriman_out' => $pengirimanOut,
            'retur_in' => $returIn,
            'available_stock' => $availableStock,
            'stock_status' => self::getStockStatus($availableStock, $baseStock)
        ];
    }

    /**
     * Get stock status
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

        $lowStockThreshold = max(10, $baseStock * 0.2);
        
        if ($availableStock <= $lowStockThreshold) {
            return 'low_stock';
        }

        return 'sufficient';
    }

    /**
     * Clear all barang cache
     * Panggil ini saat ada perubahan data barang
     */
    public static function clearAllCache()
    {
        Cache::forget(self::CACHE_KEY_ALL_BARANG);
        Cache::forget(self::CACHE_KEY_STOCK_SUMMARY);
    }

    /**
     * Clear cache untuk specific barang
     *
     * @param string $barangId
     */
    public static function clearBarangCache($barangId)
    {
        Cache::forget(self::CACHE_KEY_BARANG_DETAIL . $barangId);
        Cache::forget(self::CACHE_KEY_ALL_BARANG); // Refresh list juga
    }

    /**
     * Clear cache yang terkait stock
     * Panggil saat ada transaksi (pemesanan, pengiriman, retur)
     */
    public static function clearStockRelatedCache()
    {
        Cache::forget(self::CACHE_KEY_ALL_BARANG);
        Cache::forget(self::CACHE_KEY_STOCK_SUMMARY);
        
        // Clear semua detail cache (gunakan tags jika pakai Redis)
        // Untuk file/database cache, perlu clear manual atau gunakan prefix pattern
    }

    /**
     * Refresh cache secara manual
     * Bisa dipanggil via artisan command atau scheduled job
     */
    public static function refreshCache()
    {
        self::clearAllCache();
        return self::getAllBarang();
    }
}

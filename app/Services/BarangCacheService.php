<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Pemesanan;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Helpers\MasterData\barang\BarangStokHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BarangCacheService
{
    const CACHE_KEY_ALL_BARANG = 'barang:all';
    const CACHE_KEY_BARANG_DETAIL = 'barang:detail:';
    const CACHE_KEY_STOCK_SUMMARY = 'barang:stock_summary';
    
    const CACHE_TTL_LIST = 3600;
    const CACHE_TTL_DETAIL = 1800;
    const CACHE_TTL_STOCK = 900;

    private static function isIncompleteObject($value): bool
    {
        return is_object($value) && get_class($value) === '__PHP_Incomplete_Class';
    }

    private static function rememberWithFallback(string $key, int $ttl, callable $callback)
    {
        try {
            $cachedValue = Cache::get($key);

            if ($cachedValue !== null) {
                if (self::isIncompleteObject($cachedValue)) {
                    Log::warning('Incomplete object found in cache, regenerating value', [
                        'cache_key' => $key,
                    ]);
                    Cache::forget($key);
                } else {
                    return $cachedValue;
                }
            }

            $freshValue = $callback();

            try {
                Cache::put($key, $freshValue, $ttl);
            } catch (Throwable $e) {
                Log::warning('Cache write failed, returning fresh value without caching', [
                    'cache_key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }

            return $freshValue;
        } catch (Throwable $e) {
            Log::warning('Cache read failed, using direct query fallback', [
                'cache_key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }


    public static function getAllBarang()
    {
        return self::rememberWithFallback(self::CACHE_KEY_ALL_BARANG, self::CACHE_TTL_LIST, function () {
            return self::fetchAllBarangOptimized();
        });
    }

    private static function fetchAllBarangOptimized()
    {
        $barangList = Barang::select([
            'barang_id', 'barang_kode', 'nama_barang',
            'harga_awal_barang', 'satuan', 'shelf_life', 'keterangan'
        ])->orderBy('barang_kode', 'asc')->get();

        if ($barangList->isEmpty()) {
            return collect([]);
        }

        $barangIds = $barangList->pluck('barang_id')->toArray();

        $fifoStocks = DB::table('barang_stok')
            ->select('barang_id', DB::raw('SUM(sisa_stok) as total_tersedia'))
            ->whereIn('barang_id', $barangIds)
            ->groupBy('barang_id')
            ->pluck('total_tersedia', 'barang_id')
            ->toArray();

        $pemesananOut = Pemesanan::select('barang_id', DB::raw('SUM(jumlah_pesanan) as total'))
            ->whereIn('barang_id', $barangIds)
            ->whereIn('status_pemesanan', ['diproses', 'dikirim', 'selesai'])
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id')
            ->toArray();

        $pengirimanOut = Pengiriman::select('barang_id', DB::raw('SUM(jumlah_kirim) as total'))
            ->whereIn('barang_id', $barangIds)
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id')
            ->toArray();

        $returIn = Retur::select('barang_id', DB::raw('SUM(jumlah_retur) as total'))
            ->whereIn('barang_id', $barangIds)
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id')
            ->toArray();

        return $barangList->map(function ($barang) use ($fifoStocks, $pemesananOut, $pengirimanOut, $returIn) {
            $barangId = $barang->barang_id;
            
            $fifoStock = $fifoStocks[$barangId] ?? 0;
            $pesananOut = $pemesananOut[$barangId] ?? 0;
            $kirimOut = $pengirimanOut[$barangId] ?? 0;
            $retIn = $returIn[$barangId] ?? 0;

            $availableStock = max(0, $fifoStock - $pesananOut - $kirimOut + $retIn);
            
            $barang->stok = $fifoStock;
            $barang->available_stock = $availableStock;
            $barang->stock_status = self::getStockStatus($availableStock, $fifoStock);
            
            return $barang;
        });
    }

    public static function getBarangById($barangId)
    {
        $cacheKey = self::CACHE_KEY_BARANG_DETAIL . $barangId;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_DETAIL, function () use ($barangId) {
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

        $baseStock = BarangStokHelper::getStokTersedia($barangId);
        $fifoStock = $baseStock;

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

    public static function clearAllCache()
    {
        try {
            Cache::forget(self::CACHE_KEY_ALL_BARANG);
            Cache::forget(self::CACHE_KEY_STOCK_SUMMARY);
        } catch (Throwable $e) {
            Log::warning('Cache clear failed for barang cache keys', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function clearBarangCache($barangId)
    {
        try {
            Cache::forget(self::CACHE_KEY_BARANG_DETAIL . $barangId);
            Cache::forget(self::CACHE_KEY_ALL_BARANG);
        } catch (Throwable $e) {
            Log::warning('Cache clear failed for barang detail cache key', [
                'barang_id' => $barangId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function clearStockRelatedCache()
    {
        try {
            Cache::forget(self::CACHE_KEY_ALL_BARANG);
            Cache::forget(self::CACHE_KEY_STOCK_SUMMARY);
        } catch (Throwable $e) {
            Log::warning('Cache clear failed for stock related cache keys', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function refreshCache()
    {
        self::clearAllCache();
        return self::getAllBarang();
    }
}

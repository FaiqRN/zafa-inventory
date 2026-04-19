<?php

namespace App\Services;

use App\Models\Retur;
use App\Models\Pengiriman;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReturCacheService
{
    const CACHE_KEY_RETUR_LIST = 'retur:list';
    const CACHE_KEY_RETUR_BY_NOMER = 'retur:by_nomer:';
    const CACHE_KEY_RETUR_BY_TOKO = 'retur:by_toko:';
    const CACHE_KEY_RETUR_BY_PENGIRIMAN = 'retur:by_pengiriman:';
    const CACHE_KEY_RETUR_STATS = 'retur:stats';
    const CACHE_KEY_RETUR_SUMMARY = 'retur:summary:';
    const CACHE_KEY_PENGIRIMAN_TERKIRIM = 'retur:pengiriman_terkirim';
    
    const CACHE_TTL_LIST = 1800;      // 30 menit
    const CACHE_TTL_DETAIL = 900;     // 15 menit
    const CACHE_TTL_STATS = 600;      // 10 menit

    private static function containsIncompleteObject($value, int $depth = 0): bool
    {
        $hasIncompleteObject = false;

        if ($depth <= 8) {
            if (is_object($value)) {
                $hasIncompleteObject = self::containsIncompleteObjectInObject($value, $depth + 1);
            } elseif (is_array($value)) {
                $hasIncompleteObject = self::containsIncompleteObjectInArray($value, $depth + 1);
            }
        }

        return $hasIncompleteObject;
    }

    private static function containsIncompleteObjectInObject(object $value, int $nextDepth): bool
    {
        if (get_class($value) === '__PHP_Incomplete_Class') {
            return true;
        }

        if ($value instanceof \Traversable) {
            return self::containsIncompleteObjectInArray(iterator_to_array($value), $nextDepth);
        }

        return false;
    }

    private static function containsIncompleteObjectInArray(array $items, int $nextDepth): bool
    {
        $hasIncompleteObject = false;

        foreach ($items as $item) {
            if (self::containsIncompleteObject($item, $nextDepth)) {
                $hasIncompleteObject = true;
                break;
            }
        }

        return $hasIncompleteObject;
    }

    private static function forgetCacheKey(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (Throwable $e) {
            Log::warning('Cache clear failed for retur key', [
                'cache_key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function rememberWithFallback(string $key, int $ttl, callable $callback)
    {
        try {
            $cachedValue = Cache::get($key);

            if ($cachedValue !== null) {
                if (self::containsIncompleteObject($cachedValue)) {
                    Log::warning('Incomplete object found in cache, regenerating value', [
                        'cache_key' => $key,
                    ]);
                    self::forgetCacheKey($key);
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

    public static function getAllRetur()
    {
        return self::rememberWithFallback(self::CACHE_KEY_RETUR_LIST, self::CACHE_TTL_LIST, function () {
            return Retur::with(['barang', 'toko', 'pengiriman'])
                ->orderBy(Retur::FIELD_TANGGAL_RETUR, 'desc')
                ->get();
        });
    }

    public static function getReturByNomer($nomerPengiriman)
    {
        $cacheKey = self::CACHE_KEY_RETUR_BY_NOMER . $nomerPengiriman;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_DETAIL, function () use ($nomerPengiriman) {
            return Retur::where(Retur::FIELD_NOMER_PENGIRIMAN, $nomerPengiriman)
                ->with(['barang', 'toko', 'pengiriman'])
                ->get();
        });
    }

    public static function getReturByToko($tokoId)
    {
        $cacheKey = self::CACHE_KEY_RETUR_BY_TOKO . $tokoId;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_LIST, function () use ($tokoId) {
            return Retur::where(Retur::FIELD_TOKO_ID, $tokoId)
                ->with(['barang', 'pengiriman'])
                ->orderBy(Retur::FIELD_TANGGAL_RETUR, 'desc')
                ->get();
        });
    }

    public static function getReturByPengiriman($pengirimanId)
    {
        $cacheKey = self::CACHE_KEY_RETUR_BY_PENGIRIMAN . $pengirimanId;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_DETAIL, function () use ($pengirimanId) {
            return Retur::where(Retur::FIELD_PENGIRIMAN_ID, $pengirimanId)
                ->with(['barang', 'toko'])
                ->first();
        });
    }

    public static function getPengirimanTerkirim($filters = [])
    {
        $cacheKey = self::buildPengirimanCacheKey($filters);
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_LIST, function () use ($filters) {
            $query = Pengiriman::with(['toko'])
                ->select('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status')
                ->where('status', 'terkirim')
                ->groupBy('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status');
        
            if (!empty($filters['toko_id'])) {
                $query->where('toko_id', $filters['toko_id']);
            }
        
            if (!empty($filters['date'])) {
                $query->whereDate('tanggal_pengiriman', $filters['date']);
            }
        
            return $query->orderBy('tanggal_pengiriman', 'desc')
                ->orderBy('nomer_pengiriman', 'desc')
                ->get();
        });
    }

    public static function getReturStats()
    {
        return self::rememberWithFallback(self::CACHE_KEY_RETUR_STATS, self::CACHE_TTL_STATS, function () {
            $totalRetur = DB::table('retur')
                ->select('nomer_pengiriman')
                ->distinct()
                ->count();
            
            $totalItems = Retur::count();
            $totalJumlahRetur = Retur::sum(Retur::FIELD_JUMLAH_RETUR);
            $totalJumlahTerjual = Retur::sum(Retur::FIELD_TOTAL_TERJUAL);
            $totalHasil = Retur::sum(Retur::FIELD_HASIL);
            $totalNilaiRetur = Retur::sum(DB::raw(Retur::FIELD_JUMLAH_RETUR . ' * ' . Retur::FIELD_HARGA_AWAL_BARANG));
            
            $byKondisi = Retur::select('kondisi', DB::raw('COUNT(*) as total'))
                ->groupBy('kondisi')
                ->pluck('total', 'kondisi')
                ->toArray();
            
            $byMonth = Retur::select(
                    DB::raw('DATE_FORMAT(tanggal_retur, "%Y-%m") as month'),
                    DB::raw('COUNT(DISTINCT nomer_pengiriman) as total'),
                    DB::raw('SUM(jumlah_retur) as total_retur'),
                    DB::raw('SUM(hasil) as total_hasil')
                )
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get();
            
            $avgPersentaseRetur = Retur::where(Retur::FIELD_JUMLAH_KIRIM, '>', 0)
                ->selectRaw('AVG((' . Retur::FIELD_JUMLAH_RETUR . ' / ' . Retur::FIELD_JUMLAH_KIRIM . ') * 100) as avg_persentase')
                ->value('avg_persentase');
            
            return [
                'total_retur' => $totalRetur,
                'total_items' => $totalItems,
                'total_jumlah_retur' => $totalJumlahRetur,
                'total_jumlah_terjual' => $totalJumlahTerjual,
                'total_hasil' => $totalHasil,
                'total_nilai_retur' => $totalNilaiRetur,
                'avg_persentase_retur' => round($avgPersentaseRetur ?? 0, 2),
                'by_kondisi' => $byKondisi,
                'by_month' => $byMonth,
            ];
        });
    }

    public static function getReturSummary($nomerPengiriman)
    {
        $cacheKey = self::CACHE_KEY_RETUR_SUMMARY . $nomerPengiriman;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_DETAIL, function () use ($nomerPengiriman) {
            $returList = Retur::where(Retur::FIELD_NOMER_PENGIRIMAN, $nomerPengiriman)
                ->with(['barang', 'toko'])
                ->get();
            
            if ($returList->isEmpty()) {
                return null;
            }
            
            $totalJumlahRetur = $returList->sum(Retur::FIELD_JUMLAH_RETUR);
            $totalJumlahTerjual = $returList->sum(Retur::FIELD_TOTAL_TERJUAL);
            $totalHasil = $returList->sum(Retur::FIELD_HASIL);
            $totalItems = $returList->count();
            $isLocked = $returList->where(Retur::FIELD_IS_LOCKED, true)->isNotEmpty();
            
            return [
                'nomer_pengiriman' => $nomerPengiriman,
                'total_items' => $totalItems,
                'total_jumlah_retur' => $totalJumlahRetur,
                'total_jumlah_terjual' => $totalJumlahTerjual,
                'total_hasil' => $totalHasil,
                'is_locked' => $isLocked,
                'tanggal_retur' => $returList->first()->{Retur::FIELD_TANGGAL_RETUR},
            ];
        });
    }

    public static function clearAllCache()
    {
        self::forgetCacheKey(self::CACHE_KEY_RETUR_LIST);
        self::forgetCacheKey(self::CACHE_KEY_RETUR_STATS);
        self::forgetCacheKey(self::CACHE_KEY_PENGIRIMAN_TERKIRIM);
        
        self::clearCachePattern('retur:pengiriman_terkirim:*');
    }

    public static function clearReturCache($nomerPengiriman)
    {
        self::forgetCacheKey(self::CACHE_KEY_RETUR_BY_NOMER . $nomerPengiriman);
        self::forgetCacheKey(self::CACHE_KEY_RETUR_SUMMARY . $nomerPengiriman);
        
        self::clearAllCache();
    }

    public static function clearTokoCache($tokoId)
    {
        self::forgetCacheKey(self::CACHE_KEY_RETUR_BY_TOKO . $tokoId);
        self::clearAllCache();
    }

    public static function clearPengirimanCache($pengirimanId)
    {
        self::forgetCacheKey(self::CACHE_KEY_RETUR_BY_PENGIRIMAN . $pengirimanId);
        self::clearAllCache();
    }

    public static function refreshCache()
    {
        self::clearAllCache();
        return self::getReturStats();
    }

    private static function buildPengirimanCacheKey($filters)
    {
        if (empty($filters)) {
            return self::CACHE_KEY_PENGIRIMAN_TERKIRIM;
        }
        
        $key = self::CACHE_KEY_PENGIRIMAN_TERKIRIM . ':';
        $parts = [];
        
        if (!empty($filters['toko_id'])) {
            $parts[] = 'toko_' . $filters['toko_id'];
        }
        
        if (!empty($filters['date'])) {
            $parts[] = 'date_' . $filters['date'];
        }
        
        return $key . implode('_', $parts);
    }

    private static function clearCachePattern($pattern)
    {
        try {
            if (config('cache.default') === 'redis') {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }
        } catch (Throwable $e) {
            Log::warning('Cache pattern clear failed for retur', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

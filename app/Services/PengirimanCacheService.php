<?php

namespace App\Services;

use App\Models\Pengiriman;
use App\Helpers\MasterData\Pengiriman\PengirimanHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PengirimanCacheService
{
    const CACHE_KEY_PENGIRIMAN_LIST = 'pengiriman:list';
    const CACHE_KEY_PENGIRIMAN_DETAIL = 'pengiriman:detail:';
    const CACHE_KEY_PENGIRIMAN_BY_TOKO = 'pengiriman:by_toko:';
    const CACHE_KEY_PENGIRIMAN_BY_STATUS = 'pengiriman:by_status:';
    const CACHE_KEY_PENGIRIMAN_STATS = 'pengiriman:stats';
    const CACHE_KEY_PENGIRIMAN_SUMMARY = 'pengiriman:summary:';
    
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
            Log::warning('Cache clear failed for pengiriman key', [
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

    public static function getPengirimanList($filters = [])
    {
        $cacheKey = self::buildListCacheKey($filters);
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_LIST, function () use ($filters) {
            $query = Pengiriman::select('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status')
                ->groupBy('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status');
            
            if (!empty($filters['toko_id'])) {
                $query->where(Pengiriman::FIELD_TOKO_ID, $filters['toko_id']);
            }
            
            if (!empty($filters['status'])) {
                $query->where(Pengiriman::FIELD_STATUS, $filters['status']);
            }
            
            if (!empty($filters['start_date'])) {
                $query->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $filters['start_date']);
            }
            
            if (!empty($filters['end_date'])) {
                $query->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '<=', $filters['end_date']);
            }
            
            return $query->orderBy(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, 'desc')
                ->orderBy(Pengiriman::FIELD_NOMER_PENGIRIMAN, 'desc')
                ->get();
        });
    }

    public static function getPengirimanByNomer($nomerPengiriman)
    {
        $cacheKey = self::CACHE_KEY_PENGIRIMAN_DETAIL . $nomerPengiriman;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_DETAIL, function () use ($nomerPengiriman) {
            return PengirimanHelper::getPengirimanByNomer($nomerPengiriman);
        });
    }

    public static function getPengirimanByToko($tokoId)
    {
        $cacheKey = self::CACHE_KEY_PENGIRIMAN_BY_TOKO . $tokoId;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_LIST, function () use ($tokoId) {
            return Pengiriman::where(Pengiriman::FIELD_TOKO_ID, $tokoId)
                ->with(['barang', 'toko'])
                ->orderBy(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, 'desc')
                ->get();
        });
    }

    public static function getPengirimanByStatus($status)
    {
        $cacheKey = self::CACHE_KEY_PENGIRIMAN_BY_STATUS . $status;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_LIST, function () use ($status) {
            return Pengiriman::where(Pengiriman::FIELD_STATUS, $status)
                ->with(['barang', 'toko'])
                ->orderBy(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, 'desc')
                ->get();
        });
    }

    public static function getPengirimanStats()
    {
        return self::rememberWithFallback(self::CACHE_KEY_PENGIRIMAN_STATS, self::CACHE_TTL_STATS, function () {
            $totalPengiriman = DB::table('pengiriman')
                ->select('nomer_pengiriman')
                ->distinct()
                ->count();
            
            $totalItems = Pengiriman::count();
            $totalJumlahKirim = Pengiriman::sum(Pengiriman::FIELD_JUMLAH_KIRIM);
            
            $byStatus = Pengiriman::select('status', DB::raw('COUNT(DISTINCT nomer_pengiriman) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();
            
            $byMonth = Pengiriman::select(
                    DB::raw('DATE_FORMAT(tanggal_pengiriman, "%Y-%m") as month'),
                    DB::raw('COUNT(DISTINCT nomer_pengiriman) as total')
                )
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get();
            
            return [
                'total_pengiriman' => $totalPengiriman,
                'total_items' => $totalItems,
                'total_jumlah_kirim' => $totalJumlahKirim,
                'by_status' => [
                    'proses' => $byStatus['proses'] ?? 0,
                    'terkirim' => $byStatus['terkirim'] ?? 0,
                    'batal' => $byStatus['batal'] ?? 0,
                ],
                'by_month' => $byMonth,
            ];
        });
    }

    public static function getPengirimanSummary($nomerPengiriman)
    {
        $cacheKey = self::CACHE_KEY_PENGIRIMAN_SUMMARY . $nomerPengiriman;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_DETAIL, function () use ($nomerPengiriman) {
            $pengirimanList = Pengiriman::where(Pengiriman::FIELD_NOMER_PENGIRIMAN, $nomerPengiriman)
                ->with(['barang', 'toko'])
                ->get();
            
            if ($pengirimanList->isEmpty()) {
                return null;
            }
            
            $totalJumlah = $pengirimanList->sum(Pengiriman::FIELD_JUMLAH_KIRIM);
            $totalItems = $pengirimanList->count();
            
            return [
                'nomer_pengiriman' => $nomerPengiriman,
                'total_jumlah' => $totalJumlah,
                'total_items' => $totalItems,
                'status' => $pengirimanList->first()->{Pengiriman::FIELD_STATUS},
                'tanggal_pengiriman' => $pengirimanList->first()->{Pengiriman::FIELD_TANGGAL_PENGIRIMAN},
            ];
        });
    }

    public static function clearAllCache()
    {
        self::forgetCacheKey(self::CACHE_KEY_PENGIRIMAN_LIST);
        self::forgetCacheKey(self::CACHE_KEY_PENGIRIMAN_STATS);
        
        foreach (['proses', 'terkirim', 'batal'] as $status) {
            self::forgetCacheKey(self::CACHE_KEY_PENGIRIMAN_BY_STATUS . $status);
        }
        
        self::clearCachePattern('pengiriman:list:*');
    }

    public static function clearPengirimanCache($nomerPengiriman)
    {
        self::forgetCacheKey(self::CACHE_KEY_PENGIRIMAN_DETAIL . $nomerPengiriman);
        self::forgetCacheKey(self::CACHE_KEY_PENGIRIMAN_SUMMARY . $nomerPengiriman);
        
        self::clearAllCache();
    }

    public static function clearTokoCache($tokoId)
    {
        self::forgetCacheKey(self::CACHE_KEY_PENGIRIMAN_BY_TOKO . $tokoId);
    }

    public static function refreshCache()
    {
        self::clearAllCache();
        return self::getPengirimanStats();
    }

    private static function buildListCacheKey($filters)
    {
        if (empty($filters)) {
            return self::CACHE_KEY_PENGIRIMAN_LIST;
        }
        
        $key = self::CACHE_KEY_PENGIRIMAN_LIST . ':';
        $parts = [];
        
        if (!empty($filters['toko_id'])) {
            $parts[] = 'toko_' . $filters['toko_id'];
        }
        
        if (!empty($filters['status'])) {
            $parts[] = 'status_' . $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $parts[] = 'start_' . $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $parts[] = 'end_' . $filters['end_date'];
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
            Log::warning('Cache pattern clear failed for pengiriman', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

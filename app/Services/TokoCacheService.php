<?php

namespace App\Services;

use App\Models\Toko;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TokoCacheService
{
    const CACHE_KEY_ALL_TOKO = 'toko:all';
    const CACHE_KEY_TOKO_DETAIL = 'toko:detail:';
    const CACHE_KEY_TOKO_LIST = 'toko:list';
    const CACHE_KEY_GEOCODING_STATS = 'toko:geocoding_stats';
    
    const CACHE_TTL_LIST = 3600;      // 1 jam
    const CACHE_TTL_DETAIL = 1800;    // 30 menit
    const CACHE_TTL_STATS = 900;      // 15 menit

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

    public static function getAllToko()
    {
        return self::rememberWithFallback(self::CACHE_KEY_ALL_TOKO, self::CACHE_TTL_LIST, function () {
            return Toko::orderBy(Toko::FIELD_CREATED_AT, 'desc')->get();
        });
    }

    public static function getTokoList()
    {
        return self::rememberWithFallback(self::CACHE_KEY_TOKO_LIST, self::CACHE_TTL_LIST, function () {
            return Toko::select([
                Toko::FIELD_TOKO_ID,
                Toko::FIELD_NAMA_TOKO,
                Toko::FIELD_PEMILIK,
                Toko::FIELD_ALAMAT,
                Toko::FIELD_WILAYAH_KECAMATAN,
                Toko::FIELD_WILAYAH_KELURAHAN,
                Toko::FIELD_WILAYAH_KOTA_KABUPATEN,
                Toko::FIELD_NOMER_TELPON,
                Toko::FIELD_LATITUDE,
                Toko::FIELD_LONGITUDE,
                Toko::FIELD_IS_ACTIVE,
                Toko::FIELD_GEOCODING_PROVIDER,
                Toko::FIELD_GEOCODING_QUALITY,
                Toko::FIELD_GEOCODING_SCORE,
                Toko::FIELD_GEOCODING_CONFIDENCE
            ])
            ->orderBy(Toko::FIELD_CREATED_AT, 'desc')
            ->get();
        });
    }

    public static function getTokoById($tokoId)
    {
        $cacheKey = self::CACHE_KEY_TOKO_DETAIL . $tokoId;
        
        return self::rememberWithFallback($cacheKey, self::CACHE_TTL_DETAIL, function () use ($tokoId) {
            return Toko::find($tokoId);
        });
    }

    public static function getGeocodingStats()
    {
        return self::rememberWithFallback(self::CACHE_KEY_GEOCODING_STATS, self::CACHE_TTL_STATS, function () {
            $tokos = Toko::all();
            
            $totalToko = $tokos->count();
            $withCoordinates = $tokos->filter(function($toko) {
                return $toko->{Toko::FIELD_LATITUDE} && $toko->{Toko::FIELD_LONGITUDE};
            })->count();
            
            $inMalangRegion = $tokos->filter(function($toko) {
                if (!$toko->{Toko::FIELD_LATITUDE} || !$toko->{Toko::FIELD_LONGITUDE}) {
                    return false;
                }
                return GeocodingService::isInMalangRegion(
                    $toko->{Toko::FIELD_LATITUDE},
                    $toko->{Toko::FIELD_LONGITUDE}
                );
            })->count();
            
            $qualityScores = $tokos->whereNotNull(Toko::FIELD_GEOCODING_SCORE);
            $avgScore = $qualityScores->count() > 0
                ? round($qualityScores->avg(Toko::FIELD_GEOCODING_SCORE), 1)
                : 0;
            
            return [
                'total_toko' => $totalToko,
                'with_coordinates' => $withCoordinates,
                'without_coordinates' => $totalToko - $withCoordinates,
                'quality_distribution' => [
                    'excellent' => $tokos->where(Toko::FIELD_GEOCODING_QUALITY, 'excellent')->count(),
                    'good' => $tokos->where(Toko::FIELD_GEOCODING_QUALITY, 'good')->count(),
                    'fair' => $tokos->where(Toko::FIELD_GEOCODING_QUALITY, 'fair')->count(),
                    'poor' => $tokos->where(Toko::FIELD_GEOCODING_QUALITY, 'poor')->count(),
                    'very_poor' => $tokos->where(Toko::FIELD_GEOCODING_QUALITY, 'very poor')->count(),
                    'failed' => $tokos->filter(function($toko) {
                        return $toko->{Toko::FIELD_GEOCODING_QUALITY} === 'failed'
                            || $toko->{Toko::FIELD_GEOCODING_QUALITY} === null;
                    })->count(),
                ],
                'provider_distribution' => [
                    'nominatim' => $tokos->where(Toko::FIELD_GEOCODING_PROVIDER, 'nominatim')->count(),
                    'interactive_map' => $tokos->where(Toko::FIELD_GEOCODING_PROVIDER, 'interactive_map')->count(),
                    'unknown' => $tokos->whereNull(Toko::FIELD_GEOCODING_PROVIDER)->count(),
                ],
                'average_quality_score' => $avgScore,
                'in_malang_region' => $inMalangRegion,
            ];
        });
    }

    public static function clearAllCache()
    {
        try {
            Cache::forget(self::CACHE_KEY_ALL_TOKO);
            Cache::forget(self::CACHE_KEY_TOKO_LIST);
            Cache::forget(self::CACHE_KEY_GEOCODING_STATS);
        } catch (Throwable $e) {
            Log::warning('Cache clear failed for toko cache keys', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function clearTokoCache($tokoId)
    {
        try {
            Cache::forget(self::CACHE_KEY_TOKO_DETAIL . $tokoId);
            Cache::forget(self::CACHE_KEY_ALL_TOKO);
            Cache::forget(self::CACHE_KEY_TOKO_LIST);
            Cache::forget(self::CACHE_KEY_GEOCODING_STATS);
        } catch (Throwable $e) {
            Log::warning('Cache clear failed for toko detail cache key', [
                'toko_id' => $tokoId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function refreshCache()
    {
        self::clearAllCache();
        return self::getAllToko();
    }
}

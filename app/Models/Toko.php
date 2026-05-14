<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\GeocodingService;

class Toko extends Model
{
    use HasFactory;

    public const TABLE = 'toko';
    public const FIELD_TOKO_ID = 'toko_id';
    public const FIELD_NAMA_TOKO = 'nama_toko';
    public const FIELD_PEMILIK = 'pemilik';
    public const FIELD_ALAMAT = 'alamat';
    public const FIELD_WILAYAH_KECAMATAN = 'wilayah_kecamatan';
    public const FIELD_WILAYAH_KELURAHAN = 'wilayah_kelurahan';
    public const FIELD_WILAYAH_KOTA_KABUPATEN = 'wilayah_kota_kabupaten';
    public const FIELD_NOMER_TELPON = 'nomer_telpon';
    public const FIELD_JALAN_ID = 'jalan_id';
    public const FIELD_LATITUDE = 'latitude';
    public const FIELD_LONGITUDE = 'longitude';
    public const FIELD_IS_ACTIVE = 'is_active';
    public const FIELD_CATATAN_LOKASI = 'catatan_lokasi';
    public const FIELD_ALAMAT_LENGKAP_GEOCODING = 'alamat_lengkap_geocoding';
    public const FIELD_GEOCODING_PROVIDER = 'geocoding_provider';
    public const FIELD_GEOCODING_ACCURACY = 'geocoding_accuracy';
    public const FIELD_GEOCODING_CONFIDENCE = 'geocoding_confidence';
    public const FIELD_GEOCODING_QUALITY = 'geocoding_quality';
    public const FIELD_GEOCODING_SCORE = 'geocoding_score';
    public const FIELD_GEOCODING_LAST_UPDATED = 'geocoding_last_updated';
    public const FIELD_GEOCODING_TIMESTAMP = 'geocoding_last_updated'; // Alias for consistency
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_USER_CREATE = 'user_create';
    public const FIELD_USER_UPDATE = 'user_update';
    public const FIELD_MIN_INTERVAL_KIRIM_HARI = 'min_interval_kirim_hari';

    protected $table = self::TABLE;
    protected $primaryKey = self::FIELD_TOKO_ID;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        self::FIELD_TOKO_ID,
        self::FIELD_NAMA_TOKO,
        self::FIELD_PEMILIK,
        self::FIELD_ALAMAT,
        self::FIELD_WILAYAH_KECAMATAN,
        self::FIELD_WILAYAH_KELURAHAN,
        self::FIELD_WILAYAH_KOTA_KABUPATEN,
        self::FIELD_NOMER_TELPON,
        // self::FIELD_JALAN_ID, // Removed - field doesn't exist in database
        self::FIELD_LATITUDE,
        self::FIELD_LONGITUDE,
        self::FIELD_IS_ACTIVE,
        self::FIELD_CATATAN_LOKASI,
        self::FIELD_ALAMAT_LENGKAP_GEOCODING,
        self::FIELD_GEOCODING_PROVIDER,
        self::FIELD_GEOCODING_ACCURACY,
        self::FIELD_GEOCODING_CONFIDENCE,
        self::FIELD_GEOCODING_QUALITY,
        self::FIELD_GEOCODING_SCORE,
        self::FIELD_GEOCODING_LAST_UPDATED,
        self::FIELD_USER_CREATE,
        self::FIELD_USER_UPDATE,
        self::FIELD_MIN_INTERVAL_KIRIM_HARI,
    ];

    protected $casts = [
        self::FIELD_LATITUDE => 'decimal:8',
        self::FIELD_LONGITUDE => 'decimal:8',
        self::FIELD_IS_ACTIVE => 'boolean',
        self::FIELD_GEOCODING_CONFIDENCE => 'decimal:3',
        self::FIELD_GEOCODING_SCORE => 'decimal:2',
        self::FIELD_GEOCODING_LAST_UPDATED => 'datetime',
        self::FIELD_MIN_INTERVAL_KIRIM_HARI => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($toko) {
            if ($toko->isDirty([self::FIELD_LATITUDE, self::FIELD_LONGITUDE, self::FIELD_GEOCODING_PROVIDER])) {
                $toko->{self::FIELD_GEOCODING_LAST_UPDATED} = now();
            }
        });

        static::creating(function ($toko) {
            if ($toko->{self::FIELD_LATITUDE} && $toko->{self::FIELD_LONGITUDE}) {
                $toko->{self::FIELD_GEOCODING_LAST_UPDATED} = now();
            }
        });
    }


    public function barangToko()
    {
        return $this->hasMany(BarangToko::class, BarangToko::FIELD_TOKO_ID, self::FIELD_TOKO_ID);
    }

    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, Pengiriman::FIELD_TOKO_ID, self::FIELD_TOKO_ID);
    }

    public function retur()
    {
        return $this->hasMany(Retur::class, Retur::FIELD_TOKO_ID, self::FIELD_TOKO_ID);
    }
    
    public function barang()
    {
        return $this->belongsToMany(Barang::class, BarangToko::TABLE, self::FIELD_TOKO_ID, Barang::FIELD_BARANG_ID)
                    ->withPivot(BarangToko::FIELD_BARANG_TOKO_ID, BarangToko::FIELD_HARGA_BARANG_TOKO);
    }

    public function scopeActive($query)
    {
        return $query->where(self::FIELD_IS_ACTIVE, true);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull(self::FIELD_LATITUDE)
                    ->whereNotNull(self::FIELD_LONGITUDE);
    }

    public function scopeByGeocodeQuality($query, $quality)
    {
        return $query->where(self::FIELD_GEOCODING_QUALITY, $quality);
    }

    public function scopeHighQualityGeocode($query)
    {
        return $query->whereIn(self::FIELD_GEOCODING_QUALITY, ['excellent', 'good']);
    }

    public function scopeNeedsRegeocoding($query)
    {
        return $query->where(function($q) {
            $q->whereNull(self::FIELD_LATITUDE)
              ->orWhereNull(self::FIELD_LONGITUDE)
              ->orWhereIn(self::FIELD_GEOCODING_QUALITY, ['poor', 'very poor', 'failed'])
              ->orWhereNull(self::FIELD_GEOCODING_QUALITY)
              ->orWhere(self::FIELD_GEOCODING_SCORE, '<', 70);
        });
    }

    public function scopeByQuality($query, $quality)
    {
        $qualityMap = [
            'excellent' => ['quality' => 'excellent', 'min_score' => 90],
            'good' => ['quality' => 'good', 'min_score' => 80],
            'fair' => ['quality' => 'fair', 'min_score' => 70],
            'poor' => ['quality' => 'poor', 'min_score' => 0],
        ];

        if (isset($qualityMap[$quality])) {
            return $query->where(self::FIELD_GEOCODING_QUALITY, $qualityMap[$quality]['quality']);
        }

        return $query;
    }

    public function scopeByWilayah($query, $kota = null, $kecamatan = null, $kelurahan = null)
    {
        if ($kota) {
            $query->where(self::FIELD_WILAYAH_KOTA_KABUPATEN, $kota);
        }
        
        if ($kecamatan) {
            $query->where(self::FIELD_WILAYAH_KECAMATAN, $kecamatan);
        }
        
        if ($kelurahan) {
            $query->where(self::FIELD_WILAYAH_KELURAHAN, $kelurahan);
        }
        
        return $query;
    }

    public function scopeInMalangRegion($query)
    {
        return $query->whereNotNull(self::FIELD_LATITUDE)
                    ->whereNotNull(self::FIELD_LONGITUDE)
                    ->where(function($q) {
                        $q->whereBetween(self::FIELD_LATITUDE, [-8.6, -7.4])
                          ->whereBetween(self::FIELD_LONGITUDE, [111.8, 113.2]);
                    });
    }

    public function getFullAddressAttribute()
    {
        return $this->{self::FIELD_ALAMAT} . ', ' . $this->{self::FIELD_WILAYAH_KELURAHAN} . ', ' . 
               $this->{self::FIELD_WILAYAH_KECAMATAN} . ', ' . $this->{self::FIELD_WILAYAH_KOTA_KABUPATEN};
    }

    public function getCoordinatesAttribute()
    {
        if ($this->{self::FIELD_LATITUDE} && $this->{self::FIELD_LONGITUDE}) {
            return $this->{self::FIELD_LATITUDE} . ',' . $this->{self::FIELD_LONGITUDE};
        }
        return null;
    }

    public function getGeocodingAddressAttribute()
    {
        return trim($this->{self::FIELD_ALAMAT} . ', ' . $this->{self::FIELD_WILAYAH_KELURAHAN} . ', ' . 
                   $this->{self::FIELD_WILAYAH_KECAMATAN} . ', ' . $this->{self::FIELD_WILAYAH_KOTA_KABUPATEN} . 
                   ', Jawa Timur, Indonesia');
    }

    public function getGeocodingStatusAttribute()
    {
        if (!$this->{self::FIELD_LATITUDE} || !$this->{self::FIELD_LONGITUDE}) {
            return [
                'status' => 'missing',
                'message' => 'Belum ada koordinat GPS',
                'badge_class' => 'secondary'
            ];
        }

        $quality = $this->{self::FIELD_GEOCODING_QUALITY} ?? 'unknown';
        
        $statusMap = [
            'excellent' => ['status' => 'excellent', 'message' => 'Sangat Akurat', 'badge_class' => 'success'],
            'good' => ['status' => 'good', 'message' => 'Akurat', 'badge_class' => 'primary'],
            'fair' => ['status' => 'fair', 'message' => 'Cukup Akurat', 'badge_class' => 'warning'],
            'poor' => ['status' => 'poor', 'message' => 'Kurang Akurat', 'badge_class' => 'danger'],
            'very poor' => ['status' => 'very_poor', 'message' => 'Tidak Akurat', 'badge_class' => 'danger'],
            'failed' => ['status' => 'failed', 'message' => 'Gagal', 'badge_class' => 'secondary'],
            'unknown' => ['status' => 'unknown', 'message' => 'Tidak Diketahui', 'badge_class' => 'info']
        ];

        return $statusMap[$quality] ?? $statusMap['unknown'];
    }

    public function hasValidCoordinates()
    {
        return $this->{self::FIELD_LATITUDE} && $this->{self::FIELD_LONGITUDE} && 
               abs($this->{self::FIELD_LATITUDE}) <= 90 && abs($this->{self::FIELD_LONGITUDE}) <= 180;
    }

    public function isInMalangRegion()
    {
        if (!$this->hasValidCoordinates()) {
            return false;
        }
        
        return GeocodingService::isInMalangRegion($this->{self::FIELD_LATITUDE}, $this->{self::FIELD_LONGITUDE});
    }

    public function isInIndonesia()
    {
        if (!$this->hasValidCoordinates()) {
            return false;
        }
        
        return GeocodingService::isInIndonesia($this->{self::FIELD_LATITUDE}, $this->{self::FIELD_LONGITUDE});
    }

    public function getDistanceFrom($lat, $lng)
    {
        if (!$this->hasValidCoordinates()) {
            return null;
        }

        $earthRadius = 6371;

        $dLat = deg2rad($lat - $this->{self::FIELD_LATITUDE});
        $dLng = deg2rad($lng - $this->{self::FIELD_LONGITUDE});

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($this->{self::FIELD_LATITUDE})) * cos(deg2rad($lat)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    public function getDistanceFromMalangCenter()
    {
        return $this->getDistanceFrom(-7.9666, 112.6326);
    }

    public function getStatistics()
    {
        $totalPengiriman = $this->pengiriman()->where(Pengiriman::FIELD_STATUS, 'terkirim')->count();
        $totalRetur = $this->retur()->count();
        $jenisBarang = $this->barangToko()->count();
        $pengirimanBulanIni = $this->pengiriman()
            ->where(Pengiriman::FIELD_STATUS, 'terkirim')
            ->whereMonth(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, date('m'))
            ->whereYear(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, date('Y'))
            ->count();

        $successRate = $totalPengiriman > 0 ? 
            round((($totalPengiriman - $totalRetur) / $totalPengiriman) * 100, 2) : 0;

        return [
            'total_pengiriman' => $totalPengiriman,
            'total_retur' => $totalRetur,
            'jenis_barang' => $jenisBarang,
            'pengiriman_bulan_ini' => $pengirimanBulanIni,
            'success_rate' => $successRate,
            'has_coordinates' => $this->hasValidCoordinates(),
            'geocoding_quality' => $this->{self::FIELD_GEOCODING_QUALITY} ?? 'unknown',
            'in_malang_region' => $this->isInMalangRegion(),
            'distance_from_malang' => $this->getDistanceFromMalangCenter()
        ];
    }

    public static function getNearbyTokos($lat, $lng, $radiusKm = 5, $limit = 10)
    {
        return self::withCoordinates()
            ->active()
            ->selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(" . self::FIELD_LATITUDE . ")) * cos(radians(" . self::FIELD_LONGITUDE . ") - radians(?)) + sin(radians(?)) * sin(radians(" . self::FIELD_LATITUDE . ")))) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    public static function getHighQualityTokosInArea($lat, $lng, $radiusKm = 10, $limit = 20)
    {
        return self::withCoordinates()
            ->active()
            ->highQualityGeocode()
            ->selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(" . self::FIELD_LATITUDE . ")) * cos(radians(" . self::FIELD_LONGITUDE . ") - radians(?)) + sin(radians(?)) * sin(radians(" . self::FIELD_LATITUDE . ")))) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy(self::FIELD_GEOCODING_SCORE, 'desc')
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }


    public function getGoogleMapsUrlAttribute()
    {
        if ($this->hasValidCoordinates()) {
            return "https://www.google.com/maps?q={$this->{self::FIELD_LATITUDE}},{$this->{self::FIELD_LONGITUDE}}";
        }
        
        $address = urlencode($this->full_address);
        return "https://www.google.com/maps/search/{$address}";
    }

    public function getLocationInfoAttribute()
    {
        return [
            'address' => $this->full_address,
            'coordinates' => $this->coordinates,
            'has_coordinates' => $this->hasValidCoordinates(),
            'geocoding_info' => [
                'provider' => $this->{self::FIELD_GEOCODING_PROVIDER},
                'accuracy' => $this->{self::FIELD_GEOCODING_ACCURACY},
                'quality' => $this->{self::FIELD_GEOCODING_QUALITY},
                'score' => $this->{self::FIELD_GEOCODING_SCORE},
                'confidence' => $this->{self::FIELD_GEOCODING_CONFIDENCE},
                'last_updated' => $this->{self::FIELD_GEOCODING_LAST_UPDATED}?->format('Y-m-d H:i:s')
            ],
            'validation' => [
                'coordinates_valid' => $this->hasValidCoordinates(),
                'in_indonesia' => $this->isInIndonesia(),
                'in_malang_region' => $this->isInMalangRegion(),
                'distance_from_malang' => $this->getDistanceFromMalangCenter()
            ],
            'status' => $this->geocoding_status,
            'google_maps_url' => $this->google_maps_url
        ];
    }

    public function scopeNeedsLocationVerification($query)
    {
        return $query->where(function($q) {
            $q->whereIn(self::FIELD_GEOCODING_QUALITY, ['fair', 'poor', 'very poor'])
              ->orWhere(function($subq) {
                  $subq->whereNotNull(self::FIELD_LATITUDE)
                       ->whereNotNull(self::FIELD_LONGITUDE)
                       ->where(function($coordq) {
                           $coordq->where(self::FIELD_LATITUDE, '<', -8.6)
                                  ->orWhere(self::FIELD_LATITUDE, '>', -7.4)
                                  ->orWhere(self::FIELD_LONGITUDE, '<', 111.8)
                                  ->orWhere(self::FIELD_LONGITUDE, '>', 113.2);
                       });
              });
        });
    }

    public static function getMarketMapReady()
    {
        return self::withCoordinates()
                   ->active()
                   ->whereIn(self::FIELD_GEOCODING_QUALITY, ['excellent', 'good', 'fair'])
                   ->orderBy(self::FIELD_GEOCODING_SCORE, 'desc')
                   ->get();
    }

    public static function getGeocodingSummary()
    {
        $total = self::count();
        $withCoords = self::withCoordinates()->count();
        $highQuality = self::whereIn(self::FIELD_GEOCODING_QUALITY, ['excellent', 'good'])->count();
        $inMalangRegion = self::inMalangRegion()->count();
        
        return [
            'total_toko' => $total,
            'with_coordinates' => $withCoords,
            'without_coordinates' => $total - $withCoords,
            'high_quality' => $highQuality,
            'in_malang_region' => $inMalangRegion,
            'coverage_percentage' => $total > 0 ? round(($withCoords / $total) * 100, 1) : 0,
            'quality_percentage' => $withCoords > 0 ? round(($highQuality / $withCoords) * 100, 1) : 0,
            'regional_accuracy' => $withCoords > 0 ? round(($inMalangRegion / $withCoords) * 100, 1) : 0
        ];
    }

    public static function batchUpdateGeocodeQuality()
    {
        $tokos = self::withCoordinates()->get();
        $updated = 0;
        
        foreach ($tokos as $toko) {
            if (!$toko->{self::FIELD_GEOCODING_QUALITY} || $toko->{self::FIELD_GEOCODING_QUALITY} === 'unknown') {
                $quality = 'fair';
                
                switch ($toko->{self::FIELD_GEOCODING_PROVIDER}) {
                    case 'interactive_map':
                        $quality = 'excellent';
                        break;
                    case 'nominatim':
                        $quality = 'good';
                        break;
                    default:
                        $quality = 'poor';
                }
                
                $toko->{self::FIELD_GEOCODING_QUALITY} = $quality;
                $toko->save();
                $updated++;
            }
        }
        
        return $updated;
    }

    public static function getClusteringData()
    {
        return self::withCoordinates()
                   ->active()
                   ->select([
                       self::FIELD_TOKO_ID,
                       self::FIELD_NAMA_TOKO,
                       self::FIELD_LATITUDE,
                       self::FIELD_LONGITUDE,
                       self::FIELD_WILAYAH_KECAMATAN,
                       self::FIELD_WILAYAH_KELURAHAN,
                       self::FIELD_GEOCODING_QUALITY,
                       self::FIELD_GEOCODING_SCORE
                   ])
                   ->get()
                   ->map(function($toko) {
                       return [
                           'id' => $toko->{self::FIELD_TOKO_ID},
                           'name' => $toko->{self::FIELD_NAMA_TOKO},
                           'lat' => (float) $toko->{self::FIELD_LATITUDE},
                           'lng' => (float) $toko->{self::FIELD_LONGITUDE},
                           'kecamatan' => $toko->{self::FIELD_WILAYAH_KECAMATAN},
                           'kelurahan' => $toko->{self::FIELD_WILAYAH_KELURAHAN},
                           'quality' => $toko->{self::FIELD_GEOCODING_QUALITY},
                           'score' => (float) $toko->{self::FIELD_GEOCODING_SCORE}
                       ];
                   });
    }
}

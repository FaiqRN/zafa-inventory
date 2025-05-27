<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\GeocodingService;

class Toko extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'toko';

    /**
     * Primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'toko_id';

    /**
     * Tipe primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Menentukan apakah model menggunakan timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'toko_id',
        'nama_toko',
        'pemilik',
        'alamat',
        'wilayah_kecamatan',
        'wilayah_kelurahan',
        'wilayah_kota_kabupaten',
        'nomer_telpon',
        // Enhanced geocoding fields
        'latitude',
        'longitude',
        'is_active',
        'catatan_lokasi',
        'alamat_lengkap_geocoding',
        // Geocoding metadata
        'geocoding_provider',
        'geocoding_accuracy',
        'geocoding_confidence',
        'geocoding_quality',
        'geocoding_score',
        'geocoding_last_updated'
    ];

    /**
     * Tipe casting untuk kolom
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'geocoding_confidence' => 'decimal:3',
        'geocoding_score' => 'decimal:2',
        'geocoding_last_updated' => 'datetime'
    ];

    /**
     * Boot method untuk auto-update geocoding timestamp
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($toko) {
            // Update geocoding timestamp jika koordinat berubah
            if ($toko->isDirty(['latitude', 'longitude', 'geocoding_provider'])) {
                $toko->geocoding_last_updated = now();
            }
        });

        static::creating(function ($toko) {
            // Set geocoding timestamp untuk data baru
            if ($toko->latitude && $toko->longitude) {
                $toko->geocoding_last_updated = now();
            }
        });
    }

    /**
     * Relasi ke tabel barang_toko.
     */
    public function barangToko()
    {
        return $this->hasMany(BarangToko::class, 'toko_id', 'toko_id');
    }

    /**
     * Relasi ke tabel pengiriman.
     */
    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'toko_id', 'toko_id');
    }

    /**
     * Relasi ke tabel retur.
     */
    public function retur()
    {
        return $this->hasMany(Retur::class, 'toko_id', 'toko_id');
    }
    
    /**
     * Relasi ke tabel barang melalui barang_toko.
     */
    public function barang()
    {
        return $this->belongsToMany(Barang::class, 'barang_toko', 'toko_id', 'barang_id')
                    ->withPivot('barang_toko_id', 'harga_barang_toko');
    }

    /**
     * Scope untuk toko aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk toko dengan koordinat
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude');
    }

    /**
     * Scope untuk toko dengan kualitas geocoding tertentu
     */
    public function scopeByGeocodeQuality($query, $quality)
    {
        return $query->where('geocoding_quality', $quality);
    }

    /**
     * Scope untuk toko dengan geocoding berkualitas baik
     */
    public function scopeHighQualityGeocode($query)
    {
        return $query->whereIn('geocoding_quality', ['excellent', 'good']);
    }

    /**
     * Scope untuk toko yang perlu geocoding ulang
     */
    public function scopeNeedsRegeocoding($query)
    {
        return $query->where(function($q) {
            $q->whereNull('latitude')
              ->orWhereNull('longitude')
              ->orWhereIn('geocoding_quality', ['poor', 'very poor', 'failed'])
              ->orWhereNull('geocoding_quality');
        });
    }

    /**
     * Scope berdasarkan wilayah
     */
    public function scopeByWilayah($query, $kota = null, $kecamatan = null, $kelurahan = null)
    {
        if ($kota) {
            $query->where('wilayah_kota_kabupaten', $kota);
        }
        
        if ($kecamatan) {
            $query->where('wilayah_kecamatan', $kecamatan);
        }
        
        if ($kelurahan) {
            $query->where('wilayah_kelurahan', $kelurahan);
        }
        
        return $query;
    }

    /**
     * Scope untuk toko dalam wilayah Malang
     */
    public function scopeInMalangRegion($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->where(function($q) {
                        // Batas koordinat wilayah Malang Raya
                        $q->whereBetween('latitude', [-8.6, -7.4])
                          ->whereBetween('longitude', [111.8, 113.2]);
                    });
    }

    /**
     * Accessor untuk full address
     */
    public function getFullAddressAttribute()
    {
        return $this->alamat . ', ' . $this->wilayah_kelurahan . ', ' . $this->wilayah_kecamatan . ', ' . $this->wilayah_kota_kabupaten;
    }

    /**
     * Accessor untuk koordinat dalam format string
     */
    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return $this->latitude . ',' . $this->longitude;
        }
        return null;
    }

    /**
     * Accessor untuk alamat lengkap geocoding dengan fallback
     */
    public function getGeocodingAddressAttribute()
    {
        return trim($this->alamat . ', ' . $this->wilayah_kelurahan . ', ' . $this->wilayah_kecamatan . ', ' . $this->wilayah_kota_kabupaten . ', Jawa Timur, Indonesia');
    }

    /**
     * Accessor untuk status geocoding
     */
    public function getGeocodingStatusAttribute()
    {
        if (!$this->latitude || !$this->longitude) {
            return [
                'status' => 'missing',
                'message' => 'Belum ada koordinat GPS',
                'badge_class' => 'secondary'
            ];
        }

        $quality = $this->geocoding_quality ?? 'unknown';
        
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

    /**
     * Check if coordinates are valid
     */
    public function hasValidCoordinates()
    {
        return $this->latitude && $this->longitude && 
               abs($this->latitude) <= 90 && abs($this->longitude) <= 180;
    }

    /**
     * Check if toko is in Malang region
     */
    public function isInMalangRegion()
    {
        if (!$this->hasValidCoordinates()) {
            return false;
        }
        
        return GeocodingService::isInMalangRegion($this->latitude, $this->longitude);
    }

    /**
     * Check if toko is in Indonesia
     */
    public function isInIndonesia()
    {
        if (!$this->hasValidCoordinates()) {
            return false;
        }
        
        return GeocodingService::isInIndonesia($this->latitude, $this->longitude);
    }

    /**
     * Method untuk menghitung jarak dari koordinat lain (dalam km)
     */
    public function getDistanceFrom($lat, $lng)
    {
        if (!$this->hasValidCoordinates()) {
            return null;
        }

        $earthRadius = 6371; // Radius bumi dalam km

        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Get distance from Malang city center
     */
    public function getDistanceFromMalangCenter()
    {
        return $this->getDistanceFrom(-7.9666, 112.6326);
    }

    /**
     * Method untuk mendapatkan statistik toko
     */
    public function getStatistics()
    {
        $totalPengiriman = $this->pengiriman()->where('status', 'terkirim')->count();
        $totalRetur = $this->retur()->count();
        $jenisBarang = $this->barangToko()->count();
        $pengirimanBulanIni = $this->pengiriman()
            ->where('status', 'terkirim')
            ->whereMonth('tanggal_pengiriman', date('m'))
            ->whereYear('tanggal_pengiriman', date('Y'))
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
            'geocoding_quality' => $this->geocoding_quality ?? 'unknown',
            'in_malang_region' => $this->isInMalangRegion(),
            'distance_from_malang' => $this->getDistanceFromMalangCenter()
        ];
    }

    /**
     * Method untuk mendapatkan toko terdekat
     */
    public static function getNearbyTokos($lat, $lng, $radiusKm = 5, $limit = 10)
    {
        return self::withCoordinates()
            ->active()
            ->selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Method untuk mendapatkan toko dengan kualitas geocoding terbaik di area tertentu
     */
    public static function getHighQualityTokosInArea($lat, $lng, $radiusKm = 10, $limit = 20)
    {
        return self::withCoordinates()
            ->active()
            ->highQualityGeocode()
            ->selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('geocoding_score', 'desc')
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Force refresh geocoding untuk toko ini
     */
    public function refreshGeocode()
    {
        try {
            $fullAddress = $this->geocoding_address;
            $geocodeResult = GeocodingService::geocodeAddress($fullAddress);
            
            if ($geocodeResult) {
                $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);
                
                $this->latitude = $geocodeResult['latitude'];
                $this->longitude = $geocodeResult['longitude'];
                $this->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
                $this->geocoding_provider = $geocodeResult['provider'];
                $this->geocoding_accuracy = $geocodeResult['accuracy'];
                $this->geocoding_confidence = $geocodeResult['confidence'] ?? null;
                $this->geocoding_quality = $qualityCheck['quality'];
                $this->geocoding_score = $qualityCheck['score'];
                $this->geocoding_last_updated = now();
                $this->save();
                
                return [
                    'success' => true,
                    'geocode_result' => $geocodeResult,
                    'quality_check' => $qualityCheck
                ];
            }
            
            return ['success' => false, 'message' => 'Geocoding failed'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Google Maps URL for this toko
     */
    public function getGoogleMapsUrlAttribute()
    {
        if ($this->hasValidCoordinates()) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        
        // Fallback to address search
        $address = urlencode($this->full_address);
        return "https://www.google.com/maps/search/{$address}";
    }

    /**
     * Get comprehensive location info
     */
    public function getLocationInfoAttribute()
    {
        return [
            'address' => $this->full_address,
            'coordinates' => $this->coordinates,
            'has_coordinates' => $this->hasValidCoordinates(),
            'geocoding_info' => [
                'provider' => $this->geocoding_provider,
                'accuracy' => $this->geocoding_accuracy,
                'quality' => $this->geocoding_quality,
                'score' => $this->geocoding_score,
                'confidence' => $this->geocoding_confidence,
                'last_updated' => $this->geocoding_last_updated?->format('Y-m-d H:i:s')
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

    /**
     * Scope untuk toko yang memerlukan verifikasi lokasi
     */
    public function scopeNeedsLocationVerification($query)
    {
        return $query->where(function($q) {
            $q->whereIn('geocoding_quality', ['fair', 'poor', 'very poor'])
              ->orWhere(function($subq) {
                  // Toko dengan koordinat di luar wilayah Malang
                  $subq->whereNotNull('latitude')
                       ->whereNotNull('longitude')
                       ->where(function($coordq) {
                           $coordq->where('latitude', '<', -8.6)
                                  ->orWhere('latitude', '>', -7.4)
                                  ->orWhere('longitude', '<', 111.8)
                                  ->orWhere('longitude', '>', 113.2);
                       });
              });
        });
    }

    /**
     * Get toko yang siap untuk Market Map
     */
    public static function getMarketMapReady()
    {
        return self::withCoordinates()
                   ->active()
                   ->whereIn('geocoding_quality', ['excellent', 'good', 'fair'])
                   ->orderBy('geocoding_score', 'desc')
                   ->get();
    }

    /**
     * Get summary statistics for geocoding
     */
    public static function getGeocodingSummary()
    {
        $total = self::count();
        $withCoords = self::withCoordinates()->count();
        $highQuality = self::whereIn('geocoding_quality', ['excellent', 'good'])->count();
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

    /**
     * Batch update geocoding quality untuk semua toko
     */
    public static function batchUpdateGeocodeQuality()
    {
        $tokos = self::withCoordinates()->get();
        $updated = 0;
        
        foreach ($tokos as $toko) {
            if (!$toko->geocoding_quality || $toko->geocoding_quality === 'unknown') {
                // Set default quality berdasarkan provider
                $quality = 'fair'; // default
                
                switch ($toko->geocoding_provider) {
                    case 'internal_database':
                        $quality = 'excellent';
                        break;
                    case 'google_maps':
                        $quality = 'good';
                        break;
                    case 'locationiq':
                    case 'opencage':
                        $quality = 'good';
                        break;
                    case 'nominatim':
                    case 'mapbox':
                    case 'here':
                        $quality = 'fair';
                        break;
                    default:
                        $quality = 'poor';
                }
                
                $toko->geocoding_quality = $quality;
                $toko->save();
                $updated++;
            }
        }
        
        return $updated;
    }

    /**
     * Get clustering data untuk Market Map
     */
    public static function getClusteringData()
    {
        return self::withCoordinates()
                   ->active()
                   ->select([
                       'toko_id',
                       'nama_toko',
                       'latitude',
                       'longitude',
                       'wilayah_kecamatan',
                       'wilayah_kelurahan',
                       'geocoding_quality',
                       'geocoding_score'
                   ])
                   ->get()
                   ->map(function($toko) {
                       return [
                           'id' => $toko->toko_id,
                           'name' => $toko->nama_toko,
                           'lat' => (float) $toko->latitude,
                           'lng' => (float) $toko->longitude,
                           'kecamatan' => $toko->wilayah_kecamatan,
                           'kelurahan' => $toko->wilayah_kelurahan,
                           'quality' => $toko->geocoding_quality,
                           'score' => (float) $toko->geocoding_score
                       ];
                   });
    }
}
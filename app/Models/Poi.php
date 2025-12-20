<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poi extends Model
{
    use HasFactory;

    public const TABLE = 'pois';
    public const FIELD_ID = 'id';
    public const FIELD_OSM_ID = 'osm_id';
    public const FIELD_NAMA = 'nama';
    public const FIELD_NAMA_NORMALIZED = 'nama_normalized';
    public const FIELD_KATEGORI = 'kategori';
    public const FIELD_LATITUDE = 'latitude';
    public const FIELD_LONGITUDE = 'longitude';
    public const FIELD_ALAMAT_JALAN = 'alamat_jalan';
    public const FIELD_NOMOR_RUMAH = 'nomor_rumah';
    public const FIELD_KODE_POS = 'kode_pos';
    public const FIELD_KELURAHAN_ID = 'kelurahan_id';
    public const FIELD_IS_ACTIVE = 'is_active';

    // Kategori POI yang berguna untuk geocoding
    public const KATEGORI_CONVENIENCE = 'convenience';
    public const KATEGORI_RESTAURANT = 'restaurant';
    public const KATEGORI_FAST_FOOD = 'fast_food';
    public const KATEGORI_CAFE = 'cafe';
    public const KATEGORI_SCHOOL = 'school';
    public const KATEGORI_HOSPITAL = 'hospital';
    public const KATEGORI_BANK = 'bank';
    public const KATEGORI_ATM = 'atm';
    public const KATEGORI_PLACE_OF_WORSHIP = 'place_of_worship';
    public const KATEGORI_FUEL = 'fuel';
    public const KATEGORI_OTHER = 'other';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_OSM_ID,
        self::FIELD_NAMA,
        self::FIELD_NAMA_NORMALIZED,
        self::FIELD_KATEGORI,
        self::FIELD_LATITUDE,
        self::FIELD_LONGITUDE,
        self::FIELD_ALAMAT_JALAN,
        self::FIELD_NOMOR_RUMAH,
        self::FIELD_KODE_POS,
        self::FIELD_KELURAHAN_ID,
        self::FIELD_IS_ACTIVE,
    ];

    protected $casts = [
        self::FIELD_LATITUDE => 'decimal:8',
        self::FIELD_LONGITUDE => 'decimal:8',
        self::FIELD_IS_ACTIVE => 'boolean',
    ];

    /**
     * Relasi ke KelurahanCoordinate
     */
    public function kelurahan()
    {
        return $this->belongsTo(KelurahanCoordinate::class, self::FIELD_KELURAHAN_ID);
    }

    /**
     * Scope untuk POI aktif
     */
    public function scopeActive($query)
    {
        return $query->where(self::FIELD_IS_ACTIVE, true);
    }

    /**
     * Scope untuk filter by kategori
     */
    public function scopeByKategori($query, string $kategori)
    {
        return $query->where(self::FIELD_KATEGORI, $kategori);
    }

    /**
     * Scope untuk search by nama
     */
    public function scopeSearchByName($query, string $keyword)
    {
        $normalized = self::normalizeNama($keyword);
        return $query->where(function($q) use ($normalized, $keyword) {
            $q->where(self::FIELD_NAMA_NORMALIZED, 'like', "%{$normalized}%")
              ->orWhere(self::FIELD_NAMA, 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope untuk mencari POI dalam radius tertentu (dalam meter)
     */
    public function scopeWithinRadius($query, float $lat, float $lng, float $radiusMeters)
    {
        // Approximate degree to meter conversion at equator
        // 1 degree latitude ≈ 111,320 meters
        // 1 degree longitude ≈ 111,320 * cos(latitude) meters
        $latDelta = $radiusMeters / 111320;
        $lngDelta = $radiusMeters / (111320 * cos(deg2rad($lat)));

        return $query->whereBetween(self::FIELD_LATITUDE, [$lat - $latDelta, $lat + $latDelta])
                     ->whereBetween(self::FIELD_LONGITUDE, [$lng - $lngDelta, $lng + $lngDelta]);
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'lat' => (float) $this->{self::FIELD_LATITUDE},
            'lng' => (float) $this->{self::FIELD_LONGITUDE},
        ];
    }

    /**
     * Calculate distance to a point using Haversine formula (in meters)
     */
    public function distanceTo(float $lat, float $lng): float
    {
        return JalanSegment::haversineDistance(
            $this->{self::FIELD_LATITUDE},
            $this->{self::FIELD_LONGITUDE},
            $lat,
            $lng
        );
    }

    /**
     * Normalize nama untuk search
     */
    public static function normalizeNama(string $nama): string
    {
        $normalized = strtolower($nama);
        $normalized = str_replace([' ', '_', '-', '.', "'", '"'], '', $normalized);
        return trim($normalized);
    }

    /**
     * Map OSM amenity/shop type ke kategori internal
     */
    public static function mapOsmTypeToKategori(?string $amenity, ?string $shop): string
    {
        // Check amenity first
        if ($amenity) {
            $amenityMap = [
                'restaurant' => self::KATEGORI_RESTAURANT,
                'fast_food' => self::KATEGORI_FAST_FOOD,
                'cafe' => self::KATEGORI_CAFE,
                'school' => self::KATEGORI_SCHOOL,
                'kindergarten' => self::KATEGORI_SCHOOL,
                'hospital' => self::KATEGORI_HOSPITAL,
                'clinic' => self::KATEGORI_HOSPITAL,
                'bank' => self::KATEGORI_BANK,
                'atm' => self::KATEGORI_ATM,
                'place_of_worship' => self::KATEGORI_PLACE_OF_WORSHIP,
                'fuel' => self::KATEGORI_FUEL,
            ];

            if (isset($amenityMap[$amenity])) {
                return $amenityMap[$amenity];
            }
        }

        // Check shop type
        if ($shop) {
            $shopMap = [
                'convenience' => self::KATEGORI_CONVENIENCE,
                'supermarket' => self::KATEGORI_CONVENIENCE,
                'kiosk' => self::KATEGORI_CONVENIENCE,
            ];

            if (isset($shopMap[$shop])) {
                return $shopMap[$shop];
            }
        }

        return self::KATEGORI_OTHER;
    }

    /**
     * Find nearest POIs to a coordinate
     */
    public static function findNearest(float $lat, float $lng, int $limit = 5, float $maxRadiusMeters = 500): \Illuminate\Support\Collection
    {
        $pois = self::active()
            ->withinRadius($lat, $lng, $maxRadiusMeters)
            ->get();

        // Calculate actual distance and sort
        return $pois->map(function($poi) use ($lat, $lng) {
            $poi->distance_meters = $poi->distanceTo($lat, $lng);
            return $poi;
        })->sortBy('distance_meters')
          ->take($limit)
          ->values();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JalanSegment extends Model
{
    use HasFactory;

    public const TABLE = 'jalan_segments';
    public const FIELD_ID = 'id';
    public const FIELD_JALAN_ID = 'jalan_id';
    public const FIELD_SEQUENCE = 'sequence';
    public const FIELD_LATITUDE = 'latitude';
    public const FIELD_LONGITUDE = 'longitude';
    public const FIELD_DISTANCE_FROM_START = 'distance_from_start';

    protected $table = self::TABLE;

    protected $fillable = [
        self::FIELD_JALAN_ID,
        self::FIELD_SEQUENCE,
        self::FIELD_LATITUDE,
        self::FIELD_LONGITUDE,
        self::FIELD_DISTANCE_FROM_START,
    ];

    protected $casts = [
        self::FIELD_LATITUDE => 'decimal:8',
        self::FIELD_LONGITUDE => 'decimal:8',
        self::FIELD_DISTANCE_FROM_START => 'decimal:2',
        self::FIELD_SEQUENCE => 'integer',
    ];

    /**
     * Relasi ke Jalan
     */
    public function jalan()
    {
        return $this->belongsTo(Jalan::class, self::FIELD_JALAN_ID);
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
     * Calculate distance to a point using Haversine formula
     */
    public function distanceTo(float $lat, float $lng): float
    {
        return self::haversineDistance(
            $this->{self::FIELD_LATITUDE},
            $this->{self::FIELD_LONGITUDE},
            $lat,
            $lng
        );
    }

    /**
     * Haversine formula untuk menghitung jarak antara 2 koordinat (dalam meter)
     */
    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Interpolate position between this segment and next segment
     * 
     * @param JalanSegment $nextSegment
     * @param float $targetDistance Distance from start in meters
     * @return array ['lat' => float, 'lng' => float]
     */
    public function interpolateTo(JalanSegment $nextSegment, float $targetDistance): array
    {
        $startDistance = $this->{self::FIELD_DISTANCE_FROM_START};
        $endDistance = $nextSegment->{self::FIELD_DISTANCE_FROM_START};
        
        if ($endDistance <= $startDistance) {
            return $this->coordinates;
        }

        // Calculate interpolation ratio
        $ratio = ($targetDistance - $startDistance) / ($endDistance - $startDistance);
        $ratio = max(0, min(1, $ratio)); // Clamp between 0 and 1

        // Linear interpolation
        $lat = $this->{self::FIELD_LATITUDE} + 
               ($nextSegment->{self::FIELD_LATITUDE} - $this->{self::FIELD_LATITUDE}) * $ratio;
        $lng = $this->{self::FIELD_LONGITUDE} + 
               ($nextSegment->{self::FIELD_LONGITUDE} - $this->{self::FIELD_LONGITUDE}) * $ratio;

        return [
            'lat' => round($lat, 8),
            'lng' => round($lng, 8),
        ];
    }
}

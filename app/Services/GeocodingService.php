<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class GeocodingService
{

    public static function isInIndonesia($latitude, $longitude)
    {
        $minLat = -11.5;
        $maxLat = 6.5;
        $minLng = 94.5;
        $maxLng = 141.5;
        
        return ($latitude >= $minLat && $latitude <= $maxLat && 
                $longitude >= $minLng && $longitude <= $maxLng);
    }

    public static function isInMalangRegion($latitude, $longitude)
    {
        $malangBounds = [
            'min_lat' => -8.6,
            'max_lat' => -7.4,
            'min_lng' => 111.8,
            'max_lng' => 113.2
        ];
        
        return ($latitude >= $malangBounds['min_lat'] && 
                $latitude <= $malangBounds['max_lat'] && 
                $longitude >= $malangBounds['min_lng'] && 
                $longitude <= $malangBounds['max_lng']);
    }

    public static function validateCoordinateRange($latitude, $longitude)
    {
        $errors = [];
        $isValid = true;

        if (!is_numeric($latitude)) {
            $errors[] = 'Latitude harus berupa angka';
            $isValid = false;
        } elseif ($latitude < -90 || $latitude > 90) {
            $errors[] = 'Latitude harus berada dalam range -90 sampai 90';
            $isValid = false;
        }

        if (!is_numeric($longitude)) {
            $errors[] = 'Longitude harus berupa angka';
            $isValid = false;
        } elseif ($longitude < -180 || $longitude > 180) {
            $errors[] = 'Longitude harus berada dalam range -180 sampai 180';
            $isValid = false;
        }

        if ($latitude == 0 && $longitude == 0) {
            $errors[] = 'Koordinat (0, 0) tidak valid';
            $isValid = false;
        }

        $inIndonesia = false;
        $inMalangRegion = false;

        if ($isValid) {
            $inIndonesia = self::isInIndonesia($latitude, $longitude);
            $inMalangRegion = self::isInMalangRegion($latitude, $longitude);

            if (!$inIndonesia) {
                $errors[] = 'Koordinat berada di luar wilayah Indonesia';
            }

            if (!$inMalangRegion) {
                $errors[] = 'Koordinat berada di luar wilayah Malang Raya';
            }
        }

        return [
            'is_valid' => $isValid,
            'in_indonesia' => $inIndonesia,
            'in_malang_region' => $inMalangRegion,
            'errors' => $errors,
            'warnings' => !$inMalangRegion && $inIndonesia ? ['Koordinat di luar wilayah Malang Raya'] : [],
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
    }

    public static function reverseGeocode($latitude, $longitude)
    {
        try {
            $nominatimService = app(NominatimService::class);
            $result = $nominatimService->reverse($latitude, $longitude);
            
            if ($result) {
                return [
                    'formatted_address' => $result['display_name'] ?? '',
                    'latitude' => $result['lat'] ?? $latitude,
                    'longitude' => $result['lon'] ?? $longitude,
                    'address' => $result['address'] ?? []
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; 
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 2); 
    }

    public static function validateCoordinateTolerance($originalLat, $originalLon, $newLat, $newLon, $maxToleranceMeters = 50)
    {
        $distance = self::calculateDistance($originalLat, $originalLon, $newLat, $newLon);
        
        return [
            'within_tolerance' => $distance <= $maxToleranceMeters,
            'distance_meters' => $distance,
            'max_tolerance_meters' => $maxToleranceMeters,
            'exceeded_by_meters' => max(0, $distance - $maxToleranceMeters),
            'tolerance_percentage' => $maxToleranceMeters > 0 ? round(($distance / $maxToleranceMeters) * 100, 1) : 0
        ];
    }

    public static function calculateQualityScore($result)
    {
        if (!$result || !isset($result['latitude']) || !isset($result['longitude'])) {
            return 0;
        }

        $score = 50; 

        if (isset($result['provider'])) {
            switch ($result['provider']) {
                case 'nominatim':
                    $score += 25;
                    break;
                case 'overpass':
                    $score += 20;
                    break;
                case 'interactive_map':
                    return 100; 
            }
        }
        
        if (isset($result['accuracy'])) {
            switch ($result['accuracy']) {
                case 'very high':
                    $score += 20;
                    break;
                case 'high':
                    $score += 15;
                    break;
                case 'medium':
                    $score += 10;
                    break;
                case 'low':
                    $score += 5;
                    break;
            }
        }
        
        if (self::isInMalangRegion($result['latitude'], $result['longitude'])) {
            $score += 10;
        }
        
        if (isset($result['confidence'])) {
            $score += min(10, $result['confidence'] * 10);
        }
        
        // Validation passed bonus (5 points)
        if (isset($result['validation_passed']) && $result['validation_passed']) {
            $score += 5;
        }
        
        return min(100, max(0, $score));
    }

    public static function validateGeocodeQuality($result)
    {
        $score = self::calculateQualityScore($result);
        
        if ($score >= 90) {
            $level = 'excellent';
            $badge = 'Sangat Akurat';
            $color = 'success';
        } elseif ($score >= 80) {
            $level = 'good';
            $badge = 'Akurat';
            $color = 'primary';
        } elseif ($score >= 70) {
            $level = 'fair';
            $badge = 'Cukup Akurat';
            $color = 'warning';
        } else {
            $level = 'poor';
            $badge = 'Kurang Akurat';
            $color = 'danger';
        }
        
        return [
            'score' => $score,
            'level' => $level,
            'badge' => $badge,
            'color' => $color,
            'needs_improvement' => $score < 70,
            'in_malang_region' => self::isInMalangRegion($result['latitude'], $result['longitude']),
            'provider' => $result['provider'] ?? 'unknown',
            'accuracy' => $result['accuracy'] ?? 'unknown',
            'confidence' => $result['confidence'] ?? null,
            'quality' => $level,
            'recommendations' => []
        ];
    }

    public static function getGeocodingStats()
    {
        return [
            'system' => 'Nominatim API',
            'coverage' => 'Global (OpenStreetMap data)',
            'features' => [
                'address_search' => 'Active',
                'reverse_geocoding' => 'Active',
                'boundary_polygons' => 'Active (via Overpass API)'
            ],
            'accuracy' => 'High (OpenStreetMap quality)',
            'rate_limit' => '1 request per second',
            'cost' => 'Free'
        ];
    }
}

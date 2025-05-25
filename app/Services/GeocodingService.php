<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    public static function geocodeAddress($address)
    {
        try {
            $cleanAddress = self::cleanAddress($address);
            Log::info('Geocoding: ' . $cleanAddress);
            
            // Coba berbagai provider secara berurutan
            $providers = [
                'geocodeWithNominatim',
                'geocodeWithLocationIQ', 
                'geocodeWithOpenCage',
                'geocodeWithMapBox',
                'geocodeWithHere',
                'geocodeWithPositionStack'
            ];
            
            foreach ($providers as $provider) {
                $result = self::$provider($cleanAddress);
                if ($result) {
                    Log::info('Geocoding SUCCESS with: ' . $provider);
                    return $result;
                }
            }
            
            // Fallback ke koordinat estimasi
            return self::getFallbackCoordinates($cleanAddress);
            
        } catch (\Exception $e) {
            Log::error('Geocoding error: ' . $e->getMessage());
            return self::getFallbackCoordinates($address);
        }
    }

    /**
     * OpenStreetMap Nominatim - GRATIS UNLIMITED
     */
    private static function geocodeWithNominatim($address)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Laravel-Toko-App/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'id'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0])) {
                    $result = $data[0];
                    $lat = (float) $result['lat'];
                    $lon = (float) $result['lon'];
                    
                    // Validasi koordinat Indonesia
                    if (self::isInIndonesia($lat, $lon)) {
                        return [
                            'latitude' => $lat,
                            'longitude' => $lon,
                            'formatted_address' => $result['display_name'],
                            'accuracy' => 'medium',
                            'provider' => 'nominatim'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Nominatim failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * LocationIQ - GRATIS 5,000/day
     */
    private static function geocodeWithLocationIQ($address)
    {
        $apiKey = env('LOCATIONIQ_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(10)->get('https://eu1.locationiq.com/v1/search.php', [
                'key' => $apiKey,
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0])) {
                    $result = $data[0];
                    return [
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'formatted_address' => $result['display_name'],
                        'accuracy' => 'high',
                        'provider' => 'locationiq'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('LocationIQ failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * OpenCage - GRATIS 2,500/day
     */
    private static function geocodeWithOpenCage($address)
    {
        $apiKey = env('OPENCAGE_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(10)->get('https://api.opencagedata.com/geocode/v1/json', [
                'key' => $apiKey,
                'q' => $address,
                'limit' => 1,
                'countrycode' => 'id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results']) && isset($data['results'][0])) {
                    $result = $data['results'][0];
                    return [
                        'latitude' => (float) $result['geometry']['lat'],
                        'longitude' => (float) $result['geometry']['lng'],
                        'formatted_address' => $result['formatted'],
                        'accuracy' => 'high',
                        'provider' => 'opencage'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('OpenCage failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * MapBox - GRATIS 100,000/month
     */
    private static function geocodeWithMapBox($address)
    {
        $apiKey = env('MAPBOX_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(10)->get("https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode($address) . ".json", [
                'access_token' => $apiKey,
                'country' => 'id',
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['features']) && isset($data['features'][0])) {
                    $result = $data['features'][0];
                    return [
                        'latitude' => (float) $result['geometry']['coordinates'][1],
                        'longitude' => (float) $result['geometry']['coordinates'][0],
                        'formatted_address' => $result['place_name'],
                        'accuracy' => 'high',
                        'provider' => 'mapbox'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('MapBox failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Here Geocoding - GRATIS 250,000/month
     */
    private static function geocodeWithHere($address)
    {
        $apiKey = env('HERE_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(10)->get('https://geocode.search.hereapi.com/v1/geocode', [
                'apikey' => $apiKey,
                'q' => $address,
                'in' => 'countryCode:IDN',
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['items']) && isset($data['items'][0])) {
                    $result = $data['items'][0];
                    return [
                        'latitude' => (float) $result['position']['lat'],
                        'longitude' => (float) $result['position']['lng'],
                        'formatted_address' => $result['title'],
                        'accuracy' => 'high',
                        'provider' => 'here'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Here failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * PositionStack - GRATIS 25,000/month
     */
    private static function geocodeWithPositionStack($address)
    {
        $apiKey = env('POSITIONSTACK_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(10)->get('http://api.positionstack.com/v1/forward', [
                'access_key' => $apiKey,
                'query' => $address,
                'country' => 'ID',
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['data']) && isset($data['data'][0])) {
                    $result = $data['data'][0];
                    return [
                        'latitude' => (float) $result['latitude'],
                        'longitude' => (float) $result['longitude'],
                        'formatted_address' => $result['label'],
                        'accuracy' => 'high',
                        'provider' => 'positionstack'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('PositionStack failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Fallback coordinates berdasarkan nama kota/wilayah
     */
    private static function getFallbackCoordinates($address)
    {
        $addressLower = strtolower($address);
        
        $coordinates = [
            // Jawa Timur
            'malang' => [-7.9666, 112.6326],
            'surabaya' => [-7.2575, 112.7521],
            'kediri' => [-7.8167, 112.0167],
            'madiun' => [-7.6298, 111.5239],
            'jember' => [-8.1844, 113.7064],
            
            // Jawa Tengah  
            'semarang' => [-6.9667, 110.4167],
            'yogyakarta' => [-7.7956, 110.3695],
            'solo' => [-7.5667, 110.8333],
            
            // Jawa Barat
            'bandung' => [-6.9175, 107.6191],
            'bogor' => [-6.5944, 106.7892],
            'bekasi' => [-6.2349, 107.1543],
            
            // Jakarta & sekitarnya
            'jakarta' => [-6.2088, 106.8456],
            'tangerang' => [-6.1783, 106.6319],
            'depok' => [-6.4025, 106.7942],
        ];

        foreach ($coordinates as $city => $coords) {
            if (strpos($addressLower, $city) !== false) {
                return [
                    'latitude' => $coords[0],
                    'longitude' => $coords[1],
                    'formatted_address' => $address . ' (Estimasi)',
                    'accuracy' => 'low',
                    'provider' => 'fallback'
                ];
            }
        }

        // Default ke Malang jika tidak ditemukan
        return [
            'latitude' => -7.9666,
            'longitude' => 112.6326,
            'formatted_address' => $address . ' (Default Malang)',
            'accuracy' => 'low',
            'provider' => 'default'
        ];
    }

    /**
     * Clean dan format alamat
     */
    private static function cleanAddress($address)
    {
        $address = trim($address);
        $address = preg_replace('/\s+/', ' ', $address);
        
        if (stripos($address, 'indonesia') === false) {
            $address .= ', Indonesia';
        }
        
        return $address;
    }

    /**
     * Validasi koordinat berada di Indonesia
     */
    public static function isInIndonesia($latitude, $longitude)
    {
        // Batas koordinat Indonesia
        $minLat = -11.0;
        $maxLat = 6.0;
        $minLng = 95.0;
        $maxLng = 141.0;
        
        return ($latitude >= $minLat && $latitude <= $maxLat && 
                $longitude >= $minLng && $longitude <= $maxLng);
    }

    /**
     * Validasi koordinat berada di wilayah Malang
     */
    public static function isInMalangRegion($latitude, $longitude)
    {
        // Batas koordinat wilayah Malang Raya (perkiraan)
        $malangBounds = [
            'min_lat' => -8.5,
            'max_lat' => -7.5,
            'min_lng' => 112.0,
            'max_lng' => 113.0
        ];
        
        return ($latitude >= $malangBounds['min_lat'] && 
                $latitude <= $malangBounds['max_lat'] && 
                $longitude >= $malangBounds['min_lng'] && 
                $longitude <= $malangBounds['max_lng']);
    }

    /**
     * Reverse geocoding - convert koordinat ke alamat
     */
    public static function reverseGeocode($latitude, $longitude)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Laravel-Toko-App/1.0'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'accept-language' => 'id,en'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['display_name'])) {
                    return [
                        'formatted_address' => $data['display_name'],
                        'components' => $data['address'] ?? [],
                        'provider' => 'nominatim'
                    ];
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::warning('Reverse geocoding failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Test geocoding dengan alamat sample
     */
    public static function testGeocode()
    {
        $testAddresses = [
            'Malang, Jawa Timur, Indonesia',
            'Surabaya, Jawa Timur, Indonesia',
            'Jakarta, Indonesia'
        ];

        $results = [];
        foreach ($testAddresses as $address) {
            $result = self::geocodeAddress($address);
            $results[] = [
                'address' => $address,
                'result' => $result,
                'status' => $result ? 'success' : 'failed'
            ];
        }

        return $results;
    }

    /**
     * Test semua provider
     */
    public static function testMultipleProviders()
    {
        $testAddress = 'Malang, Jawa Timur, Indonesia';
        
        $providers = [
            'Nominatim' => 'geocodeWithNominatim',
            'LocationIQ' => 'geocodeWithLocationIQ',
            'OpenCage' => 'geocodeWithOpenCage',
            'MapBox' => 'geocodeWithMapBox',
            'Here' => 'geocodeWithHere',
            'PositionStack' => 'geocodeWithPositionStack'
        ];

        $results = [];
        foreach ($providers as $name => $method) {
            $result = self::$method($testAddress);
            $results[$name] = $result ? 'SUCCESS' : 'FAILED';
        }

        return $results;
    }

    /**
     * Debug informasi system
     */
    public static function debugInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'curl_enabled' => extension_loaded('curl'),
            'openssl_enabled' => extension_loaded('openssl'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'user_agent' => env('GEOCODING_USER_AGENT', 'Laravel-Toko-App/1.0'),
            'timeout' => env('GEOCODING_TIMEOUT', 15),
            'available_providers' => [
                'nominatim' => 'Always available',
                'locationiq' => env('LOCATIONIQ_API_KEY') ? 'Configured' : 'Not configured',
                'opencage' => env('OPENCAGE_API_KEY') ? 'Configured' : 'Not configured',
                'mapbox' => env('MAPBOX_API_KEY') ? 'Configured' : 'Not configured',
                'here' => env('HERE_API_KEY') ? 'Configured' : 'Not configured',
                'positionstack' => env('POSITIONSTACK_API_KEY') ? 'Configured' : 'Not configured',
            ]
        ];
    }
}
<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeocodingService
{
    /**
     * Geocode alamat dengan strategi multi-provider
     */
    public static function geocodeAddress($address)
    {
        try {
            $cleanAddress = self::cleanAddress($address);
            Log::info('Enhanced Geocoding: ' . $cleanAddress);
            
            // 1. Cek cache terlebih dahulu
            $cacheKey = 'geocode_' . md5(strtolower($cleanAddress));
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult && self::validateCoordinates($cachedResult)) {
                Log::info('Using cached result for: ' . $cleanAddress);
                return $cachedResult;
            }
            
            // 2. Coba berbagai provider API secara berurutan
            $providers = [
                'geocodeWithGoogleMapsAPI',     // Google Maps (paling akurat untuk API)
                'geocodeWithLocationIQ',        // LocationIQ (gratis 5,000/day)
                'geocodeWithOpenCage',          // OpenCage (gratis 2,500/day)
                'geocodeWithMapBox',            // MapBox (gratis 100,000/month)
                'geocodeWithHere',              // Here (gratis 250,000/month)
                'geocodeWithNominatim',         // OpenStreetMap (gratis dengan rate limit)
                'geocodeWithPositionStack'      // PositionStack (gratis 25,000/month)
            ];
            
            foreach ($providers as $provider) {
                $result = self::$provider($cleanAddress);
                if ($result && self::validateCoordinates($result)) {
                    Log::info('Geocoding SUCCESS with: ' . $provider);
                    
                    // Cache hasil API untuk penggunaan berikutnya
                    self::cacheCoordinatesToDatabase($cleanAddress, $result);
                    
                    return $result;
                }
                
                // Delay antar provider untuk menghindari rate limiting
                usleep(200000); // 0.2 detik delay
            }
            
            // 3. Return null jika semua provider gagal - untuk akurasi maksimal
            Log::warning('All geocoding providers failed for: ' . $cleanAddress);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Enhanced Geocoding error: ' . $e->getMessage());
            return null; // Return null untuk akurasi maksimal
        }
    }

    /**
     * Cache koordinat hasil API ke database untuk penggunaan berikutnya
     */
    private static function cacheCoordinatesToDatabase($address, $result)
    {
        try {
            $cacheKey = 'geocode_' . md5(strtolower($address));
            Cache::put($cacheKey, $result, now()->addDays(30)); // Cache 30 hari
            Log::info('Cached geocoding result for: ' . $address);
        } catch (\Exception $e) {
            Log::warning('Failed to cache geocoding result: ' . $e->getMessage());
        }
    }

    /**
     * Google Maps Geocoding API - PALING AKURAT
     */
    private static function geocodeWithGoogleMapsAPI($address)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey,
                'language' => 'id',
                'region' => 'id',
                'components' => 'country:ID|administrative_area:Jawa Timur|locality:Malang'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    foreach ($data['results'] as $result) {
                        $location = $result['geometry']['location'];
                        $lat = (float) $location['lat'];
                        $lng = (float) $location['lng'];
                        
                        // Validasi ketat untuk wilayah Malang
                        if (self::isInMalangRegion($lat, $lng)) {
                            $formattedAddress = strtolower($result['formatted_address']);
                            if (strpos($formattedAddress, 'jawa timur') !== false || 
                                strpos($formattedAddress, 'malang') !== false) {
                                
                                return [
                                    'latitude' => $lat,
                                    'longitude' => $lng,
                                    'formatted_address' => $result['formatted_address'],
                                    'accuracy' => 'very high',
                                    'provider' => 'google_maps',
                                    'place_id' => $result['place_id'] ?? null,
                                    'location_type' => $result['geometry']['location_type'] ?? 'APPROXIMATE',
                                    'confidence' => 0.9,
                                    'validation_passed' => true
                                ];
                            }
                        }
                    }
                    
                    Log::warning('Google Maps: No valid results in Malang region for: ' . $address);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Google Maps API failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * LocationIQ - GRATIS 5,000/day dengan akurasi tinggi
     */
    private static function geocodeWithLocationIQ($address)
    {
        $apiKey = env('LOCATIONIQ_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(12)->get('https://eu1.locationiq.com/v1/search.php', [
                'key' => $apiKey,
                'q' => $address,
                'format' => 'json',
                'limit' => 3, // Ambil 3 hasil untuk filtering
                'countrycodes' => 'id',
                'viewbox' => '111.8,-8.6,113.2,-7.4', // Batas wilayah Malang yang lebih ketat
                'bounded' => 1,
                'addressdetails' => 1,
                'normalizeaddress' => 1,
                'accept-language' => 'id,en'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data)) {
                    // Filter hasil berdasarkan region Malang/Jawa Timur
                    foreach ($data as $result) {
                        $lat = (float) $result['lat'];
                        $lon = (float) $result['lon'];
                        
                        // Validasi ketat untuk wilayah Malang Raya
                        if (self::isInMalangRegion($lat, $lon)) {
                            // Double check dengan mencari kata kunci Jawa Timur atau Malang di alamat
                            $displayName = strtolower($result['display_name']);
                            if (strpos($displayName, 'jawa timur') !== false || 
                                strpos($displayName, 'east java') !== false ||
                                strpos($displayName, 'malang') !== false) {
                                
                                return [
                                    'latitude' => $lat,
                                    'longitude' => $lon,
                                    'formatted_address' => $result['display_name'],
                                    'accuracy' => 'high',
                                    'provider' => 'locationiq',
                                    'confidence' => (float) ($result['importance'] ?? 0.7),
                                    'validation_passed' => true
                                ];
                            }
                        }
                    }
                    
                    // Jika tidak ada hasil yang valid, log warning
                    Log::warning('LocationIQ: No valid results in Malang region for: ' . $address);
                }
            }
        } catch (\Exception $e) {
            Log::warning('LocationIQ failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * OpenCage - GRATIS 2,500/day dengan data detail
     */
    private static function geocodeWithOpenCage($address)
    {
        $apiKey = env('OPENCAGE_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(12)->get('https://api.opencagedata.com/geocode/v1/json', [
                'key' => $apiKey,
                'q' => $address,
                'limit' => 1,
                'countrycode' => 'id',
                'language' => 'id',
                'no_annotations' => 0,
                'bounds' => '105.0,-11.0,141.0,6.0'
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
                        'provider' => 'opencage',
                        'confidence' => (float) ($result['confidence'] ?? 0.7) / 10,
                        'components' => $result['components'] ?? []
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('OpenCage failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * MapBox - GRATIS 100,000/month dengan data POI
     */
    private static function geocodeWithMapBox($address)
    {
        $apiKey = env('MAPBOX_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(12)->get("https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode($address) . ".json", [
                'access_token' => $apiKey,
                'country' => 'id',
                'limit' => 1,
                'language' => 'id',
                'bbox' => '105.0,-11.0,141.0,6.0',
                'fuzzyMatch' => 'true'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['features']) && isset($data['features'][0])) {
                    $result = $data['features'][0];
                    return [
                        'latitude' => (float) $result['geometry']['coordinates'][1],
                        'longitude' => (float) $result['geometry']['coordinates'][0],
                        'formatted_address' => $result['place_name'],
                        'accuracy' => self::getAccuracyFromRelevance($result['relevance'] ?? 0.5),
                        'provider' => 'mapbox',
                        'confidence' => (float) ($result['relevance'] ?? 0.5),
                        'place_type' => $result['place_type'] ?? []
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('MapBox failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Here Geocoding - GRATIS 250,000/month dengan routing data
     */
    private static function geocodeWithHere($address)
    {
        $apiKey = env('HERE_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(12)->get('https://geocode.search.hereapi.com/v1/geocode', [
                'apikey' => $apiKey,
                'q' => $address,
                'in' => 'countryCode:IDN',
                'limit' => 1,
                'lang' => 'id-ID',
                'at' => '-7.9666,112.6326' // Center point Malang untuk prioritas
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
                        'provider' => 'here',
                        'confidence' => (float) (($result['scoring']['queryScore'] ?? 0.7) / 100),
                        'scoring' => $result['scoring'] ?? null
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Here failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * OpenStreetMap Nominatim - GRATIS dengan rate limiting
     */
    private static function geocodeWithNominatim($address)
    {
        try {
            // Delay untuk menghindari rate limiting
            usleep(1200000); // 1.2 detik delay
            
            $response = Http::timeout(18)
                ->withHeaders([
                    'User-Agent' => 'Enhanced-Laravel-Toko-App/2.0 (geocoding@malang-toko.com)',
                    'Accept-Language' => 'id,en'
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'id',
                    'addressdetails' => 1,
                    'viewbox' => '105.0,-11.0,141.0,6.0',
                    'bounded' => 1,
                    'extratags' => 1
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0])) {
                    $result = $data[0];
                    $lat = (float) $result['lat'];
                    $lon = (float) $result['lon'];
                    
                    if (self::isInIndonesia($lat, $lon)) {
                        return [
                            'latitude' => $lat,
                            'longitude' => $lon,
                            'formatted_address' => $result['display_name'],
                            'accuracy' => self::getAccuracyFromImportance($result['importance'] ?? 0.5),
                            'provider' => 'nominatim',
                            'confidence' => (float) ($result['importance'] ?? 0.5),
                            'osm_type' => $result['osm_type'] ?? null,
                            'osm_id' => $result['osm_id'] ?? null,
                            'class' => $result['class'] ?? null,
                            'type' => $result['type'] ?? null
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
     * PositionStack - GRATIS 25,000/month
     */
    private static function geocodeWithPositionStack($address)
    {
        $apiKey = env('POSITIONSTACK_API_KEY');
        if (!$apiKey) return null;

        try {
            $response = Http::timeout(12)->get('http://api.positionstack.com/v1/forward', [
                'access_key' => $apiKey,
                'query' => $address,
                'country' => 'ID',
                'limit' => 1,
                'region' => 'East Java'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['data']) && isset($data['data'][0])) {
                    $result = $data['data'][0];
                    return [
                        'latitude' => (float) $result['latitude'],
                        'longitude' => (float) $result['longitude'],
                        'formatted_address' => $result['label'],
                        'accuracy' => 'medium',
                        'provider' => 'positionstack',
                        'confidence' => (float) ($result['confidence'] ?? 0.6)
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('PositionStack failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Return null jika semua provider gagal - untuk akurasi maksimal
     */
    private static function getFallbackCoordinates($address)
    {
        Log::warning('All geocoding providers failed for address: ' . $address);
        Log::warning('Returning null to maintain coordinate accuracy');
        
        // Return null untuk mempertahankan akurasi
        // Aplikasi harus handle ketika geocoding gagal
        return null;
    }

    /**
     * Clean dan format alamat dengan optimasi untuk Indonesia
     */
    private static function cleanAddress($address)
    {
        // Hapus karakter khusus yang tidak perlu
        $address = preg_replace('/[^\p{L}\p{N}\s\-.,\/]/u', ' ', $address);
        
        // Hapus multiple spaces
        $address = preg_replace('/\s+/', ' ', trim($address));
        
        // Standardisasi singkatan umum
        $replacements = [
            ' jl\.' => ' jalan',
            ' jl ' => ' jalan ',
            ' gg\.' => ' gang',
            ' gg ' => ' gang ',
            ' rt\.' => ' rt',
            ' rw\.' => ' rw',
            ' no\.' => ' nomor',
            ' kec\.' => ' kecamatan',
            ' kab\.' => ' kabupaten',
            ' kel\.' => ' kelurahan',
            ' ds\.' => ' desa'
        ];
        
        $address = str_ireplace(array_keys($replacements), array_values($replacements), $address);
        
        // Tambahkan konteks geografis jika belum ada
        if (stripos($address, 'indonesia') === false) {
            $address .= ', Indonesia';
        }
        
        if (stripos($address, 'malang') !== false && stripos($address, 'jawa timur') === false) {
            $address = str_ireplace(', Indonesia', ', Jawa Timur, Indonesia', $address);
        }
        
        return $address;
    }

    /**
     * Validasi koordinat berada di Indonesia dengan batas yang presisi
     */
    public static function isInIndonesia($latitude, $longitude)
    {
        $minLat = -11.5;
        $maxLat = 6.5;
        $minLng = 94.5;
        $maxLng = 141.5;
        
        return ($latitude >= $minLat && $latitude <= $maxLat && 
                $longitude >= $minLng && $longitude <= $maxLng);
    }

    /**
     * Validasi koordinat berada di wilayah Malang Raya
     */
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

    /**
     * Validasi hasil geocoding dengan kriteria yang ketat
     */
    private static function validateCoordinates($result)
    {
        if (!$result || !isset($result['latitude']) || !isset($result['longitude'])) {
            Log::warning('Validation failed: Missing coordinates');
            return false;
        }
        
        // Validasi koordinat tidak null/zero
        if ($result['latitude'] == 0 && $result['longitude'] == 0) {
            Log::warning('Validation failed: Zero coordinates');
            return false;
        }
        
        // Validasi berada di Indonesia
        if (!self::isInIndonesia($result['latitude'], $result['longitude'])) {
            Log::warning('Validation failed: Outside Indonesia bounds');
            return false;
        }
        
        // VALIDASI KETAT: Harus berada di wilayah Malang Raya
        if (!self::isInMalangRegion($result['latitude'], $result['longitude'])) {
            Log::warning('Validation failed: Outside Malang region. Coordinates: ' . 
                        $result['latitude'] . ', ' . $result['longitude']);
            return false;
        }
        
        // Validasi confidence minimal untuk API results
        if (isset($result['confidence']) && $result['confidence'] < 0.1) {
            Log::warning('Validation failed: Low confidence score');
            return false;
        }
        
        // Validasi alamat hasil harus mengandung kata kunci Jawa Timur atau Malang
        if (isset($result['formatted_address'])) {
            $addressLower = strtolower($result['formatted_address']);
            if (strpos($addressLower, 'jawa timur') === false && 
                strpos($addressLower, 'east java') === false &&
                strpos($addressLower, 'malang') === false) {
                Log::warning('Validation failed: Address does not contain Jawa Timur or Malang keywords');
                return false;
            }
        }
        
        Log::info('Validation passed for coordinates: ' . $result['latitude'] . ', ' . $result['longitude']);
        return true;
    }

    /**
     * Konversi importance score ke accuracy level
     */
    private static function getAccuracyFromImportance($importance)
    {
        if ($importance >= 0.8) return 'very high';
        if ($importance >= 0.6) return 'high';
        if ($importance >= 0.4) return 'medium';
        return 'low';
    }

    /**
     * Konversi relevance score ke accuracy level
     */
    private static function getAccuracyFromRelevance($relevance)
    {
        if ($relevance >= 0.9) return 'very high';
        if ($relevance >= 0.7) return 'high';
        if ($relevance >= 0.5) return 'medium';
        return 'low';
    }

    /**
     * Reverse geocoding - convert koordinat ke alamat
     */
    public static function reverseGeocode($latitude, $longitude)
    {
        try {
            // Coba Google Maps API dulu jika tersedia
            $apiKey = env('GOOGLE_MAPS_API_KEY');
            if ($apiKey) {
                $response = Http::timeout(12)
                    ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'latlng' => $latitude . ',' . $longitude,
                        'key' => $apiKey,
                        'language' => 'id',
                        'result_type' => 'street_address|route|neighborhood'
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['results']) && isset($data['results'][0])) {
                        $result = $data['results'][0];
                        return [
                            'formatted_address' => $result['formatted_address'],
                            'components' => $result['address_components'] ?? [],
                            'provider' => 'google_maps'
                        ];
                    }
                }
            }

            // Fallback ke Nominatim
            usleep(1000000); // 1 detik delay
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Enhanced-Laravel-Toko-App/2.0'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'accept-language' => 'id,en',
                    'zoom' => 18
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
     * Batch geocoding dengan optimasi performa
     */
    public static function batchGeocode($addresses, $delay = 500000)
    {
        $results = [];
        $totalAddresses = count($addresses);
        
        Log::info("Starting batch geocoding for {$totalAddresses} addresses");
        
        foreach ($addresses as $key => $address) {
            Log::info("Processing address {$key}/{$totalAddresses}: {$address}");
            
            $result = self::geocodeAddress($address);
            $results[$key] = $result;
            
            // Log hasil
            if ($result) {
                Log::info("SUCCESS: {$result['provider']} - {$result['accuracy']} - ({$result['latitude']}, {$result['longitude']})");
            } else {
                Log::warning("FAILED: Could not geocode address: {$address}");
            }
            
            // Delay untuk menghindari rate limiting
            if ($key < $totalAddresses - 1) {
                usleep($delay);
            }
        }
        
        Log::info("Batch geocoding completed");
        return $results;
    }

    /**
     * Test alamat spesifik untuk debugging
     */
    public static function debugGeocode($address)
    {
        Log::info('=== DEBUG GEOCODING START ===');
        Log::info('Input Address: ' . $address);
        
        $cleanAddress = self::cleanAddress($address);
        Log::info('Cleaned Address: ' . $cleanAddress);
        
        $providers = [
            'geocodeWithGoogleMapsAPI',
            'geocodeWithLocationIQ',
            'geocodeWithOpenCage',
            'geocodeWithMapBox',
            'geocodeWithHere',
            'geocodeWithNominatim',
            'geocodeWithPositionStack'
        ];
        
        $results = [];
        
        foreach ($providers as $provider) {
            Log::info('Trying provider: ' . $provider);
            
            $result = self::$provider($cleanAddress);
            
            if ($result) {
                $isValid = self::validateCoordinates($result);
                $inMalangRegion = self::isInMalangRegion($result['latitude'], $result['longitude']);
                
                $results[$provider] = [
                    'result' => $result,
                    'is_valid' => $isValid,
                    'in_malang_region' => $inMalangRegion,
                    'coordinates' => [$result['latitude'], $result['longitude']]
                ];
                
                Log::info($provider . ' Result: ' . json_encode($result));
                Log::info($provider . ' Valid: ' . ($isValid ? 'YES' : 'NO'));
                Log::info($provider . ' In Malang: ' . ($inMalangRegion ? 'YES' : 'NO'));
            } else {
                $results[$provider] = null;
                Log::info($provider . ' Result: NULL');
            }
            
            usleep(500000); // 0.5 detik delay
        }
        
        Log::info('=== DEBUG GEOCODING END ===');
        
        return [
            'input_address' => $address,
            'cleaned_address' => $cleanAddress,
            'provider_results' => $results,
            'malang_region_bounds' => [
                'min_lat' => -8.6,
                'max_lat' => -7.4,
                'min_lng' => 111.8,
                'max_lng' => 113.2
            ]
        ];
    }

    /**
     * Get geocoding statistics and configuration
     */
    public static function getGeocodingStats()
    {
        $stats = [
            'cache_system' => [
                'status' => 'Active',
                'duration' => '30 days',
                'coverage' => 'All geocoded addresses'
            ],
            'api_providers' => [
                'google_maps' => env('GOOGLE_MAPS_API_KEY') ? 'Configured' : 'Not configured',
                'locationiq' => env('LOCATIONIQ_API_KEY') ? 'Configured' : 'Not configured',
                'opencage' => env('OPENCAGE_API_KEY') ? 'Configured' : 'Not configured',
                'mapbox' => env('MAPBOX_API_KEY') ? 'Configured' : 'Not configured',
                'here' => env('HERE_API_KEY') ? 'Configured' : 'Not configured',
                'positionstack' => env('POSITIONSTACK_API_KEY') ? 'Configured' : 'Not configured',
                'nominatim' => 'Always available (rate limited)'
            ],
            'accuracy_priority' => 'Maximum accuracy - returns null if all providers fail',
            'recommendations' => []
        ];

        // Recommendations
        if (!env('GOOGLE_MAPS_API_KEY')) {
            $stats['recommendations'][] = 'Configure Google Maps API for best accuracy (paid service)';
        }
        
        if (!env('LOCATIONIQ_API_KEY')) {
            $stats['recommendations'][] = 'Configure LocationIQ API for free high-accuracy geocoding (5,000/day)';
        }
        
        $configuredProviders = array_filter($stats['api_providers'], function($status) {
            return $status === 'Configured';
        });
        
        if (count($configuredProviders) < 2) {
            $stats['recommendations'][] = 'Configure at least 2 API providers for better reliability';
        }
        
        $stats['recommendations'][] = 'Cache system provides instant results for previously geocoded addresses';
        $stats['recommendations'][] = 'System prioritizes accuracy - returns null if all providers fail';
        $stats['recommendations'][] = 'Handle null results in your application logic';
        
        return $stats;
    }

    /**
     * Validasi kualitas koordinat berdasarkan provider dan akurasi
     */
    public static function validateGeocodeQuality($result)
    {
        if (!$result) {
            return ['quality' => 'failed', 'score' => 0, 'issues' => ['No result returned']];
        }
        
        $issues = [];
        $score = 0;
        
        // Provider scoring
        $providerScores = [
            'google_maps' => 90,
            'locationiq' => 80,
            'opencage' => 75,
            'mapbox' => 70,
            'here' => 70,
            'nominatim' => 60,
            'positionstack' => 50
        ];
        
        $providerScore = $providerScores[$result['provider']] ?? 30;
        $score += $providerScore * 0.4;
        
        // Accuracy scoring
        $accuracyScores = [
            'very high' => 30,
            'high' => 25,
            'medium' => 15,
            'low' => 5
        ];
        
        $accuracyScore = $accuracyScores[$result['accuracy']] ?? 0;
        $score += $accuracyScore;
        
        // Confidence scoring
        if (isset($result['confidence'])) {
            $score += $result['confidence'] * 20;
        }
        
        // Regional validation
        if (self::isInMalangRegion($result['latitude'], $result['longitude'])) {
            $score += 10;
        } else {
            $issues[] = 'Coordinates outside Malang region';
            $score -= 15;
        }
        
        // Determine quality level
        if ($score >= 80) {
            $quality = 'excellent';
        } elseif ($score >= 60) {
            $quality = 'good';
        } elseif ($score >= 40) {
            $quality = 'fair';
        } elseif ($score >= 20) {
            $quality = 'poor';
        } else {
            $quality = 'very poor';
        }
        
        return [
            'quality' => $quality,
            'score' => round($score, 1),
            'provider_score' => $providerScore,
            'accuracy_score' => $accuracyScore,
            'confidence_score' => isset($result['confidence']) ? round($result['confidence'] * 20, 1) : 0,
            'regional_bonus' => self::isInMalangRegion($result['latitude'], $result['longitude']) ? 10 : -15,
            'issues' => $issues,
            'recommendations' => $score < 60 ? ['Consider manual verification', 'Check address spelling'] : []
        ];
    }

    /**
     * Debug informasi sistem
     */
    public static function debugInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'curl_enabled' => extension_loaded('curl'),
            'openssl_enabled' => extension_loaded('openssl'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'user_agent' => 'Enhanced-Laravel-Toko-App/2.0',
            'timeout' => 15,
            'cache_enabled' => true,
            'accuracy_mode' => 'maximum - no fallback coordinates',
            'malang_region_bounds' => [
                'min_lat' => -8.6,
                'max_lat' => -7.4,
                'min_lng' => 111.8,
                'max_lng' => 113.2
            ],
            'indonesia_bounds' => [
                'min_lat' => -11.5,
                'max_lat' => 6.5,
                'min_lng' => 94.5,
                'max_lng' => 141.5
            ],
            'providers_available' => [
                'google_maps' => env('GOOGLE_MAPS_API_KEY') ? 'Available' : 'Missing API Key',
                'locationiq' => env('LOCATIONIQ_API_KEY') ? 'Available' : 'Missing API Key',
                'opencage' => env('OPENCAGE_API_KEY') ? 'Available' : 'Missing API Key',
                'mapbox' => env('MAPBOX_API_KEY') ? 'Available' : 'Missing API Key',
                'here' => env('HERE_API_KEY') ? 'Available' : 'Missing API Key',
                'positionstack' => env('POSITIONSTACK_API_KEY') ? 'Available' : 'Missing API Key',
                'nominatim' => 'Always Available'
            ],
            'stats' => self::getGeocodingStats()
        ];
    }

    /**
     * Clear cache untuk alamat tertentu atau semua cache
     */
    public static function clearCache($address = null)
    {
        try {
            if ($address) {
                $cacheKey = 'geocode_' . md5(strtolower($address));
                Cache::forget($cacheKey);
                Log::info('Cleared cache for address: ' . $address);
                return true;
            } else {
                // Clear semua cache geocoding
                $cachePattern = 'geocode_*';
                // Note: Implementasi ini tergantung pada cache driver yang digunakan
                Log::info('Clearing all geocoding cache...');
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage());
            return false;
        }
    }
}
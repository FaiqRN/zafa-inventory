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
    public static function geocodeAddress($address, $detectedKelurahan = null)
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
            
            // 2. Coba provider cascade dengan timeout handling
            $startTime = microtime(true);
            $timeout = 15; // 15 seconds total timeout
            
            // Priority cascade: Internal Street DB -> Internal Kelurahan DB -> LocationIQ -> Nominatim
            $cascadeProviders = [
                ['method' => 'tryInternalStreetDatabase', 'timeout' => 3],
                ['method' => 'tryInternalKelurahanDatabase', 'timeout' => 2],
                ['method' => 'tryLocationIQ', 'timeout' => 4],
                ['method' => 'tryNominatim', 'timeout' => 4]
            ];
            
            foreach ($cascadeProviders as $providerConfig) {
                // Check if total timeout exceeded
                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime >= $timeout) {
                    Log::warning('Geocoding timeout exceeded after ' . round($elapsedTime, 2) . ' seconds');
                    break;
                }
                
                $method = $providerConfig['method'];
                $providerTimeout = $providerConfig['timeout'];
                
                Log::info("Trying provider: {$method} (timeout: {$providerTimeout}s)");
                
                try {
                    // Execute provider method with timeout
                    if ($method === 'tryInternalKelurahanDatabase' || $method === 'tryInternalStreetDatabase') {
                        $result = self::$method($cleanAddress, $detectedKelurahan);
                    } else {
                        $result = self::$method($cleanAddress);
                    }
                    
                    if ($result && self::validateCoordinates($result)) {
                        Log::info('Geocoding SUCCESS with: ' . $method);
                        
                        // Add quality info to result
                        $qualityInfo = self::validateGeocodeQuality($result);
                        $result['quality_score'] = $qualityInfo['score'];
                        $result['quality_level'] = $qualityInfo['level'];
                        $result['quality_badge'] = $qualityInfo['badge'];
                        $result['quality_color'] = $qualityInfo['color'];
                        $result['needs_improvement'] = $qualityInfo['needs_improvement'];
                        
                        // Cache hasil API untuk penggunaan berikutnya
                        self::cacheCoordinatesToDatabase($cleanAddress, $result);
                        
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning("Provider {$method} failed: " . $e->getMessage());
                }
                
                // Delay antar provider untuk menghindari rate limiting
                usleep(300000); // 0.3 detik delay
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
     * Try internal street database for lookup coordinates dengan NLP enhanced + OSM segments
     * Urutan pencarian: Kota → Kecamatan → Kelurahan → Jalan
     * Ini penting karena ada nama jalan yang sama di Kota Malang dan Kabupaten Malang
     */
    private static function tryInternalStreetDatabase($address, $detectedKelurahan = null)
    {
        try {
            // Parse alamat untuk extract informasi yang relevan
            $parsedAddress = self::parseIndonesianAddressNLP($address);
            Log::info('Internal Street Database: Parsed address', $parsedAddress);

            // 1. Coba cari POI terlebih dahulu (Indomaret, Alfamart, dll)
            $poiResult = self::tryPoiLookup($address, $parsedAddress);
            if ($poiResult) {
                Log::info('Internal Street Database: Found POI match');
                return $poiResult;
            }

            // ============================================================
            // URUTAN PENCARIAN: Kota → Kecamatan → Kelurahan → Jalan
            // Ini memastikan jalan dengan nama sama di lokasi berbeda
            // dapat dibedakan (misal: Jl. Veteran di Kota vs Kabupaten Malang)
            // ============================================================

            $kelurahanId = null;
            $kelurahanContext = null;
            $searchContext = [];

            // STEP 1: Identifikasi Kota/Kabupaten
            $kotaName = null;
            if (!empty($parsedAddress['kota'])) {
                $kotaName = strtolower(trim($parsedAddress['kota']));
                $searchContext['kota'] = $kotaName;
                Log::info("Internal Street Database: Kota context - {$kotaName}");
            }

            // STEP 2: Identifikasi Kecamatan
            $kecamatanName = null;
            if (!empty($parsedAddress['kecamatan'])) {
                $kecamatanName = strtolower(trim($parsedAddress['kecamatan']));
                $searchContext['kecamatan'] = $kecamatanName;
                Log::info("Internal Street Database: Kecamatan context - {$kecamatanName}");
            }

            // STEP 3: Identifikasi Kelurahan dengan konteks Kota & Kecamatan
            $kelurahanName = null;
            
            // Prioritas 1: Dari detected kelurahan (parameter)
            if ($detectedKelurahan && !empty($detectedKelurahan['kelurahan'])) {
                $kelurahanName = strtolower(trim($detectedKelurahan['kelurahan']));
            }
            // Prioritas 2: Dari parsed address
            elseif (!empty($parsedAddress['kelurahan'])) {
                $kelurahanName = strtolower(trim($parsedAddress['kelurahan']));
            }

            if ($kelurahanName) {
                $searchContext['kelurahan'] = $kelurahanName;
                
                // Build query dengan konteks hierarki: Kota → Kecamatan → Kelurahan
                $kelurahanQuery = \App\Models\KelurahanCoordinate::active()
                    ->where(function($q) use ($kelurahanName) {
                        $q->whereRaw('LOWER(nama) = ?', [$kelurahanName])
                          ->orWhereRaw('LOWER(nama_normalized) = ?', [$kelurahanName])
                          ->orWhereRaw('LOWER(nama) LIKE ?', ["%{$kelurahanName}%"]);
                    });

                // Filter by Kota jika ada
                if ($kotaName) {
                    $kelurahanQuery->where(function($q) use ($kotaName) {
                        $q->whereRaw('LOWER(kota) LIKE ?', ["%{$kotaName}%"]);
                    });
                }

                // Filter by Kecamatan jika ada
                if ($kecamatanName) {
                    $kelurahanQuery->where(function($q) use ($kecamatanName) {
                        $q->whereRaw('LOWER(kecamatan) LIKE ?', ["%{$kecamatanName}%"]);
                    });
                }

                $kelurahanContext = $kelurahanQuery->first();

                // Jika tidak ditemukan dengan filter ketat, coba tanpa filter kota/kecamatan
                if (!$kelurahanContext && ($kotaName || $kecamatanName)) {
                    Log::info('Internal Street Database: Relaxing kota/kecamatan filter for kelurahan search');
                    $kelurahanContext = \App\Models\KelurahanCoordinate::active()
                        ->where(function($q) use ($kelurahanName) {
                            $q->whereRaw('LOWER(nama) = ?', [$kelurahanName])
                              ->orWhereRaw('LOWER(nama_normalized) = ?', [$kelurahanName]);
                        })
                        ->first();
                }

                if ($kelurahanContext) {
                    $kelurahanId = $kelurahanContext->id;
                    Log::info("Internal Street Database: Found kelurahan - {$kelurahanContext->nama}, {$kelurahanContext->kecamatan}, {$kelurahanContext->kota}");
                }
            }

            // STEP 4: Cari Jalan dengan konteks wilayah
            Log::info('Internal Street Database: Search context', $searchContext);

            // Fuzzy search dengan konteks kelurahan - threshold lebih rendah untuk alamat panjang
            $minScore = strlen($address) > 50 ? 65 : 70;
            
            // Gunakan fuzzy search dengan konteks wilayah yang sudah diidentifikasi
            $streets = self::searchStreetsWithContext($address, $parsedAddress, $kelurahanId, $kotaName, $kecamatanName, $minScore);

            if ($streets->isNotEmpty()) {
                $jalan = $streets->first();
                $matchScore = $jalan->match_score ?? 0;

                Log::info("Internal Street Database: Found street - {$jalan->nama_jalan} (score: {$matchScore})");

                // 2. Gunakan OSM segment-based interpolation jika ada nomor rumah
                $houseNumber = null;
                if (!empty($parsedAddress['street_number'])) {
                    $houseNumber = (int) preg_replace('/[^0-9]/', '', $parsedAddress['street_number']);
                }

                // Get interpolated coordinate based on house number (OSM segments)
                $coordinates = $jalan->getCoordinateForHouseNumber($houseNumber);
                $lat = $coordinates['lat'];
                $lng = $coordinates['lng'];

                // Calculate confidence based on match score and OSM data availability
                $hasOsmSegments = $jalan->segments()->count() > 0;
                $confidence = min(0.99, 0.70 + ($matchScore / 100 * 0.29));
                
                if ($hasOsmSegments && $houseNumber) {
                    $confidence = min(0.99, $confidence + 0.05); // Bonus for segment interpolation
                }

                // Determine accuracy based on score and OSM data
                $accuracy = 'high';
                if ($matchScore >= 95 && $hasOsmSegments) {
                    $accuracy = 'very high';
                    $confidence = 0.98;
                } elseif ($matchScore >= 85) {
                    $accuracy = 'high';
                } elseif ($matchScore >= 70) {
                    $accuracy = 'medium';
                    $confidence = max(0.75, $confidence);
                }

                return [
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lng,
                    'formatted_address' => $jalan->full_location,
                    'accuracy' => $accuracy,
                    'provider' => $hasOsmSegments ? 'osm_street_database' : 'internal_street_database',
                    'confidence' => $confidence,
                    'match_score' => $matchScore,
                    'jalan_id' => $jalan->id,
                    'kelurahan_id' => $jalan->kelurahan_id,
                    'kelurahan_name' => $jalan->kelurahan ? $jalan->kelurahan->nama : null,
                    'kecamatan' => $jalan->kelurahan ? $jalan->kelurahan->kecamatan : null,
                    'kota' => $jalan->kelurahan ? $jalan->kelurahan->kota : null,
                    'validation_passed' => $matchScore >= 75,
                    'parsed_address' => $parsedAddress,
                    'has_osm_segments' => $hasOsmSegments,
                    'house_number_used' => $houseNumber
                ];
            }

            // ============================================================
            // FALLBACK HIERARCHY: Jalan tidak ada, coba fallback ke Kelurahan
            // Urutan: Jalan → Kelurahan → Kecamatan → Kota
            // ============================================================
            
            Log::info('Internal Street Database: No matching street found, trying fallback hierarchy');

            // FALLBACK 1: Jika kelurahan ditemukan, gunakan koordinat kelurahan
            if ($kelurahanContext) {
                Log::info("Internal Street Database: FALLBACK to kelurahan - {$kelurahanContext->nama}");
                
                return [
                    'latitude' => (float) $kelurahanContext->latitude,
                    'longitude' => (float) $kelurahanContext->longitude,
                    'formatted_address' => "{$kelurahanContext->nama}, {$kelurahanContext->kecamatan}, {$kelurahanContext->kota}",
                    'accuracy' => 'medium',
                    'provider' => 'internal_kelurahan_fallback',
                    'confidence' => 0.70,
                    'match_score' => 70,
                    'kelurahan_id' => $kelurahanContext->id,
                    'kelurahan_name' => $kelurahanContext->nama,
                    'kecamatan' => $kelurahanContext->kecamatan,
                    'kota' => $kelurahanContext->kota,
                    'validation_passed' => true,
                    'parsed_address' => $parsedAddress,
                    'fallback_reason' => 'street_not_found_kelurahan_used',
                    'requested_street' => $parsedAddress['street'] ?? null,
                ];
            }

            // FALLBACK 2: Jika kecamatan ada tapi kelurahan tidak, cari koordinat kecamatan
            if ($kecamatanName && $kotaName) {
                $kecamatanCoord = \App\Models\KelurahanCoordinate::active()
                    ->whereRaw('LOWER(kecamatan) LIKE ?', ["%{$kecamatanName}%"])
                    ->whereRaw('LOWER(kota) LIKE ?', ["%{$kotaName}%"])
                    ->selectRaw('AVG(latitude) as lat, AVG(longitude) as lng, kecamatan, kota')
                    ->groupBy('kecamatan', 'kota')
                    ->first();

                if ($kecamatanCoord && $kecamatanCoord->lat && $kecamatanCoord->lng) {
                    Log::info("Internal Street Database: FALLBACK to kecamatan - {$kecamatanCoord->kecamatan}");
                    
                    return [
                        'latitude' => (float) $kecamatanCoord->lat,
                        'longitude' => (float) $kecamatanCoord->lng,
                        'formatted_address' => "{$kecamatanCoord->kecamatan}, {$kecamatanCoord->kota}",
                        'accuracy' => 'low',
                        'provider' => 'internal_kecamatan_fallback',
                        'confidence' => 0.55,
                        'match_score' => 55,
                        'kecamatan' => $kecamatanCoord->kecamatan,
                        'kota' => $kecamatanCoord->kota,
                        'validation_passed' => true,
                        'parsed_address' => $parsedAddress,
                        'fallback_reason' => 'kelurahan_not_found_kecamatan_used',
                        'requested_kelurahan' => $kelurahanName,
                        'requested_street' => $parsedAddress['street'] ?? null,
                    ];
                }
            }

            Log::info('Internal Street Database: No fallback available');
            return null;

        } catch (\Exception $e) {
            Log::warning('Internal Street Database lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Search streets dengan konteks wilayah (Kota → Kecamatan → Kelurahan)
     * Memastikan jalan dengan nama sama di lokasi berbeda dapat dibedakan
     */
    private static function searchStreetsWithContext($address, $parsedAddress, $kelurahanId, $kotaName, $kecamatanName, $minScore = 70)
    {
        // Extract nama jalan dari alamat
        $streetName = !empty($parsedAddress['street']) ? $parsedAddress['street'] : \App\Models\Jalan::extractStreetNameFromAddress($address);
        
        if (empty($streetName)) {
            // Fallback ke fuzzy search biasa jika tidak ada nama jalan
            return \App\Models\Jalan::fuzzySearch($address, $kelurahanId, 5, $minScore);
        }

        // Ambil kota_type untuk membedakan Kota vs Kabupaten
        $kotaType = $parsedAddress['kota_type'] ?? null;
        $parsedKelurahan = $parsedAddress['kelurahan'] ?? null;

        Log::info("searchStreetsWithContext: Looking for street '{$streetName}' with context - kota: {$kotaName} (type: {$kotaType}), kecamatan: {$kecamatanName}, kelurahan: {$parsedKelurahan}");

        // Build query dengan prioritas konteks wilayah
        $query = \App\Models\Jalan::active()
            ->with('kelurahan')
            ->searchByName($streetName);

        // Ambil semua kandidat
        $candidates = $query->limit(100)->get();

        if ($candidates->isEmpty()) {
            // Coba dengan searchByAddress jika searchByName tidak menemukan
            $candidates = \App\Models\Jalan::active()
                ->with('kelurahan')
                ->searchByAddress($address)
                ->limit(100)
                ->get();
        }

        if ($candidates->isEmpty()) {
            Log::info('searchStreetsWithContext: No candidates found');
            return collect();
        }

        // ============================================================
        // PRIORITAS PENCARIAN (dari tinggi ke rendah):
        // 1. Jalan + Kelurahan cocok → best match (score 100)
        // 2. Jalan + Kecamatan cocok → good match (score 85)
        // 3. Jalan + Kota cocok → acceptable (score 70)
        // 4. Jalan tanpa context match → low priority (score 50)
        // ============================================================

        $scoredCandidates = $candidates->map(function($jalan) use ($streetName, $kotaName, $kotaType, $kecamatanName, $parsedKelurahan) {
            $hasKelurahan = $jalan->kelurahan !== null;
            $matchLevel = 'none';
            $score = 50; // Base score untuk nama jalan cocok
            $contextMatches = [];

            if (!$hasKelurahan) {
                $jalan->match_score = $score;
                $jalan->match_level = 'no_context';
                $jalan->context_matches = ['no_kelurahan_data'];
                return $jalan;
            }

            $jalanKelurahan = strtolower($jalan->kelurahan->nama ?? '');
            $jalanKecamatan = strtolower($jalan->kelurahan->kecamatan ?? '');
            $jalanKota = strtolower($jalan->kelurahan->kota ?? '');

            // Cek Kota/Kabupaten match
            $kotaMatch = false;
            if ($kotaName && $kotaType) {
                $isKotaMalang = str_contains($jalanKota, 'kota') && str_contains($jalanKota, 'malang');
                $isKabMalang = str_contains($jalanKota, 'kabupaten') && str_contains($jalanKota, 'malang');
                
                if ($kotaType === 'kota' && str_contains(strtolower($kotaName), 'malang')) {
                    $kotaMatch = $isKotaMalang;
                } elseif ($kotaType === 'kabupaten' && str_contains(strtolower($kotaName), 'malang')) {
                    $kotaMatch = $isKabMalang;
                } else {
                    $kotaMatch = str_contains($jalanKota, strtolower($kotaName));
                }
            } elseif ($kotaName) {
                $kotaMatch = str_contains($jalanKota, strtolower($kotaName));
            }

            // Cek Kecamatan match
            $kecamatanMatch = false;
            if ($kecamatanName) {
                $kecamatanMatch = str_contains($jalanKecamatan, strtolower($kecamatanName)) 
                               || str_contains(strtolower($kecamatanName), $jalanKecamatan);
            }

            // Cek Kelurahan match
            $kelurahanMatch = false;
            if ($parsedKelurahan) {
                $kelurahanMatch = str_contains($jalanKelurahan, strtolower($parsedKelurahan)) 
                               || str_contains(strtolower($parsedKelurahan), $jalanKelurahan);
            }

            // ============================================================
            // SCORING BERDASARKAN PRIORITAS
            // ============================================================
            
            // PRIORITAS 1: Jalan + Kelurahan + Kecamatan + Kota cocok (BEST)
            if ($kelurahanMatch && $kecamatanMatch && $kotaMatch) {
                $score = 100;
                $matchLevel = 'kelurahan_exact';
                $contextMatches = ['kelurahan', 'kecamatan', 'kota'];
            }
            // PRIORITAS 2: Jalan + Kecamatan + Kota cocok (kelurahan beda/tidak ada)
            elseif ($kecamatanMatch && $kotaMatch) {
                $score = 85;
                $matchLevel = 'kecamatan_match';
                $contextMatches = ['kecamatan', 'kota'];
                if ($kelurahanMatch) {
                    $contextMatches[] = 'kelurahan';
                }
            }
            // PRIORITAS 3: Jalan + Kota cocok (kecamatan beda)
            elseif ($kotaMatch) {
                $score = 70;
                $matchLevel = 'kota_match';
                $contextMatches = ['kota'];
            }
            // PRIORITAS 4: Kota tidak cocok (Kota vs Kabupaten salah)
            else {
                $score = 30; // Score rendah, kemungkinan besar salah
                $matchLevel = 'kota_mismatch';
                $contextMatches = ['kota_mismatch'];
            }

            $jalan->match_score = $score;
            $jalan->match_level = $matchLevel;
            $jalan->context_matches = $contextMatches;
            $jalan->debug_info = [
                'kelurahan' => $jalanKelurahan,
                'kecamatan' => $jalanKecamatan,
                'kota' => $jalanKota,
                'kelurahan_match' => $kelurahanMatch,
                'kecamatan_match' => $kecamatanMatch,
                'kota_match' => $kotaMatch,
            ];

            return $jalan;
        });

        // Sort by score descending
        $sortedCandidates = $scoredCandidates->sortByDesc('match_score');

        // Log untuk debugging
        Log::info("searchStreetsWithContext: Scored " . $sortedCandidates->count() . " candidates:");
        foreach ($sortedCandidates->take(5) as $candidate) {
            $kelInfo = $candidate->kelurahan 
                ? "{$candidate->kelurahan->nama}, {$candidate->kelurahan->kecamatan}, {$candidate->kelurahan->kota}" 
                : 'NO_KELURAHAN';
            Log::info("  - {$candidate->nama_jalan} | score:{$candidate->match_score} level:{$candidate->match_level} | {$kelInfo}");
        }

        // Filter berdasarkan minScore
        $results = $sortedCandidates
            ->filter(fn($jalan) => $jalan->match_score >= $minScore)
            ->take(5);

        if ($results->isNotEmpty()) {
            $best = $results->first();
            Log::info("searchStreetsWithContext: SELECTED - {$best->nama_jalan} (score: {$best->match_score}, level: {$best->match_level})");
        } else {
            Log::info("searchStreetsWithContext: No results passed minScore filter ({$minScore})");
        }

        return $results->values();
    }

    /**
     * Try POI lookup untuk landmark seperti Indomaret, Alfamart, dll
     */
    private static function tryPoiLookup($address, $parsedAddress = null)
    {
        try {
            $addressLower = strtolower($address);
            
            // ============================================================
            // STRATEGI 1: Cari langsung berdasarkan nama spesifik di alamat
            // Ini handle kasus seperti "Universitas Brawijaya" atau "UB"
            // ============================================================
            
            // Bersihkan alamat dari kata-kata umum untuk dapat nama POI
            $cleanedForPoi = preg_replace('/\b(jl\.?|jalan|no\.?|nomor|rt|rw|kec\.?|kecamatan|kel\.?|kelurahan|kota|kabupaten|kab\.?|malang|jawa timur|jatim|\d+)\b/i', '', $addressLower);
            $cleanedForPoi = preg_replace('/[,\.\-\/]+/', ' ', $cleanedForPoi);
            $cleanedForPoi = preg_replace('/\s+/', ' ', trim($cleanedForPoi));
            
            if (strlen($cleanedForPoi) >= 3) {
                // Cari POI yang namanya mengandung kata-kata dari alamat
                $words = array_filter(explode(' ', $cleanedForPoi), fn($w) => strlen($w) >= 3);
                
                if (!empty($words)) {
                    $poiQuery = \App\Models\Poi::active();
                    
                    // Cari POI yang mengandung semua kata penting
                    foreach ($words as $word) {
                        $poiQuery->where(function($q) use ($word) {
                            $q->whereRaw('LOWER(nama) LIKE ?', ["%{$word}%"])
                              ->orWhereRaw('LOWER(nama_normalized) LIKE ?', ["%{$word}%"]);
                        });
                    }
                    
                    $directMatch = $poiQuery->first();
                    
                    if ($directMatch) {
                        Log::info("POI Lookup: Direct match found - {$directMatch->nama}");
                        return self::buildPoiResult($directMatch, 95);
                    }
                }
            }

            // ============================================================
            // STRATEGI 2: Keyword-based lookup untuk POI umum
            // ============================================================
            
            $poiKeywords = [
                'indomaret', 'alfamart', 'alfamidi', 'circle k', 'lawson',
                'starbucks', 'mcdonald', 'kfc', 'burger king', 'pizza hut',
                'bank bca', 'bank bri', 'bank mandiri', 'bank bni', 'atm',
                'spbu', 'pertamina', 'shell',
                'rs ', 'rumah sakit', 'puskesmas', 'klinik', 'apotek',
                'sma ', 'smp ', 'sd ', 'universitas', 'sekolah', 'kampus',
                'masjid', 'gereja', 'pura', 'vihara',
                'mall', 'plaza', 'pasar', 'terminal', 'stasiun',
                'hotel', 'guest house', 'penginapan',
            ];

            $foundKeyword = null;
            foreach ($poiKeywords as $keyword) {
                if (strpos($addressLower, $keyword) !== false) {
                    $foundKeyword = $keyword;
                    break;
                }
            }

            if (!$foundKeyword) {
                return null;
            }

            // Search POI in database
            $pois = \App\Models\Poi::active()
                ->where(function($q) use ($foundKeyword) {
                    $q->whereRaw('LOWER(nama) LIKE ?', ["%{$foundKeyword}%"])
                      ->orWhereRaw('LOWER(nama_normalized) LIKE ?', ["%{$foundKeyword}%"]);
                })
                ->limit(20)
                ->get();

            if ($pois->isEmpty()) {
                return null;
            }

            // If only one result, use it
            if ($pois->count() === 1) {
                $poi = $pois->first();
                return self::buildPoiResult($poi, 90);
            }

            // Multiple results - find best match using full address similarity
            $bestPoi = null;
            $bestScore = 0;

            foreach ($pois as $poi) {
                $score = 70; // Base score
                $poiNameLower = strtolower($poi->nama);
                
                // Bonus besar jika nama POI ada di alamat input
                if (strpos($addressLower, $poiNameLower) !== false) {
                    $score += 25;
                }
                
                // Bonus untuk setiap kata yang cocok
                $poiWords = explode(' ', $poiNameLower);
                foreach ($poiWords as $word) {
                    if (strlen($word) >= 3 && strpos($addressLower, $word) !== false) {
                        $score += 3;
                    }
                }

                // Bonus for street name match
                if (!empty($parsedAddress['street'])) {
                    $streetNormalized = strtolower($parsedAddress['street']);
                    if ($poi->alamat_jalan && strpos(strtolower($poi->alamat_jalan), $streetNormalized) !== false) {
                        $score += 10;
                    }
                }

                // Bonus for kelurahan match
                if (!empty($parsedAddress['kelurahan']) && $poi->kelurahan) {
                    $kelNormalized = strtolower($parsedAddress['kelurahan']);
                    if (strpos(strtolower($poi->kelurahan->nama ?? ''), $kelNormalized) !== false) {
                        $score += 5;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestPoi = $poi;
                }
            }

            if ($bestPoi && $bestScore >= 75) {
                Log::info("POI Lookup: Best match - {$bestPoi->nama} (score: {$bestScore})");
                return self::buildPoiResult($bestPoi, $bestScore);
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('POI lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build result array from POI
     */
    private static function buildPoiResult($poi, $matchScore)
    {
        $formattedAddress = $poi->nama;
        if ($poi->alamat_jalan) {
            $formattedAddress .= ', ' . $poi->alamat_jalan;
        }
        if ($poi->kelurahan) {
            $formattedAddress .= ', ' . $poi->kelurahan->nama . ', ' . $poi->kelurahan->kecamatan . ', ' . $poi->kelurahan->kota;
        } else {
            // Default to Malang region for OSM POI without kelurahan
            $formattedAddress .= ', Malang, Jawa Timur';
        }

        return [
            'latitude' => (float) $poi->latitude,
            'longitude' => (float) $poi->longitude,
            'formatted_address' => $formattedAddress,
            'accuracy' => 'very high',
            'provider' => 'osm_poi_database',
            'confidence' => min(0.99, 0.80 + ($matchScore / 100 * 0.19)),
            'match_score' => $matchScore,
            'poi_id' => $poi->id,
            'poi_name' => $poi->nama,
            'poi_kategori' => $poi->kategori,
            'kelurahan_id' => $poi->kelurahan_id,
            'kelurahan_name' => $poi->kelurahan ? $poi->kelurahan->nama : null,
            'validation_passed' => true,
        ];
    }

    /**
     * NLP Parser untuk alamat Indonesia - extract komponen alamat
     */
    public static function parseIndonesianAddressNLP($address)
    {
        $result = [
            'street' => '',
            'street_number' => '',
            'rt_rw' => '',
            'building' => '',
            'kelurahan' => '',
            'kecamatan' => '',
            'kota' => '',
            'kota_type' => '', // 'kota' atau 'kabupaten' - penting untuk membedakan Kota vs Kabupaten Malang
            'provinsi' => '',
            'postal_code' => '',
            'raw' => $address
        ];

        if (empty($address)) return $result;

        // Normalize address
        $normalized = $address;
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        // Extract RT/RW
        if (preg_match('/\bRT\.?\s*(\d+)\s*[\/\s]*RW\.?\s*(\d+)/i', $normalized, $matches)) {
            $result['rt_rw'] = "RT {$matches[1]}/RW {$matches[2]}";
            $normalized = preg_replace('/\bRT\.?\s*\d+\s*[\/\s]*RW\.?\s*\d+/i', '', $normalized);
        }

        // Extract postal code
        if (preg_match('/\b(\d{5})\b/', $normalized, $matches)) {
            $result['postal_code'] = $matches[1];
            $normalized = preg_replace('/\b\d{5}\b/', '', $normalized);
        }

        // Extract Provinsi
        $provinsiPatterns = [
            'jawa timur' => 'Jawa Timur',
            'jatim' => 'Jawa Timur',
            'east java' => 'Jawa Timur',
            'jawa tengah' => 'Jawa Tengah',
            'jawa barat' => 'Jawa Barat',
        ];
        foreach ($provinsiPatterns as $pattern => $value) {
            if (stripos($normalized, $pattern) !== false) {
                $result['provinsi'] = $value;
                $normalized = preg_replace('/\b' . preg_quote($pattern, '/') . '\b/i', '', $normalized);
                break;
            }
        }

        // Extract Kota/Kabupaten - perbaikan regex untuk handle koma dan spasi
        // Pattern: "Kota Malang" atau "Kabupaten Malang" atau "Kab. Malang"
        if (preg_match('/(?:kota|kabupaten|kab\.?)\s+([a-zA-Z\s]+?)(?:,|$|\s+(?:jawa|provinsi|prov\.))/i', $normalized, $matches)) {
            $result['kota'] = trim($matches[1]);
            // Simpan juga tipe (kota/kabupaten) untuk membedakan
            if (preg_match('/^kota\s/i', $matches[0])) {
                $result['kota_type'] = 'kota';
            } else {
                $result['kota_type'] = 'kabupaten';
            }
            $normalized = preg_replace('/(?:kota|kabupaten|kab\.?)\s+[a-zA-Z\s]+?(?=,|$|\s+(?:jawa|provinsi|prov\.))/i', '', $normalized);
        }

        // Extract Kecamatan
        if (preg_match('/(?:kec\.?|kecamatan)\s+([^,]+)/i', $normalized, $matches)) {
            $result['kecamatan'] = trim($matches[1]);
            $normalized = preg_replace('/(?:kec\.?|kecamatan)\s+[^,]+/i', '', $normalized);
        }

        // Extract Kelurahan/Desa
        if (preg_match('/(?:kel\.?|kelurahan|desa|ds\.?)\s+([^,]+)/i', $normalized, $matches)) {
            $result['kelurahan'] = trim($matches[1]);
            $normalized = preg_replace('/(?:kel\.?|kelurahan|desa|ds\.?)\s+[^,]+/i', '', $normalized);
        }

        // Extract Street dengan nomor
        $streetPatterns = [
            '/(?:jalan|jl\.?)\s+([^,]+?)(?:\s+(?:no\.?|nomor)\s*(\d+[A-Za-z]?))?(?:,|$)/i',
            '/(?:gang|gg\.?)\s+([^,]+?)(?:\s+(?:no\.?|nomor)\s*(\d+[A-Za-z]?))?(?:,|$)/i',
        ];

        foreach ($streetPatterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches)) {
                $result['street'] = trim($matches[1]);
                if (!empty($matches[2])) {
                    $result['street_number'] = $matches[2];
                }
                break;
            }
        }

        // Extract standalone street number if not found
        if (empty($result['street_number'])) {
            if (preg_match('/\b(?:no\.?|nomor)\s*(\d+[A-Za-z]?)\b/i', $normalized, $matches)) {
                $result['street_number'] = $matches[1];
            }
        }

        // If kelurahan still empty, try to extract from comma-separated parts
        if (empty($result['kelurahan'])) {
            $parts = array_map('trim', explode(',', $normalized));
            foreach ($parts as $index => $part) {
                // Skip first part (usually street), skip parts with keywords
                if ($index === 0) continue;
                if (preg_match('/\b(jl\.?|jalan|gang|gg\.?|no\.?|kec\.?|kota|kab\.?|rt|rw)\b/i', $part)) continue;

                // Clean part
                $cleanPart = trim(preg_replace('/[^a-zA-Z\s]/', '', $part));
                if (strlen($cleanPart) >= 3 && strlen($cleanPart) <= 30) {
                    $result['kelurahan'] = $cleanPart;
                    break;
                }
            }
        }

        // FALLBACK: Jika street masih kosong, ambil bagian pertama sebagai potential street name
        // Ini handle alamat seperti "Indrokilo, Wonosari, Kabupaten Malang" tanpa prefix Jl.
        if (empty($result['street'])) {
            $parts = array_map('trim', explode(',', $address));
            if (!empty($parts[0])) {
                $firstPart = trim($parts[0]);
                // Pastikan bukan keyword wilayah dan panjang wajar untuk nama jalan/dusun
                if (!preg_match('/\b(kel\.?|kelurahan|kec\.?|kecamatan|kota|kabupaten|kab\.?|rt|rw|provinsi|prov\.?|jawa)\b/i', $firstPart)) {
                    $cleanFirst = trim(preg_replace('/[^a-zA-Z\s\d\-\.]/u', '', $firstPart));
                    if (strlen($cleanFirst) >= 3 && strlen($cleanFirst) <= 50) {
                        $result['street'] = $cleanFirst;
                        $result['street_no_prefix'] = true; // Flag bahwa ini alamat tanpa prefix jalan
                        Log::info("NLP Parser: Extracted street without prefix - '{$cleanFirst}'");
                    }
                }
            }
            
            // Jika kelurahan masih kosong dan ada part kedua, gunakan sebagai kelurahan
            if (empty($result['kelurahan']) && count($parts) >= 2) {
                $secondPart = trim($parts[1]);
                if (!preg_match('/\b(jl\.?|jalan|gang|gg\.?|no\.?|kec\.?|kecamatan|kota|kabupaten|kab\.?|rt|rw)\b/i', $secondPart)) {
                    $cleanSecond = trim(preg_replace('/[^a-zA-Z\s]/', '', $secondPart));
                    if (strlen($cleanSecond) >= 3 && strlen($cleanSecond) <= 30) {
                        $result['kelurahan'] = $cleanSecond;
                    }
                }
            }
        }

        // Extract building/complex names
        if (preg_match('/(?:perumahan|perum|komplek|komp\.?|apartemen|apt\.?|ruko|gedung|gd\.?)\s+([^,]+)/i', $normalized, $matches)) {
            $result['building'] = trim($matches[1]);
        }

        return $result;
    }

    /**
     * Try internal kelurahan database for lookup coordinates dengan fuzzy matching
     * Urutan pencarian: Kota → Kecamatan → Kelurahan
     */
    private static function tryInternalKelurahanDatabase($address, $detectedKelurahan = null)
    {
        try {
            $kelurahan = null;
            $matchScore = 0;

            // Parse alamat untuk extract konteks wilayah
            $parsedAddress = self::parseIndonesianAddressNLP($address);
            
            $kotaName = !empty($parsedAddress['kota']) ? strtolower(trim($parsedAddress['kota'])) : null;
            $kecamatanName = !empty($parsedAddress['kecamatan']) ? strtolower(trim($parsedAddress['kecamatan'])) : null;
            $kelurahanName = null;

            // Prioritas 1: Dari detected kelurahan (parameter)
            if ($detectedKelurahan && !empty($detectedKelurahan['kelurahan'])) {
                $kelurahanName = strtolower(trim($detectedKelurahan['kelurahan']));
            }
            // Prioritas 2: Dari parsed address
            elseif (!empty($parsedAddress['kelurahan'])) {
                $kelurahanName = strtolower(trim($parsedAddress['kelurahan']));
            }

            Log::info("Internal Kelurahan Database: Search context - kota: {$kotaName}, kecamatan: {$kecamatanName}, kelurahan: {$kelurahanName}");

            // 1. Cari dengan konteks hierarki: Kota → Kecamatan → Kelurahan
            if ($kelurahanName) {
                $query = \App\Models\KelurahanCoordinate::active()
                    ->where(function($q) use ($kelurahanName) {
                        $q->whereRaw('LOWER(nama) = ?', [$kelurahanName])
                          ->orWhereRaw('LOWER(nama_normalized) = ?', [$kelurahanName])
                          ->orWhereRaw('LOWER(nama) LIKE ?', ["%{$kelurahanName}%"]);
                    });

                // Filter by Kota jika ada (penting untuk membedakan Kota vs Kabupaten Malang)
                if ($kotaName) {
                    $query->where(function($q) use ($kotaName) {
                        $q->whereRaw('LOWER(kota) LIKE ?', ["%{$kotaName}%"]);
                    });
                }

                // Filter by Kecamatan jika ada
                if ($kecamatanName) {
                    $query->where(function($q) use ($kecamatanName) {
                        $q->whereRaw('LOWER(kecamatan) LIKE ?', ["%{$kecamatanName}%"]);
                    });
                }

                $kelurahan = $query->first();

                if ($kelurahan) {
                    $matchScore = 100;
                    Log::info("Internal Database: Found kelurahan with context - {$kelurahan->nama}, {$kelurahan->kecamatan}, {$kelurahan->kota}");
                }
            }

            // 2. Jika tidak ditemukan dengan filter ketat, coba tanpa filter kota/kecamatan
            if (!$kelurahan && $kelurahanName && ($kotaName || $kecamatanName)) {
                Log::info('Internal Database: Relaxing kota/kecamatan filter');
                $kelurahan = \App\Models\KelurahanCoordinate::active()
                    ->where(function($q) use ($kelurahanName) {
                        $q->whereRaw('LOWER(nama) = ?', [$kelurahanName])
                          ->orWhereRaw('LOWER(nama_normalized) = ?', [$kelurahanName]);
                    })
                    ->first();

                if ($kelurahan) {
                    $matchScore = 85; // Lower score karena tidak ada konteks wilayah
                    Log::info("Internal Database: Found kelurahan without context - {$kelurahan->nama}");
                }
            }

            // 3. Fuzzy matching jika masih tidak ditemukan
            if (!$kelurahan && $kelurahanName) {
                $allKelurahan = \App\Models\KelurahanCoordinate::active()->get();

                $bestMatch = null;
                $bestScore = 0;

                foreach ($allKelurahan as $kel) {
                    $kelNama = strtolower($kel->nama);
                    $kelNormalized = strtolower($kel->nama_normalized ?? $kel->nama);

                    // Calculate fuzzy score
                    $score = self::calculateKelurahanFuzzyScore($kelurahanName, $kelNama, $kelNormalized);

                    // Bonus untuk konteks wilayah yang cocok
                    if ($kotaName && str_contains(strtolower($kel->kota ?? ''), $kotaName)) {
                        $score += 10;
                    } elseif ($kotaName) {
                        $score -= 5; // Penalty jika kota tidak cocok
                    }

                    if ($kecamatanName && str_contains(strtolower($kel->kecamatan ?? ''), $kecamatanName)) {
                        $score += 5;
                    }

                    if ($score > $bestScore && $score >= 70) {
                        $bestScore = $score;
                        $bestMatch = $kel;
                    }
                }

                if ($bestMatch) {
                    $kelurahan = $bestMatch;
                    $matchScore = min(100, $bestScore);
                    Log::info("Internal Database: Found fuzzy kelurahan match - {$kelurahan->nama} (score: {$matchScore})");
                }
            }

            // 4. Last resort - search kelurahan name in full address
            if (!$kelurahan) {
                $addressLower = strtolower($address);
                $addressNormalized = preg_replace('/[^a-z0-9]/', '', $addressLower);

                $allKelurahan = \App\Models\KelurahanCoordinate::active()->get();

                foreach ($allKelurahan as $kel) {
                    $kelNormalized = preg_replace('/[^a-z0-9]/', '', strtolower($kel->nama));

                    if (strlen($kelNormalized) >= 4 && str_contains($addressNormalized, $kelNormalized)) {
                        $pos = strpos($addressNormalized, $kelNormalized);
                        $posScore = max(0, 80 - ($pos / strlen($addressNormalized) * 20));

                        // Bonus/penalty untuk konteks wilayah
                        if ($kotaName && str_contains(strtolower($kel->kota ?? ''), $kotaName)) {
                            $posScore += 10;
                        } elseif ($kotaName) {
                            $posScore -= 5;
                        }

                        if ($posScore > $matchScore) {
                            $kelurahan = $kel;
                            $matchScore = $posScore;
                        }
                    }
                }

                if ($kelurahan) {
                    Log::info("Internal Database: Found kelurahan in address - {$kelurahan->nama} (score: {$matchScore})");
                }
            }

            if ($kelurahan) {
                Log::info('Internal Database: Final kelurahan - ' . $kelurahan->nama);

                $isGenerated = ($kelurahan->source ?? 'generated') === 'generated';
                $baseAccuracy = $kelurahan->accuracy ?? ($isGenerated ? 'low' : 'medium');

                // Adjust accuracy based on match score
                if ($matchScore >= 95) {
                    $accuracy = 'high';
                } elseif ($matchScore >= 80) {
                    $accuracy = 'medium';
                } else {
                    $accuracy = $baseAccuracy;
                }

                // Calculate confidence based on match score and source
                $baseConfidence = $isGenerated ? 0.4 : 0.7;
                $confidence = $baseConfidence + ($matchScore / 100 * 0.25);

                return [
                    'latitude' => (float) $kelurahan->latitude,
                    'longitude' => (float) $kelurahan->longitude,
                    'formatted_address' => $kelurahan->full_location ?? "{$kelurahan->nama}, {$kelurahan->kecamatan}, {$kelurahan->kota}",
                    'accuracy' => $accuracy,
                    'provider' => 'internal_database',
                    'confidence' => min(0.95, $confidence),
                    'match_score' => $matchScore,
                    'kelurahan_name' => $kelurahan->nama,
                    'kecamatan' => $kelurahan->kecamatan,
                    'kota' => $kelurahan->kota,
                    'validation_passed' => $matchScore >= 75 && !$isGenerated,
                    'is_generated' => $isGenerated
                ];
            }

            Log::info('Internal Database: No matching kelurahan found');
            return null;

        } catch (\Exception $e) {
            Log::warning('Internal Database lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate fuzzy score untuk kelurahan matching
     */
    private static function calculateKelurahanFuzzyScore($input, $kelNama, $kelNormalized)
    {
        // Normalize input
        $inputNormalized = preg_replace('/[^a-z0-9]/', '', strtolower($input));
        $kelNamaNormalized = preg_replace('/[^a-z0-9]/', '', strtolower($kelNama));

        // Exact match
        if ($inputNormalized === $kelNamaNormalized) {
            return 100;
        }

        // Contains match
        if (str_contains($inputNormalized, $kelNamaNormalized) || str_contains($kelNamaNormalized, $inputNormalized)) {
            $minLen = min(strlen($inputNormalized), strlen($kelNamaNormalized));
            $maxLen = max(strlen($inputNormalized), strlen($kelNamaNormalized));
            return 75 + ($minLen / $maxLen * 20);
        }

        // Jaro-Winkler similarity
        $jwScore = \App\Models\Jalan::jaroWinklerSimilarity($inputNormalized, $kelNamaNormalized);

        // Levenshtein similarity
        $maxLen = max(strlen($inputNormalized), strlen($kelNamaNormalized));
        if ($maxLen > 0) {
            $distance = levenshtein($inputNormalized, $kelNamaNormalized);
            $levScore = round((($maxLen - $distance) / $maxLen) * 100);
        } else {
            $levScore = 0;
        }

        // Combined score - prioritize Jaro-Winkler for short strings
        return round(($jwScore * 0.6) + ($levScore * 0.4));
    }

    /**
     * Try LocationIQ sebagai backup
     */
    private static function tryLocationIQ($address)
    {
        try {
            $result = self::geocodeWithLocationIQ($address);
            if ($result) {
                Log::info('LocationIQ: Success');
                return $result;
            }
            Log::info('LocationIQ: No valid result');
            return null;
        } catch (\Exception $e) {
            Log::warning('LocationIQ: Exception - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Try Nominatim sebagai final fallback
     */
    private static function tryNominatim($address)
    {
        try {
            $result = self::geocodeWithNominatim($address);
            if ($result) {
                Log::info('Nominatim: Success');
                return $result;
            }
            Log::info('Nominatim: No valid result');
            return null;
        } catch (\Exception $e) {
            Log::warning('Nominatim: Exception - ' . $e->getMessage());
            return null;
        }
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
     * Validate coordinate range untuk check latitude/longitude validity
     * Returns array dengan validation result dan error messages
     */
    public static function validateCoordinateRange($latitude, $longitude)
    {
        $errors = [];
        $isValid = true;

        // Validate latitude range (-90 to 90)
        if (!is_numeric($latitude)) {
            $errors[] = 'Latitude harus berupa angka';
            $isValid = false;
        } elseif ($latitude < -90 || $latitude > 90) {
            $errors[] = 'Latitude harus berada dalam range -90 sampai 90';
            $isValid = false;
        }

        // Validate longitude range (-180 to 180)
        if (!is_numeric($longitude)) {
            $errors[] = 'Longitude harus berupa angka';
            $isValid = false;
        } elseif ($longitude < -180 || $longitude > 180) {
            $errors[] = 'Longitude harus berada dalam range -180 sampai 180';
            $isValid = false;
        }

        // Check for zero coordinates (likely invalid)
        if ($latitude == 0 && $longitude == 0) {
            $errors[] = 'Koordinat (0, 0) tidak valid';
            $isValid = false;
        }

        // Additional validation checks
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
            // Coba LocationIQ dulu jika tersedia
            $apiKey = env('LOCATIONIQ_API_KEY');
            if ($apiKey) {
                $response = Http::timeout(12)
                    ->get('https://eu1.locationiq.com/v1/reverse.php', [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'key' => $apiKey,
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
                            'provider' => 'locationiq'
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
                'osm_database' => 'Active (8,700+ streets, 1,500+ POIs)',
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
        if (!env('LOCATIONIQ_API_KEY')) {
            $stats['recommendations'][] = 'Configure LocationIQ API for free high-accuracy geocoding (5,000/day)';
        }
        
        $configuredProviders = array_filter($stats['api_providers'], function($status) {
            return $status === 'Configured' || str_contains($status, 'Active');
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
     * Calculate distance between two coordinates in meters using Haversine formula
     * 
     * @param float $lat1 First latitude
     * @param float $lon1 First longitude
     * @param float $lat2 Second latitude
     * @param float $lon2 Second longitude
     * @return float Distance in meters
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 2); // Return distance in meters with 2 decimal precision
    }

    /**
     * Validate coordinate tolerance (default 50m max)
     * 
     * @param float $originalLat Original latitude
     * @param float $originalLon Original longitude
     * @param float $newLat New latitude
     * @param float $newLon New longitude
     * @param int $maxToleranceMeters Maximum allowed distance in meters (default 50)
     * @return array Validation result with distance and tolerance info
     */
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
    /**
     * Calculate quality score untuk geocoding result
     * Score range: 0-100
     */
    public static function calculateQualityScore($result)
    {
        if (!$result || !isset($result['latitude']) || !isset($result['longitude'])) {
            return 0;
        }

        $score = 50; // base score

        // Provider bonus (30 points max) - OSM database now prioritized
        if (isset($result['provider'])) {
            switch ($result['provider']) {
                case 'osm_street_database':
                case 'osm_poi_database':
                    // OSM data is very accurate for local addresses
                    $matchScore = $result['match_score'] ?? 70;
                    if ($matchScore >= 90) {
                        $score += 30; // Best accuracy for high matches
                    } elseif ($matchScore >= 80) {
                        $score += 28;
                    } else {
                        $score += 25;
                    }
                    break;
                case 'internal_street_database':
                    // Street-level database is very accurate for local addresses
                    $matchScore = $result['match_score'] ?? 70;
                    if ($matchScore >= 90) {
                        $score += 28;
                    } elseif ($matchScore >= 80) {
                        $score += 25;
                    } else {
                        $score += 20;
                    }
                    break;
                case 'locationiq':
                    $score += 25;
                    break;
                case 'here':
                    $score += 23;
                    break;
                case 'opencage':
                    $score += 20;
                    break;
                case 'mapbox':
                    $score += 18;
                    break;
                case 'internal_database':
                    // Kelurahan-level is less precise than street-level
                    $matchScore = $result['match_score'] ?? 70;
                    if ($matchScore >= 90) {
                        $score += 18;
                    } else {
                        $score += 15;
                    }
                    break;
                case 'nominatim':
                    $score += 12;
                    break;
                case 'positionstack':
                    $score += 10;
                    break;
                case 'interactive_map':
                    return 100; // Manual selection always gets perfect score
            }
        }
        
        // Accuracy bonus (20 points max)
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
        } elseif (isset($result['location_type'])) {
            // Location type bonus (for external API results)
            switch ($result['location_type']) {
                case 'ROOFTOP':
                    $score += 20;
                    break;
                case 'RANGE_INTERPOLATED':
                    $score += 15;
                    break;
                case 'GEOMETRIC_CENTER':
                    $score += 10;
                    break;
                case 'APPROXIMATE':
                    $score += 5;
                    break;
            }
        }
        
        // Malang region bonus (10 points)
        if (self::isInMalangRegion($result['latitude'], $result['longitude'])) {
            $score += 10;
        }
        
        // Confidence bonus (10 points max)
        if (isset($result['confidence'])) {
            $score += min(10, $result['confidence'] * 10);
        }
        
        // Validation passed bonus (5 points)
        if (isset($result['validation_passed']) && $result['validation_passed']) {
            $score += 5;
        }
        
        return min(100, max(0, $score));
    }

    /**
     * Validate geocode quality dan return quality level dengan score
     */
    public static function validateGeocodeQuality($result)
    {
        $score = self::calculateQualityScore($result);
        
        // Determine quality level based on score
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
            'accuracy' => $result['accuracy'] ?? $result['location_type'] ?? 'unknown',
            'confidence' => $result['confidence'] ?? null,
            'quality' => $level, // Backward compatibility
            'recommendations' => [] // Default empty recommendations
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
                'osm_database' => 'Available (8,700+ streets, 1,500+ POIs)',
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
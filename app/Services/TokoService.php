<?php

namespace App\Services;

use App\Models\Toko;
use App\Helpers\MasterData\Toko\TokoHelper;
use Illuminate\Support\Facades\Log;

class TokoService
{
    /**
     * Store new toko with interactive map coordinates
     *
     * @param array $data
     * @return array
     */
    public static function store(array $data): array
    {
        try {
            $latitude = (float) $data[Toko::FIELD_LATITUDE];
            $longitude = (float) $data[Toko::FIELD_LONGITUDE];

            // Validate coordinates are in Malang region
            if (!GeocodingService::isInMalangRegion($latitude, $longitude)) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Koordinat yang dipilih berada di luar wilayah Malang Raya. Silakan pilih lokasi yang sesuai.',
                    'coordinate_info' => [
                        'selected_coordinates' => [$latitude, $longitude],
                        'valid_region' => 'Malang Raya (Kota Malang, Kabupaten Malang, Kota Batu)'
                    ]
                ];
            }

            Log::info("Storing toko with interactive map coordinates: {$data[Toko::FIELD_NAMA_TOKO]} - Lat: {$latitude}, Lng: {$longitude}");

            // Create toko
            $toko = new Toko();
            $toko->{Toko::FIELD_TOKO_ID} = $data[Toko::FIELD_TOKO_ID];
            $toko->{Toko::FIELD_NAMA_TOKO} = $data[Toko::FIELD_NAMA_TOKO];
            $toko->{Toko::FIELD_PEMILIK} = $data[Toko::FIELD_PEMILIK];
            $toko->{Toko::FIELD_ALAMAT} = $data[Toko::FIELD_ALAMAT];
            $toko->{Toko::FIELD_WILAYAH_KECAMATAN} = $data[Toko::FIELD_WILAYAH_KECAMATAN];
            $toko->{Toko::FIELD_WILAYAH_KELURAHAN} = $data[Toko::FIELD_WILAYAH_KELURAHAN];
            $toko->{Toko::FIELD_WILAYAH_KOTA_KABUPATEN} = $data[Toko::FIELD_WILAYAH_KOTA_KABUPATEN];
            $toko->{Toko::FIELD_NOMER_TELPON} = $data[Toko::FIELD_NOMER_TELPON];
            $toko->{Toko::FIELD_IS_ACTIVE} = true;

            // Set coordinates from interactive map
            $toko->{Toko::FIELD_LATITUDE} = $latitude;
            $toko->{Toko::FIELD_LONGITUDE} = $longitude;

            // Set geocoding metadata
            self::setInteractiveMapMetadata($toko);

            // Optional: Reverse geocoding
            $reverseResult = GeocodingService::reverseGeocode($latitude, $longitude);
            if ($reverseResult) {
                $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING} = $reverseResult['formatted_address'];
            }

            $toko->save();

            Log::info("Toko berhasil disimpan dengan koordinat interaktif: {$toko->{Toko::FIELD_TOKO_ID}} - ({$latitude}, {$longitude})");

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Data toko berhasil ditambahkan dengan lokasi presisi tinggi dari peta interaktif',
                'data' => $toko,
                'coordinate_info' => self::buildCoordinateInfo($latitude, $longitude, false)
            ];

        } catch (\Exception $e) {
            Log::error('Error saving toko with interactive map coordinates: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat menyimpan data toko. Silakan coba lagi.'
            ];
        }
    }

    /**
     * Update toko with interactive map support
     *
     * @param Toko $toko
     * @param array $data
     * @return array
     */
    public static function update(Toko $toko, array $data): array
    {
        try {
            $latitude = (float) $data[Toko::FIELD_LATITUDE];
            $longitude = (float) $data[Toko::FIELD_LONGITUDE];

            // Validate coordinates are in Malang region
            if (!GeocodingService::isInMalangRegion($latitude, $longitude)) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Koordinat yang dipilih berada di luar wilayah Malang Raya. Silakan pilih lokasi yang sesuai.',
                    'coordinate_info' => [
                        'selected_coordinates' => [$latitude, $longitude],
                        'valid_region' => 'Malang Raya (Kota Malang, Kabupaten Malang, Kota Batu)'
                    ]
                ];
            }

            // Check if coordinates changed
            $coordinatesChanged = (
                $toko->{Toko::FIELD_LATITUDE} != $latitude ||
                $toko->{Toko::FIELD_LONGITUDE} != $longitude
            );

            // Update toko data
            $toko->{Toko::FIELD_NAMA_TOKO} = $data[Toko::FIELD_NAMA_TOKO];
            $toko->{Toko::FIELD_PEMILIK} = $data[Toko::FIELD_PEMILIK];
            $toko->{Toko::FIELD_ALAMAT} = $data[Toko::FIELD_ALAMAT];
            $toko->{Toko::FIELD_WILAYAH_KECAMATAN} = $data[Toko::FIELD_WILAYAH_KECAMATAN];
            $toko->{Toko::FIELD_WILAYAH_KELURAHAN} = $data[Toko::FIELD_WILAYAH_KELURAHAN];
            $toko->{Toko::FIELD_WILAYAH_KOTA_KABUPATEN} = $data[Toko::FIELD_WILAYAH_KOTA_KABUPATEN];
            $toko->{Toko::FIELD_NOMER_TELPON} = $data[Toko::FIELD_NOMER_TELPON];

            // Update coordinates
            $toko->{Toko::FIELD_LATITUDE} = $latitude;
            $toko->{Toko::FIELD_LONGITUDE} = $longitude;

            // Update geocoding metadata if coordinates changed
            if ($coordinatesChanged) {
                self::setInteractiveMapMetadata($toko);

                // Optional: Reverse geocoding
                $reverseResult = GeocodingService::reverseGeocode($latitude, $longitude);
                if ($reverseResult) {
                    $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING} = $reverseResult['formatted_address'];
                }

                Log::info("Koordinat toko {$toko->{Toko::FIELD_TOKO_ID}} diperbarui melalui interactive map: ({$latitude}, {$longitude})");
            }

            $toko->save();

            $responseMessage = 'Data toko berhasil diperbarui';
            if ($coordinatesChanged) {
                $responseMessage .= ' dengan koordinat presisi tinggi dari peta interaktif';
            }

            return [
                'success' => true,
                'status_code' => 200,
                'message' => $responseMessage,
                'data' => $toko,
                'coordinate_info' => self::buildCoordinateInfo($latitude, $longitude, $coordinatesChanged)
            ];

        } catch (\Exception $e) {
            Log::error('Error updating toko with interactive map: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat memperbarui data toko'
            ];
        }
    }

    /**
     * Delete toko
     *
     * @param Toko $toko
     * @return array
     */
    public static function destroy(Toko $toko): array
    {
        try {
            $toko->delete();

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Data toko berhasil dihapus'
            ];

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                return [
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Data toko tidak dapat dihapus karena masih digunakan dalam transaksi'
                ];
            }

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat menghapus data toko'
            ];
        }
    }

    /**
     * Preview geocoding for address
     *
     * @param string $address
     * @return array
     */
    public static function previewGeocode(string $address): array
    {
        try {
            Log::info("Preview geocoding for: {$address}");

            $geocodeResult = GeocodingService::geocodeAddress($address);

            if ($geocodeResult) {
                $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);

                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Koordinat berhasil ditemukan',
                    'geocode_info' => [
                        'latitude' => $geocodeResult['latitude'],
                        'longitude' => $geocodeResult['longitude'],
                        'formatted_address' => $geocodeResult['formatted_address'],
                        'provider' => $geocodeResult['provider'],
                        'accuracy' => $geocodeResult['accuracy'],
                        'confidence' => $geocodeResult['confidence'] ?? 0,
                        'quality' => $qualityCheck['quality'],
                        'quality_score' => $qualityCheck['score'],
                        'quality_badge' => TokoHelper::getQualityBadge($qualityCheck['quality']),
                        'in_malang_region' => GeocodingService::isInMalangRegion($geocodeResult['latitude'], $geocodeResult['longitude']),
                        'region_status' => GeocodingService::isInMalangRegion($geocodeResult['latitude'], $geocodeResult['longitude'])
                            ? '✓ Dalam wilayah Malang'
                            : '⚠ Di luar wilayah Malang',
                        'recommendations' => $qualityCheck['recommendations'] ?? []
                    ]
                ];
            }

            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Koordinat tidak dapat ditemukan untuk alamat tersebut'
            ];

        } catch (\Exception $e) {
            Log::error('Error preview geocoding: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat melakukan preview geocoding'
            ];
        }
    }

    /**
     * Geocode existing toko
     *
     * @param Toko $toko
     * @param string $address
     * @return array
     */
    public static function geocodeToko(Toko $toko, string $address): array
    {
        try {
            Log::info("Manual geocoding for toko {$toko->{Toko::FIELD_TOKO_ID}}: {$address}");

            $geocodeResult = GeocodingService::geocodeAddress($address);

            if ($geocodeResult) {
                $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);

                // Update toko coordinates
                $toko->{Toko::FIELD_LATITUDE} = $geocodeResult['latitude'];
                $toko->{Toko::FIELD_LONGITUDE} = $geocodeResult['longitude'];
                $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING} = $geocodeResult['formatted_address'];
                $toko->{Toko::FIELD_GEOCODING_PROVIDER} = $geocodeResult['provider'];
                $toko->{Toko::FIELD_GEOCODING_ACCURACY} = $geocodeResult['accuracy'];
                $toko->{Toko::FIELD_GEOCODING_CONFIDENCE} = $geocodeResult['confidence'] ?? null;
                $toko->{Toko::FIELD_GEOCODING_QUALITY} = $qualityCheck['quality'];
                $toko->{Toko::FIELD_GEOCODING_SCORE} = $qualityCheck['score'];
                $toko->save();

                $message = "Koordinat toko berhasil diperbarui";
                if ($qualityCheck['quality'] === 'excellent' || $qualityCheck['quality'] === 'good') {
                    $message .= " dengan akurasi tinggi";
                } elseif (in_array($qualityCheck['quality'], ['fair', 'poor'])) {
                    $message .= " (perlu verifikasi manual)";
                }

                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => $message,
                    'data' => [
                        'toko_id' => $toko->{Toko::FIELD_TOKO_ID},
                        'nama_toko' => $toko->{Toko::FIELD_NAMA_TOKO},
                        'latitude' => $toko->{Toko::FIELD_LATITUDE},
                        'longitude' => $toko->{Toko::FIELD_LONGITUDE},
                        'formatted_address' => $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING},
                        'provider' => $toko->{Toko::FIELD_GEOCODING_PROVIDER},
                        'accuracy' => $toko->{Toko::FIELD_GEOCODING_ACCURACY},
                        'quality' => $toko->{Toko::FIELD_GEOCODING_QUALITY},
                        'quality_score' => $toko->{Toko::FIELD_GEOCODING_SCORE}
                    ],
                    'geocode_info' => $geocodeResult,
                    'quality_check' => $qualityCheck
                ];
            }

            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Tidak dapat menemukan koordinat untuk alamat tersebut'
            ];

        } catch (\Exception $e) {
            Log::error('Error geocoding toko: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat melakukan geocoding'
            ];
        }
    }

    /**
     * Batch geocoding for tokos without coordinates or low quality
     *
     * @return array
     */
    public static function batchGeocodeToko(): array
    {
        try {
            $tokosToGeocode = Toko::needsRegeocoding()->get();

            if ($tokosToGeocode->isEmpty()) {
                return [
                    'success' => true,
                    'status_code' => 200,
                    'status' => 'info',
                    'message' => 'Semua toko sudah memiliki koordinat GPS berkualitas baik',
                    'summary' => [
                        'total_processed' => 0,
                        'success_count' => 0,
                        'failed_count' => 0,
                        'improved_count' => 0
                    ]
                ];
            }

            Log::info("Starting batch geocoding for {$tokosToGeocode->count()} tokos");

            $successCount = 0;
            $failedCount = 0;
            $improvedCount = 0;
            $results = [];

            foreach ($tokosToGeocode as $toko) {
                $result = self::processBatchGeocodeItem($toko);
                $results[] = $result;

                if ($result['status'] === 'success') {
                    $successCount++;
                    if ($result['improved']) {
                        $improvedCount++;
                    }
                } else {
                    $failedCount++;
                }

                // Delay to avoid rate limiting
                usleep(600000); // 0.6 second delay
            }

            Log::info("Batch geocoding completed. Success: {$successCount}, Failed: {$failedCount}, Improved: {$improvedCount}");

            return [
                'success' => true,
                'status_code' => 200,
                'message' => "Batch geocoding selesai. Berhasil: {$successCount}, Gagal: {$failedCount}, Ditingkatkan: {$improvedCount}",
                'summary' => [
                    'total_processed' => count($tokosToGeocode),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'improved_count' => $improvedCount,
                    'success_rate' => round(($successCount / count($tokosToGeocode)) * 100, 1)
                ],
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Error batch geocoding: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat melakukan batch geocoding'
            ];
        }
    }

    /**
     * Validate map coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public static function validateMapCoordinates(float $latitude, float $longitude): array
    {
        try {
            $inMalangRegion = GeocodingService::isInMalangRegion($latitude, $longitude);
            $inIndonesia = GeocodingService::isInIndonesia($latitude, $longitude);

            // Reverse geocoding
            $reverseResult = GeocodingService::reverseGeocode($latitude, $longitude);

            return [
                'success' => true,
                'status_code' => 200,
                'validation' => [
                    'coordinates_valid' => true,
                    'in_indonesia' => $inIndonesia,
                    'in_malang_region' => $inMalangRegion,
                    'coordinates' => [$latitude, $longitude]
                ],
                'address_info' => $reverseResult,
                'message' => $inMalangRegion
                    ? 'Lokasi valid dalam wilayah Malang Raya'
                    : 'Peringatan: Lokasi berada di luar wilayah Malang Raya'
            ];

        } catch (\Exception $e) {
            Log::error('Error validating map coordinates: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat validasi koordinat'
            ];
        }
    }

    /**
     * Get coordinate details for a toko
     *
     * @param Toko $toko
     * @return array
     */
    public static function getCoordinateDetails(Toko $toko): array
    {
        try {
            $details = [
                'toko_info' => [
                    'toko_id' => $toko->{Toko::FIELD_TOKO_ID},
                    'nama_toko' => $toko->{Toko::FIELD_NAMA_TOKO},
                    'alamat' => $toko->{Toko::FIELD_ALAMAT},
                    'wilayah' => $toko->{Toko::FIELD_WILAYAH_KELURAHAN} . ', ' . $toko->{Toko::FIELD_WILAYAH_KECAMATAN} . ', ' . $toko->{Toko::FIELD_WILAYAH_KOTA_KABUPATEN}
                ],
                'coordinates' => [
                    'latitude' => $toko->{Toko::FIELD_LATITUDE},
                    'longitude' => $toko->{Toko::FIELD_LONGITUDE},
                    'has_coordinates' => $toko->{Toko::FIELD_LATITUDE} && $toko->{Toko::FIELD_LONGITUDE}
                ],
                'geocoding_info' => [
                    'provider' => $toko->{Toko::FIELD_GEOCODING_PROVIDER} ?? 'unknown',
                    'accuracy' => $toko->{Toko::FIELD_GEOCODING_ACCURACY} ?? 'unknown',
                    'quality' => $toko->{Toko::FIELD_GEOCODING_QUALITY} ?? 'unknown',
                    'quality_score' => $toko->{Toko::FIELD_GEOCODING_SCORE} ?? 0,
                    'confidence' => $toko->{Toko::FIELD_GEOCODING_CONFIDENCE} ?? 0,
                    'formatted_address' => $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING}
                ],
                'validation' => []
            ];

            if ($toko->{Toko::FIELD_LATITUDE} && $toko->{Toko::FIELD_LONGITUDE}) {
                $details['validation'] = [
                    'in_indonesia' => GeocodingService::isInIndonesia($toko->{Toko::FIELD_LATITUDE}, $toko->{Toko::FIELD_LONGITUDE}),
                    'in_malang_region' => GeocodingService::isInMalangRegion($toko->{Toko::FIELD_LATITUDE}, $toko->{Toko::FIELD_LONGITUDE}),
                    'coordinates_valid' => abs($toko->{Toko::FIELD_LATITUDE}) <= 90 && abs($toko->{Toko::FIELD_LONGITUDE}) <= 180,
                    'google_maps_url' => "https://www.google.com/maps?q={$toko->{Toko::FIELD_LATITUDE}},{$toko->{Toko::FIELD_LONGITUDE}}",
                    'distance_from_malang_center' => TokoHelper::calculateDistance($toko->{Toko::FIELD_LATITUDE}, $toko->{Toko::FIELD_LONGITUDE}, -7.9666, 112.6326)
                ];
            }

            return [
                'success' => true,
                'status_code' => 200,
                'data' => $details
            ];

        } catch (\Exception $e) {
            Log::error('Error getting coordinate details: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat mengambil detail koordinat'
            ];
        }
    }

    /**
     * Validate all coordinates
     *
     * @return array
     */
    public static function validateAllCoordinates(): array
    {
        try {
            $tokos = Toko::withCoordinates()->get();
            $results = [
                'total_checked' => $tokos->count(),
                'valid_coordinates' => 0,
                'in_malang_region' => 0,
                'outside_indonesia' => 0,
                'issues' => []
            ];

            foreach ($tokos as $toko) {
                $issues = [];

                // Check coordinate validity
                if (abs($toko->{Toko::FIELD_LATITUDE}) > 90 || abs($toko->{Toko::FIELD_LONGITUDE}) > 180) {
                    $issues[] = 'Invalid coordinate range';
                } else {
                    $results['valid_coordinates']++;
                }

                // Check if in Indonesia
                if (!GeocodingService::isInIndonesia($toko->{Toko::FIELD_LATITUDE}, $toko->{Toko::FIELD_LONGITUDE})) {
                    $issues[] = 'Outside Indonesia';
                    $results['outside_indonesia']++;
                }

                // Check if in Malang region
                if (GeocodingService::isInMalangRegion($toko->{Toko::FIELD_LATITUDE}, $toko->{Toko::FIELD_LONGITUDE})) {
                    $results['in_malang_region']++;
                } else {
                    $issues[] = 'Outside Malang region';
                }

                if (!empty($issues)) {
                    $results['issues'][] = [
                        'toko_id' => $toko->{Toko::FIELD_TOKO_ID},
                        'nama_toko' => $toko->{Toko::FIELD_NAMA_TOKO},
                        'coordinates' => [$toko->{Toko::FIELD_LATITUDE}, $toko->{Toko::FIELD_LONGITUDE}],
                        'issues' => $issues
                    ];
                }
            }

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Validasi koordinat selesai',
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Error validating coordinates: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat validasi koordinat'
            ];
        }
    }

    /**
     * Get geocoding statistics
     *
     * @return array
     */
    public static function getGeocodingStats(): array
    {
        try {
            $serviceStats = GeocodingService::getGeocodingStats();
            $tokoStats = self::buildTokoStats();

            return [
                'success' => true,
                'status_code' => 200,
                'service_stats' => $serviceStats,
                'toko_stats' => $tokoStats,
                'recommendations' => self::getGeocodingRecommendations($tokoStats)
            ];

        } catch (\Exception $e) {
            Log::error('Error getting geocoding stats: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat mengambil statistik geocoding'
            ];
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    /**
     * Set interactive map metadata for toko
     *
     * @param Toko $toko
     * @return void
     */
    private static function setInteractiveMapMetadata(Toko $toko): void
    {
        $toko->{Toko::FIELD_GEOCODING_PROVIDER} = 'interactive_map';
        $toko->{Toko::FIELD_GEOCODING_ACCURACY} = 'very high';
        $toko->{Toko::FIELD_GEOCODING_CONFIDENCE} = 1.0;
        $toko->{Toko::FIELD_GEOCODING_QUALITY} = 'excellent';
        $toko->{Toko::FIELD_GEOCODING_SCORE} = 100;
    }

    /**
     * Build coordinate info response
     *
     * @param float $latitude
     * @param float $longitude
     * @param bool $coordinatesChanged
     * @return array
     */
    private static function buildCoordinateInfo(float $latitude, float $longitude, bool $coordinatesChanged): array
    {
        return [
            'source' => 'Interactive Map Selection',
            'accuracy' => 'Very High (User Selected)',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'coordinates_changed' => $coordinatesChanged,
            'in_malang_region' => true,
            'quality' => 'Excellent',
            'score' => 100
        ];
    }

    /**
     * Process single batch geocode item
     *
     * @param Toko $toko
     * @return array
     */
    private static function processBatchGeocodeItem(Toko $toko): array
    {
        $fullAddress = trim($toko->{Toko::FIELD_ALAMAT} . ', ' .
                      $toko->{Toko::FIELD_WILAYAH_KELURAHAN} . ', ' .
                      $toko->{Toko::FIELD_WILAYAH_KECAMATAN} . ', ' .
                      $toko->{Toko::FIELD_WILAYAH_KOTA_KABUPATEN} . ', Jawa Timur, Indonesia');

        Log::info("Batch geocoding: {$toko->{Toko::FIELD_TOKO_ID}} - {$toko->{Toko::FIELD_NAMA_TOKO}}");

        $oldQuality = $toko->{Toko::FIELD_GEOCODING_QUALITY};
        $geocodeResult = GeocodingService::geocodeAddress($fullAddress);

        if ($geocodeResult) {
            $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);

            $toko->{Toko::FIELD_LATITUDE} = $geocodeResult['latitude'];
            $toko->{Toko::FIELD_LONGITUDE} = $geocodeResult['longitude'];
            $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING} = $geocodeResult['formatted_address'];
            $toko->{Toko::FIELD_GEOCODING_PROVIDER} = $geocodeResult['provider'];
            $toko->{Toko::FIELD_GEOCODING_ACCURACY} = $geocodeResult['accuracy'];
            $toko->{Toko::FIELD_GEOCODING_CONFIDENCE} = $geocodeResult['confidence'] ?? null;
            $toko->{Toko::FIELD_GEOCODING_QUALITY} = $qualityCheck['quality'];
            $toko->{Toko::FIELD_GEOCODING_SCORE} = $qualityCheck['score'];
            $toko->save();

            return [
                'toko_id' => $toko->{Toko::FIELD_TOKO_ID},
                'nama_toko' => $toko->{Toko::FIELD_NAMA_TOKO},
                'status' => 'success',
                'coordinates' => [$geocodeResult['latitude'], $geocodeResult['longitude']],
                'provider' => $geocodeResult['provider'],
                'quality' => $qualityCheck['quality'],
                'quality_score' => $qualityCheck['score'],
                'old_quality' => $oldQuality,
                'improved' => $oldQuality && $qualityCheck['quality'] !== $oldQuality
            ];
        }

        return [
            'toko_id' => $toko->{Toko::FIELD_TOKO_ID},
            'nama_toko' => $toko->{Toko::FIELD_NAMA_TOKO},
            'status' => 'failed',
            'coordinates' => null,
            'old_quality' => $oldQuality,
            'improved' => false
        ];
    }

    /**
     * Build toko statistics
     *
     * @return array
     */
    private static function buildTokoStats(): array
    {
        return [
            'total_toko' => Toko::count(),
            'with_coordinates' => Toko::withCoordinates()->count(),
            'without_coordinates' => Toko::whereNull(Toko::FIELD_LATITUDE)->orWhereNull(Toko::FIELD_LONGITUDE)->count(),
            'quality_distribution' => [
                'excellent' => Toko::where(Toko::FIELD_GEOCODING_QUALITY, 'excellent')->count(),
                'good' => Toko::where(Toko::FIELD_GEOCODING_QUALITY, 'good')->count(),
                'fair' => Toko::where(Toko::FIELD_GEOCODING_QUALITY, 'fair')->count(),
                'poor' => Toko::where(Toko::FIELD_GEOCODING_QUALITY, 'poor')->count(),
                'very_poor' => Toko::where(Toko::FIELD_GEOCODING_QUALITY, 'very poor')->count(),
                'failed' => Toko::where(Toko::FIELD_GEOCODING_QUALITY, 'failed')->orWhereNull(Toko::FIELD_GEOCODING_QUALITY)->count()
            ],
            'provider_distribution' => [
                'internal_database' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'internal_database')->count(),
                'google_maps' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'google_maps')->count(),
                'locationiq' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'locationiq')->count(),
                'opencage' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'opencage')->count(),
                'mapbox' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'mapbox')->count(),
                'here' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'here')->count(),
                'nominatim' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'nominatim')->count(),
                'fallback' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'LIKE', '%fallback%')->count(),
                'interactive_map' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'interactive_map')->count(),
                'failed' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'failed')->orWhereNull(Toko::FIELD_GEOCODING_PROVIDER)->count()
            ],
            'average_quality_score' => round(Toko::whereNotNull(Toko::FIELD_GEOCODING_SCORE)->avg(Toko::FIELD_GEOCODING_SCORE) ?? 0, 1),
            'in_malang_region' => Toko::inMalangRegion()->count()
        ];
    }

    /**
     * Get geocoding recommendations based on stats
     *
     * @param array $stats
     * @return array
     */
    private static function getGeocodingRecommendations(array $stats): array
    {
        $recommendations = [];

        if ($stats['without_coordinates'] > 0) {
            $recommendations[] = "Ada {$stats['without_coordinates']} toko tanpa koordinat GPS. Jalankan Batch Geocoding untuk mendapatkan koordinat.";
        }

        $lowQualityCount = $stats['quality_distribution']['poor'] + $stats['quality_distribution']['very_poor'] + $stats['quality_distribution']['failed'];
        if ($lowQualityCount > 0) {
            $recommendations[] = "Ada {$lowQualityCount} toko dengan kualitas geocoding rendah. Pertimbangkan untuk melakukan geocoding ulang.";
        }

        if ($stats['provider_distribution']['failed'] > $stats['total_toko'] * 0.1) {
            $recommendations[] = "Tingkat kegagalan geocoding tinggi. Periksa konfigurasi API geocoding.";
        }

        $outsideMalang = $stats['total_toko'] - $stats['in_malang_region'];
        if ($outsideMalang > 0) {
            $recommendations[] = "Ada {$outsideMalang} toko dengan koordinat di luar wilayah Malang. Periksa keakuratan alamat.";
        }

        if (empty($recommendations)) {
            $recommendations[] = "Semua toko memiliki koordinat GPS dengan kualitas baik!";
        }

        return $recommendations;
    }
}

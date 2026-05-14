<?php

namespace App\Services;

use App\Models\Toko;
use App\Helpers\MasterData\Toko\TokoHelper;
use Illuminate\Support\Facades\Log;

class TokoService
{

    public static function store(array $data): array
    {
        try {
            if (empty($data[Toko::FIELD_LATITUDE]) || empty($data[Toko::FIELD_LONGITUDE])) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Koordinat GPS wajib diisi. Silakan pilih lokasi pada peta.'
                ];
            }

            $latitude = (float) $data[Toko::FIELD_LATITUDE];
            $longitude = (float) $data[Toko::FIELD_LONGITUDE];

            if (abs($latitude) > 90 || abs($longitude) > 180) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Koordinat tidak valid. Latitude harus antara -90 sampai 90, Longitude harus antara -180 sampai 180.'
                ];
            }

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
            $toko->{Toko::FIELD_LATITUDE} = $latitude;
            $toko->{Toko::FIELD_LONGITUDE} = $longitude;

            self::setInteractiveMapMetadata($toko);

            try {
                $reverseResult = GeocodingService::reverseGeocode($latitude, $longitude);
                if ($reverseResult && isset($reverseResult['formatted_address'])) {
                    $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING} = $reverseResult['formatted_address'];
                }
            } catch (\Exception $e) {

            }

            $toko->save();

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Data toko berhasil ditambahkan dengan lokasi presisi tinggi dari peta interaktif',
                'data' => $toko,
                'coordinate_info' => self::buildCoordinateInfo($latitude, $longitude, false)
            ];

        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'ID Toko sudah digunakan. Silakan generate ID baru.'
                ];
            }
            
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan database. Silakan coba lagi.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error saving toko: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat menyimpan data toko. Silakan coba lagi.'
            ];
        }
    }

    public static function update(Toko $toko, array $data): array
    {
        try {
            $latitude = (float) $data[Toko::FIELD_LATITUDE];
            $longitude = (float) $data[Toko::FIELD_LONGITUDE];

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

            $coordinatesChanged = (
                $toko->{Toko::FIELD_LATITUDE} != $latitude ||
                $toko->{Toko::FIELD_LONGITUDE} != $longitude
            );

            $toko->{Toko::FIELD_NAMA_TOKO} = $data[Toko::FIELD_NAMA_TOKO];
            $toko->{Toko::FIELD_PEMILIK} = $data[Toko::FIELD_PEMILIK];
            $toko->{Toko::FIELD_ALAMAT} = $data[Toko::FIELD_ALAMAT];
            $toko->{Toko::FIELD_WILAYAH_KECAMATAN} = $data[Toko::FIELD_WILAYAH_KECAMATAN];
            $toko->{Toko::FIELD_WILAYAH_KELURAHAN} = $data[Toko::FIELD_WILAYAH_KELURAHAN];
            $toko->{Toko::FIELD_WILAYAH_KOTA_KABUPATEN} = $data[Toko::FIELD_WILAYAH_KOTA_KABUPATEN];
            $toko->{Toko::FIELD_NOMER_TELPON} = $data[Toko::FIELD_NOMER_TELPON];
            $toko->{Toko::FIELD_LATITUDE} = $latitude;
            $toko->{Toko::FIELD_LONGITUDE} = $longitude;

            if ($coordinatesChanged) {
                self::setInteractiveMapMetadata($toko);

                try {
                    $reverseResult = GeocodingService::reverseGeocode($latitude, $longitude);
                    if ($reverseResult && isset($reverseResult['formatted_address'])) {
                        $toko->{Toko::FIELD_ALAMAT_LENGKAP_GEOCODING} = $reverseResult['formatted_address'];
                    }
                } catch (\Exception $e) {
                }
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
            Log::error('Error updating toko: ' . $e->getMessage());

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Terjadi kesalahan saat memperbarui data toko'
            ];
        }
    }

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

    public static function validateMapCoordinates(float $latitude, float $longitude): array
    {
        try {
            $inMalangRegion = GeocodingService::isInMalangRegion($latitude, $longitude);
            $inIndonesia = GeocodingService::isInIndonesia($latitude, $longitude);

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

    public static function getCoordinateDetails(Toko $toko): array
    {
        try {
            $details = [
                'toko_info' => [
                    'toko_id' => $toko->{Toko::FIELD_TOKO_ID},
                    'nama_toko' => $toko->{Toko::FIELD_NAMA_TOKO},
                    'pemilik' => $toko->{Toko::FIELD_PEMILIK},
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

                if (abs($toko->{Toko::FIELD_LATITUDE}) > 90 || abs($toko->{Toko::FIELD_LONGITUDE}) > 180) {
                    $issues[] = 'Invalid coordinate range';
                } else {
                    $results['valid_coordinates']++;
                }

                if (!GeocodingService::isInIndonesia($toko->{Toko::FIELD_LATITUDE}, $toko->{Toko::FIELD_LONGITUDE})) {
                    $issues[] = 'Outside Indonesia';
                    $results['outside_indonesia']++;
                }

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

    public static function getGeocodingStats(): array
    {
        try {
            $serviceStats = GeocodingService::getGeocodingStats();
            $tokoStats = TokoCacheService::getGeocodingStats();

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

    private static function setInteractiveMapMetadata(Toko $toko): void
    {
        $toko->{Toko::FIELD_GEOCODING_PROVIDER} = 'interactive_map';
        $toko->{Toko::FIELD_GEOCODING_ACCURACY} = 'very high';
        $toko->{Toko::FIELD_GEOCODING_CONFIDENCE} = 1.0;
        $toko->{Toko::FIELD_GEOCODING_QUALITY} = 'excellent';
        $toko->{Toko::FIELD_GEOCODING_SCORE} = 100;
    }

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
                'nominatim' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'nominatim')->count(),
                'interactive_map' => Toko::where(Toko::FIELD_GEOCODING_PROVIDER, 'interactive_map')->count(),
                'unknown' => Toko::whereNull(Toko::FIELD_GEOCODING_PROVIDER)->count()
            ],
            'average_quality_score' => round(Toko::whereNotNull(Toko::FIELD_GEOCODING_SCORE)->avg(Toko::FIELD_GEOCODING_SCORE) ?? 0, 1),
            'in_malang_region' => Toko::inMalangRegion()->count()
        ];
    }

    private static function getGeocodingRecommendations(array $stats): array
    {
        $recommendations = [];

        if ($stats['without_coordinates'] > 0) {
            $recommendations[] = "Ada {$stats['without_coordinates']} toko tanpa koordinat GPS. Gunakan interactive map untuk menambahkan koordinat.";
        }

        $lowQualityCount = $stats['quality_distribution']['poor'] + $stats['quality_distribution']['very_poor'] + $stats['quality_distribution']['failed'];
        if ($lowQualityCount > 0) {
            $recommendations[] = "Ada {$lowQualityCount} toko dengan kualitas geocoding rendah. Pertimbangkan untuk memperbarui koordinat menggunakan interactive map.";
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

<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeocodingService; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\AnalyticsController;

class MarketMapController extends Controller
{
    public function index()
    {
        return view('market.map', [
            'activemenu' => 'market-map',
            'breadcrumb' => (object) [
                'title' => 'Market Map',
                'list' => ['Home', 'Market Map']
            ]
        ]);
    }

    public function getTokoData()
    {
        try {
            // Gunakan Eloquent Model dengan proper relationships
            $tokoData = Toko::with(['barangToko', 'pengiriman', 'retur'])
                ->select([
                    'toko_id',
                    'nama_toko',
                    'pemilik', 
                    'alamat',
                    'wilayah_kelurahan',
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    'nomer_telpon',
                    'latitude',
                    'longitude',
                    'is_active',
                    'alamat_lengkap_geocoding'
                ])
                ->get();

            // Transform data untuk peta
            $mapData = $tokoData->map(function ($toko) {
                // Gunakan koordinat asli dari database, bukan dummy
                $coordinates = $this->getTokoCoordinates($toko);
                
                return [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'pemilik' => $toko->pemilik,
                    'alamat' => $toko->alamat,
                    'kelurahan' => $toko->wilayah_kelurahan,
                    'kecamatan' => $toko->wilayah_kecamatan,
                    'kota_kabupaten' => $toko->wilayah_kota_kabupaten,
                    'telpon' => $toko->nomer_telpon,
                    'latitude' => $coordinates['lat'],
                    'longitude' => $coordinates['lng'],
                    'jumlah_barang' => $toko->barangToko->count(),
                    'total_pengiriman' => $toko->pengiriman->count(),
                    'total_retur' => $toko->retur->count(),
                    'status_aktif' => $this->getTokoStatus($toko),
                    'has_coordinates' => $coordinates['has_real_coordinates'],
                    'coordinate_source' => $coordinates['source']
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $mapData,
                'summary' => [
                    'total_toko' => $mapData->count(),
                    'toko_with_coordinates' => $mapData->where('has_coordinates', true)->count(),
                    'toko_active' => $mapData->where('status_aktif', 'Aktif')->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching toko data for map: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

        /**
     * Dapatkan koordinat toko dengan fallback system
     */
    private function getTokoCoordinates($toko)
    {
        // Prioritas 1: Gunakan koordinat asli dari geocoding
        if ($toko->latitude && $toko->longitude) {
            return [
                'lat' => (float) $toko->latitude,
                'lng' => (float) $toko->longitude,
                'has_real_coordinates' => true,
                'source' => 'geocoded'
            ];
        }

        // Prioritas 2: Generate koordinat berdasarkan wilayah sebagai fallback
        $fallbackCoords = $this->generateFallbackCoordinates(
            $toko->wilayah_kota_kabupaten,
            $toko->wilayah_kecamatan,
            $toko->wilayah_kelurahan
        );

        return [
            'lat' => $fallbackCoords['lat'],
            'lng' => $fallbackCoords['lng'],
            'has_real_coordinates' => false,
            'source' => 'estimated'
        ];
    }

        /**
     * Tentukan status toko berdasarkan berbagai kriteria
     */
    private function getTokoStatus($toko)
    {
        if (!$toko->is_active) {
            return 'Tidak Aktif';
        }

        // Toko aktif jika memiliki barang atau ada pengiriman dalam 30 hari terakhir
        $hasBarang = $toko->barangToko->count() > 0;
        $hasRecentShipment = $toko->pengiriman()
            ->where('tanggal_pengiriman', '>=', now()->subDays(30))
            ->count() > 0;

        return ($hasBarang || $hasRecentShipment) ? 'Aktif' : 'Tidak Aktif';
    }

    /**
     * Perbaikan method untuk koordinat fallback
     */
    private function generateFallbackCoordinates($kotaKabupaten, $kecamatan, $kelurahan)
    {
        // Base coordinates yang lebih akurat untuk wilayah Malang
        $baseCoordinates = [
            'Kota Malang' => ['lat' => -7.9666, 'lng' => 112.6326],
            'Kabupaten Malang' => ['lat' => -8.1844, 'lng' => 112.7456],
            'Kota Batu' => ['lat' => -7.8767, 'lng' => 112.5326]
        ];

        // Ambil base coordinate berdasarkan kota/kabupaten
        $base = $baseCoordinates[$kotaKabupaten] ?? $baseCoordinates['Kota Malang'];

        // Generate offset yang lebih realistic berdasarkan hash nama wilayah
        $hashKecamatan = crc32($kecamatan);
        $hashKelurahan = crc32($kelurahan);
        
        // Offset dalam radius yang lebih kecil (sekitar 2-3km)
        $offsetLat = (($hashKecamatan % 500) / 20000) - 0.0125; // ±0.0125 derajat
        $offsetLng = (($hashKelurahan % 500) / 20000) - 0.0125; // ±0.0125 derajat

        return [
            'lat' => $base['lat'] + $offsetLat,
            'lng' => $base['lng'] + $offsetLng
        ];
    }




    public function getWilayahStatistics()
    {
        try {
            // Statistik per kecamatan dengan join yang lebih efisien
            $statistikKecamatan = Toko::select([
                'wilayah_kecamatan',
                'wilayah_kota_kabupaten',
                DB::raw('COUNT(*) as jumlah_toko'),
                DB::raw('COUNT(CASE WHEN is_active = 1 THEN 1 END) as toko_aktif'),
                DB::raw('COUNT(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 END) as toko_with_coordinates')
            ])
            ->groupBy('wilayah_kecamatan', 'wilayah_kota_kabupaten')
            ->orderBy('jumlah_toko', 'desc')
            ->get();

            // Statistik per kelurahan
            $statistikKelurahan = Toko::select([
                'wilayah_kelurahan',
                'wilayah_kecamatan', 
                'wilayah_kota_kabupaten',
                DB::raw('COUNT(*) as jumlah_toko'),
                DB::raw('COUNT(CASE WHEN is_active = 1 THEN 1 END) as toko_aktif')
            ])
            ->groupBy('wilayah_kelurahan', 'wilayah_kecamatan', 'wilayah_kota_kabupaten')
            ->orderBy('jumlah_toko', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'kecamatan' => $statistikKecamatan,
                    'kelurahan' => $statistikKelurahan,
                    'summary' => [
                        'total_kecamatan' => $statistikKecamatan->count(),
                        'total_kelurahan' => $statistikKelurahan->count(),
                        'avg_toko_per_kecamatan' => round($statistikKecamatan->avg('jumlah_toko'), 2)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching wilayah statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTokoBarang($tokoId)
    {
        try {
            $barangToko = DB::table('barang_toko')
                ->join('barang', 'barang_toko.barang_id', '=', 'barang.barang_id')
                ->join('toko', 'barang_toko.toko_id', '=', 'toko.toko_id')
                ->where('barang_toko.toko_id', $tokoId)
                ->select([
                    'barang.barang_id',
                    'barang.nama_barang',
                    'barang.barang_kode',
                    'barang.harga_awal_barang',
                    'barang_toko.harga_barang_toko',
                    'barang.satuan',
                    'barang.keterangan',
                    'toko.nama_toko'
                ])
                ->get();

            // Statistik pengiriman untuk toko ini
            $statistikPengiriman = DB::table('pengiriman')
                ->where('toko_id', $tokoId)
                ->select([
                    DB::raw('COUNT(*) as total_pengiriman'),
                    DB::raw('SUM(jumlah_kirim) as total_barang_dikirim'),
                    DB::raw('MAX(tanggal_pengiriman) as pengiriman_terakhir')
                ])
                ->first();

            // Statistik retur untuk toko ini
            $statistikRetur = DB::table('retur')
                ->where('toko_id', $tokoId)
                ->select([
                    DB::raw('COUNT(*) as total_retur'),
                    DB::raw('SUM(jumlah_retur) as total_barang_retur'),
                    DB::raw('SUM(hasil) as total_hasil_retur')
                ])
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'barang' => $barangToko,
                    'statistik_pengiriman' => $statistikPengiriman,
                    'statistik_retur' => $statistikRetur
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWilayahData()
    {
        try {
            // Coba beberapa lokasi file wilayah_malang.json
            $possiblePaths = [
                public_path('data/wilayah_malang.json'),
                resource_path('data/wilayah_malang.json'),
                storage_path('app/data/wilayah_malang.json'),
                base_path('data/wilayah_malang.json')
            ];

            $wilayahData = null;
            $usedPath = null;

            foreach ($possiblePaths as $path) {
                if (File::exists($path)) {
                    $wilayahData = json_decode(File::get($path), true);
                    $usedPath = $path;
                    break;
                }
            }

            if (!$wilayahData) {
                // Fallback data minimal
                $wilayahData = [
                    'wilayah' => [
                        [
                            'id' => 'kotamalang',
                            'nama' => 'Kota Malang',
                            'kecamatan' => []
                        ],
                        [
                            'id' => 'kabmalang', 
                            'nama' => 'Kabupaten Malang',
                            'kecamatan' => []
                        ],
                        [
                            'id' => 'kotabatu',
                            'nama' => 'Kota Batu',
                            'kecamatan' => []
                        ]
                    ]
                ];
                
                Log::warning('Wilayah data file not found, using fallback data');
            }

            return response()->json([
                'success' => true,
                'data' => $wilayahData,
                'meta' => [
                    'source_file' => $usedPath ? basename($usedPath) : 'fallback',
                    'total_wilayah' => count($wilayahData['wilayah'] ?? [])
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading wilayah data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }



    public function getRecommendations()
    {
        try {
            // Analisis wilayah dengan potensi tinggi tapi toko sedikit
            $potensialWilayah = DB::table('toko')
                ->select([
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    DB::raw('COUNT(*) as jumlah_toko')
                ])
                ->groupBy('wilayah_kecamatan', 'wilayah_kota_kabupaten')
                ->having('jumlah_toko', '<', 3) // Wilayah dengan toko sedikit
                ->orderBy('jumlah_toko', 'asc')
                ->limit(10)
                ->get();

            // Barang yang paling banyak dipesan
            $barangPopuler = DB::table('pengiriman')
                ->join('barang', 'pengiriman.barang_id', '=', 'barang.barang_id')
                ->select([
                    'barang.nama_barang',
                    'barang.barang_kode',
                    DB::raw('SUM(pengiriman.jumlah_kirim) as total_dikirim'),
                    DB::raw('COUNT(DISTINCT pengiriman.toko_id) as jumlah_toko')
                ])
                ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.barang_kode')
                ->orderBy('total_dikirim', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'potensial_wilayah' => $potensialWilayah,
                    'barang_populer' => $barangPopuler
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPriceRecommendations()
    {
        try {
            // Analisis harga per wilayah
            $analisisHarga = DB::table('barang_toko')
                ->join('barang', 'barang_toko.barang_id', '=', 'barang.barang_id')
                ->join('toko', 'barang_toko.toko_id', '=', 'toko.toko_id')
                ->select([
                    'barang.nama_barang',
                    'toko.wilayah_kecamatan',
                    'toko.wilayah_kota_kabupaten',
                    DB::raw('AVG(barang_toko.harga_barang_toko) as rata_harga'),
                    DB::raw('MIN(barang_toko.harga_barang_toko) as harga_terendah'),
                    DB::raw('MAX(barang_toko.harga_barang_toko) as harga_tertinggi'),
                    DB::raw('COUNT(*) as jumlah_toko')
                ])
                ->groupBy('barang.barang_id', 'barang.nama_barang', 'toko.wilayah_kecamatan', 'toko.wilayah_kota_kabupaten')
                ->having('jumlah_toko', '>=', 2)
                ->orderBy('rata_harga', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $analisisHarga
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateCoordinatesByWilayah($kotaKabupaten, $kecamatan, $kelurahan)
    {
        // Base coordinates untuk wilayah Malang
        $baseCoordinates = [
            'Kota Malang' => ['lat' => -7.9666, 'lng' => 112.6326],
            'Kabupaten Malang' => ['lat' => -8.1844, 'lng' => 112.7456],
            'Kota Batu' => ['lat' => -7.8767, 'lng' => 112.5326]
        ];

        // Ambil base coordinate
        $base = $baseCoordinates[$kotaKabupaten] ?? ['lat' => -7.9666, 'lng' => 112.6326];

        // Generate offset berdasarkan hash nama kecamatan dan kelurahan
        $hashKecamatan = crc32($kecamatan);
        $hashKelurahan = crc32($kelurahan);
        
        // Offset dalam radius 0.05 derajat (sekitar 5km)
        $offsetLat = (($hashKecamatan % 1000) / 10000) - 0.05;
        $offsetLng = (($hashKelurahan % 1000) / 10000) - 0.05;

        return [
            'lat' => $base['lat'] + $offsetLat,
            'lng' => $base['lng'] + $offsetLng
        ];
    }

    public function storeToko(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama_toko' => 'required|string|max:100',
                'pemilik' => 'required|string|max:100',
                'alamat' => 'required|string',
                'kota_kabupaten' => 'required|string',
                'kecamatan' => 'required|string',
                'kelurahan' => 'required|string',
                'nomer_telpon' => 'required|string|max:20'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate ID toko menggunakan method yang sama seperti TokoController
            $lastToko = Toko::orderBy('toko_id', 'desc')->first();
            
            if (!$lastToko) {
                $newId = 'TKO001';
            } else {
                $lastId = $lastToko->toko_id;
                if (preg_match('/^TKO(\d+)$/', $lastId, $matches)) {
                    $number = intval($matches[1]);
                    $nextNumber = $number + 1;
                    $newId = 'TKO' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                } else {
                    $newId = 'TKO001';
                }
            }

            // Buat alamat lengkap untuk geocoding
            $fullAddress = $request->alamat . ', ' . 
                          $request->kelurahan . ', ' . 
                          $request->kecamatan . ', ' . 
                          $request->kota_kabupaten . ', Indonesia';

            // Lakukan geocoding
            $geocodeResult = GeocodingService::geocodeAddress($fullAddress);

            // Simpan toko dengan atau tanpa koordinat
            $tokoData = [
                'toko_id' => $newId,
                'nama_toko' => $request->nama_toko,
                'pemilik' => $request->pemilik,
                'alamat' => $request->alamat,
                'wilayah_kota_kabupaten' => $request->kota_kabupaten,
                'wilayah_kecamatan' => $request->kecamatan,
                'wilayah_kelurahan' => $request->kelurahan,
                'nomer_telpon' => $request->nomer_telpon,
                'is_active' => true
            ];

            // Tambahkan koordinat jika geocoding berhasil
            if ($geocodeResult) {
                $tokoData['latitude'] = $geocodeResult['latitude'];
                $tokoData['longitude'] = $geocodeResult['longitude'];
                $tokoData['alamat_lengkap_geocoding'] = $geocodeResult['formatted_address'];
            }

            $toko = Toko::create($tokoData);

            $responseMessage = 'Toko berhasil ditambahkan';
            if ($geocodeResult) {
                $responseMessage .= ' dengan koordinat GPS';
            } else {
                $responseMessage .= ' (koordinat akan diestimasi di peta)';
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => [
                    'toko_id' => $newId,
                    'geocoded' => $geocodeResult !== null,
                    'coordinates' => $geocodeResult
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding toko from market map: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
        /**
     * Method untuk bulk geocoding toko yang belum memiliki koordinat
     */
    public function bulkGeocodeTokos()
    {
        try {
            $tokosWithoutCoordinates = Toko::whereNull('latitude')
                                          ->orWhereNull('longitude')
                                          ->limit(10) // Batasi untuk menghindari timeout
                                          ->get();

            $results = [];
            $successCount = 0;

            foreach ($tokosWithoutCoordinates as $toko) {
                $fullAddress = $toko->alamat . ', ' . 
                              $toko->wilayah_kelurahan . ', ' . 
                              $toko->wilayah_kecamatan . ', ' . 
                              $toko->wilayah_kota_kabupaten . ', Indonesia';

                $geocodeResult = GeocodingService::geocodeAddress($fullAddress);
                
                if ($geocodeResult) {
                    $toko->update([
                        'latitude' => $geocodeResult['latitude'],
                        'longitude' => $geocodeResult['longitude'],
                        'alamat_lengkap_geocoding' => $geocodeResult['formatted_address']
                    ]);
                    
                    $successCount++;
                    $results[] = [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko,
                        'status' => 'success',
                        'coordinates' => [$geocodeResult['latitude'], $geocodeResult['longitude']]
                    ];
                } else {
                    $results[] = [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko,
                        'status' => 'failed'
                    ];
                }

                // Delay untuk menghindari rate limiting
                usleep(500000); // 0.5 detik
            }

            return response()->json([
                'success' => true,
                'message' => "Geocoding selesai. Berhasil: {$successCount}/" . count($tokosWithoutCoordinates),
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk geocoding tokos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
/**
 * Enhanced method untuk mendapatkan data toko dengan optimisasi grid heatmap
 */
public function getEnhancedTokoData()
{
    try {
        // Gunakan Eloquent Model dengan proper relationships dan optimisasi untuk grid
        $tokoData = Toko::with(['barangToko', 'pengiriman', 'retur'])
            ->select([
                'toko_id',
                'nama_toko',
                'pemilik', 
                'alamat',
                'wilayah_kelurahan',
                'wilayah_kecamatan',
                'wilayah_kota_kabupaten',
                'nomer_telpon',
                'latitude',
                'longitude',
                'is_active',
                'alamat_lengkap_geocoding',
                'geocoding_quality',
                'geocoding_score'
            ])
            ->get();

        // Transform data untuk grid heatmap
        $mapData = $tokoData->map(function ($toko) {
            // Gunakan koordinat asli dari database
            $coordinates = $this->getTokoCoordinatesEnhanced($toko);
            
            return [
                'toko_id' => $toko->toko_id,
                'nama_toko' => $toko->nama_toko,
                'pemilik' => $toko->pemilik,
                'alamat' => $toko->alamat,
                'kelurahan' => $toko->wilayah_kelurahan,
                'kecamatan' => $toko->wilayah_kecamatan,
                'kota_kabupaten' => $toko->wilayah_kota_kabupaten,
                'telpon' => $toko->nomer_telpon,
                'latitude' => $coordinates['lat'],
                'longitude' => $coordinates['lng'],
                'jumlah_barang' => $toko->barangToko->count(),
                'total_pengiriman' => $toko->pengiriman->count(),
                'total_retur' => $toko->retur->count(),
                'status_aktif' => $this->getEnhancedTokoStatus($toko),
                'has_coordinates' => $coordinates['has_real_coordinates'],
                'coordinate_source' => $coordinates['source'],
                'geocoding_quality' => $toko->geocoding_quality ?? 'unknown',
                'geocoding_score' => $toko->geocoding_score ?? 0,
                // Grid-specific data
                'grid_weight' => $this->calculateGridWeight($toko),
                'density_category' => $this->getDensityCategory($toko)
            ];
        });

        // Generate grid statistics
        $gridStats = $this->generateGridStatistics($mapData);

        return response()->json([
            'success' => true,
            'data' => $mapData,
            'grid_stats' => $gridStats,
            'summary' => [
                'total_toko' => $mapData->count(),
                'toko_with_coordinates' => $mapData->where('has_coordinates', true)->count(),
                'toko_active' => $mapData->where('status_aktif', 'Aktif')->count(),
                'high_quality_coords' => $mapData->whereIn('geocoding_quality', ['excellent', 'good'])->count(),
                'grid_coverage' => $gridStats['coverage_percentage']
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching enhanced toko data for grid map: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Method untuk mendapatkan koordinat toko dengan enhanced validation
 */
private function getTokoCoordinatesEnhanced($toko)
{
    // Prioritas 1: Gunakan koordinat asli dari geocoding dengan validasi ketat
    if ($toko->latitude && $toko->longitude) {
        $lat = (float) $toko->latitude;
        $lng = (float) $toko->longitude;
        
        // Validasi koordinat berada di wilayah Indonesia
        if ($lat >= -11.5 && $lat <= 6.5 && $lng >= 94.5 && $lng <= 142.0) {
            // Validasi spesifik wilayah Jawa Timur untuk accuracy
            $inJatim = ($lat >= -8.8 && $lat <= -7.2 && $lng >= 111.0 && $lng <= 114.5);
            
            return [
                'lat' => $lat,
                'lng' => $lng,
                'has_real_coordinates' => true,
                'source' => 'geocoded',
                'accuracy' => $inJatim ? 'high' : 'medium'
            ];
        }
    }

    // Prioritas 2: Generate koordinat enhanced berdasarkan wilayah
    $fallbackCoords = $this->generateEnhancedFallbackCoordinates(
        $toko->wilayah_kota_kabupaten,
        $toko->wilayah_kecamatan,
        $toko->wilayah_kelurahan,
        $toko->toko_id
    );

    return [
        'lat' => $fallbackCoords['lat'],
        'lng' => $fallbackCoords['lng'],
        'has_real_coordinates' => false,
        'source' => 'estimated_enhanced',
        'accuracy' => 'low'
    ];
}

/**
 * Enhanced method untuk generate koordinat fallback dengan algoritma lebih akurat
 */
private function generateEnhancedFallbackCoordinates($kotaKabupaten, $kecamatan, $kelurahan, $tokoId)
{
    // Base coordinates yang lebih akurat untuk wilayah Malang
    $baseCoordinates = [
        'Kota Malang' => [
            'center' => ['lat' => -7.9666, 'lng' => 112.6326],
            'bounds' => ['north' => -7.88, 'south' => -8.05, 'west' => 112.55, 'east' => 112.72]
        ],
        'Kabupaten Malang' => [
            'center' => ['lat' => -8.1844, 'lng' => 112.7456],
            'bounds' => ['north' => -7.80, 'south' => -8.55, 'west' => 112.30, 'east' => 113.20]
        ],
        'Kota Batu' => [
            'center' => ['lat' => -7.8767, 'lng' => 112.5326],
            'bounds' => ['north' => -7.75, 'south' => -8.00, 'west' => 112.45, 'east' => 112.62]
        ]
    ];

    // Ambil data wilayah
    $wilayahData = $baseCoordinates[$kotaKabupaten] ?? $baseCoordinates['Kota Malang'];

    // Generate offset berdasarkan kombinasi hash yang lebih sophisticated
    $hashKecamatan = crc32($kecamatan);
    $hashKelurahan = crc32($kelurahan);
    $hashToko = crc32($tokoId);
    
    // Kombinasi hash untuk mendapatkan distribusi yang lebih natural
    $combinedHash = ($hashKecamatan ^ $hashKelurahan ^ $hashToko);
    
    // Calculate bounds untuk wilayah
    $latRange = $wilayahData['bounds']['north'] - $wilayahData['bounds']['south'];
    $lngRange = $wilayahData['bounds']['east'] - $wilayahData['bounds']['west'];
    
    // Generate offset dalam bounds yang realistis
    $latOffset = (($combinedHash % 1000) / 1000) * $latRange - ($latRange / 2);
    $lngOffset = ((($combinedHash >> 10) % 1000) / 1000) * $lngRange - ($lngRange / 2);

    // Tambahkan small random untuk menghindari overlap
    $microOffset = (crc32($tokoId . $kelurahan) % 100) / 100000; // Very small offset
    
    return [
        'lat' => $wilayahData['center']['lat'] + $latOffset + $microOffset,
        'lng' => $wilayahData['center']['lng'] + $lngOffset + $microOffset
    ];
}

/**
 * Enhanced method untuk menentukan status toko
 */
private function getEnhancedTokoStatus($toko)
{
    if (!$toko->is_active) {
        return 'Tidak Aktif';
    }

    // Analisis lebih detail untuk status aktif
    $hasBarang = $toko->barangToko->count() > 0;
    $recentShipments = $toko->pengiriman()
        ->where('tanggal_pengiriman', '>=', now()->subDays(30))
        ->count();
    $veryRecentShipments = $toko->pengiriman()
        ->where('tanggal_pengiriman', '>=', now()->subDays(7))
        ->count();
        
    // Scoring system untuk status
    $score = 0;
    if ($hasBarang) $score += 2;
    if ($recentShipments > 0) $score += 3;
    if ($veryRecentShipments > 0) $score += 2;
    if ($recentShipments >= 5) $score += 2;

    if ($score >= 5) return 'Sangat Aktif';
    if ($score >= 3) return 'Aktif';
    if ($score >= 1) return 'Kurang Aktif';
    
    return 'Tidak Aktif';
}

/**
 * Calculate grid weight untuk toko berdasarkan berbagai faktor
 */
private function calculateGridWeight($toko)
{
    $weight = 1; // Base weight
    
    // Tambahkan weight berdasarkan aktivitas
    if ($toko['status_aktif'] === 'Sangat Aktif') $weight += 1.5;
    elseif ($toko['status_aktif'] === 'Aktif') $weight += 1.0;
    elseif ($toko['status_aktif'] === 'Kurang Aktif') $weight += 0.5;
    
    // Tambahkan weight berdasarkan jumlah barang
    $weight += min($toko['jumlah_barang'] * 0.1, 1.0);
    
    // Tambahkan weight berdasarkan pengiriman
    $weight += min($toko['total_pengiriman'] * 0.05, 0.5);
    
    // Faktor koordinat GPS akurat
    if ($toko['has_coordinates']) $weight += 0.2;
    
    return round($weight, 2);
}

/**
 * Tentukan kategori density untuk grid
 */
private function getDensityCategory($toko)
{
    $weight = $this->calculateGridWeight($toko);
    
    if ($weight >= 3.0) return 'very_high';
    if ($weight >= 2.0) return 'high';
    if ($weight >= 1.5) return 'medium';
    if ($weight >= 1.0) return 'low';
    
    return 'very_low';
}

/**
 * Generate statistik untuk grid heatmap
 */
private function generateGridStatistics($mapData)
{
    $gridSize = 0.01; // Size in degrees (approximately 1.1km)
    
    // Define bounds untuk wilayah Malang
    $bounds = [
        'north' => -7.4,
        'south' => -8.6,
        'west' => 111.8,
        'east' => 113.2
    ];
    
    // Generate grid cells dan hitung statistik
    $totalCells = 0;
    $occupiedCells = 0;
    $gridData = [];
    
    for ($lat = $bounds['south']; $lat < $bounds['north']; $lat += $gridSize) {
        for ($lng = $bounds['west']; $lng < $bounds['east']; $lng += $gridSize) {
            $totalCells++;
            
            // Count toko dalam grid cell ini
            $tokosInCell = $mapData->filter(function($toko) use ($lat, $lng, $gridSize) {
                $tokoLat = $toko['latitude'];
                $tokoLng = $toko['longitude'];
                
                return $tokoLat >= $lat && $tokoLat < ($lat + $gridSize) &&
                       $tokoLng >= $lng && $tokoLng < ($lng + $gridSize);
            });
            
            if ($tokosInCell->count() > 0) {
                $occupiedCells++;
                
                $cellData = [
                    'lat_min' => $lat,
                    'lat_max' => $lat + $gridSize,
                    'lng_min' => $lng,
                    'lng_max' => $lng + $gridSize,
                    'toko_count' => $tokosInCell->count(),
                    'total_weight' => $tokosInCell->sum('grid_weight'),
                    'avg_weight' => $tokosInCell->avg('grid_weight'),
                    'active_count' => $tokosInCell->whereIn('status_aktif', ['Aktif', 'Sangat Aktif'])->count()
                ];
                
                $gridData[] = $cellData;
            }
        }
    }
    
    // Calculate coverage dan density statistics
    $coveragePercentage = $totalCells > 0 ? round(($occupiedCells / $totalCells) * 100, 2) : 0;
    
    $densityDistribution = [
        'high' => count(array_filter($gridData, fn($cell) => $cell['toko_count'] >= 5)),
        'medium' => count(array_filter($gridData, fn($cell) => $cell['toko_count'] >= 2 && $cell['toko_count'] < 5)),
        'low' => count(array_filter($gridData, fn($cell) => $cell['toko_count'] === 1))
    ];
    
    return [
        'total_cells' => $totalCells,
        'occupied_cells' => $occupiedCells,
        'coverage_percentage' => $coveragePercentage,
        'density_distribution' => $densityDistribution,
        'avg_toko_per_cell' => $occupiedCells > 0 ? round($mapData->count() / $occupiedCells, 2) : 0,
        'grid_size_km' => round($gridSize * 111, 2), // Convert degrees to km (approximately)
        'total_area_km2' => round((($bounds['north'] - $bounds['south']) * ($bounds['east'] - $bounds['west'])) * 111 * 111, 2)
    ];
}

/**
 * API endpoint untuk mendapatkan data grid heatmap
 */
public function getGridHeatmapData(Request $request)
{
    try {
        $gridSize = $request->get('grid_size', 0.01); // Default 1.1km
        $wilayahFilter = $request->get('wilayah', 'all');
        
        // Base query
        $query = Toko::with(['barangToko', 'pengiriman', 'retur'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
            
        // Apply wilayah filter
        if ($wilayahFilter !== 'all') {
            $query->where('wilayah_kota_kabupaten', $wilayahFilter);
        }
        
        $tokoData = $query->get();
        
        // Generate grid data
        $gridCells = $this->generateGridCells($gridSize, $wilayahFilter);
        $gridWithData = $this->assignTokoToGrids($gridCells, $tokoData);
        
        return response()->json([
            'success' => true,
            'grid_data' => $gridWithData,
            'metadata' => [
                'grid_size' => $gridSize,
                'total_grids' => count($gridCells),
                'occupied_grids' => count(array_filter($gridWithData, fn($grid) => $grid['toko_count'] > 0)),
                'total_toko' => $tokoData->count(),
                'wilayah_filter' => $wilayahFilter
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error generating grid heatmap data: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Generate grid cells berdasarkan bounds wilayah
 */
private function generateGridCells($gridSize, $wilayahFilter = 'all')
{
    // Define bounds berdasarkan wilayah
    $bounds = $this->getWilayahBounds($wilayahFilter);
    
    $cells = [];
    for ($lat = $bounds['south']; $lat < $bounds['north']; $lat += $gridSize) {
        for ($lng = $bounds['west']; $lng < $bounds['east']; $lng += $gridSize) {
            $cells[] = [
                'id' => md5($lat . '_' . $lng),
                'bounds' => [
                    'south' => $lat,
                    'north' => $lat + $gridSize,
                    'west' => $lng,
                    'east' => $lng + $gridSize
                ],
                'center' => [
                    'lat' => $lat + ($gridSize / 2),
                    'lng' => $lng + ($gridSize / 2)
                ]
            ];
        }
    }
    
    return $cells;
}

/**
 * Get bounds coordinate berdasarkan wilayah
 */
private function getWilayahBounds($wilayah)
{
    $bounds = [
        'Kota Malang' => [
            'north' => -7.88, 'south' => -8.05,
            'west' => 112.55, 'east' => 112.72
        ],
        'Kabupaten Malang' => [
            'north' => -7.80, 'south' => -8.55,
            'west' => 112.30, 'east' => 113.20
        ],
        'Kota Batu' => [
            'north' => -7.75, 'south' => -8.00,
            'west' => 112.45, 'east' => 112.62
        ],
        'all' => [
            'north' => -7.4, 'south' => -8.6,
            'west' => 111.8, 'east' => 113.2
        ]
    ];
    
    return $bounds[$wilayah] ?? $bounds['all'];
}

/**
 * Assign toko ke grid cells yang sesuai
 */
private function assignTokoToGrids($gridCells, $tokoData)
{
    return array_map(function($cell) use ($tokoData) {
        $tokosInCell = $tokoData->filter(function($toko) use ($cell) {
            $lat = (float) $toko->latitude;
            $lng = (float) $toko->longitude;
            
            return $lat >= $cell['bounds']['south'] && 
                   $lat < $cell['bounds']['north'] &&
                   $lng >= $cell['bounds']['west'] && 
                   $lng < $cell['bounds']['east'];
        });
        
        // Calculate statistics untuk cell ini
        $tokoCount = $tokosInCell->count();
        $activeCount = $tokosInCell->filter(function($toko) {
            return $toko->is_active;
        })->count();
        
        // Determine density category
        $densityCategory = 'none';
        if ($tokoCount >= 5) $densityCategory = 'high';
        elseif ($tokoCount >= 2) $densityCategory = 'medium';
        elseif ($tokoCount >= 1) $densityCategory = 'low';
        
        // Determine color berdasarkan density
        $colors = [
            'high' => '#dc143c',    // Merah pekat
            'medium' => '#ff8c00',  // Oranye
            'low' => '#ffd700',     // Kuning terang
            'none' => 'transparent' // Transparan
        ];
        
        return array_merge($cell, [
            'toko_count' => $tokoCount,
            'active_count' => $activeCount,
            'density_category' => $densityCategory,
            'color' => $colors[$densityCategory],
            'tokos' => $tokosInCell->map(function($toko) {
                return [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'kecamatan' => $toko->wilayah_kecamatan,
                    'is_active' => $toko->is_active
                ];
            })->values()->all()
        ]);
    }, $gridCells);
}

/**
 * Get enhanced wilayah statistics dengan grid information
 */
public function getEnhancedWilayahStatistics()
{
    try {
        // Statistik per kecamatan dengan grid data
        $statistikKecamatan = Toko::select([
            'wilayah_kecamatan',
            'wilayah_kota_kabupaten',
            DB::raw('COUNT(*) as jumlah_toko'),
            DB::raw('COUNT(CASE WHEN is_active = 1 THEN 1 END) as toko_aktif'),
            DB::raw('COUNT(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 END) as toko_with_coordinates'),
            DB::raw('COUNT(CASE WHEN geocoding_quality IN ("excellent", "good") THEN 1 END) as high_quality_coords'),
            DB::raw('AVG(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN geocoding_score END) as avg_geocoding_score')
        ])
        ->groupBy('wilayah_kecamatan', 'wilayah_kota_kabupaten')
        ->orderBy('jumlah_toko', 'desc')
        ->get();

        // Enhanced density analysis
        $densityAnalysis = $this->calculateDensityAnalysis();
        
        // Grid coverage analysis
        $gridCoverage = $this->calculateGridCoverage();

        return response()->json([
            'success' => true,
            'data' => [
                'kecamatan' => $statistikKecamatan,
                'density_analysis' => $densityAnalysis,
                'grid_coverage' => $gridCoverage,
                'summary' => [
                    'total_kecamatan' => $statistikKecamatan->count(),
                    'avg_toko_per_kecamatan' => round($statistikKecamatan->avg('jumlah_toko'), 2),
                    'coord_coverage_percentage' => $statistikKecamatan->sum('toko_with_coordinates') > 0 ? 
                        round(($statistikKecamatan->sum('toko_with_coordinates') / $statistikKecamatan->sum('jumlah_toko')) * 100, 2) : 0,
                    'high_quality_percentage' => $statistikKecamatan->sum('toko_with_coordinates') > 0 ?
                        round(($statistikKecamatan->sum('high_quality_coords') / $statistikKecamatan->sum('toko_with_coordinates')) * 100, 2) : 0
                ]
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching enhanced wilayah statistics: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Calculate density analysis untuk enhanced statistics
 */
private function calculateDensityAnalysis()
{
    // Analisis kepadatan berdasarkan grid 1km x 1km
    $densityData = DB::select("
        SELECT 
            FLOOR(latitude / 0.009) as lat_grid,
            FLOOR(longitude / 0.009) as lng_grid,
            COUNT(*) as toko_count,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count,
            wilayah_kota_kabupaten
        FROM toko 
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
        GROUP BY lat_grid, lng_grid, wilayah_kota_kabupaten
        HAVING toko_count > 0
        ORDER BY toko_count DESC
    ");
    
    $highDensity = array_filter($densityData, fn($item) => $item->toko_count >= 5);
    $mediumDensity = array_filter($densityData, fn($item) => $item->toko_count >= 2 && $item->toko_count < 5);
    $lowDensity = array_filter($densityData, fn($item) => $item->toko_count === 1);
    
    return [
        'total_grids' => count($densityData),
        'high_density_grids' => count($highDensity),
        'medium_density_grids' => count($mediumDensity),
        'low_density_grids' => count($lowDensity),
        'avg_toko_per_grid' => count($densityData) > 0 ? 
            round(array_sum(array_column($densityData, 'toko_count')) / count($densityData), 2) : 0
    ];
}

/**
 * Calculate grid coverage untuk berbagai zoom levels
 */
private function calculateGridCoverage()
{
    $coverageData = [];
    $gridSizes = [0.005, 0.01, 0.02]; // Berbagai ukuran grid
    
    foreach ($gridSizes as $size) {
        $grids = $this->generateGridCells($size);
        $tokoData = Toko::whereNotNull('latitude')->whereNotNull('longitude')->get();
        $occupiedGrids = 0;
        
        foreach ($grids as $grid) {
            $hasData = $tokoData->filter(function($toko) use ($grid) {
                $lat = (float) $toko->latitude;
                $lng = (float) $toko->longitude;
                
                return $lat >= $grid['bounds']['south'] && 
                       $lat < $grid['bounds']['north'] &&
                       $lng >= $grid['bounds']['west'] && 
                       $lng < $grid['bounds']['east'];
            })->count() > 0;
            
            if ($hasData) $occupiedGrids++;
        }
        
        $coverageData[] = [
            'grid_size_km' => round($size * 111, 2),
            'total_grids' => count($grids),
            'occupied_grids' => $occupiedGrids,
            'coverage_percentage' => count($grids) > 0 ? round(($occupiedGrids / count($grids)) * 100, 2) : 0
        ];
    }
    
    return $coverageData;
}

/**
 * Enhanced bulk geocoding dengan progress tracking
 */
public function enhancedBulkGeocodeTokos(Request $request)
{
    try {
        $limit = $request->get('limit', 10);
        $qualityFilter = $request->get('quality_filter', ['poor', 'very poor', 'failed', null]);
        
        $tokosWithoutCoordinates = Toko::where(function($query) use ($qualityFilter) {
            $query->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhereIn('geocoding_quality', $qualityFilter)
                  ->orWhereNull('geocoding_quality');
        })
        ->limit($limit)
        ->get();

        $results = [];
        $successCount = 0;
        $improvementCount = 0;

        foreach ($tokosWithoutCoordinates as $toko) {
            $oldQuality = $toko->geocoding_quality;
            $fullAddress = $toko->alamat . ', ' . 
                          $toko->wilayah_kelurahan . ', ' . 
                          $toko->wilayah_kecamatan . ', ' . 
                          $toko->wilayah_kota_kabupaten . ', Jawa Timur, Indonesia';

            $geocodeResult = GeocodingService::geocodeAddress($fullAddress);
            
            if ($geocodeResult) {
                // Enhanced quality assessment
                $qualityCheck = $this->assessGeocodingQuality($geocodeResult, $toko);
                
                $toko->update([
                    'latitude' => $geocodeResult['latitude'],
                    'longitude' => $geocodeResult['longitude'],
                    'alamat_lengkap_geocoding' => $geocodeResult['formatted_address'],
                    'geocoding_provider' => $geocodeResult['provider'] ?? 'unknown',
                    'geocoding_accuracy' => $geocodeResult['accuracy'] ?? 'unknown',
                    'geocoding_confidence' => $geocodeResult['confidence'] ?? null,
                    'geocoding_quality' => $qualityCheck['quality'],
                    'geocoding_score' => $qualityCheck['score'],
                    'geocoding_last_updated' => now()
                ]);
                
                $successCount++;
                
                // Check if quality improved
                if ($this->isQualityImprovement($oldQuality, $qualityCheck['quality'])) {
                    $improvementCount++;
                }
                
                $results[] = [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'status' => 'success',
                    'coordinates' => [$geocodeResult['latitude'], $geocodeResult['longitude']],
                    'quality' => $qualityCheck['quality'],
                    'score' => $qualityCheck['score'],
                    'improved' => $this->isQualityImprovement($oldQuality, $qualityCheck['quality'])
                ];
            } else {
                $results[] = [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'status' => 'failed',
                    'error' => 'Geocoding service failed'
                ];
            }

            // Rate limiting
            usleep(500000); // 0.5 second delay
        }

        return response()->json([
            'success' => true,
            'message' => "Enhanced geocoding selesai. Berhasil: {$successCount}/" . count($tokosWithoutCoordinates) . 
                        ($improvementCount > 0 ? ", Peningkatan kualitas: {$improvementCount}" : ""),
            'data' => $results,
            'summary' => [
                'total_processed' => count($tokosWithoutCoordinates),
                'successful' => $successCount,
                'failed' => count($tokosWithoutCoordinates) - $successCount,
                'quality_improvements' => $improvementCount
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error enhanced bulk geocoding tokos: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Assess geocoding quality berdasarkan berbagai faktor
 */
private function assessGeocodingQuality($geocodeResult, $toko)
{
    $score = 0;
    $quality = 'poor';
    
    // Factor 1: Provider reliability
    $providerScores = [
        'google_maps' => 30,
        'locationiq' => 25,
        'opencage' => 25,
        'mapbox' => 20,
        'nominatim' => 15,
        'here' => 20
    ];
    
    $provider = $geocodeResult['provider'] ?? 'unknown';
    $score += $providerScores[$provider] ?? 10;
    
    // Factor 2: Confidence level
    if (isset($geocodeResult['confidence'])) {
        $score += $geocodeResult['confidence'] * 20;
    }
    
    // Factor 3: Regional accuracy (is it in correct region?)
    $lat = $geocodeResult['latitude'];
    $lng = $geocodeResult['longitude'];
    
    // Check if in Jawa Timur
    if ($lat >= -8.8 && $lat <= -7.2 && $lng >= 111.0 && $lng <= 114.5) {
        $score += 20;
        
        // Check if in Malang region specifically
        if ($lat >= -8.6 && $lat <= -7.4 && $lng >= 111.8 && $lng <= 113.2) {
            $score += 15;
        }
    }
    
    // Factor 4: Address match quality
    $formattedAddress = strtolower($geocodeResult['formatted_address'] ?? '');
    $tokoKecamatan = strtolower($toko->wilayah_kecamatan);
    $tokoKotaKab = strtolower($toko->wilayah_kota_kabupaten);
    
    if (strpos($formattedAddress, $tokoKecamatan) !== false) $score += 10;
    if (strpos($formattedAddress, $tokoKotaKab) !== false) $score += 10;
    
    // Determine quality based on score
    if ($score >= 80) $quality = 'excellent';
    elseif ($score >= 65) $quality = 'good';
    elseif ($score >= 50) $quality = 'fair';
    elseif ($score >= 30) $quality = 'poor';
    else $quality = 'very poor';
    
    return [
        'quality' => $quality,
        'score' => min($score, 100) // Cap at 100
    ];
}

/**
 * Check if geocoding quality improved
 */
private function isQualityImprovement($oldQuality, $newQuality)
{
    $qualityOrder = [
        'very poor' => 1,
        'poor' => 2,
        'fair' => 3,
        'good' => 4,
        'excellent' => 5
    ];
    
    $oldScore = $qualityOrder[$oldQuality] ?? 0;
    $newScore = $qualityOrder[$newQuality] ?? 0;
    
    return $newScore > $oldScore;
}
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Models\Toko;
use App\Services\GeocodingService; 

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

}
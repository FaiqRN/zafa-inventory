<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class TokoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('toko.index', [
            'activemenu' => 'toko',
            'breadcrumb' => (object) [
                'title' => 'Data Toko',
                'list' => ['Home', 'Master Data', 'Data Toko']
            ]
        ]);
    }

    /**
     * Get all toko data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $data = Toko::all();
        
        $response = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return ''; // Handle via JavaScript
            })
            ->rawColumns(['action'])
            ->make(true);
            
        // Add cache prevention headers
        return $response
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Generate a new toko code
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateKode()
    {
        $lastToko = Toko::orderBy('toko_id', 'desc')->first();
        
        if (!$lastToko) {
            return response()->json([
                'status' => 'success',
                'kode' => 'TKO001'
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        }
        
        $lastId = $lastToko->toko_id;
        $prefix = 'TKO';
        
        // Extract numeric part
        if (preg_match('/^TKO(\d+)$/', $lastId, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            $nextId = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'TKO001';
        }
        
        return response()->json([
            'status' => 'success',
            'kode' => $nextId
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Get all wilayah data (Kota/Kabupaten)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWilayahKota()
    {
        $jsonFile = public_path('data/wilayah_malang.json');
        
        if (!File::exists($jsonFile)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File data wilayah tidak ditemukan'
            ], 404)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        }
        
        $wilayahData = json_decode(File::get($jsonFile), true);
        
        return response()->json([
            'status' => 'success',
            'data' => $wilayahData['wilayah']
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }
    
    /**
     * Get kecamatan by kota ID
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKecamatanByKota(Request $request)
    {
        $kotaId = $request->kota_id;
        
        if (!$kotaId) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID Kota/Kabupaten tidak valid'
            ], 400)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        }
        
        $jsonFile = public_path('data/wilayah_malang.json');
        
        if (!File::exists($jsonFile)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File data wilayah tidak ditemukan'
            ], 404)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        }
        
        $wilayahData = json_decode(File::get($jsonFile), true);
        $kecamatanData = [];
        
        foreach ($wilayahData['wilayah'] as $kota) {
            if ($kota['id'] == $kotaId) {
                $kecamatanData = $kota['kecamatan'];
                break;
            }
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $kecamatanData
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }
    
    /**
     * Get kelurahan by kecamatan ID
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKelurahanByKecamatan(Request $request)
    {
        $kotaId = $request->kota_id;
        $kecamatanId = $request->kecamatan_id;
        
        if (!$kotaId || !$kecamatanId) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID Kota/Kabupaten atau Kecamatan tidak valid'
            ], 400)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        }
        
        $jsonFile = public_path('data/wilayah_malang.json');
        
        if (!File::exists($jsonFile)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File data wilayah tidak ditemukan'
            ], 404)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        }
        
        $wilayahData = json_decode(File::get($jsonFile), true);
        $kelurahanData = [];
        
        foreach ($wilayahData['wilayah'] as $kota) {
            if ($kota['id'] == $kotaId) {
                foreach ($kota['kecamatan'] as $kecamatan) {
                    if ($kecamatan['id'] == $kecamatanId) {
                        $kelurahanData = $kecamatan['kelurahan'];
                        break 2;
                    }
                }
            }
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $kelurahanData
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'toko_id' => 'required|string|max:10|unique:toko',
            'nama_toko' => 'required|string|max:100',
            'pemilik' => 'required|string|max:100',
            'alamat' => 'required|string',
            'wilayah_kota_kabupaten' => 'required|string|max:100',
            'wilayah_kecamatan' => 'required|string|max:100',
            'wilayah_kelurahan' => 'required|string|max:100',
            'nomer_telpon' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buat alamat lengkap untuk geocoding
            $fullAddress = $request->alamat . ', ' . 
                          $request->wilayah_kelurahan . ', ' . 
                          $request->wilayah_kecamatan . ', ' . 
                          $request->wilayah_kota_kabupaten . ', Indonesia';

            // Lakukan geocoding untuk mendapatkan koordinat
            $geocodeResult = \App\Services\GeocodingService::geocodeAddress($fullAddress);
            
            // Tambah data toko baru
            $toko = new Toko();
            $toko->toko_id = $request->toko_id;
            $toko->nama_toko = $request->nama_toko;
            $toko->pemilik = $request->pemilik;
            $toko->alamat = $request->alamat;
            $toko->wilayah_kecamatan = $request->wilayah_kecamatan;
            $toko->wilayah_kelurahan = $request->wilayah_kelurahan;
            $toko->wilayah_kota_kabupaten = $request->wilayah_kota_kabupaten;
            $toko->nomer_telpon = $request->nomer_telpon;
            $toko->is_active = true;
            
            // Set koordinat jika geocoding berhasil
            if ($geocodeResult) {
                $toko->latitude = $geocodeResult['latitude'];
                $toko->longitude = $geocodeResult['longitude'];
                $toko->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
                
                // Validasi apakah koordinat berada di wilayah yang wajar
                if (!\App\Services\GeocodingService::isInMalangRegion($geocodeResult['latitude'], $geocodeResult['longitude'])) {
                    // Jika koordinat di luar wilayah Malang, beri peringatan tapi tetap simpan
                    \Illuminate\Support\Facades\Log::warning('Toko ' . $request->nama_toko . ' memiliki koordinat di luar wilayah Malang: ' . $geocodeResult['latitude'] . ', ' . $geocodeResult['longitude']);
                }
            }
            
            $toko->save();

            $responseMessage = 'Data toko berhasil ditambahkan';
            if ($geocodeResult) {
                $responseMessage .= ' dengan koordinat GPS (' . $geocodeResult['latitude'] . ', ' . $geocodeResult['longitude'] . ')';
            } else {
                $responseMessage .= '. Koordinat GPS tidak dapat ditemukan, akan menggunakan estimasi lokasi di Market Map.';
            }

            return response()->json([
                'status' => 'success',
                'message' => $responseMessage,
                'data' => $toko,
                'geocode_info' => $geocodeResult
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error saving toko: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data toko'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $toko = Toko::find($id);
        
        if (!$toko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data toko tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $toko
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $toko = Toko::find($id);
        
        if (!$toko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data toko tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $toko
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $toko = Toko::find($id);
        
        if (!$toko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data toko tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama_toko' => 'required|string|max:100',
            'pemilik' => 'required|string|max:100',
            'alamat' => 'required|string',
            'wilayah_kota_kabupaten' => 'required|string|max:100',
            'wilayah_kecamatan' => 'required|string|max:100',
            'wilayah_kelurahan' => 'required|string|max:100',
            'nomer_telpon' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update data toko
        $toko->nama_toko = $request->nama_toko;
        $toko->pemilik = $request->pemilik;
        $toko->alamat = $request->alamat;
        $toko->wilayah_kecamatan = $request->wilayah_kecamatan;
        $toko->wilayah_kelurahan = $request->wilayah_kelurahan;
        $toko->wilayah_kota_kabupaten = $request->wilayah_kota_kabupaten;
        $toko->nomer_telpon = $request->nomer_telpon;
        $toko->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data toko berhasil diperbarui',
            'data' => $toko
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $toko = Toko::find($id);
        
        if (!$toko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data toko tidak ditemukan'
            ], 404);
        }

        try {
            // Hard delete toko
            $toko->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data toko berhasil dihapus'
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
            
        } catch (\Exception $e) {
            // Improved error handling for foreign key constraint violations
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data toko tidak dapat dihapus karena masih digunakan dalam transaksi'
                ], 400)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data toko'
            ], 500)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        }
    }

    public function getList(Request $request)
    {
        // Get all toko data
        $data = Toko::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    // Tambahkan method ini di TokoController.php

/**
 * Preview geocoding untuk alamat yang diinput (untuk test sebelum save)
 * 
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function previewGeocode(Request $request)
{
    $validator = Validator::make($request->all(), [
        'alamat' => 'required|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Alamat harus diisi',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        // Lakukan geocoding
        $geocodeResult = \App\Services\GeocodingService::geocodeAddress($request->alamat);
        
        if ($geocodeResult) {
            return response()->json([
                'status' => 'success',
                'message' => 'Koordinat berhasil ditemukan',
                'geocode_info' => $geocodeResult
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Koordinat tidak dapat ditemukan untuk alamat tersebut'
            ], 404);
        }

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error preview geocoding: ' . $e->getMessage());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat melakukan preview geocoding'
        ], 500);
    }
}

/**
 * Geocode alamat toko manual (untuk test atau perbaikan data)
 * 
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function geocodeToko(Request $request)
{
    $validator = Validator::make($request->all(), [
        'toko_id' => 'required|exists:toko,toko_id',
        'alamat' => 'required|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $toko = Toko::find($request->toko_id);
        
        if (!$toko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Toko tidak ditemukan'
            ], 404);
        }

        // Lakukan geocoding
        $geocodeResult = \App\Services\GeocodingService::geocodeAddress($request->alamat);
        
        if ($geocodeResult) {
            // Update koordinat toko
            $toko->latitude = $geocodeResult['latitude'];
            $toko->longitude = $geocodeResult['longitude'];
            $toko->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
            $toko->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Koordinat toko berhasil diperbarui',
                'data' => [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'latitude' => $toko->latitude,
                    'longitude' => $toko->longitude,
                    'formatted_address' => $toko->alamat_lengkap_geocoding
                ],
                'geocode_info' => $geocodeResult
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menemukan koordinat untuk alamat tersebut'
            ], 404);
        }

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error geocoding toko: ' . $e->getMessage());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat melakukan geocoding'
        ], 500);
    }
}

/**
 * Batch geocoding untuk semua toko yang belum memiliki koordinat
 * 
 * @return \Illuminate\Http\JsonResponse
 */
public function batchGeocodeToko()
{
    try {
        $tokosWithoutCoordinates = Toko::whereNull('latitude')
                                      ->orWhereNull('longitude')
                                      ->get();

        if ($tokosWithoutCoordinates->isEmpty()) {
            return response()->json([
                'status' => 'info',
                'message' => 'Semua toko sudah memiliki koordinat GPS',
                'summary' => [
                    'total_processed' => 0,
                    'success_count' => 0,
                    'failed_count' => 0
                ]
            ]);
        }

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($tokosWithoutCoordinates as $toko) {
            $fullAddress = $toko->alamat . ', ' . 
                          $toko->wilayah_kelurahan . ', ' . 
                          $toko->wilayah_kecamatan . ', ' . 
                          $toko->wilayah_kota_kabupaten . ', Indonesia';

            $geocodeResult = \App\Services\GeocodingService::geocodeAddress($fullAddress);
            
            if ($geocodeResult) {
                $toko->latitude = $geocodeResult['latitude'];
                $toko->longitude = $geocodeResult['longitude'];
                $toko->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
                $toko->save();
                
                $successCount++;
                $results[] = [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'status' => 'success',
                    'coordinates' => [$geocodeResult['latitude'], $geocodeResult['longitude']]
                ];
            } else {
                $failedCount++;
                $results[] = [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'status' => 'failed',
                    'coordinates' => null
                ];
            }

            // Delay untuk menghindari rate limiting
            usleep(500000); // 0.5 detik
        }

        return response()->json([
            'status' => 'success',
            'message' => "Batch geocoding selesai. Berhasil: {$successCount}, Gagal: {$failedCount}",
            'summary' => [
                'total_processed' => count($tokosWithoutCoordinates),
                'success_count' => $successCount,
                'failed_count' => $failedCount
            ],
            'results' => $results
        ]);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error batch geocoding: ' . $e->getMessage());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat melakukan batch geocoding'
        ], 500);
    }
    }
}

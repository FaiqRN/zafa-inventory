<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\GeocodingService;

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
     * Store a newly created resource in storage with enhanced geocoding.
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
            // Buat alamat lengkap untuk geocoding dengan format yang optimal
            $fullAddress = trim($request->alamat . ', ' . 
                          $request->wilayah_kelurahan . ', ' . 
                          $request->wilayah_kecamatan . ', ' . 
                          $request->wilayah_kota_kabupaten . ', Jawa Timur, Indonesia');

            Log::info("Starting enhanced geocoding for: {$request->nama_toko} - {$fullAddress}");

            // Lakukan enhanced geocoding
            $geocodeResult = GeocodingService::geocodeAddress($fullAddress);
            
            // Validate geocoding quality
            $qualityCheck = null;
            if ($geocodeResult) {
                $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);
                Log::info("Geocoding quality: {$qualityCheck['quality']} (Score: {$qualityCheck['score']})");
            }
            
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
            
            // Set koordinat dan informasi geocoding
            if ($geocodeResult) {
                $toko->latitude = $geocodeResult['latitude'];
                $toko->longitude = $geocodeResult['longitude'];
                $toko->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
                
                // Simpan metadata geocoding untuk analisis
                $toko->geocoding_provider = $geocodeResult['provider'];
                $toko->geocoding_accuracy = $geocodeResult['accuracy'];
                $toko->geocoding_confidence = $geocodeResult['confidence'] ?? null;
                $toko->geocoding_quality = $qualityCheck['quality'] ?? null;
                $toko->geocoding_score = $qualityCheck['score'] ?? null;
                
                // Log peringatan jika koordinat di luar wilayah Malang
                if (!GeocodingService::isInMalangRegion($geocodeResult['latitude'], $geocodeResult['longitude'])) {
                    Log::warning("Toko {$request->nama_toko} memiliki koordinat di luar wilayah Malang: {$geocodeResult['latitude']}, {$geocodeResult['longitude']} - Provider: {$geocodeResult['provider']}");
                }
            } else {
                Log::warning("Failed to geocode address for toko: {$request->nama_toko} - {$fullAddress}");
                
                // Set default info jika geocoding gagal
                $toko->geocoding_provider = 'failed';
                $toko->geocoding_accuracy = 'none';
                $toko->geocoding_quality = 'failed';
                $toko->geocoding_score = 0;
            }
            
            $toko->save();

            // Buat response message yang informatif
            $responseMessage = 'Data toko berhasil ditambahkan';
            $geocodeInfo = null;
            
            if ($geocodeResult) {
                $qualityBadge = $this->getQualityBadge($qualityCheck['quality']);
                $regionStatus = GeocodingService::isInMalangRegion($geocodeResult['latitude'], $geocodeResult['longitude']) 
                    ? '✓ Wilayah Malang' 
                    : '⚠ Di luar wilayah Malang';
                
                $responseMessage .= " dengan koordinat GPS presisi tinggi";
                $geocodeInfo = [
                    'latitude' => $geocodeResult['latitude'],
                    'longitude' => $geocodeResult['longitude'],
                    'provider' => $geocodeResult['provider'],
                    'accuracy' => $geocodeResult['accuracy'],
                    'quality' => $qualityCheck['quality'],
                    'quality_score' => $qualityCheck['score'],
                    'confidence' => $geocodeResult['confidence'] ?? 0,
                    'region_status' => $regionStatus,
                    'quality_badge' => $qualityBadge,
                    'formatted_address' => $geocodeResult['formatted_address']
                ];
            } else {
                $responseMessage .= '. Koordinat GPS tidak dapat ditemukan, toko akan menggunakan estimasi lokasi di Market Map.';
            }

            return response()->json([
                'status' => 'success',
                'message' => $responseMessage,
                'data' => $toko,
                'geocode_info' => $geocodeInfo,
                'quality_check' => $qualityCheck
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
            
        } catch (\Exception $e) {
            Log::error('Error saving toko with enhanced geocoding: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data toko. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Get quality badge for UI display
     */
    private function getQualityBadge($quality)
    {
        $badges = [
            'excellent' => ['text' => 'Sangat Akurat', 'class' => 'success'],
            'good' => ['text' => 'Akurat', 'class' => 'primary'],
            'fair' => ['text' => 'Cukup Akurat', 'class' => 'warning'],
            'poor' => ['text' => 'Kurang Akurat', 'class' => 'danger'],
            'very poor' => ['text' => 'Tidak Akurat', 'class' => 'danger'],
            'failed' => ['text' => 'Gagal', 'class' => 'secondary']
        ];
        
        return $badges[$quality] ?? $badges['failed'];
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
     * Update the specified resource in storage with enhanced geocoding.
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

        try {
            // Cek apakah alamat berubah untuk menentukan perlu geocoding ulang
            $addressChanged = (
                $toko->alamat !== $request->alamat ||
                $toko->wilayah_kelurahan !== $request->wilayah_kelurahan ||
                $toko->wilayah_kecamatan !== $request->wilayah_kecamatan ||
                $toko->wilayah_kota_kabupaten !== $request->wilayah_kota_kabupaten
            );

            // Update data toko
            $toko->nama_toko = $request->nama_toko;
            $toko->pemilik = $request->pemilik;
            $toko->alamat = $request->alamat;
            $toko->wilayah_kecamatan = $request->wilayah_kecamatan;
            $toko->wilayah_kelurahan = $request->wilayah_kelurahan;
            $toko->wilayah_kota_kabupaten = $request->wilayah_kota_kabupaten;
            $toko->nomer_telpon = $request->nomer_telpon;

            $geocodeInfo = null;
            $qualityCheck = null;

            // Lakukan geocoding ulang jika alamat berubah
            if ($addressChanged) {
                $fullAddress = trim($request->alamat . ', ' . 
                              $request->wilayah_kelurahan . ', ' . 
                              $request->wilayah_kecamatan . ', ' . 
                              $request->wilayah_kota_kabupaten . ', Jawa Timur, Indonesia');

                Log::info("Address changed for toko {$toko->toko_id}, re-geocoding: {$fullAddress}");

                $geocodeResult = GeocodingService::geocodeAddress($fullAddress);
                
                if ($geocodeResult) {
                    $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);
                    
                    // Update koordinat dan metadata
                    $toko->latitude = $geocodeResult['latitude'];
                    $toko->longitude = $geocodeResult['longitude'];
                    $toko->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
                    $toko->geocoding_provider = $geocodeResult['provider'];
                    $toko->geocoding_accuracy = $geocodeResult['accuracy'];
                    $toko->geocoding_confidence = $geocodeResult['confidence'] ?? null;
                    $toko->geocoding_quality = $qualityCheck['quality'] ?? null;
                    $toko->geocoding_score = $qualityCheck['score'] ?? null;
                    
                    $geocodeInfo = [
                        'latitude' => $geocodeResult['latitude'],
                        'longitude' => $geocodeResult['longitude'],
                        'provider' => $geocodeResult['provider'],
                        'accuracy' => $geocodeResult['accuracy'],
                        'quality' => $qualityCheck['quality'],
                        'quality_score' => $qualityCheck['score'],
                        'confidence' => $geocodeResult['confidence'] ?? 0,
                        'updated' => true
                    ];
                    
                    Log::info("Re-geocoding success: {$geocodeResult['provider']} - Quality: {$qualityCheck['quality']}");
                } else {
                    Log::warning("Re-geocoding failed for toko: {$toko->toko_id}");
                }
            }

            $toko->save();

            $responseMessage = 'Data toko berhasil diperbarui';
            if ($addressChanged && $geocodeInfo) {
                $responseMessage .= ' dengan koordinat GPS yang diperbarui';
            }

            return response()->json([
                'status' => 'success',
                'message' => $responseMessage,
                'data' => $toko,
                'geocode_info' => $geocodeInfo,
                'quality_check' => $qualityCheck,
                'address_changed' => $addressChanged
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Error updating toko: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data toko'
            ], 500);
        }
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
            $toko->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data toko berhasil dihapus'
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
            
        } catch (\Exception $e) {
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

/**
 * Get toko list for AJAX calls
 */
public function getList(Request $request)
{
    try {
        // Get all toko data with error handling
        $data = Toko::select([
            'toko_id',
            'nama_toko', 
            'pemilik',
            'alamat',
            'wilayah_kecamatan',
            'wilayah_kelurahan', 
            'wilayah_kota_kabupaten',
            'nomer_telpon',
            'latitude',
            'longitude',
            'is_active',
            'geocoding_provider',
            'geocoding_quality',
            'geocoding_score',
            'geocoding_confidence'
        ])
        ->orderBy('created_at', 'desc')
        ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'count' => $data->count()
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
        
    } catch (\Exception $e) {
        Log::error('Error in getList: ' . $e->getMessage());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal memuat data toko: ' . $e->getMessage(),
            'data' => []
        ], 500);
    }
}

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
            Log::info("Preview geocoding for: {$request->alamat}");
            
            // Lakukan enhanced geocoding
            $geocodeResult = GeocodingService::geocodeAddress($request->alamat);
            
            if ($geocodeResult) {
                $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);
                
                return response()->json([
                    'status' => 'success',
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
                        'quality_badge' => $this->getQualityBadge($qualityCheck['quality']),
                        'in_malang_region' => GeocodingService::isInMalangRegion($geocodeResult['latitude'], $geocodeResult['longitude']),
                        'region_status' => GeocodingService::isInMalangRegion($geocodeResult['latitude'], $geocodeResult['longitude']) 
                            ? '✓ Dalam wilayah Malang' 
                            : '⚠ Di luar wilayah Malang',
                        'recommendations' => $qualityCheck['recommendations'] ?? []
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Koordinat tidak dapat ditemukan untuk alamat tersebut'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error preview geocoding: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat melakukan preview geocoding'
            ], 500);
        }
    }

    /**
     * Geocode alamat toko manual (untuk update koordinat existing toko)
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

            Log::info("Manual geocoding for toko {$toko->toko_id}: {$request->alamat}");

            // Lakukan enhanced geocoding
            $geocodeResult = GeocodingService::geocodeAddress($request->alamat);
            
            if ($geocodeResult) {
                $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);
                
                // Update koordinat toko
                $toko->latitude = $geocodeResult['latitude'];
                $toko->longitude = $geocodeResult['longitude'];
                $toko->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
                $toko->geocoding_provider = $geocodeResult['provider'];
                $toko->geocoding_accuracy = $geocodeResult['accuracy'];
                $toko->geocoding_confidence = $geocodeResult['confidence'] ?? null;
                $toko->geocoding_quality = $qualityCheck['quality'];
                $toko->geocoding_score = $qualityCheck['score'];
                $toko->save();

                $message = "Koordinat toko berhasil diperbarui";
                if ($qualityCheck['quality'] === 'excellent' || $qualityCheck['quality'] === 'good') {
                    $message .= " dengan akurasi tinggi";
                } elseif (in_array($qualityCheck['quality'], ['fair', 'poor'])) {
                    $message .= " (perlu verifikasi manual)";
                }

                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'data' => [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko,
                        'latitude' => $toko->latitude,
                        'longitude' => $toko->longitude,
                        'formatted_address' => $toko->alamat_lengkap_geocoding,
                        'provider' => $toko->geocoding_provider,
                        'accuracy' => $toko->geocoding_accuracy,
                        'quality' => $toko->geocoding_quality,
                        'quality_score' => $toko->geocoding_score
                    ],
                    'geocode_info' => $geocodeResult,
                    'quality_check' => $qualityCheck
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menemukan koordinat untuk alamat tersebut'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error geocoding toko: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat melakukan geocoding'
            ], 500);
        }
    }

    /**
     * Batch geocoding untuk semua toko yang belum memiliki koordinat atau kualitas rendah
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchGeocodeToko(Request $request)
    {
        try {
            // Ambil toko yang perlu di-geocode (belum ada koordinat atau kualitas rendah)
            $tokosToGeocode = Toko::where(function($query) {
                $query->whereNull('latitude')
                      ->orWhereNull('longitude')
                      ->orWhereIn('geocoding_quality', ['poor', 'very poor', 'failed', null]);
            })->get();

            if ($tokosToGeocode->isEmpty()) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Semua toko sudah memiliki koordinat GPS berkualitas baik',
                    'summary' => [
                        'total_processed' => 0,
                        'success_count' => 0,
                        'failed_count' => 0,
                        'improved_count' => 0
                    ]
                ]);
            }

            Log::info("Starting batch geocoding for {$tokosToGeocode->count()} tokos");

            $successCount = 0;
            $failedCount = 0;
            $improvedCount = 0;
            $results = [];

            foreach ($tokosToGeocode as $toko) {
                $fullAddress = trim($toko->alamat . ', ' . 
                              $toko->wilayah_kelurahan . ', ' . 
                              $toko->wilayah_kecamatan . ', ' . 
                              $toko->wilayah_kota_kabupaten . ', Jawa Timur, Indonesia');

                Log::info("Batch geocoding: {$toko->toko_id} - {$toko->nama_toko}");

                $oldQuality = $toko->geocoding_quality;
                $geocodeResult = GeocodingService::geocodeAddress($fullAddress);
                
                if ($geocodeResult) {
                    $qualityCheck = GeocodingService::validateGeocodeQuality($geocodeResult);
                    
                    $toko->latitude = $geocodeResult['latitude'];
                    $toko->longitude = $geocodeResult['longitude'];
                    $toko->alamat_lengkap_geocoding = $geocodeResult['formatted_address'];
                    $toko->geocoding_provider = $geocodeResult['provider'];
                    $toko->geocoding_accuracy = $geocodeResult['accuracy'];
                    $toko->geocoding_confidence = $geocodeResult['confidence'] ?? null;
                    $toko->geocoding_quality = $qualityCheck['quality'];
                    $toko->geocoding_score = $qualityCheck['score'];
                    $toko->save();
                    
                    $successCount++;
                    
                    // Check if quality improved
                    if ($oldQuality && $qualityCheck['quality'] !== $oldQuality) {
                        $improvedCount++;
                    }
                    
                    $results[] = [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko,
                        'status' => 'success',
                        'coordinates' => [$geocodeResult['latitude'], $geocodeResult['longitude']],
                        'provider' => $geocodeResult['provider'],
                        'quality' => $qualityCheck['quality'],
                        'quality_score' => $qualityCheck['score'],
                        'old_quality' => $oldQuality,
                        'improved' => $oldQuality && $qualityCheck['quality'] !== $oldQuality
                    ];
                } else {
                    $failedCount++;
                    $results[] = [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko,
                        'status' => 'failed',
                        'coordinates' => null,
                        'old_quality' => $oldQuality
                    ];
                }

                // Delay untuk menghindari rate limiting
                usleep(600000); // 0.6 detik delay
            }

            Log::info("Batch geocoding completed. Success: {$successCount}, Failed: {$failedCount}, Improved: {$improvedCount}");

            return response()->json([
                'status' => 'success',
                'message' => "Batch geocoding selesai. Berhasil: {$successCount}, Gagal: {$failedCount}, Ditingkatkan: {$improvedCount}",
                'summary' => [
                    'total_processed' => count($tokosToGeocode),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'improved_count' => $improvedCount,
                    'success_rate' => round(($successCount / count($tokosToGeocode)) * 100, 1)
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error batch geocoding: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat melakukan batch geocoding'
            ], 500);
        }
    }

    /**
     * Get geocoding statistics and system info
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGeocodingStats()
    {
        try {
            // Stats dari service
            $serviceStats = GeocodingService::getGeocodingStats();
            
            // Stats dari database toko
            $tokoStats = [
                'total_toko' => Toko::count(),
                'with_coordinates' => Toko::whereNotNull('latitude')->whereNotNull('longitude')->count(),
                'without_coordinates' => Toko::whereNull('latitude')->orWhereNull('longitude')->count(),
                'quality_distribution' => [
                    'excellent' => Toko::where('geocoding_quality', 'excellent')->count(),
                    'good' => Toko::where('geocoding_quality', 'good')->count(),
                    'fair' => Toko::where('geocoding_quality', 'fair')->count(),
                    'poor' => Toko::where('geocoding_quality', 'poor')->count(),
                    'very_poor' => Toko::where('geocoding_quality', 'very poor')->count(),
                    'failed' => Toko::where('geocoding_quality', 'failed')->orWhereNull('geocoding_quality')->count()
                ],
                'provider_distribution' => [
                    'internal_database' => Toko::where('geocoding_provider', 'internal_database')->count(),
                    'google_maps' => Toko::where('geocoding_provider', 'google_maps')->count(),
                    'locationiq' => Toko::where('geocoding_provider', 'locationiq')->count(),
                    'opencage' => Toko::where('geocoding_provider', 'opencage')->count(),
                    'mapbox' => Toko::where('geocoding_provider', 'mapbox')->count(),
                    'here' => Toko::where('geocoding_provider', 'here')->count(),
                    'nominatim' => Toko::where('geocoding_provider', 'nominatim')->count(),
                    'fallback' => Toko::where('geocoding_provider', 'LIKE', '%fallback%')->count(),
                    'failed' => Toko::where('geocoding_provider', 'failed')->orWhereNull('geocoding_provider')->count()
                ],
                'average_quality_score' => round(Toko::whereNotNull('geocoding_score')->avg('geocoding_score'), 1),
                'in_malang_region' => Toko::whereNotNull('latitude')->whereNotNull('longitude')->get()->filter(function($toko) {
                    return GeocodingService::isInMalangRegion($toko->latitude, $toko->longitude);
                })->count()
            ];
            
            return response()->json([
                'status' => 'success',
                'service_stats' => $serviceStats,
                'toko_stats' => $tokoStats,
                'recommendations' => $this->getGeocodingRecommendations($tokoStats)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting geocoding stats: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistik geocoding'
            ], 500);
        }
    }

    /**
     * Get recommendations based on current geocoding statistics
     */
    private function getGeocodingRecommendations($stats)
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
    /**
     * Get detailed information about a specific toko's coordinates
     * 
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCoordinateDetails($id)
    {
        try {
            $toko = Toko::find($id);
            
            if (!$toko) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko tidak ditemukan'
                ], 404);
            }
            
            $details = [
                'toko_info' => [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'alamat' => $toko->alamat,
                    'wilayah' => $toko->wilayah_kelurahan . ', ' . $toko->wilayah_kecamatan . ', ' . $toko->wilayah_kota_kabupaten
                ],
                'coordinates' => [
                    'latitude' => $toko->latitude,
                    'longitude' => $toko->longitude,
                    'has_coordinates' => $toko->latitude && $toko->longitude
                ],
                'geocoding_info' => [
                    'provider' => $toko->geocoding_provider ?? 'unknown',
                    'accuracy' => $toko->geocoding_accuracy ?? 'unknown',
                    'quality' => $toko->geocoding_quality ?? 'unknown',
                    'quality_score' => $toko->geocoding_score ?? 0,
                    'confidence' => $toko->geocoding_confidence ?? 0,
                    'formatted_address' => $toko->alamat_lengkap_geocoding
                ],
                'validation' => []
            ];
            
            if ($toko->latitude && $toko->longitude) {
                $details['validation'] = [
                    'in_indonesia' => GeocodingService::isInIndonesia($toko->latitude, $toko->longitude),
                    'in_malang_region' => GeocodingService::isInMalangRegion($toko->latitude, $toko->longitude),
                    'coordinates_valid' => abs($toko->latitude) <= 90 && abs($toko->longitude) <= 180,
                    'google_maps_url' => "https://www.google.com/maps?q={$toko->latitude},{$toko->longitude}",
                    'distance_from_malang_center' => $this->calculateDistance($toko->latitude, $toko->longitude, -7.9666, 112.6326)
                ];
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $details
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting coordinate details: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil detail koordinat'
            ], 500);
        }
    }

    /**
     * Calculate distance between two coordinates in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c, 2);
    }

    /**
     * Validate and fix coordinates for all tokos
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateAllCoordinates()
    {
        try {
            $tokos = Toko::whereNotNull('latitude')->whereNotNull('longitude')->get();
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
                if (abs($toko->latitude) > 90 || abs($toko->longitude) > 180) {
                    $issues[] = 'Invalid coordinate range';
                } else {
                    $results['valid_coordinates']++;
                }
                
                // Check if in Indonesia
                if (!GeocodingService::isInIndonesia($toko->latitude, $toko->longitude)) {
                    $issues[] = 'Outside Indonesia';
                    $results['outside_indonesia']++;
                }
                
                // Check if in Malang region
                if (GeocodingService::isInMalangRegion($toko->latitude, $toko->longitude)) {
                    $results['in_malang_region']++;
                } else {
                    $issues[] = 'Outside Malang region';
                }
                
                if (!empty($issues)) {
                    $results['issues'][] = [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko,
                        'coordinates' => [$toko->latitude, $toko->longitude],
                        'issues' => $issues
                    ];
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Validasi koordinat selesai',
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error validating coordinates: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat validasi koordinat'
            ], 500);
        }
    }
}
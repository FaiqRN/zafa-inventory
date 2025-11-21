<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Services\TokoService;
use App\Helpers\MasterData\Toko\TokoHelper;
use App\Helpers\MasterData\Toko\WilayahHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TokoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
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
     * @param Request $request
     * @return JsonResponse
     */
    public function getData(Request $request): JsonResponse
    {
        $data = Toko::all();

        $response = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', fn($row) => '')
            ->rawColumns(['action'])
            ->make(true);

        return $this->withNoCacheHeaders($response);
    }

    /**
     * Generate a new toko code
     *
     * @return JsonResponse
     */
    public function generateKode(): JsonResponse
    {
        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'kode' => TokoHelper::generateKode()
        ]);
    }

    /**
     * Get all wilayah data (Kota/Kabupaten)
     *
     * @return JsonResponse
     */
    public function getWilayahKota(): JsonResponse
    {
        $result = WilayahHelper::getWilayahKota();

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $result['data']
        ]);
    }

    /**
     * Get kecamatan by kota ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getKecamatanByKota(Request $request): JsonResponse
    {
        $result = WilayahHelper::getKecamatanByKota($request->kota_id);

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $result['data']
        ]);
    }

    /**
     * Get kelurahan by kecamatan ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getKelurahanByKecamatan(Request $request): JsonResponse
    {
        $result = WilayahHelper::getKelurahanByKecamatan($request->kota_id, $request->kecamatan_id);

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $result['data']
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            TokoHelper::getStoreValidationRules(),
            TokoHelper::getValidationMessages()
        );

        if ($validator->fails()) {
            return $this->jsonValidationError($validator);
        }

        $result = TokoService::store($request->all());

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache(
                $result['message'],
                $result['status_code'],
                $result['coordinate_info'] ?? null
            );
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result['data'],
            'coordinate_info' => $result['coordinate_info']
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $toko = Toko::find($id);

        if (!$toko) {
            return $this->jsonNotFound('Data toko tidak ditemukan');
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $toko
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function edit(string $id): JsonResponse
    {
        $toko = Toko::find($id);

        if (!$toko) {
            return $this->jsonNotFound('Data toko tidak ditemukan');
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $toko
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $toko = Toko::find($id);

        if (!$toko) {
            return $this->jsonNotFound('Data toko tidak ditemukan');
        }

        $validator = Validator::make(
            $request->all(),
            TokoHelper::getUpdateValidationRules(),
            TokoHelper::getValidationMessages()
        );

        if ($validator->fails()) {
            return $this->jsonValidationError($validator);
        }

        $result = TokoService::update($toko, $request->all());

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache(
                $result['message'],
                $result['status_code'],
                $result['coordinate_info'] ?? null
            );
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result['data'],
            'coordinate_info' => $result['coordinate_info']
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $toko = Toko::find($id);

        if (!$toko) {
            return $this->jsonNotFound('Data toko tidak ditemukan');
        }

        $result = TokoService::destroy($toko);

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'message' => $result['message']
        ]);
    }

    /**
     * Get toko list for AJAX calls
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getList(Request $request): JsonResponse
    {
        try {
            $data = Toko::select([
                Toko::FIELD_TOKO_ID,
                Toko::FIELD_NAMA_TOKO,
                Toko::FIELD_PEMILIK,
                Toko::FIELD_ALAMAT,
                Toko::FIELD_WILAYAH_KECAMATAN,
                Toko::FIELD_WILAYAH_KELURAHAN,
                Toko::FIELD_WILAYAH_KOTA_KABUPATEN,
                Toko::FIELD_NOMER_TELPON,
                Toko::FIELD_LATITUDE,
                Toko::FIELD_LONGITUDE,
                Toko::FIELD_IS_ACTIVE,
                Toko::FIELD_GEOCODING_PROVIDER,
                Toko::FIELD_GEOCODING_QUALITY,
                Toko::FIELD_GEOCODING_SCORE,
                Toko::FIELD_GEOCODING_CONFIDENCE
            ])
            ->orderBy(Toko::FIELD_CREATED_AT, 'desc')
            ->get();

            return $this->jsonSuccessWithNoCache([
                'status' => 'success',
                'data' => $data,
                'count' => $data->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getList: ' . $e->getMessage());

            return $this->jsonErrorWithNoCache(
                'Gagal memuat data toko: ' . $e->getMessage(),
                500,
                ['data' => []]
            );
        }
    }

    /**
     * Validate coordinates from interactive map
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateMapCoordinates(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            TokoHelper::getCoordinateValidationRules()
        );

        if ($validator->fails()) {
            return $this->jsonValidationError($validator, 'Koordinat tidak valid');
        }

        $result = TokoService::validateMapCoordinates(
            (float) $request->{Toko::FIELD_LATITUDE},
            (float) $request->{Toko::FIELD_LONGITUDE}
        );

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return response()->json([
            'status' => 'success',
            'validation' => $result['validation'],
            'address_info' => $result['address_info'],
            'message' => $result['message']
        ]);
    }

    /**
     * Preview geocoding for address input
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function previewGeocode(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            TokoHelper::getPreviewGeocodeValidationRules()
        );

        if ($validator->fails()) {
            return $this->jsonValidationError($validator, 'Alamat harus diisi');
        }

        $result = TokoService::previewGeocode($request->{Toko::FIELD_ALAMAT});

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'geocode_info' => $result['geocode_info']
        ]);
    }

    /**
     * Geocode existing toko address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function geocodeToko(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            TokoHelper::getGeocodeValidationRules()
        );

        if ($validator->fails()) {
            return $this->jsonValidationError($validator);
        }

        $toko = Toko::find($request->{Toko::FIELD_TOKO_ID});

        if (!$toko) {
            return $this->jsonNotFound('Toko tidak ditemukan');
        }

        $result = TokoService::geocodeToko($toko, $request->{Toko::FIELD_ALAMAT});

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result['data'],
            'geocode_info' => $result['geocode_info'],
            'quality_check' => $result['quality_check']
        ]);
    }

    /**
     * Batch geocoding for tokos without coordinates or low quality
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchGeocodeToko(Request $request): JsonResponse
    {
        $result = TokoService::batchGeocodeToko();

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return response()->json([
            'status' => $result['status'] ?? 'success',
            'message' => $result['message'],
            'summary' => $result['summary'],
            'results' => $result['results'] ?? []
        ]);
    }

    /**
     * Get geocoding statistics
     *
     * @return JsonResponse
     */
    public function getGeocodingStats(): JsonResponse
    {
        $result = TokoService::getGeocodingStats();

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return response()->json([
            'status' => 'success',
            'service_stats' => $result['service_stats'],
            'toko_stats' => $result['toko_stats'],
            'recommendations' => $result['recommendations']
        ]);
    }

    /**
     * Get detailed information about a specific toko's coordinates
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getCoordinateDetails(string $id): JsonResponse
    {
        $toko = Toko::find($id);

        if (!$toko) {
            return $this->jsonNotFound('Toko tidak ditemukan');
        }

        $result = TokoService::getCoordinateDetails($toko);

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $result['data']
        ]);
    }

    /**
     * Validate and fix coordinates for all tokos
     *
     * @return JsonResponse
     */
    public function validateAllCoordinates(): JsonResponse
    {
        $result = TokoService::validateAllCoordinates();

        if (!$result['success']) {
            return $this->jsonErrorWithNoCache($result['message'], $result['status_code']);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'results' => $result['results']
        ]);
    }

    // ===== PRIVATE HELPER METHODS =====

    /**
     * Add no-cache headers to response
     *
     * @param mixed $response
     * @return JsonResponse
     */
    private function withNoCacheHeaders($response): JsonResponse
    {
        return $response
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Return JSON success response with no-cache headers
     *
     * @param array $data
     * @param int $statusCode
     * @return JsonResponse
     */
    private function jsonSuccessWithNoCache(array $data, int $statusCode = 200): JsonResponse
    {
        return response()->json($data, $statusCode)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Return JSON error response with no-cache headers
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $additionalData
     * @return JsonResponse
     */
    private function jsonErrorWithNoCache(string $message, int $statusCode = 400, ?array $additionalData = null): JsonResponse
    {
        $data = [
            'status' => 'error',
            'message' => $message
        ];

        if ($additionalData) {
            $data = array_merge($data, $additionalData);
        }

        return response()->json($data, $statusCode)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Return JSON validation error response
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param string|null $message
     * @return JsonResponse
     */
    private function jsonValidationError($validator, ?string $message = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);
    }

    /**
     * Return JSON not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    private function jsonNotFound(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], 404);
    }

    /**
     * Get kelurahan coordinates data for smart address parsing
     *
     * @return JsonResponse
     */
    public function getKelurahanCoordinates(): JsonResponse
    {
        try {
            $kelurahan = \App\Models\KelurahanCoordinate::active()->get();
            
            // Format data untuk JavaScript
            $formattedData = [];
            foreach ($kelurahan as $item) {
                $key = $item->nama_normalized;
                $formattedData[$key] = [
                    'coords' => [$item->latitude, $item->longitude],
                    'kecamatan' => $item->kecamatan,
                    'kota' => $item->kota,
                    'nama' => $item->nama
                ];
            }
            
            return $this->jsonSuccessWithNoCache([
                'kelurahan_database' => $formattedData,
                'total' => count($formattedData)
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting kelurahan coordinates: ' . $e->getMessage());
            return $this->jsonErrorWithNoCache('Gagal mengambil data kelurahan coordinates');
        }
    }

    /**
     * Search kelurahan by keyword
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchKelurahan(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword', '');
            $limit = $request->input('limit', 10);
            
            if (empty($keyword)) {
                return $this->jsonErrorWithNoCache('Keyword tidak boleh kosong');
            }
            
            $results = \App\Models\KelurahanCoordinate::searchKelurahan($keyword, $limit);
            
            return $this->jsonSuccessWithNoCache([
                'results' => $results,
                'total' => $results->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching kelurahan: ' . $e->getMessage());
            return $this->jsonErrorWithNoCache('Gagal mencari kelurahan');
        }
    }

    /**
     * Get kelurahan coordinate by normalized name
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getKelurahanByName(Request $request): JsonResponse
    {
        try {
            $nama = $request->input('nama', '');
            
            if (empty($nama)) {
                return $this->jsonErrorWithNoCache('Nama kelurahan tidak boleh kosong');
            }
            
            $kelurahan = \App\Models\KelurahanCoordinate::findByNormalizedName($nama);
            
            if (!$kelurahan) {
                return $this->jsonNotFound('Kelurahan tidak ditemukan');
            }
            
            return $this->jsonSuccessWithNoCache([
                'kelurahan' => [
                    'nama' => $kelurahan->nama,
                    'kecamatan' => $kelurahan->kecamatan,
                    'kota' => $kelurahan->kota,
                    'latitude' => $kelurahan->latitude,
                    'longitude' => $kelurahan->longitude,
                    'coords' => $kelurahan->coordinates
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting kelurahan by name: ' . $e->getMessage());
            return $this->jsonErrorWithNoCache('Gagal mengambil data kelurahan');
        }
    }
}

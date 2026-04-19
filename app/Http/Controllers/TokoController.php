<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Services\TokoService;
use App\Services\TokoCacheService;
use App\Services\NominatimService;
use App\Services\OverpassService;
use App\Helpers\MasterData\Toko\TokoHelper;
use App\Helpers\MasterData\Toko\WilayahHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TokoController extends Controller
{
    public function __construct(
        protected NominatimService $nominatim,
        protected OverpassService $overpass
    ) {
        $this->middleware('can:view-toko')->only([
            'index',
            'getData',
            'getList',
            'show',
            'getCoordinateDetails',
            'getWilayahKota',
            'getKecamatanByKota',
            'getKelurahanByKecamatan',
            'validateMapCoordinates',
            'getGeocodingStats',
            'validateAllCoordinates',
            'searchAddress',
            'reverseGeocode',
            'getBoundary',
        ]);
        $this->middleware('can:create-toko')->only(['generateKode', 'store']);
        $this->middleware('can:edit-toko')->only(['edit', 'update']);
        $this->middleware('can:delete-toko')->only(['destroy']);
    }

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

    public function getData(Request $request): JsonResponse
    {
        $data = TokoCacheService::getAllToko();

        $response = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('quality_badge', function($row) {
                $status = $row->geocoding_status;
                $score = $row->{Toko::FIELD_GEOCODING_SCORE};
                
                $badgeHtml = '<span class="badge badge-' . $status['badge_class'] . '" 
                    data-toggle="tooltip" 
                    data-placement="top" 
                    title="Quality Score: ' . ($score ?? 'N/A') . '">' . 
                    $status['message'] . 
                '</span>';
                
                return $badgeHtml;
            })
            ->addColumn('action', fn($row) => '')
            ->rawColumns(['quality_badge', 'action'])
            ->make(true);

        return $this->withNoCacheHeaders($response);
    }

    public function generateKode(): JsonResponse
    {
        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'kode' => TokoHelper::generateKode()
        ]);
    }

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

    public function show(string $id): JsonResponse
    {
        $toko = TokoCacheService::getTokoById($id);

        if (!$toko) {
            return $this->jsonNotFound('Data toko tidak ditemukan');
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $toko
        ]);
    }

    public function edit(string $id): JsonResponse
    {
        $toko = TokoCacheService::getTokoById($id);

        if (!$toko) {
            return $this->jsonNotFound('Data toko tidak ditemukan');
        }

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $toko
        ]);
    }

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

    public function getList(Request $request): JsonResponse
    {
        try {
            $data = TokoCacheService::getTokoList();

            return $this->jsonSuccessWithNoCache([
                'status' => 'success',
                'data' => $data,
                'count' => $data->count()
            ]);

        } catch (\Throwable $e) {
            Log::error('Error in getList: ' . $e->getMessage());

            return $this->jsonErrorWithNoCache(
                'Gagal memuat data toko: ' . $e->getMessage(),
                500,
                ['data' => []]
            );
        }
    }

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

        return $this->jsonSuccessWithNoCache([
            'status' => 'success',
            'data' => $result['data']
        ]);
    }

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

    private function withNoCacheHeaders($response): JsonResponse
    {
        return $response
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }


    private function jsonSuccessWithNoCache(array $data, int $statusCode = 200): JsonResponse
    {
        return response()->json($data, $statusCode)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

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

    private function jsonValidationError($validator, ?string $message = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);
    }

    private function jsonNotFound(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], 404);
    }

    public function searchAddress(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        try {
            $results = $this->nominatim->search($request->q, 8);

            return $this->jsonSuccessWithNoCache([
                'success' => true,
                'results' => $results,
                'count' => count($results)
            ]);
        } catch (\Exception $e) {
            Log::error('Address search error: ' . $e->getMessage());
            return $this->jsonErrorWithNoCache('Gagal mencari alamat', 500);
        }
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $result = $this->nominatim->reverse(
                (float) $request->lat,
                (float) $request->lon
            );

            if (!$result) {
                return $this->jsonErrorWithNoCache('Lokasi tidak ditemukan', 404);
            }

            return $this->jsonSuccessWithNoCache([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Reverse geocode error: ' . $e->getMessage());
            return $this->jsonErrorWithNoCache('Gagal mendapatkan informasi lokasi', 500);
        }
    }

    public function getBoundary(Request $request): JsonResponse
    {
        $request->validate([
            'osm_type' => ['required', 'string', 'in:relation,way,node'],
            'osm_id'   => ['required', 'integer', 'min:1'],
        ]);

        try {
            $boundary = $this->overpass->getBoundary(
                $request->osm_type,
                (int) $request->osm_id
            );

            if (!$boundary || ($boundary['type'] ?? '') === 'none') {
                return $this->jsonErrorWithNoCache('Batas wilayah tidak tersedia', 404);
            }

            return $this->jsonSuccessWithNoCache([
                'success' => true,
                'data' => $boundary
            ]);
        } catch (\Exception $e) {
            Log::error('Boundary fetch error: ' . $e->getMessage());
            return $this->jsonErrorWithNoCache('Gagal mengambil batas wilayah', 500);
        }
    }
}

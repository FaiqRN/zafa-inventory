<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Zscore;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\BarangToko;

class ZscoreSettingController extends Controller
{
    private const MSG_INVALID_TOKO_BARANG = 'Barang tidak terdaftar pada toko yang dipilih';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view-zscore-setting')->only(['index', 'getData', 'getBarangByToko']);
        $this->middleware('can:create-zscore-setting')->only(['store']);
        $this->middleware('can:edit-zscore-setting')->only(['edit', 'update']);
        $this->middleware('can:delete-zscore-setting')->only(['destroy']);
    }

    public function index()
    {
        $tokos = Toko::select(Toko::FIELD_TOKO_ID, Toko::FIELD_NAMA_TOKO)
            ->orderBy(Toko::FIELD_NAMA_TOKO)
            ->get();

        return view('zscore-setting.index', [
            'activemenu' => 'zscore-setting',
            'tokos' => $tokos,
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $request->validate([
            'toko_id' => 'required|exists:toko,toko_id',
            'barang_id' => 'required|exists:barang,barang_id',
        ]);

        if (!$this->isBarangMappedToToko($request->toko_id, $request->barang_id)) {
            return $this->invalidTokoBarangResponse();
        }

        try {
            $data = Zscore::forToko($request->toko_id)
                ->forBarang($request->barang_id)
                ->orderBy(Zscore::FIELD_SERVICE_LEVEL)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'toko_id' => 'required|exists:toko,toko_id',
            'barang_id' => 'required|exists:barang,barang_id',
            'label' => 'required|string|max:50',
            'service_level' => 'required|numeric|min:0|max:100',
            'z_score' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        if (!$this->isBarangMappedToToko($request->toko_id, $request->barang_id)) {
            return $this->invalidTokoBarangResponse();
        }

        try {
            $existing = Zscore::forToko($request->toko_id)
                ->forBarang($request->barang_id)
                ->where(Zscore::FIELD_SERVICE_LEVEL, $request->service_level)
                ->first();

            if ($existing) {
                $response = [
                    'success' => false,
                    'message' => 'Service level ' . $request->service_level . '% sudah ada untuk toko dan barang ini'
                ];
                $status = 400;
            } else {
                $data = Zscore::create([
                    Zscore::FIELD_TOKO_ID => $request->toko_id,
                    Zscore::FIELD_BARANG_ID => $request->barang_id,
                    Zscore::FIELD_LABEL => $request->label,
                    Zscore::FIELD_SERVICE_LEVEL => $request->service_level,
                    Zscore::FIELD_Z_SCORE => $request->z_score,
                    Zscore::FIELD_KETERANGAN => $request->keterangan,
                    Zscore::FIELD_USER_CREATE => $this->resolveCurrentUsername(),
                ]);

                $response = [
                    'success' => true,
                    'message' => 'Z-Score berhasil ditambahkan',
                    'data' => $data
                ];
                $status = 200;
            }

            return response()->json($response, $status);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id): JsonResponse
    {
        $request->validate([
            'toko_id' => 'required|exists:toko,toko_id',
            'barang_id' => 'required|exists:barang,barang_id',
        ]);

        if (!$this->isBarangMappedToToko($request->toko_id, $request->barang_id)) {
            return $this->invalidTokoBarangResponse();
        }

        try {
            $data = Zscore::forToko($request->toko_id)
                ->forBarang($request->barang_id)
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'toko_id' => 'required|exists:toko,toko_id',
            'barang_id' => 'required|exists:barang,barang_id',
            'label' => 'required|string|max:50',
            'service_level' => 'required|numeric|min:0|max:100',
            'z_score' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        if (!$this->isBarangMappedToToko($request->toko_id, $request->barang_id)) {
            return $this->invalidTokoBarangResponse();
        }

        try {
            $data = Zscore::forToko($request->toko_id)
                ->forBarang($request->barang_id)
                ->findOrFail($id);

            $hasDuplicate = false;
            if ($data->{Zscore::FIELD_SERVICE_LEVEL} != $request->service_level) {
                $existing = Zscore::forToko($request->toko_id)
                    ->forBarang($request->barang_id)
                    ->where(Zscore::FIELD_SERVICE_LEVEL, $request->service_level)
                    ->first();
                $hasDuplicate = $existing !== null;
            }

            if ($hasDuplicate) {
                $response = [
                    'success' => false,
                    'message' => 'Service level ' . $request->service_level . '% sudah ada untuk toko dan barang ini'
                ];
                $status = 400;
            } else {
                $data->update([
                    Zscore::FIELD_LABEL => $request->label,
                    Zscore::FIELD_SERVICE_LEVEL => $request->service_level,
                    Zscore::FIELD_Z_SCORE => $request->z_score,
                    Zscore::FIELD_KETERANGAN => $request->keterangan,
                    Zscore::FIELD_USER_UPDATE => $this->resolveCurrentUsername(),
                ]);

                $response = [
                    'success' => true,
                    'message' => 'Z-Score berhasil diperbarui',
                    'data' => $data
                ];
                $status = 200;
            }

            return response()->json($response, $status);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $request->validate([
            'toko_id' => 'required|exists:toko,toko_id',
            'barang_id' => 'required|exists:barang,barang_id',
        ]);

        if (!$this->isBarangMappedToToko($request->toko_id, $request->barang_id)) {
            return $this->invalidTokoBarangResponse();
        }

        try {
            $data = Zscore::forToko($request->toko_id)
                ->forBarang($request->barang_id)
                ->findOrFail($id);
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Z-Score berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBarangByToko(string $tokoId): JsonResponse
    {
        if (!Toko::where(Toko::FIELD_TOKO_ID, $tokoId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Toko tidak ditemukan'
            ], 404);
        }

        $barangList = BarangToko::query()
            ->join(Barang::TABLE, Barang::TABLE . '.' . Barang::FIELD_BARANG_ID, '=', BarangToko::TABLE . '.' . BarangToko::FIELD_BARANG_ID)
            ->where(BarangToko::TABLE . '.' . BarangToko::FIELD_TOKO_ID, $tokoId)
            ->orderBy(Barang::TABLE . '.' . Barang::FIELD_NAMA_BARANG)
            ->get([
                BarangToko::TABLE . '.' . BarangToko::FIELD_BARANG_ID . ' as barang_id',
                Barang::TABLE . '.' . Barang::FIELD_NAMA_BARANG . ' as nama_barang',
            ]);

        return response()->json([
            'success' => true,
            'data' => $barangList,
        ]);
    }

    private function isBarangMappedToToko(string $tokoId, string $barangId): bool
    {
        return BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->where(BarangToko::FIELD_BARANG_ID, $barangId)
            ->exists();
    }

    private function invalidTokoBarangResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => self::MSG_INVALID_TOKO_BARANG,
        ], 422);
    }

    private function resolveCurrentUsername(): string
    {
        $authIdentifier = Auth::id();

        if ($authIdentifier === null) {
            return 'system';
        }

        $username = User::query()
            ->where(User::FIELD_USERNAME, (string) $authIdentifier)
            ->value(User::FIELD_USERNAME);

        return $username !== null ? (string) $username : 'system';
    }
}

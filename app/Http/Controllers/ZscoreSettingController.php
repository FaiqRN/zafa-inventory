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
use App\Helpers\DashboardMonitorLogger;

class ZscoreSettingController extends Controller
{
    private const MSG_INVALID_TOKO_BARANG = 'Barang tidak terdaftar pada toko yang dipilih';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view-zscore-setting')->only(['index', 'getData', 'getBarangByToko']);
        $this->middleware('can:create-zscore-setting')->only(['store']);
        $this->middleware('can:edit-zscore-setting')->only(['edit', 'update', 'setActive']);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Service level ' . $request->service_level . '% sudah ada untuk toko dan barang ini'
                ], 400);
            }

            // FIX #3: Jika belum ada baris is_active=true untuk pasangan ini,
            // baris baru ini otomatis dijadikan aktif.
            $hasActive = Zscore::forToko($request->toko_id)
                ->forBarang($request->barang_id)
                ->where(Zscore::FIELD_IS_ACTIVE, true)
                ->exists();

            $data = Zscore::create([
                Zscore::FIELD_TOKO_ID       => $request->toko_id,
                Zscore::FIELD_BARANG_ID     => $request->barang_id,
                Zscore::FIELD_LABEL         => $request->label,
                Zscore::FIELD_SERVICE_LEVEL => $request->service_level,
                Zscore::FIELD_Z_SCORE       => $request->z_score,
                Zscore::FIELD_KETERANGAN    => $request->keterangan,
                Zscore::FIELD_IS_ACTIVE     => !$hasActive, // jadi aktif jika belum ada yang aktif
                Zscore::FIELD_USER_CREATE   => $this->resolveCurrentUsername(),
            ]);

            DashboardMonitorLogger::create('Z-Score Setting', "Tambah Z-Score SL {$request->service_level}% (label: {$request->label})", $data->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Z-Score berhasil ditambahkan' . (!$hasActive ? ' dan dijadikan aktif' : ''),
                'data' => $data
            ]);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Service level ' . $request->service_level . '% sudah ada untuk toko dan barang ini'
                ], 400);
            }

            $oldData = $data->toArray();
            $data->update([
                Zscore::FIELD_LABEL         => $request->label,
                Zscore::FIELD_SERVICE_LEVEL => $request->service_level,
                Zscore::FIELD_Z_SCORE       => $request->z_score,
                Zscore::FIELD_KETERANGAN    => $request->keterangan,
                Zscore::FIELD_USER_UPDATE   => $this->resolveCurrentUsername(),
            ]);

            DashboardMonitorLogger::update('Z-Score Setting', "Ubah Z-Score SL {$request->service_level}%", $oldData, $data->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Z-Score berhasil diperbarui',
                'data' => $data
            ]);
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

            // FIX #3: Jika baris yang dihapus adalah yang aktif,
            // otomatis aktifkan baris dengan service_level tertinggi berikutnya
            // (sebagai fallback yang aman).
            DashboardMonitorLogger::delete('Z-Score Setting', "Hapus Z-Score SL {$data->service_level}% (label: {$data->label})", $data->toArray(), $request);

            if ($data->{Zscore::FIELD_IS_ACTIVE}) {
                $data->delete();

                $nextActive = Zscore::forToko($request->toko_id)
                    ->forBarang($request->barang_id)
                    ->orderByDesc(Zscore::FIELD_SERVICE_LEVEL)
                    ->first();

                if ($nextActive) {
                    $nextActive->update([Zscore::FIELD_IS_ACTIVE => true]);
                }
            } else {
                $data->delete();
            }

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

    /**
     * FIX #3: Endpoint baru untuk memilih service level aktif per pasangan toko-barang.
     * Route: POST /zscore-setting/{id}/set-active
     */
    public function setActive(Request $request, $id): JsonResponse
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

            $data->setAsActive();

            DashboardMonitorLogger::update('Z-Score Setting', "Set aktif Z-Score SL {$data->service_level}% (label: {$data->label})", null, $data->fresh()->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Service level ' . $data->{Zscore::FIELD_SERVICE_LEVEL} . '% ('
                    . $data->{Zscore::FIELD_LABEL} . ') dijadikan aktif untuk perhitungan SS',
                'data' => $data->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status aktif: ' . $e->getMessage()
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

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

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

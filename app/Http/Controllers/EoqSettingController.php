<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\EoqBiayaPesanGlobal;
use App\Models\EoqBiayaPesanToko;
use App\Models\EoqBiayaSimpan;
use App\Models\Toko;
use App\Models\Barang;
use App\Helpers\DashboardMonitorLogger;

class EoqSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view-eoq-setting')->only([
            'index',
            'getBiayaPesanGlobal',
            'getBiayaPesanToko',
            'getBiayaSimpan',
        ]);
        $this->middleware('can:create-eoq-setting')->only([
            'storeBiayaPesanGlobal',
            'storeBiayaPesanToko',
            'storeBiayaSimpan',
        ]);
        $this->middleware('can:edit-eoq-setting')->only([
            'editBiayaPesanGlobal',
            'updateBiayaPesanGlobal',
            'editBiayaSimpan',
            'updateBiayaSimpan',
        ]);
        $this->middleware('can:delete-eoq-setting')->only([
            'destroyBiayaPesanGlobal',
            'destroyBiayaPesanToko',
            'destroyBiayaSimpan',
        ]);
    }

    /**
     * Display EOQ Settings page
     */
    public function index()
    {
        $tokos = Toko::select(Toko::FIELD_TOKO_ID, Toko::FIELD_NAMA_TOKO)
            ->orderBy(Toko::FIELD_NAMA_TOKO)
            ->get();
            
        $barangs = Barang::select(Barang::FIELD_BARANG_ID, Barang::FIELD_NAMA_BARANG)
            ->orderBy(Barang::FIELD_NAMA_BARANG)
            ->get();

        return view('eoq-setting.index', [
            'activemenu' => 'eoq-setting',
            'tokos' => $tokos,
            'barangs' => $barangs
        ]);
    }

    // ==========================================
    // BIAYA PESAN GLOBAL
    // ==========================================

    public function getBiayaPesanGlobal(): JsonResponse
    {
        try {
            $data = EoqBiayaPesanGlobal::orderBy(EoqBiayaPesanGlobal::FIELD_ID)->get();
            $total = EoqBiayaPesanGlobal::getTotalBiayaPesan();

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeBiayaPesanGlobal(Request $request): JsonResponse
    {
        $request->validate([
            'nama_biaya' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        try {
            $data = EoqBiayaPesanGlobal::create([
                EoqBiayaPesanGlobal::FIELD_NAMA_BIAYA => $request->nama_biaya,
                EoqBiayaPesanGlobal::FIELD_NOMINAL => $request->nominal,
                EoqBiayaPesanGlobal::FIELD_KETERANGAN => $request->keterangan,
                EoqBiayaPesanGlobal::FIELD_USER_CREATE => Auth::id(),
            ]);

            DashboardMonitorLogger::create('EOQ Biaya Pesan Global', "Tambah biaya pesan: {$request->nama_biaya}", $data->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Biaya pesan berhasil ditambahkan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editBiayaPesanGlobal($id): JsonResponse
    {
        try {
            $data = EoqBiayaPesanGlobal::findOrFail($id);
            
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

    public function updateBiayaPesanGlobal(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nama_biaya' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        try {
            $data = EoqBiayaPesanGlobal::findOrFail($id);
            $oldData = $data->toArray();
            $data->update([
                EoqBiayaPesanGlobal::FIELD_NAMA_BIAYA => $request->nama_biaya,
                EoqBiayaPesanGlobal::FIELD_NOMINAL => $request->nominal,
                EoqBiayaPesanGlobal::FIELD_KETERANGAN => $request->keterangan,
                EoqBiayaPesanGlobal::FIELD_USER_UPDATE => Auth::id(),
            ]);

            DashboardMonitorLogger::update('EOQ Biaya Pesan Global', "Ubah biaya pesan: {$request->nama_biaya}", $oldData, $data->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Biaya pesan berhasil diperbarui',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyBiayaPesanGlobal($id): JsonResponse
    {
        try {
            $data = EoqBiayaPesanGlobal::findOrFail($id);
            DashboardMonitorLogger::delete('EOQ Biaya Pesan Global', "Hapus biaya pesan: {$data->nama_biaya}", $data->toArray());
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Biaya pesan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // BIAYA PESAN TOKO
    // ==========================================

    public function getBiayaPesanToko($tokoId): JsonResponse
    {
        try {
            $globalBiaya = EoqBiayaPesanGlobal::orderBy(EoqBiayaPesanGlobal::FIELD_NAMA_BIAYA)->get();
            $tokoBiaya = EoqBiayaPesanToko::where(EoqBiayaPesanToko::FIELD_TOKO_ID, $tokoId)
                ->get()
                ->keyBy(EoqBiayaPesanToko::FIELD_NAMA_BIAYA);

            $data = [];
            foreach ($globalBiaya as $global) {
                $namaBiaya = $global->{EoqBiayaPesanGlobal::FIELD_NAMA_BIAYA};
                $override = $tokoBiaya->get($namaBiaya);
                
                $data[] = [
                    'nama_biaya' => $namaBiaya,
                    'nominal_global' => $global->{EoqBiayaPesanGlobal::FIELD_NOMINAL},
                    'nominal_toko' => $override ? $override->{EoqBiayaPesanToko::FIELD_NOMINAL} : null,
                    'is_override' => $override !== null,
                    'override_id' => $override ? $override->{EoqBiayaPesanToko::FIELD_ID} : null,
                    'keterangan' => $override ? $override->{EoqBiayaPesanToko::FIELD_KETERANGAN} : null
                ];
            }

            $total = EoqBiayaPesanToko::getTotalBiayaPesanForToko($tokoId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeBiayaPesanToko(Request $request): JsonResponse
    {
        $request->validate([
            'toko_id' => 'required|exists:toko,toko_id',
            'nama_biaya' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        try {
            $globalBiaya = EoqBiayaPesanGlobal::where(
                EoqBiayaPesanGlobal::FIELD_NAMA_BIAYA, 
                $request->nama_biaya
            )->first();

            if (!$globalBiaya) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama biaya tidak ditemukan di biaya global'
                ], 400);
            }

            $data = EoqBiayaPesanToko::updateOrCreate(
                [
                    EoqBiayaPesanToko::FIELD_TOKO_ID => $request->toko_id,
                    EoqBiayaPesanToko::FIELD_NAMA_BIAYA => $request->nama_biaya,
                ],
                [
                    EoqBiayaPesanToko::FIELD_NOMINAL => $request->nominal,
                    EoqBiayaPesanToko::FIELD_KETERANGAN => $request->keterangan,
                    EoqBiayaPesanToko::FIELD_USER_UPDATE => Auth::id(),
                ]
            );

            DashboardMonitorLogger::create('EOQ Biaya Pesan Toko', "Override biaya pesan toko {$request->toko_id}: {$request->nama_biaya}", $data->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Override biaya pesan berhasil disimpan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyBiayaPesanToko($id): JsonResponse
    {
        try {
            $data = EoqBiayaPesanToko::findOrFail($id);
            DashboardMonitorLogger::delete('EOQ Biaya Pesan Toko', "Hapus override biaya pesan toko ID {$id}", $data->toArray());
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Override biaya pesan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // BIAYA SIMPAN
    // ==========================================

    public function getBiayaSimpan($barangId): JsonResponse
    {
        try {
            $data = EoqBiayaSimpan::where(EoqBiayaSimpan::FIELD_BARANG_ID, $barangId)
                ->orderBy(EoqBiayaSimpan::FIELD_ID)
                ->get();

            $totalPersentase = $data->sum(EoqBiayaSimpan::FIELD_PERSENTASE);
            $hargaPokok = $data->first()->{EoqBiayaSimpan::FIELD_HARGA_POKOK} ?? 0;
            $totalBiaya = EoqBiayaSimpan::getTotalBiayaSimpanForBarang($barangId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'total_persentase' => $totalPersentase,
                'harga_pokok' => $hargaPokok,
                'total_biaya' => $totalBiaya
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeBiayaSimpan(Request $request): JsonResponse
    {
        $request->validate([
            'barang_id' => 'required|exists:barang,barang_id',
            'harga_pokok' => 'required|numeric|min:0',
            'nama_komponen' => 'required|string|max:255',
            'persentase' => 'required|numeric|min:0|max:100',
            'keterangan' => 'nullable|string'
        ]);

        try {
            $data = EoqBiayaSimpan::create([
                EoqBiayaSimpan::FIELD_BARANG_ID => $request->barang_id,
                EoqBiayaSimpan::FIELD_HARGA_POKOK => $request->harga_pokok,
                EoqBiayaSimpan::FIELD_NAMA_KOMPONEN => $request->nama_komponen,
                EoqBiayaSimpan::FIELD_PERSENTASE => $request->persentase,
                EoqBiayaSimpan::FIELD_KETERANGAN => $request->keterangan,
                EoqBiayaSimpan::FIELD_USER_CREATE => Auth::id(),
            ]);

            DashboardMonitorLogger::create('EOQ Biaya Simpan', "Tambah komponen biaya simpan: {$request->nama_komponen}", $data->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Komponen biaya simpan berhasil ditambahkan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editBiayaSimpan($id): JsonResponse
    {
        try {
            $data = EoqBiayaSimpan::findOrFail($id);
            
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

    public function updateBiayaSimpan(Request $request, $id): JsonResponse
    {
        $request->validate([
            'harga_pokok' => 'required|numeric|min:0',
            'nama_komponen' => 'required|string|max:255',
            'persentase' => 'required|numeric|min:0|max:100',
            'keterangan' => 'nullable|string'
        ]);

        try {
            $data = EoqBiayaSimpan::findOrFail($id);
            $oldData = $data->toArray();
            $data->update([
                EoqBiayaSimpan::FIELD_HARGA_POKOK => $request->harga_pokok,
                EoqBiayaSimpan::FIELD_NAMA_KOMPONEN => $request->nama_komponen,
                EoqBiayaSimpan::FIELD_PERSENTASE => $request->persentase,
                EoqBiayaSimpan::FIELD_KETERANGAN => $request->keterangan,
                EoqBiayaSimpan::FIELD_USER_UPDATE => Auth::id(),
            ]);

            DashboardMonitorLogger::update('EOQ Biaya Simpan', "Ubah komponen biaya simpan: {$request->nama_komponen}", $oldData, $data->toArray(), $request);

            return response()->json([
                'success' => true,
                'message' => 'Komponen biaya simpan berhasil diperbarui',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyBiayaSimpan($id): JsonResponse
    {
        try {
            $data = EoqBiayaSimpan::findOrFail($id);
            DashboardMonitorLogger::delete('EOQ Biaya Simpan', "Hapus komponen biaya simpan: {$data->nama_komponen}", $data->toArray());
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Komponen biaya simpan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}


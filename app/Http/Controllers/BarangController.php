<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Helpers\MasterData\barang\BarangHelper;
use App\Helpers\MasterData\barang\BarangStokHelper;
use App\Services\BarangCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class BarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-barang')->only([
            'index',
            'getData',
            'getList',
            'getStockInfo',
            'validateStock',
            'getStokBarang',
        ]);
        $this->middleware('can:create-barang')->only(['generateKode', 'store']);
        $this->middleware('can:edit-barang')->only([
            'edit',
            'update',
            'storeStok',
            'editStok',
            'updateStok',
            'storeTambahStok',
        ]);
        $this->middleware('can:delete-barang')->only(['destroy']);
    }

    private function jsonResponse(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)
            ->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    private function errorResponse(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->jsonResponse($response, $status);
    }

    private function successResponse(string $message, $data = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->jsonResponse($response);
    }

    public function index()
    {
        return view('barang.index', [
            'activemenu' => 'barang',
            'breadcrumb' => (object) [
                'title' => 'Data Barang',
                'list' => ['Home', 'Master Data', 'Data Barang']
            ]
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $data = BarangCacheService::getAllBarang();
        
        $response = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', fn($row) => '')
            ->addColumn('available_stock', fn($row) => $row->available_stock)
            ->addColumn('stock_status', fn($row) => $row->stock_status)
            ->rawColumns(['action'])
            ->make(true);
            
        return $response->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    public function generateKode(): JsonResponse
    {
        return $this->jsonResponse([
            'success' => true,
            'kode' => Barang::generateBarangKode()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barang_kode' => 'required|string|max:20|unique:barang,barang_kode',
            'nama_barang' => 'required|string|max:100',
            'harga_awal_barang' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:20',
            'shelf_life' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $barangId = BarangHelper::generateUniqueBarangId();

        $barang = Barang::create([
            'barang_id' => $barangId,
            'barang_kode' => $validated['barang_kode'],
            'nama_barang' => $validated['nama_barang'],
            'harga_awal_barang' => $validated['harga_awal_barang'],
            'satuan' => $validated['satuan'],
            'shelf_life' => $validated['shelf_life'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        BarangCacheService::clearAllCache();

        return $this->successResponse(
            'Data barang berhasil ditambahkan',
            BarangHelper::getBarangById($barangId)
        );
    }

    public function edit(string $id): JsonResponse
    {
        $barang = BarangCacheService::getBarangById($id);
        
        if (!$barang) {
            return $this->errorResponse('Data barang tidak ditemukan', 404);
        }

        return $this->successResponse('', $barang);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $barang = Barang::where('barang_id', $id)->first();
        
        if (!$barang) {
            return $this->errorResponse('Data barang tidak ditemukan', 404);
        }

        $validated = $request->validate([
            'barang_kode' => 'required|string|max:20|unique:barang,barang_kode,' . $id . ',barang_id',
            'nama_barang' => 'required|string|max:100',
            'harga_awal_barang' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:20',
            'shelf_life' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $barang->update($validated);

        BarangCacheService::clearBarangCache($id);

        return $this->successResponse(
            'Data barang berhasil diperbarui',
            BarangHelper::getBarangById($id)
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $barang = Barang::where('barang_id', $id)->first();
        
        if (!$barang) {
            return $this->errorResponse('Data barang tidak ditemukan', 404);
        }

        $barang->delete();

        BarangCacheService::clearAllCache();

        return $this->successResponse('Data barang berhasil dihapus');
    }

    public function getList(Request $request): JsonResponse
    {
        $barangList = BarangCacheService::getAllBarang();
        
        return $this->jsonResponse([
            'status' => 'success',
            'data' => $barangList
        ]);
    }

    public function getStockInfo(string $id): JsonResponse
    {
        $barang = BarangHelper::getBarangById($id);
        
        if (!$barang) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        return $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'barang_id' => $barang->barang_id,
                'barang_kode' => $barang->barang_kode,
                'nama_barang' => $barang->nama_barang,
                'satuan' => $barang->satuan,
                'stock_details' => $barang->stock_details
            ]
        ]);
    }

    public function validateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barang_id' => 'required|string|exists:barang,barang_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $validation = BarangHelper::validateStockAvailability(
            $validated['barang_id'],
            $validated['quantity']
        );

        return $this->jsonResponse([
            'status' => 'success',
            'data' => $validation
        ]);
    }

    public function getStokBarang(string $id): JsonResponse
    {
        $barang = Barang::where('barang_id', $id)->first();
        
        if (!$barang) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        $stokData = DB::table('barang_stok')
            ->where('barang_id', $id)
            ->orderBy('tanggal_stock_barang', 'desc')
            ->get();

        return $this->jsonResponse([
            'status' => 'success',
            'data' => $stokData
        ]);
    }

    public function storeStok(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barang_id' => 'required|string|exists:barang,barang_id',
            'tanggal_stock_barang' => 'required|date',
            'stok' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            DB::table('barang_stok')->insert([
                'barang_id' => $validated['barang_id'],
                'tanggal_stock_barang' => $validated['tanggal_stock_barang'],
                'stok' => $validated['stok'],
                'sisa_stok' => $validated['stok'],
                'stok_awal' => $validated['stok'],
                'catatan' => $validated['catatan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            BarangCacheService::clearBarangCache($validated['barang_id']);

            $stokData = DB::table('barang_stok')
                ->where('barang_id', $validated['barang_id'])
                ->orderBy('tanggal_stock_barang', 'desc')
                ->get();

            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Stok barang berhasil ditambahkan',
                'data' => $stokData
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Gagal menambahkan stok: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editStok(string $id): JsonResponse
    {
        $stok = DB::table('barang_stok')->where('id', $id)->first();
        
        if (!$stok) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Data stok tidak ditemukan'
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => $stok
        ]);
    }

    public function updateStok(Request $request, string $id): JsonResponse
    {
        $stok = DB::table('barang_stok')->where('id', $id)->first();
        
        if (!$stok) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Data stok tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'barang_id' => 'required|string|exists:barang,barang_id',
            'tanggal_stock_barang' => 'required|date',
            'stok' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $oldStok = $stok->stok;
            $selisih = $validated['stok'] - $oldStok;
            $newSisaStok = max(0, $stok->sisa_stok + $selisih);

            DB::table('barang_stok')->where('id', $id)->update([
                'tanggal_stock_barang' => $validated['tanggal_stock_barang'],
                'stok' => $validated['stok'],
                'stok_awal' => $validated['stok'],
                'sisa_stok' => $newSisaStok,
                'catatan' => $validated['catatan'] ?? null,
                'updated_at' => now(),
            ]);

            DB::commit();

            BarangCacheService::clearBarangCache($validated['barang_id']);

            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Stok barang berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Gagal memperbarui stok: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeTambahStok(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'required|date|before_or_equal:today',
            'catatan' => 'nullable|string|max:255',
        ], [
            'jumlah.required' => 'Jumlah stok harus diisi',
            'jumlah.min' => 'Jumlah stok minimal 1',
            'tanggal.required' => 'Tanggal stok harus diisi',
            'tanggal.before_or_equal' => 'Tanggal stok tidak boleh melebihi hari ini',
        ]);

        $result = BarangStokHelper::tambahStok(
            $id,
            $validated['jumlah'],
            $validated['tanggal'],
            $validated['catatan'] ?? null
        );

        if ($result['success']) {
            BarangCacheService::clearBarangCache($id);
            
            return $this->successResponse($result['message'], $result['data']);
        }

        return $this->errorResponse($result['message'], 400);
    }

}

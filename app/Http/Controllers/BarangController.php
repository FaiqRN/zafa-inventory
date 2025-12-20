<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Helpers\MasterData\barang\BarangHelper;
use App\Helpers\MasterData\barang\BarangStokHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-barang');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Get all barang data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        // Get active barang with stock information
        $data = BarangHelper::getActiveBarang();
        
        $response = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return ''; // This will be handled by JavaScript
            })
            ->addColumn('available_stock', function($row) {
                return $row->available_stock;
            })
            ->addColumn('stock_status', function($row) {
                return $row->stock_status;
            })
            ->rawColumns(['action'])
            ->make(true);
            
        // Add no-cache headers
        return $response->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Generate a new barang code
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateKode()
    {
        $kode = Barang::generateBarangKode();
        
        return response()->json([
            'success' => true,
            'kode' => $kode
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
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
            'barang_kode' => 'required|string|max:20|unique:barang,barang_kode',
            'nama_barang' => 'required|string|max:100',
            'harga_awal_barang' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:20',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate unique barang_id using helper
        $barangId = BarangHelper::generateUniqueBarangId();

        // Create new barang with explicit values
        $barang = new Barang();
        $barang->barang_id = $barangId;
        $barang->barang_kode = $request->barang_kode;
        $barang->nama_barang = $request->nama_barang;
        $barang->harga_awal_barang = $request->harga_awal_barang;
        $barang->stok = 0;  // Default stok 0
        $barang->satuan = $request->satuan;
        $barang->keterangan = $request->keterangan;
        $barang->save();

        // Get barang with stock details
        $barangWithStock = BarangHelper::getBarangById($barangId);

        return response()->json([
            'success' => true,
            'message' => 'Data barang berhasil ditambahkan',
            'data' => $barangWithStock
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $barang = BarangHelper::getBarangById($id);
        
        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $barang
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
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
        $barang = Barang::where('barang_id', $id)->first();
        
        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'barang_kode' => 'required|string|max:20|unique:barang,barang_kode,' . $id . ',barang_id',
            'nama_barang' => 'required|string|max:100',
            'harga_awal_barang' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:20',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update data barang
        $barang->barang_kode = $request->barang_kode;
        $barang->nama_barang = $request->nama_barang;
        $barang->harga_awal_barang = $request->harga_awal_barang;
        $barang->satuan = $request->satuan;
        $barang->keterangan = $request->keterangan;
        $barang->save();

        // Get updated barang with stock details
        $barangWithStock = BarangHelper::getBarangById($id);

        return response()->json([
            'success' => true,
            'message' => 'Data barang berhasil diperbarui',
            'data' => $barangWithStock
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Delete the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $barang = Barang::where('barang_id', $id)->first();
        
        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        // Delete barang
        $barang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data barang berhasil dihapus'
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Get list of all barang with stock information for transaction modules
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        // Get all barang
        $barangList = Barang::orderBy('barang_kode', 'asc')
            ->get(['barang_id', 'barang_kode', 'nama_barang', 'harga_awal_barang', 'satuan', 'keterangan', 'stok']);
        
        return response()->json([
            'status' => 'success',
            'data' => $barangList
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Get detailed stock information for a specific barang
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStockInfo($id)
    {
        $barang = BarangHelper::getBarangById($id);
        
        if (!$barang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'barang_id' => $barang->barang_id,
                'barang_kode' => $barang->barang_kode,
                'nama_barang' => $barang->nama_barang,
                'satuan' => $barang->satuan,
                'stock_details' => $barang->stock_details
            ]
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Validate stock availability for transaction
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barang_id' => 'required|string|exists:barang,barang_id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $validation = BarangHelper::validateStockAvailability(
            $request->barang_id,
            $request->quantity
        );

        return response()->json([
            'status' => 'success',
            'data' => $validation
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Get stock history for a specific barang
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStokBarang($id)
    {
        $barang = Barang::where('barang_id', $id)->first();
        
        if (!$barang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        // Get stok records for this barang
        $stokData = DB::table('barang_stok')
            ->where('barang_id', $id)
            ->orderBy('tanggal_stock_barang', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $stokData
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Store new stock entry for barang
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeStok(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barang_id' => 'required|string|exists:barang,barang_id',
            'tanggal_stock_barang' => 'required|date',
            'stok' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Insert stok data
        $stokId = DB::table('barang_stok')->insertGetId([
            'barang_id' => $request->barang_id,
            'tanggal_stock_barang' => $request->tanggal_stock_barang,
            'stok' => $request->stok,
            'catatan' => $request->catatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update total stok di tabel barang
        $totalStok = DB::table('barang_stok')
            ->where('barang_id', $request->barang_id)
            ->sum('stok');

        Barang::where('barang_id', $request->barang_id)->update([
            'stok' => $totalStok,
            'tanggal_stock_barang' => $request->tanggal_stock_barang,
        ]);

        // Get updated stok data
        $stokData = DB::table('barang_stok')
            ->where('barang_id', $request->barang_id)
            ->orderBy('tanggal_stock_barang', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Stok barang berhasil ditambahkan',
            'data' => $stokData
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Get specific stock entry for editing
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function editStok($id)
    {
        $stok = DB::table('barang_stok')
            ->where('id', $id)
            ->first();
        
        if (!$stok) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data stok tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $stok
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Update stock entry
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStok(Request $request, $id)
    {
        $stok = DB::table('barang_stok')
            ->where('id', $id)
            ->first();
        
        if (!$stok) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data stok tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'barang_id' => 'required|string|exists:barang,barang_id',
            'tanggal_stock_barang' => 'required|date',
            'stok' => 'required|integer|min:1',
            'catatan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update stok data
        DB::table('barang_stok')
            ->where('id', $id)
            ->update([
                'tanggal_stock_barang' => $request->tanggal_stock_barang,
                'stok' => $request->stok,
                'catatan' => $request->catatan,
                'updated_at' => now(),
            ]);

        // Recalculate total stok
        $totalStok = DB::table('barang_stok')
            ->where('barang_id', $request->barang_id)
            ->sum('stok');

        Barang::where('barang_id', $request->barang_id)->update([
            'stok' => $totalStok,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stok barang berhasil diperbarui'
        ])->withHeaders(BarangHelper::getNoCacheHeaders());
    }

    /**
     * Show form tambah stok (FIFO)
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function tambahStok($id)
    {
        $barang = Barang::find($id);
        
        if (!$barang) {
            return redirect()->route('barang.index')->with('error', 'Data barang tidak ditemukan');
        }

        return view('barang.tambah-stok', [
            'activemenu' => 'barang',
            'barang' => $barang,
            'breadcrumb' => (object) [
                'title' => 'Tambah Stok',
                'list' => ['Home', 'Master Data', 'Data Barang', 'Tambah Stok']
            ]
        ]);
    }

    /**
     * Store tambah stok (FIFO)
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeTambahStok(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'required|date|before_or_equal:today',
            'catatan' => 'nullable|string|max:255',
        ], [
            'jumlah.required' => 'Jumlah stok harus diisi',
            'jumlah.min' => 'Jumlah stok minimal 1',
            'tanggal.required' => 'Tanggal stok harus diisi',
            'tanggal.before_or_equal' => 'Tanggal stok tidak boleh melebihi hari ini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = BarangStokHelper::tambahStok(
            $id,
            $request->jumlah,
            $request->tanggal,
            $request->catatan
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Show riwayat stok (FIFO)
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function riwayatStok($id)
    {
        $barang = Barang::find($id);
        
        if (!$barang) {
            return redirect()->route('barang.index')->with('error', 'Data barang tidak ditemukan');
        }

        $summary = BarangStokHelper::getStokSummary($id);

        return view('barang.riwayat-stok', [
            'activemenu' => 'barang',
            'barang' => $barang,
            'summary' => $summary,
            'breadcrumb' => (object) [
                'title' => 'Riwayat Stok',
                'list' => ['Home', 'Master Data', 'Data Barang', 'Riwayat Stok']
            ]
        ]);
    }

    /**
     * Get detail batch for DataTables
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailBatchDatatable($id)
    {
        $batches = BarangStokHelper::getDetailBatch($id);
        
        return DataTables::of($batches)
            ->addIndexColumn()
            ->make(true);
    }
}
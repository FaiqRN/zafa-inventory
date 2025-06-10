<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class BarangController extends Controller
{
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
        // Ambil HANYA data yang tidak terhapus (is_deleted = 0) dan urutkan berdasarkan kode barang
        $data = Barang::where('is_deleted', 0)->orderBy('barang_kode', 'asc')->get();
        
        $response = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return ''; // This will be handled by JavaScript
            })
            ->rawColumns(['action'])
            ->make(true);
            
        // Tambahkan header untuk mencegah caching
        return $response
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
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
            'status' => 'success',
            'kode' => $kode
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
            'barang_kode' => 'required|string|max:20|unique:barang,barang_kode,NULL,barang_id,is_deleted,0',
            'nama_barang' => 'required|string|max:100',
            'harga_awal_barang' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:20',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate barang_id unik
        $barangId = 'BRG' . strtoupper(Str::random(7));
        
        // Check if ID already exists and regenerate if needed
        while (Barang::find($barangId)) {
            $barangId = 'BRG' . strtoupper(Str::random(7));
        }

        // Tambah data barang baru
        $barang = new Barang();
        $barang->barang_id = $barangId;
        $barang->barang_kode = $request->barang_kode;
        $barang->nama_barang = $request->nama_barang;
        $barang->harga_awal_barang = $request->harga_awal_barang;
        $barang->satuan = $request->satuan;
        $barang->keterangan = $request->keterangan;
        $barang->is_deleted = 0;
        $barang->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang berhasil ditambahkan',
            'data' => $barang
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
        $barang = Barang::where('barang_id', $id)->where('is_deleted', 0)->first();
        
        if (!$barang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $barang
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
        $barang = Barang::where('barang_id', $id)->where('is_deleted', 0)->first();
        
        if (!$barang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'barang_kode' => 'required|string|max:20|unique:barang,barang_kode,' . $id . ',barang_id,is_deleted,0',
            'nama_barang' => 'required|string|max:100',
            'harga_awal_barang' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:20',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
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

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang berhasil diperbarui',
            'data' => $barang
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Soft delete the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $barang = Barang::where('barang_id', $id)->where('is_deleted', 0)->first();
        
        if (!$barang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        // Soft delete - update is_deleted flag
        $barang->is_deleted = 1;
        $barang->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang berhasil dihapus'
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    public function getList(Request $request)
    {
        // Ambil HANYA data yang tidak terhapus (is_deleted = 0) dan urutkan berdasarkan kode barang
        $data = Barang::where('is_deleted', 0)->orderBy('barang_kode', 'asc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Toko;
use App\Models\BarangToko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BarangTokoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $toko = Toko::orderBy('nama_toko', 'asc')->get();
        
        return view('barang-toko.index', [
            'activemenu' => 'barang-toko',
            'breadcrumb' => (object) [
                'title' => 'Barang per Toko',
                'list' => ['Home', 'Master Data', 'Barang per Toko']
            ],
            'toko' => $toko
        ]);
    }

    /**
     * Get list of barang that are available for a specific toko
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableBarang(Request $request)
    {
        $tokoId = $request->toko_id;
        
        // Get barang that are not yet assigned to the selected toko
        $barangList = Barang::whereNotIn('barang_id', function($query) use ($tokoId) {
                $query->select('barang_id')
                      ->from('barang_toko')
                      ->where('toko_id', $tokoId);
            })
            ->where('is_deleted', 0)
            ->orderBy('nama_barang', 'asc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $barangList
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Get list of barang-toko for a specific toko
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBarangToko(Request $request)
    {
        $tokoId = $request->toko_id;
        
        if (empty($tokoId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Toko ID tidak boleh kosong'
            ], 400);
        }
        
        // Get barang-toko data with barang details
        $barangTokoList = BarangToko::with('barang')
            ->where('toko_id', $tokoId)
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $barangTokoList
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Get details of a barang-toko for editing
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $barangToko = BarangToko::with('barang')->find($id);
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang per toko tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $barangToko
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    /**
     * Generate a new barang_toko_id
     * 
     * @return string
     */
    private function generateBarangTokoId()
    {
        $lastBarangToko = BarangToko::orderBy('barang_toko_id', 'desc')->first();
        
        if (!$lastBarangToko) {
            return 'BT001';
        }
        
        $lastId = $lastBarangToko->barang_toko_id;
        $prefix = 'BT';
        
        // Extract numeric part
        if (preg_match('/^BT(\d+)$/', $lastId, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            $nextId = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'BT001';
        }
        
        return $nextId;
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
            'toko_id' => 'required|string|exists:toko,toko_id',
            'barang_id' => 'required|string|exists:barang,barang_id',
            'harga_barang_toko' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if barang already exists for this toko
        $exists = BarangToko::where('toko_id', $request->toko_id)
                            ->where('barang_id', $request->barang_id)
                            ->exists();
        
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang ini sudah terdaftar untuk toko yang dipilih'
            ], 422);
        }

        // Generate unique barang_toko_id
        $barangTokoId = $this->generateBarangTokoId();
        
        // Tambah data barang-toko baru
        $barangToko = new BarangToko();
        $barangToko->barang_toko_id = $barangTokoId;
        $barangToko->toko_id = $request->toko_id;
        $barangToko->barang_id = $request->barang_id;
        $barangToko->harga_barang_toko = $request->harga_barang_toko;
        $barangToko->save();

        // Get barang details for response
        $barangToko = BarangToko::with('barang')->find($barangTokoId);

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang per toko berhasil ditambahkan',
            'data' => $barangToko
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
        $barangToko = BarangToko::find($id);
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang per toko tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'harga_barang_toko' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update data barang-toko
        $barangToko->harga_barang_toko = $request->harga_barang_toko;
        $barangToko->save();

        // Get updated barang-toko with barang details
        $barangToko = BarangToko::with('barang')->find($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang per toko berhasil diperbarui',
            'data' => $barangToko
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
        $barangToko = BarangToko::find($id);
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang per toko tidak ditemukan'
            ], 404);
        }

        // Delete barang-toko relation
        $barangToko->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang per toko berhasil dihapus'
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }
}
<?php

namespace App\Http\Controllers;

use App\Helpers\MasterData\BarangTokoHelper;
use App\Models\Barang;
use App\Models\Toko;
use App\Models\BarangToko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangTokoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-master-data');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $toko = BarangTokoHelper::getAllTokoOrdered();
        
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
        
        $barangList = BarangTokoHelper::getAvailableBarangForToko($tokoId);
        
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
        
        $barangTokoList = BarangTokoHelper::getBarangTokoByToko($tokoId);
        
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
        $barangToko = BarangTokoHelper::getBarangTokoById($id);
        
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make(
            $request->all(), 
            BarangTokoHelper::validateBarangTokoData($request->all())
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if barang already exists for this toko
        if (BarangTokoHelper::isBarangExistsForToko($request->toko_id, $request->barang_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang ini sudah terdaftar untuk toko yang dipilih'
            ], 422);
        }

        // Create new barang-toko
        $barangToko = BarangTokoHelper::createBarangToko([
            'toko_id' => $request->toko_id,
            'barang_id' => $request->barang_id,
            'harga_barang_toko' => $request->harga_barang_toko,
            'user_create' => auth()->user()->username ?? null,
        ]);

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
        $barangToko = BarangTokoHelper::getBarangTokoById($id);
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang per toko tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make(
            $request->all(), 
            BarangTokoHelper::validateBarangTokoData($request->all(), true)
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update data barang-toko
        $barangToko = BarangTokoHelper::updateBarangToko($barangToko, [
            'harga_barang_toko' => $request->harga_barang_toko,
            'user_update' => auth()->user()->username ?? null,
        ]);

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
        $barangToko = BarangTokoHelper::getBarangTokoById($id);
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang per toko tidak ditemukan'
            ], 404);
        }

        // Delete barang-toko relation
        BarangTokoHelper::deleteBarangToko($barangToko);

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang per toko berhasil dihapus'
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }
}
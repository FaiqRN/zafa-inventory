<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Helpers\MasterData\BarangToko\BarangTokoHelper;
use App\Helpers\MasterData\BarangToko\BarangTokoOperationHelper;
use App\Helpers\DashboardMonitorLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BarangTokoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-barang-toko')->only(['index', 'getBarangToko']);
        $this->middleware('can:create-barang-toko')->only(['getAvailableBarang', 'store']);
        $this->middleware('can:edit-barang-toko')->only(['edit', 'update']);
        $this->middleware('can:delete-barang-toko')->only(['destroy']);
    }

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

    public function store(Request $request)
    {
        try {
            $barangToko = BarangTokoOperationHelper::storeBarangToko([
                'toko_id' => $request->toko_id,
                'barang_id' => $request->barang_id,
                'harga_barang_toko' => $request->harga_barang_toko,
                'user_create' => AuditHelper::currentUsername(),
            ]);

            DashboardMonitorLogger::create('Barang Toko', "Tambah barang toko (toko: {$request->toko_id}, barang: {$request->barang_id})", $barangToko->toArray(), $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Data barang per toko berhasil ditambahkan',
                'data' => $barangToko
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $barangToko = BarangTokoHelper::getBarangTokoById($id);
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang per toko tidak ditemukan'
            ], 404);
        }

        try {
            $oldData = $barangToko->toArray();
            $barangToko = BarangTokoOperationHelper::updateBarangTokoData($barangToko, [
                'harga_barang_toko' => $request->harga_barang_toko,
                'user_update' => AuditHelper::currentUsername(),
            ]);

            DashboardMonitorLogger::update('Barang Toko', "Ubah harga barang toko ID {$id}", $oldData, $barangToko->toArray(), $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Data barang per toko berhasil diperbarui',
                'data' => $barangToko
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy($id)
    {
        $barangToko = BarangTokoHelper::getBarangTokoById($id);
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data barang per toko tidak ditemukan'
            ], 404);
        }

        DashboardMonitorLogger::delete('Barang Toko', "Hapus barang toko ID {$id}", $barangToko->toArray());

        BarangTokoOperationHelper::deleteBarangTokoData($barangToko);

        return response()->json([
            'status' => 'success',
            'message' => 'Data barang per toko berhasil dihapus'
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

}


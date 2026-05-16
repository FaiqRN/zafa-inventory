<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\BarangToko;
use App\Helpers\MasterData\pengiriman\PengirimanHelper;
use App\Services\PengirimanCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\DashboardMonitorLogger;
use Carbon\Carbon;

class PengirimanController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-pengiriman')->only([
            'index',
            'list',
            'show_ajax',
            'print',
        ]);
        $this->middleware('can:create-pengiriman')->only([
            'create_ajax',
            'get_nomer',
            'get_barang',
            'ajax',
        ]);
        $this->middleware('can:edit-pengiriman')->only(['update_status']);
    }

    public function index()
    {
        $toko = Toko::orderBy(Toko::FIELD_NAMA_TOKO, 'asc')->get();
        
        return view('pengiriman.index', [
            'toko' => $toko
        ]);
    }

    public function list(Request $request)
    {
        $query = Pengiriman::select('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status')
            ->groupBy('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status');
        
        if ($request->has('toko_id') && !empty($request->toko_id)) {
            $query->where(Pengiriman::FIELD_TOKO_ID, $request->toko_id);
        }
        
        if ($request->has('status') && !empty($request->status)) {
            $query->where(Pengiriman::FIELD_STATUS, $request->status);
        }
        
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '<=', $request->end_date);
        }
        
        if ($request->has('tanggal_mulai') && !empty($request->tanggal_mulai)) {
            $query->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '>=', $request->tanggal_mulai);
        }
        
        if ($request->has('tanggal_akhir') && !empty($request->tanggal_akhir)) {
            $query->whereDate(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, '<=', $request->tanggal_akhir);
        }
        
        $query->orderBy(Pengiriman::FIELD_TANGGAL_PENGIRIMAN, 'desc')
              ->orderBy(Pengiriman::FIELD_NOMER_PENGIRIMAN, 'desc');
        
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('toko_nama', function($row) {
                // Lazy load toko only when needed
                $toko = Toko::find($row->toko_id);
                return $toko ? $toko->{Toko::FIELD_NAMA_TOKO} : '';
            })
            ->addColumn('formatted_tanggal', function($row) {
                return Carbon::parse($row->{Pengiriman::FIELD_TANGGAL_PENGIRIMAN})->format('d/m/Y');
            })
            ->addColumn('total_jumlah', function($row) {
                return PengirimanHelper::getTotalJumlah($row->{Pengiriman::FIELD_NOMER_PENGIRIMAN});
            })
            ->addColumn('status_label', function($row) {
                $badges = [
                    'proses' => '<span class="badge badge-warning">Proses</span>',
                    'terkirim' => '<span class="badge badge-success">Terkirim</span>',
                    'batal' => '<span class="badge badge-danger">Batal</span>',
                ];
                return $badges[$row->{Pengiriman::FIELD_STATUS}] ?? '';
            })
            ->rawColumns(['status_label'])
            ->make(true);
    }

    public function create_ajax()
    {
        $toko = Toko::orderBy(Toko::FIELD_NAMA_TOKO, 'asc')->get();
        
        return view('pengiriman.create_ajax', [
            'toko' => $toko
        ]);
    }

    public function get_nomer()
    {
        return response()->json([
            'nomer_pengiriman' => PengirimanHelper::generateNomerPengiriman()
        ]);
    }

    public function get_barang(Request $request)
    {
        $barangList = PengirimanHelper::getBarangByToko($request->toko_id);
        
        return response()->json([
            'status' => 'success',
            'data' => $barangList
        ]);
    }

    public function ajax(Request $request)
    {
        Log::info('Pengiriman Request Data:', $request->all());
        
        $validator = Validator::make($request->all(), [
            'tanggal_pengiriman' => 'required|date',
            'toko_id' => 'required|exists:toko,toko_id',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barang,barang_id',
            'items.*.jumlah' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            Log::error('Validation Failed:', $validator->errors()->toArray());
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $result = PengirimanHelper::createPengiriman($request->all());
        
        if ($result['success']) {
            DashboardMonitorLogger::create('Pengiriman', "Tambah pengiriman {$result['nomer_pengiriman']}", $request->except('_token'), $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengiriman berhasil ditambahkan',
                'nomer_pengiriman' => $result['nomer_pengiriman']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 500);
        }
    }

    public function show_ajax($nomerPengiriman)
    {
        $pengiriman = PengirimanCacheService::getPengirimanByNomer($nomerPengiriman);
        
        if (!$pengiriman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengiriman tidak ditemukan'
            ], 404);
        }
        
        return view('pengiriman.show_ajax', [
            'pengiriman' => $pengiriman
        ]);
    }

    public function update_status(Request $request, $nomerPengiriman)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:proses,terkirim,batal'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $result = PengirimanHelper::updateStatus($nomerPengiriman, $request->status);
        
        if ($result['success']) {
            DashboardMonitorLogger::update('Pengiriman', "Update status pengiriman {$nomerPengiriman} menjadi {$request->status}", ['nomer_pengiriman' => $nomerPengiriman], ['status' => $request->status], $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Status berhasil diupdate'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ]);
        }
    }

    public function print($nomerPengiriman)
    {
        $pengiriman = PengirimanCacheService::getPengirimanByNomer($nomerPengiriman);
        
        if (!$pengiriman) {
            abort(404, 'Pengiriman tidak ditemukan');
        }
        
        return view('pengiriman.print', [
            'pengiriman' => $pengiriman
        ]);
    }
}


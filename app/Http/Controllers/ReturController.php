<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Helpers\DashboardMonitorLogger;
use App\Models\Retur;
use App\Models\Pengiriman;
use App\Models\Toko;
use App\Models\BarangToko;
use App\Services\ReturCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class ReturController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-retur')->only(['index', 'getData', 'show']);
        $this->middleware('can:create-retur')->only(['store']);
    }


    public function index()
    {
        $toko = Toko::orderBy('nama_toko', 'asc')->get();
        
        return view('retur.index', [
            'activemenu' => 'retur',
            'breadcrumb' => (object) [
                'title' => 'Retur Barang',
                'list' => ['Home', 'Transaksi', 'Retur Barang']
            ],
            'toko' => $toko,
        ]);
    }

    public function getData(Request $request)
    {
        $returSummary = Retur::query()
            ->select('nomer_pengiriman')
            ->selectRaw('MAX(tanggal_retur) as tanggal_retur_terbaru')
            ->groupBy('nomer_pengiriman');

        $query = Pengiriman::with(['toko'])
            ->leftJoinSub($returSummary, 'retur_summary', function ($join) {
                $join->on('pengiriman.nomer_pengiriman', '=', 'retur_summary.nomer_pengiriman');
            })
            ->select(
                'pengiriman.nomer_pengiriman',
                'pengiriman.tanggal_pengiriman',
                'pengiriman.toko_id',
                'pengiriman.status',
                'retur_summary.tanggal_retur_terbaru'
            )
            ->where('pengiriman.status', 'terkirim')
            ->groupBy(
                'pengiriman.nomer_pengiriman',
                'pengiriman.tanggal_pengiriman',
                'pengiriman.toko_id',
                'pengiriman.status',
                'retur_summary.tanggal_retur_terbaru'
            );
    
        if ($request->has('toko_id') && !empty($request->toko_id)) {
            $query->where('pengiriman.toko_id', $request->toko_id);
        }
    
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('pengiriman.tanggal_pengiriman', $request->date);
        }

        $query->orderByRaw('CASE WHEN retur_summary.tanggal_retur_terbaru IS NULL THEN 0 ELSE 1 END ASC')
              ->orderByDesc('retur_summary.tanggal_retur_terbaru')
              ->orderByDesc('pengiriman.tanggal_pengiriman')
              ->orderByDesc('pengiriman.nomer_pengiriman');
        
        $pengirimanData = $query->get();
        
        return DataTables::of($pengirimanData)
            ->addIndexColumn()
            ->addColumn('toko_nama', function($row) {
                return $row->toko->nama_toko;
            })
            ->addColumn('formatted_tanggal_pengiriman', function($row) {
                return Carbon::parse($row->tanggal_pengiriman)->format('d/m/Y');
            })
            ->addColumn('tanggal_retur', function($row) {
                if (!empty($row->tanggal_retur_terbaru)) {
                    return Carbon::parse($row->tanggal_retur_terbaru)->format('d/m/Y');
                }
                return '<span class="badge badge-warning">Belum Diisi</span>';
            })
            ->addColumn('action', function($row) {
                return '<button type="button" class="btn btn-sm btn-info btn-detail" data-nomer="'.$row->nomer_pengiriman.'" title="Detail">
                            <i class="fas fa-eye"></i> Detail
                        </button>';
            })
            ->rawColumns(['tanggal_retur', 'action'])
            ->make(true);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomer_pengiriman' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.pengiriman_id' => 'required|exists:pengiriman,pengiriman_id',
            'tanggal_retur' => 'nullable|date',
            'items.*.tanggal_retur' => 'nullable|date',
            'items.*.jumlah_retur' => 'required|integer|min:0',
            'items.*.kondisi' => 'required|string|max:50',
            'items.*.keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $globalTanggalRetur = $request->input('tanggal_retur');
        $missingTanggalRetur = collect($request->items)->contains(function ($item) use ($globalTanggalRetur) {
            return empty($globalTanggalRetur) && empty($item['tanggal_retur']);
        });

        if ($missingTanggalRetur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => [
                    'tanggal_retur' => ['Tanggal retur wajib diisi.']
                ]
            ], 422);
        }

        try {
            $nomerPengiriman = $request->nomer_pengiriman;
            $currentUser = AuditHelper::currentUsername();
            
            $existingRetur = Retur::where('nomer_pengiriman', $nomerPengiriman)
                ->where('is_locked', true)
                ->first();
            
            if ($existingRetur) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data retur sudah disimpan dan tidak dapat diubah lagi'
                ], 422);
            }
            
            Retur::where('nomer_pengiriman', $nomerPengiriman)->delete();
            
            $pengirimanIds = collect($request->items)->pluck('pengiriman_id')->toArray();
            $pengirimanList = Pengiriman::with(['barang'])
                ->whereIn('pengiriman_id', $pengirimanIds)
                ->get()
                ->keyBy('pengiriman_id');

            $hargaBarangTokoMap = BarangToko::whereIn(
                    BarangToko::FIELD_TOKO_ID,
                    $pengirimanList->pluck(Pengiriman::FIELD_TOKO_ID)->unique()->values()
                )
                ->whereIn(
                    BarangToko::FIELD_BARANG_ID,
                    $pengirimanList->pluck(Pengiriman::FIELD_BARANG_ID)->unique()->values()
                )
                ->get()
                ->mapWithKeys(function ($barangToko) {
                    $key = $barangToko->{BarangToko::FIELD_TOKO_ID} . '|' . $barangToko->{BarangToko::FIELD_BARANG_ID};

                    return [$key => (float) ($barangToko->{BarangToko::FIELD_HARGA_BARANG_TOKO} ?? 0)];
                });
            
            foreach ($request->items as $item) {
                $pengiriman = $pengirimanList->get($item['pengiriman_id']);
                
                if (!$pengiriman) {
                    continue;
                }
                
                if ($item['jumlah_retur'] > $pengiriman->jumlah_kirim) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jumlah retur untuk barang ' . $pengiriman->barang->nama_barang . ' melebihi jumlah kirim'
                    ], 422);
                }
                
                if ($item['jumlah_retur'] > 0 && $item['kondisi'] === 'Tidak Ada Retur') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jika jumlah retur lebih dari 0, kondisi tidak boleh "Tidak Ada Retur" untuk barang ' . $pengiriman->barang->nama_barang
                    ], 422);
                }

                $hargaMapKey = $pengiriman->toko_id . '|' . $pengiriman->barang_id;
                if (!$hargaBarangTokoMap->has($hargaMapKey)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Harga toko untuk barang ' . $pengiriman->barang->nama_barang . ' belum tersedia di data barang_toko'
                    ], 422);
                }

                $hargaBarangToko = (float) $hargaBarangTokoMap->get($hargaMapKey, 0);

                $tanggalRetur = !empty($item['tanggal_retur'])
                    ? $item['tanggal_retur']
                    : $globalTanggalRetur;
                if (empty($tanggalRetur)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Tanggal retur wajib diisi untuk semua barang'
                    ], 422);
                }
                
                $totalTerjual = $pengiriman->jumlah_kirim - $item['jumlah_retur'];
                $hasil = $totalTerjual * $hargaBarangToko;
                
                $retur = new Retur();
                $retur->pengiriman_id = $pengiriman->pengiriman_id;
                $retur->toko_id = $pengiriman->toko_id;
                $retur->barang_id = $pengiriman->barang_id;
                $retur->nomer_pengiriman = $pengiriman->nomer_pengiriman;
                $retur->tanggal_retur = $tanggalRetur;
                $retur->harga_awal_barang = $hargaBarangToko;
                $retur->jumlah_kirim = $pengiriman->jumlah_kirim;
                $retur->jumlah_retur = $item['jumlah_retur'];
                $retur->total_terjual = $totalTerjual;
                $retur->hasil = $hasil;
                $retur->kondisi = $item['kondisi'];
                $retur->keterangan = $item['keterangan'] ?? null;
                $retur->is_locked = true;
                $retur->user_create = $currentUser;
                $retur->user_update = $currentUser;
                $retur->save();
            }

            DashboardMonitorLogger::create('Retur', "Tambah retur pengiriman {$nomerPengiriman}", ['nomer_pengiriman' => $nomerPengiriman, 'total_items' => count($request->items)], $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Data retur berhasil disimpan dan dikunci'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in store retur: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($nomerPengiriman)
    {
        $pengiriman = Pengiriman::with(['toko', 'barang'])
            ->where('nomer_pengiriman', $nomerPengiriman)
            ->get();
        
        if ($pengiriman->isEmpty()) {
            abort(404, 'Data pengiriman tidak ditemukan');
        }
        
        $returData = ReturCacheService::getReturByNomer($nomerPengiriman);
        
        $isLocked = $returData->where('is_locked', true)->isNotEmpty();

        $tokoId = $pengiriman->first()->{Pengiriman::FIELD_TOKO_ID};
        $hargaBarangTokoMap = BarangToko::where(BarangToko::FIELD_TOKO_ID, $tokoId)
            ->whereIn(BarangToko::FIELD_BARANG_ID, $pengiriman->pluck(Pengiriman::FIELD_BARANG_ID)->unique()->values())
            ->pluck(BarangToko::FIELD_HARGA_BARANG_TOKO, BarangToko::FIELD_BARANG_ID);
        
        return view('retur.show_ajax', [
            'pengiriman' => $pengiriman,
            'returData' => $returData,
            'nomerPengiriman' => $nomerPengiriman,
            'isLocked' => $isLocked,
            'hargaBarangTokoMap' => $hargaBarangTokoMap,
            'canCreateRetur' => Gate::allows('create-retur'),
        ]);
    }
}


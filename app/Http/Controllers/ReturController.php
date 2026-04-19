<?php

namespace App\Http\Controllers;

use App\Models\Retur;
use App\Models\Pengiriman;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\BarangToko;
use App\Services\ReturCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturExport;
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
            'items.*.tanggal_retur' => 'required|date',
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

        try {
            $nomerPengiriman = $request->nomer_pengiriman;
            
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
                
                $hargaAwalBarang = $pengiriman->barang->harga_awal_barang ?? 0;
                
                $totalTerjual = $pengiriman->jumlah_kirim - $item['jumlah_retur'];
                $hasil = $totalTerjual * $hargaAwalBarang;
                
                $retur = new Retur();
                $retur->pengiriman_id = $pengiriman->pengiriman_id;
                $retur->toko_id = $pengiriman->toko_id;
                $retur->barang_id = $pengiriman->barang_id;
                $retur->nomer_pengiriman = $pengiriman->nomer_pengiriman;
                $retur->tanggal_retur = $item['tanggal_retur'];
                $retur->harga_awal_barang = $hargaAwalBarang;
                $retur->jumlah_kirim = $pengiriman->jumlah_kirim;
                $retur->jumlah_retur = $item['jumlah_retur'];
                $retur->total_terjual = $totalTerjual;
                $retur->hasil = $hasil;
                $retur->kondisi = $item['kondisi'];
                $retur->keterangan = $item['keterangan'] ?? null;
                $retur->is_locked = true;
                $retur->save();
            }
            
            ReturCacheService::clearReturCache($nomerPengiriman);

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
        
        return view('retur.show_ajax', [
            'pengiriman' => $pengiriman,
            'returData' => $returData,
            'nomerPengiriman' => $nomerPengiriman,
            'isLocked' => $isLocked,
            'canCreateRetur' => Gate::allows('create-retur'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Retur;
use App\Models\Pengiriman;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\BarangToko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturExport;
use Illuminate\Support\Facades\Log;

class ReturController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Get retur data for DataTables - menampilkan semua pengiriman yang terkirim.
     */
    public function getData(Request $request)
    {
        // Ambil semua pengiriman dengan status terkirim
        $query = Pengiriman::with(['toko'])
            ->select('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status')
            ->where('status', 'terkirim')
            ->groupBy('nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 'status');
    
        // Filter by toko_id if provided
        if ($request->has('toko_id') && !empty($request->toko_id)) {
            $query->where('toko_id', $request->toko_id);
        }
    
        // Filter by date range if provided
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('tanggal_pengiriman', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('tanggal_pengiriman', '<=', $request->end_date);
        }
    
        $query->orderBy('tanggal_pengiriman', 'desc')
              ->orderBy('nomer_pengiriman', 'desc');
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('toko_nama', function($row) {
                return $row->toko->nama_toko;
            })
            ->addColumn('formatted_tanggal_pengiriman', function($row) {
                return Carbon::parse($row->tanggal_pengiriman)->format('d/m/Y');
            })
            ->addColumn('tanggal_retur', function($row) {
                // Cek apakah sudah ada data retur
                $retur = Retur::where('nomer_pengiriman', $row->nomer_pengiriman)->first();
                if ($retur) {
                    return Carbon::parse($retur->tanggal_retur)->format('d/m/Y');
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



    /**
     * Store or update retur data for a pengiriman.
     */
    public function store(Request $request)
    {
        // Validasi input
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
            
            // Hapus data retur lama untuk nomer pengiriman ini
            Retur::where('nomer_pengiriman', $nomerPengiriman)->delete();
            
            // Simpan data retur baru
            foreach ($request->items as $item) {
                $pengiriman = Pengiriman::with(['barang'])->find($item['pengiriman_id']);
                
                if (!$pengiriman) {
                    continue;
                }
                
                // Validasi jumlah retur tidak melebihi jumlah kirim
                if ($item['jumlah_retur'] > $pengiriman->jumlah_kirim) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jumlah retur untuk barang ' . $pengiriman->barang->nama_barang . ' melebihi jumlah kirim'
                    ], 422);
                }
                
                // Ambil harga awal barang
                $hargaAwalBarang = $pengiriman->barang->harga_awal_barang ?? 0;
                
                // Hitung total terjual dan hasil
                $totalTerjual = $pengiriman->jumlah_kirim - $item['jumlah_retur'];
                $hasil = $totalTerjual * $hargaAwalBarang;
                
                // Simpan data retur
                $retur = new Retur();
                $retur->pengiriman_id = $pengiriman->pengiriman_id;
                $retur->toko_id = $pengiriman->toko_id;
                $retur->barang_id = $pengiriman->barang_id;
                $retur->nomer_pengiriman = $pengiriman->nomer_pengiriman;
                $retur->tanggal_pengiriman = $pengiriman->tanggal_pengiriman;
                $retur->tanggal_retur = $item['tanggal_retur'];
                $retur->harga_awal_barang = $hargaAwalBarang;
                $retur->jumlah_kirim = $pengiriman->jumlah_kirim;
                $retur->jumlah_retur = $item['jumlah_retur'];
                $retur->total_terjual = $totalTerjual;
                $retur->hasil = $hasil;
                $retur->kondisi = $item['kondisi'];
                $retur->keterangan = $item['keterangan'] ?? null;
                $retur->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data retur berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in store retur: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $nomerPengiriman
     * @return \Illuminate\Http\Response
     */
    public function show($nomerPengiriman)
    {
        // Ambil data pengiriman
        $pengiriman = Pengiriman::with(['toko', 'barang'])
            ->where('nomer_pengiriman', $nomerPengiriman)
            ->get();
        
        if ($pengiriman->isEmpty()) {
            abort(404, 'Data pengiriman tidak ditemukan');
        }
        
        // Ambil data retur jika sudah ada
        $returData = Retur::where('nomer_pengiriman', $nomerPengiriman)->get();
        
        return view('retur.show_ajax', [
            'pengiriman' => $pengiriman,
            'returData' => $returData,
            'nomerPengiriman' => $nomerPengiriman
        ]);
    }



/**
     * Export retur data to Excel/CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        try {
            // Log that we entered the export function
            Log::info('=== EXPORT FUNCTION CALLED ===');
            Log::info('Request URL: ' . $request->fullUrl());
            Log::info('Request method: ' . $request->method());
            Log::info('Request all params: ', $request->all());
            
            // Collect filter parameters
            $filters = [
                'toko_id' => $request->input('toko_id'),
                'barang_id' => $request->input('barang_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];
            
            // Log filters for debugging
            Log::info('Export filters:', $filters);
            
            // Get filename with date
            $date = date('Y-m-d_His');
            $filename = 'retur_barang_' . $date;
            
            // Check if we have data first
            $query = Retur::with(['toko', 'barang']);
            if (!empty($filters['toko_id'])) {
                $query->where('toko_id', $filters['toko_id']);
            }
            if (!empty($filters['barang_id'])) {
                $query->where('barang_id', $filters['barang_id']);
            }
            if (!empty($filters['start_date'])) {
                $query->whereDate('tanggal_retur', '>=', $filters['start_date']);
            }
            if (!empty($filters['end_date'])) {
                $query->whereDate('tanggal_retur', '<=', $filters['end_date']);
            }
            
            $dataCount = $query->count();
            Log::info('Data count before export: ' . $dataCount);
            
            // Create export instance
            $export = new ReturExport($filters);
            
            // Export based on format
            if ($request->has('format') && $request->format == 'csv') {
                Log::info('Exporting as CSV');
                return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV, [
                    'Content-Type' => 'text/csv',
                ]);
            } else {
                Log::info('Exporting as XLSX');
                return Excel::download($export, $filename . '.xlsx', \Maatwebsite\Excel\Excel::XLSX, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in export: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // For AJAX requests, return JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan saat export data: ' . $e->getMessage()
                ], 500);
            }
            
            // For non-AJAX requests, redirect back with error
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data: ' . $e->getMessage());
        }
    }
}
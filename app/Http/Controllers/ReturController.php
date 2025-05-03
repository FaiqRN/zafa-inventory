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
        $barang = Barang::orderBy('nama_barang', 'asc')->get();
        
        return view('retur.index', [
            'activemenu' => 'retur',
            'breadcrumb' => (object) [
                'title' => 'Retur Barang',
                'list' => ['Home', 'Transaksi', 'Retur Barang']
            ],
            'toko' => $toko,
            'barang' => $barang,
        ]);
    }

    /**
     * Get retur data for DataTables.
     */
    public function getData(Request $request)
    {
        $query = Retur::with(['toko', 'barang', 'pengiriman']);
    
        // Filter by toko_id if provided
        if ($request->has('toko_id') && !empty($request->toko_id)) {
            $query->where('toko_id', $request->toko_id);
        }
    
        // Filter by barang_id if provided
        if ($request->has('barang_id') && !empty($request->barang_id)) {
            $query->where('barang_id', $request->barang_id);
        }
    
        // Filter by date range if provided
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('tanggal_retur', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('tanggal_retur', '<=', $request->end_date);
        }
    
        $data = $query->orderBy('tanggal_retur', 'desc')->get();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('toko_nama', function($row) {
                return $row->toko->nama_toko;
            })
            ->addColumn('barang_nama', function($row) {
                return $row->barang->nama_barang;
            })
            ->addColumn('formatted_tanggal_retur', function($row) {
                return Carbon::parse($row->tanggal_retur)->format('d/m/Y');
            })
            ->addColumn('formatted_tanggal_pengiriman', function($row) {
                return Carbon::parse($row->tanggal_pengiriman)->format('d/m/Y');
            })
            ->addColumn('formatted_harga', function($row) {
                // Gunakan harga dari relasi barang jika harga_awal_barang di retur kosong
                $harga = $row->harga_awal_barang ?? ($row->barang->harga_awal_barang ?? 0);
                return 'Rp ' . number_format($harga, 0, ',', '.');
            })
            ->addColumn('formatted_hasil', function($row) {
                return 'Rp ' . number_format($row->hasil, 0, ',', '.');
            })
            ->addColumn('action', function($row) {
                return '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$row->retur_id.'" title="Detail">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->retur_id.'" data-nomer="'.$row->nomer_pengiriman.'" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    /**
     * Get available pengiriman for retur form.
     */
    public function getPengiriman(Request $request)
    {
        try {
            Log::info('Request filter for getPengiriman:', $request->all());
            
            // Get pengiriman yang belum diretur atau bisa diretur lagi
            $query = Pengiriman::with(['toko', 'barang'])
                ->where('status', 'terkirim'); // hanya pengiriman dengan status terkirim
            
            // Debug query
            Log::info('Base query count: ' . $query->count());
                
            // Filter by toko_id if provided
            if ($request->has('toko_id') && !empty($request->toko_id)) {
                $query->where('toko_id', $request->toko_id);
                Log::info('Filtered by toko_id: ' . $request->toko_id);
            }
            
            // Filter by barang_id if provided
            if ($request->has('barang_id') && !empty($request->barang_id)) {
                $query->where('barang_id', $request->barang_id);
                Log::info('Filtered by barang_id: ' . $request->barang_id);
            }
            
            // Debug filtered query
            Log::info('After filter query count: ' . $query->count());
            
            // Get pengiriman data
            $pengiriman = $query->get();
            
            $result = [];
            foreach ($pengiriman as $item) {
                // Debug each item
                Log::info("Processing pengiriman_id: {$item->pengiriman_id}, status: {$item->status}");
                
                // Create a new array to store the pengiriman data with additional properties
                $pengirimanData = [
                    'pengiriman_id' => $item->pengiriman_id,
                    'nomer_pengiriman' => $item->nomer_pengiriman,
                    'tanggal_pengiriman' => $item->tanggal_pengiriman,
                    'toko_id' => $item->toko_id,
                    'barang_id' => $item->barang_id,
                    'jumlah_kirim' => $item->jumlah_kirim,
                    'status' => $item->status,
                    'toko' => $item->toko,
                    'barang' => $item->barang,
                    'sisa_retur' => $item->jumlah_kirim,
                    'total_retur' => 0
                ];
                
                // Dapatkan harga barang dari relasi barang
                $pengirimanData['harga_barang'] = $item->barang->harga_awal_barang ?? 0;
                
                // Check total retur if needed for display only
                $totalRetur = Retur::where('pengiriman_id', $item->pengiriman_id)->sum('jumlah_retur');
                if ($totalRetur > 0) {
                    $pengirimanData['sisa_retur'] = $item->jumlah_kirim - $totalRetur;
                    $pengirimanData['total_retur'] = $totalRetur;
                }
                
                // Add to result even if fully returned (we'll handle this in UI)
                $result[] = $pengirimanData;
            }
            
            Log::info('Total result items: ' . count($result));
            
            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getPengiriman: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memuat data. ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'pengiriman_id' => 'required|string|exists:pengiriman,pengiriman_id',
            'tanggal_retur' => 'required|date',
            'jumlah_retur' => 'required|integer|min:1',
            'kondisi' => 'required|string|max:50',
            'keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Ambil data pengiriman dengan relasi barang
            $pengiriman = Pengiriman::with(['barang'])->find($request->pengiriman_id);
            if (!$pengiriman) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pengiriman tidak ditemukan'
                ], 404);
            }
            
            // Cek total retur yang sudah ada
            $totalRetur = Retur::where('pengiriman_id', $pengiriman->pengiriman_id)->sum('jumlah_retur');
            $sisaRetur = $pengiriman->jumlah_kirim - $totalRetur;
            
            // Validasi jumlah retur
            if ($request->jumlah_retur > $sisaRetur) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jumlah retur melebihi jumlah sisa yang bisa diretur (' . $sisaRetur . ')'
                ], 422);
            }
            
            // Ambil harga awal barang dari relasi barang
            $hargaAwalBarang = $pengiriman->barang->harga_awal_barang ?? 0;
            
            // Hitung total terjual dan hasil
            $totalTerjual = $pengiriman->jumlah_kirim - $request->jumlah_retur - $totalRetur;
            $hasil = $totalTerjual * $hargaAwalBarang;
            
            // Tambah data retur baru
            $retur = new Retur();
            $retur->pengiriman_id = $pengiriman->pengiriman_id;
            $retur->toko_id = $pengiriman->toko_id;
            $retur->barang_id = $pengiriman->barang_id;
            $retur->nomer_pengiriman = $pengiriman->nomer_pengiriman;
            $retur->tanggal_pengiriman = $pengiriman->tanggal_pengiriman;
            $retur->tanggal_retur = $request->tanggal_retur;
            $retur->harga_awal_barang = $hargaAwalBarang;
            $retur->jumlah_kirim = $pengiriman->jumlah_kirim;
            $retur->jumlah_retur = $request->jumlah_retur;
            $retur->total_terjual = $totalTerjual;
            $retur->hasil = $hasil;
            $retur->kondisi = $request->kondisi;
            $retur->keterangan = $request->keterangan;
            $retur->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Data retur berhasil ditambahkan',
                'data' => $retur
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
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $retur = Retur::with(['toko', 'barang', 'pengiriman'])->find($id);
        
        if (!$retur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data retur tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $retur
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $retur = Retur::find($id);
        
        if (!$retur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data retur tidak ditemukan'
            ], 404);
        }

        // Delete the retur
        $retur->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data retur berhasil dihapus'
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
        // Collect filter parameters
        $filters = [
            'toko_id' => $request->input('toko_id'),
            'barang_id' => $request->input('barang_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];
        
        // Get filename with date
        $date = date('Y-m-d_His');
        $filename = 'retur_barang_' . $date;
        
        // Export as Excel (xlsx) by default
        if ($request->has('format') && $request->format == 'csv') {
            return Excel::download(new ReturExport($filters), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } else {
            return Excel::download(new ReturExport($filters), $filename . '.xlsx');
        }
    }
}
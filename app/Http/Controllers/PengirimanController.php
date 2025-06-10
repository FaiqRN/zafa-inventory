<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\BarangToko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PengirimanExport;

class PengirimanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $toko = Toko::orderBy('nama_toko', 'asc')->get();
        
        return view('pengiriman.index', [
            'activemenu' => 'pengiriman',
            'breadcrumb' => (object) [
                'title' => 'Pengiriman Barang',
                'list' => ['Home', 'Transaksi', 'Pengiriman Barang']
            ],
            'toko' => $toko
        ]);
    }

    /**
     * Get pengiriman data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $query = Pengiriman::with(['toko', 'barang']);
    
        // Filter by toko_id if provided
        if ($request->has('toko_id') && !empty($request->toko_id)) {
            $query->where('toko_id', $request->toko_id);
        }
    
        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
    
        // Filter by date range if provided
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('tanggal_pengiriman', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('tanggal_pengiriman', '<=', $request->end_date);
        }
    
        // Sorting
        $sortColumn = $request->input('sort_column', 'tanggal_pengiriman');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Validasi kolom yang diizinkan untuk sorting untuk mencegah SQL injection
        $allowedColumns = [
            'nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 
            'barang_id', 'jumlah_kirim', 'status'
        ];
        
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'tanggal_pengiriman';
        }
        
        // Apply sorting
        $query->orderBy($sortColumn, $sortDirection);
    
        $data = $query->get();
        
        $response = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('toko_nama', function($row) {
                return $row->toko->nama_toko;
            })
            ->addColumn('barang_nama', function($row) {
                return $row->barang->nama_barang;
            })
            ->addColumn('formatted_tanggal', function($row) {
                return Carbon::parse($row->tanggal_pengiriman)->format('d/m/Y');
            })
            ->addColumn('status_label', function($row) {
                $status = $row->status;
                if ($status == 'proses') {
                    return '<span class="badge badge-warning">Proses</span>';
                } elseif ($status == 'terkirim') {
                    return '<span class="badge badge-success">Terkirim</span>';
                } else {
                    return '<span class="badge badge-danger">Batal</span>';
                }
            })
            ->addColumn('action', function($row) {
                return '';  // Will be handled by JavaScript
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
            
        // Add cache prevention headers
        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                         ->header('Pragma', 'no-cache')
                         ->header('Expires', '0');
    }

    /**
     * Get available barang for a specific toko.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBarangByToko(Request $request)
    {
        $tokoId = $request->toko_id;
        
        if (empty($tokoId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Toko ID tidak boleh kosong'
            ], 400);
        }
        
        // Get barang that are available for this toko (from barang_toko)
        $barangList = Barang::whereHas('barangToko', function($query) use ($tokoId) {
                $query->where('toko_id', $tokoId);
            })
            ->where('is_deleted', 0)
            ->orderBy('nama_barang', 'asc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $barangList
        ]);
    }

    /**
     * Generate new pengiriman number.
     *
     * @return string
     */
    private function generateNomerPengiriman()
    {
        $lastPengiriman = Pengiriman::orderBy('nomer_pengiriman', 'desc')->first();
        
        if (!$lastPengiriman) {
            return 'PNG001';
        }
        
        $lastNumber = $lastPengiriman->nomer_pengiriman;
        $prefix = 'PNG';
        
        // Extract numeric part
        if (preg_match('/^PNG(\d+)$/', $lastNumber, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            $nextNumber = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = 'PNG001';
        }
        
        return $nextNumber;
    }

    /**
     * Generate new pengiriman ID.
     *
     * @return string
     */
    private function generatePengirimanId()
    {
        $lastPengiriman = Pengiriman::orderBy('pengiriman_id', 'desc')->first();
        
        if (!$lastPengiriman) {
            return 'P001';
        }
        
        $lastId = $lastPengiriman->pengiriman_id;
        $prefix = 'P';
        
        // Extract numeric part
        if (preg_match('/^P(\d+)$/', $lastId, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            $nextId = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $nextId = 'P001';
        }
        
        return $nextId;
    }

    /**
     * Get nomer pengiriman (auto-generated).
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNomerPengiriman()
    {
        $nomerPengiriman = $this->generateNomerPengiriman();
        
        return response()->json([
            'status' => 'success',
            'nomer_pengiriman' => $nomerPengiriman
        ]);
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
            'nomer_pengiriman' => 'required|string|max:50|unique:pengiriman,nomer_pengiriman',
            'tanggal_pengiriman' => 'required|date',
            'jumlah_kirim' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Periksa apakah barang terdaftar untuk toko ini
        $barangToko = BarangToko::where('toko_id', $request->toko_id)
                                ->where('barang_id', $request->barang_id)
                                ->first();
        
        if (!$barangToko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang tidak terdaftar untuk toko ini'
            ], 422);
        }

        // Generate pengiriman_id
        $pengirimanId = $this->generatePengirimanId();
        
        // Tambah data pengiriman baru
        $pengiriman = new Pengiriman();
        $pengiriman->pengiriman_id = $pengirimanId;
        $pengiriman->toko_id = $request->toko_id;
        $pengiriman->barang_id = $request->barang_id;
        $pengiriman->nomer_pengiriman = $request->nomer_pengiriman;
        $pengiriman->tanggal_pengiriman = $request->tanggal_pengiriman;
        $pengiriman->jumlah_kirim = $request->jumlah_kirim;
        $pengiriman->status = 'proses'; // Default status: proses
        $pengiriman->save();

        // Ambil data pengiriman dengan relasi
        $pengiriman = Pengiriman::with(['toko', 'barang'])->find($pengirimanId);

        return response()->json([
            'status' => 'success',
            'message' => 'Data pengiriman berhasil ditambahkan',
            'data' => $pengiriman
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $pengiriman = Pengiriman::with(['toko', 'barang'])->find($id);
        
        if (!$pengiriman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengiriman tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $pengiriman
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $pengiriman = Pengiriman::with(['toko', 'barang'])->find($id);
        
        if (!$pengiriman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengiriman tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $pengiriman
        ]);
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
        $pengiriman = Pengiriman::find($id);
        
        if (!$pengiriman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengiriman tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'tanggal_pengiriman' => 'required|date',
            'jumlah_kirim' => 'required|integer|min:1',
            'status' => 'required|in:proses,terkirim,batal',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update data pengiriman
        $pengiriman->tanggal_pengiriman = $request->tanggal_pengiriman;
        $pengiriman->jumlah_kirim = $request->jumlah_kirim;
        $pengiriman->status = $request->status;
        $pengiriman->save();

        // Ambil data pengiriman dengan relasi
        $pengiriman = Pengiriman::with(['toko', 'barang'])->find($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data pengiriman berhasil diperbarui',
            'data' => $pengiriman
        ]);
    }

    /**
     * Update status of the pengiriman.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $pengiriman = Pengiriman::find($id);
        
        if (!$pengiriman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengiriman tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:proses,terkirim,batal',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update status pengiriman
        $pengiriman->status = $request->status;
        $pengiriman->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status pengiriman berhasil diperbarui',
            'data' => $pengiriman
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $pengiriman = Pengiriman::find($id);
        
        if (!$pengiriman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengiriman tidak ditemukan'
            ], 404);
        }

        // Check if there are any related returns before deleting
        if ($pengiriman->retur()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengiriman ini tidak dapat dihapus karena memiliki data retur terkait'
            ], 400);
        }

        // Delete the pengiriman
        $pengiriman->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data pengiriman berhasil dihapus'
        ]);
    }

    /**
     * Export pengiriman data to Excel/CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
/**
 * Export pengiriman data to Excel/CSV.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function export(Request $request)
{
    // Debug logs
    Log::info('Export request received', [
        'format' => $request->format,
        'filters' => $request->all()
    ]);
    
    try {
        // Collect filter parameters
        $filters = [
            'toko_id' => $request->input('toko_id'),
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'sort_column' => $request->input('sort_column', 'tanggal_pengiriman'),
            'sort_direction' => $request->input('sort_direction', 'desc'),
        ];
        
        // Buat instance kelas export 
        $export = new PengirimanExport($filters);
        
        // Verifikasi data tersedia - jika tidak ada data, berikan pesan yang lebih jelas
        $dataCount = $export->collection()->count();
        
        Log::info('Data untuk export: ' . $dataCount . ' record');
        
        if ($dataCount == 0) {
            return back()->with('error', 'Tidak ada data yang sesuai dengan filter untuk diekspor.');
        }
        
        // Get filename with date
        $date = date('Y-m-d_His');
        $filename = 'pengiriman_' . $date;
        
        // Export sesuai format yang diminta
        if ($request->format == 'csv') {
            Log::info('Exporting as CSV - ' . $dataCount . ' records');
            return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Log::info('Exporting as XLSX - ' . $dataCount . ' records');
            return Excel::download($export, $filename . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }
        
    } catch (\Exception $e) {
        Log::error('Export error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->with('error', 'Export gagal: ' . $e->getMessage());
    }
}

/**
 * Get list of pengiriman with pagination.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getList(Request $request)
{
    $query = Pengiriman::with(['toko', 'barang']);
    
    // Filter by toko_id if provided
    if ($request->has('toko_id') && !empty($request->toko_id)) {
        $query->where('toko_id', $request->toko_id);
    }

    // Filter by status if provided
    if ($request->has('status') && !empty($request->status)) {
        $query->where('status', $request->status);
    }

    // Filter by date range if provided
    if ($request->has('start_date') && !empty($request->start_date)) {
        $query->whereDate('tanggal_pengiriman', '>=', $request->start_date);
    }
    
    if ($request->has('end_date') && !empty($request->end_date)) {
        $query->whereDate('tanggal_pengiriman', '<=', $request->end_date);
    }
    
    // Sorting - pastikan default adalah tanggal terbaru ke terlama
    $sortColumn = $request->input('sort_column', 'tanggal_pengiriman');
    $sortDirection = $request->input('sort_direction', 'desc'); // Default desc untuk data terbaru
    
    // Validasi kolom yang diizinkan untuk sorting untuk mencegah SQL injection
    $allowedColumns = [
        'nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 
        'barang_id', 'jumlah_kirim', 'status'
    ];
    
    if (!in_array($sortColumn, $allowedColumns)) {
        $sortColumn = 'tanggal_pengiriman';
        $sortDirection = 'desc'; // Reset ke default jika kolom tidak valid
    }
    
    // Apply sorting - untuk konsistensi, tambah secondary sort berdasarkan ID
    $query->orderBy($sortColumn, $sortDirection);
    
    // Tambahkan secondary sorting berdasarkan pengiriman_id untuk konsistensi
    if ($sortColumn !== 'pengiriman_id') {
        $query->orderBy('pengiriman_id', 'desc');
    }
    
    // Pagination
    $perPage = 10;
    $page = $request->input('page', 1);
    $total = $query->count();
    
    // Get paginated results
    $result = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
    
    // Prepare response
    $response = [
        'data' => $result,
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => (int)$page,
        'last_page' => ceil($total / $perPage)
    ];
    
    return response()->json($response)
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
}
}
<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PemesananController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $barang = Barang::orderBy('nama_barang', 'asc')->get();
        
        return view('pemesanan.index', [
            'activemenu' => 'pemesanan',
            'breadcrumb' => (object) [
                'title' => 'Pemesanan',
                'list' => ['Home', 'Transaksi', 'Pemesanan']
            ],
            'barang' => $barang
        ]);
    }

    /**
     * Get pemesanan data for DataTables
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $query = Pemesanan::with(['barang']);
        
        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status_pemesanan', $request->status);
        }
        
        // Filter by barang if provided
        if ($request->has('barang_id') && !empty($request->barang_id)) {
            $query->where('barang_id', $request->barang_id);
        }
        
        // Filter by date range if provided
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('tanggal_pemesanan', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('tanggal_pemesanan', '<=', $request->end_date);
        }
        
        $data = $query->orderBy('created_at', 'desc')->get();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('barang_nama', function($row) {
                return $row->barang ? $row->barang->nama_barang : '-';
            })
            ->addColumn('formatted_tanggal', function($row) {
                return Carbon::parse($row->tanggal_pemesanan)->format('d/m/Y');
            })
            ->addColumn('formatted_total', function($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
            })
            ->addColumn('status_label', function($row) {
                return $row->status_label;
            })
            ->addColumn('action', function($row) {
                return '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$row->pemesanan_id.'" title="Detail">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary btn-edit" data-id="'.$row->pemesanan_id.'" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->pemesanan_id.'" data-nama="'.$row->nama_pemesan.'" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    /**
     * Generate pemesanan ID
     *
     * @return string
     */
    /**
     * Generate Nomor Pemesanan (GROUP ID untuk multiple items)
     * Format: PSN20241124001
     *
     * @return string
     */
    private function generateNomorPemesanan()
    {
        $today = date('Ymd');
        $prefix = 'PSN';
        
        // Cari nomor pemesanan terakhir hari ini
        $lastPemesanan = Pemesanan::where('nomor_pemesanan', 'like', $prefix . $today . '%')
            ->orderBy('nomor_pemesanan', 'desc')
            ->first();
        
        if (!$lastPemesanan) {
            return $prefix . $today . '001';
        }
        
        // Ambil 3 digit terakhir dan increment
        $lastNumber = intval(substr($lastPemesanan->nomor_pemesanan, -3));
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $today . $newNumber;
    }

    /**
     * Generate Pemesanan ID (PRIMARY KEY unik per item)
     * Format: PS0001, PS0002, dst
     *
     * @return string
     */
    private function generatePemesananId()
    {
        $lastPemesanan = Pemesanan::orderBy('pemesanan_id', 'desc')->first();
        
        if (!$lastPemesanan) {
            return 'PS0001';
        }
        
        $lastId = $lastPemesanan->pemesanan_id;
        $prefix = 'PS';
        
        // Validasi format
        if (!preg_match('/^PS\d+$/', $lastId)) {
            return 'PS0001';
        }
        
        // Ambil angka dan increment
        $numPart = substr($lastId, strlen($prefix));
        $nextNum = intval($numPart) + 1;
        
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get auto-generated pemesanan ID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPemesananId()
    {
        $nomorPemesanan = $this->generateNomorPemesanan();
        
        return response()->json([
            'status' => 'success',
            'nomor_pemesanan' => $nomorPemesanan,
            'pemesanan_id' => $nomorPemesanan // Backward compatibility
        ]);
    }

/**
 * Validate pemesanan data including date fields based on status
 * 
 * @param array $data
 * @param string|null $previousStatus Previous status (for updates)
 * @return \Illuminate\Contracts\Validation\Validator
 */
private function validatePemesanan($data, $previousStatus = null)
{
    $rules = [
        'barang_id' => 'required|exists:barang,barang_id',
        'nama_pemesan' => 'required|string|max:100',
        'tanggal_pemesanan' => 'required|date',
        'alamat_pemesan' => 'required|string',
        'jumlah_pesanan' => 'required|integer|min:1',
        'total' => 'required|numeric|min:0',
        'pemesanan_dari' => 'required|string|max:50',
        'metode_pembayaran' => 'required|string|max:50',
        'status_pemesanan' => 'required|in:pending,diproses,dikirim,selesai,dibatalkan',
        'no_telp_pemesan' => 'required|string|max:20',
        'email_pemesan' => 'required|email|max:100',
        'catatan_pemesanan' => 'nullable|string',
        'tanggal_diproses' => 'nullable|date',
        'tanggal_dikirim' => 'nullable|date',
        'tanggal_selesai' => 'nullable|date',
    ];
    
    // Only validate the date field for the current status transition
    if (isset($data['status_pemesanan'])) {
        $currentStatus = $data['status_pemesanan'];
        
        // For update operations, check if status has changed
        if ($previousStatus && $previousStatus != $currentStatus) {
            // Only require dates for status that's being transitioned to
            switch ($currentStatus) {
                case 'diproses':
                    // Only require tanggal_diproses if moving from pending or dibatalkan
                    if ($previousStatus == 'pending' || $previousStatus == 'dibatalkan') {
                        $rules['tanggal_diproses'] = 'required|date';
                    }
                    break;
                case 'dikirim':
                    // Only require tanggal_dikirim if moving from pending, dibatalkan, or diproses
                    if ($previousStatus == 'pending' || $previousStatus == 'dibatalkan' || $previousStatus == 'diproses') {
                        $rules['tanggal_dikirim'] = 'required|date';
                    }
                    break;
                case 'selesai':
                    // Only require tanggal_selesai if moving from pending, dibatalkan, diproses, or dikirim
                    if ($previousStatus == 'pending' || $previousStatus == 'dibatalkan' || 
                        $previousStatus == 'diproses' || $previousStatus == 'dikirim') {
                        $rules['tanggal_selesai'] = 'required|date';
                    }
                    break;
            }
        } else if (!$previousStatus) {
            // For new orders, only require dates based on initial status
            switch ($currentStatus) {
                case 'diproses':
                    $rules['tanggal_diproses'] = 'required|date';
                    break;
                case 'dikirim':
                    $rules['tanggal_diproses'] = 'required|date';
                    $rules['tanggal_dikirim'] = 'required|date|after_or_equal:tanggal_diproses';
                    break;
                case 'selesai':
                    $rules['tanggal_diproses'] = 'required|date';
                    $rules['tanggal_dikirim'] = 'required|date|after_or_equal:tanggal_diproses';
                    $rules['tanggal_selesai'] = 'required|date|after_or_equal:tanggal_dikirim';
                    break;
            }
        }
    }
    
    return Validator::make($data, $rules);
}

/**
 * Store a newly created resource in storage.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
    public function store(Request $request)
    {
        // Set default tanggal_pemesanan to today if not provided
        if (!$request->has('tanggal_pemesanan') || empty($request->tanggal_pemesanan)) {
            $request->merge(['tanggal_pemesanan' => Carbon::today()->format('Y-m-d')]);
        }
        
        // Parse items_data
        $items = json_decode($request->items_data, true);
        
        if (!$items || empty($items)) {
             return response()->json([
                'status' => 'error',
                'message' => 'Data barang tidak valid atau kosong'
            ], 422);
        }

        // Validasi Stok Dulu
        foreach ($items as $item) {
            $barang = Barang::find($item['barang_id']);
            if (!$barang) {
                return response()->json(['status' => 'error', 'message' => 'Barang tidak ditemukan: ' . $item['barang_id']], 404);
            }
            if ($barang->stok < $item['jumlah']) {
                return response()->json([
                    'status' => 'error', 
                    'message' => "Stok barang {$barang->nama_barang} tidak mencukupi! Stok: {$barang->stok}"
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Generate Nomor Pemesanan (GROUP ID) - PSN20241124001
            $nomorPemesanan = $request->input('nomor_pemesanan');
            
            // Jika tidak ada, generate baru
            if (!$nomorPemesanan) {
                $nomorPemesanan = $this->generateNomorPemesanan();
            }
            
            // Validasi format: PSN20241124001 (14 karakter)
            $nomorPemesanan = trim($nomorPemesanan);
            if (!preg_match('/^PSN\d{11}$/', $nomorPemesanan)) {
                throw new \Exception("Format nomor pemesanan tidak valid: {$nomorPemesanan}. Harus PSN20241124001");
            }
            
            // Log untuk debugging
            Log::info('Store Pemesanan', [
                'nomor_pemesanan' => $nomorPemesanan,
                'total_items' => count($items)
            ]);
            
            // Loop insert setiap item
            foreach ($items as $index => $item) {
                $pemesanan = new Pemesanan();
                
                // Generate Unique Pemesanan ID (PRIMARY KEY): PS0001, PS0002, dst
                $pemesananId = $this->generatePemesananId();
                
                $pemesanan->pemesanan_id = $pemesananId;
                $pemesanan->nomor_pemesanan = $nomorPemesanan;
                
                $pemesanan->barang_id = $item['barang_id'];
                $pemesanan->jumlah_pesanan = $item['jumlah'];
                $pemesanan->total = $item['subtotal']; // Total per item row
                
                // Data umum dari request
                $pemesanan->nama_pemesan = $request->nama_pemesan;
                $pemesanan->tanggal_pemesanan = $request->tanggal_pemesanan;
                $pemesanan->alamat_pemesan = $request->alamat_pemesan;
                $pemesanan->pemesanan_dari = $request->pemesanan_dari;
                $pemesanan->metode_pembayaran = $request->metode_pembayaran;
                $pemesanan->status_pemesanan = $request->status_pemesanan;
                $pemesanan->no_telp_pemesan = $request->no_telp_pemesan;
                $pemesanan->email_pemesan = $request->email_pemesan;
                $pemesanan->catatan_pemesanan = $request->catatan_pemesanan;
                
                // Set dates based on status
                switch ($request->status_pemesanan) {
                    case 'diproses':
                        $pemesanan->tanggal_diproses = $request->tanggal_diproses ?? Carbon::now()->format('Y-m-d');
                        break;
                    case 'dikirim':
                        $pemesanan->tanggal_diproses = $request->tanggal_diproses ?? Carbon::now()->format('Y-m-d');
                        $pemesanan->tanggal_dikirim = $request->tanggal_dikirim ?? Carbon::now()->format('Y-m-d');
                        break;
                    case 'selesai':
                        $pemesanan->tanggal_diproses = $request->tanggal_diproses ?? Carbon::now()->format('Y-m-d');
                        $pemesanan->tanggal_dikirim = $request->tanggal_dikirim ?? Carbon::now()->format('Y-m-d');
                        $pemesanan->tanggal_selesai = $request->tanggal_selesai ?? Carbon::now()->format('Y-m-d');
                        break;
                }
                
                $pemesanan->save();
                
                // Kurangi stok
                $barang = Barang::find($item['barang_id']);
                $barang->stok -= $item['jumlah'];
                $barang->save();
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data pemesanan berhasil ditambahkan',
                'nomor_pemesanan' => $nomorPemesanan
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log error detail untuk debugging
            Log::error('Error Store Pemesanan', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'request_data' => $request->except(['_token'])
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $pemesanan = Pemesanan::with(['barang'])->find($id);
        
        if (!$pemesanan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pemesanan tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $pemesanan
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
    // Find pemesanan
    $pemesanan = Pemesanan::find($id);
    
    if (!$pemesanan) {
        return response()->json([
            'status' => 'error',
            'message' => 'Data pemesanan tidak ditemukan'
        ], 404);
    }
    
    // Validasi dasar (tanpa tanggal)
    $rules = [
        'barang_id' => 'required|exists:barang,barang_id',
        'nama_pemesan' => 'required|string|max:100',
        'alamat_pemesan' => 'required|string',
        'jumlah_pesanan' => 'required|integer|min:1',
        'total' => 'required|numeric|min:0',
        'pemesanan_dari' => 'required|string|max:50',
        'metode_pembayaran' => 'required|string|max:50',
        'status_pemesanan' => 'required|in:pending,diproses,dikirim,selesai,dibatalkan',
        'no_telp_pemesan' => 'required|string|max:20',
        'email_pemesan' => 'required|email|max:100',
        'catatan_pemesanan' => 'nullable|string',
    ];
    
    // Tanggal hanya divalidasi jika dikirim dan belum ada nilainya
    if ($request->has('tanggal_pemesanan') && !$pemesanan->tanggal_pemesanan) {
        $rules['tanggal_pemesanan'] = 'required|date';
    }
    
    $validator = Validator::make($request->all(), $rules);
    
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);
    }
    
    // Validasi stok barang jika ada perubahan
    $oldBarangId = $pemesanan->barang_id;
    $oldJumlah = $pemesanan->jumlah_pesanan;
    $newBarangId = $request->barang_id;
    $newJumlah = $request->jumlah_pesanan;
    
    if ($oldBarangId != $newBarangId || $oldJumlah != $newJumlah) {
        // Cek stok barang baru
        $newBarang = Barang::find($newBarangId);
        if (!$newBarang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang tidak ditemukan'
            ], 404);
        }
        
        // Hitung stok yang tersedia (stok saat ini + jumlah lama jika barang sama)
        $availableStock = $newBarang->stok;
        if ($oldBarangId == $newBarangId) {
            $availableStock += $oldJumlah;
        }
        
        if ($availableStock < $newJumlah) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok barang tidak mencukupi! Stok tersedia: ' . $availableStock . ' ' . $newBarang->satuan,
                'errors' => [
                    'jumlah_pesanan' => ['Stok barang tidak mencukupi! Stok tersedia: ' . $availableStock . ' ' . $newBarang->satuan]
                ]
            ], 422);
        }
    }
    
    // Update basic pemesanan data
    $pemesanan->barang_id = $request->barang_id;
    $pemesanan->nama_pemesan = $request->nama_pemesan;
    $pemesanan->alamat_pemesan = $request->alamat_pemesan;
    $pemesanan->jumlah_pesanan = $request->jumlah_pesanan;
    $pemesanan->total = $request->total;
    $pemesanan->pemesanan_dari = $request->pemesanan_dari;
    $pemesanan->metode_pembayaran = $request->metode_pembayaran;
    $pemesanan->no_telp_pemesan = $request->no_telp_pemesan;
    $pemesanan->email_pemesan = $request->email_pemesan;
    $pemesanan->catatan_pemesanan = $request->catatan_pemesanan;
    
    // Update tanggal hanya jika dikirim dan belum ada nilainya
    if (!$pemesanan->tanggal_pemesanan && $request->has('tanggal_pemesanan')) {
        $pemesanan->tanggal_pemesanan = $request->tanggal_pemesanan;
    }
    
    // Atur tanggal status berdasarkan status
    if ($request->status_pemesanan === 'pending' || $request->status_pemesanan === 'dibatalkan') {
        // Reset semua tanggal status jika status pending atau dibatalkan
        $pemesanan->tanggal_diproses = null;
        $pemesanan->tanggal_dikirim = null;
        $pemesanan->tanggal_selesai = null;
    } else {
        // Update tanggal status jika dikirim dan belum ada nilainya
        if ($request->status_pemesanan === 'diproses' || $request->status_pemesanan === 'dikirim' || $request->status_pemesanan === 'selesai') {
            if (!$pemesanan->tanggal_diproses) {
                $pemesanan->tanggal_diproses = $request->tanggal_diproses ?? Carbon::now()->format('Y-m-d');
            }
        }
        
        if ($request->status_pemesanan === 'dikirim' || $request->status_pemesanan === 'selesai') {
            if (!$pemesanan->tanggal_dikirim) {
                $pemesanan->tanggal_dikirim = $request->tanggal_dikirim ?? Carbon::now()->format('Y-m-d');
            }
        }
        
        if ($request->status_pemesanan === 'selesai') {
            if (!$pemesanan->tanggal_selesai) {
                $pemesanan->tanggal_selesai = $request->tanggal_selesai ?? Carbon::now()->format('Y-m-d');
            }
        }
    }
    
    // Update status
    $pemesanan->status_pemesanan = $request->status_pemesanan;
    
    // Sesuaikan stok barang jika ada perubahan (variabel sudah dideklarasikan di atas)
    if ($oldBarangId != $newBarangId || $oldJumlah != $newJumlah) {
        // Kembalikan stok barang lama
        $oldBarang = Barang::find($oldBarangId);
        if ($oldBarang) {
            $oldBarang->stok = $oldBarang->stok + $oldJumlah;
            $oldBarang->save();
        }
        
        // Kurangi stok barang baru
        $newBarang = Barang::find($newBarangId);
        if ($newBarang) {
            $newBarang->stok = $newBarang->stok - $newJumlah;
            $newBarang->save();
        }
    }
    
    $pemesanan->save();
    
    return response()->json([
        'status' => 'success',
        'message' => 'Data pemesanan berhasil diperbarui dan stok barang telah disesuaikan',
        'data' => $pemesanan
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
        $pemesanan = Pemesanan::find($id);
        
        if (!$pemesanan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pemesanan tidak ditemukan'
            ], 404);
        }
        
        // Kembalikan stok barang sebelum menghapus pemesanan
        $barang = Barang::find($pemesanan->barang_id);
        if ($barang) {
            $barang->stok = $barang->stok + $pemesanan->jumlah_pesanan;
            $barang->save();
        }
        
        // Delete pemesanan
        $pemesanan->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Data pemesanan berhasil dihapus dan stok barang telah dikembalikan'
        ]);
    }
}
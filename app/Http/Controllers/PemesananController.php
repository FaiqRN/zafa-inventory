<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        $barang = Barang::where('is_deleted', 0)->orderBy('nama_barang', 'asc')->get();
        
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
private function generatePemesananId()
{
    // Dapatkan pemesanan ID terakhir
    $lastPemesanan = Pemesanan::orderBy('created_at', 'desc')->first();
    
    if (!$lastPemesanan) {
        return 'PO-00001';
    }
    
    $lastId = $lastPemesanan->pemesanan_id;
    
    // Extract number part if format is PO-XXXXX
    if (preg_match('/^PO-(\d+)$/', $lastId, $matches)) {
        $number = intval($matches[1]);
        $nextNumber = $number + 1;
        $nextId = 'PO-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    } else {
        // If format doesn't match, start fresh
        $nextId = 'PO-00001';
    }
    
    // Verifikasi bahwa ID belum digunakan
    while (Pemesanan::where('pemesanan_id', $nextId)->exists()) {
        // Jika masih ada konflik, tambahkan lagi
        if (preg_match('/^PO-(\d+)$/', $nextId, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            $nextId = 'PO-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        } else {
            // Fallback jika format tidak sesuai
            $random = rand(1, 99999);
            $nextId = 'PO-' . str_pad($random, 5, '0', STR_PAD_LEFT);
        }
    }
    
    return $nextId;
}

/**
 * Get auto-generated pemesanan ID
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function getPemesananId()
{
    $pemesananId = $this->generatePemesananId();
    
    // Pastikan ID belum digunakan
    while (Pemesanan::where('pemesanan_id', $pemesananId)->exists()) {
        $pemesananId = $this->generatePemesananId();
    }
    
    return response()->json([
        'status' => 'success',
        'pemesanan_id' => $pemesananId
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
        // Validate request data
        $validator = Validator::make($request->all(), [
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
            'catatan_pemesanan' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Generate pemesanan ID if not provided
        $pemesananId = $request->input('pemesanan_id') ?: $this->generatePemesananId();
        
        // Create new pemesanan
        $pemesanan = new Pemesanan();
        $pemesanan->pemesanan_id = $pemesananId;
        $pemesanan->barang_id = $request->barang_id;
        $pemesanan->nama_pemesan = $request->nama_pemesan;
        $pemesanan->tanggal_pemesanan = $request->tanggal_pemesanan;
        $pemesanan->alamat_pemesan = $request->alamat_pemesan;
        $pemesanan->jumlah_pesanan = $request->jumlah_pesanan;
        $pemesanan->total = $request->total;
        $pemesanan->pemesanan_dari = $request->pemesanan_dari;
        $pemesanan->metode_pembayaran = $request->metode_pembayaran;
        $pemesanan->status_pemesanan = $request->status_pemesanan;
        $pemesanan->no_telp_pemesan = $request->no_telp_pemesan;
        $pemesanan->email_pemesan = $request->email_pemesan;
        $pemesanan->catatan_pemesanan = $request->catatan_pemesanan;
        $pemesanan->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Data pemesanan berhasil ditambahkan',
            'data' => $pemesanan
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
        
        // Validate request data
        $validator = Validator::make($request->all(), [
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
            'catatan_pemesanan' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Update pemesanan data
        $pemesanan->barang_id = $request->barang_id;
        $pemesanan->nama_pemesan = $request->nama_pemesan;
        $pemesanan->tanggal_pemesanan = $request->tanggal_pemesanan;
        $pemesanan->alamat_pemesan = $request->alamat_pemesan;
        $pemesanan->jumlah_pesanan = $request->jumlah_pesanan;
        $pemesanan->total = $request->total;
        $pemesanan->pemesanan_dari = $request->pemesanan_dari;
        $pemesanan->metode_pembayaran = $request->metode_pembayaran;
        $pemesanan->status_pemesanan = $request->status_pemesanan;
        $pemesanan->no_telp_pemesan = $request->no_telp_pemesan;
        $pemesanan->email_pemesan = $request->email_pemesan;
        $pemesanan->catatan_pemesanan = $request->catatan_pemesanan;
        $pemesanan->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Data pemesanan berhasil diperbarui',
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
        
        // Delete pemesanan
        $pemesanan->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Data pemesanan berhasil dihapus'
        ]);
    }
}
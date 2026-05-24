<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use App\Models\Barang;
use App\Helpers\MasterData\Barang\BarangStokHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\DashboardMonitorLogger;
use Carbon\Carbon;

class PemesananController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view-pemesanan')->only([
            'index',
            'getData',
            'show',
        ]);
        $this->middleware('can:create-pemesanan')->only([
            'store',
            'getPemesananId',
        ]);
        $this->middleware('can:edit-pemesanan')->only([
            'update',
        ]);
        $this->middleware('can:delete-pemesanan')->only([
            'destroy',
        ]);
    }

    public function index()
    {
        $barang = Barang::withStok()->orderBy('nama_barang', 'asc')->get();
        
        return view('pemesanan.index', [
            'activemenu' => 'pemesanan',
            'breadcrumb' => (object) [
                'title' => 'Pemesanan',
                'list' => ['Home', 'Transaksi', 'Pemesanan']
            ],
            'barang' => $barang
        ]);
    }

    public function getData(Request $request)
    {
        $user = $request->user();
        $canEditPemesanan = $user ? $user->can('edit-pemesanan') : false;
        $canDeletePemesanan = $user ? $user->can('delete-pemesanan') : false;

        $query = Pemesanan::with(['barang']);
        
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status_pemesanan', $request->status);
        }
        
        if ($request->has('barang_id') && !empty($request->barang_id)) {
            $query->where('barang_id', $request->barang_id);
        }
        
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
            ->addColumn('action', function($row) use ($canEditPemesanan, $canDeletePemesanan) {
                $buttons = '<div class="btn-group">';
                $buttons .= '<button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$row->pemesanan_id.'" title="Detail">'
                    . '<i class="fas fa-eye"></i>'
                    . '</button>';

                if ($canEditPemesanan) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-primary btn-edit" data-id="'.$row->pemesanan_id.'" title="Edit">'
                        . '<i class="fas fa-edit"></i>'
                        . '</button>';
                }

                if ($canDeletePemesanan) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->pemesanan_id.'" data-nama="'.e($row->nama_pemesan).'" title="Hapus">'
                        . '<i class="fas fa-trash"></i>'
                        . '</button>';
                }

                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    private function generateNomorPemesanan()
    {
        $today = date('Ymd');
        $prefix = 'PSN';
        
        $lastPemesanan = Pemesanan::where('nomor_pemesanan', 'like', $prefix . $today . '%')
            ->orderBy('nomor_pemesanan', 'desc')
            ->first();
        
        if (!$lastPemesanan) {
            return $prefix . $today . '001';
        }
        
        $lastNumber = intval(substr($lastPemesanan->nomor_pemesanan, -3));
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $today . $newNumber;
    }

    private function generatePemesananId()
    {
        $lastPemesanan = Pemesanan::orderBy('pemesanan_id', 'desc')->first();
        
        if (!$lastPemesanan) {
            return 'PS0001';
        }
        
        $lastId = $lastPemesanan->pemesanan_id;
        $prefix = 'PS';
        
        if (!preg_match('/^PS\d+$/', $lastId)) {
            return 'PS0001';
        }
        
        $numPart = substr($lastId, strlen($prefix));
        $nextNum = intval($numPart) + 1;
        
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    public function getPemesananId()
    {
        $nomorPemesanan = $this->generateNomorPemesanan();
        
        return response()->json([
            'status' => 'success',
            'nomor_pemesanan' => $nomorPemesanan,
            'pemesanan_id' => $nomorPemesanan 
        ]);
    }


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
    
    if (isset($data['status_pemesanan'])) {
        $currentStatus = $data['status_pemesanan'];
        
        if ($previousStatus && $previousStatus != $currentStatus) {
            switch ($currentStatus) {
                case 'diproses':
                    if ($previousStatus == 'pending' || $previousStatus == 'dibatalkan') {
                        $rules['tanggal_diproses'] = 'required|date';
                    }
                    break;
                case 'dikirim':
                    if ($previousStatus == 'pending' || $previousStatus == 'dibatalkan' || $previousStatus == 'diproses') {
                        $rules['tanggal_dikirim'] = 'required|date';
                    }
                    break;
                case 'selesai':
                    if ($previousStatus == 'pending' || $previousStatus == 'dibatalkan' || 
                        $previousStatus == 'diproses' || $previousStatus == 'dikirim') {
                        $rules['tanggal_selesai'] = 'required|date';
                    }
                    break;
            }
        } else if (!$previousStatus) {
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

    public function store(Request $request)
    {
        if (!$request->has('tanggal_pemesanan') || empty($request->tanggal_pemesanan)) {
            $request->merge(['tanggal_pemesanan' => Carbon::today()->format('Y-m-d')]);
        }
        
        $items = json_decode($request->items_data, true);
        
        if (!$items || empty($items)) {
             return response()->json([
                'status' => 'error',
                'message' => 'Data barang tidak valid atau kosong'
            ], 422);
        }

        foreach ($items as $item) {
            $barang = Barang::withStok()->find($item['barang_id']);
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
            $nomorPemesanan = $request->input('nomor_pemesanan');
            
            if (!$nomorPemesanan) {
                $nomorPemesanan = $this->generateNomorPemesanan();
            }
            
            $nomorPemesanan = trim($nomorPemesanan);
            if (!preg_match('/^PSN\d{11}$/', $nomorPemesanan)) {
                throw new \Exception("Format nomor pemesanan tidak valid: {$nomorPemesanan}. Harus PSN20241124001");
            }
            
            Log::info('Store Pemesanan', [
                'nomor_pemesanan' => $nomorPemesanan,
                'total_items' => count($items)
            ]);
            
            foreach ($items as $index => $item) {
                $pemesanan = new Pemesanan();
 
                $pemesananId = $this->generatePemesananId();
                
                $pemesanan->pemesanan_id = $pemesananId;
                $pemesanan->nomor_pemesanan = $nomorPemesanan;
                
                $pemesanan->barang_id = $item['barang_id'];
                $pemesanan->jumlah_pesanan = $item['jumlah'];
                $pemesanan->total = $item['subtotal']; 
                
                $pemesanan->nama_pemesan = $request->nama_pemesan;
                $pemesanan->tanggal_pemesanan = $request->tanggal_pemesanan;
                $pemesanan->alamat_pemesan = $request->alamat_pemesan;
                $pemesanan->pemesanan_dari = $request->pemesanan_dari;
                $pemesanan->metode_pembayaran = $request->metode_pembayaran;
                $pemesanan->status_pemesanan = $request->status_pemesanan;
                $pemesanan->no_telp_pemesan = $request->no_telp_pemesan;
                $pemesanan->email_pemesan = $request->email_pemesan;
                $pemesanan->catatan_pemesanan = $request->catatan_pemesanan;
                
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
                
                BarangStokHelper::kurangiStok($item['barang_id'], $item['jumlah']);
            }
            
            DB::commit();

            DashboardMonitorLogger::create('Pemesanan', "Tambah pemesanan {$nomorPemesanan} ({$request->nama_pemesan})", ['nomor_pemesanan' => $nomorPemesanan, 'total_items' => count($items)], $request);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data pemesanan berhasil ditambahkan',
                'nomor_pemesanan' => $nomorPemesanan
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
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

public function update(Request $request, $id)
{
    $pemesanan = Pemesanan::find($id);
    
    if (!$pemesanan) {
        return response()->json([
            'status' => 'error',
            'message' => 'Data pemesanan tidak ditemukan'
        ], 404);
    }
    
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
    
    $oldBarangId = $pemesanan->barang_id;
    $oldJumlah = $pemesanan->jumlah_pesanan;
    $newBarangId = $request->barang_id;
    $newJumlah = $request->jumlah_pesanan;
    
    if ($oldBarangId != $newBarangId || $oldJumlah != $newJumlah) {
        $newBarang = Barang::withStok()->find($newBarangId);
        if (!$newBarang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang tidak ditemukan'
            ], 404);
        }
        
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
    
    if (!$pemesanan->tanggal_pemesanan && $request->has('tanggal_pemesanan')) {
        $pemesanan->tanggal_pemesanan = $request->tanggal_pemesanan;
    }
    
    if ($request->status_pemesanan === 'pending' || $request->status_pemesanan === 'dibatalkan') {
        $pemesanan->tanggal_diproses = null;
        $pemesanan->tanggal_dikirim = null;
        $pemesanan->tanggal_selesai = null;
    } else {
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
    
    $pemesanan->status_pemesanan = $request->status_pemesanan;
    
    if ($oldBarangId != $newBarangId || $oldJumlah != $newJumlah) {
        if ($oldJumlah > 0) {
            BarangStokHelper::tambahStok(
                $oldBarangId,
                $oldJumlah,
                now()->format('Y-m-d'),
                'Pengembalian stok dari update pemesanan'
            );
        }
        
        if ($newJumlah > 0) {
            BarangStokHelper::kurangiStok($newBarangId, $newJumlah);
        }
    }
    
    $pemesanan->save();

    DashboardMonitorLogger::update('Pemesanan', "Ubah pemesanan {$pemesanan->nomor_pemesanan} ({$pemesanan->nama_pemesan})", ['old_barang' => $oldBarangId, 'old_jumlah' => $oldJumlah], $request->except('_token'), $request);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Data pemesanan berhasil diperbarui dan stok barang telah disesuaikan',
        'data' => $pemesanan
    ]);
}

    public function destroy($id)
    {
        $pemesanan = Pemesanan::find($id);
        
        if (!$pemesanan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pemesanan tidak ditemukan'
            ], 404);
        }
        
        if ($pemesanan->jumlah_pesanan > 0) {
            BarangStokHelper::tambahStok(
                $pemesanan->barang_id,
                $pemesanan->jumlah_pesanan,
                now()->format('Y-m-d'),
                'Pengembalian stok dari hapus pemesanan'
            );
        }
        
        DashboardMonitorLogger::delete('Pemesanan', "Hapus pemesanan {$pemesanan->nomor_pemesanan} ({$pemesanan->nama_pemesan})", $pemesanan->toArray());
        
        $pemesanan->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Data pemesanan berhasil dihapus dan stok barang telah dikembalikan'
        ]);
    }
}


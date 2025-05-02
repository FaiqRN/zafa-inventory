<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Toko;
use App\Models\Pengiriman;
use App\Models\Pemesanan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard dengan data statistik.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Tanggal awal bulan ini
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        
        // Mendapatkan statistik utama
        $total_barang = Barang::count();
        $total_toko = Toko::count();
        $total_pengiriman = Pengiriman::whereDate('tanggal_pengiriman', '>=', $startOfMonth)->count();
        $total_pemesanan = Pemesanan::where('status_pemesanan', 'pending')->count();
        
        // Mendapatkan data pengiriman terbaru
        $pengiriman_terbaru = Pengiriman::with(['toko', 'barang'])
                            ->orderBy('tanggal_pengiriman', 'desc')
                            ->limit(5)
                            ->get();
        
        // Mendapatkan produk terlaris
        // Catatan: Dalam implementasi nyata, ini akan menggunakan join
        // dan perhitungan yang lebih kompleks
        $produk_terlaris = Barang::select('barang.*')
                            ->selectRaw('(SELECT SUM(pengiriman.jumlah_kirim - COALESCE(
                                            (SELECT SUM(retur.jumlah_retur) FROM retur 
                                             WHERE retur.barang_id = barang.barang_id
                                             GROUP BY retur.barang_id), 0)) 
                                        FROM pengiriman 
                                        WHERE pengiriman.barang_id = barang.barang_id) as total_terjual')
                            ->orderByRaw('total_terjual DESC')
                            ->limit(3)
                            ->get();
        
        // Mendapatkan pemesanan terbaru
        $pemesanan_terbaru = Pemesanan::with(['barang'])
                            ->orderBy('tanggal_pemesanan', 'desc')
                            ->limit(3)
                            ->get();
        
        // Return view dengan data
        return view('dashboard', compact(
            'total_barang',
            'total_toko',
            'total_pengiriman',
            'total_pemesanan',
            'pengiriman_terbaru',
            'produk_terlaris',
            'pemesanan_terbaru'
        ));
    }
}
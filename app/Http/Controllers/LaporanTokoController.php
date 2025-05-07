<?php
// app/Http/Controllers/LaporanTokoController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Toko;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\TokoLaporan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\FormatHelper;

class LaporanTokoController extends Controller
{
    public function index()
    {
        return view('laporan.toko', [
            'activemenu' => 'laporan-toko',
            'breadcrumb' => (object) [
                'title' => 'Laporan Per Toko',
                'list' => ['Home', 'Laporan', 'Laporan Per Toko']
            ]
        ]);
    }
    
    public function getData(Request $request)
    {
        $periode = $request->periode;
        $bulan = $request->bulan ?? Carbon::now()->month;
        $tahun = $request->tahun ?? Carbon::now()->year;
        
        // Calculate date range based on periode
        $dateRange = $this->calculateDateRange($periode, $bulan, $tahun);
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];
        
        // Get all toko
        $tokos = Toko::all();
        $result = [];
        
        // Calculate summary stats
        $totalToko = count($tokos);
        $totalPenjualanAll = 0;
        $totalPengirimanAll = 0;
        $totalReturAll = 0;
        
        foreach ($tokos as $toko) {
            // Get total penjualan
            $totalPenjualan = Retur::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_pengiriman', [$startDate, $endDate])
                ->sum(DB::raw('total_terjual * harga_awal_barang'));
            
            // Get total pengiriman
            $totalPengiriman = Pengiriman::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_pengiriman', [$startDate, $endDate])
                ->count();
            
            // Get total barang retur
            $totalRetur = Retur::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_retur', [$startDate, $endDate])
                ->sum('jumlah_retur');
            
            // Get existing notes
            $laporan = TokoLaporan::where('toko_id', $toko->toko_id)
                ->where('periode', $periode)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();
            
            $catatan = $laporan ? $laporan->catatan : '';
            
            $result[] = [
                'toko_id' => $toko->toko_id,
                'nama_toko' => $toko->nama_toko,
                'pemilik' => $toko->pemilik,
                'alamat' => $toko->alamat,
                'total_penjualan' => $totalPenjualan,
                'total_pengiriman' => $totalPengiriman,
                'total_retur' => $totalRetur,
                'catatan' => $catatan
            ];
            
            $totalPenjualanAll += $totalPenjualan;
            $totalPengirimanAll += $totalPengiriman;
            $totalReturAll += $totalRetur;
        }
        
        return response()->json([
            'data' => $result,
            'summary' => [
                'totalToko' => $totalToko,
                'totalPenjualan' => $totalPenjualanAll,
                'totalPengiriman' => $totalPengirimanAll,
                'totalRetur' => $totalReturAll
            ]
        ]);
    }
    
    public function updateCatatan(Request $request)
    {
        $toko_id = $request->toko_id;
        $periode = $request->periode;
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $catatan = $request->catatan;
        
        $laporan = TokoLaporan::updateOrCreate(
            [
                'toko_id' => $toko_id,
                'periode' => $periode,
                'bulan' => $bulan,
                'tahun' => $tahun
            ],
            [
                'catatan' => $catatan
            ]
        );
        
        return response()->json(['success' => true]);
    }
    
    public function getDetailData(Request $request)
    {
        $toko_id = $request->toko_id;
        $periode = $request->periode;
        $bulan = $request->bulan ?? Carbon::now()->month;
        $tahun = $request->tahun ?? Carbon::now()->year;
        
        // Calculate date range based on periode
        $dateRange = $this->calculateDateRange($periode, $bulan, $tahun);
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];
        
        // Get toko info
        $toko = Toko::findOrFail($toko_id);
        
        // Get detail sales per item
        $detailPenjualan = DB::table('retur')
            ->join('barang', 'retur.barang_id', '=', 'barang.barang_id')
            ->select(
                'barang.nama_barang',
                'barang.satuan',
                'barang.harga_awal_barang',
                DB::raw('SUM(retur.jumlah_kirim) as total_kirim'),
                DB::raw('SUM(retur.jumlah_retur) as total_retur'),
                DB::raw('SUM(retur.total_terjual) as total_terjual'),
                DB::raw('SUM(retur.total_terjual * retur.harga_awal_barang) as total_penjualan')
            )
            ->where('retur.toko_id', $toko_id)
            ->whereBetween('retur.tanggal_pengiriman', [$startDate, $endDate])
            ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.satuan', 'barang.harga_awal_barang')
            ->get();
        
        // Get history of deliveries
        $pengiriman = DB::table('pengiriman')
            ->join('barang', 'pengiriman.barang_id', '=', 'barang.barang_id')
            ->select(
                'pengiriman.nomer_pengiriman',
                'pengiriman.tanggal_pengiriman',
                'barang.nama_barang',
                'pengiriman.jumlah_kirim',
                'pengiriman.status'
            )
            ->where('pengiriman.toko_id', $toko_id)
            ->whereBetween('pengiriman.tanggal_pengiriman', [$startDate, $endDate])
            ->orderBy('pengiriman.tanggal_pengiriman', 'desc')
            ->get();
        
        return response()->json([
            'toko' => $toko,
            'detailPenjualan' => $detailPenjualan,
            'pengiriman' => $pengiriman
        ]);
    }
    
    private function calculateDateRange($periode, $bulan, $tahun)
    {
        if ($periode == '1_bulan') {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } elseif ($periode == '6_bulan') {
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->subMonths(5)->startOfMonth();
        } else { // 1_tahun
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->subMonths(11)->startOfMonth();
        }
        
        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ];
    }
}
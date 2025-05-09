<?php
// app/Http/Controllers/LaporanTokoController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Toko;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\Barang;
use App\Models\TokoLaporan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\FormatHelper;
use Illuminate\Support\Facades\Log;

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
        try {
            $periode = $request->periode ?? '1_bulan';
            $bulan = $request->bulan ?? Carbon::now()->month;
            $tahun = $request->tahun ?? Carbon::now()->year;
            
            // Calculate date range based on periode
            $dateRange = $this->calculateDateRange($periode, $bulan, $tahun);
            $startDate = $dateRange['start_date'];
            $endDate = $dateRange['end_date'];
            
            Log::info("Fetching data for period: $startDate to $endDate");
            
            // Get all toko
            $tokos = Toko::all();
            $result = [];
            
            // Calculate summary stats
            $totalToko = count($tokos);
            $totalPenjualanAll = 0;
            $totalPengirimanAll = 0;
            $totalReturAll = 0;
            
            foreach ($tokos as $toko) {
                // Total Penjualan: sum of hasil from retur table
                $totalPenjualan = DB::table('retur')
                    ->where('toko_id', $toko->toko_id)
                    ->whereBetween('tanggal_pengiriman', [$startDate, $endDate])
                    ->sum('hasil');
                
                // Total jumlah barang yang dikirim dalam periode tersebut
                $totalPengiriman = DB::table('pengiriman')
                    ->where('toko_id', $toko->toko_id)
                    ->whereBetween('tanggal_pengiriman', [$startDate, $endDate])
                    ->sum('jumlah_kirim');
                
                // Total jumlah barang yang diretur dalam periode tersebut
                $totalRetur = DB::table('retur')
                    ->where('toko_id', $toko->toko_id)
                    ->whereBetween('tanggal_retur', [$startDate, $endDate])
                    ->sum('jumlah_retur');
                
                // Get existing notes
                $laporan = TokoLaporan::where('toko_id', $toko->toko_id)
                    ->where('periode', $periode)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();
                
                $catatan = $laporan ? $laporan->catatan : '';
                
                // Log data untuk debugging
                Log::info("Toko: {$toko->nama_toko}, Total Penjualan: {$totalPenjualan}, Pengiriman: {$totalPengiriman}, Retur: {$totalRetur}");
                
                $result[] = [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'pemilik' => $toko->pemilik,
                    'alamat' => $toko->alamat,
                    'total_penjualan' => $totalPenjualan ?? 0,
                    'total_pengiriman' => $totalPengiriman ?? 0,
                    'total_retur' => $totalRetur ?? 0,
                    'catatan' => $catatan
                ];
                
                $totalPenjualanAll += $totalPenjualan ?? 0;
                $totalPengirimanAll += $totalPengiriman ?? 0;
                $totalReturAll += $totalRetur ?? 0;
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
        } catch (\Exception $e) {
            Log::error("Error in getData: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateCatatan(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error("Error in updateCatatan: " . $e->getMessage());
            
            return response()->json([
                'error' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getDetailData(Request $request)
    {
        try {
            $toko_id = $request->toko_id;
            $periode = $request->periode ?? '1_bulan';
            $bulan = $request->bulan ?? Carbon::now()->month;
            $tahun = $request->tahun ?? Carbon::now()->year;
            
            // Calculate date range based on periode
            $dateRange = $this->calculateDateRange($periode, $bulan, $tahun);
            $startDate = $dateRange['start_date'];
            $endDate = $dateRange['end_date'];
            
            // Get toko info
            $toko = Toko::findOrFail($toko_id);
            
            // Get detail penjualan per barang
            $detailPenjualan = DB::table('retur')
                ->join('barang', 'retur.barang_id', '=', 'barang.barang_id')
                ->select(
                    'barang.barang_id',
                    'barang.nama_barang',
                    'barang.satuan',
                    'barang.harga_awal_barang',
                    DB::raw('SUM(retur.jumlah_kirim) as total_kirim'),
                    DB::raw('SUM(retur.jumlah_retur) as total_retur'),
                    DB::raw('SUM(retur.total_terjual) as total_terjual'),
                    DB::raw('SUM(retur.hasil) as total_penjualan')
                )
                ->where('retur.toko_id', $toko_id)
                ->whereBetween('retur.tanggal_pengiriman', [$startDate, $endDate])
                ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.satuan', 'barang.harga_awal_barang')
                ->get();
            
            // Log data untuk debugging
            Log::info("Detail Penjualan untuk Toko: {$toko->nama_toko}");
            Log::info(json_encode($detailPenjualan));
            
            // Get riwayat pengiriman
            $pengiriman = DB::table('pengiriman')
                ->join('barang', 'pengiriman.barang_id', '=', 'barang.barang_id')
                ->select(
                    'pengiriman.pengiriman_id',
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
            
            // Get riwayat retur
            $retur = DB::table('retur')
                ->join('barang', 'retur.barang_id', '=', 'barang.barang_id')
                ->select(
                    'retur.retur_id',
                    'retur.nomer_pengiriman',
                    'retur.tanggal_pengiriman',
                    'retur.tanggal_retur',
                    'barang.nama_barang',
                    'retur.jumlah_kirim',
                    'retur.jumlah_retur',
                    'retur.total_terjual',
                    'retur.hasil',
                    'retur.kondisi',
                    'retur.keterangan'
                )
                ->where('retur.toko_id', $toko_id)
                ->whereBetween('retur.tanggal_pengiriman', [$startDate, $endDate])
                ->orderBy('retur.tanggal_retur', 'desc')
                ->get();
            
            return response()->json([
                'toko' => $toko,
                'detailPenjualan' => $detailPenjualan,
                'pengiriman' => $pengiriman,
                'retur' => $retur
            ]);
        } catch (\Exception $e) {
            Log::error("Error in getDetailData: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function calculateDateRange($periode, $bulan, $tahun)
    {
        try {
            $bulan = (int)$bulan;
            $tahun = (int)$tahun;
            
            if ($periode == '1_bulan') {
                $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
                $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
            } elseif ($periode == '6_bulan') {
                $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
                $startDate = Carbon::create($tahun, $bulan, 1)->subMonths(5)->startOfMonth();
            } else { // 1_tahun
                $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();
                $startDate = Carbon::create($tahun, $bulan, 1)->subMonths(11)->startOfMonth();
            }
            
            // Log untuk debugging
            Log::info("Periode: $periode, Bulan: $bulan, Tahun: $tahun");
            Log::info("StartDate: {$startDate->format('Y-m-d')}, EndDate: {$endDate->format('Y-m-d')}");
            
            return [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ];
        } catch (\Exception $e) {
            Log::error("Error in calculateDateRange: " . $e->getMessage());
            // Fallback to current month if there's an error
            $now = Carbon::now();
            return [
                'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end_date' => $now->copy()->endOfMonth()->format('Y-m-d')
            ];
        }
    }

    public function exportCsv(Request $request)
{
    try {
        $periode = $request->periode ?? '1_bulan';
        $bulan = $request->bulan ?? Carbon::now()->month;
        $tahun = $request->tahun ?? Carbon::now()->year;
        
        // Calculate date range based on periode
        $dateRange = $this->calculateDateRange($periode, $bulan, $tahun);
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];
        
        // Get all toko
        $tokos = Toko::all();
        $data = [];
        
        foreach ($tokos as $toko) {
            // Total Penjualan: sum of hasil from retur table
            $totalPenjualan = DB::table('retur')
                ->where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_pengiriman', [$startDate, $endDate])
                ->sum('hasil');
            
            // Total jumlah barang yang dikirim dalam periode tersebut
            $totalPengiriman = DB::table('pengiriman')
                ->where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_pengiriman', [$startDate, $endDate])
                ->sum('jumlah_kirim');
            
            // Total jumlah barang yang diretur dalam periode tersebut
            $totalRetur = DB::table('retur')
                ->where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_retur', [$startDate, $endDate])
                ->sum('jumlah_retur');
            
            // Get existing notes
            $laporan = TokoLaporan::where('toko_id', $toko->toko_id)
                ->where('periode', $periode)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();
            
            $catatan = $laporan ? $laporan->catatan : '';
            
            $data[] = [
                'toko_id' => $toko->toko_id,
                'nama_toko' => $toko->nama_toko,
                'pemilik' => $toko->pemilik,
                'alamat' => $toko->alamat,
                'nomer_telpon' => $toko->nomer_telpon,
                'total_penjualan' => $totalPenjualan ?? 0,
                'total_pengiriman' => $totalPengiriman ?? 0,
                'total_retur' => $totalRetur ?? 0,
                'catatan' => $catatan
            ];
        }
        
        // Generate filename
        $periodeLabel = '';
        if ($periode == '1_bulan') {
            $bulanLabel = Carbon::create($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');
            $periodeLabel = "1_Bulan_{$bulanLabel}_{$tahun}";
        } elseif ($periode == '6_bulan') {
            $endDate = Carbon::create($tahun, $bulan, 1);
            $startDate = Carbon::create($tahun, $bulan, 1)->subMonths(5);
            $periodeLabel = "6_Bulan_{$startDate->locale('id')->isoFormat('MMMM_YYYY')}_sampai_{$endDate->locale('id')->isoFormat('MMMM_YYYY')}";
        } else { // 1_tahun
            $endDate = Carbon::create($tahun, $bulan, 1);
            $startDate = Carbon::create($tahun, $bulan, 1)->subMonths(11);
            $periodeLabel = "1_Tahun_{$startDate->locale('id')->isoFormat('MMMM_YYYY')}_sampai_{$endDate->locale('id')->isoFormat('MMMM_YYYY')}";
        }
        
        $filename = "Laporan_Toko_{$periodeLabel}.csv";
        
        // Create CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $columns = [
            'ID Toko', 
            'Nama Toko', 
            'Pemilik', 
            'Alamat', 
            'Nomor Telepon',
            'Total Penjualan (Rp)', 
            'Total Barang Dikirim', 
            'Total Barang Retur', 
            'Catatan'
        ];
        
$callback = function() use ($data, $columns) {
    $file = fopen('php://output', 'w');
    // Add BOM to fix UTF-8 in Excel
    fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    fputcsv($file, $columns);
    
    foreach ($data as $row) {
        fputcsv($file, [
            $row['toko_id'],
            $row['nama_toko'],
            $row['pemilik'],
            $row['alamat'],
            $row['nomer_telpon'],
            $row['total_penjualan'],
            $row['total_pengiriman'],
            $row['total_retur'],
            $row['catatan']
        ]);
    }
    
    fclose($file);
};
        
        return response()->stream($callback, 200, $headers);
        
    } catch (\Exception $e) {
        Log::error("Error in exportCsv: " . $e->getMessage());
        Log::error($e->getTraceAsString());
        
        return response()->json([
            'error' => true,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}

// Tambahkan juga method untuk export detail CSV
public function exportDetailCsv(Request $request)
{
    try {
        $toko_id = $request->toko_id;
        $periode = $request->periode ?? '1_bulan';
        $bulan = $request->bulan ?? Carbon::now()->month;
        $tahun = $request->tahun ?? Carbon::now()->year;
        
        // Calculate date range
        $dateRange = $this->calculateDateRange($periode, $bulan, $tahun);
        $startDate = $dateRange['start_date'];
        $endDate = $dateRange['end_date'];
        
        // Get toko info
        $toko = Toko::findOrFail($toko_id);
        
        // Get detail penjualan per barang
        $detailPenjualan = DB::table('retur')
            ->join('barang', 'retur.barang_id', '=', 'barang.barang_id')
            ->select(
                'barang.barang_id',
                'barang.nama_barang',
                'barang.satuan',
                'barang.harga_awal_barang',
                DB::raw('SUM(retur.jumlah_kirim) as total_kirim'),
                DB::raw('SUM(retur.jumlah_retur) as total_retur'),
                DB::raw('SUM(retur.total_terjual) as total_terjual'),
                DB::raw('SUM(retur.hasil) as total_penjualan')
            )
            ->where('retur.toko_id', $toko_id)
            ->whereBetween('retur.tanggal_pengiriman', [$startDate, $endDate])
            ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.satuan', 'barang.harga_awal_barang')
            ->get();
        
        // Generate filename
        $filename = "Detail_Laporan_Toko_{$toko->nama_toko}_{$periode}_{$bulan}_{$tahun}.csv";
        
        // Create CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $columns = [
            'ID Barang',
            'Nama Barang', 
            'Satuan', 
            'Harga Awal Barang', 
            'Total Kirim',
            'Total Retur', 
            'Total Terjual', 
            'Total Penjualan'
        ];
        
$callback = function() use ($detailPenjualan, $columns) {
    $file = fopen('php://output', 'w');
    // Add BOM to fix UTF-8 in Excel
    fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    fputcsv($file, $columns);
    
    foreach ($detailPenjualan as $row) {
        fputcsv($file, [
            $row->barang_id,
            $row->nama_barang,
            $row->satuan,
            $row->harga_awal_barang,
            $row->total_kirim,
            $row->total_retur,
            $row->total_terjual,
            $row->total_penjualan
        ]);
    }
    
    fclose($file);
};
        
        return response()->stream($callback, 200, $headers);
        
    } catch (\Exception $e) {
        Log::error("Error in exportDetailCsv: " . $e->getMessage());
        Log::error($e->getTraceAsString());
        
        return response()->json([
            'error' => true,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}
}
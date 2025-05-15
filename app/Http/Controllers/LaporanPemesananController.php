<?php
// app/Http/Controllers/LaporanPemesananController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pemesanan;
use App\Models\Barang;
use App\Models\PemesananLaporan; // Jika Anda membuat tabel ini
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LaporanPemesananController extends Controller
{
    public function index()
    {
        return view('laporan.pemesanan', [
            'activemenu' => 'laporan-pemesanan',
            'breadcrumb' => (object) [
                'title' => 'Laporan Pemesanan',
                'list' => ['Home', 'Laporan', 'Laporan Pemesanan']
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
            
            Log::info("Fetching pemesanan data for period: $startDate to $endDate");
            
            // Get data with join to Barang table
            $data = DB::table('pemesanan')
                ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                ->select(
                    'pemesanan.*',
                    'barang.nama_barang',
                    'barang.satuan',
                    'barang.harga_awal_barang'
                )
                ->whereBetween('pemesanan.tanggal_pemesanan', [$startDate, $endDate])
                ->orderBy('pemesanan.tanggal_pemesanan', 'desc')
                ->get();
            
            // Calculate summary stats
            $totalPemesanan = count($data);
            $totalNilai = $data->sum('total');
            $totalSelesai = $data->where('status_pemesanan', 'selesai')->count();
            $totalCancel = $data->where('status_pemesanan', 'dibatalkan')->count();
            
            // Get notes for each order if using pemesanan_laporan table
            foreach ($data as $item) {
                $laporan = DB::table('pemesanan_laporan')
                    ->where('pemesanan_id', $item->pemesanan_id)
                    ->where('periode', $periode)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();
                
                $item->catatan = $laporan ? $laporan->catatan : '';
            }
            
            return response()->json([
                'data' => $data,
                'summary' => [
                    'totalPemesanan' => $totalPemesanan,
                    'totalNilai' => $totalNilai,
                    'totalSelesai' => $totalSelesai,
                    'totalCancel' => $totalCancel
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
    
    public function getDetailData(Request $request)
    {
        try {
            $pemesanan_id = $request->pemesanan_id;
            
            // Get detail data
            $pemesanan = DB::table('pemesanan')
                ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                ->select(
                    'pemesanan.*',
                    'barang.nama_barang',
                    'barang.satuan',
                    'barang.harga_awal_barang'
                )
                ->where('pemesanan.pemesanan_id', $pemesanan_id)
                ->first();
            
            if (!$pemesanan) {
                return response()->json([
                    'error' => true,
                    'message' => 'Data pemesanan tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'pemesanan' => $pemesanan
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
    
    // Fungsi untuk menghitung rentang tanggal berdasarkan periode
    private function calculateDateRange($periode, $bulan, $tahun)
    {
        // Implementasi sama seperti di LaporanTokoController
    }
// Fungsi untuk ekspor CSV
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
           
           // Get data with join to Barang table
           $data = DB::table('pemesanan')
               ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
               ->select(
                   'pemesanan.pemesanan_id',
                   'pemesanan.tanggal_pemesanan',
                   'pemesanan.nama_pemesan',
                   'pemesanan.alamat_pemesan',
                   'pemesanan.no_telp_pemesan',
                   'pemesanan.email_pemesan',
                   'barang.nama_barang',
                   'pemesanan.jumlah_pesanan',
                   'pemesanan.total',
                   'pemesanan.pemesanan_dari',
                   'pemesanan.metode_pembayaran',
                   'pemesanan.status_pemesanan',
                   'pemesanan.catatan_pemesanan'
               )
               ->whereBetween('pemesanan.tanggal_pemesanan', [$startDate, $endDate])
               ->orderBy('pemesanan.tanggal_pemesanan', 'desc')
               ->get();
           
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
           
           $filename = "Laporan_Pemesanan_{$periodeLabel}.csv";
           
           // Create CSV
           $headers = [
               'Content-Type' => 'text/csv',
               'Content-Disposition' => "attachment; filename=\"$filename\"",
               'Pragma' => 'no-cache',
               'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
               'Expires' => '0'
           ];
           
           $columns = [
               'ID Pemesanan', 
               'Tanggal Pemesanan', 
               'Nama Pemesan', 
               'Alamat',
               'No. Telepon',
               'Email',
               'Nama Barang', 
               'Jumlah Pesanan', 
               'Total (Rp)', 
               'Sumber Pemesanan',
               'Metode Pembayaran',
               'Status',
               'Catatan'
           ];
           
           $callback = function() use ($data, $columns) {
               $file = fopen('php://output', 'w');
               // Add BOM to fix UTF-8 in Excel
               fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
               fputcsv($file, $columns);
               
               foreach ($data as $row) {
                   fputcsv($file, [
                       $row->pemesanan_id,
                       $row->tanggal_pemesanan,
                       $row->nama_pemesan,
                       $row->alamat_pemesan,
                       $row->no_telp_pemesan,
                       $row->email_pemesan,
                       $row->nama_barang,
                       $row->jumlah_pesanan,
                       $row->total,
                       $row->pemesanan_dari,
                       $row->metode_pembayaran,
                       $row->status_pemesanan,
                       $row->catatan_pemesanan
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
   
   // Fungsi untuk update catatan pemesanan (jika menggunakan tabel pemesanan_laporan)
   public function updateCatatan(Request $request)
   {
       try {
           $pemesanan_id = $request->pemesanan_id;
           $periode = $request->periode;
           $bulan = $request->bulan;
           $tahun = $request->tahun;
           $catatan = $request->catatan;
           
           $laporan = DB::table('pemesanan_laporan')->updateOrInsert(
               [
                   'pemesanan_id' => $pemesanan_id,
                   'periode' => $periode,
                   'bulan' => $bulan,
                   'tahun' => $tahun
               ],
               [
                   'catatan' => $catatan,
                   'updated_at' => now()
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
   
   // Fungsi untuk print report
   public function printReport(Request $request)
   {
       // Implementasi fungsi untuk mencetak laporan
       // Ini biasanya dihandle di sisi client dengan JavaScript
   }
}
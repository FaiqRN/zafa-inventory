<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pemesanan;
use App\Models\Barang;
use App\Models\PemesananLaporan;
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
            $tipe = $request->tipe ?? 'barang';
            $periode = $request->periode ?? '1_bulan';
            $bulan = $request->bulan ?? Carbon::now()->month;
            $tahun = $request->tahun ?? Carbon::now()->year;
            
            // Calculate date range based on periode
            $dateRange = $this->calculateDateRange($periode, $bulan, $tahun);
            $startDate = $dateRange['start_date'];
            $endDate = $dateRange['end_date'];
            
            Log::info("Fetching data for period: $startDate to $endDate");
            
            $result = [];
            $total = [
                'pesanan' => 0,
                'unit' => 0,
                'pendapatan' => 0
            ];
            
                            if ($tipe === 'barang') {
                // Group by Barang (Product)
                $data = DB::table('pemesanan')
                    ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                    ->select(
                        'barang.barang_id as id',
                        'barang.nama_barang as nama',
                        DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                        DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                        DB::raw('SUM(pemesanan.total) as total_pendapatan')
                    )
                    ->whereBetween('pemesanan.tanggal_pemesanan', [$startDate, $endDate])
                    ->where('pemesanan.status_pemesanan', 'selesai')
                    ->groupBy('barang.barang_id', 'barang.nama_barang')
                    ->get();
                
                // Get catatan for each item
                foreach ($data as $item) {
                    $laporan = PemesananLaporan::where('tipe', 'barang')
                        ->where('reference_id', $item->id)
                        ->where('periode', $periode)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->first();
                    
                    $item->catatan = $laporan ? $laporan->catatan : '';
                    
                    $total['pesanan'] += $item->jumlah_pesanan;
                    $total['unit'] += $item->total_unit;
                    $total['pendapatan'] += $item->total_pendapatan;
                }
                
                $result = $data;
                
            } elseif ($tipe === 'sumber') {
                // Group by Sumber Pemesanan (Order Source)
                $data = DB::table('pemesanan')
                    ->select(
                        'pemesanan.pemesanan_dari as id',
                        'pemesanan.pemesanan_dari as nama',
                        DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                        DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                        DB::raw('SUM(pemesanan.total) as total_pendapatan')
                    )
                    ->whereBetween('pemesanan.tanggal_pemesanan', [$startDate, $endDate])
                    ->where('pemesanan.status_pemesanan', 'selesai')
                    ->groupBy('pemesanan.pemesanan_dari')
                    ->get();
                
                // Get catatan for each item
                foreach ($data as $item) {
                    $laporan = PemesananLaporan::where('tipe', 'sumber')
                        ->where('reference_id', $item->id)
                        ->where('periode', $periode)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->first();
                    
                    $item->catatan = $laporan ? $laporan->catatan : '';
                    
                    $total['pesanan'] += $item->jumlah_pesanan;
                    $total['unit'] += $item->total_unit;
                    $total['pendapatan'] += $item->total_pendapatan;
                }
                
                $result = $data;
                
            } elseif ($tipe === 'pemesan') {
                // Group by Nama Pemesan (Customer Name)
                $data = DB::table('pemesanan')
                    ->select(
                        'pemesanan.nama_pemesan as id',
                        'pemesanan.nama_pemesan as nama',
                        DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                        DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                        DB::raw('SUM(pemesanan.total) as total_pendapatan')
                    )
                    ->whereBetween('pemesanan.tanggal_pemesanan', [$startDate, $endDate])
                    ->where('pemesanan.status_pemesanan', 'selesai')
                    ->groupBy('pemesanan.nama_pemesan')
                    ->get();
                
                // Get catatan for each item
                foreach ($data as $item) {
                    $laporan = PemesananLaporan::where('tipe', 'pemesan')
                        ->where('reference_id', $item->id)
                        ->where('periode', $periode)
                        ->where('bulan', $bulan)
                        ->where('tahun', $tahun)
                        ->first();
                    
                    $item->catatan = $laporan ? $laporan->catatan : '';
                    
                    $total['pesanan'] += $item->jumlah_pesanan;
                    $total['unit'] += $item->total_unit;
                    $total['pendapatan'] += $item->total_pendapatan;
                }
                
                $result = $data;
            }
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'total' => $total,
                'periode' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in getData: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateCatatan(Request $request)
    {
        try {
            $tipe = $request->tipe;
            $id = $request->id;
            $catatan = $request->catatan;
            $periode = $request->periode ?? '1_bulan';
            $bulan = $request->bulan ?? Carbon::now()->month;
            $tahun = $request->tahun ?? Carbon::now()->year;
            
            $laporan = PemesananLaporan::updateOrCreate(
                [
                    'tipe' => $tipe,
                    'reference_id' => $id,
                    'periode' => $periode,
                    'bulan' => $bulan,
                    'tahun' => $tahun
                ],
                [
                    'catatan' => $catatan
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil disimpan'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in updateCatatan: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getDetailData(Request $request)
    {
        try {
            $tipe = $request->tipe;
            $id = $request->id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            
            // Get pemesanan detail based on tipe and id
            $query = DB::table('pemesanan')
                ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                ->select(
                    'pemesanan.pemesanan_id',
                    'pemesanan.tanggal_pemesanan as tanggal',
                    'barang.nama_barang',
                    'pemesanan.nama_pemesan',
                    'pemesanan.jumlah_pesanan as jumlah',
                    'pemesanan.total',
                    'pemesanan.pemesanan_dari as sumber',
                    'pemesanan.status_pemesanan as status'
                )
                ->whereBetween('pemesanan.tanggal_pemesanan', [$startDate, $endDate])
                ->where('pemesanan.status_pemesanan', 'selesai');
            
            if ($tipe === 'barang') {
                $query->where('pemesanan.barang_id', $id);
            } elseif ($tipe === 'sumber') {
                $query->where('pemesanan.pemesanan_dari', $id);
            } elseif ($tipe === 'pemesan') {
                $query->where('pemesanan.nama_pemesan', $id);
            }
            
            $data = $query->orderBy('pemesanan.tanggal_pemesanan', 'desc')->get();
            
            // Prepare chart data (monthly summary)
            $chartData = [];
            $startMonth = Carbon::parse($startDate)->startOfMonth();
            $endMonth = Carbon::parse($endDate)->endOfMonth();
            
            // Generate data untuk setiap bulan dalam range, bahkan jika tidak ada data
            $currentMonth = $startMonth->copy();
            while ($currentMonth->lte($endMonth)) {
                $monthStart = $currentMonth->format('Y-m-d');
                $monthEnd = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
                
                $monthQuery = DB::table('pemesanan')
                    ->select(
                        DB::raw('COUNT(*) as count'),
                        DB::raw('SUM(total) as total')
                    )
                    ->whereBetween('tanggal_pemesanan', [$monthStart, $monthEnd])
                    ->where('status_pemesanan', 'selesai');
                
                if ($tipe === 'barang') {
                    $monthQuery->where('barang_id', $id);
                } elseif ($tipe === 'sumber') {
                    $monthQuery->where('pemesanan_dari', $id);
                } elseif ($tipe === 'pemesan') {
                    $monthQuery->where('nama_pemesan', $id);
                }
                
                $monthData = $monthQuery->first();
                
                $chartData[] = [
                    'month' => $currentMonth->format('M Y'),
                    'count' => (int)($monthData->count ?? 0),
                    'total' => (float)($monthData->total ?? 0)
                ];
                
                $currentMonth->addMonth();
            }
            
            // Debug log
            Log::info('Chart data generated:', $chartData);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'chart_data' => $chartData
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in getDetailData: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function exportCsv(Request $request)
    {
        try {
            $tipe = $request->tipe ?? 'barang';
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $detailId = $request->detail_id ?? null;
            
            // Base query for all tipe
            $query = DB::table('pemesanan')
                ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                ->whereBetween('pemesanan.tanggal_pemesanan', [$startDate, $endDate])
                ->where('pemesanan.status_pemesanan', '!=', 'dibatalkan');
            
            if ($detailId) {
                // Detail mode - get specific entries
                if ($tipe === 'barang') {
                    $query->where('pemesanan.barang_id', $detailId);
                    $groupName = DB::table('barang')->where('barang_id', $detailId)->value('nama_barang');
                } elseif ($tipe === 'sumber') {
                    $query->where('pemesanan.pemesanan_dari', $detailId);
                    $groupName = $detailId; // The source name
                } elseif ($tipe === 'pemesan') {
                    $query->where('pemesanan.nama_pemesan', $detailId);
                    $groupName = $detailId; // The customer name
                }
                
                $data = $query->select(
                    'pemesanan.pemesanan_id',
                    'pemesanan.tanggal_pemesanan',
                    'barang.nama_barang',
                    'pemesanan.nama_pemesan',
                    'pemesanan.jumlah_pesanan',
                    'pemesanan.total',
                    'pemesanan.pemesanan_dari',
                    'pemesanan.status_pemesanan'
                )->orderBy('pemesanan.tanggal_pemesanan', 'desc')->get();
                
                $filename = "Detail_Laporan_Pemesanan_{$tipe}_{$groupName}_" . date('Ymd') . ".csv";
                $columns = [
                    'ID Pemesanan',
                    'Tanggal Pemesanan',
                    'Nama Barang',
                    'Nama Pemesan',
                    'Jumlah Pesanan',
                    'Total (Rp)',
                    'Sumber Pemesanan',
                    'Status'
                ];
                
                $csvData = [];
                foreach ($data as $row) {
                    $csvData[] = [
                        $row->pemesanan_id,
                        $row->tanggal_pemesanan,
                        $row->nama_barang,
                        $row->nama_pemesan,
                        $row->jumlah_pesanan,
                        $row->total,
                        $row->pemesanan_dari,
                        $row->status_pemesanan
                    ];
                }
                
            } else {
                // Summary mode - get aggregated data
                if ($tipe === 'barang') {
                    // Group by Barang (Product)
                    $data = $query->select(
                        'barang.barang_id',
                        'barang.nama_barang',
                        DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                        DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                        DB::raw('SUM(pemesanan.total) as total_pendapatan')
                    )
                    ->groupBy('barang.barang_id', 'barang.nama_barang')
                    ->get();
                    
                    $filename = "Laporan_Pemesanan_Per_Barang_" . date('Ymd') . ".csv";
                    $columns = [
                        'ID Barang',
                        'Nama Barang',
                        'Jumlah Pesanan',
                        'Total Unit',
                        'Total Pendapatan (Rp)'
                    ];
                    
                    $csvData = [];
                    foreach ($data as $row) {
                        $csvData[] = [
                            $row->barang_id,
                            $row->nama_barang,
                            $row->jumlah_pesanan,
                            $row->total_unit,
                            $row->total_pendapatan
                        ];
                    }
                    
                } elseif ($tipe === 'sumber') {
                    // Group by Sumber Pemesanan (Order Source)
                    $data = $query->select(
                        'pemesanan.pemesanan_dari',
                        DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                        DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                        DB::raw('SUM(pemesanan.total) as total_pendapatan')
                    )
                    ->groupBy('pemesanan.pemesanan_dari')
                    ->get();
                    
                    $filename = "Laporan_Pemesanan_Per_Sumber_" . date('Ymd') . ".csv";
                    $columns = [
                        'Sumber Pemesanan',
                        'Jumlah Pesanan',
                        'Total Unit',
                        'Total Pendapatan (Rp)'
                    ];
                    
                    $csvData = [];
                    foreach ($data as $row) {
                        $csvData[] = [
                            $row->pemesanan_dari,
                            $row->jumlah_pesanan,
                            $row->total_unit,
                            $row->total_pendapatan
                        ];
                    }
                    
                } elseif ($tipe === 'pemesan') {
                    // Group by Nama Pemesan (Customer Name)
                    $data = $query->select(
                        'pemesanan.nama_pemesan',
                        DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                        DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                        DB::raw('SUM(pemesanan.total) as total_pendapatan')
                    )
                    ->groupBy('pemesanan.nama_pemesan')
                    ->get();
                    
                    $filename = "Laporan_Pemesanan_Per_Pemesan_" . date('Ymd') . ".csv";
                    $columns = [
                        'Nama Pemesan',
                        'Jumlah Pesanan',
                        'Total Unit',
                        'Total Pendapatan (Rp)'
                    ];
                    
                    $csvData = [];
                    foreach ($data as $row) {
                        $csvData[] = [
                            $row->nama_pemesan,
                            $row->jumlah_pesanan,
                            $row->total_unit,
                            $row->total_pendapatan
                        ];
                    }
                }
            }
            
            // Create CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];
            
            $callback = function() use ($csvData, $columns) {
                $file = fopen('php://output', 'w');
                // Add BOM to fix UTF-8 in Excel
                fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
                fputcsv($file, $columns);
                
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            Log::error("Error in exportCsv: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
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
                if ($bulan === 6) {
                    // Semester 1: Januari - Juni
                    $startDate = Carbon::create($tahun, 1, 1)->startOfMonth();
                    $endDate = Carbon::create($tahun, 6, 1)->endOfMonth();
                } else { // $bulan === 12
                    // Semester 2: Juli - Desember
                    $startDate = Carbon::create($tahun, 7, 1)->startOfMonth();
                    $endDate = Carbon::create($tahun, 12, 1)->endOfMonth();
                }
            } else { // 1_tahun
                // Periode 1 tahun penuh: Januari - Desember
                $startDate = Carbon::create($tahun, 1, 1)->startOfYear();
                $endDate = Carbon::create($tahun, 12, 31)->endOfYear();
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
}
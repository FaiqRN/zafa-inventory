<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Toko;
use App\Models\Pengiriman;
use App\Models\Pemesanan;
use App\Models\Retur;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama dengan 4 komponen visualisasi.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard', [
            'activemenu' => 'dashboard',
            'breadcrumb' => (object) [
                'title' => 'Dashboard CRM Zafa Potato',
                'list' => ['Home', 'Dashboard']
            ]
        ]);
    }

    /**
     * 1. Grafik Pengiriman Barang (Line Chart)
     * Data: Timeline pengiriman barang per tahun
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGrafikPengiriman(Request $request)
    {
        try {
            // Default ke tahun ini jika tidak ada parameter
            $tahun = $request->input('tahun', Carbon::now()->year);
            
            // Query data pengiriman per bulan dalam tahun yang dipilih
            $data = DB::table('pengiriman')
                ->select(
                    DB::raw('MONTH(tanggal_pengiriman) as bulan'),
                    DB::raw('SUM(jumlah_kirim) as total_kirim'),
                    DB::raw('COUNT(*) as jumlah_pengiriman')
                )
                ->whereYear('tanggal_pengiriman', $tahun)
                ->where('status', 'terkirim') // Hanya yang sudah terkirim
                ->groupBy(DB::raw('MONTH(tanggal_pengiriman)'))
                ->orderBy('bulan')
                ->get();

            // Siapkan data untuk 12 bulan (Januari - Desember)
            $chartData = [];
            $chartLabels = [];
            $tooltipData = [];
            
            $namaBulan = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            // Inisialisasi data untuk 12 bulan
            for ($i = 1; $i <= 12; $i++) {
                $chartLabels[] = $namaBulan[$i];
                $chartData[] = 0;
                $tooltipData[] = [
                    'bulan' => $namaBulan[$i],
                    'total_kirim' => 0,
                    'jumlah_pengiriman' => 0
                ];
            }

            // Isi data yang ada
            foreach ($data as $item) {
                $index = $item->bulan - 1; // Index array (0-11)
                $chartData[$index] = (int)$item->total_kirim;
                $tooltipData[$index] = [
                    'bulan' => $namaBulan[$item->bulan],
                    'total_kirim' => (int)$item->total_kirim,
                    'jumlah_pengiriman' => (int)$item->jumlah_pengiriman
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'labels' => $chartLabels,
                    'datasets' => [[
                        'label' => "Jumlah Barang Dikirim Tahun {$tahun}",
                        'data' => $chartData,
                        'borderColor' => 'rgb(75, 192, 192)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'tension' => 0.1,
                        'fill' => true
                    ]],
                    'tooltip_data' => $tooltipData,
                    'tahun' => $tahun
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getGrafikPengiriman: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data grafik pengiriman: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. Barang Laku/Tidak Laku (Bar Chart Interaktif) - DIPERBAIKI TOTAL
     * Filter: barang laku vs tidak laku berdasarkan data pengiriman
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBarangLakuTidakLaku(Request $request)
    {
        try {
            $filter = $request->input('filter', 'laku'); // 'laku' atau 'tidak_laku'
            $limit = $request->input('limit', 10);
            $periode = $request->input('periode', 6); // 6 bulan terakhir untuk analisis lebih komprehensif

            // Tentukan rentang waktu analisis
            $startDate = Carbon::now()->subMonths($periode)->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            Log::info("Dashboard Barang Analysis - Filter: {$filter}, Period: {$startDate} to {$endDate}");

            if ($filter === 'laku') {
                // Query untuk barang LAKU berdasarkan pengiriman
                $data = DB::table('pengiriman')
                    ->join('barang', 'pengiriman.barang_id', '=', 'barang.barang_id')
                    ->select(
                        'barang.barang_id',
                        'barang.nama_barang',
                        'barang.harga_awal_barang',
                        DB::raw('SUM(pengiriman.jumlah_kirim) as total_terjual'),
                        DB::raw('COUNT(pengiriman.pengiriman_id) as jumlah_transaksi'),
                        DB::raw('SUM(pengiriman.jumlah_kirim * barang.harga_awal_barang) as total_penjualan')
                    )
                    ->whereBetween('pengiriman.tanggal_pengiriman', [$startDate, $endDate])
                    ->where('pengiriman.status', 'terkirim')
                    ->where('barang.is_deleted', 0)
                    ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.harga_awal_barang')
                    ->orderByDesc('total_terjual')
                    ->limit($limit)
                    ->get();
            } else {
                // Query untuk barang TIDAK LAKU/KURANG LAKU
                // Strategi: Ambil semua barang, lalu hitung penjualannya, tampilkan yang paling sedikit
                
                $data = DB::table('barang')
                    ->leftJoin('pengiriman', function($join) use ($startDate, $endDate) {
                        $join->on('barang.barang_id', '=', 'pengiriman.barang_id')
                            ->whereBetween('pengiriman.tanggal_pengiriman', [$startDate, $endDate])
                            ->where('pengiriman.status', 'terkirim');
                    })
                    ->select(
                        'barang.barang_id',
                        'barang.nama_barang',
                        'barang.harga_awal_barang',
                        DB::raw('COALESCE(SUM(pengiriman.jumlah_kirim), 0) as total_terjual'),
                        DB::raw('COALESCE(COUNT(pengiriman.pengiriman_id), 0) as jumlah_transaksi'),
                        DB::raw('COALESCE(SUM(pengiriman.jumlah_kirim * barang.harga_awal_barang), 0) as total_penjualan')
                    )
                    ->where('barang.is_deleted', 0)
                    ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.harga_awal_barang')
                    ->orderBy('total_terjual', 'asc') // Yang paling sedikit terjual
                    ->limit($limit)
                    ->get();
            }

            Log::info("Dashboard Barang Analysis - Found " . count($data) . " items for filter: {$filter}");

            // Format data untuk chart
            $chartLabels = [];
            $chartData = [];
            $backgroundColors = [];
            $detailData = [];

            foreach ($data as $item) {
                $chartLabels[] = $item->nama_barang;
                $chartData[] = (int)$item->total_terjual;
                
                if ($filter === 'laku') {
                    $backgroundColors[] = 'rgba(34, 197, 94, 0.8)'; // Hijau
                } else {
                    $backgroundColors[] = 'rgba(239, 68, 68, 0.8)'; // Merah
                }

                $detailData[] = [
                    'nama_barang' => $item->nama_barang,
                    'total_terjual' => (int)$item->total_terjual,
                    'total_penjualan' => (float)($item->total_penjualan ?? 0),
                    'jumlah_transaksi' => (int)($item->jumlah_transaksi ?? 0)
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'labels' => $chartLabels,
                    'datasets' => [[
                        'label' => $filter === 'laku' ? 'Barang Terlaris' : 'Barang Kurang Laku',
                        'data' => $chartData,
                        'backgroundColor' => $backgroundColors,
                        'borderColor' => $backgroundColors,
                        'borderWidth' => 1
                    ]],
                    'detail_data' => $detailData,
                    'filter' => $filter,
                    'periode' => [
                        'mulai' => $startDate->format('d/m/Y'),
                        'akhir' => $endDate->format('d/m/Y')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getBarangLakuTidakLaku: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data barang: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. Transaksi Terbaru Pengiriman (Tabel Data)
     * Data pengiriman terbaru
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransaksiTerbaru(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);

            Log::info("Dashboard - Loading transaksi terbaru, limit: {$limit}");

            // Ambil data pengiriman terbaru
            $pengiriman = DB::table('pengiriman')
                ->join('barang', 'pengiriman.barang_id', '=', 'barang.barang_id')
                ->join('toko', 'pengiriman.toko_id', '=', 'toko.toko_id')
                ->select(
                    'pengiriman.pengiriman_id',
                    'pengiriman.nomer_pengiriman',
                    'pengiriman.tanggal_pengiriman',
                    'barang.nama_barang',
                    'toko.nama_toko as tujuan',
                    'pengiriman.jumlah_kirim',
                    'pengiriman.status',
                    'barang.harga_awal_barang',
                    DB::raw('(pengiriman.jumlah_kirim * barang.harga_awal_barang) as total_harga'),
                    DB::raw("'toko' as jenis_pengiriman"),
                    DB::raw("'-' as nama_pemesan")
                )
                ->orderByDesc('pengiriman.tanggal_pengiriman')
                ->limit($limit)
                ->get();

            Log::info("Dashboard - Found " . count($pengiriman) . " pengiriman records");

            // Format data untuk tabel
            $tableData = [];
            foreach ($pengiriman as $item) {
                $tableData[] = [
                    'pengiriman_id' => $item->pengiriman_id,
                    'nomer_pengiriman' => $item->nomer_pengiriman,
                    'tanggal_pengiriman' => Carbon::parse($item->tanggal_pengiriman)->format('d/m/Y H:i'),
                    'nama_barang' => $item->nama_barang,
                    'tujuan' => $item->tujuan,
                    'nama_pemesan' => $item->nama_pemesan,
                    'jumlah_pesanan' => (int)$item->jumlah_kirim,
                    'total_harga' => (int)$item->total_harga,
                    'status_pemesanan' => $this->formatStatusPengiriman($item->status),
                    'status_badge' => $this->getStatusBadge($item->status),
                    'jenis_pengiriman' => $item->jenis_pengiriman
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $tableData
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTransaksiTerbaru: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. Toko dengan Retur Terbanyak - DIPERBAIKI UNTUK MENAMPILKAN DATA HISTORIS
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTokoReturTerbanyak(Request $request)
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $periode = (int) $request->input('periode', 12); // DIPERLUAS ke 12 bulan untuk data historis

            // Tentukan rentang tanggal yang lebih luas
            $startDate = Carbon::now()->subMonths($periode)->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            Log::info("Dashboard - Loading toko retur, period: {$startDate} to {$endDate}");

            // Query yang diperbaiki untuk menampilkan data historis
            $topReturToko = DB::table('retur')
                ->join('toko', 'retur.toko_id', '=', 'toko.toko_id')
                ->select(
                    'toko.toko_id',
                    'toko.nama_toko',
                    'toko.pemilik',
                    DB::raw('COUNT(retur.retur_id) as jumlah_retur'),
                    DB::raw('SUM(retur.jumlah_retur) as total_barang_retur'),
                    DB::raw('SUM(retur.jumlah_kirim) as total_barang_kirim'),
                    DB::raw('
                        CASE 
                            WHEN SUM(retur.jumlah_kirim) > 0 
                            THEN ROUND((SUM(retur.jumlah_retur) / SUM(retur.jumlah_kirim)) * 100, 2)
                            ELSE 0 
                        END as persentase_retur
                    '),
                    DB::raw('MIN(retur.tanggal_retur) as retur_pertama'),
                    DB::raw('MAX(retur.tanggal_retur) as retur_terakhir')
                )
                ->whereBetween('retur.tanggal_retur', [$startDate, $endDate])
                ->where('retur.jumlah_retur', '>', 0)
                ->groupBy('toko.toko_id', 'toko.nama_toko', 'toko.pemilik')
                ->having('jumlah_retur', '>', 0) // Pastikan minimal ada 1 retur
                ->orderByDesc('jumlah_retur') // Urutkan berdasarkan jumlah retur terbanyak
                ->orderByDesc('persentase_retur') // Lalu berdasarkan persentase retur
                ->limit($limit)
                ->get();

            Log::info("Dashboard - Found " . count($topReturToko) . " toko retur records");

            // Format data untuk tampilan
            $data = $topReturToko->map(function ($item) {
                return [
                    'toko_id' => $item->toko_id,
                    'nama_toko' => $item->nama_toko,
                    'pemilik' => $item->pemilik,
                    'alamat' => '', // Kosongkan dulu untuk sederhana
                    'jumlah_retur' => (int) $item->jumlah_retur,
                    'total_barang_retur' => (int) $item->total_barang_retur,
                    'total_barang_kirim' => (int) $item->total_barang_kirim,
                    'persentase_retur' => (float) $item->persentase_retur,
                    'rating_class' => $this->getReturRatingClass($item->persentase_retur),
                    'retur_pertama' => $item->retur_pertama ? Carbon::parse($item->retur_pertama)->format('d/m/Y') : '',
                    'retur_terakhir' => $item->retur_terakhir ? Carbon::parse($item->retur_terakhir)->format('d/m/Y') : ''
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'periode' => [
                    'mulai' => $startDate->format('d/m/Y'),
                    'akhir' => $endDate->format('d/m/Y'),
                    'bulan' => $periode
                ],
                'total_found' => count($data)
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTokoReturTerbanyak: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memuat data retur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API untuk mendapatkan ringkasan statistik dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistikRingkasan()
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            
            Log::info("Dashboard - Loading statistik ringkasan for date: {$today}");

            // Total keseluruhan
            $totalBarang = Barang::where('is_deleted', 0)->count();
            $totalToko = Toko::count();
            
            // Statistik bulan ini
            $pengirimanBulanIni = Pengiriman::whereDate('tanggal_pengiriman', '>=', $thisMonth)->count();
            $pemesananBulanIni = Pemesanan::whereDate('tanggal_pemesanan', '>=', $thisMonth)->count();
            $returBulanIni = Retur::whereDate('tanggal_retur', '>=', $thisMonth)->count();
            
            // Statistik hari ini
            $pengirimanHariIni = Pengiriman::whereDate('tanggal_pengiriman', $today)->count();
            $pemesananHariIni = Pemesanan::whereDate('tanggal_pemesanan', $today)->count();

            Log::info("Dashboard - Statistik: Barang={$totalBarang}, Toko={$totalToko}, PengirimanHari={$pengirimanHariIni}");

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_barang' => $totalBarang,
                    'total_toko' => $totalToko,
                    'pengiriman_bulan_ini' => $pengirimanBulanIni,
                    'pemesanan_bulan_ini' => $pemesananBulanIni,
                    'retur_bulan_ini' => $returBulanIni,
                    'pengiriman_hari_ini' => $pengirimanHariIni,
                    'pemesanan_hari_ini' => $pemesananHariIni
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getStatistikRingkasan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Format status pengiriman
     * 
     * @param string $status
     * @return string
     */
    private function formatStatusPengiriman($status)
    {
        switch (strtolower($status)) {
            case 'proses':
                return 'Sedang Diproses';
            case 'terkirim':
            case 'dikirim':
                return 'Sudah Terkirim';
            case 'selesai':
                return 'Selesai';
            case 'batal':
                return 'Dibatalkan';
            default:
                return ucfirst($status);
        }
    }

    /**
     * Helper: Dapatkan badge HTML untuk status
     * 
     * @param string $status
     * @return string
     */
    private function getStatusBadge($status)
    {
        switch (strtolower($status)) {
            case 'proses':
                return '<span class="badge badge-warning">Proses</span>';
            case 'terkirim':
            case 'dikirim':
                return '<span class="badge badge-success">Terkirim</span>';
            case 'selesai':
                return '<span class="badge badge-info">Selesai</span>';
            case 'batal':
                return '<span class="badge badge-danger">Batal</span>';
            default:
                return '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
        }
    }

    /**
     * Helper: Dapatkan class rating berdasarkan persentase retur
     * 
     * @param float $persentase
     * @return string
     */
    private function getReturRatingClass($persentase)
    {
        if ($persentase <= 2) {
            return 'text-success'; // Hijau - sangat baik
        } elseif ($persentase <= 5) {
            return 'text-info'; // Biru - baik
        } elseif ($persentase <= 10) {
            return 'text-warning'; // Kuning - sedang
        } else {
            return 'text-danger'; // Merah - buruk
        }
    }

    /**
     * API DEBUG - untuk mengetahui struktur data tabel
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function debug()
    {
        try {
            $debug = [
                'today' => Carbon::today()->format('Y-m-d'),
                'thisMonth' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'tables' => [
                    'barang_count' => Barang::count(),
                    'toko_count' => Toko::count(),
                    'pengiriman_count' => Pengiriman::count(),
                    'pemesanan_count' => Pemesanan::count(),
                    'retur_count' => Retur::count()
                ],
                'sample_data' => [
                    'pengiriman_columns' => DB::getSchemaBuilder()->getColumnListing('pengiriman'),
                    'barang_columns' => DB::getSchemaBuilder()->getColumnListing('barang'),
                    'toko_columns' => DB::getSchemaBuilder()->getColumnListing('toko'),
                    'retur_columns' => DB::getSchemaBuilder()->getColumnListing('retur'),
                    'sample_pengiriman' => Pengiriman::latest()->first(),
                    'sample_barang' => Barang::first(),
                    'sample_toko' => Toko::first(),
                    'sample_retur' => Retur::latest()->first()
                ],
                'periode_analysis' => [
                    'retur_6_months' => Retur::where('tanggal_retur', '>=', Carbon::now()->subMonths(6))->count(),
                    'retur_12_months' => Retur::where('tanggal_retur', '>=', Carbon::now()->subMonths(12))->count(),
                    'barang_with_pengiriman' => DB::table('barang')
                        ->join('pengiriman', 'barang.barang_id', '=', 'pengiriman.barang_id')
                        ->where('barang.is_deleted', 0)
                        ->distinct('barang.barang_id')
                        ->count(),
                    'barang_without_pengiriman' => DB::table('barang')
                        ->leftJoin('pengiriman', 'barang.barang_id', '=', 'pengiriman.barang_id')
                        ->where('barang.is_deleted', 0)
                        ->whereNull('pengiriman.pengiriman_id')
                        ->count()
                ]
            ];

            return response()->json([
                'status' => 'success',
                'debug' => $debug
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
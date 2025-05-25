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
     * 2. Barang Laku/Tidak Laku (Bar Chart Interaktif)
     * Filter: barang laku vs tidak laku
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBarangLakuTidakLaku(Request $request)
    {
        try {
            $filter = $request->input('filter', 'laku'); // 'laku' atau 'tidak_laku'
            $limit = $request->input('limit', 10); // Batasi hasil

            // Hitung penjualan bersih (total kirim - total retur) per barang
            if ($filter === 'laku') {
                // Barang dengan penjualan tertinggi
                $data = DB::table('barang')
                    ->leftJoin('retur', 'barang.barang_id', '=', 'retur.barang_id')
                    ->select(
                        'barang.barang_id',
                        'barang.nama_barang',
                        DB::raw('COALESCE(SUM(retur.total_terjual), 0) as total_terjual'),
                        DB::raw('COALESCE(SUM(retur.hasil), 0) as total_penjualan'),
                        DB::raw('COUNT(retur.retur_id) as jumlah_transaksi')
                    )
                    ->where('barang.is_deleted', 0)
                    ->groupBy('barang.barang_id', 'barang.nama_barang')
                    ->having('total_terjual', '>', 0)
                    ->orderBy('total_terjual', 'desc')
                    ->limit($limit)
                    ->get();
            } else {
                // Barang dengan penjualan rendah atau tidak ada
                $data = DB::table('barang')
                    ->leftJoin('retur', 'barang.barang_id', '=', 'retur.barang_id')
                    ->select(
                        'barang.barang_id',
                        'barang.nama_barang',
                        DB::raw('COALESCE(SUM(retur.total_terjual), 0) as total_terjual'),
                        DB::raw('COALESCE(SUM(retur.hasil), 0) as total_penjualan'),
                        DB::raw('COUNT(retur.retur_id) as jumlah_transaksi')
                    )
                    ->where('barang.is_deleted', 0)
                    ->groupBy('barang.barang_id', 'barang.nama_barang')
                    ->orderBy('total_terjual', 'asc')
                    ->limit($limit)
                    ->get();
            }

            // Format data untuk chart
            $chartLabels = [];
            $chartData = [];
            $backgroundColors = [];
            $detailData = [];

            foreach ($data as $item) {
                $chartLabels[] = $item->nama_barang;
                $chartData[] = (int)$item->total_terjual;
                
                // Warna berbeda untuk barang laku (hijau) dan tidak laku (merah)
                if ($filter === 'laku') {
                    $backgroundColors[] = 'rgba(34, 197, 94, 0.8)'; // Hijau
                } else {
                    $backgroundColors[] = 'rgba(239, 68, 68, 0.8)'; // Merah
                }

                $detailData[] = [
                    'nama_barang' => $item->nama_barang,
                    'total_terjual' => (int)$item->total_terjual,
                    'total_penjualan' => (int)$item->total_penjualan,
                    'jumlah_transaksi' => (int)$item->jumlah_transaksi
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'labels' => $chartLabels,
                    'datasets' => [[
                        'label' => $filter === 'laku' ? 'Barang Terlaris' : 'Barang Kurang Laris',
                        'data' => $chartData,
                        'backgroundColor' => $backgroundColors,
                        'borderColor' => $backgroundColors,
                        'borderWidth' => 1
                    ]],
                    'detail_data' => $detailData,
                    'filter' => $filter
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
     * Gabungan data pemesanan dan pengiriman
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransaksiTerbaru(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);

            // Ambil data pengiriman ke toko
            $pengirimanToko = DB::table('pengiriman')
                ->join('barang', 'pengiriman.barang_id', '=', 'barang.barang_id')
                ->join('toko', 'pengiriman.toko_id', '=', 'toko.toko_id')
                ->leftJoin('pemesanan', function($join) {
                    $join->on('pengiriman.barang_id', '=', 'pemesanan.barang_id')
                        ->whereRaw('DATE(pengiriman.tanggal_pengiriman) = DATE(pemesanan.tanggal_pemesanan)');
                })
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
                    'pemesanan.nama_pemesan',
                    DB::raw("'toko' as jenis_pengiriman")
                );

            // Ambil data pengiriman langsung ke customer dari pemesanan yang statusnya 'dikirim'
            $pengirimanCustomer = DB::table('pemesanan')
                ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                ->select(
                    DB::raw('NULL as pengiriman_id'),
                    DB::raw('pemesanan_id as nomer_pengiriman'),
                    'tanggal_dikirim as tanggal_pengiriman',
                    'barang.nama_barang',
                    'pemesanan.nama_pemesan as tujuan',
                    'pemesanan.jumlah_pesanan as jumlah_kirim',
                    'pemesanan.status_pemesanan as status',
                    'barang.harga_awal_barang',
                    DB::raw('(pemesanan.jumlah_pesanan * barang.harga_awal_barang) as total_harga'),
                    'pemesanan.nama_pemesan',
                    DB::raw("'customer' as jenis_pengiriman")
                )
                ->whereNotNull('tanggal_dikirim')
                ->where('status_pemesanan', 'dikirim');

            // Gabungkan data pengiriman ke toko dan ke customer
            $dataGabungan = $pengirimanToko
                ->unionAll($pengirimanCustomer)
                ->orderBy('tanggal_pengiriman', 'desc')
                ->limit($limit)
                ->get();

            // Format data untuk tabel
            $tableData = [];
            foreach ($dataGabungan as $item) {
                $tableData[] = [
                    'pengiriman_id' => $item->pengiriman_id,
                    'nomer_pengiriman' => $item->nomer_pengiriman,
                    'tanggal_pengiriman' => Carbon::parse($item->tanggal_pengiriman)->format('d/m/Y'),
                    'nama_barang' => $item->nama_barang,
                    'tujuan' => $item->tujuan,
                    'nama_pemesan' => $item->nama_pemesan ?: '-',
                    'jumlah_pesanan' => (int)$item->jumlah_kirim,
                    'total_harga' => (int)$item->total_harga,
                    'status_pemesanan' => $this->formatStatusPengiriman($item->status),
                    'status_badge' => $this->getStatusBadge($item->status),
                    'jenis_pengiriman' => $item->jenis_pengiriman // bisa 'toko' atau 'customer'
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
     * 4. Toko dengan Retur Terbanyak (3 Bulan Terakhir)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTokoReturTerbanyak(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            
            // 3 bulan terakhir
            $tanggalMulai = Carbon::now()->subMonths(3)->startOfMonth();
            $tanggalAkhir = Carbon::now()->endOfMonth();

            $data = DB::table('retur')
                ->join('toko', 'retur.toko_id', '=', 'toko.toko_id')
                ->select(
                    'toko.toko_id',
                    'toko.nama_toko',
                    'toko.pemilik',
                    'toko.alamat',
                    DB::raw('COUNT(retur.retur_id) as jumlah_retur'),
                    DB::raw('SUM(retur.jumlah_retur) as total_barang_retur'),
                    DB::raw('SUM(retur.jumlah_kirim) as total_barang_kirim'),
                    DB::raw('ROUND((SUM(retur.jumlah_retur) / SUM(retur.jumlah_kirim)) * 100, 2) as persentase_retur')
                )
                ->whereBetween('retur.tanggal_retur', [$tanggalMulai, $tanggalAkhir])
                ->groupBy('toko.toko_id', 'toko.nama_toko', 'toko.pemilik', 'toko.alamat')
                ->orderBy('jumlah_retur', 'desc')
                ->limit($limit)
                ->get();

            // Format data untuk tabel
            $tableData = [];
            foreach ($data as $item) {
                $tableData[] = [
                    'toko_id' => $item->toko_id,
                    'nama_toko' => $item->nama_toko,
                    'pemilik' => $item->pemilik,
                    'alamat' => $item->alamat,
                    'jumlah_retur' => (int)$item->jumlah_retur,
                    'total_barang_retur' => (int)$item->total_barang_retur,
                    'total_barang_kirim' => (int)$item->total_barang_kirim,
                    'persentase_retur' => (float)$item->persentase_retur,
                    'rating_class' => $this->getReturRatingClass($item->persentase_retur)
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $tableData,
                'periode' => [
                    'mulai' => $tanggalMulai->format('d/m/Y'),
                    'akhir' => $tanggalAkhir->format('d/m/Y')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTokoReturTerbanyak: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data toko retur: ' . $e->getMessage()
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
        switch ($status) {
            case 'proses':
                return 'Sedang Diproses';
            case 'terkirim':
                return 'Sudah Terkirim';
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
        switch ($status) {
            case 'proses':
                return '<span class="badge badge-warning">Proses</span>';
            case 'terkirim':
                return '<span class="badge badge-success">Terkirim</span>';
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
        if ($persentase <= 5) {
            return 'text-success'; // Hijau - baik
        } elseif ($persentase <= 15) {
            return 'text-warning'; // Kuning - sedang
        } else {
            return 'text-danger'; // Merah - buruk
        }
    }
}
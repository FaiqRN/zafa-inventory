<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Toko;
use App\Models\Pengiriman;
use App\Models\Pemesanan;
use App\Models\Retur;
use App\Models\User;
use App\Helpers\dashboardInventoryOptimization\DashboardInventoryOptimizationHelper;
use App\Services\RekomendasiService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class DashboardInventoryOptimizationController extends Controller
{

    public function resolveDashboard()
    {
        $authIdentifier = Auth::id();
        $user = $authIdentifier !== null
            ? User::query()->where(User::FIELD_USERNAME, (string) $authIdentifier)->first()
            : null;
        $targetRoute = null;
        $warningMessage = null;

        if (!$user) {
            $targetRoute = 'login';
        } elseif (Gate::forUser($user)->allows('view-dashboard-inventory-optimization')) {
            $targetRoute = 'dashboard.inventory-optimization';
        } elseif (Gate::forUser($user)->allows('view-dashboard-partner-performance')) {
            $targetRoute = 'dashboard.partner-performance';
        } else {
            $targetRoute = $this->getFirstAllowedModuleRoute($user) ?? 'profile';

            if ($targetRoute === 'profile') {
                $warningMessage = 'Anda tidak memiliki akses dashboard atau modul yang tersedia.';
            }
        }

        $redirectResponse = redirect()->route($targetRoute);
        if ($warningMessage) {
            $redirectResponse->with('warning', $warningMessage);
        }

        return $redirectResponse;
    }

    public function index()
    {
        $dashboardData = DashboardInventoryOptimizationHelper::getDashboardData();
        
        return view('Dashboard_InventoryOptimization', [
            'rekomendasiData' => $dashboardData['rekomendasiData'],
            'tokosGeo' => $dashboardData['tokosGeo'],
            'nominatimBaseUrl' => $dashboardData['nominatimBaseUrl'],
            'activemenu' => 'dashboard',
            'breadcrumb' => (object) [
                'title' => 'Dashboard Inventory Optimization',
                'list' => ['Home', 'Dashboard']
            ]
        ]);
    }

    public function partnerPerformance()
    {
        return view('Dashboard_PartnerPerformance', [
            'activemenu' => 'dashboard',
            'breadcrumb' => (object) [
                'title' => 'Dashboard Partner Performance',
                'list' => ['Home', 'Dashboard Partner Performance']
            ]
        ]);
    }

    private function getFirstAllowedModuleRoute($user): ?string
    {
        $moduleFallbacks = [
            'view-barang' => 'barang.index',
            'view-toko' => 'toko.index',
            'view-barang-toko' => 'barang-toko.index',
            'view-customer' => 'customer.index',
            'view-pengiriman' => 'pengiriman.index',
            'view-retur' => 'retur.index',
            'view-pemesanan' => 'pemesanan.index',
            'view-follow-up' => 'follow-up-pelanggan.index',
            'manage-users' => 'user.index',
            'manage-notification-settings' => 'notification-settings.index',
            'view-eoq-setting' => 'eoq-setting.index',
            'view-zscore-setting' => 'zscore-setting.index',
        ];

        foreach ($moduleFallbacks as $permission => $routeName) {
            if (Gate::forUser($user)->allows($permission)) {
                return $routeName;
            }
        }

        return null;
    }

    public function recalculate(Request $request, RekomendasiService $rekomendasiService)
    {
        try {
            $hariObservasi = max(
                1,
                (int) $request->input('hari_observasi', DashboardInventoryOptimizationHelper::DEFAULT_HARI_OBSERVASI)
            );
            $hasil = $rekomendasiService->hitungSemua($hariObservasi);

            return redirect()->route('dashboard.inventory-optimization')
                ->with('success', 'Perhitungan optimasi inventory selesai. Berhasil: '
                    . ($hasil['berhasil'] ?? 0)
                    . ', Gagal: '
                    . ($hasil['gagal'] ?? 0)
                    . '.');
        } catch (\Throwable $e) {
            Log::error('Error in dashboard recalculate: ' . $e->getMessage());

            return redirect()->route('dashboard.inventory-optimization')
                ->with('error', 'Gagal melakukan perhitungan ulang: ' . $e->getMessage());
        }
    }

    /**
     * Auto refresh data dashboard inventory optimization.
     *
     * FIX: Endpoint ini sekarang HANYA membaca data terbaru dari DB,
     * TIDAK lagi menghitung ulang (hitungSemua) setiap dipanggil.
     *
     * Sebelumnya endpoint ini memanggil hitungSemua() setiap 10 detik
     * yang menyebabkan:
     *   1. Ratusan baris duplikat di inventory_rekomendasi
     *   2. Beban DB yang sangat berat (kalkulasi EOQ/SS/ROP berulang)
     *   3. Nilai yang tidak stabil karena terus di-overwrite
     *
     * Kalkulasi ulang sekarang hanya dilakukan lewat:
     *   - Tombol "Hitung Ulang" → route recalculate (manual oleh user)
     *   - Scheduler harian (jika dikonfigurasi)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoRefreshInventoryOptimization()
    {
        try {
            // Hanya ambil data terbaru — tidak ada kalkulasi sama sekali
            $dashboardData = DashboardInventoryOptimizationHelper::getLatestDataOnly();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data dashboard diperbarui.',
                'data'    => $dashboardData,
                'meta'    => [
                    'updated_at'       => now()->toIso8601String(),
                    'interval_seconds' => 300,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in dashboard auto refresh: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat data dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getGrafikPengiriman(Request $request)
    {
        try {
            $tahun = $request->input('tahun', Carbon::now()->year);
            $data = DB::table('pengiriman')
                ->select(
                    DB::raw('MONTH(tanggal_pengiriman) as bulan'),
                    DB::raw('SUM(jumlah_kirim) as total_kirim'),
                    DB::raw('COUNT(*) as jumlah_pengiriman')
                )
                ->whereYear('tanggal_pengiriman', $tahun)
                ->where('status', 'terkirim') 
                ->groupBy(DB::raw('MONTH(tanggal_pengiriman)'))
                ->orderBy('bulan')
                ->get();
            $chartData = [];
            $chartLabels = [];
            $tooltipData = [];
            
            $namaBulan = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            foreach (range(1, 12) as $bulan) {
                $found = $data->firstWhere('bulan', $bulan);
                $chartLabels[] = $namaBulan[$bulan];
                $chartData[]   = $found ? (int) $found->total_kirim : 0;
                $tooltipData[] = $found ? (int) $found->jumlah_pengiriman : 0;
            }

            return response()->json([
                'status' => 'success',
                'labels' => $chartLabels,
                'data'   => $chartData,
                'tooltip'=> $tooltipData,
                'tahun'  => $tahun,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getGrafikPengiriman: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat grafik: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatistikRingkasan()
    {
        try {
            $today     = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();

            $totalBarang  = Barang::count();
            $totalToko    = Toko::count();

            $pengirimanBulanIni = Pengiriman::whereDate('tanggal_pengiriman', '>=', $thisMonth)->count();
            $pemesananBulanIni  = Pemesanan::whereDate('tanggal_pemesanan', '>=', $thisMonth)->count();
            $returBulanIni      = Retur::whereDate('tanggal_retur', '>=', $thisMonth)->count();
            $pengirimanHariIni  = Pengiriman::whereDate('tanggal_pengiriman', $today)->count();
            $pemesananHariIni   = Pemesanan::whereDate('tanggal_pemesanan', $today)->count();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'total_barang'        => $totalBarang,
                    'total_toko'          => $totalToko,
                    'pengiriman_bulan_ini'=> $pengirimanBulanIni,
                    'pemesanan_bulan_ini' => $pemesananBulanIni,
                    'retur_bulan_ini'     => $returBulanIni,
                    'pengiriman_hari_ini' => $pengirimanHariIni,
                    'pemesanan_hari_ini'  => $pemesananHariIni,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getStatistikRingkasan: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatStatusPengiriman($status)
    {
        switch (strtolower($status)) {
            case 'proses':   return 'Sedang Diproses';
            case 'terkirim':
            case 'dikirim':  return 'Sudah Terkirim';
            case 'selesai':  return 'Selesai';
            case 'batal':    return 'Dibatalkan';
            default:         return ucfirst($status);
        }
    }

    private function getStatusBadge($status)
    {
        switch (strtolower($status)) {
            case 'proses':   return '<span class="badge badge-warning">Proses</span>';
            case 'terkirim':
            case 'dikirim':  return '<span class="badge badge-success">Terkirim</span>';
            case 'selesai':  return '<span class="badge badge-info">Selesai</span>';
            case 'batal':    return '<span class="badge badge-danger">Batal</span>';
            default:         return '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
        }
    }

    private function getReturRatingClass($persentase)
    {
        if ($persentase <= 2)  return 'text-success';
        if ($persentase <= 5)  return 'text-info';
        if ($persentase <= 10) return 'text-warning';
        return 'text-danger';
    }

    public function debug()
    {
        try {
            $debug = [
                'today'     => Carbon::today()->format('Y-m-d'),
                'thisMonth' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'tables'    => [
                    'barang_count'     => Barang::count(),
                    'toko_count'       => Toko::count(),
                    'pengiriman_count' => Pengiriman::count(),
                    'pemesanan_count'  => Pemesanan::count(),
                    'retur_count'      => Retur::count(),
                ],
                'sample_data' => [
                    'pengiriman_columns' => DB::getSchemaBuilder()->getColumnListing('pengiriman'),
                    'barang_columns'     => DB::getSchemaBuilder()->getColumnListing('barang'),
                    'toko_columns'       => DB::getSchemaBuilder()->getColumnListing('toko'),
                    'retur_columns'      => DB::getSchemaBuilder()->getColumnListing('retur'),
                    'sample_pengiriman'  => Pengiriman::latest()->first(),
                    'sample_barang'      => Barang::first(),
                    'sample_toko'        => Toko::first(),
                    'sample_retur'       => Retur::latest()->first(),
                ],
                'periode_analysis' => [
                    'retur_6_months'  => Retur::where('tanggal_retur', '>=', Carbon::now()->subMonths(6))->count(),
                    'retur_12_months' => Retur::where('tanggal_retur', '>=', Carbon::now()->subMonths(12))->count(),
                ],
            ];

            return response()->json(['status' => 'success', 'debug' => $debug]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
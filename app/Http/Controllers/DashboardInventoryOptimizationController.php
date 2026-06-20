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
        } elseif ($user->hasAnyRole(['Admin', 'admin', 'Superadmin', 'superadmin', 'Administrator', 'administrator'])) {
            $targetRoute = 'dashboard-monitor.index';
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

    public function recalculate()
    {
        try {
            /** @var \App\Services\RekomendasiService $rekomendasiService */
            $rekomendasiService = app(\App\Services\RekomendasiService::class);
            $hasil = $rekomendasiService->truncateAndRegenerate();

            $dashboardData = DashboardInventoryOptimizationHelper::getLatestDataOnly();

            return response()->json([
                'status'  => 'success',
                'message' => 'Hitung ulang selesai.',
                'meta'    => [
                    'berhasil'         => $hasil['berhasil'],
                    'gagal'            => $hasil['gagal'],
                    'truncated'        => $hasil['truncated'],
                    'updated_at'       => now()->toIso8601String(),
                    'interval_seconds' => 300,
                ],
                'data'    => $dashboardData,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in recalculate: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal hitung ulang: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function autoRefreshInventoryOptimization()
    {
        try {
            /** @var \App\Services\RekomendasiService $rekomendasiService */
            $rekomendasiService = app(\App\Services\RekomendasiService::class);

            $hasil = $rekomendasiService->truncateAndRegenerate();

            $dashboardData = DashboardInventoryOptimizationHelper::getLatestDataOnly();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data dashboard diperbarui.',
                'data'    => $dashboardData,
                'meta'    => [
                    'berhasil'         => $hasil['berhasil'],
                    'gagal'            => $hasil['gagal'],
                    'truncated'        => $hasil['truncated'],
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

}
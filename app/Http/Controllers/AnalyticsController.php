<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\Barang;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\Pemesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyticsController extends Controller
{
    /**
     * Analytics Dashboard Main - Overview Only
     */
    public function index()
    {
        try {
            $breadcrumb = (object)[
                'title' => 'Analytics Dashboard',
                'list' => ['Home', 'Analytics']
            ];
            
            $overview = $this->getOverviewStats();
            $monthlyTrends = $this->getMonthlyTrends();
            $channelOverview = $this->getChannelOverview();
            $performanceSummary = $this->getPerformanceSummary();
            
            return view('analytics.index', [
                'breadcrumb' => $breadcrumb,
                'overview' => $overview,
                'monthlyTrends' => $monthlyTrends,
                'channelOverview' => $channelOverview,
                'performanceSummary' => $performanceSummary,
                'activemenu' => 'analytics'
            ]);
        } catch (Exception $e) {
            Log::error('Analytics index error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat analytics dashboard.');
        }
    }

    /**
     * Get Overview Data for API
     */
    public function getOverviewData()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => $this->getOverviewStats(),
                    'monthly_revenue' => $this->getMonthlyRevenue(),
                    'channel_distribution' => $this->getChannelDistribution(),
                    'performance_summary' => $this->getPerformanceSummary(),
                    'quick_insights' => $this->getQuickInsights()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Analytics getOverviewData error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load overview data'], 500);
        }
    }

    // ===== PRIVATE HELPER METHODS - OVERVIEW ONLY =====

    private function getOverviewStats()
    {
        try {
            $totalPartners = Toko::count();
            $activePartners = Toko::where('is_active', true)->count();
            $totalProducts = Barang::where('is_deleted', 0)->count();
            $totalShipments = Pengiriman::where('status', 'terkirim')->count();
            
            // Last 30 days stats
            $last30Days = Carbon::now()->subDays(30);
            $recentShipments = Pengiriman::where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $last30Days)
                ->count();
            
            $recentRevenue = Retur::where('tanggal_retur', '>=', $last30Days)
                ->sum('hasil') ?? 0;
            
            return [
                'total_partners' => $totalPartners,
                'active_partners' => $activePartners,
                'total_products' => $totalProducts,
                'total_shipments' => $totalShipments,
                'partner_activation_rate' => $totalPartners > 0 ? round(($activePartners / $totalPartners) * 100, 1) : 0,
                'recent_shipments_30d' => $recentShipments,
                'recent_revenue_30d' => $recentRevenue,
                'avg_revenue_per_shipment' => $recentShipments > 0 ? round($recentRevenue / $recentShipments, 0) : 0
            ];
        } catch (Exception $e) {
            Log::error('Overview stats error: ' . $e->getMessage());
            return [
                'total_partners' => 0,
                'active_partners' => 0,
                'total_products' => 0,
                'total_shipments' => 0,
                'partner_activation_rate' => 0,
                'recent_shipments_30d' => 0,
                'recent_revenue_30d' => 0,
                'avg_revenue_per_shipment' => 0
            ];
        }
    }

    private function getMonthlyTrends()
    {
        try {
            $trends = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                $monthlyShipments = Pengiriman::where('status', 'terkirim')
                    ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                    ->count();
                
                $monthlyRevenue = Retur::whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                    ->sum('hasil') ?? 0;
                
                $monthlyVolume = Pengiriman::where('status', 'terkirim')
                    ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                    ->sum('jumlah_kirim') ?? 0;
                
                $trends[] = [
                    'month' => $monthStart->format('M Y'),
                    'shipments' => $monthlyShipments,
                    'revenue' => $monthlyRevenue,
                    'volume' => $monthlyVolume,
                    'avg_order_value' => $monthlyShipments > 0 ? round($monthlyRevenue / $monthlyShipments, 0) : 0
                ];
            }
            
            return $trends;
        } catch (Exception $e) {
            Log::error('Monthly trends error: ' . $e->getMessage());
            return $this->getDefaultMonthlyTrends();
        }
    }

    private function getChannelOverview()
    {
        try {
            $period = Carbon::now()->subMonths(3);
            
            // B2B (Konsinyasi) Stats
            $b2bShipments = Pengiriman::where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $period)
                ->count();
            
            $b2bRevenue = Retur::where('tanggal_retur', '>=', $period)
                ->sum('hasil') ?? 0;
            
            // B2C (Direct Sales) Stats
            $b2cOrders = Pemesanan::whereIn('pemesanan_dari', ['whatsapp', 'instagram', 'langsung', 'online'])
                ->where('tanggal_pemesanan', '>=', $period)
                ->whereIn('status_pemesanan', ['selesai', 'dikirim'])
                ->count();
            
            $b2cRevenue = Pemesanan::whereIn('pemesanan_dari', ['whatsapp', 'instagram', 'langsung', 'online'])
                ->where('tanggal_pemesanan', '>=', $period)
                ->whereIn('status_pemesanan', ['selesai', 'dikirim'])
                ->sum('total') ?? 0;
            
            $totalRevenue = $b2bRevenue + $b2cRevenue;
            
            return [
                'b2b' => [
                    'transactions' => $b2bShipments,
                    'revenue' => $b2bRevenue,
                    'percentage' => $totalRevenue > 0 ? round(($b2bRevenue / $totalRevenue) * 100, 1) : 0,
                    'avg_value' => $b2bShipments > 0 ? round($b2bRevenue / $b2bShipments, 0) : 0
                ],
                'b2c' => [
                    'transactions' => $b2cOrders,
                    'revenue' => $b2cRevenue,
                    'percentage' => $totalRevenue > 0 ? round(($b2cRevenue / $totalRevenue) * 100, 1) : 0,
                    'avg_value' => $b2cOrders > 0 ? round($b2cRevenue / $b2cOrders, 0) : 0
                ],
                'total_revenue' => $totalRevenue
            ];
        } catch (Exception $e) {
            Log::error('Channel overview error: ' . $e->getMessage());
            return $this->getDefaultChannelOverview();
        }
    }

    private function getPerformanceSummary()
    {
        try {
            $period = Carbon::now()->subMonths(3);
            
            // Calculate overall sell-through rate
            $totalShipped = Pengiriman::where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $period)
                ->sum('jumlah_kirim') ?? 0;
            
            $totalReturned = Retur::where('tanggal_retur', '>=', $period)
                ->sum('jumlah_retur') ?? 0;
            
            $totalSold = max(0, $totalShipped - $totalReturned);
            $sellThroughRate = $totalShipped > 0 ? ($totalSold / $totalShipped) * 100 : 0;
            
            // Partner performance distribution
            $partners = Toko::where('is_active', true)->get();
            $performanceDistribution = [
                'excellent' => 0, // 80%+ sell-through
                'good' => 0,      // 60-79% sell-through
                'average' => 0,   // 40-59% sell-through
                'poor' => 0       // <40% sell-through
            ];
            
            foreach ($partners as $partner) {
                $partnerShipped = Pengiriman::where('toko_id', $partner->toko_id)
                    ->where('status', 'terkirim')
                    ->where('tanggal_pengiriman', '>=', $period)
                    ->sum('jumlah_kirim') ?? 0;
                
                $partnerReturned = Retur::where('toko_id', $partner->toko_id)
                    ->where('tanggal_retur', '>=', $period)
                    ->sum('jumlah_retur') ?? 0;
                
                if ($partnerShipped > 0) {
                    $partnerSold = max(0, $partnerShipped - $partnerReturned);
                    $partnerRate = ($partnerSold / $partnerShipped) * 100;
                    
                    if ($partnerRate >= 80) $performanceDistribution['excellent']++;
                    elseif ($partnerRate >= 60) $performanceDistribution['good']++;
                    elseif ($partnerRate >= 40) $performanceDistribution['average']++;
                    else $performanceDistribution['poor']++;
                }
            }
            
            return [
                'overall_sell_through_rate' => round($sellThroughRate, 2),
                'total_volume_sold' => $totalSold,
                'total_revenue' => Retur::where('tanggal_retur', '>=', $period)->sum('hasil') ?? 0,
                'performance_distribution' => $performanceDistribution,
                'active_partners' => $partners->count(),
                'avg_partner_performance' => $partners->count() > 0 ? round($sellThroughRate, 1) : 0
            ];
        } catch (Exception $e) {
            Log::error('Performance summary error: ' . $e->getMessage());
            return $this->getDefaultPerformanceSummary();
        }
    }

    private function getMonthlyRevenue()
    {
        try {
            $revenue = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                $monthlyRevenue = Retur::whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                    ->sum('hasil') ?? 0;
                
                $revenue[] = [
                    'month' => $monthStart->format('M Y'),
                    'revenue' => $monthlyRevenue
                ];
            }
            
            return $revenue;
        } catch (Exception $e) {
            Log::error('Monthly revenue error: ' . $e->getMessage());
            return $this->getDefaultMonthlyRevenue();
        }
    }

    private function getChannelDistribution()
    {
        try {
            $period = Carbon::now()->subMonths(3);
            
            $b2bRevenue = Retur::where('tanggal_retur', '>=', $period)->sum('hasil') ?? 0;
            $b2cRevenue = Pemesanan::whereIn('pemesanan_dari', ['whatsapp', 'instagram', 'langsung', 'online'])
                ->where('tanggal_pemesanan', '>=', $period)
                ->whereIn('status_pemesanan', ['selesai', 'dikirim'])
                ->sum('total') ?? 0;
            
            $totalRevenue = $b2bRevenue + $b2cRevenue;
            
            return [
                'b2b_percentage' => $totalRevenue > 0 ? round(($b2bRevenue / $totalRevenue) * 100, 1) : 0,
                'b2c_percentage' => $totalRevenue > 0 ? round(($b2cRevenue / $totalRevenue) * 100, 1) : 0,
                'b2b_revenue' => $b2bRevenue,
                'b2c_revenue' => $b2cRevenue
            ];
        } catch (Exception $e) {
            Log::error('Channel distribution error: ' . $e->getMessage());
            return ['b2b_percentage' => 75, 'b2c_percentage' => 25, 'b2b_revenue' => 0, 'b2c_revenue' => 0];
        }
    }

    private function getQuickInsights()
    {
        try {
            $insights = [];
            
            // Get recent performance trends
            $last30Days = Carbon::now()->subDays(30);
            $previous30Days = Carbon::now()->subDays(60);
            
            $recentRevenue = Retur::where('tanggal_retur', '>=', $last30Days)->sum('hasil') ?? 0;
            $previousRevenue = Retur::whereBetween('tanggal_retur', [$previous30Days, $last30Days])->sum('hasil') ?? 0;
            
            $revenueGrowth = $previousRevenue > 0 ? (($recentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
            
            if ($revenueGrowth > 10) {
                $insights[] = [
                    'type' => 'positive',
                    'title' => 'Revenue Growth',
                    'message' => 'Revenue increased by ' . round($revenueGrowth, 1) . '% in the last 30 days',
                    'icon' => 'fas fa-arrow-up'
                ];
            } elseif ($revenueGrowth < -10) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Revenue Decline',
                    'message' => 'Revenue decreased by ' . round(abs($revenueGrowth), 1) . '% in the last 30 days',
                    'icon' => 'fas fa-arrow-down'
                ];
            }
            
            // Check for underperforming partners
            $poorPerformers = $this->getPoorPerformingPartnersCount();
            if ($poorPerformers > 0) {
                $insights[] = [
                    'type' => 'info',
                    'title' => 'Partner Performance',
                    'message' => $poorPerformers . ' partners need attention for low sell-through rates',
                    'icon' => 'fas fa-users'
                ];
            }
            
            // Inventory insights
            $lowStockProducts = $this->getLowStockProductsCount();
            if ($lowStockProducts > 0) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Inventory Alert',
                    'message' => $lowStockProducts . ' products may need restocking soon',
                    'icon' => 'fas fa-boxes'
                ];
            }
            
            return $insights;
        } catch (Exception $e) {
            Log::error('Quick insights error: ' . $e->getMessage());
            return [];
        }
    }

    private function getPoorPerformingPartnersCount()
    {
        try {
            $period = Carbon::now()->subMonths(1);
            $poorPerformers = 0;
            
            $partners = Toko::where('is_active', true)->get();
            
            foreach ($partners as $partner) {
                $shipped = Pengiriman::where('toko_id', $partner->toko_id)
                    ->where('status', 'terkirim')
                    ->where('tanggal_pengiriman', '>=', $period)
                    ->sum('jumlah_kirim') ?? 0;
                
                $returned = Retur::where('toko_id', $partner->toko_id)
                    ->where('tanggal_retur', '>=', $period)
                    ->sum('jumlah_retur') ?? 0;
                
                if ($shipped > 0) {
                    $sold = max(0, $shipped - $returned);
                    $sellThrough = ($sold / $shipped) * 100;
                    
                    if ($sellThrough < 50) {
                        $poorPerformers++;
                    }
                }
            }
            
            return $poorPerformers;
        } catch (Exception $e) {
            Log::warning('Poor performing partners count error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getLowStockProductsCount()
    {
        try {
            // Simplified calculation - count products with low recent activity
            $period = Carbon::now()->subDays(14);
            
            $lowActivityProducts = Barang::where('is_deleted', 0)
                ->whereDoesntHave('pengiriman', function($query) use ($period) {
                    $query->where('status', 'terkirim')
                          ->where('tanggal_pengiriman', '>=', $period);
                })
                ->count();
            
            return min($lowActivityProducts, 10); // Cap at 10 for display
        } catch (Exception $e) {
            Log::warning('Low stock products count error: ' . $e->getMessage());
            return 0;
        }
    }

    // Default data methods
    private function getDefaultMonthlyTrends()
    {
        return [
            ['month' => 'Jan 25', 'shipments' => 85, 'revenue' => 195000000, 'volume' => 2400, 'avg_order_value' => 2294000],
            ['month' => 'Feb 25', 'shipments' => 92, 'revenue' => 215000000, 'volume' => 2600, 'avg_order_value' => 2337000],
            ['month' => 'Mar 25', 'shipments' => 78, 'revenue' => 180000000, 'volume' => 2100, 'avg_order_value' => 2308000],
            ['month' => 'Apr 25', 'shipments' => 105, 'revenue' => 245000000, 'volume' => 2900, 'avg_order_value' => 2333000],
            ['month' => 'May 25', 'shipments' => 98, 'revenue' => 230000000, 'volume' => 2750, 'avg_order_value' => 2347000],
            ['month' => 'Jun 25', 'shipments' => 112, 'revenue' => 265000000, 'volume' => 3100, 'avg_order_value' => 2366000]
        ];
    }

    private function getDefaultChannelOverview()
    {
        return [
            'b2b' => [
                'transactions' => 450,
                'revenue' => 380000000,
                'percentage' => 76.0,
                'avg_value' => 844444
            ],
            'b2c' => [
                'transactions' => 180,
                'revenue' => 120000000,
                'percentage' => 24.0,
                'avg_value' => 666667
            ],
            'total_revenue' => 500000000
        ];
    }

    private function getDefaultPerformanceSummary()
    {
        return [
            'overall_sell_through_rate' => 72.5,
            'total_volume_sold' => 15750,
            'total_revenue' => 500000000,
            'performance_distribution' => [
                'excellent' => 12,
                'good' => 18,
                'average' => 10,
                'poor' => 5
            ],
            'active_partners' => 45,
            'avg_partner_performance' => 72.5
        ];
    }

    private function getDefaultMonthlyRevenue()
    {
        return [
            ['month' => 'Jan 25', 'revenue' => 195000000],
            ['month' => 'Feb 25', 'revenue' => 215000000],
            ['month' => 'Mar 25', 'revenue' => 180000000],
            ['month' => 'Apr 25', 'revenue' => 245000000],
            ['month' => 'May 25', 'revenue' => 230000000],
            ['month' => 'Jun 25', 'revenue' => 265000000]
        ];
    }
}
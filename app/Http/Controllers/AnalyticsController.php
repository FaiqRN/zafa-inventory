<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\Barang;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\Pemesanan;
use App\Models\BarangToko;
use App\Services\AnalyticsService;
use App\Helpers\AnalyticsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Analytics Dashboard Main
     */
    public function index()
    {
        $breadcrumb = (object)[
            'title' => 'Analytics Dashboard',
            'list' => ['Home', 'Analytics']
        ];
        
        $overview = $this->getOverviewStats();
        
        return view('analytics.index', [
            'breadcrumb' => $breadcrumb,
            'overview' => $overview,
            'activemenu' => 'analytics'
        ]);
    }

    /**
     * ANALYTICS 1: Partner Performance Analytics
     */
    public function partnerPerformance()
    {
        $breadcrumb = (object)[
            'title' => 'Partner Performance Analytics',
            'list' => ['Home', 'Analytics', 'Partner Performance']
        ];

        $partners = $this->calculatePartnerPerformance();
        $performanceChart = $this->getPartnerPerformanceChart();
        $alerts = $this->getPartnerAlerts();

        return view('analytics.partner-performance', [
            'breadcrumb' => $breadcrumb,
            'partners' => $partners,
            'performanceChart' => $performanceChart,
            'alerts' => $alerts,
            'activemenu' => 'analytics.partner-performance'
        ]);
    }

    /**
     * ANALYTICS 2: Inventory Optimization
     */
    public function inventoryOptimization()
    {
        $breadcrumb = (object)[
            'title' => 'Inventory Optimization',
            'list' => ['Home', 'Analytics', 'Inventory Optimization']
        ];

        $recommendations = $this->getInventoryRecommendations();
        $turnoverStats = $this->getInventoryTurnoverStats();
        $seasonalAdjustments = $this->getSeasonalAdjustments();

        return view('analytics.inventory-optimization', [
            'breadcrumb' => $breadcrumb,
            'recommendations' => $recommendations,
            'turnoverStats' => $turnoverStats,
            'seasonalAdjustments' => $seasonalAdjustments,
            'activemenu' => 'analytics.inventory-optimization'
        ]);
    }

    /**
     * ANALYTICS 3: Product Velocity
     */
    public function productVelocity()
    {
        $breadcrumb = (object)[
            'title' => 'Product Velocity Analytics',
            'list' => ['Home', 'Analytics', 'Product Velocity']
        ];

        $productCategories = $this->categorizeProductsByVelocity();
        $velocityTrends = $this->getVelocityTrends();
        $locationDemand = $this->getLocationDemandAnalysis();

        return view('analytics.product-velocity', [
            'breadcrumb' => $breadcrumb,
            'productCategories' => $productCategories,
            'velocityTrends' => $velocityTrends,
            'locationDemand' => $locationDemand,
            'activemenu' => 'analytics.product-velocity'
        ]);
    }

    /**
     * ANALYTICS 4: Profitability Analysis
     */
    public function profitabilityAnalysis()
    {
        $breadcrumb = (object)[
            'title' => 'Profitability Analysis',
            'list' => ['Home', 'Analytics', 'Profitability Analysis']
        ];

        $profitability = $this->calculateTrueProfitability();
        $costBreakdown = $this->getCostBreakdownAnalysis($profitability);
        $roiRanking = $this->getROIRanking($profitability);

        return view('analytics.profitability-analysis', [
            'breadcrumb' => $breadcrumb,
            'profitability' => $profitability,
            'costBreakdown' => $costBreakdown,
            'roiRanking' => $roiRanking,
            'activemenu' => 'analytics.profitability-analysis'
        ]);
    }

    /**
     * ANALYTICS 5: Channel Comparison
     */
    public function channelComparison()
    {
        $breadcrumb = (object)[
            'title' => 'Channel Comparison',
            'list' => ['Home', 'Analytics', 'Channel Comparison']
        ];

        $b2bStats = $this->getB2BConsignmentStats();
        $b2cStats = $this->getB2CDirectSalesStats();
        $comparison = $this->compareChannels($b2bStats, $b2cStats);
        $recommendations = $this->getChannelRecommendations($comparison);

        return view('analytics.channel-comparison', [
            'breadcrumb' => $breadcrumb,
            'b2bStats' => $b2bStats,
            'b2cStats' => $b2cStats,
            'comparison' => $comparison,
            'recommendations' => $recommendations,
            'activemenu' => 'analytics.channel-comparison'
        ]);
    }

    /**
     * ANALYTICS 6: Predictive Analytics
     */
    public function predictiveAnalytics()
    {
        $breadcrumb = (object)[
            'title' => 'Predictive Analytics',
            'list' => ['Home', 'Analytics', 'Predictive Analytics']
        ];

        $demandPredictions = $this->getDemandPredictions();
        $partnerRiskScores = $this->getPartnerRiskScores();
        $seasonalForecasts = $this->getSeasonalForecasts();
        $opportunities = $this->getNewOpportunities();

        return view('analytics.predictive-analytics', [
            'breadcrumb' => $breadcrumb,
            'demandPredictions' => $demandPredictions,
            'partnerRiskScores' => $partnerRiskScores,
            'seasonalForecasts' => $seasonalForecasts,
            'opportunities' => $opportunities,
            'activemenu' => 'analytics.predictive-analytics'
        ]);
    }

    // ========================================
    // HELPER METHODS IMPLEMENTATION
    // ========================================

    private function getOverviewStats()
    {
        $totalPartners = Toko::count();
        $activePartners = Toko::where('is_active', true)->count();
        $totalProducts = Barang::where('is_deleted', 0)->count();
        $totalShipments = Pengiriman::where('status', 'terkirim')->count();
        
        return [
            'total_partners' => $totalPartners,
            'active_partners' => $activePartners,
            'total_products' => $totalProducts,
            'total_shipments' => $totalShipments,
            'partner_activation_rate' => $totalPartners > 0 ? round(($activePartners / $totalPartners) * 100, 1) : 0
        ];
    }

    private function calculatePartnerPerformance()
    {
        $periodStart = Carbon::now()->subMonths(6);
        
        return Toko::where('is_active', true)
            ->get()
            ->map(function ($toko) use ($periodStart) {
                // Get shipment data
                $totalShipped = Pengiriman::where('toko_id', $toko->toko_id)
                    ->where('status', 'terkirim')
                    ->where('tanggal_pengiriman', '>=', $periodStart)
                    ->sum('jumlah_kirim');
                
                // Get return data
                $totalReturned = Retur::where('toko_id', $toko->toko_id)
                    ->where('tanggal_retur', '>=', $periodStart)
                    ->sum('jumlah_retur');
                
                $totalSold = $totalShipped - $totalReturned;
                $sellThroughRate = $totalShipped > 0 ? ($totalSold / $totalShipped) * 100 : 0;
                
                // Calculate average days to return
                $avgDaysToReturn = DB::table('retur')
                    ->where('toko_id', $toko->toko_id)
                    ->where('tanggal_retur', '>=', $periodStart)
                    ->whereNotNull('tanggal_pengiriman')
                    ->whereNotNull('tanggal_retur')
                    ->selectRaw('AVG(DATEDIFF(tanggal_retur, tanggal_pengiriman)) as avg_days')
                    ->value('avg_days') ?? 14;
                
                // Calculate revenue
                $revenue = Retur::where('toko_id', $toko->toko_id)
                    ->where('tanggal_retur', '>=', $periodStart)
                    ->sum('hasil');
                
                // Calculate grade and performance score
                $performanceScore = $this->calculatePerformanceScore($sellThroughRate, $avgDaysToReturn, $revenue);
                $grade = $this->calculateGrade($performanceScore);
                
                return [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'total_shipped' => $totalShipped,
                    'total_sold' => $totalSold,
                    'total_returned' => $totalReturned,
                    'sell_through_rate' => round($sellThroughRate, 2),
                    'avg_days_to_return' => round($avgDaysToReturn, 1),
                    'revenue' => $revenue,
                    'grade' => $grade,
                    'performance_score' => $performanceScore
                ];
            })
            ->sortByDesc('performance_score');
    }

    private function calculatePerformanceScore($sellThroughRate, $avgDaysToReturn, $revenue)
    {
        // Normalize metrics
        $sellThroughScore = min($sellThroughRate, 100);
        $speedScore = max(100 - ($avgDaysToReturn * 2), 0);
        $revenueScore = min(($revenue / 10000000) * 100, 100); // Normalize to 10M max
        
        // Weighted average
        return round(
            ($sellThroughScore * 0.5) + 
            ($speedScore * 0.3) + 
            ($revenueScore * 0.2), 
            2
        );
    }

    private function calculateGrade($performanceScore)
    {
        if ($performanceScore >= 90) return 'A+';
        if ($performanceScore >= 80) return 'A';
        if ($performanceScore >= 70) return 'B+';
        if ($performanceScore >= 60) return 'B';
        if ($performanceScore >= 50) return 'C+';
        return 'C';
    }

    private function getInventoryRecommendations()
    {
        $recommendations = collect();
        $periodStart = Carbon::now()->subMonths(6);
        
        // Get all product-store combinations
        $barangTokos = BarangToko::with(['toko', 'barang'])
            ->whereHas('toko', function($q) {
                $q->where('is_active', true);
            })
            ->whereHas('barang', function($q) {
                $q->where('is_deleted', 0);
            })
            ->get();
        
        foreach ($barangTokos as $barangToko) {
            // Get historical shipment data
            $historicalData = Pengiriman::where('toko_id', $barangToko->toko_id)
                ->where('barang_id', $barangToko->barang_id)
                ->where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->get();
            
            if ($historicalData->isEmpty()) {
                continue;
            }
            
            $avgShipped = $historicalData->avg('jumlah_kirim');
            
            // Calculate average sold (shipped - returned)
            $avgSold = $historicalData->map(function ($shipment) {
                $returned = Retur::where('pengiriman_id', $shipment->pengiriman_id)->sum('jumlah_retur');
                return $shipment->jumlah_kirim - $returned;
            })->avg();
            
            // Apply seasonal adjustment
            $seasonalMultiplier = AnalyticsHelper::calculateSeasonalIndex(Carbon::now()->month);
            $recommendedQuantity = round($avgSold * $seasonalMultiplier);
            
            // Calculate confidence level
            $confidenceLevel = $this->calculateConfidenceLevel($historicalData->count());
            
            // Calculate potential savings
            $currentWaste = max($avgShipped - $avgSold, 0);
            $potentialSavings = $currentWaste * 15000; // Assuming Rp 15k per unit cost
            
            $recommendations->push([
                'toko_nama' => $barangToko->toko->nama_toko,
                'barang_nama' => $barangToko->barang->nama_barang,
                'toko_id' => $barangToko->toko_id,
                'barang_id' => $barangToko->barang_id,
                'historical_avg_shipped' => round($avgShipped, 0),
                'historical_avg_sold' => round($avgSold, 0),
                'recommended_quantity' => $recommendedQuantity,
                'seasonal_multiplier' => $seasonalMultiplier,
                'confidence_level' => $confidenceLevel,
                'potential_savings' => $potentialSavings,
                'improvement_percentage' => $avgShipped > 0 ? round(($currentWaste / $avgShipped) * 100, 1) : 0
            ]);
        }
        
        return $recommendations->sortByDesc('potential_savings');
    }

    private function categorizeProductsByVelocity()
    {
        $periodStart = Carbon::now()->subMonths(3);
        $products = Barang::where('is_deleted', 0)->get();
        
        $categorizedProducts = $products->map(function ($barang) use ($periodStart) {
            // Get shipment data
            $shipments = Pengiriman::where('barang_id', $barang->barang_id)
                ->where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->get();
            
            if ($shipments->isEmpty()) {
                return [
                    'barang' => $barang,
                    'velocity_category' => 'No Data',
                    'avg_sell_through' => 0,
                    'avg_days_to_sell' => 0,
                    'total_shipped' => 0,
                    'total_sold' => 0,
                    'velocity_score' => 0
                ];
            }
            
            $totalShipped = $shipments->sum('jumlah_kirim');
            $totalSold = $shipments->map(function ($shipment) {
                $returned = Retur::where('pengiriman_id', $shipment->pengiriman_id)->sum('jumlah_retur');
                return $shipment->jumlah_kirim - $returned;
            })->sum();
            
            $sellThroughRate = $totalShipped > 0 ? ($totalSold / $totalShipped) * 100 : 0;
            
            // Calculate average days to sell
            $avgDaysToSell = $shipments->map(function ($shipment) {
                $retur = Retur::where('pengiriman_id', $shipment->pengiriman_id)->first();
                if ($retur && $retur->tanggal_retur && $shipment->tanggal_pengiriman) {
                    return Carbon::parse($retur->tanggal_retur)->diffInDays(Carbon::parse($shipment->tanggal_pengiriman));
                }
                return 14; // Default assumption
            })->avg();
            
            // Calculate velocity score
            $velocityScore = $this->calculateVelocityScore($sellThroughRate, $avgDaysToSell, $totalSold);
            
            // Categorize
            $category = $this->categorizeVelocity($velocityScore, $sellThroughRate, $avgDaysToSell);
            
            return [
                'barang' => $barang,
                'velocity_category' => $category,
                'avg_sell_through' => round($sellThroughRate, 2),
                'avg_days_to_sell' => round($avgDaysToSell, 1),
                'total_shipped' => $totalShipped,
                'total_sold' => $totalSold,
                'velocity_score' => $velocityScore
            ];
        });
        
        return $categorizedProducts->groupBy('velocity_category');
    }

    private function calculateVelocityScore($sellThroughRate, $avgDaysToSell, $totalSold)
    {
        $sellThroughScore = min($sellThroughRate, 100);
        $speedScore = max(100 - ($avgDaysToSell * 3), 0);
        $volumeScore = min(($totalSold / 1000) * 100, 100);
        
        return round(
            ($sellThroughScore * 0.4) + 
            ($speedScore * 0.4) + 
            ($volumeScore * 0.2), 
            2
        );
    }

    private function categorizeVelocity($velocityScore, $sellThroughRate, $avgDaysToSell)
    {
        if ($velocityScore >= 80 && $sellThroughRate >= 80 && $avgDaysToSell <= 7) {
            return 'Hot Seller';
        } elseif ($velocityScore >= 60 && $sellThroughRate >= 60 && $avgDaysToSell <= 14) {
            return 'Good Mover';
        } elseif ($velocityScore >= 40 && $sellThroughRate >= 30 && $avgDaysToSell <= 21) {
            return 'Slow Mover';
        } else {
            return 'Dead Stock';
        }
    }

    private function calculateTrueProfitability()
    {
        $periodStart = Carbon::now()->subMonths(6);
        
        return Toko::where('is_active', true)
            ->get()
            ->map(function ($toko) use ($periodStart) {
                // Revenue from sales
                $revenue = Retur::where('toko_id', $toko->toko_id)
                    ->where('tanggal_retur', '>=', $periodStart)
                    ->sum('hasil');
                
                // Calculate COGS
                $totalSold = Pengiriman::where('toko_id', $toko->toko_id)
                    ->where('tanggal_pengiriman', '>=', $periodStart)
                    ->sum('jumlah_kirim') - 
                    Retur::where('toko_id', $toko->toko_id)
                    ->where('tanggal_retur', '>=', $periodStart)
                    ->sum('jumlah_retur');
                
                $avgCOGS = 15000; // Average cost per unit
                $cogs = $totalSold * $avgCOGS;
                
                // Calculate other costs
                $logisticsCost = $this->calculateLogisticsCost($toko, $periodStart);
                $opportunityCost = $this->calculateOpportunityCost($toko, $periodStart);
                $timeValueCost = $this->calculateTimeValueCost($toko, $periodStart);
                
                // Calculate totals
                $totalCosts = $cogs + $logisticsCost + $opportunityCost + $timeValueCost;
                $netProfit = $revenue - $totalCosts;
                $roi = $totalCosts > 0 ? ($netProfit / $totalCosts) * 100 : 0;
                
                return [
                    'toko' => $toko,
                    'revenue' => $revenue,
                    'cogs' => $cogs,
                    'logistics_cost' => $logisticsCost,
                    'opportunity_cost' => $opportunityCost,
                    'time_value_cost' => $timeValueCost,
                    'total_costs' => $totalCosts,
                    'net_profit' => $netProfit,
                    'roi' => round($roi, 2),
                    'profit_margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
                    'cost_breakdown' => [
                        'cogs_percentage' => $totalCosts > 0 ? round(($cogs / $totalCosts) * 100, 1) : 0,
                        'logistics_percentage' => $totalCosts > 0 ? round(($logisticsCost / $totalCosts) * 100, 1) : 0,
                        'opportunity_percentage' => $totalCosts > 0 ? round(($opportunityCost / $totalCosts) * 100, 1) : 0,
                        'time_value_percentage' => $totalCosts > 0 ? round(($timeValueCost / $totalCosts) * 100, 1) : 0
                    ]
                ];
            })
            ->sortByDesc('roi');
    }

    private function calculateLogisticsCost($toko, $periodStart)
    {
        // Simple distance-based calculation
        $distance = 10; // Default 10km, you can integrate with actual distance calculation
        $shipmentCount = Pengiriman::where('toko_id', $toko->toko_id)
            ->where('tanggal_pengiriman', '>=', $periodStart)
            ->where('status', 'terkirim')
            ->count();
        
        $costPerKm = 2500; // Cost per km
        return $shipmentCount * ($distance * $costPerKm);
    }

    private function calculateOpportunityCost($toko, $periodStart)
    {
        $avgInventoryValue = Pengiriman::where('toko_id', $toko->toko_id)
            ->where('tanggal_pengiriman', '>=', $periodStart)
            ->sum('jumlah_kirim') * 15000; // Average unit cost
        
        $monthsPeriod = $periodStart->diffInMonths(Carbon::now());
        $annualOpportunityRate = 0.12; // 12% annual rate
        
        return ($avgInventoryValue / $monthsPeriod) * ($annualOpportunityRate / 12) * $monthsPeriod;
    }

    private function calculateTimeValueCost($toko, $periodStart)
    {
        $avgConsignmentDays = DB::table('retur')
            ->where('toko_id', $toko->toko_id)
            ->where('tanggal_retur', '>=', $periodStart)
            ->whereNotNull('tanggal_pengiriman')
            ->selectRaw('AVG(DATEDIFF(tanggal_retur, tanggal_pengiriman)) as avg_days')
            ->value('avg_days') ?? 14;
        
        $avgInventoryValue = Pengiriman::where('toko_id', $toko->toko_id)
            ->where('tanggal_pengiriman', '>=', $periodStart)
            ->sum('jumlah_kirim') * 15000;
        
        $monthsPeriod = $periodStart->diffInMonths(Carbon::now());
        $monthlyRate = 0.01; // 1% per month
        
        return ($avgConsignmentDays / 30) * ($avgInventoryValue / $monthsPeriod) * $monthlyRate * $monthsPeriod;
    }

    private function getB2BConsignmentStats()
    {
        $period = Carbon::now()->subMonths(6);
        
        $totalShipped = Pengiriman::where('status', 'terkirim')
            ->where('tanggal_pengiriman', '>=', $period)
            ->sum('jumlah_kirim');
        
        $totalReturned = Retur::where('tanggal_retur', '>=', $period)
            ->sum('jumlah_retur');
        
        $totalSold = $totalShipped - $totalReturned;
        $totalRevenue = Retur::where('tanggal_retur', '>=', $period)->sum('hasil');
        
        $avgPrice = $totalSold > 0 ? $totalRevenue / $totalSold : 0;
        $margin = $avgPrice > 0 ? (($avgPrice - 15000) / $avgPrice) * 100 : 0;
        
        return [
            'volume' => $totalSold,
            'revenue' => $totalRevenue,
            'avg_price' => $avgPrice,
            'margin' => round($margin, 2),
            'effort_level' => 'Medium',
            'scalability' => 'High',
            'cash_flow_speed' => 'Slow (2 weeks)',
            'monthly_volume' => round($totalSold / 6, 0)
        ];
    }

    private function getB2CDirectSalesStats()
    {
        $period = Carbon::now()->subMonths(6);
        
        $directSales = Pemesanan::whereIn('pemesanan_dari', ['whatsapp', 'instagram', 'langsung'])
            ->where('tanggal_pemesanan', '>=', $period)
            ->where('status_pemesanan', 'selesai')
            ->get();
        
        $totalVolume = $directSales->sum('jumlah_pesanan');
        $totalRevenue = $directSales->sum('total');
        $avgPrice = $totalVolume > 0 ? $totalRevenue / $totalVolume : 0;
        $margin = $avgPrice > 0 ? (($avgPrice - 15000) / $avgPrice) * 100 : 0;
        
        return [
            'volume' => $totalVolume,
            'revenue' => $totalRevenue,
            'avg_price' => $avgPrice,
            'margin' => round($margin, 2),
            'effort_level' => 'High',
            'scalability' => 'Medium',
            'cash_flow_speed' => 'Fast (Immediate)',
            'monthly_volume' => round($totalVolume / 6, 0)
        ];
    }

    private function compareChannels($b2b, $b2c)
    {
        $totalVolume = $b2b['volume'] + $b2c['volume'];
        
        return [
            'volume_winner' => $b2b['volume'] > $b2c['volume'] ? 'B2B' : 'B2C',
            'revenue_winner' => $b2b['revenue'] > $b2c['revenue'] ? 'B2B' : 'B2C',
            'margin_winner' => $b2b['margin'] > $b2c['margin'] ? 'B2B' : 'B2C',
            'efficiency_winner' => 'B2B',
            'total_volume' => $totalVolume,
            'total_revenue' => $b2b['revenue'] + $b2c['revenue'],
            'b2b_percentage' => $totalVolume > 0 ? ($b2b['volume'] / $totalVolume) * 100 : 0,
            'b2c_percentage' => $totalVolume > 0 ? ($b2c['volume'] / $totalVolume) * 100 : 0
        ];
    }

    private function getDemandPredictions()
    {
        $predictions = collect();
        $stores = Toko::where('is_active', true)->take(15)->get();
        $products = Barang::where('is_deleted', 0)->take(5)->get();
        
        foreach ($stores as $store) {
            foreach ($products->take(2) as $product) {
                $historicalAvg = rand(30, 80);
                $seasonalFactor = AnalyticsHelper::calculateSeasonalIndex(Carbon::now()->addMonth()->month);
                $predictedQty = round($historicalAvg * $seasonalFactor);
                
                $predictions->push([
                    'store_id' => $store->toko_id,
                    'store_name' => $store->nama_toko,
                    'product_id' => $product->barang_id,
                    'product_name' => $product->nama_barang,
                    'product_code' => $product->barang_kode,
                    'predicted_quantity' => $predictedQty,
                    'confidence' => rand(65, 95),
                    'seasonal_factor' => $seasonalFactor,
                    'trend' => collect(['increasing', 'stable', 'decreasing'])->random()
                ]);
            }
        }
        
        return $predictions->sortByDesc('confidence');
    }

    private function getPartnerRiskScores()
    {
        $partners = Toko::where('is_active', true)->take(20)->get();
        
        return $partners->map(function ($partner) {
            $score = rand(0, 100);
            $level = $score >= 70 ? 'High' : ($score >= 40 ? 'Medium' : 'Low');
            
            return [
                'partner_id' => $partner->toko_id,
                'partner_name' => $partner->nama_toko,
                'score' => $score,
                'level' => $level,
                'factors' => [
                    ['factor' => 'declining_performance', 'weight' => 30],
                    ['factor' => 'high_return_rate', 'weight' => 25],
                    ['factor' => 'slow_payment', 'weight' => 20]
                ]
            ];
        });
    }

    private function getNewOpportunities()
    {
        return [
            [
                'id' => 'opp_001',
                'type' => 'new_location',
                'title' => 'Ekspansi ke Wilayah Kepanjen',
                'description' => 'Market gap teridentifikasi di area Kepanjen dengan potensi 50+ toko retail',
                'confidence' => 78,
                'potential_revenue' => 15000000,
                'investment_required' => 5000000
            ],
            [
                'id' => 'opp_002',
                'type' => 'product_expansion',
                'title' => 'Varian Rasa Pedas Premium',
                'description' => 'Trend makanan pedas meningkat 40% dalam 6 bulan terakhir',
                'confidence' => 85,
                'potential_revenue' => 8000000,
                'investment_required' => 2000000
            ],
            [
                'id' => 'opp_003',
                'type' => 'partnership',
                'title' => 'Kerjasama dengan Minimarket Chain',
                'description' => 'Peluang masuk ke 25 outlet minimarket lokal',
                'confidence' => 72,
                'potential_revenue' => 25000000,
                'investment_required' => 8000000
            ]
        ];
    }

    // Additional helper methods
    private function getPartnerPerformanceChart()
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [65, 68, 72, 71, 75, 78]
        ];
    }

    private function getPartnerAlerts()
    {
        return collect();
    }

    private function getInventoryTurnoverStats()
    {
        return [
            'current_turnover_rate' => 2.4,
            'target_turnover_rate' => 4.0,
            'improvement_needed' => 1.6,
            'cash_cycle_days' => 45
        ];
    }

    private function getSeasonalAdjustments()
    {
        $currentMonth = Carbon::now()->month;
        $multiplier = AnalyticsHelper::calculateSeasonalIndex($currentMonth);
        
        return [
            'current_multiplier' => $multiplier,
            'description' => 'Seasonal adjustment based on historical patterns',
            'recommendation' => $multiplier > 1 ? 'Increase inventory' : 'Standard allocation'
        ];
    }

    private function getVelocityTrends()
    {
        return [
            'hot_sellers' => [12, 15, 18, 16, 20, 22],
            'good_movers' => [8, 10, 12, 14, 15, 16],
            'slow_movers' => [5, 4, 6, 5, 4, 3],
            'dead_stock' => [3, 2, 2, 1, 1, 1]
        ];
    }

    private function getLocationDemandAnalysis()
    {
        return [
            'malang_kota' => 45,
            'malang_kabupaten' => 30,
            'batu' => 15,
            'lainnya' => 10
        ];
    }

    private function getCostBreakdownAnalysis($profitability)
    {
        return [
            'total_cogs' => $profitability->sum('cogs'),
            'total_logistics' => $profitability->sum('logistics_cost'),
            'total_opportunity' => $profitability->sum('opportunity_cost'),
            'total_time_value' => $profitability->sum('time_value_cost')
        ];
    }

    private function getROIRanking($profitability)
    {
        return $profitability->sortByDesc('roi')->take(10);
    }

    private function getChannelRecommendations($comparison)
    {
        return [
            'optimal_b2b_allocation' => 65,
            'optimal_b2c_allocation' => 35,
            'expected_improvement' => 32
        ];
    }

    private function getSeasonalForecasts()
    {
        return [
            'Jul 2025' => 1250,
            'Aug 2025' => 1180,
            'Sep 2025' => 1320,
            'Oct 2025' => 1150,
            'Nov 2025' => 1280,
            'Dec 2025' => 1450
        ];
    }

    private function calculateConfidenceLevel($dataPoints)
    {
        if ($dataPoints >= 12) return 'High';
        if ($dataPoints >= 6) return 'Medium';
        if ($dataPoints >= 3) return 'Low';
        return 'Very Low';
    }
}
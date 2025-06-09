<?php

namespace App\Services;

use App\Models\Toko;
use App\Models\Barang;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\Pemesanan;
use App\Models\BarangToko;
use App\Helpers\AnalyticsHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsService
{
    const CACHE_DURATION = 30; // minutes

    /**
     * Get comprehensive partner performance analytics
     */
    public function getPartnerPerformanceAnalytics($period = 6)
    {
        $cacheKey = "analytics.partner_performance.{$period}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($period) {
            $periodStart = Carbon::now()->subMonths($period);
            
            return Toko::where('is_active', true)
                ->get()
                ->map(function ($toko) use ($periodStart) {
                    return $this->calculatePartnerMetrics($toko, $periodStart);
                })
                ->sortByDesc('performance_score')
                ->values();
        });
    }

    /**
     * Calculate detailed metrics for a single partner
     */
    private function calculatePartnerMetrics($toko, $periodStart)
    {
        // Basic shipment and return data
        $shipmentData = $this->getShipmentData($toko, $periodStart);
        $returnData = $this->getReturnData($toko, $periodStart);
        
        // Calculate core metrics
        $totalShipped = $shipmentData['total_shipped'];
        $totalReturned = $returnData['total_returned'];
        $totalSold = $totalShipped - $totalReturned;
        $sellThroughRate = $totalShipped > 0 ? ($totalSold / $totalShipped) * 100 : 0;
        
        // Revenue and profitability
        $revenue = $returnData['total_revenue'];
        $avgDaysToReturn = $returnData['avg_days_to_return'];
        
        // Performance scoring
        $performanceScore = $this->calculatePerformanceScore(
            $sellThroughRate, 
            $avgDaysToReturn, 
            $revenue, 
            $totalShipped
        );
        
        $grade = $this->calculateGrade($performanceScore);
        
        // Risk assessment
        $riskScore = $this->calculateRiskScore($toko, $periodStart);
        
        // Trend analysis
        $trend = $this->calculateTrend($toko, $periodStart);
        
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
            'performance_score' => $performanceScore,
            'risk_score' => $riskScore,
            'trend' => $trend,
            'shipment_count' => $shipmentData['shipment_count'],
            'avg_shipment_size' => $shipmentData['avg_shipment_size'],
            'consistency_score' => $this->calculateConsistencyScore($toko, $periodStart)
        ];
    }

    /**
     * Get shipment data for a partner
     */
    private function getShipmentData($toko, $periodStart)
    {
        $shipments = Pengiriman::where('toko_id', $toko->toko_id)
            ->where('status', 'terkirim')
            ->where('tanggal_pengiriman', '>=', $periodStart)
            ->get();
        
        return [
            'total_shipped' => $shipments->sum('jumlah_kirim'),
            'shipment_count' => $shipments->count(),
            'avg_shipment_size' => $shipments->count() > 0 ? $shipments->avg('jumlah_kirim') : 0
        ];
    }

    /**
     * Get return data for a partner
     */
    private function getReturnData($toko, $periodStart)
    {
        $returns = Retur::where('toko_id', $toko->toko_id)
            ->where('tanggal_retur', '>=', $periodStart)
            ->get();
        
        $avgDays = $returns->isEmpty() ? 0 : $returns->map(function ($retur) {
            if ($retur->tanggal_retur && $retur->tanggal_pengiriman) {
                return Carbon::parse($retur->tanggal_retur)->diffInDays(Carbon::parse($retur->tanggal_pengiriman));
            }
            return 14; // Default
        })->avg();
        
        return [
            'total_returned' => $returns->sum('jumlah_retur'),
            'total_revenue' => $returns->sum('hasil'),
            'avg_days_to_return' => $avgDays,
            'return_count' => $returns->count()
        ];
    }

    /**
     * Calculate performance score using weighted metrics
     */
    private function calculatePerformanceScore($sellThroughRate, $avgDaysToReturn, $revenue, $totalShipped)
    {
        // Weights for different metrics
        $weights = [
            'sell_through' => 0.4,
            'speed' => 0.25,
            'revenue' => 0.25,
            'volume' => 0.1
        ];
        
        // Normalize metrics to 0-100 scale
        $sellThroughScore = min($sellThroughRate, 100);
        
        // Speed score (inverse of days - faster is better)
        $speedScore = $avgDaysToReturn > 0 ? max(100 - ($avgDaysToReturn * 2), 0) : 50;
        
        // Revenue score (normalized to max expected revenue)
        $revenueScore = min(($revenue / 15000000) * 100, 100); // Assuming max 15M
        
        // Volume score (normalized to max expected volume)
        $volumeScore = min(($totalShipped / 5000) * 100, 100); // Assuming max 5000 units
        
        // Calculate weighted score
        $totalScore = ($sellThroughScore * $weights['sell_through']) +
                     ($speedScore * $weights['speed']) +
                     ($revenueScore * $weights['revenue']) +
                     ($volumeScore * $weights['volume']);
        
        return round($totalScore, 2);
    }

    /**
     * Calculate grade based on performance score
     */
    private function calculateGrade($performanceScore)
    {
        if ($performanceScore >= 90) return 'A+';
        if ($performanceScore >= 80) return 'A';
        if ($performanceScore >= 70) return 'B+';
        if ($performanceScore >= 60) return 'B';
        if ($performanceScore >= 50) return 'C+';
        return 'C';
    }

    /**
     * Calculate risk score for partner
     */
    private function calculateRiskScore($toko, $periodStart)
    {
        $riskFactors = [];
        
        // Recent performance decline
        $recentPerformance = $this->getRecentPerformanceTrend($toko);
        if ($recentPerformance['trend'] === 'declining') {
            $riskFactors[] = ['factor' => 'declining_performance', 'weight' => 30];
        }
        
        // High return rate
        $shipments = Pengiriman::where('toko_id', $toko->toko_id)
            ->where('tanggal_pengiriman', '>=', $periodStart)
            ->get();
        $returns = Retur::where('toko_id', $toko->toko_id)
            ->where('tanggal_retur', '>=', $periodStart)
            ->get();
        
        if ($shipments->isNotEmpty()) {
            $returnRate = ($returns->sum('jumlah_retur') / $shipments->sum('jumlah_kirim')) * 100;
            if ($returnRate > 40) {
                $riskFactors[] = ['factor' => 'high_return_rate', 'weight' => 25];
            }
        }
        
        // Slow payment (long days to return)
        $avgDays = $returns->isEmpty() ? 0 : $returns->map(function ($retur) {
            if ($retur->tanggal_retur && $retur->tanggal_pengiriman) {
                return Carbon::parse($retur->tanggal_retur)->diffInDays(Carbon::parse($retur->tanggal_pengiriman));
            }
            return 14;
        })->avg();
        
        if ($avgDays > 30) {
            $riskFactors[] = ['factor' => 'slow_payment', 'weight' => 20];
        }
        
        // Low revenue per shipment
        $avgRevenue = $shipments->isNotEmpty() ? $returns->sum('hasil') / $shipments->count() : 0;
        if ($avgRevenue < 500000) { // Less than 500k per shipment
            $riskFactors[] = ['factor' => 'low_revenue', 'weight' => 15];
        }
        
        // Calculate total risk score
        $totalRisk = collect($riskFactors)->sum('weight');
        
        return [
            'score' => min($totalRisk, 100),
            'level' => $this->getRiskLevel($totalRisk),
            'factors' => $riskFactors
        ];
    }

    /**
     * Get risk level based on score
     */
    private function getRiskLevel($score)
    {
        if ($score >= 70) return 'High';
        if ($score >= 40) return 'Medium';
        return 'Low';
    }

    /**
     * Calculate consistency score
     */
    private function calculateConsistencyScore($toko, $periodStart)
    {
        $monthlyData = [];
        
        for ($i = 0; $i < 6; $i++) {
            $monthStart = Carbon::now()->subMonths($i + 1)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i + 1)->endOfMonth();
            
            $monthlyShipped = Pengiriman::where('toko_id', $toko->toko_id)
                ->where('status', 'terkirim')
                ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                ->sum('jumlah_kirim');
            
            $monthlyReturned = Retur::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                ->sum('jumlah_retur');
            
            $monthlySold = $monthlyShipped - $monthlyReturned;
            $monthlySellThrough = $monthlyShipped > 0 ? ($monthlySold / $monthlyShipped) * 100 : 0;
            
            $monthlyData[] = $monthlySellThrough;
        }
        
        if (empty($monthlyData)) return 0;
        
        // Calculate coefficient of variation
        $mean = collect($monthlyData)->avg();
        $variance = collect($monthlyData)->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();
        
        $stdDev = sqrt($variance);
        $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 100;
        
        // Convert to consistency score (higher is better)
        return max(100 - $cv, 0);
    }

    /**
     * Get recent performance trend
     */
    private function getRecentPerformanceTrend($toko)
    {
        $last3Months = [];
        
        for ($i = 0; $i < 3; $i++) {
            $monthStart = Carbon::now()->subMonths($i + 1)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i + 1)->endOfMonth();
            
            $shipped = Pengiriman::where('toko_id', $toko->toko_id)
                ->where('status', 'terkirim')
                ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                ->sum('jumlah_kirim');
            
            $returned = Retur::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                ->sum('jumlah_retur');
            
            $sellThrough = $shipped > 0 ? (($shipped - $returned) / $shipped) * 100 : 0;
            $last3Months[] = $sellThrough;
        }
        
        if (count($last3Months) < 2) {
            return ['trend' => 'insufficient_data', 'direction' => 0];
        }
        
        // Simple trend analysis
        $recent = $last3Months[0]; // Most recent month
        $previous = $last3Months[1]; // Previous month
        
        $change = $recent - $previous;
        
        if (abs($change) < 5) {
            $trend = 'stable';
        } elseif ($change > 0) {
            $trend = 'improving';
        } else {
            $trend = 'declining';
        }
        
        return [
            'trend' => $trend,
            'direction' => $change,
            'data' => array_reverse($last3Months) // Oldest to newest
        ];
    }

    /**
     * Calculate trend for partner
     */
    private function calculateTrend($toko, $periodStart)
    {
        return $this->getRecentPerformanceTrend($toko);
    }

    /**
     * Get inventory optimization recommendations
     */
    public function getInventoryOptimizationRecommendations()
    {
        $cacheKey = "analytics.inventory_optimization";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            return BarangToko::with(['toko', 'barang'])
                ->whereHas('toko', function($q) {
                    $q->where('is_active', true);
                })
                ->whereHas('barang', function($q) {
                    $q->where('is_deleted', 0);
                })
                ->get()
                ->map(function ($barangToko) {
                    return $this->calculateInventoryRecommendation($barangToko);
                })
                ->filter()
                ->sortByDesc('recommended_quantity')
                ->values();
        });
    }

    /**
     * Calculate inventory recommendation for specific product-store combination
     */
    private function calculateInventoryRecommendation($barangToko)
    {
        $historicalData = Pengiriman::where('toko_id', $barangToko->toko_id)
            ->where('barang_id', $barangToko->barang_id)
            ->where('status', 'terkirim')
            ->where('tanggal_pengiriman', '>=', Carbon::now()->subMonths(6))
            ->get();
        
        if ($historicalData->isEmpty()) {
            return null;
        }
        
        // Calculate historical averages
        $avgShipped = $historicalData->avg('jumlah_kirim');
        $avgSold = $historicalData->map(function ($shipment) {
            $returned = Retur::where('pengiriman_id', $shipment->pengiriman_id)->sum('jumlah_retur');
            return $shipment->jumlah_kirim - $returned;
        })->avg();
        
        // Apply seasonal and trend adjustments
        $seasonalMultiplier = $this->getSeasonalMultiplier();
        $trendMultiplier = $this->getTrendMultiplier($barangToko);
        
        $recommendedQuantity = round($avgSold * $seasonalMultiplier * $trendMultiplier);
        
        // Calculate confidence level
        $confidenceLevel = $this->calculateConfidenceLevel($historicalData->count());
        
        // Calculate potential savings
        $currentWaste = max($avgShipped - $avgSold, 0);
        $potentialSavings = $currentWaste * 15000; // Average cost per unit
        
        return [
            'toko_nama' => $barangToko->toko->nama_toko,
            'barang_nama' => $barangToko->barang->nama_barang,
            'toko_id' => $barangToko->toko_id,
            'barang_id' => $barangToko->barang_id,
            'historical_avg_shipped' => round($avgShipped, 0),
            'historical_avg_sold' => round($avgSold, 0),
            'recommended_quantity' => $recommendedQuantity,
            'seasonal_multiplier' => $seasonalMultiplier,
            'trend_multiplier' => $trendMultiplier,
            'confidence_level' => $confidenceLevel,
            'potential_savings' => $potentialSavings,
            'improvement_percentage' => $avgShipped > 0 ? round(($currentWaste / $avgShipped) * 100, 1) : 0
        ];
    }

    /**
     * Get seasonal multiplier based on current month
     */
    private function getSeasonalMultiplier()
    {
        return AnalyticsHelper::calculateSeasonalIndex(Carbon::now()->month);
    }

    /**
     * Get trend multiplier for specific product-store combination
     */
    private function getTrendMultiplier($barangToko)
    {
        // Simple trend analysis based on last 3 months vs previous 3 months
        $recent3Months = Pengiriman::where('toko_id', $barangToko->toko_id)
            ->where('barang_id', $barangToko->barang_id)
            ->where('status', 'terkirim')
            ->where('tanggal_pengiriman', '>=', Carbon::now()->subMonths(3))
            ->avg('jumlah_kirim');
        
        $previous3Months = Pengiriman::where('toko_id', $barangToko->toko_id)
            ->where('barang_id', $barangToko->barang_id)
            ->where('status', 'terkirim')
            ->whereBetween('tanggal_pengiriman', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])
            ->avg('jumlah_kirim');
        
        if (!$recent3Months || !$previous3Months) {
            return 1.0; // No trend adjustment if insufficient data
        }
        
        $trendRatio = $recent3Months / $previous3Months;
        
        // Cap the trend adjustment to prevent extreme recommendations
        return max(0.8, min(1.3, $trendRatio));
    }

    /**
     * Calculate confidence level based on data points
     */
    private function calculateConfidenceLevel($dataPoints)
    {
        if ($dataPoints >= 12) return 'High';
        if ($dataPoints >= 6) return 'Medium';
        if ($dataPoints >= 3) return 'Low';
        return 'Very Low';
    }

    /**
     * Get product velocity analysis
     */
    public function getProductVelocityAnalysis()
    {
        $cacheKey = "analytics.product_velocity";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            $products = Barang::where('is_deleted', 0)->get()->map(function ($barang) {
                return $this->analyzeProductVelocity($barang);
            });
            
            return $products->groupBy('velocity_category');
        });
    }

    /**
     * Analyze velocity for a specific product
     */
    private function analyzeProductVelocity($barang)
    {
        $period = Carbon::now()->subMonths(3);
        
        $shipments = Pengiriman::where('barang_id', $barang->barang_id)
            ->where('status', 'terkirim')
            ->where('tanggal_pengiriman', '>=', $period)
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
        
        // Categorize based on velocity score
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
    }

    /**
     * Calculate velocity score
     */
    private function calculateVelocityScore($sellThroughRate, $avgDaysToSell, $totalSold)
    {
        // Weights
        $sellThroughWeight = 0.4;
        $speedWeight = 0.4;
        $volumeWeight = 0.2;
        
        // Normalize metrics
        $sellThroughScore = min($sellThroughRate, 100);
        $speedScore = max(100 - ($avgDaysToSell * 3), 0);
        $volumeScore = min(($totalSold / 1000) * 100, 100); // Normalize to 1000 units max
        
        return round(
            ($sellThroughScore * $sellThroughWeight) +
            ($speedScore * $speedWeight) +
            ($volumeScore * $volumeWeight),
            2
        );
    }

    /**
     * Categorize product based on velocity
     */
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

    /**
     * Calculate true profitability for all partners
     */
    public function calculateTrueProfitability($period = 6)
    {
        $cacheKey = "analytics.true_profitability.{$period}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($period) {
            $periodStart = Carbon::now()->subMonths($period);
            
            return Toko::where('is_active', true)
                ->get()
                ->map(function ($toko) use ($periodStart) {
                    return $this->calculatePartnerProfitability($toko, $periodStart);
                })
                ->sortByDesc('roi')
                ->values();
        });
    }

    /**
     * Calculate profitability for a specific partner
     */
    private function calculatePartnerProfitability($toko, $periodStart)
    {
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
        
        $avgCOGS = 15000; // Adjust based on your actual data
        $cogs = $totalSold * $avgCOGS;
        
        // Other costs
        $logisticsCost = $this->calculateLogisticsCost($toko, $periodStart);
        $opportunityCost = $this->calculateOpportunityCost($toko, $periodStart);
        $timeValueCost = $this->calculateTimeValueCost($toko, $periodStart);
        
        // Total costs and profit calculation
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
    }

    /**
     * Calculate logistics cost for a partner
     */
    private function calculateLogisticsCost($toko, $periodStart)
    {
        // Simple distance calculation - you can enhance this with actual distance data
        $distance = 10; // Default 10km
        $shipmentCount = Pengiriman::where('toko_id', $toko->toko_id)
            ->where('tanggal_pengiriman', '>=', $periodStart)
            ->where('status', 'terkirim')
            ->count();
        
        // Cost per km (fuel + driver time + vehicle depreciation)
        $costPerKm = 2500; // Adjust based on actual costs
        
        return $shipmentCount * ($distance * $costPerKm);
    }

    /**
     * Calculate opportunity cost
     */
    private function calculateOpportunityCost($toko, $periodStart)
    {
        $avgInventoryValue = Pengiriman::where('toko_id', $toko->toko_id)
            ->where('tanggal_pengiriman', '>=', $periodStart)
            ->sum('jumlah_kirim') * 15000; // Average unit cost
        
        $monthsPeriod = $periodStart->diffInMonths(Carbon::now());
        $annualOpportunityRate = 0.12; // 12% annual rate
        
        return ($avgInventoryValue / $monthsPeriod) * ($annualOpportunityRate / 12) * $monthsPeriod;
    }

    /**
     * Calculate time value cost
     */
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
        
        // Cost of capital tied up in consignment
        $monthlyRate = 0.01; // 1% per month
        
        return ($avgConsignmentDays / 30) * ($avgInventoryValue / $monthsPeriod) * $monthlyRate * $monthsPeriod;
    }

    /**
     * Generate alerts for underperforming partners
     */
    public function generatePartnerAlerts()
    {
        $partners = $this->getPartnerPerformanceAnalytics();
        $alerts = collect();
        
        foreach ($partners as $partner) {
            if ($partner['grade'] === 'C' || $partner['sell_through_rate'] < 40) {
                $alerts->push([
                    'type' => 'performance',
                    'severity' => 'high',
                    'partner' => $partner,
                    'message' => "Partner {$partner['nama_toko']} has critically low performance (Grade: {$partner['grade']}, Sell-through: {$partner['sell_through_rate']}%)",
                    'recommendations' => $this->generatePartnerRecommendations($partner)
                ]);
            } elseif ($partner['trend']['trend'] === 'declining') {
                $alerts->push([
                    'type' => 'trend',
                    'severity' => 'medium',
                    'partner' => $partner,
                    'message' => "Partner {$partner['nama_toko']} shows declining performance trend",
                    'recommendations' => $this->generateTrendRecommendations($partner)
                ]);
            }
        }
        
        return $alerts;
    }

    /**
     * Generate recommendations for underperforming partners
     */
    private function generatePartnerRecommendations($partner)
    {
        $recommendations = collect();
        
        if ($partner['sell_through_rate'] < 50) {
            $recommendations->push("Reduce shipment quantities by 30-50% until performance improves");
            $recommendations->push("Provide sales training and support");
            $recommendations->push("Consider different product mix more suitable for this location");
        }
        
        if ($partner['avg_days_to_return'] > 28) {
            $recommendations->push("Implement more frequent check-ins and collection schedules");
            $recommendations->push("Consider payment incentives for faster returns");
        }
        
        if ($partner['revenue'] < 1000000) {
            $recommendations->push("Evaluate if this partnership is economically viable");
            $recommendations->push("Consider consolidating with nearby better-performing partners");
        }
        
        return $recommendations->toArray();
    }

    /**
     * Generate recommendations for declining trend partners
     */
    private function generateTrendRecommendations($partner)
    {
        return [
            "Investigate causes of performance decline",
            "Schedule partner meeting to address issues",
            "Review local market conditions and competition",
            "Consider temporary promotional support"
        ];
    }
}
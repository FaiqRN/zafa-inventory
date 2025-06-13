<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Exports\ProfitabilityAnalysisExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProfitabilityController extends Controller
{
    /**
     * Display Profitability Analysis
     */
    public function index()
    {
        try {
            $breadcrumb = (object)[
                'title' => 'True Profitability Analysis',
                'list' => ['Home', 'Analytics', 'Profitability Analysis']
            ];

            $profitability = $this->calculateTrueProfitability();
            $costBreakdown = $this->getCostBreakdownAnalysis($profitability);
            $roiRanking = $this->getROIRanking($profitability);
            $profitabilityTrends = $this->getProfitabilityTrends();

            return view('analytics.profitability-analysis', [
                'breadcrumb' => $breadcrumb,
                'profitability' => $profitability,
                'costBreakdown' => $costBreakdown,
                'roiRanking' => $roiRanking,
                'profitabilityTrends' => $profitabilityTrends,
                'activemenu' => 'analytics.profitability-analysis'
            ]);
        } catch (Exception $e) {
            Log::error('Profitability analysis error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat analisis profitabilitas.');
        }
    }

    /**
     * Get Profitability Data for API
     */
    public function getData()
    {
        try {
            $profitability = $this->calculateTrueProfitability();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'profitability' => $profitability,
                    'cost_breakdown' => $this->getCostBreakdownAnalysis($profitability),
                    'roi_ranking' => $this->getROIRanking($profitability),
                    'monthly_trends' => $this->getProfitabilityTrends(),
                    'loss_makers' => $this->identifyLossMakersData()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Profitability getData error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load profitability data'], 500);
        }
    }

    /**
     * Export Profitability Analysis
     */
    public function export()
    {
        try {
            $profitability = $this->calculateTrueProfitability();
            
            return Excel::download(
                new ProfitabilityAnalysisExport($profitability),
                'profitability_analysis_' . date('Y-m-d') . '.xlsx'
            );
        } catch (Exception $e) {
            Log::error('Export profitability error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengexport data profitabilitas.');
        }
    }

    /**
     * Identify Loss Making Partners
     */
    public function identifyLossMakers()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->identifyLossMakersData()
            ]);
        } catch (Exception $e) {
            Log::error('Identify loss makers error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to identify loss makers'], 500);
        }
    }

    /**
     * Flag Partner for Review
     */
    public function flagPartner(Request $request, $partnerId)
    {
        try {
            $partner = Toko::find($partnerId);
            if (!$partner) {
                return response()->json(['error' => 'Partner not found'], 404);
            }
            
            $reason = $request->get('reason', 'Low profitability');
            $priority = $request->get('priority', 'high');
            $notes = $request->get('notes', '');
            
            // Log the flag action
            Log::warning('Partner Flagged for Review', [
                'partner_id' => $partnerId,
                'partner_name' => $partner->nama_toko,
                'reason' => $reason,
                'priority' => $priority,
                'notes' => $notes,
                'flagged_by' => auth()->user()->name ?? 'System',
                'flagged_at' => now()
            ]);
            
            // Get profitability data for context
            $profitabilityData = $this->getPartnerProfitabilityData($partnerId);
            
            return response()->json([
                'success' => true,
                'message' => 'Partner ' . $partner->nama_toko . ' telah ditandai untuk review',
                'flag_details' => [
                    'partner' => $partner->nama_toko,
                    'reason' => $reason,
                    'priority' => $priority,
                    'current_roi' => $profitabilityData['roi'] ?? 0,
                    'current_profit' => $profitabilityData['net_profit'] ?? 0,
                    'next_action' => 'Schedule review meeting within 7 days',
                    'escalation_level' => $priority === 'critical' ? 'Immediate management attention' : 'Standard review process'
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Flag partner error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to flag partner'], 500);
        }
    }

    /**
     * Optimize Partner Profitability
     */
    public function optimizePartner(Request $request, $partnerId)
    {
        try {
            $partner = Toko::find($partnerId);
            if (!$partner) {
                return response()->json(['error' => 'Partner not found'], 404);
            }
            
            $profitabilityData = $this->getPartnerProfitabilityData($partnerId);
            $optimizations = $this->generatePartnerOptimizations($profitabilityData);
            
            return response()->json([
                'success' => true,
                'partner' => $partner->nama_toko,
                'current_performance' => [
                    'roi' => $profitabilityData['roi'] ?? 0,
                    'profit_margin' => $profitabilityData['profit_margin'] ?? 0,
                    'revenue' => $profitabilityData['revenue'] ?? 0,
                    'net_profit' => $profitabilityData['net_profit'] ?? 0
                ],
                'optimizations' => $optimizations,
                'projected_improvements' => [
                    'roi_improvement' => $optimizations['projected_roi_gain'] ?? 0,
                    'cost_savings' => $optimizations['potential_cost_savings'] ?? 0,
                    'timeline' => '3-6 months',
                    'confidence_level' => $this->calculateOptimizationConfidence($profitabilityData)
                ],
                'implementation_plan' => $this->generateImplementationPlan($optimizations)
            ]);
        } catch (Exception $e) {
            Log::error('Optimize partner error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to optimize partner'], 500);
        }
    }

    /**
     * Get ROI Distribution Analysis
     */
    public function getRoiDistribution()
    {
        try {
            $profitability = $this->calculateTrueProfitability();
            
            $distribution = [
                'excellent' => $profitability->where('roi', '>=', 30)->count(),
                'good' => $profitability->whereBetween('roi', [20, 29.99])->count(),
                'average' => $profitability->whereBetween('roi', [10, 19.99])->count(),
                'poor' => $profitability->whereBetween('roi', [0, 9.99])->count(),
                'loss_making' => $profitability->where('roi', '<', 0)->count()
            ];
            
            $statistics = [
                'total_partners' => $profitability->count(),
                'profitable_partners' => $profitability->where('roi', '>', 0)->count(),
                'average_roi' => $profitability->avg('roi'),
                'median_roi' => $profitability->median('roi'),
                'top_performer_roi' => $profitability->max('roi'),
                'worst_performer_roi' => $profitability->min('roi')
            ];
            
            return response()->json([
                'success' => true,
                'distribution' => $distribution,
                'statistics' => $statistics,
                'benchmarks' => [
                    'target_roi' => 25,
                    'minimum_acceptable_roi' => 15,
                    'warning_threshold' => 5
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Get ROI distribution error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get ROI distribution'], 500);
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    private function calculateTrueProfitability()
    {
        try {
            $periodStart = Carbon::now()->subMonths(6);
            $partners = Toko::where('is_active', true)->get();
            
            if ($partners->isEmpty()) {
                return collect();
            }
            
            return $partners->map(function ($toko) use ($periodStart) {
                try {
                    // Revenue from sales
                    $revenue = Retur::where('toko_id', $toko->toko_id)
                        ->where('tanggal_retur', '>=', $periodStart)
                        ->sum('hasil') ?? 0;
                    
                    // Calculate units sold
                    $totalShipped = Pengiriman::where('toko_id', $toko->toko_id)
                        ->where('tanggal_pengiriman', '>=', $periodStart)
                        ->sum('jumlah_kirim') ?? 0;
                    
                    $totalReturned = Retur::where('toko_id', $toko->toko_id)
                        ->where('tanggal_retur', '>=', $periodStart)
                        ->sum('jumlah_retur') ?? 0;
                    
                    $totalSold = max(0, $totalShipped - $totalReturned);
                    
                    // Calculate costs
                    $avgCOGS = 15000;
                    $cogs = $totalSold * $avgCOGS;
                    
                    $logisticsCost = $this->calculateLogisticsCost($toko, $periodStart);
                    $opportunityCost = $this->calculateOpportunityCost($toko, $periodStart);
                    $timeValueCost = $this->calculateTimeValueCost($toko, $periodStart);
                    $operationalCost = $this->calculateOperationalCost($toko, $periodStart);
                    
                    $totalCosts = $cogs + $logisticsCost + $opportunityCost + $timeValueCost + $operationalCost;
                    $netProfit = $revenue - $totalCosts;
                    $roi = $totalCosts > 0 ? ($netProfit / $totalCosts) * 100 : 0;
                    $grossMargin = $revenue > 0 ? (($revenue - $cogs) / $revenue) * 100 : 0;
                    $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;
                    
                    return [
                        'toko' => $toko,
                        'revenue' => $revenue,
                        'cogs' => $cogs,
                        'logistics_cost' => $logisticsCost,
                        'opportunity_cost' => $opportunityCost,
                        'time_value_cost' => $timeValueCost,
                        'operational_cost' => $operationalCost,
                        'total_costs' => $totalCosts,
                        'gross_profit' => $revenue - $cogs,
                        'net_profit' => $netProfit,
                        'roi' => round($roi, 2),
                        'gross_margin' => round($grossMargin, 2),
                        'profit_margin' => round($profitMargin, 2),
                        'units_sold' => $totalSold,
                        'revenue_per_unit' => $totalSold > 0 ? round($revenue / $totalSold, 0) : 0,
                        'cost_breakdown' => [
                            'cogs_percentage' => $totalCosts > 0 ? round(($cogs / $totalCosts) * 100, 1) : 0,
                            'logistics_percentage' => $totalCosts > 0 ? round(($logisticsCost / $totalCosts) * 100, 1) : 0,
                            'opportunity_percentage' => $totalCosts > 0 ? round(($opportunityCost / $totalCosts) * 100, 1) : 0,
                            'time_value_percentage' => $totalCosts > 0 ? round(($timeValueCost / $totalCosts) * 100, 1) : 0,
                            'operational_percentage' => $totalCosts > 0 ? round(($operationalCost / $totalCosts) * 100, 1) : 0
                        ]
                    ];
                } catch (Exception $e) {
                    Log::warning('Error calculating profitability for toko ' . $toko->toko_id . ': ' . $e->getMessage());
                    return $this->getDefaultProfitabilityData($toko);
                }
            })->sortByDesc('roi');
        } catch (Exception $e) {
            Log::error('Calculate true profitability error: ' . $e->getMessage());
            return collect();
        }
    }

    private function calculateLogisticsCost($toko, $periodStart)
    {
        try {
            $distance = $this->estimateDistance($toko->alamat ?? '');
            
            $shipmentCount = Pengiriman::where('toko_id', $toko->toko_id)
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->where('status', 'terkirim')
                ->count();
            
            $costPerKm = 3000;
            $baseCost = 10000;
            $fuelSurcharge = 5000;
            
            return $shipmentCount * ($baseCost + ($distance * $costPerKm) + $fuelSurcharge);
        } catch (Exception $e) {
            Log::warning('Calculate logistics cost error: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateOpportunityCost($toko, $periodStart)
    {
        try {
            $avgInventoryValue = Pengiriman::where('toko_id', $toko->toko_id)
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->sum('jumlah_kirim') * 15000;
            
            $monthsPeriod = max(1, $periodStart->diffInMonths(Carbon::now()));
            $annualOpportunityRate = 0.15; // 15% annual opportunity cost
            
            return ($avgInventoryValue / $monthsPeriod) * ($annualOpportunityRate / 12) * $monthsPeriod;
        } catch (Exception $e) {
            Log::warning('Calculate opportunity cost error: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateTimeValueCost($toko, $periodStart)
    {
        try {
            $avgConsignmentDays = DB::table('retur')
                ->join('pengiriman', 'retur.pengiriman_id', '=', 'pengiriman.pengiriman_id')
                ->where('retur.toko_id', $toko->toko_id)
                ->where('retur.tanggal_retur', '>=', $periodStart)
                ->whereNotNull('retur.tanggal_retur')
                ->whereNotNull('pengiriman.tanggal_pengiriman')
                ->selectRaw('AVG(DATEDIFF(retur.tanggal_retur, pengiriman.tanggal_pengiriman)) as avg_days')
                ->value('avg_days') ?? 21;
            
            $avgInventoryValue = Pengiriman::where('toko_id', $toko->toko_id)
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->sum('jumlah_kirim') * 15000;
            
            $monthsPeriod = max(1, $periodStart->diffInMonths(Carbon::now()));
            $monthlyRate = 0.015; // 1.5% per month time value
            
            return ($avgConsignmentDays / 30) * ($avgInventoryValue / $monthsPeriod) * $monthlyRate * $monthsPeriod;
        } catch (Exception $e) {
            Log::warning('Calculate time value cost error: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateOperationalCost($toko, $periodStart)
    {
        try {
            $shipmentCount = Pengiriman::where('toko_id', $toko->toko_id)
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->count();
            
            // Operational costs per transaction
            $adminCostPerShipment = 5000;
            $communicationCost = 2000;
            $marketingAllocation = 3000;
            
            return $shipmentCount * ($adminCostPerShipment + $communicationCost + $marketingAllocation);
        } catch (Exception $e) {
            Log::warning('Calculate operational cost error: ' . $e->getMessage());
            return 0;
        }
    }

    private function estimateDistance($alamat)
    {
        $alamatLower = strtolower($alamat);
        
        if (strpos($alamatLower, 'malang kota') !== false || strpos($alamatLower, 'kota malang') !== false) {
            return 8;
        } elseif (strpos($alamatLower, 'batu') !== false) {
            return 15;
        } elseif (strpos($alamatLower, 'malang') !== false) {
            return 12;
        } else {
            return 20;
        }
    }

    private function getCostBreakdownAnalysis($profitability)
    {
        try {
            return [
                'total_cogs' => $profitability->sum('cogs'),
                'total_logistics' => $profitability->sum('logistics_cost'),
                'total_opportunity' => $profitability->sum('opportunity_cost'),
                'total_time_value' => $profitability->sum('time_value_cost'),
                'total_operational' => $profitability->sum('operational_cost'),
                'avg_cogs_percentage' => $profitability->avg('cost_breakdown.cogs_percentage'),
                'avg_logistics_percentage' => $profitability->avg('cost_breakdown.logistics_percentage'),
                'avg_opportunity_percentage' => $profitability->avg('cost_breakdown.opportunity_percentage'),
                'avg_time_value_percentage' => $profitability->avg('cost_breakdown.time_value_percentage'),
                'avg_operational_percentage' => $profitability->avg('cost_breakdown.operational_percentage')
            ];
        } catch (Exception $e) {
            Log::error('Get cost breakdown analysis error: ' . $e->getMessage());
            return [
                'total_cogs' => 0,
                'total_logistics' => 0,
                'total_opportunity' => 0,
                'total_time_value' => 0,
                'total_operational' => 0,
                'avg_cogs_percentage' => 0,
                'avg_logistics_percentage' => 0,
                'avg_opportunity_percentage' => 0,
                'avg_time_value_percentage' => 0,
                'avg_operational_percentage' => 0
            ];
        }
    }

    private function getROIRanking($profitability)
    {
        try {
            return $profitability->sortByDesc('roi')->take(15);
        } catch (Exception $e) {
            Log::error('Get ROI ranking error: ' . $e->getMessage());
            return collect();
        }
    }

    private function getProfitabilityTrends()
    {
        try {
            $trends = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                $monthlyProfitability = $this->calculateProfitabilityForPeriod($monthStart, $monthEnd);
                
                $trends[] = [
                    'month' => $monthStart->format('M Y'),
                    'avg_roi' => $monthlyProfitability->avg('roi') ?: 0,
                    'total_revenue' => $monthlyProfitability->sum('revenue'),
                    'total_profit' => $monthlyProfitability->sum('net_profit'),
                    'profitable_partners' => $monthlyProfitability->where('roi', '>', 0)->count(),
                    'total_partners' => $monthlyProfitability->count()
                ];
            }
            
            return $trends;
        } catch (Exception $e) {
            Log::error('Get profitability trends error: ' . $e->getMessage());
            return [];
        }
    }

    private function calculateProfitabilityForPeriod($startDate, $endDate)
    {
        // Simplified calculation for trends
        $partners = Toko::where('is_active', true)->get();
        
        return $partners->map(function ($toko) use ($startDate, $endDate) {
            $revenue = Retur::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_retur', [$startDate, $endDate])
                ->sum('hasil') ?? 0;
            
            $totalShipped = Pengiriman::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_pengiriman', [$startDate, $endDate])
                ->sum('jumlah_kirim') ?? 0;
            
            $estimatedCosts = $totalShipped * 20000; // Simplified cost calculation
            $netProfit = $revenue - $estimatedCosts;
            $roi = $estimatedCosts > 0 ? ($netProfit / $estimatedCosts) * 100 : 0;
            
            return [
                'toko' => $toko,
                'revenue' => $revenue,
                'net_profit' => $netProfit,
                'roi' => round($roi, 2)
            ];
        });
    }

    private function identifyLossMakersData()
    {
        try {
            $profitability = $this->calculateTrueProfitability();
            $lossMakers = $profitability->where('roi', '<', 5);
            
            $alerts = $lossMakers->map(function ($lossmaker) {
                return [
                    'partner_id' => $lossmaker['toko']->toko_id,
                    'partner_name' => $lossmaker['toko']->nama_toko,
                    'roi' => $lossmaker['roi'],
                    'net_profit' => $lossmaker['net_profit'],
                    'total_costs' => $lossmaker['total_costs'],
                    'revenue' => $lossmaker['revenue'],
                    'severity' => $lossmaker['roi'] < 0 ? 'Critical' : ($lossmaker['roi'] < 3 ? 'High' : 'Medium'),
                    'recommended_action' => $this->getRecommendedAction($lossmaker['roi']),
                    'potential_savings' => $this->calculatePotentialSavings($lossmaker),
                    'turnaround_probability' => $this->calculateTurnaroundProbability($lossmaker)
                ];
            });
            
            return [
                'loss_makers' => $alerts,
                'summary' => [
                    'total_loss_makers' => $lossMakers->count(),
                    'critical_cases' => $lossMakers->where('roi', '<', 0)->count(),
                    'high_risk_cases' => $lossMakers->whereBetween('roi', [0, 3])->count(),
                    'medium_risk_cases' => $lossMakers->whereBetween('roi', [3, 5])->count(),
                    'total_losses' => $lossMakers->where('net_profit', '<', 0)->sum('net_profit'),
                    'avg_roi' => $lossMakers->avg('roi')
                ]
            ];
        } catch (Exception $e) {
            Log::error('Identify loss makers data error: ' . $e->getMessage());
            return [
                'loss_makers' => [],
                'summary' => [
                    'total_loss_makers' => 0,
                    'critical_cases' => 0,
                    'high_risk_cases' => 0,
                    'medium_risk_cases' => 0,
                    'total_losses' => 0,
                    'avg_roi' => 0
                ]
            ];
        }
    }

    private function getRecommendedAction($roi)
    {
        if ($roi < -10) return 'Immediate termination recommended';
        if ($roi < 0) return 'Urgent review and cost optimization';
        if ($roi < 3) return 'Performance improvement plan required';
        return 'Monitor closely and optimize costs';
    }

    private function calculatePotentialSavings($profitabilityData)
    {
        // Calculate potential savings through optimization
        $currentCosts = $profitabilityData['total_costs'];
        $optimizationPotential = 0.15; // 15% potential cost reduction
        
        return round($currentCosts * $optimizationPotential, 0);
    }

    private function calculateTurnaroundProbability($profitabilityData)
    {
        $roi = $profitabilityData['roi'];
        $revenue = $profitabilityData['revenue'];
        
        if ($roi < -20) return 10; // Very low chance
        if ($roi < -10) return 25;
        if ($roi < 0) return 50;
        if ($roi < 3) return 75;
        return 90; // High chance
    }

    private function getPartnerProfitabilityData($partnerId)
    {
        try {
            $profitability = $this->calculateTrueProfitability()->firstWhere('toko.toko_id', $partnerId);
            return $profitability ? $profitability : [];
        } catch (Exception $e) {
            Log::error('Get partner profitability data error: ' . $e->getMessage());
            return [];
        }
    }

    private function generatePartnerOptimizations($profitabilityData)
    {
        $optimizations = [
            'cost_reduction' => [],
            'revenue_enhancement' => [],
            'operational_efficiency' => [],
            'projected_roi_gain' => 0,
            'potential_cost_savings' => 0
        ];
        
        if (empty($profitabilityData)) {
            return $optimizations;
        }
        
        // Cost reduction opportunities
        if (isset($profitabilityData['cost_breakdown']['logistics_percentage']) && $profitabilityData['cost_breakdown']['logistics_percentage'] > 15) {
            $optimizations['cost_reduction'][] = [
                'area' => 'Logistics Optimization',
                'action' => 'Consolidate deliveries and optimize routes',
                'potential_savings' => $profitabilityData['logistics_cost'] * 0.25,
                'implementation_effort' => 'Medium'
            ];
            $optimizations['potential_cost_savings'] += $profitabilityData['logistics_cost'] * 0.25;
        }
        
        if (isset($profitabilityData['cost_breakdown']['time_value_percentage']) && $profitabilityData['cost_breakdown']['time_value_percentage'] > 10) {
            $optimizations['cost_reduction'][] = [
                'area' => 'Payment Terms Improvement',
                'action' => 'Negotiate faster payment cycles',
                'potential_savings' => $profitabilityData['time_value_cost'] * 0.4,
                'implementation_effort' => 'Low'
            ];
            $optimizations['potential_cost_savings'] += $profitabilityData['time_value_cost'] * 0.4;
        }
        
        // Revenue enhancement
        if (isset($profitabilityData['profit_margin']) && $profitabilityData['profit_margin'] < 20) {
            $optimizations['revenue_enhancement'][] = [
                'area' => 'Product Mix Optimization',
                'action' => 'Focus on higher margin products',
                'potential_revenue_increase' => $profitabilityData['revenue'] * 0.15,
                'implementation_effort' => 'Medium'
            ];
        }
        
        if (isset($profitabilityData['revenue_per_unit']) && $profitabilityData['revenue_per_unit'] < 25000) {
            $optimizations['revenue_enhancement'][] = [
                'area' => 'Pricing Strategy',
                'action' => 'Implement value-based pricing',
                'potential_revenue_increase' => $profitabilityData['revenue'] * 0.08,
                'implementation_effort' => 'Low'
            ];
        }
        
        // Operational efficiency
        $optimizations['operational_efficiency'][] = [
            'area' => 'Inventory Management',
            'action' => 'Implement automated reorder points',
            'efficiency_gain' => '20% reduction in stockouts',
            'implementation_effort' => 'High'
        ];
        
        $optimizations['operational_efficiency'][] = [
            'area' => 'Performance Monitoring',
            'action' => 'Weekly performance reviews',
            'efficiency_gain' => 'Early problem detection',
            'implementation_effort' => 'Low'
        ];
        
        // Calculate projected improvements
        $optimizations['projected_roi_gain'] = round(
            $optimizations['potential_cost_savings'] / ($profitabilityData['total_costs'] ?? 1) * 100, 
            2
        );
        
        return $optimizations;
    }

    private function calculateOptimizationConfidence($profitabilityData)
    {
        if (empty($profitabilityData)) return 'Low';
        
        $roi = $profitabilityData['roi'] ?? 0;
        $revenue = $profitabilityData['revenue'] ?? 0;
        
        if ($roi > 10 && $revenue > 5000000) return 'High';
        if ($roi > 0 && $revenue > 2000000) return 'Medium';
        return 'Low';
    }

    private function generateImplementationPlan($optimizations)
    {
        return [
            'phase_1' => [
                'duration' => '1-2 weeks',
                'focus' => 'Quick wins and low-effort optimizations',
                'actions' => array_filter($optimizations['cost_reduction'], function($item) {
                    return isset($item['implementation_effort']) && $item['implementation_effort'] === 'Low';
                })
            ],
            'phase_2' => [
                'duration' => '1-2 months',
                'focus' => 'Medium-effort improvements',
                'actions' => array_filter($optimizations['cost_reduction'], function($item) {
                    return isset($item['implementation_effort']) && $item['implementation_effort'] === 'Medium';
                })
            ],
            'phase_3' => [
                'duration' => '3-6 months',
                'focus' => 'High-impact, high-effort optimizations',
                'actions' => array_filter($optimizations['operational_efficiency'], function($item) {
                    return isset($item['implementation_effort']) && $item['implementation_effort'] === 'High';
                })
            ]
        ];
    }

    private function getDefaultProfitabilityData($toko)
    {
        return [
            'toko' => $toko,
            'revenue' => 0,
            'cogs' => 0,
            'logistics_cost' => 0,
            'opportunity_cost' => 0,
            'time_value_cost' => 0,
            'operational_cost' => 0,
            'total_costs' => 0,
            'gross_profit' => 0,
            'net_profit' => 0,
            'roi' => 0,
            'gross_margin' => 0,
            'profit_margin' => 0,
            'units_sold' => 0,
            'revenue_per_unit' => 0,
            'cost_breakdown' => [
                'cogs_percentage' => 0,
                'logistics_percentage' => 0,
                'opportunity_percentage' => 0,
                'time_value_percentage' => 0,
                'operational_percentage' => 0
            ]
        ];
    }
}
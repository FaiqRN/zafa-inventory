<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\Pemesanan;
use App\Models\BarangToko;
use App\Exports\PartnerPerformanceExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Validator;

class PartnerPerformanceController extends Controller
{
    /**
     * Display Partner Performance Analytics
     */
    public function index()
    {
        try {
            $breadcrumb = (object)[
                'title' => 'Partner Performance Analytics',
                'list' => ['Home', 'Analytics', 'Partner Performance']
            ];

            $partners = $this->calculatePartnerPerformance();
            $performanceChart = $this->getPartnerPerformanceChart();
            $alerts = $this->getPartnerAlerts();
            $statistics = $this->getOverviewStatistics();

            return view('analytics.partner-performance', [
                'breadcrumb' => $breadcrumb,
                'partners' => $partners,
                'performanceChart' => $performanceChart,
                'alerts' => $alerts,
                'statistics' => $statistics,
                'activemenu' => 'analytics.partner-performance'
            ]);
        } catch (Exception $e) {
            Log::error('Partner performance analytics error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat analisis performa partner.');
        }
    }

    /**
     * Get Partner Performance Data (API)
     */
    public function getData()
    {
        try {
            $partners = $this->calculatePartnerPerformance();
            
            return response()->json([
                'success' => true,
                'data' => $partners,
                'summary' => [
                    'total_partners' => $partners->count(),
                    'avg_performance' => $partners->avg('performance_score'),
                    'grade_distribution' => [
                        'A+' => $partners->where('grade', 'A+')->count(),
                        'A' => $partners->where('grade', 'A')->count(),
                        'B+' => $partners->where('grade', 'B+')->count(),
                        'B' => $partners->where('grade', 'B')->count(),
                        'C+' => $partners->where('grade', 'C+')->count(),
                        'C' => $partners->where('grade', 'C')->count()
                    ]
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Get partner performance data API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Monthly Trends for Charts
     */
    public function getTrends()
    {
        try {
            $chartData = $this->getPartnerPerformanceChart();
            
            return response()->json([
                'success' => true,
                'chart_data' => $chartData,
                'trends' => [
                    'current_month' => end($chartData)['avg_performance'] ?? 0,
                    'previous_month' => count($chartData) > 1 ? $chartData[count($chartData)-2]['avg_performance'] : 0,
                    'improvement' => $this->calculateMonthlyImprovement($chartData)
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Get monthly trends error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat trend data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Partner Statistics
     */
    public function getStatistics()
    {
        try {
            $partners = $this->calculatePartnerPerformance();
            
            if ($partners->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'statistics' => $this->getDefaultStatistics()
                ]);
            }
            
            $statistics = [
                'total_partners' => $partners->count(),
                'active_partnerships' => $partners->where('shipment_count', '>', 0)->count(),
                'total_revenue' => $partners->sum('revenue'),
                'total_shipped' => $partners->sum('total_shipped'),
                'total_sold' => $partners->sum('total_sold'),
                'overall_sell_through' => $partners->sum('total_shipped') > 0 ? 
                    round(($partners->sum('total_sold') / $partners->sum('total_shipped')) * 100, 2) : 0,
                'avg_performance_score' => round($partners->avg('performance_score'), 2),
                'avg_days_to_return' => round($partners->avg('avg_days_to_return'), 0),
                'grade_distribution' => [
                    'A+' => $partners->where('grade', 'A+')->count(),
                    'A' => $partners->where('grade', 'A')->count(),
                    'B+' => $partners->where('grade', 'B+')->count(),
                    'B' => $partners->where('grade', 'B')->count(),
                    'C+' => $partners->where('grade', 'C+')->count(),
                    'C' => $partners->where('grade', 'C')->count()
                ],
                'risk_distribution' => [
                    'High' => $partners->filter(function($p) { return $p['risk_score']['level'] === 'High'; })->count(),
                    'Medium' => $partners->filter(function($p) { return $p['risk_score']['level'] === 'Medium'; })->count(),
                    'Low' => $partners->filter(function($p) { return $p['risk_score']['level'] === 'Low'; })->count()
                ],
                'trend_distribution' => [
                    'improving' => $partners->filter(function($p) { return $p['trend']['trend'] === 'improving'; })->count(),
                    'stable' => $partners->filter(function($p) { return $p['trend']['trend'] === 'stable'; })->count(),
                    'declining' => $partners->filter(function($p) { return $p['trend']['trend'] === 'declining'; })->count()
                ]
            ];
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
        } catch (Exception $e) {
            Log::error('Get partner statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search Partners
     */
    public function searchPartners(Request $request)
    {
        try {
            $searchTerm = $request->get('search', '');
            $grade = $request->get('grade', '');
            $performance = $request->get('performance', '');
            
            $partners = $this->calculatePartnerPerformance();
            
            // Apply filters
            if (!empty($searchTerm)) {
                $partners = $partners->filter(function ($partner) use ($searchTerm) {
                    return stripos($partner['nama_toko'], $searchTerm) !== false ||
                           stripos($partner['toko_id'], $searchTerm) !== false;
                });
            }
            
            if (!empty($grade)) {
                $partners = $partners->where('grade', $grade);
            }
            
            if (!empty($performance)) {
                switch ($performance) {
                    case 'high':
                        $partners = $partners->where('performance_score', '>=', 80);
                        break;
                    case 'medium':
                        $partners = $partners->whereBetween('performance_score', [60, 80]);
                        break;
                    case 'low':
                        $partners = $partners->where('performance_score', '<', 60);
                        break;
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $partners->values(),
                'count' => $partners->count()
            ]);
        } catch (Exception $e) {
            Log::error('Search partners error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Partner History
     */
    public function getPartnerHistory($partnerId)
    {
        try {
            $partner = Toko::find($partnerId);
            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partner tidak ditemukan'
                ], 404);
            }

            $periodStart = Carbon::now()->subMonths(12);
            
            // Monthly performance data
            $monthlyData = [];
            for ($i = 11; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                $shipped = Pengiriman::where('toko_id', $partnerId)
                    ->where('status', 'terkirim')
                    ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                    ->sum('jumlah_kirim') ?? 0;
                
                $returned = Retur::where('toko_id', $partnerId)
                    ->whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                    ->sum('jumlah_retur') ?? 0;
                
                $sold = max(0, $shipped - $returned);
                $sellThroughRate = $shipped > 0 ? ($sold / $shipped) * 100 : 0;
                
                $revenue = Retur::where('toko_id', $partnerId)
                    ->whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                    ->sum('hasil') ?? 0;
                
                $monthlyData[] = [
                    'month' => $monthStart->format('M Y'),
                    'shipped' => $shipped,
                    'sold' => $sold,
                    'returned' => $returned,
                    'sell_through_rate' => round($sellThroughRate, 2),
                    'revenue' => $revenue,
                    'grade' => $this->calculateGrade($sellThroughRate)
                ];
            }

            return response()->json([
                'success' => true,
                'partner' => [
                    'id' => $partner->toko_id,
                    'name' => $partner->nama_toko,
                    'alamat' => $partner->alamat ?? '',
                    'kota' => $partner->kota ?? '',
                    'phone' => $partner->no_telepon ?? ''
                ],
                'history' => $monthlyData,
                'summary' => [
                    'total_shipped' => array_sum(array_column($monthlyData, 'shipped')),
                    'total_sold' => array_sum(array_column($monthlyData, 'sold')),
                    'total_revenue' => array_sum(array_column($monthlyData, 'revenue')),
                    'avg_sell_through' => count($monthlyData) > 0 ? round(array_sum(array_column($monthlyData, 'sell_through_rate')) / count($monthlyData), 2) : 0
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Get partner history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat riwayat partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Partner Alert
     */
    public function sendPartnerAlert(Request $request, $partnerId)
    {
        try {
            $partner = Toko::find($partnerId);
            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partner tidak ditemukan'
                ], 404);
            }

            $alertType = $request->get('alert_type', 'performance');
            $message = $request->get('message', '');
            
            // Get partner performance data
            $performanceData = $this->getPartnerPerformanceData($partnerId);
            
            // Create alert message based on type
            $alertMessage = $this->generateAlertMessage($alertType, $partner, $performanceData, $message);
            
            // Log the alert (in production, integrate with WhatsApp/Email service)
            Log::info('Partner Alert Sent', [
                'partner_id' => $partnerId,
                'partner_name' => $partner->nama_toko,
                'alert_type' => $alertType,
                'message' => $alertMessage,
                'sent_at' => now(),
                'sent_by' => auth()->user()->name ?? 'System'
            ]);
            
            // Simulate WhatsApp/Email sending success
            $this->simulateNotificationSending($partner, $alertMessage);
            
            return response()->json([
                'success' => true,
                'message' => 'Alert berhasil dikirim ke ' . $partner->nama_toko,
                'alert_details' => [
                    'partner' => $partner->nama_toko,
                    'type' => $alertType,
                    'alert_message' => $alertMessage,
                    'sent_at' => now()->format('Y-m-d H:i:s'),
                    'delivery_status' => 'Sent successfully'
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Send partner alert error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Bulk Alerts to Underperforming Partners
     */
    public function sendBulkAlerts()
    {
        try {
            $underperformingPartners = $this->calculatePartnerPerformance()
                ->where('grade', 'C')
                ->take(10);
            
            $sentCount = 0;
            $errors = [];
            
            foreach ($underperformingPartners as $partner) {
                try {
                    $alertMessage = $this->generateBulkAlertMessage($partner);
                    
                    Log::info('Bulk Alert Sent', [
                        'partner_id' => $partner['toko_id'],
                        'partner_name' => $partner['nama_toko'],
                        'reason' => 'Low performance - Grade C',
                        'sell_through_rate' => $partner['sell_through_rate'],
                        'sent_at' => now()
                    ]);
                    
                    $sentCount++;
                } catch (Exception $e) {
                    $errors[] = "Gagal mengirim alert ke {$partner['nama_toko']}: " . $e->getMessage();
                    Log::error('Bulk alert failed for ' . $partner['toko_id'] . ': ' . $e->getMessage());
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil mengirim {$sentCount} alert ke partner dengan performa rendah",
                'details' => [
                    'sent_count' => $sentCount,
                    'total_targeted' => $underperformingPartners->count(),
                    'errors' => $errors
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Send bulk alerts error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim bulk alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Partner Performance to Excel
     */
    public function export()
    {
        try {
            $partners = $this->calculatePartnerPerformance();
            
            if ($partners->isEmpty()) {
                return back()->with('error', 'Tidak ada data partner untuk diekspor.');
            }
            
            $filename = 'partner_performance_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            Log::info('Partner Performance Export Started', [
                'filename' => $filename,
                'partner_count' => $partners->count(),
                'exported_by' => auth()->user()->name ?? 'System'
            ]);
            
            return Excel::download(
                new PartnerPerformanceExport($partners),
                $filename
            );
        } catch (Exception $e) {
            Log::error('Export partner performance error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengexport data partner performance: ' . $e->getMessage());
        }
    }

    /**
     * Generate Performance Report
     */
    public function generateReport(Request $request)
    {
        try {
            $format = $request->get('format', 'summary'); // summary, detailed, executive
            $partners = $this->calculatePartnerPerformance();
            
            $report = [
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'period' => 'Last 6 months',
                'total_partners' => $partners->count(),
                'report_type' => $format
            ];
            
            switch ($format) {
                case 'executive':
                    $report['executive_summary'] = $this->generateExecutiveSummary($partners);
                    break;
                case 'detailed':
                    $report['detailed_analysis'] = $this->generateDetailedAnalysis($partners);
                    break;
                default:
                    $report['summary'] = $this->generateSummaryReport($partners);
            }
            
            return response()->json([
                'success' => true,
                'report' => $report
            ]);
        } catch (Exception $e) {
            Log::error('Generate performance report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // CORE CALCULATION METHODS
    // ========================================

    private function calculatePartnerPerformance()
    {
        try {
            $periodStart = Carbon::now()->subMonths(6);
            $partners = Toko::where('is_active', true)->get();
            
            if ($partners->isEmpty()) {
                return collect();
            }
            
            return $partners->map(function ($toko) use ($periodStart) {
                try {
                    // Get shipment data
                    $totalShipped = Pengiriman::where('toko_id', $toko->toko_id)
                        ->where('status', 'terkirim')
                        ->where('tanggal_pengiriman', '>=', $periodStart)
                        ->sum('jumlah_kirim') ?? 0;
                    
                    // Get return data
                    $totalReturned = Retur::where('toko_id', $toko->toko_id)
                        ->where('tanggal_retur', '>=', $periodStart)
                        ->sum('jumlah_retur') ?? 0;
                    
                    $totalSold = max(0, $totalShipped - $totalReturned);
                    $sellThroughRate = $totalShipped > 0 ? ($totalSold / $totalShipped) * 100 : 0;
                    
                    // Calculate average days to return as INTEGER
                    $avgDaysToReturn = $this->calculateAverageDaysToReturn($toko->toko_id, $periodStart);
                    
                    // Calculate revenue
                    $revenue = Retur::where('toko_id', $toko->toko_id)
                        ->where('tanggal_retur', '>=', $periodStart)
                        ->sum('hasil') ?? 0;
                    
                    // Calculate performance score
                    $performanceScore = $this->calculatePerformanceScore($sellThroughRate, $avgDaysToReturn, $revenue, $totalShipped);
                    
                    // Calculate grade
                    $grade = $this->calculateGrade($sellThroughRate);
                    
                    // Calculate trend
                    $trend = $this->calculatePartnerTrend($toko->toko_id);
                    
                    // Calculate risk score
                    $riskScore = $this->calculatePartnerRiskScore($toko->toko_id, $periodStart);
                    
                    // Calculate consistency score
                    $consistencyScore = $this->calculateConsistencyScore($toko->toko_id, $periodStart);
                    
                    // Get shipment count
                    $shipmentCount = Pengiriman::where('toko_id', $toko->toko_id)
                        ->where('status', 'terkirim')
                        ->where('tanggal_pengiriman', '>=', $periodStart)
                        ->count();
                    
                    return [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko,
                        'total_shipped' => $totalShipped,
                        'total_sold' => $totalSold,
                        'total_returned' => $totalReturned,
                        'sell_through_rate' => round($sellThroughRate, 2),
                        'avg_days_to_return' => round($avgDaysToReturn, 0), // Integer
                        'revenue' => $revenue,
                        'grade' => $grade,
                        'performance_score' => $performanceScore,
                        'trend' => $trend,
                        'risk_score' => $riskScore,
                        'consistency_score' => $consistencyScore,
                        'shipment_count' => $shipmentCount,
                        'avg_shipment_size' => $shipmentCount > 0 ? round($totalShipped / $shipmentCount, 0) : 0
                    ];
                } catch (Exception $e) {
                    Log::warning('Error calculating performance for toko ' . $toko->toko_id . ': ' . $e->getMessage());
                    return $this->getDefaultPartnerPerformance($toko);
                }
            })->sortByDesc('performance_score');
        } catch (Exception $e) {
            Log::error('Calculate partner performance error: ' . $e->getMessage());
            return collect();
        }
    }

    private function calculateAverageDaysToReturn($tokoId, $periodStart)
    {
        try {
            $returns = DB::table('retur')
                ->join('pengiriman', 'retur.pengiriman_id', '=', 'pengiriman.pengiriman_id')
                ->where('retur.toko_id', $tokoId)
                ->where('retur.tanggal_retur', '>=', $periodStart)
                ->whereNotNull('retur.tanggal_retur')
                ->whereNotNull('pengiriman.tanggal_pengiriman')
                ->selectRaw('DATEDIFF(retur.tanggal_retur, pengiriman.tanggal_pengiriman) as days_diff')
                ->get()
                ->pluck('days_diff')
                ->filter(function ($days) {
                    return $days > 0 && $days <= 90; // Filter realistic values
                });
            
            if ($returns->isEmpty()) {
                return 14; // Default 2 weeks as INTEGER
            }
            
            return round($returns->avg(), 0); // Return as integer
        } catch (Exception $e) {
            Log::warning('Calculate average days to return error: ' . $e->getMessage());
            return 14; // Return integer default
        }
    }

    private function calculatePerformanceScore($sellThroughRate, $avgDaysToReturn, $revenue, $totalShipped)
    {
        try {
            // Enhanced scoring algorithm
            $weights = [
                'sell_through' => 0.4,
                'speed' => 0.25,
                'revenue' => 0.25,
                'volume' => 0.1
            ];
            
            // Normalize metrics to 0-100 scale
            $sellThroughScore = min($sellThroughRate, 100);
            
            // Speed score (inverse relationship - faster return is better)
            $speedScore = $avgDaysToReturn > 0 ? max(0, 100 - (($avgDaysToReturn - 7) * 2)) : 50;
            $speedScore = max(0, min(100, $speedScore));
            
            // Revenue score (logarithmic scale for better distribution)
            $revenueScore = $revenue > 0 ? min(100, (log($revenue + 1) / log(20000000)) * 100) : 0;
            
            // Volume score
            $volumeScore = $totalShipped > 0 ? min(100, ($totalShipped / 2000) * 100) : 0;
            
            // Calculate weighted score
            $totalScore = ($sellThroughScore * $weights['sell_through']) +
                         ($speedScore * $weights['speed']) +
                         ($revenueScore * $weights['revenue']) +
                         ($volumeScore * $weights['volume']);
            
            return round($totalScore, 2);
        } catch (Exception $e) {
            Log::warning('Calculate performance score error: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateGrade($sellThroughRate)
    {
        // Fixed grading system based on sell-through rate
        if ($sellThroughRate >= 85) return 'A+';
        if ($sellThroughRate >= 75) return 'A';
        if ($sellThroughRate >= 65) return 'B+';
        if ($sellThroughRate >= 55) return 'B';
        if ($sellThroughRate >= 45) return 'C+';
        return 'C';
    }

    private function calculatePartnerTrend($tokoId)
    {
        try {
            $last3Months = [];
            
            for ($i = 0; $i < 3; $i++) {
                $monthStart = Carbon::now()->subMonths($i + 1)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i + 1)->endOfMonth();
                
                $shipped = Pengiriman::where('toko_id', $tokoId)
                    ->where('status', 'terkirim')
                    ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                    ->sum('jumlah_kirim') ?? 0;
                
                $returned = Retur::where('toko_id', $tokoId)
                    ->whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                    ->sum('jumlah_retur') ?? 0;
                
                $sold = max(0, $shipped - $returned);
                $sellThrough = $shipped > 0 ? ($sold / $shipped) * 100 : 0;
                
                $last3Months[] = $sellThrough;
            }
            
            if (count($last3Months) < 2) {
                return ['trend' => 'insufficient_data', 'direction' => 0, 'data' => $last3Months];
            }
            
            // Calculate trend
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
                'direction' => round($change, 2),
                'data' => array_reverse($last3Months) // Oldest to newest
            ];
        } catch (Exception $e) {
            Log::warning('Calculate partner trend error: ' . $e->getMessage());
            return ['trend' => 'unknown', 'direction' => 0, 'data' => []];
        }
    }

    private function calculatePartnerRiskScore($tokoId, $periodStart)
    {
        try {
            $riskFactors = [];
            $totalRisk = 0;
            
            // Get performance trend
            $trend = $this->calculatePartnerTrend($tokoId);
            if ($trend['trend'] === 'declining') {
                $riskFactors[] = ['factor' => 'declining_performance', 'weight' => 30];
                $totalRisk += 30;
            }
            
            // Check return rate
            $totalShipped = Pengiriman::where('toko_id', $tokoId)
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->sum('jumlah_kirim') ?? 0;
            
            $totalReturned = Retur::where('toko_id', $tokoId)
                ->where('tanggal_retur', '>=', $periodStart)
                ->sum('jumlah_retur') ?? 0;
            
            if ($totalShipped > 0) {
                $returnRate = ($totalReturned / $totalShipped) * 100;
                if ($returnRate > 40) {
                    $riskFactors[] = ['factor' => 'high_return_rate', 'weight' => 25];
                    $totalRisk += 25;
                }
            }
            
            // Check payment speed
            $avgDays = $this->calculateAverageDaysToReturn($tokoId, $periodStart);
            if ($avgDays > 30) {
                $riskFactors[] = ['factor' => 'slow_payment', 'weight' => 20];
                $totalRisk += 20;
            }
            
            // Check revenue per shipment
            $shipmentCount = Pengiriman::where('toko_id', $tokoId)
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->count();
            
            $totalRevenue = Retur::where('toko_id', $tokoId)
                ->where('tanggal_retur', '>=', $periodStart)
                ->sum('hasil') ?? 0;
            
            $avgRevenue = $shipmentCount > 0 ? $totalRevenue / $shipmentCount : 0;
            if ($avgRevenue < 500000) {
                $riskFactors[] = ['factor' => 'low_revenue', 'weight' => 15];
                $totalRisk += 15;
            }
            
            $riskLevel = $totalRisk >= 70 ? 'High' : ($totalRisk >= 40 ? 'Medium' : 'Low');
            
            return [
                'score' => min($totalRisk, 100),
                'level' => $riskLevel,
                'factors' => $riskFactors
            ];
        } catch (Exception $e) {
            Log::warning('Calculate partner risk score error: ' . $e->getMessage());
            return ['score' => 0, 'level' => 'Low', 'factors' => []];
        }
    }

    private function calculateConsistencyScore($tokoId, $periodStart)
    {
        try {
            $monthlyData = [];
            
            for ($i = 0; $i < 6; $i++) {
                $monthStart = Carbon::now()->subMonths($i + 1)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i + 1)->endOfMonth();
                
                $monthlyShipped = Pengiriman::where('toko_id', $tokoId)
                    ->where('status', 'terkirim')
                    ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                    ->sum('jumlah_kirim') ?? 0;
                
                $monthlyReturned = Retur::where('toko_id', $tokoId)
                    ->whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                    ->sum('jumlah_retur') ?? 0;
                
                $monthlySold = max(0, $monthlyShipped - $monthlyReturned);
                $monthlySellThrough = $monthlyShipped > 0 ? ($monthlySold / $monthlyShipped) * 100 : 0;
                
                $monthlyData[] = $monthlySellThrough;
            }
            
            if (empty($monthlyData) || count(array_filter($monthlyData)) < 3) {
                return 0;
            }
            
            // Calculate coefficient of variation
            $mean = array_sum($monthlyData) / count($monthlyData);
            $variance = 0;
            
            foreach ($monthlyData as $value) {
                $variance += pow($value - $mean, 2);
            }
            
            $variance = $variance / count($monthlyData);
            $stdDev = sqrt($variance);
            $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 100;
            
            // Convert to consistency score (higher is better)
            return max(0, round(100 - $cv, 2));
        } catch (Exception $e) {
            Log::warning('Calculate consistency score error: ' . $e->getMessage());
            return 0;
        }
    }

    // ========================================
    // CHART AND VISUALIZATION METHODS
    // ========================================

    private function getPartnerPerformanceChart()
    {
        try {
            $chartData = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                // Calculate average performance for all active partners this month
                $partners = Toko::where('is_active', true)->get();
                $monthlyPerformances = [];
                
                foreach ($partners as $partner) {
                    $shipped = Pengiriman::where('toko_id', $partner->toko_id)
                        ->where('status', 'terkirim')
                        ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                        ->sum('jumlah_kirim') ?? 0;
                    
                    $returned = Retur::where('toko_id', $partner->toko_id)
                        ->whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                        ->sum('jumlah_retur') ?? 0;
                    
                    if ($shipped > 0) {
                        $sold = max(0, $shipped - $returned);
                        $sellThrough = ($sold / $shipped) * 100;
                        $monthlyPerformances[] = $sellThrough;
                    }
                }
                
                $avgPerformance = count($monthlyPerformances) > 0 ? array_sum($monthlyPerformances) / count($monthlyPerformances) : 0;
                
                $chartData[] = [
                    'month' => $monthStart->format('M Y'),
                    'avg_performance' => round($avgPerformance, 2),
                    'active_partners' => count($monthlyPerformances)
                ];
            }
            
            return $chartData;
        } catch (Exception $e) {
            Log::error('Get partner performance chart error: ' . $e->getMessage());
            return [];
        }
    }

    private function getPartnerAlerts()
    {
        try {
            $partners = $this->calculatePartnerPerformance();
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
        } catch (Exception $e) {
            Log::error('Get partner alerts error: ' . $e->getMessage());
            return collect();
        }
    }

    private function getOverviewStatistics()
    {
        try {
            $partners = $this->calculatePartnerPerformance();
            
            return [
                'total_partners' => $partners->count(),
                'avg_performance' => round($partners->avg('performance_score'), 1),
                'top_performers' => $partners->whereIn('grade', ['A+', 'A'])->count(),
                'needs_attention' => $partners->where('grade', 'C')->count(),
                'total_revenue' => $partners->sum('revenue'),
                'avg_sell_through' => round($partners->avg('sell_through_rate'), 1),
                'trend_improving' => $partners->filter(function($p) { return $p['trend']['trend'] === 'improving'; })->count(),
                'high_risk_partners' => $partners->filter(function($p) { return $p['risk_score']['level'] === 'High'; })->count()
            ];
        } catch (Exception $e) {
            Log::error('Get overview statistics error: ' . $e->getMessage());
            return [
                'total_partners' => 0,
                'avg_performance' => 0,
                'top_performers' => 0,
                'needs_attention' => 0,
                'total_revenue' => 0,
                'avg_sell_through' => 0,
                'trend_improving' => 0,
                'high_risk_partners' => 0
            ];
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function getPartnerPerformanceData($partnerId)
    {
        try {
            $partner = $this->calculatePartnerPerformance()->firstWhere('toko_id', $partnerId);
            return $partner ? $partner : [];
        } catch (Exception $e) {
            Log::error('Get partner performance data error: ' . $e->getMessage());
            return [];
        }
    }

    private function generateAlertMessage($alertType, $partner, $performanceData, $customMessage = '')
    {
        $baseMessage = "🚨 ALERT untuk Partner: {$partner->nama_toko}\n";
        $baseMessage .= "ID: {$partner->toko_id}\n\n";
        
        switch ($alertType) {
            case 'performance':
                $baseMessage .= "⚠️ MASALAH PERFORMA:\n";
                $baseMessage .= "• Sell-through rate: {$performanceData['sell_through_rate']}%\n";
                $baseMessage .= "• Grade: {$performanceData['grade']}\n";
                $baseMessage .= "• Avg days to return: {$performanceData['avg_days_to_return']} hari\n\n";
                $baseMessage .= "📋 REKOMENDASI:\n";
                $baseMessage .= "• Review strategi penjualan\n";
                $baseMessage .= "• Kurangi alokasi inventory 30-50%\n";
                $baseMessage .= "• Berikan training tambahan\n";
                break;
            case 'payment':
                $baseMessage .= "💰 MASALAH PEMBAYARAN:\n";
                $baseMessage .= "• Keterlambatan pembayaran: {$performanceData['avg_days_to_return']} hari\n";
                $baseMessage .= "• Revenue: Rp " . number_format($performanceData['revenue']) . "\n\n";
                $baseMessage .= "📋 REKOMENDASI:\n";
                $baseMessage .= "• Tindak lanjut pembayaran segera\n";
                $baseMessage .= "• Pertimbangkan cash on delivery\n";
                $baseMessage .= "• Review terms & conditions\n";
                break;
            case 'trend':
                $baseMessage .= "📉 PENURUNAN PERFORMA:\n";
                $baseMessage .= "• Trend: {$performanceData['trend']['trend']}\n";
                $baseMessage .= "• Perubahan: {$performanceData['trend']['direction']}%\n\n";
                $baseMessage .= "📋 REKOMENDASI:\n";
                $baseMessage .= "• Investigasi penyebab penurunan\n";
                $baseMessage .= "• Jadwalkan meeting review\n";
                $baseMessage .= "• Monitor kompetitor lokal\n";
                break;
            default:
                $baseMessage .= $customMessage;
        }
        
        $baseMessage .= "\n\n📞 Silakan hubungi tim kami untuk diskusi lebih lanjut.\n";
        $baseMessage .= "Terima kasih atas kerjasamanya.\n\n";
        $baseMessage .= "PT Zafa Potato - Team Analytics";
        
        return $baseMessage;
    }

    private function generateBulkAlertMessage($partner)
    {
        $message = "🚨 PERFORMANCE ALERT\n\n";
        $message .= "Partner: {$partner['nama_toko']}\n";
        $message .= "Grade: {$partner['grade']}\n";
        $message .= "Sell-through: {$partner['sell_through_rate']}%\n\n";
        $message .= "Performa partner Anda memerlukan perhatian khusus.\n";
        $message .= "Tim kami akan menghubungi untuk membantu optimasi.\n\n";
        $message .= "Terima kasih,\nPT Zafa Potato";
        
        return $message;
    }

    private function simulateNotificationSending($partner, $message)
    {
        // Simulate notification sending delay
        usleep(100000); // 100ms delay
        
        // Log different notification channels
        if (!empty($partner->no_telepon)) {
            Log::info('WhatsApp notification simulated', [
                'partner_id' => $partner->toko_id,
                'phone' => $partner->no_telepon,
                'message_length' => strlen($message)
            ]);
        }
        
        if (!empty($partner->email)) {
            Log::info('Email notification simulated', [
                'partner_id' => $partner->toko_id,
                'email' => $partner->email,
                'subject' => 'Performance Alert - ' . $partner->nama_toko
            ]);
        }
        
        // Simulate SMS fallback
        Log::info('SMS notification simulated', [
            'partner_id' => $partner->toko_id,
            'message' => 'Performance alert sent to ' . $partner->nama_toko
        ]);
    }

    private function generatePartnerRecommendations($partner)
    {
        $recommendations = [];
        
        if ($partner['sell_through_rate'] < 50) {
            $recommendations[] = "Reduce shipment quantities by 30-50% until performance improves";
            $recommendations[] = "Provide sales training and marketing support";
            $recommendations[] = "Consider different product mix suitable for location";
        }
        
        if ($partner['avg_days_to_return'] > 28) {
            $recommendations[] = "Implement more frequent check-ins and collection schedules";
            $recommendations[] = "Consider payment incentives for faster returns";
            $recommendations[] = "Review and tighten payment terms";
        }
        
        if ($partner['revenue'] < 1000000) {
            $recommendations[] = "Evaluate economic viability of this partnership";
            $recommendations[] = "Consider consolidating with nearby better-performing partners";
            $recommendations[] = "Implement minimum order requirements";
        }
        
        if ($partner['consistency_score'] < 60) {
            $recommendations[] = "Implement standardized ordering processes";
            $recommendations[] = "Provide regular performance feedback";
            $recommendations[] = "Establish clear performance targets";
        }
        
        return $recommendations;
    }

    private function generateTrendRecommendations($partner)
    {
        return [
            "Investigate root causes of performance decline immediately",
            "Schedule urgent partner meeting within 7 days",
            "Review local market conditions and competition",
            "Consider temporary promotional support or incentives",
            "Implement weekly performance monitoring",
            "Provide additional sales training if needed"
        ];
    }

    private function calculateMonthlyImprovement($chartData)
    {
        if (count($chartData) < 2) return 0;
        
        $current = end($chartData)['avg_performance'];
        $previous = $chartData[count($chartData)-2]['avg_performance'];
        
        if ($previous == 0) return 0;
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function getDefaultStatistics()
    {
        return [
            'total_partners' => 0,
            'active_partnerships' => 0,
            'total_revenue' => 0,
            'total_shipped' => 0,
            'total_sold' => 0,
            'overall_sell_through' => 0,
            'avg_performance_score' => 0,
            'avg_days_to_return' => 0,
            'grade_distribution' => [
                'A+' => 0, 'A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0
            ],
            'risk_distribution' => [
                'High' => 0, 'Medium' => 0, 'Low' => 0
            ],
            'trend_distribution' => [
                'improving' => 0, 'stable' => 0, 'declining' => 0
            ]
        ];
    }

    private function getDefaultPartnerPerformance($toko)
    {
        return [
            'toko_id' => $toko->toko_id,
            'nama_toko' => $toko->nama_toko,
            'total_shipped' => 0,
            'total_sold' => 0,
            'total_returned' => 0,
            'sell_through_rate' => 0,
            'avg_days_to_return' => 14, // Integer default
            'revenue' => 0,
            'grade' => 'C',
            'performance_score' => 0,
            'trend' => ['trend' => 'unknown', 'direction' => 0, 'data' => []],
            'risk_score' => ['score' => 0, 'level' => 'Low', 'factors' => []],
            'consistency_score' => 0,
            'shipment_count' => 0,
            'avg_shipment_size' => 0
        ];
    }

    // Report Generation Methods
    private function generateExecutiveSummary($partners)
    {
        $topPerformers = $partners->where('grade', 'A+')->count() + $partners->where('grade', 'A')->count();
        $underperformers = $partners->where('grade', 'C')->count();
        
        return [
            'key_metrics' => [
                'top_performers' => $topPerformers,
                'underperformers' => $underperformers,
                'avg_sell_through' => round($partners->avg('sell_through_rate'), 1) . '%',
                'total_revenue' => 'Rp ' . number_format($partners->sum('revenue'))
            ],
            'recommendations' => [
                'Focus on ' . $topPerformers . ' top-performing partners for expansion',
                'Urgent intervention needed for ' . $underperformers . ' underperforming partners',
                'Overall network health: ' . ($topPerformers > $underperformers ? 'Good' : 'Needs Attention')
            ]
        ];
    }

    private function generateDetailedAnalysis($partners)
    {
        return [
            'performance_breakdown' => [
                'excellent' => $partners->where('performance_score', '>=', 90)->count(),
                'good' => $partners->whereBetween('performance_score', [75, 89])->count(),
                'fair' => $partners->whereBetween('performance_score', [60, 74])->count(),
                'poor' => $partners->where('performance_score', '<', 60)->count()
            ],
            'risk_analysis' => [
                'high_risk_partners' => $partners->filter(function($p) { 
                    return $p['risk_score']['level'] === 'High'; 
                })->pluck('nama_toko')->toArray(),
                'declining_trends' => $partners->filter(function($p) { 
                    return $p['trend']['trend'] === 'declining'; 
                })->pluck('nama_toko')->toArray()
            ],
            'financial_impact' => [
                'total_revenue' => $partners->sum('revenue'),
                'avg_revenue_per_partner' => round($partners->avg('revenue'), 0),
                'revenue_concentration' => $this->calculateRevenueConcentration($partners)
            ]
        ];
    }

    private function generateSummaryReport($partners)
    {
        return [
            'overview' => [
                'total_partners' => $partners->count(),
                'avg_performance' => round($partners->avg('performance_score'), 1),
                'top_grade_count' => $partners->whereIn('grade', ['A+', 'A'])->count(),
                'needs_attention' => $partners->where('grade', 'C')->count()
            ],
            'trends' => [
                'improving' => $partners->filter(function($p) { return $p['trend']['trend'] === 'improving'; })->count(),
                'stable' => $partners->filter(function($p) { return $p['trend']['trend'] === 'stable'; })->count(),
                'declining' => $partners->filter(function($p) { return $p['trend']['trend'] === 'declining'; })->count()
            ]
        ];
    }

    private function calculateRevenueConcentration($partners)
    {
        $totalRevenue = $partners->sum('revenue');
        if ($totalRevenue == 0) return 0;
        
        $top20Percent = $partners->sortByDesc('revenue')->take(ceil($partners->count() * 0.2));
        $top20Revenue = $top20Percent->sum('revenue');
        
        return round(($top20Revenue / $totalRevenue) * 100, 1);
    }

    // Debug Methods (for development)
    public function debugTest()
    {
        try {
            $partners = $this->calculatePartnerPerformance();
            
            return response()->json([
                'success' => true,
                'partner_count' => $partners->count(),
                'sample_partners' => $partners->take(3),
                'grade_distribution' => [
                    'A+' => $partners->where('grade', 'A+')->count(),
                    'A' => $partners->where('grade', 'A')->count(),
                    'B+' => $partners->where('grade', 'B+')->count(),
                    'B' => $partners->where('grade', 'B')->count(),
                    'C+' => $partners->where('grade', 'C+')->count(),
                    'C' => $partners->where('grade', 'C')->count()
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function debugExport()
    {
        return $this->export();
    }

    public function debugAlert($partnerId)
    {
        $request = new Request([
            'alert_type' => 'performance',
            'message' => 'Test alert message'
        ]);
        
        return $this->sendPartnerAlert($request, $partnerId);
    }
}
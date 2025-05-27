<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Analytics Dashboard
     */
    public function index()
    {
        try {
            $filterOptions = $this->getFilterOptions();
            $debugInfo = $this->getDebugInfo();

            return view('analytics.index', [
                'activemenu' => 'analytics',
                'filterOptions' => $filterOptions,
                'debugInfo' => $debugInfo,
                'breadcrumb' => (object) [
                    'title' => 'Analytics CRM Zafa Potato',
                    'list' => ['Home', 'Analytics']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Analytics index error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load analytics');
        }
    }

    /**
     * Get Filter Options
     */
    private function getFilterOptions()
    {
        return [
            'partners' => DB::table('toko')
                ->select('toko_id', 'nama_toko', 'wilayah_kota_kabupaten')
                ->orderBy('nama_toko')
                ->get(),
            
            'regions' => DB::table('toko')
                ->distinct()
                ->whereNotNull('wilayah_kota_kabupaten')
                ->pluck('wilayah_kota_kabupaten')
                ->sort()
                ->values(),
            
            'products' => DB::table('barang')
                ->select('barang_id', 'nama_barang')
                ->orderBy('nama_barang')
                ->get(),
            
            'periods' => [
                ['value' => 1, 'label' => 'Last 1 Month'],
                ['value' => 3, 'label' => 'Last 3 Months'],
                ['value' => 6, 'label' => 'Last 6 Months'],
                ['value' => 12, 'label' => 'Last 12 Months']
            ]
        ];
    }

    /**
     * Debug Info
     */
    private function getDebugInfo()
    {
        try {
            return [
                'toko_count' => DB::table('toko')->count(),
                'retur_count' => DB::table('retur')->count(),
                'recent_retur_count' => DB::table('retur')
                    ->where('tanggal_pengiriman', '>=', Carbon::now()->subMonths(6))
                    ->count()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * ANALITIK 1: Partner Performance
     */
    public function getPartnerPerformance(Request $request)
    {
        try {
            $period = (int) $request->input('period', 6);
            $partners = $request->input('partners', []);
            $regions = $request->input('regions', []);
            $products = $request->input('products', []);
            
            $startDate = Carbon::now()->subMonths($period)->format('Y-m-d');
            
            Log::info('Partner Performance Request:', [
                'period' => $period,
                'start_date' => $startDate,
                'filters' => compact('partners', 'regions', 'products')
            ]);

            // Base query from retur table
            $query = DB::table('retur as r')
                ->join('toko as t', 'r.toko_id', '=', 't.toko_id')
                ->where('r.tanggal_pengiriman', '>=', $startDate)
                ->whereNotNull('r.jumlah_kirim')
                ->where('r.jumlah_kirim', '>', 0);

            // Apply filters
            if (!empty($partners)) {
                $query->whereIn('r.toko_id', $partners);
            }
            if (!empty($regions)) {
                $query->whereIn('t.wilayah_kota_kabupaten', $regions);
            }
            if (!empty($products)) {
                $query->whereIn('r.barang_id', $products);
            }

            // Get partner performance data
            $data = $query->select([
                't.toko_id',
                't.nama_toko',
                't.wilayah_kota_kabupaten',
                
                // Metrics
                DB::raw('COUNT(r.retur_id) as total_cycles'),
                DB::raw('SUM(r.jumlah_kirim) as total_sent'),
                DB::raw('SUM(COALESCE(r.total_terjual, 0)) as total_sold'),
                DB::raw('SUM(COALESCE(r.hasil, 0)) as total_revenue'),
                
                // Performance calculations
                DB::raw('ROUND(AVG(
                    CASE WHEN r.jumlah_kirim > 0 
                    THEN (COALESCE(r.total_terjual, 0) / r.jumlah_kirim) * 100 
                    ELSE 0 END
                ), 1) as performance_score'),
                
                DB::raw('ROUND(AVG(
                    CASE WHEN r.jumlah_kirim > 0 
                    THEN (COALESCE(r.total_terjual, 0) / r.jumlah_kirim) * 100 
                    ELSE 0 END
                ), 1) as sell_through_rate'),
                
                // Grade assignment
                DB::raw('CASE 
                    WHEN AVG(CASE WHEN r.jumlah_kirim > 0 THEN (COALESCE(r.total_terjual, 0) / r.jumlah_kirim) * 100 ELSE 0 END) >= 85 THEN "A+"
                    WHEN AVG(CASE WHEN r.jumlah_kirim > 0 THEN (COALESCE(r.total_terjual, 0) / r.jumlah_kirim) * 100 ELSE 0 END) >= 70 THEN "A"
                    WHEN AVG(CASE WHEN r.jumlah_kirim > 0 THEN (COALESCE(r.total_terjual, 0) / r.jumlah_kirim) * 100 ELSE 0 END) >= 50 THEN "B"
                    WHEN AVG(CASE WHEN r.jumlah_kirim > 0 THEN (COALESCE(r.total_terjual, 0) / r.jumlah_kirim) * 100 ELSE 0 END) >= 30 THEN "C"
                    ELSE "D"
                END as performance_grade')
            ])
            ->groupBy(['t.toko_id', 't.nama_toko', 't.wilayah_kota_kabupaten'])
            ->having('total_cycles', '>=', 1)
            ->orderBy('performance_score', 'DESC')
            ->get();

            // Summary calculations
            $summary = [
                'total_partners' => $data->count(),
                'avg_performance_score' => $data->count() > 0 ? round($data->avg('performance_score'), 1) : 0,
                'top_performers' => $data->where('performance_grade', 'A+')->count(),
                'total_revenue' => $data->sum('total_revenue')
            ];

            // Grade distribution
            $gradeDistribution = [];
            foreach (['A+', 'A', 'B', 'C', 'D'] as $grade) {
                $gradeDistribution[$grade] = $data->where('performance_grade', $grade)->count();
            }

            Log::info('Partner Performance Response:', [
                'partners_found' => $data->count(),
                'summary' => $summary
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $data->toArray(),
                'summary' => $summary,
                'grade_distribution' => $gradeDistribution
            ]);

        } catch (\Exception $e) {
            Log::error('Partner Performance Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch partner performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
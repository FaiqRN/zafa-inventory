<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Toko;
use App\Models\Barang;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\BarangToko;
use App\Models\InventoryRecommendation;
use App\Models\SeasonalAdjustment;
use App\Models\InventoryOptimizationLog;
use App\Exports\InventoryOptimizationExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InventoryOptimizationController extends Controller
{
    /**
     * Display Inventory Optimization Analytics
     */
    public function index()
    {
        try {
            $breadcrumb = (object)[
                'title' => 'Inventory Optimization Analytics',
                'list' => ['Home', 'Analytics', 'Inventory Optimization']
            ];

            // Generate fresh recommendations if needed
            $this->generateInventoryRecommendations();

            // Get recommendations
            $recommendations = $this->getInventoryRecommendations();

            // Get turnover statistics
            $turnoverStats = $this->getInventoryTurnoverStats();

            // Get seasonal adjustments
            $seasonalAdjustments = $this->getSeasonalAdjustments();

            // Calculate summary statistics
            $summaryStats = $this->calculateInventorySummaryStats($recommendations);

            return view('analytics.inventory-optimization', [
                'breadcrumb' => $breadcrumb,
                'recommendations' => $recommendations,
                'turnoverStats' => $turnoverStats,
                'seasonalAdjustments' => $seasonalAdjustments,
                'summaryStats' => $summaryStats,
                'activemenu' => 'analytics.inventory-optimization'
            ]);

        } catch (Exception $e) {
            Log::error('Inventory optimization view error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat halaman optimasi inventory.');
        }
    }

    /**
     * Apply Single Recommendation
     */
    public function applyRecommendation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'recommendation_id' => 'required|string',
                'toko_id' => 'nullable|string',
                'barang_id' => 'nullable|string',
                'recommended_quantity' => 'nullable|integer|min:0',
                'custom_quantity' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get recommendation data
            $recommendationId = $request->recommendation_id;
            $recommendations = $this->getInventoryRecommendations();
            $recommendation = $recommendations->firstWhere('id', $recommendationId);

            if (!$recommendation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rekomendasi tidak ditemukan'
                ], 404);
            }

            $tokoId = $request->toko_id ?? $recommendation->toko_id;
            $barangId = $request->barang_id ?? $recommendation->barang_id;
            $appliedQuantity = $request->custom_quantity ?? $recommendation->recommended_quantity;
            $notes = $request->notes;
            $appliedBy = auth()->user()->name ?? 'System';

            // Log the application
            InventoryOptimizationLog::create([
                'toko_id' => $tokoId,
                'barang_id' => $barangId,
                'action_type' => 'recommendation_applied',
                'old_quantity' => $recommendation->recommended_quantity,
                'new_quantity' => $appliedQuantity,
                'metadata' => [
                    'notes' => $notes,
                    'original_recommendation' => $recommendation->recommended_quantity,
                    'applied_at' => now()
                ],
                'performed_by' => $appliedBy,
                'performed_at' => now()
            ]);

            // Update cache to mark as applied
            $this->updateRecommendationStatusInCache($tokoId, $barangId, 'applied', $appliedQuantity, $appliedBy);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi berhasil diterapkan',
                'details' => [
                    'toko_nama' => $recommendation->toko_nama ?? 'Unknown Store',
                    'barang_nama' => $recommendation->barang_nama ?? 'Unknown Product',
                    'applied_quantity' => $appliedQuantity,
                    'original_recommendation' => $recommendation->recommended_quantity,
                    'applied_by' => $appliedBy,
                    'applied_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Apply inventory recommendation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerapkan rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply Multiple Recommendations
     */
    public function applyAllRecommendations(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'recommendation_ids' => 'required|array',
                'confidence_filter' => 'nullable|in:High,Medium,Low,Very Low'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $recommendationIds = $request->recommendation_ids;
            $confidenceFilter = $request->confidence_filter;
            $appliedBy = auth()->user()->name ?? 'System';
            
            $recommendations = $this->getInventoryRecommendations();
            $selectedRecommendations = $recommendations->whereIn('id', $recommendationIds);
            
            if ($confidenceFilter) {
                $selectedRecommendations = $selectedRecommendations->where('confidence_level', $confidenceFilter);
            }
            
            // Filter only pending recommendations
            $selectedRecommendations = $selectedRecommendations->where('status', 'pending');
            
            $appliedCount = 0;
            $errors = [];

            foreach ($selectedRecommendations as $recommendation) {
                try {
                    // Log the application
                    InventoryOptimizationLog::create([
                        'toko_id' => $recommendation->toko_id,
                        'barang_id' => $recommendation->barang_id,
                        'action_type' => 'recommendation_applied',
                        'old_quantity' => $recommendation->historical_avg_shipped,
                        'new_quantity' => $recommendation->recommended_quantity,
                        'metadata' => [
                            'bulk_application' => true,
                            'confidence_level' => $recommendation->confidence_level,
                            'potential_savings' => $recommendation->potential_savings,
                            'applied_at' => now()
                        ],
                        'performed_by' => $appliedBy,
                        'performed_at' => now()
                    ]);

                    // Update status in cache
                    $this->updateRecommendationStatusInCache(
                        $recommendation->toko_id, 
                        $recommendation->barang_id, 
                        'applied', 
                        $recommendation->recommended_quantity, 
                        $appliedBy
                    );

                    $appliedCount++;
                } catch (Exception $e) {
                    $errors[] = "Error applying recommendation for {$recommendation->toko_nama} - {$recommendation->barang_nama}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil menerapkan {$appliedCount} rekomendasi",
                'details' => [
                    'applied_count' => $appliedCount,
                    'total_selected' => $selectedRecommendations->count(),
                    'errors_count' => count($errors),
                    'errors' => $errors
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Apply all inventory recommendations error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerapkan rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Customize Recommendation
     */
    public function customizeRecommendation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'recommendation_id' => 'required|string',
                'custom_quantity' => 'required|integer|min:0',
                'reason' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $recommendationId = $request->recommendation_id;
            $recommendations = $this->getInventoryRecommendations();
            $recommendation = $recommendations->firstWhere('id', $recommendationId);

            if (!$recommendation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rekomendasi tidak ditemukan'
                ], 404);
            }

            $customQuantity = $request->custom_quantity;
            $reason = $request->reason ?? 'Custom adjustment by user';
            $appliedBy = auth()->user()->name ?? 'System';

            // Log the customization
            InventoryOptimizationLog::create([
                'toko_id' => $recommendation->toko_id,
                'barang_id' => $recommendation->barang_id,
                'action_type' => 'recommendation_customized',
                'new_quantity' => $customQuantity,
                'metadata' => [
                    'reason' => $reason,
                    'customized_at' => now()
                ],
                'performed_by' => $appliedBy,
                'performed_at' => now()
            ]);

            // Update status in cache
            $this->updateRecommendationStatusInCache($recommendation->toko_id, $recommendation->barang_id, 'customized', $customQuantity, $appliedBy, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi berhasil dikustomisasi',
                'details' => [
                    'toko_nama' => $recommendation->toko_nama ?? 'Unknown Store',
                    'barang_nama' => $recommendation->barang_nama ?? 'Unknown Product',
                    'custom_quantity' => $customQuantity,
                    'reason' => $reason,
                    'applied_by' => $appliedBy
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Customize inventory recommendation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengkustomisasi rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh Inventory Recommendations
     */
    public function refreshRecommendations()
    {
        try {
            Cache::forget('inventory_recommendations');
            $generatedCount = $this->generateInventoryRecommendations();
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil menggenerate {$generatedCount} rekomendasi inventory baru",
                'count' => $generatedCount
            ]);

        } catch (Exception $e) {
            Log::error('Refresh inventory recommendations error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal me-refresh rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Seasonal Adjustments Configuration
     */
    public function getSeasonalAdjustments()
    {
        try {
            $currentMonth = Carbon::now()->month;
            $currentMultiplier = $this->getSeasonalMultiplier();
            
            // Get all seasonal adjustments from database
            $adjustments = SeasonalAdjustment::where('is_active', true)
                ->orderBy('month')
                ->get()
                ->keyBy('month');
            
            // Fill missing months with defaults
            $defaultAdjustments = [
                1 => ['multiplier' => 1.1, 'description' => 'Perayaan Tahun Baru - peningkatan konsumsi'],
                2 => ['multiplier' => 0.95, 'description' => 'Periode normalisasi pasca liburan'],
                3 => ['multiplier' => 1.2, 'description' => 'Persiapan Ramadan - stocking merchandise'],
                4 => ['multiplier' => 1.15, 'description' => 'Bulan Ramadan - konsumsi tinggi'],
                5 => ['multiplier' => 1.0, 'description' => 'Kembali ke pola konsumsi normal'],
                6 => ['multiplier' => 1.1, 'description' => 'Awal liburan sekolah - peningkatan aktivitas'],
                7 => ['multiplier' => 1.1, 'description' => 'Liburan sekolah berlanjut'],
                8 => ['multiplier' => 1.0, 'description' => 'Persiapan kembali sekolah'],
                9 => ['multiplier' => 1.0, 'description' => 'Periode konsumsi normal'],
                10 => ['multiplier' => 0.95, 'description' => 'Awal musim hujan - sedikit penurunan'],
                11 => ['multiplier' => 0.95, 'description' => 'Musim hujan - dampak pada distribusi'],
                12 => ['multiplier' => 1.15, 'description' => 'Liburan akhir tahun - peningkatan konsumsi']
            ];
            
            $allAdjustments = collect();
            for ($month = 1; $month <= 12; $month++) {
                if ($adjustments->has($month)) {
                    $allAdjustments->put($month, $adjustments->get($month));
                } else {
                    $allAdjustments->put($month, (object) array_merge(
                        ['month' => $month, 'is_active' => true],
                        $defaultAdjustments[$month]
                    ));
                }
            }
            
            $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
            $nextMultiplier = $allAdjustments->get($nextMonth)->multiplier ?? 1.0;
            
            $currentDescription = $allAdjustments->get($currentMonth)->description ?? 'Standard period';
            $recommendation = $this->getSeasonalRecommendation($currentMultiplier);

            return [
                'all_adjustments' => $allAdjustments,
                'current_month' => $currentMonth,
                'current_multiplier' => $currentMultiplier,
                'current_description' => $currentDescription,
                'current_recommendation' => $recommendation,
                'next_month_multiplier' => $nextMultiplier
            ];

        } catch (Exception $e) {
            Log::error('Get seasonal adjustments error: ' . $e->getMessage());
            return $this->getDefaultSeasonalAdjustments();
        }
    }

    /**
     * Update Seasonal Configuration
     */
    public function updateSeasonalConfiguration(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'adjustments' => 'required|array',
                'adjustments.*.month' => 'required|integer|between:1,12',
                'adjustments.*.multiplier' => 'required|numeric|between:0.5,2.0',
                'adjustments.*.description' => 'required|string|max:255',
                'adjustments.*.is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $adjustmentsData = $request->adjustments;
            $updatedBy = auth()->user()->name ?? 'System';

            // Create seasonal_adjustments table if not exists
            if (!Schema::hasTable('seasonal_adjustments')) {
                Schema::create('seasonal_adjustments', function (Blueprint $table) {
                    $table->id();
                    $table->integer('month')->unique();
                    $table->decimal('multiplier', 8, 2)->default(1.00);
                    $table->string('description');
                    $table->boolean('is_active')->default(true);
                    $table->string('created_by')->nullable();
                    $table->string('updated_by')->nullable();
                    $table->timestamps();
                });
            }

            foreach ($adjustmentsData as $adjustment) {
                SeasonalAdjustment::updateOrCreate(
                    ['month' => $adjustment['month']],
                    [
                        'multiplier' => $adjustment['multiplier'],
                        'description' => $adjustment['description'],
                        'is_active' => $adjustment['is_active'] ?? true,
                        'updated_by' => $updatedBy,
                        'updated_at' => now()
                    ]
                );

                // Log the change
                InventoryOptimizationLog::create([
                    'action_type' => 'seasonal_updated',
                    'metadata' => [
                        'month' => $adjustment['month'],
                        'multiplier' => $adjustment['multiplier'],
                        'description' => $adjustment['description']
                    ],
                    'performed_by' => $updatedBy,
                    'performed_at' => now()
                ]);
            }

            // Clear cache and regenerate recommendations
            Cache::forget('inventory_recommendations');
            $this->generateInventoryRecommendations();

            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi seasonal adjustment berhasil diperbarui dan rekomendasi telah di-regenerate',
                'updated_count' => count($adjustmentsData)
            ]);

        } catch (Exception $e) {
            Log::error('Update seasonal configuration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui konfigurasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Optimization Data (API)
     */
    public function getOptimizationData()
    {
        try {
            $recommendations = $this->getInventoryRecommendations();
            $turnoverData = $this->getInventoryTurnoverStats();
            $seasonalData = $this->getSeasonalAdjustments();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'turnover_data' => $turnoverData,
                    'seasonal_data' => $seasonalData,
                    'efficiency_trends' => $this->getInventoryEfficiencyTrends()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get inventory optimization data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data optimasi inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Recommendation Details
     */
    public function getRecommendationDetails($recommendationId)
    {
        try {
            $recommendations = $this->getInventoryRecommendations();
            $recommendation = $recommendations->firstWhere('id', $recommendationId);
            
            if (!$recommendation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rekomendasi tidak ditemukan'
                ], 404);
            }

            $analysis = $this->generateInventoryRecommendationAnalysis($recommendation);

            return response()->json([
                'success' => true,
                'recommendation' => $recommendation,
                'analysis' => $analysis
            ]);

        } catch (Exception $e) {
            Log::error('Get inventory recommendation details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail rekomendasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Inventory Optimization
     */
    public function export()
    {
        try {
            $recommendations = $this->getInventoryRecommendations();
            
            if ($recommendations->isEmpty()) {
                return back()->with('error', 'Tidak ada data rekomendasi untuk diekspor.');
            }
            
            $filename = 'inventory_optimization_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            Log::info('Inventory Optimization Export Started', [
                'filename' => $filename,
                'recommendation_count' => $recommendations->count(),
                'exported_by' => auth()->user()->name ?? 'System'
            ]);
            
            return Excel::download(
                new InventoryOptimizationExport($recommendations),
                $filename
            );
            
        } catch (Exception $e) {
            Log::error('Export inventory optimization error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengexport data optimasi inventory: ' . $e->getMessage());
        }
    }

    // ========================================
    // CORE CALCULATION METHODS
    // ========================================

    /**
     * Generate inventory recommendations
     */
    public function generateInventoryRecommendations()
    {
        try {
            $generatedCount = 0;
            $periodStart = Carbon::now()->subMonths(6);
            
            // Clear cache first
            Cache::forget('inventory_recommendations');
            
            $barangTokos = BarangToko::with(['toko', 'barang'])
                ->whereHas('toko', function($q) {
                    $q->where('is_active', true);
                })
                ->whereHas('barang', function($q) {
                    $q->where('is_deleted', 0);
                })
                ->get();

            $recommendations = collect();

            foreach ($barangTokos as $barangToko) {
                try {
                    $recommendation = $this->calculateSingleInventoryRecommendation($barangToko, $periodStart);
                    if ($recommendation) {
                        $recommendations->push($recommendation);
                        $generatedCount++;
                    }
                } catch (Exception $e) {
                    Log::warning('Error generating recommendation for ' . $barangToko->toko_id . '-' . $barangToko->barang_id . ': ' . $e->getMessage());
                }
            }

            // Cache the recommendations
            Cache::put('inventory_recommendations', $recommendations, 60); // Cache for 1 hour
            
            Log::info('Inventory recommendations generated', [
                'count' => $generatedCount,
                'generated_by' => auth()->user()->name ?? 'System',
                'generated_at' => now()
            ]);

            return $generatedCount;

        } catch (Exception $e) {
            Log::error('Generate inventory recommendations error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate single inventory recommendation - FIXED TO RETURN OBJECT
     */
    private function calculateSingleInventoryRecommendation($barangToko, $periodStart)
    {
        try {
            // Get historical data
            $historicalData = Pengiriman::where('toko_id', $barangToko->toko_id)
                ->where('barang_id', $barangToko->barang_id)
                ->where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->get();

            if ($historicalData->isEmpty()) {
                return null;
            }

            $avgShipped = $historicalData->avg('jumlah_kirim') ?? 0;
            
            // Calculate average sold
            $avgSold = $historicalData->map(function ($shipment) {
                $returned = Retur::where('pengiriman_id', $shipment->pengiriman_id)
                    ->sum('jumlah_retur') ?? 0;
                return max(0, $shipment->jumlah_kirim - $returned);
            })->avg() ?? 0;

            // Get seasonal multiplier
            $seasonalMultiplier = $this->getSeasonalMultiplier();
            
            // Calculate trend multiplier
            $trendMultiplier = $this->calculateTrendMultiplier($barangToko, $periodStart);
            
            // Calculate recommended quantity
            $recommendedQuantity = round($avgSold * $seasonalMultiplier * $trendMultiplier);
            
            // Calculate confidence level
            $confidenceLevel = $this->calculateConfidenceLevel($historicalData->count());
            
            // Calculate potential savings
            $currentWaste = max($avgShipped - $avgSold, 0);
            $potentialSavings = $currentWaste * 15000; // Average cost per unit
            
            // Calculate improvement percentage
            $improvementPercentage = $avgShipped > 0 ? ($currentWaste / $avgShipped) * 100 : 0;

            // FIXED: Convert array to object using (object) cast
            return (object) [
                'id' => uniqid('rec_'), // Generate unique ID for frontend
                'toko_id' => $barangToko->toko_id,
                'barang_id' => $barangToko->barang_id,
                'toko' => $barangToko->toko,
                'barang' => $barangToko->barang,
                'toko_nama' => $barangToko->toko->nama_toko ?? 'Unknown Store',
                'barang_nama' => $barangToko->barang->nama_barang ?? 'Unknown Product',
                'barang_kode' => $barangToko->barang->barang_kode ?? 'No Code',
                'historical_avg_shipped' => round($avgShipped, 0),
                'historical_avg_sold' => round($avgSold, 0),
                'recommended_quantity' => max(0, $recommendedQuantity),
                'seasonal_multiplier' => $seasonalMultiplier,
                'trend_multiplier' => $trendMultiplier,
                'confidence_level' => $confidenceLevel,
                'potential_savings' => $potentialSavings,
                'improvement_percentage' => round($improvementPercentage, 1),
                'status' => 'pending',
                'confidence_color' => $this->getConfidenceColor($confidenceLevel),
                'status_color' => 'warning',
                'priority' => $this->getPriority($potentialSavings),
                'action_needed' => $this->getActionNeeded($improvementPercentage, $confidenceLevel),
                'applied_quantity' => null,
                'applied_by' => null,
                'applied_at' => null,
                'notes' => null,
                'created_at' => now()
            ];

        } catch (Exception $e) {
            Log::error('Error calculating single recommendation: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get inventory recommendations (cached)
     */
    private function getInventoryRecommendations()
    {
        try {
            $recommendations = Cache::get('inventory_recommendations');
            
            if (!$recommendations || $recommendations->isEmpty()) {
                $this->generateInventoryRecommendations();
                $recommendations = Cache::get('inventory_recommendations', collect());
            }
            
            return $recommendations->sortByDesc('potential_savings');
            
        } catch (Exception $e) {
            Log::error('Get inventory recommendations error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get seasonal multiplier for current month
     */
    private function getSeasonalMultiplier()
    {
        try {
            $currentMonth = Carbon::now()->month;
            
            // Get from database if available
            $seasonalAdjustment = SeasonalAdjustment::where('month', $currentMonth)
                ->where('is_active', true)
                ->first();
            
            if ($seasonalAdjustment) {
                return $seasonalAdjustment->multiplier;
            }
            
            // Default seasonal patterns for Indonesian market
            $defaultSeasonalIndices = [
                1 => 1.1,   // January - New Year celebrations
                2 => 0.95,  // February - Post holiday normalization
                3 => 1.2,   // March - Ramadan preparation period
                4 => 1.15,  // April - Ramadan/Eid festivities
                5 => 1.0,   // May - Back to normal
                6 => 1.1,   // June - School holidays begin
                7 => 1.1,   // July - School holidays continue
                8 => 1.0,   // August - Back to school
                9 => 1.0,   // September - Normal period
                10 => 0.95, // October - Early rainy season
                11 => 0.95, // November - Rainy season
                12 => 1.15  // December - Year end holidays
            ];
            
            return $defaultSeasonalIndices[$currentMonth] ?? 1.0;
            
        } catch (Exception $e) {
            Log::warning('Get seasonal multiplier error: ' . $e->getMessage());
            return 1.0;
        }
    }

    /**
     * Calculate trend multiplier
     */
    private function calculateTrendMultiplier($barangToko, $periodStart)
    {
        try {
            // Recent 3 months
            $recent3Months = Pengiriman::where('toko_id', $barangToko->toko_id)
                ->where('barang_id', $barangToko->barang_id)
                ->where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', Carbon::now()->subMonths(3))
                ->avg('jumlah_kirim') ?? 0;
            
            // Previous 3 months
            $previous3Months = Pengiriman::where('toko_id', $barangToko->toko_id)
                ->where('barang_id', $barangToko->barang_id)
                ->where('status', 'terkirim')
                ->whereBetween('tanggal_pengiriman', [Carbon::now()->subMonths(6), Carbon::now()->subMonths(3)])
                ->avg('jumlah_kirim') ?? 0;
            
            if (!$recent3Months || !$previous3Months) {
                return 1.0;
            }
            
            $trendRatio = $recent3Months / $previous3Months;
            
            // Cap the trend adjustment
            return max(0.7, min(1.4, $trendRatio));
        } catch (Exception $e) {
            return 1.0;
        }
    }

    /**
     * Calculate confidence level
     */
    private function calculateConfidenceLevel($dataPoints)
    {
        if ($dataPoints >= 12) return 'High';
        if ($dataPoints >= 6) return 'Medium';
        if ($dataPoints >= 3) return 'Low';
        return 'Very Low';
    }

    /**
     * Get confidence color
     */
    private function getConfidenceColor($confidenceLevel)
    {
        switch ($confidenceLevel) {
            case 'High': return 'success';
            case 'Medium': return 'primary';
            case 'Low': return 'warning';
            case 'Very Low': return 'danger';
            default: return 'secondary';
        }
    }

    /**
     * Get priority based on potential savings
     */
    private function getPriority($potentialSavings)
    {
        if ($potentialSavings > 1000000) return 'High';
        if ($potentialSavings > 500000) return 'Medium';
        return 'Low';
    }

    /**
     * Get action needed
     */
    private function getActionNeeded($improvementPercentage, $confidenceLevel)
    {
        $actions = [];
        
        if ($improvementPercentage > 30) {
            $actions[] = "Reduce allocation significantly";
        } elseif ($improvementPercentage > 15) {
            $actions[] = "Optimize allocation";
        }
        
        if (in_array($confidenceLevel, ['Low', 'Very Low'])) {
            $actions[] = "Gather more data";
        }
        
        return implode('; ', $actions) ?: 'Apply recommendation';
    }

    /**
     * Get inventory turnover statistics
     */
    private function getInventoryTurnoverStats()
    {
        try {
            $periodStart = Carbon::now()->subMonths(6);
            
            // Calculate current stats
            $totalShipped = Pengiriman::where('status', 'terkirim')
                ->where('tanggal_pengiriman', '>=', $periodStart)
                ->sum('jumlah_kirim') ?? 0;
            
            $totalReturned = Retur::where('tanggal_retur', '>=', $periodStart)
                ->sum('jumlah_retur') ?? 0;
            
            $totalSold = max(0, $totalShipped - $totalReturned);
            
            // Calculate average inventory
            $avgInventory = $totalShipped > 0 ? $totalShipped / 6 : 0; // Monthly average
            
            $currentTurnoverRate = $avgInventory > 0 ? $totalSold / $avgInventory : 0;
            
            // Calculate cash cycle days
            $avgDaysToReturn = DB::table('retur')
                ->join('pengiriman', 'retur.pengiriman_id', '=', 'pengiriman.pengiriman_id')
                ->where('retur.tanggal_retur', '>=', $periodStart)
                ->whereNotNull('retur.tanggal_retur')
                ->whereNotNull('pengiriman.tanggal_pengiriman')
                ->selectRaw('AVG(DATEDIFF(retur.tanggal_retur, pengiriman.tanggal_pengiriman)) as avg_days')
                ->value('avg_days') ?? 21;

            return [
                'current_turnover_rate' => round($currentTurnoverRate, 2),
                'target_turnover_rate' => 4.0,
                'improvement_needed' => round(4.0 - $currentTurnoverRate, 2),
                'cash_cycle_days' => round($avgDaysToReturn, 0),
                'inventory_efficiency' => $currentTurnoverRate > 0 ? min(100, ($currentTurnoverRate / 4.0) * 100) : 0,
                'total_shipped' => $totalShipped,
                'total_sold' => $totalSold,
                'total_returned' => $totalReturned,
                'waste_percentage' => $totalShipped > 0 ? round(($totalReturned / $totalShipped) * 100, 2) : 0
            ];

        } catch (Exception $e) {
            Log::error('Get inventory turnover stats error: ' . $e->getMessage());
            return $this->getDefaultInventoryTurnoverStats();
        }
    }

    /**
     * Calculate inventory summary statistics
     */
    private function calculateInventorySummaryStats($recommendations)
    {
        try {
            return [
                'total_recommendations' => $recommendations->count(),
                'total_potential_savings' => $recommendations->sum('potential_savings'),
                'high_confidence_count' => $recommendations->where('confidence_level', 'High')->count(),
                'avg_waste_reduction' => round($recommendations->avg('improvement_percentage'), 1),
                'total_products' => $recommendations->count(), // Each recommendation is a product-store combination
                'applied_count' => $recommendations->where('status', 'applied')->count(),
                'pending_count' => $recommendations->where('status', 'pending')->count(),
                'customized_count' => $recommendations->where('status', 'customized')->count(),
                'avg_confidence_distribution' => [
                    'High' => $recommendations->where('confidence_level', 'High')->count(),
                    'Medium' => $recommendations->where('confidence_level', 'Medium')->count(),
                    'Low' => $recommendations->where('confidence_level', 'Low')->count(),
                    'Very Low' => $recommendations->where('confidence_level', 'Very Low')->count()
                ]
            ];

        } catch (Exception $e) {
            Log::error('Calculate inventory summary stats error: ' . $e->getMessage());
            return $this->getDefaultInventorySummaryStats();
        }
    }

    /**
     * Get inventory efficiency trends
     */
    private function getInventoryEfficiencyTrends()
    {
        try {
            $trends = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                $monthlyShipped = Pengiriman::where('status', 'terkirim')
                    ->whereBetween('tanggal_pengiriman', [$monthStart, $monthEnd])
                    ->sum('jumlah_kirim') ?? 0;
                
                $monthlyReturned = Retur::whereBetween('tanggal_retur', [$monthStart, $monthEnd])
                    ->sum('jumlah_retur') ?? 0;
                
                $monthlySold = max(0, $monthlyShipped - $monthlyReturned);
                $efficiency = $monthlyShipped > 0 ? ($monthlySold / $monthlyShipped) * 100 : 0;
                
                $trends[] = [
                    'month' => $monthStart->format('M Y'),
                    'shipped' => $monthlyShipped,
                    'sold' => $monthlySold,
                    'returned' => $monthlyReturned,
                    'efficiency' => round($efficiency, 2)
                ];
            }
            
            return $trends;
            
        } catch (Exception $e) {
            Log::error('Get inventory efficiency trends error: ' . $e->getMessage());
            return [];
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Update recommendation status in cache - FIXED FOR OBJECT HANDLING
     */
    private function updateRecommendationStatusInCache($tokoId, $barangId, $status, $appliedQuantity = null, $appliedBy = null, $notes = null)
    {
        try {
            $recommendations = Cache::get('inventory_recommendations', collect());
            
            $recommendations = $recommendations->map(function ($rec) use ($tokoId, $barangId, $status, $appliedQuantity, $appliedBy, $notes) {
                if ($rec->toko_id === $tokoId && $rec->barang_id === $barangId) {
                    $rec->status = $status;
                    $rec->status_color = $status === 'applied' ? 'success' : ($status === 'customized' ? 'info' : 'warning');
                    $rec->applied_quantity = $appliedQuantity;
                    $rec->applied_by = $appliedBy;
                    $rec->applied_at = now();
                    $rec->notes = $notes;
                }
                return $rec;
            });
            
            Cache::put('inventory_recommendations', $recommendations, 60);
        } catch (Exception $e) {
            Log::warning('Update recommendation status in cache error: ' . $e->getMessage());
        }
    }

    /**
     * Generate detailed analysis for inventory recommendation - FIXED FOR OBJECT
     */
    private function generateInventoryRecommendationAnalysis($recommendation)
    {
        $wasteReduction = $recommendation->historical_avg_shipped - $recommendation->recommended_quantity;
        $costPerUnit = 15000; // Average cost per unit
        
        return [
            'current_situation' => [
                'avg_shipped' => $recommendation->historical_avg_shipped,
                'avg_sold' => $recommendation->historical_avg_sold,
                'current_waste' => max(0, $recommendation->historical_avg_shipped - $recommendation->historical_avg_sold),
                'waste_cost' => max(0, ($recommendation->historical_avg_shipped - $recommendation->historical_avg_sold) * $costPerUnit)
            ],
            'optimization_result' => [
                'recommended_quantity' => $recommendation->recommended_quantity,
                'waste_reduction' => max(0, $wasteReduction),
                'cost_savings' => max(0, $wasteReduction * $costPerUnit),
                'efficiency_gain' => $recommendation->improvement_percentage
            ],
            'factors' => [
                'seasonal_impact' => ($recommendation->seasonal_multiplier - 1) * 100,
                'trend_impact' => ($recommendation->trend_multiplier - 1) * 100,
                'confidence_level' => $recommendation->confidence_level,
                'data_quality' => $this->assessInventoryDataQuality($recommendation)
            ],
            'risk_assessment' => $this->assessInventoryRecommendationRisk($recommendation)
        ];
    }

    /**
     * Assess data quality for inventory recommendation - FIXED FOR OBJECT
     */
    private function assessInventoryDataQuality($recommendation)
    {
        $score = 0;
        
        // Base score from confidence level
        switch ($recommendation->confidence_level) {
            case 'High': $score += 40; break;
            case 'Medium': $score += 30; break;
            case 'Low': $score += 20; break;
            case 'Very Low': $score += 10; break;
        }
        
        // Add score based on historical data consistency
        if ($recommendation->historical_avg_shipped > 0) {
            $consistency = abs($recommendation->historical_avg_sold / $recommendation->historical_avg_shipped);
            $score += min(30, $consistency * 30);
        }
        
        // Add score for reasonable seasonal/trend factors
        if ($recommendation->seasonal_multiplier >= 0.8 && $recommendation->seasonal_multiplier <= 1.3) {
            $score += 15;
        }
        
        if ($recommendation->trend_multiplier >= 0.8 && $recommendation->trend_multiplier <= 1.3) {
            $score += 15;
        }
        
        return min(100, $score);
    }

    /**
     * Assess inventory recommendation risk - FIXED FOR OBJECT
     */
    private function assessInventoryRecommendationRisk($recommendation)
    {
        $riskFactors = [];
        $riskScore = 0;
        
        // Low confidence increases risk
        if (in_array($recommendation->confidence_level, ['Low', 'Very Low'])) {
            $riskFactors[] = 'Low data confidence';
            $riskScore += 25;
        }
        
        // Extreme seasonal adjustments increase risk
        if ($recommendation->seasonal_multiplier < 0.7 || $recommendation->seasonal_multiplier > 1.5) {
            $riskFactors[] = 'Extreme seasonal adjustment';
            $riskScore += 20;
        }
        
        // Large quantity changes increase risk
        $quantityChange = abs($recommendation->recommended_quantity - $recommendation->historical_avg_shipped);
        $changePercentage = $recommendation->historical_avg_shipped > 0 ? 
            ($quantityChange / $recommendation->historical_avg_shipped) * 100 : 0;
        
        if ($changePercentage > 50) {
            $riskFactors[] = 'Large quantity change (>' . round($changePercentage) . '%)';
            $riskScore += 30;
        }
        
        // Determine risk level
        $riskLevel = 'Low';
        if ($riskScore >= 50) {
            $riskLevel = 'High';
        } elseif ($riskScore >= 25) {
            $riskLevel = 'Medium';
        }
        
        return [
            'level' => $riskLevel,
            'score' => $riskScore,
            'factors' => $riskFactors,
            'mitigation' => $this->generateInventoryRiskMitigation($riskFactors)
        ];
    }

    /**
     * Generate risk mitigation strategies for inventory
     */
    private function generateInventoryRiskMitigation($riskFactors)
    {
        $mitigations = [];
        
        foreach ($riskFactors as $factor) {
            if (strpos($factor, 'Low data confidence') !== false) {
                $mitigations[] = 'Monitor performance closely and adjust based on actual results';
            }
            
            if (strpos($factor, 'Extreme seasonal') !== false) {
                $mitigations[] = 'Verify seasonal patterns with historical data from previous years';
            }
            
            if (strpos($factor, 'Large quantity change') !== false) {
                $mitigations[] = 'Implement gradual change over 2-3 cycles instead of immediate full adjustment';
            }
        }
        
        return $mitigations;
    }

    /**
     * Get seasonal recommendation based on multiplier
     */
    private function getSeasonalRecommendation($multiplier)
    {
        if ($multiplier > 1.15) {
            return 'Tingkatkan stok signifikan - periode peak demand';
        } elseif ($multiplier > 1.05) {
            return 'Tingkatkan stok moderat - demand di atas normal';
        } elseif ($multiplier < 0.90) {
            return 'Kurangi stok - periode low demand';
        } elseif ($multiplier < 0.95) {
            return 'Sedikit kurangi stok - demand sedikit menurun';
        } else {
            return 'Pertahankan stok normal';
        }
    }

    // Default fallback methods
    private function getDefaultSeasonalAdjustments()
    {
        return [
            'all_adjustments' => collect(),
            'current_month' => Carbon::now()->month,
            'current_multiplier' => 1.00,
            'current_description' => 'Standard period',
            'current_recommendation' => 'Maintain standard allocation',
            'next_month_multiplier' => 1.00
        ];
    }

    private function getDefaultInventoryTurnoverStats()
    {
        return [
            'current_turnover_rate' => 0,
            'target_turnover_rate' => 4.0,
            'improvement_needed' => 4.0,
            'cash_cycle_days' => 21,
            'inventory_efficiency' => 0,
            'total_shipped' => 0,
            'total_sold' => 0,
            'total_returned' => 0,
            'waste_percentage' => 0
        ];
    }

    private function getDefaultInventorySummaryStats()
    {
        return [
            'total_recommendations' => 0,
            'total_potential_savings' => 0,
            'high_confidence_count' => 0,
            'avg_waste_reduction' => 0,
            'total_products' => 0,
            'applied_count' => 0,
            'pending_count' => 0,
            'customized_count' => 0,
            'avg_confidence_distribution' => [
                'High' => 0, 'Medium' => 0, 'Low' => 0, 'Very Low' => 0
            ]
        ];
    }

    // Debug Methods (for development)
    public function debugTest()
    {
        try {
            $recommendations = $this->getInventoryRecommendations();
            
            return response()->json([
                'success' => true,
                'recommendation_count' => $recommendations->count(),
                'sample_recommendations' => $recommendations->take(3),
                'confidence_distribution' => [
                    'High' => $recommendations->where('confidence_level', 'High')->count(),
                    'Medium' => $recommendations->where('confidence_level', 'Medium')->count(),
                    'Low' => $recommendations->where('confidence_level', 'Low')->count(),
                    'Very Low' => $recommendations->where('confidence_level', 'Very Low')->count()
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
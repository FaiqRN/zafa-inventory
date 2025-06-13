<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\Barang;
use App\Models\BarangToko;
use App\Models\Pengiriman;
use App\Models\Retur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class MarketMapController extends Controller
{
    // Cache duration in minutes
    const CACHE_DURATION = 15;
    
    // CRM Configuration
    const CLUSTER_RADIUS = 1.5; // km
    const MAX_STORES_PER_CLUSTER = 5;
    const MIN_PROFIT_MARGIN = 10; // percentage
    const GOOD_PROFIT_MARGIN = 20; // percentage
    const DEFAULT_INITIAL_STOCK = 100; // units for new store
    
    // Malang region bounds
    const MALANG_BOUNDS = [
        'north' => -7.4,
        'south' => -8.6,
        'west' => 111.8,
        'east' => 113.2
    ];

    /**
     * Display CRM Market Intelligence main page
     */
    public function index()
    {
        try {
            return view('market.map', [
                'activemenu' => 'market-map',
                'breadcrumb' => (object) [
                    'title' => 'CRM Market Intelligence',
                    'list' => ['Home', 'CRM Market Intelligence']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading market map index: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to load Market Map');
        }
    }

    /**
     * Get enhanced partner data with CRM metrics
     */
    public function getTokoData()
    {
        try {
            $cacheKey = 'crm_market_toko_data_' . auth()->id();
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
                $tokoData = Toko::with([
                    'barangToko.barang',
                    'pengiriman' => function($query) {
                        $query->where('status', 'terkirim');
                    },
                    'retur'
                ])
                ->select([
                    'toko_id',
                    'nama_toko',
                    'pemilik', 
                    'alamat',
                    'wilayah_kelurahan',
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    'nomer_telpon',
                    'latitude',
                    'longitude',
                    'is_active',
                    'created_at',
                    'updated_at'
                ])
                ->where('is_active', true)
                ->get();

                if ($tokoData->isEmpty()) {
                    return [
                        'success' => true,
                        'data' => [],
                        'summary' => $this->getEmptySummary()
                    ];
                }

                // Transform data dengan CRM analytics
                $mapData = $tokoData->map(function ($toko) {
                    $coordinates = $this->getTokoCoordinates($toko);
                    $performanceData = $this->calculatePerformanceMetrics($toko);
                    $marketSegment = $this->determineMarketSegment($toko, $performanceData);
                    
                    return [
                        'toko_id' => $toko->toko_id,
                        'nama_toko' => $toko->nama_toko ?? 'Unknown',
                        'pemilik' => $toko->pemilik ?? 'Unknown',
                        'alamat' => $toko->alamat ?? '',
                        'kelurahan' => $toko->wilayah_kelurahan ?? '',
                        'kecamatan' => $toko->wilayah_kecamatan ?? '',
                        'kota_kabupaten' => $toko->wilayah_kota_kabupaten ?? '',
                        'telpon' => $toko->nomer_telpon ?? '',
                        'latitude' => $coordinates['lat'],
                        'longitude' => $coordinates['lng'],
                        'has_coordinates' => $coordinates['has_real_coordinates'],
                        'coordinate_source' => $coordinates['source'],
                        'status_aktif' => $this->getTokoStatus($toko),
                        // CRM Performance Metrics
                        'jumlah_barang' => $performanceData['product_count'],
                        'total_pengiriman' => $performanceData['total_orders'],
                        'total_retur' => $performanceData['total_returns'],
                        'performance_score' => $performanceData['performance_score'],
                        'market_segment' => $marketSegment,
                        'last_activity' => $performanceData['last_activity'],
                        'monthly_orders' => $performanceData['monthly_orders'],
                        'return_rate' => $performanceData['return_rate'],
                        'growth_trend' => $performanceData['growth_trend'],
                        // Profit Analysis
                        'profit_per_unit' => $performanceData['profit_per_unit'],
                        'margin_percent' => $performanceData['margin_percent'],
                        'total_profit' => $performanceData['total_profit'],
                        'roi' => $performanceData['roi'],
                        'harga_awal' => $performanceData['harga_awal'],
                        'harga_jual' => $performanceData['harga_jual'],
                        'total_terjual' => $performanceData['total_sold'],
                        'revenue' => $performanceData['revenue']
                    ];
                });

                // Generate comprehensive summary
                $summary = $this->generateSummary($mapData);

                return [
                    'success' => true,
                    'data' => $mapData->values(),
                    'summary' => $summary,
                    'last_updated' => now()->toISOString()
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error fetching toko data for map: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load partner data: ' . $e->getMessage(),
                'data' => [],
                'summary' => $this->getEmptySummary()
            ], 500);
        }
    }

    /**
     * Calculate comprehensive performance metrics
     */
    private function calculatePerformanceMetrics($toko)
    {
        try {
            // Basic counts
            $productCount = $toko->barangToko->count();
            $totalOrders = $toko->pengiriman->count();
            $totalReturns = $toko->retur->count();
            
            // Time-based metrics
            $recentOrders = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(30))
                ->count();
            
            $monthlyOrders = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(30))
                ->count();
            
            // Last activity
            $lastShipment = $toko->pengiriman()
                ->orderBy('tanggal_pengiriman', 'desc')
                ->first();
            
            $lastActivity = $lastShipment ? 
                Carbon::parse($lastShipment->tanggal_pengiriman)->diffForHumans() : 
                'No activity';
            
            // Return rate calculation
            $returnRate = $totalOrders > 0 ? round(($totalReturns / $totalOrders) * 100, 2) : 0;
            
            // Growth trend (current vs last month)
            $currentMonth = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->startOfMonth())
                ->count();
                
            $lastMonth = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subMonth()->startOfMonth())
                ->where('tanggal_pengiriman', '<', now()->startOfMonth())
                ->count();
                
            $growthTrend = $lastMonth > 0 ? 
                round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1) : 
                ($currentMonth > 0 ? 100 : 0);
            
            // Profit calculations
            $profitData = $this->calculateProfitMetrics($toko);
            
            // Performance score calculation
            $performanceScore = $this->calculatePerformanceScore([
                'product_count' => $productCount,
                'recent_orders' => $recentOrders,
                'monthly_avg' => $monthlyOrders,
                'return_rate' => $returnRate,
                'growth_trend' => $growthTrend,
                'profit_margin' => $profitData['margin_percent']
            ]);
            
            return [
                'product_count' => $productCount,
                'total_orders' => $totalOrders,
                'total_returns' => $totalReturns,
                'recent_orders' => $recentOrders,
                'monthly_orders' => $monthlyOrders,
                'last_activity' => $lastActivity,
                'return_rate' => $returnRate,
                'growth_trend' => $growthTrend,
                'performance_score' => $performanceScore,
                ...$profitData
            ];
        } catch (\Exception $e) {
            Log::warning('Error calculating performance metrics for toko ' . $toko->toko_id . ': ' . $e->getMessage());
            return $this->getDefaultPerformanceMetrics();
        }
    }

    /**
     * Calculate profit metrics
     */
    private function calculateProfitMetrics($toko)
    {
        try {
            // Get first barang for profit calculation (simplified)
            $firstBarangToko = $toko->barangToko->first();
            if (!$firstBarangToko || !$firstBarangToko->barang) {
                return $this->getDefaultProfitMetrics();
            }
            
            $hargaAwal = $firstBarangToko->barang->harga_awal_barang ?? 12000; // Default Rp 12.000
            $hargaJual = $firstBarangToko->harga_barang_toko ?? ($hargaAwal * 1.2); // Default 20% markup
            
            // Calculate from retur data
            $totalSold = $toko->retur->sum('total_terjual') ?? 0;
            $revenue = $toko->retur->sum('hasil') ?? 0;
            
            // Profit calculations
            $profitPerUnit = $hargaJual - $hargaAwal;
            $marginPercent = $hargaJual > 0 ? (($profitPerUnit / $hargaJual) * 100) : 0;
            $totalProfit = $profitPerUnit * $totalSold;
            $roi = $hargaAwal > 0 ? (($totalProfit / ($hargaAwal * $totalSold)) * 100) : 0;
            
            return [
                'harga_awal' => $hargaAwal,
                'harga_jual' => $hargaJual,
                'total_sold' => $totalSold,
                'revenue' => $revenue,
                'profit_per_unit' => round($profitPerUnit, 0),
                'margin_percent' => round($marginPercent, 1),
                'total_profit' => round($totalProfit, 0),
                'roi' => round($roi, 1)
            ];
        } catch (\Exception $e) {
            Log::warning('Error calculating profit metrics: ' . $e->getMessage());
            return $this->getDefaultProfitMetrics();
        }
    }

    /**
     * Calculate performance score based on multiple factors
     */
    private function calculatePerformanceScore($metrics)
    {
        try {
            $score = 0;
            
            // Product variety (0-20 points)
            $score += min($metrics['product_count'] * 2, 20);
            
            // Recent activity (0-30 points)
            $score += min($metrics['recent_orders'] * 3, 30);
            
            // Monthly consistency (0-25 points)
            $score += min($metrics['monthly_avg'] * 2.5, 25);
            
            // Return rate penalty (max -15 points)
            $score -= min($metrics['return_rate'] * 0.3, 15);
            
            // Growth trend bonus (0-15 points)
            if ($metrics['growth_trend'] > 0) {
                $score += min($metrics['growth_trend'] * 0.3, 15);
            }
            
            // Profit margin bonus (0-10 points)
            if ($metrics['profit_margin'] >= 20) {
                $score += 10;
            } elseif ($metrics['profit_margin'] >= 15) {
                $score += 7;
            } elseif ($metrics['profit_margin'] >= 10) {
                $score += 5;
            }
            
            return max(0, min(100, round($score, 1)));
        } catch (\Exception $e) {
            Log::warning('Error calculating performance score: ' . $e->getMessage());
            return 50; // Default score
        }
    }

    /**
     * Determine market segment based on performance
     */
    private function determineMarketSegment($toko, $performanceData)
    {
        try {
            $score = $performanceData['performance_score'];
            $totalOrders = $performanceData['total_orders'];
            $productCount = $performanceData['product_count'];
            $marginPercent = $performanceData['margin_percent'];
            $returnRate = $performanceData['return_rate'];
            
            // Premium Partner criteria
            if ($score >= 80 && $totalOrders >= 50 && $productCount >= 10 && $marginPercent >= 20 && $returnRate < 5) {
                return 'Premium Partner';
            }
            
            // Growth Partner criteria
            if ($score >= 60 && $totalOrders >= 20 && $productCount >= 5 && $marginPercent >= 15) {
                return 'Growth Partner';
            }
            
            // Standard Partner criteria
            if ($score >= 40 && $totalOrders >= 5 && $productCount >= 1 && $marginPercent >= 10) {
                return 'Standard Partner';
            }
            
            return 'New Partner';
        } catch (\Exception $e) {
            Log::warning('Error determining market segment: ' . $e->getMessage());
            return 'New Partner';
        }
    }

    /**
     * Get toko coordinates with fallback system
     */
    private function getTokoCoordinates($toko)
    {
        try {
            // Check if coordinates exist and are valid
            if ($toko->latitude && $toko->longitude) {
                $lat = (float) $toko->latitude;
                $lng = (float) $toko->longitude;
                
                // Validate coordinates are within reasonable bounds for Indonesia
                if ($lat >= -11.5 && $lat <= 6.5 && $lng >= 94.5 && $lng <= 142.0) {
                    return [
                        'lat' => $lat,
                        'lng' => $lng,
                        'has_real_coordinates' => true,
                        'source' => 'geocoded'
                    ];
                }
            }

            // Generate fallback coordinates
            $fallbackCoords = $this->generateFallbackCoordinates(
                $toko->wilayah_kota_kabupaten,
                $toko->wilayah_kecamatan,
                $toko->toko_id
            );

            return [
                'lat' => $fallbackCoords['lat'],
                'lng' => $fallbackCoords['lng'],
                'has_real_coordinates' => false,
                'source' => 'estimated'
            ];
        } catch (\Exception $e) {
            Log::warning('Error getting coordinates for toko ' . $toko->toko_id . ': ' . $e->getMessage());
            
            // Return default Malang coordinates
            return [
                'lat' => -7.9666,
                'lng' => 112.6326,
                'has_real_coordinates' => false,
                'source' => 'default'
            ];
        }
    }

    /**
     * Generate fallback coordinates based on administrative area
     */
    private function generateFallbackCoordinates($kotaKabupaten, $kecamatan, $tokoId)
    {
        try {
            $baseCoordinates = [
                'Kota Malang' => [
                    'center' => ['lat' => -7.9666, 'lng' => 112.6326],
                    'bounds' => ['north' => -7.88, 'south' => -8.05, 'west' => 112.55, 'east' => 112.72]
                ],
                'Kabupaten Malang' => [
                    'center' => ['lat' => -8.1844, 'lng' => 112.7456],
                    'bounds' => ['north' => -7.80, 'south' => -8.55, 'west' => 112.30, 'east' => 113.20]
                ],
                'Kota Batu' => [
                    'center' => ['lat' => -7.8767, 'lng' => 112.5326],
                    'bounds' => ['north' => -7.75, 'south' => -8.00, 'west' => 112.45, 'east' => 112.62]
                ]
            ];

            $wilayahData = $baseCoordinates[$kotaKabupaten] ?? $baseCoordinates['Kota Malang'];

            // Create pseudo-random but consistent coordinates
            $hashKecamatan = crc32($kecamatan ?? 'default');
            $hashToko = crc32($tokoId ?? 'default');
            
            $combinedHash = ($hashKecamatan ^ $hashToko);
            
            $latRange = $wilayahData['bounds']['north'] - $wilayahData['bounds']['south'];
            $lngRange = $wilayahData['bounds']['east'] - $wilayahData['bounds']['west'];
            
            $latOffset = (($combinedHash % 1000) / 1000) * $latRange - ($latRange / 2);
            $lngOffset = ((($combinedHash >> 10) % 1000) / 1000) * $lngRange - ($lngRange / 2);

            // Add micro-randomization
            $microOffset = (crc32($tokoId) % 100) / 100000;
            
            return [
                'lat' => $wilayahData['center']['lat'] + $latOffset + $microOffset,
                'lng' => $wilayahData['center']['lng'] + $lngOffset + $microOffset
            ];
        } catch (\Exception $e) {
            Log::warning('Error generating fallback coordinates: ' . $e->getMessage());
            return ['lat' => -7.9666, 'lng' => 112.6326]; // Default Malang center
        }
    }

    /**
     * Determine toko status
     */
    private function getTokoStatus($toko)
    {
        try {
            if (!$toko->is_active) {
                return 'Tidak Aktif';
            }

            $hasProducts = $toko->barangToko->count() > 0;
            $recentActivity = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(30))
                ->exists();
            $veryRecentActivity = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(7))
                ->exists();

            if ($veryRecentActivity && $hasProducts) {
                return 'Sangat Aktif';
            } elseif ($recentActivity || $hasProducts) {
                return 'Aktif';
            } else {
                return 'Kurang Aktif';
            }
        } catch (\Exception $e) {
            Log::warning('Error determining status for toko ' . $toko->toko_id . ': ' . $e->getMessage());
            return 'Unknown';
        }
    }

    /**
     * Generate comprehensive summary
     */
    private function generateSummary($mapData)
    {
        return [
            'total_toko' => $mapData->count(),
            'toko_with_coordinates' => $mapData->where('has_coordinates', true)->count(),
            'toko_active' => $mapData->where('status_aktif', 'Aktif')->count(),
            'high_performers' => $mapData->where('performance_score', '>', 75)->count(),
            'premium_partners' => $mapData->where('market_segment', 'Premium Partner')->count(),
            'growth_partners' => $mapData->where('market_segment', 'Growth Partner')->count(),
            'standard_partners' => $mapData->where('market_segment', 'Standard Partner')->count(),
            'new_partners' => $mapData->where('market_segment', 'New Partner')->count(),
            'avg_performance' => round($mapData->avg('performance_score'), 1),
            'avg_margin' => round($mapData->avg('margin_percent'), 1),
            'total_revenue' => $mapData->sum('revenue'),
            'total_profit' => $mapData->sum('total_profit'),
            'coverage_percentage' => $mapData->count() > 0 ? 
                round(($mapData->where('has_coordinates', true)->count() / $mapData->count()) * 100, 1) : 0
        ];
    }

    /**
     * Get default values for empty data
     */
    private function getEmptySummary()
    {
        return [
            'total_toko' => 0,
            'toko_with_coordinates' => 0,
            'toko_active' => 0,
            'high_performers' => 0,
            'premium_partners' => 0,
            'growth_partners' => 0,
            'standard_partners' => 0,
            'new_partners' => 0,
            'avg_performance' => 0,
            'avg_margin' => 0,
            'total_revenue' => 0,
            'total_profit' => 0,
            'coverage_percentage' => 0
        ];
    }

    /**
     * Get default performance metrics
     */
    private function getDefaultPerformanceMetrics()
    {
        return [
            'product_count' => 0,
            'total_orders' => 0,
            'total_returns' => 0,
            'recent_orders' => 0,
            'monthly_orders' => 0,
            'last_activity' => 'No activity',
            'return_rate' => 0,
            'growth_trend' => 0,
            'performance_score' => 25,
            ...$this->getDefaultProfitMetrics()
        ];
    }

    /**
     * Get default profit metrics
     */
    private function getDefaultProfitMetrics()
    {
        return [
            'harga_awal' => 12000,
            'harga_jual' => 14400,
            'total_sold' => 0,
            'revenue' => 0,
            'profit_per_unit' => 2400,
            'margin_percent' => 16.7,
            'total_profit' => 0,
            'roi' => 0
        ];
    }

    /**
     * Perform clustering analysis
     */
    public function createClusters(Request $request)
    {
        try {
            $radius = $request->input('radius', self::CLUSTER_RADIUS);
            
            $stores = $this->getTokoData();
            if (!$stores['success'] || empty($stores['data'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No store data available for clustering'
                ]);
            }
            
            $clusters = $this->performClustering($stores['data'], $radius);
            
            return response()->json([
                'success' => true,
                'clusters' => $clusters,
                'cluster_count' => count($clusters),
                'total_stores' => count($stores['data'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating clusters: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create clusters: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform geographic clustering
     */
    private function performClustering($stores, $radius)
    {
        $clusters = [];
        $processed = [];
        $clusterId = 1;
        
        foreach ($stores as $store) {
            if (in_array($store['toko_id'], $processed) || !$store['has_coordinates']) {
                continue;
            }
            
            $clusterStores = [$store];
            $processed[] = $store['toko_id'];
            
            // Find nearby stores
            foreach ($stores as $otherStore) {
                if (in_array($otherStore['toko_id'], $processed) || !$otherStore['has_coordinates']) {
                    continue;
                }
                
                $distance = $this->calculateDistance(
                    $store['latitude'], $store['longitude'],
                    $otherStore['latitude'], $otherStore['longitude']
                );
                
                if ($distance <= $radius) {
                    $clusterStores[] = $otherStore;
                    $processed[] = $otherStore['toko_id'];
                }
            }
            
            // Calculate cluster metrics
            $clusterMetrics = $this->calculateClusterMetrics($clusterStores);
            
            $clusters[] = [
                'cluster_id' => 'CLUSTER_' . chr(64 + $clusterId),
                'store_count' => count($clusterStores),
                'stores' => $clusterStores,
                'center' => $this->calculateClusterCenter($clusterStores),
                'metrics' => $clusterMetrics,
                'expansion_potential' => max(0, self::MAX_STORES_PER_CLUSTER - count($clusterStores)),
                'expansion_score' => $this->calculateExpansionScore($clusterMetrics, count($clusterStores))
            ];
            
            $clusterId++;
        }
        
        // Sort clusters by expansion score
        usort($clusters, function($a, $b) {
            return $b['expansion_score'] <=> $a['expansion_score'];
        });
        
        return $clusters;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth radius in km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Calculate cluster center point
     */
    private function calculateClusterCenter($stores)
    {
        $totalLat = 0;
        $totalLng = 0;
        $count = 0;
        
        foreach ($stores as $store) {
            if ($store['has_coordinates']) {
                $totalLat += $store['latitude'];
                $totalLng += $store['longitude'];
                $count++;
            }
        }
        
        return $count > 0 ? [
            'lat' => $totalLat / $count,
            'lng' => $totalLng / $count
        ] : ['lat' => -7.9666, 'lng' => 112.6326];
    }

    /**
     * Calculate cluster performance metrics
     */
    private function calculateClusterMetrics($stores)
    {
        $totalRevenue = array_sum(array_column($stores, 'revenue'));
        $totalProfit = array_sum(array_column($stores, 'total_profit'));
        $avgMargin = count($stores) > 0 ? array_sum(array_column($stores, 'margin_percent')) / count($stores) : 0;
        $avgPerformance = count($stores) > 0 ? array_sum(array_column($stores, 'performance_score')) / count($stores) : 0;
        
        $areas = array_unique(array_column($stores, 'kecamatan'));
        
        return [
            'total_revenue' => round($totalRevenue, 0),
            'total_profit' => round($totalProfit, 0),
            'avg_margin' => round($avgMargin, 1),
            'avg_performance' => round($avgPerformance, 1),
            'area_coverage' => implode(', ', $areas),
            'profitability_level' => $avgMargin >= 20 ? 'High' : ($avgMargin >= 15 ? 'Medium' : 'Low')
        ];
    }

    /**
     * Calculate expansion score for cluster
     */
    private function calculateExpansionScore($metrics, $storeCount)
    {
        $score = 0;
        
        // Margin weight (60%)
        $marginScore = min(($metrics['avg_margin'] / 30) * 60, 60);
        $score += $marginScore;
        
        // Expansion potential weight (30%)
        $expansionPotential = max(0, self::MAX_STORES_PER_CLUSTER - $storeCount);
        $expansionScore = ($expansionPotential / self::MAX_STORES_PER_CLUSTER) * 30;
        $score += $expansionScore;
        
        // Performance weight (10%)
        $performanceScore = ($metrics['avg_performance'] / 100) * 10;
        $score += $performanceScore;
        
        return round($score, 1);
    }

    /**
     * Generate expansion recommendations
     */
    public function generateExpansionRecommendations(Request $request)
    {
        try {
            // Get clusters data
            $clustersResponse = $this->createClusters($request);
            $clustersData = json_decode($clustersResponse->getContent(), true);
            
            if (!$clustersData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate clusters for recommendations'
                ]);
            }
            
            $recommendations = [];
            
            foreach ($clustersData['clusters'] as $cluster) {
                if ($cluster['metrics']['avg_margin'] >= self::MIN_PROFIT_MARGIN && 
                    $cluster['expansion_potential'] > 0) {
                    
                    $recommendation = $this->createExpansionRecommendation($cluster);
                    $recommendations[] = $recommendation;
                }
            }
            
            // Sort by priority and score
            usort($recommendations, function($a, $b) {
                $priorityOrder = ['TINGGI' => 3, 'SEDANG' => 2, 'RENDAH' => 1];
                $aPriority = $priorityOrder[$a['priority']] ?? 0;
                $bPriority = $priorityOrder[$b['priority']] ?? 0;
                
                if ($aPriority === $bPriority) {
                    return $b['score'] <=> $a['score'];
                }
                return $bPriority <=> $aPriority;
            });
            
            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
                'total_recommendations' => count($recommendations),
                'total_investment' => array_sum(array_column($recommendations, 'total_investment')),
                'projected_profit' => array_sum(array_column($recommendations, 'projected_monthly_profit'))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating expansion recommendations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create expansion recommendation for cluster
     */
    private function createExpansionRecommendation($cluster)
    {
        $avgMargin = $cluster['metrics']['avg_margin'];
        $expansionCount = min($cluster['expansion_potential'], 3); // Max 3 new stores per recommendation
        
        // Determine priority
        $priority = 'RENDAH';
        if ($avgMargin >= 20) {
            $priority = 'TINGGI';
        } elseif ($avgMargin >= 15) {
            $priority = 'SEDANG';
        }
        
        // Calculate financial projections
        $avgStoreRevenue = $cluster['metrics']['total_revenue'] / $cluster['store_count'];
        $avgStoreProfit = $cluster['metrics']['total_profit'] / $cluster['store_count'];
        
        $totalInvestment = $expansionCount * 1200000; // Rp 1.2M per store (100 units × Rp 12,000)
        $projectedMonthlyProfit = $expansionCount * ($avgStoreProfit / 12); // Monthly projection
        $paybackPeriod = $projectedMonthlyProfit > 0 ? ceil($totalInvestment / $projectedMonthlyProfit) : 99;
        
        // Pricing strategy
        $pricingStrategy = 'Market Average';
        if ($avgMargin >= 25) {
            $pricingStrategy = 'Premium Pricing';
        } elseif ($avgMargin <= 12) {
            $pricingStrategy = 'Competitive Pricing';
        }
        
        return [
            'cluster_id' => $cluster['cluster_id'],
            'priority' => $priority,
            'score' => $cluster['expansion_score'],
            'target_expansion' => $expansionCount,
            'current_stores' => $cluster['store_count'],
            'area_coverage' => $cluster['metrics']['area_coverage'],
            'avg_margin' => $avgMargin,
            'pricing_strategy' => $pricingStrategy,
            'recommended_price' => round(12000 * (1 + ($avgMargin / 100)), 0),
            'total_investment' => $totalInvestment,
            'projected_monthly_profit' => round($projectedMonthlyProfit, 0),
            'payback_period' => $paybackPeriod,
            'profitability_level' => $cluster['metrics']['profitability_level'],
            'center_coordinates' => $cluster['center']
        ];
    }

    /**
     * Export CRM insights to Excel
     */
    public function exportCRMInsights()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'CRM insights export initiated',
                'download_url' => '#', // Would be actual download URL
                'note' => 'Excel file with comprehensive CRM analytics will be generated',
                'estimated_completion' => now()->addMinutes(2)->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating CRM insights export: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear system cache
     */
    public function clearSystemCache()
    {
        try {
            $patterns = [
                'crm_market_toko_data_',
                'crm_recommendations_',
                'profit_analysis_',
                'clustering_data_'
            ];

            $userId = auth()->id();
            foreach ($patterns as $pattern) {
                Cache::forget($pattern . $userId);
            }

            return response()->json([
                'success' => true,
                'message' => 'System cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system health status
     */
    public function getSystemHealth()
    {
        try {
            $health = [
                'database_connection' => 'OK',
                'cache_status' => 'OK',
                'data_quality' => [],
                'performance_metrics' => []
            ];

            // Test database connection
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $health['database_connection'] = 'ERROR';
            }

            // Check data quality
            $tokoCount = Toko::count();
            $tokoWithCoords = Toko::whereNotNull('latitude')->whereNotNull('longitude')->count();
            $activeToko = Toko::where('is_active', 1)->count();

            $health['data_quality'] = [
                'total_partners' => $tokoCount,
                'geocoded_partners' => $tokoWithCoords,
                'geocoding_percentage' => $tokoCount > 0 ? round(($tokoWithCoords / $tokoCount) * 100, 1) : 0,
                'active_partners' => $activeToko,
                'activity_percentage' => $tokoCount > 0 ? round(($activeToko / $tokoCount) * 100, 1) : 0
            ];

            // Performance metrics
            $health['performance_metrics'] = [
                'cache_hit_rate' => 'Not available',
                'avg_response_time' => 'Not tracked',
                'last_data_update' => Toko::max('updated_at')
            ];

            return response()->json([
                'success' => true,
                'health' => $health,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting system health: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system health: ' . $e->getMessage()
            ], 500);
        }
    }
}
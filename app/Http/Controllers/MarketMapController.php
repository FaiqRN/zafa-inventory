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
    
    // Grid configuration
    const GRID_SIZE = 0.01;
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
     * Get partner data with CRM metrics - Main data source for map
     */
    public function getTokoData()
    {
        try {
            // Cache key for performance
            $cacheKey = 'market_map_toko_data_' . auth()->id();
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
                // Optimized query with eager loading
                $tokoData = Toko::with([
                    'barangToko:toko_id,barang_id',
                    'pengiriman:toko_id,pengiriman_id,tanggal_pengiriman,jumlah_kirim,status',
                    'retur:toko_id,retur_id,jumlah_retur,tanggal_retur'
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
                    'alamat_lengkap_geocoding',
                    'geocoding_quality',
                    'geocoding_score',
                    'created_at',
                    'updated_at'
                ])
                ->get();

                if ($tokoData->isEmpty()) {
                    return [
                        'success' => true,
                        'data' => [],
                        'summary' => [
                            'total_toko' => 0,
                            'toko_with_coordinates' => 0,
                            'toko_active' => 0,
                            'high_performers' => 0
                        ]
                    ];
                }

                // Transform data with CRM analytics
                $mapData = $tokoData->map(function ($toko) {
                    $coordinates = $this->getTokoCoordinates($toko);
                    $performanceScore = $this->calculatePerformanceScore($toko);
                    $marketSegment = $this->determineMarketSegment($toko);
                    
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
                        'jumlah_barang' => $toko->barangToko->count(),
                        'total_pengiriman' => $toko->pengiriman->count(),
                        'total_retur' => $toko->retur->count(),
                        'status_aktif' => $this->getTokoStatus($toko),
                        'has_coordinates' => $coordinates['has_real_coordinates'],
                        'coordinate_source' => $coordinates['source'],
                        'geocoding_quality' => $toko->geocoding_quality ?? 'unknown',
                        'geocoding_score' => $toko->geocoding_score ?? 0,
                        // CRM specific metrics
                        'performance_score' => $performanceScore,
                        'market_segment' => $marketSegment,
                        'last_activity' => $this->getLastActivity($toko),
                        'monthly_orders' => $this->getMonthlyOrderCount($toko),
                        'return_rate' => $this->calculateReturnRate($toko),
                        'growth_trend' => $this->calculateGrowthTrend($toko)
                    ];
                });

                // Generate summary statistics
                $summary = [
                    'total_toko' => $mapData->count(),
                    'toko_with_coordinates' => $mapData->where('has_coordinates', true)->count(),
                    'toko_active' => $mapData->where('status_aktif', 'Aktif')->count(),
                    'high_performers' => $mapData->where('performance_score', '>', 75)->count(),
                    'premium_partners' => $mapData->where('market_segment', 'Premium Partner')->count(),
                    'growth_partners' => $mapData->where('market_segment', 'Growth Partner')->count(),
                    'avg_performance' => round($mapData->avg('performance_score'), 1),
                    'coverage_percentage' => $mapData->count() > 0 ? round(($mapData->where('has_coordinates', true)->count() / $mapData->count()) * 100, 1) : 0
                ];

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
                'summary' => [
                    'total_toko' => 0,
                    'toko_with_coordinates' => 0,
                    'toko_active' => 0,
                    'high_performers' => 0
                ]
            ], 500);
        }
    }

    /**
     * Calculate performance score for CRM analytics
     */
    private function calculatePerformanceScore($toko)
    {
        try {
            $score = 0;
            
            // Base score from product variety (0-20 points)
            $productCount = $toko->barangToko->count();
            $score += min($productCount * 2, 20);
            
            // Score from recent activity (0-40 points)
            $recentShipments = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(30))
                ->count();
            $score += min($recentShipments * 4, 40);
            
            // Score from order frequency (0-25 points)
            $monthlyAvg = $this->getMonthlyOrderCount($toko);
            $score += min($monthlyAvg * 2.5, 25);
            
            // Penalty for returns (max -15 points)
            $returnRate = $this->calculateReturnRate($toko);
            $score -= min($returnRate * 0.3, 15);
            
            // Consistency bonus (0-15 points)
            $consistencyBonus = $this->calculateConsistencyBonus($toko);
            $score += $consistencyBonus;
            
            // Growth trend bonus (0-10 points)
            $growthTrend = $this->calculateGrowthTrend($toko);
            if ($growthTrend > 0) {
                $score += min($growthTrend * 5, 10);
            }
            
            return max(0, min(100, round($score, 1)));
        } catch (\Exception $e) {
            Log::warning('Error calculating performance score for toko ' . $toko->toko_id . ': ' . $e->getMessage());
            return 50; // Default score
        }
    }

    /**
     * Determine market segment for CRM classification
     */
    private function determineMarketSegment($toko)
    {
        try {
            $totalPengiriman = $toko->pengiriman->count();
            $jumlahBarang = $toko->barangToko->count();
            $recentOrders = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(90))
                ->count();
            $returnRate = $this->calculateReturnRate($toko);
            
            // Premium Partner criteria
            if ($totalPengiriman >= 50 && $jumlahBarang >= 10 && $recentOrders >= 15 && $returnRate < 5) {
                return 'Premium Partner';
            }
            
            // Growth Partner criteria
            if ($totalPengiriman >= 20 && $jumlahBarang >= 5 && $recentOrders >= 5) {
                return 'Growth Partner';
            }
            
            // Standard Partner criteria
            if ($totalPengiriman >= 5 && $jumlahBarang >= 1) {
                return 'Standard Partner';
            }
            
            return 'New Partner';
        } catch (\Exception $e) {
            Log::warning('Error determining market segment for toko ' . $toko->toko_id . ': ' . $e->getMessage());
            return 'New Partner';
        }
    }

    /**
     * Calculate consistency bonus for performance scoring
     */
    private function calculateConsistencyBonus($toko)
    {
        try {
            $months = [];
            $pengirimanData = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subMonths(12))
                ->get();
                
            foreach ($pengirimanData as $pengiriman) {
                $month = Carbon::parse($pengiriman->tanggal_pengiriman)->format('Y-m');
                $months[$month] = true;
            }
            
            $consistentMonths = count($months);
            return min($consistentMonths * 1.5, 15);
        } catch (\Exception $e) {
            Log::warning('Error calculating consistency bonus: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get last activity date
     */
    private function getLastActivity($toko)
    {
        try {
            $lastShipment = $toko->pengiriman()
                ->orderBy('tanggal_pengiriman', 'desc')
                ->first();
                
            return $lastShipment ? Carbon::parse($lastShipment->tanggal_pengiriman)->diffForHumans() : 'No activity';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get monthly order count
     */
    private function getMonthlyOrderCount($toko)
    {
        try {
            return $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(30))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate return rate
     */
    private function calculateReturnRate($toko)
    {
        try {
            $totalOrders = $toko->pengiriman->count();
            $totalReturns = $toko->retur->count();
            
            return $totalOrders > 0 ? round(($totalReturns / $totalOrders) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate growth trend
     */
    private function calculateGrowthTrend($toko)
    {
        try {
            $currentMonth = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->startOfMonth())
                ->count();
                
            $lastMonth = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subMonth()->startOfMonth())
                ->where('tanggal_pengiriman', '<', now()->startOfMonth())
                ->count();
                
            if ($lastMonth > 0) {
                return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
            }
            
            return $currentMonth > 0 ? 100 : 0;
        } catch (\Exception $e) {
            return 0;
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
                    // Check if coordinates are within East Java region
                    $inJatim = ($lat >= -8.8 && $lat <= -7.2 && $lng >= 111.0 && $lng <= 114.5);
                    
                    return [
                        'lat' => $lat,
                        'lng' => $lng,
                        'has_real_coordinates' => true,
                        'source' => 'geocoded',
                        'accuracy' => $inJatim ? 'high' : 'medium'
                    ];
                }
            }

            // Generate fallback coordinates
            $fallbackCoords = $this->generateFallbackCoordinates(
                $toko->wilayah_kota_kabupaten,
                $toko->wilayah_kecamatan,
                $toko->wilayah_kelurahan,
                $toko->toko_id
            );

            return [
                'lat' => $fallbackCoords['lat'],
                'lng' => $fallbackCoords['lng'],
                'has_real_coordinates' => false,
                'source' => 'estimated',
                'accuracy' => 'low'
            ];
        } catch (\Exception $e) {
            Log::warning('Error getting coordinates for toko ' . $toko->toko_id . ': ' . $e->getMessage());
            
            // Return default Malang coordinates
            return [
                'lat' => -7.9666,
                'lng' => 112.6326,
                'has_real_coordinates' => false,
                'source' => 'default',
                'accuracy' => 'very_low'
            ];
        }
    }

    /**
     * Determine toko status based on various criteria
     */
    private function getTokoStatus($toko)
    {
        try {
            if (!$toko->is_active) {
                return 'Tidak Aktif';
            }

            $hasBarang = $toko->barangToko->count() > 0;
            $recentShipment = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(30))
                ->exists();
            $veryRecentShipment = $toko->pengiriman()
                ->where('tanggal_pengiriman', '>=', now()->subDays(7))
                ->exists();

            if ($veryRecentShipment && $hasBarang) {
                return 'Sangat Aktif';
            } elseif ($recentShipment || $hasBarang) {
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
     * Generate fallback coordinates with improved distribution
     */
    private function generateFallbackCoordinates($kotaKabupaten, $kecamatan, $kelurahan, $tokoId)
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
            $hashKelurahan = crc32($kelurahan ?? 'default');
            $hashToko = crc32($tokoId ?? 'default');
            
            // Combine hashes for better distribution
            $combinedHash = ($hashKecamatan ^ $hashKelurahan ^ $hashToko);
            
            $latRange = $wilayahData['bounds']['north'] - $wilayahData['bounds']['south'];
            $lngRange = $wilayahData['bounds']['east'] - $wilayahData['bounds']['west'];
            
            $latOffset = (($combinedHash % 1000) / 1000) * $latRange - ($latRange / 2);
            $lngOffset = ((($combinedHash >> 10) % 1000) / 1000) * $lngRange - ($lngRange / 2);

            // Add micro-randomization to prevent exact overlaps
            $microOffset = (crc32($tokoId . $kelurahan) % 100) / 100000;
            
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
     * Get wilayah statistics with caching
     */
    public function getWilayahStatistics()
    {
        try {
            $cacheKey = 'wilayah_statistics_' . auth()->id();
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
                $statistikKecamatan = DB::table('toko')
                    ->select([
                        'wilayah_kecamatan',
                        'wilayah_kota_kabupaten',
                        DB::raw('COUNT(*) as jumlah_toko'),
                        DB::raw('COUNT(CASE WHEN is_active = 1 THEN 1 END) as toko_aktif'),
                        DB::raw('COUNT(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 END) as toko_with_coordinates'),
                        DB::raw('AVG(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN geocoding_score END) as avg_geocoding_quality')
                    ])
                    ->whereNotNull('wilayah_kecamatan')
                    ->groupBy('wilayah_kecamatan', 'wilayah_kota_kabupaten')
                    ->orderBy('jumlah_toko', 'desc')
                    ->get();

                $statistikKelurahan = DB::table('toko')
                    ->select([
                        'wilayah_kelurahan',
                        'wilayah_kecamatan', 
                        'wilayah_kota_kabupaten',
                        DB::raw('COUNT(*) as jumlah_toko'),
                        DB::raw('COUNT(CASE WHEN is_active = 1 THEN 1 END) as toko_aktif')
                    ])
                    ->whereNotNull('wilayah_kelurahan')
                    ->groupBy('wilayah_kelurahan', 'wilayah_kecamatan', 'wilayah_kota_kabupaten')
                    ->orderBy('jumlah_toko', 'desc')
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'kecamatan' => $statistikKecamatan,
                        'kelurahan' => $statistikKelurahan,
                        'summary' => [
                            'total_kecamatan' => $statistikKecamatan->count(),
                            'total_kelurahan' => $statistikKelurahan->count(),
                            'avg_toko_per_kecamatan' => round($statistikKecamatan->avg('jumlah_toko'), 2),
                            'best_coverage_kecamatan' => $statistikKecamatan->first()?->wilayah_kecamatan
                        ]
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error fetching wilayah statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load territory statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get partner details with comprehensive data
     */
    public function getPartnerDetails($partnerId)
    {
        return $this->getTokoBarang($partnerId);
    }

    /**
     * Get comprehensive toko data including products and transactions
     */
    public function getTokoBarang($tokoId)
    {
        try {
            $validator = Validator::make(['toko_id' => $tokoId], [
                'toko_id' => 'required|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid partner ID format'
                ], 400);
            }

            // Get comprehensive toko data with products
            $barangToko = DB::table('barang_toko')
                ->join('barang', 'barang_toko.barang_id', '=', 'barang.barang_id')
                ->join('toko', 'barang_toko.toko_id', '=', 'toko.toko_id')
                ->where('barang_toko.toko_id', $tokoId)
                ->select([
                    'barang.barang_id',
                    'barang.nama_barang',
                    'barang.barang_kode',
                    'barang.harga_awal_barang',
                    'barang_toko.harga_barang_toko',
                    'barang.satuan',
                    'barang.keterangan',
                    'toko.nama_toko',
                    'toko.pemilik',
                    'toko.wilayah_kecamatan',
                    'toko.wilayah_kota_kabupaten',
                    DB::raw('(barang_toko.harga_barang_toko - barang.harga_awal_barang) as margin'),
                    DB::raw('CASE WHEN barang.harga_awal_barang > 0 THEN 
                        ((barang_toko.harga_barang_toko - barang.harga_awal_barang) / barang.harga_awal_barang) * 100 
                        ELSE 0 END as margin_percentage')
                ])
                ->get();

            if ($barangToko->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Partner not found or has no products'
                ], 404);
            }

            // Get shipment statistics
            $statistikPengiriman = DB::table('pengiriman')
                ->where('toko_id', $tokoId)
                ->select([
                    DB::raw('COUNT(*) as total_pengiriman'),
                    DB::raw('SUM(jumlah_kirim) as total_barang_dikirim'),
                    DB::raw('MAX(tanggal_pengiriman) as pengiriman_terakhir'),
                    DB::raw('MIN(tanggal_pengiriman) as pengiriman_pertama'),
                    DB::raw('AVG(jumlah_kirim) as rata_rata_per_pengiriman'),
                    DB::raw('COUNT(CASE WHEN tanggal_pengiriman >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as pengiriman_30_hari'),
                    DB::raw('COUNT(CASE WHEN status = "terkirim" THEN 1 END) as pengiriman_sukses')
                ])
                ->first();

            // Get return statistics
            $statistikRetur = DB::table('retur')
                ->where('toko_id', $tokoId)
                ->select([
                    DB::raw('COUNT(*) as total_retur'),
                    DB::raw('SUM(jumlah_retur) as total_barang_retur'),
                    DB::raw('SUM(hasil) as total_hasil_retur'),
                    DB::raw('MAX(tanggal_retur) as retur_terakhir'),
                    DB::raw('AVG(jumlah_retur) as rata_rata_per_retur')
                ])
                ->first();

            // Get monthly performance trend
            $monthlyTrend = DB::table('pengiriman')
                ->where('toko_id', $tokoId)
                ->where('tanggal_pengiriman', '>=', now()->subMonths(6))
                ->select([
                    DB::raw('DATE_FORMAT(tanggal_pengiriman, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(jumlah_kirim) as volume')
                ])
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Calculate performance metrics
            $performanceMetrics = [
                'performance_score' => 0,
                'return_rate' => 0,
                'order_frequency' => 0,
                'revenue_trend' => 'stable'
            ];

            if ($statistikPengiriman->total_pengiriman > 0) {
                $performanceMetrics['return_rate'] = round(
                    ($statistikRetur->total_retur / $statistikPengiriman->total_pengiriman) * 100, 2
                );
                
                $daysSinceFirst = $statistikPengiriman->pengiriman_pertama ? 
                    Carbon::parse($statistikPengiriman->pengiriman_pertama)->diffInDays(now()) : 1;
                    
                $performanceMetrics['order_frequency'] = round(
                    $statistikPengiriman->total_pengiriman / max($daysSinceFirst / 30, 1), 2
                );
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'toko_info' => $barangToko->first(),
                    'barang' => $barangToko,
                    'statistik_pengiriman' => $statistikPengiriman,
                    'statistik_retur' => $statistikRetur,
                    'monthly_trend' => $monthlyTrend,
                    'performance_metrics' => $performanceMetrics
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching partner details: ' . $e->getMessage(), [
                'toko_id' => $tokoId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load partner details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CRM recommendations with enhanced analytics
     */
    public function getRecommendations()
    {
        try {
            $cacheKey = 'crm_recommendations_' . auth()->id();
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
                $marketOpportunities = $this->getMarketOpportunities();
                $partnerInsights = $this->getPartnerPerformanceInsights();
                $productAnalysis = $this->getProductPerformanceAnalysis();
                $expansionOpportunities = $this->getExpansionOpportunities();
                $territoryInsights = $this->getTerritoryInsights();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'market_opportunities' => $marketOpportunities,
                        'partner_insights' => $partnerInsights,
                        'product_analysis' => $productAnalysis,
                        'expansion_opportunities' => $expansionOpportunities,
                        'territory_insights' => $territoryInsights
                    ],
                    'generated_at' => now()->toISOString()
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error generating CRM recommendations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhanced market opportunities analysis
     */
    private function getMarketOpportunities()
    {
        try {
            return DB::table('toko as t')
                ->leftJoin('pengiriman as p', 't.toko_id', '=', 'p.toko_id')
                ->select([
                    't.wilayah_kecamatan',
                    't.wilayah_kota_kabupaten',
                    DB::raw('COUNT(DISTINCT t.toko_id) as partner_count'),
                    DB::raw('AVG(DATEDIFF(NOW(), p.tanggal_pengiriman)) as avg_days_since_last_order'),
                    DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                    DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_orders'),
                    DB::raw('CASE 
                        WHEN AVG(DATEDIFF(NOW(), p.tanggal_pengiriman)) > 60 THEN "High Risk"
                        WHEN AVG(DATEDIFF(NOW(), p.tanggal_pengiriman)) > 30 THEN "Medium Risk"
                        ELSE "Active"
                    END as risk_level')
                ])
                ->where('t.is_active', 1)
                ->groupBy('t.wilayah_kecamatan', 't.wilayah_kota_kabupaten')
                ->having('partner_count', '>', 0)
                ->orderBy('avg_days_since_last_order', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Error getting market opportunities: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Enhanced partner performance insights
     */
    private function getPartnerPerformanceInsights()
    {
        try {
            return DB::table('toko as t')
                ->leftJoin('pengiriman as p', 't.toko_id', '=', 'p.toko_id')
                ->leftJoin('retur as r', 't.toko_id', '=', 'r.toko_id')
                ->leftJoin('barang_toko as bt', 't.toko_id', '=', 'bt.toko_id')
                ->select([
                    't.toko_id',
                    't.nama_toko',
                    't.wilayah_kecamatan',
                    't.wilayah_kota_kabupaten',
                    DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_orders'),
                    DB::raw('COUNT(DISTINCT r.retur_id) as total_returns'),
                    DB::raw('COUNT(DISTINCT bt.barang_id) as product_variety'),
                    DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                    DB::raw('CASE 
                        WHEN COUNT(DISTINCT p.pengiriman_id) > 0 
                        THEN ROUND((COUNT(DISTINCT r.retur_id) / COUNT(DISTINCT p.pengiriman_id)) * 100, 2)
                        ELSE 0 
                    END as return_rate'),
                    DB::raw('MAX(p.tanggal_pengiriman) as last_order_date'),
                    DB::raw('DATEDIFF(NOW(), MAX(p.tanggal_pengiriman)) as days_since_last_order')
                ])
                ->where('t.is_active', 1)
                ->groupBy('t.toko_id', 't.nama_toko', 't.wilayah_kecamatan', 't.wilayah_kota_kabupaten')
                ->having('total_orders', '>', 0)
                ->orderBy('return_rate', 'asc')
                ->orderBy('total_volume', 'desc')
                ->limit(15)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Error getting partner performance insights: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Enhanced product performance analysis
     */
    private function getProductPerformanceAnalysis()
    {
        try {
            return DB::table('barang as b')
                ->join('pengiriman as p', 'b.barang_id', '=', 'p.barang_id')
                ->join('barang_toko as bt', function($join) {
                    $join->on('b.barang_id', '=', 'bt.barang_id')
                         ->on('p.toko_id', '=', 'bt.toko_id');
                })
                ->leftJoin('retur as r', function($join) {
                    $join->on('b.barang_id', '=', 'r.barang_id')
                         ->on('p.toko_id', '=', 'r.toko_id');
                })
                ->select([
                    'b.barang_id',
                    'b.nama_barang',
                    'b.barang_kode',
                    DB::raw('COUNT(DISTINCT p.toko_id) as partner_count'),
                    DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                    DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_orders'),
                    DB::raw('AVG(bt.harga_barang_toko - b.harga_awal_barang) as avg_margin'),
                    DB::raw('CASE WHEN AVG(b.harga_awal_barang) > 0 THEN
                        ROUND((AVG(bt.harga_barang_toko - b.harga_awal_barang) / AVG(b.harga_awal_barang)) * 100, 2)
                        ELSE 0 END as margin_percentage'),
                    DB::raw('COUNT(DISTINCT r.retur_id) as return_count'),
                    DB::raw('CASE WHEN SUM(p.jumlah_kirim) > 0 THEN
                        ROUND((COUNT(DISTINCT r.retur_id) / SUM(p.jumlah_kirim)) * 100, 2)
                        ELSE 0 END as return_rate')
                ])
                ->where('p.status', 'terkirim')
                ->groupBy('b.barang_id', 'b.nama_barang', 'b.barang_kode')
                ->having('total_volume', '>', 0)
                ->orderBy('margin_percentage', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Error getting product performance analysis: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Enhanced expansion opportunities
     */
    private function getExpansionOpportunities()
    {
        try {
            return DB::table('toko')
                ->select([
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    DB::raw('COUNT(*) as current_partners'),
                    DB::raw('COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_partners'),
                    DB::raw('CASE 
                        WHEN COUNT(*) < 2 THEN "High Opportunity"
                        WHEN COUNT(*) < 5 THEN "Medium Opportunity"
                        WHEN COUNT(*) < 8 THEN "Low Opportunity"
                        ELSE "Saturated"
                    END as opportunity_level'),
                    DB::raw('CASE 
                        WHEN COUNT(*) < 2 THEN GREATEST(3 - COUNT(*), 0)
                        WHEN COUNT(*) < 5 THEN GREATEST(2 - COUNT(*), 0)
                        ELSE 0
                    END as recommended_additions'),
                    DB::raw('ROUND(COUNT(CASE WHEN is_active = 1 THEN 1 END) / COUNT(*) * 100, 1) as activity_rate')
                ])
                ->whereNotNull('wilayah_kecamatan')
                ->groupBy('wilayah_kecamatan', 'wilayah_kota_kabupaten')
                ->having('current_partners', '<', 10)
                ->orderBy('recommended_additions', 'desc')
                ->orderBy('activity_rate', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::warning('Error getting expansion opportunities: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Territory insights analysis
     */
    private function getTerritoryInsights()
    {
        try {
            return DB::table('toko as t')
                ->leftJoin('pengiriman as p', 't.toko_id', '=', 'p.toko_id')
                ->select([
                    't.wilayah_kota_kabupaten as territory',
                    DB::raw('COUNT(DISTINCT t.toko_id) as total_partners'),
                    DB::raw('COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN t.toko_id END) as active_partners'),
                    DB::raw('COUNT(DISTINCT t.wilayah_kecamatan) as kecamatan_coverage'),
                    DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                    DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_transactions'),
                    DB::raw('AVG(p.jumlah_kirim) as avg_order_size'),
                    DB::raw('CASE 
                        WHEN COUNT(DISTINCT t.toko_id) > 0 
                        THEN ROUND(COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN t.toko_id END) / COUNT(DISTINCT t.toko_id) * 100, 1)
                        ELSE 0 END as activity_percentage')
                ])
                ->groupBy('t.wilayah_kota_kabupaten')
                ->orderBy('total_volume', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::warning('Error getting territory insights: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Enhanced price recommendations with market analysis
     */
    public function getPriceRecommendations(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'wilayah' => 'nullable|string|max:100',
                'barang_id' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request parameters',
                    'errors' => $validator->errors()
                ], 400);
            }

            $wilayah = $request->get('wilayah');
            $barangId = $request->get('barang_id');
            
            $cacheKey = 'price_recommendations_' . md5($wilayah . '_' . $barangId) . '_' . auth()->id();
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($wilayah, $barangId) {
                $query = DB::table('barang_toko')
                    ->join('barang', 'barang_toko.barang_id', '=', 'barang.barang_id')
                    ->join('toko', 'barang_toko.toko_id', '=', 'toko.toko_id')
                    ->leftJoin('pengiriman as p', function($join) {
                        $join->on('barang_toko.barang_id', '=', 'p.barang_id')
                             ->on('barang_toko.toko_id', '=', 'p.toko_id');
                    })
                    ->select([
                        'barang.nama_barang',
                        'barang.barang_id',
                        'barang.barang_kode',
                        'toko.wilayah_kecamatan',
                        'toko.wilayah_kota_kabupaten',
                        DB::raw('AVG(barang_toko.harga_barang_toko) as recommended_price'),
                        DB::raw('AVG(barang.harga_awal_barang) as cost_price'),
                        DB::raw('MIN(barang_toko.harga_barang_toko) as min_market_price'),
                        DB::raw('MAX(barang_toko.harga_barang_toko) as max_market_price'),
                        DB::raw('COUNT(DISTINCT toko.toko_id) as sample_size'),
                        DB::raw('STDDEV(barang_toko.harga_barang_toko) as price_variance'),
                        DB::raw('AVG(barang_toko.harga_barang_toko - barang.harga_awal_barang) as avg_margin'),
                        DB::raw('CASE WHEN AVG(barang.harga_awal_barang) > 0 THEN
                            ROUND((AVG(barang_toko.harga_barang_toko - barang.harga_awal_barang) / AVG(barang.harga_awal_barang)) * 100, 2)
                            ELSE 0 END as margin_percentage'),
                        DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_sales'),
                        DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                        DB::raw('MAX(p.tanggal_pengiriman) as last_sale_date')
                    ])
                    ->where('toko.is_active', 1);
                    
                if ($wilayah) {
                    $query->where('toko.wilayah_kecamatan', $wilayah);
                }
                
                if ($barangId) {
                    $query->where('barang.barang_id', $barangId);
                }
                
                $recommendations = $query
                    ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.barang_kode', 'toko.wilayah_kecamatan', 'toko.wilayah_kota_kabupaten')
                    ->having('sample_size', '>=', 2)
                    ->orderBy('margin_percentage', 'desc')
                    ->get()
                    ->map(function($item) {
                        // Determine confidence level
                        $confidence = 'Low';
                        if ($item->sample_size >= 5 && $item->total_sales >= 10) $confidence = 'High';
                        elseif ($item->sample_size >= 3 && $item->total_sales >= 5) $confidence = 'Medium';
                        
                        // Determine pricing strategy
                        $strategy = 'Market Average';
                        if ($item->margin_percentage > 50) $strategy = 'Premium Pricing';
                        elseif ($item->margin_percentage < 20) $strategy = 'Competitive Pricing';
                        
                        // Market dynamics
                        $marketDynamics = 'Stable';
                        if ($item->price_variance > ($item->recommended_price * 0.2)) {
                            $marketDynamics = 'Volatile';
                        } elseif ($item->price_variance < ($item->recommended_price * 0.05)) {
                            $marketDynamics = 'Stable';
                        }
                        
                        return [
                            'barang_id' => $item->barang_id,
                            'nama_barang' => $item->nama_barang,
                            'barang_kode' => $item->barang_kode,
                            'wilayah' => $item->wilayah_kecamatan,
                            'kota_kabupaten' => $item->wilayah_kota_kabupaten,
                            'recommended_price' => round($item->recommended_price, 0),
                            'cost_price' => round($item->cost_price, 0),
                            'min_market_price' => round($item->min_market_price, 0),
                            'max_market_price' => round($item->max_market_price, 0),
                            'avg_margin' => round($item->avg_margin, 0),
                            'margin_percentage' => round($item->margin_percentage, 1),
                            'sample_size' => $item->sample_size,
                            'confidence_level' => $confidence,
                            'pricing_strategy' => $strategy,
                            'price_variance' => round($item->price_variance, 0),
                            'market_dynamics' => $marketDynamics,
                            'total_sales' => $item->total_sales,
                            'total_volume' => $item->total_volume,
                            'last_sale_date' => $item->last_sale_date ? Carbon::parse($item->last_sale_date)->format('Y-m-d') : null
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'data' => $recommendations,
                    'summary' => [
                        'total_recommendations' => $recommendations->count(),
                        'high_confidence' => $recommendations->where('confidence_level', 'High')->count(),
                        'medium_confidence' => $recommendations->where('confidence_level', 'Medium')->count(),
                        'avg_margin_percentage' => round($recommendations->avg('margin_percentage'), 1),
                        'premium_strategies' => $recommendations->where('pricing_strategy', 'Premium Pricing')->count(),
                        'competitive_strategies' => $recommendations->where('pricing_strategy', 'Competitive Pricing')->count()
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error getting price recommendations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate price recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get partner performance analysis with detailed metrics
     */
    public function getPartnerPerformanceAnalysis()
    {
        try {
            $cacheKey = 'partner_performance_analysis_' . auth()->id();
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
                $performanceData = DB::table('toko as t')
                    ->leftJoin('pengiriman as p', 't.toko_id', '=', 'p.toko_id')
                    ->leftJoin('barang_toko as bt', 't.toko_id', '=', 'bt.toko_id')
                    ->leftJoin('retur as r', 't.toko_id', '=', 'r.toko_id')
                    ->select([
                        't.toko_id',
                        't.nama_toko',
                        't.pemilik',
                        't.wilayah_kecamatan',
                        't.wilayah_kota_kabupaten',
                        't.nomer_telpon',
                        DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_orders'),
                        DB::raw('COUNT(DISTINCT bt.barang_id) as product_variety'),
                        DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                        DB::raw('COUNT(DISTINCT r.retur_id) as total_returns'),
                        DB::raw('AVG(DATEDIFF(NOW(), p.tanggal_pengiriman)) as avg_days_since_order'),
                        DB::raw('COUNT(DISTINCT DATE_FORMAT(p.tanggal_pengiriman, "%Y-%m")) as active_months'),
                        DB::raw('MAX(p.tanggal_pengiriman) as last_order_date'),
                        DB::raw('MIN(p.tanggal_pengiriman) as first_order_date'),
                        DB::raw('CASE 
                            WHEN COUNT(DISTINCT p.pengiriman_id) >= 50 AND COUNT(DISTINCT bt.barang_id) >= 10 THEN "Premium Partner"
                            WHEN COUNT(DISTINCT p.pengiriman_id) >= 20 AND COUNT(DISTINCT bt.barang_id) >= 5 THEN "Growth Partner"
                            WHEN COUNT(DISTINCT p.pengiriman_id) >= 5 THEN "Standard Partner"
                            ELSE "New Partner"
                        END as partner_segment'),
                        DB::raw('CASE 
                            WHEN COUNT(DISTINCT p.pengiriman_id) > 0 
                            THEN ROUND((COUNT(DISTINCT r.retur_id) / COUNT(DISTINCT p.pengiriman_id)) * 100, 2)
                            ELSE 0 
                        END as return_rate'),
                        DB::raw('CASE 
                            WHEN DATEDIFF(MAX(p.tanggal_pengiriman), MIN(p.tanggal_pengiriman)) > 0
                            THEN ROUND(COUNT(DISTINCT p.pengiriman_id) / (DATEDIFF(MAX(p.tanggal_pengiriman), MIN(p.tanggal_pengiriman)) / 30), 2)
                            ELSE 0
                        END as monthly_order_rate')
                    ])
                    ->where('t.is_active', 1)
                    ->groupBy('t.toko_id', 't.nama_toko', 't.pemilik', 't.wilayah_kecamatan', 't.wilayah_kota_kabupaten', 't.nomer_telpon')
                    ->orderBy('total_orders', 'desc')
                    ->get();

                // Calculate summary statistics
                $summary = [
                    'total_partners' => $performanceData->count(),
                    'premium_partners' => $performanceData->where('partner_segment', 'Premium Partner')->count(),
                    'growth_partners' => $performanceData->where('partner_segment', 'Growth Partner')->count(),
                    'standard_partners' => $performanceData->where('partner_segment', 'Standard Partner')->count(),
                    'new_partners' => $performanceData->where('partner_segment', 'New Partner')->count(),
                    'avg_orders_per_partner' => round($performanceData->avg('total_orders'), 2),
                    'avg_product_variety' => round($performanceData->avg('product_variety'), 1),
                    'avg_return_rate' => round($performanceData->avg('return_rate'), 2),
                    'top_performer' => $performanceData->first()?->nama_toko,
                    'total_volume' => $performanceData->sum('total_volume')
                ];

                return response()->json([
                    'success' => true,
                    'data' => $performanceData,
                    'summary' => $summary
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error getting partner performance analysis: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze partner performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get market opportunity analysis with detailed insights
     */
    public function getMarketOpportunityAnalysis()
    {
        try {
            $cacheKey = 'market_opportunity_analysis_' . auth()->id();
            
            return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
                $opportunityData = DB::table('toko as t')
                    ->leftJoin('pengiriman as p', 't.toko_id', '=', 'p.toko_id')
                    ->select([
                        't.wilayah_kecamatan',
                        't.wilayah_kota_kabupaten',
                        DB::raw('COUNT(DISTINCT t.toko_id) as current_coverage'),
                        DB::raw('COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN t.toko_id END) as active_partners'),
                        DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                        DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_orders'),
                        DB::raw('AVG(p.jumlah_kirim) as avg_order_size'),
                        DB::raw('CASE 
                            WHEN COUNT(DISTINCT t.toko_id) < 2 THEN "High Opportunity"
                            WHEN COUNT(DISTINCT t.toko_id) < 5 THEN "Medium Opportunity" 
                            WHEN COUNT(DISTINCT t.toko_id) < 8 THEN "Low Opportunity"
                            ELSE "Saturated"
                        END as opportunity_level'),
                        DB::raw('CASE 
                            WHEN COUNT(DISTINCT t.toko_id) < 2 THEN GREATEST(5 - COUNT(DISTINCT t.toko_id), 0)
                            WHEN COUNT(DISTINCT t.toko_id) < 5 THEN GREATEST(3 - COUNT(DISTINCT t.toko_id), 0)
                            ELSE 0
                        END as recommended_additions'),
                        DB::raw('CASE 
                            WHEN COUNT(DISTINCT t.toko_id) > 0 
                            THEN ROUND(COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN t.toko_id END) / COUNT(DISTINCT t.toko_id) * 100, 1)
                            ELSE 0 
                        END as activation_rate'),
                        DB::raw('CASE
                            WHEN SUM(p.jumlah_kirim) > 1000 THEN "High Volume"
                            WHEN SUM(p.jumlah_kirim) > 500 THEN "Medium Volume"
                            WHEN SUM(p.jumlah_kirim) > 0 THEN "Low Volume"
                            ELSE "No Volume"
                        END as volume_category')
                    ])
                    ->whereNotNull('t.wilayah_kecamatan')
                    ->groupBy('t.wilayah_kecamatan', 't.wilayah_kota_kabupaten')
                    ->having('current_coverage', '<', 10)
                    ->orderBy('recommended_additions', 'desc')
                    ->orderBy('total_volume', 'desc')
                    ->get();

                // Add market potential scoring
                $scoredOpportunities = $opportunityData->map(function($item) {
                    $score = 0;
                    
                    // Opportunity level scoring
                    switch($item->opportunity_level) {
                        case 'High Opportunity': $score += 40; break;
                        case 'Medium Opportunity': $score += 25; break;
                        case 'Low Opportunity': $score += 10; break;
                    }
                    
                    // Volume scoring
                    if ($item->total_volume > 500) $score += 30;
                    elseif ($item->total_volume > 100) $score += 20;
                    elseif ($item->total_volume > 0) $score += 10;
                    
                    // Activation rate scoring
                    if ($item->activation_rate > 80) $score += 20;
                    elseif ($item->activation_rate > 60) $score += 15;
                    elseif ($item->activation_rate > 40) $score += 10;
                    
                    // Order frequency scoring
                    if ($item->total_orders > 50) $score += 10;
                    elseif ($item->total_orders > 20) $score += 5;
                    
                    $item->market_potential_score = $score;
                    $item->priority_level = $score >= 70 ? 'High' : ($score >= 40 ? 'Medium' : 'Low');
                    
                    return $item;
                });

                $summary = [
                    'high_opportunity_areas' => $opportunityData->where('opportunity_level', 'High Opportunity')->count(),
                    'medium_opportunity_areas' => $opportunityData->where('opportunity_level', 'Medium Opportunity')->count(),
                    'low_opportunity_areas' => $opportunityData->where('opportunity_level', 'Low Opportunity')->count(),
                    'total_expansion_potential' => $opportunityData->sum('recommended_additions'),
                    'high_priority_territories' => $scoredOpportunities->where('priority_level', 'High')->count(),
                    'avg_activation_rate' => round($opportunityData->avg('activation_rate'), 1),
                    'total_untapped_volume' => $opportunityData->where('volume_category', 'No Volume')->count()
                ];

                return response()->json([
                    'success' => true,
                    'data' => $scoredOpportunities->values(),
                    'summary' => $summary
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error getting market opportunity analysis: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze market opportunities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product list for filters
     */
    public function getProductList()
    {
        try {
            $cacheKey = 'product_list_' . auth()->id();
            
            return Cache::remember($cacheKey, 60, function () {
                $products = DB::table('barang')
                    ->select(['barang_id', 'nama_barang', 'barang_kode', 'satuan'])
                    ->orderBy('nama_barang')
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'count' => $products->count()
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error getting product list: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load product list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export CRM insights - Placeholder for future implementation
     */
    public function exportCRMInsights()
    {
        try {
            // This would typically generate an Excel/CSV file
            // For now, return a success message with implementation note
            
            return response()->json([
                'success' => true,
                'message' => 'CRM insights export initiated',
                'download_url' => route('market-map.export-crm-insights'), // Would be actual download URL
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
     * Export price intelligence - Placeholder for future implementation
     */
    public function exportPriceIntelligence()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Price intelligence export initiated',
                'download_url' => route('market-map.export-price-intelligence'),
                'note' => 'Excel file with detailed price analysis will be generated',
                'estimated_completion' => now()->addMinutes(1)->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating price intelligence export: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export partner performance - Placeholder for future implementation
     */
    public function exportPartnerPerformance()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Partner performance export initiated',
                'download_url' => route('market-map.export-partner-performance'),
                'note' => 'Excel file with partner performance metrics will be generated',
                'estimated_completion' => now()->addMinutes(1)->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating partner performance export: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cache for market map data
     */
    public function clearCache()
    {
        try {
            $patterns = [
                'market_map_toko_data_',
                'crm_recommendations_',
                'partner_performance_analysis_',
                'market_opportunity_analysis_',
                'price_recommendations_',
                'wilayah_statistics_',
                'product_list_'
            ];

            $userId = auth()->id();
            foreach ($patterns as $pattern) {
                Cache::forget($pattern . $userId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
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
     * Get system health and performance metrics
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
                'cache_hit_rate' => 'Not available', // Would need cache analytics
                'avg_response_time' => 'Not tracked', // Would need performance monitoring
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

    /**
     * Get territory analysis with detailed data
     */
    public function getTerritoryAnalysis()
    {
        try {
            $territoryData = DB::table('toko as t')
                ->leftJoin('pengiriman as p', 't.toko_id', '=', 'p.toko_id')
                ->select([
                    't.wilayah_kota_kabupaten as territory',
                    DB::raw('COUNT(DISTINCT t.toko_id) as partner_count'),
                    DB::raw('COUNT(DISTINCT t.wilayah_kecamatan) as kecamatan_coverage'),
                    DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                    DB::raw('AVG(p.jumlah_kirim) as avg_order_size'),
                    DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_transactions')
                ])
                ->where('t.is_active', 1)
                ->where('p.status', 'terkirim')
                ->groupBy('t.wilayah_kota_kabupaten')
                ->orderBy('total_volume', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $territoryData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed partner analysis
     */
    public function getDetailedPartnerAnalysis()
    {
        try {
            $detailedAnalysis = DB::table('toko as t')
                ->leftJoin('pengiriman as p', 't.toko_id', '=', 'p.toko_id')
                ->leftJoin('retur as r', 't.toko_id', '=', 'r.toko_id')
                ->leftJoin('barang_toko as bt', 't.toko_id', '=', 'bt.toko_id')
                ->select([
                    't.toko_id',
                    't.nama_toko',
                    't.pemilik',
                    't.wilayah_kecamatan',
                    't.wilayah_kota_kabupaten',
                    't.nomer_telpon',
                    't.alamat',
                    DB::raw('COUNT(DISTINCT p.pengiriman_id) as total_orders'),
                    DB::raw('COUNT(DISTINCT bt.barang_id) as product_variety'),
                    DB::raw('SUM(p.jumlah_kirim) as total_volume'),
                    DB::raw('COUNT(DISTINCT r.retur_id) as total_returns'),
                    DB::raw('AVG(DATEDIFF(NOW(), p.tanggal_pengiriman)) as avg_days_since_order'),
                    DB::raw('COUNT(DISTINCT DATE_FORMAT(p.tanggal_pengiriman, "%Y-%m")) as active_months'),
                    DB::raw('MAX(p.tanggal_pengiriman) as last_order_date'),
                    DB::raw('MIN(p.tanggal_pengiriman) as first_order_date'),
                    DB::raw('CASE 
                        WHEN COUNT(DISTINCT p.pengiriman_id) >= 50 THEN "Premium Partner"
                        WHEN COUNT(DISTINCT p.pengiriman_id) >= 20 THEN "Growth Partner"
                        WHEN COUNT(DISTINCT p.pengiriman_id) >= 5 THEN "Standard Partner"
                        ELSE "New Partner"
                    END as partner_segment'),
                    DB::raw('CASE 
                        WHEN COUNT(DISTINCT p.pengiriman_id) > 0 
                        THEN ROUND((COUNT(DISTINCT r.retur_id) / COUNT(DISTINCT p.pengiriman_id)) * 100, 2)
                        ELSE 0 
                    END as return_rate')
                ])
                ->where('t.is_active', 1)
                ->groupBy('t.toko_id', 't.nama_toko', 't.pemilik', 't.wilayah_kecamatan', 't.wilayah_kota_kabupaten', 't.nomer_telpon', 't.alamat')
                ->orderBy('total_orders', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $detailedAnalysis,
                'summary' => [
                    'total_analyzed' => $detailedAnalysis->count(),
                    'avg_orders' => round($detailedAnalysis->avg('total_orders'), 2),
                    'top_performer' => $detailedAnalysis->first()?->nama_toko
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear system cache
     */
    public function clearSystemCache()
    {
        try {
            // Clear Laravel cache
            Cache::flush();
            
            return response()->json([
                'success' => true,
                'message' => 'System cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }
}
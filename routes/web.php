<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketMapController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\BarangTokoController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\LaporanTokoController;
use App\Http\Controllers\LaporanPemesananController;
use App\Http\Controllers\FollowUpPelangganController;

// Analytics Controllers - FIXED NAMESPACE (Tanpa Analytics\)
use App\Http\Controllers\PartnerPerformanceController;
use App\Http\Controllers\InventoryOptimizationController;
use App\Http\Controllers\ProductVelocityController;
use App\Http\Controllers\ProfitabilityController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route tamu/belum login
Route::middleware(['guest', 'nocache'])->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm']);
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

// Route yang memerlukan autentikasi
Route::middleware(['auth', 'nocache', 'verifysession', 'session.timeout'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // ===============================
    // DASHBOARD ROUTES 
    // ===============================
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Dashboard API Routes untuk View Data
    Route::prefix('dashboard/api')->group(function() {
        Route::get('/statistik', [DashboardController::class, 'getStatistikRingkasan']);
        Route::get('/grafik-pengiriman', [DashboardController::class, 'getGrafikPengiriman']);
        Route::get('/barang-analysis', [DashboardController::class, 'getBarangLakuTidakLaku'])->name('dashboard.api.barang-analysis');
        Route::get('/transaksi-terbaru', [DashboardController::class, 'getTransaksiTerbaru']);
        Route::get('/toko-retur-terbanyak', [DashboardController::class, 'getTokoReturTerbanyak']);
        Route::get('/debug', [DashboardController::class, 'debug'])->name('dashboard.api.debug');
    });

    // ===============================
    // ANALYTICS ROUTES - CORE 4 MODULES ONLY
    // ===============================
    Route::prefix('analytics')->name('analytics.')->group(function () {
        // Main Analytics Dashboard - Overview Only
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/api/overview', [AnalyticsController::class, 'getOverviewData'])->name('api.overview');

        // ===== ANALYTICS 1: PARTNER PERFORMANCE =====
        Route::prefix('partner-performance')->name('partner-performance.')->group(function () {
            Route::get('/', [PartnerPerformanceController::class, 'index'])->name('index');
            
            // API Routes
            Route::get('/api/data', [PartnerPerformanceController::class, 'getData'])->name('api.data');
            Route::get('/api/trends', [PartnerPerformanceController::class, 'getTrends'])->name('api.trends');
            Route::get('/api/statistics', [PartnerPerformanceController::class, 'getStatistics'])->name('api.statistics');
            Route::get('/api/search', [PartnerPerformanceController::class, 'searchPartners'])->name('api.search');
            
            // Partner Actions
            Route::get('/history/{partnerId}', [PartnerPerformanceController::class, 'getPartnerHistory'])->name('history');
            Route::post('/alert/{partnerId}', [PartnerPerformanceController::class, 'sendPartnerAlert'])->name('alert');
            Route::post('/bulk-alerts', [PartnerPerformanceController::class, 'sendBulkAlerts'])->name('bulk-alerts');
            
            // Export & Reports
            Route::get('/export', [PartnerPerformanceController::class, 'export'])->name('export');
            Route::post('/generate-report', [PartnerPerformanceController::class, 'generateReport'])->name('generate-report');
        });
        
        // ===== ANALYTICS 2: INVENTORY OPTIMIZATION =====
        Route::prefix('inventory-optimization')->name('inventory-optimization.')->group(function () {
            Route::get('/', [InventoryOptimizationController::class, 'index'])->name('index');
            
            // Recommendation Actions
            Route::post('/apply', [InventoryOptimizationController::class, 'applyRecommendation'])->name('apply');
            Route::post('/apply-all', [InventoryOptimizationController::class, 'applyAllRecommendations'])->name('apply-all');
            Route::post('/customize', [InventoryOptimizationController::class, 'customizeRecommendation'])->name('customize');
            Route::post('/generate', [InventoryOptimizationController::class, 'refreshRecommendations'])->name('generate');
            
            // Seasonal Configuration
            Route::get('/seasonal-config', [InventoryOptimizationController::class, 'getSeasonalAdjustments'])->name('seasonal-config');
            Route::post('/update-seasonal', [InventoryOptimizationController::class, 'updateSeasonalConfiguration'])->name('update-seasonal');
            
            // API Routes
            Route::get('/api/data', [InventoryOptimizationController::class, 'getOptimizationData'])->name('api.data');
            Route::get('/details/{recommendationId}', [InventoryOptimizationController::class, 'getRecommendationDetails'])->name('details');
            
            // Export
            Route::get('/export', [InventoryOptimizationController::class, 'export'])->name('export');
        });
        
        // ===== ANALYTICS 3: PRODUCT VELOCITY =====
        Route::prefix('product-velocity')->name('product-velocity.')->group(function () {
            Route::get('/', [ProductVelocityController::class, 'index'])->name('index');
            Route::get('/export', [ProductVelocityController::class, 'export'])->name('export');
            Route::post('/optimize-portfolio', [ProductVelocityController::class, 'optimizePortfolio'])->name('optimize-portfolio');
            Route::post('/recommend-increase/{barangId}', [ProductVelocityController::class, 'recommendIncrease'])->name('recommend-increase');
            Route::post('/recommend-discontinue/{barangId}', [ProductVelocityController::class, 'recommendDiscontinue'])->name('recommend-discontinue');
        });
        
        // ===== ANALYTICS 4: PROFITABILITY ANALYSIS =====
        Route::prefix('profitability-analysis')->name('profitability-analysis.')->group(function () {
            Route::get('/', [ProfitabilityController::class, 'index'])->name('index');
            Route::get('/export', [ProfitabilityController::class, 'export'])->name('export');
            Route::get('/identify-loss-makers', [ProfitabilityController::class, 'identifyLossMakers'])->name('identify-loss-makers');
            Route::post('/flag-partner/{partnerId}', [ProfitabilityController::class, 'flagPartner'])->name('flag-partner');
            Route::post('/optimize-partner/{partnerId}', [ProfitabilityController::class, 'optimizePartner'])->name('optimize-partner');
            Route::get('/roi-distribution', [ProfitabilityController::class, 'getRoiDistribution'])->name('roi-distribution');
        });
    });

    // Route profil
    Route::middleware(['auth'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        
        Route::prefix('pengaturan')->group(function () {
            Route::get('/edit-profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::post('/update-profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::get('/ubah-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
            Route::post('/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
        });
    });
    
    // Route Master Data
    Route::group(['prefix' => 'barang'], function() {
        Route::get('/', [BarangController::class, 'index'])->name('barang.index');
        Route::get('/data', [BarangController::class, 'getData'])->name('barang.data');
        Route::get('/generate-kode', [BarangController::class, 'generateKode'])->name('barang.generateKode');
        Route::post('/store', [BarangController::class, 'store'])->name('barang.store');
        Route::get('/{id}/edit', [BarangController::class, 'edit'])->name('barang.edit');
        Route::put('/update/{id}', [BarangController::class, 'update'])->name('barang.update');
        Route::delete('/destroy/{id}', [BarangController::class, 'destroy'])->name('barang.destroy');
        Route::get('/list', [BarangController::class, 'getList'])->name('barang.list');
    });
    
    Route::prefix('toko')->group(function() {
        // Basic CRUD routes
        Route::get('/', [TokoController::class, 'index'])->name('toko.index');
        Route::get('/list', [TokoController::class, 'getList'])->name('toko.list');
        Route::get('/data', [TokoController::class, 'getData'])->name('toko.data');
        Route::get('/generate-kode', [TokoController::class, 'generateKode'])->name('toko.generateKode');
        Route::post('/', [TokoController::class, 'store'])->name('toko.store');
        Route::get('/{id}', [TokoController::class, 'show'])->name('toko.show');
        Route::get('/{id}/edit', [TokoController::class, 'edit'])->name('toko.edit');
        Route::put('/{id}', [TokoController::class, 'update'])->name('toko.update');
        Route::delete('/{id}', [TokoController::class, 'destroy'])->name('toko.destroy');
        
        // Wilayah routes
        Route::get('/wilayah/kota', [TokoController::class, 'getWilayahKota'])->name('toko.wilayah.kota');
        Route::get('/wilayah/kecamatan', [TokoController::class, 'getKecamatanByKota'])->name('toko.wilayah.kecamatan');
        Route::get('/wilayah/kelurahan', [TokoController::class, 'getKelurahanByKecamatan'])->name('toko.wilayah.kelurahan');
        
        // Enhanced geocoding routes
        Route::post('/preview-geocode', [TokoController::class, 'previewGeocode'])->name('toko.previewGeocode');
        Route::post('/geocode', [TokoController::class, 'geocodeToko'])->name('toko.geocodeToko');
        Route::post('/batch-geocode', [TokoController::class, 'batchGeocodeToko'])->name('toko.batchGeocodeToko');
        Route::post('/validate-coordinates', [TokoController::class, 'validateMapCoordinates'])->name('toko.validateCoordinates');
    });
    
    Route::get('/barang-toko/getBarangToko', [BarangTokoController::class, 'getBarangToko'])->name('barang-toko.getBarangToko');
    Route::get('/barang-toko/getAvailableBarang', [BarangTokoController::class, 'getAvailableBarang'])->name('barang-toko.getAvailableBarang');
    Route::resource('barang-toko', BarangTokoController::class);
    
    Route::group(['prefix' => 'customer'], function() {
        Route::get('/', [CustomerController::class, 'index'])->name('customer.index');
        Route::get('/data', [CustomerController::class, 'getData'])->name('customer.data');
        Route::post('/', [CustomerController::class, 'store'])->name('customer.store');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('customer.edit');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('customer.update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('customer.destroy');
        Route::post('/import', [CustomerController::class, 'import'])->name('customer.import');
        Route::post('/sync-pemesanan', [CustomerController::class, 'syncFromPemesanan'])->name('customer.syncPemesanan');
        Route::get('/debug-tables', [CustomerController::class, 'debugTables'])->name('customer.debugTables');
    });
    
    // Route Transaksi
    Route::group(['prefix' => 'pengiriman'], function() {
        Route::get('/', [PengirimanController::class, 'index'])->name('pengiriman.index');
        Route::get('/data', [PengirimanController::class, 'getData'])->name('pengiriman.data');
        Route::get('/get-nomer', [PengirimanController::class, 'getNomerPengiriman'])->name('pengiriman.getNomerPengiriman');
        Route::get('/get-barang-by-toko', [PengirimanController::class, 'getBarangByToko'])->name('pengiriman.getBarangByToko');
        Route::put('/{id}/update-status', [PengirimanController::class, 'updateStatus'])->name('pengiriman.updateStatus');
        Route::get('/export', [PengirimanController::class, 'export'])->name('pengiriman.export');
        Route::get('/list', [PengirimanController::class, 'getList'])->name('pengiriman.list');
        Route::post('/', [PengirimanController::class, 'store'])->name('pengiriman.store');
        Route::get('/{id}/edit', [PengirimanController::class, 'edit'])->name('pengiriman.edit');
        Route::put('/{id}', [PengirimanController::class, 'update'])->name('pengiriman.update');
        Route::delete('/{id}', [PengirimanController::class, 'destroy'])->name('pengiriman.destroy');
    });
    Route::resource('pengiriman', PengirimanController::class);
    
    Route::group(['prefix' => 'retur'], function() {
        Route::get('/', [ReturController::class, 'index'])->name('retur.index');
        Route::get('/data', [ReturController::class, 'getData'])->name('retur.data');
        Route::get('/get-pengiriman', [ReturController::class, 'getPengiriman'])->name('retur.getPengiriman');
        Route::post('/store', [ReturController::class, 'store'])->name('retur.store');
        Route::get('/export', [ReturController::class, 'export'])->name('retur.export');
        Route::get('/{id}', [ReturController::class, 'show'])->name('retur.show');
        Route::delete('/{id}', [ReturController::class, 'destroy'])->name('retur.destroy');
    });
    
    Route::group(['prefix' => 'pemesanan'], function() {
        Route::get('/', [PemesananController::class, 'index'])->name('pemesanan.index');
        Route::get('/data', [PemesananController::class, 'getData'])->name('pemesanan.data');
        Route::get('/get-id', [PemesananController::class, 'getPemesananId'])->name('pemesanan.getId');
        Route::post('/store', [PemesananController::class, 'store'])->name('pemesanan.store');
        Route::get('/{id}', [PemesananController::class, 'show'])->name('pemesanan.show');
        Route::put('/{id}', [PemesananController::class, 'update'])->name('pemesanan.update');
        Route::delete('/{id}', [PemesananController::class, 'destroy'])->name('pemesanan.destroy');
    });
    
    // Route Laporan
    Route::get('/laporan-pemesanan', [LaporanPemesananController::class, 'index'])->name('laporan.pemesanan');
    Route::get('/laporan-pemesanan/data', [LaporanPemesananController::class, 'getData'])->name('laporan.pemesanan.data');
    Route::post('/laporan-pemesanan/update-catatan', [LaporanPemesananController::class, 'updateCatatan'])->name('laporan.pemesanan.updateCatatan');
    Route::get('/laporan-pemesanan/detail', [LaporanPemesananController::class, 'getDetailData'])->name('laporan.pemesanan.detail');
    Route::get('/laporan-pemesanan/export-csv', [LaporanPemesananController::class, 'exportCsv'])->name('laporan.pemesanan.exportCsv');
    
    Route::get('/laporan-toko', [LaporanTokoController::class, 'index'])->name('laporan.toko');
    Route::get('/laporan-toko/data', [LaporanTokoController::class, 'getData'])->name('laporan.toko.data');
    Route::post('/laporan-toko/update-catatan', [LaporanTokoController::class, 'updateCatatan'])->name('laporan.toko.updateCatatan');
    Route::get('/laporan-toko/detail', [LaporanTokoController::class, 'getDetailData'])->name('laporan.toko.detail');
    Route::get('/laporan-toko/export-csv', [LaporanTokoController::class, 'exportCsv'])->name('laporan.toko.exportCsv');
    Route::get('/laporan-toko/export-detail-csv', [LaporanTokoController::class, 'exportDetailCsv'])->name('laporan.toko.exportDetailCsv');
    
    // Route Follow Up Pelanggan (Complete with WhatsApp Integration)
    Route::group(['prefix' => 'follow-up-pelanggan'], function() {
        Route::get('/', [FollowUpPelangganController::class, 'index'])->name('follow-up-pelanggan.index');
        Route::get('/test-image-sending', [FollowUpPelangganController::class, 'testImageSending']);
        // Customer data endpoints
        Route::get('/filtered-customers', [FollowUpPelangganController::class, 'getFilteredCustomers'])->name('follow-up-pelanggan.filtered-customers');
        
        // Follow up actions
        Route::post('/send', [FollowUpPelangganController::class, 'sendFollowUp'])->name('follow-up-pelanggan.send');
        
        // History and tracking
        Route::get('/history', [FollowUpPelangganController::class, 'getHistory'])->name('follow-up-pelanggan.history');
        
        // File handling
        Route::post('/upload-image', [FollowUpPelangganController::class, 'uploadImage'])->name('follow-up-pelanggan.upload-image');
        
        // WhatsApp device management
        Route::get('/device-status', [FollowUpPelangganController::class, 'getDeviceStatus'])->name('follow-up-pelanggan.device-status');
        Route::post('/test-connection', [FollowUpPelangganController::class, 'testWhatsAppConnection'])->name('follow-up-pelanggan.test-connection');
        
        // Debug route (temporary - remove in production)
        Route::get('/debug', [FollowUpPelangganController::class, 'debugDatabase'])->name('follow-up-pelanggan.debug');
        Route::get('/debug-wablas', [FollowUpPelangganController::class, 'debugWablas'])->name('follow-up-pelanggan.debug-wablas');
        
    });

    // ===============================
    // MARKET MAP CRM ROUTES - ENHANCED FOR EKSPANSI TOKO
    // ===============================
    Route::group(['prefix' => 'market-map'], function() {
        // Main CRM Market Map
        Route::get('/', [MarketMapController::class, 'index'])->name('market-map.index');
        
        // ===== CORE DATA APIs =====
        Route::get('/toko-data', [MarketMapController::class, 'getTokoData'])->name('market-map.toko-data');
        Route::get('/wilayah-statistics', [MarketMapController::class, 'getWilayahStatistics'])->name('market-map.wilayah-statistics');
        Route::get('/partner-details/{tokoId}', [MarketMapController::class, 'getTokoBarang'])->name('market-map.partner-details');
        
        // ===== PROFIT ANALYSIS APIs =====
        Route::post('/calculate-profit', [MarketMapController::class, 'calculateProfitAllStores'])->name('market-map.calculate-profit');
        Route::get('/profit-analysis', [MarketMapController::class, 'getProfitAnalysis'])->name('market-map.profit-analysis');
        Route::get('/profit-data/{tokoId}', [MarketMapController::class, 'getTokoProfit'])->name('market-map.profit-data');
        
        // ===== CLUSTERING APIs =====
        Route::post('/create-clusters', [MarketMapController::class, 'createClusters'])->name('market-map.create-clusters');
        Route::get('/clusters-data', [MarketMapController::class, 'getClustersData'])->name('market-map.clusters-data');
        Route::get('/cluster-details/{clusterId}', [MarketMapController::class, 'getClusterDetails'])->name('market-map.cluster-details');
        
        // ===== EXPANSION PLANNING APIs =====
        Route::post('/generate-expansion-plan', [MarketMapController::class, 'generateExpansionRecommendations'])->name('market-map.generate-expansion-plan');
        Route::get('/expansion-recommendations', [MarketMapController::class, 'getExpansionRecommendations'])->name('market-map.expansion-recommendations');
        Route::post('/validate-expansion', [MarketMapController::class, 'validateExpansionPlan'])->name('market-map.validate-expansion');
        
        // ===== ENHANCED CRM INTELLIGENCE APIs =====
        Route::get('/recommendations', [MarketMapController::class, 'getRecommendations'])->name('market-map.recommendations');
        Route::get('/price-recommendations', [MarketMapController::class, 'getPriceRecommendations'])->name('market-map.price-recommendations');
        Route::get('/partner-performance', [MarketMapController::class, 'getPartnerPerformanceAnalysis'])->name('market-map.partner-performance');
        Route::get('/market-opportunities', [MarketMapController::class, 'getMarketOpportunityAnalysis'])->name('market-map.market-opportunities');
        
        // ===== TERRITORY & GEOGRAPHIC ANALYSIS =====
        Route::get('/territory-analysis', [MarketMapController::class, 'getTerritoryAnalysis'])->name('market-map.territory-analysis');
        Route::get('/expansion-opportunities', [MarketMapController::class, 'getExpansionOpportunities'])->name('market-map.expansion-opportunities');
        Route::get('/competitive-analysis', [MarketMapController::class, 'getCompetitiveAnalysis'])->name('market-map.competitive-analysis');
        Route::get('/market-saturation', [MarketMapController::class, 'getMarketSaturation'])->name('market-map.market-saturation');
        
        // ===== ENHANCED GEOGRAPHIC DATA =====
        Route::get('/enhanced-toko-data', [MarketMapController::class, 'getEnhancedTokoData'])->name('market-map.enhanced-toko-data');
        Route::get('/grid-heatmap-data', [MarketMapController::class, 'getGridHeatmapData'])->name('market-map.grid-heatmap-data');
        Route::get('/enhanced-wilayah-stats', [MarketMapController::class, 'getEnhancedWilayahStatistics'])->name('market-map.enhanced-wilayah-stats');
        
        // ===== BUSINESS INTELLIGENCE APIs =====
        Route::get('/roi-calculator', [MarketMapController::class, 'calculateROI'])->name('market-map.roi-calculator');
        Route::get('/break-even-analysis', [MarketMapController::class, 'getBreakEvenAnalysis'])->name('market-map.break-even-analysis');
        Route::get('/investment-projection', [MarketMapController::class, 'getInvestmentProjection'])->name('market-map.investment-projection');
        Route::get('/market-penetration', [MarketMapController::class, 'getMarketPenetration'])->name('market-map.market-penetration');
        
        // ===== REFERENCE & SUPPORTING DATA =====
        Route::get('/wilayah-data', [MarketMapController::class, 'getWilayahData'])->name('market-map.wilayah-data');
        Route::get('/product-list', [MarketMapController::class, 'getProductList'])->name('market-map.product-list');
        Route::get('/store-categories', [MarketMapController::class, 'getStoreCategories'])->name('market-map.store-categories');
        
        // ===== EXPORT & REPORTING =====
        Route::get('/export-crm-insights', [MarketMapController::class, 'exportCRMInsights'])->name('market-map.export-crm-insights');
        Route::get('/export-profit-analysis', [MarketMapController::class, 'exportProfitAnalysis'])->name('market-map.export-profit-analysis');
        Route::get('/export-clustering-data', [MarketMapController::class, 'exportClusteringData'])->name('market-map.export-clustering-data');
        Route::get('/export-expansion-plan', [MarketMapController::class, 'exportExpansionPlan'])->name('market-map.export-expansion-plan');
        Route::get('/export-price-intelligence', [MarketMapController::class, 'exportPriceIntelligence'])->name('market-map.export-price-intelligence');
        Route::get('/export-partner-performance', [MarketMapController::class, 'exportPartnerPerformance'])->name('market-map.export-partner-performance');
        Route::get('/export-comprehensive-report', [MarketMapController::class, 'exportComprehensiveReport'])->name('market-map.export-comprehensive-report');
        
        // ===== GEOCODING & DATA QUALITY =====
        Route::post('/bulk-geocode', [MarketMapController::class, 'bulkGeocodeTokos'])->name('market-map.bulk-geocode');
        Route::get('/geocode-status', [MarketMapController::class, 'getGeocodeStatus'])->name('market-map.geocode-status');
        Route::post('/enhanced-bulk-geocode', [MarketMapController::class, 'enhancedBulkGeocodeTokos'])->name('market-map.enhanced-bulk-geocode');
        Route::post('/fix-coordinates/{tokoId}', [MarketMapController::class, 'fixTokoCoordinates'])->name('market-map.fix-coordinates');
        Route::post('/validate-coordinates', [MarketMapController::class, 'validateCoordinates'])->name('market-map.validate-coordinates');
        
        // ===== SYSTEM MANAGEMENT =====
        Route::get('/system-health', [MarketMapController::class, 'getSystemHealth'])->name('market-map.system-health');
        Route::get('/detailed-partner-analysis', [MarketMapController::class, 'getDetailedPartnerAnalysis'])->name('market-map.detailed-partner-analysis');
        Route::post('/clear-cache', [MarketMapController::class, 'clearSystemCache'])->name('market-map.clear-cache');
        Route::post('/refresh-all-data', [MarketMapController::class, 'refreshAllData'])->name('market-map.refresh-all-data');
        Route::get('/performance-metrics', [MarketMapController::class, 'getPerformanceMetrics'])->name('market-map.performance-metrics');
        
        // ===== ADVANCED ANALYTICS =====
        Route::get('/predictive-analysis', [MarketMapController::class, 'getPredictiveAnalysis'])->name('market-map.predictive-analysis');
        Route::get('/trend-analysis', [MarketMapController::class, 'getTrendAnalysis'])->name('market-map.trend-analysis');
        Route::get('/seasonal-patterns', [MarketMapController::class, 'getSeasonalPatterns'])->name('market-map.seasonal-patterns');
        Route::get('/customer-behavior', [MarketMapController::class, 'getCustomerBehavior'])->name('market-map.customer-behavior');
        
        // ===== CONFIGURATION & SETTINGS =====
        Route::get('/config', [MarketMapController::class, 'getConfiguration'])->name('market-map.config');
        Route::post('/update-config', [MarketMapController::class, 'updateConfiguration'])->name('market-map.update-config');
        Route::post('/reset-config', [MarketMapController::class, 'resetConfiguration'])->name('market-map.reset-config');
        
        // ===== COLLABORATION & SHARING =====
        Route::post('/share-analysis', [MarketMapController::class, 'shareAnalysis'])->name('market-map.share-analysis');
        Route::get('/shared-analysis/{shareId}', [MarketMapController::class, 'getSharedAnalysis'])->name('market-map.shared-analysis');
        Route::post('/save-scenario', [MarketMapController::class, 'saveScenario'])->name('market-map.save-scenario');
        Route::get('/load-scenario/{scenarioId}', [MarketMapController::class, 'loadScenario'])->name('market-map.load-scenario');
        
        // ===== MONITORING & ALERTS =====
        Route::get('/monitoring-dashboard', [MarketMapController::class, 'getMonitoringDashboard'])->name('market-map.monitoring-dashboard');
        Route::post('/setup-alerts', [MarketMapController::class, 'setupAlerts'])->name('market-map.setup-alerts');
        Route::get('/alert-history', [MarketMapController::class, 'getAlertHistory'])->name('market-map.alert-history');
        
        // ===== MOBILE API ENDPOINTS =====
        Route::prefix('mobile')->name('mobile.')->group(function() {
            Route::get('/summary', [MarketMapController::class, 'getMobileSummary'])->name('summary');
            Route::get('/nearby-stores', [MarketMapController::class, 'getNearbyStores'])->name('nearby-stores');
            Route::get('/quick-analysis', [MarketMapController::class, 'getQuickAnalysis'])->name('quick-analysis');
        });
    });

    // ===============================
    // DEBUGGING ROUTES (Development Only)
    // ===============================
    if (app()->environment(['local', 'development'])) {
        Route::group(['prefix' => 'debug/market-map'], function() {
            Route::get('/test-geocoding', [MarketMapController::class, 'testGeocodingService']);
            Route::get('/coordinate-stats', [MarketMapController::class, 'getCoordinateStatistics']);
            Route::get('/validate-coordinates', [MarketMapController::class, 'validateAllCoordinates']);
            Route::get('/crm-test-data', [MarketMapController::class, 'generateTestCRMData']);
            Route::get('/performance-test', [MarketMapController::class, 'performanceTest']);
            Route::get('/simulate-data', [MarketMapController::class, 'simulateExpansionData']);
            Route::get('/test-calculations', [MarketMapController::class, 'testCalculations']);
            Route::get('/database-health', [MarketMapController::class, 'checkDatabaseHealth']);
        });
    }
});
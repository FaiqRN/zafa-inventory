<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
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

// Route tamu/belum login dengan rate limiting
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm']);
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.process');
});

// Token authentication route dengan rate limiting
Route::middleware(['throttle:10,1'])->group(function () {
    Route::get('/auth/{token}', [LoginController::class, 'loginViaToken'])->name('auth.token');
});

// Route yang memerlukan autentikasi dengan prevent.back middleware
Route::middleware(['auth', 'prevent.back', 'verifysession', 'session.timeout'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
    
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
            Route::get('/seasonal-settings', [InventoryOptimizationController::class, 'seasonalSettings'])->name('seasonal-settings');
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
        
        // Stok Barang endpoints
        Route::get('/{id}/stok', [BarangController::class, 'getStokBarang'])->name('barang.stok');
        Route::post('/stok/store', [BarangController::class, 'storeStok'])->name('barang.stok.store');
        Route::get('/stok/{id}/edit', [BarangController::class, 'editStok'])->name('barang.stok.edit');
        Route::post('/stok/update/{id}', [BarangController::class, 'updateStok'])->name('barang.stok.update');
        
        // Stock management endpoints
        Route::get('/{id}/stock-info', [BarangController::class, 'getStockInfo'])->name('barang.stockInfo');
        Route::post('/validate-stock', [BarangController::class, 'validateStock'])->name('barang.validateStock');
        
        // FIFO Stock Management endpoints
        Route::get('/{id}/tambah-stok', [BarangController::class, 'tambahStok'])->name('barang.tambah-stok');
        Route::post('/{id}/tambah-stok', [BarangController::class, 'storeTambahStok'])->name('barang.store-tambah-stok');
        Route::get('/{id}/riwayat-stok', [BarangController::class, 'riwayatStok'])->name('barang.riwayat-stok');
        Route::get('/{id}/detail-batch-datatable', [BarangController::class, 'detailBatchDatatable'])->name('barang.detail-batch-datatable');
    });

    
    Route::prefix('toko')->group(function() {
        // Basic CRUD routes (non-parameterized first)
        Route::get('/', [TokoController::class, 'index'])->name('toko.index');
        Route::get('/list', [TokoController::class, 'getList'])->name('toko.list');
        Route::get('/data', [TokoController::class, 'getData'])->name('toko.data');
        Route::get('/generate-kode', [TokoController::class, 'generateKode'])->name('toko.generateKode');
        Route::post('/', [TokoController::class, 'store'])->name('toko.store');
        
        // Wilayah routes
        Route::get('/wilayah/kota', [TokoController::class, 'getWilayahKota'])->name('toko.wilayah.kota');
        Route::get('/wilayah/kecamatan', [TokoController::class, 'getKecamatanByKota'])->name('toko.wilayah.kecamatan');
        Route::get('/wilayah/kelurahan', [TokoController::class, 'getKelurahanByKecamatan'])->name('toko.wilayah.kelurahan');
        
        // Kelurahan coordinates API routes (MUST be before /{id})
        Route::get('/kelurahan-coordinates', [TokoController::class, 'getKelurahanCoordinates'])->name('toko.kelurahanCoordinates');
        Route::get('/kelurahan/search', [TokoController::class, 'searchKelurahan'])->name('toko.searchKelurahan');
        Route::get('/kelurahan/by-name', [TokoController::class, 'getKelurahanByName'])->name('toko.kelurahanByName');
        Route::get('/search-jalan', [TokoController::class, 'searchJalan'])->name('toko.searchJalan');
        
        // Enhanced geocoding routes
        Route::post('/preview-geocode', [TokoController::class, 'previewGeocode'])->name('toko.previewGeocode');
        Route::post('/geocode', [TokoController::class, 'geocodeToko'])->name('toko.geocodeToko');
        Route::post('/batch-geocode', [TokoController::class, 'batchGeocodeToko'])->name('toko.batchGeocodeToko');
        Route::post('/validate-coordinates', [TokoController::class, 'validateMapCoordinates'])->name('toko.validateCoordinates');
        
        // Parameterized routes (MUST be last to avoid catching specific routes)
        Route::get('/{id}', [TokoController::class, 'show'])->name('toko.show');
        Route::get('/{id}/edit', [TokoController::class, 'edit'])->name('toko.edit');
        Route::get('/{id}/coordinate-details', [TokoController::class, 'getCoordinateDetails'])->name('toko.coordinateDetails');
        Route::put('/{id}', [TokoController::class, 'update'])->name('toko.update');
        Route::delete('/{id}', [TokoController::class, 'destroy'])->name('toko.destroy');
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
    });
    
    // ===============================
    // USER MANAGEMENT ROUTES (Menu Sistem)
    // ===============================
    Route::group(['prefix' => 'user'], function() {
        Route::get('/', [UserController::class, 'index'])->name('user.index');
        Route::get('/data', [UserController::class, 'getData'])->name('user.data');
        Route::post('/', [UserController::class, 'store'])->name('user.store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('user.destroy');
    });
    
    // ===============================
    // MARKET MAP SETTINGS ROUTES (Menu Sistem)
    // ===============================
    Route::group(['prefix' => 'market-map-settings', 'middleware' => 'can:manage-users'], function() {
        Route::get('/', [\App\Http\Controllers\MarketMapSettingController::class, 'index'])->name('market-map-settings.index');
        Route::post('/update', [\App\Http\Controllers\MarketMapSettingController::class, 'update'])->name('market-map-settings.update');
        Route::post('/reset', [\App\Http\Controllers\MarketMapSettingController::class, 'reset'])->name('market-map-settings.reset');
        Route::get('/value/{key}', [\App\Http\Controllers\MarketMapSettingController::class, 'getValue'])->name('market-map-settings.getValue');
    });

    // ===============================
    // PARTNER PERFORMANCE SETTINGS ROUTES (Menu Sistem)
    // ===============================
    Route::group(['prefix' => 'partner-performance-settings', 'middleware' => 'can:manage-users'], function() {
        Route::get('/', [\App\Http\Controllers\PartnerPerformanceSettingController::class, 'index'])->name('partner-performance-settings.index');
        Route::post('/update', [\App\Http\Controllers\PartnerPerformanceSettingController::class, 'update'])->name('partner-performance-settings.update');
        Route::post('/reset', [\App\Http\Controllers\PartnerPerformanceSettingController::class, 'resetDefaults'])->name('partner-performance-settings.reset');
    });

    // ===============================
    // INVENTORY OPTIMIZATION SETTINGS ROUTES (Menu Sistem)
    // ===============================
    Route::group(['prefix' => 'inventory-optimization-settings', 'middleware' => 'can:manage-users'], function() {
        Route::get('/', [InventoryOptimizationController::class, 'seasonalSettings'])->name('inventory-optimization.seasonal-settings');
        Route::post('/update', [InventoryOptimizationController::class, 'updateSeasonalConfiguration'])->name('inventory-optimization.update-seasonal');
    });

    // ===============================
    // SEASONAL INVENTORY SETTINGS ROUTES (Menu Sistem)
    // ===============================
    Route::group(['prefix' => 'seasonal-inventory-settings', 'middleware' => 'can:manage-users'], function() {
        Route::get('/', [\App\Http\Controllers\SeasonalInventorySettingController::class, 'index'])->name('seasonal-inventory-settings.index');
        Route::post('/update', [\App\Http\Controllers\SeasonalInventorySettingController::class, 'update'])->name('seasonal-inventory-settings.update');
        Route::post('/reset', [\App\Http\Controllers\SeasonalInventorySettingController::class, 'reset'])->name('seasonal-inventory-settings.reset');
        Route::get('/value/{key}', [\App\Http\Controllers\SeasonalInventorySettingController::class, 'getValue'])->name('seasonal-inventory-settings.getValue');
    });
    
    // Route Transaksi
    Route::group(['prefix' => 'pengiriman'], function() {
        Route::get('/', [PengirimanController::class, 'index'])->name('pengiriman.index');
        Route::post('/list', [PengirimanController::class, 'list'])->name('pengiriman.list');
        Route::get('/get_nomer', [PengirimanController::class, 'get_nomer'])->name('pengiriman.getNomer');
        Route::get('/get_barang', [PengirimanController::class, 'get_barang'])->name('pengiriman.getBarang');
        Route::get('/create_ajax', [PengirimanController::class, 'create_ajax'])->name('pengiriman.createAjax');
        Route::post('/ajax', [PengirimanController::class, 'ajax'])->name('pengiriman.storeAjax');
        Route::get('/{nomer}/show_ajax', [PengirimanController::class, 'show_ajax'])->name('pengiriman.showAjax');
        Route::post('/{nomer}/update_status', [PengirimanController::class, 'update_status'])->name('pengiriman.updateStatus');
        Route::get('/{nomer}/print', [PengirimanController::class, 'print'])->name('pengiriman.print');
    });
    
    Route::group(['prefix' => 'retur'], function() {
        Route::get('/', [ReturController::class, 'index'])->name('retur.index');
        Route::get('/data', [ReturController::class, 'getData'])->name('retur.data');
        Route::post('/store', [ReturController::class, 'store'])->name('retur.store');
        Route::get('/export', [ReturController::class, 'export'])->name('retur.export');
        Route::get('/{nomerPengiriman}', [ReturController::class, 'show'])->name('retur.show');
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
        
    Route::group(['middleware' => ['can:view-reports']], function() {
        Route::get('/laporan-toko', [LaporanTokoController::class, 'index'])->name('laporan.toko');
        Route::get('/laporan-toko/data', [LaporanTokoController::class, 'getData'])->name('laporan.toko.data');
        Route::post('/laporan-toko/update-catatan', [LaporanTokoController::class, 'updateCatatan'])->name('laporan.toko.updateCatatan');
        Route::get('/laporan-toko/detail', [LaporanTokoController::class, 'getDetailData'])->name('laporan.toko.detail');
        Route::get('/laporan-toko/export-csv', [LaporanTokoController::class, 'exportCsv'])->name('laporan.toko.exportCsv');
        Route::get('/laporan-toko/export-detail-csv', [LaporanTokoController::class, 'exportDetailCsv'])->name('laporan.toko.exportDetailCsv');
    });
    
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
    // MARKET MAP CRM ROUTES - ENHANCED FOR EKSPANSI TOKO - FIXED VERSION
    // ===============================
    Route::group(['prefix' => 'market-map'], function() {
        // Main CRM Market Map
        Route::get('/', [MarketMapController::class, 'index'])->name('market-map.index');
        
        // ===== CORE DATA APIs - FIXED =====
        Route::get('/toko-data', [MarketMapController::class, 'getTokoData'])->name('market-map.toko-data');
        
        // ===== MAIN BUSINESS LOGIC APIS - FIXED =====
        Route::post('/calculate-profit', [MarketMapController::class, 'calculateProfitAllStores'])->name('market-map.calculate-profit');
        Route::post('/create-clusters', [MarketMapController::class, 'createClusters'])->name('market-map.create-clusters');
        Route::post('/generate-expansion-plan', [MarketMapController::class, 'generateExpansionRecommendations'])->name('market-map.generate-expansion-plan');
        
        // ===== ADDITIONAL DATA ENDPOINTS =====
        Route::get('/wilayah-statistics', [MarketMapController::class, 'getWilayahStatistics'])->name('market-map.wilayah-statistics');
        Route::get('/partner-details/{tokoId}', [MarketMapController::class, 'getTokoBarang'])->name('market-map.partner-details');
        Route::get('/profit-analysis', [MarketMapController::class, 'getProfitAnalysis'])->name('market-map.profit-analysis');
        Route::get('/profit-data/{tokoId}', [MarketMapController::class, 'getTokoProfit'])->name('market-map.profit-data');
        Route::get('/clusters-data', [MarketMapController::class, 'getClustersData'])->name('market-map.clusters-data');
        Route::get('/cluster-details/{clusterId}', [MarketMapController::class, 'getClusterDetails'])->name('market-map.cluster-details');
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
        
        // ===== SYSTEM MANAGEMENT - FIXED =====
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
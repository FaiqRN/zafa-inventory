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
Route::group(['prefix' => 'analytics', 'middleware' => 'auth'], function () {
    // Main Analytics Dashboard
    Route::get('/', [App\Http\Controllers\AnalyticsController::class, 'index'])
        ->name('analytics.index');
    
    // Analytics 1: Partner Performance Analytics
    Route::get('/partner-performance', [App\Http\Controllers\AnalyticsController::class, 'partnerPerformance'])
        ->name('analytics.partner-performance');
    
    // Analytics 2: Inventory Optimization
    Route::get('/inventory-optimization', [App\Http\Controllers\AnalyticsController::class, 'inventoryOptimization'])
        ->name('analytics.inventory-optimization');
    
    // Analytics 3: Product Velocity
    Route::get('/product-velocity', [App\Http\Controllers\AnalyticsController::class, 'productVelocity'])
        ->name('analytics.product-velocity');
    
    // Analytics 4: Profitability Analysis
    Route::get('/profitability-analysis', [App\Http\Controllers\AnalyticsController::class, 'profitabilityAnalysis'])
        ->name('analytics.profitability-analysis');
    
    // Analytics 5: Channel Comparison
    Route::get('/channel-comparison', [App\Http\Controllers\AnalyticsController::class, 'channelComparison'])
        ->name('analytics.channel-comparison');
    
    // Analytics 6: Predictive Analytics
    Route::get('/predictive-analytics', [App\Http\Controllers\AnalyticsController::class, 'predictiveAnalytics'])
        ->name('analytics.predictive-analytics');
    
    // API Endpoints for AJAX calls
    Route::group(['prefix' => 'api'], function () {
        // Partner Performance APIs
        Route::get('/partner/{id}/detail', [App\Http\Controllers\AnalyticsController::class, 'getPartnerDetail'])
            ->name('analytics.api.partner-detail');
        Route::get('/partner/{id}/history', [App\Http\Controllers\AnalyticsController::class, 'getPartnerHistory'])
            ->name('analytics.api.partner-history');
        Route::post('/partner/{id}/alert', [App\Http\Controllers\AnalyticsController::class, 'sendPartnerAlert'])
            ->name('analytics.api.send-alert');
        
        // Export APIs
        Route::get('/export/partner-performance', [App\Http\Controllers\AnalyticsController::class, 'exportPartnerPerformance'])
            ->name('analytics.api.export-partner-performance');
        Route::get('/export/inventory-recommendations', [App\Http\Controllers\AnalyticsController::class, 'exportInventoryRecommendations'])
            ->name('analytics.api.export-inventory');
        Route::get('/export/product-velocity', [App\Http\Controllers\AnalyticsController::class, 'exportProductVelocity'])
            ->name('analytics.api.export-velocity');
        Route::get('/export/profitability', [App\Http\Controllers\AnalyticsController::class, 'exportProfitability'])
            ->name('analytics.api.export-profitability');
        
        // Real-time data APIs
        Route::get('/charts/performance-trend', [App\Http\Controllers\AnalyticsController::class, 'getPerformanceTrendData'])
            ->name('analytics.api.performance-trend');
        Route::get('/charts/grade-distribution', [App\Http\Controllers\AnalyticsController::class, 'getGradeDistributionData'])
            ->name('analytics.api.grade-distribution');
        Route::get('/charts/velocity-heatmap', [App\Http\Controllers\AnalyticsController::class, 'getVelocityHeatmapData'])
            ->name('analytics.api.velocity-heatmap');
        Route::get('/charts/profitability-comparison', [App\Http\Controllers\AnalyticsController::class, 'getProfitabilityComparisonData'])
            ->name('analytics.api.profitability-comparison');
        
        // Prediction APIs
        Route::get('/predictions/demand/{toko_id}/{barang_id}', [App\Http\Controllers\AnalyticsController::class, 'getDemandPrediction'])
            ->name('analytics.api.demand-prediction');
        Route::get('/predictions/risk-scores', [App\Http\Controllers\AnalyticsController::class, 'getRiskScores'])
            ->name('analytics.api.risk-scores');
        Route::get('/predictions/seasonal-forecast', [App\Http\Controllers\AnalyticsController::class, 'getSeasonalForecast'])
            ->name('analytics.api.seasonal-forecast');
        
        // Optimization APIs
        Route::post('/optimize/inventory-allocation', [App\Http\Controllers\AnalyticsController::class, 'optimizeInventoryAllocation'])
            ->name('analytics.api.optimize-inventory');
        Route::post('/recommendations/generate', [App\Http\Controllers\AnalyticsController::class, 'generateRecommendations'])
            ->name('analytics.api.generate-recommendations');
        
        // Bulk Operations APIs
        Route::post('/bulk/send-alerts', [App\Http\Controllers\AnalyticsController::class, 'bulkSendAlerts'])
            ->name('analytics.api.bulk-alerts');
        Route::post('/bulk/update-grades', [App\Http\Controllers\AnalyticsController::class, 'bulkUpdateGrades'])
            ->name('analytics.api.bulk-update-grades');
        
        // Settings APIs
        Route::get('/settings/thresholds', [App\Http\Controllers\AnalyticsController::class, 'getAnalyticsThresholds'])
            ->name('analytics.api.get-thresholds');
        Route::post('/settings/thresholds', [App\Http\Controllers\AnalyticsController::class, 'updateAnalyticsThresholds'])
            ->name('analytics.api.update-thresholds');
    });
});


    // Route profil
    Route::middleware(['auth'])->group(function () {
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
        
        Route::prefix('pengaturan')->group(function () {
            Route::get('/edit-profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
            Route::post('/update-profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
            Route::get('/ubah-password', [App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.change-password');
            Route::post('/update-password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.update-password');
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
    Route::get('/list', [TokoController::class, 'getList'])->name('toko.list'); // ← Route yang hilang
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
        Route::get('/export', [ReturController::class, 'export'])->name('retur.export'); // Pastikan ini di atas {id}
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
    // Laporan Pemesanan Routes
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
        Route::get('/debug', [FollowUpPelangganController::class, 'debugDatabase'])->name('follow-up-pelanggan.debug');
    
    });


    // ===============================
    // MARKET MAP CRM ROUTES 
    // ===============================
    Route::group(['prefix' => 'market-map'], function() {
        // Main CRM Market Map
        Route::get('/', [MarketMapController::class, 'index'])->name('market-map.index');
        
        // Core CRM Data APIs
        Route::get('/toko-data', [MarketMapController::class, 'getTokoData'])->name('market-map.toko-data');
        Route::get('/wilayah-statistics', [MarketMapController::class, 'getWilayahStatistics'])->name('market-map.wilayah-statistics');
        Route::get('/partner-details/{tokoId}', [MarketMapController::class, 'getTokoBarang'])->name('market-map.partner-details');
        
        // CRM Intelligence & Recommendations
        Route::get('/recommendations', [MarketMapController::class, 'getRecommendations'])->name('market-map.recommendations');
        Route::get('/price-recommendations', [MarketMapController::class, 'getPriceRecommendations'])->name('market-map.price-recommendations');
        Route::get('/partner-performance', [MarketMapController::class, 'getPartnerPerformanceAnalysis'])->name('market-map.partner-performance');
        Route::get('/market-opportunities', [MarketMapController::class, 'getMarketOpportunityAnalysis'])->name('market-map.market-opportunities');
        
        // Geographic & Territory Analysis
        Route::get('/territory-analysis', [MarketMapController::class, 'getTerritoryAnalysis'])->name('market-map.territory-analysis');
        Route::get('/expansion-opportunities', [MarketMapController::class, 'getExpansionOpportunities'])->name('market-map.expansion-opportunities');
        Route::get('/competitive-analysis', [MarketMapController::class, 'getCompetitiveAnalysis'])->name('market-map.competitive-analysis');
        
        // Enhanced Geographic Data
        Route::get('/enhanced-toko-data', [MarketMapController::class, 'getEnhancedTokoData'])->name('market-map.enhanced-toko-data');
        Route::get('/grid-heatmap-data', [MarketMapController::class, 'getGridHeatmapData'])->name('market-map.grid-heatmap-data');
        Route::get('/enhanced-wilayah-stats', [MarketMapController::class, 'getEnhancedWilayahStatistics'])->name('market-map.enhanced-wilayah-stats');
        
        // Reference Data
        Route::get('/wilayah-data', [MarketMapController::class, 'getWilayahData'])->name('market-map.wilayah-data');
        Route::get('/product-list', [MarketMapController::class, 'getProductList'])->name('market-map.product-list');
        
        // CRM Export & Reporting
        Route::get('/export-crm-insights', [MarketMapController::class, 'exportCRMInsights'])->name('market-map.export-crm-insights');
        Route::get('/export-price-intelligence', [MarketMapController::class, 'exportPriceIntelligence'])->name('market-map.export-price-intelligence');
        Route::get('/export-partner-performance', [MarketMapController::class, 'exportPartnerPerformance'])->name('market-map.export-partner-performance');
        
        // Geocoding & Data Quality (kept for maintaining coordinate accuracy)
        Route::post('/bulk-geocode', [MarketMapController::class, 'bulkGeocodeTokos'])->name('market-map.bulk-geocode');
        Route::get('/geocode-status', [MarketMapController::class, 'getGeocodeStatus'])->name('market-map.geocode-status');
        Route::post('/enhanced-bulk-geocode', [MarketMapController::class, 'enhancedBulkGeocodeTokos'])->name('market-map.enhanced-bulk-geocode');
        Route::post('/fix-coordinates/{tokoId}', [MarketMapController::class, 'fixTokoCoordinates'])->name('market-map.fix-coordinates');
        
        // *** REMOVED: Toko creation routes (no longer needed for CRM focus) ***
        Route::get('/market-map/system-health', [MarketMapController::class, 'getSystemHealth'])->name('market-map.system-health');
Route::get('/market-map/territory-analysis', [MarketMapController::class, 'getTerritoryAnalysis'])->name('market-map.territory-analysis');
Route::get('/market-map/detailed-partner-analysis', [MarketMapController::class, 'getDetailedPartnerAnalysis'])->name('market-map.detailed-partner-analysis');
Route::post('/market-map/clear-cache', [MarketMapController::class, 'clearSystemCache'])->name('market-map.clear-cache');
        
        // Route::post('/store-toko', [MarketMapController::class, 'storeToko'])->name('market-map.store-toko');
    });

    // Route untuk debugging (hanya di development)
    if (app()->environment(['local', 'development'])) {
        Route::group(['prefix' => 'debug/market-map'], function() {
            Route::get('/test-geocoding', [MarketMapController::class, 'testGeocodingService']);
            Route::get('/coordinate-stats', [MarketMapController::class, 'getCoordinateStatistics']);
            Route::get('/validate-coordinates', [MarketMapController::class, 'validateAllCoordinates']);
            Route::get('/crm-test-data', [MarketMapController::class, 'generateTestCRMData']);
        });
    }
});
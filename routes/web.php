<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardInventoryOptimizationController;
use App\Http\Controllers\DashboardPartnerPerformanceController;
use App\Http\Controllers\PartnerPerformanceController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\BarangTokoController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\FollowUpPelangganController;
use App\Http\Controllers\EoqSettingController;
use App\Http\Controllers\ZscoreSettingController;
use App\Http\Controllers\KonfigurasiIntervalKirimController;
use App\Http\Controllers\DashboardMonitorController;

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

    // Forgot Password Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});


// Token authentication route dengan rate limiting
Route::middleware(['throttle:10,1'])->group(function () {
    Route::get('/auth/{token}', [LoginController::class, 'loginViaToken'])->name('auth.token');
});

// Route yang memerlukan autentikasi dengan prevent.back middleware
Route::middleware(['auth', 'prevent.back', 'verifysession', 'session.timeout', 'check.user.role'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // ===============================
    // DASHBOARD ROUTES
    // ===============================

    // Resolver: redirect user ke dashboard yang sesuai dengan permission-nya
    Route::get('/', [DashboardInventoryOptimizationController::class, 'resolveDashboard'])->name('dashboard.index');
    Route::get('/dashboard', [DashboardInventoryOptimizationController::class, 'resolveDashboard'])->name('dashboard');

    Route::prefix('dashboard')->group(function () {

        // ------------------------------------------
        // Dashboard: Inventory Optimization
        // ------------------------------------------
        Route::middleware('can:view-dashboard-inventory-optimization')->group(function () {
            Route::get('/inventory-optimization', [DashboardInventoryOptimizationController::class, 'index'])
                ->name('dashboard.inventory-optimization');

            // API endpoints Inventory Optimization
            Route::prefix('api/inventory-optimization')->group(function () {
                Route::get('/auto-refresh', [DashboardInventoryOptimizationController::class, 'autoRefreshInventoryOptimization'])
                    ->name('dashboard.api.inventory-optimization.auto-refresh');
            });
        });

        // ------------------------------------------
        // Dashboard: Partner Performance
        // ------------------------------------------
        Route::middleware('can:view-dashboard-partner-performance')->group(function () {
            Route::get('/partner-performance', [DashboardPartnerPerformanceController::class, 'index'])
                ->name('dashboard.partner-performance');
            Route::get('/statistik', [DashboardPartnerPerformanceController::class, 'getStatistikRingkasan']);
            Route::get('/grafik-pengiriman', [DashboardPartnerPerformanceController::class, 'getGrafikPengiriman']);
            Route::get('/barang-analysis', [DashboardPartnerPerformanceController::class, 'getBarangLakuTidakLaku'])
                ->name('dashboard.api.barang-analysis');
            Route::get('/transaksi-terbaru', [DashboardPartnerPerformanceController::class, 'getTransaksiTerbaru']);
            Route::get('/toko-retur-terbanyak', [DashboardPartnerPerformanceController::class, 'getTokoReturTerbanyak']);
        });

        // ===============================
        // DASHBOARD MONITOR
        // ===============================
        Route::group(['prefix' => 'dashboard-monitor', 'middleware' => ['role:Admin|admin|Superadmin|superadmin|Administrator|administrator']], function () {
            Route::get('/', [DashboardMonitorController::class, 'index'])->name('dashboard-monitor.index');
            Route::get('/data', [DashboardMonitorController::class, 'getData'])->name('dashboard-monitor.data');
            Route::get('/modules', [DashboardMonitorController::class, 'modules'])->name('dashboard-monitor.modules');
            Route::post('/truncate', [DashboardMonitorController::class, 'truncate'])->name('dashboard-monitor.truncate');
            // Laravel Log Management
            Route::get('/laravel-log/info', [DashboardMonitorController::class, 'laravelLogInfo'])->name('dashboard-monitor.laravel-log.info');
            Route::get('/laravel-log/export', [DashboardMonitorController::class, 'exportLaravelLog'])->name('dashboard-monitor.laravel-log.export');
            Route::post('/laravel-log/truncate', [DashboardMonitorController::class, 'truncateLaravelLog'])->name('dashboard-monitor.laravel-log.truncate');
            // SQL Import
            Route::get('/sql-import/tables', [DashboardMonitorController::class, 'getAllowedTablesList'])->name('dashboard-monitor.sql-import.tables');
            Route::get('/sql-import/columns', [DashboardMonitorController::class, 'getTableColumns'])->name('dashboard-monitor.sql-import.columns');
            Route::post('/sql-import/execute', [DashboardMonitorController::class, 'executeSqlImport'])->name('dashboard-monitor.sql-import.execute');
            // Wildcard /{id} harus di paling akhir agar tidak menangkap route lain
            Route::get('/{id}', [DashboardMonitorController::class, 'show'])->name('dashboard-monitor.show');
        });
    });

    // ===============================
    // ANALYTICS ROUTES - CORE 4 MODULES ONLY
    // ===============================
    Route::prefix('analytics')->name('analytics.')->group(function () {
        // ===== ANALYTICS 1: PARTNER PERFORMANCE (admin/ketua/AP) =====
        Route::prefix('partner-performance')->name('partner-performance.')->middleware('can:view-partner-performance')->group(function () {
            Route::get('/', [PartnerPerformanceController::class, 'index'])->name('index');
            Route::get('/dashboard', [PartnerPerformanceController::class, 'dashboard'])->name('dashboard');

            // API Routes
            Route::get('/api/data', [PartnerPerformanceController::class, 'getData'])
                ->name('api.data');
            Route::get('/api/trends', [PartnerPerformanceController::class, 'getTrends'])
                ->name('api.trends');
            Route::get('/api/statistics', [PartnerPerformanceController::class, 'getStatistics'])
                ->name('api.statistics');
            Route::get('/api/search', [PartnerPerformanceController::class, 'searchPartners'])
                ->name('api.search');

            // Partner Actions
            Route::get('/history/{partnerId}', [PartnerPerformanceController::class, 'getPartnerHistory'])
                ->name('history');
            Route::post('/alert/{partnerId}', [PartnerPerformanceController::class, 'sendPartnerAlert'])
                ->name('alert');
            Route::post('/bulk-alerts', [PartnerPerformanceController::class, 'sendBulkAlerts'])
                ->name('bulk-alerts');

            // Export & Reports
            Route::post('/generate-report', [PartnerPerformanceController::class, 'generateReport'])
                ->name('generate-report');
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
    Route::group(['prefix' => 'barang', 'middleware' => 'can:view-barang'], function () {
        Route::get('/', [BarangController::class, 'index'])->name('barang.index');
        Route::get('/data', [BarangController::class, 'getData'])->name('barang.data');
        Route::get('/generate-kode', [BarangController::class, 'generateKode'])->middleware('can:create-barang')->name('barang.generateKode');
        Route::post('/store', [BarangController::class, 'store'])->middleware('can:create-barang')->name('barang.store');
        Route::get('/{id}/edit', [BarangController::class, 'edit'])->middleware('can:edit-barang')->name('barang.edit');
        Route::put('/update/{id}', [BarangController::class, 'update'])->middleware('can:edit-barang')->name('barang.update');
        Route::delete('/destroy/{id}', [BarangController::class, 'destroy'])->middleware('can:delete-barang')->name('barang.destroy');
        Route::get('/list', [BarangController::class, 'getList'])->name('barang.list');

        // Stok Barang endpoints
        Route::get('/{id}/stok', [BarangController::class, 'getStokBarang'])->name('barang.stok');
        Route::post('/stok/store', [BarangController::class, 'storeStok'])->middleware('can:edit-barang')->name('barang.stok.store');
        Route::get('/stok/{id}/edit', [BarangController::class, 'editStok'])->middleware('can:edit-barang')->name('barang.stok.edit');
        Route::post('/stok/update/{id}', [BarangController::class, 'updateStok'])->middleware('can:edit-barang')->name('barang.stok.update');

        // Stock management endpoints
        Route::get('/{id}/stock-info', [BarangController::class, 'getStockInfo'])->name('barang.stockInfo');
        Route::post('/validate-stock', [BarangController::class, 'validateStock'])->name('barang.validateStock');

        // FIFO Stock Management endpoints
        Route::post('/{id}/tambah-stok', [BarangController::class, 'storeTambahStok'])->middleware('can:edit-barang')->name('barang.store-tambah-stok');
    });


    Route::prefix('toko')->middleware('can:view-toko')->group(function () {
        // Basic CRUD routes
        Route::get('/', [TokoController::class, 'index'])->name('toko.index');
        Route::get('/list', [TokoController::class, 'getList'])->name('toko.list');
        Route::get('/data', [TokoController::class, 'getData'])->name('toko.data');
        Route::get('/generate-kode', [TokoController::class, 'generateKode'])->middleware('can:create-toko')->name('toko.generateKode');
        Route::post('/', [TokoController::class, 'store'])->middleware('can:create-toko')->name('toko.store');

        // Nominatim API routes (NEW - Simplified address search)
        Route::get('/search-address', [TokoController::class, 'searchAddress'])->name('toko.searchAddress');
        Route::get('/reverse-geocode', [TokoController::class, 'reverseGeocode'])->name('toko.reverseGeocode');
        Route::get('/boundary', [TokoController::class, 'getBoundary'])->name('toko.boundary');

        // Wilayah routes (kept for backward compatibility with dropdowns)
        Route::get('/wilayah/kota', [TokoController::class, 'getWilayahKota'])->name('toko.wilayah.kota');
        Route::get('/wilayah/kecamatan', [TokoController::class, 'getKecamatanByKota'])->name('toko.wilayah.kecamatan');
        Route::get('/wilayah/kelurahan', [TokoController::class, 'getKelurahanByKecamatan'])->name('toko.wilayah.kelurahan');

        // Coordinate validation route
        Route::post('/validate-coordinates', [TokoController::class, 'validateMapCoordinates'])->name('toko.validateCoordinates');

        // Parameterized routes (MUST be last)
        Route::get('/{id}', [TokoController::class, 'show'])->name('toko.show');
        Route::get('/{id}/edit', [TokoController::class, 'edit'])->middleware('can:edit-toko')->name('toko.edit');
        Route::get('/{id}/coordinate-details', [TokoController::class, 'getCoordinateDetails'])->name('toko.coordinateDetails');
        Route::put('/{id}', [TokoController::class, 'update'])->middleware('can:edit-toko')->name('toko.update');
        Route::delete('/{id}', [TokoController::class, 'destroy'])->middleware('can:delete-toko')->name('toko.destroy');
    });

    Route::prefix('barang-toko')->middleware('can:view-barang-toko')->group(function () {
        Route::get('/', [BarangTokoController::class, 'index'])->name('barang-toko.index');
        Route::get('/getBarangToko', [BarangTokoController::class, 'getBarangToko'])->name('barang-toko.getBarangToko');
        Route::get('/getAvailableBarang', [BarangTokoController::class, 'getAvailableBarang'])->middleware('can:create-barang-toko')->name('barang-toko.getAvailableBarang');
        Route::post('/', [BarangTokoController::class, 'store'])->middleware('can:create-barang-toko')->name('barang-toko.store');
        Route::get('/{id}/edit', [BarangTokoController::class, 'edit'])->middleware('can:edit-barang-toko')->name('barang-toko.edit');
        Route::put('/{id}', [BarangTokoController::class, 'update'])->middleware('can:edit-barang-toko')->name('barang-toko.update');
        Route::delete('/{id}', [BarangTokoController::class, 'destroy'])->middleware('can:delete-barang-toko')->name('barang-toko.destroy');
    });

    Route::group(['prefix' => 'customer', 'middleware' => 'can:view-customer'], function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customer.index');
        Route::get('/data', [CustomerController::class, 'getData'])->name('customer.data');
        Route::post('/', [CustomerController::class, 'store'])->middleware('can:create-customer')->name('customer.store');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->middleware('can:edit-customer')->name('customer.edit');
        Route::put('/{id}', [CustomerController::class, 'update'])->middleware('can:edit-customer')->name('customer.update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->middleware('can:delete-customer')->name('customer.destroy');
        Route::post('/import', [CustomerController::class, 'import'])->middleware('can:create-customer')->name('customer.import');
        Route::post('/sync-pemesanan', [CustomerController::class, 'syncFromPemesanan'])->middleware('can:create-customer')->name('customer.syncPemesanan');
    });

    // ===============================
    // USER MANAGEMENT ROUTES (Menu Sistem)
    // ===============================
    Route::group(['prefix' => 'user', 'middleware' => 'can:manage-users'], function () {
        Route::get('/', [UserController::class, 'index'])->name('user.index');
        Route::get('/data', [UserController::class, 'getData'])->name('user.data');
        Route::post('/', [UserController::class, 'store'])->name('user.store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('user.destroy');
    });

    // ===============================
    // ROLE MANAGEMENT ROUTES (Menu Sistem)
    // ===============================
    Route::group(['prefix' => 'role', 'middleware' => 'can:manage-users'], function () {
        Route::get('/', [\App\Http\Controllers\RoleController::class, 'index'])->name('role.index');
        Route::get('/data', [\App\Http\Controllers\RoleController::class, 'getData'])->name('role.data');
        Route::post('/', [\App\Http\Controllers\RoleController::class, 'store'])->name('role.store');
        Route::get('/{id}/edit', [\App\Http\Controllers\RoleController::class, 'edit'])->name('role.edit');
        Route::put('/{id}', [\App\Http\Controllers\RoleController::class, 'update'])->name('role.update');
        Route::delete('/{id}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('role.destroy');
    });

    // Route Transaksi
    Route::group(['prefix' => 'pengiriman', 'middleware' => 'can:view-pengiriman'], function () {
        Route::get('/', [PengirimanController::class, 'index'])->name('pengiriman.index');
        Route::post('/list', [PengirimanController::class, 'list'])->name('pengiriman.list');
        Route::get('/get_nomer', [PengirimanController::class, 'get_nomer'])->middleware('can:create-pengiriman')->name('pengiriman.getNomer');
        Route::get('/get_barang', [PengirimanController::class, 'get_barang'])->middleware('can:create-pengiriman')->name('pengiriman.getBarang');
        Route::get('/create_ajax', [PengirimanController::class, 'create_ajax'])->middleware('can:create-pengiriman')->name('pengiriman.createAjax');
        Route::post('/ajax', [PengirimanController::class, 'ajax'])->middleware('can:create-pengiriman')->name('pengiriman.storeAjax');
        Route::get('/{nomer}/show_ajax', [PengirimanController::class, 'show_ajax'])->name('pengiriman.showAjax');
        Route::post('/{nomer}/update_status', [PengirimanController::class, 'update_status'])->middleware('can:edit-pengiriman')->name('pengiriman.updateStatus');
        Route::get('/{nomer}/print', [PengirimanController::class, 'print'])->name('pengiriman.print');
    });

    Route::group(['prefix' => 'retur', 'middleware' => 'can:view-retur'], function () {
        Route::get('/', [ReturController::class, 'index'])->name('retur.index');
        Route::get('/data', [ReturController::class, 'getData'])->name('retur.data');
        Route::post('/store', [ReturController::class, 'store'])->middleware('can:create-retur')->name('retur.store');
        Route::get('/{nomerPengiriman}', [ReturController::class, 'show'])->name('retur.show');
    });

    Route::group(['prefix' => 'pemesanan', 'middleware' => 'can:view-pemesanan'], function () {
        Route::get('/', [PemesananController::class, 'index'])->name('pemesanan.index');
        Route::get('/data', [PemesananController::class, 'getData'])->name('pemesanan.data');
        Route::get('/get-id', [PemesananController::class, 'getPemesananId'])->middleware('can:create-pemesanan')->name('pemesanan.getId');
        Route::post('/store', [PemesananController::class, 'store'])->middleware('can:create-pemesanan')->name('pemesanan.store');
        Route::get('/{id}', [PemesananController::class, 'show'])->name('pemesanan.show');
        Route::put('/{id}', [PemesananController::class, 'update'])->middleware('can:edit-pemesanan')->name('pemesanan.update');
        Route::delete('/{id}', [PemesananController::class, 'destroy'])->middleware('can:delete-pemesanan')->name('pemesanan.destroy');
    });

    // Route Follow Up Pelanggan (Complete with WhatsApp Integration)
    Route::group(['prefix' => 'follow-up-pelanggan', 'middleware' => 'can:view-follow-up'], function () {
        Route::get('/', [FollowUpPelangganController::class, 'index'])->name('follow-up-pelanggan.index');
        // Customer data endpoints
        Route::get('/filtered-customers', [FollowUpPelangganController::class, 'getFilteredCustomers'])->name('follow-up-pelanggan.filtered-customers');

        // Follow up actions
        Route::post('/send', [FollowUpPelangganController::class, 'sendFollowUp'])->middleware('can:create-follow-up')->name('follow-up-pelanggan.send');

        // History and tracking
        Route::get('/history', [FollowUpPelangganController::class, 'getHistory'])->name('follow-up-pelanggan.history');

        // File handling
        Route::post('/upload-image', [FollowUpPelangganController::class, 'uploadImage'])->middleware('can:create-follow-up')->name('follow-up-pelanggan.upload-image');

        // WhatsApp device management
        Route::get('/device-status', [FollowUpPelangganController::class, 'getDeviceStatus'])->name('follow-up-pelanggan.device-status');
        Route::post('/test-connection', [FollowUpPelangganController::class, 'testWhatsAppConnection'])->middleware('can:edit-follow-up')->name('follow-up-pelanggan.test-connection');

        // Debug route: keep named route for internal tooling, but restrict to privileged users.
        Route::get('/debug', [FollowUpPelangganController::class, 'debugDatabase'])
            ->middleware('can:edit-follow-up')
            ->name('follow-up-pelanggan.debug');
    });

    // ===============================
    // NOTIFICATION ROUTES (Real-time, no database)
    // ===============================
    Route::get('/notifications/get', [\App\Http\Controllers\NotificationController::class, 'getNotifications'])
        ->middleware('can:view-notifications')
        ->name('notifications.get');

    // ===============================
    // NOTIFICATION SETTINGS ROUTES
    // ===============================
    Route::group(['prefix' => 'notification-settings', 'middleware' => 'can:manage-notification-settings'], function () {
        Route::get('/', [\App\Http\Controllers\NotificationSettingController::class, 'index'])->name('notification-settings.index');
        Route::put('/', [\App\Http\Controllers\NotificationSettingController::class, 'update'])->name('notification-settings.update');
        Route::post('/reset', [\App\Http\Controllers\NotificationSettingController::class, 'reset'])->name('notification-settings.reset');
        Route::get('/api', [\App\Http\Controllers\NotificationSettingController::class, 'getSettingsApi'])->name('notification-settings.api');
    });

    // ===============================
    // EOQ SETTINGS ROUTES
    // ===============================
    Route::group(['prefix' => 'eoq-setting', 'middleware' => 'can:view-eoq-setting'], function () {
        Route::get('/', [\App\Http\Controllers\EoqSettingController::class, 'index'])->name('eoq-setting.index');

        // Biaya Pesan Global
        Route::get('/biaya-pesan-global', [\App\Http\Controllers\EoqSettingController::class, 'getBiayaPesanGlobal'])->name('eoq-setting.biaya-pesan-global.get');
        Route::post('/biaya-pesan-global', [\App\Http\Controllers\EoqSettingController::class, 'storeBiayaPesanGlobal'])->middleware('can:create-eoq-setting')->name('eoq-setting.biaya-pesan-global.store');
        Route::get('/biaya-pesan-global/{id}/edit', [\App\Http\Controllers\EoqSettingController::class, 'editBiayaPesanGlobal'])->middleware('can:edit-eoq-setting')->name('eoq-setting.biaya-pesan-global.edit');
        Route::put('/biaya-pesan-global/{id}', [\App\Http\Controllers\EoqSettingController::class, 'updateBiayaPesanGlobal'])->middleware('can:edit-eoq-setting')->name('eoq-setting.biaya-pesan-global.update');
        Route::delete('/biaya-pesan-global/{id}', [\App\Http\Controllers\EoqSettingController::class, 'destroyBiayaPesanGlobal'])->middleware('can:delete-eoq-setting')->name('eoq-setting.biaya-pesan-global.destroy');

        // Biaya Pesan Toko
        Route::get('/biaya-pesan-toko/{tokoId}', [\App\Http\Controllers\EoqSettingController::class, 'getBiayaPesanToko'])->name('eoq-setting.biaya-pesan-toko.get');
        Route::post('/biaya-pesan-toko', [\App\Http\Controllers\EoqSettingController::class, 'storeBiayaPesanToko'])->middleware('can:create-eoq-setting')->name('eoq-setting.biaya-pesan-toko.store');
        Route::delete('/biaya-pesan-toko/{id}', [\App\Http\Controllers\EoqSettingController::class, 'destroyBiayaPesanToko'])->middleware('can:delete-eoq-setting')->name('eoq-setting.biaya-pesan-toko.destroy');

        // Biaya Simpan
        Route::get('/biaya-simpan/{barangId}', [\App\Http\Controllers\EoqSettingController::class, 'getBiayaSimpan'])->name('eoq-setting.biaya-simpan.get');
        Route::post('/biaya-simpan', [\App\Http\Controllers\EoqSettingController::class, 'storeBiayaSimpan'])->middleware('can:create-eoq-setting')->name('eoq-setting.biaya-simpan.store');
        Route::get('/biaya-simpan/{id}/edit', [\App\Http\Controllers\EoqSettingController::class, 'editBiayaSimpan'])->middleware('can:edit-eoq-setting')->name('eoq-setting.biaya-simpan.edit');
        Route::put('/biaya-simpan/{id}', [\App\Http\Controllers\EoqSettingController::class, 'updateBiayaSimpan'])->middleware('can:edit-eoq-setting')->name('eoq-setting.biaya-simpan.update');
        Route::delete('/biaya-simpan/{id}', [\App\Http\Controllers\EoqSettingController::class, 'destroyBiayaSimpan'])->middleware('can:delete-eoq-setting')->name('eoq-setting.biaya-simpan.destroy');
    });

    // ===============================
    // KONFIGURASI INTERVAL KIRIM ROUTES
    // ===============================
    // Catatan: otorisasi ditangani manual di controller (safe terhadap permission yang belum di-seed)
    Route::group(['prefix' => 'config-interval-kirim', 'middleware' => 'auth'], function () {
        Route::get('/', [KonfigurasiIntervalKirimController::class, 'index'])->name('config-interval-kirim.index');
        Route::get('/data', [KonfigurasiIntervalKirimController::class, 'show'])->name('config-interval-kirim.show');
        Route::put('/', [KonfigurasiIntervalKirimController::class, 'update'])->name('config-interval-kirim.update');
    });

    // ===============================
    // ZSCORE SETTINGS ROUTES
    // ===============================
    Route::group(['prefix' => 'zscore-setting', 'middleware' => 'can:view-zscore-setting'], function () {
        Route::get('/', [\App\Http\Controllers\ZscoreSettingController::class, 'index'])->name('zscore-setting.index');
        Route::get('/data', [\App\Http\Controllers\ZscoreSettingController::class, 'getData'])->name('zscore-setting.data');
        Route::get('/barang-by-toko/{tokoId}', [\App\Http\Controllers\ZscoreSettingController::class, 'getBarangByToko'])->name('zscore-setting.barang-by-toko');
        Route::post('/', [\App\Http\Controllers\ZscoreSettingController::class, 'store'])->middleware('can:create-zscore-setting')->name('zscore-setting.store');
        Route::get('/{id}/edit', [\App\Http\Controllers\ZscoreSettingController::class, 'edit'])->middleware('can:edit-zscore-setting')->name('zscore-setting.edit');
        Route::put('/{id}', [\App\Http\Controllers\ZscoreSettingController::class, 'update'])->middleware('can:edit-zscore-setting')->name('zscore-setting.update');
        Route::delete('/{id}', [\App\Http\Controllers\ZscoreSettingController::class, 'destroy'])->middleware('can:delete-zscore-setting')->name('zscore-setting.destroy');
        Route::post('/{id}/set-active', [\App\Http\Controllers\ZscoreSettingController::class, 'setActive'])->name('zscore-setting.set-active')->middleware('can:edit-zscore-setting');
    });
});

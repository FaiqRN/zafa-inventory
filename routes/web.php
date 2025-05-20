<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\BarangTokoController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\LaporanTokoController;
use App\Http\Controllers\LaporanPemesananController;

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
    
    Route::get('/toko/data', [TokoController::class, 'getData'])->name('toko.data');
    Route::get('/toko/list', [TokoController::class, 'getList'])->name('toko.list');
    Route::get('/toko/generate-kode', [TokoController::class, 'generateKode'])->name('toko.generateKode');

    // Routes untuk Wilayah Dropdown
    Route::get('/toko/wilayah/kota', [TokoController::class, 'getWilayahKota'])->name('toko.wilayah.kota');
    Route::get('/toko/wilayah/kecamatan', [TokoController::class, 'getKecamatanByKota'])->name('toko.wilayah.kecamatan');
    Route::get('/toko/wilayah/kelurahan', [TokoController::class, 'getKelurahanByKecamatan'])->name('toko.wilayah.kelurahan');

    Route::resource('toko', TokoController::class);
    
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
    
    Route::get('/analytics', function () {
        return view('analytics', [
            'activemenu' => 'analytics',
            'breadcrumb' => (object) [
                'title' => 'analytics',
                'list' => ['Home', 'Laporan', 'analytics']
            ]
        ]);
    })->name('analytics');
});
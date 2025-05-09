<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\BarangTokoController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\LaporanTokoController;

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
    Route::get('/', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    
    // Route profil
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    
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
        Route::get('/data', [PengirimanController::class, 'getData'])->name('pengiriman.data');
        Route::get('/get-nomer', [PengirimanController::class, 'getNomerPengiriman'])->name('pengiriman.getNomerPengiriman');
        Route::get('/get-barang-by-toko', [PengirimanController::class, 'getBarangByToko'])->name('pengiriman.getBarangByToko');
        Route::put('/{id}/update-status', [PengirimanController::class, 'updateStatus'])->name('pengiriman.updateStatus');
        Route::get('/pengiriman/export', [PengirimanController::class, 'export'])->name('pengiriman.export');
        Route::get('/list', [PengirimanController::class, 'getList'])->name('pengiriman.list');
    });
    Route::resource('pengiriman', PengirimanController::class);
    
    Route::group(['prefix' => 'retur'], function() {
        Route::get('/', [ReturController::class, 'index'])->name('retur.index');
        Route::get('/data', [ReturController::class, 'getData'])->name('retur.data');
        Route::get('/get-pengiriman', [ReturController::class, 'getPengiriman'])->name('retur.getPengiriman');
        Route::post('/store', [ReturController::class, 'store'])->name('retur.store');
        Route::get('/{id}', [ReturController::class, 'show'])->name('retur.show');
        Route::delete('/{id}', [ReturController::class, 'destroy'])->name('retur.destroy');
        Route::get('/export', [ReturController::class, 'export'])->name('retur.export');
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
    Route::get('/laporan-penjualan', function () {
        return view('laporan.penjualan', [
            'activemenu' => 'laporan-penjualan',
            'breadcrumb' => (object) [
                'title' => 'Laporan Penjualan',
                'list' => ['Home', 'Laporan', 'Laporan Penjualan']
            ]
        ]);
    })->name('laporan.penjualan');
    
Route::get('/laporan-toko', [LaporanTokoController::class, 'index'])->name('laporan.toko');
Route::get('/laporan-toko/data', [LaporanTokoController::class, 'getData'])->name('laporan.toko.data');
Route::post('/laporan-toko/update-catatan', [LaporanTokoController::class, 'updateCatatan'])->name('laporan.toko.updateCatatan');
Route::get('/laporan-toko/detail', [LaporanTokoController::class, 'getDetailData'])->name('laporan.toko.detail');
    
    Route::get('/laporan-barang', function () {
        return view('laporan.barang', [
            'activemenu' => 'laporan-barang',
            'breadcrumb' => (object) [
                'title' => 'Laporan Per Barang',
                'list' => ['Home', 'Laporan', 'Laporan Per Barang']
            ]
        ]);
    })->name('laporan.barang');
});
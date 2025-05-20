@extends('layouts.template')

@section('page_title', 'Dashboard')

@php
    $activemenu = 'dashboard';
    $breadcrumb = (object) [
        'title' => 'Dashboard Zafa Potato',
        'list' => ['Home', 'Dashboard']
    ];
@endphp

@push('css')
<style>
/* Enhanced Dashboard Styles */
.small-box {
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}
.small-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.small-box .icon {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 1;
}
.small-box .icon i {
    font-size: 50px;
    color: rgba(255,255,255,0.3);
}
.dashboard-card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
    transition: all 0.3s ease;
}
.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.chart-container {
    position: relative;
    height: 300px;
    padding: 15px;
}
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,0.03);
    transform: scale(1.01);
    transition: all 0.2s ease;
}
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.9);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}
.animate-number {
    transition: all 0.5s ease;
}
.badge-enhanced {
    padding: 8px 12px;
    border-radius: 15px;
    font-weight: 500;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <h4 class="mt-3 text-primary">Memuat Dashboard...</h4>
    </div>

    <!-- Welcome Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info dashboard-card" style="background: linear-gradient(135deg, #eb7d07, #f14c05); border: none; color: white;">
                <h4><i class="fas fa-tachometer-alt mr-2"></i>Dashboard CRM Zafa Potato</h4>
                <p class="mb-0">Monitor penjualan dan pengiriman kentang olahan secara real-time</p>
            </div>
        </div>
    </div>
    
    
    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-md-8">
            <!-- Grafik Pengiriman Chart - Enhanced -->
            <div class="card dashboard-card">
                <div class="card-header bg-primary">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        Grafik Pengiriman Barang
                    </h3>
                    <div class="card-tools">
                        <select class="form-control form-control-sm" id="filter-tahun" style="width: 120px;">
                            @for($i = date('Y') - 2; $i <= date('Y'); $i++)
                                <option value="{{ $i }}" {{ date('Y') == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="pengiriman-chart"></canvas>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <!-- Pengiriman Terbaru - Enhanced -->
            <div class="card dashboard-card">
                <div class="card-header border-transparent bg-info">
                    <h3 class="card-title text-white">
                        <i class="fas fa-history mr-2"></i>Transaksi Pengiriman Terbaru
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-light btn-sm" id="refresh-transaksi">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover m-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                            <th>No. Transaksi</th>
                            <th>Customer</th>
                            <th>Barang</th>
                            <th>Tanggal</th>
                            <th class="text-center">Qty & Harga</th>
                            <th class="text-center">Status</th>
                            </tr>
                        </thead>
                            <tbody id="table-transaksi">
                                <!-- Data akan dimuat via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div id="empty-transaksi" class="empty-state" style="display: none;">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada transaksi pengiriman terbaru</p>
                    </div>
                </div>
                <div class="card-footer clearfix">
                    <a href="{{ route('pengiriman.index') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-list mr-1"></i>Lihat Semua Pengiriman
                    </a>
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
        
        <div class="col-md-4">
            <!-- Analisis Barang - Enhanced (Bar Chart) -->
            <div class="card dashboard-card">
                <div class="card-header bg-success">
                    <h3 class="card-title text-white">
                        <i class="fas fa-chart-bar mr-2"></i>Analisis Barang
                    </h3>
                    <div class="card-tools">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-light dropdown-toggle" data-toggle="dropdown" id="filter-barang-text">
                                Barang Laku
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item filter-barang" href="#" data-filter="laku">
                                    <i class="fas fa-thumbs-up text-success mr-1"></i>Barang Laku
                                </a>
                                <a class="dropdown-item filter-barang" href="#" data-filter="tidak_laku">
                                    <i class="fas fa-thumbs-down text-danger mr-1"></i>Barang Tidak Laku
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="chart-barang"></canvas>
                    </div>
                    <div id="empty-barang" class="empty-state" style="display: none;">
                        <i class="fas fa-box-open"></i>
                        <p>Tidak ada data barang</p>
                    </div>
                </div>
            </div>
            <!-- /.card -->

            <!-- Toko Retur Terbanyak - Enhanced -->
            <div class="card dashboard-card">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Toko Retur Terbanyak
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-hover table-sm m-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Toko</th>
                                    <th class="text-center">Retur</th>
                                    <th class="text-center">%</th>
                                    <th class="text-center">Rating</th>
                                </tr>
                            </thead>
                            <tbody id="table-retur">
                                <!-- Data akan dimuat via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div id="empty-retur" class="empty-state" style="display: none;">
                        <i class="fas fa-award"></i>
                        <p>Tidak ada data retur</p>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('retur.index') }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-undo-alt mr-1"></i>Kelola Retur
                    </a>
                </div>
            </div>
            <!-- /.card -->

            <!-- Info Cards Harian -->
            <div class="row">
                <div class="col-12">
                    <div class="info-box bg-gradient-info dashboard-card">
                        <span class="info-box-icon">
                            <i class="fas fa-calendar-day"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Hari Ini</span>
                            <span class="info-box-number">
                                Pengiriman: <span id="pengiriman-hari">0</span> | 
                                Pemesanan: <span id="pemesanan-hari">0</span>
                            </span>
                            <div class="progress">
                                <div class="progress-bar bg-white" style="width: 70%; opacity: 0.4;"></div>
                            </div>
                            <span class="progress-description">
                                Data real-time sistem
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>
@endsection

@push('js')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.1/dist/chart.min.js"></script>
<!-- Dashboard JavaScript (External File) -->
<script src="{{ asset('js/dashboard.js') }}?v={{ time() }}"></script>
@endpush
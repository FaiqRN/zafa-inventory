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
    background-color: rgba(0,123,255,0.1);
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
    font-weight: bold;
}
.badge-enhanced {
    padding: 8px 12px;
    border-radius: 15px;
    font-weight: 500;
}

/* ========== UPDATED STATS CARD STYLES ========== */
.stats-card {
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.18);
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}

/* Card 1 - Total Barang (Orange) */
.stats-card.card-orange {
    background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
    color: white;
}

/* Card 2 - Total Toko Partner (Yellow/Gold) */
.stats-card.card-yellow {
    background: linear-gradient(135deg, #FFB347 0%, #FFA500 100%);
    color: white;
}

/* Card 3 - Pengiriman Bulan Ini (Turquoise) */
.stats-card.card-turquoise {
    background: linear-gradient(135deg, #06BCC1 0%, #0FA4A8 100%);
    color: white;
}

/* Card 4 - Retur Bulan Ini (Coral) */
.stats-card.card-coral {
    background: linear-gradient(135deg, #F4845F 0%, #E86A47 100%);
    color: white;
}

.stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
    line-height: 1;
    position: relative;
    z-index: 2;
}

.stats-label {
    font-size: 0.95rem;
    opacity: 0.95;
    font-weight: 500;
    letter-spacing: 0.3px;
    position: relative;
    z-index: 2;
}

.stats-card .stats-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 4rem;
    opacity: 0.15;
    z-index: 1;
}

/* Decorative elements */
.stats-card::after {
    content: '';
    position: absolute;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    bottom: -30px;
    right: -30px;
    background: rgba(255,255,255,0.1);
    z-index: 1;
}

/* ========== END STATS CARD STYLES ========== */

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.fade-in {
    animation: fadeInUp 0.5s ease-out;
}
.card-header .btn-group {
    margin-left: auto;
}
/* Fix untuk dropdown filter */
.dropdown-menu {
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
}
.dropdown-item:hover {
    background-color: #f8f9fa;
}
.dropdown-toggle::after {
    margin-left: 0.5em;
}
</style>
@endpush

@section('content')
<div class="container-fluid">

    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <div class="col-md-8">
            <!-- Grafik Pengiriman Chart - Enhanced -->
            <div class="card dashboard-card fade-in">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-white">
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
            <div class="card dashboard-card fade-in">
                <div class="card-header border-transparent bg-info d-flex justify-content-between align-items-center">
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
                                    <th>Customer/Toko</th>
                                    <th>Barang</th>
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
            <div class="card dashboard-card fade-in">
                <div class="card-header bg-success d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-white">
                        <i class="fas fa-chart-bar mr-2"></i>Analisis Barang
                    </h3>
                    <div class="card-tools">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="filter-barang-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span id="filter-barang-text">Barang Laku</span>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="filter-barang-dropdown">
                                <a class="dropdown-item filter-barang" href="#" data-filter="laku">
                                    <i class="fas fa-thumbs-up text-success mr-2"></i>Barang Laku
                                </a>
                                <a class="dropdown-item filter-barang" href="#" data-filter="tidak_laku">
                                    <i class="fas fa-thumbs-down text-danger mr-2"></i>Barang Kurang Laku
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
            <div class="card dashboard-card fade-in">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Toko Retur Terbanyak
                    </h3>
                    <small class="text-muted">12 bulan terakhir</small>
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
                    <div class="info-box bg-gradient-info dashboard-card fade-in">
                        <span class="info-box-icon">
                            <i class="fas fa-calendar-day"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Aktivitas Hari Ini</span>
                            <span class="info-box-number">
                                Pengiriman: <span id="pengiriman-hari" class="animate-number">0</span> | 
                                Pemesanan: <span id="pemesanan-hari" class="animate-number">0</span>
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
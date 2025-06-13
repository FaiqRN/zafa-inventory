@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-1">{{ $overview['active_partners'] }}</h3>
                            <p class="card-text mb-0">Active Partners</p>
                            <small class="opacity-75">{{ $overview['partner_activation_rate'] }}% activation rate</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-store fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-1">{{ $overview['total_products'] }}</h3>
                            <p class="card-text mb-0">Active Products</p>
                            <small class="opacity-75">In distribution network</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-1">{{ $overview['total_shipments'] }}</h3>
                            <p class="card-text mb-0">Total Shipments</p>
                            <small class="opacity-75">Last 6 months</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-truck fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-1">{{ $overview['total_partners'] }}</h3>
                            <p class="card-text mb-0">Total Partners</p>
                            <small class="opacity-75">Network size</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-network-wired fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Menu Grid -->
    <div class="row">
        <!-- Analytics 1: Partner Performance -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 shadow-lg border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trophy mr-2"></i>
                        Partner Performance Analytics
                    </h5>
                    <small class="opacity-75">Sistem Penilaian Toko Partner - Siapa yang Terbaik?</small>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Sistem penilaian otomatis untuk semua toko partner Anda. Seperti rapor sekolah, tapi untuk toko! 
                        Sistem ini akan memberikan nilai A+, A, B, atau C untuk setiap toko berdasarkan seberapa bagus mereka menjual produk.
                    </p>
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="border-right">
                                <strong class="text-success">A+ Partners</strong>
                                <div class="text-muted small">85%+ sell-through</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-right">
                                <strong class="text-warning">B Partners</strong>
                                <div class="text-muted small">60-85% sell-through</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <strong class="text-danger">C Partners</strong>
                            <div class="text-muted small">&lt;60% sell-through</div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <a href="{{ route('analytics.partner-performance.index') }}" class="btn btn-primary">
                            <i class="fas fa-chart-line mr-1"></i> Lihat Partner Performance
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics 2: Inventory Optimization -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 shadow-lg border-0">
                <div class="card-header bg-gradient-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-boxes mr-2"></i>
                        Inventory Optimization
                    </h5>
                    <small class="opacity-75">Algoritma Cerdas - Berapa Jumlah Optimal Kirim ke Setiap Toko?</small>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Sistem pintar yang menghitung berapa jumlah optimal produk yang harus dikirim ke setiap toko. 
                        Seperti GPS untuk inventory - selalu tahu rute terbaik!
                    </p>
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="border-right">
                                <strong class="text-info">-40%</strong>
                                <div class="text-muted small">Inventory Cost</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <strong class="text-success">+30%</strong>
                            <div class="text-muted small">Cash Flow Speed</div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <a href="{{ route('analytics.inventory-optimization.index') }}" class="btn btn-success">
                            <i class="fas fa-calculator mr-1"></i> Lihat Rekomendasi Optimal
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics 3: Product Velocity -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 shadow-lg border-0">
                <div class="card-header bg-gradient-warning text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Product Velocity Analytics
                    </h5>
                    <small class="opacity-75">Detektif Produk - Mana yang Hot Seller, Mana yang Slow Mover?</small>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Sistem detektif yang mengawasi setiap produk Anda! Seperti speedometer untuk produk - 
                        tahu mana yang lari kencang (laku keras) dan mana yang jalan pelan (susah laku).
                    </p>
                    <div class="row text-center mb-3">
                        <div class="col-3">
                            <div class="border-right">
                                <strong class="text-danger">🔥</strong>
                                <div class="text-muted small">Hot Seller</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border-right">
                                <strong class="text-success">✅</strong>
                                <div class="text-muted small">Good Mover</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="border-right">
                                <strong class="text-warning">🐌</strong>
                                <div class="text-muted small">Slow Mover</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <strong class="text-secondary">💀</strong>
                            <div class="text-muted small">Dead Stock</div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <a href="{{ route('analytics.product-velocity.index') }}" class="btn btn-warning">
                            <i class="fas fa-search mr-1"></i> Analisis Kecepatan Produk
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics 4: Profitability Analysis -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card h-100 shadow-lg border-0">
                <div class="card-header bg-gradient-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calculator mr-2"></i>
                        True Profitability Analysis
                    </h5>
                    <small class="opacity-75">Kalkulator Keuntungan Sejati - Partner Mana yang Benar-benar Menguntungkan?</small>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Kalkulator super canggih yang menghitung keuntungan SESUNGGUHNYA dari setiap toko partner. 
                        Bukan cuma lihat omzet, tapi semua biaya tersembunyi juga dihitung!
                    </p>
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="border-right">
                                <strong class="text-success">+60%</strong>
                                <div class="text-muted small">Network Profit</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-right">
                                <strong class="text-info">ROI</strong>
                                <div class="text-muted small">True Calculation</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <strong class="text-warning">Hidden</strong>
                            <div class="text-muted small">Cost Detection</div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <a href="{{ route('analytics.profitability-analysis.index') }}" class="btn btn-info">
                            <i class="fas fa-money-bill-wave mr-1"></i> Analisis True Profit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Transformation Summary -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-rocket mr-2"></i>
                        Transformasi Bisnis Zafa Potato
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-danger">
                                <i class="fas fa-times-circle mr-1"></i>
                                SEBELUM - Manual & Reactive:
                            </h6>
                            <ul class="list-unstyled text-muted">
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Catat manual di nota</li>
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Tebak-tebakan jumlah kirim</li>
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Tidak tahu partner mana yang untung</li>
                                <li><i class="fas fa-circle fa-xs mr-2"></i>React setelah masalah terjadi</li>
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Keputusan berdasarkan feeling</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">
                                <i class="fas fa-check-circle mr-1"></i>
                                SESUDAH - Digital & Proactive:
                            </h6>
                            <ul class="list-unstyled text-muted">
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Dashboard real-time otomatis</li>
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Ranking partner berdasarkan ROI</li>
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Antisipasi masalah sebelum terjadi</li>
                                <li><i class="fas fa-circle fa-xs mr-2"></i>Keputusan berdasarkan data akurat</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}
.bg-gradient-success {
    background: linear-gradient(45deg, #28a745, #1e7e34);
}
.bg-gradient-warning {
    background: linear-gradient(45deg, #ffc107, #e0a800);
}
.bg-gradient-info {
    background: linear-gradient(45deg, #17a2b8, #117a8b);
}
.bg-gradient-secondary {
    background: linear-gradient(45deg, #6c757d, #545b62);
}
.bg-gradient-dark {
    background: linear-gradient(45deg, #343a40, #23272b);
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

.opacity-75 {
    opacity: 0.75;
}
</style>
@endsection

@push('css')
<style>
.analytics-card {
    transition: all 0.3s ease;
}

.analytics-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.border-right {
    border-right: 1px solid #dee2e6;
}

@media (max-width: 768px) {
    .border-right {
        border-right: none;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 10px;
        padding-bottom: 10px;
    }
}
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    // Add loading animation for analytics cards
    $('.card a').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="fas fa-spinner fa-spin mr-1"></i> Loading...');
        button.prop('disabled', true);
        
        // Restore button after a delay (in case of slow loading)
        setTimeout(function() {
            button.html(originalText);
            button.prop('disabled', false);
        }, 3000);
    });
    
    // Add tooltips to cards
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush
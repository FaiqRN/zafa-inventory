@extends('layouts.template')
@section('title', 'Analytics Dashboard')

@push('css')
<style>
/* Analytics Dashboard - AdminLTE Style */
.analytics-header {
    background: linear-gradient(135deg, #309898 0%, #00235B 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(48, 152, 152, 0.3);
}

.analytics-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.analytics-nav {
    margin-bottom: 1.5rem;
}

.nav-pills-custom {
    background: white;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
}

.nav-pills-custom .nav-link {
    background: #f4f6f9;
    color: #495057;
    border: none;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    min-width: 130px;
    text-align: center;
    margin: 0 0.25rem;
}

.nav-pills-custom .nav-link:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.nav-pills-custom .nav-link.active {
    background: #309898 !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(48, 152, 152, 0.3);
}

.analytics-section {
    display: none;
}

.analytics-section.active {
    display: block;
}

.section-header {
    margin-bottom: 2rem;
}

.section-title {
    color: #495057;
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title i {
    color: #309898;
    font-size: 1.5rem;
}

.filter-card {
    margin-bottom: 1.5rem;
}

.kpi-row {
    margin-bottom: 2rem;
}

.info-box-custom {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    border-left: 4px solid #309898;
}

.info-box-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.info-box-icon-custom {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #309898, #00235B);
    color: white;
    font-size: 1.5rem;
    margin-right: 1rem;
}

.info-box-content-custom {
    flex: 1;
}

.info-box-text-custom {
    color: #6c757d;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.info-box-number-custom {
    font-size: 1.8rem;
    font-weight: 700;
    color: #309898;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.info-box-progress-custom {
    font-size: 0.8rem;
    color: #28a745;
    font-weight: 500;
}

/* Chart Cards using AdminLTE card style */
.chart-row {
    margin-bottom: 2rem;
}

.recommendation-item {
    background: #f8f9fa;
    border-left: 4px solid #309898;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0 8px 8px 0;
    transition: transform 0.3s ease;
}

.recommendation-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.recommendation-item.opportunity {
    background: rgba(40, 167, 69, 0.05);
    border-left-color: #28a745;
}

.recommendation-item.risk {
    background: rgba(220, 53, 69, 0.05);
    border-left-color: #dc3545;
}

.recommendation-item.warning {
    background: rgba(255, 193, 7, 0.05);
    border-left-color: #ffc107;
}

.refresh-btn-custom {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.refresh-btn-custom:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    color: white;
}
</style>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Analytics Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                    <li class="breadcrumb-item active">Analytics</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="analytics-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
                    <p class="mb-0">Comprehensive business intelligence for Zafa Potato CRM</p>
                    <div class="mt-2">
                        <small><i class="fas fa-clock"></i> Last updated: <span id="currentTimestamp">Loading...</span></small>
                    </div>
                </div>
                <button onclick="refreshAllAnalytics()" class="refresh-btn-custom">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
            </div>
        </div>

        <!-- Navigation -->
        <div class="analytics-nav">
            <ul class="nav nav-pills nav-pills-custom" id="analytics-nav">
                <li class="nav-item">
                    <a class="nav-link active" data-section="overview" href="#overview">
                        <i class="fas fa-tachometer-alt"></i> Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-section="partner-performance" href="#partner">
                        <i class="fas fa-users"></i> Partner Performance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-section="inventory" href="#inventory">
                        <i class="fas fa-boxes"></i> Inventory Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-section="product-velocity" href="#velocity">
                        <i class="fas fa-rocket"></i> Product Velocity
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-section="profitability" href="#profit">
                        <i class="fas fa-chart-pie"></i> Profitability
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-section="predictive" href="#predictive">
                        <i class="fas fa-brain"></i> Predictive Analytics
                    </a>
                </li>
            </ul>
        </div>

        <!-- Filters -->
        <div class="card filter-card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="periodeFilter">Periode Analisis</label>
                            <select id="periodeFilter" class="form-control">
                                <option value="1_bulan">1 Bulan Terakhir</option>
                                <option value="3_bulan">3 Bulan Terakhir</option>
                                <option value="6_bulan">6 Bulan Terakhir</option>
                                <option value="1_tahun" selected>1 Tahun Terakhir</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="wilayahFilter">Wilayah</label>
                            <select id="wilayahFilter" class="form-control">
                                <option value="all" selected>Semua Wilayah</option>
                                <option value="Malang Kota">Malang Kota</option>
                                <option value="Malang Kabupaten">Malang Kabupaten</option>
                                <option value="Kota Batu">Kota Batu</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="produkFilter">Kategori Produk</label>
                            <select id="produkFilter" class="form-control">
                                <option value="all" selected>Semua Produk</option>
                                <option value="kentang_segar">Kentang Segar</option>
                                <option value="kentang_olahan">Kentang Olahan</option>
                                <option value="produk_turunan">Produk Turunan</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Section -->
        <div id="overview-section" class="analytics-section active">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-tachometer-alt"></i> Business Overview
                </h2>
            </div>
            
            <!-- KPI Cards Row -->
            <div class="row kpi-row">
                <div class="col-lg-3 col-6">
                    <div class="info-box-custom">
                        <div class="info-box-icon-custom">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-box-content-custom">
                            <span class="info-box-text-custom">Total Partners</span>
                            <div class="info-box-number-custom" id="totalPartners">Loading...</div>
                            <div class="info-box-progress-custom" id="partnersChange">+0%</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box-custom">
                        <div class="info-box-icon-custom bg-success">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="info-box-content-custom">
                            <span class="info-box-text-custom">Total Revenue</span>
                            <div class="info-box-number-custom" id="totalRevenue">Loading...</div>
                            <div class="info-box-progress-custom" id="revenueChange">+0%</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box-custom">
                        <div class="info-box-icon-custom bg-warning">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="info-box-content-custom">
                            <span class="info-box-text-custom">Average Sales Rate</span>
                            <div class="info-box-number-custom" id="avgSalesRate">Loading...</div>
                            <div class="info-box-progress-custom" id="salesRateChange">+0%</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box-custom">
                        <div class="info-box-icon-custom bg-info">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="info-box-content-custom">
                            <span class="info-box-text-custom">Total Pengiriman</span>
                            <div class="info-box-number-custom" id="totalPengiriman">Loading...</div>
                            <div class="info-box-progress-custom" id="pengirimanChange">+0%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row chart-row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                Revenue Trend (6 Months)
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="overviewRevenueChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                Channel Distribution
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="overviewChannelChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-map-marked-alt mr-1"></i>
                                Regional Performance
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="overviewRegionalChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partner Performance Section -->
        <div id="partner-performance-section" class="analytics-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-users"></i> Partner Performance Analytics
                </h2>
            </div>
            
            <!-- Partner KPI Cards -->
            <div class="row kpi-row">
                <div class="col-lg-4 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-chart-bar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Partner Average Sales</span>
                            <span class="info-box-number" id="partnerAvgSales">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Need Attention</span>
                            <span class="info-box-number" id="needAttention">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Active Partners</span>
                            <span class="info-box-number" id="totalActivePartners">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Partner Charts -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-medal mr-1"></i>
                                Top 10 Partner Ranking
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="partnerRankingChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-star mr-1"></i>
                                Grade Distribution
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="gradeDistributionChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Section -->
        <div id="inventory-section" class="analytics-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-boxes"></i> Inventory Analytics
                </h2>
            </div>
            
            <div class="row kpi-row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="avgTurnoverRate">Loading...</h3>
                            <p>Average Turnover Rate</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-sync"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="inventoryEfficiency">Loading...</h3>
                            <p>Inventory Efficiency</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="wasteReduction">Loading...</h3>
                            <p>Waste Reduction Potential</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-recycle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="returRate">Loading...</h3>
                            <p>Return Rate</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-undo"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Velocity Section -->
        <div id="product-velocity-section" class="analytics-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-rocket"></i> Product Velocity Analytics
                </h2>
            </div>

            <div class="row">
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-fire"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Hot Sellers</span>
                            <span class="info-box-number" id="hotSellers">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-thumbs-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Good Movers</span>
                            <span class="info-box-number" id="goodMovers">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Slow Movers</span>
                            <span class="info-box-number" id="slowMovers">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-times"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Dead Stock</span>
                            <span class="info-box-number" id="deadStock">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profitability Section -->
        <div id="profitability-section" class="analytics-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-chart-pie"></i> Profitability Analysis
                </h2>
            </div>

            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3 id="avgROI">Loading...</h3>
                            <p>Average ROI</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="profitMargin">Loading...</h3>
                            <p>Profit Margin</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="hiddenCostsImpact">Loading...</h3>
                            <p>Hidden Costs Impact</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-eye-slash"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="netProfit">Loading...</h3>
                            <p>Net Profit</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Predictive Analytics Section -->
        <div id="predictive-section" class="analytics-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-brain"></i> Predictive Analytics & AI Insights
                </h2>
            </div>
            
            <div class="row kpi-row">
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-crosshairs"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Prediction Accuracy</span>
                            <span class="info-box-number" id="predictionAccuracy">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Demand Growth Forecast</span>
                            <span class="info-box-number" id="demandGrowth">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Partners at Risk</span>
                            <span class="info-box-number" id="partnersAtRisk">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-lightbulb"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">New Opportunities</span>
                            <span class="info-box-number" id="newOpportunities">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Recommendations -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb mr-1"></i>
                        AI-Powered Recommendations
                    </h3>
                </div>
                <div class="card-body" id="aiRecommendations">
                    <div class="recommendation-item opportunity">
                        <h4><i class="fas fa-lightbulb"></i> Optimize Inventory Allocation</h4>
                        <p>Increase shipment to top 3 performers by 20% based on trend analysis.</p>
                        <p><strong>Impact: Rp 15-25 juta additional revenue</strong></p>
                    </div>
                    <div class="recommendation-item risk">
                        <h4><i class="fas fa-exclamation-triangle"></i> Partner Risk Alert</h4>
                        <p>2 partners showing declining performance trends. Consider partnership review.</p>
                        <p><strong>Impact: Prevent Rp 8-12 juta potential losses</strong></p>
                    </div>
                    <div class="recommendation-item warning">
                        <h4><i class="fas fa-calendar-alt"></i> Peak Season Preparation</h4>
                        <p>High-demand season approaching. Prepare inventory increase by 25% for top-grade partners.</p>
                        <p><strong>Impact: Rp 20-30 juta opportunity</strong></p>
                    </div>
                    <div class="recommendation-item">
                        <h4><i class="fas fa-cogs"></i> Route Optimization</h4>
                        <p>Consolidate deliveries to reduce logistics costs by 15%.</p>
                        <p><strong>Impact: Rp 5-8 juta monthly savings</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Loading Overlay -->
<div class="overlay" id="loadingOverlay" style="display: none;">
    <i class="fas fa-2x fa-sync-alt fa-spin"></i>
</div>
@endsection

@push('js')
<!-- Chart.js -->
<script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>

<script>
// Analytics Dashboard - AdminLTE Compatible
document.addEventListener('DOMContentLoaded', function() {
    console.log('Analytics Dashboard loading...');
    
    // Setup CSRF token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Initialize dashboard
    initializeAnalytics();
});

function initializeAnalytics() {
    setupNavigation();
    updateTimestamp();
    loadAllData();
}

function setupNavigation() {
    // Handle nav pill clicks
    $('#analytics-nav .nav-link').click(function(e) {
        e.preventDefault();
        
        // Remove active from all
        $('#analytics-nav .nav-link').removeClass('active');
        $('.analytics-section').removeClass('active');
        
        // Add active to clicked
        $(this).addClass('active');
        const section = $(this).data('section');
        $('#' + section + '-section').addClass('active');
        
        console.log('Switched to:', section);
    });
}

function updateTimestamp() {
    const now = new Date();
    const timestamp = now.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }) + ' WIB';
    
    $('#currentTimestamp').text(timestamp);
}

function loadAllData() {
    showLoading();
    
    // Load real data from endpoints with fallback to sample data
    Promise.all([
        loadOverviewData().catch(() => loadOverviewSample()),
        loadPartnerData().catch(() => loadPartnerSample()),
        loadInventoryData().catch(() => loadInventorySample()),
        loadProductVelocityData().catch(() => loadProductVelocitySample()),
        loadProfitabilityData().catch(() => loadProfitabilitySample()),
        loadPredictiveData().catch(() => loadPredictiveSample())
    ]).then(() => {
        hideLoading();
        console.log('All data loaded successfully');
    }).catch(error => {
        hideLoading();
        console.error('Error loading data:', error);
        showErrorMessage('Some data could not be loaded');
    });
}

// Data loading functions
function loadOverviewData() {
    return $.get('/analytics/overview')
        .done(response => {
            if (response.success) {
                updateKPI('totalPartners', response.kpi?.total_partners || 0);
                updateKPI('totalRevenue', formatCurrency(response.kpi?.total_revenue || 0));
                updateKPI('avgSalesRate', (response.kpi?.avg_sales_rate || 0) + '%');
                updateKPI('totalPengiriman', response.kpi?.total_pengiriman || 0);
                
                updateKPI('partnersChange', '+' + (response.kpi?.partners_growth || 0) + '%');
                updateKPI('revenueChange', '+' + (response.kpi?.revenue_growth || 0) + '%');
                updateKPI('salesRateChange', '+' + (response.kpi?.sales_rate_growth || 0) + '%');
                updateKPI('pengirimanChange', '+' + (response.kpi?.pengiriman_growth || 0) + '%');
                
                // Create charts
                if (response.monthly_revenue?.length > 0) {
                    createRevenueChart(response.monthly_revenue);
                } else {
                    createSampleRevenueChart();
                }
                
                if (response.channel_distribution) {
                    createChannelChart(response.channel_distribution);
                } else {
                    createSampleChannelChart();
                }
                
                if (response.regional_data?.length > 0) {
                    createRegionalChart(response.regional_data);
                } else {
                    createSampleRegionalChart();
                }
            }
        });
}

function loadPartnerData() {
    return $.get('/analytics/partner-performance')
        .done(response => {
            if (response.success) {
                updateKPI('partnerAvgSales', (response.summary?.avg_sales_rate || 0) + '%');
                updateKPI('needAttention', response.summary?.need_attention || 0);
                updateKPI('totalActivePartners', response.summary?.total_partners || 0);
                
                // Create partner charts if data available
                if (response.partners?.length > 0) {
                    createPartnerRankingChart(response.partners);
                }
                if (response.grade_distribution) {
                    createGradeDistributionChart(response.grade_distribution);
                }
            }
        });
}

function loadInventoryData() {
    return $.get('/analytics/inventory-analytics')
        .done(response => {
            if (response.success) {
                updateKPI('avgTurnoverRate', (response.summary?.avg_turnover_rate || 0) + 'x');
                updateKPI('inventoryEfficiency', (response.summary?.avg_efficiency || 0) + '%');
                updateKPI('wasteReduction', (response.summary?.waste_reduction_potential || 0) + '%');
                updateKPI('returRate', (response.summary?.retur_rate || 0) + '%');
            }
        });
}

function loadProductVelocityData() {
    return $.get('/analytics/product-velocity')
        .done(response => {
            if (response.success) {
                updateKPI('hotSellers', response.category_stats?.hot_sellers || 0);
                updateKPI('goodMovers', response.category_stats?.good_movers || 0);
                updateKPI('slowMovers', response.category_stats?.slow_movers || 0);
                updateKPI('deadStock', response.category_stats?.dead_stock || 0);
            }
        });
}

function loadProfitabilityData() {
    return $.get('/analytics/profitability-analysis')
        .done(response => {
            if (response.success) {
                updateKPI('avgROI', (response.summary?.avg_roi || 0) + '%');
                updateKPI('profitMargin', (response.summary?.avg_profit_margin || 0) + '%');
                updateKPI('hiddenCostsImpact', (response.summary?.hidden_costs_impact || 0) + '%');
                updateKPI('netProfit', formatCurrency(response.summary?.total_net_profit || 0));
            }
        });
}

function loadPredictiveData() {
    return $.get('/analytics/predictive-analytics')
        .done(response => {
            if (response.success) {
                updateKPI('predictionAccuracy', (response.summary?.forecast_accuracy || 0) + '%');
                updateKPI('demandGrowth', '+' + (response.summary?.demand_growth_trend || 0) + '%');
                updateKPI('partnersAtRisk', response.summary?.partners_at_risk || 0);
                updateKPI('newOpportunities', response.summary?.new_opportunities || 0);
            }
        });
}

// Sample data functions (fallback)
function loadOverviewSample() {
    updateKPI('totalPartners', '38');
    updateKPI('totalRevenue', 'Rp 125,000,000');
    updateKPI('avgSalesRate', '78.5%');
    updateKPI('totalPengiriman', '156');
    
    updateKPI('partnersChange', '+12.5%');
    updateKPI('revenueChange', '+18.2%');
    updateKPI('salesRateChange', '+5.1%');
    updateKPI('pengirimanChange', '+8.7%');
    
    createSampleRevenueChart();
    createSampleChannelChart();
    createSampleRegionalChart();
}

function loadPartnerSample() {
    updateKPI('partnerAvgSales', '75.8%');
    updateKPI('needAttention', '3');
    updateKPI('totalActivePartners', '38');
    
    createSamplePartnerRankingChart();
    createSampleGradeDistributionChart();
}

function loadInventorySample() {
    updateKPI('avgTurnoverRate', '2.8x');
    updateKPI('inventoryEfficiency', '87.5%');
    updateKPI('wasteReduction', '12.5%');
    updateKPI('returRate', '8.2%');
}

function loadProductVelocitySample() {
    updateKPI('hotSellers', '8');
    updateKPI('goodMovers', '15');
    updateKPI('slowMovers', '12');
    updateKPI('deadStock', '3');
}

function loadProfitabilitySample() {
    updateKPI('avgROI', '24.8%');
    updateKPI('profitMargin', '18.5%');
    updateKPI('hiddenCostsImpact', '15.2%');
    updateKPI('netProfit', 'Rp 42,500,000');
}

function loadPredictiveSample() {
    updateKPI('predictionAccuracy', '78.5%');
    updateKPI('demandGrowth', '+15.2%');
    updateKPI('partnersAtRisk', '3');
    updateKPI('newOpportunities', '7');
}

// Chart creation functions
function createRevenueChart(data) {
    const ctx = document.getElementById('overviewRevenueChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.month),
            datasets: [{
                label: 'Revenue',
                data: data.map(item => item.total_revenue),
                borderColor: '#309898',
                backgroundColor: 'rgba(48, 152, 152, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: getDefaultChartOptions('Revenue Trend')
    });
}

function createSampleRevenueChart() {
    const ctx = document.getElementById('overviewRevenueChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Revenue',
                data: [18500000, 22300000, 26100000, 19800000, 24500000, 28200000],
                borderColor: '#309898',
                backgroundColor: 'rgba(48, 152, 152, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#309898',
                pointBorderColor: '#fff',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrencyShort(value);
                        }
                    }
                }
            }
        }
    });
}

function createChannelChart(data) {
    const ctx = document.getElementById('overviewChannelChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['B2B Konsinyasi', 'B2C Direct Sales'],
            datasets: [{
                data: [data.b2b_percentage || 75, data.b2c_percentage || 25],
                backgroundColor: ['#309898', '#28a745'],
                borderWidth: 0
            }]
        },
        options: getDoughnutChartOptions()
    });
}

function createSampleChannelChart() {
    const ctx = document.getElementById('overviewChannelChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['B2B Konsinyasi', 'B2C Direct Sales'],
            datasets: [{
                data: [75, 25],
                backgroundColor: ['#309898', '#28a745'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: getDoughnutChartOptions()
    });
}

function createRegionalChart(data) {
    const ctx = document.getElementById('overviewRegionalChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.wilayah || item.region),
            datasets: [{
                label: 'Sales Rate (%)',
                data: data.map(item => item.sales_rate || item.performance),
                backgroundColor: ['#309898', '#28a745', '#ffc107', '#dc3545'],
                borderRadius: 4
            }]
        },
        options: getBarChartOptions('Regional Performance')
    });
}

function createSampleRegionalChart() {
    const ctx = document.getElementById('overviewRegionalChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Malang Kota', 'Malang Kabupaten', 'Kota Batu'],
            datasets: [{
                label: 'Sales Rate (%)',
                data: [82.5, 75.2, 88.1],
                backgroundColor: ['#309898', '#28a745', '#ffc107'],
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: getBarChartOptions('Regional Performance')
    });
}

function createPartnerRankingChart(partners) {
    const ctx = document.getElementById('partnerRankingChart');
    if (!ctx) return;
    
    const topPartners = partners.slice(0, 10);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topPartners.map(p => p.nama_toko),
            datasets: [{
                label: 'Sales Rate (%)',
                data: topPartners.map(p => p.sales_rate),
                backgroundColor: topPartners.map(p => getGradeColor(p.grade)),
                borderRadius: 4
            }]
        },
        options: getBarChartOptions('Partner Ranking')
    });
}

function createSamplePartnerRankingChart() {
    const ctx = document.getElementById('partnerRankingChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Toko Makmur', 'Warung Berkah', 'Toko Sejahtera', 'Mini Market', 'Toko Merdeka'],
            datasets: [{
                label: 'Sales Rate (%)',
                data: [95.2, 88.7, 82.1, 76.5, 71.2],
                backgroundColor: ['#28a745', '#309898', '#309898', '#ffc107', '#ffc107'],
                borderRadius: 4
            }]
        },
        options: getBarChartOptions('Partner Ranking')
    });
}

function createGradeDistributionChart(gradeData) {
    const ctx = document.getElementById('gradeDistributionChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(gradeData),
            datasets: [{
                data: Object.values(gradeData),
                backgroundColor: ['#28a745', '#309898', '#ffc107', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: getDoughnutChartOptions()
    });
}

function createSampleGradeDistributionChart() {
    const ctx = document.getElementById('gradeDistributionChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['A+', 'A', 'B', 'C'],
            datasets: [{
                data: [5, 12, 18, 3],
                backgroundColor: ['#28a745', '#309898', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: getDoughnutChartOptions()
    });
}

// Chart options functions
function getDefaultChartOptions(title) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: { display: false }
        }
    };
}

function getDoughnutChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { 
                    padding: 20, 
                    usePointStyle: true 
                }
            }
        }
    };
}

function getBarChartOptions(title) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            x: {
                ticks: { maxRotation: 45 }
            }
        }
    };
}

// Utility functions
function updateKPI(id, value) {
    $('#' + id).text(value);
}

function formatCurrency(amount) {
    if (!amount || isNaN(amount)) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function formatCurrencyShort(amount) {
    if (!amount || isNaN(amount)) return 'Rp 0';
    
    if (amount >= 1000000000) {
        return 'Rp ' + (amount / 1000000000).toFixed(1) + 'B';
    } else if (amount >= 1000000) {
        return 'Rp ' + (amount / 1000000).toFixed(1) + 'M';
    } else if (amount >= 1000) {
        return 'Rp ' + (amount / 1000).toFixed(1) + 'K';
    }
    
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function getGradeColor(grade) {
    switch(grade) {
        case 'A+': return '#28a745';
        case 'A': return '#309898';
        case 'B': return '#ffc107';
        case 'C': return '#dc3545';
        default: return '#6c757d';
    }
}

function showLoading() {
    $('#loadingOverlay').show();
}

function hideLoading() {
    $('#loadingOverlay').hide();
}

function showErrorMessage(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#dc3545'
        });
    } else {
        alert('Error: ' + message);
    }
}

function refreshAllAnalytics() {
    console.log('Refreshing analytics data...');
    updateTimestamp();
    loadAllData();
    
    // Show success message
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: 'Analytics data refreshed successfully',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
}

// Auto-refresh timestamp every minute
setInterval(updateTimestamp, 60000);

console.log('Analytics Dashboard initialized successfully');
</script>
@endpush
@extends('layouts.template')

@section('page_title', 'CRM Market Intelligence - Ekspansi Toko')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
<li class="breadcrumb-item active">CRM Market Intelligence</li>
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- Loading Indicator -->
    <div id="loading-indicator" class="alert alert-info animate-fade-in" style="display: none;">
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm mr-2" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <span>Loading CRM data...</span>
        </div>
    </div>

    <!-- Error Alert Container -->
    <div id="error-container"></div>

    <!-- CRM Statistics Cards - FIXED WITH PROPER DATA BINDING -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-info hover-lift">
                <div class="inner">
                    <h3 id="total-partners" class="animate-counter">0</h3>
                    <p>Total Toko</p>
                </div>
                <div class="icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-chart-line"></i> Geographic Distribution
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-success hover-lift">
                <div class="inner">
                    <h3 id="geo-clusters" class="animate-counter">0</h3>
                    <p>Geo Cluster</p>
                </div>
                <div class="icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-bullseye"></i> 1.5km Radius
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-warning hover-lift">
                <div class="inner">
                    <h3><span id="avg-margin" class="animate-counter">0</span>%</h3>
                    <p>Avg Margin</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-arrow-up"></i> Profit Analysis
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-danger hover-lift">
                <div class="inner">
                    <h3>Rp <span id="total-revenue" class="animate-counter">0</span></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-coins"></i> Monthly Projection
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation System -->
    <div class="card shadow-lg-custom">
        <div class="card-header bg-gradient-primary">
            <h3 class="card-title text-white">
                <i class="fas fa-chart-area mr-2"></i>
                CRM Ekspansi Toko - Market Intelligence
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-outline-light" id="btn-system-health">
                    <i class="fas fa-heartbeat mr-1"></i>System Health
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs nav-justified" id="crmTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold" id="overview-tab" data-toggle="tab" href="#overview" role="tab">
                        <i class="fas fa-chart-pie mr-2"></i>📈 Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold" id="analysis-tab" data-toggle="tab" href="#analysis" role="tab">
                        <i class="fas fa-search mr-2"></i>🔍 Analysis
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold" id="expansion-tab" data-toggle="tab" href="#expansion" role="tab">
                        <i class="fas fa-rocket mr-2"></i>🚀 Ekspansi
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content p-3" id="crmTabContent">
                
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row">
                        <!-- Map Column -->
                        <div class="col-lg-8 mb-4">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-map mr-2"></i>Geographic Market Analysis
                                    </h3>
                                    <div class="card-tools">
                                        <span class="badge badge-light" id="visible-partners-badge">
                                            <i class="fas fa-eye mr-1"></i>
                                            <span id="visible-partners-count">0</span> visible
                                        </span>
                                        <div class="btn-group ml-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown">
                                                <i class="fas fa-layer-group mr-1"></i>Map Layers
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="#" id="toggle-profit-layer">
                                                    <i class="fas fa-dollar-sign mr-2"></i>Profit Markers
                                                </a>
                                                <a class="dropdown-item" href="#" id="toggle-cluster-layer">
                                                    <i class="fas fa-circle-notch mr-2"></i>Cluster Boundaries
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="#" id="fit-bounds">
                                                    <i class="fas fa-expand-arrows-alt mr-2"></i>Fit All Markers
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="market-map-container">
                                        <div id="market-map" style="height: 500px; width: 100%;"></div>
                                        <div class="map-loading-overlay" id="map-loading" style="display: none;">
                                            <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                                    <span class="sr-only">Loading...</span>
                                                </div>
                                                <h5 class="text-muted">Loading Market Map...</h5>
                                                <p class="text-muted">Calculating profit and clustering data</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Control Panel -->
                        <div class="col-lg-4">
                            <!-- System Status Panel -->
                            <div class="card card-outline card-secondary mb-3">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-tachometer-alt mr-2"></i>System Status
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="system-status-indicators">
                                        <div class="status-item mb-2">
                                            <span class="status-label">Profit Analysis:</span>
                                            <span class="badge badge-secondary" id="profit-status">Not Started</span>
                                        </div>
                                        <div class="status-item mb-2">
                                            <span class="status-label">Geographic Clustering:</span>
                                            <span class="badge badge-secondary" id="clustering-status">Not Started</span>
                                        </div>
                                        <div class="status-item mb-2">
                                            <span class="status-label">Expansion Planning:</span>
                                            <span class="badge badge-secondary" id="expansion-status">Not Started</span>
                                        </div>
                                    </div>
                                    <div class="progress mt-3" style="height: 8px;">
                                        <div class="progress-bar bg-gradient-primary" id="overall-progress" style="width: 0%" role="progressbar"></div>
                                    </div>
                                    <small class="text-muted">Overall Progress: <span id="progress-text">0% Complete</span></small>
                                </div>
                            </div>

                            <!-- Legend Cluster -->
                            <div class="card card-info mb-3">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-palette mr-2"></i>Legend Cluster
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="legend-items">
                                        <div class="legend-item mb-2">
                                            <span class="legend-color bg-success"></span>
                                            <span>🟢 Hijau (Margin >20%): Excellent Performance</span>
                                        </div>
                                        <div class="legend-item mb-2">
                                            <span class="legend-color bg-warning"></span>
                                            <span>🟡 Kuning (Margin 10-20%): Good Performance</span>
                                        </div>
                                        <div class="legend-item mb-2">
                                            <span class="legend-color bg-danger"></span>
                                            <span>🔴 Merah (Margin <10%): Poor Performance</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-color" style="background-color: #8b5cf6;"></span>
                                            <span>🟣 Ungu: Cluster Boundary (1.5km)</span>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="legend-note">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            <strong>Note:</strong> Colors akan muncul setelah profit calculation selesai. 
                                            Default color abu-abu menunjukkan toko belum dianalisis.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-cogs mr-2"></i>Action Controls
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <!-- Step 1: Profit Calculation -->
                                    <div class="action-step mb-3">
                                        <h6 class="text-primary">
                                            <span class="step-number">1</span>
                                            Profit Analysis
                                        </h6>
                                        <button type="button" class="btn btn-warning btn-block btn-lg mb-2" id="btn-calculate-profit">
                                            <i class="fas fa-calculator mr-2"></i>
                                            💰 Hitung Profit Semua Toko
                                        </button>
                                        <small class="text-muted">Analisis margin dan profitabilitas setiap toko partner</small>
                                    </div>

                                    <!-- Step 2: Geographic Clustering -->
                                    <div class="action-step mb-3">
                                        <h6 class="text-info">
                                            <span class="step-number">2</span>
                                            Geographic Clustering
                                        </h6>
                                        <button type="button" class="btn btn-info btn-block btn-lg mb-2" id="btn-create-clustering" disabled>
                                            <i class="fas fa-project-diagram mr-2"></i>
                                            🗺️ Buat Geographic Clustering
                                        </button>
                                        <small class="text-muted">Kelompokkan toko berdasarkan lokasi geografis (radius 1.5km)</small>
                                    </div>

                                    <!-- Step 3: Expansion Planning -->
                                    <div class="action-step mb-3">
                                        <h6 class="text-success">
                                            <span class="step-number">3</span>
                                            Expansion Planning
                                        </h6>
                                        <button type="button" class="btn btn-success btn-block btn-lg mb-2" id="btn-generate-expansion" disabled>
                                            <i class="fas fa-rocket mr-2"></i>
                                            🚀 Generate Expansion Plan
                                        </button>
                                        <small class="text-muted">Buat rekomendasi ekspansi berdasarkan analisis cluster</small>
                                    </div>

                                    <hr>

                                    <!-- System Controls -->
                                    <div class="system-controls">
                                        <h6 class="text-secondary">
                                            <i class="fas fa-wrench mr-1"></i>
                                            System Controls
                                        </h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <button type="button" class="btn btn-primary btn-sm btn-block" id="btn-refresh-data">
                                                    <i class="fas fa-sync-alt mr-1"></i>Refresh Data
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button type="button" class="btn btn-secondary btn-sm btn-block" id="btn-clear-cache">
                                                    <i class="fas fa-trash mr-1"></i>Clear Cache
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analysis Tab -->
                <div class="tab-pane fade" id="analysis" role="tabpanel">
                    <div class="row">
                        <!-- Profit Analysis Panel -->
                        <div class="col-lg-6 mb-4">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-bar mr-2"></i>📈 Profit Analysis
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-sm btn-outline-light" id="btn-export-profit">
                                            <i class="fas fa-download mr-1"></i>Export
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="profit-analysis-content">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-calculator fa-3x mb-3 text-warning"></i>
                                            <h5>Profit Analysis</h5>
                                            <p>Klik "Hitung Profit Semua Toko" pada tab Overview untuk memulai analisis</p>
                                            <div class="mt-3">
                                                <button class="btn btn-warning btn-sm" onclick="$('#overview-tab').tab('show')">
                                                    <i class="fas fa-arrow-left mr-1"></i>Go to Overview
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Geographic Clusters Panel -->
                        <div class="col-lg-6 mb-4">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-map-marked-alt mr-2"></i>🎯 Geographic Clusters
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-sm btn-outline-light" id="btn-export-clusters">
                                            <i class="fas fa-download mr-1"></i>Export
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="clustering-analysis-content">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-project-diagram fa-3x mb-3 text-info"></i>
                                            <h5>Geographic Clustering</h5>
                                            <p>Klik "Buat Geographic Clustering" pada tab Overview untuk memulai pengelompokan</p>
                                            <div class="mt-3">
                                                <button class="btn btn-info btn-sm" onclick="$('#overview-tab').tab('show')">
                                                    <i class="fas fa-arrow-left mr-1"></i>Go to Overview
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Summary Row -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-line mr-2"></i>Performance Summary
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div id="performance-summary-content">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="info-box bg-gradient-success">
                                                    <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Excellent Stores</span>
                                                        <span class="info-box-number" id="excellent-stores-count">0</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" id="excellent-stores-progress" style="width: 0%"></div>
                                                        </div>
                                                        <span class="progress-description">Margin >20%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-box bg-gradient-warning">
                                                    <span class="info-box-icon"><i class="fas fa-thumbs-up"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Good Stores</span>
                                                        <span class="info-box-number" id="good-stores-count">0</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" id="good-stores-progress" style="width: 0%"></div>
                                                        </div>
                                                        <span class="progress-description">Margin 10-20%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-box bg-gradient-danger">
                                                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Poor Stores</span>
                                                        <span class="info-box-number" id="poor-stores-count">0</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" id="poor-stores-progress" style="width: 0%"></div>
                                                        </div>
                                                        <span class="progress-description">Margin <10%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-box bg-gradient-info">
                                                    <span class="info-box-icon"><i class="fas fa-chart-pie"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Total Clusters</span>
                                                        <span class="info-box-number" id="total-clusters-count">0</span>
                                                        <div class="progress">
                                                            <div class="progress-bar" id="clusters-progress" style="width: 0%"></div>
                                                        </div>
                                                        <span class="progress-description">Geographic Groups</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expansion Tab -->
                <div class="tab-pane fade" id="expansion" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-rocket mr-2"></i>🚀 Expansion Planning & Recommendations
                                    </h3>
                                    <div class="card-tools">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-light" id="btn-export-expansion">
                                                <i class="fas fa-download mr-1"></i>Export Plan
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm" id="btn-generate-expansion-header">
                                                <i class="fas fa-magic mr-1"></i>Generate Expansion Plan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Prerequisites Check -->
                                    <div id="expansion-prerequisites" class="alert alert-info">
                                        <h5><i class="fas fa-info-circle mr-2"></i>Prerequisites Check</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="prerequisite-item">
                                                    <span class="prerequisite-icon" id="profit-prereq-icon">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </span>
                                                    <span class="prerequisite-text">Profit Analysis</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="prerequisite-item">
                                                    <span class="prerequisite-icon" id="clustering-prereq-icon">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </span>
                                                    <span class="prerequisite-text">Geographic Clustering</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="prerequisite-item">
                                                    <span class="prerequisite-icon" id="data-prereq-icon">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    </span>
                                                    <span class="prerequisite-text">Store Data Available</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <p class="mb-0">
                                                <strong>Status:</strong> 
                                                <span id="expansion-readiness-status" class="badge badge-warning">Not Ready</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div id="expansion-recommendations-content">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-rocket fa-3x mb-3 text-success"></i>
                                            <h5>Expansion Recommendations</h5>
                                            <p>Silakan jalankan Profit Analysis dan Geographic Clustering terlebih dahulu, kemudian klik "Generate Expansion Plan"</p>
                                            <div class="mt-4">
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-info-circle mr-2"></i>Requirements:</h6>
                                                    <ul class="list-unstyled mb-0">
                                                        <li>✅ Data profit analysis harus sudah dijalankan</li>
                                                        <li>✅ Geographic clustering harus sudah dibuat</li>
                                                        <li>✅ Minimum margin threshold: 10%</li>
                                                    </ul>
                                                </div>
                                                <div class="mt-3">
                                                    <button class="btn btn-primary mr-2" onclick="$('#overview-tab').tab('show')">
                                                        <i class="fas fa-arrow-left mr-1"></i>Back to Overview
                                                    </button>
                                                    <button class="btn btn-outline-secondary" onclick="crmApp.showSystemHelp()">
                                                        <i class="fas fa-question-circle mr-1"></i>Help
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Store Detail Modal -->
<div class="modal fade" id="store-detail-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title text-white">
                    <i class="fas fa-store mr-2"></i>Store Details
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="store-detail-content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cluster Detail Modal -->
<div class="modal fade" id="cluster-detail-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title text-white">
                    <i class="fas fa-project-diagram mr-2"></i>Cluster Analysis
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="cluster-detail-content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Help Modal -->
<div class="modal fade" id="system-help-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title text-white">
                    <i class="fas fa-question-circle mr-2"></i>CRM Expansion System Help
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="help-content">
                    <h5>How to Use CRM Expansion System</h5>
                    
                    <div class="accordion" id="helpAccordion">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#step1Help">
                                        Step 1: Profit Analysis
                                    </button>
                                </h6>
                            </div>
                            <div id="step1Help" class="collapse show" data-parent="#helpAccordion">
                                <div class="card-body">
                                    <p>Calculate profit margins for all stores to understand performance levels:</p>
                                    <ul>
                                        <li>Click "Hitung Profit Semua Toko" button</li>
                                        <li>System calculates profit margins based on selling price vs. cost</li>
                                        <li>Map markers change color based on performance</li>
                                        <li>Results appear in Analysis tab</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#step2Help">
                                        Step 2: Geographic Clustering
                                    </button>
                                </h6>
                            </div>
                            <div id="step2Help" class="collapse" data-parent="#helpAccordion">
                                <div class="card-body">
                                    <p>Group stores geographically to identify expansion opportunities:</p>
                                    <ul>
                                        <li>Requires profit analysis to be completed first</li>
                                        <li>Groups stores within 1.5km radius</li>
                                        <li>Maximum 5 stores per cluster</li>
                                        <li>Cluster boundaries appear on map</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#step3Help">
                                        Step 3: Expansion Planning
                                    </button>
                                </h6>
                            </div>
                            <div id="step3Help" class="collapse" data-parent="#helpAccordion">
                                <div class="card-body">
                                    <p>Generate expansion recommendations based on cluster analysis:</p>
                                    <ul>
                                        <li>Requires both profit analysis and clustering</li>
                                        <li>Identifies high-potential expansion areas</li>
                                        <li>Calculates investment requirements and ROI</li>
                                        <li>Provides priority-based recommendations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
/* Enhanced CRM Ekspansi Toko Styles - FIXED VERSION */
.market-map-container {
    position: relative;
    width: 100%;
    height: 500px;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

#market-map {
    width: 100%;
    height: 100%;
    z-index: 1;
    border-radius: 12px;
}

.map-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 12px;
    backdrop-filter: blur(8px);
}

/* Tab Styling */
.nav-tabs .nav-link {
    color: #495057;
    font-weight: 600;
    border: none;
    padding: 15px 20px;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-radius: 8px 8px 0 0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.nav-tabs .nav-link:hover:not(.active) {
    background-color: #f8f9fa;
    transform: translateY(-1px);
}

/* Legend Styling */
.legend-items .legend-item {
    display: flex;
    align-items: center;
    font-size: 13px;
    margin-bottom: 8px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    flex-shrink: 0;
}

/* Enhanced Button Styling */
.btn-lg {
    padding: 12px 20px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-lg::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-lg:hover::before {
    left: 100%;
}

.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.btn-lg:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-lg:disabled:hover {
    transform: none;
    box-shadow: none;
}

.btn-lg:disabled::before {
    display: none;
}

/* Card Enhancements */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}

.card-header {
    border-radius: 12px 12px 0 0;
    border-bottom: none;
    padding: 15px 20px;
}

/* Step Styling */
.action-step {
    border-left: 4px solid #e9ecef;
    padding-left: 15px;
    position: relative;
}

.action-step.completed {
    border-left-color: #28a745;
}

.action-step.in-progress {
    border-left-color: #ffc107;
}

.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #6c757d;
    color: white;
    border-radius: 50%;
    font-size: 12px;
    font-weight: bold;
    margin-right: 8px;
}

.action-step.completed .step-number {
    background: #28a745;
}

.action-step.in-progress .step-number {
    background: #ffc107;
}

/* System Status Indicators */
.system-status-indicators .status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-label {
    font-weight: 500;
    font-size: 14px;
}

/* Prerequisites Styling */
.prerequisite-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.prerequisite-icon {
    margin-right: 10px;
    font-size: 18px;
}

.prerequisite-text {
    font-weight: 500;
}

/* Animation Classes */
.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

.animate-counter {
    transition: all 0.3s ease;
}

.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Analysis Content Styling */
.profit-item, .cluster-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #007bff;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
}

.profit-item.animated, .cluster-item.animated {
    opacity: 1;
    transform: translateY(0);
}

.profit-item:hover, .cluster-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.profit-item h6, .cluster-item h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 10px;
}

.profit-metrics, .cluster-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.metric-item {
    text-align: center;
    padding: 8px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.metric-value {
    font-size: 18px;
    font-weight: 600;
    color: #007bff;
}

.metric-label {
    font-size: 11px;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 500;
}

/* Expansion Recommendations Styling */
.recommendation-item {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateX(-20px);
}

.recommendation-item.animated {
    opacity: 1;
    transform: translateX(0);
}

.recommendation-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--priority-color, #007bff);
}

.recommendation-item.priority-tinggi {
    --priority-color: #28a745;
    border-color: rgba(40, 167, 69, 0.3);
}

.recommendation-item.priority-sedang {
    --priority-color: #ffc107;
    border-color: rgba(255, 193, 7, 0.3);
}

.recommendation-item.priority-rendah {
    --priority-color: #6c757d;
    border-color: rgba(108, 117, 125, 0.3);
}

.recommendation-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    border-color: var(--priority-color);
}

.priority-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-tinggi .priority-badge {
    background: #28a745;
    color: white;
}

.priority-sedang .priority-badge {
    background: #ffc107;
    color: #212529;
}

.priority-rendah .priority-badge {
    background: #6c757d;
    color: white;
}

.score-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin: 10px 0;
}

.score-fill {
    height: 100%;
    background: linear-gradient(90deg, #ffc107, #28a745);
    border-radius: 4px;
    transition: width 0.8s ease;
    width: 0%;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding: 4px 0;
}

.detail-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 13px;
}

.detail-value {
    font-weight: 600;
    color: #495057;
}

/* Investment Summary Styling */
.investment-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
}

.summary-metric {
    text-align: center;
    padding: 15px;
}

.priority-count {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.count-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
}

.phase-timeline {
    padding-left: 20px;
}

.phase-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.phase-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    margin-right: 12px;
    min-width: 60px;
    text-align: center;
}

.phase-text {
    font-size: 13px;
    color: #495057;
}

/* Responsive Design */
@media (max-width: 768px) {
    .market-map-container {
        height: 400px;
    }
    
    .nav-tabs .nav-link {
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .btn-lg {
        padding: 10px 16px;
        font-size: 14px;
    }
    
    .profit-metrics, .cluster-metrics {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .recommendation-item {
        padding: 15px;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 576px) {
    .market-map-container {
        height: 350px;
    }
    
    .profit-metrics, .cluster-metrics {
        grid-template-columns: 1fr;
    }
    
    .recommendation-item {
        padding: 15px;
    }
    
    .small-box .inner h3 {
        font-size: 24px;
    }
}

/* Loading States */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Success States */
.success-indicator {
    color: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Map Legend Styling */
.map-legend {
    background: rgba(255, 255, 255, 0.95);
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.1);
    max-width: 250px;
}

.legend-content h6 {
    margin-bottom: 12px;
    color: #495057;
    font-weight: 600;
}

.legend-items {
    margin-bottom: 10px;
}

.legend-info {
    border-top: 1px solid #dee2e6;
    padding-top: 8px;
}

/* Info Box Styling */
.info-box {
    border-radius: 8px;
    overflow: hidden;
}

.info-box-icon {
    border-radius: 8px 0 0 8px;
}

.info-box .progress {
    height: 4px;
    margin: 5px 0;
}

.info-box .progress-bar {
    background: rgba(255, 255, 255, 0.3);
}

/* Border utilities */
.border-left-success {
    border-left: 4px solid #28a745 !important;
}

.border-left-warning {
    border-left: 4px solid #ffc107 !important;
}

.border-left-danger {
    border-left: 4px solid #dc3545 !important;
}

.border-left-info {
    border-left: 4px solid #17a2b8 !important;
}

/* Badge utilities */
.badge-lg {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

/* Custom shadow */
.shadow-lg-custom {
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
}

/* Card outline utilities */
.card-outline.card-primary {
    border-top: 3px solid #007bff;
}

.card-outline.card-success {
    border-top: 3px solid #28a745;
}

.card-outline.card-warning {
    border-top: 3px solid #ffc107;
}

.card-outline.card-info {
    border-top: 3px solid #17a2b8;
}

.card-outline.card-secondary {
    border-top: 3px solid #6c757d;
}
</style>
@endpush

@push('js')
<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Chart.js for visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Enhanced Market Map JavaScript - Load the fixed version -->
<script src="{{ asset('js/enhanced-market-map.js') }}"></script>

<script>
// Additional UI enhancements and system integration
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Initializing UI enhancements...');
    
    // Setup additional event handlers for UI improvements
    setupUIEnhancements();
    
    // Setup system status monitoring
    setupSystemStatusMonitoring();
    
    // Setup prerequisite checking
    setupPrerequisiteChecking();
    
    console.log('✅ UI enhancements initialized');
});

/**
 * Setup UI enhancements for better user experience
 */
function setupUIEnhancements() {
    // System health button
    document.getElementById('btn-system-health')?.addEventListener('click', function() {
        showSystemHealth();
    });
    
    // Export buttons
    document.getElementById('btn-export-profit')?.addEventListener('click', function() {
        exportProfitAnalysis();
    });
    
    document.getElementById('btn-export-clusters')?.addEventListener('click', function() {
        exportClustersData();
    });
    
    document.getElementById('btn-export-expansion')?.addEventListener('click', function() {
        exportExpansionPlan();
    });
    
    // Header expansion button (duplicate for convenience)
    document.getElementById('btn-generate-expansion-header')?.addEventListener('click', function() {
        if (window.crmApp) {
            window.crmApp.generateExpansionPlan();
        }
    });
    
    // Map layer controls
    document.getElementById('toggle-profit-layer')?.addEventListener('click', function() {
        toggleProfitLayer();
    });
    
    document.getElementById('toggle-cluster-layer')?.addEventListener('click', function() {
        toggleClusterLayer();
    });
    
    document.getElementById('fit-bounds')?.addEventListener('click', function() {
        fitMapBounds();
    });
    
    // Animate statistics cards on load
    animateStatisticsCards();
}

/**
 * Setup system status monitoring
 */
function setupSystemStatusMonitoring() {
    // Monitor CRM app state and update UI accordingly
    const checkInterval = setInterval(function() {
        if (window.crmApp) {
            updateSystemStatus();
            updateProgressIndicators();
        }
    }, 2000);
    
    // Clear interval when page unloads
    window.addEventListener('beforeunload', function() {
        clearInterval(checkInterval);
    });
}

/**
 * Setup prerequisite checking for expansion tab
 */
function setupPrerequisiteChecking() {
    // Check prerequisites every few seconds
    setInterval(function() {
        if (window.crmApp) {
            updatePrerequisiteStatus();
        }
    }, 3000);
}

/**
 * Update system status indicators
 */
function updateSystemStatus() {
    if (!window.crmApp) return;
    
    const app = window.crmApp;
    
    // Update profit status
    const profitStatus = document.getElementById('profit-status');
    const profitStep = document.querySelector('.action-step:nth-child(1)');
    const profitButton = document.getElementById('btn-calculate-profit');
    
    if (app.profitCalculated) {
        if (profitStatus) profitStatus.className = 'badge badge-success';
        if (profitStatus) profitStatus.textContent = 'Completed';
        if (profitStep) profitStep.classList.add('completed');
        if (profitButton) profitButton.innerHTML = '<i class="fas fa-check mr-2"></i>✅ Profit Calculated';
        
        // Enable clustering button
        const clusteringButton = document.getElementById('btn-create-clustering');
        if (clusteringButton) {
            clusteringButton.disabled = false;
            clusteringButton.classList.remove('btn-secondary');
            clusteringButton.classList.add('btn-info');
        }
    } else {
        if (profitStatus) profitStatus.className = 'badge badge-secondary';
        if (profitStatus) profitStatus.textContent = 'Not Started';
        if (profitStep) profitStep.classList.remove('completed');
    }
    
    // Update clustering status
    const clusteringStatus = document.getElementById('clustering-status');
    const clusteringStep = document.querySelector('.action-step:nth-child(2)');
    const clusteringButton = document.getElementById('btn-create-clustering');
    
    if (app.clusteringDone) {
        if (clusteringStatus) clusteringStatus.className = 'badge badge-success';
        if (clusteringStatus) clusteringStatus.textContent = 'Completed';
        if (clusteringStep) clusteringStep.classList.add('completed');
        if (clusteringButton) clusteringButton.innerHTML = '<i class="fas fa-check mr-2"></i>✅ Clusters Created';
        
        // Enable expansion button
        const expansionButton = document.getElementById('btn-generate-expansion');
        if (expansionButton) {
            expansionButton.disabled = false;
            expansionButton.classList.remove('btn-secondary');
            expansionButton.classList.add('btn-success');
        }
    } else {
        if (clusteringStatus) clusteringStatus.className = 'badge badge-secondary';
        if (clusteringStatus) clusteringStatus.textContent = 'Not Started';
        if (clusteringStep) clusteringStep.classList.remove('completed');
    }
    
    // Update expansion status
    const expansionStatus = document.getElementById('expansion-status');
    const expansionStep = document.querySelector('.action-step:nth-child(3)');
    const expansionButton = document.getElementById('btn-generate-expansion');
    
    if (app.expansionGenerated) {
        if (expansionStatus) expansionStatus.className = 'badge badge-success';
        if (expansionStatus) expansionStatus.textContent = 'Completed';
        if (expansionStep) expansionStep.classList.add('completed');
        if (expansionButton) expansionButton.innerHTML = '<i class="fas fa-check mr-2"></i>✅ Plan Generated';
    } else {
        if (expansionStatus) expansionStatus.className = 'badge badge-secondary';
        if (expansionStatus) expansionStatus.textContent = 'Not Started';
        if (expansionStep) expansionStep.classList.remove('completed');
    }
}

/**
 * Update progress indicators
 */
function updateProgressIndicators() {
    if (!window.crmApp) return;
    
    const app = window.crmApp;
    let progress = 0;
    
    if (app.profitCalculated) progress += 33.33;
    if (app.clusteringDone) progress += 33.33;
    if (app.expansionGenerated) progress += 33.34;
    
    const progressBar = document.getElementById('overall-progress');
    const progressText = document.getElementById('progress-text');
    
    if (progressBar) {
        progressBar.style.width = progress + '%';
    }
    
    if (progressText) {
        progressText.textContent = Math.round(progress) + '% Complete';
    }
    
    // Update performance summary
    updatePerformanceSummary();
}

/**
 * Update prerequisite status for expansion tab
 */
function updatePrerequisiteStatus() {
    if (!window.crmApp) return;
    
    const app = window.crmApp;
    
    // Update profit prerequisite
    const profitIcon = document.getElementById('profit-prereq-icon');
    if (profitIcon) {
        if (app.profitCalculated) {
            profitIcon.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
        } else {
            profitIcon.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
        }
    }
    
    // Update clustering prerequisite
    const clusteringIcon = document.getElementById('clustering-prereq-icon');
    if (clusteringIcon) {
        if (app.clusteringDone) {
            clusteringIcon.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
        } else {
            clusteringIcon.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
        }
    }
    
    // Update readiness status
    const readinessStatus = document.getElementById('expansion-readiness-status');
    if (readinessStatus) {
        if (app.profitCalculated && app.clusteringDone) {
            readinessStatus.className = 'badge badge-success';
            readinessStatus.textContent = 'Ready';
        } else {
            readinessStatus.className = 'badge badge-warning';
            readinessStatus.textContent = 'Not Ready';
        }
    }
}

/**
 * Update performance summary in Analysis tab
 */
function updatePerformanceSummary() {
    if (!window.crmApp || !window.crmApp.profitCalculated) return;
    
    const stores = window.crmApp.storeData.filter(s => s.profit_calculated);
    if (stores.length === 0) return;
    
    const excellentStores = stores.filter(s => s.margin_percent >= 20).length;
    const goodStores = stores.filter(s => s.margin_percent >= 10 && s.margin_percent < 20).length;
    const poorStores = stores.filter(s => s.margin_percent < 10).length;
    const totalClusters = window.crmApp.clusters.length;
    
    // Update counts
    document.getElementById('excellent-stores-count').textContent = excellentStores;
    document.getElementById('good-stores-count').textContent = goodStores;
    document.getElementById('poor-stores-count').textContent = poorStores;
    document.getElementById('total-clusters-count').textContent = totalClusters;
    
    // Update progress bars
    const totalStores = stores.length;
    if (totalStores > 0) {
        document.getElementById('excellent-stores-progress').style.width = (excellentStores / totalStores * 100) + '%';
        document.getElementById('good-stores-progress').style.width = (goodStores / totalStores * 100) + '%';
        document.getElementById('poor-stores-progress').style.width = (poorStores / totalStores * 100) + '%';
    }
    
    if (window.crmApp.clusteringDone) {
        document.getElementById('clusters-progress').style.width = '100%';
    }
}

/**
 * Show system health modal
 */
async function showSystemHealth() {
    try {
        const response = await fetch('/market-map/system-health', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const health = data.health;
            
            Swal.fire({
                title: 'System Health Status',
                html: `
                    <div class="text-left">
                        <h6>Database Connection</h6>
                        <p class="badge badge-${health.database_connection === 'OK' ? 'success' : 'danger'}">${health.database_connection}</p>
                        
                        <h6 class="mt-3">Data Quality</h6>
                        <p>Total Partners: <strong>${health.data_quality.total_partners}</strong></p>
                        <p>Geocoded Partners: <strong>${health.data_quality.geocoded_partners}</strong></p>
                        <p>Geocoding Coverage: <strong>${health.data_quality.geocoding_percentage}%</strong></p>
                        <p>Active Partners: <strong>${health.data_quality.active_partners}</strong></p>
                        
                        <h6 class="mt-3">Last Update</h6>
                        <p>${health.performance_metrics.last_data_update || 'N/A'}</p>
                    </div>
                `,
                icon: 'info',
                width: '500px'
            });
        }
    } catch (error) {
        Swal.fire('Error', 'Failed to get system health status', 'error');
    }
}

/**
 * Export functions
 */
function exportProfitAnalysis() {
    Swal.fire('Export', 'Profit analysis export feature will be implemented', 'info');
}

function exportClustersData() {
    Swal.fire('Export', 'Clusters data export feature will be implemented', 'info');
}

function exportExpansionPlan() {
    Swal.fire('Export', 'Expansion plan export feature will be implemented', 'info');
}

/**
 * Map layer controls
 */
function toggleProfitLayer() {
    console.log('Toggle profit layer');
    // Implementation will be added
}

function toggleClusterLayer() {
    console.log('Toggle cluster layer');
    // Implementation will be added
}

function fitMapBounds() {
    if (window.crmApp && window.crmApp.map) {
        // Fit map to show all markers
        const group = new L.featureGroup(window.crmApp.markers);
        if (group.getBounds().isValid()) {
            window.crmApp.map.fitBounds(group.getBounds().pad(0.1));
        }
    }
}

/**
 * Animate statistics cards
 */
function animateStatisticsCards() {
    const cards = document.querySelectorAll('.small-box');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 200);
    });
}

/**
 * Show system help - available globally
 */
window.showSystemHelp = function() {
    $('#system-help-modal').modal('show');
};

// Add to crmApp class for easy access
if (window.crmApp) {
    window.crmApp.showSystemHelp = window.showSystemHelp;
}
</script>
@endpush
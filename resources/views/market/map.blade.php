@extends('layouts.template')

@section('page_title', 'CRM Market Intelligence')

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

    <!-- CRM Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-info hover-lift">
                <div class="inner">
                    <h3 id="total-partners" class="animate-counter">-</h3>
                    <p>Total Partners</p>
                </div>
                <div class="icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-arrow-up"></i> +5.2% from last month
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-success hover-lift">
                <div class="inner">
                    <h3 id="active-partners" class="animate-counter">-</h3>
                    <p>Active Partners</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-arrow-up"></i> +3.1% from last month
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-warning hover-lift">
                <div class="inner">
                    <h3 id="high-performers" class="animate-counter">-</h3>
                    <p>High Performers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-arrow-up"></i> +8.4% from last month
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-danger hover-lift">
                <div class="inner">
                    <h3><span id="coverage-percentage" class="animate-counter">-</span>%</h3>
                    <p>GPS Coverage</p>
                </div>
                <div class="icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="small-box-footer">
                    <span class="text-white">
                        <i class="fas fa-arrow-up"></i> +2.7% from last month
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- CRM Controls Panel -->
    <div class="card card-outline card-primary shadow-lg-custom">
        <div class="card-header bg-gradient-primary">
            <h3 class="card-title text-white">
                <i class="fas fa-sliders-h mr-2"></i>
                CRM Intelligence Controls
            </h3>
            <div class="card-tools">
                <div class="btn-group">
                    <button class="btn btn-light btn-sm" id="btn-refresh-map" title="Refresh Data">
                        <i class="fas fa-sync-alt mr-1"></i>Refresh
                    </button>
                    <button class="btn btn-light btn-sm" id="btn-clear-cache" title="Clear Cache">
                        <i class="fas fa-trash mr-1"></i>Clear Cache
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters Row -->
            <div class="row mb-3">
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="form-group">
                        <label class="form-label text-sm font-weight-bold text-uppercase">
                            <i class="fas fa-map mr-1"></i>Territory Filter
                        </label>
                        <select class="form-control form-control-sm" id="filter-wilayah">
                            <option value="all">All Territories</option>
                            <option value="Kota Malang">Kota Malang</option>
                            <option value="Kabupaten Malang">Kabupaten Malang</option>
                            <option value="Kota Batu">Kota Batu</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="form-group">
                        <label class="form-label text-sm font-weight-bold text-uppercase">
                            <i class="fas fa-users mr-1"></i>Partner Segment
                        </label>
                        <select class="form-control form-control-sm" id="filter-segment">
                            <option value="all">All Segments</option>
                            <option value="Premium Partner">Premium Partners</option>
                            <option value="Growth Partner">Growth Partners</option>
                            <option value="Standard Partner">Standard Partners</option>
                            <option value="New Partner">New Partners</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="form-group">
                        <label class="form-label text-sm font-weight-bold text-uppercase">
                            <i class="fas fa-chart-bar mr-1"></i>Performance Level
                        </label>
                        <select class="form-control form-control-sm" id="filter-performance">
                            <option value="all">All Performance</option>
                            <option value="high">High (80-100)</option>
                            <option value="medium">Medium (60-79)</option>
                            <option value="low">Low (40-59)</option>
                            <option value="very-low">Very Low (0-39)</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="form-group">
                        <label class="form-label text-sm font-weight-bold text-uppercase">
                            <i class="fas fa-calendar mr-1"></i>Date Range
                        </label>
                        <select class="form-control form-control-sm" id="filter-date">
                            <option value="all">All Time</option>
                            <option value="7d">Last 7 Days</option>
                            <option value="30d">Last 30 Days</option>
                            <option value="90d">Last 90 Days</option>
                            <option value="1y">Last Year</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Toggle Controls -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="toggle-cluster" checked>
                            <label class="custom-control-label" for="toggle-cluster">
                                <i class="fas fa-layer-group mr-1"></i>Partner Clustering
                            </label>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="toggle-heatmap">
                            <label class="custom-control-label" for="toggle-heatmap">
                                <i class="fas fa-fire mr-1"></i>Performance Heatmap
                            </label>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="toggle-grid-heatmap" checked>
                            <label class="custom-control-label" for="toggle-grid-heatmap">
                                <i class="fas fa-th mr-1"></i>Territory Grid
                            </label>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="toggle-performance">
                            <label class="custom-control-label" for="toggle-performance">
                                <i class="fas fa-percentage mr-1"></i>Performance Scores
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <div class="btn-group btn-group-sm d-flex flex-wrap justify-content-center" role="group">
                        <button type="button" class="btn btn-primary btn-crm" id="btn-price-recommendations">
                            <i class="fas fa-dollar-sign mr-1"></i>Price Intelligence
                        </button>
                        <button type="button" class="btn btn-success btn-crm" id="btn-partner-analysis">
                            <i class="fas fa-chart-bar mr-1"></i>Partner Analysis
                        </button>
                        <button type="button" class="btn btn-warning btn-crm" id="btn-market-opportunities">
                            <i class="fas fa-bullseye mr-1"></i>Market Opportunities
                        </button>
                        <button type="button" class="btn btn-info btn-crm" id="btn-export-insights">
                            <i class="fas fa-download mr-1"></i>Export Insights
                        </button>
                        <button type="button" class="btn btn-secondary btn-crm" id="btn-system-health">
                            <i class="fas fa-heartbeat mr-1"></i>System Health
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Map Column -->
        <div class="col-lg-8 mb-4">
            <div class="card card-primary card-outline shadow-md-custom">
                <div class="card-header bg-gradient-primary">
                    <h3 class="card-title text-white">
                        <i class="fas fa-map mr-2"></i>
                        Geographic CRM Intelligence
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light" id="visible-partners-badge">
                            <i class="fas fa-eye mr-1"></i>
                            <span id="visible-partners-count">0</span> visible
                        </span>
                        <button type="button" class="btn btn-tool text-white" data-card-widget="maximize" title="Maximize">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button type="button" class="btn btn-tool text-white" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="market-map-container">
                        <div id="market-map" style="height: 600px; width: 100%;"></div>
                        <div class="map-loading-overlay" id="map-loading" style="display: none;">
                            <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <h5 class="text-muted">Loading CRM Map Data...</h5>
                                <p class="text-muted">Please wait while we load partner information</p>
                            </div>
                        </div>
                        
                        <!-- Map Error State -->
                        <div class="map-error-overlay" id="map-error" style="display: none;">
                            <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-danger">Map Loading Error</h5>
                                <p class="text-muted text-center">Unable to load map data.<br>Please check your connection and try again.</p>
                                <button class="btn btn-primary" onclick="location.reload()">
                                    <i class="fas fa-sync-alt mr-1"></i>Retry
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Map Footer with Quick Stats -->
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="description-block">
                                <span class="description-percentage text-success">
                                    <i class="fas fa-caret-up"></i> 3.2%
                                </span>
                                <h5 class="description-header" id="footer-total-partners">0</h5>
                                <span class="description-text">TOTAL PARTNERS</span>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="description-block">
                                <span class="description-percentage text-warning">
                                    <i class="fas fa-caret-left"></i> 0.1%
                                </span>
                                <h5 class="description-header" id="footer-premium-partners">0</h5>
                                <span class="description-text">PREMIUM</span>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="description-block">
                                <span class="description-percentage text-success">
                                    <i class="fas fa-caret-up"></i> 2.1%
                                </span>
                                <h5 class="description-header" id="footer-active-partners">0</h5>
                                <span class="description-text">ACTIVE</span>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="description-block">
                                <span class="description-percentage text-info">
                                    <i class="fas fa-caret-up"></i> 1.5%
                                </span>
                                <h5 class="description-header" id="footer-coverage">0%</h5>
                                <span class="description-text">COVERAGE</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CRM Insights Sidebar -->
        <div class="col-lg-4">
            <!-- AI Insights Card -->
            <div class="card card-success card-outline shadow-md-custom mb-4">
                <div class="card-header bg-gradient-success">
                    <h3 class="card-title text-white">
                        <i class="fas fa-lightbulb mr-2"></i>
                        AI Insights
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light animate-pulse">Live</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="crm-insights-content">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-brain fa-spin mb-2 fa-2x text-success"></i>
                            <p class="mb-0">Analyzing partner data...</p>
                            <small>Generating AI-powered insights</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Market Opportunities Card -->
            <div class="card card-warning card-outline shadow-md-custom mb-4">
                <div class="card-header bg-gradient-warning">
                    <h3 class="card-title">
                        <i class="fas fa-bullseye mr-2"></i>
                        Market Opportunities
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">High Priority</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="market-opportunities-content">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-search fa-spin mb-2 fa-2x text-warning"></i>
                            <p class="mb-0">Identifying opportunities...</p>
                            <small>Scanning market gaps</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-warning btn-sm btn-block btn-crm" id="btn-view-all-opportunities">
                        <i class="fas fa-eye mr-1"></i>View All Opportunities
                    </button>
                </div>
            </div>

            <!-- Price Intelligence Card -->
            <div class="card card-info card-outline shadow-md-custom mb-4">
                <div class="card-header bg-gradient-info">
                    <h3 class="card-title text-white">
                        <i class="fas fa-dollar-sign mr-2"></i>
                        Price Intelligence
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">Updated</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="price-intelligence-content">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-chart-line fa-spin mb-2 fa-2x text-info"></i>
                            <p class="mb-0">Processing price data...</p>
                            <small>Analyzing market pricing</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info btn-sm btn-block btn-crm" id="load-price-intel">
                        <i class="fas fa-search mr-1"></i>Advanced Analysis
                    </button>
                </div>
            </div>

            <!-- Partner Performance Card -->
            <div class="card card-primary card-outline shadow-md-custom mb-4">
                <div class="card-header bg-gradient-primary">
                    <h3 class="card-title text-white">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Partner Performance
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">Real-time</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="partner-performance-content">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-users fa-spin mb-2 fa-2x text-primary"></i>
                            <p class="mb-0">Calculating performance...</p>
                            <small>Analyzing partner metrics</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary btn-sm btn-block btn-crm" id="btn-performance-report">
                        <i class="fas fa-file-alt mr-1"></i>Generate Report
                    </button>
                </div>
            </div>

            <!-- System Status Card -->
            <div class="card card-secondary card-outline shadow-md-custom">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-server mr-2"></i>
                        System Status
                    </h3>
                </div>
                <div class="card-body">
                    <div class="info-box mb-2">
                        <span class="info-box-icon bg-success"><i class="fas fa-database"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Database</span>
                            <span class="info-box-number">Online</span>
                        </div>
                    </div>
                    <div class="info-box mb-2">
                        <span class="info-box-icon bg-info"><i class="fas fa-memory"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Cache Status</span>
                            <span class="info-box-number">Active</span>
                        </div>
                    </div>
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Last Update</span>
                            <span class="info-box-number" id="last-update-time">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Price Intelligence Modal -->
<div class="modal fade" id="price-intelligence-modal" tabindex="-1" role="dialog" aria-labelledby="priceIntelligenceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title text-white" id="priceIntelligenceModalLabel">
                    <i class="fas fa-brain mr-2"></i>
                    Advanced Price Intelligence
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Price Analysis Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label font-weight-bold">Territory Filter</label>
                            <select class="form-control" id="price-territory-filter">
                                <option value="">All Territories</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label font-weight-bold">Product Filter</label>
                            <select class="form-control" id="price-product-filter">
                                <option value="">All Products</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label font-weight-bold">&nbsp;</label>
                            <button class="btn btn-primary btn-block btn-crm" id="analyze-pricing">
                                <i class="fas fa-search mr-1"></i>Analyze Pricing
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Price Analysis Results -->
                <div id="price-analysis-results">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-chart-line fa-3x mb-3 text-primary"></i>
                        <h5>Price Intelligence Analysis</h5>
                        <p>Select filters and click "Analyze Pricing" to view comprehensive insights</p>
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-tags"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Price Points</span>
                                        <span class="info-box-number">Analysis Ready</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-percentage"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Margin Analysis</span>
                                        <span class="info-box-number">Available</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-chart-bar"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Market Trends</span>
                                        <span class="info-box-number">Ready</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
                <button type="button" class="btn btn-primary btn-crm" id="export-price-analysis">
                    <i class="fas fa-download mr-1"></i>Export Analysis
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Partner Analysis Modal -->
<div class="modal fade" id="partner-analysis-modal" tabindex="-1" role="dialog" aria-labelledby="partnerAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h4 class="modal-title text-white" id="partnerAnalysisModalLabel">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Partner Performance Analysis
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="partner-analysis-content">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3 text-success"></i>
                        <h5>Loading Partner Analysis...</h5>
                        <p>Calculating performance metrics and generating insights</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
                <button type="button" class="btn btn-success btn-crm" id="export-partner-analysis">
                    <i class="fas fa-download mr-1"></i>Export Report
                </button>
                <button type="button" class="btn btn-info btn-crm" id="btn-detailed-analysis">
                    <i class="fas fa-search-plus mr-1"></i>Detailed Analysis
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Market Opportunities Modal -->
<div class="modal fade" id="market-opportunities-modal" tabindex="-1" role="dialog" aria-labelledby="marketOpportunitiesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h4 class="modal-title" id="marketOpportunitiesModalLabel">
                    <i class="fas fa-bullseye mr-2"></i>
                    Market Expansion Opportunities
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="market-opportunities-analysis">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3 text-warning"></i>
                        <h5>Analyzing Market Opportunities...</h5>
                        <p>Identifying high-potential territories and expansion strategies</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
                <button type="button" class="btn btn-warning btn-crm" id="export-opportunities">
                    <i class="fas fa-download mr-1"></i>Export Opportunities
                </button>
                <button type="button" class="btn btn-primary btn-crm" id="btn-territory-details">
                    <i class="fas fa-map mr-1"></i>Territory Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- System Health Modal -->
<div class="modal fade" id="system-health-modal" tabindex="-1" role="dialog" aria-labelledby="systemHealthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title text-white" id="systemHealthModalLabel">
                    <i class="fas fa-heartbeat mr-2"></i>
                    System Health & Performance
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="system-health-content">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3 text-info"></i>
                        <h5>Checking System Health...</h5>
                        <p>Analyzing system performance and status</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
                <button type="button" class="btn btn-info btn-crm" id="btn-refresh-health">
                    <i class="fas fa-sync-alt mr-1"></i>Refresh Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Partner Side Panel (Hidden by default) -->
<div id="partner-side-panel" class="partner-side-panel" style="display: none;">
    <div class="side-panel-content">
        <div class="side-panel-header">
            <h6>Partner Quick View</h6>
            <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('partner-side-panel').style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="side-panel-body">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
/* Enhanced Market Map Styles untuk Production */
.market-map-container {
    position: relative;
    width: 100%;
    height: 600px;
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

.map-loading-overlay,
.map-error-overlay {
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

/* Animation Classes */
.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

.animate-counter {
    transition: all 0.3s ease;
}

.animate-pulse {
    animation: pulse 2s infinite;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Enhanced Small Box Hover Effects */
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Custom Button Styling */
.btn-crm {
    border-radius: 8px;
    font-weight: 600;
    padding: 8px 16px;
    font-size: 13px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-crm::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-crm:hover::before {
    left: 100%;
}

.btn-primary.btn-crm {
    background: linear-gradient(135deg, #007bff, #0056b3);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.btn-primary.btn-crm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

/* Custom Shadows */
.shadow-sm-custom {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.shadow-md-custom {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.shadow-lg-custom {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.16);
}

/* Gradient Backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
}

/* Enhanced Form Controls */
.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

/* Custom Switch Styling */
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #007bff;
    border-color: #007bff;
}

/* Partner Side Panel */
.partner-side-panel {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 300px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    z-index: 1050;
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

.side-panel-header {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: between;
    align-items: center;
}

.side-panel-body {
    padding: 15px;
}

/* Chart Container Styling */
.chart-container {
    position: relative;
    height: 250px;
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 15px;
}

/* Info Box Enhancements */
.info-box {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.info-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

/* Description Block Styling */
.description-block {
    padding: 10px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.description-block:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.02);
}

/* Modal Enhancements */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 16px 64px rgba(0, 0, 0, 0.2);
}

.modal-header {
    border-radius: 12px 12px 0 0;
    border-bottom: none;
    padding: 20px 24px;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    border-radius: 0 0 12px 12px;
    border-top: 1px solid #dee2e6;
    padding: 20px 24px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .market-map-container {
        height: 500px;
    }
}

@media (max-width: 768px) {
    .market-map-container {
        height: 400px;
        border-radius: 8px;
    }
    
    .btn-group .btn-crm {
        margin: 2px 0;
        width: 100%;
    }
    
    .custom-control {
        margin-bottom: 10px;
    }
    
    .partner-side-panel {
        width: 280px;
        top: 10px;
        right: 10px;
    }
}

@media (max-width: 576px) {
    .market-map-container {
        height: 350px;
    }
    
    .small-box .inner h3 {
        font-size: 1.5rem;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .partner-side-panel {
        width: calc(100% - 20px);
        left: 10px;
        right: 10px;
    }
}

/* Print Styles */
@media print {
    .btn, .modal, .custom-control, .alert, .card-tools {
        display: none !important;
    }
    
    .market-map-container {
        height: 400px !important;
        break-inside: avoid;
    }
    
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .card, .btn, .form-control {
        border: 2px solid !important;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .animate-pulse, .animate-fade-in {
        animation: none !important;
    }
}

/* Dark mode considerations */
@media (prefers-color-scheme: dark) {
    .chart-container, .info-box, .description-block {
        background: #2c3e50 !important;
        color: #ecf0f1 !important;
    }
    
    .modal-content {
        background: #2c3e50 !important;
        color: #ecf0f1 !important;
    }
}
</style>
@endpush

@push('js')
<!-- Enhanced Market Map CRM JavaScript -->
<script src="{{ asset('js/enhanced-market-map.js') }}"></script>

<script>
// Page-specific JavaScript untuk integrasi dengan AdminLTE
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 CRM Market Intelligence page loading...');
    
    // Initialize tooltips AdminLTE style
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Setup additional event handlers
    setupAdditionalEventHandlers();
    
    // Setup AdminLTE card widgets
    setupCardWidgets();
    
    // Setup counter animations
    setupCounterAnimations();
    
    console.log('✅ CRM Market Intelligence page loaded successfully');
});

/**
 * Setup additional event handlers
 */
function setupAdditionalEventHandlers() {
    try {
        // Clear cache button
        const clearCacheBtn = document.getElementById('btn-clear-cache');
        if (clearCacheBtn) {
            clearCacheBtn.addEventListener('click', handleClearCache);
        }
        
        // System health button
        const systemHealthBtn = document.getElementById('btn-system-health');
        if (systemHealthBtn) {
            systemHealthBtn.addEventListener('click', showSystemHealth);
        }
        
        // Performance report button
        const perfReportBtn = document.getElementById('btn-performance-report');
        if (perfReportBtn) {
            perfReportBtn.addEventListener('click', generatePerformanceReport);
        }
        
        // View all opportunities button
        const viewOppsBtn = document.getElementById('btn-view-all-opportunities');
        if (viewOppsBtn) {
            viewOppsBtn.addEventListener('click', () => {
                if (window.enhancedMarketMapCRMInstance) {
                    window.enhancedMarketMapCRMInstance.loadMarketOpportunitiesModal();
                }
            });
        }
        
        console.log('✅ Additional event handlers setup complete');
        
    } catch (error) {
        console.error('❌ Error setting up additional event handlers:', error);
    }
}

/**
 * Setup AdminLTE card widgets
 */
function setupCardWidgets() {
    try {
        // Handle card maximize/minimize
        $('[data-card-widget="maximize"]').on('click', function() {
            const mapContainer = document.getElementById('market-map');
            if (mapContainer) {
                // Trigger map resize after maximize/minimize
                setTimeout(() => {
                    if (window.enhancedMarketMapCRMInstance && window.enhancedMarketMapCRMInstance.map) {
                        window.enhancedMarketMapCRMInstance.map.invalidateSize();
                    }
                }, 300);
            }
        });
        
        // Handle card collapse
        $('[data-card-widget="collapse"]').on('click', function() {
            setTimeout(() => {
                if (window.enhancedMarketMapCRMInstance && window.enhancedMarketMapCRMInstance.map) {
                    window.enhancedMarketMapCRMInstance.map.invalidateSize();
                }
            }, 300);
        });
        
        // Handle sidebar toggle
        $('[data-widget="pushmenu"]').on('click', function() {
            setTimeout(() => {
                if (window.enhancedMarketMapCRMInstance && window.enhancedMarketMapCRMInstance.map) {
                    window.enhancedMarketMapCRMInstance.map.invalidateSize();
                }
            }, 300);
        });
        
    } catch (error) {
        console.warn('⚠️ AdminLTE widgets not available:', error);
    }
}

/**
 * Setup counter animations
 */
function setupCounterAnimations() {
    try {
        const counterElements = document.querySelectorAll('.animate-counter');
        
        const animateCounter = (element, target) => {
            const duration = 1500;
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        };
        
        // Observe when counters come into view
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                        const target = parseInt(entry.target.textContent.replace(/[^0-9]/g, '')) || 0;
                        if (target > 0) {
                            animateCounter(entry.target, target);
                            entry.target.classList.add('animated');
                        }
                    }
                });
            });
            
            counterElements.forEach(el => observer.observe(el));
        }
        
    } catch (error) {
        console.warn('⚠️ Counter animations not available:', error);
    }
}

/**
 * Handle clear cache
 */
async function handleClearCache() {
    try {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: 'Clear Cache?',
                text: 'This will clear all cached data and reload fresh information.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, clear cache',
                cancelButtonText: 'Cancel'
            });
            
            if (result.isConfirmed) {
                // Clear cache via API
                const response = await fetch('/market-map/clear-cache', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    // Clear local cache
                    if (window.enhancedMarketMapCRMInstance) {
                        window.enhancedMarketMapCRMInstance.clearAllCache();
                        window.enhancedMarketMapCRMInstance.refreshAllData();
                    }
                    
                    Swal.fire({
                        title: 'Cache Cleared!',
                        text: 'All cached data has been cleared and fresh data is being loaded.',
                        icon: 'success',
                        timer: 2000
                    });
                } else {
                    throw new Error('Failed to clear cache');
                }
            }
        }
    } catch (error) {
        console.error('❌ Error clearing cache:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'Failed to clear cache: ' + error.message,
                icon: 'error'
            });
        }
    }
}

/**
 * Show system health modal
 */
async function showSystemHealth() {
    try {
        $('#system-health-modal').modal('show');
        
        const content = document.getElementById('system-health-content');
        
        // Show loading
        content.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-spinner fa-spin fa-2x mb-3 text-info"></i>
                <h5>Checking System Health...</h5>
                <p>Analyzing system performance and status</p>
            </div>
        `;
        
        // Fetch system health data
        const response = await fetch('/market-map/system-health', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            renderSystemHealth(data.health);
        } else {
            throw new Error(data.message || 'Failed to get system health');
        }
        
    } catch (error) {
        console.error('❌ Error showing system health:', error);
        
        const content = document.getElementById('system-health-content');
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Failed to load system health: ${error.message}
            </div>
        `;
    }
}

/**
 * Render system health data
 */
function renderSystemHealth(health) {
    const content = document.getElementById('system-health-content');
    
    let html = `
        <div class="system-health-container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon ${health.database_connection === 'OK' ? 'bg-success' : 'bg-danger'}">
                            <i class="fas fa-database"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Database Connection</span>
                            <span class="info-box-number">${health.database_connection}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon ${health.cache_status === 'OK' ? 'bg-success' : 'bg-warning'}">
                            <i class="fas fa-memory"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Cache Status</span>
                            <span class="info-box-number">${health.cache_status}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-12">
                    <h6><i class="fas fa-chart-bar mr-2"></i>Data Quality Metrics</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="description-block">
                                <h5 class="description-header text-primary">${health.data_quality.total_partners}</h5>
                                <span class="description-text">Total Partners</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="description-block">
                                <h5 class="description-header text-success">${health.data_quality.geocoded_partners}</h5>
                                <span class="description-text">Geocoded Partners</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="description-block">
                                <h5 class="description-header text-info">${health.data_quality.geocoding_percentage}%</h5>
                                <span class="description-text">Geocoding Coverage</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="description-block">
                                <h5 class="description-header text-warning">${health.data_quality.active_partners}</h5>
                                <span class="description-text">Active Partners</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <h6><i class="fas fa-tachometer-alt mr-2"></i>Performance Metrics</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>Cache Hit Rate</strong></td>
                                    <td>${health.performance_metrics.cache_hit_rate}</td>
                                </tr>
                                <tr>
                                    <td><strong>Average Response Time</strong></td>
                                    <td>${health.performance_metrics.avg_response_time}</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Data Update</strong></td>
                                    <td>${health.performance_metrics.last_data_update ? new Date(health.performance_metrics.last_data_update).toLocaleString() : 'Never'}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
}

/**
 * Generate performance report
 */
function generatePerformanceReport() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Generating Report',
            html: `
                <div class="report-progress">
                    <p>Generating comprehensive performance report...</p>
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 100%"></div>
                    </div>
                    <small class="text-muted">This report will include partner performance metrics, market analysis, and actionable insights.</small>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#007bff',
            timer: 4000,
            timerProgressBar: true
        });
    }
}

/**
 * Update footer statistics
 */
function updateFooterStats(data) {
    try {
        const elements = {
            'footer-total-partners': data.total_partners || 0,
            'footer-premium-partners': data.premium_partners || 0,
            'footer-active-partners': data.active_partners || 0,
            'footer-coverage': data.coverage_percentage || 0
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = typeof value === 'number' ? value.toLocaleString() : value;
            }
        });
        
    } catch (error) {
        console.warn('⚠️ Error updating footer stats:', error);
    }
}

/**
 * Update last update time
 */
function updateLastUpdateTime() {
    try {
        const element = document.getElementById('last-update-time');
        if (element) {
            element.textContent = new Date().toLocaleTimeString();
        }
    } catch (error) {
        console.warn('⚠️ Error updating last update time:', error);
    }
}

// Error handling for missing dependencies
window.addEventListener('error', function(e) {
    if (e.message.includes('Leaflet') || e.message.includes('L is not defined')) {
        console.error('❌ Leaflet library failed to load');
        
        const mapContainer = document.getElementById('market-map');
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="error-state text-center p-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">Map Library Error</h5>
                    <p class="text-muted">Unable to load map components. Please check your internet connection.</p>
                    <button class="btn btn-primary btn-crm" onclick="location.reload()">
                        <i class="fas fa-sync-alt mr-1"></i>Retry Loading
                    </button>
                </div>
            `;
        }
    }
});

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log(`📊 CRM page loaded in ${loadTime}ms`);
            
            if (loadTime > 5000) {
                console.warn('⚠️ Page load time is slower than expected');
                
                // Show performance warning
                if (typeof $ !== 'undefined' && $(document).Toasts) {
                    $(document).Toasts('create', {
                        class: 'bg-warning',
                        title: 'Performance Notice',
                        subtitle: 'Loading Time',
                        body: 'Page loading took longer than expected. Consider optimizing your connection.',
                        autohide: true,
                        delay: 5000
                    });
                }
            }
            
            // Update last update time
            updateLastUpdateTime();
            
        }, 0);
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        $('.modal').modal('hide');
    }
    
    // F5 for refresh (prevent default and use our refresh)
    if (e.key === 'F5') {
        e.preventDefault();
        if (window.enhancedMarketMapCRMInstance) {
            window.enhancedMarketMapCRMInstance.forceRefreshData();
        }
    }
});

// Auto-update last update time every minute
setInterval(updateLastUpdateTime, 60000);
</script>
@endpush
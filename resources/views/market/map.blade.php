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

    <!-- CRM Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="small-box bg-info hover-lift">
                <div class="inner">
                    <h3 id="total-partners" class="animate-counter">-</h3>
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
                    <h3 id="geo-clusters" class="animate-counter">-</h3>
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
                    <h3><span id="avg-margin" class="animate-counter">-</span>%</h3>
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
                    <h3>Rp <span id="total-revenue" class="animate-counter">-</span></h3>
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
                                    <button type="button" class="btn btn-warning btn-block btn-lg mb-3" id="btn-calculate-profit">
                                        <i class="fas fa-calculator mr-2"></i>
                                        💰 Hitung Profit Semua Toko
                                    </button>
                                    <button type="button" class="btn btn-info btn-block btn-lg mb-3" id="btn-create-clustering">
                                        <i class="fas fa-project-diagram mr-2"></i>
                                        🗺️ Buat Geographic Clustering
                                    </button>
                                    <div class="row">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-primary btn-sm btn-block" id="btn-refresh-data">
                                                <i class="fas fa-sync-alt mr-1"></i>Refresh
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
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="profit-analysis-content">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-calculator fa-3x mb-3 text-warning"></i>
                                            <h5>Profit Analysis</h5>
                                            <p>Klik "Hitung Profit Semua Toko" pada tab Overview untuk memulai analisis</p>
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
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="clustering-analysis-content">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-project-diagram fa-3x mb-3 text-info"></i>
                                            <h5>Geographic Clustering</h5>
                                            <p>Klik "Buat Geographic Clustering" pada tab Overview untuk memulai pengelompokan</p>
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
                                        <button type="button" class="btn btn-success btn-sm" id="btn-generate-expansion">
                                            <i class="fas fa-magic mr-1"></i>Generate Expansion Plan
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
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

@endsection

@push('css')
<style>
/* Enhanced CRM Ekspansi Toko Styles */
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
    border-color: #28a745;
}

.recommendation-item.priority-sedang {
    --priority-color: #ffc107;
    border-color: #ffc107;
}

.recommendation-item.priority-rendah {
    --priority-color: #6c757d;
    border-color: #6c757d;
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
</style>
@endpush

@push('js')
<!-- Chart.js for visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// CRM Ekspansi Toko - Main Application
class CRMExpansionApp {
    constructor() {
        this.map = null;
        this.storeData = [];
        this.clusters = [];
        this.profitCalculated = false;
        this.clusteringDone = false;
        
        // Configuration
        this.config = {
            CLUSTER_RADIUS: 1.5, // km
            MAX_STORES_PER_CLUSTER: 5,
            MIN_PROFIT_MARGIN: 10, // percentage
            DEFAULT_HARGA_AWAL: 12000, // Rp
            MALANG_CENTER: [-7.9666, 112.6326]
        };
        
        this.init();
    }
    
    async init() {
        try {
            console.log('🚀 Initializing CRM Expansion App...');
            
            // Initialize map
            await this.initMap();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Load initial data
            await this.loadStoreData();
            
            console.log('✅ CRM Expansion App initialized successfully');
        } catch (error) {
            console.error('❌ Error initializing app:', error);
            this.showError('Failed to initialize application: ' + error.message);
        }
    }
    
    async initMap() {
        try {
            // Initialize Leaflet map
            this.map = L.map('market-map', {
                center: this.config.MALANG_CENTER,
                zoom: 13,
                zoomControl: true,
                attributionControl: true
            });

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors | CRM Expansion System',
                maxZoom: 18
            }).addTo(this.map);
            
            console.log('✅ Map initialized successfully');
        } catch (error) {
            throw new Error('Map initialization failed: ' + error.message);
        }
    }
    
    setupEventListeners() {
        // Action buttons
        document.getElementById('btn-calculate-profit')?.addEventListener('click', () => {
            this.calculateProfitAllStores();
        });
        
        document.getElementById('btn-create-clustering')?.addEventListener('click', () => {
            this.createGeographicClustering();
        });
        
        document.getElementById('btn-generate-expansion')?.addEventListener('click', () => {
            this.generateExpansionPlan();
        });
        
        document.getElementById('btn-refresh-data')?.addEventListener('click', () => {
            this.refreshData();
        });
        
        document.getElementById('btn-clear-cache')?.addEventListener('click', () => {
            this.clearCache();
        });
        
        // Tab switching
        document.querySelectorAll('[data-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.handleTabChange(e.target.getAttribute('href'));
            });
        });
    }
    
    async loadStoreData() {
        try {
            this.showLoading('Loading store data...');
            
            const response = await fetch('/market-map/toko-data', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.storeData = data.data;
                this.updateStatistics(data.summary);
                this.renderStoresOnMap();
                
                console.log(`✅ Loaded ${this.storeData.length} stores`);
            } else {
                throw new Error(data.message || 'Failed to load store data');
            }
        } catch (error) {
            console.error('❌ Error loading store data:', error);
            this.showError('Failed to load store data: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    renderStoresOnMap() {
        try {
            // Clear existing markers
            this.map.eachLayer((layer) => {
                if (layer instanceof L.Marker) {
                    this.map.removeLayer(layer);
                }
            });
            
            // Add store markers
            this.storeData.forEach(store => {
                if (store.has_coordinates) {
                    const marker = this.createStoreMarker(store);
                    marker.addTo(this.map);
                }
            });
            
            // Update visible count
            const visibleCount = this.storeData.filter(s => s.has_coordinates).length;
            document.getElementById('visible-partners-count').textContent = visibleCount;
            
        } catch (error) {
            console.error('❌ Error rendering stores:', error);
        }
    }
    
    createStoreMarker(store) {
        // Determine marker color based on profit margin (if calculated)
        let color = '#6c757d'; // Default gray
        if (this.profitCalculated && store.margin_percent !== undefined) {
            if (store.margin_percent >= 20) {
                color = '#28a745'; // Green - Excellent
            } else if (store.margin_percent >= 10) {
                color = '#ffc107'; // Yellow - Good
            } else {
                color = '#dc3545'; // Red - Poor
            }
        }
        
        const marker = L.circleMarker([store.latitude, store.longitude], {
            radius: 8,
            fillColor: color,
            color: '#ffffff',
            weight: 2,
            opacity: 0.8,
            fillOpacity: 0.6
        });
        
        // Create popup content
        const popupContent = this.createStorePopup(store);
        marker.bindPopup(popupContent);
        
        // Click handler
        marker.on('click', () => {
            this.showStoreDetail(store);
        });
        
        return marker;
    }
    
    createStorePopup(store) {
        let profitInfo = '';
        if (this.profitCalculated && store.margin_percent !== undefined) {
            profitInfo = `
                <div class="mt-2">
                    <strong>Profit Analysis:</strong><br>
                    <small>
                        Margin: ${store.margin_percent}% | 
                        Profit/Unit: Rp ${store.profit_per_unit?.toLocaleString()} |
                        Total Profit: Rp ${store.total_profit?.toLocaleString()}
                    </small>
                </div>
            `;
        }
        
        return `
            <div class="store-popup">
                <h6 class="mb-2">${store.nama_toko}</h6>
                <small>
                    <strong>Pemilik:</strong> ${store.pemilik}<br>
                    <strong>Lokasi:</strong> ${store.kecamatan}, ${store.kota_kabupaten}<br>
                    <strong>Status:</strong> ${store.status_aktif}<br>
                    <strong>Products:</strong> ${store.jumlah_barang} | 
                    <strong>Orders:</strong> ${store.total_pengiriman}
                </small>
                ${profitInfo}
            </div>
        `;
    }
    
    async calculateProfitAllStores() {
        try {
            console.log('💰 Calculating profit for all stores...');
            this.showLoading('Calculating profit for all stores...');
            
            // Simulate profit calculation (in real app, this would call an API)
            await this.simulateDelay(2000);
            
            // Calculate profit for each store
            this.storeData.forEach(store => {
                const hargaAwal = store.harga_awal || this.config.DEFAULT_HARGA_AWAL;
                const hargaJual = store.harga_jual || (hargaAwal * 1.2); // Default 20% markup
                const totalTerjual = store.total_terjual || Math.floor(Math.random() * 100) + 10;
                
                store.profit_per_unit = hargaJual - hargaAwal;
                store.margin_percent = ((store.profit_per_unit / hargaJual) * 100);
                store.total_profit = store.profit_per_unit * totalTerjual;
                store.roi = ((store.total_profit / (hargaAwal * totalTerjual)) * 100);
            });
            
            this.profitCalculated = true;
            
            // Update map markers with new colors
            this.renderStoresOnMap();
            
            // Update analysis tab
            this.renderProfitAnalysis();
            
            // Show success message
            this.showSuccess('Profit calculation completed!', 'All stores have been analyzed for profitability.');
            
            console.log('✅ Profit calculation completed');
            
        } catch (error) {
            console.error('❌ Error calculating profit:', error);
            this.showError('Failed to calculate profit: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    renderProfitAnalysis() {
        const container = document.getElementById('profit-analysis-content');
        if (!container) return;
        
        // Sort stores by margin percentage (descending)
        const sortedStores = [...this.storeData]
            .filter(store => store.margin_percent !== undefined)
            .sort((a, b) => b.margin_percent - a.margin_percent);
        
        let html = '<div class="profit-analysis-results">';
        
        sortedStores.forEach(store => {
            const marginColor = store.margin_percent >= 20 ? 'success' : 
                              store.margin_percent >= 10 ? 'warning' : 'danger';
            
            // Calculate expansion projection
            const expansionInvestment = this.config.DEFAULT_HARGA_AWAL * 100; // 100 units
            const breakEvenUnits = Math.ceil(expansionInvestment / store.profit_per_unit);
            const projectedMonthlyProfit = store.profit_per_unit * 50; // Assume 50 units/month
            
            html += `
                <div class="profit-item border-${marginColor}">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6>${store.nama_toko}</h6>
                        <span class="badge badge-${marginColor}">${store.margin_percent.toFixed(1)}%</span>
                    </div>
                    <div class="profit-metrics">
                        <div class="metric-item">
                            <div class="metric-value">Rp ${store.profit_per_unit.toLocaleString()}</div>
                            <div class="metric-label">Profit/Unit</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">${store.total_terjual || 0}</div>
                            <div class="metric-label">Units Sold</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">Rp ${store.total_profit.toLocaleString()}</div>
                            <div class="metric-label">Total Profit</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">${store.roi.toFixed(1)}%</div>
                            <div class="metric-label">ROI</div>
                        </div>
                    </div>
                    <div class="expansion-projection mt-3 p-2 bg-light rounded">
                        <small>
                            <strong>Proyeksi Ekspansi:</strong><br>
                            Investasi: Rp ${expansionInvestment.toLocaleString()} | 
                            Break-even: ${breakEvenUnits} units | 
                            Profit/bulan: Rp ${projectedMonthlyProfit.toLocaleString()}
                        </small>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    async createGeographicClustering() {
        try {
            if (!this.profitCalculated) {
                this.showWarning('Please calculate profit first', 'Run profit analysis before creating clusters.');
                return;
            }
            
            console.log('🗺️ Creating geographic clustering...');
            this.showLoading('Creating geographic clusters...');
            
            await this.simulateDelay(2000);
            
            // Perform clustering
            this.clusters = this.performClustering();
            this.clusteringDone = true;
            
            // Render cluster boundaries on map
            this.renderClustersOnMap();
            
            // Update analysis tab
            this.renderClusteringAnalysis();
            
            this.showSuccess('Geographic clustering completed!', `Created ${this.clusters.length} clusters.`);
            
            console.log(`✅ Created ${this.clusters.length} clusters`);
            
        } catch (error) {
            console.error('❌ Error creating clusters:', error);
            this.showError('Failed to create clusters: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    performClustering() {
        const clusters = [];
        const processed = new Set();
        let clusterId = 1;
        
        this.storeData.forEach(store => {
            if (processed.has(store.toko_id) || !store.has_coordinates) {
                return;
            }
            
            const clusterStores = [store];
            processed.add(store.toko_id);
            
            // Find nearby stores
            this.storeData.forEach(otherStore => {
                if (processed.has(otherStore.toko_id) || !otherStore.has_coordinates) {
                    return;
                }
                
                const distance = this.calculateDistance(
                    store.latitude, store.longitude,
                    otherStore.latitude, otherStore.longitude
                );
                
                if (distance <= this.config.CLUSTER_RADIUS) {
                    clusterStores.push(otherStore);
                    processed.add(otherStore.toko_id);
                }
            });
            
            // Calculate cluster metrics
            const metrics = this.calculateClusterMetrics(clusterStores);
            
            clusters.push({
                cluster_id: 'CLUSTER_' + String.fromCharCode(64 + clusterId),
                store_count: clusterStores.length,
                stores: clusterStores,
                center: this.calculateClusterCenter(clusterStores),
                metrics: metrics,
                expansion_potential: Math.max(0, this.config.MAX_STORES_PER_CLUSTER - clusterStores.length)
            });
            
            clusterId++;
        });
        
        return clusters;
    }
    
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    calculateClusterCenter(stores) {
        const validStores = stores.filter(s => s.has_coordinates);
        if (validStores.length === 0) return this.config.MALANG_CENTER;
        
        const totalLat = validStores.reduce((sum, s) => sum + s.latitude, 0);
        const totalLng = validStores.reduce((sum, s) => sum + s.longitude, 0);
        
        return [totalLat / validStores.length, totalLng / validStores.length];
    }
    
    calculateClusterMetrics(stores) {
        const totalRevenue = stores.reduce((sum, s) => sum + (s.revenue || 0), 0);
        const totalProfit = stores.reduce((sum, s) => sum + (s.total_profit || 0), 0);
        const avgMargin = stores.length > 0 ? 
            stores.reduce((sum, s) => sum + (s.margin_percent || 0), 0) / stores.length : 0;
        
        const areas = [...new Set(stores.map(s => s.kecamatan))];
        
        return {
            total_revenue: totalRevenue,
            total_profit: totalProfit,
            avg_margin: avgMargin,
            area_coverage: areas.join(', ')
        };
    }
    
    renderClustersOnMap() {
        // Remove existing cluster boundaries
        this.map.eachLayer((layer) => {
            if (layer instanceof L.Circle && layer.options.className === 'cluster-boundary') {
                this.map.removeLayer(layer);
            }
        });
        
        // Add cluster boundaries
        this.clusters.forEach(cluster => {
            const circle = L.circle(cluster.center, {
                radius: this.config.CLUSTER_RADIUS * 1000, // Convert to meters
                color: '#8b5cf6',
                weight: 2,
                opacity: 0.6,
                fillColor: '#8b5cf6',
                fillOpacity: 0.1,
                className: 'cluster-boundary'
            });
            
            circle.bindPopup(this.createClusterPopup(cluster));
            circle.addTo(this.map);
        });
    }
    
    createClusterPopup(cluster) {
        return `
            <div class="cluster-popup">
                <h6 class="mb-2">${cluster.cluster_id}</h6>
                <small>
                    <strong>Stores:</strong> ${cluster.store_count}<br>
                    <strong>Avg Margin:</strong> ${cluster.metrics.avg_margin.toFixed(1)}%<br>
                    <strong>Total Revenue:</strong> Rp ${cluster.metrics.total_revenue.toLocaleString()}<br>
                    <strong>Area:</strong> ${cluster.metrics.area_coverage}<br>
                    <strong>Expansion Potential:</strong> ${cluster.expansion_potential} stores
                </small>
            </div>
        `;
    }
    
    renderClusteringAnalysis() {
        const container = document.getElementById('clustering-analysis-content');
        if (!container) return;
        
        let html = '<div class="clustering-analysis-results">';
        
        this.clusters.forEach(cluster => {
            const marginColor = cluster.metrics.avg_margin >= 20 ? 'success' : 
                              cluster.metrics.avg_margin >= 15 ? 'warning' : 'danger';
            
            html += `
                <div class="cluster-item border-${marginColor}">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6>${cluster.cluster_id}</h6>
                        <span class="badge badge-${marginColor}">${cluster.metrics.avg_margin.toFixed(1)}%</span>
                    </div>
                    <div class="cluster-info">
                        <p class="mb-2"><strong>Total Stores:</strong> ${cluster.store_count} | 
                        <strong>Area:</strong> ${cluster.metrics.area_coverage}</p>
                        <p class="mb-2"><strong>Revenue:</strong> Rp ${cluster.metrics.total_revenue.toLocaleString()}</p>
                        <p class="mb-2"><strong>Potensi Ekspansi:</strong> ${cluster.expansion_potential} toko lagi</p>
                    </div>
                    <div class="store-list mt-2">
                        <small><strong>Stores in cluster:</strong></small>
                        <ul class="list-unstyled mb-0">
                            ${cluster.stores.slice(0, 3).map(store => 
                                `<li><small>• ${store.nama_toko} (${store.kecamatan})</small></li>`
                            ).join('')}
                            ${cluster.stores.length > 3 ? `<li><small>• and ${cluster.stores.length - 3} more...</small></li>` : ''}
                        </ul>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    async generateExpansionPlan() {
        try {
            if (!this.profitCalculated || !this.clusteringDone) {
                this.showWarning('Prerequisites not met', 'Please complete profit analysis and geographic clustering first.');
                return;
            }
            
            console.log('🚀 Generating expansion plan...');
            this.showLoading('Generating expansion recommendations...');
            
            await this.simulateDelay(2000);
            
            // Generate recommendations
            const recommendations = this.createExpansionRecommendations();
            
            // Render recommendations
            this.renderExpansionRecommendations(recommendations);
            
            this.showSuccess('Expansion plan generated!', `Found ${recommendations.length} expansion opportunities.`);
            
            console.log(`✅ Generated ${recommendations.length} recommendations`);
            
        } catch (error) {
            console.error('❌ Error generating expansion plan:', error);
            this.showError('Failed to generate expansion plan: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    createExpansionRecommendations() {
        const recommendations = [];
        
        this.clusters.forEach(cluster => {
            if (cluster.metrics.avg_margin >= this.config.MIN_PROFIT_MARGIN && 
                cluster.expansion_potential > 0) {
                
                const score = this.calculateExpansionScore(cluster);
                const priority = this.determinePriority(cluster.metrics.avg_margin);
                const financialProjection = this.calculateFinancialProjection(cluster);
                
                recommendations.push({
                    cluster_id: cluster.cluster_id,
                    priority: priority,
                    score: score,
                    target_expansion: Math.min(cluster.expansion_potential, 3),
                    current_stores: cluster.store_count,
                    avg_margin: cluster.metrics.avg_margin,
                    area_coverage: cluster.metrics.area_coverage,
                    pricing_strategy: this.determinePricingStrategy(cluster.metrics.avg_margin),
                    ...financialProjection
                });
            }
        });
        
        // Sort by priority and score
        return recommendations.sort((a, b) => {
            const priorityOrder = { 'TINGGI': 3, 'SEDANG': 2, 'RENDAH': 1 };
            const aPriority = priorityOrder[a.priority] || 0;
            const bPriority = priorityOrder[b.priority] || 0;
            
            if (aPriority === bPriority) {
                return b.score - a.score;
            }
            return bPriority - aPriority;
        });
    }
    
    calculateExpansionScore(cluster) {
        let score = 0;
        
        // Margin weight (60%)
        score += (cluster.metrics.avg_margin / 30) * 60;
        
        // Expansion potential weight (30%)
        score += (cluster.expansion_potential / this.config.MAX_STORES_PER_CLUSTER) * 30;
        
        // Store count factor (10%)
        score += (cluster.store_count / this.config.MAX_STORES_PER_CLUSTER) * 10;
        
        return Math.min(100, Math.round(score));
    }
    
    determinePriority(avgMargin) {
        if (avgMargin >= 20) return 'TINGGI';
        if (avgMargin >= 15) return 'SEDANG';
        return 'RENDAH';
    }
    
    determinePricingStrategy(avgMargin) {
        if (avgMargin >= 25) return 'Premium Pricing';
        if (avgMargin <= 12) return 'Competitive Pricing';
        return 'Market Average';
    }
    
    calculateFinancialProjection(cluster) {
        const avgStoreRevenue = cluster.metrics.total_revenue / cluster.store_count;
        const avgStoreProfit = cluster.metrics.total_profit / cluster.store_count;
        const expansionCount = Math.min(cluster.expansion_potential, 3);
        
        const totalInvestment = expansionCount * this.config.DEFAULT_HARGA_AWAL * 100; // 100 units per store
        const projectedMonthlyProfit = expansionCount * (avgStoreProfit / 12);
        const paybackPeriod = projectedMonthlyProfit > 0 ? 
            Math.ceil(totalInvestment / projectedMonthlyProfit) : 99;
        
        return {
            total_investment: totalInvestment,
            projected_monthly_profit: Math.round(projectedMonthlyProfit),
            payback_period: paybackPeriod,
            recommended_price: Math.round(this.config.DEFAULT_HARGA_AWAL * (1 + cluster.metrics.avg_margin / 100))
        };
    }
    
    renderExpansionRecommendations(recommendations) {
        const container = document.getElementById('expansion-recommendations-content');
        if (!container) return;
        
        if (recommendations.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5>No Expansion Opportunities Found</h5>
                    <p>No clusters meet the minimum criteria for expansion (10% margin).</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="expansion-recommendations-list">';
        
        recommendations.forEach(rec => {
            html += `
                <div class="recommendation-item priority-${rec.priority.toLowerCase()}">
                    <div class="priority-badge">${rec.priority}</div>
                    
                    <div class="recommendation-header mb-3">
                        <h5>${rec.cluster_id}</h5>
                        <p class="text-muted mb-1">Area: ${rec.area_coverage}</p>
                        <div class="score-bar">
                            <div class="score-fill" style="width: ${rec.score}%"></div>
                        </div>
                        <small class="text-muted">Expansion Score: ${rec.score}/100</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="recommendation-details">
                                <p><strong>Target Ekspansi:</strong> ${rec.target_expansion} toko baru</p>
                                <p><strong>Current Stores:</strong> ${rec.current_stores}</p>
                                <p><strong>Pricing Strategy:</strong> ${rec.pricing_strategy}</p>
                                <p><strong>Recommended Price:</strong> Rp ${rec.recommended_price.toLocaleString()}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="financial-projection">
                                <p><strong>Proyeksi Profit Bulanan:</strong> <span class="text-success">Rp ${rec.projected_monthly_profit.toLocaleString()}</span></p>
                                <p><strong>Total Investasi:</strong> <span class="text-primary">Rp ${rec.total_investment.toLocaleString()}</span></p>
                                <p><strong>Payback Period:</strong> <span class="text-info">${rec.payback_period} bulan</span></p>
                                <p><strong>Score Keseluruhan:</strong> <span class="text-warning">${rec.score}/100</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Add summary
        const totalInvestment = recommendations.reduce((sum, rec) => sum + rec.total_investment, 0);
        const totalProjectedProfit = recommendations.reduce((sum, rec) => sum + rec.projected_monthly_profit, 0);
        
        html += `
            <div class="expansion-summary mt-4 p-3 bg-light rounded">
                <h6><i class="fas fa-calculator mr-2"></i>Investment Summary</h6>
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Total Recommendations:</strong> ${recommendations.length}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Total Investment:</strong> Rp ${totalInvestment.toLocaleString()}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Monthly Profit Projection:</strong> Rp ${totalProjectedProfit.toLocaleString()}</p>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Animate score bars
        setTimeout(() => {
            document.querySelectorAll('.score-fill').forEach(bar => {
                bar.style.transition = 'width 0.8s ease';
            });
        }, 100);
    }
    
    // Utility methods
    handleTabChange(tabId) {
        if (tabId === '#analysis' && !this.profitCalculated && !this.clusteringDone) {
            // Show helper message
        } else if (tabId === '#expansion' && (!this.profitCalculated || !this.clusteringDone)) {
            // Show prerequisites message
        }
    }
    
    updateStatistics(summary) {
        try {
            document.getElementById('total-partners').textContent = summary.total_toko || 0;
            document.getElementById('geo-clusters').textContent = this.clusters.length || 0;
            document.getElementById('avg-margin').textContent = summary.avg_margin || 0;
            document.getElementById('total-revenue').textContent = 
                summary.total_revenue ? (summary.total_revenue / 1000000).toFixed(1) + 'M' : '0';
        } catch (error) {
            console.warn('⚠️ Error updating statistics:', error);
        }
    }
    
    async refreshData() {
        try {
            this.showLoading('Refreshing data...');
            await this.loadStoreData();
            this.showSuccess('Data refreshed successfully!');
        } catch (error) {
            this.showError('Failed to refresh data: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    async clearCache() {
        try {
            const response = await fetch('/market-map/clear-cache', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                this.showSuccess('Cache cleared successfully!');
                this.refreshData();
            } else {
                throw new Error('Failed to clear cache');
            }
        } catch (error) {
            this.showError('Failed to clear cache: ' + error.message);
        }
    }
    
    showLoading(message = 'Loading...') {
        const indicator = document.getElementById('loading-indicator');
        if (indicator) {
            indicator.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                    <span>${message}</span>
                </div>
            `;
            indicator.style.display = 'block';
        }
        
        const mapLoading = document.getElementById('map-loading');
        if (mapLoading) {
            mapLoading.style.display = 'flex';
        }
    }
    
    hideLoading() {
        const indicator = document.getElementById('loading-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
        
        const mapLoading = document.getElementById('map-loading');
        if (mapLoading) {
            mapLoading.style.display = 'none';
        }
    }
    
    showSuccess(title, message = '') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'success',
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(title + (message ? '\n' + message : ''));
        }
    }
    
    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: message,
                icon: 'error'
            });
        } else {
            alert('Error: ' + message);
        }
    }
    
    showWarning(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning'
            });
        } else {
            alert(title + '\n' + message);
        }
    }
    
    simulateDelay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Initialize the application when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Starting CRM Expansion Application...');
    
    // Check for required dependencies
    if (typeof L === 'undefined') {
        console.error('❌ Leaflet library not loaded');
        return;
    }
    
    // Initialize the app
    window.crmApp = new CRMExpansionApp();
});

// Handle page unload
window.addEventListener('beforeunload', function() {
    if (window.crmApp) {
        // Cleanup if needed
        console.log('🧹 Cleaning up CRM application...');
    }
});
</script>
@endpush
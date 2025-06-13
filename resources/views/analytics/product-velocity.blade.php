@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-warning text-white shadow-lg">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-tachometer-alt mr-2"></i>
                                Product Velocity Analytics
                            </h2>
                            <p class="mb-0 opacity-75">
                                Detektif Produk - Mana yang Hot Seller, Mana yang Slow Mover?
                                Seperti speedometer untuk produk - tahu mana yang lari kencang dan mana yang jalan pelan!
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mb-0" id="hotSellerTrend">+40%</h4>
                                    <small>Hot Sellers</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0" id="deadStockTrend">-35%</h4>
                                    <small>Dead Stock</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Velocity Category Cards -->
    <div class="row mb-4" id="velocityCategoryCards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100 velocity-card" data-category="Hot Seller">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                🔥 Hot Sellers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="hotSellersCount">
                                {{ $productCategories['Hot Seller']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">&gt;80% sell-through, &lt;14 days</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fire fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 velocity-card" data-category="Good Mover">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                ✅ Good Movers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="goodMoversCount">
                                {{ $productCategories['Good Mover']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">60-80% sell-through, 14-30 days</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 velocity-card" data-category="Slow Mover">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                🐌 Slow Movers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="slowMoversCount">
                                {{ $productCategories['Slow Mover']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">30-60% sell-through, 30-60 days</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-secondary shadow h-100 velocity-card" data-category="Dead Stock">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                💀 Dead Stock
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="deadStockCount">
                                {{ $productCategories['Dead Stock']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">&lt;30% sell-through, &gt;60 days</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-skull fa-2x text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Controls -->
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="categoryFilter">
                                <option value="">All Categories</option>
                                <option value="Hot Seller">🔥 Hot Sellers</option>
                                <option value="Good Mover">✅ Good Movers</option>
                                <option value="Slow Mover">🐌 Slow Movers</option>
                                <option value="Dead Stock">💀 Dead Stock</option>
                                <option value="No Data">📊 No Data</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="velocityFilter">
                                <option value="">All Velocities</option>
                                <option value="high">High (&gt;80%)</option>
                                <option value="medium">Medium (60-80%)</option>
                                <option value="low">Low (30-60%)</option>
                                <option value="very_low">Very Low (&lt;30%)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" id="searchProduct" placeholder="Search product name...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-secondary w-100" id="resetFilters">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-success btn-sm" id="exportVelocity">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button type="button" class="btn btn-warning btn-sm" id="optimizePortfolio">
                    <i class="fas fa-magic"></i> Optimize Portfolio
                </button>
                <button type="button" class="btn btn-info btn-sm" id="refreshData">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Product Velocity Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-1"></i>
                        Product Velocity Analysis
                        <small class="text-muted ml-2" id="tableInfo">Loading...</small>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="velocityTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Product</th>
                                    <th width="12%" class="text-center">Category</th>
                                    <th width="10%" class="text-center">Sell-Through</th>
                                    <th width="10%" class="text-center">Days to Sell</th>
                                    <th width="8%" class="text-center">Return Rate</th>
                                    <th width="8%" class="text-center">Total Shipped</th>
                                    <th width="8%" class="text-center">Total Sold</th>
                                    <th width="8%" class="text-center">Velocity Score</th>
                                    <th width="6%" class="text-center">Trend</th>
                                    <th width="5%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="velocityTableBody">
                                @foreach($productCategories as $category => $products)
                                    @foreach($products as $index => $product)
                                    <tr data-category="{{ $category }}" 
                                        data-velocity="{{ $product['avg_sell_through'] }}"
                                        data-product-id="{{ $product['barang']->barang_id }}">
                                        <td class="text-center">
                                            <span class="badge badge-secondary">{{ $loop->parent->index * 1000 + $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="product-icon mr-3">
                                                    @if($category === 'Hot Seller')
                                                        <i class="fas fa-fire text-danger fa-lg"></i>
                                                    @elseif($category === 'Good Mover')
                                                        <i class="fas fa-check-circle text-success fa-lg"></i>
                                                    @elseif($category === 'Slow Mover')
                                                        <i class="fas fa-clock text-warning fa-lg"></i>
                                                    @else
                                                        <i class="fas fa-skull text-secondary fa-lg"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <strong>{{ $product['barang']->nama_barang }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $product['barang']->barang_kode }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $categoryColors = [
                                                    'Hot Seller' => 'danger',
                                                    'Good Mover' => 'success',
                                                    'Slow Mover' => 'warning',
                                                    'Dead Stock' => 'secondary',
                                                    'No Data' => 'info'
                                                ];
                                                $categoryIcons = [
                                                    'Hot Seller' => '🔥',
                                                    'Good Mover' => '✅',
                                                    'Slow Mover' => '🐌',
                                                    'Dead Stock' => '💀',
                                                    'No Data' => '📊'
                                                ];
                                                $color = $categoryColors[$category] ?? 'secondary';
                                                $icon = $categoryIcons[$category] ?? '❓';
                                            @endphp
                                            <span class="badge badge-{{ $color }} badge-lg">
                                                {{ $icon }} {{ $category }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress progress-sm mb-1">
                                                @php
                                                    $progressColor = $product['avg_sell_through'] >= 80 ? 'danger' : 
                                                                   ($product['avg_sell_through'] >= 60 ? 'success' : 
                                                                   ($product['avg_sell_through'] >= 30 ? 'warning' : 'secondary'));
                                                @endphp
                                                <div class="progress-bar bg-{{ $progressColor }}" 
                                                     style="width: {{ min($product['avg_sell_through'], 100) }}%"></div>
                                            </div>
                                            <strong>{{ number_format($product['avg_sell_through'], 1) }}%</strong>
                                        </td>
                                        <td class="text-center">
                                            @if($product['avg_days_to_sell'] <= 14)
                                                <span class="badge badge-danger">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @elseif($product['avg_days_to_sell'] <= 30)
                                                <span class="badge badge-success">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @elseif($product['avg_days_to_sell'] <= 60)
                                                <span class="badge badge-warning">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @endif
                                            <small class="d-block text-muted">days</small>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $returnRate = $product['return_rate'] ?? 0;
                                            @endphp
                                            @if($returnRate <= 5)
                                                <span class="badge badge-success">{{ number_format($returnRate, 1) }}%</span>
                                            @elseif($returnRate <= 15)
                                                <span class="badge badge-warning">{{ number_format($returnRate, 1) }}%</span>
                                            @else
                                                <span class="badge badge-danger">{{ number_format($returnRate, 1) }}%</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted">{{ number_format($product['total_shipped']) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-success font-weight-bold">{{ number_format($product['total_sold']) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="velocity-score">
                                                @if($product['velocity_score'] >= 80)
                                                    <div class="score-circle bg-danger">{{ number_format($product['velocity_score'], 0) }}</div>
                                                @elseif($product['velocity_score'] >= 60)
                                                    <div class="score-circle bg-success">{{ number_format($product['velocity_score'], 0) }}</div>
                                                @elseif($product['velocity_score'] >= 40)
                                                    <div class="score-circle bg-warning">{{ number_format($product['velocity_score'], 0) }}</div>
                                                @else
                                                    <div class="score-circle bg-secondary">{{ number_format($product['velocity_score'], 0) }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $trend = $product['monthly_trend'] ?? 'stable';
                                            @endphp
                                            @if($trend === 'improving')
                                                <i class="fas fa-arrow-up text-success" title="Improving"></i>
                                            @elseif($trend === 'declining')
                                                <i class="fas fa-arrow-down text-danger" title="Declining"></i>
                                            @else
                                                <i class="fas fa-minus text-muted" title="Stable"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-toggle="modal" 
                                                        data-target="#velocityDetailModal" 
                                                        data-product-id="{{ $product['barang']->barang_id }}"
                                                        data-product='@json($product)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if($category === 'Dead Stock')
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="recommendDiscontinue('{{ $product['barang']->barang_id }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @elseif($category === 'Hot Seller')
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="recommendIncrease('{{ $product['barang']->barang_id }}')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-1"></i>
                        Velocity Trend Analysis (Last 6 Months)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="velocityTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-map mr-1"></i>
                        Regional Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="regionalChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Recommendations -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Strategic Recommendations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success">
                                <i class="fas fa-arrow-up mr-1"></i>
                                Focus & Increase Production
                            </h6>
                            <ul class="list-unstyled" id="focusIncreaseList">
                                @foreach($strategicRecommendations['focus_increase'] ?? [] as $recommendation)
                                <li class="mb-2">
                                    <i class="fas fa-fire text-danger mr-2"></i>
                                    <strong>{{ $recommendation['product'] }}</strong>
                                    - {{ $recommendation['action'] }}
                                    <div class="text-muted small">{{ $recommendation['reason'] }}</div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger">
                                <i class="fas fa-arrow-down mr-1"></i>
                                Reduce or Discontinue
                            </h6>
                            <ul class="list-unstyled" id="reduceDiscontinueList">
                                @foreach($strategicRecommendations['reduce_discontinue'] ?? [] as $recommendation)
                                <li class="mb-2">
                                    <i class="fas fa-skull text-secondary mr-2"></i>
                                    <strong>{{ $recommendation['product'] }}</strong>
                                    - {{ $recommendation['action'] }}
                                    <div class="text-muted small">{{ $recommendation['reason'] }}</div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Velocity Detail Modal -->
<div class="modal fade" id="velocityDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Product Velocity Detail Analysis
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="velocityDetailContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading product details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Portfolio Optimization Modal -->
<div class="modal fade" id="portfolioOptimizationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-magic mr-2"></i>
                    Portfolio Optimization Results
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="portfolioOptimizationContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="applyOptimization">Apply Recommendations</button>
            </div>
        </div>
    </div>
</div>

<!-- Recommendation Modal -->
<div class="modal fade" id="recommendationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recommendationModalTitle">
                    <i class="fas fa-lightbulb mr-2"></i>
                    Product Recommendation
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="recommendationContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-danger { border-left: 4px solid #dc3545 !important; }
.border-left-success { border-left: 4px solid #28a745 !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.border-left-secondary { border-left: 4px solid #6c757d !important; }

.velocity-card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.velocity-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.velocity-card.selected {
    border: 2px solid #007bff;
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
}

.score-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin: 0 auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.product-icon {
    width: 30px;
    text-align: center;
}

.badge-lg {
    font-size: 0.8em;
    padding: 0.4em 0.6em;
}

.progress-sm { 
    height: 0.5rem; 
    margin-bottom: 0.25rem;
}

.table td { 
    vertical-align: middle; 
    font-size: 0.9em;
}

.bg-gradient-warning {
    background: linear-gradient(45deg, #ffc107, #ff8f00);
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

#velocityTable tbody tr:hover {
    background-color: #f8f9fa;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.recommendation-card {
    border-left: 4px solid #007bff;
    margin-bottom: 1rem;
}

.trend-icon {
    font-size: 1.2em;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8em;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.7rem;
    }
}
</style>

<script>
// Global variables
let velocityAnalytics = null;
let velocityTable = null;
let velocityTrendChart = null;
let regionalChart = null;
let currentData = null;

// Initialize when DOM is ready
$(document).ready(function() {
    initializeProductVelocityAnalytics();
});

function initializeProductVelocityAnalytics() {
    try {
        // Initialize DataTable
        initializeDataTable();
        
        // Load initial data
        loadVelocityData();
        
        // Setup event handlers
        setupEventHandlers();
        
        // Initialize charts
        initializeCharts();
        
        console.log('Product Velocity Analytics initialized successfully');
    } catch (error) {
        console.error('Error initializing Product Velocity Analytics:', error);
        showNotification('Failed to initialize analytics', 'error');
    }
}

function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#velocityTable')) {
        $('#velocityTable').DataTable().destroy();
    }
    
    velocityTable = $('#velocityTable').DataTable({
        "order": [[ 8, "desc" ]], // Sort by velocity score
        "pageLength": 25,
        "searching": false,
        "info": true,
        "lengthChange": false,
        "processing": true,
        "columnDefs": [
            { "orderable": false, "targets": [0, 10] }, // Disable sorting for # and Actions columns
            { "className": "text-center", "targets": [0, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
        ],
        "language": {
            "processing": "Loading velocity data...",
            "info": "Showing _START_ to _END_ of _TOTAL_ products",
            "infoEmpty": "No products found",
            "infoFiltered": "(filtered from _MAX_ total products)"
        }
    });
}

async function loadVelocityData() {
    try {
        showLoading('Loading velocity data...');
        
        const response = await fetch('/analytics/product-velocity/api/data', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            currentData = result.data;
            updateAnalyticsDashboard(result.data);
            updateCharts(result.data);
            updateTableInfo();
        } else {
            throw new Error('Failed to load data: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error loading velocity data:', error);
        showNotification('Failed to load velocity data: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function updateAnalyticsDashboard(data) {
    try {
        // Update category cards
        if (data.category_stats) {
            $('#hotSellersCount').text(data.category_stats.hot_sellers || 0);
            $('#goodMoversCount').text(data.category_stats.good_movers || 0);
            $('#slowMoversCount').text(data.category_stats.slow_movers || 0);
            $('#deadStockCount').text(data.category_stats.dead_stock || 0);
        }
        
        // Calculate trends
        if (data.velocity_trends) {
            const hotSellerTrend = calculateTrend(data.velocity_trends.hot_sellers);
            const deadStockTrend = calculateTrend(data.velocity_trends.dead_stock);
            
            $('#hotSellerTrend').text(hotSellerTrend >= 0 ? `+${hotSellerTrend}%` : `${hotSellerTrend}%`);
            $('#deadStockTrend').text(deadStockTrend >= 0 ? `+${deadStockTrend}%` : `${deadStockTrend}%`);
        }
    } catch (error) {
        console.error('Error updating dashboard:', error);
    }
}

function calculateTrend(dataArray) {
    if (!dataArray || dataArray.length < 2) return 0;
    
    const recent = dataArray[dataArray.length - 1];
    const previous = dataArray[dataArray.length - 2];
    
    if (previous === 0) return recent > 0 ? 100 : 0;
    
    return Math.round(((recent - previous) / previous) * 100);
}

function updateCharts(data) {
    try {
        updateVelocityTrendChart(data.velocity_trends);
        updateRegionalChart(data.regional_data);
    } catch (error) {
        console.error('Error updating charts:', error);
    }
}

function updateVelocityTrendChart(trends) {
    if (!trends || !velocityTrendChart) return;
    
    try {
        const months = generateMonthLabels(6);
        
        velocityTrendChart.data.labels = months;
        velocityTrendChart.data.datasets[0].data = trends.hot_sellers || [];
        velocityTrendChart.data.datasets[1].data = trends.good_movers || [];
        velocityTrendChart.data.datasets[2].data = trends.slow_movers || [];
        velocityTrendChart.data.datasets[3].data = trends.dead_stock || [];
        
        velocityTrendChart.update();
    } catch (error) {
        console.error('Error updating velocity trend chart:', error);
    }
}

function updateRegionalChart(regionalData) {
    if (!regionalData || !regionalChart) return;
    
    try {
        const labels = Object.keys(regionalData);
        const data = Object.values(regionalData);
        
        regionalChart.data.labels = labels;
        regionalChart.data.datasets[0].data = data;
        
        regionalChart.update();
    } catch (error) {
        console.error('Error updating regional chart:', error);
    }
}

function initializeCharts() {
    initializeVelocityTrendChart();
    initializeRegionalChart();
}

function initializeVelocityTrendChart() {
    const ctx = document.getElementById('velocityTrendChart');
    if (!ctx) return;
    
    try {
        velocityTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: generateMonthLabels(6),
                datasets: [{
                    label: 'Hot Sellers',
                    data: [],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }, {
                    label: 'Good Movers',
                    data: [],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }, {
                    label: 'Slow Movers',
                    data: [],
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }, {
                    label: 'Dead Stock',
                    data: [],
                    borderColor: '#6c757d',
                    backgroundColor: 'rgba(108, 117, 125, 0.1)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Number of Products',
                            font: { weight: 'bold' }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        title: { 
                            display: true, 
                            text: 'Month',
                            font: { weight: 'bold' }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    } catch (error) {
        console.error('Error initializing velocity trend chart:', error);
    }
}

function initializeRegionalChart() {
    const ctx = document.getElementById('regionalChart');
    if (!ctx) return;
    
    try {
        regionalChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Malang Kota', 'Malang Kabupaten', 'Kota Batu', 'Lainnya'],
                datasets: [{
                    data: [42, 28, 18, 12],
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545'
                    ],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 10,
                    hoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, index) => ({
                                    text: `${label}: ${data.datasets[0].data[index]}%`,
                                    fillStyle: data.datasets[0].backgroundColor[index],
                                    strokeStyle: data.datasets[0].backgroundColor[index],
                                    pointStyle: 'circle'
                                }));
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed}%`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    } catch (error) {
        console.error('Error initializing regional chart:', error);
    }
}

function setupEventHandlers() {
    // Category card click handlers
    $('.velocity-card').on('click', function() {
        const category = $(this).data('category');
        
        // Remove previous selections
        $('.velocity-card').removeClass('selected');
        $(this).addClass('selected');
        
        // Update filter
        $('#categoryFilter').val(category);
        filterTable();
    });
    
    // Filter handlers
    $('#categoryFilter, #velocityFilter').on('change', function() {
        filterTable();
    });
    
    $('#searchProduct').on('keyup', function() {
        filterTable();
    });
    
    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#categoryFilter, #velocityFilter').val('');
        $('#searchProduct').val('');
        $('.velocity-card').removeClass('selected');
        filterTable();
    });
    
    // Export functionality
    $('#exportVelocity').on('click', function() {
        exportVelocityData();
    });
    
    // Optimize portfolio
    $('#optimizePortfolio').on('click', function() {
        optimizePortfolio();
    });
    
    // Refresh data
    $('#refreshData').on('click', function() {
        loadVelocityData();
    });
    
    // Modal handlers
    $('#velocityDetailModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const productId = button.data('product-id');
        const productData = button.data('product');
        
        if (productData) {
            loadProductDetailModal(productData);
        }
    });
}

function filterTable() {
    const categoryFilter = $('#categoryFilter').val();
    const velocityFilter = $('#velocityFilter').val();
    const searchText = $('#searchProduct').val().toLowerCase();
    
    $('#velocityTable tbody tr').each(function() {
        let show = true;
        const $row = $(this);
        const category = $row.data('category');
        const velocity = parseFloat($row.data('velocity'));
        const text = $row.text().toLowerCase();
        
        // Category filter
        if (categoryFilter && category !== categoryFilter) {
            show = false;
        }
        
        // Velocity filter
        if (velocityFilter) {
            if (velocityFilter === 'high' && velocity < 80) show = false;
            if (velocityFilter === 'medium' && (velocity < 60 || velocity >= 80)) show = false;
            if (velocityFilter === 'low' && (velocity < 30 || velocity >= 60)) show = false;
            if (velocityFilter === 'very_low' && velocity >= 30) show = false;
        }
        
        // Search filter
        if (searchText && !text.includes(searchText)) {
            show = false;
        }
        
        $row.toggle(show);
    });
    
    updateTableInfo();
}

function updateTableInfo() {
    const totalRows = $('#velocityTable tbody tr').length;
    const visibleRows = $('#velocityTable tbody tr:visible').length;
    
    $('#tableInfo').text(`${visibleRows} of ${totalRows} products displayed`);
}

async function optimizePortfolio() {
    try {
        showLoading('Analyzing portfolio optimization...');
        
        const response = await fetch('/analytics/product-velocity/optimize-portfolio', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showPortfolioOptimizationModal(result);
            showNotification('Portfolio optimization completed successfully', 'success');
        } else {
            throw new Error(result.message || 'Failed to optimize portfolio');
        }
    } catch (error) {
        console.error('Error optimizing portfolio:', error);
        showNotification('Failed to optimize portfolio: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function showPortfolioOptimizationModal(data) {
    const content = generateOptimizationContent(data);
    $('#portfolioOptimizationContent').html(content);
    $('#portfolioOptimizationModal').modal('show');
}

function generateOptimizationContent(data) {
    const recommendations = data.recommendations || {};
    const summary = data.summary || {};
    
    let content = `
        <div class="optimization-summary mb-4">
            <h6 class="text-primary mb-3">📊 Portfolio Optimization Summary</h6>
            <div class="row text-center">
                <div class="col-3">
                    <div class="card border-success">
                        <div class="card-body py-2">
                            <h4 class="text-success mb-1">${summary.increase_count || 0}</h4>
                            <small class="text-muted">Increase Production</small>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card border-primary">
                        <div class="card-body py-2">
                            <h4 class="text-primary mb-1">${summary.maintain_count || 0}</h4>
                            <small class="text-muted">Maintain Levels</small>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card border-warning">
                        <div class="card-body py-2">
                            <h4 class="text-warning mb-1">${summary.reduce_count || 0}</h4>
                            <small class="text-muted">Reduce Production</small>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card border-danger">
                        <div class="card-body py-2">
                            <h4 class="text-danger mb-1">${summary.discontinue_count || 0}</h4>
                            <small class="text-muted">Discontinue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add detailed recommendations
    const sections = [
        { key: 'increase_production', title: '📈 Increase Production', color: 'success' },
        { key: 'maintain_production', title: '➡️ Maintain Production', color: 'primary' },
        { key: 'reduce_production', title: '📉 Reduce Production', color: 'warning' },
        { key: 'discontinue', title: '🚫 Consider Discontinuing', color: 'danger' }
    ];
    
    sections.forEach(section => {
        const items = recommendations[section.key] || [];
        if (items.length > 0) {
            content += `
                <div class="recommendation-section mb-4">
                    <h6 class="text-${section.color} mb-3">${section.title}</h6>
                    <div class="list-group">
            `;
            
            items.forEach(item => {
                content += `
                    <div class="list-group-item recommendation-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${item.product}</h6>
                                <p class="mb-1">${item.action}</p>
                                <small class="text-muted">${item.reason}</small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-${section.color} mb-1">${item.priority} Priority</span>
                                <div class="small text-muted">${item.potential_impact}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            content += `</div></div>`;
        }
    });
    
    return content;
}

async function recommendIncrease(barangId) {
    try {
        showLoading('Generating increase recommendation...');
        
        const response = await fetch(`/analytics/product-velocity/recommend-increase/${barangId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showRecommendationModal('increase', result);
        } else {
            throw new Error(result.message || 'Failed to generate recommendation');
        }
    } catch (error) {
        console.error('Error generating increase recommendation:', error);
        showNotification('Failed to generate recommendation: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function recommendDiscontinue(barangId) {
    try {
        showLoading('Generating discontinue recommendation...');
        
        const response = await fetch(`/analytics/product-velocity/recommend-discontinue/${barangId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showRecommendationModal('discontinue', result);
        } else {
            throw new Error(result.message || 'Failed to generate recommendation');
        }
    } catch (error) {
        console.error('Error generating discontinue recommendation:', error);
        showNotification('Failed to generate recommendation: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function showRecommendationModal(type, data) {
    const title = type === 'increase' ? '📈 Increase Production Recommendation' : '🚫 Discontinue Product Recommendation';
    const content = generateRecommendationContent(type, data);
    
    $('#recommendationModalTitle').html(title);
    $('#recommendationContent').html(content);
    $('#recommendationModal').modal('show');
}

function generateRecommendationContent(type, data) {
    let content = `
        <div class="recommendation-header mb-4">
            <h5 class="text-primary">${data.product}</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Current Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Velocity Score:</strong><br>
                                    <span class="h4 text-primary">${data.current_status.velocity_score}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Sell-Through:</strong><br>
                                    <span class="h4 text-success">${data.current_status.avg_sell_through}%</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>Days to Sell:</strong><br>
                                    <span class="h4 text-warning">${data.current_status.avg_days_to_sell}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Return Rate:</strong><br>
                                    <span class="h4 text-danger">${data.current_status.return_rate || 0}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
    `;
    
    if (type === 'increase') {
        content += `
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Increase Recommendations</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Recommended Increase:</strong> ${data.recommendations.increase_percentage}</p>
                            <p><strong>Timeline:</strong> ${data.recommendations.timing}</p>
                            <p><strong>Expected ROI:</strong> ${data.recommendations.expected_roi}</p>
                            <div class="mt-3">
                                <h6>Top Performing Locations:</h6>
                                <ul class="list-unstyled">
        `;
        
        (data.recommendations.target_locations || []).forEach(location => {
            content += `<li><i class="fas fa-map-marker-alt text-success mr-1"></i> ${location.name} (${location.performance_rating})</li>`;
        });
        
        content += `
                                </ul>
                            </div>
                        </div>
                    </div>
        `;
    } else {
        content += `
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">Discontinue Analysis</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Key Reasons:</h6>
                                <ul class="list-unstyled">
        `;
        
        (data.discontinue_analysis.reasons || []).forEach(reason => {
            content += `<li><i class="fas fa-exclamation-triangle text-warning mr-1"></i> ${reason}</li>`;
        });
        
        content += `
                                </ul>
                            </div>
                            <div class="mb-3">
                                <h6>Cost Analysis:</h6>
                                <ul class="list-unstyled small">
                                    <li><strong>Inventory Value:</strong> ${data.discontinue_analysis.cost_analysis.estimated_inventory_value}</li>
                                    <li><strong>Holding Cost:</strong> ${data.discontinue_analysis.cost_analysis.holding_cost_monthly}/month</li>
                                    <li><strong>Liquidation Value:</strong> ${data.discontinue_analysis.cost_analysis.liquidation_value}</li>
                                    <li><strong>Potential Loss:</strong> ${data.discontinue_analysis.cost_analysis.potential_loss}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
        `;
    }
    
    content += `
                </div>
            </div>
        </div>
    `;
    
    if (type === 'discontinue') {
        content += `
            <div class="phase-out-plan mb-4">
                <h6 class="text-warning">📋 Phase-Out Plan</h6>
                <div class="list-group">
        `;
        
        (data.discontinue_analysis.phase_out_plan || []).forEach((phase, index) => {
            content += `
                <div class="list-group-item">
                    <div class="d-flex align-items-center">
                        <span class="badge badge-warning badge-pill mr-3">${index + 1}</span>
                        ${phase}
                    </div>
                </div>
            `;
        });
        
        content += `
                </div>
            </div>
            
            <div class="alternative-products">
                <h6 class="text-info">🔄 Alternative Products</h6>
                <div class="row">
        `;
        
        (data.discontinue_analysis.alternative_products || []).forEach(product => {
            content += `
                <div class="col-md-4 mb-2">
                    <div class="card border-info">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-1">${product.name}</h6>
                            <small class="text-muted">${product.code}</small>
                            <div class="mt-1">
                                <span class="badge badge-${product.recommendation === 'Recommended' ? 'success' : 'warning'}">${product.recommendation}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        content += `
                </div>
            </div>
        `;
    }
    
    return content;
}

function loadProductDetailModal(productData) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Product Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Product Name:</th><td>${productData.barang.nama_barang}</td></tr>
                            <tr><th>Product Code:</th><td>${productData.barang.barang_kode}</td></tr>
                            <tr><th>Category:</th><td><span class="badge badge-primary">${productData.velocity_category}</span></td></tr>
                            <tr><th>Velocity Score:</th><td><strong>${productData.velocity_score}</strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Performance Metrics</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Sell-Through:</th><td><strong>${productData.avg_sell_through}%</strong></td></tr>
                            <tr><th>Days to Sell:</th><td>${productData.avg_days_to_sell} days</td></tr>
                            <tr><th>Return Rate:</th><td>${productData.return_rate || 0}%</td></tr>
                            <tr><th>Total Shipped:</th><td>${productData.total_shipped.toLocaleString()} units</td></tr>
                            <tr><th>Total Sold:</th><td>${productData.total_sold.toLocaleString()} units</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0">Strategic Recommendations</h6>
                    </div>
                    <div class="card-body">
                        ${getRecommendations(productData.velocity_category, productData.avg_sell_through)}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#velocityDetailContent').html(content);
}

function getRecommendations(category, sellThrough) {
    let recommendations = '';
    
    switch(category) {
        case 'Hot Seller':
            recommendations = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-fire mr-1"></i> Hot Seller Strategy</h6>
                    <ul class="mb-0">
                        <li>Increase production capacity by 30-50%</li>
                        <li>Prioritize this product in marketing campaigns</li>
                        <li>Consider expanding to new locations</li>
                        <li>Monitor for potential stockouts</li>
                        <li>Analyze demand patterns for seasonal planning</li>
                    </ul>
                </div>
            `;
            break;
        case 'Good Mover':
            recommendations = `
                <div class="alert alert-primary">
                    <h6><i class="fas fa-check-circle mr-1"></i> Good Mover Strategy</h6>
                    <ul class="mb-0">
                        <li>Maintain current production levels</li>
                        <li>Consider slight increase during peak seasons</li>
                        <li>Monitor for optimization opportunities</li>
                        <li>Use as benchmark for other products</li>
                        <li>Explore cross-selling opportunities</li>
                    </ul>
                </div>
            `;
            break;
        case 'Slow Mover':
            recommendations = `
                <div class="alert alert-warning">
                    <h6><i class="fas fa-clock mr-1"></i> Slow Mover Strategy</h6>
                    <ul class="mb-0">
                        <li>Reduce production by 20-30%</li>
                        <li>Investigate market fit and positioning</li>
                        <li>Consider promotional campaigns</li>
                        <li>Evaluate packaging or pricing changes</li>
                        <li>Review distribution channels</li>
                    </ul>
                </div>
            `;
            break;
        case 'Dead Stock':
            recommendations = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-skull mr-1"></i> Dead Stock Strategy</h6>
                    <ul class="mb-0">
                        <li>Consider discontinuing this product</li>
                        <li>Liquidate remaining inventory with discounts</li>
                        <li>Analyze failure factors for future products</li>
                        <li>Reallocate resources to better performers</li>
                        <li>Phase out over 6-12 weeks</li>
                    </ul>
                </div>
            `;
            break;
        default:
            recommendations = '<div class="alert alert-info">Insufficient data for recommendations. Continue monitoring for 2-4 weeks.</div>';
    }
    
    return recommendations;
}

function exportVelocityData() {
    try {
        showLoading('Preparing export...');
        
        // Create a temporary link and trigger download
        window.location.href = '/analytics/product-velocity/export';
        
        setTimeout(() => {
            hideLoading();
            showNotification('Export started successfully', 'success');
        }, 1000);
    } catch (error) {
        console.error('Error exporting data:', error);
        showNotification('Failed to export data: ' + error.message, 'error');
        hideLoading();
    }
}

// Utility functions
function generateMonthLabels(count) {
    const months = [];
    for (let i = count - 1; i >= 0; i--) {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        months.push(date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' }));
    }
    return months;
}

function showLoading(message = 'Loading...') {
    // Show loading overlay or spinner
    if (!$('.loading-overlay').length) {
        $('body').append(`
            <div class="loading-overlay">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="mt-2">${message}</div>
                </div>
            </div>
        `);
    }
}

function hideLoading() {
    $('.loading-overlay').remove();
}

function showNotification(message, type = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        // Fallback to browser alert
        alert(`${type.toUpperCase()}: ${message}`);
    }
}
</script>
@endsection

@push('js')
<script>
// Additional chart configuration if needed
Chart.defaults.font.family = "'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'";
</script>
@endpush
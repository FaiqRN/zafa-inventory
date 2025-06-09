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
                                    <h4 class="mb-0">+40%</h4>
                                    <small>Production Focus</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0">-35%</h4>
                                    <small>Slow Stock</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Velocity Category Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100 velocity-card" data-category="Hot Seller">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                🔥 Hot Sellers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $productCategories['Hot Seller']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">&gt;80% sell-through, &lt;7 days</div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $productCategories['Good Mover']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">60-80% sell-through, 7-14 days</div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $productCategories['Slow Mover']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">30-60% sell-through, 14-21 days</div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $productCategories['Dead Stock']->count() ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">&lt;30% sell-through, &gt;21 days</div>
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
                                    <th width="10%" class="text-center">Total Shipped</th>
                                    <th width="10%" class="text-center">Total Sold</th>
                                    <th width="10%" class="text-center">Velocity Score</th>
                                    <th width="8%" class="text-center">Trend</th>
                                    <th width="5%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productCategories as $category => $products)
                                    @foreach($products as $index => $product)
                                    <tr data-category="{{ $category }}" data-velocity="{{ $product['avg_sell_through'] }}">
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
                                            @if($product['avg_days_to_sell'] <= 7)
                                                <span class="badge badge-danger">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @elseif($product['avg_days_to_sell'] <= 14)
                                                <span class="badge badge-success">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @elseif($product['avg_days_to_sell'] <= 21)
                                                <span class="badge badge-warning">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ number_format($product['avg_days_to_sell'], 1) }}</span>
                                            @endif
                                            <small class="d-block text-muted">days</small>
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
                                                $trend = rand(1, 3);
                                            @endphp
                                            @if($trend === 1)
                                                <i class="fas fa-arrow-up text-success" title="Improving"></i>
                                            @elseif($trend === 2)
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
                                                        data-product="{{ json_encode($product) }}">
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
                        Velocity Trend Analysis
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="velocityTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-map mr-1"></i>
                        Geographic Demand
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="geographicChart" height="150"></canvas>
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
                            <ul class="list-unstyled">
                                @foreach($productCategories['Hot Seller'] ?? [] as $product)
                                <li class="mb-2">
                                    <i class="fas fa-fire text-danger mr-2"></i>
                                    <strong>{{ $product['barang']->nama_barang }}</strong>
                                    - Increase production by 25-40%
                                    <div class="text-muted small">Sell-through: {{ number_format($product['avg_sell_through'], 1) }}%</div>
                                </li>
                                @if($loop->index >= 2) @break @endif
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger">
                                <i class="fas fa-arrow-down mr-1"></i>
                                Reduce or Discontinue
                            </h6>
                            <ul class="list-unstyled">
                                @foreach($productCategories['Dead Stock'] ?? [] as $product)
                                <li class="mb-2">
                                    <i class="fas fa-skull text-secondary mr-2"></i>
                                    <strong>{{ $product['barang']->nama_barang }}</strong>
                                    - Consider discontinuing
                                    <div class="text-muted small">Sell-through: {{ number_format($product['avg_sell_through'], 1) }}%</div>
                                </li>
                                @if($loop->index >= 2) @break @endif
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
    transition: transform 0.2s ease;
}
.velocity-card:hover {
    transform: translateY(-3px);
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
}

.product-icon {
    width: 30px;
    text-align: center;
}

.badge-lg {
    font-size: 0.8em;
    padding: 0.4em 0.6em;
}

.progress-sm { height: 0.5rem; }
.table td { vertical-align: middle; }

.bg-gradient-warning {
    background: linear-gradient(45deg, #ffc107, #ff8f00);
}
</style>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#velocityTable').DataTable({
        "order": [[ 7, "desc" ]], // Sort by velocity score
        "pageLength": 25,
        "searching": false,
        "info": false,
        "lengthChange": false
    });

    // Category card click handlers
    $('.velocity-card').on('click', function() {
        const category = $(this).data('category');
        $('#categoryFilter').val(category);
        filterTable();
        
        // Highlight selected card
        $('.velocity-card').removeClass('border-primary');
        $(this).addClass('border-primary');
    });

    // Custom filters
    $('#categoryFilter, #velocityFilter').on('change', function() {
        filterTable();
    });

    $('#searchProduct').on('keyup', function() {
        filterTable();
    });

    $('#resetFilters').on('click', function() {
        $('#categoryFilter, #velocityFilter').val('');
        $('#searchProduct').val('');
        $('.velocity-card').removeClass('border-primary');
        filterTable();
    });

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
    }

    // Modal detail handler
    $('#velocityDetailModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const product = button.data('product');
        
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Product Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Product Name:</th><td>${product.barang.nama_barang}</td></tr>
                                <tr><th>Product Code:</th><td>${product.barang.barang_kode}</td></tr>
                                <tr><th>Category:</th><td><span class="badge badge-primary">${product.velocity_category}</span></td></tr>
                                <tr><th>Velocity Score:</th><td><strong>${product.velocity_score}</strong></td></tr>
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
                                <tr><th width="40%">Sell-Through:</th><td><strong>${product.avg_sell_through}%</strong></td></tr>
                                <tr><th>Days to Sell:</th><td>${product.avg_days_to_sell} days</td></tr>
                                <tr><th>Total Shipped:</th><td>${product.total_shipped.toLocaleString()} units</td></tr>
                                <tr><th>Total Sold:</th><td>${product.total_sold.toLocaleString()} units</td></tr>
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
                            ${getRecommendations(product.velocity_category, product.avg_sell_through)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#velocityDetailContent').html(content);
    });

    // Initialize Charts
    initializeCharts();
});

function getRecommendations(category, sellThrough) {
    let recommendations = '';
    
    switch(category) {
        case 'Hot Seller':
            recommendations = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-fire mr-1"></i> Hot Seller Strategy</h6>
                    <ul class="mb-0">
                        <li>Increase production capacity by 25-40%</li>
                        <li>Prioritize this product in marketing campaigns</li>
                        <li>Consider expanding to new locations</li>
                        <li>Monitor for potential stockouts</li>
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
                        <li>Liquidate remaining inventory</li>
                        <li>Analyze failure factors for future products</li>
                        <li>Reallocate resources to better performers</li>
                    </ul>
                </div>
            `;
            break;
        default:
            recommendations = '<div class="alert alert-info">Insufficient data for recommendations</div>';
    }
    
    return recommendations;
}

function initializeCharts() {
    // Velocity Trend Chart
    const trendCtx = document.getElementById('velocityTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Hot Sellers',
                data: [12, 15, 18, 16, 20, 22],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Good Movers',
                data: [8, 10, 12, 14, 15, 16],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Slow Movers',
                data: [5, 4, 6, 5, 4, 3],
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4
            }, {
                label: 'Dead Stock',
                data: [3, 2, 2, 1, 1, 1],
                borderColor: '#6c757d',
                backgroundColor: 'rgba(108, 117, 125, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Geographic Demand Chart
    const geoCtx = document.getElementById('geographicChart').getContext('2d');
    new Chart(geoCtx, {
        type: 'doughnut',
        data: {
            labels: ['Malang Kota', 'Malang Kabupaten', 'Batu', 'Lainnya'],
            datasets: [{
                data: [45, 30, 15, 10],
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function recommendIncrease(barangId) {
    alert(`Recommend increasing production for product: ${barangId}`);
}

function recommendDiscontinue(barangId) {
    if (confirm('Are you sure you want to recommend discontinuing this product?')) {
        alert(`Recommended to discontinue product: ${barangId}`);
    }
}

// Export functionality
$('#exportVelocity').on('click', function() {
    alert('Exporting velocity analysis to Excel...');
});

$('#optimizePortfolio').on('click', function() {
    alert('Optimizing product portfolio based on velocity analysis...');
});
</script>
@endpush
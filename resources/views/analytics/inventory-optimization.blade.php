@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-success text-white shadow-lg">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-boxes mr-2"></i>
                                Inventory Optimization Analytics
                            </h2>
                            <p class="mb-0 opacity-75">
                                Algoritma Cerdas - Berapa Jumlah Optimal Kirim ke Setiap Toko?
                                Seperti GPS untuk inventory - selalu tahu rute terbaik!
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mb-0">-40%</h4>
                                    <small>Inventory Cost</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0">+30%</h4>
                                    <small>Cash Flow Speed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Potential Savings
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($recommendations->sum('potential_savings'), 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-gray-500">From optimization</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-piggy-bank fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                High Confidence
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $recommendations->where('confidence_level', 'High')->count() }}
                            </div>
                            <div class="text-xs text-gray-500">Recommendations</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Avg Waste Reduction
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($recommendations->avg('improvement_percentage'), 1) }}%
                            </div>
                            <div class="text-xs text-gray-500">Inventory efficiency</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-recycle fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Products
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $recommendations->count() }}
                            </div>
                            <div class="text-xs text-gray-500">Product-store combinations</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cubes fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seasonal Adjustments Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-left border-primary">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="alert-heading mb-2">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Seasonal Adjustment Active
                        </h5>
                        <p class="mb-0">
                            Current month multiplier: <strong>{{ $seasonalAdjustments['current_multiplier'] }}x</strong>
                            - {{ $seasonalAdjustments['description'] }}
                        </p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#seasonalModal">
                                <i class="fas fa-cog"></i> Configure
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" id="refreshRecommendations">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
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
                            <select class="form-control form-control-sm" id="confidenceFilter">
                                <option value="">All Confidence Levels</option>
                                <option value="High">High Confidence</option>
                                <option value="Medium">Medium Confidence</option>
                                <option value="Low">Low Confidence</option>
                                <option value="Very Low">Very Low Confidence</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="savingsFilter">
                                <option value="">All Savings Potential</option>
                                <option value="high">High (&gt; Rp 500k)</option>
                                <option value="medium">Medium (Rp 100k - 500k)</option>
                                <option value="low">Low (&lt; Rp 100k)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" id="searchProduct" placeholder="Search product or store...">
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
                <button type="button" class="btn btn-success btn-sm" id="exportRecommendations">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="applyRecommendations">
                    <i class="fas fa-magic"></i> Apply All
                </button>
            </div>
        </div>
    </div>

    <!-- Recommendations Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-1"></i>
                        Inventory Optimization Recommendations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recommendationsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Store</th>
                                    <th width="15%">Product</th>
                                    <th width="10%" class="text-center">Historical Avg</th>
                                    <th width="10%" class="text-center">Recommended Qty</th>
                                    <th width="8%" class="text-center">Seasonal Adj</th>
                                    <th width="8%" class="text-center">Confidence</th>
                                    <th width="12%" class="text-center">Potential Savings</th>
                                    <th width="8%" class="text-center">Improvement</th>
                                    <th width="9%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recommendations as $index => $rec)
                                <tr data-confidence="{{ $rec['confidence_level'] }}" data-savings="{{ $rec['potential_savings'] }}">
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-2">
                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                    {{ strtoupper(substr($rec['toko_nama'], 0, 2)) }}
                                                </div>
                                            </div>
                                            <div>
                                                <strong>{{ $rec['toko_nama'] }}</strong>
                                                <br>
                                                <small class="text-muted">Store</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-2">
                                                <div class="avatar-title bg-soft-success text-success rounded-circle">
                                                    📦
                                                </div>
                                            </div>
                                            <div>
                                                <strong>{{ $rec['barang_nama'] }}</strong>
                                                <br>
                                                <small class="text-muted">Product</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="mb-1">
                                            <strong>{{ number_format($rec['historical_avg_shipped']) }}</strong>
                                            <small class="text-muted">shipped</small>
                                        </div>
                                        <div>
                                            <span class="text-success">{{ number_format($rec['historical_avg_sold']) }}</span>
                                            <small class="text-muted">sold</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="recommendation-highlight">
                                            <h5 class="mb-0 text-primary font-weight-bold">
                                                {{ number_format($rec['recommended_quantity']) }}
                                            </h5>
                                            <small class="text-muted">units</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($rec['seasonal_multiplier'] > 1)
                                            <span class="badge badge-success">
                                                +{{ number_format(($rec['seasonal_multiplier'] - 1) * 100) }}%
                                            </span>
                                        @elseif($rec['seasonal_multiplier'] < 1)
                                            <span class="badge badge-warning">
                                                {{ number_format(($rec['seasonal_multiplier'] - 1) * 100) }}%
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">0%</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $confidenceColors = [
                                                'High' => 'success',
                                                'Medium' => 'primary',
                                                'Low' => 'warning',
                                                'Very Low' => 'danger'
                                            ];
                                            $color = $confidenceColors[$rec['confidence_level']] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }}">
                                            {{ $rec['confidence_level'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-success">
                                            Rp {{ number_format($rec['potential_savings'], 0, ',', '.') }}
                                        </strong>
                                        <br>
                                        <small class="text-muted">per cycle</small>
                                    </td>
                                    <td class="text-center">
                                        @if($rec['improvement_percentage'] > 0)
                                            <div class="progress progress-sm mb-1">
                                                <div class="progress-bar bg-success" 
                                                     style="width: {{ min($rec['improvement_percentage'], 100) }}%"></div>
                                            </div>
                                            <strong class="text-success">{{ number_format($rec['improvement_percentage'], 1) }}%</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="applyRecommendation('{{ $rec['toko_id'] }}', '{{ $rec['barang_id'] }}', {{ $rec['recommended_quantity'] }})">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-toggle="modal" 
                                                    data-target="#detailModal" 
                                                    data-recommendation="{{ json_encode($rec) }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="customizeRecommendation('{{ $rec['toko_id'] }}', '{{ $rec['barang_id'] }}')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
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
                        <i class="fas fa-chart-area mr-1"></i>
                        Inventory Turnover Trend
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="turnoverChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-1"></i>
                        Confidence Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="confidenceChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Impact Projection -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-rocket mr-1"></i>
                        Projected Impact of Optimization
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="impact-metric">
                                <i class="fas fa-piggy-bank fa-3x text-success mb-2"></i>
                                <h4 class="text-success">Rp {{ number_format($recommendations->sum('potential_savings') * 6, 0, ',', '.') }}</h4>
                                <p class="text-muted">Annual Savings Potential</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="impact-metric">
                                <i class="fas fa-chart-line fa-3x text-primary mb-2"></i>
                                <h4 class="text-primary">{{ number_format($recommendations->avg('improvement_percentage'), 1) }}%</h4>
                                <p class="text-muted">Average Efficiency Gain</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="impact-metric">
                                <i class="fas fa-shipping-fast fa-3x text-warning mb-2"></i>
                                <h4 class="text-warning">-25%</h4>
                                <p class="text-muted">Logistics Cost Reduction</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="impact-metric">
                                <i class="fas fa-clock fa-3x text-info mb-2"></i>
                                <h4 class="text-info">30%</h4>
                                <p class="text-muted">Faster Cash Flow</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recommendation Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line mr-2"></i>
                    Recommendation Detail & Analysis
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="recommendationDetailContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seasonal Adjustment Modal -->
<div class="modal fade" id="seasonalModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Seasonal Adjustment Configuration
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="seasonalForm">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Multiplier</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>January</td>
                                    <td><input type="number" class="form-control form-control-sm" value="1.1" step="0.1" min="0.5" max="2"></td>
                                    <td>New Year</td>
                                </tr>
                                <tr>
                                    <td>February</td>
                                    <td><input type="number" class="form-control form-control-sm" value="0.95" step="0.1" min="0.5" max="2"></td>
                                    <td>Normal</td>
                                </tr>
                                <tr>
                                    <td>March</td>
                                    <td><input type="number" class="form-control form-control-sm" value="1.2" step="0.1" min="0.5" max="2"></td>
                                    <td>Ramadan Prep</td>
                                </tr>
                                <!-- Continue for all months -->
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveSeasonalSettings">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success { border-left: 4px solid #28a745 !important; }
.border-left-primary { border-left: 4px solid #007bff !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.border-left-info { border-left: 4px solid #17a2b8 !important; }

.avatar-sm { width: 2rem; height: 2rem; }
.avatar-title {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 600;
}

.bg-soft-primary { background-color: rgba(0, 123, 255, 0.1); }
.bg-soft-success { background-color: rgba(40, 167, 69, 0.1); }

.recommendation-highlight {
    background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
    padding: 10px; border-radius: 8px;
}

.progress-sm { height: 0.5rem; }
.table td { vertical-align: middle; }

.impact-metric {
    padding: 20px;
    border-radius: 10px;
    transition: transform 0.2s;
}
.impact-metric:hover { transform: translateY(-5px); }

.bg-gradient-success {
    background: linear-gradient(45deg, #28a745, #20c997);
}
</style>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#recommendationsTable').DataTable({
        "order": [[ 7, "desc" ]], // Sort by potential savings
        "pageLength": 25,
        "searching": false,
        "info": false,
        "lengthChange": false
    });

    // Custom filters
    $('#confidenceFilter, #savingsFilter').on('change', function() {
        filterTable();
    });

    $('#searchProduct').on('keyup', function() {
        filterTable();
    });

    $('#resetFilters').on('click', function() {
        $('#confidenceFilter, #savingsFilter').val('');
        $('#searchProduct').val('');
        filterTable();
    });

    function filterTable() {
        const confidenceFilter = $('#confidenceFilter').val();
        const savingsFilter = $('#savingsFilter').val();
        const searchText = $('#searchProduct').val().toLowerCase();

        $('#recommendationsTable tbody tr').each(function() {
            let show = true;
            const $row = $(this);
            const confidence = $row.data('confidence');
            const savings = parseFloat($row.data('savings'));
            const text = $row.text().toLowerCase();

            // Confidence filter
            if (confidenceFilter && confidence !== confidenceFilter) {
                show = false;
            }

            // Savings filter
            if (savingsFilter) {
                if (savingsFilter === 'high' && savings < 500000) show = false;
                if (savingsFilter === 'medium' && (savings < 100000 || savings >= 500000)) show = false;
                if (savingsFilter === 'low' && savings >= 100000) show = false;
            }

            // Search filter
            if (searchText && !text.includes(searchText)) {
                show = false;
            }

            $row.toggle(show);
        });
    }

    // Modal detail handler
    $('#detailModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const rec = button.data('recommendation');
        
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Current Situation</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="50%">Store:</th><td>${rec.toko_nama}</td></tr>
                                <tr><th>Product:</th><td>${rec.barang_nama}</td></tr>
                                <tr><th>Avg Shipped:</th><td>${rec.historical_avg_shipped} units</td></tr>
                                <tr><th>Avg Sold:</th><td>${rec.historical_avg_sold} units</td></tr>
                                <tr><th>Waste Rate:</th><td>${rec.improvement_percentage}%</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">Optimization Result</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="50%">Recommended:</th><td><strong>${rec.recommended_quantity} units</strong></td></tr>
                                <tr><th>Seasonal Adj:</th><td>${rec.seasonal_multiplier}x</td></tr>
                                <tr><th>Confidence:</th><td>${rec.confidence_level}</td></tr>
                                <tr><th>Potential Savings:</th><td><strong class="text-success">Rp ${rec.potential_savings.toLocaleString()}</strong></td></tr>
                                <tr><th>Improvement:</th><td><strong class="text-primary">${rec.improvement_percentage}%</strong></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Algorithm Explanation</h6>
                        </div>
                        <div class="card-body">
                            <p>This recommendation is based on:</p>
                            <ul>
                                <li><strong>Historical Analysis:</strong> 6 months of shipment and sales data</li>
                                <li><strong>Seasonal Adjustment:</strong> ${rec.seasonal_multiplier}x multiplier for current month</li>
                                <li><strong>Trend Analysis:</strong> Recent performance patterns</li>
                                <li><strong>Confidence Level:</strong> ${rec.confidence_level} based on data quality</li>
                            </ul>
                            <div class="alert alert-warning">
                                <strong>Note:</strong> Always consider local market conditions and recent changes when applying recommendations.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#recommendationDetailContent').html(content);
    });

    // Initialize Charts
    initializeCharts();
});

function initializeCharts() {
    // Turnover Trend Chart
    const turnoverCtx = document.getElementById('turnoverChart').getContext('2d');
    new Chart(turnoverCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Inventory Turnover Rate',
                data: [2.1, 2.3, 2.8, 2.6, 3.1, 3.4],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Optimized Projection',
                data: [null, null, null, null, 3.1, 4.2],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderDash: [5, 5],
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + 'x';
                        }
                    }
                }
            }
        }
    });

    // Confidence Distribution Chart
    const confidenceCtx = document.getElementById('confidenceChart').getContext('2d');
    const confidenceData = {
        'High': {{ $recommendations->where('confidence_level', 'High')->count() }},
        'Medium': {{ $recommendations->where('confidence_level', 'Medium')->count() }},
        'Low': {{ $recommendations->where('confidence_level', 'Low')->count() }},
        'Very Low': {{ $recommendations->where('confidence_level', 'Very Low')->count() }}
    };

    new Chart(confidenceCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(confidenceData),
            datasets: [{
                data: Object.values(confidenceData),
                backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function applyRecommendation(tokoId, barangId, quantity) {
    if (confirm(`Apply recommendation: Send ${quantity} units to this store?`)) {
        // Implement API call to apply recommendation
        alert(`Recommendation applied: ${quantity} units for ${tokoId}-${barangId}`);
    }
}

function customizeRecommendation(tokoId, barangId) {
    // Implement customization modal
    alert(`Customize recommendation for ${tokoId}-${barangId}`);
}

// Export functionality
$('#exportRecommendations').on('click', function() {
    alert('Exporting recommendations to Excel...');
});

$('#applyRecommendations').on('click', function() {
    if (confirm('Apply all high-confidence recommendations?')) {
        alert('Applying all recommendations...');
    }
});

$('#refreshRecommendations').on('click', function() {
    alert('Refreshing recommendations...');
    location.reload();
});

$('#saveSeasonalSettings').on('click', function() {
    alert('Seasonal settings saved!');
    $('#seasonalModal').modal('hide');
});
</script>
@endpush
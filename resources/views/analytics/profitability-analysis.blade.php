@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-info text-white shadow-lg">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-calculator mr-2"></i>
                                True Profitability Analysis
                            </h2>
                            <p class="mb-0 opacity-75">
                                Kalkulator Keuntungan Sejati - Partner Mana yang Benar-benar Menguntungkan?
                                Bukan cuma lihat omzet, tapi semua biaya tersembunyi juga dihitung!
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mb-0">+60%</h4>
                                    <small>Network Profit</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0">Hidden</h4>
                                    <small>Cost Detection</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profitability Overview Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Network Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($profitability->sum('revenue'), 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-gray-500">Last 6 months</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
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
                                True Net Profit
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($profitability->sum('net_profit'), 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-gray-500">After all costs</div>
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
                                Average ROI
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($profitability->avg('roi'), 1) }}%
                            </div>
                            <div class="text-xs text-gray-500">Network average</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Hidden Costs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Rp {{ number_format($profitability->sum('opportunity_cost') + $profitability->sum('time_value_cost'), 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-gray-500">Often overlooked</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye-slash fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-1"></i>
                        True Cost Breakdown Analysis
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="costBreakdownChart" height="100"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="cost-legend">
                                <h6 class="mb-3">Cost Components</h6>
                                <div class="legend-item mb-2">
                                    <span class="legend-color bg-primary"></span>
                                    <strong>COGS (Cost of Goods Sold)</strong>
                                    <div class="text-muted small">Raw materials & production</div>
                                </div>
                                <div class="legend-item mb-2">
                                    <span class="legend-color bg-warning"></span>
                                    <strong>Logistics Cost</strong>
                                    <div class="text-muted small">Transport & delivery</div>
                                </div>
                                <div class="legend-item mb-2">
                                    <span class="legend-color bg-info"></span>
                                    <strong>Opportunity Cost</strong>
                                    <div class="text-muted small">Capital tied up in consignment</div>
                                </div>
                                <div class="legend-item mb-2">
                                    <span class="legend-color bg-danger"></span>
                                    <strong>Time Value Cost</strong>
                                    <div class="text-muted small">Slow payment impact</div>
                                </div>
                            </div>
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
                            <select class="form-control form-control-sm" id="roiFilter">
                                <option value="">All ROI Levels</option>
                                <option value="high">High (&gt; 25%)</option>
                                <option value="medium">Medium (15-25%)</option>
                                <option value="low">Low (5-15%)</option>
                                <option value="negative">Negative (&lt; 5%)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="profitFilter">
                                <option value="">All Profit Levels</option>
                                <option value="high">High (&gt; Rp 2M)</option>
                                <option value="medium">Medium (Rp 500k - 2M)</option>
                                <option value="low">Low (&lt; Rp 500k)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" id="searchPartner" placeholder="Search partner name...">
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
                <button type="button" class="btn btn-success btn-sm" id="exportProfitability">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="identifyLossMakers">
                    <i class="fas fa-exclamation-triangle"></i> Loss Makers
                </button>
            </div>
        </div>
    </div>

    <!-- Profitability Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-1"></i>
                        Partner Profitability Ranking
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="profitabilityTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Partner</th>
                                    <th width="10%" class="text-center">Revenue</th>
                                    <th width="10%" class="text-center">Total Costs</th>
                                    <th width="10%" class="text-center">Net Profit</th>
                                    <th width="8%" class="text-center">ROI</th>
                                    <th width="8%" class="text-center">Margin</th>
                                    <th width="12%" class="text-center">Cost Breakdown</th>
                                    <th width="12%" class="text-center">Hidden Costs</th>
                                    <th width="10%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($profitability as $index => $partner)
                                <tr data-roi="{{ $partner['roi'] }}" data-profit="{{ $partner['net_profit'] }}">
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-2">
                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                    {{ strtoupper(substr($partner['toko']->nama_toko, 0, 2)) }}
                                                </div>
                                            </div>
                                            <div>
                                                <strong>{{ $partner['toko']->nama_toko }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $partner['toko']->toko_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-success">Rp {{ number_format($partner['revenue'], 0, ',', '.') }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-danger">Rp {{ number_format($partner['total_costs'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($partner['net_profit'] >= 0)
                                            <strong class="text-success">Rp {{ number_format($partner['net_profit'], 0, ',', '.') }}</strong>
                                        @else
                                            <strong class="text-danger">-Rp {{ number_format(abs($partner['net_profit']), 0, ',', '.') }}</strong>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($partner['roi'] >= 25)
                                            <span class="badge badge-success badge-lg">{{ number_format($partner['roi'], 1) }}%</span>
                                        @elseif($partner['roi'] >= 15)
                                            <span class="badge badge-primary badge-lg">{{ number_format($partner['roi'], 1) }}%</span>
                                        @elseif($partner['roi'] >= 5)
                                            <span class="badge badge-warning badge-lg">{{ number_format($partner['roi'], 1) }}%</span>
                                        @else
                                            <span class="badge badge-danger badge-lg">{{ number_format($partner['roi'], 1) }}%</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($partner['profit_margin'] >= 20)
                                            <span class="text-success font-weight-bold">{{ number_format($partner['profit_margin'], 1) }}%</span>
                                        @elseif($partner['profit_margin'] >= 10)
                                            <span class="text-primary font-weight-bold">{{ number_format($partner['profit_margin'], 1) }}%</span>
                                        @else
                                            <span class="text-danger font-weight-bold">{{ number_format($partner['profit_margin'], 1) }}%</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="cost-breakdown-mini">
                                            <div class="breakdown-bar">
                                                <div class="breakdown-segment bg-primary" 
                                                     style="width: {{ $partner['cost_breakdown']['cogs_percentage'] }}%" 
                                                     title="COGS: {{ $partner['cost_breakdown']['cogs_percentage'] }}%"></div>
                                                <div class="breakdown-segment bg-warning" 
                                                     style="width: {{ $partner['cost_breakdown']['logistics_percentage'] }}%" 
                                                     title="Logistics: {{ $partner['cost_breakdown']['logistics_percentage'] }}%"></div>
                                                <div class="breakdown-segment bg-info" 
                                                     style="width: {{ $partner['cost_breakdown']['opportunity_percentage'] }}%" 
                                                     title="Opportunity: {{ $partner['cost_breakdown']['opportunity_percentage'] }}%"></div>
                                                <div class="breakdown-segment bg-danger" 
                                                     style="width: {{ $partner['cost_breakdown']['time_value_percentage'] }}%" 
                                                     title="Time Value: {{ $partner['cost_breakdown']['time_value_percentage'] }}%"></div>
                                            </div>
                                            <small class="text-muted">Hover to see details</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="hidden-costs">
                                            <div class="mb-1">
                                                <small class="text-info">Opportunity:</small>
                                                <strong>Rp {{ number_format($partner['opportunity_cost'], 0, ',', '.') }}</strong>
                                            </div>
                                            <div>
                                                <small class="text-danger">Time Value:</small>
                                                <strong>Rp {{ number_format($partner['time_value_cost'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-toggle="modal" 
                                                    data-target="#profitDetailModal" 
                                                    data-partner="{{ json_encode($partner) }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($partner['roi'] < 5)
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="flagLossMaker('{{ $partner['toko']->toko_id }}')">
                                                <i class="fas fa-flag"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="optimizePartner('{{ $partner['toko']->toko_id }}')">
                                                <i class="fas fa-cog"></i>
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

    <!-- ROI Distribution Chart -->
    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar mr-1"></i>
                        ROI Distribution & Benchmark
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="roiDistributionChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy mr-1"></i>
                        Top & Bottom Performers
                    </h6>
                </div>
                <div class="card-body">
                    <div class="top-performers mb-3">
                        <h6 class="text-success">🏆 Top 3 ROI</h6>
                        @foreach($profitability->take(3) as $partner)
                        <div class="performer-item mb-2">
                            <div class="d-flex justify-content-between">
                                <span>{{ $partner['toko']->nama_toko }}</span>
                                <strong class="text-success">{{ number_format($partner['roi'], 1) }}%</strong>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="bottom-performers">
                        <h6 class="text-danger">⚠️ Bottom 3 ROI</h6>
                        @foreach($profitability->reverse()->take(3) as $partner)
                        <div class="performer-item mb-2">
                            <div class="d-flex justify-content-between">
                                <span>{{ $partner['toko']->nama_toko }}</span>
                                <strong class="text-danger">{{ number_format($partner['roi'], 1) }}%</strong>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optimization Recommendations -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Profitability Optimization Recommendations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-success">
                                <i class="fas fa-arrow-up mr-1"></i>
                                High Performers (Focus & Grow)
                            </h6>
                            <ul class="list-unstyled">
                                @foreach($profitability->where('roi', '>', 25)->take(3) as $partner)
                                <li class="mb-2">
                                    <i class="fas fa-star text-success mr-2"></i>
                                    <strong>{{ $partner['toko']->nama_toko }}</strong>
                                    <div class="text-muted small">ROI: {{ number_format($partner['roi'], 1) }}% - Increase allocation</div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-warning">
                                <i class="fas fa-tools mr-1"></i>
                                Medium Performers (Optimize)
                            </h6>
                            <ul class="list-unstyled">
                                @foreach($profitability->whereBetween('roi', [15, 25])->take(3) as $partner)
                                <li class="mb-2">
                                    <i class="fas fa-cog text-warning mr-2"></i>
                                    <strong>{{ $partner['toko']->nama_toko }}</strong>
                                    <div class="text-muted small">ROI: {{ number_format($partner['roi'], 1) }}% - Reduce costs</div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-danger">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Low Performers (Review/Exit)
                            </h6>
                            <ul class="list-unstyled">
                                @foreach($profitability->where('roi', '<', 5)->take(3) as $partner)
                                <li class="mb-2">
                                    <i class="fas fa-times-circle text-danger mr-2"></i>
                                    <strong>{{ $partner['toko']->nama_toko }}</strong>
                                    <div class="text-muted small">ROI: {{ number_format($partner['roi'], 1) }}% - Consider termination</div>
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

<!-- Partner Profitability Detail Modal -->
<div class="modal fade" id="profitDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calculator mr-2"></i>
                    True Profitability Analysis Detail
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="profitDetailContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success { border-left: 4px solid #28a745 !important; }
.border-left-primary { border-left: 4px solid #007bff !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.border-left-danger { border-left: 4px solid #dc3545 !important; }

.avatar-sm { width: 2rem; height: 2rem; }
.avatar-title {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 600;
}

.bg-soft-primary { background-color: rgba(0, 123, 255, 0.1); }

.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.cost-breakdown-mini .breakdown-bar {
    height: 8px;
    width: 100%;
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    background-color: #f8f9fa;
}

.breakdown-segment {
    height: 100%;
    transition: opacity 0.2s;
}

.breakdown-segment:hover {
    opacity: 0.8;
}

.cost-legend .legend-item {
    display: flex;
    align-items: center;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 2px;
    margin-right: 8px;
    flex-shrink: 0;
}

.hidden-costs {
    font-size: 0.8em;
}

.performer-item {
    padding: 8px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.table td { vertical-align: middle; }

.bg-gradient-info {
    background: linear-gradient(45deg, #17a2b8, #138496);
}
</style>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#profitabilityTable').DataTable({
        "order": [[ 5, "desc" ]], // Sort by ROI
        "pageLength": 25,
        "searching": false,
        "info": false,
        "lengthChange": false
    });

    // Custom filters
    $('#roiFilter, #profitFilter').on('change', function() {
        filterTable();
    });

    $('#searchPartner').on('keyup', function() {
        filterTable();
    });

    $('#resetFilters').on('click', function() {
        $('#roiFilter, #profitFilter').val('');
        $('#searchPartner').val('');
        filterTable();
    });

    function filterTable() {
        const roiFilter = $('#roiFilter').val();
        const profitFilter = $('#profitFilter').val();
        const searchText = $('#searchPartner').val().toLowerCase();

        $('#profitabilityTable tbody tr').each(function() {
            let show = true;
            const $row = $(this);
            const roi = parseFloat($row.data('roi'));
            const profit = parseFloat($row.data('profit'));
            const text = $row.text().toLowerCase();

            // ROI filter
            if (roiFilter) {
                if (roiFilter === 'high' && roi <= 25) show = false;
                if (roiFilter === 'medium' && (roi < 15 || roi > 25)) show = false;
                if (roiFilter === 'low' && (roi < 5 || roi >= 15)) show = false;
                if (roiFilter === 'negative' && roi >= 5) show = false;
            }

            // Profit filter
            if (profitFilter) {
                if (profitFilter === 'high' && profit < 2000000) show = false;
                if (profitFilter === 'medium' && (profit < 500000 || profit >= 2000000)) show = false;
                if (profitFilter === 'low' && profit >= 500000) show = false;
            }

            // Search filter
            if (searchText && !text.includes(searchText)) {
                show = false;
            }

            $row.toggle(show);
        });
    }

    // Modal detail handler
    $('#profitDetailModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const partner = button.data('partner');
        
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Revenue & Costs Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Partner:</th><td>${partner.toko.nama_toko}</td></tr>
                                <tr><th>Total Revenue:</th><td class="text-success"><strong>Rp ${partner.revenue.toLocaleString()}</strong></td></tr>
                                <tr><th>COGS:</th><td>Rp ${partner.cogs.toLocaleString()}</td></tr>
                                <tr><th>Logistics Cost:</th><td>Rp ${partner.logistics_cost.toLocaleString()}</td></tr>
                                <tr><th>Opportunity Cost:</th><td>Rp ${partner.opportunity_cost.toLocaleString()}</td></tr>
                                <tr><th>Time Value Cost:</th><td>Rp ${partner.time_value_cost.toLocaleString()}</td></tr>
                                <tr class="border-top"><th><strong>Total Costs:</strong></th><td class="text-danger"><strong>Rp ${partner.total_costs.toLocaleString()}</strong></td></tr>
                                <tr><th><strong>Net Profit:</strong></th><td class="${partner.net_profit >= 0 ? 'text-success' : 'text-danger'}"><strong>Rp ${partner.net_profit.toLocaleString()}</strong></td></tr>
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
                                <tr><th width="40%">ROI:</th><td><strong class="${partner.roi >= 15 ? 'text-success' : 'text-danger'}">${partner.roi.toFixed(1)}%</strong></td></tr>
                                <tr><th>Profit Margin:</th><td><strong>${partner.profit_margin.toFixed(1)}%</strong></td></tr>
                                <tr><th>COGS %:</th><td>${partner.cost_breakdown.cogs_percentage}%</td></tr>
                                <tr><th>Logistics %:</th><td>${partner.cost_breakdown.logistics_percentage}%</td></tr>
                                <tr><th>Opportunity %:</th><td>${partner.cost_breakdown.opportunity_percentage}%</td></tr>
                                <tr><th>Time Value %:</th><td>${partner.cost_breakdown.time_value_percentage}%</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-white">
                            <h6 class="mb-0">Optimization Recommendations</h6>
                        </div>
                        <div class="card-body">
                            ${getProfitabilityRecommendations(partner.roi, partner.net_profit, partner.cost_breakdown)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#profitDetailContent').html(content);
    });

    // Initialize Charts
    initializeCharts();
});

function getProfitabilityRecommendations(roi, netProfit, costBreakdown) {
    let recommendations = '';
    
    if (roi >= 25) {
        recommendations = `
            <div class="alert alert-success">
                <h6><i class="fas fa-star mr-1"></i> High Performer Strategy</h6>
                <ul class="mb-0">
                    <li>Increase allocation to this partner by 20-30%</li>
                    <li>Use as model for other partnerships</li>
                    <li>Consider expanding product range</li>
                    <li>Maintain current cost structure</li>
                </ul>
            </div>
        `;
    } else if (roi >= 15) {
        recommendations = `
            <div class="alert alert-primary">
                <h6><i class="fas fa-cog mr-1"></i> Good Performer - Optimize Further</h6>
                <ul class="mb-0">
                    <li>Look for logistics cost optimization opportunities</li>
                    <li>Negotiate better payment terms to reduce time value cost</li>
                    <li>Consider volume discounts for better margins</li>
                    <li>Monitor performance trends closely</li>
                </ul>
            </div>
        `;
    } else if (roi >= 5) {
        recommendations = `
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle mr-1"></i> Marginal Performer - Needs Improvement</h6>
                <ul class="mb-0">
                    <li>Reduce logistics costs by optimizing delivery routes</li>
                    <li>Implement faster payment cycles</li>
                    <li>Consider reducing inventory allocation</li>
                    <li>Evaluate partner's sales capabilities</li>
                </ul>
            </div>
        `;
    } else {
        recommendations = `
            <div class="alert alert-danger">
                <h6><i class="fas fa-times-circle mr-1"></i> Loss Maker - Immediate Action Required</h6>
                <ul class="mb-0">
                    <li><strong>Consider terminating this partnership</strong></li>
                    <li>If continuing, drastically reduce allocation</li>
                    <li>Implement immediate cost reduction measures</li>
                    <li>Set strict performance improvement targets</li>
                </ul>
            </div>
        `;
    }
    
    return recommendations;
}

function initializeCharts() {
    // Cost Breakdown Chart
    const costCtx = document.getElementById('costBreakdownChart').getContext('2d');
    const totalCOGS = {{ $profitability->sum('cogs') }};
    const totalLogistics = {{ $profitability->sum('logistics_cost') }};
    const totalOpportunity = {{ $profitability->sum('opportunity_cost') }};
    const totalTimeValue = {{ $profitability->sum('time_value_cost') }};
    
    new Chart(costCtx, {
        type: 'doughnut',
        data: {
            labels: ['COGS', 'Logistics', 'Opportunity Cost', 'Time Value Cost'],
            datasets: [{
                data: [totalCOGS, totalLogistics, totalOpportunity, totalTimeValue],
                backgroundColor: ['#007bff', '#ffc107', '#17a2b8', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // ROI Distribution Chart
    const roiCtx = document.getElementById('roiDistributionChart').getContext('2d');
    const roiData = @json($profitability->pluck('roi'));
    
    new Chart(roiCtx, {
        type: 'bar',
        data: {
            labels: ['0-5%', '5-15%', '15-25%', '25%+'],
            datasets: [{
                label: 'Number of Partners',
                data: [
                    roiData.filter(roi => roi < 5).length,
                    roiData.filter(roi => roi >= 5 && roi < 15).length,
                    roiData.filter(roi => roi >= 15 && roi < 25).length,
                    roiData.filter(roi => roi >= 25).length
                ],
                backgroundColor: ['#dc3545', '#ffc107', '#007bff', '#28a745']
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
}

function flagLossMaker(tokoId) {
    if (confirm('Flag this partner as a loss maker? This will add them to the review list.')) {
        alert(`Partner ${tokoId} flagged for review`);
    }
}

function optimizePartner(tokoId) {
    alert(`Optimize partner ${tokoId} - showing optimization suggestions...`);
}

// Export functionality
$('#exportProfitability').on('click', function() {
    alert('Exporting profitability analysis to Excel...');
});

$('#identifyLossMakers').on('click', function() {
    const lossMakers = {{ $profitability->where('roi', '<', 5)->count() }};
    alert(`Found ${lossMakers} loss-making partners. Review recommended.`);
});
</script>
@endpush
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
                                    <h4 class="mb-0">-{{ number_format($summaryStats['avg_waste_reduction'] ?? 0, 1) }}%</h4>
                                    <small>Inventory Waste</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0">+{{ round($turnoverStats['inventory_efficiency'] ?? 0, 0) }}%</h4>
                                    <small>Efficiency Score</small>
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
                                Rp {{ number_format($summaryStats['total_potential_savings'] ?? 0, 0, ',', '.') }}
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
                                {{ $summaryStats['high_confidence_count'] ?? 0 }}
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
                                {{ number_format($summaryStats['avg_waste_reduction'] ?? 0, 1) }}%
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
                                {{ $summaryStats['total_products'] ?? 0 }}
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
                            Current month multiplier: <strong>{{ $seasonalAdjustments['current_multiplier'] ?? 1.0 }}x</strong>
                            - {{ $seasonalAdjustments['current_description'] ?? 'Standard period' }}
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
                            <select class="form-control form-control-sm" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="applied">Applied</option>
                                <option value="customized">Customized</option>
                                <option value="rejected">Rejected</option>
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
                <button type="button" class="btn btn-primary btn-sm" id="applyAllRecommendations">
                    <i class="fas fa-magic"></i> Apply Selected
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
                        <span class="badge badge-info ml-2" id="totalRecommendations">{{ $recommendations->count() }}</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recommendationsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="selectAll" class="form-control-sm">
                                    </th>
                                    <th width="5%">#</th>
                                    <th width="15%">Store</th>
                                    <th width="15%">Product</th>
                                    <th width="10%" class="text-center">Historical Avg</th>
                                    <th width="10%" class="text-center">Recommended Qty</th>
                                    <th width="8%" class="text-center">Seasonal Adj</th>
                                    <th width="8%" class="text-center">Confidence</th>
                                    <th width="12%" class="text-center">Potential Savings</th>
                                    <th width="8%" class="text-center">Improvement</th>
                                    <th width="6%" class="text-center">Status</th>
                                    <th width="10%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recommendations as $index => $rec)
                                <tr data-id="{{ $rec->id ?? 'rec_' . $index }}" 
                                    data-confidence="{{ $rec->confidence_level ?? 'Medium' }}" 
                                    data-status="{{ $rec->status ?? 'pending' }}" 
                                    data-savings="{{ $rec->potential_savings ?? 0 }}">
                                    <td class="text-center">
                                        <input type="checkbox" class="recommendation-checkbox" value="{{ $rec->id ?? 'rec_' . $index }}">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-2">
                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                    {{ strtoupper(substr($rec->toko_nama ?? ($rec->toko->nama_toko ?? 'N/A'), 0, 2)) }}
                                                </div>
                                            </div>
                                            <div>
                                                <strong>{{ $rec->toko_nama ?? ($rec->toko->nama_toko ?? 'N/A') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $rec->toko->alamat ?? 'No address' }}</small>
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
                                                <strong>{{ $rec->barang_nama ?? ($rec->barang->nama_barang ?? 'N/A') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $rec->barang_kode ?? ($rec->barang->barang_kode ?? 'No code') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="mb-1">
                                            <strong>{{ number_format($rec->historical_avg_shipped ?? 0) }}</strong>
                                            <small class="text-muted">shipped</small>
                                        </div>
                                        <div>
                                            <span class="text-success">{{ number_format($rec->historical_avg_sold ?? 0) }}</span>
                                            <small class="text-muted">sold</small>
                                        </div>
                                        @if(($rec->historical_avg_shipped ?? 0) > 0)
                                        <div>
                                            <small class="text-danger">{{ round(((($rec->historical_avg_shipped ?? 0) - ($rec->historical_avg_sold ?? 0)) / ($rec->historical_avg_shipped ?? 1)) * 100, 1) }}% waste</small>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="recommendation-highlight">
                                            <h5 class="mb-0 text-primary font-weight-bold">
                                                {{ number_format($rec->recommended_quantity ?? 0) }}
                                            </h5>
                                            <small class="text-muted">units</small>
                                            @php
                                                $historicalShipped = $rec->historical_avg_shipped ?? 0;
                                                $recommendedQty = $rec->recommended_quantity ?? 0;
                                                $change = $historicalShipped - $recommendedQty;
                                                $changePercent = $historicalShipped > 0 ? ($change / $historicalShipped) * 100 : 0;
                                            @endphp
                                            @if(abs($changePercent) > 5)
                                            <div>
                                                <small class="badge badge-{{ $change > 0 ? 'success' : 'warning' }}">
                                                    {{ $change > 0 ? '-' : '+' }}{{ abs(round($changePercent, 1)) }}%
                                                </small>
                                            </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $seasonalMultiplier = $rec->seasonal_multiplier ?? 1.0;
                                        @endphp
                                        @if($seasonalMultiplier > 1)
                                            <span class="badge badge-success" title="Seasonal boost">
                                                +{{ number_format(($seasonalMultiplier - 1) * 100, 1) }}%
                                            </span>
                                        @elseif($seasonalMultiplier < 1)
                                            <span class="badge badge-warning" title="Seasonal reduction">
                                                {{ number_format(($seasonalMultiplier - 1) * 100, 1) }}%
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">0%</span>
                                        @endif
                                        @php
                                            $trendMultiplier = $rec->trend_multiplier ?? 1.0;
                                        @endphp
                                        @if($trendMultiplier != 1)
                                        <div class="mt-1">
                                            <small class="text-muted" title="Trend adjustment">
                                                Trend: {{ round(($trendMultiplier - 1) * 100, 1) }}%
                                            </small>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $confidenceLevel = $rec->confidence_level ?? 'Medium';
                                            $confidenceColors = [
                                                'High' => 'success',
                                                'Medium' => 'primary', 
                                                'Low' => 'warning',
                                                'Very Low' => 'danger'
                                            ];
                                            $confidenceColor = $confidenceColors[$confidenceLevel] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $confidenceColor }}">
                                            {{ $confidenceLevel }}
                                        </span>
                                        @if($confidenceLevel === 'High')
                                        <div class="mt-1">
                                            <i class="fas fa-check-circle text-success" title="High reliability"></i>
                                        </div>
                                        @elseif(in_array($confidenceLevel, ['Low', 'Very Low']))
                                        <div class="mt-1">
                                            <i class="fas fa-exclamation-triangle text-warning" title="Needs more data"></i>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-success">
                                            Rp {{ number_format($rec->potential_savings ?? 0, 0, ',', '.') }}
                                        </strong>
                                        <br>
                                        <small class="text-muted">per cycle</small>
                                        @php
                                            $potentialSavings = $rec->potential_savings ?? 0;
                                        @endphp
                                        @if($potentialSavings > 1000000)
                                        <div class="mt-1">
                                            <span class="badge badge-danger">HIGH PRIORITY</span>
                                        </div>
                                        @elseif($potentialSavings > 500000)
                                        <div class="mt-1">
                                            <span class="badge badge-warning">MEDIUM</span>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $improvementPercentage = $rec->improvement_percentage ?? 0;
                                        @endphp
                                        @if($improvementPercentage > 0)
                                            <div class="progress progress-sm mb-1">
                                                <div class="progress-bar bg-{{ $improvementPercentage > 30 ? 'danger' : ($improvementPercentage > 15 ? 'warning' : 'success') }}" 
                                                     style="width: {{ min($improvementPercentage, 100) }}%"></div>
                                            </div>
                                            <strong class="text-{{ $improvementPercentage > 30 ? 'danger' : 'success' }}">
                                                {{ number_format($improvementPercentage, 1) }}%
                                            </strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $status = $rec->status ?? 'pending';
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'applied' => 'success',
                                                'customized' => 'info',
                                                'rejected' => 'danger'
                                            ];
                                            $statusColor = $statusColors[$status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $statusColor }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                        @if($rec->applied_at ?? false)
                                        <div class="mt-1">
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($rec->applied_at)->format('d/m/Y') }}</small>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            @if(($rec->status ?? 'pending') === 'pending')
                                            <button type="button" class="btn btn-outline-success apply-recommendation" 
                                                    data-id="{{ $rec->id ?? 'rec_' . $index }}"
                                                    data-toggle="tooltip" title="Apply Recommendation">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info customize-recommendation" 
                                                    data-id="{{ $rec->id ?? 'rec_' . $index }}"
                                                    data-recommended="{{ $rec->recommended_quantity ?? 0 }}"
                                                    data-toggle="tooltip" title="Customize Quantity">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-primary view-details" 
                                                    data-id="{{ $rec->id ?? 'rec_' . $index }}"
                                                    data-rec-data="{{ htmlspecialchars(json_encode($rec), ENT_QUOTES, 'UTF-8') }}"
                                                    data-toggle="tooltip" title="View Details">
                                                <i class="fas fa-eye"></i>
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
                                <h4 class="text-success">Rp {{ number_format(($summaryStats['total_potential_savings'] ?? 0) * 6, 0, ',', '.') }}</h4>
                                <p class="text-muted">Annual Savings Potential</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="impact-metric">
                                <i class="fas fa-chart-line fa-3x text-primary mb-2"></i>
                                <h4 class="text-primary">{{ number_format($summaryStats['avg_waste_reduction'] ?? 0, 1) }}%</h4>
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
                                <h4 class="text-info">{{ round($turnoverStats['current_turnover_rate'] ?? 0, 1) }}x</h4>
                                <p class="text-muted">Current Turnover Rate</p>
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
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customize Recommendation Modal -->
<div class="modal fade" id="customizeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit mr-2"></i>
                    Customize Recommendation
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="customizeForm">
                    <input type="hidden" id="customizeRecommendationId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>System Recommendation</label>
                                <input type="number" class="form-control" id="systemRecommendation" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Custom Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="customQuantity" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason for Customization</label>
                        <textarea class="form-control" id="customReason" rows="3" placeholder="Enter reason for custom adjustment..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> Custom quantities will override system recommendations. Ensure the adjustment aligns with business requirements.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCustomization">
                    <i class="fas fa-save"></i> Apply Custom Quantity
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Seasonal Adjustment Modal -->
<div class="modal fade" id="seasonalModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
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
                <div class="alert alert-warning">
                    <strong>Important:</strong> Seasonal adjustments affect all inventory recommendations. Changes will trigger recommendation recalculation.
                </div>
                
                <form id="seasonalForm">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th width="15%">Month</th>
                                    <th width="15%">Multiplier</th>
                                    <th width="50%">Description</th>
                                    <th width="15%">Active</th>
                                    <th width="5%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                    $currentMonth = now()->month;
                                    $allAdjustments = $seasonalAdjustments['all_adjustments'] ?? collect();
                                @endphp
                                @foreach($months as $monthNum => $monthName)
                                @php
                                    $adjustment = $allAdjustments->get($monthNum);
                                @endphp
                                <tr class="{{ $monthNum == $currentMonth ? 'table-info' : '' }}">
                                    <td>
                                        <strong>{{ $monthName }}</strong>
                                        @if($monthNum == $currentMonth)
                                            <span class="badge badge-primary ml-1">Current</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" 
                                                   class="form-control seasonal-multiplier" 
                                                   data-month="{{ $monthNum }}"
                                                   value="{{ $adjustment ? $adjustment->multiplier : 1.00 }}" 
                                                   step="0.05" 
                                                   min="0.5" 
                                                   max="2.0">
                                            <div class="input-group-append">
                                                <span class="input-group-text">x</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               class="form-control form-control-sm seasonal-description" 
                                               data-month="{{ $monthNum }}"
                                               value="{{ $adjustment ? $adjustment->description : 'Standard period' }}" 
                                               placeholder="Describe seasonal pattern...">
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" 
                                                   class="custom-control-input seasonal-active" 
                                                   data-month="{{ $monthNum }}"
                                                   id="active{{ $monthNum }}" 
                                                   {{ ($adjustment && $adjustment->is_active) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="active{{ $monthNum }}"></label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($adjustment)
                                            @if($adjustment->multiplier > 1.1)
                                                <i class="fas fa-arrow-up text-success" title="Increase demand"></i>
                                            @elseif($adjustment->multiplier < 0.9)
                                                <i class="fas fa-arrow-down text-warning" title="Decrease demand"></i>
                                            @else
                                                <i class="fas fa-minus text-secondary" title="Normal demand"></i>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Quick Presets:</h6>
                            <div class="btn-group-vertical w-100" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary preset-btn" data-preset="ramadan">
                                    Ramadan Period (Mar-Apr boost)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary preset-btn" data-preset="holiday">
                                    Holiday Season (Dec-Jan boost)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary preset-btn" data-preset="normal">
                                    Reset to Normal (1.0x all months)
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Preview Impact:</h6>
                            <div id="seasonalPreview" class="border p-2 rounded">
                                <small class="text-muted">Adjust multipliers to see impact preview</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="previewSeasonalChanges">
                    <i class="fas fa-eye"></i> Preview Changes
                </button>
                <button type="button" class="btn btn-primary" id="saveSeasonalSettings">
                    <i class="fas fa-save"></i> Save & Regenerate
                </button>
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

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.recommendation-checkbox:checked + label {
    background-color: #007bff;
    color: white;
}

.seasonal-multiplier:focus, .seasonal-description:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.preset-btn:hover {
    transform: translateX(5px);
    transition: transform 0.2s;
}

#seasonalPreview {
    background-color: #f8f9fa;
    min-height: 100px;
}

.badge-applied { background-color: #28a745; }
.badge-pending { background-color: #ffc107; color: #212529; }
.badge-customized { background-color: #17a2b8; }
.badge-rejected { background-color: #dc3545; }
</style>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize DataTable
    const table = $('#recommendationsTable').DataTable({
        "order": [[ 8, "desc" ]], // Sort by potential savings
        "pageLength": 25,
        "searching": false,
        "info": true,
        "lengthChange": true,
        "columnDefs": [
            { "orderable": false, "targets": [0, 11] } // Disable sorting for checkbox and actions
        ]
    });

    // Select All Checkbox
    $('#selectAll').on('change', function() {
        $('.recommendation-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });

    // Individual Checkboxes
    $(document).on('change', '.recommendation-checkbox', function() {
        updateSelectedCount();
        
        // Update select all checkbox
        const total = $('.recommendation-checkbox').length;
        const checked = $('.recommendation-checkbox:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });

    function updateSelectedCount() {
        const selected = $('.recommendation-checkbox:checked').length;
        $('#applyAllRecommendations').text(`Apply Selected (${selected})`);
        $('#applyAllRecommendations').prop('disabled', selected === 0);
    }

    // Custom filters
    $('#confidenceFilter, #statusFilter').on('change', function() {
        filterTable();
    });

    $('#searchProduct').on('keyup', function() {
        filterTable();
    });

    $('#resetFilters').on('click', function() {
        $('#confidenceFilter, #statusFilter').val('');
        $('#searchProduct').val('');
        $('.recommendation-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        filterTable();
        updateSelectedCount();
    });

    function filterTable() {
        const confidenceFilter = $('#confidenceFilter').val();
        const statusFilter = $('#statusFilter').val();
        const searchText = $('#searchProduct').val().toLowerCase();

        $('#recommendationsTable tbody tr').each(function() {
            let show = true;
            const $row = $(this);
            const confidence = $row.data('confidence');
            const status = $row.data('status');
            const text = $row.text().toLowerCase();

            // Confidence filter
            if (confidenceFilter && confidence !== confidenceFilter) {
                show = false;
            }

            // Status filter
            if (statusFilter && status !== statusFilter) {
                show = false;
            }

            // Search filter
            if (searchText && !text.includes(searchText)) {
                show = false;
            }

            $row.toggle(show);
        });

        // Update total count
        const visibleRows = $('#recommendationsTable tbody tr:visible').length;
        $('#totalRecommendations').text(visibleRows);
    }

    // Apply single recommendation
    $(document).on('click', '.apply-recommendation', function() {
        const recommendationId = $(this).data('id');
        const $button = $(this);
        
        if (confirm('Apply this recommendation?')) {
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: '{{ route("analytics.inventory-optimization.apply") }}',
                method: 'POST',
                data: {
                    recommendation_id: recommendationId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        
                        // Update row status
                        const $row = $button.closest('tr');
                        $row.find('.badge').removeClass('badge-warning').addClass('badge-success').text('Applied');
                        
                        // Hide action buttons for applied recommendation
                        $button.closest('.btn-group').find('.apply-recommendation, .customize-recommendation').hide();
                        
                        // Update checkbox
                        $row.find('.recommendation-checkbox').prop('disabled', true);
                        
                    } else {
                        showNotification(response.message || 'Failed to apply recommendation', 'error');
                        $button.prop('disabled', false).html('<i class="fas fa-check"></i>');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error applying recommendation';
                    showNotification(message, 'error');
                    $button.prop('disabled', false).html('<i class="fas fa-check"></i>');
                }
            });
        }
    });

    // Customize recommendation
    $(document).on('click', '.customize-recommendation', function() {
        const recommendationId = $(this).data('id');
        const recommendedQty = $(this).data('recommended');
        
        $('#customizeRecommendationId').val(recommendationId);
        $('#systemRecommendation').val(recommendedQty);
        $('#customQuantity').val(recommendedQty);
        $('#customReason').val('');
        
        $('#customizeModal').modal('show');
    });

    // Save customization
    $('#saveCustomization').on('click', function() {
        const recommendationId = $('#customizeRecommendationId').val();
        const customQuantity = parseInt($('#customQuantity').val());
        const reason = $('#customReason').val();
        
        if (!customQuantity || customQuantity < 0) {
            showNotification('Please enter a valid quantity', 'error');
            return;
        }
        
        const $button = $(this);
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '{{ route("analytics.inventory-optimization.customize") }}',
            method: 'POST',
            data: {
                recommendation_id: recommendationId,
                custom_quantity: customQuantity,
                reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $('#customizeModal').modal('hide');
                    
                    // Update the table row
                    const $row = $(`tr[data-id="${recommendationId}"]`);
                    $row.find('.badge').removeClass('badge-warning').addClass('badge-info').text('Customized');
                    
                    // Update recommended quantity display
                    $row.find('.recommendation-highlight h5').text(customQuantity.toLocaleString());
                    
                    // Hide action buttons
                    $row.find('.apply-recommendation, .customize-recommendation').hide();
                    $row.find('.recommendation-checkbox').prop('disabled', true);
                    
                } else {
                    showNotification(response.message || 'Failed to customize recommendation', 'error');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error customizing recommendation';
                showNotification(message, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html('<i class="fas fa-save"></i> Apply Custom Quantity');
            }
        });
    });

    // Apply all selected recommendations
    $('#applyAllRecommendations').on('click', function() {
        const selectedIds = $('.recommendation-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedIds.length === 0) {
            showNotification('Please select recommendations to apply', 'warning');
            return;
        }
        
        const confidenceFilter = $('#confidenceFilter').val();
        
        if (confirm(`Apply ${selectedIds.length} selected recommendations?`)) {
            const $button = $(this);
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Applying...');
            
            $.ajax({
                url: '{{ route("analytics.inventory-optimization.apply-all") }}',
                method: 'POST',
                data: {
                    recommendation_ids: selectedIds,
                    confidence_filter: confidenceFilter,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        
                        // Update applied rows
                        selectedIds.forEach(function(id) {
                            const $row = $(`tr[data-id="${id}"]`);
                            $row.find('.badge').removeClass('badge-warning').addClass('badge-success').text('Applied');
                            $row.find('.apply-recommendation, .customize-recommendation').hide();
                            $row.find('.recommendation-checkbox').prop('disabled', true);
                        });
                        
                        // Clear selection
                        $('.recommendation-checkbox').prop('checked', false);
                        $('#selectAll').prop('checked', false);
                        updateSelectedCount();
                        
                        if (response.details.errors_count > 0) {
                            showNotification(`${response.details.errors_count} recommendations had errors`, 'warning');
                        }
                        
                    } else {
                        showNotification(response.message || 'Failed to apply recommendations', 'error');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error applying recommendations';
                    showNotification(message, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).html('<i class="fas fa-magic"></i> Apply Selected');
                }
            });
        }
    });

    // View recommendation details - FIXED FOR SAFE DATA HANDLING
    $(document).on('click', '.view-details', function() {
        const recommendationId = $(this).data('id');
        const recData = $(this).data('rec-data');
        
        $('#recommendationDetailContent').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading recommendation details...</p>
            </div>
        `);
        
        $('#detailModal').modal('show');
        
        // Try to get details from API first, fallback to data attribute
        $.ajax({
            url: `/analytics/inventory-optimization/details/${recommendationId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayRecommendationDetails(response.recommendation, response.analysis);
                } else {
                    // Fallback to data attribute
                    displayRecommendationDetailsFromData(recData);
                }
            },
            error: function() {
                // Fallback to data attribute
                displayRecommendationDetailsFromData(recData);
            }
        });
    });

    function displayRecommendationDetails(rec, analysis) {
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle"></i> Current Situation</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="50%">Store:</th><td>${rec.toko_nama || 'Unknown'}</td></tr>
                                <tr><th>Product:</th><td>${rec.barang_nama || 'Unknown'}</td></tr>
                                <tr><th>Avg Shipped:</th><td><strong>${rec.historical_avg_shipped || 0}</strong> units</td></tr>
                                <tr><th>Avg Sold:</th><td><strong>${rec.historical_avg_sold || 0}</strong> units</td></tr>
                                <tr><th>Current Waste:</th><td><span class="text-danger">${analysis.current_situation.current_waste || 0}</span> units</td></tr>
                                <tr><th>Waste Cost:</th><td><span class="text-danger">Rp ${(analysis.current_situation.waste_cost || 0).toLocaleString()}</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-target"></i> Optimization Result</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="50%">Recommended:</th><td><strong class="text-success">${rec.recommended_quantity || 0}</strong> units</td></tr>
                                <tr><th>Waste Reduction:</th><td><strong class="text-success">${analysis.optimization_result.waste_reduction || 0}</strong> units</td></tr>
                                <tr><th>Cost Savings:</th><td><strong class="text-success">Rp ${(analysis.optimization_result.cost_savings || 0).toLocaleString()}</strong></td></tr>
                                <tr><th>Efficiency Gain:</th><td><strong class="text-primary">${rec.improvement_percentage || 0}%</strong></td></tr>
                                <tr><th>Confidence:</th><td><span class="badge badge-${getConfidenceClass(rec.confidence_level)}">${rec.confidence_level || 'Medium'}</span></td></tr>
                                <tr><th>Data Quality:</th><td><div class="progress progress-sm"><div class="progress-bar" style="width: ${analysis.factors.data_quality || 0}%"></div></div> ${Math.round(analysis.factors.data_quality || 0)}%</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#recommendationDetailContent').html(content);
    }

    function displayRecommendationDetailsFromData(recData) {
        try {
            const rec = typeof recData === 'string' ? JSON.parse(recData) : recData;
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Store Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="50%">Store:</th><td>${rec.toko_nama || 'Unknown'}</td></tr>
                                    <tr><th>Product:</th><td>${rec.barang_nama || 'Unknown'}</td></tr>
                                    <tr><th>Product Code:</th><td>${rec.barang_kode || 'No code'}</td></tr>
                                    <tr><th>Current Average:</th><td><strong>${rec.historical_avg_shipped || 0}</strong> units</td></tr>
                                    <tr><th>Average Sold:</th><td><strong>${rec.historical_avg_sold || 0}</strong> units</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-target"></i> Recommendation</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="50%">Recommended:</th><td><strong class="text-success">${rec.recommended_quantity || 0}</strong> units</td></tr>
                                    <tr><th>Confidence:</th><td><span class="badge badge-${getConfidenceClass(rec.confidence_level)}">${rec.confidence_level || 'Medium'}</span></td></tr>
                                    <tr><th>Seasonal Factor:</th><td>${rec.seasonal_multiplier || 1.0}x</td></tr>
                                    <tr><th>Trend Factor:</th><td>${rec.trend_multiplier || 1.0}x</td></tr>
                                    <tr><th>Potential Savings:</th><td><strong class="text-success">Rp ${(rec.potential_savings || 0).toLocaleString()}</strong></td></tr>
                                    <tr><th>Improvement:</th><td><strong class="text-primary">${rec.improvement_percentage || 0}%</strong></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#recommendationDetailContent').html(content);
        } catch (e) {
            $('#recommendationDetailContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading recommendation details
                </div>
            `);
        }
    }

    function getConfidenceClass(confidence) {
        switch (confidence) {
            case 'High': return 'success';
            case 'Medium': return 'primary';
            case 'Low': return 'warning';
            case 'Very Low': return 'danger';
            default: return 'secondary';
        }
    }

    // Export functionality
    $('#exportRecommendations').on('click', function() {
        const confidenceFilter = $('#confidenceFilter').val();
        const statusFilter = $('#statusFilter').val();
        
        let url = '{{ route("analytics.inventory-optimization.export") }}';
        const params = new URLSearchParams();
        
        if (confidenceFilter) params.append('confidence_filter', confidenceFilter);
        if (statusFilter) params.append('status_filter', statusFilter);
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        // Show loading notification
        showNotification('Export started...', 'info');
        
        window.location.href = url;
    });

    // Refresh recommendations
    $('#refreshRecommendations').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        
        $.ajax({
            url: '{{ route("analytics.inventory-optimization.generate") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.message || 'Failed to refresh recommendations', 'error');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error refreshing recommendations';
                showNotification(message, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html('<i class="fas fa-sync"></i> Refresh');
            }
        });
    });

    // Initialize Charts
    initializeCharts();

    function initializeCharts() {
        // Turnover Trend Chart
        const turnoverCtx = document.getElementById('turnoverChart');
        if (turnoverCtx) {
            new Chart(turnoverCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Current Turnover Rate',
                        data: [2.1, 2.3, 2.8, 2.6, 3.1, {{ round($turnoverStats['current_turnover_rate'] ?? 0, 1) }}],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Target Rate (4.0x)',
                        data: [4.0, 4.0, 4.0, 4.0, 4.0, 4.0],
                        borderColor: '#007bff',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + 'x';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5,
                            title: { 
                                display: true, 
                                text: 'Turnover Rate (x/month)' 
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + 'x';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Confidence Distribution Chart
        const confidenceCtx = document.getElementById('confidenceChart');
        if (confidenceCtx) {
            const confidenceData = {
                'High': {{ ($summaryStats['avg_confidence_distribution']['High'] ?? 0) }},
                'Medium': {{ ($summaryStats['avg_confidence_distribution']['Medium'] ?? 0) }},
                'Low': {{ ($summaryStats['avg_confidence_distribution']['Low'] ?? 0) }},
                'Very Low': {{ ($summaryStats['avg_confidence_distribution']['Very Low'] ?? 0) }}
            };

            new Chart(confidenceCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(confidenceData),
                    datasets: [{
                        data: Object.values(confidenceData),
                        backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
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
                                        text: `${label}: ${data.datasets[0].data[index]}`,
                                        fillStyle: data.datasets[0].backgroundColor[index],
                                        strokeStyle: data.datasets[0].backgroundColor[index],
                                        pointStyle: 'circle'
                                    }));
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '50%'
                }
            });
        }
    }

    function showNotification(message, type = 'info') {
        // Create notification element
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';
        
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : (type === 'warning' ? 'exclamation-triangle' : 'info-circle'))}"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
    }

    // Initialize on page load
    updateSelectedCount();
});
</script>
@endpush
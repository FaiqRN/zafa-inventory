@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-dark text-white shadow-lg">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-crystal-ball mr-2"></i>
                                Predictive Analytics Dashboard
                            </h2>
                            <p class="mb-0 opacity-75">
                                Mesin Prediksi Masa Depan - AI Sederhana untuk Bisnis Cerdas!
                                Seperti punya "dukun sakti" untuk bisnis, tapi pakai data!
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mb-0">75%</h4>
                                    <small>Prediction Accuracy</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0">-45%</h4>
                                    <small>Over-stock</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prediction Accuracy Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Demand Predictions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($demandPredictions) }}
                            </div>
                            <div class="text-xs text-gray-500">75% accuracy rate</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-success"></i>
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
                                Risk Alerts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ collect($partnerRiskScores)->where('level', 'High')->count() }}
                            </div>
                            <div class="text-xs text-gray-500">High risk partners</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
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
                                Seasonal Forecasts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($seasonalForecasts) }}
                            </div>
                            <div class="text-xs text-gray-500">Next 6 months</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-info"></i>
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
                                New Opportunities
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($opportunities) }}
                            </div>
                            <div class="text-xs text-gray-500">Identified prospects</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-lightbulb fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Prediction Engine Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-left border-primary">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="alert-heading mb-2">
                            <i class="fas fa-robot mr-2"></i>
                            AI Prediction Engine Status
                        </h5>
                        <p class="mb-0">
                            <strong>Status:</strong> <span class="badge badge-success">Active</span> |
                            <strong>Last Updated:</strong> {{ now()->format('d M Y, H:i') }} |
                            <strong>Data Points:</strong> 12,847 |
                            <strong>Model Version:</strong> v2.1
                        </p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="refreshPredictions">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#algorithmModal">
                                <i class="fas fa-cog"></i> Algorithm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demand Predictions Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-1"></i>
                        Demand Forecasting - Next Month Predictions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="demandPredictionsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Store</th>
                                    <th width="20%">Product</th>
                                    <th width="15%" class="text-center">Predicted Demand</th>
                                    <th width="15%" class="text-center">Confidence</th>
                                    <th width="10%" class="text-center">Seasonal Adj</th>
                                    <th width="10%" class="text-center">Trend</th>
                                    <th width="5%" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($demandPredictions as $index => $prediction)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-2">
                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                    {{ strtoupper(substr($prediction['store_name'], 0, 2)) }}
                                                </div>
                                            </div>
                                            <div>
                                                <strong>{{ $prediction['store_name'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $prediction['store_id'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $prediction['product_name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $prediction['product_code'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="prediction-value">
                                            <h5 class="mb-0 text-primary">{{ $prediction['predicted_quantity'] }}</h5>
                                            <small class="text-muted">units</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $confidence = $prediction['confidence'];
                                            $color = $confidence >= 80 ? 'success' : ($confidence >= 60 ? 'warning' : 'danger');
                                        @endphp
                                        <div class="confidence-indicator">
                                            <div class="progress progress-sm mb-1">
                                                <div class="progress-bar bg-{{ $color }}" style="width: {{ $confidence }}%"></div>
                                            </div>
                                            <strong class="text-{{ $color }}">{{ $confidence }}%</strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($prediction['seasonal_factor'] > 1)
                                            <span class="badge badge-success">+{{ number_format(($prediction['seasonal_factor'] - 1) * 100) }}%</span>
                                        @elseif($prediction['seasonal_factor'] < 1)
                                            <span class="badge badge-warning">{{ number_format(($prediction['seasonal_factor'] - 1) * 100) }}%</span>
                                        @else
                                            <span class="badge badge-secondary">Normal</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($prediction['trend'] === 'increasing')
                                            <i class="fas fa-arrow-up text-success" title="Increasing"></i>
                                        @elseif($prediction['trend'] === 'decreasing')
                                            <i class="fas fa-arrow-down text-danger" title="Decreasing"></i>
                                        @else
                                            <i class="fas fa-minus text-muted" title="Stable"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($prediction['confidence'] >= 80)
                                            <span class="badge badge-success">High</span>
                                        @elseif($prediction['confidence'] >= 60)
                                            <span class="badge badge-warning">Medium</span>
                                        @else
                                            <span class="badge badge-danger">Low</span>
                                        @endif
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

    <!-- Risk Scoring & Early Warning -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Partner Risk Scoring & Early Warning System
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Partner</th>
                                    <th class="text-center">Risk Score</th>
                                    <th class="text-center">Risk Level</th>
                                    <th class="text-center">Main Risk Factors</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(collect($partnerRiskScores)->take(10) as $risk)
                                <tr class="{{ $risk['level'] === 'High' ? 'table-danger' : ($risk['level'] === 'Medium' ? 'table-warning' : '') }}">
                                    <td>
                                        <strong>{{ $risk['partner_name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $risk['partner_id'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $risk['level'] === 'High' ? 'danger' : ($risk['level'] === 'Medium' ? 'warning' : 'success') }}">
                                            {{ $risk['score'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $risk['level'] === 'High' ? 'danger' : ($risk['level'] === 'Medium' ? 'warning' : 'success') }}">
                                            {{ $risk['level'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small>
                                            @foreach(array_slice($risk['factors'], 0, 2) as $factor)
                                                <span class="badge badge-light">{{ ucfirst(str_replace('_', ' ', $factor['factor'])) }}</span>
                                            @endforeach
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($risk['level'] === 'High')
                                            <button class="btn btn-sm btn-danger" onclick="triggerAlert('{{ $risk['partner_id'] }}')">
                                                <i class="fas fa-bell"></i> Alert
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-info" onclick="monitorPartner('{{ $risk['partner_id'] }}')">
                                                <i class="fas fa-eye"></i> Monitor
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-1"></i>
                        Risk Level Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="riskDistributionChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Seasonal Forecasting -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        Seasonal Demand Forecasting - Next 6 Months
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="seasonalForecastChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- New Opportunities Identification -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-lightbulb mr-1"></i>
                        AI-Identified New Opportunities
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($opportunities as $opportunity)
                        <div class="col-md-6 mb-3">
                            <div class="opportunity-card p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="text-success mb-0">
                                        @if($opportunity['type'] === 'new_location')
                                            <i class="fas fa-map-marker-alt mr-1"></i> New Location
                                        @elseif($opportunity['type'] === 'product_expansion')
                                            <i class="fas fa-plus-circle mr-1"></i> Product Expansion
                                        @elseif($opportunity['type'] === 'partnership')
                                            <i class="fas fa-handshake mr-1"></i> Partnership
                                        @else
                                            <i class="fas fa-star mr-1"></i> Market Gap
                                        @endif
                                        {{ $opportunity['title'] }}
                                    </h6>
                                    <span class="badge badge-success">{{ $opportunity['confidence'] }}%</span>
                                </div>
                                <p class="text-muted small mb-2">{{ $opportunity['description'] }}</p>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Potential Revenue:</small>
                                        <br>
                                        <strong class="text-success">Rp {{ number_format($opportunity['potential_revenue'], 0, ',', '.') }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Investment Required:</small>
                                        <br>
                                        <strong class="text-warning">Rp {{ number_format($opportunity['investment_required'], 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-success" onclick="exploreOpportunity('{{ $opportunity['id'] }}')">
                                        <i class="fas fa-search-plus"></i> Explore
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="saveOpportunity('{{ $opportunity['id'] }}')">
                                        <i class="fas fa-bookmark"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Algorithm Configuration Modal -->
<div class="modal fade" id="algorithmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog mr-2"></i>
                    AI Algorithm Configuration
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Prediction Parameters</h6>
                        <div class="form-group">
                            <label>Historical Data Period</label>
                            <select class="form-control">
                                <option value="12">12 months</option>
                                <option value="18" selected>18 months</option>
                                <option value="24">24 months</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Seasonal Weight</label>
                            <input type="range" class="custom-range" min="0" max="100" value="30">
                            <small class="text-muted">30% influence</small>
                        </div>
                        <div class="form-group">
                            <label>Trend Weight</label>
                            <input type="range" class="custom-range" min="0" max="100" value="40">
                            <small class="text-muted">40% influence</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Risk Assessment</h6>
                        <div class="form-group">
                            <label>Performance Threshold</label>
                            <input type="number" class="form-control" value="60" min="0" max="100">
                            <small class="text-muted">Below this % triggers risk flag</small>
                        </div>
                        <div class="form-group">
                            <label>Payment Delay Threshold</label>
                            <input type="number" class="form-control" value="30" min="7" max="90">
                            <small class="text-muted">Days before risk increase</small>
                        </div>
                        <div class="form-group">
                            <label>Confidence Threshold</label>
                            <input type="number" class="form-control" value="75" min="50" max="95">
                            <small class="text-muted">Minimum for high confidence</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAlgorithmSettings">Save Configuration</button>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success { border-left: 4px solid #28a745 !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.border-left-info { border-left: 4px solid #17a2b8 !important; }
.border-left-primary { border-left: 4px solid #007bff !important; }

.avatar-sm { width: 2rem; height: 2rem; }
.avatar-title {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 600;
}

.bg-soft-primary { background-color: rgba(0, 123, 255, 0.1); }

.prediction-value {
    background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
    padding: 10px; border-radius: 8px;
}

.confidence-indicator .progress-sm { height: 0.5rem; }

.opportunity-card {
    background-color: #f8f9fa;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.opportunity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.table td { vertical-align: middle; }

.bg-gradient-dark {
    background: linear-gradient(45deg, #343a40, #212529);
}
</style>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#demandPredictionsTable').DataTable({
        "order": [[ 4, "desc" ]], // Sort by confidence
        "pageLength": 10,
        "searching": true,
        "info": false
    });

    // Initialize Charts
    initializeCharts();
    
    // Real-time updates simulation
    startPredictionUpdates();
});

function initializeCharts() {
    // Risk Distribution Chart
    const riskCtx = document.getElementById('riskDistributionChart').getContext('2d');
    const riskData = {
        'High': {{ collect($partnerRiskScores)->where('level', 'High')->count() }},
        'Medium': {{ collect($partnerRiskScores)->where('level', 'Medium')->count() }},
        'Low': {{ collect($partnerRiskScores)->where('level', 'Low')->count() }}
    };
    
    new Chart(riskCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(riskData),
            datasets: [{
                data: Object.values(riskData),
                backgroundColor: ['#dc3545', '#ffc107', '#28a745']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Seasonal Forecast Chart
    const seasonalCtx = document.getElementById('seasonalForecastChart').getContext('2d');
    new Chart(seasonalCtx, {
        type: 'line',
        data: {
            labels: ['Jul 2025', 'Aug 2025', 'Sep 2025', 'Oct 2025', 'Nov 2025', 'Dec 2025'],
            datasets: [{
                label: 'Predicted Demand',
                data: [1250, 1180, 1320, 1150, 1280, 1450],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'Seasonal Baseline',
                data: [1200, 1200, 1200, 1200, 1200, 1200],
                borderColor: '#6c757d',
                backgroundColor: 'transparent',
                borderDash: [5, 5]
            }, {
                label: 'Confidence Interval (Upper)',
                data: [1350, 1280, 1420, 1250, 1380, 1550],
                borderColor: 'rgba(0, 123, 255, 0.3)',
                backgroundColor: 'transparent',
                borderDash: [2, 2],
                pointRadius: 0
            }, {
                label: 'Confidence Interval (Lower)',
                data: [1150, 1080, 1220, 1050, 1180, 1350],
                borderColor: 'rgba(0, 123, 255, 0.3)',
                backgroundColor: 'transparent',
                borderDash: [2, 2],
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return value + ' units';
                        }
                    }
                }
            }
        }
    });
}

function startPredictionUpdates() {
    // Simulate real-time prediction updates
    setInterval(function() {
        updatePredictionStatus();
    }, 30000); // Update every 30 seconds
}

function updatePredictionStatus() {
    // Simulate minor changes in confidence levels
    $('.confidence-indicator .progress-bar').each(function() {
        const currentWidth = parseInt($(this).css('width'));
        const randomChange = Math.floor(Math.random() * 6) - 3; // -3 to +3
        const newWidth = Math.max(50, Math.min(95, currentWidth + randomChange));
        $(this).css('width', newWidth + '%');
        $(this).next('strong').text(newWidth + '%');
    });
}

function triggerAlert(partnerId) {
    alert(`High risk alert triggered for partner: ${partnerId}\nRecommended actions:\n- Schedule immediate review\n- Reduce inventory allocation\n- Implement payment monitoring`);
}

function monitorPartner(partnerId) {
    alert(`Added partner ${partnerId} to monitoring list.\nWill track performance changes and notify if risk level increases.`);
}

function exploreOpportunity(opportunityId) {
    alert(`Exploring opportunity: ${opportunityId}\nShowing detailed analysis, market research, and implementation timeline...`);
}

function saveOpportunity(opportunityId) {
    alert(`Opportunity ${opportunityId} saved to your action items.\nYou can review saved opportunities in the Business Planning section.`);
}

// Refresh predictions
$('#refreshPredictions').on('click', function() {
    const button = $(this);
    const originalText = button.html();
    
    button.html('<i class="fas fa-spinner fa-spin"></i> Updating...');
    button.prop('disabled', true);
    
    // Simulate API call delay
    setTimeout(function() {
        button.html(originalText);
        button.prop('disabled', false);
        alert('Predictions updated with latest data!');
        location.reload();
    }, 3000);
});

// Save algorithm settings
$('#saveAlgorithmSettings').on('click', function() {
    alert('Algorithm configuration saved!\nPrediction model will use new parameters for future calculations.');
    $('#algorithmModal').modal('hide');
});

// Auto-refresh notifications
function showPredictionAlert() {
    const alertHtml = `
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Prediction Alert:</strong> 3 partners showing declining performance trends. Review recommended.
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 10 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 10000);
}

// Show alert after page load
setTimeout(showPredictionAlert, 5000);
</script>
@endpush
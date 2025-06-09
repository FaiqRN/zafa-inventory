@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-secondary text-white shadow-lg">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-balance-scale mr-2"></i>
                                Channel Comparison Analytics
                            </h2>
                            <p class="mb-0 opacity-75">
                                Pertarungan Channel - Konsinyasi vs Jualan Langsung, Mana Lebih Untung?
                                Sistem perbandingan seperti "ring tinju" antara 2 cara jualan Anda!
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mb-0">+32%</h4>
                                    <small>Revenue Growth</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0">65/35</h4>
                                    <small>Optimal Mix</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Overview Comparison -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card border-primary shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-handshake mr-2"></i>
                        B2B Konsinyasi Channel
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="metric-item text-center">
                                <h4 class="text-primary mb-1">{{ number_format($b2bStats['monthly_volume']) }}</h4>
                                <small class="text-muted">Units/Month</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="metric-item text-center">
                                <h4 class="text-success mb-1">Rp {{ number_format($b2bStats['avg_price'], 0, ',', '.') }}</h4>
                                <small class="text-muted">Avg Price</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="channel-stats">
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Volume:</span>
                            <strong class="text-primary">High ({{ number_format($b2bStats['volume']) }} units)</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Margin:</span>
                            <strong class="text-warning">{{ number_format($b2bStats['margin'], 1) }}%</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Effort Level:</span>
                            <strong class="text-info">{{ $b2bStats['effort_level'] }}</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Scalability:</span>
                            <strong class="text-success">{{ $b2bStats['scalability'] }}</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between">
                            <span>Cash Flow:</span>
                            <strong class="text-danger">{{ $b2bStats['cash_flow_speed'] }}</strong>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6 class="text-primary">Karakteristik:</h6>
                        <ul class="list-unstyled small">
                            <li><i class="fas fa-check text-success mr-1"></i> Volume tinggi, stabil</li>
                            <li><i class="fas fa-check text-success mr-1"></i> Setup sekali, jalan terus</li>
                            <li><i class="fas fa-times text-danger mr-1"></i> Margin lebih rendah</li>
                            <li><i class="fas fa-times text-danger mr-1"></i> Cash flow lambat (2 minggu)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-success shadow h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user mr-2"></i>
                        B2C Direct Sales Channel
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="metric-item text-center">
                                <h4 class="text-primary mb-1">{{ number_format($b2cStats['monthly_volume']) }}</h4>
                                <small class="text-muted">Units/Month</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="metric-item text-center">
                                <h4 class="text-success mb-1">Rp {{ number_format($b2cStats['avg_price'], 0, ',', '.') }}</h4>
                                <small class="text-muted">Avg Price</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="channel-stats">
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Volume:</span>
                            <strong class="text-warning">Medium ({{ number_format($b2cStats['volume']) }} units)</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Margin:</span>
                            <strong class="text-success">{{ number_format($b2cStats['margin'], 1) }}%</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Effort Level:</span>
                            <strong class="text-warning">{{ $b2cStats['effort_level'] }}</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between mb-2">
                            <span>Scalability:</span>
                            <strong class="text-warning">{{ $b2cStats['scalability'] }}</strong>
                        </div>
                        <div class="stat-row d-flex justify-content-between">
                            <span>Cash Flow:</span>
                            <strong class="text-success">{{ $b2cStats['cash_flow_speed'] }}</strong>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6 class="text-success">Karakteristik:</h6>
                        <ul class="list-unstyled small">
                            <li><i class="fas fa-check text-success mr-1"></i> Margin tinggi per unit</li>
                            <li><i class="fas fa-check text-success mr-1"></i> Cash flow cepat (langsung)</li>
                            <li><i class="fas fa-times text-danger mr-1"></i> Volume terbatas</li>
                            <li><i class="fas fa-times text-danger mr-1"></i> Effort marketing tinggi</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Performance Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Volume Winner
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $comparison['volume_winner'] }}
                            </div>
                            <div class="text-xs text-gray-500">Higher total units</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Revenue Winner
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $comparison['revenue_winner'] }}
                            </div>
                            <div class="text-xs text-gray-500">Higher total revenue</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
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
                                Margin Winner
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $comparison['margin_winner'] }}
                            </div>
                            <div class="text-xs text-gray-500">Higher profit margin</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-warning"></i>
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
                                Current Mix
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($comparison['b2b_percentage'], 0) }}/{{ number_format($comparison['b2c_percentage'], 0) }}
                            </div>
                            <div class="text-xs text-gray-500">B2B/B2C ratio</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Performance Charts -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-1"></i>
                        Channel Performance Comparison
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="channelComparisonChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-1"></i>
                        Revenue Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueDistributionChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analysis Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-1"></i>
                        Detailed Channel Analysis
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="25%">Metric</th>
                                    <th width="25%" class="text-center">B2B Konsinyasi</th>
                                    <th width="25%" class="text-center">B2C Direct Sales</th>
                                    <th width="25%" class="text-center">Winner</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Total Volume (6 months)</strong></td>
                                    <td class="text-center">{{ number_format($b2bStats['volume']) }} units</td>
                                    <td class="text-center">{{ number_format($b2cStats['volume']) }} units</td>
                                    <td class="text-center">
                                        @if($b2bStats['volume'] > $b2cStats['volume'])
                                            <span class="badge badge-primary">B2B</span>
                                        @else
                                            <span class="badge badge-success">B2C</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Revenue</strong></td>
                                    <td class="text-center">Rp {{ number_format($b2bStats['revenue'], 0, ',', '.') }}</td>
                                    <td class="text-center">Rp {{ number_format($b2cStats['revenue'], 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($b2bStats['revenue'] > $b2cStats['revenue'])
                                            <span class="badge badge-primary">B2B</span>
                                        @else
                                            <span class="badge badge-success">B2C</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Average Price per Unit</strong></td>
                                    <td class="text-center">Rp {{ number_format($b2bStats['avg_price'], 0, ',', '.') }}</td>
                                    <td class="text-center">Rp {{ number_format($b2cStats['avg_price'], 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($b2bStats['avg_price'] > $b2cStats['avg_price'])
                                            <span class="badge badge-primary">B2B</span>
                                        @else
                                            <span class="badge badge-success">B2C</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Profit Margin</strong></td>
                                    <td class="text-center">{{ number_format($b2bStats['margin'], 1) }}%</td>
                                    <td class="text-center">{{ number_format($b2cStats['margin'], 1) }}%</td>
                                    <td class="text-center">
                                        @if($b2bStats['margin'] > $b2cStats['margin'])
                                            <span class="badge badge-primary">B2B</span>
                                        @else
                                            <span class="badge badge-success">B2C</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Monthly Volume</strong></td>
                                    <td class="text-center">{{ number_format($b2bStats['monthly_volume']) }} units/month</td>
                                    <td class="text-center">{{ number_format($b2cStats['monthly_volume']) }} units/month</td>
                                    <td class="text-center">
                                        @if($b2bStats['monthly_volume'] > $b2cStats['monthly_volume'])
                                            <span class="badge badge-primary">B2B</span>
                                        @else
                                            <span class="badge badge-success">B2C</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Effort Level</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-warning">{{ $b2bStats['effort_level'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-danger">{{ $b2cStats['effort_level'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">B2B</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Scalability</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ $b2bStats['scalability'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-warning">{{ $b2cStats['scalability'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">B2B</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Cash Flow Speed</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-danger">{{ $b2bStats['cash_flow_speed'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ $b2cStats['cash_flow_speed'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">B2C</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Strategic Recommendations -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Strategic Channel Recommendations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="fas fa-handshake mr-1"></i>
                                B2B Konsinyasi Strategy
                            </h6>
                            <div class="recommendation-box p-3 border border-primary rounded">
                                <h6 class="text-success">✅ Strengths to Leverage:</h6>
                                <ul class="mb-3">
                                    <li>High volume capacity - dapat handle pesanan besar</li>
                                    <li>Scalable business model - tambah partner = tambah volume</li>
                                    <li>Lower operational effort once established</li>
                                    <li>Predictable monthly revenue stream</li>
                                </ul>
                                
                                <h6 class="text-warning">⚠️ Areas to Improve:</h6>
                                <ul class="mb-3">
                                    <li>Optimize payment terms - reduce dari 2 minggu ke 1 minggu</li>
                                    <li>Improve partner selection - focus pada high-performing stores</li>
                                    <li>Implement better inventory management</li>
                                    <li>Negotiate better pricing for volume deals</li>
                                </ul>
                                
                                <div class="alert alert-primary">
                                    <strong>Recommendation:</strong> Allocate 65% of resources to B2B channel. 
                                    Focus on quality partners and process optimization.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-success">
                                <i class="fas fa-user mr-1"></i>
                                B2C Direct Sales Strategy
                            </h6>
                            <div class="recommendation-box p-3 border border-success rounded">
                                <h6 class="text-success">✅ Strengths to Leverage:</h6>
                                <ul class="mb-3">
                                    <li>Higher profit margin per unit</li>
                                    <li>Immediate cash flow - tidak perlu tunggu</li>
                                    <li>Direct customer relationship</li>
                                    <li>Premium pricing capability</li>
                                </ul>
                                
                                <h6 class="text-warning">⚠️ Areas to Improve:</h6>
                                <ul class="mb-3">
                                    <li>Scale up marketing efforts - Instagram, TikTok ads</li>
                                    <li>Automate customer service untuk efficiency</li>
                                    <li>Develop loyalty programs untuk repeat customers</li>
                                    <li>Expand product packaging options untuk gift market</li>
                                </ul>
                                
                                <div class="alert alert-success">
                                    <strong>Recommendation:</strong> Allocate 35% of resources to B2C channel.
                                    Focus on digital marketing and premium products.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Optimal Mix Recommendation -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-balance-scale mr-1"></i>
                                        Optimal Channel Mix Strategy
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="optimal-mix-item">
                                                <h4 class="text-primary">65%</h4>
                                                <h6>B2B Focus</h6>
                                                <p class="text-muted small">
                                                    Volume-driven growth dengan partner berkualitas tinggi. 
                                                    Stable revenue base untuk business sustainability.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="optimal-mix-item">
                                                <h4 class="text-success">35%</h4>
                                                <h6>B2C Focus</h6>
                                                <p class="text-muted small">
                                                    Margin-driven growth dengan direct customer relationship. 
                                                    Premium pricing dan brand building.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="optimal-mix-item">
                                                <h4 class="text-warning">+32%</h4>
                                                <h6>Expected Growth</h6>
                                                <p class="text-muted small">
                                                    Projected revenue increase dengan optimal channel allocation 
                                                    dan strategic improvements.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <h6><i class="fas fa-info-circle mr-1"></i> Implementation Timeline:</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Month 1-2:</strong> Optimize B2B partner selection
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Month 3-4:</strong> Scale up B2C marketing
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Month 5-6:</strong> Monitor and adjust mix
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

<style>
.border-left-primary { border-left: 4px solid #007bff !important; }
.border-left-success { border-left: 4px solid #28a745 !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.border-left-info { border-left: 4px solid #17a2b8 !important; }

.metric-item {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.channel-stats .stat-row {
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.channel-stats .stat-row:last-child {
    border-bottom: none;
}

.recommendation-box {
    background-color: #fafafa;
}

.optimal-mix-item {
    padding: 20px;
    margin: 10px 0;
    border-radius: 10px;
    background-color: #f8f9fa;
    transition: transform 0.2s;
}

.optimal-mix-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.table td {
    vertical-align: middle;
}

.bg-gradient-secondary {
    background: linear-gradient(45deg, #6c757d, #495057);
}
</style>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Initialize Charts
    initializeCharts();
    
    // Add interactive elements
    $('.metric-item').hover(
        function() { $(this).addClass('shadow-sm'); },
        function() { $(this).removeClass('shadow-sm'); }
    );
});

function initializeCharts() {
    // Channel Comparison Chart
    const comparisonCtx = document.getElementById('channelComparisonChart').getContext('2d');
    new Chart(comparisonCtx, {
        type: 'radar',
        data: {
            labels: ['Volume', 'Revenue', 'Margin', 'Scalability', 'Cash Flow', 'Effort (inverse)'],
            datasets: [{
                label: 'B2B Konsinyasi',
                data: [85, 70, 45, 90, 30, 70], // Normalized scores out of 100
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                pointBackgroundColor: '#007bff'
            }, {
                label: 'B2C Direct Sales',
                data: [45, 60, 85, 60, 95, 40], // Normalized scores out of 100
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                pointBackgroundColor: '#28a745'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20
                    }
                }
            }
        }
    });

    // Revenue Distribution Chart
    const revenueCtx = document.getElementById('revenueDistributionChart').getContext('2d');
    const b2bRevenue = {{ $b2bStats['revenue'] }};
    const b2cRevenue = {{ $b2cStats['revenue'] }};
    
    new Chart(revenueCtx, {
        type: 'doughnut',
        data: {
            labels: ['B2B Konsinyasi', 'B2C Direct Sales'],
            datasets: [{
                data: [b2bRevenue, b2cRevenue],
                backgroundColor: ['#007bff', '#28a745']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = b2bRevenue + b2cRevenue;
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + percentage + '% (Rp ' + context.parsed.toLocaleString() + ')';
                        }
                    }
                }
            }
        }
    });
}

// Export functionality
function exportChannelAnalysis() {
    alert('Exporting channel comparison analysis to Excel...');
}

// Optimization functions
function optimizeB2BChannel() {
    alert('Optimizing B2B channel - showing partner recommendations...');
}

function optimizeB2CChannel() {
    alert('Optimizing B2C channel - showing marketing strategies...');
}
</script>
@endpush
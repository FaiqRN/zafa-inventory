@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white shadow-lg">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-trophy mr-2"></i>
                                Partner Performance Analytics
                            </h2>
                            <p class="mb-0 opacity-75">
                                Sistem Penilaian Toko Partner - Siapa yang Terbaik? 
                                Seperti rapor sekolah, tapi untuk toko!
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="mb-0">{{ $partners->where('grade', 'A+')->count() + $partners->where('grade', 'A')->count() }}</h4>
                                    <small>Top Performers</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0">{{ $partners->where('grade', 'C')->count() }}</h4>
                                    <small>Need Attention</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                A+ Partners
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $partners->where('grade', 'A+')->count() }}
                            </div>
                            <div class="text-xs text-gray-500">85%+ sell-through rate</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-medal fa-2x text-success"></i>
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
                                A Partners
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $partners->where('grade', 'A')->count() }}
                            </div>
                            <div class="text-xs text-gray-500">70-85% sell-through rate</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-primary"></i>
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
                                B Partners
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $partners->where('grade', 'B+')->count() + $partners->where('grade', 'B')->count() }}
                            </div>
                            <div class="text-xs text-gray-500">50-70% sell-through rate</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-thumbs-up fa-2x text-warning"></i>
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
                                C Partners
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $partners->where('grade', 'C+')->count() + $partners->where('grade', 'C')->count() }}
                            </div>
                            <div class="text-xs text-gray-500">&lt;50% sell-through rate</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Action Bar -->
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-2">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="gradeFilter">
                                <option value="">All Grades</option>
                                <option value="A+">A+ Grade</option>
                                <option value="A">A Grade</option>
                                <option value="B+">B+ Grade</option>
                                <option value="B">B Grade</option>
                                <option value="C+">C+ Grade</option>
                                <option value="C">C Grade</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="performanceFilter">
                                <option value="">All Performance</option>
                                <option value="high">High (80%+)</option>
                                <option value="medium">Medium (60-80%)</option>
                                <option value="low">Low (&lt;60%)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" id="searchPartner" placeholder="Search partner name...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-secondary" id="resetFilters">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-success btn-sm" id="exportExcel">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button type="button" class="btn btn-info btn-sm" id="sendAlerts">
                    <i class="fas fa-bell"></i> Send Alerts
                </button>
            </div>
        </div>
    </div>

    <!-- Partner Performance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-1"></i>
                        Partner Performance Ranking
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="partnerTable">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Partner Name</th>
                                    <th width="8%" class="text-center">Grade</th>
                                    <th width="12%" class="text-center">Sell-Through Rate</th>
                                    <th width="10%" class="text-center">Shipped</th>
                                    <th width="10%" class="text-center">Sold</th>
                                    <th width="10%" class="text-center">Returned</th>
                                    <th width="12%" class="text-center">Revenue</th>
                                    <th width="8%" class="text-center">Avg Days</th>
                                    <th width="10%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($partners as $index => $partner)
                                <tr data-grade="{{ $partner['grade'] }}" data-performance="{{ $partner['sell_through_rate'] }}">
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-2">
                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                    {{ strtoupper(substr($partner['nama_toko'], 0, 2)) }}
                                                </div>
                                            </div>
                                            <div>
                                                <strong>{{ $partner['nama_toko'] }}</strong>
                                                <br>
                                                <small class="text-muted">ID: {{ $partner['toko_id'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $gradeColors = [
                                                'A+' => 'success',
                                                'A' => 'primary', 
                                                'B+' => 'info',
                                                'B' => 'warning',
                                                'C+' => 'orange',
                                                'C' => 'danger'
                                            ];
                                            $color = $gradeColors[$partner['grade']] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }} badge-lg">
                                            <i class="fas fa-medal mr-1"></i>{{ $partner['grade'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress progress-sm mb-1">
                                            @php
                                                $progressColor = $partner['sell_through_rate'] >= 80 ? 'success' : 
                                                               ($partner['sell_through_rate'] >= 60 ? 'warning' : 'danger');
                                            @endphp
                                            <div class="progress-bar bg-{{ $progressColor }}" 
                                                 style="width: {{ min($partner['sell_through_rate'], 100) }}%"></div>
                                        </div>
                                        <strong>{{ number_format($partner['sell_through_rate'], 1) }}%</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">{{ number_format($partner['total_shipped']) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-success font-weight-bold">{{ number_format($partner['total_sold']) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-danger">{{ number_format($partner['total_returned']) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-success">Rp {{ number_format($partner['revenue'], 0, ',', '.') }}</strong>
                                    </td>
                                    <td class="text-center">
                                        @if($partner['avg_days_to_return'] >= 21)
                                            <span class="badge badge-success">{{ number_format($partner['avg_days_to_return'], 1) }}</span>
                                        @elseif($partner['avg_days_to_return'] >= 14)
                                            <span class="badge badge-warning">{{ number_format($partner['avg_days_to_return'], 1) }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ number_format($partner['avg_days_to_return'], 1) }}</span>
                                        @endif
                                        <small class="d-block text-muted">days</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-toggle="modal" 
                                                    data-target="#detailModal" 
                                                    data-partner="{{ json_encode($partner) }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="viewHistory('{{ $partner['toko_id'] }}')">
                                                <i class="fas fa-chart-line"></i>
                                            </button>
                                            @if(in_array($partner['grade'], ['C+', 'C']))
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="sendAlert('{{ $partner['toko_id'] }}')">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </button>
                                            @endif
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

    <!-- Performance Chart -->
    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area mr-1"></i>
                        Partner Performance Trend
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-1"></i>
                        Grade Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="gradeChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Partner Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-store mr-2"></i>
                    Partner Performance Detail
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="partnerDetailContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success {
    border-left: 4px solid #28a745 !important;
}
.border-left-primary {
    border-left: 4px solid #007bff !important;
}
.border-left-warning {
    border-left: 4px solid #ffc107 !important;
}
.border-left-danger {
    border-left: 4px solid #dc3545 !important;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}

.bg-soft-primary {
    background-color: rgba(0, 123, 255, 0.1);
}

.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.progress-sm {
    height: 0.5rem;
}

.table td {
    vertical-align: middle;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}
</style>
@endsection

@push('js')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#partnerTable').DataTable({
        "order": [[ 0, "asc" ]],
        "pageLength": 25,
        "searching": false,
        "info": false,
        "lengthChange": false,
        "columnDefs": [
            { "orderable": false, "targets": [9] }
        ]
    });

    // Custom filters
    $('#gradeFilter, #performanceFilter').on('change', function() {
        filterTable();
    });

    $('#searchPartner').on('keyup', function() {
        filterTable();
    });

    $('#resetFilters').on('click', function() {
        $('#gradeFilter, #performanceFilter').val('');
        $('#searchPartner').val('');
        filterTable();
    });

    function filterTable() {
        const gradeFilter = $('#gradeFilter').val();
        const performanceFilter = $('#performanceFilter').val();
        const searchText = $('#searchPartner').val().toLowerCase();

        $('#partnerTable tbody tr').each(function() {
            let show = true;
            const $row = $(this);
            const grade = $row.data('grade');
            const performance = parseFloat($row.data('performance'));
            const partnerName = $row.find('td:eq(1)').text().toLowerCase();

            // Grade filter
            if (gradeFilter && grade !== gradeFilter) {
                show = false;
            }

            // Performance filter
            if (performanceFilter) {
                if (performanceFilter === 'high' && performance < 80) show = false;
                if (performanceFilter === 'medium' && (performance < 60 || performance >= 80)) show = false;
                if (performanceFilter === 'low' && performance >= 60) show = false;
            }

            // Search filter
            if (searchText && !partnerName.includes(searchText)) {
                show = false;
            }

            $row.toggle(show);
        });
    }

    // Modal detail handler
    $('#detailModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const partner = button.data('partner');
        
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Partner Name:</th><td>${partner.nama_toko}</td></tr>
                                <tr><th>Partner ID:</th><td>${partner.toko_id}</td></tr>
                                <tr><th>Grade:</th><td><span class="badge badge-primary">${partner.grade}</span></td></tr>
                                <tr><th>Performance Score:</th><td>${partner.performance_score}</td></tr>
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
                                <tr><th width="40%">Sell-Through Rate:</th><td>${partner.sell_through_rate}%</td></tr>
                                <tr><th>Total Shipped:</th><td>${partner.total_shipped.toLocaleString()} pcs</td></tr>
                                <tr><th>Total Sold:</th><td>${partner.total_sold.toLocaleString()} pcs</td></tr>
                                <tr><th>Total Returned:</th><td>${partner.total_returned.toLocaleString()} pcs</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Financial Performance</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-success mb-0">Rp ${partner.revenue.toLocaleString()}</h4>
                                        <small class="text-muted">Total Revenue</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-primary mb-0">${partner.avg_days_to_return}</h4>
                                        <small class="text-muted">Avg Days to Return</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-info mb-0">${(partner.revenue / partner.total_sold).toLocaleString()}</h4>
                                        <small class="text-muted">Revenue per Unit</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#partnerDetailContent').html(content);
    });

    // Initialize Charts
    initializeCharts();
});

function initializeCharts() {
    // Performance Trend Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Average Sell-Through Rate',
                data: [65, 68, 72, 71, 75, 78],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Grade Distribution Chart
    const gradeCtx = document.getElementById('gradeChart').getContext('2d');
    const gradeData = {
        'A+': {{ $partners->where('grade', 'A+')->count() }},
        'A': {{ $partners->where('grade', 'A')->count() }},
        'B+': {{ $partners->where('grade', 'B+')->count() }},
        'B': {{ $partners->where('grade', 'B')->count() }},
        'C+': {{ $partners->where('grade', 'C+')->count() }},
        'C': {{ $partners->where('grade', 'C')->count() }}
    };

    new Chart(gradeCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(gradeData),
            datasets: [{
                data: Object.values(gradeData),
                backgroundColor: [
                    '#28a745', '#007bff', '#17a2b8', 
                    '#ffc107', '#fd7e14', '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function viewHistory(tokoId) {
    // Implement history view functionality
    alert('View history for partner: ' + tokoId);
}

function sendAlert(tokoId) {
    // Implement alert sending functionality
    alert('Send alert to partner: ' + tokoId);
}

// Export functionality
$('#exportExcel').on('click', function() {
    // Implement Excel export
    alert('Exporting to Excel...');
});

$('#sendAlerts').on('click', function() {
    // Implement bulk alert sending
    alert('Sending alerts to underperforming partners...');
});
</script>
@endpush
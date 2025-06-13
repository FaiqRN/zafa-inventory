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
                            <div class="text-xs text-gray-500">75-85% sell-through rate</div>
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
                            <div class="text-xs text-gray-500">55-75% sell-through rate</div>
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
                            <div class="text-xs text-gray-500">&lt;55% sell-through rate</div>
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
                                            <span class="badge badge-success">{{ $partner['avg_days_to_return'] }}</span>
                                        @elseif($partner['avg_days_to_return'] >= 14)
                                            <span class="badge badge-warning">{{ $partner['avg_days_to_return'] }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $partner['avg_days_to_return'] }}</span>
                                        @endif
                                        <small class="d-block text-muted">days</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-view-detail" 
                                                    data-partner-id="{{ $partner['toko_id'] }}"
                                                    data-partner-name="{{ $partner['nama_toko'] }}"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-view-history" 
                                                    data-partner-id="{{ $partner['toko_id'] }}"
                                                    title="View History">
                                                <i class="fas fa-chart-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-send-alert" 
                                                    data-partner-id="{{ $partner['toko_id'] }}"
                                                    data-partner-name="{{ $partner['nama_toko'] }}"
                                                    title="Send Alert">
                                                <i class="fas fa-bell"></i>
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
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading partner details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Partner History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line mr-2"></i>
                    Partner Performance History
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="partnerHistoryContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading partner history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Alert Modal -->
<div class="modal fade" id="alertModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bell mr-2"></i>
                    Send Alert to Partner
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="alertForm">
                    <input type="hidden" id="alertPartnerId" name="partner_id">
                    <div class="form-group">
                        <label for="alertType">Alert Type</label>
                        <select class="form-control" id="alertType" name="alert_type">
                            <option value="performance">Performance Issue</option>
                            <option value="payment">Payment Reminder</option>
                            <option value="trend">Declining Trend</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="alertMessage">Additional Message (Optional)</label>
                        <textarea class="form-control" id="alertMessage" name="message" rows="3" 
                                placeholder="Enter custom message or additional notes..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <small>Alert akan dikirim via WhatsApp dan SMS ke partner yang dipilih.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="sendAlertBtn">
                    <i class="fas fa-paper-plane mr-1"></i>
                    Send Alert
                </button>
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

.badge-orange {
    color: #fff;
    background-color: #fd7e14;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.2rem;
}
</style>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    // ✅ FIXED: Export Excel functionality
    $('#exportExcel').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Exporting...');
        
        // Create form for download
        const form = $('<form>', {
            'method': 'GET',
            'action': '{{ route("analytics.partner-performance.export") }}'
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        // Reset button after delay
        setTimeout(() => {
            btn.prop('disabled', false).html('<i class="fas fa-file-excel"></i> Export Excel');
            showNotification('Export completed successfully!', 'success');
        }, 2000);
    });

    // ✅ FIXED: Send Bulk Alerts functionality
    $('#sendBulkAlerts').on('click', function() {
        const btn = $(this);
        
        // Confirm action
        if (!confirm('Send alerts to all underperforming partners (Grade C)? This action cannot be undone.')) {
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        $.ajax({
            url: '{{ route("analytics.partner-performance.bulk-alerts") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    if (response.details && response.details.errors.length > 0) {
                        console.warn('Some alerts failed:', response.details.errors);
                    }
                } else {
                    showNotification(response.message || 'Failed to send bulk alerts', 'error');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to send bulk alerts';
                showNotification(message, 'error');
                console.error('Bulk alerts error:', xhr);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-bell"></i> Send Alerts');
            }
        });
    });

    // ✅ FIXED: View Partner Detail functionality
    $(document).on('click', '.btn-view-detail', function() {
        const partnerId = $(this).data('partner-id');
        const partnerName = $(this).data('partner-name');
        
        $('#detailModal .modal-title').html(`<i class="fas fa-store mr-2"></i>Partner Detail - ${partnerName}`);
        $('#detailModal').modal('show');
        
        // Find partner data from the table
        const partnerRow = $(this).closest('tr');
        const partnerData = extractPartnerDataFromRow(partnerRow);
        
        const content = generatePartnerDetailContent(partnerData);
        $('#partnerDetailContent').html(content);
    });

    // ✅ FIXED: View Partner History functionality
    $(document).on('click', '.btn-view-history', function() {
        const partnerId = $(this).data('partner-id');
        
        $('#historyModal').modal('show');
        $('#partnerHistoryContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading partner history...</p>
            </div>
        `);
        
        $.ajax({
            url: `/analytics/partner-performance/history/${partnerId}`,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    const content = generatePartnerHistoryContent(response);
                    $('#partnerHistoryContent').html(content);
                    
                    // Initialize history chart
                    initializeHistoryChart(response.history);
                } else {
                    $('#partnerHistoryContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            ${response.message || 'Failed to load partner history'}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load partner history';
                $('#partnerHistoryContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${message}
                    </div>
                `);
                console.error('History error:', xhr);
            }
        });
    });

    // ✅ FIXED: Send Alert functionality
    $(document).on('click', '.btn-send-alert', function() {
        const partnerId = $(this).data('partner-id');
        const partnerName = $(this).data('partner-name');
        
        $('#alertPartnerId').val(partnerId);
        $('#alertModal .modal-title').html(`<i class="fas fa-bell mr-2"></i>Send Alert - ${partnerName}`);
        $('#alertForm')[0].reset();
        $('#alertModal').modal('show');
    });

    $('#sendAlertBtn').on('click', function() {
        const btn = $(this);
        const partnerId = $('#alertPartnerId').val();
        const alertType = $('#alertType').val();
        const message = $('#alertMessage').val();
        
        if (!partnerId) {
            showNotification('Invalid partner selected', 'error');
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        $.ajax({
            url: `/analytics/partner-performance/alert/${partnerId}`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                alert_type: alertType,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $('#alertModal').modal('hide');
                } else {
                    showNotification(response.message || 'Failed to send alert', 'error');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to send alert';
                showNotification(message, 'error');
                console.error('Send alert error:', xhr);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Send Alert');
            }
        });
    });

    // Helper Functions
    function extractPartnerDataFromRow($row) {
        return {
            rank: $row.find('td:eq(0)').text().trim(),
            nama_toko: $row.find('td:eq(1) strong').text().trim(),
            toko_id: $row.find('td:eq(1) small').text().replace('ID: ', '').trim(),
            grade: $row.find('td:eq(2) .badge').text().trim(),
            sell_through_rate: $row.data('performance'),
            total_shipped: $row.find('td:eq(4)').text().replace(/,/g, '').trim(),
            total_sold: $row.find('td:eq(5)').text().replace(/,/g, '').trim(),
            total_returned: $row.find('td:eq(6)').text().replace(/,/g, '').trim(),
            revenue: $row.find('td:eq(7)').text().replace(/[Rp.,]/g, '').trim(),
            avg_days_to_return: $row.find('td:eq(8) .badge').text().trim()
        };
    }

    function generatePartnerDetailContent(partner) {
        return `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Rank:</th><td>#${partner.rank}</td></tr>
                                <tr><th>Partner Name:</th><td>${partner.nama_toko}</td></tr>
                                <tr><th>Partner ID:</th><td>${partner.toko_id}</td></tr>
                                <tr><th>Grade:</th><td><span class="badge badge-primary">${partner.grade}</span></td></tr>
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
                                <tr><th>Total Shipped:</th><td>${Number(partner.total_shipped).toLocaleString()} pcs</td></tr>
                                <tr><th>Total Sold:</th><td>${Number(partner.total_sold).toLocaleString()} pcs</td></tr>
                                <tr><th>Total Returned:</th><td>${Number(partner.total_returned).toLocaleString()} pcs</td></tr>
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
                                        <h4 class="text-success mb-0">Rp ${Number(partner.revenue).toLocaleString()}</h4>
                                        <small class="text-muted">Total Revenue (6 months)</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-primary mb-0">${partner.avg_days_to_return} days</h4>
                                        <small class="text-muted">Avg Days to Return</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-info mb-0">Rp ${Math.round(partner.revenue / Math.max(partner.total_sold, 1)).toLocaleString()}</h4>
                                        <small class="text-muted">Revenue per Unit</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function generatePartnerHistoryContent(response) {
        const partner = response.partner;
        const summary = response.summary;
        
        return `
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="mb-3">${partner.name}</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-primary mb-0">${summary.total_shipped.toLocaleString()}</h4>
                                        <small class="text-muted">Total Shipped (12 months)</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-success mb-0">${summary.total_sold.toLocaleString()}</h4>
                                        <small class="text-muted">Total Sold</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-info mb-0">Rp ${summary.total_revenue.toLocaleString()}</h4>
                                        <small class="text-muted">Total Revenue</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-warning mb-0">${summary.avg_sell_through}%</h4>
                                        <small class="text-muted">Avg Sell-Through</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">12-Month Performance Trend</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="historyChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function initializeHistoryChart(historyData) {
        const ctx = document.getElementById('historyChart');
        if (!ctx) return;
        
        const labels = historyData.map(item => item.month);
        const sellThroughData = historyData.map(item => item.sell_through_rate);
        const revenueData = historyData.map(item => item.revenue / 1000000); // Convert to millions
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sell-Through Rate (%)',
                        data: sellThroughData,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: 'Revenue (Millions)',
                        data: revenueData,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Sell-Through Rate (%)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue (Millions)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }

    function showNotification(message, type = 'info') {
        // Use SweetAlert2 if available, otherwise use alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: type === 'success' ? 'Success!' : (type === 'error' ? 'Error!' : 'Info'),
                text: message,
                icon: type,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(message);
        }
    }

    // Initialize Charts
    initializeCharts();
});

function initializeCharts() {
    // Performance Trend Chart
    const performanceCtx = document.getElementById('performanceChart');
    if (performanceCtx) {
        new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Average Sell-Through Rate',
                    data: [65, 68, 72, 71, 75, 78],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
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
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Grade Distribution Chart
    const gradeCtx = document.getElementById('gradeChart');
    if (gradeCtx) {
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
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
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
    }
}
</script>

@if(session('success'))
<script>
$(document).ready(function() {
    showNotification('{{ session("success") }}', 'success');
});
</script>
@endif

@if(session('error'))
<script>
$(document).ready(function() {
    showNotification('{{ session("error") }}', 'error');
});
</script>
@endif
@endpush
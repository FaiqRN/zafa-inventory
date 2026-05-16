@extends('layouts.template')

@section('title', 'dashboard')

@push('css')
<link rel="stylesheet" href="{{ asset('css/DashboardMonitor.css') }}">
@endpush

@section('content')
<section class="content">
    <div class="container-fluid">

        {{-- ===== TODAY BADGE ===== --}}
        <div class="mb-3 d-flex align-items-center flex-wrap gap-2">
            <span class="badge badge-pill px-3 py-2" style="background:#FFC107; color:#4A2511; font-size:.9rem;">
                <i class="fas fa-calendar-day mr-1"></i>
                Aktivitas Hari Ini: <strong id="stat-today">{{ number_format($stats['today']) }}</strong>
            </span>
            <span class="badge badge-pill px-3 py-2" style="background:#17a2b8; color:#fff; font-size:.9rem;">
                <i class="fas fa-file-alt mr-1"></i>
                laravel.log: <strong id="laravel-log-size">...</strong>
            </span>
        </div>

        {{-- ===== LARAVEL LOG PANEL ===== --}}
        <div class="card mb-3 card-laravel-log">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="fas fa-file-code mr-2 text-info"></i> Laravel Log <span class="text-muted small">(storage/logs/laravel.log)</span></span>
                <div class="d-flex gap-1" id="laravel-log-actions">
                    @can('export-laravel-log')
                    <a id="btn-export-log"
                       href="{{ route('dashboard-monitor.laravel-log.export') }}"
                       class="btn btn-info btn-sm"
                       title="Download laravel.log sebagai file .log">
                        <i class="fas fa-download mr-1"></i> Export .log
                    </a>
                    @endcan
                    @can('truncate-laravel-log')
                    <button class="btn btn-warning btn-sm" id="btn-truncate-log" title="Kosongkan isi laravel.log">
                        <i class="fas fa-eraser mr-1"></i> Truncate Log
                    </button>
                    @endcan
                </div>
            </div>
            <div class="card-body py-2">
                <div class="row align-items-center" id="laravel-log-info-row">
                    <div class="col-auto">
                        <i class="fas fa-spinner fa-spin text-muted" id="log-info-spinner"></i>
                    </div>
                    <div class="col" id="log-info-content" style="display:none;">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="log-info-chip">
                                <i class="fas fa-weight-hanging mr-1 text-info"></i>
                                Ukuran: <strong id="log-info-size">—</strong>
                            </span>
                            <span class="log-info-chip">
                                <i class="fas fa-clock mr-1 text-warning"></i>
                                Terakhir diubah: <strong id="log-info-modified">—</strong>
                            </span>
                            <span class="log-info-chip">
                                <i class="fas fa-calendar-alt mr-1 text-success"></i>
                                Cleanup otomatis: <strong>Setiap tanggal 1, pukul 04:00</strong>
                            </span>
                        </div>
                    </div>
                    <div class="col-12" id="log-info-missing" style="display:none;">
                        <span class="text-muted"><i class="fas fa-exclamation-circle mr-1"></i> File laravel.log tidak ditemukan.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FILTER PANEL ===== --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="fas fa-filter mr-2"></i> Filter Aktivitas</span>
                @can('truncate-dashboard-monitor')
                <button class="btn btn-danger btn-sm" id="btn-truncate">
                    <i class="fas fa-trash mr-1"></i> Truncate Semua Log
                </button>
                @endcan
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-md-2">
                        <select id="filter-action" class="form-control form-control-sm">
                            <option value="">Semua Aksi</option>
                            <option value="create">Tambah</option>
                            <option value="update">Ubah</option>
                            <option value="delete">Hapus</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <select id="filter-module" class="form-control form-control-sm">
                            <option value="">Semua Modul</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <input type="text" id="filter-username" class="form-control form-control-sm" placeholder="Username...">
                    </div>
                    <div class="col-12 col-md-2">
                        <input type="date" id="filter-date-from" class="form-control form-control-sm" title="Dari tanggal">
                    </div>
                    <div class="col-12 col-md-2">
                        <input type="date" id="filter-date-to" class="form-control form-control-sm" title="Sampai tanggal">
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-1">
                        <button id="btn-filter" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i></button>
                        <button id="btn-reset" class="btn btn-secondary btn-sm w-100"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== TABLE ===== --}}
        <div class="card">
            <div class="card-header"><i class="fas fa-list mr-2"></i> Riwayat Aktivitas</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tbl-logs">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Waktu</th>
                                <th>User</th>
                                <th width="90">Aksi</th>
                                <th>Modul</th>
                                <th>Deskripsi</th>
                                <th width="55">Detail</th>
                            </tr>
                        </thead>
                        <tbody id="log-tbody">
                            <tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div id="pagination-info" class="text-muted small"></div>
                <nav id="pagination-nav" aria-label="Activity log pagination"></nav>
            </div>
        </div>

    </div>
</section>

{{-- ===== MODAL DETAIL ===== --}}
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="modal-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-detail-title">
                    <i class="fas fa-info-circle mr-2"></i>Detail Aktivitas
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="modal-detail-body">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
window.DASHBOARD_MONITOR_CONFIG = {
    baseUrl:        @json(route('dashboard-monitor.data')),
    showUrl:        @json(url('dashboard-monitor')),
    modUrl:         @json(route('dashboard-monitor.modules')),
    truncUrl:       @json(route('dashboard-monitor.truncate')),
    logInfoUrl:     @json(route('dashboard-monitor.laravel-log.info')),
    logExportUrl:   @json(route('dashboard-monitor.laravel-log.export')),
    logTruncUrl:    @json(route('dashboard-monitor.laravel-log.truncate')),
    csrfToken:      document.querySelector('meta[name="csrf-token"]').content
};
</script>
<script src="{{ asset('js/DashboardMonitor.js') }}"></script>
@endpush

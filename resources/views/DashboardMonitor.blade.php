@extends('layouts.template')

@section('title', 'dashboard')

@push('css')
<link rel="stylesheet" href="{{ asset('css/DashboardMonitor.css') }}?v=1.0.1">
@endpush

@section('content')
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

        {{-- ===== SQL IMPORT PANEL ===== --}}
        <div class="card mb-3 card-sql-import">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 card-header-sql-import"
                 id="sql-import-header" role="button" data-toggle="collapse" data-target="#sql-import-body" aria-expanded="false">
                <span>
                    <i class="fas fa-database mr-2 text-primary"></i> SQL Import
                    <span class="text-muted small ml-1">(Paste INSERT INTO statement)</span>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-pill badge-info sql-import-table-count" id="sql-import-table-count" title="Jumlah tabel yang diizinkan">
                        <i class="fas fa-table mr-1"></i><span id="sql-import-count-num">13</span> tabel
                    </span>
                    <i class="fas fa-chevron-down sql-import-chevron" id="sql-import-chevron"></i>
                </div>
            </div>
            <div class="collapse" id="sql-import-body">
                <div class="card-body">
                    {{-- Mode Selector --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label font-weight-bold mb-1">
                                <i class="fas fa-cog mr-1 text-muted"></i> Mode Import
                            </label>
                            <select class="form-control form-control-sm" id="sql-import-mode">
                                <option value="insert">INSERT — Tambah data baru saja</option>
                                <option value="upsert">UPSERT — Tambah atau update jika sudah ada</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-weight-bold mb-1">
                                <i class="fas fa-table mr-1 text-muted"></i> Tabel Diizinkan
                            </label>
                            <select class="form-control form-control-sm" id="sql-import-table-selector">
                                <option value="">— Pilih untuk lihat struktur —</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-outline-info btn-sm w-100" id="btn-show-columns" disabled>
                                <i class="fas fa-columns mr-1"></i> Lihat Kolom
                            </button>
                        </div>
                    </div>

                    {{-- Column Info Area --}}
                    <div class="sql-import-columns-area mb-3" id="sql-import-columns-area" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="font-weight-bold text-muted small">
                                <i class="fas fa-th-list mr-1"></i> Struktur Tabel: <strong id="sql-import-columns-table"></strong>
                            </span>
                            <button type="button" class="close" id="btn-close-columns" aria-label="Tutup">&times;</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped mb-0" id="sql-import-columns-table-body">
                                <thead>
                                    <tr>
                                        <th>Kolom</th><th>Tipe</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- SQL Textarea --}}
                    <div class="sql-import-editor-wrap">
                        <label class="form-label font-weight-bold mb-1">
                            <i class="fas fa-code mr-1 text-muted"></i> SQL Statement
                        </label>
                        <textarea class="form-control sql-import-textarea" id="sql-import-textarea"
                                  rows="10"
                                  placeholder="Paste INSERT INTO statement disini...&#10;&#10;Contoh:&#10;INSERT INTO `barang` (`barang_id`, `barang_kode`, `nama_barang`, ...) VALUES&#10;('BRG0000001', 'BRG1', 'Kering Kentang', ...);"
                                  spellcheck="false"></textarea>
                    </div>

                    {{-- SQL Preview Info --}}
                    <div class="sql-import-preview mt-2 mb-3" id="sql-import-preview" style="display:none;">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span class="sql-preview-chip" id="sql-preview-table">
                                <i class="fas fa-table mr-1"></i> Tabel: <strong>—</strong>
                            </span>
                            <span class="sql-preview-chip" id="sql-preview-rows">
                                <i class="fas fa-list-ol mr-1"></i> Baris: <strong>—</strong>
                            </span>
                            <span class="sql-preview-chip" id="sql-preview-status">
                                <i class="fas fa-check-circle mr-1"></i> <strong>—</strong>
                            </span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" id="btn-sql-import-execute" disabled>
                                <i class="fas fa-play mr-1"></i> Jalankan Import
                            </button>
                            <button class="btn btn-outline-secondary" id="btn-sql-import-clear">
                                <i class="fas fa-broom mr-1"></i> Bersihkan
                            </button>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-shield-alt mr-1 text-success"></i> FK check dinonaktifkan sementara saat import
                        </div>
                    </div>

                    {{-- Result Area --}}
                    <div class="sql-import-result mt-3" id="sql-import-result" style="display:none;"></div>
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
    showUrl:        @json(route('dashboard-monitor.show', ['id' => '__ID__'])),
    modUrl:         @json(route('dashboard-monitor.modules')),
    truncUrl:       @json(route('dashboard-monitor.truncate')),
    logInfoUrl:     @json(route('dashboard-monitor.laravel-log.info')),
    logExportUrl:   @json(route('dashboard-monitor.laravel-log.export')),
    logTruncUrl:    @json(route('dashboard-monitor.laravel-log.truncate')),
    sqlTablesUrl:   @json(route('dashboard-monitor.sql-import.tables')),
    sqlColumnsUrl:  @json(route('dashboard-monitor.sql-import.columns')),
    sqlExecuteUrl:  @json(route('dashboard-monitor.sql-import.execute')),
    csrfToken:      document.querySelector('meta[name="csrf-token"]').content
};
</script>
<script src="{{ asset('js/DashboardMonitor.js') }}?v=1.0.1"></script>
@endpush

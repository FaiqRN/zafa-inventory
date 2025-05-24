<!-- resources/views/laporan/pemesanan.blade.php -->
@extends('layouts.template')
@section('page_title', 'Laporan Pemesanan')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
<li class="breadcrumb-item"><a href="#">Laporan</a></li>
<li class="breadcrumb-item active">Laporan Pemesanan</li>
@endsection

@push('css')
<style>
    .nav-tabs .nav-link.active {
        font-weight: bold;
        border-bottom: 3px solid #007bff;
    }
    .note-textarea {
        min-height: 80px;
    }
    .period-text {
        font-weight: bold;
    }
    .card-info .card-header {
        background-color: #17a2b8;
        color: white;
    }
    .small-box .icon i {
        font-size: 50px;
        position: absolute;
        right: 15px;
        top: 15px;
        opacity: 0.3;
    }
    .total-row {
        font-weight: bold;
        background-color: #f8f9fa;
    }
    .total-cell {
        border-top: 2px solid #dee2e6 !important;
    }
    .chart-container {
        position: relative;
        height: 350px;
        margin-bottom: 20px;
    }
    
    /* FIX UTAMA UNTUK INFINITE SCROLLING */
    .modal {
        overflow: hidden !important;
    }
    
    .modal.show {
        overflow: hidden !important;
    }
    
    .modal-dialog {
        overflow: hidden;
    }
    
    .modal-dialog.modal-xl {
        max-width: 90%;
        margin: 30px auto;
        height: calc(100vh - 60px);
        display: flex;
        flex-direction: column;
    }
    
    .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    .modal-body {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 15px;
        max-height: none;
    }
    
    /* Prevent body scroll saat modal terbuka */
    body.modal-open {
        overflow: hidden !important;
        position: fixed !important;
        width: 100% !important;
        height: 100% !important;
        padding-right: 0 !important;
    }
    
    /* Chart container fixes */
    #detail-chart-container {
        position: relative;
        overflow: hidden;
        max-height: none;
    }
    
    #detail-chart-container canvas {
        max-width: 100% !important;
        max-height: 300px !important;
    }
    
    /* Disable scrolling pada chart container */
    #detail-chart-container * {
        overflow: visible !important;
    }
    
    /* DataTable fixes */
    .modal .dataTables_wrapper {
        overflow: visible;
    }
    
    .modal .dataTables_scrollBody {
        overflow: auto;
        max-height: 300px;
    }
    
    /* Button disabled state */
    .detail-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Laporan Pemesanan</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Periode</label>
                                <select id="periode" class="form-control">
                                    <option value="1_bulan">1 Bulan</option>
                                    <option value="6_bulan">6 Bulan</option>
                                    <option value="1_tahun">1 Tahun</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Bulan</label>
                                <select id="bulan" class="form-control">
                                    <!-- Opsi akan diisi oleh JavaScript berdasarkan periode -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tahun</label>
                                <select id="tahun" class="form-control">
                                    @for($i = date('Y') - 5; $i <= date('Y'); $i++)
                                        <option value="{{ $i }}" {{ date('Y') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button id="btn-filter" class="btn btn-primary form-control">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Menampilkan data periode: <strong id="periode-display"></strong>
                            </div>
                        </div>
                    </div>
                    
                    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="barang-tab" data-toggle="tab" href="#tab-barang" role="tab">
                                <i class="fas fa-box mr-1"></i> Per Barang
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="sumber-tab" data-toggle="tab" href="#tab-sumber" role="tab">
                                <i class="fas fa-store mr-1"></i> Per Sumber
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pemesan-tab" data-toggle="tab" href="#tab-pemesan" role="tab">
                                <i class="fas fa-user mr-1"></i> Per Pemesan
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="reportTabsContent">
                        <!-- Tab Barang -->
                        <div class="tab-pane fade show active" id="tab-barang" role="tabpanel">
                            <!-- Chart Visualisasi Barang -->
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i> Visualisasi Data Pemesanan Barang</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="barang-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="btn-group">
                                    <button type="button" id="refresh-barang" class="btn btn-info">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                    <button type="button" id="export-barang" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export CSV
                                    </button>
                                    <button type="button" id="print-barang" class="btn btn-primary">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table id="table-barang" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th class="text-right">Jumlah Pesanan</th>
                                            <th class="text-right">Total Unit</th>
                                            <th class="text-right">Total Pendapatan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan diisi oleh JavaScript -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <th>TOTAL</th>
                                            <th class="text-right" id="barang-total-pesanan">0</th>
                                            <th class="text-right" id="barang-total-unit">0</th>
                                            <th class="text-right" id="barang-total-pendapatan">Rp 0</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Tab Sumber -->
                        <div class="tab-pane fade" id="tab-sumber" role="tabpanel">
                            <!-- Chart Visualisasi Sumber -->
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i> Visualisasi Data Sumber Pemesanan</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="sumber-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="btn-group">
                                    <button type="button" id="refresh-sumber" class="btn btn-info">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                    <button type="button" id="export-sumber" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export CSV
                                    </button>
                                    <button type="button" id="print-sumber" class="btn btn-primary">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table id="table-sumber" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Sumber Pemesanan</th>
                                            <th class="text-right">Jumlah Pesanan</th>
                                            <th class="text-right">Total Unit</th>
                                            <th class="text-right">Total Pendapatan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan diisi oleh JavaScript -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <th>TOTAL</th>
                                            <th class="text-right" id="sumber-total-pesanan">0</th>
                                            <th class="text-right" id="sumber-total-unit">0</th>
                                            <th class="text-right" id="sumber-total-pendapatan">Rp 0</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Tab Pemesan -->
                        <div class="tab-pane fade" id="tab-pemesan" role="tabpanel">
                            <div class="mb-3">
                                <div class="btn-group">
                                    <button type="button" id="refresh-pemesan" class="btn btn-info">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                    <button type="button" id="export-pemesan" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export CSV
                                    </button>
                                    <button type="button" id="print-pemesan" class="btn btn-primary">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table id="table-pemesan" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nama Pemesan</th>
                                            <th class="text-right">Jumlah Pesanan</th>
                                            <th class="text-right">Total Unit</th>
                                            <th class="text-right">Total Pendapatan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan diisi oleh JavaScript -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <th>TOTAL</th>
                                            <th class="text-right" id="pemesan-total-pesanan">0</th>
                                            <th class="text-right" id="pemesan-total-unit">0</th>
                                            <th class="text-right" id="pemesan-total-pendapatan">Rp 0</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Catatan -->
<div class="modal fade" id="modal-catatan" tabindex="-1" role="dialog" aria-labelledby="modalCatatanTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCatatanTitle">Catatan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-catatan">
                    <input type="hidden" id="catatan-tipe">
                    <input type="hidden" id="catatan-id">
                    <div class="form-group">
                        <label for="catatan">Catatan:</label>
                        <textarea class="form-control note-textarea" id="catatan" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="save-catatan">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Detail -->
<!-- Modal for Detail - VERSI BARU -->
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="modalDetailTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailTitle">Detail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="btn-group">
                                <button type="button" id="export-detail" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel"></i> Export Detail
                                </button>
                                <button type="button" id="print-detail" class="btn btn-primary btn-sm">
                                    <i class="fas fa-print"></i> Print Detail
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Grafik Pemesanan</h6>
                                </div>
                                <div class="card-body" id="detail-chart-container" style="min-height: 350px;">
                                    <!-- Chart akan di-render di sini -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Daftar Pemesanan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table id="table-detail" class="table table-bordered table-striped table-sm">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>ID Pemesanan</th>
                                                    <th>Tanggal</th>
                                                    <th>Nama Barang</th>
                                                    <th>Nama Pemesan</th>
                                                    <th class="text-right">Jumlah</th>
                                                    <th class="text-right">Total</th>
                                                    <th>Sumber</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data akan diisi oleh JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<!-- Load Chart.js terlebih dahulu -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<!-- Kemudian load script custom -->
<script src="{{ asset('js/laporan-pemesanan.js') }}"></script>
@endpush
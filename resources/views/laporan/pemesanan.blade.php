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
                                    <option value="1" {{ date('n') == 1 ? 'selected' : '' }}>Januari</option>
                                    <option value="2" {{ date('n') == 2 ? 'selected' : '' }}>Februari</option>
                                    <option value="3" {{ date('n') == 3 ? 'selected' : '' }}>Maret</option>
                                    <option value="4" {{ date('n') == 4 ? 'selected' : '' }}>April</option>
                                    <option value="5" {{ date('n') == 5 ? 'selected' : '' }}>Mei</option>
                                    <option value="6" {{ date('n') == 6 ? 'selected' : '' }}>Juni</option>
                                    <option value="7" {{ date('n') == 7 ? 'selected' : '' }}>Juli</option>
                                    <option value="8" {{ date('n') == 8 ? 'selected' : '' }}>Agustus</option>
                                    <option value="9" {{ date('n') == 9 ? 'selected' : '' }}>September</option>
                                    <option value="10" {{ date('n') == 10 ? 'selected' : '' }}>Oktober</option>
                                    <option value="11" {{ date('n') == 11 ? 'selected' : '' }}>November</option>
                                    <option value="12" {{ date('n') == 12 ? 'selected' : '' }}>Desember</option>
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
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="modalDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailTitle">Detail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="btn-group">
                        <button type="button" id="export-detail" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Detail
                        </button>
                        <button type="button" id="print-detail" class="btn btn-primary">
                            <i class="fas fa-print"></i> Print Detail
                        </button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Grafik Pemesanan</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="detail-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Daftar Pemesanan</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="table-detail" class="table table-bordered table-striped">
                                        <thead>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/laporan-pemesanan.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
@endpush
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
    .badge-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .badge-diproses {
        background-color: #17a2b8;
        color: white;
    }
    .badge-dikirim {
        background-color: #007bff;
        color: white;
    }
    .badge-selesai {
        background-color: #28a745;
        color: white;
    }
    .badge-dibatalkan {
        background-color: #dc3545;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i> Laporan Pemesanan</h3>
                </div>
                <div class="card-body">
                    <!-- Filter Periode -->
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

                    <!-- Tampilkan periode aktif -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Menampilkan data periode: <strong id="periode-display"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Kotak Ringkasan -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="summary-total">0</h3>
                                    <p>Total Pemesanan</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="summary-nilai">Rp 0</h3>
                                    <p>Total Nilai Pemesanan</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="summary-selesai">0</h3>
                                    <p>Pemesanan Selesai</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="summary-cancel">0</h3>
                                    <p>Pemesanan Dibatalkan</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab untuk periode berbeda -->
                    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="bulan-1-tab" data-toggle="tab" href="#bulan-1" role="tab">
                                1 Bulan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="bulan-6-tab" data-toggle="tab" href="#bulan-6" role="tab">
                                6 Bulan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tahun-1-tab" data-toggle="tab" href="#tahun-1" role="tab">
                                1 Tahun
                            </a>
                        </li>
                    </ul>

                    <!-- Tabel data -->
                    <div class="tab-content mt-3" id="reportTabsContent">
                        <div class="tab-pane fade show active" id="bulan-1" role="tabpanel">
                            <div class="table-responsive">
                                <div id="export-btn-container-1" class="mb-3 btn-group"></div>
                                <table id="tabel-pemesanan-1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>ID Pemesanan</th>
                                            <th>Tanggal</th>
                                            <th>Nama Pemesan</th>
                                            <th>Barang</th>
                                            <th>Jumlah</th>
                                            <th>Total</th>
                                            <th>Sumber</th>
                                            <th>Pembayaran</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="bulan-6" role="tabpanel">
                            <div class="table-responsive">
                                <div id="export-btn-container-6" class="mb-3 btn-group"></div>
                                <table id="tabel-pemesanan-6" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>ID Pemesanan</th>
                                            <th>Tanggal</th>
                                            <th>Nama Pemesan</th>
                                            <th>Barang</th>
                                            <th>Jumlah</th>
                                            <th>Total</th>
                                            <th>Sumber</th>
                                            <th>Pembayaran</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="tahun-1" role="tabpanel">
                            <div class="table-responsive">
                                <div id="export-btn-container-tahun" class="mb-3 btn-group"></div>
                                <table id="tabel-pemesanan-tahun" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>ID Pemesanan</th>
                                            <th>Tanggal</th>
                                            <th>Nama Pemesan</th>
                                            <th>Barang</th>
                                            <th>Jumlah</th>
                                            <th>Total</th>
                                            <th>Sumber</th>
                                            <th>Pembayaran</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pemesanan -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Pemesanan: <span id="id-pemesanan"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle mr-1"></i> Informasi Pemesanan</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th width="40%">ID Pemesanan</th>
                                        <td id="detail-id"></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Pemesanan</th>
                                        <td id="detail-tanggal"></td>
                                    </tr>
                                    <tr>
                                        <th>Sumber Pemesanan</th>
                                        <td id="detail-sumber"></td>
                                    </tr>
                                    <tr>
                                        <th>Metode Pembayaran</th>
                                        <td id="detail-pembayaran"></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td id="detail-status"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-user mr-1"></i> Informasi Pemesan</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th width="40%">Nama</th>
                                        <td id="detail-nama"></td>
                                    </tr>
                                    <tr>
                                        <th>Alamat</th>
                                        <td id="detail-alamat"></td>
                                    </tr>
                                    <tr>
                                        <th>No. Telepon</th>
                                        <td id="detail-telp"></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td id="detail-email"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-shopping-basket mr-1"></i> Detail Barang</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-bordered mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID Barang</th>
                                            <th>Nama Barang</th>
                                            <th>Harga Satuan</th>
                                            <th>Jumlah</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td id="detail-barang-id"></td>
                                            <td id="detail-barang-nama"></td>
                                            <td id="detail-barang-harga"></td>
                                            <td id="detail-barang-jumlah"></td>
                                            <td id="detail-barang-total"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0"><i class="fas fa-sticky-note mr-1"></i> Catatan</h6>
                            </div>
                            <div class="card-body">
                                <div id="detail-catatan">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-print-detail">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <button type="button" class="btn btn-success mr-1" id="btn-export-detail-csv">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Catatan -->
<div class="modal fade" id="modalCatatan" tabindex="-1" role="dialog" aria-labelledby="modalCatatanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCatatanLabel">Catatan untuk <span id="id-pemesanan-catatan"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-catatan">
                    <input type="hidden" id="pemesanan_id" name="pemesanan_id">
                    <input type="hidden" id="catatan_periode" name="periode">
                    <input type="hidden" id="catatan_bulan" name="bulan">
                    <input type="hidden" id="catatan_tahun" name="tahun">
                    <div class="form-group">
                        <label for="catatan">Catatan:</label>
                        <textarea class="form-control note-textarea" id="catatan" name="catatan" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-catatan">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/laporan-pemesanan.js') }}"></script>
@endpush
<!-- resources/views/laporan/toko.blade.php -->
@extends('layouts.template')

@section('page_title', 'Laporan Per Toko')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
<li class="breadcrumb-item"><a href="#">Laporan</a></li>
<li class="breadcrumb-item active">Laporan Per Toko</li>
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
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-store mr-2"></i> Laporan Penjualan Per Toko</h3>
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

                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="summary-toko">0</h3>
                                    <p>Total Toko</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-store"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="summary-penjualan">Rp 0</h3>
                                    <p>Total Penjualan</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="summary-pengiriman">0</h3>
                                    <p>Total Barang Dikirim</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="summary-retur">0</h3>
                                    <p>Total Barang Retur</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-undo-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <div class="tab-content mt-3" id="reportTabsContent">
                        <div class="tab-pane fade show active" id="bulan-1" role="tabpanel">
                            <div class="table-responsive">
                                <div id="export-btn-container-1" class="mb-3"></div>
                                <div id="export-btn-container-1" class="mb-3 btn-group"></div>
                                <table id="tabel-laporan-1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Toko</th>
                                            <th>Pemilik</th>
                                            <th>Total Penjualan</th>
                                            <th>Total Barang Dikirim</th>
                                            <th>Total Barang Retur</th>
                                            <th>Catatan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="bulan-6" role="tabpanel">
                            <div class="table-responsive">
                                <div id="export-btn-container-6" class="mb-3"></div>
                                <div id="export-btn-container-6" class="mb-3 btn-group"></div>
                                <table id="tabel-laporan-6" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Toko</th>
                                            <th>Pemilik</th>
                                            <th>Total Penjualan</th>
                                            <th>Total Barang Dikirim</th>
<th>Total Barang Retur</th>
                                            <th>Catatan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tahun-1" role="tabpanel">
                            <div class="table-responsive">
                                <div id="export-btn-container-tahun" class="mb-3"></div>
                                <div id="export-btn-container-tahun" class="mb-3 btn-group"></div>
                                <table id="tabel-laporan-tahun" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Toko</th>
                                            <th>Pemilik</th>
                                            <th>Total Penjualan</th>
                                            <th>Total Barang Dikirim</th>
                                            <th>Total Barang Retur</th>
                                            <th>Catatan</th>
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

<!-- Modal for Catatan -->
<div class="modal fade" id="modalCatatan" tabindex="-1" role="dialog" aria-labelledby="modalCatatanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCatatanLabel">Catatan untuk <span id="nama-toko"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-catatan">
                    <input type="hidden" id="toko_id" name="toko_id">
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

<!-- Modal for Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <!-- Content akan diisi melalui JavaScript -->
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/laporan-toko.js') }}"></script>
@endpush
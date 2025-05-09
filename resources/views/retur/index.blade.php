@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter Data</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Toko</label>
                            <select class="form-control" id="filter_toko_id" name="filter_toko_id">
                                <option value="">Semua Toko</option>
                                @foreach($toko as $t)
                                    <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Barang</label>
                            <select class="form-control" id="filter_barang_id" name="filter_barang_id">
                                <option value="">Semua Barang</option>
                                @foreach($barang as $b)
                                    <option value="{{ $b->barang_id }}">{{ $b->nama_barang }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" class="form-control" id="filter_start_date" name="filter_start_date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Akhir</label>
                            <input type="date" class="form-control" id="filter_end_date" name="filter_end_date">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" id="btnFilter" class="btn btn-primary">Filter</button>
                        <button type="button" id="resetFilter" class="btn btn-secondary">Reset</button>
                        <div class="btn-group float-right">
                            <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-file-export"></i> Export Data
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item export-data" href="#" data-format="xlsx">
                                    <i class="fas fa-file-excel"></i> Export Excel (.xlsx)
                                </a>
                                <a class="dropdown-item export-data" href="#" data-format="csv">
                                    <i class="fas fa-file-csv"></i> Export CSV (.csv)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Retur Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Retur Barang</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambahRetur">
                    <i class="fas fa-plus"></i> Tambah Retur
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="alert-container"></div>
            
            <div class="table-responsive">
                <table id="table-retur" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pengiriman</th>
                            <th>Tanggal Pengiriman</th>
                            <th>Tanggal Retur</th>
                            <th>Toko</th>
                            <th>Barang</th>
                            <th>Jumlah Kirim</th>
                            <th>Jumlah Retur</th>
                            <th>Total Terjual</th>
                            <th>Harga Satuan</th>
                            <th>Total Hasil</th>
                            <th>Kondisi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan dimuat oleh AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Retur -->
<div class="modal fade" id="modalTambahRetur" tabindex="-1" role="dialog" aria-labelledby="modalTambahReturLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahReturLabel">Tambah Retur Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="step1">
                    <h5>Pilih Pengiriman</h5>
                    <div class="form-group">
                        <label>Filter Toko</label>
                        <select class="form-control" id="pengiriman_filter_toko">
                            <option value="">Semua Toko</option>
                            @foreach($toko as $t)
                                <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Filter Barang</label>
                        <select class="form-control" id="pengiriman_filter_barang">
                            <option value="">Semua Barang</option>
                            @foreach($barang as $b)
                                <option value="{{ $b->barang_id }}">{{ $b->nama_barang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="btnCariPengiriman">
                        <i class="fas fa-search"></i> Cari Pengiriman
                    </button>
                    
                    <div class="mt-3" id="pengiriman-list-container">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="table-pengiriman">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>No. Pengiriman</th>
                                        <th>Tanggal</th>
                                        <th>Toko</th>
                                        <th>Barang</th>
                                        <th>Jumlah Kirim</th>
                                        <th>Sudah Diretur</th>
                                        <th>Sisa</th>
                                        <th>Harga Satuan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data akan dimuat oleh AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div id="step2" style="display: none;">
                    <h5>Data Pengiriman</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>No. Pengiriman</label>
                                <input type="text" class="form-control" id="info_nomer_pengiriman" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Pengiriman</label>
                                <input type="text" class="form-control" id="info_tanggal_pengiriman" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Toko</label>
                                <input type="text" class="form-control" id="info_toko" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Barang</label>
                                <input type="text" class="form-control" id="info_barang" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Jumlah Kirim</label>
                                <input type="text" class="form-control" id="info_jumlah_kirim" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Sudah Diretur</label>
                                <input type="text" class="form-control" id="info_sudah_retur" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Sisa</label>
                                <input type="text" class="form-control" id="info_sisa" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Form Retur</h5>
                    <form id="formTambahRetur">
                        @csrf
                        <input type="hidden" id="pengiriman_id" name="pengiriman_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_retur">Tanggal Retur <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_retur" name="tanggal_retur" required>
                                    <div class="invalid-feedback" id="error-tanggal_retur"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jumlah_retur">Jumlah Retur <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="jumlah_retur" name="jumlah_retur" min="0" required>
                                    <small class="form-text text-muted">Masukkan 0 jika hanya ingin mencatat tanpa ada barang yang diretur.</small>
                                    <div class="invalid-feedback" id="error-jumlah_retur"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="kondisi">Kondisi Barang <span class="text-danger">*</span></label>
                            <select class="form-control" id="kondisi" name="kondisi" required>
                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Tidak Ada Retur">Tidak Ada Retur</option>
                                <option value="Rusak">Rusak</option>
                                <option value="Kadaluarsa">Kadaluarsa</option>
                                <option value="Cacat Produksi">Cacat Produksi</option>
                                <option value="Kemasan Rusak">Kemasan Rusak</option>
                                <option value="Tidak Laku">Tidak Laku</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                            <div class="invalid-feedback" id="error-kondisi"></div>
                        </div>
                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                            <div class="invalid-feedback" id="error-keterangan"></div>
                        </div>
                    </form>
                    
                    <button type="button" class="btn btn-secondary" id="btnBackToStep1">Kembali</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanRetur" style="display: none;">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Retur -->
<div class="modal fade" id="modalDetailRetur" tabindex="-1" role="dialog" aria-labelledby="modalDetailReturLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailReturLabel">Detail Retur Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>No. Pengiriman</label>
                            <input type="text" class="form-control" id="detail_nomer_pengiriman" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tanggal Pengiriman</label>
                            <input type="text" class="form-control" id="detail_tanggal_pengiriman" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tanggal Retur</label>
                            <input type="text" class="form-control" id="detail_tanggal_retur" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Toko</label>
                            <input type="text" class="form-control" id="detail_toko" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Barang</label>
                            <input type="text" class="form-control" id="detail_barang" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Harga Satuan</label>
                            <input type="text" class="form-control" id="detail_harga" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Jumlah Kirim</label>
                            <input type="text" class="form-control" id="detail_jumlah_kirim" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Jumlah Retur</label>
                            <input type="text" class="form-control" id="detail_jumlah_retur" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total Terjual</label>
                            <input type="text" class="form-control" id="detail_total_terjual" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Total Hasil</label>
                            <input type="text" class="form-control" id="detail_hasil" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Kondisi</label>
                            <input type="text" class="form-control" id="detail_kondisi" readonly>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea class="form-control" id="detail_keterangan" rows="3" readonly></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data retur ini?</p>
                <p id="delete-item-name" class="font-weight-bold"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{asset('adminlte/plugins/select2/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
<style>
    #pengiriman-list-container {
        max-height: 400px;
        overflow-y: auto;
    }
</style>
@endpush

@push('js')
<script src="{{asset('adminlte/plugins/select2/js/select2.full.min.js')}}"></script>
<script src="{{ asset('js/retur.js') }}?v={{ time() }}"></script>
@endpush
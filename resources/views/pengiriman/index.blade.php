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
                            <label>Status</label>
                            <select class="form-control" id="filter_status" name="filter_status">
                                <option value="">Semua Status</option>
                                <option value="proses">Proses</option>
                                <option value="terkirim">Terkirim</option>
                                <option value="batal">Batal</option>
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
                        <div class="btn-group">
                            <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-file-excel"></i> Export Data
                            </button>
                            <button type="button" id="export-excel" class="btn btn-success mr-2">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                            <button type="button" id="export-csv" class="btn btn-success">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Pengiriman Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Pengiriman Barang</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambahPengiriman">
                    <i class="fas fa-plus"></i> Tambah Pengiriman
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="alert-container"></div>
            
            <div class="table-responsive">
                <table id="table-pengiriman" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pengiriman</th>
                            <th>Tanggal</th>
                            <th>Toko</th>
                            <th>Barang</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan dimuat oleh AJAX -->
                    </tbody>
                </table>
            </div>
            <!-- Tambahkan container untuk pagination -->
                <div id="pagination-container" class="mt-3"></div>

                <!-- Tambahkan input hidden untuk current page -->
                <input type="hidden" id="current_page" value="1">
        </div>
    </div>
</div>

<!-- Modal Tambah Pengiriman -->
<div class="modal fade" id="modalTambahPengiriman" tabindex="-1" role="dialog" aria-labelledby="modalTambahPengirimanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahPengirimanLabel">Tambah Pengiriman Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formTambahPengiriman">
                <div class="modal-body">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nomer_pengiriman">Nomor Pengiriman</label>
                                <input type="text" class="form-control" id="nomer_pengiriman" name="nomer_pengiriman" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggal_pengiriman">Tanggal Pengiriman</label>
                                <input type="date" class="form-control" id="tanggal_pengiriman" name="tanggal_pengiriman" required>
                                <div class="invalid-feedback" id="error-tanggal_pengiriman"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="toko_id">Toko</label>
                                <select class="form-control" id="toko_id" name="toko_id" required>
                                    <option value="">-- Pilih Toko --</option>
                                    @foreach($toko as $t)
                                        <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-toko_id"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="barang_id">Barang</label>
                                <select class="form-control" id="barang_id" name="barang_id" required disabled>
                                    <option value="">-- Pilih Barang --</option>
                                    <!-- Diisi melalui AJAX saat toko dipilih -->
                                </select>
                                <div class="invalid-feedback" id="error-barang_id"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jumlah_kirim">Jumlah</label>
                                <input type="number" class="form-control" id="jumlah_kirim" name="jumlah_kirim" min="1" required>
                                <div class="invalid-feedback" id="error-jumlah_kirim"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="satuan">Satuan</label>
                                <input type="text" class="form-control" id="satuan" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" class="form-control" value="Proses" disabled>
                                <small class="text-muted">Status awal pengiriman otomatis diset ke 'Proses'</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanPengiriman">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pengiriman -->
<div class="modal fade" id="modalEditPengiriman" tabindex="-1" role="dialog" aria-labelledby="modalEditPengirimanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditPengirimanLabel">Edit Pengiriman Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditPengiriman">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="edit_pengiriman_id" name="pengiriman_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_nomer_pengiriman">Nomor Pengiriman</label>
                                <input type="text" class="form-control" id="edit_nomer_pengiriman" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_tanggal_pengiriman">Tanggal Pengiriman</label>
                                <input type="date" class="form-control" id="edit_tanggal_pengiriman" name="tanggal_pengiriman" required>
                                <div class="invalid-feedback" id="error-edit_tanggal_pengiriman"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_toko_nama">Toko</label>
                                <input type="text" class="form-control" id="edit_toko_nama" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_barang_nama">Barang</label>
                                <input type="text" class="form-control" id="edit_barang_nama" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_jumlah_kirim">Jumlah</label>
                                <input type="number" class="form-control" id="edit_jumlah_kirim" name="jumlah_kirim" min="1" required>
                                <div class="invalid-feedback" id="error-edit_jumlah_kirim"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="proses">Proses</option>
                                    <option value="terkirim">Terkirim</option>
                                    <option value="batal">Batal</option>
                                </select>
                                <div class="invalid-feedback" id="error-edit_status"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnUpdatePengiriman">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Update Status -->
<div class="modal fade" id="modalUpdateStatus" tabindex="-1" role="dialog" aria-labelledby="modalUpdateStatusLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUpdateStatusLabel">Update Status Pengiriman</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUpdateStatus">
                <div class="modal-body">
                    <input type="hidden" id="status_pengiriman_id" name="pengiriman_id">
                    <div class="form-group">
                        <label for="status_nomer_pengiriman">Nomor Pengiriman</label>
                        <input type="text" class="form-control" id="status_nomer_pengiriman" readonly>
                    </div>
                    <div class="form-group">
                        <label for="status_value">Status</label>
                        <select class="form-control" id="status_value" name="status" required>
                            <option value="proses">Proses</option>
                            <option value="terkirim">Terkirim</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
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
                <p>Apakah Anda yakin ingin menghapus data pengiriman ini?</p>
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
@endpush

@push('js')
<script src="{{asset('adminlte/plugins/select2/js/select2.full.min.js')}}"></script>
<script src="{{ asset('js/pengiriman.js') }}?v={{ time() }}"></script>
@endpush
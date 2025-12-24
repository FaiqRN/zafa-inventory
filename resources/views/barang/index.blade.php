@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div id="alert-container"></div>
    
    <div class="row">
        <!-- PANEL KIRI: DATA BARANG -->
        <div class="col-lg-5 col-md-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-boxes mr-2"></i> Data Barang
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="btnTambah">
                            <i class="fas fa-plus mr-1"></i> Tambah Barang
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-3">
                    <!-- Search Box -->
                    <div class="search-wrapper mb-3">
                        <label class="search-label">Cari :</label>
                        <input type="text" class="form-control form-control-sm" 
                               id="searchBarang" placeholder="Cari barang...">
                    </div>
                    
                    <!-- Tabel Data Barang -->
                    <div class="table-wrapper" style="overflow-x: auto;">
                        <table class="table table-hover table-sm mb-0" style="min-width: 900px;">
                            <thead>
                                <tr>
                                    <th style="min-width: 100px; width: 100px;">Kode</th>
                                    <th style="min-width: 200px; width: 250px;">Nama Barang</th>
                                    <th style="min-width: 130px; width: 140px;" class="text-right">Harga</th>
                                    <th style="min-width: 90px; width: 100px;" class="text-center">Satuan</th>
                                    <th style="min-width: 300px; width: 400px;">Keterangan</th>
                                    <th style="min-width: 100px; width: 100px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="barang-table-body">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination
                    <div class="pagination-wrapper mt-3">
                        <div class="row align-items-center">
                            <div class="col-sm-6">
                                <small class="text-muted" id="data-info">Data 1 - 5 dari 5</small>
                            </div>
                            <div class="col-sm-6">
                                <nav>
                                    <ul class="pagination pagination-sm justify-content-end mb-0">
                                        <li class="page-item disabled">
                                            <a class="page-link">Previous</a>
                                        </li>
                                        <li class="page-item active">
                                            <a class="page-link">1</a>
                                        </li>
                                        <li class="page-item disabled">
                                            <a class="page-link">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
        
        <!-- PANEL KANAN: DETAIL STOK BARANG -->
        <div class="col-lg-7 col-md-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title" id="detail-title">
                        <i class="fas fa-clipboard-list mr-2"></i> Detail Stok Barang
                    </h3>
                    <div class="card-tools" id="detail-action-buttons" style="display: none;">
                        <button type="button" class="btn btn-success btn-sm" id="btnTambahStok">
                            <i class="fas fa-plus mr-1"></i> Tambah Stok
                        </button>
                        <button type="button" class="btn btn-info btn-sm ml-1" id="btnRiwayatStok">
                            <i class="fas fa-history mr-1"></i> Riwayat Stok
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-3">
                    <!-- Empty State -->
                    <div id="detail-content" class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-hand-pointer"></i>
                        </div>
                        <p class="empty-state-text">Pilih data dari list untuk melihat detail</p>
                    </div>
                    
                    <!-- Content after selection -->
                    <div id="detail-table-container" style="display: none;">
                        <!-- Search Box -->
                        <div class="search-wrapper mb-3">
                            <label class="search-label">Cari :</label>
                            <input type="text" class="form-control form-control-sm" 
                                   id="searchStok" placeholder="Cari stok...">
                        </div>
                        
                        <!-- Tabel Stok -->
                        <div class="table-wrapper" style="overflow-x: auto;">
                            <table class="table table-hover table-sm mb-0" style="min-width: 400px;">
                                <thead>
                                    <tr>
                                        <th style="min-width: 200px; width: 250px;">Tanggal Produksi</th>
                                        <th style="min-width: 150px; width: 200px;" class="text-center">Jumlah Stok</th>
                                    </tr>
                                </thead>
                                <tbody id="stok-table-body">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination
                        <div class="pagination-wrapper mt-3">
                            <div class="row align-items-center">
                                <div class="col-sm-6">
                                    <small class="text-muted" id="stok-data-info">Data 1 - 5 dari 5</small>
                                </div>
                                <div class="col-sm-6">
                                    <nav>
                                        <ul class="pagination pagination-sm justify-content-end mb-0">
                                            <li class="page-item disabled">
                                                <a class="page-link">Previous</a>
                                            </li>
                                            <li class="page-item active">
                                                <a class="page-link">1</a>
                                            </li>
                                            <li class="page-item disabled">
                                                <a class="page-link">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Barang -->
<div class="modal fade" id="modalBarang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBarangLabel">
                    <i class="fas fa-box mr-2"></i>Tambah Barang
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formBarang">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="barang_id" name="barang_id">
                    
                    <div class="form-group">
                        <label>Kode Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="barang_kode" 
                               name="barang_kode" placeholder="Auto Generate" readonly required>
                        <div class="invalid-feedback" id="error-barang_kode"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_barang" 
                               name="nama_barang" placeholder="Masukkan nama barang" required>
                        <div class="invalid-feedback" id="error-nama_barang"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Harga <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="harga_awal_barang" 
                                           name="harga_awal_barang" placeholder="0" min="0" required>
                                </div>
                                <div class="invalid-feedback" id="error-harga_awal_barang"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Satuan <span class="text-danger">*</span></label>
                                <select class="form-control" id="satuan" name="satuan" required>
                                    <option value="">Pilih Satuan</option>
                                    <option value="Pcs">Pcs</option>
                                    <option value="Box">Box</option>
                                    <option value="Lusin">Lusin</option>
                                    <option value="Kg">Kg</option>
                                    <option value="Liter">Liter</option>
                                </select>
                                <div class="invalid-feedback" id="error-satuan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-0">
                        <label>Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" 
                                  rows="3" placeholder="Keterangan (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('css/barang.css') }}?v={{ time() }}">
@endpush

@push('js')
<script src="{{ asset('js/barang.js') }}?v={{ time() }}"></script>
@endpush
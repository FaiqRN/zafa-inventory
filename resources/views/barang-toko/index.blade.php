@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Pilih Toko Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pilih Toko</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="toko_select">Toko</label>
                <select class="form-control" id="toko_select">
                    <option value="">-- Pilih Toko --</option>
                    @foreach($toko as $t)
                        <option value="{{ $t->toko_id }}">{{ $t->nama_toko }} - {{ $t->alamat }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Daftar Barang per Toko Card -->
    <div class="card" id="barang-toko-card" style="display: none;">
        <div class="card-header">
            <h3 class="card-title">Daftar Barang di <span id="toko-name-display"></span></h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambahBarang">
                    <i class="fas fa-plus"></i> Tambah Barang
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="alert-container"></div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="table-barang-toko">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Kode Barang</th>
                            <th width="30%">Nama Barang</th>
                            <th width="15%">Harga Awal</th>
                            <th width="15%">Harga di Toko</th>
                            <th width="10%">Satuan</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="barang-toko-body">
                        <tr>
                            <td colspan="7" class="text-center">Belum ada data</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Barang ke Toko -->
<div class="modal fade" id="modalTambahBarang" tabindex="-1" role="dialog" aria-labelledby="modalTambahBarangLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahBarangLabel">Tambah Barang ke Toko</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formTambahBarang">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="selected_toko_id" name="toko_id">
                    
                    <div class="form-group">
                        <label for="barang_id">Barang</label>
                        <select class="form-control" id="barang_id" name="barang_id" required>
                            <option value="">-- Pilih Barang --</option>
                            <!-- Diisi melalui AJAX -->
                        </select>
                        <div class="invalid-feedback" id="error-barang_id"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga_barang_toko">Harga di Toko</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" class="form-control" id="harga_barang_toko" name="harga_barang_toko" min="0" step="0.01" required>
                        </div>
                        <div class="invalid-feedback" id="error-harga_barang_toko"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanBarang">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Harga Barang di Toko -->
<div class="modal fade" id="modalEditHarga" tabindex="-1" role="dialog" aria-labelledby="modalEditHargaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditHargaLabel">Edit Harga Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditHarga">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="edit_barang_toko_id" name="barang_toko_id">
                    
                    <div class="form-group">
                        <label for="edit_nama_barang">Nama Barang</label>
                        <input type="text" class="form-control" id="edit_nama_barang" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_harga_awal">Harga Awal</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" class="form-control" id="edit_harga_awal" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_harga_barang_toko">Harga di Toko</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" class="form-control" id="edit_harga_barang_toko" name="harga_barang_toko" min="0" step="0.01" required>
                        </div>
                        <div class="invalid-feedback" id="error-edit_harga_barang_toko"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnUpdateHarga">Simpan</button>
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
                <p>Apakah Anda yakin ingin menghapus barang ini dari toko?</p>
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

@push('js')
<script src="{{ asset('js/barang-toko.js') }}?v={{ time() }}"></script>
@endpush
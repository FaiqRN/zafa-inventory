@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Data Toko</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambah">
                    <i class="fas fa-plus"></i> Tambah Toko
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="alert-container"></div>
            
            <div class="table-responsive">
                <table id="table-toko" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">ID Toko</th>
                            <th width="15%">Nama Toko</th>
                            <th width="15%">Pemilik</th>
                            <th width="20%">Alamat</th>
                            <th width="15%">Wilayah</th>
                            <th width="10%">No. Telepon</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="toko-table-body">
                        <!-- Data akan dimuat oleh AJAX -->
                        <tr>
                            <td colspan="8" class="text-center">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Toko -->
<div class="modal fade" id="modalToko" tabindex="-1" role="dialog" aria-labelledby="modalTokoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTokoLabel">Tambah Toko</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formToko">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="mode" name="mode" value="add">
                    
                    <div class="form-group">
                        <label for="toko_id">ID Toko</label>
                        <input type="text" class="form-control" id="toko_id" name="toko_id" readonly>
                        <div class="invalid-feedback" id="error-toko_id"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama_toko">Nama Toko</label>
                                <input type="text" class="form-control" id="nama_toko" name="nama_toko" required>
                                <div class="invalid-feedback" id="error-nama_toko"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pemilik">Pemilik</label>
                                <input type="text" class="form-control" id="pemilik" name="pemilik" required>
                                <div class="invalid-feedback" id="error-pemilik"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                        <div class="invalid-feedback" id="error-alamat"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="wilayah_kota_id">Kota/Kabupaten</label>
                                <select class="form-control" id="wilayah_kota_id" required>
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                </select>
                                <input type="hidden" id="wilayah_kota_kabupaten" name="wilayah_kota_kabupaten">
                                <div class="invalid-feedback" id="error-wilayah_kota_kabupaten"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="wilayah_kecamatan_id">Kecamatan</label>
                                <select class="form-control" id="wilayah_kecamatan_id" required disabled>
                                    <option value="">-- Pilih Kecamatan --</option>
                                </select>
                                <input type="hidden" id="wilayah_kecamatan" name="wilayah_kecamatan">
                                <div class="invalid-feedback" id="error-wilayah_kecamatan"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="wilayah_kelurahan_id">Kelurahan</label>
                                <select class="form-control" id="wilayah_kelurahan_id" required disabled>
                                    <option value="">-- Pilih Kelurahan --</option>
                                </select>
                                <input type="hidden" id="wilayah_kelurahan" name="wilayah_kelurahan">
                                <div class="invalid-feedback" id="error-wilayah_kelurahan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nomer_telpon">Nomor Telepon</label>
                        <input type="text" class="form-control" id="nomer_telpon" name="nomer_telpon" required>
                        <div class="invalid-feedback" id="error-nomer_telpon"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
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
                <p>Apakah Anda yakin ingin menghapus toko ini?</p>
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
<script src="{{ asset('js/toko.js') }}?v={{ time() }}"></script>
@endpush
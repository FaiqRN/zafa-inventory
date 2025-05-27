@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Alert Container -->
    <div id="alert-container"></div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-store"></i> Data Toko dengan Enhanced Geocoding
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambah">
                    <i class="fas fa-plus"></i> Tambah Toko
                </button>
                <button type="button" class="btn btn-info" id="btnBatchGeocode" title="Batch Geocoding untuk semua toko">
                    <i class="fas fa-map-marker-alt"></i> Batch Geocoding
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <div id="alert-container"></div>
            
            <div class="table-responsive">
                <table id="table-toko" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th width="8%">ID Toko</th>
                            <th width="12%">Nama Toko</th>
                            <th width="12%">Pemilik</th>
                            <th width="18%">Alamat</th>
                            <th width="15%">Wilayah</th>
                            <th width="8%">No. Telepon</th>
                            <th width="8%">Koordinat</th>
                            <th width="6%">Aksi & Status</th>
                        </tr>
                    </thead>
                    <tbody id="toko-table-body">
                        <!-- Data akan dimuat oleh AJAX -->
                        <tr>
                            <td colspan="10" class="text-center">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Toko -->
<div class="modal fade" id="modalToko" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTokoLabel">Tambah Toko</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form id="formToko">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="mode" name="mode" value="add">
                    
                    <!-- ID Toko -->
                    <div class="form-group">
                        <label for="toko_id">
                            <i class="fas fa-barcode"></i> ID Toko
                        </label>
                        <input type="text" class="form-control" id="toko_id" name="toko_id" readonly>
                        <div class="invalid-feedback" id="error-toko_id"></div>
                    </div>
                    
                    <!-- Nama Toko & Pemilik -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama_toko">
                                    <i class="fas fa-store"></i> Nama Toko *
                                </label>
                                <input type="text" class="form-control" id="nama_toko" name="nama_toko" required>
                                <div class="invalid-feedback" id="error-nama_toko"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pemilik">
                                    <i class="fas fa-user"></i> Pemilik *
                                </label>
                                <input type="text" class="form-control" id="pemilik" name="pemilik" required>
                                <div class="invalid-feedback" id="error-pemilik"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alamat Detail -->
                    <div class="form-group">
                        <label for="alamat">
                            <i class="fas fa-map-marker-alt"></i> Alamat Detail *
                        </label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3" required 
                                  placeholder="Contoh: Jl. Veteran No. 12, RT/RW 02/05, Gang Mawar"></textarea>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Tips:</strong> Masukkan alamat sedetail mungkin untuk mendapatkan koordinat GPS yang akurat
                        </small>
                        <div class="invalid-feedback" id="error-alamat"></div>
                    </div>
                    
                    <!-- Wilayah -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="wilayah_kota_id">
                                    <i class="fas fa-city"></i> Kota/Kabupaten *
                                </label>
                                <select class="form-control" id="wilayah_kota_id" required>
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                </select>
                                <input type="hidden" id="wilayah_kota_kabupaten" name="wilayah_kota_kabupaten">
                                <div class="invalid-feedback" id="error-wilayah_kota_kabupaten"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="wilayah_kecamatan_id">
                                    <i class="fas fa-building"></i> Kecamatan *
                                </label>
                                <select class="form-control" id="wilayah_kecamatan_id" required disabled>
                                    <option value="">-- Pilih Kecamatan --</option>
                                </select>
                                <input type="hidden" id="wilayah_kecamatan" name="wilayah_kecamatan">
                                <div class="invalid-feedback" id="error-wilayah_kecamatan"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="wilayah_kelurahan_id">
                                    <i class="fas fa-home"></i> Kelurahan *
                                </label>
                                <select class="form-control" id="wilayah_kelurahan_id" required disabled>
                                    <option value="">-- Pilih Kelurahan --</option>
                                </select>
                                <input type="hidden" id="wilayah_kelurahan" name="wilayah_kelurahan">
                                <div class="invalid-feedback" id="error-wilayah_kelurahan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nomor Telepon -->
                    <div class="form-group">
                        <label for="nomer_telpon">
                            <i class="fas fa-phone"></i> Nomor Telepon *
                        </label>
                        <input type="text" class="form-control" id="nomer_telpon" name="nomer_telpon" required 
                               placeholder="Contoh: 0341-123456 atau 08123456789">
                        <div class="invalid-feedback" id="error-nomer_telpon"></div>
                    </div>

                    <!-- Enhanced Geocoding Preview -->
                    <div id="geocoding-info" class="alert alert-info" style="display: none;">
                        <h6>
                            <i class="fas fa-satellite-dish"></i> Enhanced Geocoding Preview
                        </h6>
                        <div id="geocoding-result"></div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Koordinat GPS akan digunakan untuk menampilkan lokasi toko di Market Map
                        </small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-warning" id="btnPreviewGeocode" style="display: none;">
                        <i class="fas fa-search-location"></i> Preview Lokasi
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning"></i> Konfirmasi Hapus
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus toko ini?</p>
                <div class="alert alert-warning">
                    <strong id="delete-item-name"></strong>
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Data yang dihapus tidak dapat dikembalikan
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-danger" id="btnDelete">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
<script src="{{ asset('js/toko.js') }}?v={{ time() }}"></script>
@endpush
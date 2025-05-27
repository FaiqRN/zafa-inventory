@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Alert Container -->
    <div id="alert-container"></div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-store"></i> Data Toko dengan Peta Interaktif
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

<!-- Modal Tambah/Edit Toko dengan Peta Interaktif -->
<div class="modal fade" id="modalToko" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document"> <!-- Changed to modal-xl for map space -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTokoLabel">
                    <i class="fas fa-store"></i> Tambah Toko
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form id="formToko">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="mode" name="mode" value="add">
                    <!-- Hidden fields for coordinates -->
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    
                    <div class="row">
                        <!-- Form Fields Column -->
                        <div class="col-md-6">
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
                                    Masukkan alamat detail, kemudian pilih lokasi presisi pada peta
                                </small>
                                <div class="invalid-feedback" id="error-alamat"></div>
                            </div>
                            
                            <!-- Wilayah -->
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
                            
                            <div class="row">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
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

                            <!-- Koordinat Display -->
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-crosshairs"></i> Koordinat GPS *
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="coordinate_display" readonly 
                                           placeholder="Pilih lokasi pada peta" style="background-color: #f8f9fa;">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="btnResetMap" title="Reset Peta">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-mouse-pointer"></i> 
                                    <strong>Klik pada peta</strong> untuk menentukan lokasi toko secara presisi
                                </small>
                                <div class="invalid-feedback" id="error-latitude"></div>
                                <div class="invalid-feedback" id="error-longitude"></div>
                            </div>
                        </div>
                        
                        <!-- Interactive Map Column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-map"></i> Peta Interaktif - Pilih Lokasi Toko *
                                </label>
                                <div class="map-container" style="border: 2px solid #dee2e6; border-radius: 0.375rem; overflow: hidden;">
                                    <div id="interactiveMap" style="height: 450px; width: 100%;"></div>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle text-primary"></i> 
                                                <strong>Petunjuk:</strong> Klik pada peta untuk menentukan lokasi
                                            </small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnCenterMalang">
                                                <i class="fas fa-bullseye"></i> Pusat Malang
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Map Status Info -->
                                    <div id="mapStatus" class="mt-2" style="display: none;">
                                        <div class="alert alert-info alert-sm mb-0">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-map-pin mr-2"></i>
                                                <div>
                                                    <strong>Lokasi Dipilih:</strong>
                                                    <div id="selectedLocationInfo" class="small"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-warning" id="btnValidateLocation" style="display: none;">
                        <i class="fas fa-check-circle"></i> Validasi Lokasi
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

@push('css')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
/* Custom Map Styles */
.map-container {
    position: relative;
}

.leaflet-container {
    font-family: inherit;
}

.leaflet-popup-content-wrapper {
    border-radius: 8px;
}

.leaflet-popup-content {
    margin: 8px 12px;
    line-height: 1.4;
}

/* Custom marker styles */
.custom-marker {
    background-color: #dc3545;
    border: 3px solid #fff;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

/* Map status alert */
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

/* Coordinate display styling */
#coordinate_display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #28a745;
}

/* Map loading indicator */
.map-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1000;
    background: rgba(255, 255, 255, 0.9);
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}
</style>
@endpush

@push('js')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script src="{{ asset('js/toko.js') }}?v={{ time() }}"></script>
@endpush
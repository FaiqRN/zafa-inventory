@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Alert Container -->
    <div id="alert-container"></div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-store"></i> Data Toko dengan Smart Address Indonesia
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
            
            <!-- Improved Table with Fixed Layout -->
            <div class="table-responsive">
                <table id="table-toko" class="table table-bordered table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="4%" class="text-center">No</th>
                            <th width="8%" class="text-center">ID Toko</th>
                            <th width="15%">Nama Toko & Pemilik</th>
                            <th width="20%">Alamat</th>
                            <th width="12%">Wilayah</th>
                            <th width="10%">No. Telepon</th>
                            <th width="12%">Koordinat GPS</th>
                            <th width="10%">Status</th>
                            <th width="9%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="toko-table-body">
                        <!-- Data akan dimuat oleh AJAX -->
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                                    <div>
                                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                        <div class="mt-2">Memuat data toko...</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Modal Tambah/Edit Toko dengan Kelurahan Auto-Zoom -->
<div class="modal fade" id="modalToko" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
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
                                <input type="text" class="form-control" id="toko_id" name="toko_id" readonly style="background-color: #f8f9fa;">
                                <div class="invalid-feedback" id="error-toko_id"></div>
                            </div>
                            
                            <!-- Nama Toko & Pemilik -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nama_toko">
                                            <i class="fas fa-store"></i> Nama Toko *
                                        </label>
                                        <input type="text" class="form-control" id="nama_toko" name="nama_toko" required 
                                               placeholder="Nama toko...">
                                        <div class="invalid-feedback" id="error-nama_toko"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pemilik">
                                            <i class="fas fa-user"></i> Pemilik *
                                        </label>
                                        <input type="text" class="form-control" id="pemilik" name="pemilik" required 
                                               placeholder="Nama pemilik...">
                                        <div class="invalid-feedback" id="error-pemilik"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Alamat Detail dengan Smart Indonesian Format Detection -->
                            <div class="form-group">
                                <label for="alamat">
                                    <i class="fas fa-map-marker-alt"></i> Alamat Detail *
                                </label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" required 
                                          placeholder="Contoh: Jl. Ahmad Yani Utara No. 200, Polowijen, Kec. Blimbing, Kota Malang, Jawa Timur 65126"></textarea>
                                <small class="form-text text-muted">
                                    <i class="fas fa-magic text-primary"></i> 
                                    <strong>Format Indonesia:</strong> Jl. [nama jalan] No. [nomor], [Kelurahan], Kec. [Kecamatan], Kota [Kota]
                                </small>
                                <div class="invalid-feedback" id="error-alamat"></div>
                                
                                <!-- Address Search Status dengan Design yang Lebih Baik -->
                                <div id="addressSearchStatus" class="mt-2" style="display: none;">
                                    <!-- Dynamic search status will be shown here -->
                                </div>
                            </div>
                            
                            <!-- Wilayah dengan Kelurahan Auto-Zoom Feature -->
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

                            <!-- Koordinat Display dengan Enhanced UI -->
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-crosshairs"></i> Koordinat GPS *
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-map-pin"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control font-weight-bold" id="coordinate_display" readonly 
                                           placeholder="Koordinat akan muncul setelah memilih di peta" 
                                           style="background-color: #f8f9fa; font-family: 'Courier New', monospace;">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="btnResetMap" title="Reset Peta">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text">
                                    <i class="fas fa-route text-info"></i> 
                                    <strong>Cara Mudah:</strong> 
                                    <span class="badge badge-light">1. Format Indonesia</span> → 
                                    <span class="badge badge-light">2. Auto-Detect Kelurahan</span> → 
                                    <span class="badge badge-light">3. Klik Lokasi Presisi</span>
                                </small>
                                <div class="invalid-feedback" id="error-latitude"></div>
                                <div class="invalid-feedback" id="error-longitude"></div>
                            </div>
                        </div>
                        
                        <!-- Interactive Map Column dengan Kelurahan Auto-Zoom -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="mb-3">
                                    <i class="fas fa-map"></i> Peta Interaktif
                                    <span class="badge badge-success ml-2">AUTO-DETECT!</span>
                                </label>
                                <div class="map-container" style="border: 2px solid #dee2e6; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                    <div id="interactiveMap" style="height: 450px; width: 100%;"></div>
                                </div>
                                <div class="mt-3">
                                    <!-- Enhanced Instructions -->
                                    <div class="card border-info">
                                        <div class="card-body p-3">
                                            <h6 class="card-title text-info mb-2">
                                                <i class="fas fa-lightbulb"></i> Cara Penggunaan Format Indonesia:
                                            </h6>
                                            <div class="row text-sm">
                                                <div class="col-12">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <span class="badge badge-primary mr-2">1</span>
                                                        <small><strong>Ketik format:</strong> Jl. [nama], [Kelurahan], Kec. [Kecamatan], Kota [Kota]</small>
                                                    </div>
                                                    <div class="d-flex align-items-center mb-1">
                                                        <span class="badge badge-warning mr-2">2</span>
                                                        <small><strong>Auto-deteksi:</strong> Sistem deteksi kelurahan → zoom otomatis</small>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge badge-success mr-2">3</span>
                                                        <small><strong>Klik presisi:</strong> Pilih lokasi exact di peta → koordinat tersimpan</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-2 p-2 bg-light rounded">
                                                <small class="text-muted">
                                                    <strong>Contoh:</strong> "Jl. Ahmad Yani No. 200, Polowijen, Kec. Blimbing, Kota Malang"
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnCenterMalang">
                                                <i class="fas fa-bullseye"></i> Pusat Malang
                                            </button>
                                        </div>
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-eye text-warning"></i> Marker kuning = preview | 
                                                <i class="fas fa-map-pin text-danger"></i> Marker merah = final
                                            </small>
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
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-warning" id="btnValidateLocation" style="display: none;">
                        <i class="fas fa-check-circle"></i> Validasi Lokasi
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">
                        <i class="fas fa-save"></i> Simpan Toko
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
/* Enhanced Table Styling */
#table-toko {
    font-size: 0.9rem;
}

#table-toko th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
}

#table-toko td {
    vertical-align: middle;
    padding: 0.6rem 0.5rem;
}

#table-toko .btn-group-sm > .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.8rem;
}

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

/* Enhanced marker styles */
.custom-marker {
    background-color: #dc3545;
    border: 3px solid #fff;
    border-radius: 50%;
    box-shadow: 0 3px 8px rgba(0,0,0,0.4);
}

/* Preview marker styles with enhanced animation */
.preview-marker {
    background-color: #ffc107;
    border: 3px solid #fff;
    border-radius: 50%;
    box-shadow: 0 3px 8px rgba(0,0,0,0.4);
}

/* Enhanced pulse animation for preview marker */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
    }
    70% {
        box-shadow: 0 0 0 15px rgba(255, 193, 7, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
    }
}

/* Map status alert styling */
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

/* Enhanced coordinate display styling */
#coordinate_display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #28a745;
    font-size: 0.95rem;
}

/* Address search status enhanced styling */
#addressSearchStatus {
    border-radius: 0.375rem;
}

#addressSearchStatus .alert {
    margin-bottom: 0;
    border-left: 4px solid;
}

#addressSearchStatus .alert-info {
    border-left-color: #17a2b8;
}

#addressSearchStatus .alert-success {
    border-left-color: #28a745;
}

#addressSearchStatus .alert-warning {
    border-left-color: #ffc107;
}

#addressSearchStatus .alert-primary {
    border-left-color: #007bff;
}

/* Enhanced form styling */
.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-text {
    font-size: 0.825rem;
    margin-top: 0.25rem;
}

/* Better visual feedback for required fields */
.form-group label::after {
    content: " *";
    color: #dc3545;
    font-weight: bold;
}

.form-group label[for="coordinate_display"]::after,
.form-group label[for="toko_id"]::after {
    content: "";
}

/* Enhanced badge styling */
.badge {
    font-size: 0.75rem;
    padding: 0.375em 0.6em;
}

/* Coordinate info styling in table */
.coordinate-info {
    max-width: 120px;
}

.coordinate-info small {
    display: block;
    line-height: 1.3;
}

/* Enhanced modal styling */
.modal-xl {
    max-width: 1200px;
}

.modal-header.bg-light {
    border-bottom: 1px solid #dee2e6;
}

.modal-footer.bg-light {
    border-top: 1px solid #dee2e6;
}

/* Enhanced card styling */
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

/* Loading animation enhancement */
.fa-spinner {
    animation: fa-spin 1s infinite linear;
}

/* Responsive table improvements */
@media (max-width: 768px) {
    #table-toko {
        font-size: 0.8rem;
    }
    
    .modal-xl {
        max-width: 95%;
    }
    
    .btn-group-sm > .btn {
        padding: 0.2rem 0.3rem;
        font-size: 0.7rem;
    }
}

/* Enhanced button styling */
.btn {
    border-radius: 0.375rem;
    font-weight: 500;
}

.btn-group-sm > .btn {
    margin: 0 1px;
}

/* Info card styling */
.card.border-info {
    border-width: 1px;
}

.card.border-info .card-body {
    background-color: #f8fdff;
}

/* Text utilities */
.text-nowrap {
    white-space: nowrap;
}

.font-weight-bold {
    font-weight: 600 !important;
}

/* Enhanced table hover effect */
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.075);
}

/* Status badge container */
.coordinate-info,
.btn-group {
    margin-bottom: 0;
}
</style>
@endpush

@push('js')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script src="{{ asset('js/toko.js') }}?v={{ time() }}"></script>
@endpush
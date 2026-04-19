@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Main Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                {{-- <i class="fas fa-store"></i> Data Toko --}}
            </h3>
            <div class="card-tools">
                @can('create-toko')
                <button type="button" class="btn btn-primary" id="btnTambah">
                    <i class="fas fa-plus"></i> Tambah Toko
                </button>
                @endcan
            </div>
        </div>
        
        <div class="card-body">
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
                            <th width="10%">Kualitas GPS</th>
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

<!-- Modal Tambah/Edit Toko -->
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
                                <input type="text" class="form-control" id="toko_id" name="toko_id" style="background-color: #f8f9fa;">
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
                            
                            <!-- Alamat Detail -->
                            <div class="form-group">
                                <label for="alamat">
                                    <i class="fas fa-map-marker-alt"></i> Alamat Detail *
                                    <small class="text-muted">(Ketik untuk mencari dengan autocomplete)</small>
                                </label>
                                <div style="position: relative;">
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required 
                                              placeholder="Ketik alamat untuk mencari... (min. 3 karakter)"></textarea>
                                    <!-- Suggestions dropdown will appear here -->
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-search text-primary"></i> 
                                    Ketik minimal 3 karakter untuk melihat saran alamat 
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
                                    <small class="text-muted">(Pilih dari autocomplete atau klik di peta)</small>
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
                                <div class="invalid-feedback" id="error-latitude"></div>
                                <div class="invalid-feedback" id="error-longitude"></div>
                            </div>
                        </div>
                        
                        <!-- Interactive Map Column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="mb-3">
                                    <i class="fas fa-map"></i> Peta Interaktif
                                </label>
                                <div class="map-container" style="border: 2px solid #dee2e6; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                    <div id="interactiveMap" style="height: 450px; width: 100%;">
                                        <!-- Loading indicator -->
                                        <div id="mapLoadingIndicator" style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa;">
                                            <div class="text-center">
                                                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                                                <div>Memuat peta...</div>
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
                    <button type="submit" class="btn btn-primary" id="btnSimpan">
                        <i class="fas fa-save"></i> Simpan Toko
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Koordinat -->
<div class="modal fade" id="coordinateDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-map-marked-alt"></i> Detail Koordinat GPS
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="coordinateDetailsLoading" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <p class="text-muted">Memuat detail koordinat...</p>
                </div>

                <!-- Content State -->
                <div id="coordinateDetailsContent" style="display: none;">
                    <!-- Toko Info -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-store"></i> Informasi Toko
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Nama Toko:</strong><br>
                                        <span id="detail-nama-toko" class="text-muted"></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Pemilik:</strong><br>
                                        <span id="detail-pemilik" class="text-muted"></span>
                                    </p>
                                </div>
                            </div>
                            <p class="mb-0">
                                <strong>Alamat:</strong><br>
                                <span id="detail-alamat" class="text-muted"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Coordinate Info -->
                    <div class="card border-success mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-crosshairs"></i> Koordinat GPS
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Latitude:</strong><br>
                                        <code id="detail-latitude" class="text-success"></code>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Longitude:</strong><br>
                                        <code id="detail-longitude" class="text-success"></code>
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="mb-0">
                                        <strong>Link Google Maps:</strong><br>
                                        <a id="detail-google-maps-link" href="#" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                            <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Geocoding Quality Info -->
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line"></i> Kualitas Geocoding
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Provider:</strong><br>
                                        <span id="detail-provider" class="badge badge-info"></span>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Accuracy:</strong><br>
                                        <span id="detail-accuracy" class="badge badge-secondary"></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Quality Score:</strong><br>
                                        <span id="detail-quality-score" class="badge badge-lg"></span>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Confidence:</strong><br>
                                        <span id="detail-confidence" class="badge badge-secondary"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Validation -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-map-marker-alt"></i> Validasi Lokasi
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Status Wilayah:</strong><br>
                                        <span id="detail-region-status"></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Jarak dari Pusat Malang:</strong><br>
                                        <span id="detail-distance" class="text-muted"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warning for Low Quality -->
                    <div id="detail-low-quality-warning" class="alert alert-warning" style="display: none;">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle"></i> Koordinat Perlu Diperbaiki
                        </h6>
                        <p class="mb-0">
                            Koordinat ini memiliki kualitas rendah. Disarankan untuk melakukan geocoding ulang atau memilih lokasi secara manual di peta.
                        </p>
                    </div>

                    <!-- Warning for Out of Region -->
                    <div id="detail-out-of-region-warning" class="alert alert-danger" style="display: none;">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-circle"></i> Koordinat Di Luar Wilayah Malang
                        </h6>
                        <p class="mb-0">
                            Koordinat ini berada di luar wilayah Malang Raya. Pastikan alamat dan koordinat sudah benar.
                        </p>
                    </div>
                </div>

                <!-- Error State -->
                <div id="coordinateDetailsError" class="alert alert-danger" style="display: none;">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-circle"></i> Gagal Memuat Data
                    </h6>
                    <p id="coordinateDetailsErrorMessage" class="mb-0"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <button type="button" class="btn btn-warning" id="btnFixCoordinates" style="display: none;">
                    <i class="fas fa-wrench"></i> Perbaiki Koordinat
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
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

/* Alamat column - allow text wrapping */
#table-toko td:nth-child(4) {
    white-space: normal;
    word-wrap: break-word;
    word-break: break-word;
    line-height: 1.4;
    max-width: 250px;
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

/* Custom marker icon */
.custom-marker-icon {
    background: transparent;
    border: none;
}

/* Enhanced coordinate display styling */
#coordinate_display {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #28a745;
    font-size: 0.95rem;
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

/* Coordinate Details Modal Styles */
#coordinateDetailsModal .modal-header {
    border-bottom: 2px solid #dee2e6;
}

#coordinateDetailsModal .card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#coordinateDetailsModal .card-header {
    font-weight: 600;
}

#coordinateDetailsModal code {
    font-size: 1.1rem;
    padding: 0.25rem 0.5rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}

#coordinateDetailsModal .badge-lg {
    font-size: 1rem;
    padding: 0.5rem 0.75rem;
}

#coordinateDetailsModal .alert-heading {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

/* Quality score badge colors */
.quality-excellent {
    background-color: #28a745;
    color: white;
}

.quality-good {
    background-color: #17a2b8;
    color: white;
}

.quality-fair {
    background-color: #ffc107;
    color: #212529;
}

.quality-poor {
    background-color: #dc3545;
    color: white;
}

/* ========================================
   SUGGESTIONS DROPDOWN (NEW)
   ======================================== */
.suggestions-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
}

.suggestion-item {
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.15s;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background: #f8f9fa;
}

.suggestion-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 3px;
}

.suggestion-address {
    font-size: 0.85rem;
    color: #6c757d;
    line-height: 1.4;
}

.suggestion-empty {
    padding: 16px 14px;
    text-align: center;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Pulse marker animation */
.pulse-marker {
    width: 20px;
    height: 20px;
    background: #4ade80;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 3px 8px rgba(0,0,0,0.4);
    position: relative;
    cursor: move; /* Show move cursor */
}

.pulse-marker::after {
    content: '';
    position: absolute;
    inset: -5px;
    border-radius: 50%;
    background: rgba(74, 222, 128, 0.35);
    animation: pulse-ring 1.6s ease-out infinite;
}

/* Draggable marker cursor */
.leaflet-marker-draggable {
    cursor: move !important;
}

.custom-marker-icon {
    cursor: move !important;
}

@keyframes pulse-ring {
    0% {
        transform: scale(1);
        opacity: 0.6;
    }
    100% {
        transform: scale(2.4);
        opacity: 0;
    }
}


</style>
@endpush

@push('js')
<!-- Debug: Check if Leaflet loaded -->
<script>
    if (typeof L !== 'undefined') {
        console.log('✅ Leaflet library loaded successfully, version:', L.version);
    } else {
        console.error('❌ Leaflet library failed to load!');
    }

    window.tokoPermissions = {
        canCreate: @json(auth()->check() && auth()->user()->can('create-toko')),
        canEdit: @json(auth()->check() && auth()->user()->can('edit-toko')),
        canDelete: @json(auth()->check() && auth()->user()->can('delete-toko'))
    };
</script>

<script src="{{ asset('js/toko-coordinate-details.js') }}?v={{ time() }}"></script>
<!-- NEW: Use simplified Nominatim version -->
<script src="{{ asset('js/toko-new.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/toko-coordinate-details-handler.js') }}?v={{ time() }}"></script>
@endpush
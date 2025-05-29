@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Enhanced Info Cards Row -->
    <div class="row mb-4 enhanced-info-cards">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="total-toko">0</h3>
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
                    <h3 id="toko-aktif">0</h3>
                    <p>Toko Aktif</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="total-kecamatan">0</h3>
                    <p>Kecamatan Tercakup</p>
                </div>
                <div class="icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="wilayah-potensi">0</h3>
                    <p>Wilayah Potensial</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Control Panel Row -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card enhanced-controls">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-layer-group mr-2"></i>
                        Kontrol Peta Enhanced - Grid Heatmap
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Filter Wilayah:</label>
                                <select class="form-control" id="filter-wilayah">
                                    <option value="all">Semua Wilayah</option>
                                    <option value="Kota Malang">Kota Malang</option>
                                    <option value="Kabupaten Malang">Kabupaten Malang</option>
                                    <option value="Kota Batu">Kota Batu</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Classic Heatmap:</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-heatmap">
                                    <label class="custom-control-label" for="toggle-heatmap">Point Heatmap</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Cluster Markers:</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-cluster" checked>
                                    <label class="custom-control-label" for="toggle-cluster">Cluster Aktif</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Koordinat Status:</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-coordinates">
                                    <label class="custom-control-label" for="toggle-coordinates">Show GPS/EST</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <div class="btn-group-vertical w-100">
                                    <button class="btn btn-primary btn-sm mb-1" id="btn-refresh-map">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                    <button class="btn btn-success btn-sm mb-1" id="btn-add-toko" data-toggle="modal" data-target="#addTokoModal">
                                        <i class="fas fa-plus"></i> Tambah Toko
                                    </button>
                                    <button class="btn btn-warning btn-sm" id="btn-bulk-geocode">
                                        <i class="fas fa-map-pin"></i> Bulk Geocode
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map and Analytics Row -->
    <div class="row">
        <!-- Enhanced Main Map -->
        <div class="col-lg-8">
            <div class="card enhanced-map-container">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-map-marked-alt mr-2"></i>
                        Enhanced Market Map - Grid Heatmap Density
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="maximize">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Loading Overlay -->
                    <div class="grid-loading-overlay" id="loading-overlay">
                        <div class="grid-loading-spinner"></div>
                        <div class="grid-loading-text">Generating Grid Heatmap...</div>
                    </div>
                    
                    <!-- Map Container -->
                    <div id="market-map" style="height: 600px; border-radius: 8px;"></div>
                </div>
            </div>
        </div>

        <!-- Enhanced Analytics Panel -->
        <div class="col-lg-4">
            <!-- Enhanced Legend Card -->
            <div class="card mb-3 enhanced-analytics">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-palette mr-2"></i>
                        Enhanced Legend
                    </h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-th mr-2"></i>Grid Heatmap Density</h6>
                        <div class="legend-items">
                            <div class="legend-item mb-2">
                                <span class="legend-marker" style="background-color: #dc143c;"></span>
                                <strong>Merah Pekat</strong>: Kepadatan Tinggi (5+ toko)
                            </div>
                            <div class="legend-item mb-2">
                                <span class="legend-marker" style="background-color: #ff8c00;"></span>
                                <strong>Oranye</strong>: Kepadatan Sedang (2-4 toko)
                            </div>
                            <div class="legend-item mb-2">
                                <span class="legend-marker" style="background-color: #ffd700;"></span>
                                <strong>Kuning Terang</strong>: Kepadatan Rendah (1 toko)
                            </div>
                            <div class="legend-item mb-2">
                                <span class="legend-marker" style="background-color: transparent; border: 1px solid #ccc;"></span>
                                <strong>Transparan</strong>: Tidak Ada Toko
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-map-pin mr-2"></i>Marker Status</h6>
                        <div class="legend-items">
                            <div class="legend-item mb-1">
                                <span class="legend-marker" style="background-color: #28a745;"></span>
                                <strong>Hijau</strong>: Toko Aktif
                            </div>
                            <div class="legend-item mb-1">
                                <span class="legend-marker" style="background-color: #dc3545;"></span>
                                <strong>Merah</strong>: Toko Tidak Aktif
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h6><i class="fas fa-satellite mr-2"></i>Koordinat Status</h6>
                        <div class="legend-items">
                            <div class="legend-item mb-1">
                                <span class="coordinate-status real">GPS</span>
                                <span class="ml-2">Koordinat GPS Akurat</span>
                            </div>
                            <div class="legend-item">
                                <span class="coordinate-status estimated">EST</span>
                                <span class="ml-2">Koordinat Estimasi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Statistics Card -->
            <div class="card mb-3 enhanced-analytics">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-2"></i>
                        Statistik Wilayah Enhanced
                    </h3>
                </div>
                <div class="card-body">
                    <div id="wilayah-stats" class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Wilayah</th>
                                    <th>Jumlah Toko</th>
                                    <th>GPS Status</th>
                                </tr>
                            </thead>
                            <tbody id="stats-tbody">
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Enhanced Recommendations -->
            <div class="card enhanced-analytics">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb mr-2"></i>
                        Rekomendasi Bisnis Enhanced
                    </h3>
                </div>
                <div class="card-body">
                    <div id="recommendations-content">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> 
                            Menganalisis data dengan grid heatmap...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Modal Tambah Toko -->
<div class="modal fade enhanced-modal" id="addTokoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-store-alt mr-2"></i>
                    Tambah Toko Baru
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="form-add-toko">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Enhanced Geocoding:</strong> Sistem akan mencari koordinat GPS secara otomatis untuk toko baru. 
                        Jika berhasil, toko akan muncul di grid heatmap dengan koordinat akurat.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Toko <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_toko" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Pemilik <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pemilik" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alamat" rows="3" required 
                                  placeholder="Contoh: Jl. Veteran No. 12, RT 02 RW 05"></textarea>
                        <small class="form-text text-muted">
                            Alamat yang detail akan menghasilkan koordinat GPS yang lebih akurat
                        </small>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kota/Kabupaten <span class="text-danger">*</span></label>
                                <select class="form-control" name="kota_kabupaten" id="select-kota" required>
                                    <option value="">Pilih Kota/Kabupaten</option>
                                    <option value="Kota Malang">Kota Malang</option>
                                    <option value="Kabupaten Malang">Kabupaten Malang</option>
                                    <option value="Kota Batu">Kota Batu</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kecamatan <span class="text-danger">*</span></label>
                                <select class="form-control" name="kecamatan" id="select-kecamatan" required>
                                    <option value="">Pilih Kecamatan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kelurahan <span class="text-danger">*</span></label>
                                <select class="form-control" name="kelurahan" id="select-kelurahan" required>
                                    <option value="">Pilih Kelurahan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nomor Telepon <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nomer_telpon" required 
                               placeholder="Contoh: 08123456789">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan & Auto Geocode
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enhanced Modal Detail Toko -->
<div class="modal fade enhanced-modal" id="tokoDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-store mr-2"></i>
                    Detail Toko - Enhanced View
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="toko-detail-content">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading enhanced view...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid Info Modal -->
<div class="modal fade enhanced-modal" id="gridInfoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-th mr-2"></i>
                    Grid Heatmap Information
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-info-circle mr-2"></i>Cara Kerja Grid Heatmap</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                <strong>Grid System:</strong> Peta dibagi menjadi grid berukuran 1.1km x 1.1km
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                <strong>Color Coding:</strong> Warna grid menunjukkan jumlah toko di area tersebut
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                <strong>Interactive:</strong> Klik grid untuk melihat detail toko di area tersebut
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                <strong>Real-time:</strong> Data diperbarui secara otomatis sesuai filter wilayah
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-palette mr-2"></i>Kode Warna Grid</h5>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div style="width: 30px; height: 20px; background-color: #dc143c; border: 1px solid #333; margin-right: 10px;"></div>
                                <span><strong>Merah Pekat:</strong> 5+ toko (Kepadatan Tinggi)</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div style="width: 30px; height: 20px; background-color: #ff8c00; border: 1px solid #333; margin-right: 10px;"></div>
                                <span><strong>Oranye:</strong> 2-4 toko (Kepadatan Sedang)</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div style="width: 30px; height: 20px; background-color: #ffd700; border: 1px solid #333; margin-right: 10px;"></div>
                                <span><strong>Kuning Terang:</strong> 1 toko (Kepadatan Rendah)</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div style="width: 30px; height: 20px; background-color: transparent; border: 1px solid #333; margin-right: 10px;"></div>
                                <span><strong>Transparan:</strong> Tidak ada toko</span>
                            </div>
                        </div>
                        
                        <h6><i class="fas fa-lightbulb mr-2"></i>Tips Penggunaan</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-1">• Gunakan filter wilayah untuk fokus pada area tertentu</li>
                            <li class="mb-1">• Kombinasikan dengan marker cluster untuk analisis detail</li>
                            <li class="mb-1">• Grid kosong menunjukkan peluang ekspansi</li>
                            <li class="mb-1">• Area merah menunjukkan persaingan tinggi</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fas fa-check"></i> Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

@push('css')
<style>
.legend-marker {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-right: 8px;
    border: 2px solid #333;
}

.leaflet-popup-content {
    max-width: 300px !important;
}

.popup-header {
    background: linear-gradient(135deg, #309898 0%, #00235B 100%);
    color: white;
    padding: 10px;
    margin: -10px -10px 10px -10px;
    border-radius: 5px 5px 0 0;
}

.popup-stats {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
}

.popup-stat {
    text-align: center;
    flex: 1;
}

.popup-stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #309898;
}

.popup-stat-label {
    font-size: 12px;
    color: #666;
}

.small-box {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.small-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

#market-map {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.market-cluster {
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid #309898;
    border-radius: 50%;
    text-align: center;
    color: #309898;
    font-weight: bold;
}

.marker-toko-aktif {
    background-color: #28a745;
    border: 3px solid #fff;
    border-radius: 50%;
    width: 15px;
    height: 15px;
}

.marker-toko-tidak-aktif {
    background-color: #dc3545;
    border: 3px solid #fff;
    border-radius: 50%;
    width: 15px;
    height: 15px;
}

/* Enhanced coordinate status */
.coordinate-status {
    font-size: 10px;
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
}

.coordinate-status.real {
    background: #28a745;
    color: white;
}

.coordinate-status.estimated {
    background: #ffc107;
    color: #333;
}

/* Enhanced loading overlay */
.grid-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(2px);
}

.grid-loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #309898;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 15px;
}

.grid-loading-text {
    color: #309898;
    font-weight: 500;
    font-size: 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced info alert */
.alert-info {
    border-left: 4px solid #309898;
    background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
}

/* Enhanced modal styling */
.enhanced-modal .modal-header {
    background: linear-gradient(135deg, #309898 0%, #00235B 100%);
    color: white;
}

.enhanced-modal .modal-header .close {
    color: white;
    opacity: 1;
}

.enhanced-modal .modal-header .close:hover {
    opacity: 0.8;
}

/* Enhanced card styling */
.enhanced-analytics {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.enhanced-analytics .card-header {
    background: linear-gradient(135deg, #309898 0%, #267373 100%);
    color: white;
    font-weight: 600;
}

/* Enhanced controls */
.enhanced-controls {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid #309898;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.enhanced-controls .card-header {
    background: linear-gradient(135deg, #309898 0%, #00235B 100%);
    color: white;
    font-weight: 600;
}

/* Enhanced map container */
.enhanced-map-container {
    border: 3px solid #309898;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.enhanced-map-container .card-header {
    background: linear-gradient(135deg, #309898 0%, #00235B 100%);
    color: white;
    font-weight: 600;
}

/* Enhanced button group */
.btn-group-vertical .btn {
    margin-bottom: 2px;
}

.btn-group-vertical .btn:last-child {
    margin-bottom: 0;
}

/* Responsive enhancements */
@media (max-width: 768px) {
    .enhanced-controls .row > .col-md-2 {
        margin-bottom: 15px;
    }
    
    .grid-loading-text {
        font-size: 14px;
    }
    
    .enhanced-map-container {
        border-width: 2px;
        border-radius: 8px;
    }
    
    .enhanced-info-cards .small-box {
        margin-bottom: 15px;
    }
}

@media (max-width: 576px) {
    .enhanced-controls {
        border-width: 1px;
    }
    
    .enhanced-map-container {
        border-width: 1px;
        border-radius: 6px;
    }
    
    .popup-stats {
        flex-direction: column;
        gap: 5px;
    }
    
    .popup-stat {
        display: flex;
        justify-content: space-between;
        padding: 2px 0;
    }
}
</style>

<!-- Include enhanced grid heatmap CSS -->
<link rel="stylesheet" href="{{ asset('css/enhanced-market-map.css') }}">
@endpush

@push('js')
<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Leaflet plugins for enhanced functionality -->
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<!-- Leaflet Heat (for classic point heatmap) -->
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<!-- Enhanced Market Map Script -->
<script src="{{ asset('js/enhanced-market-map.js') }}"></script>

<script>
// Additional initialization and helper functions
document.addEventListener('DOMContentLoaded', function() {
    // Add grid info button to map container
    setTimeout(function() {
        if (window.enhancedMarketMapInstance) {
            const mapContainer = document.querySelector('#market-map');
            if (mapContainer) {
                // Add floating info button
                const infoButton = document.createElement('div');
                infoButton.className = 'grid-info-button';
                infoButton.style.cssText = `
                    position: absolute;
                    top: 80px;
                    left: 10px;
                    z-index: 1000;
                    background: rgba(48, 152, 152, 0.9);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 12px;
                    font-weight: 600;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                    backdrop-filter: blur(10px);
                    transition: all 0.3s ease;
                `;
                infoButton.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Grid Info';
                infoButton.onclick = function() {
                    $('#gridInfoModal').modal('show');
                };
                
                // Hover effect
                infoButton.onmouseover = function() {
                    this.style.background = 'rgba(38, 115, 115, 0.9)';
                    this.style.transform = 'translateY(-2px)';
                };
                infoButton.onmouseout = function() {
                    this.style.background = 'rgba(48, 152, 152, 0.9)';
                    this.style.transform = 'translateY(0)';
                };
                
                mapContainer.appendChild(infoButton);
            }
        }
    }, 1000);
    
    // Enhanced form validation
    const formAddToko = document.getElementById('form-add-toko');
    if (formAddToko) {
        formAddToko.addEventListener('submit', function(e) {
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Reset after 5 seconds (fallback)
            setTimeout(function() {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    }
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        if (window.enhancedMarketMapInstance) {
            console.log('Auto-refreshing market map data...');
            window.enhancedMarketMapInstance.loadData();
        }
    }, 300000); // 5 minutes
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'r':
                    e.preventDefault();
                    document.getElementById('btn-refresh-map')?.click();
                    break;
                case 't':
                    e.preventDefault();
                    document.getElementById('btn-add-toko')?.click();
                    break;
                case 'g':
                    e.preventDefault();
                    document.getElementById('toggle-grid-heatmap')?.click();
                    break;
                case 'h':
                    e.preventDefault();
                    document.getElementById('toggle-heatmap')?.click();
                    break;
                case 'c':
                    e.preventDefault();
                    document.getElementById('toggle-cluster')?.click();
                    break;
            }
        }
    });
    
    console.log('Enhanced Market Map initialized with grid heatmap functionality');
});

// Global helper functions
window.marketMapHelpers = {
    showGridInfo: function() {
        $('#gridInfoModal').modal('show');
    },
    
    exportMapView: function() {
        if (window.enhancedMarketMapInstance && window.enhancedMarketMapInstance.map) {
            // Simple export functionality
            const map = window.enhancedMarketMapInstance.map;
            const bounds = map.getBounds();
            const center = map.getCenter();
            const zoom = map.getZoom();
            
            const exportData = {
                bounds: bounds,
                center: center,
                zoom: zoom,
                timestamp: new Date().toISOString(),
                gridEnabled: window.enhancedMarketMapInstance.isGridHeatmapEnabled,
                totalToko: window.enhancedMarketMapInstance.tokoData.length
            };
            
            console.log('Map Export Data:', exportData);
            
            // You can extend this to actually export/save the data
            Swal.fire({
                icon: 'info',
                title: 'Export Map View',
                text: 'Map view data has been logged to console. Extend this function for actual export.',
                timer: 3000
            });
        }
    }
};
</script>
@endpush
@endsection
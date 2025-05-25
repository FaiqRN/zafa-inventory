@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Info Cards Row -->
    <div class="row mb-4">
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

    <!-- Control Panel Row -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-layer-group mr-2"></i>
                        Kontrol Peta
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tampilan Layer:</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-heatmap" checked>
                                    <label class="custom-control-label" for="toggle-heatmap">Heatmap Density</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Cluster Markers:</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-cluster" checked>
                                    <label class="custom-control-label" for="toggle-cluster">Cluster Aktif</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button class="btn btn-primary btn-sm" id="btn-refresh-map">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                                <button class="btn btn-success btn-sm" id="btn-add-toko" data-toggle="modal" data-target="#addTokoModal">
                                    <i class="fas fa-plus"></i> Tambah Toko
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map and Analytics Row -->
    <div class="row">
        <!-- Main Map -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-map-marked-alt mr-2"></i>
                        Peta Persebaran Toko - Wilayah Malang Raya
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="maximize">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="market-map" style="height: 600px; border-radius: 8px;"></div>
                </div>
            </div>
        </div>

        <!-- Analytics Panel -->
        <div class="col-lg-4">
            <!-- Legend Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        Legenda Peta
                    </h3>
                </div>
                <div class="card-body">
                    <div class="legend-item mb-2">
                        <span class="legend-marker" style="background-color: #ff0000;"></span>
                        <strong>Merah</strong>: Kepadatan Tinggi (5+ toko)
                    </div>
                    <div class="legend-item mb-2">
                        <span class="legend-marker" style="background-color: #ff8c00;"></span>
                        <strong>Oranye</strong>: Kepadatan Sedang (2-4 toko)
                    </div>
                    <div class="legend-item mb-2">
                        <span class="legend-marker" style="background-color: #ffd700;"></span>
                        <strong>Kuning</strong>: Kepadatan Rendah (1 toko)
                    </div>
                    <div class="legend-item">
                        <span class="legend-marker" style="background-color: #00ff00;"></span>
                        <strong>Hijau</strong>: Toko Aktif dengan Stok
                    </div>
                </div>
            </div>

            <!-- Statistik Wilayah -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-2"></i>
                        Statistik Wilayah
                    </h3>
                </div>
                <div class="card-body">
                    <div id="wilayah-stats" class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Wilayah</th>
                                    <th>Jumlah Toko</th>
                                </tr>
                            </thead>
                            <tbody id="stats-tbody">
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rekomendasi -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb mr-2"></i>
                        Rekomendasi Bisnis
                    </h3>
                </div>
                <div class="card-body">
                    <div id="recommendations-content">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> 
                            Menganalisis data...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Toko -->
<div class="modal fade" id="addTokoModal" tabindex="-1" role="dialog">
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
                        <textarea class="form-control" name="alamat" rows="3" required></textarea>
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
                        <input type="text" class="form-control" name="nomer_telpon" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Toko -->
<div class="modal fade" id="tokoDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-store mr-2"></i>
                    Detail Toko
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="toko-detail-content">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
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
    border-radius: 50%;
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
</style>
@endpush

@push('js')
<script src="{{ asset('js/market-map.js') }}"></script>
@endpush
@endsection
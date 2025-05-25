/**
 * Market Map JavaScript - Leaflet Implementation
 * Menampilkan peta persebaran toko dengan visualisasi heatmap dan clustering
 */

class MarketMap {
    constructor() {
        this.map = null;
        this.tokoData = [];
        this.wilayahData = [];
        this.markerCluster = null;
        this.heatmapLayer = null;
        this.markersLayer = null;
        this.isClusterEnabled = true;
        this.isHeatmapEnabled = true;
        
        this.init();
    }

    init() {
        this.initMap();
        this.setupEventListeners();
        this.loadData();
    }

    initMap() {
        // Inisialisasi peta dengan center di Malang
        this.map = L.map('market-map').setView([-7.9666, 112.6326], 11);

        // Base tile layer - OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(this.map);

        // Inisialisasi marker cluster
        this.markerCluster = L.markerClusterGroup({
            iconCreateFunction: (cluster) => {
                const count = cluster.getChildCount();
                let size = 'small';
                let color = '#ffd700'; // kuning default
                
                if (count >= 10) {
                    size = 'large';
                    color = '#ff0000'; // merah untuk density tinggi
                } else if (count >= 5) {
                    size = 'medium';
                    color = '#ff8c00'; // oranye untuk density sedang
                }

                return L.divIcon({
                    html: `<div style="background-color: ${color}; color: white; border-radius: 50%; 
                           width: 40px; height: 40px; display: flex; align-items: center; 
                           justify-content: center; font-weight: bold; border: 3px solid white;
                           box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${count}</div>`,
                    className: 'market-cluster',
                    iconSize: [40, 40]
                });
            },
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 50
        });

        // Layer untuk markers individual
        this.markersLayer = L.layerGroup();
        
        // Add layers to map
        this.map.addLayer(this.markerCluster);
    }

    setupEventListeners() {
        // Filter wilayah
        document.getElementById('filter-wilayah').addEventListener('change', (e) => {
            this.filterByWilayah(e.target.value);
        });

        // Toggle heatmap
        document.getElementById('toggle-heatmap').addEventListener('change', (e) => {
            this.toggleHeatmap(e.target.checked);
        });

        // Toggle cluster
        document.getElementById('toggle-cluster').addEventListener('change', (e) => {
            this.toggleCluster(e.target.checked);
        });

        // Refresh map
        document.getElementById('btn-refresh-map').addEventListener('click', () => {
            this.loadData();
        });

        // Form tambah toko
        document.getElementById('form-add-toko').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleAddToko(e);
        });

        // Dropdown wilayah untuk form
        document.getElementById('select-kota').addEventListener('change', (e) => {
            this.loadKecamatan(e.target.value);
        });

        document.getElementById('select-kecamatan').addEventListener('change', (e) => {
            this.loadKelurahan(e.target.value);
        });
    }

    async loadData() {
        try {
            // Show loading
            this.showLoading(true);

            // Load toko data
            const tokoResponse = await fetch('/market-map/toko-data');
            const tokoResult = await tokoResponse.json();
            
            if (tokoResult.success) {
                this.tokoData = tokoResult.data;
                this.renderMarkers();
                this.updateStatistics();
            }

            // Load wilayah data
            const wilayahResponse = await fetch('/market-map/wilayah-data');
            const wilayahResult = await wilayahResponse.json();
            
            if (wilayahResult.success) {
                this.wilayahData = wilayahResult.data;
            }

            // Load recommendations
            await this.loadRecommendations();
            
            this.showLoading(false);
        } catch (error) {
            console.error('Error loading data:', error);
            this.showError('Gagal memuat data peta');
            this.showLoading(false);
        }
    }

    renderMarkers() {
        // Clear existing markers
        this.markerCluster.clearLayers();
        this.markersLayer.clearLayers();

        // Create markers for each toko
        this.tokoData.forEach(toko => {
            const marker = this.createTokoMarker(toko);
            
            if (this.isClusterEnabled) {
                this.markerCluster.addLayer(marker);
            } else {
                this.markersLayer.addLayer(marker);
            }
        });

        // Update heatmap
        if (this.isHeatmapEnabled) {
            this.updateHeatmap();
        }
    }

    createTokoMarker(toko) {
        // Pilih icon berdasarkan status toko
        const isAktif = toko.status_aktif === 'Aktif';
        const iconColor = isAktif ? '#28a745' : '#dc3545';
        const iconClass = isAktif ? 'fas fa-store' : 'fas fa-store-slash';

        // Custom marker icon
        const customIcon = L.divIcon({
            html: `<div style="background-color: ${iconColor}; color: white; border-radius: 50%; 
                   width: 25px; height: 25px; display: flex; align-items: center; 
                   justify-content: center; border: 3px solid white;
                   box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                   <i class="${iconClass}" style="font-size: 12px;"></i></div>`,
            className: 'custom-marker',
            iconSize: [25, 25],
            iconAnchor: [12, 12]
        });

        const marker = L.marker([toko.latitude, toko.longitude], {
            icon: customIcon
        });

        // Popup content
        const popupContent = this.createPopupContent(toko);
        marker.bindPopup(popupContent, {
            maxWidth: 350,
            className: 'custom-popup'
        });

        // Click event untuk detail
        marker.on('click', () => {
            this.showTokoDetail(toko.toko_id);
        });

        return marker;
    }

    createPopupContent(toko) {
        return `
            <div class="popup-header">
                <h5 style="margin: 0; font-size: 16px;">
                    <i class="fas fa-store mr-2"></i>${toko.nama_toko}
                </h5>
                <small>${toko.pemilik}</small>
            </div>
            <div style="padding: 5px;">
                <p style="margin: 5px 0; font-size: 13px;">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    ${toko.alamat}
                </p>
                <p style="margin: 5px 0; font-size: 13px;">
                    <i class="fas fa-phone mr-1"></i>
                    ${toko.telpon}
                </p>
                <p style="margin: 5px 0; font-size: 13px;">
                    <i class="fas fa-map mr-1"></i>
                    ${toko.kecamatan}, ${toko.kota_kabupaten}
                </p>
                <div class="popup-stats">
                    <div class="popup-stat">
                        <div class="popup-stat-value">${toko.jumlah_barang}</div>
                        <div class="popup-stat-label">Barang</div>
                    </div>
                    <div class="popup-stat">
                        <div class="popup-stat-value">${toko.total_pengiriman}</div>
                        <div class="popup-stat-label">Pengiriman</div>
                    </div>
                    <div class="popup-stat">
                        <div class="popup-stat-value">${toko.total_retur}</div>
                        <div class="popup-stat-label">Retur</div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    <span class="badge ${toko.status_aktif === 'Aktif' ? 'badge-success' : 'badge-danger'}">
                        ${toko.status_aktif}
                    </span>
                </div>
                <div style="text-align: center; margin-top: 8px;">
                    <button class="btn btn-sm btn-primary" onclick="marketMapInstance.showTokoDetail('${toko.toko_id}')">
                        <i class="fas fa-info-circle"></i> Detail
                    </button>
                </div>
            </div>
        `;
    }

    updateHeatmap() {
        // Remove existing heatmap
        if (this.heatmapLayer) {
            this.map.removeLayer(this.heatmapLayer);
        }

        if (!this.isHeatmapEnabled) return;

        // Prepare heatmap data
        const heatmapData = this.tokoData.map(toko => [
            toko.latitude,
            toko.longitude,
            toko.jumlah_barang * 0.1 + 0.5 // intensity based on jumlah barang
        ]);

        // Create heatmap layer
        this.heatmapLayer = L.heatLayer(heatmapData, {
            radius: 30,
            blur: 20,
            maxZoom: 15,
            gradient: {
                0.0: '#ffffcc',
                0.2: '#fed976',
                0.4: '#feb24c',
                0.6: '#fd8d3c',
                0.8: '#fc4e2a',
                1.0: '#e31a1c'
            }
        });

        this.map.addLayer(this.heatmapLayer);
    }

    toggleHeatmap(enabled) {
        this.isHeatmapEnabled = enabled;
        this.updateHeatmap();
    }

    toggleCluster(enabled) {
        this.isClusterEnabled = enabled;
        
        // Remove current layers
        this.map.removeLayer(this.markerCluster);
        this.map.removeLayer(this.markersLayer);
        
        // Re-render markers
        this.renderMarkers();
        
        // Add appropriate layer
        if (this.isClusterEnabled) {
            this.map.addLayer(this.markerCluster);
        } else {
            this.map.addLayer(this.markersLayer);
        }
    }

    filterByWilayah(wilayah) {
        if (wilayah === 'all') {
            // Show all markers
            this.renderMarkers();
        } else {
            // Filter markers by wilayah
            const filteredData = this.tokoData.filter(toko => 
                toko.kota_kabupaten === wilayah
            );
            
            // Clear and render filtered markers
            this.markerCluster.clearLayers();
            this.markersLayer.clearLayers();
            
            filteredData.forEach(toko => {
                const marker = this.createTokoMarker(toko);
                
                if (this.isClusterEnabled) {
                    this.markerCluster.addLayer(marker);
                } else {
                    this.markersLayer.addLayer(marker);
                }
            });
            
            // Focus map on filtered area
            if (filteredData.length > 0) {
                const group = new L.featureGroup(
                    this.isClusterEnabled ? 
                    this.markerCluster.getLayers() : 
                    this.markersLayer.getLayers()
                );
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        }
    }

    updateStatistics() {
        // Update info cards
        const totalToko = this.tokoData.length;
        const tokoAktif = this.tokoData.filter(t => t.status_aktif === 'Aktif').length;
        const kecamatanList = [...new Set(this.tokoData.map(t => t.kecamatan))];
        const wilayahPotensi = kecamatanList.filter(kec => {
            const tokoKec = this.tokoData.filter(t => t.kecamatan === kec);
            return tokoKec.length < 3 && tokoKec.length > 0;
        }).length;

        document.getElementById('total-toko').textContent = totalToko;
        document.getElementById('toko-aktif').textContent = tokoAktif;
        document.getElementById('total-kecamatan').textContent = kecamatanList.length;
        document.getElementById('wilayah-potensi').textContent = wilayahPotensi;

        // Update wilayah statistics table
        this.updateWilayahStats();
    }

    updateWilayahStats() {
        const statsContainer = document.getElementById('stats-tbody');
        
        // Group by kota/kabupaten
        const wilayahStats = {};
        this.tokoData.forEach(toko => {
            const wilayah = toko.kota_kabupaten;
            if (!wilayahStats[wilayah]) {
                wilayahStats[wilayah] = 0;
            }
            wilayahStats[wilayah]++;
        });

        // Generate table rows
        let html = '';
        Object.keys(wilayahStats).forEach(wilayah => {
            html += `
                <tr>
                    <td><strong>${wilayah}</strong></td>
                    <td><span class="badge badge-primary">${wilayahStats[wilayah]}</span></td>
                </tr>
            `;
        });

        if (html === '') {
            html = '<tr><td colspan="2" class="text-center">Tidak ada data</td></tr>';
        }

        statsContainer.innerHTML = html;
    }

    async loadRecommendations() {
        try {
            const response = await fetch('/market-map/recommendations');
            const result = await response.json();
            
            if (result.success) {
                this.renderRecommendations(result.data);
            }
        } catch (error) {
            console.error('Error loading recommendations:', error);
        }
    }

    renderRecommendations(data) {
        const container = document.getElementById('recommendations-content');
        
        let html = '<div class="mb-3">';
        html += '<h6><i class="fas fa-map-pin mr-2"></i>Wilayah Potensial:</h6>';
        
        if (data.potensial_wilayah && data.potensial_wilayah.length > 0) {
            html += '<ul class="list-unstyled">';
            data.potensial_wilayah.slice(0, 5).forEach(wilayah => {
                html += `
                    <li class="mb-1">
                        <small class="text-muted">${wilayah.wilayah_kecamatan}</small><br>
                        <span class="badge badge-warning">${wilayah.jumlah_toko} toko</span>
                    </li>
                `;
            });
            html += '</ul>';
        } else {
            html += '<p class="text-muted small">Semua wilayah sudah optimal</p>';
        }
        
        html += '</div>';
        
        html += '<div>';
        html += '<h6><i class="fas fa-star mr-2"></i>Barang Populer:</h6>';
        
        if (data.barang_populer && data.barang_populer.length > 0) {
            html += '<ul class="list-unstyled">';
            data.barang_populer.slice(0, 3).forEach(barang => {
                html += `
                    <li class="mb-1">
                        <small>${barang.nama_barang}</small><br>
                        <span class="badge badge-success">${barang.total_dikirim} unit</span>
                    </li>
                `;
            });
            html += '</ul>';
        } else {
            html += '<p class="text-muted small">Belum ada data barang</p>';
        }
        
        html += '</div>';
        
        container.innerHTML = html;
    }

    async showTokoDetail(tokoId) {
        try {
            // Show modal
            $('#tokoDetailModal').modal('show');
            
            // Show loading
            document.getElementById('toko-detail-content').innerHTML = `
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Memuat detail toko...</p>
                </div>
            `;

            // Fetch detail data
            const response = await fetch(`/market-map/toko-barang/${tokoId}`);
            const result = await response.json();

            if (result.success) {
                this.renderTokoDetail(result.data);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error loading toko detail:', error);
            document.getElementById('toko-detail-content').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Gagal memuat detail toko: ${error.message}
                </div>
            `;
        }
    }

    renderTokoDetail(data) {
        const { barang, statistik_pengiriman, statistik_retur } = data;
        
        let html = '';
        
        // Statistics cards
        html += `
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card bg-info">
                        <div class="card-body text-center text-white">
                            <h4>${barang.length}</h4>
                            <p class="mb-0">Total Barang</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success">
                        <div class="card-body text-center text-white">
                            <h4>${statistik_pengiriman?.total_pengiriman || 0}</h4>
                            <p class="mb-0">Total Pengiriman</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning">
                        <div class="card-body text-center text-white">
                            <h4>${statistik_retur?.total_retur || 0}</h4>
                            <p class="mb-0">Total Retur</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Barang table
        html += `
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-boxes mr-2"></i>Daftar Barang</h5>
                </div>
                <div class="card-body">
        `;

        if (barang.length > 0) {
            html += `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Harga Awal</th>
                                <th>Harga Toko</th>
                                <th>Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            barang.forEach(item => {
                html += `
                    <tr>
                        <td><code>${item.barang_kode}</code></td>
                        <td>${item.nama_barang}</td>
                        <td>Rp ${this.formatCurrency(item.harga_awal_barang)}</td>
                        <td>Rp ${this.formatCurrency(item.harga_barang_toko)}</td>
                        <td>${item.satuan}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            html += `
                <div class="text-center text-muted">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <p>Belum ada barang di toko ini</p>
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;

        document.getElementById('toko-detail-content').innerHTML = html;
    }

    async loadKecamatan(kotaKabupaten) {
        const kecamatanSelect = document.getElementById('select-kecamatan');
        const kelurahanSelect = document.getElementById('select-kelurahan');
        
        // Reset
        kecamatanSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
        kelurahanSelect.innerHTML = '<option value="">Pilih Kelurahan</option>';
        
        if (!kotaKabupaten || !this.wilayahData.wilayah) return;
        
        // Find wilayah data
        const wilayah = this.wilayahData.wilayah.find(w => w.nama === kotaKabupaten);
        
        if (wilayah && wilayah.kecamatan) {
            wilayah.kecamatan.forEach(kec => {
                const option = document.createElement('option');
                option.value = kec.nama;
                option.textContent = kec.nama;
                kecamatanSelect.appendChild(option);
            });
        }
    }

    async loadKelurahan(kecamatan) {
        const kotaKabupaten = document.getElementById('select-kota').value;
        const kelurahanSelect = document.getElementById('select-kelurahan');
        
        // Reset
        kelurahanSelect.innerHTML = '<option value="">Pilih Kelurahan</option>';
        
        if (!kecamatan || !kotaKabupaten || !this.wilayahData.wilayah) return;
        
        // Find wilayah and kecamatan data
        const wilayah = this.wilayahData.wilayah.find(w => w.nama === kotaKabupaten);
        
        if (wilayah && wilayah.kecamatan) {
            const kecamatanData = wilayah.kecamatan.find(k => k.nama === kecamatan);
            
            if (kecamatanData && kecamatanData.kelurahan) {
                kecamatanData.kelurahan.forEach(kel => {
                    const option = document.createElement('option');
                    option.value = kel.nama;
                    option.textContent = kel.nama;
                    kelurahanSelect.appendChild(option);
                });
            }
        }
    }

    async handleAddToko(event) {
        try {
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);

            const response = await fetch('/market-map/store-toko', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                // Close modal
                $('#addTokoModal').modal('hide');
                
                // Reset form
                event.target.reset();
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Toko baru berhasil ditambahkan',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Reload data
                this.loadData();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error adding toko:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Gagal menambahkan toko: ' + error.message
            });
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID').format(amount);
    }

    showLoading(show) {
        // Implement loading indicator if needed
        if (show) {
            // Show loading overlay
        } else {
            // Hide loading overlay
        }
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message
        });
    }
}

// Initialize market map when document is ready
let marketMapInstance;

document.addEventListener('DOMContentLoaded', function() {
    marketMapInstance = new MarketMap();
});

// Export for global access
window.marketMapInstance = marketMapInstance;
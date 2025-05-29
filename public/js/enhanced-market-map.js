/**
 * Enhanced Market Map JavaScript - Grid-based Heatmap Implementation
 * Menampilkan peta persebaran toko dengan visualisasi grid heatmap berbasis wilayah
 */

class EnhancedMarketMap {
    constructor() {
        this.map = null;
        this.tokoData = [];
        this.wilayahData = [];
        this.gridData = [];
        this.markerCluster = null;
        this.heatmapLayer = null;
        this.gridLayer = null;
        this.markersLayer = null;
        this.isClusterEnabled = true;
        this.isHeatmapEnabled = true;
        this.isGridHeatmapEnabled = true;
        this.showCoordinateStatus = false;
        
        // Heat map configuration
        this.heatmapConfig = {
            gridSize: 0.01, // Ukuran grid dalam derajat (sekitar 1.1km)
            colors: {
                high: '#dc143c',     // Merah pekat untuk 5+ toko
                medium: '#ff8c00',   // Oranye untuk 2-4 toko  
                low: '#ffd700',      // Kuning terang untuk 1 toko
                none: 'transparent'  // Transparan untuk 0 toko
            },
            opacity: 0.7,
            strokeColor: '#ffffff',
            strokeWeight: 1,
            strokeOpacity: 0.8
        };
        
        // Check dependencies
        if (!this.checkDependencies()) {
            this.showError('Required libraries not loaded. Please check your internet connection.');
            return;
        }
        
        this.init();
    }

    checkDependencies() {
        const required = {
            'Leaflet': typeof L !== 'undefined',
            'MarkerCluster': typeof L !== 'undefined' && L.markerClusterGroup,
            'SweetAlert': typeof Swal !== 'undefined'
        };

        const missing = Object.keys(required).filter(lib => !required[lib]);
        
        if (missing.length > 0) {
            console.error('Missing dependencies:', missing);
            this.showFallbackError('Missing libraries: ' + missing.join(', '));
            return false;
        }
        
        return true;
    }

    init() {
        try {
            this.initMap();
            this.setupEventListeners();
            this.loadData();
        } catch (error) {
            console.error('Error initializing Enhanced MarketMap:', error);
            this.showError('Failed to initialize map: ' + error.message);
        }
    }

    initMap() {
        try {
            // Inisialisasi peta dengan center di Malang
            this.map = L.map('market-map').setView([-7.9666, 112.6326], 11);

            // Base tile layer dengan error handling
            const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18,
                errorTileUrl: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
            });
            
            tileLayer.on('tileerror', (e) => {
                console.warn('Tile loading error:', e);
            });
            
            tileLayer.addTo(this.map);

            // Inisialisasi marker cluster dengan custom styling
            this.markerCluster = L.markerClusterGroup({
                iconCreateFunction: (cluster) => {
                    const count = cluster.getChildCount();
                    let size = 'small';
                    let color = '#ffd700'; // kuning default
                    
                    if (count >= 10) {
                        size = 'large';
                        color = '#dc143c'; // merah untuk density tinggi
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
            
            // Layer untuk grid heatmap
            this.gridLayer = L.layerGroup();
            
            // Add cluster layer to map by default
            this.map.addLayer(this.markerCluster);
            this.map.addLayer(this.gridLayer);
            
            // Add legend control
            this.addLegendControl();
            
            console.log('Enhanced Map initialized successfully');
            
        } catch (error) {
            console.error('Error initializing map:', error);
            throw new Error('Map initialization failed: ' + error.message);
        }
    }

    addLegendControl() {
        const legend = L.control({position: 'bottomright'});
        
        legend.onAdd = () => {
            const div = L.DomUtil.create('div', 'grid-heatmap-legend');
            div.innerHTML = `
                <div class="legend-content">
                    <h6><i class="fas fa-th mr-2"></i>Grid Heatmap Density</h6>
                    <div class="legend-items">
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${this.heatmapConfig.colors.high};"></span>
                            <span>Kepadatan Tinggi (5+ toko)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${this.heatmapConfig.colors.medium};"></span>
                            <span>Kepadatan Sedang (2-4 toko)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${this.heatmapConfig.colors.low};"></span>
                            <span>Kepadatan Rendah (1 toko)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: transparent; border: 1px solid #ccc;"></span>
                            <span>Tidak Ada Toko</span>
                        </div>
                    </div>
                    <div class="legend-controls">
                        <label class="legend-toggle">
                            <input type="checkbox" id="toggle-grid-heatmap" checked>
                            <span>Tampilkan Grid Heatmap</span>
                        </label>
                    </div>
                </div>
            `;
            
            // Add event listener for toggle
            div.querySelector('#toggle-grid-heatmap').addEventListener('change', (e) => {
                this.toggleGridHeatmap(e.target.checked);
            });
            
            return div;
        };
        
        legend.addTo(this.map);
    }

    setupEventListeners() {
        try {
            // Filter wilayah
            const filterWilayah = document.getElementById('filter-wilayah');
            if (filterWilayah) {
                filterWilayah.addEventListener('change', (e) => {
                    this.filterByWilayah(e.target.value);
                });
            }

            // Toggle heatmap (classic points)
            const toggleHeatmap = document.getElementById('toggle-heatmap');
            if (toggleHeatmap) {
                toggleHeatmap.addEventListener('change', (e) => {
                    this.toggleHeatmap(e.target.checked);
                });
            }

            // Toggle cluster
            const toggleCluster = document.getElementById('toggle-cluster');
            if (toggleCluster) {
                toggleCluster.addEventListener('change', (e) => {
                    this.toggleCluster(e.target.checked);
                });
            }

            // Toggle coordinate status
            const toggleCoordinates = document.getElementById('toggle-coordinates');
            if (toggleCoordinates) {
                toggleCoordinates.addEventListener('change', (e) => {
                    this.showCoordinateStatus = e.target.checked;
                    this.renderMarkers();
                });
            }

            // Refresh map
            const btnRefreshMap = document.getElementById('btn-refresh-map');
            if (btnRefreshMap) {
                btnRefreshMap.addEventListener('click', () => {
                    this.loadData();
                });
            }

            // Bulk geocoding
            const btnBulkGeocode = document.getElementById('btn-bulk-geocode');
            if (btnBulkGeocode) {
                btnBulkGeocode.addEventListener('click', () => {
                    this.handleBulkGeocode();
                });
            }

            // Form tambah toko
            const formAddToko = document.getElementById('form-add-toko');
            if (formAddToko) {
                formAddToko.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleAddToko(e);
                });
            }

            // Dropdown wilayah untuk form
            const selectKota = document.getElementById('select-kota');
            if (selectKota) {
                selectKota.addEventListener('change', (e) => {
                    this.loadKecamatan(e.target.value);
                });
            }

            const selectKecamatan = document.getElementById('select-kecamatan');
            if (selectKecamatan) {
                selectKecamatan.addEventListener('change', (e) => {
                    this.loadKelurahan(e.target.value);
                });
            }

            console.log('Event listeners set up successfully');
            
        } catch (error) {
            console.error('Error setting up event listeners:', error);
        }
    }

    async loadData() {
        try {
            this.showLoading(true);

            // Load toko data dengan error handling
            const tokoData = await this.fetchWithRetry('/market-map/toko-data');
            if (tokoData.success) {
                this.tokoData = tokoData.data;
                this.renderMarkers();
                this.generateGridHeatmap();
                this.updateStatistics(tokoData.summary);
                console.log('Loaded', this.tokoData.length, 'toko records');
            } else {
                throw new Error(tokoData.message || 'Failed to load toko data');
            }

            // Load wilayah data
            try {
                const wilayahData = await this.fetchWithRetry('/market-map/wilayah-data');
                if (wilayahData.success) {
                    this.wilayahData = wilayahData.data;
                    console.log('Loaded wilayah data successfully');
                }
            } catch (error) {
                console.warn('Failed to load wilayah data:', error);
                // Continue without wilayah data
            }

            // Load recommendations
            try {
                await this.loadRecommendations();
            } catch (error) {
                console.warn('Failed to load recommendations:', error);
            }
            
            this.showLoading(false);
            
        } catch (error) {
            console.error('Error loading data:', error);
            this.showError('Gagal memuat data peta: ' + error.message);
            this.showLoading(false);
        }
    }

    async fetchWithRetry(url, options = {}, retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        ...options.headers
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return await response.json();
            } catch (error) {
                console.warn(`Attempt ${i + 1} failed:`, error);
                if (i === retries - 1) throw error;
                await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
            }
        }
    }

    generateGridHeatmap() {
        try {
            console.log('Generating grid heatmap...');
            
            // Clear existing grid
            this.gridLayer.clearLayers();
            
            if (!this.tokoData || this.tokoData.length === 0) {
                console.warn('No toko data for grid heatmap');
                return;
            }

            // Define bounds for Malang region
            const bounds = {
                north: -7.4,    // Batas utara
                south: -8.6,    // Batas selatan  
                west: 111.8,    // Batas barat
                east: 113.2     // Batas timur
            };

            // Generate grid cells
            const gridCells = this.createGridCells(bounds);
            
            // Count toko in each grid cell
            const gridCounts = this.countTokoInGrids(gridCells);
            
            // Create colored rectangles for each grid
            gridCounts.forEach(cell => {
                if (cell.count > 0) {
                    const color = this.getGridColor(cell.count);
                    const rectangle = L.rectangle(cell.bounds, {
                        color: this.heatmapConfig.strokeColor,
                        weight: this.heatmapConfig.strokeWeight,
                        opacity: this.heatmapConfig.strokeOpacity,
                        fillColor: color,
                        fillOpacity: this.heatmapConfig.opacity
                    });
                    
                    // Add popup with information
                    const popupContent = this.createGridPopupContent(cell);
                    rectangle.bindPopup(popupContent);
                    
                    // Add hover effects
                    rectangle.on('mouseover', function() {
                        this.setStyle({
                            fillOpacity: 0.9,
                            weight: 3
                        });
                    });
                    
                    rectangle.on('mouseout', function() {
                        this.setStyle({
                            fillOpacity: 0.7,
                            weight: 1
                        });
                    });
                    
                    this.gridLayer.addLayer(rectangle);
                }
            });
            
            console.log(`Generated ${gridCounts.filter(c => c.count > 0).length} grid cells with toko data`);
            
        } catch (error) {
            console.error('Error generating grid heatmap:', error);
        }
    }

    createGridCells(bounds) {
        const cells = [];
        const gridSize = this.heatmapConfig.gridSize;
        
        for (let lat = bounds.south; lat < bounds.north; lat += gridSize) {
            for (let lng = bounds.west; lng < bounds.east; lng += gridSize) {
                cells.push({
                    bounds: [
                        [lat, lng],
                        [lat + gridSize, lng + gridSize]
                    ],
                    center: {
                        lat: lat + gridSize / 2,
                        lng: lng + gridSize / 2
                    }
                });
            }
        }
        
        return cells;
    }

    countTokoInGrids(gridCells) {
        return gridCells.map(cell => {
            const tokosInCell = this.tokoData.filter(toko => {
                const lat = parseFloat(toko.latitude);
                const lng = parseFloat(toko.longitude);
                
                if (isNaN(lat) || isNaN(lng)) return false;
                
                const [[minLat, minLng], [maxLat, maxLng]] = cell.bounds;
                
                return lat >= minLat && lat < maxLat && lng >= minLng && lng < maxLng;
            });
            
            return {
                ...cell,
                count: tokosInCell.length,
                tokos: tokosInCell
            };
        });
    }

    getGridColor(count) {
        if (count >= 5) {
            return this.heatmapConfig.colors.high;    // Merah pekat untuk 5+ toko
        } else if (count >= 2) {
            return this.heatmapConfig.colors.medium;  // Oranye untuk 2-4 toko
        } else if (count >= 1) {
            return this.heatmapConfig.colors.low;     // Kuning terang untuk 1 toko
        } else {
            return this.heatmapConfig.colors.none;    // Transparan untuk 0 toko
        }
    }

    createGridPopupContent(cell) {
        const categoryText = this.getCategoryText(cell.count);
        
        let content = `
            <div class="grid-popup">
                <div class="popup-header">
                    <h6><i class="fas fa-th mr-2"></i>Grid Wilayah</h6>
                </div>
                <div class="popup-body">
                    <div class="grid-stats">
                        <div class="stat-item">
                            <span class="stat-label">Jumlah Toko:</span>
                            <span class="stat-value">${cell.count}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Kategori:</span>
                            <span class="stat-value">${categoryText}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Koordinat:</span>
                            <span class="stat-value">${cell.center.lat.toFixed(4)}, ${cell.center.lng.toFixed(4)}</span>
                        </div>
                    </div>
        `;
        
        if (cell.count > 0) {
            content += `
                    <div class="toko-list">
                        <h6>Daftar Toko:</h6>
                        <ul>
            `;
            
            cell.tokos.slice(0, 5).forEach(toko => {
                content += `<li>${toko.nama_toko} (${toko.kecamatan})</li>`;
            });
            
            if (cell.tokos.length > 5) {
                content += `<li><em>dan ${cell.tokos.length - 5} toko lainnya...</em></li>`;
            }
            
            content += `
                        </ul>
                    </div>
            `;
        }
        
        content += `
                </div>
            </div>
        `;
        
        return content;
    }

    getCategoryText(count) {
        if (count >= 5) return 'Kepadatan Tinggi';
        if (count >= 2) return 'Kepadatan Sedang';
        if (count >= 1) return 'Kepadatan Rendah';
        return 'Tidak Ada Toko';
    }

    toggleGridHeatmap(enabled) {
        this.isGridHeatmapEnabled = enabled;
        
        if (enabled) {
            if (!this.map.hasLayer(this.gridLayer)) {
                this.map.addLayer(this.gridLayer);
            }
        } else {
            if (this.map.hasLayer(this.gridLayer)) {
                this.map.removeLayer(this.gridLayer);
            }
        }
    }

    renderMarkers() {
        try {
            // Clear existing markers
            this.markerCluster.clearLayers();
            this.markersLayer.clearLayers();

            if (!this.tokoData || this.tokoData.length === 0) {
                console.warn('No toko data to render');
                return;
            }

            // Create markers for each toko
            let validMarkers = 0;
            this.tokoData.forEach(toko => {
                try {
                    const marker = this.createTokoMarker(toko);
                    if (marker) {
                        if (this.isClusterEnabled) {
                            this.markerCluster.addLayer(marker);
                        } else {
                            this.markersLayer.addLayer(marker);
                        }
                        validMarkers++;
                    }
                } catch (error) {
                    console.warn('Error creating marker for toko:', toko.toko_id, error);
                }
            });

            console.log(`Rendered ${validMarkers} markers from ${this.tokoData.length} toko records`);

            // Update classic heatmap
            if (this.isHeatmapEnabled) {
                this.updateHeatmap();
            }
            
        } catch (error) {
            console.error('Error rendering markers:', error);
            this.showError('Gagal menampilkan marker toko');
        }
    }

    createTokoMarker(toko) {
        try {
            // Validasi koordinat
            if (!toko.latitude || !toko.longitude || 
                isNaN(toko.latitude) || isNaN(toko.longitude)) {
                console.warn('Invalid coordinates for toko:', toko.toko_id);
                return null;
            }

            // Validasi rentang koordinat (Indonesia)
            if (toko.latitude < -12 || toko.latitude > 8 || 
                toko.longitude < 94 || toko.longitude > 142) {
                console.warn('Coordinates outside Indonesia for toko:', toko.toko_id);
                return null;
            }

            // Pilih icon berdasarkan status toko
            const isAktif = toko.status_aktif === 'Aktif';
            const iconColor = isAktif ? '#28a745' : '#dc3545';
            const iconClass = isAktif ? 'fas fa-store' : 'fas fa-store-slash';
            
            // Add coordinate status indicator
            const coordStatus = toko.has_coordinates ? 'GPS' : 'EST';
            const coordClass = toko.has_coordinates ? 'real' : 'estimated';

            // Custom marker icon dengan coordinate status
            let iconHtml = `
                <div style="background-color: ${iconColor}; color: white; border-radius: 50%; 
                     width: 25px; height: 25px; display: flex; align-items: center; 
                     justify-content: center; border: 3px solid white;
                     box-shadow: 0 2px 4px rgba(0,0,0,0.3); position: relative;">
                    <i class="${iconClass}" style="font-size: 12px;"></i>
            `;
            
            if (this.showCoordinateStatus) {
                iconHtml += `
                    <span class="coordinate-status ${coordClass}" 
                          style="position: absolute; top: -8px; right: -8px; 
                                 padding: 1px 3px; font-size: 8px; border-radius: 6px;">
                        ${coordStatus}
                    </span>
                `;
            }
            
            iconHtml += '</div>';

            const customIcon = L.divIcon({
                html: iconHtml,
                className: 'custom-marker',
                iconSize: [25, 25],
                iconAnchor: [12, 12]
            });

            const marker = L.marker([parseFloat(toko.latitude), parseFloat(toko.longitude)], {
                icon: customIcon,
                title: toko.nama_toko
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
            
        } catch (error) {
            console.error('Error creating marker for toko:', toko.toko_id, error);
            return null;
        }
    }

    createPopupContent(toko) {
        const coordStatus = toko.has_coordinates ? 
            '<span class="coordinate-status real">GPS Akurat</span>' : 
            '<span class="coordinate-status estimated">Estimasi</span>';
            
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
                <div style="text-align: center; margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span class="badge ${toko.status_aktif === 'Aktif' ? 'badge-success' : 'badge-danger'}">
                        ${toko.status_aktif}
                    </span>
                    ${coordStatus}
                </div>
                <div style="text-align: center; margin-top: 8px;">
                    <button class="btn btn-sm btn-primary" onclick="enhancedMarketMapInstance.showTokoDetail('${toko.toko_id}')">
                        <i class="fas fa-info-circle"></i> Detail
                    </button>
                </div>
            </div>
        `;
    }

    // ... [Continue with remaining methods from original implementation]
    // Include all other methods like updateHeatmap, toggleHeatmap, toggleCluster, etc.
    // but keeping the enhanced grid functionality

    updateHeatmap() {
        // Remove existing heatmap
        if (this.heatmapLayer) {
            this.map.removeLayer(this.heatmapLayer);
        }

        if (!this.isHeatmapEnabled) return;

        // Prepare heatmap data for classic point heatmap
        const heatmapData = this.tokoData.filter(toko => 
            toko.latitude && toko.longitude && 
            !isNaN(toko.latitude) && !isNaN(toko.longitude)
        ).map(toko => [
            toko.latitude,
            toko.longitude,
            Math.min(toko.jumlah_barang * 0.1 + 0.5, 1.0) // intensity based on jumlah barang, capped at 1.0
        ]);

        if (heatmapData.length === 0) return;

        // Create classic heatmap layer (requires leaflet-heat plugin)
        if (typeof L.heatLayer !== 'undefined') {
            this.heatmapLayer = L.heatLayer(heatmapData, {
                radius: 25,
                blur: 15,
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
        } else {
            console.warn('Leaflet heatmap plugin not available');
        }
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
            // Show all markers and regenerate full grid
            this.renderMarkers();
            this.generateGridHeatmap();
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
                if (marker) {
                    if (this.isClusterEnabled) {
                        this.markerCluster.addLayer(marker);
                    } else {
                        this.markersLayer.addLayer(marker);
                    }
                }
            });
            
            // Regenerate grid with filtered data
            const originalData = this.tokoData;
            this.tokoData = filteredData;
            this.generateGridHeatmap();
            this.tokoData = originalData; // Restore original data
            
            // Focus map on filtered area
            if (filteredData.length > 0) {
                const group = new L.featureGroup(
                    this.isClusterEnabled ? 
                    this.markerCluster.getLayers() : 
                    this.markersLayer.getLayers()
                );
                if (group.getLayers().length > 0) {
                    this.map.fitBounds(group.getBounds().pad(0.1));
                }
            }
        }
    }

    updateStatistics(summary = null) {
        try {
            if (summary) {
                // Use summary from API
                document.getElementById('total-toko').textContent = summary.total_toko || 0;
                document.getElementById('toko-aktif').textContent = summary.toko_active || 0;
            } else {
                // Calculate from local data
                const totalToko = this.tokoData.length;
                const tokoAktif = this.tokoData.filter(t => t.status_aktif === 'Aktif').length;
                
                document.getElementById('total-toko').textContent = totalToko;
                document.getElementById('toko-aktif').textContent = tokoAktif;
            }
            
            const kecamatanList = [...new Set(this.tokoData.map(t => t.kecamatan))];
            const wilayahPotensi = kecamatanList.filter(kec => {
                const tokoKec = this.tokoData.filter(t => t.kecamatan === kec);
                return tokoKec.length < 3 && tokoKec.length > 0;
            }).length;

            document.getElementById('total-kecamatan').textContent = kecamatanList.length;
            document.getElementById('wilayah-potensi').textContent = wilayahPotensi;

            // Update wilayah statistics table
            this.updateWilayahStats();
            
        } catch (error) {
            console.error('Error updating statistics:', error);
        }
    }

    updateWilayahStats() {
        try {
            const statsContainer = document.getElementById('stats-tbody');
            if (!statsContainer) return;
            
            // Group by kota/kabupaten
            const wilayahStats = {};
            this.tokoData.forEach(toko => {
                const wilayah = toko.kota_kabupaten;
                if (!wilayahStats[wilayah]) {
                    wilayahStats[wilayah] = { total: 0, withGPS: 0 };
                }
                wilayahStats[wilayah].total++;
                if (toko.has_coordinates) {
                    wilayahStats[wilayah].withGPS++;
                }
            });

            // Generate table rows
            let html = '';
            Object.keys(wilayahStats).forEach(wilayah => {
                const stats = wilayahStats[wilayah];
                const gpsPercentage = Math.round((stats.withGPS / stats.total) * 100);
                html += `
                    <tr>
                        <td><strong>${wilayah}</strong></td>
                        <td><span class="badge badge-primary">${stats.total}</span></td>
                        <td>
                            <span class="badge ${gpsPercentage >= 80 ? 'badge-success' : gpsPercentage >= 50 ? 'badge-warning' : 'badge-danger'}">
                                ${stats.withGPS}/${stats.total}
                            </span>
                        </td>
                    </tr>
                `;
            });

            if (html === '') {
                html = '<tr><td colspan="3" class="text-center">Tidak ada data</td></tr>';
            }

            statsContainer.innerHTML = html;
            
        } catch (error) {
            console.error('Error updating wilayah stats:', error);
        }
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

    async handleBulkGeocode() {
        try {
            const result = await Swal.fire({
                title: 'Bulk Geocoding',
                text: 'Proses ini akan mencari koordinat GPS untuk toko yang belum memiliki koordinat akurat. Lanjutkan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Proses',
                cancelButtonText: 'Batal'
            });

            if (result.isConfirmed) {
                // Show progress
                Swal.fire({
                    title: 'Memproses Geocoding...',
                    text: 'Mohon tunggu, sedang mengambil koordinat GPS...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const response = await this.fetchWithRetry('/market-map/bulk-geocode', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 3000
                    });
                    
                    // Reload data
                    this.loadData();
                } else {
                    throw new Error(response.message);
                }
            }
        } catch (error) {
            console.error('Error bulk geocoding:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Bulk geocoding gagal: ' + error.message
            });
        }
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
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }

    showError(message) {
        console.error('Enhanced MarketMap Error:', message);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message
            });
        } else {
            this.showFallbackError(message);
        }
    }

    showFallbackError(message) {
        const mapContainer = document.getElementById('market-map');
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Error:</strong> ${message}
                </div>
            `;
        }
    }
}

// Initialize enhanced market map when document is ready
let enhancedMarketMapInstance;

document.addEventListener('DOMContentLoaded', function() {
    try {
        enhancedMarketMapInstance = new EnhancedMarketMap();
        window.enhancedMarketMapInstance = enhancedMarketMapInstance;
        console.log('Enhanced MarketMap instance created successfully');
    } catch (error) {
        console.error('Failed to create Enhanced MarketMap instance:', error);
    }
});

// Export for global access
window.enhancedMarketMapInstance = enhancedMarketMapInstance;
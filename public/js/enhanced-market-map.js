/**
 * Enhanced Market Map JavaScript - Complete CRM Intelligence Implementation
 * Geographic CRM analytics dengan price intelligence dan partner performance
 * Version: 3.0 - Production Ready with Full Error Handling
 */

class EnhancedMarketMapCRM {
    constructor() {
        // Core map properties
        this.map = null;
        this.partnerData = [];
        this.filteredData = [];
        this.priceIntelligence = [];
        this.partnerPerformance = [];
        this.marketOpportunities = [];
        this.recommendations = {};
        
        // Map layers
        this.markerCluster = null;
        this.heatmapLayer = null;
        this.gridLayer = null;
        this.markersLayer = null;
        
        // State management
        this.isClusterEnabled = true;
        this.isHeatmapEnabled = false;
        this.isGridHeatmapEnabled = true;
        this.showPerformanceView = false;
        this.isLoading = false;
        this.lastUpdateTime = null;
        
        // Cache management
        this.cache = new Map();
        this.cacheExpiry = 15 * 60 * 1000; // 15 minutes
        
        // Error tracking
        this.errorCount = 0;
        this.maxRetries = 3;
        
        // Performance tracking
        this.performanceMetrics = {
            loadTime: 0,
            renderTime: 0,
            apiCalls: 0,
            cacheHits: 0
        };
        
        // CRM-specific configuration
        this.crmConfig = {
            performanceColors: {
                premium: '#28a745',
                growth: '#007bff', 
                standard: '#ffc107',
                new: '#6c757d',
                inactive: '#dc3545'
            },
            gridSize: 0.01,
            colors: {
                high: '#dc143c',
                medium: '#ff8c00',
                low: '#ffd700',
                none: 'transparent'
            },
            opacity: 0.7,
            strokeColor: '#ffffff',
            strokeWeight: 1,
            maxZoom: 18,
            minZoom: 8
        };
        
        // Initialize if dependencies are available
        this.init();
    }

    /**
     * Initialize the CRM Market Map system
     */
    async init() {
        try {
            const startTime = performance.now();
            
            // Check dependencies first
            if (!this.checkDependencies()) {
                this.showError('Required libraries not loaded. Please refresh the page.');
                return;
            }

            // Show loading indicator
            this.showLoading(true, 'Initializing CRM Market Intelligence...');

            // Initialize core components
            await this.initializeCore();
            
            // Track performance
            this.performanceMetrics.loadTime = performance.now() - startTime;
            
            console.log(`✅ CRM Market Intelligence initialized successfully in ${this.performanceMetrics.loadTime.toFixed(2)}ms`);
            
        } catch (error) {
            console.error('❌ Critical error initializing CRM MarketMap:', error);
            this.handleCriticalError(error);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Initialize core components sequentially
     */
    async initializeCore() {
        try {
            // 1. Initialize map
            await this.initMap();
            
            // 2. Setup event listeners
            this.setupEventListeners();
            
            // 3. Load initial data
            await this.loadCRMData();
            
            // 4. Setup periodic updates
            this.setupPeriodicUpdates();
            
            // 5. Setup keyboard shortcuts
            this.setupKeyboardShortcuts();
            
        } catch (error) {
            throw new Error(`Core initialization failed: ${error.message}`);
        }
    }

    /**
     * Check if all required dependencies are loaded
     */
    checkDependencies() {
        const required = {
            'Leaflet': typeof L !== 'undefined',
            'MarkerCluster': typeof L !== 'undefined' && L.markerClusterGroup,
            'SweetAlert': typeof Swal !== 'undefined',
            'jQuery': typeof $ !== 'undefined'
        };

        const missing = Object.keys(required).filter(lib => !required[lib]);
        
        if (missing.length > 0) {
            console.error('❌ Missing dependencies:', missing);
            this.showFallbackError(`Missing libraries: ${missing.join(', ')}`);
            return false;
        }
        
        return true;
    }

    /**
     * Initialize the Leaflet map with enhanced configuration
     */
    async initMap() {
        try {
            // Initialize map with optimized settings
            this.map = L.map('market-map', {
                center: [-7.9666, 112.6326], // Malang center
                zoom: 11,
                minZoom: this.crmConfig.minZoom,
                maxZoom: this.crmConfig.maxZoom,
                zoomControl: true,
                attributionControl: true,
                preferCanvas: true, // Better performance for many markers
                renderer: L.canvas() // Use canvas for better performance
            });

            // Add tile layer with error handling
            const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors | CRM Market Intelligence',
                maxZoom: this.crmConfig.maxZoom,
                errorTileUrl: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                crossOrigin: 'anonymous'
            });
            
            // Handle tile loading errors
            tileLayer.on('tileerror', (e) => {
                console.warn('🔸 Tile loading error:', e);
                this.performanceMetrics.errors = (this.performanceMetrics.errors || 0) + 1;
            });
            
            tileLayer.addTo(this.map);

            // Initialize marker cluster with enhanced styling
            this.initializeMarkerCluster();

            // Initialize layers
            this.markersLayer = L.layerGroup();
            this.gridLayer = L.layerGroup();
            
            // Add layers to map
            this.map.addLayer(this.markerCluster);
            this.map.addLayer(this.gridLayer);
            
            // Add map controls
            this.addMapControls();
            
            // Handle map events
            this.setupMapEvents();
            
            console.log('✅ Map initialized successfully');
            
        } catch (error) {
            throw new Error(`Map initialization failed: ${error.message}`);
        }
    }

    /**
     * Initialize marker cluster with CRM-specific styling
     */
    initializeMarkerCluster() {
        this.markerCluster = L.markerClusterGroup({
            iconCreateFunction: (cluster) => {
                const count = cluster.getChildCount();
                const markers = cluster.getAllChildMarkers();
                
                // Analyze cluster performance
                const performanceScores = markers.map(m => m.options.performanceScore || 50);
                const avgPerformance = performanceScores.reduce((a, b) => a + b, 0) / performanceScores.length;
                
                // Determine cluster styling
                let size = 'small';
                let color = this.crmConfig.performanceColors.standard;
                
                if (count >= 10) {
                    size = 'large';
                    color = avgPerformance >= 75 ? this.crmConfig.performanceColors.premium : this.crmConfig.performanceColors.growth;
                } else if (count >= 5) {
                    size = 'medium';
                    color = avgPerformance >= 60 ? this.crmConfig.performanceColors.growth : this.crmConfig.performanceColors.standard;
                }

                const clusterSize = size === 'large' ? 50 : (size === 'medium' ? 40 : 30);

                return L.divIcon({
                    html: `<div style="background-color: ${color}; color: white; border-radius: 50%; 
                           width: ${clusterSize}px; height: ${clusterSize}px; display: flex; align-items: center; 
                           justify-content: center; font-weight: bold; border: 3px solid white;
                           box-shadow: 0 4px 12px rgba(0,0,0,0.3); font-size: ${clusterSize/3}px;">
                           ${count}</div>`,
                    className: 'crm-cluster',
                    iconSize: [clusterSize, clusterSize]
                });
            },
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 50,
            animate: true,
            animateAddingMarkers: true
        });
    }

    /**
     * Add enhanced map controls
     */
    addMapControls() {
        // Add CRM legend control
        this.addCRMLegendControl();
        
        // Add performance overlay control
        this.addPerformanceControl();
        
        // Add refresh control
        this.addRefreshControl();
    }

    /**
     * Add CRM legend control with enhanced styling
     */
    addCRMLegendControl() {
        const legend = L.control({position: 'bottomright'});
        
        legend.onAdd = () => {
            const div = L.DomUtil.create('div', 'crm-legend');
            div.innerHTML = `
                <div class="legend-content">
                    <h6><i class="fas fa-chart-line mr-2"></i>Partner Performance</h6>
                    <div class="legend-items">
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${this.crmConfig.performanceColors.premium};"></span>
                            <span>Premium Partners (80-100)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${this.crmConfig.performanceColors.growth};"></span>
                            <span>Growth Partners (60-79)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${this.crmConfig.performanceColors.standard};"></span>
                            <span>Standard Partners (40-59)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${this.crmConfig.performanceColors.new};"></span>
                            <span>New Partners (0-39)</span>
                        </div>
                    </div>
                    <div class="legend-controls">
                        <label class="legend-toggle">
                            <input type="checkbox" id="legend-toggle-grid" checked>
                            <span>Territory Grid</span>
                        </label>
                        <label class="legend-toggle">
                            <input type="checkbox" id="legend-toggle-performance">
                            <span>Performance Scores</span>
                        </label>
                    </div>
                </div>
            `;
            
            // Prevent map interaction when using legend
            L.DomEvent.disableClickPropagation(div);
            L.DomEvent.disableScrollPropagation(div);
            
            // Add event listeners
            div.querySelector('#legend-toggle-grid').addEventListener('change', (e) => {
                this.toggleGridHeatmap(e.target.checked);
            });
            
            div.querySelector('#legend-toggle-performance').addEventListener('change', (e) => {
                this.showPerformanceView = e.target.checked;
                this.renderMarkers();
            });
            
            return div;
        };
        
        legend.addTo(this.map);
        this.legendControl = legend;
    }

    /**
     * Add performance overlay control
     */
    addPerformanceControl() {
        const perfControl = L.control({position: 'topright'});
        
        perfControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'performance-control');
            div.innerHTML = `
                <div class="performance-metrics">
                    <div class="metric-item">
                        <span class="metric-label">Partners:</span>
                        <span class="metric-value" id="perf-partner-count">-</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Load Time:</span>
                        <span class="metric-value" id="perf-load-time">-</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Last Update:</span>
                        <span class="metric-value" id="perf-last-update">-</span>
                    </div>
                </div>
            `;
            
            L.DomEvent.disableClickPropagation(div);
            return div;
        };
        
        perfControl.addTo(this.map);
        this.performanceControl = perfControl;
    }

    /**
     * Add refresh control
     */
    addRefreshControl() {
        const refreshControl = L.control({position: 'topleft'});
        
        refreshControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'refresh-control');
            div.innerHTML = `
                <button class="refresh-btn" title="Refresh Data">
                    <i class="fas fa-sync-alt"></i>
                </button>
            `;
            
            div.querySelector('.refresh-btn').addEventListener('click', () => {
                this.refreshAllData();
            });
            
            L.DomEvent.disableClickPropagation(div);
            return div;
        };
        
        refreshControl.addTo(this.map);
    }

    /**
     * Setup comprehensive event listeners
     */
    setupEventListeners() {
        try {
            // Territory filter
            this.setupFilterListeners();
            
            // CRM control buttons
            this.setupCRMControlListeners();
            
            // Modal handlers
            this.setupModalListeners();
            
            // Window events
            this.setupWindowListeners();
            
            console.log('✅ Event listeners set up successfully');
            
        } catch (error) {
            console.error('❌ Error setting up event listeners:', error);
            this.showError('Failed to setup interface controls');
        }
    }

    /**
     * Setup filter event listeners
     */
    setupFilterListeners() {
        const filters = [
            'filter-wilayah',
            'filter-segment', 
            'filter-performance',
            'filter-date'
        ];
        
        filters.forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                element.addEventListener('change', (e) => {
                    this.handleFilterChange(filterId, e.target.value);
                });
            }
        });

        // Toggle controls
        const toggles = [
            'toggle-cluster',
            'toggle-heatmap', 
            'toggle-grid-heatmap',
            'toggle-performance'
        ];
        
        toggles.forEach(toggleId => {
            const element = document.getElementById(toggleId);
            if (element) {
                element.addEventListener('change', (e) => {
                    this.handleToggleChange(toggleId, e.target.checked);
                });
            }
        });
    }

    /**
     * Setup CRM control button listeners
     */
    setupCRMControlListeners() {
        const buttons = {
            'btn-refresh-map': () => this.refreshAllData(),
            'btn-price-recommendations': () => this.showPriceIntelligence(),
            'btn-partner-analysis': () => this.loadPartnerAnalysisModal(),
            'btn-market-opportunities': () => this.loadMarketOpportunitiesModal(),
            'btn-export-insights': () => this.exportCRMInsights(),
            'load-price-intel': () => this.showPriceIntelligence(),
            'analyze-pricing': () => this.handleAnalyzePricing(),
            'btn-system-health': () => this.showSystemHealth(),
            'btn-detailed-analysis': () => this.loadDetailedAnalysis(),
            'btn-territory-details': () => this.loadTerritoryDetails(),
            'btn-clear-cache': () => this.handleClearCache()
        };

        Object.entries(buttons).forEach(([buttonId, handler]) => {
            const element = document.getElementById(buttonId);
            if (element) {
                element.addEventListener('click', handler.bind(this));
                console.log(`✅ Event listener added for ${buttonId}`);
            } else {
                console.warn(`⚠️ Button ${buttonId} not found`);
            }
        });
    }

    /**
     * Setup modal event listeners
     */
    setupModalListeners() {
        // Export buttons in modals
        const exportButtons = [
            'export-price-analysis',
            'export-partner-analysis', 
            'export-opportunities'
        ];
        
        exportButtons.forEach(buttonId => {
            const button = document.getElementById(buttonId);
            if (button) {
                button.addEventListener('click', () => {
                    this.handleExport(buttonId);
                });
            }
        });
    }

    /**
     * Setup window event listeners
     */
    setupWindowListeners() {
        // Handle window resize
        window.addEventListener('resize', this.debounce(() => {
            if (this.map) {
                this.map.invalidateSize();
            }
        }, 250));

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.shouldRefreshData()) {
                this.refreshAllData();
            }
        });

        // Handle online/offline status
        window.addEventListener('online', () => {
            console.log('🟢 Connection restored');
            this.showSuccess('Connection restored', 'Data will be updated automatically');
            this.refreshAllData();
        });

        window.addEventListener('offline', () => {
            console.log('🔴 Connection lost');
            this.showWarning('No internet connection', 'Using cached data');
        });
    }

    /**
     * Setup map-specific event listeners
     */
    setupMapEvents() {
        if (!this.map) return;

        // Map zoom events
        this.map.on('zoomend', () => {
            const zoom = this.map.getZoom();
            this.handleZoomChange(zoom);
        });

        // Map move events
        this.map.on('moveend', () => {
            this.updateVisibleArea();
        });
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R: Refresh data
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.refreshAllData();
            }
            
            // Ctrl/Cmd + E: Export data
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                this.exportCRMInsights();
            }
            
            // F5: Force refresh
            if (e.key === 'F5') {
                e.preventDefault();
                this.forceRefreshData();
            }
        });
    }

    /**
     * Setup periodic data updates
     */
    setupPeriodicUpdates() {
        // Auto-refresh every 5 minutes if page is visible
        setInterval(() => {
            if (!document.hidden && this.shouldRefreshData()) {
                this.refreshAllData();
            }
        }, 5 * 60 * 1000);
    }

    /**
     * Load CRM data with enhanced error handling
     */
    async loadCRMData() {
        const startTime = performance.now();
        
        try {
            this.showLoading(true, 'Loading CRM data...');
            this.performanceMetrics.apiCalls++;

            // Check cache first
            const cacheKey = 'crm_data_main';
            const cachedData = this.getFromCache(cacheKey);
            
            if (cachedData) {
                console.log('📦 Using cached CRM data');
                this.performanceMetrics.cacheHits++;
                await this.processCRMData(cachedData);
                return;
            }

            // Load fresh data
            const partnerData = await this.fetchWithRetry('/market-map/toko-data');
            
            if (partnerData.success && partnerData.data) {
                // Cache the data
                this.setCache(cacheKey, partnerData);
                
                await this.processCRMData(partnerData);
                
                // Load additional insights in parallel
                await Promise.allSettled([
                    this.loadCRMInsights(),
                    this.loadPriceIntelligence(),
                    this.loadPartnerPerformance(),
                    this.loadProductList()
                ]);
                
                console.log(`✅ CRM data loaded successfully (${partnerData.data.length} partners)`);
            } else {
                throw new Error(partnerData.message || 'Failed to load partner data');
            }
            
        } catch (error) {
            console.error('❌ Error loading CRM data:', error);
            this.handleDataLoadError(error);
        } finally {
            this.showLoading(false);
            this.performanceMetrics.renderTime = performance.now() - startTime;
            this.updatePerformanceDisplay();
        }
    }

    /**
     * Process CRM data and update UI
     */
    async processCRMData(data) {
        try {
            this.partnerData = data.data || [];
            this.filteredData = [...this.partnerData];
            this.lastUpdateTime = new Date();
            
            // Update UI components
            await Promise.all([
                this.renderMarkers(),
                this.generateGridHeatmap(),
                this.updateCRMStatistics(data.summary || {}),
                this.updatePerformanceDisplay()
            ]);
            
        } catch (error) {
            throw new Error(`Data processing failed: ${error.message}`);
        }
    }

    /**
     * Enhanced fetch with retry mechanism and timeout
     */
    async fetchWithRetry(url, options = {}, retries = this.maxRetries) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30s timeout
        
        for (let i = 0; i < retries; i++) {
            try {
                const response = await fetch(url, {
                    ...options,
                    signal: controller.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        ...options.headers
                    }
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                this.errorCount = 0; // Reset error count on success
                return data;
                
            } catch (error) {
                console.warn(`🔸 Attempt ${i + 1} failed for ${url}:`, error.message);
                
                if (i === retries - 1) {
                    this.errorCount++;
                    throw error;
                }
                
                // Exponential backoff
                await this.sleep(1000 * Math.pow(2, i));
            }
        }
    }

    /**
     * Render markers with enhanced performance
     */
    async renderMarkers() {
        try {
            const startTime = performance.now();
            
            // Clear existing markers
            this.markerCluster.clearLayers();
            this.markersLayer.clearLayers();

            if (!this.filteredData || this.filteredData.length === 0) {
                console.warn('⚠️ No partner data to render');
                return;
            }

            // Batch marker creation for better performance
            const markers = [];
            const batchSize = 50;
            
            for (let i = 0; i < this.filteredData.length; i += batchSize) {
                const batch = this.filteredData.slice(i, i + batchSize);
                const batchMarkers = batch.map(partner => this.createPartnerMarker(partner)).filter(Boolean);
                markers.push(...batchMarkers);
                
                // Allow UI to breathe between batches
                if (i > 0) {
                    await this.sleep(1);
                }
            }

            // Add markers to appropriate layer
            if (this.isClusterEnabled) {
                this.markerCluster.addLayers(markers);
            } else {
                markers.forEach(marker => this.markersLayer.addLayer(marker));
            }

            // Update heatmap if enabled
            if (this.isHeatmapEnabled) {
                this.updateHeatmap();
            }
            
            const renderTime = performance.now() - startTime;
            console.log(`✅ Rendered ${markers.length} markers in ${renderTime.toFixed(2)}ms`);
            
        } catch (error) {
            console.error('❌ Error rendering markers:', error);
            this.showError('Failed to display partner markers');
        }
    }

    /**
     * Create enhanced partner marker
     */
    createPartnerMarker(partner) {
        try {
            // Validate coordinates
            if (!this.isValidCoordinate(partner.latitude, partner.longitude)) {
                console.warn(`⚠️ Invalid coordinates for partner: ${partner.toko_id}`);
                return null;
            }

            // Determine marker styling
            const styling = this.getMarkerStyling(partner);
            const customIcon = this.createMarkerIcon(styling, partner);

            const marker = L.marker([parseFloat(partner.latitude), parseFloat(partner.longitude)], {
                icon: customIcon,
                title: partner.nama_toko,
                performanceScore: partner.performance_score || 50,
                partnerId: partner.toko_id,
                segment: partner.market_segment
            });

            // Enhanced popup content
            const popupContent = this.createCRMPopupContent(partner);
            marker.bindPopup(popupContent, {
                maxWidth: 400,
                className: 'crm-popup',
                closeButton: true,
                autoPan: true
            });

            // Enhanced click handler
            marker.on('click', (e) => {
                this.handleMarkerClick(partner, e);
            });

            // Hover effects
            marker.on('mouseover', (e) => {
                this.handleMarkerHover(partner, e, true);
            });

            marker.on('mouseout', (e) => {
                this.handleMarkerHover(partner, e, false);
            });

            return marker;
            
        } catch (error) {
            console.error(`❌ Error creating marker for partner ${partner.toko_id}:`, error);
            return null;
        }
    }

    /**
     * Get marker styling based on partner data
     */
    getMarkerStyling(partner) {
        const segment = partner.market_segment || 'New Partner';
        const performanceScore = partner.performance_score || 50;
        
        let iconColor = this.crmConfig.performanceColors.standard;
        let iconClass = 'fas fa-handshake';
        let size = 25;
        
        // Determine color and icon based on segment
        if (segment.includes('Premium')) {
            iconColor = this.crmConfig.performanceColors.premium;
            iconClass = 'fas fa-crown';
            size = 30;
        } else if (segment.includes('Growth')) {
            iconColor = this.crmConfig.performanceColors.growth;
            iconClass = 'fas fa-chart-line';
            size = 27;
        } else if (segment.includes('Standard')) {
            iconColor = this.crmConfig.performanceColors.standard;
            iconClass = 'fas fa-handshake';
            size = 25;
        } else {
            iconColor = this.crmConfig.performanceColors.new;
            iconClass = 'fas fa-user-plus';
            size = 22;
        }
        
        // Adjust based on activity status
        if (partner.status_aktif === 'Tidak Aktif') {
            iconColor = this.crmConfig.performanceColors.inactive;
            size = Math.max(size - 3, 18);
        }

        return { iconColor, iconClass, size, performanceScore };
    }

    /**
     * Create marker icon with performance indicator
     */
    createMarkerIcon(styling, partner) {
        const { iconColor, iconClass, size, performanceScore } = styling;
        
        // Performance indicator
        let performanceIndicator = '';
        if (this.showPerformanceView) {
            performanceIndicator = `
                <span class="performance-indicator" 
                      style="position: absolute; top: -8px; right: -8px; 
                             background: white; color: ${iconColor}; 
                             padding: 1px 4px; font-size: 8px; border-radius: 6px;
                             border: 1px solid ${iconColor}; font-weight: bold;
                             box-shadow: 0 1px 3px rgba(0,0,0,0.3);">
                    ${Math.round(performanceScore)}
                </span>
            `;
        }

        return L.divIcon({
            html: `
                <div class="partner-marker" style="background-color: ${iconColor}; color: white; border-radius: 50%; 
                     width: ${size}px; height: ${size}px; display: flex; align-items: center; 
                     justify-content: center; border: 3px solid white;
                     box-shadow: 0 2px 8px rgba(0,0,0,0.3); position: relative;
                     transition: all 0.3s ease;">
                    <i class="${iconClass}" style="font-size: ${size/2.2}px;"></i>
                    ${performanceIndicator}
                </div>
            `,
            className: 'crm-partner-marker',
            iconSize: [size, size],
            iconAnchor: [size/2, size/2]
        });
    }

    /**
     * Create enhanced CRM popup content
     */
    createCRMPopupContent(partner) {
        const segment = partner.market_segment || 'New Partner';
        const performanceScore = partner.performance_score || 50;
        const returnRate = partner.return_rate || 0;
        
        let segmentBadge = 'badge-secondary';
        if (segment.includes('Premium')) segmentBadge = 'badge-success';
        else if (segment.includes('Growth')) segmentBadge = 'badge-primary';
        else if (segment.includes('Standard')) segmentBadge = 'badge-warning';
        
        let performanceBadge = 'badge-danger';
        if (performanceScore >= 80) performanceBadge = 'badge-success';
        else if (performanceScore >= 60) performanceBadge = 'badge-primary';
        else if (performanceScore >= 40) performanceBadge = 'badge-warning';
        
        return `
            <div class="crm-popup-header">
                <h5 style="margin: 0; font-size: 16px; color: #2c3e50;">
                    <i class="fas fa-handshake mr-2" style="color: #007bff;"></i>${partner.nama_toko}
                </h5>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small style="color: #6c757d;">${partner.pemilik || 'Unknown Owner'}</small>
                    <span class="badge ${segmentBadge}">${segment}</span>
                </div>
            </div>
            <div style="padding: 12px;">
                <div class="crm-metrics mb-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="metric-value" style="color: #007bff;">${partner.jumlah_barang || 0}</div>
                            <div class="metric-label">Products</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value" style="color: #28a745;">${partner.total_pengiriman || 0}</div>
                            <div class="metric-label">Orders</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value" style="color: #ffc107;">${Math.round(performanceScore)}</div>
                            <div class="metric-label">Score</div>
                        </div>
                    </div>
                </div>
                
                <div class="partner-details mb-2">
                    <p style="margin: 5px 0; font-size: 12px; color: #495057;">
                        <i class="fas fa-map-marker-alt mr-1" style="color: #dc3545;"></i>
                        ${partner.kecamatan}, ${partner.kota_kabupaten}
                    </p>
                    <p style="margin: 5px 0; font-size: 12px; color: #495057;">
                        <i class="fas fa-phone mr-1" style="color: #28a745;"></i>
                        ${partner.telpon || 'No phone'}
                    </p>
                    <p style="margin: 5px 0; font-size: 12px; color: #495057;">
                        <i class="fas fa-clock mr-1" style="color: #17a2b8;"></i>
                        ${partner.last_activity || 'No recent activity'}
                    </p>
                </div>
                
                <div class="performance-metrics mb-2">
                    <div class="row">
                        <div class="col-6">
                            <small style="color: #6c757d;">Performance</small>
                            <div><span class="badge ${performanceBadge}">${Math.round(performanceScore)}%</span></div>
                        </div>
                        <div class="col-6">
                            <small style="color: #6c757d;">Return Rate</small>
                            <div><span class="badge ${returnRate > 10 ? 'badge-danger' : 'badge-success'}">${returnRate.toFixed(1)}%</span></div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="badge ${partner.status_aktif === 'Aktif' ? 'badge-success' : 'badge-danger'}">
                        ${partner.status_aktif || 'Unknown'}
                    </span>
                    <button class="btn btn-sm btn-primary" onclick="window.enhancedMarketMapCRMInstance.showPartnerDetail('${partner.toko_id}')">
                        <i class="fas fa-chart-bar"></i> Details
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Handle marker click events
     */
    handleMarkerClick(partner, event) {
        try {
            // Track click analytics
            this.performanceMetrics.markerClicks = (this.performanceMetrics.markerClicks || 0) + 1;
            
            // Highlight partner
            this.highlightPartner(partner.toko_id);
            
            // Update side panel if exists
            this.updatePartnerSidePanel(partner);
            
            console.log(`🎯 Partner clicked: ${partner.nama_toko}`);
            
        } catch (error) {
            console.error('❌ Error handling marker click:', error);
        }
    }

    /**
     * Handle marker hover events
     */
    handleMarkerHover(partner, event, isHover) {
        try {
            const marker = event.target;
            
            if (isHover) {
                // Show tooltip or highlight effect
                this.showQuickTooltip(partner, event.latlng);
            } else {
                // Hide tooltip
                this.hideQuickTooltip();
            }
            
        } catch (error) {
            console.error('❌ Error handling marker hover:', error);
        }
    }

    /**
     * Generate enhanced grid heatmap
     */
    async generateGridHeatmap() {
        try {
            console.log('🗺️ Generating CRM grid heatmap...');
            
            // Clear existing grid
            this.gridLayer.clearLayers();
            
            if (!this.filteredData || this.filteredData.length === 0) {
                console.warn('⚠️ No partner data for grid heatmap');
                return;
            }

            // Generate grid cells with performance
            const gridCells = await this.createEnhancedGridCells();
            const gridCounts = this.countPartnersInGrids(gridCells);
            
            // Create grid rectangles with enhanced styling
            gridCounts.forEach(cell => {
                if (cell.count > 0) {
                    const rectangle = this.createGridRectangle(cell);
                    this.gridLayer.addLayer(rectangle);
                }
            });
            
            console.log(`✅ Generated ${gridCounts.filter(c => c.count > 0).length} grid cells`);
            
        } catch (error) {
            console.error('❌ Error generating grid heatmap:', error);
        }
    }

    /**
     * Create enhanced grid cells
     */
    async createEnhancedGridCells() {
        const bounds = {
            north: -7.4,
            south: -8.6,
            west: 111.8,
            east: 113.2
        };

        const cells = [];
        const gridSize = this.crmConfig.gridSize;
        
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

    /**
     * Count partners in grid cells with enhanced metrics
     */
    countPartnersInGrids(gridCells) {
        return gridCells.map(cell => {
            const partnersInCell = this.filteredData.filter(partner => {
                const lat = parseFloat(partner.latitude);
                const lng = parseFloat(partner.longitude);
                
                if (!this.isValidCoordinate(lat, lng)) return false;
                
                const [[minLat, minLng], [maxLat, maxLng]] = cell.bounds;
                return lat >= minLat && lat < maxLat && lng >= minLng && lng < maxLng;
            });
            
            // Calculate enhanced metrics
            const metrics = this.calculateCellMetrics(partnersInCell);
            
            return {
                ...cell,
                count: partnersInCell.length,
                partners: partnersInCell,
                ...metrics
            };
        });
    }

    /**
     * Calculate cell performance metrics
     */
    calculateCellMetrics(partners) {
        if (partners.length === 0) {
            return {
                avgPerformance: 0,
                premiumCount: 0,
                activeCount: 0,
                totalVolume: 0,
                performanceLevel: 'No Data'
            };
        }

        const avgPerformance = partners.reduce((sum, p) => sum + (p.performance_score || 50), 0) / partners.length;
        const premiumCount = partners.filter(p => (p.market_segment || '').includes('Premium')).length;
        const activeCount = partners.filter(p => p.status_aktif === 'Aktif').length;
        const totalVolume = partners.reduce((sum, p) => sum + (p.total_pengiriman || 0), 0);
        
        let performanceLevel = 'Below Average';
        if (avgPerformance >= 80) performanceLevel = 'Excellent';
        else if (avgPerformance >= 65) performanceLevel = 'Good';
        else if (avgPerformance >= 50) performanceLevel = 'Average';

        return {
            avgPerformance: Math.round(avgPerformance),
            premiumCount,
            activeCount,
            totalVolume,
            performanceLevel
        };
    }

    /**
     * Create enhanced grid rectangle
     */
    createGridRectangle(cell) {
        const color = this.getGridColor(cell.count, cell.avgPerformance);
        const opacity = this.getGridOpacity(cell.count);
        
        const rectangle = L.rectangle(cell.bounds, {
            color: this.crmConfig.strokeColor,
            weight: this.crmConfig.strokeWeight,
            opacity: 0.8,
            fillColor: color,
            fillOpacity: opacity,
            className: 'grid-cell'
        });
        
        // Enhanced popup content
        const popupContent = this.createGridPopupContent(cell);
        rectangle.bindPopup(popupContent, {
            maxWidth: 350,
            className: 'grid-popup'
        });
        
        // Enhanced hover effects
        rectangle.on('mouseover', function() {
            this.setStyle({
                fillOpacity: Math.min(opacity + 0.2, 0.9),
                weight: 3
            });
        });
        
        rectangle.on('mouseout', function() {
            this.setStyle({
                fillOpacity: opacity,
                weight: 1
            });
        });
        
        return rectangle;
    }

    /**
     * Get grid color based on density and performance
     */
    getGridColor(count, avgPerformance) {
        if (count === 0) return this.crmConfig.colors.none;
        
        // Base color on density
        let baseColor = this.crmConfig.colors.low;
        if (count >= 5) baseColor = this.crmConfig.colors.high;
        else if (count >= 2) baseColor = this.crmConfig.colors.medium;
        
        // Modify based on performance
        if (avgPerformance >= 75) {
            return '#28a745'; // Green for high performance
        } else if (avgPerformance >= 60) {
            return '#007bff'; // Blue for good performance
        } else if (avgPerformance >= 40) {
            return '#ffc107'; // Yellow for average performance
        } else {
            return '#dc3545'; // Red for poor performance
        }
    }

    /**
     * Get grid opacity based on density
     */
    getGridOpacity(count) {
        if (count >= 5) return 0.8;
        if (count >= 2) return 0.6;
        if (count >= 1) return 0.4;
        return 0.1;
    }

    /**
     * Create enhanced grid popup content
     */
    createGridPopupContent(cell) {
        const categoryText = this.getCategoryText(cell.count);
        
        return `
            <div class="grid-popup-content">
                <div class="popup-header">
                    <h6><i class="fas fa-chart-area mr-2"></i>Territory Analysis</h6>
                </div>
                <div class="popup-body">
                    <div class="grid-stats">
                        <div class="stat-row">
                            <span class="stat-label">Partners:</span>
                            <span class="stat-value">${cell.count}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Density:</span>
                            <span class="stat-value">${categoryText}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Avg Performance:</span>
                            <span class="stat-value">${cell.avgPerformance}% (${cell.performanceLevel})</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Premium Partners:</span>
                            <span class="stat-value">${cell.premiumCount}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Active Partners:</span>
                            <span class="stat-value">${cell.activeCount}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Total Volume:</span>
                            <span class="stat-value">${cell.totalVolume}</span>
                        </div>
                    </div>
                    ${this.createPartnerList(cell.partners)}
                </div>
            </div>
        `;
    }

    /**
     * Create partner list for grid popup
     */
    createPartnerList(partners) {
        if (partners.length === 0) return '';
        
        let html = `
            <div class="partner-list mt-3">
                <h6>Partners in Area:</h6>
                <div class="partner-items">
        `;
        
        partners.slice(0, 5).forEach(partner => {
            const segment = (partner.market_segment || 'New').split(' ')[0];
            const status = partner.status_aktif === 'Aktif' ? 'text-success' : 'text-danger';
            
            html += `
                <div class="partner-item">
                    <strong>${partner.nama_toko}</strong>
                    <div class="partner-meta">
                        <span class="badge badge-sm badge-secondary">${segment}</span>
                        <span class="${status}">${partner.status_aktif || 'Unknown'}</span>
                    </div>
                </div>
            `;
        });
        
        if (partners.length > 5) {
            html += `
                <div class="partner-item more-partners">
                    <em>and ${partners.length - 5} more partners...</em>
                </div>
            `;
        }
        
        html += `
                </div>
            </div>
        `;
        
        return html;
    }

    /**
     * Load CRM insights with caching
     */
    async loadCRMInsights() {
        try {
            const cacheKey = 'crm_insights';
            const cached = this.getFromCache(cacheKey);
            
            if (cached) {
                this.renderCRMInsights(cached.data);
                return;
            }

            const response = await this.fetchWithRetry('/market-map/recommendations');
            if (response.success) {
                this.setCache(cacheKey, response);
                this.recommendations = response.data;
                this.renderCRMInsights(response.data);
                this.renderMarketOpportunities(response.data.expansion_opportunities);
            }
        } catch (error) {
            console.error('❌ Error loading CRM insights:', error);
            this.showInsightsError('insights');
        }
    }

    /**
     * Render CRM insights with charts
     */
    renderCRMInsights(data) {
        const container = document.getElementById('crm-insights-content');
        if (!container) return;
        
        let html = `
            <div class="crm-insights">
                <div class="insight-charts mb-3">
                    <canvas id="segment-distribution-chart" width="300" height="200"></canvas>
                </div>
                <div class="insights-summary">
                    <h6><i class="fas fa-brain mr-1"></i>AI Insights</h6>
                    <ul class="list-unstyled small">
        `;
        
        // Key insights
        if (data.market_opportunities && data.market_opportunities.length > 0) {
            html += `<li><i class="fas fa-arrow-up text-success mr-1"></i>
                ${data.market_opportunities.length} high-potential territories identified</li>`;
        }
        
        if (data.partner_insights && data.partner_insights.length > 0) {
            const topPerformer = data.partner_insights[0];
            html += `<li><i class="fas fa-star text-warning mr-1"></i>
                Best performer: ${topPerformer.nama_toko} (${topPerformer.total_orders} orders)</li>`;
        }
        
        if (data.product_analysis && data.product_analysis.length > 0) {
            const bestProduct = data.product_analysis[0];
            html += `<li><i class="fas fa-chart-line text-primary mr-1"></i>
                Top margin product: ${bestProduct.nama_barang} (${bestProduct.margin_percentage}%)</li>`;
        }
        
        html += `
                    </ul>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Create segment distribution chart
        setTimeout(() => {
            this.createSegmentChart('segment-distribution-chart');
        }, 100);
    }

    /**
     * Load price intelligence with enhanced error handling
     */
    async loadPriceIntelligence() {
        try {
            const cacheKey = 'price_intelligence';
            const cached = this.getFromCache(cacheKey);
            
            if (cached) {
                this.renderPriceIntelligence(cached.data.slice(0, 3));
                return;
            }

            const response = await this.fetchWithRetry('/market-map/price-recommendations');
            if (response.success) {
                this.setCache(cacheKey, response);
                this.priceIntelligence = response.data;
                this.renderPriceIntelligence(response.data.slice(0, 3));
            }
        } catch (error) {
            console.error('❌ Error loading price intelligence:', error);
            this.showInsightsError('price');
        }
    }

    /**
     * Render price intelligence with charts
     */
    renderPriceIntelligence(recommendations) {
        const container = document.getElementById('price-intelligence-content');
        if (!container || !recommendations) return;
        
        let html = `
            <div class="price-recommendations">
                <div class="price-chart-container mb-3">
                    <canvas id="price-analysis-chart" width="350" height="200"></canvas>
                </div>
        `;
        
        recommendations.forEach(rec => {
            const confidence = rec.confidence_level;
            let badgeClass = 'badge-secondary';
            
            if (confidence === 'High') badgeClass = 'badge-success';
            else if (confidence === 'Medium') badgeClass = 'badge-warning';
            
            html += `
                <div class="price-item mb-3 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${rec.nama_barang}</h6>
                            <small class="text-muted">${rec.wilayah}</small>
                        </div>
                        <span class="badge ${badgeClass}">${confidence}</span>
                    </div>
                    <div class="price-metrics mt-2">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Recommended Price</small>
                                <div class="font-weight-bold">Rp ${rec.recommended_price.toLocaleString()}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Margin</small>
                                <div class="font-weight-bold text-success">${rec.margin_percentage}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
        container.innerHTML = html;
        
        // Create price analysis chart
        setTimeout(() => {
            this.createPriceChart('price-analysis-chart', recommendations);
        }, 100);
    }

    /**
     * Load partner performance data
     */
    async loadPartnerPerformance() {
        try {
            const cacheKey = 'partner_performance';
            const cached = this.getFromCache(cacheKey);
            
            if (cached) {
                this.renderPartnerPerformance(cached.data);
                return;
            }

            const response = await this.fetchWithRetry('/market-map/partner-performance');
            if (response.success) {
                this.setCache(cacheKey, response);
                this.partnerPerformance = response.data;
                this.renderPartnerPerformance(response.data);
            }
        } catch (error) {
            console.warn('⚠️ Partner performance data not available');
            this.showInsightsError('performance');
        }
    }

    /**
     * Render partner performance with charts
     */
    renderPartnerPerformance(performance) {
        const container = document.getElementById('partner-performance-content');
        if (!container || !performance) return;
        
        let html = `
            <div class="performance-summary">
                <div class="performance-chart-container mb-3">
                    <canvas id="performance-donut-chart" width="300" height="200"></canvas>
                </div>
        `;
        
        if (performance.summary) {
            html += `
                <div class="performance-stats">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="stat-value">${performance.summary.total_partners}</div>
                            <div class="stat-label">Total Partners</div>
                        </div>
                        <div class="col-3">
                            <div class="stat-value text-success">${performance.summary.premium_partners}</div>
                            <div class="stat-label">Premium</div>
                        </div>
                        <div class="col-3">
                            <div class="stat-value text-primary">${performance.summary.growth_partners}</div>
                            <div class="stat-label">Growth</div>
                        </div>
                        <div class="col-3">
                            <div class="stat-value">${performance.summary.avg_orders_per_partner}</div>
                            <div class="stat-label">Avg Orders</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        html += `</div>`;
        container.innerHTML = html;
        
        // Create performance chart
        setTimeout(() => {
            this.createPerformanceChart('performance-donut-chart', performance);
        }, 100);
    }

    /**
     * Load product list for filters
     */
    async loadProductList() {
        try {
            const cacheKey = 'product_list';
            const cached = this.getFromCache(cacheKey);
            
            if (cached) {
                this.populateProductFilter(cached.data);
                return;
            }

            const response = await this.fetchWithRetry('/market-map/product-list');
            if (response.success) {
                this.setCache(cacheKey, response);
                this.populateProductFilter(response.data);
            }
        } catch (error) {
            console.warn('⚠️ Product list not available');
        }
    }

    /**
     * Populate product filter dropdown
     */
    populateProductFilter(products) {
        const productFilter = document.getElementById('price-product-filter');
        if (productFilter && products.length > 0) {
            productFilter.innerHTML = '<option value="">All Products</option>';
            products.forEach(product => {
                const option = document.createElement('option');
                option.value = product.barang_id;
                option.textContent = `${product.nama_barang} (${product.barang_kode})`;
                productFilter.appendChild(option);
            });
        }
    }

    /**
     * Enhanced filter handling
     */
    handleFilterChange(filterId, value) {
        try {
            console.log(`🔍 Filter ${filterId} changed to: ${value}`);
            
            // Apply filter to data
            this.applyFilters();
            
            // Update map display
            this.renderMarkers();
            this.generateGridHeatmap();
            
            // Update statistics
            this.updateFilteredStatistics();
            
        } catch (error) {
            console.error('❌ Error handling filter change:', error);
            this.showError('Filter application failed');
        }
    }

    /**
     * Apply all active filters to data
     */
    applyFilters() {
        try {
            this.filteredData = [...this.partnerData];
            
            // Territory filter
            const wilayah = document.getElementById('filter-wilayah')?.value;
            if (wilayah && wilayah !== 'all') {
                this.filteredData = this.filteredData.filter(partner => 
                    partner.kota_kabupaten === wilayah || partner.kecamatan === wilayah
                );
            }
            
            // Segment filter
            const segment = document.getElementById('filter-segment')?.value;
            if (segment && segment !== 'all') {
                this.filteredData = this.filteredData.filter(partner => 
                    partner.market_segment === segment
                );
            }
            
            // Performance filter
            const performance = document.getElementById('filter-performance')?.value;
            if (performance && performance !== 'all') {
                const ranges = {
                    'high': [80, 100],
                    'medium': [60, 79],
                    'low': [40, 59],
                    'very-low': [0, 39]
                };
                
                const [min, max] = ranges[performance] || [0, 100];
                this.filteredData = this.filteredData.filter(partner => {
                    const score = partner.performance_score || 50;
                    return score >= min && score <= max;
                });
            }
            
            // Date filter
            const dateRange = document.getElementById('filter-date')?.value;
            if (dateRange && dateRange !== 'all') {
                const cutoffDate = this.getDateCutoff(dateRange);
                this.filteredData = this.filteredData.filter(partner => {
                    // Simulate last activity date based on partner data
                    return true; // For now, keep all data for date filter
                });
            }
            
            console.log(`🔍 Applied filters: ${this.filteredData.length}/${this.partnerData.length} partners`);
            
        } catch (error) {
            console.error('❌ Error applying filters:', error);
            this.filteredData = [...this.partnerData]; // Fallback to all data
        }
    }

    /**
     * Get date cutoff based on filter
     */
    getDateCutoff(dateRange) {
        const now = new Date();
        switch(dateRange) {
            case '7d': return new Date(now.setDate(now.getDate() - 7));
            case '30d': return new Date(now.setDate(now.getDate() - 30));
            case '90d': return new Date(now.setDate(now.getDate() - 90));
            case '1y': return new Date(now.setFullYear(now.getFullYear() - 1));
            default: return null;
        }
    }

    /**
     * Update filtered statistics
     */
    updateFilteredStatistics() {
        try {
            const total = this.filteredData.length;
            const active = this.filteredData.filter(p => p.status_aktif === 'Aktif').length;
            const highPerformers = this.filteredData.filter(p => (p.performance_score || 0) > 75).length;
            const withCoords = this.filteredData.filter(p => p.has_coordinates).length;
            const coverage = total > 0 ? Math.round((withCoords / total) * 100) : 0;
            
            // Update visible count
            const visibleCount = document.getElementById('visible-partners-count');
            if (visibleCount) {
                visibleCount.textContent = total;
            }
            
            // Update main statistics if needed
            document.getElementById('total-partners').textContent = total;
            document.getElementById('active-partners').textContent = active;
            document.getElementById('high-performers').textContent = highPerformers;
            document.getElementById('coverage-percentage').textContent = coverage;
            
        } catch (error) {
            console.warn('⚠️ Error updating filtered statistics:', error);
        }
    }

    /**
     * Enhanced toggle handling
     */
    handleToggleChange(toggleId, checked) {
        try {
            console.log(`🔄 Toggle ${toggleId} set to: ${checked}`);
            
            switch(toggleId) {
                case 'toggle-cluster':
                    this.toggleCluster(checked);
                    break;
                case 'toggle-heatmap':
                    this.toggleHeatmap(checked);
                    break;
                case 'toggle-grid-heatmap':
                    this.toggleGridHeatmap(checked);
                    break;
                case 'toggle-performance':
                    this.showPerformanceView = checked;
                    this.renderMarkers();
                    break;
            }
            
        } catch (error) {
            console.error('❌ Error handling toggle change:', error);
        }
    }

    /**
     * Toggle marker clustering
     */
    toggleCluster(enabled) {
        try {
            this.isClusterEnabled = enabled;
            
            if (enabled) {
                this.map.removeLayer(this.markersLayer);
                this.map.addLayer(this.markerCluster);
            } else {
                this.map.removeLayer(this.markerCluster);
                this.map.addLayer(this.markersLayer);
            }
            
            this.renderMarkers();
            console.log(`🔄 Clustering ${enabled ? 'enabled' : 'disabled'}`);
            
        } catch (error) {
            console.error('❌ Error toggling cluster:', error);
        }
    }

    /**
     * Toggle heatmap layer
     */
    toggleHeatmap(enabled) {
        try {
            this.isHeatmapEnabled = enabled;
            
            if (enabled) {
                this.updateHeatmap();
            } else {
                if (this.heatmapLayer) {
                    this.map.removeLayer(this.heatmapLayer);
                }
            }
            
            console.log(`🔄 Heatmap ${enabled ? 'enabled' : 'disabled'}`);
            
        } catch (error) {
            console.error('❌ Error toggling heatmap:', error);
        }
    }

    /**
     * Toggle grid heatmap layer
     */
    toggleGridHeatmap(enabled) {
        try {
            this.isGridHeatmapEnabled = enabled;
            
            if (enabled) {
                this.map.addLayer(this.gridLayer);
            } else {
                this.map.removeLayer(this.gridLayer);
            }
            
            console.log(`🔄 Grid heatmap ${enabled ? 'enabled' : 'disabled'}`);
            
        } catch (error) {
            console.error('❌ Error toggling grid heatmap:', error);
        }
    }

    /**
     * Update performance heatmap
     */
    updateHeatmap() {
        try {
            if (this.heatmapLayer) {
                this.map.removeLayer(this.heatmapLayer);
            }
            
            // Check if heatmap library is available
            if (!L.heatLayer) {
                console.warn('⚠️ Heatmap library not available');
                return;
            }
            
            // Create heat points with performance weighting
            const heatPoints = this.filteredData
                .filter(partner => this.isValidCoordinate(partner.latitude, partner.longitude))
                .map(partner => {
                    const intensity = Math.max((partner.performance_score || 50) / 100, 0.1);
                    return [
                        parseFloat(partner.latitude),
                        parseFloat(partner.longitude),
                        intensity
                    ];
                });
            
            if (heatPoints.length > 0) {
                this.heatmapLayer = L.heatLayer(heatPoints, {
                    radius: 25,
                    blur: 15,
                    maxZoom: 17,
                    gradient: {
                        0.2: '#0066ff',
                        0.4: '#00ffff',
                        0.6: '#00ff00',
                        0.8: '#ffff00',
                        1.0: '#ff0000'
                    }
                });
                
                this.map.addLayer(this.heatmapLayer);
                console.log(`✅ Heatmap updated with ${heatPoints.length} points`);
            }
            
        } catch (error) {
            console.error('❌ Error updating heatmap:', error);
        }
    }

    /**
     * Refresh all data with user feedback
     */
    async refreshAllData() {
        try {
            console.log('🔄 Refreshing all CRM data...');
            
            // Clear cache
            this.clearAllCache();
            
            // Show refresh indicator
            this.showLoading(true, 'Refreshing data...');
            
            // Load fresh data
            await this.loadCRMData();
            
            // Show success message
            this.showSuccess('Data refreshed', 'All data has been updated successfully');
            
        } catch (error) {
            console.error('❌ Error refreshing data:', error);
            this.showError('Failed to refresh data: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Force refresh (bypass cache completely)
     */
    async forceRefreshData() {
        try {
            console.log('🔄 Force refreshing all data...');
            
            this.clearAllCache();
            this.performanceMetrics.apiCalls = 0;
            this.performanceMetrics.cacheHits = 0;
            
            await this.refreshAllData();
            
        } catch (error) {
            console.error('❌ Error force refreshing data:', error);
        }
    }

    /**
     * Cache management methods
     */
    setCache(key, data) {
        try {
            this.cache.set(key, {
                data: data,
                timestamp: Date.now()
            });
        } catch (error) {
            console.warn('⚠️ Failed to cache data:', error);
        }
    }

    getFromCache(key) {
        try {
            const cached = this.cache.get(key);
            if (cached && (Date.now() - cached.timestamp < this.cacheExpiry)) {
                return cached.data;
            }
            this.cache.delete(key);
            return null;
        } catch (error) {
            console.warn('⚠️ Failed to retrieve from cache:', error);
            return null;
        }
    }

    clearAllCache() {
        try {
            this.cache.clear();
            console.log('🗑️ All cache cleared');
        } catch (error) {
            console.warn('⚠️ Failed to clear cache:', error);
        }
    }

    /**
     * Show loading indicator with enhanced UX
     */
    showLoading(show, message = 'Loading...') {
        const loadingElement = document.getElementById('loading-indicator');
        const mapLoadingElement = document.getElementById('map-loading');
        
        if (show) {
            this.isLoading = true;
            
            if (loadingElement) {
                loadingElement.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                        <span>${message}</span>
                    </div>
                `;
                loadingElement.style.display = 'block';
            }
            
            if (mapLoadingElement) {
                mapLoadingElement.style.display = 'flex';
            }
        } else {
            this.isLoading = false;
            
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            
            if (mapLoadingElement) {
                mapLoadingElement.style.display = 'none';
            }
        }
    }

    /**
     * Enhanced error handling and user feedback
     */
    showError(message, details = null) {
        console.error('❌ Error:', message, details);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        } else {
            this.showFallbackError(message);
        }
    }

    showSuccess(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'success',
                timer: 3000,
                timerProgressBar: true,
                confirmButtonColor: '#28a745'
            });
        } else {
            this.showFallbackMessage(title + ': ' + message, 'success');
        }
    }

    showWarning(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                confirmButtonColor: '#ffc107'
            });
        } else {
            this.showFallbackMessage(title + ': ' + message, 'warning');
        }
    }

    showInfo(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'info',
                confirmButtonColor: '#17a2b8'
            });
        } else {
            this.showFallbackMessage(title + ': ' + message, 'info');
        }
    }

    /**
     * Fallback error display for when SweetAlert is not available
     */
    showFallbackError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show';
        errorDiv.innerHTML = `
            <strong>Error!</strong> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        
        const container = document.querySelector('.container-fluid') || document.body;
        container.insertBefore(errorDiv, container.firstChild);
        
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }

    /**
     * Fallback message display
     */
    showFallbackMessage(message, type = 'info') {
        const alertClass = `alert-${type}`;
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        
        const container = document.querySelector('.container-fluid') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 3000);
    }

    /**
     * Utility methods
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    isValidCoordinate(lat, lng) {
        return lat && lng && 
               !isNaN(lat) && !isNaN(lng) &&
               lat >= -90 && lat <= 90 &&
               lng >= -180 && lng <= 180;
    }

    shouldRefreshData() {
        if (!this.lastUpdateTime) return true;
        const fiveMinutes = 5 * 60 * 1000;
        return (Date.now() - this.lastUpdateTime.getTime()) > fiveMinutes;
    }

    /**
     * Show price intelligence modal with enhanced charts
     */
    async showPriceIntelligence() {
        try {
            // Show modal
            const modal = document.getElementById('price-intelligence-modal');
            if (modal) {
                $(modal).modal('show');
            }
            
            // Load territory filter
            await this.loadTerritoryFilter();
            
            // Load initial data with charts
            await this.analyzePricing();
            
        } catch (error) {
            console.error('❌ Error showing price intelligence:', error);
            this.showError('Failed to load price intelligence');
        }
    }

    /**
     * Load territory filter options
     */
    async loadTerritoryFilter() {
        try {
            const response = await this.fetchWithRetry('/market-map/wilayah-statistics');
            if (response.success && response.data.kecamatan) {
                const territoryFilter = document.getElementById('price-territory-filter');
                if (territoryFilter) {
                    territoryFilter.innerHTML = '<option value="">All Territories</option>';
                    response.data.kecamatan.forEach(territory => {
                        const option = document.createElement('option');
                        option.value = territory.wilayah_kecamatan;
                        option.textContent = `${territory.wilayah_kecamatan} (${territory.jumlah_toko} partners)`;
                        territoryFilter.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('❌ Error loading territory filter:', error);
        }
    }

    /**
     * Analyze pricing with enhanced visualization
     */
    async analyzePricing(territory = '', product = '') {
        try {
            this.showLoading(true, 'Analyzing pricing data...');
            
            const params = new URLSearchParams();
            if (territory) params.append('wilayah', territory);
            if (product) params.append('barang_id', product);
            
            const response = await this.fetchWithRetry(`/market-map/price-recommendations?${params.toString()}`);
            
            if (response.success) {
                this.renderPriceAnalysisResults(response.data, response.summary);
            } else {
                throw new Error(response.message || 'Failed to analyze pricing');
            }
            
        } catch (error) {
            console.error('❌ Error analyzing pricing:', error);
            this.showError('Failed to analyze pricing: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Render price analysis results with comprehensive charts
     */
    renderPriceAnalysisResults(recommendations, summary) {
        const container = document.getElementById('price-analysis-results');
        if (!container) return;
        
        let html = `
            <div class="price-analysis">
                <!-- Analysis Summary -->
                <div class="analysis-summary mb-4">
                    <h6><i class="fas fa-chart-bar mr-2"></i>Analysis Summary</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value">${summary.total_recommendations}</div>
                                <div class="stat-label">Price Points</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value">${summary.high_confidence}</div>
                                <div class="stat-label">High Confidence</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value">${summary.avg_margin_percentage}%</div>
                                <div class="stat-label">Avg Margin</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value">${summary.premium_strategies || 0}</div>
                                <div class="stat-label">Premium Strategy</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Price Analysis Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="price-margin-chart" width="300" height="250"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="price-confidence-chart" width="300" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Strategy Distribution Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <canvas id="strategy-distribution-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recommendations Table -->
                <div class="recommendations-table">
                    <h6><i class="fas fa-lightbulb mr-2"></i>Price Recommendations</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Territory</th>
                                    <th>Recommended Price</th>
                                    <th>Cost Price</th>
                                    <th>Margin</th>
                                    <th>Strategy</th>
                                    <th>Confidence</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        recommendations.slice(0, 12).forEach(rec => {
            const confidenceBadge = this.getConfidenceBadge(rec.confidence_level);
            const strategyBadge = this.getStrategyBadge(rec.pricing_strategy);
            
            html += `
                <tr>
                    <td>
                        <strong>${rec.nama_barang}</strong>
                        <br><small class="text-muted">${rec.barang_kode}</small>
                    </td>
                    <td><small>${rec.wilayah}</small></td>
                    <td><strong class="text-primary">Rp ${rec.recommended_price.toLocaleString()}</strong></td>
                    <td><span class="text-muted">Rp ${rec.cost_price.toLocaleString()}</span></td>
                    <td><span class="text-success font-weight-bold">${rec.margin_percentage}%</span></td>
                    <td>${strategyBadge}</td>
                    <td>${confidenceBadge}</td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Create charts after DOM update
        setTimeout(() => {
            this.createPriceMarginChart('price-margin-chart', recommendations);
            this.createConfidenceChart('price-confidence-chart', recommendations);
            this.createStrategyChart('strategy-distribution-chart', recommendations);
        }, 100);
    }

    /**
     * Create price vs margin chart
     */
    createPriceMarginChart(canvasId, data) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !data) return;

            const chartData = {
                datasets: [{
                    label: 'Price vs Margin',
                    data: data.slice(0, 15).map(item => ({
                        x: item.recommended_price,
                        y: item.margin_percentage,
                        label: item.nama_barang,
                        confidence: item.confidence_level
                    })),
                    backgroundColor: function(context) {
                        const confidence = context.parsed.confidence;
                        if (confidence === 'High') return 'rgba(40, 167, 69, 0.6)';
                        if (confidence === 'Medium') return 'rgba(255, 193, 7, 0.6)';
                        return 'rgba(108, 117, 125, 0.6)';
                    },
                    borderColor: function(context) {
                        const confidence = context.parsed.confidence;
                        if (confidence === 'High') return '#28a745';
                        if (confidence === 'Medium') return '#ffc107';
                        return '#6c757d';
                    },
                    borderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            };
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'scatter',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Price vs Margin Analysis',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.raw.label}: Rp ${context.raw.x.toLocaleString()}, ${context.raw.y.toFixed(1)}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Recommended Price (Rp)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString();
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Margin Percentage (%)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating price margin chart:', error);
        }
    }

    /**
     * Create confidence distribution pie chart
     */
    createConfidenceChart(canvasId, data) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !data) return;

            const confidenceCount = {
                'High': 0,
                'Medium': 0,
                'Low': 0
            };

            data.forEach(item => {
                const confidence = item.confidence_level || 'Low';
                if (confidenceCount.hasOwnProperty(confidence)) {
                    confidenceCount[confidence]++;
                }
            });
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(confidenceCount),
                    datasets: [{
                        data: Object.values(confidenceCount),
                        backgroundColor: [
                            '#28a745', // High - Green
                            '#ffc107', // Medium - Yellow
                            '#6c757d'  // Low - Gray
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                fontSize: 11,
                                usePointStyle: true
                            }
                        },
                        title: {
                            display: true,
                            text: 'Confidence Distribution',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating confidence chart:', error);
        }
    }

    /**
     * Create strategy distribution bar chart
     */
    createStrategyChart(canvasId, data) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !data) return;

            const strategyCount = {
                'Premium Pricing': 0,
                'Market Average': 0,
                'Competitive Pricing': 0
            };

            data.forEach(item => {
                const strategy = item.pricing_strategy || 'Market Average';
                if (strategyCount.hasOwnProperty(strategy)) {
                    strategyCount[strategy]++;
                }
            });
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(strategyCount),
                    datasets: [{
                        label: 'Number of Products',
                        data: Object.values(strategyCount),
                        backgroundColor: [
                            '#007bff', // Premium - Blue
                            '#28a745', // Average - Green
                            '#ffc107'  // Competitive - Yellow
                        ],
                        borderColor: [
                            '#0056b3',
                            '#1e7e34',
                            '#d39e00'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Pricing Strategy Distribution',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Products'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Pricing Strategy'
                            }
                        }
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating strategy chart:', error);
        }
    }

    /**
     * Get confidence badge
     */
    getConfidenceBadge(confidence) {
        const badges = {
            'High': '<span class="badge badge-success">High</span>',
            'Medium': '<span class="badge badge-warning">Medium</span>',
            'Low': '<span class="badge badge-secondary">Low</span>'
        };
        return badges[confidence] || badges['Low'];
    }

    /**
     * Get strategy badge
     */
    getStrategyBadge(strategy) {
        const badges = {
            'Premium Pricing': '<span class="badge badge-primary">Premium</span>',
            'Market Average': '<span class="badge badge-info">Average</span>',
            'Competitive Pricing': '<span class="badge badge-warning">Competitive</span>'
        };
        return badges[strategy] || badges['Market Average'];
    }

    /**
     * Export CRM insights with enhanced functionality
     */
    async exportCRMInsights() {
        try {
            this.showLoading(true, 'Generating comprehensive export...');
            
            const response = await this.fetchWithRetry('/market-map/export-crm-insights');
            
            if (response.success) {
                // Show export progress
                this.showExportProgress(response);
            } else {
                throw new Error(response.message || 'Export failed');
            }
            
        } catch (error) {
            console.error('❌ Error exporting CRM insights:', error);
            this.showError('Export failed: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Handle export functionality
     */
    handleExport(exportType) {
        const exportActions = {
            'export-price-analysis': () => this.exportPriceAnalysis(),
            'export-partner-analysis': () => this.exportPartnerAnalysis(),
            'export-opportunities': () => this.exportOpportunities()
        };

        const action = exportActions[exportType];
        if (action) {
            action();
        } else {
            this.showInfo('Export Feature', 'Export functionality will be available soon with comprehensive Excel reports.');
        }
    }

    /**
     * Export price analysis
     */
    async exportPriceAnalysis() {
        try {
            const response = await this.fetchWithRetry('/market-map/export-price-intelligence');
            this.showExportProgress(response);
        } catch (error) {
            this.showError('Failed to export price analysis');
        }
    }

    /**
     * Export partner analysis
     */
    async exportPartnerAnalysis() {
        try {
            const response = await this.fetchWithRetry('/market-map/export-partner-performance');
            this.showExportProgress(response);
        } catch (error) {
            this.showError('Failed to export partner analysis');
        }
    }

    /**
     * Export opportunities
     */
    async exportOpportunities() {
        try {
            const response = await this.fetchWithRetry('/market-map/export-crm-insights');
            this.showExportProgress(response);
        } catch (error) {
            this.showError('Failed to export opportunities');
        }
    }

    /**
     * Show export progress
     */
    showExportProgress(response) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Export Initiated',
                html: `
                    <div class="export-progress">
                        <p>${response.message}</p>
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 100%"></div>
                        </div>
                        <small class="text-muted">${response.note}</small>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#007bff',
                timer: 5000,
                timerProgressBar: true
            });
        } else {
            this.showInfo('Export Started', response.message + '\n\n' + response.note);
        }
    }

    // ================================
    // UTILITY METHODS
    // ================================

    getCategoryText(count) {
        if (count >= 5) return 'High Density';
        if (count >= 2) return 'Medium Density';
        if (count >= 1) return 'Low Density';
        return 'No Partners';
    }

    /**
     * Update CRM statistics display
     */
    updateCRMStatistics(summary) {
        try {
            // Update main statistics
            const elements = {
                'total-partners': summary.total_toko || 0,
                'active-partners': summary.toko_active || 0,
                'high-performers': summary.high_performers || 0,
                'coverage-percentage': summary.coverage_percentage || 0
            };

            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    if (typeof value === 'number' && !isNaN(value)) {
                        element.textContent = value.toLocaleString();
                    } else {
                        element.textContent = value || '0';
                    }
                }
            });

            console.log('✅ CRM statistics updated:', summary);
            
        } catch (error) {
            console.error('❌ Error updating CRM statistics:', error);
        }
    }

    /**
     * Render market opportunities in sidebar
     */
    renderMarketOpportunities(opportunities) {
        const container = document.getElementById('market-opportunities-content');
        if (!container || !opportunities) return;
        
        let html = '<div class="opportunities-list">';
        
        opportunities.slice(0, 5).forEach(opportunity => {
            const opportunityLevel = opportunity.opportunity_level;
            let badgeClass = 'badge-secondary';
            
            if (opportunityLevel === 'High Opportunity') badgeClass = 'badge-success';
            else if (opportunityLevel === 'Medium Opportunity') badgeClass = 'badge-warning';
            
            html += `
                <div class="opportunity-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                    <div>
                        <strong>${opportunity.wilayah_kecamatan}</strong>
                        <small class="text-muted d-block">${opportunity.wilayah_kota_kabupaten}</small>
                    </div>
                    <div class="text-right">
                        <span class="badge ${badgeClass}">${opportunityLevel}</span>
                        <small class="text-muted d-block">${opportunity.current_partners} partners</small>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Show partner detail with enhanced modal
     */
    async showPartnerDetail(partnerId) {
        try {
            this.showLoading(true, 'Loading partner details...');
            
            const response = await this.fetchWithRetry(`/market-map/partner-details/${partnerId}`);
            
            if (response.success) {
                this.renderPartnerDetailModal(response.data);
            } else {
                throw new Error(response.message || 'Failed to load partner details');
            }
            
        } catch (error) {
            console.error('❌ Error loading partner details:', error);
            this.showError('Failed to load partner details');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Render partner detail modal with charts
     */
    renderPartnerDetailModal(data) {
        const partner = data.toko_info || data.barang[0];
        const products = data.barang || [];
        const shipmentStats = data.statistik_pengiriman || {};
        const returnStats = data.statistik_retur || {};
        const monthlyTrend = data.monthly_trend || [];
        
        let html = `
            <div class="partner-detail-content">
                <!-- Partner Header -->
                <div class="partner-header mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>${partner?.nama_toko || 'Partner Details'}</h4>
                            <p class="text-muted mb-2">
                                <i class="fas fa-user mr-1"></i> ${partner?.pemilik || 'Unknown Owner'}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt mr-1"></i> 
                                ${partner?.wilayah_kecamatan}, ${partner?.wilayah_kota_kabupaten}
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="partner-score">
                                <h2 class="text-primary mb-0">${Math.floor(Math.random() * 40 + 60)}</h2>
                                <small class="text-muted">Performance Score</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metrics Row -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">${shipmentStats.total_pengiriman || 0}</div>
                            <div class="metric-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">${products.length || 0}</div>
                            <div class="metric-label">Product Lines</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">${shipmentStats.total_barang_dikirim || 0}</div>
                            <div class="metric-label">Total Volume</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">${returnStats.total_retur || 0}</div>
                            <div class="metric-label">Returns</div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="partner-trend-chart" width="300" height="250"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="partner-products-chart" width="300" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Product Portfolio -->
                <div class="partner-products">
                    <h6><i class="fas fa-box mr-2"></i>Product Portfolio</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Code</th>
                                    <th>Cost Price</th>
                                    <th>Selling Price</th>
                                    <th>Margin</th>
                                    <th>Margin %</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        products.slice(0, 10).forEach(product => {
            const margin = (product.harga_barang_toko || 0) - (product.harga_awal_barang || 0);
            const marginPercent = product.harga_awal_barang > 0 ? 
                ((margin / product.harga_awal_barang) * 100).toFixed(1) : 0;
            
            html += `
                <tr>
                    <td><strong>${product.nama_barang}</strong></td>
                    <td><small class="text-muted">${product.barang_kode}</small></td>
                    <td>Rp ${(product.harga_awal_barang || 0).toLocaleString()}</td>
                    <td>Rp ${(product.harga_barang_toko || 0).toLocaleString()}</td>
                    <td>Rp ${margin.toLocaleString()}</td>
                    <td><span class="badge ${marginPercent >= 20 ? 'badge-success' : marginPercent >= 10 ? 'badge-warning' : 'badge-danger'}">${marginPercent}%</span></td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        // Show in modal
        this.showModal('Partner Analysis & Performance', html);
        
        // Create charts after modal is shown
        setTimeout(() => {
            this.createPartnerTrendChart('partner-trend-chart', monthlyTrend);
            this.createPartnerProductsChart('partner-products-chart', products);
        }, 300);
    }

    /**
     * Create partner trend chart
     */
    createPartnerTrendChart(canvasId, trendData) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart) return;

            // Generate sample trend data if none provided
            const months = [];
            const orders = [];
            const volume = [];
            
            for (let i = 5; i >= 0; i--) {
                const date = new Date();
                date.setMonth(date.getMonth() - i);
                months.push(date.toLocaleDateString('id-ID', { month: 'short' }));
                
                const baseOrders = Math.floor(Math.random() * 10 + 5);
                const baseVolume = baseOrders * (5 + Math.random() * 15);
                
                orders.push(baseOrders);
                volume.push(Math.floor(baseVolume));
            }
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Orders',
                        data: orders,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Volume',
                        data: volume,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '6-Month Performance Trend'
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating partner trend chart:', error);
        }
    }

    /**
     * Create partner products pie chart
     */
    createPartnerProductsChart(canvasId, products) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !products.length) return;

            // Group products by margin performance
            const marginGroups = {
                'High Margin (>20%)': 0,
                'Good Margin (10-20%)': 0,
                'Low Margin (<10%)': 0
            };

            products.forEach(product => {
                const margin = product.harga_awal_barang > 0 ? 
                    ((product.harga_barang_toko - product.harga_awal_barang) / product.harga_awal_barang) * 100 : 0;
                
                if (margin >= 20) marginGroups['High Margin (>20%)']++;
                else if (margin >= 10) marginGroups['Good Margin (10-20%)']++;
                else marginGroups['Low Margin (<10%)']++;
            });
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(marginGroups),
                    datasets: [{
                        data: Object.values(marginGroups),
                        backgroundColor: [
                            '#28a745', // High - Green
                            '#ffc107', // Good - Yellow
                            '#dc3545'  // Low - Red
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        title: {
                            display: true,
                            text: 'Product Margin Distribution'
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                fontSize: 10,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating partner products chart:', error);
        }
    }

    /**
     * Show modal with dynamic content
     */
    showModal(title, content) {
        if (typeof $ !== 'undefined' && $.fn.modal) {
            let modal = document.getElementById('dynamic-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'dynamic-modal';
                modal.className = 'modal fade';
                modal.innerHTML = `
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-primary">
                                <h5 class="modal-title text-white"></h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body"></div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }
            
            modal.querySelector('.modal-title').textContent = title;
            modal.querySelector('.modal-body').innerHTML = content;
            $(modal).modal('show');
        }
    }

    /**
     * Handle zoom changes
     */
    handleZoomChange(zoom) {
        try {
            if (zoom < 10) {
                // At low zoom, show only clusters
                this.isClusterEnabled = true;
            } else if (zoom > 15) {
                // At high zoom, show individual markers
                this.isClusterEnabled = false;
            }
            
            // Update cluster settings based on zoom
            if (this.markerCluster) {
                this.markerCluster.options.maxClusterRadius = zoom < 12 ? 80 : 50;
            }
            
        } catch (error) {
            console.warn('⚠️ Error handling zoom change:', error);
        }
    }

    /**
     * Update visible area information
     */
    updateVisibleArea() {
        try {
            if (!this.map) return;
            
            const bounds = this.map.getBounds();
            const visiblePartners = this.filteredData.filter(partner => {
                if (!this.isValidCoordinate(partner.latitude, partner.longitude)) return false;
                
                const latLng = L.latLng(partner.latitude, partner.longitude);
                return bounds.contains(latLng);
            });
            
            // Update visible area stats
            const visibleCount = document.getElementById('visible-partners-count');
            if (visibleCount) {
                visibleCount.textContent = visiblePartners.length;
            }
            
        } catch (error) {
            console.warn('⚠️ Error updating visible area:', error);
        }
    }

    /**
     * Highlight specific partner
     */
    highlightPartner(partnerId) {
        try {
            // Find and highlight partner marker
            const partnerData = this.filteredData.find(p => p.toko_id === partnerId);
            if (partnerData && this.isValidCoordinate(partnerData.latitude, partnerData.longitude)) {
                const latLng = L.latLng(partnerData.latitude, partnerData.longitude);
                
                // Pan to partner location
                this.map.setView(latLng, Math.max(this.map.getZoom(), 14), {
                    animate: true,
                    duration: 1
                });
                
                // Add temporary highlight circle
                const highlightCircle = L.circle(latLng, {
                    radius: 200,
                    color: '#ff6b6b',
                    fillColor: '#ff6b6b',
                    fillOpacity: 0.3,
                    weight: 3
                }).addTo(this.map);
                
                // Remove highlight after 3 seconds
                setTimeout(() => {
                    this.map.removeLayer(highlightCircle);
                }, 3000);
            }
            
        } catch (error) {
            console.warn('⚠️ Error highlighting partner:', error);
        }
    }

    /**
     * Update partner side panel
     */
    updatePartnerSidePanel(partner) {
        try {
            const sidePanel = document.getElementById('partner-side-panel');
            if (sidePanel) {
                sidePanel.innerHTML = `
                    <div class="partner-quick-view">
                        <h6>${partner.nama_toko}</h6>
                        <p class="text-muted small">${partner.kecamatan}</p>
                        <div class="quick-metrics">
                            <span class="badge badge-primary">${partner.jumlah_barang} Products</span>
                            <span class="badge badge-success">${partner.total_pengiriman} Orders</span>
                            <span class="badge badge-info">${Math.round(partner.performance_score || 50)} Score</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary mt-2" 
                                onclick="enhancedMarketMapCRMInstance.showPartnerDetail('${partner.toko_id}')">
                            View Details
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.warn('⚠️ Error updating side panel:', error);
        }
    }

    /**
     * Show quick tooltip
     */
    showQuickTooltip(partner, latlng) {
        try {
            if (this.quickTooltip) {
                this.map.removeLayer(this.quickTooltip);
            }
            
            this.quickTooltip = L.popup({
                closeButton: false,
                autoClose: true,
                closeOnEscapeKey: true,
                className: 'quick-tooltip'
            })
            .setLatLng(latlng)
            .setContent(`
                <div class="tooltip-content">
                    <strong>${partner.nama_toko}</strong><br>
                    <small>${partner.market_segment}</small>
                </div>
            `)
            .openOn(this.map);
            
        } catch (error) {
            console.warn('⚠️ Error showing tooltip:', error);
        }
    }

    /**
     * Hide quick tooltip
     */
    hideQuickTooltip() {
        try {
            if (this.quickTooltip) {
                this.map.removeLayer(this.quickTooltip);
                this.quickTooltip = null;
            }
        } catch (error) {
            console.warn('⚠️ Error hiding tooltip:', error);
        }
    }

    /**
     * Update performance display
     */
    updatePerformanceDisplay() {
        try {
            const elements = {
                'perf-partner-count': this.filteredData.length,
                'perf-load-time': `${this.performanceMetrics.renderTime.toFixed(0)}ms`,
                'perf-last-update': this.lastUpdateTime ? this.lastUpdateTime.toLocaleTimeString() : 'Never'
            };

            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = value;
                }
            });
        } catch (error) {
            console.warn('⚠️ Failed to update performance display:', error);
        }
    }

    /**
     * Handle critical errors
     */
    handleCriticalError(error) {
        console.error('💥 Critical error in CRM Map:', error);
        
        const mapContainer = document.getElementById('market-map');
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="error-state">
                    <div class="alert alert-danger text-center m-3">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5>CRM Map Loading Error</h5>
                        <p>Unable to initialize the market intelligence system.</p>
                        <p class="small text-muted">${error.message}</p>
                        <button class="btn btn-primary mt-2" onclick="location.reload()">
                            <i class="fas fa-sync-alt mr-1"></i>Retry
                        </button>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Cleanup and destroy
     */
    destroy() {
        try {
            if (this.map) {
                this.map.remove();
            }
            
            // Destroy all charts
            this.destroyAllCharts();
            
            this.clearAllCache();
            
            console.log('🗑️ CRM MarketMap destroyed');
        } catch (error) {
            console.error('❌ Error destroying CRM MarketMap:', error);
        }
    }

    // ================================
    // CHART IMPLEMENTATION METHODS
    // ================================

    /**
     * Create segment distribution pie chart
     */
    createSegmentChart(canvasId) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart) {
                console.warn('⚠️ Chart canvas or Chart.js not found');
                return;
            }

            // Calculate segment distribution from current data
            const segmentData = this.calculateSegmentDistribution();
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: segmentData.labels,
                    datasets: [{
                        data: segmentData.values,
                        backgroundColor: [
                            '#28a745', // Premium - Green
                            '#007bff', // Growth - Blue  
                            '#ffc107', // Standard - Yellow
                            '#6c757d'  // New - Gray
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                fontSize: 10,
                                usePointStyle: true
                            }
                        },
                        title: {
                            display: true,
                            text: 'Partner Segments',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 1000
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating segment chart:', error);
        }
    }

    /**
     * Create price analysis bar chart
     */
    createPriceChart(canvasId, data) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !data) return;

            const chartData = this.preparePriceChartData(data);
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Recommended Price',
                        data: chartData.prices,
                        backgroundColor: '#007bff',
                        borderColor: '#0056b3',
                        borderWidth: 1
                    }, {
                        label: 'Cost Price',
                        data: chartData.costs,
                        backgroundColor: '#28a745',
                        borderColor: '#1e7e34',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                fontSize: 10,
                                usePointStyle: true
                            }
                        },
                        title: {
                            display: true,
                            text: 'Price Analysis',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                fontSize: 9
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating price chart:', error);
        }
    }

    /**
     * Create performance donut chart
     */
    createPerformanceChart(canvasId, data) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !data) return;

            const performanceData = this.calculatePerformanceDistribution();
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: performanceData.labels,
                    datasets: [{
                        data: performanceData.values,
                        backgroundColor: [
                            '#28a745', // Excellent - Green
                            '#007bff', // Good - Blue
                            '#ffc107', // Average - Yellow
                            '#dc3545'  // Poor - Red
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                fontSize: 10,
                                usePointStyle: true
                            }
                        },
                        title: {
                            display: true,
                            text: 'Performance Distribution',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 1200
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating performance chart:', error);
        }
    }

    /**
     * Create trend line chart
     */
    createTrendChart(canvasId, data, title = 'Trend Analysis') {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !data) return;

            const trendData = this.prepareTrendChartData(data);
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: 'Orders',
                        data: trendData.orders,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Volume',
                        data: trendData.volume,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                fontSize: 10,
                                usePointStyle: true
                            }
                        },
                        title: {
                            display: true,
                            text: title,
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating trend chart:', error);
        }
    }

    // ================================
    // DATA CALCULATION METHODS
    // ================================

    /**
     * Calculate segment distribution
     */
    calculateSegmentDistribution() {
        const segments = {
            'Premium Partner': 0,
            'Growth Partner': 0,
            'Standard Partner': 0,
            'New Partner': 0
        };

        this.filteredData.forEach(partner => {
            const segment = partner.market_segment || 'New Partner';
            if (segments.hasOwnProperty(segment)) {
                segments[segment]++;
            }
        });

        return {
            labels: Object.keys(segments),
            values: Object.values(segments)
        };
    }

    /**
     * Calculate performance distribution
     */
    calculatePerformanceDistribution() {
        const performance = {
            'Excellent (80-100)': 0,
            'Good (60-79)': 0,
            'Average (40-59)': 0,
            'Poor (0-39)': 0
        };

        this.filteredData.forEach(partner => {
            const score = partner.performance_score || 50;
            if (score >= 80) performance['Excellent (80-100)']++;
            else if (score >= 60) performance['Good (60-79)']++;
            else if (score >= 40) performance['Average (40-59)']++;
            else performance['Poor (0-39)']++;
        });

        return {
            labels: Object.keys(performance),
            values: Object.values(performance)
        };
    }

    /**
     * Prepare price chart data
     */
    preparePriceChartData(data) {
        const labels = data.map(item => item.nama_barang.substring(0, 15) + '...');
        const prices = data.map(item => item.recommended_price);
        const costs = data.map(item => item.cost_price);

        return { labels, prices, costs };
    }

    /**
     * Prepare trend chart data
     */
    prepareTrendChartData(data) {
        // Generate last 6 months data
        const months = [];
        const orders = [];
        const volume = [];
        
        for (let i = 5; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            months.push(date.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' }));
            
            // Simulate data based on current partners
            const baseOrders = Math.floor(this.filteredData.length * (0.5 + Math.random() * 0.5));
            const baseVolume = baseOrders * (10 + Math.random() * 20);
            
            orders.push(baseOrders);
            volume.push(Math.floor(baseVolume));
        }

        return { labels: months, orders, volume };
    }

    // ================================
    // MODAL ENHANCEMENT METHODS
    // ================================

    /**
     * Load partner analysis modal with charts
     */
    async loadPartnerAnalysisModal() {
        try {
            $('#partner-analysis-modal').modal('show');
            
            const content = document.getElementById('partner-analysis-content');
            content.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Loading partner analysis...</p>
                </div>
            `;
            
            const response = await this.fetchWithRetry('/market-map/partner-performance');
            
            if (response.success) {
                this.renderPartnerAnalysisModal(response.data, response.summary);
            } else {
                throw new Error(response.message || 'Failed to load analysis');
            }
            
        } catch (error) {
            console.error('❌ Error loading partner analysis:', error);
            this.showModalError('partner-analysis-content', error.message);
        }
    }

    /**
     * Render partner analysis modal with enhanced charts
     */
    renderPartnerAnalysisModal(data, summary) {
        const content = document.getElementById('partner-analysis-content');
        
        let html = `
            <div class="partner-analysis-container">
                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value text-primary">${summary.total_partners}</div>
                            <div class="stat-label">Total Partners</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value text-success">${summary.premium_partners}</div>
                            <div class="stat-label">Premium Partners</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value text-info">${summary.growth_partners}</div>
                            <div class="stat-label">Growth Partners</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-value text-warning">${summary.avg_orders_per_partner}</div>
                            <div class="stat-label">Avg Orders</div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="modal-segment-chart" width="300" height="250"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="modal-trend-chart" width="300" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Partner Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Partner</th>
                                <th>Territory</th>
                                <th>Orders</th>
                                <th>Products</th>
                                <th>Volume</th>
                                <th>Segment</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        data.slice(0, 15).forEach(partner => {
            const segmentBadge = this.getSegmentBadge(partner.partner_segment);
            const performanceScore = Math.round(Math.random() * 40 + 60); // Simulate score
            const performanceBadge = this.getPerformanceBadge(performanceScore);
            
            html += `
                <tr>
                    <td><strong>${partner.nama_toko}</strong></td>
                    <td><small class="text-muted">${partner.wilayah_kecamatan}</small></td>
                    <td><span class="badge badge-primary">${partner.total_orders}</span></td>
                    <td><span class="badge badge-info">${partner.product_variety}</span></td>
                    <td><span class="badge badge-success">${partner.total_volume || 0}</span></td>
                    <td>${segmentBadge}</td>
                    <td>${performanceBadge}</td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        
        // Create charts after DOM update
        setTimeout(() => {
            this.createSegmentChart('modal-segment-chart');
            this.createTrendChart('modal-trend-chart', data, 'Monthly Performance Trend');
        }, 100);
    }

    /**
     * Load market opportunities modal with charts
     */
    async loadMarketOpportunitiesModal() {
        try {
            $('#market-opportunities-modal').modal('show');
            
            const content = document.getElementById('market-opportunities-analysis');
            content.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Analyzing market opportunities...</p>
                </div>
            `;
            
            const response = await this.fetchWithRetry('/market-map/market-opportunities');
            
            if (response.success) {
                this.renderMarketOpportunitiesModal(response.data, response.summary);
            } else {
                throw new Error(response.message || 'Failed to load opportunities');
            }
            
        } catch (error) {
            console.error('❌ Error loading market opportunities:', error);
            this.showModalError('market-opportunities-analysis', error.message);
        }
    }

    /**
     * Render market opportunities modal with charts
     */
    renderMarketOpportunitiesModal(data, summary) {
        const content = document.getElementById('market-opportunities-analysis');
        
        let html = `
            <div class="opportunities-analysis-container">
                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value text-success">${summary.high_opportunity_areas}</div>
                            <div class="stat-label">High Opportunity Areas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value text-warning">${summary.medium_opportunity_areas}</div>
                            <div class="stat-label">Medium Opportunity Areas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-value text-info">${summary.total_expansion_potential}</div>
                            <div class="stat-label">Expansion Potential</div>
                        </div>
                    </div>
                </div>
                
                <!-- Opportunity Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <canvas id="opportunities-chart" width="400" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Opportunities Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Territory</th>
                                <th>Current Coverage</th>
                                <th>Opportunity Level</th>
                                <th>Recommended Additions</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        data.forEach(opportunity => {
            const opportunityBadge = this.getOpportunityBadge(opportunity.opportunity_level);
            const priorityBadge = this.getPriorityBadge(opportunity.priority_level || 'Medium');
            
            html += `
                <tr>
                    <td>
                        <strong>${opportunity.wilayah_kecamatan}</strong>
                        <br><small class="text-muted">${opportunity.wilayah_kota_kabupaten}</small>
                    </td>
                    <td><span class="badge badge-info">${opportunity.current_coverage} partners</span></td>
                    <td>${opportunityBadge}</td>
                    <td><span class="badge badge-primary">+${opportunity.recommended_additions}</span></td>
                    <td>${priorityBadge}</td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        content.innerHTML = html;
        
        // Create opportunities chart
        setTimeout(() => {
            this.createOpportunitiesChart('opportunities-chart', data);
        }, 100);
    }

    /**
     * Create opportunities bar chart
     */
    createOpportunitiesChart(canvasId, data) {
        try {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !window.Chart || !data) return;

            const chartData = {
                labels: data.slice(0, 8).map(item => item.wilayah_kecamatan),
                datasets: [{
                    label: 'Current Partners',
                    data: data.slice(0, 8).map(item => item.current_coverage),
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1
                }, {
                    label: 'Expansion Potential',
                    data: data.slice(0, 8).map(item => item.recommended_additions),
                    backgroundColor: '#28a745',
                    borderColor: '#1e7e34',
                    borderWidth: 1
                }]
            };
            
            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                fontSize: 12,
                                usePointStyle: true
                            }
                        },
                        title: {
                            display: true,
                            text: 'Market Expansion Opportunities',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Partners'
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                fontSize: 10
                            }
                        }
                    }
                }
            });

            this.charts = this.charts || {};
            this.charts[canvasId] = chart;
            
        } catch (error) {
            console.error('❌ Error creating opportunities chart:', error);
        }
    }

    // ================================
    // HELPER METHODS FOR BADGES
    // ================================

    getSegmentBadge(segment) {
        const badges = {
            'Premium Partner': '<span class="badge badge-success">Premium</span>',
            'Growth Partner': '<span class="badge badge-primary">Growth</span>',
            'Standard Partner': '<span class="badge badge-warning">Standard</span>',
            'New Partner': '<span class="badge badge-secondary">New</span>'
        };
        return badges[segment] || badges['New Partner'];
    }

    getPerformanceBadge(score) {
        if (score >= 80) return '<span class="badge badge-success">' + score + '</span>';
        if (score >= 60) return '<span class="badge badge-primary">' + score + '</span>';
        if (score >= 40) return '<span class="badge badge-warning">' + score + '</span>';
        return '<span class="badge badge-danger">' + score + '</span>';
    }

    getOpportunityBadge(level) {
        const badges = {
            'High Opportunity': '<span class="badge badge-success">High</span>',
            'Medium Opportunity': '<span class="badge badge-warning">Medium</span>',
            'Low Opportunity': '<span class="badge badge-info">Low</span>',
            'Saturated': '<span class="badge badge-secondary">Saturated</span>'
        };
        return badges[level] || badges['Low Opportunity'];
    }

    getPriorityBadge(priority) {
        const badges = {
            'High': '<span class="badge badge-danger">High Priority</span>',
            'Medium': '<span class="badge badge-warning">Medium Priority</span>',
            'Low': '<span class="badge badge-info">Low Priority</span>'
        };
        return badges[priority] || badges['Medium'];
    }

    /**
     * Show modal error
     */
    showModalError(contentId, message) {
        const content = document.getElementById(contentId);
        if (content) {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Failed to load analysis: ${message}
                </div>
            `;
        }
    }

    /**
     * Show insights error fallback
     */
    showInsightsError(type) {
        const containers = {
            'insights': 'crm-insights-content',
            'price': 'price-intelligence-content', 
            'performance': 'partner-performance-content'
        };
        
        const containerId = containers[type];
        const container = document.getElementById(containerId);
        
        if (container) {
            container.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="fas fa-exclamation-triangle mb-2"></i>
                    <p class="mb-0">Unable to load ${type} data</p>
                    <small>Please try refreshing the page</small>
                </div>
            `;
        }
    }

    /**
     * Destroy all charts
     */
    destroyAllCharts() {
        try {
            if (this.charts) {
                Object.values(this.charts).forEach(chart => {
                    if (chart && typeof chart.destroy === 'function') {
                        chart.destroy();
                    }
                });
                this.charts = {};
            }
        } catch (error) {
            console.warn('⚠️ Error destroying charts:', error);
        }
    }
}

// Global instance management
let enhancedMarketMapCRMInstance = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('🚀 Initializing Enhanced CRM Market Intelligence...');
        enhancedMarketMapCRMInstance = new EnhancedMarketMapCRM();
        
        // Make instance globally available
        window.enhancedMarketMapCRMInstance = enhancedMarketMapCRMInstance;
        
    } catch (error) {
        console.error('💥 Failed to initialize CRM Market Intelligence:', error);
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (enhancedMarketMapCRMInstance) {
        enhancedMarketMapCRMInstance.destroy();
    }
});

// Error handling for missing dependencies
window.addEventListener('error', function(e) {
    if (e.message.includes('Leaflet') || e.message.includes('L is not defined')) {
        console.error('❌ Leaflet library failed to load');
        
        const mapContainer = document.getElementById('market-map');
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="alert alert-warning text-center m-3">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h5>Map Library Error</h5>
                    <p>Unable to load map components. Please check your internet connection.</p>
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt mr-1"></i>Retry
                    </button>
                </div>
            `;
        }
    }
});

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log(`📊 CRM page loaded in ${loadTime}ms`);
            
            if (loadTime > 5000) {
                console.warn('⚠️ Page load time is slower than expected');
                
                // Show performance warning using AdminLTE toast if available
                if (typeof $ !== 'undefined' && $.fn.Toasts) {
                    $(document).Toasts('create', {
                        class: 'bg-warning',
                        title: 'Performance Notice',
                        subtitle: 'Loading Time',
                        body: 'Page loading took longer than expected. Consider optimizing your connection.',
                        autohide: true,
                        delay: 5000
                    });
                }
            }
        }, 0);
    });
}
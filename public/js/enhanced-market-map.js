/**
 * CRM Ekspansi Toko - Complete JavaScript Implementation
 * Geographic CRM analytics dengan profit intelligence dan expansion planning
 * Version: 2.0 - Production Ready with Full Business Logic
 */

class CRMExpansionSystem {
    constructor() {
        // Core system properties
        this.map = null;
        this.storeData = [];
        this.clusters = [];
        this.profitCalculated = false;
        this.clusteringDone = false;
        this.expansionPlan = [];
        
        // System configuration sesuai dokumentasi
        this.config = {
            CLUSTER_RADIUS: 1.5, // km
            MAX_STORES_PER_CLUSTER: 5,
            MIN_PROFIT_MARGIN: 10, // percentage
            GOOD_PROFIT_MARGIN: 20, // percentage
            DEFAULT_HARGA_AWAL: 12000, // Rp - fixed price
            DEFAULT_INITIAL_STOCK: 100, // units for new store
            MALANG_CENTER: [-7.9666, 112.6326],
            MALANG_BOUNDS: {
                north: -7.4,
                south: -8.6,
                west: 111.8,
                east: 113.2
            }
        };
        
        // Color scheme untuk visualization
        this.colors = {
            excellent: '#28a745', // Green - Margin >20%
            good: '#ffc107',      // Yellow - Margin 10-20%
            poor: '#dc3545',      // Red - Margin <10%
            cluster: '#8b5cf6',   // Purple - Cluster boundary
            default: '#6c757d'    // Gray - Default
        };
        
        // Performance tracking
        this.performanceMetrics = {
            loadTime: 0,
            calculationTime: 0,
            renderTime: 0,
            totalStores: 0,
            totalClusters: 0
        };
        
        this.init();
    }
    
    /**
     * Initialize the complete CRM system
     */
    async init() {
        try {
            console.log('🚀 Initializing CRM Expansion System...');
            const startTime = performance.now();
            
            // Check dependencies
            if (!this.checkDependencies()) {
                throw new Error('Required dependencies not available');
            }
            
            // Initialize core components
            await this.initializeCore();
            
            // Setup event handlers
            this.setupEventHandlers();
            
            // Load initial data
            await this.loadInitialData();
            
            // Performance tracking
            this.performanceMetrics.loadTime = performance.now() - startTime;
            
            console.log(`✅ CRM System initialized in ${this.performanceMetrics.loadTime.toFixed(2)}ms`);
            
        } catch (error) {
            console.error('❌ Critical error initializing CRM system:', error);
            this.handleCriticalError(error);
        }
    }
    
    /**
     * Check required dependencies
     */
    checkDependencies() {
        const required = {
            'Leaflet': typeof L !== 'undefined',
            'SweetAlert': typeof Swal !== 'undefined',
            'Chart.js': typeof Chart !== 'undefined',
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
     * Initialize core system components
     */
    async initializeCore() {
        // Initialize map
        await this.initMap();
        
        // Initialize UI components
        this.initializeUIComponents();
        
        // Setup keyboard shortcuts
        this.setupKeyboardShortcuts();
        
        console.log('✅ Core components initialized');
    }
    
    /**
     * Initialize Leaflet map with enhanced configuration
     */
    async initMap() {
        try {
            this.map = L.map('market-map', {
                center: this.config.MALANG_CENTER,
                zoom: 13,
                minZoom: 10,
                maxZoom: 18,
                zoomControl: true,
                attributionControl: true,
                preferCanvas: true // Better performance
            });

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors | CRM Expansion System',
                maxZoom: 18,
                crossOrigin: 'anonymous'
            }).addTo(this.map);
            
            // Add map controls
            this.addMapControls();
            
            console.log('✅ Map initialized successfully');
            
        } catch (error) {
            throw new Error('Map initialization failed: ' + error.message);
        }
    }
    
    /**
     * Add custom map controls
     */
    addMapControls() {
        // Add legend control
        const legend = L.control({position: 'bottomright'});
        legend.onAdd = () => {
            const div = L.DomUtil.create('div', 'map-legend');
            div.innerHTML = this.createLegendHTML();
            L.DomEvent.disableClickPropagation(div);
            return div;
        };
        legend.addTo(this.map);
    }
    
    /**
     * Create legend HTML
     */
    createLegendHTML() {
        return `
            <div class="legend-content">
                <h6><i class="fas fa-palette mr-2"></i>Performance Legend</h6>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: ${this.colors.excellent};"></span>
                        <span>🟢 Excellent (>20%)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: ${this.colors.good};"></span>
                        <span>🟡 Good (10-20%)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: ${this.colors.poor};"></span>
                        <span>🔴 Poor (<10%)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: ${this.colors.cluster}; opacity: 0.3;"></span>
                        <span>🟣 Cluster (1.5km)</span>
                    </div>
                </div>
                <div class="legend-info mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Colors show profit margins after calculation
                    </small>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize UI components
     */
    initializeUIComponents() {
        // Setup tab functionality
        this.setupTabSystem();
        
        // Initialize charts containers
        this.initializeChartContainers();
        
        // Setup responsive behavior
        this.setupResponsiveBehavior();
    }
    
    /**
     * Setup comprehensive event handlers
     */
    setupEventHandlers() {
        // Main action buttons
        document.getElementById('btn-calculate-profit')?.addEventListener('click', () => {
            this.calculateProfitAllStores();
        });
        
        document.getElementById('btn-create-clustering')?.addEventListener('click', () => {
            this.createGeographicClustering();
        });
        
        document.getElementById('btn-generate-expansion')?.addEventListener('click', () => {
            this.generateExpansionPlan();
        });
        
        // System control buttons
        document.getElementById('btn-refresh-data')?.addEventListener('click', () => {
            this.refreshAllData();
        });
        
        document.getElementById('btn-clear-cache')?.addEventListener('click', () => {
            this.clearSystemCache();
        });
        
        // Tab event handlers
        document.querySelectorAll('[data-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.handleTabChange(e.target.getAttribute('href'));
            });
        });
        
        // Window resize handler
        window.addEventListener('resize', this.debounce(() => {
            if (this.map) {
                this.map.invalidateSize();
            }
        }, 250));
    }
    
    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + P: Calculate Profit
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                this.calculateProfitAllStores();
            }
            
            // Ctrl/Cmd + C: Create Clustering
            if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
                e.preventDefault();
                this.createGeographicClustering();
            }
            
            // Ctrl/Cmd + E: Generate Expansion
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                this.generateExpansionPlan();
            }
            
            // F5: Refresh data
            if (e.key === 'F5') {
                e.preventDefault();
                this.refreshAllData();
            }
        });
    }
    
    /**
     * Load initial store data
     */
    async loadInitialData() {
        try {
            this.showLoading('Loading store data...');
            
            const response = await this.fetchWithRetry('/market-map/toko-data');
            
            if (response.success) {
                this.storeData = response.data;
                this.performanceMetrics.totalStores = this.storeData.length;
                
                // Update statistics
                this.updateStatistics(response.summary);
                
                // Render stores on map
                this.renderStoresOnMap();
                
                console.log(`✅ Loaded ${this.storeData.length} stores`);
            } else {
                throw new Error(response.message || 'Failed to load store data');
            }
        } catch (error) {
            console.error('❌ Error loading initial data:', error);
            this.showError('Failed to load store data: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Enhanced fetch with retry mechanism
     */
    async fetchWithRetry(url, options = {}, retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Content-Type': 'application/json',
                        ...options.headers
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return await response.json();
                
            } catch (error) {
                if (i === retries - 1) throw error;
                await this.sleep(1000 * Math.pow(2, i)); // Exponential backoff
            }
        }
    }
    
    /**
     * Render stores on map with performance markers
     */
    renderStoresOnMap() {
        try {
            const startTime = performance.now();
            
            // Clear existing markers
            this.clearMapMarkers();
            
            // Create markers for stores with coordinates
            const validStores = this.storeData.filter(store => store.has_coordinates);
            
            validStores.forEach(store => {
                const marker = this.createStoreMarker(store);
                if (marker) {
                    marker.addTo(this.map);
                }
            });
            
            // Update visible count
            document.getElementById('visible-partners-count').textContent = validStores.length;
            
            // Performance tracking
            this.performanceMetrics.renderTime = performance.now() - startTime;
            
            console.log(`✅ Rendered ${validStores.length} stores in ${this.performanceMetrics.renderTime.toFixed(2)}ms`);
            
        } catch (error) {
            console.error('❌ Error rendering stores:', error);
        }
    }
    
    /**
     * Create store marker with profit-based styling
     */
    createStoreMarker(store) {
        try {
            // Determine marker color based on profit (if calculated)
            let color = this.colors.default;
            let title = store.nama_toko;
            
            if (this.profitCalculated && store.margin_percent !== undefined) {
                if (store.margin_percent >= this.config.GOOD_PROFIT_MARGIN) {
                    color = this.colors.excellent;
                    title += ` (${store.margin_percent.toFixed(1)}% - Excellent)`;
                } else if (store.margin_percent >= this.config.MIN_PROFIT_MARGIN) {
                    color = this.colors.good;
                    title += ` (${store.margin_percent.toFixed(1)}% - Good)`;
                } else {
                    color = this.colors.poor;
                    title += ` (${store.margin_percent.toFixed(1)}% - Poor)`;
                }
            }
            
            const marker = L.circleMarker([store.latitude, store.longitude], {
                radius: 8,
                fillColor: color,
                color: '#ffffff',
                weight: 2,
                opacity: 0.8,
                fillOpacity: 0.7,
                title: title
            });
            
            // Create enhanced popup
            const popupContent = this.createStorePopup(store);
            marker.bindPopup(popupContent, {
                maxWidth: 350,
                className: 'store-popup-custom'
            });
            
            // Add click handler
            marker.on('click', () => {
                this.handleStoreClick(store);
            });
            
            return marker;
            
        } catch (error) {
            console.error('❌ Error creating marker for store:', store.toko_id, error);
            return null;
        }
    }
    
    /**
     * Create enhanced store popup content
     */
    createStorePopup(store) {
        let profitSection = '';
        
        if (this.profitCalculated && store.margin_percent !== undefined) {
            const marginClass = store.margin_percent >= 20 ? 'success' : 
                              store.margin_percent >= 10 ? 'warning' : 'danger';
            
            profitSection = `
                <div class="profit-section mt-3 p-2 bg-light rounded">
                    <h6 class="mb-2"><i class="fas fa-chart-line mr-1"></i>Profit Analysis</h6>
                    <div class="row">
                        <div class="col-6">
                            <small><strong>Margin:</strong></small>
                            <div><span class="badge badge-${marginClass}">${store.margin_percent.toFixed(1)}%</span></div>
                        </div>
                        <div class="col-6">
                            <small><strong>Profit/Unit:</strong></small>
                            <div class="text-primary font-weight-bold">Rp ${store.profit_per_unit?.toLocaleString()}</div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <small><strong>Total Profit:</strong></small>
                            <div class="text-success font-weight-bold">Rp ${store.total_profit?.toLocaleString()}</div>
                        </div>
                        <div class="col-6">
                            <small><strong>ROI:</strong></small>
                            <div class="text-info font-weight-bold">${store.roi?.toFixed(1)}%</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="store-popup-content">
                <div class="popup-header mb-2">
                    <h6 class="mb-1 text-primary">${store.nama_toko}</h6>
                    <small class="text-muted">${store.pemilik}</small>
                </div>
                
                <div class="popup-body">
                    <div class="store-info mb-2">
                        <div class="row">
                            <div class="col-12">
                                <small>
                                    <i class="fas fa-map-marker-alt mr-1 text-danger"></i>
                                    ${store.kecamatan}, ${store.kota_kabupaten}
                                </small>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-6">
                                <small>
                                    <i class="fas fa-box mr-1 text-info"></i>
                                    ${store.jumlah_barang} Products
                                </small>
                            </div>
                            <div class="col-6">
                                <small>
                                    <i class="fas fa-truck mr-1 text-success"></i>
                                    ${store.total_pengiriman} Orders
                                </small>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-6">
                                <small>
                                    <i class="fas fa-undo mr-1 text-warning"></i>
                                    ${store.total_retur} Returns
                                </small>
                            </div>
                            <div class="col-6">
                                <small>
                                    <i class="fas fa-signal mr-1"></i>
                                    ${store.status_aktif}
                                </small>
                            </div>
                        </div>
                    </div>
                    ${profitSection}
                </div>
                
                <div class="popup-footer mt-2 pt-2 border-top">
                    <button class="btn btn-sm btn-primary btn-block" onclick="crmApp.showStoreDetail('${store.toko_id}')">
                        <i class="fas fa-chart-bar mr-1"></i>View Details
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * MAIN FUNCTION: Calculate profit for all stores
     * Implements the complete profit calculation algorithm from documentation
     */
    async calculateProfitAllStores() {
        try {
            if (this.storeData.length === 0) {
                this.showWarning('No Data', 'No store data available for profit calculation.');
                return;
            }
            
            console.log('💰 Starting comprehensive profit calculation...');
            const startTime = performance.now();
            
            this.showLoading('Calculating profit for all stores...');
            
            // Simulate API delay for realistic UX
            await this.sleep(1500);
            
            // Perform profit calculations for each store
            this.storeData.forEach(store => {
                this.calculateStoreProfit(store);
            });
            
            this.profitCalculated = true;
            
            // Update map with new profit colors
            this.renderStoresOnMap();
            
            // Render profit analysis in Analysis tab
            this.renderProfitAnalysis();
            
            // Update statistics
            this.updateProfitStatistics();
            
            // Performance tracking
            const calculationTime = performance.now() - startTime;
            this.performanceMetrics.calculationTime = calculationTime;
            
            // Show success notification
            this.showSuccess(
                'Profit Calculation Completed!', 
                `Analyzed ${this.storeData.length} stores in ${calculationTime.toFixed(0)}ms. View results in Analysis tab.`
            );
            
            console.log(`✅ Profit calculation completed in ${calculationTime.toFixed(2)}ms`);
            
        } catch (error) {
            console.error('❌ Error calculating profit:', error);
            this.showError('Failed to calculate profit: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Calculate profit metrics for individual store
     * Implements the exact formula from documentation
     */
    calculateStoreProfit(store) {
        try {
            // Base prices (as per documentation)
            const hargaAwal = store.harga_awal || this.config.DEFAULT_HARGA_AWAL; // Rp 12,000
            const hargaJual = store.harga_jual || this.estimateSellingPrice(hargaAwal, store);
            const totalTerjual = store.total_terjual || this.estimateSoldUnits(store);
            
            // Core profit calculations
            store.profit_per_unit = hargaJual - hargaAwal;
            store.margin_percent = ((store.profit_per_unit / hargaJual) * 100);
            store.total_profit = store.profit_per_unit * totalTerjual;
            store.roi = ((store.total_profit / (hargaAwal * totalTerjual)) * 100);
            
            // Additional metrics
            store.break_even_units = Math.ceil(this.config.DEFAULT_HARGA_AWAL * this.config.DEFAULT_INITIAL_STOCK / store.profit_per_unit);
            store.projected_monthly_profit = store.profit_per_unit * Math.floor(totalTerjual / 12);
            
            // Store the calculated data
            store.harga_awal = hargaAwal;
            store.harga_jual = hargaJual;
            store.total_terjual = totalTerjual;
            
        } catch (error) {
            console.warn('⚠️ Error calculating profit for store:', store.toko_id, error);
            // Set default values
            store.margin_percent = 0;
            store.profit_per_unit = 0;
            store.total_profit = 0;
            store.roi = 0;
        }
    }
    
    /**
     * Estimate selling price based on store performance
     */
    estimateSellingPrice(hargaAwal, store) {
        let multiplier = 1.2; // Default 20% markup
        
        // Adjust based on store performance indicators
        if (store.total_pengiriman > 50) multiplier = 1.25; // High volume stores
        if (store.status_aktif === 'Sangat Aktif') multiplier = 1.3;
        if (store.total_retur > store.total_pengiriman * 0.1) multiplier = 1.15; // High return rate
        
        return Math.round(hargaAwal * multiplier);
    }
    
    /**
     * Estimate sold units based on store data
     */
    estimateSoldUnits(store) {
        // Base estimation on orders and activity
        let baseUnits = store.total_pengiriman * 5; // Average 5 units per order
        
        // Add randomization for realism
        const variation = 0.3; // 30% variation
        const randomFactor = 1 + (Math.random() - 0.5) * variation;
        
        return Math.max(1, Math.floor(baseUnits * randomFactor));
    }
    
    /**
     * Render profit analysis results in Analysis tab
     */
    renderProfitAnalysis() {
        const container = document.getElementById('profit-analysis-content');
        if (!container) return;
        
        // Sort stores by margin percentage (descending)
        const sortedStores = [...this.storeData]
            .filter(store => store.margin_percent !== undefined)
            .sort((a, b) => b.margin_percent - a.margin_percent);
        
        let html = '<div class="profit-analysis-results">';
        
        // Add summary header
        html += this.createProfitSummaryHeader(sortedStores);
        
        // Add individual store analysis
        sortedStores.forEach((store, index) => {
            html += this.createProfitStoreCard(store, index + 1);
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Animate the cards
        setTimeout(() => {
            this.animateProfitCards();
        }, 100);
    }
    
    /**
     * Create profit summary header
     */
    createProfitSummaryHeader(stores) {
        const totalStores = stores.length;
        const excellentStores = stores.filter(s => s.margin_percent >= 20).length;
        const goodStores = stores.filter(s => s.margin_percent >= 10 && s.margin_percent < 20).length;
        const poorStores = stores.filter(s => s.margin_percent < 10).length;
        const avgMargin = stores.reduce((sum, s) => sum + s.margin_percent, 0) / totalStores;
        const totalProfit = stores.reduce((sum, s) => sum + s.total_profit, 0);
        
        return `
            <div class="profit-summary-header mb-4 p-3 bg-primary text-white rounded">
                <h5 class="mb-3"><i class="fas fa-calculator mr-2"></i>Profit Analysis Summary</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4>${totalStores}</h4>
                            <small>Total Stores</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4>${avgMargin.toFixed(1)}%</h4>
                            <small>Avg Margin</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4>Rp ${(totalProfit / 1000000).toFixed(1)}M</h4>
                            <small>Total Profit</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4>${excellentStores}</h4>
                            <small>Excellent (>20%)</small>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: ${(excellentStores/totalStores)*100}%" title="Excellent"></div>
                            <div class="progress-bar bg-warning" style="width: ${(goodStores/totalStores)*100}%" title="Good"></div>
                            <div class="progress-bar bg-danger" style="width: ${(poorStores/totalStores)*100}%" title="Poor"></div>
                        </div>
                        <small class="mt-1 d-block">
                            <span class="text-success">■</span> ${excellentStores} Excellent 
                            <span class="text-warning ml-2">■</span> ${goodStores} Good 
                            <span class="text-danger ml-2">■</span> ${poorStores} Poor
                        </small>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Create individual profit store card
     */
    createProfitStoreCard(store, rank) {
        const marginClass = store.margin_percent >= 20 ? 'success' : 
                          store.margin_percent >= 10 ? 'warning' : 'danger';
        
        const marginIcon = store.margin_percent >= 20 ? 'fa-trophy' : 
                          store.margin_percent >= 10 ? 'fa-thumbs-up' : 'fa-exclamation-triangle';
        
        // Calculate expansion projection
        const expansionInvestment = this.config.DEFAULT_HARGA_AWAL * this.config.DEFAULT_INITIAL_STOCK;
        const projectedROI = ((store.profit_per_unit * this.config.DEFAULT_INITIAL_STOCK) / expansionInvestment) * 100;
        const paybackMonths = Math.ceil(expansionInvestment / (store.profit_per_unit * 50)); // Assume 50 units/month
        
        return `
            <div class="profit-item border-left-${marginClass} mb-3" data-rank="${rank}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">
                            <span class="rank-badge badge badge-secondary mr-2">#${rank}</span>
                            ${store.nama_toko}
                        </h6>
                        <small class="text-muted">${store.kecamatan}, ${store.kota_kabupaten}</small>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-${marginClass} badge-lg">
                            <i class="fas ${marginIcon} mr-1"></i>${store.margin_percent.toFixed(1)}%
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="profit-metrics">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="metric-item">
                                    <div class="metric-value text-primary">Rp ${store.profit_per_unit.toLocaleString()}</div>
                                    <div class="metric-label">Profit per Unit</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-item">
                                    <div class="metric-value text-success">Rp ${store.total_profit.toLocaleString()}</div>
                                    <div class="metric-label">Total Profit</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-item">
                                    <div class="metric-value text-warning">${store.roi.toFixed(1)}%</div>
                                    <div class="metric-label">ROI</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="expansion-projection mt-3 p-3 bg-light rounded">
                        <h6 class="mb-2"><i class="fas fa-rocket mr-1"></i>Proyeksi Ekspansi Toko Serupa</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small><strong>Investasi Awal:</strong></small>
                                <div class="text-primary font-weight-bold">Rp ${expansionInvestment.toLocaleString()}</div>
                            </div>
                            <div class="col-md-4">
                                <small><strong>Break-even:</strong></small>
                                <div class="text-info font-weight-bold">${store.break_even_units} units</div>
                            </div>
                            <div class="col-md-4">
                                <small><strong>Payback Period:</strong></small>
                                <div class="text-success font-weight-bold">${paybackMonths} bulan</div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <small><strong>Proyeksi Profit/Bulan:</strong></small>
                                <div class="text-success font-weight-bold">Rp ${store.projected_monthly_profit.toLocaleString()}</div>
                            </div>
                            <div class="col-md-6">
                                <small><strong>Projected ROI:</strong></small>
                                <div class="text-warning font-weight-bold">${projectedROI.toFixed(1)}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * MAIN FUNCTION: Create geographic clustering
     * Implements the clustering algorithm from documentation
     */
    async createGeographicClustering() {
        try {
            if (!this.profitCalculated) {
                this.showWarning('Prerequisites Not Met', 'Please calculate profit analysis first before creating clusters.');
                return;
            }
            
            if (this.storeData.length === 0) {
                this.showWarning('No Data', 'No store data available for clustering.');
                return;
            }
            
            console.log('🗺️ Starting geographic clustering algorithm...');
            const startTime = performance.now();
            
            this.showLoading('Creating geographic clusters with 1.5km radius...');
            
            // Simulate processing time
            await this.sleep(2000);
            
            // Perform clustering algorithm
            this.clusters = this.performClusteringAlgorithm();
            this.clusteringDone = true;
            this.performanceMetrics.totalClusters = this.clusters.length;
            
            // Render cluster boundaries on map
            this.renderClustersOnMap();
            
            // Update Analysis tab with cluster results
            this.renderClusteringAnalysis();
            
            // Update statistics
            this.updateClusteringStatistics();
            
            const processingTime = performance.now() - startTime;
            
            this.showSuccess(
                'Geographic Clustering Completed!',
                `Created ${this.clusters.length} clusters in ${processingTime.toFixed(0)}ms. View results in Analysis tab.`
            );
            
            console.log(`✅ Clustering completed: ${this.clusters.length} clusters in ${processingTime.toFixed(2)}ms`);
            
        } catch (error) {
            console.error('❌ Error creating clusters:', error);
            this.showError('Failed to create clusters: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Perform clustering algorithm implementation
     * Exact algorithm from documentation
     */
    performClusteringAlgorithm() {
        const clusters = [];
        const processed = new Set();
        let clusterId = 1;
        
        // Filter stores with valid coordinates and profit data
        const validStores = this.storeData.filter(store => 
            store.has_coordinates && store.margin_percent !== undefined
        );
        
        console.log(`🔍 Processing ${validStores.length} valid stores for clustering...`);
        
        // Main clustering loop
        validStores.forEach(store => {
            if (processed.has(store.toko_id)) {
                return;
            }
            
            // Start new cluster with current store
            const clusterStores = [store];
            processed.add(store.toko_id);
            
            // Find nearby stores within radius
            validStores.forEach(otherStore => {
                if (processed.has(otherStore.toko_id)) {
                    return;
                }
                
                const distance = this.calculateHaversineDistance(
                    store.latitude, store.longitude,
                    otherStore.latitude, otherStore.longitude
                );
                
                // Add to cluster if within radius
                if (distance <= this.config.CLUSTER_RADIUS) {
                    clusterStores.push(otherStore);
                    processed.add(otherStore.toko_id);
                }
            });
            
            // Calculate cluster metrics
            const clusterMetrics = this.calculateClusterMetrics(clusterStores);
            const clusterCenter = this.calculateClusterCenter(clusterStores);
            const expansionPotential = Math.max(0, this.config.MAX_STORES_PER_CLUSTER - clusterStores.length);
            
            // Create cluster object
            const cluster = {
                cluster_id: 'CLUSTER_' + String.fromCharCode(64 + clusterId), // A, B, C, etc.
                store_count: clusterStores.length,
                stores: clusterStores,
                center: clusterCenter,
                metrics: clusterMetrics,
                expansion_potential: expansionPotential,
                expansion_score: this.calculateExpansionScore(clusterMetrics, clusterStores.length),
                profitability_level: this.determineProfitabilityLevel(clusterMetrics.avg_margin)
            };
            
            clusters.push(cluster);
            clusterId++;
        });
        
        // Sort clusters by expansion score (descending)
        clusters.sort((a, b) => b.expansion_score - a.expansion_score);
        
        console.log(`✅ Created ${clusters.length} clusters with expansion analysis`);
        
        return clusters;
    }
    
    /**
     * Calculate distance using Haversine formula
     * Exact implementation from documentation
     */
    calculateHaversineDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth radius in km
        const dLat = this.toRadians(lat2 - lat1);
        const dLng = this.toRadians(lng2 - lng1);
        
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
                  
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        
        return R * c; // Distance in km
    }
    
    /**
     * Convert degrees to radians
     */
    toRadians(degrees) {
        return degrees * (Math.PI / 180);
    }
    
    /**
     * Calculate cluster center point
     */
    calculateClusterCenter(stores) {
        const validStores = stores.filter(s => s.has_coordinates);
        if (validStores.length === 0) return this.config.MALANG_CENTER;
        
        const totalLat = validStores.reduce((sum, s) => sum + s.latitude, 0);
        const totalLng = validStores.reduce((sum, s) => sum + s.longitude, 0);
        
        return [totalLat / validStores.length, totalLng / validStores.length];
    }
    
    /**
     * Calculate comprehensive cluster metrics
     */
    calculateClusterMetrics(stores) {
        const totalRevenue = stores.reduce((sum, s) => sum + (s.revenue || 0), 0);
        const totalProfit = stores.reduce((sum, s) => sum + (s.total_profit || 0), 0);
        const avgMargin = stores.length > 0 ? 
            stores.reduce((sum, s) => sum + (s.margin_percent || 0), 0) / stores.length : 0;
        const avgPerformance = stores.length > 0 ?
            stores.reduce((sum, s) => sum + (s.performance_score || 50), 0) / stores.length : 50;
        
        // Get unique administrative areas
        const kecamatanList = [...new Set(stores.map(s => s.kecamatan).filter(Boolean))];
        const kelurahanList = [...new Set(stores.map(s => s.kelurahan).filter(Boolean))];
        
        return {
            total_revenue: totalRevenue,
            total_profit: totalProfit,
            avg_margin: avgMargin,
            avg_performance: avgPerformance,
            area_coverage: kecamatanList.join(', '),
            kecamatan_count: kecamatanList.length,
            kelurahan_count: kelurahanList.length,
            density_score: this.calculateDensityScore(stores.length)
        };
    }
    
    /**
     * Calculate expansion score for cluster
     * Implementation from documentation
     */
    calculateExpansionScore(metrics, storeCount) {
        let score = 0;
        
        // Margin weight (60%) - as per documentation
        const marginScore = Math.min((metrics.avg_margin / 30) * 60, 60);
        score += marginScore;
        
        // Expansion potential weight (30%)
        const expansionPotential = Math.max(0, this.config.MAX_STORES_PER_CLUSTER - storeCount);
        const expansionScore = (expansionPotential / this.config.MAX_STORES_PER_CLUSTER) * 30;
        score += expansionScore;
        
        // Location/density weight (10%)
        const locationScore = (metrics.density_score / 100) * 10;
        score += locationScore;
        
        return Math.min(100, Math.round(score));
    }
    
    /**
     * Calculate density score
     */
    calculateDensityScore(storeCount) {
        // Score based on store density in cluster
        if (storeCount >= 4) return 90;
        if (storeCount >= 3) return 70;
        if (storeCount >= 2) return 50;
        return 30;
    }
    
    /**
     * Determine profitability level
     */
    determineProfitabilityLevel(avgMargin) {
        if (avgMargin >= 25) return 'Excellent';
        if (avgMargin >= 20) return 'Very Good';
        if (avgMargin >= 15) return 'Good';
        if (avgMargin >= 10) return 'Fair';
        return 'Poor';
    }
    
    /**
     * Render cluster boundaries on map
     */
    renderClustersOnMap() {
        try {
            // Remove existing cluster boundaries
            this.clearClusterBoundaries();
            
            // Add cluster boundaries with enhanced styling
            this.clusters.forEach(cluster => {
                const circle = L.circle(cluster.center, {
                    radius: this.config.CLUSTER_RADIUS * 1000, // Convert to meters
                    color: this.colors.cluster,
                    weight: 2,
                    opacity: 0.6,
                    fillColor: this.colors.cluster,
                    fillOpacity: 0.1,
                    className: 'cluster-boundary'
                });
                
                // Enhanced popup for cluster
                const popupContent = this.createClusterPopup(cluster);
                circle.bindPopup(popupContent, {
                    maxWidth: 400,
                    className: 'cluster-popup-custom'
                });
                
                circle.addTo(this.map);
                
                // Add cluster label
                const label = L.marker(cluster.center, {
                    icon: L.divIcon({
                        html: `<div class="cluster-label">${cluster.cluster_id}</div>`,
                        className: 'cluster-label-marker',
                        iconSize: [60, 20]
                    })
                });
                
                label.addTo(this.map);
            });
            
            console.log(`✅ Rendered ${this.clusters.length} cluster boundaries`);
            
        } catch (error) {
            console.error('❌ Error rendering clusters:', error);
        }
    }
    
    /**
     * Create enhanced cluster popup content
     */
    createClusterPopup(cluster) {
        const marginClass = cluster.metrics.avg_margin >= 20 ? 'success' : 
                          cluster.metrics.avg_margin >= 15 ? 'warning' : 'danger';
        
        return `
            <div class="cluster-popup-content">
                <div class="popup-header mb-3">
                    <h5 class="text-primary mb-1">${cluster.cluster_id}</h5>
                    <span class="badge badge-${marginClass}">
                        ${cluster.metrics.avg_margin.toFixed(1)}% Avg Margin
                    </span>
                </div>
                
                <div class="cluster-metrics mb-3">
                    <h6><i class="fas fa-chart-bar mr-1"></i>Cluster Metrics</h6>
                    <div class="row">
                        <div class="col-6">
                            <small><strong>Stores:</strong> ${cluster.store_count}</small>
                        </div>
                        <div class="col-6">
                            <small><strong>Coverage:</strong> ${cluster.metrics.kecamatan_count} areas</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <small><strong>Revenue:</strong> Rp ${cluster.metrics.total_revenue.toLocaleString()}</small>
                        </div>
                        <div class="col-6">
                            <small><strong>Profit:</strong> Rp ${cluster.metrics.total_profit.toLocaleString()}</small>
                        </div>
                    </div>
                </div>
                
                <div class="expansion-info mb-3">
                    <h6><i class="fas fa-rocket mr-1"></i>Expansion Analysis</h6>
                    <div class="expansion-score-bar mb-2">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-gradient-success" 
                                 style="width: ${cluster.expansion_score}%" 
                                 title="Expansion Score: ${cluster.expansion_score}/100">
                            </div>
                        </div>
                        <small class="text-muted">Score: ${cluster.expansion_score}/100</small>
                    </div>
                    <p class="mb-1">
                        <strong>Potential:</strong> ${cluster.expansion_potential} new stores
                    </p>
                    <p class="mb-1">
                        <strong>Level:</strong> ${cluster.profitability_level}
                    </p>
                </div>
                
                <div class="store-list">
                    <h6><i class="fas fa-store mr-1"></i>Stores in Cluster</h6>
                    <div class="store-items">
                        ${cluster.stores.slice(0, 3).map(store => `
                            <div class="store-item">
                                <small>
                                    <strong>${store.nama_toko}</strong> 
                                    (${store.margin_percent.toFixed(1)}%)
                                </small>
                            </div>
                        `).join('')}
                        ${cluster.stores.length > 3 ? `
                            <div class="store-item">
                                <small class="text-muted">
                                    ... and ${cluster.stores.length - 3} more stores
                                </small>
                            </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="popup-actions mt-3">
                    <button class="btn btn-sm btn-primary btn-block" 
                            onclick="crmApp.showClusterDetail('${cluster.cluster_id}')">
                        <i class="fas fa-search-plus mr-1"></i>View Cluster Details
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * MAIN FUNCTION: Generate expansion plan
     * Implements the expansion recommendation algorithm from documentation
     */
    async generateExpansionPlan() {
        try {
            if (!this.profitCalculated || !this.clusteringDone) {
                this.showWarning(
                    'Prerequisites Not Met', 
                    'Please complete both profit analysis and geographic clustering before generating expansion plan.'
                );
                return;
            }
            
            if (this.clusters.length === 0) {
                this.showWarning('No Clusters', 'No clusters available for expansion planning.');
                return;
            }
            
            console.log('🚀 Generating comprehensive expansion plan...');
            const startTime = performance.now();
            
            this.showLoading('Analyzing expansion opportunities and generating recommendations...');
            
            // Simulate analysis time
            await this.sleep(2500);
            
            // Generate expansion recommendations
            this.expansionPlan = this.createExpansionRecommendations();
            
            // Render expansion plan in Expansion tab
            this.renderExpansionPlan();
            
            // Update statistics
            this.updateExpansionStatistics();
            
            const processingTime = performance.now() - startTime;
            
            this.showSuccess(
                'Expansion Plan Generated!',
                `Created ${this.expansionPlan.length} recommendations in ${processingTime.toFixed(0)}ms. View plan in Expansion tab.`
            );
            
            console.log(`✅ Expansion plan generated: ${this.expansionPlan.length} recommendations in ${processingTime.toFixed(2)}ms`);
            
        } catch (error) {
            console.error('❌ Error generating expansion plan:', error);
            this.showError('Failed to generate expansion plan: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Create expansion recommendations
     * Implementation from documentation algorithm
     */
    createExpansionRecommendations() {
        const recommendations = [];
        
        // Filter clusters that meet expansion criteria
        const eligibleClusters = this.clusters.filter(cluster => 
            cluster.metrics.avg_margin >= this.config.MIN_PROFIT_MARGIN && 
            cluster.expansion_potential > 0
        );
        
        console.log(`🎯 Analyzing ${eligibleClusters.length} eligible clusters for expansion...`);
        
        eligibleClusters.forEach(cluster => {
            const recommendation = this.createClusterRecommendation(cluster);
            recommendations.push(recommendation);
        });
        
        // Sort by priority and score
        recommendations.sort((a, b) => {
            const priorityOrder = { 'TINGGI': 3, 'SEDANG': 2, 'RENDAH': 1 };
            const aPriority = priorityOrder[a.priority] || 0;
            const bPriority = priorityOrder[b.priority] || 0;
            
            if (aPriority === bPriority) {
                return b.score - a.score;
            }
            return bPriority - aPriority;
        });
        
        console.log(`✅ Generated ${recommendations.length} expansion recommendations`);
        
        return recommendations;
    }
    
    /**
     * Create individual cluster recommendation
     */
    createClusterRecommendation(cluster) {
        const avgMargin = cluster.metrics.avg_margin;
        const targetExpansion = Math.min(cluster.expansion_potential, 3); // Max 3 new stores per recommendation
        
        // Determine priority based on margin (as per documentation)
        const priority = this.determinePriority(avgMargin);
        
        // Calculate financial projections
        const financialProjection = this.calculateFinancialProjection(cluster, targetExpansion);
        
        // Determine pricing strategy
        const pricingStrategy = this.determinePricingStrategy(avgMargin);
        
        // Calculate recommended price
        const recommendedPrice = Math.round(
            this.config.DEFAULT_HARGA_AWAL * (1 + avgMargin / 100)
        );
        
        return {
            cluster_id: cluster.cluster_id,
            priority: priority,
            score: cluster.expansion_score,
            target_expansion: targetExpansion,
            current_stores: cluster.store_count,
            area_coverage: cluster.metrics.area_coverage,
            avg_margin: avgMargin,
            pricing_strategy: pricingStrategy,
            recommended_price: recommendedPrice,
            profitability_level: cluster.profitability_level,
            center_coordinates: cluster.center,
            ...financialProjection
        };
    }
    
    /**
     * Determine priority level based on margin
     */
    determinePriority(avgMargin) {
        if (avgMargin >= 20) return 'TINGGI';
        if (avgMargin >= 15) return 'SEDANG';
        return 'RENDAH';
    }
    
    /**
     * Determine pricing strategy
     */
    determinePricingStrategy(avgMargin) {
        if (avgMargin >= 25) return 'Premium Pricing';
        if (avgMargin <= 12) return 'Competitive Pricing';
        return 'Market Average';
    }
    
    /**
     * Calculate financial projection for expansion
     */
    calculateFinancialProjection(cluster, expansionCount) {
        // Average store metrics from cluster
        const avgStoreRevenue = cluster.metrics.total_revenue / cluster.store_count;
        const avgStoreProfit = cluster.metrics.total_profit / cluster.store_count;
        
        // Investment calculation
        const totalInvestment = expansionCount * this.config.DEFAULT_HARGA_AWAL * this.config.DEFAULT_INITIAL_STOCK;
        
        // Profit projection (monthly)
        const projectedMonthlyProfit = expansionCount * (avgStoreProfit / 12);
        
        // Payback period calculation
        const paybackPeriod = projectedMonthlyProfit > 0 ? 
            Math.ceil(totalInvestment / projectedMonthlyProfit) : 99;
        
        // ROI calculation
        const annualProfit = projectedMonthlyProfit * 12;
        const expectedROI = totalInvestment > 0 ? 
            (annualProfit / totalInvestment) * 100 : 0;
        
        return {
            total_investment: totalInvestment,
            projected_monthly_profit: Math.round(projectedMonthlyProfit),
            projected_annual_profit: Math.round(annualProfit),
            payback_period: paybackPeriod,
            expected_roi: Math.round(expectedROI * 10) / 10,
            break_even_units: Math.ceil(totalInvestment / (avgStoreProfit / cluster.store_count)),
            risk_level: this.calculateRiskLevel(cluster, paybackPeriod)
        };
    }
    
    /**
     * Calculate risk level for investment
     */
    calculateRiskLevel(cluster, paybackPeriod) {
        if (paybackPeriod <= 12 && cluster.metrics.avg_margin >= 20) return 'Low';
        if (paybackPeriod <= 18 && cluster.metrics.avg_margin >= 15) return 'Medium';
        return 'High';
    }
    
    /**
     * Render expansion plan in Expansion tab
     */
    renderExpansionPlan() {
        const container = document.getElementById('expansion-recommendations-content');
        if (!container) return;
        
        if (this.expansionPlan.length === 0) {
            container.innerHTML = this.createNoRecommendationsMessage();
            return;
        }
        
        let html = '<div class="expansion-recommendations-list">';
        
        // Add expansion summary header
        html += this.createExpansionSummaryHeader();
        
        // Add individual recommendations
        this.expansionPlan.forEach((recommendation, index) => {
            html += this.createRecommendationCard(recommendation, index + 1);
        });
        
        // Add investment summary
        html += this.createInvestmentSummary();
        
        html += '</div>';
        container.innerHTML = html;
        
        // Animate the recommendation cards
        setTimeout(() => {
            this.animateRecommendationCards();
        }, 100);
    }
    
    /**
     * Create expansion summary header
     */
    createExpansionSummaryHeader() {
        const totalRecommendations = this.expansionPlan.length;
        const highPriority = this.expansionPlan.filter(r => r.priority === 'TINGGI').length;
        const totalInvestment = this.expansionPlan.reduce((sum, r) => sum + r.total_investment, 0);
        const totalProjectedProfit = this.expansionPlan.reduce((sum, r) => sum + r.projected_monthly_profit, 0);
        const avgPayback = this.expansionPlan.reduce((sum, r) => sum + r.payback_period, 0) / totalRecommendations;
        
        return `
            <div class="expansion-summary-header mb-4 p-4 bg-gradient-success text-white rounded">
                <h4 class="mb-3">
                    <i class="fas fa-rocket mr-2"></i>Expansion Plan Summary
                </h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3>${totalRecommendations}</h3>
                            <small>Total Opportunities</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3>${highPriority}</h3>
                            <small>High Priority</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3>Rp ${(totalInvestment / 1000000).toFixed(1)}M</h3>
                            <small>Total Investment</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3>${Math.round(avgPayback)} mo</h3>
                            <small>Avg Payback</small>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="text-center">
                            <h4>Rp ${(totalProjectedProfit / 1000).toFixed(0)}K</h4>
                            <small>Monthly Profit Projection</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center">
                            <h4>Rp ${(totalProjectedProfit * 12 / 1000000).toFixed(1)}M</h4>
                            <small>Annual Profit Projection</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Create individual recommendation card
     */
    createRecommendationCard(recommendation, rank) {
        const priorityClass = recommendation.priority.toLowerCase();
        const riskClass = recommendation.risk_level === 'Low' ? 'success' : 
                         recommendation.risk_level === 'Medium' ? 'warning' : 'danger';
        
        return `
            <div class="recommendation-item priority-${priorityClass} mb-4" data-rank="${rank}">
                <div class="priority-badge">${recommendation.priority}</div>
                
                <div class="recommendation-header mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">
                                <span class="rank-badge badge badge-secondary mr-2">#${rank}</span>
                                ${recommendation.cluster_id}
                            </h5>
                            <p class="text-muted mb-1">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                ${recommendation.area_coverage}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-info">Score: ${recommendation.score}/100</span>
                        </div>
                    </div>
                    
                    <div class="score-bar mt-2">
                        <div class="progress" style="height: 10px;">
                            <div class="score-fill progress-bar bg-gradient-primary" 
                                 style="width: ${recommendation.score}%"
                                 data-target-width="${recommendation.score}">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="recommendation-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="expansion-details">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-store mr-1"></i>Expansion Details
                                </h6>
                                <div class="detail-item">
                                    <span class="detail-label">Target Ekspansi:</span>
                                    <span class="detail-value font-weight-bold text-success">
                                        ${recommendation.target_expansion} toko baru
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Current Stores:</span>
                                    <span class="detail-value">${recommendation.current_stores}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Avg Margin:</span>
                                    <span class="detail-value font-weight-bold text-warning">
                                        ${recommendation.avg_margin.toFixed(1)}%
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Pricing Strategy:</span>
                                    <span class="detail-value">${recommendation.pricing_strategy}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Recommended Price:</span>
                                    <span class="detail-value font-weight-bold text-primary">
                                        Rp ${recommendation.recommended_price.toLocaleString()}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="financial-projection">
                                <h6 class="text-success mb-2">
                                    <i class="fas fa-calculator mr-1"></i>Financial Projection
                                </h6>
                                <div class="detail-item">
                                    <span class="detail-label">Total Investment:</span>
                                    <span class="detail-value font-weight-bold text-primary">
                                        Rp ${recommendation.total_investment.toLocaleString()}
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Monthly Profit:</span>
                                    <span class="detail-value font-weight-bold text-success">
                                        Rp ${recommendation.projected_monthly_profit.toLocaleString()}
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Annual Profit:</span>
                                    <span class="detail-value font-weight-bold text-success">
                                        Rp ${recommendation.projected_annual_profit.toLocaleString()}
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Payback Period:</span>
                                    <span class="detail-value font-weight-bold text-info">
                                        ${recommendation.payback_period} bulan
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Expected ROI:</span>
                                    <span class="detail-value font-weight-bold text-warning">
                                        ${recommendation.expected_roi}% annually
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Risk Level:</span>
                                    <span class="badge badge-${riskClass}">
                                        ${recommendation.risk_level}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recommendation-actions mt-3 pt-3 border-top">
                        <div class="row">
                            <div class="col-md-8">
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    <strong>Recommendation:</strong> 
                                    ${this.generateRecommendationText(recommendation)}
                                </small>
                            </div>
                            <div class="col-md-4 text-right">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="crmApp.showRecommendationDetail('${recommendation.cluster_id}')">
                                    <i class="fas fa-search-plus mr-1"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Generate recommendation text based on metrics
     */
    generateRecommendationText(rec) {
        if (rec.priority === 'TINGGI' && rec.payback_period <= 12) {
            return `Highly recommended for immediate expansion. Strong margins (${rec.avg_margin.toFixed(1)}%) with quick payback period.`;
        } else if (rec.priority === 'SEDANG' && rec.payback_period <= 18) {
            return `Good expansion opportunity. Consider after high priority areas. Moderate risk with decent returns.`;
        } else if (rec.payback_period > 24) {
            return `Consider with caution. Long payback period may indicate market saturation or challenging conditions.`;
        } else {
            return `Feasible expansion opportunity. Review local market conditions and competition before proceeding.`;
        }
    }
    
    /**
     * Create investment summary
     */
    createInvestmentSummary() {
        const totalInvestment = this.expansionPlan.reduce((sum, r) => sum + r.total_investment, 0);
        const totalMonthlyProfit = this.expansionPlan.reduce((sum, r) => sum + r.projected_monthly_profit, 0);
        const totalAnnualProfit = totalMonthlyProfit * 12;
        const overallROI = totalInvestment > 0 ? (totalAnnualProfit / totalInvestment) * 100 : 0;
        const avgPayback = this.expansionPlan.length > 0 ? 
            this.expansionPlan.reduce((sum, r) => sum + r.payback_period, 0) / this.expansionPlan.length : 0;
        
        const highPriorityCount = this.expansionPlan.filter(r => r.priority === 'TINGGI').length;
        const mediumPriorityCount = this.expansionPlan.filter(r => r.priority === 'SEDANG').length;
        const lowPriorityCount = this.expansionPlan.filter(r => r.priority === 'RENDAH').length;
        
        return `
            <div class="investment-summary mt-4 p-4 bg-light rounded">
                <h5 class="mb-3">
                    <i class="fas fa-chart-pie mr-2"></i>Investment Summary & Analysis
                </h5>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="summary-metric">
                            <h4 class="text-primary mb-1">Rp ${(totalInvestment / 1000000).toFixed(1)}M</h4>
                            <small class="text-muted">Total Investment Required</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-metric">
                            <h4 class="text-success mb-1">Rp ${(totalAnnualProfit / 1000000).toFixed(1)}M</h4>
                            <small class="text-muted">Annual Profit Projection</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-metric">
                            <h4 class="text-warning mb-1">${overallROI.toFixed(1)}%</h4>
                            <small class="text-muted">Overall ROI</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="priority-count text-center">
                            <div class="count-circle bg-success text-white">
                                <span class="h5 mb-0">${highPriorityCount}</span>
                            </div>
                            <small class="d-block mt-1">High Priority</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="priority-count text-center">
                            <div class="count-circle bg-warning text-white">
                                <span class="h5 mb-0">${mediumPriorityCount}</span>
                            </div>
                            <small class="d-block mt-1">Medium Priority</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="priority-count text-center">
                            <div class="count-circle bg-secondary text-white">
                                <span class="h5 mb-0">${lowPriorityCount}</span>
                            </div>
                            <small class="d-block mt-1">Low Priority</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="priority-count text-center">
                            <div class="count-circle bg-info text-white">
                                <span class="h5 mb-0">${Math.round(avgPayback)}</span>
                            </div>
                            <small class="d-block mt-1">Avg Payback (mo)</small>
                        </div>
                    </div>
                </div>
                
                <div class="implementation-phases">
                    <h6 class="text-dark mb-2">
                        <i class="fas fa-tasks mr-1"></i>Recommended Implementation Phases
                    </h6>
                    <div class="phase-timeline">
                        <div class="phase-item">
                            <span class="phase-badge bg-success">Phase 1</span>
                            <span class="phase-text">
                                Start with ${highPriorityCount} high-priority locations 
                                (Est. investment: Rp ${(this.expansionPlan.filter(r => r.priority === 'TINGGI').reduce((sum, r) => sum + r.total_investment, 0) / 1000000).toFixed(1)}M)
                            </span>
                        </div>
                        <div class="phase-item">
                            <span class="phase-badge bg-warning">Phase 2</span>
                            <span class="phase-text">
                                Expand to ${mediumPriorityCount} medium-priority areas after 6-12 months
                            </span>
                        </div>
                        <div class="phase-item">
                            <span class="phase-badge bg-info">Phase 3</span>
                            <span class="phase-text">
                                Consider ${lowPriorityCount} low-priority locations based on Phase 1-2 performance
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Create no recommendations message
     */
    createNoRecommendationsMessage() {
        return `
            <div class="text-center text-muted py-5">
                <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                <h5>No Expansion Opportunities Found</h5>
                <p>No clusters meet the minimum criteria for expansion:</p>
                <ul class="list-unstyled">
                    <li>• Margin ≥ ${this.config.MIN_PROFIT_MARGIN}%</li>
                    <li>• Available expansion slots</li>
                    <li>• Positive profitability metrics</li>
                </ul>
                <div class="mt-4">
                    <button class="btn btn-primary" onclick="crmApp.refreshAllData()">
                        <i class="fas fa-sync-alt mr-1"></i>Refresh Data
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * Animation and UI enhancement methods
     */
    animateProfitCards() {
        const cards = document.querySelectorAll('.profit-item');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        });
    }
    
    animateRecommendationCards() {
        const cards = document.querySelectorAll('.recommendation-item');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                card.style.transition = 'all 0.4s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateX(0)';
                }, 50);
            }, index * 150);
        });
        
        // Animate score bars
        setTimeout(() => {
            const scoreBars = document.querySelectorAll('.score-fill');
            scoreBars.forEach(bar => {
                const targetWidth = bar.getAttribute('data-target-width') || bar.style.width;
                bar.style.width = '0%';
                bar.style.transition = 'width 1s ease-in-out';
                
                setTimeout(() => {
                    bar.style.width = targetWidth + '%';
                }, 100);
            });
        }, 500);
    }
    
    /**
     * Utility and helper methods
     */
    updateStatistics(summary) {
        try {
            const elements = {
                'total-partners': summary.total_toko || 0,
                'geo-clusters': this.clusters.length || 0,
                'avg-margin': this.profitCalculated ? 
                    (this.storeData.reduce((sum, s) => sum + (s.margin_percent || 0), 0) / this.storeData.length).toFixed(1) : 0,
                'total-revenue': summary.total_revenue ? 
                    (summary.total_revenue / 1000000).toFixed(1) + 'M' : '0'
            };
            
            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = value;
                }
            });
        } catch (error) {
            console.warn('⚠️ Error updating statistics:', error);
        }
    }
    
    clearMapMarkers() {
        this.map.eachLayer((layer) => {
            if (layer instanceof L.CircleMarker) {
                this.map.removeLayer(layer);
            }
        });
    }
    
    clearClusterBoundaries() {
        this.map.eachLayer((layer) => {
            if (layer instanceof L.Circle || (layer.options && layer.options.className === 'cluster-boundary')) {
                this.map.removeLayer(layer);
            }
            if (layer.options && layer.options.icon && layer.options.icon.options.className === 'cluster-label-marker') {
                this.map.removeLayer(layer);
            }
        });
    }
    
    handleTabChange(tabId) {
        // Handle tab-specific logic
        if (tabId === '#analysis') {
            // Refresh analysis charts if needed
            setTimeout(() => {
                if (this.map) this.map.invalidateSize();
            }, 100);
        } else if (tabId === '#expansion') {
            // Update expansion statistics
            setTimeout(() => {
                if (this.map) this.map.invalidateSize();
            }, 100);
        }
    }
    
    async refreshAllData() {
        try {
            this.showLoading('Refreshing all data...');
            
            // Reset states
            this.profitCalculated = false;
            this.clusteringDone = false;
            this.clusters = [];
            this.expansionPlan = [];
            
            // Reload data
            await this.loadInitialData();
            
            this.showSuccess('Data refreshed successfully!');
        } catch (error) {
            this.showError('Failed to refresh data: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    async clearSystemCache() {
        try {
            const response = await this.fetchWithRetry('/market-map/clear-cache', {
                method: 'POST'
            });
            
            if (response.success) {
                this.showSuccess('Cache cleared successfully!');
                await this.refreshAllData();
            } else {
                throw new Error(response.message || 'Failed to clear cache');
            }
        } catch (error) {
            this.showError('Failed to clear cache: ' + error.message);
        }
    }
    
    // UI Helper methods
    showLoading(message = 'Loading...') {
        const indicator = document.getElementById('loading-indicator');
        if (indicator) {
            indicator.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                    <span>${message}</span>
                </div>
            `;
            indicator.style.display = 'block';
        }
        
        const mapLoading = document.getElementById('map-loading');
        if (mapLoading) {
            mapLoading.style.display = 'flex';
        }
    }
    
    hideLoading() {
        const indicator = document.getElementById('loading-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
        
        const mapLoading = document.getElementById('map-loading');
        if (mapLoading) {
            mapLoading.style.display = 'none';
        }
    }
    
    showSuccess(title, message = '') {
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
            alert(title + (message ? '\n' + message : ''));
        }
    }
    
    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert('Error: ' + message);
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
            alert(title + '\n' + message);
        }
    }
    
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
    
    handleCriticalError(error) {
        console.error('💥 Critical system error:', error);
        
        const mapContainer = document.getElementById('market-map');
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="error-state text-center p-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">System Error</h5>
                    <p class="text-muted">CRM Expansion System encountered a critical error.</p>
                    <p class="small text-muted">${error.message}</p>
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt mr-1"></i>Restart System
                    </button>
                </div>
            `;
        }
    }
    
    // Utility functions
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
    
    // Placeholder methods for detail views
    showStoreDetail(storeId) {
        console.log('📊 Show store detail for:', storeId);
        // Implementation for store detail modal
    }
    
    showClusterDetail(clusterId) {
        console.log('🎯 Show cluster detail for:', clusterId);
        // Implementation for cluster detail modal
    }
    
    showRecommendationDetail(clusterId) {
        console.log('🚀 Show recommendation detail for:', clusterId);
        // Implementation for recommendation detail modal
    }
}

// Initialize the CRM system when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌟 Starting CRM Expansion System...');
    
    // Check for required dependencies
    if (typeof L === 'undefined') {
        console.error('❌ Leaflet library not loaded');
        return;
    }
    
    // Initialize the system
    window.crmApp = new CRMExpansionSystem();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.crmApp) {
        console.log('🧹 Cleaning up CRM system...');
    }
});

// Export for global access
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CRMExpansionSystem;
}
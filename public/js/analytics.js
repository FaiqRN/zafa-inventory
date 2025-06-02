/**
 * ANALYTICS DEBUG MODE
 * Tambahkan di bagian atas analytics.js atau gunakan sebagai file terpisah
 */

// Mode debug - set ke true untuk testing
const DEBUG_MODE = true;

// Override function loadOverviewData untuk testing
function loadOverviewDataDebug() {
    return new Promise(function(resolve, reject) {
        console.log('Loading overview data (DEBUG MODE)...');
        
        // Data sample untuk testing
        const sampleResponse = {
            success: true,
            kpi: {
                total_partners: 38,
                total_revenue: 125000000,
                avg_sales_rate: 78.5,
                total_pengiriman: 156,
                partners_growth: 12.5,
                revenue_growth: 18.2,
                sales_rate_growth: 5.1,
                pengiriman_growth: 8.7
            },
            monthly_revenue: [
                {month: 'Jan 2024', total_revenue: 18500000},
                {month: 'Feb 2024', total_revenue: 22300000},
                {month: 'Mar 2024', total_revenue: 26100000},
                {month: 'Apr 2024', total_revenue: 19800000},
                {month: 'May 2024', total_revenue: 24500000},
                {month: 'Jun 2024', total_revenue: 28200000}
            ],
            channel_distribution: {
                b2b_percentage: 75,
                b2c_percentage: 25,
                b2b_revenue: 93750000,
                b2c_revenue: 31250000
            },
            regional_data: [
                {wilayah: 'Malang Kota', total_partners: 12, total_revenue: 45000000, sales_rate: 82.5},
                {wilayah: 'Malang Kabupaten', total_partners: 18, total_revenue: 52000000, sales_rate: 75.2},
                {wilayah: 'Kota Batu', total_partners: 8, total_revenue: 28000000, sales_rate: 88.1}
            ]
        };
        
        // Simulasi loading delay
        setTimeout(function() {
            console.log('Sample overview data loaded:', sampleResponse);
            
            // Update KPIs
            updateElement('totalPartners', sampleResponse.kpi.total_partners);
            updateElement('totalRevenue', formatCurrency(sampleResponse.kpi.total_revenue));
            updateElement('avgSalesRate', sampleResponse.kpi.avg_sales_rate + '%');
            updateElement('totalPengiriman', sampleResponse.kpi.total_pengiriman);
            
            // Update change indicators
            updateElement('partnersChange', '+' + sampleResponse.kpi.partners_growth + '%');
            updateElement('revenueChange', '+' + sampleResponse.kpi.revenue_growth + '%');
            updateElement('salesRateChange', '+' + sampleResponse.kpi.sales_rate_growth + '%');
            updateElement('pengirimanChange', '+' + sampleResponse.kpi.pengiriman_growth + '%');
            
            // Create charts
            createOverviewRevenueChart(sampleResponse.monthly_revenue);
            createOverviewChannelChart(sampleResponse.channel_distribution);
            createOverviewRegionalChart(sampleResponse.regional_data);
            
            resolve(sampleResponse);
        }, 1000);
    });
}

// Override loadAllAnalytics untuk debug mode
function loadAllAnalyticsDebug() {
    console.log('Loading all analytics (DEBUG MODE)...');
    showLoading();
    
    // Load semua data sample
    Promise.all([
        loadOverviewDataDebug(),
        loadPartnerPerformanceDataDebug(),
        loadInventoryDataDebug(),
        loadProductVelocityDataDebug(),
        loadProfitabilityDataDebug(),
        loadChannelComparisonDataDebug(),
        loadPredictiveDataDebug()
    ])
    .then(function() {
        hideLoading();
        console.log('All analytics data loaded successfully (DEBUG MODE)');
        showSuccessMessage('Debug data loaded successfully!');
    })
    .catch(function(error) {
        hideLoading();
        console.error('Error loading analytics data (DEBUG MODE):', error);
        showErrorMessage('Failed to load debug data');
    });
}

// Sample data untuk section lainnya
function loadPartnerPerformanceDataDebug() {
    return new Promise(function(resolve) {
        setTimeout(function() {
            const sampleData = {
                summary: {avg_sales_rate: 75.8, need_attention: 3, total_partners: 38},
                partners: [
                    {nama_toko: 'Toko Makmur', sales_rate: 95.2, grade: 'A+'},
                    {nama_toko: 'Warung Berkah', sales_rate: 88.7, grade: 'A'},
                    {nama_toko: 'Toko Sejahtera', sales_rate: 82.1, grade: 'A'},
                    {nama_toko: 'Mini Market Jaya', sales_rate: 76.5, grade: 'B'},
                    {nama_toko: 'Toko Merdeka', sales_rate: 71.2, grade: 'B'}
                ],
                grade_distribution: {'A+': 5, 'A': 12, 'B': 18, 'C': 3},
                performance_trends: [
                    {month: 'Jan', avg_performance: 72.1},
                    {month: 'Feb', avg_performance: 74.5},
                    {month: 'Mar', avg_performance: 76.8},
                    {month: 'Apr', avg_performance: 75.2},
                    {month: 'May', avg_performance: 78.1}
                ]
            };
            
            // Update KPIs
            updateElement('partnerAvgSales', sampleData.summary.avg_sales_rate + '%');
            updateElement('needAttention', sampleData.summary.need_attention);
            updateElement('totalActivePartners', sampleData.summary.total_partners);
            
            // Create charts
            createPartnerRankingChart(sampleData.partners);
            createGradeDistributionChart(sampleData.grade_distribution);
            createPerformanceTrendChart(sampleData.performance_trends);
            
            resolve(sampleData);
        }, 800);
    });
}

function loadInventoryDataDebug() {
    return new Promise(function(resolve) {
        setTimeout(function() {
            const sampleData = {
                summary: {
                    avg_turnover_rate: 2.8,
                    avg_efficiency: 87.5,
                    waste_reduction_potential: 12.5,
                    retur_rate: 8.2
                }
            };
            
            updateElement('avgTurnoverRate', sampleData.summary.avg_turnover_rate + 'x');
            updateElement('inventoryEfficiency', sampleData.summary.avg_efficiency + '%');
            updateElement('wasteReduction', sampleData.summary.waste_reduction_potential + '%');
            updateElement('returRate', sampleData.summary.retur_rate + '%');
            
            resolve(sampleData);
        }, 600);
    });
}

function loadProductVelocityDataDebug() {
    return new Promise(function(resolve) {
        setTimeout(function() {
            const sampleData = {
                category_stats: {
                    hot_sellers: 8,
                    good_movers: 15,
                    slow_movers: 12,
                    dead_stock: 3
                }
            };
            
            updateElement('hotSellers', sampleData.category_stats.hot_sellers);
            updateElement('goodMovers', sampleData.category_stats.good_movers);
            updateElement('slowMovers', sampleData.category_stats.slow_movers);
            updateElement('deadStock', sampleData.category_stats.dead_stock);
            
            resolve(sampleData);
        }, 700);
    });
}

function loadProfitabilityDataDebug() {
    return new Promise(function(resolve) {
        setTimeout(function() {
            const sampleData = {
                summary: {
                    avg_roi: 24.8,
                    avg_profit_margin: 18.5,
                    hidden_costs_impact: 15.2,
                    total_net_profit: 42500000
                }
            };
            
            updateElement('avgROI', sampleData.summary.avg_roi + '%');
            updateElement('profitMargin', sampleData.summary.avg_profit_margin + '%');
            updateElement('hiddenCostsImpact', sampleData.summary.hidden_costs_impact + '%');
            updateElement('netProfit', formatCurrency(sampleData.summary.total_net_profit));
            
            resolve(sampleData);
        }, 900);
    });
}

function loadChannelComparisonDataDebug() {
    return new Promise(function(resolve) {
        setTimeout(function() {
            const sampleData = {
                channel_metrics: {
                    b2b_revenue: 93750000,
                    b2c_revenue: 31250000
                },
                summary: {
                    dominant_channel: 'B2B',
                    channel_diversity: 65.4
                }
            };
            
            updateElement('b2bRevenue', formatCurrency(sampleData.channel_metrics.b2b_revenue));
            updateElement('b2cRevenue', formatCurrency(sampleData.channel_metrics.b2c_revenue));
            updateElement('dominantChannel', sampleData.summary.dominant_channel);
            updateElement('channelDiversity', sampleData.summary.channel_diversity + '%');
            
            resolve(sampleData);
        }, 750);
    });
}

function loadPredictiveDataDebug() {
    return new Promise(function(resolve) {
        setTimeout(function() {
            const sampleData = {
                summary: {
                    forecast_accuracy: 78.5,
                    demand_growth_trend: 15.2,
                    partners_at_risk: 3,
                    new_opportunities: 7
                }
            };
            
            updateElement('predictionAccuracy', sampleData.summary.forecast_accuracy + '%');
            updateElement('demandGrowth', '+' + sampleData.summary.demand_growth_trend + '%');
            updateElement('partnersAtRisk', sampleData.summary.partners_at_risk);
            updateElement('newOpportunities', sampleData.summary.new_opportunities);
            
            resolve(sampleData);
        }, 1100);
    });
}

// Test function untuk dipanggil dari console
function enableDebugMode() {
    console.log('=== ENABLING DEBUG MODE ===');
    
    // Override functions
    window.loadOverviewData = loadOverviewDataDebug;
    window.loadAllAnalytics = loadAllAnalyticsDebug;
    window.refreshAllAnalytics = loadAllAnalyticsDebug;
    
    console.log('Debug mode enabled. Call loadAllAnalytics() to test.');
    
    // Auto load
    setTimeout(function() {
        console.log('Auto-loading debug data...');
        loadAllAnalyticsDebug();
    }, 1000);
}

// Auto-enable debug mode jika DEBUG_MODE = true
if (DEBUG_MODE) {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Auto-enabling debug mode...');
        enableDebugMode();
    });
}
/**
 * ANALYTICS DASHBOARD JAVASCRIPT
 * Zafa Potato Analytics CRM System
 * Complete implementation for analytics dashboard functionality
 */

// Global variables
let analyticsCharts = {};
let currentFilters = {
    periode: '1_tahun',
    wilayah: 'all',
    produk: 'all'
};

// Chart color schemes
const COLORS = {
    primary: '#0078d4',
    success: '#28a745',
    warning: '#ffc107',
    danger: '#dc3545',
    info: '#17a2b8',
    secondary: '#6c757d',
    light: '#f8f9fa',
    dark: '#343a40'
};

const GRADIENTS = {
    blue: ['#0078d4', '#106ebe'],
    green: ['#28a745', '#20c997'],
    orange: ['#ffc107', '#fd7e14'],
    red: ['#dc3545', '#e74c3c'],
    purple: ['#6f42c1', '#e83e8c']
};

// Chart.js global configuration
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing Analytics Dashboard...');
    initializeDashboard();
    setupEventHandlers();
    updateCurrentTimestamp();
    loadAllAnalytics();
});

/**
 * Initialize dashboard components
 */
function initializeDashboard() {
    // Show overview section by default
    showSection('overview');
    
    // Setup CSRF token for AJAX requests
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': token.getAttribute('content')
            }
        });
    }
    
    console.log('Dashboard initialized successfully');
}

/**
 * Update current timestamp with real-time
 */
function updateCurrentTimestamp() {
    const now = new Date();
    const options = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta'
    };
    
    const timestamp = now.toLocaleDateString('id-ID', options) + ' WIB';
    const timestampElement = document.getElementById('currentTimestamp');
    if (timestampElement) {
        timestampElement.textContent = timestamp;
    }
    
    // Update every minute
    setTimeout(updateCurrentTimestamp, 60000);
}

/**
 * Setup all event handlers
 */
function setupEventHandlers() {
    // Navigation button handlers
    document.querySelectorAll('.nav-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            switchToSection(section);
        });
    });
    
    // Filter change handlers
    const filterElements = ['periodeFilter', 'wilayahFilter', 'produkFilter'];
    filterElements.forEach(function(filterId) {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', function() {
                updateFilters();
                debounceLoadData();
            });
        }
    });
    
    // Window resize handler with debounce
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            resizeAllCharts();
        }, 250);
    });
    
    // Print event handlers
    window.addEventListener('beforeprint', handleBeforePrint);
    window.addEventListener('afterprint', handleAfterPrint);
    
    console.log('Event handlers setup completed');
}

/**
 * Switch to specific analytics section
 */
function switchToSection(sectionName) {
    console.log('Switching to section:', sectionName);
    
    // Update navigation active state
    document.querySelectorAll('.nav-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    
    const activeBtn = document.querySelector('[data-section="' + sectionName + '"]');
    if (activeBtn) {
        activeBtn.classList.add('active');
    }
    
    // Update content visibility
    document.querySelectorAll('.analytics-section').forEach(function(section) {
        section.classList.remove('active');
    });
    
    const activeSection = document.getElementById(sectionName + '-section');
    if (activeSection) {
        activeSection.classList.add('active');
    }
    
    // Refresh charts in active section with delay
    setTimeout(function() {
        resizeChartsInSection(sectionName);
    }, 150);
}

/**
 * Show specific section (alias for switchToSection)
 */
function showSection(sectionName) {
    switchToSection(sectionName);
}

/**
 * Update current filters from form inputs
 */
function updateFilters() {
    const periodeEl = document.getElementById('periodeFilter');
    const wilayahEl = document.getElementById('wilayahFilter');
    const produkEl = document.getElementById('produkFilter');
    
    currentFilters = {
        periode: periodeEl ? periodeEl.value : '1_tahun',
        wilayah: wilayahEl ? wilayahEl.value : 'all',
        produk: produkEl ? produkEl.value : 'all'
    };
    
    console.log('Filters updated:', currentFilters);
}

/**
 * Debounced data loading to prevent excessive API calls
 */
const debounceLoadData = debounce(function() {
    loadAllAnalytics();
}, 800);

/**
 * Load all analytics data with error handling
 */
function loadAllAnalytics() {
    console.log('Loading all analytics data...');
    showLoading();
    
    const loadPromises = [
        loadOverviewData(),
        loadPartnerPerformanceData(),
        loadInventoryData(),
        loadProductVelocityData(),
        loadProfitabilityData(),
        loadChannelComparisonData(),
        loadPredictiveData()
    ];
    
    Promise.all(loadPromises)
        .then(function() {
            hideLoading();
            console.log('All analytics data loaded successfully');
        })
        .catch(function(error) {
            hideLoading();
            console.error('Error loading analytics data:', error);
            showErrorMessage('Failed to load analytics data. Please try again.');
        });
}

/**
 * Refresh all analytics data (called by refresh button)
 */
function refreshAllAnalytics() {
    console.log('Refreshing all analytics...');
    
    // Destroy existing charts
    Object.keys(analyticsCharts).forEach(function(key) {
        if (analyticsCharts[key] && typeof analyticsCharts[key].destroy === 'function') {
            analyticsCharts[key].destroy();
            delete analyticsCharts[key];
        }
    });
    
    // Update timestamp
    updateCurrentTimestamp();
    
    // Reload all data
    loadAllAnalytics();
    
    // Show success message
    showSuccessMessage('Analytics data refreshed successfully');
}

// ===== DATA LOADING FUNCTIONS =====

/**
 * Load overview dashboard data
 */
function loadOverviewData() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/analytics/overview',
            method: 'GET',
            data: currentFilters,
            success: function(response) {
                console.log('Overview data loaded:', response);
                
                // Update KPIs with real data
                const kpi = response.kpi || {};
                updateElement('totalPartners', kpi.total_partners || 0);
                updateElement('totalRevenue', formatCurrency(kpi.total_revenue || 0));
                updateElement('avgSalesRate', (kpi.avg_sales_rate || 0) + '%');
                updateElement('totalPengiriman', kpi.total_pengiriman || 0);
                
                // Update change indicators
                updateElement('partnersChange', '+' + (kpi.partners_growth || 0) + '%');
                updateElement('revenueChange', '+' + (kpi.revenue_growth || 0) + '%');
                updateElement('salesRateChange', '+' + (kpi.sales_rate_growth || 0) + '%');
                updateElement('pengirimanChange', '+' + (kpi.pengiriman_growth || 0) + '%');
                
                // Create charts with real data
                createOverviewRevenueChart(response.monthly_revenue || []);
                createOverviewChannelChart(response.channel_distribution || {});
                createOverviewRegionalChart(response.regional_data || []);
                
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading overview data:', error);
                reject(error);
            }
        });
    });
}

/**
 * Load partner performance analytics data
 */
function loadPartnerPerformanceData() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/analytics/partner-performance',
            method: 'GET',
            data: currentFilters,
            success: function(response) {
                console.log('Partner performance data loaded:', response);
                
                // Update KPIs
                const summary = response.summary || {};
                updateElement('partnerAvgSales', (summary.avg_sales_rate || 0) + '%');
                updateElement('needAttention', summary.need_attention || 0);
                updateElement('totalActivePartners', summary.total_partners || 0);
                
                // Create charts
                createPartnerRankingChart(response.partners || []);
                createGradeDistributionChart(response.grade_distribution || []);
                createPerformanceTrendChart(response.performance_trends || []);
                
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading partner performance data:', error);
                reject(error);
            }
        });
    });
}

/**
 * Load inventory analytics data
 */
function loadInventoryData() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/analytics/inventory-analytics',
            method: 'GET',
            data: currentFilters,
            success: function(response) {
                console.log('Inventory data loaded:', response);
                
                // Update KPIs
                const summary = response.summary || {};
                updateElement('avgTurnoverRate', (summary.avg_turnover_rate || 0) + 'x');
                updateElement('inventoryEfficiency', (summary.avg_efficiency || 0) + '%');
                updateElement('wasteReduction', (summary.waste_reduction_potential || 0) + '%');
                updateElement('returRate', (summary.retur_rate || 0) + '%');
                
                // Create charts
                createInventoryTurnoverChart(response.inventory_data || []);
                createOptimalVsActualChart(response.inventory_data || []);
                createInventoryEfficiencyTrendChart(response.monthly_efficiency || []);
                
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading inventory data:', error);
                reject(error);
            }
        });
    });
}

/**
 * Load product velocity analytics data
 */
function loadProductVelocityData() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/analytics/product-velocity',
            method: 'GET',
            data: currentFilters,
            success: function(response) {
                console.log('Product velocity data loaded:', response);
                
                // Update KPIs
                const categoryStats = response.category_stats || {};
                updateElement('hotSellers', categoryStats.hot_sellers || 0);
                updateElement('goodMovers', categoryStats.good_movers || 0);
                updateElement('slowMovers', categoryStats.slow_movers || 0);
                updateElement('deadStock', categoryStats.dead_stock || 0);
                
                // Create charts
                createProductVelocityChart(response.products || []);
                createVelocityCategoryChart(categoryStats);
                createRegionalPreferenceChart(response.regional_preferences || []);
                
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading product velocity data:', error);
                reject(error);
            }
        });
    });
}

/**
 * Load profitability analysis data
 */
function loadProfitabilityData() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/analytics/profitability-analysis',
            method: 'GET',
            data: currentFilters,
            success: function(response) {
                console.log('Profitability data loaded:', response);
                
                // Update KPIs
                const summary = response.summary || {};
                updateElement('avgROI', (summary.avg_roi || 0) + '%');
                updateElement('profitMargin', (summary.avg_profit_margin || 0) + '%');
                updateElement('hiddenCostsImpact', (summary.hidden_costs_impact || 0) + '%');
                updateElement('netProfit', formatCurrency(summary.total_net_profit || 0));
                
                // Create charts
                createProfitabilityRankingChart(response.profitability_data || []);
                createCostBreakdownChart(response.cost_breakdown || {});
                createProfitabilityTrendChart(response.monthly_trend || []);
                
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading profitability data:', error);
                reject(error);
            }
        });
    });
}

/**
 * Load channel comparison data
 */
function loadChannelComparisonData() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/analytics/channel-comparison',
            method: 'GET',
            data: currentFilters,
            success: function(response) {
                console.log('Channel comparison data loaded:', response);
                
                // Update KPIs
                const channelMetrics = response.channel_metrics || {};
                const summary = response.summary || {};
                
                updateElement('b2bRevenue', formatCurrency(channelMetrics.b2b_revenue || 0));
                updateElement('b2cRevenue', formatCurrency(channelMetrics.b2c_revenue || 0));
                updateElement('dominantChannel', summary.dominant_channel || 'B2B');
                updateElement('channelDiversity', (summary.channel_diversity || 0) + '%');
                
                // Create charts
                createChannelComparisonChart(channelMetrics);
                createChannelTrendChart(response.monthly_comparison || []);
                createChannelMetricsChart(channelMetrics);
                
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading channel comparison data:', error);
                reject(error);
            }
        });
    });
}

/**
 * Load predictive analytics data
 */
function loadPredictiveData() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/analytics/predictive-analytics',
            method: 'GET',
            data: currentFilters,
            success: function(response) {
                console.log('Predictive data loaded:', response);
                
                // Update KPIs
                const summary = response.summary || {};
                updateElement('predictionAccuracy', (summary.forecast_accuracy || 0) + '%');
                updateElement('demandGrowth', '+' + (summary.demand_growth_trend || 0) + '%');
                updateElement('partnersAtRisk', summary.partners_at_risk || 0);
                updateElement('newOpportunities', summary.new_opportunities || 0);
                
                // Create charts
                createDemandForecastChart(response.demand_forecast || []);
                createRiskAssessmentChart(response.risk_assessment || []);
                createAIRecommendations(response.recommendations || []);
                
                resolve(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading predictive data:', error);
                reject(error);
            }
        });
    });
}

// ===== CHART CREATION FUNCTIONS =====

/**
 * Create overview revenue trend chart
 */
function createOverviewRevenueChart(monthlyData) {
    const ctx = document.getElementById('overviewRevenueChart');
    if (!ctx) return;
    
    destroyChart('overviewRevenue');
    
    const labels = monthlyData.map(item => item.month || item.label);
    const data = monthlyData.map(item => parseFloat(item.total_revenue || item.value || 0));
    
    analyticsCharts.overviewRevenue = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (Rp)',
                data: data,
                borderColor: COLORS.primary,
                backgroundColor: createGradient(ctx, GRADIENTS.blue),
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: COLORS.primary,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrencyShort(value);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create overview channel distribution chart
 */
function createOverviewChannelChart(channelData) {
    const ctx = document.getElementById('overviewChannelChart');
    if (!ctx) return;
    
    destroyChart('overviewChannel');
    
    const b2bPercentage = channelData.b2b_percentage || 75;
    const b2cPercentage = channelData.b2c_percentage || 25;
    
    analyticsCharts.overviewChannel = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['B2B Konsinyasi', 'B2C Direct Sales'],
            datasets: [{
                data: [b2bPercentage, b2cPercentage],
                backgroundColor: [COLORS.primary, COLORS.success],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        padding: 20, 
                        usePointStyle: true 
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create overview regional performance chart
 */
function createOverviewRegionalChart(regionalData) {
    const ctx = document.getElementById('overviewRegionalChart');
    if (!ctx) return;
    
    destroyChart('overviewRegional');
    
    const labels = regionalData.map(item => item.wilayah || item.region || 'Unknown');
    const data = regionalData.map(item => parseFloat(item.sales_rate || item.performance || 0));
    const colors = generateColors(labels.length);
    
    analyticsCharts.overviewRegional = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales Rate (%)',
                data: data,
                backgroundColor: colors,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    ticks: { maxRotation: 45 }
                }
            }
        }
    });
}

/**
 * Create partner ranking chart
 */
function createPartnerRankingChart(partners) {
    const ctx = document.getElementById('partnerRankingChart');
    if (!ctx) return;
    
    destroyChart('partnerRanking');
    
    const topPartners = partners.slice(0, 10);
    const labels = topPartners.map(p => p.nama_toko || 'Partner');
    const data = topPartners.map(p => parseFloat(p.sales_rate || 0));
    const colors = topPartners.map(p => getGradeColor(p.grade));
    
    analyticsCharts.partnerRanking = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales Rate (%)',
                data: data,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const partnerIndex = context.dataIndex;
                            const partner = topPartners[partnerIndex];
                            return [
                                'Sales Rate: ' + context.parsed.y + '%',
                                'Grade: ' + (partner.grade || 'N/A')
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    ticks: { maxRotation: 45 }
                }
            }
        }
    });
}

/**
 * Create grade distribution chart
 */
function createGradeDistributionChart(gradeDistribution) {
    const ctx = document.getElementById('gradeDistributionChart');
    if (!ctx) return;
    
    destroyChart('gradeDistribution');
    
    const labels = Object.keys(gradeDistribution).length > 0 ? 
        Object.keys(gradeDistribution) : ['A+', 'A', 'B', 'C'];
    const data = Object.keys(gradeDistribution).length > 0 ? 
        Object.values(gradeDistribution) : [3, 5, 8, 2];
    
    analyticsCharts.gradeDistribution = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [COLORS.success, COLORS.primary, COLORS.warning, COLORS.danger],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        padding: 15, 
                        usePointStyle: true 
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.parsed / total) * 100);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create performance trend chart
 */
function createPerformanceTrendChart(trendData) {
    const ctx = document.getElementById('performanceTrendChart');
    if (!ctx) return;
    
    destroyChart('performanceTrend');
    
    const labels = trendData.map(item => item.month || item.period);
    const data = trendData.map(item => parseFloat(item.avg_performance || item.performance || 0));
    
    analyticsCharts.performanceTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Avg Performance (%)',
                data: data,
                borderColor: COLORS.success,
                backgroundColor: createGradient(ctx, GRADIENTS.green),
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Performance: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create inventory turnover chart
 */
function createInventoryTurnoverChart(inventoryData) {
    const ctx = document.getElementById('inventoryTurnoverChart');
    if (!ctx) return;
    
    destroyChart('inventoryTurnover');
    
    const labels = inventoryData.slice(0, 8).map(item => item.nama_toko || 'Store');
    const turnoverData = inventoryData.slice(0, 8).map(item => parseFloat(item.turnover_rate || 0));
    
    analyticsCharts.inventoryTurnover = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Turnover Rate',
                data: turnoverData,
                borderColor: COLORS.info,
                backgroundColor: createGradient(ctx, GRADIENTS.blue),
                fill: true,
                tension: 0.4,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Turnover Rate: ' + context.parsed.y + 'x';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + 'x';
                        }
                    }
                },
                x: {
                    ticks: { maxRotation: 45 }
                }
            }
        }
    });
}

/**
 * Create optimal vs actual quantities chart
 */
function createOptimalVsActualChart(inventoryData) {
    const ctx = document.getElementById('optimalVsActualChart');
    if (!ctx) return;
    
    destroyChart('optimalVsActual');
    
    const labels = inventoryData.slice(0, 6).map(item => item.nama_barang || 'Product');
    const actualData = inventoryData.slice(0, 6).map(item => parseFloat(item.current_stock || 0));
    const optimalData = inventoryData.slice(0, 6).map(item => parseFloat(item.optimal_stock || 0));
    
    analyticsCharts.optimalVsActual = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Actual Stock',
                    data: actualData,
                    backgroundColor: COLORS.warning,
                    borderRadius: 4
                },
                {
                    label: 'Optimal Stock',
                    data: optimalData,
                    backgroundColor: COLORS.success,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' units';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { maxRotation: 45 }
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Create inventory efficiency trend chart
 */
function createInventoryEfficiencyTrendChart(monthlyData) {
    const ctx = document.getElementById('inventoryEfficiencyTrendChart');
    if (!ctx) return;
    
    destroyChart('inventoryEfficiencyTrend');
    
    const labels = monthlyData.map(item => item.month || item.period);
    const efficiencyData = monthlyData.map(item => parseFloat(item.efficiency || 0));
    const rotationData = monthlyData.map(item => parseFloat(item.avg_rotation_days || 0));
    
    analyticsCharts.inventoryEfficiencyTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Efficiency (%)',
                    data: efficiencyData,
                    borderColor: COLORS.success,
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4
                },
                {
                    label: 'Avg Rotation (days)',
                    data: rotationData,
                    borderColor: COLORS.danger,
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Efficiency (%)' },
                    beginAtZero: true,
                    max: 100
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Days' },
                    grid: { drawOnChartArea: false },
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Create product velocity chart
 */
function createProductVelocityChart(products) {
    const ctx = document.getElementById('productVelocityChart');
    if (!ctx) return;
    
    destroyChart('productVelocity');
    
    const topProducts = products.slice(0, 8);
    const labels = topProducts.map(p => p.nama_barang || 'Product');
    const data = topProducts.map(p => parseFloat(p.velocity_rate || 0));
    const colors = topProducts.map(p => getVelocityColor(p.velocity_rate));
    
    analyticsCharts.productVelocity = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Velocity Rate (%)',
                data: data,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const productIndex = context.dataIndex;
                            const product = topProducts[productIndex];
                            return [
                                'Velocity: ' + context.parsed.y + '%',
                                'Category: ' + (product.category || 'N/A')
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    ticks: { maxRotation: 45 }
                }
            }
        }
    });
}

/**
 * Create velocity category distribution chart
 */
function createVelocityCategoryChart(categoryStats) {
    const ctx = document.getElementById('velocityCategoryChart');
    if (!ctx) return;
    
    destroyChart('velocityCategory');
    
    const data = [
        categoryStats.hot_sellers || 0,
        categoryStats.good_movers || 0, 
        categoryStats.slow_movers || 0,
        categoryStats.dead_stock || 0
    ];
    
    analyticsCharts.velocityCategory = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Hot Sellers', 'Good Movers', 'Slow Movers', 'Dead Stock'],
            datasets: [{
                data: data,
                backgroundColor: [COLORS.success, COLORS.primary, COLORS.warning, COLORS.danger],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        padding: 15, 
                        usePointStyle: true 
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create regional preference radar chart
 */
function createRegionalPreferenceChart(regionalData) {
    const ctx = document.getElementById('regionalPreferenceChart');
    if (!ctx) return;
    
    destroyChart('regionalPreference');
    
    const labels = regionalData.length > 0 ? 
        regionalData.map(region => region.region || region.wilayah || 'Region') :
        ['Malang Kota', 'Malang Kabupaten', 'Kota Batu'];
    
    const data = regionalData.length > 0 ? 
        regionalData.map(region => parseFloat(region.avg_velocity || region.velocity || 0)) :
        [85, 72, 68];
    
    analyticsCharts.regionalPreference = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average Velocity Rate',
                data: data,
                borderColor: COLORS.primary,
                backgroundColor: 'rgba(0, 120, 212, 0.2)',
                fill: true,
                pointBackgroundColor: COLORS.primary,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.r + '%';
                        }
                    }
                }
            },
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create profitability ranking chart
 */
function createProfitabilityRankingChart(profitabilityData) {
    const ctx = document.getElementById('profitabilityRankingChart');
    if (!ctx) return;
    
    destroyChart('profitabilityRanking');
    
    const topPartners = profitabilityData.slice(0, 8);
    const labels = topPartners.map(item => item.nama_toko || 'Partner');
    const roiData = topPartners.map(item => parseFloat(item.roi || 0));
    const colors = roiData.map(roi => getROIColor(roi));
    
    analyticsCharts.profitabilityRanking = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'ROI (%)',
                data: roiData,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const partnerIndex = context.dataIndex;
                            const partner = topPartners[partnerIndex];
                            return [
                                'ROI: ' + context.parsed.y + '%',
                                'Profit: ' + formatCurrency(partner.profit || 0)
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    ticks: { maxRotation: 45 }
                }
            }
        }
    });
}

/**
 * Create cost breakdown pie chart
 */
function createCostBreakdownChart(costData) {
    const ctx = document.getElementById('costBreakdownChart');
    if (!ctx) return;
    
    destroyChart('costBreakdown');
    
    const data = [
        costData.cogs || 60000000,
        costData.logistics || 15000000,
        costData.opportunity || 8000000,
        costData.admin || 12000000,
        costData.holding || 5000000
    ];
    
    const labels = ['COGS', 'Logistics', 'Opportunity Cost', 'Admin', 'Holding'];
    
    analyticsCharts.costBreakdown = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [COLORS.primary, COLORS.warning, COLORS.danger, COLORS.info, COLORS.secondary],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        padding: 15, 
                        usePointStyle: true 
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.parsed / total) * 100);
                            return context.label + ': ' + formatCurrency(context.parsed) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create profitability trend chart
 */
function createProfitabilityTrendChart(monthlyTrend) {
    const ctx = document.getElementById('profitabilityTrendChart');
    if (!ctx) return;
    
    destroyChart('profitabilityTrend');
    
    const labels = monthlyTrend.map(item => item.month || item.period);
    const profitData = monthlyTrend.map(item => parseFloat(item.profit_margin || 0));
    
    analyticsCharts.profitabilityTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Profit Margin (%)',
                data: profitData,
                borderColor: COLORS.success,
                backgroundColor: createGradient(ctx, GRADIENTS.green),
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Profit Margin: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create channel comparison chart
 */
function createChannelComparisonChart(channelMetrics) {
    const ctx = document.getElementById('channelComparisonChart');
    if (!ctx) return;
    
    destroyChart('channelComparison');
    
    const b2bRevenue = channelMetrics.b2b_revenue || 0;
    const b2cRevenue = channelMetrics.b2c_revenue || 0;
    const total = b2bRevenue + b2cRevenue;
    
    const b2bPercentage = total > 0 ? Math.round((b2bRevenue / total) * 100) : 75;
    const b2cPercentage = total > 0 ? Math.round((b2cRevenue / total) * 100) : 25;
    
    const data = {
        labels: ['Revenue Share', 'Volume Share', 'Profit Margin', 'Customer Count'],
        datasets: [
            {
                label: 'B2B Konsinyasi',
                data: [
                    b2bPercentage,
                    channelMetrics.b2b_volume_percentage || 80,
                    channelMetrics.b2b_margin || 15,
                    channelMetrics.b2b_customers || 100
                ],
                backgroundColor: 'rgba(0, 120, 212, 0.7)',
                borderColor: COLORS.primary,
                borderWidth: 2
            },
            {
                label: 'B2C Direct Sales',
                data: [
                    b2cPercentage,
                    channelMetrics.b2c_volume_percentage || 20,
                    channelMetrics.b2c_margin || 25,
                    channelMetrics.b2c_customers || 500
                ],
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: COLORS.success,
                borderWidth: 2
            }
        ]
    };
    
    analyticsCharts.channelComparison = new Chart(ctx, {
        type: 'radar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

/**
 * Create channel trend chart
 */
function createChannelTrendChart(monthlyComparison) {
    const ctx = document.getElementById('channelTrendChart');
    if (!ctx) return;
    
    destroyChart('channelTrend');
    
    const labels = monthlyComparison.map(item => item.month || item.period);
    const b2bData = monthlyComparison.map(item => parseFloat(item.b2b_revenue || 0));
    const b2cData = monthlyComparison.map(item => parseFloat(item.b2c_revenue || 0));
    
    analyticsCharts.channelTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'B2B Revenue',
                    data: b2bData,
                    borderColor: COLORS.primary,
                    backgroundColor: 'rgba(0, 120, 212, 0.1)',
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'B2C Revenue',
                    data: b2cData,
                    borderColor: COLORS.success,
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrencyShort(value);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create channel metrics detailed chart
 */
function createChannelMetricsChart(channelMetrics) {
    const ctx = document.getElementById('channelMetricsChart');
    if (!ctx) return;
    
    destroyChart('channelMetrics');
    
    const metrics = ['Partners/Customers', 'Avg Order Value', 'Frequency (monthly)', 'Satisfaction (%)'];
    const b2bMetrics = [
        channelMetrics.b2b_partners || 50,
        channelMetrics.b2b_avg_order || 5000000,
        channelMetrics.b2b_frequency || 8,
        channelMetrics.b2b_satisfaction || 85
    ];
    const b2cMetrics = [
        channelMetrics.b2c_customers || 200,
        channelMetrics.b2c_avg_order || 500000,
        channelMetrics.b2c_frequency || 3,
        channelMetrics.b2c_satisfaction || 92
    ];
    
    analyticsCharts.channelMetrics = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: metrics,
            datasets: [
                {
                    label: 'B2B',
                    data: b2bMetrics,
                    backgroundColor: COLORS.primary,
                    borderRadius: 4
                },
                {
                    label: 'B2C',
                    data: b2cMetrics,
                    backgroundColor: COLORS.success,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const metricIndex = context.dataIndex;
                            let value = context.parsed.y;
                            
                            if (metricIndex === 1) { // Avg Order Value
                                value = formatCurrency(value);
                            } else if (metricIndex === 3) { // Satisfaction
                                value = value + '%';
                            }
                            
                            return context.dataset.label + ': ' + value;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Create demand forecast chart
 */
function createDemandForecastChart(forecast) {
    const ctx = document.getElementById('demandForecastChart');
    if (!ctx) return;
    
    destroyChart('demandForecast');
    
    const labels = forecast.map(item => item.month || item.period);
    const predictedData = forecast.map(item => parseFloat(item.predicted_demand || 0));
    const confidenceData = forecast.map(item => parseFloat(item.confidence || 0));
    const upperBound = forecast.map(item => parseFloat(item.upper_bound || 0));
    const lowerBound = forecast.map(item => parseFloat(item.lower_bound || 0));
    
    analyticsCharts.demandForecast = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Predicted Demand',
                    data: predictedData,
                    borderColor: COLORS.primary,
                    backgroundColor: createGradient(ctx, GRADIENTS.blue),
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Upper Bound',
                    data: upperBound,
                    borderColor: COLORS.warning,
                    backgroundColor: 'transparent',
                    fill: false,
                    borderDash: [5, 5],
                    yAxisID: 'y'
                },
                {
                    label: 'Lower Bound',
                    data: lowerBound,
                    borderColor: COLORS.warning,
                    backgroundColor: 'transparent',
                    fill: false,
                    borderDash: [5, 5],
                    yAxisID: 'y'
                },
                {
                    label: 'Confidence (%)',
                    data: confidenceData,
                    borderColor: COLORS.success,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.2,
                    borderDash: [10, 5],
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Units' },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Confidence (%)' },
                    grid: { drawOnChartArea: false },
                    max: 100,
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * Create risk assessment chart
 */
function createRiskAssessmentChart(riskData) {
    const ctx = document.getElementById('riskAssessmentChart');
    if (!ctx) return;
    
    destroyChart('riskAssessment');
    
    const partners = riskData.slice(0, 6);
    const labels = partners.map(item => item.nama_toko || 'Partner');
    const riskScores = partners.map(item => parseFloat(item.risk_score || 0));
    const colors = partners.map(item => getRiskColor(item.risk_level));
    
    analyticsCharts.riskAssessment = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Risk Score',
                data: riskScores,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const partnerIndex = context.dataIndex;
                            const partner = partners[partnerIndex];
                            return [
                                'Risk Score: ' + context.parsed.y,
                                'Risk Level: ' + (partner.risk_level || 'Unknown')
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                },
                x: {
                    ticks: { maxRotation: 45 }
                }
            }
        }
    });
}

/**
 * Create AI recommendations display
 */
function createAIRecommendations(recommendations) {
    const container = document.getElementById('aiRecommendations');
    if (!container) return;
    
    let html = '';
    
    // Use provided recommendations or generate defaults
    if (!recommendations || recommendations.length === 0) {
        recommendations = [
            {
                type: 'opportunity',
                title: 'Optimize Inventory Allocation',
                message: 'Increase shipment to top 3 performers by 20% based on trend analysis.',
                estimated_impact: 'Rp 15-25 juta additional revenue'
            },
            {
                type: 'risk',
                title: 'Partner Risk Alert', 
                message: '2 partners showing declining performance trends. Consider partnership review.',
                estimated_impact: 'Prevent Rp 8-12 juta potential losses'
            },
            {
                type: 'seasonal',
                title: 'Peak Season Preparation',
                message: 'High-demand season approaching. Prepare inventory increase by 25% for top-grade partners.',
                estimated_impact: 'Rp 20-30 juta opportunity'
            },
            {
                type: 'optimization',
                title: 'Route Optimization',
                message: 'Consolidate deliveries to reduce logistics costs by 15%.',
                estimated_impact: 'Rp 5-8 juta monthly savings'
            }
        ];
    }
    
    recommendations.forEach(function(rec) {
        const typeClass = getRecommendationClass(rec.type);
        const iconClass = getRecommendationIcon(rec.type);
        const impact = rec.estimated_impact || rec.estimated_revenue || rec.estimated_savings || '';
        
        html += '<div class="recommendation-item ' + typeClass + '">' +
                '<h4><i class="' + iconClass + '"></i> ' + rec.title + '</h4>' +
                '<p>' + rec.message + '</p>' +
                (impact ? '<p><strong>Impact: ' + impact + '</strong></p>' : '') +
                '</div>';
    });
    
    container.innerHTML = html;
}

// ===== UTILITY FUNCTIONS =====

/**
 * Show loading overlay
 */
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('show');
    }
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('show');
    }
}

/**
 * Show success message using SweetAlert2 or fallback to alert
 */
function showSuccessMessage(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        alert(message);
    }
}

/**
 * Show error message using SweetAlert2 or fallback to alert
 */
function showErrorMessage(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#dc3545'
        });
    } else {
        alert('Error: ' + message);
    }
}

/**
 * Update element text content safely
 */
function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

/**
 * Format currency to Indonesian Rupiah
 */
function formatCurrency(amount) {
    if (!amount || isNaN(amount)) return 'Rp 0';
    
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Format currency with short notation (K, M, B)
 */
function formatCurrencyShort(amount) {
    if (!amount || isNaN(amount)) return 'Rp 0';
    
    if (amount >= 1000000000) {
        return 'Rp ' + (amount / 1000000000).toFixed(1) + 'B';
    } else if (amount >= 1000000) {
        return 'Rp ' + (amount / 1000000).toFixed(1) + 'M';
    } else if (amount >= 1000) {
        return 'Rp ' + (amount / 1000).toFixed(1) + 'K';
    }
    
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Safely destroy existing chart
 */
function destroyChart(chartKey) {
    if (analyticsCharts[chartKey] && typeof analyticsCharts[chartKey].destroy === 'function') {
        analyticsCharts[chartKey].destroy();
        delete analyticsCharts[chartKey];
    }
}

/**
 * Create gradient for chart backgrounds
 */
function createGradient(ctx, colors) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, colors[0] + '40'); // 40 = 25% opacity
    gradient.addColorStop(1, colors[1] + '10'); // 10 = 6% opacity
    return gradient;
}

/**
 * Generate array of colors for charts
 */
function generateColors(count) {
    const baseColors = [COLORS.primary, COLORS.success, COLORS.warning, COLORS.info, COLORS.danger, COLORS.secondary];
    const colors = [];
    
    for (let i = 0; i < count; i++) {
        colors.push(baseColors[i % baseColors.length]);
    }
    
    return colors;
}

/**
 * Get color based on partner grade
 */
function getGradeColor(grade) {
    switch(grade) {
        case 'A+': return COLORS.success;
        case 'A': return COLORS.primary;
        case 'B': return COLORS.warning;
        case 'C': return COLORS.danger;
        default: return COLORS.secondary;
    }
}

/**
 * Get color based on velocity rate
 */
function getVelocityColor(velocityRate) {
    const rate = parseFloat(velocityRate) || 0;
    if (rate >= 80) return COLORS.success; // Hot seller
    if (rate >= 60) return COLORS.primary; // Good mover
    if (rate >= 30) return COLORS.warning; // Slow mover
    return COLORS.danger; // Dead stock
}

/**
 * Get color based on ROI value
 */
function getROIColor(roi) {
    const roiValue = parseFloat(roi) || 0;
    if (roiValue >= 25) return COLORS.success;
    if (roiValue >= 15) return COLORS.warning;
    return COLORS.danger;
}

/**
 * Get color based on risk level
 */
function getRiskColor(riskLevel) {
    switch(riskLevel) {
        case 'High': return COLORS.danger;
        case 'Medium': return COLORS.warning;
        case 'Low': return COLORS.success;
        default: return COLORS.primary;
    }
}

/**
 * Get CSS class for recommendation type
 */
function getRecommendationClass(type) {
    switch(type) {
        case 'risk': return 'risk';
        case 'opportunity': return 'opportunity';
        case 'warning': 
        case 'seasonal': return 'warning';
        default: return '';
    }
}

/**
 * Get icon class for recommendation type
 */
function getRecommendationIcon(type) {
    switch(type) {
        case 'risk': return 'fas fa-exclamation-triangle';
        case 'opportunity': return 'fas fa-lightbulb';
        case 'seasonal': return 'fas fa-calendar-alt';
        case 'optimization': return 'fas fa-cogs';
        case 'product': return 'fas fa-box';
        case 'inventory': return 'fas fa-warehouse';
        case 'finance': return 'fas fa-chart-line';
        default: return 'fas fa-info-circle';
    }
}

/**
 * Format number with thousands separator
 */
function formatNumber(num) {
    if (!num || isNaN(num)) return '0';
    return new Intl.NumberFormat('id-ID').format(num);
}

/**
 * Format percentage with specified decimal places
 */
function formatPercentage(num, decimals) {
    decimals = decimals || 1;
    if (!num || isNaN(num)) return '0%';
    return parseFloat(num).toFixed(decimals) + '%';
}

/**
 * Resize all charts to fit their containers
 */
function resizeAllCharts() {
    Object.keys(analyticsCharts).forEach(function(chartKey) {
        if (analyticsCharts[chartKey] && typeof analyticsCharts[chartKey].resize === 'function') {
            try {
                analyticsCharts[chartKey].resize();
            } catch (error) {
                console.warn('Error resizing chart:', chartKey, error);
            }
        }
    });
}

/**
 * Resize charts in specific section with delay
 */
function resizeChartsInSection(sectionName) {
    setTimeout(function() {
        resizeAllCharts();
    }, 200);
}

/**
 * Debounce function to limit function calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        
        const later = function() {
            clearTimeout(timeout);
            func.apply(context, args);
        };
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Get month name from date or number
 */
function getMonthName(monthInput) {
    const months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    
    if (typeof monthInput === 'number') {
        return months[monthInput - 1] || 'Unknown';
    }
    
    return monthInput || 'Unknown';
}

/**
 * Calculate growth rate between two values
 */
function calculateGrowthRate(current, previous) {
    if (!previous || previous === 0) return 0;
    return ((current - previous) / previous * 100);
}

/**
 * Get trend indicator based on value
 */
function getTrendIndicator(value) {
    const numValue = parseFloat(value) || 0;
    if (numValue > 5) return { class: 'positive', icon: 'fas fa-arrow-up', text: '+' };
    if (numValue < -5) return { class: 'negative', icon: 'fas fa-arrow-down', text: '' };
    return { class: 'neutral', icon: 'fas fa-minus', text: '' };
}

/**
 * Validate chart data before creating chart
 */
function validateChartData(data, chartType) {
    if (!data || (Array.isArray(data) && data.length === 0)) {
        console.warn('No data provided for chart type:', chartType);
        return false;
    }
    
    if (Array.isArray(data)) {
        const hasValidData = data.some(item => {
            return item !== null && item !== undefined && !isNaN(parseFloat(item));
        });
        
        if (!hasValidData) {
            console.warn('No valid numeric data found for chart type:', chartType);
            return false;
        }
    }
    
    return true;
}

/**
 * Handle chart creation errors
 */
function handleChartError(error, chartName) {
    console.error('Error creating chart:', chartName, error);
    
    // Show placeholder or error message in chart container
    const chartContainer = document.getElementById(chartName + 'Chart');
    if (chartContainer) {
        chartContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #6c757d;">' +
            '<div style="text-align: center;">' +
            '<i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>' +
            '<p>Chart data unavailable</p>' +
            '</div></div>';
    }
}

// ===== PRINT FUNCTIONALITY =====

/**
 * Handle before print event - prepare layout for printing
 */
function handleBeforePrint() {
    console.log('Preparing for print...');
    
    // Show all sections for comprehensive print
    document.querySelectorAll('.analytics-section').forEach(function(section) {
        section.classList.add('active');
    });
    
    // Wait for layout changes and resize charts
    setTimeout(function() {
        resizeAllCharts();
    }, 100);
}

/**
 * Handle after print event - restore normal layout
 */
function handleAfterPrint() {
    console.log('Restoring layout after print...');
    
    // Hide all sections first
    document.querySelectorAll('.analytics-section').forEach(function(section) {
        section.classList.remove('active');
    });
    
    // Show only the currently active section
    const activeNavBtn = document.querySelector('.nav-btn.active');
    if (activeNavBtn) {
        const activeSection = activeNavBtn.getAttribute('data-section');
        const sectionElement = document.getElementById(activeSection + '-section');
        if (sectionElement) {
            sectionElement.classList.add('active');
        }
    }
    
    // Resize charts back to normal
    setTimeout(function() {
        resizeAllCharts();
    }, 100);
}

/**
 * Export chart as image
 */
function exportChartAsImage(chartKey, filename) {
    if (analyticsCharts[chartKey]) {
        try {
            const canvas = analyticsCharts[chartKey].canvas;
            const url = canvas.toDataURL('image/png');
            
            const link = document.createElement('a');
            link.download = filename || (chartKey + '_chart.png');
            link.href = url;
            link.click();
        } catch (error) {
            console.error('Error exporting chart:', error);
            showErrorMessage('Failed to export chart image');
        }
    }
}

/**
 * Export all analytics data as JSON
 */
function exportAnalyticsData() {
    const exportData = {
        timestamp: new Date().toISOString(),
        filters: currentFilters,
        charts: {}
    };
    
    // Collect data from all charts
    Object.keys(analyticsCharts).forEach(function(chartKey) {
        if (analyticsCharts[chartKey] && analyticsCharts[chartKey].data) {
            exportData.charts[chartKey] = analyticsCharts[chartKey].data;
        }
    });
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = 'analytics_data_' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
}

// ===== ERROR HANDLING & RETRY LOGIC =====

/**
 * Retry failed requests with exponential backoff
 */
function retryRequest(requestFunction, maxRetries, delay) {
    maxRetries = maxRetries || 3;
    delay = delay || 1000;
    
    return new Promise(function(resolve, reject) {
        let retryCount = 0;
        
        function attemptRequest() {
            requestFunction()
                .then(resolve)
                .catch(function(error) {
                    retryCount++;
                    
                    if (retryCount < maxRetries) {
                        console.warn('Request failed, retrying in ' + delay + 'ms... (attempt ' + retryCount + '/' + maxRetries + ')');
                        setTimeout(attemptRequest, delay);
                        delay *= 2; // Exponential backoff
                    } else {
                        console.error('Request failed after ' + maxRetries + ' attempts:', error);
                        reject(error);
                    }
                });
        }
        
        attemptRequest();
    });
}

/**
 * Check if element is in viewport for lazy loading
 */
function isInViewport(element) {
    if (!element) return false;
    
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * Smooth scroll to element
 */
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Local storage helpers for caching
 */
const CacheHelper = {
    set: function(key, data, expireMinutes) {
        expireMinutes = expireMinutes || 30;
        const expireTime = new Date().getTime() + (expireMinutes * 60 * 1000);
        
        const cacheData = {
            data: data,
            expireTime: expireTime
        };
        
        try {
            localStorage.setItem('analytics_' + key, JSON.stringify(cacheData));
        } catch (error) {
            console.warn('Failed to cache data:', error);
        }
    },
    
    get: function(key) {
        try {
            const cacheData = localStorage.getItem('analytics_' + key);
            if (!cacheData) return null;
            
            const parsed = JSON.parse(cacheData);
            
            if (new Date().getTime() > parsed.expireTime) {
                localStorage.removeItem('analytics_' + key);
                return null;
            }
            
            return parsed.data;
        } catch (error) {
            console.warn('Failed to retrieve cached data:', error);
            return null;
        }
    },
    
    clear: function() {
        try {
            const keys = Object.keys(localStorage);
            keys.forEach(function(key) {
                if (key.startsWith('analytics_')) {
                    localStorage.removeItem(key);
                }
            });
        } catch (error) {
            console.warn('Failed to clear cache:', error);
        }
    }
};

// ===== GLOBAL EXPORTS & INITIALIZATION =====

/**
 * Export main functions for global access
 */
window.analyticsApp = {
    // Main functions
    refreshAllAnalytics: refreshAllAnalytics,
    switchToSection: switchToSection,
    showSection: showSection,
    
    // Utility functions
    formatCurrency: formatCurrency,
    formatCurrencyShort: formatCurrencyShort,
    formatNumber: formatNumber,
    formatPercentage: formatPercentage,
    
    // UI functions
    showSuccessMessage: showSuccessMessage,
    showErrorMessage: showErrorMessage,
    showLoading: showLoading,
    hideLoading: hideLoading,
    
    // Chart functions
    resizeAllCharts: resizeAllCharts,
    exportChartAsImage: exportChartAsImage,
    exportAnalyticsData: exportAnalyticsData,
    
    // Data management
    updateFilters: updateFilters,
    getCurrentFilters: function() { return currentFilters; },
    
    // Cache management
    clearCache: CacheHelper.clear,
    
    // Chart instances (for debugging)
    getCharts: function() { return analyticsCharts; }
};

// ===== PERFORMANCE MONITORING =====

/**
 * Monitor performance and log metrics
 */
function logPerformanceMetrics() {
    if (window.performance && window.performance.timing) {
        const timing = window.performance.timing;
        const loadTime = timing.loadEventEnd - timing.navigationStart;
        
        console.log('Analytics Dashboard Performance Metrics:');
        console.log('- Page Load Time:', loadTime + 'ms');
        console.log('- DOM Ready Time:', (timing.domContentLoadedEventEnd - timing.navigationStart) + 'ms');
        console.log('- Charts Loaded:', Object.keys(analyticsCharts).length);
    }
}

// Log performance metrics after page load
window.addEventListener('load', function() {
    setTimeout(logPerformanceMetrics, 1000);
});

// ===== ERROR TRACKING =====

/**
 * Global error handler for uncaught errors
 */
window.addEventListener('error', function(event) {
    console.error('Analytics Dashboard Error:', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error
    });
    
    // Optionally send error to logging service
    // sendErrorToLoggingService(event);
});

/**
 * Handle unhandled promise rejections
 */
window.addEventListener('unhandledrejection', function(event) {
    console.error('Analytics Dashboard Unhandled Promise Rejection:', event.reason);
    
    // Prevent default browser behavior
    event.preventDefault();
});

// ===== INITIALIZATION COMPLETE =====

console.log('Analytics Dashboard JavaScript loaded successfully');
console.log('Available functions:', Object.keys(window.analyticsApp));
console.log('Chart color scheme:', COLORS);

// Auto-refresh data every 5 minutes (optional)
// setInterval(refreshAllAnalytics, 5 * 60 * 1000);
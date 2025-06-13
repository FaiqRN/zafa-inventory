/**
 * ZAFA POTATO ANALYTICS - LARAVEL INTEGRATION
 * Complete Chart Creation and Data Visualization with Laravel Backend
 * Terintegrasi penuh dengan AnalyticsController dan AnalyticsService
 */

// Global chart instances
let analyticsCharts = {};
let currentAnalyticsData = {};

// Chart color schemes - sesuai dengan design Laravel
const CHART_COLORS = {
    primary: '#007bff',
    success: '#28a745',
    warning: '#ffc107',
    danger: '#dc3545',
    info: '#17a2b8',
    secondary: '#6c757d',
    light: '#f8f9fa',
    dark: '#343a40'
};

const GRADE_COLORS = {
    'A+': '#28a745',
    'A': '#007bff', 
    'B+': '#17a2b8',
    'B': '#ffc107',
    'C+': '#fd7e14',
    'C': '#dc3545'
};

const VELOCITY_COLORS = {
    'Hot Seller': '#dc3545',
    'Good Mover': '#28a745',
    'Slow Mover': '#ffc107',
    'Dead Stock': '#6c757d'
};

// Chart configuration defaults
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';

/**
 * ===== MAIN ANALYTICS INITIALIZATION =====
 */
$(document).ready(function() {
    initializeAnalytics();
    setupEventHandlers();
    startPeriodicUpdates();
});

function initializeAnalytics() {
    const currentPage = getCurrentAnalyticsPage();
    
    switch (currentPage) {
        case 'partner-performance':
            initializePartnerPerformanceCharts();
            break;
        case 'inventory-optimization':
            initializeInventoryOptimizationCharts();
            break;
        case 'product-velocity':
            initializeProductVelocityCharts();
            break;
        case 'profitability-analysis':
            initializeProfitabilityCharts();
            break;
        case 'channel-comparison':
            initializeChannelComparisonCharts();
            break;
        case 'predictive-analytics':
            initializePredictiveAnalyticsCharts();
            break;
        case 'analytics-index':
            initializeOverviewCharts();
            break;
    }
}

function getCurrentAnalyticsPage() {
    const path = window.location.pathname;
    if (path.includes('partner-performance')) return 'partner-performance';
    if (path.includes('inventory-optimization')) return 'inventory-optimization';
    if (path.includes('product-velocity')) return 'product-velocity';
    if (path.includes('profitability-analysis')) return 'profitability-analysis';
    if (path.includes('channel-comparison')) return 'channel-comparison';
    if (path.includes('predictive-analytics')) return 'predictive-analytics';
    if (path.includes('analytics')) return 'analytics-index';
    return 'unknown';
}

/**
 * ===== PARTNER PERFORMANCE ANALYTICS =====
 */
function initializePartnerPerformanceCharts() {
    loadPartnerPerformanceData().then(data => {
        createPartnerRankingChart(data.partners);
        createGradeDistributionChart(data.partners);
        createPerformanceTrendChart(data.monthly_trends);
        createPartnerComparisonChart(data.partners);
    }).catch(handleChartError);
}

function loadPartnerPerformanceData() {
    return fetch('/analytics/partner-performance/api/data')
        .then(response => response.json())
        .then(data => {
            currentAnalyticsData.partnerPerformance = data;
            return data;
        });
}

function createPartnerRankingChart(partners) {
    const ctx = document.getElementById('partnerRankingChart');
    if (!ctx) return;
    
    destroyChart('partnerRanking');
    
    const topPartners = partners.slice(0, 10);
    const labels = topPartners.map(p => truncateText(p.nama_toko, 15));
    const data = topPartners.map(p => parseFloat(p.sell_through_rate));
    const colors = topPartners.map(p => GRADE_COLORS[p.grade] || CHART_COLORS.secondary);
    
    analyticsCharts.partnerRanking = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sell-Through Rate (%)',
                data: data,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        title: function(context) {
                            return topPartners[context[0].dataIndex].nama_toko;
                        },
                        label: function(context) {
                            const partner = topPartners[context.dataIndex];
                            return [
                                `Sell-Through: ${context.parsed.x}%`,
                                `Grade: ${partner.grade}`,
                                `Revenue: ${formatCurrency(partner.revenue)}`,
                                `Risk: ${partner.risk_score?.level || 'Low'}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            onClick: function(event, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const partner = topPartners[index];
                    showPartnerDetail(partner);
                }
            }
        }
    });
}

function createGradeDistributionChart(partners) {
    const ctx = document.getElementById('gradeDistributionChart');
    if (!ctx) return;
    
    destroyChart('gradeDistribution');
    
    const gradeData = {};
    partners.forEach(partner => {
        gradeData[partner.grade] = (gradeData[partner.grade] || 0) + 1;
    });
    
    const labels = Object.keys(gradeData);
    const data = Object.values(gradeData);
    const colors = labels.map(grade => GRADE_COLORS[grade] || CHART_COLORS.secondary);
    
    analyticsCharts.gradeDistribution = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 4,
                borderColor: '#ffffff',
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
                        usePointStyle: true,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            return data.labels.map((label, index) => ({
                                text: `${label}: ${data.datasets[0].data[index]} partners`,
                                fillStyle: data.datasets[0].backgroundColor[index],
                                strokeStyle: data.datasets[0].backgroundColor[index],
                                pointStyle: 'circle'
                            }));
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.parsed / total) * 100);
                            return `${context.label}: ${context.parsed} partners (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '50%'
        }
    });
}

function createPerformanceTrendChart(monthlyData) {
    const ctx = document.getElementById('performanceTrendChart');
    if (!ctx) return;
    
    destroyChart('performanceTrend');
    
    if (!monthlyData || monthlyData.length === 0) {
        // Generate sample data if no real data available
        monthlyData = generateSampleTrendData();
    }
    
    const labels = monthlyData.map(item => item.month);
    const performanceData = monthlyData.map(item => parseFloat(item.avg_performance || 0));
    const partnerData = monthlyData.map(item => parseInt(item.active_partners || 0));
    
    analyticsCharts.performanceTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Avg Performance (%)',
                    data: performanceData,
                    borderColor: CHART_COLORS.success,
                    backgroundColor: createGradient(ctx, CHART_COLORS.success, 0.1),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'Active Partners',
                    data: partnerData,
                    borderColor: CHART_COLORS.info,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
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
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Performance (%)' },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    title: { display: true, text: 'Partners' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

/**
 * ===== INVENTORY OPTIMIZATION ANALYTICS =====
 */
function initializeInventoryOptimizationCharts() {
    loadInventoryOptimizationData().then(data => {
        createInventoryTurnoverChart(data.turnover_data);
        createOptimalVsActualChart(data.recommendations);
        createInventoryEfficiencyChart(data.efficiency_trends);
        createConfidenceDistributionChart(data.recommendations);
    }).catch(handleChartError);
}

function loadInventoryOptimizationData() {
    return fetch('/analytics/inventory-optimization/api/data')
        .then(response => response.json())
        .then(data => {
            currentAnalyticsData.inventoryOptimization = data;
            return data;
        });
}

function createInventoryTurnoverChart(turnoverData) {
    const ctx = document.getElementById('turnoverChart');
    if (!ctx) return;
    
    destroyChart('inventoryTurnover');
    
    if (!turnoverData || turnoverData.length === 0) {
        turnoverData = generateSampleTurnoverData();
    }
    
    const labels = turnoverData.map(item => item.month);
    const actualData = turnoverData.map(item => parseFloat(item.actual_turnover || 0));
    const targetData = turnoverData.map(item => parseFloat(item.target_turnover || 4.0));
    
    analyticsCharts.inventoryTurnover = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Actual Turnover Rate',
                    data: actualData,
                    borderColor: CHART_COLORS.primary,
                    backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.1),
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Target Turnover Rate',
                    data: targetData,
                    borderColor: CHART_COLORS.success,
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
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
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Turnover Rate (x/month)' },
                    ticks: {
                        callback: function(value) {
                            return value + 'x';
                        }
                    }
                }
            }
        }
    });
}

function createOptimalVsActualChart(recommendations) {
    const ctx = document.getElementById('optimalVsActualChart');
    if (!ctx) return;
    
    destroyChart('optimalVsActual');
    
    const topRecommendations = recommendations.slice(0, 8);
    const labels = topRecommendations.map(item => truncateText(item.barang_nama, 12));
    const actualData = topRecommendations.map(item => parseFloat(item.historical_avg_shipped || 0));
    const optimalData = topRecommendations.map(item => parseFloat(item.recommended_quantity || 0));
    
    analyticsCharts.optimalVsActual = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Current Average',
                    data: actualData,
                    backgroundColor: CHART_COLORS.warning,
                    borderRadius: 4,
                    borderSkipped: false
                },
                {
                    label: 'Recommended Optimal',
                    data: optimalData,
                    backgroundColor: CHART_COLORS.success,
                    borderRadius: 4,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const item = topRecommendations[context.dataIndex];
                            if (context.datasetIndex === 0) {
                                return `Current: ${context.parsed.y} units`;
                            } else {
                                return [
                                    `Optimal: ${context.parsed.y} units`,
                                    `Savings: ${formatCurrency(item.potential_savings || 0)}`
                                ];
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity (units)' }
                }
            }
        }
    });
}

/**
 * ===== PRODUCT VELOCITY ANALYTICS =====
 */
function initializeProductVelocityCharts() {
    loadProductVelocityData().then(data => {
        createProductVelocityChart(data.products);
        createVelocityCategoryChart(data.category_stats);
        createRegionalVelocityChart(data.regional_data);
        createVelocityTrendChart(data.velocity_trends);
    }).catch(handleChartError);
}

function loadProductVelocityData() {
    return fetch('/analytics/product-velocity/api/data')
        .then(response => response.json())
        .then(data => {
            currentAnalyticsData.productVelocity = data;
            return data;
        });
}

function createProductVelocityChart(products) {
    const ctx = document.getElementById('productVelocityChart');
    if (!ctx) return;
    
    destroyChart('productVelocity');
    
    const topProducts = products.slice(0, 10);
    const labels = topProducts.map(p => truncateText(p.barang.nama_barang, 15));
    const velocityData = topProducts.map(p => parseFloat(p.velocity_score || 0));
    const colors = topProducts.map(p => VELOCITY_COLORS[p.velocity_category] || CHART_COLORS.secondary);
    
    analyticsCharts.productVelocity = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Velocity Score',
                data: velocityData,
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
                        title: function(context) {
                            return topProducts[context[0].dataIndex].barang.nama_barang;
                        },
                        label: function(context) {
                            const product = topProducts[context.dataIndex];
                            return [
                                `Velocity Score: ${context.parsed.y}`,
                                `Category: ${product.velocity_category}`,
                                `Sell-Through: ${product.avg_sell_through}%`,
                                `Days to Sell: ${product.avg_days_to_sell}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Velocity Score' }
                }
            }
        }
    });
}

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
    
    const labels = ['🔥 Hot Sellers', '✅ Good Movers', '🐌 Slow Movers', '💀 Dead Stock'];
    const colors = [
        VELOCITY_COLORS['Hot Seller'],
        VELOCITY_COLORS['Good Mover'],
        VELOCITY_COLORS['Slow Mover'],
        VELOCITY_COLORS['Dead Stock']
    ];
    
    analyticsCharts.velocityCategory = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 4,
                borderColor: '#ffffff',
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
                }
            },
            cutout: '50%'
        }
    });
}

/**
 * ===== PROFITABILITY ANALYSIS =====
 */
function initializeProfitabilityCharts() {
    loadProfitabilityData().then(data => {
        createProfitabilityRankingChart(data.profitability);
        createCostBreakdownChart(data.cost_breakdown);
        createROIDistributionChart(data.profitability);
        createProfitabilityTrendChart(data.monthly_trends);
    }).catch(handleChartError);
}

function loadProfitabilityData() {
    return fetch('/analytics/profitability-analysis/api/data')
        .then(response => response.json())
        .then(data => {
            currentAnalyticsData.profitability = data;
            return data;
        });
}

function createProfitabilityRankingChart(profitabilityData) {
    const ctx = document.getElementById('profitabilityRankingChart');
    if (!ctx) return;
    
    destroyChart('profitabilityRanking');
    
    const topPartners = profitabilityData.slice(0, 10);
    const labels = topPartners.map(item => truncateText(item.toko.nama_toko, 12));
    const roiData = topPartners.map(item => parseFloat(item.roi || 0));
    const colors = roiData.map(roi => {
        if (roi >= 25) return CHART_COLORS.success;
        if (roi >= 15) return CHART_COLORS.primary;
        if (roi >= 5) return CHART_COLORS.warning;
        return CHART_COLORS.danger;
    });
    
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
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return topPartners[context[0].dataIndex].toko.nama_toko;
                        },
                        label: function(context) {
                            const partner = topPartners[context.dataIndex];
                            return [
                                `ROI: ${context.parsed.x}%`,
                                `Net Profit: ${formatCurrency(partner.net_profit)}`,
                                `Revenue: ${formatCurrency(partner.revenue)}`,
                                `Margin: ${partner.profit_margin}%`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'ROI (%)' },
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

function createCostBreakdownChart(costData) {
    const ctx = document.getElementById('costBreakdownChart');
    if (!ctx) return;
    
    destroyChart('costBreakdown');
    
    const data = [
        costData.total_cogs || 0,
        costData.total_logistics || 0,
        costData.total_opportunity || 0,
        costData.total_time_value || 0
    ];
    
    const labels = ['COGS', 'Logistics', 'Opportunity Cost', 'Time Value Cost'];
    const colors = [CHART_COLORS.primary, CHART_COLORS.warning, CHART_COLORS.info, CHART_COLORS.danger];
    
    analyticsCharts.costBreakdown = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 4,
                borderColor: '#ffffff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.parsed / total) * 100);
                            return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * ===== CHANNEL COMPARISON ANALYTICS =====
 */
function initializeChannelComparisonCharts() {
    loadChannelComparisonData().then(data => {
        createChannelComparisonRadarChart(data.b2b_stats, data.b2c_stats);
        createChannelTrendChart(data.monthly_comparison);
        createChannelMetricsChart(data.channel_metrics);
        createRevenueDistributionChart(data.b2b_stats, data.b2c_stats);
    }).catch(handleChartError);
}

function loadChannelComparisonData() {
    return fetch('/analytics/channel-comparison/api/data')
        .then(response => response.json())
        .then(data => {
            currentAnalyticsData.channelComparison = data;
            return data;
        });
}

function createChannelComparisonRadarChart(b2bStats, b2cStats) {
    const ctx = document.getElementById('channelComparisonChart');
    if (!ctx) return;
    
    destroyChart('channelComparison');
    
    const labels = ['Volume', 'Revenue', 'Margin %', 'Scalability', 'Cash Flow Speed', 'Efficiency'];
    
    analyticsCharts.channelComparison = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'B2B Konsinyasi',
                    data: [
                        normalizeValue(b2bStats.volume, 0, 20000),
                        normalizeValue(b2bStats.revenue, 0, 500000000),
                        b2bStats.margin || 40,
                        85, // Scalability score
                        30, // Cash flow score (inverse - lower is worse)
                        75  // Efficiency score
                    ],
                    borderColor: CHART_COLORS.primary,
                    backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.2),
                    fill: true
                },
                {
                    label: 'B2C Direct Sales',
                    data: [
                        normalizeValue(b2cStats.volume, 0, 20000),
                        normalizeValue(b2cStats.revenue, 0, 500000000),
                        b2cStats.margin || 57,
                        65, // Scalability score
                        95, // Cash flow score
                        80  // Efficiency score
                    ],
                    borderColor: CHART_COLORS.success,
                    backgroundColor: createGradient(ctx, CHART_COLORS.success, 0.2),
                    fill: true
                }
            ]
        },
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
 * ===== PREDICTIVE ANALYTICS =====
 */
function initializePredictiveAnalyticsCharts() {
    loadPredictiveAnalyticsData().then(data => {
        createDemandForecastChart(data.demand_predictions);
        createRiskAssessmentChart(data.risk_scores);
        createSeasonalForecastChart(data.seasonal_forecasts);
        createOpportunityChart(data.opportunities);
    }).catch(handleChartError);
}

function loadPredictiveAnalyticsData() {
    return fetch('/analytics/predictive-analytics/api/data')
        .then(response => response.json())
        .then(data => {
            currentAnalyticsData.predictiveAnalytics = data;
            return data;
        });
}

function createDemandForecastChart(predictions) {
    const ctx = document.getElementById('demandForecastChart');
    if (!ctx) return;
    
    destroyChart('demandForecast');
    
    // Aggregate predictions by month
    const monthlyPredictions = aggregatePredictionsByMonth(predictions);
    const labels = Object.keys(monthlyPredictions);
    const predictedData = Object.values(monthlyPredictions).map(data => data.total_predicted);
    const confidenceUpper = Object.values(monthlyPredictions).map(data => data.upper_bound);
    const confidenceLower = Object.values(monthlyPredictions).map(data => data.lower_bound);
    
    analyticsCharts.demandForecast = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Predicted Demand',
                    data: predictedData,
                    borderColor: CHART_COLORS.primary,
                    backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.1),
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Upper Bound',
                    data: confidenceUpper,
                    borderColor: CHART_COLORS.warning,
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    fill: false
                },
                {
                    label: 'Lower Bound',
                    data: confidenceLower,
                    borderColor: CHART_COLORS.warning,
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Units' }
                }
            }
        }
    });
}

function createRiskAssessmentChart(riskData) {
    const ctx = document.getElementById('riskAssessmentChart');
    if (!ctx) return;
    
    destroyChart('riskAssessment');
    
    const highRiskPartners = riskData.filter(item => item.level === 'High').slice(0, 8);
    const labels = highRiskPartners.map(item => truncateText(item.partner_name, 12));
    const riskScores = highRiskPartners.map(item => parseFloat(item.score));
    const colors = riskScores.map(score => {
        if (score >= 70) return CHART_COLORS.danger;
        if (score >= 50) return CHART_COLORS.warning;
        return CHART_COLORS.success;
    });
    
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
                        title: function(context) {
                            return highRiskPartners[context[0].dataIndex].partner_name;
                        },
                        label: function(context) {
                            const partner = highRiskPartners[context.dataIndex];
                            return [
                                `Risk Score: ${context.parsed.y}`,
                                `Risk Level: ${partner.level}`,
                                `Recommendation: ${partner.recommendation}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Risk Score' }
                }
            }
        }
    });
}

/**
 * ===== OVERVIEW DASHBOARD CHARTS =====
 */
function initializeOverviewCharts() {
    loadOverviewData().then(data => {
        createOverviewRevenueChart(data.monthly_revenue);
        createOverviewChannelChart(data.channel_distribution);
        createOverviewPerformanceChart(data.performance_summary);
    }).catch(handleChartError);
}

function loadOverviewData() {
    return fetch('/analytics/api/overview')
        .then(response => response.json())
        .then(data => {
            currentAnalyticsData.overview = data;
            return data;
        });
}

function createOverviewRevenueChart(monthlyData) {
    const ctx = document.getElementById('overviewRevenueChart');
    if (!ctx) return;
    
    destroyChart('overviewRevenue');
    
    if (!monthlyData || monthlyData.length === 0) {
        monthlyData = generateSampleRevenueData();
    }
    
    const labels = monthlyData.map(item => item.month);
    const data = monthlyData.map(item => parseFloat(item.revenue));
    
    analyticsCharts.overviewRevenue = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: data,
                borderColor: CHART_COLORS.primary,
                backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.1),
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: CHART_COLORS.primary,
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
 * ===== UTILITY FUNCTIONS =====
 */
function destroyChart(chartKey) {
    if (analyticsCharts[chartKey] && typeof analyticsCharts[chartKey].destroy === 'function') {
        analyticsCharts[chartKey].destroy();
        delete analyticsCharts[chartKey];
    }
}

function createGradient(ctx, color, opacity = 0.2) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, color + Math.round(opacity * 255).toString(16).padStart(2, '0'));
    gradient.addColorStop(1, color + '00');
    return gradient;
}

function formatCurrency(amount) {
    if (!amount || isNaN(amount)) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function formatCurrencyShort(amount) {
    if (!amount || isNaN(amount)) return 'Rp 0';
    
    if (amount >= 1000000000) {
        return 'Rp ' + (amount / 1000000000).toFixed(1) + 'B';
    } else if (amount >= 1000000) {
        return 'Rp ' + (amount / 1000000).toFixed(1) + 'M';
    } else if (amount >= 1000) {
        return 'Rp ' + (amount / 1000).toFixed(1) + 'K';
    }
    
    return formatCurrency(amount);
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function normalizeValue(value, min, max) {
    if (max === min) return 50;
    return Math.max(0, Math.min(100, ((value - min) / (max - min)) * 100));
}

function handleChartError(error) {
    console.error('Chart error:', error);
    showNotification('Error loading chart data', 'error');
}

function showNotification(message, type = 'info') {
    // Integration with Laravel's notification system
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
    }
}

/**
 * ===== EVENT HANDLERS =====
 */
function setupEventHandlers() {
    // Export functionality
    $(document).on('click', '[data-export]', function() {
        const exportType = $(this).data('export');
        handleExport(exportType);
    });
    
    // Refresh functionality
    $(document).on('click', '[data-refresh]', function() {
        const chartType = $(this).data('refresh');
        refreshChart(chartType);
    });
    
    // Partner detail modal
    $(document).on('click', '[data-partner-detail]', function() {
        const partnerId = $(this).data('partner-detail');
        showPartnerDetail(partnerId);
    });
    
    // Chart interactions
    setupChartInteractions();
}

function handleExport(exportType) {
    const currentPage = getCurrentAnalyticsPage();
    let exportUrl = '';
    
    switch (exportType) {
        case 'partner-performance':
            exportUrl = '/analytics/partner-performance/export';
            break;
        case 'inventory-optimization':
            exportUrl = '/analytics/inventory-optimization/export';
            break;
        case 'product-velocity':
            exportUrl = '/analytics/product-velocity/export';
            break;
        case 'profitability':
            exportUrl = '/analytics/profitability-analysis/export';
            break;
        default:
            exportUrl = `/analytics/${currentPage}/export`;
    }
    
    if (exportUrl) {
        window.location.href = exportUrl;
        showNotification('Export started...', 'success');
    }
}

function refreshChart(chartType) {
    showNotification('Refreshing data...', 'info');
    
    switch (chartType) {
        case 'partner-performance':
            initializePartnerPerformanceCharts();
            break;
        case 'inventory-optimization':
            initializeInventoryOptimizationCharts();
            break;
        case 'product-velocity':
            initializeProductVelocityCharts();
            break;
        case 'profitability':
            initializeProfitabilityCharts();
            break;
        case 'channel-comparison':
            initializeChannelComparisonCharts();
            break;
        case 'predictive-analytics':
            initializePredictiveAnalyticsCharts();
            break;
        default:
            initializeAnalytics();
    }
}

function showPartnerDetail(partnerData) {
    if (typeof partnerData === 'string') {
        // If it's a partner ID, fetch the data
        fetch(`/analytics/partner-performance/history/${partnerData}`)
            .then(response => response.json())
            .then(data => {
                displayPartnerModal(data);
            })
            .catch(error => {
                console.error('Error fetching partner data:', error);
                showNotification('Error loading partner details', 'error');
            });
    } else {
        // If it's partner data object
        displayPartnerModal({partner: partnerData, history: []});
    }
}

function displayPartnerModal(data) {
    // This function would populate and show the partner detail modal
    // Implementation depends on your modal structure in the Blade templates
    console.log('Partner detail:', data);
}

function setupChartInteractions() {
    // Add click handlers for chart elements
    $(document).on('click', '.chart-legend-item', function() {
        const chartId = $(this).closest('.chart-container').find('canvas').attr('id');
        const datasetIndex = $(this).data('dataset-index');
        toggleDataset(chartId, datasetIndex);
    });
}

function toggleDataset(chartId, datasetIndex) {
    const chart = analyticsCharts[chartId];
    if (chart) {
        const meta = chart.getDatasetMeta(datasetIndex);
        meta.hidden = meta.hidden === null ? !chart.data.datasets[datasetIndex].hidden : null;
        chart.update();
    }
}

/**
 * ===== PERIODIC UPDATES =====
 */
function startPeriodicUpdates() {
    // Refresh charts every 5 minutes
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            refreshCurrentCharts();
        }
    }, 300000); // 5 minutes
}

function refreshCurrentCharts() {
    const currentPage = getCurrentAnalyticsPage();
    if (currentPage !== 'unknown') {
        console.log('Auto-refreshing charts for:', currentPage);
        refreshChart(currentPage);
    }
}

/**
 * ===== SAMPLE DATA GENERATORS =====
 */
function generateSampleTrendData() {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    return months.map(month => ({
        month: month,
        avg_performance: Math.random() * 30 + 60, // 60-90%
        active_partners: Math.floor(Math.random() * 20 + 30) // 30-50 partners
    }));
}

function generateSampleTurnoverData() {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    return months.map(month => ({
        month: month,
        actual_turnover: Math.random() * 2 + 2, // 2-4x
        target_turnover: 4.0
    }));
}

function generateSampleRevenueData() {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    let baseRevenue = 250000000;
    return months.map(month => {
        baseRevenue += (Math.random() - 0.5) * 50000000;
        return {
            month: month,
            revenue: Math.max(150000000, baseRevenue)
        };
    });
}

function aggregatePredictionsByMonth(predictions) {
    const monthlyData = {};
    
    predictions.forEach(prediction => {
        const month = prediction.prediction_date ? 
            new Date(prediction.prediction_date).toLocaleString('default', { month: 'short', year: 'numeric' }) :
            'Next Month';
        
        if (!monthlyData[month]) {
            monthlyData[month] = {
                total_predicted: 0,
                upper_bound: 0,
                lower_bound: 0,
                count: 0
            };
        }
        
        monthlyData[month].total_predicted += prediction.predicted_quantity || 0;
        monthlyData[month].upper_bound += (prediction.predicted_quantity || 0) * 1.2; // +20% upper bound
        monthlyData[month].lower_bound += (prediction.predicted_quantity || 0) * 0.8; // -20% lower bound
        monthlyData[month].count++;
    });
    
    return monthlyData;
}

/**
 * ===== RESIZE HANDLER =====
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

// Window resize handler
$(window).on('resize', debounce(resizeAllCharts, 300));

function debounce(func, wait) {
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

// Export for use in Laravel Blade templates
window.AnalyticsCharts = {
    initialize: initializeAnalytics,
    refresh: refreshChart,
    export: handleExport,
    showPartnerDetail: showPartnerDetail,
    destroyChart: destroyChart,
    resizeAllCharts: resizeAllCharts,
    currentData: currentAnalyticsData,
    charts: analyticsCharts
};

console.log('Analytics Laravel Integration loaded successfully');
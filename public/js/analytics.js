/**
 * ZAFA POTATO ANALYTICS CHARTS
 * Complete Chart Creation and Data Visualization
 * Implementasi lengkap untuk semua 6 analitik
 */

// Global chart instances
let analyticsCharts = {};

// Chart color schemes
const CHART_COLORS = {
    primary: '#309898',
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
    'A': '#309898', 
    'B': '#ffc107',
    'C': '#dc3545',
    'D': '#6c757d'
};

const VELOCITY_COLORS = {
    'Hot Seller': '#28a745',
    'Good Mover': '#309898',
    'Slow Mover': '#ffc107',
    'Dead Stock': '#dc3545'
};

// Chart configuration defaults
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';

/**
 * ===== ANALITIK 1: OVERVIEW CHARTS =====
 */

function createOverviewCharts(data) {
    createOverviewRevenueChart(data.monthly_revenue || []);
    createOverviewChannelChart(data.channel_distribution || {});
    createOverviewRegionalChart(data.regional_data || []);
}

function createOverviewRevenueChart(monthlyData) {
    const ctx = document.getElementById('overviewRevenueChart');
    if (!ctx) return;
    
    destroyChart('overviewRevenue');
    
    const labels = monthlyData.map(item => item.month);
    const data = monthlyData.map(item => parseFloat(item.total_revenue || 0));
    
    analyticsCharts.overviewRevenue = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (Rp)',
                data: data,
                borderColor: CHART_COLORS.primary,
                backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.1),
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: CHART_COLORS.primary,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: CHART_COLORS.primary,
                    borderWidth: 1,
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
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return formatCurrencyShort(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

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
                backgroundColor: [CHART_COLORS.primary, CHART_COLORS.success],
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
                    position: 'bottom',
                    labels: { 
                        padding: 20, 
                        usePointStyle: true,
                        font: { size: 14 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
}

function createOverviewRegionalChart(regionalData) {
    const ctx = document.getElementById('overviewRegionalChart');
    if (!ctx) return;
    
    destroyChart('overviewRegional');
    
    const labels = regionalData.map(item => item.wilayah || 'Unknown');
    const salesData = regionalData.map(item => parseFloat(item.sales_rate || 0));
    const revenueData = regionalData.map(item => parseFloat(item.total_revenue || 0));
    
    analyticsCharts.overviewRegional = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Sales Rate (%)',
                    data: salesData,
                    backgroundColor: CHART_COLORS.primary,
                    borderRadius: 8,
                    borderSkipped: false,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenue (Millions)',
                    data: revenueData.map(val => val / 1000000),
                    backgroundColor: CHART_COLORS.success,
                    borderRadius: 8,
                    borderSkipped: false,
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
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Sales Rate: ' + context.parsed.y + '%';
                            } else {
                                return 'Revenue: ' + formatCurrency(context.parsed.y * 1000000);
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Sales Rate (%)' },
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
                    title: { display: true, text: 'Revenue (Millions)' },
                    grid: { drawOnChartArea: false },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value + 'M';
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
 * ===== ANALITIK 2: PARTNER PERFORMANCE CHARTS =====
 */

function createPartnerCharts(data) {
    createPartnerRankingChart(data.partners || []);
    createGradeDistributionChart(data.grade_distribution || {});
    createPerformanceTrendChart(data.performance_trends || []);
}

function createPartnerRankingChart(partners) {
    const ctx = document.getElementById('partnerRankingChart');
    if (!ctx) return;
    
    destroyChart('partnerRanking');
    
    const topPartners = partners.slice(0, 10);
    const labels = topPartners.map(p => p.nama_toko || 'Partner');
    const data = topPartners.map(p => parseFloat(p.sales_rate || 0));
    const colors = topPartners.map(p => GRADE_COLORS[p.grade] || CHART_COLORS.secondary);
    
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
            indexAxis: 'y', // Horizontal bar chart
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const partnerIndex = context.dataIndex;
                            const partner = topPartners[partnerIndex];
                            return [
                                'Sales Rate: ' + context.parsed.x + '%',
                                'Grade: ' + (partner.grade || 'N/A'),
                                'Revenue: ' + formatCurrency(partner.total_revenue || 0)
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
                },
                y: {
                    ticks: { 
                        maxRotation: 0,
                        callback: function(value, index) {
                            const label = this.getLabelForValue(value);
                            return label.length > 15 ? label.substring(0, 15) + '...' : label;
                        }
                    }
                }
            }
        }
    });
}

function createGradeDistributionChart(gradeDistribution) {
    const ctx = document.getElementById('gradeDistributionChart');
    if (!ctx) return;
    
    destroyChart('gradeDistribution');
    
    const labels = Object.keys(gradeDistribution).length > 0 ? 
        Object.keys(gradeDistribution) : ['A+', 'A', 'B', 'C'];
    const data = Object.keys(gradeDistribution).length > 0 ? 
        Object.values(gradeDistribution) : [5, 12, 18, 3];
    
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
                            return context.label + ': ' + context.parsed + ' partners (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '50%'
        }
    });
}

function createPerformanceTrendChart(trendData) {
    const ctx = document.getElementById('performanceTrendChart');
    if (!ctx) return;
    
    destroyChart('performanceTrend');
    
    const labels = trendData.map(item => item.month || item.period);
    const performanceData = trendData.map(item => parseFloat(item.avg_performance || 0));
    const partnerData = trendData.map(item => parseInt(item.active_partners || 0));
    
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
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Performance: ' + context.parsed.y + '%';
                            } else {
                                return 'Active Partners: ' + context.parsed.y;
                            }
                        }
                    }
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
 * ===== ANALITIK 3: INVENTORY ANALYTICS CHARTS =====
 */

function createInventoryCharts(data) {
    createInventoryTurnoverChart(data.inventory_data || []);
    createOptimalVsActualChart(data.optimal_recommendations || []);
    createInventoryEfficiencyChart(data.monthly_efficiency || []);
}

function createInventoryTurnoverChart(inventoryData) {
    const ctx = document.getElementById('inventoryTurnoverChart');
    if (!ctx) return;
    
    destroyChart('inventoryTurnover');
    
    const topItems = inventoryData.slice(0, 10);
    const labels = topItems.map(item => `${item.nama_toko} - ${item.nama_barang}`);
    const turnoverData = topItems.map(item => parseFloat(item.turnover_rate || 0));
    const efficiencyData = topItems.map(item => parseFloat(item.efficiency || 0));
    
    analyticsCharts.inventoryTurnover = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Inventory Performance',
                data: turnoverData.map((turnover, index) => ({
                    x: turnover,
                    y: efficiencyData[index],
                    label: labels[index]
                })),
                backgroundColor: function(context) {
                    const efficiency = context.parsed.y;
                    if (efficiency >= 80) return CHART_COLORS.success;
                    if (efficiency >= 60) return CHART_COLORS.warning;
                    return CHART_COLORS.danger;
                },
                pointRadius: 8,
                pointHoverRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        title: function(context) {
                            return context[0].raw.label;
                        },
                        label: function(context) {
                            return [
                                'Turnover Rate: ' + context.parsed.x + 'x/month',
                                'Efficiency: ' + context.parsed.y + '%'
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Turnover Rate (x/month)' },
                    beginAtZero: true
                },
                y: {
                    title: { display: true, text: 'Efficiency (%)' },
                    beginAtZero: true,
                    max: 100
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
    const labels = topRecommendations.map(item => item.nama_barang || 'Product');
    const actualData = topRecommendations.map(item => parseFloat(item.current_avg_sent || 0));
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
                    position: 'top',
                    align: 'end'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const item = topRecommendations[context.dataIndex];
                            if (context.datasetIndex === 0) {
                                return 'Current: ' + context.parsed.y + ' units';
                            } else {
                                return [
                                    'Optimal: ' + context.parsed.y + ' units',
                                    'Savings: ' + formatCurrency(item.potential_monthly_savings || 0)
                                ];
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { maxRotation: 45 }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity (units)' }
                }
            }
        }
    });
}

function createInventoryEfficiencyChart(monthlyData) {
    const ctx = document.getElementById('inventoryEfficiencyTrendChart');
    if (!ctx) return;
    
    destroyChart('inventoryEfficiency');
    
    const labels = monthlyData.map(item => item.month || item.period);
    const efficiencyData = monthlyData.map(item => parseFloat(item.efficiency || 0));
    const rotationData = monthlyData.map(item => parseFloat(item.avg_rotation_days || 0));
    
    analyticsCharts.inventoryEfficiency = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Efficiency (%)',
                    data: efficiencyData,
                    borderColor: CHART_COLORS.success,
                    backgroundColor: createGradient(ctx, CHART_COLORS.success, 0.1),
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Avg Rotation (days)',
                    data: rotationData,
                    borderColor: CHART_COLORS.danger,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.4,
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
 * ===== ANALITIK 4: PRODUCT VELOCITY CHARTS =====
 */

function createVelocityCharts(data) {
    createProductVelocityChart(data.products || []);
    createVelocityCategoryChart(data.category_stats || {});
    createRegionalPreferenceChart(data.regional_preferences || []);
}

function createProductVelocityChart(products) {
    const ctx = document.getElementById('productVelocityChart');
    if (!ctx) return;
    
    destroyChart('productVelocity');
    
    const topProducts = products.slice(0, 10);
    const labels = topProducts.map(p => p.nama_barang || 'Product');
    const velocityData = topProducts.map(p => parseFloat(p.velocity_rate || 0));
    const colors = topProducts.map(p => VELOCITY_COLORS[p.category] || CHART_COLORS.secondary);
    
    analyticsCharts.productVelocity = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Velocity Rate (%)',
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const productIndex = context.dataIndex;
                            const product = topProducts[productIndex];
                            return [
                                'Velocity: ' + context.parsed.y + '%',
                                'Category: ' + (product.category || 'N/A'),
                                'Revenue: ' + formatCurrency(product.total_revenue || 0)
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Velocity Rate (%)' },
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
    
    const labels = ['Hot Sellers', 'Good Movers', 'Slow Movers', 'Dead Stock'];
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
                        usePointStyle: true,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            return data.labels.map((label, index) => ({
                                text: `${label}: ${data.datasets[0].data[index]}`,
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
                            const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return context.label + ': ' + context.parsed + ' products (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '50%'
        }
    });
}

function createRegionalPreferenceChart(regionalData) {
    const ctx = document.getElementById('regionalPreferenceChart');
    if (!ctx) return;
    
    destroyChart('regionalPreference');
    
    if (!regionalData || regionalData.length === 0) {
        regionalData = [
            { region: 'Malang Kota', regional_avg_velocity: 85 },
            { region: 'Malang Kabupaten', regional_avg_velocity: 72 },
            { region: 'Kota Batu', regional_avg_velocity: 68 }
        ];
    }
    
    const labels = regionalData.map(region => region.region);
    const data = regionalData.map(region => parseFloat(region.regional_avg_velocity || 0));
    
    analyticsCharts.regionalPreference = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average Velocity Rate',
                data: data,
                borderColor: CHART_COLORS.primary,
                backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.2),
                fill: true,
                pointBackgroundColor: CHART_COLORS.primary,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
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
 * ===== ANALITIK 5: PROFITABILITY CHARTS =====
 */

function createProfitabilityCharts(data) {
    createProfitabilityRankingChart(data.profitability_data || []);
    createCostBreakdownChart(data.cost_breakdown || {});
    createProfitabilityTrendChart(data.monthly_trend || []);
}

function createProfitabilityRankingChart(profitabilityData) {
    const ctx = document.getElementById('profitabilityRankingChart');
    if (!ctx) return;
    
    destroyChart('profitabilityRanking');
    
    const topPartners = profitabilityData.slice(0, 10);
    const labels = topPartners.map(item => item.nama_toko || 'Partner');
    const roiData = topPartners.map(item => parseFloat(item.roi || 0));
    const colors = roiData.map(roi => {
        if (roi >= 30) return CHART_COLORS.success;
        if (roi >= 20) return CHART_COLORS.primary;
        if (roi >= 10) return CHART_COLORS.warning;
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const partnerIndex = context.dataIndex;
                            const partner = topPartners[partnerIndex];
                            return [
                                'ROI: ' + context.parsed.x + '%',
                                'Net Profit: ' + formatCurrency(partner.net_profit || 0),
                                'Grade: ' + (partner.profitability_grade || 'N/A')
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
                },
                y: {
                    ticks: { 
                        maxRotation: 0,
                        callback: function(value, index) {
                            const label = this.getLabelForValue(value);
                            return label.length > 12 ? label.substring(0, 12) + '...' : label;
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
        costData.cogs || 60000000,
        costData.logistics || 15000000,
        costData.opportunity || 8000000,
        costData.admin || 12000000,
        costData.holding || 5000000
    ];
    
    const labels = ['COGS', 'Logistics', 'Opportunity Cost', 'Admin', 'Holding'];
    const colors = [CHART_COLORS.primary, CHART_COLORS.warning, CHART_COLORS.danger, CHART_COLORS.info, CHART_COLORS.secondary];
    
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
                    position: 'bottom',
                    labels: { 
                        padding: 15, 
                        usePointStyle: true 
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
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

function createProfitabilityTrendChart(monthlyTrend) {
    const ctx = document.getElementById('profitabilityTrendChart');
    if (!ctx) return;
    
    destroyChart('profitabilityTrend');
    
    const labels = monthlyTrend.map(item => item.month || item.period);
    const profitData = monthlyTrend.map(item => parseFloat(item.profit_margin || 0));
    const revenueData = monthlyTrend.map(item => parseFloat((item.revenue || 0) / 1000000)); // Convert to millions
    
    analyticsCharts.profitabilityTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Profit Margin (%)',
                    data: profitData,
                    borderColor: CHART_COLORS.success,
                    backgroundColor: createGradient(ctx, CHART_COLORS.success, 0.1),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenue (Millions)',
                    data: revenueData,
                    borderColor: CHART_COLORS.primary,
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
                    title: { display: true, text: 'Profit Margin (%)' },
                    beginAtZero: true,
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
                    title: { display: true, text: 'Revenue (Millions)' },
                    grid: { drawOnChartArea: false },
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * ===== ANALITIK 6: CHANNEL COMPARISON CHARTS =====
 */

function createChannelCharts(data) {
    createChannelComparisonChart(data.channel_metrics || {});
    createChannelTrendChart(data.monthly_comparison || []);
    createChannelMetricsChart(data.channel_metrics || {});
}

function createChannelComparisonChart(channelMetrics) {
    const ctx = document.getElementById('channelComparisonChart');
    if (!ctx) return;
    
    destroyChart('channelComparison');
    
    const b2b = channelMetrics.b2b || {};
    const b2c = channelMetrics.b2c || {};
    
    const labels = ['Revenue Share', 'Margin %', 'Scalability', 'Cash Flow Speed'];
    
    analyticsCharts.channelComparison = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'B2B Konsinyasi',
                    data: [
                        b2b.revenue_percentage || 75,
                        b2b.margin_percentage || 32,
                        b2b.scalability_score || 85,
                        b2b.cash_flow_score || 40
                    ],
                    borderColor: CHART_COLORS.primary,
                    backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.2),
                    fill: true,
                    pointBackgroundColor: CHART_COLORS.primary,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                },
                {
                    label: 'B2C Direct Sales',
                    data: [
                        b2c.revenue_percentage || 25,
                        b2c.margin_percentage || 58,
                        b2c.scalability_score || 65,
                        b2c.cash_flow_score || 95
                    ],
                    borderColor: CHART_COLORS.success,
                    backgroundColor: createGradient(ctx, CHART_COLORS.success, 0.2),
                    fill: true,
                    pointBackgroundColor: CHART_COLORS.success,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.r + '%';
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

function createChannelTrendChart(monthlyComparison) {
    const ctx = document.getElementById('channelTrendChart');
    if (!ctx) return;
    
    destroyChart('channelTrend');
    
    if (!monthlyComparison || monthlyComparison.length === 0) {
        // Sample data
        monthlyComparison = [
            { month: 'Jan', b2b_revenue: 18500000, b2c_revenue: 6200000 },
            { month: 'Feb', b2b_revenue: 22300000, b2c_revenue: 7400000 },
            { month: 'Mar', b2b_revenue: 26100000, b2c_revenue: 8700000 },
            { month: 'Apr', b2b_revenue: 19800000, b2c_revenue: 6600000 },
            { month: 'May', b2b_revenue: 24500000, b2c_revenue: 8200000 },
            { month: 'Jun', b2b_revenue: 28200000, b2c_revenue: 9400000 }
        ];
    }
    
    const labels = monthlyComparison.map(item => item.month);
    const b2bData = monthlyComparison.map(item => parseFloat(item.b2b_revenue || 0) / 1000000);
    const b2cData = monthlyComparison.map(item => parseFloat(item.b2c_revenue || 0) / 1000000);
    
    analyticsCharts.channelTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'B2B Revenue (Millions)',
                    data: b2bData,
                    borderColor: CHART_COLORS.primary,
                    backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.1),
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4
                },
                {
                    label: 'B2C Revenue (Millions)',
                    data: b2cData,
                    borderColor: CHART_COLORS.success,
                    backgroundColor: createGradient(ctx, CHART_COLORS.success, 0.1),
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatCurrency(context.parsed.y * 1000000);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Revenue (Millions)' },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value + 'M';
                        }
                    }
                }
            }
        }
    });
}

function createChannelMetricsChart(channelMetrics) {
    const ctx = document.getElementById('channelMetricsChart');
    if (!ctx) return;
    
    destroyChart('channelMetrics');
    
    const b2b = channelMetrics.b2b || {};
    const b2c = channelMetrics.b2c || {};
    
    const metrics = ['Partners/Customers', 'Avg Order (Millions)', 'Frequency/Month', 'Satisfaction %'];
    const b2bMetrics = [
        b2b.partners || 50,
        (b2b.avg_order_value || 5000000) / 1000000,
        b2b.frequency || 8,
        b2b.satisfaction || 85
    ];
    const b2cMetrics = [
        (b2c.customers || 200) / 4, // Scale down for comparison
        (b2c.avg_order_value || 500000) / 1000000,
        b2c.frequency || 3,
        b2c.satisfaction || 92
    ];
    
    analyticsCharts.channelMetrics = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: metrics,
            datasets: [
                {
                    label: 'B2B',
                    data: b2bMetrics,
                    backgroundColor: CHART_COLORS.primary,
                    borderRadius: 4,
                    borderSkipped: false
                },
                {
                    label: 'B2C',
                    data: b2cMetrics,
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
                    position: 'top',
                    align: 'end'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const metricIndex = context.dataIndex;
                            let value = context.parsed.y;
                            
                            if (metricIndex === 1) { // Avg Order Value
                                value = formatCurrency(value * 1000000);
                            } else if (metricIndex === 3) { // Satisfaction
                                value = value + '%';
                            } else if (metricIndex === 0 && context.datasetIndex === 1) {
                                value = value * 4; // Restore B2C customers scale
                            }
                            
                            return context.dataset.label + ': ' + value;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Metric Value' }
                },
                x: {
                    ticks: { maxRotation: 45 }
                }
            }
        }
    });
}

/**
 * ===== ANALITIK 7: PREDICTIVE ANALYTICS CHARTS =====
 */

function createPredictiveCharts(data) {
    createDemandForecastChart(data.demand_forecast || []);
    createRiskAssessmentChart(data.risk_assessment || []);
}

function createDemandForecastChart(forecast) {
    const ctx = document.getElementById('demandForecastChart');
    if (!ctx) return;
    
    destroyChart('demandForecast');
    
    if (!forecast || forecast.length === 0) {
        // Sample forecast data
        forecast = [
            { month: 'Jul 2024', predicted_demand: 4200, confidence: 85, upper_bound: 5040, lower_bound: 3360 },
            { month: 'Aug 2024', predicted_demand: 4450, confidence: 82, upper_bound: 5340, lower_bound: 3560 },
            { month: 'Sep 2024', predicted_demand: 4680, confidence: 79, upper_bound: 5616, lower_bound: 3744 },
            { month: 'Oct 2024', predicted_demand: 4920, confidence: 76, upper_bound: 5904, lower_bound: 3936 },
            { month: 'Nov 2024', predicted_demand: 5180, confidence: 73, upper_bound: 6216, lower_bound: 4144 },
            { month: 'Dec 2024', predicted_demand: 5450, confidence: 70, upper_bound: 6540, lower_bound: 4360 }
        ];
    }
    
    const labels = forecast.map(item => item.month);
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
                    borderColor: CHART_COLORS.primary,
                    backgroundColor: createGradient(ctx, CHART_COLORS.primary, 0.1),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    yAxisID: 'y'
                },
                {
                    label: 'Upper Bound',
                    data: upperBound,
                    borderColor: CHART_COLORS.warning,
                    backgroundColor: 'transparent',
                    fill: false,
                    borderDash: [5, 5],
                    pointRadius: 3,
                    yAxisID: 'y'
                },
                {
                    label: 'Lower Bound',
                    data: lowerBound,
                    borderColor: CHART_COLORS.warning,
                    backgroundColor: 'transparent',
                    fill: false,
                    borderDash: [5, 5],
                    pointRadius: 3,
                    yAxisID: 'y'
                },
                {
                    label: 'Confidence (%)',
                    data: confidenceData,
                    borderColor: CHART_COLORS.success,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.2,
                    borderDash: [10, 5],
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
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 3) { // Confidence
                                return 'Confidence: ' + context.parsed.y + '%';
                            } else {
                                return context.dataset.label + ': ' + context.parsed.y + ' units';
                            }
                        }
                    }
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

function createRiskAssessmentChart(riskData) {
    const ctx = document.getElementById('riskAssessmentChart');
    if (!ctx) return;
    
    destroyChart('riskAssessment');
    
    if (!riskData || riskData.length === 0) {
        // Sample risk data
        riskData = [
            { nama_toko: 'Toko ABC', risk_score: 85, risk_level: 'High' },
            { nama_toko: 'Warung XYZ', risk_score: 72, risk_level: 'High' },
            { nama_toko: 'Toko Sejahtera', risk_score: 45, risk_level: 'Medium' },
            { nama_toko: 'Mini Market', risk_score: 38, risk_level: 'Medium' },
            { nama_toko: 'Toko Maju', risk_score: 25, risk_level: 'Low' },
            { nama_toko: 'Warung Berkah', risk_score: 15, risk_level: 'Low' }
        ];
    }
    
    const partners = riskData.slice(0, 8);
    const labels = partners.map(item => item.nama_toko || 'Partner');
    const riskScores = partners.map(item => parseFloat(item.risk_score || 0));
    const colors = partners.map(item => {
        const level = item.risk_level;
        if (level === 'High') return CHART_COLORS.danger;
        if (level === 'Medium') return CHART_COLORS.warning;
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const partnerIndex = context.dataIndex;
                            const partner = partners[partnerIndex];
                            return [
                                'Risk Score: ' + context.parsed.y,
                                'Risk Level: ' + (partner.risk_level || 'Unknown'),
                                'Status: ' + (partner.risk_level === 'High' ? 'Needs Attention' : 
                                            partner.risk_level === 'Medium' ? 'Monitor' : 'Stable')
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
                },
                x: {
                    ticks: { maxRotation: 45 }
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
    
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

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

// Export functions for use in main analytics.js
window.AnalyticsCharts = {
    createOverviewCharts,
    createPartnerCharts,
    createInventoryCharts,
    createVelocityCharts,
    createProfitabilityCharts,
    createChannelCharts,
    createPredictiveCharts,
    destroyChart,
    resizeAllCharts,
    analyticsCharts
};

console.log('Analytics Charts module loaded successfully');
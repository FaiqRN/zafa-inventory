/**
 * PARTNER PERFORMANCE ANALYTICS - JAVASCRIPT
 * Dedicated JS for Partner Performance Analytics Module
 */

// Partner Performance specific variables
let partnerPerformanceCharts = {};
let partnerPerformanceData = {};

// Chart color schemes specific to partner performance
const PARTNER_CHART_COLORS = {
    primary: '#007bff',
    success: '#28a745',
    warning: '#ffc107',
    danger: '#dc3545',
    info: '#17a2b8'
};

const GRADE_COLORS = {
    'A+': '#28a745',
    'A': '#007bff', 
    'B+': '#17a2b8',
    'B': '#ffc107',
    'C+': '#fd7e14',
    'C': '#dc3545'
};

const RISK_COLORS = {
    'Low': '#28a745',
    'Medium': '#ffc107',
    'High': '#dc3545'
};

/**
 * ===== PARTNER PERFORMANCE INITIALIZATION =====
 */
$(document).ready(function() {
    initializePartnerPerformance();
    setupPartnerEventHandlers();
    setupPartnerFilters();
    setupPartnerModals();
});

function initializePartnerPerformance() {
    showLoadingState();
    loadPartnerPerformanceData().then(data => {
        createPartnerRankingChart(data.data);
        createGradeDistributionChart(data.data);
        createPerformanceTrendChart(data.chart_data);
        createRiskAssessmentChart(data.data);
        createConsistencyChart(data.data);
        updatePartnerStatistics(data.summary);
        hideLoadingState();
    }).catch(handlePartnerError);
}

function loadPartnerPerformanceData() {
    return fetch('/analytics/partner-performance/api/data')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            partnerPerformanceData = data;
            return data;
        });
}

/**
 * ===== CHART CREATION FUNCTIONS =====
 */
function createPartnerRankingChart(partners) {
    const ctx = document.getElementById('partnerRankingChart');
    if (!ctx) return;
    
    destroyPartnerChart('partnerRanking');
    
    const topPartners = partners.slice(0, 15);
    const labels = topPartners.map(p => truncateText(p.nama_toko, 15));
    const data = topPartners.map(p => parseFloat(p.sell_through_rate));
    const colors = topPartners.map(p => GRADE_COLORS[p.grade] || PARTNER_CHART_COLORS.secondary);
    
    partnerPerformanceCharts.partnerRanking = new Chart(ctx, {
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
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        title: function(context) {
                            return topPartners[context[0].dataIndex].nama_toko;
                        },
                        label: function(context) {
                            const partner = topPartners[context.dataIndex];
                            return [
                                `Sell-Through: ${context.parsed.x.toFixed(1)}%`,
                                `Grade: ${partner.grade}`,
                                `Revenue: ${formatCurrency(partner.revenue)}`,
                                `Risk: ${partner.risk_score?.level || 'Low'}`,
                                `Trend: ${partner.trend?.trend || 'stable'}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Sell-Through Rate (%)' },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                y: {
                    ticks: {
                        font: { size: 11 }
                    }
                }
            },
            onClick: function(event, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const partner = topPartners[index];
                    showPartnerDetailModal(partner.toko_id);
                }
            }
        }
    });
}

function createGradeDistributionChart(partners) {
    const ctx = document.getElementById('gradeDistributionChart');
    if (!ctx) return;
    
    destroyPartnerChart('gradeDistribution');
    
    const gradeData = {};
    const gradeOrder = ['A+', 'A', 'B+', 'B', 'C+', 'C'];
    
    // Initialize grade data
    gradeOrder.forEach(grade => {
        gradeData[grade] = 0;
    });
    
    // Count partners by grade
    partners.forEach(partner => {
        if (gradeData.hasOwnProperty(partner.grade)) {
            gradeData[partner.grade]++;
        }
    });
    
    const labels = gradeOrder;
    const data = gradeOrder.map(grade => gradeData[grade]);
    const colors = gradeOrder.map(grade => GRADE_COLORS[grade]);
    
    partnerPerformanceCharts.gradeDistribution = new Chart(ctx, {
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
                                pointStyle: 'circle',
                                hidden: data.datasets[0].data[index] === 0
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
    
    destroyPartnerChart('performanceTrend');
    
    if (!monthlyData || monthlyData.length === 0) {
        monthlyData = generateSampleTrendData();
    }
    
    const labels = monthlyData.map(item => item.month);
    const performanceData = monthlyData.map(item => parseFloat(item.avg_performance || 0));
    const partnerData = monthlyData.map(item => parseInt(item.active_partners || 0));
    
    partnerPerformanceCharts.performanceTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Avg Performance (%)',
                    data: performanceData,
                    borderColor: PARTNER_CHART_COLORS.success,
                    backgroundColor: createGradient(ctx, PARTNER_CHART_COLORS.success, 0.1),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    yAxisID: 'y'
                },
                {
                    label: 'Active Partners',
                    data: partnerData,
                    borderColor: PARTNER_CHART_COLORS.info,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
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
                                return `Avg Performance: ${context.parsed.y.toFixed(1)}%`;
                            } else {
                                return `Active Partners: ${context.parsed.y}`;
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

function createRiskAssessmentChart(partners) {
    const ctx = document.getElementById('riskAssessmentChart');
    if (!ctx) return;
    
    destroyPartnerChart('riskAssessment');
    
    const riskData = { 'High': 0, 'Medium': 0, 'Low': 0 };
    
    partners.forEach(partner => {
        const riskLevel = partner.risk_score?.level || 'Low';
        if (riskData.hasOwnProperty(riskLevel)) {
            riskData[riskLevel]++;
        }
    });
    
    const labels = ['🔴 High Risk', '🟡 Medium Risk', '🟢 Low Risk'];
    const data = [riskData.High, riskData.Medium, riskData.Low];
    const colors = [RISK_COLORS.High, RISK_COLORS.Medium, RISK_COLORS.Low];
    
    partnerPerformanceCharts.riskAssessment = new Chart(ctx, {
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
                            const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return `${context.label}: ${context.parsed} partners (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createConsistencyChart(partners) {
    const ctx = document.getElementById('consistencyChart');
    if (!ctx) return;
    
    destroyPartnerChart('consistency');
    
    const consistencyRanges = {
        'Excellent (90-100%)': 0,
        'Good (70-89%)': 0,
        'Fair (50-69%)': 0,
        'Poor (0-49%)': 0
    };
    
    partners.forEach(partner => {
        const consistency = partner.consistency_score || 0;
        if (consistency >= 90) {
            consistencyRanges['Excellent (90-100%)']++;
        } else if (consistency >= 70) {
            consistencyRanges['Good (70-89%)']++;
        } else if (consistency >= 50) {
            consistencyRanges['Fair (50-69%)']++;
        } else {
            consistencyRanges['Poor (0-49%)']++;
        }
    });
    
    const labels = Object.keys(consistencyRanges);
    const data = Object.values(consistencyRanges);
    const colors = [PARTNER_CHART_COLORS.success, PARTNER_CHART_COLORS.primary, PARTNER_CHART_COLORS.warning, PARTNER_CHART_COLORS.danger];
    
    partnerPerformanceCharts.consistency = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Partners',
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed.y} partners`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Partners' }
                }
            }
        }
    });
}

/**
 * ===== EVENT HANDLERS =====
 */
function setupPartnerEventHandlers() {
    // Search functionality
    $('#searchPartners').on('keyup', debounce(function() {
        searchPartners();
    }, 300));
    
    // Filter functionality
    $('#gradeFilter, #performanceFilter, #riskFilter').on('change', function() {
        filterPartners();
    });
    
    // Reset filters
    $('#resetFilters').on('click', function() {
        resetPartnerFilters();
    });
    
    // Export functionality
    $('#exportPartnerPerformance').on('click', function() {
        exportPartnerPerformance();
    });
    
    // Refresh data
    $('#refreshPartnerData').on('click', function() {
        refreshPartnerPerformance();
    });
    
    // Send alert buttons
    $(document).on('click', '.send-partner-alert', function() {
        const partnerId = $(this).data('partner-id');
        showAlertModal(partnerId);
    });
    
    // Bulk actions
    $('#sendBulkAlerts').on('click', function() {
        sendBulkAlerts();
    });
    
    // Generate report
    $('#generateReport').on('click', function() {
        const format = $('#reportFormat').val();
        generatePartnerReport(format);
    });
}

function setupPartnerFilters() {
    // Initialize DataTable for partner list if exists
    if ($('#partnersTable').length > 0) {
        $('#partnersTable').DataTable({
            "order": [[ 4, "desc" ]], // Sort by performance score
            "pageLength": 25,
            "searching": false, // We'll use custom search
            "info": true,
            "lengthChange": true,
            "columnDefs": [
                { "orderable": false, "targets": [0, 7] } // Disable sorting for checkbox and actions
            ]
        });
    }
}

function setupPartnerModals() {
    // Partner detail modal
    $('#partnerDetailModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const partnerId = button.data('partner-id');
        if (partnerId) {
            loadPartnerDetail(partnerId);
        }
    });
    
    // Alert modal
    $('#alertModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const partnerId = button.data('partner-id');
        $('#alertPartnerId').val(partnerId);
    });
}

/**
 * ===== PARTNER ACTIONS =====
 */
function searchPartners() {
    const searchTerm = $('#searchPartners').val();
    const grade = $('#gradeFilter').val();
    const performance = $('#performanceFilter').val();
    
    const params = new URLSearchParams();
    if (searchTerm) params.append('search', searchTerm);
    if (grade) params.append('grade', grade);
    if (performance) params.append('performance', performance);
    
    fetch(`/analytics/partner-performance/api/search?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePartnerTable(data.data);
                updatePartnerCount(data.count);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            showNotification('Error searching partners', 'error');
        });
}

function filterPartners() {
    searchPartners(); // Use the same search function
}

function resetPartnerFilters() {
    $('#searchPartners').val('');
    $('#gradeFilter').val('');
    $('#performanceFilter').val('');
    $('#riskFilter').val('');
    searchPartners();
}

function refreshPartnerPerformance() {
    showLoadingState();
    showNotification('Refreshing partner performance data...', 'info');
    
    // Clear existing data
    partnerPerformanceData = {};
    
    // Reload everything
    initializePartnerPerformance();
}

function exportPartnerPerformance() {
    const filters = {
        grade: $('#gradeFilter').val(),
        performance: $('#performanceFilter').val(),
        risk: $('#riskFilter').val()
    };
    
    let url = '/analytics/partner-performance/export';
    const params = new URLSearchParams();
    
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            params.append(key + '_filter', filters[key]);
        }
    });
    
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    showNotification('Export started...', 'info');
    window.location.href = url;
}

function showPartnerDetailModal(partnerId) {
    showLoadingState('#partnerDetailContent');
    
    fetch(`/analytics/partner-performance/history/${partnerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPartnerDetail(data);
                $('#partnerDetailModal').modal('show');
            } else {
                showNotification(data.message || 'Error loading partner details', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading partner detail:', error);
            showNotification('Error loading partner details', 'error');
        })
        .finally(() => {
            hideLoadingState('#partnerDetailContent');
        });
}

function displayPartnerDetail(data) {
    const partner = data.partner;
    const history = data.history;
    const summary = data.summary;
    
    // Update modal content
    $('#partnerDetailModal .modal-title').text(`Partner Details: ${partner.name}`);
    
    let historyHtml = '<div class="table-responsive"><table class="table table-sm table-striped">';
    historyHtml += '<thead><tr><th>Month</th><th>Shipped</th><th>Sold</th><th>Returned</th><th>Sell-Through</th><th>Revenue</th><th>Grade</th></tr></thead><tbody>';
    
    history.forEach(item => {
        historyHtml += `<tr>
            <td>${item.month}</td>
            <td>${formatNumber(item.shipped)}</td>
            <td>${formatNumber(item.sold)}</td>
            <td>${formatNumber(item.returned)}</td>
            <td><span class="badge badge-${getGradeBadgeClass(item.grade)}">${item.sell_through_rate}%</span></td>
            <td>${formatCurrency(item.revenue)}</td>
            <td><span class="badge badge-${getGradeBadgeClass(item.grade)}">${item.grade}</span></td>
        </tr>`;
    });
    
    historyHtml += '</tbody></table></div>';
    
    // Add summary
    const summaryHtml = `
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5>${formatNumber(summary.total_shipped)}</h5>
                        <small>Total Shipped</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>${formatNumber(summary.total_sold)}</h5>
                        <small>Total Sold</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5>${formatCurrency(summary.total_revenue)}</h5>
                        <small>Total Revenue</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5>${summary.avg_sell_through}%</h5>
                        <small>Avg Sell-Through</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#partnerDetailContent').html(summaryHtml + historyHtml);
}

function showAlertModal(partnerId) {
    $('#alertPartnerId').val(partnerId);
    $('#alertModal').modal('show');
}

function sendPartnerAlert() {
    const partnerId = $('#alertPartnerId').val();
    const alertType = $('#alertType').val();
    const message = $('#alertMessage').val();
    
    if (!partnerId) {
        showNotification('Partner ID is required', 'error');
        return;
    }
    
    const $button = $('#sendAlertButton');
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    
    fetch(`/analytics/partner-performance/alert/${partnerId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        body: JSON.stringify({
            alert_type: alertType,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            $('#alertModal').modal('hide');
            $('#alertMessage').val('');
        } else {
            showNotification(data.message || 'Error sending alert', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending alert:', error);
        showNotification('Error sending alert', 'error');
    })
    .finally(() => {
        $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Alert');
    });
}

function sendBulkAlerts() {
    if (!confirm('Send alerts to all underperforming partners?')) {
        return;
    }
    
    const $button = $('#sendBulkAlerts');
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    
    fetch('/analytics/partner-performance/bulk-alerts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            if (data.details.errors_count > 0) {
                showNotification(`${data.details.errors_count} alerts had errors`, 'warning');
            }
        } else {
            showNotification(data.message || 'Error sending bulk alerts', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending bulk alerts:', error);
        showNotification('Error sending bulk alerts', 'error');
    })
    .finally(() => {
        $button.prop('disabled', false).html('<i class="fas fa-bullhorn"></i> Send Bulk Alerts');
    });
}

function generatePartnerReport(format) {
    const $button = $('#generateReport');
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
    
    fetch('/analytics/partner-performance/generate-report', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        body: JSON.stringify({
            format: format
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showReportModal(data.report);
        } else {
            showNotification(data.message || 'Error generating report', 'error');
        }
    })
    .catch(error => {
        console.error('Error generating report:', error);
        showNotification('Error generating report', 'error');
    })
    .finally(() => {
        $button.prop('disabled', false).html('<i class="fas fa-chart-line"></i> Generate Report');
    });
}

/**
 * ===== UTILITY FUNCTIONS =====
 */
function destroyPartnerChart(chartKey) {
    if (partnerPerformanceCharts[chartKey] && typeof partnerPerformanceCharts[chartKey].destroy === 'function') {
        partnerPerformanceCharts[chartKey].destroy();
        delete partnerPerformanceCharts[chartKey];
    }
}

function createGradient(ctx, color, opacity = 0.2) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, color + Math.round(opacity * 255).toString(16).padStart(2, '0'));
    gradient.addColorStop(1, color + '00');
    return gradient;
}

function updatePartnerStatistics(summary) {
    if (!summary) return;
    
    $('#totalPartners').text(formatNumber(summary.total_partners || 0));
    $('#avgPerformance').text((summary.avg_performance || 0).toFixed(1) + '%');
    $('#topPerformers').text(formatNumber(summary.grade_distribution?.['A+'] + summary.grade_distribution?.['A'] || 0));
    $('#needsAttention').text(formatNumber(summary.grade_distribution?.['C'] || 0));
    $('#totalRevenue').text(formatCurrency(summary.total_revenue || 0));
    $('#avgSellThrough').text((summary.overall_sell_through || 0).toFixed(1) + '%');
}

function updatePartnerTable(partners) {
    // Implementation depends on your table structure
    // This is a placeholder for updating the partner table
    console.log('Updating partner table with', partners.length, 'partners');
}

function updatePartnerCount(count) {
    $('#partnersCount').text(count);
}

function showLoadingState(selector = null) {
    if (selector) {
        $(selector).html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    } else {
        $('.chart-container').append('<div class="chart-loading"><i class="fas fa-spinner fa-spin"></i></div>');
    }
}

function hideLoadingState(selector = null) {
    if (selector) {
        // Content will be replaced by actual data
    } else {
        $('.chart-loading').remove();
    }
}

function handlePartnerError(error) {
    console.error('Partner performance error:', error);
    showNotification('Error loading partner performance data', 'error');
    hideLoadingState();
}

function formatCurrency(amount) {
    if (!amount || isNaN(amount)) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function formatNumber(number) {
    if (!number || isNaN(number)) return '0';
    return new Intl.NumberFormat('id-ID').format(number);
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function getGradeBadgeClass(grade) {
    const gradeClasses = {
        'A+': 'success',
        'A': 'primary',
        'B+': 'info',
        'B': 'warning',
        'C+': 'warning',
        'C': 'danger'
    };
    return gradeClasses[grade] || 'secondary';
}

function generateSampleTrendData() {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    return months.map(month => ({
        month: month,
        avg_performance: Math.random() * 30 + 60, // 60-90%
        active_partners: Math.floor(Math.random() * 20 + 30) // 30-50 partners
    }));
}

function showNotification(message, type = 'info') {
    // Integration with notification system
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            text: message,
            icon: type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'),
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    } else {
        alert(message);
    }
}

function showReportModal(report) {
    // Implementation for showing generated report
    console.log('Generated report:', report);
    $('#reportModal .modal-body').html('<pre>' + JSON.stringify(report, null, 2) + '</pre>');
    $('#reportModal').modal('show');
}

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

// Resize handler for charts
$(window).on('resize', debounce(function() {
    Object.keys(partnerPerformanceCharts).forEach(function(chartKey) {
        if (partnerPerformanceCharts[chartKey] && typeof partnerPerformanceCharts[chartKey].resize === 'function') {
            try {
                partnerPerformanceCharts[chartKey].resize();
            } catch (error) {
                console.warn('Error resizing chart:', chartKey, error);
            }
        }
    });
}, 300));

// Event handler for send alert button in modal
$(document).on('click', '#sendAlertButton', function() {
    sendPartnerAlert();
});

// Export for use in other modules
window.PartnerPerformance = {
    initialize: initializePartnerPerformance,
    refresh: refreshPartnerPerformance,
    export: exportPartnerPerformance,
    showDetail: showPartnerDetailModal,
    sendAlert: sendPartnerAlert,
    sendBulkAlerts: sendBulkAlerts,
    charts: partnerPerformanceCharts,
    data: partnerPerformanceData
};

console.log('Partner Performance Analytics JS loaded successfully');
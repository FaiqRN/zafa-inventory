// ==========================================
// ZAFA POTATO ANALYTICS - EFFICIENT JS
// ==========================================

let charts = {};
let currentData = [];

$(document).ready(function() {
    console.log('🥔 Analytics Loading...');
    setupEvents();
    // Auto-load after 500ms
    setTimeout(loadData, 500);
});

// Setup Events
function setupEvents() {
    $('#periodFilter').change(loadData);
    $('input[type="checkbox"]').change(debounce(loadData, 300));
    
    $('#selectAllPartners').change(function() {
        $('input[name="partners[]"]').prop('checked', this.checked);
        loadData();
    });
    
    $('#selectAllRegions').change(function() {
        $('input[name="regions[]"]').prop('checked', this.checked);
        loadData();
    });
    
    $('#selectAllProducts').change(function() {
        $('input[name="products[]"]').prop('checked', this.checked);
        loadData();
    });
    
    $('#partnerSearch').on('input', function() {
        const term = $(this).val().toLowerCase();
        $('.partner-item').each(function() {
            const name = $(this).data('name') || '';
            $(this).toggle(name.includes(term));
        });
    });
    
    $('#clearFilters').click(clearFilters);
    $('#refreshData').click(loadData);
    $('#loadSample').click(loadSampleData);
}

// Load Real Data
function loadData() {
    console.log('📊 Loading data...');
    showLoading(true);
    
    const filters = {
        period: $('#periodFilter').val(),
        partners: $('input[name="partners[]"]:checked').map((i, el) => el.value).get(),
        regions: $('input[name="regions[]"]:checked').map((i, el) => el.value).get(),
        products: $('input[name="products[]"]:checked').map((i, el) => el.value).get()
    };
    
    $.ajax({
        url: '/analytics/partner-performance',
        data: filters,
        success: function(response) {
            if (response.status === 'success') {
                console.log('✅ Data loaded:', response.data.length, 'partners');
                updateUI(response);
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            console.error('❌ Load failed:', xhr);
            showError('Failed to load data. Status: ' + xhr.status);
        },
        complete: () => showLoading(false)
    });
}

// Load Sample Data (for testing)
function loadSampleData() {
    console.log('📊 Loading sample data...');
    showLoading(true);
    
    // Hardcoded sample data
    const sampleResponse = {
        status: 'success',
        data: [
            {
                toko_id: 'TK001',
                nama_toko: 'Toko Maju Jaya',
                wilayah_kota_kabupaten: 'Malang',
                performance_score: 85.5,
                performance_grade: 'A+',
                sell_through_rate: 85.5,
                total_revenue: 2500000,
                total_cycles: 12
            },
            {
                toko_id: 'TK002',
                nama_toko: 'Warung Berkah',
                wilayah_kota_kabupaten: 'Batu',
                performance_score: 72.3,
                performance_grade: 'A',
                sell_through_rate: 72.3,
                total_revenue: 1800000,
                total_cycles: 10
            },
            {
                toko_id: 'TK003',
                nama_toko: 'Toko Sumber Rezeki',
                wilayah_kota_kabupaten: 'Malang',
                performance_score: 58.7,
                performance_grade: 'B',
                sell_through_rate: 58.7,
                total_revenue: 1200000,
                total_cycles: 8
            }
        ],
        summary: {
            total_partners: 3,
            avg_performance_score: 72.2,
            top_performers: 1,
            total_revenue: 5500000
        },
        grade_distribution: {
            'A+': 1,
            'A': 1,
            'B': 1,
            'C': 0,
            'D': 0
        }
    };
    
    setTimeout(() => {
        updateUI(sampleResponse);
        showLoading(false);
        showSuccess('Sample data loaded!');
    }, 1000);
}

// Update All UI Components
function updateUI(response) {
    currentData = response.data;
    updateSummary(response.summary);
    renderGradeChart(response.grade_distribution);
    renderScatterChart(response.data);
    updateTable(response.data);
}

// Update Summary Cards
function updateSummary(summary) {
    $('#totalPartners').text(summary.total_partners || 0);
    $('#avgPerformance').text((summary.avg_performance_score || 0) + '/100');
    $('#topPerformers').text(summer.top_performers || 0);
    $('#totalRevenue').text('Rp ' + formatNumber(summary.total_revenue || 0));
}

// Render Grade Chart (Donut)
function renderGradeChart(gradeData) {
    const ctx = document.getElementById('gradeChart');
    if (!ctx) return;
    
    if (charts.grade) charts.grade.destroy();
    
    const grades = ['A+', 'A', 'B', 'C', 'D'];
    const data = grades.map(g => gradeData[g] || 0);
    const colors = ['#28a745', '#17a2b8', '#ffc107', '#fd7e14', '#dc3545'];
    
    charts.grade = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: grades.map(g => `Grade ${g}`),
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Render Scatter Chart
function renderScatterChart(data) {
    const ctx = document.getElementById('scatterChart');
    if (!ctx) return;
    
    if (charts.scatter) charts.scatter.destroy();
    
    const gradeColors = {
        'A+': '#28a745', 'A': '#17a2b8', 'B': '#ffc107', 'C': '#fd7e14', 'D': '#dc3545'
    };
    
    const datasets = Object.keys(gradeColors).map(grade => {
        const gradeData = data.filter(item => item.performance_grade === grade);
        return {
            label: `Grade ${grade}`,
            data: gradeData.map(item => ({
                x: item.performance_score,
                y: item.total_revenue,
                label: item.nama_toko
            })),
            backgroundColor: gradeColors[grade],
            borderColor: gradeColors[grade]
        };
    }).filter(d => d.data.length > 0);
    
    charts.scatter = new Chart(ctx, {
        type: 'scatter',
        data: { datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        title: ctx => ctx[0].raw.label,
                        label: ctx => [
                            `Score: ${ctx.parsed.x}/100`,
                            `Revenue: Rp ${formatNumber(ctx.parsed.y)}`
                        ]
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Performance Score' },
                    min: 0, max: 100
                },
                y: {
                    title: { display: true, text: 'Revenue (Rp)' },
                    ticks: {
                        callback: value => 'Rp ' + formatNumber(value)
                    }
                }
            }
        }
    });
}

// Update Table
function updateTable(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-inbox fa-2x mb-2"></i><div>No data found</div></td></tr>';
    } else {
        data.forEach((partner, i) => {
            const gradeClass = getGradeClass(partner.performance_grade);
            html += `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        <strong>${partner.nama_toko}</strong>
                        <br><small class="text-muted">${partner.wilayah_kota_kabupaten || ''}</small>
                    </td>
                    <td>
                        <div class="progress mb-1">
                            <div class="progress-bar" style="width: ${partner.performance_score}%"></div>
                        </div>
                        ${partner.performance_score}/100
                    </td>
                    <td><span class="badge ${gradeClass}">${partner.performance_grade}</span></td>
                    <td>${partner.sell_through_rate}%</td>
                    <td>Rp ${formatNumber(partner.total_revenue)}</td>
                    <td>${partner.total_cycles}</td>
                </tr>
            `;
        });
    }
    
    $('#partnerTable').html(html);
}

// Utility Functions
function clearFilters() {
    $('#periodFilter').val(6);
    $('input[type="checkbox"]').prop('checked', false);
    $('#partnerSearch').val('');
    $('.partner-item').show();
    loadData();
}

function getGradeClass(grade) {
    const classes = {
        'A+': 'badge-success', 'A': 'badge-info', 'B': 'badge-warning', 
        'C': 'badge-danger', 'D': 'badge-dark'
    };
    return classes[grade] || 'badge-secondary';
}

function formatNumber(num) {
    if (!num) return '0';
    return new Intl.NumberFormat('id-ID').format(num);
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function showLoading(show) {
    $('#loadingOverlay').toggle(show);
}

function showSuccess(message) {
    console.log('✅', message);
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    }
}

function showError(message) {
    console.error('❌', message);
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        alert('Error: ' + message);
    }
}

// Fix typo in updateSummary function
function updateSummary(summary) {
    $('#totalPartners').text(summary.total_partners || 0);
    $('#avgPerformance').text((summary.avg_performance_score || 0) + '/100');
    $('#topPerformers').text(summary.top_performers || 0); // Fixed typo: summer -> summary
    $('#totalRevenue').text('Rp ' + formatNumber(summary.total_revenue || 0));
}

console.log('🥔 Analytics Ready!');
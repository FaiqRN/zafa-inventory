$(document).ready(function() {
    // Initialize dashboard
    loadDashboardData();
    
    // Event listeners
    $('#filter-tahun').change(function() {
        loadGrafikPengiriman();
    });
    
    $('.filter-barang').click(function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        const text = $(this).text().trim();
        $('#filter-barang-text').text(text);
        loadBarangAnalysis(filter);
    });
    
    $('#refresh-transaksi').click(function() {
        loadTransaksiTerbaru();
    });
});

// Load semua data dashboard
function loadDashboardData() {
    showLoading(true);
    
    // Load data statistik ringkasan
    loadStatistikRingkasan();
    
    // Load grafik dan tabel
    loadGrafikPengiriman();
    loadBarangAnalysis('laku');
    loadTransaksiTerbaru();
    loadTokoReturTerbanyak();
    
    showLoading(false);
    updateLastUpdateTime();
}

// Load statistik ringkasan (info boxes)
function loadStatistikRingkasan() {
    $.ajax({
        url: '/dashboard/api/statistik',
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                $('#total-barang').text(response.data.total_barang.toLocaleString());
                $('#total-toko').text(response.data.total_toko.toLocaleString());
                $('#pengiriman-bulan').text(response.data.pengiriman_bulan_ini.toLocaleString());
                $('#retur-bulan').text(response.data.retur_bulan_ini.toLocaleString());
                $('#pengiriman-hari').text(response.data.pengiriman_hari_ini);
                $('#pemesanan-hari').text(response.data.pemesanan_hari_ini);
            }
        },
        error: function() {
            $('#total-barang, #total-toko, #pengiriman-bulan, #retur-bulan').text('-');
        }
    });
}

// Load grafik pengiriman
function loadGrafikPengiriman() {
    const tahun = $('#filter-tahun').val() || new Date().getFullYear();
    
    $.ajax({
        url: `/dashboard/api/grafik-pengiriman?tahun=${tahun}`,
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                createLineChart(response.data);
            }
        },
        error: function() {
            showChartError('#pengiriman-chart', 'Gagal memuat grafik pengiriman');
        }
    });
}

// Create line chart untuk pengiriman
function createLineChart(data) {
    const ctx = document.getElementById('pengiriman-chart').getContext('2d');
    
    // Destroy existing chart
    if (window.pengirimanChart) {
        window.pengirimanChart.destroy();
    }
    
    window.pengirimanChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const tooltipData = data.tooltip_data[context.dataIndex];
                            return [
                                `Bulan: ${tooltipData.bulan}`,
                                `Total Dikirim: ${tooltipData.total_kirim.toLocaleString()} pcs`,
                                `Jumlah Pengiriman: ${tooltipData.jumlah_pengiriman}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Barang'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Bulan'
                    }
                }
            }
        }
    });
}

// Load analisis barang
function loadBarangAnalysis(filter = 'laku') {
    $.ajax({
        url: `/dashboard/api/barang-analysis?filter=${filter}`,
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                if (response.data.labels.length === 0) {
                    $('#empty-barang').show();
                    $('#chart-barang').hide();
                } else {
                    $('#empty-barang').hide();
                    $('#chart-barang').show();
                    createBarChart(response.data);
                }
            }
        },
        error: function() {
            showChartError('#chart-barang', 'Gagal memuat analisis barang');
        }
    });
}

// Create bar chart untuk barang
function createBarChart(data) {
    const ctx = document.getElementById('chart-barang').getContext('2d');
    
    // Destroy existing chart
    if (window.barangChart) {
        window.barangChart.destroy();
    }
    
    window.barangChart = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const detailData = data.detail_data[context.dataIndex];
                            return [
                                `Terjual: ${detailData.total_terjual.toLocaleString()} pcs`,
                                `Pendapatan: Rp ${detailData.total_penjualan.toLocaleString()}`,
                                `Transaksi: ${detailData.jumlah_transaksi}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Terjual'
                    }
                }
            }
        }
    });
}

// Load transaksi terbaru
function loadTransaksiTerbaru() {
    $('#refresh-transaksi').addClass('fa-spin');
    
    $.ajax({
        url: '/dashboard/api/transaksi-terbaru',
        method: 'GET',
        success: function(response) {
            const tbody = $('#table-transaksi');
            tbody.empty();
            
            if (response.status === 'success' && response.data.length > 0) {
                $('#empty-transaksi').hide();
                
                response.data.forEach(function(item) {
                    const row = `
                        <tr>
                            <td><strong>${item.nomer_pengiriman}</strong></td>
                            <td>
                                <div>${item.nama_toko}</div>
                                ${item.nama_pemesan !== '-' ? `<small class="text-muted">Pemesan: ${item.nama_pemesan}</small>` : ''}
                            </td>
                            <td>${item.nama_barang}</td>
                            <td><small>${item.tanggal_pengiriman}</small></td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.jumlah_pesanan.toLocaleString()}</span>
                            </td>
                            <td class="text-center">${item.status_badge}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                $('#empty-transaksi').show();
            }
        },
        error: function() {
            $('#table-transaksi').html('<tr><td colspan="6" class="text-center text-danger">Error memuat data</td></tr>');
        },
        complete: function() {
            $('#refresh-transaksi').removeClass('fa-spin');
        }
    });
}

// Load toko retur terbanyak
function loadTokoReturTerbanyak() {
    $.ajax({
        url: '/dashboard/api/toko-retur-terbanyak',
        method: 'GET',
        success: function(response) {
            const tbody = $('#table-retur');
            tbody.empty();
            
            if (response.status === 'success' && response.data.length > 0) {
                $('#empty-retur').hide();
                
                response.data.forEach(function(item) {
                    const stars = generateStars(item.persentase_retur);
                    const row = `
                        <tr>
                            <td>
                                <div><strong>${item.nama_toko}</strong></div>
                                <small class="text-muted">${item.pemilik}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-danger">${item.jumlah_retur}</span>
                            </td>
                            <td class="text-center ${item.rating_class}">
                                <strong>${item.persentase_retur}%</strong>
                            </td>
                            <td class="text-center">${stars}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                $('#empty-retur').show();
            }
        },
        error: function() {
            $('#table-retur').html('<tr><td colspan="4" class="text-center text-danger">Error memuat data</td></tr>');
        }
    });
}

// Generate stars rating
function generateStars(persentase) {
    let stars = 5;
    if (persentase > 15) stars = 1;
    else if (persentase > 10) stars = 2;
    else if (persentase > 5) stars = 3;
    else if (persentase > 2) stars = 4;

    let html = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= stars) {
            html += '<i class="fas fa-star text-warning"></i>';
        } else {
            html += '<i class="far fa-star text-muted"></i>';
        }
    }
    return html;
}

// Show/hide loading overlay
function showLoading(show) {
    if (show) {
        $('#loading-overlay').show();
    } else {
        $('#loading-overlay').hide();
    }
}

// Show chart error
function showChartError(chartSelector, message) {
    const container = $(chartSelector).parent();
    container.html(`
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle text-danger"></i>
            <p class="text-danger">${message}</p>
            <button class="btn btn-sm btn-outline-primary" onclick="loadDashboardData()">
                <i class="fas fa-sync"></i> Coba Lagi
            </button>
        </div>
    `);
}

// Update last update time
function updateLastUpdateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    $('#last-update').text(timeString);
}
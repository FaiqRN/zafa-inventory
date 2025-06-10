$(document).ready(function() {
    console.log('Dashboard script loaded');
    
    // Debug mode - cek apakah ada parameter debug di URL
    const urlParams = new URLSearchParams(window.location.search);
    const debugMode = urlParams.get('debug') === 'true';
    
    if (debugMode) {
        console.log('Debug mode enabled');
        loadDebugInfo();
    }
    
    // Initialize dashboard
    loadDashboardData();
    
    // Event listeners
    $('#filter-tahun').change(function() {
        console.log('Filter tahun changed to:', $(this).val());
        loadGrafikPengiriman();
    });
    
    $('.filter-barang').click(function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        const text = $(this).text().trim();
        console.log('Filter barang changed to:', filter);
        $('#filter-barang-text').text(text);
        loadBarangAnalysis(filter);
    });
    
    $('#refresh-transaksi').click(function() {
        console.log('Refreshing transaksi');
        loadTransaksiTerbaru();
    });
});

// Load debug info
function loadDebugInfo() {
    $.ajax({
        url: '/dashboard/api/debug',
        method: 'GET',
        success: function(response) {
            console.log('Debug Info:', response);
        },
        error: function(xhr, status, error) {
            console.error('Debug Error:', error);
        }
    });
}

// Load semua data dashboard
function loadDashboardData() {
    console.log('Loading dashboard data...');
    showLoading(true);
    
    // Load data satu per satu dengan delay untuk debugging
    loadStatistikRingkasan();
    
    setTimeout(() => {
        loadGrafikPengiriman();
    }, 500);
    
    setTimeout(() => {
        loadBarangAnalysis('laku');
    }, 1000);
    
    setTimeout(() => {
        loadTransaksiTerbaru();
    }, 1500);
    
    setTimeout(() => {
        loadTokoReturTerbanyak();
    }, 2000);
    
    setTimeout(() => {
        showLoading(false);
        updateLastUpdateTime();
    }, 2500);
}

// Load statistik ringkasan (info boxes) - DENGAN DEBUG
function loadStatistikRingkasan() {
    console.log('Loading statistik ringkasan...');
    
    $.ajax({
        url: '/dashboard/api/statistik',
        method: 'GET',
        success: function(response) {
            console.log('Statistik response:', response);
            
            if (response.status === 'success') {
                // Update angka dengan animasi sederhana
                $('#total-barang').text(response.data.total_barang.toLocaleString());
                $('#total-toko').text(response.data.total_toko.toLocaleString());
                $('#pengiriman-bulan').text(response.data.pengiriman_bulan_ini.toLocaleString());
                $('#retur-bulan').text(response.data.retur_bulan_ini.toLocaleString());
                $('#pengiriman-hari').text(response.data.pengiriman_hari_ini);
                $('#pemesanan-hari').text(response.data.pemesanan_hari_ini);
                
                console.log('Statistik loaded successfully');
            } else {
                console.error('Statistik failed:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading statistik:', {xhr, status, error});
            $('#total-barang, #total-toko, #pengiriman-bulan, #retur-bulan, #pengiriman-hari, #pemesanan-hari').text('-');
        }
    });
}

// Load grafik pengiriman - DENGAN DEBUG
function loadGrafikPengiriman() {
    const tahun = $('#filter-tahun').val() || new Date().getFullYear();
    console.log('Loading grafik pengiriman for year:', tahun);
    
    $.ajax({
        url: `/dashboard/api/grafik-pengiriman?tahun=${tahun}`,
        method: 'GET',
        success: function(response) {
            console.log('Grafik pengiriman response:', response);
            
            if (response.status === 'success') {
                createLineChart(response.data);
                console.log('Grafik pengiriman loaded successfully');
            } else {
                console.error('Grafik pengiriman failed:', response);
                showChartError('#pengiriman-chart', 'Data grafik tidak tersedia');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading grafik pengiriman:', {xhr, status, error});
            showChartError('#pengiriman-chart', 'Gagal memuat grafik pengiriman');
        }
    });
}

// Create line chart untuk pengiriman - SEDERHANA
function createLineChart(data) {
    console.log('Creating line chart with data:', data);
    
    const ctx = document.getElementById('pengiriman-chart');
    if (!ctx) {
        console.error('Chart canvas not found!');
        return;
    }
    
    // Destroy existing chart
    if (window.pengirimanChart) {
        window.pengirimanChart.destroy();
    }
    
    try {
        window.pengirimanChart = new Chart(ctx.getContext('2d'), {
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
                                    `Jumlah Pengiriman: ${tooltipData.jumlah_pengiriman} transaksi`
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
                            text: 'Jumlah Barang (pcs)'
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
        console.log('Line chart created successfully');
    } catch (error) {
        console.error('Error creating line chart:', error);
    }
}

// Load analisis barang - DENGAN DEBUG
function loadBarangAnalysis(filter = 'laku') {
    console.log('Loading barang analysis with filter:', filter);
    
    $.ajax({
        url: `/dashboard/api/barang-analysis?filter=${filter}&limit=10`,
        method: 'GET',
        success: function(response) {
            console.log('Barang analysis response:', response);
            
            if (response.status === 'success') {
                if (response.data.labels.length === 0) {
                    console.log('No barang data found');
                    $('#empty-barang').show();
                    $('#chart-barang').hide();
                } else {
                    console.log('Barang data found:', response.data.labels.length, 'items');
                    $('#empty-barang').hide();
                    $('#chart-barang').show();
                    createBarChart(response.data, filter);
                }
            } else {
                console.error('Barang analysis failed:', response);
                showChartError('#chart-barang', 'Data barang tidak tersedia');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading barang analysis:', {xhr, status, error});
            showChartError('#chart-barang', 'Gagal memuat analisis barang');
        }
    });
}

// Create bar chart untuk barang - SEDERHANA
function createBarChart(data, filter) {
    console.log('Creating bar chart with data:', data);
    
    const ctx = document.getElementById('chart-barang');
    if (!ctx) {
        console.error('Bar chart canvas not found!');
        return;
    }
    
    // Destroy existing chart
    if (window.barangChart) {
        window.barangChart.destroy();
    }
    
    try {
        window.barangChart = new Chart(ctx.getContext('2d'), {
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
                            text: 'Jumlah Terjual (pcs)'
                        }
                    }
                }
            }
        });
        console.log('Bar chart created successfully');
    } catch (error) {
        console.error('Error creating bar chart:', error);
    }
}

// Load transaksi terbaru - DENGAN DEBUG
function loadTransaksiTerbaru() {
    console.log('Loading transaksi terbaru...');
    
    const refreshBtn = $('#refresh-transaksi');
    refreshBtn.addClass('fa-spin');
    
    $.ajax({
        url: '/dashboard/api/transaksi-terbaru?limit=15',
        method: 'GET',
        success: function(response) {
            console.log('Transaksi terbaru response:', response);
            
            const tbody = $('#table-transaksi');
            tbody.empty();
            
            if (response.status === 'success' && response.data.length > 0) {
                console.log('Found', response.data.length, 'transaksi records');
                $('#empty-transaksi').hide();
                
                response.data.forEach(function(item, index) {
                    const row = `
                        <tr>
                            <td>
                                <strong class="text-primary">${item.nomer_pengiriman}</strong>
                                <br><small class="text-muted">${item.tanggal_pengiriman}</small>
                            </td>
                            <td>
                                <div class="font-weight-bold">${item.tujuan}</div>
                                ${item.jenis_pengiriman === 'customer' 
                                    ? '<small class="badge badge-info">Customer</small>' 
                                    : '<small class="badge badge-secondary">Toko</small>'}
                                ${item.nama_pemesan !== '-' ? `<br><small class="text-muted">Pemesan: ${item.nama_pemesan}</small>` : ''}
                            </td>
                            <td>
                                <span class="font-weight-bold">${item.nama_barang}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.jumlah_pesanan.toLocaleString()} pcs</span>
                                <br><small class="text-success">Rp ${item.total_harga.toLocaleString()}</small>
                            </td>
                            <td class="text-center">${item.status_badge}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                console.log('Transaksi table populated successfully');
            } else {
                console.log('No transaksi data found');
                $('#empty-transaksi').show();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading transaksi terbaru:', {xhr, status, error});
            $('#table-transaksi').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error memuat data transaksi
                    </td>
                </tr>
            `);
        },
        complete: function() {
            refreshBtn.removeClass('fa-spin');
        }
    });
}

// Load toko retur terbanyak - DENGAN DEBUG
function loadTokoReturTerbanyak() {
    console.log('Loading toko retur terbanyak...');
    
    $.ajax({
        url: '/dashboard/api/toko-retur-terbanyak?limit=10',
        method: 'GET',
        success: function(response) {
            console.log('Toko retur response:', response);
            
            const tbody = $('#table-retur');
            tbody.empty();
            
            if (response.status === 'success' && response.data.length > 0) {
                console.log('Found', response.data.length, 'toko retur records');
                $('#empty-retur').hide();
                
                response.data.forEach(function(item, index) {
                    const stars = generateStars(item.persentase_retur);
                    const row = `
                        <tr>
                            <td>
                                <div><strong>${item.nama_toko}</strong></div>
                                <small class="text-muted">${item.pemilik}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-danger">${item.jumlah_retur}</span>
                                <br><small class="text-muted">${item.total_barang_retur} pcs</small>
                            </td>
                            <td class="text-center ${item.rating_class}">
                                <strong>${item.persentase_retur}%</strong>
                                <br><small class="text-muted">${item.total_barang_kirim} total</small>
                            </td>
                            <td class="text-center">${stars}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                console.log('Toko retur table populated successfully');
            } else {
                console.log('No toko retur data found');
                $('#empty-retur').show();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading toko retur:', {xhr, status, error});
            $('#table-retur').html(`
                <tr>
                    <td colspan="4" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error memuat data retur
                    </td>
                </tr>
            `);
        }
    });
}

// Generate stars rating
function generateStars(persentase) {
    let stars = 5;
    if (persentase > 10) stars = 1;
    else if (persentase > 5) stars = 2;
    else if (persentase > 2) stars = 3;
    else if (persentase > 1) stars = 4;
    
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
    const overlay = $('#loading-overlay');
    if (show) {
        console.log('Showing loading overlay');
        overlay.css('display', 'flex');
    } else {
        console.log('Hiding loading overlay');
        overlay.hide();
    }
}

// Show chart error
function showChartError(chartSelector, message) {
    console.log('Showing chart error for:', chartSelector, message);
    const container = $(chartSelector).parent();
    container.html(`
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
            <p class="text-danger mt-2">${message}</p>
            <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadDashboardData()">
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
    
    const lastUpdateElement = $('#last-update');
    if (lastUpdateElement.length) {
        lastUpdateElement.text(timeString);
    }
    
    console.log('Dashboard updated at:', timeString);
}
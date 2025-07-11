/**
 * Javascript untuk Laporan Pemesanan
 */

// DataTable Instances
let tableBarang, tableSumber, tablePemesan, tableDetail;
let detailChart = null;
// Chart untuk visualisasi barang
let barangChart = null;
// Chart untuk visualisasi sumber
let sumberChart = null;
// Chart untuk summary di detail modal
let detailSummaryChart = null;

// Data status for current active tab
let currentTab = 'barang';
let currentPeriode = '1_bulan';
let currentBulan = '';
let currentTahun = '';
let startDate = '';
let endDate = '';
let detailId = '';
let detailName = '';

// Initialize DataTables
$(document).ready(function() {
    // Set current month and year
    currentBulan = $('#bulan').val();
    currentTahun = $('#tahun').val();
    
    // Inisialisasi DataTables
    tableBarang = $('#table-barang').DataTable({
        processing: true,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        columns: [
            { data: 'nama' },
            { 
                data: 'jumlah_pesanan',
                className: 'text-right' 
            },
            { 
                data: 'total_unit',
                className: 'text-right' 
            },
            { 
                data: 'total_pendapatan',
                className: 'text-right',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    let buttonCatatan = `<button type="button" class="btn btn-sm btn-warning catatan-btn mr-1" data-tipe="barang" data-id="${data.id}" data-nama="${data.nama}" data-catatan="${data.catatan || ''}">
                        <i class="fas fa-sticky-note"></i>
                    </button>`;
                    
                    let buttonDetail = `<button type="button" class="btn btn-sm btn-info detail-btn" data-tipe="barang" data-id="${data.id}" data-nama="${data.nama}">
                        <i class="fas fa-search"></i>
                    </button>`;
                    
                    return buttonCatatan + buttonDetail;
                }
            }
        ]
    });
    
    tableSumber = $('#table-sumber').DataTable({
        processing: true,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        columns: [
            { data: 'nama' },
            { 
                data: 'jumlah_pesanan',
                className: 'text-right' 
            },
            { 
                data: 'total_unit',
                className: 'text-right' 
            },
            { 
                data: 'total_pendapatan',
                className: 'text-right',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    let buttonCatatan = `<button type="button" class="btn btn-sm btn-warning catatan-btn mr-1" data-tipe="sumber" data-id="${data.id}" data-nama="${data.nama}" data-catatan="${data.catatan || ''}">
                        <i class="fas fa-sticky-note"></i>
                    </button>`;
                    
                    let buttonDetail = `<button type="button" class="btn btn-sm btn-info detail-btn" data-tipe="sumber" data-id="${data.id}" data-nama="${data.nama}">
                        <i class="fas fa-search"></i>
                    </button>`;
                    
                    return buttonCatatan + buttonDetail;
                }
            }
        ]
    });
    
    tablePemesan = $('#table-pemesan').DataTable({
        processing: true,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        columns: [
            { data: 'nama' },
            { 
                data: 'jumlah_pesanan',
                className: 'text-right' 
            },
            { 
                data: 'total_unit',
                className: 'text-right' 
            },
            { 
                data: 'total_pendapatan',
                className: 'text-right',
                render: function(data) {
                    return formatRupiah(data);
                } 
            },
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    let buttonCatatan = `<button type="button" class="btn btn-sm btn-warning catatan-btn mr-1" data-tipe="pemesan" data-id="${data.id}" data-nama="${data.nama}" data-catatan="${data.catatan || ''}">
                        <i class="fas fa-sticky-note"></i>
                    </button>`;
                    
                    let buttonDetail = `<button type="button" class="btn btn-sm btn-info detail-btn" data-tipe="pemesan" data-id="${data.id}" data-nama="${data.nama}">
                        <i class="fas fa-search"></i>
                    </button>`;
                    
                    return buttonCatatan + buttonDetail;
                }
            }
        ]
    });
    
    tableDetail = $('#table-detail').DataTable({
        processing: true,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        columns: [
            { data: 'pemesanan_id' },
            { data: 'tanggal' },
            { data: 'nama_barang' },
            { data: 'nama_pemesan' },
            { 
                data: 'jumlah',
                className: 'text-right' 
            },
            { 
                data: 'total',
                className: 'text-right',
                render: function(data) {
                    return formatRupiah(data);
                } 
            },
            { data: 'sumber' },
            { 
                data: 'status',
                className: 'text-center',
                render: function(data) {
                    let badges = {
                        'pending': 'badge-warning',
                        'diproses': 'badge-info',
                        'dikirim': 'badge-primary',
                        'selesai': 'badge-success',
                        'dibatalkan': 'badge-danger'
                    };
                    
                    return `<span class="badge ${badges[data] || 'badge-secondary'}">${data}</span>`;
                }
            }
        ]
    });
    
    // Filter button event handler
    $('#btn-filter').click(function() {
        currentPeriode = $('#periode').val();
        currentBulan = $('#bulan').val();
        currentTahun = $('#tahun').val();
        
        // Update periode display text
        updatePeriodeDisplay();
        
        // Refresh data for the active tab
        refreshData(currentTab);
    });
    
    // Periode change handler untuk mengatur opsi bulan
    $('#periode').change(function() {
        let selectedPeriode = $(this).val();
        let bulanSelect = $('#bulan');
        
        if (selectedPeriode === '6_bulan') {
            // Untuk 6 bulan, hanya tampilkan bulan akhir semester
            bulanSelect.prop('disabled', false);
            bulanSelect.empty();
            bulanSelect.append('<option value="6">Januari-Juni (Semester 1)</option>');
            bulanSelect.append('<option value="12">Juli-Desember (Semester 2)</option>');

            // Set default ke semester saat ini
            let currentMonth = new Date().getMonth() + 1;
            if (currentMonth <= 6) {
                bulanSelect.val('6');
            } else {
                bulanSelect.val('12');
            }
        } else if (selectedPeriode === '1_tahun') {
            // Untuk 1 tahun, disable bulan
            bulanSelect.prop('disabled', true);
            bulanSelect.empty();
            bulanSelect.append('<option value="12">Tahun Penuh</option>');
            bulanSelect.val('12');
        } else {
            // Untuk 1 bulan, tampilkan semua bulan
            bulanSelect.prop('disabled', false);
            bulanSelect.empty();
            let months = [
                {val: '1', text: 'Januari'},
                {val: '2', text: 'Februari'},
                {val: '3', text: 'Maret'},
                {val: '4', text: 'April'},
                {val: '5', text: 'Mei'},
                {val: '6', text: 'Juni'},
                {val: '7', text: 'Juli'},
                {val: '8', text: 'Agustus'},
                {val: '9', text: 'September'},
                {val: '10', text: 'Oktober'},
                {val: '11', text: 'November'},
                {val: '12', text: 'Desember'}
            ];
            
            let currentMonth = new Date().getMonth() + 1;
            months.forEach(function(month) {
                let selected = month.val == currentMonth ? 'selected' : '';
                bulanSelect.append(`<option value="${month.val}" ${selected}>${month.text}</option>`);
            });
        }
    });
    
    // Load data awal dan set periode display
    $('#periode').trigger('change'); // Trigger untuk set initial state
    updatePeriodeDisplay();
    refreshData('barang');
    
    // Tab change event handler
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        let targetId = $(e.target).attr('id');
        currentTab = targetId.split('-')[0];
        refreshData(currentTab);
    });
    
    // Button refresh event handlers
    $('#refresh-barang').click(function() {
        refreshData('barang');
    });
    
    $('#refresh-sumber').click(function() {
        refreshData('sumber');
    });
    
    $('#refresh-pemesan').click(function() {
        refreshData('pemesan');
    });
/**
 * Handle saat tombol Detail diklik
 */
    $(document).on('click', '.btn-detail', function() {
        const tipe = currentTab;
        const id = $(this).data('id');
        const name = $(this).data('name');
        // Set variabel global
        detailId = id;
        detailName = name;
        // Update judul modal
        const modalTitle = `Detail ${tipe === 'barang' ? 'Barang' : tipe === 'sumber' ? 'Sumber Pemesanan' : 'Pemesan'}: ${name}`;
        $('#detail-modal-title').text(modalTitle);
        // Simpan posisi scroll saat ini
        const scrollPosition = window.scrollY;
        // Load data
        loadDetailData(tipe, id);
        // Tampilkan modal
        $('#detail-modal').modal('show');
        // Mencegah scrolling otomatis dengan mengembalikan ke posisi sebelumnya
        setTimeout(function() {
            window.scrollTo(0, scrollPosition);
        }, 50);
    });
    
    // Save catatan handler
    $('#save-catatan').click(function() {
        let formData = {
            tipe: $('#catatan-tipe').val(),
            id: $('#catatan-id').val(),
            catatan: $('#catatan').val(),
            periode: currentPeriode,
            bulan: currentBulan,
            tahun: currentTahun
        };
        
        $.ajax({
            url: '/laporan-pemesanan/update-catatan',
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    $('#modal-catatan').modal('hide');
                    refreshData(currentTab);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat menyimpan catatan'
                });
            }
        });
    });
    
    // Detail button click handler
    $(document).on('click', '.detail-btn', function() {
        let tipe = $(this).data('tipe');
        let id = $(this).data('id');
        let nama = $(this).data('nama');
        
        detailId = id;
        detailName = nama;
        
        $('#modalDetailTitle').text('Detail ' + 
            (tipe === 'barang' ? 'Barang: ' : tipe === 'sumber' ? 'Sumber Pemesanan: ' : 'Pemesan: ') + 
            nama);
        
        loadDetailData(tipe, id);
        
        $('#modal-detail').modal('show');
    });
    
    // Export handlers
    $('#export-barang').click(function() {
        exportData('barang');
    });
    
    $('#export-sumber').click(function() {
        exportData('sumber');
    });
    
    $('#export-pemesan').click(function() {
        exportData('pemesan');
    });
    
    $('#export-detail').click(function() {
        exportDetailData();
    });
    
    // Print handlers
    $('#print-barang').click(function() {
        printData('barang');
    });
    
    $('#print-sumber').click(function() {
        printData('sumber');
    });
    
    $('#print-pemesan').click(function() {
        printData('pemesan');
    });
    
    $('#print-detail').click(function() {
        printDetailData();
    });
});

/**
 * Update periode display text
 */
function updatePeriodeDisplay() {
    let bulanValue = $('#bulan').val();
    let tahunText = $('#tahun option:selected').text();
    let periodeText = '';
    
    if (currentPeriode === '1_bulan') {
        let bulanText = $('#bulan option:selected').text();
        periodeText = `${bulanText} ${tahunText}`;
    } else if (currentPeriode === '6_bulan') {
        // Calculate 6 months period based on semester
        let month = parseInt(bulanValue);
        let year = parseInt(currentTahun);
        
        if (month === 6) {
            // Semester 1: Januari - Juni
            periodeText = `Januari ${year} - Juni ${year} (Semester 1)`;
        } else if (month === 12) {
            // Semester 2: Juli - Desember
            periodeText = `Juli ${year} - Desember ${year} (Semester 2)`;
        }
    } else { // 1_tahun
        // Periode 1 tahun: berdasarkan tahun yang dipilih
        let year = parseInt(currentTahun);
        periodeText = `Januari ${year} - Desember ${year} (Tahun ${year})`;
    }
    
    $('#periode-display').text(periodeText);
}

/**
 * Create barang chart
 */
function createBarangChart(data) {
    // Destroy existing chart if it exists
    if (barangChart) {
        barangChart.destroy();
    }
    
    // Sort data by total_pendapatan in descending order
    data.sort((a, b) => b.total_pendapatan - a.total_pendapatan);
    
    // Take top 10 items for better readability if there are many items
    let chartData = data;
    if (data.length > 10) {
        chartData = data.slice(0, 10);
    }
    
    // Prepare data for the chart
    const labels = chartData.map(item => item.nama);
    const totalPendapatan = chartData.map(item => item.total_pendapatan);
    const jumlahUnit = chartData.map(item => item.total_unit);
    
    // Create chart
    const ctx = document.getElementById('barang-chart').getContext('2d');
    barangChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Pendapatan (Rp)',
                    data: totalPendapatan,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1',
                },
                {
                    label: 'Jumlah Unit Terjual',
                    data: jumlahUnit,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    yAxisID: 'y2',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: data.length > 10 ? 'Top 10 Barang Berdasarkan Pendapatan' : 'Perbandingan Penjualan Semua Barang',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            
                            if (label) {
                                label += ': ';
                            }
                            
                            if (context.dataset.yAxisID === 'y1') {
                                label += formatRupiah(context.raw);
                            } else {
                                label += context.raw;
                            }
                            
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Total Pendapatan (Rp)'
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000).toFixed(1) + ' rb';
                            }
                            return 'Rp ' + value;
                        }
                    }
                },
                y2: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Jumlah Unit Terjual'
                    },
                    grid: {
                        drawOnChartArea: false // only want the grid lines for one axis to show up
                    }
                }
            }
        }
    });
    
    // Add note if there are more than 10 items
    if (data.length > 10) {
        const chartContainer = document.querySelector('#tab-barang .chart-container');
        // Remove existing note if any
        const existingNote = chartContainer.querySelector('.chart-note');
        if (existingNote) {
            existingNote.remove();
        }
        
        let noteElement = document.createElement('div');
        noteElement.className = 'text-muted text-center mt-2 chart-note';
        noteElement.innerHTML = 'Menampilkan 10 dari ' + data.length + ' barang dengan pendapatan tertinggi';
        chartContainer.appendChild(noteElement);
    }
}

/**
 * Create sumber chart (Fungsi baru)
 */
function createSumberChart(data) {
    // Destroy existing chart if it exists
    if (sumberChart) {
        sumberChart.destroy();
    }
    
    // Sort data by jumlah_pesanan in descending order
    data.sort((a, b) => b.jumlah_pesanan - a.jumlah_pesanan);
    
    // Prepare data for the chart
    const labels = data.map(item => item.nama);
    const jumlahPesanan = data.map(item => item.jumlah_pesanan);
    
    // Calculate percentages
    const total = jumlahPesanan.reduce((sum, value) => sum + value, 0);
    const percentages = jumlahPesanan.map(value => ((value / total) * 100).toFixed(1));
    
    // Generate background colors
    const backgroundColors = generateColorArray(data.length);
    
    // Create chart
    const ctx = document.getElementById('sumber-chart').getContext('2d');
    sumberChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: jumlahPesanan,
                backgroundColor: backgroundColors,
                borderColor: 'white',
                borderWidth: 1,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 20
            },
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        boxWidth: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Distribusi Pemesanan Berdasarkan Sumber',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const percentage = percentages[context.dataIndex];
                            return `${label}: ${value} pesanan (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    display: function(context) {
                        // Only show data labels for slices with more than 5% of the total
                        return parseFloat(percentages[context.dataIndex]) > 5;
                    },
                    formatter: function(value, context) {
                        return percentages[context.dataIndex] + '%';
                    },
                    color: 'white',
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                }
            }
        }
    });

    // Add legend title
    if (data.length > 0) {
        const chartContainer = document.querySelector('#tab-sumber .chart-container');
        // Remove existing legend title if any
        const existingTitle = chartContainer.querySelector('.legend-title');
        if (existingTitle) {
            existingTitle.remove();
        }
        
        let totalPesanan = data.reduce((sum, item) => sum + item.jumlah_pesanan, 0);
        let legendTitle = document.createElement('div');
        legendTitle.className = 'text-center mt-3 legend-title';
        legendTitle.innerHTML = `<strong>Total Pesanan: ${totalPesanan}</strong>`;
        chartContainer.appendChild(legendTitle);
    }
}

/**
 * Refresh data for specified table
 */
function refreshData(tipe) {
    $.ajax({
        url: '/laporan-pemesanan/data',
        type: 'GET',
        data: {
            tipe: tipe,
            periode: currentPeriode,
            bulan: currentBulan,
            tahun: currentTahun
        },
        beforeSend: function() {
            // Show loading indicator
            $(`#table-${tipe} tbody`).html('<tr><td colspan="5" class="text-center">Loading data...</td></tr>');
            
            // Show loading indicator for chart
            if (tipe === 'barang' || tipe === 'sumber') {
                $(`#tab-${tipe} .chart-container`).html('<div class="d-flex justify-content-center align-items-center" style="height:350px;"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span></div>');
            }
        },
        success: function(response) {
            if (response.success) {
                // Save date range for export
                startDate = response.periode.start;
                endDate = response.periode.end;
                
                // Update table data
                switch (tipe) {
                    case 'barang':
                        tableBarang.clear().rows.add(response.data).draw();
                        $('#barang-total-pesanan').text(response.total.pesanan);
                        $('#barang-total-unit').text(response.total.unit);
                        $('#barang-total-pendapatan').text(formatRupiah(response.total.pendapatan));
                        
                        // Create or update chart
                        if (response.data.length > 0) {
                            $('#tab-barang .chart-container').html('<canvas id="barang-chart"></canvas>');
                            createBarangChart(response.data);
                        } else {
                            $('#tab-barang .chart-container').html('<div class="alert alert-info text-center">Tidak ada data untuk ditampilkan</div>');
                        }
                        break;
                    case 'sumber':
                        tableSumber.clear().rows.add(response.data).draw();
                        $('#sumber-total-pesanan').text(response.total.pesanan);
                        $('#sumber-total-unit').text(response.total.unit);
                        $('#sumber-total-pendapatan').text(formatRupiah(response.total.pendapatan));
                        
                        // Create or update chart
                        if (response.data.length > 0) {
                            $('#tab-sumber .chart-container').html('<canvas id="sumber-chart"></canvas>');
                            createSumberChart(response.data);
                        } else {
                            $('#tab-sumber .chart-container').html('<div class="alert alert-info text-center">Tidak ada data untuk ditampilkan</div>');
                        }
                        break;
                    case 'pemesan':
                        tablePemesan.clear().rows.add(response.data).draw();
                        $('#pemesan-total-pesanan').text(response.total.pesanan);
                        $('#pemesan-total-unit').text(response.total.unit);
                        $('#pemesan-total-pendapatan').text(formatRupiah(response.total.pendapatan));
                        break;
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Gagal memuat data'
            });
        }
    });
}

function loadDetailData(tipe, id) {
    // Simpan posisi scroll saat ini
    const scrollPosition = window.scrollY;
    
    $.ajax({
        url: '/laporan-pemesanan/detail',
        type: 'GET',
        data: {
            tipe: tipe,
            id: id,
            start_date: startDate,
            end_date: endDate
        },
        beforeSend: function() {
            // Show loading indicator
            $('#table-detail tbody').html('<tr><td colspan="8" class="text-center">Loading data...</td></tr>');
            
            // Show loading indicator for chart
            $('#detail-chart-container').html('<div class="d-flex justify-content-center align-items-center" style="height:300px;"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span></div>');
        },
        success: function(response) {
            if (response.success) {
                // Update table
                tableDetail.clear().rows.add(response.data).draw();
                
                // Update chart based on the type
                if (tipe === 'sumber') {
                    if (response.chart_data && response.summary) {
                        updateDetailSumberChart(response.chart_data, response.summary);
                    } else {
                        console.error('Data chart atau summary tidak tersedia');
                        $('#detail-chart-container').html('<div class="alert alert-warning">Data chart tidak tersedia</div>');
                    }
                } else {
                    // Reset chart container for non-sumber types
                    $('#detail-chart-container').html('<canvas id="detail-chart" height="300"></canvas>');
                    
                    if (response.chart_data) {
                        updateDetailChart(response.chart_data);
                    } else {
                        console.error('Data chart tidak tersedia');
                        $('#detail-chart-container').html('<div class="alert alert-warning">Data chart tidak tersedia</div>');
                    }
                }
                
                // Mencegah scroll otomatis
                setTimeout(function() {
                    window.scrollTo(0, scrollPosition);
                }, 50);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Terjadi kesalahan saat memuat data detail'
                });
            }
        },
        error: function(xhr) {
            console.error('Error AJAX:', xhr);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Gagal memuat data detail. Silakan coba lagi.'
            });
            
            // Mencegah scroll otomatis
            setTimeout(function() {
                window.scrollTo(0, scrollPosition);
            }, 50);
        }
    });
}
/**
 * Update chart in detail modal
 */
function updateDetailChart(chartData) {
    // Destroy existing chart if exists
    if (detailChart) {
        detailChart.destroy();
    }
    
    // Prepare data
    const labels = chartData.map(item => item.month);
    const totalData = chartData.map(item => item.total);
    const countData = chartData.map(item => item.count);
    
    // Create new chart
    const ctx = document.getElementById('detail-chart').getContext('2d');
    detailChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Pendapatan (Rp)',
                    data: totalData,
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 3,
                    fill: false, // Ubah ke false untuk melihat garis lebih jelas
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 8,
                    pointHoverRadius: 10,
                    yAxisID: 'y1',
                    spanGaps: true, // Tambahkan ini untuk menangani data yang hilang
                    showLine: true // Pastikan garis ditampilkan
                },
                {
                    label: 'Jumlah Pesanan',
                    data: countData,
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 3,
                    fill: false, // Ubah ke false untuk melihat garis lebih jelas
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 8,
                    pointHoverRadius: 10,
                    yAxisID: 'y2',
                    spanGaps: true, // Tambahkan ini untuk menangani data yang hilang
                    showLine: true // Pastikan garis ditampilkan
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            elements: {
                line: {
                    tension: 0.4
                },
                point: {
                    radius: 6,
                    hoverRadius: 8
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: `Trend Pemesanan - ${detailName}`,
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255,255,255,0.2)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            
                            if (label) {
                                label += ': ';
                            }
                            
                            if (context.dataset.yAxisID === 'y1') {
                                label += formatRupiah(context.raw);
                            } else {
                                label += context.raw + ' pesanan';
                            }
                            
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Periode',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Total Pendapatan (Rp)',
                        font: {
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        callback: function(value) {
                            return formatRupiah(value);
                        }
                    },
                    grid: {
                        color: 'rgba(54, 162, 235, 0.1)'
                    }
                },
                y2: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Jumlah Pesanan',
                        font: {
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Math.floor(value);
                        }
                    },
                    grid: {
                        drawOnChartArea: false,
                        color: 'rgba(255, 99, 132, 0.1)'
                    }
                }
            }
        }
    });
}

/**
 * Update chart in detail modal specifically for Sumber type
 */
function updateDetailSumberChart(chartData, summary) {
    // Clear existing chart container and create layout for dual charts
    $('#detail-chart-container').html(`
        <div class="row">
            <div class="col-md-7">
                <div class="chart-wrapper mb-3">
                    <h5 class="text-center">Trend Pemesanan dari ${detailName}</h5>
                    <canvas id="detail-chart" height="250"></canvas>
                </div>
            </div>
            <div class="col-md-5">
                <div class="chart-wrapper">
                    <h5 class="text-center">Kontribusi terhadap Total Pemesanan</h5>
                    <canvas id="detail-summary-chart" height="250"></canvas>
                </div>
            </div>
        </div>
    `);
    
    // Destroy existing charts if they exist
    if (detailChart) {
        detailChart.destroy();
    }
    
    if (detailSummaryChart) {
        detailSummaryChart.destroy();
    }
    
    // Prepare data for time series chart
    const labels = chartData.map(item => item.month);
    const totalData = chartData.map(item => item.total);
    const countData = chartData.map(item => item.count);
    
    // Create trend line chart
    const ctx = document.getElementById('detail-chart').getContext('2d');
    detailChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Pendapatan (Rp)',
                    data: totalData,
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    yAxisID: 'y1',
                    spanGaps: true,
                    showLine: true
                },
                {
                    label: 'Jumlah Pesanan',
                    data: countData,
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    yAxisID: 'y2',
                    spanGaps: true,
                    showLine: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 10,
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            
                            if (label) {
                                label += ': ';
                            }
                            
                            if (context.dataset.yAxisID === 'y1') {
                                label += formatRupiah(context.raw);
                            } else {
                                label += context.raw + ' pesanan';
                            }
                            
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: {
                        font: {
                            size: 10
                        },
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000).toFixed(1) + ' rb';
                            }
                            return 'Rp ' + value;
                        }
                    }
                },
                y2: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    ticks: {
                        font: {
                            size: 10
                        },
                        stepSize: 1
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    
    // Create summary pie chart if summary data exists
    if (summary && typeof summary === 'object') {
        if (!summary.thisSource || !summary.total) {
            console.error('Format data summary tidak sesuai:', summary);
            return;
        }
        const otherSourcesCount = summary.total.jumlah_pesanan - summary.thisSource.jumlah_pesanan;
        const otherSourcesPendapatan = summary.total.total_pendapatan - summary.thisSource.total_pendapatan;
        
        if (!document.getElementById('detail-summary-chart')) {
            console.error('Elemen detail-summary-chart tidak ditemukan');
            return;
        }
        const summaryCtx = document.getElementById('detail-summary-chart').getContext('2d');
        detailSummaryChart = new Chart(summaryCtx, {
            type: 'pie',
            data: {
                labels: [`${detailName}`, 'Sumber Lainnya'],
                datasets: [
                    {
                        label: 'Jumlah Pesanan',
                        data: [summary.thisSource.jumlah_pesanan, otherSourcesCount],
                        backgroundColor: ['rgba(54, 162, 235, 0.8)', 'rgba(211, 211, 211, 0.7)'],
                        borderColor: ['rgba(54, 162, 235, 1)', 'rgba(211, 211, 211, 1)'],
                        borderWidth: 1,
                        hoverOffset: 15
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            boxWidth: 10,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = summary.total.jumlah_pesanan;
                                const percentage = ((value / total) * 100).toFixed(1);
                                
                                if (context.dataIndex === 0) {
                                    return [
                                        `${label}: ${value} pesanan (${percentage}%)`,
                                        `Pendapatan: ${formatRupiah(summary.thisSource.total_pendapatan)}`
                                    ];
                                } else {
                                    return [
                                        `${label}: ${value} pesanan (${percentage}%)`,
                                        `Pendapatan: ${formatRupiah(otherSourcesPendapatan)}`
                                    ];
                                }
}
                        }
                    }
                }
            }
        });

        // Add summary statistics below the charts
        const statsContainer = document.createElement('div');
        statsContainer.className = 'row mt-3';
        statsContainer.innerHTML = `
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body p-3">
                        <h6 class="text-center mb-3">Ringkasan Kontribusi ${detailName}</h6>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border-right">
                                    <p class="mb-0 text-muted">Jumlah Pesanan</p>
                                    <h5>${summary.thisSource.jumlah_pesanan} dari ${summary.total.jumlah_pesanan} (${((summary.thisSource.jumlah_pesanan / summary.total.jumlah_pesanan) * 100).toFixed(1)}%)</h5>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border-right">
                                    <p class="mb-0 text-muted">Total Unit</p>
                                    <h5>${summary.thisSource.total_unit} dari ${summary.total.total_unit} (${((summary.thisSource.total_unit / summary.total.total_unit) * 100).toFixed(1)}%)</h5>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div>
                                    <p class="mb-0 text-muted">Total Pendapatan</p>
                                    <h5>${formatRupiah(summary.thisSource.total_pendapatan)} dari ${formatRupiah(summary.total.total_pendapatan)} (${((summary.thisSource.total_pendapatan / summary.total.total_pendapatan) * 100).toFixed(1)}%)</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('detail-chart-container').appendChild(statsContainer);
    }
}

/**
 * Generate an array of unique colors for chart
 */
function generateColorArray(count) {
    const baseColors = [
        'rgba(54, 162, 235, 0.8)',   // Blue
        'rgba(255, 99, 132, 0.8)',   // Red
        'rgba(255, 206, 86, 0.8)',   // Yellow
        'rgba(75, 192, 192, 0.8)',   // Green
        'rgba(153, 102, 255, 0.8)',  // Purple
        'rgba(255, 159, 64, 0.8)',   // Orange
        'rgba(199, 199, 199, 0.8)',  // Gray
        'rgba(83, 102, 255, 0.8)',   // Indigo
        'rgba(255, 99, 255, 0.8)',   // Pink
        'rgba(25, 125, 52, 0.8)'     // Dark Green
    ];
    
    let colors = [];
    
    // If we need more colors than in our base array
    if (count > baseColors.length) {
        // First add all base colors
        colors = [...baseColors];
        
        // Then generate additional random colors
        for (let i = baseColors.length; i < count; i++) {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            colors.push(`rgba(${r}, ${g}, ${b}, 0.8)`);
        }
    } else {
        // If we need fewer colors, just take what we need
        colors = baseColors.slice(0, count);
    }
    
    return colors;
}

/**
 * Export data to CSV
 */
function exportData(tipe) {
    let url = `/laporan-pemesanan/export-csv?tipe=${tipe}&start_date=${startDate}&end_date=${endDate}`;
    window.location.href = url;
}

/**
 * Export detail data to CSV
 */
function exportDetailData() {
    let url = `/laporan-pemesanan/export-csv?tipe=${currentTab}&start_date=${startDate}&end_date=${endDate}&detail_id=${detailId}`;
    window.location.href = url;
}

/**
 * Print data
 */
function printData(tipe) {
    let table = '';
    let title = '';
    let period = `Periode: ${formatDate(startDate)} - ${formatDate(endDate)}`;
    let chartImageHtml = '';
    
    switch (tipe) {
        case 'barang':
            table = document.getElementById('table-barang');
            title = 'Laporan Pemesanan Per Barang';
            // Add chart image if it exists
            if (barangChart) {
                let chartImage = barangChart.toBase64Image();
                chartImageHtml = `
                    <div class="chart-container">
                        <h3>Grafik Pemesanan Barang</h3>
                        <img src="${chartImage}" alt="Grafik Pemesanan Barang" style="max-width: 100%; max-height: 400px;">
                    </div>
                `;
            }
            break;
        case 'sumber':
            table = document.getElementById('table-sumber');
            title = 'Laporan Pemesanan Per Sumber';
            // Add chart image if it exists
            if (sumberChart) {
                let chartImage = sumberChart.toBase64Image();
                chartImageHtml = `
                    <div class="chart-container">
                        <h3>Grafik Distribusi Sumber Pemesanan</h3>
                        <img src="${chartImage}" alt="Grafik Sumber Pemesanan" style="max-width: 100%; max-height: 400px;">
                    </div>
                `;
            }
            break;
        case 'pemesan':
            table = document.getElementById('table-pemesan');
            title = 'Laporan Pemesanan Per Pemesan';
            break;
    }
    
    let printContent = `
        <html>
        <head>
            <title>${title}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                table {
                    border-collapse: collapse;
                    width: 100%;
                    margin-bottom: 20px;
                }
                table, th, td {
                    border: 1px solid #ddd;
                }
                th, td {
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                h1, h2, h3 {
                    text-align: center;
                }
                .print-header {
                    margin-bottom: 20px;
                }
                .print-footer {
                    margin-top: 30px;
                    text-align: right;
                }
                .total-row {
                    font-weight: bold;
                    background-color: #f2f2f2;
                }
                .chart-container {
                    text-align: center;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>${title}</h1>
                <h2>${period}</h2>
            </div>
            
            ${chartImageHtml}
            
            <h3>Tabel Data Pemesanan</h3>
            <table id="print-table">
                <thead>
                    <tr>
                        ${Array.from(table.querySelectorAll('thead th')).slice(0, -1).map(th => `<th>${th.textContent}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${Array.from(table.querySelectorAll('tbody tr')).map(tr => {
                        return `<tr>${Array.from(tr.querySelectorAll('td')).slice(0, -1).map(td => {
                            return `<td class="${td.className}">${td.textContent}</td>`;
                        }).join('')}</tr>`;
                    }).join('')}
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        ${Array.from(table.querySelectorAll('tfoot th')).slice(0, -1).map(th => `<th class="${th.className}">${th.textContent}</th>`).join('')}
                    </tr>
                </tfoot>
            </table>
            <div class="print-footer">
                <p>Dicetak pada: ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;
    
    printContentInNewWindow(printContent);
}

/**
 * Print detail data
 */
function printDetailData() {
    let title = `Detail Pemesanan ${currentTab === 'barang' ? 'Barang' : currentTab === 'sumber' ? 'Sumber' : 'Pemesan'}: ${detailName}`;
    let period = `Periode: ${formatDate(startDate)} - ${formatDate(endDate)}`;
    
    // Get chart images
    let chartHtml = '';
    
    if (currentTab === 'sumber') {
        // For sumber, we have two charts plus statistics
        let trendChartCanvas = document.getElementById('detail-chart');
        let summaryChartCanvas = document.getElementById('detail-summary-chart');
        
        if (trendChartCanvas && summaryChartCanvas) {
            chartHtml = `
                <div class="charts-container" style="display: flex; flex-wrap: wrap; justify-content: space-around; margin-bottom: 20px;">
                    <div style="flex: 1; min-width: 300px; max-width: 60%; margin-right: 10px;">
                        <h3>Trend Pemesanan</h3>
                        <img src="${trendChartCanvas.toDataURL('image/png')}" alt="Trend Pemesanan" style="width: 100%;">
                    </div>
                    <div style="flex: 1; min-width: 250px; max-width: 40%;">
                        <h3>Kontribusi terhadap Total</h3>
                        <img src="${summaryChartCanvas.toDataURL('image/png')}" alt="Kontribusi" style="width: 100%;">
                    </div>
                </div>
                
                <div class="summary-stats" style="margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9;">
                    <h3 style="margin-top: 0; text-align: center;">Ringkasan ${detailName}</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th style="width: 33%; text-align: center; padding: 8px; border: 1px solid #ddd;">Jumlah Pesanan</th>
                            <th style="width: 33%; text-align: center; padding: 8px; border: 1px solid #ddd;">Total Unit</th>
                            <th style="width: 33%; text-align: center; padding: 8px; border: 1px solid #ddd;">Total Pendapatan</th>
                        </tr>
                        <tr>
                            <td style="text-align: center; padding: 8px; border: 1px solid #ddd;" id="print-summary-pesanan"></td>
                            <td style="text-align: center; padding: 8px; border: 1px solid #ddd;" id="print-summary-unit"></td>
                            <td style="text-align: center; padding: 8px; border: 1px solid #ddd;" id="print-summary-pendapatan"></td>
                        </tr>
                    </table>
                </div>
            `;
        }
    } else {
        // For other tabs, we just have one chart
        let chartCanvas = document.getElementById('detail-chart');
        if (chartCanvas) {
            chartHtml = `
                <div class="chart-container" style="text-align: center; margin: 20px 0;">
                    <h3>Grafik Pemesanan</h3>
                    <img src="${chartCanvas.toDataURL('image/png')}" alt="Grafik Pemesanan" style="max-width: 100%;">
                </div>
            `;
        }
    }
    
    let table = document.getElementById('table-detail');
    
    let printContent = `
        <html>
        <head>
            <title>${title}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                table {
                    border-collapse: collapse;
                    width: 100%;
                    margin-bottom: 20px;
                }
                table, th, td {
                    border: 1px solid #ddd;
                }
                th, td {
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                h1, h2, h3 {
                    text-align: center;
                }
                .print-header {
                    margin-bottom: 20px;
                }
                .print-footer {
                    margin-top: 30px;
                    text-align: right;
                }
                .chart-container {
                    text-align: center;
                    margin: 20px 0;
                }
                .badge {
                    padding: 3px 6px;
                    border-radius: 4px;
                    font-size: 12px;
                }
                .badge-warning {
                    background-color: #ffc107;
                    color: #212529;
                }
                .badge-info {
                    background-color: #17a2b8;
                    color: white;
                }
                .badge-primary {
                    background-color: #007bff;
                    color: white;
                }
                .badge-success {
                    background-color: #28a745;
                    color: white;
                }
                .badge-danger {
                    background-color: #dc3545;
                    color: white;
                }
                .badge-secondary {
                    background-color: #6c757d;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>${title}</h1>
                <h2>${period}</h2>
            </div>
            
            ${chartHtml}
            
            <h3>Daftar Pemesanan</h3>
            <table id="print-detail-table">
                <thead>
                    <tr>
                        ${Array.from(table.querySelectorAll('thead th')).map(th => `<th>${th.textContent}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${Array.from(table.querySelectorAll('tbody tr')).map(tr => {
                        return `<tr>${Array.from(tr.querySelectorAll('td')).map(td => {
                            return `<td class="${td.className}">${td.innerHTML}</td>`;
                        }).join('')}</tr>`;
                    }).join('')}
                </tbody>
            </table>
            
            <div class="print-footer">
                <p>Dicetak pada: ${new Date().toLocaleString()}</p>
            </div>
            
            <script>
                // Fill in the summary data for sumber detail
                if (${currentTab === 'sumber'}) {
                    const statsText = document.querySelector('.col-md-12 .card-body').innerText;
                    const pesananMatch = statsText.match(/Jumlah Pesanan\\s*([\\d,\\.]+)\\s*dari\\s*([\\d,\\.]+)\\s*\\(([\\d\\.]+)%\\)/);
                    const unitMatch = statsText.match(/Total Unit\\s*([\\d,\\.]+)\\s*dari\\s*([\\d,\\.]+)\\s*\\(([\\d\\.]+)%\\)/);
                    const pendapatanMatch = statsText.match(/Total Pendapatan\\s*([\\S\\s]+)\\s*dari\\s*([\\S\\s]+)\\s*\\(([\\d\\.]+)%\\)/);
                    
                    if (pesananMatch) {
                        document.getElementById('print-summary-pesanan').innerText = 
                            pesananMatch[1] + ' dari ' + pesananMatch[2] + ' (' + pesananMatch[3] + '%)';
                    }
                    if (unitMatch) {
                        document.getElementById('print-summary-unit').innerText = 
                            unitMatch[1] + ' dari ' + unitMatch[2] + ' (' + unitMatch[3] + '%)';
                    }
                    if (pendapatanMatch) {
                        document.getElementById('print-summary-pendapatan').innerText = 
                            pendapatanMatch[1] + ' dari ' + pendapatanMatch[2] + ' (' + pendapatanMatch[3] + '%)';
                    }
                }
            </script>
        </body>
        </html>
    `;
    
    printContentInNewWindow(printContent);
}

/**
 * Helper function to print content in new window
 */
function printContentInNewWindow(content) {
    let printWindow = window.open('', '_blank');
    printWindow.document.write(content);
    printWindow.document.close();
    
    // Wait for images to load
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.onafterprint = function() {
            printWindow.close();
        };
    };
}

/**
 * Helper function to format currency
 */
function formatRupiah(angka) {
    let reverse = angka.toString().split('').reverse().join('');
    let ribuan = reverse.match(/\d{1,3}/g);
    let formatted = ribuan.join('.').split('').reverse().join('');
    
    return `Rp ${formatted}`;
}

/**
 * Helper function to format date
 */
function formatDate(dateString) {
    let date = new Date(dateString);
    let day = date.getDate().toString().padStart(2, '0');
    let month = (date.getMonth() + 1).toString().padStart(2, '0');
    let year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
}
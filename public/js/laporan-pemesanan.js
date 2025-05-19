/**
 * Javascript untuk Laporan Pemesanan
 */

// DataTable Instances
let tableBarang, tableSumber, tablePemesan, tableDetail;
let detailChart = null;

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
    
    // Load data awal dan set periode display
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
    
    // Catatan button click handler
    $(document).on('click', '.catatan-btn', function() {
        let tipe = $(this).data('tipe');
        let id = $(this).data('id');
        let nama = $(this).data('nama');
        let catatan = $(this).data('catatan');
        
        $('#modalCatatanTitle').text('Catatan untuk ' + nama);
        $('#catatan-tipe').val(tipe);
        $('#catatan-id').val(id);
        $('#catatan').val(catatan);
        
        $('#modal-catatan').modal('show');
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
    let bulanText = $('#bulan option:selected').text();
    let tahunText = $('#tahun option:selected').text();
    let periodeText = '';
    
    if (currentPeriode === '1_bulan') {
        periodeText = `${bulanText} ${tahunText}`;
    } else if (currentPeriode === '6_bulan') {
        // Calculate 6 months period
        let month = parseInt(currentBulan);
        let year = parseInt(currentTahun);
        
        let startMonth = month - 5;
        let startYear = year;
        
        if (startMonth <= 0) {
            startMonth += 12;
            startYear -= 1;
        }
        
        let monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        periodeText = `${monthNames[startMonth-1]} ${startYear} - ${bulanText} ${tahunText}`;
    } else { // 1_tahun
        // Calculate 1 year period
        let month = parseInt(currentBulan);
        let year = parseInt(currentTahun);
        
        let startMonth = month;
        let startYear = year - 1;
        
        let monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        periodeText = `${monthNames[startMonth-1]} ${startYear} - ${bulanText} ${tahunText}`;
    }
    
    $('#periode-display').text(periodeText);
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
                        break;
                    case 'sumber':
                        tableSumber.clear().rows.add(response.data).draw();
                        $('#sumber-total-pesanan').text(response.total.pesanan);
                        $('#sumber-total-unit').text(response.total.unit);
                        $('#sumber-total-pendapatan').text(formatRupiah(response.total.pendapatan));
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

/**
 * Load detail data for modal
 */
function loadDetailData(tipe, id) {
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
        },
        success: function(response) {
            if (response.success) {
                // Update table
                tableDetail.clear().rows.add(response.data).draw();
                
                // Update chart
                updateDetailChart(response.chart_data);
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
                text: 'Gagal memuat data detail'
            });
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
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Pendapatan (Rp)',
                    data: totalData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1'
                },
                {
                    label: 'Jumlah Pesanan',
                    data: countData,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y2'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
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
                            return formatRupiah(value);
                        }
                    }
                },
                y2: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Jumlah Pesanan'
                    },
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
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
    
    switch (tipe) {
        case 'barang':
            table = document.getElementById('table-barang');
            title = 'Laporan Pemesanan Per Barang';
            break;
        case 'sumber':
            table = document.getElementById('table-sumber');
            title = 'Laporan Pemesanan Per Sumber';
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
                h1, h2 {
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
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>${title}</h1>
                <h2>${period}</h2>
            </div>
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
    
    // Create chart image
    let chartCanvas = document.getElementById('detail-chart');
    let chartImage = chartCanvas.toDataURL('image/png');
    
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
            
            <div class="chart-container">
                <img src="${chartImage}" alt="Grafik Pemesanan" style="max-width: 100%;">
            </div>
            
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
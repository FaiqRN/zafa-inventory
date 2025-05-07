$(function() {
    'use strict';
    
    // Initialize DataTables
    let dataTable1, dataTable6, dataTableYear;
    
    // Initialize tabs
    $('#reportTabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
        
        const tabId = $(this).attr('id');
        
        // Sync periode dropdown with selected tab
        if (tabId.includes('bulan-1')) {
            $('#periode').val('1_bulan');
        } else if (tabId.includes('bulan-6')) {
            $('#periode').val('6_bulan');
        } else { // tahun-1
            $('#periode').val('1_tahun');
        }
        
        // Load data for the selected period
        loadData();
    });
    
    // Initial load
    loadData();
    
    // Handle Filter button
    $('#btn-filter').on('click', function() {
        loadData();
    });
    function loadData() {
        const periode = $('#periode').val();
        const bulan = $('#bulan').val();
        const tahun = $('#tahun').val();
        
        // Sync tabs with periode dropdown
        let tabId;
        if (periode === '1_bulan') {
            tabId = '#bulan-1-tab';
        } else if (periode === '6_bulan') {
            tabId = '#bulan-6-tab';
        } else { // 1_tahun
            tabId = '#tahun-1-tab';
        }
        
        $(tabId).tab('show');
        
        // Update periode display
        const periodeDisplay = getPeriodeLabel();
        $('#periode-display').text(periodeDisplay);
        
        // Show loading
        Swal.fire({
            title: 'Loading...',
            text: 'Sedang mengambil data',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '/laporan-toko/data',
            type: 'GET',
            data: {
                periode: periode,
                bulan: bulan,
                tahun: tahun
            },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                // Update summary stats
                $('#summary-toko').text(response.summary.totalToko);
                $('#summary-penjualan').text(formatRupiah(response.summary.totalPenjualan));
                $('#summary-pengiriman').text(response.summary.totalPengiriman);
                $('#summary-retur').text(response.summary.totalRetur);
                
                // Update table
                updateTable(response.data, periode);
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error fetching data:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data.',
                    icon: 'error'
                });
            }
        });
    }
    
    function updateTable(data, periode) {
        const tableId = periode === '1_bulan' ? '#tabel-laporan-1' : 
                        periode === '6_bulan' ? '#tabel-laporan-6' : '#tabel-laporan-tahun';
        
        // If DataTable already initialized, destroy it
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        
        // Clear export button container
        const exportContainerId = periode === '1_bulan' ? '#export-btn-container-1' : 
                                 periode === '6_bulan' ? '#export-btn-container-6' : '#export-btn-container-tahun';
        $(exportContainerId).empty();
        
        // Add print button to container
        const printBtn = $(`<button class="btn btn-info mr-2"><i class="fas fa-print"></i> Print</button>`);
        printBtn.on('click', function() {
            printReport(data, periode);
        });
        $(exportContainerId).append(printBtn);
        
        if (data.length === 0) {
            // Show empty state message
            const emptyState = $(`
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle"></i>
                    Tidak ada data untuk periode ini.
                </div>
            `);
            
            $(tableId).parent().before(emptyState);
            // Initialize empty table
            $(tableId).DataTable({
                responsive: true,
                lengthChange: true,
                autoWidth: false
            });
            return;
        }
        
        const table = $(tableId).DataTable({
            data: data,
            columns: [
                { 
                    data: null, 
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { data: 'nama_toko' },
                { data: 'pemilik' },
                { 
                    data: 'total_penjualan',
                    render: function(data, type, row) {
                        return formatRupiah(data);
                    }
                },
                { data: 'total_pengiriman' },
                { data: 'total_retur' },
                { 
                    data: 'catatan',
                    render: function(data, type, row) {
                        if (data && data.length > 50) {
                            return data.substring(0, 50) + '...';
                        }
                        return data || '-';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info btn-edit-catatan" 
                                        data-toko-id="${row.toko_id}" 
                                        data-nama-toko="${row.nama_toko}"
                                        data-catatan="${row.catatan || ''}">
                                    <i class="fas fa-edit"></i> Catatan
                                </button>
                                <button class="btn btn-sm btn-primary btn-detail" 
                                        data-toko-id="${row.toko_id}" 
                                        data-nama-toko="${row.nama_toko}">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </div>`;
                    }
                }
            ],
            order: [[1, 'asc']],
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            }
        });
        
        // Register event for edit catatan buttons
        $(tableId).on('click', '.btn-edit-catatan', function() {
            const toko_id = $(this).data('toko-id');
            const nama_toko = $(this).data('nama-toko');
            const catatan = $(this).data('catatan');
            
            $('#nama-toko').text(nama_toko);
            $('#toko_id').val(toko_id);
            $('#catatan').val(catatan);
            $('#catatan_periode').val(periode);
            $('#catatan_bulan').val($('#bulan').val());
            $('#catatan_tahun').val($('#tahun').val());
            
            $('#modalCatatan').modal('show');
        });
        
        // Register event for detail button
        $(tableId).on('click', '.btn-detail', function() {
            const toko_id = $(this).data('toko-id');
            const nama_toko = $(this).data('nama-toko');
            
            showDetailModal(toko_id, nama_toko);
        });
    }
    
    // Handle saving catatan
    $('#btn-simpan-catatan').on('click', function() {
        const toko_id = $('#toko_id').val();
        const periode = $('#catatan_periode').val();
        const bulan = $('#catatan_bulan').val();
        const tahun = $('#catatan_tahun').val();
        const catatan = $('#catatan').val();
        
        // Show loading
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Sedang menyimpan catatan',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '/laporan-toko/update-catatan',
            type: 'POST',
            data: {
                toko_id: toko_id,
                periode: periode,
                bulan: bulan,
                tahun: tahun,
                catatan: catatan,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.success) {
                    $('#modalCatatan').modal('hide');
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Catatan berhasil disimpan.',
                        icon: 'success',
                        timer: 1500
                    });
                    loadData();
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error saving catatan:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal menyimpan catatan.',
                    icon: 'error'
                });
            }
        });
    });
    
    function showDetailModal(toko_id, nama_toko) {
        // Show loading state
        Swal.fire({
            title: 'Loading...',
            text: 'Mengambil data detail toko',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '/laporan-toko/detail',
            type: 'GET',
            data: {
                toko_id: toko_id,
                periode: $('#periode').val(),
                bulan: $('#bulan').val(),
                tahun: $('#tahun').val()
            },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                // Prepare the modal content
                let modalContent = `
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Laporan: ${nama_toko}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informasi Toko</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>ID Toko</th>
                                        <td>${response.toko.toko_id}</td>
                                    </tr>
                                    <tr>
                                        <th>Nama Toko</th>
                                        <td>${response.toko.nama_toko}</td>
                                    </tr>
                                    <tr>
                                        <th>Pemilik</th>
                                        <td>${response.toko.pemilik}</td>
                                    </tr>
                                    <tr>
                                        <th>Alamat</th>
                                        <td>${response.toko.alamat}</td>
                                    </tr>
                                    <tr>
                                        <th>Telepon</th>
                                        <td>${response.toko.nomer_telpon}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Detail Penjualan Per Barang</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nama Barang</th>
                                                <th>Satuan</th>
                                                <th>Harga</th>
                                                <th>Total Kirim</th>
                                                <th>Total Retur</th>
                                                <th>Total Terjual</th>
                                                <th>Total Penjualan</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                
                let totalAllPenjualan = 0;
                                        
                if (response.detailPenjualan.length > 0) {
                    response.detailPenjualan.forEach(item => {
                        totalAllPenjualan += parseFloat(item.total_penjualan);
                        
                        modalContent += `
                            <tr>
                                <td>${item.nama_barang}</td>
                                <td>${item.satuan}</td>
                                <td>${formatRupiah(item.harga_awal_barang)}</td>
                                <td>${item.total_kirim}</td>
                                <td>${item.total_retur}</td>
                                <td>${item.total_terjual}</td>
                                <td>${formatRupiah(item.total_penjualan)}</td>
                            </tr>`;
                    });
                } else {
                    modalContent += `
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data penjualan</td>
                        </tr>`;
                }
                
                modalContent += `
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="6" class="text-right">Total Seluruh Penjualan:</th>
                                                <th>${formatRupiah(totalAllPenjualan)}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Riwayat Pengiriman</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>No. Pengiriman</th>
                                                <th>Tanggal</th>
                                                <th>Nama Barang</th>
                                                <th>Jumlah Kirim</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                                        
                if (response.pengiriman.length > 0) {
                    response.pengiriman.forEach(item => {
                        let statusClass = '';
                        
                        if (item.status === 'terkirim') {
                            statusClass = 'badge-success';
                        } else if (item.status === 'proses') {
                            statusClass = 'badge-warning';
                        } else {
                            statusClass = 'badge-danger';
                        }
                        
                        modalContent += `
                            <tr>
                                <td>${item.nomer_pengiriman}</td>
                                <td>${formatDate(item.tanggal_pengiriman)}</td>
                                <td>${item.nama_barang}</td>
                                <td>${item.jumlah_kirim}</td>
                                <td><span class="badge ${statusClass}">${item.status}</span></td>
                            </tr>`;
                    });
                } else {
                    modalContent += `
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data pengiriman</td>
                        </tr>`;
                }
                
                modalContent += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Visualisasi Penjualan</h6>
                                <div class="chart-container" style="position: relative; height:300px;">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="btn-print-detail">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>`;
                
                // Create and show the modal
                if ($('#detailModal').length) {
                    $('#detailModal').remove();
                }
                
                const modal = $('<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"></div></div></div>');
                
                modal.find('.modal-content').html(modalContent);
                $('body').append(modal);
                
                $('#detailModal').modal('show');
                
                // After modal is shown, initialize the chart
                $('#detailModal').on('shown.bs.modal', function () {
                    // Prepare data for chart
                    const labels = response.detailPenjualan.map(item => item.nama_barang);
                    const totalTerjual = response.detailPenjualan.map(item => item.total_terjual);
                    const totalPenjualan = response.detailPenjualan.map(item => item.total_penjualan);
                    
                    // Initialize chart
                    const ctx = document.getElementById('salesChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Jumlah Terjual',
                                    data: totalTerjual,
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Total Penjualan (Rp)',
                                    data: totalPenjualan,
                                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1,
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Jumlah Terjual'
                                    }
                                },
                                y1: {
                                    position: 'right',
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Total Penjualan (Rp)'
                                    },
                                    // Grid line settings
                                    grid: {
                                        drawOnChartArea: false // only want the grid lines for one axis to show up
                                    },
                                    ticks: {
                                        callback: function(value, index, values) {
                                            if (value >= 1000000) {
                                                return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                                            } else if (value >= 1000) {
                                                return 'Rp ' + (value / 1000).toFixed(1) + ' Rb';
                                            }
                                            return 'Rp ' + value;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                    // Add print handler
                    $('#btn-print-detail').on('click', function() {
                        printDetailReport(response, nama_toko);
                    });
                });
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error fetching detail data:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data detail.',
                    icon: 'error'
                });
            }
        });
    }
    
    function printDetailReport(data, nama_toko) {
        const printWindow = window.open('', '_blank');
        
        // Create print content
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Detail Laporan: ${nama_toko}</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
                <style>
                    body { padding: 20px; }
                    .page-header { margin-bottom: 30px; }
                    table { width: 100%; margin-bottom: 20px; }
                    th, td { padding: 8px; border: 1px solid #ddd; }
                    th { background-color: #f2f2f2; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                </style>
            </head>
            <body>
                <div class="page-header text-center">
                    <h3>Detail Laporan Toko</h3>
                    <h4>${nama_toko}</h4>
                    <p>Periode: ${getPeriodeLabel()}</p>
                </div>
                
                <h5>Informasi Toko</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">ID Toko</th>
                        <td>${data.toko.toko_id}</td>
                    </tr>
                    <tr>
                        <th>Nama Toko</th>
                        <td>${data.toko.nama_toko}</td>
                    </tr>
                    <tr>
                        <th>Pemilik</th>
                        <td>${data.toko.pemilik}</td>
                    </tr>
                    <tr>
                        <th>Alamat</th>
                        <td>${data.toko.alamat}</td>
                    </tr>
                    <tr>
                        <th>Telepon</th>
                        <td>${data.toko.nomer_telpon}</td>
                    </tr>
                </table>
                
                <h5>Detail Penjualan Per Barang</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Total Kirim</th>
                            <th>Total Retur</th>
                            <th>Total Terjual</th>
                            <th>Total Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        let totalAllPenjualan = 0;
        
        if (data.detailPenjualan.length > 0) {
            data.detailPenjualan.forEach((item, index) => {
                totalAllPenjualan += parseFloat(item.total_penjualan);
                
                printContent += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nama_barang}</td>
                        <td>${item.satuan}</td>
                        <td>${formatRupiah(item.harga_awal_barang)}</td>
                        <td>${item.total_kirim}</td>
                        <td>${item.total_retur}</td>
                        <td>${item.total_terjual}</td>
                        <td>${formatRupiah(item.total_penjualan)}</td>
                    </tr>`;
            });
        } else {
            printContent += `
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data penjualan</td>
                </tr>`;
        }
        
        printContent += `
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-right">Total Seluruh Penjualan:</th>
                            <th>${formatRupiah(totalAllPenjualan)}</th>
                        </tr>
                    </tfoot>
                </table>
                
                <h5>Riwayat Pengiriman</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pengiriman</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Jumlah Kirim</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;
                    
        if (data.pengiriman.length > 0) {
            data.pengiriman.forEach((item, index) => {
                printContent += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nomer_pengiriman}</td>
                        <td>${formatDate(item.tanggal_pengiriman)}</td>
                        <td>${item.nama_barang}</td>
                        <td>${item.jumlah_kirim}</td>
                        <td>${item.status}</td>
                    </tr>`;
            });
        } else {
            printContent += `
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data pengiriman</td>
                </tr>`;
        }
        
        printContent += `
                    </tbody>
                </table>
                
                <div class="text-center mt-5">
                    <p>Laporan ini dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                    }
                </script>
            </body>
            </html>`;
        
        printWindow.document.write(printContent);
        printWindow.document.close();
    }
    
    function printReport(data, periode) {
        const printWindow = window.open('', '_blank');
        
        // Create print content
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Laporan Toko - ${getPeriodeLabel()}</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
                <style>
                    body { padding: 20px; }
                    .page-header { margin-bottom: 30px; }
                    table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
                    th, td { padding: 8px; border: 1px solid #ddd; }
                    th { background-color: #f2f2f2; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                </style>
            </head>
            <body>
                <div class="page-header text-center">
                    <h3>Laporan Per Toko</h3>
                    <p>Periode: ${getPeriodeLabel()}</p>
                </div>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Toko</th>
                            <th>Pemilik</th>
                            <th>Total Penjualan</th>
                            <th>Total Pengiriman</th>
                            <th>Total Retur</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        if (data.length > 0) {
            let totalPenjualan = 0;
            let totalPengiriman = 0;
            let totalRetur = 0;
            
            data.forEach((item, index) => {
                totalPenjualan += parseFloat(item.total_penjualan);
                totalPengiriman += parseInt(item.total_pengiriman);
                totalRetur += parseInt(item.total_retur);
                
                printContent += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nama_toko}</td>
                        <td>${item.pemilik}</td>
                        <td>${formatRupiah(item.total_penjualan)}</td>
                        <td>${item.total_pengiriman}</td>
                        <td>${item.total_retur}</td>
                        <td>${item.catatan || '-'}</td>
                    </tr>`;
            });
            
            printContent += `
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total:</th>
                        <th>${formatRupiah(totalPenjualan)}</th>
                        <th>${totalPengiriman}</th>
                        <th>${totalRetur}</th>
                        <th></th>
                    </tr>
                </tfoot>`;
        } else {
            printContent += `
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data</td>
                </tr>
                </tbody>`;
        }
        
        printContent += `
                </table>
                
                <div class="text-center mt-5">
                    <p>Laporan ini dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                    }
                </script>
            </body>
            </html>`;
        
        printWindow.document.write(printContent);
        printWindow.document.close();
    }
    
    // Helper function to get formatted period label
    function getPeriodeLabel() {
        const periode = $('#periode').val();
        const bulan = $('#bulan').val();
        const tahun = $('#tahun').val();
        
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        if (periode === '1_bulan') {
            return `${monthNames[bulan - 1]} ${tahun}`;
        } else if (periode === '6_bulan') {
            // Calculate 6 months back
            let startMonth = parseInt(bulan) - 5;
            let startYear = parseInt(tahun);
            
            if (startMonth <= 0) {
                startMonth = 12 + startMonth;
                startYear = startYear - 1;
            }
            
            return `${monthNames[startMonth - 1]} ${startYear} - ${monthNames[bulan - 1]} ${tahun}`;
        } else { // 1_tahun
            // Calculate 1 year back
            let startMonth = bulan;
            let startYear = parseInt(tahun) - 1;
            
            return `${monthNames[startMonth - 1]} ${startYear} - ${monthNames[bulan - 1]} ${tahun}`;
        }
    }
    
    // Format number to Rupiah
    function formatRupiah(angka) {
        if (angka === null || angka === undefined || isNaN(angka)) return 'Rp 0';
        
        // Convert to number if it's a string
        const number = typeof angka === 'string' ? parseFloat(angka) : angka;
        
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    }
    
    // Format date
    function formatDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        }).format(date);
    }
});
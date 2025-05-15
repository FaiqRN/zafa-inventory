// public/js/laporan-pemesanan.js
$(function() {
    'use strict';
    
    // Tambah CSRF token untuk semua request AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
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
            url: '/laporan-pemesanan/data',
            type: 'GET',
            data: {
                periode: periode,
                bulan: bulan,
                tahun: tahun
            },
            dataType: 'json',
            success: function(response) {
                console.log('Response data:', response);
                
                Swal.close();
                
                // Update summary stats
                $('#summary-total').text(response.summary.totalPemesanan);
                $('#summary-nilai').text(formatRupiah(response.summary.totalNilai));
                $('#summary-selesai').text(response.summary.totalSelesai);
                $('#summary-cancel').text(response.summary.totalCancel);
                
                // Update table
                updateTable(response.data, periode);
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error fetching data:', error);
                console.error('Response:', xhr.responseText);
                
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data. Silakan coba lagi.',
                    icon: 'error'
                });
            }
        });
    }
    
    function updateTable(data, periode) {
        const tableId = periode === '1_bulan' ? '#tabel-pemesanan-1' : 
                      periode === '6_bulan' ? '#tabel-pemesanan-6' : '#tabel-pemesanan-tahun';
        
        // If DataTable already initialized, destroy it
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        
        // Clear export button container
        const exportContainerId = periode === '1_bulan' ? '#export-btn-container-1' : 
                               periode === '6_bulan' ? '#export-btn-container-6' : '#export-btn-container-tahun';
        $(exportContainerId).empty();
        
        // Add print and export buttons
        const printBtn = $(`<button class="btn btn-info mr-2"><i class="fas fa-print"></i> Print</button>`);
        printBtn.on('click', function() {
            printReport(data, periode);
        });
        $(exportContainerId).append(printBtn);
        
        const csvBtn = $(`<button class="btn btn-success mr-2"><i class="fas fa-file-csv"></i> Export CSV</button>`);
        csvBtn.on('click', function() {
            exportCsv(periode);
        });
        $(exportContainerId).append(csvBtn);
        
        // Initialize DataTable
        $(tableId).DataTable({
            data: data,
            columns: [
                { 
                    data: null, 
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { data: 'pemesanan_id' },
                { 
                    data: 'tanggal_pemesanan',
                    render: function(data) {
                        return formatDate(data);
                    }
                },
                { data: 'nama_pemesan' },
                { data: 'nama_barang' }, // Perlu join dengan tabel barang
                { data: 'jumlah_pesanan' },
                { 
                    data: 'total',
                    render: function(data) {
                        return formatRupiah(data);
                    }
                },
                { data: 'pemesanan_dari' },
                { data: 'metode_pembayaran' },
                { 
                    data: 'status_pemesanan',
                    render: function(data) {
                        let badgeClass = '';
                        
                        if (data === 'selesai') {
                            badgeClass = 'badge-selesai';
                        } else if (data === 'dikirim') {
                            badgeClass = 'badge-dikirim';
                        } else if (data === 'diproses') {
                            badgeClass = 'badge-diproses';
                        } else if (data === 'pending') {
                            badgeClass = 'badge-pending';
                        } else if (data === 'dibatalkan') {
                            badgeClass = 'badge-dibatalkan';
                        }
                        
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info btn-edit-catatan" 
                                        data-pemesanan-id="${row.pemesanan_id}" 
                                        data-catatan="${row.catatan || ''}">
                                    <i class="fas fa-edit"></i> Catatan
                                </button>
                                <button class="btn btn-sm btn-primary btn-detail" 
                                        data-pemesanan-id="${row.pemesanan_id}">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </div>`;
                    }
                }
            ],
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            },
            drawCallback: function() {
                // Re-attach event handlers after table redraws
                attachEventHandlers();
            }
        });
    }
    
    function attachEventHandlers() {
        // Register event for catatan buttons
        $('.btn-edit-catatan').off('click').on('click', function() {
            const pemesanan_id = $(this).data('pemesanan-id');
            const catatan = $(this).data('catatan');
            
            $('#id-pemesanan-catatan').text(pemesanan_id);
            $('#pemesanan_id').val(pemesanan_id);
            $('#catatan').val(catatan);
            $('#catatan_periode').val($('#periode').val());
            $('#catatan_bulan').val($('#bulan').val());
            $('#catatan_tahun').val($('#tahun').val());
            
            $('#modalCatatan').modal('show');
        });
        
        // Register event for detail button
        $('.btn-detail').off('click').on('click', function() {
            const pemesanan_id = $(this).data('pemesanan-id');
            
            showDetailModal(pemesanan_id);
        });
    }
    
    // Handle saving catatan
    $('#btn-simpan-catatan').on('click', function() {
        const pemesanan_id = $('#pemesanan_id').val();
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
            url: '/laporan-pemesanan/update-catatan',
            type: 'POST',
            data: {
                pemesanan_id: pemesanan_id,
                periode: periode,
                bulan: bulan,
                tahun: tahun,
                catatan: catatan
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
                console.error('Response:', xhr.responseText);
                
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal menyimpan catatan. Silakan coba lagi.',
                    icon: 'error'
                });
            }
        });
    });
    
    function showDetailModal(pemesanan_id) {
        // Show loading state
// Show loading state
       Swal.fire({
           title: 'Loading...',
           text: 'Mengambil data detail pemesanan',
           allowOutsideClick: false,
           didOpen: () => {
               Swal.showLoading();
           }
       });
       
       $.ajax({
           url: '/laporan-pemesanan/detail',
           type: 'GET',
           data: {
               pemesanan_id: pemesanan_id
           },
           dataType: 'json',
           success: function(response) {
               console.log('Detail data:', response);
               
               Swal.close();
               
               if (response.pemesanan) {
                   const pemesanan = response.pemesanan;
                   
                   // Update modal title and content
                   $('#id-pemesanan').text(pemesanan.pemesanan_id);
                   
                   // Fill in information
                   $('#detail-id').text(pemesanan.pemesanan_id);
                   $('#detail-tanggal').text(formatDate(pemesanan.tanggal_pemesanan));
                   $('#detail-sumber').text(pemesanan.pemesanan_dari);
                   $('#detail-pembayaran').text(pemesanan.metode_pembayaran);
                   
                   // Set status with badge
                   let statusBadge = '';
                   if (pemesanan.status_pemesanan === 'selesai') {
                       statusBadge = `<span class="badge badge-success">${pemesanan.status_pemesanan}</span>`;
                   } else if (pemesanan.status_pemesanan === 'dikirim') {
                       statusBadge = `<span class="badge badge-primary">${pemesanan.status_pemesanan}</span>`;
                   } else if (pemesanan.status_pemesanan === 'diproses') {
                       statusBadge = `<span class="badge badge-info">${pemesanan.status_pemesanan}</span>`;
                   } else if (pemesanan.status_pemesanan === 'pending') {
                       statusBadge = `<span class="badge badge-warning">${pemesanan.status_pemesanan}</span>`;
                   } else if (pemesanan.status_pemesanan === 'dibatalkan') {
                       statusBadge = `<span class="badge badge-danger">${pemesanan.status_pemesanan}</span>`;
                   }
                   $('#detail-status').html(statusBadge);
                   
                   // Fill in customer information
                   $('#detail-nama').text(pemesanan.nama_pemesan);
                   $('#detail-alamat').text(pemesanan.alamat_pemesan);
                   $('#detail-telp').text(pemesanan.no_telp_pemesan);
                   $('#detail-email').text(pemesanan.email_pemesan);
                   
                   // Fill in product information
                   $('#detail-barang-id').text(pemesanan.barang_id);
                   $('#detail-barang-nama').text(pemesanan.nama_barang);
                   $('#detail-barang-harga').text(formatRupiah(pemesanan.harga_awal_barang));
                   $('#detail-barang-jumlah').text(pemesanan.jumlah_pesanan);
                   $('#detail-barang-total').text(formatRupiah(pemesanan.total));
                   
                   // Fill in notes
                   $('#detail-catatan').text(pemesanan.catatan_pemesanan || '-');
                   
                   // Show modal
                   $('#detailModal').modal('show');
                   
                   // Setup print and export buttons
                   $('#btn-print-detail').off('click').on('click', function() {
                       printDetailReport(pemesanan);
                   });
                   
                   $('#btn-export-detail-csv').off('click').on('click', function() {
                       exportDetailCsv(pemesanan_id);
                   });
               }
           },
           error: function(xhr, status, error) {
               Swal.close();
               console.error('Error fetching detail data:', error);
               console.error('Response:', xhr.responseText);
               
               Swal.fire({
                   title: 'Error!',
                   text: 'Gagal memuat data detail. Silakan coba lagi.',
                   icon: 'error'
               });
           }
       });
   }
   
   function printDetailReport(pemesanan) {
       const printWindow = window.open('', '_blank');
       
       let printContent = `
           <!DOCTYPE html>
           <html>
           <head>
               <title>Detail Pemesanan: ${pemesanan.pemesanan_id}</title>
               <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
               <style>
                   body { 
                       padding: 20px; 
                       font-family: Arial, sans-serif;
                   }
                   .page-header { 
                       margin-bottom: 30px; 
                       border-bottom: 1px solid #dee2e6;
                       padding-bottom: 10px;
                   }
                   table { 
                       width: 100%; 
                       margin-bottom: 20px; 
                       border-collapse: collapse;
                   }
                   th, td { 
                       padding: 8px; 
                       border: 1px solid #dee2e6; 
                   }
                   th { 
                       background-color: #f8f9fa; 
                   }
                   .text-right { 
                       text-align: right; 
                   }
                   .text-center { 
                       text-align: center; 
                   }
                   h5 {
                       margin-top: 20px;
                       margin-bottom: 10px;
                       font-weight: bold;
                   }
                   .footer {
                       margin-top: 30px;
                       text-align: center;
                       font-size: 12px;
                       color: #6c757d;
                   }
               </style>
           </head>
           <body>
               <div class="page-header text-center">
                   <h3>Detail Pemesanan</h3>
                   <h4>${pemesanan.pemesanan_id}</h4>
                   <p>Tanggal: ${formatDate(pemesanan.tanggal_pemesanan)}</p>
               </div>
               
               <div class="row">
                   <div class="col-6">
                       <h5>Informasi Pemesanan</h5>
                       <table class="table table-bordered">
                           <tr>
                               <th width="40%">ID Pemesanan</th>
                               <td>${pemesanan.pemesanan_id}</td>
                           </tr>
                           <tr>
                               <th>Tanggal Pemesanan</th>
                               <td>${formatDate(pemesanan.tanggal_pemesanan)}</td>
                           </tr>
                           <tr>
                               <th>Sumber Pemesanan</th>
                               <td>${pemesanan.pemesanan_dari}</td>
                           </tr>
                           <tr>
                               <th>Metode Pembayaran</th>
                               <td>${pemesanan.metode_pembayaran}</td>
                           </tr>
                           <tr>
                               <th>Status</th>
                               <td>${pemesanan.status_pemesanan}</td>
                           </tr>
                       </table>
                   </div>
                   
                   <div class="col-6">
                       <h5>Informasi Pemesan</h5>
                       <table class="table table-bordered">
                           <tr>
                               <th width="40%">Nama</th>
                               <td>${pemesanan.nama_pemesan}</td>
                           </tr>
                           <tr>
                               <th>Alamat</th>
                               <td>${pemesanan.alamat_pemesan}</td>
                           </tr>
                           <tr>
                               <th>No. Telepon</th>
                               <td>${pemesanan.no_telp_pemesan}</td>
                           </tr>
                           <tr>
                               <th>Email</th>
                               <td>${pemesanan.email_pemesan}</td>
                           </tr>
                       </table>
                   </div>
               </div>
               
               <h5>Detail Barang</h5>
               <table class="table table-bordered">
                   <thead>
                       <tr>
                           <th>ID Barang</th>
                           <th>Nama Barang</th>
                           <th>Harga Satuan</th>
                           <th>Jumlah</th>
                           <th>Total</th>
                       </tr>
                   </thead>
                   <tbody>
                       <tr>
                           <td>${pemesanan.barang_id}</td>
                           <td>${pemesanan.nama_barang}</td>
                           <td>${formatRupiah(pemesanan.harga_awal_barang)}</td>
                           <td>${pemesanan.jumlah_pesanan}</td>
                           <td>${formatRupiah(pemesanan.total)}</td>
                       </tr>
                   </tbody>
                   <tfoot>
                       <tr>
                           <th colspan="4" class="text-right">Total:</th>
                           <th>${formatRupiah(pemesanan.total)}</th>
                       </tr>
                   </tfoot>
               </table>
               
               <h5>Catatan</h5>
               <div class="card">
                   <div class="card-body">
                       ${pemesanan.catatan_pemesanan || '-'}
                   </div>
               </div>
               
               <div class="footer">
                   <p>Laporan ini dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                   <p>ZafaSys © ${new Date().getFullYear()}</p>
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
       
       let printContent = `
           <!DOCTYPE html>
           <html>
           <head>
               <title>Laporan Pemesanan - ${getPeriodeLabel()}</title>
               <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
               <style>
                   body { 
                       padding: 20px; 
                       font-family: Arial, sans-serif;
                   }
                   .page-header { 
                       margin-bottom: 30px; 
                       border-bottom: 1px solid #dee2e6;
                       padding-bottom: 10px;
                   }
                   table { 
                       width: 100%; 
                       margin-bottom: 20px; 
                       border-collapse: collapse; 
                   }
                   th, td { 
                       padding: 8px; 
                       border: 1px solid #dee2e6; 
                   }
                   th { 
                       background-color: #f8f9fa; 
                   }
                   .text-right { 
                       text-align: right; 
                   }
                   .text-center { 
                       text-align: center; 
                   }
                   .footer {
                       margin-top: 30px;
                       text-align: center;
                       font-size: 12px;
                       color: #6c757d;
                   }
               </style>
           </head>
           <body>
               <div class="page-header text-center">
                   <h3>Laporan Pemesanan</h3>
                   <p>Periode: ${getPeriodeLabel()}</p>
               </div>
               
               <table class="table table-bordered">
                   <thead>
                       <tr>
                           <th>No</th>
                           <th>ID Pemesanan</th>
                           <th>Tanggal</th>
                           <th>Nama Pemesan</th>
                           <th>Barang</th>
                           <th>Jumlah</th>
                           <th>Total</th>
                           <th>Sumber</th>
                           <th>Status</th>
                       </tr>
                   </thead>
                   <tbody>`;
       
       if (data && data.length > 0) {
           let totalNilai = 0;
           
           data.forEach((item, index) => {
               totalNilai += parseFloat(item.total || 0);
               
               printContent += `
                   <tr>
                       <td>${index + 1}</td>
                       <td>${item.pemesanan_id}</td>
                       <td>${formatDate(item.tanggal_pemesanan)}</td>
                       <td>${item.nama_pemesan}</td>
                       <td>${item.nama_barang}</td>
                       <td>${item.jumlah_pesanan}</td>
                       <td>${formatRupiah(item.total)}</td>
                       <td>${item.pemesanan_dari}</td>
                       <td>${item.status_pemesanan}</td>
                   </tr>`;
           });
           
           printContent += `
               </tbody>
               <tfoot>
                   <tr>
                       <th colspan="6" class="text-right">Total:</th>
                       <th>${formatRupiah(totalNilai)}</th>
                       <th colspan="2"></th>
                   </tr>
               </tfoot>`;
       } else {
           printContent += `
               <tr>
                   <td colspan="9" class="text-center">Tidak ada data</td>
               </tr>
               </tbody>`;
       }
       
       printContent += `
               </table>
               
               <div class="footer">
                   <p>Laporan ini dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                   <p>ZafaSys © ${new Date().getFullYear()}</p>
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
       const bulan = parseInt($('#bulan').val());
       const tahun = parseInt($('#tahun').val());
       
       const monthNames = [
           'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
           'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
       ];
       
       if (periode === '1_bulan') {
           return `${monthNames[bulan - 1]} ${tahun}`;
       } else if (periode === '6_bulan') {
           // Calculate 6 months back
           let startMonth = bulan - 5;
           let startYear = tahun;
           
           if (startMonth <= 0) {
               startMonth = 12 + startMonth;
               startYear = tahun - 1;
           }
           
           return `${monthNames[startMonth - 1]} ${startYear} - ${monthNames[bulan - 1]} ${tahun}`;
       } else { // 1_tahun
           // Calculate 1 year back
           let startMonth = bulan;
           let startYear = tahun - 1;
           
           return `${monthNames[startMonth - 1]} ${startYear} - ${monthNames[bulan - 1]} ${tahun}`;
       }
   }
   
   // Format number to Rupiah
   function formatRupiah(angka) {
       if (angka === null || angka === undefined || isNaN(angka) || angka === '') {
           return 'Rp 0';
       }
       
       // Convert to number if it's a string
       const number = typeof angka === 'string' ? parseFloat(angka) : angka;
       
       // Check if the number is valid
       if (isNaN(number)) {
           return 'Rp 0';
       }
       
       // Format the number
       const formattedNumber = new Intl.NumberFormat('id-ID', {
           style: 'currency',
           currency: 'IDR',
           minimumFractionDigits: 0,
           maximumFractionDigits: 0
       }).format(number);
       
       return formattedNumber;
   }
   
   // Format date
   function formatDate(dateString) {
       if (!dateString) return '-';
       
       try {
           const date = new Date(dateString);
           
           // Check if date is valid
           if (isNaN(date.getTime())) {
               return dateString;
           }
           
           return new Intl.DateTimeFormat('id-ID', {
               day: '2-digit',
               month: 'long',
               year: 'numeric'
           }).format(date);
       } catch (error) {
           console.error('Error formatting date:', error);
           return dateString;
       }
   }

   // Export to CSV
   function exportCsv(periode) {
       // Show loading
       Swal.fire({
           title: 'Memproses...',
           text: 'Sedang menyiapkan file CSV',
           allowOutsideClick: false,
           didOpen: () => {
               Swal.showLoading();
               
               setTimeout(() => {
                   const bulan = $('#bulan').val();
                   const tahun = $('#tahun').val();
                   
                   const url = `/laporan-pemesanan/export-csv?periode=${periode}&bulan=${bulan}&tahun=${tahun}`;
                   window.location.href = url;
                   
                   Swal.close();
               }, 1000);
           }
       });
   }

   // Export detail to CSV
   function exportDetailCsv(pemesanan_id) {
       // Show loading
       Swal.fire({
           title: 'Memproses...',
           text: 'Sedang menyiapkan file CSV',
           allowOutsideClick: false,
           didOpen: () => {
               Swal.showLoading();
               
               setTimeout(() => {
                   const url = `/laporan-pemesanan/export-detail-csv?pemesanan_id=${pemesanan_id}`;
                   window.location.href = url;
                   
                   Swal.close();
               }, 1000);
           }
       });
   }
});
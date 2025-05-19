// File JavaScript untuk Pemesanan
$(document).ready(function() {
    // Format tanggal
    function formatDate(date) {
        if (!date) return '-';
        
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    }

    // Format mata uang
    function formatRupiah(angka) {
        return 'Rp ' + parseFloat(angka).toFixed(0).replace(/\d(?=(\d{3})+$)/g, '$&.');
    }

    // DataTable
    const table = $('#table-pemesanan').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/pemesanan/data',
            data: function(d) {
                d.barang_id = $('#filter_barang_id').val();
                d.status = $('#filter_status').val();
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'pemesanan_id', name: 'pemesanan_id' },
            { data: 'formatted_tanggal', name: 'tanggal_pemesanan' },
            { data: 'nama_pemesan', name: 'nama_pemesan' },
            { data: 'barang_nama', name: 'barang.nama_barang' },
            { data: 'jumlah_pesanan', name: 'jumlah_pesanan' },
            { data: 'formatted_total', name: 'total' },
            { data: 'status_label', name: 'status_pemesanan' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']]
    });

    // Filter action
    $('#btnFilter').click(function() {
        table.draw();
    });

    // Reset filter
    $('#resetFilter').click(function() {
        $('#filterForm')[0].reset();
        table.draw();
    });

    // Show/hide date fields based on status
    function toggleDateFields(status) {
        // Reset all date fields visibility first
        $('#tanggal-diproses-container, #tanggal-dikirim-container, #tanggal-selesai-container').hide();
        $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').prop('required', false);
        
        // Show and require appropriate date fields based on status
        if (status === 'diproses' || status === 'dikirim' || status === 'selesai') {
            $('#tanggal-diproses-container').show();
            $('#tanggal_diproses').prop('required', true);
        }
        
        if (status === 'dikirim' || status === 'selesai') {
            $('#tanggal-dikirim-container').show();
            $('#tanggal_dikirim').prop('required', true);
        }
        
        if (status === 'selesai') {
            $('#tanggal-selesai-container').show();
            $('#tanggal_selesai').prop('required', true);
        }
    }

    // Toggle date fields when status changes
    $('#status_pemesanan').change(function() {
        toggleDateFields($(this).val());
    });

    // Set default tanggal_pemesanan to today when adding new pemesanan
    $('#btnTambahPemesanan').click(function() {
        $('#form_action').val('add');
        $('#formPemesanan')[0].reset();
        $('#modalPemesananLabel').text('Tambah Pemesanan');
        
        // Set today's date as default
        const today = new Date();
        const formattedDate = today.toISOString().substr(0, 10);
        $('#tanggal_pemesanan').val(formattedDate);
        
        // Default status is pending
        $('#status_pemesanan').val('pending');
        toggleDateFields('pending');
        
        // Get new pemesanan ID
        $.ajax({
            url: '/pemesanan/get-id',
            type: 'GET',
            success: function(response) {
                $('#pemesanan_id').val(response.pemesanan_id);
                $('#no_pemesanan').val(response.pemesanan_id);
            },
            error: function(xhr) {
                Swal.fire('Error', 'Gagal mengambil nomor pemesanan', 'error');
            }
        });
        
        $('.is-invalid').removeClass('is-invalid');
        $('#modalPemesanan').modal('show');
    });

    // Calculate total price when quantity or price changes
    $('#jumlah_pesanan, #harga_satuan').on('input', function() {
        const jumlah = parseInt($('#jumlah_pesanan').val()) || 0;
        const harga = parseFloat($('#harga_satuan').val()) || 0;
        const total = jumlah * harga;
        $('#total').val(total);
    });

    // Set price when barang selection changes
    $('#barang_id').change(function() {
        const harga = $(this).find(':selected').data('harga') || 0;
        $('#harga_satuan').val(harga);
        
        // Trigger total calculation
        $('#harga_satuan').trigger('input');
    });

// Edit pemesanan
$(document).on('click', '.btn-edit', function() {
    const id = $(this).data('id');
    $('#form_action').val('edit');
    $('#modalPemesananLabel').text('Edit Pemesanan');
    $('.is-invalid').removeClass('is-invalid');
    
    // Hapus semua pesan error yang mungkin masih ada
    $('.invalid-feedback').text('');
    
    // Hapus semua field hidden tanggal sebelumnya (jika ada)
    $('.hidden-date-input').remove();
    
    $.ajax({
        url: `/pemesanan/${id}`,
        type: 'GET',
        success: function(response) {
            const data = response.data;
            
            // Isi field dasar
            $('#pemesanan_id').val(data.pemesanan_id);
            $('#no_pemesanan').val(data.pemesanan_id);
            $('#nama_pemesan').val(data.nama_pemesan);
            $('#alamat_pemesan').val(data.alamat_pemesan);
            $('#barang_id').val(data.barang_id);
            $('#jumlah_pesanan').val(data.jumlah_pesanan);
            $('#total').val(data.total);
            $('#pemesanan_dari').val(data.pemesanan_dari);
            $('#metode_pembayaran').val(data.metode_pembayaran);
            $('#status_pemesanan').val(data.status_pemesanan);
            $('#no_telp_pemesan').val(data.no_telp_pemesan);
            $('#email_pemesan').val(data.email_pemesan);
            $('#catatan_pemesanan').val(data.catatan_pemesanan);
            
            // Calculate harga satuan
            const hargaSatuan = data.jumlah_pesanan > 0 ? (data.total / data.jumlah_pesanan) : 0;
            $('#harga_satuan').val(hargaSatuan);
            
            // PENTING: Handle semua tanggal secara berbeda
            
            // 1. Tanggal Pemesanan - selalu diisi dan tidak bisa diubah saat edit
            $('#tanggal_pemesanan').val(data.tanggal_pemesanan);
            $('<input>').attr({
                type: 'hidden',
                name: 'tanggal_pemesanan',
                class: 'hidden-date-input',
                value: data.tanggal_pemesanan
            }).appendTo('#formPemesanan');
            $('#tanggal_pemesanan').prop('disabled', true);
            
            // 2. Tanggal-tanggal status
            // Sembunyikan dulu semua container
            $('#tanggal-diproses-container, #tanggal-dikirim-container, #tanggal-selesai-container').hide();
            
            // Hapus required dari semua field tanggal
            $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').prop('required', false);
            
            // Tanggal Diproses
            if (data.tanggal_diproses) {
                // Buat hidden input dengan nilai tanggal yang sudah ada
                $('<input>').attr({
                    type: 'hidden',
                    name: 'tanggal_diproses',
                    class: 'hidden-date-input',
                    value: data.tanggal_diproses
                }).appendTo('#formPemesanan');
                
                // Tampilkan field tapi disable
                $('#tanggal-diproses-container').show();
                $('#tanggal_diproses').val(data.tanggal_diproses).prop('disabled', true);
            } 
            else if (data.status_pemesanan === 'diproses' || data.status_pemesanan === 'dikirim' || data.status_pemesanan === 'selesai') {
                // Jika status butuh tanggal tapi belum ada, tampilkan field aktif
                $('#tanggal-diproses-container').show();
                $('#tanggal_diproses').val(getTodayDate()).prop('disabled', false);
            }
            
            // Tanggal Dikirim
            if (data.tanggal_dikirim) {
                // Buat hidden input dengan nilai tanggal yang sudah ada
                $('<input>').attr({
                    type: 'hidden',
                    name: 'tanggal_dikirim',
                    class: 'hidden-date-input',
                    value: data.tanggal_dikirim
                }).appendTo('#formPemesanan');
                
                // Tampilkan field tapi disable
                $('#tanggal-dikirim-container').show();
                $('#tanggal_dikirim').val(data.tanggal_dikirim).prop('disabled', true);
            } 
            else if (data.status_pemesanan === 'dikirim' || data.status_pemesanan === 'selesai') {
                // Jika status butuh tanggal tapi belum ada, tampilkan field aktif
                $('#tanggal-dikirim-container').show();
                $('#tanggal_dikirim').val(getTodayDate()).prop('disabled', false);
            }
            
            // Tanggal Selesai
            if (data.tanggal_selesai) {
                // Buat hidden input dengan nilai tanggal yang sudah ada
                $('<input>').attr({
                    type: 'hidden',
                    name: 'tanggal_selesai',
                    class: 'hidden-date-input',
                    value: data.tanggal_selesai
                }).appendTo('#formPemesanan');
                
                // Tampilkan field tapi disable
                $('#tanggal-selesai-container').show();
                $('#tanggal_selesai').val(data.tanggal_selesai).prop('disabled', true);
            } 
            else if (data.status_pemesanan === 'selesai') {
                // Jika status butuh tanggal tapi belum ada, tampilkan field aktif
                $('#tanggal-selesai-container').show();
                $('#tanggal_selesai').val(getTodayDate()).prop('disabled', false);
            }
            
            // Simpan status lama untuk keperluan pengecekan perubahan status
            $('#status_pemesanan').data('old-status', data.status_pemesanan);
            
            $('#modalPemesanan').modal('show');
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire('Error', response.message || 'Terjadi kesalahan', 'error');
        }
    });
});

// Fungsi untuk mendapatkan tanggal hari ini dalam format YYYY-MM-DD
function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Reset form saat menambah pemesanan baru
$('#btnTambahPemesanan').click(function() {
    $('#form_action').val('add');
    $('#formPemesanan')[0].reset();
    $('#modalPemesananLabel').text('Tambah Pemesanan');
    
    // Hapus semua pesan error yang mungkin masih ada
    $('.invalid-feedback').text('');
    $('.is-invalid').removeClass('is-invalid');
    
    // Hapus semua field hidden tanggal (jika ada)
    $('.hidden-date-input').remove();
    
    // Reset properties tanggal ke nilai default
    $('#tanggal_pemesanan, #tanggal_diproses, #tanggal_dikirim, #tanggal_selesai')
        .prop('disabled', false)
        .prop('required', true);
    
    // Sembunyikan semua container tanggal status
    $('#tanggal-diproses-container, #tanggal-dikirim-container, #tanggal-selesai-container').hide();
    
    // Set today's date as default for tanggal_pemesanan
    $('#tanggal_pemesanan').val(getTodayDate());
    
    // Default status is pending
    $('#status_pemesanan').val('pending');
    $('#status_pemesanan').data('old-status', 'pending');
    
    // Get new pemesanan ID
    $.ajax({
        url: '/pemesanan/get-id',
        type: 'GET',
        success: function(response) {
            $('#pemesanan_id').val(response.pemesanan_id);
            $('#no_pemesanan').val(response.pemesanan_id);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Gagal mengambil nomor pemesanan', 'error');
        }
    });
    
    $('#modalPemesanan').modal('show');
});

// Toggle date fields when status changes
$('#status_pemesanan').change(function() {
    const newStatus = $(this).val();
    const oldStatus = $(this).data('old-status') || 'pending';
    const isEditMode = $('#form_action').val() === 'edit';
    
    if (isEditMode) {
        // Handle perubahan status untuk mode edit
        
        // Cek field tanggal yang sudah terisi (ada di hidden input)
        const hasTanggalDiproses = $('input[name="tanggal_diproses"].hidden-date-input').length > 0;
        const hasTanggalDikirim = $('input[name="tanggal_dikirim"].hidden-date-input').length > 0;
        const hasTanggalSelesai = $('input[name="tanggal_selesai"].hidden-date-input').length > 0;
        
        // Sembunyikan field status yang tidak diperlukan
        $('#tanggal-diproses-container, #tanggal-dikirim-container, #tanggal-selesai-container').hide();
        $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').prop('required', false);
        
        // Reset field tanggal yang tidak digunakan lagi
        if (newStatus === 'pending' || newStatus === 'dibatalkan') {
            // Hapus hidden fields
            $('.hidden-date-input:not([name="tanggal_pemesanan"])').remove();
            return;
        }
        
        // Tampilkan field tanggal berdasarkan status baru
        if ((newStatus === 'diproses' || newStatus === 'dikirim' || newStatus === 'selesai') && !hasTanggalDiproses) {
            $('#tanggal-diproses-container').show();
            $('#tanggal_diproses').val(getTodayDate()).prop('disabled', false);
        }
        
        if ((newStatus === 'dikirim' || newStatus === 'selesai') && !hasTanggalDikirim) {
            $('#tanggal-dikirim-container').show();
            $('#tanggal_dikirim').val(getTodayDate()).prop('disabled', false);
        }
        
        if (newStatus === 'selesai' && !hasTanggalSelesai) {
            $('#tanggal-selesai-container').show();
            $('#tanggal_selesai').val(getTodayDate()).prop('disabled', false);
        }
    } else {
        // Mode tambah baru
        // Sembunyikan semua field tanggal status dulu
        $('#tanggal-diproses-container, #tanggal-dikirim-container, #tanggal-selesai-container').hide();
        $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').prop('required', false);
        
        // Tampilkan field tanggal berdasarkan status yang dipilih
        if (newStatus === 'diproses' || newStatus === 'dikirim' || newStatus === 'selesai') {
            $('#tanggal-diproses-container').show();
            $('#tanggal_diproses').prop('required', true);
        }
        
        if (newStatus === 'dikirim' || newStatus === 'selesai') {
            $('#tanggal-dikirim-container').show();
            $('#tanggal_dikirim').prop('required', true);
        }
        
        if (newStatus === 'selesai') {
            $('#tanggal-selesai-container').show();
            $('#tanggal_selesai').prop('required', true);
        }
    }
    
    // Remember the new status for next change
    $(this).data('old-status', newStatus);
});

// Pengiriman form
$('#formPemesanan').submit(function(e) {
    e.preventDefault();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    const formAction = $('#form_action').val();
    const pemesananId = $('#pemesanan_id').val();
    let url = '/pemesanan/store';
    let method = 'POST';
    
    if (formAction === 'edit') {
        url = `/pemesanan/${pemesananId}`;
        method = 'PUT';
    }
    
    // Validasi manual hanya untuk field yang tidak disabled
    const status = $('#status_pemesanan').val();
    let isValid = true;
    
    // Jika mode tambah baru, validasi semua field tanggal yang visible
    if (formAction === 'add') {
        if (!$('#tanggal_pemesanan').val()) {
            $('#tanggal_pemesanan').addClass('is-invalid');
            $('#error-tanggal_pemesanan').text('Tanggal pemesanan harus diisi');
            isValid = false;
        }
        
        if ($('#tanggal-diproses-container').is(':visible') && !$('#tanggal_diproses').val()) {
            $('#tanggal_diproses').addClass('is-invalid');
            $('#error-tanggal_diproses').text('Tanggal diproses harus diisi');
            isValid = false;
        }
        
        if ($('#tanggal-dikirim-container').is(':visible') && !$('#tanggal_dikirim').val()) {
            $('#tanggal_dikirim').addClass('is-invalid');
            $('#error-tanggal_dikirim').text('Tanggal dikirim harus diisi');
            isValid = false;
        }
        
        if ($('#tanggal-selesai-container').is(':visible') && !$('#tanggal_selesai').val()) {
            $('#tanggal_selesai').addClass('is-invalid');
            $('#error-tanggal_selesai').text('Tanggal selesai harus diisi');
            isValid = false;
        }
    } else {
        // Mode edit - hanya validasi field yang visible dan tidak disabled
        if ($('#tanggal-diproses-container').is(':visible') && !$('#tanggal_diproses').prop('disabled') && !$('#tanggal_diproses').val()) {
            $('#tanggal_diproses').addClass('is-invalid');
            $('#error-tanggal_diproses').text('Tanggal diproses harus diisi');
            isValid = false;
        }
        
        if ($('#tanggal-dikirim-container').is(':visible') && !$('#tanggal_dikirim').prop('disabled') && !$('#tanggal_dikirim').val()) {
            $('#tanggal_dikirim').addClass('is-invalid');
            $('#error-tanggal_dikirim').text('Tanggal dikirim harus diisi');
            isValid = false;
        }
        
        if ($('#tanggal-selesai-container').is(':visible') && !$('#tanggal_selesai').prop('disabled') && !$('#tanggal_selesai').val()) {
            $('#tanggal_selesai').addClass('is-invalid');
            $('#error-tanggal_selesai').text('Tanggal selesai harus diisi');
            isValid = false;
        }
    }
    
    if (!isValid) {
        return false;
    }
    
    // Form valid, lanjut submit
    $.ajax({
        url: url,
        type: method,
        data: $(this).serialize(),
        success: function(response) {
            $('#modalPemesanan').modal('hide');
            Swal.fire('Sukses', response.message, 'success');
            table.ajax.reload();
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            if (response.errors) {
                // Display validation errors
                $.each(response.errors, function(field, messages) {
                    const inputField = $(`#${field}`);
                    if (inputField.length) {
                        inputField.addClass('is-invalid');
                        $(`#error-${field}`).text(messages[0]);
                    }
                });
            } else {
                Swal.fire('Error', response.message || 'Terjadi kesalahan', 'error');
            }
        }
    });
});
    // Detail pemesanan
    $(document).on('click', '.btn-detail', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: `/pemesanan/${id}`,
            type: 'GET',
            success: function(response) {
                const data = response.data;
                $('#detail_pemesanan_id').val(data.pemesanan_id);
                $('#detail_nama_pemesan').val(data.nama_pemesan);
                $('#detail_tanggal_pemesanan').val(formatDate(data.tanggal_pemesanan));
                $('#detail_alamat_pemesan').val(data.alamat_pemesan);
                $('#detail_barang').val(data.barang ? data.barang.nama_barang : '-');
                $('#detail_jumlah_pesanan').val(data.jumlah_pesanan);
                $('#detail_harga_satuan').val(formatRupiah(data.jumlah_pesanan > 0 ? (data.total / data.jumlah_pesanan) : 0));
                $('#detail_total').val(formatRupiah(data.total));
                $('#detail_pemesanan_dari').val(data.pemesanan_dari);
                $('#detail_metode_pembayaran').val(data.metode_pembayaran);
                $('#detail_email_pemesan').val(data.email_pemesan);
                $('#detail_no_telp_pemesan').val(data.no_telp_pemesan);
                $('#detail_catatan_pemesanan').val(data.catatan_pemesanan);
                
                // Map status value to label
                const statusMap = {
                    'pending': 'Menunggu',
                    'diproses': 'Diproses',
                    'dikirim': 'Dikirim',
                    'selesai': 'Selesai',
                    'dibatalkan': 'Dibatalkan'
                };
                $('#detail_status_pemesanan').val(statusMap[data.status_pemesanan] || 'Tidak Diketahui');
                
                // Show status dates if available
                $('#detail_tanggal_diproses').val(formatDate(data.tanggal_diproses));
                $('#detail_tanggal_dikirim').val(formatDate(data.tanggal_dikirim));
                $('#detail_tanggal_selesai').val(formatDate(data.tanggal_selesai));
                
                // Show/hide date fields based on status
                $('#detail-tanggal-diproses-container, #detail-tanggal-dikirim-container, #detail-tanggal-selesai-container').hide();
                
                if (data.status_pemesanan === 'diproses' || data.status_pemesanan === 'dikirim' || data.status_pemesanan === 'selesai') {
                    $('#detail-tanggal-diproses-container').show();
                }
                
                if (data.status_pemesanan === 'dikirim' || data.status_pemesanan === 'selesai') {
                    $('#detail-tanggal-dikirim-container').show();
                }
                
                if (data.status_pemesanan === 'selesai') {
                    $('#detail-tanggal-selesai-container').show();
                }
                
                $('#modalDetailPemesanan').modal('show');
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error', response.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Delete confirmation
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        const nama = $(this).data('nama');
        $('#delete-item-name').text(nama);
        
        $('#deleteModal').modal('show');
        
        $('#btnDelete').off('click').on('click', function() {
            $.ajax({
                url: `/pemesanan/${id}`,
                type: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#deleteModal').modal('hide');
                    Swal.fire('Sukses', response.message, 'success');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error', response.message || 'Terjadi kesalahan', 'error');
                }
            });
        });
    });

    // Initialize date fields on page load (for add new case)
    toggleDateFields($('#status_pemesanan').val());
    
    // Set today's date as default
    if (!$('#tanggal_pemesanan').val()) {
        const today = new Date();
        const formattedDate = today.toISOString().substr(0, 10);
        $('#tanggal_pemesanan').val(formattedDate);
    }
    
    // Date fields validation - check that dates are in logical order
    $('#tanggal_dikirim').change(function() {
        const tanggalDiproses = $('#tanggal_diproses').val();
        const tanggalDikirim = $(this).val();
        
        if (tanggalDiproses && tanggalDikirim && new Date(tanggalDikirim) < new Date(tanggalDiproses)) {
            $(this).addClass('is-invalid');
            $('#error-tanggal_dikirim').text('Tanggal dikirim tidak boleh sebelum tanggal diproses');
        } else {
            $(this).removeClass('is-invalid');
            $('#error-tanggal_dikirim').text('');
        }
    });
    
    $('#tanggal_selesai').change(function() {
        const tanggalDikirim = $('#tanggal_dikirim').val();
        const tanggalSelesai = $(this).val();
        
        if (tanggalDikirim && tanggalSelesai && new Date(tanggalSelesai) < new Date(tanggalDikirim)) {
            $(this).addClass('is-invalid');
            $('#error-tanggal_selesai').text('Tanggal selesai tidak boleh sebelum tanggal dikirim');
        } else {
            $(this).removeClass('is-invalid');
            $('#error-tanggal_selesai').text('');
        }
    });
    
    // Set default date when status changes
    $('#status_pemesanan').change(function() {
        const status = $(this).val();
        const today = new Date().toISOString().substr(0, 10);
        
        // If status changes to a value requiring dates and the date fields are empty, 
        // set them to today's date
        if (status === 'diproses' && !$('#tanggal_diproses').val()) {
            $('#tanggal_diproses').val(today);
        }
        
        if (status === 'dikirim' && !$('#tanggal_dikirim').val()) {
            $('#tanggal_dikirim').val(today);
        }
        
        if (status === 'selesai' && !$('#tanggal_selesai').val()) {
            $('#tanggal_selesai').val(today);
        }
        
        // If changing to pending or dibatalkan, clear all status dates
        if (status === 'pending' || status === 'dibatalkan') {
            $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').val('');
        }
    });
});
$(document).ready(function() {
    // Initialize Select2
    if ($.fn.select2) {
        $('#filter_barang_id, #barang_id').select2({
            placeholder: "Pilih Barang",
            allowClear: true,
            theme: 'bootstrap4'
        });
        
        $('#filter_status, #status_pemesanan').select2({
            placeholder: "Pilih Status",
            allowClear: true,
            theme: 'bootstrap4'
        });
        
        $('#pemesanan_dari').select2({
            placeholder: "Pilih Sumber",
            allowClear: true,
            theme: 'bootstrap4',
            dropdownParent: $('#modalPemesanan')
        });
        
        $('#metode_pembayaran').select2({
            placeholder: "Pilih Metode",
            allowClear: true,
            theme: 'bootstrap4',
            dropdownParent: $('#modalPemesanan')
        });
    }
    
    // Set default date for pemesanan (today)
    const today = new Date().toISOString().split('T')[0];
    $('#tanggal_pemesanan').val(today);
    
    // Initialize DataTable
    var table = $('#table-pemesanan').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "/pemesanan/data",
            type: "GET",
            data: function(d) {
                d.barang_id = $('#filter_barang_id').val();
                d.status = $('#filter_status').val();
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
                return d;
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
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        order: [[2, 'desc']] // Order by tanggal_pemesanan desc
    });

    // Apply filter when button is clicked
    $('#btnFilter').click(function() {
        table.ajax.reload();
    });

    // Reset filter
    $('#resetFilter').click(function() {
        $('#filter_barang_id').val('').trigger('change');
        $('#filter_status').val('').trigger('change');
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
        table.ajax.reload();
    });

    // Calculate total when harga_satuan or jumlah_pesanan changes
    $('#harga_satuan, #jumlah_pesanan').on('input', function() {
        calculateTotal();
    });

    // Calculate total when barang is selected (if it has price)
    $('#barang_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var harga = selectedOption.data('harga') || 0;
        $('#harga_satuan').val(harga);
        calculateTotal();
    });

    // Open modal for adding new pemesanan
    $('#btnTambahPemesanan').click(function() {
        resetForm();
        $('#modalPemesananLabel').text('Tambah Pemesanan');
        $('#form_action').val('add');
        $('#modalPemesanan').modal('show');
        
        // Get auto-generated ID
        getPemesananId();
    });

    // Handle detail pemesanan button
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');
        showPemesananDetail(id);
    });

    // Handle edit pemesanan button
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        
        resetForm();
        $('#modalPemesananLabel').text('Edit Pemesanan');
        $('#form_action').val('edit');
        
        // Get pemesanan data for editing
        $.ajax({
            url: '/pemesanan/' + id,
            type: 'GET',
            success: function(response) {
                const data = response.data;
                
                // Fill form with pemesanan data
                $('#pemesanan_id').val(data.pemesanan_id);
                $('#no_pemesanan').val(data.pemesanan_id);
                $('#tanggal_pemesanan').val(data.tanggal_pemesanan);
                $('#nama_pemesan').val(data.nama_pemesan);
                $('#email_pemesan').val(data.email_pemesan);
                $('#no_telp_pemesan').val(data.no_telp_pemesan);
                $('#pemesanan_dari').val(data.pemesanan_dari).trigger('change');
                $('#alamat_pemesan').val(data.alamat_pemesan);
                $('#barang_id').val(data.barang_id).trigger('change');
                $('#jumlah_pesanan').val(data.jumlah_pesanan);
                
                // Calculate harga_satuan
                var hargaSatuan = 0;
                if (data.jumlah_pesanan > 0) {
                    hargaSatuan = data.total / data.jumlah_pesanan;
                }
                $('#harga_satuan').val(hargaSatuan);
                
                $('#total').val(data.total);
                $('#metode_pembayaran').val(data.metode_pembayaran).trigger('change');
                $('#status_pemesanan').val(data.status_pemesanan).trigger('change');
                $('#catatan_pemesanan').val(data.catatan_pemesanan);
                
                // Show modal
                $('#modalPemesanan').modal('show');
            },
            error: function() {
                showAlert('danger', 'Gagal memuat data pemesanan');
            }
        });
    });

    // Submit pemesanan form
    $('#formPemesanan').submit(function(e) {
        e.preventDefault();
        clearErrors();
        
        var formData = $(this).serialize();
        var formAction = $('#form_action').val();
        var url, method;
        
        if (formAction === 'add') {
            url = '/pemesanan/store';
            method = 'POST';
        } else {
            // Edit existing pemesanan
            var id = $('#pemesanan_id').val();
            url = '/pemesanan/' + id;
            method = 'PUT';
        }
        
        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(response) {
                $('#modalPemesanan').modal('hide');
                table.ajax.reload();
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    showValidationErrors(errors);
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Terjadi kesalahan! Silahkan coba lagi.');
                }
            }
        });
    });

    // Setup delete confirmation
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        
        $('#delete-item-name').text(nama);
        $('#btnDelete').data('id', id);
        
        $('#deleteModal').modal('show');
    });

    // Process delete
    $('#btnDelete').click(function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: '/pemesanan/' + id,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                table.ajax.reload();
                showAlert('success', response.message);
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Gagal menghapus data pemesanan');
                }
            }
        });
    });

    // HELPER FUNCTIONS

    // Get auto-generated pemesanan ID
    function getPemesananId() {
        $.ajax({
            url: '/pemesanan/get-id',
            type: 'GET',
            success: function(response) {
                $('#pemesanan_id').val(response.pemesanan_id);
                $('#no_pemesanan').val(response.pemesanan_id);
            },
            error: function() {
                showAlert('danger', 'Gagal mendapatkan nomor pemesanan otomatis');
            }
        });
    }

    // Calculate total from harga_satuan and jumlah_pesanan
    function calculateTotal() {
        var hargaSatuan = parseFloat($('#harga_satuan').val()) || 0;
        var jumlahPesanan = parseInt($('#jumlah_pesanan').val()) || 0;
        var total = hargaSatuan * jumlahPesanan;
        
        $('#total').val(total);
    }

    // Show pemesanan detail
    function showPemesananDetail(id) {
        $.ajax({
            url: '/pemesanan/' + id,
            type: 'GET',
            success: function(response) {
                const data = response.data;
                
                // Fill detail modal
                $('#detail_pemesanan_id').val(data.pemesanan_id);
                $('#detail_tanggal_pemesanan').val(formatDate(data.tanggal_pemesanan));
                $('#detail_nama_pemesan').val(data.nama_pemesan);
                $('#detail_email_pemesan').val(data.email_pemesan);
                $('#detail_no_telp_pemesan').val(data.no_telp_pemesan);
                $('#detail_pemesanan_dari').val(data.pemesanan_dari);
                $('#detail_alamat_pemesan').val(data.alamat_pemesan);
                $('#detail_barang').val(data.barang ? data.barang.nama_barang : '-');
                $('#detail_jumlah_pesanan').val(data.jumlah_pesanan);
                
                // Calculate harga_satuan
                var hargaSatuan = 0;
                if (data.jumlah_pesanan > 0) {
                    hargaSatuan = data.total / data.jumlah_pesanan;
                }
                $('#detail_harga_satuan').val(formatRupiah(hargaSatuan));
                
                $('#detail_total').val(formatRupiah(data.total));
                $('#detail_metode_pembayaran').val(data.metode_pembayaran);
                
                // Format status label
                var statusLabels = {
                    'pending': 'Menunggu',
                    'diproses': 'Diproses',
                    'dikirim': 'Dikirim',
                    'selesai': 'Selesai',
                    'dibatalkan': 'Dibatalkan'
                };
                $('#detail_status_pemesanan').val(statusLabels[data.status_pemesanan] || data.status_pemesanan);
                
                $('#detail_catatan_pemesanan').val(data.catatan_pemesanan);
                
                // Show modal
                $('#modalDetailPemesanan').modal('show');
            },
            error: function() {
                showAlert('danger', 'Gagal memuat detail pemesanan');
            }
        });
    }

    // Reset form
    function resetForm() {
        $('#formPemesanan')[0].reset();
        $('#barang_id').val('').trigger('change');
        $('#pemesanan_dari').val('').trigger('change');
        $('#metode_pembayaran').val('').trigger('change');
        $('#status_pemesanan').val('pending').trigger('change');
        $('#tanggal_pemesanan').val(today);
        clearErrors();
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Format currency
    function formatRupiah(angka) {
        if (!angka) return 'Rp 0';
        return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
    }

    // Clear validation errors
    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    // Show validation errors
    function showValidationErrors(errors) {
        $.each(errors, function(field, messages) {
            $('#' + field).addClass('is-invalid');
            $('#error-' + field).text(messages[0]);
        });
    }

    // Show alert message
    function showAlert(type, message) {
        var alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        $('#alert-container').html(alert);
        
        // Auto close alert after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
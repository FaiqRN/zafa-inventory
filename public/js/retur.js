$(document).ready(function() {
    // Initialize Select2
    if ($.fn.select2) {
        $('#filter_toko_id, #pengiriman_filter_toko').select2({
            placeholder: "Pilih Toko",
            allowClear: true,
            theme: 'bootstrap4'
        });
        
        $('#filter_barang_id, #pengiriman_filter_barang').select2({
            placeholder: "Pilih Barang",
            allowClear: true,
            theme: 'bootstrap4'
        });
        
        $('#kondisi').select2({
            placeholder: "Pilih Kondisi",
            theme: 'bootstrap4',
            dropdownParent: $('#modalTambahRetur')
        });
    }
    
    // Set default date for retur (today)
    const today = new Date().toISOString().split('T')[0];
    $('#tanggal_retur').val(today);
    
    // Initialize DataTable
    var table = $('#table-retur').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "/retur/data",
            type: "GET",
            data: function(d) {
                d.toko_id = $('#filter_toko_id').val();
                d.barang_id = $('#filter_barang_id').val();
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
                return d;
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nomer_pengiriman', name: 'nomer_pengiriman' },
            { data: 'formatted_tanggal_pengiriman', name: 'tanggal_pengiriman' },
            { data: 'formatted_tanggal_retur', name: 'tanggal_retur' },
            { data: 'toko_nama', name: 'toko.nama_toko' },
            { data: 'barang_nama', name: 'barang.nama_barang' },
            { data: 'jumlah_kirim', name: 'jumlah_kirim' },
            { data: 'jumlah_retur', name: 'jumlah_retur' },
            { data: 'total_terjual', name: 'total_terjual' },
            { data: 'formatted_harga', name: 'harga_awal_barang' },
            { data: 'formatted_hasil', name: 'hasil' },
            { data: 'kondisi', name: 'kondisi' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        order: [[3, 'desc']] // Order by tanggal_retur desc
    });

    // Apply filter when button is clicked
    $('#btnFilter').click(function() {
        table.ajax.reload();
        console.log("Filter applied:", {
            toko: $('#filter_toko_id').val(),
            barang: $('#filter_barang_id').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        });
    });

    // Reset filter
    $('#resetFilter').click(function() {
        $('#filter_toko_id').val('').trigger('change');
        $('#filter_barang_id').val('').trigger('change');
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
        table.ajax.reload();
    });

    // Export data
    $(document).on('click', '.export-data', function(e) {
        e.preventDefault();
        
        let format = $(this).data('format');
        let url = '/retur/export';
        let params = [];
        
        // Add format to parameters
        params.push('format=' + format);
        
        // Add filters if any
        if ($('#filter_toko_id').val()) {
            params.push('toko_id=' + $('#filter_toko_id').val());
        }
        
        if ($('#filter_barang_id').val()) {
            params.push('barang_id=' + $('#filter_barang_id').val());
        }
        
        if ($('#filter_start_date').val()) {
            params.push('start_date=' + $('#filter_start_date').val());
        }
        
        if ($('#filter_end_date').val()) {
            params.push('end_date=' + $('#filter_end_date').val());
        }
        
        // Add parameters to URL
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        // Open URL
        window.location.href = url;
    });

    // Open tambah retur modal
    $('#btnTambahRetur').click(function() {
        resetFormRetur();
        $('#modalTambahRetur').modal('show');
    });

    // Cari pengiriman available untuk retur
    $('#btnCariPengiriman').click(function() {
        loadPengirimanList();
    });

    // Go back from step 2 to step 1
    $('#btnBackToStep1').click(function() {
        $('#step2').hide();
        $('#step1').show();
        $('#btnSimpanRetur').hide();
    });

    // Select pengiriman for retur
    $(document).on('click', '.btn-pilih-pengiriman', function() {
        var pengirimanId = $(this).data('id');
        var row = $(this).closest('tr');
        
        // Fill hidden input
        $('#pengiriman_id').val(pengirimanId);
        
        // Fill info fields
        $('#info_nomer_pengiriman').val(row.find('td:eq(1)').text());
        $('#info_tanggal_pengiriman').val(row.find('td:eq(2)').text());
        $('#info_toko').val(row.find('td:eq(3)').text());
        $('#info_barang').val(row.find('td:eq(4)').text());
        $('#info_jumlah_kirim').val(row.find('td:eq(5)').text());
        $('#info_sudah_retur').val(row.find('td:eq(6)').text());
        $('#info_sisa').val(row.find('td:eq(7)').text());
        
        // MODIFIED: Remove attributes for number spinner and set default to 0
        $('#jumlah_retur').val(0);
        $('#jumlah_retur').attr('type', 'text'); // Ensure it's a text input
        $('#jumlah_retur').removeAttr('min');
        $('#jumlah_retur').removeAttr('max');
        $('#jumlah_retur').removeAttr('step');
        
        // Show step 2, hide step 1
        $('#step1').hide();
        $('#step2').show();
        $('#btnSimpanRetur').show();
        
        // MODIFIED: Validate input as text field (only allow numbers)
        $('#jumlah_retur').off('input').on('input', function() {
            // Remove any non-numeric characters except for the first '-' if it exists
            let value = $(this).val().replace(/[^0-9]/g, '');
            
            // Convert to number and enforce limits
            let numValue = parseInt(value);
            
            // If not a number, set to 0
            if (isNaN(numValue)) {
                numValue = 0;
            }
            
            // Ensure value is not negative
            if (numValue < 0) {
                numValue = 0;
            }
            
            // Ensure value doesn't exceed max limit
            const maxVal = parseInt($('#info_sisa').val());
            if (numValue > maxVal) {
                numValue = maxVal;
            }
            
            // Update the input value
            $(this).val(numValue);
        });
    });

    // Submit tambah retur form
    $('#btnSimpanRetur').click(function() {
        submitFormRetur();
    });

    // Handle detail retur button
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: '/retur/' + id,
            type: 'GET',
            success: function(response) {
                const data = response.data;
                
                $('#detail_nomer_pengiriman').val(data.nomer_pengiriman);
                $('#detail_tanggal_pengiriman').val(formatDate(data.tanggal_pengiriman));
                $('#detail_tanggal_retur').val(formatDate(data.tanggal_retur));
                $('#detail_toko').val(data.toko ? data.toko.nama_toko : '-');
                $('#detail_barang').val(data.barang ? data.barang.nama_barang : '-');
                $('#detail_harga').val(formatRupiah(data.harga_awal_barang));
                $('#detail_jumlah_kirim').val(data.jumlah_kirim);
                $('#detail_jumlah_retur').val(data.jumlah_retur);
                $('#detail_total_terjual').val(data.total_terjual);
                $('#detail_hasil').val(formatRupiah(data.hasil));
                $('#detail_kondisi').val(data.kondisi);
                $('#detail_keterangan').val(data.keterangan);
                
                $('#modalDetailRetur').modal('show');
            },
            error: function() {
                showAlert('danger', 'Gagal memuat data retur');
            }
        });
    });

    // Setup delete confirmation
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var nomer = $(this).data('nomer');
        
        $('#delete-item-name').text(nomer);
        $('#btnDelete').data('id', id);
        
        $('#deleteModal').modal('show');
    });

    // Process delete
    $('#btnDelete').click(function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: '/retur/' + id,
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
                    showAlert('danger', 'Gagal menghapus data retur');
                }
            }
        });
    });

    // HELPER FUNCTIONS

    // Reset form retur
    function resetFormRetur() {
        $('#formTambahRetur')[0].reset();
        $('#kondisi').val('').trigger('change');
        $('#tanggal_retur').val(today);
        clearErrors();
        
        // Reset pengiriman list
        $('#table-pengiriman tbody').empty();
        $('#pengiriman_filter_toko').val('').trigger('change');
        $('#pengiriman_filter_barang').val('').trigger('change');
        
        // Show step 1, hide step 2
        $('#step1').show();
        $('#step2').hide();
        $('#btnSimpanRetur').hide();
    }

    // Load pengiriman list for retur
    function loadPengirimanList() {
        $('#table-pengiriman tbody').html('<tr><td colspan="9" class="text-center">Loading data...</td></tr>');
        
        $.ajax({
            url: '/retur/get-pengiriman',
            type: 'GET',
            data: {
                toko_id: $('#pengiriman_filter_toko').val(),
                barang_id: $('#pengiriman_filter_barang').val()
            },
            success: function(response) {
                console.log("Response success:", response);
                $('#table-pengiriman tbody').empty();
                
                if (!response.data || response.data.length === 0) {
                    $('#table-pengiriman tbody').html('<tr><td colspan="9" class="text-center">Tidak ada data pengiriman yang tersedia untuk retur</td></tr>');
                    return;
                }
                
                let no = 1;
                $.each(response.data, function(index, item) {
                    // We don't skip any items here since the backend already filters
                    // out shipments that have been returned, and we allow zero returns
                    
                    let hargaBarang = 'Rp 0';
                    if (item.barang && item.barang.harga_awal_barang) {
                        hargaBarang = 'Rp ' + parseFloat(item.barang.harga_awal_barang).toLocaleString('id-ID');
                    } else if (item.harga_barang) {
                        hargaBarang = 'Rp ' + parseFloat(item.harga_barang).toLocaleString('id-ID');
                    }
                    
                    let row = `
                        <tr>
                            <td>${no++}</td>
                            <td>${item.nomer_pengiriman}</td>
                            <td>${formatDate(item.tanggal_pengiriman)}</td>
                            <td>${item.toko ? item.toko.nama_toko : '-'}</td>
                            <td>${item.barang ? item.barang.nama_barang : '-'}</td>
                            <td>${item.jumlah_kirim}</td>
                            <td>${item.total_retur}</td>
                            <td>${item.sisa_retur}</td>
                            <td>${hargaBarang}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary btn-pilih-pengiriman" 
                                    data-id="${item.pengiriman_id}" 
                                    data-harga="${item.barang ? item.barang.harga_awal_barang : 0}">
                                    <i class="fas fa-check"></i> Pilih
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    $('#table-pengiriman tbody').append(row);
                });
                
                // If no rows were added (all items were already returned)
                if ($('#table-pengiriman tbody tr').length === 0) {
                    $('#table-pengiriman tbody').html('<tr><td colspan="9" class="text-center">Semua pengiriman sudah diretur sepenuhnya</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error response:", xhr.responseText);
                console.error("Status:", status);
                console.error("Error:", error);
                
                $('#table-pengiriman tbody').html('<tr><td colspan="9" class="text-center">Gagal memuat data pengiriman: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : error) + '</td></tr>');
                showAlert('danger', 'Gagal memuat data pengiriman');
            }
        });
    }

    // Submit form retur
    function submitFormRetur() {
        clearErrors();
        
        // Collect form data
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            pengiriman_id: $('#pengiriman_id').val(),
            tanggal_retur: $('#tanggal_retur').val(),
            jumlah_retur: $('#jumlah_retur').val(),
            kondisi: $('#kondisi').val(),
            keterangan: $('#keterangan').val()
        };
        
        $.ajax({
            url: '/retur/store',
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#modalTambahRetur').modal('hide');
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
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).split('/').join('/');
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
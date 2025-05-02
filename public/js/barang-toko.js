$(document).ready(function() {
    // Current selected toko
    var selectedTokoId = '';
    var selectedTokoName = '';
    
    // Initialize select2 if available
    if ($.fn.select2) {
        $('#toko_select').select2({
            placeholder: "Pilih Toko"
        });
        $('#barang_id').select2({
            placeholder: "Pilih Barang",
            dropdownParent: $('#modalTambahBarang')
        });
    }
    
    // Event when toko is selected
    $('#toko_select').change(function() {
        selectedTokoId = $(this).val();
        selectedTokoName = $(this).find('option:selected').text();
        
        if (selectedTokoId) {
            // Show the barang-toko card
            $('#barang-toko-card').show();
            $('#toko-name-display').text(selectedTokoName);
            
            // Load barang data for this toko
            loadBarangTokoData(selectedTokoId);
        } else {
            // Hide the barang-toko card if no toko selected
            $('#barang-toko-card').hide();
        }
    });
    
    // Function to load barang-toko data for a specific toko
    function loadBarangTokoData(tokoId) {
        $.ajax({
            url: '/barang-toko/getBarangToko',
            type: 'GET',
            data: {
                toko_id: tokoId
            },
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function() {
                $('#barang-toko-body').html('<tr><td colspan="7" class="text-center">Memuat data...</td></tr>');
            },
            success: function(response) {
                if (response.data.length > 0) {
                    let tableHtml = '';
                    
                    $.each(response.data, function(index, item) {
                        tableHtml += `
                            <tr id="row-${item.barang_toko_id}">
                                <td>${index + 1}</td>
                                <td>${item.barang.barang_kode}</td>
                                <td>${item.barang.nama_barang}</td>
                                <td>Rp ${formatRupiah(item.barang.harga_awal_barang)}</td>
                                <td>Rp ${formatRupiah(item.harga_barang_toko)}</td>
                                <td>${item.barang.satuan}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="${item.barang_toko_id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="${item.barang_toko_id}" data-name="${item.barang.nama_barang}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#barang-toko-body').html(tableHtml);
                } else {
                    $('#barang-toko-body').html('<tr><td colspan="7" class="text-center">Belum ada barang untuk toko ini</td></tr>');
                }
            },
            error: function() {
                $('#barang-toko-body').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data</td></tr>');
                showAlert('danger', 'Gagal memuat data barang toko. Silahkan coba lagi.');
            }
        });
    }
    
    // Function to load available barang for a toko (not yet assigned)
    function loadAvailableBarang(tokoId) {
        $.ajax({
            url: '/barang-toko/getAvailableBarang',
            type: 'GET',
            data: {
                toko_id: tokoId
            },
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                // Clear select options
                $('#barang_id').empty().append('<option value="">-- Pilih Barang --</option>');
                
                // Populate select with available barang
                if (response.data.length > 0) {
                    $.each(response.data, function(index, item) {
                        $('#barang_id').append(
                            $('<option></option>')
                                .attr('value', item.barang_id)
                                .attr('data-harga', item.harga_awal_barang)
                                .text(item.barang_kode + ' - ' + item.nama_barang)
                        );
                    });
                }
                
                // Refresh select2 if available
                if ($.fn.select2) {
                    $('#barang_id').trigger('change');
                }
            },
            error: function() {
                showAlert('danger', 'Gagal memuat data barang. Silahkan coba lagi.');
            }
        });
    }
    
    // Event when a barang is selected in the tambah form
    $('#barang_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            var hargaAwal = selectedOption.data('harga');
            $('#harga_barang_toko').val(hargaAwal);
        } else {
            $('#harga_barang_toko').val('');
        }
    });
    
    // Button to show tambah barang modal
    $('#btnTambahBarang').click(function() {
        if (!selectedTokoId) {
            showAlert('warning', 'Silahkan pilih toko terlebih dahulu');
            return;
        }
        
        resetForm();
        $('#selected_toko_id').val(selectedTokoId);
        
        // Load available barang for this toko
        loadAvailableBarang(selectedTokoId);
        
        $('#modalTambahBarang').modal('show');
    });
    
    // Submit form tambah barang ke toko
    $('#formTambahBarang').submit(function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearErrors();
        
        $.ajax({
            url: '/barang-toko',
            type: 'POST',
            data: $(this).serialize(),
            cache: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                // Hide modal
                $('#modalTambahBarang').modal('hide');
                
                // Reload data
                loadBarangTokoData(selectedTokoId);
                
                // Show success message
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    showValidationErrors(errors);
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Terjadi kesalahan. Silahkan coba lagi.');
                }
            }
        });
    });
    
    // Get data for edit harga
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        
        resetForm();
        clearErrors();
        
        $.ajax({
            url: '/barang-toko/' + id + '/edit',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                var data = response.data;
                
                $('#edit_barang_toko_id').val(data.barang_toko_id);
                $('#edit_nama_barang').val(data.barang.nama_barang);
                $('#edit_harga_awal').val(formatRupiah(data.barang.harga_awal_barang));
                $('#edit_harga_barang_toko').val(data.harga_barang_toko);
                
                $('#modalEditHarga').modal('show');
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON.message || 'Gagal mengambil data.');
            }
        });
    });
    
    // Submit form edit harga
    $('#formEditHarga').submit(function(e) {
        e.preventDefault();
        
        clearErrors();
        
        var id = $('#edit_barang_toko_id').val();
        
        $.ajax({
            url: '/barang-toko/' + id,
            type: 'PUT',
            data: $(this).serialize(),
            cache: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                // Hide modal
                $('#modalEditHarga').modal('hide');
                
                // Reload data
                loadBarangTokoData(selectedTokoId);
                
                // Show success message
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    // Map field names to form field IDs
                    if (errors.harga_barang_toko) {
                        $('#edit_harga_barang_toko').addClass('is-invalid');
                        $('#error-edit_harga_barang_toko').text(errors.harga_barang_toko[0]);
                    }
                } else {
                    showAlert('danger', xhr.responseJSON.message || 'Terjadi kesalahan. Silahkan coba lagi.');
                }
            }
        });
    });
    
    // Setup for delete barang from toko
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#delete-item-name').text(name);
        $('#btnDelete').data('id', id);
        $('#deleteModal').modal('show');
    });
    
    // Process delete barang from toko
    $('#btnDelete').click(function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: '/barang-toko/' + id,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                // Hide modal
                $('#deleteModal').modal('hide');
                
                // Reload data
                loadBarangTokoData(selectedTokoId);
                
                // Show success message
                showAlert('success', response.message);
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Gagal menghapus data.');
                }
            }
        });
    });
    
    // Reset form
    function resetForm() {
        $('#formTambahBarang')[0].reset();
        $('#formEditHarga')[0].reset();
        clearErrors();
    }
    
    // Clear validation errors
    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }
    
    // Display validation errors
    function showValidationErrors(errors) {
        $.each(errors, function(field, messages) {
            $('#' + field).addClass('is-invalid');
            $('#error-' + field).text(messages[0]);
        });
    }
    
    // Format currency
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(number);
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
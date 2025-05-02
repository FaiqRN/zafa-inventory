$(document).ready(function() {
    // Initialize Select2
    if ($.fn.select2) {
        $('#toko_id, #filter_toko_id').select2({
            placeholder: "Pilih Toko",
            allowClear: true,
            theme: 'bootstrap4'
        });
        
        $('#barang_id').select2({
            placeholder: "Pilih Barang",
            allowClear: true,
            theme: 'bootstrap4',
            dropdownParent: $('#modalTambahPengiriman')
        });
    }
    
    // Set default date for new shipment (today)
    const today = new Date().toISOString().split('T')[0];
    $('#tanggal_pengiriman').val(today);

    // Load initial data
    loadPengirimanData();
    
    // Apply filter when button is clicked
    $('#btnFilter').click(function() {
        loadPengirimanData();
        console.log("Filter applied:", {
            toko: $('#filter_toko_id').val(),
            status: $('#filter_status').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        });
    });

    // Reset filter
    $('#resetFilter').click(function() {
        $('#filter_toko_id').val('').trigger('change');
        $('#filter_status').val('');
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
        loadPengirimanData();
    });

    // Export data button
    $('#exportData').click(function() {
        let url = '/pengiriman/export';
        let params = [];
        
        if ($('#filter_toko_id').val()) {
            params.push('toko_id=' + $('#filter_toko_id').val());
        }
        
        if ($('#filter_status').val()) {
            params.push('status=' + $('#filter_status').val());
        }
        
        if ($('#filter_start_date').val()) {
            params.push('start_date=' + $('#filter_start_date').val());
        }
        
        if ($('#filter_end_date').val()) {
            params.push('end_date=' + $('#filter_end_date').val());
        }
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        window.location.href = url;
    });

    // Generate nomor pengiriman when opening the add modal
    $('#btnTambahPengiriman').click(function() {
        resetForm();
        getNomerPengiriman();
        $('#modalTambahPengiriman').modal('show');
    });

    // When toko is selected, load available barang for that toko
    $('#toko_id').change(function() {
        var tokoId = $(this).val();
        if (tokoId) {
            $('#barang_id').prop('disabled', false);
            loadBarangByToko(tokoId);
        } else {
            $('#barang_id').prop('disabled', true).empty().append('<option value="">-- Pilih Barang --</option>');
            $('#satuan').val('');
        }
    });

    // When barang is selected, populate satuan
    $('#barang_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            $('#satuan').val(selectedOption.data('satuan'));
        } else {
            $('#satuan').val('');
        }
    });

    // Submit tambah pengiriman form
    $('#formTambahPengiriman').submit(function(e) {
        e.preventDefault();
        clearErrors();
        
        $.ajax({
            url: '/pengiriman',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#modalTambahPengiriman').modal('hide');
                loadPengirimanData(); // Reload the data after successful submission
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

    // Handle edit pengiriman button
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        clearErrors();
        
        $.ajax({
            url: '/pengiriman/' + id + '/edit',
            type: 'GET',
            success: function(response) {
                const data = response.data;
                
                $('#edit_pengiriman_id').val(data.pengiriman_id);
                $('#edit_nomer_pengiriman').val(data.nomer_pengiriman);
                $('#edit_tanggal_pengiriman').val(data.tanggal_pengiriman);
                $('#edit_toko_nama').val(data.toko.nama_toko);
                $('#edit_barang_nama').val(data.barang.nama_barang);
                $('#edit_jumlah_kirim').val(data.jumlah_kirim);
                $('#edit_status').val(data.status);
                
                $('#modalEditPengiriman').modal('show');
            },
            error: function() {
                showAlert('danger', 'Gagal memuat data pengiriman');
            }
        });
    });

    // Submit edit pengiriman form
    $('#formEditPengiriman').submit(function(e) {
        e.preventDefault();
        clearErrors();
        
        var id = $('#edit_pengiriman_id').val();
        
        $.ajax({
            url: '/pengiriman/' + id,
            type: 'PUT',
            data: $(this).serialize(),
            success: function(response) {
                $('#modalEditPengiriman').modal('hide');
                loadPengirimanData(); // Reload the data after successful edit
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    // Map field names to form field IDs
                    if (errors.tanggal_pengiriman) {
                        $('#edit_tanggal_pengiriman').addClass('is-invalid');
                        $('#error-edit_tanggal_pengiriman').text(errors.tanggal_pengiriman[0]);
                    }
                    if (errors.jumlah_kirim) {
                        $('#edit_jumlah_kirim').addClass('is-invalid');
                        $('#error-edit_jumlah_kirim').text(errors.jumlah_kirim[0]);
                    }
                    if (errors.status) {
                        $('#edit_status').addClass('is-invalid');
                        $('#error-edit_status').text(errors.status[0]);
                    }
                } else {
                    showAlert('danger', 'Terjadi kesalahan. Silahkan coba lagi.');
                }
            }
        });
    });

    // Handle update status button
    $(document).on('click', '.btn-status', function() {
        var id = $(this).data('id');
        var nomer = $(this).data('nomer');
        var status = $(this).data('status');
        
        $('#status_pengiriman_id').val(id);
        $('#status_nomer_pengiriman').val(nomer);
        $('#status_value').val(status);
        
        $('#modalUpdateStatus').modal('show');
    });

    // Submit update status form
    $('#formUpdateStatus').submit(function(e) {
        e.preventDefault();
        
        var id = $('#status_pengiriman_id').val();
        
        $.ajax({
            url: '/pengiriman/' + id + '/update-status',
            type: 'PUT',
            data: $(this).serialize(),
            success: function(response) {
                $('#modalUpdateStatus').modal('hide');
                loadPengirimanData(); // Reload the data after successful status update
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Gagal mengubah status pengiriman');
                }
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
            url: '/pengiriman/' + id,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                loadPengirimanData(); // Reload the data after successful deletion
                showAlert('success', response.message);
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Gagal menghapus data pengiriman');
                }
            }
        });
    });

    // -------------- HELPER FUNCTIONS --------------

    // Function to get auto-generated nomor pengiriman
    function getNomerPengiriman() {
        $.ajax({
            url: '/pengiriman/get-nomer',
            type: 'GET',
            success: function(response) {
                $('#nomer_pengiriman').val(response.nomer_pengiriman);
            },
            error: function() {
                showAlert('danger', 'Gagal mendapatkan nomor pengiriman otomatis');
            }
        });
    }

    // Function to load barang by toko
    function loadBarangByToko(tokoId) {
        $.ajax({
            url: '/pengiriman/get-barang-by-toko',
            type: 'GET',
            data: {
                toko_id: tokoId
            },
            success: function(response) {
                $('#barang_id').empty().append('<option value="">-- Pilih Barang --</option>');
                
                if (response.data.length > 0) {
                    $.each(response.data, function(index, item) {
                        $('#barang_id').append(
                            $('<option></option>')
                                .attr('value', item.barang_id)
                                .attr('data-satuan', item.satuan)
                                .text(item.barang_kode + ' - ' + item.nama_barang)
                        );
                    });
                } else {
                    showAlert('warning', 'Tidak ada barang yang terdaftar untuk toko ini');
                }
                
                // Refresh select2
                if ($.fn.select2) {
                    $('#barang_id').trigger('change');
                }
            },
            error: function() {
                showAlert('danger', 'Gagal memuat data barang untuk toko ini');
            }
        });
    }

    // Reset form
    function resetForm() {
        $('#formTambahPengiriman')[0].reset();
        $('#toko_id').val('').trigger('change');
        $('#barang_id').val('').prop('disabled', true);
        $('#tanggal_pengiriman').val(today);
        clearErrors();
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

    // --------- MAIN FUNCTION FOR LOADING DATA ----------

    // Function to load data with AJAX and update the table
    function loadPengirimanData() {
        // Show loading indicator
        $('#table-pengiriman tbody').html('<tr><td colspan="8" class="text-center">Loading data...</td></tr>');
        
        // Prepare filter parameters
        var filterParams = {
            toko_id: $('#filter_toko_id').val(),
            status: $('#filter_status').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        };
        
        // Make AJAX request
        $.ajax({
            url: '/pengiriman/list', // Make sure the endpoint returns JSON data
            type: 'GET',
            data: filterParams,
            success: function(response) {
                // Clear the table body
                $('#table-pengiriman tbody').empty();
                
                if (response.data.length === 0) {
                    // Handle empty data
                    $('#table-pengiriman tbody').html('<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>');
                    return;
                }
                
                // Loop through the data and append rows to the table
                $.each(response.data, function(index, item) {
                    // Create formatted date
                    var tanggal = new Date(item.tanggal_pengiriman);
                    var formattedTanggal = tanggal.getDate() + '/' + (tanggal.getMonth() + 1) + '/' + tanggal.getFullYear();
                    
                    // Format the status label
                    var statusLabel;
                    if (item.status === 'proses') {
                        statusLabel = '<span class="badge badge-warning">Proses</span>';
                    } else if (item.status === 'terkirim') {
                        statusLabel = '<span class="badge badge-success">Terkirim</span>';
                    } else {
                        statusLabel = '<span class="badge badge-danger">Batal</span>';
                    }
                    
                    // Create action buttons
                    var actionButtons = `
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info btn-edit" data-id="${item.pengiriman_id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning btn-status" data-id="${item.pengiriman_id}" data-nomer="${item.nomer_pengiriman}" data-status="${item.status}" title="Update Status">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="${item.pengiriman_id}" data-nomer="${item.nomer_pengiriman}" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    
                    // Create row
                    var row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.nomer_pengiriman}</td>
                            <td>${formattedTanggal}</td>
                            <td>${item.toko ? item.toko.nama_toko : ''}</td>
                            <td>${item.barang ? item.barang.nama_barang : ''}</td>
                            <td>${item.jumlah_kirim} ${item.barang ? item.barang.satuan : ''}</td>
                            <td>${statusLabel}</td>
                            <td>${actionButtons}</td>
                        </tr>
                    `;
                    
                    // Append row to table
                    $('#table-pengiriman tbody').append(row);
                });
                
                // Add pagination if needed
                updatePagination(response);
            },
            error: function() {
                showAlert('danger', 'Gagal memuat data pengiriman');
                $('#table-pengiriman tbody').html('<tr><td colspan="8" class="text-center">Error loading data</td></tr>');
            }
        });
    }
    
    // Function to update pagination
    function updatePagination(response) {
        // If you want to implement custom pagination, you can do it here
        // Example: Create pagination links based on response.current_page, response.last_page, etc.
        if (response.last_page > 1) {
            var paginationHtml = '<ul class="pagination justify-content-center">';
            
            // Previous page link
            if (response.current_page > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${response.current_page - 1}">Previous</a></li>`;
            } else {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }
            
            // Page number links
            for (var i = 1; i <= response.last_page; i++) {
                if (i === response.current_page) {
                    paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
            }
            
            // Next page link
            if (response.current_page < response.last_page) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${response.current_page + 1}">Next</a></li>`;
            } else {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }
            
            paginationHtml += '</ul>';
            
            // Append pagination to a container (you need to add this container to your HTML)
            $('#pagination-container').html(paginationHtml);
        } else {
            $('#pagination-container').empty();
        }
    }
    
    // Handle pagination clicks
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        
        // Add page to filter parameters
        $('#current_page').val(page);
        
        // Reload data with new page
        loadPengirimanData();
    });
});
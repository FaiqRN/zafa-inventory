$(function() {
    // Initialize DataTable
    var table = $('#customerTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '/customer/data',
            type: 'GET',
        },
        columns: [
            { data: 'no', name: 'no' },
            { data: 'nama', name: 'nama' },
            { data: 'gender', name: 'gender' },
            { data: 'usia', name: 'usia' },
            { data: 'alamat', name: 'alamat' },
            { data: 'email', name: 'email' },
            { data: 'no_tlp', name: 'no_tlp' },
            { data: 'source', name: 'source' },
            {
                data: 'customer_id',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info btn-edit" data-id="${data}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="${data}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'asc']]
    });

    // Fungsi untuk refresh data table
    function refreshData() {
        table.ajax.reload(null, false); // null callback, false to maintain pagination
    }

    // Auto refresh setiap 30 detik
    var refreshInterval = setInterval(function() {
        refreshData();
    }, 30000); // 30 detik

    // Manual refresh button
    $('#btnRefresh').click(function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i>');
        refreshData();
        setTimeout(function() {
            $('#btnRefresh').html('<i class="fas fa-sync-alt"></i> Refresh Data');
        }, 1000);
    });

    // Show modal for adding new customer
    $('#btnTambah').click(function() {
        resetForm();
        $('#modalCustomerLabel').text('Tambah Data Customer');
        $('#modalCustomer').modal('show');
    });

    // Show import modal
    $('#btnImport').click(function() {
        $('#formImport')[0].reset();
        $('.custom-file-label').text('Pilih file');
        $('#modalImport').modal('show');
    });

    // Debug Tables
    $('#btnDebugTables').click(function() {
        $.ajax({
            url: '/customer/debug-tables',
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                $('#btnDebugTables').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            },
            success: function(response) {
                // Format JSON data for display
                $('#pemesananColumns').text(JSON.stringify(response.pemesanan_columns, null, 2));
                $('#pemesananSamples').text(JSON.stringify(response.pemesanan_samples, null, 2));
                $('#customerColumns').text(JSON.stringify(response.customer_columns, null, 2));
                $('#customerSamples').text(JSON.stringify(response.customer_samples, null, 2));
                
                // Show the debug modal
                $('#modalDebug').modal('show');
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan saat mengambil data debug'
                });
            },
            complete: function() {
                $('#btnDebugTables').prop('disabled', false).html('<i class="fas fa-bug"></i> Debug Tables');
            }
        });
    });

    // Sync from pemesanan
    $('#btnSyncPemesanan').click(function() {
        $.ajax({
            url: '/customer/sync-pemesanan',
            type: 'POST',
            dataType: 'json',
            beforeSend: function() {
                $('#btnSyncPemesanan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyinkronkan...');
            },
            success: function(response) {
                console.log('Sync response:', response); // Log untuk debugging
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message
                    });
                    refreshData();
                } else if (response.status === 'info') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Informasi',
                        text: response.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                console.error('Sync error:', xhr); // Log error untuk debugging
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyinkronkan data'
                });
            },
            complete: function() {
                $('#btnSyncPemesanan').prop('disabled', false).html('<i class="fas fa-sync"></i> Sinkronkan dari Pemesanan');
            }
        });
    });

    // Handle file input change
    $('#file').change(function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').text(fileName);
    });

    // Handle import form submit
    $('#formImport').submit(function(e) {
        e.preventDefault();
        
        // Create FormData object
        var formData = new FormData(this);
        
        $.ajax({
            url: '/customer/import',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('#btnSimpanImport').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mengimpor...');
            },
            success: function(response) {
                console.log('Import response:', response); // Log untuk debugging
                
                if (response.status === 'success') {
                    $('#modalImport').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message
                    });
                    refreshData();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                console.error('Import error:', xhr); // Log error untuk debugging
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan saat mengimpor data'
                });
            },
            complete: function() {
                $('#btnSimpanImport').prop('disabled', false).html('Import');
            }
        });
    });
    
    // Show edit modal
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: '/customer/' + id + '/edit',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    resetForm();
                    
                    $('#customer_id').val(response.data.customer_id);
                    $('#nama').val(response.data.nama);
                    $('#gender').val(response.data.gender);
                    $('#usia').val(response.data.usia);
                    $('#alamat').val(response.data.alamat);
                    $('#email').val(response.data.email);
                    $('#no_tlp').val(response.data.no_tlp);
                    
                    $('#modalCustomerLabel').text('Edit Data Customer');
                    $('#modalCustomer').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                console.error('Edit error:', xhr); // Log error untuk debugging
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan saat mengambil data'
                });
            }
        });
    });
    
    // Handle delete
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data customer akan dihapus!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/customer/' + id,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message
                            });
                            refreshData();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Delete error:', xhr); // Log error untuk debugging
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus data'
                        });
                    }
                });
            }
        });
    });
    
    // Handle form submit (add/edit)
    $('#formCustomer').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var customerId = $('#customer_id').val();
        var url = customerId ? '/customer/' + customerId : '/customer';
        var method = customerId ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            type: method,
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#btnSimpan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            },
            success: function(response) {
                console.log('Save response:', response); // Log untuk debugging
                
                if (response.status === 'success') {
                    $('#modalCustomer').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message
                    });
                    refreshData();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                console.error('Save error:', xhr); // Log error untuk debugging
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data'
                });
            },
            complete: function() {
                $('#btnSimpan').prop('disabled', false).html('Simpan');
            }
        });
    });
    
    // Reset form
    function resetForm() {
        $('#formCustomer')[0].reset();
        $('#customer_id').val('');
    }
    
    // Pause auto-refresh when any modal is open
    $('.modal').on('show.bs.modal', function() {
        clearInterval(refreshInterval);
    });
    
    // Resume auto-refresh when all modals are closed
    $('.modal').on('hidden.bs.modal', function() {
        if ($('.modal:visible').length === 0) {
            refreshInterval = setInterval(function() {
                refreshData();
            }, 10000);
        }
    });
});
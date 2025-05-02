$(document).ready(function() {
    // Inisialisasi - load data toko
    loadTokoData();
    
    // Fungsi untuk memuat data toko dengan AJAX murni
    function loadTokoData() {
        $.ajax({
            url: '/toko/list',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function() {
                $('#toko-table-body').html('<tr><td colspan="8" class="text-center">Memuat data...</td></tr>');
            },
            success: function(response) {
                if (response.data.length > 0) {
                    let tableHtml = '';
                    
                    $.each(response.data, function(index, item) {
                        tableHtml += `
                            <tr id="row-${item.toko_id}">
                                <td>${index + 1}</td>
                                <td>${item.toko_id}</td>
                                <td>${item.nama_toko}</td>
                                <td>${item.pemilik}</td>
                                <td>${item.alamat}</td>
                                <td>${item.wilayah_kelurahan}, ${item.wilayah_kecamatan}, ${item.wilayah_kota_kabupaten}</td>
                                <td>${item.nomer_telpon}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="${item.toko_id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="${item.toko_id}" data-name="${item.nama_toko}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#toko-table-body').html(tableHtml);
                } else {
                    $('#toko-table-body').html('<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>');
                }
            },
            error: function() {
                $('#toko-table-body').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data</td></tr>');
                showAlert('danger', 'Gagal memuat data toko. Silahkan coba lagi.');
            }
        });
    }

    // Tampilkan modal tambah toko
    $('#btnTambah').click(function() {
        resetForm();
        $('#modalTokoLabel').text('Tambah Toko');
        $('#mode').val('add');
        
        // Generate toko_id otomatis
        generateTokoId();
        
        $('#modalToko').modal('show');
    });

    // Fungsi untuk generate ID toko otomatis
    function generateTokoId() {
        $.ajax({
            url: '/toko/generate-kode',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#toko_id').val(response.kode);
                }
            },
            error: function() {
                showAlert('warning', 'Gagal generate ID toko otomatis');
            }
        });
    }

    // Submit form tambah/edit toko
    $('#formToko').submit(function(e) {
        e.preventDefault();
        
        // Hapus validasi error sebelumnya
        clearErrors();
        
        // Cek mode (tambah/edit)
        var mode = $('#mode').val();
        var url = mode === 'add' ? '/toko' : '/toko/' + $('#toko_id').val();
        var method = mode === 'add' ? 'POST' : 'PUT';
        
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            cache: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                // Sembunyikan modal
                $('#modalToko').modal('hide');
                
                // Reload data toko tanpa refresh halaman
                loadTokoData();
                
                // Tampilkan notifikasi
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    showValidationErrors(errors);
                } else {
                    showAlert('danger', xhr.responseJSON.message || 'Terjadi kesalahan! Silahkan coba lagi.');
                }
            }
        });
    });

    // Ambil data untuk edit
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        
        // Reset form dan validasi
        resetForm();
        clearErrors();
        
        $.ajax({
            url: '/toko/' + id + '/edit',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                $('#modalTokoLabel').text('Edit Toko');
                $('#mode').val('edit');
                
                // Isi form dengan data
                $('#toko_id').val(response.data.toko_id);
                $('#nama_toko').val(response.data.nama_toko);
                $('#pemilik').val(response.data.pemilik);
                $('#alamat').val(response.data.alamat);
                $('#wilayah_kelurahan').val(response.data.wilayah_kelurahan);
                $('#wilayah_kecamatan').val(response.data.wilayah_kecamatan);
                $('#wilayah_kota_kabupaten').val(response.data.wilayah_kota_kabupaten);
                $('#nomer_telpon').val(response.data.nomer_telpon);
                
                $('#modalToko').modal('show');
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON.message || 'Gagal mengambil data toko');
            }
        });
    });

    // Setup untuk hapus toko
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#delete-item-name').text(name);
        $('#btnDelete').data('id', id);
        $('#deleteModal').modal('show');
    });

    // Proses hapus toko
    $('#btnDelete').click(function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: '/toko/' + id,
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
                // Sembunyikan modal konfirmasi
                $('#deleteModal').modal('hide');
                
                // Reload data toko tanpa refresh halaman
                loadTokoData();
                
                // Tampilkan pesan sukses
                showAlert('success', response.message);
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                
                // Check if we have a specific error message
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Gagal menghapus data toko');
                }
            }
        });
    });

    // Reset form
    function resetForm() {
        $('#formToko')[0].reset();
        clearErrors();
    }

    // Hapus semua pesan error validasi
    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    // Tampilkan error validasi
    function showValidationErrors(errors) {
        $.each(errors, function(field, messages) {
            $('#' + field).addClass('is-invalid');
            $('#error-' + field).text(messages[0]);
        });
    }

    // Tampilkan alert
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
        
        // Otomatis hilangkan alert setelah 5 detik
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
$(document).ready(function() {
    // Inisialisasi tabel tanpa DataTables
    loadBarangData();
    
    // Fungsi untuk memuat data barang
    function loadBarangData() {
        $.ajax({
            url: '/barang/list',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function() {
                $('#barang-table-body').html('<tr><td colspan="7" class="text-center">Memuat data...</td></tr>');
            },
            success: function(response) {
                if (response.data.length > 0) {
                    let tableHtml = '';
                    
                    $.each(response.data, function(index, item) {
                        tableHtml += `
                            <tr id="row-${item.barang_id}">
                                <td>${index + 1}</td>
                                <td>${item.barang_kode}</td>
                                <td>${item.nama_barang}</td>
                                <td>Rp ${formatRupiah(item.harga_awal_barang)}</td>
                                <td>${item.satuan}</td>
                                <td>${item.keterangan ? item.keterangan : '-'}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="${item.barang_id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="${item.barang_id}" data-name="${item.nama_barang}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#barang-table-body').html(tableHtml);
                } else {
                    $('#barang-table-body').html('<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>');
                }
            },
            error: function() {
                $('#barang-table-body').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data</td></tr>');
                showAlert('danger', 'Gagal memuat data barang. Silahkan coba lagi.');
            }
        });
    }

    // Tampilkan modal tambah barang
    $('#btnTambah').click(function() {
        resetForm();
        $('#modalBarangLabel').text('Tambah Barang');
        
        // Generate kode barang otomatis
        generateBarangKode();
        
        $('#modalBarang').modal('show');
    });

    // Fungsi untuk generate kode barang otomatis
    function generateBarangKode() {
        $.ajax({
            url: '/barang/generate-kode',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#barang_kode').val(response.kode);
                }
            },
            error: function() {
                showAlert('warning', 'Gagal generate kode barang otomatis');
            }
        });
    }

    // Tambah dan Edit Barang
    $('#formBarang').submit(function(e) {
        e.preventDefault();
        
        // Hapus validasi error sebelumnya
        clearErrors();
        
        // Cek mode (tambah/edit)
        var mode = $('#barang_id').val() ? 'edit' : 'add';
        var url = mode === 'add' ? '/barang/store' : '/barang/update/' + $('#barang_id').val();
        var method = mode === 'add' ? 'POST' : 'PUT';
        
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                // Sembunyikan modal
                $('#modalBarang').modal('hide');
                
                // Reload data barang
                loadBarangData();
                
                // Tampilkan notifikasi
                showAlert('success', response.message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    showValidationErrors(errors);
                } else {
                    showAlert('danger', 'Terjadi kesalahan! Silahkan coba lagi.');
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
            url: '/barang/' + id + '/edit',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            success: function(response) {
                $('#modalBarangLabel').text('Edit Barang');
                
                // Isi form dengan data
                $('#barang_id').val(response.data.barang_id);
                $('#barang_kode').val(response.data.barang_kode);
                $('#nama_barang').val(response.data.nama_barang);
                $('#harga_awal_barang').val(response.data.harga_awal_barang);
                $('#satuan').val(response.data.satuan);
                $('#keterangan').val(response.data.keterangan);
                
                $('#modalBarang').modal('show');
            },
            error: function() {
                showAlert('danger', 'Gagal mengambil data barang');
            }
        });
    });

    // Setup untuk hapus barang
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#delete-item-name').text(name);
        $('#btnDelete').data('id', id);
        $('#deleteModal').modal('show');
    });

    // Proses hapus barang (soft delete)
    $('#btnDelete').click(function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: '/barang/destroy/' + id,
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
                
                // Reload data barang
                loadBarangData();
                
                // Tampilkan pesan sukses
                showAlert('success', response.message);
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                
                // Check if we have a specific error message
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Gagal menghapus data barang');
                }
            }
        });
    });

    // Format angka ke format rupiah
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    // Reset form
    function resetForm() {
        $('#formBarang')[0].reset();
        $('#barang_id').val('');
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
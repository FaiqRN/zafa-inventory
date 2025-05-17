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
        
        // Load data wilayah kota/kabupaten
        loadWilayahKota();
        
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
    
    // Fungsi untuk memuat data wilayah Kota/Kabupaten
    function loadWilayahKota() {
        $.ajax({
            url: '/toko/wilayah/kota',
            type: 'GET',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function() {
                $('#wilayah_kota_id').html('<option value="">Memuat data...</option>');
                $('#wilayah_kota_id').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kota/Kabupaten --</option>';
                    
                    $.each(response.data, function(index, item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    
                    $('#wilayah_kota_id').html(options);
                    $('#wilayah_kota_id').prop('disabled', false);
                }
            },
            error: function() {
                $('#wilayah_kota_id').html('<option value="">-- Pilih Kota/Kabupaten --</option>');
                $('#wilayah_kota_id').prop('disabled', false);
                showAlert('warning', 'Gagal memuat data wilayah');
            }
        });
    }
    
    // Event listener untuk perubahan pilihan Kota/Kabupaten
    $(document).on('change', '#wilayah_kota_id', function() {
        const kotaId = $(this).val();
        const kotaNama = $(this).find('option:selected').data('nama') || '';
        
        // Set nilai tersembunyi untuk kota/kabupaten
        $('#wilayah_kota_kabupaten').val(kotaNama);
        
        // Reset dropdown kecamatan dan kelurahan
        $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
        $('#wilayah_kecamatan').val('');
        $('#wilayah_kelurahan').val('');
        
        if (kotaId) {
            // Load data kecamatan berdasarkan kota yang dipilih
            loadKecamatan(kotaId);
            $('#wilayah_kecamatan_id').prop('disabled', false);
        } else {
            $('#wilayah_kecamatan_id').prop('disabled', true);
            $('#wilayah_kelurahan_id').prop('disabled', true);
        }
    });
    
    // Fungsi untuk memuat data Kecamatan berdasarkan Kota
    function loadKecamatan(kotaId) {
        $.ajax({
            url: '/toko/wilayah/kecamatan',
            type: 'GET',
            data: {
                kota_id: kotaId
            },
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function() {
                $('#wilayah_kecamatan_id').html('<option value="">Memuat data...</option>');
                $('#wilayah_kecamatan_id').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kecamatan --</option>';
                    
                    $.each(response.data, function(index, item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    
                    $('#wilayah_kecamatan_id').html(options);
                    $('#wilayah_kecamatan_id').prop('disabled', false);
                }
            },
            error: function() {
                $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
                $('#wilayah_kecamatan_id').prop('disabled', false);
                showAlert('warning', 'Gagal memuat data kecamatan');
            }
        });
    }
    
    // Event listener untuk perubahan pilihan Kecamatan
    $(document).on('change', '#wilayah_kecamatan_id', function() {
        const kotaId = $('#wilayah_kota_id').val();
        const kecamatanId = $(this).val();
        const kecamatanNama = $(this).find('option:selected').data('nama') || '';
        
        // Set nilai tersembunyi untuk kecamatan
        $('#wilayah_kecamatan').val(kecamatanNama);
        
        // Reset dropdown kelurahan
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
        $('#wilayah_kelurahan').val('');
        
        if (kotaId && kecamatanId) {
            // Load data kelurahan berdasarkan kecamatan yang dipilih
            loadKelurahan(kotaId, kecamatanId);
            $('#wilayah_kelurahan_id').prop('disabled', false);
        } else {
            $('#wilayah_kelurahan_id').prop('disabled', true);
        }
    });
    
    // Fungsi untuk memuat data Kelurahan berdasarkan Kecamatan
    function loadKelurahan(kotaId, kecamatanId) {
        $.ajax({
            url: '/toko/wilayah/kelurahan',
            type: 'GET',
            data: {
                kota_id: kotaId,
                kecamatan_id: kecamatanId
            },
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            beforeSend: function() {
                $('#wilayah_kelurahan_id').html('<option value="">Memuat data...</option>');
                $('#wilayah_kelurahan_id').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kelurahan --</option>';
                    
                    $.each(response.data, function(index, item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    
                    $('#wilayah_kelurahan_id').html(options);
                    $('#wilayah_kelurahan_id').prop('disabled', false);
                }
            },
            error: function() {
                $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
                $('#wilayah_kelurahan_id').prop('disabled', false);
                showAlert('warning', 'Gagal memuat data kelurahan');
            }
        });
    }
    
    // Event listener untuk perubahan pilihan Kelurahan
    $(document).on('change', '#wilayah_kelurahan_id', function() {
        const kelurahanNama = $(this).find('option:selected').data('nama') || '';
        
        // Set nilai tersembunyi untuk kelurahan
        $('#wilayah_kelurahan').val(kelurahanNama);
    });

    // Submit form tambah/edit toko
    $('#formToko').submit(function(e) {
        e.preventDefault();
        
        // Hapus validasi error sebelumnya
        clearErrors();
        
        // Validasi apakah dropdown lokasi sudah dipilih
        if (!$('#wilayah_kota_kabupaten').val()) {
            $('#wilayah_kota_id').addClass('is-invalid');
            $('#error-wilayah_kota_kabupaten').text('Kota/Kabupaten harus dipilih');
            return false;
        }
        
        if (!$('#wilayah_kecamatan').val()) {
            $('#wilayah_kecamatan_id').addClass('is-invalid');
            $('#error-wilayah_kecamatan').text('Kecamatan harus dipilih');
            return false;
        }
        
        if (!$('#wilayah_kelurahan').val()) {
            $('#wilayah_kelurahan_id').addClass('is-invalid');
            $('#error-wilayah_kelurahan').text('Kelurahan harus dipilih');
            return false;
        }
        
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
        
        // Load data wilayah kota terlebih dahulu
        loadWilayahKota();
        
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
                $('#nomer_telpon').val(response.data.nomer_telpon);
                
                // Simpan sementara nilai wilayah untuk diisi pada dropdown setelah data dropdown dimuat
                const kotaKabupaten = response.data.wilayah_kota_kabupaten;
                const kecamatan = response.data.wilayah_kecamatan;
                const kelurahan = response.data.wilayah_kelurahan;
                
                // Set nilai tersembunyi
                $('#wilayah_kota_kabupaten').val(kotaKabupaten);
                $('#wilayah_kecamatan').val(kecamatan);
                $('#wilayah_kelurahan').val(kelurahan);
                
                // Setelah dropdown kota dimuat, pilih kota yang sesuai
                const waitForKotaDropdown = setInterval(function() {
                    if ($('#wilayah_kota_id option').length > 1) {
                        clearInterval(waitForKotaDropdown);
                        
                        // Cari ID kota berdasarkan nama
                        $('#wilayah_kota_id option').each(function() {
                            if ($(this).text() === kotaKabupaten) {
                                const kotaId = $(this).val();
                                $('#wilayah_kota_id').val(kotaId).trigger('change');
                                
                                // Setelah dropdown kecamatan dimuat, pilih kecamatan yang sesuai
                                const waitForKecamatanDropdown = setInterval(function() {
                                    if ($('#wilayah_kecamatan_id option').length > 1) {
                                        clearInterval(waitForKecamatanDropdown);
                                        
                                        // Cari ID kecamatan berdasarkan nama
                                        $('#wilayah_kecamatan_id option').each(function() {
                                            if ($(this).text() === kecamatan) {
                                                const kecamatanId = $(this).val();
                                                $('#wilayah_kecamatan_id').val(kecamatanId).trigger('change');
                                                
                                                // Setelah dropdown kelurahan dimuat, pilih kelurahan yang sesuai
                                                const waitForKelurahanDropdown = setInterval(function() {
                                                    if ($('#wilayah_kelurahan_id option').length > 1) {
                                                        clearInterval(waitForKelurahanDropdown);
                                                        
                                                        // Cari ID kelurahan berdasarkan nama
                                                        $('#wilayah_kelurahan_id option').each(function() {
                                                            if ($(this).text() === kelurahan) {
                                                                const kelurahanId = $(this).val();
                                                                $('#wilayah_kelurahan_id').val(kelurahanId).trigger('change');
                                                            }
                                                        });
                                                    }
                                                }, 100);
                                            }
                                        });
                                    }
                                }, 100);
                            }
                        });
                    }
                }, 100);
                
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
        
        // Reset semua dropdown wilayah
        $('#wilayah_kota_id').html('<option value="">-- Pilih Kota/Kabupaten --</option>');
        $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
        
        // Disable dropdown bawahan
        $('#wilayah_kecamatan_id').prop('disabled', true);
        $('#wilayah_kelurahan_id').prop('disabled', true);
        
        // Kosongkan hidden fields wilayah
        $('#wilayah_kota_kabupaten').val('');
        $('#wilayah_kecamatan').val('');
        $('#wilayah_kelurahan').val('');
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
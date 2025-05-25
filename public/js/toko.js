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
                $('#toko-table-body').html('<tr><td colspan="10" class="text-center">Memuat data...</td></tr>');
            },
            success: function(response) {
                if (response.data.length > 0) {
                    let tableHtml = '';
                    
                    $.each(response.data, function(index, item) {
                        // Status koordinat
                        let koordinatStatus = '';
                        let statusBadge = '';
                        
                        if (item.latitude && item.longitude) {
                            koordinatStatus = `<small class="text-success"><i class="fas fa-map-marker-alt"></i> ${parseFloat(item.latitude).toFixed(4)}, ${parseFloat(item.longitude).toFixed(4)}</small>`;
                            statusBadge = '<span class="badge badge-success">GPS Aktif</span>';
                        } else {
                            koordinatStatus = '<small class="text-muted"><i class="fas fa-map-marker"></i> Belum ada</small>';
                            statusBadge = '<span class="badge badge-warning">Perlu GPS</span>';
                        }
                        
                        tableHtml += `
                            <tr id="row-${item.toko_id}">
                                <td>${index + 1}</td>
                                <td><strong>${item.toko_id}</strong></td>
                                <td>${item.nama_toko}</td>
                                <td>${item.pemilik}</td>
                                <td>${item.alamat}</td>
                                <td><small>${item.wilayah_kelurahan}, ${item.wilayah_kecamatan}, ${item.wilayah_kota_kabupaten}</small></td>
                                <td>${item.nomer_telpon}</td>
                                <td>${koordinatStatus}</td>
                                <td>${statusBadge}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-xs btn-info btn-edit" data-id="${item.toko_id}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-warning btn-geocode" data-id="${item.toko_id}" title="Update GPS">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-success btn-detail-koordinat" data-id="${item.toko_id}" title="Detail Koordinat" ${item.latitude && item.longitude ? '' : 'style="display:none"'}>
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-danger btn-delete" data-id="${item.toko_id}" data-name="${item.nama_toko}" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#toko-table-body').html(tableHtml);
                } else {
                    $('#toko-table-body').html('<tr><td colspan="10" class="text-center">Tidak ada data</td></tr>');
                }
            },
            error: function() {
                $('#toko-table-body').html('<tr><td colspan="10" class="text-center text-danger">Gagal memuat data</td></tr>');
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

    // Event untuk batch geocoding - PENTING!
    $('#btnBatchGeocode').click(function() {
        console.log('Batch geocoding button clicked');
        if ($('#modalBatchGeocode').length) {
            $('#modalBatchGeocode').modal('show');
        } else {
            // Jika modal belum ada, tampilkan konfirmasi sederhana
            if (confirm('Lakukan batch geocoding untuk semua toko yang belum memiliki koordinat GPS?')) {
                startBatchGeocode();
            }
        }
    });

    // Event untuk geocode manual toko - PENTING!
    $(document).on('click', '.btn-geocode', function() {
        console.log('Geocode button clicked');
        var id = $(this).data('id');
        geocodeToko(id);
    });

    // Event untuk detail koordinat toko - PENTING!
    $(document).on('click', '.btn-detail-koordinat', function() {
        console.log('Detail koordinat button clicked');
        var id = $(this).data('id');
        showDetailKoordinat(id);
    });

    // Fungsi untuk geocode toko secara manual
    function geocodeToko(tokoId) {
        console.log('Starting geocode for toko:', tokoId);
        
        // Get toko data first
        $.ajax({
            url: '/toko/' + tokoId,
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const toko = response.data;
                    const fullAddress = `${toko.alamat}, ${toko.wilayah_kelurahan}, ${toko.wilayah_kecamatan}, ${toko.wilayah_kota_kabupaten}, Indonesia`;
                    
                    // Confirm geocoding
                    if (confirm(`Lakukan geocoding untuk toko ${toko.nama_toko}?\n\nAlamat: ${fullAddress}`)) {
                        performGeocodeToko(tokoId, fullAddress);
                    }
                }
            },
            error: function() {
                showAlert('danger', 'Gagal mengambil data toko');
            }
        });
    }

    // Fungsi untuk melakukan geocoding toko
    function performGeocodeToko(tokoId, address) {
        showAlert('info', 'Sedang melakukan geocoding...');
        
        $.ajax({
            url: '/toko/geocode',
            type: 'POST',
            data: {
                toko_id: tokoId,
                alamat: address,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    loadTokoData(); // Reload data
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                console.error('Geocoding error:', xhr);
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert('danger', xhr.responseJSON.message);
                } else {
                    showAlert('danger', 'Gagal melakukan geocoding. Periksa koneksi internet.');
                }
            }
        });
    }

    // Fungsi untuk menampilkan detail koordinat
    function showDetailKoordinat(tokoId) {
        console.log('Showing detail koordinat for:', tokoId);
        
        $.ajax({
            url: '/toko/' + tokoId,
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const toko = response.data;
                    
                    if (!toko.latitude || !toko.longitude) {
                        showAlert('warning', 'Toko ini belum memiliki koordinat GPS');
                        return;
                    }
                    
                    let content = `
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">${toko.nama_toko} (${toko.toko_id})</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Koordinat GPS:</strong><br>
                                        <code class="h5">${toko.latitude}, ${toko.longitude}</code>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Status:</strong><br>
                                        <span class="badge badge-success">GPS Aktif</span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <strong>Alamat Input:</strong><br>
                                        ${toko.alamat}<br>
                                        ${toko.wilayah_kelurahan}, ${toko.wilayah_kecamatan}, ${toko.wilayah_kota_kabupaten}
                                    </div>
                                </div>
                                
                                ${toko.alamat_lengkap_geocoding ? `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <strong>Alamat Hasil Geocoding:</strong><br>
                                        <small class="text-muted">${toko.alamat_lengkap_geocoding}</small>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <strong>Tautan Google Maps:</strong><br>
                                        <a href="https://www.google.com/maps?q=${toko.latitude},${toko.longitude}" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                                        </a>
                                        <button type="button" class="btn btn-sm btn-warning ml-2" onclick="geocodeToko('${toko.toko_id}')">
                                            <i class="fas fa-sync-alt"></i> Update Koordinat
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Jika ada modal, gunakan modal
                    if ($('#modalDetailKoordinat').length) {
                        $('#detail-koordinat-content').html(content);
                        $('#btnUpdateKoordinat').data('toko-id', tokoId);
                        $('#modalDetailKoordinat').modal('show');
                    } else {
                        // Jika tidak ada modal, tampilkan dengan alert
                        const simpleInfo = `
                            Koordinat GPS: ${toko.latitude}, ${toko.longitude}\n
                            Alamat: ${toko.alamat}, ${toko.wilayah_kelurahan}, ${toko.wilayah_kecamatan}, ${toko.wilayah_kota_kabupaten}\n
                            \nKlik OK untuk membuka di Google Maps
                        `;
                        
                        if (confirm(simpleInfo)) {
                            window.open(`https://www.google.com/maps?q=${toko.latitude},${toko.longitude}`, '_blank');
                        }
                    }
                }
            },
            error: function() {
                showAlert('danger', 'Gagal mengambil detail koordinat');
            }
        });
    }

    // Fungsi untuk batch geocoding
    function startBatchGeocode() {
        console.log('Starting batch geocoding...');
        
        // Show loading alert
        showAlert('info', 'Memulai batch geocoding... Mohon tunggu.');
        
        $.ajax({
            url: '/toko/batch-geocode',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            timeout: 300000, // 5 minutes timeout
            success: function(response) {
                console.log('Batch geocoding response:', response);
                
                if (response.status === 'success') {
                    const summary = response.summary;
                    const message = `
                        Batch Geocoding Selesai!<br>
                        <strong>Berhasil:</strong> ${summary.success_count}<br>
                        <strong>Gagal:</strong> ${summary.failed_count}<br>
                        <strong>Total:</strong> ${summary.total_processed}
                    `;
                    showAlert('success', message);
                    
                    // Reload data toko
                    loadTokoData();
                } else {
                    showAlert('warning', response.message || 'Batch geocoding selesai dengan beberapa error');
                }
            },
            error: function(xhr) {
                console.error('Batch geocoding error:', xhr);
                if (xhr.status === 404) {
                    showAlert('danger', 'Endpoint batch geocoding tidak ditemukan. Pastikan route sudah ditambahkan.');
                } else {
                    showAlert('danger', 'Terjadi kesalahan saat batch geocoding. Periksa koneksi internet dan coba lagi.');
                }
            }
        });
    }

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
        
        // Disable submit button dan show loading
        $('#btnSimpan').html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        $('#btnSimpan').prop('disabled', true);
        
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
                
                // Tampilkan notifikasi dengan info geocoding jika ada
                let message = response.message;
                if (response.geocode_info) {
                    message += `<br><small class="text-muted">Koordinat GPS: ${response.geocode_info.latitude}, ${response.geocode_info.longitude}</small>`;
                }
                showAlert('success', message);
            },
            error: function(xhr) {
                console.error('Submit error:', xhr);
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    showValidationErrors(errors);
                } else {
                    showAlert('danger', xhr.responseJSON.message || 'Terjadi kesalahan! Silahkan coba lagi.');
                }
            },
            complete: function() {
                // Reset submit button
                $('#btnSimpan').html('<i class="fas fa-save"></i> Simpan');
                $('#btnSimpan').prop('disabled', false);
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
        
        $('#btnDelete').html('<i class="fas fa-spinner fa-spin"></i> Menghapus...');
        $('#btnDelete').prop('disabled', true);
        
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
            },
            complete: function() {
                $('#btnDelete').html('<i class="fas fa-trash"></i> Hapus');
                $('#btnDelete').prop('disabled', false);
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
        
        // Hide geocoding info dan preview button jika ada
        if ($('#geocoding-info').length) {
            $('#geocoding-info').hide();
        }
        if ($('#btnPreviewGeocode').length) {
            $('#btnPreviewGeocode').hide();
        }
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

    // Event untuk modal batch geocoding (jika ada)
    if ($('#btnStartBatchGeocode').length) {
        $('#btnStartBatchGeocode').click(function() {
            startBatchGeocode();
        });
    }

    // Event untuk update koordinat dari modal detail (jika ada)
    if ($('#btnUpdateKoordinat').length) {
        $('#btnUpdateKoordinat').click(function() {
            const tokoId = $(this).data('toko-id');
            $('#modalDetailKoordinat').modal('hide');
            geocodeToko(tokoId);
        });
    }

    // Event untuk preview geocoding (jika ada fitur preview)
    if ($('#btnPreviewGeocode').length) {
        $('#btnPreviewGeocode').click(function() {
            previewGeocode();
        });
    }

    // Event untuk perubahan alamat (jika ada fitur preview)
    $('#alamat').on('input', function() {
        if ($('#geocoding-info').length) {
            $('#geocoding-info').hide();
        }
        if ($('#btnPreviewGeocode').length) {
            $('#btnPreviewGeocode').hide();
            
            // Show preview button if all location fields are filled
            if ($(this).val() && $('#wilayah_kota_kabupaten').val() && 
                $('#wilayah_kecamatan').val() && $('#wilayah_kelurahan').val()) {
                $('#btnPreviewGeocode').show();
            }
        }
    });

    // Fungsi untuk preview geocoding sebelum menyimpan (opsional)
    function previewGeocode() {
        const alamat = $('#alamat').val();
        const kelurahan = $('#wilayah_kelurahan').val();
        const kecamatan = $('#wilayah_kecamatan').val();
        const kotaKab = $('#wilayah_kota_kabupaten').val();
        
        if (!alamat || !kelurahan || !kecamatan || !kotaKab) {
            showAlert('warning', 'Lengkapi semua field alamat terlebih dahulu');
            return;
        }
        
        const fullAddress = `${alamat}, ${kelurahan}, ${kecamatan}, ${kotaKab}, Indonesia`;
        
        $('#btnPreviewGeocode').html('<i class="fas fa-spinner fa-spin"></i> Mencari...');
        $('#btnPreviewGeocode').prop('disabled', true);
        
        // Test geocoding via AJAX
        $.ajax({
            url: '/toko/preview-geocode',
            type: 'POST',
            data: {
                alamat: fullAddress,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success' && response.geocode_info) {
                    const info = response.geocode_info;
                    if ($('#geocoding-result').length) {
                        $('#geocoding-result').html(`
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Koordinat:</strong><br>
                                    <code>${info.latitude}, ${info.longitude}</code>
                                </div>
                                <div class="col-md-6">
                                    <strong>Provider:</strong> ${info.provider}<br>
                                    <strong>Akurasi:</strong> ${info.accuracy}
                                </div>
                            </div>
                            <div class="mt-2">
                                <strong>Alamat Terformat:</strong><br>
                                <small class="text-muted">${info.formatted_address}</small>
                            </div>
                        `);
                        $('#geocoding-info').show();
                    }
                    showAlert('success', 'Koordinat GPS berhasil ditemukan');
                } else {
                    if ($('#geocoding-result').length) {
                        $('#geocoding-result').html(`
                            <div class="text-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Koordinat GPS tidak dapat ditemukan untuk alamat ini.
                                <br><small>Toko akan tetap dapat disimpan, namun lokasi di Market Map mungkin tidak akurat.</small>
                            </div>
                        `);
                        $('#geocoding-info').show();
                    }
                    showAlert('warning', 'Koordinat GPS tidak ditemukan');
                }
            },
            error: function() {
                showAlert('danger', 'Gagal melakukan preview geocoding');
            },
            complete: function() {
                $('#btnPreviewGeocode').html('<i class="fas fa-search-location"></i> Preview Lokasi');
                $('#btnPreviewGeocode').prop('disabled', false);
            }
        });
    }

    // Make geocodeToko function global untuk akses dari modal dan inline onclick
    window.geocodeToko = geocodeToko;

    // Debug function untuk troubleshooting
    window.debugTokoJS = function() {
        console.log('=== DEBUG TOKO JS ===');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Available buttons:');
        console.log('- btnTambah:', $('#btnTambah').length);
        console.log('- btnBatchGeocode:', $('#btnBatchGeocode').length);
        console.log('- btn-geocode:', $('.btn-geocode').length);
        console.log('- btn-detail-koordinat:', $('.btn-detail-koordinat').length);
        console.log('- btn-edit:', $('.btn-edit').length);
        console.log('- btn-delete:', $('.btn-delete').length);
        console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
        console.log('===================');
    };

    // Call debug on load
    console.log('Toko.js loaded successfully with geocoding features');
    
    // Auto-call debug function in development (uncomment for debugging)
    // window.debugTokoJS();
});
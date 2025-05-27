$(document).ready(function() {
    // ========================================
    // INITIALIZATION
    // ========================================
    console.log('Loading Enhanced Toko Management System...');
    loadTokoData();

    // ========================================
    // MAIN DATA LOADING
    // ========================================
    function loadTokoData() {
        $.ajax({
            url: '/toko/list',
            type: 'GET',
            cache: false,
            beforeSend: function() {
                $('#toko-table-body').html('<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
            },
            success: function(response) {
                displayTokoData(response.data);
            },
            error: function() {
                $('#toko-table-body').html('<tr><td colspan="10" class="text-center text-danger">Gagal memuat data</td></tr>');
                showAlert('danger', 'Gagal memuat data toko');
            }
        });
    }

    function displayTokoData(data) {
        if (data.length === 0) {
            $('#toko-table-body').html('<tr><td colspan="10" class="text-center">Tidak ada data</td></tr>');
            return;
        }

        let tableHtml = '';
        data.forEach(function(item, index) {
            const hasCoords = item.latitude && item.longitude;
            const quality = item.geocoding_quality || 'unknown';
            
            // Status dan badge
            let statusBadge = '';
            let koordinatInfo = '';
            
            if (hasCoords) {
                const qualityClass = getQualityClass(quality);
                const qualityText = getQualityText(quality);
                statusBadge = `<span class="badge badge-${qualityClass}">${qualityText}</span>`;
                koordinatInfo = `
                    <div class="coordinate-info">
                        <small><i class="fas fa-map-marker-alt text-success"></i> ${parseFloat(item.latitude).toFixed(4)}, ${parseFloat(item.longitude).toFixed(4)}</small>
                        <br><small class="text-muted">${item.geocoding_provider || 'Unknown'}</small>
                    </div>
                `;
            } else {
                statusBadge = '<span class="badge badge-warning">Perlu GPS</span>';
                koordinatInfo = '<small class="text-muted"><i class="fas fa-map-marker"></i> Belum ada koordinat</small>';
            }

            tableHtml += `
                <tr id="row-${item.toko_id}">
                    <td>${index + 1}</td>
                    <td><strong>${item.toko_id}</strong></td>
                    <td>
                        <div>${item.nama_toko}</div>
                        <small class="text-muted">${item.pemilik}</small>
                    </td>
                    <td>
                        <div>${item.alamat}</div>
                        <small class="text-muted">${item.wilayah_kelurahan}, ${item.wilayah_kecamatan}</small>
                    </td>
                    <td><small>${item.wilayah_kota_kabupaten}</small></td>
                    <td>${item.nomer_telpon}</td>
                    <td>${koordinatInfo}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-info btn-edit" data-id="${item.toko_id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-warning btn-geocode" data-id="${item.toko_id}" title="Update GPS">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            ${hasCoords ? `
                                <button class="btn btn-success btn-detail" data-id="${item.toko_id}" title="Detail GPS">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <button class="btn btn-primary btn-maps" onclick="openGoogleMaps(${item.latitude}, ${item.longitude})" title="Maps">
                                    <i class="fas fa-external-link-alt"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-danger btn-delete" data-id="${item.toko_id}" data-name="${item.nama_toko}" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        $('#toko-table-body').html(tableHtml);
    }

    // ========================================
    // QUALITY HELPERS
    // ========================================
    function getQualityClass(quality) {
        const classes = {
            'excellent': 'success',
            'good': 'primary',
            'fair': 'warning',
            'poor': 'danger',
            'very poor': 'danger',
            'failed': 'secondary',
            'unknown': 'info'
        };
        return classes[quality] || 'info';
    }

    function getQualityText(quality) {
        const texts = {
            'excellent': 'Sangat Akurat',
            'good': 'Akurat',
            'fair': 'Cukup Akurat',
            'poor': 'Kurang Akurat',
            'very poor': 'Tidak Akurat',
            'failed': 'Gagal',
            'unknown': 'GPS Aktif'
        };
        return texts[quality] || 'GPS Aktif';
    }

    // ========================================
    // MODAL HANDLERS
    // ========================================
    $('#btnTambah').click(function() {
        resetForm();
        $('#modalTokoLabel').text('Tambah Toko');
        $('#mode').val('add');
        generateTokoId();
        loadWilayahKota();
        $('#modalToko').modal('show');
    });

    // ========================================
    // GEOCODING FUNCTIONS
    // ========================================
    
    // Individual Geocoding
    $(document).on('click', '.btn-geocode', function() {
        const id = $(this).data('id');
        geocodeSingleToko(id);
    });

    function geocodeSingleToko(tokoId) {
        // Get toko data first
        $.ajax({
            url: '/toko/' + tokoId,
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const toko = response.data;
                    const fullAddress = `${toko.alamat}, ${toko.wilayah_kelurahan}, ${toko.wilayah_kecamatan}, ${toko.wilayah_kota_kabupaten}, Jawa Timur, Indonesia`;
                    
                    if (confirm(`Lakukan geocoding untuk toko ${toko.nama_toko}?\n\nAlamat: ${fullAddress}`)) {
                        performGeocode(tokoId, fullAddress);
                    }
                }
            },
            error: function() {
                showAlert('danger', 'Gagal mengambil data toko');
            }
        });
    }

    function performGeocode(tokoId, address) {
        showAlert('info', '<i class="fas fa-spinner fa-spin"></i> Melakukan geocoding...');
        
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
                    let message = response.message;
                    if (response.geocode_info) {
                        const info = response.geocode_info;
                        message += `<br><small>Provider: ${info.provider} | Quality: ${info.quality} | Score: ${info.quality_score}/100</small>`;
                    }
                    showAlert('success', message);
                    loadTokoData();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal melakukan geocoding';
                showAlert('danger', message);
            }
        });
    }

    // Batch Geocoding
    $('#btnBatchGeocode').click(function() {
        showBatchGeocodeModal();
    });

    function showBatchGeocodeModal() {
        if (confirm('Lakukan batch geocoding untuk semua toko yang belum memiliki koordinat GPS akurat?\n\nProses ini mungkin memakan waktu beberapa menit.')) {
            startBatchGeocode();
        }
    }

    function startBatchGeocode() {
        showAlert('info', '<i class="fas fa-spinner fa-spin"></i> Memulai batch geocoding...');
        
        $.ajax({
            url: '/toko/batch-geocode',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            timeout: 300000, // 5 minutes
            success: function(response) {
                if (response.status === 'success') {
                    const summary = response.summary;
                    const message = `
                        Batch Geocoding Selesai!<br>
                        <strong>Berhasil:</strong> ${summary.success_count}<br>
                        <strong>Gagal:</strong> ${summary.failed_count}<br>
                        <strong>Total:</strong> ${summary.total_processed}<br>
                        <strong>Success Rate:</strong> ${summary.success_rate || 0}%
                    `;
                    showAlert('success', message);
                    loadTokoData();
                } else {
                    showAlert('warning', response.message);
                }
            },
            error: function(xhr) {
                let errorMsg = 'Terjadi kesalahan saat batch geocoding';
                if (xhr.status === 404) {
                    errorMsg = 'Endpoint batch geocoding tidak ditemukan';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error saat batch geocoding';
                }
                showAlert('danger', errorMsg);
            }
        });
    }

    // Preview Geocoding
    function previewGeocode() {
        const alamat = $('#alamat').val();
        const kelurahan = $('#wilayah_kelurahan').val();
        const kecamatan = $('#wilayah_kecamatan').val();
        const kotaKab = $('#wilayah_kota_kabupaten').val();
        
        if (!alamat || !kelurahan || !kecamatan || !kotaKab) {
            showAlert('warning', 'Lengkapi semua field alamat terlebih dahulu');
            return;
        }
        
        const fullAddress = `${alamat}, ${kelurahan}, ${kecamatan}, ${kotaKab}, Jawa Timur, Indonesia`;
        
        $('#btnPreviewGeocode').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mencari...');
        
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
                    showPreviewResult(info);
                    showAlert('success', `Koordinat ditemukan dengan kualitas ${info.quality}`);
                } else {
                    showPreviewError();
                    showAlert('warning', 'Koordinat tidak ditemukan');
                }
            },
            error: function() {
                showAlert('danger', 'Gagal melakukan preview geocoding');
            },
            complete: function() {
                $('#btnPreviewGeocode').prop('disabled', false).html('<i class="fas fa-search-location"></i> Preview Lokasi');
            }
        });
    }

    function showPreviewResult(info) {
        if ($('#geocoding-result').length) {
            const qualityClass = getQualityClass(info.quality);
            const qualityText = getQualityText(info.quality);
            
            $('#geocoding-result').html(`
                <div class="preview-result">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Koordinat:</strong><br>
                            <code>${info.latitude}, ${info.longitude}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>Quality:</strong> <span class="badge badge-${qualityClass}">${qualityText}</span><br>
                            <strong>Provider:</strong> ${info.provider}<br>
                            <strong>Score:</strong> ${info.quality_score}/100
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <strong>Alamat:</strong><br>
                            <small class="text-muted">${info.formatted_address}</small>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openGoogleMaps(${info.latitude}, ${info.longitude})">
                                <i class="fas fa-external-link-alt"></i> Lihat di Google Maps
                            </button>
                        </div>
                    </div>
                </div>
            `);
            $('#geocoding-info').show();
        }
    }

    function showPreviewError() {
        if ($('#geocoding-result').length) {
            $('#geocoding-result').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Koordinat tidak ditemukan. Toko masih bisa disimpan.
                </div>
            `);
            $('#geocoding-info').show();
        }
    }

    // Detail Koordinat
    $(document).on('click', '.btn-detail', function() {
        const id = $(this).data('id');
        showDetailKoordinat(id);
    });

    function showDetailKoordinat(tokoId) {
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
                    
                    showDetailModal(toko);
                }
            },
            error: function() {
                showAlert('danger', 'Gagal mengambil detail koordinat');
            }
        });
    }

    function showDetailModal(toko) {
        const qualityClass = getQualityClass(toko.geocoding_quality);
        const qualityText = getQualityText(toko.geocoding_quality);
        
        const content = `
            <div class="detail-content">
                <h6><i class="fas fa-store"></i> ${toko.nama_toko} (${toko.toko_id})</h6>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Koordinat GPS:</strong><br>
                        <code class="h5">${toko.latitude}, ${toko.longitude}</code><br>
                        <span class="badge badge-${qualityClass}">${qualityText}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Provider:</strong> ${toko.geocoding_provider || 'Unknown'}<br>
                        <strong>Score:</strong> ${toko.geocoding_score || 0}/100<br>
                        <strong>Confidence:</strong> ${((toko.geocoding_confidence || 0) * 100).toFixed(1)}%
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <strong>Alamat:</strong><br>
                        ${toko.alamat}<br>
                        <small class="text-muted">${toko.wilayah_kelurahan}, ${toko.wilayah_kecamatan}, ${toko.wilayah_kota_kabupaten}</small>
                    </div>
                </div>
                ${toko.alamat_lengkap_geocoding ? `
                    <div class="row mt-2">
                        <div class="col-12">
                            <strong>Alamat Hasil Geocoding:</strong><br>
                            <small class="text-muted">${toko.alamat_lengkap_geocoding}</small>
                        </div>
                    </div>
                ` : ''}
                <hr>
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary btn-sm" onclick="openGoogleMaps(${toko.latitude}, ${toko.longitude})">
                            <i class="fas fa-external-link-alt"></i> Google Maps
                        </button>
                        <button type="button" class="btn btn-warning btn-sm ml-2" onclick="geocodeSingleToko('${toko.toko_id}'); $('#detailModal').modal('hide');">
                            <i class="fas fa-sync-alt"></i> Update Koordinat
                        </button>
                        <button type="button" class="btn btn-info btn-sm ml-2" onclick="copyToClipboard('${toko.latitude}, ${toko.longitude}')">
                            <i class="fas fa-copy"></i> Copy Koordinat
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Create and show modal
        const modalHtml = `
            <div class="modal fade" id="detailModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detail Koordinat GPS</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">${content}</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#detailModal').remove();
        $('body').append(modalHtml);
        $('#detailModal').modal('show');
    }

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    
    // Open Google Maps
    window.openGoogleMaps = function(lat, lng) {
        const url = `https://www.google.com/maps?q=${lat},${lng}`;
        window.open(url, '_blank');
    };

    // Copy to clipboard
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showAlert('success', 'Koordinat berhasil disalin: ' + text);
            });
        } else {
            // Fallback
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showAlert('success', 'Koordinat berhasil disalin: ' + text);
        }
    };

    // Global function for geocoding
    window.geocodeSingleToko = geocodeSingleToko;

    // ========================================
    // FORM FUNCTIONS
    // ========================================
    function generateTokoId() {
        $.ajax({
            url: '/toko/generate-kode',
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    $('#toko_id').val(response.kode);
                }
            },
            error: function() {
                showAlert('warning', 'Gagal generate ID toko');
            }
        });
    }
    
    function loadWilayahKota() {
        $.ajax({
            url: '/toko/wilayah/kota',
            type: 'GET',
            beforeSend: function() {
                $('#wilayah_kota_id').html('<option value="">Memuat...</option>').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kota/Kabupaten --</option>';
                    response.data.forEach(function(item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    $('#wilayah_kota_id').html(options).prop('disabled', false);
                }
            },
            error: function() {
                $('#wilayah_kota_id').html('<option value="">-- Pilih Kota/Kabupaten --</option>').prop('disabled', false);
            }
        });
    }

    function loadKecamatan(kotaId) {
        $.ajax({
            url: '/toko/wilayah/kecamatan',
            type: 'GET',
            data: { kota_id: kotaId },
            beforeSend: function() {
                $('#wilayah_kecamatan_id').html('<option value="">Memuat...</option>').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kecamatan --</option>';
                    response.data.forEach(function(item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    $('#wilayah_kecamatan_id').html(options).prop('disabled', false);
                }
            },
            error: function() {
                $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', false);
            }
        });
    }
    
    function loadKelurahan(kotaId, kecamatanId) {
        $.ajax({
            url: '/toko/wilayah/kelurahan',
            type: 'GET',
            data: { kota_id: kotaId, kecamatan_id: kecamatanId },
            beforeSend: function() {
                $('#wilayah_kelurahan_id').html('<option value="">Memuat...</option>').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    let options = '<option value="">-- Pilih Kelurahan --</option>';
                    response.data.forEach(function(item) {
                        options += `<option value="${item.id}" data-nama="${item.nama}">${item.nama}</option>`;
                    });
                    $('#wilayah_kelurahan_id').html(options).prop('disabled', false);
                }
            },
            error: function() {
                $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>').prop('disabled', false);
            }
        });
    }

    // ========================================
    // EVENT HANDLERS
    // ========================================
    
    // Wilayah dropdowns
    $(document).on('change', '#wilayah_kota_id', function() {
        const kotaId = $(this).val();
        const kotaNama = $(this).find('option:selected').data('nama') || '';
        
        $('#wilayah_kota_kabupaten').val(kotaNama);
        $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>');
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
        $('#wilayah_kecamatan').val('');
        $('#wilayah_kelurahan').val('');
        
        if (kotaId) {
            loadKecamatan(kotaId);
            $('#wilayah_kecamatan_id').prop('disabled', false);
        } else {
            $('#wilayah_kecamatan_id, #wilayah_kelurahan_id').prop('disabled', true);
        }
    });
    
    $(document).on('change', '#wilayah_kecamatan_id', function() {
        const kotaId = $('#wilayah_kota_id').val();
        const kecamatanId = $(this).val();
        const kecamatanNama = $(this).find('option:selected').data('nama') || '';
        
        $('#wilayah_kecamatan').val(kecamatanNama);
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>');
        $('#wilayah_kelurahan').val('');
        
        if (kotaId && kecamatanId) {
            loadKelurahan(kotaId, kecamatanId);
            $('#wilayah_kelurahan_id').prop('disabled', false);
        } else {
            $('#wilayah_kelurahan_id').prop('disabled', true);
        }
    });
    
    $(document).on('change', '#wilayah_kelurahan_id', function() {
        const kelurahanNama = $(this).find('option:selected').data('nama') || '';
        $('#wilayah_kelurahan').val(kelurahanNama);
    });

    // Preview geocoding button
    $(document).on('click', '#btnPreviewGeocode', previewGeocode);

    // Show/hide preview button
    $('#alamat, #wilayah_kelurahan, #wilayah_kecamatan, #wilayah_kota_kabupaten').on('input change', function() {
        const alamat = $('#alamat').val();
        const kelurahan = $('#wilayah_kelurahan').val();
        const kecamatan = $('#wilayah_kecamatan').val();
        const kotaKab = $('#wilayah_kota_kabupaten').val();
        
        if (alamat && kelurahan && kecamatan && kotaKab) {
            $('#btnPreviewGeocode').show();
        } else {
            $('#btnPreviewGeocode').hide();
        }
        
        if ($('#geocoding-info').length) {
            $('#geocoding-info').hide();
        }
    });

    // Form submission
    $('#formToko').submit(function(e) {
        e.preventDefault();
        
        clearErrors();
        
        // Validate wilayah
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
        
        const mode = $('#mode').val();
        const url = mode === 'add' ? '/toko' : '/toko/' + $('#toko_id').val();
        const method = mode === 'add' ? 'POST' : 'PUT';
        
        $('#btnSimpan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#modalToko').modal('hide');
                loadTokoData();
                
                let message = response.message;
                if (response.geocode_info) {
                    const info = response.geocode_info;
                    message += `<br><small>Geocoding: ${info.provider} | Quality: ${info.quality} | Score: ${info.quality_score}/100</small>`;
                }
                
                showAlert('success', message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    showValidationErrors(xhr.responseJSON.errors);
                } else {
                    showAlert('danger', xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
            },
            complete: function() {
                $('#btnSimpan').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    });

    // Edit toko
    $(document).on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        
        resetForm();
        loadWilayahKota();
        
        $.ajax({
            url: '/toko/' + id + '/edit',
            type: 'GET',
            success: function(response) {
                $('#modalTokoLabel').text('Edit Toko');
                $('#mode').val('edit');
                
                const data = response.data;
                $('#toko_id').val(data.toko_id);
                $('#nama_toko').val(data.nama_toko);
                $('#pemilik').val(data.pemilik);
                $('#alamat').val(data.alamat);
                $('#nomer_telpon').val(data.nomer_telpon);
                
                // Set wilayah values
                $('#wilayah_kota_kabupaten').val(data.wilayah_kota_kabupaten);
                $('#wilayah_kecamatan').val(data.wilayah_kecamatan);
                $('#wilayah_kelurahan').val(data.wilayah_kelurahan);
                
                // Set dropdowns when loaded
                setTimeout(() => setWilayahDropdowns(data), 100);
                
                $('#modalToko').modal('show');
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Gagal mengambil data toko');
            }
        });
    });

    function setWilayahDropdowns(data) {
        // Set kota
        $('#wilayah_kota_id option').each(function() {
            if ($(this).text() === data.wilayah_kota_kabupaten) {
                $(this).prop('selected', true).trigger('change');
                
                // Set kecamatan after kota is set
                setTimeout(() => {
                    $('#wilayah_kecamatan_id option').each(function() {
                        if ($(this).text() === data.wilayah_kecamatan) {
                            $(this).prop('selected', true).trigger('change');
                            
                            // Set kelurahan after kecamatan is set
                            setTimeout(() => {
                                $('#wilayah_kelurahan_id option').each(function() {
                                    if ($(this).text() === data.wilayah_kelurahan) {
                                        $(this).prop('selected', true).trigger('change');
                                    }
                                });
                            }, 100);
                        }
                    });
                }, 100);
            }
        });
    }

    // Delete toko
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        $('#delete-item-name').text(name);
        $('#btnDelete').data('id', id);
        $('#deleteModal').modal('show');
    });

    $('#btnDelete').click(function() {
        const id = $(this).data('id');
        
        $('#btnDelete').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menghapus...');
        
        $.ajax({
            url: '/toko/' + id,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                loadTokoData();
                showAlert('success', response.message);
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                showAlert('danger', xhr.responseJSON?.message || 'Gagal menghapus data toko');
            },
            complete: function() {
                $('#btnDelete').prop('disabled', false).html('<i class="fas fa-trash"></i> Hapus');
            }
        });
    });

    // ========================================
    // HELPER FUNCTIONS
    // ========================================
    function resetForm() {
        $('#formToko')[0].reset();
        clearErrors();
        
        $('#wilayah_kota_id').html('<option value="">-- Pilih Kota/Kabupaten --</option>');
        $('#wilayah_kecamatan_id').html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', true);
        $('#wilayah_kelurahan_id').html('<option value="">-- Pilih Kelurahan --</option>').prop('disabled', true);
        
        $('#wilayah_kota_kabupaten, #wilayah_kecamatan, #wilayah_kelurahan').val('');
        
        if ($('#geocoding-info').length) {
            $('#geocoding-info').hide();
        }
        if ($('#btnPreviewGeocode').length) {
            $('#btnPreviewGeocode').hide();
        }
    }

    function clearErrors() {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function showValidationErrors(errors) {
        Object.keys(errors).forEach(function(field) {
            $('#' + field).addClass('is-invalid');
            $('#error-' + field).text(errors[field][0]);
        });
    }

    function showAlert(type, message) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        $('#alert-container').html(alert);
        
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }

    // ========================================
    // INITIALIZATION COMPLETE
    // ========================================
    console.log('✅ Enhanced Toko Management System loaded successfully!');
    console.log('🌟 Features: Enhanced Geocoding, Multi-provider, Quality Assessment');
});
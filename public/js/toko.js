$(document).ready(function() {
    // ========================================
    // INITIALIZATION
    // ========================================
    console.log('Loading Enhanced Toko Management System with Interactive Map...');
    
    // Global variables for map
    let interactiveMap = null;
    let currentMarker = null;
    let isMapInitialized = false;
    
    // Malang Region Bounds
    const MALANG_BOUNDS = {
        north: -7.4,
        south: -8.6,
        east: 113.2,
        west: 111.8
    };
    
    // Default center (Malang city center)
    const MALANG_CENTER = [-7.9666, 112.6326];
    
    loadTokoData();

    // ========================================
    // INTERACTIVE MAP FUNCTIONS
    // ========================================
    
    function initializeInteractiveMap() {
        if (isMapInitialized && interactiveMap) {
            return;
        }
        
        console.log('Initializing interactive map...');
        
        // Initialize map centered on Malang
        interactiveMap = L.map('interactiveMap', {
            center: MALANG_CENTER,
            zoom: 12,
            zoomControl: true,
            attributionControl: true
        });
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
            minZoom: 10
        }).addTo(interactiveMap);
        
        // Add boundary rectangle for Malang region
        const malangBounds = [
            [MALANG_BOUNDS.south, MALANG_BOUNDS.west],
            [MALANG_BOUNDS.north, MALANG_BOUNDS.east]
        ];
        
        L.rectangle(malangBounds, {
            color: '#007bff',
            weight: 2,
            fillOpacity: 0.1,
            dashArray: '5, 5'
        }).addTo(interactiveMap).bindPopup('Wilayah Malang Raya<br><small>Klik di dalam area ini untuk menentukan lokasi toko</small>');
        
        // Map click event - MAIN FEATURE
        interactiveMap.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            console.log('Map clicked at:', lat, lng);
            
            // Validate if coordinates are within Malang region
            if (isWithinMalangRegion(lat, lng)) {
                setMapMarker(lat, lng);
                updateCoordinateFields(lat, lng);
                showLocationStatus(lat, lng, true);
            } else {
                showAlert('warning', 'Lokasi yang dipilih berada di luar wilayah Malang Raya. Silakan pilih lokasi di dalam area yang ditandai.');
                showLocationStatus(lat, lng, false);
            }
        });
        
        // Add scale control
        L.control.scale({
            position: 'bottomright',
            imperial: false
        }).addTo(interactiveMap);
        
        // Add custom control for location info
        const locationControl = L.control({position: 'topright'});
        locationControl.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'leaflet-control-custom');
            div.innerHTML = '<div style="background: white; padding: 5px; border-radius: 3px; box-shadow: 0 1px 5px rgba(0,0,0,0.4);"><small><i class="fas fa-info-circle"></i> Klik pada peta untuk menentukan lokasi</small></div>';
            return div;
        };
        locationControl.addTo(interactiveMap);
        
        isMapInitialized = true;
        console.log('✅ Interactive map initialized successfully');
        
        // Invalidate size after modal is shown
        setTimeout(() => {
            if (interactiveMap) {
                interactiveMap.invalidateSize();
            }
        }, 300);
    }
    
    function setMapMarker(lat, lng) {
        // Remove existing marker
        if (currentMarker) {
            interactiveMap.removeLayer(currentMarker);
        }
        
        // Create custom icon
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background-color: #dc3545; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });
        
        // Add new marker
        currentMarker = L.marker([lat, lng], {
            icon: customIcon,
            draggable: true
        }).addTo(interactiveMap);
        
        // Marker drag event
        currentMarker.on('dragend', function(e) {
            const newLat = e.target.getLatLng().lat;
            const newLng = e.target.getLatLng().lng;
            
            if (isWithinMalangRegion(newLat, newLng)) {
                updateCoordinateFields(newLat, newLng);
                showLocationStatus(newLat, newLng, true);
            } else {
                // Reset marker to previous valid position
                showAlert('warning', 'Lokasi tidak valid! Marker dikembalikan ke posisi sebelumnya.');
                currentMarker.setLatLng([lat, lng]);
            }
        });
        
        // Add popup to marker
        currentMarker.bindPopup(`
            <div style="text-align: center;">
                <strong><i class="fas fa-store text-primary"></i> Lokasi Toko</strong><br>
                <small>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}</small><br>
                <small class="text-muted">Geser marker untuk penyesuaian</small>
            </div>
        `).openPopup();
    }
    
    function updateCoordinateFields(lat, lng) {
        $('#latitude').val(lat.toFixed(8));
        $('#longitude').val(lng.toFixed(8));
        $('#coordinate_display').val(`${lat.toFixed(6)}, ${lng.toFixed(6)}`);
        
        console.log('Coordinates updated:', lat, lng);
    }
    
    function showLocationStatus(lat, lng, isValid) {
        const statusDiv = $('#mapStatus');
        const infoDiv = $('#selectedLocationInfo');
        
        if (isValid) {
            infoDiv.html(`
                <strong>Koordinat:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>
                <span class="text-success"><i class="fas fa-check-circle"></i> Lokasi valid dalam wilayah Malang Raya</span>
            `);
            statusDiv.removeClass('d-none').show();
            $('#btnValidateLocation').show();
        } else {
            infoDiv.html(`
                <strong>Koordinat:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>
                <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Lokasi di luar wilayah Malang Raya</span>
            `);
            statusDiv.removeClass('d-none').show();
            $('#btnValidateLocation').hide();
        }
    }
    
    function isWithinMalangRegion(lat, lng) {
        return (lat >= MALANG_BOUNDS.south && lat <= MALANG_BOUNDS.north && 
                lng >= MALANG_BOUNDS.west && lng <= MALANG_BOUNDS.east);
    }
    
    function resetMap() {
        if (currentMarker) {
            interactiveMap.removeLayer(currentMarker);
            currentMarker = null;
        }
        
        $('#latitude').val('');
        $('#longitude').val('');
        $('#coordinate_display').val('');
        $('#mapStatus').hide();
        $('#btnValidateLocation').hide();
        
        // Reset map view
        interactiveMap.setView(MALANG_CENTER, 12);
        
        console.log('Map reset');
    }
    
    function centerMapToMalang() {
        if (interactiveMap) {
            interactiveMap.setView(MALANG_CENTER, 12);
        }
    }
    
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
                
                // Special badge for interactive map selections
                if (item.geocoding_provider === 'interactive_map') {
                    statusBadge = '<span class="badge badge-success"><i class="fas fa-mouse-pointer"></i> Peta Interaktif</span>';
                }
                
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
    // MODAL HANDLERS WITH MAP INTEGRATION
    // ========================================
    $('#btnTambah').click(function() {
        resetForm();
        $('#modalTokoLabel').html('<i class="fas fa-store"></i> Tambah Toko dengan Peta Interaktif');
        $('#mode').val('add');
        generateTokoId();
        loadWilayahKota();
        $('#modalToko').modal('show');
        
        // Initialize map after modal is shown
        $('#modalToko').on('shown.bs.modal', function() {
            if (!isMapInitialized) {
                initializeInteractiveMap();
            } else {
                setTimeout(() => {
                    if (interactiveMap) {
                        interactiveMap.invalidateSize();
                        resetMap();
                    }
                }, 100);
            }
        });
    });

    // Map control buttons
    $('#btnResetMap').click(function() {
        resetMap();
    });
    
    $('#btnCenterMalang').click(function() {
        centerMapToMalang();
    });
    
    $('#btnValidateLocation').click(function() {
        const lat = parseFloat($('#latitude').val());
        const lng = parseFloat($('#longitude').val());
        
        if (lat && lng) {
            validateMapCoordinates(lat, lng);
        } else {
            showAlert('warning', 'Silakan pilih lokasi pada peta terlebih dahulu');
        }
    });

    // ========================================
    // COORDINATE VALIDATION
    // ========================================
    function validateMapCoordinates(lat, lng) {
        $('#btnValidateLocation').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Validasi...');
        
        $.ajax({
            url: '/toko/validate-coordinates',
            type: 'POST',
            data: {
                latitude: lat,
                longitude: lng,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success') {
                    let message = response.message;
                    if (response.address_info && response.address_info.formatted_address) {
                        message += '<br><small><strong>Alamat:</strong> ' + response.address_info.formatted_address + '</small>';
                    }
                    showAlert('success', message);
                } else {
                    showAlert('warning', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Gagal melakukan validasi koordinat');
            },
            complete: function() {
                $('#btnValidateLocation').prop('disabled', false).html('<i class="fas fa-check-circle"></i> Validasi Lokasi');
            }
        });
    }

    // ========================================
    // FORM FUNCTIONS WITH MAP INTEGRATION
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

    // Form submission with coordinate validation
    $('#formToko').submit(function(e) {
        e.preventDefault();
        
        clearErrors();
        
        // Validate coordinates REQUIRED
        const lat = $('#latitude').val();
        const lng = $('#longitude').val();
        
        if (!lat || !lng) {
            showAlert('danger', 'Koordinat GPS wajib diisi! Silakan pilih lokasi toko pada peta.');
            $('#coordinate_display').addClass('is-invalid');
            return false;
        }
        
        // Validate within Malang region
        if (!isWithinMalangRegion(parseFloat(lat), parseFloat(lng))) {
            showAlert('danger', 'Koordinat berada di luar wilayah Malang Raya! Silakan pilih lokasi yang sesuai.');
            $('#coordinate_display').addClass('is-invalid');
            return false;
        }
        
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
                if (response.coordinate_info) {
                    const info = response.coordinate_info;
                    message += `<br><small><strong>Lokasi:</strong> ${info.source} | Akurasi: ${info.accuracy}</small>`;
                }
                
                showAlert('success', message);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    showValidationErrors(errors);
                    
                    // Special handling for coordinate errors
                    if (errors.latitude || errors.longitude) {
                        $('#coordinate_display').addClass('is-invalid');
                        showAlert('danger', 'Koordinat GPS tidak valid. Silakan pilih lokasi pada peta.');
                    }
                } else {
                    showAlert('danger', xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
            },
            complete: function() {
                $('#btnSimpan').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    });

    // Edit toko with map integration
    $(document).on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        
        resetForm();
        loadWilayahKota();
        
        $.ajax({
            url: '/toko/' + id + '/edit',
            type: 'GET',
            success: function(response) {
                $('#modalTokoLabel').html('<i class="fas fa-edit"></i> Edit Toko dengan Peta Interaktif');
                $('#mode').val('edit');
                
                const data = response.data;
                $('#toko_id').val(data.toko_id);
                $('#nama_toko').val(data.nama_toko);
                $('#pemilik').val(data.pemilik);
                $('#alamat').val(data.alamat);
                $('#nomer_telpon').val(data.nomer_telpon);
                
                // Set coordinates if available
                if (data.latitude && data.longitude) {
                    $('#latitude').val(data.latitude);
                    $('#longitude').val(data.longitude);
                    $('#coordinate_display').val(data.latitude + ', ' + data.longitude);
                }
                
                // Set wilayah values
                $('#wilayah_kota_kabupaten').val(data.wilayah_kota_kabupaten);
                $('#wilayah_kecamatan').val(data.wilayah_kecamatan);
                $('#wilayah_kelurahan').val(data.wilayah_kelurahan);
                
                // Set dropdowns when loaded
                setTimeout(() => setWilayahDropdowns(data), 100);
                
                $('#modalToko').modal('show');
                
                // Initialize map and set existing marker
                $('#modalToko').on('shown.bs.modal', function() {
                    if (!isMapInitialized) {
                        initializeInteractiveMap();
                    } else {
                        if (interactiveMap) {
                            interactiveMap.invalidateSize();
                        }
                    }
                    
                    // Set existing marker if coordinates exist
                    if (data.latitude && data.longitude) {
                        setTimeout(() => {
                            setMapMarker(parseFloat(data.latitude), parseFloat(data.longitude));
                            showLocationStatus(parseFloat(data.latitude), parseFloat(data.longitude), true);
                        }, 300);
                    }
                });
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

    // Detail koordinat dengan map preview
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
        
        // Special handling for interactive map source
        let sourceInfo = toko.geocoding_provider || 'Unknown';
        if (toko.geocoding_provider === 'interactive_map') {
            sourceInfo = '<span class="text-success"><i class="fas fa-mouse-pointer"></i> Peta Interaktif (User Selected)</span>';
        }
        
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
                        <strong>Sumber:</strong><br>${sourceInfo}<br>
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
        $('#latitude, #longitude, #coordinate_display').val('');
        $('#mapStatus').hide();
        $('#btnValidateLocation').hide();
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
        }, 8000);
    }

    // ========================================
    // INITIALIZATION COMPLETE
    // ========================================
    console.log('✅ Enhanced Toko Management System with Interactive Map loaded successfully!');
    console.log('🗺️ Features: Interactive Map, Coordinate Selection, Real-time Validation');
    console.log('📍 Region: Malang Raya (Kota Malang, Kabupaten Malang, Kota Batu)');
});
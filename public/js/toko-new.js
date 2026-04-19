$(document).ready(function () {

    let interactiveMap = null;
    let currentMarker = null;
    let activeBoundary = null;
    let isMapInitialized = false;
    let searchTimeout = null;

    const DEFAULT_CENTER = [-7.9666, 112.6326];
    const DEFAULT_ZOOM = 13;
    const permissionState = window.tokoPermissions || {};
    const canCreateToko = Boolean(permissionState.canCreate);
    const canEditToko = Boolean(permissionState.canEdit);
    const canDeleteToko = Boolean(permissionState.canDelete);
    const canUseTokoForm = canCreateToko || canEditToko;

    loadTokoData();

    function initializeMap() {
        if (isMapInitialized) return;

        try {
            interactiveMap = L.map('interactiveMap').setView(DEFAULT_CENTER, DEFAULT_ZOOM);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(interactiveMap);

            L.control.scale({ metric: true, imperial: false }).addTo(interactiveMap);
            interactiveMap.on('click', handleMapClick);

            isMapInitialized = true;
            $('#mapLoadingIndicator').hide();
        } catch (error) {
            AlertHelper.error('Gagal memuat peta. Silakan refresh halaman.');
        }
    }

    $('#alamat').on('input', function () {
        const query = $(this).val().trim();
        clearTimeout(searchTimeout);

        if (query.length < 3) {
            hideSuggestions();
            return;
        }

        searchTimeout = setTimeout(() => searchAddress(query), 500);
    });

    function searchAddress(query) {
        $.ajax({
            url: '/toko/search-address',
            type: 'GET',
            data: { q: query },
            success: function (response) {
                if (response.success && response.results.length > 0) {
                    showSuggestions(response.results);
                } else {
                    showNoResults();
                }
            },
            error: function () {
                hideSuggestions();
            }
        });
    }

    function showSuggestions(results) {
        const $container = $('#addressSuggestions');
        
        if (!$container.length) {
            $('#alamat').after('<div id="addressSuggestions" class="suggestions-dropdown"></div>');
        }

        const $suggestions = $('#addressSuggestions');
        $suggestions.empty();

        results.forEach(result => {
            const $item = $('<div class="suggestion-item"></div>');
            $item.html(`
                <div class="suggestion-name">${escapeHtml(result.name)}</div>
                <div class="suggestion-address">${escapeHtml(result.display_name)}</div>
            `);
            $item.on('click', function () {
                selectAddress(result);
            });
            $suggestions.append($item);
        });

        $suggestions.show();
    }

    function showNoResults() {
        const $suggestions = $('#addressSuggestions');
        $suggestions.html('<div class="suggestion-empty">Tidak ada hasil ditemukan</div>');
        $suggestions.show();
    }

    function hideSuggestions() {
        $('#addressSuggestions').hide();
    }

    function selectAddress(place) {
        $('#alamat').val(place.display_name);
        hideSuggestions();

        if (!isMapInitialized) {
            initializeMap();
        }

        placeMarker(place.lat, place.lon, place.name);
        interactiveMap.flyTo([place.lat, place.lon], 17, { animate: true, duration: 1.2 });
        updateCoordinateFields(place.lat, place.lon);
        
        if (place.address) {
            autoFillWilayah(place.address);
        }

        if (place.osm_type && place.osm_type !== 'node') {
            fetchAndDrawBoundary(place.osm_type, place.osm_id);
        }

        AlertHelper.success('Lokasi ditemukan! Koordinat telah diisi otomatis. Pastikan data wilayah sudah benar.');
    }

    function handleMapClick(e) {
        const { lat, lng } = e.latlng;
        AlertHelper.info('Mencari informasi lokasi...');

        $.ajax({
            url: '/toko/reverse-geocode',
            type: 'GET',
            data: { lat: lat, lon: lng },
            success: function (response) {
                if (response.success && response.data) {
                    const place = response.data;
                    
                    $('#alamat').val(place.display_name);
                    placeMarker(lat, lng, place.name);
                    updateCoordinateFields(lat, lng);
                    
                    if (place.address) {
                        autoFillWilayah(place.address);
                    }

                    if (place.osm_type && place.osm_type !== 'node') {
                        fetchAndDrawBoundary(place.osm_type, place.osm_id);
                    }

                    AlertHelper.success('Lokasi berhasil dipilih! Pastikan data wilayah sudah benar.');
                } else {
                    AlertHelper.warning('Informasi lokasi tidak ditemukan untuk titik ini. Silakan isi data wilayah secara manual.');
                }
            },
            error: function () {
                AlertHelper.error('Gagal mendapatkan informasi lokasi. Silakan isi data wilayah secara manual.');
            }
        });
    }

    function placeMarker(lat, lon, popupText) {
        if (!interactiveMap) return;

        if (currentMarker) {
            interactiveMap.removeLayer(currentMarker);
        }

        const customIcon = L.divIcon({
            className: 'custom-marker-icon',
            html: '<div class="pulse-marker"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
        });

        currentMarker = L.marker([lat, lon], { 
            icon: customIcon,
            draggable: true  
        })
            .addTo(interactiveMap)
            .bindPopup(`<b>${escapeHtml(popupText)}</b><br><small>Drag marker untuk mengubah posisi</small>`)
            .openPopup();
        
        currentMarker.on('dragend', function(e) {
            const position = e.target.getLatLng();
            updateCoordinateFields(position.lat, position.lng);
            
            $.ajax({
                url: '/toko/reverse-geocode',
                type: 'GET',
                data: { lat: position.lat, lon: position.lng },
                success: function (response) {
                    if (response.success && response.data) {
                        const place = response.data;
                        $('#alamat').val(place.display_name);
                        autoFillWilayah(place.address);
                        
                        // Update popup
                        currentMarker.setPopupContent(`<b>${escapeHtml(place.name)}</b><br><small>Drag marker untuk mengubah posisi</small>`);
                        
                        showAlert('success', 'Lokasi berhasil diperbarui!');
                    }
                }
            });
        });
    }

    function fetchAndDrawBoundary(osmType, osmId) {
        if (!osmType || !osmId || osmType === 'node') return;

        $.ajax({
            url: '/toko/boundary',
            type: 'GET',
            data: { osm_type: osmType, osm_id: osmId },
            success: function (response) {
                if (response.success && response.data) {
                    drawBoundary(response.data);
                }
            }
        });
    }

    function drawBoundary(boundary) {
        clearBoundary();

        const rings = boundary.coordinates;
        if (!rings || !rings.length) return;

        const style = {
            color: '#4ade80',
            weight: 2.5,
            opacity: 0.8,
            fillColor: '#4ade80',
            fillOpacity: 0.1,
            lineJoin: 'round',
            lineCap: 'round',
        };

        if (rings.length === 1) {
            activeBoundary = L.polygon(rings[0], style).addTo(interactiveMap);
        } else {
            const layers = rings.map(ring => L.polygon(ring, style));
            activeBoundary = L.layerGroup(layers).addTo(interactiveMap);
        }

        if (activeBoundary.getBounds) {
            interactiveMap.fitBounds(activeBoundary.getBounds(), { 
                padding: [40, 40], 
                maxZoom: 16 
            });
        }
    }

    function clearBoundary() {
        if (activeBoundary) {
            interactiveMap.removeLayer(activeBoundary);
            activeBoundary = null;
        }
    }

    function updateCoordinateFields(lat, lon) {
        $('#latitude').val(lat);
        $('#longitude').val(lon);
        $('#coordinate_display').val(`${lat.toFixed(6)}, ${lon.toFixed(6)}`);
    }

    function autoFillWilayah(address) {
        if (!address) return;

        const city = address.city || address.town || address.county || address.city_district || '';
        const suburb = address.suburb || address.village || address.subdistrict || '';
        const neighbourhood = address.neighbourhood || address.hamlet || '';

        if (city) {
            $('#wilayah_kota_kabupaten').val(city);
            
            let kotaFound = false;
            $('#wilayah_kota_id option').each(function() {
                const optionText = $(this).text().trim().toLowerCase();
                const searchText = city.trim().toLowerCase();
                
                if (optionText.includes(searchText) || searchText.includes(optionText)) {
                    $('#wilayah_kota_id').val($(this).val()).trigger('change');
                    kotaFound = true;
                    return false;
                }
            });
        }

        if (suburb) {
            $('#wilayah_kecamatan').val(suburb);
        }

        if (neighbourhood) {
            $('#wilayah_kelurahan').val(neighbourhood);
        } else if (suburb) {
            $('#wilayah_kelurahan').val(suburb);
        }
    }

    $('#btnTambah').on('click', function () {
        if (!canCreateToko) {
            AlertHelper.error('Anda tidak memiliki izin untuk menambah toko.');
            return;
        }

        resetForm();
        $('#modalTokoLabel').html('<i class="fas fa-plus"></i> Tambah Toko');
        $('#mode').val('add');
        
        generateTokoId();
        $('#toko_id').prop('readonly', true);
        
        $('#modalToko').modal('show');

        setTimeout(() => {
            if (!isMapInitialized) {
                initializeMap();
            } else {
                interactiveMap.invalidateSize();
            }
        }, 300);
    });

    $('#modalToko').on('shown.bs.modal', function () {
        if (interactiveMap) {
            interactiveMap.invalidateSize();
        }
    });

    $('#btnResetMap').on('click', function () {
        if (interactiveMap) {
            interactiveMap.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
            if (currentMarker) {
                interactiveMap.removeLayer(currentMarker);
                currentMarker = null;
            }
            clearBoundary();
            updateCoordinateFields('', '');
        }
    });

    $('#formToko').on('submit', function (e) {
        e.preventDefault();

        const mode = $('#mode').val();
        const tokoId = $('#toko_id').val();
        const url = mode === 'add' ? '/toko' : `/toko/${tokoId}`;
        const method = mode === 'add' ? 'POST' : 'PUT';

        if (mode === 'add' && !canCreateToko) {
            AlertHelper.error('Anda tidak memiliki izin untuk menambah toko.');
            return;
        }

        if (mode === 'edit' && !canEditToko) {
            AlertHelper.error('Anda tidak memiliki izin untuk mengubah toko.');
            return;
        }

        const latitude = $('#latitude').val();
        const longitude = $('#longitude').val();
        const wilayahKota = $('#wilayah_kota_kabupaten').val();
        const wilayahKec = $('#wilayah_kecamatan').val();
        const wilayahKel = $('#wilayah_kelurahan').val();

        if (!latitude || !longitude) {
            AlertHelper.warning('Koordinat GPS belum dipilih. Silakan pilih lokasi di peta atau gunakan pencarian alamat.');
            return;
        }

        if (!wilayahKota || !wilayahKec || !wilayahKel) {
            AlertHelper.warning('Data wilayah belum lengkap. Silakan pilih Kota/Kabupaten, Kecamatan, dan Kelurahan.');
            return;
        }

        const formData = {
            toko_id: tokoId,
            nama_toko: $('#nama_toko').val(),
            pemilik: $('#pemilik').val(),
            alamat: $('#alamat').val(),
            wilayah_kota_kabupaten: wilayahKota,
            wilayah_kecamatan: wilayahKec,
            wilayah_kelurahan: wilayahKel,
            nomer_telpon: $('#nomer_telpon').val(),
            latitude: latitude,
            longitude: longitude,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        if (method === 'PUT') {
            formData._method = 'PUT';
        }

        AlertHelper.loading('Menyimpan...', 'Mohon tunggu sebentar');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function (response) {
                AlertHelper.close();
                
                if (response.status === 'success') {
                    AlertHelper.success(response.message);
                    $('#modalToko').modal('hide');
                    loadTokoData();
                } else {
                    AlertHelper.error(response.message);
                }
            },
            error: function (xhr) {
                AlertHelper.close();
                handleFormErrors(xhr);
            }
        });
    });

    function loadTokoData() {
        $.ajax({
            url: '/toko/list',
            type: 'GET',
            success: function (response) {
                if (response.status === 'success' && response.data) {
                    renderTokoTable(response.data);
                } else {
                    AlertHelper.error(response.message || 'Gagal memuat data toko');
                }
            },
            error: function (xhr) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Gagal memuat data toko';
                AlertHelper.error(errorMessage);
            }
        });
    }

    function renderTokoTable(data) {
        const $tbody = $('#toko-table-body');
        $tbody.empty();

        if (!data || data.length === 0) {
            $tbody.html('<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>');
            return;
        }

        data.forEach((toko, index) => {
            const actionButtons = [];

            if (canEditToko) {
                actionButtons.push(`<button class="btn btn-info btn-edit" data-id="${toko.toko_id}">
                                <i class="fas fa-edit"></i>
                            </button>`);
            }

            if (canDeleteToko) {
                actionButtons.push(`<button class="btn btn-danger btn-delete" data-id="${toko.toko_id}" data-name="${escapeHtml(toko.nama_toko || '')}">
                                <i class="fas fa-trash"></i>
                            </button>`);
            }

            const actionHtml = actionButtons.length > 0
                ? `<div class="btn-group btn-group-sm">${actionButtons.join('')}</div>`
                : '<span class="text-muted">-</span>';

            const row = `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${toko.toko_id || '-'}</td>
                    <td>
                        <strong>${escapeHtml(toko.nama_toko || '-')}</strong><br>
                        <small class="text-muted">${escapeHtml(toko.pemilik || '-')}</small>
                    </td>
                    <td style="white-space: normal; word-wrap: break-word; max-width: 250px;" title="${escapeHtml(toko.alamat || '')}">
                        ${escapeHtml(toko.alamat || '-')}
                    </td>
                    <td>
                        ${escapeHtml(toko.wilayah_kelurahan || '-')}<br>
                        <small class="text-muted">${escapeHtml(toko.wilayah_kecamatan || '-')}</small>
                    </td>
                    <td>${escapeHtml(toko.nomer_telpon || '-')}</td>
                    <td class="text-center">
                        <small>${toko.latitude ? parseFloat(toko.latitude).toFixed(5) : '-'}</small><br>
                        <small>${toko.longitude ? parseFloat(toko.longitude).toFixed(5) : '-'}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">OSM</span>
                    </td>
                    <td class="text-center">
                        ${actionHtml}
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });

        $('.btn-edit').on('click', function () {
            editToko($(this).data('id'));
        });

        $('.btn-delete').on('click', function () {
            confirmDelete($(this).data('id'), $(this).data('name'));
        });
    }

    function editToko(id) {
        if (!canEditToko) {
            AlertHelper.error('Anda tidak memiliki izin untuk mengubah toko.');
            return;
        }

        $.ajax({
            url: `/toko/${id}/edit`,
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    const toko = response.data;
                    
                    $('#mode').val('edit');
                    $('#toko_id').val(toko.toko_id).prop('readonly', true);
                    $('#nama_toko').val(toko.nama_toko);
                    $('#pemilik').val(toko.pemilik);
                    $('#alamat').val(toko.alamat);
                    $('#nomer_telpon').val(toko.nomer_telpon);
                    
                    $('#wilayah_kota_kabupaten').val(toko.wilayah_kota_kabupaten);
                    $('#wilayah_kecamatan').val(toko.wilayah_kecamatan);
                    $('#wilayah_kelurahan').val(toko.wilayah_kelurahan);
                    
                    const lat = toko.latitude ? parseFloat(toko.latitude) : null;
                    const lon = toko.longitude ? parseFloat(toko.longitude) : null;
                    
                    if (lat && lon) {
                        updateCoordinateFields(lat, lon);
                    }

                    $('#modalTokoLabel').html('<i class="fas fa-edit"></i> Edit Toko');
                    $('#modalToko').modal('show');

                    setTimeout(() => {
                        if (!isMapInitialized) {
                            initializeMap();
                        } else {
                            interactiveMap.invalidateSize();
                        }

                        if (lat && lon) {
                            placeMarker(lat, lon, toko.nama_toko);
                            interactiveMap.setView([lat, lon], 16);
                        }
                        
                        autoFillWilayahDropdowns(toko);
                    }, 300);
                }
            },
            error: function () {
                AlertHelper.error('Gagal memuat data toko');
            }
        });
    }
    
    function autoFillWilayahDropdowns(toko) {
        const kotaText = toko.wilayah_kota_kabupaten;
        if (kotaText) {
            let kotaFound = false;
            $('#wilayah_kota_id option').each(function() {
                const optionText = $(this).text().trim();
                const searchText = kotaText.trim();
                
                if (optionText === searchText || optionText.includes(searchText) || searchText.includes(optionText)) {
                    $('#wilayah_kota_id').val($(this).val());
                    kotaFound = true;
                    return false; // break loop
                }
            });
            
            if (kotaFound) {
                const kotaId = $('#wilayah_kota_id').val();
                if (kotaId) {
                    loadKecamatan(kotaId, function() {
                        const kecText = toko.wilayah_kecamatan;
                        if (kecText) {
                            let kecFound = false;
                            $('#wilayah_kecamatan_id option').each(function() {
                                const optionText = $(this).text().trim();
                                const searchText = kecText.trim();
                                
                                if (optionText === searchText || optionText.includes(searchText) || searchText.includes(optionText)) {
                                    $('#wilayah_kecamatan_id').val($(this).val());
                                    kecFound = true;
                                    return false;
                                }
                            });
                            
                            if (kecFound) {
                                const kecId = $('#wilayah_kecamatan_id').val();
                                if (kecId) {
                                    loadKelurahan(kotaId, kecId, function() {
                                        const kelText = toko.wilayah_kelurahan;
                                        if (kelText) {
                                            $('#wilayah_kelurahan_id option').each(function() {
                                                const optionText = $(this).text().trim();
                                                const searchText = kelText.trim();
                                                
                                                if (optionText === searchText || optionText.includes(searchText) || searchText.includes(optionText)) {
                                                    $('#wilayah_kelurahan_id').val($(this).val());
                                                    return false;
                                                }
                                            });
                                        }
                                    });
                                }
                            }
                        }
                    });
                }
            }
        }
    }

    function confirmDelete(id, name) {
        if (!canDeleteToko) {
            AlertHelper.error('Anda tidak memiliki izin untuk menghapus toko.');
            return;
        }

        AlertHelper.confirmDelete(
            'Hapus Toko?',
            `Apakah Anda yakin ingin menghapus toko "${name}"? Data yang dihapus tidak dapat dikembalikan.`
        ).then((result) => {
            if (result.isConfirmed) {
                deleteToko(id);
            }
        });
    }

    function deleteToko(id) {
        AlertHelper.loading('Menghapus...', 'Mohon tunggu sebentar');

        $.ajax({
            url: `/toko/${id}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                AlertHelper.close();
                
                if (response.status === 'success') {
                    AlertHelper.success(response.message);
                    loadTokoData();
                } else {
                    AlertHelper.error(response.message);
                }
            },
            error: function () {
                AlertHelper.close();
                AlertHelper.error('Gagal menghapus data toko');
            }
        });
    }

    function resetForm() {
        $('#formToko')[0].reset();
        $('#toko_id').val('');
        $('#coordinate_display').val('');
        $('#latitude').val('');
        $('#longitude').val('');
        
        if (currentMarker && interactiveMap) {
            interactiveMap.removeLayer(currentMarker);
            currentMarker = null;
        }
        clearBoundary();

        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function showAlert(type, message) {
        switch(type) {
            case 'success':
                AlertHelper.success(message);
                break;
            case 'error':
                AlertHelper.error(message);
                break;
            case 'warning':
                AlertHelper.warning(message);
                break;
            case 'info':
                AlertHelper.info(message);
                break;
            default:
                AlertHelper.info(message);
        }
    }

    function handleFormErrors(xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            Object.keys(errors).forEach(field => {
                const $field = $(`#${field}`);
                $field.addClass('is-invalid');
                $(`#error-${field}`).text(errors[field][0]);
            });

            const errorMessages = Object.values(errors).flat();
            const errorList = errorMessages.map(msg => `• ${msg}`).join('<br>');
            AlertHelper.error('Validasi Gagal', errorList, false);
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            AlertHelper.error(xhr.responseJSON.message, '', false);
        } else {
            AlertHelper.error('Terjadi kesalahan. Silakan coba lagi.', '', false);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#alamat, #addressSuggestions').length) {
            hideSuggestions();
        }
    });

    if (canUseTokoForm) {
        loadWilayahKota();
    }
    
    function generateTokoId() {
        $.ajax({
            url: '/toko/generate-kode',
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    $('#toko_id').val(response.kode);
                }
            }
        });
    }

    function loadWilayahKota() {
        $.ajax({
            url: '/toko/wilayah/kota',
            type: 'GET',
            success: function (response) {
                if (response.status === 'success') {
                    const $select = $('#wilayah_kota_id');
                    $select.empty().append('<option value="">-- Pilih Kota/Kabupaten --</option>');
                    
                    response.data.forEach(kota => {
                        $select.append(`<option value="${kota.id}">${kota.nama}</option>`);
                    });
                }
            }
        });
    }

    $('#wilayah_kota_id').on('change', function () {
        const kotaId = $(this).val();
        const kotaNama = $(this).find('option:selected').text();
        
        $('#wilayah_kota_kabupaten').val(kotaNama);
        
        if (kotaId) {
            loadKecamatan(kotaId);
        } else {
            $('#wilayah_kecamatan_id').prop('disabled', true).empty().append('<option value="">-- Pilih Kecamatan --</option>');
            $('#wilayah_kelurahan_id').prop('disabled', true).empty().append('<option value="">-- Pilih Kelurahan --</option>');
            $('#wilayah_kecamatan').val('');
            $('#wilayah_kelurahan').val('');
        }
    });

    function loadKecamatan(kotaId, callback) {
        $.ajax({
            url: '/toko/wilayah/kecamatan',
            type: 'GET',
            data: { kota_id: kotaId },
            success: function (response) {
                if (response.status === 'success') {
                    const $select = $('#wilayah_kecamatan_id');
                    $select.empty().append('<option value="">-- Pilih Kecamatan --</option>');
                    
                    response.data.forEach(kec => {
                        $select.append(`<option value="${kec.id}">${kec.nama}</option>`);
                    });
                    
                    $select.prop('disabled', false);
                    
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            }
        });
    }

    $('#wilayah_kecamatan_id').on('change', function () {
        const kecId = $(this).val();
        const kecNama = $(this).find('option:selected').text();
        const kotaId = $('#wilayah_kota_id').val();
        
        $('#wilayah_kecamatan').val(kecNama);
        
        if (kecId && kotaId) {
            loadKelurahan(kotaId, kecId);
        } else {
            $('#wilayah_kelurahan_id').prop('disabled', true).empty().append('<option value="">-- Pilih Kelurahan --</option>');
            $('#wilayah_kelurahan').val('');
        }
    });

    function loadKelurahan(kotaId, kecId, callback) {
        $.ajax({
            url: '/toko/wilayah/kelurahan',
            type: 'GET',
            data: { kota_id: kotaId, kecamatan_id: kecId },
            success: function (response) {
                if (response.status === 'success') {
                    const $select = $('#wilayah_kelurahan_id');
                    $select.empty().append('<option value="">-- Pilih Kelurahan --</option>');
                    
                    response.data.forEach(kel => {
                        $select.append(`<option value="${kel.id}">${kel.nama}</option>`);
                    });
                    
                    $select.prop('disabled', false);
                    
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            }
        });
    }

    $('#wilayah_kelurahan_id').on('change', function () {
        const kelNama = $(this).find('option:selected').text();
        $('#wilayah_kelurahan').val(kelNama);
    });
});

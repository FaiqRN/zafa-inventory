function showCoordinateDetailsModal(tokoId) {
    console.log('📍 [COORDINATE DETAILS] Loading details for toko:', tokoId);

    $('#coordinateDetailsModal').modal('show');
    $('#coordinateDetailsLoading').show();
    $('#coordinateDetailsContent').hide();
    $('#coordinateDetailsError').hide();
    $('#btnFixCoordinates').hide();

    $.ajax({
        url: `/toko/${tokoId}/coordinate-details`,
        type: 'GET',
        success: function (response) {
            if (response.status === 'success' && response.data) {
                displayCoordinateDetails(response.data);
            } else {
                showCoordinateDetailsError('Data koordinat tidak ditemukan');
            }
        },
        error: function (xhr, status, error) {
            console.error('❌ [COORDINATE DETAILS] Failed to load:', error);
            let errorMessage = 'Gagal memuat detail koordinat';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            showCoordinateDetailsError(errorMessage);
        }
    });
}

function displayCoordinateDetails(data) {
    console.log('📊 [COORDINATE DETAILS] Displaying data:', data);

    // Hide loading, show content
    $('#coordinateDetailsLoading').hide();
    $('#coordinateDetailsContent').show();
    $('#coordinateDetailsError').hide();

    $('#detail-nama-toko').text(data.toko_info.nama_toko);
    $('#detail-pemilik').text(data.toko_info.pemilik || '-');
    $('#detail-alamat').text(data.toko_info.alamat);

    if (data.coordinates.has_coordinates) {
        const lat = parseFloat(data.coordinates.latitude).toFixed(6);
        const lng = parseFloat(data.coordinates.longitude).toFixed(6);

        $('#detail-latitude').text(lat);
        $('#detail-longitude').text(lng);

        const googleMapsUrl = data.validation.google_maps_url ||
            `https://www.google.com/maps?q=${lat},${lng}`;
        $('#detail-google-maps-link').attr('href', googleMapsUrl);
    } else {
        $('#detail-latitude').text('N/A');
        $('#detail-longitude').text('N/A');
        $('#detail-google-maps-link').hide();
    }

    const provider = data.geocoding_info.provider || 'unknown';
    const accuracy = data.geocoding_info.accuracy || 'unknown';
    const qualityScore = data.geocoding_info.quality_score || 0;
    const confidence = data.geocoding_info.confidence || 0;

    let providerBadgeClass = 'badge-secondary';
    let providerText = provider;

    if (provider === 'interactive_map') {
        providerBadgeClass = 'badge-success';
        providerText = 'Peta Interaktif (Manual)';
    } else if (provider === 'nominatim') {
        providerBadgeClass = 'badge-primary';
        providerText = 'Nominatim API';
    } else if (provider === 'overpass') {
        providerBadgeClass = 'badge-info';
        providerText = 'Overpass API';
    } else {
        providerText = provider.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    $('#detail-provider').removeClass().addClass(`badge ${providerBadgeClass}`).text(providerText);

    let accuracyBadgeClass = 'badge-secondary';
    let accuracyText = accuracy;

    if (accuracy === 'rooftop' || accuracy === 'very_high') {
        accuracyBadgeClass = 'badge-success';
        accuracyText = 'Sangat Tinggi';
    } else if (accuracy === 'range_interpolated' || accuracy === 'high') {
        accuracyBadgeClass = 'badge-primary';
        accuracyText = 'Tinggi';
    } else if (accuracy === 'geometric_center' || accuracy === 'medium') {
        accuracyBadgeClass = 'badge-info';
        accuracyText = 'Sedang';
    } else if (accuracy === 'approximate' || accuracy === 'low') {
        accuracyBadgeClass = 'badge-warning';
        accuracyText = 'Rendah';
    }

    $('#detail-accuracy').removeClass().addClass(`badge ${accuracyBadgeClass}`).text(accuracyText);

    let qualityBadgeClass = 'badge-secondary';
    let qualityText = `${qualityScore}/100`;

    if (qualityScore >= 90) {
        qualityBadgeClass = 'quality-excellent';
        qualityText = `${qualityScore}/100 - Sangat Akurat`;
    } else if (qualityScore >= 80) {
        qualityBadgeClass = 'quality-good';
        qualityText = `${qualityScore}/100 - Akurat`;
    } else if (qualityScore >= 70) {
        qualityBadgeClass = 'quality-fair';
        qualityText = `${qualityScore}/100 - Cukup Akurat`;
    } else {
        qualityBadgeClass = 'quality-poor';
        qualityText = `${qualityScore}/100 - Kurang Akurat`;
    }

    $('#detail-quality-score').removeClass().addClass(`badge badge-lg ${qualityBadgeClass}`).text(qualityText);

    const confidencePercent = (confidence * 100).toFixed(1);
    let confidenceBadgeClass = 'badge-secondary';

    if (confidence >= 0.9) {
        confidenceBadgeClass = 'badge-success';
    } else if (confidence >= 0.7) {
        confidenceBadgeClass = 'badge-primary';
    } else if (confidence >= 0.5) {
        confidenceBadgeClass = 'badge-info';
    } else {
        confidenceBadgeClass = 'badge-warning';
    }

    $('#detail-confidence').removeClass().addClass(`badge ${confidenceBadgeClass}`).text(`${confidencePercent}%`);

    if (data.validation) {
        const inMalangRegion = data.validation.in_malang_region;
        const distance = data.validation.distance_from_malang_center;

        if (inMalangRegion) {
            $('#detail-region-status').html('<span class="badge badge-success"><i class="fas fa-check-circle"></i> Dalam Wilayah Malang</span>');
            $('#detail-out-of-region-warning').hide();
        } else {
            $('#detail-region-status').html('<span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Di Luar Wilayah Malang</span>');
            $('#detail-out-of-region-warning').show();
        }

        if (distance !== undefined && distance !== null) {
            $('#detail-distance').text(`${distance.toFixed(2)} km`);
        } else {
            $('#detail-distance').text('N/A');
        }
    }

    if (data.tolerance_check) {
        const toleranceHtml = data.tolerance_check.within_tolerance
            ? `<span class="badge badge-success">
                 <i class="fas fa-check-circle"></i> 
                 Dalam Toleransi (${data.tolerance_check.distance_meters}m dari posisi geocoding)
               </span>`
            : `<span class="badge badge-warning">
                 <i class="fas fa-exclamation-triangle"></i> 
                 Melebihi Toleransi (${data.tolerance_check.distance_meters}m, maks: ${data.tolerance_check.max_tolerance_meters}m)
               </span>`;

        let $toleranceElement = $('#detail-tolerance-status');
        if ($toleranceElement.length === 0) {
            $('#detail-confidence').parent().after(`
                <div class="form-group row">
                    <label class="col-sm-4 col-form-label">Status Toleransi:</label>
                    <div class="col-sm-8">
                        <div id="detail-tolerance-status" class="mt-2"></div>
                    </div>
                </div>
            `);
            $toleranceElement = $('#detail-tolerance-status');
        }
        $toleranceElement.html(toleranceHtml).show();
    }

    if (qualityScore < 70) {
        $('#detail-low-quality-warning').show();
        $('#btnFixCoordinates').show().data('toko-id', data.toko_info.toko_id);
    } else {
        $('#detail-low-quality-warning').hide();
        $('#btnFixCoordinates').hide();
    }

    console.log('✅ [COORDINATE DETAILS] Details displayed successfully');
}

function showCoordinateDetailsError(message) {
    $('#coordinateDetailsLoading').hide();
    $('#coordinateDetailsContent').hide();
    $('#coordinateDetailsError').show();
    $('#coordinateDetailsErrorMessage').text(message);
    $('#btnFixCoordinates').hide();
}


$(document).on('click', '#btnFixCoordinates', function () {
    const tokoId = $(this).data('toko-id');

    console.log('🔧 [COORDINATE DETAILS] Fix coordinates requested for toko:', tokoId);

    $('#coordinateDetailsModal').modal('hide');

    $(`.btn-edit[data-id="${tokoId}"]`).trigger('click');
});

if (typeof window !== 'undefined') {
    window.showCoordinateDetailsModal = showCoordinateDetailsModal;
    window.displayCoordinateDetails = displayCoordinateDetails;
    window.showCoordinateDetailsError = showCoordinateDetailsError;
}

console.log('✅ [COORDINATE DETAILS] Module loaded successfully');

/**
 * Coordinate Details Modal Functions
 * Task 14: Add coordinate details modal untuk validation
 * Requirements: 7.2, 7.3, 7.4
 */

/**
 * Show coordinate details modal for a toko
 * 
 * @param {string} tokoId - The toko ID to show details for
 */
function showCoordinateDetailsModal(tokoId) {
    console.log('📍 [COORDINATE DETAILS] Loading details for toko:', tokoId);

    // Show modal
    $('#coordinateDetailsModal').modal('show');

    // Show loading state
    $('#coordinateDetailsLoading').show();
    $('#coordinateDetailsContent').hide();
    $('#coordinateDetailsError').hide();
    $('#btnFixCoordinates').hide();

    // Fetch coordinate details from API
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

/**
 * Display coordinate details in the modal
 * 
 * @param {object} data - Coordinate details data from API
 */
function displayCoordinateDetails(data) {
    console.log('📊 [COORDINATE DETAILS] Displaying data:', data);

    // Hide loading, show content
    $('#coordinateDetailsLoading').hide();
    $('#coordinateDetailsContent').show();
    $('#coordinateDetailsError').hide();

    // Populate toko info
    $('#detail-nama-toko').text(data.toko_info.nama_toko);
    $('#detail-pemilik').text(data.toko_info.pemilik || '-');
    $('#detail-alamat').text(data.toko_info.alamat);

    // Populate coordinates
    if (data.coordinates.has_coordinates) {
        const lat = parseFloat(data.coordinates.latitude).toFixed(6);
        const lng = parseFloat(data.coordinates.longitude).toFixed(6);

        $('#detail-latitude').text(lat);
        $('#detail-longitude').text(lng);

        // Google Maps link
        const googleMapsUrl = data.validation.google_maps_url ||
            `https://www.google.com/maps?q=${lat},${lng}`;
        $('#detail-google-maps-link').attr('href', googleMapsUrl);
    } else {
        $('#detail-latitude').text('N/A');
        $('#detail-longitude').text('N/A');
        $('#detail-google-maps-link').hide();
    }

    // Populate geocoding quality info
    const provider = data.geocoding_info.provider || 'unknown';
    const accuracy = data.geocoding_info.accuracy || 'unknown';
    const qualityScore = data.geocoding_info.quality_score || 0;
    const confidence = data.geocoding_info.confidence || 0;

    // Provider badge
    let providerBadgeClass = 'badge-secondary';
    let providerText = provider;

    if (provider === 'interactive_map') {
        providerBadgeClass = 'badge-success';
        providerText = 'Peta Interaktif (Manual)';
    } else if (provider === 'google_maps') {
        providerBadgeClass = 'badge-primary';
        providerText = 'Google Maps API';
    } else if (provider === 'locationiq') {
        providerBadgeClass = 'badge-info';
        providerText = 'LocationIQ API';
    } else if (provider === 'nominatim') {
        providerBadgeClass = 'badge-warning';
        providerText = 'Nominatim OSM';
    } else if (provider === 'internal_street_database') {
        providerBadgeClass = 'badge-success';
        providerText = 'Database Jalan (Street Level)';
    } else if (provider === 'internal_database') {
        providerBadgeClass = 'badge-info';
        providerText = 'Database Kelurahan (Area Level)';
    }

    $('#detail-provider').removeClass().addClass(`badge ${providerBadgeClass}`).text(providerText);

    // Accuracy badge
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

    // Quality score badge
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

    // Confidence badge
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

    // Location validation
    if (data.validation) {
        const inMalangRegion = data.validation.in_malang_region;
        const distance = data.validation.distance_from_malang_center;

        // Region status
        if (inMalangRegion) {
            $('#detail-region-status').html('<span class="badge badge-success"><i class="fas fa-check-circle"></i> Dalam Wilayah Malang</span>');
            $('#detail-out-of-region-warning').hide();
        } else {
            $('#detail-region-status').html('<span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Di Luar Wilayah Malang</span>');
            $('#detail-out-of-region-warning').show();
        }

        // Distance from Malang center
        if (distance !== undefined && distance !== null) {
            $('#detail-distance').text(`${distance.toFixed(2)} km`);
        } else {
            $('#detail-distance').text('N/A');
        }
    }

    // Tolerance check display (if available)
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

        // Find or create tolerance status element
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

    // Show warnings for low quality coordinates
    if (qualityScore < 70) {
        $('#detail-low-quality-warning').show();
        $('#btnFixCoordinates').show().data('toko-id', data.toko_info.toko_id);
    } else {
        $('#detail-low-quality-warning').hide();
        $('#btnFixCoordinates').hide();
    }

    console.log('✅ [COORDINATE DETAILS] Details displayed successfully');
}

/**
 * Show error message in coordinate details modal
 * 
 * @param {string} message - Error message to display
 */
function showCoordinateDetailsError(message) {
    $('#coordinateDetailsLoading').hide();
    $('#coordinateDetailsContent').hide();
    $('#coordinateDetailsError').show();
    $('#coordinateDetailsErrorMessage').text(message);
    $('#btnFixCoordinates').hide();
}

/**
 * Handle "Perbaiki Koordinat" button click
 * Opens the edit modal for the toko to fix coordinates
 */
$(document).on('click', '#btnFixCoordinates', function () {
    const tokoId = $(this).data('toko-id');

    console.log('🔧 [COORDINATE DETAILS] Fix coordinates requested for toko:', tokoId);

    // Close coordinate details modal
    $('#coordinateDetailsModal').modal('hide');

    // Open edit modal
    // Trigger the edit button click for this toko
    $(`.btn-edit[data-id="${tokoId}"]`).trigger('click');
});

// Export functions for use in toko.js
if (typeof window !== 'undefined') {
    window.showCoordinateDetailsModal = showCoordinateDetailsModal;
    window.displayCoordinateDetails = displayCoordinateDetails;
    window.showCoordinateDetailsError = showCoordinateDetailsError;
}

console.log('✅ [COORDINATE DETAILS] Module loaded successfully');

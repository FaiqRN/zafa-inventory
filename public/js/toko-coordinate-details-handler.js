/**
 * Override the btn-detail click handler to use the new coordinate details modal
 * This file should be loaded after toko.js
 */

$(document).ready(function() {
    // Override the btn-detail click handler
    $(document).off('click', '.btn-detail');
    
    $(document).on('click', '.btn-detail', function() {
        const id = $(this).data('id');
        console.log('📍 [BTN-DETAIL] Opening coordinate details modal for toko:', id);
        
        // Use the new modal function
        if (typeof showCoordinateDetailsModal === 'function') {
            showCoordinateDetailsModal(id);
        } else {
            console.error('❌ [BTN-DETAIL] showCoordinateDetailsModal function not found');
            alert('Gagal membuka detail koordinat. Silakan refresh halaman.');
        }
    });
    
    console.log('✅ [BTN-DETAIL] Handler override loaded successfully');
});

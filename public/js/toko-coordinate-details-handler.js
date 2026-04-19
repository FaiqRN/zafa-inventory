$(document).ready(function() {
    $(document).off('click', '.btn-detail');
    
    $(document).on('click', '.btn-detail', function() {
        const id = $(this).data('id');
        console.log('📍 [BTN-DETAIL] Opening coordinate details modal for toko:', id);
        
        if (typeof showCoordinateDetailsModal === 'function') {
            showCoordinateDetailsModal(id);
        } else {
            console.error('❌ [BTN-DETAIL] showCoordinateDetailsModal function not found');
            alert('Gagal membuka detail koordinat. Silakan refresh halaman.');
        }
    });
    
    console.log('✅ [BTN-DETAIL] Handler override loaded successfully');
});

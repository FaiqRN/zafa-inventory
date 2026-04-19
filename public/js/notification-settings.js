$(document).ready(function() {
    $('#stock_threshold').on('input', function() {
        $('#previewStock').text($(this).val());
    });
    
    $('#return_deadline_days').on('input', function() {
        const deadline = parseInt($(this).val()) || 14;
        const warning = parseInt($('#pending_return_days').val()) || 2;
        const actualDays = deadline - warning;
        
        $('#previewDeadline').text(deadline);
        $('#exampleDays').text(actualDays);
    });
    
    $('#pending_return_days').on('input', function() {
        const warning = parseInt($(this).val()) || 2;
        const deadline = parseInt($('#return_deadline_days').val()) || 14;
        const actualDays = deadline - warning;
        
        $('#previewWarning').text(warning);
        $('#warningDaysPreview').text(warning);
        $('#exampleDays').text(actualDays);
    });
    
    $('#check_interval').on('input', function() {
        $('#previewInterval').text($(this).val());
        $('#intervalPreview').text($(this).val());
    });
    
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...').prop('disabled', true);
        
        const deadline = parseInt($('#return_deadline_days').val());
        const warning = parseInt($('#pending_return_days').val());
        const actualPendingDays = deadline - warning;
        
        const formData = {
            _token: window.csrfToken,
            _method: 'PUT',
            stock_threshold: $('#stock_threshold').val(),
            pending_return_days: actualPendingDays,
            return_deadline_days: deadline,
            check_interval: $('#check_interval').val()
        };
        
        $.ajax({
            url: window.notificationSettingsRoutes.update,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    AlertHelper.success('Berhasil!', response.message);
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan saat menyimpan';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                AlertHelper.error('Gagal!', message);
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    $('#btnReset').on('click', function() {
        AlertHelper.confirm(
            'Reset Pengaturan?',
            'Semua pengaturan akan dikembalikan ke nilai default.',
            'Ya, Reset!',
            'Batal'
        ).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.notificationSettingsRoutes.reset,
                    type: 'POST',
                    data: { _token: window.csrfToken },
                    success: function(response) {
                        if (response.success) {
                            // Update form values
                            $('#stock_threshold').val(response.settings.stock_threshold).trigger('input');
                            $('#return_deadline_days').val(response.settings.return_deadline_days).trigger('input');
                            $('#pending_return_days').val(response.settings.return_deadline_days - response.settings.pending_return_days).trigger('input');
                            $('#check_interval').val(response.settings.check_interval).trigger('input');
                            
                            AlertHelper.success('Berhasil!', response.message);
                        }
                    },
                    error: function() {
                        AlertHelper.error('Gagal!', 'Terjadi kesalahan saat reset');
                    }
                });
            }
        });
    });
});

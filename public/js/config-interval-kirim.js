$(document).ready(function () {
    var $form = $('#form-update-interval');

    if (!$form.length) {
        return;
    }

    var updateUrl = $form.data('update-url');
    var csrfToken = $form.data('csrf');

    // Preview badge saat input berubah
    $('#input-nilai').on('input', function () {
        var val = parseInt($(this).val(), 10);
        var $badge = $('#preview-badge');
        var $text = $('#preview-text');

        if (!isNaN(val) && val >= 0 && val <= 365) {
            $badge.show();
            if (val === 0) {
                $text.removeClass('badge-info badge-primary').addClass('badge-secondary')
                    .text('Tidak ada batasan minimum pengiriman');
            } else {
                $text.removeClass('badge-secondary').addClass('badge-info badge-primary')
                    .text('Pengiriman minimal setiap ' + val + ' hari');
            }
        } else {
            $badge.hide();
        }
    });

    // Submit form
    $form.on('submit', function (e) {
        e.preventDefault();

        var nilai = parseInt($('#input-nilai').val(), 10);

        if (isNaN(nilai) || nilai < 0 || nilai > 365) {
            AlertHelper.warning('Validasi Gagal', 'Nilai harus berupa bilangan bulat antara 0 dan 365.', false);
            return;
        }

        var confirmText = nilai === 0
            ? 'Anda akan menghapus batasan interval minimum pengiriman (nilai = 0). Lanjutkan?'
            : 'Interval minimum pengiriman akan diubah menjadi ' + nilai + ' hari. Lanjutkan?';

        AlertHelper.confirm('Konfirmasi Perubahan', confirmText, 'Ya, Simpan', 'Batal').then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            var $btn = $('#btn-simpan');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

            $.ajax({
                url: updateUrl,
                method: 'PUT',
                data: {
                    _token: csrfToken,
                    nilai: nilai,
                },
                success: function (res) {
                    if (res.success) {
                        var updatedValue = nilai;
                        if (res.data && res.data.nilai !== undefined) {
                            updatedValue = parseInt(res.data.nilai, 10);
                            if (isNaN(updatedValue)) {
                                updatedValue = nilai;
                            }
                        }

                        $('#nilai-saat-ini').text(updatedValue);
                        $('#input-nilai').val(updatedValue);
                        $('#preview-badge').hide();

                        var $badge = $('#nilai-badge');
                        if (!$badge.length) {
                            $badge = $('#nilai-saat-ini').closest('.text-center').find('.badge').first();
                        }

                        if ($badge.length) {
                            if (updatedValue === 0) {
                                $badge.removeClass('badge-primary').addClass('badge-secondary')
                                    .text('Tidak ada batasan minimum');
                            } else {
                                $badge.removeClass('badge-secondary').addClass('badge-primary')
                                    .text('Pengiriman minimal setiap ' + updatedValue + ' hari');
                            }
                        }

                        AlertHelper.success('Berhasil!', res.message);
                    } else {
                        AlertHelper.error('Gagal', res.message ?? 'Terjadi kesalahan.', false);
                    }
                },
                error: function (xhr) {
                    AlertHelper.ajaxError('Gagal', xhr, 'Terjadi kesalahan server.', false);
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Simpan Perubahan');
                },
            });
        });
    });
});

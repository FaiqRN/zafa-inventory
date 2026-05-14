// File JavaScript untuk Pemesanan - Enhanced Version
$(document).ready(function () {
    let currentStep = 1;
    const totalSteps = 3;
    const pemesananPermissions = window.pemesananPermissions || {};
    const canCreatePemesanan = !!pemesananPermissions.create;
    const canEditPemesanan = !!pemesananPermissions.edit;
    const canDeletePemesanan = !!pemesananPermissions.delete;

    function showPermissionDeniedMessage(message) {
        AlertHelper.fire('Akses Ditolak', message, 'warning');
    }

    function canSubmitCurrentForm() {
        const formAction = $('#form_action').val();
        if (formAction === 'edit') {
            return canEditPemesanan;
        }

        return canCreatePemesanan;
    }

    // Format tanggal
    function formatDate(date) {
        if (!date) return '-';

        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    }

    // Format mata uang
    function formatRupiah(angka) {
        return 'Rp ' + parseFloat(angka).toFixed(0).replace(/\d(?=(\d{3})+$)/g, '$&.');
    }



    // DataTable
    const table = $('#table-pemesanan').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/pemesanan/data',
            data: function (d) {
                d.barang_id = $('#filter_barang_id').val();
                d.status = $('#filter_status').val();
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'pemesanan_id', name: 'pemesanan_id' },
            { data: 'formatted_tanggal', name: 'tanggal_pemesanan' },
            { data: 'nama_pemesan', name: 'nama_pemesan' },
            { data: 'barang_nama', name: 'barang.nama_barang' },
            { data: 'jumlah_pesanan', name: 'jumlah_pesanan' },
            { data: 'formatted_total', name: 'total' },
            { data: 'status_label', name: 'status_pemesanan' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']]
    });

    // Filter action
    $('#btnFilter').click(function () {
        table.draw();
    });

    // Reset filter
    $('#resetFilter').click(function () {
        $('#filterForm')[0].reset();
        table.draw();
    });

    // Multi-Step Navigation
    function showStep(step) {
        // Hide all steps
        $('.step-content').hide();

        // Show current step
        $(`#step-${step}`).show();

        // Update step indicators
        $('.step-item').removeClass('active completed');
        for (let i = 1; i < step; i++) {
            $(`.step-item[data-step="${i}"]`).addClass('completed');
        }
        $(`.step-item[data-step="${step}"]`).addClass('active');

        // Update buttons
        if (step === 1) {
            $('#btnPrevStep').hide();
            $('#btnNextStep').show();
            $('#btnSubmit').hide();
        } else if (step === totalSteps) {
            $('#btnPrevStep').show();
            $('#btnNextStep').hide();

            if (canSubmitCurrentForm()) {
                $('#btnSubmit').show();
            } else {
                $('#btnSubmit').hide();
            }

            // Update summary - check if multi-item mode
            if (typeof window.updateSummaryMultiItem === 'function') {
                window.updateSummaryMultiItem();
            } else {
                updateSummary();
            }
        } else {
            $('#btnPrevStep').show();
            $('#btnNextStep').show();
            $('#btnSubmit').hide();
        }

        currentStep = step;
    }

    // Update summary on step 3
    function updateSummary() {
        const namaPemesan = $('#nama_pemesan').val() || '-';
        const barangText = $('#barang_id option:selected').text().split(' - ')[0] || '-';
        const jumlah = $('#jumlah_pesanan').val() || '0';
        const total = $('#total').val() || '0';

        $('#summary-nama').text(namaPemesan);
        $('#summary-barang').text(barangText);
        $('#summary-jumlah').text(jumlah + ' pcs');
        $('#summary-total').text(formatRupiah(total));
    }

    // Validate step
    function validateStep(step) {
        let isValid = true;
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        if (step === 1) {
            // Validate customer data
            const requiredFields = ['nama_pemesan', 'email_pemesan', 'no_telp_pemesan',
                'pemesanan_dari', 'alamat_pemesan', 'tanggal_pemesanan'];

            requiredFields.forEach(field => {
                const value = $(`#${field}`).val();
                if (!value || value.trim() === '') {
                    $(`#${field}`).addClass('is-invalid');
                    $(`#error-${field}`).text('Field ini wajib diisi');
                    isValid = false;
                }
            });

            // Validate email format
            const email = $('#email_pemesan').val();
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailPattern.test(email)) {
                $('#email_pemesan').addClass('is-invalid');
                $('#error-email_pemesan').text('Format email tidak valid');
                isValid = false;
            }
        } else if (step === 2) {
            // Check if multi-item validation exists (from pemesanan-multi-item.js)
            if (typeof window.validateStep2 === 'function') {
                isValid = window.validateStep2();
            } else {
                // Validate product data (single item mode)
                if (!$('#barang_id').val()) {
                    $('#barang_id').addClass('is-invalid');
                    $('#error-barang_id').text('Pilih barang terlebih dahulu');
                    isValid = false;
                }

                const jumlah = parseInt($('#jumlah_pesanan').val()) || 0;
                if (jumlah <= 0) {
                    $('#jumlah_pesanan').addClass('is-invalid');
                    $('#error-jumlah_pesanan').text('Jumlah pesanan harus lebih dari 0');
                    isValid = false;
                }

                // Check stock
                const stok = parseInt($('#barang_id option:selected').data('stok')) || 0;
                if (jumlah > stok) {
                    $('#jumlah_pesanan').addClass('is-invalid');
                    $('#error-jumlah_pesanan').text(`Stok tidak mencukupi! Stok tersedia: ${stok}`);
                    isValid = false;
                }

                const total = parseFloat($('#total').val()) || 0;
                if (total <= 0) {
                    AlertHelper.fire('Perhatian', 'Total harga harus lebih dari 0', 'warning');
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    // Next step button
    $('#btnNextStep').click(function () {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
    });

    // Previous step button
    $('#btnPrevStep').click(function () {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    // Show/hide date fields based on status
    function toggleDateFields(status) {
        $('#tanggal-diproses-container, #tanggal-dikirim-container, #tanggal-selesai-container').hide();
        $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').prop('required', false);

        if (status === 'diproses' || status === 'dikirim' || status === 'selesai') {
            $('#tanggal-diproses-container').show();
            $('#tanggal_diproses').prop('required', true);
        }

        if (status === 'dikirim' || status === 'selesai') {
            $('#tanggal-dikirim-container').show();
            $('#tanggal_dikirim').prop('required', true);
        }

        if (status === 'selesai') {
            $('#tanggal-selesai-container').show();
            $('#tanggal_selesai').prop('required', true);
        }
    }

    // Status change handler
    $('#status_pemesanan').change(function () {
        toggleDateFields($(this).val());

        const status = $(this).val();
        const today = new Date().toISOString().substr(0, 10);

        if (status === 'diproses' && !$('#tanggal_diproses').val()) {
            $('#tanggal_diproses').val(today);
        }

        if (status === 'dikirim' && !$('#tanggal_dikirim').val()) {
            $('#tanggal_dikirim').val(today);
        }

        if (status === 'selesai' && !$('#tanggal_selesai').val()) {
            $('#tanggal_selesai').val(today);
        }

        if (status === 'pending' || status === 'dibatalkan') {
            $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').val('');
        }
    });

    // Barang selection handler with real-time info
    $('#barang_id').change(function () {
        const selectedOption = $(this).find(':selected');
        const harga = parseFloat(selectedOption.data('harga')) || 0;
        const stok = parseInt(selectedOption.data('stok')) || 0;

        if ($(this).val()) {
            // Show info box
            $('#barang-info').removeClass('d-none');
            $('#info-harga-satuan').text(formatRupiah(harga));
            $('#info-stok').text(stok + ' pcs');

            // Set price
            $('#harga_satuan').val(harga);

            // Trigger total calculation
            $('#harga_satuan').trigger('input');

            // Check stock warning
            checkStockWarning();
        } else {
            $('#barang-info').addClass('d-none');
            $('#harga_satuan').val('');
            $('#total').val('');
            $('#stok-warning').text('').removeClass('text-danger text-success');
        }
    });

    // Calculate total price with real-time validation
    $('#jumlah_pesanan').on('input', function () {
        const jumlah = parseInt($(this).val()) || 0;
        const harga = parseFloat($('#harga_satuan').val()) || 0;
        const total = jumlah * harga;

        $('#total').val(total);

        // Check stock availability
        checkStockWarning();
    });

    // Real-time stock validation
    function checkStockWarning() {
        const jumlah = parseInt($('#jumlah_pesanan').val()) || 0;
        const stok = parseInt($('#barang_id option:selected').data('stok')) || 0;
        const warningEl = $('#stok-warning');

        if (jumlah > 0) {
            if (jumlah > stok) {
                warningEl.text(`âš ï¸ Stok tidak mencukupi! Tersedia: ${stok} pcs`)
                    .removeClass('text-success')
                    .addClass('text-danger');
                $('#jumlah_pesanan').addClass('is-invalid');
            } else {
                warningEl.text(`âœ“ Stok tersedia: ${stok - jumlah} pcs tersisa`)
                    .removeClass('text-danger')
                    .addClass('text-success');
                $('#jumlah_pesanan').removeClass('is-invalid');
            }
        } else {
            warningEl.text('').removeClass('text-danger text-success');
        }
    }

    // Tambah Pemesanan Button
    $('#btnTambahPemesanan').click(function () {
        if (!canCreatePemesanan) {
            showPermissionDeniedMessage('Anda tidak memiliki izin untuk menambah pemesanan.');
            return;
        }

        $('#form_action').val('add');
        $('#formPemesanan')[0].reset();
        $('#modalPemesananLabel').text('Tambah Pemesanan');

        // Reset multi-step
        showStep(1);

        // Set today's date as default
        const today = new Date();
        const formattedDate = today.toISOString().substr(0, 10);
        $('#tanggal_pemesanan').val(formattedDate);

        // Default status is pending
        $('#status_pemesanan').val('pending');
        toggleDateFields('pending');

        // Hide barang info
        $('#barang-info').addClass('d-none');
        $('#stok-warning').text('').removeClass('text-danger text-success');

        // Initialize Select2 - REMOVED
        // initSelect2();

        // Get new pemesanan ID
        $.ajax({
            url: '/pemesanan/get-id',
            type: 'GET',
            success: function (response) {
                const nomorPemesanan = response.nomor_pemesanan || response.pemesanan_id;
                $('#pemesanan_id').val(nomorPemesanan);
                $('#no_pemesanan').val(nomorPemesanan);
            },
            error: function (xhr) {
                AlertHelper.fire('Error', 'Gagal mengambil nomor pemesanan', 'error');
            }
        });

        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#modalPemesanan').modal('show');
    });

    // Edit pemesanan
    $(document).on('click', '.btn-edit', function () {
        if (!canEditPemesanan) {
            showPermissionDeniedMessage('Anda tidak memiliki izin untuk mengubah pemesanan.');
            return;
        }

        const id = $(this).data('id');
        $('#form_action').val('edit');
        $('#modalPemesananLabel').text('Edit Pemesanan');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('.hidden-date-input').remove();

        // Reset to step 1
        showStep(1);

        // Initialize Select2 - REMOVED
        // initSelect2();

        $.ajax({
            url: `/pemesanan/${id}`,
            type: 'GET',
            success: function (response) {
                const data = response.data;

                // Fill basic fields
                const nomorPemesanan = data.nomor_pemesanan || data.pemesanan_id;
                $('#pemesanan_id').val(data.pemesanan_id);
                $('#no_pemesanan').val(nomorPemesanan);
                $('#nama_pemesan').val(data.nama_pemesan);
                $('#alamat_pemesan').val(data.alamat_pemesan);
                $('#barang_id').val(data.barang_id).trigger('change');
                $('#jumlah_pesanan').val(data.jumlah_pesanan);
                $('#total').val(data.total);
                $('#pemesanan_dari').val(data.pemesanan_dari);
                $('#metode_pembayaran').val(data.metode_pembayaran);
                $('#status_pemesanan').val(data.status_pemesanan);
                $('#no_telp_pemesan').val(data.no_telp_pemesan);
                $('#email_pemesan').val(data.email_pemesan);
                $('#catatan_pemesanan').val(data.catatan_pemesanan);

                // Calculate harga satuan
                const hargaSatuan = data.jumlah_pesanan > 0 ? (data.total / data.jumlah_pesanan) : 0;
                $('#harga_satuan').val(hargaSatuan);

                // Handle tanggal pemesanan
                $('#tanggal_pemesanan').val(data.tanggal_pemesanan);
                $('<input>').attr({
                    type: 'hidden',
                    name: 'tanggal_pemesanan',
                    class: 'hidden-date-input',
                    value: data.tanggal_pemesanan
                }).appendTo('#formPemesanan');
                $('#tanggal_pemesanan').prop('disabled', true);

                // Handle status dates
                $('#tanggal-diproses-container, #tanggal-dikirim-container, #tanggal-selesai-container').hide();
                $('#tanggal_diproses, #tanggal_dikirim, #tanggal_selesai').prop('required', false);

                if (data.tanggal_diproses) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'tanggal_diproses',
                        class: 'hidden-date-input',
                        value: data.tanggal_diproses
                    }).appendTo('#formPemesanan');
                    $('#tanggal_diproses').val(data.tanggal_diproses).prop('disabled', true);
                }

                if (data.tanggal_dikirim) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'tanggal_dikirim',
                        class: 'hidden-date-input',
                        value: data.tanggal_dikirim
                    }).appendTo('#formPemesanan');
                    $('#tanggal_dikirim').val(data.tanggal_dikirim).prop('disabled', true);
                }

                if (data.tanggal_selesai) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'tanggal_selesai',
                        class: 'hidden-date-input',
                        value: data.tanggal_selesai
                    }).appendTo('#formPemesanan');
                    $('#tanggal_selesai').val(data.tanggal_selesai).prop('disabled', true);
                }

                toggleDateFields(data.status_pemesanan);

                $('#modalPemesanan').modal('show');
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                AlertHelper.fire('Error', response.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Form submission
    $('#formPemesanan').submit(function (e) {
        e.preventDefault();

        const formAction = $('#form_action').val();
        const pemesananId = $('#pemesanan_id').val();

        if (formAction === 'add' && !canCreatePemesanan) {
            showPermissionDeniedMessage('Anda tidak memiliki izin untuk menambah pemesanan.');
            return;
        }

        if (formAction === 'edit' && !canEditPemesanan) {
            showPermissionDeniedMessage('Anda tidak memiliki izin untuk mengubah pemesanan.');
            return;
        }

        let url, method;
        if (formAction === 'add') {
            url = '/pemesanan/store';
            method = 'POST';
        } else {
            url = `/pemesanan/${pemesananId}`;
            method = 'PUT';
        }

        // Final validation
        let isValid = true;
        $('.is-invalid').removeClass('is-invalid');

        // Validate required fields
        const requiredFields = ['nama_pemesan', 'email_pemesan', 'no_telp_pemesan',
            'pemesanan_dari', 'alamat_pemesan', 'barang_id',
            'jumlah_pesanan', 'metode_pembayaran'];

        requiredFields.forEach(field => {
            const value = $(`#${field}`).val();
            if (!value || value.trim() === '') {
                $(`#${field}`).addClass('is-invalid');
                $(`#error-${field}`).text('Field ini wajib diisi');
                isValid = false;
            }
        });

        // Validate date fields if visible
        if ($('#tanggal-diproses-container').is(':visible') && !$('#tanggal_diproses').prop('disabled') && !$('#tanggal_diproses').val()) {
            $('#tanggal_diproses').addClass('is-invalid');
            $('#error-tanggal_diproses').text('Tanggal diproses harus diisi');
            isValid = false;
        }

        if ($('#tanggal-dikirim-container').is(':visible') && !$('#tanggal_dikirim').prop('disabled') && !$('#tanggal_dikirim').val()) {
            $('#tanggal_dikirim').addClass('is-invalid');
            $('#error-tanggal_dikirim').text('Tanggal dikirim harus diisi');
            isValid = false;
        }

        if ($('#tanggal-selesai-container').is(':visible') && !$('#tanggal_selesai').prop('disabled') && !$('#tanggal_selesai').val()) {
            $('#tanggal_selesai').addClass('is-invalid');
            $('#error-tanggal_selesai').text('Tanggal selesai harus diisi');
            isValid = false;
        }

        if (!isValid) {
            AlertHelper.fire('Perhatian', 'Mohon lengkapi semua field yang wajib diisi', 'warning');
            return false;
        }

        // Show loading
        AlertHelper.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit form
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function (response) {
                $('#modalPemesanan').modal('hide');
                AlertHelper.fire('Sukses', response.message, 'success');
                table.ajax.reload();
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                if (response.errors) {
                    // Display validation errors
                    $.each(response.errors, function (field, messages) {
                        const inputField = $(`#${field}`);
                        if (inputField.length) {
                            inputField.addClass('is-invalid');
                            $(`#error-${field}`).text(messages[0]);
                        }
                    });
                    AlertHelper.fire('Error', 'Terdapat kesalahan pada form. Mohon periksa kembali.', 'error');
                } else {
                    AlertHelper.fire('Error', response.message || 'Terjadi kesalahan', 'error');
                }
            }
        });
    });

    // Detail pemesanan
    $(document).on('click', '.btn-detail', function () {
        const id = $(this).data('id');

        $.ajax({
            url: `/pemesanan/${id}`,
            type: 'GET',
            success: function (response) {
                const data = response.data;
                $('#detail_pemesanan_id').val(data.pemesanan_id);
                $('#detail_nama_pemesan').val(data.nama_pemesan);
                $('#detail_tanggal_pemesanan').val(formatDate(data.tanggal_pemesanan));
                $('#detail_alamat_pemesan').val(data.alamat_pemesan);
                $('#detail_barang').val(data.barang ? data.barang.nama_barang : '-');
                $('#detail_jumlah_pesanan').val(data.jumlah_pesanan);
                $('#detail_harga_satuan').val(formatRupiah(data.jumlah_pesanan > 0 ? (data.total / data.jumlah_pesanan) : 0));
                $('#detail_total').val(formatRupiah(data.total));
                $('#detail_pemesanan_dari').val(data.pemesanan_dari);
                $('#detail_metode_pembayaran').val(data.metode_pembayaran);
                $('#detail_email_pemesan').val(data.email_pemesan);
                $('#detail_no_telp_pemesan').val(data.no_telp_pemesan);
                $('#detail_catatan_pemesanan').val(data.catatan_pemesanan);

                const statusMap = {
                    'pending': 'Menunggu',
                    'diproses': 'Diproses',
                    'dikirim': 'Dikirim',
                    'selesai': 'Selesai',
                    'dibatalkan': 'Dibatalkan'
                };
                $('#detail_status_pemesanan').val(statusMap[data.status_pemesanan] || 'Tidak Diketahui');

                $('#detail_tanggal_diproses').val(formatDate(data.tanggal_diproses));
                $('#detail_tanggal_dikirim').val(formatDate(data.tanggal_dikirim));
                $('#detail_tanggal_selesai').val(formatDate(data.tanggal_selesai));

                $('#detail-tanggal-diproses-container, #detail-tanggal-dikirim-container, #detail-tanggal-selesai-container').hide();

                if (data.status_pemesanan === 'diproses' || data.status_pemesanan === 'dikirim' || data.status_pemesanan === 'selesai') {
                    $('#detail-tanggal-diproses-container').show();
                }

                if (data.status_pemesanan === 'dikirim' || data.status_pemesanan === 'selesai') {
                    $('#detail-tanggal-dikirim-container').show();
                }

                if (data.status_pemesanan === 'selesai') {
                    $('#detail-tanggal-selesai-container').show();
                }

                $('#modalDetailPemesanan').modal('show');
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                AlertHelper.fire('Error', response.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Delete confirmation
    $(document).on('click', '.btn-delete', function () {
        if (!canDeletePemesanan) {
            showPermissionDeniedMessage('Anda tidak memiliki izin untuk menghapus pemesanan.');
            return;
        }

        const id = $(this).data('id');
        const nama = $(this).data('nama');
        $('#delete-item-name').text(nama);

        $('#deleteModal').modal('show');

        $('#btnDelete').off('click').on('click', function () {
            $.ajax({
                url: `/pemesanan/${id}`,
                type: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    $('#deleteModal').modal('hide');
                    AlertHelper.fire('Sukses', response.message, 'success');
                    table.ajax.reload();
                },
                error: function (xhr) {
                    const response = xhr.responseJSON;
                    AlertHelper.fire('Error', response.message || 'Terjadi kesalahan', 'error');
                }
            });
        });
    });

    // Initialize
    toggleDateFields($('#status_pemesanan').val());

    // Date validation
    $('#tanggal_dikirim').change(function () {
        const tanggalDiproses = $('#tanggal_diproses').val();
        const tanggalDikirim = $(this).val();

        if (tanggalDiproses && tanggalDikirim && new Date(tanggalDikirim) < new Date(tanggalDiproses)) {
            $(this).addClass('is-invalid');
            $('#error-tanggal_dikirim').text('Tanggal dikirim tidak boleh sebelum tanggal diproses');
        } else {
            $(this).removeClass('is-invalid');
            $('#error-tanggal_dikirim').text('');
        }
    });

    $('#tanggal_selesai').change(function () {
        const tanggalDikirim = $('#tanggal_dikirim').val();
        const tanggalSelesai = $(this).val();

        if (tanggalDikirim && tanggalSelesai && new Date(tanggalSelesai) < new Date(tanggalDikirim)) {
            $(this).addClass('is-invalid');
            $('#error-tanggal_selesai').text('Tanggal selesai tidak boleh sebelum tanggal dikirim');
        } else {
            $(this).removeClass('is-invalid');
            $('#error-tanggal_selesai').text('');
        }
    });
});
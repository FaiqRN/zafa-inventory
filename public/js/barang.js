$(document).ready(function () {
    // Variables
    let selectedBarangId = null;
    let selectedBarangData = null;

    // Load data barang saat halaman dibuka
    loadBarangData();

    // ========================================
    // LOAD DATA BARANG
    // ========================================
    function loadBarangData() {
        $.ajax({
            url: '/barang/list',
            type: 'GET',
            cache: false,
            beforeSend: function () {
                $('#barang-table-body').html(`
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                `);
            },
            success: function (response) {
                if (response.status === 'success' && response.data && response.data.length > 0) {
                    renderBarangTable(response.data);
                } else {
                    $('#barang-table-body').html(`
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Tidak ada data barang
                            </td>
                        </tr>
                    `);
                }
            },
            error: function () {
                $('#barang-table-body').html(`
                    <tr>
                        <td colspan="6" class="text-center py-4 text-danger">
                            Gagal memuat data
                        </td>
                    </tr>
                `);
            }
        });
    }

    // ========================================
    // RENDER TABEL BARANG
    // ========================================
    function renderBarangTable(data) {
        let html = '';
        $.each(data, function (index, item) {
            const harga = formatRupiah(item.harga_awal_barang);
            const keterangan = item.keterangan || '-';
            html += `
                <tr class="barang-row" data-id="${item.barang_id}" data-nama="${item.nama_barang}" data-satuan="${item.satuan}">
                    <td>${item.barang_kode}</td>
                    <td>${item.nama_barang}</td>
                    <td class="text-right">${harga}</td>
                    <td class="text-center"><span class="badge badge-info">${item.satuan}</span></td>
                    <td class="td-keterangan">${keterangan}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-info btn-action-view" data-id="${item.barang_id}" title="Lihat Detail Stok">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action-delete" data-id="${item.barang_id}" data-nama="${item.nama_barang}" title="Hapus">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#barang-table-body').html(html);

        // Re-highlight jika ada yang dipilih
        if (selectedBarangId) {
            $(`.barang-row[data-id="${selectedBarangId}"]`).addClass('selected-row');
        }
    }

    // ========================================
    // LOAD DETAIL STOK
    // ========================================
    function loadStokDetail(barangId, namaBarang, satuan) {
        selectedBarangId = barangId;
        selectedBarangData = { nama: namaBarang, satuan: satuan };

        // Highlight row
        $('.barang-row').removeClass('selected-row');
        $(`.barang-row[data-id="${barangId}"]`).addClass('selected-row');

        // Update header
        $('#detail-title').html(`<i class="fas fa-clipboard-list mr-2"></i>Detail Stok → ${namaBarang}`);

        // Show action buttons
        $('#detail-action-buttons').show();

        // Hide empty state, show table
        $('#detail-content').hide();
        $('#detail-table-container').show();

        // Load data stok
        $.ajax({
            url: `/barang/${barangId}/stok`,
            type: 'GET',
            cache: false,
            beforeSend: function () {
                $('#stok-table-body').html(`
                    <tr>
                        <td colspan="2" class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                `);
            },
            success: function (response) {
                if (response.status === 'success' && response.data && response.data.length > 0) {
                    renderStokTable(response.data, satuan);
                } else {
                    $('#stok-table-body').html(`
                        <tr>
                            <td colspan="2" class="text-center py-4 text-muted">
                                Belum ada data stok
                            </td>
                        </tr>
                    `);
                }
            },
            error: function () {
                $('#stok-table-body').html(`
                    <tr>
                        <td colspan="2" class="text-center py-4 text-danger">
                            Gagal memuat data stok
                        </td>
                    </tr>
                `);
            }
        });
    }

    // ========================================
    // RENDER TABEL STOK
    // ========================================
    function renderStokTable(data, satuan) {
        let html = '';
        $.each(data, function (index, item) {
            html += `
                <tr>
                    <td>${formatTanggal(item.tanggal_stock_barang)}</td>
                    <td class="text-center"><span class="badge badge-success badge-lg">${item.stok} ${satuan}</span></td>
                </tr>
            `;
        });
        $('#stok-table-body').html(html);
    }

    // ========================================
    // EVENT: KLIK ICON MATA / ROW
    // ========================================
    $(document).on('click', '.btn-action-view', function (e) {
        e.stopPropagation();
        const barangId = $(this).data('id');
        const row = $(`.barang-row[data-id="${barangId}"]`);
        const namaBarang = row.data('nama');
        const satuan = row.data('satuan');
        loadStokDetail(barangId, namaBarang, satuan);
    });

    $(document).on('click', '.barang-row', function (e) {
        if ($(e.target).closest('.btn').length) return;
        const barangId = $(this).data('id');
        const namaBarang = $(this).data('nama');
        const satuan = $(this).data('satuan');
        loadStokDetail(barangId, namaBarang, satuan);
    });

    // ========================================
    // EVENT: TAMBAH STOK (FIFO) - FROM DETAIL PANEL
    // ========================================
    $('#btnTambahStok').click(function () {
        if (!selectedBarangId) {
            showAlert('warning', 'Pilih barang terlebih dahulu');
            return;
        }
        window.location.href = `/barang/${selectedBarangId}/tambah-stok`;
    });

    // ========================================
    // EVENT: RIWAYAT STOK (FIFO) - FROM DETAIL PANEL
    // ========================================
    $('#btnRiwayatStok').click(function () {
        if (!selectedBarangId) {
            showAlert('warning', 'Pilih barang terlebih dahulu');
            return;
        }
        window.location.href = `/barang/${selectedBarangId}/riwayat-stok`;
    });
    // ========================================
    // EVENT: TAMBAH BARANG
    // ========================================
    $('#btnTambah').click(function () {
        $('#modalBarangLabel').html('<i class="fas fa-box mr-2"></i>Tambah Barang');
        $('#formBarang')[0].reset();
        $('#barang_id').val('');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Generate kode barang otomatis
        $.ajax({
            url: '/barang/generate-kode',
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#barang_kode').val(response.kode);
                }
            }
        });

        $('#modalBarang').modal('show');
    });

    // ========================================
    // EVENT: SUBMIT FORM BARANG
    // ========================================
    $('#formBarang').submit(function (e) {
        e.preventDefault();

        const barangId = $('#barang_id').val();
        const url = barangId ? `/barang/update/${barangId}` : '/barang/store';
        const method = 'POST';

        let formData = $(this).serialize();
        if (barangId) {
            formData += '&_method=PUT';
        }

        $.ajax({
            url: url,
            type: method,
            data: formData,
            beforeSend: function () {
                $('#btnSimpan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            },
            success: function (response) {
                if (response.success) {
                    $('#modalBarang').modal('hide');
                    loadBarangData();
                    showAlert('success', response.message);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function (field, messages) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#error-${field}`).text(messages[0]);
                    });
                } else {
                    const errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data';
                    showAlert('danger', errorMsg);
                    console.error('Error response:', xhr.responseJSON);
                }
            },
            complete: function () {
                $('#btnSimpan').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
            }
        });
    });

    // ========================================
    // EVENT: HAPUS BARANG - SWEETALERT
    // ========================================
    $(document).on('click', '.btn-action-delete', function (e) {
        e.stopPropagation();
        const barangId = $(this).data('id');
        const namaBarang = $(this).data('nama');

        // SweetAlert confirmation
        Swal.fire({
            title: 'Konfirmasi Hapus',
            html: `<div style="text-align: center;">
                    <i class="fas fa-trash-alt" style="font-size: 80px; color: #dc3545; margin-bottom: 20px;"></i>
                    <p style="font-size: 16px; margin-bottom: 10px;">Apakah Anda yakin ingin menghapus?</p>
                    <p style="font-size: 18px; font-weight: bold; color: #333;">${namaBarang}</p>
                   </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash mr-1"></i> Hapus',
            cancelButtonText: '<i class="fas fa-times mr-1"></i> Batal',
            width: '500px',
            padding: '2rem',
            customClass: {
                popup: 'swal-wide',
                confirmButton: 'btn btn-danger btn-lg px-4',
                cancelButton: 'btn btn-secondary btn-lg px-4'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                deleteBarang(barangId);
            }
        });
    });

    function deleteBarang(barangId) {
        $.ajax({
            url: `/barang/destroy/${barangId}`,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () {
                Swal.fire({
                    title: 'Menghapus...',
                    html: '<i class="fas fa-spinner fa-spin fa-3x"></i>',
                    showConfirmButton: false,
                    allowOutsideClick: false
                });
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadBarangData();
                    if (selectedBarangId === barangId) {
                        $('#detail-content').show();
                        $('#detail-table-container').hide();
                        $('#detail-action-buttons').hide();
                        $('#detail-title').html('<i class="fas fa-clipboard-list mr-2"></i>Detail Stok Barang');
                        selectedBarangId = null;
                    }
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal menghapus data',
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    // ========================================
    // SEARCH
    // ========================================
    $('#searchBarang').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#barang-table-body tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $('#searchStok').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#stok-table-body tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    function formatRupiah(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }

    function formatTanggal(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('id-ID', options);
    }

    function showAlert(type, message) {
        const iconMap = {
            success: 'check-circle',
            danger: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };

        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${iconMap[type]} mr-2"></i>
                <strong>${message}</strong>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        $('#alert-container').html(alert);
        $('html, body').animate({ scrollTop: 0 }, 400);

        setTimeout(function () {
            $('.alert').fadeOut(400, function () {
                $(this).remove();
            });
        }, 5000);
    }
});
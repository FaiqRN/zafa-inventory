$(document).ready(function () {
    // Variables
    let selectedBarangId = null;
    let selectedBarangData = null;
    const permissionState = window.barangPermissions || {};
    const canCreateBarang = Boolean(permissionState.canCreate);
    const canEditBarang = Boolean(permissionState.canEdit);
    const canDeleteBarang = Boolean(permissionState.canDelete);

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
                        <td colspan="7" class="text-center py-4 text-muted">
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
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data barang
                            </td>
                        </tr>
                    `);
                }
            },
            error: function () {
                $('#barang-table-body').html(`
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
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
            const shelfLife = item.shelf_life ? `${item.shelf_life} Hari` : '-';
            const keterangan = item.keterangan || '-';
            const deleteButton = canDeleteBarang
                ? `<button class="btn btn-sm btn-danger btn-action-delete" data-id="${item.barang_id}" data-nama="${item.nama_barang}" title="Hapus">
                            <i class="fas fa-trash-alt"></i>
                        </button>`
                : '';
            html += `
                <tr class="barang-row" data-id="${item.barang_id}" data-kode="${item.barang_kode}" data-nama="${item.nama_barang}" data-satuan="${item.satuan}">
                    <td>${item.barang_kode}</td>
                    <td>${item.nama_barang}</td>
                    <td class="text-right">${harga}</td>
                    <td class="text-center"><span class="badge badge-info">${item.satuan}</span></td>
                    <td class="text-center"><span class="badge badge-info">${shelfLife}</span></td>
                    <td class="td-keterangan">${keterangan}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-info btn-action-view" data-id="${item.barang_id}" title="Lihat Detail Stok">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${deleteButton}
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
    function loadStokDetail(barangId, namaBarang, satuan, barangKode = '-') {
        selectedBarangId = barangId;
        selectedBarangData = { kode: barangKode, nama: namaBarang, satuan: satuan };

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
            const sisaStok = item.sisa_stok !== undefined ? item.sisa_stok : item.stok;
            const terpakai = item.stok - sisaStok;
            const badgeClass = sisaStok > 0 ? 'badge-success' : 'badge-secondary';
            
            html += `
                <tr>
                    <td>${formatTanggal(item.tanggal_stock_barang)}</td>
                    <td class="text-center">
                        <span class="badge ${badgeClass} badge-lg">${sisaStok} ${satuan}</span>
                        ${terpakai > 0 ? `<br><small class="text-muted">(Terpakai: ${terpakai})</small>` : ''}
                    </td>
                </tr>
            `;
        });
        $('#stok-table-body').html(html);
    }

    // ========================================
    // LOAD RIWAYAT STOK (POPUP)
    // ========================================
    function loadRiwayatStok(barangId) {
        $('#riwayat-stok-table-body').html(`
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Memuat riwayat stok...
                </td>
            </tr>
        `);

        const info = `${selectedBarangData.nama} (${selectedBarangData.kode}) - Satuan: ${selectedBarangData.satuan}`;
        $('#riwayatBarangInfo').text(info);
        $('#searchRiwayatModal').val('');
        $('#modalRiwayatStok').modal('show');

        $.ajax({
            url: `/barang/${barangId}/stok`,
            type: 'GET',
            cache: false,
            success: function (response) {
                if (response.status === 'success' && response.data && response.data.length > 0) {
                    renderRiwayatStokTable(response.data);
                } else {
                    $('#riwayat-stok-table-body').html(`
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada riwayat stok
                            </td>
                        </tr>
                    `);
                }
            },
            error: function () {
                $('#riwayat-stok-table-body').html(`
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            Gagal memuat riwayat stok
                        </td>
                    </tr>
                `);
                AlertHelper.error('Error!', 'Gagal memuat riwayat stok barang');
            }
        });
    }

    function renderRiwayatStokTable(data) {
        let html = '';

        $.each(data, function (index, item) {
            const stokAwalRaw = item.stok_awal !== undefined && item.stok_awal !== null ? item.stok_awal : item.stok;
            const stokAwal = Number(stokAwalRaw || 0);
            const sisaStok = Number(item.sisa_stok || 0);
            const terpakai = Math.max(0, stokAwal - sisaStok);
            const catatan = item.catatan || '-';
            const badgeClass = sisaStok > 0 ? 'success' : 'secondary';
            const statusText = sisaStok > 0 ? 'Tersedia' : 'Habis';

            html += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td>${formatTanggal(item.tanggal_stock_barang)}</td>
                    <td class="text-right">${formatNumber(stokAwal)}</td>
                    <td class="text-right">${formatNumber(sisaStok)}</td>
                    <td class="text-right">${formatNumber(terpakai)}</td>
                    <td>${catatan}</td>
                    <td class="text-center"><span class="badge badge-${badgeClass}">${statusText}</span></td>
                </tr>
            `;
        });

        $('#riwayat-stok-table-body').html(html);
    }

    // ========================================
    // EVENT: KLIK ICON MATA / ROW
    // ========================================
    $(document).on('click', '.btn-action-view', function (e) {
        e.stopPropagation();
        const barangId = $(this).data('id');
        const row = $(`.barang-row[data-id="${barangId}"]`);
        const barangKode = row.data('kode');
        const namaBarang = row.data('nama');
        const satuan = row.data('satuan');
        loadStokDetail(barangId, namaBarang, satuan, barangKode);
    });

    $(document).on('click', '.barang-row', function (e) {
        if ($(e.target).closest('.btn').length) return;
        const barangId = $(this).data('id');
        const barangKode = $(this).data('kode');
        const namaBarang = $(this).data('nama');
        const satuan = $(this).data('satuan');
        loadStokDetail(barangId, namaBarang, satuan, barangKode);
    });

    // ========================================
    // EVENT: TAMBAH STOK (FIFO) - FROM DETAIL PANEL
    // ========================================
    $('#btnTambahStok').click(function () {
        if (!canEditBarang) {
            AlertHelper.error('Anda tidak memiliki izin untuk menambah stok barang.');
            return;
        }

        if (!selectedBarangId) {
            AlertHelper.warning('Pilih barang terlebih dahulu');
            return;
        }

        if (!selectedBarangData) {
            AlertHelper.error('Data barang tidak ditemukan', 'Silakan pilih ulang barang dari tabel');
            return;
        }

        $('#formTambahStok')[0].reset();
        $('#tanggal').val($('#tanggal').attr('max'));
        $('.is-invalid').removeClass('is-invalid');
        $('#formTambahStok .invalid-feedback').text('');

        $('#stok_barang_kode').val(selectedBarangData.kode || '-');
        $('#stok_nama_barang').val(selectedBarangData.nama || '-');
        $('#stok_satuan_barang').val(selectedBarangData.satuan || '-');
        $('#stok_satuan_append').text(selectedBarangData.satuan || 'Unit');

        $('#formTambahStok').attr('action', `/barang/${selectedBarangId}/tambah-stok`);
        $('#modalTambahStok').modal('show');
    });

    // ========================================
    // EVENT: RIWAYAT STOK (FIFO) - FROM DETAIL PANEL
    // ========================================
    $('#btnRiwayatStok').click(function () {
        if (!selectedBarangId) {
            AlertHelper.warning('Pilih barang terlebih dahulu');
            return;
        }

        if (!selectedBarangData) {
            AlertHelper.error('Data barang tidak ditemukan', 'Silakan pilih ulang barang dari tabel');
            return;
        }

        loadRiwayatStok(selectedBarangId);
    });
    // ========================================
    // EVENT: TAMBAH BARANG
    // ========================================
    $('#btnTambah').click(function () {
        if (!canCreateBarang) {
            AlertHelper.error('Anda tidak memiliki izin untuk menambah barang.');
            return;
        }

        $('#modalBarangLabel').html('<i class="fas fa-box mr-2"></i>Tambah Barang');
        
        // Cek apakah form barang ada (untuk menghindari error di halaman lain)
        if ($('#formBarang').length > 0 && $('#formBarang')[0]) {
            $('#formBarang')[0].reset();
        }
        
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
        const isUpdate = Boolean(barangId);

        if (isUpdate && !canEditBarang) {
            AlertHelper.error('Anda tidak memiliki izin untuk mengubah barang.');
            return;
        }

        if (!isUpdate && !canCreateBarang) {
            AlertHelper.error('Anda tidak memiliki izin untuk menambah barang.');
            return;
        }

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
                $('#btnSimpanBarang').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            },
            success: function (response) {
                if (response.success) {
                    $('#modalBarang').modal('hide');
                    loadBarangData();
                    AlertHelper.success(response.message);
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
                    AlertHelper.error(errorMsg);
                    console.error('Error response:', xhr.responseJSON);
                }
            },
            complete: function () {
                $('#btnSimpanBarang').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
            }
        });
    });

    // ========================================
    // EVENT: SUBMIT FORM TAMBAH STOK
    // ========================================
    $('#formTambahStok').submit(function (e) {
        e.preventDefault();

        if (!canEditBarang) {
            AlertHelper.error('Anda tidak memiliki izin untuk menambah stok barang.');
            return;
        }

        const form = $(this);
        const submitBtn = $('#btnSimpanStok');
        const actionUrl = form.attr('action');

        if (!actionUrl) {
            AlertHelper.error('Aksi tidak valid', 'Silakan tutup pop-up lalu pilih barang kembali');
            return;
        }

        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');

        $.ajax({
            url: actionUrl,
            method: 'POST',
            data: form.serialize(),
            beforeSend: function () {
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            },
            success: function (response) {
                if (response.success) {
                    $('#modalTambahStok').modal('hide');
                    AlertHelper.success(response.message);
                    loadStokDetail(selectedBarangId, selectedBarangData.nama, selectedBarangData.satuan, selectedBarangData.kode);
                    loadBarangData();
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function (field, messages) {
                        const input = form.find(`#${field}`);
                        const errorEl = form.find(`#error-${field}`);

                        input.addClass('is-invalid');
                        errorEl.text(messages[0]);
                    });

                    AlertHelper.error('Validasi Gagal', 'Periksa kembali form tambah stok');
                    return;
                }

                const errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan stok';
                AlertHelper.error('Error!', errorMsg);
            },
            complete: function () {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
            }
        });
    });

    // ========================================
    // EVENT: HAPUS BARANG - SWEETALERT
    // ========================================
    $(document).on('click', '.btn-action-delete', function (e) {
        e.stopPropagation();

        if (!canDeleteBarang) {
            AlertHelper.error('Anda tidak memiliki izin untuk menghapus barang.');
            return;
        }

        const barangId = $(this).data('id');
        const namaBarang = $(this).data('nama');

        // Use AlertHelper for confirmation
        AlertHelper.confirmDelete('Hapus Barang?', `Apakah Anda yakin ingin menghapus "${namaBarang}"?`).then((result) => {
            if (result.isConfirmed) {
                deleteBarang(barangId);
            }
        });
    });

    function deleteBarang(barangId) {
        if (!canDeleteBarang) {
            AlertHelper.error('Anda tidak memiliki izin untuk menghapus barang.');
            return;
        }

        $.ajax({
            url: `/barang/destroy/${barangId}`,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () {
                AlertHelper.loading('Menghapus...', 'Mohon tunggu sebentar');
            },
            success: function (response) {
                if (response.success) {
                    AlertHelper.success(response.message);
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
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Gagal menghapus data';
                AlertHelper.error(errorMsg);
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

    $('#searchRiwayatModal').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#riwayat-stok-table-body tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    function formatRupiah(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }

    function formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    function formatTanggal(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('id-ID', options);
    }
});
// Multi-Item Pemesanan JavaScript
$(document).ready(function () {
    let itemCounter = 0;
    let barangList = []; // Data barang dari server
    let barangLoaded = false; // Flag untuk track apakah data sudah loaded

    // Load barang data
    function loadBarangData() {
        return $.ajax({
            url: '/barang/list',
            method: 'GET',
            success: function (response) {
                if (response.status === 'success' && Array.isArray(response.data)) {
                    barangList = response.data.map(function (item) {
                        return {
                            id: item.barang_id,
                            kode: item.barang_kode || '',
                            nama: item.nama_barang,
                            harga: parseFloat(item.harga_awal_barang) || 0,
                            stok: parseInt(item.stok) || 0,
                            satuan: item.satuan || 'pcs'
                        };
                    });
                    barangLoaded = true;
                } else {
                    barangList = [];
                    barangLoaded = false;
                }
            },
            error: function () {
                barangList = [];
                barangLoaded = false;
            }
        });
    }

    // Load data saat halaman dimuat
    loadBarangData();

    // Format currency
    function formatRupiah(angka) {
        return 'Rp ' + parseFloat(angka || 0).toFixed(0).replace(/\d(?=(\d{3})+$)/g, '$&.');
    }

    // Generate item row HTML
    function generateItemRow(index) {
        let barangOptions = '<option value="">-- Pilih Barang --</option>';

        // Loop through barangList dari API
        if (barangList && barangList.length > 0) {
            barangList.forEach(function (barang) {
                const displayText = `${barang.kode} - ${barang.nama} (Stok: ${barang.stok})`;
                barangOptions += `<option value="${barang.id}" data-harga="${barang.harga}" data-stok="${barang.stok}" data-nama="${barang.nama}">${displayText}</option>`;
            });
        } else {
            // Fallback: ambil dari hidden select yang berisi data dari blade
            $('#barang_fallback option').each(function () {
                if ($(this).val()) {
                    barangOptions += `<option value="${$(this).val()}" 
                                        data-harga="${$(this).data('harga')}" 
                                        data-stok="${$(this).data('stok')}"
                                        data-nama="${$(this).data('nama')}">
                                        ${$(this).text()}
                                    </option>`;
                }
            });
        }

        return `
            <div class="card mb-3 item-row" data-index="${index}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">Barang <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm barang-select" data-index="${index}" required>
                                    ${barangOptions}
                                </select>
                                <small class="text-muted harga-info-${index}"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm jumlah-input" 
                                       data-index="${index}" min="1" placeholder="0" required>
                                <small class="text-muted stok-info-${index}"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">Subtotal</label>
                                <input type="text" class="form-control form-control-sm subtotal-display" 
                                       data-index="${index}" readonly value="Rp 0">
                                <input type="hidden" class="subtotal-value" data-index="${index}" value="0">
                                <input type="hidden" class="harga-satuan" data-index="${index}" value="0">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="small d-block">&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-item" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Add new item row
    function addItemRow() {
        if ($('#items-container .empty-item-placeholder').length) {
            $('#items-container').empty();
        }

        itemCounter++;
        const newRow = generateItemRow(itemCounter);
        $('#items-container').append(newRow);
        updateItemsCount();
    }

    // Remove item row
    $(document).on('click', '.btn-remove-item', function () {
        const index = $(this).data('index');
        $(`.item-row[data-index="${index}"]`).remove();

        calculateTotal();
        updateItemsCount();
    });

    // Handle barang selection change
    $(document).on('change', '.barang-select', function () {
        const index = $(this).data('index');
        const selectedOption = $(this).find('option:selected');
        const harga = selectedOption.data('harga') || 0;
        const stok = selectedOption.data('stok') || 0;

        // Update hidden harga satuan
        $(`.harga-satuan[data-index="${index}"]`).val(harga);

        // Show info
        $(`.harga-info-${index}`).html(`<i class="fas fa-tag"></i> ${formatRupiah(harga)}`);
        $(`.stok-info-${index}`).html(`<i class="fas fa-boxes"></i> Stok: ${stok}`);

        // Calculate subtotal
        calculateItemSubtotal(index);
    });

    // Handle jumlah change
    $(document).on('input', '.jumlah-input', function () {
        const index = $(this).data('index');
        calculateItemSubtotal(index);
    });

    // Calculate item subtotal
    function calculateItemSubtotal(index) {
        const jumlah = parseFloat($(`.jumlah-input[data-index="${index}"]`).val()) || 0;
        const hargaSatuan = parseFloat($(`.harga-satuan[data-index="${index}"]`).val()) || 0;
        const subtotal = jumlah * hargaSatuan;

        $(`.subtotal-display[data-index="${index}"]`).val(formatRupiah(subtotal));
        $(`.subtotal-value[data-index="${index}"]`).val(subtotal);

        calculateTotal();
    }

    // Calculate total keseluruhan
    function calculateTotal() {
        let totalKeseluruhan = 0;
        let totalItems = 0;

        $('.subtotal-value').each(function () {
            const subtotal = parseFloat($(this).val()) || 0;
            totalKeseluruhan += subtotal;
        });

        $('.jumlah-input').each(function () {
            const jumlah = parseInt($(this).val()) || 0;
            totalItems += jumlah;
        });

        $('#total-keseluruhan').text(formatRupiah(totalKeseluruhan));
        $('#total-items-count').text(totalItems + ' item');

        // Update hidden inputs untuk submit
        $('#total').val(totalKeseluruhan);
        $('#jumlah_pesanan').val(totalItems);
    }

    // Update items count
    function updateItemsCount() {
        const count = $('.item-row').length;
        if (count === 0) {
            $('#items-container').html(`
                <div class="alert alert-info text-center empty-item-placeholder">
                    <i class="fas fa-info-circle"></i> Belum ada barang yang ditambahkan. 
                    Klik tombol "Tambah Barang" untuk menambah item pesanan.
                </div>
            `);
            return;
        }

        $('#items-container .empty-item-placeholder').remove();
    }

    // Add item button click
    $('#btnAddItem').click(function () {
        addItemRow();
    });

    // Collect items data untuk submit
    function collectItemsData(showAlert = true) {
        const items = [];
        let isValid = true;

        $('.item-row').each(function () {
            const index = $(this).data('index');
            const barangId = $(`.barang-select[data-index="${index}"]`).val();
            const selectedBarang = $(`.barang-select[data-index="${index}"] option:selected`);
            const barangNama = selectedBarang.data('nama') || selectedBarang.text();
            const jumlah = $(`.jumlah-input[data-index="${index}"]`).val();
            const hargaSatuan = $(`.harga-satuan[data-index="${index}"]`).val();
            const subtotal = $(`.subtotal-value[data-index="${index}"]`).val();

            if (!barangId || !jumlah || jumlah <= 0) {
                isValid = false;
                return false;
            }

            items.push({
                barang_id: barangId,
                barang_nama: barangNama,
                jumlah: parseInt(jumlah),
                harga_satuan: parseFloat(hargaSatuan),
                subtotal: parseFloat(subtotal)
            });
        });

        if (!isValid) {
            if (showAlert) {
                Swal.fire({
                    icon: 'error',
                    title: 'Data Tidak Lengkap',
                    text: 'Pastikan semua barang telah dipilih dan jumlah telah diisi!',
                    confirmButtonColor: '#dc3545'
                });
            }
            return null;
        }

        if (items.length === 0) {
            if (showAlert) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Ada Barang',
                    text: 'Harap tambahkan minimal 1 barang!',
                    confirmButtonColor: '#ffc107'
                });
            }
            return null;
        }

        return items;
    }

    // Update summary di step 3
    window.updateSummaryMultiItem = function () {
        const namaPemesan = $('#nama_pemesan').val() || '-';
        const noTelp = $('#no_telp_pemesan').val() || '-';
        const items = collectItemsData(false);

        $('#summary-nama').text(namaPemesan);
        $('#summary-telp').text(noTelp);

        if (items && items.length > 0) {
            let itemsHTML = '<div class="table-responsive"><table class="table table-sm mb-0">';
            itemsHTML += '<thead><tr><th>Barang</th><th class="text-center">Qty</th><th class="text-right">Subtotal</th></tr></thead><tbody>';

            items.forEach(item => {
                itemsHTML += `
                    <tr>
                        <td>${item.barang_nama}</td>
                        <td class="text-center">${item.jumlah}</td>
                        <td class="text-right">${formatRupiah(item.subtotal)}</td>
                    </tr>
                `;
            });

            itemsHTML += '</tbody></table></div>';
            $('#summary-items-list').html(itemsHTML);

            const totalItems = items.reduce((sum, item) => sum + item.jumlah, 0);
            const totalKeseluruhan = items.reduce((sum, item) => sum + item.subtotal, 0);

            $('#summary-total-items').text(totalItems + ' item');
            $('#summary-total').text(formatRupiah(totalKeseluruhan));
        } else {
            $('#summary-items-list').html('<p class="text-muted text-center">Tidak ada barang</p>');
            $('#summary-total-items').text('0 item');
            $('#summary-total').text('Rp 0');
        }
    };

    // Validate step 2 (items)
    window.validateStep2 = function () {
        const items = collectItemsData(true);
        if (!items || items.length === 0) {
            return false;
        }

        // Set items data untuk dikirim
        $('#items_data').val(JSON.stringify(items));

        // Set barang_id pertama untuk kompatibilitas dengan database existing
        $('#barang_id').val(items[0].barang_id);

        // Kirim nomor_pemesanan yang sudah di-generate
        if (!$('#nomor_pemesanan').length) {
            $('<input>').attr({
                type: 'hidden',
                id: 'nomor_pemesanan',
                name: 'nomor_pemesanan'
            }).appendTo('#formPemesanan');
        }
        $('#nomor_pemesanan').val($('#no_pemesanan').val());

        return true;
    };

    // Initialize with one item row when modal opens
    $('#btnTambahPemesanan').click(function () {
        // Reload barang data jika belum loaded atau kosong
        if (!barangLoaded || barangList.length === 0) {
            loadBarangData().always(function () {
                $('#items-container').empty();
                itemCounter = 0;
                addItemRow();
            });
        } else {
            $('#items-container').empty();
            itemCounter = 0;
            addItemRow();
        }
    });

    // Set tanggal default ke hari ini
    const today = new Date().toISOString().split('T')[0];
    $('#tanggal_pemesanan').val(today);
});

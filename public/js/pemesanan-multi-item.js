// Multi-Item Pemesanan JavaScript
$(document).ready(function() {
    let itemsData = []; // Array untuk menyimpan semua items
    let itemCounter = 0;
    let barangList = []; // Data barang dari server

    // Load barang data
    $.ajax({
        url: '/barang/list-all', // Endpoint untuk get all barang
        method: 'GET',
        success: function(response) {
            barangList = response;
        },
        error: function() {
            // Fallback: ambil dari dropdown yang sudah ada
            $('#barang_id option').each(function() {
                if ($(this).val()) {
                    barangList.push({
                        id: $(this).val(),
                        nama: $(this).text().split(' - ')[0],
                        harga: $(this).data('harga'),
                        stok: $(this).data('stok')
                    });
                }
            });
        }
    });

    // Format currency
    function formatRupiah(angka) {
        return 'Rp ' + parseFloat(angka || 0).toFixed(0).replace(/\d(?=(\d{3})+$)/g, '$&.');
    }

    // Generate item row HTML
    function generateItemRow(index) {
        let barangOptions = '<option value="">-- Pilih Barang --</option>';
        
        // Loop through barangList dari dropdown yang ada
        $('#barang_id option').each(function() {
            if ($(this).val()) {
                const barangId = $(this).val();
                const namaBarang = $(this).text();
                const harga = $(this).data('harga');
                const stok = $(this).data('stok');
                
                barangOptions += `<option value="${barangId}" data-harga="${harga}" data-stok="${stok}">${namaBarang}</option>`;
            }
        });

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
        itemCounter++;
        const newRow = generateItemRow(itemCounter);
        $('#items-container').append(newRow);
        updateItemsCount();
    }

    // Remove item row
    $(document).on('click', '.btn-remove-item', function() {
        const index = $(this).data('index');
        $(`.item-row[data-index="${index}"]`).remove();
        
        // Remove from itemsData
        itemsData = itemsData.filter(item => item.index !== index);
        
        calculateTotal();
        updateItemsCount();
    });

    // Handle barang selection change
    $(document).on('change', '.barang-select', function() {
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
    $(document).on('input', '.jumlah-input', function() {
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
        
        $('.subtotal-value').each(function() {
            const subtotal = parseFloat($(this).val()) || 0;
            totalKeseluruhan += subtotal;
        });
        
        $('.jumlah-input').each(function() {
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
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Belum ada barang yang ditambahkan. 
                    Klik tombol "Tambah Barang" untuk menambah item pesanan.
                </div>
            `);
        }
    }

    // Add item button click
    $('#btnAddItem').click(function() {
        addItemRow();
    });

    // Collect items data untuk submit
    function collectItemsData() {
        const items = [];
        let isValid = true;
        
        $('.item-row').each(function() {
            const index = $(this).data('index');
            const barangId = $(`.barang-select[data-index="${index}"]`).val();
            const barangNama = $(`.barang-select[data-index="${index}"] option:selected`).text();
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
            Swal.fire({
                icon: 'error',
                title: 'Data Tidak Lengkap',
                text: 'Pastikan semua barang telah dipilih dan jumlah telah diisi!',
                confirmButtonColor: '#dc3545'
            });
            return null;
        }
        
        if (items.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Barang',
                text: 'Harap tambahkan minimal 1 barang!',
                confirmButtonColor: '#ffc107'
            });
            return null;
        }
        
        return items;
    }

    // Update summary di step 3
    function updateSummary() {
        const namaPemesan = $('#nama_pemesan').val() || '-';
        const noTelp = $('#no_telp_pemesan').val() || '-';
        const items = collectItemsData();
        
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
    }

    // Validate step 2 (items)
    function validateStep2() {
        const items = collectItemsData();
        if (!items || items.length === 0) {
            return false;
        }
        
        // Set items data untuk dikirim
        $('#items_data').val(JSON.stringify(items));
        
        // Set barang_id pertama untuk kompatibilitas dengan database existing
        $('#barang_id').val(items[0].barang_id);
        
        return true;
    }

    // Next step button
    $('#btnNextStep').click(function() {
        if (currentStep === 2) {
            if (!validateStep2()) {
                return;
            }
        }
        
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    });

    // Previous step button
    $('#btnPrevStep').click(function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    // Show step function (sesuaikan dengan yang ada di pemesanan.js)
    function showStep(step) {
        $('.step-content').hide();
        $(`#step-${step}`).show();
        
        $('.step-item').removeClass('active completed');
        for (let i = 1; i < step; i++) {
            $(`.step-item[data-step="${i}"]`).addClass('completed');
        }
        $(`.step-item[data-step="${step}"]`).addClass('active');
        
        if (step === 1) {
            $('#btnPrevStep').hide();
            $('#btnNextStep').show();
            $('#btnSubmit').hide();
        } else if (step === totalSteps) {
            $('#btnPrevStep').show();
            $('#btnNextStep').hide();
            $('#btnSubmit').show();
            updateSummary();
        } else {
            $('#btnPrevStep').show();
            $('#btnNextStep').show();
            $('#btnSubmit').hide();
        }
        
        currentStep = step;
    }

    // Initialize with one item row when modal opens
    $('#btnTambahPemesanan').click(function() {
        $('#items-container').empty();
        itemCounter = 0;
        addItemRow(); // Add first row
        showStep(1);
    });

    // Set tanggal default ke hari ini
    const today = new Date().toISOString().split('T')[0];
    $('#tanggal_pemesanan').val(today);
});

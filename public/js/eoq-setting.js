$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const eoqPermissions = window.eoqPermissions || {};
    const canCreateEoq = !!eoqPermissions.create;
    const canEditEoq = !!eoqPermissions.edit;
    const canDeleteEoq = !!eoqPermissions.delete;

    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: function() {
                return $(this).data('placeholder');
            }
        });
    }

    loadBiayaPesanGlobal();

    // ==========================================
    // BIAYA PESAN GLOBAL
    // ==========================================

    function loadBiayaPesanGlobal() {
        $.ajax({
            url: '/eoq-setting/biaya-pesan-global',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    globalData = response.data; // Store for search
                    $('#searchBiayaGlobal').val(''); // Reset search
                    renderBiayaPesanGlobal(response.data, response.total);
                }
            },
            error: function(xhr) {
                AlertHelper.error('Gagal memuat data biaya pemesanan global');
            }
        });
    }

    function renderBiayaPesanGlobal(data, total) {
        const tbody = $('#tbody-biaya-global');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox"></i><br>
                        Belum ada data biaya pemesanan
                    </td>
                </tr>
            `);
        } else {
            data.forEach((item, index) => {
                let actionButtons = '';
                if (canEditEoq) {
                    actionButtons += `
                        <button class="btn btn-warning btn-edit-global" data-id="${item.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
                if (canDeleteEoq) {
                    actionButtons += `
                        <button class="btn btn-danger btn-delete-global" data-id="${item.id}" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }

                const actionHtml = actionButtons
                    ? `<div class="btn-group btn-group-sm">${actionButtons}</div>`
                    : '<span class="text-muted">-</span>';

                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nama_biaya}</td>
                        <td class="text-right">${formatRupiah(item.nominal)}</td>
                        <td class="text-center">
                            ${actionHtml}
                        </td>
                    </tr>
                `);
            });
        }

        $('#total-global').text(formatRupiah(total));
    }

    $('#btn-add-global').click(function() {
        if (!canCreateEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menambah biaya EOQ.');
            return;
        }

        $('#modal-global-title').text('Tambah Biaya Pemesanan Global');
        $('#form-biaya-global')[0].reset();
        $('#global-id').val('');
        $('#modal-biaya-global').modal('show');
    });

    $(document).on('click', '.btn-edit-global', function() {
        if (!canEditEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk mengubah biaya EOQ.');
            return;
        }

        const id = $(this).data('id');
        
        $.ajax({
            url: `/eoq-setting/biaya-pesan-global/${id}/edit`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#modal-global-title').text('Edit Biaya Pemesanan Global');
                    $('#global-id').val(response.data.id);
                    $('#global-nama-biaya').val(response.data.nama_biaya);
                    $('#global-nominal').val(response.data.nominal);
                    $('#global-keterangan').val(response.data.keterangan);
                    $('#modal-biaya-global').modal('show');
                }
            },
            error: function() {
                AlertHelper.error('Gagal memuat data');
            }
        });
    });

    $('#form-biaya-global').submit(function(e) {
        e.preventDefault();
        
        const id = $('#global-id').val();

        if (id && !canEditEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk mengubah biaya EOQ.');
            return;
        }

        if (!id && !canCreateEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menambah biaya EOQ.');
            return;
        }

        const url = id ? `/eoq-setting/biaya-pesan-global/${id}` : '/eoq-setting/biaya-pesan-global';
        const method = id ? 'PUT' : 'POST';
        
        const formData = {
            nama_biaya: $('#global-nama-biaya').val(),
            nominal: $('#global-nominal').val(),
            keterangan: $('#global-keterangan').val()
        };

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#modal-biaya-global').modal('hide');
                    AlertHelper.success(response.message);
                    loadBiayaPesanGlobal();
                    
                    const selectedToko = $('#select-toko').val();
                    if (selectedToko) {
                        loadBiayaPesanToko(selectedToko);
                    }
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal menyimpan data';
                AlertHelper.error(message);
            }
        });
    });

    $(document).on('click', '.btn-delete-global', function() {
        if (!canDeleteEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menghapus biaya EOQ.');
            return;
        }

        const id = $(this).data('id');
        
        AlertHelper.confirmDelete('Hapus Biaya Pemesanan?', 'Data yang dihapus tidak dapat dikembalikan').then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/eoq-setting/biaya-pesan-global/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            AlertHelper.success(response.message);
                            loadBiayaPesanGlobal();
                            
                            const selectedToko = $('#select-toko').val();
                            if (selectedToko) {
                                loadBiayaPesanToko(selectedToko);
                            }
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Gagal menghapus data';
                        AlertHelper.error(message);
                    }
                });
            }
        });
    });

    // ==========================================
    // BIAYA PESAN TOKO
    // ==========================================

    $('#select-toko').change(function() {
        const tokoId = $(this).val();
        
        if (tokoId) {
            loadBiayaPesanToko(tokoId);
            $('#toko-biaya-section').show();
            $('#toko-empty-state').hide();
        } else {
            $('#toko-biaya-section').hide();
            $('#toko-empty-state').show();
        }
    });

    function loadBiayaPesanToko(tokoId) {
        $.ajax({
            url: `/eoq-setting/biaya-pesan-toko/${tokoId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    tokoData = response.data; 
                    tokoTotal = response.total;
                    $('#searchBiayaToko').val(''); 
                    renderBiayaPesanToko(response.data, response.total);
                }
            },
            error: function() {
                AlertHelper.error('Gagal memuat data biaya pemesanan toko');
            }
        });
    }

    function renderBiayaPesanToko(data, total) {
        const tbody = $('#tbody-biaya-toko');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        Tidak ada data biaya pemesanan global
                    </td>
                </tr>
            `);
        } else {
            data.forEach((item, index) => {
                const overrideDisplay = item.is_override 
                    ? `<strong class="text-warning">${formatRupiah(item.nominal_toko)}</strong>`
                    : `<span class="text-muted">-</span>`;

                let actionBtn = '<span class="text-muted">-</span>';
                if (item.is_override && canDeleteEoq) {
                    actionBtn = `<button class="btn btn-xs btn-danger btn-remove-override" data-id="${item.override_id}" title="Hapus Override">
                        <i class="fas fa-times"></i>
                       </button>`;
                } else if (!item.is_override && canCreateEoq) {
                    actionBtn = `<button class="btn btn-xs btn-warning btn-set-override"
                        data-nama="${item.nama_biaya}"
                        data-global="${item.nominal_global}"
                        title="Set Override">
                        <i class="fas fa-edit"></i>
                       </button>`;
                }

                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nama_biaya}</td>
                        <td class="text-right">${formatRupiah(item.nominal_global)}</td>
                        <td class="text-right">${overrideDisplay}</td>
                        <td class="text-center">${actionBtn}</td>
                    </tr>
                `);
            });
        }

        $('#total-toko').text(formatRupiah(total));
    }

    // Set override for toko
    $(document).on('click', '.btn-set-override', function() {
        if (!canCreateEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menyimpan override biaya.');
            return;
        }

        const namaBiaya = $(this).data('nama');
        const nominalGlobal = $(this).data('global');
        const tokoId = $('#select-toko').val();

        $('#toko-id').val(tokoId);
        $('#toko-nama-biaya').val(namaBiaya);
        $('#toko-nama-display').text(namaBiaya);
        $('#toko-nominal-global').text(formatNumber(nominalGlobal));
        $('#toko-nominal').val('');
        $('#toko-keterangan').val('');
        
        $('#modal-biaya-toko').modal('show');
    });

    $('#form-biaya-toko').submit(function(e) {
        e.preventDefault();

        if (!canCreateEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menyimpan override biaya.');
            return;
        }
        
        const formData = {
            toko_id: $('#toko-id').val(),
            nama_biaya: $('#toko-nama-biaya').val(),
            nominal: $('#toko-nominal').val(),
            keterangan: $('#toko-keterangan').val()
        };

        $.ajax({
            url: '/eoq-setting/biaya-pesan-toko',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#modal-biaya-toko').modal('hide');
                    AlertHelper.success(response.message);
                    loadBiayaPesanToko(formData.toko_id);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal menyimpan override';
                AlertHelper.error(message);
            }
        });
    });

    $(document).on('click', '.btn-remove-override', function() {
        if (!canDeleteEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menghapus override biaya.');
            return;
        }

        const id = $(this).data('id');
        const tokoId = $('#select-toko').val();
        
        AlertHelper.confirmDelete('Hapus Override?', 'Biaya akan kembali menggunakan nilai global').then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/eoq-setting/biaya-pesan-toko/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            AlertHelper.success(response.message);
                            loadBiayaPesanToko(tokoId);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Gagal menghapus override';
                        AlertHelper.error(message);
                    }
                });
            }
        });
    });

    // ==========================================
    // BIAYA SIMPAN
    // ==========================================

    $('#select-barang').change(function() {
        const barangId = $(this).val();
        
        if (barangId) {
            loadBiayaSimpan(barangId);
            $('#barang-biaya-section').show();
            $('#barang-empty-state').hide();
        } else {
            $('#barang-biaya-section').hide();
            $('#barang-empty-state').show();
        }
    });

    function loadBiayaSimpan(barangId) {
        $.ajax({
            url: `/eoq-setting/biaya-simpan/${barangId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    simpanData = response.data; // Store for search
                    simpanSummary = {
                        total_persentase: response.total_persentase,
                        harga_pokok: response.harga_pokok,
                        total_biaya: response.total_biaya
                    };
                    $('#searchBiayaSimpan').val(''); // Reset search
                    renderBiayaSimpan(response.data, response.total_persentase, response.harga_pokok, response.total_biaya);
                }
            },
            error: function() {
                AlertHelper.error('Gagal memuat data biaya penyimpanan');
            }
        });
    }

    function renderBiayaSimpan(data, totalPersentase, hargaPokok, totalBiaya) {
        const tbody = $('#tbody-biaya-simpan');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox"></i><br>
                        Belum ada komponen biaya penyimpanan
                    </td>
                </tr>
            `);
        } else {
            data.forEach((item, index) => {
                const biaya = parseFloat(item.harga_pokok) * parseFloat(item.persentase) / 100;
                let actionButtons = '';

                if (canEditEoq) {
                    actionButtons += `
                        <button class="btn btn-info btn-edit-simpan" data-id="${item.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }

                if (canDeleteEoq) {
                    actionButtons += `
                        <button class="btn btn-danger btn-delete-simpan" data-id="${item.id}" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }

                const actionHtml = actionButtons
                    ? `<div class="btn-group btn-group-sm">${actionButtons}</div>`
                    : '<span class="text-muted">-</span>';
                
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.nama_komponen}</td>
                        <td class="text-right">${formatRupiah(item.harga_pokok)}</td>
                        <td class="text-right">${formatNumber(item.persentase)}%</td>
                        <td class="text-right">${formatRupiah(biaya)}</td>
                        <td class="text-center">
                            ${actionHtml}
                        </td>
                    </tr>
                `);
            });
        }

        $('#total-persentase').text(formatNumber(totalPersentase) + '%');
        $('#total-biaya-simpan').text(formatRupiah(totalBiaya));
    }

    $('#btn-add-simpan').click(function() {
        if (!canCreateEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menambah komponen biaya simpan.');
            return;
        }

        const barangId = $('#select-barang').val();
        
        if (!barangId) {
            AlertHelper.warning('Pilih produk terlebih dahulu');
            return;
        }

        $('#modal-simpan-title').text('Tambah Komponen Biaya Penyimpanan');
        $('#form-biaya-simpan')[0].reset();
        $('#simpan-id').val('');
        $('#simpan-barang-id').val(barangId);
        $('#modal-biaya-simpan').modal('show');
    });

    $(document).on('click', '.btn-edit-simpan', function() {
        if (!canEditEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk mengubah komponen biaya simpan.');
            return;
        }

        const id = $(this).data('id');
        
        $.ajax({
            url: `/eoq-setting/biaya-simpan/${id}/edit`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#modal-simpan-title').text('Edit Komponen Biaya Penyimpanan');
                    $('#simpan-id').val(response.data.id);
                    $('#simpan-barang-id').val(response.data.barang_id);
                    $('#simpan-harga-pokok').val(response.data.harga_pokok);
                    $('#simpan-nama-komponen').val(response.data.nama_komponen);
                    $('#simpan-persentase').val(response.data.persentase);
                    $('#simpan-keterangan').val(response.data.keterangan);
                    $('#modal-biaya-simpan').modal('show');
                }
            },
            error: function() {
                AlertHelper.error('Gagal memuat data');
            }
        });
    });

    $('#form-biaya-simpan').submit(function(e) {
        e.preventDefault();
        
        const id = $('#simpan-id').val();

        if (id && !canEditEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk mengubah komponen biaya simpan.');
            return;
        }

        if (!id && !canCreateEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menambah komponen biaya simpan.');
            return;
        }

        const url = id ? `/eoq-setting/biaya-simpan/${id}` : '/eoq-setting/biaya-simpan';
        const method = id ? 'PUT' : 'POST';
        
        const formData = {
            barang_id: $('#simpan-barang-id').val(),
            harga_pokok: $('#simpan-harga-pokok').val(),
            nama_komponen: $('#simpan-nama-komponen').val(),
            persentase: $('#simpan-persentase').val(),
            keterangan: $('#simpan-keterangan').val()
        };

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#modal-biaya-simpan').modal('hide');
                    AlertHelper.success(response.message);
                    loadBiayaSimpan(formData.barang_id);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal menyimpan data';
                AlertHelper.error(message);
            }
        });
    });

    $(document).on('click', '.btn-delete-simpan', function() {
        if (!canDeleteEoq) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menghapus komponen biaya simpan.');
            return;
        }

        const id = $(this).data('id');
        const barangId = $('#select-barang').val();
        
        AlertHelper.confirmDelete('Hapus Komponen?', 'Data yang dihapus tidak dapat dikembalikan').then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/eoq-setting/biaya-simpan/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            AlertHelper.success(response.message);
                            loadBiayaSimpan(barangId);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Gagal menghapus data';
                        AlertHelper.error(message);
                    }
                });
            }
        });
    });

    // ==========================================
    // SEARCH FUNCTIONALITY
    // ==========================================

    let globalData = [];
    $('#searchBiayaGlobal').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const filtered = globalData.filter(item => 
            item.nama_biaya.toLowerCase().includes(searchTerm)
        );
        const total = filtered.reduce((sum, item) => sum + parseFloat(item.nominal), 0);
        renderBiayaPesanGlobal(filtered, total);
    });

    let tokoData = [];
    let tokoTotal = 0;
    $('#searchBiayaToko').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const filtered = tokoData.filter(item => 
            item.nama_biaya.toLowerCase().includes(searchTerm)
        );
        renderBiayaPesanToko(filtered, tokoTotal);
    });

    let simpanData = [];
    let simpanSummary = { total_persentase: 0, harga_pokok: 0, total_biaya: 0 };
    $('#searchBiayaSimpan').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const filtered = simpanData.filter(item => 
            item.nama_komponen.toLowerCase().includes(searchTerm)
        );
        const filteredPersentase = filtered.reduce((sum, item) => sum + parseFloat(item.persentase), 0);
        renderBiayaSimpan(filtered, filteredPersentase, simpanSummary.harga_pokok, simpanSummary.total_biaya);
    });

    // ==========================================
    // HELPER FUNCTIONS
    // ==========================================

    function formatRupiah(number) {
        return 'Rp ' + formatNumber(number);
    }

    function formatNumber(number) {
        return parseFloat(number).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

});
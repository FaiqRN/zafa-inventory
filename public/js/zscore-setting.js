$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const zscorePermissions = window.zscorePermissions || {};
    const canCreateZscore = !!zscorePermissions.create;
    const canEditZscore = !!zscorePermissions.edit;
    const canDeleteZscore = !!zscorePermissions.delete;

    let globalData = [];
    let selectedTokoId = '';
    let selectedBarangId = '';

    function setAddButtonDisabled(isDisabled) {
        if (!canCreateZscore) {
            return;
        }

        $('#btn-add-zscore').prop('disabled', isDisabled);
    }

    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: function() {
                return $(this).data('placeholder');
            }
        });
    }

    setAddButtonDisabled(true);

    $('#select-toko').on('change', function() {
        selectedTokoId = $(this).val() || '';
        selectedBarangId = '';

        if (selectedTokoId) {
            loadBarangByToko(selectedTokoId);
            $('#zscore-section').hide();
            $('#zscore-empty-state').show();
            setAddButtonDisabled(true);
        } else {
            globalData = [];
            resetBarangSelect();
            $('#searchZscore').val('');
            $('#zscore-section').hide();
            $('#zscore-empty-state').show();
            setAddButtonDisabled(true);
        }
    });

    $('#select-barang').on('change', function() {
        selectedBarangId = $(this).val() || '';

        if (selectedTokoId && selectedBarangId) {
            $('#zscore-empty-state').hide();
            $('#zscore-section').show();
            setAddButtonDisabled(false);
            loadZscoreData(selectedTokoId, selectedBarangId);
        } else {
            globalData = [];
            $('#searchZscore').val('');
            $('#zscore-section').hide();
            $('#zscore-empty-state').show();
            setAddButtonDisabled(true);
        }
    });

    function resetBarangSelect() {
        const barangSelect = $('#select-barang');
        barangSelect.empty();
        barangSelect.append('<option value="">-- Pilih Barang --</option>');
        barangSelect.val('').trigger('change.select2');
        barangSelect.prop('disabled', true);
    }

    function loadBarangByToko(tokoId) {
        resetBarangSelect();

        $.ajax({
            url: `/zscore-setting/barang-by-toko/${tokoId}`,
            method: 'GET',
            success: function(response) {
                if (!response.success) {
                    return;
                }

                const barangSelect = $('#select-barang');
                response.data.forEach(item => {
                    barangSelect.append(`<option value="${item.barang_id}">${item.barang_id} - ${item.nama_barang}</option>`);
                });
                barangSelect.prop('disabled', false);
            },
            error: function() {
                AlertHelper.error('Gagal memuat daftar barang untuk toko ini');
            }
        });
    }

    function loadZscoreData(tokoId, barangId) {
        $.ajax({
            url: '/zscore-setting/data',
            method: 'GET',
            data: {
                toko_id: tokoId,
                barang_id: barangId,
            },
            success: function(response) {
                if (response.success) {
                    globalData = response.data;
                    $('#searchZscore').val('');
                    renderZscoreTable(response.data);
                }
            },
            error: function(xhr) {
                AlertHelper.error('Gagal memuat data Z-Score');
            }
        });
    }

    function renderZscoreTable(data) {
        const tbody = $('#tbody-zscore');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox"></i><br>
                        Belum ada data Z-Score
                    </td>
                </tr>
            `);
        } else {
            data.forEach((item, index) => {
                const keterangan = item.keterangan ? (item.keterangan.length > 40 ? item.keterangan.substring(0, 40) + '...' : item.keterangan) : '-';

                let actionButtons = '';
                if (canEditZscore) {
                    actionButtons += `
                        <button class="btn btn-info btn-edit-zscore" data-id="${item.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
                if (canDeleteZscore) {
                    actionButtons += `
                        <button class="btn btn-danger btn-delete-zscore" data-id="${item.id}" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }

                const actionHtml = actionButtons
                    ? `<div class="btn-group btn-group-sm">${actionButtons}</div>`
                    : '<span class="text-muted">-</span>';

                tbody.append(`
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td>${item.label}</td>
                        <td class="text-center">${parseFloat(item.service_level).toFixed(2)}</td>
                        <td class="text-right">${parseFloat(item.z_score).toFixed(4)}</td>
                        <td title="${item.keterangan || '-'}">${keterangan}</td>
                        <td class="text-center">
                            ${actionHtml}
                        </td>
                    </tr>
                `);
            });
        }
    }

    $(document).on('keyup', '#searchZscore', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterTable(globalData, searchTerm);
    });

    function filterTable(data, searchTerm) {
        const filtered = data.filter(item => {
            return item.label.toLowerCase().includes(searchTerm) ||
                   item.z_score.toString().includes(searchTerm) ||
                   item.service_level.toString().includes(searchTerm) ||
                   (item.keterangan && item.keterangan.toLowerCase().includes(searchTerm));
        });
        renderZscoreTable(filtered);
    }

    $('#btn-add-zscore').click(function() {
        if (!canCreateZscore) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menambah Z-Score.');
            return;
        }

        if (!selectedTokoId) {
            AlertHelper.warning('Pilih toko terlebih dahulu');
            return;
        }

        if (!selectedBarangId) {
            AlertHelper.warning('Pilih barang terlebih dahulu');
            return;
        }

        $('#modal-title').text('Tambah Z-Score');
        $('#form-zscore')[0].reset();
        $('#zscore-id').val('');
        $('#modal-zscore').modal('show');
    });

    $(document).on('click', '.btn-edit-zscore', function() {
        if (!canEditZscore) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk mengubah Z-Score.');
            return;
        }

        const id = $(this).data('id');
        
        $.ajax({
            url: `/zscore-setting/${id}/edit`,
            method: 'GET',
            data: {
                toko_id: selectedTokoId,
                barang_id: selectedBarangId,
            },
            success: function(response) {
                if (response.success) {
                    $('#modal-title').text('Edit Z-Score');
                    $('#zscore-id').val(response.data.id);
                    $('#zscore-label').val(response.data.label);
                    $('#zscore-service-level').val(response.data.service_level);
                    $('#zscore-z-score').val(response.data.z_score);
                    $('#zscore-keterangan').val(response.data.keterangan);
                    $('#modal-zscore').modal('show');
                }
            },
            error: function(xhr) {
                AlertHelper.error('Gagal memuat data Z-Score');
            }
        });
    });

    $('#form-zscore').on('submit', function(e) {
        e.preventDefault();

        const id = $('#zscore-id').val();

        if (id && !canEditZscore) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk mengubah Z-Score.');
            return;
        }

        if (!id && !canCreateZscore) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menambah Z-Score.');
            return;
        }

        const url = id ? `/zscore-setting/${id}` : '/zscore-setting';
        const method = id ? 'PUT' : 'POST';

        const data = {
            toko_id: selectedTokoId,
            barang_id: selectedBarangId,
            label: $('#zscore-label').val(),
            service_level: $('#zscore-service-level').val(),
            z_score: $('#zscore-z-score').val(),
            keterangan: $('#zscore-keterangan').val()
        };

        $.ajax({
            url: url,
            method: method,
            data: data,
            success: function(response) {
                if (response.success) {
                    AlertHelper.success(response.message);
                    $('#modal-zscore').modal('hide');
                    loadZscoreData(selectedTokoId, selectedBarangId);
                } else {
                    AlertHelper.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 400) {
                    const response = xhr.responseJSON;
                    AlertHelper.error(response.message || 'Data tidak valid');
                } else {
                    AlertHelper.error('Gagal menyimpan data');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-zscore', function() {
        if (!canDeleteZscore) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menghapus Z-Score.');
            return;
        }

        const id = $(this).data('id');
        
        if (confirm('Anda yakin ingin menghapus Z-Score ini?')) {
            $.ajax({
                url: `/zscore-setting/${id}`,
                method: 'DELETE',
                data: {
                    toko_id: selectedTokoId,
                    barang_id: selectedBarangId,
                },
                success: function(response) {
                    if (response.success) {
                        AlertHelper.success(response.message);
                        loadZscoreData(selectedTokoId, selectedBarangId);
                    } else {
                        AlertHelper.error(response.message);
                    }
                },
                error: function(xhr) {
                    AlertHelper.error('Gagal menghapus data');
                }
            });
        }
    });
});

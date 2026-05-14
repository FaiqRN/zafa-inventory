<form action="{{ url('/pengiriman/ajax') }}" method="POST" id="form-tambah-pengiriman">
    @csrf
    <div id="modal-master" class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pengiriman Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="pengiriman-form-grid mb-3">
                    <div class="form-group mb-0">
                        <label for="nomer_pengiriman">Nomor Pengiriman</label>
                        <input type="text" name="nomer_pengiriman" id="nomer_pengiriman" class="form-control" readonly>
                    </div>

                    <div class="form-group mb-0">
                        <label for="toko_id">Toko <span class="text-danger">*</span></label>
                        <select name="toko_id" id="toko_id" class="form-control" required>
                            <option value="">- Pilih Toko -</option>
                            @foreach($toko as $t)
                                <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label for="tanggal_pengiriman">Tanggal Pengiriman <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pengiriman" id="tanggal_pengiriman" class="form-control" required>
                    </div>
                </div>

                <div class="pengiriman-total-summary mb-3">
                    <div class="pengiriman-total-main">
                        <div class="pengiriman-total-label">Total Nilai</div>
                        <div class="pengiriman-total-value">Rp <span id="total_nilai_kirim">0</span></div>
                    </div>
                    <div class="pengiriman-total-side">
                        <span>Item</span>
                        <strong id="total_item_kirim">0</strong>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                    <h6 class="mb-0">Daftar Barang</h6>
                    <button type="button" class="btn btn-sm btn-success" onclick="addBarangRow()">
                        <i class="fas fa-plus"></i> Tambah Barang
                    </button>
                </div>

                <div id="barang-empty-state" class="pengiriman-empty-state mb-2">
                    Belum ada barang. Pilih toko lalu klik Tambah Barang.
                </div>

                <div id="barang-rows" class="pengiriman-item-list"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</form>

<style>
    #myModal .pengiriman-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.75rem;
    }

    #myModal .pengiriman-form-grid .form-group {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #f8fafc;
        padding: 0.7rem;
    }

    #myModal .pengiriman-form-grid label {
        font-size: 0.82rem;
        margin-bottom: 0.35rem;
        color: #475569;
        font-weight: 700;
    }

    #myModal .select2-container {
        width: 100% !important;
    }

    #myModal .select2-dropdown {
        z-index: 1060;
        max-height: 260px;
        overflow: hidden;
    }

    #myModal .select2-results__options {
        max-height: 240px;
        overflow-y: auto;
    }

    #myModal .pengiriman-total-summary {
        border: 1px solid #fddd7f;
        border-radius: 10px;
        background: #fff7d9;
        padding: 0.65rem 0.85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
    }

    #myModal .pengiriman-total-main {
        display: flex;
        flex-direction: column;
        gap: 0.12rem;
    }

    #myModal .pengiriman-total-label {
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #8a6d1a;
        font-weight: 700;
    }

    #myModal .pengiriman-total-value {
        font-size: 1.2rem;
        line-height: 1.2;
        color: #8a6d1a;
        font-weight: 800;
    }

    #myModal .pengiriman-total-side {
        min-width: 85px;
        border: 1px solid #f7d266;
        border-radius: 8px;
        background: #ffefb7;
        padding: 0.35rem 0.6rem;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 0.08rem;
    }

    #myModal .pengiriman-total-side span {
        font-size: 0.74rem;
        color: #8a6d1a;
    }

    #myModal .pengiriman-total-side strong {
        font-size: 1.05rem;
        color: #7a5d14;
    }

    #myModal .pengiriman-empty-state {
        border: 1px dashed #d1d5db;
        border-radius: 10px;
        background: #f9fafb;
        color: #6b7280;
        padding: 0.75rem;
        font-size: 0.92rem;
    }

    #myModal .pengiriman-item-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 6px 12px -10px rgba(15, 23, 42, 0.5);
    }

    #myModal .pengiriman-item-index {
        font-size: 0.74rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
    }

    #myModal .pengiriman-subtotal-box {
        border: 1px solid #fddd7f;
        border-radius: 8px;
        background: #fff8dd;
        padding: 0.42rem 0.62rem;
        min-height: 64px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.12rem;
    }

    #myModal .pengiriman-subtotal-box span {
        font-size: 0.74rem;
        color: #8a6d1a;
    }

    #myModal .pengiriman-subtotal-box strong {
        color: #7a5d14;
        font-size: 1rem;
        line-height: 1.2;
    }

    @media (max-width: 767.98px) {
        #myModal .modal-body {
            padding: 0.9rem;
        }

        #myModal .pengiriman-total-summary {
            flex-direction: column;
            align-items: flex-start;
        }

        #myModal .pengiriman-total-side {
            min-width: 110px;
        }

        #myModal .pengiriman-item-card .card-body {
            padding: 0.85rem;
        }

        #myModal .select2-dropdown {
            max-height: 220px;
        }

        #myModal .select2-results__options {
            max-height: 200px;
        }
    }
</style>

<script>
if (typeof window.pengirimanBarangList === 'undefined') {
    window.pengirimanBarangList = [];
}
if (typeof window.pengirimanRowIndex === 'undefined') {
    window.pengirimanRowIndex = 0;
}

window.pengirimanBarangList = [];
window.pengirimanRowIndex = 0;

if (!window.pengirimanNumberFormatter) {
    window.pengirimanNumberFormatter = new Intl.NumberFormat('id-ID');
}

function formatPengirimanNumber(value) {
    return window.pengirimanNumberFormatter.format(Number(value || 0));
}

function initPengirimanCreateForm() {
    $.ajax({
        url: "{{ url('pengiriman/get_nomer') }}",
        type: "GET",
        success: function(response) {
            $('#nomer_pengiriman').val(response.nomer_pengiriman);
        }
    });

    $('#tanggal_pengiriman').val(new Date().toISOString().split('T')[0]);
    initPengirimanSelects();
    updatePengirimanSummary();
}

function initPengirimanSelects() {
    if (!$.fn.select2) {
        return;
    }

    const $tokoSelect = $('#toko_id');
    if ($tokoSelect.hasClass('select2-hidden-accessible')) {
        $tokoSelect.select2('destroy');
    }

    $tokoSelect.select2({
        placeholder: '- Pilih Toko -',
        allowClear: true,
        theme: 'bootstrap4',
        width: '100%',
        dropdownParent: $('#myModal')
    });
}

function updatePengirimanSummary() {
    let totalNilai = 0;
    let totalItem = 0;

    $('#barang-rows .pengiriman-item-card').each(function() {
        const row = $(this);
        const jumlah = parseInt(row.find('.jumlah-input').val(), 10) || 0;
        const harga = parseFloat(row.attr('data-harga')) || 0;
        const subtotal = jumlah * harga;

        row.find('.subtotal-value').text(`Rp ${formatPengirimanNumber(subtotal)}`);
        totalNilai += subtotal;
        totalItem += jumlah;
    });

    $('#total_nilai_kirim').text(formatPengirimanNumber(totalNilai));
    $('#total_item_kirim').text(formatPengirimanNumber(totalItem));
    toggleBarangEmptyState();
}

function toggleBarangEmptyState() {
    const hasRows = $('#barang-rows .pengiriman-item-card').length > 0;
    $('#barang-empty-state').toggleClass('d-none', hasRows);
}

$(document).ready(function() {
    initPengirimanCreateForm();

    $(document)
        .off('change.pengiriman', '#toko_id')
        .on('change.pengiriman', '#toko_id', function() {
        const tokoId = $(this).val();

        window.pengirimanBarangList = [];
        $('#barang-rows').empty();
        updatePengirimanSummary();

        if (tokoId) {
            loadBarangByToko(tokoId);
        }
    });

    $(document)
        .off('submit.pengiriman', '#form-tambah-pengiriman')
        .on('submit.pengiriman', '#form-tambah-pengiriman', function(e) {
        e.preventDefault();

        const cards = $('#barang-rows .pengiriman-item-card');
        if (cards.length === 0) {
            AlertHelper.warning('Perhatian', 'Minimal harus ada 1 barang', false);
            return false;
        }

        const items = [];
        let hasError = false;

        cards.each(function() {
            const row = $(this);
            const barangId = row.find('.barang-select').val();
            const jumlah = parseInt(row.find('.jumlah-input').val(), 10) || 0;

            if (!barangId || jumlah <= 0) {
                hasError = true;
                return false;
            }

            items.push({
                barang_id: barangId,
                jumlah: jumlah
            });
        });

        if (hasError) {
            AlertHelper.warning('Perhatian', 'Pastikan semua barang dan jumlah sudah terisi dengan benar', false);
            return false;
        }

        if (items.length === 0) {
            AlertHelper.warning('Perhatian', 'Minimal harus ada 1 barang yang valid', false);
            return false;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('nomer_pengiriman', $('#nomer_pengiriman').val());
        formData.append('tanggal_pengiriman', $('#tanggal_pengiriman').val());
        formData.append('toko_id', $('#toko_id').val());

        items.forEach(function(item, index) {
            formData.append(`items[${index}][barang_id]`, item.barang_id);
            formData.append(`items[${index}][jumlah]`, item.jumlah);
        });

        AlertHelper.loading('Menyimpan...', 'Mohon tunggu sebentar');

        $.ajax({
            url: $('#form-tambah-pengiriman').attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    $('#myModal').modal('hide');
                    AlertHelper.success('Berhasil!', response.message);
                    dataTable.ajax.reload();
                } else {
                    AlertHelper.error('Gagal', response.message);
                }
            },
            error: function(xhr) {
                let errorMsg = 'Terjadi kesalahan pada server';
                if (xhr.responseJSON?.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMsg = errors.join('\n');
                } else if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                AlertHelper.error('Gagal Menyimpan!', errorMsg.replace(/\n/g, '<br>'));
            }
        });
    });
});

function loadBarangByToko(tokoId) {
    $.ajax({
        url: "{{ url('pengiriman/get_barang') }}",
        type: "GET",
        data: { toko_id: tokoId },
        success: function(response) {
            if (response.status === 'success') {
                window.pengirimanBarangList = Array.isArray(response.data) ? response.data : [];
            }
        },
        error: function() {
            AlertHelper.error('Error', 'Gagal memuat data barang');
        }
    });
}

function addBarangRow() {
    if (window.pengirimanBarangList.length === 0) {
        AlertHelper.warning('Perhatian', 'Pilih toko terlebih dahulu', false);
        return;
    }

    window.pengirimanRowIndex++;

    let options = '<option value="">- Pilih Barang -</option>';
    window.pengirimanBarangList.forEach(function(barang) {
        const parsedHargaBarangToko = Number(barang.harga_barang_toko);
        const hargaBarangToko = Number.isFinite(parsedHargaBarangToko) ? parsedHargaBarangToko : 0;

        options += `<option value="${barang.barang_id}"
                            data-satuan="${barang.satuan || ''}"
                            data-harga="${hargaBarangToko}"
                            data-stok="${barang.stok}">
                        ${barang.nama_barang} (Stok: ${barang.stok})
                    </option>`;
    });

    const row = `
        <div id="row-${window.pengirimanRowIndex}" class="card pengiriman-item-card mb-3" data-harga="0">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                    <div class="pengiriman-item-index">Barang ${window.pengirimanRowIndex}</div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeBarangRow(${window.pengirimanRowIndex})">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="small text-muted mb-1" for="barang_${window.pengirimanRowIndex}">Barang</label>
                        <select id="barang_${window.pengirimanRowIndex}" class="form-control form-control-sm barang-select" onchange="updateBarangInfo(this)" required>
                            ${options}
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small text-muted mb-1" for="jumlah_${window.pengirimanRowIndex}">Jumlah</label>
                        <input id="jumlah_${window.pengirimanRowIndex}" type="number" class="form-control form-control-sm jumlah-input" min="1" oninput="updateJumlahInfo(this)" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small text-muted mb-1" for="satuan_${window.pengirimanRowIndex}">Satuan</label>
                        <input id="satuan_${window.pengirimanRowIndex}" type="text" class="form-control form-control-sm satuan-input" readonly>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="small text-muted mb-1" for="harga_${window.pengirimanRowIndex}">Harga</label>
                        <input id="harga_${window.pengirimanRowIndex}" type="text" class="form-control form-control-sm harga-input" readonly>
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="pengiriman-subtotal-box mt-md-4">
                            <span>Subtotal</span>
                            <strong class="subtotal-value">Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#barang-rows').append(row);
    updatePengirimanSummary();
}

function updateBarangInfo(select) {
    const selectedOption = $(select).find('option:selected');
    const row = $(select).closest('.pengiriman-item-card');
    const selectedBarangId = $(select).val();

    if (selectedBarangId) {
        const isDuplicate = checkDuplicateBarang(selectedBarangId, row.attr('id'));
        if (isDuplicate) {
            AlertHelper.warning('Duplikasi Barang', 'Barang ini sudah dipilih. Silakan pilih barang lain.', false);
            $(select).val('');
            row.find('.satuan-input').val('');
            row.find('.harga-input').val('');
            row.attr('data-harga', '0');
            updatePengirimanSummary();
            return;
        }
    }

    const hargaBarangToko = Number(selectedOption.data('harga') ?? 0);

    row.attr('data-harga', hargaBarangToko);
    row.find('.satuan-input').val(selectedOption.data('satuan') || '');
    row.find('.harga-input').val(hargaBarangToko > 0 ? `Rp ${formatPengirimanNumber(hargaBarangToko)}` : '');

    updatePengirimanSummary();
}

function updateJumlahInfo(input) {
    const value = parseInt($(input).val(), 10) || 0;
    if (value < 0) {
        $(input).val(0);
    }
    updatePengirimanSummary();
}

function checkDuplicateBarang(barangId, currentRowId) {
    let isDuplicate = false;

    $('#barang-rows .pengiriman-item-card').each(function() {
        if ($(this).attr('id') !== currentRowId) {
            const existingBarangId = $(this).find('.barang-select').val();
            if (existingBarangId === barangId) {
                isDuplicate = true;
                return false;
            }
        }
    });

    return isDuplicate;
}

function removeBarangRow(index) {
    AlertHelper.confirmDelete('Hapus Barang?', 'Barang ini akan dihapus dari daftar').then((result) => {
        if (result.isConfirmed) {
            $(`#row-${index}`).remove();
            updatePengirimanSummary();
            AlertHelper.success('Terhapus!', 'Barang berhasil dihapus');
        }
    });
}
</script>

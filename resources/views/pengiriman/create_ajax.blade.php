<form action="{{ url('/pengiriman/ajax') }}" method="POST" id="form-tambah-pengiriman">
    @csrf
    <div id="modal-master" class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pengiriman Barang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nomor Pengiriman</label>
                    <input type="text" name="nomer_pengiriman" id="nomer_pengiriman" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label>Tanggal Pengiriman <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_pengiriman" id="tanggal_pengiriman" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Toko <span class="text-danger">*</span></label>
                    <select name="toko_id" id="toko_id" class="form-control" required>
                        <option value="">- Pilih Toko -</option>
                        @foreach($toko as $t)
                            <option value="{{ $t->toko_id }}">{{ $t->nama_toko }}</option>
                        @endforeach
                    </select>
                </div>

                <hr>
                <h6>Daftar Barang</h6>
                <button type="button" class="btn btn-sm btn-success mb-2" onclick="addBarangRow()">
                    <i class="fas fa-plus"></i> Tambah Barang
                </button>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th width="40%">Barang</th>
                                <th width="15%">Jumlah</th>
                                <th width="15%">Satuan</th>
                                <th width="20%">Harga</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="barang-rows">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</form>

<script>
let barangList = [];
let rowIndex = 0;

$(document).ready(function() {
    $.ajax({
        url: "{{ url('pengiriman/get_nomer') }}",
        type: "GET",
        success: function(response) {
            $('#nomer_pengiriman').val(response.nomer_pengiriman);
        }
    });

    $('#tanggal_pengiriman').val(new Date().toISOString().split('T')[0]);

    $('#toko_id').change(function() {
        const tokoId = $(this).val();
        if (tokoId) {
            loadBarangByToko(tokoId);
        } else {
            barangList = [];
            $('#barang-rows').empty();
        }
    });

    $('#form-tambah-pengiriman').submit(function(e) {
        e.preventDefault();
        
        if ($('#barang-rows tr').length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Minimal harus ada 1 barang'
            });
            return false;
        }

        const items = [];
        $('#barang-rows tr').each(function() {
            const row = $(this);
            items.push({
                barang_id: row.find('.barang-select').val(),
                jumlah: row.find('.jumlah-input').val()
            });
        });

        const formData = {
            _token: '{{ csrf_token() }}',
            nomer_pengiriman: $('#nomer_pengiriman').val(),
            tanggal_pengiriman: $('#tanggal_pengiriman').val(),
            toko_id: $('#toko_id').val(),
            items: items
        };

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    $('#myModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500
                    });
                    dataTable.ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'Terjadi kesalahan';
                if (xhr.responseJSON?.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                } else if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
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
                barangList = response.data;
                $('#barang-rows').empty();
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Gagal memuat data barang'
            });
        }
    });
}

function addBarangRow() {
    if (barangList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Pilih toko terlebih dahulu'
        });
        return;
    }

    rowIndex++;
    let options = '<option value="">- Pilih Barang -</option>';
    barangList.forEach(function(barang) {
        options += `<option value="${barang.barang_id}" 
                            data-satuan="${barang.satuan}" 
                            data-harga="${barang.harga}"
                            data-stok="${barang.stok}">
                        ${barang.nama_barang} (Stok: ${barang.stok})
                    </option>`;
    });

    const row = `
        <tr id="row-${rowIndex}">
            <td>
                <select class="form-control form-control-sm barang-select" onchange="updateBarangInfo(this)" required>
                    ${options}
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm jumlah-input" min="1" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm satuan-input" readonly>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm harga-input" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeBarangRow(${rowIndex})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#barang-rows').append(row);
}

function updateBarangInfo(select) {
    const selectedOption = $(select).find('option:selected');
    const row = $(select).closest('tr');
    
    row.find('.satuan-input').val(selectedOption.data('satuan'));
    row.find('.harga-input').val(new Intl.NumberFormat('id-ID').format(selectedOption.data('harga')));
}

function removeBarangRow(index) {
    $(`#row-${index}`).remove();
}
</script>

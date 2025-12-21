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
// Cek apakah variabel sudah ada, jika belum baru deklarasikan
if (typeof window.pengirimanBarangList === 'undefined') {
    window.pengirimanBarangList = [];
}
if (typeof window.pengirimanRowIndex === 'undefined') {
    window.pengirimanRowIndex = 0;
}

// Reset untuk setiap modal baru
window.pengirimanBarangList = [];
window.pengirimanRowIndex = 0;

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
            window.pengirimanBarangList = [];
            $('#barang-rows').empty();
        }
    });

    $('#form-tambah-pengiriman').submit(function(e) {
        e.preventDefault();
        
        // Validasi minimal 1 barang
        if ($('#barang-rows tr').length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Minimal harus ada 1 barang'
            });
            return false;
        }

        // Kumpulkan data items
        const items = [];
        let hasError = false;
        
        $('#barang-rows tr').each(function() {
            const row = $(this);
            const barangId = row.find('.barang-select').val();
            const jumlah = row.find('.jumlah-input').val();
            
            // Validasi setiap item harus terisi
            if (!barangId || !jumlah || jumlah <= 0) {
                hasError = true;
                return false; // break loop
            }
            
            items.push({
                barang_id: barangId,
                jumlah: parseInt(jumlah)
            });
        });

        // Validasi jika ada item yang tidak lengkap
        if (hasError) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Pastikan semua barang dan jumlah sudah terisi dengan benar'
            });
            return false;
        }

        // Validasi items tidak kosong
        if (items.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Minimal harus ada 1 barang yang valid'
            });
            return false;
        }

        // Buat FormData dengan array notation yang benar
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('nomer_pengiriman', $('#nomer_pengiriman').val());
        formData.append('tanggal_pengiriman', $('#tanggal_pengiriman').val());
        formData.append('toko_id', $('#toko_id').val());
        
        // Tambahkan items dengan array notation
        items.forEach(function(item, index) {
            formData.append(`items[${index}][barang_id]`, item.barang_id);
            formData.append(`items[${index}][jumlah]`, item.jumlah);
        });

        // Debug: log data yang akan dikirim
        console.log('Sending items:', items);

        // Tampilkan loading
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    $('#myModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
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
                console.error('Error response:', xhr.responseJSON);
                let errorMsg = 'Terjadi kesalahan pada server';
                if (xhr.responseJSON?.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMsg = errors.join('\n');
                } else if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan!',
                    html: errorMsg.replace(/\n/g, '<br>'),
                    confirmButtonText: 'OK'
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
                window.pengirimanBarangList = response.data;
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
    if (window.pengirimanBarangList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Pilih toko terlebih dahulu'
        });
        return;
    }

    window.pengirimanRowIndex++;
    let options = '<option value="">- Pilih Barang -</option>';
    window.pengirimanBarangList.forEach(function(barang) {
        options += `<option value="${barang.barang_id}" 
                            data-satuan="${barang.satuan}" 
                            data-harga="${barang.harga}"
                            data-stok="${barang.stok}">
                        ${barang.nama_barang} (Stok: ${barang.stok})
                    </option>`;
    });

    const row = `
        <tr id="row-${window.pengirimanRowIndex}">
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
                <button type="button" class="btn btn-danger btn-sm" onclick="removeBarangRow(${window.pengirimanRowIndex})">
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
    const selectedBarangId = $(select).val();
    
    if (selectedBarangId) {
        const isDuplicate = checkDuplicateBarang(selectedBarangId, row.attr('id'));
        if (isDuplicate) {
            Swal.fire({
                icon: 'warning',
                title: 'Duplikasi Barang',
                text: 'Barang ini sudah dipilih. Silakan pilih barang lain.'
            });
            $(select).val('');
            row.find('.satuan-input').val('');
            row.find('.harga-input').val('');
            return;
        }
    }
    
    row.find('.satuan-input').val(selectedOption.data('satuan'));
    row.find('.harga-input').val(new Intl.NumberFormat('id-ID').format(selectedOption.data('harga')));
}

function checkDuplicateBarang(barangId, currentRowId) {
    let isDuplicate = false;
    $('#barang-rows tr').each(function() {
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
    Swal.fire({
        title: 'Hapus Barang?',
        text: 'Barang ini akan dihapus dari daftar',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $(`#row-${index}`).remove();
            Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: 'Barang berhasil dihapus',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}
</script>
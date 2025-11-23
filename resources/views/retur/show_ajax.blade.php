<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Detail Retur - {{ $nomerPengiriman }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div id="alert-container-modal"></div>
            
            <h6 class="mb-3">Informasi Pengiriman</h6>
            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>No. Pengiriman:</strong> {{ $nomerPengiriman }}
                </div>
                <div class="col-md-4">
                    <strong>Tanggal Pengiriman:</strong> {{ \Carbon\Carbon::parse($pengiriman->first()->tanggal_pengiriman)->format('d/m/Y') }}
                </div>
                <div class="col-md-4">
                    <strong>Toko:</strong> {{ $pengiriman->first()->toko->nama_toko }}
                </div>
            </div>

            <hr>

            <h6 class="mb-3">Daftar Barang & Data Retur</h6>
            @if($isLocked)
                <div class="alert alert-info">
                    <i class="fas fa-lock"></i> Data retur sudah disimpan dan tidak dapat diubah lagi.
                </div>
            @endif
            
            <form id="formRetur">
                @csrf
                <input type="hidden" name="nomer_pengiriman" value="{{ $nomerPengiriman }}">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th width="3%">No</th>
                                <th width="15%">Nama Barang</th>
                                <th width="8%">Tanggal Pengiriman</th>
                                <th width="8%">Tanggal Retur</th>
                                <th width="10%">Harga Awal Barang</th>
                                <th width="7%">Jumlah Kirim</th>
                                <th width="7%">Jumlah Retur</th>
                                <th width="7%">Total Terjual</th>
                                <th width="10%">Hasil</th>
                                <th width="10%">Kondisi</th>
                                <th width="15%">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pengiriman as $index => $item)
                            @php
                                $retur = $returData->where('pengiriman_id', $item->pengiriman_id)->first();
                                $tanggalRetur = $retur ? $retur->tanggal_retur : date('Y-m-d');
                                $jumlahRetur = $retur ? $retur->jumlah_retur : 0;
                                $kondisi = $retur ? $retur->kondisi : 'Tidak Ada Retur';
                                $keterangan = $retur ? $retur->keterangan : '';
                                $totalTerjual = $item->jumlah_kirim - $jumlahRetur;
                                $hargaAwalBarang = $item->barang->harga_awal_barang ?? 0;
                                $hasil = $totalTerjual * $hargaAwalBarang;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $item->barang->nama_barang }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal_pengiriman)->format('d/m/Y') }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][pengiriman_id]" value="{{ $item->pengiriman_id }}">
                                    @if($isLocked)
                                        <span>{{ \Carbon\Carbon::parse($tanggalRetur)->format('d/m/Y') }}</span>
                                    @else
                                        <input type="date" 
                                               class="form-control form-control-sm tanggal-retur" 
                                               name="items[{{ $index }}][tanggal_retur]" 
                                               value="{{ $tanggalRetur }}" 
                                               required>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($hargaAwalBarang, 2, ',', '.') }}</td>
                                <td class="text-center">{{ $item->jumlah_kirim }}</td>
                                <td>
                                    @if($isLocked)
                                        <span class="d-block text-center">{{ $jumlahRetur }}</span>
                                    @else
                                        <input type="number" 
                                               class="form-control form-control-sm jumlah-retur" 
                                               name="items[{{ $index }}][jumlah_retur]" 
                                               value="{{ $jumlahRetur }}" 
                                               min="0" 
                                               max="{{ $item->jumlah_kirim }}"
                                               data-max="{{ $item->jumlah_kirim }}"
                                               data-index="{{ $index }}"
                                               data-harga="{{ $hargaAwalBarang }}"
                                               required>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="total-terjual" data-index="{{ $index }}">{{ $totalTerjual }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="hasil" data-index="{{ $index }}">{{ number_format($hasil, 2, ',', '.') }}</span>
                                </td>
                                <td>
                                    @if($isLocked)
                                        <span>{{ $kondisi }}</span>
                                    @else
                                        <select class="form-control form-control-sm" 
                                                name="items[{{ $index }}][kondisi]" 
                                                required>
                                            <option value="Tidak Ada Retur" {{ $kondisi == 'Tidak Ada Retur' ? 'selected' : '' }}>Tidak Ada Retur</option>
                                            <option value="Rusak" {{ $kondisi == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                                            <option value="Kadaluarsa" {{ $kondisi == 'Kadaluarsa' ? 'selected' : '' }}>Kadaluarsa</option>
                                            <option value="Cacat Produksi" {{ $kondisi == 'Cacat Produksi' ? 'selected' : '' }}>Cacat Produksi</option>
                                            <option value="Kemasan Rusak" {{ $kondisi == 'Kemasan Rusak' ? 'selected' : '' }}>Kemasan Rusak</option>
                                            <option value="Tidak Laku" {{ $kondisi == 'Tidak Laku' ? 'selected' : '' }}>Tidak Laku</option>
                                            <option value="Lainnya" {{ $kondisi == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                        </select>
                                    @endif
                                </td>
                                <td>
                                    @if($isLocked)
                                        <span>{{ $keterangan }}</span>
                                    @else
                                        <textarea class="form-control form-control-sm" 
                                                  name="items[{{ $index }}][keterangan]" 
                                                  rows="1">{{ $keterangan }}</textarea>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            @if(!$isLocked)
                <button type="button" class="btn btn-primary" id="btnSimpanRetur">
                    <i class="fas fa-save"></i> Simpan Data Retur
                </button>
            @endif
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Hitung ulang total terjual dan hasil saat jumlah retur berubah
    $('.jumlah-retur').on('input', function() {
        const index = $(this).data('index');
        const max = parseInt($(this).data('max'));
        const harga = parseFloat($(this).data('harga')) || 0;
        let jumlahRetur = parseInt($(this).val()) || 0;
        
        // Validasi tidak melebihi jumlah kirim
        if (jumlahRetur > max) {
            jumlahRetur = max;
            $(this).val(max);
        }
        
        if (jumlahRetur < 0) {
            jumlahRetur = 0;
            $(this).val(0);
        }
        
        const totalTerjual = max - jumlahRetur;
        const hasil = totalTerjual * harga;
        
        $(`.total-terjual[data-index="${index}"]`).text(totalTerjual);
        $(`.hasil[data-index="${index}"]`).text(hasil.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    });
    
    // Submit form
    $('#btnSimpanRetur').click(function() {
        const formData = $('#formRetur').serialize();
        
        $.ajax({
            url: "{{ url('retur/store') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        $('#myModal').modal('hide');
                        if (typeof dataTable !== 'undefined') {
                            dataTable.ajax.reload();
                        }
                    });
                } else {
                    showAlertModal('danger', response.message);
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan saat menyimpan data';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlertModal('danger', message);
            }
        });
    });
    
    function showAlertModal(type, message) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('#alert-container-modal').html(alert);
        
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
</script>

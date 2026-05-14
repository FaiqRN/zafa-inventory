@php
    $defaultTanggalRetur = $returData->isNotEmpty()
        ? \Carbon\Carbon::parse($returData->first()->tanggal_retur)->format('Y-m-d')
        : date('Y-m-d');
@endphp

<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Detail Retur - {{ $nomerPengiriman }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <h6 class="mb-3">Informasi Pengiriman</h6>
            <div class="retur-info-grid mb-3">
                <div class="retur-info-item">
                    <span class="retur-info-label">No. Pengiriman</span>
                    <strong>{{ $nomerPengiriman }}</strong>
                </div>
                <div class="retur-info-item">
                    <span class="retur-info-label">Tanggal Pengiriman</span>
                    <strong>{{ \Carbon\Carbon::parse($pengiriman->first()->tanggal_pengiriman)->format('d/m/Y') }}</strong>
                </div>
                <div class="retur-info-item">
                    <span class="retur-info-label">Toko</span>
                    <strong>{{ $pengiriman->first()->toko->nama_toko }}</strong>
                </div>
            </div>

            <h6 class="mb-3">Daftar Barang & Data Retur</h6>
            @if($isLocked)
                <div class="alert alert-info">
                    <i class="fas fa-lock"></i> Data retur sudah disimpan dan tidak dapat diubah lagi.
                </div>
            @endif

            @if(!$isLocked && !$canCreateRetur)
                <div class="alert alert-secondary">
                    <i class="fas fa-eye"></i> Anda hanya memiliki akses lihat data retur.
                </div>
            @endif

            <form id="formRetur">
                @csrf
                <input type="hidden" name="nomer_pengiriman" value="{{ $nomerPengiriman }}">

                <div class="retur-total-summary mb-2">
                    <div class="retur-total-label">Total</div>
                    <div class="retur-total-value">Rp <span id="total_hasil_semua">0,00</span></div>
                </div>

                <div class="retur-global-date mb-3">
                    <label for="tanggal_retur_global" class="font-weight-bold mb-1">Tanggal Retur (berlaku untuk semua barang)</label>
                    @if($isLocked || !$canCreateRetur)
                        <div class="retur-readonly-value">{{ \Carbon\Carbon::parse($defaultTanggalRetur)->format('d/m/Y') }}</div>
                    @else
                        <input
                            type="date"
                            class="form-control"
                            name="tanggal_retur"
                            id="tanggal_retur_global"
                            value="{{ $defaultTanggalRetur }}"
                            required>
                        <small class="form-text text-muted">Isi sekali saja, otomatis berlaku untuk seluruh barang di bawah.</small>
                    @endif
                </div>

                <div class="retur-item-list">
                    @foreach($pengiriman as $index => $item)
                        @php
                            $retur = $returData->where('pengiriman_id', $item->pengiriman_id)->first();
                            $tanggalRetur = $retur ? $retur->tanggal_retur : $defaultTanggalRetur;
                            $jumlahRetur = $retur ? $retur->jumlah_retur : 0;
                            $kondisi = $retur ? $retur->kondisi : 'Tidak Ada Retur';
                            $keterangan = $retur ? $retur->keterangan : '';
                            $totalTerjual = $item->jumlah_kirim - $jumlahRetur;
                            $hargaBarangToko = (float) ($hargaBarangTokoMap->get($item->barang_id) ?? 0);
                            $hargaRetur = $retur ? (float) ($retur->harga_awal_barang ?? 0) : $hargaBarangToko;
                            $hasil = $totalTerjual * $hargaRetur;
                        @endphp

                        <div class="card retur-item-card mb-3">
                            <div class="card-body">
                                <input type="hidden" name="items[{{ $index }}][pengiriman_id]" value="{{ $item->pengiriman_id }}">

                                <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                    <div class="mr-2 mb-2">
                                        <div class="retur-item-index">Barang {{ $index + 1 }}</div>
                                        <h6 class="mb-1">{{ $item->barang->nama_barang }}</h6>
                                        <div class="text-muted small">Tanggal kirim: {{ \Carbon\Carbon::parse($item->tanggal_pengiriman)->format('d/m/Y') }}</div>
                                    </div>
                                    <div class="retur-price-box mb-2">
                                        <span>Harga di Toko</span>
                                        <strong>Rp {{ number_format($hargaRetur, 2, ',', '.') }}</strong>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6 col-md-3 mb-2">
                                        <div class="retur-mini-stat">
                                            <span>Jumlah Kirim</span>
                                            <strong>{{ $item->jumlah_kirim }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <div class="retur-mini-stat">
                                            <span>Total Terjual</span>
                                            <strong class="total-terjual" data-index="{{ $index }}">{{ $totalTerjual }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3 mb-2">
                                        <div class="retur-mini-stat">
                                            <span>Hasil</span>
                                            <strong>Rp <span class="hasil" data-index="{{ $index }}" data-value="{{ number_format($hasil, 2, '.', '') }}">{{ number_format($hasil, 2, ',', '.') }}</span></strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3 mb-2">
                                        <div class="retur-mini-stat">
                                            <span>Tanggal Retur</span>
                                            <strong>{{ \Carbon\Carbon::parse($tanggalRetur)->format('d/m/Y') }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-1">
                                    <div class="col-md-3 mb-2">
                                        @if($isLocked || !$canCreateRetur)
                                            <div class="small text-muted mb-1">Jumlah Retur</div>
                                            <div class="retur-readonly-value text-center">{{ $jumlahRetur }}</div>
                                        @else
                                            <label class="small text-muted mb-1" for="jumlah_retur_{{ $index }}">Jumlah Retur</label>
                                            <input type="number"
                                                   id="jumlah_retur_{{ $index }}"
                                                   class="form-control form-control-sm jumlah-retur"
                                                   name="items[{{ $index }}][jumlah_retur]"
                                                   value="{{ $jumlahRetur }}"
                                                   min="0"
                                                   max="{{ $item->jumlah_kirim }}"
                                                   data-max="{{ $item->jumlah_kirim }}"
                                                   data-index="{{ $index }}"
                                                   data-harga="{{ $hargaRetur }}"
                                                   required>
                                        @endif
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        @if($isLocked || !$canCreateRetur)
                                            <div class="small text-muted mb-1">Kondisi</div>
                                            <div class="retur-readonly-value">{{ $kondisi }}</div>
                                        @else
                                            <label class="small text-muted mb-1" for="kondisi_{{ $index }}">Kondisi</label>
                                            <select class="form-control form-control-sm"
                                                    id="kondisi_{{ $index }}"
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
                                    </div>

                                    <div class="col-md-5 mb-2">
                                        @if($isLocked || !$canCreateRetur)
                                            <div class="small text-muted mb-1">Keterangan</div>
                                            <div class="retur-readonly-value">{{ $keterangan ?: '-' }}</div>
                                        @else
                                            <label class="small text-muted mb-1" for="keterangan_{{ $index }}">Keterangan</label>
                                            <textarea class="form-control form-control-sm"
                                                      id="keterangan_{{ $index }}"
                                                      name="items[{{ $index }}][keterangan]"
                                                      rows="2"
                                                      placeholder="Opsional">{{ $keterangan }}</textarea>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            @if(!$isLocked && $canCreateRetur)
                <button type="button" class="btn btn-primary" id="btnSimpanRetur">
                    <i class="fas fa-save"></i> Simpan Data Retur
                </button>
            @endif
        </div>
    </div>
</div>

<style>
    #myModal .retur-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
    }

    #myModal .retur-info-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 0.75rem 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    #myModal .retur-info-label {
        font-size: 0.78rem;
        color: #000000;
    }

    #myModal .retur-global-date {
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #eff6ff;
        padding: 0.85rem;
    }

    #myModal .retur-total-summary {
        border: 1px solid #fddd7f;
        border-radius: 10px;
        background: #fff7d9;
        padding: 0.65rem 0.85rem;
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.6rem;
    }

    #myModal .retur-total-label {
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #8a6d1a;
    }

    #myModal .retur-total-value {
        font-size: 1.15rem;
        font-weight: 800;
        color: #8a6d1a;
        line-height: 1.2;
    }

    #myModal .retur-item-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 6px 12px -10px rgba(15, 23, 42, 0.5);
    }

    #myModal .retur-item-index {
        font-size: 0.74rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #000000;
        font-weight: 700;
    }

    #myModal .retur-price-box {
        border-radius: 10px;
        border: 1px solid #fddd7f;
        background: #fff8dd;
        padding: 0.45rem 0.7rem;
        min-width: 100px;
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }

    #myModal .retur-price-box span {
        font-size: 0.76rem;
        color: #8a6d1a;
    }

    #myModal .retur-price-box strong {
        color: #7a5d14;
    }

    #myModal .retur-mini-stat {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
        padding: 0.45rem 0.65rem;
        min-height: 58px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.15rem;
    }

    #myModal .retur-mini-stat span {
        font-size: 0.74rem;
        color: #000000;
    }

    #myModal .retur-mini-stat .hasil {
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.1;
    }

    #myModal .retur-readonly-value {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #f9fafb;
        min-height: 31px;
        padding: 0.34rem 0.55rem;
        color: #374151;
    }

    @media (max-width: 767.98px) {
        #myModal .modal-body {
            padding: 0.9rem;
        }

        #myModal .retur-item-card .card-body {
            padding: 0.85rem;
        }

        #myModal .retur-price-box {
            width: 100%;
            text-align: left;
        }
    }
</style>

<script>
$(document).ready(function() {
    const canCreateRetur = @json($canCreateRetur);

    const formatNumber = function(value) {
        return Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    const recalculateGrandTotal = function() {
        let grandTotal = 0;

        $('.hasil').each(function() {
            grandTotal += parseFloat($(this).attr('data-value')) || 0;
        });

        $('#total_hasil_semua').text(formatNumber(grandTotal));
    };

    $('.jumlah-retur').on('input', function() {
        const index = $(this).data('index');
        const max = parseInt($(this).data('max'), 10);
        const harga = parseFloat($(this).data('harga')) || 0;
        let jumlahRetur = parseInt($(this).val(), 10) || 0;

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
        const hasilElement = $(`.hasil[data-index="${index}"]`);
        hasilElement.text(formatNumber(hasil));
        hasilElement.attr('data-value', hasil.toFixed(2));
        recalculateGrandTotal();
    });

    recalculateGrandTotal();

    $('#btnSimpanRetur').on('click', function() {
        if (!canCreateRetur) {
            AlertHelper.error('Akses ditolak', 'Anda tidak memiliki izin untuk menyimpan retur.', false);
            return;
        }

        const tanggalReturGlobal = $('#tanggal_retur_global').val();
        if (!tanggalReturGlobal) {
            AlertHelper.error('Validasi gagal', 'Tanggal retur wajib diisi.', false);
            return;
        }

        let validationError = false;
        let errorMessage = '';

        $('.jumlah-retur').each(function() {
            const index = $(this).data('index');
            const jumlahRetur = parseInt($(this).val(), 10) || 0;
            const kondisi = $(`select[name="items[${index}][kondisi]"]`).val();

            if (jumlahRetur > 0 && kondisi === 'Tidak Ada Retur') {
                validationError = true;
                errorMessage = 'Jika jumlah retur lebih dari 0, kondisi tidak boleh "Tidak Ada Retur". Silakan pilih kondisi yang sesuai.';
                return false;
            }
        });

        if (validationError) {
            AlertHelper.error('Validasi gagal', errorMessage, false);
            return;
        }

        const formData = $('#formRetur').serialize();

        $.ajax({
            url: "{{ url('retur/store') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    $('#myModal').modal('hide');
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
                    }
                    AlertHelper.success('Berhasil', response.message);
                } else {
                    AlertHelper.error('Gagal', response.message || 'Gagal menyimpan data retur', false);
                }
            },
            error: function(xhr) {
                AlertHelper.ajaxError('Error!', xhr, 'Terjadi kesalahan saat menyimpan data', false);
            }
        });
    });
});
</script>

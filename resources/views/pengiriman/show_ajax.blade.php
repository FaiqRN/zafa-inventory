@php
    $status = $pengiriman['status'] ?? 'proses';
    $statusMap = [
        'proses' => ['label' => 'Proses', 'class' => 'badge-warning'],
        'terkirim' => ['label' => 'Terkirim', 'class' => 'badge-success'],
        'batal' => ['label' => 'Batal', 'class' => 'badge-danger'],
    ];
    $statusMeta = $statusMap[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-secondary'];

    $items = collect($pengiriman['items'] ?? []);
    $totalJumlah = $items->sum('jumlah');
    $totalNilai = $items->reduce(function ($carry, $item) {
        return $carry + (((int) ($item['jumlah'] ?? 0)) * ((float) ($item['harga'] ?? 0)));
    }, 0);
@endphp

<div id="modal-master" class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Detail Pengiriman - {{ $pengiriman['nomer_pengiriman'] }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="modal-body">
            <h6 class="mb-3">Informasi Pengiriman</h6>
            <div class="pengiriman-info-grid mb-3">
                <div class="pengiriman-info-item">
                    <span class="pengiriman-info-label">No. Pengiriman</span>
                    <strong>{{ $pengiriman['nomer_pengiriman'] }}</strong>
                </div>
                <div class="pengiriman-info-item">
                    <span class="pengiriman-info-label">Tanggal</span>
                    <strong>{{ \Carbon\Carbon::parse($pengiriman['tanggal_pengiriman'])->format('d/m/Y') }}</strong>
                </div>
                <div class="pengiriman-info-item">
                    <span class="pengiriman-info-label">Toko</span>
                    <strong>{{ $pengiriman['toko']->nama_toko }}</strong>
                </div>
                <div class="pengiriman-info-item">
                    <span class="pengiriman-info-label">Status</span>
                    <strong><span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></strong>
                </div>
            </div>

            <div class="pengiriman-total-summary mb-3">
                <div class="pengiriman-total-group">
                    <span class="pengiriman-total-label">Total Jumlah</span>
                    <strong>{{ number_format($totalJumlah, 0, ',', '.') }}</strong>
                </div>
                <div class="pengiriman-total-group text-right">
                    <span class="pengiriman-total-label">Total Nilai</span>
                    <strong>Rp {{ number_format($totalNilai, 0, ',', '.') }}</strong>
                </div>
            </div>

            <h6 class="mb-2">Daftar Barang</h6>
            <div class="pengiriman-item-list">
                @forelse($items as $index => $item)
                    @php
                        $jumlah = (int) ($item['jumlah'] ?? 0);
                        $harga = (float) ($item['harga'] ?? 0);
                        $subtotal = $jumlah * $harga;
                    @endphp
                    <div class="card pengiriman-item-card mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                <div class="mr-2 mb-2">
                                    <div class="pengiriman-item-index">Barang {{ $index + 1 }}</div>
                                    <h6 class="mb-1">{{ $item['barang']->nama_barang }}</h6>
                                    <div class="text-muted small">Satuan: {{ $item['satuan'] }}</div>
                                </div>
                                <div class="pengiriman-price-box mb-2">
                                    <span>Harga Satuan</span>
                                    <strong>Rp {{ number_format($harga, 0, ',', '.') }}</strong>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 col-md-3 mb-2">
                                    <div class="pengiriman-mini-stat">
                                        <span>Jumlah</span>
                                        <strong>{{ number_format($jumlah, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <div class="pengiriman-mini-stat">
                                        <span>Satuan</span>
                                        <strong>{{ $item['satuan'] }}</strong>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <div class="pengiriman-mini-stat">
                                        <span>Harga</span>
                                        <strong>Rp {{ number_format($harga, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mb-2">
                                    <div class="pengiriman-mini-stat">
                                        <span>Subtotal</span>
                                        <strong class="pengiriman-subtotal-text">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light border text-muted mb-0">
                        Belum ada item barang pada pengiriman ini.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
    </div>
</div>

<style>
    #myModal .pengiriman-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
    }

    #myModal .pengiriman-info-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 0.75rem 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    #myModal .pengiriman-info-label {
        font-size: 0.78rem;
        color: #6b7280;
    }

    #myModal .pengiriman-total-summary {
        border: 1px solid #fddd7f;
        border-radius: 10px;
        background: #fff7d9;
        padding: 0.65rem 0.85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    #myModal .pengiriman-total-group {
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

    #myModal .pengiriman-total-group strong {
        font-size: 1.06rem;
        color: #8a6d1a;
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

    #myModal .pengiriman-price-box {
        border-radius: 10px;
        border: 1px solid #fddd7f;
        background: #fff7d9;
        padding: 0.45rem 0.7rem;
        min-width: 150px;
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }

    #myModal .pengiriman-price-box span {
        font-size: 0.76rem;
        color: #8a6d1a;
    }

    #myModal .pengiriman-price-box strong {
        color: #8a6d1a;
    }

    #myModal .pengiriman-mini-stat {
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

    #myModal .pengiriman-mini-stat span {
        font-size: 0.74rem;
        color: #6b7280;
    }

    #myModal .pengiriman-subtotal-text {
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.1;
    }

    @media (max-width: 767.98px) {
        #myModal .modal-body {
            padding: 0.9rem;
        }

        #myModal .pengiriman-item-card .card-body {
            padding: 0.85rem;
        }

        #myModal .pengiriman-total-summary {
            flex-direction: column;
            align-items: flex-start;
        }

        #myModal .pengiriman-total-group.text-right {
            text-align: left !important;
        }

        #myModal .pengiriman-price-box {
            width: 100%;
            text-align: left;
        }
    }
</style>

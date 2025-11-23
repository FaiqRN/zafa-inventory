<div id="modal-master" class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Detail Pengiriman</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <table class="table table-sm table-borderless">
                <tr>
                    <th width="30%">No. Pengiriman</th>
                    <td>{{ $pengiriman->nomer_pengiriman }}</td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td>{{ \Carbon\Carbon::parse($pengiriman->tanggal_pengiriman)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Toko</th>
                    <td>{{ $pengiriman->toko->nama_toko }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @if($pengiriman->status === 'proses')
                            <span class="badge badge-warning">Proses</span>
                        @elseif($pengiriman->status === 'terkirim')
                            <span class="badge badge-success">Terkirim</span>
                        @else
                            <span class="badge badge-danger">Batal</span>
                        @endif
                    </td>
                </tr>
            </table>

            <hr>
            <h6>Daftar Barang</h6>
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pengiriman->details as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detail->barang->nama_barang }}</td>
                        <td class="text-right">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                        <td>{{ $detail->satuan }}</td>
                        <td class="text-right">Rp {{ number_format($detail->harga, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th class="text-right">{{ number_format($pengiriman->total_jumlah, 0, ',', '.') }}</th>
                        <th colspan="2"></th>
                        <th class="text-right">Rp {{ number_format($pengiriman->total_nilai, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
    </div>
</div>

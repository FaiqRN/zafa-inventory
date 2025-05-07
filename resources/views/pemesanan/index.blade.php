@extends('layouts.template')

@section('content')
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filter Data</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Barang</label>
                            <select class="form-control" id="filter_barang_id" name="filter_barang_id">
                                <option value="">Semua Barang</option>
                                @foreach($barang as $b)
                                    <option value="{{ $b->barang_id }}">{{ $b->nama_barang }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" id="filter_status" name="filter_status">
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu</option>
                                <option value="diproses">Diproses</option>
                                <option value="dikirim">Dikirim</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" class="form-control" id="filter_start_date" name="filter_start_date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Akhir</label>
                            <input type="date" class="form-control" id="filter_end_date" name="filter_end_date">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" id="btnFilter" class="btn btn-primary">Filter</button>
                        <button type="button" id="resetFilter" class="btn btn-secondary">Reset</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Pemesanan Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Pemesanan</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" id="btnTambahPemesanan">
                    <i class="fas fa-plus"></i> Tambah Pemesanan
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="alert-container"></div>
            
            <div class="table-responsive">
                <table id="table-pemesanan" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pemesanan</th>
                            <th>Tanggal</th>
                            <th>Nama Pemesan</th>
                            <th>Barang</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan dimuat oleh AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Pemesanan -->
<div class="modal fade" id="modalPemesanan" tabindex="-1" role="dialog" aria-labelledby="modalPemesananLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPemesananLabel">Tambah Pemesanan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formPemesanan">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="form_action" value="add">
                    <input type="hidden" id="pemesanan_id" name="pemesanan_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="no_pemesanan">Nomor Pemesanan</label>
                                <input type="text" class="form-control" id="no_pemesanan" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggal_pemesanan">Tanggal Pemesanan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_pemesanan" name="tanggal_pemesanan" required>
                                <div class="invalid-feedback" id="error-tanggal_pemesanan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama_pemesan">Nama Pemesan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_pemesan" name="nama_pemesan" required>
                                <div class="invalid-feedback" id="error-nama_pemesan"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_pemesan">Email Pemesan <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email_pemesan" name="email_pemesan" required>
                                <div class="invalid-feedback" id="error-email_pemesan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="no_telp_pemesan">No. Telepon <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="no_telp_pemesan" name="no_telp_pemesan" required>
                                <div class="invalid-feedback" id="error-no_telp_pemesan"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pemesanan_dari">Sumber Pemesanan <span class="text-danger">*</span></label>
                                <select class="form-control" id="pemesanan_dari" name="pemesanan_dari" required>
                                    <option value="">-- Pilih Sumber --</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                    <option value="Instagram">Instagram</option>
                                    <option value="Shopee">Shopee</option>
                                    <option value="Tokopedia">Tokopedia</option>
                                    <option value="Website">Website</option>
                                    <option value="Langsung">Langsung</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                                <div class="invalid-feedback" id="error-pemesanan_dari"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat_pemesan">Alamat Pemesan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamat_pemesan" name="alamat_pemesan" rows="2" required></textarea>
                        <div class="invalid-feedback" id="error-alamat_pemesan"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="barang_id">Barang <span class="text-danger">*</span></label>
                                <select class="form-control" id="barang_id" name="barang_id" required>
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($barang as $b)
                                        <option value="{{ $b->barang_id }}" data-harga="{{ $b->harga_awal_barang ?? 0 }}">{{ $b->nama_barang }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-barang_id"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jumlah_pesanan">Jumlah Pesanan <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="jumlah_pesanan" name="jumlah_pesanan" min="1" required>
                                <div class="invalid-feedback" id="error-jumlah_pesanan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="harga_satuan">Harga Satuan</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="harga_satuan" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="total">Total <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="total" name="total" min="0" required>
                                </div>
                                <div class="invalid-feedback" id="error-total"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="metode_pembayaran">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-control" id="metode_pembayaran" name="metode_pembayaran" required>
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="Cash">Cash</option>
                                    <option value="COD">COD</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                                <div class="invalid-feedback" id="error-metode_pembayaran"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status_pemesanan">Status Pemesanan <span class="text-danger">*</span></label>
                                <select class="form-control" id="status_pemesanan" name="status_pemesanan" required>
                                    <option value="pending">Menunggu</option>
                                    <option value="diproses">Diproses</option>
                                    <option value="dikirim">Dikirim</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="dibatalkan">Dibatalkan</option>
                                </select>
                                <div class="invalid-feedback" id="error-status_pemesanan"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tanggal Status Fields (Show/hide based on status) -->
                    <div class="row" id="tanggal-status-container">
                        <!-- Tanggal Diproses (For 'diproses', 'dikirim', and 'selesai' status) -->
                        <div class="col-md-4" id="tanggal-diproses-container" style="display: none;">
                            <div class="form-group">
                                <label for="tanggal_diproses">Tanggal Diproses <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_diproses" name="tanggal_diproses">
                                <div class="invalid-feedback" id="error-tanggal_diproses"></div>
                            </div>
                        </div>
                        
                        <!-- Tanggal Dikirim (For 'dikirim' and 'selesai' status) -->
                        <div class="col-md-4" id="tanggal-dikirim-container" style="display: none;">
                            <div class="form-group">
                                <label for="tanggal_dikirim">Tanggal Dikirim <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_dikirim" name="tanggal_dikirim">
                                <div class="invalid-feedback" id="error-tanggal_dikirim"></div>
                            </div>
                        </div>
                        
                        <!-- Tanggal Selesai (For 'selesai' status) -->
                        <div class="col-md-4" id="tanggal-selesai-container" style="display: none;">
                            <div class="form-group">
                                <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai">
                                <div class="invalid-feedback" id="error-tanggal_selesai"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="catatan_pemesanan">Catatan</label>
                        <textarea class="form-control" id="catatan_pemesanan" name="catatan_pemesanan" rows="3"></textarea>
                        <div class="invalid-feedback" id="error-catatan_pemesanan"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Pemesanan -->
<div class="modal fade" id="modalDetailPemesanan" tabindex="-1" role="dialog" aria-labelledby="modalDetailPemesananLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailPemesananLabel">Detail Pemesanan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>No. Pemesanan</label>
                            <input type="text" class="form-control" id="detail_pemesanan_id" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tanggal Pemesanan</label>
                            <input type="text" class="form-control" id="detail_tanggal_pemesanan" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nama Pemesan</label>
                            <input type="text" class="form-control" id="detail_nama_pemesan" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" class="form-control" id="detail_email_pemesan" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" class="form-control" id="detail_no_telp_pemesan" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Sumber Pemesanan</label>
                            <input type="text" class="form-control" id="detail_pemesanan_dari" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea class="form-control" id="detail_alamat_pemesan" rows="2" readonly></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Barang</label>
                            <input type="text" class="form-control" id="detail_barang" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Jumlah Pesanan</label>
                            <input type="text" class="form-control" id="detail_jumlah_pesanan" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Harga Satuan</label>
                            <input type="text" class="form-control" id="detail_harga_satuan" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total</label>
                            <input type="text" class="form-control" id="detail_total" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Metode Pembayaran</label>
                            <input type="text" class="form-control" id="detail_metode_pembayaran" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Status</label>
                            <input type="text" class="form-control" id="detail_status_pemesanan" readonly>
                        </div>
                    </div>
                </div>

                <!-- Detail tanggal status -->
                <div class="row" id="detail-tanggal-status-container">
                    <!-- Tanggal Diproses -->
                    <div class="col-md-4" id="detail-tanggal-diproses-container">
                        <div class="form-group">
                            <label>Tanggal Diproses</label>
                            <input type="text" class="form-control" id="detail_tanggal_diproses" readonly>
                        </div>
                    </div>
                    
                    <!-- Tanggal Dikirim -->
                    <div class="col-md-4" id="detail-tanggal-dikirim-container">
                        <div class="form-group">
                            <label>Tanggal Dikirim</label>
                            <input type="text" class="form-control" id="detail_tanggal_dikirim" readonly>
                        </div>
                    </div>
                    
                    <!-- Tanggal Selesai -->
                    <div class="col-md-4" id="detail-tanggal-selesai-container">
                        <div class="form-group">
                            <label>Tanggal Selesai</label>
                            <input type="text" class="form-control" id="detail_tanggal_selesai" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea class="form-control" id="detail_catatan_pemesanan" rows="3" readonly></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pemesanan dari: <strong id="delete-item-name"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{asset('adminlte/plugins/select2/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
@endpush

@push('js')
<script src="{{asset('adminlte/plugins/select2/js/select2.full.min.js')}}"></script>
<script src="{{ asset('js/pemesanan.js') }}?v={{ time() }}"></script>
@endpush
@extends('layouts.template')

@section('content')
<div class="container-fluid pemesanan-page">
    <!-- Filter Card - Single Row Layout -->
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
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">Barang</label>
                            <select class="form-control form-control-sm" id="filter_barang_id" name="filter_barang_id">
                                <option value="">Semua Barang</option>
                                @foreach($barang as $b)
                                    <option value="{{ $b->barang_id }}">{{ $b->nama_barang }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label class="small mb-1">Status</label>
                            <select class="form-control form-control-sm" id="filter_status" name="filter_status">
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu</option>
                                <option value="diproses">Diproses</option>
                                <option value="dikirim">Dikirim</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label class="small mb-1">Tanggal Mulai</label>
                            <input type="date" class="form-control form-control-sm" id="filter_start_date" name="filter_start_date">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label class="small mb-1">Tanggal Akhir</label>
                            <input type="date" class="form-control form-control-sm" id="filter_end_date" name="filter_end_date">
                        </div>
                    </div>
                    <div class="col-md-3 text-right">
                        <div class="form-group mb-0">
                            <label class="small mb-1 d-block">&nbsp;</label>
                            <button type="button" id="btnFilter" class="btn btn-primary btn-sm" title="Terapkan Filter">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button type="button" id="resetFilter" class="btn btn-secondary btn-sm ml-1" title="Reset Filter">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
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
                @can('create-pemesanan')
                <button type="button" class="btn btn-primary" id="btnTambahPemesanan">
                    <i class="fas fa-plus"></i> Tambah Pemesanan
                </button>
                @endcan
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

<!-- Modal Tambah/Edit Pemesanan - Multi-Step -->
<div class="modal fade" id="modalPemesanan" tabindex="-1" role="dialog" aria-labelledby="modalPemesananLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pemesanan-modal-header">
                <h5 class="modal-title text-white" id="modalPemesananLabel">Tambah Pemesanan</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <!-- Progress Steps -->
            <div class="modal-body py-2 bg-light">
                <div class="steps-container">
                    <div class="row">
                        <div class="col-4 text-center">
                            <div class="step-item active" data-step="1">
                                <div class="step-circle">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="step-label">Data Pelanggan</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="step-item" data-step="2">
                                <div class="step-circle">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="step-label">Barang & Kuantitas</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="step-item" data-step="3">
                                <div class="step-circle">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="step-label">Pembayaran</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form id="formPemesanan">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="form_action" value="add">
                    <input type="hidden" id="pemesanan_id" name="pemesanan_id">
                    
                    <!-- STEP 1: Data Pelanggan -->
                    <div class="step-content" id="step-1">
                        <div class="text-center mb-3">
                            <h5 class="text-primary"><i class="fas fa-user-circle"></i> Informasi Pelanggan</h5>
                            <p class="text-muted small">Masukkan data lengkap pelanggan</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="no_pemesanan" class="font-weight-bold">Nomor Pemesanan</label>
                                    <input type="text" class="form-control" id="no_pemesanan" readonly>
                                    <small class="text-muted">Otomatis dibuat sistem</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_pemesanan" class="font-weight-bold">
                                        Tanggal Pemesanan <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="tanggal_pemesanan" name="tanggal_pemesanan" required>
                                    <div class="invalid-feedback" id="error-tanggal_pemesanan"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama_pemesan" class="font-weight-bold">
                                        Nama Pemesan <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nama_pemesan" name="nama_pemesan" 
                                           placeholder="Masukkan nama lengkap" required>
                                    <div class="invalid-feedback" id="error-nama_pemesan"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email_pemesan" class="font-weight-bold">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email_pemesan" name="email_pemesan" 
                                           placeholder="contoh@email.com" required>
                                    <div class="invalid-feedback" id="error-email_pemesan"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="no_telp_pemesan" class="font-weight-bold">
                                        No. Telepon <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="no_telp_pemesan" name="no_telp_pemesan" 
                                           placeholder="08xxxxxxxxxx" required>
                                    <div class="invalid-feedback" id="error-no_telp_pemesan"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pemesanan_dari" class="font-weight-bold">
                                        Sumber Pemesanan <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="pemesanan_dari" name="pemesanan_dari" required>
                                        <option value="">-- Pilih Sumber --</option>
                                        <option value="WhatsApp">WhatsApp</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="Facebook">Facebook</option>
                                        <option value="Shopee">Shopee</option>
                                        <option value="Tokopedia">Tokopedia</option>
                                        <option value="Walk-in">Walk-in</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <div class="invalid-feedback" id="error-pemesanan_dari"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat_pemesan" class="font-weight-bold">
                                Alamat Lengkap <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="alamat_pemesan" name="alamat_pemesan" 
                                      rows="3" placeholder="Masukkan alamat lengkap pengiriman" required></textarea>
                            <div class="invalid-feedback" id="error-alamat_pemesan"></div>
                        </div>
                    </div>
                    
                    <!-- STEP 2: Data Barang - MULTI ITEM -->
                    <div class="step-content" id="step-2" style="display:none;">
                        <div class="text-center mb-3">
                            <h5 class="text-primary"><i class="fas fa-boxes"></i> Pilih Barang & Jumlah</h5>
                            <p class="text-muted small">Tambahkan produk yang dipesan (bisa lebih dari 1 item)</p>
                        </div>

                        <!-- Item List Container -->
                        <div id="items-container">
                            <!-- Item Row Template akan di-generate oleh JavaScript -->
                        </div>

                        <!-- Add Item Button -->
                        <div class="text-center mb-3">
                            <button type="button" class="btn btn-success btn-sm" id="btnAddItem">
                                <i class="fas fa-plus"></i> Tambah Barang
                            </button>
                        </div>

                        <!-- Total Keseluruhan -->
                        <div class="card bg-light mt-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <h6 class="mb-0">Total Item:</h6>
                                    </div>
                                    <div class="col-6 text-right">
                                        <h5 class="mb-0 text-info" id="total-items-count">0 item</h5>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <h5 class="mb-0 font-weight-bold">Total Keseluruhan:</h5>
                                    </div>
                                    <div class="col-6 text-right">
                                        <h4 class="mb-0 text-primary font-weight-bold" id="total-keseluruhan">Rp 0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden inputs untuk data yang akan dikirim -->
                        <input type="hidden" id="barang_id" name="barang_id">
                        <input type="hidden" id="jumlah_pesanan" name="jumlah_pesanan">
                        <input type="hidden" id="total" name="total">
                        <input type="hidden" id="items_data" name="items_data">
                        
                        <!-- Hidden select untuk fallback data barang -->
                        <select id="barang_fallback" style="display:none;">
                            <option value="">-- Pilih Barang --</option>
                            @foreach($barang as $b)
                                <option value="{{ $b->barang_id }}" 
                                        data-kode="{{ $b->barang_kode }}"
                                        data-nama="{{ $b->nama_barang }}"
                                        data-harga="{{ $b->harga_awal_barang }}" 
                                        data-stok="{{ $b->stok }}"
                                        data-satuan="{{ $b->satuan }}">
                                    {{ $b->barang_kode }} - {{ $b->nama_barang }} (Stok: {{ $b->stok }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- STEP 3: Pembayaran & Status -->
                    <div class="step-content" id="step-3" style="display:none;">
                        <div class="text-center mb-3">
                            <h5 class="text-primary"><i class="fas fa-credit-card"></i> Metode Pembayaran & Status</h5>
                            <p class="text-muted small">Tentukan metode pembayaran dan status pesanan</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="metode_pembayaran" class="font-weight-bold">
                                        Metode Pembayaran <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="metode_pembayaran" name="metode_pembayaran" required>
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="Tunai">Tunai</option>
                                        <option value="Transfer Bank">Transfer Bank</option>
                                        <option value="QRIS">QRIS</option>
                                        <option value="E-Wallet">E-Wallet</option>
                                        <option value="COD">COD (Cash on Delivery)</option>
                                    </select>
                                    <div class="invalid-feedback" id="error-metode_pembayaran"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status_pemesanan" class="font-weight-bold">
                                        Status Pemesanan <span class="text-danger">*</span>
                                    </label>
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

                        <!-- Tanggal Status (Dynamic) -->
                        <div class="row">
                            <div class="col-md-4" id="tanggal-diproses-container" style="display:none;">
                                <div class="form-group">
                                    <label for="tanggal_diproses" class="font-weight-bold">
                                        Tanggal Diproses
                                    </label>
                                    <input type="date" class="form-control" id="tanggal_diproses" name="tanggal_diproses">
                                    <div class="invalid-feedback" id="error-tanggal_diproses"></div>
                                </div>
                            </div>
                            <div class="col-md-4" id="tanggal-dikirim-container" style="display:none;">
                                <div class="form-group">
                                    <label for="tanggal_dikirim" class="font-weight-bold">
                                        Tanggal Dikirim
                                    </label>
                                    <input type="date" class="form-control" id="tanggal_dikirim" name="tanggal_dikirim">
                                    <div class="invalid-feedback" id="error-tanggal_dikirim"></div>
                                </div>
                            </div>
                            <div class="col-md-4" id="tanggal-selesai-container" style="display:none;">
                                <div class="form-group">
                                    <label for="tanggal_selesai" class="font-weight-bold">
                                        Tanggal Selesai
                                    </label>
                                    <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai">
                                    <div class="invalid-feedback" id="error-tanggal_selesai"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="catatan_pemesanan" class="font-weight-bold">Catatan (Opsional)</label>
                            <textarea class="form-control" id="catatan_pemesanan" name="catatan_pemesanan" 
                                      rows="3" placeholder="Tambahkan catatan khusus jika ada"></textarea>
                            <div class="invalid-feedback" id="error-catatan_pemesanan"></div>
                        </div>

                        <!-- Order Summary -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-receipt"></i> Ringkasan Pesanan:</h6>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Nama Pelanggan:</div>
                                    <div class="col-7 text-right font-weight-bold" id="summary-nama">-</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">No. Telepon:</div>
                                    <div class="col-7 text-right font-weight-bold" id="summary-telp">-</div>
                                </div>
                                <hr>
                                <h6 class="font-weight-bold mb-2">Daftar Barang:</h6>
                                <div id="summary-items-list" class="mb-2">
                                    <!-- Items akan di-generate oleh JavaScript -->
                                </div>
                                <hr>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted"><strong>Total Item:</strong></div>
                                    <div class="col-6 text-right font-weight-bold text-info" id="summary-total-items">0</div>
                                </div>
                                <div class="row">
                                    <div class="col-6 text-muted"><strong>Total Keseluruhan:</strong></div>
                                    <div class="col-6 text-right text-primary font-weight-bold" 
                                         style="font-size: 1.3rem;" id="summary-total">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnPrevStep" style="display:none;">
                        <i class="fas fa-arrow-left"></i> Sebelumnya
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnNextStep">
                        Selanjutnya <i class="fas fa-arrow-right"></i>
                    </button>
                    @canany(['create-pemesanan', 'edit-pemesanan'])
                    <button type="submit" class="btn btn-success" id="btnSubmit" style="display:none;">
                        <i class="fas fa-save"></i> Simpan Pemesanan
                    </button>
                    @endcanany
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Pemesanan -->
<div class="modal fade" id="modalDetailPemesanan" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white" id="modalDetailLabel">
                    <i class="fas fa-file-invoice"></i> Detail Pemesanan
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
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
                    <div class="col-md-4" id="detail-tanggal-diproses-container">
                        <div class="form-group">
                            <label>Tanggal Diproses</label>
                            <input type="text" class="form-control" id="detail_tanggal_diproses" readonly>
                        </div>
                    </div>
                    <div class="col-md-4" id="detail-tanggal-dikirim-container">
                        <div class="form-group">
                            <label>Tanggal Dikirim</label>
                            <input type="text" class="form-control" id="detail_tanggal_dikirim" readonly>
                        </div>
                    </div>
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
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pemesanan dari: <strong id="delete-item-name"></strong>?</p>
                <p class="text-danger"><small><i class="fas fa-info-circle"></i> Data yang sudah dihapus tidak dapat dikembalikan!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnDelete">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{asset('adminlte/plugins/select2/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
<style>
/* Multi-Step Modal Styles */
.pemesanan-page .steps-container {
    padding: 15px 0;
}

.pemesanan-page .step-item {
    position: relative;
    cursor: default;
}

.pemesanan-page .step-item::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #dee2e6;
    z-index: 0;
}

.pemesanan-page .step-item:first-child::before {
    width: 50%;
    left: 50%;
}

.pemesanan-page .step-item:last-child::before {
    width: 50%;
    left: 0;
}

.pemesanan-page .step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 20px;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}

.pemesanan-page .step-item.active .step-circle {
    background: linear-gradient(135deg, var(--zafa-yellow) 0%, var(--zafa-orange) 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.35);
    transform: scale(1.1);
}

.pemesanan-page .step-item.completed .step-circle {
    background: var(--zafa-turquoise);
    color: white;
}

.pemesanan-page .step-label {
    font-size: 13px;
    font-weight: 600;
    color: #6c757d;
}

.pemesanan-page .step-item.active .step-label {
    color: var(--zafa-orange);
}

.pemesanan-page .step-item.completed .step-label {
    color: var(--zafa-teal);
}

/* Form Animation */
.pemesanan-page .step-content {
    animation: fadeIn 0.4s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Input Styles */
.pemesanan-page .form-control:focus {
    border-color: var(--zafa-yellow);
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.2);
}

/* Info Box Enhancement */
.pemesanan-page #barang-info {
    border-left: 4px solid var(--zafa-turquoise);
}

/* Summary Card */
.pemesanan-page .card.bg-light {
    border-left: 4px solid var(--zafa-yellow);
}

.pemesanan-page .text-primary {
    color: var(--zafa-orange) !important;
}

.pemesanan-page .text-info {
    color: var(--zafa-teal) !important;
}

.pemesanan-modal-header {
    background-color: var(--zafa-yellow) !important;
    border-bottom: 1px solid var(--zafa-orange) !important;
}

.pemesanan-modal-header .modal-title,
.pemesanan-modal-header .close {
    color: var(--zafa-dark) !important;
    text-shadow: none !important;
}

/* Select2 Custom */
.pemesanan-page .select2-container--bootstrap4 .select2-selection {
    height: calc(2.25rem + 2px);
}

.pemesanan-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    line-height: calc(2.25rem);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .pemesanan-page .step-label {
        font-size: 11px;
    }
    
    .pemesanan-page .step-circle {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
}
</style>
@endpush

@push('js')
<script>
window.pemesananPermissions = {
    create: @json(\Illuminate\Support\Facades\Gate::allows('create-pemesanan')),
    edit: @json(\Illuminate\Support\Facades\Gate::allows('edit-pemesanan')),
    delete: @json(\Illuminate\Support\Facades\Gate::allows('delete-pemesanan')),
};
</script>
<script src="{{asset('adminlte/plugins/select2/js/select2.full.min.js')}}"></script>
<script src="{{ asset('js/pemesanan.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/pemesanan-multi-item.js') }}?v={{ time() }}"></script>
@endpush

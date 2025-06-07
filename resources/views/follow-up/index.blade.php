@extends('layouts.template')

@section('page_title', 'Follow Up Pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
    <li class="breadcrumb-item active">Follow Up Pelanggan</li>
@endsection

@push('css')
    <!-- Follow Up Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/follow-up.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.css">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header Card -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Follow Up Pelanggan
                    </h3>
                </div>
                <div class="badge badge-warning p-2" style="font-size: 1rem;">
                    🥔 Zafa Potato CRM
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel Kiri - Filter, Upload, Pesan, Riwayat -->
        <div class="col-lg-8">
            <!-- Filter Card -->
            <div class="card card-outline card-info">
                <div class="card-header filter-card">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter mr-2"></i>
                        Filter Target Customer
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">
                                <i class="fas fa-users mr-1"></i>
                                Jenis Customer
                            </h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="keseluruhan" value="keseluruhan">
                                <label class="form-check-label" for="keseluruhan">
                                    <span class="badge badge-secondary customer-badge mr-1">ALL</span>
                                    Keseluruhan Customer
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="pelangganLama" value="pelangganLama">
                                <label class="form-check-label" for="pelangganLama">
                                    <span class="badge badge-primary customer-badge mr-1">VIP</span>
                                    Pelanggan Lama (≥3 transaksi)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="pelangganBaru" value="pelangganBaru">
                                <label class="form-check-label" for="pelangganBaru">
                                    <span class="badge badge-success customer-badge mr-1">NEW</span>
                                    Pelanggan Baru (1 bulan terakhir)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="pelangganTidakKembali" value="pelangganTidakKembali">
                                <label class="form-check-label" for="pelangganTidakKembali">
                                    <span class="badge badge-warning customer-badge mr-1">⚠️</span>
                                    Pelanggan Tidak Kembali (>2 bulan)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">
                                <i class="fas fa-share-alt mr-1"></i>
                                Sumber Pesanan
                            </h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="shopee" value="shopee">
                                <label class="form-check-label" for="shopee">
                                    <span class="badge badge-warning customer-badge mr-1">🛒</span>
                                    Shopee
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="tokopedia" value="tokopedia">
                                <label class="form-check-label" for="tokopedia">
                                    <span class="badge badge-warning customer-badge mr-1">🛒</span>
                                    Tokopedia
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="whatsapp" value="whatsapp">
                                <label class="form-check-label" for="whatsapp">
                                    <span class="badge badge-success customer-badge mr-1">📱</span>
                                    WhatsApp
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="instagram" value="instagram">
                                <label class="form-check-label" for="instagram">
                                    <span class="badge badge-danger customer-badge mr-1">📷</span>
                                    Instagram
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="langsung" value="langsung">
                                <label class="form-check-label" for="langsung">
                                    <span class="badge badge-info customer-badge mr-1">🏪</span>
                                    Langsung
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Summary -->
                    <div id="filterSummary" class="filter-summary" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Filter Aktif:</strong> <span id="filterList"></span>
                            </div>
                            <div>
                                Target: <span class="customer-count-badge" id="customerCount">0</span> customer
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Gambar Card -->
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-image mr-2"></i>
                        Upload Gambar (Opsional)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Drag & Drop gambar atau klik untuk pilih</h5>
                        <p class="text-muted mb-3">Format: JPG, PNG, GIF - Maksimal 5MB per file</p>
                        <input type="file" id="imageInput" multiple accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('imageInput').click()">
                            <i class="fas fa-folder-open mr-1"></i>
                            Pilih Gambar
                        </button>
                    </div>
                    
                    <!-- Image Preview Area -->
                    <div id="imagePreviewArea" class="mt-3" style="display: none;">
                        <h6 class="font-weight-bold mb-2">Preview Gambar:</h6>
                        <div id="imagePreviewContainer" class="d-flex flex-wrap"></div>
                    </div>
                </div>
            </div>

            <!-- Pesan Card -->
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-comment-alt mr-2"></i>
                        Tulis Pesan Follow Up
                    </h5>
                </div>
                <div class="card-body">
                    <form id="massFollowUpForm">
                        @csrf
                        <div class="form-group">
                            <label for="followUpMessage">
                                <i class="fas fa-pen mr-1"></i>
                                Pesan untuk Customer
                            </label>
                            <textarea class="form-control" id="followUpMessage" name="message" rows="5" 
                                placeholder="Contoh: Halo! Terima kasih sudah menjadi pelanggan setia Zafa Potato. Ada promo spesial untuk Anda! 🥔"></textarea>
                            <small class="form-text text-muted">
                                <span id="charCount">0</span>/1000 karakter
                            </small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Tips:</strong> Anda bisa mengirim pesan teks saja, gambar saja, atau kombinasi keduanya. Minimal salah satu harus diisi.
                        </div>
                        
                        <div class="text-right">
                            <button type="button" class="btn btn-secondary mr-2" id="previewBtn">
                                <i class="fas fa-eye mr-1"></i>
                                Preview
                            </button>
                            <button type="submit" class="btn btn-success" id="sendMassFollowUpBtn" disabled>
                                <i class="fas fa-paper-plane mr-2"></i>
                                Kirim ke <span id="targetCount">0</span> Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Riwayat Card -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-history mr-2"></i>
                        Riwayat Follow Up
                    </h5>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="refreshRiwayatBtn" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-striped table-sm riwayat-table">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th style="width: 120px;">Tanggal</th>
                                    <th>Pesan</th>
                                    <th style="width: 80px;">Gambar</th>
                                    <th style="width: 150px;">Customer</th>
                                    <th style="width: 100px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="riwayatTableBody">
                                <!-- Data riwayat akan dimuat via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No Data Message -->
                    <div id="noRiwayatMessage" class="text-center p-4" style="display: none;">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum Ada Riwayat</h5>
                        <p class="text-muted">Riwayat follow up akan muncul setelah Anda mengirim pesan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Kanan - Daftar Customer -->
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-users mr-2"></i>
                        Data Customer
                    </h5>
                    <div class="card-tools">
                        <span class="badge badge-primary" id="customerListCount">0 customer</span>
                    </div>
                </div>
                
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <!-- Search Bar -->
                    <div class="p-3 border-bottom">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="searchCustomer" placeholder="Cari nama, phone, email...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="loadFilteredCustomers()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="customerList">
                        <!-- Customer data akan dimuat via AJAX -->
                    </div>
                    
                    <!-- Default State -->
                    <div id="defaultCustomerState" class="text-center p-4">
                        <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Pilih Filter</h6>
                        <p class="text-muted mb-0">Centang filter di sebelah kiri untuk melihat daftar customer</p>
                    </div>
                    
                    <!-- No Data State -->
                    <div id="noCustomerData" class="text-center p-4" style="display: none;">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Tidak Ada Data</h6>
                        <p class="text-muted mb-0">Tidak ada customer yang sesuai dengan filter yang dipilih</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Detail Modal (tanpa avatar) -->
<div class="modal fade" id="customerDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-circle mr-2"></i>
                    Detail Customer
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="customer-initial-circle mb-3">
                            <span id="modalCustomerInitial" class="initial-text"></span>
                        </div>
                        <div id="modalCustomerBadges"></div>
                    </div>
                    <div class="col-md-9">
                        <h4 id="modalCustomerName" class="font-weight-bold text-primary mb-3"></h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <strong><i class="fas fa-phone text-primary mr-2"></i>Telepon:</strong>
                                    <p id="modalCustomerPhone" class="mb-0"></p>
                                </div>
                                <div class="info-item mb-3">
                                    <strong><i class="fas fa-envelope text-primary mr-2"></i>Email:</strong>
                                    <p id="modalCustomerEmail" class="mb-0"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <strong><i class="fas fa-shopping-cart text-primary mr-2"></i>Order Terakhir:</strong>
                                    <p id="modalCustomerLastOrder" class="mb-0"></p>
                                </div>
                                <div class="info-item mb-3">
                                    <strong><i class="fas fa-history text-primary mr-2"></i>Total Pesanan:</strong>
                                    <p id="modalCustomerTotalOrders" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-map-marker-alt text-primary mr-2"></i>Alamat:</strong>
                            <p id="modalCustomerAddress" class="mb-0"></p>
                        </div>
                        
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-money-bill-wave text-primary mr-2"></i>Total Belanja:</strong>
                            <p id="modalCustomerTotalSpent" class="mb-0 text-success font-weight-bold"></p>
                        </div>
                        
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-box text-primary mr-2"></i>Produk Terakhir:</strong>
                            <p id="modalCustomerLastProduct" class="mb-0"></p>
                        </div>
                        
                        <div class="info-item">
                            <strong><i class="fas fa-sticky-note text-primary mr-2"></i>Catatan:</strong>
                            <p id="modalCustomerNotes" class="mb-0 text-muted"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="sendIndividualBtn">
                    <i class="fas fa-paper-plane mr-1"></i>
                    Kirim Follow Up Individual
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Message Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye mr-2"></i>
                    Preview Pesan Follow Up
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Pesan ini akan dikirim via WhatsApp ke <strong id="previewTargetCount">0</strong> customer
                </div>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Preview Pesan:</strong>
                    </div>
                    <div class="card-body">
                        <div id="previewImages" class="mb-3"></div>
                        <div id="previewMessage" class="border p-3 bg-light rounded"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success" id="confirmSendBtn">
                    <i class="fas fa-paper-plane mr-1"></i>
                    Kirim Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Image Full View Modal -->
<div class="modal fade" id="imageFullModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Gambar</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="fullImageView" src="" alt="Full Image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('js')
    <!-- SweetAlert2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <!-- Follow Up Custom JavaScript -->
    <script src="{{ asset('js/follow-up.js') }}"></script>
@endpush
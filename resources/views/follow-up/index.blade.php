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
    <!-- Header Card with Device Status -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">
                        <i class=""></i>
                        Follow Up Pelanggan
                    </h3>
                </div>
                <div class="d-flex align-items-center">
                    <!-- WhatsApp Device Status Indicator -->
                    <div id="deviceStatusIndicator" class="mr-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary rounded-circle mr-2" style="width: 8px; height: 8px;"></div>
                            <small class="text-muted">Checking WhatsApp...</small>
                        </div>
                    </div>
                    
                    <!-- Test Connection Button -->
                    <button type="button" class="btn btn-sm btn-outline-success mr-2" id="testConnectionBtn" title="Test WhatsApp Connection">
                        <i class="fas fa-wifi mr-1"></i>
                        Test
                    </button>
                    
                    <div class="badge badge-warning p-2" style="font-size: 1rem;">
                        Zafa Potato App
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel Kiri - Filter dan Customer List -->
        <div class="col-lg-8">
            <!-- Filter Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 font-weight-bold">Pilih Target Customer</h6>
                </div>
                <div class="card-body py-3">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted small mb-2">Jenis Customer</p>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="keseluruhan" value="keseluruhan">
                                <label class="form-check-label" for="keseluruhan">Keseluruhan Customer</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="pelangganLama" value="pelangganLama">
                                <label class="form-check-label" for="pelangganLama">Pelanggan Lama (≥3 transaksi)</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="pelangganBaru" value="pelangganBaru">
                                <label class="form-check-label" for="pelangganBaru">Pelanggan Baru (1 bulan terakhir)</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="pelangganTidakKembali" value="pelangganTidakKembali">
                                <label class="form-check-label" for="pelangganTidakKembali">Pelanggan Tidak Kembali (>2 bulan)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-2">Sumber Pesanan</p>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="shopee" value="shopee">
                                <label class="form-check-label" for="shopee">Shopee</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="tokopedia" value="tokopedia">
                                <label class="form-check-label" for="tokopedia">Tokopedia</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="whatsapp" value="whatsapp">
                                <label class="form-check-label" for="whatsapp">WhatsApp</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="instagram" value="instagram">
                                <label class="form-check-label" for="instagram">Instagram</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" id="langsung" value="langsung">
                                <label class="form-check-label" for="langsung">Langsung</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Summary -->
                    <div id="filterSummary" class="mt-3 p-2 bg-light rounded border" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small">
                                <strong>Filter Aktif:</strong> <span id="filterList"></span>
                            </div>
                            <div class="small">
                                <strong>Target:</strong> <span class="badge badge-primary" id="customerCount">0</span> customer
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Broadcast Message Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 font-weight-bold">Buat Broadcast Pesan</h6>
                </div>
                <div class="card-body">
                    <form id="massFollowUpForm">
                        @csrf
                        <div class="row">
                            <!-- Kolom Kiri - Upload Gambar -->
                            <div class="col-md-5">
                                <label class="small text-muted mb-2">Upload Gambar (Opsional)</label>
                                <div class="upload-box-simple rounded p-4 text-center" style="min-height: 240px; background-color: #fafafa; border: 2px dashed #667eea !important; transition: all 0.3s ease; cursor: pointer;" onclick="document.getElementById('imageInput').click()">
                                    <input type="file" id="imageInput" multiple accept="image/*" class="d-none">
                                    <div id="uploadPlaceholder">
                                        <i class="fas fa-image fa-3x mb-3" style="color: #667eea;"></i>
                                        <p class="text-dark mb-2">Klik untuk upload gambar</p>
                                        <p class="text-muted small mb-3">atau drag & drop file di sini</p>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); document.getElementById('imageInput').click()">
                                            <i class="fas fa-folder-open mr-1"></i> Pilih File
                                        </button>
                                        <p class="small text-muted mt-3 mb-0">JPG, PNG, GIF - Max 5MB</p>
                                    </div>
                                    
                                    <!-- Image Preview Area -->
                                    <div id="imagePreviewArea" style="display: none;">
                                        <div id="imagePreviewContainer" class="d-flex flex-wrap justify-content-center"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kolom Kanan - Pesan -->
                            <div class="col-md-7">
                                <label class="small text-muted mb-2">Pesan untuk Customer</label>
                                <textarea class="form-control" id="followUpMessage" name="message" rows="7" 
                                    placeholder="Contoh: Halo! Terima kasih sudah menjadi pelanggan setia Zafa Potato. Ada promo spesial untuk Anda!"></textarea>
                                <small class="form-text text-muted">
                                    <span id="charCount">0</span>/1000 karakter
                                </small>
                                
                                <div class="mt-3 d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Akan dikirim via WhatsApp</small>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="previewBtn">Preview</button>
                                        <button type="submit" class="btn btn-sm btn-primary" id="sendMassFollowUpBtn" disabled>
                                            Kirim ke <span id="targetCount">0</span> Customer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel Kanan - Daftar Customer -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 font-weight-bold">Daftar Customer</h6>
                        <span class="badge badge-light border" id="customerListCount">0 customer</span>
                    </div>
                </div>
                
                <div class="card-body p-0 d-flex flex-column" style="height: 396px;">
                    <!-- Search Bar -->
                    <div class="p-3 border-bottom">
                        <input type="text" class="form-control form-control-sm" id="searchCustomer" placeholder="Cari customer...">
                    </div>
                    
                    <div id="customerList" class="flex-grow-1" style="overflow-y: auto;">
                        <!-- Customer data akan dimuat via AJAX -->
                    </div>
                    
                    <!-- Default State -->
                    <div id="defaultCustomerState" class="text-center p-5">
                        <p class="text-muted mb-0">Pilih filter untuk melihat customer</p>
                    </div>
                    
                    <!-- No Data State -->
                    <div id="noCustomerData" class="text-center p-5" style="display: none;">
                        <p class="text-muted mb-0">Tidak ada customer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Riwayat Section - Full Width Center -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 font-weight-bold">Riwayat Broadcast</h6>
                        <button type="button" class="btn btn-sm btn-link text-secondary" id="refreshRiwayatBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light" style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th class="border-0 small" style="width: 60px;">ID</th>
                                    <th class="border-0 small" style="width: 140px;">Tanggal</th>
                                    <th class="border-0 small">Pesan</th>
                                    <th class="border-0 small" style="width: 80px;">Gambar</th>
                                    <th class="border-0 small" style="width: 180px;">Customer</th>
                                    <th class="border-0 small" style="width: 100px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="riwayatTableBody">
                                <!-- Data riwayat akan dimuat via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No Data Message -->
                    <div id="noRiwayatMessage" class="text-center p-4" style="display: none;">
                        <p class="text-muted mb-0">Belum ada riwayat broadcast</p>
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
                    <i class="fab fa-whatsapp mr-2"></i>
                    Preview Pesan WhatsApp
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fab fa-whatsapp mr-2"></i>
                    Pesan ini akan dikirim via WhatsApp ke <strong id="previewTargetCount">0</strong> customer
                </div>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Preview Pesan WhatsApp:</strong>
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
                    <i class="fab fa-whatsapp mr-1"></i>
                    Kirim WhatsApp Sekarang
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
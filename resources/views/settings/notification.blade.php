@extends('layouts.template')

@section('page_title', 'Pengaturan Notifikasi')

@php
    $activemenu = 'notification-settings';
    $breadcrumb = (object) [
        'title' => 'Pengaturan Notifikasi',
        'list' => ['Home', 'Sistem Pengaturan', 'Pengaturan Notifikasi']
    ];
@endphp

@push('css')
<style>
.setting-card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.setting-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.setting-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.form-control-lg {
    font-size: 1.1rem;
    font-weight: 600;
}
.info-text {
    font-size: 0.85rem;
    color: #6c757d;
}
.preview-box {
    background: linear-gradient(135deg, #FFC107 0%, #FFC107 80%);
    border-radius: 10px;
    padding: 20px;
    color: white;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Alert Container -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <form action="{{ route('notification-settings.update') }}" method="POST" id="settingsForm">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Left Column - Settings -->
            <div class="col-lg-8">
                <!-- Pengaturan Stok -->
                <div class="card setting-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex align-items-center">
                            <div class="setting-icon bg-warning text-white mr-3">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Notifikasi Stok Barang</h5>
                                <small class="text-muted">Atur kapan notifikasi stok akan muncul</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="stock_threshold" class="font-weight-bold">
                                <i class="fas fa-cubes mr-1"></i> Batas Stok Minimum
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control form-control-lg text-center" 
                                       id="stock_threshold" 
                                       name="stock_threshold" 
                                       value="{{ $settings['stock_threshold'] }}" 
                                       min="0" 
                                       max="1000"
                                       style="max-width: 150px;">
                                <div class="input-group-append">
                                    <span class="input-group-text">unit</span>
                                </div>
                            </div>
                            <p class="info-text mt-2 mb-0">
                                <i class="fas fa-info-circle mr-1"></i>
                                Notifikasi akan muncul jika stok barang <strong>&le; {{ $settings['stock_threshold'] }}</strong> unit.
                                <br>Set ke <strong>0</strong> untuk notifikasi hanya saat stok habis (kosong).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pengaturan Retur -->
                <div class="card setting-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex align-items-center">
                            <div class="setting-icon bg-warning text-white mr-3">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Notifikasi Retur Pengiriman</h5>
                                <small class="text-muted">Atur pengingat untuk pengiriman yang belum diretur</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="return_deadline_days" class="font-weight-bold">
                                        <i class="fas fa-calendar-alt mr-1"></i> Batas Waktu Retur
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control form-control-lg text-center" 
                                               id="return_deadline_days" 
                                               name="return_deadline_days" 
                                               value="{{ $settings['return_deadline_days'] }}" 
                                               min="7" 
                                               max="60"
                                               style="max-width: 120px;">
                                        <div class="input-group-append">
                                            <span class="input-group-text">hari</span>
                                        </div>
                                    </div>
                                    <p class="info-text mt-2 mb-0">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Batas waktu maksimal sejak barang diterima (tanggal terima) untuk diretur.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pending_return_days" class="font-weight-bold">
                                        <i class="fas fa-bell mr-1"></i> Peringatan H-
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control form-control-lg text-center" 
                                               id="pending_return_days" 
                                               name="pending_return_days" 
                                               value="{{ $settings['return_deadline_days'] - $settings['pending_return_days'] }}" 
                                               min="1" 
                                               max="30"
                                               style="max-width: 120px;">
                                        <div class="input-group-append">
                                            <span class="input-group-text">hari sebelum</span>
                                        </div>
                                    </div>
                                    <p class="info-text mt-2 mb-0">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Notifikasi muncul <strong>H-<span id="warningDaysPreview">{{ $settings['return_deadline_days'] - $settings['pending_return_days'] }}</span></strong> sebelum batas waktu.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengaturan Interval -->
                <div class="card setting-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex align-items-center">
                            <div class="setting-icon bg-warning text-white mr-3">
                                <i class="fas fa-sync-alt"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Interval Pengecekan</h5>
                                <small class="text-muted">Seberapa sering sistem mengecek notifikasi baru</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label for="check_interval" class="font-weight-bold">
                                <i class="fas fa-clock mr-1"></i> Interval Cek Otomatis
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control form-control-lg text-center" 
                                       id="check_interval" 
                                       name="check_interval" 
                                       value="{{ $settings['check_interval'] }}" 
                                       min="30" 
                                       max="300"
                                       style="max-width: 120px;">
                                <div class="input-group-append">
                                    <span class="input-group-text">detik</span>
                                </div>
                            </div>
                            <p class="info-text mt-2 mb-0">
                                <i class="fas fa-info-circle mr-1"></i>
                                Sistem akan mengecek notifikasi baru setiap <strong><span id="intervalPreview">{{ $settings['check_interval'] }}</span> detik</strong>.
                                <br>Nilai lebih kecil = lebih real-time, tapi lebih berat.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Preview & Actions -->
            <div class="col-lg-4">
                <!-- Preview Box -->
                <div class="card setting-card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-eye mr-2"></i>Preview Pengaturan</h5>
                    </div>
                    <div class="card-body">
                        <div class="preview-box mb-3">
                            <h6 class="text-white-100 font-weight-bold mb-3">Ringkasan Pengaturan</h6>
                            <div class="mb-2">
                                <i class="fas fa-box-open mr-2"></i>
                                <span>Stok &le; <strong id="previewStock">{{ $settings['stock_threshold'] }}</strong></span>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-calendar-check mr-2"></i>
                                <span>Batas retur: <strong id="previewDeadline">{{ $settings['return_deadline_days'] }}</strong> hari</span>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-bell mr-2"></i>
                                <span>Peringatan: <strong>H-<span id="previewWarning">{{ $settings['return_deadline_days'] - $settings['pending_return_days'] }}</span></strong></span>
                            </div>
                            <div>
                                <i class="fas fa-sync-alt mr-2"></i>
                                <span>Cek setiap: <strong id="previewInterval">{{ $settings['check_interval'] }}</strong> detik</span>
                            </div>
                        </div>
                        
                        <div class="alert alert-light border mb-0">
                            <small>
                                <i class="fas fa-lightbulb text-warning mr-1"></i>
                                <strong>Contoh:</strong> Dengan pengaturan ini, notifikasi retur akan muncul setelah barang diterima selama 
                                <strong><span id="exampleDays">{{ $settings['pending_return_days'] }}</span> hari</strong>.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card setting-card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg btn-block mb-3">
                            <i class="fas fa-save mr-2"></i> Simpan Pengaturan
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-block" id="btnReset">
                            <i class="fas fa-undo mr-2"></i> Reset ke Default
                        </button>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card bg-light mt-4">
                    <div class="card-body">
                        <h6><i class="fas fa-question-circle mr-2"></i>Bantuan</h6>
                        <ul class="mb-0 pl-3" style="font-size: 0.85rem;">
                            <li class="mb-2"><strong>Batas Stok:</strong> Set 0 untuk notif saat stok habis saja, atau angka lain untuk peringatan dini.</li>
                            <li class="mb-2"><strong>Batas Retur:</strong> Maksimal hari sejak tanggal terima sampai retur dilakukan.</li>
                            <li><strong>H- Peringatan:</strong> Berapa hari sebelum batas untuk mulai memberi peringatan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
    window.csrfToken = '{{ csrf_token() }}';
    window.notificationSettingsRoutes = {
        update: '{{ route("notification-settings.update") }}',
        reset: '{{ route("notification-settings.reset") }}'
    };
</script>
<script src="{{ asset('js/notification-settings.js') }}"></script>
@endpush

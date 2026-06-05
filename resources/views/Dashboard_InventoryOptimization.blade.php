@extends('layouts.template')

@section('title', 'dashboard')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/Dashboard_InventoryOptimization.css') }}" />
@endpush


@section('content')

{{-- METRIC SUMMARY --}}
{{--
    FIX: Tambah 4 metric card yang sebelumnya dicari oleh updateSummary() di JS
    tapi tidak ada elemen HTML-nya, sehingga setTextIfExists() selalu skip
    dan counter summary tidak pernah ter-update saat auto-refresh.
    Nilai server-side render ({{ }}) sebagai initial value,
    lalu JS update via setTextIfExists() saat auto-refresh berjalan.
--}}
<div class="inv-metric-grid">
    <div class="inv-metric-card">
        <div class="inv-metric-label">Total toko mitra</div>
        <div class="inv-metric-value" id="inv-m-total-toko">{{ count($tokosGeo) }}</div>
        <div class="inv-metric-sub">aktif</div>
    </div>

    <div class="inv-metric-card">
        <div class="inv-metric-label">Total kombinasi produk</div>
        <div class="inv-metric-value" id="inv-m-kombinasi">{{ count($rekomendasiData) }}</div>
        <div class="inv-metric-sub">produk × toko</div>
    </div>

    <div class="inv-metric-card inv-metric-card--danger">
        <div class="inv-metric-label">Di bawah ROP</div>
        <div class="inv-metric-value" id="inv-m-kritis">
            {{ collect($rekomendasiData)->where('is_below_rop', true)->count() }}
        </div>
        <div class="inv-metric-sub" id="inv-warn-count">
            {{ collect($rekomendasiData)->where('is_below_rop', true)->count() }} perlu perhatian
        </div>
    </div>

    <div class="inv-metric-card inv-metric-card--warn">
        <div class="inv-metric-label">Shelf life flag</div>
        <div class="inv-metric-value" id="inv-m-flag">
            {{ collect($rekomendasiData)->where('shelf_life_flag', true)->count() }}
        </div>
        <div class="inv-metric-sub" id="inv-flag-count">
            {{ collect($rekomendasiData)->where('shelf_life_flag', true)->count() }} interval > batas aman
        </div>
    </div>

    <div class="inv-metric-card inv-metric-card--ok">
        <div class="inv-metric-label">Stok aman</div>
        <div class="inv-metric-value" id="inv-m-ok">
            {{ collect($rekomendasiData)->where('is_below_rop', false)->count() }}
        </div>
        <div class="inv-metric-sub" id="inv-ok-count">
            {{ collect($rekomendasiData)->where('is_below_rop', false)->count() }} di atas ROP
        </div>
    </div>
</div>

<div class="inv-auto-refresh-bar" id="inv-auto-refresh-bar">
    <div class="inv-auto-refresh-left">
        <span class="inv-auto-refresh-dot is-idle" id="inv-auto-refresh-dot"></span>
        <span id="inv-auto-refresh-label">Auto-update aktif</span>
    </div>
    <div class="inv-auto-refresh-right">
        <span class="inv-refresh-time" id="inv-refresh-time">Update terakhir: -</span>
        <span class="inv-refresh-countdown" id="inv-refresh-countdown"></span>
    </div>
</div>

{{-- MAP --}}
<div class="inv-map-wrap">
    <div class="inv-map-search" role="search" aria-label="Cari toko di peta">
        <div class="inv-map-search-input">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input
                type="text"
                id="inv-toko-search"
                placeholder="Cari toko..."
                autocomplete="off"
                spellcheck="false"
                aria-label="Cari toko"
            >
            <button type="button" id="inv-toko-search-btn">Cari</button>
        </div>
        <div class="inv-map-search-status" id="inv-toko-search-status">Ketik nama toko lalu tekan Enter.</div>
    </div>
    <div id="inv-map"></div>
</div>

{{-- DETAIL PANEL --}}
<div class="inv-detail-panel">
    <div class="inv-detail-header">
        <span class="inv-detail-title" id="inv-detail-title">Pilih toko di peta</span>
        <span id="inv-detail-badge"></span>
    </div>
    <div class="inv-detail-body" id="inv-detail-body">
        <div class="inv-empty-state">
            Klik marker pada peta untuk melihat EOQ, SS, ROP, dan rekomendasi kirim per produk.
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        window.INV_DATA  = @json($rekomendasiData);
        window.INV_TOKOS = @json($tokosGeo);
        window.INV_NOMINATIM_BASE_URL = @json($nominatimBaseUrl ?? 'https://nominatim.openstreetmap.org');
        window.INV_AUTO_REFRESH_URL = @json(route('dashboard.api.inventory-optimization.auto-refresh'));

        {{-- Auto-refresh setiap 5 menit (300 detik) — truncate + regenerate semua kombinasi --}}
        window.INV_AUTO_REFRESH_INTERVAL_MS = 300000;
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/Dashboard_InventoryOptimization.js') }}"></script>
@endpush

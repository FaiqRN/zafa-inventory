@extends('layouts.template')

@section('title', 'dashboard')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/Dashboard_InventoryOptimization.css') }}" />
@endpush


@section('content')

{{-- METRIC SUMMARY --}}
<div class="inv-metric-grid">
    <div class="inv-metric-card">
        <div class="inv-metric-label">Total toko mitra</div>
        <div class="inv-metric-value" id="inv-m-total-toko">{{ count($tokosGeo) }}</div>
        <div class="inv-metric-sub">aktif bulan ini</div>
    </div>
</div>

<div class="inv-refresh-time inv-refresh-time-plain" id="inv-refresh-time">Update terakhir: -</div>

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

        {{--
            FIX: Auto-refresh diubah dari 10.000ms (10 detik) ke 300.000ms (5 menit).
            Interval 10 detik menyebabkan ratusan baris duplikat di inventory_rekomendasi
            karena setiap fetch memanggil hitungSemua() dari sisi server.
            5 menit cukup untuk pembaruan real-time dashboard tanpa membebani DB.
        --}}
        window.INV_AUTO_REFRESH_INTERVAL_MS = 300000;
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/Dashboard_InventoryOptimization.js') }}"></script>
@endpush
{{-- EOF --}}

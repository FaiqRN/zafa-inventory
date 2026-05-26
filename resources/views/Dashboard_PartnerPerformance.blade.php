    {{-- ═══ test new 6 ═══ --}}
@extends('layouts.template')

@section('title', 'dashboard')

@push('css')
<link rel="stylesheet" href="{{ asset('css/Dashboard_PartnerPerformance.css') }}">
@endpush

@section('content')
<div class="container-fluid">

    {{-- ═══════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════ --}}
    <div class="db-header" style="justify-content:flex-end;">
        <div class="d-flex align-items-center" style="gap:10px;">
            <a href="{{ route('analytics.partner-performance.index') }}"
               class="btn btn-sm text-white font-weight-bold"
               style="background:var(--za);border-radius:8px;font-size:12px;">
                <i class="fas fa-table mr-1"></i> Buka Laporan Lengkap
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
          Donut Kategori + Bar Skor Hybrid
    ═══════════════════════════════════════════ --}}
    <div class="row mb-3">
        <div class="col-lg-5 mb-3">
            <div class="db-panel fade-up donut-panel" style="animation-delay:.1s;">
                <div class="panel-head">
                    <div class="panel-head-left">
                        <div class="panel-icon"><i class="fas fa-chart-pie"></i></div>
                        <div>
                            <div class="panel-title">Distribusi Kategori Mitra</div>
                            <div class="panel-sub">Berdasarkan skor hybrid · Histori 2025 dan berlanjut ke 2026</div>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="d-flex align-items-center" style="gap:20px; flex-wrap:wrap;">
                        {{-- Donut --}}
                        <div class="donut-wrap">
                            <canvas id="chartDonut"></canvas>
                            <div class="donut-center">
                                <div class="donut-center-num" id="donutTotal">25</div>
                                <div class="donut-center-lbl">mitra</div>
                            </div>
                        </div>
                        {{-- Legend modern --}}
                        <div class="donut-legend-grid" id="donutLegend"></div>
                    </div>
                    <div id="donutNoData" class="chart-empty-state mt-2" style="display:none;"></div>
                    <div class="nav-hint">
                        <i class="fas fa-hand-pointer"></i>
                        Klik kartu kategori untuk melihat daftar mitra di kelompok tersebut
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar Chart — Score Hybrid Top 10 --}}
        <div class="col-lg-7 mb-3">
            <div class="db-panel fade-up" style="animation-delay:.15s;">
                <div class="panel-head">
                    <div class="panel-head-left">
                        <div class="panel-icon"><i class="fas fa-chart-bar"></i></div>
                        <div>
                            <div class="panel-title">Skor Kinerja Mitra (Top 10)</div>
                            <div class="panel-sub">Diurutkan dari skor tertinggi — klik batang untuk detail mitra</div>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="bar-chart-wrap">
                        <canvas id="chartBar"></canvas>
                    </div>
                    <div id="barNoData" class="chart-empty-state" style="display:none;"></div>
                    <div class="nav-hint">
                        <i class="fas fa-hand-pointer"></i>
                        Klik batang mitra untuk melihat detail KPI dan skor di halaman laporan
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════BARIS 3 — Line Chart Tren ANALITIK (Full Width) ═══════════════════════════════════════════ --}}
    <div class="row mb-3">
        <div class="col-12 mb-3">
            <div class="db-panel fade-up" style="animation-delay:.2s;">
                <div class="panel-head">
                    <div class="panel-head-left">
                        <div class="panel-icon"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="panel-title">Analisis Tren Kinerja Mitra — Time Series</div>
                            <div class="panel-sub" id="lineSubTitle">
                                Rata-rata skor hybrid · Moving Average 3-bulan · Tren Least Square · Forecasting
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-body">

                    {{-- Kontrol Analitik + Filter Bulan --}}
                    <div class="d-flex align-items-center justify-content-between mb-3" style="flex-wrap:wrap; gap:10px;">

                        {{-- Toggle Analisis --}}
                        <div class="analytic-pills" id="analyticPills">
                            <button class="analytic-pill active-ma" id="btnMA" onclick="toggleLayer('ma')">
                                <span style="display:inline-block;width:16px;height:2px;background:#E8A020;border-radius:1px;vertical-align:middle;"></span>
                                Moving Average (MA-3)
                            </button>
                            <button class="analytic-pill active-ls" id="btnLS" onclick="toggleLayer('ls')">
                                <span style="display:inline-block;width:16px;height:0px;border-top:2px dashed #6B6B66;vertical-align:middle;"></span>
                                Tren Least Square
                            </button>
                            <button class="analytic-pill active-fc" id="btnFC" onclick="toggleLayer('fc')">
                                <span style="display:inline-block;width:16px;height:2px;background:#1E607F;border-radius:1px;vertical-align:middle;opacity:.5;"></span>
                                Forecasting <span id="forecastHorizonLabel"></span>
                            </button>
                        </div>

                        {{-- Filter Kategori --}}
                        <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                            <span style="font-size:10px;color:var(--text-muted);font-weight:700;">FILTER:</span>
                            <button class="month-chip active" id="filterAll" onclick="setKatFilter('all')">Semua Mitra</button>
                            <button class="month-chip" id="filterA" onclick="setKatFilter('A')" style="border-color:#1F7A4D;color:#1F7A4D;">Kat A</button>
                            <button class="month-chip" id="filterB" onclick="setKatFilter('B')" style="border-color:#1E607F;color:#1E607F;">Kat B</button>
                            <button class="month-chip" id="filterC" onclick="setKatFilter('C')" style="border-color:#9A6B22;color:#9A6B22;">Kat C</button>
                            <button class="month-chip" id="filterD" onclick="setKatFilter('D')" style="border-color:#A7472E;color:#A7472E;">Kat D</button>
                        </div>
                    </div>

                    {{-- Canvas --}}
                    <div class="line-chart-wrap">
                        <canvas id="chartLine"></canvas>
                    </div>
                    <div id="lineNoData" class="chart-empty-state" style="display:none;"></div>

                    {{-- Legend --}}
                    <div class="line-legend">
                        <div class="line-legend-item">
                            <div class="line-legend-line" style="background:#1F7A4D;"></div>
                            <span>Skor Aktual</span>
                        </div>
                        <div class="line-legend-item" id="legendMA">
                            <div class="line-legend-line" style="background:#E8A020;"></div>
                            <span>Moving Average 3-bln</span>
                        </div>
                        <div class="line-legend-item" id="legendLS">
                            <div class="line-legend-dash" style="border-color:#6B6B66;"></div>
                            <span>Tren Least Square</span>
                        </div>
                        <div class="line-legend-item" id="legendFC">
                            <div class="line-legend-line" style="background:#1E607F;opacity:.5;"></div>
                            <span>Prediksi (Forecasting)</span>
                        </div>
                        <div class="ml-auto">
                            <span class="insight-badge" id="trendBadge" style="background:#E7F4ED;color:#1F7A4D;">
                                <i class="fas fa-arrow-trend-up"></i> <span id="trendText">Tren Meningkat</span>
                            </span>
                        </div>
                    </div>

                    <div class="nav-hint mt-2">
                        <i class="fas fa-info-circle"></i>
                        <span id="lineHintText">Skor aktual diambil dari rata-rata hybrid score semua mitra per bulan. Titik data forecast merupakan prediksi berbasis tren historis.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
    BARIS 4 — Retur Tertinggi + Profil KPI + Sebaran Wilayah
    ═══════════════════════════════════════════  --}}
    <div class="row mb-3">

        {{-- Mitra Retur Tertinggi --}}
        <div class="col-lg-4 mb-3">
            <div class="db-panel fade-up" style="animation-delay:.3s; height:calc(100% - 16px);">
                <div class="panel-head">
                    <div class="panel-head-left">
                        <div class="panel-icon" style="background:#FBEDE8; color:var(--d);">
                            <i class="fas fa-undo"></i>
                        </div>
                        <div>
                            <div class="panel-title">Mitra dengan Retur Tertinggi</div>
                            <div class="panel-sub">Perlu evaluasi sebelum pengiriman berikutnya</div>
                        </div>
                    </div>
                </div>
                <div class="panel-body" id="returList"></div>
            </div>
        </div>

        {{-- Profil KPI Rata-rata — Horizontal Bar Chart --}}
        <div class="col-lg-4 mb-3">
            <div class="db-panel fade-up" style="animation-delay:.35s; height:calc(100% - 16px);">
                <div class="panel-head">
                    <div class="panel-head-left">
                        <div class="panel-icon"><i class="fas fa-chart-bar"></i></div>
                        <div>
                            <div class="panel-title">Profil KPI Rata-rata Seluruh Mitra</div>
                            <div class="panel-sub">5 indikator kinerja dalam format yang lebih mudah dibaca</div>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <div style="position:relative; height:260px;">
                        <canvas id="chartKpiProfile"></canvas>
                    </div>
                    <div class="nav-hint">
                        <i class="fas fa-info-circle"></i>
                        Nilai indikator ditampilkan sebagai batang agar perbandingan antar KPI lebih cepat dibaca
                    </div>
                </div>
            </div>
        </div>

        {{-- Sebaran Kinerja per Wilayah --}}
        <div class="col-lg-4 mb-3">
            <div class="db-panel fade-up" style="animation-delay:.25s; height:calc(100% - 16px);">
                <div class="panel-head">
                    <div class="panel-head-left">
                        <div class="panel-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="panel-title">Kinerja per Kecamatan</div>
                            <div class="panel-sub">Rata-rata skor wilayah</div>
                        </div>
                    </div>
                </div>
                <div class="panel-body" id="wilayahList"></div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
window.DASHBOARD_PP_CONFIG = {
    apiDataUrl: @json(route('analytics.partner-performance.api.data')),
    apiTrendsUrl: @json(route('analytics.partner-performance.api.trends')),
    reportIndexUrl: @json(route('analytics.partner-performance.index')),
};
</script>
<script src="{{ asset('js/Dashboard_PartnerPerformance.js') }}"></script>
@endpush
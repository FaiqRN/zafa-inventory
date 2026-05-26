    {{-- ═══ test new 5 ═══ --}}
@extends('layouts.template')

@php
    $activemenu = 'analytics.partner-performance';
    $breadcrumb = (object) [
        'title' => 'Partner Performance Analytics',
        'list'  => ['Reports', 'Partner Performance']
    ];
@endphp

@push('css')
<link rel="stylesheet" href="{{ asset('css/Analytics_PartnerPerformance.css') }}">
@endpush

@section('content')
<div class="container-fluid">

    {{-- ═══ FILTER ═══ --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm filter-shell">
                <div class="card-body py-3 px-3">
                    <div class="filter-head">
                        <i class="fas fa-filter mr-1" style="color:var(--za);"></i> Filter Data Partner
                        <span class="text-muted" style="font-size:11px;font-weight:500;">(otomatis diterapkan)</span>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-3 mb-1 filter-group">
                            <label>Periode</label>
                            <div class="d-flex" style="gap:6px;">
                                <select id="fBulan" class="form-control form-control-sm">
                                    <option value="">Semua Bulan</option>
                                    @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i=>$b)
                                        <option value="{{ $i+1 }}">{{ $b }}</option>
                                    @endforeach
                                </select>
                                <select id="fTahun" class="form-control form-control-sm">
                                    <option value="2025" selected>2025</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-1 filter-group">
                            <label>Kategori</label>
                            <select id="fKategori" class="form-control form-control-sm">
                                <option value="">Semua Kategori</option>
                                <option value="A">A — Kinerja Sangat Baik</option>
                                <option value="B">B — Kinerja Baik</option>
                                <option value="C">C — Perlu Perhatian</option>
                                <option value="D">D — Mitra Berisiko</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-2 mb-1 filter-group">
                            <label>Wilayah</label>
                            <select id="fWilayah" class="form-control form-control-sm">
                                <option value="">Semua Wilayah</option>
                                <option>Lowokwaru</option><option>Blimbing</option>
                                <option>Kedungkandang</option><option>Sukun</option><option>Klojen</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-1 filter-group">
                            <label>Cari Toko</label>
                            <input id="fCari" type="text" class="form-control form-control-sm" placeholder="Nama toko...">
                        </div>
                        <div class="col-12 col-md-6 col-lg-1 mb-1 d-flex" style="align-items:flex-end;">
                            <button id="btnReset" class="btn btn-sm btn-outline-secondary btn-block" style="height:34px;padding:0 10px;font-weight:600;">
                                <i class="fas fa-undo-alt mr-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ TABEL RANKING ═══ --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header py-2" style="background:#faf9f2;border-bottom:1px solid #f0ede8;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size:13px;font-weight:700;color:#2c2c2a;">
                            <i class="fas fa-list-ol mr-1" style="color:var(--za);"></i>
                            Ranking Partner Performance
                        </span>
                        <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
                            <small class="text-muted" id="infoTotal" style="font-size:11px;"></small>
                            <div class="d-flex align-items-center" style="gap:6px;">
                                <small class="text-muted" style="font-size:11px;">Tampilkan</small>
                                <select id="pageSize" class="form-control form-control-sm" style="height:30px;width:72px;font-size:11px;">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm mb-0" id="partnerTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:54px;">No.</th>
                                    <th>Nama Toko</th>
                                    <th>Wilayah</th>
                                    <th class="text-center">Kategori</th>
                                    <th class="text-center" style="cursor:pointer;" onclick="sortTable('hybrid')">
                                        Skor Kinerja
                                        <i class="fas fa-sort ml-1" style="font-size:9px;color:#bbb;"></i>
                                        <i class="fas fa-info-circle text-muted ml-1"
                                           title="Skor akhir dari perhitungan sistem — menggabungkan pola penjualan, kemiripan toko, dan konsistensi transaksi."
                                           data-toggle="tooltip"></i>
                                    </th>
                                    <th class="text-center" style="cursor:pointer;" onclick="sortTable('performance')">
                                        Efisiensi Jual
                                        <i class="fas fa-sort ml-1" style="font-size:9px;color:#bbb;"></i>
                                        <i class="fas fa-info-circle text-muted ml-1"
                                           title="Persentase barang terjual dari total kiriman. Semakin tinggi semakin baik."
                                           data-toggle="tooltip"></i>
                                    </th>
                                    <th class="text-center">Total Terjual</th>
                                    <th class="text-center">Tingkat Retur</th>
                                    <th class="text-center" style="width:100px;">Detail</th>
                                </tr>
                            </thead>
                            <tbody id="tblBody">
                                <tr><td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center px-3 py-2 table-meta-shell" style="gap:8px;">
                        <small class="text-muted" id="pageInfo" style="font-size:11px;"></small>
                        <div id="tblPagination" class="d-flex align-items-center" style="gap:6px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ REKOMENDASI PENGIRIMAN ═══ --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header py-2" style="background:#faf9f2;border-bottom:1px solid #f0ede8;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:8px;">
                        <span style="font-size:13px;font-weight:700;color:#2c2c2a;">
                            <i class="fas fa-truck mr-1" style="color:var(--za);"></i>
                            Rekomendasi Distribusi Pengiriman — Periode Berikutnya
                        </span>
                        <small id="rekMeta" class="rek-head-note"></small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row" id="rekCards"></div>
                    <div id="rekDetailPanel" class="rek-detail-panel" style="display:none;">
                        <div class="rek-detail-head">
                            <div>
                                <div class="rek-detail-title" id="rekDetailTitle">Detail mitra kategori</div>
                                <div class="rek-detail-sub" id="rekDetailSub">Klik mitra untuk melihat alasan rekomendasi.</div>
                            </div>
                            <button type="button" class="rek-detail-close" id="rekDetailClose" aria-label="Tutup">&times;</button>
                        </div>
                        <div class="rek-detail-body">
                            <div class="rek-detail-list" id="rekDetailList"></div>
                            <div class="rek-detail-reason" id="rekDetailReason">
                                <div class="rek-empty">Klik mitra untuk melihat alasan rekomendasi.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════
     MODAL DETAIL MITRA — 
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:860px;">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

            {{-- ── Header: nama + meta + tombol tutup ── --}}
            <div class="md-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap:14px;">
                    <div id="mAv" class="md-avatar">TK</div>
                    <div>
                        <div class="md-name" id="mNama">-</div>
                        <div class="md-meta" id="mSub">-</div>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" style="font-size:20px;">&times;</button>
            </div>

            <div class="modal-body p-0" style="max-height:82vh;overflow-y:auto;">

                {{-- ── BAGIAN 1: Skor Kinerja (Hybrid) dalam persen ── --}}
                <div class="hybrid-hero" id="mHeroBox">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="hybrid-pct" id="mHPct">-</div>
                            <div class="hybrid-label" id="mHLabel">Skor Kinerja Mitra</div>
                        </div>
                        <div class="col">
                            <div class="hybrid-desc" id="mHDesc">-</div>
                        </div>
                    </div>
                </div>

                {{-- ── Perhitungan skor: strip visual ── --}}
                <div class="calc-strip">
                    <div style="font-size:11px;color:#888780;margin-bottom:8px;font-weight:600;">
                        <i class="fas fa-calculator mr-1"></i>
                        Cara skor ini dihitung:
                    </div>
                    <div class="calc-row">
                        <div class="calc-item">
                            <div class="calc-val" style="color:#534AB7;" id="cCBF">-</div>
                            <div class="calc-lbl">Kecocokan Profil<br>(CBF)</div>
                        </div>
                        <div class="calc-op">×</div>
                        <div class="calc-item">
                            <div class="calc-val" style="color:#5a5852;" id="cAlpha">0.5</div>
                            <div class="calc-lbl">Bobot α</div>
                        </div>
                        <div class="calc-op" style="color:#aaa;">+</div>
                        <div class="calc-item">
                            <div class="calc-val" style="color:#C58A2E;" id="cCF">-</div>
                            <div class="calc-lbl">Pola Transaksi<br>(CF)</div>
                        </div>
                        <div class="calc-op">×</div>
                        <div class="calc-item">
                            <div class="calc-val" style="color:#5a5852;" id="c1Alpha">0.5</div>
                            <div class="calc-lbl">Bobot (1-α)</div>
                        </div>
                        <div class="calc-op" style="font-size:20px;">=</div>
                        <div class="calc-item">
                            <div class="calc-val" id="cResult" style="font-size:22px;">-</div>
                            <div class="calc-lbl">Skor Akhir</div>
                        </div>
                    </div>
                    <div style="font-size:11px;color:#aaa;margin-top:10px;line-height:1.5;" id="calcNote">-</div>
                </div>

                {{-- ── BAGIAN 2: 5 KPI dalam format kartu + persen ── --}}
                <div class="md-section">
                    <div class="md-section-title">
                        <span class="icon-circle"><i class="fas fa-tachometer-alt"></i></span>
                        Rincian Indikator Kinerja (5 KPI)
                        <span style="font-size:11px;font-weight:400;color:#888780;">
                            — semua nilai ditampilkan dalam persen untuk kemudahan membaca
                        </span>
                    </div>
                    <div class="kpi-grid" id="kpiGrid"></div>
                </div>

                {{-- ── BAGIAN 3: Visualisasi — toggle Bar / Radar ── --}}
                <div class="md-section md-section-last mt-3">
                    <div class="md-section-title">
                        <span class="icon-circle"><i class="fas fa-chart-bar"></i></span>
                        Visualisasi Profil KPI
                    </div>
                    <div class="chart-toggle">
                        <div class="ctog active" id="togBar" onclick="switchChart('bar')">
                            <i class="fas fa-chart-bar mr-1"></i> Grafik Batang
                        </div>
                        <div class="ctog" id="togRadar" onclick="switchChart('radar')">
                            <i class="fas fa-chart-area mr-1"></i> Grafik Jaring
                        </div>
                    </div>
                    <div class="chart-container">
                        <div id="barChart-wrap" style="display:block;">
                            <canvas id="barKpi" height="160"></canvas>
                        </div>
                        <div id="radarChart-wrap" style="display:none; height:280px;">
                            <canvas id="radarKpi"></canvas>
                        </div>
                    </div>
                </div>

            </div>{{-- end modal-body --}}

            <div class="modal-footer py-2" style="border-top:1px solid #f0ede8;">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
window.PARTNER_PERFORMANCE_CONFIG = {
    serverData: @json($partners ?? []),
    serverMeta: @json($kpi_meta ?? []),
    dataStatus: @json($data_status ?? null),
    apiDataUrl: @json(route('analytics.partner-performance.api.data')),
    apiRecommendationUrl: @json(route('analytics.partner-performance.api.recommendations')),
    autoSyncMs: 30000
};
</script>
<script src="{{ asset('js/partner-performance.js') }}"></script>
@endpush
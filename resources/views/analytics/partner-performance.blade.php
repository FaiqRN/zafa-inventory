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
                            {{-- Tombol Export Evaluasi Excel + Dropdown Alpha --}}
                            <div class="btn-group" style="height:30px;" id="exportAlphaGroup">
                                {{-- Tombol utama: langsung download alpha=0.5 --}}
                                <a id="btnExportEvaluasi"
                                   href="{{ route('analytics.partner-performance.evaluasi.export') }}?alpha=0.5"
                                   class="btn btn-sm"
                                   style="height:30px;padding:0 10px;font-size:11px;font-weight:600;
                                          background:#1a6b3a;color:#fff;border:none;border-radius:5px 0 0 5px;
                                          display:inline-flex;align-items:center;gap:5px;
                                          text-decoration:none;white-space:nowrap;"
                                   title="Download Excel evaluasi dengan Alpha = 0.5 (default)">
                                    <i class="fas fa-file-excel" style="font-size:11px;"></i>
                                    Export Evaluasi <span style="opacity:.75;font-size:10px;">(α=0.5)</span>
                                </a>
                                {{-- Tombol panah dropdown --}}
                                <button type="button"
                                        class="btn btn-sm dropdown-toggle dropdown-toggle-split"
                                        data-toggle="dropdown"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        style="height:30px;padding:0 8px;font-size:11px;
                                               background:#145c30;color:#fff;border:none;
                                               border-left:1px solid rgba(255,255,255,.25);
                                               border-radius:0 5px 5px 0;"
                                        title="Pilih nilai Alpha lainnya">
                                    <i class="fas fa-chevron-down" style="font-size:9px;"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" style="min-width:220px;padding:6px 0;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.15);">
                                    <div style="padding:6px 14px 4px;font-size:10px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;">
                                        <i class="fas fa-sliders-h mr-1"></i> Pilih Nilai Alpha (α)
                                    </div>
                                    <div class="dropdown-divider" style="margin:4px 0;"></div>
                                    @foreach([
                                        '0.0' => ['label' => 'α = 0.0', 'desc' => 'Baseline — Murni CF (100% Collaborative Filtering)',  'color' => '#37474F', 'badge' => 'Baseline'],
                                        '0.3' => ['label' => 'α = 0.3', 'desc' => 'Hybrid — CF Lebih Dominan (70% CF + 30% CBF)',          'color' => '#1565C0', 'badge' => ''],
                                        '0.5' => ['label' => 'α = 0.5', 'desc' => 'Hybrid — Seimbang CBF & CF (50% + 50%)',                 'color' => '#1a6b3a', 'badge' => 'Default'],
                                        '0.7' => ['label' => 'α = 0.7', 'desc' => 'Hybrid — CBF Lebih Dominan (70% CBF + 30% CF)',          'color' => '#E65100', 'badge' => ''],
                                        '1.0' => ['label' => 'α = 1.0', 'desc' => 'Baseline — Murni CBF (100% Content-Based Filtering)',     'color' => '#B71C1C', 'badge' => 'Baseline'],
                                    ] as $val => $info)
                                    <a class="dropdown-item d-flex align-items-center"
                                       href="{{ route('analytics.partner-performance.evaluasi.export') }}?alpha={{ $val }}"
                                       style="padding:7px 14px;font-size:11px;gap:10px;
                                              {{ in_array($val, ['0.0','1.0']) ? 'background:#f8f9fa;' : '' }}"
                                       title="Download evaluasi_alpha{{ str_replace('.', '', $val) }}_....xlsx">
                                        <span style="width:28px;height:28px;border-radius:50%;background:{{ $info['color'] }};
                                                     display:inline-flex;align-items:center;justify-content:center;
                                                     color:#fff;font-size:9px;font-weight:700;flex-shrink:0;">
                                            {{ $val }}
                                        </span>
                                        <span style="flex:1;">
                                            <span style="font-weight:700;color:#2c2c2a;display:block;">
                                                {{ $info['label'] }}
                                                @if($info['badge'])
                                                    <span style="font-size:9px;font-weight:600;padding:1px 5px;
                                                                 border-radius:3px;margin-left:4px;
                                                                 background:{{ $info['badge']==='Baseline' ? '#eceff1' : '#e8f5e9' }};
                                                                 color:{{ $info['badge']==='Baseline' ? '#546e7a' : '#2e7d32' }}">
                                                        {{ $info['badge'] }}
                                                    </span>
                                                @endif
                                            </span>
                                            <span style="color:#888;font-size:10px;">{{ $info['desc'] }}</span>
                                        </span>
                                        <i class="fas fa-download" style="color:#bbb;font-size:10px;flex-shrink:0;"></i>
                                    </a>
                                    @endforeach
                                    <div class="dropdown-divider" style="margin:4px 0;"></div>
                                    <div style="padding:4px 14px 2px;font-size:10px;color:#aaa;line-height:1.4;">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        File: <code style="font-size:9px;">evaluasi_alpha<em>XX</em>_YYYYMMDD.xlsx</code>
                                    </div>
                                </div>
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
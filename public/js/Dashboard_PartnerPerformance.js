$(function(){

/* ═══════════════════════════════════════════════════════════
    Sinkron dengan partner-performance blade
═══════════════════════════════════════════════════════════ */
const DASHBOARD_PP_CONFIG = (window.DASHBOARD_PP_CONFIG && typeof window.DASHBOARD_PP_CONFIG === 'object')
        ? window.DASHBOARD_PP_CONFIG
        : {};
const API_DATA_URL = DASHBOARD_PP_CONFIG.apiDataUrl || '/analytics/partner-performance/api/data';
const API_TRENDS_URL = DASHBOARD_PP_CONFIG.apiTrendsUrl || '/analytics/partner-performance/api/trends';
const REPORT_INDEX_URL = DASHBOARD_PP_CONFIG.reportIndexUrl || '/analytics/partner-performance';
const TREND_HISTORY_MONTHS = 12;
const TREND_FORECAST_MONTHS = 2;
const AUTO_SYNC_MS = 30000;

let MITRA = [];
let TOTAL_ACTIVE_MITRA = 0;
let DATA_STATUS = null;
let AVG_KPI = {
    total_sales: 0,
    return_rate: 0,
    trans_freq: 0,
    sales_eff: 0,
    konsistensi: 0,
};
const KPI_LABELS = ['Volume Jual','Retur (rendah=baik)','Frek. Transaksi','Efisiensi Jual','Konsistensi'];
const KPI_KEYS_ORDER = ['total_sales', 'return_rate', 'trans_freq', 'sales_eff', 'konsistensi'];

/* Warna */
const WA = { A:'#1F7A4D', B:'#1E607F', C:'#9A6B22', D:'#A7472E' };
const BG = { A:'#E7F4ED', B:'#E7F1F6', C:'#F9F1E4', D:'#FBEDE8' };

let BULAN_LABEL = [];
let TREN_KAT = { all: [], A: [], B: [], C: [], D: [] };

let activeKat = 'all';
let showMA    = true;
let showLS    = true;
let showFC    = true;
let dashboardSyncInFlight = false;
let dataRequestSequence = 0;
let latestAppliedSequence = 0;
let latestSnapshotHash = '';
let latestSnapshotTimestamp = 0;

let donutChart, barChart, lineChart, kpiProfileChart;

function toNumber(value, fallback = 0){
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
}

function parseNumericFromRaw(rawValue, fallback = 0){
    const cleaned = String(rawValue ?? '').replace(/[^0-9,.-]/g, '').replace(/,/g, '.');
    const n = parseFloat(cleaned);
    return Number.isFinite(n) ? n : fallback;
}

function parseSnapshotTimestamp(value){
    if (!value) {
        return 0;
    }

    const normalized = String(value).replace(' ', 'T');
    const parsed = Date.parse(normalized);
    return Number.isNaN(parsed) ? 0 : parsed;
}

function extractSnapshotMeta(res){
    const snapshot = (res && res.snapshot && typeof res.snapshot === 'object') ? res.snapshot : {};
    const meta = (res && res.kpi_meta && typeof res.kpi_meta === 'object') ? res.kpi_meta : {};

    return {
        hash: String(snapshot.hash || meta.snapshot_hash || ''),
        generatedAt: snapshot.generated_at || meta.snapshot_generated_at || meta.generated_at || '',
        isStale: Boolean(snapshot.is_stale || meta.snapshot_stale || false),
    };
}

function shouldApplyDataPayload(res, requestSeq){
    if (requestSeq < latestAppliedSequence) {
        return false;
    }

    const snapshotMeta = extractSnapshotMeta(res);
    const incomingTimestamp = parseSnapshotTimestamp(snapshotMeta.generatedAt);

    if (
        latestSnapshotTimestamp > 0
        && incomingTimestamp > 0
        && incomingTimestamp < latestSnapshotTimestamp
    ) {
        return false;
    }

    if (
        latestSnapshotTimestamp > 0
        && incomingTimestamp > 0
        && incomingTimestamp === latestSnapshotTimestamp
        && latestSnapshotHash
        && snapshotMeta.hash
        && snapshotMeta.hash === latestSnapshotHash
        && requestSeq <= latestAppliedSequence
    ) {
        return false;
    }

    return true;
}

function applyDataPayload(dataRes, requestSeq){
    if (!(dataRes && dataRes.success && Array.isArray(dataRes.frontend_data))) {
        return false;
    }

    if (!shouldApplyDataPayload(dataRes, requestSeq)) {
        return false;
    }

    MITRA = normalizeFrontendRows(dataRes.frontend_data);
    DATA_STATUS = (dataRes.data_status && typeof dataRes.data_status === 'object')
        ? dataRes.data_status
        : DATA_STATUS;
    TOTAL_ACTIVE_MITRA = toNumber(
        (dataRes.kpi_meta && dataRes.kpi_meta.total_active_partners)
        || (dataRes.summary && dataRes.summary.total_active_partners),
        MITRA.length
    );
    AVG_KPI = buildAverageKpi(MITRA);

    const snapshotMeta = extractSnapshotMeta(dataRes);
    if (snapshotMeta.hash) {
        latestSnapshotHash = snapshotMeta.hash;
    }

    const incomingTimestamp = parseSnapshotTimestamp(snapshotMeta.generatedAt);
    if (incomingTimestamp > 0) {
        latestSnapshotTimestamp = incomingTimestamp;
    }

    latestAppliedSequence = Math.max(latestAppliedSequence, requestSeq);

    return true;
}

function hasScoringData(){
    return !!(DATA_STATUS && DATA_STATUS.flags && DATA_STATUS.flags.ready_for_scoring);
}

function normalizeFrontendRows(rows){
    if (!Array.isArray(rows)) return [];

    return rows.map(row => {
        const kpi = row && typeof row.kpi === 'object' && row.kpi !== null ? row.kpi : {};
        const returnRate = kpi.return_rate || {};
        const totalSales = kpi.total_sales || {};
        const hybrid = Math.max(0, Math.min(1, toNumber(row.hybrid, 0)));
        const perf = toNumber(row.performance, hybrid * 100);
        const retur = parseNumericFromRaw(returnRate.raw, 0);
        const terjual = parseInt(String(totalSales.raw || '').replace(/[^0-9]/g, ''), 10) || 0;
        const kat = ['A', 'B', 'C', 'D'].includes(row.kat) ? row.kat : 'D';

        return {
            id: String(row.id ?? row.toko_id ?? ''),
            nama: String(row.nama ?? row.nama_toko ?? '-'),
            wil: String(row.wil ?? row.wilayah ?? '-'),
            kat: kat,
            hybrid: hybrid,
            perf: perf,
            retur: retur,
            terjual: terjual,
            kpi: kpi,
        };
    }).filter(row => row.id !== '');
}

function buildAverageKpi(rows){
    const normalized = normalizeFrontendRows(rows);
    const result = {
        total_sales: 0,
        return_rate: 0,
        trans_freq: 0,
        sales_eff: 0,
        konsistensi: 0,
    };

    if (!normalized.length) {
        return result;
    }

    KPI_KEYS_ORDER.forEach((key) => {
        const values = normalized
            .map(row => {
                const kpi = row && row.kpi ? row.kpi : {};
                const metric = kpi[key] || null;
                return metric ? toNumber(metric.pct, null) : null;
            })
            .filter(value => value !== null);

        if (!values.length) {
            result[key] = 0;
            return;
        }

        result[key] = Math.round(values.reduce((sum, value) => sum + value, 0) / values.length);
    });

    return result;
}

function applyTrendData(trendResponse){
    const series = trendResponse && trendResponse.series ? trendResponse.series : {};
    const labels = Array.isArray(series.labels) ? series.labels : [];
    const futureLabels = Array.isArray(series.future_labels)
        ? series.future_labels
        : new Array(TREND_FORECAST_MONTHS).fill('').map((_, i) => `F+${i + 1}`);

    BULAN_LABEL = labels.length ? [...labels, ...futureLabels] : [...futureLabels];

    ['all', 'A', 'B', 'C', 'D'].forEach((key) => {
        const base = Array.isArray(series[key]) ? series[key].map((value) => value === null ? null : toNumber(value, 0)) : [];
        const normalizedBase = labels.length ? labels.map((_, idx) => (idx < base.length ? base[idx] : null)) : [];
        const forecastPadding = new Array(futureLabels.length).fill(null);
        TREN_KAT[key] = [...normalizedBase, ...forecastPadding];
    });

    if (!BULAN_LABEL.length) {
        BULAN_LABEL = ['F+1', 'F+2'];
        TREN_KAT = { all: [null, null], A: [null, null], B: [null, null], C: [null, null], D: [null, null] };
    }
}

function loadDashboardData(){
    if (dashboardSyncInFlight) {
        return $.Deferred().resolve().promise();
    }

    dashboardSyncInFlight = true;
    const requestSeq = ++dataRequestSequence;
    const ts = Date.now();

    const dataRequest = $.ajax({
        url: API_DATA_URL,
        method: 'GET',
        cache: false,
        data: { months: 6, _ts: ts },
    });

    const trendRequest = $.ajax({
        url: API_TRENDS_URL,
        method: 'GET',
        cache: false,
        data: { months: TREND_HISTORY_MONTHS, forecast_months: TREND_FORECAST_MONTHS, _ts: ts },
    });

    const dataDeferred = $.Deferred();
    const trendDeferred = $.Deferred();

    dataRequest.done((res) => dataDeferred.resolve(res)).fail(() => dataDeferred.resolve(null));
    trendRequest.done((res) => trendDeferred.resolve(res)).fail(() => trendDeferred.resolve(null));

    return $.when(dataDeferred, trendDeferred).done((dataRes, trendRes) => {
        let shouldRender = false;

        if (applyDataPayload(dataRes, requestSeq)) {
            shouldRender = true;
        }

        if (requestSeq >= latestAppliedSequence && trendRes && trendRes.success) {
            applyTrendData(trendRes);
            shouldRender = true;
        }

        if (shouldRender) {
            renderAll();
        }
    }).always(() => {
        dashboardSyncInFlight = false;
    });
}

/* ── Navigasi ke Report PP ── */
window.goToReport = function(kat, sortBy){
    let url = REPORT_INDEX_URL;
    const params = [];
    if (kat)    params.push('kategori='+kat);
    if (sortBy) params.push('sort='+sortBy);
    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
};

/* ═══════════════════════════════════════════════════════════
   PERHITUNGAN ANALITIK
═══════════════════════════════════════════════════════════ */
function calcMovingAverage(data, n) {
    const result = new Array(data.length).fill(null);
    for (let i = n - 1; i < data.length; i++) {
        const window = data.slice(i - n + 1, i + 1);
        if (window.some(v => v === null)) continue;
        result[i] = parseFloat((window.reduce((s,v)=>s+v,0) / n).toFixed(2));
    }
    return result;
}

function calcLeastSquare(data, totalLen) {
    const actualData = data.map((v, i) => v !== null ? { x: i + 1, y: v } : null).filter(Boolean);
    const n  = actualData.length;
    if (n < 2) {
        return Array.from({ length: totalLen }, () => null);
    }
    const sx  = actualData.reduce((s, d) => s + d.x, 0);
    const sy  = actualData.reduce((s, d) => s + d.y, 0);
    const sxy = actualData.reduce((s, d) => s + d.x * d.y, 0);
    const sx2 = actualData.reduce((s, d) => s + d.x * d.x, 0);
    const denominator = (n * sx2 - sx * sx);
    if (denominator === 0) {
        return Array.from({ length: totalLen }, () => null);
    }

    const b = (n * sxy - sx * sy) / denominator;
    const a = (sy - b * sx) / n;
    // Hasilkan seluruh garis tren (termasuk posisi forecast)
    return Array.from({ length: totalLen }, (_, i) => parseFloat((a + b * (i + 1)).toFixed(2)));
}


function calcForecast(data, lsTrend) {
    // Ambil nilai terakhir aktual sebagai titik sambung
    const lastActualIdx = data.map((v, i) => v !== null ? i : -1).filter(i => i >= 0).pop();
    if (lastActualIdx === undefined) {
        return new Array(data.length).fill(null);
    }

    const result = new Array(data.length).fill(null);
    // Titik sambung: nilai aktual terakhir
    result[lastActualIdx] = data[lastActualIdx];
    // Proyeksikan ke indeks berikutnya menggunakan tren LS
    for (let i = lastActualIdx + 1; i < data.length; i++) {
        const trendValue = lsTrend[i];
        if (trendValue === null || trendValue === undefined || Number.isNaN(trendValue)) {
            result[i] = null;
            continue;
        }

        result[i] = Math.min(100, Math.max(0, trendValue));
    }
    return result;
}

/* ═══════════════════════════════════════════════════════════
   KPI SUMMARY CARDS
═══════════════════════════════════════════════════════════ */
function renderKpiCards(){
    const totalActive = TOTAL_ACTIVE_MITRA > 0 ? TOTAL_ACTIVE_MITRA : MITRA.length;
    const totalOperational = MITRA.length;
    const canScore = hasScoringData();
    const avgH  = totalOperational > 0 ? Math.round(MITRA.reduce((s,d)=>s+d.hybrid,0)/totalOperational*100) : 0;
    const katA  = MITRA.filter(d=>d.kat==='A').length;
    const totT  = MITRA.reduce((s,d)=>s+d.terjual,0).toLocaleString('id');
    const avgR  = totalOperational > 0 ? (MITRA.reduce((s,d)=>s+d.retur,0)/totalOperational).toFixed(1) : '0.0';
    $('#kpiTotal').text(totalActive);
    $('#kpiAvgH').text(canScore ? avgH+'%' : '-');
    $('#kpiA').text(canScore ? katA : '-');
    $('#kpiSales').text(canScore ? totT : '-');
    $('#kpiRetur').text(canScore ? avgR+'%' : '-');
}

/* ═══════════════════════════════════════════════════════════
   DONUT CHART
═══════════════════════════════════════════════════════════ */
function renderDonut(){
    const canScore = hasScoringData();
    const cnt = { A:0, B:0, C:0, D:0 };
    MITRA.forEach(d=>cnt[d.kat]++);
    const totalActive = TOTAL_ACTIVE_MITRA > 0 ? TOTAL_ACTIVE_MITRA : MITRA.length;
    const totalSafe = totalActive > 0 ? totalActive : 1;
    $('#donutTotal').text(totalActive);

    if (!canScore) {
        if (donutChart) {
            donutChart.destroy();
            donutChart = null;
        }

        $('#donutLegend').empty();
        $('#donutNoData').hide().empty();
        return;
    }

    $('#donutNoData').hide();

    const legCfg = [
        { k:'A', lbl:'Kinerja Sangat Baik', desc:'Prioritas utama pengiriman' },
        { k:'B', lbl:'Kinerja Baik',         desc:'Pantau & pertahankan' },
        { k:'C', lbl:'Perlu Perhatian',       desc:'Kurangi volume kiriman' },
        { k:'D', lbl:'Berisiko',              desc:'Tahan pengiriman sementara' },
    ];
    $('#donutLegend').html(legCfg.map(c=>{
        const pct = ((cnt[c.k]/totalSafe)*100).toFixed(0);
        return `
        <div class="donut-leg-item" style="background:${BG[c.k]}; border-color:${BG[c.k]};"
             id="leg${c.k}" onclick="goToReport('${c.k}')">
            <div class="d-flex align-items-center gap-1" style="gap:6px;">
                <div class="donut-leg-dot" style="background:${WA[c.k]};"></div>
                <div class="donut-leg-name" style="color:${WA[c.k]};">Kategori ${c.k}</div>
            </div>
            <div class="donut-leg-count" style="color:${WA[c.k]};">${cnt[c.k]}</div>
            <div class="donut-leg-pct" style="color:${WA[c.k]};">${pct}% dari total mitra</div>
            <div style="font-size:10px;color:${WA[c.k]};opacity:.7;">${c.desc}</div>
        </div>`;
    }).join(''));

    if (donutChart) donutChart.destroy();
    const ctx = document.getElementById('chartDonut');
    donutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Kategori A','Kategori B','Kategori C','Kategori D'],
            datasets:[{
                data: [cnt.A, cnt.B, cnt.C, cnt.D],
                backgroundColor: [WA.A+'CC', WA.B+'CC', WA.C+'CC', WA.D+'CC'],
                hoverBackgroundColor: [WA.A, WA.B, WA.C, WA.D],
                borderWidth: 3, borderColor: '#fff',
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const pct = ((ctx.parsed/totalSafe)*100).toFixed(1);
                            return ` ${ctx.parsed} mitra — ${pct}%`;
                        },
                        title: ctx => {
                            const map = {
                                'Kategori A':'Top 25% ranking hybrid periode ini',
                                'Kategori B':'Top 26–50% ranking hybrid periode ini',
                                'Kategori C':'Top 51–75% ranking hybrid periode ini',
                                'Kategori D':'Bottom 25% ranking hybrid periode ini'
                            };
                            return map[ctx[0].label] || ctx[0].label;
                        }
                    }
                }
            },
            onClick: (evt, el) => {
                if (!el.length) return;
                goToReport(['A','B','C','D'][el[0].index]);
            }
        }
    });
}

/* ═══════════════════════════════════════════════════════════
   BAR CHART — Skor Hybrid Top 10
═══════════════════════════════════════════════════════════ */
function renderBar(){
    const canScore = hasScoringData();
    const sorted = [...MITRA].sort((a,b)=>b.hybrid-a.hybrid).slice(0,10);
    if (barChart) {
        barChart.destroy();
        barChart = null;
    }

    if (!canScore || !sorted.length) {
        $('#chartBar').hide();
        $('#barNoData').hide().empty();
        return;
    }

    $('#barNoData').hide();
    $('#chartBar').show();

    const ctx = document.getElementById('chartBar');
    barChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sorted.map(d=> d.nama.length>16 ? d.nama.substring(0,15)+'…' : d.nama),
            datasets:[{
                label: 'Skor Kinerja (%)',
                data: sorted.map(d=>Math.round(d.hybrid*100)),
                backgroundColor: sorted.map(d=>WA[d.kat]+'BB'),
                hoverBackgroundColor: sorted.map(d=>WA[d.kat]),
                borderRadius: 6, borderSkipped: false, barThickness: 22,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: ctx => sorted[ctx[0].dataIndex].nama,
                        label: ctx => {
                            const d = sorted[ctx.dataIndex];
                            const katLabel = {A:'Kinerja Sangat Baik',B:'Kinerja Baik',C:'Perlu Perhatian',D:'Berisiko'};
                            return [
                                ` Skor Hybrid: ${ctx.parsed.x}%`,
                                ` Kategori: ${d.kat} — ${katLabel[d.kat]}`,
                                ` Wilayah: ${d.wil}`,
                                ` Retur: ${d.retur}%`,
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    min: 0, max: 100,
                    grid: { color: '#F5F3EE' },
                    ticks: { font:{size:10}, color:'#888', callback: v => v+'%' },
                    title: { display: true, text:'Skor Hybrid (%)', font:{size:10}, color:'#888' }
                },
                y: { grid: { display: false }, ticks: { font:{size:11}, color:'#444' } }
            },
            onClick: (evt, el) => {
                if (!el.length) return;
                window.location.href = `${REPORT_INDEX_URL}?search=${encodeURIComponent(sorted[el[0].index].nama)}`;
            }
        }
    });
}

function renderLine(){
    const canScore = hasScoringData();
    if (!canScore) {
        if (lineChart) {
            lineChart.destroy();
            lineChart = null;
        }

        $('#chartLine').hide();
        $('#lineNoData').hide().empty();
        $('#lineSubTitle').text('');
        $('#aboxAvg').text('-');
        $('#aboxMA').text('-');
        $('#aboxSlope').text('-');
        $('#aboxForecast').text('-');
        $('#trendBadge').css({background:'#F4F2EE',color:'#6B6B66'}).find('#trendText').text('-');
        $('#trendBadge').find('i').attr('class','fas fa-hourglass-half');
        return;
    }

    $('#lineNoData').hide();
    $('#chartLine').show();
    $('#lineSubTitle').text('Rata-rata skor hybrid · Moving Average 3-bulan · Tren Least Square · Forecasting');

    const raw  = Array.isArray(TREN_KAT[activeKat]) ? TREN_KAT[activeKat] : [];
    const labels = BULAN_LABEL.length ? [...BULAN_LABEL] : ['F+1', 'F+2'];
    const allData = labels.map((_, idx) => idx < raw.length ? raw[idx] : null);
    const lastActualIdx = allData.reduce((last, v, i) => v !== null ? i : last, -1);
    const actual = lastActualIdx >= 0 ? allData.slice(0, lastActualIdx + 1) : [];

    // Hitung analitik
    const maData      = calcMovingAverage(allData, 3);
    const lsTrend     = calcLeastSquare(allData, allData.length);
    const forecastData = calcForecast(allData, lsTrend);

    // Tentukan warna berdasarkan filter aktif
    const katColor = activeKat === 'all' ? '#1F7A4D'
                   : activeKat === 'A'   ? '#1F7A4D'
                   : activeKat === 'B'   ? '#1E607F'
                   : activeKat === 'C'   ? '#9A6B22' : '#A7472E';

    // Hitung insight untuk analysis box
    const actualOnly = actual.filter(v => v !== null && Number.isFinite(v));
    const avg = actualOnly.length > 0
        ? (actualOnly.reduce((s,v)=>s+v,0)/actualOnly.length).toFixed(1)
        : '0.0';
    const lastMA  = [...maData].reverse().find(v=>v!==null);
    const trendOnly = lsTrend.filter(v => v !== null && Number.isFinite(v));
    const slopeVal = trendOnly.length >= 2
        ? (trendOnly[trendOnly.length - 1] - trendOnly[0])
        : 0;
    const slope = slopeVal.toFixed(2);
    const forecastOnly = forecastData.filter((v, idx) => idx > lastActualIdx && v !== null && Number.isFinite(v));
    const forecastEnd = forecastOnly.length > 0 ? forecastOnly[forecastOnly.length - 1] : null;

    $('#aboxAvg').text(avg+'%');
    $('#aboxMA').text(lastMA ? lastMA+'%' : '—');
    $('#aboxSlope').text((parseFloat(slope)>=0?'+':'')+slope+'%/bln');
    $('#aboxForecast').text(forecastEnd !== null ? forecastEnd.toFixed(1)+'%' : '—');

    // Badge tren
    if (slopeVal > 0.5) {
        $('#trendBadge').css({background:'#E7F4ED',color:'#1F7A4D'}).find('#trendText').text('Tren Meningkat');
        $('#trendBadge').find('i').attr('class','fas fa-arrow-trend-up');
    } else if (slopeVal < -0.5) {
        $('#trendBadge').css({background:'#FBEDE8',color:'#A7472E'}).find('#trendText').text('Tren Menurun');
        $('#trendBadge').find('i').attr('class','fas fa-arrow-trend-down');
    } else {
        $('#trendBadge').css({background:'#F4F2EE',color:'#6B6B66'}).find('#trendText').text('Tren Stabil');
        $('#trendBadge').find('i').attr('class','fas fa-minus');
    }

    // Subtitle update
    const katLabel = {all:'Semua Mitra', A:'Kategori A', B:'Kategori B', C:'Kategori C', D:'Kategori D'};
    $('#lineSubTitle').text(`Rata-rata skor hybrid ${katLabel[activeKat]} · MA-3 · Least Square · Forecasting`);

    if (lastActualIdx >= 0 && (lastActualIdx + 1) < labels.length) {
        const firstForecastLabel = labels[lastActualIdx + 1];
        const lastForecastLabel = labels[labels.length - 1];
        const forecastRange = firstForecastLabel === lastForecastLabel
            ? firstForecastLabel
            : `${firstForecastLabel}–${lastForecastLabel}`;
        $('#legendFC span').text(`Prediksi (${forecastRange})`);
    } else {
        $('#legendFC span').text('Prediksi (Forecasting)');
    }

    // Bangun datasets
    const datasets = [
        {
            label: 'Skor Aktual',
            data: allData,
            borderColor: katColor,
            backgroundColor: 'transparent',
            borderWidth: 2.5,
            tension: .3,
            pointRadius: allData.map(v => v !== null ? 4 : 0),
            pointHoverRadius: 7,
            pointBorderWidth: 2,
            pointBackgroundColor: '#fff',
            pointBorderColor: katColor,
            spanGaps: false,
            order: 1,
        }
    ];

    if (showMA) datasets.push({
        label: 'Moving Average (MA-3)',
        data: maData,
        borderColor: '#E8A020',
        backgroundColor: 'transparent',
        borderWidth: 2,
        tension: .3,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: '#E8A020',
        pointBorderColor: '#fff',
        pointBorderWidth: 1.5,
        spanGaps: false,
        order: 2,
    });

    if (showLS) datasets.push({
        label: 'Tren Least Square',
        data: lsTrend,
        borderColor: '#6B6B66',
        backgroundColor: 'transparent',
        borderWidth: 1.5,
        borderDash: [6, 4],
        tension: 0,
        pointRadius: 0,
        spanGaps: true,
        order: 3,
    });

    if (showFC) datasets.push({
        label: 'Prediksi (Forecasting)',
        data: forecastData,
        borderColor: '#1E607F',
        backgroundColor: 'rgba(30,96,127,0.08)',
        borderWidth: 2,
        borderDash: [4, 3],
        tension: .2,
        pointRadius: forecastData.map((v,i) => v !== null && allData[i] === null ? 5 : 0),
        pointHoverRadius: 7,
        pointBackgroundColor: '#1E607F',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        fill: '-1',
        spanGaps: false,
        order: 4,
    });

    if (lineChart) lineChart.destroy();
    const ctx = document.getElementById('chartLine');
    lineChart = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#1A1A18',
                    bodyColor: '#6B6B66',
                    borderColor: '#ECEAE4',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                           title: ctx => labels[ctx[0].dataIndex] +
                               (ctx[0].dataIndex > lastActualIdx ? ' (Prediksi)' : ' (Aktual)'),
                        label: ctx => {
                            const v = ctx.parsed.y;
                            if (v === null || v === undefined) return null;
                            return ` ${ctx.dataset.label}: ${v.toFixed(1)}%`;
                        },
                        afterBody: (ctx) => {
                            const idx = ctx[0].dataIndex;
                            if (idx < 2) return ['', '  MA-3 memerlukan minimal 3 data'];
                            if (idx > lastActualIdx) return ['', '  ⚠ Data ini adalah hasil prediksi'];
                            return [];
                        }
                    }
                },
                // Annotation zona forecast (opsional — perlu plugin annotation)
            },
            scales: {
                x: {
                    grid: { color: '#F3EEE2' },
                    ticks: {
                        font:{size:11}, color: (ctx) => {
                            // Beri warna berbeda untuk label forecast
                            return lastActualIdx >= 0 && ctx.index > lastActualIdx ? '#1E607F' : '#888';
                        }
                    }
                },
                y: {
                    min: 0, max: 100,
                    grid: { color: '#F2ECE0' },
                    ticks: { font:{size:10}, color:'#888', callback: v=>v+'%' },
                    title: { display:true, text:'Rata-rata Skor Hybrid (%)', font:{size:10}, color:'#888' }
                }
            },
            onClick: (evt, el) => {
                if (!el.length) return;
                const idx = el[0].index;
                if (idx > lastActualIdx) return;

                const label = labels[idx] || '';
                const monthMap = {
                    Jan: 1, Feb: 2, Mar: 3, Apr: 4, May: 5, Mei: 5, Jun: 6,
                    Jul: 7, Aug: 8, Ags: 8, Sep: 9, Oct: 10, Okt: 10, Nov: 11, Dec: 12, Des: 12,
                };
                const parts = label.split('-');
                if (parts.length !== 2) return;

                const bulan = monthMap[parts[0]] || null;
                const tahunShort = parseInt(parts[1], 10);
                if (!bulan || Number.isNaN(tahunShort)) return;

                const tahun = 2000 + tahunShort;
                window.location.href = `${REPORT_INDEX_URL}?bulan=${bulan}&tahun=${tahun}`;
            }
        }
    });
}

/* ── Toggle lapisan analitik ── */
window.toggleLayer = function(layer){
    if (layer === 'ma') {
        showMA = !showMA;
        $('#btnMA').toggleClass('active-ma');
        $('#legendMA').css('opacity', showMA ? 1 : .3);
    } else if (layer === 'ls') {
        showLS = !showLS;
        $('#btnLS').toggleClass('active-ls');
        $('#legendLS').css('opacity', showLS ? 1 : .3);
    } else if (layer === 'fc') {
        showFC = !showFC;
        $('#btnFC').toggleClass('active-fc');
        $('#legendFC').css('opacity', showFC ? 1 : .3);
    }
    renderLine();
};

/* ── Filter Kategori untuk Line Chart ── */
window.setKatFilter = function(kat){
    activeKat = kat;
    $('.month-chip').removeClass('active');
    $('#filter' + (kat === 'all' ? 'All' : kat)).addClass('active');
    renderLine();
};

/* ═══════════════════════════════════════════════════════════
   SEBARAN KINERJA PER WILAYAH
═══════════════════════════════════════════════════════════ */
function renderWilayah(){
    const map = {};
    MITRA.forEach(d=>{
        if (!map[d.wil]) map[d.wil] = { total:0, sum:0 };
        map[d.wil].total++;
        map[d.wil].sum += d.hybrid;
    });
    const arr = Object.entries(map).map(([wil,v])=>({
        wil, avg: Math.round(v.sum/v.total*100), total: v.total
    })).sort((a,b)=>b.avg-a.avg);

    if (!arr.length) {
        $('#wilayahList').html('<div class="text-center text-muted" style="font-size:12px;padding:18px 0;">Belum ada data wilayah dari backend.</div>');
        return;
    }

    const maxAvg = Math.max(...arr.map(a=>a.avg), 1);
    const barC = pct => pct>=75?WA.A:pct>=50?WA.B:pct>=25?WA.C:WA.D;

    $('#wilayahList').html(arr.map(w=>`
    <div class="wilayah-item" style="cursor:pointer;"
         title="Klik untuk melihat mitra di ${w.wil}">
        <div class="wilayah-name">${w.wil}</div>
        <div class="wilayah-bar-wrap">
            <div class="wilayah-bar-fill" style="width:${(w.avg/maxAvg*100).toFixed(0)}%;background:${barC(w.avg)};"></div>
        </div>
        <div class="wilayah-pct" style="color:${barC(w.avg)};">${w.avg}%</div>
        <div class="wilayah-cnt">${w.total} mitra</div>
    </div>`).join(''));
}

/* ═══════════════════════════════════════════════════════════
   MITRA RETUR TERTINGGI
═══════════════════════════════════════════════════════════ */
function renderRetur(){
    const sorted = [...MITRA].sort((a,b)=>b.retur-a.retur).slice(0,5);
    if (!sorted.length) {
        $('#returList').html('<div class="text-center text-muted" style="font-size:12px;padding:18px 0;">Belum ada data retur dari backend.</div>');
        return;
    }

    $('#returList').html(sorted.map(d=>{
        const col = d.retur>=40?WA.D:d.retur>=25?WA.C:WA.B;
        const bg  = d.retur>=40?BG.D:d.retur>=25?BG.C:BG.B;
        const detailUrl = `${REPORT_INDEX_URL}?search=${encodeURIComponent(d.nama)}`;
        return `
        <div class="retur-item" style="cursor:pointer;"
             onclick="window.location.href='${detailUrl}'"
             title="Klik untuk melihat detail mitra ini">
            <div>
                <div class="retur-toko">${d.nama}</div>
                <div class="retur-wil"><i class="fas fa-map-marker-alt mr-1"></i>${d.wil} · Skor: ${Math.round(d.hybrid*100)}%</div>
            </div>
            <span class="retur-pct" style="color:${col};background:${bg};">${d.retur}% retur</span>
        </div>`;
    }).join(''));
}

/* ═══════════════════════════════════════════════════════════
   HORIZONTAL BAR CHART — Profil KPI Rata-rata
═══════════════════════════════════════════════════════════ */
function renderKpiProfile(){
    if (kpiProfileChart) kpiProfileChart.destroy();
    const ctx = document.getElementById('chartKpiProfile');
    if (!ctx) return;

    const values = KPI_KEYS_ORDER.map((key) => toNumber(AVG_KPI[key], 0));
    const barColor = v => v >= 75 ? WA.A+'CC' : v >= 60 ? WA.B+'CC' : v >= 45 ? WA.C+'CC' : WA.D+'CC';

    kpiProfileChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: KPI_LABELS,
            datasets:[{
                label: 'Rata-rata KPI (%)',
                data: values,
                backgroundColor: values.map(v => barColor(v)),
                borderRadius: 6, borderSkipped: false, maxBarThickness: 14,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.x}%` }
                }
            },
            scales: {
                x: {
                    min: 0, max: 100,
                    grid: { color: '#F2ECE0' },
                    ticks: { font:{size:10}, color:'#888', callback: v => v+'%' },
                    title: { display:true, text:'Skor KPI (%)', font:{size:10}, color:'#888' }
                },
                y: { grid:{display:false}, ticks:{font:{size:10},color:'#444'} }
            },
            onClick: () => goToReport('')
        }
    });
}

/* ── Animasi stagger ── */
function stagger(){
    $('.fade-up').each(function(i){
        $(this).css({'animation-delay':(i*0.07)+'s','animation-fill-mode':'both'});
    });
}

function renderAll(){
    renderKpiCards();
    renderDonut();
    renderBar();
    renderLine();
    renderWilayah();
    renderRetur();
    renderKpiProfile();
}

$('[data-toggle="tooltip"]').tooltip();
stagger();
renderAll();

loadDashboardData();

window.setInterval(function(){
    if(document.hidden){
        return;
    }

    loadDashboardData();
}, AUTO_SYNC_MS);

});
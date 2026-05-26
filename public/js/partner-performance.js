$(function(){

/* ═══════════════════════════════════════════════════════════
    DATA PARTNER PERFORMANCE
═══════════════════════════════════════════════════════════ */
const PARTNER_PERFORMANCE_CONFIG = (window.PARTNER_PERFORMANCE_CONFIG && typeof window.PARTNER_PERFORMANCE_CONFIG === 'object')
    ? window.PARTNER_PERFORMANCE_CONFIG
    : {};
const serverData = Array.isArray(PARTNER_PERFORMANCE_CONFIG.serverData)
    ? PARTNER_PERFORMANCE_CONFIG.serverData
    : [];
const serverMeta = (PARTNER_PERFORMANCE_CONFIG.serverMeta && typeof PARTNER_PERFORMANCE_CONFIG.serverMeta === 'object')
    ? PARTNER_PERFORMANCE_CONFIG.serverMeta
    : {};
const API_DATA_URL = PARTNER_PERFORMANCE_CONFIG.apiDataUrl || '/analytics/partner-performance/api/data';
const API_REK_URL = PARTNER_PERFORMANCE_CONFIG.apiRecommendationUrl || '/analytics/partner-performance/api/recommendations';
const AUTO_SYNC_MS = Number(PARTNER_PERFORMANCE_CONFIG.autoSyncMs || 30000);
let DATA = Array.isArray(serverData) ? serverData : [];
let TOTAL_ACTIVE_MITRA = Number(serverMeta.total_active_partners || 0);
let DATA_STATUS = (PARTNER_PERFORMANCE_CONFIG.dataStatus && typeof PARTNER_PERFORMANCE_CONFIG.dataStatus === 'object')
    ? PARTNER_PERFORMANCE_CONFIG.dataStatus
    : null;

/* ── Config KPI — label ringkas untuk user non-teknis ── */
const KPI_CFG = [
    { key:'total_sales',  label:'Volume Penjualan',      icon:'fas fa-box',          color:'#1F7A4D' },
    { key:'return_rate',  label:'Retur Barang',           icon:'fas fa-undo',          color:'#A7472E', inv:true },
    { key:'trans_freq',   label:'Frekuensi Transaksi',   icon:'fas fa-receipt',       color:'#1E607F' },
    { key:'sales_eff',    label:'Efisiensi Penjualan',   icon:'fas fa-percentage',    color:'#1F7A4D' },
    { key:'konsistensi',  label:'Konsistensi',            icon:'fas fa-chart-line',    color:'#534AB7' },
];

const REK = {
    A:'Prioritas utama — tingkatkan volume pengiriman (+20–30%)',
    B:'Kirim normal — pantau perkembangan periode berikutnya',
    C:'Kurangi volume — evaluasi terlebih dahulu (−20–30%)',
    D:'Tahan pengiriman — lakukan review kerja sama segera',
};
const KAT_COL  = { A:'#1F7A4D', B:'#1E607F', C:'#9A6B22', D:'#A7472E' };
const KAT_BG   = { A:'#E7F4ED', B:'#E7F1F6', C:'#F9F1E4', D:'#FBEDE8' };
const KAT_DESC = {
    A:'Mitra ini berada di posisi terbaik. Kinerja penjualan sangat baik, retur rendah, dan transaksi konsisten.',
    B:'Kinerja mitra ini baik dan cukup stabil. Masih ada ruang untuk ditingkatkan agar masuk kategori terbaik.',
    C:'Kinerja mitra ini mulai menurun. Beberapa indikator perlu diperhatikan agar tidak turun lebih jauh.',
    D:'Mitra ini menunjukkan kinerja yang mengkhawatirkan. Pengiriman perlu ditahan sambil dilakukan evaluasi.',
};
const KAT_META = {
    A:{ icon:'fas fa-medal',              short:'Sangat Baik', title:'Kategori A - Kinerja sangat baik, prioritas distribusi' },
    B:{ icon:'fas fa-thumbs-up',          short:'Baik',        title:'Kategori B - Kinerja baik dan cukup stabil' },
    C:{ icon:'fas fa-exclamation-circle', short:'Pantau',      title:'Kategori C - Perlu perhatian dan evaluasi berkala' },
    D:{ icon:'fas fa-shield-alt',         short:'Risiko',      title:'Kategori D - Mitra berisiko, perlu review pengiriman' },
};

let filtered = [...DATA];
let sortCol = 'hybrid', sortOrd = 'desc';
let pageSize = 10, currentPage = 1;
let barInst = null, radarInst = null;
let alpha = Number(serverMeta.alpha || 0.5), beta = Number(serverMeta.beta || 0.5);
let syncInFlight = false;
let dataRequestSequence = 0;
let latestAppliedSequence = 0;
let latestSnapshotHash = String(serverMeta.snapshot_hash || '');
let latestSnapshotTimestamp = parseSnapshotTimestamp(
    serverMeta.snapshot_generated_at || serverMeta.generated_at || ''
);
let rekVisibleIdsByCategory = {};
let rekCache = {};
let activeRekCategory = '';
let activeRekPartnerId = '';

function parseSnapshotTimestamp(value){
    if(!value){
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

function shouldApplyPayload(res, requestSeq){
    if(requestSeq < latestAppliedSequence){
        return false;
    }

    const snapshotMeta = extractSnapshotMeta(res);
    const incomingTimestamp = parseSnapshotTimestamp(snapshotMeta.generatedAt);

    if(latestSnapshotTimestamp > 0 && incomingTimestamp > 0 && incomingTimestamp < latestSnapshotTimestamp){
        return false;
    }

    return true;
}

function applyBackendPayload(res, options = {}){
    if(!(res && res.success && Array.isArray(res.frontend_data))){
        return false;
    }

    const requestSeq = Number(options.requestSeq || 0);
    if(!shouldApplyPayload(res, requestSeq)){
        return false;
    }

    const activeFromMeta = Number((res.kpi_meta && res.kpi_meta.total_active_partners) || 0);
    const activeFromSummary = Number((res.summary && res.summary.total_active_partners) || 0);
    TOTAL_ACTIVE_MITRA = activeFromMeta > 0 ? activeFromMeta : activeFromSummary;
    DATA_STATUS = (res.data_status && typeof res.data_status === 'object') ? res.data_status : DATA_STATUS;
    alpha = Number((res.kpi_meta && res.kpi_meta.alpha) || alpha || 0.5);
    beta = Number((res.kpi_meta && res.kpi_meta.beta) || beta || 0.5);
    DATA = res.frontend_data;

    const snapshotMeta = extractSnapshotMeta(res);
    if(snapshotMeta.hash && snapshotMeta.hash !== latestSnapshotHash){
        rekCache = {};
    }
    if(snapshotMeta.hash){
        latestSnapshotHash = snapshotMeta.hash;
    }

    const incomingTimestamp = parseSnapshotTimestamp(snapshotMeta.generatedAt);
    if(incomingTimestamp > 0){
        latestSnapshotTimestamp = incomingTimestamp;
    }

    latestAppliedSequence = Math.max(latestAppliedSequence, requestSeq);

    refreshWilayahFilterOptions();

    if(options.preservePage){
        applyFilters({ preservePage: true });
    } else {
        filtered = [...DATA];
        currentPage = 1;
        renderAll(filtered);
    }

    return true;
}

function hasScoringData(){
    return !!(DATA_STATUS && DATA_STATUS.flags && DATA_STATUS.flags.ready_for_scoring);
}

function refreshWilayahFilterOptions(){
    const wilSelect = $('#fWilayah');
    if(!wilSelect.length){
        return;
    }

    const currentValue = wilSelect.val() || '';
    const wilayahList = Array.from(new Set(
        (Array.isArray(DATA) ? DATA : [])
            .map(d => String(d && d.wil ? d.wil : '').trim())
            .filter(Boolean)
    )).sort((a, b) => a.localeCompare(b, 'id'));

    wilSelect.empty();
    wilSelect.append($('<option>', { value: '', text: 'Semua Wilayah' }));

    wilayahList.forEach((wilayah) => {
        wilSelect.append($('<option>', { value: wilayah, text: wilayah }));
    });

    if (currentValue && wilayahList.includes(currentValue)) {
        wilSelect.val(currentValue);
    } else {
        wilSelect.val('');
    }
}

/* ══════════════════════════════════════════════════════════
   RENDER TABEL RANKING — kolom baru tanpa insight/rekomendasi
══════════════════════════════════════════════════════════ */
function renderTable(data){
    const sorted = [...data].sort((a, b) => {
        if (sortCol === 'hybrid' && sortOrd === 'desc') {
            return (Number(a.rank) || 0) - (Number(b.rank) || 0);
        }

        return sortOrd === 'desc'
            ? b[sortCol] - a[sortCol]
            : a[sortCol] - b[sortCol];
    });
    const totalRows = sorted.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    if(currentPage > totalPages) currentPage = totalPages;
    const startIdx = totalRows ? ((currentPage - 1) * pageSize) : 0;
    const paged = sorted.slice(startIdx, startIdx + pageSize);

    $('#infoTotal').text('');
    if(!sorted.length){
        const emptyText = '&nbsp;';
        $('#tblBody').html(`<tr><td colspan="9" class="text-center py-4 text-muted">${emptyText}</td></tr>`);
        $('#pageInfo').text('Menampilkan 0 dari 0 mitra');
        $('#tblPagination').html('');
        return;
    }

    const startHuman = startIdx + 1;
    const endHuman = Math.min(startIdx + pageSize, totalRows);
    $('#pageInfo').text(`Menampilkan ${startHuman}–${endHuman} dari ${totalRows} mitra`);

    const prevDisabled = currentPage===1 ? 'disabled' : '';
    const nextDisabled = currentPage===totalPages ? 'disabled' : '';
    let pageBtns = `<button class="table-page-btn" data-page="${currentPage-1}" ${prevDisabled}><i class="fas fa-chevron-left"></i></button>`;

    const span = 2;
    const pStart = Math.max(1, currentPage - span);
    const pEnd = Math.min(totalPages, currentPage + span);
    for(let p = pStart; p <= pEnd; p++){
        pageBtns += `<button class="table-page-btn ${p===currentPage?'active':''}" data-page="${p}">${p}</button>`;
    }
    pageBtns += `<button class="table-page-btn" data-page="${currentPage+1}" ${nextDisabled}><i class="fas fa-chevron-right"></i></button>`;
    $('#tblPagination').html(pageBtns);

    const PC = { high:'#1F7A4D', mid:'#1E607F', warn:'#C58A2E', low:'#A7472E' };
    $('#tblBody').html(paged.map((d,i)=>{
        const nama = String((d && d.nama) || '-');
        const wilayah = String((d && d.wil) || '-');
        const k = ['A', 'B', 'C', 'D'].includes(String((d && d.kat) || '')) ? String(d.kat) : 'D';
        const kMeta = KAT_META[k] || KAT_META.D;
        const hybridVal = Number((d && d.hybrid) || 0);
        const performanceVal = Number((d && d.performance) || 0);
        const hPct = Math.round(Math.max(0, Math.min(1, hybridVal)) * 100);
        const hCol = hybridVal>=0.75?PC.high:hybridVal>=0.5?PC.mid:hybridVal>=0.25?PC.warn:PC.low;
        const ePct = Number.isFinite(performanceVal) ? performanceVal.toFixed(1) : '0.0';
        const eCol = performanceVal>=75?PC.high:performanceVal>=50?PC.mid:PC.low;
        const rPct = parseFloat((((d || {}).kpi || {}).return_rate || {}).raw) || 0;
        const rCol = rPct<=15?PC.high:rPct<=30?PC.warn:PC.low;
        const tSales = ((((d || {}).kpi || {}).total_sales || {}).raw || '-');
        const partnerId = String((d && d.id) || '');
        return `
        <tr class="partner-row">
            <td class="text-center">${startIdx+i+1}</td>
            <td style="font-weight:600;">${nama}</td>
            <td><span class="text-muted" style="font-size:12px;">${wilayah}</span></td>
            <td class="text-center">
                <span class="kat-chip kat-chip-${k.toLowerCase()}" data-toggle="tooltip" title="${kMeta.title}">
                    <span class="kat-chip-badge">${k}</span>
                    <i class="${kMeta.icon} kat-chip-icon"></i>
                    <span class="kat-chip-label">${kMeta.short}</span>
                </span>
            </td>
            <td class="text-center">
                <div class="d-inline-flex align-items-center" style="gap:5px;">
                    <div class="prog-wrap"><div class="prog-fill" style="width:${hPct}%;background:${hCol};"></div></div>
                    <strong style="font-size:13px;color:${hCol};min-width:36px;">${hPct}%</strong>
                </div>
            </td>
            <td class="text-center">
                <strong style="font-size:13px;color:${eCol};">${ePct}%</strong>
                <div style="font-size:10px;color:#aaa;">terjual/kirim</div>
            </td>
            <td class="text-center">
                <span style="font-size:12px;font-weight:600;color:#1a1a18;">${tSales}</span>
            </td>
            <td class="text-center">
                <span style="font-size:13px;font-weight:600;color:${rCol};">${rPct}%</span>
            </td>
            <td class="text-center">
                ${partnerId
                    ? `<button class="btn btn-outline-warning btn-sm btn-detail" data-id="${partnerId}" style="font-size:11px;padding:4px 12px;border-radius:8px;font-weight:600;">
                        <i class="fas fa-eye mr-1"></i>Detail
                    </button>`
                    : '<span class="text-muted">-</span>'}
            </td>
        </tr>`;
    }).join(''));
    $('[data-toggle="tooltip"]').tooltip();
}

/* ══════════════════════════════════════════════════════════
   RENDER REKOMENDASI PENGIRIMAN
══════════════════════════════════════════════════════════ */
function renderRek(data){
    if(!hasScoringData()){
        $('#rekMeta').html('');
        $('#rekCards').html('');
        rekVisibleIdsByCategory = {};
        activeRekCategory = '';
        activeRekPartnerId = '';
        hideRekDetailPanel();
        return;
    }

    const cfg=[
        {k:'A',icon:'fas fa-medal',       head:'Sangat Baik', aksi:'Kirim 100–120% dari volume rata-rata', desc:'Mitra paling siap untuk dorongan distribusi lebih tinggi.'},
        {k:'B',icon:'fas fa-thumbs-up',   head:'Baik',        aksi:'Kirim pada volume normal (100%)',       desc:'Pertahankan alokasi, lalu pantau indikator yang mulai turun.'},
        {k:'C',icon:'fas fa-search',      head:'Perhatian',   aksi:'Kurangi 20–30% dan evaluasi akar masalah', desc:'Fokus perbaikan pada retur dan konsistensi transaksi.'},
        {k:'D',icon:'fas fa-hand-paper',  head:'Berisiko',    aksi:'Tahan pengiriman dan lakukan review mitra', desc:'Prioritas mitigasi untuk menghindari kerugian stok.'},
    ];
    const total = data.length;
    rekVisibleIdsByCategory = {};
    const updatedAt = new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    $('#rekMeta').html(`<span class="rek-live-dot"></span> Berbasis kategori mitra aktif · ${total} mitra · update ${updatedAt} · klik kartu untuk detail`);

    $('#rekCards').html(cfg.map(c=>{
        const rows = data.filter(d=>d.kat===c.k);
        const cnt = rows.length;
        const share = total ? Math.round((cnt/total)*100) : 0;
        const avgHybrid = rows.length
            ? Math.round((rows.reduce((sum,row)=>sum+row.hybrid,0)/rows.length)*100)
            : 0;
        rekVisibleIdsByCategory[c.k] = rows.map(row => String(row && row.id ? row.id : '')).filter(Boolean);
        const isActive = activeRekCategory === c.k ? ' active' : '';
        const pressed = activeRekCategory === c.k ? 'true' : 'false';
        return `<div class="col-xl-3 col-md-6 col-12 mb-2">
            <div class="rcard rcard-${c.k.toLowerCase()} rek-card${isActive}" data-kat="${c.k}" role="button" tabindex="0" aria-pressed="${pressed}">
                <div class="rcard-top">
                    <div>
                        <div class="rcard-kat" style="color:${KAT_COL[c.k]};">
                            <span class="rcard-kmark" style="background:${KAT_COL[c.k]};">${c.k}</span>
                            <i class="${c.icon}"></i> ${c.head}
                        </div>
                        <div class="rcard-title">Rekomendasi Kategori ${c.k}</div>
                    </div>
                    <div class="rcard-ring" style="--ring-color:${KAT_COL[c.k]};--ring-pct:${share};">
                        <span>${share}%</span>
                    </div>
                </div>

                <div class="rcard-main" style="color:${KAT_COL[c.k]};">
                    <div class="val">${cnt} <small>mitra</small></div>
                    <div class="sub">Rata-rata skor kinerja ${avgHybrid}%</div>
                </div>

                <div class="rcard-bar"><div class="rcard-bar-fill" style="width:${share}%;background:${KAT_COL[c.k]};"></div></div>
                <div class="rcard-action" style="color:${KAT_COL[c.k]};"><i class="fas fa-route mr-1"></i>${c.aksi}</div>
                <div class="rcard-desc" style="color:${KAT_COL[c.k]};">${c.desc}</div>

                <div class="rcard-tags" style="color:${KAT_COL[c.k]};">
                    <span class="rcard-tag"><i class="fas fa-layer-group mr-1"></i>Data kategori ${c.k}</span>
                    <span class="rcard-tag"><i class="fas fa-chart-pie mr-1"></i>${share}% porsi mitra</span>
                </div>
            </div>
        </div>`;
    }).join(''));

    if(activeRekCategory && rekCache[activeRekCategory]){
        renderRekDetailPanel(rekCache[activeRekCategory], { preservePartner: true });
    }
}

function setActiveRekCard(category){
    $('.rek-card').removeClass('active').attr('aria-pressed', 'false');
    if(!category){
        return;
    }

    $(`.rek-card[data-kat="${category}"]`).addClass('active').attr('aria-pressed', 'true');
}

function hideRekDetailPanel(){
    $('#rekDetailPanel').hide();
}

function showRekDetailLoading(category){
    const label = (KAT_META[category] && KAT_META[category].short) ? KAT_META[category].short : category;
    $('#rekDetailTitle').text(`Detail mitra kategori ${category} - ${label}`);
    $('#rekDetailSub').text('Memuat data mitra...');
    $('#rekDetailList').html('<div class="rek-empty">Memuat daftar mitra...</div>');
    $('#rekDetailReason').html('<div class="rek-empty">Pilih mitra untuk melihat alasan rekomendasi.</div>');
    $('#rekDetailPanel').show();
}

function renderRekDetailPanel(payload, options = {}){
    const category = String(payload && payload.category ? payload.category : '').toUpperCase();
    const label = payload && payload.category_label
        ? payload.category_label
        : ((KAT_META[category] && KAT_META[category].short) ? KAT_META[category].short : category);
    let partners = Array.isArray(payload && payload.partners) ? payload.partners : [];

    const visibleIds = new Set(rekVisibleIdsByCategory[category] || []);
    if(visibleIds.size){
        partners = partners.filter(p => visibleIds.has(String(p && p.id ? p.id : '')));
    }

    $('#rekDetailTitle').text(`Detail mitra kategori ${category} - ${label}`);
    $('#rekDetailSub').text(`${partners.length} mitra - klik mitra untuk lihat alasan rekomendasi`);
    $('#rekDetailPanel').show();

    if(!partners.length){
        $('#rekDetailList').html('<div class="rek-empty">Belum ada mitra pada kategori ini.</div>');
        $('#rekDetailReason').html('<div class="rek-empty">Tidak ada alasan rekomendasi yang bisa ditampilkan.</div>');
        return;
    }

    const preservePartner = !!options.preservePartner;
    const hasSelected = preservePartner && partners.some(p => String(p.id) === String(activeRekPartnerId));
    const selectedId = hasSelected ? String(activeRekPartnerId) : String(partners[0].id);
    activeRekPartnerId = selectedId;

    $('#rekDetailList').html(partners.map((partner, idx) => {
        const score = partner.score || {};
        const hybridPct = Number.isFinite(score.hybrid_pct)
            ? score.hybrid_pct
            : Math.round(((partner.hybrid || 0) * 100));
        const kpi = partner.kpi || {};
        const sales = kpi.total_sales && kpi.total_sales.raw ? kpi.total_sales.raw : '-';
        const retur = kpi.return_rate && kpi.return_rate.raw ? kpi.return_rate.raw : '-';
        const freq = kpi.trans_freq && kpi.trans_freq.raw ? kpi.trans_freq.raw : '-';
        const isActive = String(partner.id) === selectedId ? ' active' : '';

        return `
        <div class="rek-item${isActive}" data-id="${partner.id}">
            <div class="rek-item-top">
                <div>
                    <div class="rek-item-title">#${idx + 1} ${partner.nama || '-'}</div>
                    <div class="rek-item-meta">${partner.wil || '-'}</div>
                </div>
                <div class="rek-item-score">${hybridPct}%<span>hybrid</span></div>
            </div>
            <div class="rek-item-tags">
                <span class="rek-chip">Sales ${sales}</span>
                <span class="rek-chip">Retur ${retur}</span>
                <span class="rek-chip">Freq ${freq}</span>
            </div>
        </div>`;
    }).join(''));

    const selectedPartner = partners.find(p => String(p.id) === selectedId) || partners[0];
    renderRekReason(selectedPartner);
}

function renderRekReason(partner){
    if(!partner){
        $('#rekDetailReason').html('<div class="rek-empty">Pilih mitra untuk melihat alasan rekomendasi.</div>');
        return;
    }

    const reason = partner.reason || {};
    const details = Array.isArray(reason.details) ? reason.details : [];
    const score = partner.score || {};

    const hybridPct = Number.isFinite(score.hybrid_pct)
        ? score.hybrid_pct
        : Math.round(((partner.hybrid || 0) * 100));
    const cbfPct = Number.isFinite(score.cbf_pct)
        ? score.cbf_pct
        : Math.round(((partner.cbf || 0) * 100));
    const cfPct = Number.isFinite(score.cf_pct)
        ? score.cf_pct
        : Math.round(((partner.cf || 0) * 100));
    const userPct = Number.isFinite(score.cf_user_pct)
        ? score.cf_user_pct
        : Math.round(((partner.user || 0) * 100));
    const itemPct = Number.isFinite(score.cf_item_pct)
        ? score.cf_item_pct
        : Math.round(((partner.item || 0) * 100));

    const summary = reason.summary || 'Alasan rekomendasi belum tersedia.';
    const detailHtml = details.length
        ? `<ul class="rek-reason-list">${details.map(text => `<li>${text}</li>`).join('')}</ul>`
        : '';

    const shareCbf = Number.isFinite(Number(reason.cbf_share)) ? Number(reason.cbf_share) : null;
    const shareCf = Number.isFinite(Number(reason.cf_share)) ? Number(reason.cf_share) : null;
    const shareUser = Number.isFinite(Number(reason.cf_user_share)) ? Number(reason.cf_user_share) : null;
    const shareItem = Number.isFinite(Number(reason.cf_item_share)) ? Number(reason.cf_item_share) : null;

    const shareHtml = (shareCbf !== null && shareCf !== null)
        ? `
            <div class="rek-reason-chips">
                <span class="rek-chip">Kontribusi CBF ${shareCbf}%</span>
                <span class="rek-chip">Kontribusi CF ${shareCf}%</span>
                <span class="rek-chip">CF User ${shareUser}%</span>
                <span class="rek-chip">CF Item ${shareItem}%</span>
            </div>`
        : '';

    $('#rekDetailReason').html(`
        <div class="rek-reason-title">Alasan rekomendasi - ${partner.nama || '-'}</div>
        <div class="rek-reason-summary">${summary}</div>
        ${detailHtml}
        <div class="rek-reason-metrics">
            <div class="rek-metric">
                <div class="rek-metric-label">Hybrid</div>
                <div class="rek-metric-val">${hybridPct}%</div>
            </div>
            <div class="rek-metric">
                <div class="rek-metric-label">CBF</div>
                <div class="rek-metric-val">${cbfPct}%</div>
            </div>
            <div class="rek-metric">
                <div class="rek-metric-label">CF</div>
                <div class="rek-metric-val">${cfPct}%</div>
            </div>
            <div class="rek-metric">
                <div class="rek-metric-label">CF User</div>
                <div class="rek-metric-val">${userPct}%</div>
            </div>
            <div class="rek-metric">
                <div class="rek-metric-label">CF Item</div>
                <div class="rek-metric-val">${itemPct}%</div>
            </div>
        </div>
        ${shareHtml}
    `);
}

function openRekDetail(category){
    if(!category){
        return;
    }

    activeRekCategory = category;
    setActiveRekCard(category);

    if(rekCache[category]){
        renderRekDetailPanel(rekCache[category]);
        return;
    }

    showRekDetailLoading(category);
    const requestedCategory = category;

    $.ajax({
        url: API_REK_URL,
        method: 'GET',
        cache: false,
        data: { category: requestedCategory, months: 6, _ts: Date.now() },
        success: function(res){
            if(activeRekCategory !== requestedCategory){
                return;
            }

            if(res && res.success){
                rekCache[requestedCategory] = res;
                renderRekDetailPanel(res);
                return;
            }

            const message = res && res.message
                ? res.message
                : 'Data rekomendasi belum tersedia.';
            $('#rekDetailList').html(`<div class="rek-empty">${message}</div>`);
        },
        error: function(xhr){
            if(activeRekCategory !== requestedCategory){
                return;
            }

            const message = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Gagal memuat detail rekomendasi.';
            $('#rekDetailList').html(`<div class="rek-empty">${message}</div>`);
        }
    });
}

$(document).on('click', '.rek-card', function(){
    const category = String($(this).data('kat') || '').toUpperCase();
    openRekDetail(category);
});

$(document).on('keydown', '.rek-card', function(e){
    if(e.key === 'Enter' || e.key === ' '){
        e.preventDefault();
        const category = String($(this).data('kat') || '').toUpperCase();
        openRekDetail(category);
    }
});

$(document).on('click', '.rek-item', function(){
    const selectedId = String($(this).data('id') || '');
    if(!selectedId || !activeRekCategory || !rekCache[activeRekCategory]){
        return;
    }

    const partners = Array.isArray(rekCache[activeRekCategory].partners)
        ? rekCache[activeRekCategory].partners
        : [];
    const partner = partners.find(p => String(p && p.id ? p.id : '') === selectedId);
    if(!partner){
        return;
    }

    activeRekPartnerId = selectedId;
    $('.rek-item').removeClass('active');
    $(this).addClass('active');
    renderRekReason(partner);
});

$(document).on('click', '#rekDetailClose', function(){
    activeRekCategory = '';
    activeRekPartnerId = '';
    setActiveRekCard('');
    hideRekDetailPanel();
});

/* ══════════════════════════════════════════════════════════
   MODAL DETAIL — klik tombol Detail
══════════════════════════════════════════════════════════ */
$(document).on('click','.btn-detail',function(){
    const d   = DATA.find(x=>x.id===$(this).data('id'));
    if(!d)return;
    const k   = d.kat;
    const col = KAT_COL[k];
    const bg  = KAT_BG[k];

    /* ── Header ── */
    const ini = d.nama.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
    $('#mNama').text(d.nama);
    $('#mSub').text(d.wil + ' · ' + d.id);
    $('#mAv').text(ini).css({background:bg, border:'2px solid '+col, color:col});

    /* ── Skor Hybrid dalam persen ── */
    const hPct = Math.round(d.hybrid*100);
    $('#mHeroBox').css({background:bg, 'border-color':col, color:col});
    $('#mHPct').text(hPct+'%');
    $('#mHLabel').text('Skor Kinerja Mitra — Kategori '+k);
    $('#mHDesc').text(KAT_DESC[k]);

    /* ── Perhitungan strip ── */
    const cfMix   = (beta*d.user)+((1-beta)*d.item);
    const hCalc   = (alpha*d.cbf)+((1-alpha)*cfMix);
    const cbfPct  = Math.round(d.cbf*100)+'%';
    const cfPct   = Math.round(cfMix*100)+'%';
    const resPct  = Math.round(hCalc*100)+'%';
    $('#cCBF').text(cbfPct);
    $('#cCF').text(cfPct);
    $('#cResult').text(resPct).css('color',col);
    $('#cAlpha').text(alpha.toFixed(1));
    $('#c1Alpha').text((1-alpha).toFixed(1));
    $('#calcNote').html(
        'Sistem menggabungkan dua pendekatan: <strong>kecocokan profil toko</strong> (seberapa mirip KPI toko ini dengan toko-toko lain) ' +
        'dan <strong>pola transaksi</strong> (seberapa baik rekam jejak penjualan dan distribusi produk). ' +
        'Hasilnya dikombinasikan menggunakan bobot hybrid yang aktif pada periode perhitungan ini.'
    );

    /* ── 5 KPI dalam bentuk kartu + persen ── */
    function starRating(pct){
        const stars = pct>=80?5:pct>=60?4:pct>=40?3:pct>=20?2:1;
        return Array.from({length:5},(_, i)=>
            `<i class="${i<stars?'fas':'far'} fa-star kpi-star"></i>`
        ).join('');
    }
    function kpiLabel(pct){ return pct>=80?'Sangat Baik':pct>=60?'Baik':pct>=40?'Cukup':pct>=20?'Perlu Perbaikan':'Kritis'; }
    const kBarCol = p=>p>=75?'#1F7A4D':p>=50?'#1E607F':p>=30?'#C58A2E':'#A7472E';

    $('#kpiGrid').html(KPI_CFG.map(cfg=>{
        const kpi  = d.kpi[cfg.key];
        const pct  = kpi.pct;
        const bCol = kBarCol(pct);
        const noteInv = cfg.inv ? '<small style="font-size:9px;color:#aaa;">(lebih rendah = lebih baik)</small>' : '';
        return `
        <div class="kpi-card" style="color:${bCol};">
            <div class="kpi-card-icon"><i class="${cfg.icon}" style="color:${bCol};"></i></div>
            <div class="kpi-card-label">${cfg.label}</div>
            <div class="kpi-card-val" style="color:${bCol};">${pct}%</div>
            <div class="kpi-card-sub">${kpi.raw} ${noteInv}</div>
            <div style="margin-top:5px;">${starRating(pct)}</div>
            <div class="kpi-bar-mini"><div class="kpi-bar-mini-fill" style="width:${pct}%;background:${bCol};"></div></div>
        </div>`;
    }).join(''));

    /* ── Chart default: Bar ── */
    if(barInst){barInst.destroy();barInst=null;}
    if(radarInst){radarInst.destroy();radarInst=null;}
    $('#barChart-wrap').show(); $('#radarChart-wrap').hide();
    $('#togBar').addClass('active'); $('#togRadar').removeClass('active');

    setTimeout(()=>{
        const labels = KPI_CFG.map(c=>c.label);
        const vals   = KPI_CFG.map(c=>d.kpi[c.key].pct);
        const avgKpi = vals.length ? Math.round(vals.reduce((sum,v)=>sum+v,0)/vals.length) : 0;
        const gaBlue = '#1a73e8';
        const gaBlueSoft = 'rgba(26,115,232,.22)';
        const gaGreen = '#34a853';

        /* Bar Chart */
        const ctx1=document.getElementById('barKpi');
        if(ctx1){
            barInst=new Chart(ctx1,{
                type:'bar',
                data:{
                    labels:labels,
                    datasets:[
                        {
                            label:'Skor KPI (%)',
                            data:vals,
                            backgroundColor:gaBlueSoft,
                            borderColor:gaBlue,
                            borderWidth:1.5,
                            borderRadius:4,
                            hoverBackgroundColor:'rgba(26,115,232,.32)',
                            maxBarThickness:34,
                        },
                        {
                            type:'line',
                            label:'Rata-rata KPI',
                            data:vals.map(()=>avgKpi),
                            borderColor:gaGreen,
                            borderWidth:1.5,
                            borderDash:[6,4],
                            pointRadius:0,
                            fill:false,
                        }
                    ]
                },
                options:{
                    responsive:true, maintainAspectRatio:false,
                    interaction:{mode:'index',intersect:false},
                    plugins:{
                        legend:{
                            display:true,
                            position:'top',
                            labels:{
                                boxWidth:10,
                                usePointStyle:true,
                                color:'#5f6368',
                                font:{size:10,weight:'600'}
                            }
                        },
                        tooltip:{
                            backgroundColor:'#fff',
                            titleColor:'#202124',
                            bodyColor:'#202124',
                            borderColor:'#dadce0',
                            borderWidth:1,
                            padding:10,
                            callbacks:{
                                label:c=>c.dataset.type==='line'
                                    ? `${c.dataset.label}: ${c.parsed.y}%`
                                    : `Skor: ${c.parsed.y}% — ${kpiLabel(c.parsed.y)}`
                            }
                        }
                    },
                    scales:{
                        y:{
                            min:0,
                            max:100,
                            grid:{color:'#e8eaed'},
                            ticks:{stepSize:20,font:{size:10},color:'#5f6368',callback:v=>v+'%'},
                            border:{display:false}
                        },
                        x:{
                            grid:{display:false},
                            ticks:{font:{size:10},color:'#5f6368',maxRotation:18,minRotation:0},
                            border:{display:false}
                        }
                    }
                }
            });
        }

        /* Radar Chart (pre-build, hidden) */
        const ctx2=document.getElementById('radarKpi');
        if(ctx2){
            radarInst=new Chart(ctx2,{
                type:'radar',
                data:{
                    labels:KPI_CFG.map(c=>c.label),
                    datasets:[{
                        label:'Skor KPI',
                        data:vals,
                        backgroundColor:col+'22',borderColor:col,
                        borderWidth:2,pointBackgroundColor:col,pointRadius:4,
                    }]
                },
                options:{
                    responsive:true,maintainAspectRatio:false,
                    scales:{r:{min:0,max:100,
                        ticks:{stepSize:25,font:{size:9},color:'#888780',
                               backdropColor:'transparent',callback:v=>v+'%'},
                        pointLabels:{font:{size:10},color:'#444'},
                        grid:{color:'#f0ede8'},angleLines:{color:'#f0ede8'}}},
                    plugins:{legend:{display:false},
                        tooltip:{callbacks:{label:c=>c.parsed.r+'% — '+kpiLabel(c.parsed.r)}}}
                }
            });
        }
    },250);

    $('#modalDetail').modal('show');
});

/* ── Toggle Bar / Radar ── */
window.switchChart = function(type){
    if(type==='bar'){
        $('#barChart-wrap').show(); $('#radarChart-wrap').hide();
        $('#togBar').addClass('active'); $('#togRadar').removeClass('active');
    } else {
        $('#barChart-wrap').hide(); $('#radarChart-wrap').show();
        $('#togBar').removeClass('active'); $('#togRadar').addClass('active');
    }
};

/* ── Sort ── */
window.sortTable = function(col){
    if(sortCol===col) sortOrd=sortOrd==='desc'?'asc':'desc';
    else{sortCol=col; sortOrd='desc';}
    currentPage = 1;
    renderTable(filtered);
};

/* ── Filter ── */
function applyFilters(options = {}){
    const preservePage = !!options.preservePage;
    const previousPage = currentPage;
    const kat = $('#fKategori').val();
    const wil = $('#fWilayah').val();
    const cari = ($('#fCari').val() || '').toLowerCase().trim();

    filtered = DATA.filter((d) => {
        const nama = String((d && d.nama) || '').toLowerCase();
        const kategori = String((d && d.kat) || '');
        const wilayah = String((d && d.wil) || '');

        return (!kat || kategori === kat)
            && (!wil || wilayah === wil)
            && (!cari || nama.includes(cari));
    });

    if (preservePage) {
        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        currentPage = Math.min(Math.max(1, previousPage), totalPages);
    } else {
        currentPage = 1;
    }

    renderAll(filtered);
}

$('#fKategori,#fWilayah,#fBulan,#fTahun').on('change',applyFilters);
$('#fCari').on('input',applyFilters);
$('#pageSize').on('change',function(){
    pageSize = parseInt($(this).val(),10) || 10;
    currentPage = 1;
    renderTable(filtered);
});
$(document).on('click', '.table-page-btn', function(){
    const target = parseInt($(this).data('page'), 10);
    if(!target || Number.isNaN(target) || target===currentPage) return;
    currentPage = target;
    renderTable(filtered);
});
$('#btnReset').on('click',function(){
    $('#fBulan,#fKategori,#fWilayah').val(''); $('#fTahun').val('2025'); $('#fCari').val('');
    pageSize = parseInt($('#pageSize').val(),10) || 10;
    currentPage = 1;
    filtered=[...DATA]; renderAll(filtered);
});

function syncDataFromBackend(silent = true){
    if(syncInFlight){
        return;
    }

    syncInFlight = true;
    const requestSeq = ++dataRequestSequence;

    $.ajax({
        url: API_DATA_URL,
        method: 'GET',
        cache: false,
        data: { months: 6, _ts: Date.now() },
        success: function(res){
            if(applyBackendPayload(res, { preservePage: true, requestSeq: requestSeq })){
                return;
            }

            if(!silent){
                alert('Data backend belum tersedia untuk halaman ini.');
            }
        },
        error: function(xhr){
            if(silent){
                return;
            }

            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Gagal memuat data terbaru dari backend.';
            alert(msg);
        },
        complete: function(){
            syncInFlight = false;
        }
    });
}

function renderAll(data){ renderTable(data); renderRek(data); }
$('[data-toggle="tooltip"]').tooltip();
refreshWilayahFilterOptions();
renderAll(DATA);
syncDataFromBackend(true);

window.setInterval(function(){
    if(document.hidden){
        return;
    }

    syncDataFromBackend(true);
}, AUTO_SYNC_MS);

});
(function () {
    'use strict';

    const cfg = window.DASHBOARD_MONITOR_CONFIG || {};
    const BASE_URL   = cfg.baseUrl || '';
    const SHOW_URL   = cfg.showUrl || '';
    const MOD_URL    = cfg.modUrl || '';
    const TRUNC_URL  = cfg.truncUrl || '';
    const CSRF       = cfg.csrfToken || (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '');

    let currentPage = 1;

    // ── ACTION BADGE ────────────────────────────────────────────────────
    const actionBadge = (action) => {
        const map = {
            create: { cls: 'badge-success', label: 'Tambah' },
            update: { cls: 'badge-warning', label: 'Ubah'   },
            delete: { cls: 'badge-danger',  label: 'Hapus'  },
        };
        const m = map[action] ?? { cls: 'badge-secondary', label: action };
        return `<span class="badge badge-action ${m.cls}">${m.label}</span>`;
    };

    // ── FORMAT DATE ─────────────────────────────────────────────────────
    const fmtDate = (str) => {
        if (!str) return '-';
        const d = new Date(str);
        return d.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' })
             + ' ' + d.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    };

    // ── LOAD MODULES DROPDOWN ───────────────────────────────────────────
    const loadModules = () => {
        if(!MOD_URL) return;
        fetch(MOD_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(({ data }) => {
                const sel = document.getElementById('filter-module');
                if(!sel) return;
                data.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    sel.appendChild(opt);
                });
            }).catch(() => {});
    };

    // ── FETCH DATA ──────────────────────────────────────────────────────
    const fetchData = (page = 1) => {
        if(!BASE_URL) return;
        currentPage = page;
        const tbody = document.getElementById('log-tbody');
        if(!tbody) return;
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4">
            <i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</td></tr>`;

        const params = new URLSearchParams({
            page,
            action    : document.getElementById('filter-action')?.value || '',
            module    : document.getElementById('filter-module')?.value || '',
            username  : document.getElementById('filter-username')?.value || '',
            date_from : document.getElementById('filter-date-from')?.value || '',
            date_to   : document.getElementById('filter-date-to')?.value || '',
        });

        fetch(`${BASE_URL}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(({ data, meta }) => renderTable(data, meta))
            .catch(() => {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat data</td></tr>`;
            });
    };

    // ── RENDER TABLE ────────────────────────────────────────────────────
    const renderTable = (rows, meta) => {
        const tbody = document.getElementById('log-tbody');
        if(!tbody) return;
        
        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">
                <i class="fas fa-inbox mr-2"></i>Tidak ada data ditemukan</td></tr>`;
        } else {
            tbody.innerHTML = rows.map((r, i) => `
                <tr>
                    <td class="text-muted">${meta.from + i}</td>
                    <td style="white-space:nowrap;">${fmtDate(r.created_at)}</td>
                    <td><i class="fas fa-user-circle mr-1 text-muted"></i>${r.username ?? '-'}</td>
                    <td>${actionBadge(r.action)}</td>
                    <td><span class="badge badge-light border">${r.module ?? '-'}</span></td>
                    <td class="text-truncate" style="max-width:260px;" title="${(r.description??'').replace(/"/g,'&quot;')}">${r.description ?? '-'}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-info btn-detail" data-id="${r.id}" title="Lihat detail">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        renderPagination(meta);
        updateInfo(meta);
    };

    // ── PAGINATION ──────────────────────────────────────────────────────
    const renderPagination = (meta) => {
        const nav = document.getElementById('pagination-nav');
        if(!nav) return;
        
        if (meta.last_page <= 1) { nav.innerHTML = ''; return; }

        let html = '<ul class="pagination pagination-sm mb-0">';
        html += `<li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${meta.current_page - 1}">&laquo;</a></li>`;

        const delta = 2;
        let pages = [];
        for (let p = 1; p <= meta.last_page; p++) {
            if (p === 1 || p === meta.last_page
                || (p >= meta.current_page - delta && p <= meta.current_page + delta)) {
                pages.push(p);
            }
        }

        let prev = null;
        pages.forEach(p => {
            if (prev !== null && p - prev > 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            html += `<li class="page-item ${p === meta.current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
            prev = p;
        });

        html += `<li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${meta.current_page + 1}">&raquo;</a></li>`;
        html += '</ul>';
        nav.innerHTML = html;
    };

    const updateInfo = (meta) => {
        const el = document.getElementById('pagination-info');
        if(!el) return;
        if (!meta.total) { el.textContent = 'Tidak ada data'; return; }
        el.textContent = `Menampilkan ${meta.from}–${meta.to} dari ${meta.total} data`;
    };

    // ── MODAL DETAIL ────────────────────────────────────────────────────
    const showDetail = (id) => {
        const body = document.getElementById('modal-detail-body');
        if(!body) return;
        
        body.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat...</p>';
        if(typeof $ !== 'undefined') $('#modal-detail').modal('show');

        fetch(`${SHOW_URL}/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(({ data: r }) => {
                const jsonBlock = (obj) => {
                    if (!obj) return '<span class="text-muted">—</span>';
                    return `<pre class="json-block">${JSON.stringify(obj, null, 2)}</pre>`;
                };
                body.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr><th style="width:110px">Waktu</th><td>${fmtDate(r.created_at)}</td></tr>
                                <tr><th>User</th><td>${r.username ?? '-'}</td></tr>
                                <tr><th>Aksi</th><td>${actionBadge(r.action)}</td></tr>
                                <tr><th>Modul</th><td><span class="badge badge-light border">${r.module}</span></td></tr>
                                <tr><th>IP</th><td>${r.ip_address ?? '-'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-12">
                            <p class="mb-1 font-weight-bold">Deskripsi</p>
                            <p>${r.description ?? '-'}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 font-weight-bold text-muted">Data Lama (Before)</p>
                            ${jsonBlock(r.old_data)}
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 font-weight-bold text-muted">Data Baru (After)</p>
                            ${jsonBlock(r.new_data)}
                        </div>
                    </div>`;
            }).catch(() => {
                body.innerHTML = '<p class="text-danger text-center">Gagal memuat detail.</p>';
            });
    };

    // ── TRUNCATE ────────────────────────────────────────────────────────
    const doTruncate = () => {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            title: 'Hapus Semua Log?',
            html: 'Seluruh data activity log akan <strong>dihapus permanen</strong>.<br>Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Truncate!',
            cancelButtonText: 'Batal',
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch(TRUNC_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
            }).then(r => r.json())
              .then(({ success, message }) => {
                if (success) {
                    Swal.fire('Berhasil!', message, 'success');
                    fetchData(1);
                } else {
                    Swal.fire('Gagal', message ?? 'Terjadi kesalahan.', 'error');
                }
              }).catch(() => Swal.fire('Error', 'Gagal melakukan truncate.', 'error'));
        });
    };



    // ── EVENT LISTENERS ─────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const btnFilter = document.getElementById('btn-filter');
        if (btnFilter) btnFilter.addEventListener('click', () => fetchData(1));

        const btnReset = document.getElementById('btn-reset');
        if (btnReset) {
            btnReset.addEventListener('click', () => {
                const filterAction = document.getElementById('filter-action');
                const filterModule = document.getElementById('filter-module');
                const filterUsername = document.getElementById('filter-username');
                const filterDateFrom = document.getElementById('filter-date-from');
                const filterDateTo = document.getElementById('filter-date-to');
                
                if(filterAction) filterAction.value = '';
                if(filterModule) filterModule.value = '';
                if(filterUsername) filterUsername.value = '';
                if(filterDateFrom) filterDateFrom.value = '';
                if(filterDateTo) filterDateTo.value = '';
                
                fetchData(1);
            });
        }

        const paginationNav = document.getElementById('pagination-nav');
        if (paginationNav) {
            paginationNav.addEventListener('click', (e) => {
                e.preventDefault();
                const link = e.target.closest('[data-page]');
                if (!link) return;
                const page = parseInt(link.dataset.page);
                if (page && page !== currentPage) fetchData(page);
            });
        }

        const logTbody = document.getElementById('log-tbody');
        if (logTbody) {
            logTbody.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-detail');
                if (btn) showDetail(btn.dataset.id);
            });
        }

        const truncBtn = document.getElementById('btn-truncate');
        if (truncBtn) truncBtn.addEventListener('click', doTruncate);

        // Enter key filter
        ['filter-username', 'filter-date-from', 'filter-date-to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') fetchData(1);
                });
            }
        });

        // ── INIT ────────────────────────────────────────────────────────────
        loadModules();
        fetchData(1);
    });
})();

(function () {
    'use strict';

    const cfg = window.DASHBOARD_MONITOR_CONFIG || {};
    const BASE_URL      = cfg.baseUrl      || '';
    const SHOW_URL      = cfg.showUrl      || '';
    const MOD_URL       = cfg.modUrl       || '';
    const TRUNC_URL     = cfg.truncUrl     || '';
    const LOG_INFO_URL  = cfg.logInfoUrl   || '';
    const LOG_EXPORT_URL= cfg.logExportUrl || '';
    const LOG_TRUNC_URL = cfg.logTruncUrl  || '';
    const SQL_TABLES_URL  = cfg.sqlTablesUrl  || '';
    const SQL_COLUMNS_URL = cfg.sqlColumnsUrl || '';
    const SQL_EXECUTE_URL = cfg.sqlExecuteUrl || '';
    const CSRF          = cfg.csrfToken || (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '');

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

        fetch(SHOW_URL.replace('__ID__', id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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

    // ── TRUNCATE ACTIVITY LOG ────────────────────────────────────────────
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

    // ── LARAVEL LOG INFO ────────────────────────────────────────────────
    const fmtBytes = (bytes) => {
        if (bytes === 0) return '0 B';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return (bytes / 1024 / 1024).toFixed(2) + ' MB';
    };

    const loadLaravelLogInfo = () => {
        if (!LOG_INFO_URL) return;
        const spinner   = document.getElementById('log-info-spinner');
        const content   = document.getElementById('log-info-content');
        const missing   = document.getElementById('log-info-missing');
        const sizeEl    = document.getElementById('log-info-size');
        const modEl     = document.getElementById('log-info-modified');
        const badgeSz   = document.getElementById('laravel-log-size');

        fetch(LOG_INFO_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(({ exists, size_bytes, modified }) => {
                if (spinner) spinner.style.display = 'none';
                if (!exists) {
                    if (missing) missing.style.display = '';
                    if (badgeSz) badgeSz.textContent = 'Tidak ada';
                    return;
                }
                const formatted = fmtBytes(size_bytes);
                if (sizeEl) sizeEl.textContent = formatted;
                if (modEl)  modEl.textContent  = modified ? modified : '—';
                if (content) content.style.display = '';
                if (badgeSz) badgeSz.textContent = formatted;
            }).catch(() => {
                if (spinner) spinner.style.display = 'none';
                if (missing) { missing.style.display = ''; missing.textContent = 'Gagal memuat info laravel.log.'; }
            });
    };

    // ── TRUNCATE LARAVEL LOG ────────────────────────────────────────────
    const doTruncateLaravelLog = () => {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            title: 'Kosongkan laravel.log?',
            html: 'Seluruh isi file <strong>laravel.log</strong> akan dihapus permanen.<br><span class="text-muted small">Tindakan ini tidak dapat dibatalkan.</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e6a817',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kosongkan!',
            cancelButtonText: 'Batal',
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch(LOG_TRUNC_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
            }).then(r => r.json())
              .then(({ success, message }) => {
                if (success) {
                    Swal.fire('Berhasil!', message, 'success').then(() => loadLaravelLogInfo());
                } else {
                    Swal.fire('Gagal', message ?? 'Terjadi kesalahan.', 'error');
                }
              }).catch(() => Swal.fire('Error', 'Gagal mengosongkan laravel.log.', 'error'));
        });
    };

    // ═════════════════════════════════════════════════════════════════════
    // SQL IMPORT FEATURE
    // ═════════════════════════════════════════════════════════════════════

    const ALLOWED_TABLES = [
        'barang', 'barang_stok', 'toko', 'barang_toko', 'pengiriman',
        'retur', 'pemesanan', 'follow_up', 'data_customer',
        'eoq_biaya_pesan_global', 'eoq_biaya_pesan_toko', 'eoq_biaya_simpan',
        'ss_zscore_setting',
    ];

    /**
     * Load tabel yang diizinkan ke dropdown selector.
     */
    const loadSqlImportTables = () => {
        const sel = document.getElementById('sql-import-table-selector');
        if (!sel) return;

        // Populate dari daftar lokal agar tidak perlu fetch
        ALLOWED_TABLES.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            sel.appendChild(opt);
        });

        const countEl = document.getElementById('sql-import-count-num');
        if (countEl) countEl.textContent = ALLOWED_TABLES.length;
    };

    /**
     * Load kolom tabel dari server.
     */
    const loadTableColumns = (tableName) => {
        if (!SQL_COLUMNS_URL || !tableName) return;

        const area = document.getElementById('sql-import-columns-area');
        const titleEl = document.getElementById('sql-import-columns-table');
        const tbody = document.querySelector('#sql-import-columns-table-body tbody');
        if (!area || !tbody) return;

        titleEl.textContent = tableName;
        tbody.innerHTML = '<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat...</td></tr>';
        area.style.display = '';

        fetch(`${SQL_COLUMNS_URL}?table=${encodeURIComponent(tableName)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(({ success, columns, message }) => {
            if (!success) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">${message}</td></tr>`;
                return;
            }
            tbody.innerHTML = columns.map(c => `
                <tr>
                    <td><strong>${c.field}</strong></td>
                    <td>${c.type}</td>
                    <td>${c.null === 'YES' ? '<span class="text-success">YES</span>' : '<span class="text-danger">NO</span>'}</td>
                    <td>${c.key ? `<span class="badge badge-primary badge-sm">${c.key}</span>` : '—'}</td>
                    <td>${c.default ?? '<span class="text-muted">NULL</span>'}</td>
                    <td>${c.extra || '—'}</td>
                </tr>
            `).join('');
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Gagal memuat kolom.</td></tr>';
        });
    };

    /**
     * Parse SQL untuk preview (extract nama tabel dan jumlah baris).
     */
    const parseSqlPreview = (sql) => {
        const result = { table: null, rows: 0, valid: false, error: null };

        if (!sql || sql.trim().length < 10) return result;

        const trimmed = sql.trim();

        // Cek apakah dimulai dengan INSERT INTO
        if (!/^\s*INSERT\s+INTO\s+/i.test(trimmed)) {
            result.error = 'Bukan statement INSERT INTO';
            return result;
        }

        // Extract nama tabel
        const tableMatch = trimmed.match(/INSERT\s+INTO\s+`?(\w+)`?\s*/i);
        if (!tableMatch) {
            result.error = 'Tidak dapat mendeteksi nama tabel';
            return result;
        }
        result.table = tableMatch[1];

        // Cek apakah tabel diizinkan
        if (!ALLOWED_TABLES.includes(result.table)) {
            result.error = `Tabel "${result.table}" tidak diizinkan`;
            return result;
        }

        // Hitung jumlah baris (hitung kemunculan pola buka-tutup kurung VALUES)
        // Setiap row dalam VALUES ditandai dengan tanda kurung buka-tutup yang berisi data
        const valuesMatch = trimmed.match(/VALUES\s*/i);
        if (valuesMatch) {
            // Count rows by matching top-level parenthesized groups after VALUES
            const valuesIdx = trimmed.search(/VALUES\s*/i) + trimmed.match(/VALUES\s*/i)[0].length;
            const valuesPart = trimmed.substring(valuesIdx);
            
            // Count opening parens at the top level (not nested)
            let depth = 0;
            let rowCount = 0;
            for (let i = 0; i < valuesPart.length; i++) {
                const ch = valuesPart[i];
                if (ch === '(') {
                    if (depth === 0) rowCount++;
                    depth++;
                } else if (ch === ')') {
                    depth--;
                } else if (ch === "'" || ch === '"') {
                    // Skip string literals
                    const quote = ch;
                    i++;
                    while (i < valuesPart.length) {
                        if (valuesPart[i] === '\\') { i++; } // skip escaped
                        else if (valuesPart[i] === quote) break;
                        i++;
                    }
                }
            }
            result.rows = rowCount;
        }

        result.valid = true;
        return result;
    };

    /**
     * Update preview chips berdasarkan parsing SQL.
     */
    const updateSqlPreview = () => {
        const textarea = document.getElementById('sql-import-textarea');
        const preview = document.getElementById('sql-import-preview');
        const executeBtn = document.getElementById('btn-sql-import-execute');
        if (!textarea || !preview) return;

        const sql = textarea.value;
        if (!sql || sql.trim().length < 10) {
            preview.style.display = 'none';
            if (executeBtn) executeBtn.disabled = true;
            return;
        }

        const parsed = parseSqlPreview(sql);
        preview.style.display = '';

        const tableChip = document.getElementById('sql-preview-table');
        const rowsChip  = document.getElementById('sql-preview-rows');
        const statusChip = document.getElementById('sql-preview-status');

        if (tableChip) {
            tableChip.innerHTML = `<i class="fas fa-table mr-1"></i> Tabel: <strong>${parsed.table || '—'}</strong>`;
            tableChip.className = 'sql-preview-chip' + (parsed.table && ALLOWED_TABLES.includes(parsed.table) ? ' chip-valid' : parsed.table ? ' chip-invalid' : '');
        }
        if (rowsChip) {
            rowsChip.innerHTML = `<i class="fas fa-list-ol mr-1"></i> Baris: <strong>${parsed.rows || '—'}</strong>`;
        }
        if (statusChip) {
            if (parsed.valid) {
                statusChip.innerHTML = '<i class="fas fa-check-circle mr-1"></i> <strong>Siap import</strong>';
                statusChip.className = 'sql-preview-chip chip-valid';
            } else {
                statusChip.innerHTML = `<i class="fas fa-times-circle mr-1"></i> <strong>${parsed.error || 'Tidak valid'}</strong>`;
                statusChip.className = 'sql-preview-chip chip-invalid';
            }
        }

        if (executeBtn) executeBtn.disabled = !parsed.valid;
    };

    /**
     * Eksekusi SQL Import via API.
     */
    const executeSqlImport = () => {
        const textarea = document.getElementById('sql-import-textarea');
        const modeSelect = document.getElementById('sql-import-mode');
        const resultArea = document.getElementById('sql-import-result');
        const executeBtn = document.getElementById('btn-sql-import-execute');
        if (!textarea || !SQL_EXECUTE_URL) return;

        const sql  = textarea.value.trim();
        const mode = modeSelect?.value || 'insert';

        if (!sql) return;

        // Konfirmasi dengan SweetAlert
        const parsed = parseSqlPreview(sql);
        if (!parsed.valid) return;

        const modeLabel = mode === 'upsert' ? 'UPSERT (Insert atau Update)' : 'INSERT (Tambah baru)';

        if (typeof Swal === 'undefined') {
            // Fallback ke confirm biasa
            if (!confirm(`Import ${parsed.rows} baris ke tabel "${parsed.table}" dengan mode ${modeLabel}?`)) return;
            doExecuteImport(sql, mode, resultArea, executeBtn);
            return;
        }

        Swal.fire({
            title: 'Konfirmasi SQL Import',
            html: `
                <div style="text-align:left;">
                    <p>Anda akan menjalankan import berikut:</p>
                    <table class="table table-sm table-borderless" style="font-size:.85rem;">
                        <tr><td style="width:100px;"><strong>Tabel</strong></td><td><code>${parsed.table}</code></td></tr>
                        <tr><td><strong>Baris</strong></td><td>${parsed.rows} baris data</td></tr>
                        <tr><td><strong>Mode</strong></td><td>${modeLabel}</td></tr>
                    </table>
                    <p class="text-muted small mb-0"><i class="fas fa-info-circle mr-1"></i>Foreign key check akan dinonaktifkan sementara.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-play mr-1"></i> Jalankan Import',
            cancelButtonText: 'Batal',
        }).then(result => {
            if (!result.isConfirmed) return;
            doExecuteImport(sql, mode, resultArea, executeBtn);
        });
    };

    const doExecuteImport = (sql, mode, resultArea, executeBtn) => {
        // Disable button + show spinner
        if (executeBtn) {
            executeBtn.disabled = true;
            executeBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
        }

        fetch(SQL_EXECUTE_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ sql, mode }),
        })
        .then(r => r.json())
        .then(data => {
            if (resultArea) {
                resultArea.style.display = '';
                if (data.success) {
                    resultArea.className = 'sql-import-result mt-3 result-success';
                    resultArea.innerHTML = `
                        <div class="result-title"><i class="fas fa-check-circle mr-1"></i> ${data.message}</div>
                        <div class="result-stats">
                            <span class="stat-chip"><i class="fas fa-table mr-1 text-primary"></i> Tabel: <strong>${data.table}</strong></span>
                            <span class="stat-chip"><i class="fas fa-arrow-left mr-1 text-muted"></i> Sebelum: <strong>${data.rows_before}</strong></span>
                            <span class="stat-chip"><i class="fas fa-arrow-right mr-1 text-success"></i> Sesudah: <strong>${data.rows_after}</strong></span>
                            <span class="stat-chip"><i class="fas fa-plus mr-1 text-info"></i> Ditambahkan: <strong>${data.rows_inserted}</strong></span>
                            <span class="stat-chip"><i class="fas fa-cog mr-1 text-secondary"></i> Mode: <strong>${data.mode}</strong></span>
                        </div>
                    `;
                    // Refresh activity log
                    fetchData(1);
                } else {
                    resultArea.className = 'sql-import-result mt-3 result-error';
                    resultArea.innerHTML = `
                        <div class="result-title"><i class="fas fa-times-circle mr-1"></i> Import Gagal</div>
                        <div class="result-detail">${data.message || 'Terjadi kesalahan.'}</div>
                    `;
                }
            }
        })
        .catch(err => {
            if (resultArea) {
                resultArea.style.display = '';
                resultArea.className = 'sql-import-result mt-3 result-error';
                resultArea.innerHTML = `
                    <div class="result-title"><i class="fas fa-times-circle mr-1"></i> Error</div>
                    <div class="result-detail">${err.message || 'Gagal menghubungi server.'}</div>
                `;
            }
        })
        .finally(() => {
            if (executeBtn) {
                executeBtn.innerHTML = '<i class="fas fa-play mr-1"></i> Jalankan Import';
                // Re-evaluate state
                updateSqlPreview();
            }
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

        const truncLogBtn = document.getElementById('btn-truncate-log');
        if (truncLogBtn) truncLogBtn.addEventListener('click', doTruncateLaravelLog);

        // Enter key filter
        ['filter-username', 'filter-date-from', 'filter-date-to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') fetchData(1);
                });
            }
        });

        // ── SQL IMPORT LISTENERS ────────────────────────────────────────
        // Chevron rotation for collapse
        const sqlImportBody = document.getElementById('sql-import-body');
        const sqlImportChevron = document.getElementById('sql-import-chevron');
        if (sqlImportBody && sqlImportChevron) {
            sqlImportBody.addEventListener('show.bs.collapse', () => sqlImportChevron.classList.add('rotated'));
            sqlImportBody.addEventListener('hide.bs.collapse', () => sqlImportChevron.classList.remove('rotated'));
            // Bootstrap 4 fallback
            if (typeof $ !== 'undefined') {
                $(sqlImportBody).on('show.bs.collapse', () => sqlImportChevron.classList.add('rotated'));
                $(sqlImportBody).on('hide.bs.collapse', () => sqlImportChevron.classList.remove('rotated'));
            }
        }

        // Load tables dropdown
        loadSqlImportTables();

        // Table selector → enable/disable columns button
        const tableSelector = document.getElementById('sql-import-table-selector');
        const btnShowColumns = document.getElementById('btn-show-columns');
        if (tableSelector && btnShowColumns) {
            tableSelector.addEventListener('change', () => {
                btnShowColumns.disabled = !tableSelector.value;
            });
        }

        // Show Columns button
        if (btnShowColumns) {
            btnShowColumns.addEventListener('click', () => {
                const tbl = document.getElementById('sql-import-table-selector')?.value;
                if (tbl) loadTableColumns(tbl);
            });
        }

        // Close columns area
        const btnCloseColumns = document.getElementById('btn-close-columns');
        if (btnCloseColumns) {
            btnCloseColumns.addEventListener('click', () => {
                const area = document.getElementById('sql-import-columns-area');
                if (area) area.style.display = 'none';
            });
        }

        // SQL textarea → real-time preview
        const sqlTextarea = document.getElementById('sql-import-textarea');
        if (sqlTextarea) {
            let previewTimer = null;
            sqlTextarea.addEventListener('input', () => {
                clearTimeout(previewTimer);
                previewTimer = setTimeout(updateSqlPreview, 300);
            });
        }

        // Execute button
        const btnExecute = document.getElementById('btn-sql-import-execute');
        if (btnExecute) {
            btnExecute.addEventListener('click', executeSqlImport);
        }

        // Clear button
        const btnClear = document.getElementById('btn-sql-import-clear');
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                const ta = document.getElementById('sql-import-textarea');
                const preview = document.getElementById('sql-import-preview');
                const result = document.getElementById('sql-import-result');
                const execBtn = document.getElementById('btn-sql-import-execute');
                if (ta) ta.value = '';
                if (preview) preview.style.display = 'none';
                if (result) result.style.display = 'none';
                if (execBtn) execBtn.disabled = true;
            });
        }

        // ── INIT ────────────────────────────────────────────────────────────
        loadModules();
        fetchData(1);
        loadLaravelLogInfo();
    });
})();


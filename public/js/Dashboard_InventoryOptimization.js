(function () {
    'use strict';

    var invData = window.INV_DATA || [];
    var tokosGeo = window.INV_TOKOS || [];
    var nominatimBaseUrl = window.INV_NOMINATIM_BASE_URL || 'https://nominatim.openstreetmap.org';
    var autoRefreshUrl = window.INV_AUTO_REFRESH_URL || '/dashboard/api/inventory-optimization/auto-refresh';
    var autoRefreshIntervalMs = parseInt(window.INV_AUTO_REFRESH_INTERVAL_MS, 10) || 300000;

    const NEARBY_DISTANCE_KM = 3; //Buat jarak maksimal untuk kelompok toko berdekatan
    const MIN_TOKO_FOR_AREA = 3;
    const NEARBY_AREA_COLOR = '#FF9800';

    var map = null;
    var dynamicLayer = null;
    var hasInitialBounds = false;
    var selectedTokoId = null;
    var tokoMarkerIndex = {};
    var autoRefreshTimer = null;
    var isAutoRefreshRunning = false;

    function groupByToko(data) {
        return data.reduce(function (acc, item) {
            var tokoKey = String(item.toko_id);
            if (!acc[tokoKey]) acc[tokoKey] = [];
            acc[tokoKey].push(item);
            return acc;
        }, {});
    }

    function markerColor(tokoId, grouped) {
        var items = grouped[String(tokoId)] || [];
        if (items.some(function (i) { return i.is_below_rop; }))    return '#dc2626';
        if (items.some(function (i) { return i.shelf_life_flag; })) return '#d97706';
        return '#16a34a';
    }

    function toRadians(value) {
        return value * (Math.PI / 180);
    }

    function calculateDistanceKm(pointA, pointB) {
        var earthRadiusKm = 6371;
        var dLat = toRadians(pointB.lat - pointA.lat);
        var dLng = toRadians(pointB.lng - pointA.lng);
        var lat1 = toRadians(pointA.lat);
        var lat2 = toRadians(pointB.lat);

        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(lat1) * Math.cos(lat2)
            * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return earthRadiusKm * c;
    }

    function getNearbyGroups(points, maxDistanceKm, minGroupSize) {
        var visited = new Array(points.length).fill(false);
        var groups = [];

        points.forEach(function (_, startIndex) {
            if (visited[startIndex]) return;

            var queue = [startIndex];
            var groupIndexes = [];
            visited[startIndex] = true;

            while (queue.length > 0) {
                var currentIndex = queue.shift();
                groupIndexes.push(currentIndex);

                points.forEach(function (_, compareIndex) {
                    if (visited[compareIndex]) return;

                    var distanceKm = calculateDistanceKm(points[currentIndex], points[compareIndex]);
                    if (distanceKm <= maxDistanceKm) {
                        visited[compareIndex] = true;
                        queue.push(compareIndex);
                    }
                });
            }

            if (groupIndexes.length >= minGroupSize) {
                groups.push(groupIndexes.map(function (index) {
                    return points[index];
                }));
            }
        });

        return groups;
    }

    function getConvexHullLatLng(points) {
        if (points.length < 3) return [];

        var sorted = points.map(function (point) {
            return { x: point.lng, y: point.lat };
        }).sort(function (a, b) {
            if (a.x === b.x) return a.y - b.y;
            return a.x - b.x;
        });

        function cross(o, a, b) {
            return (a.x - o.x) * (b.y - o.y) - (a.y - o.y) * (b.x - o.x);
        }

        var lower = [];
        sorted.forEach(function (point) {
            while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], point) <= 0) {
                lower.pop();
            }
            lower.push(point);
        });

        var upper = [];
        sorted.slice().reverse().forEach(function (point) {
            while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], point) <= 0) {
                upper.pop();
            }
            upper.push(point);
        });

        upper.pop();
        lower.pop();

        var hull = lower.concat(upper);
        return hull.map(function (point) {
            return [point.y, point.x];
        });
    }

    function drawNearbyAreaPolygons(map, points, targetLayer) {
        if (points.length < MIN_TOKO_FOR_AREA) return;

        var groups = getNearbyGroups(points, NEARBY_DISTANCE_KM, MIN_TOKO_FOR_AREA);

        if (!map.getPane('inv-area-pane')) {
            map.createPane('inv-area-pane');
            map.getPane('inv-area-pane').style.zIndex = 350;
        }

        groups.forEach(function (group) {
            var hullLatLng = getConvexHullLatLng(group);
            if (hullLatLng.length < 3) return;

            var polygon = L.polygon(hullLatLng, {
                pane: 'inv-area-pane',
                color: NEARBY_AREA_COLOR,
                weight: 2,
                fillColor: NEARBY_AREA_COLOR,
                fillOpacity: 0.18
            });

            polygon.bindPopup('Area toko berdekatan (' + group.length + ' toko)');
            targetLayer.addLayer(polygon);
        });
    }

    function buildPopupHtml(toko, items) {
        var rows = '';
        var hint = 'klik untuk detail lengkap';

        if (items.length > 0) {
            rows = items.map(function (item) {
                var cls       = (item.is_below_rop || item.shelf_life_flag) ? 'warn' : 'ok';
                var shortName = item.barang_nama.length > 22
                                ? item.barang_nama.substring(0, 20) + '…'
                                : item.barang_nama;
                return '<div class="inv-popup-row">'
                    + '<span class="inv-popup-produk">' + shortName + '</span>'
                    + '<span class="inv-popup-val ' + cls + '">' + item.q_kirim_result + '</span>'
                    + '<span class="inv-popup-unit">unit</span>'
                    + '</div>';
            }).join('');
        } else {
            rows = '<div class="inv-popup-empty">Belum ada data rekomendasi produk.</div>';
            hint = 'data rekomendasi sedang disiapkan';
        }

        var nominatimUrl = nominatimBaseUrl + '/ui/reverse.html?lat='
            + encodeURIComponent(toko.lat)
            + '&lon=' + encodeURIComponent(toko.lng)
            + '&zoom=18';

        return '<div class="inv-popup">'
            + '<div class="inv-popup-toko">' + toko.nama + '</div>'
            + '<div class="inv-popup-loc">'  + (toko.lokasi || '-') + '</div>'
            + '<div class="inv-popup-items">' + rows + '</div>'
            + '<a class="inv-popup-link" href="' + nominatimUrl + '" target="_blank" rel="noopener noreferrer">Lihat titik di Nominatim</a>'
            + '<div class="inv-popup-hint">' + hint + '</div>'
            + '</div>';
    }

    function buildDetailHtml(items) {
        return items.map(function (item) {
            var recCls = (item.is_below_rop || item.shelf_life_flag) ? 'warn' : 'ok';

            var flagTag = item.shelf_life_flag
                ? '<span class="inv-badge inv-badge-warn" style="font-size:10px;">Shelf life flag</span>'
                : '';

            var flagNote = item.shelf_life_flag
                ? '<div class="inv-rec-note">Disesuaikan shelf life</div>'
                : '';

            var kritisNote = item.is_below_rop
                ? '<div class="inv-rec-note" style="font-weight:600;color:#dc2626;">Segera kirim!</div>'
                : '';

            var interval = parseFloat(item.interval_kirim) || 0;
            var qKirim   = parseInt(item.q_kirim_result)   || 0;

            return '<div class="inv-produk-item">'

                // Nama produk + badge
                + '<div class="inv-produk-name">'
                + item.barang_nama + ' ' + flagTag
                + '</div>'

                // EOQ, SS, ROP
                + '<div class="inv-row3">'
                + '<div><div class="inv-mini-label">EOQ</div>'
                + '<div class="inv-mini-val">' + item.eoq_result
                + '<span class="inv-mini-unit">unit</span></div></div>'
                + '<div><div class="inv-mini-label">Safety Stock</div>'
                + '<div class="inv-mini-val">' + item.ss_result
                + '<span class="inv-mini-unit">unit</span></div></div>'
                + '<div><div class="inv-mini-label">ROP</div>'
                + '<div class="inv-mini-val">' + item.rop_result
                + '<span class="inv-mini-unit">unit</span></div></div>'
                + '</div>'

                // Rekomendasi kirim
                + '<div class="inv-rec-box ' + recCls + '">'
                + '<div>'
                + '<div class="inv-rec-label">Rekomendasi kirim</div>'
                + '<span class="inv-rec-main">' + qKirim + '</span>'
                + '<span class="inv-rec-unit"> unit</span>'
                + '</div>'
                + '<div style="text-align:right;">'
                + '<div class="inv-rec-note">Interval: ' + interval + ' hari</div>'
                + flagNote + kritisNote
                + '</div>'
                + '</div>'

                + '</div>';
        }).join('');
    }

    function showDetail(toko, items) {
        setTextIfExists('inv-detail-label', 'Detail - ' + toko.nama);
        setTextIfExists('inv-detail-title', toko.nama + ' · ' + (toko.lokasi || '-'));

        var badge     = document.getElementById('inv-detail-badge');
        var detailBody = document.getElementById('inv-detail-body');

        if (!badge || !detailBody) {
            return;
        }

        if (items.length === 0) {
            badge.className = 'inv-badge inv-badge-info';
            badge.textContent = 'Belum ada rekomendasi';
            detailBody.innerHTML = '<div class="inv-empty-state">Belum ada data rekomendasi kirim untuk toko ini.</div>';
            return;
        }

        var hasKritis = items.some(function (i) { return i.is_below_rop; });
        var hasFlag   = items.some(function (i) { return i.shelf_life_flag; });

        if (hasKritis) {
            badge.className   = 'inv-badge inv-badge-danger';
            badge.textContent = 'Perlu perhatian';
        } else if (hasFlag) {
            badge.className   = 'inv-badge inv-badge-warn';
            badge.textContent = 'Shelf life flag';
        } else {
            badge.className   = 'inv-badge inv-badge-ok';
            badge.textContent = 'Aman';
        }

        detailBody.innerHTML = buildDetailHtml(items);
    }

    function resetDetailPanel() {
        setTextIfExists('inv-detail-title', 'Pilih toko di peta');

        var badge = document.getElementById('inv-detail-badge');
        if (badge) {
            badge.className = '';
            badge.textContent = '';
        }

        var detailBody = document.getElementById('inv-detail-body');
        if (detailBody) {
            detailBody.innerHTML = '<div class="inv-empty-state">Klik marker pada peta untuk melihat EOQ, SS, ROP, dan rekomendasi kirim per produk.</div>';
        }
    }

    function setTextIfExists(elementId, value) {
        var el = document.getElementById(elementId);
        if (el) {
            el.textContent = value;
        }
    }

    function normalizeSearchText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function setSearchStatus(message, state) {
        var statusEl = document.getElementById('inv-toko-search-status');
        if (!statusEl) {
            return;
        }

        statusEl.className = 'inv-map-search-status' + (state ? ' is-' + state : '');
        statusEl.textContent = message;
    }

    function findBestTokoMatch(query) {
        var normalizedQuery = normalizeSearchText(query);

        if (!normalizedQuery) {
            return null;
        }

        var validTokos = getValidTokos();
        var bestMatch = null;
        var bestRank = Number.POSITIVE_INFINITY;

        validTokos.forEach(function (toko) {
            var nama = normalizeSearchText(toko.nama);
            var lokasi = normalizeSearchText(toko.lokasi);
            var rank = null;

            if (nama === normalizedQuery) {
                rank = 0;
            } else if (lokasi === normalizedQuery) {
                rank = 1;
            } else if (nama.indexOf(normalizedQuery) === 0) {
                rank = 2;
            } else if (lokasi.indexOf(normalizedQuery) === 0) {
                rank = 3;
            } else if (nama.indexOf(normalizedQuery) >= 0) {
                rank = 4;
            } else if (lokasi.indexOf(normalizedQuery) >= 0) {
                rank = 5;
            }

            if (rank !== null && rank < bestRank) {
                bestRank = rank;
                bestMatch = toko;
            }
        });

        return bestMatch;
    }

    function focusTokoOnMap(toko) {
        if (!toko || !map) {
            return;
        }

        var tokoKey = String(toko.toko_id);
        var lat = parseFloat(toko.lat);
        var lng = parseFloat(toko.lng);
        var markerRecord = tokoMarkerIndex[tokoKey];
        var grouped = groupByToko(invData);
        var items = grouped[tokoKey] || [];

        selectedTokoId = tokoKey;

        if (!isNaN(lat) && !isNaN(lng)) {
            var targetZoom = Math.max(map.getZoom() || 0, 15);
            map.setView([lat, lng], targetZoom);
        }

        if (markerRecord && markerRecord.marker) {
            markerRecord.marker.openPopup();
        }

        showDetail(toko, items);
        setSearchStatus('Menampilkan: ' + toko.nama, 'success');
    }

    function searchTokoFromInput() {
        var searchInput = document.getElementById('inv-toko-search');

        if (!searchInput) {
            return;
        }

        var query = searchInput.value || '';
        var normalizedQuery = normalizeSearchText(query);

        if (!normalizedQuery) {
            setSearchStatus('Ketik nama toko lalu tekan Enter.', '');
            return;
        }

        var toko = findBestTokoMatch(normalizedQuery);

        if (!toko) {
            setSearchStatus('Toko "' + query.trim() + '" tidak ditemukan.', 'error');
            return;
        }

        focusTokoOnMap(toko);
    }

    function wireTokoSearch() {
        var searchInput = document.getElementById('inv-toko-search');
        var searchButton = document.getElementById('inv-toko-search-btn');

        if (!searchInput || !searchButton || searchInput.dataset.bound === '1') {
            return;
        }

        searchInput.dataset.bound = '1';

        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchTokoFromInput();
            }
        });

        searchButton.addEventListener('click', function () {
            searchTokoFromInput();
        });
    }

    function updateSummary(grouped) {
        var totalKombinasi = 0, totalKritis = 0, totalFlag = 0, totalWarn = 0, totalOk = 0;

        // FIX: iterasi dari grouped (data yang sudah diproses), bukan invData global
        // agar angka summary selalu sinkron dengan state peta saat ini
        Object.keys(grouped).forEach(function (tokoKey) {
            var items = grouped[tokoKey] || [];
            items.forEach(function (item) {
                totalKombinasi++;
                if (item.is_below_rop)    totalKritis++;
                if (item.shelf_life_flag) totalFlag++;
                if (item.is_below_rop || item.shelf_life_flag) totalWarn++;
                else totalOk++;
            });
        });

        setTextIfExists('inv-m-kombinasi',  totalKombinasi);
        setTextIfExists('inv-m-kritis',     totalKritis);
        setTextIfExists('inv-m-flag',       totalFlag);
        setTextIfExists('inv-warn-count',   totalWarn + ' perlu perhatian');
        setTextIfExists('inv-ok-count',     totalOk + ' aman');
    }

    function getValidTokos() {
        return tokosGeo.filter(function (toko) {
            var lat = parseFloat(toko.lat);
            var lng = parseFloat(toko.lng);
            return !isNaN(lat) && !isNaN(lng);
        });
    }

    function initMapIfNeeded(validTokos) {
        if (map) {
            return;
        }

        var initialCenter = validTokos.length > 0
            ? [parseFloat(validTokos[0].lat), parseFloat(validTokos[0].lng)]
            : [-7.98, 112.63];

        map = L.map('inv-map', { zoomControl: true }).setView(initialCenter, 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors · Geocoding by <a href="' + nominatimBaseUrl + '" target="_blank" rel="noopener noreferrer">Nominatim</a>',
            maxZoom: 19
        }).addTo(map);

        dynamicLayer = L.layerGroup().addTo(map);
    }

    function renderMap(grouped) {
        var validTokos = getValidTokos();
        initMapIfNeeded(validTokos);

        if (!map || !dynamicLayer) {
            return;
        }

        dynamicLayer.clearLayers();
        tokoMarkerIndex = {};

        var tokoPoints = validTokos.map(function (toko) {
            return {
                toko_id: toko.toko_id,
                nama: toko.nama,
                lat: parseFloat(toko.lat),
                lng: parseFloat(toko.lng)
            };
        });

        drawNearbyAreaPolygons(map, tokoPoints, dynamicLayer);

        var bounds = [];
        var selectedFound = false;

        validTokos.forEach(function (toko) {
            var tokoKey = String(toko.toko_id);
            var items = grouped[tokoKey] || [];
            var lat = parseFloat(toko.lat);
            var lng = parseFloat(toko.lng);

            var color  = markerColor(tokoKey, grouped);
            var marker = L.circleMarker([lat, lng], {
                radius: 8,
                color: '#ffffff',
                weight: 2,
                fillColor: color,
                fillOpacity: 1
            });

            marker.bindPopup(buildPopupHtml(toko, items), { maxWidth: 230 });

            marker.on('click', function () {
                selectedTokoId = tokoKey;
                showDetail(toko, items);
            });

            marker.on('popupopen', function () {
                selectedTokoId = tokoKey;
                showDetail(toko, items);
            });

            dynamicLayer.addLayer(marker);
            tokoMarkerIndex[tokoKey] = {
                marker: marker,
                toko: toko,
                items: items
            };

            bounds.push([lat, lng]);

            if (selectedTokoId !== null && selectedTokoId === tokoKey) {
                selectedFound = true;
                showDetail(toko, items);
            }
        });

        if (!hasInitialBounds && bounds.length > 1) {
            map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
            hasInitialBounds = true;
        }

        if (selectedTokoId && !selectedFound) {
            selectedTokoId = null;
            resetDetailPanel();
        }
    }

    function formatRefreshTimestamp(isoDate) {
        var date = new Date(isoDate);
        if (isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
    }

    function setAutoRefreshIndicator(updatedAt) {
        var timeEl = document.getElementById('inv-refresh-time');

        if (timeEl && updatedAt) {
            timeEl.textContent = 'Update terakhir: ' + formatRefreshTimestamp(updatedAt);
        }
    }

    function setRefreshDotState(state) {
        var dotEl = document.getElementById('inv-auto-refresh-dot');
        var labelEl = document.getElementById('inv-auto-refresh-label');

        if (dotEl) {
            dotEl.className = 'inv-auto-refresh-dot' + (state ? ' is-' + state : '');
        }

        if (labelEl) {
            switch (state) {
                case 'running':
                    labelEl.textContent = 'Menghitung ulang data...';
                    break;
                case 'error':
                    labelEl.textContent = 'Gagal memperbarui';
                    break;
                default:
                    labelEl.textContent = 'Auto-update aktif';
                    break;
            }
        }
    }

    function updateCountdown(secondsLeft) {
        var countdownEl = document.getElementById('inv-refresh-countdown');
        if (countdownEl) {
            if (secondsLeft > 0) {
                countdownEl.textContent = '· refresh dalam ' + secondsLeft + 's';
            } else {
                countdownEl.textContent = '';
            }
        }
    }

    function applyDashboardSnapshot(snapshot) {
        if (!snapshot) {
            return;
        }

        invData = Array.isArray(snapshot.rekomendasiData) ? snapshot.rekomendasiData : [];
        tokosGeo = Array.isArray(snapshot.tokosGeo) ? snapshot.tokosGeo : [];

        setTextIfExists('inv-m-total-toko', tokosGeo.length);

        var grouped = groupByToko(invData);
        updateSummary(grouped);
        renderMap(grouped);
    }

    function refreshDashboardData() {
        if (isAutoRefreshRunning) {
            return;
        }

        isAutoRefreshRunning = true;
        setRefreshDotState('running');

        fetch(autoRefreshUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (result) {
                if (!result || !result.data) {
                    throw new Error('Respon auto refresh tidak valid.');
                }

                applyDashboardSnapshot(result.data);

                var updatedAt = result.meta && result.meta.updated_at
                    ? result.meta.updated_at
                    : new Date().toISOString();

                setAutoRefreshIndicator(updatedAt);
                setRefreshDotState('idle');
            })
            .catch(function (error) {
                console.error('Auto refresh dashboard gagal:', error);
                setRefreshDotState('error');

                // Kembalikan ke idle setelah 5 detik agar user tahu error sudah lewat
                setTimeout(function () {
                    setRefreshDotState('idle');
                }, 5000);
            })
            .finally(function () {
                isAutoRefreshRunning = false;
            });
    }

    var countdownTimer = null;
    var countdownSeconds = 0;

    function startCountdown() {
        countdownSeconds = Math.floor(autoRefreshIntervalMs / 1000);
        updateCountdown(countdownSeconds);

        if (countdownTimer) {
            clearInterval(countdownTimer);
        }

        countdownTimer = setInterval(function () {
            countdownSeconds--;
            if (countdownSeconds <= 0) {
                updateCountdown(0);
                clearInterval(countdownTimer);
                countdownTimer = null;
            } else {
                updateCountdown(countdownSeconds);
            }
        }, 1000);
    }

    function doRefreshAndResetCountdown() {
        refreshDashboardData();
        startCountdown();
    }

    function startAutoRefresh() {
        setAutoRefreshIndicator(new Date().toISOString());
        setRefreshDotState('idle');

        // Refresh pertama kali saat halaman dimuat
        doRefreshAndResetCountdown();

        autoRefreshTimer = window.setInterval(doRefreshAndResetCountdown, autoRefreshIntervalMs);

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                doRefreshAndResetCountdown();
            }
        });

        window.addEventListener('beforeunload', function () {
            if (autoRefreshTimer !== null) {
                window.clearInterval(autoRefreshTimer);
                autoRefreshTimer = null;
            }
            if (countdownTimer !== null) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        wireTokoSearch();
        applyDashboardSnapshot({
            rekomendasiData: invData,
            tokosGeo: tokosGeo
        });
        startAutoRefresh();
    });

})();
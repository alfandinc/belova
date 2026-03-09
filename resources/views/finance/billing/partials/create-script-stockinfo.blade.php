// --- Stock info modal helpers (kept in same scope as billingData/table) ---
function getGudangNameById(gudangId) {
    try {
        if (window.gudangData && Array.isArray(window.gudangData.gudangs)) {
            const found = window.gudangData.gudangs.find(function(g) {
                return String(g.id) === String(gudangId) || g.id == gudangId;
            });
            if (found) return found.nama || found.name || '';
        }
    } catch (e) {
        // ignore
    }
    return '';
}

function loadStockTotal(obatId, gudangId) {
    const cacheKey = String(obatId) + '|' + String(gudangId);
    const cached = stockCache.get(cacheKey);
    const now = Date.now();
    if (cached && cached.at && (now - cached.at) <= STOCK_CACHE_TTL_MS) {
        return $.Deferred().resolve({ total: cached.total, gudangName: cached.gudangName, cached: true }).promise();
    }

    return $.getJSON("{{ route('erm.stok-gudang.batch-details') }}", { obat_id: obatId, gudang_id: gudangId })
        .then(function(resp) {
            const data = resp.data || [];
            let total = 0;
            data.forEach(function(d) {
                if (typeof d.stok !== 'undefined') {
                    total += parseFloat(d.stok) || 0;
                } else if (d.stok_display) {
                    const num = d.stok_display.toString().replace(/[^0-9\-]/g, '');
                    total += parseFloat(num) || 0;
                }
            });
            const gudangName = getGudangNameById(gudangId);
            const resolvedTotal = (data.length === 0) ? 0 : total;
            stockCache.set(cacheKey, { total: resolvedTotal, gudangName: gudangName, at: Date.now() });
            return { total: resolvedTotal, gudangName: gudangName, cached: false };
        });
}

function escapeHtml(value) {
    return (value === null || typeof value === 'undefined')
        ? ''
        : String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
}

// Helper function to get default gudang for an item
function getDefaultGudangForItem(item) {
    // Determine transaction type based on item
    let transactionType = 'tindakan'; // default

    // Check if this is an obat/resep item
    if (item.billable_type === 'App\\Models\\ERM\\ResepFarmasi' ||
        item.billable_type === 'App\\Models\\ERM\\Racikan' ||
        (item.deskripsi && item.deskripsi.toLowerCase().includes('obat')) ||
        (item.nama_item && item.nama_item.toLowerCase().includes('obat'))) {
        transactionType = 'resep';
    }
    // Check if this is a RiwayatTindakan with kode tindakan obat
    else if (item.billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
        // For riwayat tindakan, use kode_tindakan transaction type for obat stock
        transactionType = 'kode_tindakan';
    }
    // Check if this is a bundled obat from tindakan
    else if (item.billable_type === 'App\\Models\\ERM\\Obat' &&
        item.keterangan && item.keterangan.includes('Obat Bundled:')) {
        transactionType = 'tindakan';
    }

    return window.gudangData.mappings[transactionType] ||
        (window.gudangData.gudangs.length ? window.gudangData.gudangs[0].id : null);
}

function getSelectedGudangIdForItem(itemId, item) {
    let gudangId = $('select.gudang-selector[data-item-id="' + itemId + '"]').val();
    if (!gudangId && item && item.selected_gudang_id) gudangId = item.selected_gudang_id;
    if (!gudangId && item) gudangId = getDefaultGudangForItem(item);
    return gudangId;
}

function buildObatRowsForStockModal(item) {
    // Returns: [{ obatId, name, needed }]
    const rows = [];

    function toQty(value, fallback) {
        const num = (value === null || typeof value === 'undefined') ? NaN : parseFloat(value);
        if (!isNaN(num) && isFinite(num)) {
            const abs = Math.abs(num);
            return abs > 0 ? abs : (fallback || 0);
        }
        return fallback || 0;
    }

    if (item.is_racikan) {
        const bungkusFallback = toQty(item.racikan_bungkus, 1) || 1;

        // Prefer components list (most reliable: contains obat_id + nama + stok_dikurangi)
        if (Array.isArray(item.racikan_components) && item.racikan_components.length) {
            item.racikan_components.forEach(function(c, idx) {
                if (!c || !c.obat_id) return;
                const obatId = c.obat_id;
                const fallbackName = (Array.isArray(item.racikan_obat_list) && item.racikan_obat_list[idx])
                    ? String(item.racikan_obat_list[idx])
                    : ('Obat #' + obatId);
                const name = (c.nama ? String(c.nama) : fallbackName).toString().trim() || fallbackName;
                const sd = (typeof c.stok_dikurangi !== 'undefined' && c.stok_dikurangi !== null)
                    ? toQty(c.stok_dikurangi, 0)
                    : 0;
                const needed = (sd && sd > 0) ? sd : bungkusFallback;
                rows.push({ obatId: obatId, name: name, needed: needed });
            });
            if (rows.length) return rows;
        }

        // Fallback: use explicit obat ids list if present
        if (Array.isArray(item.racikan_obat_ids) && item.racikan_obat_ids.length) {
            const obatIds = item.racikan_obat_ids.slice();
            const obatNames = Array.isArray(item.racikan_obat_list) ? item.racikan_obat_list.slice() : [];

            obatIds.forEach(function(obatId, idx) {
                const name = (obatNames[idx] || '').toString().trim() || ('Obat #' + obatId);
                rows.push({ obatId: obatId, name: name, needed: bungkusFallback });
            });

            return rows;
        }
    }

    // Non-racikan: try to locate obatId
    let obatId = null;
    let name = item.nama_item || '';

    try {
        if (item.billable_type === 'App\\Models\\ERM\\ResepFarmasi' && item.billable && item.billable.obat) {
            obatId = item.billable.obat.id;
            name = item.billable.obat.nama_obat || name;
        } else if (item.billable_type === 'App\\Models\\ERM\\Obat' && item.billable_id) {
            obatId = item.billable_id;
        } else if (item.obat_id) {
            obatId = item.obat_id;
        } else if (item.billable && item.billable.obat) {
            obatId = item.billable.obat.id;
            name = item.billable.obat.nama_obat || name;
        } else {
            const m = (item.id || '').toString().match(/^obat-(\d+)$/);
            if (m) obatId = m[1];
        }
    } catch (e) {
        obatId = null;
    }

    if (!obatId) return rows;

    // Needed qty: prefer billable.jumlah (if present), fallback to qty
    let sd = null;
    try {
        if (item.billable && typeof item.billable.jumlah !== 'undefined' && item.billable.jumlah !== null) {
            sd = toQty(item.billable.jumlah, null);
        }
    } catch (e) { }

    const qtyFallback = toQty(item.qty, 1) || 1;
    const needed = (sd !== null && !isNaN(sd) && sd > 0) ? sd : qtyFallback;

    rows.push({ obatId: obatId, name: (name || ('Obat #' + obatId)), needed: needed });
    return rows;
}

function renderStockRowsToHtml(rows, stockResults) {
    // stockResults index-aligned with rows
    function formatQty(value) {
        const num = parseFloat(value);
        if (isNaN(num) || !isFinite(num)) return '0';
        if (Math.abs(num - Math.round(num)) < 0.000001) return String(Math.round(num));
        return String(num);
    }

    let html = '<div class="table-responsive">' +
        '<table class="table table-sm table-bordered mb-0">' +
        '<thead><tr><th>Obat/Produk</th><th class="text-right">Stok Dibutuhkan</th><th class="text-right">Stok Tersedia</th></tr></thead><tbody>';

    rows.forEach(function(row, idx) {
        const r = stockResults[idx];
        const stok = (r && typeof r.total !== 'undefined') ? r.total : 0;

        const stokNum = isNaN(Number(stok)) ? 0 : Number(stok);
        const neededNum = isNaN(Number(row.needed)) ? 0 : Number(row.needed);
        const isLow = (neededNum > 0) && (stokNum < neededNum);
        const trClass = isLow ? ' class="table-danger"' : '';

        html += '<tr' + trClass + '>' +
            '<td>' + escapeHtml(row.name) + '</td>' +
                '<td class="text-right">' + formatQty(row.needed || 0) + '</td>' +
            '<td class="text-right">' + stok + '</td>' +
            '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function loadRiwayatTindakanObatRows(riwayatTindakanId) {
    if (!riwayatTindakanId) {
        return $.Deferred().resolve([]).promise();
    }

    return $.getJSON("{{ route('finance.billing.riwayat-tindakan-obats') }}", { riwayat_tindakan_id: riwayatTindakanId })
        .then(function(resp) {
            const data = (resp && resp.data) ? resp.data : [];
            const rows = [];
            data.forEach(function(d) {
                if (!d || !d.obat_id) return;
                const needed = (typeof d.qty !== 'undefined' && d.qty !== null) ? (parseFloat(d.qty) || 0) : 0;
                rows.push({ obatId: d.obat_id, name: (d.obat_nama || ('Obat #' + d.obat_id)), needed: needed });
            });
            return {
                rows: rows,
                suggestedGudangId: (resp && resp.suggested_gudang_id) ? resp.suggested_gudang_id : null
            };
        }, function() {
            return { rows: [], suggestedGudangId: null };
        });
}

function openStockInfoModalForItem(itemId, item) {
    // Assumes modal HTML is already present in DOM
    $('#stockInfoItemName').text(item && item.nama_item ? item.nama_item : (itemId || '-'));
    $('#stockInfoGudangName').text('-');
    $('#stockInfoContent').html('<div class="text-muted">Memuat stok...</div>');
    $('#stockInfoModal').modal('show');

    if (!item) {
        $('#stockInfoContent').html('<div class="text-danger">Item tidak ditemukan.</div>');
        return;
    }

    let gudangId = getSelectedGudangIdForItem(itemId, item);
    const gudangName = getGudangNameById(gudangId);
    $('#stockInfoGudangName').text(gudangName || '-');

    const rows = buildObatRowsForStockModal(item);

    if (!gudangId) {
        $('#stockInfoContent').html('<div class="text-danger">Gudang belum dipilih.</div>');
        return;
    }

    // Special case: tindakan (RiwayatTindakan) should resolve its obat needs from pivot table
    if (!rows.length && item.billable_type === 'App\\Models\\ERM\\RiwayatTindakan' && item.billable_id) {
        $('#stockInfoContent').html('<div class="text-muted">Memuat stok tindakan...</div>');
        loadRiwayatTindakanObatRows(item.billable_id)
            .then(function(resp) {
                const pivotRows = resp && resp.rows ? resp.rows : [];
                const suggestedGudangId = resp && resp.suggestedGudangId ? resp.suggestedGudangId : null;

                // If user didn't explicitly choose a gudang, prefer suggested mapping
                const explicitGudangFromItem = (item && item.selected_gudang_id) ? item.selected_gudang_id : null;
                if (!explicitGudangFromItem && suggestedGudangId) {
                    gudangId = suggestedGudangId;
                    const resolvedName = getGudangNameById(gudangId);
                    $('#stockInfoGudangName').text(resolvedName || '-');
                    try { item.selected_gudang_id = gudangId; } catch (e) { }
                }

                if (!pivotRows || !pivotRows.length) {
                    $('#stockInfoContent').html('<div class="text-muted">Item ini tidak memiliki stok (tidak ada obat terkait).</div>');
                    return;
                }

                const requests = pivotRows.map(function(r) {
                    return loadStockTotal(r.obatId, gudangId);
                });

                $.when.apply($, requests)
                    .done(function() {
                        const args = Array.prototype.slice.call(arguments);
                        const normalized = (requests.length === 1) ? [arguments[0]] : args;
                        const html = renderStockRowsToHtml(pivotRows, normalized);
                        $('#stockInfoContent').html(html);
                    })
                    .fail(function() {
                        $('#stockInfoContent').html('<div class="text-danger">Gagal memuat stok. Coba lagi.</div>');
                    });
            });
        return;
    }

    if (!rows.length) {
        $('#stockInfoContent').html('<div class="text-muted">Item ini tidak memiliki stok (bukan obat/produk).</div>');
        return;
    }

    const requests = rows.map(function(r) {
        return loadStockTotal(r.obatId, gudangId);
    });

    $.when.apply($, requests)
        .done(function() {
            const args = Array.prototype.slice.call(arguments);
            const normalized = (requests.length === 1) ? [arguments[0]] : args;
            const html = renderStockRowsToHtml(rows, normalized);
            $('#stockInfoContent').html(html);
        })
        .fail(function() {
            $('#stockInfoContent').html('<div class="text-danger">Gagal memuat stok. Coba lagi.</div>');
        });
}

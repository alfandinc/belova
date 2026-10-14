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
        return $.Deferred().resolve({ total: cached.total, expired: cached.expired || [], expiredQty: cached.expiredQty || 0, gudangName: cached.gudangName, cached: true }).promise();
    }

    return $.getJSON("{{ $billingUrls['batchDetails'] }}", { obat_id: obatId, gudang_id: gudangId })
        .then(function(resp) {
            const data = resp.data || [];
            let total = 0;
            const expired = [];
            data.forEach(function(d) {
                let stok = 0;
                if (typeof d.stok !== 'undefined') {
                    stok = parseFloat(d.stok) || 0;
                } else if (d.stok_display) {
                    stok = parseFloat(d.stok_display.toString().replace(/[^0-9\-]/g, '')) || 0;
                }
                // Expired batches kept aside: payment still takes them, after the usable ones (with a warning).
                // The modal lists them and offers an ED correction.
                if (d.is_expired) {
                    if (stok > 0) {
                        expired.push({ id: d.id, batch: d.batch, stok: stok, ed: d.expiration_date, edRaw: d.expiration_date_raw });
                    }
                    return;
                }
                total += stok;
            });
            const gudangName = getGudangNameById(gudangId);
            const resolvedTotal = (data.length === 0) ? 0 : total;
            const expiredQty = expired.reduce(function(sum, b) { return sum + (Number(b.stok) || 0); }, 0);
            stockCache.set(cacheKey, { total: resolvedTotal, expired: expired, expiredQty: expiredQty, gudangName: gudangName, at: Date.now() });
            return { total: resolvedTotal, expired: expired, expiredQty: expiredQty, gudangName: gudangName, cached: false };
        });
}

// Drop cached stok so the modal and row warnings re-read it after an ED correction
function invalidateStockCaches() {
    try { stockCache.clear(); } catch (e) { }
    try { itemStockWarningCache.clear(); } catch (e) { }
    try { stockWarningServerHintsValid = false; } catch (e) { }
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

function getItemSpesialisasiId(item) {
    if (!item) return null;

    try {
        if (item.spesialis_id) return item.spesialis_id;
        if (item.tindakan && item.tindakan.spesialis_id) return item.tindakan.spesialis_id;
        if (item.billable && item.billable.spesialis_id) return item.billable.spesialis_id;
        if (item.billable && item.billable.tindakan && item.billable.tindakan.spesialis_id) {
            return item.billable.tindakan.spesialis_id;
        }
    } catch (e) {
        return null;
    }

    return null;
}

// Lab test / paket rows: their obat are reduced from the 'lab' gudang mapping when the invoice is made
function isLabBillingItem(item) {
    return !!item && (item.billable_type === 'App\\Models\\ERM\\LabPermintaan' || item.billable_type === 'App\\Models\\ERM\\LabPaket');
}

// Helper function to get default gudang for an item
function getDefaultGudangForItem(item) {
    const isEventBilling = !!(window.billingPage && window.billingPage.isEventBilling);

    // Determine transaction type based on item
    let transactionType = 'tindakan'; // default

    if (isLabBillingItem(item)) {
        transactionType = 'lab';
    }
    // Check if this is an obat/resep item
    else if (item.billable_type === 'App\\Models\\ERM\\ResepFarmasi' ||
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

    if (transactionType === 'kode_tindakan') {
        const spesialisId = getItemSpesialisasiId(item);
        if (isEventBilling && spesialisId && window.gudangData.eventSpesialisasiMappings && window.gudangData.eventSpesialisasiMappings.kode_tindakan) {
            const mappedGudangId = window.gudangData.eventSpesialisasiMappings.kode_tindakan[String(spesialisId)] || window.gudangData.eventSpesialisasiMappings.kode_tindakan[spesialisId];
            if (mappedGudangId) return mappedGudangId;
        }

        if (spesialisId && window.gudangData.spesialisasiMappings && window.gudangData.spesialisasiMappings.kode_tindakan) {
            const mappedGudangId = window.gudangData.spesialisasiMappings.kode_tindakan[String(spesialisId)] || window.gudangData.spesialisasiMappings.kode_tindakan[spesialisId];
            if (mappedGudangId) return mappedGudangId;
        }
    }

    if (isEventBilling && window.gudangData.eventMappings && window.gudangData.eventMappings[transactionType]) {
        return window.gudangData.eventMappings[transactionType];
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

// Expired batches count as available (taken after the usable ones)
function renderStockRowsToHtml(rows, stockResults) {
    // stockResults index-aligned with rows
    function formatQty(value) {
        const num = parseFloat(value);
        if (isNaN(num) || !isFinite(num)) return '0';
        if (Math.abs(num - Math.round(num)) < 0.000001) return String(Math.round(num));
        return String(num);
    }

    const canKoreksiEd = !!(window.billingPage && window.billingPage.canKoreksiEd);
    let hasExpired = false;
    let pakaiEd = false;

    let html = '<div class="table-responsive">' +
        '<table class="table table-sm table-bordered mb-0">' +
        '<thead><tr><th>Obat/Produk</th><th class="text-right">Stok Dibutuhkan</th><th class="text-right">Stok Tersedia</th></tr></thead><tbody>';

    rows.forEach(function(row, idx) {
        const r = stockResults[idx];
        const stok = (r && typeof r.total !== 'undefined') ? r.total : 0;
        const expired = (r && Array.isArray(r.expired)) ? r.expired : [];
        const expiredQty = expired.reduce(function(sum, b) { return sum + (Number(b.stok) || 0); }, 0);

        const stokNum = isNaN(Number(stok)) ? 0 : Number(stok);
        const neededNum = isNaN(Number(row.needed)) ? 0 : Number(row.needed);
        const availNum = stokNum + expiredQty;
        const isLow = (neededNum > 0) && (availNum < neededNum);
        const usesEd = !isLow && (neededNum > 0) && (stokNum < neededNum);
        if (usesEd) pakaiEd = true;
        const trClass = isLow ? ' class="table-danger"' : (usesEd ? ' class="table-warning"' : '');

        html += '<tr' + trClass + '>' +
            '<td>' + escapeHtml(row.name) + '</td>' +
                '<td class="text-right">' + formatQty(row.needed || 0) + '</td>' +
            '<td class="text-right">' + formatQty(stok) +
                (expiredQty > 0 ? ' <span class="text-danger small">(' + formatQty(expiredQty) + ' expired)</span>' : '') +
            '</td>' +
            '</tr>';

        expired.forEach(function(b) {
            hasExpired = true;
            html += '<tr class="stock-expired-batch">' +
                '<td colspan="3" class="small bg-light">' +
                    '<div class="d-flex flex-wrap align-items-center">' +
                        '<span class="badge badge-danger mr-2">Expired</span>' +
                        '<span class="mr-3">Batch <strong>' + escapeHtml(b.batch || '-') + '</strong></span>' +
                        '<span class="mr-3">ED <strong>' + escapeHtml(b.ed || '-') + '</strong></span>' +
                        '<span class="mr-auto">Stok <strong>' + formatQty(b.stok) + '</strong></span>' +
                        (canKoreksiEd
                            ? '<span class="ed-fix-area">' +
                                '<button type="button" class="btn btn-sm btn-outline-primary btn-koreksi-ed" data-id="' + escapeHtml(b.id) + '" data-ed="' + escapeHtml(b.edRaw || '') + '"><i class="fas fa-calendar-alt"></i> Koreksi ED</button>' +
                              '</span>'
                            : '') +
                    '</div>' +
                '</td>' +
                '</tr>';
        });
    });

    html += '</tbody></table></div>';

    if (pakaiEd) {
        html += '<div class="alert alert-warning small py-2 mt-2 mb-0"><i class="fas fa-calendar-times mr-1"></i>' +
            'Stok layak pakai kurang untuk baris kuning: saat pembayaran, sisanya diambil dari <strong>batch yang sudah ED</strong> (setelah batch layak pakai habis).</div>';
    }
    if (hasExpired) {
        html += '<div class="small text-muted mt-2">' +
            'Batch yang sudah lewat ED ikut dihitung, tapi baru dipakai setelah batch layak pakai habis. ' +
            (canKoreksiEd
                ? 'Jika ED tercatat salah, cocokkan dengan kemasan/faktur lalu klik <strong>Koreksi ED</strong>.'
                : 'Jika ED tercatat salah, minta Farmasi/Admin mengoreksi ED batch tersebut.') +
            '</div>';
    }
    return html;
}

// Inline ED correction inside the Stok Tersedia modal (Admin/Farmasi/Beautician/Lab only; server enforces the role)
$(document).on('click', '#stockInfoModal .btn-koreksi-ed', function() {
    const btn = $(this);
    const area = btn.closest('.ed-fix-area');
    area.data('orig', area.html());
    area.html(
        '<span class="d-inline-flex align-items-center">' +
            '<input type="date" class="form-control form-control-sm ed-fix-input" style="width:150px;" value="' + escapeHtml(btn.data('ed') || '') + '">' +
            '<button type="button" class="btn btn-sm btn-primary ml-1 btn-koreksi-ed-save" data-id="' + escapeHtml(btn.data('id')) + '">Simpan</button>' +
            '<button type="button" class="btn btn-sm btn-secondary ml-1 btn-koreksi-ed-cancel">Batal</button>' +
        '</span>'
    );
    area.find('.ed-fix-input').trigger('focus');
});

$(document).on('click', '#stockInfoModal .btn-koreksi-ed-cancel', function() {
    const area = $(this).closest('.ed-fix-area');
    area.html(area.data('orig') || '');
});

$(document).on('click', '#stockInfoModal .btn-koreksi-ed-save', function() {
    const btn = $(this);
    const area = btn.closest('.ed-fix-area');
    const newEd = area.find('.ed-fix-input').val();
    if (!newEd) {
        Swal.fire({ icon: 'warning', title: 'ED belum diisi', text: 'Isi tanggal kadaluarsa sesuai kemasan/faktur.' });
        return;
    }

    btn.prop('disabled', true).text('Menyimpan...');
    $.post(window.billingPage.updateBatchExpUrl, {
        _token: $('meta[name="csrf-token"]').attr('content'),
        id: btn.data('id'),
        expiration_date: newEd
    }).done(function() {
        invalidateStockCaches();
        const ctx = $('#stockInfoModal').data('stock-context');
        if (ctx) {
            openStockInfoModalForItem(ctx.itemId, ctx.item);
        }
        try { updateStockWarningsForVisibleRows(); } catch (e) { }
        Swal.fire({ icon: 'success', title: 'ED disimpan', timer: 1500, showConfirmButton: false });
    }).fail(function(xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message)
            ? xhr.responseJSON.message
            : (xhr.status === 403 ? 'Anda tidak punya akses mengubah ED.' : 'Gagal menyimpan ED.');
        Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        btn.prop('disabled', false).text('Simpan');
    });
});

function loadRiwayatTindakanObatRows(riwayatTindakanId) {
    if (!riwayatTindakanId) {
        return $.Deferred().resolve([]).promise();
    }

    return $.getJSON("{{ $billingUrls['riwayatTindakanObats'] }}", {
        riwayat_tindakan_id: riwayatTindakanId,
        visitation_id: window.billingPage && window.billingPage.visitationId ? window.billingPage.visitationId : null
    })
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

function loadTindakanObatRows(tindakanId) {
    if (!tindakanId) {
        return $.Deferred().resolve({ rows: [], suggestedGudangId: null }).promise();
    }

    return $.getJSON("{{ $billingUrls['tindakanObats'] }}", {
        tindakan_id: tindakanId,
        visitation_id: window.billingPage && window.billingPage.visitationId ? window.billingPage.visitationId : null
    }).then(function(resp) {
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

function loadLabObatRows(billingId) {
    if (!billingId) {
        return $.Deferred().resolve({ rows: [], suggestedGudangId: null }).promise();
    }

    return $.getJSON("{{ $billingUrls['labObats'] }}", {
        billing_id: billingId,
        visitation_id: window.billingPage && window.billingPage.visitationId ? window.billingPage.visitationId : null
    }).then(function(resp) {
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
    $('#stockInfoModal').data('stock-context', { itemId: itemId, item: item });
    if (!$('#stockInfoModal').hasClass('show')) {
        $('#stockInfoModal').modal('show');
    }

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

    // Special case: tindakan rows should resolve their obat needs from backend.
    if (!rows.length && item.billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
        $('#stockInfoContent').html('<div class="text-muted">Memuat stok tindakan...</div>');
        getGroupedRiwayatTindakanObatRowsCached(
            (Array.isArray(item.grouped_billable_ids) && item.grouped_billable_ids.length)
                ? item.grouped_billable_ids
                : [item.billable_id]
        )
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

    if (isLabBillingItem(item)) {
        $('#stockInfoContent').html('<div class="text-muted">Memuat stok lab...</div>');
        loadLabObatRows(item.id)
            .then(function(resp) {
                const labRows = resp && resp.rows ? resp.rows : [];
                const suggestedGudangId = resp && resp.suggestedGudangId ? resp.suggestedGudangId : null;

                const explicitGudangFromItem = (item && item.selected_gudang_id) ? item.selected_gudang_id : null;
                if (!explicitGudangFromItem && suggestedGudangId) {
                    gudangId = suggestedGudangId;
                    $('#stockInfoGudangName').text(getGudangNameById(gudangId) || '-');
                }

                if (!labRows.length) {
                    $('#stockInfoContent').html('<div class="text-muted">Item ini tidak memiliki stok (tidak ada obat terkait).</div>');
                    return;
                }

                const requests = labRows.map(function(r) {
                    return loadStockTotal(r.obatId, gudangId);
                });

                $.when.apply($, requests)
                    .done(function() {
                        const args = Array.prototype.slice.call(arguments);
                        const normalized = (requests.length === 1) ? [arguments[0]] : args;
                        $('#stockInfoContent').html(renderStockRowsToHtml(labRows, normalized));
                    })
                    .fail(function() {
                        $('#stockInfoContent').html('<div class="text-danger">Gagal memuat stok. Coba lagi.</div>');
                    });
            });
        return;
    }

    if (!rows.length && item.billable_type === 'App\\Models\\ERM\\Tindakan') {
        $('#stockInfoContent').html('<div class="text-muted">Memuat stok tindakan...</div>');
        loadTindakanObatRows(item.billable_id)
            .then(function(resp) {
                const tindakanRows = ((resp && resp.rows) ? resp.rows : []).map(function(row) {
                    const qtyMultiplier = Math.max(1, parseFloat(item.qty) || 1);
                    return Object.assign({}, row, { needed: (parseFloat(row.needed) || 0) * qtyMultiplier });
                });
                const suggestedGudangId = resp && resp.suggestedGudangId ? resp.suggestedGudangId : null;

                const explicitGudangFromItem = (item && item.selected_gudang_id) ? item.selected_gudang_id : null;
                if (!explicitGudangFromItem && suggestedGudangId) {
                    gudangId = suggestedGudangId;
                    const resolvedName = getGudangNameById(gudangId);
                    $('#stockInfoGudangName').text(resolvedName || '-');
                    try { item.selected_gudang_id = gudangId; } catch (e) { }
                }

                if (!tindakanRows.length) {
                    $('#stockInfoContent').html('<div class="text-muted">Item ini tidak memiliki stok (tidak ada obat terkait).</div>');
                    return;
                }

                const requests = tindakanRows.map(function(r) {
                    return loadStockTotal(r.obatId, gudangId);
                });

                $.when.apply($, requests)
                    .done(function() {
                        const args = Array.prototype.slice.call(arguments);
                        const normalized = (requests.length === 1) ? [arguments[0]] : args;
                        const html = renderStockRowsToHtml(tindakanRows, normalized);
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

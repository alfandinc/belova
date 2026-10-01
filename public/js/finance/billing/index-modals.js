(function () {
    if (window.__financeBillingIndexModalsInitialized) return;

    function escapeHtml(unsafe) {
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getConfig() {
        return window.financeBillingIndexConfig || {};
    }

    function getBillingTable() {
        return window.billingTable || null;
    }

    function getMerchandiseErrorMessage(xhr, fallbackMessage) {
        if (xhr && xhr.responseJSON) {
            return xhr.responseJSON.message || xhr.responseJSON.error || fallbackMessage;
        }

        return fallbackMessage;
    }

    function ensureModalElementsExist() {
        return (
            document.getElementById('modalOldNotificationsFinance') &&
            document.getElementById('modalPdfPreview') &&
            document.getElementById('modalTerimaPembayaran')
        );
    }

    var merchandiseStockOutCatalog = null;

    function resetMerchandiseStockOutForm() {
        try { $('#form-merchandise-stock-out')[0].reset(); } catch (e) { }
        try { $('#merchandise-stock-out-item').val('').trigger('change'); } catch (e2) { }
        try { $('#merchandise-history-item').val('').trigger('change'); } catch (e3) { }
        try { $('#merchandise-stock-out-pasien').val(null).trigger('change'); } catch (e4) { }
        $('#merchandise-stock-out-pasien').prop('required', true);
        $('#merchandise-stock-out-reason').prop('required', false).val('');
        $('#merchandise-stock-out-qty').attr('max', '').val(1);
        $('#merchandise-stock-out-summary').text('Pilih item untuk melihat stok tersedia.');
        $('#merchandise-history-content').html('<div class="text-muted">Pilih merchandise untuk melihat riwayat.</div>');
        $('#merchandise-target-pasien').prop('checked', true);
        $('#tab-berikan-merchandise').tab('show');
        toggleMerchandiseStockOutTargetFields();
    }

    function toggleMerchandiseStockOutTargetFields() {
        var targetType = $('input[name="target_type"]:checked', '#form-merchandise-stock-out').val();
        var isPasien = targetType === 'pasien';

        $('#merchandise-pasien-field').toggleClass('d-none', !isPasien);
        $('#merchandise-reason-field').toggleClass('d-none', isPasien);
        $('#merchandise-stock-out-pasien').prop('required', isPasien);
        $('#merchandise-stock-out-reason').prop('required', !isPasien);
    }

    function renderMerchandiseStockOutSummary(item) {
        if (!item) {
            $('#merchandise-stock-out-summary').text('Pilih item untuk melihat stok tersedia.');
            $('#merchandise-stock-out-qty').attr('max', '');
            return;
        }

        var currentStock = parseInt(item.current_stock || 0, 10);
        var remaining = item.remaining_monthly_stock;
        var summary = 'Stok saat ini: ' + currentStock;
        if (remaining !== null && remaining !== undefined && remaining !== '') {
            summary += ' | Sisa limit bulanan: ' + remaining;
        } else {
            summary += ' | Tanpa limit bulanan';
        }

        $('#merchandise-stock-out-summary').text(summary);
        $('#merchandise-stock-out-qty').attr('max', Math.max(currentStock, 1));
    }

    function getSelectedMerchandiseStockOutItem() {
        var selectedId = String($('#merchandise-stock-out-item').val() || '');
        if (!selectedId || !Array.isArray(merchandiseStockOutCatalog)) {
            return null;
        }

        return merchandiseStockOutCatalog.find(function (item) {
            return String(item.id) === selectedId;
        }) || null;
    }

    function populateMerchandiseStockOutItems(items) {
        var $select = $('#merchandise-stock-out-item');
        var $historySelect = $('#merchandise-history-item');
        var options = '<option value="">Pilih merchandise</option>';

        (items || []).forEach(function (item) {
            options += '<option value="' + escapeHtml(item.id) + '">' + escapeHtml(item.name || '-') + '</option>';
        });

        $select.html(options).trigger('change');
        $historySelect.html(options).trigger('change');
    }

    function renderMerchandiseHistoryRows(rows) {
        if (!rows || !rows.length) {
            $('#merchandise-history-content').html('<div class="text-muted">Belum ada riwayat merchandise untuk item ini.</div>');
            return;
        }

        var html = '<table class="table table-sm table-bordered mb-0">'
            + '<thead><tr><th>Tanggal</th><th>Tipe</th><th>Qty</th><th>Stok Saat Itu</th><th>Catatan</th></tr></thead><tbody>';

        rows.forEach(function (row) {
            var typeText = String((row.type || '').toUpperCase() || '-');
            var typeClass = typeText === 'OUT' ? 'text-danger font-weight-bold' : (typeText === 'IN' ? 'text-success font-weight-bold' : '');
            html += '<tr>'
                + '<td>' + escapeHtml(row.tanggal || '-') + '</td>'
                + '<td class="' + typeClass + '">' + escapeHtml(typeText) + '</td>'
                + '<td>' + escapeHtml(row.qty || 0) + '</td>'
                + '<td>' + escapeHtml(row.current_stock || 0) + '</td>'
                + '<td>' + escapeHtml(row.notes || '-') + '</td>'
                + '</tr>';
        });

        html += '</tbody></table>';
        $('#merchandise-history-content').html(html);
    }

    function loadMerchandiseHistory(merchandiseId) {
        var cfg = getConfig();
        if (!merchandiseId) {
            $('#merchandise-history-content').html('<div class="text-muted">Pilih merchandise untuk melihat riwayat.</div>');
            return;
        }

        if (!cfg.marketingMasterMerchandiseBaseUrl) return;

        $('#merchandise-history-content').html('<div class="text-center text-muted"><span class="spinner-border spinner-border-sm mr-2"></span>Memuat riwayat...</div>');
        $.get(cfg.marketingMasterMerchandiseBaseUrl.replace(/\/$/, '') + '/' + merchandiseId + '/stock-history')
            .done(function (response) {
                renderMerchandiseHistoryRows(response && response.data ? response.data : []);
            })
            .fail(function () {
                $('#merchandise-history-content').html('<div class="text-danger">Gagal memuat riwayat merchandise.</div>');
            });
    }

    function loadMerchandiseStockOutCatalog() {
        var cfg = getConfig();
        if (Array.isArray(merchandiseStockOutCatalog)) {
            return $.Deferred().resolve(merchandiseStockOutCatalog).promise();
        }

        if (!cfg.marketingMasterMerchandiseDataUrl) {
            return $.Deferred().reject().promise();
        }

        return $.get(cfg.marketingMasterMerchandiseDataUrl).then(function (response) {
            merchandiseStockOutCatalog = response && response.data ? response.data : [];
            populateMerchandiseStockOutItems(merchandiseStockOutCatalog);
            return merchandiseStockOutCatalog;
        });
    }

    function initializeMerchandiseStockOutModal() {
        var cfg = getConfig();
        var $itemSelect = $('#merchandise-stock-out-item');
        var $historySelect = $('#merchandise-history-item');
        var $pasienSelect = $('#merchandise-stock-out-pasien');
        var $modal = $('#modalMerchandiseStockOut');

        if (!$modal.length) return;

        if (!$itemSelect.hasClass('select2-hidden-accessible')) {
            $itemSelect.select2({
                width: '100%',
                dropdownParent: $modal,
                placeholder: 'Pilih merchandise'
            });
        }

        if (!$pasienSelect.hasClass('select2-hidden-accessible') && cfg.ermPasiensSelect2Url) {
            $pasienSelect.select2({
                width: '100%',
                dropdownParent: $modal,
                placeholder: 'Cari nama pasien atau no. RM',
                ajax: {
                    url: cfg.ermPasiensSelect2Url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term || '' };
                    },
                    processResults: function (data) {
                        return data;
                    }
                },
                minimumInputLength: 1
            });
        }

        if (!$historySelect.hasClass('select2-hidden-accessible')) {
            $historySelect.select2({
                width: '100%',
                dropdownParent: $modal,
                placeholder: 'Pilih merchandise'
            });
        }
    }

    function openMerchandiseStockOutModal() {
        initializeMerchandiseStockOutModal();
        resetMerchandiseStockOutForm();

        loadMerchandiseStockOutCatalog().done(function () {
            $('#modalMerchandiseStockOut').modal('show');
        }).fail(function () {
            if (window.Swal) Swal.fire('Gagal', 'Tidak dapat memuat daftar merchandise.', 'error');
        });
    }

    function loadOldFinNotifications() {
        var cfg = getConfig();
        var oldFinNotifsUrl = cfg.oldFinNotifsUrl;
        if (!oldFinNotifsUrl) return;

        $('#old-fin-notifs-list').empty();
        $('#old-fin-notifs-empty').hide();
        $('#old-fin-notifs-loading').show();

        $.get(oldFinNotifsUrl, function (res) {
            $('#old-fin-notifs-loading').hide();
            var items = Array.isArray(res) ? res : (res && Array.isArray(res.data) ? res.data : []);
            if (!items || items.length === 0) {
                $('#old-fin-notifs-empty').show();
                return;
            }

            items.forEach(function (n) {
                var message = n.message || n.title || n.text || JSON.stringify(n);
                var time = n.created_at || '';
                var $li = $("<li class='list-group-item d-flex justify-content-between align-items-start'></li>");
                var left = '<div class="notif-content">';
                if (n.read) left += '<div class="text-muted">' + escapeHtml(message) + '</div>';
                else left += '<div class="font-weight-bold">' + escapeHtml(message) + '</div>';
                if (time) left += '<small class="text-muted">' + escapeHtml(time) + '</small>';
                left += '</div>';

                var right = '<div class="notif-actions">';
                if (!n.read) {
                    right += '<button class="btn btn-sm btn-primary btn-mark-read-fin" data-id="' + n.id + '">Tandai sudah dibaca</button>';
                } else {
                    right += '<span class="badge badge-secondary">Sudah dibaca</span>';
                }
                right += '</div>';

                $li.html(left + right);
                $('#old-fin-notifs-list').append($li);
            });
        }).fail(function () {
            $('#old-fin-notifs-loading').hide();
            $('#old-fin-notifs-empty').text('Gagal memuat notifikasi.').show();
        });
    }

    function openPdfPreviewByHref(href) {
        if (!href || href === '#') return;

        $('#pdf-preview-container').empty();
        $('#pdf-preview-loading').show();
        $('#pdf-preview-download').attr('href', href).show();

        var $iframe = $('<iframe>', {
            src: href,
            style: 'width:100%; height:80vh; border:0;'
        });
        $iframe.on('load', function () {
            $('#pdf-preview-loading').hide();
        });
        $('#pdf-preview-container').append($iframe);
        $('#modalPdfPreview').modal('show');
    }

    function parseMoney(val) {
        if (val === null || val === undefined) return 0;
        if (typeof val === 'number') return val;
        var s = String(val).trim();
        if (!s) return 0;
        s = s.replace(/[^0-9.,-]/g, '');
        if (s.indexOf('.') !== -1 && s.indexOf(',') !== -1) {
            s = s.replace(/\./g, '');
            s = s.replace(/,/g, '.');
        } else if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) {
            s = s.replace(/,/g, '.');
        }
        var f = parseFloat(s);
        return isNaN(f) ? 0 : f;
    }

    function openTerimaPembayaranModal(opts) {
        opts = opts || {};
        var id = opts.id;
        var amount = opts.amount;
        var paid = opts.paid || 0;
        var invoice = opts.invoice;

        var amtNum = parseMoney(amount);
        var paidNum = parseMoney(paid);
        var remaining = amtNum - paidNum;
        if (!isFinite(remaining) || remaining < 0) remaining = 0;

        var $label = $('#piutang_kekurangan_label');
        function formatKekuranganLabelValue(num) {
            try {
                return 'kurang : RP ' + Number(num).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            } catch (e) {
                return 'kurang : RP ' + num;
            }
        }
        function updateKekuranganLabel(rem) {
            if (!isFinite(rem) || rem <= 0) {
                $label.removeClass('text-danger').addClass('text-success').text('LUNAS');
            } else {
                $label.removeClass('text-success').addClass('text-danger').text(formatKekuranganLabelValue(rem));
            }
        }

        // Prefill jumlah with already paid amount (so user sees paid total)
        $('#piutang_amount').val(paidNum || 0);
        updateKekuranganLabel(remaining);

        $('#piutang_amount').off('input.piutang').on('input.piutang', function () {
            var entered = parseMoney($(this).val());
            var newRem = amtNum - (paidNum + (isNaN(entered) ? 0 : entered));
            if (!isFinite(newRem) || newRem < 0) newRem = 0;
            updateKekuranganLabel(newRem);
        });

        $('#piutang_id').val(id);
        $('#piutang_invoice').val(invoice);

        var now = new Date();
        var pad = function (n) { return n < 10 ? '0' + n : n; };
        var local = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
        $('#piutang_payment_date').val(local);

        $('#modalTerimaPembayaran').modal('show');
    }

    function submitTerimaPembayaran() {
        var cfg = getConfig();
        var csrfToken = cfg.csrfToken;
        var piutangReceiveBase = cfg.piutangReceiveBase;

        var id = $('#piutang_id').val();
        if (!id || !piutangReceiveBase) return;

        var payload = {
            amount: $('#piutang_amount').val(),
            payment_date: $('#piutang_payment_date').val(),
            payment_method: $('#piutang_payment_method').val(),
            _token: csrfToken
        };

        $.post(piutangReceiveBase + '/' + id + '/receive', payload)
            .done(function (res) {
                if (res && res.success) {
                    $('#modalTerimaPembayaran').modal('hide');
                    var dt = getBillingTable();
                    if (dt && typeof dt.ajax === 'object' && typeof dt.ajax.reload === 'function') {
                        dt.ajax.reload(null, false);
                    }
                    try {
                        if (typeof window.financeBillingFetchTabCounts === 'function') {
                            window.financeBillingFetchTabCounts();
                        }
                    } catch (e) { }
                    if (window.Swal) Swal.fire('Sukses', res.message || 'Pembayaran tercatat', 'success');
                } else {
                    if (window.Swal) Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal menyimpan pembayaran', 'error');
                }
            })
            .fail(function (xhr) {
                var msg = 'Terjadi kesalahan';
                try {
                    msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : msg;
                } catch (e) { }
                if (window.Swal) Swal.fire('Gagal', msg, 'error');
            });
    }

    function init() {
        if (window.__financeBillingIndexModalsInitialized) return;
        if (!ensureModalElementsExist()) return;
        window.__financeBillingIndexModalsInitialized = true;

        // Autofocus amount field when modal opens
        try {
            $('#modalTerimaPembayaran')
                .off('shown.bs.modal.piutangFocus')
                .on('shown.bs.modal.piutangFocus', function () {
                    setTimeout(function () {
                        var $input = $('#piutang_amount');
                        if ($input && $input.length) {
                            $input.trigger('focus');
                            try { $input[0].select(); } catch (e) { }
                        }
                    }, 150);
                });
        } catch (e) { }

        // Old notifications
        $(document).on('click', '#btn-old-notifs-finance', function () {
            $('#modalOldNotificationsFinance').modal('show');
            loadOldFinNotifications();
        });

        $(document).on('click', '.btn-mark-read-fin', function (e) {
            e.preventDefault();
            var cfg = getConfig();
            var csrfToken = cfg.csrfToken;
            var markFinReadBase = cfg.markFinReadBase;

            var $btn = $(this);
            var id = $btn.data('id');
            if (!id || !markFinReadBase) return;
            $btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: markFinReadBase + '/' + id + '/mark-read',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function (res) {
                    if (res && res.success) loadOldFinNotifications();
                    else {
                        alert('Gagal menandai notifikasi.');
                        $btn.prop('disabled', false).text('Tandai sudah dibaca');
                    }
                },
                error: function () {
                    alert('Gagal menandai notifikasi.');
                    $btn.prop('disabled', false).text('Tandai sudah dibaca');
                }
            });
        });

        $(document).on('click', '#btn-mark-all-finance', function () {
            var cfg = getConfig();
            var csrfToken = cfg.csrfToken;
            var oldFinNotifsUrl = cfg.oldFinNotifsUrl;
            var markFinReadBase = cfg.markFinReadBase;

            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) return;
            $.get(oldFinNotifsUrl, function (res) {
                var items = Array.isArray(res) ? res : (res && Array.isArray(res.data) ? res.data : []);
                var unread = items.filter(function (i) { return !i.read; });
                if (!unread.length) { alert('Tidak ada notifikasi belum dibaca.'); return; }
                var requests = [];
                unread.forEach(function (n) {
                    requests.push($.ajax({
                        url: markFinReadBase + '/' + n.id + '/mark-read',
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken }
                    }));
                });
                $.when.apply($, requests).always(function () { loadOldFinNotifications(); });
            });
        });

        // PDF preview
        $(document).on('click', '.billing-print-item', function (e) {
            var $el = $(this);
            var txt = ($el.text() || '').trim();
            if (!/cetak/i.test(txt)) return;
            e.preventDefault();

            var href = $el.attr('href') || $el.data('href') || '';
            var onclick = $el.attr('onclick') || '';

            if (href && href !== '#') {
                openPdfPreviewByHref(href);
                return;
            }

            if (onclick) {
                try {
                    var fn = new Function(onclick);
                    fn.call(this);
                } catch (err) {
                    console.error('Failed to execute onclick preview:', err);
                }
            }
        });

        $('#modalPdfPreview').on('hidden.bs.modal', function () {
            $('#pdf-preview-container').empty();
            $('#pdf-preview-loading').hide();
            $('#pdf-preview-download').attr('href', '#').hide();
        });

        // Terima pembayaran
        $(document).on('click', '.btn-terima-pembayaran', function () {
            var $btn = $(this);
            openTerimaPembayaranModal({
                id: $btn.data('id'),
                amount: $btn.data('amount'),
                paid: $btn.data('paid') || 0,
                invoice: $btn.data('invoice')
            });
        });

        $(document).on('click', '#btn-submit-terima', function () {
            submitTerimaPembayaran();
        });

        $(document).on('change', 'input[name="target_type"]', function () {
            toggleMerchandiseStockOutTargetFields();
        });

        $(document).on('change', '#merchandise-stock-out-item', function () {
            renderMerchandiseStockOutSummary(getSelectedMerchandiseStockOutItem());
        });

        $(document).on('change', '#merchandise-history-item', function () {
            loadMerchandiseHistory($(this).val());
        });

        $(document).on('shown.bs.tab', '#tab-riwayat-merchandise', function () {
            var historyId = $('#merchandise-history-item').val() || $('#merchandise-stock-out-item').val();
            if (historyId && !$('#merchandise-history-item').val()) {
                $('#merchandise-history-item').val(historyId).trigger('change');
                return;
            }

            loadMerchandiseHistory(historyId);
        });

        $(document).on('submit', '#form-merchandise-stock-out', function (e) {
            var cfg = getConfig();
            var pasienMerchandiseBaseUrl = cfg.ermPasienMerchandiseBaseUrl;
            var stockOutUrl = cfg.ermRawatjalanMerchandiseStockOutUrl;

            e.preventDefault();

            var targetType = $('input[name="target_type"]:checked', this).val();
            var selectedItem = getSelectedMerchandiseStockOutItem();
            var quantity = parseInt($('#merchandise-stock-out-qty').val() || 0, 10);
            var notes = $('#merchandise-stock-out-notes').val();

            if (!selectedItem) {
                if (window.Swal) Swal.fire('Validasi', 'Pilih merchandise terlebih dahulu.', 'warning');
                return;
            }

            if (!quantity || quantity < 1) {
                if (window.Swal) Swal.fire('Validasi', 'Qty keluar harus lebih dari 0.', 'warning');
                return;
            }

            if (quantity > parseInt(selectedItem.current_stock || 0, 10)) {
                if (window.Swal) Swal.fire('Validasi', 'Qty keluar melebihi stok yang tersedia.', 'warning');
                return;
            }

            var payload = {
                _token: cfg.csrfToken,
                merchandise_id: selectedItem.id,
                quantity: quantity,
                notes: notes
            };

            var request;
            if (targetType === 'pasien') {
                var pasienId = $('#merchandise-stock-out-pasien').val();
                if (!pasienId) {
                    if (window.Swal) Swal.fire('Validasi', 'Pilih pasien terlebih dahulu.', 'warning');
                    return;
                }
                if (!pasienMerchandiseBaseUrl) return;
                request = $.post(pasienMerchandiseBaseUrl.replace(/\/$/, '') + '/' + pasienId + '/merchandises', payload);
            } else {
                var reason = $.trim($('#merchandise-stock-out-reason').val() || '');
                if (!reason) {
                    if (window.Swal) Swal.fire('Validasi', 'Isi keperluan pengeluaran terlebih dahulu.', 'warning');
                    return;
                }
                if (!stockOutUrl) return;
                payload.reason = reason;
                request = $.post(stockOutUrl, payload);
            }

            $('#btn-submit-merchandise-stock-out').prop('disabled', true);

            request.done(function (response) {
                var message = response && response.message ? response.message : 'Pengeluaran merchandise berhasil disimpan.';
                $('#modalMerchandiseStockOut').modal('hide');
                merchandiseStockOutCatalog = null;
                try { if (window.billingTableUmum) window.billingTableUmum.ajax.reload(null, false); } catch (e2) { }
                try { if (window.billingTableAsuransi) window.billingTableAsuransi.ajax.reload(null, false); } catch (e3) { }
                if (window.Swal) Swal.fire('Berhasil', message, 'success');
            }).fail(function (xhr) {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: getMerchandiseErrorMessage(xhr, 'Pengeluaran merchandise gagal disimpan.') });
                }
            }).always(function () {
                $('#btn-submit-merchandise-stock-out').prop('disabled', false);
            });
        });

    }

    window.financeBillingIndexModals = {
        init: init,
        openPdfPreviewByHref: openPdfPreviewByHref,
        openTerimaPembayaranModal: openTerimaPembayaranModal,
        submitTerimaPembayaran: submitTerimaPembayaran,
        loadOldFinNotifications: loadOldFinNotifications,
        openMerchandiseStockOutModal: openMerchandiseStockOutModal
    };
})();

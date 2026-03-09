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

    function ensureModalElementsExist() {
        return (
            document.getElementById('modalOldNotificationsFinance') &&
            document.getElementById('modalPdfPreview') &&
            document.getElementById('modalTerimaPembayaran')
        );
    }

    function ensureDaftarKunjunganElementsExist() {
        return (
            document.getElementById('modalDaftarKunjunganBillingIndex') &&
            document.getElementById('form-daftar-kunjungan-billing-index')
        );
    }

    function setDateTodayIfEmpty($input) {
        try {
            if (!$input || !$input.length) return;
            if ($input.val()) return;
            if (window.moment) {
                $input.val(window.moment().format('YYYY-MM-DD'));
                return;
            }
        } catch (e) { }

        try {
            var d = new Date();
            var pad = function (n) { return n < 10 ? '0' + n : String(n); };
            var y = d.getFullYear();
            var m = pad(d.getMonth() + 1);
            var day = pad(d.getDate());
            $input.val(y + '-' + m + '-' + day);
        } catch (e2) { }
    }

    function applyDaftarKunjunganMode(mode) {
        mode = (mode || 'konsultasi').toString();
        $('#fb_mode').val(mode);

        if (mode === 'produk') {
            $('#modalDaftarKunjunganBillingIndexLabel').text('Daftarkan Kunjungan Beli Produk Pasien');
            $('#fb_jenis_kunjungan').val('2');
            $('#fb_waktu_group').hide();
            $('#fb_no_antrian_group').hide();
            $('#fb_waktu_kunjungan').val('');
            $('#fb_no_antrian').val('');
        } else if (mode === 'lab') {
            $('#modalDaftarKunjunganBillingIndexLabel').text('Daftarkan Kunjungan Laboratorium Pasien');
            $('#fb_jenis_kunjungan').val('3');
            $('#fb_waktu_group').hide();
            $('#fb_no_antrian_group').hide();
            $('#fb_waktu_kunjungan').val('');
            $('#fb_no_antrian').val('');
        } else {
            $('#modalDaftarKunjunganBillingIndexLabel').text('Daftarkan Kunjungan Pasien');
            $('#fb_jenis_kunjungan').val('1');
            $('#fb_waktu_group').show();
            $('#fb_no_antrian_group').show();
        }
    }

    function resetDaftarKunjunganForm() {
        try { $('#form-daftar-kunjungan-billing-index')[0].reset(); } catch (e) { }
        try { $('#fb_pasien_id').val(null).trigger('change'); } catch (e2) { }
        try {
            $('#fb_dokter_id')
                .empty()
                .append('<option value="">Pilih Dokter</option>')
                .prop('disabled', true)
                .trigger('change.select2');
        } catch (e3) { }
        try { $('#fb_no_antrian').val(''); } catch (e4) { }
    }

    var __daftarKunjunganInitialized = false;
    function initDaftarKunjunganModal() {
        if (__daftarKunjunganInitialized) return;
        if (!ensureDaftarKunjunganElementsExist()) return;

        __daftarKunjunganInitialized = true;

        var cfg = getConfig();
        var $modal = $('#modalDaftarKunjunganBillingIndex');
        var $form = $('#form-daftar-kunjungan-billing-index');

        // init select2 inside modal (static selects)
        try {
            $modal.find('select.select2').select2({ width: '100%', dropdownParent: $modal });
        } catch (e) { }

        // pasien select2 ajax
        try {
            if (cfg.ermPasiensSelect2Url) {
                $('#fb_pasien_id').select2({
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: 'Cari pasien (nama / RM / NIK)',
                    allowClear: true,
                    ajax: {
                        url: cfg.ermPasiensSelect2Url,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term || '' };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true
                    }
                });
            }
        } catch (e2) { }

        function cekAntrianIfNeeded() {
            var mode = ($('#fb_mode').val() || 'konsultasi').toString();
            if (mode !== 'konsultasi') return;

            var dokterId = $('#fb_dokter_id').val();
            var tanggal = $('#fb_tanggal_visitation').val();
            if (!dokterId || !tanggal) return;
            if (!cfg.ermCekAntrianUrl) return;

            $.get(cfg.ermCekAntrianUrl, { dokter_id: dokterId, tanggal: tanggal })
                .done(function (res) {
                    $('#fb_no_antrian').val((res && res.no_antrian) ? res.no_antrian : '');
                })
                .fail(function () {
                    $('#fb_no_antrian').val('');
                });
        }

        // klinik => load doctors
        $('#fb_klinik_id').off('change.fbKlinik').on('change.fbKlinik', function () {
            var klinikId = $(this).val();
            var $dokter = $('#fb_dokter_id');

            $dokter.empty().append('<option value="">Loading...</option>').prop('disabled', true);
            if (!klinikId || !cfg.getDoktersBaseUrl) {
                $dokter.empty().append('<option value="">Pilih Dokter</option>').prop('disabled', true).trigger('change.select2');
                return;
            }

            $.ajax({
                url: cfg.getDoktersBaseUrl.replace(/\/$/, '') + '/' + klinikId,
                type: 'GET'
            }).done(function (data) {
                $dokter.empty().append('<option value="">Pilih Dokter</option>');
                if (data && data.length) {
                    $.each(data, function (_, dokter) {
                        var dokterName = (dokter.user && dokter.user.name) ? dokter.user.name : 'Unknown Doctor';
                        var spesialis = (dokter.spesialisasi && dokter.spesialisasi.nama) ? ' (' + dokter.spesialisasi.nama + ')' : '';
                        $dokter.append('<option value="' + dokter.id + '">' + escapeHtml(dokterName + spesialis) + '</option>');
                    });
                } else {
                    $dokter.append('<option value="" disabled>Tidak ada dokter di klinik ini</option>');
                }
                $dokter.prop('disabled', false).trigger('change.select2');
            }).fail(function () {
                $dokter.empty().append('<option value="">Pilih Dokter</option>').prop('disabled', true).trigger('change.select2');
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data dokter' });
            });
        });

        // dokter/date => cek antrian
        $('#fb_dokter_id, #fb_tanggal_visitation').off('change.fbAntrian').on('change.fbAntrian', function () {
            cekAntrianIfNeeded();
        });

        // submit
        $form.off('submit.fbSubmit').on('submit.fbSubmit', function (e) {
            e.preventDefault();

            var mode = ($('#fb_mode').val() || 'konsultasi').toString();
            var url = cfg.ermVisitationsStoreUrl;
            if (mode === 'produk') url = cfg.ermVisitationsProdukStoreUrl;
            else if (mode === 'lab') url = cfg.ermVisitationsLabStoreUrl;

            if (!url) return;

            $.ajax({
                url: url,
                type: 'POST',
                data: $form.serialize()
            }).done(function (res) {
                $modal.modal('hide');
                resetDaftarKunjunganForm();

                var msg = (res && res.message) ? res.message : 'Kunjungan berhasil disimpan.';
                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: msg, confirmButtonText: 'OK' })
                        .then(function () {
                            try { if (window.billingTableUmum) window.billingTableUmum.ajax.reload(null, false); } catch (e1) { }
                            try { if (window.billingTableAsuransi) window.billingTableAsuransi.ajax.reload(null, false); } catch (e2) { }
                            try { if (typeof window.financeBillingFetchTabCounts === 'function') window.financeBillingFetchTabCounts(); } catch (e3) { }
                        });
                } else {
                    try { if (window.billingTableUmum) window.billingTableUmum.ajax.reload(null, false); } catch (e4) { }
                    try { if (window.billingTableAsuransi) window.billingTableAsuransi.ajax.reload(null, false); } catch (e5) { }
                    try { if (typeof window.financeBillingFetchTabCounts === 'function') window.financeBillingFetchTabCounts(); } catch (e6) { }
                }
            }).fail(function (xhr) {
                var msg = 'Terjadi kesalahan. Pastikan semua data valid.';
                try {
                    if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                } catch (e7) { }
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonText: 'OK' });
            });
        });

        // cleanup on close
        $modal.off('hidden.bs.modal.fbCleanup').on('hidden.bs.modal.fbCleanup', function () {
            resetDaftarKunjunganForm();
            applyDaftarKunjunganMode('konsultasi');
        });

        // default mode
        applyDaftarKunjunganMode('konsultasi');
    }

    function openDaftarKunjunganModal(mode) {
        initDaftarKunjunganModal();
        if (!ensureDaftarKunjunganElementsExist()) return;

        applyDaftarKunjunganMode(mode);
        setDateTodayIfEmpty($('#fb_tanggal_visitation'));
        $('#modalDaftarKunjunganBillingIndex').modal('show');
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
        $(document).on('click', '.invoice-cell .dropdown-item', function (e) {
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

        // Daftarkan kunjungan (billing index)
        initDaftarKunjunganModal();
    }

    window.financeBillingIndexModals = {
        init: init,
        openPdfPreviewByHref: openPdfPreviewByHref,
        openTerimaPembayaranModal: openTerimaPembayaranModal,
        submitTerimaPembayaran: submitTerimaPembayaran,
        loadOldFinNotifications: loadOldFinNotifications,
        openDaftarKunjunganModal: openDaftarKunjunganModal
    };
})();

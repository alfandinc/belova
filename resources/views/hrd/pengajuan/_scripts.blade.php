{{-- Shared styles + JS helpers for the pengajuan pages (lembur, libur, tidak masuk). --}}
<style>
    .row-needs-action > td { background-color: rgba(255, 193, 7, .12) !important; }
    .row-needs-action > td:first-child { box-shadow: inset 3px 0 0 #ffc107; }
    .pengajuan-tabs .nav-link .badge { vertical-align: middle; }
    .approval-summary { background: rgba(0, 0, 0, .03); }
    .approval-summary:empty { display: none; }
    .pengajuan-legend { font-size: .8rem; }
    .pengajuan-legend .swatch { display: inline-block; width: 12px; height: 12px; vertical-align: middle; background: rgba(255, 193, 7, .35); border-left: 3px solid #ffc107; }
    #dateRangeFilter { width: 230px; }
    @media (max-width: 575.98px) {
        .pengajuan-toolbar { width: 100%; margin-top: .5rem; }
        .pengajuan-toolbar #dateRangeFilter { flex: 1 1 auto; width: auto; }
    }
</style>
<script>
window.Pengajuan = (function ($) {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    // Report DataTables load errors through our own handler instead of a browser alert
    $.fn.dataTable.ext.errMode = 'none';

    var LANG_DT = {
        processing: '<i class="fa fa-spinner fa-spin mr-1"></i> Memuat...',
        search: 'Cari:',
        lengthMenu: 'Tampilkan _MENU_',
        info: '_START_ - _END_ dari _TOTAL_ pengajuan',
        infoEmpty: 'Tidak ada data',
        infoFiltered: '(disaring dari _MAX_)',
        zeroRecords: 'Tidak ada data yang cocok',
        emptyTable: 'Belum ada pengajuan pada rentang tanggal ini',
        paginate: { first: 'Pertama', last: 'Terakhir', next: '&rsaquo;', previous: '&lsaquo;' }
    };

    function escapeHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function errorMessage(xhr, fallback) {
        var r = xhr && xhr.responseJSON;
        if (r && r.errors) {
            var msgs = [];
            $.each(r.errors, function (k, v) { msgs.push(escapeHtml($.isArray(v) ? v[0] : v)); });
            if (msgs.length) return msgs.join('<br>');
        }
        if (xhr) {
            if (xhr.status === 0) return 'Tidak dapat terhubung ke server. Periksa koneksi Anda.';
            if (xhr.status === 419) return 'Sesi Anda telah berakhir. Silakan muat ulang halaman.';
            if (xhr.status === 413) return 'Ukuran file terlalu besar.';
            if (r && r.message && xhr.status < 500) return escapeHtml(r.message);
            if (xhr.status === 403) return 'Anda tidak memiliki akses untuk tindakan ini.';
            if (xhr.status === 404) return 'Data tidak ditemukan.';
        }
        return fallback || 'Terjadi kesalahan pada server. Silakan coba lagi.';
    }

    function showError(xhr, title) {
        Swal.fire({ icon: 'error', title: title || 'Gagal', html: errorMessage(xhr) });
    }

    function toast(msg, icon) {
        Swal.fire({ toast: true, position: 'top-end', icon: icon || 'success', title: msg, showConfirmButton: false, timer: 2500 });
    }

    function setBusy($btn, busy, busyText) {
        if (busy) {
            $btn.data('label', $btn.html()).prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i>' + (busyText === undefined ? ' Memproses...' : busyText));
        } else {
            $btn.prop('disabled', false);
            if ($btn.data('label') !== undefined) $btn.html($btn.data('label'));
        }
    }

    // ---- Inline form errors ----
    function clearFieldErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.js-error').remove();
    }

    function setFieldError($input, msg) {
        $input.addClass('is-invalid');
        $input.siblings('.invalid-feedback.js-error').remove();
        $('<div class="invalid-feedback js-error"></div>').text(msg).insertAfter($input);
    }

    function clearFieldError($input) {
        $input.removeClass('is-invalid');
        $input.siblings('.invalid-feedback.js-error').remove();
    }

    // Map Laravel 422 validation errors onto the form fields; returns true if any were shown inline
    function applyServerErrors($form, xhr) {
        var r = xhr && xhr.responseJSON, shown = false;
        if (xhr && xhr.status === 422 && r && r.errors) {
            $.each(r.errors, function (name, msgs) {
                var $i = $form.find('[name="' + name + '"]');
                if ($i.length) { setFieldError($i, $.isArray(msgs) ? msgs[0] : msgs); shown = true; }
            });
        }
        return shown;
    }

    // ---- Date range filter ----
    function initDateRange($input, start, end, onChange) {
        var range = { start: moment(start), end: moment(end) };
        $input.daterangepicker({
            startDate: range.start,
            endDate: range.end,
            autoApply: true,
            locale: {
                format: 'DD/MM/YYYY', separator: ' - ', firstDay: 1, customRangeLabel: 'Pilih Tanggal',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            },
            ranges: {
                'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                's.d Bulan Depan': [moment().startOf('month'), moment().add(1, 'month').endOf('month')],
                'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                'Tahun Ini': [moment().startOf('year'), moment().endOf('year')]
            }
        }, function (s, e) {
            range.start = s;
            range.end = e;
            onChange();
        });
        return range;
    }

    // ---- DataTables ----
    function dataTable(selector, url, range, columns) {
        var table = $(selector).DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            order: [],
            ajax: {
                url: url,
                data: function (d) {
                    d.date_start = range.start.format('YYYY-MM-DD');
                    d.date_end = range.end.format('YYYY-MM-DD');
                }
            },
            columns: columns,
            columnDefs: [
                { targets: -1, className: 'text-nowrap', responsivePriority: 1 },
                { targets: 0, width: '40px' }
            ],
            language: LANG_DT
        });
        table.on('error.dt', function () {
            toast('Gagal memuat data. Silakan muat ulang halaman.', 'error');
        });
        return table;
    }

    function reloadTables(tables) {
        $.each(tables, function (_, t) { if (t) t.ajax.reload(null, false); });
    }

    function rowData(el) {
        var $tr = $(el).closest('tr');
        if ($tr.hasClass('child')) $tr = $tr.prev();
        var $table = $tr.closest('table');
        if (!$table.length || !$.fn.dataTable.isDataTable($table)) return {};
        return $table.DataTable().row($tr).data() || {};
    }

    function summaryHtml(d) {
        // Values come from the server already escaped (alasan, employee_nama) or server-built HTML (tanggal)
        var parts = [];
        if (d.employee_nama) parts.push('<div class="font-weight-bold mb-1"><i class="fas fa-user mr-1 text-muted"></i>' + d.employee_nama + '</div>');
        var tgl = d.tanggal_range || d.tanggal;
        if (tgl) parts.push('<div class="mb-1">' + tgl + '</div>');
        if (d.alasan) parts.push('<div class="text-muted"><i class="fas fa-comment-alt mr-1"></i>' + d.alasan + '</div>');
        return parts.join('');
    }

    // ---- Tabs ----
    function initTabs(storageKey, preferredPane) {
        var $tabs = $('.pengajuan-tabs a[data-toggle="tab"]');
        if (!$tabs.length) return;
        $tabs.on('shown.bs.tab', function (e) {
            var api = $.fn.dataTable.tables({ visible: true, api: true });
            api.columns.adjust();
            if (api.responsive) api.responsive.recalc();
            try { localStorage.setItem(storageKey, $(e.target).attr('href')); } catch (err) {}
        });
        var $target = preferredPane ? $tabs.filter('[href="#' + preferredPane + '"]') : $();
        if (!$target.length) {
            try {
                var saved = localStorage.getItem(storageKey);
                if (saved) $target = $tabs.filter('[href="' + saved + '"]');
            } catch (err) {}
        }
        if (!$target.length) $target = $tabs.first();
        $target.tab('show');
    }

    function decrementBadge(selector) {
        var $b = $(selector);
        if (!$b.length) return;
        var n = (parseInt($b.first().text(), 10) || 0) - 1;
        if (n > 0) $b.text(n); else $b.remove();
    }

    // ---- Detail modal ----
    function bindDetail(trigger, urlFn, modalSelector) {
        var $modal = $(modalSelector);
        $(document).on('click', trigger, function () {
            var $btn = $(this);
            setBusy($btn, true, '');
            $.get(urlFn($btn.data('id')))
                .done(function (html) {
                    $modal.find('.modal-body').html(html);
                    $modal.modal('show');
                })
                .fail(function (xhr) { showError(xhr); })
                .always(function () { setBusy($btn, false); });
        });
    }

    // ---- Partial approval ("approve fewer days / hours") ----
    function hmToMinutes(hm) {
        var p = String(hm || '').split(':');
        return parseInt(p[0], 10) * 60 + parseInt(p[1], 10);
    }

    function formatMinutes(min) {
        var h = Math.floor(min / 60), m = min % 60, out = [];
        if (h) out.push(h + ' jam');
        if (m) out.push(m + ' menit');
        return out.join(' ') || '0 menit';
    }

    // Evaluates the adjust inputs: {ok, partial, amount, total, message}
    function evaluateAdjust($box) {
        var type = $box.data('adjust'), orig = $box.data('orig') || {};
        var $inputs = $box.find('input'), a = $inputs.eq(0).val(), b = $inputs.eq(1).val();

        if (type === 'date') {
            var total = dayCount(orig.start, orig.end);
            if (!a || !b) return { ok: false, message: 'Isi tanggal mulai dan selesai yang disetujui.' };
            if (b < a) return { ok: false, message: 'Tanggal selesai tidak boleh sebelum tanggal mulai.' };
            if (a < orig.start || b > orig.end) return { ok: false, message: 'Tanggal harus di dalam rentang pengajuan.' };
            var days = dayCount(a, b);
            return { ok: true, partial: days < total, amount: days, total: total,
                message: days < total ? 'Disetujui sebagian: <strong>' + days + ' dari ' + total + ' hari</strong>.' : 'Semua ' + total + ' hari disetujui.' };
        }

        // time: offsets from the requested start so windows crossing midnight work
        var s0 = hmToMinutes(orig.start), dur = ((hmToMinutes(orig.end) - s0 + 1440) % 1440) || 1440;
        if (!a || !b) return { ok: false, message: 'Isi jam mulai dan selesai yang disetujui.' };
        var offA = (hmToMinutes(a) - s0 + 1440) % 1440, offB = ((hmToMinutes(b) - s0 + 1440) % 1440) || 1440;
        if (!(offA < offB && offB <= dur)) {
            return { ok: false, message: 'Jam harus di dalam jam pengajuan (' + orig.start + ' - ' + orig.end + ') dan jam selesai setelah jam mulai.' };
        }
        var minutes = offB - offA;
        return { ok: true, partial: minutes < dur, amount: minutes, total: dur,
            message: minutes < dur ? 'Disetujui sebagian: <strong>' + formatMinutes(minutes) + ' dari ' + formatMinutes(dur) + '</strong>.' : 'Semua ' + formatMinutes(dur) + ' disetujui.' };
    }

    function refreshApproveLabel($modal) {
        var $box = $modal.find('.approval-adjust');
        var partial = $box.length && $box.data('result') && $box.data('result').partial;
        var potong = $modal.find('[name="potong_dari_cuti"]').is(':checked');
        $modal.find('[data-status="disetujui"]').html('<i class="fas fa-check-circle mr-1"></i>Setujui'
            + (partial ? ' Sebagian' : '') + (potong ? ' &amp; Potong Cuti' : ''));
    }

    function updateAdjust($modal) {
        var $box = $modal.find('.approval-adjust');
        if (!$box.length) return;
        var r = evaluateAdjust($box);
        $box.data('result', r);
        $box.toggleClass('border-warning', !!(r.ok && r.partial)).toggleClass('border-danger', !r.ok);
        $box.find('.adjust-info').html('<span class="' + (r.ok ? (r.partial ? 'text-warning' : 'text-muted') : 'text-danger') + '">' + r.message + '</span>');
        refreshApproveLabel($modal);
        $modal.trigger('adjust:change', [r]);
    }

    function initAdjust($modal, row) {
        var $box = $modal.find('.approval-adjust');
        if (!$box.length) return;
        var type = $box.data('adjust'), $inputs = $box.find('input');
        var orig = type === 'date'
            ? { start: row.tanggal_mulai_ymd, end: row.tanggal_selesai_ymd }
            : { start: row.jam_mulai_hm, end: row.jam_selesai_hm };
        $box.data('orig', orig).toggle(!!(orig.start && orig.end));
        $inputs.eq(0).val(orig.start || '');
        $inputs.eq(1).val(orig.end || '');
        if (type === 'date') $inputs.attr({ min: orig.start, max: orig.end });
        updateAdjust($modal);
    }

    // ---- Approval modal (see hrd/pengajuan/_approval_modal) ----
    function bindApproval(opts) {
        var $modal = $(opts.modal), $form = $modal.find('form'), currentId = null;

        $(document).on('click', opts.trigger, function () {
            var row = rowData(this);
            currentId = $(this).data('id');
            $form[0].reset();
            $modal.find('.approval-summary').html(summaryHtml(row));
            initAdjust($modal, row);
            if (opts.onOpen) opts.onOpen($modal, currentId, row);
            $modal.modal('show');
        });

        $modal.on('input change', '.approval-adjust input', function () { updateAdjust($modal); });

        $modal.on('click', '[data-status]', function () {
            if (!currentId) return;
            var $btn = $(this), status = $btn.data('status'), $all = $modal.find('[data-status]');

            var $box = $modal.find('.approval-adjust:visible');
            if (status === 'disetujui' && $box.length) {
                var r = evaluateAdjust($box);
                if (!r.ok) {
                    Swal.fire({ icon: 'warning', title: 'Periksa jumlah yang disetujui', html: r.message });
                    return;
                }
            }

            var data = $form.serializeArray();
            // The approved-amount fields only matter when approving
            if (status !== 'disetujui') data = $.grep(data, function (f) { return !/_disetujui$/.test(f.name); });
            data.push({ name: 'status', value: status });
            if (opts.method && opts.method !== 'POST') data.push({ name: '_method', value: opts.method });

            $all.prop('disabled', true);
            setBusy($btn, true);
            $.ajax({ url: opts.url(currentId), type: 'POST', data: $.param(data) })
                .done(function (res) {
                    $modal.modal('hide');
                    toast((res && res.message) || (status === 'disetujui' ? 'Pengajuan disetujui' : 'Pengajuan ditolak'));
                    if (opts.onSuccess) opts.onSuccess(status);
                })
                .fail(function (xhr) {
                    showError(xhr);
                    var r = xhr.responseJSON || {};
                    // Already processed / no longer allowed: close and refresh so the list reflects reality
                    if (xhr.status === 403 || xhr.status === 404 || (xhr.status === 422 && !r.errors)) {
                        $modal.modal('hide');
                        if (opts.onStale) opts.onStale();
                    }
                })
                .always(function () {
                    setBusy($btn, false);
                    $all.prop('disabled', false);
                });
        });
    }

    function dayCount(startVal, endVal) {
        if (!startVal || !endVal) return 0;
        var s = moment(startVal, 'YYYY-MM-DD'), e = moment(endVal, 'YYYY-MM-DD');
        if (!s.isValid() || !e.isValid() || e.isBefore(s)) return 0;
        return e.diff(s, 'days') + 1;
    }

    return {
        escapeHtml: escapeHtml,
        errorMessage: errorMessage,
        showError: showError,
        toast: toast,
        setBusy: setBusy,
        clearFieldErrors: clearFieldErrors,
        setFieldError: setFieldError,
        clearFieldError: clearFieldError,
        applyServerErrors: applyServerErrors,
        initDateRange: initDateRange,
        dataTable: dataTable,
        reloadTables: reloadTables,
        initTabs: initTabs,
        decrementBadge: decrementBadge,
        bindDetail: bindDetail,
        bindApproval: bindApproval,
        refreshApproveLabel: refreshApproveLabel,
        dayCount: dayCount
    };
})(jQuery);
</script>

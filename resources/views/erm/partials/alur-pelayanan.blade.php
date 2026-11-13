{{--
    Tahap Pelayanan shared by Rawat Jalan, Billing and E-Resep (App\Services\ERM\AlurPelayanan).
    Include in the page's scripts section BEFORE the page script, put <div data-alur-strip></div> where the strip goes,
    then: AlurPelayanan.init({ filters: fn }) + AlurPelayanan.attach(dataTable, row => row.id),
    and render AlurPelayanan.placeholder(row.id) in a table cell.
--}}
@once
<style>
    /* Strip: one chevron bar, each stage an arrow pointing to the next, so it reads as a flow.
       Each arrow is a soft tint of its stage colour (same colours as the table badges), label and number in a deeper
       shade of it; red only for patients waiting too long today.
       Chevrons overlap by 12px against a 14px notch, leaving a 2px gap of the panel colour between them. */
    .alur-strip { border: 1px solid rgba(128,128,128,.22); border-radius: 10px; padding: 14px 16px; margin-bottom: 1rem; }
    .alur-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
    .alur-title { font-size: 14px; font-weight: 600; }
    .alur-meta { font-size: 12px; opacity: .6; margin-left: 10px; font-variant-numeric: tabular-nums; }
    .alur-sides { display: flex; gap: 2px; flex-wrap: wrap; font-size: 12px; }
    .alur-side { background: none; border: 0; padding: 2px 8px; border-radius: 6px; font-size: 12px; color: inherit; opacity: .7; cursor: pointer; }
    .alur-side b { font-variant-numeric: tabular-nums; margin-left: 3px; }
    .alur-side:hover { opacity: 1; background: rgba(128,128,128,.12); }
    .alur-flow { display: flex; overflow-x: auto; scrollbar-width: thin; }
    .alur-step { --alur: #6c757d; --tint: 14%; flex: 1 0 128px; display: flex; flex-direction: column; align-items: flex-start; justify-content: center;
        gap: 2px; min-height: 64px; margin-left: -12px; padding: 8px 18px 8px 28px; border: 0; text-align: left; cursor: pointer;
        background: rgba(128,128,128,.10); background: color-mix(in srgb, var(--alur) var(--tint), transparent);
        color: inherit; color: color-mix(in srgb, var(--alur) 72%, #000); transition: background .12s;
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%, 14px 50%); }
    :root.theme-dark .alur-step { --tint: 22%; color: color-mix(in srgb, var(--alur) 50%, #fff); }
    .alur-step:first-child { margin-left: 0; padding-left: 14px;
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%); }
    .alur-step:last-child { padding-right: 14px;
        clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 14px 50%); }
    .alur-step:hover, .alur-step:focus-visible { outline: none; --tint: 24%; }
    :root.theme-dark .alur-step:hover, :root.theme-dark .alur-step:focus-visible { --tint: 32%; }
    .alur-step-label { font-size: 12px; font-weight: 500; line-height: 1.2; white-space: nowrap; }
    .alur-step-num { font-size: 22px; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .alur-step-late { font-size: 11px; font-weight: 700; color: #dc3545; white-space: nowrap; }
    :root.theme-dark .alur-step-late { color: #f87171; }
    /* Empty stage: faint tint, greyed text, not clickable */
    .alur-step.is-zero, .alur-step.is-zero:hover { --tint: 6%; cursor: default; }
    :root.theme-dark .alur-step.is-zero, :root.theme-dark .alur-step.is-zero:hover { --tint: 8%; }
    .alur-step.is-zero .alur-step-num, .alur-step.is-zero .alur-step-label { opacity: .45; }
    /* Stage badge in table cells: soft tint of the stage colour, text in a deeper shade of it; minutes after a thin divider */
    .alur-tahap { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; margin-bottom: 4px; }
    .alur-badge { --alur: #6c757d; display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 6px;
        font-size: 12px; font-weight: 600; line-height: 18px; white-space: nowrap; cursor: pointer;
        background: rgba(128,128,128,.14); background: color-mix(in srgb, var(--alur) 14%, transparent);
        color: var(--alur); color: color-mix(in srgb, var(--alur) 78%, #000); }
    :root.theme-dark .alur-badge { background: color-mix(in srgb, var(--alur) 24%, transparent); color: color-mix(in srgb, var(--alur) 55%, #fff); }
    .alur-badge:hover { filter: brightness(.96); }
    .alur-badge-loading { --alur: #94a3b8; opacity: .5; cursor: default; }
    .alur-badge .alur-menit { font-weight: 500; opacity: .75; font-variant-numeric: tabular-nums; padding-left: 6px;
        border-left: 1px solid currentColor; border-left-color: color-mix(in srgb, currentColor 30%, transparent); }
    .alur-badge.is-late .alur-menit { color: #dc3545; opacity: 1; font-weight: 700; }
    .alur-chip-lab { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;
        line-height: 18px; white-space: nowrap; color: #b91c1c; background: rgba(220,53,69,.12); }
    :root.theme-dark .alur-chip-lab { color: #fca5a5; background: rgba(248,113,113,.18); }
    /* Shared id cell (E-Resep no resep / Billing invoice): number line, small meta line, one row of same-size tags */
    .alur-cell { display: flex; flex-direction: column; gap: 5px; min-width: 210px; }
    .alur-cell-id { display: flex; align-items: center; gap: 8px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .alur-cell-id .klinik-logo { height: 22px; max-width: 48px; object-fit: contain; flex: 0 0 auto; margin: 0 !important; }
    .alur-cell-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 4px 10px; font-size: 12px; line-height: 16px; }
    .alur-cell-meta .alur-cell-muted { opacity: .65; }
    .alur-pay { display: inline-flex; align-items: center; gap: 5px; font-weight: 600; color: var(--pay); }
    .alur-pay::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--pay); }
    .alur-cell-tags { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
    .alur-cell-tags .alur-tahap { margin: 0; }
    .alur-col-status { min-width: 170px; }
    /* Same as Rawat Jalan: main stage badge on top, small icon tiles (lab, stok, …) below it */
    .alur-icons { display: flex; flex-wrap: wrap; gap: 4px; flex-basis: 100%; }
    .alur-icon { display: inline-flex; align-items: center; justify-content: center; gap: 3px; min-width: 24px; height: 22px; padding: 0 6px;
        border-radius: 6px; font-size: 11px; line-height: 1; white-space: nowrap; color: #64748b; background: rgba(100,116,139,.12); cursor: default; }
    .alur-icon i { font-size: 11px; }
    .alur-icon b { font-size: 10px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .alur-icon-warn { color: #b45309; background: rgba(217,119,6,.14); }
    .alur-icon-danger { color: #b91c1c; background: rgba(220,53,69,.14); }
    :root.theme-dark .alur-icon { color: #cbd5e1; background: rgba(148,163,184,.16); }
    :root.theme-dark .alur-icon-warn { color: #fcd34d; background: rgba(251,191,36,.18); }
    :root.theme-dark .alur-icon-danger { color: #fca5a5; background: rgba(248,113,113,.2); }
    .alur-chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;
        line-height: 18px; white-space: nowrap; color: #475569; background: rgba(100,116,139,.12); }
    .alur-chip-warn { color: #92400e; background: rgba(217,119,6,.14); }
    .alur-chip-danger { color: #b91c1c; background: rgba(220,53,69,.12); }
    :root.theme-dark .alur-chip { color: #cbd5e1; background: rgba(148,163,184,.18); }
    :root.theme-dark .alur-chip-warn { color: #fcd34d; background: rgba(251,191,36,.16); }
    :root.theme-dark .alur-chip-danger { color: #fca5a5; background: rgba(248,113,113,.18); }
    #modalAlurPelayanan .alur-wait-late { color: #dc3545; font-weight: 700; }
    #modalAlurPelayanan .alur-wait-warn { color: #d39e00; font-weight: 700; }
    #modalAlurPelayanan .alur-tl { list-style: none; padding: 0; margin: 0; }
    #modalAlurPelayanan .alur-tl li { position: relative; padding: 0 0 14px 26px; }
    #modalAlurPelayanan .alur-tl li::before { content: ''; position: absolute; left: 6px; top: 4px; width: 12px; height: 12px; border-radius: 50%;
        background: rgba(128,128,128,.35); }
    #modalAlurPelayanan .alur-tl li.is-done::before { background: #20a374; }
    #modalAlurPelayanan .alur-tl li::after { content: ''; position: absolute; left: 11px; top: 18px; bottom: 0; width: 2px; background: rgba(128,128,128,.25); }
    #modalAlurPelayanan .alur-tl li:last-child::after { display: none; }
    #modalAlurPelayanan .alur-row { cursor: pointer; }
</style>

<div class="modal fade" id="modalAlurPelayanan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="alur-modal-title">Alur Pelayanan</h5>
                    <small class="text-muted" id="alur-modal-sub"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="alur-modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="alur-modal-back" style="display:none;"><i class="fas fa-arrow-left mr-1"></i>Kembali</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@php
    $alurStages = [];
    foreach (\App\Services\ERM\AlurPelayanan::STAGES as $alurKey => $alurStage) {
        $alurStages[$alurKey] = ['label' => $alurStage[0], 'color' => $alurStage[1], 'icon' => $alurStage[2], 'desc' => $alurStage[3]];
    }
    $alurStrip = \App\Services\ERM\AlurPelayanan::STRIP_STAGES;
@endphp
<script>
window.AlurPelayanan = (function ($) {
    var STAGES = @json($alurStages);
    var STRIP = @json($alurStrip);
    var URLS = {
        summary: @json(route('erm.alur-pelayanan.summary')),
        list: @json(route('erm.alur-pelayanan.list')),
        timeline: @json(url('erm/alur-pelayanan/__ID__/timeline'))
    };
    var LATE_MINUTES = @json(\App\Services\ERM\AlurPelayanan::LATE_MINUTES); // waiting longer than this is flagged

    var cache = {};
    var tables = [];
    var filtersFn = function () { return {}; };
    var xhr = null;
    var refreshTimer = null;
    var lastListStage = null;

    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function durasi(menit) {
        if (menit == null) return '';
        if (menit < 60) return menit + ' mnt';
        return Math.floor(menit / 60) + ' jam ' + (menit % 60) + ' mnt';
    }

    function badgeHtml(info, showLab) {
        var st = STAGES[info.tahap] || { label: info.tahap, color: '#6c757d', icon: 'fas fa-circle', desc: '' };
        var late = info.menit != null && info.menit > LATE_MINUTES;
        var title = st.desc + (info.sejak ? ' — sejak ' + info.sejak : '') + (info.menit != null ? ' (' + durasi(info.menit) + ')' : '') + '. Klik untuk timeline.';
        return '<span class="alur-badge' + (late ? ' is-late' : '') + '" style="--alur:' + st.color + ';" title="' + esc(title) + '">'
            + '<span class="alur-badge-name">' + esc(st.label) + '</span>'
            + (info.menit != null ? '<span class="alur-menit">' + esc(durasi(info.menit)) + '</span>' : '')
            + '</span>'
            + (showLab !== false && info.lab_pending > 0 && info.tahap !== 'menunggu_hasil_lab' ? '<span class="alur-chip-lab" title="Permintaan lab belum selesai"><i class="fas fa-flask"></i>Lab ' + info.lab_pending + '</span>' : '');
    }

    /** Payment status as coloured dot + text (same colours on Billing and E-Resep). */
    var PAY_COLORS = { 'Lunas': '#20a374', 'Belum Lunas': '#d39e00', 'Piutang': '#0891b2', 'Terhapus': '#6c757d' };
    function payHtml(label) {
        label = $.trim(label || '') || 'Belum Transaksi';
        return '<span class="alur-pay" style="--pay:' + (PAY_COLORS[label] || '#dc3545') + ';">' + esc(label) + '</span>';
    }

    /** Cell markup for a visit; filled from the last known stage, refreshed after each draw. opts.lab=false hides the lab note. */
    /** Small icon tile under the badge (same look as Rawat Jalan isi kunjungan). variant: '' | 'warn' | 'danger' */
    function iconHtml(icon, title, count, variant) {
        return '<span class="alur-icon' + (variant ? ' alur-icon-' + variant : '') + '" title="' + esc(title) + '" aria-label="' + esc(title) + '">'
            + '<i class="' + esc(icon) + '"></i>' + (count > 1 ? '<b>' + count + '</b>' : '') + '</span>';
    }

    var extraIcons = {}; // visit id -> page icons (e.g. E-Resep stok) kept across badge refreshes

    /** Badge on top; lab (unless opts.lab=false) + page icons in a row below. */
    function cellHtml(id, info, showLab) {
        var icons = '';
        if (showLab && info && info.lab_pending > 0 && info.tahap !== 'menunggu_hasil_lab') {
            icons += iconHtml('fas fa-flask', 'Permintaan lab belum selesai (' + info.lab_pending + ')', info.lab_pending, 'danger');
        }
        icons += extraIcons[id] || '';
        return (info ? badgeHtml(info, false) : '<span class="alur-badge alur-badge-loading">…</span>')
            + (icons ? '<div class="alur-icons">' + icons + '</div>' : '');
    }

    /** Cell markup for a visit; filled from the last known stage, refreshed after each draw.
        opts.lab=false hides the lab icon; opts.icons = extra icon html shown under the badge. */
    function placeholder(id, opts) {
        if (!id) return '';
        var showLab = !(opts && opts.lab === false);
        if (opts && opts.icons !== undefined) extraIcons[id] = opts.icons || '';
        return '<div class="alur-tahap" data-alur-vid="' + esc(id) + '"' + (showLab ? '' : ' data-alur-nolab="1"') + '>'
            + cellHtml(id, cache[id], showLab) + '</div>';
    }

    function fillBadges() {
        $('[data-alur-vid]').each(function () {
            var id = $(this).attr('data-alur-vid');
            if (cache[id]) $(this).html(cellHtml(id, cache[id], !$(this).attr('data-alur-nolab')));
        });
    }

    /** Build the panel once; the page's own children (side counters) move into the header and keep their handlers. */
    function ensureStrip($strip) {
        if ($strip.find('.alur-flow').length) return;
        var $sides = $strip.children().detach();
        // data-alur-strip="card": standalone panel using the theme's card surface (light / dark)
        $strip.addClass('alur-strip' + ($strip.attr('data-alur-strip') === 'card' ? ' card' : '')).html(
            '<div class="alur-head"><div><span class="alur-title">Alur Pelayanan</span><span class="alur-meta"></span></div><div class="alur-sides"></div></div>'
            + '<div class="alur-flow" role="list"></div>'
        );
        $strip.find('.alur-sides').append($sides);
    }

    function stepHtml(key, n, lateN) {
        var st = STAGES[key];
        var title = st.desc + (n ? ' — klik untuk daftar pasien' : '');
        var cls = 'alur-step' + (n === 0 ? ' is-zero' : '');
        return '<button type="button" role="listitem" class="' + cls + '"' + (n ? ' data-alur-stage="' + key + '"' : '')
            + ' style="--alur:' + st.color + ';" title="' + esc(title) + '">'
            + '<span class="alur-step-label">' + esc(st.label) + '</span>'
            + '<span class="alur-step-num">' + n + '</span>'
            + (lateN ? '<span class="alur-step-late">' + lateN + ' lewat ' + LATE_MINUTES + ' mnt</span>' : '')
            + '</button>';
    }

    function renderStrip(counts, late) {
        var $strip = $('[data-alur-strip]');
        if (!$strip.length) return;
        ensureStrip($strip);
        late = late || {};

        var aktif = 0;
        var steps = STRIP
            .filter(function (key) { return key !== 'sedang_dilayani' || (counts[key] || 0) > 0; })
            .map(function (key) {
                var n = counts[key] || 0;
                if (key !== 'selesai') aktif += n;
                return stepHtml(key, n, late[key] || 0);
            });
        $strip.find('.alur-meta').text(aktif + ' dalam proses · ' + (counts.selesai || 0) + ' selesai');
        $strip.find('.alur-flow').html(steps.join(''));
    }

    function visibleIds() {
        var ids = [];
        tables.forEach(function (t) {
            try {
                t.dt.rows({ page: 'current' }).data().each(function (row) {
                    var id = t.idOf(row);
                    if (id) ids.push(String(id));
                });
            } catch (e) {}
        });
        return ids;
    }

    function refresh() {
        if (xhr && xhr.readyState !== 4) { try { xhr.abort(); } catch (e) {} }
        xhr = $.getJSON(URLS.summary, $.extend({}, filtersFn(), { ids: visibleIds() }))
            .done(function (res) {
                $.extend(cache, res.rows || {});
                fillBadges();
                renderStrip(res.counts || {}, res.late || {});
            });
        return xhr;
    }

    function scheduleRefresh() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refresh, 150);
    }

    // ---- modal: stage list / timeline ----
    function modalLoading() {
        $('#alur-modal-body').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    }

    function openList(stage) {
        var st = STAGES[stage];
        lastListStage = stage;
        $('#alur-modal-title').html('<i class="' + st.icon + ' mr-1" style="color:' + st.color + '"></i>' + esc(st.label));
        $('#alur-modal-sub').text(st.desc);
        $('#alur-modal-back').hide();
        modalLoading();
        $('#modalAlurPelayanan').modal('show');
        $.getJSON(URLS.list, $.extend({}, filtersFn(), { tahap: stage })).done(function (res) {
            var rows = res.data || [];
            if (!rows.length) {
                $('#alur-modal-body').html('<div class="text-center text-muted py-4">Tidak ada pasien di tahap ini.</div>');
                return;
            }
            var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr>'
                + '<th>Antrian</th><th>Pasien</th><th>Dokter / Klinik</th><th>Sejak</th><th>Lama</th></tr></thead><tbody>';
            rows.forEach(function (r) {
                var cls = r.menit == null ? '' : (r.menit > LATE_MINUTES * 2 ? 'alur-wait-late' : (r.menit > LATE_MINUTES ? 'alur-wait-warn' : ''));
                html += '<tr class="alur-row" data-alur-timeline="' + esc(r.id) + '" title="Klik untuk timeline">'
                    + '<td>' + esc(r.no_antrian || '-') + '</td>'
                    + '<td><div class="font-weight-bold">' + esc(r.nama_pasien) + '</div><small class="text-muted">' + esc(r.no_rm) + ' · ' + esc(r.jenis) + '</small>'
                    + (r.lab_pending > 0 ? '<span class="alur-chip-lab"><i class="fas fa-flask"></i>Lab</span>' : '') + '</td>'
                    + '<td>' + esc(r.dokter) + '<br><small class="text-muted">' + esc(r.klinik) + '</small></td>'
                    + '<td>' + esc(r.sejak || '-') + '</td>'
                    + '<td class="' + cls + '">' + esc(durasi(r.menit) || '-') + '</td></tr>';
            });
            html += '</tbody></table></div>';
            if (rows.length >= 300) html += '<small class="text-muted">Menampilkan 300 pasien pertama.</small>';
            $('#alur-modal-body').html(html);
        }).fail(function () {
            $('#alur-modal-body').html('<div class="alert alert-danger mb-0">Gagal memuat daftar.</div>');
        });
    }

    function openTimeline(id, fromList) {
        $('#alur-modal-title').text('Timeline Pelayanan');
        $('#alur-modal-sub').text('');
        $('#alur-modal-back').toggle(!!fromList);
        if (!fromList) lastListStage = null;
        modalLoading();
        $('#modalAlurPelayanan').modal('show');
        $.getJSON(URLS.timeline.replace('__ID__', encodeURIComponent(id))).done(function (t) {
            var st = STAGES[t.tahap] || { label: t.tahap, color: '#6c757d', icon: 'fas fa-circle', desc: '' };
            $('#alur-modal-sub').text(t.nama_pasien + ' · ' + t.no_rm);
            var html = '<div class="mb-3">Tahap sekarang: ' + badgeHtml(t)
                + '<div class="small text-muted mt-1">' + esc(st.desc) + '</div></div><ul class="alur-tl">';
            (t.steps || []).forEach(function (s) {
                html += '<li class="' + (s.at ? 'is-done' : '') + '"><div class="font-weight-bold">' + esc(s.label) + '</div>'
                    + '<small class="text-muted">' + (s.at ? esc(s.at) + (s.durasi != null ? ' · +' + esc(durasi(s.durasi)) : '') : 'belum') + '</small></li>';
            });
            html += '</ul>';
            $('#alur-modal-body').html(html);
        }).fail(function () {
            $('#alur-modal-body').html('<div class="alert alert-danger mb-0">Gagal memuat timeline.</div>');
        });
    }

    $(document).on('click', '[data-alur-stage]', function () { openList($(this).data('alur-stage')); });
    $(document).on('click', '[data-alur-vid] .alur-badge:not(.alur-badge-loading)', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openTimeline($(this).closest('[data-alur-vid]').attr('data-alur-vid'), false);
    });
    $(document).on('click', '#modalAlurPelayanan [data-alur-timeline]', function () { openTimeline($(this).attr('data-alur-timeline'), true); });
    $(document).on('click', '#alur-modal-back', function () { if (lastListStage) openList(lastListStage); });

    return {
        /** opts.filters: () => ({start_date, end_date, klinik_id, dokter_id}) */
        init: function (opts) {
            filtersFn = opts.filters || filtersFn;
            // Keep the strip / badges current while the page stays open
            setInterval(function () { if (!document.hidden) refresh(); }, 60000);
        },
        /** Refresh the strip + badges after every draw of this DataTable. */
        attach: function (dt, idOf) {
            if (!dt) return;
            tables.push({ dt: dt, idOf: idOf || function (row) { return row.id; } });
            dt.on('draw.dt', scheduleRefresh);
        },
        placeholder: placeholder,
        payHtml: payHtml,
        iconHtml: iconHtml,
        refresh: scheduleRefresh
    };
})(jQuery);
</script>
@endonce

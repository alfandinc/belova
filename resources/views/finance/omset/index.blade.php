@extends('layouts.finance.app')
@section('title', 'Finance | Laporan Omset')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
<style>
    /* Same color tokens as the dashboard widgets (dashboard/partials/widget_styles): validated light + dark steps */
    .omz {
        --dw-series-1: #2a78d6;
        --dw-series-2: #eb6834;
        --dw-series-3: #1baf7a;
        --dw-series-4: #eda100;
        --dw-other: #b8b6ae;
        --dw-bar: #5b6b82;
        --dw-grid: #eef0f3;
        --dw-track: #eef0f3;
        --dw-good-ink: #006300;
        --dw-critical-ink: #b42323;
        --dw-klinik-premiere: #2563eb;
        --dw-klinik-skin: #db2777;
        --dw-klinik-dental: #16a34a;
        --omz-muted: #6b7280;
    }
    html.theme-dark .omz {
        --dw-series-1: #3987e5;
        --dw-series-2: #d95926;
        --dw-series-3: #199e70;
        --dw-series-4: #c98500;
        --dw-other: #6b7286;
        --dw-bar: #8d98b0;
        --dw-grid: #383f58;
        --dw-track: #383f58;
        --dw-good-ink: #4cc74c;
        --dw-critical-ink: #f07a7a;
        --dw-klinik-premiere: #5b93f2;
        --dw-klinik-skin: #ea5d9e;
        --dw-klinik-dental: #32b768;
        --omz-muted: #9aa3b8;
    }
    .omz .omz-filters { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .omz .omz-filters .form-control { min-width: 0; }
    .omz .omz-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .75rem; margin-bottom: .75rem; }
    .omz .omz-kpi { padding: .75rem 1rem; }
    .omz .omz-kpi-label { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--omz-muted); }
    .omz .omz-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1.3; white-space: nowrap; }
    .omz .omz-kpi-hero .omz-kpi-value { font-size: 1.6rem; }
    .omz .omz-kpi-sub { font-size: 12px; color: var(--omz-muted); }
    .omz .omz-trend-up { color: var(--dw-good-ink); font-weight: 600; }
    .omz .omz-trend-down { color: var(--dw-critical-ink); font-weight: 600; }
    .omz .omz-track { height: 6px; border-radius: 3px; background: var(--dw-track); overflow: hidden; margin-top: .35rem; display: flex; gap: 2px; }
    .omz .omz-track > span { display: block; height: 100%; border-radius: 3px; }
    .omz .omz-card-title { font-weight: 700; font-size: .95rem; margin-bottom: .1rem; }
    .omz .omz-card-sub { font-size: 12px; color: var(--omz-muted); margin-bottom: .6rem; }
    .omz .omz-row { padding: .45rem 0; border-top: 1px solid var(--dw-grid); }
    .omz .omz-row:first-child { border-top: 0; }
    .omz .omz-row-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .omz .omz-row-name { display: flex; align-items: center; gap: .45rem; font-weight: 600; min-width: 0; }
    .omz .omz-row-name img { height: 20px; max-width: 48px; object-fit: contain; }
    .omz .omz-swatch { width: 10px; height: 10px; border-radius: 2px; flex: 0 0 auto; }
    .omz .omz-cutoffs { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .9rem; }
    .omz .omz-cutoff { display: inline-flex; align-items: center; gap: .3rem; }
    .omz .omz-cutoff.is-dim { opacity: .4; }
    .omz .omz-row-value { font-weight: 700; white-space: nowrap; }
    .omz .omz-row-meta { font-size: 12px; color: var(--omz-muted); display: flex; justify-content: space-between; gap: .5rem; margin-top: .15rem; }
    .omz .omz-loading { opacity: .5; transition: opacity .15s; }
    .omz #omz-chart { min-height: 280px; }
    .omz .omz-empty { color: var(--omz-muted); font-size: 13px; padding: .5rem 0; }
</style>

<div class="container-fluid omz">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap: .75rem;">
        <div>
            <h3 class="mb-0 font-weight-bold">Laporan Omset</h3>
            <div class="text-muted small">Omset = total invoice yang ditransaksikan (dibayar / dipiutangkan / asuransi), dihitung pada tanggal transaksi.</div>
            <div class="omz-cutoffs small mt-1">
                <span class="text-muted" title="Transaksi setelah jam 00:00 dan sebelum jam cut off dihitung sebagai omset hari sebelumnya">
                    <i class="far fa-clock mr-1"></i>Jam cut off:
                </span>
                @foreach ($kliniks as $klinik)
                    <span class="omz-cutoff" data-klinik="{{ $klinik->id }}">
                        <span class="omz-swatch" style="background: {{ $klinik->color }}"></span>{{ $klinik->short }}
                        <strong>{{ $cutoffs[$klinik->id] ?? '00:00' }}</strong>
                    </span>
                @endforeach
            </div>
        </div>
        <div class="omz-filters">
            <div class="input-group input-group-sm" style="width: 260px;">
                <input type="text" class="form-control form-control-sm" id="omz-daterange" readonly aria-label="Rentang tanggal">
                <span class="input-group-text"><i class="ti-calendar"></i></span>
            </div>
            <select id="omz-klinik" class="form-control form-control-sm" style="width: 200px;" aria-label="Klinik">
                <option value="">Semua Klinik</option>
                @foreach ($kliniks as $klinik)
                    <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                @endforeach
            </select>
            <button type="button" id="omz-toggle-stats" class="btn btn-sm btn-light" aria-controls="omz-body" aria-expanded="true">
                <i class="fas fa-eye-slash mr-1"></i><span>Sembunyikan Ringkasan</span>
            </button>
        </div>
    </div>

    <div id="omz-body">
        <div class="omz-kpis">
            <div class="card shadow-sm mb-0 omz-kpi omz-kpi-hero">
                <div class="omz-kpi-label">Omset <span data-k="period"></span></div>
                <div class="omz-kpi-value" data-k="total">-</div>
                <div class="omz-kpi-sub"><span data-k="trend"></span> <span data-k="prev"></span></div>
            </div>
            <div class="card shadow-sm mb-0 omz-kpi">
                <div class="omz-kpi-label">Jumlah Invoice</div>
                <div class="omz-kpi-value" data-k="invoices">-</div>
                <div class="omz-kpi-sub">Rata-rata <span data-k="average">-</span> / invoice</div>
            </div>
            <div class="card shadow-sm mb-0 omz-kpi">
                <div class="omz-kpi-label">Sudah Diterima</div>
                <div class="omz-kpi-value" data-k="diterima">-</div>
                <div class="omz-track" aria-hidden="true"><span data-k="diterima-bar" style="background: var(--dw-series-3); width: 0;"></span></div>
                <div class="omz-kpi-sub"><span data-k="diterima-pct">-</span> dari omset</div>
            </div>
            <div class="card shadow-sm mb-0 omz-kpi">
                <div class="omz-kpi-label">Belum Diterima (Piutang)</div>
                <div class="omz-kpi-value" data-k="belum">-</div>
                <div class="omz-kpi-sub">Kekurangan bayar dari invoice periode ini</div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="omz-card-title" data-k="chart-title">Omset per Hari</div>
                <div class="omz-card-sub">Arahkan kursor ke batang untuk melihat nominal dan jumlah invoice.</div>
                <div id="omz-chart"></div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="omz-card-title">Per Klinik</div>
                        <div class="omz-card-sub">Pencapaian target: <span data-k="target-label"></span></div>
                        <div data-k="klinik"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="omz-card-title">Per Kategori</div>
                        <div class="omz-card-sub">Diskon / pajak invoice dibagi proporsional ke item</div>
                        <div data-k="kategori"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="omz-card-title">Per Metode Bayar</div>
                        <div class="omz-card-sub">Metode bayar kunjungan: Umum atau asuransi</div>
                        <div data-k="metode"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="omz-card-title">Daftar Invoice</div>
            <div class="omz-card-sub">Invoice yang dihitung sebagai omset pada periode dan klinik terpilih</div>
            <table id="omz-invoices" class="table table-sm table-bordered table-hover nowrap" style="width:100%;">
                <thead class="thead-light">
                    <tr>
                        <th>Tgl Transaksi</th>
                        <th>No. Invoice</th>
                        <th>Pasien</th>
                        <th>Klinik</th>
                        <th>Metode Bayar</th>
                        <th class="text-right">Omset</th>
                        <th class="text-right">Diterima</th>
                        <th class="text-right">Sisa</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@hasanyrole('Admin|Finance')
<div class="modal fade" id="omz-date-modal" tabindex="-1" role="dialog" aria-labelledby="omz-date-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="omz-date-title">Ubah Tanggal Transaksi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2">Invoice <strong id="omz-date-invoice"></strong>: omset invoice ini dihitung pada tanggal transaksi.</div>
                <div class="form-group">
                    <label class="small mb-1" for="omz-date-input">Tanggal transaksi</label>
                    <input type="datetime-local" class="form-control form-control-sm" id="omz-date-input">
                </div>
                <div class="form-group mb-0">
                    <label class="small mb-1" for="omz-date-reason">Alasan perubahan <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-sm" id="omz-date-reason" rows="2" maxlength="1000" placeholder="Wajib diisi, minimal 5 karakter"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="omz-date-save"><i class="fas fa-save mr-1"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>
@endhasanyrole
@endsection

@section('scripts')
<script>
$(function() {
    var dataUrl = '{{ route('finance.omset.data') }}';
    var invoicesUrl = '{{ route('finance.omset.invoices') }}';
    var dateUrlBase = '{{ url('/finance/omset/invoices') }}/';
    var canEditDate = @json(auth()->user()->hasAnyRole(['Admin', 'Finance']));
    var startDate = moment().startOf('month').format('YYYY-MM-DD');
    var endDate = moment().format('YYYY-MM-DD');
    var klinikId = '';
    var lastData = null;
    var chart = null;

    function rp(n) { return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID'); }
    function rpShort(n) {
        n = Number(n) || 0;
        var a = Math.abs(n);
        if (a >= 1e9) return 'Rp ' + (n / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        if (a >= 1e6) return 'Rp ' + (n / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        if (a >= 1e3) return 'Rp ' + Math.round(n / 1e3).toLocaleString('id-ID') + ' rb';
        return rp(n);
    }
    function num(n) { return (Number(n) || 0).toLocaleString('id-ID'); }
    function pct(n) { return (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%'; }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function cssVar(name) {
        return getComputedStyle(document.querySelector('.omz')).getPropertyValue(name).trim() || '#888';
    }
    var $k = function(key) { return $('#omz-body [data-k="' + key + '"]'); };

    $('#omz-daterange').daterangepicker({
        startDate: moment(startDate), endDate: moment(endDate),
        locale: {
            format: 'DD MMM YYYY', applyLabel: 'Pilih', cancelLabel: 'Batal', customRangeLabel: 'Pilih Tanggal',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        ranges: {
            'Hari Ini': [moment(), moment()],
            'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
            'Bulan Ini': [moment().startOf('month'), moment()],
            'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Tahun Ini': [moment().startOf('year'), moment()]
        }
    }, function(start, end) {
        startDate = start.format('YYYY-MM-DD');
        endDate = end.format('YYYY-MM-DD');
        reload();
    });
    $('#omz-klinik').on('change', function() {
        klinikId = $(this).val();
        // keep the selected clinic's cut off in focus
        $('.omz-cutoff').each(function() { $(this).toggleClass('is-dim', !!klinikId && String($(this).data('klinik')) !== klinikId); });
        reload();
    });

    // ---- Summary / breakdowns ----
    function shareRow(o) {
        // o: name (html), value, pct, color, meta (html), extraBar (html)
        return '<div class="omz-row">'
            + '<div class="omz-row-head"><div class="omz-row-name">' + o.name + '</div><div class="omz-row-value">' + rp(o.value) + '</div></div>'
            + '<div class="omz-track" aria-hidden="true"><span style="width:' + Math.min(100, o.pct) + '%; background:' + o.color + ';"></span></div>'
            + '<div class="omz-row-meta">' + o.meta + '</div>'
            + '</div>';
    }

    function render(d) {
        lastData = d;
        var s = d.summary;
        $k('period').text(d.period.label);
        $k('total').text(rp(s.total));
        var t = s.trend || {};
        var trendHtml = '';
        if (t.dir === 'up' || t.dir === 'down') {
            trendHtml = '<span class="omz-trend-' + t.dir + '">' + (t.dir === 'up' ? '&#9650; ' : '&#9660; ') + esc(t.label) + '</span>';
        } else if (t.dir === 'flat') {
            trendHtml = '<span>= 0%</span>';
        }
        $k('trend').html(trendHtml);
        $k('prev').text('vs ' + d.period.previous_label + ' (' + rpShort(s.previous_total) + ')');
        $k('invoices').text(num(s.invoices));
        $k('average').text(rp(s.average));
        $k('diterima').text(rp(s.diterima));
        $k('diterima-pct').text(pct(s.diterima_pct));
        $k('diterima-bar').css('width', Math.min(100, s.diterima_pct) + '%');
        $k('belum').text(rp(s.belum_diterima));

        // per klinik: each clinic in its own row with its logo + color
        $k('target-label').text(d.klinik.target_label);
        var kHtml = '';
        $.each(d.klinik.rows, function(i, r) {
            var name = (r.logo ? '<img src="' + esc(r.logo) + '" alt="">' : '<span class="omz-swatch" style="background:' + r.color + '"></span>')
                + '<span class="text-truncate">' + esc(r.nama) + '</span>';
            var target = r.target > 0
                ? 'Target ' + rpShort(r.target) + ' &middot; <strong>' + pct(r.target_pct) + '</strong>'
                : '<span>Belum ada target</span>';
            kHtml += shareRow({
                name: name, value: r.total, pct: r.pct, color: r.color,
                meta: '<span>' + num(r.invoices) + ' invoice &middot; ' + pct(r.pct) + '</span><span>' + target + '</span>'
            });
        });
        $k('klinik').html(kHtml || '<div class="omz-empty">Belum ada omset.</div>');

        // per kategori (fixed color per category, same as the dashboard)
        var catColors = { tindakan: 'var(--dw-series-1)', obat: 'var(--dw-series-2)', konsultasi: 'var(--dw-series-3)', lab: 'var(--dw-series-4)', lain: 'var(--dw-other)' };
        var cHtml = '';
        $.each(d.kategori, function(i, c) {
            if (c.total <= 0) return;
            cHtml += shareRow({
                name: '<span class="omz-swatch" style="background:' + catColors[c.key] + '"></span><span>' + esc(c.label) + '</span>',
                value: c.total, pct: c.pct, color: catColors[c.key],
                meta: '<span>' + pct(c.pct) + ' dari omset</span>'
            });
        });
        $k('kategori').html(cHtml || '<div class="omz-empty">Belum ada omset.</div>');

        // per metode bayar: Umum vs Asuransi, then each insurer
        var m = d.metode_bayar, mHtml = '';
        if (s.total > 0) {
            mHtml += shareRow({ name: '<span class="omz-swatch" style="background:var(--dw-series-1)"></span><span>Umum</span>', value: m.umum.total, pct: m.umum.pct, color: 'var(--dw-series-1)', meta: '<span>' + num(m.umum.invoices) + ' invoice &middot; ' + pct(m.umum.pct) + '</span>' });
            mHtml += shareRow({ name: '<span class="omz-swatch" style="background:var(--dw-series-2)"></span><span>Asuransi</span>', value: m.asuransi.total, pct: m.asuransi.pct, color: 'var(--dw-series-2)', meta: '<span>' + num(m.asuransi.invoices) + ' invoice &middot; ' + pct(m.asuransi.pct) + '</span>' });
            $.each(m.insurers || [], function(i, ins) {
                mHtml += '<div class="omz-row-meta pl-3"><span>&bull; ' + esc(ins.label) + ' (' + num(ins.invoices) + ')</span><span>' + rp(ins.total) + '</span></div>';
            });
        }
        $k('metode').html(mHtml || '<div class="omz-empty">Belum ada omset.</div>');

        renderChart(d.chart);
    }

    // ---- Chart: one measure over time = neutral bars, hover tooltip with nominal + invoice count ----
    function renderChart(c) {
        $k('chart-title').text(c.granularity === 'month' ? 'Omset per Bulan' : 'Omset per Hari');
        var points = c.points || [];
        var ink = cssVar('--omz-muted');
        var options = {
            chart: { type: 'bar', height: 280, toolbar: { show: false }, animations: { enabled: false }, background: 'transparent', fontFamily: 'inherit' },
            series: [{ name: 'Omset', data: points.map(function(p) { return p.total; }) }],
            xaxis: {
                categories: points.map(function(p) { return p.label; }),
                labels: { style: { colors: ink, fontSize: '11px' }, rotate: -45, hideOverlappingLabels: true },
                axisBorder: { color: cssVar('--dw-grid') }, axisTicks: { show: false }
            },
            yaxis: { labels: { style: { colors: ink, fontSize: '11px' }, formatter: rpShort } },
            colors: [cssVar('--dw-bar')],
            plotOptions: { bar: { columnWidth: points.length > 20 ? '70%' : '45%', borderRadius: 4, borderRadiusApplication: 'end' } },
            dataLabels: { enabled: false },
            grid: { borderColor: cssVar('--dw-grid'), strokeDashArray: 0, xaxis: { lines: { show: false } } },
            states: { hover: { filter: { type: 'darken', value: 0.85 } } },
            tooltip: {
                theme: document.documentElement.classList.contains('theme-dark') ? 'dark' : 'light',
                custom: function(o) {
                    var p = points[o.dataPointIndex];
                    return '<div class="px-2 py-1"><div class="font-weight-bold">' + esc(p.title) + '</div>'
                        + '<div>' + rp(p.total) + '</div><div class="small">' + num(p.invoices) + ' invoice</div></div>';
                }
            }
        };
        if (chart) chart.destroy();
        chart = new ApexCharts(document.querySelector('#omz-chart'), options);
        chart.render();
    }

    // Repaint the chart with the other theme's tokens when the theme is toggled
    new MutationObserver(function() { if (lastData) renderChart(lastData.chart); })
        .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    var xhr = null;
    var summaryHidden = false;
    var summaryStale = true; // filters changed while the summary was hidden
    try { summaryHidden = localStorage.getItem('omset.hideSummary') === '1'; } catch (e) {}

    function applySummaryVisibility(animate) {
        var $body = $('#omz-body');
        if (summaryHidden) { animate ? $body.slideUp(150) : $body.hide(); } else { animate ? $body.slideDown(150) : $body.show(); }
        $('#omz-toggle-stats').attr('aria-expanded', summaryHidden ? 'false' : 'true')
            .find('i').attr('class', (summaryHidden ? 'fas fa-eye' : 'fas fa-eye-slash') + ' mr-1').end()
            .find('span').text(summaryHidden ? 'Tampilkan Ringkasan' : 'Sembunyikan Ringkasan');
    }

    $('#omz-toggle-stats').on('click', function() {
        summaryHidden = !summaryHidden;
        try { localStorage.setItem('omset.hideSummary', summaryHidden ? '1' : '0'); } catch (e) {}
        applySummaryVisibility(true);
        if (!summaryHidden && summaryStale) loadSummary();
        // the chart can't measure itself while hidden: redraw once visible
        if (!summaryHidden && !summaryStale && lastData) setTimeout(function() { renderChart(lastData.chart); }, 160);
    });

    function loadSummary() {
        // hidden summary: skip its queries until it is shown again
        if (summaryHidden) { summaryStale = true; return; }
        summaryStale = false;
        var params = { start_date: startDate, end_date: endDate, klinik_id: klinikId };
        $('#omz-body').addClass('omz-loading');
        if (xhr && xhr.readyState !== 4) xhr.abort();
        xhr = $.getJSON(dataUrl, params).done(render).fail(function(x) {
            if (x.statusText !== 'abort') Swal.fire('Gagal', 'Gagal memuat data omset.', 'error');
        }).always(function() { $('#omz-body').removeClass('omz-loading'); });
    }
    function reload() {
        loadSummary();
        if (table) table.ajax.reload();
    }

    // ---- Invoice list ----
    var table = $('#omz-invoices').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        order: [[0, 'desc']],
        ajax: {
            url: invoicesUrl,
            data: function(d) { d.start_date = startDate; d.end_date = endDate; d.klinik_id = klinikId; }
        },
        columns: [
            { data: 'transaction_date', name: 'transaction_date', searchable: false, className: 'text-nowrap', render: function(v, t, r) {
                if (t !== 'display') return v;
                // after-midnight service before the clinic cut off counts for the previous day
                var omsetDay = (r.tanggal_omset && String(v).substr(0, 10) !== r.tanggal_omset)
                    ? '<br><small class="text-muted" title="Lewat jam cut off klinik">omset ' + esc(moment(r.tanggal_omset).format('D MMM YYYY')) + '</small>'
                    : '';
                if (!canEditDate) return esc(v) + omsetDay;
                return esc(v) + omsetDay + ' <button type="button" class="btn btn-sm btn-link p-0 ml-1 omz-edit-date" data-id="' + r.id + '" data-date="' + esc(v) + '" data-invoice="' + esc(r.invoice_number) + '" title="Ubah tanggal transaksi" aria-label="Ubah tanggal transaksi"><i class="fas fa-pen"></i></button>';
            } },
            { data: 'invoice_number', name: 'invoice_number', render: function(v) { return '<span class="font-weight-bold">' + esc(v) + '</span>'; } },
            { data: 'pasien_nama', name: 'pasien_nama', render: function(v, t, r) { return esc(v || '-') + (r.pasien_id ? '<br><small class="text-muted">' + esc(r.pasien_id) + '</small>' : ''); } },
            { data: 'klinik_nama', name: 'klinik_nama', render: function(v, t, r) { return esc(r.klinik_short || v || '-'); } },
            { data: 'metode_bayar_nama', name: 'metode_bayar_nama', render: function(v) { return esc(v || '-'); } },
            { data: 'total_amount', name: 'total_amount', searchable: false, className: 'text-right', render: function(v) { return '<span class="font-weight-bold">' + rp(v) + '</span>'; } },
            { data: 'diterima', name: 'diterima', searchable: false, orderable: false, className: 'text-right', render: function(v) { return rp(v); } },
            { data: 'sisa', name: 'sisa', searchable: false, orderable: false, className: 'text-right', render: function(v) { return v > 0 ? '<span class="text-danger font-weight-bold">' + rp(v) + '</span>' : '-'; } }
        ],
        language: {
            search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', zeroRecords: 'Tidak ada invoice',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ invoice', infoEmpty: 'Tidak ada invoice', infoFiltered: '(dari _MAX_)',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' }, processing: 'Memproses...'
        }
    });

    // ---- Admin: change an invoice's transaction date ----
    var editingId = null;
    $(document).on('click', '.omz-edit-date', function() {
        var $b = $(this);
        editingId = $b.data('id');
        $('#omz-date-invoice').text($b.data('invoice'));
        $('#omz-date-input').val(String($b.data('date')).replace(' ', 'T')).attr('max', moment().format('YYYY-MM-DDTHH:mm'));
        $('#omz-date-reason').val('');
        $('#omz-date-modal').modal('show');
    });
    $('#omz-date-save').on('click', function() {
        var date = $('#omz-date-input').val();
        var reason = $.trim($('#omz-date-reason').val());
        if (!date) { Swal.fire('Tanggal wajib diisi', '', 'warning'); return; }
        if (reason.length < 5) { Swal.fire('Alasan wajib diisi', 'Tulis alasan perubahan (minimal 5 karakter).', 'warning'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: dateUrlBase + editingId + '/transaction-date',
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { transaction_date: date, reason: reason }
        }).done(function(res) {
            $('#omz-date-modal').modal('hide');
            Swal.fire('Tersimpan', res.message, 'success');
            reload();
        }).fail(function(x) {
            var r = x.responseJSON || {};
            var msg = r.errors ? $.map(r.errors, function(v) { return v; }).join('\n') : (r.message || 'Gagal menyimpan.');
            Swal.fire('Gagal', msg, 'error');
        }).always(function() { $btn.prop('disabled', false); });
    });

    applySummaryVisibility(false);
    loadSummary();
});
</script>
@endsection

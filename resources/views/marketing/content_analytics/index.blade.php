@extends('layouts.marketing.app')

@section('title', 'Analisa Konten')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@push('styles')
@include('marketing.partials.workflow_styles')
<style>
.ca-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; }
.ca-toolbar .form-control { width: auto; min-width: 0; border-radius: 8px; }
.ca-dates { display: none; align-items: center; gap: 6px; }
.ca-dates.is-on { display: inline-flex; }
.ca-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-left: auto; }
.ca-period { font-size: .84rem; color: var(--wf-muted); margin-bottom: 16px; }
.ca-period strong { color: var(--wf-text); }
@media (max-width: 767.98px) { .ca-filters { margin-left: 0; width: 100%; } .ca-filters .form-control { flex: 1 1 30%; } }

/* KPI cards */
.ca-kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; margin-bottom: 16px; }
.ca-kpi { background: var(--wf-surface-solid); border-radius: 12px; padding: 14px 16px; box-shadow: 0 0 0 1px var(--wf-border); min-width: 0; }
.ca-kpi-label { font-size: .76rem; font-weight: 600; color: var(--wf-muted); display: flex; align-items: center; gap: 6px; }
.ca-kpi-value { font-size: 1.55rem; font-weight: 700; line-height: 1.2; margin-top: 4px; font-variant-numeric: tabular-nums; }
.ca-kpi-change { font-size: .76rem; font-weight: 700; margin-top: 2px; }
.ca-kpi-change.up { color: var(--st-green-fg); }
.ca-kpi-change.down { color: var(--wf-danger); }
.ca-kpi-change.flat { color: var(--wf-muted); font-weight: 600; }

.ca-card { background: var(--wf-surface-solid); border-radius: 12px; padding: 14px 16px; box-shadow: 0 0 0 1px var(--wf-border); margin-bottom: 16px; min-width: 0; }
.ca-card-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
.ca-card-title { font-weight: 700; font-size: .98rem; }
.ca-card-head .cp-seg { margin-left: auto; }


/* highlights */
.ca-highlights { display: flex; flex-direction: column; gap: 10px; }
.ca-hl { display: flex; gap: 10px; align-items: flex-start; }
.ca-hl-ico { width: 32px; height: 32px; border-radius: 10px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
.ca-hl-ico.views { background: var(--st-blue-bg); color: var(--st-blue-fg); }
.ca-hl-ico.er { background: var(--st-green-bg); color: var(--st-green-fg); }
.ca-hl-ico.growth { background: var(--st-amber-bg); color: var(--st-amber-fg); }
.ca-hl-ico.pilar { background: var(--st-indigo-bg); color: var(--st-indigo-fg); }
.ca-hl small { display: block; color: var(--wf-muted); font-size: .74rem; font-weight: 600; }
.ca-hl a, .ca-hl span.t { font-weight: 700; font-size: .88rem; color: var(--wf-text); word-break: break-word; }

/* ranking table */
.ca-table { width: 100%; font-size: .84rem; }
.ca-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--wf-muted); font-weight: 700; padding: 6px 8px; white-space: nowrap; border-bottom: 1px solid var(--wf-border); }
.ca-table td { padding: 8px; border-bottom: 1px solid var(--wf-border); vertical-align: middle; white-space: nowrap; }
.ca-table tr:last-child td { border-bottom: 0; }
.ca-table td.title { white-space: normal; min-width: 220px; }
.ca-table td.title a { font-weight: 700; color: var(--wf-text); }
.ca-table td.title a:hover { color: var(--wf-accent); text-decoration: none; }
.ca-table .num { text-align: right; font-variant-numeric: tabular-nums; }
.ca-table .is-sort { font-weight: 700; color: var(--wf-text); }
.ca-rank { width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .78rem; background: var(--wf-surface-2); }
.ca-rank.top { background: var(--wf-accent); color: #fff; }
.ca-meta { font-size: .74rem; color: var(--wf-muted); margin-top: 2px; display: flex; flex-wrap: wrap; gap: 4px 8px; align-items: center; }
.ca-spark { width: 90px; height: 26px; display: block; }
.ca-spark path { fill: none; stroke: var(--wf-accent); stroke-width: 1.6; }
.ca-gain { color: var(--st-green-fg); font-weight: 700; }
.ca-logo { width: 18px; height: 18px; border-radius: 50%; overflow: hidden; display: inline-flex; background: #fff; box-shadow: 0 0 0 1px var(--wf-border); }
.ca-logo img { width: 100%; height: 100%; object-fit: contain; }
.ca-only { font-size: .8rem; display: inline-flex; align-items: center; gap: 6px; color: var(--wf-muted); cursor: pointer; margin: 0; }

/* breakdown bars */
.ca-break-row { display: grid; grid-template-columns: minmax(110px, 180px) 1fr auto; gap: 10px; align-items: center; padding: 6px 0; }
.ca-break-label { font-weight: 600; font-size: .84rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ca-break-label small { color: var(--wf-muted); font-weight: 400; }
.ca-break-bar { height: 10px; border-radius: 999px; background: var(--wf-surface-2); overflow: hidden; }
.ca-break-bar span { display: block; height: 100%; border-radius: 999px; background: var(--wf-accent); }
.ca-break-val { font-size: .8rem; font-variant-numeric: tabular-nums; text-align: right; min-width: 130px; }
.ca-break-val strong { font-size: .86rem; }
.ca-break-row.is-best .ca-break-bar span { background: #2f9e44; }
@media (max-width: 575.98px) { .ca-break-row { grid-template-columns: 1fr auto; } .ca-break-bar { grid-column: 1 / -1; order: 3; } }

/* followers per clinic */
.ca-clinics { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin-bottom: 16px; }
.ca-clinic {
    text-align: left; border: 0; border-radius: 12px; padding: 12px 14px; background: var(--wf-surface-2); color: var(--wf-text);
    box-shadow: inset 0 0 0 1px var(--wf-border); display: flex; flex-direction: column; gap: 4px; min-width: 0;
}
button.ca-clinic { cursor: pointer; transition: box-shadow .15s; }
button.ca-clinic:hover { box-shadow: inset 0 0 0 1px var(--wf-accent); }
button.ca-clinic.is-on { background: var(--wf-surface-solid); box-shadow: inset 0 0 0 2px var(--clinic, var(--wf-accent)), 0 4px 14px rgba(0,0,0,.08); }
.ca-clinic-head { display: flex; align-items: center; gap: 8px; min-width: 0; }
.ca-clinic-head strong { font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ca-clinic-logo { width: 26px; height: 26px; border-radius: 50%; overflow: hidden; flex: 0 0 auto; background: #fff; box-shadow: 0 0 0 1px var(--wf-border); display: inline-flex; align-items: center; justify-content: center; }
.ca-clinic-logo img { width: 100%; height: 100%; object-fit: contain; }
.ca-clinic-logo.has-bg img { padding: 3px; }
.ca-clinic-user { font-size: .76rem; color: var(--wf-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ca-clinic-num { font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.2; }
.ca-clinic-num small { font-size: .74rem; font-weight: 600; color: var(--wf-muted); }
.ca-clinic-change { font-size: .76rem; font-weight: 700; }
.ca-clinic-change.up { color: var(--st-green-fg); } .ca-clinic-change.down { color: var(--wf-danger); } .ca-clinic-change.flat { color: var(--wf-muted); font-weight: 600; }
.ca-clinic.is-empty { opacity: .85; }
.ca-clinic.is-empty .ca-clinic-msg { font-size: .78rem; color: var(--wf-muted); }
.ca-clinic .ca-warn { font-size: .74rem; color: var(--st-amber-fg); font-weight: 600; }
.ca-clinic svg { width: 100%; height: 28px; display: block; margin-top: 2px; }
.ca-clinic svg path { fill: none; stroke: var(--clinic, var(--wf-accent)); stroke-width: 1.8; }

.ca-fdetail { border-top: 1px solid var(--wf-border); padding-top: 14px; }
.ca-fdetail-title { font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 8px; }
.ca-story { font-size: .9rem; line-height: 1.5; margin: 6px 0 12px; }
.ca-story b { font-weight: 700; }
.ca-subtitle { font-weight: 700; font-size: .86rem; margin-bottom: 6px; }
.ca-fkpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; margin-bottom: 16px; }
.ca-fkpi { background: var(--wf-surface-2); border-radius: 10px; padding: 10px 12px; }
.ca-fkpi .l { font-size: .78rem; color: var(--wf-text); font-weight: 700; }
.ca-fkpi .h { font-size: .72rem; color: var(--wf-muted); }
.ca-fkpi .v { font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; margin-top: 2px; }
.ca-fkpi .c { font-size: .74rem; font-weight: 700; }
.ca-fkpi .c.up { color: var(--st-green-fg); } .ca-fkpi .c.down { color: var(--wf-danger); } .ca-fkpi .c.flat { color: var(--wf-muted); font-weight: 600; }

/* charts are drawn at the real width of their box (no stretching) */
.ca-chart svg { display: block; max-width: 100%; }
.ca-chart text { fill: var(--wf-muted); font-size: 11px; font-family: inherit; }
.ca-chart .grid { stroke: var(--wf-border); stroke-width: 1; }
.ca-chart .zero { stroke: var(--wf-muted); stroke-width: 1; opacity: .6; }
.ca-chart .fline { fill: none; stroke: #E4405F; stroke-width: 2.5; stroke-linejoin: round; }
.ca-chart .farea { fill: #E4405F; opacity: .07; }
.ca-chart .fdot { fill: #E4405F; }
.ca-chart .fdot.est { fill: var(--wf-surface-solid); stroke: #E4405F; stroke-width: 1.5; }
.ca-chart .bnew { fill: #2f9e44; }
.ca-chart .bunf { fill: var(--wf-danger); }
.ca-chart .bar { fill: var(--wf-accent); opacity: .85; }
.ca-chart .bar.is-empty { fill: var(--wf-border); opacity: 1; }
.ca-chart .post { fill: var(--wf-accent); }
.ca-chart .val { fill: var(--wf-text); font-weight: 700; font-size: 10px; }
.ca-legend { display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: .76rem; color: var(--wf-muted); margin: 6px 0 4px; }
.ca-legend i { display: inline-block; width: 12px; height: 8px; border-radius: 2px; margin-right: 5px; vertical-align: middle; }
.ca-legend i.line { background: #E4405F; height: 3px; }
.ca-legend i.est { width: 8px; height: 8px; border-radius: 50%; border: 1.5px solid #E4405F; background: transparent; }
.ca-legend i.new { background: #2f9e44; }
.ca-legend i.unf { background: var(--wf-danger); }
.ca-legend i.post { background: var(--wf-accent); width: 8px; height: 8px; border-radius: 50%; }
.ca-demo { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--wf-border); }
.ca-demo-title { font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--wf-muted); margin-bottom: 6px; }
.ca-demo-row { display: grid; grid-template-columns: 1fr 70px 44px; gap: 8px; align-items: center; font-size: .8rem; padding: 2px 0; }
.ca-demo-row .lbl { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ca-demo-row .bar { height: 7px; border-radius: 999px; background: var(--wf-surface-2); overflow: hidden; }
.ca-demo-row .bar span { display: block; height: 100%; background: #E4405F; border-radius: 999px; }
.ca-demo-row .pct { text-align: right; font-variant-numeric: tabular-nums; color: var(--wf-muted); }

.ca-loading { opacity: .5; pointer-events: none; transition: opacity .15s; }
.ca-note { font-size: .78rem; color: var(--wf-muted); }
</style>
@endpush

@section('content')
<div class="container-fluid cp-page" id="caPage">
    <div class="cp-top">
        <div>
            <h4>Analisa Konten</h4>
            <p>Performa konten Instagram per periode, dibanding periode sebelumnya.</p>
        </div>
        <div class="cp-top-actions">
            <a href="{{ url('/marketing/content-plan') }}" class="btn btn-light cp-install"><i class="far fa-calendar mr-1"></i> Content Plan</a>
            @if($canManage)
                <button type="button" class="btn btn-primary cp-new" id="caSync"><i class="fas fa-chart-line mr-1"></i> Update insight</button>
            @endif
        </div>
    </div>

    @unless($enabled)
        <div class="alert alert-warning">Tabel insight belum ada. Jalankan <code>php artisan migrate</code>.</div>
    @endunless

    <div class="ca-toolbar">
        <div class="cp-seg" id="caRange" role="group" aria-label="Periode">
            <button type="button" data-range="7">7 hari</button>
            <button type="button" data-range="30">30 hari</button>
            <button type="button" data-range="90">90 hari</button>
            <button type="button" data-range="month">Bulan ini</button>
            <button type="button" data-range="lastmonth">Bulan lalu</button>
            <button type="button" data-range="custom"><i class="far fa-calendar-alt"></i></button>
        </div>
        <span class="ca-dates" id="caDates">
            <input type="date" class="form-control form-control-sm" id="caStart" aria-label="Dari">
            <span class="wf-muted">–</span>
            <input type="date" class="form-control form-control-sm" id="caEnd" aria-label="Sampai">
        </span>
        <div class="ca-filters">
            <select class="form-control form-control-sm" id="caBrand">
                <option value="">Semua klinik</option>
                @foreach($brands as $b)<option value="{{ $b['value'] }}">{{ $b['label'] }}</option>@endforeach
            </select>
            <select class="form-control form-control-sm" id="caPilar">
                <option value="">Semua pilar</option>
                @foreach($kontenPilar as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
            </select>
            <select class="form-control form-control-sm" id="caJenis">
                <option value="">Semua jenis</option>
                @foreach($jenisKonten as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="ca-period" id="caPeriod"></div>

    <div id="caBody">
        <div class="ca-kpis" id="caKpis"></div>

        {{-- Followers per clinic (each clinic = its connected Instagram account) --}}
        <div class="ca-card" id="caFollowersCard" style="display:none">
            <div class="ca-card-head">
                <span class="ca-card-title"><i class="fab fa-instagram mr-1" style="color:#E4405F"></i>Followers per klinik</span>
                <span class="ca-note">klik kartu klinik untuk melihat rinciannya</span>
            </div>
            <div class="ca-clinics" id="caClinics"></div>

            <div id="caFollowerDetail" class="ca-fdetail">
                <div class="ca-fdetail-title" id="caFollowerTitle"></div>
                <p class="ca-story" id="caFollowerStory"></p>
                <div class="ca-fkpis" id="caFollowerKpis"></div>

                <div class="ca-subtitle">Jumlah followers per hari</div>
                <div class="ca-chart" id="caFollowerChart"></div>
                <div class="ca-legend"><span><i class="line"></i>Jumlah followers</span><span><i class="est"></i>Perkiraan (hari tanpa update)</span><span><i class="post"></i>Ada konten tayang (arahkan kursor untuk judulnya)</span></div>

                <div class="ca-subtitle mt-3">Follow &amp; berhenti follow per hari</div>
                <div class="ca-chart" id="caFollowChart"></div>
                <div class="ca-legend"><span><i class="new"></i>Mulai follow</span><span><i class="unf"></i>Berhenti follow</span></div>

                <div class="ca-subtitle mt-3">Siapa followers-nya?</div>
                <p class="ca-story" id="caDemoStory"></p>
                <div class="ca-demo" id="caDemo"></div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="ca-card">
                    <div class="ca-card-head"><span class="ca-card-title">Views baru per hari</span><span class="ca-note">tambahan views dari semua konten Instagram</span></div>
                    <p class="ca-story" id="caTrendStory"></p>
                    <div class="ca-chart" id="caTrend"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="ca-card">
                    <div class="ca-card-head"><span class="ca-card-title">Sorotan periode ini</span></div>
                    <div class="ca-highlights" id="caHighlights"></div>
                </div>
            </div>
        </div>

        <div class="ca-card">
            <div class="ca-card-head">
                <span class="ca-card-title">Waktu tayang terbaik</span>
                <span class="ca-note">rata-rata views 3 hari pertama setelah tayang · konten 90 hari terakhir</span>
            </div>
            <p class="ca-story" id="caTimeStory"></p>
            <div class="row">
                <div class="col-md-6"><div class="ca-subtitle">Per jam tayang</div><div id="caTimeSlots"></div></div>
                <div class="col-md-6 mt-3 mt-md-0"><div class="ca-subtitle">Per hari tayang</div><div id="caTimeDays"></div></div>
            </div>
        </div>

        <div class="ca-card">
            <div class="ca-card-head">
                <span class="ca-card-title">Peringkat konten</span>
                <label class="ca-only"><input type="checkbox" id="caOnlyNew" checked> Hanya yang tayang di periode ini</label>
                <div class="cp-seg cp-seg-sm" id="caSort" role="group" aria-label="Urutkan">
                    <button type="button" data-sort="views" class="active">Views</button>
                    <button type="button" data-sort="er">Engagement</button>
                    <button type="button" data-sort="views_gain">Pertumbuhan</button>
                    <button type="button" data-sort="saves">Saves</button>
                    <button type="button" data-sort="shares">Shares</button>
                </div>
            </div>
            <div class="table-responsive"><table class="ca-table" id="caTable"></table></div>
        </div>

        <div class="ca-card">
            <div class="ca-card-head">
                <span class="ca-card-title">Track record tim</span>
                <span class="ca-note">periode yang dipilih, dibanding periode sebelumnya</span>
            </div>
            <div class="ca-subtitle">Content creator <span class="ca-note">(PIC konten)</span></div>
            <div class="table-responsive"><table class="ca-table" id="caCreators"></table></div>
            <div class="ca-subtitle mt-3">Designer</div>
            <div class="table-responsive"><table class="ca-table" id="caDesigners"></table></div>
            <p class="ca-note mt-2 mb-0">Tepat waktu = tayang paling lambat 30 menit dari jadwal. Tepat deadline = designer menandai <em>Selesai dikerjakan</em> paling lambat di hari deadline.
                Lama pengerjaan = dari <em>Mulai kerjakan</em> sampai pertama kali <em>Selesai dikerjakan</em>. Revisi = jumlah "Revisi masuk" yang dicatat.</p>
        </div>

        <div class="ca-card">
            <div class="ca-card-head">
                <span class="ca-card-title">Performa per kelompok</span>
                <span class="ca-note">rata-rata konten yang tayang di periode ini</span>
                <div class="cp-seg cp-seg-sm" id="caGroup" role="group" aria-label="Kelompok">
                    <button type="button" data-group="pilar" class="active">Pilar</button>
                    <button type="button" data-group="jenis">Jenis</button>
                    <button type="button" data-group="brand">Klinik</button>
                </div>
            </div>
            <div id="caBreakdown"></div>
        </div>

        <p class="ca-note" id="caFoot"></p>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@include('marketing.partials.workflow_scripts')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    var W = window.MkWf, esc = W.esc;
    var DATA_URL = @json(route('marketing.content-analytics.data'));
    var SYNC_URL = @json(url('/marketing/content-insight/sync'));
    var PLAN_URL = @json(url('/marketing/content-plan'));
    var BRANDS = {};
    (@json($brands) || []).forEach(function (b) { BRANDS[b.value] = b; });

    var CAN_MANAGE = @json($canManage);
    var range = '30', sortBy = 'views', group = 'pilar', data = null, seq = 0;
    try { range = localStorage.getItem('ca_range') || '30'; } catch (e) {}

    // 12400 -> "12,4rb"
    function num(n) {
        if (n === null || n === undefined) return '–';
        if (Math.abs(n) >= 1000000) return (n / 1000000).toFixed(1).replace('.0', '').replace('.', ',') + 'jt';
        if (Math.abs(n) >= 10000) return (n / 1000).toFixed(1).replace('.0', '').replace('.', ',') + 'rb';
        return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    function pct(n) { return n === null || n === undefined ? '–' : String(n).replace('.', ',') + '%'; }

    // ---- period
    function periodDates() {
        var end = moment().startOf('day'), start;
        if (range === 'month') { start = moment().startOf('month'); }
        else if (range === 'lastmonth') { start = moment().subtract(1, 'month').startOf('month'); end = moment().subtract(1, 'month').endOf('month').startOf('day'); }
        else if (range === 'custom') {
            start = moment($('#caStart').val() || moment().subtract(29, 'days').format('YYYY-MM-DD'));
            end = moment($('#caEnd').val() || moment().format('YYYY-MM-DD'));
        } else { start = moment().subtract(parseInt(range, 10) - 1, 'days').startOf('day'); }
        return { start: start, end: end };
    }
    function setRangeUi() {
        $('#caRange button').each(function () { $(this).toggleClass('active', String($(this).data('range')) === range); });
        $('#caDates').toggleClass('is-on', range === 'custom');
        if (range === 'custom' && !$('#caStart').val()) {
            $('#caStart').val(moment().subtract(29, 'days').format('YYYY-MM-DD'));
            $('#caEnd').val(moment().format('YYYY-MM-DD'));
        }
    }
    $('#caRange').on('click', 'button', function () {
        range = String($(this).data('range'));
        try { localStorage.setItem('ca_range', range); } catch (e) {}
        setRangeUi();
        load();
    });
    $('#caStart, #caEnd, #caBrand, #caPilar, #caJenis').on('change', load);

    function load() {
        var p = periodDates();
        if (p.end.isBefore(p.start)) return;
        var my = ++seq;
        $('#caBody').addClass('ca-loading');
        $.getJSON(DATA_URL, {
            start: p.start.format('YYYY-MM-DD'), end: p.end.format('YYYY-MM-DD'),
            brand: $('#caBrand').val(), pilar: $('#caPilar').val(), jenis: $('#caJenis').val(), account: selectedAccount
        }).done(function (r) {
            if (my !== seq) return;
            data = r;
            render();
        }).fail(function (xhr) {
            Swal.fire('Gagal', W.errorMessage(xhr), 'error');
        }).always(function () { if (my === seq) $('#caBody').removeClass('ca-loading'); });
    }

    function render() {
        var pd = data.period;
        $('#caPeriod').html('<strong>' + esc(W.date(pd.start, { weekday: false })) + ' – ' + esc(W.date(pd.end, { weekday: false, year: true })) + '</strong>'
            + ' (' + pd.days + ' hari) · dibanding ' + esc(W.date(pd.prev_start, { weekday: false })) + ' – ' + esc(W.date(pd.prev_end, { weekday: false })));
        renderKpis();
        renderFollowers();
        renderTrend();
        renderHighlights();
        renderPostingTime();
        renderTeam();
        renderTable();
        renderBreakdown();
        $('#caFoot').html('<i class="fab fa-instagram mr-1"></i>Data dari <strong>Update insight</strong> Instagram (' + data.tracked + ' konten terlacak'
            + (data.last_update ? ', update terakhir ' + esc(W.date(data.last_update, { time: true })) : '') + '). '
            + 'Views periode = pertambahan views selama periode. Engagement rate = (likes + komentar + saves + shares) ÷ reach; konten dengan reach di bawah 50 tidak dihitung.');
    }

    // ---- KPI cards: value + change against the previous period
    var KPIS = [
        { key: 'views', label: 'Views baru', icon: 'far fa-eye', fmt: num, unit: '%' },
        { key: 'interactions', label: 'Interaksi baru', icon: 'far fa-heart', fmt: num, unit: '%', title: 'Likes + komentar + saves + shares selama periode' },
        { key: 'published', label: 'Konten tayang', icon: 'fas fa-bullhorn', fmt: num, unit: '%' },
        { key: 'avg_views', label: 'Rata-rata views / konten', icon: 'fas fa-chart-bar', fmt: num, unit: '%', title: 'Konten yang tayang di periode ini' },
        { key: 'avg_er', label: 'Engagement rate', icon: 'fas fa-bolt', fmt: pct, unit: ' poin' },
        { key: 'on_time', label: 'Tayang tepat waktu', icon: 'far fa-clock', fmt: pct, unit: ' poin', title: 'Paling lambat 30 menit dari jadwal' }
    ];
    function renderKpis() {
        $('#caKpis').html(KPIS.map(function (k) {
            var d = data.kpi[k.key] || {};
            var ch = d.change, cls = 'flat', txt = 'belum ada data periode sebelumnya';
            if (ch !== null && ch !== undefined) {
                cls = ch > 0 ? 'up' : (ch < 0 ? 'down' : 'flat');
                txt = ch === 0 ? 'sama dengan periode sebelumnya'
                    : (ch > 0 ? '▲ naik ' : '▼ turun ') + String(Math.abs(ch)).replace('.', ',') + k.unit + ' dibanding periode sebelumnya';
            } else if (d.previous !== null && d.previous !== undefined) {
                txt = 'sebelumnya ' + k.fmt(d.previous);
            }
            return '<div class="ca-kpi"' + (k.title ? ' title="' + esc(k.title) + '"' : '') + '>'
                + '<div class="ca-kpi-label"><i class="' + k.icon + '"></i>' + esc(k.label) + '</div>'
                + '<div class="ca-kpi-value">' + k.fmt(d.value) + '</div>'
                + '<div class="ca-kpi-change ' + cls + '">' + esc(txt) + '</div></div>';
        }).join(''));
    }

    // ---- chart helpers: every chart is drawn at the real pixel width of its box, so lines and text
    // keep their shape on any screen (no stretching); they are redrawn when the window is resized
    function dayLabel(iso) { var m = moment(iso); return m.date() + ' ' + W.BULAN[m.month()]; }
    function boxWidth(sel) { return Math.max(300, Math.floor($(sel).width() || 600)); }
    function svgOpen(w, h, label) { return '<svg width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '" role="img" aria-label="' + esc(label) + '">'; }
    // round axis steps (890, 891, 892 … or 0, 500, 1.000 …)
    function niceTicks(min, max, count) {
        if (min === max) { min = Math.max(0, min - 1); max += 1; }
        var raw = (max - min) / count, mag = Math.pow(10, Math.floor(Math.log10(raw))), r = raw / mag;
        var step = Math.max(1, (r <= 1 ? 1 : r <= 2 ? 2 : r <= 5 ? 5 : 10) * mag);
        var ticks = [];
        for (var v = Math.floor(min / step) * step; v <= Math.ceil(max / step) * step + step / 2; v += step) ticks.push(v);
        return ticks;
    }
    function labelEvery(n, width) { return Math.max(1, Math.ceil(n / Math.max(2, Math.floor(width / 70)))); }
    function xLabels(series, cx, every, y) {
        return series.map(function (d, i) {
            return i % every === 0 ? '<text x="' + cx(i).toFixed(1) + '" y="' + y + '" text-anchor="middle">' + esc(dayLabel(d.date)) + '</text>' : '';
        }).join('');
    }

    // ---- followers per clinic
    var selectedAccount = '';
    try { selectedAccount = localStorage.getItem('ca_account') || ''; } catch (e) {}

    function growthText(growth, change) {
        if (growth === null || growth === undefined) return { cls: 'flat', text: 'belum cukup data untuk dibandingkan' };
        if (growth === 0) return { cls: 'flat', text: 'tidak berubah di periode ini' };
        return {
            cls: growth > 0 ? 'up' : 'down',
            text: (growth > 0 ? '▲ naik ' : '▼ turun ') + num(Math.abs(growth)) + ' orang' + (change !== null && change !== undefined ? ' (' + String(Math.abs(change)).replace('.', ',') + '%)' : '') + ' di periode ini'
        };
    }
    function miniSpark(values) {
        if (!values || values.length < 2) return '';
        var max = Math.max.apply(null, values), min = Math.min.apply(null, values), span = max - min || 1;
        var d = values.map(function (v, i) { return (i ? 'L' : 'M') + (i * 200 / (values.length - 1)).toFixed(1) + ' ' + (26 - (v - min) / span * 24).toFixed(1); }).join(' ');
        return '<svg viewBox="0 0 200 28" preserveAspectRatio="none"><path d="' + d + '" vector-effect="non-scaling-stroke"/></svg>';
    }
    function clinicLogo(c) {
        if (!c || !c.logo) return '<span class="ca-clinic-logo"><i class="fab fa-instagram" style="color:#E4405F"></i></span>';
        return '<span class="ca-clinic-logo' + (c.logo_bg ? ' has-bg' : '') + '"' + (c.logo_bg ? ' style="background:' + c.logo_bg + '"' : '') + '><img src="' + esc(c.logo) + '" alt=""></span>';
    }
    function accountCard(clinic, a, selected) {
        var g = growthText(a.growth, a.change);
        return '<button type="button" class="ca-clinic' + (selected ? ' is-on' : '') + '" data-account="' + a.id + '" style="--clinic:' + esc((clinic && clinic.color) || '#E4405F') + '">'
            + '<span class="ca-clinic-head">' + clinicLogo(clinic) + '<strong>' + esc(clinic ? clinic.label : 'Klinik belum dipilih') + '</strong></span>'
            + '<span class="ca-clinic-user"><i class="fab fa-instagram mr-1"></i>@' + esc(a.username || '?') + '</span>'
            + '<span class="ca-clinic-num">' + num(a.followers) + ' <small>followers</small></span>'
            + '<span class="ca-clinic-change ' + g.cls + '">' + esc(g.text) + '</span>'
            + (clinic ? '' : '<span class="ca-warn"><i class="fas fa-exclamation-circle mr-1"></i>Pilih kliniknya di Content Plan → ikon Instagram</span>')
            + (a.last_error ? '<span class="ca-warn"><i class="fas fa-exclamation-triangle mr-1"></i>' + esc(a.last_error) + '</span>' : '')
            + miniSpark(a.spark) + '</button>';
    }
    function renderClinics(f) {
        var cards = [];
        var all = [];
        f.clinics.forEach(function (c) { c.accounts.forEach(function (a) { all.push(a); }); });
        f.unassigned.forEach(function (a) { all.push(a); });
        if (all.length > 1) {
            var total = all.reduce(function (s, a) { return a.followers === null ? s : s + a.followers; }, 0);
            var growth = all.some(function (a) { return a.growth === null; }) ? null : all.reduce(function (s, a) { return s + a.growth; }, 0);
            var g = growthText(growth, null);
            cards.push('<button type="button" class="ca-clinic' + (!f.selected ? ' is-on' : '') + '" data-account="">'
                + '<span class="ca-clinic-head"><span class="ca-clinic-logo"><i class="fas fa-layer-group text-muted"></i></span><strong>Semua klinik</strong></span>'
                + '<span class="ca-clinic-user">' + all.length + ' akun Instagram</span>'
                + '<span class="ca-clinic-num">' + num(total) + ' <small>followers</small></span>'
                + '<span class="ca-clinic-change ' + g.cls + '">' + esc(g.text) + '</span></button>');
        }
        f.clinics.forEach(function (c) {
            if (!c.accounts.length) {
                cards.push('<div class="ca-clinic is-empty"><span class="ca-clinic-head">' + clinicLogo(c) + '<strong>' + esc(c.label) + '</strong></span>'
                    + '<span class="ca-clinic-msg"><i class="fas fa-unlink mr-1"></i>Belum terhubung ke akun Instagram.</span>'
                    + (CAN_MANAGE ? '<a href="' + PLAN_URL + '?ig=1" class="small font-weight-bold">Hubungkan akun →</a>' : '') + '</div>');
                return;
            }
            c.accounts.forEach(function (a) { cards.push(accountCard(c, a, String(f.selected) === String(a.id) || (!f.selected && all.length === 1))); });
        });
        f.unassigned.forEach(function (a) { cards.push(accountCard(null, a, String(f.selected) === String(a.id) || (!f.selected && all.length === 1))); });
        $('#caClinics').html(cards.join(''));
    }
    $(document).on('click', '#caClinics button.ca-clinic', function () {
        selectedAccount = String($(this).data('account') || '');
        try { localStorage.setItem('ca_account', selectedAccount); } catch (e) {}
        load();
    });

    // invert: for "berhenti follow" going up is bad (red), going down is good (green)
    function changeTag(ch, invert) {
        if (ch === null || ch === undefined) return '<div class="c flat">belum ada data periode sebelumnya</div>';
        var good = invert ? ch < 0 : ch > 0, bad = invert ? ch > 0 : ch < 0;
        return '<div class="c ' + (good ? 'up' : (bad ? 'down' : 'flat')) + '">' + (ch > 0 ? '▲ ' : (ch < 0 ? '▼ ' : '')) + String(Math.abs(ch)).replace('.', ',') + '% dibanding periode sebelumnya</div>';
    }

    function selectedInfo(f) {
        var found = null;
        f.clinics.forEach(function (c) { c.accounts.forEach(function (a) { if (String(a.id) === String(f.selected)) found = { clinic: c, account: a }; }); });
        f.unassigned.forEach(function (a) { if (String(a.id) === String(f.selected)) found = { clinic: null, account: a }; });
        if (!found && !f.selected) {
            var only = [];
            f.clinics.forEach(function (c) { c.accounts.forEach(function (a) { only.push({ clinic: c, account: a }); }); });
            f.unassigned.forEach(function (a) { only.push({ clinic: null, account: a }); });
            if (only.length === 1) found = only[0];
        }
        return found;
    }

    function renderFollowers() {
        var f = data.followers;
        $('#caFollowersCard').toggle(!!f);
        if (!f) return;
        renderClinics(f);
        var d = f.detail;
        $('#caFollowerDetail').toggle(!!d);
        if (!d) return;

        var info = selectedInfo(f);
        var who = info ? '@' + info.account.username : 'semua akun';
        $('#caFollowerTitle').html(info
            ? clinicLogo(info.clinic) + esc(info.clinic ? info.clinic.label : 'Klinik belum dipilih') + ' <span class="wf-muted font-weight-normal">· @' + esc(info.account.username || '') + '</span>'
            : '<i class="fas fa-layer-group text-muted"></i> Semua klinik');

        var k = d.kpi, days = data.period.days;
        var growth = k.growth.value, total = k.followers.value;
        var story = 'Dalam <b>' + days + ' hari</b> ini, followers ' + esc(who) + ' ';
        if (growth === null) story += 'belum bisa dibandingkan karena datanya baru sedikit.';
        else story += (growth > 0 ? '<b>bertambah ' + num(growth) + ' orang</b>' : growth < 0 ? '<b>berkurang ' + num(-growth) + ' orang</b>' : '<b>tidak berubah</b>')
            + (total !== null ? ' (dari ' + num(total - growth) + ' menjadi ' + num(total) + ')' : '') + '.';
        if (k.new.value !== null) {
            story += ' Ada <b>' + num(k.new.value) + ' orang mulai follow</b>' + (k.unfollows.value !== null ? ' dan <b>' + num(k.unfollows.value) + ' orang berhenti follow</b>' : '') + '.';
        }
        if (k.reach.value) story += ' Konten &amp; profilnya dilihat oleh <b>' + num(k.reach.value) + ' akun</b>.';
        if (k.new.change !== null && k.new.change !== undefined && k.new.change !== 0) {
            story += ' Followers baru ' + (k.new.change > 0 ? '<b class="text-success">lebih banyak ' : '<b class="text-danger">lebih sedikit ') + String(Math.abs(k.new.change)).replace('.', ',') + '%</b> dibanding periode sebelumnya.';
        }
        $('#caFollowerStory').html(story);

        var g = growthText(growth, k.followers.change);
        $('#caFollowerKpis').html(
            '<div class="ca-fkpi"><div class="l">Jumlah followers</div><div class="h">per ' + esc(dayLabel(data.period.end)) + '</div><div class="v">' + num(total) + '</div><div class="c ' + g.cls + '">' + esc(g.text) + '</div></div>'
            + '<div class="ca-fkpi"><div class="l">Mulai follow</div><div class="h">orang baru follow</div><div class="v">' + num(k.new.value) + '</div>' + changeTag(k.new.change) + '</div>'
            + '<div class="ca-fkpi"><div class="l">Berhenti follow</div><div class="h">unfollow</div><div class="v">' + num(k.unfollows.value) + '</div>' + changeTag(k.unfollows.change, true) + '</div>'
            + '<div class="ca-fkpi"><div class="l">Jangkauan akun</div><div class="h">akun yang melihat konten/profil</div><div class="v">' + num(k.reach.value) + '</div>' + changeTag(k.reach.change) + '</div>');

        renderFollowerChart(d.series);
        renderFollowChart(d.series);
        renderDemographics(d.demographics);
    }

    function renderFollowerChart(s) {
        var sel = '#caFollowerChart';
        var pts = s.filter(function (d) { return d.followers !== null; });
        if (!pts.length) { $(sel).html('<div class="wf-empty">Belum ada data followers. Klik <strong>Update insight</strong>; 30 hari terakhir langsung terisi.</div>'); return; }
        var w = boxWidth(sel), h = 250, padL = 58, padR = 14, top = 14, bottom = 196;
        var vals = pts.map(function (d) { return d.followers; });
        var ticks = niceTicks(Math.min.apply(null, vals), Math.max.apply(null, vals), 4);
        var lo = ticks[0], hi = ticks[ticks.length - 1];
        var step = (w - padL - padR) / s.length;
        var cx = function (i) { return padL + i * step + step / 2; };
        var fy = function (v) { return bottom - (v - lo) / (hi - lo || 1) * (bottom - top); };
        var grid = ticks.map(function (t) {
            return '<line class="grid" x1="' + padL + '" x2="' + (w - padR) + '" y1="' + fy(t).toFixed(1) + '" y2="' + fy(t).toFixed(1) + '"/>'
                + '<text x="' + (padL - 8) + '" y="' + (fy(t) + 4).toFixed(1) + '" text-anchor="end">' + num(t) + '</text>';
        }).join('');
        var line = '', dots = '', posts = '', first = null, last = null;
        s.forEach(function (d, i) {
            if (d.followers !== null) {
                line += (first === null ? 'M' : 'L') + cx(i).toFixed(1) + ' ' + fy(d.followers).toFixed(1) + ' ';
                if (first === null) first = i;
                last = i;
                dots += '<circle class="fdot' + (d.estimated ? ' est' : '') + '" cx="' + cx(i).toFixed(1) + '" cy="' + fy(d.followers).toFixed(1) + '" r="' + (step > 14 ? 3.5 : 2.5) + '">'
                    + '<title>' + esc(dayLabel(d.date)) + ': ' + num(d.followers) + ' followers' + (d.estimated ? ' (perkiraan)' : '') + '</title></circle>';
            }
            if (d.posted && d.posted.length) {
                posts += '<circle class="post" cx="' + cx(i).toFixed(1) + '" cy="' + (bottom + 16) + '" r="5"><title>' + esc(dayLabel(d.date)) + ' tayang: '
                    + d.posted.map(function (p) { return esc(p.judul); }).join(', ') + '</title></circle>';
            }
        });
        var area = first === null ? '' : '<path class="farea" d="' + line + 'L' + cx(last).toFixed(1) + ' ' + bottom + ' L' + cx(first).toFixed(1) + ' ' + bottom + ' Z"/>';
        $(sel).html(svgOpen(w, h, 'Jumlah followers per hari') + grid + area + '<path class="fline" d="' + line + '"/>' + dots + posts
            + xLabels(s, cx, labelEvery(s.length, w - padL), h - 6) + '</svg>');
    }

    function renderFollowChart(s) {
        var sel = '#caFollowChart';
        if (!s.some(function (d) { return d.new !== null || d.unfollows !== null; })) {
            $(sel).html('<div class="wf-muted small py-2">Rincian follow / berhenti follow per hari belum tersedia (akun di bawah 100 followers, atau belum Update insight).</div>');
            return;
        }
        var w = boxWidth(sel), h = 190, padL = 58, padR = 14, top = 12, bottom = 160, mid = (top + bottom) / 2;
        var max = Math.max.apply(null, s.map(function (d) { return Math.max(d.new || 0, d.unfollows || 0); })) || 1;
        var ticks = niceTicks(0, max, 2), hi = ticks[ticks.length - 1];
        var step = (w - padL - padR) / s.length, bw = Math.max(2, Math.min(step * .6, 26));
        var cx = function (i) { return padL + i * step + step / 2; };
        var sc = function (v) { return v / hi * (mid - top); };
        var axis = '<line class="zero" x1="' + padL + '" x2="' + (w - padR) + '" y1="' + mid + '" y2="' + mid + '"/>'
            + '<text x="' + (padL - 8) + '" y="' + (top + 8) + '" text-anchor="end">+' + num(hi) + '</text>'
            + '<text x="' + (padL - 8) + '" y="' + (mid + 4) + '" text-anchor="end">0</text>'
            + '<text x="' + (padL - 8) + '" y="' + bottom + '" text-anchor="end">−' + num(hi) + '</text>';
        var showVal = step >= 22;
        var bars = s.map(function (d, i) {
            var out = '';
            if (d.new) {
                out += '<rect class="bnew" x="' + (cx(i) - bw / 2).toFixed(1) + '" y="' + (mid - sc(d.new)).toFixed(1) + '" width="' + bw.toFixed(1) + '" height="' + sc(d.new).toFixed(1) + '" rx="2">'
                    + '<title>' + esc(dayLabel(d.date)) + ': ' + num(d.new) + ' orang mulai follow</title></rect>'
                    + (showVal ? '<text class="val" x="' + cx(i).toFixed(1) + '" y="' + (mid - sc(d.new) - 3).toFixed(1) + '" text-anchor="middle">' + num(d.new) + '</text>' : '');
            }
            if (d.unfollows) {
                out += '<rect class="bunf" x="' + (cx(i) - bw / 2).toFixed(1) + '" y="' + mid + '" width="' + bw.toFixed(1) + '" height="' + sc(d.unfollows).toFixed(1) + '" rx="2">'
                    + '<title>' + esc(dayLabel(d.date)) + ': ' + num(d.unfollows) + ' orang berhenti follow</title></rect>'
                    + (showVal ? '<text class="val" x="' + cx(i).toFixed(1) + '" y="' + (mid + sc(d.unfollows) + 11).toFixed(1) + '" text-anchor="middle">' + num(d.unfollows) + '</text>' : '');
            }
            return out;
        }).join('');
        $(sel).html(svgOpen(w, h, 'Follow dan berhenti follow per hari') + axis + bars + xLabels(s, cx, labelEvery(s.length, w - padL), h - 6) + '</svg>');
    }

    var GENDER = { F: 'Perempuan', M: 'Laki-laki', U: 'Tidak diketahui' };
    var regionName = (function () {
        try { var dn = new Intl.DisplayNames(['id'], { type: 'region' }); return function (c) { try { return dn.of(c) || c; } catch (e) { return c; } }; }
        catch (e) { return function (c) { return c; }; }
    })();
    function demoLabel(kind, key) {
        if (kind === 'gender') return GENDER[key] || key;
        if (kind === 'country') return regionName(key);
        if (kind === 'age') return key + ' tahun';
        return key.replace(/, .*$/, '');
    }
    function topOf(values) {
        var total = 0, best = null;
        Object.keys(values || {}).forEach(function (k) { total += values[k]; if (best === null || values[k] > values[best]) best = k; });
        return best === null ? null : { key: best, pct: total ? Math.round(values[best] / total * 100) : 0 };
    }
    function renderDemographics(demo) {
        if (!demo || !demo.data) {
            $('#caDemoStory').html('');
            $('#caDemo').html('<div class="wf-muted small" style="grid-column:1/-1">Data usia, gender dan kota followers belum tersedia (butuh akun dengan minimal 100 followers dan sekali Update insight).</div>');
            return;
        }
        var parts = [];
        var g = topOf(demo.data.gender), a = topOf(demo.data.age), c = topOf(demo.data.city);
        if (g) parts.push('<b>' + esc(demoLabel('gender', g.key)) + '</b> (' + g.pct + '%)');
        if (a) parts.push('usia <b>' + esc(demoLabel('age', a.key)) + '</b> (' + a.pct + '%)');
        if (c) parts.push('dari <b>' + esc(demoLabel('city', c.key)) + '</b> (' + c.pct + '%)');
        $('#caDemoStory').html(parts.length ? 'Followers terbanyak: ' + parts.join(', ') + '.' : '');
        var blocks = [['age', 'Usia', 6], ['gender', 'Gender', 3], ['city', 'Kota teratas', 6], ['country', 'Negara teratas', 5]];
        $('#caDemo').html(blocks.map(function (b) {
            var values = demo.data[b[0]] || {};
            var total = Object.keys(values).reduce(function (s, k) { return s + values[k]; }, 0);
            var keys = Object.keys(values).sort(function (x, y) { return values[y] - values[x]; }).slice(0, b[2]);
            if (b[0] === 'age') keys.sort();
            if (!keys.length) return '';
            return '<div><div class="ca-demo-title">' + b[1] + '</div>' + keys.map(function (key) {
                var p = total ? values[key] / total * 100 : 0;
                return '<div class="ca-demo-row"><span class="lbl" title="' + esc(key) + '">' + esc(demoLabel(b[0], key)) + '</span><span class="bar"><span style="width:' + p.toFixed(1) + '%"></span></span><span class="pct">' + p.toFixed(0) + '%</span></div>';
            }).join('') + '</div>';
        }).join('') + '<div class="ca-note" style="grid-column:1/-1">Data per ' + esc(dayLabel(demo.as_of)) + '. Persentase dari seluruh followers akun yang dipilih.</div>');
    }

    // ---- views gained per day (all content)
    function renderTrend() {
        var sel = '#caTrend', t = data.trend || [];
        if (!t.some(function (d) { return d.tracked; })) {
            $('#caTrendStory').html('');
            $(sel).html('<div class="wf-empty">Belum ada data insight di periode ini. Klik <strong>Update insight</strong> setiap pagi agar grafik terisi.</div>');
            return;
        }
        var tracked = t.filter(function (d) { return d.tracked; });
        var sum = tracked.reduce(function (s, d) { return s + d.views; }, 0);
        var bestDay = tracked.slice().sort(function (a, b) { return b.views - a.views; })[0];
        $('#caTrendStory').html('Konten Instagram mendapat <b>+' + num(sum) + ' views baru</b> di periode ini, rata-rata <b>' + num(Math.round(sum / tracked.length)) + ' per hari</b>.'
            + (bestDay && bestDay.views ? ' Hari terbaik: <b>' + esc(dayLabel(bestDay.date)) + '</b> (+' + num(bestDay.views) + ').' : ''));

        var w = boxWidth(sel), h = 230, padL = 58, padR = 14, top = 14, bottom = 200;
        var ticks = niceTicks(0, Math.max.apply(null, t.map(function (d) { return d.views; })) || 1, 4), hi = ticks[ticks.length - 1];
        var step = (w - padL - padR) / t.length, bw = Math.max(2, Math.min(step * .65, 34));
        var cx = function (i) { return padL + i * step + step / 2; };
        var fy = function (v) { return bottom - v / hi * (bottom - top); };
        var grid = ticks.map(function (v) {
            return '<line class="grid" x1="' + padL + '" x2="' + (w - padR) + '" y1="' + fy(v).toFixed(1) + '" y2="' + fy(v).toFixed(1) + '"/>'
                + '<text x="' + (padL - 8) + '" y="' + (fy(v) + 4).toFixed(1) + '" text-anchor="end">' + num(v) + '</text>';
        }).join('');
        var bars = t.map(function (d, i) {
            var x = (cx(i) - bw / 2).toFixed(1);
            if (!d.tracked) return '<rect class="bar is-empty" x="' + x + '" y="' + (bottom - 2) + '" width="' + bw.toFixed(1) + '" height="2"><title>' + esc(dayLabel(d.date)) + ': tidak ada update insight</title></rect>';
            var y = fy(d.views);
            return '<rect class="bar" x="' + x + '" y="' + y.toFixed(1) + '" width="' + bw.toFixed(1) + '" height="' + Math.max(1, bottom - y).toFixed(1) + '" rx="2">'
                + '<title>' + esc(dayLabel(d.date)) + ': +' + num(d.views) + ' views</title></rect>';
        }).join('');
        $(sel).html(svgOpen(w, h, 'Views baru per hari') + grid + bars + xLabels(t, cx, labelEvery(t.length, w - padL), h - 6) + '</svg>');
    }

    // redraw the charts at the new width
    var resizeTimer = null;
    $(window).on('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (!data) return;
            renderTrend();
            if (data.followers && data.followers.detail) {
                renderFollowerChart(data.followers.detail.series);
                renderFollowChart(data.followers.detail.series);
            }
        }, 200);
    });

    // ---- highlights
    function best(list, key) {
        return list.filter(function (i) { return i[key] !== null && i[key] !== undefined && (key !== 'er' || (i.reach || 0) >= 50); })
            .sort(function (a, b) { return b[key] - a[key]; })[0];
    }
    function link(i) { return '<a href="' + PLAN_URL + '?open=' + i.id + '" target="_blank" rel="noopener">' + esc(i.judul) + '</a>'; }
    function renderHighlights() {
        var fresh = data.items.filter(function (i) { return i.published_in_range; });
        var rows = [];
        var v = best(fresh, 'views'), e = best(fresh, 'er'), g = best(data.items, 'views_gain');
        var p = (data.breakdown.pilar || []).filter(function (x) { return x.avg_views !== null; })[0];
        if (v) rows.push(['views', 'far fa-eye', 'Views terbanyak · ' + num(v.views), link(v)]);
        if (e) rows.push(['er', 'fas fa-bolt', 'Engagement tertinggi · ' + pct(e.er), link(e)]);
        if (g && g.views_gain) rows.push(['growth', 'fas fa-arrow-up', 'Naik paling cepat · +' + num(g.views_gain) + ' views', link(g)]);
        if (p) rows.push(['pilar', 'fas fa-layer-group', 'Pilar terbaik · rata-rata ' + num(p.avg_views) + ' views', '<span class="t">' + esc(p.label) + '</span>']);
        $('#caHighlights').html(rows.length ? rows.map(function (r) {
            return '<div class="ca-hl"><span class="ca-hl-ico ' + r[0] + '"><i class="' + r[1] + '"></i></span><div style="min-width:0"><small>' + r[2] + '</small>' + r[3] + '</div></div>';
        }).join('') : '<div class="wf-muted small">Belum ada konten dengan data insight di periode ini.</div>');
    }

    // ---- team track record (values with an arrow against the previous period)
    function cmp(cur, prev, unit, lowerIsBetter) {
        if (cur === null || cur === undefined) return '<span class="wf-muted">–</span>';
        var out = String(cur).replace('.', ',') + (unit || '');
        if (prev === null || prev === undefined || prev === cur) return out;
        var better = lowerIsBetter ? cur < prev : cur > prev;
        return out + ' <span class="' + (better ? 'ca-gain' : 'text-danger') + '" title="Periode sebelumnya: ' + String(prev).replace('.', ',') + (unit || '') + '" style="font-size:.72rem">'
            + (cur > prev ? '▲' : '▼') + '</span>';
    }
    function hours(h) {
        if (h === null || h === undefined) return null;
        return h >= 48 ? Math.round(h / 24 * 10) / 10 + ' hari' : Math.round(h * 10) / 10 + ' jam';
    }
    function renderTeam() {
        var t = data.team || {};
        var c = t.creators || [], d = t.designers || [];
        $('#caCreators').html(c.length
            ? '<thead><tr><th>Nama</th><th class="num">Tayang</th><th class="num">Tepat waktu</th><th class="num">Revisi / konten</th><th class="num">Rata-rata views</th><th class="num">Konten rutin</th><th class="num">Tertunda</th></tr></thead><tbody>'
                + c.map(function (r) {
                    return '<tr><td class="title"><strong>' + esc(r.name) + '</strong></td>'
                        + '<td class="num">' + cmp(r.published, r.prev_published) + ' <span class="wf-muted">/ ' + r.planned + '</span></td>'
                        + '<td class="num">' + cmp(r.on_time, r.prev_on_time, '%') + '</td>'
                        + '<td class="num">' + cmp(r.revisions, r.prev_revisions, '×', true) + '</td>'
                        + '<td class="num">' + num(r.avg_views) + '</td>'
                        + '<td class="num">' + (r.routine ? r.routine.posted + '/' + r.routine.total + ' hari' : '<span class="wf-muted">–</span>') + '</td>'
                        + '<td class="num">' + (r.pending ? '<span class="text-danger font-weight-bold">' + r.pending + '</span>' : '0') + '</td></tr>';
                }).join('') + '</tbody>'
            : '<tr><td class="wf-empty">Belum ada konten dengan PIC di periode ini.</td></tr>');
        $('#caDesigners').html(d.length
            ? '<thead><tr><th>Nama</th><th class="num">Job diserahkan</th><th class="num">Tepat deadline</th><th class="num">Lama pengerjaan</th><th class="num">Revisi / job</th><th class="num">Terlambat (aktif)</th></tr></thead><tbody>'
                + d.map(function (r) {
                    return '<tr><td class="title"><strong>' + esc(r.name) + '</strong></td>'
                        + '<td class="num">' + r.delivered + (r.finished ? ' <span class="wf-muted">(' + r.finished + ' selesai)</span>' : '') + '</td>'
                        + '<td class="num">' + cmp(r.on_deadline, r.prev_on_deadline, '%') + '</td>'
                        + '<td class="num">' + (r.work_hours !== null ? hours(r.work_hours) + (r.prev_work_hours !== null && r.prev_work_hours !== r.work_hours
                            ? ' <span class="' + (r.work_hours < r.prev_work_hours ? 'ca-gain' : 'text-danger') + '" style="font-size:.72rem" title="Periode sebelumnya: ' + esc(hours(r.prev_work_hours)) + '">' + (r.work_hours > r.prev_work_hours ? '▲' : '▼') + '</span>' : '') : '<span class="wf-muted">–</span>') + '</td>'
                        + '<td class="num">' + (r.revisions !== null ? String(r.revisions).replace('.', ',') + '×' : '<span class="wf-muted">–</span>') + '</td>'
                        + '<td class="num">' + (r.late_active ? '<span class="text-danger font-weight-bold">' + r.late_active + '</span>' : '0') + '</td></tr>';
                }).join('') + '</tbody>'
            : '<tr><td class="wf-empty">Belum ada job designer yang diserahkan di periode ini.</td></tr>');
    }

    // ---- best posting time: per time slot and per weekday, best one marked
    function renderPostingTime() {
        var t = data.posting_time;
        if (!t || !t.samples) {
            $('#caTimeStory').html('Belum cukup data. Analisa ini butuh konten yang sudah tayang minimal ' + (t ? t.after_days : 3) + ' hari dan rutin di-<b>Update insight</b>.');
            $('#caTimeSlots, #caTimeDays').empty();
            return;
        }
        var story = 'Dari <b>' + t.samples + ' konten</b>, rata-rata mendapat <b>' + num(t.overall_avg) + ' views</b> di ' + t.after_days + ' hari pertama.';
        if (t.best_slot) {
            story += ' Tayang jam <b>' + esc(t.best_slot.label) + '</b> paling bagus: rata-rata <b>' + num(t.best_slot.avg) + ' views</b>'
                + (t.best_slot.ratio > 1 ? ' (' + Math.round((t.best_slot.ratio - 1) * 100) + '% di atas rata-rata)' : '') + '.';
        }
        if (t.best_day) story += ' Hari terbaik: <b>' + esc(t.best_day.label) + '</b>.';
        if (!t.best_slot && !t.best_day) story += ' Untuk menentukan waktu terbaik, tiap jam/hari butuh minimal ' + t.min_posts + ' konten; terus isi datanya.';
        $('#caTimeStory').html(story);

        var bars = function (groups, best) {
            var max = Math.max.apply(null, groups.map(function (g) { return g.avg || 0; })) || 1;
            return groups.map(function (g) {
                var isBest = best && best.label === g.label;
                return '<div class="ca-break-row' + (isBest ? ' is-best' : '') + '"><div class="ca-break-label">' + esc(g.label) + (isBest ? ' <span class="cp-tag" style="background:var(--st-green-bg);color:var(--st-green-fg)">terbaik</span>' : '') + '</div>'
                    + '<div class="ca-break-bar"><span style="width:' + ((g.avg || 0) / max * 100).toFixed(1) + '%"></span></div>'
                    + '<div class="ca-break-val">' + (g.count ? '<strong>' + num(g.avg) + '</strong> views · ' + g.count + ' konten' : '<span class="wf-muted">belum ada</span>') + '</div></div>';
            }).join('');
        };
        $('#caTimeSlots').html(bars(t.slots, t.best_slot));
        $('#caTimeDays').html(bars(t.days, t.best_day));
    }

    // ---- ranking
    function spark(values) {
        if (!values || values.length < 2) return '';
        var max = Math.max.apply(null, values), min = Math.min.apply(null, values), span = max - min || 1;
        var d = values.map(function (v, i) { return (i ? 'L' : 'M') + (i * 88 / (values.length - 1) + 1).toFixed(1) + ' ' + (24 - (v - min) / span * 22).toFixed(1); }).join(' ');
        return '<svg class="ca-spark" viewBox="0 0 90 26" preserveAspectRatio="none"><path d="' + d + '"/></svg>';
    }
    function logo(b) {
        var m = BRANDS[b];
        return m && m.logo ? '<span class="ca-logo" title="' + esc(m.label) + '"' + (m.logo_bg ? ' style="background:' + m.logo_bg + '"' : '') + '><img src="' + esc(m.logo) + '" alt=""></span>' : '';
    }
    function renderTable() {
        var only = $('#caOnlyNew').is(':checked');
        var list = data.items.filter(function (i) { return !only || i.published_in_range; })
            .filter(function (i) { return sortBy !== 'er' || (i.er !== null && (i.reach || 0) >= 50); })
            .sort(function (a, b) { return (b[sortBy] || 0) - (a[sortBy] || 0); }).slice(0, 20);
        if (!list.length) {
            $('#caTable').html('<tr><td class="wf-empty">Tidak ada konten dengan data insight' + (only ? ' yang tayang di periode ini' : '') + '.</td></tr>');
            return;
        }
        var cols = [['views', 'Views'], ['reach', 'Reach'], ['er', 'ER'], ['views_gain', 'Naik'], ['likes', 'Likes'], ['comments', 'Komen'], ['saves', 'Saves'], ['shares', 'Shares']];
        var head = '<thead><tr><th>#</th><th>Konten</th>' + cols.map(function (c) { return '<th class="num">' + c[1] + '</th>'; }).join('') + '<th>Tren</th></tr></thead>';
        var body = list.map(function (i, n) {
            var meta = (i.brand || []).map(logo).join('')
                + (i.tanggal_publish ? '<span>' + esc(W.date(i.tanggal_publish, { weekday: false })) + '</span>' : '')
                + (i.pilar ? '<span class="cp-tag">' + esc(i.pilar) + '</span>' : '')
                + ((i.jenis || []).length ? '<span>' + esc(i.jenis.join(', ')) + '</span>' : '')
                + (i.on_time === false ? '<span class="cp-tag cp-tag-rev">telat tayang</span>' : '');
            return '<tr><td><span class="ca-rank' + (n < 3 ? ' top' : '') + '">' + (n + 1) + '</span></td>'
                + '<td class="title"><a href="' + PLAN_URL + '?open=' + i.id + '" target="_blank" rel="noopener">' + esc(i.judul) + '</a><div class="ca-meta">' + meta + '</div></td>'
                + cols.map(function (c) {
                    var v = c[0] === 'er' ? pct(i.er) : (c[0] === 'views_gain' ? (i.views_gain ? '<span class="ca-gain">+' + num(i.views_gain) + '</span>' : '–') : num(i[c[0]]));
                    return '<td class="num' + (c[0] === sortBy ? ' is-sort' : '') + '">' + v + '</td>';
                }).join('')
                + '<td>' + spark(i.spark) + '</td></tr>';
        }).join('');
        $('#caTable').html(head + '<tbody>' + body + '</tbody>');
    }
    $('#caSort').on('click', 'button', function () {
        sortBy = $(this).data('sort');
        $('#caSort button').removeClass('active');
        $(this).addClass('active');
        // growth is about all tracked content, the rest about content of the period
        if (sortBy === 'views_gain') $('#caOnlyNew').prop('checked', false);
        if (data) renderTable();
    });
    $('#caOnlyNew').on('change', function () { if (data) renderTable(); });

    // ---- per group
    function renderBreakdown() {
        var list = data.breakdown[group] || [];
        if (!list.length) { $('#caBreakdown').html('<div class="wf-muted small">Belum ada konten yang tayang dengan data insight di periode ini.</div>'); return; }
        var max = Math.max.apply(null, list.map(function (x) { return x.avg_views || 0; })) || 1;
        $('#caBreakdown').html(list.map(function (x) {
            var label = group === 'brand' && BRANDS[x.label] ? BRANDS[x.label].label : x.label;
            return '<div class="ca-break-row"><div class="ca-break-label" title="' + esc(label) + '">' + esc(label) + ' <small>· ' + x.count + ' konten</small></div>'
                + '<div class="ca-break-bar"><span style="width:' + ((x.avg_views || 0) / max * 100).toFixed(1) + '%"></span></div>'
                + '<div class="ca-break-val"><strong>' + num(x.avg_views) + '</strong> views · ER ' + pct(x.avg_er) + '</div></div>';
        }).join(''));
    }
    $('#caGroup').on('click', 'button', function () {
        group = $(this).data('group');
        $('#caGroup button').removeClass('active');
        $(this).addClass('active');
        if (data) renderBreakdown();
    });

    // ---- update insight from here
    $('#caSync').on('click', function () {
        var $b = $(this), html = $b.html();
        $b.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Mengambil…');
        $.post(SYNC_URL).done(function (r) {
            W.toast(r.updated + ' konten diperbarui' + ((r.not_found || []).length ? ', ' + r.not_found.length + ' tidak ditemukan' : ''));
            load();
        }).fail(function (xhr) {
            Swal.fire('Gagal', W.errorMessage(xhr) + (xhr.responseJSON && xhr.responseJSON.no_account ? '<br>Hubungkan akun lewat ikon Instagram di halaman Content Plan.' : ''), 'error');
        }).always(function () { $b.prop('disabled', false).html(html); });
    });

    setRangeUi();
    load();
});
</script>
@endpush

{{-- Asesmen gigi: odontogram (FDI numbering, standard 3-letter codes, per surface) + Status Intra Oral, filled by clicking --}}
@php
    $odgSaved = \App\Support\Odontogram::clean($asesmen->odontogram ?? null);
    $odgCarried = !$odgSaved && !empty($odontogramSebelumnya['data']) ? $odontogramSebelumnya : null;
    $odgData = $odgSaved ?? ($odgCarried['data'] ?? []);
    $intraOral = is_array($asesmen->intra_oral ?? null) ? $asesmen->intra_oral : [];
    $oldDrawing = $asesmen->status_lokalis ?? null;

    $odgIcdCodes = collect(\App\Support\Odontogram::CONDITIONS)->pluck(4)->filter()->unique()->values();
    $odgIcd = \App\Models\ERM\Icd10::whereIn('code', $odgIcdCodes)->pluck('description', 'code');
    // Short palette labels (the full label stays in the tooltip, summary and print)
    $odgShort = [
        'car' => 'Karies', 'amf' => 'Amalgam', 'cof' => 'Komposit', 'gif' => 'GIC', 'fis' => 'Fissure sealant', 'inl' => 'Inlay', 'onl' => 'Onlay',
        'nvt' => 'Non-vital', 'rct' => 'Saluran akar', 'rrx' => 'Sisa akar', 'cfr' => 'Fraktur mahkota', 'mis' => 'Hilang',
        'une' => 'Belum erupsi', 'pre' => 'Erupsi sebagian', 'imv' => 'Impaksi', 'ano' => 'Anomali', 'per' => 'Persistensi',
        'fmc' => 'Mahkota logam', 'poc' => 'Mahkota porselen', 'mpc' => 'Mahkota PFM', 'gmc' => 'Mahkota emas', 'vnc' => 'Veneer',
        'ipx' => 'Implan', 'meb' => 'Bridge logam', 'pob' => 'Bridge porselen', 'prd' => 'GT sebagian', 'fld' => 'GT lengkap',
    ];
    $odgConditions = collect(\App\Support\Odontogram::CONDITIONS)->map(fn ($c, $code) => [
        'label' => $c[0], 'short' => $odgShort[$code] ?? $c[0], 'color' => $c[1], 'group' => $c[2], 'surface' => $c[3],
        'icd' => $c[4] && isset($odgIcd[$c[4]]) ? $c[4] . ' - ' . $odgIcd[$c[4]] : null,
        'dmf' => $c[5],
    ]);
    $odgGroups = $odgConditions->groupBy('group', true);
@endphp

<style>
    .odg-card { border: 1px solid #dee2e6; border-radius: .5rem; padding: 1rem; margin-bottom: 1rem; }
    .odg-steps { font-size: .85rem; color: #6c757d; margin-bottom: .5rem; }
    .odg-steps b { color: #343a40; }
    /* Chart left, compact palette right; on narrow screens the palette comes first */
    /* Side by side only when the card itself is wide enough for the full chart (container query), else stacked */
    .odg-cq { width: 100%; container-type: inline-size; }
    .odg-layout { display: flex; flex-wrap: wrap; gap: 1rem 1.25rem; align-items: flex-start; }
    .odg-chart-col { flex: 1 1 100%; min-width: 0; }
    .odg-palette-col { flex: 1 1 100%; }
    @container (min-width: 1120px) {
        .odg-chart-col { flex: 1 1 760px; }
        .odg-palette-col { flex: 0 1 330px; order: 1; border-left: 1px solid #dee2e6; padding-left: 1rem; }
    }
    .odg-group-label { font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; margin: .5rem 0 .25rem; }
    .odg-group-label:first-child { margin-top: 0; }
    .odg-palette { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 4px; }
    .odg-chip { display: flex; align-items: center; border: 1px solid #ced4da; background: #fff; color: #343a40; border-radius: 6px; padding: .15rem .35rem; font-size: .74rem; line-height: 1.35; cursor: pointer; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .odg-chip .odg-dot { flex: none; display: inline-block; width: 2.6em; padding: 0 .2em; margin-right: .35rem; border-radius: 4px; color: #fff; font-weight: 700; font-size: .62rem; text-align: center; text-transform: uppercase; }
    .odg-chip .odg-chip-text { overflow: hidden; text-overflow: ellipsis; }
    .odg-chip.active { border-color: #0d6efd; box-shadow: 0 0 0 .15rem rgba(13,110,253,.35); background: #e7f1ff; font-weight: 600; }
    .odg-chip.odg-clear.active { border-color: #6c757d; box-shadow: 0 0 0 .15rem rgba(108,117,125,.35); background: #f1f3f5; }
    .odg-palette-wrap.odg-nudge { animation: odgNudge .5s; }
    @keyframes odgNudge { 0%,100% { transform: none; } 25% { transform: translateX(-4px); } 75% { transform: translateX(4px); } }
    .odg-surface-bar { display: none; align-items: center; gap: .35rem; flex-wrap: wrap; margin: .6rem 0 .2rem; font-size: .8rem; }
    .odg-surface-bar.show { display: flex; }
    .odg-surface { border: 1px solid #ced4da; background: #fff; color: #343a40; border-radius: 6px; min-width: 2.2rem; padding: .1rem .4rem; font-weight: 700; font-size: .78rem; cursor: pointer; }
    .odg-surface.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
    .odg-chart { overflow-x: auto; padding-bottom: .25rem; margin-top: .4rem; }
    .odg-chart-inner { min-width: 660px; max-width: 820px; margin: 0 auto; }
    .odg-row { display: flex; align-items: center; margin-bottom: .3rem; }
    .odg-row-label { width: 100px; flex: none; font-size: .72rem; color: #6c757d; }
    .odg-half { flex: 1; display: flex; gap: 3px; }
    .odg-half.right-side { justify-content: flex-end; padding-right: 6px; border-right: 2px dashed #adb5bd; }
    .odg-half.left-side { justify-content: flex-start; padding-left: 6px; }
    .odg-tooth { width: 36px; flex: none; border: 1px solid transparent; border-radius: 6px; background: transparent; padding: 1px 0 2px; display: flex; flex-direction: column; align-items: center; cursor: pointer; position: relative; }
    .odg-tooth:hover { border-color: #0d6efd; }
    .odg-tooth:focus { outline: none; box-shadow: 0 0 0 .15rem rgba(13,110,253,.4); }
    .odg-tooth .odg-num { font-size: .68rem; color: #495057; line-height: 1.3; }
    .odg-tooth svg { width: 30px; height: 30px; display: block; }
    .odg-tooth svg polygon, .odg-tooth svg rect { stroke: #6c757d; stroke-width: 1; fill: #fff; }
    .odg-tooth .odg-codes { min-height: 12px; font-size: .55rem; font-weight: 700; line-height: 1.15; text-transform: uppercase; text-align: center; }
    .odg-legend-note { font-size: .75rem; color: #6c757d; }
    .odg-summary { margin: .75rem 0 0; padding: 0; list-style: none; font-size: .85rem; }
    .odg-summary li { display: flex; align-items: center; gap: .5rem; padding: .2rem 0; border-bottom: 1px dotted #e9ecef; }
    .odg-summary .odg-sum-tooth { font-weight: 700; min-width: 2rem; }
    .odg-summary .odg-sum-remove { margin-left: auto; border: 0; background: none; color: #dc3545; font-size: .8rem; cursor: pointer; white-space: nowrap; }
    .odg-stats { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: .6rem; font-size: .85rem; }
    .odg-stats b { font-size: 1rem; }
    .odg-icd { margin-top: .6rem; }
    .odg-icd .btn { margin: 0 .3rem .3rem 0; }
    .odg-intra td { padding: .3rem .4rem; vertical-align: middle; }
    .odg-intra td:first-child { width: 170px; font-weight: 500; }
    .odg-intra .custom-control-inline { margin-right: .9rem; }
    /* ERM default theme is dark */
    html.theme-dark .odg-card { border-color: #3a4150; }
    @container (min-width: 1120px) { html.theme-dark .odg-palette-col { border-left-color: #3a4150; } }
    html.theme-dark .odg-steps, html.theme-dark .odg-group-label, html.theme-dark .odg-row-label, html.theme-dark .odg-legend-note { color: #a9b1c1; }
    html.theme-dark .odg-steps b { color: #e9ecef; }
    html.theme-dark .odg-chip, html.theme-dark .odg-surface { background: #262b36; border-color: #3a4150; color: #e9ecef; }
    html.theme-dark .odg-chip.active { background: #1c3a66; border-color: #4d8dff; }
    html.theme-dark .odg-chip.odg-clear.active { background: #3a4150; }
    html.theme-dark .odg-surface.active { background: #0d6efd; border-color: #0d6efd; }
    html.theme-dark .odg-tooth .odg-num { color: #e9ecef; }
    html.theme-dark .odg-tooth svg polygon, html.theme-dark .odg-tooth svg rect { stroke: #8a93a6; fill: #262b36; }
    html.theme-dark .odg-summary li { border-bottom-color: #3a4150; }
    html.theme-dark .odg-summary .odg-sum-remove { color: #ff6b7a; }
</style>

<div class="form-group">
    <label class="form-label font-weight-bold">Status Lokalis — Odontogram</label>

    <div class="odg-card" id="odgCard">
        @if ($odgCarried)
            <div class="alert alert-info py-2 small mb-2">
                <i class="fas fa-history mr-1"></i>
                Dilanjutkan dari odontogram kunjungan <b>{{ \Carbon\Carbon::parse($odgCarried['tanggal'])->translatedFormat('j F Y') }}</b>.
                Periksa dan sesuaikan dengan kondisi hari ini sebelum menyimpan.
            </div>
        @endif

        <div class="odg-steps">
            <b>1.</b> Pilih kondisi &nbsp;→&nbsp; <b>2.</b> karies / tumpatan: pilih permukaan (opsional) &nbsp;→&nbsp; <b>3.</b> klik gigi.
            Klik lagi untuk membatalkan. Tanpa tanda = <b>sehat</b>.
        </div>

        <div class="odg-cq">
        <div class="odg-layout">
        <div class="odg-palette-col">
        <div class="odg-palette-wrap" id="odgPalette">
            @foreach ($odgGroups as $groupName => $items)
                <div class="odg-group-label">{{ $groupName }}</div>
                <div class="odg-palette">
                    @foreach ($items as $code => $c)
                        <button type="button" class="odg-chip" data-code="{{ $code }}" title="{{ $c['label'] }}{{ $c['icd'] ? ' — saran ICD-10: ' . $c['icd'] : '' }}">
                            <span class="odg-dot" style="background: {{ $c['color'] }}">{{ $code }}</span><span class="odg-chip-text">{{ $c['short'] }}</span>
                        </button>
                    @endforeach
                </div>
            @endforeach
            <div class="odg-palette mt-2">
                <button type="button" class="odg-chip odg-clear" data-code="__clear" title="Klik gigi untuk menghapus semua tandanya">
                    <i class="fas fa-eraser mr-2"></i><span class="odg-chip-text">Hapus tanda gigi</span>
                </button>
            </div>
        </div>
        </div>

        <div class="odg-chart-col">
        <div class="odg-surface-bar" id="odgSurfaceBar">
            <span>Permukaan:</span>
            @foreach (\App\Support\Odontogram::SURFACES as $s => $sLabel)
                <button type="button" class="odg-surface" data-surface="{{ $s }}" title="{{ $sLabel }}">{{ $s }}</button>
            @endforeach
            <span class="odg-legend-note">M mesial · O oklusal/insisal · D distal · V bukal/labial · L lingual/palatal — kosongkan bila seluruh gigi</span>
        </div>

        <div class="odg-chart">
            <div class="odg-chart-inner">
                <div class="d-flex small text-muted mb-1">
                    <div style="width:100px"></div>
                    <div class="flex-fill text-right pr-2">Kanan pasien</div>
                    <div class="flex-fill pl-2">Kiri pasien</div>
                </div>
                @foreach (\App\Support\Odontogram::ROWS as $rowLabel => [$rightSide, $leftSide])
                    <div class="odg-row">
                        <div class="odg-row-label">{{ $rowLabel }}</div>
                        @foreach (['right-side' => $rightSide, 'left-side' => $leftSide] as $side => $teeth)
                            <div class="odg-half {{ $side }}">
                                @foreach ($teeth as $tooth)
                                    <button type="button" class="odg-tooth" data-tooth="{{ $tooth }}" data-upper="{{ str_contains($rowLabel, 'atas') ? 1 : 0 }}" data-side="{{ $side }}">
                                        <span class="odg-num">{{ $tooth }}</span>
                                        <svg viewBox="0 0 30 30" aria-hidden="true">
                                            <polygon data-pos="top" points="0,0 30,0 21,9 9,9"></polygon>
                                            <polygon data-pos="bottom" points="9,21 21,21 30,30 0,30"></polygon>
                                            <polygon data-pos="left" points="0,0 9,9 9,21 0,30"></polygon>
                                            <polygon data-pos="right" points="30,0 30,30 21,21 21,9"></polygon>
                                            <rect data-pos="center" x="9" y="9" width="12" height="12"></rect>
                                            <g class="odg-overlay"></g>
                                        </svg>
                                        <span class="odg-codes"></span>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <ul class="odg-summary" id="odgSummary"></ul>

        <div class="odg-stats" id="odgStats"></div>

        <div class="odg-icd" id="odgIcd"></div>
        </div>{{-- /odg-chart-col --}}
        </div>{{-- /odg-layout --}}
        </div>{{-- /odg-cq --}}

        <input type="hidden" name="odontogram" id="odontogramInput" value="{{ json_encode((object) $odgData) }}">

        @php
            $odgRiwayat = isset($visitation) && \Illuminate\Support\Facades\Schema::hasTable('erm_odontogram_riwayat')
                ? \App\Models\ERM\OdontogramRiwayat::with('user:id,name')
                    ->where('pasien_id', $visitation->pasien_id)
                    ->orderByDesc('created_at')->orderByDesc('id')
                    ->limit(30)->get()
                : collect();
        @endphp
        @if ($odgRiwayat->isNotEmpty())
            <details class="mt-3">
                <summary class="small font-weight-bold" style="cursor:pointer;">
                    <i class="fas fa-history mr-1"></i>Riwayat perubahan odontogram pasien ({{ $odgRiwayat->count() }}{{ $odgRiwayat->count() >= 30 ? ' terakhir' : '' }})
                </summary>
                <table class="table table-sm mt-2 mb-0 small">
                    <thead><tr><th style="width:140px">Waktu</th><th style="width:160px">Oleh</th><th>Perubahan</th></tr></thead>
                    <tbody>
                        @foreach ($odgRiwayat as $r)
                            <tr>
                                <td>
                                    {{ $r->created_at?->translatedFormat('j M Y H:i') }}
                                    @if ($r->visitation_id === ($visitation->id ?? null))
                                        <span class="badge badge-info">kunjungan ini</span>
                                    @endif
                                </td>
                                <td>{{ $r->user->name ?? '-' }}</td>
                                <td>{!! collect($r->perubahan)->map(fn ($line) => e($line))->implode('<br>') !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        @endif
    </div>

    <label class="form-label font-weight-bold">Status Intra Oral</label>
    <div class="odg-card">
        <div class="small text-muted mb-1">Klik pilihan yang sudah terpilih untuk mengosongkannya.</div>
        <table class="table table-sm table-borderless odg-intra mb-0">
            <tbody>
                @foreach (\App\Support\Odontogram::INTRA_ORAL as $field => [$label, $options, $hasKet])
                    <tr>
                        <td>{{ $label }}</td>
                        <td>
                            @foreach ($options as $i => $option)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" id="io_{{ $field }}_{{ $i }}"
                                        name="intra_oral[{{ $field }}]" value="{{ $option }}" @checked(($intraOral[$field] ?? null) === $option)>
                                    <label class="custom-control-label" for="io_{{ $field }}_{{ $i }}">{{ $option }}</label>
                                </div>
                            @endforeach
                            @if ($hasKet)
                                <input type="text" class="form-control form-control-sm d-inline-block mt-1" style="max-width: 280px;"
                                    name="intra_oral[{{ $field }}_ket]" value="{{ $intraOral[$field . '_ket'] ?? '' }}" placeholder="Keterangan (jika ada)">
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td>Lain-lain</td>
                    <td><input type="text" class="form-control form-control-sm" name="intra_oral[lain_lain]" value="{{ $intraOral['lain_lain'] ?? '' }}"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <label class="form-label">Catatan status lokalis</label>
    <textarea class="form-control mb-2" name="ket_status_lokalis" rows="3" placeholder="Catatan tambahan (opsional)...">{{ old('ket_status_lokalis', $asesmen->ket_status_lokalis ?? '') }}</textarea>

    @if ($oldDrawing)
        <details class="mt-2">
            <summary class="small text-muted" style="cursor:pointer;">Lihat coretan status lokalis yang tersimpan sebelumnya</summary>
            <img src="{{ asset($oldDrawing) }}" class="img-fluid border rounded mt-2" alt="Coretan status lokalis">
        </details>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var conditions = @json($odgConditions);
    var input = document.getElementById('odontogramInput');
    var palette = document.getElementById('odgPalette');
    var surfaceBar = document.getElementById('odgSurfaceBar');
    var summary = document.getElementById('odgSummary');
    var stats = document.getElementById('odgStats');
    var icdBox = document.getElementById('odgIcd');
    if (!input || !palette) return;

    var data = {};
    try { data = JSON.parse(input.value || '{}') || {}; } catch (e) { data = {}; }
    if (Array.isArray(data)) data = {};
    var active = null;
    var surfaces = [];
    var teethButtons = Array.prototype.slice.call(document.querySelectorAll('.odg-tooth'));
    var toothOrder = teethButtons.map(function (b) { return b.dataset.tooth; });

    function isDeciduous(t) { return ['5', '6', '7', '8'].indexOf(String(t).charAt(0)) !== -1; }

    // Which drawn side of the tooth is which surface: mesial faces the midline,
    // vestibular is the outer side (top for upper teeth, bottom for lower teeth)
    function surfacePos(btn, surface) {
        var upper = btn.dataset.upper === '1';
        var rightSide = btn.dataset.side === 'right-side';
        switch (surface) {
            case 'O': return 'center';
            case 'V': return upper ? 'top' : 'bottom';
            case 'L': return upper ? 'bottom' : 'top';
            case 'M': return rightSide ? 'right' : 'left';
            case 'D': return rightSide ? 'left' : 'right';
        }
        return null;
    }

    function codesOf(t) { return data[t] ? Object.keys(data[t]) : []; }

    function svgEl(name, attrs) {
        var el = document.createElementNS('http://www.w3.org/2000/svg', name);
        Object.keys(attrs).forEach(function (k) { el.setAttribute(k, attrs[k]); });
        return el;
    }

    function renderTooth(btn) {
        var t = btn.dataset.tooth;
        var tooth = data[t] || {};
        var codes = Object.keys(tooth);
        var parts = {};
        btn.querySelectorAll('svg [data-pos]').forEach(function (p) { p.style.fill = ''; parts[p.dataset.pos] = p; });

        // Surface conditions colour their surfaces; whole-tooth surface conditions colour all five
        codes.forEach(function (code) {
            var c = conditions[code];
            if (!c || !c.surface) return;
            var list = tooth[code].length ? tooth[code] : ['M', 'O', 'D', 'V', 'L'];
            list.forEach(function (s) {
                var pos = surfacePos(btn, s);
                if (pos && parts[pos] && !parts[pos].style.fill) parts[pos].style.fill = c.color;
            });
        });

        // Whole-tooth conditions: outline in their colour; missing = cross; radix = root mark
        var overlay = btn.querySelector('.odg-overlay');
        overlay.innerHTML = '';
        var whole = codes.filter(function (code) { return conditions[code] && !conditions[code].surface; });
        if (whole.length) {
            overlay.appendChild(svgEl('rect', { x: 1, y: 1, width: 28, height: 28, fill: 'none', stroke: conditions[whole[0]].color, 'stroke-width': 3 }));
        }
        if (codes.indexOf('mis') !== -1) {
            overlay.appendChild(svgEl('line', { x1: 2, y1: 2, x2: 28, y2: 28, stroke: conditions.mis.color, 'stroke-width': 3 }));
            overlay.appendChild(svgEl('line', { x1: 28, y1: 2, x2: 2, y2: 28, stroke: conditions.mis.color, 'stroke-width': 3 }));
        }
        if (codes.indexOf('rrx') !== -1) {
            overlay.appendChild(svgEl('polyline', { points: '8,6 15,24 22,6', fill: 'none', stroke: conditions.rrx.color, 'stroke-width': 3 }));
        }

        var codeBox = btn.querySelector('.odg-codes');
        codeBox.innerHTML = '';
        codes.forEach(function (code) {
            var s = document.createElement('div');
            s.textContent = code;
            s.style.color = conditions[code] ? conditions[code].color : '';
            codeBox.appendChild(s);
        });

        btn.title = t + ': ' + (codes.length ? describe(t) : 'sehat (sou)');
    }

    function describe(t) {
        return codesOf(t).map(function (code) {
            var s = data[t][code];
            return (conditions[code] ? conditions[code].label : code) + (s && s.length ? ' (' + s.join(', ') + ')' : '');
        }).join(', ');
    }

    function renderSummary() {
        summary.innerHTML = '';
        var teeth = toothOrder.filter(function (t) { return codesOf(t).length; });
        if (!teeth.length) {
            summary.innerHTML = '<li class="text-muted">Belum ada gigi yang ditandai (semua sehat).</li>';
            return;
        }
        teeth.forEach(function (t) {
            var li = document.createElement('li');
            var num = document.createElement('span');
            num.className = 'odg-sum-tooth';
            num.textContent = t;
            var text = document.createElement('span');
            text.textContent = describe(t);
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'odg-sum-remove';
            rm.innerHTML = '<i class="fas fa-times"></i> hapus';
            rm.addEventListener('click', function () { delete data[t]; refresh(); });
            li.appendChild(num);
            li.appendChild(text);
            li.appendChild(rm);
            summary.appendChild(li);
        });
    }

    // DMF-T (permanent) and def-t (deciduous): a tooth counts once, M before D before F
    function renderStats() {
        var r = { D: 0, M: 0, F: 0, d: 0, e: 0, f: 0 };
        toothOrder.forEach(function (t) {
            var roles = codesOf(t).map(function (code) { return conditions[code] ? conditions[code].dmf : null; });
            var role = roles.indexOf('M') !== -1 ? 'M' : (roles.indexOf('D') !== -1 ? 'D' : (roles.indexOf('F') !== -1 ? 'F' : null));
            if (!role) return;
            if (isDeciduous(t)) r[{ D: 'd', M: 'e', F: 'f' }[role]]++; else r[role]++;
        });
        stats.innerHTML =
            '<div>DMF-T: <b>' + (r.D + r.M + r.F) + '</b> <span class="text-muted">(D ' + r.D + ' · M ' + r.M + ' · F ' + r.F + ')</span></div>' +
            '<div>def-t: <b>' + (r.d + r.e + r.f) + '</b> <span class="text-muted">(d ' + r.d + ' · e ' + r.e + ' · f ' + r.f + ')</span></div>';
    }

    // ICD-10 suggestions from the marked conditions; a click puts one into the next empty Diagnosa Kerja
    function renderIcd() {
        var byIcd = {};
        toothOrder.forEach(function (t) {
            codesOf(t).forEach(function (code) {
                var c = conditions[code];
                if (!c || !c.icd) return;
                (byIcd[c.icd] = byIcd[c.icd] || []).push(t);
            });
        });
        var keys = Object.keys(byIcd);
        icdBox.innerHTML = '';
        if (!keys.length) return;
        var label = document.createElement('div');
        label.className = 'small text-muted mb-1';
        label.textContent = 'Saran diagnosa ICD-10 dari odontogram (klik untuk menambahkan ke Diagnosa Kerja):';
        icdBox.appendChild(label);
        keys.forEach(function (icd) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-outline-info';
            b.textContent = icd + ' (gigi ' + byIcd[icd].join(', ') + ')';
            b.addEventListener('click', function () { addDiagnosis(icd); });
            icdBox.appendChild(b);
        });
    }

    function toast(icon, title) {
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: icon, title: title });
    }

    function addDiagnosis(text) {
        var selects = Array.prototype.slice.call(document.querySelectorAll('select.select2-icd10'));
        if (selects.some(function (s) { return s.value === text; })) { toast('info', 'Diagnosa ini sudah ada'); return; }
        var empty = selects.filter(function (s) { return !s.value; })[0];
        if (!empty) { toast('warning', 'Diagnosa kerja sudah penuh (maks. 5)'); return; }
        var opt = new Option(text, text, true, true);
        empty.appendChild(opt);
        if (window.jQuery) jQuery(empty).trigger('change'); else empty.dispatchEvent(new Event('change'));
        toast('success', 'Ditambahkan ke Diagnosa Kerja');
    }

    function save() { input.value = JSON.stringify(data); }

    function refresh() {
        teethButtons.forEach(renderTooth);
        renderSummary();
        renderStats();
        renderIcd();
        save();
    }

    palette.addEventListener('click', function (e) {
        var chip = e.target.closest('.odg-chip');
        if (!chip) return;
        var code = chip.dataset.code;
        active = (active === code) ? null : code;
        palette.querySelectorAll('.odg-chip').forEach(function (c) { c.classList.toggle('active', c.dataset.code === active); });
        var perSurface = !!(active && conditions[active] && conditions[active].surface);
        surfaceBar.classList.toggle('show', perSurface);
        if (!perSurface) {
            surfaces = [];
            surfaceBar.querySelectorAll('.odg-surface').forEach(function (b) { b.classList.remove('active'); });
        }
    });

    surfaceBar.addEventListener('click', function (e) {
        var b = e.target.closest('.odg-surface');
        if (!b) return;
        var s = b.dataset.surface;
        var i = surfaces.indexOf(s);
        if (i === -1) surfaces.push(s); else surfaces.splice(i, 1);
        b.classList.toggle('active', i === -1);
    });

    teethButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var t = btn.dataset.tooth;
            if (!active) {
                palette.classList.remove('odg-nudge');
                void palette.offsetWidth;
                palette.classList.add('odg-nudge');
                toast('info', 'Pilih kondisi dulu, lalu klik gigi');
                return;
            }
            if (active === '__clear') {
                delete data[t];
            } else {
                var tooth = data[t] || {};
                var chosen = conditions[active].surface ? ['M', 'O', 'D', 'V', 'L'].filter(function (s) { return surfaces.indexOf(s) !== -1; }) : [];
                var current = tooth[active];
                // Same condition again with the same surfaces = remove; other surfaces = update
                if (current && current.join() === chosen.join()) {
                    delete tooth[active];
                } else {
                    tooth[active] = chosen;
                }
                if (Object.keys(tooth).length) data[t] = tooth; else delete data[t];
            }
            refresh();
        });
    });

    refresh();

    // Intra oral: clicking the already-selected option clears it (a wrong click can be undone)
    document.querySelectorAll('.odg-intra input[type=radio]').forEach(function (radio) {
        radio.addEventListener('mousedown', function () { radio.dataset.wasChecked = radio.checked ? '1' : ''; });
        radio.addEventListener('keydown', function () { radio.dataset.wasChecked = ''; });
        radio.addEventListener('click', function () {
            if (radio.dataset.wasChecked === '1') {
                radio.checked = false;
            }
            radio.dataset.wasChecked = '';
        });
    });
    document.querySelectorAll('.odg-intra label.custom-control-label').forEach(function (label) {
        label.addEventListener('mousedown', function () {
            var radio = document.getElementById(label.getAttribute('for'));
            if (radio) radio.dataset.wasChecked = radio.checked ? '1' : '';
        });
    });
});
</script>

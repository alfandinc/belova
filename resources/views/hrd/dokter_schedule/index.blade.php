@extends('layouts.hrd.app')
@section('title', 'HRD | Jadwal Dokter')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<style>
    .sched-toolbar { position: sticky; top: 0; z-index: 30; background: #fff; padding: 8px 0; border-bottom: 1px solid #e8ebf3; }
    .sched-toolbar .btn { white-space: nowrap; }
    .sched-palette { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .sched-palette .pal-chip { border: 1px solid #d0d7e2; background: #fff; border-radius: 4px; padding: 3px 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .sched-palette .pal-chip:hover { background: #f1f5ff; }
    .sched-palette .pal-chip kbd { font-size: 10px; padding: 0 4px; margin-right: 4px; }
    .sched-scroll { max-height: calc(100vh - 250px); overflow: auto; border: 1px solid #dee2e6; }
    .sched-table { border-collapse: separate; border-spacing: 0; user-select: none; }
    .sched-table th, .sched-table td { padding: 4px 6px; vertical-align: middle; }
    .sched-table thead th { position: sticky; top: 0; z-index: 3; background: #f8f9fa; text-align: center; cursor: pointer; vertical-align: top !important; font-weight: 700; }
    .sched-table thead th small { color: #6c757d; font-weight: 700; }
    .sched-table tfoot td { position: sticky; bottom: 0; z-index: 3; background: #f8f9fa; }
    .sched-table .sched-name-col { position: sticky; left: 0; z-index: 2; background: #fff; min-width: 220px; max-width: 260px; }
    .sched-table thead .sched-name-col, .sched-table tfoot .sched-name-col { z-index: 4; background: #f8f9fa; cursor: default; }
    .sched-table .sched-emp { cursor: pointer; font-size: 13px; }
    .sched-table .sched-emp:hover { background: #f1f5ff; }
    .sched-table th.is-today { background: #e3f2fd; color: #0d47a1; }
    .sched-table th.is-weekend small { color: #dc3545; }
    .sched-division td { background: #eef1f6 !important; font-weight: 700; color: #333; cursor: pointer; }
    .sched-division.collapsed .sched-caret { transform: rotate(-90deg); }
    .sched-caret { transition: transform .15s; font-size: 11px; width: 12px; }
    .doctor-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; border: 1px solid rgba(0,0,0,.15); }
    td.sc { min-width: 110px; height: 34px; cursor: cell; position: relative; }
    td.sc.is-today { background: #f5faff; }
    td.sc.sel { box-shadow: inset 0 0 0 2px #1e88e5; background: #e3f2fd; }
    td.sc.cursor { box-shadow: inset 0 0 0 3px #0d47a1; }
    td.sc.dirty::after { content: ''; position: absolute; top: 0; right: 0; border-style: solid; border-width: 0 9px 9px 0; border-color: transparent #ff9800 transparent transparent; }
    .sc-chip { display: block; border-radius: 3px; padding: 1px 6px; font-size: 12px; font-weight: 600; line-height: 1.5; white-space: nowrap; text-align: center; }
    .sc-empty { color: #c0c4cc; display: block; text-align: center; }
    #time-picker { position: fixed; z-index: 1050; width: 260px; background: #fff; border: 1px solid #d0d7e2; border-radius: 6px; box-shadow: 0 6px 24px rgba(0,0,0,.15); display: none; }
    #time-picker .sp-head { padding: 6px 10px; font-size: 12px; color: #6c757d; border-bottom: 1px solid #eee; }
    #time-picker .sp-list { max-height: 240px; overflow-y: auto; }
    #time-picker .sp-item { display: flex; align-items: center; padding: 5px 10px; cursor: pointer; font-size: 13px; }
    #time-picker .sp-item:hover { background: #f1f5ff; }
    #time-picker .sp-item kbd { font-size: 10px; margin-right: 8px; }
    #time-picker .sp-custom { border-top: 1px solid #eee; padding: 6px 8px; }
    #time-picker .sp-foot { border-top: 1px solid #eee; padding: 6px 8px; display: flex; justify-content: space-between; }
    .sched-help kbd { font-size: 10px; }
</style>

<div class="container-fluid">
    <div class="sched-toolbar">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center flex-wrap">
                <h4 class="mb-0 font-weight-bold mr-3">Jadwal Dokter</h4>
                <div class="btn-group btn-group-sm mr-2">
                    <button type="button" class="btn btn-outline-primary" id="prev-week-btn" title="Minggu sebelumnya (Alt+←)">&laquo;</button>
                    <button type="button" class="btn btn-outline-primary font-weight-bold" id="week-range" title="Pilih tanggal">
                        {{ $startOfWeek->format('d M Y') }} - {{ $startOfWeek->copy()->addDays(6)->format('d M Y') }}
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="next-week-btn" title="Minggu berikutnya (Alt+→)">&raquo;</button>
                </div>
                <input type="date" id="week-jump" class="d-none">
                <button id="this-week-btn" type="button" class="btn btn-outline-success btn-sm mr-2">Minggu Ini</button>
            </div>
            <div class="d-flex align-items-center flex-wrap">
                <input type="search" id="emp-search" class="form-control form-control-sm mr-2" placeholder="Cari dokter... ( / )" style="width:180px;">
                <select id="division-filter" class="form-control form-control-sm mr-2" style="width:auto;">
                    <option value="">Semua Klinik</option>
                </select>
                <div class="btn-group btn-group-sm mr-2">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-copy"></i> Copy Minggu
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="#" class="dropdown-item copy-week-option" data-target="this">Copy minggu ini ke Minggu Ini</a>
                        <a href="#" class="dropdown-item copy-week-option" data-target="next">Copy minggu ini ke Minggu Depan</a>
                        <a href="#" class="dropdown-item copy-week-option" data-target="following">Copy minggu ini ke minggu setelahnya</a>
                    </div>
                </div>
                <a href="#" id="print-btn" target="_blank" class="btn btn-outline-secondary btn-sm mr-2" title="Print jadwal bulanan"><i class="fa fa-print"></i> Print</a>
                <button id="undo-btn" type="button" class="btn btn-outline-secondary btn-sm mr-2" disabled title="Undo (Ctrl+Z)"><i class="fa fa-undo"></i></button>
                <button id="save-btn" type="button" class="btn btn-primary btn-sm" disabled title="Simpan (Ctrl+S)">
                    <i class="fa fa-save"></i> Simpan <span class="badge badge-light ml-1" id="pending-count">0</span>
                </button>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="sched-palette" id="sched-palette"></div>
            <a href="#" class="small text-muted" data-toggle="collapse" data-target="#sched-help">Pintasan keyboard</a>
        </div>
        <div id="sched-help" class="collapse small text-muted sched-help mt-2">
            <b>Pilih sel:</b> klik / tarik (drag) untuk blok, <kbd>Shift</kbd>+klik rentang, <kbd>Ctrl</kbd>+klik tambah sel, klik nama dokter = 1 minggu, klik header hari = 1 kolom.
            &nbsp;<b>Isi:</b> <kbd>1</kbd> jam default dokter, <kbd>2</kbd>–<kbd>9</kbd> jam lain di palet, atau isi jam sendiri di popup; <kbd>Del</kbd> kosongkan.
            &nbsp;<b>Lainnya:</b> <kbd>←↑↓→</kbd> pindah sel, <kbd>Enter</kbd> buka pilihan, <kbd>Ctrl+C</kbd>/<kbd>Ctrl+V</kbd> copy-paste, <kbd>Ctrl+Z</kbd> undo, <kbd>Ctrl+S</kbd> simpan, <kbd>Esc</kbd> batal pilih.
        </div>
    </div>

    <div id="ajax-loading" style="display:none;text-align:center;" class="my-2">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat...
    </div>
    <div id="jadwal-wrapper" class="mt-2">
        @include('hrd.dokter_schedule._table')
    </div>

    <!-- Time picker popup (shared) -->
    <div id="time-picker">
        <div class="sp-head" id="sp-head"></div>
        <div class="sp-list" id="sp-list"></div>
        <div class="sp-custom">
            <div class="small text-muted mb-1">Jam lain</div>
            <div class="d-flex align-items-center">
                <input type="time" id="sp-start" class="form-control form-control-sm">
                <span class="mx-1">–</span>
                <input type="time" id="sp-end" class="form-control form-control-sm">
                <button type="button" class="btn btn-sm btn-primary ml-1" id="sp-apply">OK</button>
            </div>
        </div>
        <div class="sp-foot">
            <button type="button" class="btn btn-sm btn-outline-danger" id="sp-clear"><i class="fa fa-eraser"></i> Kosongkan</button>
            <button type="button" class="btn btn-sm btn-light" id="sp-close">Tutup</button>
        </div>
    </div>

    <!-- Modal jam default & warna dokter -->
    <div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="shift-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="shiftModalLabel">Jam Default Dokter</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="shift-id">
                        <div class="form-group">
                            <label>Dokter</label>
                            <select id="shift-dokter" class="form-control" required>
                                <option value="">-- Pilih Dokter --</option>
                                @foreach(collect($doktersByKlinik)->flatten(1) as $d)
                                    <option value="{{ $d->id }}">{{ $d->schedule_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label>Jam Mulai</label>
                                <input type="time" id="shift-start" class="form-control" required>
                            </div>
                            <div class="form-group col-6">
                                <label>Jam Selesai</label>
                                <input type="time" id="shift-end" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Warna Dokter</label>
                            <input type="color" id="shift-color" class="form-control" value="#64B5F6" style="height:44px;">
                            <small class="text-muted">Dipakai di jadwal dokter dan modul jadwal di mainmenu.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var URLS = {
        index: "{{ route('hrd.dokter-schedule.index') }}",
        save: "{{ route('hrd.dokter-schedule.save_week') }}",
        copyWeek: "{{ route('hrd.dokter-schedule.copy_week') }}",
        print: "{{ route('hrd.dokter-schedule.print') }}",
        shiftStore: "{{ route('hrd.dokter-shifts.store') }}",
        shiftUpdate: "{{ route('hrd.dokter-shifts.update', ['id' => '__ID__']) }}"
    };
    var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var presets = [];         // "HH:MM-HH:MM" (key 2..9)
    var selected = new Set();
    var anchor = null;
    var dragging = false;
    var undoStack = [];
    var clipboard = null;

    // ---------- helpers ----------
    function $id(id) { return document.getElementById(id); }
    function showLoading(show) { $id('ajax-loading').style.display = show ? 'block' : 'none'; }
    function toast(message) { swal.fire({ title: message, icon: 'info', timer: 2000, showConfirmButton: false, toast: true, position: 'top-end' }); }
    function success(message) { swal.fire({ title: 'Berhasil!', text: message, icon: 'success', confirmButtonText: 'OK' }); }
    function fail(message) { swal.fire({ title: 'Gagal!', text: message, icon: 'error', confirmButtonText: 'OK' }); }
    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
    function parseYmd(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function addDays(s, n) { var d = parseYmd(s); d.setDate(d.getDate() + n); return ymd(d); }
    function mondayOf(d) { d = new Date(d); var day = d.getDay(); d.setDate(d.getDate() + ((day === 0 ? -6 : 1) - day)); return ymd(d); }
    function weekStart() { var el = $id('week-start'); return el ? el.value : mondayOf(new Date()); }
    function fmt(val) { return val ? val.replace('-', '–') : ''; }
    function contrast(hex) {
        var c = (hex || '').replace('#', '');
        if (c.length !== 6) return '#000';
        var r = parseInt(c.substr(0, 2), 16), g = parseInt(c.substr(2, 2), 16), b = parseInt(c.substr(4, 2), 16);
        return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#000' : '#fff';
    }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function isTyping(e) { var t = e.target; return t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable); }
    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body)
        }).then(function (res) { if (!res.ok) throw new Error(); return res.json(); });
    }

    // ---------- cell state ----------
    function getVal(td) { return td.getAttribute('data-val') || ''; }
    function renderCell(td) {
        var val = getVal(td);
        if (!val) { td.innerHTML = '<span class="sc-empty">–</span>'; return; }
        var color = td.parentNode.getAttribute('data-color') || '#64B5F6';
        td.innerHTML = '<span class="sc-chip" style="background:' + esc(color) + ';color:' + contrast(color) + '">' + esc(fmt(val)) + '</span>';
    }
    function setVal(td, val, batch) {
        var before = getVal(td);
        if (before === val) return;
        batch.push({ td: td, before: before });
        td.setAttribute('data-val', val);
        td.classList.toggle('dirty', val !== (td.getAttribute('data-orig') || ''));
        renderCell(td);
    }
    function commitBatch(batch) {
        if (batch.length) { undoStack.push(batch); if (undoStack.length > 100) undoStack.shift(); }
        refreshStatus();
    }
    function applyToSelection(fn) {
        var batch = [];
        selected.forEach(function (td) { var v = fn(td); if (v !== null) setVal(td, v, batch); });
        commitBatch(batch);
    }
    function applyTime(val) { applyToSelection(function () { return val; }); }
    function applyDefault() {
        var skipped = 0;
        applyToSelection(function (td) {
            var def = td.parentNode.getAttribute('data-default');
            if (!def) { skipped++; return null; }
            return def;
        });
        if (skipped) toast(skipped + ' sel dilewati: dokter belum punya jam default');
    }
    function clearCells() { applyToSelection(function () { return ''; }); }
    function undo() {
        var batch = undoStack.pop();
        if (!batch) return;
        batch.reverse().forEach(function (c) {
            if (!document.body.contains(c.td)) return;
            c.td.setAttribute('data-val', c.before);
            c.td.classList.toggle('dirty', c.before !== (c.td.getAttribute('data-orig') || ''));
            renderCell(c.td);
        });
        refreshStatus();
    }
    function dirtyCells() { return document.querySelectorAll('#sched-table td.sc.dirty'); }
    function refreshStatus() {
        var n = dirtyCells().length;
        $id('pending-count').textContent = n;
        $id('save-btn').disabled = n === 0;
        $id('undo-btn').disabled = undoStack.length === 0;
        var table = $id('sched-table');
        if (!table) return;
        var counts = [0, 0, 0, 0, 0, 0, 0];
        table.querySelectorAll('tbody td.sc').forEach(function (td) { if (getVal(td)) counts[+td.getAttribute('data-col')]++; });
        table.querySelectorAll('tfoot .sched-count').forEach(function (td) {
            td.innerHTML = '<b>' + counts[+td.getAttribute('data-col')] + '</b>/' + table.getAttribute('data-total');
        });
    }

    // ---------- selection ----------
    function clearSelection() { selected.forEach(function (td) { td.classList.remove('sel'); }); selected.clear(); }
    function setCursor(td) { if (anchor) anchor.classList.remove('cursor'); anchor = td; if (td) td.classList.add('cursor'); }
    function select(tds, additive) {
        if (!additive) clearSelection();
        tds.forEach(function (td) { selected.add(td); td.classList.add('sel'); });
    }
    function visibleRows() {
        return Array.prototype.filter.call(document.querySelectorAll('#sched-table tr.employee-row'), function (tr) { return tr.style.display !== 'none'; });
    }
    function cellAt(tr, col) { return tr.querySelector('td.sc[data-col="' + col + '"]'); }
    function rectCells(a, b) {
        var rows = visibleRows(), r1 = rows.indexOf(a.parentNode), r2 = rows.indexOf(b.parentNode);
        if (r1 < 0 || r2 < 0) return [b];
        var c1 = +a.getAttribute('data-col'), c2 = +b.getAttribute('data-col'), out = [];
        for (var r = Math.min(r1, r2); r <= Math.max(r1, r2); r++)
            for (var c = Math.min(c1, c2); c <= Math.max(c1, c2); c++) out.push(cellAt(rows[r], c));
        return out;
    }
    function selectionBounds() {
        var rows = visibleRows(), r1 = Infinity, r2 = -1, c1 = 7, c2 = -1;
        selected.forEach(function (td) {
            var r = rows.indexOf(td.parentNode), c = +td.getAttribute('data-col');
            if (r < 0) return;
            r1 = Math.min(r1, r); r2 = Math.max(r2, r); c1 = Math.min(c1, c); c2 = Math.max(c2, c);
        });
        return r2 < 0 ? null : { rows: rows, r1: r1, r2: r2, c1: c1, c2: c2 };
    }

    // ---------- palette & picker ----------
    function buildPalette() {
        var html = '<span class="small text-muted mr-1">Isi:</span>' +
            '<button type="button" class="pal-chip" data-action="default"><kbd>1</kbd>Jam default dokter</button>';
        presets.slice(0, 8).forEach(function (p, i) {
            html += '<button type="button" class="pal-chip" data-time="' + esc(p) + '"><kbd>' + (i + 2) + '</kbd>' + esc(fmt(p)) + '</button>';
        });
        html += '<button type="button" class="pal-chip" data-action="custom"><i class="fa fa-clock-o"></i> Jam lain…</button>' +
            '<button type="button" class="pal-chip text-danger" data-action="clear"><i class="fa fa-eraser"></i> Kosongkan</button>';
        $id('sched-palette').innerHTML = html;

        var list = '<div class="sp-item" data-action="default"><kbd>1</kbd>Jam default dokter</div>';
        presets.slice(0, 8).forEach(function (p, i) {
            list += '<div class="sp-item" data-time="' + esc(p) + '"><kbd>' + (i + 2) + '</kbd>' + esc(fmt(p)) + '</div>';
        });
        $id('sp-list').innerHTML = list;
    }
    function openPicker(focusCustom) {
        if (!selected.size) { closePicker(); return; }
        var picker = $id('time-picker');
        var single = selected.size === 1 ? Array.from(selected)[0] : null;
        var def = single ? single.parentNode.getAttribute('data-default') : '';
        $id('sp-head').textContent = single ? 'Pilih jam' + (def ? ' (default ' + fmt(def) + ')' : '') : 'Terapkan ke ' + selected.size + ' sel';
        var cur = single ? (getVal(single) || def) : '';
        $id('sp-start').value = cur ? cur.split('-')[0] : '';
        $id('sp-end').value = cur ? cur.split('-')[1] : '';
        var target = anchor && selected.has(anchor) ? anchor : Array.from(selected).pop();
        var r = target.getBoundingClientRect();
        picker.style.display = 'block';
        var pw = picker.offsetWidth, ph = picker.offsetHeight;
        var top = r.bottom + 4;
        if (top + ph > window.innerHeight - 8) top = Math.max(8, r.top - ph - 4);
        picker.style.left = Math.max(8, Math.min(r.left, window.innerWidth - pw - 8)) + 'px';
        picker.style.top = top + 'px';
        if (focusCustom) $id('sp-start').focus();
    }
    function closePicker() { $id('time-picker').style.display = 'none'; }
    function handleAction(el) {
        if (!selected.size) { toast('Pilih sel terlebih dahulu'); return; }
        var action = el.getAttribute('data-action'), time = el.getAttribute('data-time');
        if (time) { applyTime(time); closePicker(); }
        else if (action === 'default') { applyDefault(); closePicker(); }
        else if (action === 'clear') { clearCells(); closePicker(); }
        else if (action === 'custom') openPicker(true);
    }

    // ---------- filters ----------
    function buildDivisionFilter() {
        var sel = $id('division-filter'), cur = sel.value;
        var opts = '<option value="">Semua Klinik</option>';
        document.querySelectorAll('#sched-table tr.sched-division').forEach(function (tr) {
            opts += '<option value="' + esc(tr.getAttribute('data-division')) + '" data-klinik-id="' + esc(tr.getAttribute('data-klinik-id')) + '">' + esc(tr.getAttribute('data-division')) + '</option>';
        });
        sel.innerHTML = opts;
        sel.value = cur;
        if (sel.value !== cur) sel.value = '';
    }
    function applyFilters() {
        var q = $id('emp-search').value.trim().toLowerCase(), div = $id('division-filter').value;
        var collapsed = {}, visible = {};
        document.querySelectorAll('#sched-table tr.sched-division').forEach(function (tr) { collapsed[tr.getAttribute('data-division')] = tr.classList.contains('collapsed'); });
        document.querySelectorAll('#sched-table tr.employee-row').forEach(function (tr) {
            var d = tr.getAttribute('data-division');
            var match = (!div || d === div) && (!q || tr.getAttribute('data-name').indexOf(q) !== -1);
            if (match) visible[d] = true;
            tr.style.display = match && !collapsed[d] ? '' : 'none';
        });
        document.querySelectorAll('#sched-table tr.sched-division').forEach(function (tr) { tr.style.display = visible[tr.getAttribute('data-division')] ? '' : 'none'; });
        updatePrintLink();
    }
    function updatePrintLink() {
        var opt = $id('division-filter').selectedOptions[0];
        var klinikId = opt ? (opt.getAttribute('data-klinik-id') || '') : '';
        $id('print-btn').href = URLS.print + '?month=' + weekStart().slice(0, 7) + (klinikId ? '&clinic_id=' + klinikId : '');
    }

    // ---------- load / save ----------
    function initTable() {
        var p = $id('time-presets');
        presets = p ? JSON.parse(p.textContent) : [];
        selected.clear(); anchor = null; undoStack = []; closePicker();
        document.querySelectorAll('#sched-table td.sc').forEach(renderCell);
        buildPalette();
        buildDivisionFilter();
        applyFilters();
        var ws = weekStart(), opt = { day: '2-digit', month: 'short', year: 'numeric' };
        $id('week-range').textContent = parseYmd(ws).toLocaleDateString('id-ID', opt) + ' - ' + parseYmd(addDays(ws, 6)).toLocaleDateString('id-ID', opt);
        $id('week-jump').value = ws;
        refreshStatus();
        try { if (localStorage.getItem('dokterSched.mgmtOpen') === '1') $('#shift-mgmt-body').addClass('show'); } catch (e) {}
        $('#shift-mgmt-body').off('shown.bs.collapse hidden.bs.collapse')
            .on('shown.bs.collapse', function () { try { localStorage.setItem('dokterSched.mgmtOpen', '1'); } catch (e) {} })
            .on('hidden.bs.collapse', function () { try { localStorage.setItem('dokterSched.mgmtOpen', '0'); } catch (e) {} });
    }
    function confirmDiscard() {
        if (!dirtyCells().length) return Promise.resolve(true);
        return swal.fire({
            title: 'Ada perubahan belum disimpan', text: 'Simpan dulu sebelum lanjut?', icon: 'warning',
            showCancelButton: true, showCloseButton: true, confirmButtonText: 'Simpan & lanjut', cancelButtonText: 'Buang perubahan'
        }).then(function (r) {
            if (r.value) return save();
            return r.dismiss === swal.DismissReason.cancel;
        });
    }
    function loadWeek(startDate, skipConfirm) {
        return (skipConfirm ? Promise.resolve(true) : confirmDiscard()).then(function (ok) {
            if (!ok) return;
            showLoading(true);
            return fetch(URLS.index + '?start_date=' + encodeURIComponent(startDate), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { if (!res.ok) throw new Error(); return res.text(); })
                .then(function (html) { $id('jadwal-wrapper').innerHTML = html; initTable(); })
                .catch(function () { fail('Gagal memuat jadwal'); })
                .finally(function () { showLoading(false); });
        });
    }
    var saving = false;
    function save() {
        var dirty = dirtyCells();
        if (!dirty.length || saving) return Promise.resolve(!dirty.length);
        var payload = {};
        dirty.forEach(function (td) {
            var id = td.getAttribute('data-emp');
            (payload[id] = payload[id] || {})[td.getAttribute('data-date')] = getVal(td);
        });
        saving = true;
        showLoading(true);
        return postJson(URLS.save, { schedule: payload })
            .then(function (data) {
                if (!data || !data.success) throw new Error();
                dirty.forEach(function (td) { td.setAttribute('data-orig', getVal(td)); td.classList.remove('dirty'); });
                undoStack = [];
                var parts = [];
                if (data.saved) parts.push(data.saved + ' jadwal disimpan');
                if (data.removed) parts.push(data.removed + ' jadwal dihapus');
                success(parts.join(', ') || 'Jadwal disimpan');
                return true;
            })
            .catch(function () { fail('Gagal menyimpan jadwal'); return false; })
            .finally(function () { saving = false; showLoading(false); refreshStatus(); });
    }
    function copyWeek(target, label) {
        if (target === weekStart()) { toast('Anda sedang membuka ' + label); return; }
        confirmDiscard().then(function (ok) {
            if (!ok) return;
            return swal.fire({
                title: 'Copy jadwal ke ' + label + '?', text: 'Jadwal yang sudah ada di ' + label + ' tidak akan ditimpa.',
                icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Copy', cancelButtonText: 'Batal', reverseButtons: true
            }).then(function (r) {
                if (!r.value) return;
                showLoading(true);
                return postJson(URLS.copyWeek, { source_start_date: weekStart(), target_start_date: target })
                    .then(function (data) {
                        success(data.inserted + ' jadwal dicopy ke ' + label);
                        return loadWeek(target, true);
                    })
                    .catch(function () { fail('Gagal copy jadwal'); })
                    .finally(function () { showLoading(false); });
            });
        });
    }

    // ---------- clipboard ----------
    function copySelection() {
        var b = selectionBounds();
        if (!b) return;
        clipboard = [];
        for (var r = b.r1; r <= b.r2; r++) {
            var row = [];
            for (var c = b.c1; c <= b.c2; c++) {
                var td = cellAt(b.rows[r], c);
                row.push(td && selected.has(td) ? getVal(td) : null);
            }
            clipboard.push(row);
        }
        toast('Disalin ' + clipboard.length + '×' + clipboard[0].length + ' sel');
    }
    function pasteSelection() {
        var b = selectionBounds();
        if (!clipboard || !b) return;
        var batch = [];
        if (clipboard.length === 1 && clipboard[0].length === 1) {
            selected.forEach(function (td) { setVal(td, clipboard[0][0] || '', batch); });
        } else {
            for (var r = 0; r < clipboard.length; r++) {
                var tr = b.rows[b.r1 + r];
                if (!tr) break;
                for (var c = 0; c < clipboard[r].length; c++) {
                    if (b.c1 + c > 6 || clipboard[r][c] === null) continue;
                    setVal(cellAt(tr, b.c1 + c), clipboard[r][c], batch);
                }
            }
        }
        commitBatch(batch);
    }

    // ---------- events ----------
    document.addEventListener('DOMContentLoaded', function () {
        initTable();
        var wrapper = $id('jadwal-wrapper');

        wrapper.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            var td = e.target.closest('td.sc');
            if (!td) return;
            e.preventDefault();
            closePicker();
            if (e.ctrlKey || e.metaKey) {
                if (selected.has(td)) { selected.delete(td); td.classList.remove('sel'); } else select([td], true);
                setCursor(td);
                return;
            }
            if (e.shiftKey && anchor) { select(rectCells(anchor, td)); return; }
            setCursor(td);
            select([td]);
            dragging = true;
        });
        wrapper.addEventListener('mouseover', function (e) {
            if (!dragging) return;
            var td = e.target.closest('td.sc');
            if (td && anchor) select(rectCells(anchor, td));
        });
        document.addEventListener('mouseup', function (e) {
            if (dragging) { dragging = false; if (selected.size) openPicker(); return; }
            if (e.target.closest && e.target.closest('td.sc') && (e.ctrlKey || e.metaKey || e.shiftKey) && selected.size) openPicker();
        });

        wrapper.addEventListener('click', function (e) {
            var th = e.target.closest('th.sched-day-head');
            if (th) {
                var col = th.getAttribute('data-col');
                var cells = visibleRows().map(function (tr) { return cellAt(tr, col); }).filter(Boolean);
                select(cells, e.ctrlKey || e.metaKey); setCursor(cells[0] || null); openPicker();
                return;
            }
            var emp = e.target.closest('td.sched-emp');
            if (emp) {
                var tds = Array.from(emp.parentNode.querySelectorAll('td.sc'));
                select(tds, e.ctrlKey || e.metaKey); setCursor(tds[0]); openPicker();
                return;
            }
            var div = e.target.closest('tr.sched-division');
            if (div) { div.classList.toggle('collapsed'); applyFilters(); return; }

            if (e.target.closest('#btn-add-shift')) { openShiftForm({}); return; }
            var edit = e.target.closest('.shift-edit-btn');
            if (edit) {
                var def = edit.getAttribute('data-default') || '';
                openShiftForm({
                    id: edit.getAttribute('data-shift-id'), dokter: edit.getAttribute('data-dokter-id'),
                    start: def.split('-')[0] || '', end: def.split('-')[1] || '', color: edit.getAttribute('data-color')
                });
            }
        });

        $id('sched-palette').addEventListener('click', function (e) { var chip = e.target.closest('.pal-chip'); if (chip) handleAction(chip); });
        $id('time-picker').addEventListener('mousedown', function (e) { e.stopPropagation(); });
        $id('sp-list').addEventListener('click', function (e) { var item = e.target.closest('.sp-item'); if (item) handleAction(item); });
        function applyCustom() {
            var s = $id('sp-start').value, en = $id('sp-end').value;
            if (!s || !en) { toast('Isi jam mulai dan jam selesai'); return; }
            applyTime(s + '-' + en);
            closePicker();
        }
        $id('sp-apply').addEventListener('click', applyCustom);
        $id('sp-end').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); applyCustom(); } });
        $id('sp-clear').addEventListener('click', function () { clearCells(); closePicker(); });
        $id('sp-close').addEventListener('click', closePicker);
        document.addEventListener('mousedown', function (e) {
            if (!e.target.closest('#time-picker') && !e.target.closest('#jadwal-wrapper') && !e.target.closest('.sched-toolbar')) closePicker();
        });
        wrapper.addEventListener('scroll', closePicker, true);
        window.addEventListener('resize', closePicker);
        window.addEventListener('scroll', closePicker);

        document.addEventListener('keydown', function (e) {
            if (isTyping(e) || $('.modal.show').length || document.querySelector('.swal2-popup:not(.swal2-toast)')) {
                if (e.key === 'Escape' && isTyping(e)) { e.target.blur(); closePicker(); }
                return;
            }
            var ctrl = e.ctrlKey || e.metaKey, k = e.key.toLowerCase();
            if (ctrl && k === 's') { e.preventDefault(); save(); return; }
            if (ctrl && k === 'z') { e.preventDefault(); undo(); return; }
            if (ctrl && k === 'c' && selected.size) { e.preventDefault(); copySelection(); return; }
            if (ctrl && k === 'v' && selected.size) { e.preventDefault(); pasteSelection(); closePicker(); return; }
            if (e.altKey && e.key === 'ArrowLeft') { e.preventDefault(); loadWeek(addDays(weekStart(), -7)); return; }
            if (e.altKey && e.key === 'ArrowRight') { e.preventDefault(); loadWeek(addDays(weekStart(), 7)); return; }
            if (e.key === '/') { e.preventDefault(); $id('emp-search').focus(); return; }
            if (e.key === 'Escape') { closePicker(); clearSelection(); setCursor(null); return; }
            if (!selected.size) return;

            var m = /^(?:Digit|Numpad)([1-9])$/.exec(e.code);
            if (m && !ctrl && !e.altKey) {
                e.preventDefault();
                var n = +m[1];
                if (n === 1) applyDefault();
                else if (presets[n - 2]) applyTime(presets[n - 2]);
                closePicker();
                return;
            }
            if (e.key === 'Delete' || e.key === 'Backspace') { e.preventDefault(); clearCells(); closePicker(); return; }
            if (e.key === 'Enter') { e.preventDefault(); openPicker(); return; }
            var dir = { ArrowUp: [-1, 0], ArrowDown: [1, 0], ArrowLeft: [0, -1], ArrowRight: [0, 1] }[e.key];
            if (dir && anchor) {
                e.preventDefault();
                var rows = visibleRows(), r = rows.indexOf(anchor.parentNode) + dir[0], c = +anchor.getAttribute('data-col') + dir[1];
                if (r < 0 || r >= rows.length || c < 0 || c > 6) return;
                var next = cellAt(rows[r], c);
                closePicker(); setCursor(next); select([next]);
                next.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        });

        $id('save-btn').addEventListener('click', function () { save(); });
        $id('undo-btn').addEventListener('click', undo);
        $id('prev-week-btn').addEventListener('click', function () { loadWeek(addDays(weekStart(), -7)); });
        $id('next-week-btn').addEventListener('click', function () { loadWeek(addDays(weekStart(), 7)); });
        $id('this-week-btn').addEventListener('click', function () {
            var monday = mondayOf(new Date());
            if (monday === weekStart()) { toast('Sudah berada di minggu ini'); return; }
            loadWeek(monday);
        });
        $id('week-range').addEventListener('click', function () {
            var inp = $id('week-jump');
            inp.classList.remove('d-none');
            if (inp.showPicker) { inp.style.cssText = 'position:absolute;opacity:0;width:1px;height:1px;'; try { inp.showPicker(); return; } catch (err) {} }
            inp.style.cssText = 'width:150px;';
            inp.focus();
        });
        $id('week-jump').addEventListener('change', function () { if (this.value) loadWeek(mondayOf(parseYmd(this.value))); });
        $id('emp-search').addEventListener('input', function () { clearSelection(); closePicker(); applyFilters(); });
        $id('division-filter').addEventListener('change', function () { clearSelection(); closePicker(); applyFilters(); });
        document.querySelectorAll('.copy-week-option').forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                e.preventDefault();
                var thisMonday = mondayOf(new Date()), t = opt.getAttribute('data-target');
                if (t === 'this') copyWeek(thisMonday, 'Minggu Ini');
                else if (t === 'next') copyWeek(addDays(thisMonday, 7), 'Minggu Depan');
                else { var target = addDays(weekStart(), 7); copyWeek(target, 'minggu ' + parseYmd(target).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })); }
            });
        });
        window.addEventListener('beforeunload', function (e) { if (dirtyCells().length) { e.preventDefault(); e.returnValue = ''; } });

        // Jam default & warna dokter
        function openShiftForm(s) {
            $id('shift-id').value = s.id || '';
            $id('shift-dokter').value = s.dokter || '';
            $id('shift-start').value = s.start || '';
            $id('shift-end').value = s.end || '';
            $id('shift-color').value = s.color || '#64B5F6';
            $('#shiftModal').modal('show');
        }
        window.openShiftForm = openShiftForm;
        $id('shift-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var id = $id('shift-id').value;
            var body = { dokter_id: $id('shift-dokter').value, jam_mulai: $id('shift-start').value, jam_selesai: $id('shift-end').value, color_hex: $id('shift-color').value.toUpperCase() };
            if (!body.dokter_id || !body.jam_mulai || !body.jam_selesai) { toast('Semua field wajib diisi'); return; }
            showLoading(true);
            postJson(id ? URLS.shiftUpdate.replace('__ID__', id) : URLS.shiftStore, body)
                .then(function (data) {
                    if (!data || !data.success) throw new Error();
                    $('#shiftModal').modal('hide');
                    success('Jam default dokter disimpan');
                    return loadWeek(weekStart());
                })
                .catch(function () { fail('Gagal menyimpan jam default dokter'); })
                .finally(function () { showLoading(false); });
        });
    });
})();
</script>
@endpush

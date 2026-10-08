@extends('layouts.hrd.app')
@section('title', 'HRD | Jadwal Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<style>
    .sched-toolbar { position: sticky; top: 0; z-index: 30; background: #fff; padding: 8px 0; border-bottom: 1px solid #e8ebf3; }
    .sched-toolbar .btn { white-space: nowrap; }
    .sched-palette { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .sched-palette .pal-chip { border: 0; border-radius: 4px; padding: 3px 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .sched-palette .pal-chip kbd { font-size: 10px; padding: 0 4px; margin-right: 4px; background: rgba(0,0,0,.25); color: #fff; }
    .sched-palette .pal-chip:disabled { opacity: .45; cursor: not-allowed; }
    .sched-scroll { max-height: calc(100vh - 250px); overflow: auto; border: 1px solid #dee2e6; }
    .sched-table { border-collapse: separate; border-spacing: 0; user-select: none; }
    .sched-table th, .sched-table td { padding: 4px 6px; vertical-align: middle; }
    .sched-table thead th { position: sticky; top: 0; z-index: 3; background: #f8f9fa; text-align: center; cursor: pointer; vertical-align: top !important; font-weight: 700; }
    .sched-table thead th small { color: #6c757d; font-weight: 700; }
    .sched-table tfoot td { position: sticky; bottom: 0; z-index: 3; background: #f8f9fa; }
    .sched-table .sched-name-col { position: sticky; left: 0; z-index: 2; background: #fff; min-width: 180px; max-width: 220px; }
    .sched-table thead .sched-name-col, .sched-table tfoot .sched-name-col { z-index: 4; background: #f8f9fa; cursor: default; }
    .sched-table .sched-emp { cursor: pointer; font-size: 13px; }
    .sched-table .sched-emp:hover { background: #f1f5ff; }
    .sched-table th.is-today { background: #e3f2fd; color: #0d47a1; }
    .sched-table th.is-weekend small { color: #dc3545; }
    .sched-division td { background: #eef1f6 !important; font-weight: 700; color: #333; cursor: pointer; position: sticky; left: 0; }
    .sched-division.collapsed .sched-caret { transform: rotate(-90deg); }
    .sched-caret { transition: transform .15s; font-size: 11px; width: 12px; }
    td.sc { min-width: 110px; height: 34px; cursor: cell; position: relative; }
    td.sc.is-today { background: #f5faff; }
    td.sc.sel { box-shadow: inset 0 0 0 2px #1e88e5; background: #e3f2fd; }
    td.sc.cursor { box-shadow: inset 0 0 0 3px #0d47a1; }
    td.sc.dirty::after { content: ''; position: absolute; top: 0; right: 0; border-style: solid; border-width: 0 9px 9px 0; border-color: transparent #ff9800 transparent transparent; }
    td.sc-libur { background: #dc3545 !important; color: #fff; font-weight: 700; font-size: 12px; text-align: center; }
    td.sc-libur.sel { opacity: .8; }
    .sc-chip { display: block; border-radius: 3px; padding: 1px 6px; font-size: 12px; font-weight: 600; line-height: 1.5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sc-chip + .sc-chip { margin-top: 2px; }
    .sc-empty { color: #c0c4cc; display: block; text-align: center; }
    #shift-picker { position: fixed; z-index: 1050; width: 250px; background: #fff; border: 1px solid #d0d7e2; border-radius: 6px; box-shadow: 0 6px 24px rgba(0,0,0,.15); display: none; }
    #shift-picker .sp-head { padding: 6px 10px; font-size: 12px; color: #6c757d; border-bottom: 1px solid #eee; }
    #shift-picker .sp-list { max-height: 280px; overflow-y: auto; }
    #shift-picker .sp-item { display: flex; align-items: center; padding: 4px 8px; cursor: pointer; font-size: 13px; }
    #shift-picker .sp-item:hover { background: #f1f5ff; }
    #shift-picker .sp-dot { width: 14px; height: 14px; border-radius: 3px; margin-right: 8px; flex: none; }
    .sc-gl { background: #fd7e14 !important; color: #fff !important; }
    .sched-palette .pal-chip.sc-gl kbd { background: rgba(0,0,0,.25); }
    #shift-picker .sp-time { color: #6c757d; font-size: 11px; margin-left: auto; margin-right: 6px; }
    #shift-picker .sp-add { border: 1px solid #ccc; background: #fff; border-radius: 3px; font-size: 11px; padding: 0 5px; line-height: 18px; }
    #shift-picker .sp-add:hover { background: #1e88e5; color: #fff; border-color: #1e88e5; }
    #shift-picker .sp-foot { border-top: 1px solid #eee; padding: 6px 8px; display: flex; justify-content: space-between; }
    .sched-help kbd { font-size: 10px; }
    #save-schedule-btn .badge { font-size: 10px; }
</style>

<div class="container-fluid">
    <div class="sched-toolbar">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center flex-wrap">
                <h4 class="mb-0 font-weight-bold mr-3">Jadwal Karyawan</h4>
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
                <input type="search" id="emp-search" class="form-control form-control-sm mr-2" placeholder="Cari karyawan... ( / )" style="width:180px;">
                <select id="division-filter" class="form-control form-control-sm mr-2" style="width:auto;">
                    <option value="">Semua Divisi</option>
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
                <button type="button" id="rekap-libur-btn" class="btn btn-outline-warning btn-sm mr-2" title="Rekap karyawan masuk di hari Minggu / libur nasional">
                    <i class="fa fa-calendar-check-o"></i> Rekap Minggu/Libur
                </button>
                <a href="#" id="print-btn" target="_blank" class="btn btn-outline-secondary btn-sm mr-2"><i class="fa fa-print"></i> Print</a>
                <button id="undo-btn" type="button" class="btn btn-outline-secondary btn-sm mr-2" disabled title="Undo (Ctrl+Z)"><i class="fa fa-undo"></i></button>
                <button id="save-schedule-btn" type="button" class="btn btn-primary btn-sm" disabled title="Simpan (Ctrl+S)">
                    <i class="fa fa-save"></i> Simpan <span class="badge badge-light ml-1" id="pending-count">0</span>
                </button>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="sched-palette" id="sched-palette"></div>
            <a href="#" class="small text-muted" data-toggle="collapse" data-target="#sched-help">Pintasan keyboard</a>
        </div>
        <div id="sched-help" class="collapse small text-muted sched-help mt-2">
            <b>Pilih sel:</b> klik / tarik (drag) untuk blok, <kbd>Shift</kbd>+klik untuk rentang, <kbd>Ctrl</kbd>+klik tambah sel, klik nama karyawan = 1 minggu, klik header hari = 1 kolom.
            &nbsp;<b>Isi:</b> klik shift di palet/popup atau tekan <kbd>1</kbd>–<kbd>9</kbd>; <kbd>Shift</kbd>+angka = tambah sebagai shift kedua (double shift); <kbd>G</kbd> = ganti libur (jatah -1, pilih hari masuk yang diganti; G lagi = ubah); <kbd>Del</kbd> kosongkan.
            &nbsp;<b>Lainnya:</b> <kbd>←↑↓→</kbd> pindah sel, <kbd>Enter</kbd> buka pilihan, <kbd>Ctrl+C</kbd>/<kbd>Ctrl+V</kbd> copy-paste blok, <kbd>Ctrl+Z</kbd> undo, <kbd>Ctrl+S</kbd> simpan, <kbd>Esc</kbd> batal pilih.
        </div>
    </div>

    <div id="ajax-loading" style="display:none;text-align:center;" class="my-2">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat...
    </div>
    <div id="jadwal-wrapper" class="mt-2">
        @include('hrd.schedule._table', ['dates' => $dates, 'employeesByDivision' => $employeesByDivision, 'shifts' => $shifts, 'allShifts' => $allShifts, 'schedules' => $schedules, 'startOfWeek' => $startOfWeek])
    </div>

    <!-- Shift picker popup (shared) -->
    <div id="shift-picker">
        <div class="sp-head" id="sp-head"></div>
        <div class="sp-list" id="sp-list"></div>
        <div class="sp-foot">
            <button type="button" class="btn btn-sm btn-outline-danger" id="sp-clear"><i class="fa fa-eraser"></i> Kosongkan</button>
            <button type="button" class="btn btn-sm btn-light" id="sp-close">Tutup</button>
        </div>
    </div>

    <!-- Rekap masuk hari Minggu / libur nasional -->
    <div class="modal fade" id="rekapLiburModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rekap Masuk Hari Minggu / Libur Nasional</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row align-items-end mb-2">
                        <div class="col-auto">
                            <label class="small mb-0">Dari</label>
                            <input type="date" id="rekap-from" class="form-control form-control-sm">
                        </div>
                        <div class="col-auto">
                            <label class="small mb-0">Sampai</label>
                            <input type="date" id="rekap-to" class="form-control form-control-sm">
                        </div>
                        <div class="col">
                            <input type="search" id="rekap-search" class="form-control form-control-sm" placeholder="Cari karyawan...">
                        </div>
                        <div class="col-auto">
                            <button type="button" id="rekap-load" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Tampilkan</button>
                        </div>
                    </div>
                    <div class="small text-muted mb-2">Tiap hari masuk Minggu / libur nasional = +1 jatah ganti libur, dipasangkan dengan tanggal libur penggantinya.</div>
                    <div id="rekap-body" style="max-height:60vh;overflow:auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shift Management Modal -->
    <div class="modal fade" id="shiftModal" tabindex="-1" role="dialog" aria-labelledby="shiftModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="shift-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="shiftModalLabel">Tambah Shift</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="shift-id">
                        <div class="form-group">
                            <label for="shift-name">Nama Shift</label>
                            <input type="text" class="form-control" id="shift-name" placeholder="Pagi-Service">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="shift-start">Jam Mulai</label>
                                <input type="time" class="form-control" id="shift-start" step="60">
                            </div>
                            <div class="form-group col-6">
                                <label for="shift-end">Jam Selesai</label>
                                <input type="time" class="form-control" id="shift-end" step="60">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="shift-color">Warna Shift</label>
                                <input type="color" class="form-control" id="shift-color" value="#007bff">
                            </div>
                            <div class="form-group col-6">
                                <label for="shift-active">Status</label>
                                <select class="form-control" id="shift-active">
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
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
        index: "{{ route('hrd.schedule.index') }}",
        store: "{{ route('hrd.schedule.store') }}",
        copyWeek: "{{ route('hrd.schedule.copy_week') }}",
        print: "{{ route('hrd.schedule.print') }}",
        rekapLibur: "{{ route('hrd.schedule.rekap_hari_libur') }}",
        hariMasuk: "{{ route('hrd.schedule.hari_masuk_tersedia') }}",
        shiftStore: "{{ route('hrd.master.shift.store') }}",
        shiftUpdate: "{{ route('hrd.master.shift.update', ['shift' => '__ID__']) }}",
        shiftDestroy: "{{ route('hrd.master.shift.destroy', ['shift' => '__ID__']) }}"
    };
    var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var shiftMap = {};       // id -> shift
    var palette = [];        // active shifts, sorted by start time (index = number key - 1)
    var selected = new Set(); // selected td.sc elements
    var anchor = null;       // anchor cell for range selection / keyboard cursor
    var dragging = false;
    var dragMoved = false;
    var undoStack = [];
    var clipboard = null;    // 2D array of shift-id arrays
    var currentShiftMode = null;

    // ---------- helpers ----------
    function $id(id) { return document.getElementById(id); }
    function showLoading(show) { $id('ajax-loading').style.display = show ? 'block' : 'none'; }
    function showAlert(type, message) {
        var icon = { success: 'success', danger: 'error', error: 'error', warning: 'warning' }[type] || 'info';
        if (icon === 'success' || icon === 'error') {
            // Modal di tengah seperti halaman lain
            swal.fire({ title: icon === 'success' ? 'Berhasil!' : 'Gagal!', text: message, icon: icon, confirmButtonText: 'OK' });
            return;
        }
        swal.fire({ title: message, icon: icon, timer: 2000, showConfirmButton: false, toast: true, position: 'top-end' });
    }
    function ymd(d) {
        // local date -> YYYY-MM-DD (avoid toISOString timezone shift)
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function parseYmd(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function addDays(s, n) { var d = parseYmd(s); d.setDate(d.getDate() + n); return ymd(d); }
    function mondayOf(d) { d = new Date(d); var day = d.getDay(); d.setDate(d.getDate() + ((day === 0 ? -6 : 1) - day)); return ymd(d); }
    function weekStart() { var el = $id('week-start'); return el ? el.value : mondayOf(new Date()); }
    function contrast(hex) {
        if (!hex) return '#000';
        var c = hex.replace('#', '');
        if (c.length === 3) c = c[0] + c[0] + c[1] + c[1] + c[2] + c[2];
        if (c.length !== 6) return '#000';
        var r = parseInt(c.substr(0, 2), 16), g = parseInt(c.substr(2, 2), 16), b = parseInt(c.substr(4, 2), 16);
        return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#000' : '#fff';
    }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function isTyping(e) {
        var t = e.target;
        return t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable);
    }

    // ---------- cell state ----------
    function getIds(td) { var v = td.getAttribute('data-shifts'); return v ? v.split(',') : []; }
    function isEditable(td) { return td && td.classList.contains('sc') && !td.hasAttribute('data-libur'); }
    // Ganti libur cells also carry the Sunday / holiday worked that they replace (data-gl-masuk)
    function isDirty(td) {
        var shifts = td.getAttribute('data-shifts') || '';
        return shifts !== (td.getAttribute('data-orig') || '')
            || (shifts === 'GL' && (td.getAttribute('data-gl-masuk') || '') !== (td.getAttribute('data-gl-orig') || ''));
    }
    var HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    function shortDate(s) { var d = parseYmd(s); return HARI[d.getDay()] + ' ' + d.getDate() + ' ' + BULAN[d.getMonth()]; }

    function renderCell(td) {
        var ids = getIds(td);
        if (!ids.length) { td.innerHTML = '<span class="sc-empty">–</span>'; td.title = ''; return; }
        if (ids[0] === 'GL') {
            var masuk = td.getAttribute('data-gl-masuk');
            td.innerHTML = '<span class="sc-chip sc-gl">Ganti Libur' + (masuk ? '<small class="d-block">' + esc(shortDate(masuk)) + '</small>' : '') + '</span>';
            td.title = 'Ganti libur (jatah ganti libur -1)' + (masuk ? '\nMengganti masuk ' + shortDate(masuk) : '\nTanpa tanggal masuk (saldo lama)') + '\nTekan G lagi untuk mengganti hari masuk';
            return;
        }
        var html = '', titles = [];
        ids.forEach(function (id) {
            var s = shiftMap[id];
            if (!s) { html += '<span class="sc-chip" style="background:#ccc">#' + esc(id) + '</span>'; return; }
            var bg = s.color || '#adb5bd';
            html += '<span class="sc-chip" style="background:' + esc(bg) + ';color:' + contrast(bg) + '">' + esc(s.name) + '</span>';
            titles.push(s.name + ' (' + s.start + '–' + s.end + ')' + (s.active ? '' : ' [tidak aktif]'));
        });
        td.innerHTML = html;
        td.title = titles.join('\n');
    }

    // glMasuk: for ganti libur, the Sunday / holiday worked it replaces ('' = saldo lama tanpa tanggal)
    function setIds(td, ids, batch, glMasuk) {
        if (!isEditable(td)) return;
        ids = ids.filter(function (v, i, a) { return v && a.indexOf(v) === i; }).slice(0, 2);
        if (ids.indexOf('GL') !== -1) ids = ['GL']; // ganti libur tidak digabung dengan shift
        var before = td.getAttribute('data-shifts') || '';
        var beforeMasuk = td.getAttribute('data-gl-masuk') || '';
        var after = ids.join(',');
        var afterMasuk = after === 'GL' ? (glMasuk !== undefined ? glMasuk : beforeMasuk) : '';
        if (before === after && beforeMasuk === afterMasuk) return;
        if (batch) batch.push({ td: td, before: before, beforeMasuk: beforeMasuk });
        td.setAttribute('data-shifts', after);
        td.setAttribute('data-gl-masuk', afterMasuk);
        td.classList.toggle('dirty', isDirty(td));
        renderCell(td);
    }

    function commitBatch(batch) {
        if (batch.length) {
            undoStack.push(batch);
            if (undoStack.length > 100) undoStack.shift();
        }
        refreshStatus();
    }

    function applyToSelection(fn) {
        var batch = [];
        selected.forEach(function (td) { if (isEditable(td)) setIds(td, fn(getIds(td), td), batch); });
        commitBatch(batch);
        if (!batch.length && selected.size) showAlert('info', 'Tidak ada perubahan');
    }
    function setShift(id) { applyToSelection(function () { return [String(id)]; }); }
    function addShift(id) {
        id = String(id);
        applyToSelection(function (ids) {
            if (!ids.length || ids[0] === 'GL') return [id];
            if (ids.indexOf(id) !== -1) return ids;
            return [ids[0], id];
        });
    }
    function clearShift() { applyToSelection(function () { return []; }); }
    // Tandai hari ganti libur (jatah -1 saat disimpan). Tidak berlaku di hari Minggu / libur nasional.
    // Tiap sel meminta hari masuk Minggu / libur nasional yang diganti; G lagi di sel GL = ganti pilihan.
    var pickingGantiLibur = false;
    function setGantiLibur() {
        if (pickingGantiLibur) return;
        var targets = [], skipped = 0;
        selected.forEach(function (td) {
            if (!isEditable(td)) return;
            if (isHariGantiLibur(td)) { skipped++; return; }
            targets.push(td);
        });
        if (skipped) showAlert('info', skipped + ' sel hari Minggu/libur nasional dilewati');
        if (!targets.length) return;

        var batch = [];
        pickingGantiLibur = true;
        targets.reduce(function (chain, td) {
            return chain.then(function (stop) {
                if (stop) return true;
                return pickHariMasuk(td).then(function (masuk) {
                    if (masuk === null) return true; // batal: sel sisanya tidak diproses
                    if (masuk !== undefined) setIds(td, ['GL'], batch, masuk);
                    return false;
                });
            });
        }, Promise.resolve(false)).finally(function () {
            pickingGantiLibur = false;
            commitBatch(batch);
        });
    }

    function employeeName(td) {
        var cell = td.parentNode.querySelector('.sched-emp');
        return cell ? cell.childNodes[0].textContent.trim() : '';
    }

    // Resolves to the chosen date, '' (saldo lama tanpa tanggal), undefined (sel dilewati) or null (batal semua)
    function pickHariMasuk(td) {
        var emp = td.getAttribute('data-emp'), date = td.getAttribute('data-date');
        var url = URLS.hariMasuk + '?employee_id=' + encodeURIComponent(emp) + '&libur=' + encodeURIComponent(date)
            + (td.getAttribute('data-orig') === 'GL' ? '&exclude_libur=' + encodeURIComponent(date) : '');
        var today = "{{ now()->toDateString() }}";
        showLoading(true);
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
            .finally(function () { showLoading(false); })
            .then(function (data) {
                var options = {}, taken = {}, rowCells = document.querySelectorAll('#sched-table td.sc[data-emp="' + emp + '"]');
                rowCells.forEach(function (c) {
                    // Dipakai sel ganti libur lain di grid, atau Minggu yang dikosongkan tapi belum disimpan
                    if (c !== td && c.getAttribute('data-shifts') === 'GL' && c.getAttribute('data-gl-masuk')) taken[c.getAttribute('data-gl-masuk')] = true;
                    if (c.classList.contains('dirty') && !c.getAttribute('data-shifts') && isHariGantiLibur(c)) taken[c.getAttribute('data-date')] = true;
                });
                (data.dates || []).forEach(function (d) {
                    if (taken[d.date]) return;
                    options[d.date] = d.label + ' (' + d.keterangan + (d.shift ? ', ' + d.shift : '') + ')';
                });
                // Minggu / libur nasional yang baru diisi di grid ini (disimpan bersamaan); masuk dulu baru libur
                rowCells.forEach(function (c) {
                    var shifts = c.getAttribute('data-shifts'), d = c.getAttribute('data-date');
                    if (c.classList.contains('dirty') && shifts && shifts !== 'GL' && isHariGantiLibur(c) && !options[d] && !taken[d]
                        && d < date && d <= today) {
                        options[d] = shortDate(d) + ' (belum disimpan)';
                    }
                });
                var keys = Object.keys(options).sort();
                var sorted = {};
                keys.forEach(function (k) { sorted[k] = options[k]; });
                var name = employeeName(td);
                if (!keys.length) {
                    if (data.saldo < 1 || !(data.tanpa_tanggal > 0)) {
                        return swal.fire({
                            title: data.saldo < 1 ? 'Jatah ganti libur habis' : 'Belum ada hari masuk',
                            text: name + ' belum punya hari masuk Minggu / libur nasional sebelum ' + shortDate(date) + ' yang belum dipakai (masuk dulu baru libur).',
                            icon: 'warning'
                        }).then(function () { return undefined; });
                    }
                    sorted[''] = 'Tanpa tanggal (saldo lama: ' + data.tanpa_tanggal + ' hari)';
                }
                var current = td.getAttribute('data-gl-masuk') || '';
                return swal.fire({
                    title: 'Ganti libur ' + name,
                    html: 'Libur pada <b>' + esc(shortDate(date)) + '</b>.<br>Pilih hari masuk Minggu / libur nasional yang diganti:',
                    input: 'select',
                    inputOptions: sorted,
                    inputValue: sorted.hasOwnProperty(current) ? current : keys[0] || '',
                    showCancelButton: true,
                    confirmButtonText: 'Pilih',
                    cancelButtonText: 'Batal'
                }).then(function (r) { return r.dismiss ? null : (r.value || ''); });
            })
            .catch(function () { showAlert('danger', 'Gagal memuat hari masuk ' + employeeName(td)); return null; });
    }
    function isHariGantiLibur(td) {
        var th = document.querySelector('#sched-table th.sched-day-head[data-col="' + td.getAttribute('data-col') + '"]');
        return th && th.hasAttribute('data-hari-ganti-libur');
    }

    function undo() {
        var batch = undoStack.pop();
        if (!batch) return;
        batch.reverse().forEach(function (c) {
            if (!document.body.contains(c.td)) return;
            c.td.setAttribute('data-shifts', c.before);
            c.td.setAttribute('data-gl-masuk', c.beforeMasuk || '');
            c.td.classList.toggle('dirty', isDirty(c.td));
            renderCell(c.td);
        });
        refreshStatus();
    }

    function refreshStatus() {
        var dirty = document.querySelectorAll('#sched-table td.sc.dirty').length;
        $id('pending-count').textContent = dirty;
        $id('save-schedule-btn').disabled = dirty === 0;
        $id('undo-btn').disabled = undoStack.length === 0;
        updateCounts();
    }

    function updateCounts() {
        var table = $id('sched-table');
        if (!table) return;
        var total = table.getAttribute('data-total');
        var counts = [0, 0, 0, 0, 0, 0, 0], libur = [0, 0, 0, 0, 0, 0, 0];
        table.querySelectorAll('tbody td.sc').forEach(function (td) {
            var c = +td.getAttribute('data-col');
            if (td.hasAttribute('data-libur') || td.getAttribute('data-shifts') === 'GL') libur[c]++;
            else if (td.getAttribute('data-shifts')) counts[c]++;
        });
        table.querySelectorAll('tfoot .sched-count').forEach(function (td) {
            var c = +td.getAttribute('data-col');
            td.innerHTML = '<b>' + counts[c] + '</b>/' + total + (libur[c] ? ' <span class="text-danger">(' + libur[c] + ' libur)</span>' : '');
        });
    }

    // ---------- selection ----------
    function clearSelection() {
        selected.forEach(function (td) { td.classList.remove('sel'); });
        selected.clear();
    }
    function setCursor(td) {
        if (anchor) anchor.classList.remove('cursor');
        anchor = td;
        if (td) td.classList.add('cursor');
    }
    function select(tds, additive) {
        if (!additive) clearSelection();
        tds.forEach(function (td) { selected.add(td); td.classList.add('sel'); });
    }
    function visibleRows() {
        return Array.prototype.filter.call(document.querySelectorAll('#sched-table tr.employee-row'), function (tr) {
            return tr.style.display !== 'none';
        });
    }
    function cellAt(tr, col) { return tr.querySelector('td.sc[data-col="' + col + '"]'); }
    function rectCells(a, b) {
        var rows = visibleRows();
        var r1 = rows.indexOf(a.parentNode), r2 = rows.indexOf(b.parentNode);
        if (r1 < 0 || r2 < 0) return [b];
        var c1 = +a.getAttribute('data-col'), c2 = +b.getAttribute('data-col');
        var out = [];
        for (var r = Math.min(r1, r2); r <= Math.max(r1, r2); r++)
            for (var c = Math.min(c1, c2); c <= Math.max(c1, c2); c++) out.push(cellAt(rows[r], c));
        return out;
    }
    function selectionBounds() {
        // Returns {rows, r1, r2, c1, c2} for the selection's bounding box over visible rows
        var rows = visibleRows(), r1 = Infinity, r2 = -1, c1 = 7, c2 = -1;
        selected.forEach(function (td) {
            var r = rows.indexOf(td.parentNode), c = +td.getAttribute('data-col');
            if (r < 0) return;
            r1 = Math.min(r1, r); r2 = Math.max(r2, r); c1 = Math.min(c1, c); c2 = Math.max(c2, c);
        });
        return r2 < 0 ? null : { rows: rows, r1: r1, r2: r2, c1: c1, c2: c2 };
    }

    // ---------- picker ----------
    function buildPalette() {
        var el = $id('sched-palette');
        var html = '<span class="small text-muted mr-1">Shift:</span>';
        palette.forEach(function (s, i) {
            var bg = s.color || '#adb5bd';
            html += '<button type="button" class="pal-chip" data-shift-id="' + s.id + '" style="background:' + esc(bg) + ';color:' + contrast(bg) + '" title="' + esc(s.start + '–' + s.end) + ' · Shift+klik = shift kedua">' +
                (i < 9 ? '<kbd>' + (i + 1) + '</kbd>' : '') + esc(s.name) + '</button>';
        });
        html += '<button type="button" class="pal-chip pal-gl sc-gl" title="Jadikan hari ganti libur, jatah -1 (G)"><kbd>G</kbd>Ganti Libur</button>';
        html += '<button type="button" class="pal-chip pal-clear" style="background:#f1f3f5;color:#c62828" title="Kosongkan (Del)"><i class="fa fa-eraser"></i> Kosongkan</button>';
        el.innerHTML = html;

        var list = '<div class="sp-item sp-gl"><span class="sp-dot sc-gl"></span><span><kbd class="mr-1" style="font-size:10px">G</kbd>Ganti Libur</span><span class="sp-time">jatah -1</span></div>';
        palette.forEach(function (s, i) {
            list += '<div class="sp-item" data-shift-id="' + s.id + '">' +
                '<span class="sp-dot" style="background:' + esc(s.color || '#adb5bd') + '"></span>' +
                '<span>' + (i < 9 ? '<kbd class="mr-1" style="font-size:10px">' + (i + 1) + '</kbd>' : '') + esc(s.name) + '</span>' +
                '<span class="sp-time">' + esc(s.start) + '–' + esc(s.end) + '</span>' +
                '<button type="button" class="sp-add" data-shift-id="' + s.id + '" title="Tambah sebagai shift kedua (double shift)">+2</button>' +
                '</div>';
        });
        $id('sp-list').innerHTML = list || '<div class="p-2 small text-muted">Belum ada shift aktif.</div>';
    }

    function openPicker() {
        var picker = $id('shift-picker');
        var editable = Array.from(selected).filter(isEditable);
        if (!editable.length) { closePicker(); return; }
        $id('sp-head').textContent = editable.length === 1 ? 'Pilih shift' : 'Terapkan ke ' + editable.length + ' sel';
        var target = anchor && selected.has(anchor) ? anchor : editable[editable.length - 1];
        var r = target.getBoundingClientRect();
        picker.style.display = 'block';
        var pw = picker.offsetWidth, ph = picker.offsetHeight;
        var left = Math.min(r.left, window.innerWidth - pw - 8);
        var top = r.bottom + 4;
        if (top + ph > window.innerHeight - 8) top = Math.max(8, r.top - ph - 4);
        picker.style.left = Math.max(8, left) + 'px';
        picker.style.top = top + 'px';
    }
    function closePicker() { $id('shift-picker').style.display = 'none'; }

    // ---------- filters ----------
    function buildDivisionFilter() {
        var sel = $id('division-filter'), cur = sel.value;
        var opts = '<option value="">Semua Divisi</option>';
        document.querySelectorAll('#sched-table tr.sched-division').forEach(function (tr) {
            var d = tr.getAttribute('data-division');
            opts += '<option value="' + esc(d) + '">' + esc(d) + '</option>';
        });
        sel.innerHTML = opts;
        sel.value = cur;
        if (sel.value !== cur) sel.value = '';
        // Tanpa pengelompokan divisi, filter divisi tidak berguna
        sel.style.display = document.querySelector('#sched-table tr.sched-division') ? '' : 'none';
    }
    function applyFilters() {
        var q = $id('emp-search').value.trim().toLowerCase();
        var div = $id('division-filter').value;
        var collapsed = {};
        document.querySelectorAll('#sched-table tr.sched-division').forEach(function (tr) {
            collapsed[tr.getAttribute('data-division')] = tr.classList.contains('collapsed');
        });
        var visiblePerDiv = {};
        document.querySelectorAll('#sched-table tr.employee-row').forEach(function (tr) {
            var d = tr.getAttribute('data-division');
            var match = (!div || d === div) && (!q || tr.getAttribute('data-name').indexOf(q) !== -1);
            if (match) visiblePerDiv[d] = (visiblePerDiv[d] || 0) + 1;
            tr.style.display = match && !collapsed[d] ? '' : 'none';
        });
        document.querySelectorAll('#sched-table tr.sched-division').forEach(function (tr) {
            tr.style.display = visiblePerDiv[tr.getAttribute('data-division')] ? '' : 'none';
        });
    }

    // ---------- table init (after every load) ----------
    function initTable() {
        var dataEl = $id('shift-data');
        var shifts = dataEl ? JSON.parse(dataEl.textContent) : [];
        shiftMap = {};
        shifts.forEach(function (s) { shiftMap[String(s.id)] = s; });
        palette = shifts.filter(function (s) { return s.active; })
            .sort(function (a, b) { return (a.start || '').localeCompare(b.start || '') || a.name.localeCompare(b.name); });

        selected.clear();
        anchor = null;
        undoStack = [];
        closePicker();

        document.querySelectorAll('#sched-table td.sc:not([data-libur])').forEach(renderCell);
        buildPalette();
        buildDivisionFilter();
        applyFilters();
        updateWeekNav();
        initShiftDataTable();
        refreshStatus();

        try {
            if (localStorage.getItem('sched.shiftMgmtOpen') === '1') $('#shift-mgmt-body').addClass('show');
        } catch (e) {}
        $('#shift-mgmt-body').off('shown.bs.collapse hidden.bs.collapse')
            .on('shown.bs.collapse', function () { try { localStorage.setItem('sched.shiftMgmtOpen', '1'); } catch (e) {} })
            .on('hidden.bs.collapse', function () { try { localStorage.setItem('sched.shiftMgmtOpen', '0'); } catch (e) {} });
    }

    function updateWeekNav() {
        var ws = weekStart(), we = addDays(ws, 6);
        var opt = { day: '2-digit', month: 'short', year: 'numeric' };
        $id('week-range').textContent = parseYmd(ws).toLocaleDateString('id-ID', opt) + ' - ' + parseYmd(we).toLocaleDateString('id-ID', opt);
        $id('print-btn').href = URLS.print + '?start_date=' + ws;
        $id('week-jump').value = ws;
    }

    function hasDirty() { return document.querySelectorAll('#sched-table td.sc.dirty').length > 0; }

    function confirmDiscard() {
        if (!hasDirty()) return Promise.resolve(true);
        return swal.fire({
            title: 'Ada perubahan belum disimpan',
            text: 'Simpan dulu sebelum lanjut?',
            icon: 'warning',
            showCancelButton: true,
            showCloseButton: true,
            confirmButtonText: 'Simpan & lanjut',
            cancelButtonText: 'Buang perubahan'
        }).then(function (r) {
            if (r.value) return save();
            return r.dismiss === swal.DismissReason.cancel; // close/Esc/backdrop = batal
        });
    }

    function loadWeek(startDate, skipConfirm) {
        return (skipConfirm ? Promise.resolve(true) : confirmDiscard()).then(function (ok) {
            if (!ok) return;
            showLoading(true);
            return fetch(URLS.index + '?start_date=' + encodeURIComponent(startDate), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { if (!res.ok) throw new Error(); return res.text(); })
                .then(function (html) {
                    $id('jadwal-wrapper').innerHTML = html;
                    initTable();
                })
                .catch(function () { showAlert('danger', 'Gagal memuat jadwal'); })
                .finally(function () { showLoading(false); });
        });
    }

    // ---------- save ----------
    var saving = false;
    function save() {
        var dirty = document.querySelectorAll('#sched-table td.sc.dirty');
        if (!dirty.length || saving) return Promise.resolve(!dirty.length);
        var payload = {};
        dirty.forEach(function (td) {
            var emp = td.getAttribute('data-emp');
            var ids = getIds(td);
            if (ids[0] === 'GL') ids = ['GL', td.getAttribute('data-gl-masuk') || ''];
            (payload[emp] = payload[emp] || {})[td.getAttribute('data-date')] = ids;
        });
        saving = true;
        var btn = $id('save-schedule-btn');
        btn.disabled = true;
        showLoading(true);
        return fetch(URLS.store, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ schedule: payload })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.success) throw new Error((data && data.message) || '');
                dirty.forEach(function (td) {
                    td.setAttribute('data-orig', td.getAttribute('data-shifts') || '');
                    td.setAttribute('data-gl-orig', td.getAttribute('data-gl-masuk') || '');
                    td.classList.remove('dirty');
                });
                undoStack = [];
                showSaveResult(dirty.length, data.ganti_libur || []);
                return true;
            })
            .catch(function (err) { showAlert('danger', (err && err.message) || 'Gagal menyimpan jadwal'); return false; })
            .finally(function () { saving = false; showLoading(false); refreshStatus(); });
    }

    function showSaveResult(cellCount, gantiLibur) {
        var html = '<div>' + cellCount + ' sel jadwal berhasil disimpan.</div>';
        if (gantiLibur.length) {
            var rows = gantiLibur.map(function (g) {
                var change = '';
                if (g.added.length) change += '<div class="text-success font-weight-bold">+' + g.added.length + ' <small class="text-muted font-weight-normal">(kerja ' + esc(g.added.join(', ')) + ')</small></div>';
                if (g.removed.length) change += '<div class="text-danger font-weight-bold">-' + g.removed.length + ' <small class="text-muted font-weight-normal">(jadwal ' + esc(g.removed.join(', ')) + ' dihapus)</small></div>';
                if (g.used.length) change += '<div class="text-danger font-weight-bold">-' + g.used.length + ' <small class="text-muted font-weight-normal">(libur ' + esc(g.used.join(', ')) + ')</small></div>';
                if (g.refunded.length) change += '<div class="text-success font-weight-bold">+' + g.refunded.length + ' <small class="text-muted font-weight-normal">(ganti libur ' + esc(g.refunded.join(', ')) + ' dibatalkan)</small></div>';
                return '<tr><td class="text-left">' + esc(g.nama) + '</td><td class="text-left">' + change + '</td><td class="text-center font-weight-bold">' + g.saldo + '</td></tr>';
            }).join('');
            html += '<div class="mt-3 mb-1 font-weight-bold text-left">Perubahan Jatah Ganti Libur</div>' +
                '<table class="table table-sm table-bordered mb-0" style="font-size:13px">' +
                '<thead class="thead-light"><tr><th class="text-left">Karyawan</th><th class="text-left">Perubahan</th><th class="text-center">Saldo</th></tr></thead>' +
                '<tbody>' + rows + '</tbody></table>';
        }
        swal.fire({ title: 'Berhasil!', html: html, icon: 'success', confirmButtonText: 'OK', width: gantiLibur.length ? 600 : undefined });
    }

    // ---------- copy week ----------
    function performCopyWeek(sourceStart, targetStart, label) {
        if (sourceStart === targetStart) { showAlert('info', 'Anda sedang membuka ' + label); return; }
        confirmDiscard().then(function (ok) {
            if (!ok) return;
            return swal.fire({
                title: 'Copy jadwal ke ' + label + '?',
                text: 'Jadwal yang sudah ada di ' + label + ' tidak akan ditimpa.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Copy',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (result) {
                if (!result.value) return;
                showLoading(true);
                return fetch(URLS.copyWeek, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ target_start_date: targetStart, source_start_date: sourceStart, overwrite: false })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            showAlert('success', 'Berhasil copy' + (data.inserted ? ' (+' + data.inserted + ' shift)' : '') +
                                (data.ganti_libur_added ? '. Jatah ganti libur +1 untuk ' + data.ganti_libur_added + ' hari Minggu/libur nasional.' : ''));
                            return loadWeek(targetStart, true);
                        }
                        showAlert('danger', (data && data.message) || 'Gagal copy jadwal');
                    })
                    .catch(function () { showAlert('danger', 'Gagal copy jadwal'); })
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
                // Ganti libur is not copied: each one needs its own hari masuk (set it with G)
                row.push(td && selected.has(td) && isEditable(td) && getIds(td)[0] !== 'GL' ? getIds(td) : null);
            }
            clipboard.push(row);
        }
        showAlert('info', 'Disalin ' + clipboard.length + '×' + clipboard[0].length + ' sel');
    }
    function pasteSelection() {
        if (!clipboard) return;
        var b = selectionBounds();
        if (!b) return;
        var batch = [];
        if (clipboard.length === 1 && clipboard[0].length === 1) {
            // single cell -> fill whole selection (a copied ganti libur cell is null: nothing to paste)
            if (clipboard[0][0] === null) return;
            var ids = clipboard[0][0];
            selected.forEach(function (td) { setIds(td, ids.slice(), batch); });
        } else {
            // block -> paste starting at top-left of selection
            for (var r = 0; r < clipboard.length; r++) {
                var tr = b.rows[b.r1 + r];
                if (!tr) break;
                for (var c = 0; c < clipboard[r].length; c++) {
                    if (b.c1 + c > 6 || clipboard[r][c] === null) continue;
                    var td = cellAt(tr, b.c1 + c);
                    if (td) setIds(td, clipboard[r][c].slice(), batch);
                }
            }
        }
        commitBatch(batch);
    }

    // ---------- rekap masuk hari Minggu / libur nasional ----------
    var rekapData = [];
    function loadRekapLibur() {
        var from = $id('rekap-from').value, to = $id('rekap-to').value;
        if (!from || !to) { showAlert('info', 'Isi rentang tanggal'); return; }
        $id('rekap-body').innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat...</div>';
        fetch(URLS.rekapLibur + '?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
            .then(function (data) { rekapData = data.employees || []; renderRekapLibur(); })
            .catch(function () { $id('rekap-body').innerHTML = '<div class="text-danger p-2">Gagal memuat rekap</div>'; });
    }
    function renderRekapLibur() {
        var q = $id('rekap-search').value.trim().toLowerCase();
        var list = rekapData.filter(function (e) { return !q || e.nama.toLowerCase().indexOf(q) !== -1; });
        if (!list.length) { $id('rekap-body').innerHTML = '<div class="text-muted text-center p-3">Tidak ada karyawan masuk di hari Minggu / libur nasional pada rentang ini.</div>'; return; }
        var rows = list.map(function (e) {
            var n = e.pairs.length;
            return e.pairs.map(function (p, i) {
                var masuk = p.masuk
                    ? '<b>' + esc(p.masuk.label) + '</b> <span class="badge ' + (p.masuk.is_holiday ? 'badge-danger' : 'badge-warning') + '">' + esc(p.masuk.keterangan) + '</span>' +
                      (p.masuk.shifts.length ? '<div class="small text-muted">' + esc(p.masuk.shifts.join(', ')) + '</div>' : '')
                    : '<span class="text-muted font-italic">Tanpa tanggal masuk (saldo lama)</span>';
                var libur = p.libur
                    ? '<b>' + esc(p.libur.label) + '</b><div class="small text-muted">' + esc(p.libur.sumber) + '</div>'
                    : '<span class="badge badge-success">Belum dipakai</span>';
                var status = p.libur
                    ? '<span class="badge ' + (p.libur.status === 'Disetujui' ? 'badge-primary' : 'badge-secondary') + '">' + esc(p.libur.status) + '</span>'
                    : '';
                var head = i === 0
                    ? '<td rowspan="' + n + '">' + esc(e.nama) + '<div class="small text-muted">Masuk: ' + e.total_masuk + ' · belum dipakai: ' + e.belum_dipakai + ' · saldo GL: ' + e.saldo + '</div></td>'
                    : '';
                return '<tr>' + head + '<td>' + masuk + '</td><td class="text-center text-muted">&rarr;</td><td>' + libur + '</td><td>' + status + '</td></tr>';
            }).join('');
        }).join('');
        $id('rekap-body').innerHTML = '<table class="table table-sm table-bordered mb-0" style="font-size:13px">' +
            '<thead class="thead-light"><tr><th>Karyawan</th><th>Masuk (Minggu / Libur Nasional)</th><th></th><th>Diganti Libur Tanggal</th><th>Status</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table>';
    }

    // ---------- shift management ----------
    function initShiftDataTable() {
        if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable || !$('#shift-table').length) return;
        if ($.fn.DataTable.isDataTable('#shift-table')) $('#shift-table').DataTable().destroy();
        $('#shift-table').DataTable({
            paging: false, searching: true, info: false, ordering: true, order: [[0, 'asc']],
            initComplete: function () {
                var api = this.api();
                $(api.table().container()).find('.dataTables_filter').hide();
                var filter = function (val) {
                    if (val === 'active') api.column(3).search('^\\s*Aktif\\s*$', true, false).draw();
                    else if (val === 'inactive') api.column(3).search('^\\s*Tidak\\s+Aktif\\s*$', true, false).draw();
                    else api.column(3).search('', false, false).draw();
                };
                $('#shift-status-filter').val('active').off('change').on('change', function () { filter($(this).val()); });
                filter('active');
            }
        });
    }

    function openShiftForm(mode, shift) {
        currentShiftMode = mode;
        shift = shift || {};
        var modal = $('#shiftModal');
        modal.find('#shiftModalLabel').text(mode === 'edit' ? 'Edit Shift' : 'Tambah Shift');
        modal.find('#shift-id').val(shift.id || '');
        modal.find('#shift-name').val(shift.name || '');
        modal.find('#shift-start').val(shift.start || '');
        modal.find('#shift-end').val(shift.end || '');
        modal.find('#shift-active').val(typeof shift.active !== 'undefined' ? String(shift.active) : '1');
        modal.find('#shift-color').val(shift.color || '#007bff');
        modal.modal('show');
    }

    function reloadAfterShiftChange() {
        // Shift list changed: reload table but keep unsaved edits warning
        return loadWeek(weekStart());
    }

    // ---------- events ----------
    document.addEventListener('DOMContentLoaded', function () {
        initTable();

        var wrapper = $id('jadwal-wrapper');

        // Cell selection (mouse)
        wrapper.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            var td = e.target.closest('td.sc');
            if (!td) return;
            e.preventDefault();
            closePicker();
            if (e.ctrlKey || e.metaKey) {
                if (selected.has(td)) { selected.delete(td); td.classList.remove('sel'); }
                else select([td], true);
                setCursor(td);
                return;
            }
            if (e.shiftKey && anchor) {
                select(rectCells(anchor, td));
                return;
            }
            setCursor(td);
            select([td]);
            dragging = true;
            dragMoved = false;
        });
        wrapper.addEventListener('mouseover', function (e) {
            if (!dragging) return;
            var td = e.target.closest('td.sc');
            if (!td || !anchor) return;
            dragMoved = true;
            select(rectCells(anchor, td));
        });
        document.addEventListener('mouseup', function (e) {
            if (dragging) {
                dragging = false;
                if (selected.size) openPicker();
                return;
            }
            if (e.target.closest && e.target.closest('td.sc') && (e.ctrlKey || e.metaKey || e.shiftKey) && selected.size) openPicker();
        });

        // Header / row / division clicks
        wrapper.addEventListener('click', function (e) {
            var th = e.target.closest('th.sched-day-head');
            if (th) {
                var col = th.getAttribute('data-col');
                var cells = visibleRows().map(function (tr) { return cellAt(tr, col); }).filter(Boolean);
                select(cells, e.ctrlKey || e.metaKey);
                setCursor(cells[0] || null);
                openPicker();
                return;
            }
            var emp = e.target.closest('td.sched-emp');
            if (emp) {
                var tds = Array.from(emp.parentNode.querySelectorAll('td.sc'));
                select(tds, e.ctrlKey || e.metaKey);
                setCursor(tds[0]);
                openPicker();
                return;
            }
            var div = e.target.closest('tr.sched-division');
            if (div) {
                div.classList.toggle('collapsed');
                applyFilters();
                return;
            }
            // Shift management buttons
            var add = e.target.closest('#btn-add-shift');
            if (add) { e.preventDefault(); openShiftForm('add'); return; }
            var edit = e.target.closest('.shift-edit-btn');
            if (edit) {
                e.preventDefault();
                openShiftForm('edit', {
                    id: edit.getAttribute('data-shift-id'),
                    name: edit.getAttribute('data-shift-name'),
                    start: edit.getAttribute('data-shift-start'),
                    end: edit.getAttribute('data-shift-end'),
                    active: edit.getAttribute('data-shift-active') || '1',
                    color: edit.getAttribute('data-shift-color') || '#007bff'
                });
                return;
            }
            var del = e.target.closest('.shift-delete-btn');
            if (del) {
                e.preventDefault();
                var shiftId = del.getAttribute('data-shift-id');
                swal.fire({
                    title: 'Hapus shift ini?',
                    text: 'Semua jadwal yang menggunakan shift ini juga akan terhapus. Lanjutkan?',
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal', reverseButtons: true
                }).then(function (result) {
                    if (!result.value) return;
                    showLoading(true);
                    fetch(URLS.shiftDestroy.replace('__ID__', shiftId), {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (!data || !data.success) throw new Error((data && data.message) || '');
                            showAlert('success', 'Shift berhasil dihapus' + (data.ganti_libur_removed ? '. Jatah ganti libur -1 untuk ' + data.ganti_libur_removed + ' hari Minggu/libur nasional yang jadi kosong.' : ''));
                            return reloadAfterShiftChange();
                        })
                        .catch(function (err) { showAlert('danger', (err && err.message) || 'Gagal menghapus shift'); })
                        .finally(function () { showLoading(false); });
                });
            }
        });

        // Palette bar
        $id('sched-palette').addEventListener('click', function (e) {
            var chip = e.target.closest('.pal-chip');
            if (!chip) return;
            if (!selected.size) { showAlert('info', 'Pilih sel terlebih dahulu'); return; }
            if (chip.classList.contains('pal-clear')) clearShift();
            else if (chip.classList.contains('pal-gl')) setGantiLibur();
            else if (e.shiftKey) addShift(chip.getAttribute('data-shift-id'));
            else setShift(chip.getAttribute('data-shift-id'));
        });

        // Picker
        $id('shift-picker').addEventListener('mousedown', function (e) { e.stopPropagation(); });
        $id('sp-list').addEventListener('click', function (e) {
            if (e.target.closest('.sp-gl')) { setGantiLibur(); closePicker(); return; }
            var addBtn = e.target.closest('.sp-add');
            if (addBtn) { addShift(addBtn.getAttribute('data-shift-id')); closePicker(); return; }
            var item = e.target.closest('.sp-item');
            if (item) { setShift(item.getAttribute('data-shift-id')); closePicker(); }
        });
        $id('sp-clear').addEventListener('click', function () { clearShift(); closePicker(); });
        $id('sp-close').addEventListener('click', closePicker);
        document.addEventListener('mousedown', function (e) {
            if (!e.target.closest('#shift-picker') && !e.target.closest('#jadwal-wrapper') && !e.target.closest('.sched-toolbar')) {
                closePicker();
            }
        });
        wrapper.addEventListener('scroll', closePicker, true);
        window.addEventListener('resize', closePicker);
        window.addEventListener('scroll', closePicker);

        // Keyboard
        document.addEventListener('keydown', function (e) {
            if (isTyping(e) || $('.modal.show').length || document.querySelector('.swal2-popup:not(.swal2-toast)')) {
                if (e.key === 'Escape' && e.target.id === 'emp-search') e.target.blur();
                return;
            }
            var ctrl = e.ctrlKey || e.metaKey;
            if (ctrl && e.key.toLowerCase() === 's') { e.preventDefault(); save(); return; }
            if (ctrl && e.key.toLowerCase() === 'z') { e.preventDefault(); undo(); return; }
            if (ctrl && e.key.toLowerCase() === 'c' && selected.size) { e.preventDefault(); copySelection(); return; }
            if (ctrl && e.key.toLowerCase() === 'v' && selected.size) { e.preventDefault(); pasteSelection(); closePicker(); return; }
            if (e.altKey && e.key === 'ArrowLeft') { e.preventDefault(); loadWeek(addDays(weekStart(), -7)); return; }
            if (e.altKey && e.key === 'ArrowRight') { e.preventDefault(); loadWeek(addDays(weekStart(), 7)); return; }
            if (e.key === '/') { e.preventDefault(); $id('emp-search').focus(); return; }
            if (e.key === 'Escape') { closePicker(); clearSelection(); setCursor(null); return; }

            if (!selected.size && !anchor) return;

            var m = /^Digit([1-9])$/.exec(e.code) || /^Numpad([1-9])$/.exec(e.code);
            if (m && !ctrl && !e.altKey) {
                var s = palette[+m[1] - 1];
                if (!s) return;
                e.preventDefault();
                if (e.shiftKey) addShift(s.id); else setShift(s.id);
                closePicker();
                return;
            }
            if (e.key === 'Delete' || e.key === 'Backspace') { e.preventDefault(); clearShift(); closePicker(); return; }
            if (e.key.toLowerCase() === 'g' && !ctrl && !e.altKey) { e.preventDefault(); setGantiLibur(); closePicker(); return; }
            if (e.key === 'Enter') { e.preventDefault(); openPicker(); return; }

            var dir = { ArrowUp: [-1, 0], ArrowDown: [1, 0], ArrowLeft: [0, -1], ArrowRight: [0, 1] }[e.key];
            if (dir && anchor) {
                e.preventDefault();
                var rows = visibleRows();
                var r = rows.indexOf(anchor.parentNode) + dir[0];
                var c = +anchor.getAttribute('data-col') + dir[1];
                if (r < 0 || r >= rows.length || c < 0 || c > 6) return;
                var next = cellAt(rows[r], c);
                if (!next) return;
                closePicker();
                setCursor(next);
                select([next]);
                next.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        });

        // Toolbar
        $id('save-schedule-btn').addEventListener('click', function () { save(); });
        $id('undo-btn').addEventListener('click', undo);
        $id('prev-week-btn').addEventListener('click', function () { loadWeek(addDays(weekStart(), -7)); });
        $id('next-week-btn').addEventListener('click', function () { loadWeek(addDays(weekStart(), 7)); });
        $id('this-week-btn').addEventListener('click', function () {
            var monday = mondayOf(new Date());
            if (monday === weekStart()) { showAlert('info', 'Sudah berada di minggu ini'); return; }
            loadWeek(monday);
        });
        $id('week-range').addEventListener('click', function () {
            var inp = $id('week-jump');
            if (inp.showPicker) { inp.classList.remove('d-none'); inp.style.cssText = 'position:absolute;opacity:0;width:1px;height:1px;'; try { inp.showPicker(); return; } catch (err) {} }
            inp.classList.remove('d-none');
            inp.style.cssText = 'width:150px;';
            inp.focus();
        });
        $id('week-jump').addEventListener('change', function () {
            if (this.value) loadWeek(mondayOf(parseYmd(this.value)));
        });
        $id('rekap-libur-btn').addEventListener('click', function () {
            if (!$id('rekap-from').value) {
                // default: bulan dari minggu yang sedang dibuka
                var d = parseYmd(weekStart());
                $id('rekap-from').value = ymd(new Date(d.getFullYear(), d.getMonth(), 1));
                $id('rekap-to').value = ymd(new Date(d.getFullYear(), d.getMonth() + 1, 0));
            }
            $('#rekapLiburModal').modal('show');
            loadRekapLibur();
        });
        $id('rekap-load').addEventListener('click', loadRekapLibur);
        $id('rekap-search').addEventListener('input', renderRekapLibur);
        $id('emp-search').addEventListener('input', function () { clearSelection(); closePicker(); applyFilters(); });
        $id('division-filter').addEventListener('change', function () { clearSelection(); closePicker(); applyFilters(); });

        document.querySelectorAll('.copy-week-option').forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                e.preventDefault();
                var thisMonday = mondayOf(new Date());
                var t = opt.getAttribute('data-target');
                if (t === 'this') performCopyWeek(weekStart(), thisMonday, 'Minggu Ini');
                else if (t === 'next') performCopyWeek(weekStart(), addDays(thisMonday, 7), 'Minggu Depan');
                else {
                    var target = addDays(weekStart(), 7);
                    performCopyWeek(weekStart(), target, 'minggu ' + parseYmd(target).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
                }
            });
        });

        window.addEventListener('beforeunload', function (e) {
            if (hasDirty()) { e.preventDefault(); e.returnValue = ''; }
        });

        // Shift form submit
        $id('shift-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var id = $id('shift-id').value;
            var body = {
                name: $id('shift-name').value.trim(),
                start_time: $id('shift-start').value.trim(),
                end_time: $id('shift-end').value.trim(),
                active: $id('shift-active').value,
                color: $id('shift-color').value
            };
            if (!body.name || !body.start_time || !body.end_time) { showAlert('danger', 'Semua field shift wajib diisi'); return; }
            var isEdit = currentShiftMode === 'edit' && id;
            showLoading(true);
            fetch(isEdit ? URLS.shiftUpdate.replace('__ID__', id) : URLS.shiftStore, {
                method: isEdit ? 'PUT' : 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.success) throw new Error();
                    showAlert('success', 'Shift berhasil disimpan');
                    $('#shiftModal').modal('hide');
                    return reloadAfterShiftChange();
                })
                .catch(function () { showAlert('danger', 'Gagal menyimpan shift'); })
                .finally(function () { showLoading(false); });
        });
    });
})();
</script>
@endpush

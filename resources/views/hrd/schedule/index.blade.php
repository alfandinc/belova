@extends('layouts.hrd.app')
@section('title', 'HRD | Jadwal Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<style>
    .sched-toolbar { position: sticky; top: 0; z-index: 30; background: #fff; padding: 10px 0; border-bottom: 1px solid #e8ebf3; }
    .sched-toolbar .btn { white-space: nowrap; }
    /* Toolbar rows: left group / right group, every control the same height */
    .tb-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .tb-group { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
    .tb-title { margin: 0; font-weight: 700; font-size: 18px; }
    .tb-week { min-width: 190px; }
    .tb-search { position: relative; }
    .tb-search .fa-search { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: 12px; pointer-events: none; }
    .tb-search input { width: 200px; padding-left: 28px; }
    .tb-division { width: 180px; }
    .sched-toolbar .form-control-sm, .sched-toolbar .btn-sm { height: 31px; }
    /* Floating, so loading never pushes the table (and the scroll position) around */
    #ajax-loading { position: fixed; top: 80px; left: 50%; transform: translateX(-50%); z-index: 1060; margin: 0 !important;
        background: #fff; padding: 6px 14px; border-radius: 20px; box-shadow: 0 4px 16px rgba(0,0,0,.15); font-size: 13px; }
    .sched-palette { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .sched-palette .pal-chip { border: 0; border-radius: 4px; padding: 3px 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .sched-palette .pal-chip kbd { font-size: 10px; padding: 0 4px; margin-right: 4px; background: rgba(0,0,0,.25); color: #fff; }
    .sched-palette .pal-chip:disabled { opacity: .45; cursor: not-allowed; }
    .sched-scroll { max-height: calc(100vh - 200px); overflow: auto; border: 1px solid #dee2e6; }
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
    td.sc[data-pending] { box-shadow: inset 0 0 0 2px #ffb74d; }
    td.sc[data-pending]::before { content: attr(data-pending); position: absolute; left: 3px; bottom: 0; font-size: 9px; line-height: 1.1; color: #e65100; pointer-events: none; }
    td.sc.is-today { background: #f5faff; }
    td.sc.sel { box-shadow: inset 0 0 0 2px #1e88e5; background: #e3f2fd; }
    td.sc.cursor { box-shadow: inset 0 0 0 3px #0d47a1; }
    td.sc.dirty::after { content: ''; position: absolute; top: 0; right: 0; border-style: solid; border-width: 0 9px 9px 0; border-color: transparent #ff9800 transparent transparent; }
    /* Cuti / libur: same chip as a shift, so every filled cell looks alike */
    td.sc-libur { cursor: not-allowed; }
    .sc-chip.sc-libur-chip { background: #dc3545; color: #fff; text-align: center; }
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
    .sched-help { background: #f8f9fc; border: 1px solid #e8ebf3; border-radius: 6px; padding: 8px 10px; color: #555; }
    .sched-help kbd { font-size: 10px; }
    #save-schedule-btn .badge { font-size: 10px; }
    /* Row 2: selection status, then the shifts that can be applied to it */
    .sched-palette-row { display: flex; align-items: flex-start; gap: 8px; background: #f8f9fc; border: 1px solid #e8ebf3; border-radius: 6px; padding: 6px 8px; }
    .sel-info { flex: none; font-size: 12px; padding: 3px 8px; border-radius: 4px; background: #f1f3f5; color: #6c757d; white-space: nowrap; }
    .sel-info.has-sel { background: #e3f2fd; color: #0d47a1; font-weight: 600; }
    .sched-palette.no-sel .pal-chip { opacity: .45; }
    /* Palette can be collapsed (remembered per browser); the shift picker still opens on the selected cells */
    .sched-palette { flex: 1 1 auto; min-width: 0; }
    .pal-toggle { flex: none; margin-left: auto; padding: 2px 8px; line-height: 1; }
    .pal-toggle i { transition: transform .15s; }
    .sched-palette-row.collapsed .sched-palette { display: none; }
    .sched-palette-row.collapsed .pal-toggle i { transform: rotate(180deg); }
    @media (max-width: 767.98px) {
        .sched-toolbar { position: static; }
        .sched-palette-row { flex-wrap: wrap; align-items: center; }
        .sel-info { white-space: normal; flex: 1 1 auto; }
        /* One swipeable row of shifts instead of many wrapped lines */
        .sched-palette { order: 3; flex: 0 0 100%; flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
        .sched-palette .pal-chip { flex: none; }
    }
    /* Read-only grid (roles other than Hrd / Admin): hide the editing tools */
    .sched-readonly #undo-btn, .sched-readonly #save-schedule-btn, .sched-readonly .sched-palette-row,
    .sched-readonly .edit-only, .sched-readonly #sched-help { display: none !important; }
    .sched-readonly .sched-table td.sc { cursor: default; }
    .dirty-mark { display: inline-block; width: 0; height: 0; border-style: solid; border-width: 0 9px 9px 0; border-color: transparent #ff9800 transparent transparent; vertical-align: middle; }
    .sched-table .sched-emp small { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
    .sched-table .gl-day { display: block; font-size: 11px; font-weight: 600; color: #e8590c; margin-top: 2px; }
    /* Readable sizes: day header, shift hours, footer counts */
    .sched-table thead th .day-name { font-size: 14px; }
    .sched-table thead th .day-date { display: block; font-size: 13px; font-weight: 600; color: #495057; }
    .sched-table thead th.is-weekend .day-date { color: #dc3545; }
    .sched-table thead th.is-today .day-date { color: #0d47a1; }
    .sc-chip .sc-time { display: block; font-size: 11px; font-weight: 500; opacity: .85; line-height: 1.3; }
    .sched-table tfoot td { font-size: 13px; }
    /* Leave balance columns (cuti / ganti libur) */
    .sched-table .sched-jatah-col { width: 70px; min-width: 70px; text-align: center; font-size: 12px; background: #fcfcfd; }
    .sched-table thead th.sched-jatah-col { cursor: default; line-height: 1.2; }
    /* Karyawan / Cuti / Ganti Libur headers: same size as the day names, centered in the tall header row */
    .sched-table thead th.sched-name-col, .sched-table thead th.sched-jatah-col { vertical-align: middle !important; font-size: 14px; color: #212529; }
    .sched-table .jatah-num { display: block; font-size: 16px; font-weight: 700; line-height: 1.2; }
    .sched-table .jatah-sub { display: block; font-size: 10px; line-height: 1.2; white-space: nowrap; }
    .sched-table td.jatah-btn { cursor: pointer; }
    .sched-table td.jatah-btn:hover { background: #eef4ff; }
    .riwayat-table { font-size: 13px; text-align: left; margin-bottom: 0; }
    .riwayat-table th { font-size: 12px; white-space: nowrap; }
    .riwayat-wrap { max-height: 55vh; overflow-y: auto; }
    /* Ganti libur matcher: worked Sundays (left) ↔ weekdays off (right) */
    .gl-match { border: 1px solid #e3e7ef; border-radius: 6px; padding: 10px; background: #fafbfd; }
    .gl-col-title { font-size: 12px; font-weight: 700; color: #495057; margin-bottom: 4px; }
    .gl-list { max-height: 260px; overflow-y: auto; border: 1px solid #e3e7ef; border-radius: 4px; background: #fff; }
    .gl-item { display: block; margin: 0; padding: 6px 8px; font-size: 13px; cursor: pointer; border-bottom: 1px solid #f1f3f7; }
    .gl-item:last-child { border-bottom: 0; }
    .gl-item:hover { background: #f3f7ff; }
    .gl-item input { margin-right: 6px; }
    .gl-item:has(input:checked) { background: #e3eeff; font-weight: 600; }
</style>

<div class="container-fluid{{ $canEdit ? '' : ' sched-readonly' }}">
    <div class="sched-toolbar">
        {{-- Row 1: title · save --}}
        <div class="tb-row">
            <h4 class="tb-title">Jadwal Karyawan</h4>
            <div class="tb-group">
                <button id="undo-btn" type="button" class="btn btn-outline-secondary btn-sm" disabled title="Batalkan perubahan terakhir (Ctrl+Z)"><i class="fa fa-undo"></i> Undo</button>
                <button id="save-schedule-btn" type="button" class="btn btn-primary btn-sm" disabled title="Simpan (Ctrl+S)">
                    <i class="fa fa-save"></i> Simpan <span class="badge badge-light ml-1" id="pending-count">0</span>
                </button>
            </div>
        </div>

        {{-- Row 2: week navigation · search & filter · more --}}
        <div class="tb-row mt-2">
            <div class="tb-group">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary" id="prev-week-btn" title="Minggu sebelumnya (Alt+←)"><i class="fa fa-chevron-left"></i></button>
                    <button type="button" class="btn btn-outline-primary font-weight-bold tb-week" id="week-range" title="Klik untuk pilih tanggal">
                        {{ $startOfWeek->locale('id')->isoFormat('D MMM YYYY') }} – {{ $startOfWeek->copy()->addDays(6)->locale('id')->isoFormat('D MMM YYYY') }}
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="next-week-btn" title="Minggu berikutnya (Alt+→)"><i class="fa fa-chevron-right"></i></button>
                </div>
                <input type="date" id="week-jump" class="d-none">
                <button id="this-week-btn" type="button" class="btn btn-outline-secondary btn-sm">Minggu ini</button>
            </div>
            <div class="tb-group">
                <div class="tb-search">
                    <i class="fa fa-search"></i>
                    <input type="search" id="emp-search" class="form-control form-control-sm" placeholder="Cari karyawan ( / )">
                </div>
                <select id="division-filter" class="form-control form-control-sm tb-division">
                    <option value="">Semua Divisi</option>
                </select>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" title="Copy minggu, rekap, print, kelola shift">
                        <i class="fa fa-ellipsis-h"></i> Lainnya
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="edit-only">
                            <h6 class="dropdown-header">Copy jadwal minggu yang dibuka ke…</h6>
                            <a href="#" class="dropdown-item copy-week-option" data-target="this"><i class="fa fa-copy fa-fw mr-1"></i>Minggu ini</a>
                            <a href="#" class="dropdown-item copy-week-option" data-target="next"><i class="fa fa-copy fa-fw mr-1"></i>Minggu depan</a>
                            <a href="#" class="dropdown-item copy-week-option" data-target="following"><i class="fa fa-copy fa-fw mr-1"></i>Minggu setelah yang dibuka</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item" id="rekap-libur-btn"><i class="fa fa-calendar-check fa-fw mr-1"></i>Rekap masuk Minggu / libur nasional</a>
                            <a href="#" class="dropdown-item" id="audit-log-btn"><i class="fa fa-history fa-fw mr-1"></i>Riwayat perubahan minggu ini</a>
                        </div>
                        <a href="#" class="dropdown-item" id="print-btn" target="_blank"><i class="fa fa-print fa-fw mr-1"></i>Print jadwal</a>
                        <a href="#" class="dropdown-item edit-only" id="open-shift-mgmt"><i class="fa fa-cog fa-fw mr-1"></i>Kelola shift</a>
                        @if($canEdit)
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">Cuti &amp; libur</h6>
                        <a href="#" class="dropdown-item" id="leave-capacity-btn"><i class="fa fa-users fa-fw mr-1"></i>Kuota libur harian</a>
                        <a href="#" class="dropdown-item" id="reset-annual-btn"><i class="fa fa-undo fa-fw mr-1"></i>Reset cuti tahunan</a>
                        <a href="#" class="dropdown-item" id="pasangkan-all-btn"><i class="fa fa-link fa-fw mr-1"></i>Pasangkan ganti libur tanpa tanggal</a>
                        <a href="#" class="dropdown-item" id="libur-nasional-btn"><i class="fa fa-calendar-day fa-fw mr-1"></i>Libur nasional</a>
                        @endif
                        <div class="edit-only">
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item" data-toggle="collapse" data-target="#sched-help"><i class="fa fa-keyboard fa-fw mr-1"></i>Cara pakai &amp; pintasan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 3: what to do now + the shifts to apply --}}
        <div class="sched-palette-row mt-2" id="palette-row">
            <div class="sel-info" id="sel-info"></div>
            <div class="sched-palette" id="sched-palette"></div>
            <button type="button" class="btn btn-sm btn-light pal-toggle" id="palette-toggle" title="Sembunyikan / tampilkan daftar shift" aria-expanded="true">
                <i class="fa fa-chevron-up"></i>
            </button>
        </div>

        <div id="sched-help" class="collapse small sched-help mt-2">
            <div class="row">
                <div class="col-md-4 mb-1">
                    <b>1. Pilih sel</b><br>
                    Klik sel, atau tarik (drag) untuk blok. Klik <b>nama karyawan</b> = 1 minggu, klik <b>nama hari</b> = 1 kolom.
                    <kbd>Shift</kbd>+klik = rentang, <kbd>Ctrl</kbd>+klik = tambah sel. <kbd>←↑↓→</kbd> pindah sel.
                </div>
                <div class="col-md-4 mb-1">
                    <b>2. Isi</b><br>
                    Klik shift di atas / di popup, atau tekan <kbd>1</kbd>–<kbd>9</kbd>. <kbd>Shift</kbd>+angka atau tombol <b>+2</b> = shift kedua (double shift).
                    <kbd>G</kbd> = ganti libur (pilih hari Minggu yang diganti; G lagi = ubah). <kbd>Del</kbd> = kosongkan. <kbd>Enter</kbd> = buka popup.
                </div>
                <div class="col-md-4 mb-1">
                    <b>3. Simpan</b><br>
                    Sel bertanda <span class="dirty-mark"></span> belum disimpan. <kbd>Ctrl+S</kbd> simpan, <kbd>Ctrl+Z</kbd> batalkan,
                    <kbd>Ctrl+C</kbd>/<kbd>Ctrl+V</kbd> copy-paste blok, <kbd>Esc</kbd> batal pilih.
                    Masuk di hari <span class="text-danger">Minggu / libur nasional</span> = 1 hari ganti libur. Dengan <kbd>G</kbd> ganti libur
                    bisa langsung dijadwalkan setelah hari Minggu yang terjadwal masuk, walau belum dikerjakan.
                </div>
            </div>
        </div>
    </div>

    <div id="ajax-loading" style="display:none;text-align:center;" class="my-2">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat...
    </div>
    <div id="jadwal-wrapper" class="mt-2">
        @include('hrd.schedule._table', ['dates' => $dates, 'employeesByDivision' => $employeesByDivision, 'shifts' => $shifts, 'allShifts' => $allShifts, 'schedules' => $schedules, 'startOfWeek' => $startOfWeek, 'jatah' => $jatah])
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
                    <div class="small text-muted mb-2">Tiap hari masuk Minggu / libur nasional = 1 hari ganti libur, dipasangkan dengan tanggal libur penggantinya.</div>
                    <div id="rekap-body" style="max-height:60vh;overflow:auto;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pilih hari masuk Minggu / libur nasional yang diganti oleh sel Ganti Libur (G) --}}
    <div class="modal fade" id="hariMasukModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hm-title">Ganti libur</h5>
                    <button type="button" class="close hm-cancel" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="hm-info"></p>
                    <label for="hm-select" class="small mb-1">Hari masuk Minggu / libur nasional yang diganti</label>
                    <select id="hm-select" class="form-control"></select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary hm-cancel">Batal</button>
                    <button type="button" class="btn btn-primary" id="hm-ok">Pilih</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Riwayat cuti / ganti libur (klik angka di kolom Cuti / Ganti Libur) --}}
    <div class="modal fade" id="riwayatModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="riwayat-title">Riwayat</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="riwayat-body"></div>
            </div>
        </div>
    </div>

    {{-- Riwayat perubahan jadwal minggu yang dibuka (audit log) --}}
    <div class="modal fade" id="auditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Riwayat perubahan minggu ini</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="audit-body"></div>
            </div>
        </div>
    </div>

    {{-- Kuota libur harian --}}
    <div class="modal fade" id="kuotaModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <form class="modal-content" id="kuota-form">
                <div class="modal-header">
                    <h5 class="modal-title">Kuota libur harian</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <label for="kuota-input">Maksimal karyawan libur per hari</label>
                    <input type="number" id="kuota-input" class="form-control" min="1" max="100" required>
                    <small class="form-text text-muted">Jika tercapai, karyawan lain tidak bisa mengajukan libur di tanggal tersebut.</small>
                    <div class="invalid-feedback d-block" id="kuota-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="kuota-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @if($canEdit)
    {{-- Master libur nasional: masuk pada tanggal ini = +1 ganti libur, sama seperti hari Minggu --}}
    <div class="modal fade" id="liburNasionalModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Libur Nasional</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Pilih tahun">
                            <button type="button" class="btn btn-light border" id="ln-prev-year" title="Tahun sebelumnya"><i class="fa fa-chevron-left"></i></button>
                            <button type="button" class="btn btn-light border font-weight-bold" id="ln-year" disabled></button>
                            <button type="button" class="btn btn-light border" id="ln-next-year" title="Tahun berikutnya"><i class="fa fa-chevron-right"></i></button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="ln-add"><i class="fa fa-plus-circle mr-1"></i>Tambah Libur</button>
                    </div>
                    <form id="ln-form" class="border rounded p-2 mb-2 bg-light" style="display:none;" novalidate>
                        <input type="hidden" id="ln-id">
                        <div class="small font-weight-bold mb-1" id="ln-form-title">Tambah Libur Nasional</div>
                        <div class="form-row align-items-start">
                            <div class="col-sm-4 mb-1">
                                <input type="date" class="form-control form-control-sm" id="ln-tanggal" required>
                            </div>
                            <div class="col-sm mb-1">
                                <input type="text" class="form-control form-control-sm" id="ln-nama" maxlength="150" required placeholder="Nama libur, contoh: Hari Kemerdekaan RI">
                            </div>
                            <div class="col-sm-auto mb-1 text-nowrap">
                                <button type="button" class="btn btn-sm btn-light border" id="ln-cancel">Batal</button>
                                <button type="submit" class="btn btn-sm btn-primary" id="ln-save">Simpan</button>
                            </div>
                        </div>
                        <div class="small text-danger" id="ln-error"></div>
                    </form>
                    <div class="table-responsive" style="max-height:55vh;overflow-y:auto;">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Libur</th>
                                    <th>Karyawan Terjadwal</th>
                                    <th style="width:90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="ln-body"></tbody>
                        </table>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="fa fa-info-circle mr-1"></i>Karyawan yang terjadwal masuk pada libur nasional mendapat +1 jatah ganti libur, sama seperti hari Minggu.
                        Menambah, memindah, atau menghapus libur nasional otomatis menyesuaikan jatah karyawan yang sudah terjadwal pada tanggal tersebut.
                        Libur yang jatuh pada hari Minggu tidak dihitung dua kali.
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

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
                        <div class="form-group mb-0">
                            <label for="shift-min-staff">Minimal orang per hari</label>
                            <input type="number" class="form-control" id="shift-min-staff" min="0" max="100" placeholder="0 = tanpa minimum">
                            <small class="form-text text-muted">Hari dengan orang di shift ini kurang dari angka ini diberi tanda merah di baris bawah jadwal.</small>
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
        riwayatJatah: "{{ route('hrd.schedule.riwayat_jatah') }}",
        @if($canEdit)
        leaveCapacity: "{{ route('hrd.master.jatah-libur.leave_capacity.get') }}",
        leaveCapacityUpdate: "{{ route('hrd.master.jatah-libur.leave_capacity.update') }}",
        resetAnnual: "{{ route('hrd.master.jatah-libur.reset_annual') }}",
        pasangkan: "{{ route('hrd.master.jatah-libur.pasangkan_otomatis') }}",
        liburNasional: "{{ route('hrd.master.libur-nasional.index') }}",
        jadikanGl: "{{ route('hrd.master.jatah-libur.jadikan_ganti_libur', ['employee' => '__ID__']) }}",
        logs: "{{ route('hrd.schedule.logs') }}",
        cutiUpdate: "{{ route('hrd.master.jatah-libur.cuti.update', ['employee' => '__ID__']) }}",
        hariMasukUpdate: "{{ route('hrd.master.jatah-libur.hari-masuk.update', ['employee' => '__ID__']) }}",
        @endif
        shiftStore: "{{ route('hrd.master.shift.store') }}",
        shiftUpdate: "{{ route('hrd.master.shift.update', ['shift' => '__ID__']) }}",
        shiftDestroy: "{{ route('hrd.master.shift.destroy', ['shift' => '__ID__']) }}"
    };
    var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var CAN_EDIT = @json($canEdit); // Hrd / Admin; other roles get a read-only grid

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
    function isEditable(td) { return CAN_EDIT && td && td.classList.contains('sc') && !td.hasAttribute('data-libur'); }
    // Ganti libur cells also carry the Sunday / holiday worked that they replace (data-gl-masuk)
    function isDirty(td) {
        var shifts = td.getAttribute('data-shifts') || '';
        return shifts !== (td.getAttribute('data-orig') || '')
            || (shifts === 'GL' && (td.getAttribute('data-gl-masuk') || '') !== (td.getAttribute('data-gl-orig') || ''));
    }
    var HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    function shortDate(s) { var d = parseYmd(s); return HARI[d.getDay()] + ' ' + d.getDate() + ' ' + BULAN[d.getMonth()]; }

    // Cells with a leave request still waiting for approval keep a note (see pendingLiburMap)
    function renderCell(td) {
        renderCellContent(td);
        var pending = td.getAttribute('data-pending');
        if (pending) td.title = pending + ' (menunggu persetujuan)' + (td.title ? '\n' + td.title : '');
    }
    function renderCellContent(td) {
        var ids = getIds(td);
        if (!ids.length) { td.innerHTML = '<span class="sc-empty">–</span>'; td.title = ''; return; }
        if (ids[0] === 'GL') {
            var masuk = td.getAttribute('data-gl-masuk');
            td.innerHTML = '<span class="sc-chip sc-gl">Ganti Libur' + (masuk ? '<small class="d-block">' + esc(shortDate(masuk)) + '</small>' : '') + '</span>';
            td.title = 'Ganti libur' + (masuk ? '\nMengganti masuk ' + shortDate(masuk) : '') + '\nTekan G lagi untuk mengganti hari masuk';
            return;
        }
        var html = '', titles = [];
        ids.forEach(function (id) {
            var s = shiftMap[id];
            if (!s) { html += '<span class="sc-chip" style="background:#ccc">#' + esc(id) + '</span>'; return; }
            var bg = s.color || '#adb5bd';
            html += '<span class="sc-chip" style="background:' + esc(bg) + ';color:' + contrast(bg) + '">' + esc(s.name)
                + '<span class="sc-time">' + esc(s.start) + '–' + esc(s.end) + '</span></span>';
            titles.push(s.name + ' (' + s.start + '–' + s.end + ')' + (s.active ? '' : ' [tidak aktif]'));
        });
        td.innerHTML = html;
        td.title = titles.join('\n');
    }

    // glMasuk: for ganti libur, the Sunday / holiday worked it replaces
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

    // Resolves to the chosen date, undefined (sel dilewati) or null (batal semua)
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
                    options[d.date] = d.label + ' (' + d.keterangan + (d.shift ? ', ' + d.shift : '') + ')'
                        + (d.terjadwal ? ' – terjadwal, belum dikerjakan' : '');
                });
                // Minggu / libur nasional yang baru diisi di grid ini (disimpan bersamaan); masuk dulu baru libur.
                // Boleh yang belum dikerjakan: HRD bisa menjadwalkan masuk Minggu besok + ganti liburnya minggu depan sekaligus.
                rowCells.forEach(function (c) {
                    var shifts = c.getAttribute('data-shifts'), d = c.getAttribute('data-date');
                    if (c.classList.contains('dirty') && shifts && shifts !== 'GL' && isHariGantiLibur(c) && !options[d] && !taken[d]
                        && d < date) {
                        options[d] = shortDate(d) + ' (belum disimpan' + (d > today ? ', belum dikerjakan' : '') + ')';
                    }
                });
                var keys = Object.keys(options).sort();
                var sorted = {};
                keys.forEach(function (k) { sorted[k] = options[k]; });
                var name = employeeName(td);
                if (!keys.length) {
                    return swal.fire({
                        title: 'Belum ada hari masuk',
                        text: name + ' belum punya hari masuk / jadwal masuk Minggu / libur nasional sebelum ' + shortDate(date) + ' yang belum dipakai (masuk dulu baru libur).',
                        icon: 'warning'
                    }).then(function () { return undefined; });
                }
                var current = td.getAttribute('data-gl-masuk') || '';
                return chooseHariMasuk('Ganti libur ' + name, 'Libur pada <b>' + esc(shortDate(date)) + '</b>.', sorted,
                    sorted.hasOwnProperty(current) ? current : keys[0]);
            })
            .catch(function () { showAlert('danger', 'Gagal memuat hari masuk ' + employeeName(td)); return null; });
    }

    // Modal with a select of worked days; resolves to the chosen date, or null when cancelled. Resolves only
    // once the modal is fully hidden, so the next cell's modal (several cells marked G at once) can open.
    function chooseHariMasuk(title, infoHtml, options, selected) {
        return new Promise(function (resolve) {
            var $m = $('#hariMasukModal'), result = null;
            var finish = function (val) { result = val; $m.modal('hide'); };
            $('#hm-title').text(title);
            $('#hm-info').html(infoHtml);
            $('#hm-select').html(Object.keys(options).map(function (k) {
                return '<option value="' + esc(k) + '"' + (k === selected ? ' selected' : '') + '>' + esc(options[k]) + '</option>';
            }).join(''));
            $m.off('.hm')
                .on('click.hm', '#hm-ok', function () { finish($('#hm-select').val()); })
                .on('click.hm', '.hm-cancel', function () { finish(null); })
                .on('hidden.bs.modal.hm', function () { $m.off('.hm'); resolve(result); })
                .on('shown.bs.modal.hm', function () { $('#hm-select').trigger('focus'); })
                .on('keydown.hm', function (e) { if (e.key === 'Enter') { e.preventDefault(); finish($('#hm-select').val()); } })
                .modal('show');
        });
    }

    // Modal riwayat saat angka Cuti / Ganti Libur diklik. HRD / Admin juga bisa mengubah jatah cuti dan
    // pasangan hari masuk ganti libur langsung di modal ini (inline, tanpa modal bertumpuk).
    var CAN_MANAGE_JATAH = CAN_EDIT;
    var RIWAYAT_STATUS = {
        disetujui: ['success', 'Disetujui'], menunggu: ['warning', 'Menunggu'], ditolak: ['danger', 'Ditolak'],
        tersedia: ['success', 'Bisa dipakai'], terjadwal: ['info', 'Terjadwal (belum dikerjakan)'],
        diajukan: ['warning', 'Diajukan'], dipakai: ['secondary', 'Sudah dipakai']
    };
    var riwayat = null; // { emp, jenis, nama, saldo, rows } of the open modal
    function riwayatBadge(status) {
        var s = RIWAYAT_STATUS[status] || ['light', status || '-'];
        return '<span class="badge badge-' + s[0] + '">' + esc(s[1]) + '</span>';
    }
    // JSON request for the HRD actions; rejects with the server's (validation) message
    function jatahRequest(url, method, body) {
        return fetch(url, {
            method: method,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (res.ok) return data;
                var errors = data.errors || {};
                var first = Object.keys(errors).map(function (k) { return errors[k][0]; })[0];
                throw new Error(first || data.message || data.error || 'Gagal menyimpan');
            });
        });
    }
    // Update the number in the grid without reloading it (unsaved schedule edits stay)
    function setJatahCell(emp, jenis, saldo) {
        var num = document.querySelector('#sched-table td.jatah-btn[data-emp="' + emp + '"][data-jenis="' + jenis + '"] .jatah-num');
        if (!num) return;
        num.textContent = saldo;
        num.classList.toggle('text-muted', !(saldo > 0));
    }

    function showRiwayatJatah(emp, jenis, nama) {
        var url = URLS.riwayatJatah + '?employee_id=' + encodeURIComponent(emp) + '&jenis=' + encodeURIComponent(jenis);
        var $modal = $('#riwayatModal');
        if (!$modal.hasClass('show')) {
            $('#riwayat-title').text((jenis === 'cuti' ? 'Riwayat Cuti ' : 'Riwayat Ganti Libur ') + nama);
            $('#riwayat-body').html('<div class="text-muted text-center p-3"><span class="spinner-border spinner-border-sm mr-1"></span> Memuat...</div>');
            $modal.modal('show');
        }
        return jatahRequest(url, 'GET')
            .then(function (data) {
                riwayat = { emp: emp, jenis: jenis, nama: data.nama || nama, saldo: data.saldo, rows: data.ganti_libur_tanggal || [] };
                setJatahCell(emp, jenis, data.saldo);
                $('#riwayat-title').text((jenis === 'cuti' ? 'Riwayat Cuti ' : 'Riwayat Ganti Libur ') + riwayat.nama);
                $('#riwayat-body').html(jenis === 'cuti' ? riwayatCutiHtml(data) : riwayatGantiLiburHtml(data));
            })
            .catch(function (err) { $('#riwayat-body').html('<div class="text-danger p-3">' + esc(err.message || 'Gagal memuat riwayat') + '</div>'); });
    }

    function riwayatCutiHtml(data) {
        var html = '<div class="d-flex align-items-center flex-wrap mb-2" id="rw-cuti-line">'
            + '<span>Sisa cuti tahunan: <b>' + data.saldo + ' hari</b></span>'
            + (CAN_MANAGE_JATAH ? '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 ml-2 rw-edit-cuti"><i class="fa fa-pen"></i> Ubah</button>' : '')
            + '</div>';
        return html + (data.cuti.length
            ? '<div class="riwayat-wrap"><table class="table table-sm table-bordered riwayat-table"><thead><tr><th>Tanggal</th><th>Hari</th><th>Alasan</th><th>Status</th></tr></thead><tbody>'
                + data.cuti.map(function (c) {
                    return '<tr><td>' + esc(c.tanggal) + '</td><td class="text-center">' + c.jumlah_hari + '</td><td>' + esc(c.alasan) + '</td><td>' + riwayatBadge(c.status) + '</td></tr>';
                }).join('') + '</tbody></table></div>'
            : '<p class="text-muted mb-0">Belum ada pengajuan cuti.</p>');
    }

    // Ganti libur modal, kept simple: what can still be used, a two-column matcher for HRD (worked Sunday ↔
    // weekday off), and the pairs already matched.
    function riwayatGantiLiburHtml(data) {
        var r = data.ganti_libur_ringkasan || {};
        var tersedia = riwayat.rows.filter(function (x) { return x.status === 'tersedia'; }).slice().reverse(); // oldest first
        var terjadwal = riwayat.rows.filter(function (x) { return x.status === 'terjadwal'; });
        var tanpaTanggal = riwayat.rows.filter(function (x) { return !x.date && x.pengajuan_id; });
        var dipakai = riwayat.rows.filter(function (x) { return x.date && x.pengajuan_id; });
        var kosong = (data.hari_kosong || []).slice().reverse();

        var html = '<p class="mb-1">Bisa dipakai: <b>' + data.saldo + ' hari</b>'
            + (r.terjadwal ? ' · ' + r.terjadwal + ' terjadwal (belum dikerjakan)' : '')
            + (r.diajukan ? ' · ' + r.diajukan + ' diajukan' : '') + '</p>'
            + '<p class="small text-muted mb-3">Dihitung mulai ' + esc(data.mulai) + '.</p>';

        if (CAN_MANAGE_JATAH) {
            var masukItems = tersedia.map(function (x) {
                return '<label class="gl-item"><input type="radio" name="gl-masuk" value="' + x.date + '"> '
                    + esc(x.masuk) + (x.keterangan && x.keterangan !== 'Hari Minggu' ? ' <small class="text-muted">' + esc(x.keterangan) + '</small>' : '') + '</label>';
            }).join('');
            var liburItems = kosong.map(function (k) {
                return '<label class="gl-item"><input type="radio" name="gl-libur" value="kosong:' + k.date + '" data-date="' + k.date + '"> '
                    + esc(k.label) + ' <small class="text-muted">tanpa jadwal</small></label>';
            }).concat(tanpaTanggal.map(function (x) {
                return '<label class="gl-item"><input type="radio" name="gl-libur" value="gl:' + x.pengajuan_id + '" data-date="' + x.libur_mulai + '"> '
                    + esc(x.libur) + ' <small class="text-muted">ganti libur tanpa tanggal masuk</small></label>';
            })).join('');
            html += '<div class="gl-match mb-3">'
                + '<div class="small text-muted mb-2">Pilih hari masuk di kiri dan hari liburnya di kanan, lalu klik <b>Cocokkan</b>. Hari masuk yang sudah dicocokkan tidak dihitung lagi.</div>'
                + '<div class="row">'
                + '<div class="col-6"><div class="gl-col-title">Masuk hari Minggu / libur nasional</div><div class="gl-list">'
                + (masukItems || '<div class="text-muted small p-2">Tidak ada yang belum dipakai.</div>') + '</div></div>'
                + '<div class="col-6"><div class="gl-col-title">Hari kerja tanpa jadwal</div><div class="gl-list">'
                + (liburItems || '<div class="text-muted small p-2">Tidak ada.</div>') + '</div></div>'
                + '</div>'
                + '<div class="d-flex align-items-center mt-2"><button type="button" class="btn btn-primary btn-sm rw-cocokkan" disabled><i class="fa fa-link"></i> Cocokkan</button>'
                + '<span class="small ml-2 rw-match-info"></span></div></div>';
        } else if (tersedia.length) {
            html += '<div class="mb-3"><div class="gl-col-title">Bisa dipakai</div>'
                + tersedia.map(function (x) { return '<span class="badge badge-success mr-1 mb-1">' + esc(x.masuk) + '</span>'; }).join('') + '</div>';
        }

        if (terjadwal.length) {
            html += '<div class="mb-3"><div class="gl-col-title">Terjadwal (bisa dipakai setelah dikerjakan)</div>'
                + terjadwal.map(function (x) { return '<span class="badge badge-info mr-1 mb-1">' + esc(x.masuk) + '</span>'; }).join('') + '</div>';
        }

        html += '<div class="gl-col-title">Sudah dicocokkan</div>';
        html += dipakai.length
            ? '<div class="riwayat-wrap"><table class="table table-sm table-bordered riwayat-table mb-0"><thead><tr><th>Masuk</th><th>Libur pengganti</th><th>Status</th>'
                + (CAN_MANAGE_JATAH ? '<th></th>' : '') + '</tr></thead><tbody>'
                + dipakai.map(function (x) {
                    var i = riwayat.rows.indexOf(x);
                    return '<tr data-i="' + i + '"><td class="rw-masuk">' + esc(x.masuk) + '</td>'
                        + '<td>' + esc(x.libur) + (x.jumlah_hari > 1 ? ' (' + x.jumlah_hari + ' hari)' : '')
                        + (x.terbalik ? '<div class="small text-danger">libur sebelum hari masuk</div>' : '') + '</td>'
                        + '<td>' + riwayatBadge(x.status) + '</td>'
                        + (CAN_MANAGE_JATAH ? '<td class="text-nowrap rw-act"><button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 rw-edit-masuk" title="Ganti hari masuknya"><i class="fa fa-pen"></i></button></td>' : '')
                        + '</tr>';
                }).join('') + '</tbody></table></div>'
            : '<p class="text-muted small mb-0">Belum ada.</p>';
        return html;
    }

    // Inline edits inside the riwayat modal (HRD / Admin)
    $(document).on('click', '#riwayatModal .rw-edit-cuti', function () {
        $('#rw-cuti-line').html('<label class="mb-0 mr-2" for="rw-cuti-input">Sisa cuti tahunan</label>'
            + '<input type="number" id="rw-cuti-input" class="form-control form-control-sm mr-2" style="width:90px" min="0" max="365" value="' + riwayat.saldo + '"> hari'
            + '<button type="button" class="btn btn-sm btn-primary ml-2 rw-save-cuti">Simpan</button>'
            + '<button type="button" class="btn btn-sm btn-light ml-1 rw-cancel">Batal</button>'
            + '<div class="w-100 small text-danger rw-error"></div>');
        $('#rw-cuti-input').trigger('focus').trigger('select');
    });
    $(document).on('click', '#riwayatModal .rw-save-cuti', function () {
        var $btn = $(this).prop('disabled', true), r = riwayat;
        jatahRequest(URLS.cutiUpdate.replace('__ID__', r.emp), 'PUT', { jatah_cuti_tahunan: $('#rw-cuti-input').val() })
            .then(function (res) {
                showAlert('success', 'Jatah cuti ' + r.nama + ' menjadi ' + res.saldo + ' hari');
                return showRiwayatJatah(r.emp, r.jenis, r.nama);
            })
            .catch(function (err) { $btn.prop('disabled', false); $('#rw-cuti-line .rw-error').text(err.message); });
    });
    $(document).on('keydown', '#rw-cuti-input', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#riwayatModal .rw-save-cuti').trigger('click'); }
    });
    $(document).on('click', '#riwayatModal .rw-cancel', function () {
        showRiwayatJatah(riwayat.emp, riwayat.jenis, riwayat.nama);
    });

    $(document).on('click', '#riwayatModal .rw-edit-masuk', function () {
        var $tr = $(this).closest('tr'), row = riwayat.rows[$tr.data('i')];
        var today = "{{ now()->toDateString() }}";
        if (!row.date && row.jumlah_hari > 1) {
            $tr.find('.rw-masuk').append('<div class="small text-danger">Ganti libur ' + row.jumlah_hari + ' hari tanpa tanggal masuk: tolak dan minta karyawan mengajukan ulang.</div>');
            return;
        }
        // Masuk dulu baru libur: free days already worked and before the libur's first day
        var options = riwayat.rows.filter(function (x) {
            return x.date && !x.pengajuan_id && x.date <= today && x.date < row.libur_mulai;
        });
        if (!options.length) {
            $tr.find('.rw-masuk').append('<div class="small text-danger">Tidak ada hari masuk lain yang belum dipakai sebelum ' + esc(row.libur) + '.</div>');
            return;
        }
        $tr.find('.rw-masuk').html('<select class="form-control form-control-sm rw-masuk-select">' + options.map(function (x) {
            return '<option value="' + x.date + '">' + esc(x.masuk + (x.keterangan ? ' – ' + x.keterangan : '')) + '</option>';
        }).join('') + '</select><div class="small text-danger rw-error"></div>');
        $tr.find('.rw-act').html('<button type="button" class="btn btn-sm btn-success py-0 px-1 rw-save-masuk" title="Simpan"><i class="fa fa-check"></i></button> '
            + '<button type="button" class="btn btn-sm btn-light py-0 px-1 rw-cancel" title="Batal"><i class="fa fa-times"></i></button>');
    });
    $(document).on('click', '#riwayatModal .rw-save-masuk', function () {
        var $btn = $(this).prop('disabled', true), $tr = $btn.closest('tr'), r = riwayat, row = r.rows[$tr.data('i')];
        jatahRequest(URLS.hariMasukUpdate.replace('__ID__', r.emp), 'PUT', { pengajuan_id: row.pengajuan_id, lama: row.date, baru: [$tr.find('.rw-masuk-select').val()] })
            .then(function () {
                showAlert('success', 'Hari masuk pengganti diperbarui');
                return showRiwayatJatah(r.emp, r.jenis, r.nama);
            })
            .catch(function (err) { $btn.prop('disabled', false); $tr.find('.rw-error').text(err.message); });
    });

    // Matcher: enable "Cocokkan" once both sides are picked; the worked day must come before the day off
    $(document).on('change', '#riwayatModal input[name="gl-masuk"], #riwayatModal input[name="gl-libur"]', function () {
        var masuk = $('#riwayatModal input[name="gl-masuk"]:checked').val();
        var $libur = $('#riwayatModal input[name="gl-libur"]:checked');
        var $info = $('#riwayatModal .rw-match-info').removeClass('text-danger').text('');
        var ok = !!(masuk && $libur.length);
        if (ok && masuk >= $libur.data('date')) {
            ok = false;
            $info.addClass('text-danger').text('Hari masuk harus sebelum hari liburnya.');
        } else if (ok) {
            $info.text(shortDate(masuk) + ' → libur ' + shortDate($libur.data('date')));
        }
        $('#riwayatModal .rw-cocokkan').prop('disabled', !ok);
    });
    $(document).on('click', '#riwayatModal .rw-cocokkan', function () {
        var $btn = $(this).prop('disabled', true), r = riwayat;
        var masuk = $('#riwayatModal input[name="gl-masuk"]:checked').val();
        var libur = $('#riwayatModal input[name="gl-libur"]:checked').val() || '';
        var req = libur.indexOf('gl:') === 0
            // a ganti libur already recorded without a worked day: just name the day
            ? jatahRequest(URLS.hariMasukUpdate.replace('__ID__', r.emp), 'PUT', { pengajuan_id: libur.slice(3), lama: null, baru: [masuk] })
            // a weekday HRD once emptied: record it as the ganti libur it was
            : jatahRequest(URLS.jadikanGl.replace('__ID__', r.emp), 'POST', { libur: libur.slice(7), hari_masuk: masuk });
        req.then(function () {
                showAlert('success', 'Dicocokkan: masuk ' + shortDate(masuk));
                return showRiwayatJatah(r.emp, r.jenis, r.nama);
            })
            .catch(function (err) { $btn.prop('disabled', false); $('#riwayatModal .rw-match-info').addClass('text-danger').text(err.message); });
    });

    // Same for every employee at once (confirmation → swal)
    function pasangkanSemua() {
        swal.fire({
            title: 'Pasangkan ganti libur tanpa tanggal?',
            text: 'Untuk semua karyawan: tiap ganti libur yang belum punya tanggal masuk dipasangkan dengan hari masuk Minggu / libur nasional terlama yang belum dipakai sebelum tanggal liburnya. Saldo "bisa dipakai" akan berkurang.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Pasangkan',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return jatahRequest(URLS.pasangkan, 'POST', {})
                    .catch(function (err) { swal.showValidationMessage(err.message); });
            }
        }).then(function (res) {
            if (!res.value) return;
            var v = res.value;
            swal.fire({
                title: v.paired + ' ganti libur dipasangkan',
                html: v.gagal.length
                    ? 'Tidak cukup hari masuk sebelum tanggal libur (perlu dipasangkan manual / ditolak):<ul class="text-left mt-2">'
                        + v.gagal.map(function (g) { return '<li>' + esc(g) + '</li>'; }).join('') + '</ul>'
                    : 'Semua ganti libur tanpa tanggal sudah dipasangkan.',
                icon: v.gagal.length ? 'warning' : 'success'
            });
            loadWeek(weekStart()); // refresh the Ganti Libur column
        });
    }

    function leaveCapacity() {
        $('#kuota-error').text('');
        showLoading(true);
        jatahRequest(URLS.leaveCapacity, 'GET')
            .finally(function () { showLoading(false); })
            .then(function (data) {
                $('#kuota-input').val(data.capacity || 2);
                $('#kuotaModal').modal('show');
            })
            .catch(function (err) { showAlert('danger', err.message || 'Gagal memuat kuota libur'); });
    }
    $(document).on('shown.bs.modal', '#kuotaModal', function () { $('#kuota-input').trigger('focus').trigger('select'); });
    $(document).on('submit', '#kuota-form', function (e) {
        e.preventDefault();
        var $btn = $('#kuota-save').prop('disabled', true);
        jatahRequest(URLS.leaveCapacityUpdate, 'POST', { capacity: $('#kuota-input').val() })
            .then(function (res) {
                $('#kuotaModal').modal('hide');
                showAlert('success', 'Kuota libur harian: ' + res.capacity + ' orang');
            })
            .catch(function (err) { $('#kuota-error').text(err.message); })
            .finally(function () { $btn.prop('disabled', false); });
    });

    // ---------- libur nasional ----------
    // Holidays change the day headers and ganti libur balances, so the grid reloads when the modal closes
    var ln = { tahun: new Date().getFullYear(), rows: [], changed: false };
    function lnLoad() {
        $('#ln-year').text(ln.tahun);
        var $body = $('#ln-body').html('<tr><td colspan="5" class="text-center text-muted"><i class="fa fa-spinner fa-spin mr-1"></i>Memuat...</td></tr>');
        jatahRequest(URLS.liburNasional + '?tahun=' + ln.tahun, 'GET')
            .then(function (res) {
                ln.rows = (res && res.data) || [];
                if (!ln.rows.length) {
                    $body.html('<tr><td colspan="5" class="text-center text-muted">Belum ada libur nasional untuk tahun ' + ln.tahun + '.</td></tr>');
                    return;
                }
                $body.html(ln.rows.map(function (r, i) {
                    var terjadwal = r.is_sunday
                        ? '<span class="text-muted small">Hari Minggu (sudah dihitung)</span>'
                        : (r.terjadwal ? r.terjadwal + ' karyawan' : '<span class="text-muted">-</span>');
                    return '<tr>'
                        + '<td>' + (i + 1) + '</td>'
                        + '<td>' + esc(r.hari) + ', ' + esc(r.tanggal_label) + '</td>'
                        + '<td>' + esc(r.nama) + '</td>'
                        + '<td>' + terjadwal + '</td>'
                        + '<td class="text-nowrap"><div class="btn-group btn-group-sm">'
                        + '<button type="button" class="btn btn-warning ln-edit" data-id="' + r.id + '" title="Ubah"><i class="fa fa-edit"></i></button>'
                        + '<button type="button" class="btn btn-danger ln-delete" data-id="' + r.id + '" title="Hapus"><i class="fa fa-trash"></i></button>'
                        + '</div></td></tr>';
                }).join(''));
            })
            .catch(function (err) {
                $body.html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>');
                showAlert('danger', err.message || 'Gagal memuat libur nasional');
            });
    }
    function lnFind(id) { return ln.rows.filter(function (r) { return r.id === id; })[0]; }
    function lnShowForm(r) {
        $('#ln-id').val(r ? r.id : '');
        $('#ln-tanggal').val(r ? r.tanggal : '');
        $('#ln-nama').val(r ? r.nama : '');
        $('#ln-error').text('');
        $('#ln-form-title').text(r ? 'Ubah Libur Nasional' : 'Tambah Libur Nasional');
        $('#ln-form').slideDown(150, function () { $('#ln-tanggal').trigger('focus'); });
    }
    function lnHideForm() { $('#ln-form').slideUp(150); }
    function liburNasional() {
        ln.tahun = parseYmd(weekStart()).getFullYear();
        ln.changed = false;
        $('#ln-form').hide();
        $('#liburNasionalModal').modal('show');
        lnLoad();
    }
    $(document).on('click', '#ln-prev-year', function () { ln.tahun--; lnLoad(); });
    $(document).on('click', '#ln-next-year', function () { ln.tahun++; lnLoad(); });
    $(document).on('click', '#ln-add', function () { lnShowForm(null); });
    $(document).on('click', '#ln-cancel', lnHideForm);
    $(document).on('click', '.ln-edit', function () {
        var r = lnFind($(this).data('id'));
        if (r) lnShowForm(r);
    });
    $(document).on('submit', '#ln-form', function (e) {
        e.preventDefault();
        var id = $('#ln-id').val(), tanggal = $('#ln-tanggal').val(), nama = $.trim($('#ln-nama').val());
        if (!tanggal || !nama) { $('#ln-error').text('Tanggal dan nama libur wajib diisi.'); return; }
        $('#ln-error').text('');
        var $btn = $('#ln-save').prop('disabled', true);
        jatahRequest(id ? URLS.liburNasional + '/' + id : URLS.liburNasional, id ? 'PUT' : 'POST', { tanggal: tanggal, nama: nama })
            .then(function (res) {
                ln.changed = true;
                lnHideForm();
                showAlert('success', res.message);
                ln.tahun = parseInt(tanggal.substr(0, 4), 10) || ln.tahun; // jump to the saved date's year
                lnLoad();
            })
            .catch(function (err) { $('#ln-error').text(err.message); })
            .finally(function () { $btn.prop('disabled', false); });
    });
    $(document).on('click', '.ln-delete', function () {
        var r = lnFind($(this).data('id'));
        if (!r) return;
        var impact = (!r.is_sunday && r.terjadwal)
            ? '<br><br>Jatah ganti libur <b>' + r.terjadwal + ' karyawan</b> yang terjadwal pada tanggal ini akan dikurangi 1.'
            : '';
        swal.fire({
            icon: 'warning',
            title: 'Hapus libur nasional?',
            html: esc(r.nama) + ' (' + esc(r.tanggal_label) + ')' + impact,
            showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33', reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            jatahRequest(URLS.liburNasional + '/' + r.id, 'DELETE')
                .then(function (res) { ln.changed = true; showAlert('success', res.message); lnLoad(); })
                .catch(function (err) { showAlert('danger', err.message); });
        });
    });
    $(document).on('hidden.bs.modal', '#liburNasionalModal', function () {
        if (ln.changed) { ln.changed = false; loadWeek(weekStart()); }
    });

    // Reset is a confirmation, so it stays a swal
    function resetAnnual() {
        swal.fire({
            title: 'Reset cuti tahunan',
            text: 'Jatah cuti tahunan semua karyawan aktif diset 12 (masa kerja ≥ 1 tahun) atau 0. Lanjutkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Reset',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return jatahRequest(URLS.resetAnnual, 'POST')
                    .catch(function (err) { swal.showValidationMessage(err.message); });
            }
        }).then(function (res) {
            if (!res.value) return;
            showAlert('success', 'Cuti tahunan direset: ' + res.value.set_12 + ' karyawan 12 hari, ' + res.value.set_0 + ' karyawan 0 hari');
            loadWeek(weekStart()); // asks first if there are unsaved schedule edits
        });
    }

    // Audit log of the opened week: who changed which day, from what to what
    function showAuditLog() {
        $('#audit-body').html('<div class="text-muted text-center p-3"><span class="spinner-border spinner-border-sm mr-1"></span> Memuat...</div>');
        $('#auditModal').modal('show');
        jatahRequest(URLS.logs + '?start_date=' + encodeURIComponent(weekStart()), 'GET')
            .then(function (rows) {
                $('#audit-body').html(rows.length
                    ? '<div class="riwayat-wrap"><table class="table table-sm table-bordered riwayat-table"><thead><tr><th>Waktu</th><th>Oleh</th><th>Karyawan</th><th>Tanggal</th><th>Perubahan</th></tr></thead><tbody>'
                        + rows.map(function (r) {
                            return '<tr><td class="text-nowrap">' + esc(r.waktu) + '</td><td>' + esc(r.oleh) + '</td><td>' + esc(r.karyawan) + '</td>'
                                + '<td class="text-nowrap">' + esc(r.tanggal) + '</td>'
                                + '<td><span class="text-muted">' + esc(r.sebelum || 'Kosong') + '</span> → <b>' + esc(r.sesudah || 'Kosong') + '</b>'
                                + '<div class="small text-muted">' + esc(r.aksi) + '</div></td></tr>';
                        }).join('') + '</tbody></table></div>'
                    : '<p class="text-muted mb-0">Belum ada perubahan tercatat untuk minggu ini.</p>');
            })
            .catch(function (err) { $('#audit-body').html('<div class="text-danger p-3">' + esc(err.message || 'Gagal memuat riwayat perubahan') + '</div>'); });
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
        var perShift = [{}, {}, {}, {}, {}, {}, {}]; // column -> shift id -> people
        table.querySelectorAll('tbody td.sc').forEach(function (td) {
            var c = +td.getAttribute('data-col');
            if (td.hasAttribute('data-libur') || td.getAttribute('data-shifts') === 'GL') libur[c]++;
            else if (td.getAttribute('data-shifts')) {
                counts[c]++;
                getIds(td).forEach(function (id) { perShift[c][id] = (perShift[c][id] || 0) + 1; });
            }
        });
        // Shifts with a minimum (Kelola shift → Minimal orang per hari) that this day does not reach
        var minShifts = Object.keys(shiftMap).map(function (id) { return shiftMap[id]; })
            .filter(function (s) { return s.active && s.min > 0; });
        table.querySelectorAll('tfoot .sched-count').forEach(function (td) {
            var c = +td.getAttribute('data-col');
            var kurang = minShifts.filter(function (s) { return (perShift[c][String(s.id)] || 0) < s.min; })
                .map(function (s) { return s.name + ' ' + (perShift[c][String(s.id)] || 0) + '/' + s.min; });
            td.title = counts[c] + ' dari ' + total + ' karyawan masuk' + (libur[c] ? ', ' + libur[c] + ' libur / cuti' : '')
                + (kurang.length ? '\nKurang orang: ' + kurang.join(', ') : '');
            td.innerHTML = '<b>' + counts[c] + '</b> masuk' + (libur[c] ? ' · <span class="text-danger">' + libur[c] + ' libur</span>' : '')
                + (kurang.length ? '<div class="text-danger font-weight-bold"><i class="fa fa-exclamation-triangle"></i> Kurang: ' + esc(kurang.join(', ')) + '</div>' : '');
        });
    }

    // ---------- selection ----------
    // Tells the user what to do next: select cells first, then pick a shift
    function updateSelectionInfo() {
        var n = Array.from(selected).filter(isEditable).length;
        var info = $id('sel-info');
        if (!info) return;
        info.classList.toggle('has-sel', n > 0);
        info.innerHTML = n
            ? n + ' sel dipilih <i class="fa fa-arrow-right"></i>'
            : '<i class="fa fa-hand-pointer"></i> Klik / tarik sel jadwal dulu, lalu pilih shift:';
        $id('sched-palette').classList.toggle('no-sel', n === 0);
    }
    function clearSelection() {
        selected.forEach(function (td) { td.classList.remove('sel'); });
        selected.clear();
        updateSelectionInfo();
    }
    function setCursor(td) {
        if (anchor) anchor.classList.remove('cursor');
        anchor = td;
        if (td) td.classList.add('cursor');
    }
    function select(tds, additive) {
        if (!additive) clearSelection();
        tds.forEach(function (td) { selected.add(td); td.classList.add('sel'); });
        updateSelectionInfo();
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
        var html = '';
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
    // The grid scrolls inside its own box (its header row / name column are sticky there), so the box is sized to
    // end at the bottom of the screen: scrolling then moves the rows, not the page, and the header stays visible.
    // Desktop: from where the box starts. Phone (toolbar not sticky): a full screen once the toolbar is scrolled away.
    function fitGridHeight() {
        var sc = $id('sched-scroll');
        if (!sc) return;
        var h = window.innerWidth < 768
            ? window.innerHeight - 16
            : window.innerHeight - (sc.getBoundingClientRect().top + window.pageYOffset) - 16;
        sc.style.maxHeight = Math.max(320, Math.floor(h)) + 'px';
    }

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
        updateSelectionInfo();
        buildDivisionFilter();
        applyFilters();
        updateWeekNav();
        initShiftDataTable();
        refreshStatus();
        fitGridHeight();

        try {
            if (localStorage.getItem('sched.shiftMgmtOpen') === '1') $('#shift-mgmt-body').addClass('show');
        } catch (e) {}
        $('#shift-mgmt-body').off('shown.bs.collapse hidden.bs.collapse')
            .on('shown.bs.collapse', function () { try { localStorage.setItem('sched.shiftMgmtOpen', '1'); } catch (e) {} })
            .on('hidden.bs.collapse', function () { try { localStorage.setItem('sched.shiftMgmtOpen', '0'); } catch (e) {} });
    }

    function updateWeekNav() {
        var ws = weekStart(), we = addDays(ws, 6);
        var opt = { day: 'numeric', month: 'short', year: 'numeric' };
        $id('week-range').textContent = parseYmd(ws).toLocaleDateString('id-ID', opt) + ' – ' + parseYmd(we).toLocaleDateString('id-ID', opt);
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

    // Keep the user's place when the table is reloaded (other week, shift change): scroll and collapsed divisions
    function captureView() {
        var sc = $id('sched-scroll');
        return {
            top: sc ? sc.scrollTop : 0,
            left: sc ? sc.scrollLeft : 0,
            pageY: window.pageYOffset,
            collapsed: Array.from(document.querySelectorAll('#sched-table tr.sched-division.collapsed'))
                .map(function (tr) { return tr.getAttribute('data-division'); })
        };
    }
    function restoreView(v) {
        if (v.collapsed.length) {
            document.querySelectorAll('#sched-table tr.sched-division').forEach(function (tr) {
                if (v.collapsed.indexOf(tr.getAttribute('data-division')) !== -1) tr.classList.add('collapsed');
            });
            applyFilters();
        }
        var sc = $id('sched-scroll');
        if (sc) { sc.scrollTop = v.top; sc.scrollLeft = v.left; }
        window.scrollTo(window.pageXOffset, v.pageY);
    }

    var loadSeq = 0; // only the latest load may replace the table
    function loadWeek(startDate, skipConfirm) {
        return (skipConfirm ? Promise.resolve(true) : confirmDiscard()).then(function (ok) {
            if (!ok) return;
            var seq = ++loadSeq;
            showLoading(true);
            return fetch(URLS.index + '?start_date=' + encodeURIComponent(startDate), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { if (!res.ok) throw new Error(); return res.text(); })
                .then(function (html) {
                    if (seq !== loadSeq) return;
                    var view = captureView();
                    $id('jadwal-wrapper').innerHTML = html;
                    initTable();
                    restoreView(view);
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
            body: JSON.stringify({ schedule: payload, loaded_at: ($id('loaded-at') || {}).value || '' })
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
                if (data.loaded_at && $id('loaded-at')) $id('loaded-at').value = data.loaded_at;
                showSaveResult(dirty.length, data.ganti_libur || []);
                // Ganti libur balance changed: refresh the Cuti / Ganti Libur columns (scroll position is kept)
                if ((data.ganti_libur || []).length) loadWeek(weekStart(), true);
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
                if (g.added.length) change += '<div class="text-success">Hari masuk: ' + esc(g.added.join(', ')) + '</div>';
                if (g.removed.length) change += '<div class="text-danger">Hari masuk dihapus: ' + esc(g.removed.join(', ')) + '</div>';
                if (g.used.length) change += '<div class="text-danger">Ganti libur: ' + esc(g.used.join(', ')) + '</div>';
                if (g.refunded.length) change += '<div class="text-success">Ganti libur dibatalkan: ' + esc(g.refunded.join(', ')) + '</div>';
                return '<tr><td class="text-left">' + esc(g.nama) + '</td><td class="text-left">' + change + '</td><td class="text-center font-weight-bold">' + g.saldo + '</td></tr>';
            }).join('');
            html += '<div class="mt-3 mb-1 font-weight-bold text-left">Perubahan Ganti Libur</div>' +
                '<table class="table table-sm table-bordered mb-0" style="font-size:13px">' +
                '<thead class="thead-light"><tr><th class="text-left">Karyawan</th><th class="text-left">Perubahan</th><th class="text-center">Saldo bisa dipakai</th></tr></thead>' +
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
                                (data.ganti_libur_added ? '. ' + data.ganti_libur_added + ' hari masuk Minggu/libur nasional baru (dihitung ke ganti libur setelah dikerjakan).' : ''));
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
                    : '<span class="text-muted font-italic">Tanpa tanggal masuk (data lama)</span>';
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
        modal.find('#shift-min-staff').val(shift.min && shift.min !== '0' ? shift.min : '');
        modal.modal('show');
    }

    function reloadAfterShiftChange() {
        // Shift list changed: reload table but keep unsaved edits warning
        return loadWeek(weekStart());
    }

    // ---------- events ----------
    // Collapsible shift palette: choice remembered per browser; collapsed by default on phones
    function setPaletteCollapsed(collapsed, remember) {
        var row = $id('palette-row');
        if (!row) return;
        row.classList.toggle('collapsed', collapsed);
        $id('palette-toggle').setAttribute('aria-expanded', String(!collapsed));
        if (remember) {
            try { localStorage.setItem('sched-palette-collapsed', collapsed ? '1' : '0'); } catch (e) {}
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTable();

        var stored = null;
        try { stored = localStorage.getItem('sched-palette-collapsed'); } catch (e) {}
        setPaletteCollapsed(stored !== null ? stored === '1' : window.innerWidth < 768, false);
        $id('palette-toggle').addEventListener('click', function () {
            setPaletteCollapsed(!$id('palette-row').classList.contains('collapsed'), true);
            fitGridHeight();
        });
        // Toolbar height changes (window size, help panel) move where the grid starts
        var fitTimer;
        window.addEventListener('resize', function () { clearTimeout(fitTimer); fitTimer = setTimeout(fitGridHeight, 100); });
        $('#sched-help').on('shown.bs.collapse hidden.bs.collapse', fitGridHeight);
        fitGridHeight();

        var wrapper = $id('jadwal-wrapper');

        // Cell selection (mouse)
        wrapper.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            var td = e.target.closest('td.sc');
            if (!td) return;
            e.preventDefault();
            closePicker();
            if (e.ctrlKey || e.metaKey) {
                if (selected.has(td)) { selected.delete(td); td.classList.remove('sel'); updateSelectionInfo(); }
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
            var jatahTd = e.target.closest('td.jatah-btn');
            if (jatahTd) {
                showRiwayatJatah(jatahTd.getAttribute('data-emp'), jatahTd.getAttribute('data-jenis'), jatahTd.getAttribute('data-nama'));
                return;
            }
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
                    color: edit.getAttribute('data-shift-color') || '#007bff',
                    min: edit.getAttribute('data-shift-min') || ''
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
                            showAlert('success', 'Shift berhasil dihapus' + (data.ganti_libur_removed ? '. ' + data.ganti_libur_removed + ' hari masuk Minggu/libur nasional ikut terhapus.' : ''));
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
        if (CAN_MANAGE_JATAH) {
            $id('leave-capacity-btn').addEventListener('click', function (e) { e.preventDefault(); leaveCapacity(); });
            $id('reset-annual-btn').addEventListener('click', function (e) { e.preventDefault(); resetAnnual(); });
            $id('pasangkan-all-btn').addEventListener('click', function (e) { e.preventDefault(); pasangkanSemua(); });
            $id('libur-nasional-btn').addEventListener('click', function (e) { e.preventDefault(); liburNasional(); });
            $id('audit-log-btn').addEventListener('click', function (e) { e.preventDefault(); showAuditLog(); });
        }
        $id('open-shift-mgmt').addEventListener('click', function (e) {
            e.preventDefault();
            $('#shift-mgmt-body').collapse('show');
            var card = $id('shift-mgmt-toggle');
            if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        $id('rekap-libur-btn').addEventListener('click', function (e) {
            e.preventDefault();
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
                color: $id('shift-color').value,
                min_staff: parseInt($id('shift-min-staff').value, 10) || 0
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

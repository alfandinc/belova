@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Dokter')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
{{-- In the content section: the HRD layout has no @stack('styles') / @yield('styles') --}}
<link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
<style>
    /* Horizontal scroll with Nama pinned left and Aksi pinned right (FixedColumns), as on Data Karyawan */
    :root, :root.theme-light {
        --dok-fixed-bg: #ffffff;
        --dok-fixed-bg-alt: #f7f8fc;
        --dok-fixed-header-bg: #eef3fb;
        --dok-fixed-text: #212529;
        --dok-fixed-border: rgba(0, 0, 0, 0.08);
        --dok-header-text: #3d4c63;
    }
    :root.theme-dark {
        --dok-fixed-bg: #2a3042;
        --dok-fixed-bg-alt: #3a4058;
        --dok-fixed-header-bg: #2f6df6;
        --dok-fixed-text: #dfe7ff;
        --dok-fixed-border: rgba(255, 255, 255, 0.08);
        --dok-header-text: #ffffff;
    }
    .page-wrapper, .page-content, .container-fluid, .card, .card-body { min-width: 0; }
    #dokter-table { width: 100% !important; }
    #dokter-table th, #dokter-table td { vertical-align: middle; white-space: nowrap; }
    #dokter-table td.col-klinik { white-space: normal; min-width: 200px; }
    #dokter-table_wrapper { width: 100%; }
    #dokter-table_wrapper .dataTables_scrollBody { overflow-x: auto !important; }
    #dokter-table_wrapper .dataTables_scroll,
    #dokter-table_wrapper .dataTables_scrollHead,
    #dokter-table_wrapper .dataTables_scrollBody { width: 100% !important; }
    #dokter-table_wrapper .dtfc-fixed-left,
    #dokter-table_wrapper .dtfc-fixed-right { color: var(--dok-fixed-text) !important; box-shadow: none !important; }
    #dokter-table_wrapper table.dataTable thead .dtfc-fixed-left,
    #dokter-table_wrapper table.dataTable thead .dtfc-fixed-right {
        background-color: var(--dok-fixed-header-bg) !important;
        color: var(--dok-header-text) !important;
        border-color: var(--dok-fixed-border) !important;
    }
    #dokter-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-left,
    #dokter-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-right {
        background-color: var(--dok-fixed-bg) !important;
        border-color: var(--dok-fixed-border) !important;
    }
    #dokter-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-left,
    #dokter-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-right {
        background-color: var(--dok-fixed-bg-alt) !important;
        border-color: var(--dok-fixed-border) !important;
    }
    #dokter-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-left,
    #dokter-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-right { filter: brightness(1.03); }
    .stat-colon { font-weight: 400; margin: 0 10px; color: inherit; }
    .badge-info { background-color: #17a2b8; color: #fff; }
    .badge-warning { color: #212529; }
    .quick-chip.active { box-shadow: 0 0 0 2px rgba(0, 0, 0, .25); }
    .quick-chip[disabled] { opacity: .45; }
    .photo-zoom { cursor: zoom-in; transition: transform .15s; }
    .photo-zoom:hover { transform: scale(1.08); }
    .photo-viewer-img { max-width: 100%; max-height: 70vh; border-radius: 8px; object-fit: contain; }
    .dok-avatar { width: 34px; height: 34px; min-width: 34px; border-radius: 50%; object-fit: cover; margin-right: 8px; flex: none; }
    .dok-avatar-initials { display: inline-flex; align-items: center; justify-content: center; background: #e9ecef; color: #6c757d; font-size: 12px; font-weight: 700; }
    .izin { line-height: 1.4; }
    .izin-sub { font-size: 12px; color: #8a94a6; }
    .blink-warning { animation: blink-animation 1s linear infinite; }
    @keyframes blink-animation { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
    .dok-ttd { max-width: 180px; max-height: 90px; border: 1px dashed #d5dae3; border-radius: 6px; padding: 4px; background: #fff; }
    /* Tambah / edit dokter: footer stays visible, the body scrolls */
    #dokterFormModal .df-body { max-height: calc(100vh - 220px); overflow-y: auto; background: #f6f7fb; }
    #dokterFormModal .df-tabs .nav-link { white-space: nowrap; }
    #dokterFormModal .df-tab-badge .badge { font-size: 10px; vertical-align: top; }
    #dokterFormModal .df-block { background: #fff; border: 1px solid #e6e9f0; border-radius: 8px; margin-bottom: 14px; }
    #dokterFormModal .df-block-head { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-bottom: 1px solid #eef0f4; }
    #dokterFormModal .df-block-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; flex: none; border-radius: 8px; background: #eef3ff; color: #4e73df; }
    #dokterFormModal .df-block-title { font-weight: 700; font-size: 14px; color: #2d3748; line-height: 1.2; }
    #dokterFormModal .df-block-desc { font-size: 12px; color: #8a94a6; }
    #dokterFormModal .df-block-body { padding: 14px 16px 2px; margin: 0; }
    #dokterFormModal label { text-transform: none; letter-spacing: normal; font-weight: 600; font-size: 13px; color: #4a5568; margin-bottom: 5px; }
    #dokterFormModal .custom-control-label { font-weight: 500; }
    #dokterFormModal .form-group { margin-bottom: 14px; }
    #dokterFormModal .form-text { font-size: 12px; margin-top: 4px; }
    #dokterFormModal .df-preview { width: 44px; height: 44px; flex: none; object-fit: cover; border-radius: 50%; border: 1px solid #e3e6f0; }
    #dokterFormModal .df-preview-ttd { width: 70px; border-radius: 4px; object-fit: contain; background: #fff; }
    #dokterFormModal .df-klinik-row { padding: 4px 8px; border-radius: 6px; }
    #dokterFormModal .df-klinik-row + .df-klinik-row { border-top: 1px solid #f0f2f6; }
    #dokterFormModal .df-klinik-row.is-checked { background: #f5f8ff; }
    @media (max-width: 767px) {
        #dokter-table { min-width: 800px; }
    }
</style>
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-start">
            <div>
                <h3 class="mb-0 font-weight-bold">Data Dokter</h3>
                <div class="text-muted small">Kelola data dokter, izin praktik (SIP / STR), dan klinik tempat praktik.</div>
                <div class="mt-3">
                    <div class="d-flex flex-wrap align-items-center" style="gap:12px;">
                        <div class="mr-4 mb-1"><span class="badge badge-secondary">Total</span><span class="stat-colon">:</span><strong id="stat-total">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-success">Tetap</span><span class="stat-colon">:</span><strong id="stat-tetap">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-warning">Kontrak</span><span class="stat-colon">:</span><strong id="stat-kontrak">0</strong></div>
                    </div>
                </div>
            </div>
            <div class="mt-2 mt-sm-0 text-nowrap">
                <a href="#" class="btn btn-outline-success" id="btn-export" title="Export daftar sesuai filter ke Excel">
                    <i class="fas fa-file-excel mr-1"></i> Export
                </a>
                @if($canManage)
                <button type="button" class="btn btn-primary" id="btn-add-dokter">
                    <i class="fas fa-plus mr-1"></i> Tambah Dokter
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Perlu tindak lanjut: click a chip to show only those dokters (regardless of the filters) --}}
    <div id="quick-chips" class="d-flex flex-wrap align-items-center mb-2" style="gap:6px;">
        <span class="small text-muted mr-1">Perlu tindak lanjut:</span>
        <button type="button" class="btn btn-sm quick-chip" data-quick="sip_lewat" data-color="danger"><i class="fas fa-exclamation-circle mr-1"></i>SIP sudah lewat <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="sip_segera" data-color="warning"><i class="fas fa-hourglass-half mr-1"></i>SIP habis ≤ 30 hari <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="str_lewat" data-color="danger"><i class="fas fa-exclamation-circle mr-1"></i>STR sudah lewat <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="str_segera" data-color="warning"><i class="fas fa-hourglass-half mr-1"></i>STR habis ≤ 30 hari <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="tanpa_tanggal" data-color="secondary"><i class="fas fa-calendar-times mr-1"></i>Tanggal berlaku kosong <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="tanpa_ttd" data-color="secondary"><i class="fas fa-signature mr-1"></i>Belum ada TTD <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm btn-link px-1" id="quick-reset" style="display:none;"><i class="fas fa-times mr-1"></i>Tampilkan semua</button>
    </div>

    <!-- Filter toolbar (moved next to the DataTables search box) -->
    <div id="dokterToolbarHolder" class="d-none">
        <select id="filter-aktif" class="form-control form-control-sm mr-2" style="width: 150px; max-width: 100%;">
            <option value="aktif" selected>Dokter Aktif</option>
            <option value="nonaktif">Nonaktif</option>
            <option value="semua">Semua</option>
        </select>
        <select id="filter-spesialisasi" class="form-control form-control-sm mr-2" style="width: 200px; max-width: 100%;">
            <option value="">-- Semua Spesialisasi --</option>
            @foreach($spesialisasis as $s)
                <option value="{{ $s->id }}">{{ $s->nama }}</option>
            @endforeach
        </select>
        <select id="filter-klinik" class="form-control form-control-sm mr-2" style="width: 200px; max-width: 100%;">
            <option value="">-- Semua Klinik --</option>
            @foreach($kliniks as $klinik)
                <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
            @endforeach
        </select>
        <select id="filter-status" class="form-control form-control-sm mr-2" style="width: 150px; max-width: 100%;">
            <option value="">-- Semua Status --</option>
            <option value="Tetap">Tetap</option>
            <option value="Kontrak">Kontrak</option>
        </select>
        <div class="dropdown mr-2">
            <button type="button" class="btn btn-sm btn-light border dropdown-toggle" data-toggle="dropdown" title="Pilih kolom yang ditampilkan"><i class="fas fa-columns"></i></button>
            <div class="dropdown-menu dropdown-menu-right p-2" id="column-toggle" style="min-width: 190px;"></div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            {{-- scrollX + FixedColumns: Nama pinned left, Aksi pinned right --}}
            <table id="dokter-table" class="table table-bordered table-striped table-hover w-100 mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Spesialisasi</th>
                        <th>Klinik</th>
                        <th>SIP</th>
                        <th>STR</th>
                        <th>No HP</th>
                        <th>NIK</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Dokter Detail Modal (filled from the table row) -->
<div class="modal fade" id="dokterDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-md mr-2"></i>Detail Dokter</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="alert alert-danger py-2 small" id="dokter-nonaktif" style="display:none;"></div>
                <div class="row">
                    <div class="col-md-3 text-center mb-4">
                        <div id="dokter-photo-container"></div>
                        <h5 class="mt-3" id="dokter-name">-</h5>
                        <p class="text-muted mb-0" id="dokter-spesialisasi">-</p>
                        <p id="dokter-status">-</p>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-12 mb-3"><h5 class="border-bottom pb-2">Data Pribadi</h5></div>
                            <div class="col-md-6 mb-3"><strong><i class="fas fa-id-card mr-1"></i>NIK:</strong><p class="text-muted mb-0" id="dokter-nik">-</p></div>
                            <div class="col-md-6 mb-3"><strong><i class="fas fa-phone mr-1"></i>No HP:</strong><p class="text-muted mb-0" id="dokter-no_hp">-</p></div>
                            <div class="col-md-6 mb-3"><strong><i class="fas fa-envelope mr-1"></i>Akun Login:</strong><p class="text-muted mb-0" id="dokter-email">-</p></div>
                            <div class="col-md-6 mb-3"><strong><i class="fas fa-home mr-1"></i>Alamat:</strong><p class="text-muted mb-0" id="dokter-alamat">-</p></div>

                            <div class="col-12 mt-2 mb-3"><h5 class="border-bottom pb-2">Izin Praktik</h5></div>
                            <div class="col-md-6 mb-3"><strong><i class="fas fa-id-badge mr-1"></i>SIP:</strong><div class="mt-1" id="dokter-sip">-</div></div>
                            <div class="col-md-6 mb-3"><strong><i class="fas fa-certificate mr-1"></i>STR:</strong><div class="mt-1" id="dokter-str">-</div></div>

                            <div class="col-12 mt-2 mb-3"><h5 class="border-bottom pb-2">Klinik Praktik</h5></div>
                            <div class="col-12 mb-3" id="dokter-kliniks">-</div>

                            <div class="col-12 mt-2 mb-3"><h5 class="border-bottom pb-2">Tanda Tangan</h5></div>
                            <div class="col-12 mb-3" id="dokter-ttd">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                @if($canManage)
                <button type="button" class="btn btn-primary" id="edit-dokter-btn"><i class="fas fa-edit mr-1"></i>Edit</button>
                @endif
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Tambah / Edit Dokter (form loaded from hrd.dokters.create / edit) -->
<div class="modal fade" id="dokterFormModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content"></div>
    </div>
</div>
@endsection


@section('scripts')
<script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
<script>
$(function() {
    var CAN_MANAGE = @json($canManage); // Ceo / Head Manager: view only
    var DOKTER_URL = '{{ url('/hrd/dokters') }}';
    var CSRF = '{{ csrf_token() }}';
    var STATUS_COLOR = { 'Tetap': 'success', 'Kontrak': 'warning' };
    var quickFilter = '';

    // Also quotes: values end up inside attributes (data-name, title)
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function statusBadge(status) {
        if (!status) return '<span class="text-muted">-</span>';
        return '<span class="badge badge-pill badge-' + (STATUS_COLOR[status] || 'secondary') + '">' + esc(status) + '</span>';
    }
    function initials(name) {
        return String(name || '?').replace(/^dr\.?\s*/i, '').split(/\s+/).filter(Boolean).slice(0, 2).map(function(w) { return w.charAt(0).toUpperCase(); }).join('');
    }
    // "0812..." -> WhatsApp link (62812...)
    function waLink(phone) {
        var digits = String(phone || '').replace(/\D/g, '');
        if (!digits) return '<span class="text-muted">-</span>';
        if (digits.charAt(0) === '0') digits = '62' + digits.slice(1);
        return '<a href="https://wa.me/' + digits + '" target="_blank" rel="noopener" class="text-nowrap" title="Chat WhatsApp"><i class="fab fa-whatsapp text-success mr-1"></i>' + esc(phone) + '</a>';
    }
    function ymdDate(value) { return value ? new Date(String(value).slice(0, 10) + 'T00:00:00') : null; }

    // Sisa masa berlaku: red when passed, orange within 30 days
    function sisaBerlaku(value) {
        var end = ymdDate(value);
        if (!end || isNaN(end.getTime())) return { text: 'Tanggal berlaku belum diisi', cls: 'text-muted', icon: '' };
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var days = Math.round((end - today) / 86400000);
        var date = end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        if (days < 0) return { text: date + ' · lewat ' + (-days) + ' hari', cls: 'text-danger', icon: '<i class="fas fa-exclamation-triangle text-danger blink-warning ml-1" title="Sudah berakhir"></i>' };
        if (days === 0) return { text: date + ' · berakhir hari ini', cls: 'text-danger', icon: '<i class="fas fa-exclamation-triangle text-danger blink-warning ml-1"></i>' };
        var years = Math.floor(days / 365), months = Math.floor((days % 365) / 30), rest = (days % 365) % 30, parts = [];
        if (years) parts.push(years + ' thn');
        if (months) parts.push(months + ' bln');
        if (rest || !parts.length) parts.push(rest + ' hari');
        return {
            text: date + ' · sisa ' + parts.join(' '),
            cls: days <= 30 ? 'text-warning font-weight-bold' : '',
            icon: days <= 30 ? '<i class="fas fa-exclamation-triangle text-warning blink-warning ml-1" title="Segera berakhir"></i>' : ''
        };
    }
    function izinHtml(number, due) {
        var s = sisaBerlaku(due);
        return '<div class="izin">' + (number ? esc(number) : '<span class="text-muted">-</span>') + s.icon +
               '<div class="izin-sub ' + s.cls + '">' + esc(s.text) + '</div></div>';
    }
    function nonaktifText(row) {
        var date = ymdDate(row.nonaktif_tanggal);
        return 'Nonaktif' + (date ? ' sejak ' + date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '') +
               (row.nonaktif_keterangan ? ' · ' + row.nonaktif_keterangan : '');
    }
    function klinikHtml(list) {
        if (!list || !list.length) return '<span class="text-muted">-</span>';
        return list.map(function(k) {
            var main = k.utama && list.length > 1;
            var title = k.nama + (main ? ' (utama)' : '') + (k.spesialisasi ? ' · ' + k.spesialisasi : '');
            return '<span class="badge ' + (main ? 'badge-primary' : 'badge-light border') + ' mr-1 mb-1" data-toggle="tooltip" title="' + esc(title) + '">' +
                   esc(k.nama) + (k.spesialisasi ? ' <i class="fas fa-stethoscope ml-1"></i>' : '') + '</span>';
        }).join('');
    }

    $('body').tooltip({ selector: '[data-toggle="tooltip"]', container: 'body' });

    function filterParams() {
        return {
            aktif: $('#filter-aktif').val(),
            spesialisasi_id: $('#filter-spesialisasi').val(),
            klinik_id: $('#filter-klinik').val(),
            status: $('#filter-status').val(),
            quick: quickFilter
        };
    }

    // Filters and visible columns are remembered per browser
    var FILTER_IDS = ['filter-aktif', 'filter-spesialisasi', 'filter-klinik', 'filter-status'];
    var STORE_KEY = 'hrd-dokter-list';
    function loadPrefs() {
        try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; } catch (e) { return {}; }
    }
    function savePrefs(patch) {
        try { localStorage.setItem(STORE_KEY, JSON.stringify($.extend(loadPrefs(), patch))); } catch (e) {}
    }
    var prefs = loadPrefs();
    $.each(prefs.filters || {}, function(id, value) {
        var $el = $('#' + id);
        if ($el.length && $el.find('option[value="' + value + '"]').length) $el.val(value);
    });

    var table = $('#dokter-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: false, // horizontal scroll instead of hiding columns
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        fixedColumns: { left: 1, right: 1 }, // Nama, Aksi
        dom: '<"top"fl>rt<"bottom"ip><"clear">',
        pageLength: -1,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
        order: [[0, 'asc']], // nama; SIP / STR urgency is covered by the chips
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ entri',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 entri',
            infoFiltered: '(disaring dari _MAX_ entri keseluruhan)',
            paginate: { previous: '<i class="fas fa-chevron-left"></i>', next: '<i class="fas fa-chevron-right"></i>' },
            emptyTable: 'Tidak ada data yang tersedia'
        },
        ajax: {
            url: "{{ route('hrd.dokters.index') }}",
            data: function(d) { $.extend(d, filterParams()); },
            dataSrc: function(json) {
                renderSummary(json.summary || {});
                return json.data;
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat data. Silakan coba lagi.' });
            }
        },
        columns: [
            {
                data: 'nama',
                name: 'nama',
                title: 'Nama',
                defaultContent: '-',
                render: function(data, type, row) {
                    var noTtd = row.ttd_url ? '' : ' <i class="fas fa-signature text-muted small" data-toggle="tooltip" title="Belum ada tanda tangan"></i>';
                    if (!row.is_active) noTtd += ' <span class="badge badge-danger" data-toggle="tooltip" title="' + esc(nonaktifText(row)) + '">Nonaktif</span>';
                    var avatar = row.photo_url
                        ? '<img src="' + esc(row.photo_url) + '" alt="" class="dok-avatar photo-zoom" width="34" height="34" loading="lazy" data-name="' + esc(data) + '" title="Lihat foto">'
                        : '<span class="dok-avatar dok-avatar-initials">' + esc(initials(data)) + '</span>';
                    return '<div class="d-flex align-items-center">' + avatar + '<div>' +
                           '<div class="font-weight-bold"><a href="#" class="show-dokter text-dark" data-id="' + row.id + '">' + esc(data || '-') + '</a>' + noTtd + '</div>' +
                           '<div class="text-muted small">' + esc(row.email || '-') + '</div></div></div>';
                }
            },
            { data: 'spesialisasi', name: 'spesialisasi', title: 'Spesialisasi', defaultContent: '-' },
            {
                data: 'klinik_daftar',
                name: 'klinik_daftar',
                title: 'Klinik',
                className: 'col-klinik',
                orderable: false,
                searchable: false,
                render: function(data) { return klinikHtml(data); }
            },
            {
                data: 'sip',
                name: 'erm_dokters.sip',
                title: 'SIP',
                defaultContent: '-',
                render: function(data, type, row) { return izinHtml(data, row.due_date_sip); }
            },
            {
                data: 'str',
                name: 'erm_dokters.str',
                title: 'STR',
                defaultContent: '-',
                render: function(data, type, row) { return izinHtml(data, row.due_date_str); }
            },
            {
                data: 'no_hp',
                name: 'erm_dokters.no_hp',
                title: 'No HP',
                defaultContent: '-',
                orderable: false,
                render: function(data) { return waLink(data); }
            },
            { data: 'nik', name: 'erm_dokters.nik', title: 'NIK', defaultContent: '-', visible: false },
            {
                data: 'status',
                name: 'erm_dokters.status',
                title: 'Status',
                defaultContent: '-',
                searchable: false,
                render: function(data) { return statusBadge(data); }
            },
            {
                data: null,
                name: 'aksi',
                title: 'Aksi',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (!CAN_MANAGE) {
                        return '<button type="button" class="btn btn-sm btn-outline-primary show-dokter" data-id="' + row.id + '"><i class="fas fa-eye mr-1"></i>Detail</button>';
                    }
                    var toggle = row.is_active
                        ? '<button type="button" class="btn btn-sm btn-outline-warning toggle-dokter" data-id="' + row.id + '" data-name="' + esc(row.nama) + '" data-aktif="0" title="Nonaktifkan (dokter berhenti praktik)"><i class="fas fa-user-slash"></i></button>'
                        : '<button type="button" class="btn btn-sm btn-outline-success toggle-dokter" data-id="' + row.id + '" data-name="' + esc(row.nama) + '" data-aktif="1" title="Aktifkan kembali"><i class="fas fa-user-check"></i></button>';
                    return '<div class="btn-group">' +
                        '<button type="button" class="btn btn-sm btn-primary open-dokter-form" data-id="' + row.id + '" title="Edit data dokter"><i class="fas fa-edit mr-1"></i>Edit</button>' +
                        toggle +
                        '<button type="button" class="btn btn-sm btn-outline-danger delete-dokter" data-id="' + row.id + '" data-name="' + esc(row.nama) + '" title="Hapus dokter (hanya yang belum punya data)"><i class="fas fa-trash"></i></button>' +
                        '</div>';
                }
            }
        ]
    });

    // Column picker (remembered per browser)
    var TOGGLEABLE = [1, 2, 3, 4, 5, 6]; // Nama, Status and Aksi always visible
    $.each(prefs.columns || {}, function(index, visible) {
        if (TOGGLEABLE.indexOf(+index) !== -1) table.column(+index).visible(!!visible, false);
    });
    table.columns.adjust();
    $('#column-toggle').html(TOGGLEABLE.map(function(i) {
        var title = table.settings()[0].aoColumns[i].sTitle;
        return '<div class="custom-control custom-checkbox">' +
            '<input type="checkbox" class="custom-control-input col-toggle" id="col-toggle-' + i + '" data-col="' + i + '"' + (table.column(i).visible() ? ' checked' : '') + '>' +
            '<label class="custom-control-label" for="col-toggle-' + i + '">' + esc(title) + '</label></div>';
    }).join('')).on('click', function(e) { e.stopPropagation(); });
    $(document).on('change', '.col-toggle', function() {
        var i = $(this).data('col'), columns = {};
        table.column(i).visible(this.checked);
        TOGGLEABLE.forEach(function(c) { columns[c] = table.column(c).visible(); });
        savePrefs({ columns: columns });
    });

    // ---------- Stats & quick filters ----------
    // Totals come from the server: correct with paging too (within the filters, not the search box)
    function renderSummary(summary) {
        $('#stat-total').text(summary.total || 0);
        $('#stat-tetap').text(summary.tetap || 0);
        $('#stat-kontrak').text(summary.kontrak || 0);
        $('.quick-chip').each(function() {
            var $chip = $(this), key = $chip.data('quick'), n = summary[key] || 0, active = quickFilter === key;
            $chip.find('[data-count]').text(n);
            $chip.toggleClass('btn-' + $chip.data('color'), n > 0 || active)
                 .toggleClass('btn-outline-secondary', !n && !active)
                 .toggleClass('active', active)
                 .prop('disabled', !n && !active);
        });
        $('#quick-reset').toggle(!!quickFilter);
    }

    $('#quick-chips').on('click', '.quick-chip', function() {
        var key = $(this).data('quick');
        quickFilter = quickFilter === key ? '' : key;
        table.ajax.reload();
    });
    $('#quick-reset').on('click', function() { quickFilter = ''; table.ajax.reload(); });

    // Move custom filters next to the DataTables search box
    var $toolbarContent = $('#dokterToolbarHolder').children().detach();
    var $filter = $('#dokter-table_wrapper').find('.dataTables_filter');
    $filter.addClass('d-flex align-items-center justify-content-end flex-wrap');
    $filter.prepend($('<div class="d-flex align-items-center flex-wrap mb-2 mb-sm-0" id="dokterToolbar"></div>').append($toolbarContent));
    $filter.find('label').addClass('mb-0 ml-sm-3');
    $('#dokterToolbarHolder').remove();

    $('#' + FILTER_IDS.join(', #')).on('change', function() {
        var filters = {};
        FILTER_IDS.forEach(function(id) { filters[id] = $('#' + id).val(); });
        savePrefs({ filters: filters });
        table.ajax.reload();
    });

    // Click a photo (list or detail) to see it large
    $(document).on('click', '.photo-zoom', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var name = $(this).data('name') || '';
        Swal.fire({
            title: esc(name),
            html: '<img src="' + esc(this.src) + '" alt="' + esc(name) + '" class="photo-viewer-img">',
            showConfirmButton: false,
            showCloseButton: true,
            width: 'auto',
            padding: '1rem'
        });
    });

    // ---------- Detail dokter (modal, from the row data) ----------
    function rowById(id) {
        return table.rows().data().toArray().filter(function(r) { return String(r.id) === String(id); })[0];
    }

    $('#dokter-table').on('click', '.show-dokter', function(e) {
        e.preventDefault();
        var row = rowById($(this).data('id'));
        if (!row) return;
        $('#edit-dokter-btn').data('id', row.id);
        $('#dokter-nonaktif').toggle(!row.is_active).html(row.is_active ? '' : '<i class="fas fa-user-slash mr-1"></i>' + esc(nonaktifText(row)));
        $('#dokter-name').text(row.nama || '-');
        $('#dokter-spesialisasi').text(row.spesialisasi || '-');
        $('#dokter-status').html(statusBadge(row.status));
        $('#dokter-nik').text(row.nik || '-');
        $('#dokter-no_hp').html(waLink(row.no_hp));
        $('#dokter-email').text(row.email || '-');
        $('#dokter-alamat').text(row.alamat || '-');
        $('#dokter-sip').html(izinHtml(row.sip, row.due_date_sip));
        $('#dokter-str').html(izinHtml(row.str, row.due_date_str));
        var kliniks = row.klinik_daftar || [];
        $('#dokter-kliniks').html(kliniks.length
            ? '<ul class="mb-0 pl-3">' + kliniks.map(function(k) {
                return '<li>' + esc(k.nama) + (k.utama ? ' <span class="badge badge-primary">utama</span>' : '') +
                       ' <span class="text-muted small">· ' + esc(k.spesialisasi || 'spesialisasi default') + '</span></li>';
            }).join('') + '</ul>'
            : '<span class="text-muted">-</span>');
        $('#dokter-ttd').html(row.ttd_url
            ? '<img src="' + esc(row.ttd_url) + '" alt="TTD" class="dok-ttd photo-zoom" data-name="TTD ' + esc(row.nama) + '">'
            : '<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Belum ada tanda tangan</span>');
        $('#dokter-photo-container').html(row.photo_url
            ? '<img src="' + esc(row.photo_url) + '" alt="' + esc(row.nama) + '" class="img-thumbnail rounded-circle photo-zoom" data-name="' + esc(row.nama) + '" title="Lihat foto" style="width: 160px; height: 160px; object-fit: cover;">'
            : '<div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 160px; height: 160px;"><i class="fas fa-user-md fa-5x text-secondary"></i></div>');
        $('#dokterDetailModal').modal('show');
    });

    // From the detail modal: close it first (Bootstrap 4 does not stack modals)
    $('#edit-dokter-btn').on('click', function() {
        var id = $(this).data('id');
        $('#dokterDetailModal').one('hidden.bs.modal', function() { openDokterForm(id); }).modal('hide');
    });

    // ---------- Hapus dokter ----------
    $('#dokter-table').on('click', '.delete-dokter', function() {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            icon: 'warning',
            title: 'Hapus dokter?',
            html: '<b>' + esc(name) + '</b> akan dihapus dari daftar dokter.<br><small class="text-muted">Hanya untuk data yang salah input. Dokter yang sudah punya riwayat (kunjungan, resep, jadwal, ...) tidak dapat dihapus: gunakan Nonaktifkan.</small>',
            showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33', reverseButtons: true
        }).then(function(r) {
            if (!r.value) return;
            $.ajax({ url: DOKTER_URL + '/' + id, type: 'POST', data: { _method: 'DELETE', _token: CSRF }, dataType: 'json' })
                .done(function(res) {
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1800, timerProgressBar: true });
                })
                .fail(function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus dokter.' });
                });
        });
    });

    // ---------- Nonaktifkan / aktifkan kembali ----------
    $('#dokter-table').on('click', '.toggle-dokter', function() {
        var id = $(this).data('id'), name = $(this).data('name'), aktif = +$(this).data('aktif') === 1;
        var today = new Date(), ymd = today.getFullYear() + '-' + ('0' + (today.getMonth() + 1)).slice(-2) + '-' + ('0' + today.getDate()).slice(-2);
        Swal.fire(aktif ? {
            icon: 'question',
            title: 'Aktifkan kembali?',
            html: '<b>' + esc(name) + '</b> akan muncul lagi di pilihan dokter (pendaftaran kunjungan, jadwal, dll).',
            showCancelButton: true, confirmButtonText: 'Ya, aktifkan', cancelButtonText: 'Batal', reverseButtons: true
        } : {
            icon: 'warning',
            title: 'Nonaktifkan dokter?',
            html: '<div class="text-left"><p class="mb-2"><b>' + esc(name) + '</b> tidak akan muncul lagi di pilihan dokter untuk data baru. Riwayat kunjungan, resep, slip gaji tetap tersimpan.</p>' +
                  '<label class="small font-weight-bold mb-1" for="swal-nonaktif-tanggal">Berhenti praktik sejak</label>' +
                  '<input type="date" id="swal-nonaktif-tanggal" class="form-control mb-2" value="' + ymd + '">' +
                  '<label class="small font-weight-bold mb-1" for="swal-nonaktif-keterangan">Keterangan (opsional)</label>' +
                  '<input type="text" id="swal-nonaktif-keterangan" class="form-control" maxlength="255" placeholder="mis. kontrak selesai, pindah"></div>',
            showCancelButton: true, confirmButtonText: 'Ya, nonaktifkan', cancelButtonText: 'Batal', confirmButtonColor: '#d33', reverseButtons: true,
            preConfirm: function() {
                if (!$('#swal-nonaktif-tanggal').val()) {
                    Swal.showValidationMessage('Tanggal wajib diisi');
                    return false;
                }
                return { nonaktif_tanggal: $('#swal-nonaktif-tanggal').val(), nonaktif_keterangan: $('#swal-nonaktif-keterangan').val() };
            }
        }).then(function(r) {
            if (!r.value) return;
            var data = $.extend({ _token: CSRF, aktif: aktif ? 1 : 0 }, aktif ? {} : r.value);
            $.ajax({ url: DOKTER_URL + '/' + id + '/status', type: 'POST', data: data, dataType: 'json' })
                .done(function(res) {
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1800, timerProgressBar: true });
                })
                .fail(function(xhr) {
                    var j = xhr.responseJSON || {};
                    var first = j.errors ? j.errors[Object.keys(j.errors)[0]][0] : null;
                    Swal.fire({ icon: 'error', title: 'Gagal', text: first || j.message || 'Gagal mengubah status dokter.' });
                });
        });
    });

    $('#btn-export').on('click', function(e) {
        e.preventDefault();
        var params = filterParams();
        params.search = { value: table.search() };
        window.location.href = '{{ route('hrd.dokters.export') }}?' + $.param(params);
    });

    // ---------- Tambah / Edit dokter (modal) ----------
    var $formModal = $('#dokterFormModal');

    function openDokterForm(dokterId) {
        $formModal.find('.modal-content').html(
            '<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</h5>' +
            '<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' +
            '<div class="modal-body text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
        $formModal.modal('show');
        $.ajax({
            url: dokterId ? DOKTER_URL + '/' + dokterId + '/edit' : '{{ route('hrd.dokters.create') }}',
            type: 'GET',
            dataType: 'json'
        }).done(function(res) {
            $formModal.find('.modal-content').html(res.html);
            initDokterForm();
        }).fail(function(xhr) {
            $formModal.modal('hide');
            Swal.fire({ icon: 'error', title: 'Gagal', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat data. Silakan coba lagi.' });
        });
    }

    function initDokterForm() {
        var $form = $('#dokter-form');
        $form.find('.df-select2').select2({ width: '100%', dropdownParent: $formModal });

        // Klinik utama is always saved as a klinik praktik: keep its box checked and locked.
        // When the utama changes, a box that was only checked because it was the utama is unchecked again.
        function updateKlinik() {
            var utama = $('#df-klinik_id').val();
            $form.find('.df-klinik-row').each(function() {
                var $row = $(this), isUtama = String($row.data('klinik')) === String(utama);
                var $check = $row.find('.df-klinik-check');
                if (isUtama && !$check.is(':checked')) $check.prop('checked', true).data('forced', true);
                if (!isUtama && $check.data('forced')) $check.prop('checked', false).removeData('forced');
                $check.prop('disabled', isUtama);
                $row.find('.df-utama-badge').toggle(isUtama);
                $row.toggleClass('is-checked', $check.is(':checked'));
                $row.find('.df-klinik-spesialisasi').prop('disabled', !$check.is(':checked'));
            });
        }
        $('#df-klinik_id').on('change', updateKlinik);
        $form.on('change', '.df-klinik-check', updateKlinik);
        updateKlinik();

        // Masa berlaku SIP / STR next to the date
        function updateDueHints() {
            $form.find('.df-due').each(function() {
                var $hint = $(this).siblings('.df-due-hint');
                if (!this.value) { $hint.attr('class', 'form-text df-due-hint text-muted').text(''); return; }
                var s = sisaBerlaku(this.value);
                $hint.attr('class', 'form-text df-due-hint ' + (s.cls || 'text-muted')).text(s.text);
            });
        }
        $form.on('change input', '.df-due', updateDueHints);
        updateDueHints();

        // Sebelumnya / Lanjut between the tabs
        var $tabLinks = $form.find('.df-tabs a[data-toggle="tab"]');
        function tabIndex() { return $tabLinks.index($tabLinks.filter('.active')); }
        function updateTabNav() {
            var i = tabIndex();
            $('#df-prev').prop('disabled', i <= 0);
            $('#df-next').prop('disabled', i >= $tabLinks.length - 1);
        }
        $tabLinks.on('shown.bs.tab', function() {
            updateTabNav();
            $formModal.find('.df-body').scrollTop(0);
        });
        $('#df-prev').on('click', function() { $tabLinks.eq(tabIndex() - 1).tab('show'); });
        $('#df-next').on('click', function() { $tabLinks.eq(tabIndex() + 1).tab('show'); });
        updateTabNav();

        updateTabBadges();
        $form.on('change input', 'input, select, textarea', updateTabBadges);

        // Snapshot to detect unsaved changes when closing
        formCanClose = false;
        formSnapshot = formState();
    }

    function isFilled($el) {
        var v = $el.val();
        return Array.isArray(v) ? v.length > 0 : $.trim(v || '') !== '';
    }
    // Per-tab badges: missing required fields / errors
    function updateTabBadges() {
        var $form = $('#dokter-form');
        if (!$form.length) return;
        $form.find('.df-tabs a[data-toggle="tab"]').each(function() {
            var $pane = $($(this).attr('href'));
            var missing = $pane.find('[required]').filter(function() { return !isFilled($(this)); }).length;
            var errors = $pane.find('.is-invalid').length;
            $(this).find('.df-tab-badge').html(errors
                ? '<span class="badge badge-danger" title="Ada isian yang salah">!</span>'
                : (missing ? '<span class="badge badge-warning" title="Wajib diisi">' + missing + '</span>' : ''));
        });
    }

    function markInvalid($input) {
        $input.addClass('is-invalid');
        $input.next('.select2').find('.select2-selection').addClass('border-danger');
    }
    function clearInvalid($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.select2-selection.border-danger').removeClass('border-danger');
    }
    $(document).on('change input', '#dokter-form .is-invalid', function() {
        $(this).removeClass('is-invalid').next('.select2').find('.select2-selection').removeClass('border-danger');
        updateTabBadges();
    });

    // Unsaved changes: confirm before closing (X, Batal, Esc)
    var formSnapshot = null, formCanClose = true;
    function formState() {
        var $form = $('#dokter-form');
        return $form.length ? $form.serialize() + '|' + $form.find('input[type=file]').map(function() { return this.value; }).get().join(',') : null;
    }
    $(document).on('click', '#dokter-form .df-close', function() { $formModal.modal('hide'); });
    $formModal.on('hide.bs.modal', function(e) {
        if (e.target !== this || formCanClose || !$('#dokter-form').length || formState() === formSnapshot) return;
        e.preventDefault();
        Swal.fire({
            icon: 'warning', title: 'Tutup tanpa menyimpan?', text: 'Perubahan yang belum disimpan akan hilang.',
            showCancelButton: true, confirmButtonText: 'Ya, tutup', cancelButtonText: 'Kembali ke form', reverseButtons: true
        }).then(function(r) {
            if (r.value) { formCanClose = true; $formModal.modal('hide'); }
        });
    });

    // Ctrl+S = simpan
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S') && $formModal.hasClass('show') && $('#dokter-form').length) {
            e.preventDefault();
            $('#dokter-form').trigger('submit');
        }
    });

    // File inputs: show the file name, and a preview for images
    $(document).on('change', '#dokterFormModal .custom-file-input', function() {
        var file = this.files && this.files[0], $label = $(this).siblings('.custom-file-label');
        $label.text(file ? file.name : ($label.data('default') || 'Pilih file'));
        var preview = $(this).data('preview');
        if (file && preview) {
            var reader = new FileReader();
            reader.onload = function(ev) { $(preview).attr('src', ev.target.result).show(); };
            reader.readAsDataURL(file);
        }
    });

    // Hapus foto / TTD: no new file at the same time, preview hidden
    $(document).on('change', '#dokterFormModal .df-remove', function() {
        var $file = $($(this).data('file')), $preview = $($(this).data('preview'));
        if (this.checked) {
            $file.val('').siblings('.custom-file-label').text($file.siblings('.custom-file-label').data('default'));
        }
        $file.prop('disabled', this.checked);
        $preview.toggle(!this.checked && !!$preview.attr('src'));
    });

    $(document).on('submit', '#dokter-form', function(e) {
        e.preventDefault();
        var $form = $(this), $btn = $('#dokter-form-save'), $errors = $('#dokter-form-errors');
        if ($btn.prop('disabled')) return;
        clearInvalid($form);
        $errors.hide().empty();

        // Required fields first, without a round trip: list them and open the tab of the first one
        var missing = $form.find('[required]').filter(function() { return !isFilled($(this)); });
        if (missing.length) {
            var labels = missing.map(function() {
                markInvalid($(this));
                return $.trim($form.find('label[for="' + this.id + '"]').first().clone().children().remove().end().text()) || this.name;
            }).get();
            $errors.html('<i class="fas fa-exclamation-circle mr-1"></i>Wajib diisi: <b>' + labels.map(esc).join(', ') + '</b>').show();
            $form.find('a[href="#' + missing.first().closest('.tab-pane').attr('id') + '"]').tab('show');
            updateTabBadges();
            return;
        }

        var btnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(res) {
            table.ajax.reload(null, false);
            formCanClose = true; // saved: no "unsaved changes" question
            $formModal.modal('hide');
            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Data dokter disimpan', timer: 1800, timerProgressBar: true });
        }).fail(function(xhr) {
            var r = xhr.responseJSON || {};
            if (xhr.status === 422 && r.errors) {
                var firstPane = null;
                $errors.html('<ul class="mb-0 pl-3">' + $.map(r.errors, function(msgs, field) {
                    var name = field.replace(/\.\d+$/, '');
                    var $input = $form.find('[name="' + name + '"], [name="' + name + '[]"]').not('[type=hidden]').first();
                    markInvalid($input);
                    if (!firstPane && $input.length) firstPane = $input.closest('.tab-pane').attr('id');
                    return '<li>' + esc(msgs[0]) + '</li>';
                }).join('') + '</ul>').show();
                if (firstPane) $form.find('a[href="#' + firstPane + '"]').tab('show');
                $formModal.find('.df-body').scrollTop(0);
                updateTabBadges();
            } else {
                $errors.text(r.message || 'Gagal menyimpan data. Silakan coba lagi.').show();
            }
        }).always(function() {
            $btn.prop('disabled', false).html(btnHtml);
        });
    });

    $('#btn-add-dokter').on('click', function() { openDokterForm(null); });
    $('#dokter-table').on('click', '.open-dokter-form', function() { openDokterForm($(this).data('id')); });

    // Old links (/dokters/create, /dokters/{id}/edit) land here with a query to open the modal
    (function openFromQuery() {
        var params = new URLSearchParams(window.location.search);
        if (params.has('create') && CAN_MANAGE) openDokterForm(null);
        else if (params.get('edit') && CAN_MANAGE) openDokterForm(params.get('edit'));
        else return;
        if (window.history.replaceState) window.history.replaceState(null, '', window.location.pathname);
    })();
});
</script>
@endsection

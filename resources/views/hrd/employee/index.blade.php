@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
{{-- In the content section: the HRD layout has no @stack('styles') / @yield('styles') --}}
<link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
<style>
    /* Horizontal scroll with Nama pinned left and Aksi pinned right (FixedColumns), as on ERM Rawat Jalan */
    :root, :root.theme-light {
        --emp-fixed-bg: #ffffff;
        --emp-fixed-bg-alt: #f7f8fc;
        --emp-fixed-header-bg: #eef3fb;
        --emp-fixed-text: #212529;
        --emp-fixed-border: rgba(0, 0, 0, 0.08);
        --emp-header-text: #3d4c63;
    }
    :root.theme-dark {
        --emp-fixed-bg: #2a3042;
        --emp-fixed-bg-alt: #3a4058;
        --emp-fixed-header-bg: #2f6df6;
        --emp-fixed-text: #dfe7ff;
        --emp-fixed-border: rgba(255, 255, 255, 0.08);
        --emp-header-text: #ffffff;
    }
    .page-wrapper, .page-content, .container-fluid, .card, .card-body { min-width: 0; }
    #employees-table { width: 100% !important; }
    #employees-table th, #employees-table td { vertical-align: middle; white-space: nowrap; }
    #employees-table td.col-posisi { white-space: normal; min-width: 200px; }
    #employees-table_wrapper { width: 100%; }
    #employees-table_wrapper .dataTables_scrollBody { overflow-x: auto !important; }
    #employees-table_wrapper .dataTables_scroll,
    #employees-table_wrapper .dataTables_scrollHead,
    #employees-table_wrapper .dataTables_scrollBody { width: 100% !important; }
    #employees-table_wrapper .dtfc-fixed-left,
    #employees-table_wrapper .dtfc-fixed-right { color: var(--emp-fixed-text) !important; box-shadow: none !important; }
    #employees-table_wrapper table.dataTable thead .dtfc-fixed-left,
    #employees-table_wrapper table.dataTable thead .dtfc-fixed-right {
        background-color: var(--emp-fixed-header-bg) !important;
        color: var(--emp-header-text) !important;
        border-color: var(--emp-fixed-border) !important;
    }
    #employees-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-left,
    #employees-table_wrapper table.dataTable tbody tr:nth-of-type(odd) > .dtfc-fixed-right {
        background-color: var(--emp-fixed-bg) !important;
        border-color: var(--emp-fixed-border) !important;
    }
    #employees-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-left,
    #employees-table_wrapper table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-right {
        background-color: var(--emp-fixed-bg-alt) !important;
        border-color: var(--emp-fixed-border) !important;
    }
    #employees-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-left,
    #employees-table_wrapper table.dataTable tbody tr:hover > .dtfc-fixed-right { filter: brightness(1.03); }
    .stat-colon { font-weight: 400; margin: 0 10px; color: inherit; }
    .badge-info { background-color: #17a2b8; color: #fff; }
    .badge-warning { color: #212529; }
    .quick-chip.active { box-shadow: 0 0 0 2px rgba(0, 0, 0, .25); }
    .quick-chip[disabled] { opacity: .45; }
    .photo-zoom { cursor: zoom-in; transition: transform .15s; }
    .photo-zoom:hover { transform: scale(1.08); }
    .photo-viewer-img { max-width: 100%; max-height: 70vh; border-radius: 8px; object-fit: contain; }
    .emp-avatar { width: 34px; height: 34px; min-width: 34px; border-radius: 50%; object-fit: cover; margin-right: 8px; flex: none; }
    .emp-avatar-initials { display: inline-flex; align-items: center; justify-content: center; background: #e9ecef; color: #6c757d; font-size: 12px; font-weight: 700; }
    /* Tambah / edit karyawan: footer stays visible, the body scrolls */
    #employeeFormModal .ef-body { max-height: calc(100vh - 220px); overflow-y: auto; }
    #employeeFormModal .ef-tabs .nav-link { white-space: nowrap; }
    #employeeFormModal .ef-tab-badge .badge { font-size: 10px; vertical-align: top; }
    /* Simple form: plain-case labels (the theme uppercases them), light section titles, white fields */
    #employeeFormModal .ef-body { background: #f6f7fb; }
    /* One card per group: icon + title + what it is for, then at most two fields per row */
    #employeeFormModal .ef-block { background: #fff; border: 1px solid #e6e9f0; border-radius: 8px; margin-bottom: 14px; }
    #employeeFormModal .ef-block-head { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-bottom: 1px solid #eef0f4; }
    #employeeFormModal .ef-block-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; flex: none; border-radius: 8px; background: #eef3ff; color: #4e73df; }
    #employeeFormModal .ef-block-title { font-weight: 700; font-size: 14px; color: #2d3748; line-height: 1.2; }
    #employeeFormModal .ef-block-desc { font-size: 12px; color: #8a94a6; }
    #employeeFormModal .ef-block-body { padding: 14px 16px 2px; margin: 0; }
    #employeeFormModal label { text-transform: none; letter-spacing: normal; font-weight: 600; font-size: 13px; color: #4a5568; margin-bottom: 5px; }
    #employeeFormModal .custom-control-label { font-weight: 500; }
    #employeeFormModal .ef-sub { margin-top: 8px; padding: 8px 10px; background: #f8f9fc; border-radius: 6px; }
    #employeeFormModal .ef-sublabel { font-size: 12px; font-weight: 600; color: #6c757d; margin-bottom: 3px; }
    #employeeFormModal .form-group { margin-bottom: 14px; }
    #employeeFormModal .form-text { font-size: 12px; margin-top: 4px; }
    #employeeFormModal .ef-nonaktif { padding: 10px 6px 0; background: #fff5f5; border: 1px solid #f5c2c7; border-radius: 6px; }
    #employeeFormModal .ef-progress { background: #f8f9fc; border: 1px solid #edf0f5; border-radius: 6px; padding: 8px 10px; }
    /* Kontrak modal */
    #contractModal .ck-body { max-height: calc(100vh - 200px); overflow-y: auto; }
    .ck-employee { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 10px 12px; background: #f8f9fc; border-left: 4px solid #4e73df; border-radius: 4px; }
    .ck-current { padding: 12px; border: 1px solid #e3e6f0; border-radius: 6px; }
    .ck-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .ck-action { flex: 1 1 220px; display: flex; align-items: center; gap: 12px; text-align: left; padding: 10px 14px; background: #fff; border: 1px solid #cfe0ff; border-radius: 6px; color: #2c5cc5; cursor: pointer; transition: background .15s, box-shadow .15s; }
    .ck-action i { font-size: 22px; width: 26px; text-align: center; }
    .ck-action b, .ck-action small { display: block; }
    .ck-action small { color: #6c757d; }
    .ck-action:hover, .ck-action.active { background: #eef4ff; box-shadow: 0 0 0 2px #cfe0ff; }
    .ck-action-danger { border-color: #f5c2c7; color: #c82333; }
    .ck-action-danger:hover, .ck-action-danger.active { background: #fff5f5; box-shadow: 0 0 0 2px #f5c2c7; }
    .ck-form { padding: 14px; border: 1px solid #cfe0ff; border-radius: 6px; background: #fbfcff; }
    .ck-form-danger { border-color: #f5c2c7; background: #fffafa; }
    .ck-preview { padding: 10px 12px; border-radius: 6px; background: #eef4ff; }
    .ck-preview-period { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .ck-preview-period small { display: block; color: #6c757d; font-size: 11px; text-transform: uppercase; }
    .ck-preview-period i { color: #4e73df; font-size: 20px; }
    .ck-effects { padding: 8px 12px; border-radius: 6px; background: #f1f8f3; color: #2d6a3e; }
    .ck-effects-danger { background: #fdf1f2; color: #8a2a33; }
    .ck-timeline { border-left: 2px solid #e3e6f0; margin-left: 6px; padding-left: 14px; }
    .ck-item { position: relative; padding: 8px 10px; margin-bottom: 8px; border: 1px solid #edf0f5; border-radius: 6px; background: #fff; }
    .ck-item::before { content: ''; position: absolute; left: -21px; top: 13px; width: 12px; height: 12px; border-radius: 50%; background: #adb5bd; border: 2px solid #fff; }
    .ck-item-success::before { background: #28a745; }
    .ck-item-info::before { background: #17a2b8; }
    .ck-item-danger::before { background: #dc3545; }
    .ck-item-active { border-color: #b7e4c7; background: #f6fcf8; }
    /* Tempat, tgl lahir */
    .ttl { line-height: 1.4; white-space: nowrap; }
    .ttl-age, .ttl-sub { color: #8a94a6; }
    .ttl-sub { font-size: 12px; }
    .ttl-note { font-size: 12px; color: #8a94a6; margin-top: 2px; }
    .ttl-note-soon { color: #c47f00; font-weight: 600; }
    .ttl-note-today { display: inline-block; color: #fff; background: #e83e8c; font-weight: 600; border-radius: 4px; padding: 0 6px; }
    .employee-logs-table { font-size: 12px; }
    .employee-logs-table td { vertical-align: top; }
    @media (max-width: 767px) {
        #employees-table { min-width: 800px; }
    }
</style>
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-start">
            <div>
                <h3 class="mb-0 font-weight-bold">Data Karyawan</h3>
                <div class="text-muted small">Kelola data karyawan, filter berdasarkan divisi, perusahaan, dan status aktif.</div>
                <div id="employee-stats" class="mt-3">
                    <div class="d-flex flex-wrap align-items-center" style="gap:12px;">
                        <div class="mr-4 mb-1"><span class="badge badge-warning">Kontrak</span><span class="stat-colon">:</span><strong id="stat-kontrak">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-success">Tetap</span><span class="stat-colon">:</span><strong id="stat-tetap">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-info">Freelance</span><span class="stat-colon">:</span><strong id="stat-freelance">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-secondary">Rata-rata Usia</span><span class="stat-colon">:</span><strong id="stat-usia">-</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-primary">Laki-laki</span><span class="stat-colon">:</span><strong id="stat-male">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge" style="background:#e83e8c;color:#fff;">Perempuan</span><span class="stat-colon">:</span><strong id="stat-female">0</strong></div>
                    </div>
                </div>
            </div>
            <div class="mt-2 mt-sm-0 text-nowrap">
                <a href="#" class="btn btn-outline-success" id="btn-export" title="Export daftar sesuai filter ke Excel">
                    <i class="fas fa-file-excel mr-1"></i> Export
                </a>
                @if($canManage)
                <button type="button" class="btn btn-primary" id="btn-add-employee">
                    <i class="fas fa-plus mr-1"></i> Tambah Karyawan
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Perlu tindak lanjut: click a chip to show only those employees (karyawan aktif, regardless of the filters) --}}
    <div id="quick-chips" class="d-flex flex-wrap align-items-center mb-2" style="gap:6px;">
        <span class="small text-muted mr-1">Perlu tindak lanjut:</span>
        <button type="button" class="btn btn-sm quick-chip" data-quick="kontrak_segera" data-color="warning"><i class="fas fa-hourglass-half mr-1"></i>Kontrak habis ≤ 30 hari <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="kontrak_lewat" data-color="danger"><i class="fas fa-exclamation-circle mr-1"></i>Kontrak sudah lewat <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="kontrak_kosong" data-color="danger"><i class="fas fa-file-contract mr-1"></i>Kontrak tanpa tanggal <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="belum_lengkap" data-color="secondary"><i class="fas fa-user-edit mr-1"></i>Data belum lengkap <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="tanpa_finger" data-color="secondary"><i class="fas fa-fingerprint mr-1"></i>Tanpa Finger ID <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="tanpa_akun" data-color="secondary"><i class="fas fa-user-lock mr-1"></i>Belum punya akun <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="baru" data-color="info"><i class="fas fa-user-plus mr-1"></i>Karyawan baru ≤ 3 bln <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm quick-chip" data-quick="ultah" data-color="info"><i class="fas fa-birthday-cake mr-1"></i>Ulang tahun bulan ini <span class="badge badge-light ml-1" data-count>0</span></button>
        <button type="button" class="btn btn-sm btn-link px-1" id="quick-reset" style="display:none;"><i class="fas fa-times mr-1"></i>Tampilkan semua</button>
    </div>

    <!-- Filter toolbar (moved next to the DataTables search box) -->
    <div id="employeeToolbarHolder" class="d-none">
        <select id="filter-division" class="form-control form-control-sm mr-2" style="width: 200px; max-width: 100%;">
            <option value="all">-- Semua Divisi --</option>
            @foreach($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
            @endforeach
        </select>
        <select id="filter-perusahaan" class="form-control form-control-sm mr-2" style="width: 220px; max-width: 100%;">
            <option value="all">-- Semua Perusahaan --</option>
            @foreach(\App\Models\HRD\Employee::PERUSAHAAN as $perusahaan)
                <option value="{{ $perusahaan }}">{{ $perusahaan }}</option>
            @endforeach
        </select>
        <select id="filter-position" class="form-control form-control-sm mr-2" style="width: 180px; max-width: 100%;">
            <option value="">-- Semua Posisi --</option>
            @foreach($positions as $position)
                <option value="{{ $position->id }}">{{ $position->name }}</option>
            @endforeach
        </select>
        <select id="filter-status" class="form-control form-control-sm mr-2" style="width: 170px; max-width: 100%;">
            <option value="active" selected>Semua Aktif</option>
            <option value="tetap">Tetap</option>
            <option value="kontrak">Kontrak</option>
            <option value="freelance">Freelance</option>
            <option value="inactive">Tidak Aktif</option>
            <option value="all">Semua (termasuk nonaktif)</option>
        </select>
        {{-- Turnover: only with status Tidak Aktif --}}
        <select id="filter-nonaktif-alasan" class="form-control form-control-sm mr-2 filter-nonaktif" style="width: 170px; max-width: 100%; display:none;">
            <option value="">-- Semua Alasan --</option>
            @foreach(\App\Models\HRD\Employee::NONAKTIF_ALASAN as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select id="filter-nonaktif-tahun" class="form-control form-control-sm mr-2 filter-nonaktif" style="width: 130px; max-width: 100%; display:none;">
            <option value="">-- Semua Tahun --</option>
            @for($year = now()->year; $year >= now()->year - 5; $year--)
                <option value="{{ $year }}">Keluar {{ $year }}</option>
            @endfor
        </select>
        <div class="dropdown mr-2">
            <button type="button" class="btn btn-sm btn-light border dropdown-toggle" data-toggle="dropdown" title="Pilih kolom yang ditampilkan"><i class="fas fa-columns"></i></button>
            <div class="dropdown-menu dropdown-menu-right p-2" id="column-toggle" style="min-width: 190px;"></div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            {{-- scrollX + FixedColumns: Nama pinned left, Aksi pinned right --}}
            <table id="employees-table" class="table table-bordered table-striped table-hover w-100 mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>No Induk</th>
                        <th>Tempat, Tgl Lahir</th>
                        <th>Posisi</th>
                        <th>Divisi</th>
                        <th>Perusahaan</th>
                        <th>Tanggal Masuk</th>
                        <th>No HP</th>
                        <th>Status</th>
                        <th>Sisa Kontrak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employee Detail Modal -->
<div class="modal fade" id="employeeDetailModal" tabindex="-1" role="dialog" aria-labelledby="employeeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="employeeDetailModalLabel"><i class="fas fa-user mr-2"></i>Detail Karyawan</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center mb-3 detail-spinner">
                    <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                </div>
                <div id="employeeDetailContent" style="display: none;">
                    <div class="alert alert-warning py-2 small" id="employee-belum-lengkap" style="display:none;"></div>
                    <div class="alert alert-danger py-2 small" id="employee-nonaktif" style="display:none;"></div>
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <div id="employee-photo-container"></div>
                            <h5 class="mt-3" id="employee-name">-</h5>
                            <p class="text-muted mb-0" id="employee-position">-</p>
                            <p class="text-muted" id="employee-division">-</p>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-12 mb-3"><h5 class="border-bottom pb-2">Data Pribadi</h5></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-id-card mr-1"></i>NIK:</strong><p class="text-muted" id="employee-nik">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-fingerprint mr-1"></i>No Induk:</strong><p class="text-muted" id="employee-no_induk">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-users mr-1"></i>Kategori Pegawai:</strong><p class="text-muted" id="employee-kategori_pegawai">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-map-marker-alt mr-1"></i>Tempat, Tanggal Lahir:</strong><div class="mt-1" id="employee-ttl">-</div></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-phone mr-1"></i>No HP:</strong><p class="text-muted" id="employee-no_hp">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-tint mr-1"></i>Gol. Darah:</strong><p class="text-muted" id="employee-gol_darah">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-user-shield mr-1"></i>Kontak Darurat:</strong><p class="text-muted" id="employee-darurat">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-envelope mr-1"></i>Email:</strong><p class="text-muted" id="employee-email">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fab fa-instagram mr-1"></i>Instagram:</strong><p class="text-muted" id="employee-instagram">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-home mr-1"></i>Alamat:</strong><p class="text-muted" id="employee-alamat">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-graduation-cap mr-1"></i>Pendidikan:</strong><p class="text-muted" id="employee-pendidikan">-</p></div>

                                <div class="col-12 mt-3 mb-3"><h5 class="border-bottom pb-2">Data Kepegawaian</h5></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-building mr-1"></i>Perusahaan:</strong><p class="text-muted" id="employee-perusahaan">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-user-check mr-1"></i>Status:</strong><p id="employee-status">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-calendar-alt mr-1"></i>Tanggal Masuk:</strong><div class="mt-1" id="employee-tanggal_masuk">-</div></div>
                                <div class="col-md-6 mb-3 kontrak-info" style="display: none;"><strong><i class="fas fa-calendar-times mr-1"></i>Kontrak Berakhir:</strong><p class="text-muted" id="employee-kontrak_berakhir">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-key mr-1"></i>Akun Login:</strong><p class="text-muted" id="employee-akun">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-fingerprint mr-1"></i>Finger ID:</strong><p class="text-muted" id="employee-finger_id">-</p></div>

                                <div class="col-12 mt-3 mb-3"><h5 class="border-bottom pb-2">Payroll</h5></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-file-invoice-dollar mr-1"></i>NPWP:</strong><p class="text-muted" id="employee-npwp">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-receipt mr-1"></i>Status Pajak (PTKP):</strong><p class="text-muted" id="employee-ptkp">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-notes-medical mr-1"></i>BPJS Kesehatan:</strong><p class="text-muted" id="employee-no_bpjs_kesehatan">-</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-hard-hat mr-1"></i>BPJS Ketenagakerjaan:</strong><p class="text-muted" id="employee-no_bpjs_ketenagakerjaan">-</p></div>
                                <div class="col-md-12 mb-3"><strong><i class="fas fa-university mr-1"></i>Rekening Gaji:</strong><p class="text-muted" id="employee-rekening">-</p></div>

                                <div class="col-12 mt-3 mb-3"><h5 class="border-bottom pb-2">Dokumen</h5></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-file-alt mr-1"></i>CV:</strong><p id="employee-doc_cv">Tidak ada dokumen</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-id-card-alt mr-1"></i>KTP:</strong><p id="employee-doc_ktp">Tidak ada dokumen</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-file-contract mr-1"></i>Kontrak (lama):</strong><p id="employee-doc_kontrak">Tidak ada dokumen</p></div>
                                <div class="col-md-6 mb-3"><strong><i class="fas fa-file-invoice mr-1"></i>Dokumen Pendukung:</strong><p id="employee-doc_pendukung">Tidak ada dokumen</p></div>

                                <div class="col-12 mt-3 mb-2 d-flex justify-content-between align-items-center border-bottom pb-2">
                                    <h5 class="mb-0">Riwayat Perubahan</h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-load-logs"><i class="fas fa-history mr-1"></i>Tampilkan</button>
                                </div>
                                <div class="col-12" id="employee-logs"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="contract-employee-btn">
                    <i class="fas fa-file-contract mr-1"></i>Kontrak
                </button>
                @if($canManage)
                <button type="button" class="btn btn-primary" id="edit-employee-btn">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                @endif
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Tambah / Edit Karyawan (form loaded from hrd.employee.create / edit) -->
<div class="modal fade" id="employeeFormModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content"></div>
    </div>
</div>

<!-- Kontrak karyawan (content loaded from hrd.employee.contracts.index) -->
<div class="modal fade" id="contractModal" tabindex="-1" role="dialog" aria-hidden="true">
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
    var EMPLOYEE_URL = '{{ url('/hrd/employee') }}';
    var STATUS_COLOR = { 'tetap': 'success', 'kontrak': 'warning', 'freelance': 'info', 'tidak aktif': 'danger' };
    var quickFilter = '';

    function esc(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }
    function statusBadge(status) {
        status = status || '-';
        return '<span class="badge badge-pill badge-' + (STATUS_COLOR[status] || 'secondary') + '">' + esc(status.charAt(0).toUpperCase() + status.slice(1)) + '</span>';
    }
    function fmtDate(value) {
        if (!value) return '-';
        var d = new Date(value);
        return isNaN(d.getTime()) ? '-' : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    $('body').tooltip({ selector: '[data-toggle="tooltip"]', container: 'body' });

    function filterParams() {
        var div = $('#filter-division').val(), per = $('#filter-perusahaan').val(), inactive = $('#filter-status').val() === 'inactive';
        return {
            division_id: div === 'all' ? '' : div,
            perusahaan: per === 'all' ? '' : per,
            position_id: $('#filter-position').val(),
            status_filter: $('#filter-status').val(),
            nonaktif_alasan: inactive ? $('#filter-nonaktif-alasan').val() : '',
            nonaktif_tahun: inactive ? $('#filter-nonaktif-tahun').val() : '',
            quick: quickFilter
        };
    }

    // Filters and visible columns are remembered per browser
    var FILTER_IDS = ['filter-division', 'filter-perusahaan', 'filter-position', 'filter-status', 'filter-nonaktif-alasan', 'filter-nonaktif-tahun'];
    var STORE_KEY = 'hrd-employee-list';
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
    function toggleNonaktifFilters() {
        $('.filter-nonaktif').toggle($('#filter-status').val() === 'inactive');
    }
    toggleNonaktifFilters();

    // "0812..." -> WhatsApp link (62812...)
    function waLink(phone) {
        var digits = String(phone || '').replace(/\D/g, '');
        if (!digits) return '-';
        if (digits.charAt(0) === '0') digits = '62' + digits.slice(1);
        return '<a href="https://wa.me/' + digits + '" target="_blank" rel="noopener" class="text-nowrap" title="Chat WhatsApp"><i class="fab fa-whatsapp text-success mr-1"></i>' + esc(phone) + '</a>';
    }
    // Age and days until the next birthday (0 = today); 29 Feb counts as 28 Feb in other years
    function birthdayInfo(value) {
        // Dates arrive as UTC timestamps (e.g. 3 Jul WIB = "07-02T17:00Z"): read them in local time
        var born = new Date(value), today = new Date();
        today.setHours(0, 0, 0, 0);
        var month = born.getMonth(), day = born.getDate();
        function on(year) {
            var last = new Date(year, month + 1, 0).getDate();
            return new Date(year, month, Math.min(day, last));
        }
        var thisYear = on(today.getFullYear());
        var next = thisYear < today ? on(today.getFullYear() + 1) : thisYear;
        var age = today.getFullYear() - born.getFullYear() - (thisYear > today ? 1 : 0);
        var lastBirthday = thisYear <= today ? thisYear : on(today.getFullYear() - 1);
        var months = (today.getFullYear() - lastBirthday.getFullYear()) * 12 + today.getMonth() - lastBirthday.getMonth() - (today.getDate() < lastBirthday.getDate() ? 1 : 0);
        return {
            age: age,
            ageMonths: Math.max(0, months),
            days: Math.round((next - today) / 86400000),         // until the next birthday, 0 = today
            daysAgo: Math.round((today - lastBirthday) / 86400000), // since the last one
            next: next,
            nextAge: age + (Math.round((next - today) / 86400000) === 0 ? 0 : 1)
        };
    }


    // Tempat, tanggal lahir, umur and a birthday reminder (today / tomorrow / this week / within 30 days / just passed)
    // "Ngawi, 17 Januari 2002 (25 Tahun)", plus a birthday reminder line when it is near
    function ttlHtml(row) {
        var place = row.tempat_lahir ? esc(row.tempat_lahir) : '';
        if (!row.tanggal_lahir) {
            return '<div class="ttl">' + (place || '<span class="text-muted">–</span>') + '</div>';
        }
        var born = new Date(row.tanggal_lahir), b = birthdayInfo(row.tanggal_lahir);
        var date = born.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        var age = b.age > 0 ? b.age + ' Tahun' : b.ageMonths + ' Bulan';
        var html = '<div class="ttl">' + (place ? place + ', ' : '') + date + ' <span class="ttl-age">(' + age + ')</span>';

        var nextDate = b.next.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        var note = null, cls = 'ttl-note';
        if (b.days === 0) {
            note = '🎂 Ulang tahun hari ini';
            cls += ' ttl-note-today';
        } else if (b.days === 1) {
            note = '🎂 Besok ulang tahun';
            cls += ' ttl-note-soon';
        } else if (b.days <= 7) {
            note = '🎂 ' + b.days + ' hari lagi (' + nextDate + ')';
            cls += ' ttl-note-soon';
        } else if (b.days <= 30) {
            note = 'Ulang tahun ' + nextDate;
        } else if (b.daysAgo >= 1 && b.daysAgo <= 7) {
            note = 'Ulang tahun ' + (b.daysAgo === 1 ? 'kemarin' : b.daysAgo + ' hari lalu');
        }
        return html + (note ? '<div class="' + cls + '">' + note + '</div>' : '') + '</div>';
    }

    // "1 Oktober 2024 (2 Tahun 1 Bulan)": tanggal masuk with masa kerja (computed on the server)
    function tanggalMasukHtml(value, masaKerja) {
        if (!value) return '<span class="text-muted">–</span>';
        var date = new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        return '<div class="ttl">' + date + (masaKerja ? ' <span class="ttl-age">(' + esc(masaKerja) + ')</span>' : '') + '</div>';
    }

    function initials(name) {
        return String(name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(function(w) { return w.charAt(0).toUpperCase(); }).join('');
    }

    var table = $('#employees-table').DataTable({
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
        order: [[0, 'asc']], // nama; contract urgency is covered by the chips
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
            url: "{{ route('hrd.employee.index') }}",
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
                // First column: the pinned area on the left is the name only
                data: 'nama',
                name: 'hrd_employee.nama',
                title: 'Nama',
                className: 'col-nama',
                defaultContent: '-',
                render: function(data, type, row) {
                    var missing = row.belum_lengkap || [];
                    var warn = missing.length
                        ? ' <span class="badge badge-light border text-muted" data-toggle="tooltip" title="Belum diisi: ' + esc(missing.join(', ')) + '"><i class="fas fa-exclamation-triangle text-warning"></i> ' + missing.length + '</span>'
                        : '';
                    var noAccount = row.user_id ? '' : ' <i class="fas fa-user-lock text-muted small" data-toggle="tooltip" title="Belum punya akun login"></i>';
                    var avatar = row.photo_url
                        ? '<img src="' + esc(row.photo_url) + '" alt="" class="emp-avatar photo-zoom" width="34" height="34" loading="lazy" data-name="' + esc(data) + '" title="Lihat foto">'
                        : '<span class="emp-avatar emp-avatar-initials">' + esc(initials(data)) + '</span>';
                    return '<div class="d-flex align-items-center">' + avatar + '<div>' +
                           '<div class="font-weight-bold"><a href="#" class="show-employee text-dark" data-id="' + row.id + '">' + esc(data || '-') + '</a>' + noAccount + warn + '</div>' +
                           '<div class="text-muted small">No Induk: ' + esc(row.no_induk || '-') + '</div></div></div>';
                }
            },
            {data: 'nik', name: 'hrd_employee.nik', title: 'NIK', defaultContent: '-'},
            // Shown under the name; the column stays available (hidden) for sorting by no induk
            {data: 'no_induk', name: 'hrd_employee.no_induk', title: 'No Induk', defaultContent: '-', visible: false},
            {
                data: 'tanggal_lahir',
                name: 'hrd_employee.tanggal_lahir',
                title: 'Tempat, Tgl Lahir',
                defaultContent: '-',
                searchable: false,
                render: function(data, type, row) { return ttlHtml(row); }
            },
            {
                data: 'position',
                name: 'position',
                title: 'Posisi',
                className: 'col-posisi',
                defaultContent: '-',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return esc(data || '-') + '<div class="text-muted small">' + esc(row.division || '-') + '</div>';
                }
            },
            {data: 'division', name: 'division', title: 'Divisi', defaultContent: '-', visible: false, orderable: false, searchable: false},
            {
                data: 'perusahaan',
                name: 'hrd_employee.perusahaan',
                title: 'Perusahaan',
                defaultContent: '-',
                render: function(data, type, row) {
                    var list = row.perusahaan_daftar || [];
                    if (!list.length) return '<span class="text-muted">-</span>';
                    // Main company highlighted when there are several
                    return list.map(function(p) {
                        var main = p.utama && list.length > 1;
                        return '<span class="badge ' + (main ? 'badge-primary' : 'badge-light border') + ' mr-1" data-toggle="tooltip" title="' + esc(p.nama) + (main ? ' (utama)' : '') + '">' + esc(p.singkat) + '</span>';
                    }).join('');
                }
            },
            {
                data: 'tanggal_masuk',
                name: 'hrd_employee.tanggal_masuk',
                title: 'Tanggal Masuk',
                defaultContent: '-',
                searchable: false,
                render: function(data, type, row) {
                    return tanggalMasukHtml(data, row.masa_kerja);
                }
            },
            {
                data: 'no_hp',
                name: 'hrd_employee.no_hp',
                title: 'No HP',
                defaultContent: '-',
                orderable: false,
                render: function(data) { return waLink(data); }
            },
            {
                data: 'status',
                name: 'hrd_employee.status',
                title: 'Status',
                defaultContent: '-',
                searchable: false,
                render: function(data, type, row) {
                    var status = data || '-';
                    var info = '';
                    if (status === 'kontrak' && row.kontrak_berakhir) {
                        info = sisaKontrakHtml(row.kontrak_berakhir);
                    } else if (status === 'kontrak') {
                        // Status kontrak without a contract: the end date is unknown
                        info = CAN_MANAGE
                            ? '<a href="#" class="small text-danger d-block open-contracts" data-id="' + row.id + '" data-baru="1"><i class="fas fa-exclamation-triangle mr-1"></i>Belum ada kontrak</a>'
                            : '<div class="small text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Belum ada kontrak</div>';
                    } else if (status === 'tidak aktif' && row.nonaktif_label) {
                        info = '<div class="small text-muted">' + esc(row.nonaktif_label) + '</div>';
                    }
                    return statusBadge(status) + info;
                }
            },
            {data: 'kontrak_berakhir', name: 'hrd_employee.kontrak_berakhir', title: 'Sisa Kontrak', defaultContent: '-', visible: false, searchable: false},
            {
                data: 'action',
                name: 'action',
                title: 'Aksi',
                className: 'col-aksi',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    var html = '<div class="btn-group">';
                    if (CAN_MANAGE) html += '<button type="button" class="btn btn-sm btn-primary open-employee-form" data-id="' + row.id + '" title="Edit data karyawan"><i class="fas fa-edit mr-1"></i>Edit</button>';
                    html += '<button type="button" class="btn btn-sm btn-outline-primary open-contracts" data-id="' + row.id + '" title="Riwayat, perpanjang, putus kontrak"><i class="fas fa-file-contract mr-1"></i>Kontrak</button>';
                    return html + '</div>';
                }
            }
        ],
        drawCallback: function() {
            updateEmployeeStats();
        }
    });

    // Column picker (remembered per browser)
    var TOGGLEABLE = [1, 2, 3, 4, 5, 6, 7, 8, 10]; // Nama, Status and Aksi always visible
    $.each(prefs.columns3 || {}, function(index, visible) { // columns3: column order changed, older choices are dropped
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
        savePrefs({ columns3: columns });
    });

    // "2 bln 5 hari" until the contract end date; orange within 30 days, red when passed
    function sisaKontrakHtml(endValue) {
        var today = new Date(), endDate = new Date(endValue);
        today.setHours(0, 0, 0, 0);
        endDate.setHours(0, 0, 0, 0);
        var diffDays = Math.ceil((endDate - today) / 86400000);
        var text, color = '';
        if (diffDays < 0) {
            text = 'Kontrak Berakhir';
            color = 'text-danger';
        } else if (diffDays === 0) {
            text = 'Berakhir Hari Ini';
            color = 'text-warning';
        } else {
            var years = Math.floor(diffDays / 365), months = Math.floor((diffDays % 365) / 30), days = (diffDays % 365) % 30;
            var parts = [];
            if (years) parts.push(years + ' thn');
            if (months) parts.push(months + ' bln');
            if (days || !parts.length) parts.push(days + ' hari');
            text = parts.join(' ');
            color = diffDays <= 30 ? 'text-warning' : '';
        }
        return '<div class="small ' + color + '" data-toggle="tooltip" title="Berakhir pada: ' + fmtDate(endValue) + '">' + text + '</div>';
    }

    // ---------- Stats & quick filters ----------
    function getGenderFromRow(row) {
        var g = String(row.jenis_kelamin || '').trim().toLowerCase();
        if (g === 'l' || g === 'laki-laki') return 'male';
        if (g === 'p' || g === 'perempuan') return 'female';
        return null;
    }

    function updateEmployeeStats() {
        var rows = table.rows({ search: 'applied' }).data().toArray();
        var count = { kontrak: 0, tetap: 0, freelance: 0, male: 0, female: 0 }, ageSum = 0, ageCount = 0, today = new Date();
        rows.forEach(function(r) {
            var status = String(r.status || '').toLowerCase();
            if (count.hasOwnProperty(status)) count[status]++;
            var g = getGenderFromRow(r);
            if (g) count[g]++;
            if (r.tanggal_lahir) {
                var d = new Date(r.tanggal_lahir);
                if (!isNaN(d.getTime())) {
                    var age = today.getFullYear() - d.getFullYear();
                    var m = today.getMonth() - d.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < d.getDate())) age--;
                    if (age >= 0) { ageSum += age; ageCount++; }
                }
            }
        });
        $('#stat-kontrak').text(count.kontrak);
        $('#stat-tetap').text(count.tetap);
        $('#stat-freelance').text(count.freelance);
        $('#stat-male').text(count.male);
        $('#stat-female').text(count.female);
        $('#stat-usia').text(ageCount ? Math.round(ageSum / ageCount * 10) / 10 + ' Tahun' : '-');
    }

    function renderSummary(summary) {
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
        // The chips count active employees: make sure the status filter does not hide them
        if (quickFilter && $('#filter-status').val() === 'inactive') $('#filter-status').val('active');
        table.ajax.reload();
    });
    $('#quick-reset').on('click', function() { quickFilter = ''; table.ajax.reload(); });

    $('#btn-export').on('click', function(e) {
        e.preventDefault();
        var params = filterParams();
        params.search = { value: table.search() };
        window.location.href = '{{ route('hrd.employee.export') }}?' + $.param(params);
    });

    // Move custom filters next to the DataTables search box
    var $toolbarContent = $('#employeeToolbarHolder').children().detach();
    var $filter = $('#employees-table_wrapper').find('.dataTables_filter');
    $filter.addClass('d-flex align-items-center justify-content-end flex-wrap');
    $filter.prepend($('<div class="d-flex align-items-center flex-wrap mb-2 mb-sm-0" id="employeeToolbar"></div>').append($toolbarContent));
    $filter.find('label').addClass('mb-0 ml-sm-3');
    $('#employeeToolbarHolder').remove();

    $('#' + FILTER_IDS.join(', #')).on('change', function() {
        toggleNonaktifFilters();
        var filters = {};
        FILTER_IDS.forEach(function(id) { filters[id] = $('#' + id).val(); });
        savePrefs({ filters: filters });
        table.ajax.reload();
    });

    // Click a photo (list or detail) to see it large; the name stays a link to the detail
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

    // ---------- Detail karyawan (modal) ----------
    $('#employees-table').on('click', '.show-employee', function(e) {
        e.preventDefault();
        showEmployee($(this).data('id'));
    });

    function showEmployee(employeeId) {
        $('#employeeDetailContent').hide();
        $('#employeeDetailModal .detail-spinner').show();
        $('#employee-logs').empty();
        $('#btn-load-logs').show().data('id', employeeId);
        $('#edit-employee-btn, #contract-employee-btn').data('id', employeeId);
        $('#employeeDetailModal').modal('show');

        $.ajax({ url: EMPLOYEE_URL + '/' + employeeId + '/get-details', type: 'GET', dataType: 'json' })
            .done(function(response) {
                var employee = response.data;
                var positions = employee.positions || [];
                var divisions = positions.flatMap(function(p) { return (p.divisions || []).map(function(d) { return d && d.name; }); })
                    .filter(Boolean).filter(function(v, i, self) { return self.indexOf(v) === i; });

                $('#employee-name').text(employee.nama || '-');
                $('#employee-position').text(positions.map(function(p) { return p.name; }).filter(Boolean).join(', ') || '-');
                $('#employee-division').text(divisions.join(', ') || '-');
                $.each(['nik', 'no_induk', 'kategori_pegawai', 'gol_darah', 'email', 'alamat', 'pendidikan',
                        'finger_id', 'npwp', 'no_bpjs_kesehatan', 'no_bpjs_ketenagakerjaan'], function(_, field) {
                    $('#employee-' + field).text(employee[field] || '-');
                });
                $('#employee-no_hp').html(waLink(employee.no_hp));
                var companies = employee.perusahaan_daftar || [];
                $('#employee-perusahaan').html(companies.length
                    ? companies.map(function(c) { return esc(c) + (c === employee.perusahaan && companies.length > 1 ? ' <span class="badge badge-primary">utama</span>' : ''); }).join('<br>')
                    : '-');
                var darurat = [employee.darurat_nama, employee.darurat_hubungan ? '(' + employee.darurat_hubungan + ')' : null].filter(Boolean).join(' ');
                $('#employee-darurat').html(employee.no_darurat || darurat
                    ? esc(darurat || '-') + '<br>' + waLink(employee.no_darurat)
                    : '-');
                $('#employee-ptkp').text(employee.ptkp ? employee.ptkp + ' · ' + employee.status_pernikahan_label + ', ' + (employee.jumlah_tanggungan || 0) + ' tanggungan' : '-');
                $('#employee-rekening').text(employee.bank_no_rekening
                    ? [employee.bank_nama, employee.bank_no_rekening, employee.bank_atas_nama ? 'a.n. ' + employee.bank_atas_nama : null].filter(Boolean).join(' · ')
                    : '-');
                $('#employee-ttl').html(ttlHtml(employee));
                $('#employee-tanggal_masuk').html(tanggalMasukHtml(employee.tanggal_masuk, employee.masa_kerja));

                var instagrams = employee.instagram;
                if (typeof instagrams === 'string') {
                    try { instagrams = JSON.parse(instagrams); } catch (e) { instagrams = [instagrams]; }
                }
                instagrams = (Array.isArray(instagrams) ? instagrams : (instagrams ? [instagrams] : [])).filter(function(i) { return i && i !== 'null'; });
                $('#employee-instagram').html(instagrams.length ? instagrams.map(function(i) { return '@' + esc(i); }).join('<br>') : '-');

                $('#employee-status').html(statusBadge(employee.status));
                $('.kontrak-info').toggle(employee.status === 'kontrak' && !!employee.kontrak_berakhir);
                $('#employee-kontrak_berakhir').text(fmtDate(employee.kontrak_berakhir));

                var akun = employee.akun;
                $('#employee-akun').html(akun
                    ? esc(akun.name) + '<br><small>' + esc(akun.email) + (akun.roles.length ? ' · ' + esc(akun.roles.join(', ')) : '') + '</small>'
                    : '<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Belum punya akun login</span>');

                var missing = employee.belum_lengkap || [];
                $('#employee-belum-lengkap').toggle(missing.length > 0)
                    .html('<i class="fas fa-exclamation-triangle mr-1"></i>Data belum lengkap: <b>' + esc(missing.join(', ')) + '</b>');
                var nonaktif = employee.status === 'tidak aktif';
                $('#employee-nonaktif').toggle(nonaktif).html(nonaktif
                    ? '<i class="fas fa-user-slash mr-1"></i>Tidak aktif' +
                      (employee.nonaktif_tanggal ? ' sejak <b>' + fmtDate(employee.nonaktif_tanggal) + '</b>' : '') +
                      (employee.nonaktif_alasan_label ? ' · ' + esc(employee.nonaktif_alasan_label) : '') +
                      (employee.nonaktif_keterangan ? '<br>' + esc(employee.nonaktif_keterangan) : '')
                    : '');

                $('#employee-photo-container').html(employee.photo
                    ? '<img src="{{ asset('storage') }}/' + esc(employee.photo) + '" alt="' + esc(employee.nama) + '" class="img-thumbnail rounded-circle photo-zoom" data-name="' + esc(employee.nama) + '" title="Lihat foto" style="width: 180px; height: 180px; object-fit: cover;">'
                    : '<div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 180px; height: 180px;"><i class="fas fa-user-tie fa-6x text-secondary"></i></div>');

                var docNames = { cv: 'CV', ktp: 'KTP', kontrak: 'Kontrak', pendukung: 'Dokumen Pendukung' };
                $.each(docNames, function(docType, label) {
                    var path = employee['doc_' + docType];
                    $('#employee-doc_' + docType).html(path
                        ? '<a href="{{ asset('storage') }}/' + esc(path) + '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-download mr-1"></i>Download ' + label + '</a>'
                        : 'Tidak ada dokumen');
                });

                $('#employeeDetailModal .detail-spinner').hide();
                $('#employeeDetailContent').show();
            })
            .fail(function() {
                $('#employeeDetailModal').modal('hide');
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat detail karyawan.' });
            });
    }

    $('#btn-load-logs').on('click', function() {
        var $btn = $(this), $box = $('#employee-logs');
        $box.html('<div class="text-muted small py-2"><i class="fa fa-spinner fa-spin mr-1"></i>Memuat...</div>');
        $.getJSON(EMPLOYEE_URL + '/' + $btn.data('id') + '/logs')
            .done(function(logs) {
                $btn.hide();
                if (!logs.length) {
                    $box.html('<div class="text-muted small py-2">Belum ada riwayat perubahan tercatat.</div>');
                    return;
                }
                $box.html('<div class="table-responsive"><table class="table table-sm table-bordered employee-logs-table mb-0">' +
                    '<thead class="thead-light"><tr><th>Waktu</th><th>Oleh</th><th>Data</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody>' +
                    logs.map(function(l) {
                        return '<tr><td class="text-nowrap">' + esc(l.waktu) + '</td><td>' + esc(l.oleh) + '</td>' +
                            '<td>' + esc(l.kolom || l.aksi) + '</td>' +
                            '<td class="text-muted">' + (l.sebelum != null ? esc(l.sebelum) : '-') + '</td>' +
                            '<td>' + (l.sesudah != null ? esc(l.sesudah) : '-') + '</td></tr>';
                    }).join('') + '</tbody></table></div>');
            })
            .fail(function() { $box.html('<div class="text-danger small py-2">Gagal memuat riwayat.</div>'); });
    });

    // ---------- Tambah / Edit karyawan (modal) ----------
    var $formModal = $('#employeeFormModal');

    function modalLoading($modal, title) {
        $modal.find('.modal-content').html(
            '<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-spinner fa-spin mr-2"></i>' + esc(title) + '</h5>' +
            '<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' +
            '<div class="modal-body text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
    }

    function loadFailed($modal, xhr) {
        $modal.modal('hide');
        Swal.fire({ icon: 'error', title: 'Gagal', text: (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat data. Silakan coba lagi.' });
    }

    function openEmployeeForm(employeeId) {
        modalLoading($formModal, 'Memuat...');
        $formModal.modal('show');
        $.ajax({
            url: employeeId ? EMPLOYEE_URL + '/' + employeeId + '/edit' : '{{ route('hrd.employee.create') }}',
            type: 'GET',
            dataType: 'json'
        }).done(function(res) {
            $formModal.find('.modal-content').html(res.html);
            initEmployeeForm();
        }).fail(function(xhr) { loadFailed($formModal, xhr); });
    }

    function initEmployeeForm() {
        var $form = $('#employee-form');
        $form.find('.ef-select2').select2({ width: '100%', dropdownParent: $formModal });

        var $user = $('#ef-user_id');
        $user.select2({
            width: '100%',
            dropdownParent: $formModal,
            allowClear: true,
            placeholder: '-- Belum punya akun --',
            ajax: { url: $user.data('url'), dataType: 'json', delay: 250, data: function(p) { return { q: p.term }; } }
        });

        // Posisi utama: options follow the selected positions, shown only with more than one position
        var $positions = $('#ef-position_ids'), $primary = $('#ef-primary_position');
        var primaryVal = String($primary.data('initial') || '');
        function updatePrimary() {
            var selected = $positions.find('option:selected');
            if ($primary.val()) primaryVal = $primary.val();
            $primary.empty();
            selected.each(function() {
                $primary.append(new Option($(this).text(), this.value, false, this.value === primaryVal));
            });
            $primary.trigger('change.select2');
            $('#ef-primary-group').toggle(selected.length > 1);
        }
        $positions.on('change', updatePrimary);
        updatePrimary();

        // Perusahaan utama: same idea, chosen among the selected companies when there are several
        var $companies = $('#ef-perusahaan_list'), $mainCompany = $('#ef-perusahaan');
        var mainCompanyVal = String($mainCompany.data('initial') || '');
        function updateMainCompany() {
            var selected = $companies.find('option:selected');
            if ($mainCompany.val()) mainCompanyVal = $mainCompany.val();
            $mainCompany.empty();
            selected.each(function() {
                $mainCompany.append(new Option(this.value, this.value, false, this.value === mainCompanyVal));
            });
            $('#ef-perusahaan-utama-group').toggle(selected.length > 1);
        }
        $companies.on('change', updateMainCompany);
        updateMainCompany();

        // Alasan / tanggal nonaktif only for status Tidak Aktif
        function toggleNonaktif() {
            $('#ef-nonaktif-group').toggle($('#ef-status').val() === 'tidak aktif');
        }
        $('#ef-status').on('change', toggleNonaktif);
        toggleNonaktif();

        // PTKP preview, same rule as Employee::ptkp()
        function updatePtkp() {
            var status = $('#ef-status_pernikahan').val();
            var ptkp = status ? (status === 'menikah' ? 'K' : 'TK') + '/' + Math.min(3, parseInt($('#ef-jumlah_tanggungan').val(), 10) || 0) : '';
            $('#ef-ptkp').val(ptkp);
            $('#ef-ptkp-text').text(ptkp || 'isi status pernikahan');
        }
        $('#ef-status_pernikahan, #ef-jumlah_tanggungan').on('change input', updatePtkp);
        updatePtkp();

        // Pendidikan is saved as one text: show how it will read
        function updatePendidikan() {
            var text = $.trim($('#ef-pendidikan_jenjang').val() + ' ' + $.trim($('#ef-pendidikan_jurusan').val()));
            $('#ef-pendidikan-preview').html(text ? 'Tersimpan sebagai: <b>' + esc(text) + '</b>' : '');
        }
        $('#ef-pendidikan_jenjang, #ef-pendidikan_jurusan').on('change input', updatePendidikan);
        updatePendidikan();

        // Sebelumnya / Lanjut between the tabs
        var $tabLinks = $form.find('.ef-tabs a[data-toggle="tab"]');
        function tabIndex() { return $tabLinks.index($tabLinks.filter('.active')); }
        function updateTabNav() {
            var i = tabIndex();
            $('#ef-prev').prop('disabled', i <= 0);
            $('#ef-next').prop('disabled', i >= $tabLinks.length - 1);
        }
        $tabLinks.on('shown.bs.tab', function(e) {
            updateTabNav();
            $formModal.find('.ef-body').scrollTop(0);
            // Cursor to the first plain field of the tab (select2 fields would pop open)
            $($(e.target).attr('href')).find('input:visible:not([readonly]):not([type=checkbox]):not([type=file]), select:visible:not(.ef-select2), textarea:visible').first().trigger('focus');
        });
        setTimeout(function() { $('#ef-nama').trigger('focus'); }, 150);
        $('#ef-prev').on('click', function() { $tabLinks.eq(tabIndex() - 1).tab('show'); });
        $('#ef-next').on('click', function() { $tabLinks.eq(tabIndex() + 1).tab('show'); });
        updateTabNav();

        // Live hints: umur, masa kerja
        function updateHints() {
            var lahir = $('#ef-tanggal_lahir').val(), masuk = $('#ef-tanggal_masuk').val();
            var umur = '';
            if (lahir) {
                var b = birthdayInfo(lahir + 'T00:00:00');
                umur = b.age >= 0 ? 'Umur ' + b.age + ' Tahun' + (b.ageMonths ? ' ' + b.ageMonths + ' Bulan' : '') +
                    (b.days === 0 ? ' · <b>ulang tahun hari ini</b> 🎂' : (b.days <= 30 ? ' · ulang tahun ' + b.days + ' hari lagi' : '')) : '';
            }
            $('#ef-umur-hint').html(umur);
            $('#ef-masa-hint').html(masuk ? masaKerjaHint(masuk) : '');
        }
        $('#ef-tanggal_lahir, #ef-tanggal_masuk').on('change input', updateHints);
        updateHints();

        // Kelengkapan bar + per-tab badges (missing required fields / errors)
        updateProgress();
        $form.on('change input', 'input, select, textarea', updateProgress);

        // Snapshot to detect unsaved changes when closing
        formCanClose = false;
        formSnapshot = formState();
    }

    // Masa kerja text for a tanggal masuk (YYYY-MM-DD)
    function masaKerjaHint(value) {
        var start = new Date(value + 'T00:00:00'), today = new Date();
        today.setHours(0, 0, 0, 0);
        var days = Math.round((today - start) / 86400000);
        if (days < 0) return 'Mulai bekerja ' + (-days) + ' hari lagi';
        var months = (today.getFullYear() - start.getFullYear()) * 12 + today.getMonth() - start.getMonth() - (today.getDate() < start.getDate() ? 1 : 0);
        var text = months >= 12 ? Math.floor(months / 12) + ' Tahun' + (months % 12 ? ' ' + (months % 12) + ' Bulan' : '') : (months > 0 ? months + ' Bulan' : days + ' Hari');
        return 'Masa kerja ' + text + (months < 3 ? ' · <b>masa percobaan</b>' : '');
    }

    function isFilled($el) {
        var v = $el.val();
        return Array.isArray(v) ? v.length > 0 : $.trim(v || '') !== '';
    }
    // Required fields that apply right now (the nonaktif fields only for status Tidak Aktif)
    function requiredFields($form) {
        var inactive = $('#ef-status').val() === 'tidak aktif';
        return $form.find('[required]').add(inactive ? $('#ef-nonaktif_alasan, #ef-nonaktif_tanggal') : $());
    }
    function updateProgress() {
        var $form = $('#employee-form');
        if (!$form.length) return;
        var $required = requiredFields($form), $fields = $required.add($form.find('[data-penting]'));
        var filled = $fields.filter(function() { return isFilled($(this)); }).length;
        var pct = $fields.length ? Math.round(filled / $fields.length * 100) : 100;
        $('#ef-progress-text').text(filled + ' / ' + $fields.length + ' data utama terisi');
        $('#ef-progress-bar').css('width', pct + '%').toggleClass('bg-success', pct === 100).toggleClass('bg-warning', pct < 60);

        $form.find('.ef-tabs a[data-toggle="tab"]').each(function() {
            var $pane = $($(this).attr('href'));
            var missing = $required.filter(function() { return $pane.has(this).length && !isFilled($(this)); }).length;
            var errors = $pane.find('.is-invalid').length;
            $(this).find('.ef-tab-badge').html(errors
                ? '<span class="badge badge-danger" title="Ada isian yang salah">!</span>'
                : (missing ? '<span class="badge badge-warning" title="Wajib diisi">' + missing + '</span>' : ''));
        });
    }

    // Mark a field (select2 too) as invalid
    function markInvalid($input) {
        $input.addClass('is-invalid');
        $input.next('.select2').find('.select2-selection').addClass('border-danger');
    }
    function clearInvalid($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.select2-selection.border-danger').removeClass('border-danger');
    }
    $(document).on('change input', '#employee-form .is-invalid', function() {
        $(this).removeClass('is-invalid').next('.select2').find('.select2-selection').removeClass('border-danger');
        updateProgress();
    });

    // Unsaved changes: confirm before closing (X, Batal, Esc)
    var formSnapshot = null, formCanClose = true;
    function formState() {
        var $form = $('#employee-form');
        return $form.length ? $form.serialize() + '|' + $form.find('input[type=file]').map(function() { return this.value; }).get().join(',') : null;
    }
    $(document).on('click', '#employee-form .ef-close', function() { $formModal.modal('hide'); });
    $formModal.on('hide.bs.modal', function(e) {
        if (e.target !== this || formCanClose || !$('#employee-form').length || formState() === formSnapshot) return;
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
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S') && $formModal.hasClass('show') && $('#employee-form').length) {
            e.preventDefault();
            $('#employee-form').trigger('submit');
        }
    });

    $(document).on('change', '#ef-photo', function() {
        var file = this.files && this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(ev) { $('#ef-photo-preview').attr('src', ev.target.result).show(); };
        reader.readAsDataURL(file);
    });
    $(document).on('change', '#employeeFormModal .custom-file-input, #contractModal .custom-file-input', function() {
        var name = this.files && this.files[0] ? this.files[0].name : ($(this).siblings('.custom-file-label').data('default') || 'Pilih file');
        $(this).siblings('.custom-file-label').text(name);
    });

    $(document).on('submit', '#employee-form', function(e) {
        e.preventDefault();
        var $form = $(this), $btn = $('#employee-form-save'), $errors = $('#employee-form-errors');
        if ($btn.prop('disabled')) return;
        clearInvalid($form);
        $errors.hide().empty();

        // Required fields first, without a round trip: list them and open the tab of the first one
        var missing = requiredFields($form).filter(function() { return !isFilled($(this)); });
        if (missing.length) {
            var labels = missing.map(function() {
                markInvalid($(this));
                return $.trim($form.find('label[for="' + this.id + '"]').first().clone().children().remove().end().text()) || this.name;
            }).get();
            $errors.html('<i class="fas fa-exclamation-circle mr-1"></i>Wajib diisi: <b>' + labels.map(esc).join(', ') + '</b>').show();
            $form.find('a[href="#' + missing.first().closest('.tab-pane').attr('id') + '"]').tab('show');
            updateProgress();
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
            var employeeId = $form.data('id') || (res.data && res.data.id);
            if (res.need_contract && employeeId) {
                // Status kontrak needs a contract (end date): continue straight to the contract form
                $formModal.one('hidden.bs.modal', function() { openContracts(employeeId, false, true); }).modal('hide');
                return;
            }
            $formModal.modal('hide');
            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Data karyawan disimpan', timer: 1800, timerProgressBar: true });
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
                $formModal.find('.modal-body').scrollTop(0);
                updateProgress();
            } else {
                $errors.text(r.message || 'Gagal menyimpan data. Silakan coba lagi.').show();
            }
        }).always(function() {
            $btn.prop('disabled', false).html(btnHtml);
        });
    });

    $('#btn-add-employee').on('click', function() { openEmployeeForm(null); });
    $('#employees-table').on('click', '.open-employee-form', function() { openEmployeeForm($(this).data('id')); });

    // ---------- Kontrak (modal) ----------
    var $contractModal = $('#contractModal');
    var contractEmployeeId = null;

    // baru: open with the new-contract form expanded (status is becoming / is kontrak without a contract)
    function openContracts(employeeId, keepContent, baru) {
        contractEmployeeId = employeeId;
        if (!keepContent) modalLoading($contractModal, 'Memuat kontrak...');
        $contractModal.modal('show');
        $.ajax({ url: EMPLOYEE_URL + '/' + employeeId + '/contracts', type: 'GET', data: baru ? { baru: 1 } : {}, dataType: 'json' })
            .done(function(res) {
                $contractModal.find('.modal-content').html(res.html);
                updateContractPreview();
                updateTerminateHint();
            })
            .fail(function(xhr) { loadFailed($contractModal, xhr); });
    }

    function ymdDate(value) { return value ? new Date(value + 'T00:00:00') : null; }
    function daysBetween(a, b) { return Math.round((b - a) / 86400000); }
    var LONG_FMT = { day: 'numeric', month: 'long', year: 'numeric' };

    // Same rule as the server: end = start + N months (no overflow) - 1 day
    function contractEnd(start, months) {
        var target = new Date(start.getFullYear(), start.getMonth() + months, 1);
        var lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
        var end = new Date(target.getFullYear(), target.getMonth(), Math.min(start.getDate(), lastDay));
        end.setDate(end.getDate() - 1);
        return end;
    }

    // New contract: period preview + how the start date relates to the previous contract
    function updateContractPreview() {
        var $form = $('#ck-renew');
        if (!$form.length) return;
        var start = ymdDate($('#ck-start').val()), months = parseInt($('#ck-duration').val(), 10);
        $('.ck-duration-chip').each(function() {
            $(this).toggleClass('btn-primary', +$(this).data('months') === months).toggleClass('btn-outline-primary', +$(this).data('months') !== months);
        });

        var prevEnd = ymdDate($form.data('prev-end'));
        var $hint = $('#ck-start-hint').removeClass('text-success text-warning text-danger text-muted');
        if (start && prevEnd) {
            var gap = daysBetween(prevEnd, start) - 1;
            if (gap === 0) {
                $hint.addClass('text-success').html('<i class="fas fa-check-circle mr-1"></i>Tepat setelah kontrak sebelumnya berakhir');
            } else if (gap > 0) {
                $hint.addClass('text-warning').html('<i class="fas fa-exclamation-triangle mr-1"></i>Ada jeda ' + gap + ' hari tanpa kontrak setelah ' + prevEnd.toLocaleDateString('id-ID', LONG_FMT));
            } else {
                $hint.addClass('text-danger').html('<i class="fas fa-exclamation-triangle mr-1"></i>Tumpang tindih ' + (-gap) + ' hari dengan kontrak sebelumnya (berakhir ' + prevEnd.toLocaleDateString('id-ID', LONG_FMT) + ')');
            }
        } else {
            $hint.addClass('text-muted').text(prevEnd ? '' : 'Kontrak pertama karyawan ini.');
        }

        if (!start || !months || months < 1 || months > 60) {
            $('#ck-preview').html('<span class="text-muted">Isi tanggal mulai dan durasi (1–60 bulan) untuk melihat periode kontrak.</span>');
            return;
        }
        var end = contractEnd(start, months);
        $('#ck-preview').html(
            '<div class="ck-preview-period"><div><small>Mulai</small><b>' + start.toLocaleDateString('id-ID', LONG_FMT) + '</b></div>' +
            '<i class="fas fa-long-arrow-alt-right"></i>' +
            '<div><small>Berakhir</small><b>' + end.toLocaleDateString('id-ID', LONG_FMT) + '</b></div></div>' +
            '<div class="small text-muted mt-1">' + months + ' bulan · ' + (daysBetween(start, end) + 1) + ' hari</div>');
    }
    $(document).on('change input', '#ck-start, #ck-duration', updateContractPreview);
    $(document).on('click', '.ck-duration-chip', function() {
        $('#ck-duration').val($(this).data('months'));
        updateContractPreview();
    });

    // Putus kontrak: how much earlier than planned the contract ends
    function updateTerminateHint() {
        var $form = $('#ck-terminate');
        if (!$form.length) return;
        var effective = ymdDate($('#ck-nonaktif-tanggal').val()), end = ymdDate($form.data('end')), start = ymdDate($form.data('start'));
        var $hint = $('#ck-terminate-hint').removeClass('text-muted text-danger text-warning');
        if (!effective) { $hint.text(''); return; }
        if (effective < start) {
            $hint.addClass('text-danger').text('Sebelum kontrak dimulai (' + start.toLocaleDateString('id-ID', LONG_FMT) + ').');
        } else if (effective >= end) {
            $hint.addClass('text-muted').text('Sesuai / setelah akhir kontrak (' + end.toLocaleDateString('id-ID', LONG_FMT) + ').');
        } else {
            $hint.addClass('text-warning').html('<i class="fas fa-cut mr-1"></i>Kontrak dipercepat ' + daysBetween(effective, end) + ' hari dari jadwal (' + end.toLocaleDateString('id-ID', LONG_FMT) + ').');
        }
    }
    $(document).on('change input', '#ck-nonaktif-tanggal', updateTerminateHint);

    // Only one of the two forms open at a time; highlight the open action
    $(document).on('show.bs.collapse', '#ck-renew, #ck-terminate', function() {
        $('#ck-renew, #ck-terminate').not(this).collapse('hide');
        $('.ck-action').removeClass('active').filter('[data-target="#' + this.id + '"]').addClass('active');
    });
    $(document).on('hide.bs.collapse', '#ck-renew, #ck-terminate', function() {
        $('.ck-action[data-target="#' + this.id + '"]').removeClass('active');
    });
    $(document).on('shown.bs.collapse', '#ck-renew, #ck-terminate', function() {
        $(this).find('input:visible, select:visible').first().trigger('focus');
    });

    function showContractError($error, message) {
        $error.html('<i class="fas fa-exclamation-circle mr-1"></i>' + esc(message)).show();
    }

    function submitContractForm($form, $btn, $error) {
        $error.hide().empty();
        var btnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...');
        $.ajax({
            url: $form.data('url'),
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(res) {
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2200, timerProgressBar: true });
            openContracts(contractEmployeeId, true);
        }).fail(function(xhr) {
            var r = xhr.responseJSON || {};
            var first = r.errors ? r.errors[Object.keys(r.errors)[0]][0] : null;
            showContractError($error, first || r.message || 'Gagal menyimpan. Silakan coba lagi.');
            $btn.prop('disabled', false).html(btnHtml);
        });
    }

    $(document).on('submit', '#ck-renew', function(e) {
        e.preventDefault();
        var months = parseInt($('#ck-duration').val(), 10);
        if (!$('#ck-start').val() || !(months >= 1 && months <= 60)) {
            showContractError($('#ck-renew-error'), 'Tanggal mulai dan durasi (1–60 bulan) wajib diisi.');
            return;
        }
        submitContractForm($(this), $('#ck-renew-save'), $('#ck-renew-error'));
    });

    $(document).on('submit', '#ck-terminate', function(e) {
        e.preventDefault();
        var $form = $(this);
        if (!$('#ck-nonaktif-alasan').val() || !$('#ck-nonaktif-tanggal').val() || !$.trim($('#ck-termination-notes').val())) {
            showContractError($('#ck-terminate-error'), 'Alasan, tanggal, dan keterangan wajib diisi.');
            return;
        }
        // Ending employment is not undone by a click: ask once more
        Swal.fire({
            icon: 'warning',
            title: 'Putus kontrak?',
            html: 'Alasan: <b>' + esc($('#ck-nonaktif-alasan option:selected').text()) + '</b><br>Berlaku: <b>' +
                ymdDate($('#ck-nonaktif-tanggal').val()).toLocaleDateString('id-ID', LONG_FMT) + '</b><br><br>Karyawan akan menjadi <b>tidak aktif</b>.',
            showCancelButton: true, confirmButtonText: 'Ya, putus kontrak', cancelButtonText: 'Batal', confirmButtonColor: '#d33', reverseButtons: true
        }).then(function(r) {
            if (r.value) submitContractForm($form, $('#ck-terminate-save'), $('#ck-terminate-error'));
        });
    });

    $('#employees-table').on('click', '.open-contracts', function(e) {
        e.preventDefault();
        openContracts($(this).data('id'), false, !!$(this).data('baru'));
    });

    // From the detail modal: close it first (Bootstrap 4 does not stack modals)
    function fromDetailModal(open) {
        return function() {
            var id = $(this).data('id');
            $('#employeeDetailModal').one('hidden.bs.modal', function() { open(id); }).modal('hide');
        };
    }
    $('#edit-employee-btn').on('click', fromDetailModal(openEmployeeForm));
    $('#contract-employee-btn').on('click', fromDetailModal(openContracts));

    // Old links (/employee/create, /{id}, /{id}/edit, /{id}/contracts) land here with a query to open the modal
    (function openFromQuery() {
        var params = new URLSearchParams(window.location.search);
        if (params.has('create') && CAN_MANAGE) openEmployeeForm(null);
        else if (params.get('edit') && CAN_MANAGE) openEmployeeForm(params.get('edit'));
        else if (params.get('kontrak')) openContracts(params.get('kontrak'));
        else if (params.get('show')) showEmployee(params.get('show'));
        else return;
        if (window.history.replaceState) window.history.replaceState(null, '', window.location.pathname);
    })();
});
</script>
@endsection

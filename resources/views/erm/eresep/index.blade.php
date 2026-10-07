@extends('layouts.erm.app')
@section('title', 'ERM | E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/erm/rawatjalans.css') }}?v={{ filemtime(public_path('assets/css/erm/rawatjalans.css')) }}">
@endsection

@section('content')

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="page-title mb-0">Daftar Resep Kunjungan Rawat Jalan</h4>
                            <div>
                                <button id="btn-statistik-resep" type="button" class="btn btn-outline-primary" style="min-width:110px;" title="Statistik resep sesuai filter">
                                    <i class="fas fa-chart-bar mr-1"></i>Statistik
                                </button>
                                <button id="btn-old-notifs" type="button" class="btn btn-primary ml-2 text-center position-relative" style="min-width:110px;" title="Lihat Notifikasi Lama">
                                    Notification
                                    <span id="old-notifs-badge" class="badge badge-danger" style="display:none; position:absolute; top:-6px; right:-6px;">0</span>
                                </button>
                            </div>
                        </div>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Farmasi</li>
                            <li class="breadcrumb-item">E-Resep</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-body">
            <style>
            /* E-Resep only: align all table cells to the top (rawatjalans.css centers them) */
            #rawatjalan-table_wrapper table.dataTable td { vertical-align: top !important; }
            /* Klinik logo next to the resep number (same size as on the Billing page) */
            #rawatjalan-table_wrapper .klinik-logo { height: 24px; max-width: 60px; object-fit: contain; flex: 0 0 auto; }
            #modalStatistikResep .stat-tile { border: 1px solid #e9ecef; border-left-width: 4px; border-radius: 6px; padding: 12px 14px; height: 100%; }
            #modalStatistikResep .stat-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; font-weight: 600; }
            #modalStatistikResep .stat-value { font-size: 1.9rem; font-weight: 700; line-height: 1.15; }
            #modalStatistikResep .stat-sub { font-size: .85rem; color: #6c757d; }
            #modalStatistikResep .stat-table td, #modalStatistikResep .stat-table th { padding: .4rem .6rem; }
            #modalStatistikResep.is-loading .stat-body { opacity: .45; pointer-events: none; }
            </style>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="filter_tanggal_mulai">Start Date</label>
                    <input type="date" id="filter_tanggal_mulai" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label for="filter_tanggal_selesai">End Date</label>
                    <input type="date" id="filter_tanggal_selesai" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_klinik">Filter Klinik</label>
                    <select id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_status_resep">Status Resep</label>
                    <select id="filter_status_resep" class="form-control select2">
                        <option value="0" selected>Belum Dilayani</option>
                        <option value="1">Sudah Dilayani</option>
                    </select>
                </div>
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>No Resep</th>
                        <th>Nama Pasien</th>
                        <th>Informasi Pasien</th>
                        <th>Alergi</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Dokter</th>
                        <th>Resep</th>
                    </tr>
                </thead>
            </table>
            <!-- Modal: Old Notifications -->
            <div class="modal fade" id="modalOldNotifications" tabindex="-1" role="dialog" aria-labelledby="modalOldNotificationsLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalOldNotificationsLabel">Notifikasi Lama</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                            <div id="old-notifs-loading" style="display:none; text-align:center; padding:20px;">
                                <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                            </div>
                            <div id="old-notifs-empty" style="display:none; text-align:center; color:#666;">Belum ada notifikasi lama.</div>
                            <ul class="list-group" id="old-notifs-list"></ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="btn-mark-all-old-notifs">Tandai semua telah dibaca</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Statistik Resep -->
            <div class="modal fade" id="modalStatistikResep" tabindex="-1" role="dialog" aria-labelledby="modalStatistikResepLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="modalStatistikResepLabel">Statistik Resep</h5>
                                <small class="text-muted" id="stat-filter-info"></small>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="stat-error" class="alert alert-danger py-2" style="display:none;">Gagal memuat statistik.</div>
                            <div class="stat-body">
                                <div class="row">
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="stat-tile" style="border-left-color:#6c757d;">
                                            <div class="stat-label">Total Resep</div>
                                            <div class="stat-value" id="stat-total">-</div>
                                            <div class="stat-sub">resep masuk</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="stat-tile" style="border-left-color:#28a745;">
                                            <div class="stat-label">Sudah Dilayani</div>
                                            <div class="stat-value text-success" id="stat-terlayani">-</div>
                                            <div class="stat-sub" id="stat-terlayani-pct">&nbsp;</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="stat-tile" style="border-left-color:#dc3545;">
                                            <div class="stat-label">Belum Dilayani</div>
                                            <div class="stat-value text-danger" id="stat-belum">-</div>
                                            <div class="stat-sub" id="stat-belum-pct">&nbsp;</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="stat-tile" style="border-left-color:#007bff;">
                                            <div class="stat-label">Item Dilayani</div>
                                            <div class="stat-value text-primary" id="stat-items">-</div>
                                            <div class="stat-sub" id="stat-items-sub">&nbsp;</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="progress mb-1" style="height:10px;" title="Proporsi resep sudah dilayani">
                                    <div class="progress-bar bg-success" id="stat-progress" role="progressbar" style="width:0%"></div>
                                    <div class="progress-bar bg-danger" id="stat-progress-belum" role="progressbar" style="width:0%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mb-3">
                                    <span><span class="text-success">&#9632;</span> Sudah dilayani</span>
                                    <span><span class="text-danger">&#9632;</span> Belum dilayani</span>
                                </div>

                                <div class="table-responsive" style="max-height:40vh; overflow-y:auto;">
                                    <table class="table table-sm table-bordered table-hover mb-0 stat-table">
                                        <thead class="thead-light" style="position:sticky; top:0; z-index:1;">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th class="text-right">Sudah</th>
                                                <th class="text-right">Belum</th>
                                                <th class="text-right">Non-Racikan</th>
                                                <th class="text-right">Racikan</th>
                                            </tr>
                                        </thead>
                                        <tbody id="stat-rows">
                                            <tr><td colspan="5" class="text-center text-muted">Memuat...</td></tr>
                                        </tbody>
                                        <tfoot id="stat-foot"></tfoot>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Non-Racikan = jumlah item obat satuan. Racikan = jumlah paket racikan (1 paket dihitung 1). Item hanya dihitung dari resep yang sudah dilayani.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" id="btn-stat-refresh"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
<script>
$(document).ready(function () {
    // Default date filter: today (same Start/End Date inputs as the Rawat Jalan page)
    $('#filter_tanggal_mulai, #filter_tanggal_selesai').val(moment().format('YYYY-MM-DD'));
    $('#filter_tanggal_mulai, #filter_tanggal_selesai').on('change', function () {
        table.ajax.reload();
        refreshStatistikIfOpen();
    });

    $('.select2').select2({ width: '100%' });
    $('#filter_status_resep').val('0').trigger('change'); // set default to 0

    @php $spesialisasiColorMap = \App\Models\ERM\Dokter::spesialisasiColorMap(); @endphp
    // Table layout mirrors the Rawat Jalan page (same columns, icons and styles from rawatjalans.css)
    var spesialisasiColorMap = {!! json_encode($spesialisasiColorMap) !!};
    var HARI_ID = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var BULAN_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    function statusIcon(color, icon, title) {
        return '<span class="status-pasien-icon d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;background-color:' + color + ';border-radius:50%;" title="' + title + '"><i class="fas ' + icon + ' text-white" style="font-size:11px;"></i></span>';
    }

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        fixedColumns: { right: 1 },
        pageLength: 50,
        ajax: {
            url: '{{ route("erm.eresepfarmasi.index") }}',
            data: function(d) {
                d.tanggal_mulai = $('#filter_tanggal_mulai').val();
                d.tanggal_selesai = $('#filter_tanggal_selesai').val();
                d.dokter_id = $('#filter_dokter').val();
                d.klinik_id = $('#filter_klinik').val();
                d.status_resep = $('#filter_status_resep').val();
            }
        },
        order: [[4, 'asc']], // Tanggal ASC
        columns: [
            {
                data: 'no_resep',
                name: 'no_resep',
                searchable: true,
                orderable: false,
                render: function(data, type, row) {
                    // "Umum - Lunas" as coloured text: metode bayar (green for Umum, blue otherwise) - invoice status
                    var metode = $.trim(row.metode_bayar || '');
                    var metodeHtml = metode ? '<span class="' + (metode === 'Umum' ? 'text-success' : 'text-info') + '">' + escapeHtml(metode) + '</span>' : '';
                    // Invoice status uses the same labels as the Billing page
                    var invoiceLabel = row.invoice_status || 'Belum Transaksi';
                    var invoiceColor = {
                        'Terhapus': '#6c757d',
                        'Lunas': '#28a745',
                        'Belum Lunas': '#d39e00', // darker than Billing's #ffc107 so it stays readable as text
                        'Piutang': '#17a2b8'
                    }[invoiceLabel] || '#dc3545';
                    var invoiceHtml = '<span style="color:' + invoiceColor + ';">' + escapeHtml(invoiceLabel) + '</span>';
                    var subLine = [metodeHtml, invoiceHtml].filter(Boolean).join(' - ');
                    return '<div class="d-flex flex-column">'
                        + '<div class="d-flex align-items-center">'
                        + (row.klinik_logo_url ? '<img src="' + escapeHtml(row.klinik_logo_url) + '" alt="' + escapeHtml(row.nama_klinik || '') + '" title="' + escapeHtml(row.nama_klinik || '') + '" class="klinik-logo mr-2">' : '')
                        + (data ? '<strong>' + escapeHtml(data) + '</strong>' : '<span class="text-muted">-</span>')
                        + '</div>'
                        + (subLine ? '<small class="font-weight-bold mt-1">' + subLine + '</small>' : '')
                        + '</div>';
                }
            },
            {
                data: 'nama_pasien',
                name: 'nama_pasien',
                searchable: true,
                orderable: false,
                render: function(data, type, row) {
                    var sp = (row.status_pasien || '').toLowerCase();
                    var sa = (row.status_akses || '').toLowerCase();
                    var icons = '';
                    if (sp.includes('vip')) icons += statusIcon('#FFD700', 'fa-crown', 'VIP Member');
                    else if (sp.includes('familia')) icons += statusIcon('#32CD32', 'fa-users', 'Familia Member');
                    else if (sp.includes('black')) icons += statusIcon('#2F2F2F', 'fa-credit-card', 'Black Card Member');
                    else if (sp.includes('red')) icons += statusIcon('#FF0000', 'fa-exclamation-triangle', 'Red Flag');
                    if (sa.includes('akses cepat') || sa.includes('akses_cepat') || sa.includes('akses-cep')) icons += statusIcon('#007BFF', 'fa-wheelchair', 'Akses Cepat');
                    if (row.employee_id) icons += statusIcon('#10b981', 'fa-id-badge', 'Employee');
                    if ((row.status_review || '').toLowerCase() !== 'sudah') icons += statusIcon('#FF0000', 'fa-map-marker-alt', 'Belum Review');

                    var newBadge = parseInt(row.is_first_visit || 0, 10) === 1
                        ? ' <span class="badge badge-primary blinking ml-1" style="font-size:10px; line-height:1; padding:3px 6px; border-radius:999px; vertical-align:middle;" title="Visit pertama pasien">NEW</span>'
                        : '';
                    // sub line: "No RM - notes" (same as the Billing page)
                    var subLine = [$.trim(row.no_rm || ''), $.trim(row.catatan_pasien || '')]
                        .filter(function(v) { return v !== ''; })
                        .map(escapeHtml)
                        .join(' - ');
                    return '<div class="d-flex flex-column">'
                        + '<div class="d-inline-flex align-items-center font-weight-bold"><span class="rawatjalan-patient-name-text">' + escapeHtml(data || '-') + '</span>' + newBadge + icons + '</div>'
                        + (subLine ? '<small class="pasien-notes-preview">' + subLine + '</small>' : '')
                        + '</div>';
                }
            },
            {
                data: null,
                name: 'tanggal_lahir',
                searchable: false,
                orderable: false,
                render: function(data, type, row) {
                    var birthText = '-', childIcon = '', birthdayIcon = '';
                    var birth = row.tanggal_lahir ? moment(row.tanggal_lahir, 'YYYY-MM-DD') : null;
                    if (birth && birth.isValid()) {
                        var today = moment();
                        birthText = birth.date() + ' ' + BULAN_ID[birth.month()] + ' ' + birth.year();
                        if (today.diff(birth, 'years') < 17) childIcon = '<span class="rawatjalan-patient-child-icon" title="Pasien anak"><i class="fas fa-baby-carriage"></i></span>';
                        if (birth.month() === today.month() && birth.date() === today.date()) birthdayIcon = '<span class="rawatjalan-patient-birthday-icon" title="Ulang tahun hari ini"><i class="fas fa-birthday-cake"></i></span>';
                    }

                    var areaParts = [row.village_name, row.district_name, row.regency_name, row.province_name].map(function(p) { return $.trim(p || ''); });
                    var areaComplete = areaParts.every(function(p) { return p.length > 0; });

                    var missing = [];
                    if (!$.trim(row.identity_number || '')) missing.push('Dokumen Identitas');
                    if (!$.trim(row.tanggal_lahir || '')) missing.push('Tanggal Lahir');
                    if (!$.trim(row.gender || '')) missing.push('Gender');
                    if (!$.trim(row.alamat || '')) missing.push('Alamat');
                    if (!areaComplete) missing.push('Desa/Kecamatan/Kabupaten/Provinsi');
                    if (!$.trim(row.telepon_pasien || '')) missing.push('No. HP');
                    var warningIcon = missing.length
                        ? '<span class="text-danger blinking" title="' + escapeHtml('Data pasien belum lengkap: ' + missing.join(', ')) + '"><i class="fas fa-exclamation-triangle"></i></span>'
                        : '';

                    var addressHtml;
                    if (areaComplete) {
                        var alamat = areaParts.join(', ');
                        var shortAlamat = alamat.length > 70 ? alamat.substring(0, 70).trim() + '...' : alamat;
                        addressHtml = '<small class="rawatjalan-patient-address" title="' + escapeHtml(alamat) + '">' + escapeHtml(shortAlamat) + '</small>';
                    } else {
                        addressHtml = '<small class="rawatjalan-patient-address text-danger font-weight-bold">' + ($.trim(row.alamat || '') ? 'Alamat belum lengkap' : 'Alamat belum ditambahkan') + '</small>';
                    }

                    return '<div class="d-flex flex-column">'
                        + '<div class="rawatjalan-patient-birth-row"><span class="rawatjalan-patient-birth-text">' + birthText + '</span>' + childIcon + birthdayIcon + warningIcon + '</div>'
                        + addressHtml
                        + '</div>';
                }
            },
            {
                data: 'alergi',
                name: 'alergi',
                searchable: false,
                orderable: false,
                render: function(data) {
                    // Server sends the allergen names as an array; show one per line
                    var items = (Array.isArray(data) ? data : []).map(function(n) { return $.trim(n || ''); }).filter(Boolean);
                    if (!items.length) return '<span class="text-muted">Tidak ada</span>';
                    return '<div class="text-danger font-weight-bold" style="white-space:normal;">'
                        + items.map(function(n) { return '<div>' + escapeHtml(n) + '</div>'; }).join('')
                        + '</div>';
                }
            },
            {
                data: 'tanggal_visitation',
                name: 'erm_visitations.tanggal_visitation',
                searchable: false,
                orderable: true,
                render: function(data, type, row) {
                    if (type !== 'display') return data || '';
                    var m = moment(data, 'YYYY-MM-DD');
                    var tanggal = m.isValid()
                        ? HARI_ID[m.day()] + ', ' + m.date() + ' ' + BULAN_ID[m.month()] + ' ' + m.year()
                        : escapeHtml(row.tanggal || data || '');
                    var waktu = row.waktu_kunjungan && row.waktu_kunjungan !== '-' ? ' - ' + String(row.waktu_kunjungan).replace(':', '.') : '';

                    // Jenis kunjungan as small coloured text under the date
                    var jenis = {
                        '1': '<small class="font-weight-bold text-primary">Konsultasi</small>',
                        '2': '<small class="font-weight-bold" style="color:#d39e00;">Beli Produk</small>',
                        '5': '<small class="font-weight-bold text-dark">Marketplace</small>'
                    }[String(row.jenis_kunjungan)] || '';

                    return '<div><strong>' + tanggal + '</strong>' + waktu + '</div>' + jenis;
                }
            },
            {
                data: 'dokter_nama',
                name: 'dokter_nama',
                searchable: false,
                orderable: false,
                render: function(data, type, row) {
                    var spes = row.spesialisasi || '';
                    var badgeClass = (spes && spesialisasiColorMap[spes]) || 'badge-light text-dark';
                    var textClass = badgeClass.indexOf('badge-light') !== -1 ? 'text-secondary' : badgeClass.replace(/badge-/g, 'text-');
                    var spesHtml = spes ? '<div class="mt-1"><small class="font-weight-bold ' + textClass + '">' + escapeHtml(spes) + '</small></div>' : '';
                    return '<div><strong>' + escapeHtml(data || '-') + '</strong>' + spesHtml + '</div>';
                }
            },
            {
                data: 'dokumen',
                name: 'dokumen',
                searchable: false,
                orderable: false,
                render: function(data, type, row) {
                    var time = row.asesmen_selesai && row.asesmen_selesai !== '-'
                        ? '<div class="text-muted small mt-1">Asesmen selesai ' + String(row.asesmen_selesai).replace(':', '.') + '</div>'
                        : '';
                    return '<div>' + (data || '') + time + '</div>';
                }
            }
        ],
        columnDefs: [
            { targets: 0, width: "200px" },  // No Resep
            { targets: 1, width: "280px" },  // Nama Pasien
            { targets: 2, width: "260px", className: "rawatjalan-col-informasi" },  // Informasi Pasien
            { targets: 3, width: "200px" },  // Alergi
            { targets: 4, width: "260px" },  // Tanggal
            { targets: 5, width: "220px" },  // Dokter
            { targets: 6, width: "200px" },  // Resep
        ],
    });

    table.on('draw.dt', function() {
        try { table.columns.adjust(); } catch (e) {}
    });
    $(window).on('resize', function() {
        try { table.columns.adjust(); } catch (e) {}
    });

    // initial & periodic badge refresh
    refreshOldNotifsBadge();

    // Auto-refresh table every 10 seconds (keep current page); skipped while the tab is hidden,
    // a modal is open, or the previous reload is still running
    var tableReloading = false;
    table.on('preXhr.dt', function() { tableReloading = true; });
    table.on('xhr.dt', function() { tableReloading = false; });
    setInterval(function() {
        if (document.visibilityState && document.visibilityState !== 'visible') return;

        if (!tableReloading && $('.modal.show').length === 0) {
            try {
                table.ajax.reload(null, false);
            } catch (e) {}
        }

        try {
            refreshOldNotifsBadge();
        } catch (e) {}
    }, 10000);

    // Event ganti filter
    $('#filter_dokter, #filter_klinik, #filter_status_resep').on('change', function () {
        table.ajax.reload();
    });
    $('#filter_dokter, #filter_klinik').on('change', refreshStatistikIfOpen);

    // Statistik Resep modal (uses the same tanggal/dokter/klinik filters as the table)
    var statistikUrl = '{{ route("erm.statistic.summary") }}';
    var statistikXhr = null;
    var HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function fmtTanggal(iso, withDay) {
        var m = moment(iso, 'YYYY-MM-DD');
        if (!m.isValid()) return iso || '';
        return (withDay ? HARI[m.day()] + ', ' : '') + m.date() + ' ' + BULAN[m.month()] + ' ' + m.year();
    }
    function fmtNum(n) { return (Number(n) || 0).toLocaleString('id-ID'); }
    function pct(part, total) { return total > 0 ? Math.round(part / total * 100) : 0; }

    function refreshStatistikIfOpen() {
        if ($('#modalStatistikResep').hasClass('show')) loadStatistik();
    }

    function loadStatistik() {
        var start = $('#filter_tanggal_mulai').val();
        var end = $('#filter_tanggal_selesai').val();
        var dokterText = $('#filter_dokter').val() ? $('#filter_dokter option:selected').text() : 'Semua dokter';
        var klinikText = $('#filter_klinik').val() ? $('#filter_klinik option:selected').text() : 'Semua klinik';
        var periode = start === end ? fmtTanggal(start, true) : fmtTanggal(start) + ' – ' + fmtTanggal(end);
        $('#stat-filter-info').text(periode + ' · ' + dokterText + ' · ' + klinikText);

        if (statistikXhr) statistikXhr.abort();
        $('#stat-error').hide();
        $('#modalStatistikResep').addClass('is-loading');

        statistikXhr = $.get(statistikUrl, {
            start_date: start,
            end_date: end,
            dokter_id: $('#filter_dokter').val(),
            klinik_id: $('#filter_klinik').val()
        }).done(renderStatistik).fail(function (xhr, status) {
            if (status !== 'abort') $('#stat-error').show();
        }).always(function (xhr, status) {
            if (status !== 'abort') $('#modalStatistikResep').removeClass('is-loading');
        });
    }

    function renderStatistik(res) {
        var t = res.totals || {};
        var total = (t.terlayani || 0) + (t.belum || 0);
        var items = (t.non_racikan || 0) + (t.racikan || 0);
        var pSudah = pct(t.terlayani, total);

        $('#stat-total').text(fmtNum(total));
        $('#stat-terlayani').text(fmtNum(t.terlayani));
        $('#stat-belum').text(fmtNum(t.belum));
        $('#stat-terlayani-pct').text(pSudah + '% dari total');
        $('#stat-belum-pct').text((total > 0 ? 100 - pSudah : 0) + '% dari total');
        $('#stat-items').text(fmtNum(items));
        $('#stat-items-sub').text(fmtNum(t.non_racikan) + ' non-racikan · ' + fmtNum(t.racikan) + ' racikan');
        $('#stat-progress').css('width', pSudah + '%');
        $('#stat-progress-belum').css('width', (total > 0 ? 100 - pSudah : 0) + '%');

        var rows = res.rows || [];
        var $body = $('#stat-rows').empty();
        $('#stat-foot').empty();
        if (!rows.length) {
            $body.append('<tr><td colspan="5" class="text-center text-muted">Tidak ada resep pada periode ini.</td></tr>');
            return;
        }
        var html = '';
        rows.forEach(function (r) {
            html += '<tr>'
                + '<td>' + fmtTanggal(r.tanggal, true) + '</td>'
                + '<td class="text-right text-success">' + fmtNum(r.terlayani) + '</td>'
                + '<td class="text-right' + (r.belum > 0 ? ' text-danger font-weight-bold' : ' text-muted') + '">' + fmtNum(r.belum) + '</td>'
                + '<td class="text-right">' + fmtNum(r.non_racikan) + '</td>'
                + '<td class="text-right">' + fmtNum(r.racikan) + '</td>'
                + '</tr>';
        });
        $body.html(html);
        if (rows.length > 1) {
            $('#stat-foot').html('<tr class="font-weight-bold" style="background:#f8f9fa;">'
                + '<td>Total</td>'
                + '<td class="text-right">' + fmtNum(t.terlayani) + '</td>'
                + '<td class="text-right">' + fmtNum(t.belum) + '</td>'
                + '<td class="text-right">' + fmtNum(t.non_racikan) + '</td>'
                + '<td class="text-right">' + fmtNum(t.racikan) + '</td>'
                + '</tr>');
        }
    }

    $('#btn-statistik-resep').on('click', function () {
        $('#modalStatistikResep').modal('show');
        loadStatistik();
    });
    $('#btn-stat-refresh').on('click', loadStatistik);

    // ambil no antrian otomatis
    $('#reschedule-dokter-id, #reschedule-tanggal-visitation').on('change', function() {
        let dokterId = $('#reschedule-dokter-id').val();
        let tanggal = $('#reschedule-tanggal-visitation').val();

        if (dokterId && tanggal) {
            $.get('{{ route("erm.rawatjalans.cekAntrian") }}', { dokter_id: dokterId, tanggal: tanggal }, function(res) {
                $('#reschedule-no-antrian').val(res.no_antrian);
            });
        }
    });

    // submit form reschedule
    $('#form-reschedule').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("erm.rawatjalans.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modalReschedule').modal('hide');
                $('#rawatjalan-table').DataTable().ajax.reload();
                alert(res.message);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan!');
            }
        });
    });
});

// 🛠️ Fungsi openRescheduleModal dibuat di luar $(document).ready supaya global
function openRescheduleModal(visitationId, namaPasien, pasienId) {
    $('#modalReschedule').modal('show');
    $('#reschedule-visitation-id').val(visitationId);
    $('#reschedule-pasien-id').val(pasienId);
    $('#reschedule-nama-pasien').val(namaPasien);
}

// Notification polling moved to global partial (partials.farmasi-notif)

// URL to fetch old notifications and to mark them read
var oldNotifsUrl = '{{ route("erm.farmasi.notifications.old") }}';
var oldNotifsCountUrl = '{{ route("erm.farmasi.notifications.old.count") }}';
var markReadBase = '{{ url("erm/farmasi/notifications") }}';
var markAllReadUrl = '{{ route("erm.farmasi.notifications.markallread") }}';
var csrfToken = '{{ csrf_token() }}';

function refreshOldNotifsBadge() {
    $.get(oldNotifsCountUrl, function (res) {
        var n = 0;
        if (res && res.count !== undefined && res.count !== null) {
            n = parseInt(res.count, 10);
            if (isNaN(n) || n < 0) n = 0;
        }

        if (n > 0) {
            $('#old-notifs-badge').text(n).show();
        } else {
            $('#old-notifs-badge').hide();
        }
    }).fail(function () {
        // ignore badge errors
    });
}

// Open modal and load notifications when button is clicked
$(document).on('click', '#btn-old-notifs', function () {
    $('#modalOldNotifications').modal('show');
    refreshOldNotifsBadge();
    loadOldNotifications();
});

function loadOldNotifications() {
    $('#old-notifs-list').empty();
    $('#old-notifs-empty').hide();
    $('#old-notifs-loading').show();

    $.get(oldNotifsUrl, function (res) {
        $('#old-notifs-loading').hide();

        var items = [];
        if (Array.isArray(res)) {
            items = res;
        } else if (res && Array.isArray(res.data)) {
            items = res.data;
        }

        if (!items || items.length === 0) {
            $('#old-notifs-empty').show();
            return;
        }

        items.forEach(function (n) {
            var message = n.message || n.title || n.text || JSON.stringify(n);
            var time = n.created_at || n.time || n.tanggal || '';

            var $li = $("<li class='list-group-item d-flex justify-content-between align-items-start'></li>");
            var left = '<div class="notif-content">';
            if (n.read) {
                left += '<div class="text-muted">' + escapeHtml(message) + '</div>';
            } else {
                left += '<div class="font-weight-bold">' + escapeHtml(message) + '</div>';
            }
            if (time) left += '<small class="text-muted">' + escapeHtml(time) + '</small>';
            left += '</div>';

            var right = '<div class="notif-actions">';
            if (!n.read) {
                right += '<button class="btn btn-sm btn-primary btn-mark-read" data-id="' + n.id + '">Tandai sudah dibaca</button>';
            } else {
                right += '<span class="badge badge-secondary">Sudah dibaca</span>';
            }
            right += '</div>';

            $li.html(left + right);
            $('#old-notifs-list').append($li);
        });

        refreshOldNotifsBadge();
    }).fail(function () {
        $('#old-notifs-loading').hide();
        $('#old-notifs-empty').text('Gagal memuat notifikasi.').show();
    });
}

// Handle mark-as-read click
$(document).on('click', '.btn-mark-read', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data('id');
    if (!id) return;

    $btn.prop('disabled', true).text('Memproses...');

    $.ajax({
        url: markReadBase + '/' + id + '/mark-read',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        success: function (res) {
            if (res && res.success) {
                // reload list to reflect change
                loadOldNotifications();
                refreshOldNotifsBadge();
            } else {
                alert('Gagal menandai notifikasi.');
                $btn.prop('disabled', false).text('Tandai sudah dibaca');
            }
        },
        error: function () {
            alert('Gagal menandai notifikasi.');
            $btn.prop('disabled', false).text('Tandai sudah dibaca');
        }
    });
});

// Handle mark-all-as-read click
$(document).on('click', '#btn-mark-all-old-notifs', function (e) {
    e.preventDefault();
    var $btn = $(this);

    Swal.fire({
        title: 'Tandai semua telah dibaca?',
        text: 'Semua notifikasi yang belum dibaca akan ditandai sudah dibaca.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then(function (result) {
        if (!result.value) return;

        $btn.prop('disabled', true).text('Memproses...');

        $.ajax({
            url: markAllReadUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res && res.success) {
                    loadOldNotifications();
                    refreshOldNotifsBadge();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (res && res.message) ? res.message : 'Gagal menandai semua notifikasi.' });
                }
            },
            error: function (xhr) {
                var msg = 'Gagal menandai semua notifikasi.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            },
            complete: function () {
                $btn.prop('disabled', false).text('Tandai semua telah dibaca');
            }
        });
    });
});

// Mark resep as selesai (only appears when invoice is locked but resep status is 0)
$(document).on('click', '.btn-selesai-resep', function (e) {
    e.preventDefault();

    var $btn = $(this);
    var url = $btn.data('url');
    if (!url) return;

    Swal.fire({
        title: 'Tandai resep selesai?'
        , text: 'Resep akan dianggap sudah dilayani.'
        , icon: 'question'
        , showCancelButton: true
        , confirmButtonText: 'Ya'
        , cancelButtonText: 'Batal'
    }).then(function (result) {
        if (!result.value) return;

        $btn.prop('disabled', true).text('Memproses...');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res && res.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Selesai', timer: 1200, showConfirmButton: false });
                    try {
                        $('#rawatjalan-table').DataTable().ajax.reload(null, false);
                    } catch (e) {}
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (res && res.message) ? res.message : 'Gagal menandai selesai.' });
                }
            },
            error: function (xhr) {
                var msg = 'Gagal menandai selesai.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            },
            complete: function () {
                $btn.prop('disabled', false).text('Selesai');
            }
        });
    });
});

function escapeHtml(unsafe) {
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

</script>


@endsection


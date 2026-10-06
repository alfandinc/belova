@extends('layouts.erm.app')
@section('title', 'ERM | E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
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
            .dataTables_wrapper .status-pasien-icon {
                width: 20px;
                height: 20px;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                vertical-align: middle;
                margin-right: 8px;
                border-radius: 3px;
                font-size: 11px;
                color: #fff;
            }
            .dataTables_wrapper .patient-meta { line-height: 1.05; }
            .dataTables_wrapper .patient-name { font-weight: 700; display:inline-block; }
            .dataTables_wrapper .patient-rm { font-weight: 400; color:#6c757d; display:inline-block; margin-left:6px; font-size: .95rem; }
            .dataTables_wrapper .patient-info { color: #6c757d; font-size: .85rem; margin-top: 3px; }
            #modalStatistikResep .stat-tile { border: 1px solid #e9ecef; border-left-width: 4px; border-radius: 6px; padding: 12px 14px; height: 100%; }
            #modalStatistikResep .stat-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; font-weight: 600; }
            #modalStatistikResep .stat-value { font-size: 1.9rem; font-weight: 700; line-height: 1.15; }
            #modalStatistikResep .stat-sub { font-size: .85rem; color: #6c757d; }
            #modalStatistikResep .stat-table td, #modalStatistikResep .stat-table th { padding: .4rem .6rem; }
            #modalStatistikResep.is-loading .stat-body { opacity: .45; pointer-events: none; }
            </style>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="filter_tanggal_range">Filter Tanggal Kunjungan</label>
                    <input type="text" id="filter_tanggal_range" class="form-control" placeholder="Pilih Rentang Tanggal">
                    <input type="hidden" id="filter_tanggal_mulai">
                    <input type="hidden" id="filter_tanggal_selesai">
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
                <div class="col-md-3">
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
                        <th>Detail resep</th> <!-- Date will be shown under this in the cell -->
                        <th class="d-none">Tanggal Kunjungan</th>
                        <th>Detail Pasien</th>
                        <th>Dokter</th>
                        <th>Metode Bayar</th>
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
<script>
$(document).ready(function () {
    // Initialize date range picker
    $('#filter_tanggal_range').daterangepicker({
        opens: 'left',
        autoApply: true,
        locale: {
            format: 'DD-MM-YYYY',
            separator: ' s/d ',
            applyLabel: 'Pilih',
            cancelLabel: 'Batal',
            fromLabel: 'Dari',
            toLabel: 'Sampai',
            customRangeLabel: 'Custom',
            weekLabel: 'M',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        startDate: moment(),
        endDate: moment()
    }, function(start, end, label) {
        $('#filter_tanggal_mulai').val(start.format('YYYY-MM-DD'));
        $('#filter_tanggal_selesai').val(end.format('YYYY-MM-DD'));
        table.ajax.reload();
        refreshStatistikIfOpen();
    });
    
    // Set default value tanggal ke hari ini
    $('#filter_tanggal_mulai').val(moment().format('YYYY-MM-DD'));
    $('#filter_tanggal_selesai').val(moment().format('YYYY-MM-DD'));
    
    $('.select2').select2({ width: '100%' });
    $('#filter_status_resep').val('0').trigger('change'); // set default to 0

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("erm.eresepfarmasi.index") }}',
            data: function(d) {
                d.tanggal_mulai = $('#filter_tanggal_mulai').val();
                d.tanggal_selesai = $('#filter_tanggal_selesai').val();
                d.dokter_id = $('#filter_dokter').val();
                d.klinik_id = $('#filter_klinik').val();
                d.status_resep = $('#filter_status_resep').val(); // add status_resep
            }
        },
        // order: [[5, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC
        columns: [
            { 
                data: 'no_resep', 
                name: 'no_resep', 
                searchable: true, 
                orderable: true,
                render: function(data, type, row) {
                    var rawDate = row.tanggal_visitation_formatted || row.tanggal_visitation || '';
                    if (type === 'display') {
                        var displayDate = rawDate;
                        // Only try to parse ISO-like dates; if parsing fails, use server-provided string
                        var m = moment(rawDate, moment.ISO_8601, true);
                        if (m.isValid()) {
                            displayDate = m.format('D MMMM YYYY');
                        }
                        return '<div>'+ (data || '') +'<br><small class="text-muted"><strong>'+ displayDate +'</strong></small></div>';
                    }
                    if (type === 'sort') {
                        // For sorting, prefer a machine-friendly ISO date if available
                        return row.tanggal_visitation_iso || row.tanggal_visitation || data || '';
                    }
                    if (type === 'filter') {
                        return (data || '') + ' ' + (rawDate || '');
                    }
                    return data;
                }
            },
            { data: 'tanggal_visitation', name: 'tanggal_visitation', visible: false, searchable: true, orderable: true },
            { 
                data: 'nama_pasien', 
                name: 'nama_pasien', 
                searchable: true, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var iconHtml = '';
                        var status = (row.status_pasien || '').toString().trim();
                        if (status === 'VIP') {
                            iconHtml += '<span class="status-pasien-icon" style="background-color:#FFD700;" title="VIP Member"><i class="fas fa-crown" style="font-size:11px;color:#fff;"></i></span>';
                        } else if (status === 'Familia') {
                            iconHtml += '<span class="status-pasien-icon" style="background-color:#32CD32;" title="Familia Member"><i class="fas fa-users" style="font-size:11px;color:#fff;"></i></span>';
                        } else if (status === 'Black Card') {
                            iconHtml += '<span class="status-pasien-icon" style="background-color:#2F2F2F;" title="Black Card Member"><i class="fas fa-credit-card" style="font-size:11px;color:#fff;"></i></span>';
                        }

                        var noRm = row.no_rm || '';
                        var umur = row.pasien_umur ? (row.pasien_umur + ' tahun') : '';
                        var alamat = row.pasien_alamat || '';
                        var info = '';
                        if (umur && alamat) info = umur + ' · ' + alamat;
                        else if (umur) info = umur;
                        else if (alamat) info = alamat;
                        var infoHtml = info ? '<small class="text-muted">' + info + '</small>' : '';
                        var rmHtml = noRm ? ' (' + noRm + ')' : '';
                        var nameHtml = '<strong>' + (data || '') + '</strong>';
                        // Build a two-row layout: top row has icon + name, second row is age/address aligned under the name
                        var topRow = '<div style="display:flex;align-items:flex-start;">' + iconHtml + '<div class="patient-meta" style="margin-left:8px;">' + '<div><span class="patient-name">' + nameHtml + '</span>' + '<span class="patient-rm">' + rmHtml + '</span></div>';
                        var infoRow = info ? '<div class="patient-info">' + infoHtml + '</div>' : '';
                        return '<div>' + topRow + infoRow + '</div></div>';
                    }
                    if (type === 'filter') {
                        return (data || '') + ' ' + (row.no_rm || '') + ' ' + (row.pasien_alamat || '');
                    }
                    return data;
                }
            },
            { 
                data: 'nama_dokter', 
                searchable: false, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var spec = row.spesialisasi || '';
                        var style = '';
                        switch ((spec || '').toString().trim()) {
                            case 'Penyakit Dalam': style = 'background-color:#007bff;color:#fff;'; break; // blue
                            case 'Saraf': style = 'background-color:#5bc0de;color:#fff;'; break; // light blue
                            case 'Estetika': style = 'background-color:#ff69b4;color:#fff;'; break; // pink
                            case 'Gigi': style = 'background-color:#fd7e14;color:#fff;'; break; // orange
                            case 'Anak': style = 'background-color:#28a745;color:#fff;'; break; // green
                            case 'Umum': style = 'background-color:#ffc107;color:#212529;'; break; // yellow (dark text)
                            default: style = 'background-color:#6c757d;color:#fff;';
                        }
                        var badge = spec ? '<span class="badge" style="'+ style +'">'+ spec +'</span>' : '';
                        return '<div>'+ (data || '') +'<br><small>'+ badge +'</small></div>';
                    }
                    if (type === 'filter') {
                        return (data || '') + ' ' + (row.spesialisasi || '');
                    }
                    return data;
                }
            },
            { 
                data: 'metode_bayar', 
                searchable: false, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var name = data || row.metode_bayar || '';
                        var cls = 'badge badge-primary';
                        if ((name || '').toString().trim() === 'Umum') cls = 'badge badge-success';

                        // Jenis kunjungan badge
                        var jenisVal = (row && (row.jenis_kunjungan !== undefined && row.jenis_kunjungan !== null)) ? row.jenis_kunjungan : (row ? (row.jenis || row.jenis_kunjungan_id || null) : null);
                        var jenisLabel = '';
                            var jenisIconHtml = '';
                            var jenisStyle = '';
                        var referralType = (row && row.referral_type ? String(row.referral_type) : '').toLowerCase().trim();
                        var referralDetail = (row && row.referral_detail ? String(row.referral_detail) : '').toLowerCase().trim();
                        if (jenisVal !== null && jenisVal !== undefined && jenisVal !== '') {
                            var j = String(jenisVal);
                            if (j === '1') jenisLabel = 'Konsultasi';
                            else if (j === '2') jenisLabel = 'Beli Produk';
                            else if (j === '3') jenisLabel = 'Lab';
                                else if (j === '5') {
                                var marketplaceLabelMap = {
                                    shopee: 'Shopee',
                                    tiktokshop: 'Tiktokshop',
                                    tokopedia: 'Tokopedia',
                                    lazada: 'Lazada'
                                };
                                var marketplaceStyleMap = {
                                    shopee: 'background-color:#f97316;color:#fff;',
                                    tiktokshop: 'background-color:#111827;color:#fff;',
                                    tokopedia: 'background-color:#16a34a;color:#fff;',
                                    lazada: 'background-color:#2563eb;color:#fff;'
                                };

                                jenisLabel = referralType === 'marketplace'
                                    ? (marketplaceLabelMap[referralDetail] || (row.referral_detail || 'Marketplace'))
                                    : 'Marketplace';
                                jenisIconHtml = '<i class="fas fa-store mr-1"></i>';
                                jenisStyle = referralType === 'marketplace'
                                    ? (marketplaceStyleMap[referralDetail] || 'background-color:#d97706;color:#fff;')
                                    : 'background-color:#d97706;color:#fff;';
                                }
                            else jenisLabel = j;
                        }
                        if (!jenisLabel && row && row.jenis_kunjungan_text) {
                            jenisLabel = row.jenis_kunjungan_text;
                        }

                        var jenisCls = 'badge badge-secondary';
                        try {
                            var j2 = String(jenisVal);
                            if (j2 === '1') jenisCls = 'badge badge-primary';      // Konsultasi
                            else if (j2 === '2') jenisCls = 'badge badge-warning'; // Beli Produk
                            else if (j2 === '3') jenisCls = 'badge badge-danger';  // Lab
                            else if (j2 === '5') jenisCls = 'badge';              // Marketplace
                        } catch (e) {}

                        var jenisBadgeAttrs = jenisStyle ? ' class="' + jenisCls + '" style="' + jenisStyle + '"' : ' class="' + jenisCls + '"';
                        var jenisHtml = jenisLabel ? '<div class="mt-1"><span' + jenisBadgeAttrs + '>' + jenisIconHtml + jenisLabel + '</span></div>' : '';
                        return '<div><span class="'+ cls +'">'+ name +'</span>' + jenisHtml + '</div>';
                    }
                    if (type === 'filter') return data || row.metode_bayar || '';
                    return data;
                }
            },
            { 
                data: 'dokumen', 
                searchable: false, 
                orderable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        var btn = data || row.dokumen || '';
                        var time = row.asesmen_selesai || '';
                        var timeHtml = '';
                        if (time && time !== '-') {
                            var displayTime = (time || '').toString().replace(':', '.');
                            timeHtml = '<div class="text-muted small mt-1">Asesmen selesai pada ' + displayTime + '</div>';
                        }
                        return '<div>'+ btn + timeHtml +'</div>';
                    }
                    if (type === 'filter') return (data || row.dokumen || '') + ' ' + (row.asesmen_selesai || '');
                    return data;
                }
            },
            { data: 'status_kunjungan', visible: false, searchable: false } // 🛠️ Sembunyikan
        ],
        columnDefs: [
            // Adjusted widths: No Resep, (hidden date), Nama Pasien, Nama Dokter, Metode Bayar, Resep
            { targets: 0, width: "15%", orderData: [1] },
            { targets: 1, width: "0%" },
            { targets: 2, width: "30%" },
            { targets: 3, width: "35%" },
            { targets: 4, width: "8%", className: 'text-center' },
            { targets: 5, width: "12%", className: 'text-center' },
        ],
    });

    // initial & periodic badge refresh
    refreshOldNotifsBadge();

    // Auto-refresh table every 10 seconds (keep current page)
    setInterval(function() {
        try {
            table.ajax.reload(null, false);
        } catch (e) {}

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


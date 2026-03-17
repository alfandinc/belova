@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Daftar Billing</h3>
                <div class="text-muted small">Kelola billing kunjungan mingguan: filter data dan proses pembayaran.</div>
            </div>
            <div class="d-flex align-items-center">
                <div class="btn-group btn-group-sm" role="group" aria-label="Header actions">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Daftarkan Kunjungan
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item btn-daftarkan-kunjungan-billing" href="#" data-jenis="konsultasi">Konsultasi</a>
                            <a class="dropdown-item btn-daftarkan-kunjungan-billing" href="#" data-jenis="produk">Produk</a>
                            <a class="dropdown-item btn-daftarkan-kunjungan-billing" href="#" data-jenis="lab">Lab</a>
                        </div>
                    </div>
                    <button id="btn-send-farmasi-notif" class="btn btn-primary" title="Kirim Notif ke Farmasi"><i class="fas fa-bell me-1"></i> Kirim Notif ke Farmasi</button>
                    <button id="btn-old-notifs-finance" type="button" class="btn btn-light" title="Lihat Notifikasi Lama">
                        <span style="color:#007bff; font-size:14px;">&#10084;</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <style>
                        /* Allow table cells with class .wrap-column to wrap into multiple lines */
                        .wrap-column {
                            white-space: normal !important;
                            word-wrap: break-word !important;
                            overflow-wrap: break-word !important;
                            /* allow column to grow/shrink based on content */
                            max-width: none !important;
                            min-width: 160px; /* prevent collapsing too small */
                            vertical-align: middle;
                        }
                        /* allow long doctor names to wrap gracefully */
                        .dokter-cell { word-break: break-word; }
                        /* Keep action buttons aligned and prevent wrapping inside action cell */
                        .no-wrap-cell {
                            white-space: nowrap !important;
                        }

                        /* Keep status column fixed width and prevent badge text from splitting */
                        .status-cell {
                            white-space: nowrap !important;
                            width: 120px; /* adjust as needed */
                            text-align: center;
                        }
                        /* custom pink badge for klinik id 2 */
                        .badge-pink {
                            background: #e83e8c;
                            color: #fff;
                        }
                        .badge-black {
                            background: #2f2f2f;
                            color: #fff;
                        }
                        /* Ensure specialization (small) inside dokter-cell is not bold */
                        .dokter-cell small { font-weight: 400 !important; }
                        /* Make patient RM muted and normal weight */
                        .patient-name-cell small { font-weight: 400; color: #6c757d; }
                    </style>

                    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: .5rem;">
                        <ul class="nav nav-tabs mb-0" id="billingTabs" role="tablist" style="flex:0 0 auto;">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="billing-tab-umum" data-toggle="tab" href="#billing-umum" role="tab" aria-controls="billing-umum" aria-selected="true">
                                    Umum <span id="billing-tab-badge-umum" class="badge badge-danger ml-2" style="display:none;">0</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="billing-tab-asuransi" data-toggle="tab" href="#billing-asuransi" role="tab" aria-controls="billing-asuransi" aria-selected="false">
                                    Asuransi <span id="billing-tab-badge-asuransi" class="badge badge-danger ml-2" style="display:none;">0</span>
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: .5rem; flex:1 1 auto;">
                            <div class="d-flex align-items-center" style="flex:0 0 220px;">
                                <select id="filter-dokter" class="form-control form-control-sm w-100">
                                    <option value="">Semua Dokter</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center" style="flex:0 0 220px;">
                                <select id="filter-klinik" class="form-control form-control-sm w-100">
                                    <option value="">Semua Klinik</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center" style="flex:0 0 260px;">
                                <div class="input-group input-group-sm w-100">
                                    <input type="text" class="form-control form-control-sm" id="daterange" placeholder="Pilih Rentang Tanggal" readonly>
                                    <span class="input-group-text"><i class="ti-calendar"></i></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center" style="flex:0 0 160px;">
                                <select id="filter-status" class="form-control form-control-sm w-100">
                                    <option value="belum">Belum Transaksi</option>
                                    <option value="belum_lunas">Belum Lunas</option>
                                    <option value="sudah">Lunas</option>
                                    <option value="piutang">Piutang</option>
                                    <option value="terhapus">Terhapus</option>
                                    <option value="">Semua Status</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="billing-umum" role="tabpanel" aria-labelledby="billing-tab-umum">
                            <div class="table-responsive">
                                <table id="datatable-billing-umum" class="table table-bordered table-hover table-striped dt-responsive" style="width:100%;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Pasien</th>
                                            <th>Dokter</th>
                                            <th>Tanggal Visit</th>
                                            <th>Nomor Invoice</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="billing-asuransi" role="tabpanel" aria-labelledby="billing-tab-asuransi">
                            <div class="table-responsive">
                                <table id="datatable-billing-asuransi" class="table table-bordered table-hover table-striped dt-responsive" style="width:100%;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Pasien</th>
                                            <th>Dokter</th>
                                            <th>Tanggal Visit</th>
                                            <th>Nomor Invoice</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Lazy-loaded modal container (loaded on demand) -->
                    <div id="billing-index-modal-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Set up date variables
        var today = moment().format('YYYY-MM-DD');
        var startDate = today;
        var endDate = today;
        var dokterId = '';
        var klinikId = '';
       var statusFilter = 'belum';

        var billingTableUmum = null;
        var billingTableAsuransi = null;

        var billingTabCountsUrl = '{{ route('finance.billing.tab-counts') }}';

        function updateTabBadges(counts) {
            counts = counts || {};
            var umum = Number(counts.umum || 0) || 0;
            var asuransi = Number(counts.asuransi || 0) || 0;

            var $b1 = $('#billing-tab-badge-umum');
            var $b2 = $('#billing-tab-badge-asuransi');

            if ($b1.length) {
                $b1.text(umum);
                if (umum > 0) $b1.show(); else $b1.hide();
            }
            if ($b2.length) {
                $b2.text(asuransi);
                if (asuransi > 0) $b2.show(); else $b2.hide();
            }
        }

        var __tabCountXhr = null;
        function fetchTabCounts() {
            if (!billingTabCountsUrl) return;
            try {
                if (__tabCountXhr && __tabCountXhr.readyState !== 4) {
                    __tabCountXhr.abort();
                }
            } catch(e) {}

            __tabCountXhr = $.getJSON(billingTabCountsUrl, {
                start_date: startDate,
                end_date: endDate,
                dokter_id: dokterId,
                klinik_id: klinikId
            }).done(function(res) {
                updateTabBadges(res);
            });
        }

        // Allow lazy-loaded modal script to refresh tab badges after actions
        window.financeBillingFetchTabCounts = fetchTabCounts;

        function reloadBillingTables(resetPaging, keepPage) {
            var reset = true;
            if (typeof resetPaging !== 'undefined') reset = !!resetPaging;
            if (typeof keepPage !== 'undefined') reset = !keepPage;

            try {
                if (billingTableUmum) billingTableUmum.ajax.reload(null, reset);
            } catch(e) {}
            try {
                if (billingTableAsuransi) billingTableAsuransi.ajax.reload(null, reset);
            } catch(e) {}
        }

        function setActiveBillingTableGlobal() {
            var activeTab = $('#billingTabs .nav-link.active').attr('id') || '';
            if (activeTab === 'billing-tab-asuransi') {
                window.billingTable = billingTableAsuransi;
            } else {
                window.billingTable = billingTableUmum;
            }
        }
        
        // Initialize date range picker
        $('#daterange').daterangepicker({
            startDate: moment(),
            endDate: moment(),
            locale: {
                format: 'DD MMMM YYYY',
                applyLabel: 'Pilih',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Hingga',
                customRangeLabel: 'Custom Range',
                weekLabel: 'W',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            ranges: {
               'Hari Ini': [moment(), moment()],
               'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Minggu Ini': [moment().startOf('week'), moment().endOf('week')],
               'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
               'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(start, end) {
            startDate = start.format('YYYY-MM-DD');
            endDate = end.format('YYYY-MM-DD');
            reloadBillingTables(true);
            fetchTabCounts();
        });

        // Load dokter and klinik options (AJAX or server-side rendering)
        function loadFilters() {
            $.getJSON("{{ route('finance.billing.filters') }}", function(data) {
                // Dokter
                var dokterSelect = $('#filter-dokter');
                dokterSelect.empty().append('<option value="">Semua Dokter</option>');
                $.each(data.dokters, function(i, dokter) {
                    dokterSelect.append('<option value="'+dokter.id+'">'+dokter.name+'</option>');
                });
                // Klinik
                var klinikSelect = $('#filter-klinik');
                klinikSelect.empty().append('<option value="">Semua Klinik</option>');
                $.each(data.kliniks, function(i, klinik) {
                    klinikSelect.append('<option value="'+klinik.id+'">'+klinik.nama+'</option>');
                });
            });
        }
        loadFilters();

        $('#filter-dokter, #filter-klinik').on('change', function() {
            dokterId = $('#filter-dokter').val();
            klinikId = $('#filter-klinik').val();
            reloadBillingTables(true);
            fetchTabCounts();
        });

       $('#filter-status').on('change', function() {
           statusFilter = $(this).val();
           reloadBillingTables(true);
           fetchTabCounts();
       });
        
        // Initialize DataTables with date and filter
        function createBillingTable($selector, metodeGroup) {
            return $selector.DataTable({
            processing: true,
            serverSide: true,
            // responsive: true,
            ajax: {
                url: "{{ route('finance.billing.data') }}",
                data: function(d) {
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.dokter_id = dokterId;
                    d.klinik_id = klinikId;
                    d.status_filter = statusFilter;
                    d.metode_group = metodeGroup;
                }
            },
            columnDefs: [
                // make the dokter column wrap and allow flexible width (index 1)
                { targets: 1, className: 'wrap-column', responsivePriority: 2 },
                // keep action column compact and no-wrap (now at index 4)
                { targets: 4, className: 'no-wrap-cell', width: '140px', responsivePriority: 1 }
            ],

            columns: [
                { data: null, name: 'nama_pasien', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var name = row.nama_pasien || '';
                            var noRm = row.no_rm || '';
                            var statusPasien = String(row.status_pasien || '').trim();
                            var html = '<div class="patient-name-cell">';
                            html += '<div class="font-weight-bold">' + escapeHtml(name) + '</div>';

                            var pasienStatusBadge = '';
                            try {
                                var statusLower = statusPasien.toLowerCase();
                                if (statusLower.indexOf('vip') !== -1) {
                                    pasienStatusBadge = '<span class="badge badge-warning"><i class="fas fa-crown mr-1"></i>VIP</span>';
                                } else if (statusLower.indexOf('familia') !== -1) {
                                    pasienStatusBadge = '<span class="badge badge-primary"><i class="fas fa-users mr-1"></i>Familia</span>';
                                } else if (statusLower.indexOf('black') !== -1) {
                                    pasienStatusBadge = '<span class="badge badge-black"><i class="fas fa-id-card mr-1"></i>Black</span>';
                                }
                            } catch(e) { pasienStatusBadge = ''; }

                            // compute metode bayar (but render together with no_rm to place badge left of id)
                            var metodeName = '';
                            try {
                                if (row && row.visitation) {
                                    var vb = row.visitation;
                                    if (vb.metodeBayar && (vb.metodeBayar.nama || vb.metodeBayar.name)) metodeName = vb.metodeBayar.nama || vb.metodeBayar.name;
                                    if (!metodeName && (vb.metode_bayar_name || vb.metode_bayar)) metodeName = vb.metode_bayar_name || vb.metode_bayar;
                                }
                                if (!metodeName) {
                                    metodeName = row.metode_bayar_name || row.metode_bayar || row.metodeBayar || row.metodeBayar_name || row.metodeBayarName || '';
                                }
                                if (metodeName && typeof metodeName === 'object') {
                                    metodeName = metodeName.nama || metodeName.name || String(metodeName);
                                }
                            } catch(e) { metodeName = ''; }

                            if (pasienStatusBadge || noRm || (metodeName && String(metodeName).trim() !== '')) {
                                html += '<div class="mt-1 d-flex align-items-center">';
                                if (pasienStatusBadge) {
                                    html += pasienStatusBadge;
                                }
                                if (metodeName && String(metodeName).trim() !== '') {
                                    html += '<span class="badge badge-info' + (pasienStatusBadge ? ' ml-2' : '') + '">' + escapeHtml(String(metodeName)) + '</span>';
                                }
                                if (noRm) {
                                    html += '<span class="badge badge-secondary ml-2">' + escapeHtml(noRm) + '</span>';
                                }
                                html += '</div>';
                            }
                            html += '</div>';
                            return html;
                        }
                        return row.nama_pasien;
                    }
                },
                { data: 'dokter', name: 'dokter', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var dokterName = data || row.dokter || '';
                            var klinikName = row.nama_klinik || '';
                            var klinikId = row.klinik_id || (row.klinik && row.klinik.id) || '';
                            var badgeClass = 'badge-secondary';
                            if (String(klinikId) === '1') badgeClass = 'badge-primary';
                            else if (String(klinikId) === '2') badgeClass = 'badge-pink';

                            var decodeHtml = function(str) { return $('<textarea/>').html(str || '').text(); };
                            var dokterDecoded = decodeHtml(dokterName);
                            var klinikDecoded = decodeHtml(klinikName);
                            var spesialis = row.spesialisasi || row.spesialis || row.dokter_spesialisasi || '';
                            var spesialisDecoded = decodeHtml(spesialis);

                            var dokterClean = dokterDecoded;
                            if (!spesialisDecoded) {
                                var m = dokterDecoded.match(/\s*\(([^)]+)\)\s*$/);
                                if (m) {
                                    dokterClean = dokterDecoded.replace(/\s*\([^)]+\)\s*$/, '').trim();
                                    spesialisDecoded = m[1];
                                }
                            }

                            var html = '<div class="dokter-cell">';
                            html += '<div class="font-weight-bold">' + escapeHtml(dokterClean) + '</div>';
                            if (spesialisDecoded) html += '<div class="mt-1"><span class="badge badge-secondary">' + escapeHtml(spesialisDecoded) + '</span></div>';
                            html += '</div>';
                            // klinik moved to tanggal_visit column
                            return html;
                        }
                        return data;
                    }
                },
                { data: 'tanggal_visit', name: 'tanggal_visit', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var dateText = data || row.tanggal_visit || '';
                            var jenis = row.jenis_kunjungan || '';
                            var klinikId = row.klinik_id || (row.klinik && row.klinik.id) || '';
                            var klinikLabel = '';
                            var klinikBadgeClass = 'badge-secondary';
                            if (String(klinikId) === '1') { klinikLabel = 'Premiere Belova'; klinikBadgeClass = 'badge-primary'; }
                            else if (String(klinikId) === '2') { klinikLabel = 'Belova Skin'; klinikBadgeClass = 'badge-pink'; }
                            else if (row.nama_klinik) { klinikLabel = row.nama_klinik; }

                            var html = '<div class="tanggal-cell"><span class="font-weight-bold">' + escapeHtml(dateText) + '</span>';
                            if (jenis || klinikLabel) {
                                html += '<div class="mt-1">';
                                if (jenis) html += '<span class="badge badge-info">' + escapeHtml(jenis) + '</span>';
                                if (klinikLabel) html += ' <span class="badge ' + klinikBadgeClass + ' ml-1">' + escapeHtml(klinikLabel) + '</span>';
                                html += '</div>';
                            }
                            html += '</div>';
                            return html;
                        }
                        return data || row.tanggal_visit;
                    }
                },
                { data: 'invoice_number', name: 'invoice_number', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            var inv = data || row.invoice_number || '';
                            var statusHtml = row.status || '';
                            var badge = '';
                            var returBadge = '';
                            var returnedItemsCount = 0;
                            try {
                                returnedItemsCount = Number((row.invoice && row.invoice.returned_items_count) || row.returned_items_count || 0) || 0;
                            } catch(e) { returnedItemsCount = 0; }
                            if (returnedItemsCount > 0) {
                                returBadge = '<span class="badge badge-danger ml-1">' + escapeHtml(String(returnedItemsCount)) + ' Item Diretur</span>';
                            }
                            if (row.payment_method && String(row.payment_method).toLowerCase() === 'piutang') {
                                // Prefer authoritative piutang relation if available to determine paid vs remaining
                                var piutangRel = null;
                                try {
                                    if (row.invoice && row.invoice.piutangs && Array.isArray(row.invoice.piutangs) && row.invoice.piutangs.length) {
                                        piutangRel = row.invoice.piutangs[0];
                                    } else if (row.piutang) {
                                        piutangRel = row.piutang;
                                    }
                                } catch(e) { piutangRel = null; }

                                if (piutangRel) {
                                    var ps = String(piutangRel.payment_status || '').toLowerCase();
                                    if (ps === 'paid') {
                                        badge = '<span class="badge badge-success">Lunas</span>';
                                    } else if (ps === 'partial') {
                                        badge = '<span class="badge badge-warning">Belum Lunas</span>';
                                    } else {
                                        badge = '<span class="badge badge-info">Piutang</span>';
                                    }
                                } else {
                                    // Fallback to server status text
                                    var plainFromServer = $('<div>').html(statusHtml).text() || '';
                                    var sLower = String(plainFromServer).toLowerCase();
                                    if (sLower.indexOf('lunas') !== -1) {
                                        badge = '<span class="badge badge-success">Lunas</span>';
                                    } else if (sLower.indexOf('belum lunas') !== -1) {
                                        badge = '<span class="badge badge-warning">Belum Lunas</span>';
                                    } else {
                                        badge = '<span class="badge badge-info">Piutang</span>';
                                    }
                                }
                            } else if (statusHtml) {
                                var plain = $('<div>').html(statusHtml).text();
                                var s = String(plain).toLowerCase();
                                var cls = 'badge-secondary';
                                if (s.indexOf('belum lunas') !== -1) {
                                    cls = 'badge-warning';
                                } else if (s.indexOf('lunas') !== -1) {
                                    cls = 'badge-success';
                                } else if (s.indexOf('piutang') !== -1) {
                                    cls = 'badge-info';
                                } else if (s.indexOf('belum transaksi') !== -1) {
                                    cls = 'badge-danger';
                                } else if (s.indexOf('terhapus') !== -1) {
                                    cls = 'badge-secondary';
                                }
                                badge = '<span class="badge ' + cls + '">' + escapeHtml(plain) + '</span>';
                            }

                            // Build invoice cell with invoice number + badge stacked, and a right-aligned three-dots dropdown
                            var html = '<div class="invoice-cell d-flex align-items-center justify-content-between">';
                            html += '<div class="invoice-left">';
                            html += '<div class="font-weight-bold">' + escapeHtml(inv) + '</div>';
                            if (badge || returBadge) html += '<div class="mt-1">' + badge + returBadge + '</div>';
                            // show invoice total under invoice number if available
                            try {
                                var totalVal = 0;
                                if (row && row.invoice && (row.invoice.total_amount !== undefined && row.invoice.total_amount !== null)) totalVal = row.invoice.total_amount;
                                else if (row && (row.total_amount !== undefined && row.total_amount !== null)) totalVal = row.total_amount;
                                else if (row && (row.total || row.amount || row.total_amount)) totalVal = row.total || row.amount || row.total_amount || 0;
                                if (totalVal && Number(totalVal) > 0) {
                                    var totalFmt = (function(n){ try { return 'Rp ' + Number(n).toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:0}); } catch(e) { return n; } })(totalVal);
                                    // if this invoice has a piutang relation and is partially paid, show remaining
                                    var remainingHtml = '';
                                    try {
                                        var rem = null;
                                        var piutangRel = null;
                                        if (row && row.invoice && row.invoice.piutangs && Array.isArray(row.invoice.piutangs) && row.invoice.piutangs.length) piutangRel = row.invoice.piutangs[0];
                                        else if (row && row.piutang) piutangRel = row.piutang;

                                        if (piutangRel) {
                                            var pAmt = Number(piutangRel.amount || piutangRel.total_amount || piutangRel.total || 0) || 0;
                                            var pPaid = Number(piutangRel.paid_amount || piutangRel.paid || piutangRel.amount_paid || 0) || 0;
                                            rem = pAmt - pPaid;
                                        } else {
                                            // Try common server-side fields for shortage or compute from totals
                                            var cand = Number(row.shortage_amount || row.shortage || row.kekurangan || 0) || 0;
                                            if (cand && cand > 0) {
                                                rem = cand;
                                            } else {
                                                var totFallback = Number((row && row.invoice && (row.invoice.total_amount || row.invoice.total)) || row.total_amount || row.total || row.amount || 0) || 0;
                                                var paidFallback = Number((row && row.invoice && (row.invoice.amount_paid || row.invoice.amountPaid)) || row.amount_paid || row.amountPaid || row.paid_amount || row.paid || 0) || 0;
                                                rem = totFallback - paidFallback;
                                            }
                                        }
                                        if (isFinite(rem) && rem > 0) {
                                            var remFmt = 'Rp ' + Number(rem).toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:0});
                                            remainingHtml = ' <span class="text-danger">(Kurang ' + remFmt + ')</span>';
                                        }
                                    } catch(e) { /* ignore */ }
                                    html += '<div class="mt-1 text-muted"><small>Total: <strong>' + totalFmt + '</strong>' + remainingHtml + '</small></div>';
                                }
                            } catch(e) { }
                            html += '</div>';

                            // Attempt to extract print links/buttons from row.action HTML
                            var actionsHtml = row.action || '';
                            var printItemsHtml = ''; // will hold menu items
                            if (actionsHtml) {
                                try {
                                    var $tmp = $('<div>').html(actionsHtml);
                                    // find anchors or buttons that indicate printing
                                    $tmp.find('a, button').each(function() {
                                        var $el = $(this);
                                        var txt = ($el.text() || '').trim();
                                        var title = ($el.attr('title') || '').trim();
                                        if (/cetak\s*nota\s*v?2/i.test(txt) || /cetak\s*nota\s*v?2/i.test(title)) {
                                            // create menu item preserving href and onclick
                                            var href = $el.attr('href') || '#';
                                            var onclick = $el.attr('onclick') || '';
                                            printItemsHtml += '<a class="dropdown-item" href="' + href + '"' + (onclick ? ' onclick="' + onclick + '"' : '') + '>Cetak Invoice</a>';
                                        } else if (/cetak\s*nota/i.test(txt) || /cetak\s*nota/i.test(title)) {
                                            var href2 = $el.attr('href') || '#';
                                            var onclick2 = $el.attr('onclick') || '';
                                            printItemsHtml += '<a class="dropdown-item" href="' + href2 + '"' + (onclick2 ? ' onclick="' + onclick2 + '"' : '') + '>Cetak Nota</a>';
                                        }
                                    });
                                } catch (e) { /* ignore parse errors */ }
                            }

                            if (printItemsHtml) {
                                // three-dots dropdown button styled like slip_gaji: dropleft + ellipsis icon
                                html += '<div class="invoice-actions ml-3">';
                                html += '<div class="btn-group dropleft">';
                                html += '<button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                                html += '<i class="fa fa-ellipsis-v"></i>';
                                html += '</button>';
                                html += '<div class="dropdown-menu dropdown-menu-right p-2" style="min-width:160px;">' + printItemsHtml + '</div>';
                                html += '</div>';
                                html += '</div>';
                            }

                            html += '</div>'; // .invoice-cell
                            return html;
                        }
                        return data;
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false, responsivePriority: 1,
                    render: function(data, type, row, meta) {
                        if (type === 'display' && data) {
                            // create a temporary container to manipulate the HTML safely
                            var $container = $('<div>').html(data);
                            // Map text to icons and set accessible titles
                            $container.find('a, button').each(function() {
                                var $el = $(this);
                                // ensure anchor billing links open in new tab
                                try { if ($el.is('a')) $el.attr('target', '_blank'); } catch(e) {}
                                if ($el.data('no-icon')) return;
                                // remove spacing utilities and inline margins so btn-group packs buttons tightly
                                $el.css({ 'margin-left': '', 'margin-right': '' });
                                $el.removeClass('mr-1 ml-1 ml-2 mr-2');
                                // ensure consistent small button styling inside group
                                $el.addClass('btn btn-sm');
                                var text = $el.text().trim();
                                if (/lihat\s*billing/i.test(text)) { $el.html('Billing'); $el.attr('title', 'Lihat Billing'); }
                                else if (/cetak\s*nota\s*v?2/i.test(text)) { $el.html('<i class="ti-printer" aria-hidden="true"></i>'); $el.attr('title', 'Cetak Nota v2'); }
                                else if (/cetak\s*nota/i.test(text)) { $el.html('<i class="ti-printer" aria-hidden="true"></i>'); $el.attr('title', 'Cetak Nota'); }
                                else if (/edit/i.test(text)) { $el.html('<i class="ti-pencil" aria-hidden="true"></i>'); $el.attr('title', 'Edit'); }
                                else if (/hapus|delete|remove/i.test(text) && $el.find('i').length === 0) { $el.html('<i class="ti-trash" aria-hidden="true"></i>'); $el.attr('title', 'Hapus'); }
                                $el.attr('aria-label', $el.attr('title') || text);
                            });

                            // Fallback: if a Billing button exists but is not an anchor, open its href/data-href in new tab when clicked
                            $(document).off('click.openBilling').on('click.openBilling', '.btn[title="Lihat Billing"]', function(e){
                                var $b = $(this);
                                var href = $b.attr('href') || $b.data('href') || $b.attr('data-href') || '';
                                if (href && href !== '#') {
                                    e.preventDefault();
                                    window.open(href, '_blank');
                                }
                            });

                            // Group action buttons into a btn-group for compact layout
                            // Exclude print-nota links because we render those in the invoice column
                            var $buttons = $container.find('a, button').filter(function() {
                                var t = ($(this).text() || '').trim();
                                var title = ($(this).attr('title') || '').trim();
                                return !(/cetak\s*nota/i.test(t) || /cetak\s*nota/i.test(title));
                            });
                            if ($buttons.length) {
                                var $group = $('<div class="btn-group" role="group"></div>');
                                $buttons.each(function() { $group.append($(this)); });

                                // Append "Terima" button ONLY when there is an actual Piutang record
                                // with payment_status unpaid/partial and there is remaining amount > 0.
                                try {
                                    var invoice = '';
                                    var piutang = null;
                                    if (row && row.invoice) {
                                        invoice = row.invoice.invoice_number || row.invoice_number || '';
                                        if (row.invoice.piutangs && Array.isArray(row.invoice.piutangs) && row.invoice.piutangs.length) {
                                            piutang = row.invoice.piutangs[0];
                                        } else if (row.invoice.piutang) {
                                            piutang = row.invoice.piutang;
                                        }
                                    }

                                    if (piutang) {
                                        var piutangId = piutang.id || '';
                                        var amount = (piutang.amount !== undefined && piutang.amount !== null) ? piutang.amount : 0;
                                        var paid = (piutang.paid_amount !== undefined && piutang.paid_amount !== null) ? piutang.paid_amount : 0;
                                        var status = (piutang.payment_status || piutang.status || '').toString().toLowerCase();

                                        var remainingPiutang = Number(amount) - Number(paid);
                                        if (!isFinite(remainingPiutang) || remainingPiutang < 0) remainingPiutang = 0;

                                        var statusEligible = (status === 'unpaid' || status === 'partial' || status === '');
                                        if (piutangId && remainingPiutang > 0 && statusEligible) {
                                            var $terima = $('<button type="button" class="btn btn-sm btn-success btn-terima-pembayaran"></button>');
                                            $terima.attr('data-id', piutangId);
                                            $terima.attr('data-amount', amount);
                                            $terima.attr('data-paid', paid);
                                            $terima.attr('data-invoice', invoice);
                                            $terima.html('Lunasi');

                                            var $billingBtn = $group.find('a,button').filter(function() {
                                                var t = ($(this).attr('title') || '').toLowerCase();
                                                var txt = ($(this).text() || '').toLowerCase();
                                                if (t.indexOf('lihat billing') !== -1) return true;
                                                if (txt.indexOf('billing') !== -1) return true;
                                                return false;
                                            }).first();
                                            if ($billingBtn && $billingBtn.length) {
                                                $billingBtn.after($terima);
                                            } else {
                                                $group.append($terima);
                                            }
                                        }
                                    }
                                } catch (e) { /* ignore */ }

                                return $group.prop('outerHTML');
                            }

                            return $container.html();
                        }
                        return data;
                    }
                },
                
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Tidak ada data yang ditemukan",
                info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                infoEmpty: "Tidak ada data yang tersedia",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                },
                processing: "Memproses..."
            },
            // Adjusted ordering index after merging No. RM into Nama Pasien
            order: [[3, 'desc']]
            });
        }

        billingTableUmum = createBillingTable($('#datatable-billing-umum'), 'umum');
        billingTableAsuransi = createBillingTable($('#datatable-billing-asuransi'), 'asuransi');

        // Keep a global active pointer for lazy-loaded modal script compatibility
        window.billingTableUmum = billingTableUmum;
        window.billingTableAsuransi = billingTableAsuransi;
        setActiveBillingTableGlobal();

        // Adjust columns when switching tabs (DataTables needs this for hidden tables)
        $('#billingTabs a[data-toggle="tab"]').on('shown.bs.tab', function() {
            setActiveBillingTableGlobal();
            try {
                var t = window.billingTable;
                if (t) t.columns.adjust();
            } catch(e) {}
        });

        // Auto-reload DataTable every 15 seconds
        setInterval(function() {
            reloadBillingTables(false, true); // keep current page position
            fetchTabCounts();
        }, 15000); // 15000 milliseconds = 15 seconds

        // Expose config used by lazy-loaded modal script
        window.financeBillingIndexConfig = {
            oldFinNotifsUrl: '{{ route("finance.notifications.old") }}',
            markFinReadBase: '{{ url("finance/notifications") }}',
            piutangReceiveBase: '{{ url('/finance/piutang') }}',
            ermPasiensSelect2Url: '{{ route("erm.pasiens.select2") }}',
            ermVisitationsStoreUrl: '{{ route("erm.visitations.store") }}',
            ermVisitationsProdukStoreUrl: '{{ route("erm.visitations.produk.store") }}',
            ermVisitationsLabStoreUrl: '{{ route("erm.visitations.lab.store") }}',
            ermCekAntrianUrl: '{{ route("erm.visitations.cekAntrian") }}',
            getDoktersBaseUrl: '{{ url('/get-dokters') }}',
            csrfToken: '{{ csrf_token() }}'
        };

        var billingIndexModalsUrl = '{{ route('finance.billing.index-modals') }}';
        var billingIndexModalsScriptUrl = '{{ asset('js/finance/billing/index-modals.js') }}';
        window.__billingIndexLazyAssetsReady = false;
        window.__billingIndexLazyAssetsPromise = null;

        function loadScriptOnce(src) {
            return new Promise(function(resolve, reject) {
                if (!src) return reject(new Error('Missing script src'));
                if (document.querySelector('script[data-src="' + src + '"]')) return resolve();
                var s = document.createElement('script');
                s.src = src;
                s.async = true;
                s.setAttribute('data-src', src);
                s.onload = function() { resolve(); };
                s.onerror = function() { reject(new Error('Failed to load script: ' + src)); };
                document.head.appendChild(s);
            });
        }

        function ensureBillingIndexLazyAssets() {
            if (window.__billingIndexLazyAssetsPromise) return window.__billingIndexLazyAssetsPromise;

            window.__billingIndexLazyAssetsPromise = new Promise(function(resolve, reject) {
                var $container = $('#billing-index-modal-container');
                if (!$container.length) {
                    $('body').append('<div id="billing-index-modal-container"></div>');
                    $container = $('#billing-index-modal-container');
                }

                var needHtml = ($('#modalOldNotificationsFinance').length === 0 || $('#modalPdfPreview').length === 0 || $('#modalTerimaPembayaran').length === 0);
                needHtml = needHtml || ($('#modalDaftarKunjunganBillingIndex').length === 0);
                var htmlPromise = needHtml
                    ? $.get(billingIndexModalsUrl).then(function(html) { $container.html(html); })
                    : Promise.resolve();

                Promise.resolve(htmlPromise)
                    .then(function() { return loadScriptOnce(billingIndexModalsScriptUrl); })
                    .then(function() {
                        window.__billingIndexLazyAssetsReady = true;
                        if (window.financeBillingIndexModals && typeof window.financeBillingIndexModals.init === 'function') {
                            window.financeBillingIndexModals.init();
                        }
                        resolve();
                    })
                    .catch(reject);
            });

            return window.__billingIndexLazyAssetsPromise;
        }

        // Delegate handlers for visitation-level actions (trash/restore/force)
        $(document).on('click', '.btn-trash-visitation', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Pindahkan billing ke trash?',
                showCancelButton: true,
                confirmButtonText: 'Ya, pindahkan',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.value) {
                    $.post("{{ url('/finance/billing/visitation/') }}/" + id + "/trash", {_token: '{{ csrf_token() }}'})
                    .done(function(res) {
                        Swal.fire('Sukses', res.message, 'success');
                        reloadBillingTables(true);
                        fetchTabCounts();
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });

        // Lazy-load billing index modals and their handlers
        $(document).on('click', '#btn-old-notifs-finance', function (e) {
            if (window.__billingIndexLazyAssetsReady) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            ensureBillingIndexLazyAssets().then(function () {
                if (window.financeBillingIndexModals) {
                    $('#modalOldNotificationsFinance').modal('show');
                    window.financeBillingIndexModals.loadOldFinNotifications();
                }
            });
        });

        function escapeHtml(unsafe) {
            return String(unsafe).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        // Lazy-load on first PDF preview click
        $(document).on('click', '.invoice-cell .dropdown-item', function (e) {
            if (window.__billingIndexLazyAssetsReady) return;
            var $el = $(this);
            var txt = ($el.text() || '').trim();
            if (!/cetak/i.test(txt)) return;

            e.preventDefault();
            e.stopImmediatePropagation();

            var href = $el.attr('href') || $el.data('href') || '';
            ensureBillingIndexLazyAssets().then(function () {
                if (!href || href === '#') {
                    // Let the lazy-loaded handler deal with onclick fallbacks
                    $el.trigger('click');
                    return;
                }

                if (window.financeBillingIndexModals) {
                    window.financeBillingIndexModals.openPdfPreviewByHref(href);
                }
            });
        });

        // Lazy-load on first Terima Pembayaran click
        $(document).on('click', '.btn-terima-pembayaran', function (e) {
            if (window.__billingIndexLazyAssetsReady) return;
            e.preventDefault();
            e.stopImmediatePropagation();

            var $btn = $(this);
            ensureBillingIndexLazyAssets().then(function () {
                if (window.financeBillingIndexModals) {
                    window.financeBillingIndexModals.openTerimaPembayaranModal({
                        id: $btn.data('id'),
                        amount: $btn.data('amount'),
                        paid: $btn.data('paid') || 0,
                        invoice: $btn.data('invoice')
                    });
                }
            });
        });

        // Lazy-load on first Daftarkan Kunjungan click
        $(document).on('click', '.btn-daftarkan-kunjungan-billing', function (e) {
            var mode = ($(this).data('jenis') || 'konsultasi').toString();

            if (!window.__billingIndexLazyAssetsReady) {
                e.preventDefault();
                e.stopImmediatePropagation();
                ensureBillingIndexLazyAssets().then(function () {
                    if (window.financeBillingIndexModals && typeof window.financeBillingIndexModals.openDaftarKunjunganModal === 'function') {
                        window.financeBillingIndexModals.openDaftarKunjunganModal(mode);
                    }
                });
                return;
            }

            if (window.financeBillingIndexModals && typeof window.financeBillingIndexModals.openDaftarKunjunganModal === 'function') {
                e.preventDefault();
                window.financeBillingIndexModals.openDaftarKunjunganModal(mode);
            }
        });

        $(document).on('click', '.btn-restore-visitation', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Kembalikan billing dari trash?',
                showCancelButton: true,
                confirmButtonText: 'Ya, kembalikan',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.value) {
                    $.post("{{ url('/finance/billing/visitation/') }}/" + id + "/restore", {_token: '{{ csrf_token() }}'})
                    .done(function(res) {
                        Swal.fire('Sukses', res.message, 'success');
                        reloadBillingTables(true);
                        fetchTabCounts();
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });

        $(document).on('click', '.btn-force-visitation', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus permanen billing untuk kunjungan ini?',
                text: 'Tindakan ini tidak dapat dibatalkan.',
                showCancelButton: true,
                confirmButtonText: 'Hapus Permanen',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.value) {
                    $.ajax({
                        url: "{{ url('/finance/billing/visitation/') }}/" + id + "/force",
                        method: 'DELETE',
                        data: {_token: '{{ csrf_token() }}'},
                    }).done(function(res) {
                        Swal.fire('Dihapus', res.message, 'success');
                        reloadBillingTables(true);
                        fetchTabCounts();
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });

        // Finance: Send notification to Farmasi
        $('#btn-send-farmasi-notif').click(function() {
            // Ask for a short message to send
            Swal.fire({
                title: 'Kirim notifikasi ke Farmasi',
                input: 'text',
                inputPlaceholder: 'Masukkan pesan singkat...',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                preConfirm: (value) => {
                    if (!value) {
                        Swal.showValidationMessage('Pesan tidak boleh kosong');
                    }
                    return value;
                }
            }).then(function(result) {
                if (result.value && result.value) {
                    var message = result.value;
                    $.post("{{ url('/finance/send-notif-farmasi') }}", {
                        message: message,
                        _token: '{{ csrf_token() }}'
                    }, function(res) {
                        if (res && res.success) {
                            var info = 'Notifikasi berhasil dikirim ke Farmasi.';
                            if (res.total !== undefined) {
                                info += "\nTerkirim: " + (res.sent || 0) + " dari " + (res.total || 0);
                            }
                            if (res.failed && res.failed.length > 0) {
                                info += "\nGagal: " + res.failed.join(', ');
                            }
                            Swal.fire('Terkirim!', info, 'success');
                            // set sound type so Farmasi page can play a sound
                            localStorage.setItem('notifSoundType', 'notif');
                        } else {
                            Swal.fire('Gagal', 'Tidak dapat mengirim notifikasi.', 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan saat mengirim notifikasi.', 'error');
                    });
                }
            });
        });

        // Allow lazy-loaded script to reuse active DataTable instance
        setActiveBillingTableGlobal();

        // Initial badge counts
        fetchTabCounts();
    });
</script>
@endsection
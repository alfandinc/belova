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
                            <a class="dropdown-item btn-daftarkan-kunjungan-billing" href="#" data-jenis="marketplace">Marketplace</a>
                        </div>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Event Billing
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            @forelse(($activeEvents ?? collect()) as $event)
                                <a class="dropdown-item btn-open-billing-modal" href="{{ route('finance.billing.event-create', $event->id) }}">
                                    {{ $event->nama_event }}
                                    @if(!empty($event->kode_event))
                                        <small class="text-muted d-block">{{ $event->kode_event }} - {{ optional($event->klinik)->nama ?? '-' }}</small>
                                    @endif
                                </a>
                            @empty
                                <span class="dropdown-item text-muted">Belum ada event aktif</span>
                            @endforelse
                        </div>
                    </div>
                    <button id="btn-merchandise-stock-out" type="button" class="btn btn-warning" title="Keluar Merchandise">
                        <i class="fas fa-gift mr-1"></i> Merchandise
                    </button>
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
                    <link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
                    <style>
                        /* Solid backgrounds for pinned columns so scrolled cells don't show through (dark theme default) */
                        .billing-dt-wrap { --bfc-bg: #2c3144; --bfc-bg-even: #333950; --bfc-bg-hover: #2a2e40; --bfc-head: #333950; }
                        html.theme-light .billing-dt-wrap { --bfc-bg: #fff; --bfc-bg-even: #f1f5fa; --bfc-bg-hover: #f8f8fc; --bfc-head: #f1f5fa; }
                        .billing-dt-wrap table.dataTable tbody tr > .dtfc-fixed-left,
                        .billing-dt-wrap table.dataTable tbody tr > .dtfc-fixed-right { background-color: var(--bfc-bg) !important; }
                        .billing-dt-wrap table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-left,
                        .billing-dt-wrap table.dataTable tbody tr:nth-of-type(even) > .dtfc-fixed-right { background-color: var(--bfc-bg-even) !important; }
                        .billing-dt-wrap table.dataTable tbody tr:hover > .dtfc-fixed-left,
                        .billing-dt-wrap table.dataTable tbody tr:hover > .dtfc-fixed-right { background-color: var(--bfc-bg-hover) !important; }
                        .billing-dt-wrap table.dataTable thead tr > .dtfc-fixed-left,
                        .billing-dt-wrap table.dataTable thead tr > .dtfc-fixed-right { background-color: var(--bfc-head) !important; }
                        /* Lift the pinned cell whose print menu is open above the pinned cells of the following rows */
                        .billing-dt-wrap table.dataTable tbody td.billing-menu-open { z-index: 10 !important; }
                        .billing-dt-wrap .billing-print-menu .dropdown-menu { z-index: 1050; }
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
                        /* Klinik logo next to the invoice number */
                        .invoice-cell .klinik-logo { height: 24px; max-width: 60px; object-fit: contain; flex: 0 0 auto; }
                        /* Blinking NEW badge, same as Rawat Jalan index */
                        .blinking { animation: blinking-animation 1s linear infinite; }
                        @keyframes blinking-animation { 0%, 100% { opacity: 1; } 50% { opacity: 0.2; } }
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
                            <button type="button" id="btn-export-billing" class="btn btn-sm btn-success" title="Download daftar billing (tab, filter & pencarian saat ini)">
                                <i class="fas fa-file-excel mr-1"></i> Export Excel
                            </button>
                        </div>
                    </div>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="billing-umum" role="tabpanel" aria-labelledby="billing-tab-umum">
                            <div class="billing-dt-wrap">
                                <table id="datatable-billing-umum" class="table table-bordered table-hover table-striped nowrap" style="width:100%;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nomor Invoice</th>
                                            <th>Nama Pasien</th>
                                            <th>Dokter</th>
                                            <th>Tanggal Visit</th>
                                            <th>Metode Bayar</th>
                                            <th>Referral</th>
                                            <th>Total</th>
                                            <th>Kekurangan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="billing-asuransi" role="tabpanel" aria-labelledby="billing-tab-asuransi">
                            <div class="billing-dt-wrap">
                                <table id="datatable-billing-asuransi" class="table table-bordered table-hover table-striped nowrap" style="width:100%;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nomor Invoice</th>
                                            <th>Nama Pasien</th>
                                            <th>Dokter</th>
                                            <th>Tanggal Visit</th>
                                            <th>Metode Bayar</th>
                                            <th>Referral</th>
                                            <th>Total</th>
                                            <th>Kekurangan</th>
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

                    <!-- Billing create page shown in a modal (iframe with ?embed=1) -->
                    <style>
                        #modalBillingCreate .modal-dialog { max-width: 98vw; width: 98vw; height: 96vh; margin: 2vh auto; }
                        #modalBillingCreate .modal-content { height: 100%; }
                        #modalBillingCreate .modal-header { padding: .5rem 1rem; }
                        #modalBillingCreate .modal-body { padding: 0; position: relative; flex: 1 1 auto; overflow: hidden; }
                        #modalBillingCreate iframe { width: 100%; height: 100%; border: 0; display: block; }
                        #modalBillingCreate .billing-create-loading { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
                        /* the chat launcher sits above modals; hide it while billing is open */
                        body.billing-create-open #belovaChatWidget { display: none !important; }
                    </style>
                    <div class="modal fade" id="modalBillingCreate" tabindex="-1" role="dialog" aria-labelledby="modalBillingCreateLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalBillingCreateLabel">Billing</h5>
                                    <div class="d-flex align-items-center">
                                        <a href="#" id="billingCreateOpenTab" class="btn btn-sm btn-light mr-2" target="_blank" title="Buka di tab baru"><i class="fas fa-external-link-alt"></i></a>
                                        <button type="button" class="close" id="billingCreateCloseBtn" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                </div>
                                <div class="modal-body">
                                    <div class="billing-create-loading">
                                        <div class="spinner-border text-primary" role="status"><span class="sr-only">Memuat...</span></div>
                                    </div>
                                    <iframe id="billingCreateFrame" title="Billing" src="about:blank"></iframe>
                                </div>
                            </div>
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

            // Only the visible tab's table; the other one reloads when its tab is opened
            try {
                var activeTable = window.billingTable || billingTableUmum;
                if (activeTable) activeTable.ajax.reload(null, reset);
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

        // Excel export of the active tab with the current filters and search
        $('#btn-export-billing').on('click', function() {
            var isAsuransi = ($('#billingTabs .nav-link.active').attr('id') || '') === 'billing-tab-asuransi';
            var search = '';
            try {
                var activeTable = isAsuransi ? billingTableAsuransi : billingTableUmum;
                if (activeTable) search = activeTable.search() || '';
            } catch (e) {}

            var params = $.param({
                start_date: startDate,
                end_date: endDate,
                dokter_id: dokterId,
                klinik_id: klinikId,
                status_filter: statusFilter,
                metode_group: isAsuransi ? 'asuransi' : 'umum',
                search: search
            });
            window.location.href = "{{ route('finance.billing.export') }}?" + params;
        });

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
        
        function formatRupiah(n) {
            return 'Rp ' + Number(n).toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:0});
        }

        // Invoice total and remaining unpaid amount for a billing row (rem is null when it cannot be computed)
        function billingRowAmounts(row) {
            var totalVal = 0;
            if (row && row.invoice && (row.invoice.total_amount !== undefined && row.invoice.total_amount !== null)) totalVal = row.invoice.total_amount;
            else if (row && (row.total_amount !== undefined && row.total_amount !== null)) totalVal = row.total_amount;
            else if (row && (row.total || row.amount)) totalVal = row.total || row.amount || 0;

            var rem = null;
            try {
                // if this invoice has a piutang relation use it, otherwise shortage / total - paid
                var piutangRel = null;
                if (row.invoice && row.invoice.piutangs && Array.isArray(row.invoice.piutangs) && row.invoice.piutangs.length) piutangRel = row.invoice.piutangs[0];
                else if (row.piutang) piutangRel = row.piutang;

                if (piutangRel) {
                    var pAmt = Number(piutangRel.amount || piutangRel.total_amount || piutangRel.total || 0) || 0;
                    var pPaid = Number(piutangRel.paid_amount || piutangRel.paid || piutangRel.amount_paid || 0) || 0;
                    rem = pAmt - pPaid;
                } else {
                    var cand = Number(row.shortage_amount || row.shortage || row.kekurangan || 0) || 0;
                    if (cand > 0) {
                        rem = cand;
                    } else {
                        var totFallback = Number((row.invoice && (row.invoice.total_amount || row.invoice.total)) || row.total_amount || row.total || row.amount || 0) || 0;
                        var paidFallback = Number((row.invoice && (row.invoice.amount_paid || row.invoice.amountPaid)) || row.amount_paid || row.amountPaid || row.paid_amount || row.paid || 0) || 0;
                        rem = totFallback - paidFallback;
                    }
                }
                if (!isFinite(rem)) rem = null;
            } catch (e) {
                rem = null;
            }
            return { total: Number(totalVal) || 0, rem: rem };
        }

        // Initialize DataTables with date and filter
        function createBillingTable($selector, metodeGroup, deferInitialLoad) {
            return $selector.DataTable({
            processing: true,
            serverSide: true,
            // Horizontal scroll with Nomor Invoice pinned left, Total + Kekurangan + Aksi pinned right
            scrollX: true,
            scrollCollapse: true,
            // the Asuransi tab starts hidden: skip its first request, it loads when the tab is opened
            deferLoading: deferInitialLoad ? 0 : null,
            autoWidth: false,
            fixedColumns: {
                left: 1,
                right: 3
            },
            drawCallback: function() {
                // Position print menus as fixed so the scroll container doesn't clip them
                $(this.api().table().container()).find('.billing-print-menu [data-toggle="dropdown"]').each(function() {
                    if (!$(this).data('bs.dropdown')) {
                        $(this).dropdown({ popperConfig: { positionFixed: true } });
                    }
                });
            },
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
                // make the dokter column wrap and allow flexible width (index 2)
                { targets: 2, className: 'wrap-column', responsivePriority: 2 },
                // keep action column compact and no-wrap (now at index 8)
                { targets: 8, className: 'no-wrap-cell', width: '140px', responsivePriority: 1 },
                // Metode Bayar (index 4) is only shown on the Asuransi tab; every Umum row is "Umum"
                { targets: 4, visible: metodeGroup !== 'umum' }
            ],

            columns: [
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
                            // klinik logo left of the invoice number (klinik_logo_url / nama_klinik are server-escaped)
                            var klinikLogo = row.klinik_logo_url
                                ? '<img src="' + row.klinik_logo_url + '" alt="' + (row.nama_klinik || '') + '" title="' + (row.nama_klinik || '') + '" class="klinik-logo mr-2">'
                                : '';
                            html += '<div class="d-flex align-items-center">' + klinikLogo + '<span class="font-weight-bold">' + escapeHtml(inv) + '</span></div>';
                            if (badge || returBadge) html += '<div class="mt-1">' + badge + returBadge + '</div>';
                            html += '</div>';

                            html += '</div>'; // .invoice-cell
                            return html;
                        }
                        return data;
                    }
                },
                { data: null, name: 'nama_pasien', render: function(data, type, row, meta) {
                        if (type === 'display') {
                            // nama_pasien / catatan_pasien arrive already HTML-escaped from the server
                            var name = row.nama_pasien || '';
                            var statusPasien = String(row.status_pasien || '').trim();
                            var statusAkses = String(row.status_akses || '').trim();

                            // Status icons, same style as Rawat Jalan index
                            function iconCircle(bg, icon, title) {
                                return '<span class="status-pasien-icon d-inline-flex align-items-center justify-content-center ml-1" style="width: 20px; height: 20px; background-color: ' + bg + '; border-radius: 50%;" title="' + title + '"><i class="fas ' + icon + ' text-white" style="font-size: 11px;"></i></span>';
                            }
                            var statusIcons = '';
                            var spLower = statusPasien.toLowerCase();
                            if (spLower.indexOf('vip') !== -1) statusIcons += iconCircle('#FFD700', 'fa-crown', 'VIP Member');
                            else if (spLower.indexOf('familia') !== -1) statusIcons += iconCircle('#32CD32', 'fa-users', 'Familia Member');
                            else if (spLower.indexOf('black') !== -1) statusIcons += iconCircle('#2F2F2F', 'fa-credit-card', 'Black Card Member');
                            else if (spLower.indexOf('red') !== -1) statusIcons += iconCircle('#FF0000', 'fa-exclamation-triangle', 'Red Flag');
                            var saLower = statusAkses.toLowerCase();
                            if (saLower.indexOf('akses cepat') !== -1 || saLower.indexOf('akses_cepat') !== -1 || saLower.indexOf('akses-cep') !== -1) {
                                statusIcons += iconCircle('#007BFF', 'fa-wheelchair', 'Akses Cepat');
                            }
                            if (row.employee_id) statusIcons += iconCircle('#10b981', 'fa-id-badge', 'Employee');

                            var newVisitBadge = parseInt(row.is_first_visit || 0, 10) === 1
                                ? ' <span class="badge badge-primary blinking ml-1" style="font-size:10px; line-height:1; padding:3px 6px; border-radius:999px; vertical-align:middle;" title="Visit pertama pasien">NEW</span>'
                                : '';

                            var html = '<div class="patient-name-cell d-flex flex-column">';
                            html += '<div class="d-inline-flex align-items-center font-weight-bold">' + name + newVisitBadge + statusIcons + '</div>';
                            // sub line: "No RM - notes" (both already HTML-escaped by the server)
                            var noRm = $.trim(String(row.no_rm || ''));
                            if (noRm === '-') noRm = '';
                            var catatan = $.trim(row.catatan_pasien || '');
                            var subLine = [noRm, catatan].filter(function(v) { return v !== ''; }).join(' - ');
                            if (subLine) html += '<small class="pasien-notes-preview">' + subLine + '</small>';

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
                            return '<div class="tanggal-cell"><span class="font-weight-bold">' + escapeHtml(dateText) + '</span></div>';
                        }
                        return data || row.tanggal_visit;
                    }
                },
                { data: 'metode_bayar_nama', name: 'metode_bayar_nama', orderable: false, searchable: false, render: function(data, type, row) {
                        var metode = data || '';
                        if (type !== 'display') return metode;
                        if (!metode || metode === '-') return '-';
                        var iconClass = String(metode).toLowerCase().indexOf('umum') !== -1 ? 'fas fa-money-bill-wave' : 'fas fa-credit-card';
                        return '<span class="d-inline-flex align-items-center"><i class="' + iconClass + ' mr-2"></i><span>' + metode + '</span></span>'; // metode is already escaped server-side
                    }
                },
                { data: 'referral_display', name: 'referral_display', orderable: false, searchable: false, render: function(data, type, row) {
                        return data || 'Walk-in';
                    }
                },
                { data: null, name: 'total_amount', orderable: false, searchable: false, className: 'no-wrap-cell', render: function(data, type, row, meta) {
                        var amounts = billingRowAmounts(row);
                        var totalVal = amounts.total;
                        if (type !== 'display') return totalVal;
                        if (totalVal <= 0) {
                            if (!row.invoice) return '-';
                            // zero total (e.g. free voucher): green once the invoice is settled (status "Lunas")
                            var zeroLunas = $.trim($('<div>').html(row.status || '').text()).toLowerCase() === 'lunas';
                            return '<div class="font-weight-bold' + (zeroLunas ? ' text-success' : '') + '">Rp 0</div>';
                        }
                        // total in green when fully paid (nothing remaining); the remaining amount has its own column
                        var isLunas = amounts.rem !== null && amounts.rem <= 0;
                        return '<div class="font-weight-bold' + (isLunas ? ' text-success' : '') + '">' + formatRupiah(totalVal) + '</div>';
                    }
                },
                { data: null, name: 'kekurangan', orderable: false, searchable: false, className: 'no-wrap-cell', render: function(data, type, row, meta) {
                        var amounts = billingRowAmounts(row);
                        var rem = (amounts.total > 0 && amounts.rem !== null && amounts.rem > 0) ? amounts.rem : 0;
                        if (type !== 'display') return rem;
                        if (rem <= 0) return '-';
                        return '<div class="font-weight-bold text-danger">' + formatRupiah(rem) + '</div>';
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


                            // Collect print links into a "Cetak" dropdown (Cetak Nota / Cetak Invoice)
                            var $printItems = $();
                            $container.find('a, button').each(function() {
                                var $el = $(this);
                                var s = ($el.attr('title') || '') + ' ' + ($el.text() || '');
                                var label = /cetak\s*nota\s*v?2/i.test(s) ? 'Cetak Invoice' : (/cetak\s*nota/i.test(s) ? 'Cetak Nota' : '');
                                if (!label) return;
                                var $item = $('<a class="dropdown-item billing-print-item"></a>').attr('href', $el.attr('href') || '#').text(label);
                                if ($el.attr('onclick')) $item.attr('onclick', $el.attr('onclick'));
                                $printItems = $printItems.add($item);
                            });

                            // Group the remaining action buttons into a btn-group for compact layout
                            var $buttons = $container.find('a, button').filter(function() {
                                var t = ($(this).text() || '').trim();
                                var title = ($(this).attr('title') || '').trim();
                                return !(/cetak\s*nota/i.test(t) || /cetak\s*nota/i.test(title));
                            });
                            if ($buttons.length || $printItems.length) {
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

                                if ($printItems.length) {
                                    var $printMenu = $('<div class="btn-group billing-print-menu" role="group">'
                                        + '<button type="button" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Cetak" aria-label="Cetak"><i class="ti-printer" aria-hidden="true"></i></button>'
                                        + '<div class="dropdown-menu dropdown-menu-right"></div>'
                                        + '</div>');
                                    $printMenu.find('.dropdown-menu').append($printItems);
                                    $group.append($printMenu);
                                }

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
            order: [[0, 'desc']]
            });
        }

        billingTableUmum = createBillingTable($('#datatable-billing-umum'), 'umum');
        billingTableAsuransi = createBillingTable($('#datatable-billing-asuransi'), 'asuransi', true);

        // Keep a global active pointer for lazy-loaded modal script compatibility
        window.billingTableUmum = billingTableUmum;
        window.billingTableAsuransi = billingTableAsuransi;
        setActiveBillingTableGlobal();

        // Raise the Aksi cell while its print menu is open so the next rows' pinned cells don't cover it
        $(document).on('show.bs.dropdown', '.billing-print-menu', function() {
            $(this).closest('td').addClass('billing-menu-open');
        }).on('hidden.bs.dropdown', '.billing-print-menu', function() {
            $(this).closest('td').removeClass('billing-menu-open');
        });

        // Adjust columns when switching tabs (DataTables needs this for hidden tables)
        $('#billingTabs a[data-toggle="tab"]').on('shown.bs.tab', function() {
            setActiveBillingTableGlobal();
            try {
                var t = window.billingTable;
                if (t) {
                    t.columns.adjust();
                    // the hidden tab isn't refreshed in the background (filters may have changed), so reload it from page 1
                    t.ajax.reload(null, true);
                }
            } catch(e) {}
        });

        // Auto-reload every 15 seconds: only the visible tab's table, and only while the browser tab is visible
        setInterval(function() {
            if (document.hidden || $('#modalBillingCreate').hasClass('show')) return; // paused while billing modal is open
            try {
                if (window.billingTable) window.billingTable.ajax.reload(null, false); // keep current page position
            } catch(e) {}
            fetchTabCounts();
        }, 15000); // 15000 milliseconds = 15 seconds

        // Catch up right away when the user comes back to this browser tab
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) return;
            try {
                if (window.billingTable) window.billingTable.ajax.reload(null, false);
            } catch(e) {}
            fetchTabCounts();
        });

        // Expose config used by lazy-loaded modal script
        window.financeBillingIndexConfig = {
            oldFinNotifsUrl: '{{ route("finance.notifications.old") }}',
            markFinReadBase: '{{ url("finance/notifications") }}',
            piutangReceiveBase: '{{ url('/finance/piutang') }}',
            ermPasiensSelect2Url: '{{ route("erm.pasiens.select2") }}',
            ermPasienMerchandiseBaseUrl: '{{ url("erm/pasiens") }}',
            ermVisitationsStoreUrl: '{{ route("erm.visitations.store") }}',
            ermVisitationsProdukStoreUrl: '{{ route("erm.visitations.produk.store") }}',
            ermVisitationsLabStoreUrl: '{{ route("erm.visitations.lab.store") }}',
            ermCekAntrianUrl: '{{ route("erm.visitations.cekAntrian") }}',
            marketingMasterMerchandiseDataUrl: '{{ route("marketing.master_merchandise.data") }}',
            marketingMasterMerchandiseBaseUrl: '{{ url("marketing/master-merchandise") }}',
            ermRawatjalanMerchandiseStockOutUrl: '{{ route("erm.rawatjalans.merchandise.stock-out") }}',
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

        // ---- Billing create in a modal (iframe with ?embed=1) ----
        function billingEmbedUrl(href) {
            var u = new URL(href, window.location.href);
            u.searchParams.set('embed', '1');
            return u.toString();
        }

        function openBillingCreateModal(href, title) {
            var $modal = $('#modalBillingCreate');
            $('#modalBillingCreateLabel').text(title || 'Billing');
            $('#billingCreateOpenTab').attr('href', href);
            $modal.find('.billing-create-loading').show();
            $('#billingCreateFrame').attr('src', billingEmbedUrl(href));
            $('body').addClass('billing-create-open');
            $modal.modal('show');
        }

        $('#billingCreateFrame').on('load', function() {
            if (this.getAttribute('src') !== 'about:blank') {
                $('#modalBillingCreate .billing-create-loading').hide();
            }
        });

        // Plain click opens the modal; Ctrl/Cmd/Shift+click and middle-click still open a new tab
        $(document).on('click', '.btn[title="Lihat Billing"], .btn-open-billing-modal', function(e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
            var href = $(this).attr('href') || $(this).data('href') || '';
            if (!href || href === '#') return;
            e.preventDefault();

            var title = 'Billing';
            try {
                var $tr = $(this).closest('tr');
                var dt = window.billingTable;
                var rowData = (dt && $tr.length) ? dt.row($tr).data() : null;
                if (rowData) {
                    var nama = $('<div>').html(rowData.nama_pasien || '').text();
                    var inv = rowData.invoice_number && rowData.invoice_number !== '-' ? ' - ' + rowData.invoice_number : '';
                    title = 'Billing: ' + nama + inv;
                } else if ($(this).hasClass('btn-open-billing-modal')) {
                    title = 'Event Billing: ' + $.trim($(this).clone().children().remove().end().text());
                }
            } catch (err) {}

            openBillingCreateModal(href, title);
        });

        $('#billingCreateCloseBtn').on('click', function() {
            $('#modalBillingCreate').modal('hide');
        });

        // The embedded page's own "Tutup" button asks us to close the modal
        window.addEventListener('message', function(e) {
            if (e.origin !== window.location.origin) return;
            if (e.data && e.data.type === 'billing-embed:close') {
                $('#modalBillingCreate').modal('hide');
            }
            // the embedded page shows its own loading overlay; avoid two spinners at once
            if (e.data && e.data.type === 'billing-embed:ready') {
                $('#modalBillingCreate .billing-create-loading').hide();
            }
        });

        $('#modalBillingCreate').on('hidden.bs.modal', function() {
            $('body').removeClass('billing-create-open');
            // unload the page so its timers/requests stop, then refresh the list and tab badges
            $('#billingCreateFrame').attr('src', 'about:blank');
            try {
                if (window.billingTable) window.billingTable.ajax.reload(null, false);
            } catch (err) {}
            fetchTabCounts();
        });

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
        $(document).on('click', '.billing-print-item', function (e) {
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

        $(document).on('click', '#btn-merchandise-stock-out', function (e) {
            if (!window.__billingIndexLazyAssetsReady) {
                e.preventDefault();
                e.stopImmediatePropagation();
                ensureBillingIndexLazyAssets().then(function () {
                    if (window.financeBillingIndexModals && typeof window.financeBillingIndexModals.openMerchandiseStockOutModal === 'function') {
                        window.financeBillingIndexModals.openMerchandiseStockOutModal();
                    }
                });
                return;
            }

            if (window.financeBillingIndexModals && typeof window.financeBillingIndexModals.openMerchandiseStockOutModal === 'function') {
                e.preventDefault();
                window.financeBillingIndexModals.openMerchandiseStockOutModal();
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

@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-4 col-12">
                            <h4 class="card-title mb-0">Daftar Billing</h4>
                        </div>
                        <div class="col-md-8 col-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: .5rem;">
                                <div class="d-flex align-items-center" style="flex:0 0 auto;">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Header actions">
                                        <button id="btn-send-farmasi-notif" class="btn btn-primary" title="Kirim Notif ke Farmasi"><i class="fas fa-bell me-1"></i> Kirim Notif ke Farmasi</button>
                                        <button id="btn-old-notifs-finance" type="button" class="btn btn-light" title="Lihat Notifikasi Lama">
                                            <span style="color:#007bff; font-size:14px;">&#10084;</span>
                                        </button>
                                    </div>
                                </div>
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
                                            <option value="belum">Belum Dibayar</option>
                                            <option value="belum_lunas">Belum Lunas</option>
                                            <option value="sudah">Sudah Bayar</option>
                                            <option value="piutang">Piutang</option>
                                            <option value="">Semua Status</option>
                                        </select>
                                </div>
                                <div class="d-flex align-items-center" style="flex:0 0 auto;">
                                    <div class="form-check d-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" value="1" id="show-deleted">
                                        <label class="form-check-label small ml-2 mb-0" for="show-deleted">Tampilkan Terhapus</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
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
                                                /* Ensure specialization (small) inside dokter-cell is not bold */
                                                .dokter-cell small { font-weight: 400 !important; }
                                                /* Make patient RM muted and normal weight */
                                                .patient-name-cell small { font-weight: 400; color: #6c757d; }
                                            </style>

                                            <table id="datatable-billing" class="table table-bordered table-hover table-striped dt-responsive" style="width:100%;">
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
                    <!-- Modal: Old Notifications (Finance) -->
                    <div class="modal fade" id="modalOldNotificationsFinance" tabindex="-1" role="dialog" aria-labelledby="modalOldNotificationsFinanceLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalOldNotificationsFinanceLabel">Notifikasi Lama (Finance)</h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-mark-all-finance" style="margin-right:1rem;">Tandai semua sudah dibaca</button>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div id="old-fin-notifs-loading" style="display:none; text-align:center; padding:20px;">
                                        <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                                    </div>
                                    <div id="old-fin-notifs-empty" style="display:none; text-align:center; color:#666;">Belum ada notifikasi lama.</div>
                                    <ul class="list-group" id="old-fin-notifs-list"></ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal: PDF Preview -->
                    <div class="modal fade" id="modalPdfPreview" tabindex="-1" role="dialog" aria-labelledby="modalPdfPreviewLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document" style="max-width:95%;">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalPdfPreviewLabel">Preview PDF</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" style="padding:0; min-height:60vh;">
                                    <div id="pdf-preview-loading" style="text-align:center; padding:1.5rem; display:none;"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>
                                    <div id="pdf-preview-container" style="width:100%; height:80vh;">
                                        <!-- iframe inserted here -->
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                                    <a id="pdf-preview-download" class="btn btn-primary" href="#" target="_blank">Buka di Tab Baru</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal: Terima Pembayaran (from Piutang page) -->
                    <div class="modal fade" id="modalTerimaPembayaran" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-md" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Terima Pembayaran</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <form id="form-terima-pembayaran">
                                        <input type="hidden" name="piutang_id" id="piutang_id">
                                        <div class="mb-2">
                                            <label>Invoice</label>
                                            <input type="text" id="piutang_invoice" class="form-control" readonly>
                                        </div>
                                        <!-- Kekurangan moved to inline label next to Jumlah -->
                                        <div class="mb-2">
                                            <label>Jumlah (Rp) <small id="piutang_kekurangan_label" class="ml-2 text-danger"></small></label>
                                            <input type="number" step="0.01" name="amount" id="piutang_amount" class="form-control" required>
                                        </div>
                                        <div class="mb-2">
                                            <label>Tanggal Bayar</label>
                                            <input type="datetime-local" name="payment_date" id="piutang_payment_date" class="form-control" required>
                                        </div>
                                        <div class="mb-2">
                                            <label>Metode Pembayaran</label>
                                            <select name="payment_method" id="piutang_payment_method" class="form-control">
                                                <option value="cash">Tunai</option>
                                                <option value="piutang">Piutang</option>
                                                <option value="edc_bca">EDC BCA</option>
                                                <option value="edc_bni">EDC BNI</option>
                                                <option value="edc_bri">EDC BRI</option>
                                                <option value="edc_mandiri">EDC Mandiri</option>
                                                <option value="qris">QRIS</option>
                                                <option value="transfer">Transfer</option>
                                                <option value="shopee">Shopee</option>
                                                <option value="tiktokshop">Tiktokshop</option>
                                                <option value="tokopedia">Tokopedia</option>
                                                <option value="asuransi_inhealth">Asuransi InHealth</option>
                                                <option value="asuransi_brilife">Asuransi Brilife</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="button" id="btn-submit-terima" class="btn btn-primary">Simpan Pembayaran</button>
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
<script>
    $(document).ready(function() {
        // Set up date variables
        var today = moment().format('YYYY-MM-DD');
        var startDate = today;
        var endDate = today;
        var dokterId = '';
        var klinikId = '';
       var statusFilter = 'belum';
        
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
            billingTable.ajax.reload();
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
            billingTable.ajax.reload();
        });

       $('#filter-status').on('change', function() {
           statusFilter = $(this).val();
           billingTable.ajax.reload();
       });
        
        // Initialize DataTable with date and filter
        var billingTable = $('#datatable-billing').DataTable({
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
                    d.include_deleted = $('#show-deleted').is(':checked') ? 1 : 0;
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
                            var html = '<div class="patient-name-cell">';
                            html += '<div class="font-weight-bold">' + escapeHtml(name) + '</div>';
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

                            if (noRm || (metodeName && String(metodeName).trim() !== '')) {
                                html += '<div class="mt-1 d-flex align-items-center">';
                                if (metodeName && String(metodeName).trim() !== '') {
                                    html += '<span class="badge badge-info">' + escapeHtml(String(metodeName)) + '</span>';
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
                                    var amt = Number(piutangRel.amount || piutangRel.total || piutangRel.nominal || 0) || 0;
                                    var paidAmt = Number(piutangRel.paid_amount || piutangRel.paid || piutangRel.amount_paid || 0) || 0;
                                    if (paidAmt >= amt && amt > 0) {
                                        badge = '<span class="badge badge-success">Piutang Sudah Bayar</span>';
                                    } else if (paidAmt > 0 && paidAmt < amt) {
                                        badge = '<span class="badge badge-warning">Piutang Belum Lunas</span>';
                                    } else {
                                        badge = '<span class="badge badge-danger">Piutang</span>';
                                    }
                                } else {
                                    var plainFromServer = $('<div>').html(statusHtml).text() || '';
                                    var sLower = String(plainFromServer).toLowerCase();
                                    if (sLower.indexOf('sudah') !== -1 || sLower.indexOf('lunas') !== -1) {
                                        badge = '<span class="badge badge-success">Piutang Sudah Bayar</span>';
                                    } else {
                                        badge = '<span class="badge badge-warning">Piutang</span>';
                                    }
                                }
                            } else if (statusHtml) {
                                var plain = $('<div>').html(statusHtml).text();
                                var s = String(plain).toLowerCase();
                                var cls = 'badge-secondary';
                                if (s.indexOf('belum') !== -1 && s.indexOf('lunas') !== -1) {
                                    cls = 'badge-warning';
                                } else if (s.indexOf('sudah') !== -1 || (s.indexOf('lunas') !== -1 && s.indexOf('belum') === -1)) {
                                    cls = 'badge-success';
                                } else if (s.indexOf('belum') !== -1) {
                                    cls = 'badge-danger';
                                }
                                badge = '<span class="badge ' + cls + '">' + escapeHtml(plain) + '</span>';
                            }

                            // Build invoice cell with invoice number + badge stacked, and a right-aligned three-dots dropdown
                            var html = '<div class="invoice-cell d-flex align-items-center justify-content-between">';
                            html += '<div class="invoice-left">';
                            html += '<div class="font-weight-bold">' + escapeHtml(inv) + '</div>';
                            if (badge) html += '<div class="mt-1">' + badge + '</div>';
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
                                if (/lihat\s*billing/i.test(text)) { $el.html('<i class="ti-eye" aria-hidden="true"></i> Billing'); $el.attr('title', 'Lihat Billing'); }
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

                                // If this row represents a piutang (credit) invoice, append a Terima button
                                var isPiutang = false;
                                try {
                                    if (row && row.payment_method && String(row.payment_method).toLowerCase() === 'piutang') isPiutang = true;
                                    if (!isPiutang && row && (row.piutang || row.saldo || row.amount_due || row.piutang_id)) isPiutang = true;
                                } catch (e) { isPiutang = false; }

                                if (isPiutang) {
                                    // Prefer nested invoice.piutangs data if present (server-side relation)
                                    var piutangId = '';
                                    var amount = 0;
                                    var paid = 0;
                                    var invoice = '';
                                    try {
                                        if (row && row.invoice) {
                                            invoice = row.invoice.invoice_number || row.invoice_number || invoice;
                                            var piutangs = row.invoice.piutangs || (row.invoice.piutang ? [row.invoice.piutang] : null);
                                            if (Array.isArray(piutangs) && piutangs.length > 0) {
                                                var p = piutangs[0];
                                                piutangId = p.id || piutangId;
                                                amount = (p.amount !== undefined && p.amount !== null) ? p.amount : amount;
                                                paid = (p.paid_amount !== undefined && p.paid_amount !== null) ? p.paid_amount : paid;
                                            }
                                        }
                                    } catch (e) { /* ignore */ }
                                    // Fallbacks if nested data not available
                                    piutangId = piutangId || (row && (row.piutang_id || (row.piutang && row.piutang.id))) || row.id || '';
                                    amount = amount || (row && (row.piutang_amount || row.saldo || row.amount_due || row.total || row.amount)) || 0;
                                    paid = paid || (row && (row.paid_amount || row.paid || row.amount_paid || 0)) || 0;
                                    // Only show Terima button if there is remaining amount to receive
                                    var remainingPiutang = Number(amount) - Number(paid);
                                    if (!isFinite(remainingPiutang) || remainingPiutang < 0) remainingPiutang = 0;
                                    if (remainingPiutang > 0) {
                                        var $terima = $('<button type="button" class="btn btn-sm btn-success btn-terima-pembayaran"></button>');
                                        $terima.attr('data-id', piutangId);
                                        $terima.attr('data-amount', amount);
                                        $terima.attr('data-paid', paid);
                                        $terima.attr('data-invoice', invoice);
                                        // add icon + text inside button
                                        $terima.html('<i class="ti-wallet" aria-hidden="true"></i> <span class="ml-1">Terima</span>');
                                        // try to place Terima to the right side of the Billing button if exists
                                        var $billingBtn = $group.find('a,button').filter(function() {
                                            var t = ($(this).attr('title') || '').toLowerCase();
                                            var txt = ($(this).text() || '').toLowerCase();
                                            if (t.indexOf('lihat billing') !== -1) return true;
                                            if (txt.indexOf('billing') !== -1 && $(this).find('i.ti-eye').length>0) return true;
                                            return false;
                                        }).first();
                                        if ($billingBtn && $billingBtn.length) {
                                            $billingBtn.after($terima);
                                        } else {
                                            $group.append($terima);
                                        }
                                    }
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
            order: [[3, 'desc']]
        });

        // Auto-reload DataTable every 15 seconds
        setInterval(function() {
            billingTable.ajax.reload(null, false); // false keeps current page position
        }, 15000); // 15000 milliseconds = 15 seconds

        // Toggle show deleted
        $('#show-deleted').on('change', function() {
            billingTable.ajax.reload();
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
                        billingTable.ajax.reload();
                    }).fail(function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });

        // Finance: Old Notifications modal handlers
        var oldFinNotifsUrl = '{{ route("finance.notifications.old") }}';
        var markFinReadBase = '{{ url("finance/notifications") }}';
        var csrfToken = '{{ csrf_token() }}';

        $(document).on('click', '#btn-old-notifs-finance', function () {
            $('#modalOldNotificationsFinance').modal('show');
            loadOldFinNotifications();
        });

        function loadOldFinNotifications() {
            $('#old-fin-notifs-list').empty();
            $('#old-fin-notifs-empty').hide();
            $('#old-fin-notifs-loading').show();

            $.get(oldFinNotifsUrl, function (res) {
                $('#old-fin-notifs-loading').hide();
                var items = Array.isArray(res) ? res : (res && Array.isArray(res.data) ? res.data : []);
                if (!items || items.length === 0) {
                    $('#old-fin-notifs-empty').show();
                    return;
                }

                items.forEach(function (n) {
                    var message = n.message || n.title || n.text || JSON.stringify(n);
                    var time = n.created_at || '';
                    var $li = $("<li class='list-group-item d-flex justify-content-between align-items-start'></li>");
                    var left = '<div class="notif-content">';
                    if (n.read) left += '<div class="text-muted">' + escapeHtml(message) + '</div>';
                    else left += '<div class="font-weight-bold">' + escapeHtml(message) + '</div>';
                    if (time) left += '<small class="text-muted">' + escapeHtml(time) + '</small>';
                    left += '</div>';

                    var right = '<div class="notif-actions">';
                    if (!n.read) {
                        right += '<button class="btn btn-sm btn-primary btn-mark-read-fin" data-id="' + n.id + '">Tandai sudah dibaca</button>';
                    } else {
                        right += '<span class="badge badge-secondary">Sudah dibaca</span>';
                    }
                    right += '</div>';

                    $li.html(left + right);
                    $('#old-fin-notifs-list').append($li);
                });
            }).fail(function () {
                $('#old-fin-notifs-loading').hide();
                $('#old-fin-notifs-empty').text('Gagal memuat notifikasi.').show();
            });
        }

        $(document).on('click', '.btn-mark-read-fin', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var id = $btn.data('id');
            if (!id) return;
            $btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: markFinReadBase + '/' + id + '/mark-read',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function (res) {
                    if (res && res.success) loadOldFinNotifications();
                    else { alert('Gagal menandai notifikasi.'); $btn.prop('disabled', false).text('Tandai sudah dibaca'); }
                },
                error: function () { alert('Gagal menandai notifikasi.'); $btn.prop('disabled', false).text('Tandai sudah dibaca'); }
            });
        });

        // Mark all read
        $(document).on('click', '#btn-mark-all-finance', function () {
            if (!confirm('Tandai semua notifikasi sebagai sudah dibaca?')) return;
            // fetch ids then post each (simple approach)
            $.get(oldFinNotifsUrl, function (res) {
                var items = Array.isArray(res) ? res : (res && Array.isArray(res.data) ? res.data : []);
                var unread = items.filter(function(i) { return !i.read; });
                if (!unread.length) { alert('Tidak ada notifikasi belum dibaca.'); return; }
                var requests = [];
                unread.forEach(function (n) {
                    requests.push($.ajax({ url: markFinReadBase + '/' + n.id + '/mark-read', method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } }));
                });
                $.when.apply($, requests).always(function() { loadOldFinNotifications(); });
            });
        });

        function escapeHtml(unsafe) {
            return String(unsafe).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        // Intercept Cetak actions inside invoice dropdown and show PDF preview in modal
        $(document).on('click', '.invoice-cell .dropdown-item', function(e) {
            var $el = $(this);
            var txt = ($el.text() || '').trim();
            if (!/cetak/i.test(txt)) return; // not a print item
            e.preventDefault();
            var href = $el.attr('href') || $el.data('href') || '';
            var onclick = $el.attr('onclick') || '';

            // If we have a valid href (not '#'), load it in iframe preview
            if (href && href !== '#') {
                $('#pdf-preview-container').empty();
                $('#pdf-preview-loading').show();
                $('#pdf-preview-download').attr('href', href).show();

                // Create iframe and append
                var $iframe = $('<iframe>', {
                    src: href,
                    style: 'width:100%; height:80vh; border:0;'
                });
                // When iframe loads, hide spinner
                $iframe.on('load', function() { $('#pdf-preview-loading').hide(); });
                $('#pdf-preview-container').append($iframe);
                $('#modalPdfPreview').modal('show');
                return;
            }

            // Fallback: if onclick handler exists, try to execute it (best-effort)
            if (onclick) {
                try {
                    // attempt to execute inline onclick (keep original context)
                    var fn = new Function(onclick);
                    fn.call(this);
                } catch (err) {
                    console.error('Failed to execute onclick preview:', err);
                }
            }
        });

        // Clear iframe when modal hidden to free memory
        $('#modalPdfPreview').on('hidden.bs.modal', function() {
            $('#pdf-preview-container').empty();
            $('#pdf-preview-loading').hide();
            $('#pdf-preview-download').attr('href', '#').hide();
        });

        // Handle opening the Terima Pembayaran modal when clicking Terima on billing page
        $(document).on('click', '.btn-terima-pembayaran', function() {
            var id = $(this).data('id');
            var amount = $(this).data('amount');
            var paid = $(this).data('paid') || 0;
            var invoice = $(this).data('invoice');
            // helper to parse currency/number strings into a float
            function parseMoney(val) {
                if (val === null || val === undefined) return 0;
                if (typeof val === 'number') return val;
                var s = String(val).trim();
                if (!s) return 0;
                // remove currency letters and spaces
                s = s.replace(/[^0-9.,-]/g, '');
                // if both dot and comma exist, assume dot thousand separator and comma decimal (e.g. 1.234,56)
                if (s.indexOf('.') !== -1 && s.indexOf(',') !== -1) {
                    s = s.replace(/\./g, ''); // remove thousand sep
                    s = s.replace(/,/g, '.'); // decimal separator
                } else if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) {
                    // assume comma is decimal separator
                    s = s.replace(/,/g, '.');
                } else {
                    // leave dots as-is (dot decimal or integer)
                }
                var f = parseFloat(s);
                return isNaN(f) ? 0 : f;
            }
            // compute remaining robustly
            var amtNum = parseMoney(amount);
            var paidNum = parseMoney(paid);
            var remaining = amtNum - paidNum;
            if (!isFinite(remaining) || remaining < 0) remaining = 0;
            // format currency for display (ID locale)
            function formatRupiah(num) {
                try { return 'Rp ' + Number(num).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}); }
                catch(e) { return num; }
            }
            // prefill jumlah with already paid amount (so user sees paid total)
            $('#piutang_amount').val(paidNum || 0);
            // update inline label (format: "kurang : RP 10.000") and color
            var $label = $('#piutang_kekurangan_label');
            function formatKekuranganLabelValue(num) {
                try { return 'kurang : RP ' + Number(num).toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:0}); }
                catch(e) { return 'kurang : RP ' + num; }
            }
            function updateKekuranganLabel(rem) {
                if (!isFinite(rem) || rem <= 0) {
                    $label.removeClass('text-danger').addClass('text-success').text('LUNAS');
                } else {
                    $label.removeClass('text-success').addClass('text-danger').text(formatKekuranganLabelValue(rem));
                }
            }
            updateKekuranganLabel(remaining);

            // bind input handler to update kekurangan when jumlah changes
            $('#piutang_amount').off('input.piutang').on('input.piutang', function() {
                var entered = parseMoney($(this).val());
                var newRem = amtNum - (paidNum + (isNaN(entered) ? 0 : entered));
                if (!isFinite(newRem) || newRem < 0) newRem = 0;
                updateKekuranganLabel(newRem);
            });
            $('#piutang_id').val(id);
            $('#piutang_invoice').val(invoice);
            // default payment date to now
            var now = new Date();
            var pad = function(n){return n<10?'0'+n:n};
            var local = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
            $('#piutang_payment_date').val(local);
            $('#modalTerimaPembayaran').modal('show');
        });

        // Submit Terima Pembayaran (same behavior as Piutang page)
        $('#btn-submit-terima').on('click', function() {
            var id = $('#piutang_id').val();
            if (!id) return;
            var payload = {
                amount: $('#piutang_amount').val(),
                payment_date: $('#piutang_payment_date').val(),
                payment_method: $('#piutang_payment_method').val(),
                _token: csrfToken
            };
            $.post('{{ url('/finance/piutang') }}' + '/' + id + '/receive', payload)
                .done(function(res) {
                    if (res && res.success) {
                        $('#modalTerimaPembayaran').modal('hide');
                        billingTable.ajax.reload(null, false);
                        Swal.fire('Sukses', res.message || 'Pembayaran tercatat', 'success');
                    } else {
                        Swal.fire('Gagal', res.message || 'Gagal menyimpan pembayaran', 'error');
                    }
                }).fail(function(xhr) {
                    var msg = 'Terjadi kesalahan';
                    try { msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : msg; } catch(e){}
                    Swal.fire('Gagal', msg, 'error');
                });
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
                        billingTable.ajax.reload();
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
                        billingTable.ajax.reload();
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
    });
</script>
@endsection
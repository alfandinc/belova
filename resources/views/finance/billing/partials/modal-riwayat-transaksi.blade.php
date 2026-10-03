{{--
    Riwayat Transaksi (Billing page): cashier money in / out.
    One toolbar (period, Masuk/Keluar, search, download), one summary strip with per-method chips (click = filter),
    and a compact table. Opens on "Hari Ini"; loads on first open and only refreshes while the modal is open.
    Admin also gets "Backfill Kembalian Lama" behind a toggle.
--}}
@php($rtIsAdmin = auth()->check() && auth()->user()->hasRole('Admin'))
<style>
    #modalRiwayatTransaksi .modal-body { padding-top: .75rem; }
    #modalRiwayatTransaksi .rt-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    #modalRiwayatTransaksi .rt-toolbar .btn-group .btn { white-space: nowrap; }
    #modalRiwayatTransaksi .rt-search { flex: 1 1 200px; min-width: 180px; }
    #modalRiwayatTransaksi .rt-daterange { flex: 0 0 210px; }
    #modalRiwayatTransaksi .rt-summary { display: flex; flex-wrap: wrap; align-items: stretch; gap: .5rem; margin: .75rem 0; }
    #modalRiwayatTransaksi .rt-total { flex: 1 1 150px; border: 1px solid rgba(127,127,127,.2); border-radius: 8px; padding: .4rem .75rem; }
    #modalRiwayatTransaksi .rt-total .rt-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; opacity: .7; }
    #modalRiwayatTransaksi .rt-total .rt-value { font-size: 1.15rem; font-weight: 700; line-height: 1.3; }
    #modalRiwayatTransaksi .rt-methods { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .75rem; }
    #modalRiwayatTransaksi .rt-chip { border: 1px solid rgba(127,127,127,.3); border-radius: 999px; padding: .2rem .7rem; font-size: .8rem; background: transparent; color: inherit; cursor: pointer; line-height: 1.5; }
    #modalRiwayatTransaksi .rt-chip:hover { border-color: #1761fd; }
    #modalRiwayatTransaksi .rt-chip.active { background: #1761fd; border-color: #1761fd; color: #fff; }
    #modalRiwayatTransaksi .rt-chip .rt-chip-amt { font-weight: 700; margin-left: .25rem; }
    #modalRiwayatTransaksi #rt-datatable td { vertical-align: middle; }
    #modalRiwayatTransaksi .rt-desc { font-size: .8rem; opacity: .75; white-space: normal; min-width: 220px; }
    #modalRiwayatTransaksi .rt-amount { font-weight: 700; white-space: nowrap; text-align: right; }
</style>

<div class="modal fade" id="modalRiwayatTransaksi" tabindex="-1" role="dialog" aria-labelledby="modalRiwayatTransaksiTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modalRiwayatTransaksiTitle"><i class="fas fa-receipt mr-2"></i>Riwayat Transaksi</h5>
                <div class="d-flex align-items-center">
                    @if($rtIsAdmin)
                    <button type="button" class="btn btn-sm btn-link text-muted mr-2" id="rt-btn-toggle-backfill" title="Backfill kembalian lama (Admin)"><i class="fas fa-history mr-1"></i>Backfill</button>
                    @endif
                    <button type="button" class="close ml-0" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>
            <div class="modal-body">
                @if($rtIsAdmin)
                <div id="rt-backfill" class="border rounded px-3 py-2 mb-3" style="display:none;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:.75rem;">
                        <div>
                            <div class="font-weight-bold">Backfill Kembalian Lama</div>
                            <div class="text-muted small">Cari invoice dengan kembalian yang belum tercatat sebagai transaksi keluar, tinjau, lalu proses.</div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
                            <div style="flex:0 0 220px;"><input type="text" id="rt-backfill-daterange" class="form-control form-control-sm" readonly></div>
                            <div class="custom-control custom-checkbox mr-1">
                                <input type="checkbox" class="custom-control-input" id="rt-backfill-cash-only">
                                <label class="custom-control-label small pt-1" for="rt-backfill-cash-only">Cash only</label>
                            </div>
                            <button type="button" id="rt-btn-generate-backfill" class="btn btn-warning btn-sm">Cek</button>
                        </div>
                    </div>
                    <div id="rt-backfill-preview" class="mt-2" style="display:none;">
                        <div class="small mb-2">
                            <span id="rt-backfill-range" class="font-weight-bold">-</span>
                            <span id="rt-backfill-filter" class="text-muted ml-1"></span>
                            &middot; <span id="rt-backfill-count" class="font-weight-bold">0</span> transaksi
                            &middot; kembalian <span id="rt-backfill-total" class="font-weight-bold text-danger">Rp 0</span>
                        </div>
                        <div id="rt-backfill-empty" class="alert alert-light border py-2 mb-2" style="display:none;">Tidak ada kembalian yang perlu dibackfill pada filter ini.</div>
                        <div id="rt-backfill-table-wrapper" class="table-responsive mb-2" style="display:none; max-height:260px; overflow:auto;">
                            <table class="table table-bordered table-sm mb-0">
                                <thead><tr><th>No</th><th>Tanggal Bayar</th><th>Pasien</th><th>Invoice</th><th>Metode</th><th class="text-right">Kembalian</th><th>Deskripsi</th></tr></thead>
                                <tbody id="rt-backfill-body"></tbody>
                            </table>
                        </div>
                        <div class="text-right">
                            <button type="button" id="rt-btn-cancel-backfill" class="btn btn-light btn-sm">Batal</button>
                            <button type="button" id="rt-btn-process-backfill" class="btn btn-primary btn-sm" disabled>Proses Backfill</button>
                        </div>
                    </div>
                </div>
                @endif

                <div class="rt-toolbar">
                    <div class="btn-group btn-group-sm" role="group" id="rt-period">
                        <button type="button" class="btn btn-outline-primary active" data-period="today">Hari Ini</button>
                        <button type="button" class="btn btn-outline-primary" data-period="yesterday">Kemarin</button>
                        <button type="button" class="btn btn-outline-primary" data-period="week">Minggu Ini</button>
                        <button type="button" class="btn btn-outline-primary" data-period="month">Bulan Ini</button>
                    </div>
                    <div class="rt-daterange"><input type="text" id="rt-daterange" class="form-control form-control-sm" readonly title="Rentang tanggal"></div>
                    <div class="btn-group btn-group-sm" role="group" id="rt-jenis">
                        <button type="button" class="btn btn-outline-secondary active" data-jenis="">Semua</button>
                        <button type="button" class="btn btn-outline-success" data-jenis="in">Masuk</button>
                        <button type="button" class="btn btn-outline-danger" data-jenis="out">Keluar</button>
                    </div>
                    <div class="rt-search">
                        <input type="search" id="rt-search" class="form-control form-control-sm" placeholder="Cari pasien, No RM, invoice, keterangan...">
                    </div>
                    <button type="button" id="rt-btn-download" class="btn btn-success btn-sm" title="Download Excel (filter saat ini)"><i class="fas fa-file-excel mr-1"></i>Excel</button>
                </div>

                <div class="rt-summary">
                    <div class="rt-total"><div class="rt-label">Masuk</div><div class="rt-value text-success" id="rt-summary-in">Rp 0</div></div>
                    <div class="rt-total"><div class="rt-label">Keluar</div><div class="rt-value text-danger" id="rt-summary-out">Rp 0</div></div>
                    <div class="rt-total"><div class="rt-label">Bersih</div><div class="rt-value text-primary" id="rt-summary-balance">Rp 0</div></div>
                    <div class="rt-total"><div class="rt-label">Transaksi</div><div class="rt-value" id="rt-summary-count">0</div></div>
                </div>

                <div class="rt-methods" id="rt-methods"></div>

                <div class="table-responsive">
                    <table id="rt-datatable" class="table table-hover table-sm mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pasien</th>
                                <th>Invoice</th>
                                <th>Metode</th>
                                <th class="text-right">Jumlah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    var $modal = $('#modalRiwayatTransaksi');
    if (!$modal.length) return;

    var rtStart = moment().format('YYYY-MM-DD');
    var rtEnd = rtStart;
    var rtJenis = '';
    var rtMetode = '';
    var rtTable = null;
    var rtRefreshTimer = null;

    var METODE_LABELS = {
        cash: 'Tunai', piutang: 'Piutang', qris: 'QRIS', transfer: 'Transfer',
        edc_bca: 'EDC BCA', edc_bni: 'EDC BNI', edc_bri: 'EDC BRI', edc_mandiri: 'EDC Mandiri',
        shopee: 'Shopee', tiktokshop: 'Tiktokshop', tokopedia: 'Tokopedia',
        asuransi_inhealth: 'Asuransi InHealth', asuransi_brilife: 'Asuransi Brilife',
        asuransi_admedika: 'Asuransi Admedika', asuransi_bcalife: 'Asuransi BCA Life'
    };
    function metodeLabel(value) {
        if (!value || value === '-') return '-';
        return METODE_LABELS[value] || String(value).replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    var pickerLocale = {
        format: 'DD MMM YYYY',
        separator: ' - ',
        applyLabel: 'Pilih',
        cancelLabel: 'Batal',
        customRangeLabel: 'Custom',
        daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
        monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        firstDay: 1
    };

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    // ---- summary + per-method chips ----
    var statsXhr = null;
    function fetchStats() {
        if (statsXhr && statsXhr.readyState !== 4) { try { statsXhr.abort(); } catch (e) {} }
        statsXhr = $.get('{{ route("finance.transactions.stats") }}', {
            start_date: rtStart, end_date: rtEnd, jenis_transaksi: rtJenis, metode_bayar: rtMetode
        }).done(function (res) {
            res = res || {};
            $('#rt-summary-in').text(formatRupiah(res.total_in));
            $('#rt-summary-out').text(formatRupiah(res.total_out));
            $('#rt-summary-balance').text(formatRupiah(res.balance));
            $('#rt-summary-count').text(Number(res.count || 0).toLocaleString('id-ID'));
            renderMethodChips(res.by_method || []);
        });
    }

    function renderMethodChips(methods) {
        if (!methods.length) {
            $('#rt-methods').html('');
            return;
        }
        var html = '<button type="button" class="rt-chip' + (rtMetode === '' ? ' active' : '') + '" data-metode="">Semua Metode</button>';
        methods.forEach(function (m) {
            var net = Number(m.total_in || 0) - Number(m.total_out || 0);
            html += '<button type="button" class="rt-chip' + (rtMetode === m.metode ? ' active' : '') + '" data-metode="' + escapeHtml(m.metode) + '"'
                + ' title="Masuk ' + escapeHtml(formatRupiah(m.total_in)) + ' · Keluar ' + escapeHtml(formatRupiah(m.total_out)) + ' · ' + Number(m.count || 0) + ' transaksi">'
                + escapeHtml(metodeLabel(m.metode)) + '<span class="rt-chip-amt">' + escapeHtml(formatRupiah(net)) + '</span></button>';
        });
        $('#rt-methods').html(html);
    }

    $('#rt-methods').on('click', '.rt-chip', function () {
        rtMetode = String($(this).data('metode') || '');
        $('#rt-methods .rt-chip').removeClass('active');
        $(this).addClass('active');
        reloadAll();
    });

    // ---- table ----
    function initTable() {
        rtTable = $('#rt-datatable').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 400,
            pageLength: 25,
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"<"small text-muted"i><"d-flex align-items-center"lp>>',
            ajax: {
                url: '{{ route("finance.transactions.data") }}',
                data: function (d) {
                    d.start_date = rtStart;
                    d.end_date = rtEnd;
                    d.jenis_transaksi = rtJenis;
                    d.metode_bayar = rtMetode;
                }
            },
            columns: [
                { data: 'tanggal', name: 'tanggal', render: function (data, type, row) {
                    if (type !== 'display') return data;
                    return '<div class="font-weight-bold">' + escapeHtml(row.tanggal_jam || '-') + '</div><div class="small text-muted text-nowrap">' + escapeHtml(row.tanggal_tgl || '') + '</div>';
                } },
                { data: 'pasien_nama', name: 'visitation_id', orderable: false, render: function (data, type, row) {
                    if (type !== 'display') return data;
                    if (!data) return '<span class="text-muted">-</span>';
                    return '<div class="font-weight-bold">' + escapeHtml(data) + '</div>' + (row.pasien_id ? '<div class="small text-muted">' + escapeHtml(row.pasien_id) + '</div>' : '');
                } },
                { data: 'invoice_number', name: 'invoice_id', render: function (data, type) {
                    if (type !== 'display') return data;
                    return data ? '<span class="text-nowrap">' + escapeHtml(data) + '</span>' : '<span class="text-muted">-</span>';
                } },
                { data: 'metode_bayar', name: 'metode_bayar', render: function (data, type) {
                    if (type !== 'display') return data;
                    return '<span class="badge badge-light border">' + escapeHtml(metodeLabel(data)) + '</span>';
                } },
                { data: 'jumlah', name: 'jumlah', className: 'rt-amount', render: function (data, type, row) {
                    if (type !== 'display') return data;
                    var out = String(row.jenis_transaksi || '').toLowerCase() === 'out';
                    return '<span class="' + (out ? 'text-danger' : 'text-success') + '" title="' + (out ? 'Uang keluar' : 'Uang masuk') + '">' + (out ? '− ' : '+ ') + escapeHtml(formatRupiah(data)) + '</span>';
                } },
                { data: 'deskripsi', name: 'deskripsi', orderable: false, render: function (data, type) {
                    if (type !== 'display') return data;
                    return data ? '<div class="rt-desc">' + escapeHtml(data) + '</div>' : '<span class="text-muted">-</span>';
                } }
            ],
            order: [[0, 'desc']],
            language: {
                emptyTable: 'Belum ada transaksi pada periode ini',
                zeroRecords: 'Tidak ada transaksi yang cocok',
                info: '_START_–_END_ dari _TOTAL_ transaksi',
                infoEmpty: '0 transaksi',
                infoFiltered: '',
                processing: 'Memuat...',
                lengthMenu: '_MENU_',
                paginate: { previous: '‹', next: '›' }
            }
        });
    }

    var reloadTimer = null;
    function reloadAll() {
        // coalesce quick successive filter clicks into one request
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function () {
            if (rtTable) rtTable.ajax.reload();
            fetchStats();
        }, 50);
    }

    // ---- toolbar ----
    function setRange(start, end) {
        rtStart = start.format('YYYY-MM-DD');
        rtEnd = end.format('YYYY-MM-DD');
        var picker = $('#rt-daterange').data('daterangepicker');
        if (picker) { picker.setStartDate(start); picker.setEndDate(end); }
    }

    var PERIODS = {
        today: function () { return [moment(), moment()]; },
        yesterday: function () { return [moment().subtract(1, 'day'), moment().subtract(1, 'day')]; },
        week: function () { return [moment().startOf('isoWeek'), moment()]; },
        month: function () { return [moment().startOf('month'), moment()]; }
    };

    $('#rt-period').on('click', '[data-period]', function () {
        var range = PERIODS[$(this).data('period')]();
        $('#rt-period .btn').removeClass('active');
        $(this).addClass('active');
        setRange(range[0], range[1]);
        reloadAll();
    });

    $('#rt-daterange').daterangepicker({
        startDate: moment(),
        endDate: moment(),
        locale: pickerLocale,
        parentEl: '#modalRiwayatTransaksi',
        opens: 'left'
    }, function (start, end) {
        rtStart = start.format('YYYY-MM-DD');
        rtEnd = end.format('YYYY-MM-DD');
        $('#rt-period .btn').removeClass('active'); // custom range
        reloadAll();
    });

    $('#rt-jenis').on('click', '[data-jenis]', function () {
        rtJenis = String($(this).data('jenis') || '');
        $('#rt-jenis .btn').removeClass('active');
        $(this).addClass('active');
        reloadAll();
    });

    var searchTimer = null;
    $('#rt-search').on('input', function () {
        var value = $(this).val();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (rtTable) rtTable.search(value).draw();
        }, 400);
    });

    $('#rt-btn-download').on('click', function () {
        window.location.href = '{{ route("finance.transactions.download") }}?' + $.param({
            start_date: rtStart,
            end_date: rtEnd,
            jenis_transaksi: rtJenis,
            metode_bayar: rtMetode,
            search: $('#rt-search').val() || ''
        });
    });

    // ---- open / close: load on first open, refresh only while open ----
    $modal.on('shown.bs.modal', function () {
        if (!rtTable) {
            initTable();
        } else {
            rtTable.columns.adjust();
            rtTable.ajax.reload(null, false);
        }
        fetchStats();

        if (!rtRefreshTimer) {
            rtRefreshTimer = setInterval(function () {
                if (document.hidden) return;
                if (rtTable) rtTable.ajax.reload(null, false);
                fetchStats();
            }, 15000);
        }
    });

    $modal.on('hidden.bs.modal', function () {
        if (rtRefreshTimer) {
            clearInterval(rtRefreshTimer);
            rtRefreshTimer = null;
        }
    });

    function openRiwayatTransaksi() {
        $modal.modal('show');
    }
    $('#btn-riwayat-transaksi').on('click', openRiwayatTransaksi);

    // Old Finance > Riwayat Transaksi link lands here with ?riwayat_transaksi=1
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('riwayat_transaksi') === '1') {
            openRiwayatTransaksi();
            params.delete('riwayat_transaksi');
            var qs = params.toString();
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        }
    } catch (e) {}

    // ---- Admin: backfill old change (kembalian) transactions ----
    if (!$('#rt-backfill').length) return;

    var backfillStart = moment().startOf('month').format('YYYY-MM-DD');
    var backfillEnd = moment().endOf('month').format('YYYY-MM-DD');
    var backfillRows = [];
    var backfillCashOnly = false;

    $('#rt-btn-toggle-backfill').on('click', function () {
        $('#rt-backfill').slideToggle(150);
    });

    $('#rt-backfill-daterange').daterangepicker({
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month'),
        locale: pickerLocale,
        parentEl: '#modalRiwayatTransaksi'
    }, function (start, end) {
        backfillStart = start.format('YYYY-MM-DD');
        backfillEnd = end.format('YYYY-MM-DD');
    });

    function setBackfillLoading(isLoading) {
        $('#rt-btn-generate-backfill').prop('disabled', isLoading);
        $('#rt-btn-process-backfill').prop('disabled', isLoading || !backfillRows.length);
    }

    function renderBackfillPreview(data) {
        data = data || {};
        backfillRows = Array.isArray(data.transactions) ? data.transactions : [];
        backfillStart = data.start_date || backfillStart;
        backfillEnd = data.end_date || backfillEnd;
        backfillCashOnly = !!data.cash_only;

        $('#rt-backfill-range').text(moment(backfillStart, 'YYYY-MM-DD').format('DD MMM YYYY') + ' - ' + moment(backfillEnd, 'YYYY-MM-DD').format('DD MMM YYYY'));
        $('#rt-backfill-filter').text(backfillCashOnly ? '(cash only)' : '');
        $('#rt-backfill-count').text(backfillRows.length);
        $('#rt-backfill-total').text(formatRupiah(data.total_change_amount));

        $('#rt-backfill-body').html(backfillRows.map(function (row, index) {
            var patientLabel = (row.patient_name || '-') + (row.patient_id ? ' (' + row.patient_id + ')' : '');
            return '<tr>'
                + '<td>' + (index + 1) + '</td>'
                + '<td>' + escapeHtml(row.payment_date_display || '-') + '</td>'
                + '<td>' + escapeHtml(patientLabel) + '</td>'
                + '<td>' + escapeHtml(row.invoice_number || '-') + '</td>'
                + '<td>' + escapeHtml(metodeLabel(row.payment_method)) + '</td>'
                + '<td class="text-right font-weight-bold text-danger">' + escapeHtml(formatRupiah(row.change_amount || 0)) + '</td>'
                + '<td>' + escapeHtml(row.description || '-') + '</td>'
                + '</tr>';
        }).join(''));
        $('#rt-backfill-empty').toggle(backfillRows.length === 0);
        $('#rt-backfill-table-wrapper').toggle(backfillRows.length > 0);
        $('#rt-btn-process-backfill').prop('disabled', backfillRows.length === 0);
        $('#rt-backfill-preview').show();
    }

    $('#rt-btn-generate-backfill').on('click', function () {
        setBackfillLoading(true);
        $.get('{{ route("finance.transactions.backfill.preview") }}', {
            start_date: backfillStart,
            end_date: backfillEnd,
            cash_only: $('#rt-backfill-cash-only').is(':checked') ? 1 : 0
        }).done(function (res) {
            renderBackfillPreview(res);
        }).fail(function (xhr) {
            Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat preview backfill.', 'error');
        }).always(function () {
            setBackfillLoading(false);
        });
    });

    $('#rt-btn-cancel-backfill').on('click', function () {
        backfillRows = [];
        $('#rt-backfill-preview').hide();
    });

    $('#rt-btn-process-backfill').on('click', function () {
        var invoiceIds = backfillRows.map(function (row) { return row.invoice_id; });
        if (!invoiceIds.length) {
            Swal.fire('Tidak ada data', 'Tidak ada transaksi backfill yang bisa diproses.', 'info');
            return;
        }

        setBackfillLoading(true);
        $.ajax({
            url: '{{ route("finance.transactions.backfill.process") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: {
                start_date: backfillStart,
                end_date: backfillEnd,
                cash_only: backfillCashOnly ? 1 : 0,
                invoice_ids: invoiceIds
            }
        }).done(function (res) {
            backfillRows = [];
            $('#rt-backfill-preview').hide();
            reloadAll();
            Swal.fire('Berhasil', ((res && res.message) || 'Backfill selesai.') + ' Dibuat ' + Number((res && res.created_count) || 0) + ' transaksi.', 'success');
        }).fail(function (xhr) {
            Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memproses backfill.', 'error');
        }).always(function () {
            setBackfillLoading(false);
        });
    });
});
</script>
@endpush

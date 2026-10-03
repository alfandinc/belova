{{--
    Download Invoice (Billing page): pick the report, period, klinik and dokter, preview it, then download the Excel.
    Replaces the separate Finance > Rekap Penjualan page. Two reports:
      rekap   -> Rekap Penjualan, one row per sold item  (finance.rekap-penjualan.preview / .download)
      invoice -> Invoice, one row per invoice            (finance.invoice.export.preview / .download)
    The preview uses the same filters as the Excel file. Period / klinik / dokter start from the billing list filters.
--}}
<style>
    #modalDownloadInvoice .di-type { display: flex; flex-wrap: wrap; gap: .5rem; }
    #modalDownloadInvoice .di-type-option { flex: 1 1 240px; border: 1px solid rgba(127,127,127,.3); border-radius: 8px; padding: .6rem .8rem; cursor: pointer; margin: 0; }
    #modalDownloadInvoice .di-type-option.active { border-color: #1761fd; box-shadow: 0 0 0 1px #1761fd inset; }
    #modalDownloadInvoice .di-type-option input { margin-right: .4rem; }
    #modalDownloadInvoice .di-type-title { font-weight: 700; }
    #modalDownloadInvoice .di-type-desc { font-size: .78rem; opacity: .7; }
    #modalDownloadInvoice #di-preview-table th,
    #modalDownloadInvoice #di-preview-table td { white-space: nowrap; font-size: .82rem; vertical-align: middle; }
    #modalDownloadInvoice .di-num { text-align: right; }
</style>

<div class="modal fade" id="modalDownloadInvoice" tabindex="-1" role="dialog" aria-labelledby="modalDownloadInvoiceTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modalDownloadInvoiceTitle"><i class="fas fa-file-download mr-2"></i>Download Invoice</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="di-type mb-3" id="di-type">
                    <label class="di-type-option active">
                        <input type="radio" name="di_type" value="rekap" checked>
                        <span class="di-type-title">Rekap Penjualan</span>
                        <div class="di-type-desc">Per item terjual: tindakan, obat/produk, lab, dengan diskon, principal, status & metode bayar.</div>
                    </label>
                    <label class="di-type-option">
                        <input type="radio" name="di_type" value="invoice">
                        <span class="di-type-title">Invoice</span>
                        <div class="di-type-desc">Per invoice: no invoice, referral, subtotal, diskon, pajak, total, dibayar, kembalian, kekurangan, retur, metode bayar & status.</div>
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4 mb-2">
                        <label class="small mb-1">Tanggal Visit</label>
                        <input type="text" id="di-daterange" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <label class="small mb-1">Klinik</label>
                        <select id="di-klinik" class="form-control form-control-sm"><option value="">Semua Klinik</option></select>
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <label class="small mb-1">Dokter</label>
                        <select id="di-dokter" class="form-control form-control-sm"><option value="">Semua Dokter</option></select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                    <div class="font-weight-bold">Preview <span class="text-muted small font-weight-normal" id="di-preview-info"></span></div>
                </div>
                <div class="table-responsive" id="di-preview-wrap"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success btn-sm" id="di-btn-download"><i class="fas fa-file-excel mr-1"></i>Download Excel</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    var $modal = $('#modalDownloadInvoice');
    if (!$modal.length) return;

    var URLS = {
        rekap: { preview: '{{ route("finance.rekap-penjualan.preview") }}', download: '{{ route("finance.rekap-penjualan.download") }}' },
        invoice: { preview: '{{ route("finance.invoice.export.preview") }}', download: '{{ route("finance.invoice.export.download") }}' }
    };

    var METODE_LABELS = {
        cash: 'Tunai', piutang: 'Piutang', qris: 'QRIS', transfer: 'Transfer',
        edc_bca: 'EDC BCA', edc_bni: 'EDC BNI', edc_bri: 'EDC BRI', edc_mandiri: 'EDC Mandiri',
        shopee: 'Shopee', tiktokshop: 'Tiktokshop', tokopedia: 'Tokopedia',
        asuransi_inhealth: 'Asuransi InHealth', asuransi_brilife: 'Asuransi Brilife',
        asuransi_admedika: 'Asuransi Admedika', asuransi_bcalife: 'Asuransi BCA Life'
    };

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }
    function text(value) {
        return (value === null || value === undefined || value === '') ? '<span class="text-muted">-</span>' : esc(value);
    }
    function rupiah(value) {
        if (value === null || value === undefined || value === '') return '<span class="text-muted">-</span>';
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }
    function date(value) {
        if (!value) return '<span class="text-muted">-</span>';
        var m = moment(value);
        return m.isValid() ? esc(m.format('DD MMM YYYY')) : esc(value);
    }
    function metode(value) {
        return value ? esc(METODE_LABELS[value] || value) : '<span class="text-muted">-</span>';
    }
    function num(render) {
        return { className: 'di-num', render: function (data, type) { return type === 'display' ? render(data) : data; } };
    }
    function col(data, title, opts) {
        return $.extend({ data: data, title: title, orderable: false, render: function (v, type) { return type === 'display' ? text(v) : v; } }, opts || {});
    }

    // Columns follow the Excel files
    var COLUMNS = {
        rekap: [
            col('tanggal_visit', 'Tanggal Visit', { render: function (v, t) { return t === 'display' ? date(v) : v; } }),
            col('no_rm', 'No RM'),
            col('nama_pasien', 'Nama Pasien'),
            col('nama_dokter', 'Dokter'),
            col('nama_klinik', 'Klinik'),
            col('jenis', 'Jenis'),
            col('nama_item', 'Nama Item'),
            col('principal', 'Principal'),
            col('qty', 'Qty', { className: 'di-num' }),
            col('harga', 'Harga', num(rupiah)),
            col('harga_sebelum_diskon', 'Sebelum Diskon', num(rupiah)),
            col('diskon_nominal', 'Diskon', num(rupiah)),
            col('harga_setelah_diskon', 'Setelah Diskon', num(rupiah)),
            col('status', 'Status'),
            col('payment_method', 'Metode Bayar', { render: function (v, t) { return t === 'display' ? metode(v) : v; } }),
            col('notes', 'Catatan')
        ],
        invoice: [
            col('invoice_number', 'No Invoice'),
            col('tanggal_visit', 'Tanggal Visit', { render: function (v, t) { return t === 'display' ? date(v) : v; } }),
            col('tanggal_dibayar', 'Tanggal Dibayar', { render: function (v, t) { return t === 'display' ? date(v) : v; } }),
            col('no_rm', 'No RM'),
            col('nama_pasien', 'Nama Pasien'),
            col('nama_dokter', 'Dokter'),
            col('nama_klinik', 'Klinik'),
            col('referral_type', 'Referral'),
            col('referral_detail', 'Detail Referral'),
            col('subtotal', 'Subtotal', num(rupiah)),
            col('discount', 'Diskon', num(rupiah)),
            col('tax', 'Pajak', num(rupiah)),
            col('total_amount', 'Total', num(rupiah)),
            col('amount_paid', 'Dibayar', num(rupiah)),
            col('change_amount', 'Kembalian', num(rupiah)),
            col('kekurangan', 'Kekurangan', num(rupiah)),
            col('retur', 'Retur', num(rupiah)),
            col('payment_method', 'Metode Bayar', { render: function (v, t) { return t === 'display' ? metode(v) : v; } }),
            col('status', 'Status'),
            col('notes', 'Catatan')
        ]
    };

    var state = { type: 'rekap', start: moment().format('YYYY-MM-DD'), end: moment().format('YYYY-MM-DD'), klinik: '', dokter: '' };
    var previewTable = null;

    function filters() {
        return { start_date: state.start, end_date: state.end, klinik_id: state.klinik, dokter_id: state.dokter };
    }

    function buildPreview() {
        if (previewTable) {
            previewTable.destroy();
            previewTable = null;
        }
        // columns differ per report, so rebuild the table element
        $('#di-preview-wrap').html('<table id="di-preview-table" class="table table-sm table-striped table-bordered mb-0" style="width:100%"></table>');
        previewTable = $('#di-preview-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ordering: false,
            pageLength: 10,
            lengthChange: false,
            dom: 'rt<"d-flex justify-content-between align-items-center mt-2"<"small text-muted"i>p>',
            ajax: {
                url: URLS[state.type].preview,
                data: function (d) { return $.extend(d, filters()); }
            },
            columns: COLUMNS[state.type],
            language: {
                processing: 'Memuat preview...',
                emptyTable: 'Tidak ada data pada filter ini',
                info: '_TOTAL_ baris akan diunduh (menampilkan _START_–_END_)',
                infoEmpty: 'Tidak ada data untuk diunduh',
                paginate: { previous: '‹', next: '›' }
            },
            drawCallback: function (settings) {
                var total = settings.json ? Number(settings.json.recordsTotal || 0) : 0;
                $('#di-btn-download').prop('disabled', total === 0);
            }
        });
    }

    function reloadPreview() {
        if (previewTable) previewTable.ajax.reload();
    }

    // ---- preferences ----
    $('#di-type').on('change', 'input[name="di_type"]', function () {
        state.type = $(this).val();
        $('#di-type .di-type-option').removeClass('active');
        $(this).closest('.di-type-option').addClass('active');
        buildPreview();
    });

    $('#di-daterange').daterangepicker({
        startDate: moment(),
        endDate: moment(),
        parentEl: '#modalDownloadInvoice',
        locale: {
            format: 'DD MMM YYYY',
            applyLabel: 'Pilih',
            cancelLabel: 'Batal',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        ranges: {
            'Hari Ini': [moment(), moment()],
            'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Minggu Ini': [moment().startOf('isoWeek'), moment()],
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
            'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function (start, end) {
        state.start = start.format('YYYY-MM-DD');
        state.end = end.format('YYYY-MM-DD');
        reloadPreview();
    });

    $('#di-klinik').on('change', function () { state.klinik = $(this).val() || ''; reloadPreview(); });
    $('#di-dokter').on('change', function () { state.dokter = $(this).val() || ''; reloadPreview(); });

    // Start from the billing list filters (period, klinik, dokter)
    function syncFromBillingFilters() {
        var billingPicker = $('#daterange').data('daterangepicker');
        if (billingPicker) {
            state.start = billingPicker.startDate.format('YYYY-MM-DD');
            state.end = billingPicker.endDate.format('YYYY-MM-DD');
            var picker = $('#di-daterange').data('daterangepicker');
            picker.setStartDate(billingPicker.startDate.clone());
            picker.setEndDate(billingPicker.endDate.clone());
        }

        [['#filter-klinik', '#di-klinik', 'klinik'], ['#filter-dokter', '#di-dokter', 'dokter']].forEach(function (pair) {
            var $src = $(pair[0]);
            var $dst = $(pair[1]);
            if ($src.length && $src.find('option').length > 1) {
                $dst.html($src.html());
            }
            $dst.val($src.val() || '');
            state[pair[2]] = $dst.val() || '';
        });
    }

    $modal.on('show.bs.modal', syncFromBillingFilters);
    $modal.on('shown.bs.modal', function () {
        if (!previewTable) {
            buildPreview();
        } else {
            reloadPreview();
        }
    });

    $('#di-btn-download').on('click', function () {
        window.location.href = URLS[state.type].download + '?' + $.param(filters());
    });

    function openDownloadInvoice() {
        $modal.modal('show');
    }
    $('#btn-download-invoice').on('click', openDownloadInvoice);

    // Old Finance > Rekap Penjualan link lands here with ?download_invoice=1
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('download_invoice') === '1') {
            params.delete('download_invoice');
            var qs = params.toString();
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
            // wait for the klinik / dokter options of the billing filters
            setTimeout(openDownloadInvoice, 400);
        }
    } catch (e) {}
});
</script>
@endpush

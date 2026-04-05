@extends('layouts.finance.app')
@section('title', 'Finance | Transaksi')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Daftar Transaksi</h3>
                <div class="text-muted small">Kelola riwayat transaksi kasir: pantau pemasukan dan pengeluaran pembayaran.</div>
            </div>
            <div class="d-flex flex-wrap justify-content-end" style="gap:.75rem;">
                <div class="card shadow-sm border-0 mb-0" style="min-width:180px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Total Uang Masuk</div>
                        <div id="summary-total-in" class="h5 mb-0 font-weight-bold text-success">Rp 0</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:180px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Total Uang Keluar</div>
                        <div id="summary-total-out" class="h5 mb-0 font-weight-bold text-danger">Rp 0</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:180px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Balance</div>
                        <div id="summary-balance" class="h5 mb-0 font-weight-bold text-primary">Rp 0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            @if(Auth::user() && Auth::user()->hasAnyRole(['Admin']))
            <div class="border rounded px-3 py-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem;">
                    <div>
                        <div class="font-weight-bold">Backfill Kembalian Lama</div>
                        <div class="text-muted small">Pilih rentang tanggal invoice yang ingin dicek, lalu tinjau transaksi kembalian yang belum tercatat sebelum diproses.</div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
                        <div style="flex:0 0 260px;">
                            <input type="text" id="backfill-daterange" class="form-control form-control-sm" />
                        </div>
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="backfill-cash-only">
                            <label class="custom-control-label small pt-1" for="backfill-cash-only">Cash only</label>
                        </div>
                        <div style="flex:0 0 auto;">
                            <button type="button" id="btn-generate-backfill" class="btn btn-warning btn-sm">
                                <i class="fas fa-history mr-1"></i> Generate Backfill
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="d-flex flex-wrap justify-content-end mb-3" style="gap:.5rem;">
                <div style="flex:0 0 auto;">
                    <button type="button" id="btn-download-transactions" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel mr-1"></i> Download Excel
                    </button>
                </div>
                <div style="flex:0 0 260px;">
                    <input type="text" id="daterange-transactions" class="form-control form-control-sm" />
                </div>
                <div style="flex:0 0 180px;">
                    <select id="filter-jenis-transaksi" class="form-control form-control-sm">
                        <option value="">Semua Jenis</option>
                        <option value="in">In</option>
                        <option value="out">Out</option>
                    </select>
                </div>
                <div style="flex:0 0 180px;">
                    <select id="filter-metode-bayar" class="form-control form-control-sm">
                        <option value="">Semua Metode</option>
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
                        <option value="asuransi_admedika">Asuransi Admedika</option>
                        <option value="asuransi_bcalife">Asuransi BCA Life</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable-transactions" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama Pasien</th>
                            <th>No Invoice</th>
                            <th>Jumlah</th>
                            <th>Jenis Transaksi</th>
                            <th>Metode Bayar</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-backfill-preview" tabindex="-1" role="dialog" aria-labelledby="modal-backfill-preview-label" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-backfill-preview-label">Preview Backfill Kembalian</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:1rem;">
                    <div>
                        <div class="text-muted small">Rentang dipilih</div>
                        <div id="backfill-preview-range" class="font-weight-bold">-</div>
                        <div id="backfill-preview-filter" class="text-muted small">Semua metode bayar</div>
                    </div>
                    <div class="d-flex flex-wrap" style="gap:1rem;">
                        <div>
                            <div class="text-muted small">Total transaksi</div>
                            <div id="backfill-preview-count" class="font-weight-bold">0</div>
                        </div>
                        <div>
                            <div class="text-muted small">Total kembalian</div>
                            <div id="backfill-preview-total" class="font-weight-bold text-danger">Rp 0</div>
                        </div>
                    </div>
                </div>

                <div id="backfill-preview-empty" class="alert alert-light border mb-0" style="display:none;">
                    Tidak ada invoice dengan kembalian yang perlu dibackfill pada filter ini.
                </div>

                <div id="backfill-preview-table-wrapper" class="table-responsive" style="display:none; max-height:420px; overflow:auto;">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Bayar</th>
                                <th>Nama Pasien</th>
                                <th>No Invoice</th>
                                <th>Metode Bayar</th>
                                <th>Kembalian</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody id="backfill-preview-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                <button type="button" id="btn-process-backfill" class="btn btn-primary btn-sm" disabled>OK, Proses Backfill</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        var startDate = moment().startOf('month').format('YYYY-MM-DD');
        var endDate = moment().endOf('month').format('YYYY-MM-DD');
        var jenisTransaksi = '';
        var metodeBayar = '';
        var transactionRefreshIntervalId = null;
        var backfillPreviewRows = [];
        var backfillRangeStartDate = moment().startOf('month').format('YYYY-MM-DD');
        var backfillRangeEndDate = moment().endOf('month').format('YYYY-MM-DD');
        var backfillPreviewCashOnly = false;

        function formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        }

        function formatDateRangeLabel(start, end) {
            if (!start || !end) {
                return '-';
            }

            return moment(start, 'YYYY-MM-DD').format('DD MMM YYYY') + ' - ' + moment(end, 'YYYY-MM-DD').format('DD MMM YYYY');
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function setBackfillProcessingState(isLoading) {
            $('#btn-generate-backfill').prop('disabled', isLoading);
            $('#btn-process-backfill').prop('disabled', isLoading || !backfillPreviewRows.length);
        }

        function renderBackfillPreview(data) {
            backfillPreviewRows = Array.isArray(data && data.transactions) ? data.transactions : [];
            backfillRangeStartDate = (data && data.start_date) ? data.start_date : backfillRangeStartDate;
            backfillRangeEndDate = (data && data.end_date) ? data.end_date : backfillRangeEndDate;
            backfillPreviewCashOnly = !!(data && data.cash_only);

            $('#backfill-preview-range').text(formatDateRangeLabel(backfillRangeStartDate, backfillRangeEndDate));
            $('#backfill-preview-filter').text(backfillPreviewCashOnly ? 'Filter metode bayar: cash' : 'Semua metode bayar');
            $('#backfill-preview-count').text(backfillPreviewRows.length);
            $('#backfill-preview-total').text(formatRupiah(data && data.total_change_amount));

            var rowsHtml = backfillPreviewRows.map(function(row, index) {
                var patientLabel = row.patient_name || '-';
                if (row.patient_id) {
                    patientLabel += ' (' + row.patient_id + ')';
                }

                return '<tr>' +
                    '<td>' + (index + 1) + '</td>' +
                    '<td>' + escapeHtml(row.payment_date_display || '-') + '</td>' +
                    '<td>' + escapeHtml(patientLabel) + '</td>' +
                    '<td>' + escapeHtml(row.invoice_number || '-') + '</td>' +
                    '<td>' + escapeHtml(row.payment_method || '-') + '</td>' +
                    '<td class="text-right font-weight-bold text-danger">' + escapeHtml(formatRupiah(row.change_amount || 0)) + '</td>' +
                    '<td>' + escapeHtml(row.description || '-') + '</td>' +
                '</tr>';
            }).join('');

            $('#backfill-preview-body').html(rowsHtml);
            $('#backfill-preview-empty').toggle(backfillPreviewRows.length === 0);
            $('#backfill-preview-table-wrapper').toggle(backfillPreviewRows.length > 0);
            $('#btn-process-backfill').prop('disabled', backfillPreviewRows.length === 0);
        }

        function updateSummaryCards(data) {
            $('#summary-total-in').text(formatRupiah(data && data.total_in));
            $('#summary-total-out').text(formatRupiah(data && data.total_out));
            $('#summary-balance').text(formatRupiah(data && data.balance));
        }

        function fetchTransactionStats() {
            $.get('{{ route("finance.transactions.stats") }}', {
                start_date: startDate,
                end_date: endDate,
                jenis_transaksi: jenisTransaksi,
                metode_bayar: metodeBayar
            }).done(function(res) {
                updateSummaryCards(res || {});
            });
        }

        $('#daterange-transactions').daterangepicker({
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
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
            transactionTable.ajax.reload();
            fetchTransactionStats();
        });

        $('#backfill-daterange').daterangepicker({
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
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
            backfillRangeStartDate = start.format('YYYY-MM-DD');
            backfillRangeEndDate = end.format('YYYY-MM-DD');
        });

        var transactionTable = $('#datatable-transactions').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("finance.transactions.data") }}',
                data: function(d) {
                    d.start_date = startDate;
                    d.end_date = endDate;
                    d.jenis_transaksi = jenisTransaksi;
                    d.metode_bayar = metodeBayar;
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'tanggal_display', name: 'tanggal' },
                { data: 'pasien_display', name: 'visitation.pasien.nama' },
                { data: 'invoice_display', name: 'invoice_id' },
                { data: 'jumlah_display', name: 'jumlah' },
                { data: 'jenis_transaksi_display', name: 'jenis_transaksi' },
                { data: 'metode_bayar_display', name: 'metode_bayar' },
                { data: 'deskripsi', name: 'deskripsi' }
            ],
            order: [[1, 'desc']],
            language: {
                emptyTable: 'Belum ada transaksi',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ transaksi',
                search: 'Cari:',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                },
                lengthMenu: 'Tampilkan _MENU_ transaksi'
            }
        });

        $('#filter-jenis-transaksi').on('change', function() {
            jenisTransaksi = $(this).val();
            transactionTable.ajax.reload();
            fetchTransactionStats();
        });

        $('#filter-metode-bayar').on('change', function() {
            metodeBayar = $(this).val();
            transactionTable.ajax.reload();
            fetchTransactionStats();
        });

        $('#btn-download-transactions').on('click', function() {
            var searchValue = '';
            try {
                searchValue = transactionTable.search() || '';
            } catch (e) {
                searchValue = '';
            }

            var params = $.param({
                start_date: startDate,
                end_date: endDate,
                jenis_transaksi: jenisTransaksi,
                metode_bayar: metodeBayar,
                search: searchValue
            });

            window.location.href = '{{ route("finance.transactions.download") }}' + '?' + params;
        });

        $('#btn-generate-backfill').on('click', function() {
            var cashOnly = $('#backfill-cash-only').is(':checked') ? 1 : 0;

            setBackfillProcessingState(true);

            $.ajax({
                url: '{{ route("finance.transactions.backfill.preview") }}',
                method: 'GET',
                data: {
                    start_date: backfillRangeStartDate,
                    end_date: backfillRangeEndDate,
                    cash_only: cashOnly
                }
            }).done(function(res) {
                renderBackfillPreview(res || {});
                $('#modal-backfill-preview').modal('show');
            }).fail(function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal memuat preview backfill.';
                Swal.fire('Gagal', message, 'error');
            }).always(function() {
                setBackfillProcessingState(false);
            });
        });

        $('#btn-process-backfill').on('click', function() {
            var invoiceIds = backfillPreviewRows.map(function(row) {
                return row.invoice_id;
            });
            var cashOnly = backfillPreviewCashOnly ? 1 : 0;

            if (!backfillRangeStartDate || !backfillRangeEndDate || !invoiceIds.length) {
                Swal.fire('Tidak ada data', 'Tidak ada transaksi backfill yang bisa diproses.', 'info');
                return;
            }

            setBackfillProcessingState(true);

            $.ajax({
                url: '{{ route("finance.transactions.backfill.process") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    start_date: backfillRangeStartDate,
                    end_date: backfillRangeEndDate,
                    cash_only: cashOnly,
                    invoice_ids: invoiceIds
                }
            }).done(function(res) {
                $('#modal-backfill-preview').modal('hide');
                backfillPreviewRows = [];
                transactionTable.ajax.reload(null, false);
                fetchTransactionStats();
                Swal.fire('Berhasil', (res && res.message ? res.message : 'Backfill selesai.') + ' Dibuat ' + Number((res && res.created_count) || 0) + ' transaksi.', 'success');
            }).fail(function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal memproses backfill.';
                Swal.fire('Gagal', message, 'error');
            }).always(function() {
                setBackfillProcessingState(false);
            });
        });

        transactionRefreshIntervalId = setInterval(function() {
            transactionTable.ajax.reload(null, false);
            fetchTransactionStats();
        }, 10000);

        $(window).on('beforeunload', function() {
            if (transactionRefreshIntervalId) {
                clearInterval(transactionRefreshIntervalId);
                transactionRefreshIntervalId = null;
            }
        });

        fetchTransactionStats();
    });
</script>
@endsection
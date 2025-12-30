@extends('layouts.finance.app')
@section('title', 'Finance | Piutang')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Daftar Piutang</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <div class="d-flex justify-content-end mb-3" style="gap:.5rem;">
                    <div style="flex:0 0 260px;">
                        <input type="text" id="daterange-piutang" class="form-control form-control-sm" />
                    </div>
                    <div style="flex:0 0 180px;">
                        <select id="filter-status-piutang" class="form-control form-control-sm">
                            <option value="">Semua Status</option>
                            <option value="unpaid">Belum Dibayar</option>
                            <option value="partial">Belum Lunas</option>
                            <option value="paid">Sudah Bayar</option>
                        </select>
                    </div>
                </div>
                <table id="datatable-piutang" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pasien</th>
                            <th>Nomor Invoice</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
                    <!-- Modal: Terima Pembayaran (moved inside content) -->
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
                                        <div class="mb-2">
                                            <label>Jumlah (Rp)</label>
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

                @endsection

                @section('scripts')
                <script>
                    $(function() {
                        var startDate = moment().startOf('month').format('YYYY-MM-DD');
                        var endDate = moment().endOf('month').format('YYYY-MM-DD');
                        var statusFilter = 'unpaid';

                        $('#daterange-piutang').daterangepicker({
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
                            piutangTable.ajax.reload();
                        });

                        // set initial status filter UI and ensure table uses default
                        $('#filter-status-piutang').val(statusFilter);

                        var piutangTable = $('#datatable-piutang').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: {
                                url: '{{ route("finance.piutang.data") }}',
                                data: function(d) {
                                    d.start_date = startDate;
                                    d.end_date = endDate;
                                    d.status_filter = statusFilter;
                                }
                            },
                            columns: [
                            { data: 'id', name: 'id' },
                            { data: 'nama_pasien', name: 'nama_pasien' },
                            { data: 'invoice_number', name: 'invoice_number' },
                            { data: 'amount_display', name: 'amount' },
                            { data: 'payment_status', name: 'payment_status' },
                            { data: 'action', name: 'action', orderable: false, searchable: false }
                        ],
                            order: [[0, 'desc']],
                            responsive: true
                        });

                        $('#filter-status-piutang').on('change', function() {
                            statusFilter = $(this).val();
                            piutangTable.ajax.reload();
                        });
                    });

                    $(document).on('click', '.btn-terima-pembayaran', function() {
                        var id = $(this).data('id');
                        var amount = $(this).data('amount');
                        var invoice = $(this).data('invoice');
                        $('#piutang_id').val(id);
                        $('#piutang_amount').val(amount);
                        $('#piutang_invoice').val(invoice);
                        // default payment date to now
                        var now = new Date();
                        var pad = function(n){return n<10?'0'+n:n};
                        var local = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
                        $('#piutang_payment_date').val(local);
                        $('#modalTerimaPembayaran').modal('show');
                    });

                    $('#btn-submit-terima').on('click', function() {
                        var id = $('#piutang_id').val();
                        var payload = {
                            amount: $('#piutang_amount').val(),
                            payment_date: $('#piutang_payment_date').val(),
                            payment_method: $('#piutang_payment_method').val(),
                            _token: '{{ csrf_token() }}'
                        };
                        if (!id) return;
                        $.post('{{ url('/finance/piutang') }}/' + id + '/receive', payload)
                            .done(function(res) {
                                if (res && res.success) {
                                    $('#modalTerimaPembayaran').modal('hide');
                                    $('#datatable-piutang').DataTable().ajax.reload(null, false);
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
                </script>
                @endsection

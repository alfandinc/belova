@extends('layouts.bcl.app')

@section('bcl-content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Transaksi Sewa</h4>
                    <span>Riwayat sewa, reschedule, pindah kamar dan refund. Sewa baru, tambahan sewa dan pelunasan ada di <a href="{{ route('bcl.rooms') }}">Daftar Kamar</a>.</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title">Daftar Transaksi</h4>
                    </div>
                    <div class="col-auto align-self-center">
                        <a href="#" class="btn btn-sm btn-light dropdown-toggle" data-toggle="dropdown">
                            <i class="far fa-file-alt"></i> Export <i class="las la-angle-down"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-bottom side-color side-color-dark">
                            <a class="dropdown-item btn_exls" href="#">Excel</a>
                            <a class="dropdown-item btn_epdf" href="#">PDF</a>
                            <a class="dropdown-item btn_eprint" href="#">Print</a>
                        </div>
                    </div>
                    <div class="col-auto align-self-center">
                        <a href="javascript:void(0)" class="btn btn-sm btn-light" id="filter_faktur">
                            <span id="Select_date"></span>
                            <i data-feather="calendar" class="align-self-center icon-xs ml-1"></i>
                        </a>
                        <form id="f_filter_tgl" action="{{ route('bcl.transaksi.index') }}" method="POST">
                            @csrf
                            <input type="hidden" name="filter" id="filter">
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive-sm">
                    <table class="table table-sm table-hover mb-0" id="tb_transaksi">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Tgl. Trans</th>
                                <th>Kode</th>
                                <th>Kamar</th>
                                <th>Penyewa</th>
                                <th>Paket</th>
                                <th>Periode</th>
                                <th class="text-right">Harga</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $trx)
                            @php
                                $kurang = $paid->has($trx->trans_id) ? max((float) $trx->harga - $paid[$trx->trans_id], 0) : 0;
                                $isActive = $trx->tgl_selesai > now()->format('Y-m-d');
                            @endphp
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $trx->tanggal }}</td>
                                <td><a href="javascript:void(0)" data-id="{{ $trx->trans_id }}" class="dt_transaksi"><u>{{ $trx->trans_id }}</u></a></td>
                                <td>
                                    {{ $trx->room->room_name ?? 'Kamar dihapus' }}
                                    @if($trx->identity === 'Pindah Kamar')<span class="badge badge-soft-primary">Pindah</span>@endif
                                    @if($trx->cancelled_at)<span class="bcl-pill is-bad">Batal</span>@endif
                                    @if($trx->tambahan->count())<span class="badge badge-soft-success" title="Ada tambahan sewa">+{{ $trx->tambahan->count() }}</span>@endif
                                </td>
                                <td>{{ $trx->renter->nama ?? 'N/a' }}</td>
                                <td>{{ $trx->lama_sewa . ' ' . $trx->jangka_sewa }}</td>
                                <td class="text-nowrap">{{ $trx->tgl_mulai . ' s/d ' . $trx->tgl_selesai }}</td>
                                <td class="text-right text-nowrap">Rp {{ number_format($trx->harga, 0, ',', '.') }}</td>
                                <td>
                                    @if($overdue->has($trx->trans_id))
                                    <span class="bcl-pill is-bad d-inline-block mb-1">Lewat masa sewa</span><br>
                                    @endif
                                    @if($kurang > 0)
                                    <span class="badge badge-danger">Kurang Rp {{ number_format($kurang, 0, ',', '.') }}</span>
                                    @else
                                    <span class="badge badge-success">Lunas</span>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    @if(!$trx->cancelled_at && $trx->tgl_mulai > now()->format('Y-m-d'))
                                    <button type="button" class="btn btn-xs btn-outline-danger js-supervisor js-cancel-booking" data-trans="{{ $trx->trans_id }}" data-label="{{ ($trx->room->room_name ?? '') . ' - ' . ($trx->renter->nama ?? '') }}" title="Batalkan booking"><i class="fas fa-ban"></i></button>
                                    @endif
                                    @if($isActive && $trx->identity !== 'Pindah Kamar' && !$trx->cancelled_at)
                                    <button type="button" class="btn btn-xs btn-outline-secondary js-reschedule" data-trans="{{ $trx->trans_id }}" data-start="{{ $trx->tgl_mulai }}" title="Ubah tanggal masuk"><i class="fas fa-calendar-alt"></i></button>
                                    @endif
                                    @if($isActive)
                                    <button type="button" class="btn btn-xs btn-outline-primary js-change-room" data-trans="{{ $trx->trans_id }}" title="Pindah kamar"><i class="fas fa-exchange-alt"></i></button>
                                    <button type="button" class="btn btn-xs btn-outline-warning js-refund" data-trans="{{ $trx->trans_id }}" data-label="{{ ($trx->room->room_name ?? '') . ' - ' . ($trx->renter->nama ?? '') }}" title="Refund / keluar lebih awal"><i class="fas fa-hand-holding-usd"></i></button>
                                    @endif
                                    @if($overdue->has($trx->trans_id))
                                    <a class="btn btn-xs btn-outline-danger" href="{{ route('bcl.transaksi.checkout', ['trans_id' => $trx->trans_id]) }}" onclick="bclConfirmPost(event, 'Penghuni sudah keluar?', 'Kamar akan tampil kosong. Tunggakan (jika ada) tetap tercatat.')" title="Konfirmasi sudah keluar"><i class="fas fa-door-open"></i></a>
                                    @endif
                                    <a class="btn btn-xs btn-outline-info js-print" href="{{ route('bcl.transaksi.cetak', $trx->trans_id) }}" data-label="{{ $trx->trans_id }}" title="Cetak nota"><i class="fas fa-print"></i></a>
                                    <a class="btn btn-xs btn-outline-danger" href="{{ route('bcl.transaksi.delete', $trx->trans_id) }}" onclick="bclConfirmPost(event, 'Hapus transaksi {{ $trx->trans_id }}?', 'Semua pembayaran, tambahan sewa dan pemakaian deposit transaksi ini ikut dihapus.')" title="Hapus transaksi"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="md_reschedule" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Ubah Tanggal Masuk (Reschedule)</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="la la-times"></i></span></button>
            </div>
            <form action="{{ route('bcl.transaksi.reschedule') }}" method="POST">
                @csrf
                <input type="hidden" name="trans_id" id="rs_trans_id">
                <div class="modal-body">
                    <label for="rs_tgl">Tanggal Masuk Baru</label>
                    <input type="date" id="rs_tgl" name="tgl_rencana_masuk" class="form-control" required>
                    <small class="text-muted">Tanggal selesai dihitung ulang sesuai paket (termasuk bonus).</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="md_dt_trans" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Informasi Transaksi</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="la la-times"></i></span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between">
                    <h4 class="text-info mt-0" id="dt_no_trans"></h4>
                    <button type="button" class="btn btn-sm btn-outline-primary js-supervisor" id="dt_edit_trx">Koreksi transaksi</button>
                    <h4 class="mt-0" id="dt_status"></h4>
                </div>
                <dl class="row mb-2">
                    <dt class="col-sm-3">Penyewa / Kamar</dt><dd class="col-sm-9" id="dt_renter"></dd>
                    <dt class="col-sm-3">Periode</dt><dd class="col-sm-9" id="dt_period"></dd>
                    <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9" id="dt_notes"></dd>
                </dl>
                <table class="table table-sm" id="dt_table">
                    <thead>
                        <tr><th>Tanggal</th><th>Keterangan</th><th class="text-right">Tagihan</th><th class="text-right">Dibayar</th><th></th></tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot class="font-weight-bold"><tr><td colspan="2" class="text-right">Total</td><td class="text-right" id="dt_total_bill"></td><td class="text-right" id="dt_total_paid"></td><td></td></tr></tfoot>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="md_edit_payment" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document"><div class="modal-content">
        <div class="modal-header bg-light"><h6 class="modal-title m-0">Koreksi Pembayaran</h6><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form id="form_edit_payment">
            <input type="hidden" name="no_jurnal" id="ep_no_jurnal">
            <div class="modal-body">
                <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="ep_tanggal" class="form-control" required></div>
                <div class="form-group"><label>Nominal (Rp)</label><input type="number" min="1" name="nominal" id="ep_nominal" class="form-control" required></div>
                <div class="form-group"><label>Metode</label><select name="metode_bayar" class="form-control">@foreach(\App\Services\BCL\RentalService::METHODS as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
                <div class="form-group mb-0"><label>Alasan koreksi <span class="text-danger">*</span></label><input type="text" name="alasan" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
        </form>
    </div></div>
</div>
<div class="modal fade" id="md_edit_trx" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header bg-light"><h6 class="modal-title m-0">Koreksi Transaksi <span id="et_title"></span></h6><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form id="form_edit_trx">
            <input type="hidden" name="trans_id" id="et_trans_id">
            <div class="modal-body">
                <div class="form-group"><label>Harga sewa disepakati (Rp)</label><input type="number" min="0" name="harga" id="et_harga" class="form-control" required><small class="text-muted">Untuk diskon/kesepakatan harga. Tanggal: gunakan Reschedule; kamar: Pindah Kamar.</small></div>
                <div class="form-group"><label>Catatan</label><input type="text" name="catatan" id="et_catatan" class="form-control"></div>
                <div class="form-group mb-0"><label>Alasan koreksi <span class="text-danger">*</span></label><input type="text" name="alasan" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
        </form>
    </div></div>
</div>
<div class="modal fade" id="md_cancel_booking" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header bg-light"><h6 class="modal-title m-0">Batalkan Booking <span id="cb_title"></span></h6><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form id="form_cancel_booking">
            <input type="hidden" name="trans_id" id="cb_trans_id">
            <div class="modal-body">
                <div class="bcl-hint mb-3" id="cb_info"></div>
                <div class="form-row">
                    <div class="form-group col-6"><label>Refund (Rp)</label><input type="number" min="0" name="refund" id="cb_refund" class="form-control" value="0"><small class="text-muted">Sisanya hangus.</small></div>
                    <div class="form-group col-6"><label>Refund ke</label><select name="refund_mode" class="form-control"><option value="tunai">Tunai/transfer</option><option value="deposit">Saldo deposit</option></select></div>
                </div>
                <div class="form-group"><label>Metode</label><select name="metode_bayar" class="form-control">@foreach(\App\Services\BCL\RentalService::METHODS as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
                <div class="form-group mb-0"><label>Alasan <span class="text-danger">*</span></label><input type="text" name="alasan" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Tutup</button><button type="submit" class="btn btn-danger btn-sm">Batalkan Booking</button></div>
        </form>
    </div></div>
</div>

@include('bcl.partials.change_room_modal')
@include('bcl.partials.refund_modal')
@endsection

@section('pagescript')
<script>
    var filterStart = moment(@json($start), 'YYYY-MM-DD'),
        filterEnd = moment(@json($end), 'YYYY-MM-DD');

    function esc(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    $(document).ready(function() {
        var table = $('#tb_transaksi').DataTable({
            order: [[1, 'desc']],
            columnDefs: [{ targets: [9], orderable: false, searchable: false }],
            language: { emptyTable: 'Tidak ada transaksi pada periode ini' }
        });
        table.on('order.dt search.dt', function() {
            var i = 1;
            table.cells(null, 0, { search: 'applied', order: 'applied' }).every(function() { this.data(i++); });
        }).draw();

        var exportOptions = {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
            format: { body: function(data) { return String(data).replace(/(&nbsp;|<([^>]+)>)/ig, '').trim(); } }
        };
        new $.fn.dataTable.Buttons(table, {
            buttons: [
                { extend: 'excelHtml5', exportOptions: exportOptions, title: "{{ config('app.name') }} - Transaksi Sewa", filename: function() { return 'Transaksi Sewa ' + moment().format('YYYY-MM-DD'); } },
                { extend: 'pdfHtml5', exportOptions: exportOptions, orientation: 'landscape', pageSize: 'A4', title: "{{ config('app.name') }} - Transaksi Sewa" },
                { extend: 'print', exportOptions: exportOptions, title: 'Transaksi Sewa' }
            ]
        }).container().appendTo($('#button_export'));
        $('.btn_epdf').on('click', function(e) { e.preventDefault(); $('.buttons-pdf').click(); });
        $('.btn_exls').on('click', function(e) { e.preventDefault(); $('.buttons-excel').click(); });
        $('.btn_eprint').on('click', function(e) { e.preventDefault(); $('.buttons-print').click(); });
    });

    $('#filter_faktur').daterangepicker({
        showDropdowns: true,
        autoApply: true,
        startDate: filterStart,
        endDate: filterEnd,
        locale: { format: 'YYYY-MM-DD', separator: ' s/d ', customRangeLabel: 'Custom', firstDay: 1 },
        ranges: {
            'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
            'Bulan lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Tahun ini': [moment().startOf('year'), moment().endOf('year')],
            'Tahun lalu': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        }
    }, function(start, end) {
        $('#filter').val(start.format('YYYY-MM-DD') + 's/d' + end.format('YYYY-MM-DD'));
        $('#f_filter_tgl').submit();
    });
    $('#Select_date').text(filterStart.format('YYYY-MM-DD') + ' s/d ' + filterEnd.format('YYYY-MM-DD'));

    $(document).on('click', '.js-reschedule', function() {
        $('#rs_trans_id').val($(this).data('trans'));
        $('#rs_tgl').val($(this).data('start'));
        $('#md_reschedule').modal('show');
    });
    $(document).on('click', '.js-change-room', function() {
        bclOpenChangeRoom($(this).data('trans'));
    });
    $(document).on('click', '.js-refund', function() {
        bclOpenRefund($(this).data('trans'), $(this).data('label'));
    });

    var currentTrx = null;
    var trxUrls = {
        show: @json(route('bcl.transaksi.show', ':id')),
        paymentUpdate: @json(route('bcl.income.update')),
        paymentDelete: @json(route('bcl.income.delete', ':id')),
        extraDelete: @json(route('bcl.extrarent.delete', ':id')),
        trxUpdate: @json(route('bcl.transaksi.update')),
        cancelBooking: @json(route('bcl.transaksi.cancel')),
    };

    function paymentActions(line) {
        if (!window.BCL_CAN_SUPERVISE || !line.no_jurnal) return '';
        return '<button type="button" class="btn btn-xs btn-outline-primary js-edit-payment" data-jurnal="' + esc(line.no_jurnal) + '" data-tanggal="' + esc(line.tanggal) + '" data-nominal="' + (parseFloat(line.kredit) || 0) + '">Koreksi</button> ' +
            '<button type="button" class="btn btn-xs btn-outline-danger js-delete-payment" data-jurnal="' + esc(line.no_jurnal) + '">Hapus</button>';
    }

    function ajaxPost(urlValue, data) {
        return $.ajax({ url: urlValue, type: 'POST', data: data, headers: { Accept: 'application/json' } })
            .fail(function(xhr) { bclToast((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal.', 'error'); });
    }

    function reloadSoon(res) {
        bclToast(res.message);
        setTimeout(function() { location.reload(); }, 700);
    }

    function confirmPost(title, content, urlValue) {
        $.confirm({ title: title, content: content, buttons: {
            confirm: { text: 'Ya', btnClass: 'btn-red', action: function() { ajaxPost(urlValue, {}).done(reloadSoon); } },
            cancel: { text: 'Batal' }
        } });
    }

    $(document).on('click', '.js-edit-payment', function() {
        $('#ep_no_jurnal').val($(this).data('jurnal'));
        $('#ep_tanggal').val($(this).data('tanggal'));
        $('#ep_nominal').val($(this).data('nominal'));
        $('#md_edit_payment').modal('show');
    });
    $(document).on('click', '.js-delete-payment', function() {
        confirmPost('Hapus pembayaran ini?', 'Pembayaran dihapus dan tagihan kembali Belum Lunas. Tercatat di Log Aktivitas.', trxUrls.paymentDelete.replace(':id', $(this).data('jurnal')));
    });
    $(document).on('click', '.js-cancel-extra', function() {
        confirmPost('Batalkan tambahan sewa ' + $(this).data('kode') + '?', 'Pembayarannya ikut dihapus dan extra bed dilepas.', trxUrls.extraDelete.replace(':id', $(this).data('kode')));
    });
    $('#dt_edit_trx').on('click', function() {
        if (!currentTrx) return;
        $('#et_trans_id').val(currentTrx.trans_id);
        $('#et_title').text(currentTrx.trans_id);
        $('#et_harga').val(parseFloat(currentTrx.harga) || 0);
        $('#et_catatan').val(currentTrx.catatan || '');
        $('#md_edit_trx').modal('show');
    });
    $(document).on('click', '.js-cancel-booking', function() {
        var transId = $(this).data('trans');
        $('#form_cancel_booking')[0].reset();
        $('#cb_trans_id').val(transId);
        $('#cb_title').text($(this).data('label'));
        $('#cb_info').text('Memuat...');
        $('#md_cancel_booking').modal('show');
        $.getJSON(trxUrls.show.replace(':id', encodeURIComponent(transId)), { id: transId }).done(function(trx) {
            var paid = (trx.jurnal || []).reduce(function(t, l) { return t + (parseFloat(l.kredit) || 0); }, 0);
            $('#cb_info').text('Sudah dibayar Rp ' + $.number(paid, 0) + '. Isi nominal yang dikembalikan, sisanya hangus.');
            $('#cb_refund').val(paid);
        });
    });
    $('#form_edit_payment, #form_edit_trx, #form_cancel_booking').on('submit', function(e) {
        e.preventDefault();
        var target = { form_edit_payment: trxUrls.paymentUpdate, form_edit_trx: trxUrls.trxUpdate, form_cancel_booking: trxUrls.cancelBooking }[this.id];
        var $btn = $(this).find('button[type="submit"]').prop('disabled', true);
        ajaxPost(target, $(this).serialize()).done(reloadSoon).always(function() { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.dt_transaksi', function() {
        var id = $(this).data('id');
        $.getJSON("{{ route('bcl.transaksi.show', ':id') }}".replace(':id', encodeURIComponent(id)), { id: id }).done(function(data) {
            var rows = [];
            var bill = parseFloat(data.harga) || 0;
            var paid = 0;
            rows.push('<tr><td>' + esc(data.tanggal) + '</td><td>Sewa ' + esc(data.lama_sewa + ' ' + data.jangka_sewa) + ' - ' + esc((data.room || {}).room_name || '-') + '</td><td class="text-right">Rp ' + $.number(bill, 0) + '</td><td></td></tr>');
            (data.jurnal || []).forEach(function(line) {
                paid += parseFloat(line.kredit) || 0;
                rows.push('<tr><td>' + esc(line.tanggal) + '</td><td>' + esc(line.catatan) + (line.name ? ' <small class="text-muted">(' + esc(line.name) + ')</small>' : '') + '</td><td></td><td class="text-right">Rp ' + $.number(line.kredit, 0) + '</td><td class="text-right text-nowrap">' + paymentActions(line) + '</td></tr>');
            });
            (data.tambahan || []).forEach(function(extra) {
                var total = (parseFloat(extra.harga) || 0) * (parseInt(extra.qty, 10) || 0) * (parseInt(extra.lama_sewa, 10) || 0);
                var label = extra.nama + ' x' + extra.qty + ' (' + extra.lama_sewa + ' ' + extra.jangka_sewa + ')';
                if (extra.assigned_asset_codes && extra.assigned_asset_codes.length) {
                    label += ' [' + extra.assigned_asset_codes.join(', ') + ']';
                }
                bill += total;
                rows.push('<tr><td>' + esc(extra.tgl_mulai) + '</td><td>' + esc(extra.kode + ' - ' + label) + '</td><td class="text-right">Rp ' + $.number(total, 0) + '</td><td></td><td class="text-right">' + (window.BCL_CAN_SUPERVISE ? '<button type="button" class="btn btn-xs btn-outline-danger js-cancel-extra" data-kode="' + esc(extra.kode) + '">Batal</button>' : '') + '</td></tr>');
                (extra.jurnal || []).forEach(function(line) {
                    paid += parseFloat(line.kredit) || 0;
                    rows.push('<tr><td>' + esc(line.tanggal) + '</td><td>' + esc(line.catatan) + '</td><td></td><td class="text-right">Rp ' + $.number(line.kredit, 0) + '</td><td class="text-right text-nowrap">' + paymentActions(line) + '</td></tr>');
                });
            });

            $('#dt_no_trans').text(data.trans_id);
            currentTrx = data;
            $('#dt_renter').text(((data.renter || {}).nama || '-') + ' / ' + ((data.room || {}).room_name || '-'));
            $('#dt_period').text(data.tgl_mulai + ' s/d ' + data.tgl_selesai);
            $('#dt_notes').text(data.catatan || '-');
            $('#dt_table tbody').html(rows.join(''));
            $('#dt_total_bill').text('Rp ' + $.number(bill, 0));
            $('#dt_total_paid').text('Rp ' + $.number(paid, 0));
            $('#dt_status').html(bill > paid + 0.5 ? '<span class="text-danger">BELUM LUNAS</span>' : '<span class="text-success">LUNAS</span>');
            $('#md_dt_trans').modal('show');
        });
    });
</script>
@include('bcl.partials.change_room_script')
@include('bcl.partials.refund_script')
@endsection

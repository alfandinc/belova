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
                                    @if($isActive && $trx->identity !== 'Pindah Kamar')
                                    <button type="button" class="btn btn-xs btn-outline-secondary js-reschedule" data-trans="{{ $trx->trans_id }}" data-start="{{ $trx->tgl_mulai }}" title="Ubah tanggal masuk"><i class="fas fa-calendar-alt"></i></button>
                                    @endif
                                    @if($isActive)
                                    <button type="button" class="btn btn-xs btn-outline-primary js-change-room" data-trans="{{ $trx->trans_id }}" title="Pindah kamar"><i class="fas fa-exchange-alt"></i></button>
                                    <button type="button" class="btn btn-xs btn-outline-warning js-refund" data-trans="{{ $trx->trans_id }}" data-label="{{ ($trx->room->room_name ?? '') . ' - ' . ($trx->renter->nama ?? '') }}" title="Refund / keluar lebih awal"><i class="fas fa-hand-holding-usd"></i></button>
                                    @endif
                                    @if($overdue->has($trx->trans_id))
                                    <a class="btn btn-xs btn-outline-danger" href="{{ route('bcl.transaksi.checkout', ['trans_id' => $trx->trans_id]) }}" onclick="bclConfirmPost(event, 'Penghuni sudah keluar?', 'Kamar akan tampil kosong. Tunggakan (jika ada) tetap tercatat.')" title="Konfirmasi sudah keluar"><i class="fas fa-door-open"></i></a>
                                    @endif
                                    <a class="btn btn-xs btn-outline-info" href="{{ route('bcl.transaksi.cetak', $trx->trans_id) }}" target="_blank" title="Cetak nota"><i class="fas fa-print"></i></a>
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
                    <h4 class="mt-0" id="dt_status"></h4>
                </div>
                <dl class="row mb-2">
                    <dt class="col-sm-3">Penyewa / Kamar</dt><dd class="col-sm-9" id="dt_renter"></dd>
                    <dt class="col-sm-3">Periode</dt><dd class="col-sm-9" id="dt_period"></dd>
                    <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9" id="dt_notes"></dd>
                </dl>
                <table class="table table-sm" id="dt_table">
                    <thead>
                        <tr><th>Tanggal</th><th>Keterangan</th><th class="text-right">Tagihan</th><th class="text-right">Dibayar</th></tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot class="font-weight-bold"><tr><td colspan="2" class="text-right">Total</td><td class="text-right" id="dt_total_bill"></td><td class="text-right" id="dt_total_paid"></td></tr></tfoot>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
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

    $(document).on('click', '.dt_transaksi', function() {
        var id = $(this).data('id');
        $.getJSON("{{ route('bcl.transaksi.show', ':id') }}".replace(':id', encodeURIComponent(id)), { id: id }).done(function(data) {
            var rows = [];
            var bill = parseFloat(data.harga) || 0;
            var paid = 0;
            rows.push('<tr><td>' + esc(data.tanggal) + '</td><td>Sewa ' + esc(data.lama_sewa + ' ' + data.jangka_sewa) + ' - ' + esc((data.room || {}).room_name || '-') + '</td><td class="text-right">Rp ' + $.number(bill, 0) + '</td><td></td></tr>');
            (data.jurnal || []).forEach(function(line) {
                paid += parseFloat(line.kredit) || 0;
                rows.push('<tr><td>' + esc(line.tanggal) + '</td><td>' + esc(line.catatan) + (line.name ? ' <small class="text-muted">(' + esc(line.name) + ')</small>' : '') + '</td><td></td><td class="text-right">Rp ' + $.number(line.kredit, 0) + '</td></tr>');
            });
            (data.tambahan || []).forEach(function(extra) {
                var total = (parseFloat(extra.harga) || 0) * (parseInt(extra.qty, 10) || 0) * (parseInt(extra.lama_sewa, 10) || 0);
                var label = extra.nama + ' x' + extra.qty + ' (' + extra.lama_sewa + ' ' + extra.jangka_sewa + ')';
                if (extra.assigned_asset_codes && extra.assigned_asset_codes.length) {
                    label += ' [' + extra.assigned_asset_codes.join(', ') + ']';
                }
                bill += total;
                rows.push('<tr><td>' + esc(extra.tgl_mulai) + '</td><td>' + esc(extra.kode + ' - ' + label) + '</td><td class="text-right">Rp ' + $.number(total, 0) + '</td><td></td></tr>');
                (extra.jurnal || []).forEach(function(line) {
                    paid += parseFloat(line.kredit) || 0;
                    rows.push('<tr><td>' + esc(line.tanggal) + '</td><td>' + esc(line.catatan) + '</td><td></td><td class="text-right">Rp ' + $.number(line.kredit, 0) + '</td></tr>');
                });
            });

            $('#dt_no_trans').text(data.trans_id);
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

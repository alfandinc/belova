@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<?php

use Carbon\Carbon;

$data = $data;
?>
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Laporan Pemasukan</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    @can('Pemasukan')
                    <a href="#addBiaya" data-toggle="modal" data-target="#md_pay_biaya" class="btn btn-primary btn-sm btn-square btn-outline-dashed waves-effect waves-light">
                        <i data-feather="plus" class="align-self-center icon-xs"></i> Input Pemasukan
                    </a>
                    @endcan
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->

@if($belum_lunas->count()>0 or $belum_lunas->count()>0)
<div class="row mb-2">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-danger">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title text-white">Transaksi Belum Lunas</h4>
                    </div>

                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive-sm">
                    <table class="table table-sm mb-0 table-hover" data-page-length="50" id="tb_pending_tr">
                        <thead class="thead-secondary bg-light">
                            <tr>
                                <th class="" width="25px"><b>No</b></th>
                                <th class=""><b>Tanggal</b></th>
                                <th class=""><b>Nomor</b></th>
                                <th class=" "><b>Tipe</b></th>
                                <th class=" "><b>Catatan</b></th>
                                <th class="text-right"><b>Nominal</b></th>
                                <th class="text-right"><b>Kurang</b></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($belum_lunas->count() > 0) {
                                foreach ($belum_lunas as $value) {
                            ?>
                                    <tr>
                                        <td><?= $no ?></td>
                                        <td><?= $value->tanggal ?></td>
                                        <td><?= $value->doc_id ?></td>
                                        <td><?= $value->identity == 'Sewa Kamar' ? 'Pendapatan Sewa' : 'Pendapatan Lain' ?></td>
                                        <td><?= $value->catatan ?></td>
                                        <td class="text-right">Rp <?= number_format($value->harga, 2) ?></td>
                                        <td class="text-right text-danger font-weight-bold">Rp <?= number_format($value->kurang, 2) ?></td>
                                        <th class="text-right">
                                            <button class="btn btn-xs btn-success" onclick="terima('<?= $value->doc_id ?>')"><i class="mdi mdi-check"></i>Terima</button>
                                        </th>
                                    </tr>
                                <?php
                                    $no++;
                                }
                            }
                            if ($belum_lunas_extra->count() > 0) {
                                $total_jurnal = 0;
                                foreach ($belum_lunas_extra as $value) {
                                    $total_harga = $value->harga * $value->lama_sewa * $value->qty;
                                ?>
                                    <tr>
                                        <td><?= $no ?></td>
                                        <td><?= $value->tgl_mulai ?></td>
                                        <td><?= $value->kode ?></td>
                                        <td><?= 'Tambahan Sewa' ?></td>
                                        <td><?= $value->nama . ' ' . $value->lama_sewa . ' ' . $value->jangka_sewa . ' ' . $value->renter->nama ?></td>
                                        <td class="text-right">Rp <?= number_format($total_harga, 2) ?></td>
                                        <td class="text-right text-danger font-weight-bold">Rp <?= number_format($total_harga - ($value->total_kredit == null ? 0 : $value->total_kredit), 2) ?></td>
                                        <th class="text-right">
                                            <button class="btn btn-xs btn-success" onclick="terima('<?= $value->kode ?>')"><i class="mdi mdi-check"></i>Terima</button>
                                        </th>
                                    </tr>
                            <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Pemasukan</h4>
                    </div>
                    <div class="col-auto align-self-center">

                        <a href="#" class="btn btn-sm btn-light waves-effect waves-light dropdown-toggle" data-toggle="dropdown">
                            <i class="far fa-file-alt"></i> Export <i class="las la-angle-down "></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-bottom side-color side-color-dark">
                            <a class="dropdown-item btn_exls" href="#">Excel</a>
                            <a class="dropdown-item btn_epdf" href="#">PDF</a>
                            <a class="dropdown-item btn_eprint" href="#">Print</a>
                        </div>
                    </div>
                    <div class="col-auto align-self-center">
                        <a href="javascript:void(0)" class="btn btn-sm btn-light" id="filter_faktur">
                            <span class="ay-name" id="Day_Name">Today:</span>&nbsp;
                            <span class="" id="Select_date">Jan 11</span>
                            <i data-feather="calendar" class="align-self-center icon-xs ml-1"></i>
                        </a>
                        <form id="f_filter_tgl" action="{{route('bcl.income.index')}}" method="POST">
                            @csrf
                            <input type="hidden" name="filter" id="filter">
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive-sm">
                    <div id="tb_penjualan_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-sm-12">
                                <table class="table table-sm table-hover mb-0 dataTable no-footer" id="tb_kamar">
                                    <thead class="thead-info bg-info">
                                        <tr class="text-white">
                                            <th class="text-center text-white">No</th>
                                            <th class="text-center text-white">Tanggal</th>
                                            <th class="text-white">Nomor</th>
                                            <th class="text-white">Tipe</th>
                                            <th class="text-white">Catatan</th>
                                            <th class="text-white text-right">Jumlah</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        ?>
                                        @foreach($data as $data)
                                        <tr>
                                            <td class="text-center">{{ $no }}</td>
                                            <td class="text-center">{{ $data->tanggal }}</td>
                                            <td>{{$data->doc_id}}</td>
                                            <td>{{$data->identity}}</td>
                                            <td>{{$data->catatan}}</td>
                                            <td class="text-right">{{$data->debet}} </td>
                                            <td class="text-right">
                                                @role('Administrator')
                                                <a class="btn btn-xs btn-square btn-danger" onclick="deletes(event)" href="{{route('bcl.income.delete',$data->no_jurnal)}}"><i class="fas fa-trash"></i></i></button>
                                                    @endrole
                                            </td>

                                        </tr>
                                        <?php $no++; ?>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5"></div>
                            <div class="col-sm-12 col-md-7"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_pay_biaya" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog  modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Penerimaan Pembayaran</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                </button>
            </div>
            <form method="POST" action="{{route('bcl.income.store')}}">
                @csrf
                <input type="hidden" id="section" name="section" value="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="transaksi">Pilih Transaksi</label>
                                <select class="select2" name="transaksi" id="transaksi" required>
                                    <option value="" selected></option>
                                    <?php
                                    foreach ($belum_lunas as $value) {
                                        // Check if this is an upgrade transaction by looking at the identity
                                        $section = strpos($value->identity, 'Upgrade Kamar') !== false ? 'Upgrade Kamar' : 'Sewa Kamar';
                                    ?>
                                        <option data-section="<?= $section ?>" data-kurang="<?= $value->kurang ?>" value="<?= $value->doc_id ?>"><?= $value->doc_id . ' - ' . $value->catatan ?></option>
                                    <?php }
                                    foreach ($belum_lunas_extra as $value) {
                                        $total_harga = $value->harga * $value->lama_sewa * $value->qty;
                                    ?>
                                        <option data-section="Tambahan Sewa" data-kurang="<?= $total_harga - ($value->total_kredit == null ? 0 : $value->total_kredit) ?>" value="<?= $value->kode ?>"><?= $value->kode . ' - ' . $value->nama . ' ' . $value->lama_sewa . ' ' . $value->jangka_sewa ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="tgl_transaksi">Tanggal</label>
                                <input id="tgl_transaksi" name="tgl_transaksi" autocomplete="off" required type="text" class="form-control datePicker">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="nominal">Nominal</label>
                                <input id="nominal" name="nominal" autocomplete="off" required type="text" class="form-control inputmask">
                            </div>
                        </div>
                        <div class="col-sm-6 col-xs-12">
                            <div class="form-group">
                                <label for="keterangan">Keterangan</label>
                                <input id="keterangan" name="keterangan" required autocomplete="off" type="text" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('pagescript')
<script>
    // Polyfill for $.number when jquery.number plugin is missing
    if (typeof $.number !== 'function') {
        $.number = function(number, decimals) {
            try {
                var opts = {};
                if (typeof decimals === 'number') {
                    opts.minimumFractionDigits = decimals;
                    opts.maximumFractionDigits = decimals;
                }
                return new Intl.NumberFormat('id-ID', opts).format(Number(number || 0));
            } catch (e) {
                // fallback simple formatting
                var n = Number(number || 0).toFixed(decimals || 0);
                return n.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }
        }
    }
    var t = moment('<?= $start ?>', 'YYYY-MM-DD'),
        a = moment('<?= $end ?>', 'YYYY-MM-DD');
    $(document).ready(function() {
        var table_bb = $("#tb_kamar").DataTable({
            order: [
                [3, 'ASC'],
                [1, 'DESC'],
            ],
            // "paging": false,
            // "info": false,
            "language": {
                "emptyTable": "Tidak ada data untuk ditampilkan, silakan gunakan filter",
            },
            columnDefs: [{
                targets: 5,
                render: $.fn.dataTable.render.number(',', '.', 2, 'Rp ')
            }],
            rowGroup: {
                dataSrc: [
                    function(row) {
                        return '<i class="fas fa-chevron-down"></i> ' + row[3];
                    }
                ],
                endRender: function(rows, group) {
                    // var numGroups = Math.ceil(rows.count()); //Math.round(rows.count() / 3) + 1;
                    // return group + ' (' + numGroups + ' groups max of 3)';
                    var avg =
                        rows
                        .data()
                        .pluck(5)
                        .reduce((a, b) => a + b.replace(/[(Rp ,)]|(&nbsp;|<([^>]+)>)/g, '') * 1, 0);

                    return (
                        'Total <span class="highlight text-dark">' + $.number(avg, 2) + '</span>'
                    );
                }
            }
        });
        table_bb.on('order.dt search.dt', function() {
            let i = 1;

            table_bb.cells(null, 0, {
                search: 'applied',
                order: 'applied'
            }).every(function(cell) {
                this.data(i++);
            });
        }).draw();
        var buttonCommon = {
            exportOptions: {
                format: {
                    body: function(data, row, column, node) {
                        if (column == 0) {
                            return data;
                        } else {
                            return column == 5 ?
                                data.replace(/[(Rp ,)]|(&nbsp;|<([^>]+)>)/g, '') :
                                data.replace(/(&nbsp;|<([^>]+)>)/ig, "");
                        }
                    }
                }
            }
        };
        var buttons = new $.fn.dataTable.Buttons(table_bb, {
            buttons: [
                $.extend(true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    filename: function() {
                        return "Laporan Pemasukan " + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        var data = "{{config('app.name')}} \n Laporan Pemasukan";
                        return data.replace(/<br>/g, String.fromCharCode(10));
                    },
                    messageTop: function() {
                        var data = '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]';
                        return data.replace(/<br>/g, String.fromCharCode(10));
                    },
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    filename: function() {
                        return "Laporan Pemasukan " + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Laporan Pemasukan",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova </h3><h4 class="m-0 p-0">Laporan Pemasukan</h4></span>',
                    messageTop: '<b>#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]</b><hr>',
                    pageSize: 'A4',
                })

            ]
        }).container().prependTo($('#button_export'));

        $('.btn_epdf').click(function() {
            $('.buttons-pdf').click();
        });
        $('.btn_exls').click(function() {
            $('.buttons-excel').click();
        });
        $('.btn_eprint').click(function() {
            $('.buttons-print').click();
        });
        table_bb.on('click', 'tbody tr:not(".dtrg-group")', (e) => {
            let classList = e.currentTarget.classList;

            if (classList.contains('selected')) {
                // classList.remove('selected');
            } else {
                table_bb.rows('.selected').nodes().each((row) => row.classList.remove('selected'));
                classList.add('selected');
            }
        });
    });
    $("#filter_faktur").daterangepicker({
        // minDate: periode,
        showDropdowns: true,
        locale: {
            format: "YYYY-MM-DD",
            "separator": " s/d ",
            "customRangeLabel": "<i class='fas fa-filter'></i> Custom range",
            "firstDay": 1
        },
        autoApply: true,
        startDate: t,
        endDate: a,
        ranges: {
            "Minggu Lalu": [moment().subtract(1, 'weeks').startOf('week'), moment().subtract(1, 'weeks').endOf('week')],
            "Minggu ini": [moment().startOf('week'), moment().endOf('week')],
            "Bulan ini": [moment().startOf("month"), moment().endOf("month")],
            "Bulan lalu": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],
            "Tahun ini": [moment().startOf('year'), moment().endOf("year")]
        }
    }, e), e(t, a, "");

    function e(t, a, e) {
        $("#filter").val(t.format("YYYY-MM-DD") + "s/d" + a.format("YYYY-MM-DD")).trigger('change');
        var n = "",
            s = "";
        a - t < 100 || "Hari ini" == e ? (n = "Hari ini:", s = t.format("YYYY-MM-DD")) : "Kemarin" == e ? (n = "Kemarin:", s = t.format("YYYY-MM-DD")) : s = t.format("YYYY-MM-DD") + " s/d " + a.format("YYYY-MM-DD"), $("#Select_date").html(s), $("#Day_Name").html(n)
    }
    $('#filter').on('change', function() {
        console.log($(this).val());
        $('#f_filter_tgl').submit();
    });
    $('#transaksi').on('select2:select', function() {
        var kurang = $(this).find(':selected').data('kurang');
        var section = $(this).find(':selected').data('section');
        $('#section').val(section);
        console.log(section);
        $('#nominal').attr('data-inputmask-max', kurang);
        init_component();
    });

    function terima(doc_id) {
        $('#md_pay_biaya').modal();
        $('#transaksi').val(doc_id).trigger('select2:select');
        // init_component();
    }

    // Ensure components (datepicker, inputmask, select2, etc.) are initialized
    function init_component() {
        try {
            // initialize daterangepicker single-date on elements with .datePicker
            if ($.fn.daterangepicker) {
                $('.datePicker').each(function() {
                    var $el = $(this);
                    if (!$el.data('daterangepicker')) {
                        $el.daterangepicker({
                            singleDatePicker: true,
                            showDropdowns: true,
                            locale: { format: 'YYYY-MM-DD' }
                        });
                    }
                });
            }
            // optional: inputmask initialization if plugin available
            if ($.fn.inputmask) {
                $('.inputmask').each(function() {
                    var $el = $(this);
                    // guard: only apply once
                    if (!$el.data('inputmask')) {
                        $el.inputmask();
                    }
                });
            }
            // re-init select2 inside modal
            if ($.fn.select2) {
                $('#transaksi').select2({
                    width: '100%',
                    dropdownParent: $('#md_pay_biaya')
                });
            }
        } catch (err) {
            console.error('init_component error', err);
        }
    }

    // initialize components when the payment modal is shown
    $('#md_pay_biaya').on('shown.bs.modal', function() {
        init_component();
        // focus the date input for convenience
        $(this).find('.datePicker').focus();
    });

    function deletes(e) {
        e.preventDefault();
        var url = e.currentTarget.getAttribute('href');
        $.confirm({
            title: 'Hapus data ini?',
            content: 'Aksi ini tidak dapat diurungkan',
            buttons: {
                confirm: {
                    text: 'Ya',
                    btnClass: 'btn-red',
                    keys: ['enter'],
                    action: function() {
                        window.location.href = url;
                    },
                },
                cancel: {
                    text: 'Batal',
                    action: function() {}
                }
            }
        });
    };
</script>
@stop
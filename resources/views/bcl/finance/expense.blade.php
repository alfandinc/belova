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
                    <h4 class="page-title">Laporan Pengeluaran</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    {{-- @can('Pengeluaran') --}}
                    <a href="#addBiaya" data-toggle="modal" data-target="#md_add_biaya" class="btn btn-danger btn-sm btn-square btn-outline-dashed waves-effect waves-light">
                        <i data-feather="plus" class="align-self-center icon-xs"></i> Input Pengeluaran
                    </a>
                    {{-- @endcan --}}
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Pengeluaran</h4>
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
                        <form id="f_filter_tgl" action="{{route('bcl.expense.index')}}" method="POST">
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
                                <table class="table table-sm table-hover mb-0 dataTable no-footer" id="tb_expense">
                                    <thead class="thead-info bg-info">
                                        <tr class="text-white">
                                            <th class="text-center text-white">No</th>
                                            <th class="text-center text-white">Tanggal</th>
                                            <th class="text-white">Nomor</th>
                                            <th class="text-white">Tipe Pengaluaran</th>
                                            <th class="text-white">Penerima Manfaat</th>
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
                                            <td><u><a href="javascript:void(0)" class="view_expense" data-id="{{$data->doc_id}}">{{$data->doc_id}}</a></u></td>
                                            <td>{{$data->tipe_pengeluaran}}</td>
                                            <td>
                                                @if(count($data->arr_inventory)>0)
                                                @foreach($data->arr_inventory as $value)
                                                <span class="badge badge-dark">{{isset($value)?$value->name:''}}</span>
                                                @endforeach
                                                @endif
                                            </td>
                                            <td>{{$data->catatan}}</td>
                                            <td class="text-right">Rp {{number_format($data->debet,2)}} </td>
                                            <td class="text-right">
                                                @role('Administrator')
                                                <a class="btn btn-xs btn-square btn-danger" onclick="deletes(event)" href="{{route('bcl.expense.delete',$data->doc_id)}}"><i class="fas fa-trash"></i></i></button>
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
<div class="modal fade" id="md_add_biaya" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog  modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambah Biaya (Expense)</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                </button>
            </div>
            <form method="POST" action="{{route('bcl.expense.store')}}" enctype="multipart/form-data">
                @csrf

                <div class="modal-body" id="loading_content">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label for="tgl_transaksi">Tanggal Transaksi</label>
                                <input id="tgl_transaksi" name="tgl_transaksi" autocomplete="off" required type="text" class="form-control datePicker">
                            </div>
                        </div>
                    </div>
                    <hr class="hr-dashed mt-0">

                    <div class="row">
                        <table class="table" id="tb_biaya">
                            <thead class="bg-soft-primary">
                                <tr>
                                    <th>Tipe Pengeluaran</th>
                                    <th>Penerima Manfaat<br><small class="text-danger">*kosongkan jika bukan inventaris kamar</small></th>
                                    <th>Deskripsi</th>
                                    <th>Jumlah</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody data-repeater-item="">
                                <tr data-id="0">
                                    <td width="25%">
                                        <select class="select2" name="akun[]" required>
                                            <option value="" selected></option>
                                            <option value="5-10101">Perbaikan/perawatan inventaris kamar atau bangunan</option>
                                            <option value="5-10102">Biaya Operasional/lain-lain</option>
                                        </select>
                                        <input type="hidden" name="items[]" value="">
                                    </td>
                                    <td width="20%">
                                        <select data-placeholder="Kosongkan jika bukan inventaris kamar" name="akun_subledger[0][]" class="select2 select2-multiple" multiple="multiple" id="akun_subledger" allow-clear="true" data-allow-clear="true">
                                            <option value=""></option>
                                            <?php
                                            foreach ($inventory as $value) {
                                            ?>
                                                <option value="<?= $value->inv_number ?>"><?= $value->name ?> (<?= $value->nama_kamar ?>)</option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="deskripsi[]">
                                    </td>
                                    <td width="15%">
                                        <input type="text" class="form-control inputmask" data-inputmask-prefix="Rp " name="jumlah[]">
                                    </td>
                                    <td class="text-center" width="1%">
                                        <a href="javascript:removelist(0)" title="Remove List"><i class="fas fa-minus text-danger"></i></a>
                                    </td>
                                </tr>
                            </tbody>

                        </table>
                    </div>
                    <div class="row mb-1">
                        <div class="col-sm-12">
                            <button type="button" onclick="createrow()" class="btn btn-outline-success btn-sm">
                                <span class="fa fa-plus"></span> Tambah Data
                            </button>
                        </div>
                    </div>
                    <hr class="hr-dashed mt-0">

                    <div class="row">
                        <div class="col-sm-6">

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">

                        </div>
                        <div class="col-sm-3 text-right">
                            <h5 class="font-weight-bold">Total</h5>
                        </div>
                        <div class="col-sm-3 text-right">
                            <h5 id="amm_total">Rp 0</h5>
                        </div>
                    </div>
                    <div class="row">
                        <div id="fileUpload"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_dt_biaya" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog  modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Informasi Pengeluaran</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="text-info mt-0" id="no_exp"></h3>
                    </div>
                    <div class="col-sm-6 text-right">
                        <h3 class="text-info mt-0"><span class="text-success">PAID</span></h3>
                    </div>
                </div>
                <hr class=" mt-0">
                <div class="row">
                    <dt class="col-sm-2">Tanggal</dt>
                    <dd class="col-sm-10" id="tgl_jurnal"></dd>
                    <dt class="col-sm-2">Oleh</dt>
                    <dd class="col-sm-10" id="user_id"></dd>
                </div>
                <hr class="hr-dashed mt-2">
                <div class="row">
                    <table class="table table-sm">
                        <thead class="bg-soft-primary">
                            <tr>
                                <th>Tipe Pengeluaran</th>
                                <th>Penerima Manfaat</th>
                                <th>Deskripsi</th>
                                <th class="text-right">Jumlah</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>

                <hr class="hr-dashed mt-0">
                <div class="row">
                    <div class="col-sm-6">
                    </div>
                    <div class="col-sm-3 text-right">
                        <h5 class="font-weight-bold">Total</h5>
                    </div>
                    <div class="col-sm-3 text-right">
                        <h5 class="amm_total" id="total_exp"></h5>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-sm-12 text-right">
                        <small class="text-muted"></small>
                    </div>
                </div>
                <div class="row" id="receipt">

                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('pagescript')
<script>
    var item = $('[data-repeater-item]').html();
    var itemid = 0;
    var t = moment('<?= $start ?>', 'YYYY-MM-DD'),
        a = moment('<?= $end ?>', 'YYYY-MM-DD');
    $(document).ready(function() {
        $(document).ready(function() {
            $("#fileUpload").fileUpload();
        });

        var table_bb = $("#tb_expense").DataTable({
            order: [
                [4, 'DESC'],
                [2, 'DESC']
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
                        .pluck(6)
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
                            return column == 6 ?
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
                        return "Laporan Pengeluaran " + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        var data = "{{config('app.name')}} \n Laporan Pengeluaran";
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
                        return "Laporan Pengeluaran " + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Laporan Pengeluaran",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova </h3><h4 class="m-0 p-0">Laporan Pengeluaran</h4></span>',
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

    function createrow() {
        // $.alert({
        //     type: 'red',
        //     title: 'Oops!',
        //     content: "Demi mencegah terjadinya `POTENTIAL FRAUD`, fungsi ini belum dapat digunakan",
        // });
        itemid++;
        new_elem = item.replace('"0"', '"' + itemid + '"');
        new_elem = new_elem.replace('removelist(0)', 'removelist(' + itemid + ')');
        new_elem = new_elem.replace('akun_subledger[0][]', 'akun_subledger[' + itemid + '][]');
        $('[data-repeater-item]').append(new_elem);
        init_component();
        $('[name="jumlah[]"]').on('change', function() {
            update_amount();
        });
    }

    function removelist(id) {
        if (id == 0) {
            $.alert({
                type: 'red',
                title: 'Oops!',
                content: 'Elemen pertama tidak dapat dihapus',
            });
        } else {
            $.confirm({
                title: 'Hapus List?',
                type: 'red',
                buttons: {
                    Ya: function() {
                        $('tr[data-id="' + id + '"]').remove();
                        update_amount();
                    },
                    Batal: function() {

                    }
                }
            });

        }
    }

    function update_select2(result, elem) {
        $(elem).empty();
        $.each(result, function(index, value) {
            var newOption = new Option(value.kode + ' | ' + value.nama, value.kode, false, false);
            $(elem).append(newOption);
        });
        $(elem).val(null).trigger('change');
    }


    $('[name="jumlah[]"]').on('change', function() {
        update_amount();
    });

    function update_amount() {
        var arr_jumlah = [];
        var total = 0;
        $('[name="jumlah[]"]').each(function() {
            arr_jumlah.push(parseInt($(this).val()));
            total += parseFloat($(this).val());
        });
        $('#amm_total').text('Rp ' + $.number(total));
    }

    $('.view_expense').on('click', function() {
        var id = $(this).data('id');
        var address = "{{route('bcl.expense.show',':id')}}";
        $.get(address, {
                id: id
            },
            function(data) {
                console.log(data);
                $('#receipt').empty();
                $('#md_dt_biaya').find('tbody').empty();
                var receipt = null;
                var total = 0;
                $.each(data, function(index, value) {
                    var manfaat = '';
                    $.each(value.arr_inventory, function(index, val) {
                        if (val != null) {
                            manfaat += '<span class="badge badge-dark">' + val.name + '</span> ';
                        }
                    });
                    if (value.receipt != null) {
                        receipt = value.receipt;
                    }
                    $('#no_exp').text(value.doc_id);
                    $('#tgl_jurnal').text(value.tanggal);
                    $('#user_id').text(value.user.name);
                    $('#md_dt_biaya').find('tbody').append('<tr><td>' + value.tipe_pengeluaran + '</td><td>' + manfaat + '</td><td>' + value.catatan + '</td><td class="text-right">Rp ' + $.number(value.debet, 2) + '</td></tr>');
                    total += parseFloat(value.debet);
                });
                if (receipt != null) {
                    $.each(receipt, function(index, val) {
                        var path = "{{asset('assets/images/receipt/')}}";
                        $('#receipt').append('<a href="' + path + '/' + val.img + '" class="image-popup-vertical-fit mr-2 " title="' + val.trans_id + '"><img class="img-thumbnail hvr-glow" width="150" src="' + path + '/' + val.img + '"></a>');
                    });
                }
                $('#total_exp').text('Rp ' + $.number(total, 2));
                $('#md_dt_biaya').modal();
                $(".image-popup-vertical-fit").magnificPopup({
                    type: "image",
                    closeOnContentClick: !0,
                    mainClass: "mfp-img-mobile",
                    image: {
                        verticalFit: !0
                    }
                })
            });
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
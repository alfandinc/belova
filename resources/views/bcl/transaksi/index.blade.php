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
                    <h4 class="page-title">Transaksi Sewa</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    <button class="btn btn-outline-dashed btn-square btn-primary waves-effect waves-light" data-toggle="modal" data-target="#md_extra" id="bt_extra">
                        <i class="mdi mdi-plus"></i> Tambahan Sewa
                    </button>
                    <button class="btn btn-outline-dashed btn-square btn-success waves-effect waves-light" data-toggle="modal" data-target="#md_sewa" id="bt_sewa">
                        <i class="mdi mdi-check-all"></i> Sewa Kamar
                    </button>
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->

@if($belum_lunas->count()>0 or $belum_lunas_extra->count()>0)
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
                <div class="table-responsive-sm mb-3">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($belum_lunas->count() > 0) {
                                foreach ($belum_lunas as $value) { ?>
                                    <tr>
                                        <td><?= $no ?></td>
                                        <td><?= $value->tanggal ?></td>
                                        <td><?= $value->doc_id ?></td>
                                        <td><?= $value->identity == 'Sewa Kamar' ? 'Pendapatan Sewa' : 'Pendapatan Lain' ?></td>
                                        <td><?= $value->catatan ?></td>
                                        <td class="text-right">Rp <?= number_format($value->harga, 2) ?></td>
                                        <td class="text-right text-danger font-weight-bold">Rp <?= number_format($value->kurang, 2) ?></td>
                                    </tr>
                                <?php $no++; }
                            }
                            if ($belum_lunas_extra->count() > 0) {
                                foreach ($belum_lunas_extra as $value) {
                                    $total_harga = $value->harga * $value->lama_sewa * $value->qty; ?>
                                    <tr>
                                        <td><?= $no ?></td>
                                        <td><?= $value->tgl_mulai ?></td>
                                        <td><?= $value->kode ?></td>
                                        <td>Tambahan Sewa</td>
                                        <td><?= $value->nama . ' ' . $value->lama_sewa . ' ' . $value->jangka_sewa . ' ' . $value->renter->nama ?></td>
                                        <td class="text-right">Rp <?= number_format($total_harga, 2) ?></td>
                                        <td class="text-right text-danger font-weight-bold">Rp <?= number_format($total_harga - ($value->total_kredit == null ? 0 : $value->total_kredit), 2) ?></td>
                                    </tr>
                                <?php $no++; }
                            } ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-danger">*Untuk melakukan pelunasan silahkan melalui menu Keuangan -> Pemasukan</small>
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
                        <h4 class="card-title text-white">Daftar Transaksi</h4>
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
                        <form id="f_filter_tgl" action="{{route('bcl.transaksi.index')}}" method="POST">
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
                                            <th class="text-center text-white">Tgl. Trans</th>
                                            <th class="text-white">Kode Trans.</th>
                                            <th class="text-white">No. Kamar</th>
                                            <th class="text-white">Penyewa</th>
                                            <th class="text-white">Jangka Sewa</th>
                                            <th class="text-white">Periode</th>
                                            <th class="text-white text-right">Harga Sewa</th>
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
                                            <td><u><a href="javascript:void(0)" data-id="{{$data->trans_id}}" class="dt_transaksi">{{$data->trans_id}}</a></u></td>
                                            <td>{{$data->room->room_name??'Kamar dihapus'}} <span class="badge badge-success">{{count($data->tambahan)>0?'+':''}}</span></td>
                                            <td>{{$data->renter->nama??'N/a'}}</td>
                                            <td>{{$data->lama_sewa.' '.$data->jangka_sewa}}</td>
                                            <td>{{$data->tgl_mulai.' s/d '.$data->tgl_selesai}}</td>
                                            <td class="text-right text-nowrap">Rp {{number_format($data->harga,2)}} <a href="#" style="padding: 1px 10px;" data-toggle="dropdown" class="btn btn-xs btn-primary dropdown-toggle"><i class="fas fa-ellipsis-v"></i></a>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item ubah_tgl_masuk" href="javascript:void(0)" data-trans="{{$data->trans_id}}"><i class="fas fa-calendar-alt"></i> Ubah Tgl. Masuk (Reschedule)</a>
                                                    <a class="dropdown-item change_room" href="javascript:void(0)" data-trans="{{$data->trans_id}}" data-room="{{$data->room_id}}"><i class="fas fa-exchange-alt"></i> Pindah Kamar</a>
                                                    <a class="dropdown-item refund" href="javascript:void(0)" data-trans="{{$data->trans_id}}"><i class="fas fa-hand-holding-usd"></i> Refund Transaksi</a>
                                                    <a class="dropdown-item" href="{{route('bcl.transaksi.cetak',$data->trans_id)}}"><i class="fas fa-print"></i> Cetak Transaksi</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="{{route('bcl.transaksi.delete',$data->trans_id)}}" onclick="deletes(event)"><i class="fas fa-trash"></i> Hapus Transaksi</a>
                                                </div>
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
<div class="modal fade" id="md_ubah_tgl_masuk" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Ubah Tgl Masuk (Reschedule)</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.transaksi.reschedule')}}" method="POST">
                @csrf
                <input type="hidden" name="trans_id" id="trans_id">
                <div class="modal-body">
                    <div class="row mt-3">
                        <div class="col-md-12 col-sm-12">
                            <label class="">Tgl. Rencana Masuk</label>
                            <input type="text" id="tgl_rencana_masuk" name="tgl_rencana_masuk" class="form-control datePicker">
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
<div class="modal fade" id="md_refund" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Refund Transaksi</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.transaksi.refund')}}" method="POST">
                @csrf
                <input type="hidden" name="kode_trans" id="kode_trans">
                <div class="modal-body">
                    <div class="row mt-3">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tgl. Refund</label>
                            <input type="text" id="tgl_refund" name="tgl_refund" class="form-control datePicker">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Nominal Refund</label>
                            <input type="text" id="nominal_refund" required name="nominal_refund" class="form-control inputmask">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tanggal Keluar</label>
                            <input type="text" id="tgl_keluar" required name="tgl_keluar" class="form-control datePicker">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Alasan</label>
                            <input type="text" id="alasan" name="alasan" class="form-control">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="md_sewa" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Sewa Kamar</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.rooms.sewa')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 col-sm-12">
                            <label class="">Penyewa</label>
                            <select class="mb-3 select2" name="renter" required style="width: 100%" data-placeholder="Pilih Penyewa">
                                <option value=""></option>
                                @foreach($renter as $rent)
                                <option value="{{$rent->id}}">{{$rent->nama}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label class="">No/Nama Kamar</label>
                            <select class="mb-3 select2" id="kamar" name="kamar" required style="width: 100%" data-placeholder="Pilih Kamar">
                                <option value=""></option>
                                @foreach($rooms as $room)
                                <option value="{{$room->id}}" data-room_category="{{$room->category->id_category}}">{{$room->room_name.' '.$room->category->category_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label class="">Durasi Sewa</label>
                            <select class="mb-3 select2" id="pricelist" name="pricelist" required style="width: 100%" data-placeholder="Pilih Durasi">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4 col-sm-12">
                            <label class="">Tanggal Rencana Masuk</label>
                            <input type="text" id="tgl_masuk" required name="tgl_masuk" class="form-control datePicker">
                        </div>
                        <div class="col-md-8 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" id="catatan" name="catatan" class="form-control">
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row mt-3">
                        <div class="col-md-4 col-sm-12">
                            <label class="">Tanggal Terima Pembayaran</label>
                            <input type="text" id="tgl_bayar" required name="tgl_bayar" class="form-control datePicker">
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <label class="">Nominal</label>
                            <input type="text" id="nominal" required name="nominal" class="form-control inputmask">
                            <small class="form-text text-muted">*Jika Pembayaran kurang dari harga, maka akan dianggap sebagai DP</small>
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
<div class="modal fade" id="md_dt_trans" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog  modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Informasi Transaksi</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="text-info mt-0" id="no_trans"></h3>
                    </div>
                    <div class="col-sm-6 text-right">
                        <h3 class="text-info mt-0"><span class="text-success" id="paid_status">PAID</span></h3>
                    </div>
                </div>
                <hr class=" mt-0">
                <div class="row">
                    <dt class="col-sm-2">Tanggal tr. Sewa</dt>
                    <dd class="col-sm-10" id="tgl_transaksi"></dd>
                    <dt class="col-sm-2">Oleh</dt>
                    <dd class="col-sm-10" id="user_id"></dd>
                </div>
                <hr class="hr-dashed mt-2">
                <div class="row">
                    <table class="table table-sm" id="tb_tr">
                        <thead class="bg-soft-primary">
                            <tr>
                                <th>Tanggal Tr.</th>
                                <th>Catatan</th>
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_extra" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambahan Sewa</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.extrarent.store')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Item</label>
                            <select class="mb-3 select2" name="pricelist" id="pricelist_extra" required style="width: 100%" data-placeholder="Pilih">
                                <option value=""></option>
                                @foreach($extra_pricelist as $pl_xtra)
                                <option data-lama="{{$pl_xtra->jangka_sewa}}" value="{{$pl_xtra->id}}">{{$pl_xtra->nama.' ('.number_format($pl_xtra->harga,2).'/'.$pl_xtra->jangka_sewa.')'}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tanggal Sewa</label>
                            <input type="text" id="tgl_sewa" required name="tgl_sewa" class="form-control datePicker">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jumlah Item</label>
                            <input type="text" id="jml_item" required name="jml_item" class="form-control inputmask">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Lama Sewa</label>
                            <input type="text" id="lama_sewa" required name="lama_sewa" data-inputmask-suffix="" class="form-control inputmask">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <label class="">Transaksi Penyewa</label>
                        <select class="mb-3 select2" name="trans_id" id="trans_id" required style="width: 100%" data-placeholder="Pilih">
                            <option value=""></option>
                            @foreach($rooms as $room)
                            @if($room->renter!=null)
                            <option value="{{$room->renter->trans_id}}">{{$room->room_name.' ('.$room->renter->nama.')'}}</option>
                            @endif
                            @endforeach
                        </select>
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
    var t = moment('<?= $start ?>', 'YYYY-MM-DD'),
        a = moment('<?= $end ?>', 'YYYY-MM-DD');
    $(document).ready(function() {
        $('#pricelist_extra').on('select2:select', function() {
            var data = $(this).find(':selected');
            var lama_sewa = data.data('lama');
            console.log(lama_sewa);
            $('#lama_sewa').attr('data-inputmask-suffix', " " + lama_sewa);
            init_component();
        });
        var table_bb = $("#tb_kamar").DataTable({
            order: [
                [1, 'DESC']
            ],
            // "paging": false,
            // "info": false,
            "language": {
                "emptyTable": "Tidak ada data untuk ditampilkan, silakan gunakan filter",
            },
            // columnDefs: [{
            //     targets: 7,
            //     render: $.fn.dataTable.render.number(',', '.', 2, 'Rp ')
            // }],
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
                        .pluck(7)
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
                            return column == 7 ?
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
                        return "Transaksi Sewa " + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        var data = "{{config('app.name')}} \n Transaksi Sewa";
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
                        return "Transaksi Sewa " + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Transaksi Sewa",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova </h3><h4 class="m-0 p-0">Transaksi Sewa</h4></span>',
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
    $('#kamar').on('select2:select', function() {
        var id = $(this).find(':selected').data('room_category');
        $.ajax({
            url: "{{route('bcl.pricelist.get_pl_room', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                // console.log(data);
                $('#pricelist').empty();
                $('#pricelist').append('<option value=""></option>');
                $.each(data, function(index, value) {
                    $('#pricelist').append('<option data-harga="' + value.price + '" value="' + value.id + '">' + value.jangka_waktu + ' ' + value.jangka_sewa + ' ' + $.number(value.price) + '</option>');
                });
            }
        });
    });
    $('#pricelist').on('select2:select', function() {
        var harga = $(this).find(':selected').data('harga');
        $('#nominal').inputmask({
            min: 0,
            max: parseInt(harga),
            autoUnmask: "true",
            unmaskAsNumber: "true",
            'removeMaskOnSubmit': true,
            alias: 'decimal',
            groupSeparator: ',',
        });
    });
    $('.dt_transaksi').on('click', function() {
        var id = $(this).data('id');
        var address = "{{route('bcl.transaksi.show', ':id')}}";
        $.get(address, {
            'id': id
        }, function(data) {
            // console.log(data);
            $('#md_dt_trans').modal();
            $('#tgl_transaksi').text(data.tanggal);
            // generate script for place ajax data to my modal
            $('#no_trans').text(data.trans_id);
            $('#tb_tr tbody').empty();
            var total = 0;
            $.each(data.jurnal, function(index, value) {
                $('#tb_tr tbody').append('<tr><td>' + value.tanggal + '</td><td>' + value.catatan + '</td><td class="text-right">Rp ' + $.number(value.kredit, 2) + '</td></tr>');
                total += parseInt(value.kredit);
                $('#user_id').text(value.name);
            });
            var total_tbh = 0;
            var total_dibayar = 0;
            $.each(data.tambahan, function(index, value) {
                $('#tb_tr tbody').append('<tr><td>' + value.tgl_mulai + '</td><td>' + value.nama + '</td><td class="text-right">Rp ' + $.number(value.harga, 2) + '</td></tr>');
                total_tbh += parseInt(value.harga);
                $.each(value.jurnal, function(index, val) {
                    total_dibayar += parseInt(val.kredit);
                });
            });
            $('#total_exp').text('Rp ' + $.number(total + total_tbh, 2));
            if (parseInt(data.harga) + total_tbh > total + total_dibayar) {
                $('#paid_status').removeClass('text-success').addClass('text-danger').text('BELUM LUNAS');
            } else {
                $('#paid_status').removeClass('text-danger').addClass('text-success').text('LUNAS');
            }
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

    $('.refund').on('click', function() {
        var kode_trans = $(this).data('trans');
        console.log(kode_trans);
        $('#kode_trans').val(kode_trans);
        $('#md_refund').modal();
    });
    $('.ubah_tgl_masuk').on('click', function() {
        var kode_trans = $(this).data('trans');
        $('#trans_id').val(kode_trans);
        $('#md_ubah_tgl_masuk').modal();
    });

    // Polyfill for $.number when jquery.number plugin is missing
    if (typeof $.number !== 'function') {
        $.number = function(number, decimals) {
            if (number === null || number === undefined || number === '') return '';
            var d = typeof decimals === 'number' ? decimals : 0;
            try {
                var nf = new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: d,
                    maximumFractionDigits: d
                });
                return nf.format(Number(number));
            } catch (e) {
                // Fallback simple formatting
                var n = Number(number).toFixed(d);
                return n.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        };
    }

    // Minimal shim for $.fn.inputmask when jquery.inputmask plugin is missing
    if (typeof $.fn.inputmask !== 'function') {
        $.fn.inputmask = function(opts) {
            // allow method calls like $(el).inputmask({ ... })
            if (typeof opts === 'object') {
                this.each(function() {
                    var $el = $(this);
                    $el.data('inputmask-options', opts);
                    // very small behavior: restrict to numeric characters and basic formatting
                    if (opts.alias === 'decimal' || opts.alias === undefined) {
                        $el.off('input.inputmaskShim').on('input.inputmaskShim', function() {
                            var v = $el.val().toString();
                            // allow digits, dots, commas, minus
                            v = v.replace(/[^0-9.,-]/g, '');
                            // simple grouping on blur will be handled if needed
                            $el.val(v);
                        });
                    }
                });
            }
            return this;
        };
    }

    // Initialize date pickers (single-date) for fields with class .datePicker
    try {
        if ($.fn.daterangepicker) {
            $('.datePicker').each(function() {
                if (!$(this).data('daterangepicker')) {
                    $(this).daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        locale: { format: 'YYYY-MM-DD' }
                    });
                }
            });
        }
    } catch (err) {
        console.error('Datepicker init error', err);
    }

    // Helper: unformat currency-like string to plain number (e.g. "1,600,000" -> "1600000")
    function unformatNumber(value) {
        if (value === null || value === undefined) return '';
        // remove anything that's not digit, minus sign or dot
        var cleaned = value.toString().replace(/[^0-9.-]/g, '');
        // If multiple dots/hyphens, keep first occurrences reasonably
        var parts = cleaned.split('.');
        if (parts.length > 2) {
            cleaned = parts.shift() + '.' + parts.join('');
        }
        // remove stray minus signs except leading
        cleaned = cleaned.replace(/-(?=.)/g, '');
        return cleaned;
    }

    // Before submitting the Sewa Kamar form, ensure #nominal contains a plain numeric string
    $(document).on('submit', 'form[action="{{route('bcl.rooms.sewa')}}"]', function(e) {
        var $nom = $(this).find('#nominal');
        if ($nom.length) {
            var raw = $nom.val();
            var un = unformatNumber(raw);
            $nom.val(un);
        }
        // also for other forms that might submit formatted inputs (defensive)
        $(this).find('input.inputmask').each(function() {
            var $i = $(this);
            var v = $i.val();
            var u = unformatNumber(v);
            $i.val(u);
        });
        // Normalize datePicker inputs to YYYY-MM-DD to avoid epoch default (1970-01-01)
        $(this).find('.datePicker').each(function() {
            var $d = $(this);
            var val = $d.val();
            if (!val) return;
            // If daterangepicker was used (singleDatePicker), it stores 'MM/DD/YYYY' or 'YYYY-MM-DD' depending on locale
            // Try parsing with moment if available, else attempt manual ISO conversion
            if (typeof moment === 'function') {
                var m = moment(val, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY', moment.ISO_8601], true);
                if (!m.isValid()) {
                    // try lenient parse
                    m = moment(val);
                }
                if (m.isValid()) {
                    $d.val(m.format('YYYY-MM-DD'));
                }
            } else {
                // Basic fallback: try to detect common separators
                var parts = val.split(/[-\/]/);
                if (parts.length === 3) {
                    // guess order: if first part length is 4 -> YYYY-MM-DD
                    if (parts[0].length === 4) {
                        $d.val(parts[0] + '-' + parts[1].padStart(2, '0') + '-' + parts[2].padStart(2, '0'));
                    } else {
                        // assume MM-DD-YYYY -> convert
                        $d.val(parts[2] + '-' + parts[0].padStart(2, '0') + '-' + parts[1].padStart(2, '0'));
                    }
                }
            }
        });
        return true;
    });

    // Global: normalize datePicker and inputmask fields for any form submission (covers reschedule form)
    $(document).on('submit', 'form', function(e) {
        var $form = $(this);
        // Normalize any .datePicker fields inside this form
        $form.find('.datePicker').each(function() {
            var $d = $(this);
            var val = $d.val();
            if (!val) return;
            if (typeof moment === 'function') {
                var m = moment(val, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY', moment.ISO_8601], true);
                if (!m.isValid()) m = moment(val);
                if (m.isValid()) $d.val(m.format('YYYY-MM-DD'));
            } else {
                var parts = val.split(/[-\/]/);
                if (parts.length === 3) {
                    if (parts[0].length === 4) {
                        $d.val(parts[0] + '-' + parts[1].padStart(2, '0') + '-' + parts[2].padStart(2, '0'));
                    } else {
                        $d.val(parts[2] + '-' + parts[0].padStart(2, '0') + '-' + parts[1].padStart(2, '0'));
                    }
                }
            }
        });

        // Unformat any inputmask-style fields
        $form.find('input.inputmask').each(function() {
            var $i = $(this);
            var v = $i.val();
            var u = unformatNumber(v);
            $i.val(u);
        });
        // allow other submit handlers to proceed
        return true;
    });

    // Handle change room button click
    $(document).on('click', '.change_room', function(e) {
        e.preventDefault();
        var transId = $(this).data('trans');
        var currentRoomId = $(this).data('room');
        
        $('#change_room_trans_id').val(transId);
        $('#current_room_id').val(currentRoomId);
        
        // Prevent selecting the current room
        $('#new_room_id option').prop('disabled', false);
        $('#new_room_id option[value="' + currentRoomId + '"]').prop('disabled', true);
        
        // Initialize inputmask for payment amount
        if ($.fn.inputmask) {
            $('#payment_amount').inputmask({
                'alias': 'numeric',
                'groupSeparator': '.',
                'radixPoint': ',',
                'autoGroup': true,
                'digits': 2,
                'digitsOptional': false,
                'suffix': '',
                'placeholder': '0'
            });
        }
        
        // Manually initialize the datepicker
        if ($.fn.daterangepicker) {
            $('#effective_date').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                locale: { format: 'YYYY-MM-DD' },
                autoApply: true
            });
            
            // Set default date to today
            $('#effective_date').val(moment().format('YYYY-MM-DD'));
            
            // Also initialize the payment date picker
            $('#payment_date').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                locale: { format: 'YYYY-MM-DD' },
                autoApply: true
            });
            
            // Set default date for payment
            $('#payment_date').val(moment().format('YYYY-MM-DD'));
        }
        
        // Open the modal after initialization
        $('#md_change_room').modal('show');
    });
    
    // Dynamic data for change room modal
    let changeRoomData = { transaction: null, options: [] };

    function loadChangeRoomOptions(transId){
        return $.getJSON(`/bcl/transaksi/change-room/options/${transId}`);
    }
    function loadTransaction(transId){
        return $.getJSON(`/bcl/transaksi/show/${transId}`);
    }

    function sumPaidJurnal(trx){
        if(!trx || !trx.jurnal) return 0;
        // kredit entries of identity 'Sewa Kamar' or 'Upgrade Kamar'
        return trx.jurnal.filter(j=> (j.identity||'').toLowerCase().match(/sewa kamar|upgrade kamar/)).reduce((a,b)=> a + (parseFloat(b.kredit)||0),0);
    }

    function populateRoomSelect(){
        const $sel = $('#new_room_id');
        $sel.empty().append('<option value="">-- Pilih Kamar --</option>');
        changeRoomData.options.forEach(opt=>{
            if(!opt || !opt.price) return; // skip null
            $sel.append(`<option data-price="${opt.price}" value="${opt.room.id}">${opt.room.name} - ${opt.room.category_name} (Rp ${formatNumber(opt.price)})</option>`);
        });
    }

    function recalcPayment(){
        if(!changeRoomData.transaction) return;
    const trx = changeRoomData.transaction;
        const lama = parseInt(trx.lama_sewa);
        const jangka = (trx.jangka_sewa||'').toLowerCase();
        const effective = moment($('#effective_date').val(), 'YYYY-MM-DD');
        const start = moment(trx.tgl_mulai,'YYYY-MM-DD');
        const end = moment(trx.tgl_selesai,'YYYY-MM-DD');
        if(!effective.isValid()) return;

        // elapsed units (floor) using effective - start (not start - effective)
        let elapsed = 0;
        if(effective.isSameOrAfter(start)){
            switch(jangka){
                case 'bulan':
                    elapsed = effective.diff(start,'months');
                    break;
                case 'minggu':
                    elapsed = effective.diff(start,'weeks');
                    break;
                case 'tahun':
                    elapsed = effective.diff(start,'years');
                    break;
                case 'hari':
                default:
                    elapsed = effective.diff(start,'days');
                    break;
            }
        }
        if(elapsed < 0) elapsed = 0; // safety (shouldn't happen now)
        if(elapsed > lama) elapsed = lama; // cap at contract length
        const remaining = lama - elapsed;
        const remainingPercent = lama>0? remaining/lama : 0;

        // old full package price = trx.harga (stored)
    const oldFull = parseFloat(trx.harga);
    const alreadyPaid = sumPaidJurnal(trx);
    const outstandingOld = Math.max(oldFull - alreadyPaid,0);
        // new full package price from selected option
        const sel = $('#new_room_id').find(':selected');
        const newFull = parseFloat(sel.data('price')) || 0;
        const diffFull = newFull - oldFull;
        const payable = Math.round(diffFull * remainingPercent); // upgrade if >0 else refund
        
        // Get current "Bayar Sekarang" value
        let payNowRaw = $('#pay_now').val();
        let payNow = parseFloat(unformatNumber(payNowRaw)) || 0;
        
        // Cap the payment to the payable amount (prevent paying more than required)
        if (payable > 0 && payNow > payable) {
            payNow = payable;
            $('#pay_now').val(formatNumber(payNow));
        }
        
        // Calculate remaining due after payment
        const remainingDue = payable > 0 ? Math.max(0, payable - payNow) : 0; // Only track unpaid for upgrades

        // update UI
        $('#old_package_price').text('Rp '+formatNumber(oldFull.toFixed(2)));
        $('#new_package_price').text('Rp '+formatNumber(newFull.toFixed(2)));
        $('#diff_full').text('Rp '+formatNumber(diffFull.toFixed(2)));
        $('#total_units').text(lama+' '+trx.jangka_sewa);
        $('#elapsed_units').text(elapsed+' '+trx.jangka_sewa);
        $('#remaining_units').text(remaining+' '+trx.jangka_sewa);
        $('#remaining_percent').text((remainingPercent*100).toFixed(1)+'%');
        $('#payment_amount_text').text('Rp '+formatNumber(Math.abs(payable).toFixed(2)));
        $('#old_total_package_text').text('Rp '+formatNumber(oldFull.toFixed(0)));
        $('#already_paid_text').text('Rp '+formatNumber(alreadyPaid.toFixed(0)));
        $('#outstanding_old_text').text('Rp '+formatNumber(outstandingOld.toFixed(0)));
        $('#pay_now_text').text('Rp '+formatNumber(payNow));
        $('#remaining_due_text').text('Rp '+formatNumber(remainingDue));

        // total due now logic:
        // If upgrade: user must cover (outstandingOld + prorated upgrade - amount already being paid now)
        // If downgrade: potential refund reduced by any outstanding (can't refund what they haven't paid) -> refundable = min(alreadyPaid, |payable|)
        let totalDueNow = 0;
        if(payable>0){
            // Subtract any amount being paid now from the total due
            totalDueNow = outstandingOld + payable - payNow; 
        }else if(payable<0){
            const refundBase = Math.min(alreadyPaid, Math.abs(payable));
            totalDueNow = -refundBase; // negative means refund
        }else{ // no price diff
            totalDueNow = outstandingOld; // still need to settle remaining of old package if any
        }
        const totalDueNowAbs = Math.abs(totalDueNow);
        $('#total_due_now_text').text((totalDueNow<0? '- ':'')+'Rp '+formatNumber(totalDueNowAbs.toFixed(2)));
        if(payable>0){
            $('#payment_type_text').html('<span class="text-primary">Upgrade: bayar tambahan' + (remainingDue > 0 ? ' (akan tercatat di transaksi belum lunas)' : '') + '</span>');
            $('#payment_type_hidden').val('charge');
            // Show payment input
            $('#payment_input_row').show();
        }else if(payable<0){
            $('#payment_type_text').html('<span class="text-success">Downgrade: kemungkinan refund' + (outstandingOld > 0 ? ' (dikurangi tunggakan)' : '') + '</span>');
            $('#payment_type_hidden').val('refund');
            // Hide payment input for refund
            $('#payment_input_row').hide();
            $('#pay_now').val('0');
        }else{
            $('#payment_type_text').html('<span class="text-muted">Tidak ada selisih paket' + (outstandingOld > 0 ? ' (hanya tunggakan lama)' : '') + '</span>');
            $('#payment_type_hidden').val('none');
            // Hide payment input when no price difference
            $('#payment_input_row').hide();
            $('#pay_now').val('0');
        }
        // Update hidden fields
        $('#payment_amount_hidden').val(Math.abs(payable));
        $('#payment_total_due_hidden').val(totalDueNow);
        $('#pay_now_hidden').val(payNow);
        $('#remaining_due_hidden').val(remainingDue);
    }

    // Helper for unformatting numbers (e.g. "1,234.56" -> 1234.56)
    function unformatNumber(value) {
        return value ? value.toString().replace(/[^\d.-]/g, '') : '0';
    }

    // hook changes
    $(document).on('change','#new_room_id',recalcPayment);
    $(document).on('change','#effective_date',recalcPayment);
    $(document).on('input','#pay_now',recalcPayment);

    // Override open handler to load data first
    $(document).off('click','.change_room').on('click','.change_room',function(e){
        e.preventDefault();
        const transId = $(this).data('trans');
        $('#change_room_trans_id').val(transId);
        $('#current_room_id').val($(this).data('room'));
        $('#new_room_id').html('<option value="">Loading...</option>');
        $('#md_change_room').modal('show');
        // add hidden for total due if not present
        if(!$('#payment_total_due_hidden').length){
            $('#form_change_room').append('<input type="hidden" id="payment_total_due_hidden" name="payment_total_due" value="0" />');
        }
        $.when(loadTransaction(transId), loadChangeRoomOptions(transId)).done(function(trxRes, optRes){
            changeRoomData.transaction = trxRes[0];
            changeRoomData.options = optRes[0].rooms;
            populateRoomSelect();
            // init pickers if not already
            if ($.fn.daterangepicker && !$('#effective_date').data('daterangepicker')){
                $('#effective_date').daterangepicker({singleDatePicker:true,showDropdowns:true,locale:{format:'YYYY-MM-DD'},autoApply:true});
                $('#payment_date').daterangepicker({singleDatePicker:true,showDropdowns:true,locale:{format:'YYYY-MM-DD'},autoApply:true});
                $('#effective_date').val(moment().format('YYYY-MM-DD'));
                $('#payment_date').val(moment().format('YYYY-MM-DD'));
            }
            recalcPayment();
        }).fail(function(){
            $('#new_room_id').html('<option value="">Gagal memuat opsi</option>');
        });
    });
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
</script>

<!-- Modal for Room Change -->
<div class="modal fade" id="md_change_room" tabindex="-1" role="dialog" aria-labelledby="roomChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white" id="roomChangeModalLabel">Pindah Kamar</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{route('bcl.transaksi.change_room')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="trans_id" id="change_room_trans_id">
                    <input type="hidden" name="current_room_id" id="current_room_id">
                    
                    <div class="form-group row">
                        <div class="col-lg-12">
                            <label for="new_room_id">Pilih Kamar Baru (Harga Paket {{isset($data[0])?$data[0]->lama_sewa:''}} {{isset($data[0])?$data[0]->jangka_sewa:''}})</label>
                            <select name="new_room_id" id="new_room_id" class="form-control" required>
                                <option value="">-- Loading opsi kamar --</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="payment_amount" id="payment_amount_hidden">
                    <input type="hidden" name="payment_type" id="payment_type_hidden">
                    <input type="hidden" name="payment_total_due" id="payment_total_due_hidden">
                    <input type="hidden" name="remaining_due" id="remaining_due_hidden" value="0">
                    
                    <div class="form-group row">
                        <div class="col-lg-12">
                            <label for="effective_date">Tanggal Pindah Kamar</label>
                            <input type="text" name="effective_date" id="effective_date" class="form-control datePicker" required placeholder="Pilih Tanggal">
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <div class="col-lg-12">
                            <label for="payment_date">Tanggal Pembayaran/Refund</label>
                            <input type="text" name="payment_date" id="payment_date" class="form-control datePicker" value="{{date('Y-m-d')}}">
                        </div>
                    </div>
                    
                    <div class="form-group row" id="payment_input_row">
                        <div class="col-lg-12">
                            <label for="pay_now">Bayar Sekarang</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" name="pay_now" id="pay_now" class="form-control inputmask text-right" data-inputmask="'alias': 'decimal', 'groupSeparator': ','" value="0">
                                <input type="hidden" name="pay_now_hidden" id="pay_now_hidden" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info payment-info">
                        <p class="mb-1">Perhitungan berdasarkan paket penuh & proporsi sisa durasi.</p>
                        <table class="table table-sm mb-2">
                            <tr><td width="40%">Paket Lama</td><td id="old_package_price">Rp 0</td></tr>
                            <tr><td>Paket Baru</td><td id="new_package_price">Rp 0</td></tr>
                            <tr><td>Selisih Paket</td><td id="diff_full">Rp 0</td></tr>
                            <tr><td>Durasi Total</td><td id="total_units">-</td></tr>
                            <tr><td>Sudah Terpakai</td><td id="elapsed_units">-</td></tr>
                            <tr><td>Sisa</td><td id="remaining_units">-</td></tr>
                            <tr class="font-weight-bold"><td>Proporsi Sisa</td><td id="remaining_percent">0%</td></tr>
                        </table>
                        
                        <h6 class="mb-2 text-primary">Status Pembayaran Saat Ini:</h6>
                        <table class="table table-sm mb-2">
                            <tr><td width="40%">Total Paket Lama</td><td id="old_total_package_text">Rp 0</td></tr>
                            <tr><td>Sudah Dibayar</td><td id="already_paid_text" class="text-success">Rp 0</td></tr>
                            <tr class="font-weight-bold"><td>Kurang (Belum Lunas)</td><td id="outstanding_old_text" class="text-danger">Rp 0</td></tr>
                        </table>
                        
                        <h6 class="mb-2 text-warning">Tagihan Pindah Kamar:</h6>
                        <table class="table table-sm mb-2">
                            <tr class="font-weight-bold"><td width="40%">Tagihan / Refund</td><td id="payment_amount_text">Rp 0</td></tr>
                            <tr><td>Bayar Sekarang</td><td id="pay_now_text" class="text-success">Rp 0</td></tr>
                            <tr class="font-weight-bold"><td>Sisa Tagihan</td><td id="remaining_due_text" class="text-danger">Rp 0</td></tr>
                            <tr class="font-weight-bold border-top"><td>Total Yang Harus Dibayar</td><td id="total_due_now_text" class="text-primary">Rp 0</td></tr>
                        </table>
                        
                        <div id="payment_type_text" class="mt-1 font-weight-bold"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop
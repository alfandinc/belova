@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<?php

use Carbon\Carbon;

$inv = $data;
?>
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Inventaris</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col text-right">
                    <!-- <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#md_perbaikan_inv"><i class="mdi mdi-wrench"></i> Perbaikan/Perawatan Inventaris</button> -->
                    {{-- @can('Tambah Inventaris') --}}
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#md_tambah_inv"><i class="mdi mdi-plus"></i> Tambah Inventaris</button>
                    {{-- @endcan --}}
                </div>
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
                        <h4 class="card-title text-white">Daftar Inventaris</h4>
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
                                            <th class="text-center text-white">Nomor Inv</th>
                                            <th class="text-white">Nama</th>
                                            <th class="text-white">Catatan</th>
                                            <th class="text-white">Tipe</th>
                                            <th class="text-white">Kamar</th>
                                            <th class="text-white">Perawatan Rutin</th>
                                            <th class="text-white">Terakhir Perawatan</th>
                                            <th class="text-white">Perawatan Berikutnya</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        ?>
                                        @foreach($inv as $data)
                                        <?php /* next_maintanance and remaining_badge computed server-side in controller */ ?>

                                        <tr>
                                            <td class="text-center">{{ $no }}</td>
                                            <td class="text-center"><u><a href="javascript:void(0)" class="view_inv" data-id="{{ $data->inv_number }}">{{ $data->inv_number }}</a></u></td>
                                            <td>{{$data->name}}</td>
                                            <td>{{$data->notes}}</td>
                                            <td>{{$data->type}}</td>
                                            <td>{{$data->room_name}}</td>
                                            <td>{{$data->maintanance_period!=null?$data->maintanance_period.' '.$data->maintanance_cycle:''}}</td>
                                            <td>{{$data->last_maintanance}}</td>
                                            <td>{{$data->next_maintanance}} {!! $data->remaining_badge !!}</td>
                                            <td class="text-right">
                                                {{-- @can('Tambah Inventaris') --}}
                                                <a href="#" data-id="{{$data->id}}" class="btn btn-xs btn-outline-primary edit_inv">
                                                    <i data-feather="edit" class="align-self-center icon-xs"></i>
                                                </a>
                                                {{-- @endcan --}}
                                                {{-- @can('Hapus Inventaris') --}}
                                                <a href="{{route('bcl.inventories.delete',$data->id)}}" onclick="deletes(event)" class="btn btn-xs btn-danger">
                                                    <i data-feather="trash" class="align-self-center icon-xs"></i>
                                                </a>
                                                {{-- @endcan --}}
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
<div class="modal fade" id="md_tambah_inv" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambah Inventaris</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.inventories.store')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Nomor (Perkiraan)</label>
                            <input type="text" readonly value="{{$no_inv}}" class="form-control">
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Nama</label>
                            <input type="text" name="nama" required class="form-control">
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Type</label>
                            <select class="mb-3 select2" id="tipe_inv" name="tipe_inv" required style="width: 100%" data-placeholder="Pilih Type">
                                <option value=""></option>
                                <option value="Private/Room">Private/Room</option>
                                <option value="Public">Public/Umum</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-4 col-md-6 col-sm-12 hidden" id="kamar_kontainer">
                            <label class="">Untuk Kamar</label>
                            <select class="mb-3 select2" id="kamar" name="kamar" style="width: 100%" data-placeholder="Pilih...">
                                <option value=""></option>
                                <?php
                                foreach ($rooms as $value) {
                                ?>
                                    <option value="<?= $value->id ?>"><?= $value->room_name ?> (<?= $value->category_name ?>)</option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <div class="custom-control custom-switch mt-4 pt-2">
                                <input type="checkbox" name="perawatan_rutin" class="custom-control-input" id="perawatan_rutin">
                                <label class="custom-control-label" for="perawatan_rutin">Diperlukan Perawatan Rutin</label>
                            </div>
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row hidden" id="cycle_kontainer">
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Setiap</label>
                            <input type="text" name="waktu_perawatan" id="waktu_perawatan" class="form-control inputmask">
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">&nbsp;</label>
                            <select class="mb-3 select2" id="cycle_perawatan" name="cycle_perawatan" style="width: 100%" data-placeholder="Pilih...">
                                <option value=""></option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row">
                        <div class="col-sm-12">
                            <label class="">Keterangan</label>
                            <input type="text" name="keterangan" id="keterangan" class="form-control">
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
<div class="modal fade" id="md_edit_inv" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Edit Inventaris</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.inventories.update')}}" method="POST">
                @csrf
                <input type="hidden" name="id" id="id" value="">
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Nomor</label>
                            <input type="text" id="no_inv" name="inv_number" value="" class="form-control">
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Nama</label>
                            <input type="text" id="nama" name="nama" required class="form-control">
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Type</label>
                            <select class="mb-3 select2" id="tipe_inv_edit" name="tipe_inv" required style="width: 100%" data-placeholder="Pilih Type">
                                <option value=""></option>
                                <option value="Private/Room">Private/Room</option>
                                <option value="Public">Public/Umum</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-4 col-md-6 col-sm-12 hidden" id="kamar_kontainer_edit">
                            <label class="">Untuk Kamar</label>
                            <select class="mb-3 select2" id="kamar_edit" name="kamar" style="width: 100%" data-placeholder="Pilih...">
                                <option value=""></option>
                                <?php
                                foreach ($rooms as $value) {
                                ?>
                                    <option value="<?= $value->id ?>"><?= $value->room_name ?> (<?= $value->category_name ?>)</option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <div class="custom-control custom-switch mt-4 pt-2">
                                <input type="checkbox" name="perawatan_rutin" class="custom-control-input" id="perawatan_rutin_edit">
                                <label class="custom-control-label" for="perawatan_rutin_edit">Diperlukan Perawatan Rutin</label>
                            </div>
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row hidden" id="cycle_kontainer_edit">
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">Setiap</label>
                            <input type="text" name="waktu_perawatan" id="waktu_perawatan_edit" class="form-control inputmask">
                        </div>
                        <div class="col-xl-4 col-md-6 col-sm-12">
                            <label class="">&nbsp;</label>
                            <select class="mb-3 select2" id="cycle_perawatan_edit" name="cycle_perawatan" style="width: 100%" data-placeholder="Pilih...">
                                <option value=""></option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row">
                        <div class="col-sm-12">
                            <label class="">Keterangan</label>
                            <input type="text" name="keterangan" id="keterangan_edit" class="form-control">
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
<div class="modal fade" id="md_history" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Riwayat Perbaikan</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="text-info mt-0">Riwayat Perbaikan</h3>
                    </div>
                    <div class="col-sm-6 text-right">
                        <h3 class="text-info mt-0"><span class="text-success"></span></h3>
                    </div>
                </div>
                <hr class=" mt-0">
                <div class="row">
                    <dt class="col-sm-2">No Inventory</dt>
                    <dd class="col-sm-10" id="view_inv"></dd>
                    <dt class="col-sm-2">Nama</dt>
                    <dd class="col-sm-10" id="view_nama"></dd>
                    <dt class="col-sm-2">Tipe</dt>
                    <dd class="col-sm-10" id="view_tipe"></dd>
                    <dt class="col-sm-2">No. Kamar</dt>
                    <dd class="col-sm-10" id="view_no_kamar"></dd>
                </div>
                <hr class="hr-dashed mt-2">
                <div class="row mb-2">
                    <div class="col-sm-12">
                        <h5>Catat Perawatan / Perbaikan</h5>
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-3">
                                <label>Tanggal</label>
                                <input type="date" id="maint_tanggal" class="form-control" value="{{date('Y-m-d')}}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Biaya (Opsional)</label>
                                <input type="number" id="maint_nominal" class="form-control" placeholder="0">
                            </div>
                            <div class="form-group col-md-5">
                                <label>Deskripsi</label>
                                <input type="text" id="maint_catatan" class="form-control" placeholder="Uraian perbaikan / perawatan">
                            </div>
                            <div class="form-group col-md-1">
                                <button type="button" id="save_maintenance" class="btn btn-primary btn-block">Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="hr-dashed mt-2">
                <div class="row">
                    <table class="table table-sm">
                        <thead class="bg-soft-primary">
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th class="text-right">No Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
                <hr class="hr-dashed mt-0">
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
    $(document).ready(function() {
        var table_bb = $("#tb_kamar").DataTable({
            order: [
                [4, 'asc'],
                [5, 'asc'],
                [8, 'asc']
            ],
            "paging": false,
            "info": false,
            "language": {
                "emptyTable": "Tidak ada data untuk ditampilkan, silakan gunakan filter",
            },
            rowGroup: {
                dataSrc: [
                    function(row) {
                        return '<i class="fas fa-chevron-down"></i> ' + row[4];
                    },
                    function(row) {
                        return '<i class="fas fa-chevron-down"></i> ' + row[5];
                    }
                ],
                endRender: function(rows, group) {
                    var numGroups = Math.ceil(rows.count()); //Math.round(rows.count() / 3) + 1;
                    // return group + ' (' + numGroups + ' groups max of 3)';
                    // var avg =
                    //     rows
                    //     .data()
                    //     .pluck(7)
                    //     .reduce((a, b) => a + b.replace(/[(Rp ,)]|(&nbsp;|<([^>]+)>)/g, '') * 1, 0);

                    return (
                        'Total <span class="highlight text-dark">' + $.number(numGroups, 0) + ' Item</span>'
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
                            return column == 1 ?
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
                        return "Laporan Inventaris " + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        var data = "{{config('app.name')}} \n Laporan Inventaris";
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
                        return "Laporan Inventaris " + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Laporan Inventaris",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova </h3><h4 class="m-0 p-0">Laporan Inventaris</h4></span>',
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
    $('#tipe_inv').on('select2:select', function() {
        var val = $(this).val();
        if (val == 'Public') {
            $('#kamar_kontainer').addClass('hidden');
            $('#kamar').removeAttr('required', 'required');
        } else {
            $('#kamar_kontainer').removeClass('hidden');
            $('#kamar').attr('required', 'required');
        }
    });

    $('#perawatan_rutin').on('change', function() {
        var x = $(this).is(":checked");
        if (x == true) {
            $('#cycle_kontainer').removeClass('hidden');
            $('#waktu_perawatan').data('inputmask-min', '1');
            // init_component();
            $('#waktu_perawatan').attr('required', 'required');
            $('#cycle_perawatan').attr('required', 'required');
        } else {
            $('#cycle_kontainer').addClass('hidden');
            $('#waktu_perawatan').removeAttr('required', 'required');
            $('#cycle_perawatan').removeAttr('required', 'required');
            $('#waktu_perawatan').data('inputmask-min', '0');
        }
    });

    $('#tipe_inv_edit').on('select2:select', function() {
        var val = $(this).val();
        if (val == 'Public') {
            $('#kamar_kontainer_edit').addClass('hidden');
            $('#kamar_edit').removeAttr('required', 'required');
        } else {
            $('#kamar_kontainer_edit').removeClass('hidden');
            $('#kamar_edit').attr('required', 'required');
        }
    });

    $('#perawatan_rutin_edit').on('change', function() {
        var x = $(this).is(":checked");
        if (x == true) {
            $('#cycle_kontainer_edit').removeClass('hidden');
            $('#waktu_perawatan_edit').data('inputmask-min', '1');
            // init_component();
            $('#waktu_perawatan_edit').attr('required', 'required');
            $('#cycle_perawatan_edit').attr('required', 'required');
        } else {
            $('#cycle_kontainer_edit').addClass('hidden');
            $('#waktu_perawatan_edit').removeAttr('required', 'required');
            $('#cycle_perawatan_edit').removeAttr('required', 'required');
            $('#waktu_perawatan_edit').data('inputmask-min', '0');
        }
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
    $('[name="jumlah[]"]').on('change', function() {
        update_amount();
    });

    function createrow() {
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

    function update_amount() {
        var jml_debet = 0;
        $('[name="jumlah[]"]').each(function(index) {
            jml_debet += parseFloat($(this).val());
        });
        $('#amm_debet').text('Rp ' + $.number(jml_debet));
        if (jml_debet != '') {
            $('#bt_simpan').removeAttr('disabled');
        } else {
            $('#bt_simpan').attr('disabled', 'disabled');

        }
    }
    $('.edit_inv').on('click', function() {
        var id = $(this).data('id');
        var address = "{{route('bcl.inventories.edit',':id')}}";
        $.get(address, {
                'id': id
            },
            function(data) {
                console.log(data);
                $('#id').val(data.id);
                $('#no_inv').val(data.inv_number);
                $('#nama').val(data.name);
                $('#tipe_inv_edit').val(data.type).trigger('select2:select').trigger('change');
                $('#keterangan_edit').val(data.notes);
                $('#kamar_edit').val(data.assigned_to).trigger('change');
                if (data.maintanance_period != null) {
                    $('#perawatan_rutin_edit').prop('checked', true);
                    $('#cycle_kontainer_edit').removeClass('hidden');
                    $('#waktu_perawatan_edit').attr('required', 'required');
                    $('#cycle_perawatan_edit').attr('required', 'required');
                    $('#waktu_perawatan_edit').val(data.maintanance_period);
                    $('#cycle_perawatan_edit').val(data.maintanance_cycle).trigger('select2:select').trigger('change');
                } else {
                    $('#perawatan_rutin_edit').prop('checked', false);
                    $('#cycle_kontainer_edit').addClass('hidden');
                    $('#waktu_perawatan_edit').removeAttr('required', 'required');
                    $('#cycle_perawatan_edit').removeAttr('required', 'required');
                }
            }, 'json');
        $('#md_edit_inv').modal('show');
    });
    $('.view_inv').on('click', function() {
        var id = $(this).data('id');
        var address = "{{route('bcl.inventories.show',':id')}}";
        $.get(address, {
            'id': id
        }, function(data) {
            $('#md_history tbody').html('');
            console.log(data);
            $('#view_inv').html(data.inv_number);
            $('#view_nama').html(data.name);
            $('#view_tipe').html(data.type);
            if (data.room_name != null) {
                $('#view_no_kamar').html(data.room_name);
            }
            $.each(data.history, function(index, value) {
                var row = '<tr><td>' + value.tanggal + '</td><td>' + value.catatan + '</td><td class="text-right">' + value.doc_id + '</td></tr>';
                $('#md_history tbody').append(row);
            });
            $('#md_history').modal('show');
        });
    });
    
    // Save maintenance record
    $('#save_maintenance').on('click', function() {
        var inv = $('#view_inv').text().trim();
        if (!inv) {
            $.alert({title: 'Error', content: 'No inventory selected'});
            return;
        }
        var tanggal = $('#maint_tanggal').val();
        var nominal = $('#maint_nominal').val();
        var catatan = $('#maint_catatan').val();
        if (!tanggal || !catatan) {
            $.alert({title: 'Error', content: 'Tanggal dan Deskripsi wajib diisi'});
            return;
        }
        var address = "{{route('bcl.inventories.maintenance.store')}}";
        $.post(address, {
            _token: '{{csrf_token()}}',
            inv_number: inv,
            tanggal: tanggal,
            nominal: nominal,
            catatan: catatan
        }, function(resp) {
            if (resp.success) {
                $.alert({title: 'Berhasil', content: 'Catatan perawatan tersimpan. Halaman akan dimuat ulang.'});
                setTimeout(function() {
                    location.reload();
                }, 800);
            } else {
                $.alert({title: 'Error', content: resp.message || 'Gagal menyimpan'});
            }
        }).fail(function(xhr) {
            $.alert({title: 'Error', content: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menyimpan'});
        });
    });
</script>
@stop
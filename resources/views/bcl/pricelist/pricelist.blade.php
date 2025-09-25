@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<?php
$pricelist = $pricelist;
$categories = $categories;
?>
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Daftar Harga</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    {{-- @can('Tambah Pricelist') --}}
                    <button class="btn btn-sm btn-danger waves-effect waves-light" data-toggle="modal" data-target="#md_tambah_pricelist" id="bt_filter">
                        <i class="mdi mdi-plus"></i> Tambah Harga Kamar
                    </button>
                    <button class="btn btn-sm btn-danger waves-effect waves-light" data-toggle="modal" data-target="#md_tambah_tambahan" id="bt_tambahan">
                        <i class="mdi mdi-plus"></i> Tambah Harga Tambahan
                    </button>
                    {{-- @endcan --}}
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Daftar Harga Kamar</h4>
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
                                            <th class="text-center text-white">Harga</th>
                                            <th class="text-white">Lama Sewa</th>
                                            <th class="text-white">Bonus</th>
                                            <th class="text-white">Kategori</th>
                                            <th class="text-white hidden">Order</th>
                                            <th class="text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        ?>
                                        @foreach($pricelist as $pl)
                                        <tr>
                                            <td class="text-center">{{ $no }}</td>
                                            <td class="text-center">Rp {{ number_format($pl->price,2,',') }}</td>
                                            <td>{{$pl->jangka_waktu.' '.$pl->jangka_sewa}}</td>
                                            <td>{{$pl->bonus_waktu.' '.$pl->bonus_sewa}}</td>
                                            <td>{{$pl->category_name}}</td>
                                            <td class=" hidden">{{$pl->category_id}}</td>
                                            <td class="text-right text-nowrap">
                                                {{-- @can('Edit Pricelist') --}}
                                                <a href="#" data-id="{{$pl->id}}" class="btn btn-xs btn-warning edit_pricelist">
                                                    <i data-feather="edit" class="align-self-center icon-xs"></i>
                                                </a>
                                                {{-- @endcan --}}
                                                {{-- @can('Hapus Pricelist') --}}
                                                <a href="{{route('bcl.pricelist.delete',$pl->id)}}" onclick="delete_harga(event)" class="btn btn-xs btn-danger">
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
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Daftar Harga Tambahan</h4>
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
                                <table class="table table-sm table-hover mb-0 dataTable no-footer" id="tb_tambahan">
                                    <thead class="thead-info bg-info">
                                        <tr class="text-white">
                                            <th class="text-center text-white">No</th>
                                            <th class="text-center text-white">Nama</th>
                                            <th class="text-white">Lama Sewa</th>
                                            <th class="text-center text-white">Harga</th>
                                            <th class="text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        ?>
                                        @foreach($pl_tambahan as $pl)
                                        <tr>
                                            <td class="text-center">{{ $no }}</td>
                                            <td class="">{{$pl->nama}}</td>
                                            <td>{{$pl->qty.' '.$pl->jangka_sewa}}</td>
                                            <td class="text-right">Rp {{ number_format($pl->harga,2,',') }}</td>
                                            <td class="text-right text-nowrap">
                                                {{-- @can('Edit Pricelist') --}}
                                                <a href="#" data-id="{{$pl->id}}" class="btn btn-xs btn-warning edit_tambahan">
                                                    <i data-feather="edit" class="align-self-center icon-xs"></i>
                                                </a>
                                                {{-- @endcan --}}
                                                {{-- @can('Hapus Pricelist') --}}
                                                <a href="{{route('bcl.extra_pl.delete',$pl->id)}}" onclick="delete_harga(event)" class="btn btn-xs btn-danger">
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
<div class="modal fade" id="md_edit_tambahan" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Edit Daftar Harga Tamban</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.extra_pl.update')}}" method="POST">
                @csrf
                <input type="hidden" name="id" id="id_tambahan" value="" />
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Nama</label>
                            <input type="text" id="nama_tbh" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Harga</label>
                            <input type="text" id="harga_tbh" name="harga" required class="form-control inputmask">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Waktu</label>
                            <input type="text" id="jangka_waktu_tbh" name="jangka_waktu" required class="form-control inputmask" data-inputmask-min="1">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Sewa</label>
                            <select class="mb-3 select2" id="jangka_sewa_tbh" name="jangka_sewa" required style="width: 100%" data-placeholder="Pilih...">
                                <option value="Hari">Hari</option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
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
<div class="modal fade" id="md_tambah_tambahan" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambah Daftar Harga Tamban</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.extra_pl.store')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Harga</label>
                            <input type="text" name="harga" required class="form-control inputmask">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Waktu</label>
                            <input type="text" name="jangka_waktu" required class="form-control inputmask" data-inputmask-min="1">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Sewa</label>
                            <select class="mb-3 select2" name="jangka_sewa" required style="width: 100%" data-placeholder="Pilih...">
                                <option value="Hari">Hari</option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
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
<div class="modal fade" id="md_tambah_pricelist" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambah Daftar Harga</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.pricelist.store')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Harga</label>
                            <input type="text" name="harga" class="form-control inputmask">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tipe Kamar</label>
                            <select class="mb-3 select2" name="tipe_kamar" required style="width: 100%" data-placeholder="Pilih Kategori">
                                <option value=""></option>
                                <?php
                                foreach ($categories as $value) {
                                ?>
                                    <option value="<?= $value->id_category ?>"><?= $value->category_name ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Waktu</label>
                            <input type="text" name="jangka_waktu" required class="form-control inputmask" data-inputmask-min="1">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Sewa</label>
                            <select class="mb-3 select2" name="jangka_sewa" required style="width: 100%" data-placeholder="Pilih...">
                                <option value="Hari">Hari</option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Bonus Waktu</label>
                            <input type="text" name="bonus_waktu" value="0" class="form-control inputmask" data-inputmask-min="0">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Bonus Sewa</label>
                            <select class="mb-3 select2" name="bonus_sewa" style="width: 100%" data-placeholder="Pilih...">
                                <option value=""></option>
                                <option value="Hari">Hari</option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
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
<div class="modal fade" id="md_edit" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Edit Harga</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.pricelist.update')}}" method="POST">
                @csrf
                <input type="hidden" name="id_pricelist" id="id_pricelist" value="">
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Harga</label>
                            <input type="text" name="harga" id="harga" value="" class="form-control inputmask">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tipe Kamar</label>
                            <select class="mb-3 select2" id="tipe_kamar" name="tipe_kamar" required style="width: 100%" data-placeholder="Pilih Kategori">
                                <?php
                                foreach ($categories as $value) {
                                ?>
                                    <option value="<?= $value->id_category ?>"><?= $value->category_name ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Waktu</label>
                            <input type="text" name="jangka_waktu" value="" id="jangka_waktu" required class="form-control inputmask" data-inputmask-min="1">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Jangka Sewa</label>
                            <select class="mb-3 select2" id="jangka_sewa" name="jangka_sewa" required style="width: 100%" data-placeholder="Pilih...">
                                <option value="Hari">Hari</option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
                        </div>
                    </div>
                    <hr class="hr-dashed">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">Bonus Waktu</label>
                            <input type="text" name="bonus_waktu" value="" id="bonus_waktu" class="form-control inputmask" data-inputmask-min="0">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Bonus Sewa</label>
                            <select class="mb-3 select2" id="bonus_sewa" name="bonus_sewa" style="width: 100%" data-placeholder="Pilih...">
                                <option value=""></option>
                                <option value="Hari">Hari</option>
                                <option value="Minggu">Minggu</option>
                                <option value="Bulan">Bulan</option>
                                <option value="Tahun">Tahun</option>
                            </select>
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
    $(document).ready(function() {
        var table_bb = $("#tb_kamar").DataTable({
            order: [
                [5, 'asc']
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
                        'Total <span class="highlight text-dark">' + $.number(numGroups, 0) + ' Kamar</span>'
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
                        return "Laporan Pricelist " + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        var data = "{{config('app.name')}} \n Laporan Pricelist";
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
                        return "Laporan Pricelist " + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Laporan Pricelist",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova </h3><h4 class="m-0 p-0">Laporan Pricelist</h4></span>',
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
    $('.edit_tambahan').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{route('bcl.extra_pl.edit', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                console.log(data);
                $('#id_tambahan').val(data.id);
                $('#nama_tbh').val(data.nama);
                $('#harga_tbh').val(data.harga);
                $('#jangka_waktu_tbh').val(data.qty);
                $('#jangka_sewa_tbh').val(data.jangka_sewa).trigger('change');
                $('#md_edit_tambahan').modal('show');
            }
        });
    })
    $('.edit_pricelist').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{route('bcl.pricelist.edit', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                // console.log(data);
                $('#id_pricelist').val(data.id);
                $('#harga').val(data.price);
                $('#tipe_kamar').val(data.room_category).trigger('change');
                $('#jangka_waktu').val(data.jangka_waktu);
                $('#jangka_sewa').val(data.jangka_sewa).trigger('change');
                $('#bonus_waktu').val(data.bonus_waktu);
                $('#bonus_sewa').val(data.bonus_sewa).trigger('change');
                $('#md_edit').modal('show');
            }
        });
    });

    function delete_harga(e) {
        e.preventDefault();
        var url = e.currentTarget.getAttribute('href');
        $.confirm({
            title: 'Hapus Harga ini?',
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
    }
</script>
@stop
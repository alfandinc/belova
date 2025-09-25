@extends('layouts.app')

@section('content')
<!-- Page-Title -->
<?php

use carbon\carbon;

$categories = $categories;
?>
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Daftar Kaegori Kamar</h4>
                    <span>{{config('app.name')}}</span>
                </div>
                <div class="col-auto align-self-center">
                    <button class="btn btn-sm btn-danger waves-effect waves-light" data-toggle="modal" data-target="#md_add" id="bt_filter">
                        <i class="mdi mdi-plus"></i> Tambah Kategori
                    </button>
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
                        <h4 class="card-title text-white">Daftar Kategori</h4>
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
                                            <th class="text-white">Nama Kategori</th>
                                            <th class="text-white">Catatan</th>
                                            <th class="text-white">Jml Foto</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        ?>
                                        @foreach($categories as $category)
                                        <tr class="@if($category->trashed()) text-muted @endif">
                                            <td class="text-center">{{ $no }}</td>
                                            <td class="">{{ $category->category_name }}</td>
                                            <td>{{$category->notes }}</td>
                                            <td>{{count($category->images)}}</td>
                                            <td class="text-right">
                                                @if(!$category->trashed())
                                                <a href="#" data-id="{{$category->id_category}}" class="btn btn-xs btn-outline-primary edit_category">
                                                    <i data-feather="edit" class="align-self-center icon-xs"></i>
                                                </a>
                                                @endif
                                                @if($category->trashed())
                                                <a href="{{route('category.restore',$category->id_category)}}" onclick="restore(event)" data-toggle="tooltip" data-original-title="Kembalikan, Dihapus pada {{$category->deleted_at}}" class="btn btn-xs btn-outline-success">
                                                    <i data-feather="rotate-ccw" class="align-self-center icon-xs"></i>
                                                </a>
                                                <a href="{{route('category.forcedelete',$category->id_category)}}" onclick="forcedeletes(event)" data-toggle="tooltip" data-original-title="Hapus Permanen" class="btn btn-xs btn-outline-dark">
                                                    <i data-feather="trash" class="align-self-center icon-xs"></i>
                                                </a>
                                                @endif
                                                @if(!$category->trashed())
                                                <a href="{{route('category.delete',$category->id_category)}}" onclick="deletes(event)" class="btn btn-xs btn-outline-danger">
                                                    <i data-feather="trash" class="align-self-center icon-xs"></i>
                                                </a>
                                                @endif
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
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-success">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Daftar Foto</h4>
                    </div>
                    <div class="col-auto align-self-center">
                        <button class="btn btn-sm btn-light waves-effect waves-light" data-toggle="modal" data-target="#md_upload">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Foto
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="file-box-content">
                    @foreach($images as $image)
                    @if($image->category == null)
                    <div class="file-box">
                        <a href="{{route('images.delete',$image->id)}}" onclick="deletes(event)" class="download-icon-link">
                            <i class="dripicons-trash file-download-icon"></i>
                        </a>
                        <div class="text-center">
                            <a href="{{asset('assets/images/rooms/'.$image->image)}}" class="image-popup-vertical-fit" title="Fasilitas Umum">
                                <img class="thumb-xl rounded" src="{{asset('assets/images/rooms/'.$image->image)}}" alt="image">
                            </a>
                            <h6 class="text-truncate">Fasilitas Umum</h6>
                            <small class="text-muted">{{$image->tag}}</small>
                        </div>
                    </div>
                    @else
                    <div class="file-box">
                        <a href="{{route('images.delete',$image->id)}}" onclick="deletes(event)" class="download-icon-link">
                            <i class="dripicons-trash file-download-icon"></i>
                        </a>
                        <div class="text-center">
                            <a href="{{asset('assets/images/rooms/'.$image->image)}}" class="image-popup-vertical-fit" title="{{$image->category->category_name}}">
                                <img class="thumb-xl rounded" src="{{asset('assets/images/rooms/'.$image->image)}}" alt="image">
                            </a>
                            <h6 class="text-truncate">{{$image->category->category_name}}</h6>
                            <small class="text-muted">{{$image->tag}}</small>
                        </div>
                    </div>
                    @endif

                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="md_add" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambah Kategori</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('category.store')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <label class="">Nama Kategori</label>
                            <input type="text" name="nama_kategori" required class="form-control">
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" name="notes" class="form-control">
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
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Edit Kategori</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('category.update')}}" method="POST">
                @csrf
                <input type="hidden" name="id" id="id_kategori">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <label class="">Nama Kategori</label>
                            <input type="text" name="nama_kategori" id="nama_kategori" required class="form-control">
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" name="notes" id="notes" class="form-control">
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
<div class="modal fade" id="md_upload" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Upload Foto</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('images.store')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <label class="">Tipe Foto</label>
                            <select name="tag" class="form-control select2" required>
                                <option value=""></option>
                                <option value="room">Foto Kamar</option>
                                <option value="public">Foto Fasilitas Umum</option>
                            </select>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <label class="">Kategori Kamar</label>
                            <select name="room" class="form-control select2">
                                <option value=""></option>
                                @foreach($categories as $category)
                                <option value="{{$category->id_category}}">{{$category->category_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div id="fileUpload"></div>
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
        $("#fileUpload").fileUpload();
        var table_bb = $("#tb_kamar").DataTable({
            order: [
                [0, 'asc']
            ],
            "paging": false,
            "info": false,
            "language": {
                "emptyTable": "Tidak ada data untuk ditampilkan, silakan gunakan filter",
            },
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
                            return column >= 6 && column <= 7 ?
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
                        return "Laporan Kamar " + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        var data = "{{config('app.name')}} \n Laporan Kamar";
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
                        return "Laporan Kamar " + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Laporan Kamar",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova</h3><h4 class="m-0 p-0">Laporan Kamar</h4></span>',
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
    $('.edit_category').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{route('category.edit', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                console.log(data);
                $('#id_kategori').val(data.id_category);
                $('#nama_kategori').val(data.category_name);
                $('#notes').val(data.notes);
                $('#md_edit').modal('show');
            }
        });
    });

    function deletes(e) {
        e.preventDefault();
        var url = e.currentTarget.getAttribute('href');
        $.confirm({
            title: 'Hapus data ini?',
            content: 'Aksi ini akan membuat data dihapus sementara',
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
    function forcedeletes(e) {
        e.preventDefault();
        var url = e.currentTarget.getAttribute('href');
        $.confirm({
            title: 'Hapus Permanen data ini?',
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

    function restore(e) {
        e.preventDefault();
        var url = e.currentTarget.getAttribute('href');
        $.confirm({
            title: 'Pulihkan data ini?',
            content: 'Aksi ini mengembalikan data yang telah dihapus',
            buttons: {
                confirm: {
                    text: 'Ya',
                    btnClass: 'btn-green',
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
    $('#kamar').on('select2:select', function() {
        var id = $(this).find(':selected').data('room_category');
        $.ajax({
            url: "{{route('pricelist.get_pl_room', ':id')}}",
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
</script>
@stop
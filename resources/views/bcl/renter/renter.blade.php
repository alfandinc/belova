@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<?php
$renter = $renter;
$categories = [];
$pricelist = [];
?>
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Daftar Penyewa</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    {{-- @can('Tambah Penyewa') --}}
                    <button class="btn btn-sm btn-danger waves-effect waves-light" data-toggle="modal" data-target="#md_tambah">
                        <i class="mdi mdi-plus"></i> Tambah Penyewa
                    </button>
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
                        <h4 class="card-title text-white">Daftar Penyewa Kamar</h4>
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
                                            <th class="text-center text-white">Foto</th>
                                            <th class="text-white">Nama</th>
                                            <th class="text-white">Alamat</th>
                                            <th class="text-white">Tgl. Lahir</th>
                                            <th class="text-white">Phone</th>
                                            <th class="text-white">Kendaraan</th>
                                            <th class="text-white">Kamar</th>
                                            <th class="text-white">Habis Kontrak</th>
                                            <th class="text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        ?>
                                        @foreach($renter as $renter)
                                        <?php
                                        $foto = null;
                                        foreach ($renter->document as $doc) {
                                            if ($doc->document_type == 'PHOTO') {
                                                $foto = $doc->img;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-center">{{ $no }}</td>
                                            <td class="text-center">
                                                @if($foto)
                                                <a href="{{ asset('storage/renter/' . $foto) }}" class="image-popup-vertical-fit" title="{{$renter->nama}}">
                                                    <img class="thumb-sm rounded" width="50" src="{{ asset('storage/renter/' . $foto) }}">
                                                </a>
                                                @else
                                                <img class="thumb-sm rounded" width="50" src="{{ asset('assets/images/no-image.png') }}">
                                                @endif
                                            </td>
                                            <td class="">{{ $renter->nama }}</td>
                                            <td>{{$renter->alamat}}</td>
                                            <td>{{$renter->birthday}}</td>
                                            <td>{{$renter->phone}}</td>
                                            <td>{{$renter->kendaraan.' - '.$renter->nopol}}</td>
                                            <td>{{$renter->current_room->room_name??''}}</td>
                                            <td>{{$renter->current_room->tgl_selesai??''}}</td>
                                            <td class="text-right text-nowrap">
                                                {{-- @can('Edit Penyewa') --}}
                                                <a href="#" data-id="{{$renter->id}}" class="btn btn-xs btn-warning edit">
                                                    <i data-feather="edit" class="align-self-center icon-xs"></i>
                                                </a>
                                                {{-- @endcan --}}
                                                {{-- @can('Hapus Penyewa') --}}
                                                <a href="{{route('bcl.renter.delete',$renter->id)}}" onclick="deletes(event)" class="btn btn-xs btn-danger">
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
<div class="modal fade bd-example-modal-xl" id="md_tambah" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{route('bcl.renter.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-success">
                    <h6 class="modal-title m-0 text-white" id="myExtraLargeModalLabel">Tambah Penyewa</h6>
                    <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-4 col-sm-12">
                            <div class="form-group">
                                <label for="nama">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" required="">
                            </div>
                        </div>
                        <div class="col-lg-8 col-sm-12">
                            <div class="form-group">
                                <label for="alamat">Alamat Lengkap (Sesuai identitas)</label>
                                <input type="text" class="form-control" name="alamat" required="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="phone">No Hp</label>
                                <input type="text" class="form-control" name="phone" required="" placeholder="...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="phone2">No HP Alternatif</label>
                                <input type="text" class="form-control" name="phone2" required="" placeholder="...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="identitas">Identitas Resmi</label>
                                <select class="form-control select2" name="identitas">
                                    <option value=""></option>
                                    <option value="KTP">KTP</option>
                                    <option value="SIM">SIM</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="nomor_identitas">No Identitas</label>
                                <input type="text" class="form-control" name="nomor_identitas" required="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="kendaraan">Kendaraan</label>
                                <input type="text" class="form-control" name="kendaraan" required="" placeholder="...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="nopol">No Polisi</label>
                                <input type="text" class="form-control" name="nopol" required="" placeholder="...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="birthday">Tgl. Lahir</label>
                                <input type="text" class="form-control datePicker" name="birthday" required="">
                            </div>
                        </div>
                    </div>

                    <hr class="hr-dashed mt-0">
                    <div class="row mb-2">
                        <div class="col-sm-12 col-lg-6">
                            <input type="file" accept="image/png, image/jpeg" class="dropify_foto" name="img_photo" data-allowed-file-extensions="jpg png" data-max-file-size-preview="5M" required />
                        </div>
                        <div class="col-sm-12 col-lg-6">
                            <input type="file" accept="image/png, image/jpeg" class="dropify_ktp" name="img_identitas" data-allowed-file-extensions="jpg png" data-max-file-size-preview="5M" required />
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-lg-6">
                            <input type="file" accept="image/png, image/jpeg" class="dropify_lain" name="input_lain" data-allowed-file-extensions="jpg png" data-max-file-size-preview="5M" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
        <!--end modal-content-->
    </div>
    <!--end modal-dialog-->
</div>
<div class="modal fade bd-example-modal-xl" id="md_edit" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{route('bcl.renter.update')}}" enctype="multipart/form-data">
                <input type="hidden" name="id" id="id">
                @csrf
                <div class="modal-header bg-success">
                    <h6 class="modal-title m-0 text-white" id="myExtraLargeModalLabel">Edit Penyewa</h6>
                    <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="la la-times text-white"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-4 col-sm-12">
                            <div class="form-group">
                                <label for="nama">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" required="">
                            </div>
                        </div>
                        <div class="col-lg-8 col-sm-12">
                            <div class="form-group">
                                <label for="alamat">Alamat Lengkap (Sesuai identitas)</label>
                                <input type="text" class="form-control" id="alamat" name="alamat" required="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="phone">No Hp</label>
                                <input type="text" class="form-control" id="phone" name="phone" required="" placeholder="62..., 0273...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="phone2">No HP Alternatif</label>
                                <input type="text" class="form-control" id="phone2" name="phone2" required="" placeholder="62...,0273...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="identitas">Identitas Resmi</label>
                                <select class="form-control select2" id="identitas" name="identitas">
                                    <option value=""></option>
                                    <option value="KTP">KTP</option>
                                    <option value="SIM">SIM</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="nomor_identitas">No Identitas</label>
                                <input type="text" class="form-control" id="nomor_identitas" name="nomor_identitas" required="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="kendaraan">Kendaraan</label>
                                <input type="text" class="form-control" id="kendaraan" name="kendaraan" required="" placeholder="Ford Raptor">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="nopol">No Polisi</label>
                                <input type="text" class="form-control" id="nopol" name="nopol" required="" placeholder="AD 8310 VA">
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12">
                            <div class="form-group">
                                <label for="birthday">Tgl. Lahir</label>
                                <input type="text" class="form-control datePicker" id="birthday" name="birthday" required="">
                            </div>
                        </div>
                    </div>

                    <hr class="hr-dashed mt-0">
                    <div class="row mb-2">
                        <div class="col-sm-12 col-lg-6">
                            <input type="file" accept="image/png, image/jpeg" id="input_foto" class="dropify_foto" name="img_photo" data-allowed-file-extensions="jpg png" data-max-file-size-preview="5M" />
                        </div>
                        <div class="col-sm-12 col-lg-6">
                            <input type="file" accept="image/png, image/jpeg" id="input_ktp" class="dropify_ktp" name="img_identitas" data-allowed-file-extensions="jpg png" data-max-file-size-preview="5M" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-lg-6">
                            <input type="file" accept="image/png, image/jpeg" id="input_lain" class="dropify_lain" name="input_lain" data-allowed-file-extensions="jpg png" data-max-file-size-preview="5M" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
        <!--end modal-content-->
    </div>
    <!--end modal-dialog-->
</div>
@endsection
@section('pagescript')
<script>
    $(document).ready(function() {
        var table_bb = $('#tb_kamar').DataTable({});
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
                        return "Laporan Penyewa " + moment().format('YYYY-MM-DD');
                    },
                    title: function() {
                        var data = "{{config('app.name')}} \n Laporan Penyewa";
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
                        return "Laporan Penyewa " + moment().format('YYYY-MM-DD');
                    },
                    title: "{{config('app.name')}} \n Laporan Penyewa",
                    messageTop: '#Tgl Cetak: ' + moment().format('YYYY-MM-DD, HH:mm') + ' [{{Auth::user()->name}}]',
                    pageSize: 'A4',
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'print',
                    title: '<span class="text-center"><h3 class="m-0 p-0">Belova </h3><h4 class="m-0 p-0">Laporan Penyewa</h4></span>',
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
    $('.edit').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{route('bcl.renter.edit', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                $(".dropify-clear").trigger("click");
                console.log(data);
                var bio = data[0];
                var doc = data[1];
                $('#id').val(bio.id);
                $('#nama').val(bio.nama);
                $('#alamat').val(bio.alamat);
                $('#phone').val(bio.phone);
                $('#phone2').val(bio.phone2);
                $('#birthday').val(bio.birthday);
                $('#identitas').val(bio.identitas).trigger('change');
                $('#nomor_identitas').val(bio.no_identitas);
                $('#kendaraan').val(bio.kendaraan);
                $('#nopol').val(bio.nopol);
                $.each(doc, function(i, val) {
                    if (val.document_type == 'PHOTO') {
                        $('#input_foto').attr('data-default-file', "{{ URL::asset('assets/images/renter/')}}/" + val.img);
                        var imagenUrl = "{{ URL::asset('assets/images/renter/')}}/" + val.img;
                        var drEvent = $('#input_foto').dropify({
                            defaultFile: imagenUrl
                        });
                        drEvent = drEvent.data('dropify');
                        drEvent.resetPreview();
                        drEvent.clearElement();
                        drEvent.settings.defaultFile = imagenUrl;
                        drEvent.destroy();
                        drEvent.init();
                    } else if (val.document_type == 'IDENTITAS') {
                        $('#input_ktp').attr('data-default-file', "{{ URL::asset('assets/images/renter/')}}/" + val.img);
                        var imagenUrl = "{{ URL::asset('assets/images/renter/')}}/" + val.img;
                        var drEvent = $('#input_ktp').dropify({
                            defaultFile: imagenUrl
                        });
                        drEvent = drEvent.data('dropify');
                        drEvent.resetPreview();
                        drEvent.clearElement();
                        drEvent.settings.defaultFile = imagenUrl;
                        drEvent.destroy();
                        drEvent.init();
                    } else if (val.document_type == 'LAINNYA') {
                        $('#input_lain').attr('data-default-file', "{{ URL::asset('assets/images/renter/')}}/" + val.img);
                        var imagenUrl = "{{ URL::asset('assets/images/renter/')}}/" + val.img;
                        var drEvent = $('#input_lain').dropify({
                            defaultFile: imagenUrl
                        });
                        drEvent = drEvent.data('dropify');
                        drEvent.resetPreview();
                        drEvent.clearElement();
                        drEvent.settings.defaultFile = imagenUrl;
                        drEvent.destroy();
                        drEvent.init();
                    }
                });

                // $('#id_pricelist').val(data.id);
                // $('#harga').val(data.price);
                // $('#tipe_kamar').val(data.room_category).trigger('change');
                // $('#jangka_waktu').val(data.jangka_waktu);
                // $('#jangka_sewa').val(data.jangka_sewa).trigger('change');
                // $('#bonus_waktu').val(data.bonus_waktu);
                // $('#bonus_sewa').val(data.bonus_sewa).trigger('change');
                $('#md_edit').modal('show');
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
    }
    // initialize datepicker and dropify for add modal and other inputs
    $(function() {
        try {
            // single-date picker for birthday fields
            if ($.fn.daterangepicker) {
                $('.datePicker').each(function() {
                    // avoid re-initializing if already initialized
                    if (!$(this).data('daterangepicker')) {
                        $(this).daterangepicker({
                            singleDatePicker: true,
                            showDropdowns: true,
                            locale: { format: 'YYYY-MM-DD' }
                        });
                    }
                });
            }

            // init dropify for file inputs in add modal (and others)
            if ($.fn.dropify) {
                $('.dropify_foto').each(function() {
                    if (!$(this).data('dropify')) $(this).dropify();
                });
                $('.dropify_ktp').each(function() {
                    if (!$(this).data('dropify')) $(this).dropify();
                });
                $('.dropify_lain').each(function() {
                    if (!$(this).data('dropify')) $(this).dropify();
                });
            }
        } catch (e) {
            console.error('Init renter widgets error', e);
        }
    });
</script>
@stop
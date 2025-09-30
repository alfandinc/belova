@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<?php

use Carbon\Carbon;

$categories = $category;
$rooms = $data;
?>
<style>
    .minimap {
            width: 100%; /* Adjust width as needed */
            height: 300px; /* Adjust height as needed */
            border: 1px solid #ccc;
            position: relative;
            background: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            /* margin: 0 auto;  */
        }

        .room {
            width: 30px; /* Adjust width of individual rooms */
            height: 30px; /* Adjust height of individual rooms */
            background-color: #007bff; /* Room color */
            border: 2px solid #fff;
            position: absolute;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0;
        }

        .room:hover {
            transform: scale(1.1);
        }

        .label {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            color: #333;
        }
</style>
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Daftar Kamar</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    <button class="btn btn-sm btn-dark waves-effect waves-light" data-toggle="modal" data-target="#md_deleted">
                        <i class="mdi mdi-trash-can"></i> Kamar dihapus
                    </button>
                    <button class="btn btn-sm btn-danger waves-effect waves-light" data-toggle="modal" data-target="#md_filter" id="bt_filter">
                        <i class="mdi mdi-plus"></i> Tambah Kamar
                    </button>
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->
{{-- <div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Drawing Kamar</h4>
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
                <div class="minimap">
                    <!-- Example rooms -->
                    <div class="room" style="top: 20px; left: 5px;"></div> <!-- Room 1 -->
                    <div class="room" style="top: 20px; left: 35px;"></div> <!-- Room 2 -->
                    <div class="room" style="top: 20px; left: 65px;"></div> <!-- Room 3 -->
                    <div class="room" style="top: 60px; left: 5px;"></div> <!-- Room 4 -->
                    <div class="room" style="top: 60px; left: 35px;"></div> <!-- Room 5 -->
                    <div class="room" style="top: 60px; left: 65px;"></div> <!-- Room 6 -->
                    <div class="room" style="top: 100px; left: 5px;"></div> <!-- Room 7 -->
                    <div class="room" style="top: 100px; left: 35px;"></div> <!-- Room 8 -->
                    <div class="room" style="top: 100px; left: 65px;"></div> <!-- Room 9 -->
                </div>
                <div class="tab-content" id="files-tabContent">
                    <div class="tab-pane fade show active" id="files-projects">
                        <h4 class="card-title mt-0 mb-3">Lantai 1</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information  file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C3</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C2</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C1</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                        </div>
                        <br class="mb-5">
                        <h4 class="card-title mt-0 mb-3">Lantai 2</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information  file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C3</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C2</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C1</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                        </div>
                        <br class="mb-5">
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information  file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C3</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C2</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="fas fa-home text-warning"></i>
                                    <h6 class="text-truncate">C1</h6>
                                    <small class="text-muted">Kosong</small>
                                </div>
                            </div>
                        </div>
                    </div><!--end tab-pane-->

                    <div class="tab-pane fade" id="files-pdf">
                        <h4 class="mt-0 card-title mb-3">PDF Files</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-info"></i>
                                    <h6 class="text-truncate">Admin_Panel</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-danger"></i>
                                    <h6 class="text-truncate">Ecommerce.pdf</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-warning"></i>
                                    <h6 class="text-truncate">Payment_app.zip</h6>
                                    <small class="text-muted">11 April 2019 / 10MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-secondary"></i>
                                    <h6 class="text-truncate">App_landing_001.pdf</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                        </div>
                    </div><!--end tab-pane-->

                    <div class="tab-pane fade" id="files-documents">
                        <h4 class="mt-0 card-title mb-3">Documents</h4>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-info"></i>
                                    <h6 class="text-truncate">Adharcard_update</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-danger"></i>
                                    <h6 class="text-truncate">Pancard</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-warning"></i>
                                    <h6 class="text-truncate">ICICI_statment</h6>
                                    <small class="text-muted">11 April 2019 / 10MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-information file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-secondary"></i>
                                    <h6 class="text-truncate">March_Invoice</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h4 class="card-title my-3">Company Documents</h4>
                            </div>
                        </div>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-success"></i>
                                    <h6 class="text-truncate">Adharcard_update</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-pink"></i>
                                    <h6 class="text-truncate">Pancard</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-purple"></i>
                                    <h6 class="text-truncate">ICICI_statment</h6>
                                    <small class="text-muted">11 April 2019 / 10MB</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h4 class="card-title my-3">Personal Documents</h4>
                            </div>
                        </div>
                        <div class="file-box-content">
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-blue"></i>
                                    <h6 class="text-truncate">Adharcard_update</h6>
                                    <small class="text-muted">06 March 2019 / 5MB</small>
                                </div>
                            </div>
                            <div class="file-box">
                                <a href="#" class="download-icon-link">
                                    <i class="dripicons-download file-download-icon"></i>
                                </a>
                                <div class="text-center">
                                    <i class="lar la-file-pdf text-dark"></i>
                                    <h6 class="text-truncate">Pancard</h6>
                                    <small class="text-muted">15 March 2019 / 8MB</small>
                                </div>
                            </div>
                        </div>
                    </div><!--end tab-pen-->

                    <div class="tab-pane fade" id="files-hide">
                        <h4 class="mt-0 card-title mb-3">Hide</h4>
                    </div><!--end tab-pane-->
                </div>
            </div>
        </div>
    </div>
</div> --}}
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-dark">
                <div class="row align-self-center">
                    <div class="col align-self-center">
                        <h4 class="card-title text-white">Daftar Kamar</h4>
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
                                            <th class="text-center text-white">Nomor Kamar</th>
                                            <th class="text-white">Tipe Kamar</th>
                                            <th class="text-white">Penyewa</th>
                                            <th class="text-white">Periode</th>
                                            <th class="text-white">Durasi</th>
                                            <th class="text-white">Catatan</th>
                                            <th class="text-white hidden">Status</th>
                                            <th class="text-white hidden">Order</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        ?>
                                        @foreach($rooms as $room)
                                        <?php
                                        if (isset($room->jangka_sewa)) {
                                            $periode = $room->tgl_mulai . ' s/d ' . $room->tgl_selesai;
                                            $status = 'Terisi';
                                            $order = 2;
                                            $class = 'success';
                                            $selesai = Carbon::parse($room->tgl_selesai);
                                            $now = Carbon::now();
                                            $diff = $selesai->diffInDays($now);
                                            if ($diff <= 7) {
                                                $message = '<i class="fas fa-exclamation-triangle faa faa-flash animated text-danger"></i>';
                                            } else {
                                                $message = '';
                                            }
                                        } else {
                                            $periode = '';
                                            $status = 'Kosong';
                                            $class = 'warning';
                                            $message = '';
                                            $order = 1;
                                        }
                                        if ($room->kurang > 0 and $room->kurang != null) {
                                            $kurang = ' <span class="text-danger">(Belum Lunas)</span>';
                                        } else {
                                            $kurang = "";
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-center">{{ $no }}</td>
                                            <td class="text-center"><span class="badge badge-{{$class}}">{{ $room->room_name }}</span></td>
                                            <td>{{$room->category_name}}</td>
                                            <td>{{$room->nama}} {!!$kurang!!}</td>
                                            <td>{{$periode}} {!! $message !!}</td>
                                            <td>{{$room->lama_sewa.' '.$room->jangka_sewa}} </td>
                                            <td>{{$room->notes }}</td>
                                            <td class="hidden">{{$status}}</td>
                                            <td class="hidden">{{$order}}</td>
                                            <td class="text-right">
                                                {{-- @can('Edit Kamar') --}}
                                                <a href="#" data-id="{{$room->id}}" class="btn btn-xs btn-outline-primary edit_kamar">
                                                    <i data-feather="edit" class="align-self-center icon-xs"></i>
                                                </a>
                                                {{-- @endcan --}}
                                                {{-- @can('Hapus Kamar') --}}
                                                <a href="{{route('bcl.rooms.delete',$room->id)}}" onclick="deletes(event)" class="btn btn-xs btn-outline-danger">
                                                    <i data-feather="trash-2" class="align-self-center icon-xs"></i>
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
<div class="modal fade" id="md_filter" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Tambah Kamar</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.rooms.store')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <label class="">No/Nama Kamar</label>
                            <input type="text" name="no_kamar" required class="form-control">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tipe Kamar</label>
                            <select class="mb-3 form-control select2 " name="kategori" required style="width: 100%" data-placeholder="Pilih Kategori">
                                <option value=""></option>
                                <?php foreach ($categories as $category) { ?>
                                    <option value="{{$category->id_category}}">{{$category->category_name}}</option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" name="catatan" class="form-control">
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
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Edit Kamar</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <form action="{{route('bcl.rooms.update')}}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="id" id="id_kamar">
                        <div class="col-md-6 col-sm-12">
                            <label class="">No/Nama Kamar</label>
                            <input type="text" name="no_kamar" id="no_kamar" required class="form-control">
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <label class="">Tipe Kamar</label>
                            <select class="mb-3 form-control select2 " id="kategori" name="kategori" required style="width: 100%" data-placeholder="Pilih Kategori">
                                <option value=""></option>
                                <?php foreach ($categories as $category) { ?>
                                    <option value="{{$category->id_category}}">{{$category->category_name}}</option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <label class="">Catatan</label>
                            <input type="text" name="catatan" id="notes" class="form-control">
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
<div class="modal fade" id="md_deleted" tabindex="-1" role="dialog" aria-labelledby="exampleModalDefaultLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h6 class="modal-title m-0 text-white" id="exampleModalDefaultLabel">Kamar Dihapus</h6>
                <button type="button" class="close " data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="la la-times"></i></span>
                </button>
            </div>
            <div class="model-body">
                <div class="row">
                    <div class="col-sm-12">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kamar</th>
                                    <th>Catatan</th>
                                    <th>Dihapus pada</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deleted as $del)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$del->room_name}}</td>
                                    <td>{{$del->notes}}</td>
                                    <td>{{$del->deleted_at}}</td>
                                    <td class="text-right">
                                        <a href="{{route('bcl.rooms.restore',$del->id)}}" class="btn btn-xs btn-success">Aktifkan</a>
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
</div>
@endsection
@section('pagescript')
<script>
    $(document).ready(function() {
        var table_bb = $("#tb_kamar").DataTable({
            order: [
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
                        return '<i class="fas fa-chevron-down"></i> ' + row[7];
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
    $('.edit_kamar').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{route('bcl.rooms.edit', ':id')}}",
            type: "GET",
            data: {
                id: id
            },
            success: function(data) {
                console.log(data);
                $('#id_kamar').val(data.id);
                $('#no_kamar').val(data.room_name);
                $('#notes').val(data.notes);
                $('#kategori').val(data.room_category).trigger('change');
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
    };
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
</script>
@stop
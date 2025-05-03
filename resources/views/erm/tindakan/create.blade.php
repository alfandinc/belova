@extends('layouts.erm.app')

@section('title', 'ERM | Tindakan & Inform Consent')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Tindakan & Inform Consent</h3>
    </div>
       <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">Tindakan & Inform Consent</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')
        <div class="card">
        <div class="card-body">
            <div class="form-group">
                <input type="text" class="form-control" placeholder="Search for names.." id="searchInformConsent">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Nama Inform Consent</th>
                            <th>SMF</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="informConsentTable">
                        <!-- Contoh Data Static (boleh kosong kalau mau) -->
                        <tr>
                            <td class="text-center">1</td>
                            <td>Blok Perifer</td>
                            <td>Anestesi</td>
                            <td class="text-center">
                                <button class="btn btn-success btn-sm">
                                    <i class="fa fa-pencil"></i> Buat
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">2</td>
                            <td>General Anestesia</td>
                            <td>Anestesi</td>
                            <td class="text-center">
                                <button class="btn btn-success btn-sm">
                                    <i class="fa fa-pencil"></i> Buat
                                </button>
                            </td>
                        </tr>
                        <!-- Tambah baris lain kalau mau -->
                    </tbody>
                </table>
            </div>
        </div>
    </div> 
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-file-medical-alt mr-2"></i> Riwayat Tindakan & Dokumen Inform Consent</h5>
        </div>
        <div class="card-body">
            <!-- Riwayat Hasil Tindakan -->
            <div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>No</th>
                                <th>Tanggal Dibuat</th>
                                <th>Tanggal OP</th>
                                <th>Nama Tindakan</th>
                                <th>Dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Kosong -->
                            <tr class="text-center">
                                <td colspan="5">Belum ada data tindakan.</td>
                            </tr>
                        </tbody>
                    </table>                  
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

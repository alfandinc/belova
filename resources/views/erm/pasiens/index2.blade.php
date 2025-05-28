@extends('layouts.erm.app')
@section('title', 'ERM | Daftar Pasien')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<div class="container-fluid">
                    <!-- Page-Title -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                <div class="row">
                                    <div class="col">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                                            <li class="breadcrumb-item active">Pasien</li>
                                        </ol>
                                    </div><!--end col-->
                                    <div class="col-auto align-self-center">
                                        <a href="{{ route('erm.pasiens.create') }}" class="btn btn-primary btn-lg">
                                            <i class="fa fa-user mr-2"></i> Add Pasien Baru
                                        </a>
                                    </div><!--end col-->  
                                </div><!--end row-->                                                              
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div><!--end row-->
                    <!-- end page title end breadcrumb -->

                    {{-- Table Pasien  --}}
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h4 class="card-title text-white">Daftar Pasien</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered" id="pasiens-table">
                                <thead>
                                    <tr>
                                        <th>No RM</th>
                                        <th>Name</th>
                                        <th>NIK</th>
                                        <th>Alamat</th>
                                        <th>No HP</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
</div><!-- container -->
@endsection



@section('scripts')
<script>
$(document).ready(function() {
    $('#pasiens-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: "{{ route('erm.pasiens.index') }}",
    columns: [
        { data: 'id', name: 'id' },
        { data: 'nama', name: 'nama' },
        { data: 'nik', name: 'nik' },
        { data: 'alamat', name: 'alamat' },
        { data: 'no_hp', name: 'no_hp' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ]
});
});
</script>
@endsection


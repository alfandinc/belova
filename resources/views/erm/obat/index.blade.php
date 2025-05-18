@extends('layouts.erm.app')
@section('title', 'ERM | Daftar Obat')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')


<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Daftar Obat Farmasi</h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Stok Obat</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->
     <a href="{{ route('erm.obat.create') }}" class="btn btn-primary mb-3">+ Tambah Obat</a>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama Obat</th>
                <th>Stok</th>
                <th>Harga Umum</th>
                <th>Harga Inhealth</th>
                <th>Zat Aktif</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($obats as $obat)
                <tr>
                    <td>{{ $obat->nama }} {{ $obat->dosis }} {{ $obat->satuan }}</td>
                    <td>{{ $obat->stok }}</td>
                    <td>{{ $obat->harga_umum }}</td>
                    <td>{{ $obat->harga_inhealth }}</td>
                    <td>
                        @foreach ($obat->zatAktifs as $zat)
                            <span class="badge bg-secondary">{{ $zat->nama }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


        </div>
    </div>
</div>
@endsection

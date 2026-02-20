@extends('layouts.hrd.app')
@section('title', 'Master Payroll')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Insentif Omset</h4>
                    <button class="btn btn-primary" id="btnAddInsentifOmset">Tambah Insentif Omset</button>
                </div>
                <div class="card-body">
                    <table id="insentifOmsetTable" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Nama Penghasil</th>
                                <th>Omset Min</th>
                                <th>Omset Max</th>
                                <th>Insentif Normal</th>
                                <th>Insentif Up</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@include('hrd.payroll.insentif_omset._modal')
@endsection
@section('scripts')
@include('hrd.payroll.insentif_omset._scripts')
@endsection

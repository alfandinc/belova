@extends('layouts.hrd.app')
@section('title', 'Slip Payroll')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="card-title">Slip Gaji</h4>
            <div class="d-flex align-items-center">
                <input type="month" id="filterBulan" class="form-control mr-2" style="width:180px;" value="{{ date('Y-m') }}">
                <button class="btn btn-success" id="btnBuatSlipGaji">Buat Slip Gaji</button>
            </div>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-12">
            <div id="omsetInfoBox" class="alert alert-info">Bulan: <span id="omsetInfoBulan">{{ date('m-Y') }}</span> &nbsp; Total Omset Bulan Ini: <span id="omsetInfoNominal">0</span></div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="slipGajiTable" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th style="display:none;">ID</th>
                                <th>No Induk</th>
                                <th>Nama</th>
                                <th>Divisi</th>
                                <th>Jumlah Hari Masuk</th>
                                <th>KPI Poin</th>
                                <th>Jumlah Pendapatan</th>
                                <th>Jumlah Potongan</th>
                                <th>Total Gaji</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@include('hrd.payroll.slip_gaji._modal_detail')
@include('hrd.payroll.slip_gaji.buat._modal')
@endsection
@section('scripts')
@include('hrd.payroll.slip_gaji._scripts')
@include('hrd.payroll.slip_gaji.buat._scripts')
@endsection

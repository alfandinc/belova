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
                    <h4 class="card-title">KPI</h4>
                    <button class="btn btn-primary" id="btnAdd">Tambah KPI</button>
                </div>
                <div class="card-body">
                    <table id="kpiTable" class="table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Nama Poin</th>
                                <th>Initial Poin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@include('hrd.payroll.kpi._modal')
@endsection
@section('scripts')
@include('hrd.payroll.kpi._scripts')
@endsection

@extends('layouts.hrd.app')

@section('title', 'Master Payroll')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">Master Payroll</h4>
            <ul class="nav nav-tabs" id="payrollTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="gajipokok-tab" data-toggle="tab" href="#gajipokok" role="tab">Gaji Pokok</a></li>
                <li class="nav-item"><a class="nav-link" id="tunjanganjabatan-tab" data-toggle="tab" href="#tunjanganjabatan" role="tab">Tunjangan Jabatan</a></li>
                <li class="nav-item"><a class="nav-link" id="tunjanganlain-tab" data-toggle="tab" href="#tunjanganlain" role="tab">Tunjangan Lain</a></li>
                <li class="nav-item"><a class="nav-link" id="benefit-tab" data-toggle="tab" href="#benefit" role="tab">Benefit</a></li>
                <li class="nav-item"><a class="nav-link" id="potongan-tab" data-toggle="tab" href="#potongan" role="tab">Potongan</a></li>
                <li class="nav-item"><a class="nav-link" id="kpi-tab" data-toggle="tab" href="#kpi" role="tab">KPI</a></li>
                <li class="nav-item"><a class="nav-link" id="insentif-omset-tab" data-toggle="tab" href="#insentif-omset" role="tab">Insentif Omset</a></li>
            </ul>
            <div class="tab-content mt-3" id="payrollTabContent">
                <div class="tab-pane fade show active" id="gajipokok" role="tabpanel">
                    @include('hrd.payroll.master._table_gajipokok')
                </div>
                <div class="tab-pane fade" id="tunjanganjabatan" role="tabpanel">
                    @include('hrd.payroll.master._table_tunjangan_jabatan')
                </div>
                <div class="tab-pane fade" id="tunjanganlain" role="tabpanel">
                    @include('hrd.payroll.master._table_tunjangan_lain')
                </div>
                <div class="tab-pane fade" id="benefit" role="tabpanel">
                    @include('hrd.payroll.master._table_benefit')
                </div>
                <div class="tab-pane fade" id="potongan" role="tabpanel">
                    @include('hrd.payroll.master._table_potongan')
                </div>
                <div class="tab-pane fade" id="kpi" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">KPI</h4>
                            <button class="btn btn-primary" id="btnAddKpi">Tambah KPI</button>
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
                    @include('hrd.payroll.kpi._modal')
                </div>
                <div class="tab-pane fade" id="insentif-omset" role="tabpanel">
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
                    @include('hrd.payroll.insentif_omset._modal')
                </div>
            </div>
        </div>
    </div>
</div>

@include('hrd.payroll.master._modal')
@endsection

@push('scripts')
<script>
    @include('hrd.payroll.master._scripts')
</script>
@include('hrd.payroll.kpi._scripts')
@include('hrd.payroll.insentif_omset._scripts')
@endpush

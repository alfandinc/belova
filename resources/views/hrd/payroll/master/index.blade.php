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
@endpush

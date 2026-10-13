@extends('layouts.erm.app')

@section('title', 'ERM | MCU')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">MCU</h3>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">MCU</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('erm.partials.card-identitaspasien')

    {{-- Halaman MCU belum dibuat --}}
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-heartbeat fa-2x mb-3"></i>
            <p class="mb-0">Halaman MCU belum tersedia.</p>
        </div>
    </div>
</div>
@endsection

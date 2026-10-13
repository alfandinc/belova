{{--
    Shown instead of a Slimming / Vaksin / MCU page while earlier steps are missing
    (Visitation::pendingServiceSteps()). Params: $serviceLabel, $blockers.
--}}
@extends('layouts.erm.app')

@section('title', 'ERM | ' . $serviceLabel)
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">{{ $serviceLabel }}</h3>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">{{ $serviceLabel }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('erm.partials.card-identitaspasien')

    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-lock fa-2x text-warning mb-3"></i>
            <h5>Halaman {{ $serviceLabel }} belum bisa dibuka</h5>
            <p class="text-muted mb-2">Lengkapi dulu di Rawat Jalan:</p>
            <ul class="list-unstyled mb-4">
                @foreach($blockers as $blocker)
                    <li class="font-weight-bold text-danger"><i class="fas fa-times-circle mr-1"></i>{{ ucfirst($blocker) }}</li>
                @endforeach
            </ul>
            <a href="{{ route('erm.rawatjalans.index') }}" class="btn btn-primary"><i class="fas fa-arrow-left mr-1"></i>Kembali ke Rawat Jalan</a>
        </div>
    </div>
</div>
@endsection

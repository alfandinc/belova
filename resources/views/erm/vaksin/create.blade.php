@extends('layouts.erm.app')

@section('title', 'ERM | Vaksin')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-alergipasien')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Vaksin</h3>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">Vaksin</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('erm.partials.card-identitaspasien')

    {{-- Screening vaksin (diisi perawat): a "Ya" answer is a contraindication for the dokter to check --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="fas fa-clipboard-check mr-1"></i>Screening Vaksin</h5>
            @if($screening)
                <small class="text-muted">
                    Diisi {{ $screeningCreator ? 'oleh ' . $screeningCreator : '' }}
                    {{ optional($screening->created_at)->translatedFormat('j F Y H:i') }}
                </small>
            @endif
        </div>
        <div class="card-body">
            @if(!$screening)
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Screening vaksin belum diisi perawat. Pastikan screening diisi sebelum pemberian vaksin.
                </div>
            @else
                @php
                    $positives = collect(\App\Models\ERM\ScreeningVaksin::QUESTIONS)->keys()->filter(fn ($field) => $screening->{$field} === 'ya');
                @endphp
                @if($positives->isNotEmpty())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-1"></i><strong>{{ $positives->count() }} jawaban "Ya"</strong> — periksa kemungkinan kontraindikasi sebelum vaksin.
                    </div>
                @else
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-1"></i>Semua jawaban "Tidak" — tidak ada kontraindikasi dari screening.
                    </div>
                @endif

                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:48px;" class="text-center">No</th>
                            <th>Pertanyaan</th>
                            <th style="width:90px;" class="text-center">Jawaban</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\ERM\ScreeningVaksin::QUESTIONS as $field => $question)
                            @php $isYa = $screening->{$field} === 'ya'; @endphp
                            <tr class="{{ $isYa ? 'table-danger' : '' }}">
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $question }}</td>
                                <td class="text-center font-weight-bold {{ $isYa ? 'text-danger' : '' }}">{{ $isYa ? 'Ya' : 'Tidak' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <dl class="row mb-0">
                    <dt class="col-sm-3">Rencana Vaksin</dt>
                    <dd class="col-sm-9">{{ $screening->rencana_vaksin ?: '-' }}</dd>
                    <dt class="col-sm-3">Catatan</dt>
                    <dd class="col-sm-9 mb-0" style="white-space:pre-line;">{{ $screening->catatan ?: '-' }}</dd>
                </dl>
            @endif
        </div>
    </div>
</div>
@endsection

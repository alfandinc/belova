@extends('layouts.erm.app')

@section('title', 'ERM | Riwayat Kunjungan')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-suratdiagnosa')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0"><strong>Riwayat Kunjungan</strong></h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">Riwayat Kunjungan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    @include('erm.partials.form-theme')
    <link rel="stylesheet" href="{{ asset('css/erm-penunjang.css') }}?v={{ filemtime(public_path('css/erm-penunjang.css')) }}">
    <style>
        /* Stat tiles */
        .rk-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; margin-bottom: 14px; }
        .rk-stat { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--sb-line, #e2e8f0); background: var(--sb-bg, #fff); }
        .rk-stat i { flex: 0 0 38px; width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; background: rgba(var(--ef-accent-rgb), .12); color: var(--ef-accent-text); }
        .rk-stat-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--sb-muted, #8a94ad); }
        .rk-stat-value { font-size: 1.15rem; font-weight: 800; color: var(--sb-text-strong, #1e2538); line-height: 1.25; }
        .rk-stat-note { font-size: .75rem; color: var(--sb-muted, #8a94ad); }
        .rk-stat-note.is-due { color: #dc2626; font-weight: 700; }
        /* Filters */
        .rk-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 12px; }
        .rk-chip { padding: 5px 13px; border-radius: 999px; border: 1px solid var(--sb-line, #e2e8f0); background: var(--sb-bg, #fff); color: var(--sb-text, #4b5671); font-size: .82rem; font-weight: 600; cursor: pointer; }
        .rk-chip:hover { border-color: rgba(var(--ef-accent-rgb), .45); }
        .rk-chip.active { background: var(--ef-accent); border-color: var(--ef-accent); color: #fff; }
        .rk-filters .form-control { width: auto; min-width: 170px; margin-left: auto; }
        /* Table badges + actions */
        .rk-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: .72rem; font-weight: 800; white-space: nowrap; color: var(--rk-c); background: color-mix(in srgb, var(--rk-c) 14%, transparent); }
        html.theme-dark .rk-badge { color: color-mix(in srgb, var(--rk-c) 55%, #fff); }
        .rk-doc { font-size: .78rem; font-weight: 700; color: var(--sb-text, #4b5671); }
        .rk-actions { display: flex; flex-wrap: wrap; gap: 4px; }
        .rk-actions .btn { border-radius: 8px; font-weight: 600; white-space: nowrap; }
        #riwayat-table td { vertical-align: middle; }
        .rk-filters-2 .form-control { margin-left: 0; min-width: 0; width: auto; }
        .rk-filters-2 #filter_status_kunjungan { min-width: 160px; }
        .rk-chip-count { opacity: .75; font-weight: 800; }
        .rk-chip-count:empty { display: none; }
        .rk-bayar-chip.active { background: #dc2626; border-color: #dc2626; color: #fff; }
        .rk-reset { font-weight: 600; }
        button.rk-stat-note { border: 0; background: none; padding: 0; cursor: pointer; text-align: left; }
        button.rk-stat-note:hover { text-decoration: underline; }
        .rk-hidden-note { display: none; margin: -4px 0 10px; font-size: .8rem; color: var(--sb-muted, #8a94ad); }
        .rk-hidden-note a { font-weight: 700; cursor: pointer; }
        /* Row content */
        .rk-date { font-weight: 700; color: var(--sb-text-strong, #1e2538); }
        .rk-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 3px; }
        .rk-dokter { font-weight: 600; }
        .rk-sub { font-size: .76rem; color: var(--sb-muted, #8a94ad); }
        .rk-sub.is-due { color: #dc2626; font-weight: 700; }
        .rk-dx { font-size: .84rem; color: var(--sb-text-strong, #1e2538); max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rk-chips-sm { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
        .rk-chip-sm { display: inline-flex; align-items: center; gap: 4px; padding: 1px 8px; border-radius: 999px; font-size: .7rem; font-weight: 700; background: var(--sb-tile, #f1f5f9); color: var(--sb-text, #4b5671); }
        .rk-bill { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .rk-amount { font-weight: 800; color: var(--sb-text-strong, #1e2538); }
        .rk-amount.is-muted { color: var(--sb-muted, #8a94ad); font-weight: 600; }
        .rk-sub .is-due { color: #dc2626; font-weight: 700; }
    </style>

    <div class="erm-form elab-page" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        <section class="erm-section">
            <div class="erm-section-head">
                <span class="erm-section-num"><i class="fas fa-history"></i></span>
                <h4 class="erm-section-title">Riwayat Kunjungan<span class="erm-section-sub">Semua kunjungan pasien, terbaru di atas</span></h4>
            </div>

            <div class="rk-stats">
                <div class="rk-stat">
                    <i class="fas fa-calendar-check"></i>
                    <div>
                        <div class="rk-stat-label">Kunjungan selesai</div>
                        <div class="rk-stat-value">{{ number_format($stats['total_visits']) }}</div>
                    </div>
                </div>
                <div class="rk-stat">
                    <i class="fas fa-clock"></i>
                    <div>
                        <div class="rk-stat-label">Kunjungan terakhir</div>
                        <div class="rk-stat-value">{{ $stats['last_visit'] }}</div>
                    </div>
                </div>
                <div class="rk-stat">
                    <i class="fas fa-file-invoice"></i>
                    <div>
                        <div class="rk-stat-label">Total tagihan</div>
                        <div class="rk-stat-value">Rp {{ number_format($stats['total_tagihan'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="rk-stat">
                    <i class="fas fa-wallet"></i>
                    <div>
                        <div class="rk-stat-label">Terbayar</div>
                        <div class="rk-stat-value">Rp {{ number_format($stats['total_terbayar'], 0, ',', '.') }}</div>
                        @php $sisa = $stats['total_tagihan'] - $stats['total_terbayar']; @endphp
                        @if($sisa > 0)
                            <button type="button" class="rk-stat-note is-due rk-due-filter" title="Tampilkan kunjungan yang belum lunas">Belum dibayar Rp {{ number_format($sisa, 0, ',', '.') }} <i class="fas fa-filter"></i></button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rk-filters">
                @foreach($jenisFilters as $key => $filter)
                    <button type="button" class="rk-chip {{ $key === 'klinis' ? 'active' : '' }}" data-jenis="{{ $key }}">{{ $filter['label'] }} <span class="rk-chip-count" data-count-for="{{ $key }}"></span></button>
                @endforeach
            </div>
            <div class="rk-filters rk-filters-2">
                <select id="filter_status_kunjungan" class="form-control form-control-sm" aria-label="Status kunjungan">
                    <option value="">Semua status</option>
                    <option value="0">Tidak datang</option>
                    <option value="1">Belum diperiksa</option>
                    <option value="2" selected>Sudah diperiksa</option>
                    <option value="7">Dibatalkan</option>
                </select>
                <input type="date" id="filter_date_from" class="form-control form-control-sm" aria-label="Dari tanggal" title="Dari tanggal">
                <span class="rk-sub">s/d</span>
                <input type="date" id="filter_date_to" class="form-control form-control-sm" aria-label="Sampai tanggal" title="Sampai tanggal">
                <button type="button" class="rk-chip rk-bayar-chip" id="filterBayar" title="Hanya kunjungan yang tagihannya belum lunas"><i class="fas fa-file-invoice-dollar mr-1"></i>Belum lunas</button>
                <button type="button" class="btn btn-sm btn-link rk-reset" id="btnResetFilter">Reset filter</button>
            </div>
            <div class="rk-hidden-note" id="rkHiddenNote"></div>

            <div class="table-responsive">
                <table class="table w-100" id="riwayat-table">
                    <thead>
                        <tr>
                            <th style="width: 40px">No</th>
                            <th style="min-width: 150px">Kunjungan</th>
                            <th>Dokter</th>
                            <th style="min-width: 180px">Ringkasan</th>
                            <th>Tagihan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>

        @include('erm.partials.modal-pdf-preview')
    </div>
</div>
@endsection

@section('scripts')
{{-- Page data for public/js/erm-riwayat-kunjungan.js --}}
<script>window.RiwayatKunjunganConfig = {!! json_encode([
    'routes' => [
        'data' => route('erm.riwayatkunjungan.index', $pasien),
        'diagnosisData' => url('/erm/riwayatkunjungan/__ID__/get-data-diagnosis'),
        'diagnosisStore' => url('/erm/riwayatkunjungan/store-surat-diagnosis'),
        'diagnosisPrint' => url('/erm/riwayatkunjungan/__ID__/print-surat-diagnosis'),
        'diagnosisPrintEn' => url('/erm/riwayatkunjungan/__ID__/print-surat-diagnosis-en'),
    ],
], 15) !!};</script>
<script src="{{ asset('js/erm-pdf-preview.js') }}?v={{ filemtime(public_path('js/erm-pdf-preview.js')) }}"></script>
<script src="{{ asset('js/erm-riwayat-kunjungan.js') }}?v={{ filemtime(public_path('js/erm-riwayat-kunjungan.js')) }}"></script>
@endsection

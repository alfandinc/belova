@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('styles')
    <link rel="stylesheet" href="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/css/fixedColumns.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/erm/rawatjalans.css') }}?v={{ filemtime(public_path('assets/css/erm/rawatjalans.css')) }}">
@endsection

@section('content')

@include('erm.partials.modal-reschedule')
@include('erm.rawatjalans.partials.modal-daftar-kunjungan')

<div class="modal fade" id="modalKalenderAntrian" tabindex="-1" role="dialog" aria-labelledby="modalKalenderAntrianLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalKalenderAntrianLabel">Kalender Antrian</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 queue-calendar-toolbar">
                    <div>
                        <div class="font-weight-bold" id="queue-calendar-month-label">Bulan Ini</div>
                        <small class="text-muted" id="queue-calendar-summary">Memuat data antrian...</small>
                    </div>
                    <div class="d-flex align-items-center queue-calendar-toolbar-actions">
                        <input type="month" id="queue-calendar-month-filter" class="form-control form-control-sm mr-2">
                        <select id="queue-calendar-dokter-filter" class="form-control form-control-sm mr-2">
                            <option value="">Semua Dokter</option>
                            @foreach($dokters as $dokter)
                                <option value="{{ $dokter->id }}" {{ isset($defaultDokterId) && $defaultDokterId == $dokter->id ? 'selected' : '' }}>{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-queue-calendar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div id="queue-calendar-content">
                    <div class="text-center text-muted py-5">
                        <span class="spinner-border spinner-border-sm mr-2"></span>Memuat kalender antrian...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- Screening Batuk modals are lazy-loaded on-demand to keep initial page HTML light --}}

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Rawat Jalan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    {{-- Lab/Rujuk/Visitation list modals are lazy-loaded on-demand --}}

    {{-- Alur Pelayanan on top (shared with Billing / E-Resep); Lab / Rujuk / Batal stay as side counters (filled by updateStats) --}}
    <div data-alur-strip="card">
        <button type="button" class="alur-side stat-card-clickable" data-status="lab_permintaan">Lab <b id="stat-lab-permintaan">{{ $stats['lab_permintaan'] ?? 0 }}</b></button>
        <button type="button" class="alur-side stat-card-clickable" data-status="rujuk">Rujuk <b id="stat-rujuk">{{ $stats['rujuk'] ?? 0 }}</b></button>
        <button type="button" class="alur-side stat-card-clickable" data-status="dibatalkan">Batal <b id="stat-dibatalkan">{{ $stats['dibatalkan'] }}</b></button>
    </div>

    <div class="card">
        <div class="card-body">
            <style>
                /* One compact filter bar: tanggal range | dokter | klinik */
                .rj-filterbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 14px; }
                .rj-filter { display: flex; flex-direction: column; min-width: 0; }
                .rj-filter > label { font-size: 12px; font-weight: 500; text-transform: none; letter-spacing: 0; opacity: .7; margin-bottom: 4px; }
                .rj-filter-tanggal { flex: 0 0 auto; }
                .rj-filter-dokter { flex: 2 1 280px; }
                .rj-filter-klinik { flex: 1 1 220px; }
                .rj-daterange { display: flex; align-items: center; border: 1px solid rgba(128,128,128,.35); border-radius: 6px; overflow: hidden; }
                .rj-daterange input { border: 0 !important; box-shadow: none !important; background: transparent; width: 150px; height: 36px; }
                .rj-daterange span { opacity: .5; padding: 0 2px; }
                .rj-filterbar .select2-container .select2-selection--single { height: 38px; }
                .rj-filter-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-left: auto; }
                .rj-filter-actions .btn { height: 38px; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
                .rj-filter-dokter { flex: 2 1 220px; }
                .rj-filter-klinik { flex: 1 1 180px; }
            </style>
            <div class="rj-filterbar">
                <div class="rj-filter rj-filter-tanggal">
                    <label for="filter_start_date">Tanggal</label>
                    <div class="rj-daterange">
                        <input type="date" id="filter_start_date" class="form-control" aria-label="Dari tanggal" />
                        <span>–</span>
                        <input type="date" id="filter_end_date" class="form-control" aria-label="Sampai tanggal" />
                    </div>
                </div>
                {{-- Show dokter filter to everyone, but pre-select logged-in Dokter when available --}}
                <div class="rj-filter rj-filter-dokter">
                    <label for="filter_dokter">Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}" {{ isset($defaultDokterId) && $defaultDokterId == $dokter->id ? 'selected' : '' }}>{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rj-filter rj-filter-klinik">
                    <label for="filter_klinik">Klinik</label>
                    <select id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Page actions sit on the filter row, aligned right --}}
                <div class="rj-filter-actions">
                    <div class="btn-group" role="group" aria-label="Dokter room actions">
                        @if (auth()->user() && auth()->user()->hasRole('Dokter'))
                            <button id="btn-buka-pintu" class="btn btn-danger">
                                <i class="fas fa-door-open"></i> Buka Pintu
                            </button>
                            <button id="btn-panggil-perawat" class="btn btn-warning">
                                <i class="fas fa-bell"></i> Panggil Perawat
                            </button>
                        @elseif (auth()->check())
                            <button id="btn-notification-history" class="btn btn-warning position-relative">
                                <i class="fas fa-bell"></i> Notification
                                <span id="notification-unread-badge" class="badge badge-danger position-absolute" style="top:-6px; right:-6px; min-width:18px; height:18px; line-height:18px; padding:0 4px; font-size:10px; border-radius:999px; display:none;">0</span>
                            </button>
                            <button id="btn-merchandise-stock-out" class="btn btn-info">
                                <i class="fas fa-gift"></i> Merchandise
                            </button>
                        @endif
                    </div>
                    <div class="btn-group" role="group" aria-label="Rawat jalan actions">
                        <button type="button" class="btn btn-primary" id="btn-queue-calendar">
                            <i class="fas fa-calendar-alt"></i> Antrian
                        </button>
                        @include('erm.partials.daftar-kunjungan-dropdown')
                    </div>
                </div>
            </div>
            {{-- Jenis kunjungan filter: outlined until chosen, filled when chosen (--chip = jenis colour); count from the server --}}
            @php
                $jenisChipColors = \App\Models\ERM\Visitation::TYPE_COLORS;
            @endphp
            <style>
                /* Neutral chips: the jenis colour is only a dot; the chosen one gets a light tint of it */
                .rj-jenis-chips { display: flex; flex-wrap: wrap; gap: 6px; padding-top: 12px; border-top: 1px solid rgba(128,128,128,.18); }
                .rj-jenis-chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(128,128,128,.28); color: inherit; background: transparent;
                    font-weight: 500; border-radius: 6px; padding: 3px 10px; font-size: 0.8rem; box-shadow: none !important; }
                .rj-jenis-chip::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--chip); }
                .rj-jenis-chip[data-jenis-filter=""]::before { display: none; }
                .rj-jenis-chip:hover { color: inherit; background: rgba(128,128,128,.10); }
                .rj-jenis-chip.active, .rj-jenis-chip.active:hover { color: inherit; font-weight: 600; border-color: var(--chip);
                    background: rgba(128,128,128,.12); background: color-mix(in srgb, var(--chip) 14%, transparent); }
                .rj-jenis-chip .rj-jenis-count { margin-left: 0; opacity: .55; font-variant-numeric: tabular-nums; }
                .rj-jenis-chip.is-empty:not(.active) { display: none; }
            </style>
            <div class="rj-jenis-chips mb-3" id="rj-jenis-chips" role="group" aria-label="Filter jenis kunjungan">
                @foreach(\App\Models\ERM\Visitation::rawatJalanFilters() as $key => $filter)
                    @php
                        $chipColor = match (true) {
                            $key === '' => '#64748b',
                            $key === 'klinis' => '#0f766e',
                            default => $jenisChipColors[$filter['types'][0]] ?? '#64748b',
                        };
                    @endphp
                    <button type="button" class="btn btn-sm rj-jenis-chip{{ $key === '' ? ' active' : '' }}" data-jenis-filter="{{ $key }}" style="--chip: {{ $chipColor }};">
                        {{ $filter['label'] }}<span class="rj-jenis-count"></span>
                    </button>
                @endforeach
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Nama Pasien</th>
                        <th>Informasi Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        @if (empty($isDokter))
                            <th>Dokter</th>
                        @endif
                        <th>Metode Bayar</th>
                        <th>Referral</th>
                        <!-- Selesai Asesmen column removed; will show under Dokumen -->
                        <th>Dokumen</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('dastone/vendor/datatable/FixedColumns-4.3.0/js/dataTables.fixedColumns.min.js') }}"></script>
@include('erm.partials.alur-pelayanan')
<script src="{{ route('erm.rawatjalans.assets.js') }}?v={{ filemtime(resource_path('views/erm/rawatjalans/assets/index_js.blade.php')) }}"></script>
@endsection



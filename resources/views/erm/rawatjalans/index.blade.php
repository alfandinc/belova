@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/erm/rawatjalans.css') }}">
@endsection

@section('content')

@include('erm.partials.modal-reschedule')
@include('erm.rawatjalans.partials.modal-daftar-kunjungan')
@include('erm.rawatjalans.partials.modal-visitation-chat')
@include('erm.rawatjalans.partials.modal-scheduled-messages')


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

    <div class="row mb-3">
        <div class="col-12">
            <div class="rawatjalan-toolbar">
                <div class="rawatjalan-toolbar-actions rawatjalan-toolbar-actions-left">
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
                        @endif
                    </div>
                </div>

                <div class="rawatjalan-toolbar-stats">
                    <div class="stats-row">
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable stat-theme-primary" data-status="total">
                <div class="card-body stat-pill-body">
                    <div class="stat-icon-shell">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-pill-content">
                        <div class="stat-label">Total</div>
                            <div class="stat-number" id="stat-total">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable stat-theme-warning" data-status="belum_diperiksa">
                <div class="card-body stat-pill-body">
                    <div class="stat-icon-shell">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-pill-content">
                        <div class="stat-label">Menunggu</div>
                            <div class="stat-number" id="stat-belum-diperiksa">{{ $stats['belum_diperiksa'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable stat-theme-success" data-status="sudah_diperiksa">
                <div class="card-body stat-pill-body">
                    <div class="stat-icon-shell">
                        <div class="stat-icon">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    <div class="stat-pill-content">
                        <div class="stat-label">Selesai</div>
                            <div class="stat-number" id="stat-sudah-diperiksa">{{ $stats['sudah_diperiksa'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable stat-theme-purple" data-status="lab_permintaan">
                <div class="card-body stat-pill-body">
                    <div class="stat-icon-shell">
                        <div class="stat-icon">
                            <i class="fas fa-vials"></i>
                        </div>
                    </div>
                    <div class="stat-pill-content">
                        <div class="stat-label">Lab</div>
                            <div class="stat-number" id="stat-lab-permintaan">{{ $stats['lab_permintaan'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable stat-theme-purple" data-status="rujuk">
                <div class="card-body stat-pill-body">
                    <div class="stat-icon-shell">
                        <div class="stat-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                    </div>
                    <div class="stat-pill-content">
                        <div class="stat-label">Rujuk</div>
                            <div class="stat-number" id="stat-rujuk">{{ $stats['rujuk'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable stat-theme-danger" data-status="dibatalkan">
                <div class="card-body stat-pill-body">
                    <div class="stat-icon-shell">
                        <div class="stat-icon">
                            <i class="fas fa-times"></i>
                        </div>
                    </div>
                    <div class="stat-pill-content">
                        <div class="stat-label">Batal</div>
                            <div class="stat-number" id="stat-dibatalkan">{{ $stats['dibatalkan'] }}</div>
                    </div>
                </div>
            </div>
        </div>
                    </div>
                </div>

                <div class="rawatjalan-toolbar-actions rawatjalan-toolbar-actions-right">
                    <div class="btn-group" role="group" aria-label="Rawat jalan actions">
                        <button type="button" class="btn btn-success" id="btn-scheduled-messages">
                            <i class="fab fa-whatsapp"></i> Whatsapp Bot
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-calendar-plus"></i> Daftarkan Pasien
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item btn-daftarkan-pasien-rawatjalan" href="#" data-jenis="konsultasi">Konsultasi</a>
                                <a class="dropdown-item btn-daftarkan-pasien-rawatjalan" href="#" data-jenis="produk">Produk</a>
                                <a class="dropdown-item btn-daftarkan-pasien-rawatjalan" href="#" data-jenis="lab">Lab</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lab/Rujuk/Visitation list modals are lazy-loaded on-demand --}}

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="filter_start_date">Start Date</label>
                    <input type="date" id="filter_start_date" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label for="filter_end_date">End Date</label>
                    <input type="date" id="filter_end_date" class="form-control" />
                </div>
                {{-- Show dokter filter to everyone, but pre-select logged-in Dokter when available --}}
                <div class="col-md-4">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}" {{ isset($defaultDokterId) && $defaultDokterId == $dokter->id ? 'selected' : '' }}>{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter_klinik">Filter Klinik</label>
                    <select id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>
                            @if (!empty($isDokter))
                                No
                            @else
                                Antrian
                            @endif
                        </th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Metode Bayar</th>
                        @if (empty($isDokter))
                            <th>Dokter</th>
                        @endif
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
<script src="{{ route('erm.rawatjalans.assets.js') }}?v={{ filemtime(resource_path('views/erm/rawatjalans/assets/index_js.blade.php')) }}"></script>
@endsection



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

    <!-- Dokter-to-Perawat Notification Button -->
    @if (auth()->user() && auth()->user()->hasRole('Dokter'))
    <div class="row mb-3">
        <div class="col-md-12 d-flex gap-2">
            <button id="btn-buka-pintu" class="btn btn-danger mr-2">
                <i class="fas fa-door-open"></i> Perawat Buka Pintu
            </button>
            <button id="btn-panggil-perawat" class="btn btn-warning">
                <i class="fas fa-bell"></i> Panggil Perawat ke Ruang Dokter
            </button>
        </div>
    </div>
    @endif
    <!-- Statistics Cards -->
    <div class="row mb-4 stats-row">
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="total" style="border: 2px solid #007bff; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Total Visit</h6>
                            <h4 class="mb-0 text-primary stat-number" id="stat-total">{{ $stats['total'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="belum_diperiksa" style="border: 2px solid #ffc107; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Belum Diperiksa</h6>
                            <h4 class="mb-0 text-warning stat-number" id="stat-belum-diperiksa">{{ $stats['belum_diperiksa'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="sudah_diperiksa" style="border: 2px solid #28a745; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-success d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Sudah Diperiksa</h6>
                            <h4 class="mb-0 text-success stat-number" id="stat-sudah-diperiksa">{{ $stats['sudah_diperiksa'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="tidak_datang" style="border: 2px solid #17a2b8; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-info d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-user-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Tidak Datang</h6>
                            <h4 class="mb-0 text-info stat-number" id="stat-tidak-datang">{{ $stats['tidak_datang'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="dibatalkan" style="border: 2px solid #dc3545; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px;">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Dibatalkan</h6>
                            <h4 class="mb-0 text-danger stat-number" id="stat-dibatalkan">{{ $stats['dibatalkan'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <div class="stat-col">
                <div class="card shadow-sm stat-card stat-card-clickable" data-status="rujuk" style="border: 2px solid #6f42c1; border-radius: 10px; cursor:pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <div class="rounded-circle bg-purple d-flex align-items-center justify-content-center stat-icon" style="width: 48px; height: 48px; background-color:#6f42c1;">
                                    <i class="fas fa-share-alt text-white"></i>
                                </div>
                            </div>
                            <div class="flex-fill">
                                <h6 class="mb-1 font-weight-bold text-muted">Rujuk/Konsultasi</h6>
                                <h4 class="mb-0 text-dark stat-number" id="stat-rujuk">{{ $stats['rujuk'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <div class="stat-col">
            <div class="card shadow-sm stat-card stat-card-clickable" data-status="lab_permintaan" style="border: 2px solid #20c997; border-radius: 10px; cursor:pointer;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center stat-icon" style="width:48px;height:48px;background:linear-gradient(135deg,#20c997,#0d8865);">
                                <i class="fas fa-vials text-white"></i>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1 font-weight-bold text-muted">Permintaan Lab</h6>
                            <h4 class="mb-0 text-teal stat-number" id="stat-lab-permintaan">{{ $stats['lab_permintaan'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <!-- 7th Card Template (duplicate & adjust as needed) -->
            <!--
            <div class="stat-col">
                <div class="card shadow-sm stat-card stat-card-clickable" data-status="baru" style="border: 2px solid #0d6efd; border-radius: 10px; cursor:pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center stat-icon">
                                    <i class="fas fa-star text-white"></i>
                                </div>
                            </div>
                            <div class="flex-fill">
                                <h6 class="mb-1 font-weight-bold text-muted">Label Baru</h6>
                                <h4 class="mb-0 text-primary stat-number" id="stat-baru">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            -->
    </div>

    {{-- Lab/Rujuk/Visitation list modals are lazy-loaded on-demand --}}

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Kunjungan Rawat Jalan</h4>
        </div>
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
                            @if ($role === 'Dokter')
                                No
                            @else
                                Antrian
                            @endif
                        </th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Dokter</th>
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
<script src="{{ route('erm.rawatjalans.assets.js') }}"></script>
@endsection



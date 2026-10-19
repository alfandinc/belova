@extends('layouts.erm.app')

@section('title', 'ERM | E-Radiologi')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-radiologi-create')
@include('erm.partials.modal-radiologi-hasil')
@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0"><strong>E-Radiologi</strong></h3>
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
                            <li class="breadcrumb-item active">E-Radiologi</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    @include('erm.partials.form-theme')
    {{-- Same look as E-Lab --}}
    <link rel="stylesheet" href="{{ asset('css/erm-penunjang.css') }}?v={{ filemtime(public_path('css/erm-penunjang.css')) }}">

    <div class="erm-form elab-page" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        <div class="row">
            <!-- 1. Pilih pemeriksaan -->
            <div class="col-xl-8 mb-3">
                <section class="erm-section h-100">
                    <div class="erm-section-head">
                        <span class="erm-section-num">1</span>
                        <h4 class="erm-section-title">Pilih Pemeriksaan<span class="erm-section-sub">Centang pemeriksaan radiologi</span></h4>
                        <div class="elab-head-tools">
                            <button type="button" class="btn elab-tool-btn is-main" data-toggle="modal" data-target="#modalHasilRadiologi">
                                <i class="fas fa-x-ray"></i>Hasil Radiologi<span class="elab-count">{{ $hasilCount }}</span>
                            </button>
                            <button type="button" class="btn elab-tool-btn" data-toggle="modal" data-target="#modalRiwayatRadiologi">
                                <i class="fas fa-history"></i>Riwayat<span class="elab-count">{{ $riwayatCount }}</span>
                            </button>
                            <a href="{{ route('erm.eradiologi.print', $visitation->id) }}" class="btn elab-tool-btn btn-pdf-preview" id="printPermintaanBtn" data-title="Permintaan Radiologi" title="Formulir permintaan radiologi kunjungan ini">
                                <i class="fas fa-print"></i>Print Permintaan
                            </a>
                            @if($previousRadiologi)
                                <button type="button" class="btn elab-tool-btn" id="repeatPrevious" title="Centang lagi pemeriksaan dari kunjungan radiologi terakhir">
                                    <i class="fas fa-redo"></i>Ulangi {{ $previousRadiologi['tanggal'] }}
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="erm-subhead">Diagnosa Kerja</div>
                    <div class="elab-diagnosa">
                        @php
                            $diagnosaKerja = $lastAsesmen
                                ? collect([1, 2, 3, 4, 5])->map(fn ($i) => $lastAsesmen->{'diagnosakerja_' . $i})->filter()->values()
                                : collect();
                        @endphp
                        @forelse($diagnosaKerja as $diagnosa)
                            <span class="elab-diagnosa-chip">{{ $diagnosa }}</span>
                        @empty
                            <span class="elab-diagnosa-empty">Belum ada diagnosa kerja untuk pasien ini.</span>
                        @endforelse
                    </div>

                    <div class="elab-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="testSearch" class="form-control" placeholder="Cari pemeriksaan radiologi..." autocomplete="off">
                    </div>

                    <div class="erm-subhead">Pemeriksaan</div>
                    <div class="elab-kategori-grid">
                        @foreach($radiologiCategories as $category)
                            @continue($category->radiologiTests->isEmpty())
                            <div class="elab-kategori">
                                <div class="elab-kategori-head">{{ $category->nama }}</div>
                                <div class="elab-kategori-body">
                                    @foreach($category->radiologiTests as $test)
                                        <label class="elab-test form-check-label" data-search="{{ mb_strtolower($test->nama . ' ' . $category->nama) }}" data-test-id="{{ $test->id }}">
                                            <input class="test-checkbox" type="checkbox"
                                                data-id="{{ $test->id }}"
                                                data-name="{{ $test->nama }}"
                                                data-price="{{ $test->harga }}"
                                                {{ array_key_exists($test->id, $existingStatuses) ? 'checked' : '' }}>
                                            <span class="elab-test-name">{{ $test->nama }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="elab-empty-search" id="searchEmpty">Tidak ada pemeriksaan yang cocok.</div>
                </section>
            </div>

            <!-- 2. Permintaan terpilih -->
            <div class="col-xl-4 mb-3">
                <section class="erm-section elab-selected">
                    <div class="erm-section-head">
                        <span class="erm-section-num">2</span>
                        <h4 class="erm-section-title">Permintaan Radiologi<span class="erm-section-sub">Kunjungan ini{{ $canEditStatus ? ' · klik status untuk mengubah' : '' }}</span></h4>
                    </div>
                    @if($hasilThisVisit > 0)
                        <div class="elab-hasil-banner">
                            <i class="fas fa-check-circle"></i>
                            <span>Hasil radiologi kunjungan ini sudah diunggah ({{ $hasilThisVisit }} dokumen).</span>
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalHasilRadiologi">Lihat</button>
                        </div>
                    @endif
                    @if($canEditStatus)
                        <div class="elab-bulk">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="selectAll">
                                <label class="custom-control-label" for="selectAll">Pilih Semua</label>
                            </div>
                            <div class="elab-bulk-status bulk-status-container ml-auto">
                                <button type="button" class="btn btn-warning bulk-status-btn" data-status="requested">Diminta</button>
                                <button type="button" class="btn btn-info bulk-status-btn" data-status="processing">Diproses</button>
                                <button type="button" class="btn btn-success bulk-status-btn" data-status="completed">Selesai</button>
                            </div>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-sm elab-selected-table" id="selectedTable">
                            <thead>
                                <tr>
                                    <th style="width: 44px">No</th>
                                    <th>Pemeriksaan</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="elab-total">
                        <span>Estimasi Harga</span>
                        <strong>Rp <span id="estimasiTotal">0</span></strong>
                    </div>
                </section>
            </div>
        </div>

        <div class="erm-actionbar">
            <span class="erm-actionbar-hint" id="saveHint"><i class="fas fa-info-circle mr-1"></i>Pemeriksaan yang dicentang baru tersimpan setelah klik Simpan.</span>
            <span class="elab-actionbar-total">Estimasi <strong>Rp <span id="estimasiBar">0</span></strong></span>
            <button type="button" id="submitRequests" class="btn btn-erm-primary"><i class="fas fa-save mr-1"></i>Simpan Permintaan Radiologi</button>
        </div>

        {{-- Hasil radiologi (all visits): the table loads on the first open only --}}
        <div class="modal fade" id="modalHasilRadiologi" tabindex="-1" role="dialog" aria-labelledby="modalHasilRadiologiLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalHasilRadiologiLabel">Hasil Radiologi <small class="text-muted">&middot; semua kunjungan pasien</small></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="elab-tab-tools">
                            <button type="button" class="btn btn-primary btn-sm" id="btnUploadHasil">
                                <i class="fas fa-upload mr-1"></i>Upload Hasil Radiologi
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="dokumenRadiologiTable" class="table w-100">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">No</th>
                                        <th style="width: 15%">Tanggal</th>
                                        <th style="width: 25%">Dokter Pengirim</th>
                                        <th>Pemeriksaan</th>
                                        <th style="width: 12%">Hasil</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PDF preview (print permintaan) without leaving the page --}}
        @include('erm.partials.modal-pdf-preview')

        {{-- Riwayat permintaan radiologi: the visit list loads on the first open, each visit's tests on its first expand --}}
        <div class="modal fade" id="modalRiwayatRadiologi" tabindex="-1" role="dialog" aria-labelledby="modalRiwayatRadiologiLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRiwayatRadiologiLabel">Riwayat Permintaan Radiologi <small class="text-muted">&middot; klik kunjungan untuk melihat isinya</small></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="historyTable" class="table w-100 elab-history-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px"></th>
                                        <th style="width: 30%">Tanggal Kunjungan</th>
                                        <th>Dokter</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{-- Page data for public/js/erm-eradiologi.js --}}
<script>window.EradiologiConfig = {!! json_encode([
    'visitationId' => $visitation->id,
    'pasienId' => (string) $visitation->pasien_id,
    'existingStatuses' => (object) $existingStatuses,
    'previous' => $previousRadiologi,
    'canEditStatus' => (bool) $canEditStatus,
    'routes' => [
        'sync' => route('erm.eradiologi.sync'),
        'dokumenData' => route('erm.eradiologi.dokumen.data', $visitation->id),
        'hasilDetail' => url('/erm/eradiologi/hasil/__ID__'),
        'hasilDeskripsi' => url('/erm/eradiologi/hasil/__ID__/deskripsi'),
        'hasilUpload' => route('erm.eradiologi.hasil.upload'),
        'history' => url('/erm/eradiologi/patient/' . rawurlencode((string) $visitation->pasien_id) . '/history'),
        'visitDetail' => url('/erm/eradiologi/visitation/__ID__/detail'),
        'print' => route('erm.eradiologi.print', $visitation->id),
    ],
], 15) !!};</script>
<script src="{{ asset('js/erm-pdf-preview.js') }}?v={{ filemtime(public_path('js/erm-pdf-preview.js')) }}"></script>
<script src="{{ asset('js/erm-eradiologi.js') }}?v={{ filemtime(public_path('js/erm-eradiologi.js')) }}"></script>
@endsection

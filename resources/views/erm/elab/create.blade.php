@extends('layouts.erm.app')

@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-lis-hasil')
@include('erm.partials.modal-lis-hasil-add')
@include('erm.partials.modal-eksternal-hasil')
@include('erm.partials.modal-eksternal-hasil-add')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <input type="hidden" id="visitationId" value="{{ $visitation->id }}">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0"><strong>E-Laboratorium</strong></h3>
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
                            <li class="breadcrumb-item active">E-Lab</li>
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

    <div class="erm-form elab-page" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        <div class="row">
            <!-- 1. Pilih pemeriksaan -->
            <div class="col-xl-8 mb-3">
                <section class="erm-section h-100">
                    <div class="erm-section-head">
                        <span class="erm-section-num">1</span>
                        <h4 class="erm-section-title">Pilih Pemeriksaan<span class="erm-section-sub">Centang paket atau pemeriksaan satuan</span></h4>
                        <div class="elab-head-tools">
                            <button type="button" class="btn elab-tool-btn is-main" data-toggle="modal" data-target="#modalHasilLab">
                                <i class="fas fa-vial"></i>Hasil Lab<span class="elab-count">{{ $hasilLabCount }}</span>
                            </button>
                            <button type="button" class="btn elab-tool-btn" data-toggle="modal" data-target="#modalRiwayatLab">
                                <i class="fas fa-history"></i>Riwayat<span class="elab-count">{{ $riwayatLabCount }}</span>
                            </button>
                            @if($lisVisitCount > 0)
                                <a href="{{ route('erm.elab.lembar-monitoring.pdf', $visitation->pasien_id) }}" class="btn elab-tool-btn btn-pdf-preview" data-title="Lembar Monitoring Lab" title="Hasil LIS 5 kunjungan terakhir">
                                    <i class="fas fa-file-medical-alt"></i>Lembar Monitoring
                                </a>
                            @endif
                            @if($previousLab)
                                <button type="button" class="btn elab-tool-btn" id="repeatPreviousLab" title="Centang lagi pemeriksaan dari kunjungan lab terakhir">
                                    <i class="fas fa-redo"></i>Ulangi Lab {{ $previousLab['tanggal'] }}
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
                        <input type="text" id="labTestSearch" class="form-control" placeholder="Cari pemeriksaan atau paket..." autocomplete="off">
                    </div>

                    <form id="labRequestForm" onsubmit="return false;">
                        @csrf
                        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">

                        @if($labPakets->count() > 0)
                            <div class="erm-subhead">Paket Lab <span class="text-lowercase font-weight-normal">&middot; semua pemeriksaan di dalam paket ikut diminta</span></div>
                            <div class="elab-paket-grid">
                                @foreach($labPakets as $paket)
                                    <label class="elab-paket form-check-label" data-search="{{ mb_strtolower($paket->nama . ' ' . $paket->labTests->pluck('nama')->implode(' ')) }}">
                                        <div class="elab-paket-top">
                                            <input class="lab-paket-checkbox" type="checkbox" id="paket-{{ $paket->id }}" data-id="{{ $paket->id }}"
                                                {{ in_array($paket->id, $existingLabPaketIds) ? 'checked' : '' }}>
                                            <div>
                                                <div class="elab-paket-name">{{ $paket->nama }}</div>
                                                <div class="elab-paket-price">Rp {{ number_format($paket->harga_paket, 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                        <div class="elab-paket-isi">{{ $paket->labTests->pluck('nama')->implode(' · ') ?: 'Belum ada isi' }}</div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="erm-subhead">Pemeriksaan Satuan</div>
                        <div class="elab-kategori-grid">
                            @foreach($labCategories as $category)
                                @continue($category->labTests->isEmpty())
                                <div class="elab-kategori">
                                    <div class="elab-kategori-head">{{ $category->nama }}</div>
                                    <div class="elab-kategori-body">
                                        @foreach($category->labTests as $test)
                                            @php $available = isset($test->available) && $test->available === 'yes'; @endphp
                                            <label class="elab-test form-check-label" data-search="{{ mb_strtolower($test->nama) }}" data-test-id="{{ $test->id }}">
                                                <input class="lab-test-checkbox" type="checkbox"
                                                    id="test-{{ $test->id }}"
                                                    data-id="{{ $test->id }}"
                                                    data-name="{{ $test->nama }}"
                                                    data-price="{{ $test->harga }}"
                                                    {{ in_array($test->id, $existingLabTestIds) ? 'checked' : '' }}>
                                                <span class="elab-test-name">{{ $test->nama }}</span>
                                                <span class="elab-test-tag is-paket">PAKET</span>
                                                @unless($available)
                                                    <span class="elab-test-tag is-off" title="Tidak tersedia di lab klinik">N/A</span>
                                                @endunless
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="elab-empty-search" id="labSearchEmpty">Tidak ada pemeriksaan yang cocok.</div>
                    </form>
                </section>
            </div>

            <!-- 2. Permintaan terpilih -->
            <div class="col-xl-4 mb-3">
                <section class="erm-section elab-selected">
                    <div class="erm-section-head">
                        <span class="erm-section-num">2</span>
                        <h4 class="erm-section-title">Permintaan Lab<span class="erm-section-sub">Kunjungan ini{{ $canEditLabStatus ? ' · klik status untuk mengubah' : '' }}</span></h4>
                    </div>
                    @if($hasilLisNames->isNotEmpty())
                        <div class="elab-hasil-banner">
                            <i class="fas fa-check-circle"></i>
                            <span>Hasil lab kunjungan ini sudah keluar ({{ $hasilLisNames->count() }} item).</span>
                            <button type="button" class="btn btn-sm btn-success btn-view-hasil-lis" data-id="{{ $visitation->id }}">Lihat</button>
                        </div>
                    @endif
                    @if($canEditLabStatus)
                    <div class="elab-bulk">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="selectAllLab">
                            <label class="custom-control-label" for="selectAllLab">Pilih Semua</label>
                        </div>
                        <div class="elab-bulk-status bulk-status-container ml-auto">
                            <button type="button" class="btn btn-warning bulk-status-btn" data-status="requested">Diminta</button>
                            <button type="button" class="btn btn-info bulk-status-btn" data-status="processing">Diproses</button>
                            <button type="button" class="btn btn-success bulk-status-btn" data-status="completed">Selesai</button>
                        </div>
                    </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-sm elab-selected-table" id="checkedLabTable">
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
                        <strong>Rp <span id="estimasiHargaTotal">0</span></strong>
                    </div>
                </section>
            </div>

        </div>

        <div class="erm-actionbar">
            <span class="erm-actionbar-hint" id="labSaveHint"><i class="fas fa-info-circle mr-1"></i>Pemeriksaan yang dicentang baru tersimpan setelah klik Simpan.</span>
            <span class="elab-actionbar-total">Estimasi <strong>Rp <span id="estimasiHargaBar">0</span></strong></span>
            <button type="button" id="submitLabRequests" class="btn btn-erm-primary"><i class="fas fa-save mr-1"></i>Simpan Permintaan Lab</button>
        </div>

        {{-- Hasil lab (all visits): tables load on the first open only --}}
        <div class="modal fade" id="modalHasilLab" tabindex="-1" role="dialog" aria-labelledby="modalHasilLabLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header align-items-center">
                        <h5 class="modal-title" id="modalHasilLabLabel">Hasil Lab <small class="text-muted">&middot; semua kunjungan pasien</small></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav elab-seg" id="labResultsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="hasil-lis-tab" data-toggle="tab" href="#hasil-lis-content" role="tab" aria-controls="hasil-lis-content" aria-selected="true">
                                    <i class="fas fa-hospital mr-1"></i>Hasil LIS
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="hasil-eksternal-tab" data-toggle="tab" href="#hasil-eksternal-content" role="tab" aria-controls="hasil-eksternal-content" aria-selected="false">
                                    <i class="fas fa-file-medical mr-1"></i>Hasil Eksternal
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content" id="labResultsTabsContent">
                            <div class="tab-pane fade show active" id="hasil-lis-content" role="tabpanel" aria-labelledby="hasil-lis-tab">
                                <div class="table-responsive">
                                    <table id="hasilLisTable" class="table w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">No</th>
                                                <th style="width: 18%">Tanggal Kunjungan</th>
                                                <th>Pemeriksaan</th>
                                                <th style="width: 18%">Dokter</th>
                                                <th style="width: 22%">Aksi</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="hasil-eksternal-content" role="tabpanel" aria-labelledby="hasil-eksternal-tab">
                                <div class="elab-tab-tools">
                                    <button type="button" class="btn btn-primary btn-sm" id="addEksternalHasilBtn">
                                        <i class="fas fa-plus mr-1"></i>Tambah Hasil Lab Eksternal
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table id="hasilEksternalTable" class="table w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">No</th>
                                                <th style="width: 20%">Asal Lab</th>
                                                <th>Nama Pemeriksaan</th>
                                                <th style="width: 15%">Dokter</th>
                                                <th style="width: 15%">Tanggal Periksa</th>
                                                <th style="width: 15%">Aksi</th>
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

        {{-- PDF preview (hasil LIS, lembar monitoring) without leaving the page --}}
        @include('erm.partials.modal-pdf-preview')

        {{-- Riwayat permintaan lab: the visit list loads on the first open, each visit's tests on its first expand --}}
        <div class="modal fade" id="modalRiwayatLab" tabindex="-1" role="dialog" aria-labelledby="modalRiwayatLabLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRiwayatLabLabel">Riwayat Permintaan Lab <small class="text-muted">&middot; klik kunjungan untuk melihat isinya</small></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="labHistoryTable" class="table w-100 elab-history-table">
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
{{-- Page data for public/js/erm-elab.js --}}
<script>window.ElabConfig = {!! json_encode([
    'visitationId' => $visitation->id,
    'pasienId' => (string) $visitation->pasien_id,
    'existingLabTestStatuses' => (object) $existingLabTestStatuses,
    'existingLabTestPaket' => (object) $existingLabTestPaket,
    'previousLab' => $previousLab,
    'hasilLisNames' => $hasilLisNames,
    'canEditStatus' => (bool) $canEditLabStatus,
    'labPakets' => $labPaketPayload,
    'routes' => [
        'bulkUpdate' => route('erm.elab.bulk-update'),
        'hasilLisData' => url('/erm/elab/' . $visitation->id . '/hasil-lis/data'),
        'hasilLisStore' => route('erm.elab.hasil-lis.store'),
        'hasilEksternalData' => url('/erm/elab/' . $visitation->id . '/hasil-eksternal/data'),
        'hasilEksternalStore' => route('erm.elab.hasil-eksternal.store'),
    ],
], 15) !!};</script>
<script src="{{ asset('js/erm-pdf-preview.js') }}?v={{ filemtime(public_path('js/erm-pdf-preview.js')) }}"></script>
<script src="{{ asset('js/erm-elab.js') }}?v={{ filemtime(public_path('js/erm-elab.js')) }}"></script>
@endsection

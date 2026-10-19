@extends('layouts.erm.app')

@section('title', 'ERM | Surat Keterangan')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0"><strong>Surat Keterangan</strong></h3>
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
                            <li class="breadcrumb-item active">Surat Keterangan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    @include('erm.partials.form-theme')
    {{-- Same look as E-Lab / E-Radiologi --}}
    <link rel="stylesheet" href="{{ asset('css/erm-penunjang.css') }}?v={{ filemtime(public_path('css/erm-penunjang.css')) }}">

    <div class="erm-form elab-page surat-page" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        <div class="row">
            <!-- 1. Surat Istirahat -->
            <div class="col-xl-6 mb-3">
                <section class="erm-section h-100">
                    <div class="erm-section-head">
                        <span class="erm-section-num">1</span>
                        <h4 class="erm-section-title">Surat Istirahat<span class="erm-section-sub">Istirahat sakit, maksimal {{ \App\Http\Controllers\ERM\SuratIstirahatController::MAX_HARI_ISTIRAHAT }} hari</span></h4>
                        <div class="elab-head-tools">
                            <button type="button" class="btn elab-tool-btn is-main" id="btnBuatIstirahat"><i class="fas fa-plus"></i>Buat Surat</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover w-100" id="suratTable">
                            <thead>
                                <tr><th>Dokter</th><th>Periode</th><th>Dibuat</th><th class="text-right">Aksi</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- 2. Surat Mondok -->
            <div class="col-xl-6 mb-3">
                <section class="erm-section h-100">
                    <div class="erm-section-head">
                        <span class="erm-section-num">2</span>
                        <h4 class="erm-section-title">Surat Mondok<span class="erm-section-sub">Pengantar rawat inap ke IGD</span></h4>
                        <div class="elab-head-tools">
                            <button type="button" class="btn elab-tool-btn is-main" id="btnBuatMondok"><i class="fas fa-plus"></i>Buat Surat</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover w-100" id="suratMondokTable">
                            <thead>
                                <tr><th>Dokter</th><th>Tujuan IGD</th><th>Tanggal</th><th class="text-right">Aksi</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- 3. Surat Diagnosa -->
            <div class="col-12 mb-3">
                <section class="erm-section">
                    <div class="erm-section-head">
                        <span class="erm-section-num">3</span>
                        <h4 class="erm-section-title">Surat Diagnosa<span class="erm-section-sub">Satu surat per kunjungan, berisi diagnosa kerja kunjungan itu</span></h4>
                        <div class="elab-head-tools">
                            <button type="button" class="btn elab-tool-btn is-main" id="btnBuatDiagnosa"><i class="fas fa-plus"></i>Buat Surat Diagnosa</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover w-100" id="suratDiagnosaTable">
                            <thead>
                                <tr><th style="width:40px">No</th><th>Dokter</th><th>Keterangan</th><th>Tanggal</th><th class="text-right">Aksi</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <!-- Modal Surat Istirahat -->
        <div class="modal fade" id="modalSurat" tabindex="-1" role="dialog" aria-labelledby="modalSuratLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form id="formSurat" class="modal-content" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSuratLabel"><i class="fas fa-bed mr-1"></i> Buat Surat Istirahat</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                        <div class="form-group">
                            <label for="dokter_id">Dokter</label>
                            <select id="dokter_id" name="dokter_id" class="form-control surat-select" required>
                                @foreach ($dokters as $dokter)
                                    <option value="{{ $dokter->id }}" @selected($dokter->id == $defaultDokterId)>{{ $dokter->user->name ?? 'Tanpa Nama' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lama istirahat</label>
                            <div class="surat-quick">
                                @for ($i = 1; $i <= \App\Http\Controllers\ERM\SuratIstirahatController::MAX_HARI_ISTIRAHAT; $i++)
                                    <button type="button" class="btn btn-sm btn-outline-primary surat-quick-btn" data-hari="{{ $i }}">{{ $i }} hari</button>
                                @endfor
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="tanggal_mulai">Tanggal Mulai</label>
                                <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" required>
                            </div>
                            <div class="col-sm-6 form-group">
                                <label for="tanggal_selesai">Tanggal Selesai</label>
                                <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>
                        <div class="surat-hari" id="istirahatInfo"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanIstirahat"><i class="fas fa-save mr-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Surat Mondok -->
        <div class="modal fade" id="modalSuratMondok" tabindex="-1" role="dialog" aria-labelledby="modalSuratMondokLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <form id="formSuratMondok" class="modal-content" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSuratMondokLabel"><i class="fas fa-hospital mr-1"></i> Buat Surat Mondok</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="visitation_id_mondok">Kunjungan</label>
                                <select id="visitation_id_mondok" name="visitation_id" class="form-control surat-select surat-visit-select" required></select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="dokter_id_mondok">Dokter</label>
                                <select id="dokter_id_mondok" name="dokter_id" class="form-control surat-select" required>
                                    @foreach ($dokters as $dokter)
                                        <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? 'Tanpa Nama' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="mondokTanggal">Tanggal Surat</label>
                                <input type="date" id="mondokTanggal" name="created_at" class="form-control" required>
                                <small class="form-text text-muted">Mengikuti tanggal kunjungan, bisa diubah.</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="mondokTujuan">Tujuan IGD</label>
                                <input type="text" id="mondokTujuan" name="tujuan_igd" class="form-control" maxlength="255" placeholder="Contoh: IGD RSUD ..." required>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="mondokDiagnosa" class="mb-0">Diagnosa</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAutoFill"><i class="fas fa-magic mr-1"></i>Ambil dari asesmen</button>
                            </div>
                            <textarea id="mondokDiagnosa" name="diagnosa" class="form-control" rows="3" placeholder="Diagnosa pasien" required></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label for="mondokInstruksi">Instruksi Terapi</label>
                            <textarea id="mondokInstruksi" name="instruksi_terapi" class="form-control" rows="4" placeholder="Instruksi terapi untuk IGD" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanMondok"><i class="fas fa-save mr-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Surat Diagnosa -->
        <div class="modal fade" id="modalSuratDiagnosa" tabindex="-1" role="dialog" aria-labelledby="modalSuratDiagnosaLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form id="formSuratDiagnosa" class="modal-content" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSuratDiagnosaLabel"><i class="fas fa-file-medical-alt mr-1"></i> Surat Diagnosa</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="diagnosaKunjunganSelect">Kunjungan</label>
                            <select id="diagnosaKunjunganSelect" name="visitation_id" class="form-control surat-select surat-visit-select" required></select>
                            <small class="form-text text-muted">Dokter dan diagnosa di surat mengikuti kunjungan ini.</small>
                        </div>
                        <div class="alert alert-info py-2 small d-none" id="diagnosaExists"><i class="fas fa-info-circle mr-1"></i>Kunjungan ini sudah punya surat diagnosa; menyimpan akan memperbarui keterangannya.</div>
                        <div class="form-group">
                            <label>Diagnosa Kerja</label>
                            <div class="elab-diagnosa mb-0" id="diagnosaKerjaList"></div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="diagnosaKeterangan">Keterangan</label>
                            <textarea id="diagnosaKeterangan" name="keterangan" class="form-control" rows="3" maxlength="191" placeholder="Keterangan tambahan (opsional)"></textarea>
                            <small class="form-text text-muted text-right" id="diagnosaKeteranganCount">0/191</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanDiagnosa"><i class="fas fa-save mr-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        @include('erm.partials.modal-pdf-preview')
    </div>
</div><!-- container -->

<style>
    .surat-page .table td { vertical-align: middle; }
    .surat-quick { display: flex; flex-wrap: wrap; gap: 6px; }
    .surat-quick-btn.active { background: var(--ef-accent); border-color: var(--ef-accent); color: #fff; }
    .surat-hari { font-size: .88rem; font-weight: 600; color: var(--ef-accent-text); min-height: 1.3em; }
    .surat-hari.is-invalid { color: #dc2626; }
</style>
@endsection

@section('scripts')
{{-- Page data for public/js/erm-surat-keterangan.js --}}
<script>window.SuratKeteranganConfig = {!! json_encode([
    'pasienId' => (string) $pasien->id,
    'visitationId' => $visitation->id,
    'maxHari' => \App\Http\Controllers\ERM\SuratIstirahatController::MAX_HARI_ISTIRAHAT,
    'today' => now()->format('Y-m-d'),
    'visits' => $visitOptions,
    'routes' => [
        'istirahatData' => route('erm.suratistirahat.index', $pasien->id),
        'istirahatStore' => route('erm.suratistirahat.store'),
        'mondokData' => route('erm.suratmondok.data', $pasien->id),
        'mondokStore' => route('erm.suratmondok.store'),
        'asesmenData' => url('/erm/suratmondok/asesmen-data/__ID__'),
        'diagnosaData' => route('riwayatkunjungan.get-data-diagnosis-table', $pasien->id),
        'diagnosaStore' => url('/erm/riwayatkunjungan/store-surat-diagnosis'),
        'diagnosaPrint' => url('/erm/riwayatkunjungan/__ID__/print-surat-diagnosis'),
    ],
], 15) !!};</script>
<script src="{{ asset('js/erm-pdf-preview.js') }}?v={{ filemtime(public_path('js/erm-pdf-preview.js')) }}"></script>
<script src="{{ asset('js/erm-surat-keterangan.js') }}?v={{ filemtime(public_path('js/erm-surat-keterangan.js')) }}"></script>
@endsection

@extends('layouts.erm.app')
@section('title', 'ERM | CPPT')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@php
    $filteredJenisKonsultasi = collect($jenisKonsultasi)->values();
    $visitSpesialisasi = $visitation->resolvedSpesialisasi();
    $defaultJenisKonsultasi = (string) old('jenis_konsultasi', optional($visitSpesialisasi)->id == 6 ? 1 : 2);
    if (!$filteredJenisKonsultasi->contains('id', (int) $defaultJenisKonsultasi)) {
        $defaultJenisKonsultasi = (string) optional($filteredJenisKonsultasi->first())->id;
    }
    $konsultasiPopupOptions = $filteredJenisKonsultasi
        ->mapWithKeys(fn ($konsultasi) => [
            (string) $konsultasi->id => $konsultasi->nama . ' - Rp ' . number_format($konsultasi->harga, 0, ',', '.'),
        ])
        ->all();
@endphp

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Catatan Perkembangan</h3>
        <h3 class="mb-0"><strong>Pasien Terintegrasi</strong></h3>
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
                            <li class="breadcrumb-item active">CPPT</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    @include('erm.partials.card-identitaspasien')

    @include('erm.partials.form-theme')
    <style>
        /* SOAP / SBAR switch */
        .cppt-seg { display: inline-flex; gap: 4px; padding: 4px; margin: 4px 0 14px; border-radius: 12px; background: var(--sb-tile, #f1f5f9); border: 1px solid var(--sb-line, #e2e8f0); }
        .cppt-seg .nav-link { border-radius: 9px; padding: 7px 22px; font-weight: 700; font-size: .9rem; color: var(--sb-text, #4b5671); }
        .cppt-seg .nav-link.active { background: var(--ef-accent); color: #fff; box-shadow: 0 2px 8px rgba(var(--ef-accent-rgb), .3); }
        .cppt-seg .nav-link small { display: block; font-size: .7rem; font-weight: 500; opacity: .8; }
        /* Field label with its letter */
        .cppt-field-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .cppt-letter {
            flex: 0 0 26px; width: 26px; height: 26px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
            background: rgba(var(--ef-accent-rgb), .12); color: var(--ef-accent-text); font-weight: 800; font-size: .85rem;
        }
        .erm-form .cppt-field-head label { margin: 0; font-size: .88rem; font-weight: 700; color: var(--sb-text-strong, #1e2538); }
        .cppt-field-head .btn { margin-left: auto; border-radius: 8px; font-size: .75rem; font-weight: 600; padding: 3px 10px; }
        .erm-form .cppt-req { color: #dc2626; }

        /* Riwayat CPPT: date + initial | S/O/A/P rows | TTD (same layout as the old table) */
        .cppt-item { display: grid; grid-template-columns: 150px minmax(0, 1fr) 160px; border: 1px solid var(--sb-line, #e2e8f0); border-radius: 12px; background: var(--sb-bg, #fff); margin-bottom: 14px; overflow: hidden; }
        .cppt-meta, .cppt-side { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 14px 10px; text-align: center; background: var(--sb-tile, #f1f5f9); }
        .cppt-meta { border-right: 1px solid var(--sb-line, #e2e8f0); }
        .cppt-side { border-left: 1px solid var(--sb-line, #e2e8f0); }
        .cppt-date { font-size: .78rem; font-weight: 700; color: var(--sb-muted, #8a94ad); }
        .cppt-initial { font-size: 3.2rem; font-weight: 800; line-height: 1; color: #64748b; }
        .cppt-initial.is-dokter { color: var(--ef-accent-text); }
        .cppt-initial.is-perawat { color: #0d9488; }
        .cppt-type { font-size: .68rem; font-weight: 800; letter-spacing: .05em; padding: 2px 9px; border-radius: 999px; background: rgba(var(--ef-accent-rgb), .12); color: var(--ef-accent-text); }
        .cppt-row { display: grid; grid-template-columns: 170px minmax(0, 1fr); align-items: center; border-bottom: 1px solid var(--sb-line, #e2e8f0); min-height: 56px; }
        .cppt-row:last-child { border-bottom: 0; }
        .cppt-row-label { padding: 10px 14px; align-self: stretch; display: flex; align-items: center; border-right: 1px solid var(--sb-line, #e2e8f0); }
        .cppt-pill { display: inline-block; padding: 5px 14px; border-radius: 999px; background: var(--ef-accent); color: #fff; font-size: .78rem; font-weight: 700; white-space: nowrap; }
        .cppt-value { padding: 10px 16px; white-space: pre-line; word-break: break-word; font-size: .9rem; color: var(--sb-text-strong, #1e2538); }
        .cppt-value.is-empty { color: var(--sb-muted, #8a94ad); }
        .cppt-side-name { font-size: .82rem; font-weight: 700; color: var(--sb-text-strong, #1e2538); }
        .cppt-side-role { font-size: .72rem; font-weight: 700; color: var(--ef-accent-text); }
        .cppt-side img { width: 92px; height: 92px; object-fit: contain; background: #fff; border-radius: 8px; border: 1px solid var(--sb-line, #e2e8f0); padding: 3px; }
        .cppt-side .btn { border-radius: 8px; font-weight: 600; }
        .cppt-read { font-size: .75rem; color: #16a34a; line-height: 1.3; }
        @media (max-width: 991.98px) {
            .cppt-item { grid-template-columns: 1fr; }
            .cppt-meta { flex-direction: row; justify-content: flex-start; gap: 12px; border-right: 0; border-bottom: 1px solid var(--sb-line, #e2e8f0); }
            .cppt-initial { font-size: 1.8rem; }
            .cppt-side { flex-direction: row; flex-wrap: wrap; border-left: 0; border-top: 1px solid var(--sb-line, #e2e8f0); }
            .cppt-side img { width: 64px; height: 64px; }
            .cppt-row { grid-template-columns: 1fr; }
            .cppt-row-label { border-right: 0; padding-bottom: 0; }
        }
    </style>

    <div class="erm-form" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        <ul class="nav cppt-seg" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#soap" role="tab" aria-selected="true">SOAP<small>Subject · Object · Assessment · Planning</small></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#sbar" role="tab" aria-selected="false">SBAR<small>Situation · Background · Assessment · Recommendation</small></a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="soap" role="tabpanel">
                <form id="form-cppt-soap" action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="jenis_dokumen" value="1">
                    <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">

                    <section class="erm-section">
                        <div class="erm-section-head">
                            <span class="erm-section-num">1</span>
                            <h4 class="erm-section-title">Catatan SOAP<span class="erm-section-sub">Semua kolom bertanda * wajib diisi</span></h4>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">S</span><label for="subjectS">Subject <span class="cppt-req">*</span></label>
                                </div>
                                <textarea id="subjectS" name="s" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">O</span><label for="objectO">Object <span class="cppt-req">*</span></label>
                                    @if(auth()->user() && auth()->user()->hasRole('Perawat'))
                                        <button type="button" class="btn btn-outline-secondary" id="btnTemplateO"><i class="fas fa-paste mr-1"></i>Template</button>
                                    @endif
                                </div>
                                <textarea id="objectO" name="o" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">A</span><label for="assessmentA">Assessment <span class="cppt-req">*</span></label>
                                </div>
                                <select id="assessmentOptions" class="form-control select2" style="width: 100%;">
                                    <option value="">-- Pilih Assessment --</option>
                                    <option value="Ansietas">Ansietas</option>
                                    <option value="Bersihan Jalan Nafas Tidak Efektif">Bersihan Jalan Nafas Tidak Efektif</option>
                                    <option value="Defisit Nutrisi">Defisit Nutrisi</option>
                                    <option value="Defisit Pengetahuan">Defisit Pengetahuan</option>
                                    <option value="Defisit Perawat Diri">Defisit Perawat Diri</option>
                                    <option value="Diare">Diare</option>
                                    <option value="Gangguan Citra Tubuh">Gangguan Citra Tubuh</option>
                                    <option value="Gangguan Eliminasi Urin">Gangguan Eliminasi Urin</option>
                                    <option value="Gangguan Komunikasi Verbal">Gangguan Komunikasi Verbal</option>
                                    <option value="Gangguan Menelan">Gangguan Menelan</option>
                                    <option value="Gangguan Mobilitas Fisik">Gangguan Mobilitas Fisik</option>
                                    <option value="Gangguan Pertukaran Gas">Gangguan Pertukaran Gas</option>
                                    <option value="Gangguan Rasa Nyaman">Gangguan Rasa Nyaman</option>
                                    <option value="Gangguan Sirkuasi Spontan">Gangguan Sirkuasi Spontan</option>
                                    <option value="Gangguan Tumbuh Kembang">Gangguan Tumbuh Kembang</option>
                                    <option value="Hipertemi">Hipertemi</option>
                                    <option value="Hipertemia/Hipotermia">Hipertemia/Hipotermia</option>
                                    <option value="Hipervolemia">Hipervolemia</option>
                                    <option value="Hipotermi">Hipotermi</option>
                                    <option value="Hypovolemia">Hypovolemia</option>
                                    <option value="Ikterik Neonatus">Ikterik Neonatus</option>
                                    <option value="Intoleransi Aktifitas">Intoleransi Aktifitas</option>
                                    <option value="Keletihan">Keletihan</option>
                                    <option value="Kesiapan Persalinan">Kesiapan Persalinan</option>
                                    <option value="Ketidaknyamanan Pasca Partum">Ketidaknyamanan Pasca Partum</option>
                                    <option value="Ketidakstabilan Kadar Glukosa Darah">Ketidakstabilan Kadar Glukosa Darah</option>
                                    <option value="Konstipasi">Konstipasi</option>
                                    <option value="Menyusui Efektif">Menyusui Efektif</option>
                                    <option value="Menyusui Tidak Efektif">Menyusui Tidak Efektif</option>
                                    <option value="Nausea">Nausea</option>
                                    <option value="Nyeri Akut">Nyeri Akut</option>
                                    <option value="Nyeri Kronis">Nyeri Kronis</option>
                                    <option value="Penurunan Curah Jantung">Penurunan Curah Jantung</option>
                                    <option value="Perfusi Perifer Tidak Efektif">Perfusi Perifer Tidak Efektif</option>
                                    <option value="Perilaku Kesehatan Cenderung Berisiko">Perilaku Kesehatan Cenderung Berisiko</option>
                                    <option value="Perlambatan Pemulihan Pasca Bedah">Perlambatan Pemulihan Pasca Bedah</option>
                                    <option value="Pola Nafas Tidak Efektif">Pola Nafas Tidak Efektif</option>
                                    <option value="Retensi Urin">Retensi Urin</option>
                                    <option value="Risiko Alergi">Risiko Alergi</option>
                                    <option value="Risiko Aspirasi">Risiko Aspirasi</option>
                                    <option value="Risiko Cidera">Risiko Cidera</option>
                                    <option value="Risiko Defisit Nutrisi">Risiko Defisit Nutrisi</option>
                                    <option value="Risiko Gangguan Integritas Kulit">Risiko Gangguan Integritas Kulit</option>
                                    <option value="Risiko Hipotermi">Risiko Hipotermi</option>
                                    <option value="Risiko Ikterik Neonatus">Risiko Ikterik Neonatus</option>
                                    <option value="Risiko Infeksi">Risiko Infeksi</option>
                                    <option value="Risiko Jatuh">Risiko Jatuh</option>
                                    <option value="Risiko Ketidakseimbangan Cairan">Risiko Ketidakseimbangan Cairan</option>
                                    <option value="Risiko Ketidakseimbangan Elektrolit">Risiko Ketidakseimbangan Elektrolit</option>
                                    <option value="Risiko Perdarahan">Risiko Perdarahan</option>
                                    <option value="Risiko Perfusi Perifer Tidak Efektif">Risiko Perfusi Perifer Tidak Efektif</option>
                                    <option value="Risiko Perfusi Renal Tidak Efektif">Risiko Perfusi Renal Tidak Efektif</option>
                                    <option value="Risiko Proses Pengasuhan Tidak Efektif">Risiko Proses Pengasuhan Tidak Efektif</option>
                                    <option value="Risiko Syok">Risiko Syok</option>
                                </select>
                                <textarea id="assessmentA" name="a" class="form-control mt-2" rows="4" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">P</span><label for="planningP">Planning <span class="cppt-req">*</span></label>
                                    @if(auth()->user() && auth()->user()->hasRole('Perawat'))
                                        <button type="button" class="btn btn-outline-secondary" id="btnTemplateP"><i class="fas fa-paste mr-1"></i>Template</button>
                                    @endif
                                </div>
                                <textarea id="planningP" name="p" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="instruksiSoap">Rencana Tindak Lanjut</label>
                                <select id="instruksiSoap" name="instruksi" class="form-control">
                                    <option value="kembali">Kontrol Kembali</option>
                                    <option value="selesai">Kontrol Selesai</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <div class="erm-actionbar">
                        <span class="erm-actionbar-hint"><i class="fas fa-info-circle mr-1"></i>Biaya konsultasi dipilih saat menyimpan.</span>
                        <button type="button" id="btnMarkAllReadSoap" class="btn btn-outline-secondary"><i class="fas fa-check-double mr-1"></i>Tandai Dibaca Semua</button>
                        <button type="submit" class="btn btn-erm-primary"><i class="fas fa-save mr-1"></i>Simpan SOAP</button>
                    </div>
                </form>
            </div>

            <div class="tab-pane" id="sbar" role="tabpanel">
                <form id="form-cppt-sbar" action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="jenis_dokumen" value="2">
                    <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">

                    <section class="erm-section">
                        <div class="erm-section-head">
                            <span class="erm-section-num">1</span>
                            <h4 class="erm-section-title">Catatan SBAR<span class="erm-section-sub">Semua kolom bertanda * wajib diisi</span></h4>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">S</span><label for="situationS">Situation <span class="cppt-req">*</span></label>
                                </div>
                                <textarea id="situationS" name="s" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">B</span><label for="backgroundB">Background <span class="cppt-req">*</span></label>
                                </div>
                                <textarea id="backgroundB" name="o" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">A</span><label for="assessmentSbar">Assessment <span class="cppt-req">*</span></label>
                                </div>
                                <textarea id="assessmentSbar" name="a" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="cppt-field-head">
                                    <span class="cppt-letter">R</span><label for="recommendationR">Recommendation <span class="cppt-req">*</span></label>
                                </div>
                                <textarea id="recommendationR" name="p" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="instruksiSbar">Rencana Tindak Lanjut</label>
                                <select id="instruksiSbar" name="instruksi" class="form-control">
                                    <option value="kembali">Kontrol Kembali</option>
                                    <option value="selesai">Kontrol Selesai</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <div class="erm-actionbar">
                        <span class="erm-actionbar-hint"><i class="fas fa-info-circle mr-1"></i>Biaya konsultasi dipilih saat menyimpan.</span>
                        <button type="button" id="btnMarkAllReadSbar" class="btn btn-outline-secondary"><i class="fas fa-check-double mr-1"></i>Tandai Dibaca Semua</button>
                        <button type="submit" class="btn btn-erm-primary"><i class="fas fa-save mr-1"></i>Simpan SBAR</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Riwayat CPPT: every CPPT of this patient, rendered by refreshCpptHistory() --}}
        <section class="erm-section mt-4">
            <div class="erm-section-head">
                <span class="erm-section-num">2</span>
                <h4 class="erm-section-title">Riwayat CPPT<span class="erm-section-sub">Semua kunjungan pasien, terbaru di atas</span></h4>
            </div>
            <div id="cppt-history" class="pb-2">
                <div class="text-center py-4" style="color: var(--sb-muted, #8a94ad);"><span class="spinner-border spinner-border-sm mr-2"></span>Memuat riwayat CPPT...</div>
            </div>
        </section>
    </div>
</div><!-- container -->

<!-- Edit CPPT Modal -->
<div class="modal fade" id="modalEditCppt" tabindex="-1" role="dialog" aria-labelledby="modalEditCpptLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditCpptLabel">Edit CPPT</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditCppt">
                        <div class="modal-body">
                            <input type="hidden" name="cppt_id" id="editCpptId">
                            <div class="form-group">
                                <label for="editS">Subject (S)</label>
                                <textarea class="form-control" name="s" id="editS" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editO">Object (O)</label>
                                <textarea class="form-control" name="o" id="editO" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editA">Assessment (A)</label>
                                <textarea class="form-control" name="a" id="editA" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editP">Planning (P)</label>
                                <textarea class="form-control" name="p" id="editP" rows="2"></textarea>
                            </div>
                        </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection
@section('scripts')
<script>
$(document).ready(function () {
    const konsultasiOptions = @json($konsultasiPopupOptions);
    let selectedJenisKonsultasi = @json($defaultJenisKonsultasi);

    async function promptJenisKonsultasi() {
        const availableOptions = Object.keys(konsultasiOptions);

        if (!availableOptions.length) {
            await Swal.fire({
                title: 'Biaya konsultasi tidak tersedia',
                text: 'Tidak ada jenis konsultasi dengan nama yang mengandung "Konsultasi".',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return null;
        }

        if (!availableOptions.includes(String(selectedJenisKonsultasi))) {
            selectedJenisKonsultasi = availableOptions[0];
        }

        const result = await Swal.fire({
            title: 'Pilih Biaya Konsultasi',
            input: 'select',
            inputOptions: konsultasiOptions,
            inputValue: String(selectedJenisKonsultasi),
            inputPlaceholder: 'Pilih biaya konsultasi',
            showCancelButton: true,
            confirmButtonText: 'Lanjut simpan',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Pilih biaya konsultasi terlebih dahulu.';
                }

                return undefined;
            }
        });

        if (!result.value) {
            return null;
        }

        selectedJenisKonsultasi = result.value;
        return result.value;
    }

    // Refresh CPPT history on page load
    var visitationId = $('input[name="visitation_id"]', '#form-cppt-soap').val() || $('input[name="visitation_id"]', '#form-cppt-sbar').val();
    if (visitationId) {
        refreshCpptHistory(visitationId);
    }
    $('.select2').select2({ width: '100%' });
    
    // Initialize assessment options select2 with minimum input length
    $('#assessmentOptions').select2({ 
        width: '100%',
        minimumInputLength: 2,
        placeholder: '-- Pilih Assessment --'
    });

    // Assessment options handler
    $('#assessmentOptions').on('select2:select', function (e) {
        var selectedData = e.params.data.text;
        var currentValue = $('#assessmentA').val();
        
        if (currentValue) {
            $('#assessmentA').val(currentValue + ', ' + selectedData);
        } else {
            $('#assessmentA').val(selectedData);
        }
        
        // Clear the select2 after selection
        $(this).val('').trigger('change');
    });

    // Modal alergi logic
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

        // Template button logic for Object (O)
        $('#btnTemplateO').on('click', function () {
            $('#objectO').val('KU = Baik\nT = \nN = \nRR = \nS = \nTB = \nBB = \nRESIKO JATUH= TIDAK BERESIKO\nSKALA NYERI= 0');
        });

        // Template button logic for Planning (P)
        $('#btnTemplateP').on('click', function () {
            $('#planningP').val('Monitor KU dan VS\nKolaborasi dengan Dokter');
        });

    var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val();
    if (initialStatusAlergi === 'ada') {
        $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').show();
    } else {
        $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').hide();
    }

    $('input[name="statusAlergi"]').on('change', function () {
        if ($(this).val() === 'ada') {
            $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').show();
        } else {
            $('#inputKataKunciWrapper, #selectAlergiWrapper, #selectKandunganWrapper').hide();
            $('#inputKataKunci').val('');
            $('#selectAlergi, #selectKandungan').val(null).trigger('change');
        }
    });

    // CPPT rows of the history, by id (the Edit button reads them from here)
    var cpptById = {};
    var canEditOwnCppt = {{ auth()->user() && auth()->user()->hasRole('Dokter') ? 'true' : 'false' }};
    var currentUserId = {{ (int) auth()->id() }};

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function formatWaktuBaca(value) {
        // "Y-m-d H:i:s" from the server: Safari only parses it with a "T"
        return new Date(String(value).replace(' ', 'T')).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function readStatusHtml(readerName, waktuBaca) {
        return '<div class="cppt-read"><i class="fas fa-check-double mr-1"></i>Dibaca ' + escapeHtml(readerName)
            + '<br><small>' + escapeHtml(waktuBaca) + '</small></div>';
    }

    function renderCpptItem(cppt) {
        var user = cppt.user;
        var roles = (user && user.roles) ? user.roles.map(function (role) { return role.name; }) : [];
        var userRole = roles.indexOf('Dokter') !== -1 ? 'Dokter' : (roles.indexOf('Perawat') !== -1 ? 'Perawat' : (roles[0] || 'Unknown'));
        var userInitial = userRole === 'Dokter' ? 'D' : (userRole === 'Perawat' ? 'P' : ((user && user.name) ? user.name.charAt(0).toUpperCase() : '-'));
        var roleClass = userRole === 'Dokter' ? ' is-dokter' : (userRole === 'Perawat' ? ' is-perawat' : '');
        var isSoap = cppt.jenis_dokumen == 1;
        var fields = isSoap
            ? [['Subject (S)', cppt.s], ['Object (O)', cppt.o], ['Assessment (A)', cppt.a], ['Planning (P)', cppt.p]]
            : [['Situation (S)', cppt.s], ['Background (B)', cppt.o], ['Assessment (A)', cppt.a], ['Recommendation (R)', cppt.p]];

        var rowsHtml = fields.map(function (field) {
            var hasValue = field[1] != null && String(field[1]).trim() !== '';
            return '<div class="cppt-row"><div class="cppt-row-label"><span class="cppt-pill">' + field[0] + '</span></div>'
                + '<div class="cppt-value' + (hasValue ? '' : ' is-empty') + '">' + (hasValue ? escapeHtml(field[1]) : '-') + '</div></div>';
        }).join('');

        var userName = user ? user.name : '';
        var qrData = encodeURIComponent(userName + ' - ' + userRole + ' - ' + cppt.created_at);
        var readHtml = cppt.dibaca
            ? readStatusHtml(cppt.reader ? cppt.reader.name : 'User tidak ditemukan', formatWaktuBaca(cppt.waktu_baca))
            : '<span><button class="btn btn-sm btn-outline-success btn-mark-read" data-cppt-id="' + cppt.id + '"><i class="fas fa-check mr-1"></i>Tandai Dibaca</button></span>';
        var editHtml = (userInitial === 'D' || (Number(cppt.user_id) === currentUserId && canEditOwnCppt))
            ? '<button class="btn btn-sm btn-outline-warning btn-edit-cppt" data-cppt-id="' + cppt.id + '"><i class="fas fa-pen mr-1"></i>Edit</button>'
            : '';

        return '<div class="cppt-item">'
            + '<div class="cppt-meta">'
            +   '<div class="cppt-date">' + escapeHtml(cppt.formatted_date) + '</div>'
            +   '<div class="cppt-initial' + roleClass + '">' + escapeHtml(userInitial) + '</div>'
            +   '<span class="cppt-type">' + (isSoap ? 'SOAP' : 'SBAR') + '</span>'
            + '</div>'
            + '<div class="cppt-rows">' + rowsHtml + '</div>'
            + '<div class="cppt-side">'
            +   '<div class="cppt-side-name">' + escapeHtml(userName || '-') + '</div>'
            +   '<div class="cppt-side-role">TTD ' + escapeHtml(userRole) + '</div>'
            +   '<img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + qrData + '" alt="TTD ' + escapeHtml(userRole) + '">'
            +   readHtml + editHtml
            + '</div>'
            + '</div>';
    }

    function refreshCpptHistory(visitationId) {
        $.ajax({
            url: '/erm/cppt/history-json/' + visitationId,
            type: 'GET',
            success: function (res) {
                cpptById = {};
                if (!res.length) {
                    $('#cppt-history').html('<p class="text-center py-4 mb-0" style="color: var(--sb-muted, #8a94ad);">Belum ada catatan CPPT.</p>');
                    return;
                }
                $('#cppt-history').html(res.map(function (cppt) {
                    cpptById[cppt.id] = cppt;
                    return renderCpptItem(cppt);
                }).join(''));
            },
            error: function () {
                $('#cppt-history').html('<p class="text-center text-danger py-4 mb-0">Gagal memuat riwayat CPPT.</p>');
            }
        });
    }

    // AJAX Submit - SOAP
    $('#form-cppt-soap').on('submit', async function(e) {
        e.preventDefault();
        const jenisKonsultasi = await promptJenisKonsultasi();
        if (!jenisKonsultasi) {
            return;
        }

        let formData = new FormData(this);
        let visitationId = formData.get('visitation_id');
        formData.set('jenis_konsultasi', jenisKonsultasi);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            },
            success: function (res) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'SOAP berhasil disimpan.', timer: 2000, showConfirmButton: false });
                $('#form-cppt-soap')[0].reset();
                refreshCpptHistory(visitationId);
            },
            error: function (xhr) {
                let msg = 'Terjadi kesalahan saat menyimpan.';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            }
        });
    });

    // AJAX Submit - SBAR
    $('#form-cppt-sbar').on('submit', async function(e) {
        e.preventDefault();
        const jenisKonsultasi = await promptJenisKonsultasi();
        if (!jenisKonsultasi) {
            return;
        }

        let formData = new FormData(this);
        let visitationId = formData.get('visitation_id');
        formData.set('jenis_konsultasi', jenisKonsultasi);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            },
            success: function (res) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'SBAR berhasil disimpan.', timer: 2000, showConfirmButton: false });
                $('#form-cppt-sbar')[0].reset();
                refreshCpptHistory(visitationId);
            },
            error: function (xhr) {
                let msg = 'Terjadi kesalahan saat menyimpan.';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            }
        });
    });

    // Tandai Dibaca button functionality
    $(document).on('click', '.btn-mark-read', function() {
        let cpptId = $(this).data('cppt-id');
        let button = $(this);
        
        $.ajax({
            url: '/erm/cppt/' + cpptId + '/mark-read',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function () {
                button.prop('disabled', true).text('Loading...');
            },
            success: function (res) {
                button.parent().replaceWith(readStatusHtml(res.reader_name, res.waktu_baca));
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Berhasil', 
                    text: res.message, 
                    timer: 2000, 
                    showConfirmButton: false 
                });
            },
            error: function (xhr) {
                let msg = 'Terjadi kesalahan saat menandai dibaca.';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                button.prop('disabled', false).text('Tandai Dibaca');
            }
        });
    });

        // Mark all CPPT as read handler
        function markAllCpptRead(visitationId) {
            Swal.fire({ title: 'Menandai semua...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            $.ajax({
                url: '/erm/cppt/mark-all-read/' + visitationId,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Semua CPPT ditandai sudah dibaca.', timer: 2000, showConfirmButton: false });
                    refreshCpptHistory(visitationId);
                },
                error: function (xhr) {
                    let msg = 'Terjadi kesalahan.';
                    if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                }
            });
        }

        $('#btnMarkAllReadSoap').on('click', function() {
            let visitationId = $('input[name="visitation_id"]', '#form-cppt-soap').val();
            markAllCpptRead(visitationId);
        });
        $('#btnMarkAllReadSbar').on('click', function() {
            let visitationId = $('input[name="visitation_id"]', '#form-cppt-sbar').val();
            markAllCpptRead(visitationId);
        });

        // Edit CPPT Modal logic
    $(document).on('click', '.btn-edit-cppt', function() {
        var cppt = cpptById[$(this).data('cppt-id')];
        if (!cppt) return;
    $('#editCpptId').val(cppt.id);
    $('#editS').val(cppt.s || '');
    $('#editO').val(cppt.o || '');
    $('#editA').val(cppt.a || '');
    $('#editP').val(cppt.p || '');
    $('#modalEditCppt').modal('show');
    });

    $('#formEditCppt').on('submit', function(e) {
        e.preventDefault();
        var cpptId = $('#editCpptId').val();
        var formData = $(this).serialize();
        $.ajax({
            url: '/erm/cppt/' + cpptId + '/update',
            type: 'POST',
            data: formData + '&_token={{ csrf_token() }}',
            success: function(res) {
                $('#modalEditCppt').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'CPPT berhasil diupdate.', timer: 2000, showConfirmButton: false });
                // Refresh history
                var visitationId = $('input[name="visitation_id"]', '#form-cppt-soap').val() || $('input[name="visitation_id"]', '#form-cppt-sbar').val();
                refreshCpptHistory(visitationId);
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan saat update.';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            }
        });
    });

});
</script>
@endsection



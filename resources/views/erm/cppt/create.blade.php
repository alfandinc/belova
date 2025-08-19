@extends('layouts.erm.app')
@section('title', 'ERM | CPPT')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
<style>
    .cppt-value {
        /* background: #23263a; */
        /* color: #fff; */
        border-radius: 0.7em;
        padding: 0.7em 1em;
        margin-bottom: 0.7em;
        /* font-size: 0.97rem; */
        box-shadow: 0 1px 6px rgba(33,150,243,0.07);
        word-break: break-word;
        border: 1px solid #2196f3;
    }
    .cppt-entry {
        /* background: #23263a; */
        border-radius: 1.2em;
        box-shadow: 0 2px 12px rgba(33,150,243,0.08);
        padding: 1.5em 1.2em;
        margin-bottom: 1.5em;
    display: flex;
    align-items: flex-start;
    position: relative;
    }
    .cppt-entry .cppt-label {
        margin-bottom: 0.5em;
    }
    .cppt-entry .cppt-meta {
        min-width: 140px;
        text-align: center;
        margin-right: 2em;
    }
    .cppt-entry .cppt-meta .display-4 {
    font-size: 4rem;
    font-weight: 700;
    color: #2196f3;
    margin-top: 0.5em;
    }
    .cppt-entry .cppt-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    }
    .cppt-entry .row {
        margin-bottom: 0.7em;
    }
    .cppt-entry .row:last-child {
        margin-bottom: 0;
    }
    .cppt-entry .cppt-qr {
        min-width: 120px;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin-left: 2em;
    }
    .cppt-entry .cppt-qr img {
        width: 80px;
        height: 80px;
        object-fit: contain;
        margin-bottom: 0.5em;
        background: #fff;
        border-radius: 0.5em;
        border: 1px solid #2196f3;
        box-shadow: 0 1px 6px rgba(33,150,243,0.07);
    }
    .cppt-entry .cppt-qr-label {
        font-size: 0.85rem;
        color: #2196f3;
        font-weight: bold;
        margin-bottom: 0.5em;
    }
    .cppt-label {
        background: #2196f3;
        color: #fff;
        font-weight: bold;
        font-size: 0.95rem;
        padding: 0.35em 1.2em;
        border-radius: 0.5em;
        box-shadow: 0 2px 8px rgba(33,150,243,0.12);
        display: inline-block;
        margin-bottom: 0.7em;
        letter-spacing: 0.5px;
        border: none;
    }
</style>

    <div class="d-flex align-items-center justify-content-between mb-0 mt-2">
        <div>
            <h3 class="mb-0">Catatan Perkembangan Pasien Terintegrasi</h3>
        </div>
        <div style="width: 400px;" class="d-flex align-items-center justify-content-end mt-2">
            <select class="form-control select2" name="jenis_konsultasi" id="jenis_konsultasi" style="margin-right: 20px; flex-shrink: 0;">
                <option value="" disabled>Pilih Jenis Konsultasi</option>
                @foreach ($jenisKonsultasi as $konsultasi)
                    <option value="{{ $konsultasi->id }}"
                        {{ old('jenis_konsultasi', $visitation->dokter->spesialisasi->id == 6 ? 1 : 2) == $konsultasi->id ? 'selected' : '' }}>
                        {{ $konsultasi->nama }} - Rp {{ $konsultasi->harga }}
                    </option>
                @endforeach
            </select>
        </div>
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

    <div class="card">
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-pills nav-justified" role="tablist">
                <li class="nav-item waves-effect waves-light">
                    <a class="nav-link active" data-toggle="tab" href="#soap" role="tab" aria-selected="true">SOAP</a>
                </li>
                <li class="nav-item waves-effect waves-light">
                    <a class="nav-link" data-toggle="tab" href="#sbar" role="tab" aria-selected="false">SBAR</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane p-3 active" id="soap" role="tabpanel">
                    <form id="form-cppt-soap" action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">

                @csrf
                <input type="hidden" name="jenis_dokumen" value="1">
                <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Subject (S) *</strong></label>
                        <textarea name="s" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label><strong>Object (O) *</strong></label></textarea>
                            <textarea id="objectO" name="o" class="form-control" rows="8" required></textarea>
                            @if(auth()->user() && auth()->user()->hasRole('Perawat'))
                            <button type="button" class="btn btn-secondary mt-2" id="btnTemplateO">Template</button>
                            @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Assessment (A) *</strong></label>
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
                        <textarea id="assessmentA" name="a" class="form-control mt-2" rows="6" required></textarea>
                        
                    </div>
                    <div class="col-md-6">
                        <label><strong>Planning (P) *</strong></label></textarea>
                        <textarea id="planningP" name="p" class="form-control" rows="8" required></textarea>
                            @if(auth()->user() && auth()->user()->hasRole('Perawat'))
                            <button type="button" class="btn btn-secondary mt-2" id="btnTemplateP">Template</button>
                            @endif

                    </div>
                </div>

                <div class="mb-3">
                    <label><strong>Rencana Tindak Lanjut</strong></label>
                    <select name="instruksi" class="form-control">
                        <option value="kembali">Kontrol Kembali</option>
                        <option value="selesai">Kontrol Selesai</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="mr-2">
                        <button type="submit" class="btn btn-primary">Simpan SOAP</button>
                    </div>
                </div>
            </form>
                    
                </div>
                <div class="tab-pane p-3" id="sbar" role="tabpanel">
                    <form id="form-cppt-sbar" action="{{ route('erm.cppt.store') }}" method="POST" enctype="multipart/form-data">

                @csrf
                <input type="hidden" name="jenis_dokumen" value="2">
                <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                <!-- Biaya konsultasi select only at top, not inside SBAR form -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Situation (S) *</strong></label>
                        <textarea name="s" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label><strong>Background (B) *</strong></label>
                        <textarea name="o" class="form-control" rows="8" required></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong>Assessment (A) *</strong></label>
                        <textarea name="a" class="form-control" rows="8" required></textarea>
                        
                    </div>
                    <div class="col-md-6">
                        <label><strong>Recommendation (R) *</strong></label>
                        <textarea name="p" class="form-control" rows="8" required></textarea>

                    </div>
                </div>

                <div class="mb-3">
                    <label><strong>Rencana Tindak Lanjut</strong></label>
                    <select name="instruksi" class="form-control">
                        <option value="kembali">Kontrol Kembali</option>
                        <option value="selesai">Kontrol Selesai</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="mr-2">
                        <button type="submit" class="btn btn-primary">Simpan SBAR</button>
                    </div>
                </div>
            </form>
                    
                </div>
            </div>    
        </div><!--end card-body-->
    </div><!--end card-->

    {{-- CPPT History --}}
<div class="card mt-4">
    <div class="card-header bg-light">
        <strong>Riwayat CPPT</strong>
        
    </div>
    <div class="card-body">
        @forelse ($cpptList as $cppt)
            @php $user = $cppt->user; @endphp
            @if ($cppt->jenis_dokumen == 1)
                <table class="table table-bordered mb-3" style="table-layout: fixed; width: 100%; border: 1px solid #007bff;">
                    <tr>
                        <td rowspan="4" style="width: 180px; border: 1px solid #007bff; text-align: center; vertical-align: middle;">
                            <div class="font-weight-bold text-muted small">{{ \Carbon\Carbon::parse($cppt->created_at)->translatedFormat('d M Y H:i') }}</div>
                            <div class="display-4">
                                @if ($user && $user->hasRole('Perawat'))
                                    P
                                @elseif ($user && $user->hasRole('Dokter'))
                                    D
                                @else
                                    {{ strtoupper(substr(optional($user)->name ?? '', 0, 1)) }}
                                @endif
                            </div>
                        </td>
                        <td style="width: 150px; border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Subject (S)</span></td>
                        <td class="cppt-value" style="border: 1px solid #007bff;">{{ $cppt->s }}</td>
                        <td rowspan="4" style="width: 140px; border: 1px solid #007bff; text-align: center; vertical-align: middle;">
                            @if ($user)
                                <div class="cppt-qr-label">{{ $user->name }}</div>
                                @if ($user->hasRole('Dokter'))
                                    <div class="cppt-qr-label">TTD Dokter</div>
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($user->name . ' - Dokter - ' . $cppt->created_at) }}" alt="QR Dokter" style="width: 120px; height: 120px; object-fit: contain; margin-top: 1em; margin-bottom: 0.5em; background: #fff; border-radius: 0.5em; border: 1px solid #2196f3; box-shadow: 0 1px 6px rgba(33,150,243,0.07);">
                                @elseif ($user->hasRole('Perawat'))
                                    <div class="cppt-qr-label">TTD Perawat</div>
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($user->name . ' - Perawat - ' . $cppt->created_at) }}" alt="QR Perawat" style="width: 120px; height: 120px; object-fit: contain; margin-top: 1em; margin-bottom: 0.5em; background: #fff; border-radius: 0.5em; border: 1px solid #2196f3; box-shadow: 0 1px 6px rgba(33,150,243,0.07);">
                                @endif
                                <div class="mt-2">
                                    @if ($cppt->dibaca)
                                        <small class="text-success">
                                            <strong>Dibaca oleh:</strong><br>
                                            {{ $cppt->reader ? $cppt->reader->name : 'User tidak ditemukan' }}<br>
                                            <strong>Pada:</strong><br>
                                            {{ \Carbon\Carbon::parse($cppt->waktu_baca)->translatedFormat('d M Y H:i') }}
                                        </small>
                                    @else
                                        <button class="btn btn-success btn-sm btn-mark-read" data-cppt-id="{{ $cppt->id }}">
                                            Tandai Dibaca
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Object (O)</span></td>
                        <td class="cppt-value" style="border: 1px solid #007bff;">{!! nl2br(e($cppt->o)) !!}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Assessment (A)</span></td>
                        <td class="cppt-value" style="border: 1px solid #007bff;">{{ $cppt->a }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Planning (P)</span></td>
                        <td class="cppt-value" style="border: 1px solid #007bff;">{!! nl2br(e($cppt->p)) !!}</td>
                    </tr>
                </table>
            @elseif ($cppt->jenis_dokumen == 2)
                <div class="cppt-entry">
                    <div class="cppt-meta">
                        <div class="font-weight-bold text-muted small">{{ \Carbon\Carbon::parse($cppt->created_at)->translatedFormat('d M Y H:i') }}</div>
                        <div class="display-4">
                            @if ($user && $user->hasRole('Perawat'))
                                P
                            @elseif ($user && $user->hasRole('Dokter'))
                                D
                            @else
                                {{ strtoupper(substr(optional($user)->name ?? '', 0, 1)) }}
                            @endif
                        </div>
                    </div>
                    <div class="cppt-content">
                        <div class="row">
                            <div class="col-md-6"><span class="cppt-label">Situation (S)</span><div class="cppt-value">{{ $cppt->s }}</div></div>
                            <div class="col-md-6"><span class="cppt-label">Background (B)</span><div class="cppt-value">{!! nl2br(e($cppt->o)) !!}</div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><span class="cppt-label">Assessment (A)</span><div class="cppt-value">{{ $cppt->a }}</div></div>
                            <div class="col-md-6"><span class="cppt-label">Recommendation (R)</span><div class="cppt-value">{!! nl2br(e($cppt->p)) !!}</div></div>
                        </div>
                    </div>
                    <div class="cppt-qr">
                        @if ($user)
                            <div class="cppt-qr-label">{{ $user->name }}</div>
                            @if ($user->hasRole('Dokter'))
                                <div class="cppt-qr-label">TTD Dokter</div>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($user->name . ' - Dokter - ' . $cppt->created_at) }}" alt="QR Dokter" style="width: 120px; height: 120px; object-fit: contain; margin-top: 1em; margin-bottom: 0.5em; background: #fff; border-radius: 0.5em; border: 1px solid #2196f3; box-shadow: 0 1px 6px rgba(33,150,243,0.07);">
                            @elseif ($user->hasRole('Perawat'))
                                <div class="cppt-qr-label">TTD Perawat</div>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($user->name . ' - Perawat - ' . $cppt->created_at) }}" alt="QR Perawat" style="width: 120px; height: 120px; object-fit: contain; margin-top: 1em; margin-bottom: 0.5em; background: #fff; border-radius: 0.5em; border: 1px solid #2196f3; box-shadow: 0 1px 6px rgba(33,150,243,0.07);">
                            @endif
                            <div class="mt-2">
                                @if ($cppt->dibaca)
                                    <small class="text-success">
                                        <strong>Dibaca oleh:</strong><br>
                                        {{ $cppt->reader ? $cppt->reader->name : 'User tidak ditemukan' }}<br>
                                        <strong>Pada:</strong><br>
                                        {{ \Carbon\Carbon::parse($cppt->waktu_baca)->translatedFormat('d M Y H:i') }}
                                    </small>
                                @else
                                    <button class="btn btn-success btn-sm btn-mark-read" data-cppt-id="{{ $cppt->id }}">
                                        Tandai Dibaca
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            <hr style="border-color:#2196f3;border-width:3px;opacity:0.5;">
        @empty
            <p class="text-muted text-center">Belum ada catatan CPPT.</p>
        @endforelse

    </div>
</div>

           
</div><!-- container -->


@endsection
@section('scripts')
<script>
$(document).ready(function () {
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

    function refreshCpptHistory(visitationId) {
        $.ajax({
            url: '/erm/cppt/history-json/' + visitationId,
            type: 'GET',
            success: function (res) {
                let html = '';
                if (res.length === 0) {
                    html = '<p class="text-muted text-center">Belum ada catatan CPPT.</p>';
                } else {
                    res.forEach(cppt => {
                        let user = cppt.user;
                        let userInitial = '-';
                        let userRole = 'Unknown';
                        
                        if (user && user.roles) {
                            // Check for Dokter role first (highest priority)
                            if (user.roles.some(role => role.name === 'Dokter')) {
                                userInitial = 'D';
                                userRole = 'Dokter';
                            } 
                            // Check for Perawat role second
                            else if (user.roles.some(role => role.name === 'Perawat')) {
                                userInitial = 'P';
                                userRole = 'Perawat';
                            } 
                            // Default to first letter of name for other roles
                            else {
                                userInitial = user.name?.charAt(0).toUpperCase() || '-';
                                userRole = user.roles[0]?.name || 'Unknown';
                            }
                        } else if (user) {
                            // If no roles loaded, use first letter of name
                            userInitial = user.name?.charAt(0).toUpperCase() || '-';
                        }

                        if (cppt.jenis_dokumen == 1) {
                            // SOAP format with table
                            let readSection = '';
                            if (cppt.dibaca) {
                                let readerName = cppt.reader ? cppt.reader.name : 'User tidak ditemukan';
                                let waktuBaca = new Date(cppt.waktu_baca).toLocaleDateString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                readSection = `
                                    <div class="mt-2">
                                        <small class="text-success">
                                            <strong>Dibaca oleh:</strong><br>
                                            ${readerName}<br>
                                            <strong>Pada:</strong><br>
                                            ${waktuBaca}
                                        </small>
                                    </div>
                                `;
                            } else {
                                readSection = `
                                    <div class="mt-2">
                                        <button class="btn btn-success btn-sm btn-mark-read" data-cppt-id="${cppt.id}">
                                            Tandai Dibaca
                                        </button>
                                    </div>
                                `;
                            }
                            
                            html += `
                                <table class="table table-bordered mb-3" style="table-layout: fixed; width: 100%; border: 1px solid #007bff;">
                                    <tr>
                                        <td rowspan="4" style="width: 180px; border: 1px solid #007bff; text-align: center; vertical-align: middle;">
                                            <div class="font-weight-bold text-muted small">${cppt.formatted_date}</div>
                                            <div class="display-4">${userInitial}</div>
                                        </td>
                                        <td style="width: 150px; border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Subject (S)</span></td>
                                        <td class="cppt-value" style="border: 1px solid #007bff;">${cppt.s}</td>
                                        <td rowspan="4" style="width: 140px; border: 1px solid #007bff; text-align: center; vertical-align: middle;">
                                            <div class="cppt-qr-label">${user ? user.name : ''}</div>
                                            <div class="cppt-qr-label">TTD ${userRole}</div>
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent((user ? user.name : '') + ' - ' + userRole + ' - ' + cppt.created_at)}" alt="QR ${userRole}" style="width: 120px; height: 120px; object-fit: contain; margin-top: 1em; margin-bottom: 0.5em; background: #fff; border-radius: 0.5em; border: 1px solid #2196f3; box-shadow: 0 1px 6px rgba(33,150,243,0.07);">
                                            ${readSection}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Object (O)</span></td>
                                        <td class="cppt-value" style="border: 1px solid #007bff;">${cppt.o.replace(/\n/g, '<br>')}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Assessment (A)</span></td>
                                        <td class="cppt-value" style="border: 1px solid #007bff;">${cppt.a}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #007bff;"><span class="badge badge-pill badge-primary px-3 py-2">Planning (P)</span></td>
                                        <td class="cppt-value" style="border: 1px solid #007bff;">${cppt.p.replace(/\n/g, '<br>')}</td>
                                    </tr>
                                </table>
                            `;
                        } else {
                            // SBAR format with original layout
                            let readSection = '';
                            if (cppt.dibaca) {
                                let readerName = cppt.reader ? cppt.reader.name : 'User tidak ditemukan';
                                let waktuBaca = new Date(cppt.waktu_baca).toLocaleDateString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                readSection = `
                                    <div class="mt-2">
                                        <small class="text-success">
                                            <strong>Dibaca oleh:</strong><br>
                                            ${readerName}<br>
                                            <strong>Pada:</strong><br>
                                            ${waktuBaca}
                                        </small>
                                    </div>
                                `;
                            } else {
                                readSection = `
                                    <div class="mt-2">
                                        <button class="btn btn-success btn-sm btn-mark-read" data-cppt-id="${cppt.id}">
                                            Tandai Dibaca
                                        </button>
                                    </div>
                                `;
                            }
                            
                            html += `
                                <div class="cppt-entry">
                                    <div class="cppt-meta">
                                        <div class="font-weight-bold text-muted small">${cppt.formatted_date}</div>
                                        <div class="display-4">${userInitial}</div>
                                    </div>
                                    <div class="cppt-content">
                                        <div class="row">
                                            <div class="col-md-6"><span class="cppt-label">Situation (S)</span><div class="cppt-value">${cppt.s}</div></div>
                                            <div class="col-md-6"><span class="cppt-label">Background (B)</span><div class="cppt-value">${cppt.o.replace(/\n/g, '<br>')}</div></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6"><span class="cppt-label">Assessment (A)</span><div class="cppt-value">${cppt.a}</div></div>
                                            <div class="col-md-6"><span class="cppt-label">Recommendation (R)</span><div class="cppt-value">${cppt.p.replace(/\n/g, '<br>')}</div></div>
                                        </div>
                                    </div>
                                    <div class="cppt-qr">
                                        <div class="cppt-qr-label">${user ? user.name : ''}</div>
                                        <div class="cppt-qr-label">TTD ${userRole}</div>
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent((user ? user.name : '') + ' - ' + userRole + ' - ' + cppt.created_at)}" alt="QR ${userRole}" style="width: 120px; height: 120px; object-fit: contain; margin-top: 1em; margin-bottom: 0.5em; background: #fff; border-radius: 0.5em; border: 1px solid #2196f3; box-shadow: 0 1px 6px rgba(33,150,243,0.07);">
                                        ${readSection}
                                    </div>
                                </div>
                                <hr style="border-color:#2196f3;border-width:3px;opacity:0.5;">
                            `;
                        }
                    });
                }
                // Temukan dan ganti bagian card-body dalam card Riwayat CPPT
                $('.card:has(strong:contains("Riwayat CPPT")) .card-body').html(html);
            }
        });
    }

    // AJAX Submit - SOAP
    $('#form-cppt-soap').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let visitationId = formData.get('visitation_id');
        // Add konsultasi value from top select
        formData.set('jenis_konsultasi', $('#jenis_konsultasi').val());

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
    $('#form-cppt-sbar').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let visitationId = formData.get('visitation_id');
        // Add konsultasi value from top select
        formData.set('jenis_konsultasi', $('#jenis_konsultasi').val());

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
                button.parent().html(`
                    <small class="text-success">
                        <strong>Dibaca oleh:</strong><br>
                        ${res.reader_name}<br>
                        <strong>Pada:</strong><br>
                        ${res.waktu_baca}
                    </small>
                `);
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

});
</script>
@endsection



@extends('layouts.erm.app')
@section('title', 'ERM | Asesmen Keperawat')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')
<style>
    /* Sembunyikan form wizard sebelum siap */
    #asesmenperawat-form {
        visibility: hidden;
    }

    /* Tampilkan setelah wizard di-init */
    #asesmenperawat-form.wizard-initialized {
        visibility: visible;
    }

    .is-invalid {
    border-color: red !important;    
    }

</style>

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Asesmen</h3>
        <h3 class="mb-0"><strong>Keperawatan</strong></h3>
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
                            <li class="breadcrumb-item active">Asesmen Keperawatan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

@include('erm.partials.card-identitaspasien')
    
    @if(isset($visitation) && isset($visitation->dokter->spesialisasi) && strtolower($visitation->dokter->spesialisasi->nama) === 'estetika')
    @php
        $existingSkincheck = \App\Models\ERM\HasilSkincheck::where('visitation_id', $visitation->id)->latest()->first();
    @endphp
    <div class="card mt-3">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-5">
                    <form id="skincheck-form" enctype="multipart/form-data" onsubmit="return false;">
                        @csrf
                        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                        <input type="hidden" id="skincheck_url_hidden" name="skincheck_url" value="{{ $existingSkincheck->decoded_text ?? '' }}">
                        <input type="hidden" id="skincheck_pasien_id" name="pasien_id" value="{{ $visitation->pasien_id }}">

                        <label class="font-weight-bold d-block mb-1">Hasil Skincheck (QR)</label>
                        <div class="input-group input-group-sm mb-2">
                            <div class="custom-file">
                                <input type="file" accept="image/*" id="qr_file_input" class="custom-file-input">
                                <label class="custom-file-label" for="qr_file_input">Pilih file...</label>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-2">
                            <div class="btn-group btn-group-sm" role="group" aria-label="skincheck-actions">
                                <button type="button" id="check_skincheck" class="btn btn-outline-primary">Check URL</button>
                                <button type="button" id="save_skincheck" class="btn btn-primary">Simpan</button>
                                <button type="button" id="reset_skincheck" class="btn btn-link text-secondary">Reset</button>
                            </div>
                            <a href="#" id="qr_decoded_link" class="btn btn-sm btn-success ml-2" style="display:{{ ($existingSkincheck && $existingSkincheck->url) ? '' : 'none' }};">Lihat Hasil</a>
                        </div>

                        <small class="form-text text-muted mb-2">Upload QR image, lalu klik <strong>Check URL</strong> untuk memeriksa hasil sebelum menyimpan.</small>

                        <div class="d-flex align-items-start">
                            <div id="qr_preview_wrapper" style="display:{{ $existingSkincheck ? 'block' : 'none' }};">
                                <img id="qr_preview" src="{{ $existingSkincheck ? asset('storage/'.$existingSkincheck->qr_image) : '#' }}" alt="QR Preview" class="img-thumbnail" style="width:80px; height:auto;">
                            </div>
                            <div class="ml-3 w-100">
                                <div id="qr_decoded_text" class="small text-muted" style="max-height:64px; overflow:hidden; word-break:break-word;">{{ $existingSkincheck->decoded_text ?? '' }}</div>
                                <div id="qr_status" class="small text-success mt-1"></div>
                                <div id="qr_loading" style="display:none; font-size:13px;">Memproses QR...</div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-7 border-left pl-4">
                    <h6 class="mb-2">Riwayat Skincheck Pasien</h6>
                    <div class="table-responsive" style="max-height:220px; overflow:auto;">
                        <table id="riwayat-skincheck-table" class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width:110px">Waktu</th>
                                    <th style="width:80px">Preview</th>
                                    <th>Decoded</th>
                                    <th style="width:80px">Link</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
                
    <div class="card">
        <div class="card-body">
            <form id="asesmenperawat-form" class="form-wizard-wrapper" action="{{ route('erm.asesmenperawat.store') }}" method="POST">
                @csrf
                <input type="text" id="visitation_id" name="visitation_id" class="form-control mr-2" value="{{ $visitation->id }}" hidden>

                <h3>Pengkajian Keperawatan</h3>
                    <fieldset>
                        <hr>
                        <div class="col-md-12">                           
                            <label><strong>1. Keluhan Utama Pasien</strong></label>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="keluhan_utama">Keluhan Utama</label>
                                    <div class="form-group">
                                    <select class="form-control select2" id="keluhan_utama_select" name="keluhan_utama_select">
                                        <option value="">Pilih Keluhan Utama</option>
                                    </select>
                                </div>
                                    
                                    <textarea class="form-control" id="keluhan_utama" name="keluhan_utama" rows="3">{{ old('keluhan_utama', $dataperawat->keluhan_utama ?? 'Pasien mengeluhkan') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="alasan_kunjungan">Alasan Kunjungan</label>
                                    <input type="text" class="form-control" id="alasan_kunjungan" name="alasan_kunjungan" value="{{ old('alasan_kunjungan', $dataperawat->alasan_kunjungan ?? '') }}">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-12">
                            <label><strong>2. Keadaan Umum Pasien</strong></label>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-group">
                                        <label for="kesadaran">Kesadaran</label>
                                        <input type="text" class="form-control" id="kesadaran" name="kesadaran" value="{{ old('kesadaran', $dataperawat->kesadaran ?? '') }}">
                                    </div>                              
                                </div> 
                            </div> 
                            <div class="col-md-6">
                                <div class="form-row">
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="td">TD (mmHg)</label>
                                        <input type="text" class="form-control" id="td" name="td" placeholder="Contoh: 120/80" value="{{ old('td', $dataperawat->td ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="nadi">Nadi (x/mnt)</label>
                                        <input type="text" class="form-control" id="nadi" name="nadi" placeholder="Contoh: 75" value="{{ old('nadi', $dataperawat->nadi ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="rr">RR (x/mnt)</label>
                                        <input type="text" class="form-control" id="rr" name="rr" placeholder="Contoh: 18" value="{{ old('rr', $dataperawat->rr ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="suhu">Suhu (°C)</label>
                                        <input type="text" class="form-control" id="suhu" name="suhu" placeholder="Contoh: 36.5" value="{{ old('suhu', $dataperawat->suhu ?? '') }}">
                                    </div>
                                </div>
                            </div> 
                        </div>
                        <div class="row">
                            <!-- Left Side: Input -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="riwayat_psikososial">Riwayat Psikososial</label>
                                    <input type="text" class="form-control" id="riwayat_psikososial" name="riwayat_psikososial" value="{{ old('riwayat_psikososial', $dataperawat->riwayat_psikososial ?? '') }}">
                                </div>
                            </div>

                            <!-- Right Side: Radio Buttons -->
                            <div class="col-md-6 d-flex align-items-center">
                                <label class="mr-3 mb-0" for="hubunganBaik">Hubungan dengan keluarga</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="hubungan_keluarga" id="hubunganBaik" value="Baik"
                                            {{ old('hubungan_keluarga', $dataperawat->hubungan_keluarga ?? '') == 'Baik' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hubunganBaik">Baik</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="hubungan_keluarga" id="hubunganTidakBaik" value="Tidak Baik"
                                            {{ old('hubungan_keluarga', $dataperawat->hubungan_keluarga ?? '') == 'Tidak Baik' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hubunganTidakBaik">Tidak Baik</label>
                                    </div>
                                </div>
                            </div>
                        </div>     
                        <hr>
                        <div class="col-md-12">
                            <label><strong>3. Skrining Gizi Pasien</strong></label>
                        </div>
                        <div class="row">
                            
                            
                            <div class="col-md-12">
                                <label>Asesmen Nutrisi Pasien Dewasa <em>(Malnutrition Universal Scoring Treatment)</em></label>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="tb">TB :</label>
                                    <input type="text" class="form-control" id="tb" name="tb" placeholder="Cm" value="{{ old('tb', $dataperawat->tb ?? '') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bb">BB :</label>
                                    <input type="text" class="form-control" id="bb" name="bb" placeholder="Kg" value="{{ old('bb', $dataperawat->bb ?? '') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="lla">LLA :</label>
                                    <input type="text" class="form-control" id="lla" name="lla" placeholder="Cm" value="{{ old('lla', $dataperawat->lla ?? '') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="diet">Diet :</label>
                                    <input type="text" class="form-control" id="diet" name="diet" value="{{ old('diet', $dataperawat->diet ?? '') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="porsi">Porsi :</label>
                                    <input type="text" class="form-control" id="porsi" name="porsi" value="{{ old('porsi', $dataperawat->porsi ?? '') }}">
                                </div>
                            </div>
                        </div>
                        <!-- Tabel Penilaian dan Skor -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <thead class="text-center">
                                        <tr>
                                            <th>Penilaian</th>
                                            <th style="width: 150px;">Skor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>IMT</td>
                                            <td><input type="text" class="form-control" name="imt" value="{{ old('imt', $dataperawat->imt ?? '') }}"></td>
                                        </tr>
                                        <tr>
                                            <td>Presentase Kehilangan BB yang tidak diharapkan</td>
                                            <td><input type="text" class="form-control" name="presentase" value="{{ old('presentase', $dataperawat->presentase ?? '') }}"></td>
                                        </tr>
                                        <tr>
                                            <td>Efek Dari Penyakit Yang Diderita / 5 Hari Tidak Mendapat Asupan Nutrisi</td>
                                            <td><input type="text" class="form-control" name="efek" value="{{ old('efek', $dataperawat->efek ?? '') }}"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-12">
                            <label><strong>4. Skrining Nyeri Pasien</strong></label>
                        </div> 
                        <div class="row">
                            <div class="col-md-12">
                                
                                <div class="form-group">
                                    <label>Nyeri:</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="nyeri" id="nyeri_tidak" value="Tidak"
                                            {{ old('nyeri', $dataperawat->nyeri ?? '') == 'Tidak' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="nyeri_tidak">Tidak</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="nyeri" id="nyeri_ya" value="Ya"
                                            {{ old('nyeri', $dataperawat->nyeri ?? '') == 'Ya' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="nyeri_ya">Ya</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Asesmen Nyeri Dengan:</label>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label for="p">P:</label>
                                            <input type="text" class="form-control" id="p" name="p" value="{{ old('p', $dataperawat->p ?? '') }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="q">Q:</label>
                                            <input type="text" class="form-control" id="q" name="q" value="{{ old('q', $dataperawat->q ?? '') }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="r">R:</label>
                                            <input type="text" class="form-control" id="r" name="r" value="{{ old('r', $dataperawat->r ?? '') }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="t">T:</label>
                                            <input type="text" class="form-control" id="t" name="t" value="{{ old('t', $dataperawat->t ?? '') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Onset:</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="onset" id="onset_akut" value="Akut"
                                            {{ old('onset', $dataperawat->onset ?? '') == 'Akut' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="onset_akut">Akut</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="onset" id="onset_kronik" value="Kronik"
                                            {{ old('onset', $dataperawat->onset ?? '') == 'Kronik' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="onset_kronik">Kronik</label>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-md-2">
                                        <label for="skor">Skor:</label>
                                        <input type="text" class="form-control" id="skor" name="skor" value="{{ old('skor', $dataperawat->skor ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="kategori">Kategori:</label>
                                        <input type="text" class="form-control" id="kategori" name="kategori" value="{{ old('kategori', $dataperawat->kategori ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-12">
                            <label><strong>5. Risiko Jatuh Rawat Jalan (Get Up And Go)</strong></label>
                        </div>                            
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-group mt-2">
                                    <div class="form-check form-check-inline ms-3">
                                        <input class="form-check-input" type="radio" name="kategori_risja" id="tidak_beresiko" value="tidak_beresiko"
                                            {{ old('kategori_risja', $dataperawat->kategori_risja ?? '') == 'tidak_beresiko' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tidak_beresiko">Tdk Beresiko (tdk ditemukan a dan b)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="kategori_risja" id="risiko_rendah" value="risiko_rendah"
                                            {{ old('kategori_risja', $dataperawat->kategori_risja ?? '') == 'risiko_rendah' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="risiko_rendah">Risiko Rendah (ditemukan a atau b)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="kategori_risja" id="risiko_tinggi" value="risiko_tinggi"
                                            {{ old('kategori_risja', $dataperawat->kategori_risja ?? '') == 'risiko_tinggi' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="risiko_tinggi">Risiko Tinggi (a dan b ditemukan)</label>
                                    </div>
                                </div>
                                
                                
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-12">
                            <label><strong>6. Status Psikologis</strong></label>
                        </div> 
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-group mt-2">
                                    <div class="form-check form-check-inline ms-3">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="tenang" value="tenang"
                                            {{ old('status_fungsional', $dataperawat->status_fungsional ?? '') == 'tenang' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tenang">Tenang</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="cemas" value="cemas"
                                            {{ old('status_fungsional', $dataperawat->status_fungsional ?? '') == 'cemas' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="cemas">Cemas</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="takut" value="takut"
                                            {{ old('status_fungsional', $dataperawat->status_fungsional ?? '') == 'takut' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="takut">Takut</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="marah" value="marah"
                                            {{ old('status_fungsional', $dataperawat->status_fungsional ?? '') == 'marah' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="marah">Marah</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="sedih" value="sedih"
                                            {{ old('status_fungsional', $dataperawat->status_fungsional ?? '') == 'sedih' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="sedih">Sedih</label>
                                    </div>
                                </div>
                            </div>
                        </div>              
                    </fieldset>       
                <h3>Masalah Keperawatan</h3>
                    <fieldset>
                        @php
    if (old('masalah_keperawatan')) {
        $oldMasalah = old('masalah_keperawatan');
    } elseif (isset($dataperawat->masalah_keperawatan)) {
        $oldMasalah = is_string($dataperawat->masalah_keperawatan)
            ? json_decode($dataperawat->masalah_keperawatan, true)
            : $dataperawat->masalah_keperawatan;
    } else {
        $oldMasalah = [];
    }

    $lainLainText = old('lain_lain_text', $dataperawat->lain_lain_text ?? '');
@endphp

<div class="row mt-3">
    <div class="col-md-12">
        <div class="row mt-2">
            <div class="col-md-4">
                <label><strong>Respirasi</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Aspirasi" id="aspirasi"
                        {{ in_array('Risiko Aspirasi', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="aspirasi">Risiko Aspirasi</label>
                </div>

                <label><strong>Sirkulasi</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Perfusi Perifer Tidak Efektif" id="perfusi"
                        {{ in_array('Risiko Perfusi Perifer Tidak Efektif', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="perfusi">Risiko Perfusi Perifer Tidak Efektif</label>
                </div>

                <label><strong>Nutrisi dan Cairan</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Diare" id="diare"
                        {{ in_array('Diare', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="diare">Diare</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ikterik Neonatus" id="ikterik"
                        {{ in_array('Ikterik Neonatus', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="ikterik">Ikterik Neonatus</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ketidakstabilan Kadar Glukosa Darah" id="glukosa"
                        {{ in_array('Ketidakstabilan Kadar Glukosa Darah', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="glukosa">Ketidakstabilan Kadar Glukosa Darah</label>
                </div>

                <label><strong>Eliminasi</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Konstipasi" id="konstipasi"
                        {{ in_array('Konstipasi', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="konstipasi">Konstipasi</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Retensi Urin" id="retensi"
                        {{ in_array('Retensi Urin', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="retensi">Retensi Urin</label>
                </div>

                <label><strong>Reproduksi dan Seksualitas</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Kesiapan Persalinan" id="persalinan"
                        {{ in_array('Kesiapan Persalinan', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="persalinan">Kesiapan Persalinan</label>
                </div>
            </div>

            <div class="col-md-4">
                <label><strong>Nyeri dan Kenyamanan</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Gangguan Rasa Nyaman" id="rasa_nyaman"
                        {{ in_array('Gangguan Rasa Nyaman', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="rasa_nyaman">Gangguan Rasa Nyaman</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ketidaknyamanan Pasca Partum" id="pasca_partum"
                        {{ in_array('Ketidaknyamanan Pasca Partum', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="pasca_partum">Ketidaknyamanan Pasca Partum</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Nausea" id="nausea"
                        {{ in_array('Nausea', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="nausea">Nausea</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Nyeri Akut" id="nyeri_akut"
                        {{ in_array('Nyeri Akut', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="nyeri_akut">Nyeri Akut</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Nyeri Kronis" id="nyeri_kronis"
                        {{ in_array('Nyeri Kronis', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="nyeri_kronis">Nyeri Kronis</label>
                </div>

                <label><strong>Integritas Ego</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ansietas" id="ansietas"
                        {{ in_array('Ansietas', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="ansietas">Ansietas</label>
                </div>

                <label><strong>Pertumbuhan dan Perkembangan</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Gangguan Tumbuh Kembang" id="tumbuh_kembang"
                        {{ in_array('Gangguan Tumbuh Kembang', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="tumbuh_kembang">Gangguan Tumbuh Kembang</label>
                </div>

                <label><strong>Penyuluhan dan Pembelajaran</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Defisit Pengetahuan" id="defisit_pengetahuan"
                        {{ in_array('Defisit Pengetahuan', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="defisit_pengetahuan">Defisit Pengetahuan</label>
                </div>
            </div>

            <div class="col-md-4">
                <label><strong>Interaksi Sosial</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Gangguan Komunikasi Verbal" id="komunikasi_verbal"
                        {{ in_array('Gangguan Komunikasi Verbal', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="komunikasi_verbal">Gangguan Komunikasi Verbal</label>
                </div>

                <label><strong>Keamanan dan Proteksi</strong></label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Hipertemia / Hipotermia" id="hiper_hipo"
                        {{ in_array('Hipertemia / Hipotermia', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="hiper_hipo">Hipertemia / Hipotermia</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Perlambatan Pemulihan Pasca Bedah" id="pasca_bedah"
                        {{ in_array('Perlambatan Pemulihan Pasca Bedah', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="pasca_bedah">Perlambatan Pemulihan Pasca Bedah</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Cedera" id="cedera"
                        {{ in_array('Risiko Cedera', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="cedera">Risiko Cedera</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Jatuh" id="jatuh"
                        {{ in_array('Risiko Jatuh', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="jatuh">Risiko Jatuh</label>
                </div>

                <label><strong>Lain-lain</strong></label>
                <div class="form-check d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Lain-lain" id="lain_lain"
                        {{ in_array('Lain-lain', (array)$oldMasalah) ? 'checked' : '' }}>
                    <label class="form-check-label" for="lain_lain">Lain-lain</label>
                    <input type="text" name="lain_lain_text" class="form-control" style="width: 70%" placeholder="Sebutkan" value="{{ $lainLainText }}">
                </div>
            </div>
        </div>
    </div>
</div>


                    </fieldset>
            </form>
        </div>
    </div>
</div><!-- container -->


@endsection
@section('scripts')
<script>  

   $(document).ready(function () {
    var wizard = $("#asesmenperawat-form").steps({
    headerTag: "h3",
    bodyTag: "fieldset",
    transitionEffect: "slide",
    onStepChanged: function () {},
    onInit: function () {
        $('#asesmenperawat-form').addClass('wizard-initialized');
        },
    onStepChanging: function (event, currentIndex, newIndex) {
        var currentStep = $('.body:eq(' + currentIndex + ')');
        var isValid = true;

        currentStep.find('input, select, textarea').each(function () {
            if (!this.checkValidity()) {
                isValid = false;
                $(this).addClass('is-invalid');

                // ✅ Tambahkan class ke Select2
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
                }
            } else {
                $(this).removeClass('is-invalid');

                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                }
            }
        });

        return isValid; // ⬅️ Hanya lanjut step jika valid
    },
    onFinished: function (event, currentIndex) {
            $('#asesmenperawat-form').submit(); // 👈 THIS enables actual form submission
        }
    });
    
    $('.select2').select2({ width: '100%' });


    // Append selected value to Keluhan Utama textarea
    $('#keluhan_utama_select').select2({
        width: '100%',
        ajax: {
            url: '{{ route("keluhan-utama.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,// search term
                    visitation_id: $('#visitation_id').val()
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function (item) {
                        return { id: item.id, text: item.keluhan };
                    })
                };
            },
            cache: true
        },
        placeholder: 'Pilih Keluhan Utama',
        minimumInputLength: 1
    });

    $('#keluhan_utama_select').on('select2:select', function (e) {
        const selectedValue = e.params.data.text; // Get the selected option's text
        const keluhanUtamaField = $('#keluhan_utama'); // Reference the textarea

        
        if (selectedValue) {
            const currentText = keluhanUtamaField.val().trim(); // Get current textarea value and trim whitespace
            const newText = currentText.endsWith('mengeluhkan') || currentText === '' 
                ? `${currentText} ${selectedValue}` // Append without comma if default text is present
                : `${currentText}, ${selectedValue}`; // Append with comma otherwise
            keluhanUtamaField.val(newText); // Update the textarea value
        }

        // Clear the Select2 dropdown to allow selecting another option
        $(this).val(null).trigger('change');
        
    });

    // VALIDASI INPUT
    $('#nadi').on('blur', function () {
        const value = parseInt($(this).val(), 10);
        if (isNaN(value) || value < 40 || value > 140) {
            Swal.fire({
                icon: 'warning',
                title: 'Nilai tidak valid',
                text: 'Silakan masukkan angka antara 40 hingga 140.',
            }).then(() => {
                $(this).val('').focus();
            });
        }
    });

    $('#rr').on('blur', function () {
        const value = parseInt($(this).val(), 10);
        if (isNaN(value) || value < 15 || value > 60) {
            Swal.fire({
                icon: 'warning',
                title: 'Nilai tidak valid',
                text: 'Silakan masukkan angka antara 15 hingga 60.',
            }).then(() => {
                $(this).val('').focus();
            });
        }
    });

    $('#suhu').on('blur', function () {
        const value = parseFloat($(this).val());
        if (isNaN(value) || value < 35 || value > 41) {
            Swal.fire({
                icon: 'warning',
                title: 'Nilai tidak valid',
                text: 'Silakan masukkan suhu antara 35 hingga 41 derajat Celcius.',
            }).then(() => {
                $(this).val('').focus();
            });
        }
    });
    $('#td').on('blur', function () {
        const value = $(this).val().trim();
        const parts = value.split('/');

        if (parts.length !== 2) {
            return showInvalidTD();
        }

        const systolic = parseInt(parts[0], 10);
        const diastolic = parseInt(parts[1], 10);

        if (
            isNaN(systolic) || isNaN(diastolic) ||
            systolic < 70 || systolic > 200 ||
            diastolic < 50 || diastolic > 120
        ) {
            return showInvalidTD();
        }

        function showInvalidTD() {
            Swal.fire({
                icon: 'warning',
                title: 'Nilai tidak valid',
                text: 'Masukkan tensi dalam format benar (contoh: 120/80), dengan nilai sistolik antara 70–200 dan diastolik antara 50–120.',
            }).then(() => {
                $('#td').val('').focus();
            });
        }
    });


    // Saat tombol modal alergi ditekan
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    // Toggle semua bagian tergantung status
        var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val(); // Ambil status yang dipilih awalnya
    
    // Jika status alergi adalah 'ada', tampilkan semua elemen yang terkait
    if (initialStatusAlergi === 'ada') {
        $('#inputKataKunciWrapper').show();
        $('#selectAlergiWrapper').show();
        $('#selectKandunganWrapper').show();
    } else {
        // Jika tidak, sembunyikan elemen-elemen tersebut
        $('#inputKataKunciWrapper').hide();
        $('#selectAlergiWrapper').hide();
        $('#selectKandunganWrapper').hide();
    }
    $('input[name="statusAlergi"]').on('change', function () {
        if ($(this).val() === 'ada') {
            $('#inputKataKunciWrapper').show();
            $('#selectAlergiWrapper').show();
            $('#selectKandunganWrapper').show();
        } else {
            $('#inputKataKunciWrapper').hide();
            $('#selectAlergiWrapper').hide();
            $('#selectKandunganWrapper').hide();
            $('#inputKataKunci').val('');
            $('#selectAlergi, #selectKandungan').val(null).trigger('change');
        }
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $('#asesmenperawat-form').on('submit', function (e) {
        e.preventDefault();

        let form = $(this);
        let formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json', // ✅ ensure it's parsed
            success: function (response) {
                Swal.fire({
                    title: 'Sukses!',
                     html: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            },
            error: function (xhr) {
                let errors = xhr.responseJSON?.errors;
                let errorMsg = "Terjadi kesalahan saat mengirim data.";

                if (errors) {
                    errorMsg = Object.values(errors).map(err => `• ${err}`).join('<br>');
                }

                Swal.fire({
                    title: 'Gagal!',
                    html: errorMsg,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

});
</script>

<script>
// JS to handle standalone Hasil Skincheck form (preview + server-side decode via Check URL)
document.addEventListener('DOMContentLoaded', function () {
    var fileInput = document.getElementById('qr_file_input');
    var preview = document.getElementById('qr_preview');
    var previewWrapper = document.getElementById('qr_preview_wrapper');
    var decodedLink = document.getElementById('qr_decoded_link');
    var statusEl = document.getElementById('qr_status');
    var loading = document.getElementById('qr_loading');
    var saveBtn = document.getElementById('save_skincheck');
    var resetBtn = document.getElementById('reset_skincheck');
    var hiddenUrl = document.getElementById('skincheck_url_hidden');

    var serverDecoded = null; // result from server-side decode when using Check URL
    var lastFile = null;

    if (!fileInput) return;

    fileInput.addEventListener('change', function (e) {
        var file = e.target.files[0];
        lastFile = file || null;
        serverDecoded = null;
        statusEl.textContent = '';
        decodedLink.textContent = '';
        hiddenUrl.value = '';

        if (!file) {
            previewWrapper.style.display = 'none';
            return;
        }

        // update custom file label (Bootstrap 4 custom-file)
        try {
            var label = document.querySelector('label[for="qr_file_input"].custom-file-label');
            if (!label) label = document.querySelector('.custom-file-label');
            if (label) label.textContent = file.name;
        } catch (e) {}

        // show preview only; do NOT attempt client-side decode
        var reader = new FileReader();
        reader.onload = function (ev) {
            preview.src = ev.target.result;
            previewWrapper.style.display = 'block';
            var decTextEl = document.getElementById('qr_decoded_text');
            if (decTextEl) decTextEl.textContent = '';
        };
        reader.readAsDataURL(file);
    });

    var checkBtn = document.getElementById('check_skincheck');

    // Check button: upload file to decode endpoint and show decoded text/url (no DB save)
    if (checkBtn) {
        checkBtn.addEventListener('click', function () {
            if (!lastFile) {
                statusEl.style.color = 'red';
                statusEl.textContent = 'Pilih file QR terlebih dahulu.';
                return;
            }

            var form = new FormData();
            form.append('qr_image', lastFile);

            loading.style.display = 'block';
            statusEl.textContent = '';

            $.ajax({
                url: '{{ route('erm.hasil_skincheck.decode') }}',
                method: 'POST',
                data: form,
                processData: false,
                contentType: false,
                success: function (res) {
                    loading.style.display = 'none';
                    serverDecoded = res.decoded_text || null;
                    if (serverDecoded) {
                        document.getElementById('qr_decoded_text').textContent = serverDecoded;
                        if (res.url) {
                            decodedLink.href = res.url;
                            decodedLink.style.display = '';
                            decodedLink.className = 'btn btn-sm btn-success ml-2';
                            decodedLink.textContent = 'Lihat Hasil';
                        } else {
                            decodedLink.href = '#';
                            decodedLink.style.display = 'none';
                            decodedLink.textContent = '';
                            decodedLink.className = 'btn btn-sm btn-success ml-2';
                        }
                        statusEl.style.color = 'green';
                        statusEl.textContent = 'Decoded by server. You can save the skincheck.';
                        hiddenUrl.value = serverDecoded;
                    } else {
                        statusEl.style.color = 'orange';
                        statusEl.textContent = 'Server could not decode the image.';
                        hiddenUrl.value = '';
                        decodedLink.href = '#';
                        decodedLink.style.display = 'none';
                        decodedLink.textContent = '';
                        decodedLink.className = 'btn btn-sm btn-success ml-2';
                    }
                },
                error: function (xhr) {
                    loading.style.display = 'none';
                    statusEl.style.color = 'red';
                    var msg = 'Gagal mendecode di server.';
                    if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                    statusEl.textContent = msg;
                }
            });
        });
    }

    // Save button: upload the image and decoded text (if any)
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            if (!lastFile) {
                statusEl.style.color = 'red';
                statusEl.textContent = 'Pilih file QR terlebih dahulu.';
                return;
            }

            var form = new FormData();
                form.append('visitation_id', '{{ $visitation->id }}');
                form.append('pasien_id', document.getElementById('skincheck_pasien_id')?.value || '');
            form.append('qr_image', lastFile);
            // prefer serverDecoded (from Check URL) if available
            if (serverDecoded) {
                form.append('decoded_text', serverDecoded);
            }

            loading.style.display = 'block';
            statusEl.textContent = '';

            $.ajax({
                url: '{{ route('erm.hasil_skincheck.store') }}',
                method: 'POST',
                data: form,
                processData: false,
                contentType: false,
                success: function (res) {
                    loading.style.display = 'none';
                    statusEl.style.color = 'green';
                    statusEl.textContent = 'Tersimpan ke Hasil Skincheck.';
                    if (res.data?.url) {
                        decodedLink.href = res.data.url;
                        decodedLink.textContent = 'Lihat Hasil';
                        decodedLink.style.display = '';
                        decodedLink.className = 'btn btn-sm btn-success ml-2';
                    }
                    Swal.fire({ title: 'Sukses', text: res.message || 'Hasil skincheck tersimpan', icon: 'success' });
                    // refresh riwayat table if DataTable is present, otherwise do a full reload
                    try {
                        if ($.fn.DataTable && $('#riwayat-skincheck-table').length) {
                            var dt = $('#riwayat-skincheck-table').DataTable();
                            if (dt && dt.ajax) {
                                dt.ajax.reload(null, false);
                            } else {
                                setTimeout(function () { location.reload(); }, 700);
                            }
                        } else {
                            setTimeout(function () { location.reload(); }, 700);
                        }
                    } catch (e) {
                        setTimeout(function () { location.reload(); }, 700);
                    }
                },
                error: function (xhr) {
                    loading.style.display = 'none';
                    statusEl.style.color = 'red';
                    var msg = 'Gagal menyimpan hasil skincheck.';
                    if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                    statusEl.textContent = msg;
                }
            });
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            fileInput.value = null;
            if (previewWrapper) previewWrapper.style.display = 'none';
            if (preview) preview.src = '#';
            if (decodedLink) decodedLink.href = '#';
            if (decodedLink) decodedLink.textContent = '';
            if (decodedLink) decodedLink.style.display = 'none';
            if (statusEl) statusEl.textContent = '';
            if (hiddenUrl) hiddenUrl.value = '';
            lastFile = null; serverDecoded = null;
        });
    }
});
</script>

<script>
    // Initialize DataTable for riwayat (AJAX-backed)
    (function () {
        $(function () {
            var tableEl = $('#riwayat-skincheck-table');
            var pasienId = $('#skincheck_pasien_id').val();
            if (!pasienId) {
                // no pasien_id available — show empty placeholder
                tableEl.find('tbody').html('<tr><td colspan="4" class="small text-muted">Belum ada pasien terpilih.</td></tr>');
                return;
            }

            if (tableEl.length && $.fn.DataTable) {
                tableEl.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('erm.hasil_skincheck.riwayat') }}',
                        type: 'GET',
                        data: function (d) {
                            d.pasien_id = pasienId;
                        }
                    },
                    pageLength: 5,
                    searching: false,
                    info: false,
                    ordering: true,
                    order: [[0, 'desc']],
                    columns: [
                        { data: 'created_at', name: 'created_at' },
                        {
                            data: 'qr_image',
                            name: 'qr_image',
                            render: function (data) {
                                if (data) return '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-qr" data-src="' + data + '">Lihat QR</button>';
                                return '-';
                            }
                        },
                        {
                            data: 'decoded_text',
                            name: 'decoded_text',
                            render: function (data) {
                                var txt = data || '';
                                var escaped = $('<div>').text(txt).html();
                                return '<div class="small text-truncate" style="max-width:360px">' + escaped + '</div>';
                            }
                        },
                        {
                            data: 'url',
                            name: 'url',
                            render: function (data) {
                                if (data) return '<a href="' + data + '" target="_blank" class="btn btn-sm btn-outline-primary">Buka</a>';
                                return '<span class="small text-muted">-</span>';
                            }
                        }
                    ],
                    columnDefs: [
                        { orderable: false, targets: [1, 3] }
                    ]
                });
            }

            // delegated handler for Lihat QR buttons
            $(document).on('click', '.btn-view-qr', function (e) {
                e.preventDefault();
                var src = $(this).data('src');
                if (src) {
                    $('#modalQrImage').attr('src', src);
                    $('#modalQrPreview').modal('show');
                }
            });
        });
    })();
</script>

@endsection

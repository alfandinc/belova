@extends('layouts.erm.app')
@section('title', 'ERM | Asesmen Keperawat')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

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
                
    @include('erm.partials.form-theme')
    @php
        $radioChecked = fn (string $field, string $value) => old($field, $dataperawat->$field ?? '') == $value ? 'checked' : '';
        if (old('masalah_keperawatan')) {
            $oldMasalah = old('masalah_keperawatan');
        } elseif (isset($dataperawat->masalah_keperawatan)) {
            $oldMasalah = is_string($dataperawat->masalah_keperawatan)
                ? json_decode($dataperawat->masalah_keperawatan, true)
                : $dataperawat->masalah_keperawatan;
        } else {
            $oldMasalah = [];
        }
        $oldMasalah = (array) $oldMasalah;
        $lainLainText = old('lain_lain_text', $dataperawat->lain_lain_text ?? '');
        // Masalah keperawatan per domain (value = stored text, id = checkbox id)
        $masalahGroups = [
            'Respirasi' => ['aspirasi' => 'Risiko Aspirasi'],
            'Sirkulasi' => ['perfusi' => 'Risiko Perfusi Perifer Tidak Efektif'],
            'Nutrisi dan Cairan' => ['diare' => 'Diare', 'ikterik' => 'Ikterik Neonatus', 'glukosa' => 'Ketidakstabilan Kadar Glukosa Darah'],
            'Eliminasi' => ['konstipasi' => 'Konstipasi', 'retensi' => 'Retensi Urin'],
            'Reproduksi dan Seksualitas' => ['persalinan' => 'Kesiapan Persalinan'],
            'Nyeri dan Kenyamanan' => ['rasa_nyaman' => 'Gangguan Rasa Nyaman', 'pasca_partum' => 'Ketidaknyamanan Pasca Partum', 'nausea' => 'Nausea', 'nyeri_akut' => 'Nyeri Akut', 'nyeri_kronis' => 'Nyeri Kronis'],
            'Integritas Ego' => ['ansietas' => 'Ansietas'],
            'Pertumbuhan dan Perkembangan' => ['tumbuh_kembang' => 'Gangguan Tumbuh Kembang'],
            'Penyuluhan dan Pembelajaran' => ['defisit_pengetahuan' => 'Defisit Pengetahuan'],
            'Interaksi Sosial' => ['komunikasi_verbal' => 'Gangguan Komunikasi Verbal'],
            'Keamanan dan Proteksi' => ['hiper_hipo' => 'Hipertemia / Hipotermia', 'pasca_bedah' => 'Perlambatan Pemulihan Pasca Bedah', 'cedera' => 'Risiko Cedera', 'jatuh' => 'Risiko Jatuh'],
        ];
    @endphp

    <form id="asesmenperawat-form" class="erm-form" action="{{ route('erm.asesmenperawat.store') }}" method="POST" style="{{ \App\Support\ClinicTheme::cssVars('ef') }}">
        @csrf
        <input type="hidden" id="visitation_id" name="visitation_id" value="{{ $visitation->id }}">

        {{-- 1. Keluhan --}}
        <section class="erm-section">
            <div class="erm-section-head">
                <span class="erm-section-num">1</span>
                <h4 class="erm-section-title">Keluhan Utama<span class="erm-section-sub">Pilih dari daftar untuk menambahkan ke keluhan</span></h4>
            </div>
            <div class="row">
                <div class="col-lg-7">
                    <div class="form-group">
                        <label for="keluhan_utama">Keluhan Utama</label>
                        <select class="form-control select2" id="keluhan_utama_select" name="keluhan_utama_select">
                            <option value="">Cari keluhan utama...</option>
                        </select>
                        <textarea class="form-control mt-2" id="keluhan_utama" name="keluhan_utama" rows="3">{{ old('keluhan_utama', $dataperawat->keluhan_utama ?? 'Pasien mengeluhkan') }}</textarea>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="form-group">
                        <label for="alasan_kunjungan">Alasan Kunjungan</label>
                        <input type="text" class="form-control" id="alasan_kunjungan" name="alasan_kunjungan" value="{{ old('alasan_kunjungan', $dataperawat->alasan_kunjungan ?? '') }}">
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Keadaan umum + tanda vital --}}
        <section class="erm-section">
            <div class="erm-section-head">
                <span class="erm-section-num">2</span>
                <h4 class="erm-section-title">Keadaan Umum &amp; Tanda Vital</h4>
            </div>
            <div class="row">
                <div class="col-md-6 col-xl-4">
                    <div class="form-group">
                        <label for="kesadaran">Kesadaran</label>
                        <input type="text" class="form-control" id="kesadaran" name="kesadaran" placeholder="Contoh: Compos Mentis" value="{{ old('kesadaran', $dataperawat->kesadaran ?? '') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="form-group">
                        <label for="td">Tekanan Darah</label>
                        <div class="erm-unit"><input type="text" class="form-control" id="td" name="td" placeholder="120/80" value="{{ old('td', $dataperawat->td ?? '') }}"><span>mmHg</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="form-group">
                        <label for="nadi">Nadi</label>
                        <div class="erm-unit"><input type="text" class="form-control" id="nadi" name="nadi" placeholder="75" value="{{ old('nadi', $dataperawat->nadi ?? '') }}"><span>x/mnt</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="form-group">
                        <label for="rr">Respirasi</label>
                        <div class="erm-unit"><input type="text" class="form-control" id="rr" name="rr" placeholder="18" value="{{ old('rr', $dataperawat->rr ?? '') }}"><span>x/mnt</span></div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="form-group">
                        <label for="suhu">Suhu</label>
                        <div class="erm-unit"><input type="text" class="form-control" id="suhu" name="suhu" placeholder="36.5" value="{{ old('suhu', $dataperawat->suhu ?? '') }}"><span>°C</span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="riwayat_psikososial">Riwayat Psikososial</label>
                        <input type="text" class="form-control" id="riwayat_psikososial" name="riwayat_psikososial" value="{{ old('riwayat_psikososial', $dataperawat->riwayat_psikososial ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="d-block">Hubungan dengan Keluarga</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="hubungan_keluarga" id="hubunganBaik" value="Baik" {{ $radioChecked('hubungan_keluarga', 'Baik') }}>
                            <label class="form-check-label" for="hubunganBaik">Baik</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="hubungan_keluarga" id="hubunganTidakBaik" value="Tidak Baik" {{ $radioChecked('hubungan_keluarga', 'Tidak Baik') }}>
                            <label class="form-check-label" for="hubunganTidakBaik">Tidak Baik</label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row">
            {{-- 3. Skrining gizi --}}
            <div class="col-xl-6">
                <section class="erm-section">
                    <div class="erm-section-head">
                        <span class="erm-section-num">3</span>
                        <h4 class="erm-section-title">Skrining Gizi<span class="erm-section-sub">Asesmen nutrisi pasien dewasa (<em>Malnutrition Universal Screening Tool</em>)</span></h4>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="tb">Tinggi Badan</label>
                                <div class="erm-unit"><input type="text" class="form-control" id="tb" name="tb" value="{{ old('tb', $dataperawat->tb ?? '') }}"><span>cm</span></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="bb">Berat Badan</label>
                                <div class="erm-unit"><input type="text" class="form-control" id="bb" name="bb" value="{{ old('bb', $dataperawat->bb ?? '') }}"><span>kg</span></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="lla">LLA</label>
                                <div class="erm-unit"><input type="text" class="form-control" id="lla" name="lla" value="{{ old('lla', $dataperawat->lla ?? '') }}"><span>cm</span></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="diet">Diet</label>
                                <input type="text" class="form-control" id="diet" name="diet" value="{{ old('diet', $dataperawat->diet ?? '') }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="porsi">Porsi</label>
                                <input type="text" class="form-control" id="porsi" name="porsi" value="{{ old('porsi', $dataperawat->porsi ?? '') }}">
                            </div>
                        </div>
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr><th>Penilaian</th><th style="width: 130px;">Skor</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>IMT</td><td><input type="text" class="form-control" name="imt" value="{{ old('imt', $dataperawat->imt ?? '') }}"></td></tr>
                            <tr><td>Persentase kehilangan BB yang tidak diharapkan</td><td><input type="text" class="form-control" name="presentase" value="{{ old('presentase', $dataperawat->presentase ?? '') }}"></td></tr>
                            <tr><td>Efek dari penyakit yang diderita / 5 hari tidak mendapat asupan nutrisi</td><td><input type="text" class="form-control" name="efek" value="{{ old('efek', $dataperawat->efek ?? '') }}"></td></tr>
                        </tbody>
                    </table>
                </section>
            </div>

            {{-- 4. Skrining nyeri --}}
            <div class="col-xl-6">
                <section class="erm-section">
                    <div class="erm-section-head">
                        <span class="erm-section-num">4</span>
                        <h4 class="erm-section-title">Skrining Nyeri</h4>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="d-block">Nyeri</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="nyeri" id="nyeri_tidak" value="Tidak" {{ $radioChecked('nyeri', 'Tidak') }}>
                                    <label class="form-check-label" for="nyeri_tidak">Tidak</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="nyeri" id="nyeri_ya" value="Ya" {{ $radioChecked('nyeri', 'Ya') }}>
                                    <label class="form-check-label" for="nyeri_ya">Ya</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="d-block">Onset</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="onset" id="onset_akut" value="Akut" {{ $radioChecked('onset', 'Akut') }}>
                                    <label class="form-check-label" for="onset_akut">Akut</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="onset" id="onset_kronik" value="Kronik" {{ $radioChecked('onset', 'Kronik') }}>
                                    <label class="form-check-label" for="onset_kronik">Kronik</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="erm-subhead">Asesmen nyeri (PQRT)</div>
                    <div class="row">
                        <div class="col-6 col-sm-3"><div class="form-group"><label for="p">P — Provokasi</label><input type="text" class="form-control" id="p" name="p" value="{{ old('p', $dataperawat->p ?? '') }}"></div></div>
                        <div class="col-6 col-sm-3"><div class="form-group"><label for="q">Q — Kualitas</label><input type="text" class="form-control" id="q" name="q" value="{{ old('q', $dataperawat->q ?? '') }}"></div></div>
                        <div class="col-6 col-sm-3"><div class="form-group"><label for="r">R — Lokasi</label><input type="text" class="form-control" id="r" name="r" value="{{ old('r', $dataperawat->r ?? '') }}"></div></div>
                        <div class="col-6 col-sm-3"><div class="form-group"><label for="t">T — Waktu</label><input type="text" class="form-control" id="t" name="t" value="{{ old('t', $dataperawat->t ?? '') }}"></div></div>
                        <div class="col-6"><div class="form-group"><label for="skor">Skor</label><input type="text" class="form-control" id="skor" name="skor" value="{{ old('skor', $dataperawat->skor ?? '') }}"></div></div>
                        <div class="col-6"><div class="form-group"><label for="kategori">Kategori</label><input type="text" class="form-control" id="kategori" name="kategori" value="{{ old('kategori', $dataperawat->kategori ?? '') }}"></div></div>
                    </div>
                </section>
            </div>

            {{-- 5. Risiko jatuh --}}
            <div class="col-xl-6">
                <section class="erm-section">
                    <div class="erm-section-head">
                        <span class="erm-section-num">5</span>
                        <h4 class="erm-section-title">Risiko Jatuh Rawat Jalan<span class="erm-section-sub">Get Up and Go</span></h4>
                    </div>
                    <div class="form-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="kategori_risja" id="tidak_beresiko" value="tidak_beresiko" {{ $radioChecked('kategori_risja', 'tidak_beresiko') }}>
                            <label class="form-check-label" for="tidak_beresiko">Tidak berisiko <small class="text-muted">(a dan b tidak ditemukan)</small></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="kategori_risja" id="risiko_rendah" value="risiko_rendah" {{ $radioChecked('kategori_risja', 'risiko_rendah') }}>
                            <label class="form-check-label" for="risiko_rendah">Risiko rendah <small class="text-muted">(a atau b)</small></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="kategori_risja" id="risiko_tinggi" value="risiko_tinggi" {{ $radioChecked('kategori_risja', 'risiko_tinggi') }}>
                            <label class="form-check-label" for="risiko_tinggi">Risiko tinggi <small class="text-muted">(a dan b)</small></label>
                        </div>
                    </div>
                </section>
            </div>

            {{-- 6. Status psikologis --}}
            <div class="col-xl-6">
                <section class="erm-section">
                    <div class="erm-section-head">
                        <span class="erm-section-num">6</span>
                        <h4 class="erm-section-title">Status Psikologis</h4>
                    </div>
                    <div class="form-group">
                        @foreach (['tenang' => 'Tenang', 'cemas' => 'Cemas', 'takut' => 'Takut', 'marah' => 'Marah', 'sedih' => 'Sedih'] as $value => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status_fungsional" id="{{ $value }}" value="{{ $value }}" {{ $radioChecked('status_fungsional', $value) }}>
                                <label class="form-check-label" for="{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        {{-- 7. Masalah keperawatan --}}
        <section class="erm-section">
            <div class="erm-section-head">
                <span class="erm-section-num">7</span>
                <h4 class="erm-section-title">Masalah Keperawatan<span class="erm-section-sub">Centang semua yang sesuai</span></h4>
            </div>
            <div class="erm-masalah-grid">
                @foreach ($masalahGroups as $group => $items)
                    <div class="erm-masalah-group">
                        <div class="erm-subhead">{{ $group }}</div>
                        @foreach ($items as $id => $value)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="{{ $value }}" id="{{ $id }}" {{ in_array($value, $oldMasalah) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $id }}">{{ $value }}</label>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                <div class="erm-masalah-group">
                    <div class="erm-subhead">Lain-lain</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Lain-lain" id="lain_lain" {{ in_array('Lain-lain', $oldMasalah) ? 'checked' : '' }}>
                        <label class="form-check-label" for="lain_lain">Lain-lain</label>
                    </div>
                    <input type="text" name="lain_lain_text" class="form-control form-control-sm mt-1" placeholder="Sebutkan" value="{{ $lainLainText }}">
                </div>
            </div>
        </section>

        <div class="erm-actionbar">
            <span class="erm-actionbar-hint"><i class="fas fa-info-circle mr-1"></i>Pastikan tanda vital dan masalah keperawatan sudah terisi.</span>
            <button type="submit" class="btn btn-erm-primary" id="btn-simpan-asesmen-perawat">
                <i class="fas fa-save mr-1"></i>Simpan Asesmen
            </button>
        </div>
    </form>
</div><!-- container -->


@endsection
@section('scripts')
<script>  

   $(document).ready(function () {
    // One page form (was a 2-step wizard); saved with the save bar at the bottom
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
        // Guard against double submit while saving
        let saveBtn = $('#btn-simpan-asesmen-perawat');
        if (saveBtn.prop('disabled')) {
            return;
        }
        let saveBtnHtml = saveBtn.html();
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span>Menyimpan...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json', // ✅ ensure it's parsed
            complete: function () {
                saveBtn.prop('disabled', false).html(saveBtnHtml);
            },
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

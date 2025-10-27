@extends('layouts.erm.app')
@section('title', 'ERM | Asesmen Medis')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-0 mt-2">
        <div>
            <h3 class="mb-0">Asesmen Dokter<strong> {{ $visitation->dokter->spesialisasi->nama }}</h3>
            
        </div>
        <div style="width: 400px;" class="d-flex align-items-center justify-content-end mt-2">

            <select class="form-control select2" name="jenis_konsultasi" style="margin-right: 20px; flex-shrink: 0;">
                <option value="" disabled>Pilih Jenis Konsultasi</option>
                @foreach ($jenisKonsultasi as $konsultasi)
                    <option value="{{ $konsultasi->id }}" 
                        {{ old('jenis_konsultasi', $visitation->dokter->spesialisasi->id == 6 ? 1 : 2) == $konsultasi->id ? 'selected' : '' }}>
                        {{ $konsultasi->nama }} - Rp {{ $konsultasi->harga }}
                    </option>
                @endforeach
            </select>
            <span id="timer" class="ml-3" style="font-size: 14px; color: white; background-color: #007bff; padding: 5px 10px; border-radius: 5px;">
                00:00:00
            </span>
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
                            <li class="breadcrumb-item active">Asesmen Medis</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->


    @include('erm.partials.card-identitaspasien')

    @if(isset($spesialisasi) && $spesialisasi === 'estetika')
    <!-- Compact side-by-side Skincheck + Riwayat (moved to top) -->
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

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Terjadi kesalahan:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form id="asesmen-form" action="{{ route('erm.asesmendokter.store') }}" method="POST">
                @csrf
                    
                <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                <input type="hidden" name="pasien_id" value="{{ $visitation->pasien_id }}">

                    {{-- Asesmen Dokter --}}

                    @includeIf("erm.asesmendokter.partials.$spesialisasi", [
                        'dataperawat' => $dataperawat,
                        'asesmen' => $currentAsesmen,
                        'lokalisPath' => old('status_lokalis', $currentAsesmen->status_lokalis ?? null),
                    ])

                    {{-- Asesmen Penunjang --}}
                            
                            <!-- Diagnosa Kerja -->
                            <div class="mb-3">
                                <label class="form-label">Diagnosa Kerja</label>
                                @php
                                    $diagnosa_fields = [
                                        'diagnosakerja_1',
                                        'diagnosakerja_2',
                                        'diagnosakerja_3',
                                        'diagnosakerja_4',
                                        'diagnosakerja_5'
                                    ];
                                @endphp
                                <div class="row g-2 mb-4">
                                    @foreach ($diagnosa_fields as $index => $field)
                                        @php
                                            $oldValue = old($field, $asesmenPenunjang->$field ?? '');
                                        @endphp
                                        @if ($index == 3) </div><div class="row g-2"> @endif
                                        <div class="col-md-4">
                                            <select class="form-control select2-icd10" name="{{ $field }}">
                                                @if ($oldValue)
                                                    <option value="{{ $oldValue }}" selected>{{ $oldValue }}</option>
                                                @endif
                                            </select>
                                        </div>
                                    @endforeach
                                    <div class="col-md-4">
                                        {{-- <label for="diagnosakerja_6" class="form-label">Diagnosa Kerja 6 (Text)</label> --}}
                                        <input type="text" class="form-control" name="diagnosakerja_6" id="diagnosakerja_6" value="{{ old('diagnosakerja_6', $asesmenPenunjang->diagnosakerja_6 ?? '') }}" placeholder="Input diagnosa non ICD-10">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    
                                </div>
                            </div>

                            <!-- Diagnosa Banding -->
                            <div class="mb-3">
                                <label for="diagnosa_banding" class="form-label">Diagnosa Banding</label>
                                <input type="text" class="form-control" name="diagnosa_banding" id="diagnosa_banding" value="{{ old('diagnosa_banding', $asesmenPenunjang->diagnosa_banding ?? '') }}">
                            </div>

                            <!-- Masalah Medis dan Keperawatan -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="masalah_medis" class="form-label">Masalah Medis</label>
                                    <textarea class="form-control" name="masalah_medis" id="masalah_medis" rows="2" ></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="masalah_keperawatan" class="form-label">Masalah Keperawatan</label>
                                    @php
                                        $valueFromPenunjang = old('masalah_keperawatan', $asesmenPenunjang->masalah_keperawatan ?? null);
                                        $valueFromPerawat = '';
                                        if (!$valueFromPenunjang && isset($dataperawat->masalah_keperawatan)) {
                                            if (is_array($dataperawat->masalah_keperawatan)) {
                                                $perawatArray = $dataperawat->masalah_keperawatan;
                                            } else {
                                                $perawatArray = json_decode($dataperawat->masalah_keperawatan, true);
                                            }
                                            if (is_array($perawatArray)) {
                                                $valueFromPerawat = implode("\n", $perawatArray);
                                            }
                                        }
                                        $textareaValue = $valueFromPenunjang ?? $valueFromPerawat;
                                    @endphp

                                    <textarea class="form-control" name="masalah_keperawatan" id="masalah_keperawatan" rows="4">{{ $textareaValue }}</textarea>
                                </div>
                            </div>                    

                    {{-- Tindak Lanjut dan Edukasi --}}                       

                            <!-- Sasaran -->
                            <div class="mb-3">
                                <label for="sasaran" class="form-label">Sasaran</label>
                                <input type="text" class="form-control" name="sasaran" id="sasaran" value="Kondisi Umum Baik dan Stabil">
                            </div>

                            <!-- Rencana Asuhan / Terapi / Intruksi -->
                            <div class="mb-3">
                                <label for="standing_order" class="form-label">Rencana Asuhan / Terapi / Intruksi (Standing Order)</label>
                                <textarea class="form-control" name="standing_order" id="standing_order" rows="5">edukasi diet dan olahraga &#10;kepatuhan konsumsi obat sesuai anjuran dokter</textarea>
                            </div>


                            <!-- Rencana Tindak Lanjut -->
                        <div class="mb-3">
                            <label class="form-label">Rencana Tindak Lanjut</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rtl" id="rawat_jalan" value="Rawat Jalan" checked>
                                <label class="form-check-label" for="rawat_jalan">Rawat Jalan</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rtl" id="rawat_inap" value="Rawat Inap">
                                <label class="form-check-label" for="rawat_inap">Rawat Inap</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rtl" id="rujuk" value="Rujuk">
                                <label class="form-check-label" for="rujuk">Rujuk</label>
                            </div>
                        </div>

                        <!-- Rawat Inap Fields -->
                        <div id="ranap_fields" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="ruang" class="form-label">Ruang</label>
                                    <input type="text" class="form-control" name="ruang" id="ruang">
                                </div>
                                <div class="col-md-6">
                                    <label for="dpip" class="form-label">DPJP Ranap</label>
                                    <input type="text" class="form-control" name="dpip" id="dpip">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="pengantar" class="form-label">Pengantar Pasien</label>
                                <select class="form-select" name="pengantar" id="pengantar">
                                    <option value="1">Ya</option>
                                    <option value="0">Tidak (Rujuk ke Dinas Sosial)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Rujuk Fields -->
                        <div id="rujuk_fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Rujuk Ke</label><br>
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="rujuk[]" value="RS" id="rujuk_rs">
                                            <label class="form-check-label" for="rujuk_rs">RS</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="rujuk[]" value="Dokter Keluarga" id="rujuk_dokter_keluarga">
                                            <label class="form-check-label" for="rujuk_dokter_keluarga">Dokter Keluarga</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="rujuk[]" value="Puskesmas" id="rujuk_puskesmas">
                                            <label class="form-check-label" for="rujuk_puskesmas">Puskesmas</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="rujuk[]" value="Dokter" id="rujuk_dokter">
                                            <label class="form-check-label" for="rujuk_dokter">Dokter</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="rujuk[]" value="Home Care" id="rujuk_homecare">
                                            <label class="form-check-label" for="rujuk_homecare">Home Care</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label for="kontrol_homecare" class="form-label">Kontrol Klinik / Homecare Di</label>
                                        <input type="text" class="form-control" name="kontrol_homecare" id="kontrol_homecare">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tanggal_kontrol" class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" name="tanggal_kontrol" id="tanggal_kontrol">
                                    </div>
                                </div>
                            </div>
                        </div>                   
                        <div>
                        <label>Edukasi Pasien :</label>
                        <p>Edukasi Awal, disampaikan tentang diagnosis, Rencana dan Tujuan Terapi kepada :</p>

                        <label>
                            <input type="checkbox" name="edukasi[]" value="pasien" checked> Pasien
                        </label><br>

                        <label>
                            <input type="checkbox" name="edukasi[]" value="keluarga"> 
                            Keluarga Pasien, nama : <input type="text" name="nama_keluarga"> , Hubungan dengan pasien : 
                            <input type="text" name="hubungan_keluarga">
                        </label><br>

                        <label>
                            <input type="checkbox" name="edukasi[]" value="tidak_diberikan">
                            Tidak dapat memberi edukasi kepada pasien atau keluarga, karena 
                            <input type="text" name="alasan_tidak_edukasi">
                        </label>
                        </div>                  
                <div class="d-flex justify-content-end mt-4">
                    {{-- <input type="hidden" name="spesialisasi" value="{{ strtolower($visitation->dokter->spesialisasi->nama) }}"> --}}

                    <button type="submit" class="btn btn-primary">
                        Simpan Asesmen
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</div><!-- container -->



@endsection
@section('scripts')
<script>   
    let timerInterval;
    let secondsElapsed = parseInt(localStorage.getItem('secondsElapsed')) || 0;

    function updateTimer() {
        const hours = String(Math.floor(secondsElapsed / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((secondsElapsed % 3600) / 60)).padStart(2, '0');
        const seconds = String(secondsElapsed % 60).padStart(2, '0');
        document.getElementById('timer').textContent = `${hours}:${minutes}:${seconds}`;
        secondsElapsed++;
        localStorage.setItem('secondsElapsed', secondsElapsed);

        if (secondsElapsed === 840) {
            const jenisKonsultasiSelect = document.querySelector('select[name="jenis_konsultasi"]');
            jenisKonsultasiSelect.value = 2;
            jenisKonsultasiSelect.dispatchEvent(new Event('change'));
        }
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function startTimer() {
        stopTimer();
        timerInterval = setInterval(updateTimer, 1000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        startTimer();
    });
   $(document).ready(function () {
    $('.select2').select2({ width: '100%' });
    $('.select2-icd10').select2({
        placeholder: 'Search ICD-10 code...',
        ajax: {
            url: '{{ route("icd10.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(item => ({
                        id: `${item.code} - ${item.description}`,
                        text: `${item.code} - ${item.description}`
                    }))
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    function updateGCS() {
        // Ambil value dari masing-masing dropdown dan konversi ke integer
        const e = parseInt(document.getElementById('e').value) || 0;
        const v = parseInt(document.getElementById('v').value) || 0;
        const m = parseInt(document.getElementById('m').value) || 0;

        // Hitung total
        const total = e + v + m;

        // Tampilkan di input readonly
        document.getElementById('hsl').value = total;
    }

    // Pasang event listener ke semua select
    if (document.getElementById('e') && document.getElementById('v') && document.getElementById('m')) {
    document.getElementById('e').addEventListener('change', updateGCS);
    document.getElementById('v').addEventListener('change', updateGCS);
    document.getElementById('m').addEventListener('change', updateGCS);

    // Run updateGCS once during page load
    updateGCS();
}

    if ($('#keluhan_utama').length > 0) {
        // Initially set masalah_medis to match keluhan_utama
        $('#masalah_medis').val($('#keluhan_utama').val());

        // Update masalah_medis whenever keluhan_utama changes
        $('#keluhan_utama').on('input', function () {
            $('#masalah_medis').val($(this).val());
        });
    }

    // // Saat tombol modal alergi ditekan
    // $('#btnBukaAlergi').on('click', function () {
    //     $('#modalAlergi').modal('show');
    // });

    // // Toggle semua bagian tergantung status
    //     var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val(); // Ambil status yang dipilih awalnya
    
    // // Jika status alergi adalah 'ada', tampilkan semua elemen yang terkait
    // if (initialStatusAlergi === 'ada') {
    //     $('#inputKataKunciWrapper').show();
    //     $('#selectAlergiWrapper').show();
    //     $('#selectKandunganWrapper').show();
    // } else {
    //     // Jika tidak, sembunyikan elemen-elemen tersebut
    //     $('#inputKataKunciWrapper').hide();
    //     $('#selectAlergiWrapper').hide();
    //     $('#selectKandunganWrapper').hide();
    // }
    // $('input[name="statusAlergi"]').on('change', function () {
    //     if ($(this).val() === 'ada') {
    //         $('#inputKataKunciWrapper').show();
    //         $('#selectAlergiWrapper').show();
    //         $('#selectKandunganWrapper').show();
    //     } else {
    //         $('#inputKataKunciWrapper').hide();
    //         $('#selectAlergiWrapper').hide();
    //         $('#selectKandunganWrapper').hide();
    //         $('#inputKataKunci').val('');
    //         $('#selectAlergi, #selectKandungan').val(null).trigger('change');
    //     }
    // });

    function toggleRencanaTindakLanjut() {
    const pilihan = $('input[name="rtl"]:checked').val();
    // console.log('Pilihan RTL:', pilihan);  // Debug

        if (pilihan === 'Rawat Jalan') {
            $('#ranap_fields').hide();
            $('#rujuk_fields').hide();
        } else if (pilihan === 'Rawat Inap') {
            $('#ranap_fields').show();
            $('#rujuk_fields').hide();
        } else if (pilihan === 'Rujuk') {
            $('#ranap_fields').hide();
            $('#rujuk_fields').show();
        }
    }

    // Panggil saat halaman pertama kali dibuka
    toggleRencanaTindakLanjut();

    // Event listener saat radio berubah
    $('input[name="rtl"]').on('change', function () {
        console.log('Radio RTL changed');  // Debug
        toggleRencanaTindakLanjut();
    });

    

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#asesmen-form').on('submit', function (e) {
        e.preventDefault();

        let form = $(this);
        let formData = new FormData(this);
        let jenisKonsultasi = $('select[name="jenis_konsultasi"]').val();
            formData.append('jenis_konsultasi', jenisKonsultasi);

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

                  // Reset timer dengan benar
                stopTimer(); // stop interval lama
                secondsElapsed = 0;
                localStorage.setItem('secondsElapsed', secondsElapsed);
                timerElement.textContent = "00:00:00";
                startTimer(); // restart timer baru
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

// Canvas-related functions - only run if elements exist
const addButton = document.getElementById('addButton');
if (addButton) {
    addButton.addEventListener('click', function () {
        let canvas = document.getElementById('drawingCanvas');
        if (canvas) {
            let base64Image = canvas.toDataURL('image/png');
            const statusLokalisInput = document.getElementById('status_lokalis_image');
            if (statusLokalisInput) {
                statusLokalisInput.value = base64Image;
            }
        }
    });
}
</script>

<script>
window.onload = function () {
    const canvas = document.getElementById("drawingCanvas");
    if (!canvas) return; // Exit if canvas doesn't exist
    const ctx = canvas.getContext("2d");
    const imagePath = "{{ asset($lokalisBackground) }}";
    const savedImagePath = "{{ asset($lokalisPath) }}"; // Load the saved image path

    const img = new Image();
    img.onload = function () {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);

        // Load the saved image (if it exists)
        if (savedImagePath) {
            const savedImg = new Image();
            savedImg.onload = function () {
                ctx.drawImage(savedImg, 0, 0); // Draw the saved image on top of the background
            };
            savedImg.src = savedImagePath;
        }

        ctx.strokeStyle = "#00FF00";
        ctx.lineWidth = 5;

        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        canvas.addEventListener('mousedown', function (e) {
            isDrawing = true;
            lastX = e.offsetX;
            lastY = e.offsetY;
        });

        canvas.addEventListener('mousemove', function (e) {
            if (isDrawing) {
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.stroke();
                lastX = e.offsetX;
                lastY = e.offsetY;
            }
        });

        canvas.addEventListener('mouseup', function () {
            isDrawing = false;
        });

        const resetButton = document.getElementById('resetButton');
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0); // Reset to background image
            });
        }
    };

    img.src = imagePath;
};
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
            document.getElementById('qr_decoded_text').textContent = '';
        };
        reader.readAsDataURL(file);
    });

    var checkBtn = document.getElementById('check_skincheck');

    // Check button: upload file to decode endpoint and show decoded text/url (no DB save)
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

        // Save button: upload the image and decoded text (if any)
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

    resetBtn.addEventListener('click', function () {
        fileInput.value = null;
        previewWrapper.style.display = 'none';
        preview.src = '#';
        decodedLink.href = '#';
        decodedLink.textContent = '';
        decodedLink.style.display = 'none';
        statusEl.textContent = '';
        hiddenUrl.value = '';
        lastFile = null; serverDecoded = null;
    });
});
</script>

<!-- Modal for QR preview -->
<div class="modal fade" id="modalQrPreview" tabindex="-1" role="dialog" aria-labelledby="modalQrPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalQrPreviewLabel">Preview QR</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalQrImage" src="#" alt="QR" style="max-width:100%; height:auto;" />
            </div>
        </div>
    </div>
</div>

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

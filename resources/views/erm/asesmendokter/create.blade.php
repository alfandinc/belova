@extends('layouts.erm.app')
@section('title', 'ERM | Asesmen Medis')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection
@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Asesmen Dokter</h3>
        <h3 class="mb-0"><strong>Penyakit Dalam</strong></h3>
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
        
    <div class="card">
        {{-- <div class="card-header bg-primary">
            <h4 class="card-title text-white">Asesmen Medis Penyakit Dalam</h4>
        </div> --}}
        <div class="card-body">

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

                {{-- Asesmen Dokter --}}
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="keluhan_utama">KELUHAN UTAMA</label>
                                    <input type="text" class="form-control focus:outline-white focus:border-white" id="keluhan_utama" name="keluhan_utama" value="{{ old('keluhanUtama', $dataperawat->keluhan_utama ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="riwayat_penyakit_sekarang">Riwayat Penyakit Sekarang</label>
                                    <input type="text" class="form-control" id="riwayat_penyakit_sekarang" name="riwayat_penyakit_sekarang">
                                </div>
                            </div>    
                        </div> 
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="riwayat_penyakit_dahulu">Riwayat Penyakit Dahulu</label>
                                    <input type="text" class="form-control" id="riwayat_penyakit_dahulu" name="riwayat_penyakit_dahulu">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="obat_dikonsumsi">Obat yang Dikonsumsi</label>
                                    <input type="text" class="form-control" id="obat_dikonsumsi" name="obat_dikonsumsi">
                                </div>
                            </div>    
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="keadaan_umum">Keadaan Umum</label>
                                    <input type="text" class="form-control" id="keadaan_umum" name="keadaan_umum">
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="e">E (Eye Opening)</label>
                                    <select class="form-control" id="e" name="e">
                                        <option value="">Pilih</option>
                                        <option selected value="4">Spontan (4)</option>
                                        <option value="3">Perintah Suara (3)</option>
                                        <option value="2">Nyeri (2)</option>
                                        <option value="1">Tidak Ada Respon (1)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="v">V (Verbal)</label>
                                    <select class="form-control" id="v" name="v">
                                        <option value="">Pilih</option>
                                        <option selected value="5">Orientasi Baik (5)</option>
                                        <option value="4">Bingung (4)</option>
                                        <option value="3">Kata Tidak Tepat (3)</option>
                                        <option value="2">Kata Tidak Dimengerti (2)</option>
                                        <option value="1">Tidak Ada Suara (1)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="m">M (Motorik)</label>
                                    <select class="form-control" id="m" name="m">
                                        <option value="">Pilih</option>
                                        <option selected value="6">Perintah Tepat (6)</option>
                                        <option value="5">Lokal Nyeri (5)</option>
                                        <option value="4">Menarik (4)</option>
                                        <option value="3">Fleksi Abnormal (3)</option>
                                        <option value="2">Ekstensi Abnormal (2)</option>
                                        <option value="1">Tidak Ada Gerakan (1)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hsl">Total GCS</label>
                                    <input value="15" type="number" id="hsl" name="hsl" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="td" class="me-2 mb-0 mr-2" style="width: 40px;">TD</label>
                                    <input type="text" class="form-control" id="td" name="td">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="n" class="me-2 mb-0 mr-2" style="width: 40px;">N</label>
                                    <input type="text" class="form-control" id="n" name="n">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="s" class="me-2 mb-0 mr-2" style="width: 40px;">S</label>
                                    <input type="text" class="form-control" id="s" name="s">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="r" class="me-2 mb-0 mr-2" style="width: 40px;">R</label>
                                    <input type="text" class="form-control" id="r" name="r">
                                </div>
                            </div>
                        </div>
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>1.</td>
                                    <td>Kepala</td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="kepala" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td>2.</td>
                                    <td>Leher</td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="leher" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td>3.</td>
                                    <td><em>Thorax</em></td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="thorax" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td>4.</td>
                                    <td><em>Abdomen</em></td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="abdomen" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td>5.</td>
                                    <td><em>Genitalia</em></td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="genitalia" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td>6.</td>
                                    <td><em>Extremitas</em></td>
                                    <td>:</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>- <em>Extremitas Atas</em></td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="ext_atas" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>- <em>Extremitas Bawah</em></td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="ext_bawah" value="dbn"></td>
                                </tr>
                            </tbody>
                        </table>    
                       <div class="form-group row">
                            <!-- Gambar (3 bagian dari total 4) -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <canvas id="drawingCanvas" class="img-fluid rounded border"></canvas>
                                    <img src="{{ asset('img/dalam-coba.png')}}" class="img-fluid rounded border" alt="Status Lokalis Image" id="imageElement" style="display:none;">
                                </div>
                            </div>

                            <!-- Textarea (1 bagian dari total 4) -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <textarea class="form-control" rows="4" placeholder="Tulis status lokalis di sini..."></textarea>
                                </div>
                                <!-- Tombol -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-secondary" id="resetButton">Reset</button>
                                    <button type="button" class="btn btn-primary" id="addButton">Add</button>
                                </div>
                            </div>
                        </div>
                            
                            <!-- Diagnosa Kerja -->
                            <div class="mb-3">
                                <label class="form-label"><strong>Diagnosa Kerja</strong></label>
                                <div class="row g-2 mb-4">
                                    <div class="col-md-4">
                                        <select class="form-control select2" name="diagnosa_kerja_1">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="K25">K25 - Gastric ulcer</option>
                                            <option value="E11.7">E11.7 - Diabetes mellitus</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control select2" name="diagnosa_kerja_2">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="I11">I11 - Hypertensive heart disease</option>
                                            <option value="N13.0">N13.0 - Hydronephrosis</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control select2" name="diagnosa_kerja_3">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="J45">J45 - Asthma</option>
                                            <option value="A09">A09 - Gastroenteritis</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select class="form-control select2" name="diagnosa_kerja_4">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="K25">K25 - Gastric ulcer</option>
                                            <option value="E11.7">E11.7 - Diabetes mellitus</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control select2" name="diagnosa_kerja_5">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="I11">I11 - Hypertensive heart disease</option>
                                            <option value="N13.0">N13.0 - Hydronephrosis</option>
                                        </select>
                                    </div>
                                    
                                </div>
                            </div>

                            <!-- Diagnosa Banding -->
                            <div class="mb-3">
                                <label for="diagnosa_banding" class="form-label"><strong>Diagnosa Banding</strong></label>
                                <input type="text" class="form-control" name="diagnosa_banding" id="diagnosa_banding">
                            </div>

                            <!-- Masalah Medis dan Keperawatan -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="masalah_medis" class="form-label"><strong>Masalah Medis</strong></label>
                                    <textarea class="form-control" name="masalah_medis" id="masalah_medis" rows="2" ></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="masalah_keperawatan" class="form-label"><strong>Masalah Keperawatan</strong></label>
                                    <textarea class="form-control" name="masalah_keperawatan" id="masalah_keperawatan" rows="2"></textarea>
                                </div>
                            </div>                    

                 {{-- Tindak Lanjut dan Edukasi --}}                       

                            <!-- Sasaran -->
                            <div class="mb-3">
                                <label for="sasaran" class="form-label"><strong>Sasaran</strong></label>
                                <input type="text" class="form-control" name="sasaran" id="sasaran">
                            </div>

                            <!-- Rencana Asuhan / Terapi / Intruksi -->
                            <div class="mb-3">
                                <label for="standing_order" class="form-label"><strong>Rencana Asuhan / Terapi / Intruksi (Standing Order)</strong></label>
                                <textarea class="form-control" name="standing_order" id="standing_order" rows="2"></textarea>
                            </div>

                            <!-- Rencana Tindak Lanjut -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Rencana Tindak Lanjut</strong></label><br>
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
                                <label class="form-label"><strong>Rujuk Ke</strong></label><br>
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
                        <label><strong>Edukasi Pasien :</strong></label>
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
// SweetAlert setelah document ready (boleh di luar juga)
        @if(session('success'))
            Swal.fire({
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        @endif 

   $(document).ready(function () {
    $('.select2').select2({ width: '100%' });
    $('#tanggal_lahir').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

   
    

    $('#tanggal_lahir').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD'));
    });

    $('#tanggal_lahir').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
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
    document.getElementById('e').addEventListener('change', updateGCS);
    document.getElementById('v').addEventListener('change', updateGCS);
    document.getElementById('m').addEventListener('change', updateGCS);

    // Jalankan sekali saat halaman load (optional)
    updateGCS();

    $('#keluhan_utama').on('input', function () {
        var keluhan = $(this).val();
        var masalahMedis = $('#masalah_medis');

        if (masalahMedis.val() === '') {
            masalahMedis.val(keluhan);
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

    function toggleRencanaTindakLanjut() {
    const pilihan = $('input[name="rtl"]:checked').val();
    console.log('Pilihan RTL:', pilihan);  // Debug

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

});
</script>

<script>
    window.onload = function() {
        var canvas = document.getElementById("drawingCanvas");
        var ctx = canvas.getContext("2d");
        var img = document.getElementById("imageElement");

        function setupCanvas() {
            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);

            ctx.strokeStyle = "#00FF00"; // hijau
            ctx.lineWidth = 5;

            var isDrawing = false;
            var lastX = 0;
            var lastY = 0;

            canvas.addEventListener('mousedown', function(e) {
                isDrawing = true;
                lastX = e.offsetX;
                lastY = e.offsetY;
            });

            canvas.addEventListener('mousemove', function(e) {
                if (isDrawing) {
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(e.offsetX, e.offsetY);
                    ctx.stroke();
                    lastX = e.offsetX;
                    lastY = e.offsetY;
                }
            });

            canvas.addEventListener('mouseup', function() {
                isDrawing = false;
            });

            document.getElementById('resetButton').addEventListener('click', function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0);  // gambar ulang
            });
        }

        img.onload = setupCanvas;

        // Cek jika gambar sudah dimuat (dari cache), panggil langsung
        if (img.complete) {
            img.onload(); // panggil langsung
        }
    };
</script>



@endsection

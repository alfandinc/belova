@extends('layouts.erm.app')
@section('title', 'Asesmen Medis')
@section('content')
<style>
    /* Sembunyikan form wizard sebelum siap */
    #asesmen-form {
        visibility: hidden;
    }

    /* Tampilkan setelah wizard di-init */
    #asesmen-form.wizard-initialized {
        visibility: visible;
    }

    .is-invalid {
    border-color: red !important;    
    }

</style>
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Asesmen</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    <div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">No. RM</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><strong>: {{ $visitation->pasien->id ?? '-' }}</strong></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">Nama Pasien</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><strong>: {{ $visitation->pasien->nama ?? '-' }}</strong></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">Tanggal Lahir</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext">
                            <strong>: {{ $visitation->pasien->tanggal_lahir ? \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir)->format('d-m-Y') : '-' }}</strong>
                        </p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 form-label text-end">Jenis Kelamin</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext">
                            <strong>: {{ ucfirst($visitation->pasien->gender ?? '-') }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Asesmen Medis</h4>
        </div>
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
            <form id="asesmen-form" class="form-wizard-wrapper" action="{{ route('erm.pasiens.store') }}" method="POST">
                @csrf
                <h3>Riwayat Alergi</h3>
                    <fieldset>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="alergi">Riwayat Alergi (Nama Obat)</label>
                                    <select class="form-control select2" id="alergi" name="alergi[]" multiple="multiple" required>
                                        <option value="Paracetamol">Paracetamol</option>
                                        <option value="Amoxicillin">Amoxicillin</option>
                                        <option value="Ibuprofen">Ibuprofen</option>
                                        <option value="Cetirizine">Cetirizine</option>
                                        <option value="Aspirin">Aspirin</option>
                                        <option value="Metformin">Metformin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Anamnesis</label><br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="anamnesis[]" id="anamnesisA" value="Autoanamnesis">
                                        <label class="form-check-label" for="anamnesisA">Autoanamnesis</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="anamnesis[]" id="anamnesisB" value="Alloanamnesis">
                                        <label class="form-check-label" for="anamnesisB">Alloanamnesis</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nama">Hubungan dengan Pasien</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Keluhan Utama</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Riwayat Penyakit Sekarang</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>    
                        </div> 
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Alloanamnesis dengan</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Hasil Alloanamnesis</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>    
                        </div>  
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">Riwayat Penyakit Dahulu</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama">HObay yang Dikonsumsi</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required>
                                </div>
                            </div>    
                        </div>   
                    </fieldset>
                <h3>Keadaan Umum</h3>
                    <fieldset>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="gcs_e">E (Eye Opening)</label>
                                    <select class="form-control" id="gcs_e" name="gcs_e" required>
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
                                    <label for="gcs_v">V (Verbal)</label>
                                    <select class="form-control" id="gcs_v" name="gcs_v" required>
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
                                    <label for="gcs_m">M (Motorik)</label>
                                    <select class="form-control" id="gcs_m" name="gcs_m" required>
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
                                    <label for="gcs_total">Total GCS</label>
                                    <input value="15" type="number" id="gcs_total" name="gcs_total" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="td" class="me-2 mb-0 mr-2" style="width: 40px;">TD</label>
                                    <input type="text" class="form-control" id="td" name="td" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="n" class="me-2 mb-0 mr-2" style="width: 40px;">N</label>
                                    <input type="text" class="form-control" id="n" name="n" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="s" class="me-2 mb-0 mr-2" style="width: 40px;">S</label>
                                    <input type="text" class="form-control" id="s" name="s" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group d-flex align-items-center">
                                    <label for="r" class="me-2 mb-0 mr-2" style="width: 40px;">R</label>
                                    <input type="text" class="form-control" id="r" name="r" required>
                                </div>
                            </div>
                        </div> 

                    </fieldset>
                <h3>Status General</h3>
                    <fieldset>
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
                                    <td><input type="text" class="form-control" name="extremitas_atas" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>- <em>Extremitas Bawah</em></td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="extremitas_bawah" value="dbn"></td>
                                </tr>
                            </tbody>
                        </table>
                    </fieldset>
                <h3>Status Lokalis</h3>
                    <fieldset>
                        <div class="form-group">
                            
                            
                            <!-- Gambar -->
                            <div class="mb-3">
                                <img src="{{ asset('img/dalam-coba.png')}}" class="img-fluid rounded border" alt="Status Lokalis Image">
                            </div>

                            <!-- Tombol -->
                            <div class="mb-3">
                                <button type="button" class="btn btn-secondary">Reset</button>
                                <button type="button" class="btn btn-primary">Add</button>
                            </div>

                            <!-- Textarea -->
                            <div class="mb-3">
                                <textarea class="form-control" rows="5" placeholder="Tulis status lokalis di sini..."></textarea>
                            </div>
                        </div>
                    </fieldset>
                    <h3>Pemeriksaan Lab</h3>
                    <fieldset>
                        <div class="container">
                            <div class="row">
                                <!-- Kolom Kiri -->
                                <div class="col-md-6">
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">GDP</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="gdp">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">KREATININ</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="kreatinin">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">SGOT</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="sgot">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">HB</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="hb">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">TROMBOSIT</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="trombosit">
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Kanan -->
                                <div class="col-md-6">
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">GDS</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="gds">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">LDL / TRIGLISERIDA</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="ldl_trigliserida">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">SGPT</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="sgpt">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">LEUKOSIT</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="leukosit">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">ASAM URAT</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="asam_urat">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </fieldset>
                    <h3>Diagnosa</h3>
                    <fieldset>
                        <div class="container mt-3">

                            <!-- Diagnosa Kerja -->
                            <div class="mb-3">
                                <label class="form-label"><strong>Diagnosa Kerja</strong></label>
                                <div class="row g-2 mb-4">
                                    <div class="col-md-4">
                                        <select class="form-control" name="diagnosa_kerja[]">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="K25">K25 - Gastric ulcer</option>
                                            <option value="E11.7">E11.7 - Diabetes mellitus</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control" name="diagnosa_kerja[]">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="I11">I11 - Hypertensive heart disease</option>
                                            <option value="N13.0">N13.0 - Hydronephrosis</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control" name="diagnosa_kerja[]">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="J45">J45 - Asthma</option>
                                            <option value="A09">A09 - Gastroenteritis</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select class="form-control" name="diagnosa_kerja[]">
                                            <option selected disabled>Pilih Diagnosa</option>
                                            <option value="K25">K25 - Gastric ulcer</option>
                                            <option value="E11.7">E11.7 - Diabetes mellitus</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control" name="diagnosa_kerja[]">
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
                                    <textarea class="form-control" name="masalah_medis" id="masalah_medis" rows="2"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="masalah_keperawatan" class="form-label"><strong>Masalah Keperawatan</strong></label>
                                    <textarea class="form-control" name="masalah_keperawatan" id="masalah_keperawatan" rows="2">Gangguan Rasa Nyaman</textarea>
                                </div>
                            </div>

                            <!-- Checkbox -->
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="sama_keluhan" name="sama_keluhan">
                                <label class="form-check-label" for="sama_keluhan">
                                    Apakah sama dengan Keluhan Utama?
                                </label>
                            </div>

                        </div>

                    </fieldset>
                    <h3>Tindak Lanjut</h3>
                    <fieldset>
                        <div class="container mt-3">

    <!-- Sasaran -->
    <div class="mb-3">
        <label for="sasaran" class="form-label"><strong>Sasaran</strong></label>
        <input type="text" class="form-control" name="sasaran" id="sasaran">
    </div>

    <!-- Rencana Asuhan / Terapi / Intruksi -->
    <div class="mb-3">
        <label for="rencana_asuhan" class="form-label"><strong>Rencana Asuhan / Terapi / Intruksi (Standing Order)</strong></label>
        <textarea class="form-control" name="rencana_asuhan" id="rencana_asuhan" rows="2"></textarea>
    </div>

    <!-- Rencana Tindak Lanjut -->
    <div class="mb-3">
        <label class="form-label"><strong>Rencana Tindak Lanjut</strong></label><br>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tindak_lanjut" id="rawat_jalan" value="Rawat Jalan" checked>
            <label class="form-check-label" for="rawat_jalan">Rawat Jalan</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tindak_lanjut" id="rawat_inap" value="Rawat Inap">
            <label class="form-check-label" for="rawat_inap">Rawat Inap</label>
        </div>
    </div>

    <!-- Ruang dan DPJP -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="ruang" class="form-label">Ruang</label>
            <input type="text" class="form-control" name="ruang" id="ruang">
        </div>
        <div class="col-md-6">
            <label for="dpjp_ranap" class="form-label">DPJP Ranap</label>
            <input type="text" class="form-control" name="dpjp_ranap" id="dpjp_ranap">
        </div>
    </div>

    <!-- Indikasi -->
    <div class="mb-3">
        <label for="pengantar" class="form-label">Pengantar Pasien</label>
        <select class="form-select" name="pengantar" id="pengantar">
            <option value="1">Ya</option>
            <option value="0">Tidak (Rujuk ke Dinas Sosial)</option>
        </select>
    </div>

    <!-- Rujuk Ke -->
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

                    </fieldset>
                    
                    <h3>Edukasi</h3>
                    <fieldset>
                        <div>
  <label><strong>Edukasi Pasien :</strong></label>
  <p>Edukasi Awal, disampaikan tentang diagnosis, Rencana dan Tujuan Terapi kepada :</p>

  <label>
    <input type="checkbox" name="edukasi[]" value="pasien"> Pasien
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

                    </fieldset>

                    
            </form>
        </div>
    </div>
</div><!-- container -->


@endsection
@section('scripts')
<script>  

   $(document).ready(function () {
    var wizard = $("#asesmen-form").steps({
    headerTag: "h3",
    bodyTag: "fieldset",
    transitionEffect: "slide",
    onStepChanged: function () {},
    onInit: function () {
        $('#asesmen-form').addClass('wizard-initialized');
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
            $('#asesmen-form').submit(); // 👈 THIS enables actual form submission
        }
    });
    
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
});
</script>
@endsection

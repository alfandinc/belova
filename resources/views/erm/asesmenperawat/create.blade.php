@extends('layouts.erm.app')
@section('title', 'Asesmen Medis')
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

{{-- Modals --}}
<div class="modal fade" id="modalAlergi" tabindex="-1" aria-labelledby="modalAlergiLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formAlergi">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Riwayat Alergi Pasien</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">

          {{-- Radio Button --}}
          <div class="form-group">
            <label>Apakah Pasien memiliki riwayat alergi?</label><br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="statusAlergi" id="alergiTidakAda" value="tidak" checked>
              <label class="form-check-label" for="alergiTidakAda">Tidak Ada</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="statusAlergi" id="alergiAda" value="ada">
              <label class="form-check-label" for="alergiAda">Ada</label>
            </div>
          </div>

          {{-- Input Kata Kunci --}}
          <div class="form-group" id="inputKataKunciWrapper" style="display: none;">
            <label for="inputKataKunci">Kata Kunci</label>
            <input type="text" id="inputKataKunci" class="form-control" placeholder="Masukkan kata kunci...">
          </div>

          {{-- Select2 Alergi --}}
          <div class="form-group" id="selectAlergiWrapper" style="display: none;">
            <label for="selectAlergi">Nama Obat</label>
            <select class="form-control select2" id="selectAlergi" multiple="multiple" style="width: 100%;">
              <option value="Paracetamol">Paracetamol</option>
              <option value="Amoxicillin">Amoxicillin</option>
              <option value="Ibuprofen">Ibuprofen</option>
              <option value="Cetirizine">Cetirizine</option>
              <option value="Aspirin">Aspirin</option>
              <option value="Metformin">Metformin</option>
            </select>
          </div>

          {{-- Select2 Kandungan Obat --}}
          <div class="form-group" id="selectKandunganWrapper" style="display: none;">
            <label for="selectKandungan">Kandungan Obat</label>
            <select class="form-control select2" id="selectKandungan" multiple="multiple" style="width: 100%;">
              <option value="Asam Mefenamat">Asam Mefenamat</option>
              <option value="Deksametason">Deksametason</option>
              <option value="Kloramfenikol">Kloramfenikol</option>
              <option value="Ranitidine">Ranitidine</option>
              <option value="Loratadine">Loratadine</option>
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Simpan Alergi</button>
        </div>
      </div>
    </form>
  </div>
</div>




{{-- End MOdals --}}
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
            <h4 class="card-title text-white">Asesmen Awal Keperawatan Pasien Rawat Jalan</h4>
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
            <form id="asesmenperawat-form" class="form-wizard-wrapper" action="{{ route('erm.asesmenperawat.store') }}" method="POST">
                @csrf
                <input type="text" id="visitation_id" name="visitation_id" class="form-control mr-2" value="{{ $visitation->id }}" hidden>
                @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
                <h3>I. Pengkajian Keperawatan</h3>
                    <fieldset>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="inputAlergiTerpilih"><strong>Riwayat Alergi</strong></label>
                                    <div class="d-flex">
                                        <input type="text" id="inputAlergiTerpilih" class="form-control mr-2" value="Tidak Ada" readonly>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAlergi">
                                            Pilih
                                        </button>
                                    </div>
                                </div>                               
                            </div>   
                        </div>
                        <hr>
                        <div class="col-md-12">                           
                            <label><strong>1. Keluhan Utama Pasien</strong></label>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-group">
                                        <label for="keluhan_utama">Keluhan Utama</label>
                                        <input type="text" class="form-control" id="keluhan_utama" name="keluhan_utama" >
                                    </div>                              
                                </div> 
                            </div> 
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-group">
                                        <label for="alasan_kunjungan">Alasan Kunjungan</label>
                                        <input type="text" class="form-control" id="alasan_kunjungan" name="alasan_kunjungan" >
                                    </div>                              
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
                                        <input type="text" class="form-control" id="kesadaran" name="kesadaran" >
                                    </div>                              
                                </div> 
                            </div> 
                            <div class="col-md-6">
                                <div class="form-row">
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="td">TD (mmHg)</label>
                                        <input type="text" class="form-control" id="td" name="td" placeholder="Contoh: 120/80">
                                    </div>
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="nadi">Nadi (x/mnt)</label>
                                        <input type="text" class="form-control" id="nadi" name="nadi" placeholder="Contoh: 75">
                                    </div>
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="rr">RR (x/mnt)</label>
                                        <input type="text" class="form-control" id="rr" name="rr" placeholder="Contoh: 18">
                                    </div>
                                    <div class="form-group col-md-6 col-lg-3">
                                        <label for="suhu">Suhu (°C)</label>
                                        <input type="text" class="form-control" id="suhu" name="suhu" placeholder="Contoh: 36.5">
                                    </div>
                                </div>
                            </div> 
                        </div>
                        <div class="row">
                            <!-- Left Side: Input -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="riwayat_psikososial">Riwayat Psikososial</label>
                                    <input type="text" class="form-control" id="riwayat_psikososial" name="riwayat_psikososial">
                                </div>
                            </div>

                            <!-- Right Side: Radio Buttons -->
                            <div class="col-md-6 d-flex align-items-center">
                                <label class="mr-3 mb-0" for="hubunganBaik">Hubungan dengan keluarga</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="hubungan_keluarga" id="hubunganBaik" value="Baik" checked>
                                        <label class="form-check-label" for="hubunganBaik">Baik</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="hubungan_keluarga" id="hubunganTidakBaik" value="Tidak Baik">
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
                                    <input type="text" class="form-control" id="tb" name="tb" placeholder="Cm">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bb">BB :</label>
                                    <input type="text" class="form-control" id="bb" name="bb" placeholder="Kg">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="lla">LLA :</label>
                                    <input type="text" class="form-control" id="lla" name="lla" placeholder="Cm">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="diet">Diet :</label>
                                    <input type="text" class="form-control" id="diet" name="diet">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="porsi">Porsi :</label>
                                    <input type="text" class="form-control" id="porsi" name="porsi">
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
                                            <td><input type="text" class="form-control" name="imt"></td>
                                        </tr>
                                        <tr>
                                            <td>Presentase Kehilangan BB yang tidak diharapkan</td>
                                            <td><input type="text" class="form-control" name="presentase"></td>
                                        </tr>
                                        <tr>
                                            <td>Efek Dari Penyakit Yang Diderita / 5 Hari Tidak Mendapat Asupan Nutrisi</td>
                                            <td><input type="text" class="form-control" name="efek"></td>
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
                                        <input class="form-check-input" type="radio" name="nyeri" id="nyeri_tidak" value="Tidak">
                                        <label class="form-check-label" for="nyeri_tidak">Tidak</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="nyeri" id="nyeri_ya" value="Ya">
                                        <label class="form-check-label" for="nyeri_ya">Ya</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Asesmen Nyeri Dengan:</label>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label for="p">P:</label>
                                            <input type="text" class="form-control" id="p" name="p">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="q">Q:</label>
                                            <input type="text" class="form-control" id="q" name="q">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="r">R:</label>
                                            <input type="text" class="form-control" id="r" name="r">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="t">T:</label>
                                            <input type="text" class="form-control" id="t" name="t">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Onset:</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="onset" id="onset_akut" value="Akut">
                                        <label class="form-check-label" for="onset_akut">Akut</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="onset" id="onset_kronik" value="Kronik">
                                        <label class="form-check-label" for="onset_kronik">Kronik</label>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-md-2">
                                        <label for="skor">Skor:</label>
                                        <input type="text" class="form-control" id="skor" name="skor">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="kategori">Kategori:</label>
                                        <input type="text" class="form-control" id="kategori" name="kategori">
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
                                        <input class="form-check-input" type="radio" name="kategori_risja" id="tidak_beresiko" value="tidak_beresiko">
                                        <label class="form-check-label" for="tidak_beresiko">Tdk Beresiko (tdk ditemukan a dan b)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="kategori_risja" id="risiko_rendah" value="risiko_rendah" checked>
                                        <label class="form-check-label" for="risiko_rendah">Risiko Rendah (ditemukan a atau b)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="kategori_risja" id="risiko_tinggi" value="risiko_tinggi">
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
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="tenang" value="tenang">
                                        <label class="form-check-label" for="tenang">Tenang</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="cemas" value="cemas">
                                        <label class="form-check-label" for="cemas">Cemas</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="takut" value="takut">
                                        <label class="form-check-label" for="takut">Takut</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="marah" value="marah">
                                        <label class="form-check-label" for="marah">Marah</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_fungsional" id="sedih" value="sedih">
                                        <label class="form-check-label" for="sedih">Sedih</label>
                                    </div>
                                </div>
                            </div>
                        </div>              
                    </fieldset>       
                <h3>II. Masalah Keperawatan</h3>
                    <fieldset>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label><strong>Respirasi</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Aspirasi" id="aspirasi">
                                            <label class="form-check-label" for="aspirasi">Risiko Aspirasi</label>
                                        </div>

                                        <label><strong>Sirkulasi</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Perfusi Perifer Tidak Efektif" id="perfusi">
                                            <label class="form-check-label" for="perfusi">Risiko Perfusi Perifer Tidak Efektif</label>
                                        </div>

                                        <label><strong>Nutrisi dan Cairan</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Diare" id="diare">
                                            <label class="form-check-label" for="diare">Diare</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ikterik Neonatus" id="ikterik">
                                            <label class="form-check-label" for="ikterik">Ikterik Neonatus</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ketidakstabilan Kadar Glukosa Darah" id="glukosa">
                                            <label class="form-check-label" for="glukosa">Ketidakstabilan Kadar Glukosa Darah</label>
                                        </div>

                                        <label><strong>Eliminasi</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Konstipasi" id="konstipasi">
                                            <label class="form-check-label" for="konstipasi">Konstipasi</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Retensi Urin" id="retensi">
                                            <label class="form-check-label" for="retensi">Retensi Urin</label>
                                        </div>

                                        <label><strong>Reproduksi dan Seksualitas</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Kesiapan Persalinan" id="persalinan">
                                            <label class="form-check-label" for="persalinan">Kesiapan Persalinan</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label><strong>Nyeri dan Kenyamanan</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Gangguan Rasa Nyaman" id="rasa_nyaman">
                                            <label class="form-check-label" for="rasa_nyaman">Gangguan Rasa Nyaman</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ketidaknyamanan Pasca Partum" id="pasca_partum">
                                            <label class="form-check-label" for="pasca_partum">Ketidaknyamanan Pasca Partum</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Nausea" id="nausea">
                                            <label class="form-check-label" for="nausea">Nausea</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Nyeri Akut" id="nyeri_akut">
                                            <label class="form-check-label" for="nyeri_akut">Nyeri Akut</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Nyeri Kronis" id="nyeri_kronis">
                                            <label class="form-check-label" for="nyeri_kronis">Nyeri Kronis</label>
                                        </div>

                                        <label><strong>Integritas Ego</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Ansietas" id="ansietas">
                                            <label class="form-check-label" for="ansietas">Ansietas</label>
                                        </div>

                                        <label><strong>Pertumbuhan dan Perkembangan</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Gangguan Tumbuh Kembang" id="tumbuh_kembang">
                                            <label class="form-check-label" for="tumbuh_kembang">Gangguan Tumbuh Kembang</label>
                                        </div>

                                        <label><strong>Penyuluhan dan Pembelajaran</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Defisit Pengetahuan" id="defisit_pengetahuan">
                                            <label class="form-check-label" for="defisit_pengetahuan">Defisit Pengetahuan</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label><strong>Interaksi Sosial</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Gangguan Komunikasi Verbal" id="komunikasi_verbal">
                                            <label class="form-check-label" for="komunikasi_verbal">Gangguan Komunikasi Verbal</label>
                                        </div>

                                        <label><strong>Keamanan dan Proteksi</strong></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Hipertemia / Hipotermia" id="hiper_hipo">
                                            <label class="form-check-label" for="hiper_hipo">Hipertemia / Hipotermia</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Perlambatan Pemulihan Pasca Bedah" id="pasca_bedah">
                                            <label class="form-check-label" for="pasca_bedah">Perlambatan Pemulihan Pasca Bedah</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Cedera" id="cedera">
                                            <label class="form-check-label" for="cedera">Risiko Cedera</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Infeksi" id="infeksi">
                                            <label class="form-check-label" for="infeksi">Risiko Infeksi</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Jatuh" id="jatuh">
                                            <label class="form-check-label" for="jatuh">Risiko Jatuh</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="masalah_keperawatan[]" value="Risiko Alergi" id="alergi">
                                            <label class="form-check-label" for="alergi">Risiko Alergi</label>
                                        </div>
                                        <div class="form-check d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" name="masalah_keperawatan[]" value="Lain-lain" id="lain">
                                            <label class="form-check-label me-2" for="lain">Lain - Lain</label>
                                            <input type="text" class="form-control" name="lain_lain_text" style="width: 60%;">
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
    // Saat tombol modal ditekan
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    // Toggle semua bagian tergantung status
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

});
</script>
@endsection

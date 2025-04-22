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

<!-- Modal: Riwayat Lab -->
<div class="modal fade" id="modalLab" tabindex="-1" role="dialog" aria-labelledby="modalLabLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabLabel">Riwayat Lab</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Jenis Pemeriksaan</th>
              <th>Hasil</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Radiologi -->
<div class="modal fade" id="modalRadiologi" tabindex="-1" role="dialog" aria-labelledby="modalRadiologiLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRadiologiLabel">Radiologi</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Jenis Radiologi</th>
              <th>Hasil</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Riwayat Kunjungan -->
<div class="modal fade" id="modalKunjungan" tabindex="-1" role="dialog" aria-labelledby="modalKunjunganLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalKunjunganLabel">Riwayat Kunjungan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Poli</th>
              <th>Dokter</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Surat Istirahat -->
<div class="modal fade" id="modalIstirahat" tabindex="-1" role="dialog" aria-labelledby="modalIstirahatLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalIstirahatLabel">Surat Istirahat</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Lama Istirahat</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Surat Mondok -->
<div class="modal fade" id="modalMondok" tabindex="-1" role="dialog" aria-labelledby="modalMondokLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalMondokLabel">Surat Mondok</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Ruangan</th>
              <th>Alasan</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
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
                <!-- Kolom Kiri -->
                <div class="col-md-6">
                    <div class="row mb-1">
                        <div class="col-2 text-end fw-bold">No. RM</div>
                        <div class="col-1">:</div>
                        <div class="col-4"><strong>{{ $visitation->pasien->id ?? '-' }}</strong></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-2 text-end fw-bold">Nama</div>
                        <div class="col-1">:</div>
                        <div class="col-4"><strong>{{ $visitation->pasien->nama ?? '-' }}</strong></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-2 text-end fw-bold">Tgl Lahir</div>
                        <div class="col-1">:</div>
                        <div class="col-4">
                            <strong>{{ $visitation->pasien->tanggal_lahir ? \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir)->format('d-m-Y') : '-' }}</strong>
                        </div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-2 text-end fw-bold">Jenis Kelamin</div>
                        <div class="col-1">:</div>
                        <div class="col-4"><strong>{{ ucfirst($visitation->pasien->gender ?? '-') }}</strong></div>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="col-md-6">
                    <div class="row mb-1">
                        <div class="col-2 text-end fw-bold">NIK</div>
                        <div class="col-1">:</div>
                        <div class="col-4"><strong>{{ $visitation->pasien->nik ?? '-' }}</strong></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-2 text-end fw-bold">No HP</div>
                        <div class="col-1">:</div>
                        <div class="col-4"><strong>{{ ucfirst($visitation->pasien->no_hp ?? '-') }}</strong></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-2 text-end fw-bold">Alamat</div>
                        <div class="col-1">:</div>
                        <div class="col-4"><strong>{{ ucfirst($visitation->pasien->alamat ?? '-') }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex flex-wrap">
                <!-- Tombol E-Resep -->
                <a href="" target="_blank" class="btn btn-outline-primary mr-2 mb-2">
                    E-Resep
                </a>

                <!-- Tombol Modal -->
                <button class="btn btn-outline-success mr-2 mb-2" data-toggle="modal" data-target="#modalLab">
                    Riwayat Lab
                </button>
                <button class="btn btn-outline-info mr-2 mb-2" data-toggle="modal" data-target="#modalRadiologi">
                    Radiologi
                </button>
                <button class="btn btn-outline-warning mr-2 mb-2" data-toggle="modal" data-target="#modalKunjungan">
                    Riwayat Kunjungan
                </button>
                <button class="btn btn-outline-secondary mr-2 mb-2" data-toggle="modal" data-target="#modalIstirahat">
                    Surat Istirahat
                </button>
                <button class="btn btn-outline-danger mr-2 mb-2" data-toggle="modal" data-target="#modalMondok">
                    Surat Mondok
                </button>
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
                                    <label for="riwayatAlergi"><strong>Riwayat Alergi</strong></label>
                                    <div class="d-flex">
                                        <input type="text" id="riwayatAlergi" class="form-control mr-2" value="Tidak Ada" readonly>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAlergi">
                                            Pilih
                                        </button>
                                    </div>
                                </div>                               
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Anamnesis</label><br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="anamnesis[]" id="autoanamnesis" value="Autoanamnesis">
                                        <label class="form-check-label" for="autoanamnesis">Autoanamnesis</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="anamnesis[]" id="alloanamnesis" value="Alloanamnesis">
                                        <label class="form-check-label" for="alloanamnesis">Alloanamnesis</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hubunganDenganPasien">Hubungan dengan Pasien</label>
                                    <input type="text" class="form-control" id="hubunganDenganPasien" name="hubunganDenganPasien">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="keluhanUtama">Keluhan Utama</label>
                                    <input type="text" class="form-control" id="keluhanUtama" name="keluhanUtama">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="riwayatPenyakitSekarang">Riwayat Penyakit Sekarang</label>
                                    <input type="text" class="form-control" id="riwayatPenyakitSekarang" name="riwayatPenyakitSekarang">
                                </div>
                            </div>    
                        </div> 
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="alloanamnesisDengan">Alloanamnesis dengan</label>
                                    <input type="text" class="form-control" id="alloanamnesisDengan" name="alloanamnesisDengan">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hasilAlloanamnesis">Hasil Alloanamnesis</label>
                                    <input type="text" class="form-control" id="hasilAlloanamnesis" name="hasilAlloanamnesis">
                                </div>
                            </div>    
                        </div>  
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="riwayatPenyakitDahulu">Riwayat Penyakit Dahulu</label>
                                    <input type="text" class="form-control" id="riwayatPenyakitDahulu" name="riwayatPenyakitDahulu">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="obatYangDikonsumsi">Obat yang Dikonsumsi</label>
                                    <input type="text" class="form-control" id="obatYangDikonsumsi" name="obatYangDikonsumsi">
                                </div>
                            </div>    
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="gcsE">E (Eye Opening)</label>
                                    <select class="form-control" id="gcsE" name="gcsE">
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
                                    <label for="gcsV">V (Verbal)</label>
                                    <select class="form-control" id="gcsV" name="gcsV">
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
                                    <label for="gcsM">M (Motorik)</label>
                                    <select class="form-control" id="gcsM" name="gcsM">
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
                                    <label for="gcsTotal">Total GCS</label>
                                    <input value="15" type="number" id="gcsTotal" name="gcsTotal" class="form-control" readonly>
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
                                    <td><input type="text" class="form-control" name="extremitasAtas" value="dbn"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>- <em>Extremitas Bawah</em></td>
                                    <td>:</td>
                                    <td><input type="text" class="form-control" name="extremitasBawah" value="dbn"></td>
                                </tr>
                            </tbody>
                        </table>    
                        <div class="form-group row">
                            <!-- Gambar (3 bagian dari total 4) -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <img src="{{ asset('img/dalam-coba.png')}}" class="img-fluid rounded border" alt="Status Lokalis Image">
                                </div>
                            </div>

                            <!-- Textarea (1 bagian dari total 4) -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <textarea class="form-control" rows="5" placeholder="Tulis status lokalis di sini..."></textarea>
                                </div>
                                <!-- Tombol -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-secondary">Reset</button>
                                    <button type="button" class="btn btn-primary">Add</button>
                                </div>
                            </div>
                        </div>

                    </fieldset>


                <h3>Penunjang</h3>
                    <fieldset>
                        <div class="container">
                            <div class="row">
                                <!-- Kolom Kiri -->
                                <div class="col-md-6">
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">KIMIA DARAH</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="gdp">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">SGOT</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="kreatinin">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">SGPT</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="sgot">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">UREUM</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="hb">
                                        </div>
                                    </div>
                                    
                                </div>

                                <!-- Kolom Kanan -->
                                <div class="col-md-6">
                                    <div class="form-group row mb-2">
                                        <label class="col-sm-4 col-form-label">ASAM URAT</label>
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
                                        <label class="col-sm-4 col-form-label">KREATININ</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="trombosit">
                                        </div>
                                    </div>
                                    
                                    
                                    
                                </div>
                            </div>
                           <div class="row mt-3">
    <!-- LAB -->
    <div class="col-md-6 mb-4">
        <label for="upload_lab">Upload Hasil Lab</label>
        <input type="file" class="form-control-file" id="upload_lab" name="upload_lab">
        <small class="form-text text-muted">Lampirkan hasil laboratorium jika ada.</small>
        <textarea class="form-control mt-2" rows="2" name="catatan_lab" placeholder="Catatan tambahan untuk hasil lab..."></textarea>
    </div>

    <!-- X-RAY -->
    <div class="col-md-6 mb-4">
        <label for="upload_xray">Upload X-Ray</label>
        <input type="file" class="form-control-file" id="upload_xray" name="upload_xray">
        <small class="form-text text-muted">Lampirkan hasil rontgen.</small>
        <textarea class="form-control mt-2" rows="2" name="catatan_xray" placeholder="Catatan tambahan untuk X-Ray..."></textarea>
    </div>

    <!-- USG -->
    <div class="col-md-6 mb-4">
        <label for="upload_usg">Upload USG</label>
        <input type="file" class="form-control-file" id="upload_usg" name="upload_usg">
        <small class="form-text text-muted">Lampirkan hasil USG.</small>
        <textarea class="form-control mt-2" rows="2" name="catatan_usg" placeholder="Catatan tambahan untuk USG..."></textarea>
    </div>

    <!-- REKAM JANTUNG -->
    <div class="col-md-6 mb-4">
        <label for="upload_ekg">Upload Rekam Jantung (EKG)</label>
        <input type="file" class="form-control-file" id="upload_ekg" name="upload_ekg">
        <small class="form-text text-muted">Lampirkan hasil rekam jantung (EKG).</small>
        <textarea class="form-control mt-2" rows="2" name="catatan_ekg" placeholder="Catatan tambahan untuk rekam jantung..."></textarea>
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

                           >

                        </div>

                    </fieldset>
                <h3>Tindak Lanjut & Edukasi</h3>
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

        // ⬇️ Tambahkan ini
    enableAllSteps: true,
    forceMoveForward: false,


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

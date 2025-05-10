@extends('layouts.erm.app')

@section('title', 'ERM | Tindakan & Inform Consent')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')
<div class="modal fade" id="modalInjeksiGenue" tabindex="-1" aria-labelledby="injeksiGenueLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="injeksiGenueLabel">Inform Consent - Injeksi Genue</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Tindakan injeksi genue dilakukan untuk menangani nyeri atau peradangan pada area sendi lutut. Risiko tindakan meliputi infeksi, nyeri sementara, dan reaksi alergi terhadap obat. Dengan ini pasien menyetujui tindakan tersebut setelah diberikan penjelasan yang cukup.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary">Saya Setuju</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalSkleroterapi" tabindex="-1" aria-labelledby="skleroterapiLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="skleroterapiLabel">Inform Consent - Injeksi Hemoroid / Skleroterapi</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Skleroterapi hemoroid adalah tindakan penyuntikan bahan tertentu ke dalam hemoroid untuk mengecilkannya. Risiko termasuk nyeri lokal, perdarahan ringan, atau reaksi alergi. Saya menyetujui tindakan ini atas penjelasan dari dokter.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary">Saya Setuju</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalHistoAcril" tabindex="-1" aria-labelledby="histoAcrilLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="histoAcrilLabel">Inform Consent - Injeksi Hemoroid dengan Histo Acril</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Tindakan ini menggunakan bahan perekat (Histo Acril) untuk mengatasi hemoroid. Tindakan ini relatif aman namun dapat menimbulkan nyeri lokal, infeksi, atau reaksi lokal. Saya memahami dan menyetujui prosedur ini.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary">Saya Setuju</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalTenderpoint" tabindex="-1" aria-labelledby="tenderpointLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenderpointLabel">Inform Consent - Injeksi Tenderpoint</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Injeksi tenderpoint adalah penyuntikan pada titik nyeri otot untuk mengurangi nyeri dan spasme. Risiko termasuk nyeri sementara, pendarahan lokal, atau infeksi. Pasien telah diberikan penjelasan dan menyetujui tindakan ini.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary">Saya Setuju</button>
      </div>
    </div>
  </div>
</div>


<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Tindakan & Inform Consent</h3>
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
                            <li class="breadcrumb-item active">Tindakan & Inform Consent</li>
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
            <div class="form-group">
                <input type="text" class="form-control" placeholder="Search for names.." id="searchInformConsent">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" style="color: white">
    <thead class="thead-light text-center">
        <tr>
            <th>No</th>
            <th>Nama Inform Consent</th>
            <th>SMF</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody id="informConsentTable">
        <tr>
            <td class="text-center">1</td>
            <td>INJEKSI GENUE</td>
            <td>Penyakit Dalam</td>
            <td class="text-center">
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalInjeksiGenue">
                    <i class="fa fa-pencil"></i> Buat
                </button>
            </td>
        </tr>
        <tr>
            <td class="text-center">2</td>
            <td>INJEKSI HEMOROID ATAU SKLEROTERAPI HEMOROID (STH)</td>
            <td>Penyakit Dalam</td>
            <td class="text-center">
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalSkleroterapi">
                    <i class="fa fa-pencil"></i> Buat
                </button>
            </td>
        </tr>
        <tr>
            <td class="text-center">3</td>
            <td>Injeksi Hemoroid dengan Histo Acril</td>
            <td>Penyakit Dalam</td>
            <td class="text-center">
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalHistoAcril">
                    <i class="fa fa-pencil"></i> Buat
                </button>
            </td>
        </tr>
        <tr>
            <td class="text-center">4</td>
            <td>Injeksi Tenderpoint</td>
            <td>Penyakit Dalam</td>
            <td class="text-center">
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTenderpoint">
                    <i class="fa fa-pencil"></i> Buat
                </button>
            </td>
        </tr>
    </tbody>
</table>

            </div>
        </div>
    </div> 
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-file-medical-alt mr-2"></i> Riwayat Tindakan & Dokumen Inform Consent</h5>
        </div>
        <div class="card-body">
            <!-- Riwayat Hasil Tindakan -->
            <div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>No</th>
                                <th>Tanggal Dibuat</th>
                                <th>Tanggal OP</th>
                                <th>Nama Tindakan</th>
                                <th>Dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Kosong -->
                            <tr class="text-center">
                                <td colspan="5">Belum ada data tindakan.</td>
                            </tr>
                        </tbody>
                    </table>                  
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

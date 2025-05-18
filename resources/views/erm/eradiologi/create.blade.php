@extends('layouts.erm.app')

@section('title', 'ERM | E-Radiologi')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-permintaanradiologi')

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Radiologi</h3>
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
                            <li class="breadcrumb-item active">E-Radiologi</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    <!-- Riwayat Hasil Radiologi -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-file-medical-alt mr-2"></i> Riwayat Hasil Radiologi</h5>
            <div>
                <button type="button" class="btn btn-primary mr-2">Rontgen</button>
                <button type="button" class="btn btn-primary mr-2">USG</button>
                <button type="button" class="btn btn-primary mr-2">MRI</button>
                <button type="button" class="btn btn-primary mr-2">CT SCAN</button>
            </div>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalPermintaanRadiologi">
                + Tambah Permintaan Radiologi
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th>No</th>
                            <th>Tanggal Kunjungan</th>
                            <th>Kelompok</th>
                            <th>Dokter Baca</th>
                            <th>Dokter Kirim</th>
                            <th>Pemeriksaan</th>
                            <th>Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data Kosong Sementara -->
                        <tr class="text-center">
                            <td colspan="7">Belum ada data radiologi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>  

$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

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
});
</script>    
@endsection

@extends('layouts.erm.app')

@section('title', 'ERM | Riwayat Kunjungan')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Riwayat Kunjungan</h3>
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
                            <li class="breadcrumb-item active">E-Lab</li>
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
            <!-- Riwayat Hasil Laboratorium -->
            <div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="riwayat-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Kunjungan</th>
                                <th>Spesialisasi</th>
                                <th>Dokter</th>
                                <th>Status Pasien</th>                
                                {{-- <th>Tanggal Booking</th> --}}
                                <th>Dokumen</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
    $('#riwayat-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('erm.riwayatkunjungan.index', $pasien) }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', title: 'No', orderable: false, searchable: false },
            
            { data: 'tanggal_visitation', name: 'tanggal_visitation' },
            { data: 'spesialisasi', name: 'spesialisasi', orderable: false }, 
            { data: 'dokter', name: 'dokter', orderable: false }, 
            { data: 'metode', name: 'metodeBayar.nama' },
            { data: 'status_dokumen', name: 'status_dokumen' },
            // { data: 'created_at', name: 'created_at' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ]
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
});
</script>
@endsection
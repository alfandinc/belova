@extends('layouts.erm.app')
@section('title', 'ERM | Data Pasien')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
@include('erm.partials.modal-daftarkunjungan')
@include('erm.partials.modal-info-pasien')

<div class="container-fluid">
                    <!-- Page-Title -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                <div class="row">
                                    <div class="col">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                                            <li class="breadcrumb-item active">Data Pasien</li>
                                        </ol>
                                    </div><!--end col-->
                                    <div class="col-auto align-self-center">
                                        <a href="{{ route('erm.pasiens.create') }}" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus-square mr-2"></i>Pasien Baru
                                        </a>
                                    </div><!--end col-->  
                                </div><!--end row-->                                                              
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div><!--end row-->
                    <!-- end page title end breadcrumb -->

    {{-- Table Pasien --}}
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Pasien</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
    <div class="col-md-2">
        <input type="text" id="filter_no_rm" class="form-control" placeholder="No RM">
    </div>
    <div class="col-md-2">
        <input type="text" id="filter_nama" class="form-control" placeholder="Nama">
    </div>
    <div class="col-md-2">
        <input type="text" id="filter_nik" class="form-control" placeholder="NIK">
    </div>
    <div class="col-md-2">
        <input type="text" id="filter_alamat" class="form-control" placeholder="Alamat">
    </div>
    <div class="col-md-2">
        <button id="btn-filter" class="btn btn-primary"><i class="fas fa-search-plus mr-2"></i>Cari</button>
    </div>
</div>
            <table class="table table-bordered" id="pasiens-table">
                <thead>
                    <tr>
                        <th>No RM</th>
                        <th>Name</th>
                        <th>NIK</th>
                        <th>Alamat</th>
                        <th>No HP</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    let table = $('#pasiens-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        deferLoading: 0, // Prevent initial load
        ajax: {
            url: "{{ route('erm.pasiens.index') }}",
            data: function (d) {
                d.no_rm = $('#filter_no_rm').val();
                d.nama = $('#filter_nama').val();
                d.nik = $('#filter_nik').val();
                d.alamat = $('#filter_alamat').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'nama', name: 'nama' },
            { data: 'nik', name: 'nik' },
            { data: 'alamat', name: 'alamat' },
            { data: 'no_hp', name: 'no_hp' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: 0, width: '50px' },
            { targets: 5, width: '200px' } // Set the width of the "Action" column
        ]
    });

    $('#btn-filter').click(function () {
        table.ajax.reload();
    });

    $(document).on('click', '.btn-info-pasien', function () {
        let pasienId = $(this).data('id');

        $.ajax({
            url: "{{ route('erm.pasien.show', '') }}/" + pasienId, // Fetch patient info
            type: "GET",
            success: function (response) {
                // Populate table cells with response data
                $('#info-no-rm').text(response.id);
                $('#info-nama').text(response.nama);
                $('#info-nik').text(response.nik);
                $('#info-alamat').text(response.alamat);
                $('#info-tanggal-lahir').text(response.tanggal_lahir);
                $('#info-jenis-kelamin').text(response.gender);
                $('#info-agama').text(response.agama);
                $('#info-marital-status').text(response.martial_status);
                $('#info-pendidikan').text(response.pendidikan);
                $('#info-pekerjaan').text(response.pekerjaan);
                $('#info-golongan-darah').text(response.gol_darah);
                $('#info-no-hp').text(response.no_hp);
                $('#info-email').text(response.email);
                $('#info-instagram').text(response.instagram);
                
                // Show the modal
                $('#modalInfoPasien').modal('show');
            },
            error: function () {
                alert("Terjadi kesalahan saat mengambil data pasien.");
            }
        });
    });
});
</script>
@endsection
@extends('layouts.erm.app')

@section('title', 'ERM | Surat Istirahat')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')
<!-- Modal -->
<div class="modal fade" id="modalSurat" tabindex="-1" role="dialog" aria-labelledby="modalSuratLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="formSurat">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalSuratLabel">
                        <i class="fas fa-file-medical"></i> Buat Surat Istirahat
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-user-md"></i> Dokter</label>
                        <select id="dokter_id" name="dokter_id" class="form-control select2" required>
                            @foreach ($dokters as $dokter)
                                <option value="{{ $dokter->id }}"
                                    {{ $dokter->user_id == $dokterUserId ? 'selected' : '' }}>
                                    {{ $dokter->user->name ?? 'Tanpa Nama' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-calculator"></i> Jumlah Hari</label>
                        <input type="text" name="jumlah_hari" class="form-control" readonly>
                        <small class="form-text text-muted">Akan dihitung otomatis berdasarkan tanggal mulai dan selesai</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Surat Istirahat</h3>
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
                            <li class="breadcrumb-item active">Surat Istirahat</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->    @include('erm.partials.card-identitaspasien')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Daftar Surat Istirahat</h4>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#modalSurat">
                            <i class="fas fa-plus"></i> Buat Surat
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="suratTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Dokter</th>
                                    <th>Spesialisasi</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Jumlah Hari</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- container -->


@endsection
@section('scripts')
<script>
$(document).ready(function() {
        // Initialize DataTable with AJAX
    let table = $('#suratTable').DataTable({
        responsive: true,
        autoWidth: false,
        processing: true,
        serverSide: false,
        scrollX: true,
        language: {
            processing: "Memuat data...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            },
            emptyTable: "Tidak ada data surat istirahat"
        },
        ajax: {
            url: '{{ route("erm.suratistirahat.index", $pasien->id) }}', // Same route as index
            type: 'GET',
        },
        columns: [
            { 
                data: 'dokter_name', 
                name: 'dokter_name',
                title: 'Dokter'
            },
            { 
                data: 'spesialisasi', 
                name: 'spesialisasi',
                title: 'Spesialisasi'
            },
            { 
                data: 'tanggal_mulai', 
                name: 'tanggal_mulai',
                title: 'Tanggal Mulai'
            },
            { 
                data: 'tanggal_selesai', 
                name: 'tanggal_selesai',
                title: 'Tanggal Selesai'
            },
            { 
                data: 'jumlah_hari', 
                name: 'jumlah_hari',
                title: 'Jumlah Hari',
                className: 'text-center'
            },
            { 
                data: 'aksi', 
                name: 'aksi', 
                orderable: false, 
                searchable: false,
                title: 'Aksi',
                className: 'text-center'
            },
        ],
        columnDefs: [
            {
                targets: [4, 5], // Jumlah Hari and Aksi columns
                className: 'text-center'
            }
        ]
    });

    $('.select2').select2({ width: '100%' });

    function countDays() {
        let mulai = $('input[name="tanggal_mulai"]').val();
        let selesai = $('input[name="tanggal_selesai"]').val();
        if (mulai && selesai) {
            let start = new Date(mulai);
            let end = new Date(selesai);
            let diff = (end - start) / (1000 * 60 * 60 * 24) + 1;
            $('input[name="jumlah_hari"]').val(diff);
        }
    }

    $('input[name="tanggal_mulai"], input[name="tanggal_selesai"]').on('change', countDays);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#formSurat').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("erm.suratistirahat.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(data) {
                table.ajax.reload(); // Reload DataTable to fetch updated data
                $('#modalSurat').modal('hide');
                $('#formSurat')[0].reset();
            },
            error: function(err) {
                alert("Terjadi kesalahan.");
            }
        });
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


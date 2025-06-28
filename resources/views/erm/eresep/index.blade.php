@extends('layouts.erm.app')
@section('title', 'ERM | E-Resep Farmasi')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Farmasi</li>
                            <li class="breadcrumb-item">E-Resep</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Resep Kunjungan Rawat Jalan</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="filter_tanggal">Filter Tanggal Kunjungan</label>
                    <input type="date" id="filter_tanggal" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_klinik">Filter Klinik</label>
                    <select id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_status_resep">Status Resep</label>
                    <select id="filter_status_resep" class="form-control select2">
                        <option value="0" selected>Belum Dilayani</option>
                        <option value="1">Sudah Dilayani</option>
                    </select>
                </div>
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>No Resep</th> <!-- New column -->
                        <th>Tanggal Kunjungan</th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Nama Dokter</th> <!-- New -->
                        <th>Spesialisasi</th> <!-- New -->
                        <th>Metode Bayar</th>
                        <th>Asesmen Selesai</th> <!-- New column -->
                        <th>Resep</th>
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
    // Set default value tanggal ke hari ini
    var today = new Date().toISOString().substr(0, 10);
    $('#filter_tanggal').val(today);
    $('.select2').select2({ width: '100%' });
    $('#filter_status_resep').val('0').trigger('change'); // set default to 0

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("erm.eresepfarmasi.index") }}',
            data: function(d) {
                d.tanggal = $('#filter_tanggal').val();
                d.dokter_id = $('#filter_dokter').val();
                d.klinik_id = $('#filter_klinik').val();
                d.status_resep = $('#filter_status_resep').val(); // add status_resep
            }
        },
        // order: [[5, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC
        columns: [
            { data: 'no_resep', name: 'no_resep', searchable: true, orderable: false }, // Now searchable
            { data: 'tanggal_visitation', name: 'tanggal_visitation' },
            { data: 'no_rm', searchable: false, orderable: false },
            { data: 'nama_pasien', name: 'nama_pasien', searchable: true, orderable: false }, // Now searchable
            { data: 'nama_dokter', searchable: false, orderable: false }, // New
            { data: 'spesialisasi', searchable: false, orderable: false }, // New
            { data: 'metode_bayar', searchable: false, orderable: false },
            { data: 'asesmen_selesai', name: 'asesmen_selesai', searchable: false, orderable: false }, // New column
            { data: 'dokumen', searchable: false, orderable: false },
            { data: 'status_kunjungan', visible: false, searchable: false } // 🛠️ Sembunyikan
        ],
        columnDefs: [
        { targets: 0, width: "15%" },
        { targets: 1, width: "10%" },
        { targets: 3, width: "20%" },
        { targets: 4, width: "20%" },
        { targets: 7, width: "10%" },
    ],
    });

    // Event ganti filter
    $('#filter_tanggal, #filter_dokter, #filter_klinik, #filter_status_resep').on('change', function () {
        table.ajax.reload();
    });

    // Event ganti tanggal
    $('#filter_tanggal').on('change', function () {
        table.ajax.reload();
    });

    // ambil no antrian otomatis
    $('#reschedule-dokter-id, #reschedule-tanggal-visitation').on('change', function() {
        let dokterId = $('#reschedule-dokter-id').val();
        let tanggal = $('#reschedule-tanggal-visitation').val();

        if (dokterId && tanggal) {
            $.get('{{ route("erm.rawatjalans.cekAntrian") }}', { dokter_id: dokterId, tanggal: tanggal }, function(res) {
                $('#reschedule-no-antrian').val(res.no_antrian);
            });
        }
    });

    // submit form reschedule
    $('#form-reschedule').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("erm.rawatjalans.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modalReschedule').modal('hide');
                $('#rawatjalan-table').DataTable().ajax.reload();
                alert(res.message);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan!');
            }
        });
    });
});

// 🛠️ Fungsi openRescheduleModal dibuat di luar $(document).ready supaya global
function openRescheduleModal(visitationId, namaPasien, pasienId) {
    $('#modalReschedule').modal('show');
    $('#reschedule-visitation-id').val(visitationId);
    $('#reschedule-pasien-id').val(pasienId);
    $('#reschedule-nama-pasien').val(namaPasien);
}

// Notification polling for farmasi users
@auth
@if(auth()->user()->hasRole('Farmasi'))
$(document).ready(function() {
    let lastCheck = 0;
    let isPolling = false;
    
    function checkForNewNotifications() {
        if (isPolling) return;
        isPolling = true;
        
        $.ajax({
            url: '{{ route("erm.check.notifications") }}',
            type: 'GET',
            data: {
                lastCheck: lastCheck
            },
            success: function(response) {
                if (response.hasNew) {
                    // Show SweetAlert notification
                    Swal.fire({
                        title: 'Pasien Keluar!',
                        text: response.message,
                        icon: 'info',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((result) => {
                        // Refresh the DataTable when user clicks "OK"
                        $('#rawatjalan-table').DataTable().ajax.reload();
                    });
                }
                lastCheck = response.timestamp;
            },
            error: function(xhr, status, error) {
                console.error('Error checking for notifications:', error);
            },
            complete: function() {
                isPolling = false;
            }
        });
    }
    
    // Poll every 10 seconds for new notifications
    setInterval(checkForNewNotifications, 10000);
    
    // Check immediately when page loads
    checkForNewNotifications();
    
    // Optional: Check when user focuses on the tab
    $(window).on('focus', function() {
        checkForNewNotifications();
    });
});
@endif
@endauth
</script>


@endsection

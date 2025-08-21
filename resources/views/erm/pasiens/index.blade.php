@extends('layouts.erm.app')
@section('title', 'ERM | Data Pasien')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<style>
/* Status Pasien styling in DataTable */
.status-pasien-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.status-akses-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

.edit-status-btn {
    font-size: 12px;
    padding: 2px;
}

.edit-status-btn:hover {
    background-color: transparent !important;
}

.status-text {
    font-weight: 500;
}
</style>

@include('erm.partials.modal-daftarkunjungan')
@include('erm.partials.modal-daftarkunjunganproduk')
@include('erm.partials.modal-daftarkunjunganlab')
@include('erm.partials.modal-info-pasien')

<!-- Modal Edit Status Pasien -->
<div class="modal fade" id="modalEditStatusPasien" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusPasienLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusPasienLabel">Edit Status Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusPasienForm">
                    <div class="form-group">
                        <label for="edit_status_pasien">Status Pasien</label>
                        <select class="form-control" id="edit_status_pasien" name="status_pasien" required>
                            <option value="Regular">Regular</option>
                            <option value="VIP">VIP</option>
                            <option value="Familia">Familia</option>
                            <option value="Black Card">Black Card</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusPasien">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Status Akses -->
<div class="modal fade" id="modalEditStatusAkses" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusAksesLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusAksesLabel">Edit Status Akses</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusAksesForm">
                    <div class="form-group">
                        <label for="edit_status_akses">Status Akses</label>
                        <select class="form-control" id="edit_status_akses" name="status_akses" required>
                            <option value="normal">Normal</option>
                            <option value="akses cepat">Akses Cepat</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditStatusAkses">Simpan</button>
            </div>
        </div>
    </div>
</div>

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
        <select id="filter_status_pasien" class="form-control">
            <option value="">Semua Status Pasien</option>
            <option value="Regular">Regular</option>
            <option value="VIP">VIP</option>
            <option value="Familia">Familia</option>
            <option value="Black Card">Black Card</option>
        </select>
    </div>
    <div class="col-md-2">
        <select id="filter_status_akses" class="form-control">
            <option value="">Semua Status Akses</option>
            <option value="normal">Normal</option>
            <option value="akses cepat">Akses Cepat</option>
        </select>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-2">
        <button id="btn-filter" class="btn btn-primary"><i class="fas fa-search-plus mr-2"></i>Cari</button>
    </div>
    <div class="col-md-2">
        <button id="btn-reset" class="btn btn-secondary"><i class="fas fa-undo mr-2"></i>Reset</button>
    </div>
</div>
            <table class="table table-bordered table-striped" id="pasiens-table">
                <thead class="text-center font-weight-bold">
                    <tr>
                        <th>No RM</th>
                        <th>Name</th>
                        <th>NIK</th>
                        <th>Alamat</th>
                        <th>No HP</th>
                        <th>Status Pasien</th>
                        <th>Status Akses</th>
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
        stripe: true,    // Enable row striping
        deferLoading: 0, // Prevent initial load
        ajax: {
            url: "{{ route('erm.pasiens.index') }}",
            data: function (d) {
                d.no_rm = $('#filter_no_rm').val();
                d.nama = $('#filter_nama').val();
                d.nik = $('#filter_nik').val();
                d.alamat = $('#filter_alamat').val();
                d.status_pasien = $('#filter_status_pasien').val();
                d.status_akses = $('#filter_status_akses').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'nama', name: 'nama' },
            { data: 'nik', name: 'nik' },
            { data: 'alamat', name: 'alamat' },
            { data: 'no_hp', name: 'no_hp' },
            { data: 'status_pasien', name: 'status_pasien', orderable: false, searchable: false },
            { data: 'status_akses', name: 'status_akses', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: 0, width: '50px' },
            { targets: 5, width: '120px' }, // Status Pasien column
            { targets: 6, width: '120px' }, // Status Akses column
            { targets: 7, width: '250px' } // Action column
        ]
    });

    $('#btn-filter').click(function () {
        table.ajax.reload();
    });

    // Reset button functionality
    $('#btn-reset').click(function () {
        // Clear all filter inputs
        $('#filter_no_rm').val('');
        $('#filter_nama').val('');
        $('#filter_nik').val('');
        $('#filter_alamat').val('');
        $('#filter_status_pasien').val('');
        $('#filter_status_akses').val('');
        
        // Reload table with cleared filters
        table.ajax.reload();
    });

    // Add Enter key functionality to search fields
    $('#filter_no_rm, #filter_nama, #filter_nik, #filter_alamat').on('keypress', function(e) {
        if (e.which === 13) { // Enter key code
            table.ajax.reload();
        }
    });

    // Add change event for select dropdowns
    $('#filter_status_pasien, #filter_status_akses').on('change', function() {
        table.ajax.reload();
    });

    // Optional: Add input event for real-time search (search as you type)
    // Uncomment the lines below if you want search-as-you-type functionality
    /*
    $('#filter_no_rm, #filter_nama, #filter_nik, #filter_alamat').on('input', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            table.ajax.reload();
        }, 500); // 500ms delay after user stops typing
    });
    */
let currentPasienId;
    $(document).on('click', '.btn-info-pasien', function () {
        let pasienId = $(this).data('id');
        currentPasienId = pasienId;

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
    });    $(document).on('click', '#btn-edit-pasien', function() {
        if (currentPasienId) {
            window.location.href = "{{ route('erm.pasiens.create') }}?edit_id=" + currentPasienId;
        }
    });

    // Handle edit status button click
    $(document).on('click', '.edit-status-btn', function() {
        let pasienId = $(this).data('pasien-id');
        let currentStatus = $(this).data('current-status');
        
        $('#edit_status_pasien').val(currentStatus);
        $('#modalEditStatusPasien').data('pasien-id', pasienId);
        $('#modalEditStatusPasien').modal('show');
    });
    
    // Handle save status
    $('#saveEditStatusPasien').on('click', function() {
        let pasienId = $('#modalEditStatusPasien').data('pasien-id');
        let newStatus = $('#edit_status_pasien').val();
        
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_pasien: newStatus
            },
            success: function(response) {
                if(response.success) {
                    $('#modalEditStatusPasien').modal('hide');
                    table.ajax.reload(); // Reload the DataTable
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status pasien.',
                });
            }
        });
    });

    // Handle edit status akses button click
    $(document).on('click', '.edit-status-akses-btn', function() {
        let pasienId = $(this).data('pasien-id');
        let currentStatus = $(this).data('current-status');
        
        $('#edit_status_akses').val(currentStatus);
        $('#modalEditStatusAkses').data('pasien-id', pasienId);
        $('#modalEditStatusAkses').modal('show');
    });
    
    // Handle save status akses
    $('#saveEditStatusAkses').on('click', function() {
        let pasienId = $('#modalEditStatusAkses').data('pasien-id');
        let newStatus = $('#edit_status_akses').val();
        
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status-akses',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_akses: newStatus
            },
            success: function(response) {
                if(response.success) {
                    $('#modalEditStatusAkses').modal('hide');
                    table.ajax.reload(); // Reload the DataTable
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status akses pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status akses pasien.',
                });
            }
        });
    });

});
</script>
@endsection

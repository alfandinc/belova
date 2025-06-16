@extends('layouts.erm.app')

@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Laboratorium</h3>
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

    <!-- Two column layout for Lab Management -->
    <div class="row">
        <!-- Left Column - Permintaan Lab -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center ">
                    <h5 class="mb-0"><i class="fa fa-flask mr-2"></i> Permintaan Laboratorium</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="kategoriFilter">Filter Kategori:</label>
                        <select id="kategoriFilter" class="form-control select2">
                            <option value="">-- Semua Kategori --</option>
                            @foreach($labCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-responsive mt-3">
                        <table id="permintaanLabTable" class="table table-bordered table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama Pemeriksaan</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Riwayat Lab -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-medical-alt mr-2"></i> Riwayat Permintaan Lab</h5>
                    
                    <!-- Total Estimated Price -->
                    <div class="text-right">
                        <div class="h5">Estimasi Total: <span id="totalEstimasi">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span></div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Bulk action buttons -->
                    <div class="mb-3">
                        <div class="btn-group">
                            <button type="button" class="btn btn-danger btn-bulk-delete" disabled>
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                            <button type="button" class="btn btn-info btn-bulk-edit ml-2" disabled data-toggle="dropdown">
                                <i class="fas fa-edit"></i> Edit Status Terpilih
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item bulk-status-option" href="#" data-status="requested">Diminta</a>
                                <a class="dropdown-item bulk-status-option" href="#" data-status="processing">Diproses</a>
                                <a class="dropdown-item bulk-status-option" href="#" data-status="completed">Selesai</a>
                            </div>
                        </div>
                    </div>
                
                    <div class="table-responsive">
                        <table id="riwayatLabTable" class="table table-bordered table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="checkAll">
                                    </th>
                                    <th>Tanggal</th>
                                    <th>Pemeriksaan</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Status Modal -->
<div class="modal fade" id="editStatusModal" tabindex="-1" role="dialog" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStatusModalLabel">Edit Status Permintaan Lab</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusForm">
                    <input type="hidden" id="permintaanId" name="permintaan_id">
                    <div class="form-group">
                        <label for="statusSelect">Status</label>
                        <select class="form-control" id="statusSelect" name="status">
                            <option value="requested">Diminta</option>
                            <option value="processing">Diproses</option>
                            <option value="completed">Selesai</option>
                        </select>
                    </div>
                    <div class="form-group" id="hasilGroup" style="display: none;">
                        <label for="hasilText">Hasil</label>
                        <textarea class="form-control" id="hasilText" name="hasil" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveStatus">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>  
$(document).ready(function () {
    // Initialize select2
    $('.select2').select2();
    
    // Initialize DataTables
    let permintaanTable = $('#permintaanLabTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("erm.elab.tests.data") }}',
            data: function (d) {
                d.kategori_id = $('#kategoriFilter').val();
            }
        },
        columns: [
            { data: 'nama', name: 'nama' },
            { data: 'kategori', name: 'kategori' },
            { data: 'harga_formatted', name: 'harga' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)"
        }
    });
    
    let riwayatTable = $('#riwayatLabTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("erm.elab.requests.data", $visitation->id) }}',
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
            { data: 'tanggal', name: 'created_at' },
            { data: 'nama_pemeriksaan', name: 'nama_pemeriksaan' },
            { data: 'kategori', name: 'kategori' },
            { data: 'status_label', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)"
        }
    });
    
    // Filter by category when selection changes
    $('#kategoriFilter').change(function() {
        permintaanTable.ajax.reload();
    });
    
    // Handle request lab button click using delegation (for dynamically created elements)
    $('#permintaanLabTable').on('click', '.btn-permintaan-lab', function() {
        let testId = $(this).data('id');
        
        $.ajax({
            url: '{{ route("erm.elab.store") }}',
            type: 'POST',
            data: {
                visitation_id: '{{ $visitation->id }}',
                lab_test_id: testId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Update total price
                    $('#totalEstimasi').text(response.totalHargaFormatted);
                    
                    // Reload riwayat table
                    riwayatTable.ajax.reload();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan! Silakan coba lagi.'
                });
                console.error(xhr.responseText);
            }
        });
    });
    
    // Handle delete button click
    $('#riwayatLabTable').on('click', '.btn-delete-permintaan', function() {
    let permintaanId = $(this).data('id');
    
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Permintaan lab akan dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, batalkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        console.log('Swal pertama result:', result);
        if (result.value) {
            $.ajax({
                url: '/erm/elab/permintaan/' + permintaanId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Dibatalkan!',
                            'Permintaan lab berhasil dibatalkan.',
                            'success'
                        );
                        
                        // Update total price
                        $('#totalEstimasi').text(response.totalHargaFormatted);
                        
                        // Reload riwayat table
                        riwayatTable.ajax.reload();
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    Swal.fire(
                        'Error!',
                        'Terjadi kesalahan saat membatalkan permintaan.',
                        'error'
                    );
                }
            });
        }
    });
});
    
    // Handle edit status button click
    $('#riwayatLabTable').on('click', '.btn-edit-status', function() {
        let permintaanId = $(this).data('id');
        $('#permintaanId').val(permintaanId);
        
        // Show modal
        $('#editStatusModal').modal('show');
    });
    
    // Show/hide hasil field based on status selection
    $('#statusSelect').on('change', function() {
        if ($(this).val() === 'completed') {
            $('#hasilGroup').show();
        } else {
            $('#hasilGroup').hide();
        }
    });
    
    // Handle save status button click
    $('#saveStatus').on('click', function() {
        let permintaanId = $('#permintaanId').val();
        let status = $('#statusSelect').val();
        let hasil = $('#hasilText').val();
        
        $.ajax({
            url: '/erm/elab/permintaan/' + permintaanId + '/status',
            type: 'PUT',
            data: {
                status: status,
                hasil: hasil,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#editStatusModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Reload riwayat table
                    riwayatTable.ajax.reload();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan! Silakan coba lagi.'
                });
                console.error(xhr.responseText);
            }
        });
    });
    
    // Handle check all checkbox
    $('#checkAll').on('click', function() {
        $('.permintaan-checkbox').prop('checked', this.checked);
        toggleBulkButtons();
    });
    
    // Handle individual checkbox clicks
    $('#riwayatLabTable').on('click', '.permintaan-checkbox', function() {
        // If any checkbox is unchecked, uncheck the "check all" checkbox
        if (!this.checked) {
            $('#checkAll').prop('checked', false);
        }
        
        // If all checkboxes are checked, check the "check all" checkbox
        if ($('.permintaan-checkbox:checked').length === $('.permintaan-checkbox').length) {
            $('#checkAll').prop('checked', true);
        }
        
        toggleBulkButtons();
    });
    
    // Toggle bulk action buttons based on selection
    function toggleBulkButtons() {
        let anyChecked = $('.permintaan-checkbox:checked').length > 0;
        $('.btn-bulk-delete, .btn-bulk-edit').prop('disabled', !anyChecked);
    }
    
    // Handle bulk delete button click
    $('.btn-bulk-delete').on('click', function() {
    let selectedIds = [];
    $('.permintaan-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Pilih setidaknya satu permintaan lab untuk dibatalkan.'
        });
        return;
    }
    
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: selectedIds.length + " permintaan lab akan dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, batalkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: "{{ route('erm.elab.bulk-delete') }}",
                type: 'POST',
                data: {
                    ids: selectedIds,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Dibatalkan!',
                            response.message,
                            'success'
                        );
                        
                        // Update total price
                        $('#totalEstimasi').text(response.totalHargaFormatted);
                        
                        // Reload riwayat table and reset checkAll
                        $('#checkAll').prop('checked', false);
                        riwayatTable.ajax.reload();
                        toggleBulkButtons();
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    Swal.fire(
                        'Error!',
                        'Terjadi kesalahan saat membatalkan permintaan.',
                        'error'
                    );
                }
            });
        }
    });
});
    
    // Handle bulk edit status options
    $('.bulk-status-option').on('click', function(e) {
    e.preventDefault();
    
    let selectedIds = [];
    $('.permintaan-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Pilih setidaknya satu permintaan lab untuk diubah statusnya.'
        });
        return;
    }
    
    let newStatus = $(this).data('status');
    let statusText = newStatus === 'requested' ? 'Diminta' : 
                    (newStatus === 'processing' ? 'Diproses' : 'Selesai');
    
    Swal.fire({
        title: 'Ubah status?',
        text: "Status " + selectedIds.length + " permintaan lab akan diubah menjadi '" + statusText + "'",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, ubah!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: "{{ route('erm.elab.bulk-update') }}",
                type: 'POST',
                data: {
                    ids: selectedIds,
                    status: newStatus,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Berhasil!',
                            response.message,
                            'success'
                        );
                        
                        // Reload riwayat table and reset checkAll
                        $('#checkAll').prop('checked', false);
                        riwayatTable.ajax.reload();
                        toggleBulkButtons();
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    Swal.fire(
                        'Error!',
                        'Terjadi kesalahan saat mengubah status permintaan.',
                        'error'
                    );
                }
            });
        }
    });
});


});

</script>    
@endsection
@extends('layouts.erm.app')

@section('title', 'ERM | E-Radiologi')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')


@include('erm.partials.modal-radiologi-create')
@include('erm.partials.modal-radiologi-hasil')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
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
        <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center ">
                    <h5 class="mb-0"><i class="fa fa-history mr-2"></i> Dokumen Radiologi</h5>
                </div>
                <div class="card-body">

                    <div class="table-responsive mt-3">
                        <table id="dokumenRadiologiTable" class="table table-bordered table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Dokter Pengirim</th> 
                                    <th>Pemeriksaan</th>    
                                    <th>Hasil</th>
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

    <!-- Two column layout for Radiologi Management -->
    <div class="row">
        <!-- Left Column - Permintaan Radiologi -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center ">
                    <h5 class="mb-0"><i class="fa fa-flask mr-2"></i> Permintaan Radiologi</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="kategoriFilter">Filter Kategori:</label>
                        <select id="kategoriFilter" class="form-control select2">
                            <option value="">-- Semua Kategori --</option>
                            @foreach($radiologiCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-responsive mt-3">
                        <table id="permintaanRadiologiTable" class="table table-bordered table-hover w-100">
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

        <!-- Right Column - Riwayat Radiologi -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-medical-alt mr-2"></i> Riwayat Permintaan Radiologi</h5>

                    <!-- Total Estimated Price -->
                    <div class="text-right">
                        
                        <div class="h5">Estimasi Total: <span id="totalEstimasi">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span></div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Bulk action buttons -->
                    <div class="mb-3">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-danger btn-bulk-delete mr-2" disabled>
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                            <button type="button" class="btn btn-sm btn-info btn-bulk-edit mr-2" disabled data-toggle="dropdown">
                                <i class="fas fa-edit"></i> Edit Status Terpilih
                            </button>
                            <a href="{{ route('erm.eradiologi.print', $visitation->id) }}" target="_blank" class="btn btn-sm btn-primary mr-2">
                            <i class="fas fa-print"></i> Print Permintaan
                        </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item bulk-status-option" href="#" data-status="requested">Diminta</a>
                                <a class="dropdown-item bulk-status-option" href="#" data-status="processing">Diproses</a>
                                <a class="dropdown-item bulk-status-option" href="#" data-status="completed">Selesai</a>
                            </div>
                        </div>
                    </div>
                
                    <div class="table-responsive">
                        <table id="riwayatRadiologiTable" class="table table-bordered table-hover w-100">
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
                <h5 class="modal-title" id="editStatusModalLabel">Edit Status Permintaan Radiologi</h5>
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
    let permintaanTable = $('#permintaanRadiologiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("erm.eradiologi.tests.data") }}',
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

    let riwayatTable = $('#riwayatRadiologiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("erm.eradiologi.requests.data", $visitation->id) }}',
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
    
    // Handle request radiologi button click using delegation (for dynamically created elements)
    $('#permintaanRadiologiTable').on('click', '.btn-permintaan-radiologi', function() {
        let testId = $(this).data('id');
        
        $.ajax({
            url: '{{ route("erm.eradiologi.store") }}',
            type: 'POST',
            data: {
                visitation_id: '{{ $visitation->id }}',
                radiologi_test_id: testId,
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
    $('#riwayatRadiologiTable').on('click', '.btn-delete-permintaan', function() {
    let permintaanId = $(this).data('id');
    
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Permintaan radiologi akan dibatalkan!",
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
                url: '/erm/eradiologi/permintaan/' + permintaanId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Dibatalkan!',
                            'Permintaan radiologi berhasil dibatalkan.',
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
    $('#riwayatRadiologiTable').on('click', '.btn-edit-status', function() {
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
            url: '/erm/eradiologi/permintaan/' + permintaanId + '/status',
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
    $('#riwayatRadiologiTable').on('click', '.permintaan-checkbox', function() {
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
            text: 'Pilih setidaknya satu permintaan radiologi untuk dibatalkan.'
        });
        return;
    }
    
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: selectedIds.length + " permintaan radiologi akan dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, batalkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: "{{ route('erm.eradiologi.bulk-delete') }}",
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
            text: 'Pilih setidaknya satu permintaan radiologi untuk diubah statusnya.'
        });
        return;
    }
    
    let newStatus = $(this).data('status');
    let statusText = newStatus === 'requested' ? 'Diminta' : 
                    (newStatus === 'processing' ? 'Diproses' : 'Selesai');
    
    Swal.fire({
        title: 'Ubah status?',
        text: "Status " + selectedIds.length + " permintaan radiologi akan diubah menjadi '" + statusText + "'",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, ubah!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: "{{ route('erm.eradiologi.bulk-update') }}",
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

let dokumenTable = $('#dokumenRadiologiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("erm.eradiologi.dokumen.data", $visitation->id) }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'tanggal', name: 'tanggal_pemeriksaan' },
            { data: 'dokter_pengirim', name: 'dokter_pengirim' },
            { data: 'nama_pemeriksaan', name: 'nama_pemeriksaan' },
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

    // Add button to add new document
    $('.card-header:first').append(
        '<button class="btn btn-sm btn-success ml-2" id="btn-add-radiologi-dokumen">' +
        '<i class="fas fa-plus"></i> Upload Hasil Radiologi</button>'
    );

    // Handle add radiologi dokumen button click
    $('#btn-add-radiologi-dokumen').click(function() {
        $('#uploadRadiologiModal').modal('show');
    });

    // Handle view radiologi button click
    $('#dokumenRadiologiTable').on('click', '.btn-view-radiologi', function() {
        let hasilId = $(this).data('id');
        
        // Get hasil details
        $.ajax({
            url: '/erm/eradiologi/hasil/' + hasilId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    let hasil = response.data;
                    
                    // Set modal content
                    $('#viewRadiologiNamaPemeriksaan').text(hasil.nama_pemeriksaan);
                    $('#viewRadiologiDokter').text(hasil.dokter_pengirim);
                    $('#viewRadiologiTanggal').text(new Date(hasil.tanggal_pemeriksaan).toLocaleDateString('id-ID'));
                    $('#viewRadiologiDeskripsi').text(hasil.deskripsi || '-');
                    
                    // Set file preview
                    let fileExt = hasil.file_path.split('.').pop().toLowerCase();
                    let filePreview = $('#viewRadiologiFilePreview');
                    filePreview.empty();
                    
                    if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
                        filePreview.html(`<img src="/storage/${hasil.file_path}" class="img-fluid" alt="Radiologi Image">`);
                    } else if (fileExt === 'pdf') {
                        filePreview.html(`
                            <div class="embed-responsive embed-responsive-16by9">
                                <iframe class="embed-responsive-item" src="/storage/${hasil.file_path}" allowfullscreen></iframe>
                            </div>
                        `);
                    } else {
                        filePreview.html(`<p>File tidak dapat ditampilkan. <a href="/storage/${hasil.file_path}" target="_blank">Download</a></p>`);
                    }
                    
                    // Show modal
                    $('#viewRadiologiModal').modal('show');
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

    // Handle upload form submission
    $('#uploadRadiologiForm').submit(function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("erm.eradiologi.hasil.upload") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#uploadRadiologiModal').modal('hide');
                    $('#uploadRadiologiForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Reload dokumen table
                    dokumenTable.ajax.reload();
                }
            },
            error: function(xhr) {
                let errors = xhr.responseJSON.errors;
                let errorMessage = '';
                
                for (let key in errors) {
                    errorMessage += errors[key][0] + '<br>';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Error',
                    html: errorMessage
                });
                
                console.error(xhr.responseText);
            }
        });
    });


});

</script>    
@endsection
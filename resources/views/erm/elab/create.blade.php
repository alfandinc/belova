@extends('layouts.erm.app')

@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-lab-create')
@include('erm.partials.modal-lab-hasil')


@include('erm.partials.modal-alergipasien')
<style>
    .lab-category-box {
        
        margin-bottom: 24px;
        height: 100%;
        padding: 12px;
        box-sizing: border-box;
        
        box-shadow: 0 2px 8px rgba(0,51,102,0.04);
        transition: box-shadow 0.2s;
    }
    .lab-category-box:hover {
        box-shadow: 0 4px 16px rgba(0,51,102,0.10);
    }
    .lab-category-header {
        background: #00509e;
        border-top-left-radius: 7px;
        border-top-right-radius: 7px;
        font-weight: bold;
        border-bottom: 2px solid #00509e;
        letter-spacing: 1px;
        font-size: 1.05em;
        padding-left: 8px;
        padding-right: 8px;
        padding-top: 6px;
        padding-bottom: 6px;
    }
    
    .lab-category-content {
        overflow-y: visible; /* Remove vertical scrollbar */
        padding-top: 8px;
        padding-bottom: 8px;
        box-sizing: border-box;
        padding-left: 8px;
        padding-right: 26px; /* Add extra space for scrollbar */
    }
    
    .form-check {
        margin-bottom: 8px;
        padding: 6px 0 6px 8px;
        /* border-bottom: 1px dashed #cce0f6; */ /* Removed dashed line */
    }
    .form-check:last-child {
        border-bottom: none;
    }
    /* Add spacing between columns */
    .row > [class^='col-'] {
        margin-bottom: 12px;
    }
    .custom-container-padding {
        padding-left: 32px !important;
        padding-right: 32px !important;
    }
</style>

<div class="container-fluid custom-container-padding">
    <!-- Hidden input for visitation ID -->
    <input type="hidden" id="visitationId" value="{{ $visitation->id }}">
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
                    <h5 class="mb-0"><i class="fa fa-history mr-2"></i> Riwayat Hasil Lab</h5>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table id="hasilLabTable" class="table table-bordered table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal Pemeriksaan</th>
                                    <th>Asal Lab</th>  
                                    <th>Pemeriksaan</th>    
                                    <th>Dokter</th> 
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
        <!-- Merged Card: Estimasi Harga & Permintaan Terpilih -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center" style="flex:1;">
                        <h5 class="mb-0 mr-2"><i class="fa fa-flask mr-2"></i> Permintaan Lab</h5>
                    </div>
                    <div class="flex-grow-1 text-center">
                        <span class="h5 mb-0">Estimasi Harga: Rp <span id="estimasiHargaTotal">0</span></span>
                    </div>
                    <div class="d-flex align-items-center" style="flex:1; justify-content: flex-end;">
                        <button type="button" id="submitLabRequests" class="btn btn-sm btn-primary">Simpan Permintaan Lab</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="checkedLabTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Pemeriksaan</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Filled by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="labRequestForm">
                        @csrf
                        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
                        <div class="row">
                            @foreach($labCategories as $category)
                            <div class="col-md-3 mb-3">
                                <div class="lab-category-box">
                                    <div class="lab-category-header text-center text-white py-1">
                                        {{ strtoupper($category->nama) }}
                                    </div>
                                    <div class="lab-category-content p-2">
                                        @foreach($category->labTests as $test)
                                        <div class="form-check">
                                            <input class="form-check-input lab-test-checkbox" type="checkbox" 
                                                id="test-{{ $test->id }}" 
                                                data-id="{{ $test->id }}" 
                                                data-name="{{ $test->nama }}"
                                                data-price="{{ $test->harga }}"
                                                {{ in_array($test->id, $existingLabTestIds) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="test-{{ $test->id }}">
                                                {{ $test->nama }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </form>
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
                            <option value="requested">requested</option>
                            <option value="processing">processing</option>
                            <option value="completed">completed</option>
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
// Define lab test statuses from PHP to JavaScript
var existingLabTestStatuses = @json($existingLabTestStatuses);

$(document).ready(function () {
    // Make it available in the window scope too
    window.existingLabTestStatuses = existingLabTestStatuses;
    
    // Initialize select2
    $('.select2').select2();
    
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
    
    // Handle lab test checkbox clicks
    $('.lab-test-checkbox').on('change', function() {
        let testId = $(this).data('id');
        let testName = $(this).data('name');
        let testPrice = $(this).data('price');
        
        if (this.checked) {
            // Add to lab request
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
                        // Update price and refresh table
                        $('#totalEstimasi').text(response.totalHargaFormatted);
                        riwayatTable.ajax.reload();
                        
                        // Show success message
                        toastr.success('Permintaan lab berhasil ditambahkan');
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    toastr.error('Gagal menambahkan permintaan lab');
                    $(this).prop('checked', false);
                }
            });
        } else {
            // Remove from lab request - find the request in the table first
            let rowData = riwayatTable.rows().data().toArray();
            let requestId = null;
            
            for (let i = 0; i < rowData.length; i++) {
                if (rowData[i].lab_test_id == testId) {
                    // Extract the ID from the checkbox HTML
                    let checkboxHtml = $(rowData[i].checkbox);
                    requestId = checkboxHtml.val();
                    break;
                }
            }
            
            if (requestId) {
                $.ajax({
                    url: '/erm/elab/' + requestId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Update price and refresh table
                        $('#totalEstimasi').text(response.totalHargaFormatted);
                        riwayatTable.ajax.reload();
                        
                        toastr.success('Permintaan lab berhasil dihapus');
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        toastr.error('Gagal menghapus permintaan lab');
                        $(this).prop('checked', true);
                    }
                });
            }
        }
    });
    
    // --- Estimasi Harga calculation ---
    function updateEstimasiHarga() {
        let total = 0;
        $('.lab-test-checkbox:checked').each(function() {
            let price = parseInt($(this).data('price')) || 0;
            total += price;
        });
        // Format as currency (Rp)
        let formatted = total.toLocaleString('id-ID');
        $('#estimasiHargaTotal').text(formatted);
    }

    // Update on page load and whenever a checkbox changes
    updateEstimasiHarga();
    $(document).on('change', '.lab-test-checkbox', updateEstimasiHarga);
    
    // Update checked lab tests table
    function updateCheckedLabTable() {
        let tbody = $('#checkedLabTable tbody');
        tbody.empty();
        let checked = $('.lab-test-checkbox:checked');
        checked.each(function(idx) {
            let name = $(this).data('name');
            let price = parseInt($(this).data('price')) || 0;
            let formatted = price.toLocaleString('id-ID');
            let testId = $(this).data('id');
            // Use old status if exists, otherwise default to 'requested'
            // Convert testId to string to ensure consistent lookup
            let testIdStr = String(testId);
            
            // Find the status - handle both number and string keys
            let selectedStatus = 'requested'; // Default
            if (window.existingLabTestStatuses) {
                if (window.existingLabTestStatuses[testId] !== undefined) {
                    selectedStatus = window.existingLabTestStatuses[testId];
                } else if (window.existingLabTestStatuses[testIdStr] !== undefined) {
                    selectedStatus = window.existingLabTestStatuses[testIdStr];
                }
            }
            
            let statusOptions = `
                <select class="form-control form-control-sm status-select" data-test-id="${testId}">
                    <option value="requested" ${selectedStatus === 'requested' ? 'selected' : ''}>Diminta</option>
                    <option value="processing" ${selectedStatus === 'processing' ? 'selected' : ''}>Diproses</option>
                    <option value="completed" ${selectedStatus === 'completed' ? 'selected' : ''}>Selesai</option>
                </select>
            `;
            let row = `<tr>
                <td>${idx + 1}</td>
                <td>${name}</td>
                <td>Rp ${formatted}</td>
                <td>${statusOptions}</td>
            </tr>`;
            tbody.append(row);
        });
        if (checked.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center text-muted">Belum ada permintaan dipilih</td></tr>');
        }
    }
    // Update on page load and whenever a checkbox changes
    updateCheckedLabTable();
    $(document).on('change', '.lab-test-checkbox', updateCheckedLabTable);
    
    // Force update all status dropdowns to match their actual values
    function forceUpdateStatusDropdowns() {
        $('.status-select').each(function() {
            let testId = $(this).data('test-id');
            let testIdStr = String(testId);
            let selectedStatus = 'requested';
            
            if (window.existingLabTestStatuses) {
                if (window.existingLabTestStatuses[testId] !== undefined) {
                    selectedStatus = window.existingLabTestStatuses[testId];
                } else if (window.existingLabTestStatuses[testIdStr] !== undefined) {
                    selectedStatus = window.existingLabTestStatuses[testIdStr];
                }
            }
            
            $(this).val(selectedStatus);
        });
    }
    
    // Run this after a short delay to ensure all elements are rendered
    setTimeout(forceUpdateStatusDropdowns, 500);
    
    // Handler for status change (AJAX can be added here)
    $(document).on('change', '.status-select', function() {
        let testId = $(this).data('test-id');
        let newStatus = $(this).val();
        // TODO: Add AJAX call here if you want to update status in backend
        // Example:
        // $.post('/erm/elab/permintaan/' + testId + '/status', { status: newStatus, _token: '{{ csrf_token() }}' });
    });
    
    // Handle the submit button for all lab requests
    $('#submitLabRequests').on('click', function() {
        let visitationId = $('#visitationId').val();
        let requests = [];
        $('.lab-test-checkbox').each(function() {
            if (this.checked) {
                let testId = $(this).data('id');
                // Find the status from the corresponding status-select
                let status = $(
                    '.status-select[data-test-id="' + testId + '"]'
                ).val() || 'requested';
                requests.push({
                    lab_test_id: testId,
                    status: status
                });
            }
        });

        $.ajax({
            url: '/erm/elab/permintaan/bulk-update',
            method: 'POST',
            data: {
                visitation_id: visitationId,
                requests: requests,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire('Berhasil', response.message, 'success');
                // Optionally reload table or update UI
            },
            error: function(xhr) {
                Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
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

// Initialize DataTable for hasilLabTable
let hasilLabTable = $('#hasilLabTable').DataTable({
    processing: true,
    serverSide: true,
    searching: false, // Hide search box
    lengthChange: false, // Hide length menu
    ajax: {
        url: '/erm/elab/{{ $visitation->id }}/hasil/data',
    },
    columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false },
        { data: 'tanggal', name: 'tanggal_pemeriksaan' },
        { data: 'asal_lab', name: 'asal_lab' },
        { data: 'nama_pemeriksaan', name: 'nama_pemeriksaan' },
        { data: 'dokter', name: 'dokter' },
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

// Add button to upload new lab result
$('.card-header').first().append(
    '<button id="addLabHasil" class="btn btn-sm btn-primary ml-2">' +
    '<i class="fas fa-plus"></i> Upload Hasil Lab</button>'
);

// Show upload modal when button is clicked
$('#addLabHasil').on('click', function() {
    $('#uploadLabHasilModal').modal('show');
});

// View lab result details
$('#hasilLabTable').on('click', '.btn-view-hasil', function() {
    let hasilId = $(this).data('id');
    
    // Get lab result details
    $.ajax({
        url: '/erm/elab/hasil/' + hasilId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                let hasil = response.data;
                
                // Populate modal with data
                $('#viewLabHasilModal #viewNamaPemeriksaan').text(hasil.nama_pemeriksaan);
                $('#viewLabHasilModal #viewTanggalPemeriksaan').text(moment(hasil.tanggal_pemeriksaan).format('DD-MM-YYYY'));
                $('#viewLabHasilModal #viewAsalLab').text(hasil.asal_lab === 'internal' ? 'Lab Internal' : 'Lab Eksternal');
                $('#viewLabHasilModal #viewDokter').text(hasil.dokter);
                $('#viewLabHasilModal #viewCatatan').text(hasil.catatan || '-');
                
                // Clear the result table first
                $('#viewLabHasilModal #resultTable tbody').empty();
                
                if (hasil.asal_lab === 'internal' && hasil.hasil_detail) {
                    // Populate table with result details
                    $('#viewLabHasilModal #resultTableContainer').show();
                    $('#viewLabHasilModal #pdfViewerContainer').hide();
                    
                    // Add rows to the table
                    $.each(hasil.hasil_detail, function(i, item) {
                        let row = $('<tr>');
                        row.append($('<td>').text(item.nama_test || ''));
                        row.append($('<td>').text(item.flag || ''));
                        row.append($('<td>').text(item.hasil || ''));
                        row.append($('<td>').text(item.satuan || ''));
                        row.append($('<td>').text(item.nilai_rujukan || ''));
                        $('#viewLabHasilModal #resultTable tbody').append(row);
                    });
                } else if (hasil.asal_lab === 'eksternal' && hasil.file_path) {
                    // Show PDF viewer for external lab results
                    $('#viewLabHasilModal #resultTableContainer').hide();
                    $('#viewLabHasilModal #pdfViewerContainer').show();
                    
                    // Use the correct URL for accessing storage files
                    let pdfUrl = '/storage/' + hasil.file_path;
                    $('#viewLabHasilModal #pdfViewer').attr('src', pdfUrl);
                }
                
                // Show the modal
                $('#viewLabHasilModal').modal('show');
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
$('#uploadLabHasilForm').on('submit', function(e) {
    e.preventDefault();
    
    // Additional validation for internal lab
    if ($('#asalLab').val() === 'internal') {
        // Check if at least one row exists
        if ($('#hasilDetailTable tbody tr').length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Mohon tambahkan setidaknya satu hasil lab untuk Lab Internal'
            });
            return false;
        }
    }
    
    // Continue with form submission
    let formData = new FormData(this);
    
    $.ajax({
        url: '/erm/elab/hasil/upload',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#uploadLabHasilModal').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Reset form and reload table
                $('#uploadLabHasilForm')[0].reset();
                hasilLabTable.ajax.reload();
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

// Toggle hasil detail form based on asal lab selection
$('#asalLab').on('change', function() {
    if ($(this).val() === 'internal') {
        // Show internal fields, hide external
        $('#hasilFileGroup').hide();
        $('#hasilDetailGroup').show();
        
        // Make file upload not required
        $('#hasilFile').prop('required', false);
        
        // Make the first row fields required if they exist
        if ($('#hasilDetailTable tbody tr').length > 0) {
            $('#hasilDetailTable tbody tr:first-child input[name$="[nama_test]"]').prop('required', true);
            $('#hasilDetailTable tbody tr:first-child input[name$="[hasil]"]').prop('required', true);
        }
    } else {
        // Show external fields, hide internal
        $('#hasilFileGroup').show();
        $('#hasilDetailGroup').hide();
        
        // Make file upload required
        $('#hasilFile').prop('required', true);
        
        // Remove required from all internal lab fields
        $('#hasilDetailTable tbody input[required]').prop('required', false);
    }
});

$('#asalLab').trigger('change');

// Add a new row to hasil detail table
$('#addHasilDetail').on('click', function() {
        let rowCount = $('#hasilDetailTable tbody tr').length;
        let newRow = `
            <tr>
                <td>
                    <input type="text" class="form-control" name="hasil_detail[${rowCount}][nama_test]" 
                        ${$('#asalLab').val() === 'internal' ? 'required' : ''}>
                </td>
                <td>
                    <select class="form-control" name="hasil_detail[${rowCount}][flag]">
                        <option value="">-</option>
                        <option value="H">H</option>
                        <option value="L">L</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="hasil_detail[${rowCount}][hasil]" 
                        ${$('#asalLab').val() === 'internal' ? 'required' : ''}>
                </td>
                <td>
                    <input type="text" class="form-control" name="hasil_detail[${rowCount}][satuan]">
                </td>
                <td>
                    <input type="text" class="form-control" name="hasil_detail[${rowCount}][nilai_rujukan]">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-detail"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
        $('#hasilDetailTable tbody').append(newRow);
    });

// Remove a row from hasil detail table
$('#hasilDetailTable').on('click', '.remove-detail', function() {
    $(this).closest('tr').remove();
});


});
</script>    
@endsection
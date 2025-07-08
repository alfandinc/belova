@extends('layouts.erm.app')

@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-lis-hasil')
@include('erm.partials.modal-eksternal-hasil')
@include('erm.partials.modal-eksternal-hasil-add')

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
    
    /* Status styling */
    .status-select {
        font-weight: bold;
        border: none !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        width: 100%;
    }
    .status-select option {
        font-weight: bold;
    }
    /* Fix text contrast in badges */
    .badge-warning.text-dark {
        color: #212529 !important;
    }
    /* Style the status display to be clickable */
    .status-display {
        cursor: pointer;
    }
    .status-display .badge {
        cursor: pointer;
        font-size: 0.9rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .status-display:hover .badge {
        filter: brightness(95%);
    }
    /* Container for the status elements */
    .status-container {
        position: relative;
    }
    
    /* Bulk actions styling */
    .bulk-actions {
        border: 1px solid #e0e0e0;
    }
    
    .bulk-status-btn {
        font-weight: bold;
        min-width: 90px;
    }
    
    /* Custom checkboxes for rows */
    .custom-control-input:checked ~ .custom-control-label::before {
        border-color: #007bff;
        background-color: #007bff;
    }

    /* Button pulse animation for unsaved changes */
    @keyframes pulse-animation {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
        }
    }
    
    .btn-pulse {
        animation: pulse-animation 2s infinite;
        position: relative;
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-flask mr-2"></i> <strong>HASIL LAB</strong></h5>
                </div>
                <div class="card-body">
                    <!-- Tabs navigation for lab results -->
                    <ul class="nav nav-tabs" id="labResultsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="hasil-lis-tab" data-toggle="tab" href="#hasil-lis-content" role="tab" aria-controls="hasil-lis-content" aria-selected="true">
                                <i class="fas fa-hospital mr-1"></i> Hasil LIS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="hasil-eksternal-tab" data-toggle="tab" href="#hasil-eksternal-content" role="tab" aria-controls="hasil-eksternal-content" aria-selected="false">
                                <i class="fas fa-file-medical mr-1"></i> Hasil Eksternal
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Tabs content -->
                    <div class="tab-content mt-3" id="labResultsTabsContent">
                        <!-- Hasil LIS Tab -->
                        <div class="tab-pane fade show active" id="hasil-lis-content" role="tabpanel" aria-labelledby="hasil-lis-tab">
                            <div class="table-responsive">
                                <table id="hasilLisTable" class="table table-bordered table-hover w-100">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 5%">No</th>
                                            <th style="width: 20%">Tanggal Kunjungan</th>
                                            <th style="width: 55%">Dokter</th>
                                            <th style="width: 20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be populated by DataTables -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Hasil Eksternal Tab -->
                        <div class="tab-pane fade" id="hasil-eksternal-content" role="tabpanel" aria-labelledby="hasil-eksternal-tab">
                            <div class="d-flex justify-content-end mb-3">
                                <button class="btn btn-sm btn-primary" id="addEksternalHasilBtn">
                                    <i class="fas fa-plus mr-1"></i> Tambah Hasil Lab Eksternal
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table id="hasilEksternalTable" class="table table-bordered table-hover w-100">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 5%">No</th>
                                            <th style="width: 20%">Asal Lab</th>
                                            <th style="width: 20%">Nama Dokter</th>
                                            <th style="width: 20%">Tanggal Periksa</th>
                                            <th style="width: 20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be populated by DataTables -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Merged Card: Estimasi Harga & Permintaan Terpilih with Tabs -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center" style="flex:1;">
                        <h5 class="mb-0 mr-2"><i class="fa fa-flask mr-2"></i> <strong>PERMINTAAN LAB</strong></h5>
                    </div>
                    {{-- <div class="flex-grow-1 text-center">
                        <span class="h5 mb-0">Estimasi Harga: Rp <span id="estimasiHargaTotal">0</span></span>
                    </div> --}}
                    
                </div>
                
                <div class="card-body">
                    <!-- Move Estimasi Harga to the top of the form permintaan lab card -->
                    {{-- <div class="mb-3 text-right">
                        <span class="h5 mb-0">Estimasi Harga: Rp <span id="estimasiHargaTotal">0</span></span>
                    </div> --}}
                    <!-- Tabs navigation -->
                    <ul class="nav nav-tabs" id="labTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="current-tab" data-toggle="tab" href="#current-content" role="tab" aria-controls="current-content" aria-selected="true">
                                <i class="fas fa-clipboard-list mr-1"></i> Permintaan Saat Ini
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="history-tab" data-toggle="tab" href="#history-content" role="tab" aria-controls="history-content" aria-selected="false">
                                <i class="fas fa-history mr-1"></i> Riwayat Permintaan Lab
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Tabs content -->
                    <div class="tab-content mt-3" id="labTabsContent">
                        <!-- Current Lab Requests Tab -->
                        <div class="tab-pane fade show active" id="current-content" role="tabpanel" aria-labelledby="current-tab">
                            <div class="d-flex align-items-center" style="flex:1; justify-content: flex-end; margin-bottom: 10px;">
                                <button type="button" id="submitLabRequests" class="btn btn-sm btn-primary">Simpan Permintaan Lab</button>
                            </div>
                            <!-- Bulk Actions Section -->
                            <div class="bulk-actions mb-3 p-2 bg-light rounded">
                                <div class="d-flex align-items-center">
                                    <div class="custom-control custom-checkbox mr-2">
                                        <input type="checkbox" class="custom-control-input" id="selectAllLab">
                                        <label class="custom-control-label" for="selectAllLab">Pilih Semua</label>
                                    </div>
                                    <div class="ml-3 bulk-status-container" style="display: none;">
                                        <span class="mr-2">Ubah Status:</span>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-warning text-dark bulk-status-btn" data-status="requested">Diminta</button>
                                            <button type="button" class="btn btn-info text-white bulk-status-btn" data-status="processing">Diproses</button>
                                            <button type="button" class="btn btn-success text-white bulk-status-btn" data-status="completed">Selesai</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0" id="checkedLabTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="45%">Pemeriksaan</th>
                                            <th width="20%">Harga</th>
                                            <th width="30%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Filled by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Lab Request History Tab -->
                        <div class="tab-pane fade" id="history-content" role="tabpanel" aria-labelledby="history-tab">
                            <div class="table-responsive">
                                <table id="labHistoryTable" class="table table-bordered table-hover w-100">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 5%">No</th>
                                            <th style="width: 20%">Tanggal Kunjungan</th>
                                            <th style="width: 55%">Dokter</th>
                                            <th style="width: 20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be populated by DataTables -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Estimasi Harga moved here between the two cards above and the form card below -->
    <div class="row">
        <div class="col-12">
            <div class="mb-3 text-center">
                <span class="h3 mb-0 font-weight-bold">Estimasi Harga: Rp <span id="estimasiHargaTotal">0</span></span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-flask mr-2"></i> <strong>FORM PERMINTAAN LAB</strong></h5>
                </div>
                <div class="card-body">
                    <!-- Move Estimasi Harga here -->
                    {{-- <div class="mb-3 text-right">
                        <span class="h5 mb-0">Estimasi Harga: Rp <span id="estimasiHargaTotal">0</span></span>
                    </div> --}}
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

<!-- Lab History Detail Modal -->
<div class="modal fade" id="labHistoryDetailModal" tabindex="-1" role="dialog" aria-labelledby="labHistoryDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labHistoryDetailModalLabel">Detail Permintaan Lab</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="labHistoryDetailTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="30%">Pemeriksaan</th>
                                <th width="15%">Kategori</th>
                                <th width="15%">Harga</th>
                                <th width="15%">Status</th>
                                {{-- <th width="20%">Hasil</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
    
    // Initialize bulk status options visibility
    if ($('.row-checkbox:checked').length > 0) {
        $('.bulk-status-container').show();
    } else {
        $('.bulk-status-container').hide();
    }
    
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
        let checkbox = $(this);
        
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
                        
                        // Show success message using SweetAlert2
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Permintaan lab berhasil ditambahkan',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    
                    // Show error message using SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal menambahkan permintaan lab',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Uncheck the checkbox on error
                    checkbox.prop('checked', false);
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
                        
                        // Show success message using SweetAlert2
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Permintaan lab berhasil dihapus',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        
                        // Show error message using SweetAlert2
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menghapus permintaan lab',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        // Need to use the original element context
                        $('.lab-test-checkbox[data-id="' + testId + '"]').prop('checked', true);
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
        
        // Store existing row checkbox states before updating the table
        let rowCheckStates = {};
        $('.row-checkbox').each(function() {
            let testId = $(this).data('test-id');
            rowCheckStates[testId] = $(this).prop('checked');
        });
        
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
            
            // Create the status display element based on the current status
            let statusDisplay;
            if (selectedStatus === 'requested') {
                statusDisplay = `<span class="badge badge-warning text-dark w-100 py-2">Diminta</span>`;
            } else if (selectedStatus === 'processing') {
                statusDisplay = `<span class="badge badge-info text-white w-100 py-2">Diproses</span>`;
            } else if (selectedStatus === 'completed') {
                statusDisplay = `<span class="badge badge-success text-white w-100 py-2">Selesai</span>`;
            }
            
            // Create dropdown with hidden select
            let statusOptions = `
                <div class="status-container" data-test-id="${testId}">
                    <div class="status-display">${statusDisplay}</div>
                    <select class="form-control form-control-sm status-select d-none" data-test-id="${testId}">
                        <option value="requested" ${selectedStatus === 'requested' ? 'selected' : ''}>Diminta</option>
                        <option value="processing" ${selectedStatus === 'processing' ? 'selected' : ''}>Diproses</option>
                        <option value="completed" ${selectedStatus === 'completed' ? 'selected' : ''}>Selesai</option>
                    </select>
                </div>
            `;
            
            // Determine if the row checkbox should be checked based on previous state
            let isChecked = rowCheckStates[testId] !== undefined ? rowCheckStates[testId] : false;
            
            let row = `<tr>
                <td>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input row-checkbox" id="rowCheck-${testId}" data-test-id="${testId}" ${isChecked ? 'checked' : ''}>
                        <label class="custom-control-label" for="rowCheck-${testId}">${idx + 1}</label>
                    </div>
                </td>
                <td>${name}</td>
                <td>Rp ${formatted}</td>
                <td>${statusOptions}</td>
            </tr>`;
            tbody.append(row);
        });
        
        if (checked.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center text-muted">Belum ada permintaan dipilih</td></tr>');
            $('.bulk-status-container').hide();
        } else {
            // Update select all checkbox based on if all row checkboxes are checked
            updateSelectAllCheckbox();
            
            // Show bulk status options if any row is checked
            if ($('.row-checkbox:checked').length > 0) {
                $('.bulk-status-container').show();
            } else {
                $('.bulk-status-container').hide();
            }
        }
    }
    
    // Function to update the "Select All" checkbox state
    function updateSelectAllCheckbox() {
        const rowCheckboxes = $('.row-checkbox');
        if (rowCheckboxes.length === 0) {
            $('#selectAllLab').prop('checked', false);
            return;
        }
        
        const allChecked = $('.row-checkbox:not(:checked)').length === 0;
        $('#selectAllLab').prop('checked', allChecked);
    }
    // Update on page load and whenever a checkbox changes
    updateCheckedLabTable();
    $(document).on('change', '.lab-test-checkbox', updateCheckedLabTable);
    
    // Handle clicks on the status display to show dropdown
    $(document).on('click', '.status-display', function() {
        // Get the parent container
        let container = $(this).closest('.status-container');
        // Hide the display and show the select
        $(this).hide();
        container.find('.status-select').removeClass('d-none');
        container.find('.status-select').focus();
    });
    
    // Hide select and show display when focus is lost or change is made
    $(document).on('blur change', '.status-select', function() {
        // Get the parent container
        let container = $(this).closest('.status-container');
        // Hide the select
        $(this).addClass('d-none');
        
        // Get the new status and update the display
        let newStatus = $(this).val();
        let statusDisplay;
        
        if (newStatus === 'requested') {
            statusDisplay = `<span class="badge badge-warning text-dark w-100 py-2">Diminta</span>`;
        } else if (newStatus === 'processing') {
            statusDisplay = `<span class="badge badge-info text-white w-100 py-2">Diproses</span>`;
        } else if (newStatus === 'completed') {
            statusDisplay = `<span class="badge badge-success text-white w-100 py-2">Selesai</span>`;
        }
        
        // Update the display and show it
        container.find('.status-display').html(statusDisplay);
        container.find('.status-display').show();
        
        // Show the save reminder
        $('#submitLabRequests').addClass('btn-pulse').removeClass('btn-primary').addClass('btn-warning');
        $('#submitLabRequests').text('Simpan Perubahan');
        
        if (!$('#save-reminder').length) {
            $('<div id="save-reminder" class="text-danger small mt-1 mb-2 text-right">' +
              '<i class="fas fa-exclamation-circle"></i> Jangan lupa klik "Simpan Perubahan" untuk menyimpan' +
              '</div>').insertBefore('#submitLabRequests');
        }
        
        // TODO: Add AJAX call here if you want to update status in backend
        // Example:
        // let testId = $(this).data('test-id');
        // $.post('/erm/elab/permintaan/' + testId + '/status', { status: newStatus, _token: '{{ csrf_token() }}' });
    });
    
    // Handle the submit button for all lab requests
    $('#submitLabRequests').on('click', function() {
        let visitationId = $('#visitationId').val();
        let requestsData = {};
        let hasChanges = false;
        
        // Get all lab tests (both checked and unchecked)
        $('.lab-test-checkbox').each(function() {
            let testId = $(this).data('id');
            let isChecked = $(this).prop('checked');
            
            // If checked, include it in the request with its status
            if (isChecked) {
                // Get status regardless of row checkbox state - we need to save all checked lab tests
                let status = $(
                    '.status-container[data-test-id="' + testId + '"] .status-select'
                ).val() || 'requested';
                
                // Add to requests object with test ID as key
                requestsData[testId] = {
                    status: status
                };
                hasChanges = true;
            }
            // Note: unchecked tests are handled server-side by their absence in the request
        });
        
        // Check if any requests are selected or if we have changes to save
        if (!hasChanges && Object.keys(requestsData).length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Tidak Ada Perubahan',
                text: 'Pilih minimal satu permintaan lab untuk disimpan',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        $.ajax({
            url: '/erm/elab/permintaan/bulk-update',
            method: 'POST',
            data: {
                visitation_id: visitationId,
                requests: requestsData,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire('Berhasil', response.message, 'success');
                // Optionally reload table or update UI
                if (riwayatTable) {
                    riwayatTable.ajax.reload();
                }
                
                // Reset the button state
                $('#submitLabRequests').removeClass('btn-pulse btn-warning').addClass('btn-primary');
                $('#submitLabRequests').text('Simpan Permintaan Lab');
                $('#save-reminder').remove();
            },
            error: function(xhr) {
                console.error(xhr.responseJSON || xhr.responseText);
                Swal.fire('Gagal', 'Terjadi kesalahan: ' + (xhr.responseJSON?.message || 'Unknown error'), 'error');
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
    

    // Initialize HasilLis DataTable
    let hasilLisTable = $('#hasilLisTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // Hide search box
        lengthChange: false, // Hide length menu
        ajax: '/erm/elab/{{ $visitation->id }}/hasil-lis/data',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false },
            { data: 'tanggal', name: 'tanggal_visitation' },
            { data: 'dokter', name: 'dokter' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        language: {
            processing: 'Memproses...',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data yang ditampilkan',
            infoFiltered: '(difilter dari _MAX_ total data)',
            zeroRecords: 'Tidak ada hasil pencarian ditemukan',
            emptyTable: 'Tidak ada data di tabel',
            paginate: {
                first: '<<',
                previous: '<',
                next: '>',
                last: '>>'
            }
        }
    });

    // Handle view HasilLis details button click
    $('#hasilLisTable').on('click', '.btn-view-hasil-lis', function() {
        let visitationId = $(this).data('id');
        
        // Get hasil LIS details
        $.ajax({
            url: '/erm/elab/hasil-lis/' + visitationId,
            method: 'GET',
            beforeSend: function() {
                $('#hasilLisDetailTable tbody').html(
                    '<tr><td colspan="6" class="text-center">Memuat data...</td></tr>'
                );
            },
            success: function(response) {
                // Clear table
                $('#hasilLisDetailTable tbody').empty();
                
                if (response.data.length === 0) {
                    $('#hasilLisDetailTable tbody').html(
                        '<tr><td colspan="6" class="text-center">Tidak ada data hasil</td></tr>'
                    );
                    return;
                }
                
                // Group data by header and sub_header
                var groupedData = {};
                var rowNumber = 1;
                
                $.each(response.data, function(index, item) {
                    // Skip items with empty header
                    if (!item.header) return;
                    
                    if (!groupedData[item.header]) {
                        groupedData[item.header] = {};
                    }
                    
                    // Default to empty string if sub_header is null
                    let subHeader = item.sub_header || '';
                    
                    if (!groupedData[item.header][subHeader]) {
                        groupedData[item.header][subHeader] = [];
                    }
                    
                    groupedData[item.header][subHeader].push(item);
                });
                
                // Populate table with data
                $.each(groupedData, function(header, subHeaders) {
                    // Add header row
                    $('#hasilLisDetailTable tbody').append(`
                        <tr>
                            <td colspan="6" style="background-color: #f8f9fa; font-weight: bold; color: #000; text-transform: uppercase;">${header}</td>
                        </tr>
                    `);
                    
                    $.each(subHeaders, function(subHeader, items) {
                        // Add sub-header row
                        $('#hasilLisDetailTable tbody').append(`
                            <tr>
                                <td colspan="6" style="background-color: #f0f0f0; font-weight: bold; padding-left: 20px; color: green;">${subHeader}</td>
                            </tr>
                        `);
                        
                        // Add items
                        $.each(items, function(i, item) {
                            $('#hasilLisDetailTable tbody').append(`
                                <tr>
                                    <td>${rowNumber}</td>
                                    <td>${item.nama_test || '-'}</td>
                                    <td>${item.hasil || '-'}</td>
                                    <td>${item.flag || '-'}</td>
                                    <td>${item.nilai_rujukan || '-'}</td>
                                    <td>${item.satuan || '-'}</td>
                                </tr>
                            `);
                            rowNumber++;
                        });
                    });
                });
            },
            error: function(xhr, status, error) {
                $('#hasilLisDetailTable tbody').html(
                    '<tr><td colspan="6" class="text-center">Error: ' + error + '</td></tr>'
                );
            }
        });
        
        // Show modal
        $('#hasilLisModal').modal('show');
    });
    
    // Initialize HasilEksternal DataTable
    let hasilEksternalTable = $('#hasilEksternalTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // Hide search box
        lengthChange: false, // Hide length menu
        ajax: '/erm/elab/{{ $visitation->id }}/hasil-eksternal/data',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false },
            { data: 'asal_lab', name: 'asal_lab' },
            { data: 'dokter', name: 'dokter' },
            { data: 'tanggal_pemeriksaan', name: 'tanggal_pemeriksaan' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[3, 'desc']],
        language: {
            processing: 'Memproses...',
            paginate: {
                first: '<<',
                previous: '<',
                next: '>',
                last: '>>'
            }
        }
    });

    // Handle view HasilEksternal details button click
    $('#hasilEksternalTable').on('click', '.btn-view-hasil-eksternal', function() {
        let id = $(this).data('id');
        
        // Get hasil eksternal details
        $.ajax({
            url: '/erm/elab/hasil-eksternal/' + id,
            method: 'GET',
            beforeSend: function() {
                // Show loading state
                $('#hasilEksternalAsalLab').text('Memuat...');
                $('#hasilEksternalNamaPemeriksaan').text('Memuat...');
                $('#hasilEksternalTanggalPemeriksaan').text('Memuat...');
                $('#hasilEksternalDokter').text('Memuat...');
                $('#hasilEksternalCatatan').text('Memuat...');
                $('#pdfViewer').attr('src', '');
            },
            success: function(response) {
                // Populate the modal with data
                $('#hasilEksternalAsalLab').text(response.data.asal_lab);
                $('#hasilEksternalNamaPemeriksaan').text(response.data.nama_pemeriksaan);
                $('#hasilEksternalTanggalPemeriksaan').text(new Date(response.data.tanggal_pemeriksaan).toLocaleDateString('id-ID'));
                $('#hasilEksternalDokter').text(response.data.dokter);
                $('#hasilEksternalCatatan').text(response.data.catatan || '-');
                
                // Set PDF viewer if file exists
                if (response.fileUrl) {
                    $('#pdfViewer').attr('src', response.fileUrl);
                    $('#downloadPdfLink').attr('href', response.fileUrl);
                    $('#pdfViewerContainer').show();
                } else {
                    $('#pdfViewerContainer').hide();
                }
                
                // Show the modal
                $('#hasilEksternalModal').modal('show');
            },
            error: function(xhr) {
                console.error(xhr);
                alert('Gagal mengambil data hasil lab eksternal');
            }
        });
    });

    // Bulk selection handling
    $('#selectAllLab').on('change', function() {
        // Check or uncheck all row checkboxes in the table
        $('.row-checkbox').prop('checked', this.checked);
        
        // Show/hide bulk status options
        if (this.checked && $('.row-checkbox').length > 0) {
            $('.bulk-status-container').show();
        } else {
            $('.bulk-status-container').hide();
        }
    });
    
    // When a lab test checkbox changes, update the table
    $(document).on('change', '.lab-test-checkbox', function() {
        // Update the table and price calculation
        updateCheckedLabTable();
        updateEstimasiHarga();
    });
    
    // Handle row checkboxes
    $(document).on('change', '.row-checkbox', function() {
        // Update the Select All checkbox
        updateSelectAllCheckbox();
        
        // Show/hide bulk status options based on whether any row is checked
        if ($('.row-checkbox:checked').length > 0) {
            $('.bulk-status-container').show();
        } else {
            $('.bulk-status-container').hide();
        }
    });
    
    // Bulk status change
    $('.bulk-status-btn').on('click', function() {
        const newStatus = $(this).data('status');
        let statusText = '';
        
        if (newStatus === 'requested') {
            statusText = 'Diminta';
        } else if (newStatus === 'processing') {
            statusText = 'Diproses';
        } else if (newStatus === 'completed') {
            statusText = 'Selesai';
        }
        
        // Get all checked rows
        const checkedRows = $('.row-checkbox:checked');
        
        // If no rows are checked, show a warning
        if (checkedRows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Item Terpilih',
                text: 'Pilih minimal satu item untuk mengubah status',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }
        
        // Update all checked rows' status
        checkedRows.each(function() {
            const testId = $(this).data('test-id');
            const statusContainer = $(`.status-container[data-test-id="${testId}"]`);
            const statusSelect = statusContainer.find('.status-select');
            
            // Update the select value
            statusSelect.val(newStatus);
            
            // Update the display
            let statusDisplay;
            if (newStatus === 'requested') {
                statusDisplay = `<span class="badge badge-warning text-dark w-100 py-2">Diminta</span>`;
            } else if (newStatus === 'processing') {
                statusDisplay = `<span class="badge badge-info text-white w-100 py-2">Diproses</span>`;
            } else if (newStatus === 'completed') {
                statusDisplay = `<span class="badge badge-success text-white w-100 py-2">Selesai</span>`;
            }
            
            statusContainer.find('.status-display').html(statusDisplay);
        });
        
        // Show a subtle notification that doesn't block interaction
        $('#submitLabRequests').addClass('btn-pulse').removeClass('btn-primary').addClass('btn-warning');
        
        // Add a tooltip or change the button text to indicate changes need to be saved
        $('#submitLabRequests').text('Simpan Perubahan');
        
        // Create a small notification above the button
        if (!$('#save-reminder').length) {
            $('<div id="save-reminder" class="text-danger small mt-1 mb-2 text-right">' +
              '<i class="fas fa-exclamation-circle"></i> Jangan lupa klik "Simpan Perubahan" untuk menyimpan' +
              '</div>').insertBefore('#submitLabRequests');
        }
    });
    
    // Initialize Lab History DataTable when history tab is shown
    $('#history-tab').on('shown.bs.tab', function (e) {
        if (!$.fn.DataTable.isDataTable('#labHistoryTable')) {
            // Get the patient ID from the visitation's pasien relationship - as a string to preserve leading zeros
            let pasienId = '{{ $visitation->pasien->id }}';
            
            // Initialize DataTable for lab history
            $('#labHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                ajax: '/erm/elab/patient/' + pasienId + '/history',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false },
                    { data: 'tanggal', name: 'tanggal_visitation' },
                    { data: 'dokter', name: 'dokter' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']],
                language: {
                    processing: 'Memproses...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data yang ditampilkan',
                    infoFiltered: '(difilter dari _MAX_ total data)',
                    zeroRecords: 'Tidak ada hasil pencarian ditemukan',
                    emptyTable: 'Tidak ada data di tabel',
                    paginate: {
                        first: '<<',
                        previous: '<',
                        next: '>',
                        last: '>>'
                    }
                }
            });
        }
    });
    
    // Handle view lab history detail button click
    $(document).on('click', '.btn-view-lab-history', function() {
        let visitationId = $(this).data('id');
        
        // Get lab history details for this visitation
        $.ajax({
            url: '/erm/elab/visitation/' + visitationId + '/detail',
            method: 'GET',
            beforeSend: function() {
                $('#labHistoryDetailTable tbody').html(
                    '<tr><td colspan="6" class="text-center">Memuat data...</td></tr>'
                );
            },
            success: function(response) {
                // Clear table
                $('#labHistoryDetailTable tbody').empty();
                
                if (response.data.length === 0) {
                    $('#labHistoryDetailTable tbody').html(
                        '<tr><td colspan="6" class="text-center">Tidak ada data permintaan lab</td></tr>'
                    );
                    return;
                }
                
                // Populate table with data
                $.each(response.data, function(index, item) {
                    $('#labHistoryDetailTable tbody').append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.nama_pemeriksaan}</td>
                            <td>${item.kategori}</td>
                            <td>${item.harga}</td>
                            <td>${item.status_label}</td>
                            
                        </tr>
                    `);
                });
            },
            error: function(xhr, status, error) {
                $('#labHistoryDetailTable tbody').html(
                    '<tr><td colspan="6" class="text-center">Error: ' + error + '</td></tr>'
                );
            }
        });
        
        // Show modal
        $('#labHistoryDetailModal').modal('show');
    });
    
    // Handle adding new external lab results
    $('#addEksternalHasilBtn').on('click', function() {
        // Reset the form
        $('#addEksternalHasilForm')[0].reset();
        // Set default date to today
        $('#tanggal_pemeriksaan').val(new Date().toISOString().split('T')[0]);
        // Show the modal
        $('#addEksternalHasilModal').modal('show');
    });
    
    // Handle saving external lab results
    $('#saveEksternalHasil').on('click', function() {
        // Create FormData object to handle file upload
        let formData = new FormData($('#addEksternalHasilForm')[0]);
        
        // Submit the form
        $.ajax({
            url: '{{ route("erm.elab.hasil-eksternal.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                // Disable the save button
                $('#saveEksternalHasil').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
            },
            success: function(response) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Hasil lab eksternal berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Refresh the data table
                hasilEksternalTable.ajax.reload();
                
                // Close the modal
                $('#addEksternalHasilModal').modal('hide');
            },
            error: function(xhr) {
                // Show error message
                let message = 'Terjadi kesalahan saat menyimpan hasil lab eksternal';
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: message
                });
            },
            complete: function() {
                // Re-enable the save button
                $('#saveEksternalHasil').prop('disabled', false).html('Simpan');
            }
        });
    });
});
</script>   
 
@endsection
@extends('layouts.erm.app')

@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')
@include('erm.partials.modal-lis-hasil')

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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-flask mr-2"></i> Riwayat Hasil LAB Pasien</h5>
                </div>
                <div class="card-body">
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

});
</script>   
 
@endsection
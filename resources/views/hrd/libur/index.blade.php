@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Cuti/Libur')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
@section('content')
<div class="container-fluid px-2">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box">
                        <div class="row">
                            <div class="col">
                                <h4 class="page-title">Pengajuan Cuti/Libur</h4>
                            </div>
                            <div class="col-auto align-self-center">
                                    <input type="text" id="dateRange" class="form-control form-control-sm d-inline-block mr-2" style="width: 260px;" placeholder="Filter tanggal" />
                                <a href="#" class="btn btn-sm btn-primary" id="btnCreateLibur">
                                    <i class="fas fa-plus-circle mr-2"></i>Buat Pengajuan Baru
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
                /* Hide any dynamically added "akan mengajukan libur" messages */
                [id$="day-info"], 
                div:contains("Anda akan mengajukan libur"),
                p:contains("Anda akan mengajukan libur") {
                    display: none !important;
                }
            </style>
            
            @if(auth()->user()->hasRole('Employee'))
                @include('hrd.libur.karyawan-index')
            @elseif(auth()->user()->hasRole('Manager'))
                @if(isset($viewType) && $viewType == 'team')
                    @include('hrd.libur.manager-index')
                @else
                    @include('hrd.libur.karyawan-index')
                @endif
            @elseif(auth()->user()->hasRole('Hrd'))
                @include('hrd.libur.hrd-index')
            @endif

        </div>

<!-- Modal Create Pengajuan -->
<div class="modal fade" id="modalCreateLibur" tabindex="-1" role="dialog" aria-labelledby="modalCreateLiburLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateLiburLabel">Form Pengajuan Cuti/Libur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateLibur">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Jenis Cuti/Libur <span class="text-danger">*</span></label>
                        <select class="form-control" name="jenis_libur" id="jenis_libur" required>
                            <option value="">Pilih Jenis</option>
                            <option value="cuti_tahunan">Cuti Tahunan</option>
                            <option value="ganti_libur">Ganti Libur</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_selesai" id="tanggal_selesai" required>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i> Catatan: Jumlah hari dihitung secara inklusif termasuk tanggal awal dan akhir.
                        <br>Contoh: Libur 1 hari (1 Jan) - isi tanggal mulai dan selesai sama: 1 Jan.
                        <br>Contoh: Libur 4 hari (9-12 Jan) - isi tanggal mulai: 9 Jan, tanggal selesai: 12 Jan.
                    </div>
                    
                    <div class="form-group">
                        <label>Alasan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan" id="alasan" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <p class="mb-0"><strong>Jatah Cuti Tahunan:</strong> <span id="jatahCutiTahunan">{{ auth()->user()->employee->jatahLibur->jatah_cuti_tahunan ?? 0 }} hari</span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <p class="mb-0"><strong>Saldo Ganti Libur:</strong> <span id="jatahGantiLibur">{{ auth()->user()->employee->jatahLibur->jatah_ganti_libur ?? 0 }} hari</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitLibur">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Pengajuan -->
<div class="modal fade" id="modalDetailLibur" tabindex="-1" role="dialog" aria-labelledby="modalDetailLiburLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailLiburLabel">Detail Pengajuan Cuti/Libur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailLiburBody">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Approval Manager -->
<div class="modal fade" id="modalApprovalManager" tabindex="-1" role="dialog" aria-labelledby="modalApprovalManagerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalApprovalManagerLabel">Persetujuan Manager</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalManager">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="pengajuan_id" id="manager_pengajuan_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="status_manager" required>
                            <option value="">Pilih Status</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea class="form-control" name="komentar_manager" id="komentar_manager" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitApprovalManager">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Approval HRD -->
<div class="modal fade" id="modalApprovalHRD" tabindex="-1" role="dialog" aria-labelledby="modalApprovalHRDLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalApprovalHRDLabel">Persetujuan HRD</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalHRD">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="pengajuan_id" id="hrd_pengajuan_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="status_hrd" required>
                            <option value="">Pilih Status</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea class="form-control" name="komentar_hrd" id="komentar_hrd" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitApprovalHRD">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<!-- daterangepicker (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<style>
/* Hide the "Anda akan mengajukan libur" messages outside of modal */
.container-fluid .alert-info p.mb-0 + .hari-info,
.container-fluid .alert-info div:contains("Anda akan mengajukan libur") {
    display: none !important;
}
</style>
<script>
$(document).ready(function() {
    // Init Date Range Picker with default (this month to end of next month)
    var drpStart = moment("{{ isset($defaultDateStart) ? $defaultDateStart : now()->startOfMonth()->toDateString() }}");
    var drpEnd = moment("{{ isset($defaultDateEnd) ? $defaultDateEnd : now()->addMonthNoOverflow()->endOfMonth()->toDateString() }}");

    $('#dateRange').daterangepicker({
        startDate: drpStart,
        endDate: drpEnd,
        autoApply: true,
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - '
        },
        ranges: {
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
            's.d Bulan Depan': [moment().startOf('month'), moment().add(1,'month').endOf('month')],
            '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
            '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
            'Bulan Depan': [moment().add(1,'month').startOf('month'), moment().add(1,'month').endOf('month')]
        }
    }, function(start, end) {
        drpStart = start;
        drpEnd = end;
        // Reload all tables that exist
        if (typeof tableKaryawan !== 'undefined' && $.fn.dataTable.isDataTable('#tableLiburKaryawan')) {
            tableKaryawan.ajax.reload();
        }
        if (typeof tableManager !== 'undefined' && $.fn.dataTable.isDataTable('#tableLiburManager')) {
            tableManager.ajax.reload();
        }
        if (typeof tableHRD !== 'undefined' && $.fn.dataTable.isDataTable('#tableLiburHRD')) {
            tableHRD.ajax.reload();
        }
    });

    // Remove any "Anda akan mengajukan libur" text from the main page alerts
    $('.page-content .alert-info').each(function() {
        $(this).find('div, p').each(function() {
            if ($(this).text().indexOf('Anda akan mengajukan libur') !== -1) {
                $(this).remove();
            }
        });
    });
    
    // Also clean up any dynamically added elements with specific IDs
    $('[id$="day-info"]').not('#modalCreateLibur [id$="day-info"]').remove();
    // Initialize DataTable for Employee
    var tableKaryawan = $('#tableLiburKaryawan').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('hrd.libur.index') }}?view=personal",
            data: function(d) {
                d.date_start = drpStart.format('YYYY-MM-DD');
                d.date_end = drpEnd.format('YYYY-MM-DD');
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'jenis_libur', name: 'jenis_libur'},
            {data: 'tanggal_range', name: 'tanggal_range', orderable: false, searchable: false},
            {data: 'total_hari', name: 'total_hari'},
            {data: 'status_manager', name: 'status_manager'},
            {data: 'status_hrd', name: 'status_hrd'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    // Initialize DataTable for Manager
    var tableManager = $('#tableLiburManager').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('hrd.libur.index') }}?view=team",
            data: function(d) {
                d.date_start = drpStart.format('YYYY-MM-DD');
                d.date_end = drpEnd.format('YYYY-MM-DD');
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee.nama', name: 'employee.nama'},
            {data: 'jenis_libur', name: 'jenis_libur'},
            {data: 'tanggal_range', name: 'tanggal_range', orderable: false, searchable: false},
            {data: 'total_hari', name: 'total_hari'},
            {data: 'status_pengajuan', name: 'status_pengajuan', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    // Initialize DataTable for HRD
    var tableHRD = $('#tableLiburHRD').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('hrd.libur.index') }}?view=approval",
            data: function(d) {
                d.date_start = drpStart.format('YYYY-MM-DD');
                d.date_end = drpEnd.format('YYYY-MM-DD');
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee.nama', name: 'employee.nama'},
            {data: 'jenis_libur', name: 'jenis_libur'},
            {data: 'tanggal_range', name: 'tanggal_range', orderable: false, searchable: false},
            {data: 'total_hari', name: 'total_hari'},
            {data: 'status_pengajuan', name: 'status_pengajuan', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    // Create modal
    $('#btnCreateLibur').click(function() {
        $('#formCreateLibur')[0].reset();
        $('#modalCreateLibur').modal('show');
    });
    
    // Submit create form
    $('#formCreateLibur').submit(function(e) {
        e.preventDefault();
        // Validate dates are not before today and end >= start
        var startDateVal = $('#tanggal_mulai').val();
        var endDateVal = $('#tanggal_selesai').val();
        var todayStart = new Date();
        todayStart.setHours(0,0,0,0);
        if (startDateVal) {
            var startDate = new Date(startDateVal + 'T00:00:00');
            if (startDate < todayStart) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Tanggal mulai tidak boleh sebelum hari ini',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
        }
        if (endDateVal) {
            var endDate = new Date(endDateVal + 'T00:00:00');
            if (endDate < todayStart) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Tanggal selesai tidak boleh sebelum hari ini',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
        }
        if (startDateVal && endDateVal) {
            var startDate2 = new Date(startDateVal + 'T00:00:00');
            var endDate2 = new Date(endDateVal + 'T00:00:00');
            if (endDate2 < startDate2) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Tanggal selesai tidak boleh sebelum tanggal mulai',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
        }

        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('hrd.libur.store') }}",
            type: "POST",
            data: formData,
            beforeSend: function() {
                $('#btnSubmitLibur').attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            },
            success: function(response) {
                $('#modalCreateLibur').modal('hide');
                $('#formCreateLibur')[0].reset();

                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Pengajuan libur berhasil diajukan',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
                
                if(typeof tableKaryawan !== 'undefined') {
                    tableKaryawan.ajax.reload();
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMessage = '';
                
                $.each(errors, function(key, value) {
                    errorMessage += value[0] + '<br>';
                });
                
                Swal.fire({
                    title: 'Gagal!',
                    html: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $('#btnSubmitLibur').attr('disabled', false).html('Ajukan');
            }
        });
    });
    
    // Show detail modal
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('hrd/libur') }}/" + id,
            type: "GET",
            success: function(response) {
                $('#modalDetailLiburBody').html(response);
                $('#modalDetailLibur').modal('show');
            }
        });
    });
    
    // Show manager approval modal
    $(document).on('click', '.btn-approve-manager', function() {
        var id = $(this).data('id');
        $('#manager_pengajuan_id').val(id);
        $('#formApprovalManager')[0].reset();
        
        // Fetch current status and notes
        $.ajax({
            url: "{{ url('hrd/libur') }}/" + id + "/approval-status",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#status_manager').val(data.status_manager);
                    $('#komentar_manager').val(data.komentar_manager);
                }
                $('#modalApprovalManager').modal('show');
            },
            error: function() {
                $('#modalApprovalManager').modal('show');
            }
        });
    });
    
    // Submit manager approval
    $('#formApprovalManager').submit(function(e) {
        e.preventDefault();
        
        var id = $('#manager_pengajuan_id').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ url('hrd/libur') }}/" + id + "/manager",
            type: "PUT",
            data: formData,
            beforeSend: function() {
                $('#btnSubmitApprovalManager').attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            },
            success: function(response) {
                $('#modalApprovalManager').modal('hide');
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Status pengajuan berhasil diperbarui',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
                
                if(typeof tableManager !== 'undefined') {
                    tableManager.ajax.reload();
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMessage = '';
                
                $.each(errors, function(key, value) {
                    errorMessage += value[0] + '<br>';
                });
                
                Swal.fire({
                    title: 'Gagal!',
                    html: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $('#btnSubmitApprovalManager').attr('disabled', false).html('Simpan');
            }
        });
    });
    
    // Show HRD approval modal
    $(document).on('click', '.btn-approve-hrd', function() {
        var id = $(this).data('id');
        $('#hrd_pengajuan_id').val(id);
        $('#formApprovalHRD')[0].reset();
        
        // Fetch current status and notes
        $.ajax({
            url: "{{ url('hrd/libur') }}/" + id + "/approval-status",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#status_hrd').val(data.status_hrd);
                    $('#komentar_hrd').val(data.komentar_hrd);
                }
                $('#modalApprovalHRD').modal('show');
            },
            error: function() {
                $('#modalApprovalHRD').modal('show');
            }
        });
    });
    
    // Submit HRD approval
    $('#formApprovalHRD').submit(function(e) {
        e.preventDefault();
        
        var id = $('#hrd_pengajuan_id').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ url('hrd/libur') }}/" + id + "/hrd",
            type: "PUT",
            data: formData,
            beforeSend: function() {
                $('#btnSubmitApprovalHRD').attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            },
            success: function(response) {
                $('#modalApprovalHRD').modal('hide');
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Status pengajuan berhasil diperbarui',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
                
                if(typeof tableHRD !== 'undefined') {
                    tableHRD.ajax.reload();
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMessage = '';
                
                $.each(errors, function(key, value) {
                    errorMessage += value[0] + '<br>';
                });
                
                Swal.fire({
                    title: 'Gagal!',
                    html: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $('#btnSubmitApprovalHRD').attr('disabled', false).html('Simpan');
            }
        });
    });
    
    // Date validation and day calculation
    function updateDayCount() {
        var startDateVal = $('#tanggal_mulai').val();
        var endDateVal = $('#tanggal_selesai').val();
        
        if (startDateVal && endDateVal) {
            var startDate = new Date(startDateVal + 'T00:00:00'); // Add time to ensure proper date handling
            var endDate = new Date(endDateVal + 'T00:00:00');
            
            // Calculate days between dates (inclusive)
            // Force absolute value to ensure positive number regardless of date order
            var diffTime = Math.abs(endDate - startDate);
            var diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end date
            
            // Safety check - ensure at least 1 day
            if (diffDays < 1) {
                diffDays = 1;
            }
            
            // Show message with day count only in the modal's info alert about calculation
            var infoMessage = 'Anda akan mengajukan libur selama <b>' + diffDays + ' hari</b>.';
            
            // Find the alert in the modal that contains the calculation info
            var $targetAlert = $('#modalCreateLibur .alert-info').filter(function() {
                return $(this).text().indexOf('Jumlah hari dihitung secara inklusif') !== -1;
            });
            
            if (!$('#day-info').length) {
                // Only append to the alert in the modal that explains the calculation
                $targetAlert.append('<div id="day-info" class="mt-2">' + infoMessage + '</div>');
            } else {
                $('#day-info').html(infoMessage);
            }
        }
    }

    
    $('#tanggal_selesai').change(function() {
        var startDate = new Date($('#tanggal_mulai').val());
        var endDate = new Date($(this).val());
        var todayStart = new Date();
        todayStart.setHours(0,0,0,0);

        if (endDate < todayStart) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Tanggal selesai tidak boleh sebelum hari ini',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            $(this).val('');
            return;
        }
        
        if (endDate < startDate) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Tanggal selesai tidak boleh sebelum tanggal mulai',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            $(this).val('');
        } else {
            updateDayCount();
        }
    });
    
    $('#tanggal_mulai').change(function() {
        var startDate = new Date($(this).val());
        var endDateInput = $('#tanggal_selesai');
        var todayStart = new Date();
        todayStart.setHours(0,0,0,0);

        if (startDate < todayStart) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Tanggal mulai tidak boleh sebelum hari ini',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            $(this).val('');
            return;
        }
        
        if (endDateInput.val()) {
            var endDate = new Date(endDateInput.val());
            
            if (endDate < startDate) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Tanggal mulai tidak boleh setelah tanggal selesai',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                $(this).val('');
            } else {
                updateDayCount();
            }
        }
    });
});
</script>
@endsection
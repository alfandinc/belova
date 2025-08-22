@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <h2>Rekap Absensi</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4" id="statisticsCards">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Records
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRecords">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Late Employees
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="lateCount">0</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="text-xs font-weight-bold text-danger mr-3" id="latePercentage">0%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-danger" role="progressbar" id="lateProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Overtime Employees
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overtimeCount">0</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="text-xs font-weight-bold text-warning mr-3" id="overtimePercentage">0%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-warning" role="progressbar" id="overtimeProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-business-time fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                On Time Employees
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="onTimeCount">0</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="text-xs font-weight-bold text-success mr-3" id="onTimePercentage">0%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-success" role="progressbar" id="onTimeProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="form-group mb-0">
                <label for="dateRange">Filter Tanggal:</label>
                <input type="text" id="dateRange" class="form-control" placeholder="Pilih rentang tanggal" autocomplete="off">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group mb-0">
                <label for="employeeFilter">Filter Karyawan:</label>
                <select id="employeeFilter" class="form-control">
                    <option value="">Semua Karyawan</option>
                    @foreach(\App\Models\HRD\Employee::orderBy('nama')->get() as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 ml-auto">
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                    <div class="custom-file">
                        <input type="file" name="file" id="file" class="custom-file-input" required>
                        <label class="custom-file-label" for="file">Choose XLS file</label>
                    </div>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Sync Shifts Button -->
    <div class="row mb-3">
        <div class="col-md-8">
            <button id="syncShiftsBtn" class="btn btn-warning mr-2" data-toggle="tooltip" data-placement="top" 
                    title="Sync shift data and recalculate work hours for overnight shifts">
                <i class="fas fa-sync"></i> Sync Shift Data & Work Hours
            </button>
            <button id="reprocessTimesBtn" class="btn btn-info" data-toggle="tooltip" data-placement="top" 
                    title="Reprocess attendance times using smart selection (closest to shift schedule)">
                <i class="fas fa-clock"></i> Smart Time Selection
            </button>
            <br>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                <strong>Sync:</strong> Fix overnight shift calculations &nbsp;|&nbsp; 
                <strong>Smart Selection:</strong> Choose best jam masuk/keluar for night shifts
            </small>
        </div>
    </div>
    
    <hr>
    <table id="rekapTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Finger ID</th>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Shift</th>
                <th>Work Hour</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Jam Masuk & Jam Keluar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId">
                    <div class="form-group">
                        <label for="editJamMasuk">Jam Masuk</label>
                        <input type="time" class="form-control" id="editJamMasuk" required>
                    </div>
                    <div class="form-group">
                        <label for="editJamKeluar">Jam Keluar</label>
                        <input type="time" class="form-control" id="editJamKeluar" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">Simpan</button>
            </div>
        </div>
    </div>
</div>
        </thead>
    </table>
</div>

@push('styles')
<style>
/* Bootstrap 4 Dashboard Cards */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.text-xs {
    font-size: 0.7rem;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.card {
    transition: all 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2) !important;
}

#statisticsCards .card {
    cursor: pointer;
}

/* Responsive text sizing */
@media (max-width: 768px) {
    .h5 {
        font-size: 1.1rem;
    }
    
    .fa-2x {
        font-size: 1.5em;
    }
}

/* Animation for number updates */
@keyframes countUp {
    from { opacity: 0.5; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

.count-update {
    animation: countUp 0.5s ease-out;
}

/* Progress bars for percentages */
.progress-sm {
    height: 0.5rem;
}

.progress-xs {
    height: 0.25rem;
}
</style>
@endpush

@push('scripts')
<script>
$(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Function to load statistics
    function loadStatistics() {
        $.ajax({
            url: '{{ route('hrd.absensi_rekap.statistics') }}',
            type: 'GET',
            data: {
                date_range: $('#dateRange').val(),
                employee_ids: $('#employeeFilter').val()
            },
            success: function(response) {
                console.log('Statistics request data:', {
                    date_range: $('#dateRange').val(),
                    employee_ids: $('#employeeFilter').val()
                });
                console.log('Statistics response:', response);
                
                // Add animation class and update numbers
                $('#totalRecords').addClass('count-update').text(response.total_records);
                $('#lateCount').addClass('count-update').text(response.late_count);
                $('#overtimeCount').addClass('count-update').text(response.overtime_count);
                $('#onTimeCount').addClass('count-update').text(response.on_time_count);
                
                // Update percentages and progress bars
                $('#latePercentage').text(response.late_percentage + '%');
                $('#overtimePercentage').text(response.overtime_percentage + '%');
                $('#onTimePercentage').text(response.on_time_percentage + '%');
                
                // Animate progress bars
                $('#lateProgressBar').css('width', '0%').animate({width: response.late_percentage + '%'}, 800);
                $('#overtimeProgressBar').css('width', '0%').animate({width: response.overtime_percentage + '%'}, 800);
                $('#onTimeProgressBar').css('width', '0%').animate({width: response.on_time_percentage + '%'}, 800);
                
                // Update aria attributes
                $('#lateProgressBar').attr('aria-valuenow', response.late_percentage);
                $('#overtimeProgressBar').attr('aria-valuenow', response.overtime_percentage);
                $('#onTimeProgressBar').attr('aria-valuenow', response.on_time_percentage);
                
                // Remove animation class after animation completes
                setTimeout(function() {
                    $('.count-update').removeClass('count-update');
                }, 500);
            },
            error: function(xhr) {
                console.error('Error loading statistics:', xhr);
            }
        });
    }

    // Load initial statistics
    loadStatistics();

    // Date range picker
    $('#dateRange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        autoUpdateInput: false,
        opens: 'left',
        ranges: {
            'Hari ini': [moment(), moment()],
            '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
            'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
            'Bulan lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });
    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
        loadStatistics();
    });
    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
        loadStatistics();
    });

    // Enable select2 multi-select
    $('#employeeFilter').val(null).select2({
        placeholder: 'Pilih Karyawan',
        allowClear: true,
        width: '100%'
    });

    var table = $('#rekapTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('hrd.absensi_rekap.data') }}',
            data: function(d) {
                d.date_range = $('#dateRange').val();
                d.employee_ids = $('#employeeFilter').val();
            }
        },
        columns: [
            { data: 'finger_id', name: 'finger_id' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'date', name: 'date' },
            { data: 'jam_masuk', name: 'jam_masuk' },
            { data: 'jam_keluar', name: 'jam_keluar' },
            { data: 'shift', name: 'shift' },
            { data: 'work_hour', name: 'work_hour' },
            { data: 'status', name: 'status' },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<button class="btn btn-sm btn-warning edit-btn" data-id="'+row.id+'" data-date="'+row.date+'" data-jam-masuk="'+row.jam_masuk+'" data-jam-keluar="'+row.jam_keluar+'">Edit</button>';
                }
            }
        ]
    });

    $('#employeeFilter').on('change', function() {
        table.ajax.reload();
        loadStatistics();
    });

    $('#rekapTable').on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var jamMasuk = $(this).data('jam-masuk');
        var jamKeluar = $(this).data('jam-keluar');
        $('#editId').val(id);
        $('#editJamMasuk').val(jamMasuk);
        $('#editJamKeluar').val(jamKeluar);
        $('#editModal').modal('show');
    });

    $('#saveEditBtn').on('click', function() {
        var id = $('#editId').val();
        var jamMasuk = $('#editJamMasuk').val();
        var jamKeluar = $('#editJamKeluar').val();
        $.post({
            url: '/hrd/absensi-rekap/' + id + '/update',
            data: {
                _token: '{{ csrf_token() }}',
                jam_masuk: jamMasuk,
                jam_keluar: jamKeluar
            },
            success: function(response) {
                $('#editModal').modal('hide');
                table.ajax.reload();
                loadStatistics();
                alert('Data berhasil diupdate!');
            },
            error: function(xhr) {
                alert('Gagal update: ' + (xhr.responseJSON?.error || 'Unknown error'));
            }
        });
    });

    // Upload form handler
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
            url: '{{ route('hrd.absensi_rekap.upload') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                alert('File berhasil diupload!');
                table.ajax.reload();
                loadStatistics();
                $('#uploadForm')[0].reset();
                $('.custom-file-label').text('Choose XLS file');
            },
            error: function(xhr) {
                alert('Gagal upload: ' + (xhr.responseJSON?.error || 'Unknown error'));
            }
        });
    });

    // Sync Shifts Button Handler
    $('#syncShiftsBtn').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
        
        $.ajax({
            url: '{{ route("hrd.absensi_rekap.sync_shifts") }}',
            type: 'GET',
            success: function(response) {
                alert(response.message || 'Shift data synchronized successfully!');
                table.ajax.reload();
                loadStatistics();
            },
            error: function(xhr) {
                alert('Failed to sync shifts: ' + (xhr.responseJSON?.error || 'Unknown error'));
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reprocess Times Button Handler
    $('#reprocessTimesBtn').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();
        
        if (!confirm('This will reprocess attendance times using smart selection based on shift schedules. Continue?')) {
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: '{{ route("hrd.absensi_rekap.reprocess_times") }}',
            type: 'GET',
            success: function(response) {
                alert(response.message || 'Attendance times reprocessed successfully!');
                table.ajax.reload();
                loadStatistics();
            },
            error: function(xhr) {
                alert('Failed to reprocess times: ' + (xhr.responseJSON?.error || 'Unknown error'));
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Update file input label
    $('#file').on('change', function() {
        var fileName = $(this)[0].files[0] ? $(this)[0].files[0].name : 'Choose XLS file';
        $(this).next('.custom-file-label').text(fileName);
    });
});
</script>
@endpush
@endsection

@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <h2>Rekap Absensi</h2>

    <!-- Download Rekap Absensi Excel Button -->
    <a href="{{ route('hrd.absensi_rekap.export_excel') }}" class="btn btn-success mb-3">
        <i class="fas fa-file-excel"></i> Download Rekap Excel
    </a>
    
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
                                Late Records
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
                                Overtime Records
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
                                On Time Records
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
    
    <!-- Top 5 Most Late Employees Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-clock text-danger"></i>
                        Top 5 Karyawan Paling Sering Terlambat
                    </h6>
                </div>
                <div class="card-body">
                    <div id="top5LateEmployees">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
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
            <button id="showLatenessRecapModalBtn" class="btn btn-success mt-2" style="width:100%">
                <i class="fas fa-file-export"></i> Submit Rekap Keterlambatan Bulan Ini
            </button>
            <!-- Modal for lateness recap confirmation -->
            <div class="modal fade" id="latenessRecapModal" tabindex="-1" role="dialog" aria-labelledby="latenessRecapModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="latenessRecapModalLabel">Konfirmasi Rekap Keterlambatan Bulan Ini</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="latenessRecapTableContainer"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="button" id="confirmLatenessRecapBtn" class="btn btn-success">Konfirmasi & Simpan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Sync Shifts Button -->
    <div class="row mb-3">
        <div class="col-md-8">
            <button id="syncShiftsBtn" class="btn btn-warning mr-2" data-toggle="tooltip" data-placement="top" 
                    title="Sync shift data and recalculate work hours for all attendance records">
                <i class="fas fa-sync"></i> Sync Shift Data & Work Hours
            </button>
            <br>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                <strong>Sync:</strong> Fix shift times and recalculate work hours for all records
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
                <th>Terlambat</th>
                <th>Overtime</th>
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

        <!-- Add Absensi Button -->
        <button class="btn btn-primary mb-3" id="addAbsensiBtn">
            <i class="fas fa-plus"></i> Tambah Absensi
        </button>

        <!-- Add Absensi Modal -->
        <div class="modal fade" id="addAbsensiModal" tabindex="-1" role="dialog" aria-labelledby="addAbsensiModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAbsensiModalLabel">Tambah Absensi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="addAbsensiForm">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="addEmployee">Karyawan</label>
                                <select id="addEmployee" name="employee_id" class="form-control" required></select>
                            </div>
                            <div class="form-group">
                                <label for="addTanggal">Tanggal</label>
                                <input type="date" id="addTanggal" name="date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="addShift">Shift</label>
                                <input type="text" id="addShift" name="shift" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label for="addJamMasuk">Jam Masuk</label>
                                <input type="time" id="addJamMasuk" name="jam_masuk" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="addJamKeluar">Jam Keluar</label>
                                <input type="time" id="addJamKeluar" name="jam_keluar" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="addWorkHour">Work Hour</label>
                                <input type="text" id="addWorkHour" name="work_hour" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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

/* Top 5 Late Employees Styling */
#top5LateEmployees .table-responsive {
    border-radius: 0.35rem;
}

#top5LateEmployees .table th {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    font-weight: 600;
    color: #5a5c69;
    font-size: 0.8rem;
}

#top5LateEmployees .table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

#top5LateEmployees .badge-danger {
    background-color: #e74a3b;
}

#top5LateEmployees .badge-warning {
    background-color: #f6c23e;
    color: #fff;
}

#top5LateEmployees .badge-secondary {
    background-color: #6c757d;
}

#top5LateEmployees .badge-light {
    background-color: #f8f9fa;
    color: #6c757d;
    border: 1px solid #e3e6f0;
}

#top5LateEmployees .fa-crown {
    animation: sparkle 2s infinite;
}

@keyframes sparkle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
}

#top5LateEmployees tr:hover {
    background-color: #f8f9fc;
}
</style>
@endpush

@push('scripts')
<script>
$(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Function to update Top 5 Late Employees display
    function updateTop5LateEmployees(employees) {
        const container = $('#top5LateEmployees');
        
        if (!employees || employees.length === 0) {
            container.html(`
                <div class="text-center text-muted">
                    <i class="fas fa-check-circle text-success"></i>
                    <p class="mb-0">Tidak ada karyawan yang terlambat dalam periode ini</p>
                </div>
            `);
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-sm">';
        html += `
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="40%">Nama Karyawan</th>
                    <th width="20%">Total Keterlambatan</th>
                    <th width="15%">Jumlah Terlambat</th>
                    <th width="20%">Rata-rata Terlambat</th>
                </tr>
            </thead>
            <tbody>
        `;
        
        employees.forEach((employee, index) => {
            const badgeClass = index === 0 ? 'danger' : (index === 1 ? 'warning' : 'secondary');
            const crown = index === 0 ? '<i class="fas fa-crown text-warning mr-1"></i>' : '';
            
            html += `
                <tr>
                    <td>
                        <span class="badge badge-${badgeClass}">${index + 1}</span>
                    </td>
                    <td>
                        ${crown}${employee.employee_name}
                    </td>
                    <td>
                        <span class="text-danger font-weight-bold">${employee.formatted_total}</span>
                    </td>
                    <td>
                        <span class="badge badge-light">${employee.late_instances}x</span>
                    </td>
                    <td>
                        <span class="text-muted">${employee.formatted_avg}</span>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.html(html);
    }

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
            // Show lateness recap modal before submitting
            $('#showLatenessRecapModalBtn').on('click', function() {
                var dateRange = $('#dateRange').val();
                var employeeIds = $('#employeeFilter').val();
                if (!dateRange) {
                    alert('Pilih rentang tanggal terlebih dahulu!');
                    return;
                }
                $(this).prop('disabled', true).text('Memproses...');
                $.ajax({
                    url: '/hrd/absensi-rekap/data',
                    method: 'GET',
                    data: {
                        date_range: dateRange,
                        employee_ids: employeeIds
                    },
                    success: function(res) {
                        // Summarize lateness recap per employee
                        var data = res.data || res;
                        var recap = {};
                        data.forEach(function(row) {
                            var empId = row.employee_id;
                            if (!recap[empId]) {
                                recap[empId] = {
                                    nama: row.employee_name,
                                    total_late_days: 0,
                                    total_late_minutes: 0,
                                    total_overtime_minutes: 0
                                };
                            }
                            if (row.menit_terlambat > 0) {
                                recap[empId].total_late_days++;
                                recap[empId].total_late_minutes += row.menit_terlambat;
                            }
                            recap[empId].total_overtime_minutes += row.overtime;
                        });
                        // Build table
                        var html = '<table class="table table-bordered"><thead><tr><th>Nama</th><th>Total Hari Terlambat</th><th>Total Menit Terlambat</th><th>Total Menit Overtime</th><th>Total Menit Terlambat - Overtime</th></tr></thead><tbody>';
                        Object.values(recap).forEach(function(r) {
                            html += '<tr>' +
                                '<td>' + r.nama + '</td>' +
                                '<td>' + r.total_late_days + '</td>' +
                                '<td>' + r.total_late_minutes + '</td>' +
                                '<td>' + r.total_overtime_minutes + '</td>' +
                                '<td>' + (r.total_late_minutes - r.total_overtime_minutes) + '</td>' +
                                '</tr>';
                        });
                        html += '</tbody></table>';
                        $('#latenessRecapTableContainer').html(html);
                        $('#latenessRecapModal').modal('show');
                    },
                    error: function(xhr) {
                        alert('Gagal mengambil data rekap!');
                    },
                    complete: function() {
                        $('#showLatenessRecapModalBtn').prop('disabled', false).html('<i class="fas fa-file-export"></i> Submit Rekap Keterlambatan Bulan Ini');
                    }
                });
            });

            // Confirm and submit lateness recap
            $('#confirmLatenessRecapBtn').on('click', function() {
                var dateRange = $('#dateRange').val();
                var employeeIds = $('#employeeFilter').val();
                $.ajax({
                    url: '/hrd/absensi-rekap/submit-lateness-recap',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content') || $('[name="_token"]').val(),
                        date_range: dateRange,
                        employee_ids: employeeIds
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message || 'Rekap keterlambatan berhasil disimpan!',
                            confirmButtonText: 'OK'
                        });
                        $('#latenessRecapModal').modal('hide');
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan rekap keterlambatan!',
                            confirmButtonText: 'OK'
                        });
                    }
                });
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
                
                // Update Top 5 Late Employees
                updateTop5LateEmployees(response.top_5_late_employees || []);
                
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
    // Set default to current month
    var startOfMonth = moment().startOf('month');
    var endOfMonth = moment().endOf('month');
    $('#dateRange').val(startOfMonth.format('YYYY-MM-DD') + ' - ' + endOfMonth.format('YYYY-MM-DD'));
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
            {
                data: 'work_hour',
                name: 'work_hour',
                render: function(data, type, row) {
                    if (data == null) return '';
                    var hours = Math.floor(data);
                    var minutes = Math.round((data - hours) * 60);
                    return hours + ' jam ' + minutes + ' menit';
                }
            },
            {
                data: 'menit_terlambat',
                name: 'menit_terlambat',
                render: function(data, type, row) {
                    return data > 0 ? data + ' menit' : '-';
                }
            },
            {
                data: 'overtime',
                name: 'overtime',
                render: function(data, type, row) {
                    return data > 0 ? data + ' menit' : '-';
                }
            },
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
                table.ajax.reload(null, false); // force reload and keep paging
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

    // Update file input label
    $('#file').on('change', function() {
        var fileName = $(this)[0].files[0] ? $(this)[0].files[0].name : 'Choose XLS file';
        $(this).next('.custom-file-label').text(fileName);
    });

        // Add Absensi Modal logic
        $('#addAbsensiBtn').on('click', function() {
            $('#addAbsensiModal').modal('show');
            $('#addAbsensiForm')[0].reset();
            $('#addShift').val('');
            $('#addWorkHour').val('');
        });

        // Initialize select2 for employee
            $('#addEmployee').select2({
                placeholder: 'Pilih Karyawan',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '/api/hrd/employees',
                    dataType: 'json',
                    delay: 250,
                    processResults: function (data) {
                        return {
                            results: data.map(function(emp) {
                                return { id: emp.id, text: emp.nama };
                            })
                        };
                    }
                }
            });

        // Fetch schedule and autofill
        function fetchScheduleAndFill() {
            var empId = $('#addEmployee').val();
            var tanggal = $('#addTanggal').val();
            if (empId && tanggal) {
                $.get('/hrd/absensi-rekap/schedule', { employee_id: empId, date: tanggal }, function(res) {
                    if (res && res.shift) {
                        $('#addShift').val(res.shift.name || '');
                        $('#addJamMasuk').val(res.shift.start || '');
                        $('#addJamKeluar').val(res.shift.end || '');
                        calculateWorkHour();
                    } else {
                        $('#addShift').val('');
                        $('#addJamMasuk').val('');
                        $('#addJamKeluar').val('');
                        $('#addWorkHour').val('');
                    }
                });
            }
        }

        $('#addEmployee, #addTanggal').on('change', fetchScheduleAndFill);

        // Calculate work hour
        function calculateWorkHour() {
            var masuk = $('#addJamMasuk').val();
            var keluar = $('#addJamKeluar').val();
            if (masuk && keluar) {
                var start = moment(masuk, 'HH:mm');
                var end = moment(keluar, 'HH:mm');
                var diff = end.diff(start, 'minutes');
                if (diff < 0) {
                    // Overnight shift: add 24 hours
                    diff += 24 * 60;
                }
                $('#addWorkHour').val(diff > 0 ? (diff / 60).toFixed(2) : '0');
            } else {
                $('#addWorkHour').val('');
            }
        }
        $('#addJamMasuk, #addJamKeluar').on('change', calculateWorkHour);

        // Submit add absensi form
            $('#addAbsensiForm').on('submit', function(e) {
                e.preventDefault();
                // Combine date and time to 'd/m/Y H:i' format
                var tanggal = $('#addTanggal').val();
                var jamMasuk = $('#addJamMasuk').val();
                var jamKeluar = $('#addJamKeluar').val();
                function toExcelFormat(date, time) {
                    if (!date || !time) return '';
                    var parts = date.split('-'); // yyyy-mm-dd
                    if (parts.length === 3) {
                        return parts[2] + '/' + parts[1] + '/' + parts[0] + ' ' + time.substring(0,5);
                    }
                    return date + ' ' + time.substring(0,5);
                }
                var jamMasukExcel = toExcelFormat(tanggal, jamMasuk);
                var jamKeluarExcel = toExcelFormat(tanggal, jamKeluar);

                var formData = $(this).serializeArray();
                // Replace jam_masuk and jam_keluar values
                formData = formData.map(function(f) {
                    if (f.name === 'jam_masuk') f.value = jamMasukExcel;
                    if (f.name === 'jam_keluar') f.value = jamKeluarExcel;
                    return f;
                });
                $.post('/hrd/absensi-rekap', $.param(formData), function(res) {
                    alert('Absensi berhasil ditambahkan!');
                    $('#addAbsensiModal').modal('hide');
                    table.ajax.reload(null, false); // force reload and keep paging
                }).fail(function(xhr) {
                    alert('Gagal menambah absensi: ' + (xhr.responseJSON?.error || 'Unknown error'));
                });
            });
});
</script>
@endpush
@endsection

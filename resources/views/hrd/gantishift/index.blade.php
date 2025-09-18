@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Ganti Shift')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Pengajuan Ganti Shift</h4>
                    </div>
                </div>
            </div>
            
            <div class="row">
                @if(auth()->user()->hasRole('Employee'))
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Pengajuan Ganti Shift Saya</h4>
                            <button class="btn btn-primary" id="btnCreateGantiShift">Buat Pengajuan Baru</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableGantiShiftPersonal" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tanggal Shift</th>
                                            <th>Shift Lama</th>
                                            <th>Shift Baru</th>
                                            <th>Jenis</th>
                                            <th>Status Manager</th>
                                            <th>Status HRD</th>
                                            <th>Status Target</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if(auth()->user()->hasRole('Manager'))
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Persetujuan Ganti Shift Tim</h4>
                            <button class="btn btn-primary" id="btnCreateGantiShift">Buat Pengajuan Baru</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableGantiShiftTeam" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Karyawan</th>
                                            <th>Tanggal Shift</th>
                                            <th>Shift Lama</th>
                                            <th>Shift Baru</th>
                                            <th>Jenis</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if(auth()->user()->hasRole('Hrd'))
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Persetujuan HRD - Ganti Shift</h4>
                            <button class="btn btn-primary" id="btnCreateGantiShift">Buat Pengajuan Baru</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableGantiShiftApproval" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Karyawan</th>
                                            <th>Tanggal Shift</th>
                                            <th>Shift Lama</th>
                                            <th>Shift Baru</th>
                                            <th>Jenis</th>
                                            <th>Status Manager</th>
                                            <th>Status HRD</th>
                                            <th>Status Target</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Create Ganti Shift -->
<div class="modal fade" id="modalCreateGantiShift" tabindex="-1" role="dialog" aria-labelledby="modalCreateGantiShiftLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateGantiShiftLabel">Buat Pengajuan Ganti Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateGantiShift">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tanggal_shift">Tanggal Shift <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_shift" name="tanggal_shift" required>
                    </div>
                    
                    <!-- Tukar Shift Checkbox -->
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_tukar_shift" name="is_tukar_shift" value="1">
                            <label class="form-check-label" for="is_tukar_shift">
                                Tukar Shift dengan Karyawan Lain
                            </label>
                            <small class="form-text text-muted">Centang jika ingin menukar shift dengan karyawan lain yang memiliki shift yang sama</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="shift_baru_id">Shift Baru <span class="text-danger">*</span></label>
                        <select class="form-control" id="shift_baru_id" name="shift_baru_id" required>
                            <option value="">Pilih Shift</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="shift_lama_display">Shift Saat Ini</label>
                        <input type="text" class="form-control" id="shift_lama_display" readonly placeholder="Tidak ada shift">
                    </div>
                    
                    <!-- Target Employee Selection for Tukar Shift -->
                    <div class="form-group" id="target_employee_group" style="display: none;">
                        <label for="target_employee_id">Karyawan untuk Ditukar <span class="text-danger">*</span></label>
                        <select class="form-control" id="target_employee_id" name="target_employee_id">
                            <option value="">Pilih Karyawan</option>
                        </select>
                        <small class="form-text text-muted">Karyawan yang akan ditukar shiftnya dengan Anda</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="alasan">Alasan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alasan" name="alasan" rows="3" required placeholder="Jelaskan alasan Anda meminta ganti shift..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Ganti Shift -->
<div class="modal fade" id="modalDetailGantiShift" tabindex="-1" role="dialog" aria-labelledby="modalDetailGantiShiftLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailGantiShiftLabel">Detail Pengajuan Ganti Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailGantiShiftBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Approval Manager -->
<div class="modal fade" id="modalApprovalManagerGantiShift" tabindex="-1" role="dialog" aria-labelledby="modalApprovalManagerGantiShiftLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalApprovalManagerGantiShiftLabel">Persetujuan Manager</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalManagerGantiShift">
                @csrf
                <input type="hidden" id="manager_gantishift_id" name="gantishift_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status_manager">Keputusan <span class="text-danger">*</span></label>
                        <select class="form-control" id="status_manager" name="status" required>
                            <option value="">Pilih Keputusan</option>
                            <option value="disetujui">Setujui</option>
                            <option value="ditolak">Tolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="komentar_manager">Komentar</label>
                        <textarea class="form-control" id="komentar_manager" name="komentar_manager" rows="3" placeholder="Berikan komentar (opsional)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Approval HRD -->
<div class="modal fade" id="modalApprovalHRDGantiShift" tabindex="-1" role="dialog" aria-labelledby="modalApprovalHRDGantiShiftLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalApprovalHRDGantiShiftLabel">Persetujuan HRD</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalHRDGantiShift">
                @csrf
                <input type="hidden" id="hrd_gantishift_id" name="gantishift_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status_hrd">Keputusan <span class="text-danger">*</span></label>
                        <select class="form-control" id="status_hrd" name="status" required>
                            <option value="">Pilih Keputusan</option>
                            <option value="disetujui">Setujui</option>
                            <option value="ditolak">Tolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="komentar_hrd">Komentar</label>
                        <textarea class="form-control" id="komentar_hrd" name="komentar_hrd" rows="3" placeholder="Berikan komentar (opsional)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Approval Target Employee (for Tukar Shift) -->
<div class="modal fade" id="modalTargetEmployeeApproval" tabindex="-1" role="dialog" aria-labelledby="modalTargetEmployeeApprovalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTargetEmployeeApprovalLabel">Persetujuan Tukar Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formTargetEmployeeApproval">
                @csrf
                <input type="hidden" id="target_approval_id" name="id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Permintaan Tukar Shift</strong><br>
                        Rekan kerja Anda ingin menukar shift dengan Anda. Silakan berikan persetujuan.
                    </div>
                    <div class="form-group">
                        <label>Keputusan <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="target_approve" value="disetujui" required>
                                <label class="form-check-label" for="target_approve">Setuju</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="target_reject" value="ditolak" required>
                                <label class="form-check-label" for="target_reject">Tolak</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="target_notes">Catatan</label>
                        <textarea class="form-control" id="target_notes" name="notes" rows="3" placeholder="Berikan catatan (opsional)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(document).ready(function() {
    // DataTable untuk Employee
    @if(auth()->user()->hasRole('Employee'))
    var tablePersonal = $('#tableGantiShiftPersonal').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.gantishift.index') }}?view=personal",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'tanggal_shift', name: 'tanggal_shift'},
            {data: 'shift_lama', name: 'shift_lama'},
            {data: 'shift_baru', name: 'shift_baru'},
            {data: 'jenis', name: 'jenis', orderable: false},
            {data: 'status_manager', name: 'status_manager'},
            {data: 'status_hrd', name: 'status_hrd'},
            {data: 'status_target', name: 'status_target'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
    @endif

    // DataTable untuk Manager (team)
    @if(auth()->user()->hasRole('Manager'))
    var tableTeam = $('#tableGantiShiftTeam').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.gantishift.index') }}?view=team",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'tanggal_shift', name: 'tanggal_shift'},
            {data: 'shift_lama', name: 'shift_lama'},
            {data: 'shift_baru', name: 'shift_baru'},
            {data: 'jenis', name: 'jenis', orderable: false},
            {data: 'status_manager', name: 'status_manager'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
    @endif

    // DataTable untuk HRD (approval)
    @if(auth()->user()->hasRole('Hrd'))
    var tableApproval = $('#tableGantiShiftApproval').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.gantishift.index') }}?view=approval",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'tanggal_shift', name: 'tanggal_shift'},
            {data: 'shift_lama', name: 'shift_lama'},
            {data: 'shift_baru', name: 'shift_baru'},
            {data: 'jenis', name: 'jenis', orderable: false},
            {data: 'status_manager', name: 'status_manager'},
            {data: 'status_hrd', name: 'status_hrd'},
            {data: 'status_target', name: 'status_target'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
    @endif

    // Show create modal
    $('#btnCreateGantiShift').click(function() {
        $('#formCreateGantiShift')[0].reset();
        $('#shift_lama_display').val('');
        $('#shift_baru_id').empty().append('<option value="">Pilih Shift</option>');
        $('#target_employee_id').empty().append('<option value="">Pilih Karyawan</option>');
        $('#target_employee_group').hide();
        $('#modalCreateGantiShift').modal('show');
    });

    // Handle tukar shift checkbox
    $('#is_tukar_shift').change(function() {
        if ($(this).is(':checked')) {
            $('#target_employee_group').show();
            $('#target_employee_id').prop('required', true);
            
            // Load employees if date and shift are already selected
            var date = $('#tanggal_shift').val();
            var shiftId = $('#shift_baru_id').val();
            if (date && shiftId) {
                loadEmployeesSameShift(date, shiftId);
            }
        } else {
            $('#target_employee_group').hide();
            $('#target_employee_id').prop('required', false);
        }
    });

    // Function to load employees with same shift
    function loadEmployeesSameShift(date, shiftId) {
        console.log('Loading employees for shift exchange...', { date, shiftId });
        $.ajax({
            url: "{{ route('hrd.gantishift.same-shift-employees') }}",
            method: 'GET',
            data: { date: date, shift_id: shiftId },
            success: function(response) {
                console.log('Employees response:', response);
                var employeeSelect = $('#target_employee_id');
                employeeSelect.empty().append('<option value="">Pilih Karyawan</option>');
                
                if (response.employees && response.employees.length > 0) {
                    response.employees.forEach(function(employee) {
                        var displayName = employee.name;
                        if (employee.position && employee.position.trim() !== '') {
                            displayName += ' (' + employee.position + ')';
                        }
                        employeeSelect.append('<option value="' + employee.id + '">' + displayName + '</option>');
                    });
                    console.log('Added ' + response.employees.length + ' employees to select');
                } else {
                    employeeSelect.append('<option value="" disabled>Tidak ada karyawan dengan shift yang sama</option>');
                    console.log('No employees found with same shift');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', { xhr, status, error });
                var errorMessage = 'Gagal memuat data karyawan';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage += ': ' + xhr.responseJSON.error;
                }
                Swal.fire('Error', errorMessage, 'error');
            }
        });
    }

    // When date is selected, load available shifts
    $('#tanggal_shift').change(function() {
        var date = $(this).val();
        if (date) {
            $.ajax({
                url: "{{ route('hrd.gantishift.available-shifts') }}",
                method: 'GET',
                data: { date: date },
                success: function(response) {
                    var shiftSelect = $('#shift_baru_id');
                    shiftSelect.empty().append('<option value="">Pilih Shift</option>');
                    
                    response.shifts.forEach(function(shift) {
                        shiftSelect.append('<option value="' + shift.id + '">' + 
                            shift.name + ' (' + shift.start_time + '-' + shift.end_time + ')</option>');
                    });

                    // Display current shift
                    if (response.current_shift_id) {
                        var currentShift = response.shifts.find(s => s.id == response.current_shift_id);
                        if (currentShift) {
                            $('#shift_lama_display').val(currentShift.name + ' (' + currentShift.start_time + '-' + currentShift.end_time + ')');
                        }
                    } else {
                        $('#shift_lama_display').val('Tidak ada shift');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Gagal memuat data shift', 'error');
                }
            });
        }
    });

    // When shift is selected and tukar shift is checked, load employees with same shift
    $('#shift_baru_id').change(function() {
        var shiftId = $(this).val();
        var date = $('#tanggal_shift').val();
        var isTukarShift = $('#is_tukar_shift').is(':checked');
        
        console.log('Shift changed:', { shiftId, date, isTukarShift });
        
        if (shiftId && date && isTukarShift) {
            loadEmployeesSameShift(date, shiftId);
        } else {
            // Clear employee select if conditions not met
            $('#target_employee_id').empty().append('<option value="">Pilih Karyawan</option>');
        }
    });

    // Submit create form
    $('#formCreateGantiShift').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('hrd.gantishift.store') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#modalCreateGantiShift').modal('hide');
                Swal.fire('Berhasil', 'Pengajuan ganti shift berhasil diajukan', 'success');
                @if(auth()->user()->hasRole('Employee'))
                tablePersonal.ajax.reload();
                @endif
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors || {};
                var message = xhr.responseJSON.error || 'Terjadi kesalahan';
                Swal.fire('Error', message, 'error');
            }
        });
    });

    // Show detail modal
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('hrd.gantishift.show', ':id') }}".replace(':id', id),
            method: 'GET',
            success: function(response) {
                var data = response.data;
                var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nama Karyawan:</strong> ${data.employee_name}</p>
                            <p><strong>Tanggal Shift:</strong> ${data.tanggal_shift}</p>
                            <p><strong>Shift Lama:</strong> ${data.shift_lama}</p>
                            <p><strong>Shift Baru:</strong> ${data.shift_baru}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status Manager:</strong> <span class="badge badge-${data.status_manager === 'disetujui' ? 'success' : data.status_manager === 'ditolak' ? 'danger' : 'warning'}">${data.status_manager}</span></p>
                            <p><strong>Status HRD:</strong> <span class="badge badge-${data.status_hrd === 'disetujui' ? 'success' : data.status_hrd === 'ditolak' ? 'danger' : 'warning'}">${data.status_hrd}</span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Alasan:</strong></p>
                            <p>${data.alasan}</p>
                        </div>
                    </div>
                `;
                
                if (data.notes_manager) {
                    html += `
                        <div class="row">
                            <div class="col-12">
                                <p><strong>Catatan Manager:</strong></p>
                                <p>${data.notes_manager}</p>
                                <small class="text-muted">Tanggal: ${data.tanggal_persetujuan_manager || '-'}</small>
                            </div>
                        </div>
                    `;
                }
                
                if (data.notes_hrd) {
                    html += `
                        <div class="row">
                            <div class="col-12">
                                <p><strong>Catatan HRD:</strong></p>
                                <p>${data.notes_hrd}</p>
                                <small class="text-muted">Tanggal: ${data.tanggal_persetujuan_hrd || '-'}</small>
                            </div>
                        </div>
                    `;
                }
                
                // Show schedule update status if both approvals are granted
                if (data.status_manager === 'disetujui' && data.status_hrd === 'disetujui' && data.schedule_info) {
                    html += `
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-${data.schedule_info.is_updated ? 'success' : 'warning'}">
                                    <h6><strong>Status Jadwal:</strong></h6>
                                    ${data.schedule_info.is_updated 
                                        ? `✅ Jadwal telah berhasil diperbarui ke: <strong>${data.schedule_info.current_shift_name} (${data.schedule_info.current_shift_time})</strong>`
                                        : `⚠️ Jadwal belum diperbarui. Shift saat ini: <strong>${data.schedule_info.current_shift_name} (${data.schedule_info.current_shift_time})</strong>`
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                $('#modalDetailGantiShiftBody').html(html);
                $('#modalDetailGantiShift').modal('show');
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat detail pengajuan', 'error');
            }
        });
    });

    // Show target employee approval modal
    $(document).on('click', '.btn-target-approve', function() {
        var id = $(this).data('id');
        $('#target_approval_id').val(id);
        $('#formTargetEmployeeApproval')[0].reset();
        $('#modalTargetEmployeeApproval').modal('show');
    });

    // Submit target employee approval
    $('#formTargetEmployeeApproval').submit(function(e) {
        e.preventDefault();
        var id = $('#target_approval_id').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('hrd.gantishift.target-approval', ':id') }}".replace(':id', id),
            method: 'PUT',
            data: formData,
            success: function(response) {
                $('#modalTargetEmployeeApproval').modal('hide');
                Swal.fire('Sukses', response.message, 'success');
                @if(auth()->user()->hasRole('Employee'))
                tablePersonal.ajax.reload();
                @endif
            },
            error: function() {
                Swal.fire('Error', 'Gagal memproses persetujuan', 'error');
            }
        });
    });

    // Show manager approval modal
    $(document).on('click', '.btn-approve-manager', function() {
        var id = $(this).data('id');
        $('#manager_gantishift_id').val(id);
        $('#formApprovalManagerGantiShift')[0].reset();
        $('#modalApprovalManagerGantiShift').modal('show');
    });

    // Submit manager approval
    $('#formApprovalManagerGantiShift').submit(function(e) {
        e.preventDefault();
        var id = $('#manager_gantishift_id').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('hrd.gantishift.manager', ':id') }}".replace(':id', id),
            method: 'PUT',
            data: formData,
            success: function(response) {
                $('#modalApprovalManagerGantiShift').modal('hide');
                var message = response.message || 'Keputusan berhasil disimpan';
                Swal.fire('Berhasil', message, 'success');
                @if(auth()->user()->hasRole('Manager'))
                tableTeam.ajax.reload();
                @endif
            },
            error: function() {
                Swal.fire('Error', 'Gagal menyimpan keputusan', 'error');
            }
        });
    });

    // Show HRD approval modal
    $(document).on('click', '.btn-approve-hrd', function() {
        var id = $(this).data('id');
        $('#hrd_gantishift_id').val(id);
        $('#formApprovalHRDGantiShift')[0].reset();
        $('#modalApprovalHRDGantiShift').modal('show');
    });

    // Submit HRD approval
    $('#formApprovalHRDGantiShift').submit(function(e) {
        e.preventDefault();
        var id = $('#hrd_gantishift_id').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('hrd.gantishift.hrd', ':id') }}".replace(':id', id),
            method: 'PUT',
            data: formData,
            success: function(response) {
                $('#modalApprovalHRDGantiShift').modal('hide');
                var message = response.message || 'Keputusan berhasil disimpan';
                Swal.fire('Berhasil', message, 'success');
                @if(auth()->user()->hasRole('Hrd'))
                tableApproval.ajax.reload();
                @endif
            },
            error: function() {
                Swal.fire('Error', 'Gagal menyimpan keputusan', 'error');
            }
        });
    });
});
</script>
@endsection

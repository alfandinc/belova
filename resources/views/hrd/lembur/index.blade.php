@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Lembur')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Pengajuan Lembur</h3>
                <div class="text-muted small">Kelola pengajuan lembur karyawan</div>
            </div>
            <div class="d-flex align-items-center">
                <input type="text" id="dateRangeLembur" class="form-control form-control-sm d-inline-block mr-2" style="width: 260px;" placeholder="Filter tanggal" />
                <a href="#" class="btn btn-sm btn-primary" id="btnCreateLembur">
                    <i class="fas fa-plus-circle mr-2"></i>Ajukan Lembur
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <table class="table table-bordered" id="tableLembur">
                        <thead>
                            <tr>
                                <th>No</th>
                                @if(\App\Models\User::find(Auth::id())->hasRole('Manager') || \App\Models\User::find(Auth::id())->hasRole('Hrd'))
                                <th>Nama Pegawai</th>
                                @endif
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
        </div>
    </div>
</div>

<!-- Modal Create Lembur -->
<div class="modal fade" id="modalCreateLembur" tabindex="-1" role="dialog" aria-labelledby="modalCreateLemburLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajukan Lembur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateLembur">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" id="tanggal" required>
                    </div>
                    <div class="form-group">
                        <label for="jam_mulai">Jam Mulai</label>
                        <input type="time" class="form-control" name="jam_mulai" id="jam_mulai" required>
                    </div>
                    <div class="form-group">
                        <label for="jam_selesai">Jam Selesai</label>
                        <input type="time" class="form-control" name="jam_selesai" id="jam_selesai" required>
                    </div>
                    <div class="form-group">
                        <label for="alasan">Alasan</label>
                        <textarea class="form-control" name="alasan" id="alasan" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lembur -->
<div class="modal fade" id="modalDetailLembur" tabindex="-1" role="dialog" aria-labelledby="modalDetailLemburLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan Lembur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailLemburBody">
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>

<!-- Modal Approval Manager Lembur -->
<div class="modal fade" id="modalApprovalManagerLembur" tabindex="-1" role="dialog" aria-labelledby="modalApprovalManagerLemburLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Persetujuan Manager</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalManagerLembur">
                @csrf
                <input type="hidden" name="pengajuan_id" id="manager_lembur_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status Persetujuan</label>
                        <select class="form-control" name="status" id="manager_lembur_status" required>
                            <option value="disetujui">Setujui</option>
                            <option value="ditolak">Tolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Catatan Manager</label>
                        <textarea class="form-control" name="komentar_manager" id="manager_lembur_komentar"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btnSubmitApprovalManagerLembur">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Approval HRD Lembur -->
<div class="modal fade" id="modalApprovalHRDLembur" tabindex="-1" role="dialog" aria-labelledby="modalApprovalHRDLemburLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Persetujuan HRD</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalHRDLembur">
                @csrf
                <input type="hidden" name="pengajuan_id" id="hrd_lembur_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status Persetujuan</label>
                        <select class="form-control" name="status" id="hrd_lembur_status" required>
                            <option value="disetujui">Setujui</option>
                            <option value="ditolak">Tolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Catatan HRD</label>
                        <textarea class="form-control" name="komentar_hrd" id="hrd_lembur_komentar"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btnSubmitApprovalHRDLembur">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- daterangepicker (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
$(document).ready(function() {
    // Init Date Range Picker with default (this month to end of next month)
    var drpStartLB = moment("{{ isset($defaultDateStart) ? $defaultDateStart : now()->startOfMonth()->toDateString() }}");
    var drpEndLB = moment("{{ isset($defaultDateEnd) ? $defaultDateEnd : now()->addMonthNoOverflow()->endOfMonth()->toDateString() }}");

    $('#dateRangeLembur').daterangepicker({
        startDate: drpStartLB,
        endDate: drpEndLB,
        autoApply: true,
        locale: { format: 'DD/MM/YYYY', separator: ' - ' },
        ranges: {
            'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
            's.d Bulan Depan': [moment().startOf('month'), moment().add(1,'month').endOf('month')],
            '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
            '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
            'Bulan Depan': [moment().add(1,'month').startOf('month'), moment().add(1,'month').endOf('month')]
        }
    }, function(start, end) {
        drpStartLB = start;
        drpEndLB = end;
        if ($.fn.dataTable.isDataTable('#tableLembur')) {
            tableLembur.ajax.reload(function(){
                tableLembur.columns.adjust().draw(false);
            });
        }
    });

    var tableLembur = $('#tableLembur').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: true,
        responsive: true,
        ajax: {
            url: "{{ route('hrd.lembur.index') }}",
            data: function(d){
                d.date_start = drpStartLB.format('YYYY-MM-DD');
                d.date_end = drpEndLB.format('YYYY-MM-DD');
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            @if(\App\Models\User::find(Auth::id())->hasRole('Manager') || \App\Models\User::find(Auth::id())->hasRole('Hrd'))
            {data: 'employee_nama', name: 'employee_nama'},
            @endif
            {data: 'tanggal', name: 'tanggal', orderable: false, searchable: false},
            {data: 'alasan', name: 'alasan'},
            {data: 'catatan', name: 'catatan', orderable: false, searchable: false},
            {data: 'status_pengajuan', name: 'status_pengajuan', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        columnDefs: [
            { targets: -1, className: 'text-nowrap' } // prevent action buttons from wrapping
        ],
        drawCallback: function(){
            this.api().columns.adjust();
        }
    });

    $('#btnCreateLembur').click(function() {
        $('#formCreateLembur')[0].reset();
        $('#modalCreateLembur').modal('show');
        // Enforce current-time minimums if date is today
        enforceTodayTimeMin();
    });

    $('#formCreateLembur').submit(function(e) {
        e.preventDefault();
        // Final guard: if date is today, ensure times >= now and selesai >= mulai
        if (!validateTimesBeforeSubmit()) {
            return;
        }
        var formData = $(this).serialize();
        $.ajax({
            url: "{{ route('hrd.lembur.store') }}",
            method: 'POST',
            data: formData,
            success: function(res) {
                $('#modalCreateLembur').modal('hide');
                tableLembur.ajax.reload();
            },
            error: function(xhr) {
                alert('Gagal mengajukan lembur!');
            }
        });
    });

    $(document).on('click', '.btn-detail-lembur', function() {
        var id = $(this).data('id');
        $.get("/hrd/lembur/" + id, function(res) {
            $('#modalDetailLemburBody').html(res);
            $('#modalDetailLembur').modal('show');
        });
    });

    // Show manager approval modal
    $(document).on('click', '.btn-approve-manager-lembur', function() {
        var id = $(this).data('id');
        $('#manager_lembur_id').val(id);
        $('#formApprovalManagerLembur')[0].reset();
        // Fetch current status and notes
        $.ajax({
            url: "/hrd/lembur/" + id + "/approval-status",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    $('#manager_lembur_status').val(response.data.status_manager);
                    $('#manager_lembur_komentar').val(response.data.notes_manager);
                }
                $('#modalApprovalManagerLembur').modal('show');
            },
            error: function() {
                $('#modalApprovalManagerLembur').modal('show');
            }
        });
    });

    // Submit manager approval
    $('#formApprovalManagerLembur').submit(function(e) {
        e.preventDefault();
        var id = $('#manager_lembur_id').val();
        var formData = $(this).serialize();
        $.ajax({
            url: "/hrd/lembur/" + id + "/persetujuan-manager",
            type: "POST",
            data: formData,
            beforeSend: function() {
                $('#btnSubmitApprovalManagerLembur').attr('disabled', true).html('<i class=\"fa fa-spinner fa-spin\"></i> Memproses...');
            },
            success: function(response) {
                $('#modalApprovalManagerLembur').modal('hide');
                tableLembur.ajax.reload();
                $('#btnSubmitApprovalManagerLembur').attr('disabled', false).html('Simpan');
            },
            error: function(xhr) {
                alert('Terjadi kesalahan.');
                $('#btnSubmitApprovalManagerLembur').attr('disabled', false).html('Simpan');
            }
        });
    });

    // Show HRD approval modal
    $(document).on('click', '.btn-approve-hrd-lembur', function() {
        var id = $(this).data('id');
        $('#hrd_lembur_id').val(id);
        $('#formApprovalHRDLembur')[0].reset();
        // Fetch current status and notes
        $.ajax({
            url: "/hrd/lembur/" + id + "/approval-status",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    $('#hrd_lembur_status').val(response.data.status_hrd);
                    $('#hrd_lembur_komentar').val(response.data.notes_hrd);
                }
                $('#modalApprovalHRDLembur').modal('show');
            },
            error: function() {
                $('#modalApprovalHRDLembur').modal('show');
            }
        });
    });

    // Submit HRD approval
    $('#formApprovalHRDLembur').submit(function(e) {
        e.preventDefault();
        var id = $('#hrd_lembur_id').val();
        var formData = $(this).serialize();
        $.ajax({
            url: "/hrd/lembur/" + id + "/persetujuan-hrd",
            type: "POST",
            data: formData,
            beforeSend: function() {
                $('#btnSubmitApprovalHRDLembur').attr('disabled', true).html('<i class=\"fa fa-spinner fa-spin\"></i> Memproses...');
            },
            success: function(response) {
                $('#modalApprovalHRDLembur').modal('hide');
                tableLembur.ajax.reload();
                $('#btnSubmitApprovalHRDLembur').attr('disabled', false).html('Simpan');
            },
            error: function(xhr) {
                alert('Terjadi kesalahan.');
                $('#btnSubmitApprovalHRDLembur').attr('disabled', false).html('Simpan');
            }
        });
    });
    // ===================== Time validation (today) =====================
    function getNowHM() {
        var now = new Date();
        var h = now.getHours().toString().padStart(2, '0');
        var m = now.getMinutes().toString().padStart(2, '0');
        return h + ':' + m;
    }

    function isSelectedDateToday() {
        var val = $('#tanggal').val();
        if (!val) return false;
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = (today.getMonth() + 1).toString().padStart(2, '0');
        var dd = today.getDate().toString().padStart(2, '0');
        var todayStr = yyyy + '-' + mm + '-' + dd;
        return val === todayStr;
    }

    function enforceTodayTimeMin() {
        // If selected date is today, set min attributes to current time
        if (isSelectedDateToday()) {
            var minHM = getNowHM();
            $('#jam_mulai').attr('min', minHM);
            $('#jam_selesai').attr('min', minHM);
        } else {
            $('#jam_mulai').removeAttr('min');
            $('#jam_selesai').removeAttr('min');
        }
    }

    function warn(msg) {
        if (window.Swal && Swal.fire) {
            Swal.fire({ title: 'Peringatan!', text: msg, icon: 'warning', confirmButtonText: 'OK' });
        } else {
            alert(msg);
        }
    }

    function validateTimeAgainstNow(inputSelector, label) {
        if (!isSelectedDateToday()) return true;
        var val = $(inputSelector).val();
        if (!val) return true;
        var minHM = getNowHM();
        if (val < minHM) {
            warn(label + ' tidak boleh sebelum waktu saat ini');
            $(inputSelector).val('');
            // Also set min so UI prevents choosing earlier values
            $(inputSelector).attr('min', minHM);
            return false;
        }
        return true;
    }

    function validateMulaiBeforeSelesai() {
        var mulai = $('#jam_mulai').val();
        var selesai = $('#jam_selesai').val();
        if (mulai && selesai && selesai < mulai) {
            warn('Jam selesai tidak boleh sebelum jam mulai');
            $('#jam_selesai').val('');
            return false;
        }
        return true;
    }

    function validateTimesBeforeSubmit() {
        enforceTodayTimeMin();
        var ok1 = validateTimeAgainstNow('#jam_mulai', 'Jam mulai');
        var ok2 = validateTimeAgainstNow('#jam_selesai', 'Jam selesai');
        var ok3 = validateMulaiBeforeSelesai();
        return ok1 && ok2 && ok3;
    }

    $('#tanggal').on('change', function() {
        enforceTodayTimeMin();
        // Re-check current values when date changes
        validateTimeAgainstNow('#jam_mulai', 'Jam mulai');
        validateTimeAgainstNow('#jam_selesai', 'Jam selesai');
        validateMulaiBeforeSelesai();
    });

    $('#jam_mulai').on('change', function() {
        validateTimeAgainstNow('#jam_mulai', 'Jam mulai');
        // If mulai changes, ensure selesai is not before it
        validateMulaiBeforeSelesai();
    });

    $('#jam_selesai').on('change', function() {
        validateTimeAgainstNow('#jam_selesai', 'Jam selesai');
        validateMulaiBeforeSelesai();
    });
    // ==================================================================
});
</script>
@endsection

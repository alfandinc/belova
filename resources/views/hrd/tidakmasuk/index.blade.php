@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Tidak Masuk (Sakit/Izin)')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box">
                        <div class="row">
                            <div class="col">
                                <h4 class="page-title">Pengajuan Tidak Masuk (Sakit/Izin)</h4>
                            </div>
                            <div class="col-auto align-self-center">
                                <a href="#" class="btn btn-sm btn-primary" id="btnCreateTidakMasuk">
                                    <i class="fas fa-plus-circle mr-2"></i>Ajukan Tidak Masuk
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    @if(auth()->user()->hasRole('Employee'))
                    <table id="tableTidakMasukPersonal" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Jenis</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Total Hari</th>
                                <th>Status Manager</th>
                                <th>Status HRD</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                    @endif
                    @if(auth()->user()->hasRole('Manager'))
                    <table id="tableTidakMasukTeam" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pegawai</th>
                                <th>Jenis</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Total Hari</th>
                                <th>Status Manager</th>
                                <th>Status HRD</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                    @endif
                    @if(auth()->user()->hasRole('Hrd'))
                    <table id="tableTidakMasukApproval" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pegawai</th>
                                <th>Jenis</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Total Hari</th>
                                <th>Status Manager</th>
                                <th>Status HRD</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create Tidak Masuk -->
<div class="modal fade" id="modalCreateTidakMasuk" tabindex="-1" role="dialog" aria-labelledby="modalCreateTidakMasukLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajukan Tidak Masuk (Sakit/Izin)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateTidakMasuk" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="jenis">Jenis</label>
                        <select name="jenis" id="jenis" class="form-control" required>
                            <option value="">Pilih Jenis</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="alasan">Alasan</label>
                        <textarea name="alasan" id="alasan" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="bukti">Bukti (Gambar/PDF)</label>
                        <input type="file" name="bukti" id="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="form-text text-muted">File maksimal 2MB. Format: JPG, PNG, PDF.</small>
                    </div>
                    <div class="alert alert-info mt-2">Jumlah hari dihitung secara inklusif (termasuk tanggal mulai dan selesai).</div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btnSubmitTidakMasuk">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Tidak Masuk -->
<div class="modal fade" id="modalDetailTidakMasuk" tabindex="-1" role="dialog" aria-labelledby="modalDetailTidakMasukLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan Tidak Masuk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailTidakMasukBody">
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>

<!-- Modal Approval Manager -->
<div class="modal fade" id="modalApprovalManagerTidakMasuk" tabindex="-1" role="dialog" aria-labelledby="modalApprovalManagerTidakMasukLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Persetujuan Manager</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalManagerTidakMasuk">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="pengajuan_id" id="manager_tidakmasuk_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="status_manager_tidakmasuk" required>
                            <option value="">Pilih Status</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea class="form-control" name="komentar_manager" id="komentar_manager_tidakmasuk" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitApprovalManagerTidakMasuk">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Approval HRD -->
<div class="modal fade" id="modalApprovalHRDTidakMasuk" tabindex="-1" role="dialog" aria-labelledby="modalApprovalHRDTidakMasukLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Persetujuan HRD</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formApprovalHRDTidakMasuk">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="pengajuan_id" id="hrd_tidakmasuk_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="status_hrd_tidakmasuk" required>
                            <option value="">Pilih Status</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea class="form-control" name="komentar_hrd" id="komentar_hrd_tidakmasuk" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitApprovalHRDTidakMasuk">Simpan</button>
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
    var tablePersonal = $('#tableTidakMasukPersonal').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.tidakmasuk.index') }}?view=personal",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'jenis', name: 'jenis'},
            {data: 'tanggal_mulai', name: 'tanggal_mulai'},
            {data: 'tanggal_selesai', name: 'tanggal_selesai'},
            {data: 'total_hari', name: 'total_hari'},
            {data: 'status_manager', name: 'status_manager', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'status_hrd', name: 'status_hrd', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    @endif
    // DataTable untuk Manager (team)
    @if(auth()->user()->hasRole('Manager'))
    var tableTeam = $('#tableTidakMasukTeam').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.tidakmasuk.index') }}?view=team",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_nama', name: 'employee_nama'},
            {data: 'jenis', name: 'jenis'},
            {data: 'tanggal_mulai', name: 'tanggal_mulai'},
            {data: 'tanggal_selesai', name: 'tanggal_selesai'},
            {data: 'total_hari', name: 'total_hari'},
            {data: 'status_manager', name: 'status_manager', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'status_hrd', name: 'status_hrd', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    @endif
    // DataTable untuk HRD (approval)
    @if(auth()->user()->hasRole('Hrd'))
    var tableApproval = $('#tableTidakMasukApproval').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.tidakmasuk.index') }}?view=approval",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_nama', name: 'employee_nama'},
            {data: 'jenis', name: 'jenis'},
            {data: 'tanggal_mulai', name: 'tanggal_mulai'},
            {data: 'tanggal_selesai', name: 'tanggal_selesai'},
            {data: 'total_hari', name: 'total_hari'},
            {data: 'status_manager', name: 'status_manager', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'status_hrd', name: 'status_hrd', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    @endif

    // Fungsi render badge status
    function renderStatusBadge(status) {
        if (status === 'menunggu') {
            return '<span class="badge badge-warning">Menunggu</span>';
        } else if (status === 'disetujui') {
            return '<span class="badge badge-success">Disetujui</span>';
        } else if (status === 'ditolak') {
            return '<span class="badge badge-danger">Ditolak</span>';
        } else {
            return '-';
        }
    }

    // Show create modal
    $('#btnCreateTidakMasuk').click(function() {
        $('#formCreateTidakMasuk')[0].reset();
        $('#modalCreateTidakMasuk').modal('show');
    });

    // Submit create form (with file upload)
    $('#formCreateTidakMasuk').submit(function(e) {
        e.preventDefault();
        var form = $(this)[0];
        var formData = new FormData(form);
        $.ajax({
            url: "{{ route('hrd.tidakmasuk.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#btnSubmitTidakMasuk').attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            },
            success: function(response) {
                $('#modalCreateTidakMasuk').modal('hide');
                // Refresh correct DataTable
                if(typeof tablePersonal !== 'undefined') {
                    tablePersonal.ajax.reload();
                } else if(typeof tableTeam !== 'undefined') {
                    tableTeam.ajax.reload();
                } else if(typeof tableApproval !== 'undefined') {
                    tableApproval.ajax.reload();
                }
                $('#btnSubmitTidakMasuk').attr('disabled', false).html('Ajukan');
                if(window.Swal) {
                    Swal.fire('Berhasil!', 'Pengajuan tidak masuk berhasil diajukan.', 'success');
                }
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if(window.Swal) {
                    Swal.fire('Gagal!', msg, 'error');
                } else {
                    alert(msg);
                }
                $('#btnSubmitTidakMasuk').attr('disabled', false).html('Ajukan');
            }
        });
    });

    // Show detail modal
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ url('hrd/tidakmasuk') }}/" + id,
            type: "GET",
            success: function(response) {
                $('#modalDetailTidakMasukBody').html(response);
                $('#modalDetailTidakMasuk').modal('show');
            }
        });
    });

    // Show manager approval modal
    $(document).on('click', '.btn-approve-manager', function() {
        var id = $(this).data('id');
        $('#manager_tidakmasuk_id').val(id);
        $('#formApprovalManagerTidakMasuk')[0].reset();
        // Fetch current status and notes
        $.ajax({
            url: "{{ url('hrd/tidakmasuk') }}/" + id + "/approval-status",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#status_manager_tidakmasuk').val(data.status_manager);
                    $('#komentar_manager_tidakmasuk').val(data.notes_manager);
                }
                $('#modalApprovalManagerTidakMasuk').modal('show');
            },
            error: function() {
                $('#modalApprovalManagerTidakMasuk').modal('show');
            }
        });
    });

    // Submit manager approval
    $('#formApprovalManagerTidakMasuk').submit(function(e) {
        e.preventDefault();
        var id = $('#manager_tidakmasuk_id').val();
        var formData = $(this).serialize();
        $.ajax({
            url: "{{ url('hrd/tidakmasuk') }}/" + id + "/manager",
            type: "PUT",
            data: formData,
            beforeSend: function() {
                $('#btnSubmitApprovalManagerTidakMasuk').attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            },
            success: function(response) {
                $('#modalApprovalManagerTidakMasuk').modal('hide');
                if(typeof tableTeam !== 'undefined') {
                    tableTeam.ajax.reload();
                }
                $('#btnSubmitApprovalManagerTidakMasuk').attr('disabled', false).html('Simpan');
            },
            error: function(xhr) {
                alert('Terjadi kesalahan.');
                $('#btnSubmitApprovalManagerTidakMasuk').attr('disabled', false).html('Simpan');
            }
        });
    });

    // Show HRD approval modal
    $(document).on('click', '.btn-approve-hrd', function() {
        var id = $(this).data('id');
        $('#hrd_tidakmasuk_id').val(id);
        $('#formApprovalHRDTidakMasuk')[0].reset();
        // Fetch current status and notes
        $.ajax({
            url: "{{ url('hrd/tidakmasuk') }}/" + id + "/approval-status",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#status_hrd_tidakmasuk').val(data.status_hrd);
                    $('#komentar_hrd_tidakmasuk').val(data.notes_hrd);
                }
                $('#modalApprovalHRDTidakMasuk').modal('show');
            },
            error: function() {
                $('#modalApprovalHRDTidakMasuk').modal('show');
            }
        });
    });

    // Submit HRD approval
    $('#formApprovalHRDTidakMasuk').submit(function(e) {
        e.preventDefault();
        var id = $('#hrd_tidakmasuk_id').val();
        var formData = $(this).serialize();
        $.ajax({
            url: "{{ url('hrd/tidakmasuk') }}/" + id + "/hrd",
            type: "PUT",
            data: formData,
            beforeSend: function() {
                $('#btnSubmitApprovalHRDTidakMasuk').attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            },
            success: function(response) {
                $('#modalApprovalHRDTidakMasuk').modal('hide');
                if(typeof tableApproval !== 'undefined') {
                    tableApproval.ajax.reload();
                }
                $('#btnSubmitApprovalHRDTidakMasuk').attr('disabled', false).html('Simpan');
            },
            error: function(xhr) {
                alert('Terjadi kesalahan.');
                $('#btnSubmitApprovalHRDTidakMasuk').attr('disabled', false).html('Simpan');
            }
        });
    });

    // Date validation and day calculation
    function updateDayCount() {
        var startDateVal = $('#tanggal_mulai').val();
        var endDateVal = $('#tanggal_selesai').val();
        if (startDateVal && endDateVal) {
            var startDate = new Date(startDateVal + 'T00:00:00');
            var endDate = new Date(endDateVal + 'T00:00:00');
            var diffTime = Math.abs(endDate - startDate);
            var diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;
            if (diffDays < 1) { diffDays = 1; }
            var infoMessage = 'Anda akan mengajukan tidak masuk selama <b>' + diffDays + ' hari</b>.';
            if (!$('#day-info').length) {
                $('#modalCreateTidakMasuk .alert-info').append('<div id="day-info" class="mt-2">' + infoMessage + '</div>');
            } else {
                $('#day-info').html(infoMessage);
            }
        }
    }
    $('#tanggal_selesai').change(function() {
        var startDate = new Date($('#tanggal_mulai').val());
        var endDate = new Date($(this).val());
        if (endDate < startDate) {
            alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
            $(this).val('');
        } else {
            updateDayCount();
        }
    });
    $('#tanggal_mulai').change(function() {
        var startDate = new Date($(this).val());
        var endDateInput = $('#tanggal_selesai');
        if (endDateInput.val()) {
            var endDate = new Date(endDateInput.val());
            updateDayCount();
        }
    });
});
</script>
@endsection

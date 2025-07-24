@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Lembur')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 mb-3">
                    <button class="btn btn-primary" id="btnCreateLembur">Ajukan Lembur</button>
                </div>
                <div class="col-12">
                    <table class="table table-bordered" id="tableLembur">
                        <thead>
                            <tr>
                                <th>No</th>
                                @if(\App\Models\User::find(Auth::id())->hasRole('Manager') || \App\Models\User::find(Auth::id())->hasRole('Hrd'))
                                <th>Nama Pegawai</th>
                                @endif
                                <th>Tanggal</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Total Jam</th>
                                <th>Status Manager</th>
                                <th>Status HRD</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
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
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
$(document).ready(function() {
    var tableLembur = $('#tableLembur').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.lembur.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            @if(\App\Models\User::find(Auth::id())->hasRole('Manager') || \App\Models\User::find(Auth::id())->hasRole('Hrd'))
            {data: 'employee_nama', name: 'employee_nama'},
            @endif
            {data: 'tanggal', name: 'tanggal'},
            {data: 'jam_mulai', name: 'jam_mulai'},
            {data: 'jam_selesai', name: 'jam_selesai'},
            {data: 'total_jam', name: 'total_jam'},
            {data: 'status_manager', name: 'status_manager', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'status_hrd', name: 'status_hrd', orderable: false, searchable: false, render: function(data){return renderStatusBadge(data);}},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

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

    $('#btnCreateLembur').click(function() {
        $('#formCreateLembur')[0].reset();
        $('#modalCreateLembur').modal('show');
    });

    $('#formCreateLembur').submit(function(e) {
        e.preventDefault();
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
});
</script>
@endsection

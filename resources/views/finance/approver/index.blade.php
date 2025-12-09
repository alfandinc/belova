@extends('layouts.finance.app')
@section('title', 'Finance | Approver Pengajuan Dana')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Approver Pengajuan Dana</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Finance</a></li>
                            <li class="breadcrumb-item active">Approver Pengajuan Dana</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Daftar Approver</h4>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary" id="btnAddApprover">
                                <i class="fas fa-plus me-1"></i> Tambah Approver
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="approverTable" class="table table-bordered dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Jabatan</th>
                                        <th>Tingkat</th>
                                        <th>Sumber Dana</th>
                                        <th>Aktif</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Approver Modal -->
<div class="modal fade" id="approverModal" tabindex="-1" aria-labelledby="approverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approverModalLabel">Tambah Approver</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="approverForm">
                @csrf
                <input type="hidden" id="approver_id" name="approver_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="user_id">User <span class="text-danger">*</span></label>
                        <select name="user_id" id="user_id" class="form-control select2" required>
                            <option value="">-- Pilih User --</option>
                            @php $users = \App\Models\User::orderBy('name')->get(); @endphp
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="user_id-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="jabatan">Jabatan</label>
                        <input type="text" class="form-control" id="jabatan" name="jabatan">
                        <div class="invalid-feedback" id="jabatan-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="tingkat">Tingkat (urutan approval, higher first)</label>
                        <input type="number" class="form-control" id="tingkat" name="tingkat" min="1" value="1">
                        <div class="invalid-feedback" id="tingkat-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="jenis">Sumber Dana (kosong = semua)</label>
                        <select id="jenis" name="jenis" class="form-control">
                            <option value="">-- Semua Sumber Dana --</option>
                            <option value="Kas Bank">Kas Bank</option>
                            <option value="Kas Kecil">Kas Kecil</option>
                        </select>
                        <div class="invalid-feedback" id="jenis-error"></div>
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="aktif" name="aktif" value="1" checked>
                        <label class="form-check-label" for="aktif">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" id="saveApprover" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize select2 if available
    if (typeof $.fn.select2 === 'function') {
        $('#user_id').select2({ dropdownParent: $('#approverModal') });
    }

    var table = $('#approverTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{!! url('/finance/pengajuan-dana-approvers/data') !!}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'user.name', name: 'user.name', defaultContent: '' },
            { data: 'jabatan', name: 'jabatan' },
            { data: 'tingkat', name: 'tingkat' },
            { data: 'jenis', name: 'jenis' },
            { data: 'aktif_label', name: 'aktif_label', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        responsive: true,
        order: [[0, 'desc']]
    });

    // Open modal for create
    $('#btnAddApprover').on('click', function() {
        $('#approverModalLabel').text('Tambah Approver');
        $('#approverForm')[0].reset();
        $('#approver_id').val('');
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');
        if (typeof $('#user_id').select2 === 'function') { $('#user_id').val('').trigger('change'); }
        // default tingkat to 1 and jenis to empty (all)
        $('#tingkat').val(1);
        $('#jenis').val('');
        $('#approverModal').modal('show');
    });

    // Save approver (create/update)
    $('#approverForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#approver_id').val();
        var url = id ? ('/finance/pengajuan-dana-approvers/' + id) : '/finance/pengajuan-dana-approvers';
        var method = id ? 'PUT' : 'POST';

        var data = {
            user_id: $('#user_id').val(),
            jabatan: $('#jabatan').val(),
            tingkat: parseInt($('#tingkat').val() || 1),
            jenis: $('#jenis').val() || '',
            aktif: $('#aktif').is(':checked') ? 1 : 0,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        // Clear validation
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: url,
            method: method,
            data: data,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function() {
                $('#saveApprover').attr('disabled', true).text('Menyimpan...');
            },
            success: function(res) {
                Swal.fire('Sukses', res.message || 'Data tersimpan', 'success');
                $('#approverModal').modal('hide');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors || {};
                    Object.keys(errors).forEach(function(key) {
                        $('#' + key).addClass('is-invalid');
                        $('#' + key + '-error').text(errors[key][0]);
                    });
                } else {
                    Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                }
            },
            complete: function() {
                $('#saveApprover').attr('disabled', false).text('Simpan');
            }
        });
    });

    // Edit approver
    $('#approverTable').on('click', '.edit-approver', function() {
        var id = $(this).data('id');
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');
        $.ajax({
            url: '/finance/pengajuan-dana-approvers/' + id,
            method: 'GET',
            success: function(res) {
                $('#approverModalLabel').text('Edit Approver');
                $('#approver_id').val(res.id);
                $('#user_id').val(res.user_id).trigger('change');
                $('#jabatan').val(res.jabatan);
                $('#tingkat').val(res.tingkat || 1);
                $('#jenis').val(res.jenis || '');
                $('#aktif').prop('checked', res.aktif == 1);
                $('#approverModal').modal('show');
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat data', 'error');
            }
        });
    });

    // Delete approver
    $('#approverTable').on('click', '.delete-approver', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/finance/pengajuan-dana-approvers/' + id,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message || 'Data telah dihapus', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghapus data', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endsection

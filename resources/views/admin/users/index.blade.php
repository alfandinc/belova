@extends('layouts.admin.app')

@section('title', 'Users & Roles')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<style>
    #roleList .list-group-item { padding: .45rem .75rem; cursor: pointer; }
    #roleList .role-delete { visibility: hidden; }
    #roleList .list-group-item:hover .role-delete { visibility: visible; }
</style>
<div class="container-fluid">
    <div class="row">
        {{-- Roles: click to filter, add / delete unused --}}
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm mb-0">
                <div class="card-body p-2">
                    <h5 class="px-2 pt-1 mb-2">Role</h5>
                    <div class="list-group list-group-flush" id="roleList"></div>
                    <form id="roleForm" class="px-2 pt-2 border-top mt-1">
                        <div class="input-group input-group-sm">
                            <input type="text" name="name" class="form-control" placeholder="Role baru" maxlength="50" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="submit"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </form>
                    <small class="text-muted d-block px-2 pt-2">Nama role dipakai sistem untuk hak akses menu, jadi tidak bisa diganti. Role hanya bisa dihapus jika tidak dipakai user.</small>
                </div>
            </div>
        </div>

        {{-- Users --}}
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                        <div class="mb-2">
                            <h3 class="card-title mb-0">Users <span class="text-primary" id="roleFilterLabel"></span></h3>
                            <small class="text-muted">
                                {{ $summary['total'] }} user &middot; {{ $summary['active'] }} aktif
                                @if($summary['inactive']) &middot; {{ $summary['inactive'] }} nonaktif @endif
                            </small>
                        </div>
                        <div class="d-flex mb-2">
                            <select id="filterStatus" class="form-control form-control-sm mr-2" style="width:140px">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="">Semua status</option>
                            </select>
                            <button type="button" class="btn btn-primary btn-sm text-nowrap" id="btnAddUser"><i class="fas fa-plus mr-1"></i> Tambah User</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered" id="users-table" style="width:100%">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th style="width:90px">Status</th>
                                    <th style="width:220px">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form class="modal-content" id="userForm" autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Tambah User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="userId">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="userName">Nama</label>
                        <input type="text" class="form-control" id="userName" name="name" maxlength="255" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="userEmail">Email</label>
                        <input type="email" class="form-control" id="userEmail" name="email" maxlength="255" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="userPassword" id="userPasswordLabel">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="userPassword" name="password" minlength="6" autocomplete="new-password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="btnTogglePassword" title="Tampilkan"><i class="fas fa-eye"></i></button>
                            <button type="button" class="btn btn-outline-secondary" id="btnGeneratePassword">Buat acak</button>
                        </div>
                    </div>
                    <small class="text-muted" id="userPasswordHelp">Minimal 6 karakter.</small>
                </div>
                <div class="form-group mb-0">
                    <label>Role <small class="text-muted">(pilih satu atau lebih)</small></label>
                    <div class="d-flex flex-wrap" id="userRoles"></div>
                    <small class="text-muted d-none" id="userSelfNote">Anda sedang mengedit akun sendiri: role Admin tidak bisa dilepas.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSaveUser">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    var baseUrl = '{{ url('/admin/users') }}';
    var rolesUrl = '{{ url('/admin/roles') }}';
    var roles = [];
    var activeRole = @json((string) request('role', ''));

    function esc(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }

    function errorMessage(xhr) {
        var res = xhr.responseJSON || {};
        if (res.errors) return Object.values(res.errors).map(function(m) { return m[0]; }).join('<br>');
        return res.message || 'Terjadi kesalahan.';
    }

    // ---- Roles panel ----
    function renderRoles() {
        var html = '<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center' + (activeRole === '' ? ' active' : '') + '" data-role="">'
            + '<span>Semua user</span></a>';
        roles.forEach(function(role) {
            var deletable = role.users_count === 0;
            html += '<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center' + (activeRole === role.name ? ' active' : '') + '" data-role="' + esc(role.name) + '">'
                + '<span class="text-truncate">' + esc(role.name) + '</span>'
                + '<span class="text-nowrap">'
                + (deletable
                    ? '<span class="role-delete text-danger mr-2" data-id="' + role.id + '" data-name="' + esc(role.name) + '" title="Hapus role"><i class="fas fa-trash"></i></span><span class="badge badge-light">0</span>'
                    : '<span class="badge badge-pill badge-soft-primary" title="' + role.active_users_count + ' aktif dari ' + role.users_count + ' user">' + role.active_users_count + '</span>')
                + '</span></a>';
        });
        $('#roleList').html(html);
        $('#roleFilterLabel').text(activeRole ? '· ' + activeRole : '');

        // keep the checkboxes in the user form in sync (new roles appear right away)
        var checked = $('#userRoles input:checked').map(function() { return this.value; }).get();
        $('#userRoles').html(roles.map(function(role, i) {
            return '<div class="custom-control custom-checkbox mr-3 mb-2" style="min-width:150px">'
                + '<input type="checkbox" class="custom-control-input" name="roles[]" value="' + esc(role.name) + '" id="role_cb_' + i + '"' + (checked.indexOf(role.name) !== -1 ? ' checked' : '') + '>'
                + '<label class="custom-control-label" for="role_cb_' + i + '">' + esc(role.name) + '</label></div>';
        }).join(''));
    }

    function loadRoles() {
        return $.get(rolesUrl).done(function(data) { roles = data; renderRoles(); });
    }

    $('#roleList').on('click', '.list-group-item', function() {
        activeRole = $(this).data('role') || '';
        renderRoles();
        table.ajax.reload();
    });

    $('#roleList').on('click', '.role-delete', function(e) {
        e.stopPropagation();
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus role "' + name + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(function(r) {
            if (!r.value) return;
            $.ajax({ url: rolesUrl + '/' + id, type: 'DELETE' })
                .done(function(res) {
                    if (activeRole === name) { activeRole = ''; table.ajax.reload(); }
                    loadRoles();
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                })
                .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
        });
    });

    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        $.post(rolesUrl, $(form).serialize())
            .done(function(res) {
                form.reset();
                loadRoles();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
            })
            .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
    });

    // ---- Users table ----
    var table = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route('admin.users.index') }}',
            data: function(d) {
                d.role = activeRole;
                d.status = $('#filterStatus').val();
            }
        },
        order: [[0, 'asc']],
        columns: [
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'roles', name: 'roles', orderable: false },
            { data: 'status', name: 'is_active', searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_', info: '_START_-_END_ dari _TOTAL_ user', infoEmpty: 'Tidak ada user', infoFiltered: '', zeroRecords: 'Tidak ada user yang cocok', processing: 'Memuat...', paginate: { previous: '&lsaquo;', next: '&rsaquo;' } }
    });

    $('#filterStatus').on('change', function() { table.ajax.reload(); });

    function reloadAll() {
        table.ajax.reload(null, false);
        loadRoles();
    }

    // ---- Add / edit user ----
    function openForm(user) {
        $('#userForm')[0].reset();
        $('#userId').val(user ? user.id : '');
        $('#userModalLabel').text(user ? 'Edit User' : 'Tambah User');
        $('#userPassword').attr('type', 'password').prop('required', !user);
        $('#userPasswordLabel').text(user ? 'Password baru' : 'Password');
        $('#userPasswordHelp').text(user ? 'Kosongkan jika tidak ingin mengganti password. Minimal 6 karakter.' : 'Minimal 6 karakter.');
        $('#userSelfNote').toggleClass('d-none', !(user && user.is_self));
        $('#userRoles input').each(function() {
            // a new user starts with the role currently selected in the panel
            this.checked = user ? user.roles.indexOf(this.value) !== -1 : (activeRole !== '' && this.value === activeRole);
        });
        if (user) {
            $('#userName').val(user.name);
            $('#userEmail').val(user.email);
        }
        $('#userModal').modal('show');
    }

    $('#userModal').on('shown.bs.modal', function() { $('#userName').trigger('focus'); });

    $('#btnAddUser').on('click', function() { openForm(null); });

    $('#users-table').on('click', '.btn-edit-user', function() {
        $.get(baseUrl + '/' + $(this).data('id'), openForm)
            .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
    });

    $('#btnTogglePassword').on('click', function() {
        var $input = $('#userPassword');
        $input.attr('type', $input.attr('type') === 'password' ? 'text' : 'password');
    });

    $('#btnGeneratePassword').on('click', function() {
        var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        var values = new Uint32Array(10);
        window.crypto.getRandomValues(values);
        var password = Array.prototype.map.call(values, function(v) { return chars[v % chars.length]; }).join('');
        $('#userPassword').val(password).attr('type', 'text');
    });

    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        if (!$('#userRoles input:checked').length) {
            Swal.fire('Role belum dipilih', 'Pilih minimal satu role.', 'warning');
            return;
        }
        var id = $('#userId').val();
        var data = $(this).serialize() + (id ? '&_method=PUT' : '');
        var $btn = $('#btnSaveUser').prop('disabled', true);
        $.post(id ? baseUrl + '/' + id : baseUrl, data)
            .done(function(res) {
                $('#userModal').modal('hide');
                reloadAll();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
            })
            .fail(function(xhr) { Swal.fire({ icon: 'error', title: 'Gagal', html: errorMessage(xhr) }); })
            .always(function() { $btn.prop('disabled', false); });
    });

    // ---- Activate / deactivate / delete ----
    function toggleActive(id) {
        return $.post(baseUrl + '/' + id + '/toggle-active')
            .done(function(res) {
                reloadAll();
                Swal.fire({ icon: 'success', title: res.message, timer: 2000, showConfirmButton: false });
            })
            .fail(function(xhr) { Swal.fire('Gagal', errorMessage(xhr), 'error'); });
    }

    $('#users-table').on('click', '.btn-toggle-user', function() {
        var id = $(this).data('id'), name = $(this).data('name'), active = $(this).data('active') == 1;
        if (!active) { toggleActive(id); return; }
        Swal.fire({
            title: 'Nonaktifkan ' + name + '?',
            text: 'User tidak bisa login lagi (dan langsung keluar jika sedang login). Semua datanya tetap tersimpan dan bisa diaktifkan kembali kapan saja.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Nonaktifkan',
            cancelButtonText: 'Batal'
        }).then(function(r) { if (r.value) toggleActive(id); });
    });

    $('#users-table').on('click', '.btn-delete-user', function() {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus ' + name + '?',
            text: 'Akun dihapus permanen. Hanya akun yang belum punya data apa pun yang bisa dihapus; untuk staf yang keluar, gunakan Nonaktifkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(function(r) {
            if (!r.value) return;
            $.ajax({ url: baseUrl + '/' + id, type: 'DELETE' })
                .done(function(res) {
                    reloadAll();
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                })
                .fail(function(xhr) {
                    var res = xhr.responseJSON || {};
                    if (!res.can_deactivate) { Swal.fire('Tidak bisa dihapus', errorMessage(xhr), 'error'); return; }
                    Swal.fire({
                        title: 'Tidak bisa dihapus',
                        text: res.message,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Nonaktifkan saja',
                        cancelButtonText: 'Tutup'
                    }).then(function(r2) { if (r2.value) toggleActive(id); });
                });
        });
    });

    loadRoles().done(function() {
        @if(request('add'))
            openForm(null);
        @endif
    });
});
</script>
@endsection

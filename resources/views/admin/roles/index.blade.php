@extends('layouts.hrd.app')

@section('title', 'Manage Roles')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">Role Management</h3>
                <form action="{{ route('admin.roles.store') }}" method="POST" class="form-inline">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="name" class="form-control" placeholder="New role name" required>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Add Role</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:80px">ID</th>
                            <th>Role Name</th>
                            <th style="width:160px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>{{ $role->id }}</td>
                                <td>{{ $role->name }}</td>
                                <td>
                                    @php
                                        $editUrl = \Illuminate\Support\Facades\Route::has('admin.roles.edit')
                                            ? route('admin.roles.edit', $role->id)
                                            : url('/admin/roles/' . $role->id . '/edit');
                                        $destroyUrl = \Illuminate\Support\Facades\Route::has('admin.roles.destroy')
                                            ? route('admin.roles.destroy', $role->id)
                                            : url('/admin/roles/' . $role->id);
                                    @endphp
                                    <a href="{{ $editUrl }}" class="btn btn-sm btn-warning">Edit</a>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-role" data-url="{{ $destroyUrl }}" data-name="{{ $role->name }}">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function(){
        // confirm delete for roles
        $('.btn-delete-role').on('click', function(){
            var url = $(this).data('url');
            var name = $(this).data('name');
            $('#confirmDeleteModal .modal-body').text('Are you sure you want to delete role "' + name + '"?');
            $('#confirmDeleteModal form').attr('action', url);
            $('#confirmDeleteModal').modal('show');
        });
    });
</script>
@endsection

@section('modals')
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Delete</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- message set by JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <form method="POST" action="#">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

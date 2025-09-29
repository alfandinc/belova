@extends('layouts.hrd.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">User Management</h3>
                <div>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add New User</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered" id="users-table" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="width:160px">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        var table = $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: "{{ route('admin.users.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'role', name: 'role' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
            }
        });

        // Delegate delete action to a confirmation modal
        $('#users-table').on('click', '.btn-delete-user', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            var name = $(this).data('name') || 'this user';
            $('#confirmDeleteModal .modal-body').text('Are you sure you want to delete ' + name + '? This action cannot be undone.');
            $('#confirmDeleteModal form').attr('action', url);
            $('#confirmDeleteModal').modal('show');
        });
    });
</script>

@endsection

@section('modals')
<!-- Delete confirmation modal -->
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

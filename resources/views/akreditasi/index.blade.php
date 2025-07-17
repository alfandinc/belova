@extends('layouts.akreditasi.app')
@section('title', 'Dashboard | Akreditasi Belova')
@section('navbar')
    @include('layouts.akreditasi.navbar')
@endsection  
@section('content')
<div class="container mt-4">
    <h2>BAB List</h2>
    <table id="babTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
    <button id="addBabBtn" class="btn btn-primary mt-2">Add BAB</button>
</div>
@include('akreditasi.modals.bab')
@endsection
@section('scripts')
<script>
$(function() {
    var table = $('#babTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('akreditasi.index') }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Show modal for add
    $('#addBabBtn').click(function() {
        $('#babForm')[0].reset();
        $('#babId').val('');
        $('#babModal').modal('show');
    });

    // Submit form (create/update)
    $('#babForm').submit(function(e) {
        e.preventDefault();
        var id = $('#babId').val();
        var url = id ? '{{ url('akreditasi/bab') }}/' + id : '{{ route('akreditasi.bab.store') }}';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(res) {
                $('#babModal').modal('hide');
                table.ajax.reload();
            }
        });
    });

    // Edit
    $('#babTable').on('click', '.edit-btn', function() {
        var data = table.row($(this).parents('tr')).data();
        $('#babId').val(data.id);
        $('#babName').val(data.name);
        $('#babModal').modal('show');
    });

    // Delete
    $('#babTable').on('click', '.delete-btn', function() {
        var data = table.row($(this).parents('tr')).data();
        if (confirm('Delete BAB?')) {
            $.ajax({
                url: '{{ url('akreditasi/bab') }}/' + data.id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    table.ajax.reload();
                }
            });
        }
    });
});
</script>
@endsection

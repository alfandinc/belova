@extends('layouts.akreditasi.app')
@section('title', 'Dashboard | Akreditasi Belova')
@section('navbar')
    @include('layouts.akreditasi.navbar')
@endsection  
@section('content')
<div class="container mt-4">
    <h2>Standar List for BAB: {{ $bab->name }}</h2>
    <table id="standarTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
    <button id="addStandarBtn" class="btn btn-primary mt-2">Add Standar</button>
</div>
@include('akreditasi.modals.standar')
@endsection
@section('scripts')
<script>
$(function() {
    var table = $('#standarTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('akreditasi.standars', $bab->id) }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Show modal for add
    $('#addStandarBtn').click(function() {
        $('#standarForm')[0].reset();
        $('#standarId').val('');
        $('#standarModal').modal('show');
    });

    // Submit form (create/update)
    $('#standarForm').submit(function(e) {
        e.preventDefault();
        var id = $('#standarId').val();
        var url = id ? '{{ url('akreditasi/standar') }}/' + id : '{{ route('akreditasi.standar.store', $bab->id) }}';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(res) {
                $('#standarModal').modal('hide');
                table.ajax.reload();
            }
        });
    });

    // Edit
    $('#standarTable').on('click', '.edit-btn', function() {
        var data = table.row($(this).parents('tr')).data();
        $('#standarId').val(data.id);
        $('#standarName').val(data.name);
        $('#standarModal').modal('show');
    });

    // Delete
    $('#standarTable').on('click', '.delete-btn', function() {
        var data = table.row($(this).parents('tr')).data();
        if (confirm('Delete Standar?')) {
            $.ajax({
                url: '{{ url('akreditasi/standar') }}/' + data.id,
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

@extends('layouts.akreditasi.app')
@section('title', 'Dashboard | Akreditasi Belova')
@section('navbar')
    @include('layouts.akreditasi.navbar')
@endsection  
@section('content')
<div class="container mt-4">
    <h2>EP List for Standar: {{ $standar->name }}</h2>
    <table id="epTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Elemen Penilaian</th>
                <th>Kelengkapan Bukti</th>
                <th>Skor Maksimal</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
    <button id="addEpBtn" class="btn btn-primary mt-2">Add EP</button>
</div>
@include('akreditasi.modals.ep')
@endsection
@section('scripts')
<script>
$(function() {
    var table = $('#epTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('akreditasi.eps', $standar->id) }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'elemen_penilaian', name: 'elemen_penilaian' },
            { data: 'kelengkapan_bukti', name: 'kelengkapan_bukti' },
            { data: 'skor_maksimal', name: 'skor_maksimal' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Show modal for add
    $('#addEpBtn').click(function() {
        $('#epForm')[0].reset();
        $('#epId').val('');
        $('#epModal').modal('show');
    });

    // Submit form (create/update)
    $('#epForm').submit(function(e) {
        e.preventDefault();
        var id = $('#epId').val();
        var url = id ? '{{ url('akreditasi/ep') }}/' + id : '{{ route('akreditasi.ep.store', $standar->id) }}';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(res) {
                $('#epModal').modal('hide');
                table.ajax.reload();
            }
        });
    });

    // Edit
    $('#epTable').on('click', '.edit-btn', function() {
        var data = table.row($(this).parents('tr')).data();
        $('#epId').val(data.id);
        $('#epName').val(data.name);
        $('#kelengkapanBukti').val(data.kelengkapan_bukti);
        $('#skorMaksimal').val(data.skor_maksimal);
        $('#epModal').modal('show');
    });

    // Delete
    $('#epTable').on('click', '.delete-btn', function() {
        var data = table.row($(this).parents('tr')).data();
        if (confirm('Delete EP?')) {
            $.ajax({
                url: '{{ url('akreditasi/ep') }}/' + data.id,
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

@extends('layouts.erm.app')

@section('content')
<div class="container">
    <h2>Pasien List</h2>
    <a href="{{ route('erm.pasiens.create') }}" class="btn btn-primary">Create New Pasien</a>
    <table id="pasienTable" class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Tanggal Lahir</th>
                <th>Gender</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>

<script>
$(document).ready(function() {
    $('#pasienTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("erm.pasiens.index") }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'nik', name: 'nik' },
            { data: 'nama', name: 'nama' },
            { data: 'tanggal_lahir', name: 'tanggal_lahir' },
            { data: 'gender', name: 'gender' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
@endsection

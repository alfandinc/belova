@extends('layouts.workdoc.app')
@section('title', 'Notulensi Rapat')
@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Notulensi Rapat</h4>
        <a href="{{ route('workdoc.notulensi-rapat.create') }}" class="btn btn-success">Tambah Notulensi</a>
    </div>
    <div class="card">
        <div class="card-body">
            <table id="notulensi-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
$(function() {
    $('#notulensi-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('workdoc.notulensi-rapat.index') }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'title', name: 'title' },
            { data: 'date', name: 'date' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });
});
</script>
@endsection

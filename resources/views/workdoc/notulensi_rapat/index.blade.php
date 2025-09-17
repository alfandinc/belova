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
                        <th>Dibuat Oleh</th>
                        <th>Aksi</th>
                        <th>Notulensi</th>
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
                    { data: 'created_by', name: 'created_by' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'notulensi_btn', name: 'notulensi_btn', orderable: false, searchable: false },
        ]
    });
        // Handle Notulensi button click
        $('#notulensi-table').on('click', '.show-notulensi', function() {
                var notulensi = $(this).data('notulensi');
                $('#notulensiModalBody').html(notulensi);
                $('#notulensiModal').modal('show');
        });
});
</script>

<!-- Notulensi Modal (Bootstrap 4) -->
<div class="modal fade" id="notulensiModal" tabindex="-1" role="dialog" aria-labelledby="notulensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notulensiModalLabel">Notulensi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="notulensiModalBody" style="max-height:60vh; overflow-y:auto;">
            </div>
        </div>
    </div>
</div>
@endsection

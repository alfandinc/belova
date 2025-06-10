@extends('layouts.hrd.app')
@section('title', 'HRD | Tim Saya')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Anggota Tim Saya</h3>
            <a href="{{ route('hrd.division.mine') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Divisi
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="team-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Posisi</th>
                            <th>Status</th>
                            <th>Tanggal Masuk</th>
                            <th>No. HP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(function() {
    var table = $('#team-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.division.team') }}",
        columns: [
            {data: 'nik', name: 'nik'},
            {data: 'nama', name: 'nama'},
            {data: 'position.name', name: 'position.name'},
            {data: 'status_label', name: 'status', searchable: false},
            {data: 'tanggal_masuk', name: 'tanggal_masuk'},
            {data: 'no_hp', name: 'no_hp'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
});
</script>
@endpush
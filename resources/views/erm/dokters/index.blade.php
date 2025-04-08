@extends('layouts.erm.app')
@section('title', 'Data Dokter')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="card-title">Data Dokter</h4>
        </div>
        <div class="card-body">
            <a href="{{ route('erm.dokters.create') }}" class="btn btn-success mb-3">+ Tambah Dokter</a>
            <table class="table table-bordered" id="dokter-table">
                <thead>
                    <tr>
                        <th>Nama Dokter</th>
            <th>Spesialisasi</th>
            <th>SIP</th>
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
$(function () {
    $('#dokter-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('erm.dokters.index') }}",
        columns: [
            { data: 'nama_dokter', name: 'user.name' },
            { data: 'spesialisasi', name: 'spesialisasi.nama' },
            { data: 'sip', name: 'sip' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
@endsection

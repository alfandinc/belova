
@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Dokter')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h3 class="card-title m-0 font-weight-bold text-primary">Daftar Dokter</h3>
            <a href="{{ route('erm.dokters.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Tambah Dokter
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dokter-table" class="table table-bordered table-hover table-striped">
                    <thead class="thead-light">
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
</div>
@endsection

@section('scripts')
<script>
$(function () {
    $('#dokter-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"top"fl>rt<"bottom"ip><"clear">',
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ entri",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
            infoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
            paginate: {
                previous: '<i class="fas fa-chevron-left"></i>',
                next: '<i class="fas fa-chevron-right"></i>'
            },
            emptyTable: 'Tidak ada data yang tersedia'
        },
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

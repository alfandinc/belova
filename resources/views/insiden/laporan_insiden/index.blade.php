@extends('layouts.insiden.app')
@section('title', 'Laporan Insiden')
@section('navbar')
    @include('layouts.insiden.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('insiden.laporan_insiden.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Laporan Insiden</a>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="laporanInsidenTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pasien</th>
                                <th>Tanggal Insiden</th>
                                <th>Jenis Insiden</th>
                                <th>Lokasi</th>
                                <th>Pembuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script>
$(function() {
    var table = $('#laporanInsidenTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('insiden.laporan_insiden.data') }}',
        columns: [
            { data: 'id' },
            { data: 'pasien.nama', name: 'pasien.nama' },
            { data: 'tanggal_insiden' },
            { data: 'jenis_insiden' },
            { data: 'lokasi_insiden' },
            { data: 'pembuat_laporan_nama', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    // Edit button event (delegated, still using modal or you can change to full page)
    // $('#laporanInsidenTable').on('click', '.btn-edit', function() {
    //     var id = $(this).data('id');
    //     $.get('/insiden/laporan_insiden/' + id + '/edit', function(html) {
    //         $('#modalLaporanInsidenBody').html(html);
    //         $('#modalLaporanInsiden').modal('show');
    //     });
    // });
});
</script>
@endpush

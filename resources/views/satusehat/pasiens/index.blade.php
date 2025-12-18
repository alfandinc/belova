@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Pasien SatuSehat — Kunjungan Hari Ini</h4>
            <table id="pasiens-table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>No Antrian</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Pasien</th>
                        <th>Dokter</th>
                        <th>Klinik</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    $('#pasiens-table').DataTable({
        ajax: {
            url: "{{ route('satusehat.pasiens.data') }}",
            dataSrc: 'data'
        },
        columns: [
            { data: 'no_antrian' },
            { data: 'tanggal_visitation' },
            { data: 'waktu_kunjungan' },
            { data: 'pasien' },
            { data: 'dokter' },
            { data: 'klinik' },
            { data: 'status_kunjungan' }
        ],
        order: [[2, 'asc']],
        responsive: true
    });
});
</script>
@endsection

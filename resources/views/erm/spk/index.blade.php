@extends('layouts.erm.app')

@section('title', 'ERM | Daftar SPK')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Daftar SPK / Riwayat Tindakan</h3>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box"></div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table id="spkRiwayatTable" class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pasien</th>
                            <th>Tindakan</th>
                            <th>Dokter</th>
                            <th>Paket</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via DataTables AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
    $('#spkRiwayatTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/erm/spk',
        columns: [
            { data: 'tanggal', name: 'tanggal' },
            { data: 'pasien', name: 'pasien' },
            { data: 'tindakan', name: 'tindakan' },
            { data: 'dokter', name: 'dokter' },
            { data: 'paket', name: 'paket' },
            { 
                data: 'aksi', 
                name: 'aksi', 
                orderable: false, 
                searchable: false
            },
        ]
    });
});
</script>
@endsection

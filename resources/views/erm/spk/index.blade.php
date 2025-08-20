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
    <div class="row mb-3 mt-3">
        <div class="col-md-3">
            <label for="filterTanggal">Tanggal</label>
            <input type="text" id="filterTanggal" class="form-control" autocomplete="off" />
        </div>
        <div class="col-md-3">
            <label for="filterDokter">Dokter</label>
            <select id="filterDokter" class="form-control select2">
                <option value="">Semua Dokter</option>
                @foreach(\App\Models\ERM\Dokter::with('user')->get() as $dokter)
                    <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? '-' }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table id="spkRiwayatTable" class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Kunjungan</th>
                            <th>Pasien</th>
                            <th style="width: 30%;">Tindakan</th>
                            <th>Dokter</th>
                            <th style="width: 20%;">Aksi</th>
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
    // Initialize select2 for dokter filter
    $('#filterDokter').select2({
        width: '100%',
        placeholder: 'Pilih Dokter',
        allowClear: true
    });

    // Date range picker
    $('#filterTanggal').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        startDate: moment().format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD'),
        autoUpdateInput: true,
        autoApply: true
    });

    var table = $('#spkRiwayatTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/erm/spk',
            data: function(d) {
                var tanggal = $('#filterTanggal').val().split(' - ');
                d.tanggal_start = tanggal[0];
                d.tanggal_end = tanggal[1] || tanggal[0];
                d.dokter_id = $('#filterDokter').val();
            }
        },
        columns: [
            { data: 'tanggal', name: 'tanggal' },
            { data: 'jam_kunjungan', name: 'jam_kunjungan' },
            { data: 'pasien', name: 'pasien' },
            { 
                data: 'tindakan', 
                name: 'tindakan',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return data;
                    }
                    // For sorting/filtering, create a plain text version
                    return $('<div>').html(data).text();
                }
            },
            { data: 'dokter', name: 'dokter' },
            { 
                data: 'aksi', 
                name: 'aksi', 
                orderable: false, 
                searchable: false
            },
        ],
        rowCallback: function(row, data) {
            // Remove previous color classes
            $(row).removeClass('table-success table-warning');
            if (data.spk_status_color === 'green') {
                $(row).addClass('table-success');
            } else if (data.spk_status_color === 'yellow') {
                $(row).addClass('table-warning');
            }
        },
        order: [[0, 'desc'], [1, 'desc']]
    });

    $('#filterTanggal, #filterDokter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endsection

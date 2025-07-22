
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
            <a href="{{ route('hrd.dokters.create') }}" class="btn btn-primary">
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
                            <th>Due Date SIP</th>
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
    var table = $('#dokter-table').DataTable({
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
        ajax: "{{ route('hrd.dokters.index') }}",
        columns: [
            { data: 'nama_dokter', name: 'user.name' },
            { data: 'spesialisasi', name: 'spesialisasi.nama' },
            { data: 'sip', name: 'sip' },
            { data: 'due_date_sip', name: 'due_date_sip' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        createdRow: function(row, data, dataIndex) {
            // Highlight yellow if due_date_sip < 1 month from today, red if expired
            if (data.due_date_sip && data.due_date_sip !== '-') {
                var parts = data.due_date_sip.split('-'); // format: d-m-Y
                var dueDate = new Date(parts[2], parts[1] - 1, parts[0]);
                var today = new Date();
                today.setHours(0,0,0,0); // ignore time
                var oneMonthLater = new Date();
                oneMonthLater.setMonth(today.getMonth() + 1);
                if (dueDate < today) {
                    $(row).css('background-color', 'red');
                    $(row).css('color', 'white');
                } else if (dueDate <= oneMonthLater && dueDate >= today) {
                    $(row).css('background-color', 'yellow');
                }
            }
        }
    });

    // Handle edit button click
    $('#dokter-table').on('click', '.btn-edit-dokter', function() {
        var dokterId = $(this).data('id');
        var editUrl = "{{ route('hrd.dokters.edit', ['id' => 'DOKTER_ID']) }}".replace('DOKTER_ID', dokterId);
        window.location.href = editUrl;
    });
});
</script>
@endsection

@extends('layouts.laporan.app')

@section('title', 'Rekap Kehadiran Karyawan')
@section('navbar')
    @include('layouts.laporan.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Rekap Kehadiran Karyawan</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex align-items-center">
                        <label for="dateRange" class="mr-2">Filter Tanggal:</label>
                        <input type="text" id="dateRange" name="date_range" class="form-control mr-2" autocomplete="off" style="width:220px;" />
                        <a href="#" id="downloadExcelBtn" class="btn btn-success mr-2">
                            <i class="fa fa-file-excel-o"></i> Download Excel
                        </a>
                        <a href="#" id="downloadPdfBtn" class="btn btn-danger">
                            <i class="fa fa-file-pdf-o"></i> Download PDF
                        </a>
                    </div>
                    <table id="rekapKehadiranTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No Induk</th>
                                <th>Nama</th>
                                <th>Sakit</th>
                                <th>Izin</th>
                                <th>Cuti</th>
                                <th>Sisa Cuti</th>
                                <th>Jumlah Hari Masuk</th>
                                <th>On Time</th>
                                <th>Overtime (menit)</th>
                                <th>Terlambat</th>
                                <th>Menit Terlambat</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script>
$(document).ready(function() {
    // Date Range Picker
    $('#dateRange').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' s/d ',
            applyLabel: 'Pilih',
            cancelLabel: 'Batal',
            daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
            monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
            firstDay: 1
        },
        autoUpdateInput: false,
        opens: 'left',
        showDropdowns: true,
        startDate: moment().startOf('month'),
        endDate: moment(),
    });

    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' s/d ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
    });
    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
    });

    // DataTable AJAX
    var table = $('#rekapKehadiranTable').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('laporan.hrd.rekap-kehadiran.data') }}",
            data: function(d) {
                d.date_range = $('#dateRange').val();
            }
        },
        columns: [
            { data: 'no_induk' },
            { data: 'nama' },
            { data: 'sakit' },
            { data: 'izin' },
            { data: 'cuti' },
            { data: 'sisa_cuti' },
            { data: 'jumlah_hari_masuk' },
            { data: 'on_time' },
            { data: 'overtime' },
            { data: 'terlambat' },
            { data: 'menit_terlambat' }
        ]
    });
        // Download Excel and PDF with current date range
    $('#downloadExcelBtn').on('click', function(e) {
        e.preventDefault();
        var dateRange = encodeURIComponent($('#dateRange').val());
        var url = "{{ route('laporan.hrd.rekap-kehadiran.excel') }}";
        if (dateRange) url += '?date_range=' + dateRange;
        window.open(url, '_blank');
    });
    $('#downloadPdfBtn').on('click', function(e) {
        e.preventDefault();
        var dateRange = encodeURIComponent($('#dateRange').val());
        var url = "{{ route('laporan.hrd.rekap-kehadiran.pdf') }}";
        if (dateRange) url += '?date_range=' + dateRange;
        window.open(url, '_blank');
    });
});

</script>
@endpush

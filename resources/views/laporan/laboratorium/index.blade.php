@extends('layouts.laporan.app')
@section('title', 'laporan | Laporan Laboratorium')
@section('navbar')
    @include('layouts.laporan.navbar')
@endsection  

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Laporan Laboratorium</h4>
    <div class="row mb-3">
        <div class="col-md-12 mb-2">
            <button class="btn btn-success" id="btnExportExcel"><i class="fa fa-file-excel-o"></i> Export Excel</button>
            <button class="btn btn-danger" id="btnPrintPdf"><i class="fa fa-file-pdf-o"></i> Print PDF</button>
        </div>
    </div>
    <div class="row mb-3" id="labStats">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Jumlah Permintaan Lab</h6>
                    <h3 id="statPermintaanLab">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Pasien Kunjungan Lab</h6>
                    <h3 id="statPasienLab">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Total Pendapatan (Harga Jual)</h6>
                    <h3 id="statPendapatanLab">-</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <input type="text" id="dateRange" class="form-control" placeholder="Filter tanggal visit...">
        </div>
        <div class="col-md-3">
            <select id="filterDokter" class="form-control">
                <option value="">- Semua Dokter -</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterKlinik" class="form-control">
                <option value="">- Semua Klinik -</option>
            </select>
        </div>
    </div>
    <table id="labTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tanggal Visit</th>
                <th>Pasien</th>
                <th>Nama Test</th>
                <th>Dokter</th>
                <th>Klinik</th>
                <th>Harga</th>
                <th>Harga Jual</th>
                <th>Invoice</th>
            </tr>
        </thead>
    </table>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Export Excel
    $('#btnExportExcel').on('click', function() {
        var dr = $('#dateRange').val().split(' - ');
        var dokter_id = $('#filterDokter').val();
        var klinik_id = $('#filterKlinik').val();
        var url = '/laporan/laboratorium/export-excel?start_date=' + encodeURIComponent(dr[0]) + '&end_date=' + encodeURIComponent(dr[1]);
        if (dokter_id) url += '&dokter_id=' + encodeURIComponent(dokter_id);
        if (klinik_id) url += '&klinik_id=' + encodeURIComponent(klinik_id);
        window.open(url, '_blank');
    });
    // Print PDF
    $('#btnPrintPdf').on('click', function() {
        var dr = $('#dateRange').val().split(' - ');
        var dokter_id = $('#filterDokter').val();
        var klinik_id = $('#filterKlinik').val();
        var url = '/laporan/laboratorium/print-pdf?start_date=' + encodeURIComponent(dr[0]) + '&end_date=' + encodeURIComponent(dr[1]);
        if (dokter_id) url += '&dokter_id=' + encodeURIComponent(dokter_id);
        if (klinik_id) url += '&klinik_id=' + encodeURIComponent(klinik_id);
        window.open(url, '_blank');
    });
    function loadLabStats() {
        var dr = $('#dateRange').val().split(' - ');
        var dokter_id = $('#filterDokter').val();
        var klinik_id = $('#filterKlinik').val();
        $.getJSON("/laporan/laboratorium/data", {
            start_date: dr[0],
            end_date: dr[1],
            dokter_id: dokter_id,
            klinik_id: klinik_id,
            stats: 1
        }, function(data) {
            if (data.stats) {
                $('#statPermintaanLab').text(data.stats.jumlah_permintaan_lab);
                $('#statPasienLab').text(data.stats.jumlah_pasien_lab);
                $('#statPendapatanLab').text(data.stats.total_pendapatan_lab);
            }
        });
    }
    // Set default date range to this month
    var start = moment().startOf('month');
    var end = moment().endOf('month');

    $('#dateRange').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.months(),
            firstDay: 1
        },
        startDate: start,
        endDate: end,
        opens: 'left',
        autoUpdateInput: true,
    });

    var table = $('#labTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("laporan.laboratorium.data") }}',
            data: function(d) {
                var dr = $('#dateRange').val().split(' - ');
                d.start_date = dr[0];
                d.end_date = dr[1];
                d.dokter_id = $('#filterDokter').val();
                d.klinik_id = $('#filterKlinik').val();
            }
        },
        columns: [
            { data: 'tanggal_visit', name: 'tanggal_visit' },
            { data: 'pasien', name: 'pasien' },
            { data: 'nama_test', name: 'nama_test' },
            { data: 'dokter', name: 'dokter' },
            { data: 'klinik', name: 'klinik' },
            { data: 'harga', name: 'harga' },
            { data: 'harga_jual', name: 'harga_jual' },
            { data: 'invoice', name: 'invoice' },
        ]
    });

    // Reload table and stats when filter changes
    function reloadLabTableAndStats() {
        table.ajax.reload();
        loadLabStats();
    }
    $('#dateRange').on('apply.daterangepicker', reloadLabTableAndStats);
    $('#filterDokter, #filterKlinik').on('change', reloadLabTableAndStats);
    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
        reloadLabTableAndStats();
    });
    // Set initial value
    $('#dateRange').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
    // Initial load
    loadLabStats();

    // Populate dokter filter
    $.getJSON('/laporan/dokters', function(data) {
        if (Array.isArray(data)) {
            data.forEach(function(dokter) {
                $('#filterDokter').append('<option value="'+dokter.id+'">'+dokter.nama+'</option>');
            });
        }
    });
    // Populate klinik filter
    $.getJSON('/laporan/kliniks', function(data) {
        if (Array.isArray(data)) {
            data.forEach(function(klinik) {
                $('#filterKlinik').append('<option value="'+klinik.id+'">'+klinik.nama+'</option>');
            });
        }
    });
});
</script>
@endpush

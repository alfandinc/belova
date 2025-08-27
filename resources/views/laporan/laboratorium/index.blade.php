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
                                <th>Dokter</th>
                                <th>Klinik</th>
                                <th>Invoice</th>
                                <th>Total Harga Jual</th>
                                <th>Action</th>
                        </tr>
                </thead>
        </table>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Grafik Permintaan Lab per Bulan</h6>
                        <canvas id="labMonthChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for permintaan details -->
        <div class="modal fade" id="labDetailsModal" tabindex="-1" role="dialog" aria-labelledby="labDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="labDetailsModalLabel">Detail Permintaan Lab</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Test</th>
                                    <th>Harga</th>
                                    <th>Harga Jual</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="labDetailsBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function() {
    // Monthly chart
    $.getJSON('/laporan/laboratorium/monthly-stats', function(data) {
        var labels = data.labels;
        var values = data.values;
        var ctx = document.getElementById('labMonthChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Permintaan Lab',
                    data: values,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
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
            url: '/laporan/laboratorium/grouped-data',
            data: function(d) {
                var dr = $('#dateRange').val().split(' - ');
                d.start_date = dr[0];
                d.end_date = dr[1];
                d.dokter_id = $('#filterDokter').val();
                d.klinik_id = $('#filterKlinik').val();
            }
        },
        columns: [
            { data: 'tanggal_visitation', name: 'tanggal_visitation' },
            { data: 'pasien', name: 'pasien' },
            { data: 'dokter', name: 'dokter' },
            { data: 'klinik', name: 'klinik' },
            { data: 'invoice', name: 'invoice' },
            { data: 'total_harga_jual', name: 'total_harga_jual' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });
    // Show details modal
    window.showLabDetails = function(visitationId) {
        $('#labDetailsBody').html('<tr><td colspan="5">Loading...</td></tr>');
        $('#labDetailsModal').modal('show');
        $.getJSON('/laporan/laboratorium/permintaan-details/' + visitationId, function(data) {
            var html = '';
            if (data.details && data.details.length) {
                data.details.forEach(function(item) {
                    html += '<tr>' +
                        '<td>' + item.nama_test + '</td>' +
                        '<td>' + item.harga + '</td>' +
                        '<td>' + item.harga_jual + '</td>' +
                        '<td>' + item.status + '</td>' +
                        '</tr>';
                });
            } else {
                html = '<tr><td colspan="4">No data</td></tr>';
            }
            $('#labDetailsBody').html(html);
        });
    }

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

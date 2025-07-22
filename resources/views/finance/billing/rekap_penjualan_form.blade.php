@extends('layouts.finance.app')

@section('title', 'Rekap Penjualan')

@section('navbar')
    @include('layouts.finance.navbar')

@section('scripts')
<script>
function formatRupiah(num) {
    return 'Rp ' + (num ? num.toLocaleString('id-ID') : '0');
}

function updateStatistik() {
    var daterange = $('#stat_daterange').val();
    var klinikId = $('#stat_klinik').val();
    var dates = daterange.split(' - ');
    var startDate = dates[0];
    var endDate = dates.length > 1 ? dates[1] : dates[0];
    $.get("{{ route('finance.rekap-penjualan.statistik') }}", {start_date: startDate, end_date: endDate, klinik_id: klinikId}, function(data) {
        $('#stat-pendapatan').text(formatRupiah(data.pendapatan));
        $('#stat-nota').text(data.jumlahNota);
        $('#stat-kunjungan').text(data.jumlahKunjungan);
        var persen = data.persen !== null ? (data.persen >= 0 ? '+' : '') + data.persen.toFixed(2) + '%' : '-';
        $('#stat-persen').html('<span class="' + (data.persen >= 0 ? 'text-success' : 'text-danger') + '">' + persen + '</span>');
        // Update chart with daily pendapatan
        if(window.pendapatanChart) {
            var categories = data.dailyPendapatan.map(function(item){ return item.date; });
            var values = data.dailyPendapatan.map(function(item){ return item.pendapatan; });
            window.pendapatanChart.updateOptions({
                xaxis: { categories: categories },
                series: [{ name: 'Pendapatan', data: values }],
                title: { text: 'Pendapatan Harian', align: 'left' }
            });
        }
    });
}

$(function() {
    $('#stat_klinik').select2({width: '100%'});
    $('#stat_daterange').daterangepicker({
        singleDatePicker: false,
        showDropdowns: true,
        locale: { format: 'YYYY-MM-DD' },
        autoUpdateInput: true
    });

    // Inisialisasi chart satu kali
    window.pendapatanChart = new ApexCharts(document.querySelector("#chart-pendapatan"), {
        chart: { type: 'bar', height: 200 },
        series: [{ name: 'Pendapatan', data: [0] }],
        xaxis: { categories: ['Hari Ini'] },
        colors: ['#28a745'],
        dataLabels: { enabled: true, formatter: function(val){ return formatRupiah(val); } },
        title: { text: 'Pendapatan Hari Ini', align: 'left' },
        yaxis: { labels: { formatter: function(val){ return formatRupiah(val); } } }
    });
    window.pendapatanChart.render();

    $('#stat_daterange, #stat_klinik').on('change', updateStatistik);
    updateStatistik();
});
</script>
@endsection

@section('content')

<div class="container mt-4">
    <h3 class="mb-3">Statistik Pendapatan Harian</h3>
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="stat_daterange" class="form-label">Tanggal</label>
            <input type="text" id="stat_daterange" class="form-control" autocomplete="off" style="background:#fff;" />
        </div>
        <div class="col-md-4">
            <label for="stat_klinik" class="form-label">Klinik</label>
            <select name="klinik_id" id="stat_klinik" class="form-control select2">
                <option value="">Semua Klinik</option>
                @foreach($kliniks as $klinik)
                    <option value="{{ $klinik->id }}" {{ $klinikId == $klinik->id ? 'selected' : '' }}>{{ $klinik->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fw-bold text-muted mb-1">Pendapatan</div>
                    <div class="h4" id="stat-pendapatan">Rp 0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fw-bold text-muted mb-1">Jumlah Nota</div>
                    <div class="h4" id="stat-nota">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fw-bold text-muted mb-1">Jumlah Kunjungan</div>
                    <div class="h4" id="stat-kunjungan">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="fw-bold text-muted mb-1">Perubahan Pendapatan</div>
                    <div class="h4" id="stat-persen">-</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div id="chart-pendapatan"></div>
        </div>
    </div>

    <h3>Rekap Penjualan</h3>
    <form method="GET" action="{{ route('finance.rekap-penjualan.download') }}" class="row g-3 align-items-end mb-4">
        <div class="col-md-3">
            <label for="start_date" class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" id="start_date" class="form-control" required value="{{ request('start_date') }}">
        </div>
        <div class="col-md-3">
            <label for="end_date" class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" id="end_date" class="form-control" required value="{{ request('end_date') }}">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-success">Download Rekap Penjualan</button>
        </div>
    </form>

    <h3>Export Invoice</h3>
    <form method="GET" action="{{ route('finance.invoice.export.download') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="start_date" class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" id="invoice_start_date" class="form-control" required value="{{ request('start_date') }}">
        </div>
        <div class="col-md-3">
            <label for="end_date" class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" id="invoice_end_date" class="form-control" required value="{{ request('end_date') }}">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Download Invoice Excel</button>
        </div>
    </form>
</div>
@endsection

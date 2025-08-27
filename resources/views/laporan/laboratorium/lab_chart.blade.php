@extends('layouts.laporan.app')
@section('title', 'Laporan Laboratorium - Grafik Bulanan')
@section('navbar')
    @include('layouts.laporan.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Grafik Permintaan Lab per Bulan</h4>
    <canvas id="labMonthChart" height="120"></canvas>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function() {
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
});
</script>
@endpush

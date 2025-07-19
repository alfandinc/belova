@extends('layouts.marketing.app')
@section('title', 'ERM | Tambah Pasien')
@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Services Analytics</h4>
                    </div><!--end col-->
                    <div class="col-auto">
                        <div class="form-inline">
                            <div class="form-group mr-2">
                                <select class="form-control" name="year">
                                    @for($i = date('Y'); $i >= date('Y')-5; $i--)
                                        <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group mr-2">
                                <select class="form-control" name="month">
                                    <option value="">All Months</option>
                                    @foreach([
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
                                        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ] as $num => $name)
                                        <option value="{{ $num }}" {{ (isset($month) && $month == $num) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <select class="form-control" name="clinic_id">
                                    <option value="">All Clinics</option>
                                    @foreach($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" {{ $clinicId == $clinic->id ? 'selected' : '' }}>
                                            {{ $clinic->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Popular Treatments & Treatment Packages Charts -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Popular Treatments</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="popularTreatmentsChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Treatment Package Performance</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="packagePerformanceChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Visitation Trends Chart -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Monthly Visitation Trends for {{ $year }}</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="visitationTrendsChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

</div><!-- container -->
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let popularTreatmentsChart, packagePerformanceChart, visitationTrendsChart;

    function updateCharts(data) {
        // Update Popular Treatments
        popularTreatmentsChart.updateOptions({
            xaxis: { categories: data.popularTreatments.labels },
            series: [
                { name: 'Count', data: data.popularTreatments.count },
                { name: 'Revenue', data: data.popularTreatments.revenue }
            ]
        });
        // Update Package Performance
        packagePerformanceChart.updateOptions({
            xaxis: { categories: data.packagePerformance.labels },
            series: [
                { name: 'Count', data: data.packagePerformance.count },
                { name: 'Revenue', data: data.packagePerformance.revenue }
            ]
        });
        // Update Visitation Trends
        visitationTrendsChart.updateOptions({
            xaxis: { categories: data.visitationTrends.labels },
            series: [{ name: 'Visitations', data: data.visitationTrends.series }],
            title: { text: 'Monthly Visitations for ' + data.year }
        });
    }

    $(document).on('change', 'select[name="year"], select[name="month"], select[name="clinic_id"]', function(e) {
        e.preventDefault();
        const year = $('select[name="year"]').val();
        const month = $('select[name="month"]').val();
        const clinic_id = $('select[name="clinic_id"]').val();
        $.getJSON("{{ route('marketing.services.analytics.data') }}", { year, month, clinic_id }, function(data) {
            updateCharts(data);
        });
    });

    // Initial chart rendering (store chart instances)
    popularTreatmentsChart = new ApexCharts(
        document.querySelector("#popularTreatmentsChart"),
        {
            chart: { height: 350, type: 'bar', stacked: false },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            series: [
                { name: 'Count', data: @json($popularTreatments['count']) },
                { name: 'Revenue', data: @json($popularTreatments['revenue']) }
            ],
            xaxis: { categories: @json($popularTreatments['labels']), labels: { rotate: -45, style: { fontSize: '12px' } } },
            yaxis: [
                { title: { text: 'Count' } },
                { opposite: true, title: { text: 'Revenue' }, labels: { formatter: function (y) { return "Rp " + y.toLocaleString(); } } }
            ],
            fill: { opacity: 1 },
            tooltip: { y: { formatter: function (val, { seriesIndex }) { return seriesIndex === 0 ? val : "Rp " + val.toLocaleString(); } } },
            colors: ['#4e73df', '#1cc88a'],
            title: { text: 'Popular Treatments', align: 'center' }
        }
    );
    popularTreatmentsChart.render();

    packagePerformanceChart = new ApexCharts(
        document.querySelector("#packagePerformanceChart"),
        {
            chart: { height: 350, type: 'bar', stacked: false },
            plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            series: [
                { name: 'Count', data: @json($packagePerformance['count']) },
                { name: 'Revenue', data: @json($packagePerformance['revenue']) }
            ],
            xaxis: { categories: @json($packagePerformance['labels']), labels: { rotate: -45, style: { fontSize: '12px' } } },
            yaxis: [
                { title: { text: 'Count' } },
                { opposite: true, title: { text: 'Revenue' }, labels: { formatter: function (y) { return "Rp " + y.toLocaleString(); } } }
            ],
            fill: { opacity: 1 },
            tooltip: { y: { formatter: function (val, { seriesIndex }) { return seriesIndex === 0 ? val : "Rp " + val.toLocaleString(); } } },
            colors: ['#fd7e14', '#e74a3b'],
            title: { text: 'Treatment Package Performance', align: 'center' }
        }
    );
    packagePerformanceChart.render();

    visitationTrendsChart = new ApexCharts(
        document.querySelector("#visitationTrendsChart"),
        {
            chart: { height: 350, type: 'line', zoom: { enabled: true } },
            dataLabels: { enabled: false },
            stroke: { curve: 'straight', width: 3 },
            series: [{ name: 'Visitations', data: @json($visitationTrends['series']) }],
            title: { text: 'Monthly Visitations for {{ $year }}', align: 'center' },
            grid: { row: { colors: ['#f3f3f3', 'transparent'], opacity: 0.5 } },
            xaxis: { categories: @json($visitationTrends['labels']) },
            colors: ['#4e73df'],
            markers: { size: 5 }
        }
    );
    visitationTrendsChart.render();
});
</script>
@endpush
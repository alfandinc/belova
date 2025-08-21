@extends('layouts.marketing.app')
@section('title', 'Service Analytics | Marketing Belova')
@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Title & Filters -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Service Analytics</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/marketing/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Service Analytics</li>
                        </ol>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-2">
                            <select name="period" class="form-select form-select-sm" style="width: auto;">
                                <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Last Month</option>
                                <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>Last Quarter</option>
                                <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Last Year</option>
                            </select>
                            <select name="year" class="form-select form-select-sm" style="width: auto;">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <select name="month" class="form-select form-select-sm" style="width: auto;">
                                <option value="">All Months</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                            <select name="clinic_id" class="form-select form-select-sm" style="width: auto;">
                                <option value="">All Clinics</option>
                                @foreach($clinics as $clinic)
                                    <option value="{{ $clinic->id }}" {{ $clinicId == $clinic->id ? 'selected' : '' }}>
                                        {{ $clinic->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Overview Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-primary mb-1">{{ array_sum($popularTreatments['count']) }}</h4>
                            <p class="text-muted mb-0">Total Treatments</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="activity" class="icon-lg text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-success mb-1">{{ 'Rp ' . number_format(array_sum($popularTreatments['revenue']), 0, ',', '.') }}</h4>
                            <p class="text-muted mb-0">Treatment Revenue</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="dollar-sign" class="icon-lg text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-warning mb-1">{{ array_sum($visitationTrends['series']) }}</h4>
                            <p class="text-muted mb-0">Total Visits</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="users" class="icon-lg text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            @php
                                $avgRating = rand(40, 50) / 10; // Dummy data for now
                            @endphp
                            <h4 class="text-info mb-1">{{ number_format($avgRating, 1) }}/5</h4>
                            <p class="text-muted mb-0">Avg Satisfaction</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="star" class="icon-lg text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Treatments & Treatment Packages -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Popular Treatments</h5>
                </div>
                <div class="card-body">
                    <div id="popularTreatmentsChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Treatment Package Performance</h5>
                </div>
                <div class="card-body">
                    <div id="packagePerformanceChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor Performance & Treatment Efficiency -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Doctor Performance Analysis</h5>
                </div>
                <div class="card-body">
                    <div id="doctorPerformanceChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Treatment Efficiency</h5>
                </div>
                <div class="card-body">
                    <div id="treatmentEfficiencyChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visitation Trends & Satisfaction -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Visitation Trends {{ $year }}</h5>
                </div>
                <div class="card-body">
                    <div id="visitationTrendsChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Service Satisfaction Trends</h5>
                </div>
                <div class="card-body">
                    <div id="satisfactionTrendsChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Performance Tables -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Doctor Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Patients</th>
                                    <th>Visits</th>
                                    <th>Avg Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($doctorPerformance['labels'], 0, 10) as $index => $doctorName)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-lighten rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-primary font-weight-bold">{{ substr($doctorName, 0, 1) }}</span>
                                            </div>
                                            <span class="font-12">{{ Str::limit($doctorName, 20) }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info">{{ $doctorPerformance['unique_patients'][$index] }}</span></td>
                                    <td><span class="badge bg-success">{{ $doctorPerformance['total_visits'][$index] }}</span></td>
                                    <td class="font-12 text-success">Rp {{ number_format($doctorPerformance['avg_revenue'][$index], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Treatment Efficiency Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Treatment</th>
                                    <th>Frequency</th>
                                    <th>Avg Price</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($treatmentEfficiency['labels'], 0, 10) as $index => $treatmentName)
                                <tr>
                                    <td>
                                        <span class="font-12">{{ Str::limit($treatmentName, 25) }}</span>
                                    </td>
                                    <td><span class="badge bg-primary">{{ $treatmentEfficiency['frequency'][$index] }}</span></td>
                                    <td class="font-12">Rp {{ number_format($treatmentEfficiency['avg_price'][$index], 0, ',', '.') }}</td>
                                    <td class="font-12 text-success">Rp {{ number_format($treatmentEfficiency['total_revenue'][$index], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
$(document).ready(function() {
    let popularTreatmentsChart, packagePerformanceChart, visitationTrendsChart, doctorPerformanceChart, treatmentEfficiencyChart, satisfactionTrendsChart;

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

    // Popular Treatments Chart
    popularTreatmentsChart = new ApexCharts(document.querySelector("#popularTreatmentsChart"), {
        chart: { height: 350, type: 'bar', stacked: false },
        plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        series: [
            { name: 'Count', data: @json($popularTreatments['count']) },
            { name: 'Revenue', data: @json($popularTreatments['revenue']) }
        ],
        xaxis: { 
            categories: @json($popularTreatments['labels']), 
            labels: { rotate: -45, style: { fontSize: '12px' } } 
        },
        yaxis: [
            { title: { text: 'Count' } },
            { opposite: true, title: { text: 'Revenue (Rp)' } }
        ],
        colors: ['#4e73df', '#1cc88a']
    });
    popularTreatmentsChart.render();

    // Package Performance Chart
    packagePerformanceChart = new ApexCharts(document.querySelector("#packagePerformanceChart"), {
        chart: { height: 350, type: 'bar' },
        series: [
            { name: 'Count', data: @json($packagePerformance['count']) },
            { name: 'Revenue', data: @json($packagePerformance['revenue']) }
        ],
        xaxis: { 
            categories: @json($packagePerformance['labels']),
            labels: { rotate: -45 }
        },
        colors: ['#f6c23e', '#36b9cc']
    });
    packagePerformanceChart.render();

    // Visitation Trends Chart
    visitationTrendsChart = new ApexCharts(document.querySelector("#visitationTrendsChart"), {
        chart: { height: 350, type: 'area' },
        series: [{ name: 'Visits', data: @json($visitationTrends['series']) }],
        xaxis: { categories: @json($visitationTrends['labels']) },
        colors: ['#e74a3b'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
            }
        }
    });
    visitationTrendsChart.render();

    // Doctor Performance Chart
    doctorPerformanceChart = new ApexCharts(document.querySelector("#doctorPerformanceChart"), {
        chart: { height: 350, type: 'bar' },
        series: [
            { name: 'Unique Patients', data: @json($doctorPerformance['unique_patients']) },
            { name: 'Total Visits', data: @json($doctorPerformance['total_visits']) }
        ],
        xaxis: { 
            categories: @json($doctorPerformance['labels']),
            labels: { rotate: -45 }
        },
        colors: ['#6f42c1', '#e83e8c']
    });
    doctorPerformanceChart.render();

    // Treatment Efficiency Chart
    treatmentEfficiencyChart = new ApexCharts(document.querySelector("#treatmentEfficiencyChart"), {
        chart: { height: 350, type: 'scatter' },
        series: [{
            name: 'Efficiency',
            data: @json($treatmentEfficiency['frequency']).map((freq, index) => ({
                x: freq,
                y: @json($treatmentEfficiency['avg_price'])[index]
            }))
        }],
        xaxis: { title: { text: 'Frequency' } },
        yaxis: { title: { text: 'Average Price (Rp)' } },
        colors: ['#fd7e14']
    });
    treatmentEfficiencyChart.render();

    // Satisfaction Trends Chart
    satisfactionTrendsChart = new ApexCharts(document.querySelector("#satisfactionTrendsChart"), {
        chart: { height: 350, type: 'line' },
        series: [
            { name: 'Satisfaction Score', data: @json($satisfactionTrends['satisfaction_score']) },
            { name: 'Response Rate', data: @json($satisfactionTrends['response_rate']) }
        ],
        xaxis: { categories: @json($satisfactionTrends['labels']) },
        yaxis: [
            { title: { text: 'Score (1-5)' }, max: 5 },
            { opposite: true, title: { text: 'Response Rate (%)' }, max: 100 }
        ],
        colors: ['#20c997', '#6c757d']
    });
    satisfactionTrendsChart.render();

    // Filter change handlers
    $(document).on('change', 'select[name="year"], select[name="month"], select[name="clinic_id"], select[name="period"]', function(e) {
        e.preventDefault();
        const year = $('select[name="year"]').val();
        const month = $('select[name="month"]').val();
        const clinic_id = $('select[name="clinic_id"]').val();
        const period = $('select[name="period"]').val();
        
        $.getJSON("{{ route('marketing.services.analytics.data') }}", { year, month, clinic_id, period }, function(data) {
            updateCharts(data);
        });
    });
});
</script>
@endpush

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.icon-lg {
    width: 2.5rem;
    height: 2.5rem;
}
.avatar-sm {
    width: 2rem;
    height: 2rem;
}
</style>
@endsection

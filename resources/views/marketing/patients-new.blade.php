@extends('layouts.marketing.app')
@section('title', 'Patient Analytics | Marketing Belova')
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
                        <h4 class="page-title">Patient Analytics</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/marketing/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Patient Analytics</li>
                        </ol>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-2">
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

    <!-- Patient Overview Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-primary mb-1">{{ array_sum($ageDemographics['series']) }}</h4>
                            <p class="text-muted mb-0">Total Patients</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="users" class="icon-lg text-primary"></i>
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
                            <h4 class="text-success mb-1">{{ array_sum($patientLoyalty['series']) }}</h4>
                            <p class="text-muted mb-0">Total Visits</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="activity" class="icon-lg text-success"></i>
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
                            <h4 class="text-warning mb-1">{{ $retentionAnalysis['retention_rate'] }}%</h4>
                            <p class="text-muted mb-0">Retention Rate</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="repeat" class="icon-lg text-warning"></i>
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
                            <h4 class="text-info mb-1">{{ array_sum($geographicDistribution['series']) }}</h4>
                            <p class="text-muted mb-0">Geographic Areas</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="map-pin" class="icon-lg text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demographics Analysis -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Age Demographics</h5>
                </div>
                <div class="card-body">
                    <div id="ageDistributionChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Gender Distribution</h5>
                </div>
                <div class="card-body">
                    <div id="genderDistributionChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Geographic Distribution -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Geographic Distribution</h5>
                </div>
                <div class="card-body">
                    <div id="geographicDistributionChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Address Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="address-stats-table">
                            <thead>
                                <tr>
                                    <th>Area</th>
                                    <th>Count</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($addressStats as $area => $stats)
                                <tr>
                                    <td>{{ $area }}</td>
                                    <td>{{ $stats['count'] }}</td>
                                    <td>{{ $stats['percentage'] }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Loyalty & Growth Trends -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Most Loyal Patients</h5>
                </div>
                <div class="card-body">
                    <div id="patientLoyaltyChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Patient Growth Trends {{ $year }}</h5>
                </div>
                <div class="card-body">
                    <div id="growthTrendsChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Retention Analysis -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Patient Retention Analysis</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h3 class="text-primary">{{ $retentionAnalysis['total_patients'] }}</h3>
                                <p class="text-muted mb-0">Total Patients</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h3 class="text-success">{{ $retentionAnalysis['returning_patients'] }}</h3>
                                <p class="text-muted mb-0">Returning Patients</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h3 class="text-warning">{{ $retentionAnalysis['one_time_patients'] }}</h3>
                                <p class="text-muted mb-0">One-time Patients</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <h3 class="text-info">{{ $retentionAnalysis['retention_rate'] }}%</h3>
                                <p class="text-muted mb-0">Retention Rate</p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div id="retentionChart" style="height: 250px;"></div>
                        </div>
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
    let ageDistributionChart, genderDistributionChart, patientLoyaltyChart, geographicDistributionChart, growthTrendsChart, retentionChart;

    // Helper to update address table
    function updateAddressTable(addressTable) {
        let tbody = '';
        addressTable.forEach(row => {
            tbody += `<tr><td>${row.area}</td><td>${row.count}</td><td>${row.percentage}%</td></tr>`;
        });
        $("#address-stats-table tbody").html(tbody);
    }

    // Age Distribution Chart
    ageDistributionChart = new ApexCharts(document.querySelector("#ageDistributionChart"), {
        chart: { height: 350, type: 'bar' },
        series: [{ name: 'Patients', data: @json($ageDemographics['series']) }],
        xaxis: { categories: @json($ageDemographics['labels']) },
        colors: ['#4e73df'],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%' } }
    });
    ageDistributionChart.render();

    // Gender Distribution Chart
    genderDistributionChart = new ApexCharts(document.querySelector("#genderDistributionChart"), {
        chart: { height: 350, type: 'pie' },
        series: @json($genderDemographics['series']),
        labels: @json($genderDemographics['labels']),
        colors: ['#4e73df', '#e74a3b']
    });
    genderDistributionChart.render();

    // Geographic Distribution Chart
    geographicDistributionChart = new ApexCharts(document.querySelector("#geographicDistributionChart"), {
        chart: { height: 350, type: 'bar' },
        series: [{ name: 'Patients', data: @json($geographicDistribution['series']) }],
        xaxis: { 
            categories: @json($geographicDistribution['labels']),
            labels: { rotate: -45 }
        },
        colors: ['#1cc88a'],
        plotOptions: { bar: { horizontal: false } }
    });
    geographicDistributionChart.render();

    // Patient Loyalty Chart
    patientLoyaltyChart = new ApexCharts(document.querySelector("#patientLoyaltyChart"), {
        chart: { height: 350, type: 'bar' },
        series: [{ name: 'Visits', data: @json($patientLoyalty['series']) }],
        xaxis: { 
            categories: @json($patientLoyalty['labels']),
            labels: { rotate: -45 }
        },
        colors: ['#f6c23e']
    });
    patientLoyaltyChart.render();

    // Growth Trends Chart
    growthTrendsChart = new ApexCharts(document.querySelector("#growthTrendsChart"), {
        chart: { height: 350, type: 'line' },
        series: [{ name: 'New Patients', data: @json($growthTrends['series']) }],
        xaxis: { categories: @json($growthTrends['labels']) },
        colors: ['#36b9cc'],
        stroke: { width: 3 }
    });
    growthTrendsChart.render();

    // Retention Chart
    retentionChart = new ApexCharts(document.querySelector("#retentionChart"), {
        chart: { height: 250, type: 'donut' },
        series: [{{ $retentionAnalysis['returning_patients'] }}, {{ $retentionAnalysis['one_time_patients'] }}],
        labels: ['Returning Patients', 'One-time Patients'],
        colors: ['#1cc88a', '#e74a3b']
    });
    retentionChart.render();

    // Listen for filter changes
    $(document).on('change', 'select[name="year"], select[name="month"], select[name="clinic_id"]', function(e) {
        e.preventDefault();
        const year = $('select[name="year"]').val();
        const month = $('select[name="month"]').val();
        const clinic_id = $('select[name="clinic_id"]').val();
        
        $.getJSON("{{ route('marketing.patients.analytics.data') }}", { year, month, clinic_id }, function(data) {
            // Update Age Chart
            ageDistributionChart.updateOptions({
                series: [{ name: 'Patients', data: data.ageDemographics.series }],
                xaxis: { categories: data.ageDemographics.labels }
            });
            
            // Update Gender Chart
            genderDistributionChart.updateOptions({
                series: data.genderDemographics.series,
                labels: data.genderDemographics.labels
            });
            
            // Update Geographic Chart
            geographicDistributionChart.updateOptions({
                series: [{ name: 'Patients', data: data.geographicDistribution.series }],
                xaxis: { categories: data.geographicDistribution.labels }
            });
            
            // Update Loyalty Chart
            patientLoyaltyChart.updateOptions({
                series: [{ name: 'Visits', data: data.patientLoyalty.series }],
                xaxis: { categories: data.patientLoyalty.labels }
            });
            
            updateAddressTable(data.addressTable);
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
</style>
@endsection

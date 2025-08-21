@extends('layouts.marketing.app')
@section('title', 'Revenue Analytics | Marketing Belova')
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
                        <h4 class="page-title">Revenue Analytics</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/marketing/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Revenue Analytics</li>
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

    <!-- Revenue Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-primary mb-1">{{ 'Rp ' . number_format(array_sum($monthlyRevenue['series']), 0, ',', '.') }}</h4>
                            <p class="text-muted mb-0">Total Revenue {{ $year }}</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="dollar-sign" class="icon-lg text-primary"></i>
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
                            <h4 class="text-success mb-1">{{ 'Rp ' . number_format(array_sum($monthlyRevenue['series']) / 12, 0, ',', '.') }}</h4>
                            <p class="text-muted mb-0">Average Monthly</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="trending-up" class="icon-lg text-success"></i>
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
                            <h4 class="text-warning mb-1">{{ max($monthlyRevenue['series']) > 0 ? 'Rp ' . number_format(max($monthlyRevenue['series']), 0, ',', '.') : 'Rp 0' }}</h4>
                            <p class="text-muted mb-0">Peak Month</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="bar-chart-2" class="icon-lg text-warning"></i>
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
                                $growth = 0;
                                if(isset($revenueGrowth['growth_percentage'])) {
                                    $growth = array_sum($revenueGrowth['growth_percentage']) / count($revenueGrowth['growth_percentage']);
                                }
                            @endphp
                            <h4 class="text-info mb-1">{{ number_format($growth, 1) }}%</h4>
                            <p class="text-muted mb-0">Avg Growth</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="activity" class="icon-lg text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue Trend -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Revenue Trend {{ $year }}</h5>
                </div>
                <div class="card-body">
                    <div id="monthlyRevenueChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <div id="paymentMethodChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue by Doctor & Treatment Categories -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Performing Doctors</h5>
                </div>
                <div class="card-body">
                    <div id="doctorRevenueChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue by Treatment Category</h5>
                </div>
                <div class="card-body">
                    <div id="treatmentRevenueChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Growth Comparison & Daily Trends -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Year-over-Year Comparison</h5>
                </div>
                <div class="card-body">
                    <div id="revenueGrowthChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Profitable Patients</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Visits</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($topPatients['labels'], 0, 10) as $index => $patientName)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-lighten rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-primary font-weight-bold">{{ substr($patientName, 0, 1) }}</span>
                                            </div>
                                            <span class="font-12">{{ Str::limit($patientName, 20) }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info">{{ $topPatients['visits'][$index] }}</span></td>
                                    <td class="font-12 text-success">Rp {{ number_format($topPatients['spending'][$index], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($month)
    <!-- Daily Revenue Trends (shown when month is selected) -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Revenue Trends - {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h5>
                </div>
                <div class="card-body">
                    <div id="dailyRevenueChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
$(document).ready(function() {
    // Monthly Revenue Chart
    var monthlyRevenueOptions = {
        chart: {
            height: 350,
            type: 'area',
            toolbar: { show: true }
        },
        series: [{
            name: 'Revenue',
            data: @json($monthlyRevenue['series'])
        }],
        xaxis: {
            categories: @json($monthlyRevenue['labels'])
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        colors: ['#4e73df'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        }
    };
    new ApexCharts(document.querySelector("#monthlyRevenueChart"), monthlyRevenueOptions).render();

    // Doctor Revenue Chart
    var doctorRevenueOptions = {
        chart: {
            height: 350,
            type: 'bar'
        },
        series: [{
            name: 'Revenue',
            data: @json($doctorRevenue['series'])
        }],
        xaxis: {
            categories: @json($doctorRevenue['labels']),
            labels: {
                rotate: -45
            }
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        colors: ['#1cc88a'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%'
            }
        }
    };
    new ApexCharts(document.querySelector("#doctorRevenueChart"), doctorRevenueOptions).render();

    // Treatment Revenue Chart
    var treatmentRevenueOptions = {
        chart: {
            height: 350,
            type: 'donut'
        },
        series: @json($treatmentRevenue['revenue']),
        labels: @json($treatmentRevenue['labels']),
        colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6c757d']
    };
    new ApexCharts(document.querySelector("#treatmentRevenueChart"), treatmentRevenueOptions).render();

    // Payment Method Chart
    var paymentMethodOptions = {
        chart: {
            height: 350,
            type: 'pie'
        },
        series: @json($paymentMethodAnalysis['revenue']),
        labels: @json($paymentMethodAnalysis['labels']),
        colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
    };
    new ApexCharts(document.querySelector("#paymentMethodChart"), paymentMethodOptions).render();

    // Revenue Growth Chart
    var revenueGrowthOptions = {
        chart: {
            height: 350,
            type: 'line'
        },
        series: [{
            name: '{{ $year }}',
            data: @json($revenueGrowth['current_year'])
        }, {
            name: '{{ $year - 1 }}',
            data: @json($revenueGrowth['previous_year'])
        }],
        xaxis: {
            categories: @json($revenueGrowth['labels'])
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        colors: ['#4e73df', '#36b9cc'],
        stroke: {
            width: 3
        }
    };
    new ApexCharts(document.querySelector("#revenueGrowthChart"), revenueGrowthOptions).render();

    @if($month)
    // Daily Revenue Chart
    var dailyRevenueOptions = {
        chart: {
            height: 300,
            type: 'column'
        },
        series: [{
            name: 'Daily Revenue',
            data: @json($dailyRevenue['series'])
        }],
        xaxis: {
            categories: @json($dailyRevenue['labels']),
            title: { text: 'Day of Month' }
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        colors: ['#f6c23e']
    };
    new ApexCharts(document.querySelector("#dailyRevenueChart"), dailyRevenueOptions).render();
    @endif

    // Filter change handlers
    $('select[name="year"], select[name="month"], select[name="clinic_id"]').on('change', function() {
        var params = new URLSearchParams();
        var year = $('select[name="year"]').val();
        var month = $('select[name="month"]').val();
        var clinicId = $('select[name="clinic_id"]').val();
        
        if (year) params.append('year', year);
        if (month) params.append('month', month);
        if (clinicId) params.append('clinic_id', clinicId);
        
        window.location.href = '/marketing/revenue?' + params.toString();
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

@extends('layouts.marketing.app')
@section('title', 'Product Analytics | Marketing Belova')
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
                        <h4 class="page-title">Product Analytics</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/marketing/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Product Analytics</li>
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

    <!-- Product Overview Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-primary mb-1">{{ array_sum($bestSellingProducts['quantity']) }}</h4>
                            <p class="text-muted mb-0">Total Products Sold</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="package" class="icon-lg text-primary"></i>
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
                            <h4 class="text-success mb-1">{{ 'Rp ' . number_format(array_sum($bestSellingProducts['revenue']), 0, ',', '.') }}</h4>
                            <p class="text-muted mb-0">Product Revenue</p>
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
                            <h4 class="text-warning mb-1">{{ array_sum($medicationTrends['series']) }}</h4>
                            <p class="text-muted mb-0">Monthly Volume</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="trending-up" class="icon-lg text-warning"></i>
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
                                $avgProfitMargin = count($profitabilityAnalysis['profit_margin']) > 0 ? 
                                    array_sum($profitabilityAnalysis['profit_margin']) / count($profitabilityAnalysis['profit_margin']) : 0;
                            @endphp
                            <h4 class="text-info mb-1">{{ number_format($avgProfitMargin, 1) }}%</h4>
                            <p class="text-muted mb-0">Avg Profit Margin</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="pie-chart" class="icon-lg text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Selling Products & Category Performance -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Best Selling Products</h5>
                </div>
                <div class="card-body">
                    <div id="bestSellingProductsChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Performance</h5>
                </div>
                <div class="card-body">
                    <div id="categoryPerformanceChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Medication Trends & Inventory Analysis -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Medication Volume Trends {{ $year }}</h5>
                </div>
                <div class="card-body">
                    <div id="medicationTrendsChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Inventory Turnover Analysis</h5>
                </div>
                <div class="card-body">
                    <div id="inventoryTurnoverChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profitability Analysis -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Product Profitability Analysis</h5>
                </div>
                <div class="card-body">
                    <div id="profitabilityChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Performance Tables -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Product Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Revenue</th>
                                    <th>Turnover</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($bestSellingProducts['labels'], 0, 10) as $index => $productName)
                                <tr>
                                    <td>
                                        <span class="font-12">{{ Str::limit($productName, 25) }}</span>
                                    </td>
                                    <td><span class="badge bg-primary">{{ $bestSellingProducts['quantity'][$index] }}</span></td>
                                    <td class="font-12 text-success">Rp {{ number_format($bestSellingProducts['revenue'][$index], 0, ',', '.') }}</td>
                                    <td>
                                        @if(isset($inventoryTurnover['turnover_rates'][$index]))
                                            <span class="badge bg-{{ $inventoryTurnover['turnover_rates'][$index] > 0.5 ? 'success' : 'warning' }}">
                                                {{ number_format($inventoryTurnover['turnover_rates'][$index], 2) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
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
                    <h5 class="card-title mb-0">Profitability Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Revenue</th>
                                    <th>Profit</th>
                                    <th>Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($profitabilityAnalysis['labels'], 0, 10) as $index => $productName)
                                <tr>
                                    <td>
                                        <span class="font-12">{{ Str::limit($productName, 25) }}</span>
                                    </td>
                                    <td class="font-12">Rp {{ number_format($profitabilityAnalysis['revenue'][$index], 0, ',', '.') }}</td>
                                    <td class="font-12 text-{{ $profitabilityAnalysis['profit'][$index] > 0 ? 'success' : 'danger' }}">
                                        Rp {{ number_format($profitabilityAnalysis['profit'][$index], 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $profitabilityAnalysis['profit_margin'][$index] > 20 ? 'success' : ($profitabilityAnalysis['profit_margin'][$index] > 10 ? 'warning' : 'danger') }}">
                                            {{ number_format($profitabilityAnalysis['profit_margin'][$index], 1) }}%
                                        </span>
                                    </td>
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
<script>
$(document).ready(function() {
    // Best Selling Products Chart
    var bestSellingProductsChart = new ApexCharts(document.querySelector("#bestSellingProductsChart"), {
        chart: { height: 350, type: 'bar', stacked: false },
        plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        series: [
            { name: 'Quantity', data: @json($bestSellingProducts['quantity']) },
            { name: 'Revenue', data: @json($bestSellingProducts['revenue']) }
        ],
        xaxis: { 
            categories: @json($bestSellingProducts['labels']), 
            labels: { rotate: -45, style: { fontSize: '12px' } } 
        },
        yaxis: [
            { title: { text: 'Quantity' } },
            { opposite: true, title: { text: 'Revenue (Rp)' } }
        ],
        fill: { opacity: 1 },
        tooltip: {
            y: {
                formatter: function(val, opts) {
                    if (opts.seriesIndex === 1) {
                        return 'Rp ' + val.toLocaleString('id-ID');
                    }
                    return val;
                }
            }
        },
        colors: ['#4e73df', '#1cc88a']
    });
    bestSellingProductsChart.render();

    // Category Performance Chart
    var categoryPerformanceChart = new ApexCharts(document.querySelector("#categoryPerformanceChart"), {
        chart: { height: 350, type: 'donut' },
        series: @json($categoryPerformance['revenue']),
        labels: @json($categoryPerformance['labels']),
        colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
    });
    categoryPerformanceChart.render();

    // Medication Trends Chart
    var medicationTrendsChart = new ApexCharts(document.querySelector("#medicationTrendsChart"), {
        chart: { height: 350, type: 'area' },
        dataLabels: { enabled: false },
        stroke: { width: 3 },
        series: [{ name: 'Volume', data: @json($medicationTrends['series']) }],
        xaxis: { categories: @json($medicationTrends['labels']) },
        colors: ['#e83e8c'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
            }
        }
    });
    medicationTrendsChart.render();

    // Inventory Turnover Chart
    var inventoryTurnoverChart = new ApexCharts(document.querySelector("#inventoryTurnoverChart"), {
        chart: { height: 350, type: 'bar' },
        series: [
            { name: 'Turnover Rate', data: @json($inventoryTurnover['turnover_rates']) },
            { name: 'Current Stock', data: @json($inventoryTurnover['current_stock']) }
        ],
        xaxis: { 
            categories: @json($inventoryTurnover['labels']),
            labels: { rotate: -45 }
        },
        yaxis: [
            { title: { text: 'Turnover Rate' } },
            { opposite: true, title: { text: 'Stock Level' } }
        ],
        colors: ['#fd7e14', '#6c757d']
    });
    inventoryTurnoverChart.render();

    // Profitability Chart
    var profitabilityChart = new ApexCharts(document.querySelector("#profitabilityChart"), {
        chart: { height: 400, type: 'bar', stacked: true },
        series: [
            { name: 'Cost', data: @json($profitabilityAnalysis['cost']) },
            { name: 'Profit', data: @json($profitabilityAnalysis['profit']) }
        ],
        xaxis: { 
            categories: @json($profitabilityAnalysis['labels']),
            labels: { rotate: -45 }
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        colors: ['#6c757d', '#28a745'],
        plotOptions: { bar: { horizontal: false } }
    });
    profitabilityChart.render();

    // Filter change handlers
    $('select[name="year"], select[name="month"], select[name="clinic_id"], select[name="period"]').on('change', function() {
        var params = new URLSearchParams();
        var year = $('select[name="year"]').val();
        var month = $('select[name="month"]').val();
        var clinicId = $('select[name="clinic_id"]').val();
        var period = $('select[name="period"]').val();
        
        if (year) params.append('year', year);
        if (month) params.append('month', month);
        if (clinicId) params.append('clinic_id', clinicId);
        if (period) params.append('period', period);
        
        window.location.href = '/marketing/products?' + params.toString();
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

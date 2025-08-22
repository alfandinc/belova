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
                            <!-- Date Range Picker -->
                            <div class="position-relative">
                                <input type="text" id="dateRange" class="form-control form-control-sm" 
                                       placeholder="Select date range" readonly style="width: 250px;">
                                <i class="fas fa-calendar-alt position-absolute" 
                                   style="right: 10px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
                            </div>
                            
                            <!-- Clinic Filter -->
                            <select id="clinicFilter" class="form-select form-select-sm" style="width: 180px;">
                                <option value="">All Clinics</option>
                                @foreach($clinics as $clinic)
                                    <option value="{{ $clinic->id }}">{{ $clinic->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading analytics data...</p>
    </div>

    <!-- Product Overview Cards -->
    <div class="row" id="summaryCards">
        <div class="col-lg-4 col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-primary mb-1" id="totalProductsSold">-</h4>
                            <p class="text-muted mb-0">Total Products Sold</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="package" class="icon-lg text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-success mb-1" id="totalMedications">-</h4>
                            <p class="text-muted mb-0">Unique Medications</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="activity" class="icon-lg text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-warning mb-1" id="avgInventoryTurnover">-</h4>
                            <p class="text-muted mb-0">Avg Inventory Turnover</p>
                        </div>
                        <div class="ms-auto">
                            <i data-feather="trending-up" class="icon-lg text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row" id="chartsContainer">
        <!-- Best Selling Products Chart -->
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

        <!-- Medication Trends Chart -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Medication Trends</h5>
                </div>
                <div class="card-body">
                    <div id="medicationTrendsChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Include necessary libraries -->
<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/daterangepicker.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script src="{{ asset('js/apexcharts.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Chart instances
    let bestSellingProductsChart;
    let medicationTrendsChart;
    
    // Initialize date range picker
    const today = moment();
    const lastMonth = moment().subtract(1, 'month');
    
    $('#dateRange').daterangepicker({
        startDate: lastMonth,
        endDate: today,
        ranges: {
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            format: 'YYYY-MM-DD'
        }
    });

    // Initialize Select2 for clinic filter
    $('#clinicFilter').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select clinic',
        allowClear: true
    });

    // Function to destroy all existing charts
    function destroyAllCharts() {
        if (bestSellingProductsChart) {
            bestSellingProductsChart.destroy();
            bestSellingProductsChart = null;
        }
        if (medicationTrendsChart) {
            medicationTrendsChart.destroy();
            medicationTrendsChart = null;
        }
    }

    // Function to load analytics data
    function loadAnalyticsData() {
        const dateRange = $('#dateRange').data('daterangepicker');
        const startDate = dateRange.startDate.format('YYYY-MM-DD');
        const endDate = dateRange.endDate.format('YYYY-MM-DD');
        const clinicId = $('#clinicFilter').val();

        // Show loading state
        $('#loadingState').show();
        $('#summaryCards, #chartsContainer').hide();

        $.ajax({
            url: '{{ route("marketing.products.analytics.data") }}',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                clinic_id: clinicId
            },
            success: function(response) {
                if (response.success) {
                    updateSummaryCards(response.data.summary);
                    updateCharts(response.data.charts);
                } else {
                    console.error('Error:', response.message);
                    alert('Error loading data: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr);
                let errorMessage = 'Failed to load analytics data';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage += ': ' + xhr.responseJSON.message;
                }
                alert(errorMessage);
            },
            complete: function() {
                // Hide loading state
                $('#loadingState').hide();
                $('#summaryCards, #chartsContainer').show();
            }
        });
    }

    // Function to update summary cards
    function updateSummaryCards(summary) {
        $('#totalProductsSold').text(summary.total_products_sold.toLocaleString());
        $('#totalMedications').text(summary.total_medications.toLocaleString());
        $('#avgInventoryTurnover').text(summary.avg_inventory_turnover);
    }

    // Function to update charts
    function updateCharts(charts) {
        // Destroy existing charts first
        destroyAllCharts();

        // Best Selling Products Chart
        if (charts.best_selling_products && charts.best_selling_products.labels.length > 0) {
            const bestSellingOptions = {
                series: [{
                    name: 'Quantity Sold',
                    data: charts.best_selling_products.values
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4
                    }
                },
                xaxis: {
                    categories: charts.best_selling_products.labels
                },
                colors: ['#3B82F6'],
                grid: {
                    borderColor: '#e7e7e7'
                }
            };
            bestSellingProductsChart = new ApexCharts(document.querySelector("#bestSellingProductsChart"), bestSellingOptions);
            bestSellingProductsChart.render();
        } else {
            document.getElementById('bestSellingProductsChart').innerHTML = '<div class="text-center py-5"><p class="text-muted">No data available</p></div>';
        }

        // Medication Trends Chart
        if (charts.medication_trends && charts.medication_trends.labels.length > 0) {
            const trendsOptions = {
                series: [{
                    name: 'Quantity',
                    data: charts.medication_trends.values
                }],
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: charts.medication_trends.labels
                },
                colors: ['#10B981'],
                grid: {
                    borderColor: '#e7e7e7'
                }
            };
            medicationTrendsChart = new ApexCharts(document.querySelector("#medicationTrendsChart"), trendsOptions);
            medicationTrendsChart.render();
        } else {
            document.getElementById('medicationTrendsChart').innerHTML = '<div class="text-center py-5"><p class="text-muted">No data available</p></div>';
        }
    }

    // Event listeners
    $('#dateRange').on('apply.daterangepicker', function() {
        loadAnalyticsData();
    });

    $('#clinicFilter').on('change', function() {
        loadAnalyticsData();
    });

    // Initial load
    loadAnalyticsData();

    // Initialize Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}">
@endsection

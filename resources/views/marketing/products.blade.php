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

                            <!-- Category Filter -->
                            <select id="categoryFilter" class="form-select form-select-sm" style="width: 180px;">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
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
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Best Selling Products</h5>
                    <div>
                        <button id="viewAllProductsBtn" type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewAllProductsModal">
                            View All
                        </button>
                    </div>
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

    <!-- View All Products Modal -->
    <div class="modal fade" id="viewAllProductsModal" tabindex="-1" role="dialog" aria-labelledby="viewAllProductsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAllProductsModalLabel">All Sold Products</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <button id="downloadExcelBtn" type="button" class="btn btn-sm btn-success">Download Excel</button>
                    </div>
                    <div class="table-responsive">
                        <table id="viewAllProductsTable" class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
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
<!-- SheetJS for client-side Excel export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<script>
$(document).ready(function() {
    // Chart instances
    let bestSellingProductsChart;
    let medicationTrendsChart;
    // cached best selling products data for modal/export
    let bestSellingProductsData = null;
    
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

    // Initialize Select2 for clinic and category filters
    $('#clinicFilter, #categoryFilter').select2({
        theme: 'bootstrap-5',
        placeholder: function() {
            return $(this).attr('id') === 'clinicFilter' ? 'Select clinic' : 'Select category';
        },
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
        const kategori = $('#categoryFilter').val();

        // Show loading state
        $('#loadingState').show();
        $('#summaryCards, #chartsContainer').hide();

        $.ajax({
            url: '{{ route("marketing.products.analytics.data") }}',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                clinic_id: clinicId,
                kategori: kategori
            },
            success: function(response) {
                if (response.success) {
                    updateSummaryCards(response.data.summary);
                    updateCharts(response.data.charts);
                    // cache best selling products for modal/export
                    if (response.data && response.data.charts && response.data.charts.best_selling_products) {
                        bestSellingProductsData = response.data.charts.best_selling_products;
                    } else {
                        bestSellingProductsData = null;
                    }
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
                },
                tooltip: {
                    custom: function({series, seriesIndex, dataPointIndex, w}) {
                        const productName = charts.best_selling_products.labels[dataPointIndex];
                        const quantity = charts.best_selling_products.values[dataPointIndex];
                        const revenue = charts.best_selling_products.revenue[dataPointIndex];
                        const category = charts.best_selling_products.categories ? charts.best_selling_products.categories[dataPointIndex] : '';
                        
                        return `<div class="custom-tooltip p-3">
                            <div><strong>${productName}</strong></div>
                            ${category ? `<div class="text-muted">Category: ${category}</div>` : ''}
                            <div>Quantity: ${quantity.toLocaleString()}</div>
                            <div>Revenue: Rp ${revenue.toLocaleString()}</div>
                        </div>`;
                    }
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

    $('#clinicFilter, #categoryFilter').on('change', function() {
        loadAnalyticsData();
    });

    // Initial load
    loadAnalyticsData();

    // When View All clicked, fetch full list (not limited) from server and populate modal
    let bestSellingProductsAllData = null;
    $('#viewAllProductsBtn').on('click', function() {
        const $tbody = $('#viewAllProductsTable tbody');
        $tbody.empty();

        const dateRange = $('#dateRange').data('daterangepicker');
        const startDate = dateRange.startDate.format('YYYY-MM-DD');
        const endDate = dateRange.endDate.format('YYYY-MM-DD');
        const clinicId = $('#clinicFilter').val();
        const kategori = $('#categoryFilter').val();

        // Show a loading row
        $tbody.append('<tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>');

        $.ajax({
            url: '{{ route("marketing.products.analytics.all") }}',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                clinic_id: clinicId,
                kategori: kategori
            },
            success: function(res) {
                $tbody.empty();
                if (res.success && res.data && res.data.length > 0) {
                    bestSellingProductsAllData = res.data;
                    for (let i = 0; i < res.data.length; i++) {
                        const row = res.data[i];
                        const rev = row.total_revenue != null ? parseFloat(row.total_revenue) : 0;
                        const qty = row.total_quantity != null ? row.total_quantity : '';
                        $tbody.append(`<tr>
                            <td>${i+1}</td>
                            <td>${row.product_name}</td>
                            <td>${row.category || '-'}</td>
                            <td>${qty}</td>
                            <td>Rp ${rev ? rev.toLocaleString() : '-'}</td>
                        </tr>`);
                    }
                } else {
                    bestSellingProductsAllData = null;
                    $tbody.append('<tr><td colspan="5" class="text-center text-muted py-4">No data available</td></tr>');
                }
            },
            error: function(xhr) {
                $tbody.empty();
                $tbody.append('<tr><td colspan="5" class="text-center text-danger py-4">Failed to load data</td></tr>');
                console.error('Error fetching full products list', xhr);
            }
        });
    });

    // Export the full products list previously fetched from server
    $('#downloadExcelBtn').on('click', function() {
        if (!bestSellingProductsAllData || bestSellingProductsAllData.length === 0) {
            alert('No data to export. Open "View All" first to load data.');
            return;
        }

        const rows = bestSellingProductsAllData.map(function(r, idx) {
            return {
                No: idx + 1,
                Product: r.product_name || '',
                Category: r.category || '',
                Quantity: r.total_quantity != null ? r.total_quantity : '',
                Revenue: r.total_revenue != null ? r.total_revenue : ''
            };
        });

        const worksheet = XLSX.utils.json_to_sheet(rows);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'AllProducts');
        XLSX.writeFile(workbook, `products_sold_all_${moment().format('YYYYMMDD')}.xlsx`);
    });

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
<style>
.custom-tooltip {
    background: white;
    border: 1px solid #e7e7e7;
    border-radius: 6px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    font-size: 13px;
}
</style>
@endsection

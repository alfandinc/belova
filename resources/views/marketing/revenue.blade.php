@extends('layouts.marketing.app')
@section('title', 'Revenue Analytics | Marketing Belova')
@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
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

/* Fix chart container overlapping issues */
.chart-container {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

/* Ensure cards don't overlap */
.card {
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

/* Fix ApexCharts positioning issues */
.apexcharts-canvas {
    position: relative !important;
}

/* Loading spinner styling */
.chart-container .text-center {
    padding: 2rem;
    color: #6c757d;
}

/* Prevent text wrapping issues in summary cards */
.card-body h4 {
    word-break: break-word;
    font-size: 1.25rem;
    line-height: 1.2;
}

/* Select2 customization for clinic filter */
.select2-container--bootstrap4 .select2-selection--single {
    height: calc(1.5em + 0.5rem + 2px) !important;
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
    border-color: #ced4da !important;
}

.select2-container--bootstrap4 .select2-selection__rendered {
    padding-left: 0 !important;
    padding-right: 0 !important;
    line-height: calc(1.5em + 0.5rem) !important;
}

.select2-container--bootstrap4 .select2-selection__arrow {
    height: calc(1.5em + 0.5rem) !important;
}

/* Ensure Select2 dropdown appears above other elements */
.select2-dropdown {
    z-index: 9999 !important;
}

.revenue-ranking-table tbody tr td,
.revenue-ranking-table tbody tr th {
    vertical-align: middle;
}

.ranking-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 1rem;
}

.ranking-filter-row .form-control,
.ranking-filter-row .form-select {
    min-width: 220px;
}

.ranking-filter-row .ranking-search {
    flex: 1 1 220px;
}

.treatment-detail-trigger {
    color: #2f5aa8;
    cursor: pointer;
    text-decoration: none;
}

.treatment-detail-trigger:hover,
.treatment-detail-trigger:focus {
    color: #1d3f79;
    text-decoration: underline;
}
</style>
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
                            <select name="clinic" id="clinicFilter" class="form-select form-select-sm" style="width: 250px;">
                                <option value="">All Clinics</option>
                            </select>
                            <div class="input-group" style="width: 280px;">
                                <input type="text" id="daterange" class="form-control form-control-sm" placeholder="Select Date Range" readonly>
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            </div>
                            <button type="button" id="clearDateRange" class="btn btn-outline-secondary btn-sm" title="Clear Date Range">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
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
                            <h4 class="text-primary mb-1 total-revenue">Rp 0</h4>
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
                            <h4 class="text-success mb-1 avg-revenue">Rp 0</h4>
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
                            <h4 class="text-warning mb-1 peak-revenue">Rp 0</h4>
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
                            <h4 class="text-info mb-1 growth-percentage">0%</h4>
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
                    <div id="monthlyRevenueChart" class="chart-container" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <div id="paymentMethodChart" class="chart-container" style="height: 350px;"></div>
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
                    <div id="doctorRevenueChart" class="chart-container" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue by Treatment Category</h5>
                </div>
                <div class="card-body">
                    <div id="treatmentRevenueChart" class="chart-container" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-0">Top Obat Revenue</h5>
                    <div class="text-end">
                        <div class="small text-muted">Total Revenue</div>
                        <div class="font-weight-bold" id="topObatRevenueTotal">Rp 0</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ranking-filter-row">
                        <select id="topObatCategoryFilter" class="form-select form-select-sm">
                            <option value="">Semua kategori obat</option>
                            @foreach(($obatCategories ?? []) as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="topObatSearch" class="form-control form-control-sm ranking-search" placeholder="Search obat revenue...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm revenue-ranking-table mb-0">
                            <tbody id="topObatRevenueTable">
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-0">Top Treatment Revenue</h5>
                    <div class="text-end">
                        <div class="small text-muted">Total Revenue</div>
                        <div class="font-weight-bold" id="topTreatmentRevenueTotal">Rp 0</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ranking-filter-row">
                        <select id="topTreatmentSpecialtyFilter" class="form-select form-select-sm">
                            <option value="">Semua spesialisasi</option>
                            @foreach(($treatmentSpecialties ?? []) as $specialty)
                                <option value="{{ $specialty->id }}">{{ $specialty->nama }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="topTreatmentSearch" class="form-control form-control-sm ranking-search" placeholder="Search treatment revenue...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm revenue-ranking-table mb-0">
                            <tbody id="topTreatmentRevenueTable">
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
                    <div id="revenueGrowthChart" class="chart-container" style="height: 350px;"></div>
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
                            <tbody id="topPatientsTable">
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Revenue Trends (shown when month is selected) -->
    <div class="row" id="dailyRevenueSection" style="display: none;">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Revenue Trends</h5>
                </div>
                <div class="card-body">
                    <div id="dailyRevenueChart" class="chart-container" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="treatmentVisitationDetailModal" tabindex="-1" aria-labelledby="treatmentVisitationDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="treatmentVisitationDetailModalLabel">Visitation Details</h5>
                        <small class="text-muted" id="treatmentVisitationDetailModalSubtitle">Loading treatment details...</small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="treatmentVisitationDetailBody">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin"></i><br>Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Global variables to store chart instances
var chartInstances = {};
var dateRangePicker = null;
var rankingSearchTimer = null;
var treatmentVisitationDetailUrlTemplate = '{{ route("marketing.revenue.analytics.treatment-visitations", ["treatmentId" => "__TREATMENT_ID__"]) }}';

$(document).ready(function() {
    console.log('Document ready, jQuery version:', $.fn.jquery);
    console.log('Testing jQuery selectors:', {
        totalRevenue: $('.total-revenue').length,
        monthlyChart: $('#monthlyRevenueChart').length
    });
    
    // Initialize Select2 for clinic filter
    initializeClinicFilter();
    
    // Initialize date range picker
    initializeDateRangePicker();
    
    // Load data immediately after DOM is ready
    setTimeout(function() {
        // Destroy any existing charts first to prevent conflicts
        if (window.ApexCharts && window.ApexCharts.exec) {
            try {
                window.ApexCharts.exec('monthlyRevenueChart', 'destroy');
                window.ApexCharts.exec('doctorRevenueChart', 'destroy');
                window.ApexCharts.exec('treatmentRevenueChart', 'destroy');
                window.ApexCharts.exec('paymentMethodChart', 'destroy');
                window.ApexCharts.exec('revenueGrowthChart', 'destroy');
                window.ApexCharts.exec('dailyRevenueChart', 'destroy');
            } catch (e) {
                // Ignore destroy errors
            }
        }
        loadRevenueData();
    }, 500);
});

function loadRevenueData() {
    // Show loading state
    showLoadingState();
    
    // Get current filter values
    var dateRange = getDateRangeValues();
    var clinicId = $('#clinicFilter').val();
    
    console.log('Loading revenue data with filters:', {dateRange, clinicId});
    
    // Make AJAX request
    $.ajax({
        url: '{{ route("marketing.revenue.analytics.data") }}',
        method: 'GET',
        data: {
            year: new Date().getFullYear(), // Default year for fallback
            month: '', // Empty month for all months
            start_date: dateRange.start,
            end_date: dateRange.end,
            clinic_id: clinicId,
            obat_q: $('#topObatSearch').val() || '',
            obat_kategori: $('#topObatCategoryFilter').val() || '',
            treatment_q: $('#topTreatmentSearch').val() || '',
            treatment_spesialisasi_id: $('#topTreatmentSpecialtyFilter').val() || ''
        },
        success: function(response) {
            console.log('AJAX success, response:', response);
            hideLoadingState();
            
            // Extract data from response wrapper
            var data = response.data || response;
            console.log('Extracted data:', data);
            
            updateDashboard(data);
            initializeCharts(data);
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', {xhr, status, error});
            hideLoadingState();
            showErrorState();
        }
    });
}

function initializeClinicFilter() {
    // Initialize Select2 for clinic filter
    $('#clinicFilter').select2({
        placeholder: 'Select Clinic',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap4'
    });
    
    // Load clinic options via AJAX
    $.ajax({
        url: '{{ route("marketing.clinics") }}',
        method: 'GET',
        success: function(response) {
            var clinics = response.data || response;
            var options = '<option value="">All Clinics</option>';
            
            clinics.forEach(function(clinic) {
                options += '<option value="' + clinic.id + '">' + clinic.nama + '</option>';
            });
            
            $('#clinicFilter').html(options);
        },
        error: function(xhr, status, error) {
            console.error('Error loading clinics:', {xhr, status, error});
        }
    });
    
    // Handle clinic filter change
    $('#clinicFilter').on('change', function() {
        loadRevenueData();
    });

    $('#topObatCategoryFilter, #topTreatmentSpecialtyFilter').on('change', function() {
        loadRevenueData();
    });

    $('#topObatSearch, #topTreatmentSearch').on('input', function() {
        clearTimeout(rankingSearchTimer);
        rankingSearchTimer = setTimeout(function() {
            loadRevenueData();
        }, 250);
    });
}

function initializeDateRangePicker() {
    if (typeof moment === 'undefined') {
        console.error('Moment.js is not loaded');
        return;
    }
    
    // Initialize daterangepicker
    $('#daterange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'DD/MM/YYYY'
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        }
    });
    
    // Handle date range selection
    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        
        // Reload data
        loadRevenueData();
    });
    
    // Handle date range clear
    $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        loadRevenueData();
    });
    
    // Clear button handler
    $('#clearDateRange').on('click', function() {
        $('#daterange').val('');
        loadRevenueData();
    });
}

function getDateRangeValues() {
    var dateRangeValue = $('#daterange').val();
    if (!dateRangeValue) {
        return { start: null, end: null };
    }
    
    var dates = dateRangeValue.split(' - ');
    if (dates.length === 2) {
        return {
            start: moment(dates[0], 'DD/MM/YYYY').format('YYYY-MM-DD'),
            end: moment(dates[1], 'DD/MM/YYYY').format('YYYY-MM-DD')
        };
    }
    
    return { start: null, end: null };
}

function showLoadingState() {
    // Show loading spinners in charts
    $('.chart-container').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin"></i><br>Loading...</div>');
    
    // Show loading in summary cards
    $('.total-revenue, .avg-revenue, .peak-revenue, .growth-percentage').html('<i class="fas fa-spinner fa-spin"></i>');
    
    // Show loading in patients table
    $('#topPatientsTable').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');

    $('#topObatRevenueTotal').text('Rp 0');
    $('#topTreatmentRevenueTotal').text('Rp 0');
    $('#topObatRevenueTable').html('<tr><td colspan="2" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    $('#topTreatmentRevenueTable').html('<tr><td colspan="2" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
}

function hideLoadingState() {
    // Clear loading states - they will be replaced by actual content
    console.log('Hiding loading state');
}

function showErrorState() {
    $('.chart-container').html('<div class="text-center text-danger py-5"><i class="fas fa-exclamation-triangle"></i><br>Error loading data</div>');
    $('#topObatRevenueTable').html('<tr><td colspan="2" class="text-center text-danger">Error loading data</td></tr>');
    $('#topTreatmentRevenueTable').html('<tr><td colspan="2" class="text-center text-danger">Error loading data</td></tr>');
}

function formatCurrency(value) {
    var amount = parseFloat(value || 0);
    return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
}

function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function renderTreatmentNameCell(item, index) {
    var label = (index + 1) + '. ' + (item.name || '-');

    if (!item || !item.id) {
        return escapeHtml(label);
    }

    return '<button type="button" class="btn btn-link btn-sm p-0 align-baseline treatment-detail-trigger" data-treatment-id="' +
        escapeHtml(item.id) + '" data-treatment-name="' + escapeHtml(item.name || 'Treatment') + '">' +
        escapeHtml(label) +
    '</button>';
}

function renderRevenueRankingTable(tableSelector, totalSelector, payload, emptyText, options) {
    var rows = payload && payload.items ? payload.items : [];
    var config = options || {};
    $(totalSelector).text(formatCurrency(payload && payload.total_revenue ? payload.total_revenue : 0));

    if (!rows.length) {
        $(tableSelector).html('<tr><td colspan="2" class="text-center text-muted">' + (emptyText || 'No data available') + '</td></tr>');
        return;
    }

    var html = '';
    rows.forEach(function(item, index) {
        var nameHtml = config.nameRenderer ? config.nameRenderer(item, index) : escapeHtml((index + 1) + '. ' + (item.name || '-'));
        html += '<tr>' +
            '<th>' + nameHtml + '</th>' +
            '<td class="text-end">' + formatCurrency(item.revenue || 0) + '</td>' +
        '</tr>';
    });

    $(tableSelector).html(html);
}

function updateDashboard(data) {
    console.log('Updating dashboard...');
    
    // Update summary cards
    if (data.monthlyRevenue && data.monthlyRevenue.series) {
        try {
            var totalRevenue = data.monthlyRevenue.series.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
            var avgRevenue = totalRevenue / 12;
            var peakRevenue = Math.max(...data.monthlyRevenue.series.map(v => parseFloat(v)));
            
            // Format numbers safely
            var totalFormatted = 'Rp ' + Math.round(totalRevenue).toLocaleString('en-US');
            var avgFormatted = 'Rp ' + Math.round(avgRevenue).toLocaleString('en-US');
            var peakFormatted = 'Rp ' + Math.round(peakRevenue).toLocaleString('en-US');
            
            $('.total-revenue').text(totalFormatted);
            $('.avg-revenue').text(avgFormatted);
            $('.peak-revenue').text(peakFormatted);
            
            console.log('Updated summary cards successfully');
        } catch (e) {
            console.error('Error calculating revenue totals:', e);
        }
    }
    
    if (data.revenueGrowth && data.revenueGrowth.growth_percentage !== undefined) {
        try {
            var growth = parseFloat(data.revenueGrowth.growth_percentage);
            var growthText = growth >= 0 ? '+' + growth.toFixed(1) + '%' : growth.toFixed(1) + '%';
            $('.growth-percentage').text(growthText);
        } catch (e) {
            console.error('Error updating growth percentage:', e);
        }
    }
    
    // Update top patients table
    if (data.topPatients && data.topPatients.labels) {
        var tableHtml = '';
        var maxPatients = Math.min(10, data.topPatients.labels.length);
        
        for (var i = 0; i < maxPatients; i++) {
            var patientName = data.topPatients.labels[i];
            var visits = data.topPatients.visits[i] || 0;
            var spending = data.topPatients.spending[i] || 0;
            var initial = patientName.charAt(0).toUpperCase();
            var truncatedName = patientName.length > 20 ? patientName.substring(0, 20) + '...' : patientName;
            
            tableHtml += '<tr>' +
                '<td>' +
                    '<div class="d-flex align-items-center">' +
                        '<div class="avatar-sm bg-primary-lighten rounded-circle d-flex align-items-center justify-content-center me-2">' +
                            '<span class="text-primary font-weight-bold">' + initial + '</span>' +
                        '</div>' +
                        '<span class="font-12">' + truncatedName + '</span>' +
                    '</div>' +
                '</td>' +
                '<td><span class="badge bg-info">' + visits + '</span></td>' +
                '<td class="font-12 text-success">Rp ' + spending.toLocaleString('id-ID') + '</td>' +
            '</tr>';
        }
        
        if (tableHtml === '') {
            tableHtml = '<tr><td colspan="3" class="text-center text-muted">No data available</td></tr>';
        }
        
        $('#topPatientsTable').html(tableHtml);
    }

    renderRevenueRankingTable('#topObatRevenueTable', '#topObatRevenueTotal', data.topObatRevenue, 'No obat revenue data');
    renderRevenueRankingTable('#topTreatmentRevenueTable', '#topTreatmentRevenueTotal', data.topTreatmentRevenue, 'No treatment revenue data', {
        nameRenderer: renderTreatmentNameCell
    });
    
    // Show/hide daily revenue section based on whether month is selected
    var month = $('select[name="month"]').val();
    if (month && data.dailyRevenue) {
        $('#dailyRevenueSection').show();
    } else {
        $('#dailyRevenueSection').hide();
    }
}

function openTreatmentVisitationDetails(treatmentId, treatmentName) {
    var modalElement = document.getElementById('treatmentVisitationDetailModal');
    var modalBody = $('#treatmentVisitationDetailBody');
    var modalSubtitle = $('#treatmentVisitationDetailModalSubtitle');
    var detailUrl = treatmentVisitationDetailUrlTemplate.replace('__TREATMENT_ID__', encodeURIComponent(treatmentId));
    var dateRange = getDateRangeValues();

    $('#treatmentVisitationDetailModalLabel').text('Visitation Details');
    modalSubtitle.text(treatmentName || 'Treatment');
    modalBody.html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin"></i><br>Loading...</div>');

    if (window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    } else {
        $('#treatmentVisitationDetailModal').modal('show');
    }

    $.ajax({
        url: detailUrl,
        method: 'GET',
        data: {
            year: new Date().getFullYear(),
            start_date: dateRange.start,
            end_date: dateRange.end,
            clinic_id: $('#clinicFilter').val() || '',
            treatment_spesialisasi_id: $('#topTreatmentSpecialtyFilter').val() || ''
        },
        success: function(response) {
            var data = response.data || response;
            var items = data.items || [];
            var subtitle = data.treatment_name || treatmentName || 'Treatment';
            var html = '';

            modalSubtitle.text(subtitle);

            if (!items.length) {
                modalBody.html('<div class="text-center py-4 text-muted">No visitation details found for this treatment.</div>');
                return;
            }

            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped align-middle mb-0">';
            html += '<thead><tr><th>Date</th><th>Patient</th><th>Doctor</th><th>Clinic</th><th class="text-center">Qty</th><th class="text-end">Revenue</th><th class="text-end">Action</th></tr></thead><tbody>';

            items.forEach(function(item) {
                html += '<tr>' +
                    '<td>' + escapeHtml(item.visitation_date || '-') + '</td>' +
                    '<td>' + escapeHtml(item.patient_name || '-') + '</td>' +
                    '<td>' + escapeHtml(item.doctor_name || '-') + '</td>' +
                    '<td>' + escapeHtml(item.clinic_name || '-') + '</td>' +
                    '<td class="text-center">' + escapeHtml(item.treatment_count || 0) + '</td>' +
                    '<td class="text-end">' + formatCurrency(item.revenue || 0) + '</td>' +
                    '<td class="text-end">' +
                        '<a href="' + escapeHtml(item.resume_url || '#') + '" class="btn btn-outline-primary btn-sm me-1" target="_blank" rel="noopener noreferrer">Resume</a>' +
                        '<a href="' + escapeHtml(item.history_url || '#') + '" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer">Riwayat</a>' +
                    '</td>' +
                '</tr>';
            });

            html += '</tbody></table></div>';
            modalBody.html(html);
        },
        error: function(xhr) {
            var message = 'Failed to load visitation details.';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            modalBody.html('<div class="text-center py-4 text-danger">' + escapeHtml(message) + '</div>');
        }
    });
}

$(document).on('click', '.treatment-detail-trigger', function() {
    openTreatmentVisitationDetails($(this).data('treatment-id'), $(this).data('treatment-name'));
});

function initializeCharts(data) {
    // Check if ApexCharts is loaded
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        showErrorState();
        return;
    }

    console.log('ApexCharts version:', ApexCharts.version || 'Unknown');
    console.log('Initializing charts with data:', data);

    // Destroy existing chart instances
    Object.keys(chartInstances).forEach(key => {
        if (chartInstances[key]) {
            try {
                chartInstances[key].destroy();
                console.log('Destroyed chart:', key);
            } catch (e) {
                console.log('Error destroying chart:', key, e);
            }
        }
    });
    chartInstances = {};

    // Monthly Revenue Chart
    if (document.querySelector("#monthlyRevenueChart")) {
        console.log('Creating monthly revenue chart');
        // Clear loading state first
        document.querySelector("#monthlyRevenueChart").innerHTML = '';
        
        var monthlyRevenueOptions = {
            chart: {
                height: 350,
                type: 'area',
                toolbar: { show: true }
            },
            series: [{
                name: 'Revenue',
                data: data.monthlyRevenue ? data.monthlyRevenue.series || [] : []
            }],
            xaxis: {
                categories: data.monthlyRevenue ? data.monthlyRevenue.labels || [] : []
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
        try {
            chartInstances.monthlyRevenue = new ApexCharts(document.querySelector("#monthlyRevenueChart"), monthlyRevenueOptions);
            chartInstances.monthlyRevenue.render();
        } catch (e) {
            console.error('Error rendering monthly revenue chart:', e);
        }
    }

    // Doctor Revenue Chart
    if (document.querySelector("#doctorRevenueChart")) {
        // Clear loading state first
        document.querySelector("#doctorRevenueChart").innerHTML = '';
        
        var doctorRevenueOptions = {
            chart: {
                height: 350,
                type: 'bar'
            },
            series: [{
                name: 'Revenue',
                data: data.doctorRevenue ? data.doctorRevenue.series || [] : []
            }],
            xaxis: {
                categories: data.doctorRevenue ? data.doctorRevenue.labels || [] : [],
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
        try {
            chartInstances.doctorRevenue = new ApexCharts(document.querySelector("#doctorRevenueChart"), doctorRevenueOptions);
            chartInstances.doctorRevenue.render();
        } catch (e) {
            console.error('Error rendering doctor revenue chart:', e);
        }
    }

    // Treatment Revenue Chart
    if (document.querySelector("#treatmentRevenueChart")) {
        // Clear loading state first
        document.querySelector("#treatmentRevenueChart").innerHTML = '';
        
        var treatmentRevenueOptions = {
            chart: {
                height: 350,
                type: 'donut'
            },
            series: data.treatmentRevenue ? data.treatmentRevenue.revenue || [] : [],
            labels: data.treatmentRevenue ? data.treatmentRevenue.labels || [] : [],
            colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6c757d']
        };
        try {
            chartInstances.treatmentRevenue = new ApexCharts(document.querySelector("#treatmentRevenueChart"), treatmentRevenueOptions);
            chartInstances.treatmentRevenue.render();
        } catch (e) {
            console.error('Error rendering treatment revenue chart:', e);
        }
    }

    // Payment Method Chart
    if (document.querySelector("#paymentMethodChart")) {
        // Clear loading state first
        document.querySelector("#paymentMethodChart").innerHTML = '';
        
        var paymentMethodOptions = {
            chart: {
                height: 350,
                type: 'pie'
            },
            series: data.paymentMethodAnalysis ? data.paymentMethodAnalysis.revenue || [] : [],
            labels: data.paymentMethodAnalysis ? data.paymentMethodAnalysis.labels || [] : [],
            colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
        };
        try {
            chartInstances.paymentMethod = new ApexCharts(document.querySelector("#paymentMethodChart"), paymentMethodOptions);
            chartInstances.paymentMethod.render();
        } catch (e) {
            console.error('Error rendering payment method chart:', e);
        }
    }

    // Revenue Growth Chart
    if (document.querySelector("#revenueGrowthChart")) {
        // Clear loading state first
        document.querySelector("#revenueGrowthChart").innerHTML = '';
        
        var currentYear = new Date().getFullYear();
        var revenueGrowthOptions = {
            chart: {
                height: 350,
                type: 'line'
            },
            series: [{
                name: currentYear.toString(),
                data: data.revenueGrowth ? data.revenueGrowth.current_year || [] : []
            }, {
                name: (currentYear - 1).toString(),
                data: data.revenueGrowth ? data.revenueGrowth.previous_year || [] : []
            }],
            xaxis: {
                categories: data.revenueGrowth ? data.revenueGrowth.labels || [] : []
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
        try {
            chartInstances.revenueGrowth = new ApexCharts(document.querySelector("#revenueGrowthChart"), revenueGrowthOptions);
            chartInstances.revenueGrowth.render();
        } catch (e) {
            console.error('Error rendering revenue growth chart:', e);
        }
    }

    // Daily Revenue Chart
    if (document.querySelector("#dailyRevenueChart") && data.dailyRevenue) {
        // Clear loading state first
        document.querySelector("#dailyRevenueChart").innerHTML = '';
        
        var dailyRevenueOptions = {
            chart: {
                height: 300,
                type: 'column'
            },
            series: [{
                name: 'Daily Revenue',
                data: data.dailyRevenue.series || []
            }],
            xaxis: {
                categories: data.dailyRevenue.labels || [],
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
        try {
            chartInstances.dailyRevenue = new ApexCharts(document.querySelector("#dailyRevenueChart"), dailyRevenueOptions);
            chartInstances.dailyRevenue.render();
        } catch (e) {
            console.error('Error rendering daily revenue chart:', e);
        }
    }
}

</script>
@endpush



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
                            <div class="form-group">
                                <input type="text" id="daterange" class="form-control form-control-sm" 
                                       placeholder="Select Date Range" style="width: 250px;">
                            </div>
                            <select id="clinic_filter" class="form-select form-select-sm" style="width: 200px;">
                                <option value="">All Clinics</option>
                                @foreach(\App\Models\ERM\Klinik::all() as $clinic)
                                    <option value="{{ $clinic->id }}">{{ $clinic->nama }}</option>
                                @endforeach
                            </select>
                            <button type="button" id="refresh_data" class="btn btn-primary btn-sm">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading_spinner" class="row">
        <div class="col-12 text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading service analytics data...</p>
        </div>
    </div>

    <!-- Analytics Content -->
    <div id="analytics_content" style="display: none;">
        <!-- Key Metrics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-2">Total Treatments</p>
                                <h4 class="mb-0" id="total_treatments">-</h4>
                            </div>
                            <div class="col-4">
                                <div class="avatar-sm mx-auto">
                                    <span class="avatar-title rounded-circle bg-soft-primary">
                                        <i class="mdi mdi-medical-bag font-size-20"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-2">Total Packages</p>
                                <h4 class="mb-0" id="total_packages">-</h4>
                            </div>
                            <div class="col-4">
                                <div class="avatar-sm mx-auto">
                                    <span class="avatar-title rounded-circle bg-soft-success">
                                        <i class="mdi mdi-package-variant font-size-20"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-2">Avg Satisfaction</p>
                                <h4 class="mb-0" id="avg_satisfaction">-</h4>
                            </div>
                            <div class="col-4">
                                <div class="avatar-sm mx-auto">
                                    <span class="avatar-title rounded-circle bg-soft-warning">
                                        <i class="mdi mdi-star font-size-20"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <p class="text-muted mb-2">Service Efficiency</p>
                                <h4 class="mb-0" id="service_efficiency">-</h4>
                            </div>
                            <div class="col-4">
                                <div class="avatar-sm mx-auto">
                                    <span class="avatar-title rounded-circle bg-soft-info">
                                        <i class="mdi mdi-speedometer font-size-20"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                                <h4 class="card-title mb-0">Popular Treatments</h4>
                                <div>
                                    <button id="viewAllTreatmentsBtn" type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewAllTreatmentsModal">
                                        View All
                                    </button>
                                </div>
                            </div>
                    <div class="card-body">
                        <div id="popular_treatments_chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Doctor Performance</h4>
                    </div>
                    <div class="card-body">
                        <div id="doctor_performance_chart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Visitation Trends</h4>
                    </div>
                    <div class="card-body">
                        <div id="visitation_trends_chart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 3 -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Treatment Efficiency</h4>
                    </div>
                    <div class="card-body">
                        <div id="treatment_efficiency_chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Service Satisfaction Trends</h4>
                    </div>
                    <div class="card-body">
                        <div id="satisfaction_trends_chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- View All Treatments Modal -->
    <div class="modal fade" id="viewAllTreatmentsModal" tabindex="-1" role="dialog" aria-labelledby="viewAllTreatmentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAllTreatmentsModalLabel">All Treatments</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <button id="downloadTreatmentsExcelBtn" type="button" class="btn btn-sm btn-success">Download Excel</button>
                    </div>
                    <div class="table-responsive">
                        <table id="viewAllTreatmentsTable" class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Treatment</th>
                                    <th>Count</th>
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
<!-- SheetJS for client-side Excel export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
$(document).ready(function() {
    console.log('Services analytics page loaded');
    
    // Chart instances for proper cleanup
    let chartInstances = {};

    // Initialize date range picker
    const today = moment();
    const startOfMonth = today.clone().startOf('month');
    
    $('#daterange').daterangepicker({
        startDate: startOfMonth,
        endDate: today,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        },
        locale: {
            format: 'YYYY-MM-DD'
        }
    });

    console.log('Date range picker initialized');

    // Function to destroy all charts
    function destroyAllCharts() {
        Object.values(chartInstances).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        chartInstances = {};
    }

    // Function to load analytics data
    function loadAnalyticsData() {
        console.log('Loading analytics data...');
        
        const dateRange = $('#daterange').val().split(' - ');
        const clinicId = $('#clinic_filter').val();

        console.log('Date range:', dateRange);
        console.log('Clinic ID:', clinicId);

        $('#loading_spinner').show();
        $('#analytics_content').hide();

        // Destroy existing charts before loading new data
        destroyAllCharts();

        $.ajax({
            url: '/marketing/services-analytics-data',
            method: 'GET',
            data: {
                start_date: dateRange[0],
                end_date: dateRange[1],
                clinic_id: clinicId
            },
            success: function(response) {
                console.log('AJAX success:', response);
                
                if (response.success) {
                    updateDashboard(response.data);
                } else {
                    console.error('API returned error:', response.message);
                    alert('Error loading data: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    responseJSON: xhr.responseJSON
                });
                
                // Show detailed error message
                let errorMessage = 'Error loading analytics data.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage += ' Details: ' + xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    errorMessage += ' Response: ' + xhr.responseText.substring(0, 200);
                }
                
                alert(errorMessage);
            },
            complete: function() {
                $('#loading_spinner').hide();
                $('#analytics_content').show();
            }
        });
    }

    // Function to update dashboard with data
    function updateDashboard(data) {
        try {
            console.log('Updating dashboard with data:', data);
            
            // Update metrics cards
            $('#total_treatments').text(data.summary?.total_treatments || '0');
            $('#total_packages').text(data.summary?.total_packages || '0');
            $('#avg_satisfaction').text((data.satisfactionTrends?.satisfaction_score?.slice(-1)[0] || 0).toFixed(1));
            $('#service_efficiency').text((data.treatmentEfficiency?.efficiency_rate || 0).toFixed(1) + '%');

            // Create charts
            createPopularTreatmentsChart(data.popularTreatments);
            createDoctorPerformanceChart(data.doctorPerformance);
            createVisitationTrendsChart(data.visitationTrends);
            createTreatmentEfficiencyChart(data.treatmentEfficiency);
            createSatisfactionTrendsChart(data.satisfactionTrends);

            console.log('Dashboard updated successfully');

        } catch (error) {
            console.error('Error updating dashboard:', error);
            alert('Error displaying data. Please check console for details.');
        }
    }

    // Chart creation functions
    function createPopularTreatmentsChart(data) {
        console.log('Creating popular treatments chart:', data);
        
        if (!data || !data.labels || !data.values) {
            console.warn('No data for popular treatments chart');
            return;
        }

        const options = {
            series: [{
                name: 'Treatments',
                data: data.values
            }],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            xaxis: {
                categories: data.labels
            },
            colors: ['#5156be']
        };

        chartInstances.popularTreatments = new ApexCharts(
            document.querySelector("#popular_treatments_chart"), 
            options
        );
        chartInstances.popularTreatments.render();
    }

    function createTreatmentEfficiencyChart(data) {
        console.log('Creating treatment efficiency chart:', data);
        
        if (!data || !data.labels || !data.frequency) {
            console.warn('No data for treatment efficiency chart');
            return;
        }

        const options = {
            series: [{
                name: 'Frequency',
                data: data.frequency
            }, {
                name: 'Avg Price',
                data: data.avg_price
            }],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: data.labels
            },
            yaxis: [{
                title: {
                    text: 'Frequency'
                }
            }],
            fill: {
                opacity: 1
            },
            colors: ['#5156be', '#2ab57d']
        };

        chartInstances.treatmentEfficiency = new ApexCharts(
            document.querySelector("#treatment_efficiency_chart"), 
            options
        );
        chartInstances.treatmentEfficiency.render();
    }

    function createVisitationTrendsChart(data) {
        console.log('Creating visitation trends chart:', data);
        
        if (!data || !data.labels || !data.values) {
            console.warn('No data for visitation trends chart');
            return;
        }

        const options = {
            series: [{
                name: 'Visits',
                data: data.values
            }],
            chart: {
                type: 'line',
                height: 350
            },
            xaxis: {
                categories: data.labels
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            colors: ['#5156be']
        };

        chartInstances.visitationTrends = new ApexCharts(
            document.querySelector("#visitation_trends_chart"), 
            options
        );
        chartInstances.visitationTrends.render();
    }

    function createDoctorPerformanceChart(data) {
        console.log('Creating doctor performance chart:', data);
        
        if (!data || !data.labels || !data.revenue || !data.patients) {
            console.warn('No data for doctor performance chart');
            return;
        }

        const options = {
            series: [{
                name: 'Revenue',
                type: 'column',
                data: data.revenue
            }, {
                name: 'Patients',
                type: 'line',
                data: data.patients
            }],
            chart: {
                height: 350,
                type: 'line'
            },
            stroke: {
                width: [0, 4]
            },
            xaxis: {
                categories: data.labels
            },
            yaxis: [{
                title: {
                    text: 'Revenue (IDR)'
                }
            }, {
                opposite: true,
                title: {
                    text: 'Patients'
                }
            }],
            colors: ['#5156be', '#2ab57d']
        };

        chartInstances.doctorPerformance = new ApexCharts(
            document.querySelector("#doctor_performance_chart"), 
            options
        );
        chartInstances.doctorPerformance.render();
    }

    function createSatisfactionTrendsChart(data) {
        console.log('Creating satisfaction trends chart:', data);
        
        if (!data || !data.labels || !data.satisfaction_score) {
            console.warn('No data for satisfaction trends chart');
            return;
        }

        const options = {
            series: [{
                name: 'Satisfaction Score',
                data: data.satisfaction_score
            }, {
                name: 'Response Rate (%)',
                data: data.response_rate
            }],
            chart: {
                height: 350,
                type: 'line'
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: data.labels
            },
            yaxis: [{
                title: {
                    text: 'Satisfaction Score'
                },
                min: 0,
                max: 5
            }, {
                opposite: true,
                title: {
                    text: 'Response Rate (%)'
                },
                min: 0,
                max: 100
            }],
            colors: ['#fd7e14', '#2ab57d']
        };

        chartInstances.satisfactionTrends = new ApexCharts(
            document.querySelector("#satisfaction_trends_chart"), 
            options
        );
        chartInstances.satisfactionTrends.render();
    }

    // Event handlers
    $('#refresh_data, #clinic_filter').on('change', function() {
        console.log('Filter changed, reloading data');
        loadAnalyticsData();
    });

    $('#daterange').on('apply.daterangepicker', function() {
        console.log('Date range changed, reloading data');
        loadAnalyticsData();
    });

    // Initial load
    console.log('Starting initial load...');
    loadAnalyticsData();

    // Fetch full list of treatments when modal opened
    let allTreatmentsData = null;
    $('#viewAllTreatmentsBtn').on('click', function() {
        const $tbody = $('#viewAllTreatmentsTable tbody');
        $tbody.empty();

        const dateRange = $('#daterange').val().split(' - ');
        const clinicId = $('#clinic_filter').val();
        const startDate = dateRange[0];
        const endDate = dateRange[1];

        $tbody.append('<tr><td colspan="4" class="text-center text-muted py-4">Loading...</td></tr>');

        $.ajax({
            url: '{{ route("marketing.services.analytics.all") }}',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                clinic_id: clinicId
            },
            success: function(res) {
                $tbody.empty();
                if (res.success && res.data && res.data.length > 0) {
                    allTreatmentsData = res.data;
                    for (let i = 0; i < res.data.length; i++) {
                        const r = res.data[i];
                        const rev = r.total_revenue != null ? parseFloat(r.total_revenue) : 0;
                        const cnt = r.total_count != null ? r.total_count : '';
                        $tbody.append(`<tr>
                            <td>${i+1}</td>
                            <td>${r.treatment_name}</td>
                            <td>${cnt}</td>
                            <td>Rp ${rev ? rev.toLocaleString() : '-'}</td>
                        </tr>`);
                    }
                } else {
                    allTreatmentsData = null;
                    $tbody.append('<tr><td colspan="4" class="text-center text-muted py-4">No data available</td></tr>');
                }
            },
            error: function(xhr) {
                $tbody.empty();
                $tbody.append('<tr><td colspan="4" class="text-center text-danger py-4">Failed to load data</td></tr>');
                console.error('Error fetching treatments', xhr);
            }
        });
    });

    // Download Excel for treatments
    $('#downloadTreatmentsExcelBtn').on('click', function() {
        if (!allTreatmentsData || allTreatmentsData.length === 0) {
            alert('No data to export. Open "View All" first to load data.');
            return;
        }

        const rows = allTreatmentsData.map(function(r, idx) {
            return {
                No: idx + 1,
                Treatment: r.treatment_name || '',
                Count: r.total_count != null ? r.total_count : '',
                Revenue: r.total_revenue != null ? r.total_revenue : ''
            };
        });

        const worksheet = XLSX.utils.json_to_sheet(rows);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Treatments');
        XLSX.writeFile(workbook, `treatments_all_${moment().format('YYYYMMDD')}.xlsx`);
    });
});
</script>
@endsection

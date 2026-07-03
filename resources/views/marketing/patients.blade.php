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

    <!-- Patient Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-primary mb-1 total-patients">0</h4>
                            <p class="text-muted mb-0">Total Patients</p>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-users text-primary" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-left-success" id="newPatientsCard" style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="text-success mb-1 new-patients">0</h4>
                            <p class="text-muted mb-0">New Patients</p>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-user-plus text-success" style="font-size: 24px;"></i>
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
                            <h4 class="text-warning mb-1 retention-rate">0%</h4>
                            <p class="text-muted mb-0">Retention Rate</p>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-redo text-warning" style="font-size: 24px;"></i>
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
                            <h4 class="text-info mb-1 avg-visits">0</h4>
                            <p class="text-muted mb-0">Avg Visits per Patient</p>
                        </div>
                        <div class="ms-auto">
                            <i class="fas fa-chart-line text-info" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demographics Charts -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Age Demographics</h4>
                </div>
                <div class="card-body">
                    <div id="ageChart"></div>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Age Range</th>
                                    <th class="text-end">Patients</th>
                                    <th class="text-end">Revenue</th>
                                    <th>Top Tindakan</th>
                                </tr>
                            </thead>
                            <tbody id="ageStatsBody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Gender Distribution</h4>
                </div>
                <div class="card-body">
                    <div id="genderChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Occupations -->
    <div class="row mt-3">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Top Patient Occupations</h4>
                    <small class="text-muted">Top 10 by visits</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width:6%">No</th>
                                    <th>Pekerjaan</th>
                                    <th class="text-end" style="width:20%">Count</th>
                                </tr>
                            </thead>
                            <tbody id="top-occupations-body">
                                <tr><td colspan="3" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Geographic & Loyalty Analysis -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Geographic Distribution</h4>
                </div>
                <div class="card-body">
                    <div id="geographicChart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Patient Loyalty Analysis</h4>
                </div>
                <div class="card-body">
                    <div id="loyaltyChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Growth Trends -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Patient Growth Trends</h4>
                </div>
                <div class="card-body">
                    <div id="growthChart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Retention Analysis</h4>
                </div>
                <div class="card-body">
                    <div id="retentionChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Detail Tables -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Patient Statistics by Location</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Total Patients</th>
                                    <th>New Patients</th>
                                    <th>Return Patients</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody id="locationStatsTable">
                                <tr>
                                    <td colspan="5" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Retention Detail Analysis -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Detailed Retention Analysis</h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="text-primary total-patients-detail">0</h3>
                                <p class="mb-0">Total Patients</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="text-success returning-patients">0</h3>
                                <p class="mb-0">Returning Patients</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="text-warning one-time-patients">0</h3>
                                <p class="mb-0">One-time Patients</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded">
                                <h3 class="text-info retention-rate-detail">0%</h3>
                                <p class="mb-0">Retention Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Patients Modal -->
    <div class="modal fade" id="newPatientsModal" tabindex="-1" role="dialog" aria-labelledby="newPatientsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newPatientsModalLabel">New Patients</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="newPatientsTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>DOB</th>
                                    <th>Gender</th>
                                    <th>Address</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(255,255,255,0.8); z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    function formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number(value) || 0);
    }

    function formatCompactCurrency(value) {
        const amount = Number(value) || 0;

        if (Math.abs(amount) >= 1000000000) {
            return 'Rp ' + (amount / 1000000000).toFixed(1) + 'B';
        }

        if (Math.abs(amount) >= 1000000) {
            return 'Rp ' + (amount / 1000000).toFixed(1) + 'M';
        }

        if (Math.abs(amount) >= 1000) {
            return 'Rp ' + (amount / 1000).toFixed(1) + 'K';
        }

        return formatCurrency(amount);
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    // Check if ApexCharts is loaded
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts is not loaded');
        return;
    }
    
    console.log('ApexCharts version:', ApexCharts.version || 'Unknown');
    // Initialize Select2 for clinic filter
    $('#clinicFilter').select2({
        placeholder: 'Select a clinic',
        allowClear: true,
        width: 'resolve'
    });

    // Initialize date range picker
    $('#daterange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment().endOf('month')],
            'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        }
    });

    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        loadAnalyticsData();
    });

    $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        loadAnalyticsData();
    });

    // Clear date range
    $('#clearDateRange').on('click', function() {
        $('#daterange').val('');
        loadAnalyticsData();
    });

    // Clinic filter change
    $('#clinicFilter').on('change', function() {
        loadAnalyticsData();
    });

    // Chart containers
    let chartInstances = {
        age: null,
        gender: null,
        geographic: null,
        loyalty: null,
        growth: null,
        retention: null
    };

    // Destroy all charts
    function destroyAllCharts() {
        Object.keys(chartInstances).forEach(key => {
            if (chartInstances[key]) {
                try {
                    chartInstances[key].destroy();
                    chartInstances[key] = null;
                } catch (e) {
                    console.warn(`Error destroying chart ${key}:`, e);
                }
            }
        });
    }

    // Load clinic data
    function loadClinics() {
        $.get('/marketing/clinics')
            .done(function(response) {
                console.log('Clinics response:', response); // Debug log
                
                $('#clinicFilter').empty().append('<option value="">All Clinics</option>');
                
                if (response.success && response.data && Array.isArray(response.data)) {
                    response.data.forEach(function(clinic) {
                        $('#clinicFilter').append(`<option value="${clinic.id}">${clinic.nama}</option>`);
                    });
                } else {
                    console.error('Invalid clinics response format:', response);
                }
            })
            .fail(function(xhr) {
                console.error('Failed to load clinics:', xhr.responseText);
            });
    }

    // Load analytics data
    function loadAnalyticsData() {
        $('#loadingOverlay').removeClass('d-none');

        const dateRange = $('#daterange').val();
        const clinicId = $('#clinicFilter').val();

        let params = {};
        if (dateRange) {
            const dates = dateRange.split(' - ');
            params.start_date = dates[0];
            params.end_date = dates[1];
        }
        if (clinicId) {
            params.clinic_id = clinicId;
        }

        $.get('/marketing/analytics/patients-data', params)
            .done(function(response) {
                console.log('Response:', response); // Debug log
                if (response.success && response.data) {
                    updateSummaryCards(response.data);
                    updateCharts(response.data);
                    updateTables(response.data);
                } else {
                    console.error('Invalid response format:', response);
                    alert('Invalid response format received.');
                }
            })
            .fail(function(xhr) {
                console.error('Failed to load analytics data:', xhr);
                console.error('Response Text:', xhr.responseText);
                console.error('Status:', xhr.status);
                alert('Failed to load analytics data. Check console for details.');
            })
            .always(function() {
                $('#loadingOverlay').addClass('d-none');
            });
    }

    // Update summary cards
    function updateSummaryCards(data) {
        console.log('Updating summary cards with data:', data); // Debug log
        
        // Safe access with fallbacks
        const totalPatients = data.ageDemographics && data.ageDemographics.series ? 
            data.ageDemographics.series.reduce((a, b) => a + b, 0) : 0;
        const newPatients = data.patientLoyalty && data.patientLoyalty.series ? 
            data.patientLoyalty.series.reduce((a, b) => a + b, 0) : 0;
        const retentionRate = data.retentionAnalysis && data.retentionAnalysis.retention_rate ? 
            data.retentionAnalysis.retention_rate : 0;
        const avgVisits = data.retentionAnalysis && data.retentionAnalysis.avg_visits_per_patient ? 
            Math.round(data.retentionAnalysis.avg_visits_per_patient * 10) / 10 : 0;
            
        $('.total-patients').text(totalPatients);
        $('.new-patients').text(newPatients);
        $('.retention-rate').text(retentionRate + '%');
        $('.avg-visits').text(avgVisits);
    }

    // Update charts
    function updateCharts(data) {
        console.log('Updating charts with data:', data); // Debug log
        
        // Destroy existing charts first
        destroyAllCharts();
        
        // Add small delay to ensure DOM is ready
        setTimeout(() => {
            try {
                // Age Demographics Chart
                if (data.ageDemographics && data.ageDemographics.series && data.ageDemographics.labels) {
                    const ageOptions = {
                        chart: { 
                            type: 'line', 
                            height: 350,
                            id: 'ageChart'
                        },
                        series: [
                            { name: 'Patients', type: 'column', data: data.ageDemographics.series },
                            { name: 'Revenue', type: 'line', data: data.ageDemographics.revenueSeries || [] }
                        ],
                        xaxis: { categories: data.ageDemographics.labels },
                        yaxis: [
                            {
                                title: { text: 'Patients' }
                            },
                            {
                                opposite: true,
                                title: { text: 'Revenue' },
                                labels: {
                                    formatter: function(value) {
                                        return formatCompactCurrency(value);
                                    }
                                }
                            }
                        ],
                        stroke: {
                            width: [0, 3],
                            curve: 'smooth'
                        },
                        dataLabels: {
                            enabled: false
                        },
                        colors: ['#007bff', '#28a745'],
                        tooltip: {
                            shared: true,
                            y: {
                                formatter: function(value, context) {
                                    return context.seriesIndex === 1 ? formatCurrency(value) : value;
                                }
                            }
                        },
                        title: { text: 'Age Distribution' }
                    };
                    chartInstances.age = new ApexCharts(document.querySelector("#ageChart"), ageOptions);
                    chartInstances.age.render();
                }

                // Gender Distribution Chart
                if (data.genderDemographics && data.genderDemographics.series && data.genderDemographics.labels) {
                    const genderOptions = {
                        chart: { 
                            type: 'pie', 
                            height: 350,
                            id: 'genderChart'
                        },
                        series: data.genderDemographics.series,
                        labels: data.genderDemographics.labels,
                        colors: ['#007bff', '#28a745', '#ffc107']
                    };
                    chartInstances.gender = new ApexCharts(document.querySelector("#genderChart"), genderOptions);
                    chartInstances.gender.render();
                }

                // Geographic Distribution Chart
                if (data.geographicDistribution && data.geographicDistribution.series && data.geographicDistribution.labels) {
                    const geographicOptions = {
                        chart: { 
                            type: 'bar', 
                            height: 350,
                            id: 'geographicChart'
                        },
                        series: [{ name: 'Patients', data: data.geographicDistribution.series }],
                        xaxis: {
                            categories: data.geographicDistribution.labels,
                            labels: { rotate: -45 }
                        },
                        colors: ['#17a2b8']
                    };
                    chartInstances.geographic = new ApexCharts(document.querySelector("#geographicChart"), geographicOptions);
                    chartInstances.geographic.render();
                }

                // Patient Loyalty Chart
                if (data.patientLoyalty && data.patientLoyalty.series && data.patientLoyalty.labels) {
                    const loyaltyOptions = {
                        chart: { 
                            type: 'bar', 
                            height: 350,
                            id: 'loyaltyChart'
                        },
                        series: [{ name: 'Visits', data: data.patientLoyalty.series }],
                        xaxis: {
                            categories: data.patientLoyalty.labels,
                            labels: { rotate: -45 }
                        },
                        colors: ['#28a745']
                    };
                    chartInstances.loyalty = new ApexCharts(document.querySelector("#loyaltyChart"), loyaltyOptions);
                    chartInstances.loyalty.render();
                }

                // Growth Trends Chart
                if (data.growthTrends && data.growthTrends.series && data.growthTrends.labels) {
                    const growthOptions = {
                        chart: { 
                            type: 'line', 
                            height: 350,
                            id: 'growthChart'
                        },
                        series: [{ name: 'New Patients', data: data.growthTrends.series }],
                        xaxis: { categories: data.growthTrends.labels },
                        colors: ['#ffc107'],
                        stroke: { curve: 'smooth' }
                    };
                    chartInstances.growth = new ApexCharts(document.querySelector("#growthChart"), growthOptions);
                    chartInstances.growth.render();
                }

                // Retention Analysis Chart
                if (data.retentionAnalysis && 
                    typeof data.retentionAnalysis.returning_patients !== 'undefined' && 
                    typeof data.retentionAnalysis.one_time_patients !== 'undefined') {
                    const retentionOptions = {
                        chart: { 
                            type: 'donut', 
                            height: 350,
                            id: 'retentionChart'
                        },
                        series: [data.retentionAnalysis.returning_patients, data.retentionAnalysis.one_time_patients],
                        labels: ['Returning Patients', 'One-time Patients'],
                        colors: ['#28a745', '#dc3545']
                    };
                    chartInstances.retention = new ApexCharts(document.querySelector("#retentionChart"), retentionOptions);
                    chartInstances.retention.render();
                }
            } catch (error) {
                console.error('Error rendering charts:', error);
            }
        }, 100);
    }

    // Update tables
    function updateTables(data) {
        console.log('Updating tables with data:', data); // Debug log

        let ageStatsHtml = '';
        if (data.ageDemographics && Array.isArray(data.ageDemographics.breakdown) && data.ageDemographics.breakdown.length) {
            data.ageDemographics.breakdown.forEach(function(item) {
                const topTreatments = Array.isArray(item.top_treatments) && item.top_treatments.length
                    ? item.top_treatments.map(function(treatment) {
                        return `<div class="age-treatment-item"><strong>${escapeHtml(treatment.name)}</strong> <span class="text-muted">(${treatment.count}x, ${formatCurrency(treatment.revenue)})</span></div>`;
                    }).join('')
                    : '<span class="text-muted">No tindakan</span>';

                ageStatsHtml += `
                    <tr>
                        <td>${escapeHtml(item.label)}</td>
                        <td class="text-end">${item.patient_count || 0}</td>
                        <td class="text-end">${formatCurrency(item.revenue)}</td>
                        <td>${topTreatments}</td>
                    </tr>
                `;
            });
        } else {
            ageStatsHtml = '<tr><td colspan="4" class="text-center text-muted">No data available</td></tr>';
        }
        $('#ageStatsBody').html(ageStatsHtml);
        
        // Location stats table
        let locationHtml = '';
        if (data.addressStats && typeof data.addressStats === 'object') {
            const totalPatients = Object.values(data.addressStats).reduce((sum, stats) => {
                return sum + (stats.count || 0);
            }, 0);
            
            Object.entries(data.addressStats).forEach(([area, stats]) => {
                const percentage = totalPatients > 0 ? ((stats.count / totalPatients) * 100).toFixed(1) : 0;
                locationHtml += `
                    <tr>
                        <td>${area}</td>
                        <td>${stats.count || 0}</td>
                        <td>${stats.new || 0}</td>
                        <td>${stats.returning || 0}</td>
                        <td>${percentage}%</td>
                    </tr>
                `;
            });
        } else {
            locationHtml = '<tr><td colspan="5" class="text-center">No data available</td></tr>';
        }
        $('#locationStatsTable').html(locationHtml);

        // Top occupations
        let occHtml = '';
        if (data.topPatientPekerjaan && Array.isArray(data.topPatientPekerjaan) && data.topPatientPekerjaan.length) {
            data.topPatientPekerjaan.forEach(function(item, idx) {
                occHtml += `<tr>
                    <td>${idx+1}</td>
                    <td>${item.pekerjaan || 'Unknown'}</td>
                    <td class="text-end">${item.count || 0}</td>
                </tr>`;
            });
        } else {
            occHtml = '<tr><td colspan="3" class="text-center text-muted">No data available</td></tr>';
        }
        $('#top-occupations-body').html(occHtml);

        // Retention detail analysis
        if (data.retentionAnalysis) {
            $('.total-patients-detail').text(data.retentionAnalysis.total_patients || 0);
            $('.returning-patients').text(data.retentionAnalysis.returning_patients || 0);
            $('.one-time-patients').text(data.retentionAnalysis.one_time_patients || 0);
            $('.retention-rate-detail').text((data.retentionAnalysis.retention_rate || 0) + '%');
        }
    }

    // Initialize page
    loadClinics();
    loadAnalyticsData();

    // New Patients modal & DataTable
    let newPatientsTable = null;

    function initNewPatientsTable() {
        if (newPatientsTable) return;

        newPatientsTable = $('#newPatientsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/marketing/patients/new-list',
                data: function(d) {
                    const dateRange = $('#daterange').val();
                    const clinicId = $('#clinicFilter').val();
                    if (dateRange) {
                        const dates = dateRange.split(' - ');
                        d.start_date = dates[0];
                        d.end_date = dates[1];
                    }
                    if (clinicId) d.clinic_id = clinicId;
                },
                dataSrc: function(json) {
                    if (!json || !json.success) return [];
                    return json.data || [];
                }
            },
            columns: [
                { data: 'nama', title: 'Name' },
                { data: 'no_hp', title: 'Phone', defaultContent: '-' },
                { data: 'tanggal_lahir', title: 'DOB', render: function(data){ return data ? data : '-'; } },
                { data: 'gender', title: 'Gender' },
                { data: 'alamat', title: 'Address', defaultContent: '-' },
                { data: 'created_at', title: 'Created At', render: function(data){ return data ? moment(data).format('YYYY-MM-DD HH:mm') : '-'; } }
            ],
            order: [[5, 'desc']],
            pageLength: 25,
            lengthChange: false
        });
    }

    $('#newPatientsCard').on('click', function(e) {
        e.preventDefault();
        // initialize table if needed then show modal
        initNewPatientsTable();
        // reload with current filters
        if (newPatientsTable) {
            newPatientsTable.ajax.reload();
        }
        $('#newPatientsModal').modal('show');
    });
});
</script>

<style>
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
    }
    .table th {
        background-color: #f8f9fc;
        border-color: #e3e6f0;
    }
    .select2-container--default .select2-selection--single {
        height: 31px;
        border: 1px solid #d1d3e2;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 29px;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 29px;
    }
    .age-treatment-item + .age-treatment-item {
        margin-top: 0.25rem;
    }
</style>
@endsection

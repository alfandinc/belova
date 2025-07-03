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
                        <h4 class="page-title">Patient Analytics</h4>
                    </div><!--end col-->
                    <div class="col-auto">
                        <form class="form-inline" method="GET">
                            <div class="form-group mr-2">
                                <select class="form-control" name="year" onchange="this.form.submit()">
                                    @for($i = date('Y'); $i >= date('Y')-5; $i--)
                                        <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group">
                                <select class="form-control" name="clinic_id" onchange="this.form.submit()">
                                    <option value="">All Clinics</option>
                                    @foreach($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" {{ $clinicId == $clinic->id ? 'selected' : '' }}>
                                            {{ $clinic->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Demographics Charts -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Patient Age Distribution</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="ageDistributionChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Gender Distribution</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="genderDistributionChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->
    
    <!-- Address Distribution Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Distribusi Pasien Berdasarkan Wilayah</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div id="area-chart" class="apex-charts"></div>
                        </div>
                        <div class="col-lg-4">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Wilayah</th>
                                            <th>Jumlah</th>
                                            <th>Persentase</th>
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
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Patient Loyalty & Geographic Distribution Charts -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Most Loyal Patients (Visit Frequency)</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="patientLoyaltyChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Geographic Distribution</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="geographicDistributionChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

</div><!-- container -->
@endsection

@section('scripts')
<!-- ApexCharts js -->

<script>
    $(document).ready(function() {
        // Age Distribution Chart
        var ageDistributionOptions = {
            chart: {
                height: 350,
                type: 'pie',
            },
            labels: @json($ageDemographics['labels']),
            series: @json($ageDemographics['series']),
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
            title: {
                text: 'Patient Age Distribution',
                align: 'center',
            }
        };
        
        var ageDistributionChart = new ApexCharts(
            document.querySelector("#ageDistributionChart"),
            ageDistributionOptions
        );
        ageDistributionChart.render();

        // Gender Distribution Chart
        var genderDistributionOptions = {
            chart: {
                height: 350,
                type: 'donut',
            },
            labels: @json($genderDemographics['labels']),
            series: @json($genderDemographics['series']),
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            colors: ['#4e73df', '#e74a3b', '#36b9cc'],
            title: {
                text: 'Gender Distribution',
                align: 'center',
            }
        };
        
        var genderDistributionChart = new ApexCharts(
            document.querySelector("#genderDistributionChart"),
            genderDistributionOptions
        );
        genderDistributionChart.render();
        
        // Patient Loyalty Chart
        var patientLoyaltyOptions = {
            chart: {
                height: 350,
                type: 'bar',
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                }
            },
            dataLabels: {
                enabled: true
            },
            series: [{
                name: 'Visits',
                data: @json($patientLoyalty['series'])
            }],
            xaxis: {
                categories: @json($patientLoyalty['labels']),
            },
            colors: ['#1aae6f'],
            title: {
                text: 'Most Loyal Patients (Visit Frequency)',
                align: 'center',
            }
        };
        
        var patientLoyaltyChart = new ApexCharts(
            document.querySelector("#patientLoyaltyChart"),
            patientLoyaltyOptions
        );
        patientLoyaltyChart.render();
        
        // Geographic Distribution Chart
        var geographicDistributionOptions = {
            chart: {
                height: 350,
                type: 'bar',
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            series: [{
                name: 'Patients',
                data: @json($geographicDistribution['series'])
            }],
            xaxis: {
                categories: @json($geographicDistribution['labels']),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            fill: {
                opacity: 1
            },
            colors: ['#fd7e14'],
            title: {
                text: 'Geographic Distribution',
                align: 'center',
            }
        };
        
        var geographicDistributionChart = new ApexCharts(
            document.querySelector("#geographicDistributionChart"),
            geographicDistributionOptions
        );
        geographicDistributionChart.render();
        
        // Address Distribution Chart
        var areaChartOptions = {
            series: [
                @foreach($addressStats as $area => $stats)
                {{ $stats['percentage'] }},
                @endforeach
            ],
            chart: {
                type: 'donut',
                height: 380
            },
            labels: [
                @foreach($addressStats as $area => $stats)
                '{{ $area }}',
                @endforeach
            ],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            },
            legend: {
                position: 'bottom'
            },
            colors: ['#3b5998', '#55acee', '#0077b5', '#007bb5', '#00a0d1', '#3aaa35', '#c32aa3', '#bd081c', '#ea4c89'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 280
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var areaChart = new ApexCharts(document.querySelector("#area-chart"), areaChartOptions);
        areaChart.render();
    });
</script>
@endsection
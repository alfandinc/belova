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
                        <h4 class="page-title">Services Analytics</h4>
                    </div><!--end col-->
                    <div class="col-auto">
                        <form class="form-inline" method="GET">
                            <div class="form-group mr-2">
                                <select class="form-control" name="period" onchange="this.form.submit()">
                                    <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Last Month</option>
                                    <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>Last Quarter</option>
                                    <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Last Year</option>
                                </select>
                            </div>
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

    <!-- Popular Treatments & Treatment Packages Charts -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Popular Treatments</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="popularTreatmentsChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Treatment Package Performance</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="packagePerformanceChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Visitation Trends Chart -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Monthly Visitation Trends for {{ $year }}</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="visitationTrendsChart" class="apex-charts"></div>
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
        // Popular Treatments Chart
        var popularTreatmentsOptions = {
            chart: {
                height: 350,
                type: 'bar',
                stacked: false
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
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
            series: [{
                name: 'Count',
                data: @json($popularTreatments['count'])
            }, {
                name: 'Revenue',
                data: @json($popularTreatments['revenue'])
            }],
            xaxis: {
                categories: @json($popularTreatments['labels']),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: 'Count'
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Revenue'
                    },
                    labels: {
                        formatter: function (y) {
                            return "Rp " + y.toLocaleString();
                        }
                    }
                }
            ],
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val, { seriesIndex }) {
                        return seriesIndex === 0 ? val : "Rp " + val.toLocaleString();
                    }
                }
            },
            colors: ['#4e73df', '#1cc88a'],
            title: {
                text: 'Popular Treatments',
                align: 'center',
            }
        };
        
        var popularTreatmentsChart = new ApexCharts(
            document.querySelector("#popularTreatmentsChart"),
            popularTreatmentsOptions
        );
        popularTreatmentsChart.render();

        // Package Performance Chart
        var packagePerformanceOptions = {
            chart: {
                height: 350,
                type: 'bar',
                stacked: false
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
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
            series: [{
                name: 'Count',
                data: @json($packagePerformance['count'])
            }, {
                name: 'Revenue',
                data: @json($packagePerformance['revenue'])
            }],
            xaxis: {
                categories: @json($packagePerformance['labels']),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: 'Count'
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Revenue'
                    },
                    labels: {
                        formatter: function (y) {
                            return "Rp " + y.toLocaleString();
                        }
                    }
                }
            ],
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val, { seriesIndex }) {
                        return seriesIndex === 0 ? val : "Rp " + val.toLocaleString();
                    }
                }
            },
            colors: ['#fd7e14', '#e74a3b'],
            title: {
                text: 'Treatment Package Performance',
                align: 'center',
            }
        };
        
        var packagePerformanceChart = new ApexCharts(
            document.querySelector("#packagePerformanceChart"),
            packagePerformanceOptions
        );
        packagePerformanceChart.render();
        
        // Visitation Trends Chart
        var visitationTrendsOptions = {
            chart: {
                height: 350,
                type: 'line',
                zoom: {
                    enabled: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'straight',
                width: 3
            },
            series: [{
                name: 'Visitations',
                data: @json($visitationTrends['series'])
            }],
            title: {
                text: 'Monthly Visitations for {{ $year }}',
                align: 'center'
            },
            grid: {
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            xaxis: {
                categories: @json($visitationTrends['labels']),
            },
            colors: ['#4e73df'],
            markers: {
                size: 5
            }
        };
        
        var visitationTrendsChart = new ApexCharts(
            document.querySelector("#visitationTrendsChart"),
            visitationTrendsOptions
        );
        visitationTrendsChart.render();
    });
</script>
@endsection
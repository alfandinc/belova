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
                        <h4 class="page-title">Product Analytics</h4>
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

    <!-- Best Selling Products Chart -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Best Selling Products</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="bestSellingProductsChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Medication Trends Chart -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Monthly Medication Sales for {{ $year }}</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="medicationTrendsChart" class="apex-charts"></div>
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
        // Best Selling Products Chart
        var bestSellingProductsOptions = {
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
                name: 'Quantity',
                data: @json($bestSellingProducts['quantity'])
            }, {
                name: 'Revenue',
                data: @json($bestSellingProducts['revenue'])
            }],
            xaxis: {
                categories: @json($bestSellingProducts['labels']),
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
                        text: 'Quantity'
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
                text: 'Best Selling Products',
                align: 'center',
            }
        };
        
        var bestSellingProductsChart = new ApexCharts(
            document.querySelector("#bestSellingProductsChart"),
            bestSellingProductsOptions
        );
        bestSellingProductsChart.render();

        // Medication Trends Chart
        var medicationTrendsOptions = {
            chart: {
                height: 350,
                type: 'area',
                zoom: {
                    enabled: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            series: [{
                name: 'Quantity Sold',
                data: @json($medicationTrends['series'])
            }],
            title: {
                text: 'Monthly Medication Sales for {{ $year }}',
                align: 'center'
            },
            grid: {
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            xaxis: {
                categories: @json($medicationTrends['labels']),
            },
            colors: ['#e83e8c'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.9,
                    stops: [0, 90, 100]
                }
            }
        };
        
        var medicationTrendsChart = new ApexCharts(
            document.querySelector("#medicationTrendsChart"),
            medicationTrendsOptions
        );
        medicationTrendsChart.render();
    });
</script>
@endsection
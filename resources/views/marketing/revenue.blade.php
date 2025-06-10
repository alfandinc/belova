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
                        <h4 class="page-title">Revenue Analytics</h4>
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

    <!-- Monthly Revenue Chart -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Monthly Revenue for {{ $year }} {{ $clinicId ? '- ' . ($clinics->find($clinicId)->nama ?? 'Unknown Clinic') : '' }}</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="monthlyRevenueChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

    <!-- Revenue by Doctor & Most Profitable Patients Charts -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Revenue by Doctor</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="doctorRevenueChart" class="apex-charts"></div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Most Profitable Patients</h4>
                </div><!--end card-header-->
                <div class="card-body">
                    <div id="profitablePatientsChart" class="apex-charts"></div>
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
        // Monthly Revenue Chart
        var monthlyRevenueOptions = {
            chart: {
                height: 350,
                type: 'bar',
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top'
                    },
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return "Rp " + val.toLocaleString();
                },
                offsetY: -20
            },
            series: [{
                name: 'Revenue',
                data: @json($monthlyRevenue['series'])
            }],
            xaxis: {
                categories: @json($monthlyRevenue['labels']),
                position: 'bottom'
            },
            yaxis: {
                labels: {
                    formatter: function (y) {
                        return "Rp " + y.toLocaleString();
                    }
                }
            },
            colors: ['#2a77f4'],
            title: {
                text: 'Monthly Revenue {{ $year }}',
                align: 'center',
            }
        };
        
        var monthlyRevenueChart = new ApexCharts(
            document.querySelector("#monthlyRevenueChart"),
            monthlyRevenueOptions
        );
        monthlyRevenueChart.render();

        // Doctor Revenue Chart
        var doctorRevenueOptions = {
            chart: {
                height: 350,
                type: 'bar',
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return "Rp " + val.toLocaleString();
                }
            },
            series: [{
                name: 'Revenue',
                data: @json($doctorRevenue['series'])
            }],
            xaxis: {
                categories: @json($doctorRevenue['labels']),
                labels: {
                    formatter: function (val) {
                        return "Rp " + val.toLocaleString();
                    }
                }
            },
            colors: ['#1aae6f'],
            title: {
                text: 'Doctor Revenue {{ $year }}',
                align: 'center',
            }
        };
        
        var doctorRevenueChart = new ApexCharts(
            document.querySelector("#doctorRevenueChart"),
            doctorRevenueOptions
        );
        doctorRevenueChart.render();
        
        // Profitable Patients Chart
        var profitablePatientsOptions = {
            chart: {
                height: 350,
                type: 'bar',
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return "Rp " + val.toLocaleString();
                }
            },
            series: [{
                name: 'Total Spent',
                data: @json($topPatients['spending'])
            }],
            xaxis: {
                categories: @json($topPatients['labels']),
                labels: {
                    formatter: function (val) {
                        return "Rp " + val.toLocaleString();
                    }
                }
            },
            colors: ['#e83e8c'],
            title: {
                text: 'Most Profitable Patients {{ $year }}',
                align: 'center',
            }
        };
        
        var profitablePatientsChart = new ApexCharts(
            document.querySelector("#profitablePatientsChart"),
            profitablePatientsOptions
        );
        profitablePatientsChart.render();
    });
</script>
@endsection
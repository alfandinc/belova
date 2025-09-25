@extends('layouts.bcl.app')
@section('title', 'BCL | Home')
@section('navbar')
    @include('layouts.bcl.navbar')
@endsection


@section('content')
<?php
function convert($sum)
{
    $years = floor($sum / 365);
    $months = floor(($sum - ($years * 365)) / 30.5);
    $days = round($sum - ($years * 365) - ($months * 30.5));
    // echo "Days received: " . $sum . " days <br />";
    if ($years == 0) $years = "";
    else $years = $years . " Tahun, ";
    if ($months == 0) $months = "";
    else $months = $months . " Bulan, ";
    if ($days == 0) $days = "0 Hari";
    else $days = $days . " Hari";
    return $years . $months  . $days;
}
?>
<!-- Page-Title -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Dashboard</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    <a href="#" class="btn btn-sm btn-outline-primary" id="Dash_Date">
                        <span class="day-name" id="Day_Name">Today:</span>&nbsp;
                        <span class="" id="Select_date">Jan 11</span>
                        <i data-feather="calendar" class="align-self-center icon-xs ml-1"></i>
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-primary">
                        <i data-feather="download" class="align-self-center icon-xs"></i>
                    </a>
                </div><!--end col-->
            </div><!--end row-->
        </div>
        <div class="row">
            <div class="col-lg-9">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="card report-card">
                            <a href="{{route('bcl.rooms')}}">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Kamar Terisi</p>
                                            <h3 class="m-0">{{$response->rooms->used.' dari '.$response->rooms->total}}</h3>
                                            <p class="mb-0 text-truncate text-muted"> Kamar Terisi</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="users" class="align-self-center text-muted icon-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body-->
                            </a>
                        </div><!--end card-->
                    </div> <!--end col-->
                    <div class="col-md-6 col-lg-4">
                        <div class="card report-card">
                            <a href="{{route('bcl.income.index')}}">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Transaksi Belum Lunas</p>
                                            <h3 class="m-0">{{$response->belum_lunas}}</h3>
                                            <p class="mb-0 text-truncate text-muted"> Sewa Kamar Belum lunas</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="clock" class="align-self-center text-muted icon-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body-->
                            </a>
                        </div><!--end card-->
                    </div> <!--end col-->
                    <div class="col-md-6 col-lg-4">
                        <div class="card report-card">
                            <a href="{{route('bcl.inventories.index')}}">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Perawaran Inventaris</p>
                                            <h3 class="m-0">{{$response->needed_maintanance}}</h3>
                                            <p class="mb-0 text-truncate text-muted">Dibutuhkan Perawatan segera</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="activity" class="align-self-center text-muted icon-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body-->
                            </a>
                        </div><!--end card-->
                    </div> <!--end col-->
                </div><!--end row-->
                <div class="card">

                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Room Stat. by Revenue <?= date('Y') ?> (Top 10)</h4>
                            </div><!--end col-->
                        </div> <!--end row-->
                    </div><!--end card-header-->
                    <div class="card-body">
                        <div id="barchart" class="apex-charts ml-n4"></div>
                    </div><!--end card-body-->

                </div><!--end card-->
            </div><!--end col-->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Sessions Device</h4>
                            </div><!--end col-->
                            <div class="col-auto">
                                <div class="dropdown">
                                    <a href="#" class="btn btn-sm btn-outline-light dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        All<i class="las la-angle-down ml-1"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="#">Purchases</a>
                                        <a class="dropdown-item" href="#">Emails</a>
                                    </div>
                                </div>
                            </div><!--end col-->
                        </div> <!--end row-->
                    </div><!--end card-header-->
                    <div class="card-body">
                        <div class="text-center">
                            <div id="ana_device" class="apex-charts"></div>
                            <h6 class="bg-light-alt py-3 px-2 mb-0">
                                <i data-feather="calendar" class="align-self-center icon-xs mr-1"></i>
                                TOP 5 Penyewa Terlama
                            </h6>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table border-dashed mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th class="text-right">Total Lama Sewa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($response->ranking_penyewa->original->take(5) as $data)
                                    <tr>
                                        <td class="text-truncated">{{$data->renter->nama}}</td>
                                        <td class="text-right">{{convert($data->total_lama_sewa)}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table><!--end /table-->
                        </div><!--end /div-->
                    </div><!--end card-body-->
                </div><!--end card-->
            </div> <!--end col-->
        </div><!--end row-->

        <!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->
@section('pagescript')
<script>
    var rooms = {!!json_encode($response -> room_stat -> room_name) !!};
    var total_value = {!!json_encode($response -> room_stat -> total_value) !!};
</script>
<script src="{{ URL::asset('plugins/apex-charts/apexcharts.min.js') }}"></script>
<script src="{{ URL::asset('assets/js/jquery.analytics_dashboard.init.js') }}"></script>

@endsection
@endsection
@extends('layouts.erm.app')
@section('title')
    @php
        $klinikId = auth()->user()->dokter->klinik_id ?? null; // Assuming 'dokter' is the relationship
        echo $klinikId == 1 ? ' ERM Premiere Belova' : ($klinikId == 2 ? ' ERM Belova Skin' : 'ERM Belova');
    @endphp
@endsection 
@section('navbar')
    @include('layouts.erm.navbar')
@endsection        
@section('content')
            <!-- Page Content-->           
                <div class="container-fluid">
                    <!-- Page-Title -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                <div class="row">
                                    <div class="col">
                                        {{-- <h4 class="page-title">Selamat Datang, {{ auth()->user()->name }}! You are logged in as <strong>{{ auth()->user()->getRoleNames()->first() }}</strong>.</h4> --}}
                                        <h4>Selamat Datang di ERM <strong>{{ auth()->user()->getRoleNames()->first() }}</strong>, {{ auth()->user()->name }}!</h4>
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                                            <li class="breadcrumb-item active">Dashboard</li>
                                        </ol>
                                    </div><!--end col-->
                                    <div class="col-auto align-self-center">
                                        <a href="#" class="btn btn-sm btn-outline-primary" id="Dash_Date">
                                            <span class="ay-name" id="Day_Name">Today:</span>&nbsp;
                                            <span class="" id="Select_date">Jan 11</span>
                                            <i data-feather="calendar" class="align-self-center icon-xs ml-1"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i data-feather="download" class="align-self-center icon-xs"></i>
                                        </a>
                                    </div><!--end col-->  
                                </div><!--end row-->                                                              
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div><!--end row-->
                    <!-- end page title end breadcrumb -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row justify-content-center">
                                
                              
                            </div><!--end row-->
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">                      
                                            <h4 class="card-title">Grafik Kedatangan Pasien</h4>                      
                                        </div><!--end col-->
                                        <div class="col-auto"> 
                                            <div class="dropdown">
                                                <a href="#" class="btn btn-sm btn-outline-light dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                   This Year<i class="las la-angle-down ml-1"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="#">Today</a>
                                                    <a class="dropdown-item" href="#">Last Week</a>
                                                    <a class="dropdown-item" href="#">Last Month</a>
                                                    <a class="dropdown-item" href="#">This Year</a>
                                                </div>
                                            </div>               
                                        </div><!--end col-->
                                    </div>  <!--end row-->                                  
                                </div><!--end card-header-->
                                <div class="card-body">
                                    <div class="">
                                        <div id="ana_dash_1" class="apex-charts"></div>
                                    </div> 
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div><!--end col-->
                        
                        {{-- <div class="col-lg-3">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">                      
                                            <h4 class="card-title">Mapping Pasien</h4>                      
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
                                    </div>  <!--end row-->                                  
                                </div><!--end card-header-->
                                <div class="card-body">
                                    <div class="text-center">
                                        <div id="ana_device" class="apex-charts"></div>
                                        <h6 class="bg-light-alt py-3 px-2 mb-0">
                                            <i data-feather="calendar" class="align-self-center icon-xs mr-1"></i>
                                            01 January 2020 to 31 December 2020
                                        </h6>
                                    </div>  
                                    <div class="table-responsive mt-2">
                                        <table class="table border-dashed mb-0">
                                            <thead>
                                            <tr>
                                                <th>Device</th>
                                                <th class="text-right">Sassions</th>
                                                <th class="text-right">Day</th>
                                                <th class="text-right">Week</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Dasktops</td>
                                                <td class="text-right">1843</td>
                                                <td class="text-right">-3</td>
                                                <td class="text-right">-12</td>
                                            </tr>
                                            <tr>
                                                <td>Tablets</td>
                                                <td class="text-right">2543</td>
                                                <td class="text-right">-5</td>
                                                <td class="text-right">-2</td>                                                 
                                            </tr>
                                            <tr>
                                                <td>Mobiles</td>
                                                <td class="text-right">3654</td>
                                                <td class="text-right">-5</td>
                                                <td class="text-right">-6</td>
                                            </tr>
                                            
                                            </tbody>
                                        </table><!--end /table-->
                                    </div><!--end /div-->                                 
                                </div><!--end card-body--> 
                            </div><!--end card--> 
                        </div> <!--end col-->  --}}
                    </div><!--end row-->
                </div><!-- container -->
                @include('erm.dashboard_stats')

                <!-- System Update Modal -->
                <div class="modal fade" id="systemUpdateModal" tabindex="-1" role="dialog" aria-labelledby="systemUpdateModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="systemUpdateModalLabel">Daftar Update Sistem</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <strong>List :</strong>
                        <ul>
                          <li><b>Pendaftaran :</b>
                            <ul>
                              <li>Fix Tanggal dan Waktu Local dalam sistem</li>
                              <li>Fix bug Antrian Pendaftaran</li>
                              <li>Fix bug Session Time Out</li>
                              <li>Update Informasi Waktu Kunjungan</li>
                              <li>Update Informasi Asesmen Selesai (Dokter submit asesmen)</li>
                              <li>Update Fitur Edit Antrian</li>
                              <li>Update Fitur Filter by Klinik</li>
                            </ul>
                          </li>
                          <li><b>Dokter :</b>
                            <ul>
                              <li>Fix Tanggal dan Waktu Local dalam sistem</li>
                              <li>Fix bug Tanggal Kunjungan Terakhir</li>
                              <li>Fix bug Urutan Resep Terbaru</li>
                              <li>Fix bug Save Catatan Dokter</li>
                              <li>Fix bug Copy History Resep</li>
                              <li>Fix bug Total Harga Resep</li>
                              <li>Update Informasi Waktu Kunjungan</li>
                              <li>Update Informasi Asesmen Selesai (Dokter submit asesmen)</li>
                              <li>Update Tabel Informasi Resep</li>
                              <li>Update Atutofil Aturan Pakai (Klik TAB)</li>
                              <li>Update Homepage login to Rawat Jalan Page</li>
                            </ul>
                          </li>
                        </ul>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                      </div>
                    </div>
                  </div>
                </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var options = {
        chart: { height: 320, type: 'area', stacked: true, toolbar: { show: false, autoSelected: 'zoom' } },
        colors: ['#2a77f4'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: [1.5], lineCap: 'round' },
        grid: { padding: { left: 0, right: 0 }, strokeDashArray: 3 },
        markers: { size: 0, hover: { size: 0 } },
        series: [
            { name: 'Visits', data: @json($monthlyVisits) }
        ],
        xaxis: {
            type: 'month',
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: { show: true },
            axisTicks: { show: true }
        },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.3, stops: [0, 90, 100] } },
        tooltip: { x: { format: 'dd/MM/yy HH:mm' } },
        legend: { position: 'top', horizontalAlign: 'right' }
    };
    var chart = new ApexCharts(document.querySelector('#ana_dash_1'), options);
    chart.render();
});

$(document).ready(function() {
    if (!localStorage.getItem('systemUpdateModalShown')) {
        $('#systemUpdateModal').modal('show');
        localStorage.setItem('systemUpdateModalShown', '1');
    }
});
</script>
@endpush

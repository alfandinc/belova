@extends('layouts.erm.app')
@section('title', 'ERM Premiere Belova')  
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
                        <div class="col-lg-9">
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
                        <div class="col-lg-3">
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
                        </div> <!--end col--> 
                    </div><!--end row-->
                </div><!-- container -->
@endsection

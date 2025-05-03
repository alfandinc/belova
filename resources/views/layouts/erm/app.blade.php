<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Company Portal')</title>  <!-- Dynamic title -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Admin & Dashboard Template" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('img/logo-favicon-belova.png')}}"> 

    <!-- DataTables -->
    <link href="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('dastone/plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Forms -->
    <link href="{{ asset('dastone/plugins/select2/select2.min.css')}}" rel="stylesheet" type="text/css" />
    {{-- <link href="{{ asset('dastone/plugins/air-datepicker/air-datepicker.min.css')}}" rel="stylesheet" type="text/css" /> --}}
    <link href="{{ asset('dastone/plugins/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
    <!--Form Wizard-->
    <link rel="stylesheet" href="{{ asset('dastone//plugins/jquery-steps/jquery.steps.css')}}">
    <!-- App CSS -->
    <link href="{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/app-dark.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/metisMenu.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/daterangepicker/daterangepicker.css')}}" rel="stylesheet" type="text/css" />
        <!-- Sweet Alert -->
        <link href="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css">
        <link href="{{ asset('dastone/plugins/animate/animate.css')}}" rel="stylesheet" type="text/css">
    {{-- FullCalendar --}}

    {{-- <link href="{{ asset('fullcalendar/dist/index.global.min.js')}}" />
    <link href="{{ asset('fullcalendar/dist/index.global.js')}}" /> --}}
 {{-- <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' /> --}}





</head>

<body>
    @yield('navbar')
    <div class="page-wrapper">
        @include('layouts.erm.topbar')
        <div class="page-content">
         <!-- Page Content -->
            @yield('content')    
        @include('layouts.erm.footer')
        </div>
    </div>   
    <!-- jQuery & Scripts -->
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/metismenu.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/waves.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/feather.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/simplebar.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/moment.js')}}"></script>
    <script src="{{ asset('dastone/plugins/daterangepicker/daterangepicker.js')}}"></script>
    <script src="{{ asset('dastone/plugins/select2/select2.min.js')}}"></script>
    <script src="{{ asset('dastone/plugins/air-datepicker/air-datepicker.min.js')}}"></script>
    <script src="{{ asset('dastone/plugins/flatpickr/flatpickr.min.js')}}"></script>
    <script src="{{ asset('dastone/plugins/timepicker/bootstrap-material-datetimepicker.js')}}"></script>
    <script src="{{ asset('dastone/plugins/jquery-steps/jquery.steps.min.js')}}"></script>
    <script src="{{ asset('dastone/assets/pages/jquery.form-wizard.init.js')}}"></script>

    <!-- DataTables JS -->
    <!-- Required datatable js -->
    <script src="{{ asset('dastone/plugins/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <script src="{{ asset('dastone/plugins/datatables/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('dastone/plugins/datatables/responsive.bootstrap4.min.js')}}"></script>

    <script src="{{ asset('dastone/plugins/apex-charts/apexcharts.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/pages/jquery.analytics_dashboard.init.js')}}"></script>

    <!-- App js -->
    <script src="{{ asset('dastone/default/assets/js/app.js')}}"></script>


    
        <!-- Sweet-Alert  -->
        <script src="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.js')}}"></script>
        <script src="{{ asset('dastone/pages/jquery.sweet-alert.init.js')}}"></script>
    {{-- <script src="{{ asset('fullcalendar/dist/index.global.min.js')}}"></script>
    <script src="{{ asset('fullcalendar/dist/index.global.js')}}"></script> --}}

        {{-- <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script> --}}

    @yield('scripts')
</body>
</html>

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

    <!-- App CSS -->
    <link href="{{ asset('dastone/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/app.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/metisMenu.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/daterangepicker/daterangepicker.css')}}" rel="stylesheet" type="text/css" />

</head>

<body class="account-body accountbg">
    @include('layouts.erm.navbar')
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

    <!-- DataTables JS -->
    <!-- Required datatable js -->
    <script src="{{ asset('dastone/plugins/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <script src="{{ asset('dastone/plugins/apex-charts/apexcharts.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/pages/jquery.analytics_dashboard.init.js')}}"></script>

    <!-- App js -->
    <script src="{{ asset('dastone/default/assets/js/app.js')}}"></script>

    @yield('scripts')
</body>
</html>

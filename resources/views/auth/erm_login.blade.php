<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Login | ERM</title>  <!-- Dynamic title -->
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



<div class="container">
    <div class="row vh-100 d-flex justify-content-center">
        <div class="col-12 align-self-center">
            <div class="row">
                <div class="col-lg-5 mx-auto">
                    <div class="card rounded-3" style="border-radius: 15px; overflow: hidden;">
                        <div class="card-body p-0 auth-header-box" style="background-color: #0d6efd;">
                            <div class="text-center p-3">
                                <h1 class="mt-3 mb-1 font-weight-semibold text-white"><i class="fas fa-heartbeat"></i> ERM Login</h1>
                                <h5 class="text-white">Welcome to ERM Belova, login to continue.</h5>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <input type="hidden" name="module" value="erm">
                                <div class="form-group mb-2">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter Email">
                                </div>

                                <div class="form-group mb-2">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter password">
                                </div>

                                <button class="btn btn-primary btn-block" type="submit">Log In</button>
                                <a href="{{ url('/') }}" class="btn btn-secondary btn-block mt-2">Back to Main Menu</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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


</body>
</html>

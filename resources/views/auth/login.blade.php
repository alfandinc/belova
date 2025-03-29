<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Login | ERM Belova</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('img/logo-favicon-belova.png')}}"> 

    <!-- App css -->
    <link href="{{ asset('dastone/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/app.min.css')}}" rel="stylesheet" type="text/css" />
</head>

<body class="account-body accountbg">
    <!-- Log In page -->
    <div class="container">
        <div class="row vh-100 d-flex justify-content-center">
            <div class="col-12 align-self-center">
                <div class="row">
                    <div class="col-lg-5 mx-auto">
                        <div class="card rounded-3" style="border-radius: 15px; overflow: hidden;">
                            <div class="card-body p-0 auth-header-box" style="background-color: #0d6efd;">
                                <div class="text-center p-3 d-flex align-items-center justify-content-center">
                                    
                                    <div>
                                        <h1 class="mt-3 mb-1 font-weight-semibold text-white" style="font-size: 32px;"><i class="fas fa-heartbeat fa text-white mr-1"></i>ERM Login</h1>
                                        <h1 class="mb-1 font-weight-medium text-white" style="font-size: 16px;">Welcome to ERM Belova, Login to continue.</h1>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                
                                <!-- Tab panes -->
                                <div class="tab-content">
                                    <div class="tab-pane active p-3" id="LogIn_Tab" role="tabpanel">
                                        <form method="POST" action="{{ route('login') }}">
                                            @csrf

                                            <div class="form-group mb-2">
                                                <label for="username">Username</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="username"
                                                        id="username" placeholder="Enter username">
                                                </div>
                                            </div><!--end form-group-->

                                            <div class="form-group mb-2">
                                                <label for="userpassword">Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="password"
                                                        id="userpassword" placeholder="Enter password">
                                                </div>
                                            </div><!--end form-group-->

                                            

                                            <div class="form-group mb-0 row">
                                                <div class="col-12">
                                                    <button class="btn btn-primary btn-block waves-effect waves-light"
                                                        type="submit">Log In 
                                                        <i class="fas fa-sign-in-alt ml-1"></i></button>
                                                </div><!--end col-->
                                            </div> <!--end form-group-->
                                        </form><!--end form-->
                                        
                            
                                    </div>
                                </div>
                            </div><!--end card-body-->
                            <div class="card-body bg-light-alt text-center">
                                <span class="text-muted d-none d-sm-inline-block">Mannatthemes © 2020</span>
                            </div>
                        </div><!--end card-->
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end col-->
        </div><!--end row-->
    </div><!--end container-->
    <!-- End Log In page -->
    <!-- jQuery  -->
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/waves.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/feather.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/simplebar.min.js')}}"></script>
</body>

</html>
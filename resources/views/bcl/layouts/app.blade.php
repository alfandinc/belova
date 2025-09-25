<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{config('app.name')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="{{config('app.tagline')}}" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('assets/images/icon.png') }}">

    <!-- App css -->
    <link href="{{ URL::asset('plugins/pacejs/flash.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ URL::asset('plugins/pacejs/pace.js') }}"></script>
    <link href="{{ URL::asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/metisMenu.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('vendor/datatable/datatables.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('plugins/jquery-toast/dist/jquery.toast.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/app.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('plugins/jquery-confirm/jquery-confirm.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('plugins/dropify/css/dropify.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('plugins/hover-css/css/hover.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('plugins/animate/animate.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('plugins/lightbox/magnific-popup.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome-animation@1.1.1/css/font-awesome-animation.min.css"> -->
    <link href="{{ URL::asset('plugins/font-awesome-animation/font-awesome-animation.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ URL::asset('plugins/custom-drag-drop-file-upload/fileUpload/fileUpload.css') }}">
</head>

<body class="dark-sidenav">
    <!-- Left Sidenav -->
    <div class="left-sidenav">
        <!-- LOGO -->
        <div class="brand">
            <a href="{{ url('/') }}" class="logo">
                <!-- <span>
                    <img src="{{ URL::asset('assets/images/icon.png')}}" alt="logo-small" class="logo-sm">
                </span> -->
                <span>
                    <img src="{{ URL::asset('assets/images/logo.png')}}" style="max-height: 32px;" alt="logo-large" class="logo-xl logo-dark">
                </span>
            </a>
            <br>
            <h5 class="text-white text-center mt-0 pt-0">{{config('app.tagline2')}}</h5>
        </div>
        <!--end logo-->
        @include('layouts.navbar')
    </div>
    <!-- end left-sidenav-->


    <div class="page-wrapper">
        <!-- Top Bar Start -->
        @include('layouts.topbar')
        <!-- Top Bar End -->

        <!-- Page Content-->
        <div class="page-content">
            <div class="container-fluid">
                @yield('content')
                <!-- end page title end breadcrumb -->

                <span id="button_export" class="m-0 p-0 hidden"></span>
            </div>

            @include('layouts.footer')
        </div>
        <!-- end page content -->
    </div>
    <!-- end page-wrapper -->

    <!-- jQuery  -->
    <script src="{{ URL::asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/metismenu.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/waves.js') }}"></script>
    <script src="{{ URL::asset('assets/js/feather.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/moment.js') }}"></script>
    <script src="{{ URL::asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ URL::asset('vendor/datatable/datatables.min.js') }}"></script>
    <script src="{{ URL::asset('plugins/select2/select2.min.js') }}"></script>
    <script src="{{ URL::asset('plugins/jquery-toast/dist/jquery.toast.min.js') }}"></script>
    <script src="{{ URL::asset('plugins/input_money/jquery.inputmask.min.js') }}" type="text/javascript"></script>
    <script src="{{ URL::asset('plugins/bootstrap-maxlength/bootstrap-maxlength.min.js') }}"></script>
    <script src="{{ URL::asset('plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
    <script src="{{ URL::asset('plugins/dropify/js/dropify.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/jquery.form-upload.init.js') }}"></script>
    <script src="{{ URL::asset('assets/js/jquery.number.js') }}"></script>
    <script src="{{ URL::asset('plugins/lightbox/jquery.magnific-popup.js') }}"></script>
    <script src="{{ URL::asset('assets/pages/jquery.lightbox.init.js') }}"></script>
    <script src="{{ URL::asset('plugins/custom-drag-drop-file-upload/fileUpload/fileUpload.js') }}"></script>

    <!-- App js -->
    <script src="{{ URL::asset('assets/js/core.js') }}"></script>
    <script src="{{ URL::asset('assets/js/app.js') }}"></script>
    @yield('pagescript')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: 'Pilih',
                width: '100%'
            });
            @if($message = Session::get('success'))
            $.toast({
                text: "{{ $message }}",
                heading: 'Result',
                position: 'top-center',
                hideAfter: 5000,
                icon: 'info',
            });
            @endif
            @if($error = Session::get('error'))
            $.toast({
                text: "{{ $error }}",
                heading: 'Result',
                position: 'top-center',
                hideAfter: 5000,
                icon: 'error',
            });
            @endif
        });
    </script>
</body>

</html>
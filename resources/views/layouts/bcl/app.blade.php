<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Belova Center Living</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="{{config('app.tagline')}}" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{asset('dastone/assets/images/icon.png') }}">

    <!-- App css -->
    <link href="{{ asset('dastone/plugins/pacejs/flash.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ asset('dastone/plugins/pacejs/pace.js') }}"></script>
    <link href="{{ asset('dastone/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/metisMenu.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/vendor/datatable/datatables.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/jquery-toast/dist/jquery.toast.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/assets/css/app.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/jquery-confirm/jquery-confirm.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/dropify/css/dropify.min.css') }}" rel="stylesheet">
    <link href="{{ asset('dastone/plugins/hover-css/css/hover.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/plugins/animate/animate.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('dastone/plugins/lightbox/magnific-popup.css') }}" rel="stylesheet" type="text/css" />
    
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome-animation@1.1.1/css/font-awesome-animation.min.css"> -->
    <link href="{{ asset('dastone/plugins/font-awesome-animation/font-awesome-animation.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('dastone/plugins/custom-drag-drop-file-upload/fileUpload/fileUpload.css') }}">
</head>

<body class="dark-sidenav">
    <!-- Left Sidenav -->
    <div class="left-sidenav">
        <!-- LOGO -->
        <div class="brand">
            <a href="{{ url('/') }}" class="logo">
                <!-- <span>
                    <img src="{{ asset('dastone/assets/images/icon.png')}}" alt="logo-small" class="logo-sm">
                </span> -->
                <span>
                    <img src="{{ asset('dastone/assets/images/logo.png')}}" style="max-height: 32px;" alt="logo-large" class="logo-xl logo-dark">
                </span>
            </a>
            <br>
            <h5 class="text-white text-center mt-0 pt-0">{{config('app.tagline2')}}</h5>
        </div>
        <!--end logo-->
        @include('layouts.bcl.navbar')
    </div>
    <!-- end left-sidenav-->


    <div class="page-wrapper">
        <!-- Top Bar Start -->
        @include('layouts.bcl.topbar')
        <!-- Top Bar End -->

        <!-- Page Content-->
        <div class="page-content">
            <div class="container-fluid">
                @yield('content')
                <!-- end page title end breadcrumb -->

                <span id="button_export" class="m-0 p-0 hidden"></span>
            </div>

            @include('layouts.bcl.footer')
        </div>
        <!-- end page content -->
    </div>
    <!-- end page-wrapper -->

    <!-- jQuery  -->
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/metismenu.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/waves.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/moment.js') }}"></script>
    <script src="{{ asset('dastone/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('dastone/vendor/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/jquery-toast/dist/jquery.toast.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/input_money/jquery.inputmask.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('dastone/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/dropify/js/dropify.min.js') }}"></script>
    <script src="{{ asset('dastone/assets/js/jquery.form-upload.init.js') }}"></script>
    <script src="{{ asset('dastone/assets/js/jquery.number.js') }}"></script>
    <script src="{{ asset('dastone/plugins/lightbox/jquery.magnific-popup.js') }}"></script>
    <script src="{{ asset('dastone/assets/pages/jquery.lightbox.init.js') }}"></script>
    <script src="{{ asset('dastone/plugins/custom-drag-drop-file-upload/fileUpload/fileUpload.js') }}"></script>

    <script>
        // Global polyfill for $.number if jquery.number plugin file is missing or failed to load.
        if (typeof $.number !== 'function') {
            $.number = function(number, decimals) {
                try {
                    var opts = {};
                    if (typeof decimals === 'number') {
                        opts.minimumFractionDigits = decimals;
                        opts.maximumFractionDigits = decimals;
                    }
                    return new Intl.NumberFormat('id-ID', opts).format(Number(number || 0));
                } catch (e) {
                    var n = Number(number || 0).toFixed(decimals || 0);
                    return n.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }
            };
            // Also provide a simple jQuery element helper to mimic plugin if used as $(el).number(dec)
            if (typeof $.fn !== 'undefined' && typeof $.fn.number !== 'function') {
                $.fn.number = function(decimals) {
                    return this.each(function() {
                        var $t = $(this);
                        var val = $t.data('value') !== undefined ? $t.data('value') : $t.text();
                        $t.text($.number(val, decimals));
                    });
                };
            }
        }
    </script>

    <!-- App js -->
    <script src="{{ asset('dastone/default/assets/js/core.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/app.js') }}"></script>
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
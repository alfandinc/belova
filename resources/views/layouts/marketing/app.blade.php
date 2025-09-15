<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Company Portal')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Admin & Dashboard Template" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('img/logo-favicon-belova.png') }}">

    <!-- ======= Early theme logic ======= -->
    <script>
        // Prevent flicker before theme loads
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.classList.add(savedTheme === 'light' ? 'theme-light' : 'theme-dark');
        document.documentElement.classList.add('no-transition');
    </script>

    

    <!-- Other CSS -->
    <link href="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/select2/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/default/assets/css/icons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/default/assets/css/metisMenu.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('dastone/plugins/jquery-steps/jquery.steps.css')}}">
    <link href="{{ asset('dastone/plugins/animate/animate.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/default/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />

    <!-- Theme CSS -->
    <link id="bootstrap-dark" href="{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}" rel="stylesheet" />
    <link id="app-dark" href="{{ asset('dastone/default/assets/css/app-dark.min.css') }}" rel="stylesheet" />
    <link id="bootstrap-light" href="{{ asset('dastone/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" disabled />
    <link id="app-light" href="{{ asset('dastone/default/assets/css/app.min.css') }}" rel="stylesheet" disabled />

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    @stack('styles')

    <style>
        body { visibility: hidden; }
        .no-transition *, .no-transition *::before, .no-transition *::after {
            transition: none !important;
        }
    </style>
</head>

<body>
    @yield('navbar')

    <div class="page-wrapper">
        @include('layouts.erm.topbar')

        <div class="page-content">
            @yield('content')
            
        </div>
        @include('layouts.erm.footer')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/metismenu.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/waves.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/moment.js') }}"></script>
    <script src="{{ asset('dastone/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('dastone/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/timepicker/bootstrap-material-datetimepicker.js') }}"></script>
    <script src="{{ asset('dastone/plugins/jquery-steps/jquery.steps.min.js') }}"></script>
    <script src="{{ asset('dastone/assets/pages/jquery.form-wizard.init.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/apex-charts/apexcharts.min.js') }}"></script>
    @if(request()->is('marketing') || request()->is('marketing/dashboard'))
    <script src="{{ asset('dastone/default/assets/pages/jquery.analytics_dashboard.init.js') }}"></script>
    @endif
    <script src="{{ asset('dastone/default/assets/js/app.js') }}"></script>

    <!-- Sweet-Alert  -->
        <script src="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.js')}}"></script>
        <script src="{{ asset('dastone/assets/pages/jquery.sweet-alert.init.js')}}"></script>

    <!-- CSRF Token for AJAX -->
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>

    <!-- Theme Toggle Script -->
    <script>
        function applyTheme(isDark) {
            const html = document.documentElement;
            const bootstrapDark = document.getElementById('bootstrap-dark');
            const appDark = document.getElementById('app-dark');
            const bootstrapLight = document.getElementById('bootstrap-light');
            const appLight = document.getElementById('app-light');

            html.classList.add('no-transition');

            bootstrapDark.disabled = !isDark;
            appDark.disabled = !isDark;
            bootstrapLight.disabled = isDark;
            appLight.disabled = isDark;

            // Force style recalculation
            void bootstrapDark.offsetWidth;

            html.classList.remove('theme-dark', 'theme-light');
            html.classList.add(isDark ? 'theme-dark' : 'theme-light');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            setTimeout(() => {
                html.classList.remove('no-transition');
            }, 100);
        }

        document.addEventListener("DOMContentLoaded", function () {
            const toggle = document.getElementById('darkModeSwitch');
            const saved = localStorage.getItem('theme') || 'dark';
            const isDark = saved === 'dark';

            applyTheme(isDark);

            if (toggle) {
                toggle.checked = isDark;
                toggle.addEventListener('change', function () {
                    applyTheme(this.checked);
                });
            }

            // Show content after theme is applied
            document.body.style.visibility = 'visible';
        });
    </script>

    @yield('scripts')
    @stack('scripts')  <!-- Add this line -->
</body>
</html>

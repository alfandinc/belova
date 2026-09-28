<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Company Portal')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Admin & Dashboard Template" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App favicon -->
    @php
        $clinicChoice = session('clinic_choice');
        if ($clinicChoice === 'premiere') {
            $favicon = asset('img/favicon-premiere.png');
        } elseif ($clinicChoice === 'skin') {
            $favicon = asset('img/favicon-belovaskin.png');
        } elseif ($clinicChoice === 'dental') {
            $favicon = asset('img/logo-dental.png');
        } else {
            $favicon = asset('img/favicon-belovaskin.png');
        }
    @endphp
    <link rel="shortcut icon" href="{{ $favicon }}">

    @php
        // ?embed=1 renders only the page content (used when a page is shown inside a modal iframe,
        // e.g. billing create opened from the billing index). Navbar, topbar, footer, chat widget,
        // notification polling and the sidebar/dashboard assets are skipped because the parent page has them.
        $financeEmbed = request()->boolean('embed');
    @endphp

    <!-- Other CSS -->
    <link href="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/select2/select2.min.css') }}" rel="stylesheet" />
    @unless($financeEmbed)
    <link href="{{ asset('dastone/plugins/flatpickr/flatpickr.min.css') }}" rel="stylesheet" />
    @endunless
    <link href="{{ asset('dastone/default/assets/css/icons.min.css') }}" rel="stylesheet" />
    @unless($financeEmbed)
    <link href="{{ asset('dastone/default/assets/css/metisMenu.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" />
    @endunless
    <link href="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.css') }}" rel="stylesheet" />
    @unless($financeEmbed)
    <link rel="stylesheet" href="{{ asset('dastone/plugins/jquery-steps/jquery.steps.css')}}">
    <link href="{{ asset('dastone/plugins/animate/animate.css') }}" rel="stylesheet" />
    @endunless

    <!-- Theme CSS -->
    <link id="bootstrap-dark" href="{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}" rel="stylesheet" />
    <link id="app-dark" href="{{ asset('dastone/default/assets/css/app-dark.min.css') }}" rel="stylesheet" />
    <link id="bootstrap-light" href="{{ asset('dastone/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" disabled />
    <link id="app-light" href="{{ asset('dastone/default/assets/css/app.min.css') }}" rel="stylesheet" disabled />

    <!-- ======= Early theme bootstrap (critical for LCP) =======
         Apply saved theme immediately to avoid body hide + JS-delayed paint.
    -->
    <script>
        (function () {
            try {
                const saved = localStorage.getItem('theme') || 'dark';
                const isDark = saved !== 'light';
                const html = document.documentElement;

                html.classList.add(isDark ? 'theme-dark' : 'theme-light');
                html.classList.add('no-transition');

                const bootstrapDark = document.getElementById('bootstrap-dark');
                const appDark = document.getElementById('app-dark');
                const bootstrapLight = document.getElementById('bootstrap-light');
                const appLight = document.getElementById('app-light');

                if (bootstrapDark) bootstrapDark.disabled = !isDark;
                if (appDark) appDark.disabled = !isDark;
                if (bootstrapLight) bootstrapLight.disabled = isDark;
                if (appLight) appLight.disabled = isDark;
            } catch (e) {
                // fail open: default CSS stays as authored (dark)
            }
        })();
    </script>

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    

    <style>
        .no-transition *, .no-transition *::before, .no-transition *::after {
            transition: none !important;
        }
    </style>
</head>

<body class="{{ $financeEmbed ? 'finance-embed' : '' }}">
    @if($financeEmbed)
        <style>
            /* the theme makes <body> a flex row (normally .page-wrapper sizes itself); in embed mode
               the content wrapper must take the full width instead of shrinking to its content */
            body.finance-embed { padding: 0; margin: 0; display: block !important; }
            body.finance-embed .finance-embed-content { width: 100%; min-width: 0; padding: 12px 16px; }
        </style>
        <div class="finance-embed-content">
            @yield('content')
        </div>
    @else
    @yield('navbar')

    <div class="page-wrapper">
        @include('layouts.shared.topbar')

        <div class="page-content">
            @yield('content')
            @include('layouts.finance.footer')
        </div>
    </div>
    @endif

    <!-- Scripts -->
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/bootstrap.bundle.min.js') }}"></script>
    @unless($financeEmbed)
    {{-- sidebar / topbar helpers (not rendered in embed mode) --}}
    <script src="{{ asset('dastone/default/assets/js/metismenu.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/waves.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/simplebar.min.js') }}"></script>
    @endunless
    <script src="{{ asset('dastone/default/assets/js/moment.js') }}"></script>
    @unless($financeEmbed)
    <script src="{{ asset('dastone/plugins/daterangepicker/daterangepicker.js') }}"></script>
    @endunless
    <script src="{{ asset('dastone/plugins/select2/select2.min.js') }}"></script>
    @unless($financeEmbed)
    <script src="{{ asset('dastone/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/timepicker/bootstrap-material-datetimepicker.js') }}"></script>
    <script src="{{ asset('dastone/plugins/jquery-steps/jquery.steps.min.js') }}"></script>
    <script src="{{ asset('dastone/assets/pages/jquery.form-wizard.init.js') }}"></script>
    @endunless
    <script src="{{ asset('dastone/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/responsive.bootstrap4.min.js') }}"></script>
    @unless($financeEmbed)
    {{-- dashboard charts + theme init for sidebar/topbar: not needed for pages shown in a modal (embed) --}}
    <script src="{{ asset('dastone/plugins/apex-charts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/pages/jquery.analytics_dashboard.init.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/app.js') }}"></script>
    @endunless

    <!-- Sweet-Alert  -->
        <script src="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.js')}}"></script>
        <script src="{{ asset('dastone/assets/pages/jquery.sweet-alert.init.js')}}"></script>

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
        });
    </script>

    @unless($financeEmbed)
    @include('partials.global_emotion_heartbeat')
    @include('partials.global_chat_widget')
    @endunless
    @yield('scripts')
    {{-- @include('partials.farmasi-notif') --}}
    @if(!$financeEmbed && Auth::user() && Auth::user()->hasRole('Kasir'))
    <script>
        // Kasir notification polling: popup + optional sound when Farmasi submits resep
        window.kasirSoundEnabled = false;
        $(function() {
            Swal.fire({
                title: 'Aktifkan Notifikasi Suara?',
                text: 'Klik OK untuk mengaktifkan suara notifikasi untuk Kasir. Cukup lakukan sekali.',
                icon: 'question',
                confirmButtonText: 'OK',
            }).then(function() {
                try { var audio = new Audio('/sounds/confirm.mp3'); audio.play(); } catch(e) {}
                window.kasirSoundEnabled = true;
            }).catch(function(){ /* ignore */ });
        });

        function kasirEscape(s) {
            return $('<div>').text(s == null ? '' : String(s)).html();
        }

        var kasirPollBusy = false;
        function pollKasirNotifications() {
            // Don't poll while the tab is hidden, a request is in flight, or another dialog is open:
            // unread notifications stay queued and are shown together in one popup later.
            if (kasirPollBusy || document.hidden) return;
            if (window.Swal && typeof Swal.isVisible === 'function' && Swal.isVisible()) return;

            kasirPollBusy = true;
            $.get('/finance/get-notif', function(data) {
                if (!data || !data.new) return;

                var count = parseInt(data.count || 1, 10);
                if (count <= 1) {
                    Swal.fire({
                        title: data.sender ? data.sender : 'Notifikasi',
                        text: data.message || 'Ada notifikasi baru.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else {
                    var items = (data.items || []).map(function(n) {
                        var when = n.created_at && window.moment ? ' <small class="text-muted">(' + moment(n.created_at).fromNow() + ')</small>' : '';
                        return '<li class="mb-1"><strong>' + kasirEscape(n.sender || 'Notifikasi') + ':</strong> ' + kasirEscape(n.message) + when + '</li>';
                    }).join('');
                    var more = count > (data.items || []).length
                        ? '<div class="small text-muted mt-2">dan ' + (count - data.items.length) + ' lainnya. Lihat semua di Notifikasi Lama.</div>'
                        : '';
                    Swal.fire({
                        title: count + ' Notifikasi Baru',
                        html: '<ul class="text-left pl-3 mb-0">' + items + '</ul>' + more,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }

                if (window.kasirSoundEnabled) {
                    try { var s = new Audio('/sounds/money.mp3'); s.play(); } catch(e) {}
                }
            }).fail(function(){ /* silent */ }).always(function() {
                kasirPollBusy = false;
            });
        }

        // Poll every 3 seconds, and right away when the tab becomes visible again
        setInterval(pollKasirNotifications, 3000);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) pollKasirNotifications();
        });
    </script>
    @endif
</body>
</html>

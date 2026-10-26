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
    @include('layouts.shared.theme_styles')

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    @yield('styles')

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
        @include('layouts.shared.topbar', ['topbarShowDateTime' => true, 'topbarDateTimeId' => 'erm-date-time-display'])

        <div class="page-content">
            @yield('content')
            
        </div>
        @include('layouts.erm.footer')
    </div>
    <!-- Scripts -->
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js') }}"></script>
    <script>
        // Ensure AJAX requests include Laravel CSRF token
        (function(){
            try {
                var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                if (window.jQuery) {
                    window.jQuery.ajaxSetup({
                        headers: { 'X-CSRF-TOKEN': token }
                    });
                }
                // If axios is used on the page, set its default header too
                if (window.axios) {
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                    window.axios.defaults.withCredentials = true;
                }
            } catch (e) {
                // silent fail: page might not have meta tag or libs yet
            }
        })();
    </script>
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
    <script src="{{ asset('dastone/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/datatables/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('dastone/plugins/apex-charts/apexcharts.min.js') }}"></script>
    {{-- <script src="{{ asset('dastone/default/assets/pages/jquery.analytics_dashboard.init.js') }}"></script> --}}
    <script src="{{ asset('dastone/default/assets/js/app.js') }}"></script>
    {{-- One notification poller per browser instead of per tab (used by farmasi/perawat/lab/rawat jalan polling) --}}
    <script src="{{ asset('js/belova-tab-leader.js') }}?v={{ filemtime(public_path('js/belova-tab-leader.js')) }}"></script>

    <!-- Sweet-Alert  -->
        <script src="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.js')}}"></script>
    <script>
    // A Bootstrap 4 modal pulls keyboard focus back into itself on every focusin, so the input of a
    // SweetAlert dialog (or a Select2 search box) opened on top of it could not be typed in.
    // Let focus stay in those overlays; everything else keeps the normal modal focus trap.
    (function () {
        if (!window.jQuery || !$.fn.modal || !$.fn.modal.Constructor || $.fn.modal.Constructor.prototype._wfFocusPatched) return;
        var proto = $.fn.modal.Constructor.prototype;
        proto._enforceFocus = function () {
            var self = this;
            $(document).off('focusin.bs.modal').on('focusin.bs.modal', function (e) {
                if ($(e.target).closest('.swal2-container, .select2-container').length) return;
                if (document !== e.target && self._element !== e.target && $(self._element).has(e.target).length === 0) {
                    self._element.focus();
                }
            });
        };
        proto._wfFocusPatched = true;
    })();

    // Readable (HTML-escaped) message of a failed AJAX save for the error Swal: validation errors as a list,
    // otherwise the server's message, or what the HTTP status means.
    window.belovaAjaxError = function (xhr, fallback) {
        var esc = function (s) { return $('<div>').text(String(s)).html(); };
        var res = (xhr && xhr.responseJSON) || {};
        if (res.errors) {
            return Object.values(res.errors).flat().map(function (e) { return '&bull; ' + esc(e); }).join('<br>');
        }
        switch (xhr ? xhr.status : 0) {
            case 0: return 'Tidak ada koneksi ke server. Periksa jaringan lalu simpan lagi.';
            case 419: return 'Sesi login sudah habis. Salin dulu isian yang panjang, lalu muat ulang halaman dan simpan lagi.';
            case 403: return esc(res.message || 'Anda tidak punya akses untuk menyimpan data ini.');
            case 413: return 'Data / gambar yang dikirim terlalu besar.';
        }
        if (res.message && res.message !== 'Server Error') {
            return esc(res.message);
        }
        return esc(fallback || 'Terjadi kesalahan saat mengirim data.') + ' (kode ' + xhr.status + ')';
    };
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

            window.belovaSetThemeSheets(isDark);

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

    @include('partials.global_emotion_heartbeat')
    @include('partials.global_chat_widget')
    @yield('scripts')
    @stack('scripts')  <!-- Add this line -->

    @include('erm.partials.perawat-notif')
    @include('partials.farmasi-notif')
    @php $user = auth()->user(); @endphp
    @if($user && $user->hasRole('Dokter'))
    <script>
        (function(){
            const LS_KEY = 'labNotifLastCompleted';
            const endpoint = '{{ route('erm.elab.notifications.completed') }}';
            const pollIntervalMs = 15000;
            let lastCompleted = null; // last seen completed_at, shared by all tabs through localStorage
            let pollBusy = false;

            function toast(text){
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Hasil Lab Selesai',
                    text: text,
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true
                });
            }

            async function poll(){
                if (pollBusy) return;
                pollBusy = true;
                try {
                    // Re-read every time: another tab may have been the poller until now
                    lastCompleted = localStorage.getItem(LS_KEY);
                    const url = new URL(endpoint, window.location.origin);
                    if (lastCompleted) url.searchParams.set('since', lastCompleted);
                    const res = await fetch(url.toString(), { headers:{'X-Requested-With':'XMLHttpRequest'} });
                    if(!res.ok) throw new Error('HTTP '+res.status);
                    const data = await res.json();
                    if(data.ok){
                        // First hydration in this browser (no lastCompleted yet): just set baseline and skip toasts
                        if(!lastCompleted){
                            // Prefer last_completed_at returned, fallback server_time
                            lastCompleted = data.last_completed_at || data.server_time;
                            if (lastCompleted) localStorage.setItem(LS_KEY, lastCompleted);
                        } else {
                            (data.data || []).forEach(item => {
                                if(item.completed_at){
                                    toast((item.test || 'Pemeriksaan') + ' pasien ' + (item.patient || '-') + ' sudah selesai');
                                    // Move cursor forward if this item newer
                                    if(!lastCompleted || item.completed_at > lastCompleted){
                                        lastCompleted = item.completed_at;
                                        localStorage.setItem(LS_KEY, lastCompleted);
                                    }
                                }
                            });
                            // Also update from API summary if newer
                            if(data.last_completed_at && (!lastCompleted || data.last_completed_at > lastCompleted)){
                                lastCompleted = data.last_completed_at;
                                localStorage.setItem(LS_KEY, lastCompleted);
                            }
                        }
                    }
                } catch(e){
                    // silent
                } finally {
                    pollBusy = false;
                }
            }

            document.addEventListener('DOMContentLoaded', function(){
                // One poller per browser (the tab the user is looking at); see public/js/belova-tab-leader.js
                if (window.BelovaTabLeader) {
                    window.BelovaTabLeader.run('lab-notif', pollIntervalMs, poll);
                } else {
                    setInterval(poll, pollIntervalMs);
                    poll();
                }
            });
        })();
    </script>
    @endif
</body>
</html>

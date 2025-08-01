        <!-- Left Sidenav -->
        <div class="left-sidenav ">
            <!-- LOGO -->
            @php
                $clinicChoice = session('clinic_choice');
                if ($clinicChoice === 'premiere') {
                    $lightLogo = asset('img/logo-premiere-bw.png');
                    $darkLogo = asset('img/logo-premiere.png');
                    $logoHeight = '50px';
                } elseif ($clinicChoice === 'skin') {
                    $lightLogo = asset('img/logo-belovaskin-bw.png');
                    $darkLogo = asset('img/logo-belovaskin.png');
                    $logoHeight = '50px';
                } else {
                    $lightLogo = asset('img/logo-belovacorp-bw.png');
                    $darkLogo = asset('img/logo-belovacorp.png');
                    $logoHeight = '50px';
                }
            @endphp
            <div class="brand mt-3">
                <a href="/finance/billing" class="logo">
                    <span>
                        <!-- Light-theme logo (for dark background) -->
                        <img src="{{ $lightLogo }}" alt="logo" class="logo-light" style="width: auto; height: {{ $logoHeight }};">

                        <!-- Dark-theme logo (for light background) -->
                        <img src="{{ $darkLogo }}" alt="logo" class="logo-dark" style="width: auto; height: {{ $logoHeight }};">
                    </span>
                </a>
            </div>
            <!--end logo-->
            <div class="menu-content h-100" data-simplebar>
                <ul class="metismenu left-sidenav-menu">
                    {{-- <li>
                        <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/finance"><i class="ti-control-record"></i>Analytics</a></li>
                        </ul>
                    </li> --}}
                    <li>
                        <a href="javascript: void(0);">
                            <i class="fas fa-cash-register align-self-center menu-icon"></i>
                            <span>Kasir</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/finance/billing"><i class="ti-control-record"></i>Billing</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('finance.rekap-penjualan.form') }}"><i class="ti-control-record"></i>Rekap Penjualan</a></li>
                        </ul>
                    </li>
                    {{-- <li>
                        <a href="javascript: void(0);">
                            <i class="fas fa-file-invoice-dollar align-self-center menu-icon"></i>
                            <span>Keuangan</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/finance/laporankeuangan"><i class="ti-control-record"></i>Laporan Keuangan</a></li>
                        </ul>
                    </li> --}}
                </ul>              
            </div>
        </div>
        <!-- end left-sidenav-->
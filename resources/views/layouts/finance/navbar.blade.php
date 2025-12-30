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
                    @hasanyrole('Kasir|Admin|Finance')
                    <li>
                        <a href="javascript: void(0);">
                            <i class="fas fa-cash-register align-self-center menu-icon"></i>
                            <span>Kasir</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/finance/billing"><i class="ti-control-record"></i>Billing</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('finance.piutang.index') }}"><i class="ti-control-record"></i>Piutang</a></li>
                            <!-- Rekap Penjualan moved to Laporan section -->
                            <li class="nav-item"><a class="nav-link" href="{{ route('finance.retur-pembelian.index') }}"><i class="ti-control-record"></i>Retur Pembelian</a></li>
                            <!-- Pengajuan links moved to their own top-level section -->
                        </ul>
                    </li>
                    @endhasanyrole
                    @hasanyrole('Admin|Finance')
                    <li>
                        <a href="javascript: void(0);">
                            <i class="fas fa-chart-line align-self-center menu-icon"></i>
                            <span>Laporan</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="{{ route('finance.rekap-penjualan.form') }}"><i class="ti-control-record"></i>Rekap Penjualan</a></li>
                        </ul>
                    </li>
                    @endhasanyrole

                    <li>
                        <a href="javascript: void(0);">
                            <i class="fas fa-file-invoice-dollar align-self-center menu-icon"></i>
                            <span>Pengajuan</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            @hasanyrole('Kasir|Admin|Finance|Employee|Maanager|Hrd')
                            <li class="nav-item"><a class="nav-link" href="{{ route('finance.pengajuan.index') }}"><i class="ti-control-record"></i>Pengajuan Dana</a></li>
                            @endhasanyrole
                            @hasanyrole('Admin|Finance')
                            <li class="nav-item"><a class="nav-link" href="{{ route('finance.pengajuan.approver.index') }}"><i class="ti-control-record"></i>Approver Pengajuan</a></li>
                            @endhasanyrole
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
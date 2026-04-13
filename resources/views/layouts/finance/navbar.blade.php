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
                    <li class="menu-label mt-0">Kasir</li>
                    <li>
                        <a href="/finance/billing">
                            <i class="fas fa-file-invoice-dollar align-self-center menu-icon"></i>
                            <span>Daftar Billing</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('finance.transactions.index') }}">
                            <i class="fas fa-receipt align-self-center menu-icon"></i>
                            <span>Riwayat Transaksi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('finance.retur-pembelian.index') }}">
                            <i class="fas fa-undo-alt align-self-center menu-icon"></i>
                            <span>Retur Pembelian</span>
                        </a>
                    </li>
                    @endhasanyrole
                    
                    <li class="menu-label">Laporan</li>
                    @hasanyrole('Admin|Finance')
                    <li>
                        <a href="{{ route('finance.rekap-penjualan.form') }}">
                            <i class="fas fa-chart-line align-self-center menu-icon"></i>
                            <span>Rekap Penjualan</span>
                        </a>
                    </li>
                    @endhasanyrole
                    <li>
                        <a href="{{ route('finance.laporan-keuangan.index') }}">
                            <i class="fas fa-chart-bar align-self-center menu-icon"></i>
                            <span>Laporan Keuangan</span>
                        </a>
                    </li>
                    

                    @hasanyrole('Kasir|Admin|Finance|Employee|Maanager|Hrd')
                    <li class="menu-label">Pengajuan</li>
                    <li>
                        <a href="{{ route('finance.pengajuan.index') }}">
                            <i class="fas fa-wallet align-self-center menu-icon"></i>
                            <span>Pengajuan Dana</span>
                        </a>
                    </li>
                    @endhasanyrole
                    @hasanyrole('Admin|Finance')
                    <li>
                        <a href="{{ route('finance.pengajuan.approver.index') }}">
                            <i class="fas fa-user-check align-self-center menu-icon"></i>
                            <span>Approver Pengajuan</span>
                        </a>
                    </li>
                    @endhasanyrole
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
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
                <a href="/inventory" class="logo">
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
                        <li>
                            <a href="/inventory">
                                <i data-feather="home" class="align-self-center menu-icon"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    <li>
                        <a href="javascript: void(0);">
                            <i data-feather="database" class="align-self-center menu-icon"></i>
                            <span>Data Master</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/gedung"><i class="ti-control-record"></i>Gedung</a></li>
                            <li class="nav-item"><a class="nav-link" href="/inventory/ruangan"><i class="ti-control-record"></i>Ruangan</a></li>
                            <li class="nav-item"><a class="nav-link" href="/inventory/tipe-barang"><i class="ti-control-record"></i>Tipe Barang</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);">
                            <i data-feather="box" class="align-self-center menu-icon"></i>
                            <span>Manajemen Barang</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/barang"><i class="ti-control-record"></i>List Barang</a></li>
                            <li class="nav-item"><a class="nav-link" href="/inventory/pembelian"><i class="ti-control-record"></i>Pembelian</a></li>
                            <li class="nav-item"><a class="nav-link" href="/inventory/maintenance"><i class="ti-control-record"></i>Maintenance</a></li>
                        </ul>
                    </li>
                </ul>              
            </div>
        </div>
        <!-- end left-sidenav-->
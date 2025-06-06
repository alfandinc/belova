        <!-- Left Sidenav -->
        <div class="left-sidenav ">
            <!-- LOGO -->
            <div class="brand mt-3">
                <a href="/finance" class="logo">
                    <span>
                        <!-- Light-theme logo (for dark background) -->
                        <img src="{{ asset('img/logo-belovacorp-bw.png')}}" alt="logo" class="logo-light" style="width: auto; height: 50px;">

                        <!-- Dark-theme logo (for light background) -->
                        <img src="{{ asset('img/logo-belovacorp.png')}}" alt="logo" class="logo-dark" style="width: auto; height: 50px;">
                        {{-- <img src="{{ asset('img/logo-premiere-bw.png')}}" alt="logo-small" class="logo-sm " style="width: auto; height: 50px;"> --}}
                    </span>
                </a>
            </div>
            <!--end logo-->
            <div class="menu-content h-100" data-simplebar>
                <ul class="metismenu left-sidenav-menu">
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/finance"><i class="ti-control-record"></i>Analytics</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Billing</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/finance"><i class="ti-control-record"></i>Buat Billing</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Keuangan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/finance"><i class="ti-control-record"></i>Laporan Keuangan</a></li>
                        </ul>
                    </li>
                </ul>              
            </div>
        </div>
        <!-- end left-sidenav-->
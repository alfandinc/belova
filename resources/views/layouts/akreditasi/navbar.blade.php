<!-- Left Sidenav -->
<div class="left-sidenav">
    <!-- LOGO -->
    <div class="brand mt-3">
        <a href="/akreditasi" class="logo">
            <span>
                <!-- Light-theme logo (for dark background) -->
                <img src="{{ asset('img/logo-belovacorp-bw.png')}}" alt="logo" class="logo-light" style="width: auto; height: 50px;">

                <!-- Dark-theme logo (for light background) -->
                <img src="{{ asset('img/logo-belovacorp.png')}}" alt="logo" class="logo-dark" style="width: auto; height: 50px;">
            </span>
        </a>
    </div>
    <!--end logo-->
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li class="menu-label mt-0">Master</li>
            <li>
                <a href="javascript:void(0);"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Master</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a class="nav-link" href="{{ route('akreditasi.index') }}"><i data-feather="layers" class="align-self-center menu-icon"></i>Master BAB</a></li>
                    <li><a class="nav-link" href="{{ url('/akreditasi/bab/1/standars') }}"><i data-feather="grid" class="align-self-center menu-icon"></i>Master Standar</a></li>
                    <li><a class="nav-link" href="{{ url('/akreditasi/standar/1/eps') }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Master EP</a></li>
                </ul>
            </li>

            <li class="menu-label">BAB 1</li>
            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Standar BAB 1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li>
                        <a href="javascript:void(0);"><span>Standar 1.1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/1') }}">EP 1.1.1</a></li>
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/2') }}">EP 1.1.2</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);"><span>Standar 1.2</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/3') }}">EP 1.2.1</a></li>
                        </ul>
                    </li>
                </ul>
            </li>

            <li class="menu-label">BAB 2</li>
            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Standar BAB 2</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li>
                        <a href="javascript:void(0);"><span>Standar 2.1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-third-level" aria-expanded="false">
                            <li><a class="nav-link" href="{{ url('/akreditasi/ep/4') }}">EP 2.1.1</a></li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>              
    </div>
</div>
<!-- end left-sidenav-->
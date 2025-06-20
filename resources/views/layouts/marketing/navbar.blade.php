<!-- Left Sidenav -->
<div class="left-sidenav">
    <!-- LOGO -->
    <div class="brand mt-3">
        <a href="/marketing" class="logo">
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
            <li class="menu-label mt-0">Main</li>
            <li>
                <a href="/marketing/dashboard"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li>
            
            <li class="menu-label">Analytics</li>
            <li>
                <a href="/marketing/revenue"> <i data-feather="dollar-sign" class="align-self-center menu-icon"></i><span>Revenue</span></a>
            </li>
            <li>
                <a href="/marketing/patients"> <i data-feather="users" class="align-self-center menu-icon"></i><span>Patient Analytics</span></a>
            </li>
            <li>
                <a href="/marketing/services"> <i data-feather="activity" class="align-self-center menu-icon"></i><span>Service Analytics</span></a>
            </li>
            <li>
                <a href="/marketing/products"> <i data-feather="package" class="align-self-center menu-icon"></i><span>Product Analytics</span></a>
            </li>
            <li>
                <a href="/marketing/clinic-comparison"> <i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Clinic Performance</span></a>
            </li>
            
            <li class="menu-label">Administration</li>
            <li>
                <a href="javascript: void(0);"><i data-feather="clipboard" class="align-self-center menu-icon"></i><span>Manage Tindakan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('marketing.tindakan.index') }}"><i class="ti-control-record"></i>Tindakan</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('marketing.tindakan.paket.index') }}"><i class="ti-control-record"></i>Paket Tindakan</a></li>
                </ul>
            </li>
        </ul>              
    </div>
</div>
<!-- end left-sidenav-->
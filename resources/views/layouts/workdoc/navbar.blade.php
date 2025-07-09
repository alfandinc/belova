<!-- Left Sidenav -->
<div class="left-sidenav">
    <!-- LOGO -->
    <div class="brand mt-3">
        <a href="/workdoc" class="logo">
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
                <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('workdoc.dashboard') }}"><i class="ti-control-record"></i>Home</a></li>
                </ul>
            </li>
            <li>
                <a href="{{ route('workdoc.documents.index') }}"> <i data-feather="folder" class="align-self-center menu-icon"></i><span>Document Manager</span></a>
            </li>
        </ul>              
    </div>
</div>
<!-- end left-sidenav-->
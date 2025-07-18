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
            @role('Admin')
            <li>
                <a href="javascript:void(0);"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Master</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a class="nav-link" href="{{ route('akreditasi.index') }}"><i data-feather="layers" class="align-self-center menu-icon"></i>Master BAB</a></li>
                    <li><a class="nav-link" href="{{ url('/akreditasi/bab/1/standars') }}"><i data-feather="grid" class="align-self-center menu-icon"></i>Master Standar</a></li>
                    <li><a class="nav-link" href="{{ url('/akreditasi/standar/1/eps') }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Master EP</a></li>
                </ul>
            </li>
            @endrole
            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>BAB 1</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 1) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 1.1</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 2) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 1.2</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 3) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 1.3</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 4) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 1.4</a></li>
                    
                </ul>
            </li>
            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>BAB 2</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 5) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 2.1</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 6) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 2.2</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 7) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 2.3</a></li>
                    
                </ul>
            </li>
            <li>
                <a href="javascript:void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>BAB 3</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 8) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.1</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 9) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.2</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 10) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.3</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 11) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.4</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 12) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.5</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 13) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.6</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 14) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.7</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 15) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.8</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 16) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.9</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 17) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.10</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 18) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.11</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 19) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.12</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 20) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.13</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 21) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.14</a></li>
                    <li><a class="nav-link" href="{{ route('akreditasi.standar.detail', 22) }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Standar 3.15</a></li>

                </ul>   
            </li>

        </ul>              
    </div>
</div>
<!-- end left-sidenav-->
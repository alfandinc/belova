<!-- Left Sidenav -->
<div class="left-sidenav">
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
        <a href="/laporan" class="logo">
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
            <li class="menu-label mt-0">Main</li>
            @hasanyrole('Admin|Finance|Manager|Hrd')
            
            <li>
                <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="/laporan"><i class="ti-control-record"></i>Analytics</a></li>
                </ul>
            </li>
            @endhasanyrole
            @hasanyrole('Admin|Finance|Manager|Hrd')
            <li>
                <a class="nav-link" href="/laporan/farmasi">
                    <i data-feather="activity" class="align-self-center menu-icon"></i>
                    <span>Laporan Farmasi</span>
                </a>
            </li>
            @endhasanyrole
            @hasanyrole('Admin|Manager|Hrd')
            <li>
                <a class="nav-link" href="/laporan/laboratorium">
                    <i data-feather="file-text" class="align-self-center menu-icon"></i>
                    <span>Laporan Laboratorium</span>
                </a>
            </li>
            @endhasanyrole
            @hasanyrole('Admin|Manager|Hrd')
            <li>
                <a href="javascript:void(0);"> <i data-feather="users" class="align-self-center menu-icon"></i><span>Laporan HRD</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="/laporan/hrd/rekap-kehadiran"><i class="ti-control-record"></i>Rekap Kehadiran</a></li>
                </ul>
            </li>
            @endhasanyrole
        </ul>
    </div>
</div>
<!-- end left-sidenav-->
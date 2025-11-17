<!-- JobList-specific Left Sidenav (copied/adapted from HRD navbar) -->
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
        <a href="/hrd" class="logo">
            <span>
                <img src="{{ $lightLogo }}" alt="logo" class="logo-light" style="width: auto; height: {{ $logoHeight }};">
                <img src="{{ $darkLogo }}" alt="logo" class="logo-dark" style="width: auto; height: {{ $logoHeight }};">
            </span>
        </a>
    </div>
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li class="menu-label mt-0">Main</li>

            <li>
                <a href="/hrd"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li>

            <li>
                <a href="javascript: void(0);"> <i data-feather="check-square" class="align-self-center menu-icon"></i><span>Job List</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.joblist.index') }}"><i class="ti-control-record"></i>Daftar Job</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.joblist.index') }}?view=create"><i class="ti-control-record"></i>Buat Job Baru</a></li>
                </ul>
            </li>

        </ul>
    </div>
</div>

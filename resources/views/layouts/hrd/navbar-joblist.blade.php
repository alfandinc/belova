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

            {{-- <li>
                <a href="/hrd"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li> --}}

            <li class="menu-label mt-0">Job List</li>
            <li>
                <a href="{{ route('hrd.joblist.dashboard') }}"> <i data-feather="grid" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li>
            <li>
                <a href="{{ route('hrd.joblist.index') }}"> <i data-feather="check-square" class="align-self-center menu-icon"></i><span>Daftar Job</span></a>
            </li>

        </ul>
    </div>
</div>

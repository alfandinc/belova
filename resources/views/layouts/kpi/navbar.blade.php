<!-- Left Sidenav -->
<div class="left-sidenav">
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
        <a href="{{ route('indicator.index') }}" class="logo">
            <span>
                <img src="{{ $lightLogo }}" alt="logo" class="logo-light" style="width: auto; height: {{ $logoHeight }};">
                <img src="{{ $darkLogo }}" alt="logo" class="logo-dark" style="width: auto; height: {{ $logoHeight }};">
            </span>
        </a>
    </div>

    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li class="menu-label mt-0">KPI Module</li>

            <li>
                <a href="{{ url('/') }}">
                    <i data-feather="grid" class="align-self-center menu-icon"></i>
                    <span>Main Menu</span>
                </a>
            </li>

            @hasanyrole('Admin|Hrd|Ceo|Head Manager')
            <li class="{{ request()->routeIs('indicator.index') ? 'mm-active' : '' }}">
                <a href="{{ route('indicator.index') }}" class="{{ request()->routeIs('indicator.index') ? 'active' : '' }}">
                    <i data-feather="sliders" class="align-self-center menu-icon"></i>
                    <span>Master Indicators</span>
                </a>
            </li>
            @endhasanyrole

            <li>
                <a href="javascript: void(0);">
                    <i data-feather="target" class="align-self-center menu-icon"></i>
                    <span>Assessment</span>
                    <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                </a>
                <ul class="nav-second-level" aria-expanded="false">
                    @hasanyrole('Admin|Hrd|Ceo|Head Manager')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('indicator.index') ? 'active' : '' }}" href="{{ route('indicator.index') }}">
                            <i class="ti-control-record"></i>Master Indikator
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('kpi.periods.*') ? 'active' : '' }}" href="{{ route('kpi.periods.index') }}">
                            <i class="ti-control-record"></i>Periods
                        </a>
                    </li>
                    @endhasanyrole
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('kpi.evaluatees.*') ? 'active' : '' }}" href="{{ route('kpi.evaluatees.index') }}">
                            <i class="ti-control-record"></i>My Evaluations
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<!-- CEO Dashboard Left Sidenav -->
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
                    $lightLogo = asset('img/logo-belova-klinik-bw.png');
                    $darkLogo = asset('img/logo-belova-klinik.png');
                    $logoHeight = '70px';
                }
            @endphp

                <div class="brand mt-3">
                <a href="/ceo-dashboard" class="logo">
                    <span>
                        <img src="{{ $lightLogo }}" alt="logo" class="logo-light" style="width: auto; height: {{ $logoHeight }};">
                        <img src="{{ $darkLogo }}" alt="logo" class="logo-dark" style="width: auto; height: {{ $logoHeight }};">
                    </span>
                </a>
            </div>

            <div class="menu-content h-100" data-simplebar>
                @php
                    $isClinicMenuOpen = request()->routeIs('ceo-dashboard.premiere_belova.index')
                        || request()->routeIs('ceo-dashboard.belova_skin.index')
                        || request()->routeIs('ceo-dashboard.belova_dental.index');
                @endphp
                <ul class="metismenu left-sidenav-menu">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('ceo-dashboard.index') }}"><i data-feather="home" class="align-self-center menu-icon"></i><span>CEO Dashboard</span></a>
                    </li>
                    <li>
                        <a href="{{ route('ceo-dashboard.daily-tasks.index') }}"><i data-feather="check-square" class="align-self-center menu-icon"></i><span>Daily Task Report</span></a>
                    </li>

                    <li class="{{ $isClinicMenuOpen ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);">
                            <i data-feather="grid" class="align-self-center menu-icon"></i>
                            <span>Klinik</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="{{ $isClinicMenuOpen ? 'true' : 'false' }}">
                            <li>
                                <a href="{{ route('ceo-dashboard.premiere_belova.index') }}"><i data-feather="award" class="align-self-center menu-icon"></i>Premiere Belova</a>
                            </li>
                            <li>
                                <a href="{{ route('ceo-dashboard.belova_skin.index') }}"><i data-feather="layers" class="align-self-center menu-icon"></i>Belova Skin</a>
                            </li>
                            <li>
                                <a href="{{ route('ceo-dashboard.belova_dental.index') }}"><i data-feather="shield" class="align-self-center menu-icon"></i>Belova Dental</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Reports (placeholder for future) -->
                    <li>
                        <a href="/ceo-dashboard/reports"><i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Reports</span></a>
                    </li>

                </ul>
            </div>
        </div>
        <!-- end left-sidenav-->

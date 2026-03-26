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
                <ul class="metismenu left-sidenav-menu">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('ceo-dashboard.index') }}"><i data-feather="home" class="align-self-center menu-icon"></i><span>CEO Dashboard</span></a>
                    </li>

                    <!-- Statistik Dokter -->
                    <li>
                        <a href="{{ route('ceo-dashboard.dokter.index') }}"><i data-feather="user" class="align-self-center menu-icon"></i><span>Statistik Dokter</span></a>
                    </li>

                    <li>
                        <a href="{{ route('ceo-dashboard.daily-tasks.index') }}"><i data-feather="check-square" class="align-self-center menu-icon"></i><span>Daily Task Report</span></a>
                    </li>

                    <li>
                        <a href="{{ route('ceo-dashboard.premiere_belova.index') }}"><i data-feather="award" class="align-self-center menu-icon"></i><span>Premiere Belova</span></a>
                    </li>

                    <!-- Reports (placeholder for future) -->
                    <li>
                        <a href="/ceo-dashboard/reports"><i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Reports</span></a>
                    </li>

                </ul>
            </div>
        </div>
        <!-- end left-sidenav-->

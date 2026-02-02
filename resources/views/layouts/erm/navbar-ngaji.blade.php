<!-- Left Sidenav -->
        <div class="left-sidenav ">
            <!-- LOGO -->
            @php
                // Use session clinic_choice to determine logo
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
                <a href="/erm" class="logo">
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
                                        <!-- SPK / Riwayat Tindakan Section -->
                    @hasanyrole('Employee|Ustad|Admin')
                    <!-- Top-level Events Dashboard (no parent) - placed above Belova Mengaji section -->
                    <li>
                        <a href="/events"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Events Dashboard</span></a>
                    </li>

                    <li>
                        <a href="javascript: void(0);"><i data-feather="file-text" class="align-self-center menu-icon"></i><span>Belova Mengaji</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/belova-mengaji"><i data-feather="list" class="align-self-center menu-icon"></i><span>Penilaian</span></a>
                            </li>
                            <li>
                                <a href="/belova-mengaji/analytics"><i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Analytics</span></a>
                            </li>
                        </ul>
                    </li>

                    <!-- New Running section (separate from Belova Mengaji) -->
                    <li>
                        <a href="javascript: void(0);"><i data-feather="activity" class="align-self-center menu-icon"></i><span>Running</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/running"><i data-feather="list" class="align-self-center menu-icon"></i><span>Index</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasanyrole                  




                   
                </ul>
            </div>
        </div>
        <!-- end left-sidenav-->
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
                    
                    {{-- @php
                        $role = Auth::user()->getRoleNames()->first();
                        $colorClass = match($role) {
                            'Admin' => 'bg-primary',
                            'Dokter' => 'bg-success',
                            'Perawat' => 'bg-info',
                            'Lab' => 'bg-warning',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <li class="menu-label mt-0">
                        <span class="text-white px-2 py-1 rounded {{ $colorClass }}" style="font-size: 1.2rem;">
                        ERM {{ $role }}
                        </span>
                    </li> --}}

                    @hasrole('Dokter|Admin')
                    <li class="menu-label mt-0">Dashboard</li>
                    <li>
                        <a href="/erm"><i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Analytics</span></a>
                    </li>
                    @endhasrole

                    @hasanyrole('Pendaftaran|Perawat|Farmasi|Admin|Beautician|Lab')
                    <li class="menu-label">Pendaftaran</li>
                    <li>
                        <a href="/erm/pasiens/create"><i data-feather="user-plus" class="align-self-center menu-icon"></i><span>Pasien Baru</span></a>
                    </li>
                    <li>
                        <a href="/erm/pasiens"><i data-feather="users" class="align-self-center menu-icon"></i><span>Data Pasien</span></a>
                    </li>
                    @endhasanyrole

                    @hasanyrole('Dokter|Perawat|Admin')
                    <li class="menu-label">Rawat Jalan</li>
                    <li>
                        <a href="/erm/rawatjalans"><i data-feather="clipboard" class="align-self-center menu-icon"></i><span>Kunjungan Rajal</span></a>
                    </li>
                    <li>
                        <a href="/erm/listantrian"><i data-feather="list" class="align-self-center menu-icon"></i><span>Antrian Rajal</span></a>
                    </li>
                    @endhasanyrole

                    <li class="menu-label">Lainnya</li>
                </ul>
            </div>
        </div>
        <!-- end left-sidenav-->
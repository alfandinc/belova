<!-- SatuSehat Left Sidenav (copied from ERM navbar and adapted) -->
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
                <a href="/satusehat" class="logo">
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
                        <a href="/satusehat"><i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
                    </li>

                    <!-- Integration -->
                    @hasanyrole('Satusehat|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="server" class="align-self-center menu-icon"></i><span>Integrasi</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="/satusehat/integrasi"><i data-feather="download" class="align-self-center menu-icon"></i><span>Sinkronisasi</span></a></li>
                            <li><a href="/satusehat/mappings"><i data-feather="layers" class="align-self-center menu-icon"></i><span>Mapping Data</span></a></li>
                        </ul>
                    </li>
                    @endhasanyrole

                    <!-- Pasien SatuSehat -->
                    @hasanyrole('Satusehat|Admin|Pendaftaran')
                    <li>
                        <a href="/satusehat/pasiens"><i data-feather="users" class="align-self-center menu-icon"></i><span>Pasien SatuSehat</span></a>
                    </li>
                    @endhasanyrole

                    <!-- Klinik Configs -->
                    @hasanyrole('Satusehat|Admin')
                    <li>
                        <a href="/satusehat/clinics"><i data-feather="database" class="align-self-center menu-icon"></i><span>Konfigurasi Klinik</span></a>
                    </li>
                    @endhasanyrole

                    <!-- Logs & Settings -->
                    @hasrole('Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="file-text" class="align-self-center menu-icon"></i><span>Logs & Pengaturan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li><a href="/satusehat/logs"><i data-feather="list" class="align-self-center menu-icon"></i><span>Sync Logs</span></a></li>
                            <li><a href="/satusehat/settings"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Pengaturan</span></a></li>
                        </ul>
                    </li>
                    @endhasrole
                </ul>
            </div>
        </div>
        <!-- end left-sidenav--> 

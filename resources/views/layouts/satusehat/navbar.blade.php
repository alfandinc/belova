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
                                        <!-- Klinik Configs -->
                    @hasanyrole('Satusehat|Admin')
                    <li>
                        <a href="/satusehat/clinics"><i data-feather="database" class="align-self-center menu-icon"></i><span>Konfigurasi Klinik</span></a>
                    </li>
                    @endhasanyrole

                    <!-- Pasien SatuSehat -->
                    @hasanyrole('Satusehat|Admin|Pendaftaran')
                    <li>
                        <a href="/satusehat/pasiens"><i data-feather="users" class="align-self-center menu-icon"></i><span>Pasien SatuSehat</span></a>
                    </li>
                    @endhasanyrole

                    @hasanyrole('Satusehat|Admin|Pendaftaran|Farmasi|Beautician|Lab|Finance|Dokter|Perawat')
                    <li>
                        <a href="/erm/obat-kfa"><i data-feather="link" class="align-self-center menu-icon"></i><span>Obat KFA Mapping</span></a>
                    </li>
                    @endhasanyrole


                </ul>
            </div>
        </div>
        <!-- end left-sidenav--> 

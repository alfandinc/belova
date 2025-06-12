        <!-- Left Sidenav -->
        <div class="left-sidenav ">
            <!-- LOGO -->
            @php
                // Fetch the klinik_id associated with the logged-in user
                $klinikId = Auth::user()->dokter->klinik_id ?? null;

                // Define logo paths based on klinik_id
                if ($klinikId === 1) {
                    $lightLogo = asset('img/logo-premiere-bw.png');
                    $darkLogo = asset('img/logo-premiere.png');
                    $logoHeight = '50px'; // Height for klinik_id = 1
                } elseif ($klinikId === 2) {
                    $lightLogo = asset('img/logo-belovaskin-bw.png');
                    $darkLogo = asset('img/logo-belovaskin.png');
                    $logoHeight = '50px'; // Height for klinik_id = 2
                } else {
                    // Default logos if klinik_id is not 1 or 2
                    $lightLogo = asset('img/logo-belova-klinik-bw.png');
                    $darkLogo = asset('img/logo-belova-klinik.png');
                    $logoHeight = '70px'; // Default height
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
                    @php
                        $role = Auth::user()->getRoleNames()->first();
                        $colorClass = match($role) {
                            'Admin' => 'bg-primary',
                            'Dokter' => 'bg-success',
                            'Perawat' => 'bg-info',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <li class="menu-label mt-0">
                        <span class="text-white px-2 py-1 rounded {{ $colorClass }}" style="font-size: 1.2rem;">
                        ERM {{ $role }}
                        </span>
                    </li>


                    {{-- Dashboard - only for Dokter --}}
                    @hasrole('Dokter|Admin')
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm"><i class="ti-control-record"></i>Analytics</a></li>
                        </ul>
                    </li>
                    @endhasrole

                    {{-- Pendaftaran - for roles: pendaftaran, perawat, farmasi --}}
                    @hasanyrole('Pendaftaran|Perawat|Farmasi|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Pendaftaran</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm/pasiens/create"><i class="ti-control-record"></i>Pasien Baru</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/pasiens"><i class="ti-control-record"></i>Data Pasien</a></li>
                        </ul>
                    </li>
                    @endhasanyrole

                    {{-- Rawat Jalan - for roles: dokter, perawat --}}
                    @hasanyrole('Dokter|Perawat|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Rawat Jalan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm/rawatjalans"><i class="ti-control-record"></i>Kunjungan Rajal</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/listantrian"><i class="ti-control-record"></i>Antrian Rajal</a></li>
                        </ul>
                    </li>
                    @endhasanyrole

                    {{-- Farmasi - only for farmasi --}}
                    @hasrole('Farmasi|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Farmasi</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm/eresepfarmasi"><i class="ti-control-record"></i>E-Resep Farmasi</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/obat"><i class="ti-control-record"></i>Stok Obat</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/obat/create"><i class="ti-control-record"></i>Tambah Obat</a></li>
                        </ul>
                    </li>
                    @endhasrole

       
                </ul>              
            </div>
        </div>
        <!-- end left-sidenav-->
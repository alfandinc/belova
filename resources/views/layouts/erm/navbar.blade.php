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
                    
                    @php
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
                    </li>

                    <!-- Dashboard Section -->
                    @hasrole('Dokter|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/erm"><i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Analytics</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasrole

                    <!-- Pendaftaran Section -->
                    @hasanyrole('Pendaftaran|Perawat|Farmasi|Admin|Beautician|Lab')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="user" class="align-self-center menu-icon"></i><span>Pendaftaran</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/erm/pasiens/create"><i data-feather="user-plus" class="align-self-center menu-icon"></i><span>Pasien Baru</span></a>
                            </li>
                            <li>
                                <a href="/erm/pasiens"><i data-feather="users" class="align-self-center menu-icon"></i><span>Data Pasien</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasanyrole

                    <!-- Rawat Jalan Section -->
                    @hasanyrole('Dokter|Perawat|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="activity" class="align-self-center menu-icon"></i><span>Rawat Jalan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/erm/rawatjalans"><i data-feather="clipboard" class="align-self-center menu-icon"></i><span>Kunjungan Rajal</span></a>
                            </li>
                            <li>
                                <a href="/erm/listantrian"><i data-feather="list" class="align-self-center menu-icon"></i><span>Antrian Rajal</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasanyrole

                    <!-- Farmasi Section -->
                    @hasrole('Farmasi|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="shopping-bag" class="align-self-center menu-icon"></i><span>Farmasi</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            
                            <li>
                                <a href="/erm/eresepfarmasi"><i data-feather="file-text" class="align-self-center menu-icon"></i><span>E-Resep Farmasi</span></a>
                            </li>
                            <li>
                                <a href="/erm/statistic"><i data-feather="activity" class="align-self-center menu-icon"></i><span>Statistik Farmasi</span></a>
                            </li>
                            <li>
                                <a href="/erm/fakturpembelian"><i data-feather="file" class="align-self-center menu-icon"></i><span>Faktur Pembelian</span></a>
                            </li>
                            <li>
                                <a href="/erm/fakturpembelian/create"><i data-feather="plus-square" class="align-self-center menu-icon"></i><span> Tambah Faktur Pembelian</span></a>
                            </li>
                            <li>
                                <a href="/erm/obat"><i data-feather="package" class="align-self-center menu-icon"></i><span>Stok Obat</span></a>
                            </li>
                            <li>
                                <a href="/erm/obat/create"><i data-feather="plus-square" class="align-self-center menu-icon"></i><span>Tambah Obat</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasrole

                    <!-- SPK / Riwayat Tindakan Section -->
                    @hasanyrole('Lab|Perawat|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="file-text" class="align-self-center menu-icon"></i><span>Laboratorium</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/erm/elab"><i data-feather="list" class="align-self-center menu-icon"></i><span>Kunjungan Lab</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasanyrole

                    <!-- SPK / Riwayat Tindakan Section -->
                    @hasanyrole('Beautician|Perawat|Admin')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="file-text" class="align-self-center menu-icon"></i><span>SPK Tindakan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/erm/spk"><i data-feather="list" class="align-self-center menu-icon"></i><span>Daftar SPK Tindakan</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasanyrole

                    <!-- Other Section -->
                    <li>
                        <a href="javascript: void(0);"><i data-feather="more-horizontal" class="align-self-center menu-icon"></i><span>Lainnya</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/erm/birthday"><i data-feather="gift" class="align-self-center menu-icon"></i><span>Ulang Tahun Pasien</span></a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <!-- end left-sidenav-->
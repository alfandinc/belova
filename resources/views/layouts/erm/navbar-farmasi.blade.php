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
                } elseif ($clinicChoice === 'dental') {
                    $lightLogo = asset('img/logo-dental.png');
                    $darkLogo = asset('img/logo-dental.png');
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

                    <!-- Farmasi Section -->
                    @hasanyrole('Farmasi|Admin')
                        <li>
                            <a href="javascript: void(0);"><i data-feather="database" class="align-self-center menu-icon"></i><span>Master Data</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                            <ul class="nav-second-level" aria-expanded="false">
                                <li>
                                    <a href="/erm/obat"><i data-feather="package" class="align-self-center menu-icon"></i><span>Master Obat & BHP</span></a>
                                </li>
                                <li>
                                <a href="/erm/masterfaktur"><i data-feather="shopping-cart" class="align-self-center menu-icon"></i><span>Master Pembelian</span></a>
                                </li>
                                <li>
                                    <a href="{{ route('erm.paket-racikan.index') }}"><i data-feather="layers" class="align-self-center menu-icon"></i><span>Paket Racikan</span></a>
                                </li>
                                
                            </ul>
                        </li>
                    @endhasanyrole
                    @hasanyrole('Farmasi|Admin')    
                    <li>
                        <a href="/erm/eresepfarmasi"><i data-feather="file-text" class="align-self-center menu-icon"></i><span>E-Resep</span></a>
                    </li>
                    @endhasanyrole
                    @hasanyrole('Farmasi|Admin|Beautician|Lab')
                    <li>
                        <a href="javascript: void(0);"><i data-feather="shopping-cart" class="align-self-center menu-icon"></i><span>Pembelian</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            @hasanyrole('Farmasi|Admin')
                            <li>
                                <a href="/erm/permintaan"><i data-feather="inbox" class="align-self-center menu-icon"></i><span>Permintaan Pembelian</span></a>
                            </li>
                            <li>
                                <a href="/erm/fakturpembelian"><i data-feather="file" class="align-self-center menu-icon"></i><span>Faktur Pembelian</span></a>
                            </li>
                            @endhasanyrole
                            <li>
                                <a href="/erm/obat-hibah"><i data-feather="gift" class="align-self-center menu-icon"></i><span>Obat Hibah</span></a>
                            </li>
                            @hasanyrole('Farmasi|Admin')
                            <li>
                                <a href="/erm/fakturretur"><i data-feather="rotate-ccw" class="align-self-center menu-icon"></i><span>Retur ke Supplier</span></a>
                            </li>
                            @endhasanyrole
                            <li>
                                <a href="{{ route('erm.obat.forecast.index') }}"><i data-feather="trending-up" class="align-self-center menu-icon"></i><span>Forecast Stok</span></a>
                            </li>
                            @hasanyrole('Farmasi|Admin')
                            <li>
                                <a href="{{ route('erm.laporan-pembelian.index') }}"><i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Laporan Pembelian</span></a>
                            </li>
                            @endhasanyrole
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);"><i data-feather="archive" class="align-self-center menu-icon"></i><span>Stok</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="/erm/stok-gudang"><i data-feather="database" class="align-self-center menu-icon"></i><span>Manajemen Stok</span></a>
                            </li>
                            <li>
                                <a href="/erm/mutasi-gudang"><i data-feather="repeat" class="align-self-center menu-icon"></i><span>Transfer Antar Gudang</span></a>
                            </li>
                            <li>
                                <a href="/erm/mutasi-stok"><i data-feather="shuffle" class="align-self-center menu-icon"></i><span>Penyesuaian Stok</span></a>
                            </li>
                            <li>
                                <a href="/erm/stokopname"><i data-feather="refresh-cw" class="align-self-center menu-icon"></i><span>Stok Opname</span></a>
                            </li>
                        </ul>
                    </li>
                    @endhasanyrole
                    @hasanyrole('Farmasi|Admin|Finance')
                    @php($hargaMenunggu = \App\Models\ERM\ObatHargaPengajuan::where('status', 'menunggu')->count())
                    <li>
                        <a href="{{ route('erm.harga-jual.index') }}"><i data-feather="tag" class="align-self-center menu-icon"></i><span>Harga Jual Obat</span>@if($hargaMenunggu)<span class="badge badge-warning ml-auto">{{ $hargaMenunggu }}</span>@endif</a>
                    </li>
                    @endhasanyrole
                </ul>
            </div>
        </div>
        <!-- end left-sidenav-->
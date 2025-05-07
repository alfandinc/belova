        <!-- Left Sidenav -->
        <div class="left-sidenav ">
            <!-- LOGO -->
            <div class="brand mt-3">
                <a href="/erm" class="logo">
                    <span>
                        <!-- Light-theme logo (for dark background) -->
                        <img src="{{ asset('img/logo-premiere-bw.png')}}" alt="logo" class="logo-light" style="width: auto; height: 50px;">

                        <!-- Dark-theme logo (for light background) -->
                        <img src="{{ asset('img/logo-premiere.png')}}" alt="logo" class="logo-dark" style="width: auto; height: 50px;">
                        {{-- <img src="{{ asset('img/logo-premiere-bw.png')}}" alt="logo-small" class="logo-sm " style="width: auto; height: 50px;"> --}}
                    </span>
                </a>
            </div>
            <!--end logo-->
            <div class="menu-content h-100" data-simplebar>
                <ul class="metismenu left-sidenav-menu">
                    <li class="menu-label mt-0">Main</li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm"><i class="ti-control-record"></i>Analytics</a></li>
                        </ul>
                    </li>
    
                    <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Pendaftaran Pasien</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm/pasiens/create"><i class="ti-control-record"></i>Tambah Pasien</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/visitations"><i class="ti-control-record"></i>Daftarkan Kunjungan</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/pasiens"><i class="ti-control-record"></i>Manajemen Pasien</a></li>

                        </ul>
                    </li>
    
                    <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Rawat Jalan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm/rawatjalans"><i class="ti-control-record"></i>Kunjungan Rajal</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/rawatjalan/antrian"><i class="ti-control-record"></i>Antrian Rajal</a></li>
                        </ul>
                    </li>   
                    <li>
                        <a href="javascript:void(0);" data-toggle="modal" data-target="#modalResume">
                            <i data-feather="file-text" class="align-self-center menu-icon"></i>
                            <span>Resume Medis</span>
                        </a>
                    </li>  
       
                </ul>              
            </div>
        </div>
        <!-- end left-sidenav-->
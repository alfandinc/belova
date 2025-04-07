        <!-- Left Sidenav -->
        <div class="left-sidenav ">
            <!-- LOGO -->
            <div class="brand">
                <a href="/" class="logo">
                    <span>
                        <img src="{{ asset('img/logo-premiere.png')}}" alt="logo-small" class="logo-sm " style="width: auto; height: 50px;">
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
                            <li class="nav-item"><a class="nav-link" href="/erm/pasiens/create"><i class="ti-control-record"></i>Add Pasien Baru</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/visitations"><i class="ti-control-record"></i>Daftarkan Kunjungan</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/pasiens"><i class="ti-control-record"></i>Manajemen Pasien</a></li>

                        </ul>
                    </li>
    
                    <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Rawat Jalan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/erm/rawatjalans"><i class="ti-control-record"></i>Daftar Rawat Jalan</a></li>
                            <li class="nav-item"><a class="nav-link" href="/erm/rawatjalan/antrian"><i class="ti-control-record"></i>Antrian Pasien</a></li>
                        </ul>
                    </li> 
                    {{-- <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Rawat Inap</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="auth-login.html"><i class="ti-control-record"></i>Daftar Rawat Inap</a></li>
                            <li class="nav-item"><a class="nav-link" href="auth-login.html"><i class="ti-control-record"></i>Antrian Pasien</a></li>
                        </ul>
                    </li> 
                    <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Resume Medis</span></a>
                        
                    </li>  --}}
                    {{-- <li>
                        <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Laboratorium</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="auth-login.html"><i class="ti-control-record"></i>Daftar Lab</a></li>
                            <li class="nav-item"><a class="nav-link" href="auth-login.html"><i class="ti-control-record"></i>Dokumen Lab</a></li>
                        </ul>
                    </li>  --}}
    
                    <hr class="hr-dashed hr-menu">
                    <li class="menu-label my-2">Lain-Lain</li>
    
                    <li>
                        <a href="javascript: void(0);"><i data-feather="box" class="align-self-center menu-icon"></i><span>Pengaturan Akun</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li>
                                <a href="javascript: void(0);"><i class="ti-control-record"></i>UI Elements <span class="menu-arrow left-has-menu"><i class="mdi mdi-chevron-right"></i></span></a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="ui-alerts.html">Alerts</a></li>                                    
                                    <li><a href="ui-avatar.html">Avatar</a></li>
                                    <li><a href="ui-buttons.html">Buttons</a></li>
                                    <li><a href="ui-badges.html">Badges</a></li>
                                    <li><a href="ui-cards.html">Cards</a></li>
                                    <li><a href="ui-carousels.html">Carousels</a></li>
                                    <li><a href="ui-check-radio.html"><span>Check & Radio</span></a></li>
                                    <li><a href="ui-dropdowns.html">Dropdowns</a></li>                                   
                                    <li><a href="ui-grids.html">Grids</a></li> 
                                    <li><a href="ui-images.html">Images</a></li>
                                    <li><a href="ui-list.html">List</a></li>                                   
                                    <li><a href="ui-modals.html">Modals</a></li>
                                    <li><a href="ui-navs.html">Navs</a></li>
                                    <li><a href="ui-navbar.html">Navbar</a></li> 
                                    <li><a href="ui-paginations.html">Paginations</a></li>   
                                    <li><a href="ui-popover-tooltips.html">Popover & Tooltips</a></li>                                
                                    <li><a href="ui-progress.html">Progress</a></li>
                                    <li><a href="ui-spinners.html">Spinners</a></li>
                                    <li><a href="ui-tabs-accordions.html">Tabs & Accordions</a></li>
                                    <li><a href="ui-toasts.html">Toasts</a></li>
                                    <li><a href="ui-typography.html">Typography</a></li>
                                    <li><a href="ui-videos.html">Videos</a></li>
                                </ul>
                            </li>  
  
                        </ul>                        
                    </li>
    
           
                </ul>
    
                
            </div>
        </div>
        <!-- end left-sidenav-->
        <!-- Left Sidenav -->
        <div class="left-sidenav ">
            <!-- LOGO -->
            <div class="brand mt-3">
                <a href="/inventory" class="logo">
                    <span>
                        <!-- Light-theme logo (for dark background) -->
                        <img src="{{ asset('img/logo-belovacorp-bw.png')}}" alt="logo" class="logo-light" style="width: auto; height: 50px;">

                        <!-- Dark-theme logo (for light background) -->
                        <img src="{{ asset('img/logo-belovacorp.png')}}" alt="logo" class="logo-dark" style="width: auto; height: 50px;">
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
                            <li class="nav-item"><a class="nav-link" href="/inventory"><i class="ti-control-record"></i>Analytics</a></li>
                        </ul>
                    </li>
                    
                    <li class="menu-label mt-0">Master Inventory</li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="layers" class="align-self-center menu-icon"></i><span>Gedung</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/gedung"><i class="ti-control-record"></i>List Gedung</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="grid" class="align-self-center menu-icon"></i><span>Ruangan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/ruangan"><i class="ti-control-record"></i>List Ruangan</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="tag" class="align-self-center menu-icon"></i><span>Tipe Barang</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/tipe-barang"><i class="ti-control-record"></i>List Tipe Barang</a></li>
                        </ul>
                    </li>
                    
                    <li class="menu-label mt-0">Manajemen Barang</li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="box" class="align-self-center menu-icon"></i><span>Barang</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/barang"><i class="ti-control-record"></i>List Barang</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="shopping-cart" class="align-self-center menu-icon"></i><span>Pembelian</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/pembelian"><i class="ti-control-record"></i>List Pembelian</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="tool" class="align-self-center menu-icon"></i><span>Maintenance</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/maintenance"><i class="ti-control-record"></i>List Maintenance</a></li>
                        </ul>
                    </li>
                    
                    <li class="menu-label mt-0">Legacy</li>
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="archive" class="align-self-center menu-icon"></i><span>Items</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="/inventory/item"><i class="ti-control-record"></i>List Items</a></li>
                            <li class="nav-item"><a class="nav-link" href="/inventory/item/create"><i class="ti-control-record"></i>Add Items</a></li>
                        </ul>
                    </li>
                </ul>              
            </div>
        </div>
        <!-- end left-sidenav-->
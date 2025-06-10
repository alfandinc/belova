<!-- Left Sidenav -->
<div class="left-sidenav">
    <!-- LOGO -->
    <div class="brand mt-3">
        <a href="/hrd" class="logo">
            <span>
                <!-- Light-theme logo (for dark background) -->
                <img src="{{ asset('img/logo-belovacorp-bw.png')}}" alt="logo" class="logo-light" style="width: auto; height: 50px;">

                <!-- Dark-theme logo (for light background) -->
                <img src="{{ asset('img/logo-belovacorp.png')}}" alt="logo" class="logo-dark" style="width: auto; height: 50px;">
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
                    <li class="nav-item"><a class="nav-link" href="/hrd"><i class="ti-control-record"></i>Analytics</a></li>
                </ul>
            </li>
            
            <!-- Self Service - Visible to all authenticated users -->
            <li>
                <a href="javascript: void(0);"> <i data-feather="user" class="align-self-center menu-icon"></i><span>Profil Saya</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.employee.profile') }}"><i class="ti-control-record"></i>Lihat Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.employee.profile.edit') }}"><i class="ti-control-record"></i>Edit Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.employee.password.change') }}"><i class="ti-control-record"></i>Ubah Password</a></li>
                </ul>
            </li>
            
            <!-- Performance Evaluations - Visible to all authenticated users -->
            <li>
                <a href="javascript: void(0);"> <i data-feather="award" class="align-self-center menu-icon"></i><span>Penilaian Kinerja</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.my-evaluations') }}"><i class="ti-control-record"></i>Evaluasi Saya</a></li>
                    
                    <!-- For Managers: Team Evaluations -->
                    @if(Auth::check() && Auth::user()->hasRole('Manager'))
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.my-evaluations') }}"><i class="ti-control-record"></i>Evaluasi Tim</a></li>
                    @endif
                    
                    <!-- For HRD and CEO: Full Performance Management -->
                    @if(Auth::check() && (Auth::user()->hasRole('Hrd') || Auth::user()->hasRole('Ceo')))
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.periods.index') }}"><i class="ti-control-record"></i>Periode Penilaian</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.questions.index') }}"><i class="ti-control-record"></i>Kelola Pertanyaan</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.results.index') }}"><i class="ti-control-record"></i>Hasil Penilaian</a></li>
                    @endif
                </ul>
            </li>
            
            <!-- For Managers: Team Management -->
            @if(Auth::check() && Auth::user()->hasRole('Manager'))
            <li>
                <a href="javascript: void(0);"> <i data-feather="users" class="align-self-center menu-icon"></i><span>Divisi Saya</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.division.mine') }}"><i class="ti-control-record"></i>Informasi Divisi</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.division.team') }}"><i class="ti-control-record"></i>Anggota Tim</a></li>
                </ul>
            </li>
            @endif
            
            <!-- For HRD and CEO: Employee Management -->
            @if(Auth::check() && (Auth::user()->hasRole('Hrd') || Auth::user()->hasRole('Ceo')))
            <li>
                <a href="javascript: void(0);"> <i data-feather="users" class="align-self-center menu-icon"></i><span>Kepegawaian</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.employee.create') }}"><i class="ti-control-record"></i>Tambah Pegawai Baru</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.employee.index') }}"><i class="ti-control-record"></i>Data Pegawai</a></li>
                </ul>
            </li>
            
            <!-- For HRD and CEO: Division and Position Management -->
            <li>
                <a href="javascript: void(0);"> <i data-feather="briefcase" class="align-self-center menu-icon"></i><span>Master Data</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Divisi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Posisi/Jabatan</a></li>
                </ul>
            </li>
            
            <!-- For HRD and CEO: Reports -->
            <li>
                <a href="javascript: void(0);"> <i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Laporan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Statistik Pegawai</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Kontrak Berakhir</a></li>
                </ul>
            </li>
            @endif
        </ul>              
    </div>
</div>
<!-- end left-sidenav-->
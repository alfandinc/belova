<!-- Left Sidenav -->
<div class="left-sidenav">
    <!-- LOGO -->
    <div class="brand mt-3 text-center">
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
    <!-- end logo -->

    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li>
                <a href="{{ route('erm.rawatjalans.index', $visitation->id) }}" target="_blank">
                    <i data-feather="home" class="align-self-center menu-icon"></i>
                    <span>Daftar Rawat Jalan</span>
                </a>
            </li>
            <li>
                <a href="{{ route('erm.asesmendokter.create', $visitation->id) }}" target="_blank">
                    <i data-feather="file-text" class="align-self-center menu-icon"></i>
                    <span>Asesmen</span>
                </a>
            </li>
            <li>
                <a href="{{ route('erm.cppt.create', $visitation->id) }}" target="_blank">
                    <i data-feather="file-text" class="align-self-center menu-icon"></i>
                    <span>CPPT</span>
                </a>
            </li>

            <li>
                <a href="{{ route('erm.eresep.create', $visitation->id) }}" target="_blank">
                    <i data-feather="file-text" class="align-self-center menu-icon"></i>
                    <span>E-Resep</span>
                </a>
            </li>

            <li>
                <a href="{{ route('erm.elab.create', $visitation->id) }}" target="_blank">
                    <i data-feather="file-text" class="align-self-center menu-icon"></i>
                    <span>E-Lab</span>
                </a>
            </li>

            <li>
                <a href="{{ route('erm.eradiologi.create', $visitation->id) }}" target="_blank">
                    <i data-feather="file-text" class="align-self-center menu-icon"></i>
                    <span>E-Radiologi</span>
                </a>
            </li>

            <li>
                <a href="{{ route('erm.tindakan.create', $visitation->id) }}" target="_blank">
                    <i data-feather="file-text" class="align-self-center menu-icon"></i>
                    <span>Tindakan & Inform Consent</span>
                </a>
            </li>

            <li>
                <a href="{{ route('erm.riwayatkunjungan.index', $visitation->pasien_id) }}" target="_blank">
                    <i data-feather="book-open" class="align-self-center menu-icon"></i>
                    <span>Riwayat Kunjungan</span>
                </a>
            </li>

            <li>
                <a href="javascript:void(0);" data-toggle="modal" data-target="#modalIstirahat">
                    <i data-feather="coffee" class="align-self-center menu-icon"></i>
                    <span>Surat Istirahat</span>
                </a>
            </li>

            <li>
                <a href="javascript:void(0);" data-toggle="modal" data-target="#modalMondok">
                    <i data-feather="home" class="align-self-center menu-icon"></i>
                    <span>Surat Mondok</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" data-toggle="modal" data-target="#modalKunjungan">
                    <i data-feather="calendar" class="align-self-center menu-icon"></i>
                    <span>Jadwalkan Kunjungan</span>
                </a>
            </li>

        </ul>
    </div>
</div>
<!-- end left-sidenav -->

@include('erm.partials.modal-daftarkunjungan')

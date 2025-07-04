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
                        </span>                    </li>
            
            @php
                $userRole = Auth::user()->getRoleNames()->first();
                $statusDokumen = $visitation->status_dokumen; // adjust if the status comes from somewhere else
                // dd($userRole, $statusDokumen);
            @endphp
            
            @if($userRole !== 'Lab')
                <li>
                    <a href="{{ route('erm.rawatjalans.index', $visitation->id) }}">
                        <i data-feather="clipboard" class="align-self-center menu-icon"></i>
                        <span>Daftar Rawat Jalan</span>
                    </a>
                </li>
            
                @if($userRole === 'Dokter')
                    @if($statusDokumen === 'asesmen')
                        <li>
                            <a href="{{ route('erm.asesmendokter.create', $visitation->id) }}" target="_blank">
                                <i data-feather="clipboard" class="align-self-center menu-icon"></i>
                                <span>Asesmen</span>
                            </a>
                        </li>
                    @elseif($statusDokumen === 'cppt')
                        <li>
                            <a href="{{ route('erm.cppt.create', $visitation->id) }}" target="_blank">
                                <i data-feather="edit-3" class="align-self-center menu-icon"></i>
                                <span>CPPT</span>
                            </a>
                        </li>
                    @endif
                @elseif($userRole === 'Perawat')
                    {{-- Show both buttons for perawat --}}
                    <li>
                        <a href="{{ route('erm.asesmenperawat.create', $visitation->id) }}" target="_blank">
                            <i data-feather="clipboard" class="align-self-center menu-icon"></i>
                            <span>Asesmen</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('erm.cppt.create', $visitation->id) }}" target="_blank">
                            <i data-feather="edit-3" class="align-self-center menu-icon"></i>
                            <span>CPPT</span>
                        </a>
                    </li>
                @endif

                @if($userRole !== 'Perawat')
                    <li>
                        <a href="{{ route('erm.eresep.create', $visitation->id) }}" target="_blank">
                            <i data-feather="package" class="align-self-center menu-icon"></i>
                            <span>E-Resep</span>
                        </a>
                    </li>
                @endif
            @endif

            <li>
                <a href="{{ route('erm.elab.create', $visitation->id) }}" target="_blank">
                    <i data-feather="activity" class="align-self-center menu-icon"></i>
                    <span>E-Lab</span>
                </a>
            </li>

            @if($userRole !== 'Lab')
                <li>
                    <a href="{{ route('erm.eradiologi.create', $visitation->id) }}" target="_blank">
                        <i data-feather="image" class="align-self-center menu-icon"></i>
                        <span>E-Radiologi</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('erm.tindakan.create', $visitation->id) }}" target="_blank">
                        <i data-feather="check-square" class="align-self-center menu-icon"></i>
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
                    <a href="{{ route('erm.suratistirahat.index', $visitation->pasien_id) }}" target="_blank">
                        <i data-feather="file-text" class="align-self-center menu-icon"></i>
                        <span>Surat Istirahat & Mondok</span>
                    </a>
                </li>

                {{-- <li>
                    <a href="javascript:void(0);" data-toggle="modal" data-target="#modalMondok">
                        <i data-feather="home" class="align-self-center menu-icon"></i>
                        <span>Surat Mondok</span>
                    </a>
                </li> --}}
                <li>
                    <a href="javascript:void(0);"
                    class="btn-daftar-visitation"
                    data-id="{{ $visitation->pasien_id }}"
                    data-nama="{{ $visitation->pasien->nama }}"
                    data-klinik="{{ $visitation->klinik_id ?? '' }}"
                    data-dokter="{{ $visitation->dokter_id ?? '' }}"
                    data-metodebayar="{{ $visitation->metode_bayar_id ?? '' }}">
                        <i data-feather="calendar" class="align-self-center menu-icon"></i>
                        <span>Daftar Kunjungan</span>
                    </a>
                </li>
                {{-- <li>
                    <a href="javascript:void(0);" data-toggle="modal" data-target="#modalKunjungan">
                        <i data-feather="calendar" class="align-self-center menu-icon"></i>
                        <span>Jadwalkan Kunjungan</span>
                    </a>
                </li> --}}
            @endif
        </ul>
    </div>
</div>
<!-- end left-sidenav -->

@include('erm.partials.modal-daftarkunjungan')
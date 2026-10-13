{{-- ERM visit actions, shown on top of the unified sidebar on visit pages (needs $visitation). --}}
<li class="menu-label mt-0 belova-context-label">Kunjungan Pasien</li>
@php
    $user = Auth::user();
    $statusDokumen = $visitation->status_dokumen; // adjust if the status comes from somewhere else
@endphp

<li>
    <a href="{{ route('erm.rawatjalans.index') }}">
        <i data-feather="clipboard" class="align-self-center menu-icon"></i>
        <span>Daftar Rawat Jalan</span>
    </a>
</li>

@if(!$user->hasRole('Lab'))
    @if($user->hasRole('Dokter'))
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
    @elseif($user->hasRole('Perawat'))
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

    @if(!$user->hasRole('Perawat'))
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

@if(!$user->hasRole('Lab'))
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
        <a href="{{ route('erm.slimming.create', $visitation->id) }}" target="_blank">
            <i data-feather="sliders" class="align-self-center menu-icon"></i>
            <span>Slimming</span>
        </a>
    </li>

    <li>
        <a href="{{ route('erm.riwayatkunjungan.index', $visitation->pasien_id) }}" target="_blank">
            <i data-feather="book-open" class="align-self-center menu-icon"></i>
            <span>Riwayat Kunjungan</span>
        </a>
    </li>

    <li>
        <a href="{{ route('erm.suratistirahat.index', ['pasien_id' => $visitation->pasien_id, 'visitation_id' => $visitation->id]) }}" target="_blank">
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
        class="btn-daftarkan-pasien-rawatjalan"
        data-jenis="konsultasi"
        data-id="{{ $visitation->pasien_id }}"
        data-nama="{{ $visitation->pasien->nama }}"
        data-klinik="{{ $visitation->klinik_id ?? '' }}"
        data-dokter="{{ $visitation->dokter_id ?? '' }}"
        data-metodebayar="{{ $visitation->metode_bayar_id ?? '' }}">
            <i data-feather="calendar" class="align-self-center menu-icon"></i>
            <span>Reservasi Kunjungan</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0);"
        class="btn-rujuk"
        data-id="{{ $visitation->pasien_id }}"
        data-nama="{{ $visitation->pasien->nama }}"
        data-norm="{{ $visitation->pasien->id }}"
        data-tgllahir="{{ $visitation->pasien->tanggal_lahir ?? '' }}"
        data-dokter="{{ $visitation->dokter_id ?? '' }}"
        data-metodebayar="{{ $visitation->metode_bayar_id ?? '' }}">
            <i data-feather="share" class="align-self-center menu-icon"></i>
            <span>Rujuk / Konsultasi</span>
        </a>
    </li>
    {{-- <li>
        <a href="javascript:void(0);" data-toggle="modal" data-target="#modalKunjungan">
            <i data-feather="calendar" class="align-self-center menu-icon"></i>
            <span>Jadwalkan Kunjungan</span>
        </a>
    </li> --}}
@endif

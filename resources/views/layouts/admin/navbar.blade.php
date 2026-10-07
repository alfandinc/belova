<!-- Admin Left Sidenav -->
<div class="left-sidenav">
    <div class="brand mt-3">
        <a href="/admin" class="logo">
            <span>
                <img src="{{ asset('img/logo-belova-klinik.png') }}" alt="logo" class="logo-dark" style="height:48px;">
            </span>
        </a>
    </div>
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li class="menu-label mt-0">Admin</li>

            <li>
                <a href="/admin"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li>

            <li class="menu-label">User Management</li>
            <li>
                <a href="{{ route('admin.users.index') }}"><i data-feather="users" class="align-self-center menu-icon"></i><span>Users & Roles</span></a>
            </li>

            <li class="menu-label">SatuSehat</li>
            <li>
                <a href="{{ route('satusehat.pasiens.index') }}"><i data-feather="users" class="align-self-center menu-icon"></i><span>Pasien SatuSehat</span></a>
            </li>
            <li>
                <a href="/satusehat/obat-kfa"><i data-feather="link" class="align-self-center menu-icon"></i><span>Obat KFA Mapping</span></a>
            </li>
            <li>
                <a href="{{ route('satusehat.dokter_mapping.index') }}"><i data-feather="link-2" class="align-self-center menu-icon"></i><span>Mapping Dokter</span></a>
            </li>

            <li class="menu-label">Others</li>
            <li>
                <a href="{{ route('admin.klinik_settings.index') }}"><i data-feather="home" class="align-self-center menu-icon"></i><span>Klinik Setting</span></a>
            </li>
            <li>
                <a href="{{ route('admin.obat_gudang_mapping.index') }}"><i data-feather="shuffle" class="align-self-center menu-icon"></i><span>Obat & Gudang Mapping</span></a>
            </li>
            <li>
                <a href="{{ route('admin.icd10.index') }}"><i data-feather="book-open" class="align-self-center menu-icon"></i><span>Manage ICD 10</span></a>
            </li>
            <li>
                <a href="/admin/settings"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Settings</span></a>
            </li>
        </ul>
    </div>
</div>
<!-- end left-sidenav -->

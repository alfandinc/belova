<div class="left-sidenav">
    <div class="brand mt-3">
        <a href="{{ route('rnd.dashboard') }}" class="logo">
            <span>
                <img src="{{ asset('img/logo-belova-klinik.png') }}" alt="logo" class="logo-dark" style="height:48px;">
            </span>
        </a>
    </div>
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li class="menu-label mt-0">RND</li>
            <li>
                <a href="{{ route('rnd.dashboard') }}"><i data-feather="database" class="align-self-center menu-icon"></i><span>Data Master</span></a>
            </li>
            <li>
                <a href="{{ route('rnd.products.index') }}"><i data-feather="package" class="align-self-center menu-icon"></i><span>Produk</span></a>
            </li>
            <li class="menu-label">Master</li>
            <li><a href="{{ route('rnd.dashboard') }}#master-brand"><i data-feather="tag" class="align-self-center menu-icon"></i><span>Brand</span></a></li>
            <li><a href="{{ route('rnd.dashboard') }}#master-kemasan"><i data-feather="package" class="align-self-center menu-icon"></i><span>Kemasan</span></a></li>
            <li><a href="{{ route('rnd.dashboard') }}#master-sediaan"><i data-feather="layers" class="align-self-center menu-icon"></i><span>Sediaan</span></a></li>
            <li><a href="{{ route('rnd.dashboard') }}#master-vendor"><i data-feather="truck" class="align-self-center menu-icon"></i><span>Vendor</span></a></li>
            <li><a href="{{ route('rnd.dashboard') }}#master-bahan-aktif"><i data-feather="droplet" class="align-self-center menu-icon"></i><span>Bahan Aktif</span></a></li>
        </ul>
    </div>
</div>
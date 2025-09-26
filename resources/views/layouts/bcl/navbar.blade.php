<style>
    /* Scoped styles to make BCL left navbar hot pink and keep good contrast */
    .left-sidenav {
        background: #cf0973 !important; /* hotpink */
        color: #ffffff;
    }
    .left-sidenav .metismenu .menu-label,
    .left-sidenav .metismenu a span,
    .left-sidenav .metismenu a {
        color: #fff !important;
    }
    /* Ensure all kinds of icons (feather SVGs, font icons like mdi/ti) render white */
    .left-sidenav .metismenu a .menu-icon,
    .left-sidenav .metismenu a .menu-icon svg,
    .left-sidenav .metismenu a .menu-icon i,
    .left-sidenav .metismenu a i,
    .left-sidenav .metismenu .nav-item .nav-link i,
    .left-sidenav .metismenu .menu-arrow i {
        color: #ffffff !important;
        fill: #ffffff !important;
        stroke: #ffffff !important;
        opacity: 1 !important;
    }
    /* Some SVGs have nested paths/circles/rects with explicit fills/strokes */
    .left-sidenav .metismenu a .menu-icon svg path,
    .left-sidenav .metismenu a .menu-icon svg circle,
    .left-sidenav .metismenu a .menu-icon svg rect {
        fill: #ffffff !important;
        stroke: #ffffff !important;
    }
    /* subtle divider color */
    .left-sidenav .hr-dashed {
        border-top-color: rgba(255,255,255,0.12) !important;
    }
    /* make the logo area match the background */
    .left-sidenav .nav-logo img {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.25));
    }
</style>

<div class="left-sidenav">
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
        
        <li class="nav-logo mb-3">
            <a href="{{ route('bcl.dashboard') }}">
                <img src="{{ asset('img/logobcl-white.png') }}" alt="Logo" class="img-fluid" style="max-height:80px; display:block; margin:0.5rem auto;" />
            </a>
        </li>
        <li class="menu-label mt-0">Main</li>
        <li>
            <a href="{{ route('bcl.dashboard') }}"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Home</span></a>
        </li>
        <li>
            <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Kamar</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.rooms')}}"><i class="ti-control-record"></i>Daftar Kamar</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.pricelist.index')}}"><i class="ti-control-record"></i>Daftar Harga</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.category.index')}}"><i class="ti-control-record"></i>Daftar Kategori</a></li>
            </ul>
        </li>

        <li>
            <a href="javascript: void(0);"><i data-feather="users" class="align-self-center menu-icon"></i><span>Penyewa</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.renter.index')}}"><i class="ti-control-record"></i>Daftar Penyewa</a></li>
            </ul>
        </li>
        <li>
            <a href="javascript: void(0);"><i data-feather="credit-card" class="align-self-center menu-icon"></i><span>Transaksi</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.transaksi.index')}}"><i class="ti-control-record"></i>Transaksi Sewa</a></li>
            </ul>
        </li>

        <hr class="hr-dashed hr-menu">
        <li class="menu-label my-2">Inventaris</li>
        <li>
            <a href="javascript: void(0);"><i data-feather="monitor" class="align-self-center menu-icon"></i><span>Inventaris</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.inventories.index')}}"><i class="ti-control-record"></i>Daftar Inventaris</a></li>
            </ul>
        </li>

        <hr class="hr-dashed hr-menu">
        <li class="menu-label my-2">Keuangan</li>
        <li>
            <a href="javascript: void(0);"><i data-feather="book" class="align-self-center menu-icon"></i><span>Keuangan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.income.index')}}"><i class="ti-control-record"></i>Pemasukan</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.expense.index')}}"><i class="ti-control-record"></i>Pengeluaran</a></li>
            </ul>
        </li>

        {{-- <hr class="hr-dashed hr-menu">
        <li class="menu-label my-2">Account</li>
        <li>
            <a href="javascript: void(0);"><i data-feather="lock" class="align-self-center menu-icon"></i><span>User Account</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.users.index')}}"><i class="ti-control-record"></i>Daftar Pengguna</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.roles.index')}}"><i class="ti-control-record"></i>User Permission</a></li>
            </ul>
        </li> --}}



        </ul>

        <!-- <div class="update-msg text-center">
            <h5 class="mt-3">Warning</h5>
            <p class="mb-3">H</p>
            <a href="javascript: void(0);" class="btn btn-outline-warning btn-sm">Upgrade your plan</a>
        </div> -->
    </div>
    <!-- end left-sidenav-->
</div>
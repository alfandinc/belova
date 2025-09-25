<div class="menu-content h-100" data-simplebar>
    <ul class="metismenu left-sidenav-menu">
        <li class="menu-label mt-0">Main</li>
        <li>
            <a href="{{ route('home') }}"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Home</span></a>
        </li>
        @hasrole('Keuangan|Admin Kamar|Administrator')
        <li>
            <a href="javascript: void(0);"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Kamar</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('rooms')}}"><i class="ti-control-record"></i>Daftar Kamar</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('bcl.pricelist.index')}}"><i class="ti-control-record"></i>Daftar Harga</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('category')}}"><i class="ti-control-record"></i>Daftar Kategori</a></li>
            </ul>
        </li>
        @endhasrole
        @hasrole('Keuangan|Admin Kamar|Administrator')
        <li>
            <a href="javascript: void(0);"><i data-feather="users" class="align-self-center menu-icon"></i><span>Penyewa</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('renter.index')}}"><i class="ti-control-record"></i>Daftar Penyewa</a></li>
            </ul>
        </li>
        <li>
            <a href="javascript: void(0);"><i data-feather="credit-card" class="align-self-center menu-icon"></i><span>Transaksi</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('transaksi.index')}}"><i class="ti-control-record"></i>Transaksi Sewa</a></li>
            </ul>
        </li>
        @endhasrole
        @hasrole('Keuangan|Admin Kamar|Administrator')
        <hr class="hr-dashed hr-menu">
        <li class="menu-label my-2">Inventaris</li>
        <li>
            <a href="javascript: void(0);"><i data-feather="monitor" class="align-self-center menu-icon"></i><span>Inventaris</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('inventories.index')}}"><i class="ti-control-record"></i>Daftar Inventaris</a></li>
            </ul>
        </li>
        @endhasrole
        @hasrole('Keuangan|Admin Kamar|Administrator')
        <hr class="hr-dashed hr-menu">
        <li class="menu-label my-2">Keuangan</li>
        <li>
            <a href="javascript: void(0);"><i data-feather="book" class="align-self-center menu-icon"></i><span>Keuangan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('income.index')}}"><i class="ti-control-record"></i>Pemasukan</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('expense.index')}}"><i class="ti-control-record"></i>Pengeluaran</a></li>
            </ul>
        </li>
        @endhasrole
        @hasrole('Administrator')
        <hr class="hr-dashed hr-menu">
        <li class="menu-label my-2">Account</li>
        <li>
            <a href="javascript: void(0);"><i data-feather="lock" class="align-self-center menu-icon"></i><span>User Account</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <li class="nav-item"><a class="nav-link" href="{{route('users.index')}}"><i class="ti-control-record"></i>Daftar Pengguna</a></li>
                <li class="nav-item"><a class="nav-link" href="{{route('roles.index')}}"><i class="ti-control-record"></i>User Permission</a></li>
            </ul>
        </li>
        @endhasrole


    </ul>

    <!-- <div class="update-msg text-center">
        <h5 class="mt-3">Warning</h5>
        <p class="mb-3">H</p>
        <a href="javascript: void(0);" class="btn btn-outline-warning btn-sm">Upgrade your plan</a>
    </div> -->
</div>
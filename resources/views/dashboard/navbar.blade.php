<!-- Dashboard Left Sidenav (custom for /dashboard) -->
        <div class="left-sidenav">
            <div class="brand mt-3">
                <a href="/dashboard" class="logo">
                    <span>
                        <img src="{{ asset('img/logo-favicon-belova.png') }}" alt="logo" class="logo-light" style="width: auto; height: 48px;">
                    </span>
                </a>
            </div>

            <div class="menu-content h-100" data-simplebar>
                <ul class="metismenu left-sidenav-menu">
                    <li class="{{ request()->routeIs('dashboard.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('dashboard.index') }}"><i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
                    </li>
                    <li class="{{ request()->routeIs('dashboard.widgets.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('dashboard.widgets.index') }}"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Widgets</span></a>
                    </li>
                    <li class="{{ request()->routeIs('dashboard.settings') ? 'mm-active' : '' }}">
                        <a href="#settings"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Settings</span></a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- end left-sidenav -->

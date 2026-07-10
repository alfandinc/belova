<!-- Dashboard Left Sidenav (custom for /dashboard) -->
    @php
        $canManageDashboardAdmin = auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Ceo', 'CEO', 'Head Manager']);
        $isCeoDashboardRoute = request()->routeIs('ceo-dashboard.*');
        $dashboardHomeRoute = $isCeoDashboardRoute ? route('ceo-dashboard.index') : route('dashboard.index');
        $dashboardHomeLabel = $isCeoDashboardRoute ? 'CEO Dashboard' : 'Dashboard';
        $isClinicMenuOpen = request()->routeIs('ceo-dashboard.premiere_belova.index')
            || request()->routeIs('ceo-dashboard.belova_skin.index')
            || request()->routeIs('ceo-dashboard.belova_dental.index');
    @endphp
        <div class="left-sidenav">
            <div class="brand mt-3">
                <a href="{{ $dashboardHomeRoute }}" class="logo">
                    <span>
                        <img src="{{ asset('img/logo-favicon-belova.png') }}" alt="logo" class="logo-light" style="width: auto; height: 48px;">
                    </span>
                </a>
            </div>

            <div class="menu-content h-100" data-simplebar>
                <ul class="metismenu left-sidenav-menu">
                    <li class="{{ request()->routeIs('dashboard.index') || request()->routeIs('ceo-dashboard.index') ? 'mm-active' : '' }}">
                        <a href="{{ $dashboardHomeRoute }}"><i data-feather="home" class="align-self-center menu-icon"></i><span>{{ $dashboardHomeLabel }}</span></a>
                    </li>
                    @if($isCeoDashboardRoute)
                        <li class="{{ request()->routeIs('ceo-dashboard.daily-tasks.index') ? 'mm-active' : '' }}">
                            <a href="{{ route('ceo-dashboard.daily-tasks.index') }}"><i data-feather="check-square" class="align-self-center menu-icon"></i><span>Daily Task Report</span></a>
                        </li>
                        <li class="{{ $isClinicMenuOpen ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);">
                                <i data-feather="grid" class="align-self-center menu-icon"></i>
                                <span>Klinik</span>
                                <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                            </a>
                            <ul class="nav-second-level" aria-expanded="{{ $isClinicMenuOpen ? 'true' : 'false' }}">
                                <li>
                                    <a href="{{ route('ceo-dashboard.premiere_belova.index') }}"><i data-feather="award" class="align-self-center menu-icon"></i>Premiere Belova</a>
                                </li>
                                <li>
                                    <a href="{{ route('ceo-dashboard.belova_skin.index') }}"><i data-feather="layers" class="align-self-center menu-icon"></i>Belova Skin</a>
                                </li>
                                <li>
                                    <a href="{{ route('ceo-dashboard.belova_dental.index') }}"><i data-feather="shield" class="align-self-center menu-icon"></i>Belova Dental</a>
                                </li>
                            </ul>
                        </li>
                        <li class="{{ request()->routeIs('ceo-dashboard.bcl.index') ? 'mm-active' : '' }}">
                            <a href="{{ route('ceo-dashboard.bcl.index') }}"><i data-feather="briefcase" class="align-self-center menu-icon"></i><span>Belova Center Living</span></a>
                        </li>
                    @endif
                    @if($canManageDashboardAdmin)
                        <li class="{{ request()->routeIs('dashboard.widgets.*') ? 'mm-active' : '' }}">
                            <a href="{{ route('dashboard.widgets.index') }}"><i data-feather="grid" class="align-self-center menu-icon"></i><span>Widgets</span></a>
                        </li>
                        <li class="{{ request()->routeIs('dashboard.settings') ? 'mm-active' : '' }}">
                            <a href="#settings"><i data-feather="settings" class="align-self-center menu-icon"></i><span>Settings</span></a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        <!-- end left-sidenav -->

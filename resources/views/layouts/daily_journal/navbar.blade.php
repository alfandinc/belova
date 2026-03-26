<div class="left-sidenav">
    @php
        $clinicChoice = session('clinic_choice');
        if ($clinicChoice === 'premiere') {
            $lightLogo = asset('img/logo-premiere-bw.png');
            $darkLogo = asset('img/logo-premiere.png');
            $logoHeight = '50px';
        } elseif ($clinicChoice === 'skin') {
            $lightLogo = asset('img/logo-belovaskin-bw.png');
            $darkLogo = asset('img/logo-belovaskin.png');
            $logoHeight = '50px';
        } else {
            $lightLogo = asset('img/logo-belovacorp-bw.png');
            $darkLogo = asset('img/logo-belovacorp.png');
            $logoHeight = '50px';
        }

        $activeFilter = request('filter', 'today');
        $todayDate = now()->toDateString();
        $currentRouteName = request()->route()?->getName();
        $isDivisionPage = $currentRouteName === 'daily-journal.division.index';
        $baseJournalRoute = $isDivisionPage ? 'daily-journal.division.index' : 'daily-journal.index';
        $baseJournalParams = [
            'filter' => $activeFilter,
            'date' => request('date', $todayDate),
        ];

        if ($isDivisionPage) {
            $baseJournalParams['user_id'] = request('user_id');
            $baseJournalParams['start_date'] = request('start_date', $todayDate);
            $baseJournalParams['end_date'] = request('end_date', $todayDate);
        }
    @endphp

    <div class="brand mt-3">
        <a href="{{ route('daily-journal.index') }}" class="logo">
            <span>
                <img src="{{ $lightLogo }}" alt="logo" class="logo-light" style="width: auto; height: {{ $logoHeight }};">
                <img src="{{ $darkLogo }}" alt="logo" class="logo-dark" style="width: auto; height: {{ $logoHeight }};">
            </span>
        </a>
    </div>

    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            <li class="menu-label mt-0">Index Pages</li>

            <li>
                <a href="{{ route('daily-journal.index') }}" class="{{ $currentRouteName === 'daily-journal.index' ? 'active' : '' }}">
                    <i data-feather="book-open" class="align-self-center menu-icon"></i>
                    <span>My Daily Journal</span>
                </a>
            </li>

            @if(Auth::user()?->hasRole('Manager'))
                <li>
                    <a href="{{ route('daily-journal.division.index') }}" class="{{ $isDivisionPage ? 'active' : '' }}">
                        <i data-feather="users" class="align-self-center menu-icon"></i>
                        <span>Division Daily Journal</span>
                    </a>
                </li>
            @endif

            {{-- <li class="menu-label mt-0">Navigation</li>

            <li>
                <a href="/">
                    <i data-feather="home" class="align-self-center menu-icon"></i>
                    <span>Main Menu</span>
                </a>
            </li> --}}

            {{-- <li class="menu-label mt-0">Quick Filter</li>

            <li>
                <a href="{{ route($baseJournalRoute, array_filter(array_merge($baseJournalParams, ['filter' => 'today', 'date' => $todayDate]), fn ($value) => $value !== null && $value !== '')) }}" class="{{ $activeFilter === 'today' ? 'active' : '' }}">
                    <i data-feather="sun" class="align-self-center menu-icon"></i>
                    <span>Today</span>
                </a>
            </li>
            <li>
                <a href="{{ route($baseJournalRoute, array_filter(array_merge($baseJournalParams, ['filter' => 'week', 'date' => $todayDate]), fn ($value) => $value !== null && $value !== '')) }}" class="{{ $activeFilter === 'week' ? 'active' : '' }}">
                    <i data-feather="calendar" class="align-self-center menu-icon"></i>
                    <span>This Week</span>
                </a>
            </li>
            <li>
                <a href="{{ route($baseJournalRoute, array_filter(array_merge($baseJournalParams, ['filter' => 'month', 'date' => $todayDate]), fn ($value) => $value !== null && $value !== '')) }}" class="{{ $activeFilter === 'month' ? 'active' : '' }}">
                    <i data-feather="grid" class="align-self-center menu-icon"></i>
                    <span>This Month</span>
                </a>
            </li>
            <li>
                <a href="{{ route($baseJournalRoute, array_filter(array_merge($baseJournalParams, ['filter' => 'year', 'date' => $todayDate]), fn ($value) => $value !== null && $value !== '')) }}" class="{{ $activeFilter === 'year' ? 'active' : '' }}">
                    <i data-feather="archive" class="align-self-center menu-icon"></i>
                    <span>This Year</span>
                </a>
            </li>
            <li>
                <a href="{{ route($baseJournalRoute, array_filter(array_merge($baseJournalParams, ['filter' => 'custom', 'date' => request('date', $todayDate), 'start_date' => request('start_date', $todayDate), 'end_date' => request('end_date', $todayDate)]), fn ($value) => $value !== null && $value !== '')) }}" class="{{ $activeFilter === 'custom' ? 'active' : '' }}">
                    <i data-feather="sliders" class="align-self-center menu-icon"></i>
                    <span>Custom Range</span>
                </a>
            </li> --}}
        </ul>
    </div>
</div>
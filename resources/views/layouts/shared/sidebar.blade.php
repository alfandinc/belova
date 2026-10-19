{{--
    Unified left sidebar for every module (replaces the old main menu and per-module navbars).
    Menu structure lives in App\Support\Navigation\MainNavigation.
    Optional: $sidebarContextView = a view with its own <li> items (e.g. ERM visit actions). When given, the sidebar
    shows only that menu: the main menu (and its search) is left out so the two never mix.
--}}
@php
    $mainNavSections = empty($sidebarContextView) ? \App\Support\Navigation\MainNavigation::forUser(auth()->user()) : [];
    $mainNavHasPayroll = collect($mainNavSections)->flatMap(fn ($s) => $s['modules'])
        ->flatMap(fn ($m) => $m['items'] ?? [])
        ->contains(fn ($i) => str_contains($i['attrs']['onclick'] ?? '', 'check'));

    $clinicChoice = session('clinic_choice');
    if ($clinicChoice === 'premiere') {
        $sidebarLightLogo = asset('img/logo-premiere-bw.png');
        $sidebarDarkLogo = asset('img/logo-premiere.png');
    } elseif ($clinicChoice === 'skin') {
        $sidebarLightLogo = asset('img/logo-belovaskin-bw.png');
        $sidebarDarkLogo = asset('img/logo-belovaskin.png');
    } elseif ($clinicChoice === 'dental') {
        $sidebarLightLogo = asset('img/logo-dental.png');
        $sidebarDarkLogo = asset('img/logo-dental.png');
    } else {
        $sidebarLightLogo = asset('img/logo-belovacorp-bw.png');
        $sidebarDarkLogo = asset('img/logo-belovacorp.png');
    }

    $renderNavBadges = function (array $badges) {
        return collect($badges)->map(function ($badge) {
            return '<span class="badge badge-pill badge-' . e($badge['class']) . ' belova-nav-badge ' . e($badge['extra'] ?? '') . '"'
                . (!empty($badge['title']) ? ' title="' . e($badge['title']) . '"' : '') . '>' . (int) $badge['count'] . '</span>';
        })->implode('');
    };
    $renderNavAttrs = function (array $attrs) {
        return collect($attrs)->except('class')->map(fn ($value, $key) => e($key) . '="' . e($value) . '"')->implode(' ');
    };
@endphp

<link rel="stylesheet" href="{{ asset('css/sidebar.css') }}?v={{ filemtime(public_path('css/sidebar.css')) }}">

<div class="left-sidenav belova-sidenav" id="belovaSidenav"
     style="{{ \App\Support\ClinicTheme::cssVars('sb') }}">
    <div class="brand">
        <a href="{{ url('/') }}" class="logo" title="Dashboard">
            <span>
                <img src="{{ $sidebarLightLogo }}" alt="logo" class="logo-light">
                <img src="{{ $sidebarDarkLogo }}" alt="logo" class="logo-dark">
            </span>
        </a>
        <span class="brand-accent"></span>
    </div>

    @if(empty($sidebarContextView))
        <div class="belova-nav-search">
            <div class="input-wrap">
                <i data-feather="search" class="search-icon"></i>
                <input type="search" id="belovaNavSearch" placeholder="Cari menu..." autocomplete="off" aria-label="Cari menu">
                <kbd>/</kbd>
            </div>
        </div>
    @endif

    <div class="menu-content h-100" data-simplebar>
        {{-- Menu markup is buffered and its indentation collapsed: ~45KB less HTML on every page --}}
        @php ob_start(); @endphp
        <ul class="metismenu left-sidenav-menu" id="belovaMainMenu">
            @if(!empty($sidebarContextView))
                @include($sidebarContextView)
            @endif

            @foreach($mainNavSections as $navSection)
                @if(!empty($navSection['label']))
                    <li class="menu-label {{ $loop->first ? 'mt-0' : '' }}" data-nav-section>{{ $navSection['label'] }}</li>
                @endif

                @foreach($navSection['modules'] as $navModule)
                    @if(isset($navModule['items']))
                        <li class="{{ !empty($navModule['active']) ? 'mm-active' : '' }}" data-nav-module data-nav-label="{{ strtolower($navModule['label']) }}">
                            <a href="javascript: void(0);" aria-expanded="{{ !empty($navModule['active']) ? 'true' : 'false' }}">
                                <i data-feather="{{ $navModule['icon'] }}" class="align-self-center menu-icon"></i>
                                <span>{{ $navModule['label'] }}</span>
                                {!! $renderNavBadges($navModule['badges']) !!}
                                <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                            </a>
                            <ul class="nav-second-level {{ !empty($navModule['active']) ? 'mm-show' : '' }}" aria-expanded="{{ !empty($navModule['active']) ? 'true' : 'false' }}">
                                @foreach($navModule['items'] as $navItem)
                                    @if(isset($navItem['heading']))
                                        <li class="nav-sub-label" data-nav-heading>{{ $navItem['heading'] }}</li>
                                    @else
                                        <li class="nav-item {{ !empty($navItem['active']) ? 'active' : '' }}" data-nav-item data-nav-label="{{ strtolower($navItem['label']) }}">
                                            <a class="nav-link {{ !empty($navItem['active']) ? 'active' : '' }} {{ $navItem['attrs']['class'] ?? '' }}" href="{{ $navItem['url'] }}" {!! $renderNavAttrs($navItem['attrs']) !!}>
                                                <i data-feather="{{ $navItem['icon'] ?? 'circle' }}" class="belova-sub-icon"></i>{{ $navItem['label'] }}{!! $renderNavBadges($navItem['badges']) !!}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="belova-nav-link {{ !empty($navModule['active']) ? 'mm-active' : '' }}" data-nav-module data-nav-label="{{ strtolower($navModule['label']) }}">
                            <a href="{{ $navModule['url'] }}" class="{{ !empty($navModule['active']) ? 'active' : '' }} {{ $navModule['attrs']['class'] ?? '' }}" {!! $renderNavAttrs($navModule['attrs']) !!}>
                                <i data-feather="{{ $navModule['icon'] }}" class="align-self-center menu-icon"></i>
                                <span>{{ $navModule['label'] }}</span>
                                {!! $renderNavBadges($navModule['badges']) !!}
                            </a>
                        </li>
                    @endif
                @endforeach
            @endforeach
        </ul>
        @php echo preg_replace('/>\s+</', '> <', ob_get_clean()); @endphp
        <div class="belova-nav-empty" id="belovaNavEmpty">Menu tidak ditemukan.</div>
    </div>
</div>

<script>
// Sidebar colours key off html.theme-dark / theme-light; not every layout sets them, so mirror the active theme stylesheet
(function () {
    var link = document.getElementById('app-dark'), html = document.documentElement;
    if (!link) return;
    function sync() {
        var dark = !link.disabled && link.media !== 'not all';
        html.classList.toggle('theme-dark', dark);
        html.classList.toggle('theme-light', !dark);
    }
    sync();
    if (window.MutationObserver) new MutationObserver(sync).observe(link, { attributes: true, attributeFilter: ['disabled', 'media'] });
})();
</script>

@if($mainNavHasPayroll)
    @include('partials.payroll_password_modal')
@endif

@include('partials.daily_journal_reminder')

<script>window.BelovaSidebarConfig = {!! json_encode(['journalModalUrl' => route('daily-journal.modal', ['v' => \App\Http\Controllers\DailyJournalController::modalVersion()]), 'routes' => ['jadwal.viewer' => route('jadwal.viewer'), 'daily-journal.index' => route('daily-journal.index')]], 15) !!};</script>
<script src="{{ asset('js/belova-sidebar.js') }}?v={{ filemtime(public_path('js/belova-sidebar.js')) }}"></script>

{{--
    Theme stylesheets (dark / light). The theme is read from the "theme" cookie so the right CSS is in the HTML
    and loads (render-blocking) before first paint. Previously the light CSS was <link disabled> and only enabled
    in DOMContentLoaded, so light-theme users saw an unstyled page (open chat panel, raw menus) while it downloaded.

    The inactive theme is media="not all" (not disabled): it still downloads (low priority, non-blocking) and stays
    parsed, so switching is just a media flip. Toggling `disabled` made the browser drop and re-apply the sheet,
    leaving the page unstyled for a moment (sidebar blown up full-width) on every switch.
--}}
@php
    $belovaTheme = request()->cookie('theme') === 'light' ? 'light' : 'dark';
    $belovaDarkMedia = $belovaTheme === 'dark' ? 'all' : 'not all';
    $belovaLightMedia = $belovaTheme === 'light' ? 'all' : 'not all';
@endphp
<link id="bootstrap-dark" href="{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}" rel="stylesheet" media="{{ $belovaDarkMedia }}" />
<link id="app-dark" href="{{ asset('dastone/default/assets/css/app-dark.min.css') }}" rel="stylesheet" media="{{ $belovaDarkMedia }}" />
<link id="bootstrap-light" href="{{ asset('dastone/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" media="{{ $belovaLightMedia }}" />
<link id="app-light" href="{{ asset('dastone/default/assets/css/app.min.css') }}" rel="stylesheet" media="{{ $belovaLightMedia }}" />
<style>html.belova-theme-pending body { visibility: hidden !important; }</style>
<script>
    (function () {
        var serverTheme = @json($belovaTheme);
        var saved = serverTheme;
        try { saved = localStorage.getItem('theme') === 'light' ? 'light' : (localStorage.getItem('theme') ? 'dark' : serverTheme); } catch (e) {}

        window.belovaSetThemeCookie = function (theme) {
            document.cookie = 'theme=' + (theme === 'light' ? 'light' : 'dark') + '; path=/; max-age=31536000; SameSite=Lax';
        };
        window.belovaSetThemeCookie(saved);

        // Show one theme's sheets and hide the other's (see the media note above; never toggle `disabled`)
        window.belovaSetThemeSheets = function (isDark) {
            [['bootstrap-dark', isDark], ['app-dark', isDark], ['bootstrap-light', !isDark], ['app-light', !isDark]].forEach(function (pair) {
                var link = document.getElementById(pair[0]);
                if (!link) return;
                link.disabled = false;
                link.media = pair[1] ? 'all' : 'not all';
            });
        };

        if (saved === serverTheme) return;

        // Cookie missing/outdated (first visit after this change): swap now and keep the page hidden until the CSS is in
        var html = document.documentElement;
        var pending = 2;
        function done() { if (--pending <= 0) html.classList.remove('belova-theme-pending'); }

        html.classList.add('belova-theme-pending');
        (saved === 'dark' ? ['bootstrap-dark', 'app-dark'] : ['bootstrap-light', 'app-light']).forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { done(); return; }
            link.addEventListener('load', done);
            link.addEventListener('error', done);
        });
        window.belovaSetThemeSheets(saved === 'dark');
        setTimeout(function () { html.classList.remove('belova-theme-pending'); }, 4000);
    })();
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Daily Journal')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Daily Journal Module" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="shortcut icon" href="{{ asset('img/logo-favicon-belova.png') }}">

    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        const savedJournalPattern = localStorage.getItem('dailyJournalPattern') || 'clear';
        document.documentElement.classList.add(savedTheme === 'light' ? 'theme-light' : 'theme-dark');
        document.documentElement.dataset.journalPattern = savedJournalPattern;
        document.documentElement.classList.add('no-transition');
    </script>

    <link href="{{ asset('dastone/default/assets/css/icons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/default/assets/css/metisMenu.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.css') }}" rel="stylesheet" />
    <link id="bootstrap-dark" href="{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}" rel="stylesheet" />
    <link id="app-dark" href="{{ asset('dastone/default/assets/css/app-dark.min.css') }}" rel="stylesheet" />
    <link id="bootstrap-light" href="{{ asset('dastone/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" disabled />
    <link id="app-light" href="{{ asset('dastone/default/assets/css/app.min.css') }}" rel="stylesheet" disabled />
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        body {
            visibility: hidden;
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            --journal-bg: linear-gradient(180deg, #ffd9ea 0%, #ffeed1 44%, #dff3ff 100%);
            --journal-accent: #ff7ba6;
            --journal-panel: rgba(255, 255, 255, 0.92);
            --journal-pattern-primary: rgba(255, 123, 166, 0.28);
            --journal-pattern-secondary: rgba(255, 200, 214, 0.24);
            --journal-pattern-size: 180px;
            --journal-pattern-image: none;
            background: var(--journal-bg);
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
        }

        body::before {
            z-index: -2;
            background:
                radial-gradient(circle at 12% 18%, color-mix(in srgb, var(--journal-pattern-primary) 85%, white) 0, rgba(255, 255, 255, 0) 24%),
                radial-gradient(circle at 84% 14%, color-mix(in srgb, var(--journal-pattern-secondary) 90%, white) 0, rgba(255, 255, 255, 0) 18%),
                radial-gradient(circle at 18% 88%, color-mix(in srgb, var(--journal-pattern-primary) 70%, white) 0, rgba(255, 255, 255, 0) 24%),
                radial-gradient(circle at 82% 84%, color-mix(in srgb, var(--journal-pattern-secondary) 72%, white) 0, rgba(255, 255, 255, 0) 18%),
                var(--journal-bg);
        }

        body::after {
            z-index: -1;
            opacity: 1;
            background-image: var(--journal-pattern-image);
            background-size: var(--journal-pattern-size) var(--journal-pattern-size);
            background-position: 0 0;
            filter: saturate(1.18) contrast(1.04);
        }

        html[data-journal-pattern='hospital'] body {
            --journal-bg: linear-gradient(180deg, #dff3ff 0%, #efe7ff 46%, #fff0e4 100%);
            --journal-accent: #2d8eff;
            --journal-panel: rgba(255, 255, 255, 0.92);
            --journal-pattern-primary: rgba(45, 142, 255, 0.24);
            --journal-pattern-secondary: rgba(255, 121, 121, 0.18);
            --journal-pattern-size: 180px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180' viewBox='0 0 180 180'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Crect width='180' height='180' fill='rgba(255,255,255,0)'/%3E%3Crect x='28' y='30' width='54' height='54' rx='18' fill='rgba(45,142,255,0.24)'/%3E%3Cpath d='M52 41h6v12h12v6H58v12h-6V59H40v-6h12z' fill='rgba(255,255,255,0.97)'/%3E%3Ccircle cx='132' cy='62' r='22' fill='rgba(255,121,121,0.2)'/%3E%3Cpath d='M122 62h20M132 52v20' stroke='rgba(255,255,255,0.97)' stroke-width='5' stroke-linecap='round'/%3E%3Crect x='96' y='110' width='56' height='40' rx='18' fill='rgba(45,142,255,0.18)'/%3E%3Cpath d='M110 130h28' stroke='rgba(255,255,255,0.95)' stroke-width='5' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        }

            html[data-journal-pattern='clear'] body {
                --journal-bg: linear-gradient(180deg, #ffffff 0%, #fbfbfb 52%, #f3f6fb 100%);
                --journal-accent: #94a3b8;
                --journal-panel: rgba(255, 255, 255, 0.96);
                --journal-pattern-primary: rgba(255, 255, 255, 0);
                --journal-pattern-secondary: rgba(255, 255, 255, 0);
                --journal-pattern-size: 180px;
                --journal-pattern-image: none;
            }

        html[data-journal-pattern='cat'] body {
            --journal-bg: linear-gradient(180deg, #ffe8cb 0%, #ffe2ef 44%, #e9f7ff 100%);
            --journal-accent: #ff8b39;
            --journal-panel: rgba(255, 251, 247, 0.92);
            --journal-pattern-primary: rgba(255, 139, 57, 0.23);
            --journal-pattern-secondary: rgba(255, 92, 140, 0.18);
            --journal-pattern-size: 190px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='190' height='190' viewBox='0 0 190 190'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Ccircle cx='58' cy='58' r='26' fill='rgba(255,139,57,0.23)'/%3E%3Cpath d='M40 48l8-14 11 11M76 48l-8-14-11 11' fill='rgba(255,92,140,0.2)'/%3E%3Ccircle cx='50' cy='58' r='3.5' fill='rgba(255,255,255,0.98)'/%3E%3Ccircle cx='66' cy='58' r='3.5' fill='rgba(255,255,255,0.98)'/%3E%3Cpath d='M55 69c4 4 10 4 14 0' stroke='rgba(255,255,255,0.98)' stroke-width='4' stroke-linecap='round'/%3E%3Cpath d='M32 62h13M31 70h15M86 62H73M88 70H73' stroke='rgba(255,255,255,0.9)' stroke-width='3' stroke-linecap='round'/%3E%3Ccircle cx='136' cy='126' r='23' fill='rgba(255,92,140,0.18)'/%3E%3Ccircle cx='125' cy='118' r='7' fill='rgba(255,255,255,0.92)'/%3E%3Ccircle cx='147' cy='118' r='7' fill='rgba(255,255,255,0.92)'/%3E%3Ccircle cx='118' cy='136' r='7' fill='rgba(255,255,255,0.92)'/%3E%3Ccircle cx='136' cy='144' r='8' fill='rgba(255,255,255,0.92)'/%3E%3Ccircle cx='154' cy='136' r='7' fill='rgba(255,255,255,0.92)'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='heart'] body {
            --journal-bg: linear-gradient(180deg, #ffdceb 0%, #fff1f8 44%, #ebe8ff 100%);
            --journal-accent: #ff3f88;
            --journal-panel: rgba(255, 250, 252, 0.92);
            --journal-pattern-primary: rgba(255, 63, 136, 0.24);
            --journal-pattern-secondary: rgba(140, 102, 255, 0.18);
            --journal-pattern-size: 180px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180' viewBox='0 0 180 180'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cpath d='M55 42c9 0 16 7 16 16 0-9 7-16 16-16 8 0 15 7 15 16 0 21-31 37-31 37S40 79 40 58c0-9 7-16 15-16z' fill='rgba(255,63,136,0.24)'/%3E%3Cpath d='M118 108c7 0 12 6 12 12 0-6 5-12 12-12 6 0 11 5 11 12 0 16-23 28-23 28s-23-12-23-28c0-7 5-12 11-12z' fill='rgba(140,102,255,0.18)'/%3E%3Ccircle cx='45' cy='126' r='14' fill='rgba(255,63,136,0.16)'/%3E%3Ccircle cx='61' cy='126' r='14' fill='rgba(255,63,136,0.16)'/%3E%3Cpath d='M36 134c0 12 25 24 25 24s25-12 25-24' fill='rgba(255,63,136,0.16)'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='flower'] body {
            --journal-bg: linear-gradient(180deg, #ddffe2 0%, #fff0cd 44%, #e8f8ff 100%);
            --journal-accent: #37b85a;
            --journal-panel: rgba(252, 255, 250, 0.92);
            --journal-pattern-primary: rgba(55, 184, 90, 0.23);
            --journal-pattern-secondary: rgba(255, 174, 47, 0.18);
            --journal-pattern-size: 190px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='190' height='190' viewBox='0 0 190 190'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Ccircle cx='58' cy='58' r='12' fill='rgba(255,174,47,0.2)'/%3E%3Ccircle cx='58' cy='35' r='14' fill='rgba(55,184,90,0.22)'/%3E%3Ccircle cx='58' cy='81' r='14' fill='rgba(55,184,90,0.22)'/%3E%3Ccircle cx='35' cy='58' r='14' fill='rgba(55,184,90,0.22)'/%3E%3Ccircle cx='81' cy='58' r='14' fill='rgba(55,184,90,0.22)'/%3E%3Cpath d='M130 108c10 0 18 8 18 18s-8 18-18 18-18-8-18-18 8-18 18-18z' fill='rgba(55,184,90,0.18)'/%3E%3Cpath d='M130 116v20M120 126h20' stroke='rgba(255,255,255,0.95)' stroke-width='4' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='cloud'] body {
            --journal-bg: linear-gradient(180deg, #dcecff 0%, #eceeff 48%, #ffe5f1 100%);
            --journal-accent: #5f7fff;
            --journal-panel: rgba(248, 251, 255, 0.92);
            --journal-pattern-primary: rgba(95, 127, 255, 0.22);
            --journal-pattern-secondary: rgba(255, 126, 170, 0.17);
            --journal-pattern-size: 210px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='210' height='210' viewBox='0 0 210 210'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cpath d='M42 78c0-14 11-25 25-25 10 0 18 5 22 13 3-2 7-3 12-3 12 0 22 10 22 22 0 13-10 22-23 22H58C49 107 42 99 42 90c0-5 2-9 5-12z' fill='rgba(95,127,255,0.22)'/%3E%3Cpath d='M128 132c0-12 10-22 22-22 8 0 15 4 19 11 3-1 6-2 9-2 11 0 20 9 20 20s-9 20-20 20h-40c-11 0-20-9-20-20 0-3 0-5 1-7z' fill='rgba(255,126,170,0.18)'/%3E%3Ccircle cx='70' cy='142' r='8' fill='rgba(255,255,255,0.92)'/%3E%3Ccircle cx='84' cy='154' r='6' fill='rgba(255,255,255,0.88)'/%3E%3Ccircle cx='58' cy='156' r='5' fill='rgba(255,255,255,0.82)'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='bunny'] body {
            --journal-bg: linear-gradient(180deg, #ffe8f3 0%, #fff6dc 46%, #e4f7ff 100%);
            --journal-accent: #ff6ea8;
            --journal-panel: rgba(255, 251, 252, 0.93);
            --journal-pattern-primary: rgba(255, 110, 168, 0.24);
            --journal-pattern-secondary: rgba(255, 182, 88, 0.18);
            --journal-pattern-size: 190px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='190' height='190' viewBox='0 0 190 190'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Crect width='190' height='190' fill='rgba(255,255,255,0)'/%3E%3Cellipse cx='54' cy='46' rx='12' ry='28' fill='rgba(255,110,168,0.2)'/%3E%3Cellipse cx='78' cy='46' rx='12' ry='28' fill='rgba(255,182,88,0.2)'/%3E%3Ccircle cx='66' cy='84' r='24' fill='rgba(255,110,168,0.22)'/%3E%3Ccircle cx='58' cy='80' r='3.5' fill='rgba(255,255,255,0.96)'/%3E%3Ccircle cx='74' cy='80' r='3.5' fill='rgba(255,255,255,0.96)'/%3E%3Cpath d='M61 92c3 3 7 3 10 0' stroke='rgba(255,255,255,0.96)' stroke-width='4' stroke-linecap='round'/%3E%3Ccircle cx='140' cy='126' r='18' fill='rgba(91,188,255,0.16)'/%3E%3Ccircle cx='132' cy='122' r='5' fill='rgba(255,255,255,0.92)'/%3E%3Ccircle cx='148' cy='122' r='5' fill='rgba(255,255,255,0.92)'/%3E%3Cpath d='M136 133h8' stroke='rgba(255,255,255,0.92)' stroke-width='4' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='sakura'] body {
            --journal-bg: linear-gradient(180deg, #ffdce8 0%, #fff1f6 44%, #f2e8ff 100%);
            --journal-accent: #f04f8f;
            --journal-panel: rgba(255, 250, 252, 0.93);
            --journal-pattern-primary: rgba(240, 79, 143, 0.24);
            --journal-pattern-secondary: rgba(171, 120, 255, 0.18);
            --journal-pattern-size: 190px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='190' height='190' viewBox='0 0 190 190'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cpath d='M56 54c8 0 10 10 4 14 6 4 4 14-4 14-4 0-7-2-9-5-2 3-5 5-9 5-8 0-10-10-4-14-6-4-4-14 4-14 4 0 7 2 9 5 2-3 5-5 9-5z' fill='rgba(240,79,143,0.22)'/%3E%3Cpath d='M134 118c8 0 10 10 4 14 6 4 4 14-4 14-4 0-7-2-9-5-2 3-5 5-9 5-8 0-10-10-4-14-6-4-4-14 4-14 4 0 7 2 9 5 2-3 5-5 9-5z' fill='rgba(171,120,255,0.18)'/%3E%3Ccircle cx='47' cy='68' r='4' fill='rgba(255,255,255,0.96)'/%3E%3Ccircle cx='125' cy='132' r='4' fill='rgba(255,255,255,0.94)'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='star'] body {
            --journal-bg: linear-gradient(180deg, #e7efff 0%, #f0e6ff 44%, #ffe7cf 100%);
            --journal-accent: #6a63ff;
            --journal-panel: rgba(250, 251, 255, 0.93);
            --journal-pattern-primary: rgba(106, 99, 255, 0.23);
            --journal-pattern-secondary: rgba(255, 185, 79, 0.19);
            --journal-pattern-size: 200px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200' viewBox='0 0 200 200'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cpath d='M56 34l7 15 17 2-12 11 3 16-15-8-15 8 3-16-12-11 17-2z' fill='rgba(106,99,255,0.22)'/%3E%3Cpath d='M144 108l6 12 14 2-10 9 2 14-12-7-12 7 2-14-10-9 14-2z' fill='rgba(255,185,79,0.2)'/%3E%3Ccircle cx='92' cy='126' r='4' fill='rgba(255,255,255,0.94)'/%3E%3Ccircle cx='108' cy='142' r='3' fill='rgba(255,255,255,0.9)'/%3E%3Ccircle cx='124' cy='52' r='4' fill='rgba(255,255,255,0.92)'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='coffee'] body {
            --journal-bg: linear-gradient(180deg, #f5dfcf 0%, #fff0e3 44%, #f5e7ff 100%);
            --journal-accent: #9b5b3d;
            --journal-panel: rgba(255, 250, 246, 0.93);
            --journal-pattern-primary: rgba(155, 91, 61, 0.22);
            --journal-pattern-secondary: rgba(167, 118, 255, 0.16);
            --journal-pattern-size: 190px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='190' height='190' viewBox='0 0 190 190'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cpath d='M38 68h42v22c0 12-9 22-21 22S38 102 38 90V68z' fill='rgba(155,91,61,0.22)'/%3E%3Cpath d='M80 74h10c8 0 14 6 14 14s-6 14-14 14H80' stroke='rgba(155,91,61,0.22)' stroke-width='6'/%3E%3Cpath d='M50 48c0 9-7 9-7 18M64 44c0 9-7 9-7 18' stroke='rgba(255,255,255,0.95)' stroke-width='4' stroke-linecap='round'/%3E%3Ccircle cx='138' cy='128' r='20' fill='rgba(167,118,255,0.15)'/%3E%3Cpath d='M130 128h16' stroke='rgba(255,255,255,0.94)' stroke-width='4' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        }

        html[data-journal-pattern='pill'] body {
            --journal-bg: linear-gradient(180deg, #dff5ff 0%, #fff0da 44%, #ffdbe8 100%);
            --journal-accent: #00a6c7;
            --journal-panel: rgba(248, 254, 255, 0.93);
            --journal-pattern-primary: rgba(0, 166, 199, 0.23);
            --journal-pattern-secondary: rgba(255, 109, 145, 0.18);
            --journal-pattern-size: 190px;
            --journal-pattern-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='190' height='190' viewBox='0 0 190 190'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Crect x='28' y='42' width='62' height='28' rx='14' fill='rgba(0,166,199,0.22)'/%3E%3Cpath d='M59 42v28' stroke='rgba(255,255,255,0.96)' stroke-width='4'/%3E%3Crect x='106' y='112' width='56' height='26' rx='13' fill='rgba(255,109,145,0.2)'/%3E%3Cpath d='M134 112v26' stroke='rgba(255,255,255,0.96)' stroke-width='4'/%3E%3Ccircle cx='56' cy='126' r='16' fill='rgba(255,191,92,0.16)'/%3E%3Ccircle cx='48' cy='126' r='3' fill='rgba(255,255,255,0.96)'/%3E%3Ccircle cx='56' cy='118' r='3' fill='rgba(255,255,255,0.96)'/%3E%3Ccircle cx='64' cy='126' r='3' fill='rgba(255,255,255,0.96)'/%3E%3Ccircle cx='56' cy='134' r='3' fill='rgba(255,255,255,0.96)'/%3E%3C/g%3E%3C/svg%3E");
        }

        .page-wrapper,
        .page-content {
            position: relative;
        }

        .page-content {
            z-index: 0;
        }

        .journal-pattern-switcher {
            position: fixed;
            left: 20px;
            bottom: 22px;
            z-index: 1200;
            width: auto;
        }

        .journal-pattern-toggle {
            width: 54px;
            height: 54px;
            border: 0;
            border-radius: 18px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--journal-panel);
            color: #1f2937;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
            backdrop-filter: blur(16px);
        }

        .journal-pattern-toggle-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .journal-pattern-toggle-icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, var(--journal-accent), color-mix(in srgb, var(--journal-accent) 45%, white));
            box-shadow: 0 10px 18px color-mix(in srgb, var(--journal-accent) 30%, transparent);
            font-size: 14px;
        }

        .journal-pattern-toggle-text,
        .journal-pattern-toggle-chevron {
            display: none;
        }

        .journal-pattern-panel {
            position: absolute;
            left: 0;
            bottom: calc(100% + 10px);
            width: min(280px, calc(100vw - 32px));
            margin-top: 0;
            border-radius: 20px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
            backdrop-filter: blur(18px);
            display: none;
        }

        .journal-pattern-panel.active {
            display: block;
        }

        .journal-pattern-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .journal-pattern-option {
            border: 0;
            border-radius: 16px;
            padding: 10px;
            text-align: left;
            background: #fff;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08), 0 12px 26px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .journal-pattern-option:hover {
            transform: translateY(-1px);
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.1), 0 16px 30px rgba(15, 23, 42, 0.1);
        }

        .journal-pattern-option.is-active {
            box-shadow: inset 0 0 0 2px var(--journal-accent), 0 18px 32px rgba(15, 23, 42, 0.12);
        }

        .journal-pattern-option-clear {
            background: linear-gradient(135deg, #ffffff, #f2f5f9);
        }

        .journal-pattern-swatch {
            height: 50px;
            border-radius: 14px;
            margin-bottom: 8px;
            background-size: cover;
            background-position: center;
        }

        .journal-pattern-option-title {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .journal-pattern-option-copy {
            display: block;
            margin-top: 2px;
            font-size: 10px;
            color: #6b7280;
        }

        .swatch-hospital {
            background: linear-gradient(135deg, #d9f2ff, #f6edff);
            position: relative;
        }

        .swatch-clear {
            background: linear-gradient(135deg, #ffffff, #eef2f7);
            position: relative;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.28);
        }

        .swatch-clear::before,
        .swatch-clear::after,
        .swatch-hospital::before,
        .swatch-hospital::after,
        .swatch-cat::before,
        .swatch-cat::after,
        .swatch-heart::before,
        .swatch-heart::after,
        .swatch-flower::before,
        .swatch-flower::after,
        .swatch-cloud::before,
        .swatch-cloud::after,
        .swatch-bunny::before,
        .swatch-bunny::after,
        .swatch-sakura::before,
        .swatch-sakura::after,
        .swatch-star::before,
        .swatch-star::after,
        .swatch-coffee::before,
        .swatch-coffee::after,
        .swatch-pill::before,
        .swatch-pill::after {
            content: '';
            position: absolute;
        }

        .swatch-clear::before {
            inset: 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.7);
            box-shadow: inset 0 0 0 1px rgba(203, 213, 225, 0.9);
        }

        .swatch-clear::after {
            inset: 22px 18px auto 18px;
            height: 6px;
            border-radius: 999px;
            background: rgba(203, 213, 225, 0.9);
            box-shadow: 0 12px 0 0 rgba(226, 232, 240, 0.95);
        }

        .swatch-hospital::before {
            inset: 14px auto auto 16px;
            width: 18px;
            height: 18px;
            background: #59a8ff;
            border-radius: 6px;
            box-shadow: 28px 18px 0 6px rgba(255, 135, 135, 0.45);
        }

        .swatch-hospital::after {
            inset: 18px auto auto 21px;
            width: 8px;
            height: 8px;
            background: #fff;
            box-shadow: 0 5px 0 0 #fff, -5px 5px 0 0 #fff, 5px 5px 0 0 #fff;
        }

        .swatch-cat {
            background: linear-gradient(135deg, #ffe8cf, #ffe4ef);
            position: relative;
        }

        .swatch-cat::before {
            inset: 18px auto auto 18px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255, 149, 82, 0.75);
            box-shadow: 70px 14px 0 8px rgba(255, 124, 154, 0.3);
        }

        .swatch-cat::after {
            inset: 12px auto auto 14px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 12px solid rgba(255, 124, 154, 0.65);
            box-shadow: 16px 0 0 0 rgba(255, 124, 154, 0.65);
        }

        .swatch-heart {
            background: linear-gradient(135deg, #ffe2f1, #f0ebff);
            position: relative;
        }

        .swatch-heart::before {
            inset: 17px auto auto 20px;
            width: 18px;
            height: 18px;
            background: rgba(255, 95, 155, 0.75);
            transform: rotate(45deg);
        }

        .swatch-heart::after {
            inset: 12px auto auto 20px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(255, 95, 155, 0.75);
            box-shadow: 12px 0 0 0 rgba(255, 95, 155, 0.75), 62px 16px 0 4px rgba(169, 122, 255, 0.42);
        }

        .swatch-flower {
            background: linear-gradient(135deg, #e8ffe8, #fff7d9);
            position: relative;
        }

        .swatch-flower::before {
            inset: 16px auto auto 20px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255, 196, 84, 0.9);
            box-shadow: 0 -12px 0 4px rgba(101, 194, 124, 0.55), 0 12px 0 4px rgba(101, 194, 124, 0.55), -12px 0 0 4px rgba(101, 194, 124, 0.55), 12px 0 0 4px rgba(101, 194, 124, 0.55);
        }

        .swatch-flower::after {
            inset: 18px auto auto 78px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(101, 194, 124, 0.28);
        }

        .swatch-cloud {
            background: linear-gradient(135deg, #e5f3ff, #ffeaf4);
            position: relative;
        }

        .swatch-cloud::before {
            inset: 22px auto auto 18px;
            width: 34px;
            height: 18px;
            border-radius: 20px;
            background: rgba(125, 155, 255, 0.52);
            box-shadow: 48px 14px 0 3px rgba(255, 170, 198, 0.36);
        }

        .swatch-cloud::after {
            inset: 12px auto auto 26px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(125, 155, 255, 0.52);
            box-shadow: 12px 4px 0 2px rgba(125, 155, 255, 0.52), 68px 18px 0 2px rgba(255, 170, 198, 0.36), 82px 18px 0 0 rgba(255, 170, 198, 0.36);
        }

        .swatch-bunny {
            background: linear-gradient(135deg, #ffd6e9, #fff0bc);
            position: relative;
        }

        .swatch-bunny::before {
            inset: 8px auto auto 18px;
            width: 12px;
            height: 28px;
            border-radius: 12px;
            background: rgba(255, 110, 168, 0.75);
            box-shadow: 18px 0 0 0 rgba(255, 182, 88, 0.75);
        }

        .swatch-bunny::after {
            inset: 26px auto auto 16px;
            width: 28px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 110, 168, 0.6);
            box-shadow: 62px 18px 0 4px rgba(91, 188, 255, 0.34);
        }

        .swatch-sakura {
            background: linear-gradient(135deg, #ffd6e5, #efe2ff);
            position: relative;
        }

        .swatch-sakura::before {
            inset: 16px auto auto 18px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(240, 79, 143, 0.78);
            box-shadow: 8px -8px 0 0 rgba(240, 79, 143, 0.78), 16px 0 0 0 rgba(240, 79, 143, 0.78), 8px 8px 0 0 rgba(240, 79, 143, 0.78), 70px 16px 0 5px rgba(171, 120, 255, 0.42);
        }

        .swatch-sakura::after {
            inset: 26px auto auto 26px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 72px 20px 0 0 rgba(255, 255, 255, 0.9);
        }

        .swatch-star {
            background: linear-gradient(135deg, #e3e9ff, #ffe6bc);
            position: relative;
        }

        .swatch-star::before {
            inset: 12px auto auto 16px;
            width: 24px;
            height: 24px;
            background: rgba(106, 99, 255, 0.72);
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 92%, 50% 71%, 21% 92%, 32% 57%, 2% 35%, 39% 35%);
            box-shadow: 72px 18px 0 4px rgba(255, 185, 79, 0.45);
        }

        .swatch-star::after {
            inset: 44px auto auto 26px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 12px 10px 0 0 rgba(255, 255, 255, 0.9), 68px -10px 0 0 rgba(255, 255, 255, 0.9);
        }

        .swatch-coffee {
            background: linear-gradient(135deg, #efd5c6, #f3e1ff);
            position: relative;
        }

        .swatch-coffee::before {
            inset: 20px auto auto 18px;
            width: 24px;
            height: 18px;
            border-radius: 0 0 10px 10px;
            background: rgba(155, 91, 61, 0.72);
            box-shadow: 8px -10px 0 -6px rgba(255,255,255,0.95), 70px 14px 0 6px rgba(167, 118, 255, 0.34);
        }

        .swatch-coffee::after {
            inset: 10px auto auto 22px;
            width: 4px;
            height: 14px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 10px -4px 0 0 rgba(255, 255, 255, 0.94);
        }

        .swatch-pill {
            background: linear-gradient(135deg, #d9f5ff, #ffdbe8);
            position: relative;
        }

        .swatch-pill::before {
            inset: 16px auto auto 18px;
            width: 34px;
            height: 16px;
            border-radius: 999px;
            background: rgba(0, 166, 199, 0.72);
            box-shadow: 68px 18px 0 3px rgba(255, 109, 145, 0.42);
        }

        .swatch-pill::after {
            inset: 16px auto auto 35px;
            width: 3px;
            height: 16px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 75px 18px 0 0 rgba(255, 255, 255, 0.95);
        }

        .no-transition *, .no-transition *::before, .no-transition *::after {
            transition: none !important;
        }

        @media (max-width: 767px) {
            .journal-pattern-switcher {
                left: 16px;
                bottom: 16px;
            }

            .journal-pattern-panel {
                width: min(260px, calc(100vw - 24px));
            }

            .journal-pattern-grid {
                grid-template-columns: 1fr;
            }

            .journal-pattern-toggle {
                width: 50px;
                height: 50px;
            }
        }
    </style>

    @yield('styles')
</head>
<body>
    @yield('navbar')

    <div class="page-wrapper">
        @include('layouts.shared.topbar', ['topbarUseEmployeePhoto' => true, 'topbarProfileRoute' => 'hrd.employee.profile'])

        <div class="page-content">
            @yield('content')
            @include('layouts.daily_journal.footer')
        </div>
    </div>

    <div class="journal-pattern-switcher" id="journalPatternSwitcher">
        <button type="button" class="journal-pattern-toggle" id="journalPatternToggle" aria-label="Open background picker" title="Background picker">
            <span class="journal-pattern-toggle-label">
                <span class="journal-pattern-toggle-icon">
                    <i data-feather="image"></i>
                </span>
            </span>
        </button>

        <div class="journal-pattern-panel" id="journalPatternPanel">
            <div class="journal-pattern-grid">
                <button type="button" class="journal-pattern-option journal-pattern-option-clear" data-pattern="clear" data-pattern-label="Clear White">
                    <span class="journal-pattern-swatch swatch-clear"></span>
                    <span class="journal-pattern-option-title">Clear White</span>
                    <span class="journal-pattern-option-copy">Plain clean background</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="hospital" data-pattern-label="Hospital Charm">
                    <span class="journal-pattern-swatch swatch-hospital"></span>
                    <span class="journal-pattern-option-title">Hospital Charm</span>
                    <span class="journal-pattern-option-copy">Blue pink medical icons</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="cat" data-pattern-label="Cat Parade">
                    <span class="journal-pattern-swatch swatch-cat"></span>
                    <span class="journal-pattern-option-title">Cat Parade</span>
                    <span class="journal-pattern-option-copy">Warm peach playful cats</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="heart" data-pattern-label="Heart Pop">
                    <span class="journal-pattern-swatch swatch-heart"></span>
                    <span class="journal-pattern-option-title">Heart Pop</span>
                    <span class="journal-pattern-option-copy">Candy pink dreamy hearts</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="flower" data-pattern-label="Garden Bloom">
                    <span class="journal-pattern-swatch swatch-flower"></span>
                    <span class="journal-pattern-option-title">Garden Bloom</span>
                    <span class="journal-pattern-option-copy">Fresh green floral dots</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="cloud" data-pattern-label="Cloud Candy">
                    <span class="journal-pattern-swatch swatch-cloud"></span>
                    <span class="journal-pattern-option-title">Cloud Candy</span>
                    <span class="journal-pattern-option-copy">Airy blue pastel clouds</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="bunny" data-pattern-label="Bunny Bounce">
                    <span class="journal-pattern-swatch swatch-bunny"></span>
                    <span class="journal-pattern-option-title">Bunny Bounce</span>
                    <span class="journal-pattern-option-copy">Pink sunny bunny faces</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="sakura" data-pattern-label="Sakura Pop">
                    <span class="journal-pattern-swatch swatch-sakura"></span>
                    <span class="journal-pattern-option-title">Sakura Pop</span>
                    <span class="journal-pattern-option-copy">Bright blossom petals</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="star" data-pattern-label="Star Sprinkle">
                    <span class="journal-pattern-swatch swatch-star"></span>
                    <span class="journal-pattern-option-title">Star Sprinkle</span>
                    <span class="journal-pattern-option-copy">Violet gold night candy</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="coffee" data-pattern-label="Coffee Break">
                    <span class="journal-pattern-swatch swatch-coffee"></span>
                    <span class="journal-pattern-option-title">Coffee Break</span>
                    <span class="journal-pattern-option-copy">Caramel mauve cafe mood</span>
                </button>
                <button type="button" class="journal-pattern-option" data-pattern="pill" data-pattern-label="Pill Party">
                    <span class="journal-pattern-swatch swatch-pill"></span>
                    <span class="journal-pattern-option-title">Pill Party</span>
                    <span class="journal-pattern-option-copy">Aqua coral medical candy</span>
                </button>
            </div>
        </div>
    </div>

    <script src="{{ asset('dastone/default/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/metismenu.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/waves.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('dastone/default/assets/js/app.js') }}"></script>
    <script src="{{ asset('dastone/plugins/sweet-alert2/sweetalert2.min.js') }}"></script>

    <script>
        function applyTheme(isDark) {
            const html = document.documentElement;
            const bootstrapDark = document.getElementById('bootstrap-dark');
            const appDark = document.getElementById('app-dark');
            const bootstrapLight = document.getElementById('bootstrap-light');
            const appLight = document.getElementById('app-light');

            html.classList.add('no-transition');

            bootstrapDark.disabled = !isDark;
            appDark.disabled = !isDark;
            bootstrapLight.disabled = isDark;
            appLight.disabled = isDark;

            void bootstrapDark.offsetWidth;

            html.classList.remove('theme-dark', 'theme-light');
            html.classList.add(isDark ? 'theme-dark' : 'theme-light');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            setTimeout(() => {
                html.classList.remove('no-transition');
            }, 100);
        }

        function applyJournalPattern(pattern) {
            const html = document.documentElement;
            const nextPattern = pattern || 'clear';
            html.dataset.journalPattern = nextPattern;
            localStorage.setItem('dailyJournalPattern', nextPattern);

            const options = document.querySelectorAll('.journal-pattern-option');

            options.forEach((option) => {
                const isActive = option.dataset.pattern === nextPattern;
                option.classList.toggle('is-active', isActive);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('darkModeSwitch');
            const saved = localStorage.getItem('theme') || 'dark';
            const isDark = saved === 'dark';
            const patternToggle = document.getElementById('journalPatternToggle');
            const patternPanel = document.getElementById('journalPatternPanel');
            const switcher = document.getElementById('journalPatternSwitcher');
            const patternOptions = document.querySelectorAll('.journal-pattern-option');
            const savedPattern = localStorage.getItem('dailyJournalPattern') || document.documentElement.dataset.journalPattern || 'clear';

            applyTheme(isDark);
            applyJournalPattern(savedPattern);

            if (toggle) {
                toggle.checked = isDark;
                toggle.addEventListener('change', function () {
                    applyTheme(this.checked);
                });
            }

            if (patternToggle && patternPanel) {
                patternToggle.addEventListener('click', function () {
                    patternPanel.classList.toggle('active');
                });
            }

            patternOptions.forEach((option) => {
                option.addEventListener('click', function () {
                    applyJournalPattern(option.dataset.pattern);

                    if (patternPanel) {
                        patternPanel.classList.remove('active');
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (!switcher || !patternPanel || !patternPanel.classList.contains('active')) {
                    return;
                }

                if (!switcher.contains(event.target)) {
                    patternPanel.classList.remove('active');
                }
            });

            if (window.feather) {
                window.feather.replace();
            }

            document.body.style.visibility = 'visible';
        });
    </script>

    @include('partials.global_emotion_heartbeat')
    @yield('scripts')
</body>
</html>
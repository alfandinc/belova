<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8" />
    <title>Sistem Informasi Klinik Belova</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Sistem Informasi Manajemen Rumah Sakit Belova" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('img/logo-favicon-belova.png')}}"> 

    <!-- App css -->
    <link href="{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}" rel="stylesheet" type="text/css" id="bootstrap-style" />
    <link href="{{ asset('dastone/default/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('dastone/default/assets/css/fontawesome.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('dastone/default/assets/css/app-dark.min.css')}}" rel="stylesheet" type="text/css" id="app-style" />

    <style>
        :root {
            --bg-body: #2d3748;
            --bg-topbar: #1e2430;
            --bg-banner: linear-gradient(45deg, #3a4b5c, #1e2430);
            --text-color: #ffffff;
            --text-muted: rgba(255,255,255,0.5);
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.2);
        }
        
        [data-theme="light"] {
            --bg-body: #f8f9fa;
            --bg-topbar: #ffffff;
            --bg-banner: linear-gradient(45deg, #e9ecef, #dee2e6);
            --text-color: #212529;
            --text-muted: rgba(0,0,0,0.5);
            --border-color: rgba(0,0,0,0.1);
            --shadow-color: rgba(0,0,0,0.1);
        }
        
        body {
            background-color: var(--bg-body);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-color);
            transition: background-color 0.3s ease;
        }
        
        .page-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .topbar {
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--bg-topbar);
            border-bottom: 1px solid var(--border-color);
            width: 100%;
            box-sizing: border-box;
            transition: background-color 0.3s ease;
        }

        /* Topbar datetime responsive tweaks */
        .date-display { display:flex; flex-direction:column; align-items:flex-end; font-size:13px; }
        .date-display .date-compact { font-weight:700; font-size:14px; }
        
        .logo img {
            height: 40px;
            width: auto;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-middle {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 0 20px;
            min-width: 0;
        }

        .emotion-board {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
            max-width: 780px;
        }

        .emotion-avatar-item {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            flex: 0 0 auto;
            z-index: 1;
        }

        .emotion-avatar-item:hover {
            z-index: 6;
        }

        .emotion-avatar-item.is-current-user,
        .clickable-avatar {
            cursor: pointer;
        }

        .emotion-avatar-item.is-current-user .emotion-avatar,
        .clickable-avatar {
            box-shadow: 0 0 0 2px rgba(255,255,255,0.14), 0 8px 20px rgba(0,0,0,0.22);
        }

        .emotion-chip-empty {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            color: var(--text-muted);
            border: 1px dashed var(--border-color);
        }

        .emotion-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.16);
            box-shadow: 0 6px 16px rgba(0,0,0,0.18);
            background: rgba(255,255,255,0.08);
        }

        .emotion-avatar-item:hover .emotion-avatar {
            box-shadow: 0 10px 22px rgba(0,0,0,0.28);
        }

        .emotion-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .emotion-avatar-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #64748b, #334155);
        }

        .emotion-badge {
            position: absolute;
            bottom: -4px;
            right: -2px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
            border: 2px solid var(--bg-topbar);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            transform-origin: center center;
        }

        .emotion-avatar-item:hover .emotion-badge {
            transform: scale(1.6);
            box-shadow: 0 10px 20px rgba(0,0,0,0.34);
        }

        .emotion-avatar-tooltip {
            position: absolute;
            left: 50%;
            bottom: -52px;
            transform: translateX(-50%);
            padding: 5px 10px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.94);
            color: #fff;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
            box-shadow: 0 8px 18px rgba(0,0,0,0.22);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .emotion-avatar-tooltip .name {
            font-weight: 700;
            line-height: 1.2;
        }

        .emotion-avatar-tooltip .mood {
            font-size: 10px;
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            line-height: 1.2;
        }

        .emotion-avatar-item:hover .emotion-avatar-tooltip {
            opacity: 1;
            transform: translateX(-50%) translateY(4px);
        }

        .emotion-picker-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .emotion-picker-option {
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            color: inherit;
            padding: 12px 8px;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .emotion-picker-option:hover,
        .emotion-picker-option.active {
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.12);
        }

        .emotion-picker-option.active {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.22);
            background: rgba(37, 99, 235, 0.12);
        }

        .emotion-picker-option.active .label {
            color: #2563eb;
        }

        .emotion-picker-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            font-size: 14px;
            font-weight: 600;
        }

        .emotion-picker-selected .emoji {
            font-size: 18px;
            line-height: 1;
        }

        .emotion-picker-option .emoji {
            display: block;
            font-size: 24px;
            line-height: 1;
            margin-bottom: 8px;
        }

        .emotion-picker-option .label {
            display: block;
            font-size: 13px;
            font-weight: 600;
        }
        
        .date-display {
            color: var(--text-color);
            font-size: 14px;
        }
        
        .theme-toggle {
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background: var(--border-color);
        }

        #logout-btn {
            background: transparent;
            border-color: #dc3545;
            color: #dc3545;
        }

        #logout-btn:hover {
            background: rgba(220, 53, 69, 0.12);
            border-color: #dc3545;
            color: #dc3545;
        }
        
        .content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .welcome-banner {
            text-align: center;
            margin: 20px 0;
              padding: 8px 0 12px;
            color: var(--text-color);
            width: 100%;
            max-width: 1200px;
            transition: all 0.3s ease;
        }
        
        .welcome-banner h2 {
            margin-top: 0;
            margin-bottom: 12px;
            animation: fadeIn 1s ease;
            font-size: clamp(1.9rem, 2.7vw, 2.7rem);
            letter-spacing: -0.03em;
        }
        
        .welcome-prefix {
            font-weight: 400; /* Regular weight for "Welcome to" */
        }
        
        .sim-name {
            font-weight: 800; /* Bolder weight for "SIM Klinik Belova" */
            font-size: 1.1em; /* Slightly larger */
        }
        
        .welcome-banner p {
            margin-bottom: 0;
            animation: fadeIn 1.2s ease;
        }

        .welcome-slogan {
            margin: 0;
            font-size: clamp(0.95rem, 1.5vw, 1.1rem);
            font-weight: 500;
            letter-spacing: 0.02em;
            color: var(--text-muted);
        }

        .welcome-dashboard-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 220px;
            padding: 10px 18px;
            border-radius: 999px;
            background: #c7d2fe;
            border: 1px solid rgba(199,210,254,0.85);
            color: #1e3a8a;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            line-height: 1;
            white-space: nowrap;
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .welcome-dashboard-btn i {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(30, 58, 138, 0.12);
        }

        .welcome-dashboard-btn:hover {
            color: #1d4ed8;
            background: #e0e7ff;
            border-color: rgba(224,231,255,0.95);
            transform: translateY(-1px);
            text-decoration: none;
        }
        
        /* Main menu grid and modern glass tiles */
        .menu-grid-wrapper {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 16px;
            box-sizing: border-box;
        }

        .menu-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .menu-dashboard-cta {
            flex: 0 0 auto;
            display: flex;
            justify-content: center;
        }

        .menu-search {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu-search input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: rgba(255,255,255,0.03);
            color: var(--text-color);
            outline: none;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-trigger {
            cursor: pointer;
        }

        .profile-trigger:focus {
            outline: none;
        }

        .profile-trigger-info {
            min-width: 120px;
            padding: 6px 8px;
            border-radius: 10px;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .profile-trigger-info:hover,
        .profile-trigger-info:focus {
            background: rgba(255,255,255,0.06);
            transform: translateY(-1px);
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            display: inline-block;
            border: 2px solid rgba(255,255,255,0.06);
            transition: transform 180ms ease, box-shadow 180ms ease;
            transform-origin: center center;
            position: relative;
        }

        .avatar img { width: 100%; height: 100%; object-fit: cover; transition: transform 180ms ease; display:block; }

        /* Enlarge avatar on hover without shifting layout */
        .avatar:hover {
            transform: scale(1.25);
            z-index: 50;
            box-shadow: 0 8px 22px rgba(0,0,0,0.35);
        }

        /* Slight image inner scale for crispness */
        .avatar:hover img { transform: scale(1.05); }

        /* Reduce hover effect on small screens */
        @media (max-width: 480px) {
            .avatar:hover { transform: scale(1.08); }
        }

        .avatar-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display:flex; align-items:center; justify-content:center;
            background: rgba(0,0,0,0.15);
            color: var(--text-color);
            font-weight:700;
            font-size: 14px;
        }

        .tiles {
            display: grid;
            grid-template-columns: repeat(5, minmax(160px, 1fr));
            gap: 18px;
            align-items: stretch;
        }

        .menu-tile {
            min-height: 160px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            color: #fff;
            transition: transform 0.28s ease, box-shadow 0.28s ease, filter 0.28s ease;
            position: relative;
            overflow: hidden;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(2,6,23,0.35);
            background: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(0,0,0,0.06));
            backdrop-filter: blur(6px) saturate(120%);
            border: 1px solid rgba(255,255,255,0.04);
            cursor: pointer;
            text-decoration: none;
        }

        .menu-tile:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 14px 36px rgba(2,6,23,0.5);
            filter: brightness(1.05);
        }

        .menu-tile .menu-top {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: flex-start;
        }

        .menu-icon {
            font-size: 2.6rem;
            margin-bottom: 8px;
            width: 48px;
            height: 48px;
            display:flex; align-items:center; justify-content:center;
            background: rgba(255,255,255,0.06);
            border-radius: 10px;
        }

        .menu-title {
            font-weight:700;
            font-size:15px;
            letter-spacing:0.6px;
        }

        .menu-sub { font-size:12px; opacity:0.85; margin-top:4px; }

        .menu-badge { font-size:12px; padding:4px 8px; border-radius:999px; background: rgba(0,0,0,0.2); }

        .menu-label { position: static; bottom: auto; }

        /* Bell notification top-right and badge bottom-right for tiles */
        .tile-bell-top {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 6;
        }
        .tile-bell-top .bell {
            width: 36px;
            height: 36px;
            display:flex; align-items:center; justify-content:center;
            border-radius:10px;
            background: rgba(255,255,255,0.06);
            color: #fff;
            font-size: 16px;
            position: relative;
            box-shadow: 0 6px 14px rgba(0,0,0,0.18); /* subtle shadow for bell */
            backdrop-filter: blur(2px);
        }
        .tile-bell-top .count {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ff3b30; /* red */
            color: #fff;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 999px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            font-weight:700;
            line-height:1;
            min-width: 20px;
            text-align: center;
        }

        /* Small label pinned to bottom-right of the tile (e.g. 'Task') */
        .tile-badge-bottom {
            position: absolute;
            right: 16px; /* moved further from the tile edge */
            bottom: 14px; /* lifted slightly above the edge */
            font-size:12px;
            padding:8px 12px; /* more comfortable padding */
            border-radius:999px;
            background: rgba(0,0,0,0.18);
            color: #fff;
            z-index: 6;
            box-shadow: none; /* removed shadow per request */
            font-weight:700;
            border: 1px solid rgba(255,255,255,0.03);
            backdrop-filter: blur(2px);
        }

        /* Reduce sizes a bit on small screens */
        @media (max-width: 768px) {
            .tile-bell-top .bell { width: 32px; height: 32px; font-size:14px; }
            .tile-bell-top .count { top: -5px; right: -5px; padding:2px 5px; font-size:11px; }
            .tile-badge-bottom { right:12px; bottom:10px; padding:6px 9px; font-size:11px; }
        }

        
    /* Harmonized accessible palette (soft, friendly, good contrast for white icons) */
    .tile-erm { background-color: #1fb6aa; }          /* teal */
    .tile-farmasi { background-color: #3ac36d; }      /* green */
    .tile-laboratorium { background-color: #f07ab8; } /* warm pink */
    .tile-beautician { background-color: #ff8fa3; }   /* coral-pink */
    .tile-lab { background-color: #ef6b6b; }          /* soft red */
    .tile-hrd { background-color: #5bb0ff; }          /* light sky blue */
    .tile-dokumen { background-color: #4f8ef7; }      /* blue */
    .tile-laporan { background-color: #8b5cf6; }      /* violet */
    .tile-marketing { background-color: #ffab66; }    /* warm orange */
    .tile-finance { background-color: #f6b042; }      /* amber/gold */
    .tile-inventory { background-color: #b794ff; }    /* soft purple */
    .tile-akreditasi { background-color: #2dd4bf; }   /* teal-light */
    .tile-kos { background-color: #ff6fb5; }          /* magenta */
    .tile-insiden { background-color: #d9534f; }      /* alert red */
    .tile-jadwal { background-color: #9b72ff; }       /* schedule violet */
    .tile-belova-mengaji { background-color: #0ed668; } /* green */
    .tile-joblist { background-color: #00b8d9; }      /* bright cyan */
    .tile-wifi { background-color: #00b8d9; }      /* bright cyan */
    .tile-admin { background-color: #34495e; }     /* dark slate for admin */
    .tile-daily-journal { background-color: #da16d0; } /* warm pink */
    .tile-rnd { background-color: #0f766e; }       /* research teal */

    .tile-satusehat { background-color: #009688; }  /* satusehat teal */

    .tile-ceodashboard { background-color: #2758b6; }  /* CEO dashboard blue */

    /* Hover: subtly darken the existing background for depth */
    .menu-tile:hover { filter: brightness(0.92); }
        .footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            width: 100%;
            /* Reserve a fixed footer height so we can avoid content overlap */
            height: 72px;
            box-sizing: border-box;
        }

        /* Make sure page content leaves space for footer to avoid overlap */
        .content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
            padding-bottom: calc(72px + 20px); /* footer height + extra spacing */
        }

        /* ===== Jadwal viewer (modal) ===== */
        .jv-dialog { max-width: 1200px; width: 95vw; }
        .jv-tabs .nav-link { font-weight: 600; padding: 8px; }
        .jv-nav { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .jv-nav .btn { min-height: 40px; }
        .jv-nav .jv-label { flex: 1; font-weight: 600; color: inherit; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .jv-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; margin-bottom: 8px; }
        .jv-mode { min-width: 40px; }
        .jv-day { min-width: 0; min-height: 50px; border: 1px solid rgba(128,128,128,.35); border-radius: 10px; background: transparent; color: inherit; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4px 2px; position: relative; }
        .jv-day small { font-size: 11px; opacity: .75; }
        .jv-day b { font-size: 16px; line-height: 1.2; font-weight: 600; }
        .jv-day.red small, .jv-day.red b { color: #e74c3c; }
        .jv-day.today::after { content: ''; position: absolute; bottom: 4px; width: 6px; height: 6px; border-radius: 50%; background: #1e88e5; }
        .jv-day.active { background: #1e88e5; border-color: #1e88e5; color: #fff; }
        .jv-day.active small, .jv-day.active b { color: #fff; }
        .jv-day.active.today::after { background: #fff; }
        .jv-filters { display: flex; gap: 6px; margin-bottom: 8px; }
        .jv-filters .form-control { min-height: 38px; font-size: 15px; }
        .jv-filters .jv-clinic { max-width: 45%; }
        .jv-table-wrap::-webkit-scrollbar { display: none; }
        .jv-mini { border-radius: 6px; font-size: 12px; font-weight: 500; line-height: 1.3; padding: 4px 8px; white-space: nowrap; }
        .jv-mini + .jv-mini { margin-top: 3px; }
        .jv-mini.off { opacity: .45; font-weight: 400; }
        .jv-mini.libur { background: #e74c3c; color: #fff; }
        .jv-body { max-height: calc(100vh - 330px); overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .jv-empty { padding: 24px 8px; text-align: center; opacity: .75; }
        .jv-day-title { font-weight: 600; padding: 4px 2px 6px; }
        .jv-group { position: sticky; top: 0; z-index: 1; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; padding: 6px 8px; background: rgba(128,128,128,.18); backdrop-filter: blur(6px); border-radius: 6px; margin-top: 6px; }
        .jv-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px; border-bottom: 1px solid rgba(128,128,128,.18); }
        .jv-row.me, .jv-table tr.me td { background: rgba(30,136,229,.12); }
        .jv-name { min-width: 0; font-size: 14px; font-weight: 400; }
        .jv-name small { display: block; font-weight: 400; font-size: 11px; opacity: .7; }
        .jv-val { flex: none; max-width: 48%; text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 3px; }
        .jv-chip { display: inline-block; border-radius: 6px; padding: 3px 8px; font-size: 12px; font-weight: 500; white-space: nowrap; }
        .jv-chip2 { text-align: center; line-height: 1.25; padding: 4px 10px; }
        .jv-chip2 small { display: block; font-size: 10.5px; font-weight: 400; opacity: .85; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
        .jv-libur { background: #e74c3c; color: #fff; }
        .jv-off { opacity: .55; font-size: 12px; }
        .jv-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
        .jv-table-wrap { overflow: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
        .jv-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
        .jv-table th, .jv-table td { padding: 5px 6px; border-bottom: 1px solid rgba(128,128,128,.2); text-align: center; vertical-align: middle; }
        .jv-table td { min-width: 112px; padding: 6px 5px; }
        .jv-table thead th { min-width: 112px; font-weight: 600; }
        body.jv-open #belovaChatWidget { display: none !important; }
        .jv-table thead th { position: sticky; top: 0; z-index: 2; background: var(--jv-bg, #fff); }
        .jv-table th.today, .jv-table td.today { background-color: rgba(30,136,229,.08); }
        .jv-table th.red { color: #e74c3c; }
        .jv-table .jv-sticky { position: sticky; left: 0; z-index: 1; background: var(--jv-bg, #fff); text-align: left; min-width: 150px; }
        .jv-table thead .jv-sticky { z-index: 3; }
        .jv-table .jv-group-row td { font-weight: 700; font-size: 12px; text-transform: uppercase; background: rgba(128,128,128,.15); text-align: left; }
        .jv-group-label { position: sticky; left: 8px; }
        .jv-table tr.me td.jv-sticky { background: linear-gradient(rgba(30,136,229,.12), rgba(30,136,229,.12)), var(--jv-bg, #fff); }
        @media (max-width: 767.98px) {
            #jadwalModal .modal-dialog.jv-dialog { margin: 0; width: 100%; max-width: 100%; height: 100%; }
            #jadwalModal .modal-content { min-height: 100%; border-radius: 0; border: 0; }
            .jv-body { max-height: none; }
            .jv-day b { font-size: 15px; }
            .jv-name { font-size: 13.5px; }
            .jv-nav { gap: 4px; }
            .jv-table { font-size: 12px; }
            .jv-table td, .jv-table thead th { min-width: 108px; }
            .jv-table .jv-sticky { min-width: 118px; max-width: 128px; white-space: normal; box-shadow: 2px 0 4px rgba(0,0,0,.15); }
            .jv-table .jv-sticky.jv-name { font-size: 12.5px; }
            .jv-nav .btn { padding: 6px 10px; }
            .jv-nav .jv-label { font-size: 14px; padding: 6px 2px; }
        }

        /* Tablet and small desktop */
        @media (max-width: 992px) {
            .tiles { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 768px) {
            .welcome-banner { padding: 14px 14px 16px; }
            /* On medium/smaller screens show 2 columns for better touch targets */
            .tiles { grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 12px; width: calc(100% - 24px); max-width: 100%; }
            .menu-tile { min-height: 140px; }
            .menu-icon { font-size: 2.4rem; margin-bottom: 12px; }
            .menu-label { font-size: 12px; }
            .modal-dialog { margin: 10px; width: calc(100% - 20px); }
            .modal-content { border-radius: 8px; }
            .welcome-dashboard-btn { min-width: 0; }
        }

        /* Phones: keep 2 columns on most phones to match the visual layout; collapse to 1 on very small devices */
        @media (max-width: 420px) {
            .tiles { grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 10px; width: calc(100% - 20px); }
            .menu-tile { min-height: 120px; border-radius: 8px; }
            .menu-icon { font-size: 2rem; margin-bottom: 10px; }
            .menu-label { font-size: 12px; bottom: 10px; }
            .topbar { padding: 8px; }
            .logo img { height: 32px; }
        }

        /* Very small screens (older phones) */
        @media (max-width: 360px) {
            .tiles { grid-template-columns: 1fr; }
            .menu-tile { min-height: 110px; }
        }

        /* Stack controls and tighten banner on small devices */
        @media (max-width: 480px) {
            .menu-controls { flex-direction: column; align-items: stretch; gap: 8px; }
            .menu-search { order: 1; }
            .menu-dashboard-cta { order: 2; }
            .user-area { order: 3; justify-content: space-between; }
            .welcome-banner { padding: 14px 10px; border-radius: 10px; }
            .welcome-banner h2 { font-size: 18px; line-height: 1.15; }
            .welcome-banner p { display: none; } /* hide subtitle to reduce clutter */
            .menu-search input { padding: 10px; font-size: 14px; }
            /* make controls feel like a compact card */
            .menu-controls { background: rgba(255,255,255,0.02); padding: 10px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.03); }
            .user-area { display:flex; justify-content:space-between; align-items:center; }
            .user-area .info { text-align:left; }
            .menu-search { width:100%; }
            .menu-dashboard-cta .welcome-dashboard-btn { width: 100%; }
            /* On phones hide the long date and show compact time only */
            .date-display .date-full { display: none; }
            .date-display .date-compact { display: block; }
        }

        @media (max-width: 992px) {
            .topbar {
                flex-wrap: wrap;
                gap: 10px;
            }

            .topbar-middle {
                order: 3;
                width: 100%;
                justify-content: flex-start;
                padding: 0;
            }

            .emotion-board {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 2px;
            }

            .emotion-picker-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* Hide the centered topbar greeting on very small screens to avoid overlap */
        @media (max-width: 480px) {
            .topbar-center { display: none; }
        }
        
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            0% { opacity: 0; transform: translateX(-20px); }
            100% { opacity: 1; transform: translateX(0); }
        }
        
        .animate-item {
            animation: slideIn 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
        .delay-6 { animation-delay: 0.6s; }
        .delay-7 { animation-delay: 0.7s; }
        .delay-8 { animation-delay: 0.8s; }
        .delay-9 { animation-delay: 0.9s; }
        .delay-10 { animation-delay: 1.0s; }
        .delay-11 { animation-delay: 1.1s; }
        .delay-12 { animation-delay: 1.2s; }
        .delay-13 { animation-delay: 1.3s; }
        .delay-14 { animation-delay: 1.4s; }
        .delay-15 { animation-delay: 1.5s; }
        .delay-16 { animation-delay: 1.6s; }
        .delay-17 { animation-delay: 1.7s; }
        .delay-18 { animation-delay: 1.8s; }
        .delay-19 { animation-delay: 1.9s; }
        .delay-20 { animation-delay: 2.0s; }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <!-- Top Bar -->
        <div class="topbar" style="position:relative;">
            <div class="logo">
                @php
                    $clinicChoice = session('clinic_choice');
                    $logoDark = 'img/logo-belovacorp-bw.png';
                    $logoLight = 'img/logo-belovacorp-bw.png';
                    if ($clinicChoice === 'premiere') {
                        $logoDark = 'img/logo-premiere-bw.png';
                        $logoLight = 'img/logo-premiere.png'; // Make sure this file exists
                    } elseif ($clinicChoice === 'skin') {
                        $logoDark = 'img/logo-belovaskin-bw.png';
                        $logoLight = 'img/logo-belovaskin.png'; // Make sure this file exists
                    } elseif ($clinicChoice === 'dental') {
                        $logoDark = 'img/logo-dental.png';
                        $logoLight = 'img/logo-dental.png';
                    }
                @endphp
                <img src="{{ asset($logoDark) }}" data-logo-dark="{{ asset($logoDark) }}" data-logo-light="{{ asset($logoLight) }}" alt="Belova Logo" id="logo-image">
            </div>
            <div class="topbar-middle">
                <div class="emotion-board" id="user-emotion-board">
                    @php
                        $emotionItems = $activeUserEmotions ?? [];
                    @endphp
                    @forelse($emotionItems as $emotionItem)
                        <span class="emotion-avatar-item {{ (($emotionItem['user_id'] ?? null) === Auth::id()) ? 'is-current-user js-open-emotion-picker' : '' }}" data-user-id="{{ $emotionItem['user_id'] ?? '' }}">
                            <span class="emotion-avatar">
                                @if(!empty($emotionItem['avatar_url']))
                                    <img src="{{ $emotionItem['avatar_url'] }}" alt="{{ $emotionItem['name'] ?? 'User' }}">
                                @else
                                    <span class="emotion-avatar-fallback">{{ $emotionItem['initials'] ?? 'U' }}</span>
                                @endif
                            </span>
                            <span class="emotion-badge" style="background: {{ $emotionItem['color'] ?? '#475569' }};">{{ $emotionItem['emoji'] ?? '🙂' }}</span>
                            <span class="emotion-avatar-tooltip">
                                <span class="name">{{ $emotionItem['name'] ?? 'User' }}</span>
                                <span class="mood">{{ $emotionItem['label'] ?? 'Calm' }}</span>
                            </span>
                        </span>
                    @empty
                        <span class="emotion-chip-empty">Belum ada user emotion aktif</span>
                    @endforelse
                </div>
            </div>
            {{-- <div class="topbar-center" style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); font-size:16px; font-weight:600; color:var(--text-color); white-space:nowrap;">
                Hello, {{ Auth::user()->name ?? '' }}
            </div> --}}
            <div class="topbar-right">
                <div class="date-display" id="date-time-display">
                    <span class="date-full">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }}</span>
                    <span class="date-compact" style="display:none">{{ \Carbon\Carbon::now()->format('H:i') }}</span>
                </div>
                <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="theme-toggle" id="info-update-btn" title="Informasi Update">
                    <i class="fas fa-info-circle"></i>
                </button>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;" id="logout-form">
                    @csrf
                    <button type="submit" class="theme-toggle" title="Logout" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="welcome-banner">
                @php
                    $clinicChoice = session('clinic_choice');
                    $simName = 'SIM Klinik Belova';
                    if ($clinicChoice === 'premiere') {
                        $simName = 'SIM Klinik Premiere Belova';
                    } elseif ($clinicChoice === 'skin') {
                        $simName = 'SIM Klinik Belova Skin';
                    } elseif ($clinicChoice === 'dental') {
                        $simName = 'SIM Klinik Belova Dental Care';
                    }
                @endphp
                <h2><span class="welcome-prefix">Welcome to</span> <span class="sim-name">{{ $simName }}</span></h2>
                <p class="welcome-slogan">Bertumbuh Bersama dengan Niat Baik</p>
            </div>
            
            <div class="menu-grid-wrapper">
                <div class="menu-controls">
                    <div class="menu-search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input id="menuFilter" placeholder="Cari modul" aria-label="Cari modul" />
                    </div>
                    <div class="menu-dashboard-cta">
                        <a href="{{ route('dashboard.index') }}" class="welcome-dashboard-btn">
                            <i class="fas fa-th-large"></i>
                            <span>Open Your Dashboard</span>
                        </a>
                    </div>
                    <div class="user-area">
                        <div class="info profile-trigger profile-trigger-info js-open-profile-modal" tabindex="0" role="button" aria-label="Buka profil karyawan">
                            <div style="font-size:13px; font-weight:700;">{{ Auth::user()->name ?? '' }}</div>
                            <div style="font-size:12px; opacity:0.8;">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        @php
                            $name = trim(Auth::user()->name ?? '');
                            $initials = collect(explode(' ', $name))->filter()->map(function($p){ return strtoupper(substr($p,0,1)); })->take(2)->join('');
                            $emp = Auth::user()->employee ?? null;
                            $dok = Auth::user()->dokter ?? null;
                            $photoUrl = null;
                            try {
                                if ($emp && !empty($emp->photo)) {
                                    $photoUrl = \Illuminate\Support\Facades\Storage::url($emp->photo);
                                } elseif ($dok && !empty($dok->photo)) {
                                    $photoUrl = \Illuminate\Support\Facades\Storage::url($dok->photo);
                                }
                            } catch (\Throwable $e) {
                                $photoUrl = null;
                            }
                        @endphp

                        @if(!empty($photoUrl))
                            <div class="avatar clickable-avatar profile-trigger js-open-profile-modal" title="Klik untuk edit profil" tabindex="0" role="button" aria-label="Buka profil karyawan">
                                <img src="{{ $photoUrl }}" alt="{{ Auth::user()->name ?? '' }}" />
                            </div>
                        @else
                            <div class="avatar-initials clickable-avatar profile-trigger js-open-profile-modal" title="Klik untuk edit profil" tabindex="0" role="button" aria-label="Buka profil karyawan">
                                {{ $initials ?: 'U' }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="tiles">
                @php
                    $userRoles = Auth::user()->roles->pluck('name')->toArray();
                @endphp

                @php
                    $kemitraanSoonCount = 0;
                    try {
                        $kemitraanSoonCount = \App\Models\Workdoc\Kemitraan::whereNotNull('end_date')
                            ->whereDate('end_date', '<=', \Carbon\Carbon::today()->addDays(183))
                            ->count();
                    } catch (\Throwable $e) {
                        $kemitraanSoonCount = 0;
                    }
                @endphp

                @php
                    $dailyJournalDeadlineCount = 0;
                    try {
                        $dailyJournalDeadlineCount = \App\Models\DailyJournalTask::query()
                            ->where('user_id', Auth::id())
                            ->whereNotNull('deadline_date')
                            ->where('status', '!=', 'done')
                            ->count();
                    } catch (\Throwable $e) {
                        $dailyJournalDeadlineCount = 0;
                    }
                @endphp

                <!-- Row 1: ERM, Farmasi, Laboratorium, Beautician, Penilaian Pelanggan -->
                <a href="/erm/rawatjalans" class="menu-tile tile-erm animate-item delay-1" data-filter="erm healthcare patient"
                   @if(!array_intersect($userRoles, ['Dokter','Perawat','Pendaftaran','Admin','Farmasi']))
                       onclick="showRoleWarning(event, 'ERM')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-heartbeat"></i></div>
                        <div class="menu-badge">Patient</div>
                    </div>
                    <div class="menu-title">Electronic Medical Record</div>
                    <div class="menu-sub">ERM - Rawat Jalan</div>
                </a>

                <a href="/erm/eresepfarmasi" class="menu-tile tile-farmasi animate-item delay-2" data-filter="farmasi resep obat pharmacy"
                   @if(!array_intersect($userRoles, ['Farmasi','Admin','Lab','Beautician','Finance']))
                       onclick="showRoleWarning(event, 'Farmasi')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-pills"></i></div>
                        <div class="menu-badge">Obat</div>
                    </div>
                    <div class="menu-title">Farmasi</div>
                    <div class="menu-sub">e-Resep & Stok</div>
                </a>

                <a href="/erm/elab" class="menu-tile tile-laboratorium animate-item delay-3" data-filter="lab pemeriksaan hasil"
                   @if(!array_intersect($userRoles, ['Lab','Admin']))
                       onclick="showRoleWarning(event, 'Laboratorium')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-vials"></i></div>
                        <div class="menu-badge">Lab</div>
                    </div>
                    <div class="menu-title">Laboratorium</div>
                    <div class="menu-sub">Hasil & Sample</div>
                </a>

                {{--
                <a href="/erm/spktindakan" class="menu-tile tile-beautician animate-item delay-4" data-filter="beauty esthetic treatment"
                   @if(!array_intersect($userRoles, ['Beautician','Admin']))
                       onclick="showRoleWarning(event, 'Beautician')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-spa"></i></div>
                        <div class="menu-badge">Service</div>
                    </div>
                    <div class="menu-title">Beautician</div>
                    <div class="menu-sub">Tindakan & Booking</div>
                </a>
                --}}

                <!-- New: Buku Menu (accessible to all users) -->
                <a href="/buku-menu" class="menu-tile tile-dokumen animate-item delay-4" data-filter="buku menu harga daftar menu items">
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-book"></i></div>
                        <div class="menu-badge">Menu</div>
                    </div>
                    <div class="menu-title">Buku Menu</div>
                    <div class="menu-sub">Harga & Daftar Item</div>
                </a>

                <a href="/daily-journal" class="menu-tile tile-daily-journal animate-item delay-5" data-filter="daily journal task planner agenda habit">
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-book-open"></i></div>
                        {{-- <div class="menu-badge">Journal</div> --}}
                    </div>
                    <div class="tile-bell-top" aria-hidden="true">
                        <div class="bell"><i class="fas fa-hourglass-end"></i></div>
                        @if(!empty($dailyJournalDeadlineCount) && $dailyJournalDeadlineCount > 0)
                            <div class="count">{{ $dailyJournalDeadlineCount }}</div>
                        @endif
                    </div>
                    <div class="menu-title">Daily Journal</div>
                    <div class="menu-sub">Task & Status Harian</div>
                </a>

                <a href="/customersurvey" class="menu-tile tile-lab animate-item delay-5" data-filter="survey feedback rating">
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-star-half-alt"></i></div>
                        <div class="menu-badge">Feedback</div>
                    </div>
                    <div class="menu-title">Penilaian Pelanggan</div>
                    <div class="menu-sub">Survey & Rating</div>
                </a>

                <!-- Row 2: HRD, Dokumen Kerja, Laporan, Marketing, Finance -->
                     <a href="/hrd" class="menu-tile tile-hrd animate-item delay-6" data-filter="hrd staff employee"
                         @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Head Manager','Employee','Finance','Admin']))
                       onclick="showRoleWarning(event, 'HRD')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-user-friends"></i></div>
                        <div class="menu-badge">Team</div>
                    </div>
                    <div class="menu-title">HRD</div>
                    <div class="menu-sub">Manajemen Karyawan</div>
                </a>

                     <a href="/workdoc" class="menu-tile tile-dokumen animate-item delay-7" data-filter="dokumen workdoc files"
                         @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Head Manager','Employee','Admin']))
                       onclick="showRoleWarning(event, 'Dokumen Kerja')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-folder-open"></i></div>
                    </div>
                    <div class="tile-bell-top" aria-hidden="true">
                        <div class="bell"><i class="fas fa-bell"></i></div>
                        @if(!empty($kemitraanSoonCount) && $kemitraanSoonCount > 0)
                            <div class="count">{{ $kemitraanSoonCount }}</div>
                        @endif
                    </div>
                    <div class="menu-title">Dokumen Kerja</div>
                    <div class="menu-sub">SOP & Template</div>
                </a>

                     <a href="/laporan" class="menu-tile tile-laporan animate-item delay-8" data-filter="laporan reports analytics"
                         @if(!array_intersect($userRoles, ['Manager','Head Manager','Hrd','Admin','Finance','Farmasi']))
                       onclick="showRoleWarning(event, 'Laporan')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="menu-badge">Report</div>
                    </div>
                    <div class="menu-title">Laporan</div>
                    <div class="menu-sub">Statistik & Export</div>
                </a>

                <a href="/marketing/dashboard" class="menu-tile tile-marketing animate-item delay-9" data-filter="marketing campaign ads"
                   @if(!array_intersect($userRoles, ['Marketing','Admin','Finance']))
                       onclick="showRoleWarning(event, 'Marketing')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="menu-badge">Growth</div>
                    </div>
                    <div class="menu-title">Marketing</div>
                    <div class="menu-sub">Kampanye & Leads</div>
                </a>

                @php
                    $isKasir = in_array('Kasir', $userRoles);
                    $financeHref = $isKasir ? '/finance/billing' : '/finance/pengajuan-dana';
                @endphp
                <a href="{{ $financeHref }}" class="menu-tile tile-finance animate-item delay-10" data-filter="finance billing kasir">
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-coins"></i></div>
                        <div class="menu-badge">Billing</div>
                    </div>
                    <div class="menu-title">Finance</div>
                    <div class="menu-sub">Tagihan & Pembayaran</div>
                </a>

                <!-- Row 3: remaining tiles -->
                <a href="/inventory" class="menu-tile tile-inventory animate-item delay-11" data-filter="inventory stok gudang"
                   @if(!array_intersect($userRoles, ['Inventaris','Admin','Finance']))
                       onclick="showRoleWarning(event, 'Inventory')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-box"></i></div>
                        <div class="menu-badge">Stock</div>
                    </div>
                    <div class="menu-title">Inventory</div>
                    <div class="menu-sub">Barang & Persediaan</div>
                </a>

                <a href="{{ route('rnd.dashboard') }}" class="menu-tile tile-rnd animate-item delay-12" data-filter="rnd research development master brand vendor bahan aktif"
                   @if(!array_intersect($userRoles, ['Admin','Rnd','rnd','RND']))
                       onclick="showRoleWarning(event, 'RND')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-atom"></i></div>
                        <div class="menu-badge">R&D</div>
                    </div>
                    <div class="menu-title">Product R&D</div>
                    <div class="menu-sub">Research and Development</div>
                </a>

                     <a href="/akreditasi" class="menu-tile tile-akreditasi animate-item delay-13" data-filter="akreditasi quality compliance"
                         @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Head Manager','Employee','Admin']))
                       onclick="showRoleWarning(event, 'Akreditasi')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-medal"></i></div>
                        <div class="menu-badge">Quality</div>
                    </div>
                    <div class="menu-title">Akreditasi</div>
                    <div class="menu-sub">Compliance</div>
                </a>

                <a href="/insiden" class="menu-tile tile-insiden animate-item delay-13" data-filter="insiden laporan kecelakaan"
                    @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Head Manager','Employee','Admin']))
                       onclick="showRoleWarning(event, 'INSIDEN')"
                    @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="menu-badge">Alert</div>
                    </div>
                    <div class="menu-title">Laporan Insiden</div>
                    <div class="menu-sub">Keamanan & Laporan</div>
                </a>

                <a href="/bcl" class="menu-tile tile-kos animate-item delay-14" data-filter="bcl kos"
                    @if(!array_intersect($userRoles, ['Kos','Admin']))
                       onclick="showRoleWarning(event, 'BCL')"
                    @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-building"></i></div>
                        <div class="menu-badge">External</div>
                    </div>
                    <div class="menu-title">KOS BCL</div>
                    <div class="menu-sub">Portal Bisnis</div>
                </a>

                <a href="#" class="menu-tile tile-jadwal animate-item delay-15" id="jadwal-menu-tile" data-filter="jadwal schedule kalender">
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="menu-badge">Jadwal</div>
                    </div>
                    <div class="menu-title">Jadwal</div>
                    <div class="menu-sub">Cetak & Download</div>
                </a>
                
                <!-- Events module: marketing event list, patients per event, event billing -->
                <a href="/events" class="menu-tile tile-belova-mengaji animate-item delay-16" id="events-tile" data-filter="events event dashboard agenda billing pasien">
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="menu-badge">Events</div>
                    </div>
                    <div class="menu-title">Events</div>
                    <div class="menu-sub">Event, Pasien &amp; Billing</div>
                </a>
                
                <!-- WiFi Panel -->
                {{-- <a href="https://wifibelova.duckdns.org" target="_blank" rel="noopener noreferrer" class="menu-tile tile-wifi animate-item delay-17" data-filter="wifi jaringan panel"
                    @if(!array_intersect($userRoles, ['Admin']))
                       onclick="showRoleWarning(event, 'WIFI Panel')"
                    @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-wifi"></i></div>
                        <div class="menu-badge">External</div>
                    </div>
                    <div class="menu-title">WiFi Panel</div>
                    <div class="menu-sub">Akses Pengaturan WiFi</div>
                </a> --}}

                {{--
                <a href="/hrd/joblist" class="menu-tile tile-joblist animate-item delay-17" data-filter="joblist task pekerjaan"
                   @if(!array_intersect($userRoles, ['Hrd','Manager','Employee','Admin']))
                       onclick="showRoleWarning(event, 'Job List')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-tasks"></i></div>
                    </div>

                    <div class="tile-bell-top" aria-hidden="true">
                        <div class="bell"><i class="fas fa-bell"></i></div>
                        @if(!empty($inProgressCount) && $inProgressCount > 0)
                            <div class="count">{{ $inProgressCount }}</div>
                        @endif
                    </div>

                    <div class="menu-title">Job List</div>
                    <div class="menu-sub">Tugas & Deadline</div>
                </a>
                --}}
                
                <!-- Admin Panel -->
                <a href="/admin/users" class="menu-tile tile-admin animate-item delay-18" data-filter="admin users pengaturan"
                   @if(!array_intersect($userRoles, ['Admin']))
                       onclick="showRoleWarning(event, 'Admin Panel')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-user-shield"></i></div>
                        <div class="menu-badge">Admin</div>
                    </div>
                    <div class="menu-title">Admin Panel</div>
                    <div class="menu-sub">Manajemen Pengguna</div>
                </a>
                @php
                    // Allow access only to users with role 'Satusehat' or 'Admin'
                    $hasSatusehatAccess = count(array_intersect($userRoles, ['Satusehat','Admin'])) > 0;
                    $hasCeoDashboardAccess = count(array_intersect($userRoles, ['Ceo','CEO','Head Manager','Manager','Hrd','Admin'])) > 0;
                @endphp
                <a href="{{ $hasSatusehatAccess ? '/satusehat' : '#' }}" class="menu-tile tile-satusehat animate-item delay-19" id="satusehat-tile" data-filter="satusehat bpjs kesehatan"
                   @if(!$hasSatusehatAccess)
                       onclick="showRoleWarning(event, 'Satusehat')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-hospital"></i></div>
                        <div class="menu-badge">SatuSehat</div>
                    </div>
                    <div class="menu-title">SatuSehat</div>
                    <div class="menu-sub">Integrasi Data Kesehatan</div>
                </a>
                <a href="{{ $hasCeoDashboardAccess ? '/ceo-dashboard' : '#' }}" class="menu-tile tile-ceodashboard animate-item delay-20" id="ceodashboard-tile" data-filter="ceo dashboard executive analytics statistik"
                   @if(!$hasCeoDashboardAccess)
                       onclick="showRoleWarning(event, 'CEO Dashboard')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="menu-badge">CEO</div>
                    </div>
                    <div class="menu-title">CEO Dashboard</div>
                    <div class="menu-sub">Executive Summary & KPI</div>
                </a>
                <!-- Assessment module (added last) -->
                <a href="{{ route('kpi.evaluatees.index') }}" class="menu-tile tile-hrd animate-item delay-21" id="assessment-tile" data-filter="assessment penilaian kpi"
                   @if(!array_intersect($userRoles, ['Employee','Manager','Hrd','Ceo','Head Manager','Admin']))
                       onclick="showRoleWarning(event, 'Assessment')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div class="menu-badge">Assessment</div>
                    </div>
                    <div class="menu-title">Assessment</div>
                    <div class="menu-sub">Employee Assessments & Scoring</div>
                </a>
                </div>
            </div>
            </div>
            <div class="modal fade" id="emotionPickerModal" tabindex="-1" role="dialog" aria-labelledby="emotionPickerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="emotionPickerModalLabel">Ganti Mood</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">Pilih mood terbaru untuk ditampilkan di topbar.</p>
                            <div class="emotion-picker-selected" id="emotion-picker-selected">
                                <span class="emoji">😌</span>
                                <span class="text">Mood terpilih: Calm</span>
                            </div>
                            <div class="emotion-picker-grid" id="emotion-picker-grid">
                                @foreach(($emotionOptions ?? []) as $emotionKey => $emotionOption)
                                    <button type="button" class="emotion-picker-option {{ ($currentUserEmotion ?? 'calm') === $emotionKey ? 'active' : '' }}" data-emotion="{{ $emotionKey }}" data-label="{{ $emotionOption['label'] }}" data-emoji="{{ $emotionOption['emoji'] }}">
                                        <span class="emoji">{{ $emotionOption['emoji'] }}</span>
                                        <span class="label">{{ $emotionOption['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" id="selected-emotion-input" value="{{ $currentUserEmotion ?? 'calm' }}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-primary" id="save-emotion-btn">Simpan Mood</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content" id="profileModalContent">
                        <div class="text-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Jadwal Modal (tampilan HTML, ramah HP) -->
            <div class="modal fade" id="jadwalModal" tabindex="-1" role="dialog" aria-labelledby="jadwalModalLabel" aria-hidden="true">
                <div class="modal-dialog jv-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h5 class="modal-title" id="jadwalModalLabel"><i class="fas fa-calendar-alt mr-1"></i> Jadwal</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body p-2 p-md-3">
                            <ul class="nav nav-pills nav-justified jv-tabs mb-2" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#jv-karyawan" data-jv="karyawan" role="tab">Karyawan</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#jv-dokter" data-jv="dokter" role="tab">Dokter</a></li>
                            </ul>
                            <div class="tab-content">
                                @foreach(['karyawan' => 'Cari karyawan...', 'dokter' => 'Cari dokter...'] as $jvKey => $jvPlaceholder)
                                <div class="tab-pane fade {{ $jvKey === 'karyawan' ? 'show active' : '' }}" id="jv-{{ $jvKey }}" role="tabpanel" data-jv-pane="{{ $jvKey }}">
                                    <div class="jv-nav">
                                        <button type="button" class="btn btn-outline-secondary jv-prev" aria-label="Minggu sebelumnya"><i class="fas fa-chevron-left"></i></button>
                                        <button type="button" class="btn btn-link jv-label" title="Kembali ke minggu ini">-</button>
                                        <button type="button" class="btn btn-outline-secondary jv-next" aria-label="Minggu berikutnya"><i class="fas fa-chevron-right"></i></button>
                                        <button type="button" class="btn btn-outline-secondary jv-mode" title="Tampilan seminggu / per hari"><i class="fas fa-table"></i></button>
                                        <button type="button" class="btn btn-primary jv-download" title="Download gambar"><i class="fas fa-download"></i><span class="d-none d-md-inline ml-1">Gambar</span></button>
                                    </div>
                                    <div class="jv-days"></div>
                                    <div class="jv-filters">
                                        @if($jvKey === 'dokter')
                                            <select class="form-control form-control-sm jv-clinic"><option value="">Semua Klinik</option></select>
                                        @endif
                                        <input type="search" class="form-control form-control-sm jv-search" placeholder="{{ $jvPlaceholder }}">
                                    </div>
                                    <div class="jv-body"><div class="jv-empty">Memuat jadwal...</div></div>
                                </div>
                                @endforeach
                            </div>
                            <canvas id="jadwalPdfCanvas" style="display:none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Footer -->
        <footer class="footer">
            &copy; 2025 - Belova Corp
        </footer>
    </div>

    @include('partials.system_update_modal')

    <!-- jQuery and core JS -->
    <script src="{{ asset('dastone/default/assets/js/jquery.min.js')}}"></script>
    <script src="{{ asset('dastone/default/assets/js/bootstrap.bundle.min.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // Configure pdf.js worker to avoid deprecated API warning and enable worker usage
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>
    <script>
    function showRoleWarning(e, modul) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Akses Ditolak',
            text: 'Anda tidak memiliki akses ke ' + modul + '.',
            confirmButtonText: 'OK'
        });
    }
    
    function showComingSoon(e, modulName) {
        e.preventDefault();
        Swal.fire({
            icon: 'info',
            title: modulName,
            text: 'Fitur ini akan segera hadir. Nantikan pembaruan berikutnya!',
            confirmButtonText: 'Tutup'
        });
    }
    </script>
    <script>
    // Logout confirmation
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('logout-btn');
        const logoutForm = document.getElementById('logout-form');
        if (logoutBtn && logoutForm) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Logout',
                    text: 'Apakah Anda yakin ingin logout?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Logout',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        logoutForm.submit();
                    }
                });
            });
        }
    });
    </script>
    <!-- Theme Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const htmlElement = document.documentElement;
            const themeIcon = themeToggleBtn.querySelector('i');
            const bootstrapStyle = document.getElementById('bootstrap-style');
            const appStyle = document.getElementById('app-style');
            const logoImage = document.getElementById('logo-image');
            
            // Check for saved theme preference or use default dark theme
            const savedTheme = localStorage.getItem('belova-theme') || 'dark';
            applyTheme(savedTheme);
            
            // Theme toggle button event
            themeToggleBtn.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                applyTheme(newTheme);
                localStorage.setItem('belova-theme', newTheme);
            });
            
            function applyTheme(theme) {
                htmlElement.setAttribute('data-theme', theme);
                // Update icon
                if (theme === 'dark') {
                    themeIcon.className = 'fas fa-sun';
                    bootstrapStyle.href = "{{ asset('dastone/default/assets/css/bootstrap-dark.min.css') }}";
                    appStyle.href = "{{ asset('dastone/default/assets/css/app-dark.min.css') }}";
                    // Change logo to dark version
                    if (logoImage) logoImage.src = logoImage.getAttribute('data-logo-dark');
                } else {
                    themeIcon.className = 'fas fa-moon';
                    bootstrapStyle.href = "{{ asset('dastone/default/assets/css/bootstrap.min.css') }}";
                    appStyle.href = "{{ asset('dastone/default/assets/css/app.min.css') }}";
                    // Change logo to light version
                    if (logoImage) logoImage.src = logoImage.getAttribute('data-logo-light');
                }
            }
            
            // Live date-time update
            function updateDateTime() {
                const now = new Date();
                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const dayName = days[now.getDay()];
                const day = String(now.getDate()).padStart(2, '0');
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const formattedFull = `${dayName}, ${day} ${month} ${year} ${hours}:${minutes}:${seconds}`;
                const formattedCompact = `${hours}:${minutes}`;
                const el = document.getElementById('date-time-display');
                if (el) {
                    const full = el.querySelector('.date-full');
                    const compact = el.querySelector('.date-compact');
                    if (full) full.textContent = formattedFull;
                    if (compact) compact.textContent = formattedCompact;
                }
            }
            setInterval(updateDateTime, 1000);
            updateDateTime();
        });
    </script>
    <script>
    $(document).ready(function() {
        var currentUserId = {{ (int) Auth::id() }};
        var currentUserEmotion = @json($currentUserEmotion ?? 'calm');
        var emotionCatalog = @json($emotionOptions ?? []);
        var hasEmployeeProfile = {{ Auth::user()->employee ? 'true' : 'false' }};
        var profileModalUrl = '{{ route('hrd.employee.profile.modal') }}';

        function defaultProfileModalState() {
            return '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>';
        }

        function bindCustomFileInputLabels(scope) {
            $(scope).find('.custom-file-input').off('change.profile').on('change.profile', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).siblings('.custom-file-label').addClass('selected').html(fileName || 'Pilih file...');
            });
        }

        function initProfileFormSubmission() {
            var $form = $('#profileUpdateForm');
            if (!$form.length) {
                return;
            }

            $form.off('submit.profile').on('submit.profile', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var $submitButton = $form.find('button[type="submit"]');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                        $submitButton.attr('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#editProfileModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true,
                                willClose: function() {
                                    window.location.reload();
                                }
                            }).then(function(result) {
                                if (result.value) {
                                    window.location.reload();
                                }
                            });
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Gagal memperbarui profil.'
                        });
                    },
                    error: function(xhr) {
                        var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                        var messages = [];

                        $.each(errors, function(key, value) {
                            if (Array.isArray(value) && value.length) {
                                messages.push(value[0]);
                            }
                        });

                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: messages.length ? messages.join('<br>') : 'Terjadi kesalahan saat menyimpan profil.'
                        });
                    },
                    complete: function() {
                        $submitButton.html('Simpan Perubahan');
                        $submitButton.attr('disabled', false);
                    }
                });
            });
        }

        function openProfileModal() {
            if (!hasEmployeeProfile) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Profil tidak tersedia',
                    text: 'Data karyawan tidak ditemukan',
                    confirmButtonText: 'OK'
                });
                return;
            }

            $('#profileModalContent').html(defaultProfileModalState());

            $.ajax({
                url: profileModalUrl,
                type: 'GET',
                success: function(response) {
                    $('#profileModalContent').html(response);
                    bindCustomFileInputLabels('#profileModalContent');
                    initProfileFormSubmission();
                    $('#editProfileModal').modal('show');
                },
                error: function(xhr) {
                    var message = 'Form profil gagal dimuat. Silakan coba lagi.';
                    if (xhr.status === 404 && xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'warning',
                        title: 'Profil tidak tersedia',
                        text: message,
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        function renderEmotionBoard(items) {
            var $board = $('#user-emotion-board');
            if (!$board.length) {
                return;
            }

            if (!Array.isArray(items) || items.length === 0) {
                $board.html('<span class="emotion-chip-empty">Belum ada user emotion aktif</span>');
                return;
            }

            var html = items.map(function(item) {
                var color = item.color || '#475569';
                var emoji = item.emoji || '🙂';
                var name = item.name || 'User';
                var label = item.label || 'Calm';
                var avatarUrl = item.avatar_url || '';
                var initials = item.initials || 'U';
                var isCurrentUser = Number(item.user_id || 0) === currentUserId;
                var avatarHtml = avatarUrl
                    ? '<img src="' + avatarUrl + '" alt="' + name + '">' 
                    : '<span class="emotion-avatar-fallback">' + initials + '</span>';
                return '<span class="emotion-avatar-item ' + (isCurrentUser ? 'is-current-user js-open-emotion-picker' : '') + '" data-user-id="' + (item.user_id || '') + '">'
                    + '<span class="emotion-avatar">' + avatarHtml + '</span>'
                    + '<span class="emotion-badge" style="background:' + color + ';">' + emoji + '</span>'
                        + '<span class="emotion-avatar-tooltip">'
                        + '<span class="name">' + labelSafe(name) + '</span>'
                        + '<span class="mood">' + labelSafe(label) + '</span>'
                        + '</span>'
                    + '</span>';
            }).join('');

            $board.html(html);
        }

        function heartbeatUserEmotions() {
            $.ajax({
                url: '{{ route('user-emotions.heartbeat') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    renderEmotionBoard(response.data || []);
                }
            });
        }

        function labelSafe(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setSelectedEmotion(emotion) {
            currentUserEmotion = emotion;
            $('#selected-emotion-input').val(emotion);
            $('.emotion-picker-option').removeClass('active');
            $('.emotion-picker-option[data-emotion="' + emotion + '"]').addClass('active');

            var emotionMeta = emotionCatalog[emotion] || emotionCatalog.calm || { label: 'Calm', emoji: '😌' };
            $('#emotion-picker-selected .emoji').text(emotionMeta.emoji || '😌');
            $('#emotion-picker-selected .text').text('Mood terpilih: ' + (emotionMeta.label || 'Calm'));
        }

        $(document).on('click', '.js-open-emotion-picker', function() {
            setSelectedEmotion(currentUserEmotion || $('#selected-emotion-input').val() || 'calm');
            $('#emotionPickerModal').modal('show');
        });

        $(document).on('click', '.js-open-profile-modal', function(e) {
            e.preventDefault();
            openProfileModal();
        });

        $(document).on('keydown', '.js-open-profile-modal', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openProfileModal();
            }
        });

        $(document).on('click', '.emotion-picker-option', function() {
            setSelectedEmotion($(this).data('emotion'));
        });

        $('#save-emotion-btn').on('click', function() {
            var $button = $(this);
            var emotion = $('#selected-emotion-input').val() || 'calm';

            $button.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: '{{ route('user-emotions.update') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    emotion: emotion
                },
                success: function(response) {
                    currentUserEmotion = emotion;
                    renderEmotionBoard(response.data || []);
                    $('#emotionPickerModal').modal('hide');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Simpan Mood');
                }
            });
        });

        renderEmotionBoard(@json($activeUserEmotions ?? []));
        heartbeatUserEmotions();
        setInterval(heartbeatUserEmotions, 60000);

        // Helper to render all pages of a PDF and download each as PNG
        async function renderAndDownloadPdfPages(url, baseFilename, canvasElement) {
            try {
                const loadingTask = pdfjsLib.getDocument(url);
                const pdf = await loadingTask.promise;
                const total = pdf.numPages;
                const canvas = canvasElement || document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                for (let pageNum = 1; pageNum <= total; pageNum++) {
                    const page = await pdf.getPage(pageNum);
                    const viewport = page.getViewport({ scale: 1.5 });
                    canvas.width = Math.round(viewport.width);
                    canvas.height = Math.round(viewport.height);
                    await page.render({ canvasContext: ctx, viewport: viewport }).promise;

                    // Convert to blob to avoid memory/url size limits and trigger download
                    await new Promise((resolve) => {
                        canvas.toBlob(function(blob) {
                            if (!blob) return resolve();
                            const link = document.createElement('a');
                            const pageSuffix = total > 1 ? ('_page' + pageNum) : '';
                            const filename = baseFilename + pageSuffix + '.png';
                            const urlBlob = URL.createObjectURL(blob);
                            link.href = urlBlob;
                            link.download = filename;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            // Revoke object URL after a small delay to ensure download started
                            setTimeout(() => URL.revokeObjectURL(urlBlob), 1000);
                            resolve();
                        }, 'image/png');
                    });
                }
            } catch (err) {
                console.error('Error rendering PDF pages', err);
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat memproses PDF.' });
            }
        }

        // Helper to render all pages into a single tall image and download as PNG
        async function renderPdfToSingleImage(url, baseFilename, canvasElement) {
            try {
                const loadingTask = pdfjsLib.getDocument(url);
                const pdf = await loadingTask.promise;
                const total = pdf.numPages;

                // First, measure each page viewport to determine total height and max width
                const viewports = [];
                let totalHeight = 0;
                let maxWidth = 0;
                const scale = 1.5;
                for (let i = 1; i <= total; i++) {
                    const page = await pdf.getPage(i);
                    const vp = page.getViewport({ scale });
                    viewports.push(vp);
                    totalHeight += Math.round(vp.height);
                    maxWidth = Math.max(maxWidth, Math.round(vp.width));
                }

                // Safety: browsers have limits on canvas size. If too big, fallback to per-page downloads.
                const MAX_CANVAS_DIMENSION = 32767; // conservative limit for many browsers
                if (maxWidth > MAX_CANVAS_DIMENSION || totalHeight > MAX_CANVAS_DIMENSION) {
                    console.warn('Combined image too large, falling back to per-page downloads');
                    return renderAndDownloadPdfPages(url, baseFilename, canvasElement);
                }

                const canvas = canvasElement || document.createElement('canvas');
                canvas.width = maxWidth;
                canvas.height = totalHeight;
                const ctx = canvas.getContext('2d');

                // Render each page sequentially and draw to the combined canvas
                let yOffset = 0;
                for (let i = 1; i <= total; i++) {
                    const page = await pdf.getPage(i);
                    const vp = viewports[i - 1];

                    // Render page to a temporary canvas to avoid needing different widths
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = Math.round(vp.width);
                    tempCanvas.height = Math.round(vp.height);
                    const tempCtx = tempCanvas.getContext('2d');
                    await page.render({ canvasContext: tempCtx, viewport: vp }).promise;

                    // Draw the temp canvas onto the big canvas at current offset
                    ctx.drawImage(tempCanvas, 0, yOffset, tempCanvas.width, tempCanvas.height);
                    yOffset += tempCanvas.height;
                }

                // Convert combined canvas to blob and download
                await new Promise((resolve) => {
                    canvas.toBlob(function(blob) {
                        if (!blob) return resolve();
                        const link = document.createElement('a');
                        const filename = baseFilename + '.png';
                        const urlBlob = URL.createObjectURL(blob);
                        link.href = urlBlob;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        setTimeout(() => URL.revokeObjectURL(urlBlob), 1000);
                        resolve();
                    }, 'image/png');
                });
            } catch (err) {
                console.error('Error creating combined PDF image, falling back to per-page', err);
                // Fallback to per-page download
                return renderAndDownloadPdfPages(url, baseFilename, canvasElement);
            }
        }

        // System update modal
        if (!localStorage.getItem('systemUpdateModalShown')) {
            $('#systemUpdateModal').modal('show');
            localStorage.setItem('systemUpdateModalShown', '1');
        }
        $('#info-update-btn').on('click', function() {
            $('#systemUpdateModal').modal('show');
        });
        // ===== Jadwal viewer (HTML, ramah HP) =====
        (function () {
            var URL_KARYAWAN = "{{ route('hrd.schedule.view_data') }}";
            var URL_DOKTER = "{{ route('hrd.dokter-schedule.view_data') }}";
            var isMobile = function () { return window.matchMedia('(max-width: 767.98px)').matches; };

            function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
            function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
            function mondayOf(d) { d = new Date(d); var day = d.getDay(); d.setDate(d.getDate() + ((day === 0 ? -6 : 1) - day)); return ymd(d); }
            function addDays(s, n) { var p = s.split('-'); var d = new Date(+p[0], +p[1] - 1, +p[2]); d.setDate(d.getDate() + n); return ymd(d); }
            function contrast(hex) {
                var c = (hex || '').replace('#', '');
                if (c.length === 3) c = c[0] + c[0] + c[1] + c[1] + c[2] + c[2];
                if (c.length !== 6) return '#000';
                var r = parseInt(c.substr(0, 2), 16), g = parseInt(c.substr(2, 2), 16), b = parseInt(c.substr(4, 2), 16);
                return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#000' : '#fff';
            }
            function chip(text, color, title) {
                return '<span class="jv-chip" style="background:' + esc(color) + ';color:' + contrast(color) + '"' + (title ? ' title="' + esc(title) + '"' : '') + '>' + esc(text) + '</span>';
            }
            // "09:00","19:00" -> "09.00 – 19.00" (satu baris, format Indonesia)
            function jam(start, end) { return String(start).replace(':', '.') + ' – ' + String(end).replace(':', '.'); }
            function jamRange(t) { var p = String(t).split('–'); return p.length === 2 ? jam(p[0], p[1]) : t; }
            function liburChip(label) { return '<span class="jv-chip jv-libur">' + esc(label) + '</span>'; }

            // Isi sel karyawan untuk satu hari (compact = tabel mingguan, selain itu = daftar harian)
            function karyawanCell(day, compact) {
                if (!day) return '<span class="jv-off">Libur</span>';
                if (day.libur) return liburChip(day.libur);
                return day.shifts.map(function (s) {
                    if (compact) return chip(jam(s.start, s.end), s.color, s.name);
                    return '<span class="jv-chip jv-chip2" style="background:' + esc(s.color) + ';color:' + contrast(s.color) + '">' +
                        '<small>' + esc(s.name) + '</small>' + esc(jam(s.start, s.end)) + '</span>';
                }).join('');
            }
            // Sel ringkas untuk tabel mingguan: "09.00 – 19.00" satu baris
            function miniCell(day) {
                if (!day) return '<div class="jv-mini off">–</div>';
                if (day.libur) return '<div class="jv-mini libur">' + esc(day.libur) + '</div>';
                return day.shifts.map(function (s) {
                    return '<div class="jv-mini" style="background:' + esc(s.color) + ';color:' + contrast(s.color) + '" title="' + esc(s.name) + '">' +
                        esc(jam(s.start, s.end)) + '</div>';
                }).join('');
            }

            function createViewer(key) {
                var $pane = $('[data-jv-pane="' + key + '"]');
                var state = { start: mondayOf(new Date()), day: null, mode: null, data: null };

                function load() {
                    var params = { start_date: state.start };
                    if (key === 'dokter') params.clinic_id = $pane.find('.jv-clinic').val() || '';
                    $pane.find('.jv-body').html('<div class="jv-empty"><span class="spinner-border spinner-border-sm mr-1"></span> Memuat jadwal...</div>');
                    $.getJSON(key === 'dokter' ? URL_DOKTER : URL_KARYAWAN, params)
                        .done(function (data) {
                            state.data = data;
                            var today = data.dates.filter(function (d) { return d.today; })[0];
                            if (!state.day || !data.dates.some(function (d) { return d.date === state.day; })) {
                                state.day = today ? today.date : data.dates[0].date;
                            }
                            if (key === 'dokter' && data.clinicOptions) {
                                var $sel = $pane.find('.jv-clinic');
                                if ($sel.find('option').length <= 1) {
                                    data.clinicOptions.forEach(function (k) { $sel.append('<option value="' + k.id + '">' + esc(k.nama) + '</option>'); });
                                }
                            }
                            render();
                        })
                        .fail(function () {
                            $pane.find('.jv-body').html('<div class="jv-empty text-danger">Gagal memuat jadwal.</div>');
                        });
                }

                function renderDays() {
                    var html = state.data.dates.map(function (d) {
                        var cls = 'jv-day' + (state.mode === 'day' && d.date === state.day ? ' active' : '') + (d.today ? ' today' : '') + (d.sunday || d.holiday ? ' red' : '');
                        return '<button type="button" class="' + cls + '" data-date="' + d.date + '"' + (d.holiday ? ' title="' + esc(d.holiday) + '"' : '') + '>' +
                            '<small>' + esc(d.day) + '</small><b>' + esc(d.num) + '</b></button>';
                    }).join('');
                    $pane.find('.jv-days').html(html);
                    // Tombol mode: tampilkan ikon tujuan (tabel seminggu <-> daftar harian)
                    $pane.find('.jv-mode').toggleClass('active', state.mode === 'week')
                        .html(state.mode === 'week' ? '<i class="fas fa-list"></i>' : '<i class="fas fa-table"></i>');
                    $pane.find('.jv-label').text(state.data.label);
                }

                function matches(name) {
                    var q = ($pane.find('.jv-search').val() || '').trim().toLowerCase();
                    return !q || (name || '').toLowerCase().indexOf(q) !== -1;
                }

                function render() {
                    if (!state.data) return;
                    if (!state.mode) state.mode = isMobile() ? 'day' : 'week';
                    renderDays();
                    var groups = key === 'dokter' ? state.data.kliniks : state.data.divisions;
                    $pane.find('.jv-body').html(state.mode === 'day' ? renderDay(groups) : renderWeek(groups));
                    if (state.mode === 'week') {
                        // Geser tabel supaya kolom hari yang dipilih langsung terlihat di samping kolom nama
                        var wrap = $pane.find('.jv-table-wrap')[0];
                        var idx = state.data.dates.findIndex(function (d) { return d.date === state.day; });
                        var th = wrap && wrap.querySelectorAll('thead th')[idx + 1];
                        var sticky = wrap && wrap.querySelector('thead th.jv-sticky');
                        if (th && sticky && idx > 0) wrap.scrollLeft = th.offsetLeft - sticky.offsetWidth;
                    }
                }

                function personName(p) {
                    return (key === 'dokter' ? '<span class="jv-dot" style="background:' + esc(p.color) + '"></span>' : '') +
                        esc(p.nama) + (p.posisi ? '<small>' + esc(p.posisi) + '</small>' : '');
                }

                function renderDay(groups) {
                    var dayInfo = state.data.dates.filter(function (d) { return d.date === state.day; })[0];
                    var html = '<div class="jv-day-title">' + esc(dayInfo.dayLong) + (dayInfo.holiday ? ' <span class="badge badge-danger">' + esc(dayInfo.holiday) + '</span>' : '') + '</div>';
                    var any = false;
                    groups.forEach(function (g) {
                        var people = key === 'dokter' ? g.dokters : g.employees;
                        var rows = people.filter(function (p) {
                            return matches(p.nama) && (key === 'dokter' ? !!p.days[state.day] : true);
                        }).map(function (p) {
                            var right = key === 'dokter' ? chip(jamRange(p.days[state.day]), p.color) : karyawanCell(p.days[state.day], false);
                            var me = key === 'karyawan' && p.id === state.data.me ? ' me' : '';
                            return '<div class="jv-row' + me + '"><div class="jv-name">' + personName(p) + '</div><div class="jv-val">' + right + '</div></div>';
                        }).join('');
                        if (rows) { any = true; html += (g.name ? '<div class="jv-group">' + esc(g.name) + '</div>' : '') + rows; }
                    });
                    return any ? html : html + '<div class="jv-empty">Tidak ada jadwal ' + (key === 'dokter' ? 'dokter ' : '') + 'di hari ini.</div>';
                }

                function renderWeek(groups) {
                    var dates = state.data.dates;
                    var html = '<div class="jv-table-wrap"><table class="jv-table"><thead><tr><th class="jv-sticky">' + (key === 'dokter' ? 'Dokter' : 'Karyawan') + '</th>' +
                        dates.map(function (d) {
                            return '<th class="' + (d.today ? 'today' : '') + (d.sunday || d.holiday ? ' red' : '') + '"' + (d.holiday ? ' title="' + esc(d.holiday) + '"' : '') + '>' + esc(d.day) + '<br><small>' + esc(d.num) + '</small></th>';
                        }).join('') + '</tr></thead><tbody>';
                    var any = false;
                    groups.forEach(function (g) {
                        var people = (key === 'dokter' ? g.dokters : g.employees).filter(function (p) { return matches(p.nama); });
                        if (!people.length) return;
                        any = true;
                        if (g.name) html += '<tr class="jv-group-row"><td colspan="' + (dates.length + 1) + '"><span class="jv-group-label">' + esc(g.name) + '</span></td></tr>';
                        people.forEach(function (p) {
                            var me = key === 'karyawan' && p.id === state.data.me ? ' class="me"' : '';
                            html += '<tr' + me + '><td class="jv-sticky jv-name">' + personName(p) + '</td>';
                            dates.forEach(function (d) {
                                // Jam mulai/selesai bertumpuk supaya 7 hari muat di layar HP
                                var v;
                                if (key === 'dokter') {
                                    var t = p.days[d.date];
                                    v = t ? '<div class="jv-mini" style="background:' + esc(p.color) + ';color:' + contrast(p.color) + '">' + esc(jamRange(t)) + '</div>' : '<div class="jv-mini off">–</div>';
                                } else {
                                    v = miniCell(p.days[d.date]);
                                }
                                html += '<td class="' + (d.today ? 'today' : '') + '">' + v + '</td>';
                            });
                            html += '</tr>';
                        });
                    });
                    html += '</tbody></table></div>';
                    return any ? html : '<div class="jv-empty">Tidak ada jadwal minggu ini.</div>';
                }

                // Events
                $pane.on('click', '.jv-prev', function () { state.start = addDays(state.start, -7); state.day = null; load(); });
                $pane.on('click', '.jv-next', function () { state.start = addDays(state.start, 7); state.day = null; load(); });
                $pane.on('click', '.jv-label', function () { state.start = mondayOf(new Date()); state.day = null; load(); });
                $pane.on('click', '.jv-day', function () {
                    state.mode = 'day';
                    state.day = $(this).data('date');
                    render();
                });
                $pane.on('click', '.jv-mode', function () {
                    state.mode = state.mode === 'week' ? 'day' : 'week';
                    render();
                });
                $pane.on('input', '.jv-search', render);
                $pane.on('change', '.jv-clinic', load);
                $pane.on('click', '.jv-download', function () {
                    var clinic = $pane.find('.jv-clinic').val();
                    var url = key === 'dokter'
                        ? "{{ route('hrd.dokter-schedule.print') }}?month=" + state.start.slice(0, 7) + (clinic ? '&clinic_id=' + clinic : '')
                        : "{{ route('hrd.schedule.print') }}?start_date=" + state.start;
                    var name = key === 'dokter' ? 'jadwal_dokter_' + state.start.slice(0, 7) : 'jadwal_karyawan_' + state.start;
                    var $btn = $(this).prop('disabled', true);
                    Promise.resolve(renderPdfToSingleImage(url, name, document.getElementById('jadwalPdfCanvas')))
                        .finally(function () { $btn.prop('disabled', false); });
                });

                // Geser kiri/kanan di daftar harian untuk ganti hari (HP)
                var touchX = null;
                $pane.on('touchstart', '.jv-body', function (e) { touchX = e.originalEvent.touches[0].clientX; });
                $pane.on('touchend', '.jv-body', function (e) {
                    if (touchX === null || state.mode !== 'day' || !state.data) return;
                    var dx = e.originalEvent.changedTouches[0].clientX - touchX;
                    touchX = null;
                    if (Math.abs(dx) < 60) return;
                    var idx = state.data.dates.findIndex(function (d) { return d.date === state.day; }) + (dx < 0 ? 1 : -1);
                    if (idx < 0) { state.start = addDays(state.start, -7); state.day = addDays(state.start, 6); load(); return; }
                    if (idx > 6) { state.start = addDays(state.start, 7); state.day = state.start; load(); return; }
                    state.day = state.data.dates[idx].date;
                    render();
                });

                return { load: function () { if (!state.data) load(); } };
            }

            var viewers = { karyawan: createViewer('karyawan'), dokter: createViewer('dokter') };
            // Kolom/header sticky butuh warna latar solid yang sama dengan modal (tema terang/gelap)
            $('#jadwalModal').on('shown.bs.modal', function () {
                var content = this.querySelector('.modal-content');
                content.style.setProperty('--jv-bg', getComputedStyle(content).backgroundColor);
            });
            // Tombol chat melayang menutupi daftar di HP: sembunyikan selama jadwal terbuka
            $('#jadwalModal').on('show.bs.modal', function () { $('body').addClass('jv-open'); })
                .on('hidden.bs.modal', function () { $('body').removeClass('jv-open'); });
            $('#jadwal-menu-tile').on('click', function (e) {
                e.preventDefault();
                $('#jadwalModal').modal('show');
                viewers[$('.jv-tabs .nav-link.active').data('jv')].load();
            });
            $('.jv-tabs a[data-toggle="pill"]').on('shown.bs.tab', function () { viewers[$(this).data('jv')].load(); });
        })();
    });
    </script>
    <script>
    // Small client-side filter for the redesigned menu
    (function(){
        const input = document.getElementById('menuFilter');
        if (!input) return;
        const tiles = Array.from(document.querySelectorAll('.tiles .menu-tile'));

        function normalize(s){ return (s||'').toString().toLowerCase(); }

        function applyFilter() {
            const q = normalize(input.value.trim());
            if (!q) {
                tiles.forEach(t => t.style.display = 'flex');
                return;
            }
            tiles.forEach(t => {
                const title = normalize(t.querySelector('.menu-title')?.textContent);
                const sub = normalize(t.querySelector('.menu-sub')?.textContent);
                const data = normalize(t.getAttribute('data-filter'));
                if (title.includes(q) || sub.includes(q) || data.includes(q)) {
                    t.style.display = 'flex';
                } else {
                    t.style.display = 'none';
                }
            });
        }

        input.addEventListener('input', applyFilter);

        // Keyboard focus shortcut: '/' focuses the search input (unless typing in an input already)
        document.addEventListener('keydown', function(e){
            if (e.key === '/' && document.activeElement.tagName.toLowerCase() !== 'input' && document.activeElement.tagName.toLowerCase() !== 'textarea') {
                e.preventDefault();
                input.focus();
                input.select();
            }
            if (e.key === 'Escape') {
                if (document.activeElement === input) input.blur();
                input.value = '';
                applyFilter();
            }
        });
    })();
    </script>
    @include('partials.global_chat_widget')
</body>
</html>
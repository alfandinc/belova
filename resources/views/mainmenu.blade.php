<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8" />
    <title>Sistem Informasi Klinik Belova</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Sistem Informasi Manajemen Rumah Sakit Belova" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

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
            gap: 20px;
        }
        
        .date-display {
            color: var(--text-color);
            font-size: 14px;
        }
        
        .theme-toggle {
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
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
            padding: 20px;
            color: var(--text-color);
            background: var(--bg-banner);
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--shadow-color);
            width: 100%;
            max-width: 1200px;
            transition: all 0.3s ease;
        }
        
        .welcome-banner h2 {
            margin-top: 0;
            animation: fadeIn 1s ease;
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

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            display: inline-block;
            border: 2px solid rgba(255,255,255,0.06);
        }

        .avatar img { width: 100%; height: 100%; object-fit: cover; }

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

        /* Responsive styles for Jadwal modal and PDF iframe containers */
        .jadwal-modal-iframe {
            width: 100%;
            height: 70vh; /* use viewport height for responsiveness */
            border: 1px solid #eee;
            background: #fafafa;
            display: block;
            align-items: flex-start;
            justify-content: flex-start;
            overflow: auto;
            padding: 12px;
            box-sizing: border-box;
        }

        /* Tablet and small desktop */
        @media (max-width: 992px) {
            .tiles { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 768px) {
            .welcome-banner { padding: 12px; }
            /* On medium/smaller screens show 2 columns for better touch targets */
            .tiles { grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 12px; width: calc(100% - 24px); max-width: 100%; }
            .menu-tile { min-height: 140px; }
            .menu-icon { font-size: 2.4rem; margin-bottom: 12px; }
            .menu-label { font-size: 12px; }
            .jadwal-modal-iframe { height: 60vh; }
            .modal-dialog { margin: 10px; width: calc(100% - 20px); }
            .modal-content { border-radius: 8px; }
        }

        /* Phones: keep 2 columns on most phones to match the visual layout; collapse to 1 on very small devices */
        @media (max-width: 420px) {
            .tiles { grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 10px; width: calc(100% - 20px); }
            .menu-tile { min-height: 120px; border-radius: 8px; }
            .menu-icon { font-size: 2rem; margin-bottom: 10px; }
            .menu-label { font-size: 12px; bottom: 10px; }
            .jadwal-modal-iframe { height: 55vh; }
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
            .user-area { order: 2; justify-content: space-between; }
            .welcome-banner { padding: 14px 10px; border-radius: 10px; }
            .welcome-banner h2 { font-size: 18px; line-height: 1.15; }
            .welcome-banner p { display: none; } /* hide subtitle to reduce clutter */
            .menu-search input { padding: 10px; font-size: 14px; }
            /* make controls feel like a compact card */
            .menu-controls { background: rgba(255,255,255,0.02); padding: 10px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.03); }
            .user-area { display:flex; justify-content:space-between; align-items:center; }
            .user-area .info { text-align:left; }
            .menu-search { width:100%; }
            /* On phones hide the long date and show compact time only */
            .date-display .date-full { display: none; }
            .date-display .date-compact { display: block; }
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
                    }
                @endphp
                <img src="{{ asset($logoDark) }}" data-logo-dark="{{ asset($logoDark) }}" data-logo-light="{{ asset($logoLight) }}" alt="Belova Logo" id="logo-image">
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
                <form method="POST" action="{{ route('logout') }}" style="display:inline; margin-left:10px;" id="logout-form">
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
                    }
                @endphp
                <h2><span class="welcome-prefix">Welcome to</span> <span class="sim-name">{{ $simName }}</span></h2>
                <p>Sistem Informasi Manajemen Terintegrasi</p>
            </div>
            
            <div class="menu-grid-wrapper">
                <div class="menu-controls">
                    <div class="menu-search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input id="menuFilter" placeholder="Cari modul" aria-label="Cari modul" />
                    </div>
                    <div class="user-area">
                        <div class="info" style="min-width:120px;">
                            <div style="font-size:13px; font-weight:700;">{{ Auth::user()->name ?? '' }}</div>
                            <div style="font-size:12px; opacity:0.8;">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        <div class="avatar-initials" title="{{ Auth::user()->name ?? '' }}">
                            @php
                                $name = trim(Auth::user()->name ?? '');
                                $initials = collect(explode(' ', $name))->filter()->map(function($p){ return strtoupper(substr($p,0,1)); })->take(2)->join('');
                            @endphp
                            {{ $initials ?: 'U' }}
                        </div>
                    </div>
                </div>
                <div class="tiles">
                @php
                    $userRoles = Auth::user()->roles->pluck('name')->toArray();
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
                   @if(!array_intersect($userRoles, ['Farmasi','Admin','Lab','Beautician']))
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
                   @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Employee']))
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
                   @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Employee','Admin']))
                       onclick="showRoleWarning(event, 'Dokumen Kerja')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-folder-open"></i></div>
                        <div class="menu-badge">Docs</div>
                    </div>
                    <div class="menu-title">Dokumen Kerja</div>
                    <div class="menu-sub">SOP & Template</div>
                </a>

                <a href="/laporan" class="menu-tile tile-laporan animate-item delay-8" data-filter="laporan reports analytics"
                   @if(!array_intersect($userRoles, ['Manager','Hrd','Admin']))
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
                   @if(!array_intersect($userRoles, ['Marketing','Admin']))
                       onclick="showRoleWarning(event, 'Marketing')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="menu-badge">Growth</div>
                    </div>
                    <div class="menu-title">Marketing</div>
                    <div class="menu-sub">Kampanye & Leads</div>
                </a>

                <a href="/finance/billing" class="menu-tile tile-finance animate-item delay-10" data-filter="finance billing kasir"
                   @if(!array_intersect($userRoles, ['Kasir','Admin','Farmasi']))
                       onclick="showRoleWarning(event, 'Finance')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-coins"></i></div>
                        <div class="menu-badge">Billing</div>
                    </div>
                    <div class="menu-title">Finance</div>
                    <div class="menu-sub">Tagihan & Pembayaran</div>
                </a>

                <!-- Row 3: remaining tiles -->
                <a href="/inventory" class="menu-tile tile-inventory animate-item delay-11" data-filter="inventory stok gudang"
                   @if(!array_intersect($userRoles, ['Inventaris','Admin']))
                       onclick="showRoleWarning(event, 'Inventory')"
                   @endif>
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-box"></i></div>
                        <div class="menu-badge">Stock</div>
                    </div>
                    <div class="menu-title">Inventory</div>
                    <div class="menu-sub">Barang & Persediaan</div>
                </a>

                <a href="/akreditasi" class="menu-tile tile-akreditasi animate-item delay-12" data-filter="akreditasi quality compliance"
                   @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Employee','Admin']))
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
                    @if(!array_intersect($userRoles, ['Hrd','Ceo','Manager','Employee','Admin']))
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
                
                <!-- Belova Mengaji (Coming Soon) -->
                <a href="#" class="menu-tile tile-marketing animate-item delay-16" id="belova-mengaji-tile" data-filter="mengaji islam belajar doa" onclick="showComingSoon(event, 'Belova Mengaji')">
                    <div class="menu-top">
                        <div class="menu-icon"><i class="fas fa-book-reader"></i></div>
                        <div class="menu-badge">Coming Soon...</div>
                    </div>
                    <div class="menu-title">Belova Mengaji</div>
                    <div class="menu-sub">Module coming soon</div>
                </a>
                </div>
            </div>
            </div>
            <!-- Jadwal Modal -->
                        <!-- Jadwal Improved Modal -->
                                    <div class="modal fade" id="jadwalModal" tabindex="-1" role="dialog" aria-labelledby="jadwalModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-fullscreen" role="document" style="max-width:1800px; width:95vw;">
                                            <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="jadwalModalLabel">Cetak Jadwal</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <ul class="nav nav-tabs" id="jadwalTab" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="karyawan-tab" data-toggle="tab" href="#karyawan" role="tab" aria-controls="karyawan" aria-selected="true">Karyawan</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="dokter-tab" data-toggle="tab" href="#dokter" role="tab" aria-controls="dokter" aria-selected="false">Dokter</a>
                                            </li>
                                        </ul>
                                        <div class="tab-content mt-3" id="jadwalTabContent">
                                            <!-- Karyawan Tab -->
                                            <div class="tab-pane fade show active" id="karyawan" role="tabpanel" aria-labelledby="karyawan-tab">
                                                <div class="form-row mb-3">
                                                    <div class="col-md-4 d-flex align-items-end">
                                                            <div style="width:100%">
                                                                <label for="jadwal-week">Periode (Minggu)</label>
                                                                <div class="d-flex" style="gap:8px;">
                                                                    <input type="hidden" id="jadwal-week" value="{{ date('Y-\WW') }}">
                                                                    <button type="button" id="thisWeekBtn" class="btn btn-outline-light" style="height:38px; white-space:nowrap;">This Week</button>
                                                                    <button type="button" id="nextWeekBtn" class="btn btn-outline-light" style="height:38px; white-space:nowrap;">Next Week</button>
                                                                </div>
                                                            </div>
                                                            <button id="downloadJadwalImageBtn" class="btn btn-primary ml-2 mb-1" style="height:38px; white-space:nowrap;">Download Jadwal (Gambar)</button>
                                                    </div>
                                                </div>
                                                <canvas id="jadwalPdfCanvas" style="display:none;"></canvas>
                                                <div id="jadwal-karyawan-pdf" class="jadwal-modal-iframe">
                                                    <span>Pilih klinik dan periode untuk melihat jadwal karyawan.</span>
                                                </div>
                                            </div>
                                            <!-- Dokter Tab -->
                                            <div class="tab-pane fade" id="dokter" role="tabpanel" aria-labelledby="dokter-tab">
                                                <div class="form-row mb-3">
                                                    <div class="col-md-4">
                                                        <label for="jadwal-klinik-dokter">Klinik</label>
                                                        <select class="form-control" id="jadwal-klinik-dokter"></select>
                                                    </div>
                                                    <div class="col-md-4 d-flex align-items-end">
                                                        <div style="width:100%">
                                                            <label for="jadwal-month">Periode (Bulan)</label>
                                                            <input type="month" class="form-control" id="jadwal-month" value="{{ date('Y-m') }}">
                                                        </div>
                                                        <button id="downloadJadwalDokterImageBtn" class="btn btn-primary ml-2 mb-1" style="height:38px; white-space:nowrap;">Download Jadwal Dokter (Gambar)</button>
                                                    </div>
                                                </div>
                                                <canvas id="jadwalDokterPdfCanvas" style="display:none;"></canvas>
                                                <div id="jadwal-dokter-pdf" class="jadwal-modal-iframe">
                                                    <span>Pilih klinik dan periode untuk melihat jadwal dokter.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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

        // Download Jadwal Dokter (Gambar) button logic (all pages)
        $('#downloadJadwalDokterImageBtn').on('click', function() {
            var clinicId = $('#jadwal-klinik-dokter').val();
            var month = $('#jadwal-month').val();
            if (!month) {
                Swal.fire({icon:'warning',text:'Pilih periode bulan terlebih dahulu.'});
                return;
            }
            var url = '/hrd/dokter-schedule/print?month='+month+(clinicId ? '&clinic_id='+clinicId : '');
            var canvas = document.getElementById('jadwalDokterPdfCanvas');
            renderPdfToSingleImage(url, 'jadwal_dokter_' + month, canvas);
        });
        // System update modal
        if (!localStorage.getItem('systemUpdateModalShown')) {
            $('#systemUpdateModal').modal('show');
            localStorage.setItem('systemUpdateModalShown', '1');
        }
        $('#info-update-btn').on('click', function() {
            $('#systemUpdateModal').modal('show');
        });
        // Jadwal Improved Modal logic
        $('#jadwal-menu-tile').on('click', function() {
            $('#jadwalModal').modal('show');
            // Load jadwal for active tab when modal opens
            setTimeout(function() {
                if ($('#karyawan-tab').hasClass('active')) {
                    loadKaryawanPDF();
                } else if ($('#dokter-tab').hasClass('active')) {
                    loadDokterPDF();
                }
            }, 300); // Wait for modal animation
        });

        // Download Jadwal (Gambar) button logic (all pages)
        $('#downloadJadwalImageBtn').on('click', function() {
            var week = $('#jadwal-week').val();
            if (!week) {
                Swal.fire({icon:'warning',text:'Pilih periode minggu terlebih dahulu.'});
                return;
            }
            var startDate = moment(week, 'YYYY-\WW').startOf('isoWeek').format('YYYY-MM-DD');
            var url = '/hrd/schedule/print?start_date='+startDate;
            var canvas = document.getElementById('jadwalPdfCanvas');
            renderPdfToSingleImage(url, 'jadwal_karyawan_' + startDate, canvas);
        });

        // Helper: compute ISO week start (Monday) for a given Date or 'this'/'next'
        function getIsoWeekStartDateFromDate(d) {
            // clone
            const date = new Date(d.getTime());
            // ISO week starts Monday; getDay() returns 0-6 (Sun-Sat)
            const day = (date.getDay() + 6) % 7; // 0=Mon, 6=Sun
            date.setDate(date.getDate() - day);
            return date;
        }

        function toWeekInputValue(date) {
            // date is JS Date representing start of ISO week
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const isoWeekNumber = moment(date).isoWeek();
            return year + '-W' + String(isoWeekNumber).padStart(2, '0');
        }

        // Provide fallback currentStartDate in case week input is unsupported on some phones
        let currentStartDate = getIsoWeekStartDateFromDate(new Date());

        $('#thisWeekBtn').on('click', function() {
            currentStartDate = getIsoWeekStartDateFromDate(new Date());
            $('#jadwal-week').val(toWeekInputValue(currentStartDate));
            loadKaryawanPDF();
        });

        $('#nextWeekBtn').on('click', function() {
            const next = new Date();
            next.setDate(next.getDate() + 7);
            currentStartDate = getIsoWeekStartDateFromDate(next);
            $('#jadwal-week').val(toWeekInputValue(currentStartDate));
            loadKaryawanPDF();
        });

        // On change of native week input, update currentStartDate and load PDF
        $('#jadwal-week').on('change', function() {
            const w = $(this).val();
            if (!w) return;
            // moment can parse 'YYYY-Www'
            const start = moment(w, 'YYYY-\WW').startOf('isoWeek').toDate();
            currentStartDate = getIsoWeekStartDateFromDate(start);
            loadKaryawanPDF();
        });

        // Fetch klinik list for both selectors (AJAX, replace with your endpoint)
        function fetchKlinikList(selectId) {
            $.get('/marketing/clinics', function(data) {
                var select = $(selectId);
                select.empty();
                select.append('<option value="">Semua Klinik</option>');
                var klinikList = [];
                if (data && data.success && Array.isArray(data.data)) {
                    klinikList = data.data;
                } else if (Array.isArray(data)) {
                    klinikList = data;
                }
                klinikList.forEach(function(klinik) {
                    select.append('<option value="'+klinik.id+'">'+klinik.nama+'</option>');
                });
                // If this is dokter selector, load PDF after populating
                if (selectId === '#jadwal-klinik-dokter') {
                    loadDokterPDF();
                }
            });
        }
    // Load klinik list for dokter, then load PDF if tab is active
    fetchKlinikList('#jadwal-klinik-dokter');
    $('#jadwal-klinik-dokter').on('change', loadDokterPDF);

        // Render PDF into a container using pdf.js (better than iframe embedding)
        async function renderPdfIntoContainer(containerEl, url, filenameHint) {
            const container = $(containerEl);
            container.empty();
            const loadingMessage = $('<div>').text('Memuat jadwal...').css({padding: '12px'});
            container.append(loadingMessage);

            try {
                const loadingTask = pdfjsLib.getDocument(url);
                const pdf = await loadingTask.promise;
                container.empty();

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const viewport = page.getViewport({ scale: 1.2 });
                    const canvas = document.createElement('canvas');
                    canvas.style.display = 'block';
                    canvas.style.marginBottom = '12px';
                    canvas.width = Math.round(viewport.width);
                    canvas.height = Math.round(viewport.height);
                    const ctx = canvas.getContext('2d');
                    await page.render({ canvasContext: ctx, viewport }).promise;
                    container.append(canvas);
                }

                // Add a small hint/fallback link to open the PDF in a new tab
                const openLink = $('<a>').attr('href', url).attr('target', '_blank').text('Buka PDF di tab baru');
                openLink.css({display: 'inline-block', marginTop: '8px'});
                container.append(openLink);
            } catch (err) {
                console.error('Failed to render PDF into container', err);
                container.empty();
                const msg = $('<div>').text('Gagal menampilkan jadwal. ').css({padding:'12px'});
                const openLink = $('<a>').attr('href', url).attr('target', '_blank').text('Buka PDF di tab baru');
                msg.append(openLink);
                container.append(msg);
            }
        }

        // Load PDF for karyawan
        function loadKaryawanPDF() {
            var week = $('#jadwal-week').val();
            if (!week) return;
            var startDate = moment(week, 'YYYY-\WW').startOf('isoWeek').format('YYYY-MM-DD');
            var url = '/hrd/schedule/print?start_date='+startDate;
            renderPdfIntoContainer('#jadwal-karyawan-pdf', url, 'jadwal_karyawan_' + startDate);
        }
        $('#jadwal-week').on('change', loadKaryawanPDF);

        // Load PDF for dokter
        function loadDokterPDF() {
            var clinicId = $('#jadwal-klinik-dokter').val();
            var month = $('#jadwal-month').val();
            if (!month) return;
            var url = '/hrd/dokter-schedule/print?month='+month+(clinicId ? '&clinic_id='+clinicId : '');
            renderPdfIntoContainer('#jadwal-dokter-pdf', url, 'jadwal_dokter_' + month);
        }
        $('#jadwal-month').on('change', loadDokterPDF);

        // Tab switch: load PDF if already selected
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('id') === 'karyawan-tab') {
                loadKaryawanPDF();
            } else {
                loadDokterPDF();
            }
        });
        // Print Jadwal Dokter by clinic name
        $('.print-jadwal-dokter-btn').on('click', function() {
            var clinicId = $(this).data('clinic-id');
            window.open("{{ route('hrd.dokter-schedule.print') }}?clinic_id=" + clinicId, '_blank');
            $('#jadwalModal').modal('hide');
        });
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
</body>
</html>
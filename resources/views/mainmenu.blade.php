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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            padding: 20px;
            width: 100%;
            max-width: 1200px;
        }
        
        .menu-tile {
            height: 180px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .menu-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow-color);
        }
        
        .menu-tile:before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            top: -100%;
            left: -100%;
            transition: all 0.5s;
        }
        
        .menu-tile:hover:before {
            top: 0;
            left: 0;
        }
        
        .menu-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .menu-tile:hover .menu-icon {
            transform: scale(1.1);
        }
        
        .menu-label {
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            font-size: 14px;
            position: absolute;
            bottom: 15px;
        }
        
        .tile-erm { background-color: #00B4DB; }
        .tile-hrd { background-color: #50C878; }
        .tile-inventory { background-color: #9B59B6; }
        .tile-marketing { background-color: #FF7F50; }
        .tile-finance { background-color: #FFC300; }
        .tile-dokumen { background-color: #3498DB; }
        .tile-lab { background-color: #E74C3C; }
        .tile-akreditasi { background-color: #1ABC9C; }
        
        .footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            width: 100%;
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
    </style>
</head>

<body>
    <div class="page-wrapper">
        <!-- Top Bar -->
        <div class="topbar">
            <div class="logo">
                <img src="{{ asset('img/logo-belovacorp-bw.png')}}" alt="Belova Logo" id="logo-image">
            </div>
            <div class="topbar-right">
                <div class="date-display" id="date-time-display">
                    {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }}
                </div>
                <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="theme-toggle" id="info-update-btn" title="Informasi Update">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="welcome-banner">
                <h2><span class="welcome-prefix">Welcome to</span> <span class="sim-name">SIM Klinik Belova</span></h2>
                <p>Sistem Informasi Manajemen Terintegrasi</p>
            </div>
            
            <div class="menu-grid">
                <!-- ERM Tile -->
                <a href="/erm/login" class="menu-tile tile-erm animate-item delay-1">
                    <div class="menu-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="menu-label">ERM</div>
                </a>
                
                <!-- HRD Tile -->
                <a href="/hrd/login" class="menu-tile tile-hrd animate-item delay-2">
                    <div class="menu-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="menu-label">HRD</div>
                </a>
                
                <!-- Inventory Tile -->
                <a href="/inventory/login" class="menu-tile tile-inventory animate-item delay-3">
                    <div class="menu-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="menu-label">INVENTORY</div>
                </a>
                
                <!-- Marketing Tile -->
                <a href="/marketing/login" class="menu-tile tile-marketing animate-item delay-4">
                    <div class="menu-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="menu-label">MARKETING</div>
                </a>
                
                <!-- Finance Tile -->
                <a href="/finance/login" class="menu-tile tile-finance animate-item delay-5">
                    <div class="menu-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="menu-label">FINANCE</div>
                </a>
                
                <!-- Dokumen Kerja Tile -->
                <a href="/workdoc/login" class="menu-tile tile-dokumen animate-item delay-6">
                    <div class="menu-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="menu-label">WORKDOC</div>
                </a>
                
                <!-- Cuatomer Tile -->
                <a href="/customer/login" class="menu-tile tile-lab animate-item delay-7">
                    <div class="menu-icon">
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <div class="menu-label">PENILAIAN PELANGGAN</div>
                </a>
                
                <!-- Akreditasi Tile -->
                <a href="/akreditasi/login" class="menu-tile tile-akreditasi animate-item delay-8">
                    <div class="menu-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <div class="menu-label">AKREDITASI</div>
                </a>
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
                    logoImage.src = "{{ asset('img/logo-belovacorp-bw.png') }}";
                } else {
                    themeIcon.className = 'fas fa-moon';
                    bootstrapStyle.href = "{{ asset('dastone/default/assets/css/bootstrap.min.css') }}";
                    appStyle.href = "{{ asset('dastone/default/assets/css/app.min.css') }}";
                    // If you have a light version of the logo, you can set it here
                    logoImage.src = "{{ asset('img/logo-belovacorp-bw.png') }}";
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
                const formatted = `${dayName}, ${day} ${month} ${year} ${hours}:${minutes}:${seconds}`;
                document.getElementById('date-time-display').textContent = formatted;
            }
            setInterval(updateDateTime, 1000);
            updateDateTime();
        });
    </script>
    <script>
    $(document).ready(function() {
        if (!localStorage.getItem('systemUpdateModalShown')) {
            $('#systemUpdateModal').modal('show');
            localStorage.setItem('systemUpdateModalShown', '1');
        }
        $('#info-update-btn').on('click', function() {
            $('#systemUpdateModal').modal('show');
        });
    });
    </script>
</body>
</html>
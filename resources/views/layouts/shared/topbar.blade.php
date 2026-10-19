@php
    $topbarShowThemeToggle = $topbarShowThemeToggle ?? true;
    $topbarProfileRoute = $topbarProfileRoute ?? 'hrd.employee.profile';
    $topbarDateTimeId = $topbarDateTimeId ?? 'shared-topbar-date-time-display';
    $topbarUser = Auth::user();
    $topbarPhotoPath = null;

    if ($topbarUser && $topbarUser->employee && $topbarUser->employee->photo) {
        $topbarPhotoPath = $topbarUser->employee->photo;
    } elseif ($topbarUser && $topbarUser->dokter && $topbarUser->dokter->photo) {
        $topbarPhotoPath = $topbarUser->dokter->photo;
    }

    $topbarOnlineUsers = $topbarUser ? \App\Http\Controllers\AuthController::getActiveUserEmotions() : [];
    $topbarEmotionOptions = \App\Http\Controllers\AuthController::emotionCatalog();
    $topbarCurrentEmotion = session('user_emotion', \App\Http\Controllers\AuthController::DEFAULT_EMOTION);
    // Bell: what is waiting on the user (same counts as the sidebar badges, which are cached per user)
    $topbarAttention = \App\Support\Navigation\AttentionItems::forUser($topbarUser);
@endphp

<link rel="stylesheet" href="{{ asset('css/belova-topbar.css') }}?v={{ filemtime(public_path('css/belova-topbar.css')) }}">

<!-- Top Bar Start -->
<div class="topbar" style="{{ \App\Support\ClinicTheme::cssVars('sb') }}">
    <nav class="navbar-custom belova-topbar">
        <ul class="list-unstyled topbar-nav mb-0">
            <li>
                <button class="nav-link button-menu-mobile" title="Tampilkan / sembunyikan menu">
                    <i data-feather="menu" class="align-self-center topbar-icon"></i>
                </button>
            </li>
        </ul>

        <div class="belova-online" id="belovaOnline">
            <button type="button" class="belova-online-count" id="belovaOnlineToggle" title="Lihat semua yang sedang online">
                <span class="dot"></span><span id="belovaOnlineCount">{{ count($topbarOnlineUsers) }}</span> online
            </button>
            <div class="emotion-board" id="user-emotion-board"></div>
            <div class="belova-online-panel" id="belovaOnlinePanel"></div>
        </div>

        <div class="belova-topbar-right">
            <div class="date-time-display" id="{{ $topbarDateTimeId }}"></div>

            @if($topbarShowThemeToggle)
                <input type="checkbox" id="darkModeSwitch" class="shared-theme-toggle-input">
                <button type="button" class="shared-theme-toggle" id="sharedThemeToggle" title="Ganti tema" aria-label="Ganti tema">
                    <i class="fas fa-sun"></i>
                </button>
            @endif

            <div class="dropdown belova-bell">
                <button type="button" class="shared-theme-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        title="Perlu Perhatian" aria-label="Perlu Perhatian{{ $topbarAttention['total'] > 0 ? ': ' . $topbarAttention['total'] . ' menunggu' : '' }}">
                    <i data-feather="bell" style="width: 17px; height: 17px;"></i>
                    @if ($topbarAttention['total'] > 0)
                        <span class="belova-bell-count">{{ $topbarAttention['total'] > 99 ? '99+' : $topbarAttention['total'] }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-right belova-attention-menu">
                    <div class="belova-attention-head">
                        <strong>Perlu Perhatian</strong>
                        <span>{{ $topbarAttention['total'] > 0 ? $topbarAttention['total'] . ' menunggu tindak lanjut Anda' : 'Semua beres' }}</span>
                    </div>
                    @if (empty($topbarAttention['items']))
                        <div class="belova-attention-empty">
                            <i data-feather="check-circle"></i>
                            <div>Tidak ada persetujuan, pengingat, atau tenggat yang menunggu Anda.</div>
                        </div>
                    @else
                        <ul class="belova-attention-list">
                            @foreach ($topbarAttention['items'] as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="belova-attention-item {{ $item['class'] }}">
                                        <span class="ic"><i data-feather="{{ $item['icon'] }}"></i></span>
                                        <span class="main">
                                            <span class="title">{{ $item['label'] }}</span>
                                            <span class="meta">{{ $item['module'] }} · {{ collect($item['reasons'])->map(fn ($r) => $r['count'] . ' ' . strtolower($r['title']))->implode(', ') }}</span>
                                        </span>
                                        <span class="n is-{{ $item['tone'] }}">{{ $item['count'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <button type="button" class="shared-theme-toggle belova-topbar-info" id="info-update-btn" title="Informasi Update" aria-label="Informasi Update">
                <i class="fas fa-info-circle"></i>
            </button>

            <div class="dropdown">
                <a class="nav-link dropdown-toggle nav-user shared-topbar-profile-trigger" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="nav-user-name shared-topbar-profile-name">{{ $topbarUser->name ?? '-' }}</span>
                    @if($topbarPhotoPath)
                        <img src="{{ route('chat.avatar', ['user' => $topbarUser->id, 'size' => 48]) }}" alt="profile-user" class="shared-topbar-avatar" width="36" height="36" loading="eager" fetchpriority="high" decoding="async" />
                    @else
                        <img src="{{ asset('img/avatar.png') }}" alt="profile-user" class="shared-topbar-avatar" width="36" height="36" loading="eager" fetchpriority="high" decoding="async" />
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item js-open-profile-modal" href="#"><i data-feather="edit-2" class="align-self-center icon-xs icon-dual mr-1"></i> Edit Profil</a>
                    <a class="dropdown-item" href="{{ route($topbarProfileRoute) }}"><i data-feather="user" class="align-self-center icon-xs icon-dual mr-1"></i> Halaman Profil</a>
                    <a class="dropdown-item js-open-emotion-picker" href="#"><i data-feather="smile" class="align-self-center icon-xs icon-dual mr-1"></i> Ganti Mood</a>
                    <div class="dropdown-divider mb-0"></div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a class="dropdown-item" href="#" id="belovaLogoutLink">
                        <i data-feather="power" class="align-self-center icon-xs icon-dual mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
</div>
<!-- Top Bar End -->

@include('partials.topbar_user_modals', [
    'emotionOptions' => $topbarEmotionOptions,
    'currentUserEmotion' => $topbarCurrentEmotion,
    'activeUserEmotions' => $topbarOnlineUsers,
])

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var element = document.getElementById(@json($topbarDateTimeId));
        var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        function pad(n) { return String(n).padStart(2, '0'); }

        function updateSharedTopbarDateTime() {
            if (!element) return;
            var now = new Date();
            element.innerHTML = '<span class="dt-date">' + days[now.getDay()] + ', ' + pad(now.getDate()) + ' ' + months[now.getMonth()] + ' ' + now.getFullYear() + '</span>'
                + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        }

        setInterval(updateSharedTopbarDateTime, 1000);
        updateSharedTopbarDateTime();
    });
</script>

@if($topbarShowThemeToggle)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleButton = document.getElementById('sharedThemeToggle');
            const themeToggleInput = document.getElementById('darkModeSwitch');

            if (!themeToggleButton || !themeToggleInput) {
                return;
            }

            function syncThemeToggleIcon() {
                const icon = themeToggleButton.querySelector('i');

                if (!icon) {
                    return;
                }

                icon.className = themeToggleInput.checked ? 'fas fa-sun' : 'fas fa-moon';
            }

            themeToggleButton.addEventListener('click', function() {
                themeToggleInput.checked = !themeToggleInput.checked;
                themeToggleInput.dispatchEvent(new Event('change', { bubbles: true }));
                if (window.belovaSetThemeCookie) window.belovaSetThemeCookie(themeToggleInput.checked ? 'dark' : 'light');
                syncThemeToggleIcon();
            });

            themeToggleInput.addEventListener('change', syncThemeToggleIcon);

            setTimeout(syncThemeToggleIcon, 0);
        });
    </script>
@endif
